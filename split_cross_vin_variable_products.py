"""
split_cross_vin_variable_products.py

Fixes category bleed by splitting variable products whose variations span multiple VINs
into separate variable products (one per VIN).

Example:
  Before: Product "NUT" (parent 219497) has 64 variations across 5 VINs
          Categories: Union of all VINs (96 categories including "Fuel Storage")
  
  After:  Product "NUT - T90 EV" (new parent) has 2 variations for T90 EV only
          Categories: Only T90 EV categories (42 categories, no "Fuel Storage")
          
          Product "NUT - T60" (new parent) has 8 variations for T60 only
          Categories: Only T60 categories (including "Fuel Storage")
          
          etc. for all VINs

This preserves variable products for legitimate size/color variants while preventing
category bleed from cross-VIN groupings.
"""
import mysql.connector
import sys
from collections import defaultdict

sys.stdout.reconfigure(encoding='utf-8')

# Database config
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

# Dry-run mode (set to False to apply changes)
DRY_RUN = '--fix' not in sys.argv
USE_REMOTE = '--remote' in sys.argv

print("=" * 70)
print("Split Cross-VIN Variable Products")
print("=" * 70)
print(f"Mode: {'DRY-RUN (no changes)' if DRY_RUN else '*** LIVE FIX ***'}")
print(f"Target: {'REMOTE (themed site)' if USE_REMOTE else 'LOCAL (WAMP)'}")
print()

# Connect to database
if USE_REMOTE:
    print("Connecting to remote SQL endpoint...")
    from sql_exec import RemoteSQL
    conn = RemoteSQL()
else:
    print("Connecting to local database...")
    conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

# VIN to vehicle name mapping (for readable product names)
VIN_NAMES = {
    'LSFAM120XNA160733': 'T90 EV',
    'LSFAM11C6RA133899': 'T60',
    'LSFAM11C6RA144501': 'New T60',
    'LSFAL11A4PA157987': 'D9 RWD Lux',
    'LSFAL11A5MA087816': 'D9 RWD Chassis',
    'LSH14J7C2MA122115': 'D9 RWD Std',
    'LSH14J7CXMA114599': 'D9 FWD Lux',
    'LSH14J7C7MA114771': 'D9 FWD Std',
    'LSH14J7C4RV123458': 'D7 High Roof',
    'LSH14J7C3RV123225': 'D7',
    'LSH14J7C9RV123360': 'D7 Low Roof',
    'LSH14J4CXMA165329': 'E D9',
    'LSH14J4C0RV121632': 'E D7',
    'LSH14J7C0SA082498': 'New D9',
    'LSH14C4C5NA129710': 'E D3',
    'LSH14JTC6FA621119': 'A80 Chassis',
    'LSKG5GL16KA060062': 'V80',
}

# Step 1: Find variable products with cross-VIN variations
print("STEP 1: Finding variable products with cross-VIN variations...")
print()

cur.execute(f"""
    SELECT 
        p.ID as parent_id,
        p.post_title as parent_title,
        COUNT(DISTINCT v.ID) as variation_count,
        COUNT(DISTINCT svm.vin) as vin_count,
        GROUP_CONCAT(DISTINCT svm.vin ORDER BY svm.vin SEPARATOR '|||') as vins
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}posts v ON v.post_parent = p.ID AND v.post_type = 'product_variation'
    INNER JOIN {PREFIX}postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE p.post_type = 'product'
      AND p.post_status = 'publish'
    GROUP BY p.ID
    HAVING vin_count > 1
    ORDER BY vin_count DESC, variation_count DESC
""")

cross_vin_products = cur.fetchall()

print(f"Found {len(cross_vin_products)} variable products with cross-VIN variations")
print()

if not cross_vin_products:
    print("No cross-VIN products found. Nothing to split.")
    sys.exit(0)

