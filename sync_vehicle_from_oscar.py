#!/usr/bin/env python3
"""
Sync vehicle products and categories from Oscar database (authoritative source).

This script:
1. Queries Oscar for all parts and categories for a specific vehicle VIN
2. Creates missing categories in WordPress
3. Updates product categories to match Oscar (preserves images and other meta)
4. Handles both simple products and variable product variations

Usage:
    python sync_vehicle_from_oscar.py <VIN> [--fix]
    
Example:
    python sync_vehicle_from_oscar.py LSH14C4C5NA129710       # dry-run
    python sync_vehicle_from_oscar.py LSH14C4C5NA129710 --fix # apply changes
"""

import sys
import psycopg2
import mysql.connector
from collections import defaultdict

# Oscar Database Configuration
OSCAR_CONFIG = {
    'host': '80.95.207.42',
    'port': 5432,
    'user': 'postgres',
    'password': 'N0rwich!',
    'dbname': 'parts_store'
}

# WordPress Database Configuration
WP_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'maxussql',
    'charset': 'utf8mb4'
}

def connect_oscar():
    """Connect to Oscar PostgreSQL database."""
    try:
        conn = psycopg2.connect(
            host=OSCAR_CONFIG['host'],
            port=OSCAR_CONFIG['port'],
            user=OSCAR_CONFIG['user'],
            password=OSCAR_CONFIG['password'],
            dbname=OSCAR_CONFIG['dbname']
        )
        print("✅ Connected to Oscar database")
        return conn
    except Exception as e:
        print(f"❌ Failed to connect to Oscar: {e}")
        sys.exit(1)

def connect_wordpress():
    """Connect to WordPress MySQL database."""
    try:
        conn = mysql.connector.connect(**WP_CONFIG)
        print("✅ Connected to WordPress database")
        return conn
    except Exception as e:
        print(f"❌ Failed to connect to WordPress: {e}")
        sys.exit(1)

def get_oscar_parts(oscar_conn, vin):
    """Get all parts with categories from Oscar for a specific vehicle."""
    cursor = oscar_conn.cursor()
    
    # Query matching the oscar.txt documentation structure
    query = """
        SELECT DISTINCT
            sn.serial,
            pt.title as main_category,
            ct.title as sub_category,
            p.part_number as original_sku
        FROM motorpartsdata_part p
        JOIN motorpartsdata_childtitle ct ON p.child_title_id = ct.id
        JOIN motorpartsdata_parenttitle pt ON ct.parent_id = pt.id
        JOIN motorpartsdata_serialnumber sn ON pt.serial_number_id = sn.id
        WHERE sn.serial = %s
        ORDER BY p.part_number, pt.title, ct.title
    """
    
    cursor.execute(query, (vin,))
    results = cursor.fetchall()
    cursor.close()
    
    # Organize data: {sku: {'parents': set(), 'children': set()}}
    parts_data = defaultdict(lambda: {'parents': set(), 'children': set()})
    
    for serial, main_cat, sub_cat, sku in results:
        if not sku:
            continue
        
        sku = sku.strip()
        if main_cat:
            parts_data[sku]['parents'].add(main_cat.strip())
        if sub_cat:
            parts_data[sku]['children'].add(sub_cat.strip())
    
    return dict(parts_data)

def get_wp_category_map(wp_conn):
    """Get mapping of category names to IDs in WordPress."""
    cursor = wp_conn.cursor(dictionary=True)
    
    query = """
        SELECT t.term_id, t.name, t.slug, tt.parent
        FROM wp_terms t
        JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
        WHERE tt.taxonomy = 'product_cat'
    """
    
    cursor.execute(query)
    results = cursor.fetchall()
    cursor.close()
    
    # Create bidirectional mapping
    name_to_id = {}
    id_to_parent = {}
    
    for row in results:
        name_to_id[row['name']] = row['term_id']
        id_to_parent[row['term_id']] = row['parent']
    
    return name_to_id, id_to_parent

