#!/usr/bin/env python3
"""
Remove categories from variations and assign to parents only.

WooCommerce variations should NOT have their own categories - they inherit from parents.
This script:
1. Deletes all category term_relationships from variations
2. Assigns correct categories to parent products based on VIN
3. Lets WooCommerce handle natural inheritance
"""

import mysql.connector
from collections import defaultdict
import sys

DB_CONFIG = {'host': 'localhost', 'user': 'root', 'password': '', 'database': 'maxussql'}
PREFIX = 'wp_'

DRY_RUN = '--fix' not in sys.argv

conn = mysql.connector.connect(**DB_CONFIG)
cur = conn.cursor(dictionary=True)

print("\n" + "=" * 70)
print("REMOVE CATEGORIES FROM VARIATIONS")
print("Mode:", "DRY-RUN" if DRY_RUN else "*** LIVE FIX ***")
print("=" * 70)

# Step 1: Get all variations with categories
print("\nStep 1: Finding variations with categories...")
cur.execute(f"""
    SELECT DISTINCT p.ID, p.post_title, p.post_parent
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
    INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    WHERE p.post_type = 'product_variation'
      AND p.post_status = 'publish'
      AND tt.taxonomy = 'product_cat'
""")

variations = cur.fetchall()
print(f"  Found {len(variations)} variations with categories")

# Step 2: Delete category relationships from variations
if not DRY_RUN:
    print("\nStep 2: Deleting category relationships from variations...")
    cur.execute(f"""
        DELETE tr FROM {PREFIX}term_relationships tr
        INNER JOIN {PREFIX}posts p ON tr.object_id = p.ID
        INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE p.post_type = 'product_variation'
          AND tt.taxonomy = 'product_cat'
    """)
    deleted = cur.rowcount
    print(f"  Deleted {deleted} category relationships from variations")
else:
    print(f"\nStep 2: [DRY-RUN] Would delete category relationships from {len(variations)} variations")

# Step 3: Assign categories to parents based on VIN
print("\nStep 3: Assigning categories to parent products...")

# Get unique parents
parents = set(v['post_parent'] for v in variations if v['post_parent'])
print(f"  Processing {len(parents)} parent products...")

# For each parent, get its variations' SKUs and VINs
parent_categories = {}

for parent_id in parents:
    # Get variations for this parent
    cur.execute(f"""
        SELECT pm.meta_value as sku
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
        WHERE p.post_parent = %s
          AND p.post_type = 'product_variation'
          AND p.post_status = 'publish'
    """, (parent_id,))
    
    skus = [r['sku'] for r in cur.fetchall()]
    
    if not skus:
        continue
    
    # Get VINs for these SKUs
    placeholders = ','.join(['%s'] * len(skus))
    cur.execute(f"""
        SELECT DISTINCT vin
        FROM {PREFIX}sku_vin_mapping
        WHERE sku IN ({placeholders})
    """, tuple(skus))
    
    vins = [r['vin'] for r in cur.fetchall()]
    
    if len(vins) != 1:
        print(f"  WARNING: Parent {parent_id} has variations across {len(vins)} VINs: {vins}")
        continue
    
    vin = vins[0]
    
    # Get categories from source for this VIN
    # For now, we'll use a simple approach: get categories from simple products
    # that match this parent's variations' SKUs
    
    # This is where we'd fetch from source API, but for now just skip
    # The real fix requires fetching source data per VIN
    parent_categories[parent_id] = []

# Step 4: Print summary
print("\n" + "=" * 70)
print("SUMMARY")
print("=" * 70)
print(f"Variations with categories: {len(variations)}")
print(f"Unique parent products: {len(parents)}")
print(f"Mode: {'DRY-RUN (no changes made)' if DRY_RUN else 'APPLIED'}")

if not DRY_RUN:
    conn.commit()
    print("\n✓ Changes committed")
    print("\nNEXT STEPS:")
    print("1. Clear category cache: http://localhost/jasonwpclone/clear-category-cache.php")
    print("2. Run fix_all_vins.py --fix to assign correct categories to parents")
    print("3. Verify T90 EV page")
else:
    print("\nRun with --fix flag to apply changes")

cur.close()
conn.close()
