#!/usr/bin/env python3
"""
Fix categories on the newly split VIN-specific products.

The split_cross_vin_variable_products.py script correctly split the products,
but the category assignment logic was flawed (variations don't have their own categories).

This script reads the correct categories from the source API and assigns them
to the split products based on their SKUs and VIN.
"""

import mysql.connector
import requests
import json
import time
from collections import defaultdict

# Database config
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'maxussql',
    'charset': 'utf8mb4'
}

PREFIX = 'wp_'
VIN = 'LSFAM120XNA160733'  # T90 EV

# WooCommerce REST API credentials for SOURCE site
SOURCE_URL = 'https://maxusvanparts.co.uk'
SOURCE_CONSUMER_KEY = 'ck_a6f4f6c976c91a5eab7c91e02fb6579d67c06c0b'
SOURCE_CONSUMER_SECRET = 'cs_c1f0a49bf939f2629d652d3ba7c0e4a49fe869c3'

def fetch_source_products():
    """Fetch ALL products from source site to build SKU -> categories mapping."""
    print("Fetching products from source site...")
    
    sku_categories = {}
    page = 1
    per_page = 100
    
    while True:
        response = requests.get(
            f'{SOURCE_URL}/wp-json/wc/v3/products',
            auth=(SOURCE_CONSUMER_KEY, SOURCE_CONSUMER_SECRET),
            params={'per_page': per_page, 'page': page},
            timeout=30
        )
        
        if response.status_code != 200:
            print(f"Error fetching page {page}: {response.status_code}")
            break
        
        products = response.json()
        if not products:
            break
        
        for product in products:
            sku = product.get('sku', '').strip()
            if not sku:
                continue
            
            # Get category IDs and names
            cats = product.get('categories', [])
            cat_info = [(c['id'], c['name']) for c in cats]
            
            if cat_info:
                sku_categories[sku] = cat_info
        
        print(f"  Page {page}: {len(products)} products")
        page += 1
        time.sleep(0.5)  # Rate limiting
        
        # Limit for testing
        if page > 100:  # ~10,000 products
            break
    
    print(f"Total SKUs with categories: {len(sku_categories)}")
    return sku_categories

def get_local_category_mapping(cursor):
    """Build mapping of source category ID -> local term_id."""
    print("Building category mapping...")
    
    cursor.execute(f"""
        SELECT t.term_id, t.name, t.slug, tm.meta_value as source_id
        FROM {PREFIX}terms t
        INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id
        LEFT JOIN {PREFIX}termmeta tm ON t.term_id = tm.term_id AND tm.meta_key = 'source_category_id'
        WHERE tt.taxonomy = 'product_cat'
    """)
    
    mapping = {}
    
    for row in cursor.fetchall():
        if row['source_id']:
            mapping[int(row['source_id'])] = row['term_id']
        else:
            # Try to match by name as fallback
            mapping[row['name']] = row['term_id']
    
    print(f"  Mapped {len(mapping)} categories")
    return mapping

def get_products_to_fix(cursor, vin):
    """Get all variable products with the --VIN suffix that need category fixes."""
    print(f"Finding products for VIN {vin}...")
    
    # Get VIN name from mapping
    vin_names = {
        'LSFAM120XNA160733': 'T90 EV',
        'LSFAB1A0XMA124601': 'T60',
        'LSFAB1A0XMA149502': 'New T60',
        'LSFAM3A0XNA165101': 'E D9',
        'LSFAM3B0XNA165101': 'E D9 RWD Lux',
        'LSFAD1A0XNA160908': 'E Deliver 3',
        'LSFMD1B0XNA162401': 'E Deliver 9',
        'LSFMD1A0XNA162401': 'E Deliver 9 FWD',
        # Add more asneeded
    }
    
    vin_name = vin_names.get(vin, vin)
    
    # Find variable products with "- VIN_NAME" suffix
    cursor.execute(f"""
        SELECT p.ID, p.post_title
        FROM {PREFIX}posts p
        WHERE p.post_type = 'product'
          AND p.post_status = 'publish'
          AND p.post_title LIKE CONCAT('%% - ', %s)
    """, (vin_name,))
    
    products = cursor.fetchall()
    print(f"  Found {len(products)} products with VIN suffix")
    return products