def create_category_if_missing(wp_conn, category_name, parent_id=0, dry_run=True):
    """Create a category in WordPress if it doesn't exist."""
    cursor = wp_conn.cursor(dictionary=True)
    
    # Check if exists
    cursor.execute("""
        SELECT t.term_id 
        FROM wp_terms t
        JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
        WHERE t.name = %s AND tt.taxonomy = 'product_cat'
    """, (category_name,))
    
    result = cursor.fetchone()
    if result:
        cursor.close()
        return result['term_id']
    
    if dry_run:
        print(f"  [DRY-RUN] Would create category: {category_name} (parent: {parent_id})")
        cursor.close()
        return None
    
    # Create slug from name
    import re
    slug = re.sub(r'[^a-z0-9]+', '-', category_name.lower()).strip('-')
    
    # Insert into wp_terms
    cursor.execute("""
        INSERT INTO wp_terms (name, slug, term_group)
        VALUES (%s, %s, 0)
    """, (category_name, slug))
    
    term_id = cursor.lastrowid
    
    # Insert into wp_term_taxonomy
    cursor.execute("""
        INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
        VALUES (%s, 'product_cat', '', %s, 0)
    """, (term_id, parent_id))
    
    wp_conn.commit()
    cursor.close()
    
    print(f"  ✅ Created category: {category_name} (ID: {term_id}, parent: {parent_id})")
    return term_id

def find_product_by_sku(wp_conn, sku):
    """
    Find product by SKU. Returns (product_id, is_variation, parent_id).
    
    Returns:
        tuple: (product_id, is_variation, parent_id)
        - For simple products: (product_id, False, None)
        - For variations: (variation_id, True, parent_id)
        - If not found: (None, False, None)
    """
    cursor = wp_conn.cursor(dictionary=True)
    
    # First try to find as variation
    cursor.execute("""
        SELECT p.ID, p.post_parent, p.post_type
        FROM wp_posts p
        JOIN wp_postmeta pm ON p.ID = pm.post_id
        WHERE pm.meta_key = '_sku' 
        AND pm.meta_value = %s
        AND p.post_type IN ('product', 'product_variation')
        AND p.post_status NOT IN ('trash', 'auto-draft')
        LIMIT 1
    """, (sku,))
    
    result = cursor.fetchone()
    cursor.close()
    
    if not result:
        return None, False, None
    
    is_variation = result['post_type'] == 'product_variation'
    parent_id = result['post_parent'] if is_variation else None
    
    return result['ID'], is_variation, parent_id

def get_current_categories(wp_conn, product_id):
    """Get current category IDs for a product."""
    cursor = wp_conn.cursor()
    
    cursor.execute("""
        SELECT tt.term_id
        FROM wp_term_relationships tr
        JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tr.object_id = %s AND tt.taxonomy = 'product_cat'
    """, (product_id,))
    
    results = cursor.fetchall()
    cursor.close()
    
    return set(row[0] for row in results)

def update_product_categories(wp_conn, product_id, category_ids, dry_run=True):
    """Update product categories (replaces all existing categories)."""
    if not category_ids:
        return
    
    cursor = wp_conn.cursor()
    
    if dry_run:
        # Get current categories for display
        current = get_current_categories(wp_conn, product_id)
        if current != category_ids:
            cursor.execute("SELECT post_title FROM wp_posts WHERE ID = %s", (product_id,))
            result = cursor.fetchone()
            title = result[0] if result else f"Product {product_id}"
            print(f"  [DRY-RUN] Would update product {product_id} ({title})")
            print(f"    Current categories: {current}")
            print(f"    New categories: {category_ids}")
        cursor.close()
        return
    
    # Delete existing category relationships
    cursor.execute("""
        DELETE tr FROM wp_term_relationships tr
        JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tr.object_id = %s AND tt.taxonomy = 'product_cat'
    """, (product_id,))
    
    # Insert new category relationships
    for category_id in category_ids:
        cursor.execute("""
            INSERT INTO wp_term_relationships (object_id, term_taxonomy_id)
            SELECT %s, term_taxonomy_id
            FROM wp_term_taxonomy
            WHERE term_id = %s AND taxonomy = 'product_cat'
        """, (product_id, category_id))
    
    wp_conn.commit()
    cursor.close()