# Show summary
print("Top 10 products to split:")
for i, prod in enumerate(cross_vin_products[:10], 1):
    vins = prod['vins'].split('|||')
    vin_names = [VIN_NAMES.get(v, v) for v in vins]
    print(f"  {i}. ID {prod['parent_id']}: {prod['parent_title'][:50]}")
    print(f"     {prod['variation_count']} variations across {prod['vin_count']} VINs")
    print(f"     VINs: {', '.join(vin_names)}")
print()

# Step 2: Process each cross-VIN product
print("=" * 70)
print(f"STEP 2: Splitting {len(cross_vin_products)} products...")
print("=" * 70)
print()

stats = {
    'products_processed': 0,
    'new_parents_created': 0,
    'variations_moved': 0,
    'original_parents_deleted': 0,
}

for prod in cross_vin_products:
    parent_id = prod['parent_id']
    parent_title = prod['parent_title']
    
    print(f"\nProcessing: {parent_title} (ID {parent_id})")
    
    # RESUME CHECK: Skip if VIN-specific versions already exist
    cur.execute(f"""
        SELECT COUNT(*) as count 
        FROM {PREFIX}posts 
        WHERE post_type = 'product' 
          AND post_title LIKE %s 
          AND post_status = 'publish'
    """, (f"{parent_title} - %",))
    
    already_split = int(cur.fetchone()['count'])
    if already_split > 0:
        print(f"  ✓ Already processed - {already_split} VIN-specific products exist. Skipping.")
        stats['products_processed'] += 1
        continue
    
    # Get all variations with their VINs
    cur.execute(f"""
        SELECT 
            v.ID as variation_id,
            v.post_name as variation_slug,
            pm.meta_value as sku,
            svm.vin
        FROM {PREFIX}posts v
        INNER JOIN {PREFIX}postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
        INNER JOIN {PREFIX}sku_vin_mapping svm ON pm.meta_value = svm.sku
        WHERE v.post_parent = %s
          AND v.post_type = 'product_variation'
          AND v.post_status = 'publish'
        ORDER BY svm.vin, v.ID
    """, (parent_id,))
    
    variations = cur.fetchall()
    
    # Additional check: Skip if no variations found (all moved already)
    if not variations:
        print(f"  ✓ No variations found - already processed. Skipping.")
        stats['products_processed'] += 1
        continue
    
    # Group variations by VIN
    vin_groups = defaultdict(list)
    for var in variations:
        vin_groups[var['vin']].append(var)
    
    print(f"  Variations grouped into {len(vin_groups)} VINs:")
    for vin, vars in vin_groups.items():
        vin_name = VIN_NAMES.get(vin, vin)
        print(f"    - {vin_name}: {len(vars)} variations")
    
    if DRY_RUN:
        print(f"  [DRY-RUN] Would create {len(vin_groups)} new variable products")
        stats['products_processed'] += 1
        stats['new_parents_created'] += len(vin_groups)
        stats['variations_moved'] += len(variations)
        stats['original_parents_deleted'] += 1
        continue
    
    # For each VIN, create a new parent product
    new_parents = {}
    
    for vin, vars in vin_groups.items():
        vin_name = VIN_NAMES.get(vin, vin)
        new_title = f"{parent_title} - {vin_name}"
        new_slug = f"{prod['parent_id']}-{vin.lower()}"  # Use original ID + VIN for unique slug
        
        # Create new parent product
        cur.execute(f"""
            INSERT INTO {PREFIX}posts (
                post_author, post_date, post_date_gmt, post_content, post_title,
                post_excerpt, post_status, comment_status, ping_status, post_name,
                post_modified, post_modified_gmt, post_parent, guid, menu_order,
                post_type, post_mime_type, comment_count
            )
            SELECT 
                post_author, post_date, post_date_gmt, post_content, %s as post_title,
                post_excerpt, post_status, comment_status, ping_status, %s as post_name,
                post_modified, post_modified_gmt, post_parent, guid, menu_order,
                post_type, post_mime_type, comment_count
            FROM {PREFIX}posts
            WHERE ID = %s
        """, (new_title, new_slug, parent_id))
        
        new_parent_id = cur.lastrowid
        new_parents[vin] = new_parent_id
        
        print(f"    Created new parent: {new_title} (ID {new_parent_id})")
        
        # Copy parent product meta (except SKU - parent doesn't have SKU in variable products)
        cur.execute(f"""
            INSERT INTO {PREFIX}postmeta (post_id, meta_key, meta_value)
            SELECT %s, meta_key, meta_value
            FROM {PREFIX}postmeta
            WHERE post_id = %s
              AND meta_key != '_sku'
        """, (new_parent_id, parent_id))
        
        # Get VIN-specific categories (from one of the variations)
        first_var_id = vars[0]['variation_id']
        cur.execute(f"""
            SELECT DISTINCT tt.term_id, tt.term_taxonomy_id
            FROM {PREFIX}term_relationships tr
            INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tr.object_id = %s
              AND tt.taxonomy = 'product_cat'
        """, (first_var_id,))
        
        categories = cur.fetchall()
        
        # Assign VIN-specific categories to new parent
        for cat in categories:
            cur.execute(f"""
                INSERT IGNORE INTO {PREFIX}term_relationships (object_id, term_taxonomy_id)
                VALUES (%s, %s)
            """, (new_parent_id, cat['term_taxonomy_id']))
        
        print(f"      Assigned {len(categories)} categories")
        
        # Move variations to new parent
        var_ids = [v['variation_id'] for v in vars]
        placeholders = ','.join(['%s'] * len(var_ids))
        cur.execute(f"""
            UPDATE {PREFIX}posts
            SET post_parent = %s
            WHERE ID IN ({placeholders})
        """, [new_parent_id] + var_ids)
        
        print(f"      Moved {len(vars)} variations to new parent")
        
        stats['new_parents_created'] += 1
        stats['variations_moved'] += len(vars)
    
    # Delete original parent product (now empty)
    cur.execute(f"DELETE FROM {PREFIX}posts WHERE ID = %s", (parent_id,))
    cur.execute(f"DELETE FROM {PREFIX}postmeta WHERE post_id = %s", (parent_id,))
    cur.execute(f"DELETE FROM {PREFIX}term_relationships WHERE object_id = %s", (parent_id,))
    
    print(f"  Deleted original parent (ID {parent_id})")
    stats['original_parents_deleted'] += 1
    stats['products_processed'] += 1

