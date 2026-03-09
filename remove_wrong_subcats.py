#!/usr/bin/env python3
"""
Remove subcategories of wrong parent categories for T90 EV products
"""
import mysql.connector
import sys

T90_EV_VIN = "LSFAM120XNA160733"
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"
DRY_RUN = "--fix" not in sys.argv

print("=" * 80)
print("REMOVE WRONG SUBCATEGORIES FROM T90 EV PRODUCTS")
print("=" * 80)
print(f"Mode: {'DRY-RUN' if DRY_RUN else '*** APPLYING FIXES ***'}")
print()

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

# Wrong parent categories (diesel-specific)
wrong_parent_cats = [
    'Air Intake System',
    'Emission Exhaust System',
    'Fuel Storage & Handling',
    'Power Generation'
]

# EXCLUDE these electric categories even if they're children of wrong parents
# (database structure issue - electric cats wrongly nested under diesel parent)
keep_electric_cats = [
    'EPT System',
    'EPT System1',
    'EPT System2',
    'EPT System3',
    'Battery and electric drive system',
    'electric drive system',
    'electric drive system1',
    'Motor controller and accessories',
    'The internal parts of reduction'
]

# Get IDs of wrong parent categories and their children
cur.execute(f"""
    SELECT 
        t_parent.term_id as parent_id,
        t_parent.name as parent_name,
        t_child.term_id as child_id,
        t_child.name as child_name
    FROM wp_terms t_parent
    INNER JOIN wp_term_taxonomy tt_parent ON t_parent.term_id = tt_parent.term_id
        AND tt_parent.taxonomy = 'product_cat'
    LEFT JOIN wp_term_taxonomy tt_child ON tt_parent.term_id = tt_child.parent
    LEFT JOIN wp_terms t_child ON tt_child.term_id = t_child.term_id
    WHERE t_parent.name IN ({','.join(['%s'] * len(wrong_parent_cats))})
    ORDER BY t_parent.name, t_child.name
""", tuple(wrong_parent_cats))

cat_structure = cur.fetchall()

# Build list of all category IDs to remove (parents + children)
all_wrong_cat_ids = set()
categories_info = {}

for row in cat_structure:
    parent_id = row['parent_id']
    parent_name = row['parent_name']
    
    # Always include parent category
    all_wrong_cat_ids.add(parent_id)
    
    if parent_name not in categories_info:
        categories_info[parent_name] = {
            'id': parent_id,
            'subcategories': []
        }
    
    # Include subcategory only if NOT in the keep list
    if row['child_id'] and row['child_name'] not in keep_electric_cats:
        all_wrong_cat_ids.add(row['child_id'])
        categories_info[parent_name]['subcategories'].append({
            'id': row['child_id'],
            'name': row['child_name']
        })

print("Wrong categories and their subcategories:")
for parent_name, info in categories_info.items():
    print(f"\n{parent_name} (ID: {info['id']})")
    if info['subcategories']:
        for sc in info['subcategories']:
            print(f"  - {sc['name']} (ID: {sc['id']})")
    else:
        print(f"  (no subcategories)")

print(f"\nTotal category IDs to remove: {len(all_wrong_cat_ids)}")
print()

# Find T90 EV products that have any of these categories
if all_wrong_cat_ids:
    id_placeholders = ','.join(['%s'] * len(all_wrong_cat_ids))
    
    cur.execute(f"""
        SELECT DISTINCT
            p.ID,
            p.post_title,
            pm_sku.meta_value as sku,
            GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as wrong_cats
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id 
            AND pm_sku.meta_key = '_sku'
        LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id 
            AND pm_var.meta_key = 'attribute_pa_variant'
        INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
            AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
            AND svm.vin = %s
        INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
        INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            AND tt.taxonomy = 'product_cat'
        INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
        WHERE p.post_type IN ('product', 'product_variation')
          AND p.post_status = 'publish'
          AND tt.term_id IN ({id_placeholders})
        GROUP BY p.ID
        ORDER BY p.post_title
    """, (T90_EV_VIN,) + tuple(all_wrong_cat_ids))
    
    products = cur.fetchall()
    
    print("=" * 80)
    print(f"Found {len(products)} products with wrong categories/subcategories:")
    print("=" * 80)
    print()
    
    updates_made = 0
    
    for prod in products:
        print(f"{prod['sku']}: {prod['post_title']}")
        print(f"  Wrong categories: {prod['wrong_cats']}")
        
        if DRY_RUN:
            print(f"  [DRY-RUN] Would remove these categories")
        else:
            # Remove all wrong categories and subcategories
            cur.execute(f"""
                DELETE tr FROM {PREFIX}term_relationships tr
                INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = %s
                  AND tt.taxonomy = 'product_cat'
                  AND tt.term_id IN ({id_placeholders})
            """, (prod['ID'],) + tuple(all_wrong_cat_ids))
            
            rows_deleted = cur.rowcount
            print(f"  [REMOVED] {rows_deleted} category assignments")
            updates_made += 1
        
        print()
    
    if not DRY_RUN and updates_made > 0:
        conn.commit()
        print(f"\n[SUCCESS] Updated {updates_made} products")
    
    print()
    print("=" * 80)
    print("SUMMARY")
    print("=" * 80)
    print(f"Products {'to update' if DRY_RUN else 'updated'}: {len(products)}")
    
    if DRY_RUN:
        print("\nThis was a DRY-RUN. To apply changes, run:")
        print("  python remove_wrong_subcats.py --fix")

conn.close()