def get_product_skus(cursor, product_id):
    """Get all SKUs from variations of this product."""
    cursor.execute(f"""
        SELECT pm.meta_value as sku
        FROM {PREFIX}posts v
        INNER JOIN {PREFIX}postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
        WHERE v.post_parent = %s
          AND v.post_type = 'product_variation'
          AND v.post_status = 'publish'
    """, (product_id,))
    
    skus = [row['sku'] for row in cursor.fetchall()]
    return skus

def assign_categories(cursor, product_id, category_ids):
    """Assign categories to product, replacing existing assignments."""
    # Delete existing category assignments
    cursor.execute(f"""
        DELETE FROM {PREFIX}term_relationships
        WHERE object_id = %s
          AND term_taxonomy_id IN (
              SELECT term_taxonomy_id
              FROM {PREFIX}term_taxonomy
              WHERE taxonomy = 'product_cat'
          )
    """, (product_id,))
    
    # Insert new assignments
    for cat_id in category_ids:
        cursor.execute(f"""
            INSERT IGNORE INTO {PREFIX}term_relationships (object_id, term_taxonomy_id)
            SELECT %s, term_taxonomy_id
            FROM {PREFIX}term_taxonomy
            WHERE term_id = %s AND taxonomy = 'product_cat'
        """, (product_id, cat_id))

def main():
    # Connect to database
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)
    
    try:
        # Step 1: Fetch source categories for all SKUs
        sku_categories = fetch_source_products()
        
        # Step 2: Build category mapping
        category_mapping = get_local_category_mapping(cursor)
        
        # Step 3: Get products to fix
        products = get_products_to_fix(cursor, VIN)
        
        # Step 4: Fix each product
        stats = {
            'processed': 0,
            'fixed': 0,
            'no_skus': 0,
            'no_source_data': 0
        }
        
        for product in products:
            product_id = product['ID']
            product_title = product['post_title']
            
            # Get SKUs from variations
            skus = get_product_skus(cursor, product_id)
            
            if not skus:
                print(f"  {product_title}: No SKUs found")
                stats['no_skus'] += 1
                continue
            
            # Collect categories from source for these SKUs
            source_cat_ids = set()
            
            for sku in skus:
                if sku in sku_categories:
                    for source_id, cat_name in sku_categories[sku]:
                        # Try to map source ID to local term_id
                        if source_id in category_mapping:
                            source_cat_ids.add(category_mapping[source_id])
                        elif cat_name in category_mapping:
                            source_cat_ids.add(category_mapping[cat_name])
            
            if not source_cat_ids:
                print(f"  {product_title}: No source categories found for SKUs {skus[:3]}")
                stats['no_source_data'] += 1
                continue
            
            # Assign correct categories
            assign_categories(cursor, product_id, source_cat_ids)
            print(f"  {product_title}: Assigned {len(source_cat_ids)} categories")
            
            stats['fixed'] += 1
            stats['processed'] += 1
        
        # Commit changes
        conn.commit()
        
        # Recalculate category counts
        print("\nRecalculating category counts...")
        cursor.execute(f"""
            UPDATE {PREFIX}term_taxonomy tt
            SET count = (
                SELECT COUNT(*)
                FROM {PREFIX}term_relationships tr
                INNER JOIN {PREFIX}posts p ON tr.object_id = p.ID
                WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
                  AND p.post_status = 'publish'
                  AND p.post_type IN ('product', 'product_variation')
            )
            WHERE tt.taxonomy = 'product_cat'
        """)
        conn.commit()
        
        print("\n" + "=" * 70)
        print("SUMMARY")
        print("=" * 70)
        print(f"Products processed: {stats['processed']}")
        print(f"Products fixed: {stats['fixed']}")
        print(f"Products with no SKUs: {stats['no_skus']}")
        print(f"Products with no source data: {stats['no_source_data']}")
        print("\nDone! Clear cache and verify T90 EV page.")
        
    finally:
        cursor.close()
        conn.close()

if __name__ == '__main__':
    main()