if not DRY_RUN:
    conn.commit()
    
    # Recalculate term counts
    print("\n" + "=" * 70)
    print("STEP 3: Recalculating category counts...")
    print("=" * 70)
    
    cur.execute(f"""
        UPDATE {PREFIX}term_taxonomy tt
        SET count = (
            SELECT COUNT(DISTINCT tr.object_id)
            FROM {PREFIX}term_relationships tr
            INNER JOIN {PREFIX}posts p ON tr.object_id = p.ID
            WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
              AND p.post_type IN ('product', 'product_variation')
              AND p.post_status = 'publish'
        )
        WHERE tt.taxonomy = 'product_cat'
    """)
    
    print("Category counts updated")

cur.close()
conn.close()

# Print summary
print("\n" + "=" * 70)
print("SUMMARY")
print("=" * 70)
print(f"Mode: {'DRY-RUN' if DRY_RUN else 'APPLIED'}")
print(f"Products processed: {stats['products_processed']}")
print(f"New parent products created: {stats['new_parents_created']}")
print(f"Variations moved: {stats['variations_moved']}")
print(f"Original parents deleted: {stats['original_parents_deleted']}")
print()

if DRY_RUN:
    print("This was a DRY-RUN. No changes were made.")
    print("Run with --fix flag to apply changes:")
    print("  python split_cross_vin_variable_products.py --fix")
else:
    print("✓ Changes applied successfully!")
    print()
    print("IMPORTANT: Clear category cache:")
    print("  Visit: http://localhost/jasonwpclone/clear-category-cache.php")
    print()
    print("Then verify T90 EV page:")
    print("  Visit: http://localhost/jasonwpclone/maxus-t90-ev/")
    print("  'Fuel Storage & Handling' should no longer appear!")

print("=" * 70)