def main():
    if len(sys.argv) < 2:
        print("Usage: python sync_vehicle_from_oscar.py <VIN> [--fix]")
        sys.exit(1)
    
    vin = sys.argv[1]
    dry_run = '--fix' not in sys.argv
    
    print("=" * 80)
    print(f"SYNC VEHICLE FROM OSCAR: {vin}")
    print("=" * 80)
    print(f"Mode: {'DRY-RUN (use --fix to apply changes)' if dry_run else 'APPLYING CHANGES'}")
    print()
    
    # Connect to databases
    oscar_conn = connect_oscar()
    wp_conn = connect_wordpress()
    
    try:
        # Get all parts from Oscar
        print(f"\nFetching parts from Oscar for {vin}...")
        parts_data = get_oscar_parts(oscar_conn, vin)
        print(f"✅ Found {len(parts_data)} unique SKUs in Oscar")
        
        # Get WordPress category mapping
        print("\nFetching WordPress categories...")
        cat_name_to_id, cat_id_to_parent = get_wp_category_map(wp_conn)
        print(f"✅ Found {len(cat_name_to_id)} categories in WordPress")
        
        # Track statistics
        stats = {
            'found_products': 0,
            'not_found_skus': 0,
            'updated_products': 0,
            'created_categories': 0,
            'skipped_variations': 0
        }
        
        not_found_skus = []
        
        # Process each SKU from Oscar
        print(f"\nProcessing {len(parts_data)} SKUs from Oscar...")
        print()
        
        for sku, categories in parts_data.items():
            # Find product in WordPress
            product_id, is_variation, parent_id = find_product_by_sku(wp_conn, sku)
            
            if not product_id:
                stats['not_found_skus'] += 1
                not_found_skus.append(sku)
                continue
            
            stats['found_products'] += 1
            
            # For variations, we update the parent product's categories
            target_product_id = parent_id if is_variation else product_id
            
            # Build category ID list
            category_ids = set()
            
            # Add parent categories
            for parent_name in categories['parents']:
                if parent_name in cat_name_to_id:
                    category_ids.add(cat_name_to_id[parent_name])
                else:
                    # Create missing parent category
                    new_id = create_category_if_missing(wp_conn, parent_name, parent_id=0, dry_run=dry_run)
                    if new_id:
                        cat_name_to_id[parent_name] = new_id
                        category_ids.add(new_id)
                    stats['created_categories'] += 1
            
            # Add child categories
            for child_name in categories['children']:
                if child_name in cat_name_to_id:
                    category_ids.add(cat_name_to_id[child_name])
                else:
                    # Try to find parent category ID for this child
                    parent_cat_id = 0
                    for parent_name in categories['parents']:
                        if parent_name in cat_name_to_id:
                            parent_cat_id = cat_name_to_id[parent_name]
                            break
                    
                    # Create missing child category
                    new_id = create_category_if_missing(wp_conn, child_name, parent_id=parent_cat_id, dry_run=dry_run)
                    if new_id:
                        cat_name_to_id[child_name] = new_id
                        category_ids.add(new_id)
                    stats['created_categories'] += 1
            
            # Update product categories
            current_cats = get_current_categories(wp_conn, target_product_id)
            if current_cats != category_ids:
                update_product_categories(wp_conn, target_product_id, category_ids, dry_run=dry_run)
                stats['updated_products'] += 1
        
        # Print summary
        print("\n" + "=" * 80)
        print("SUMMARY")
        print("=" * 80)
        print(f"Total SKUs in Oscar: {len(parts_data)}")
        print(f"Found in WordPress: {stats['found_products']}")
        print(f"Not found in WordPress: {stats['not_found_skus']}")
        print(f"Products updated: {stats['updated_products']}")
        print(f"Categories created: {stats['created_categories']}")
        
        if not_found_skus and len(not_found_skus) <= 20:
            print(f"\nSKUs not found in WordPress:")
            for sku in not_found_skus:
                print(f"  - {sku}")
        elif not_found_skus:
            print(f"\nFirst 20 SKUs not found in WordPress:")
            for sku in not_found_skus[:20]:
                print(f"  - {sku}")
            print(f"  ... and {len(not_found_skus) - 20} more")
        
        if dry_run:
            print("\n⚠️  DRY-RUN mode: No changes were made. Use --fix to apply changes.")
        else:
            print("\n✅ Changes applied successfully!")
    
    finally:
        oscar_conn.close()
        wp_conn.close()

if __name__ == '__main__':
    main()
