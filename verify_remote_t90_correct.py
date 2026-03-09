"""
Verify T90 EV categories on REMOTE - using the correct query from verify_t90_categories.py
"""
from sql_exec import RemoteSQL
import re

VIN = "LSFAM120XNA160733"
PREFIX = "wp_"

def normalize(s):
    """Normalize category name for comparison."""
    s = re.sub(r'\s*&\s*', 'and', s)
    s = re.sub(r'[,\s\-_/()]', '', s)
    return s.lower()

# Connect to remote DB
conn = RemoteSQL()
cur = conn.cursor(dictionary=True)

# Get all products linked to this VIN
cur.execute(f"""
    SELECT DISTINCT p.ID
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id
        AND pm.meta_key = '_sku' AND pm.meta_value != ''
    INNER JOIN {PREFIX}sku_vin_mapping svm
        ON pm.meta_value = svm.sku AND svm.vin = '{VIN}'
    WHERE p.post_type IN ('product','product_variation')
      AND p.post_status = 'publish'
""")

all_ids = [int(r["ID"]) for r in cur.fetchall()]
print(f"Products linked to {VIN}: {len(all_ids)}\n")

if not all_ids:
    print("ERROR: No products found!")
    exit(1)

# Get all categories assigned to these products (batch query to avoid too many placeholders)
# Query in chunks of 1000
categories_dict = {}
chunk_size = 1000

for i in range(0, len(all_ids), chunk_size):
    chunk = all_ids[i:i+chunk_size]
    placeholders = ",".join(str(x) for x in chunk)
    
    cur.execute(f"""
        SELECT DISTINCT
            t_parent.term_id AS parent_id,
            t_parent.name AS parent_name,
            t_child.term_id AS child_id,
            t_child.name AS child_name
        FROM {PREFIX}term_relationships tr
        INNER JOIN {PREFIX}term_taxonomy tt_child
            ON tr.term_taxonomy_id = tt_child.term_taxonomy_id
            AND tt_child.taxonomy = 'product_cat'
        INNER JOIN {PREFIX}terms t_child ON tt_child.term_id = t_child.term_id
        LEFT JOIN {PREFIX}term_taxonomy tt_parent ON tt_child.parent = tt_parent.term_id
        LEFT JOIN {PREFIX}terms t_parent ON tt_parent.term_id = t_parent.term_id
        WHERE tr.object_id IN ({placeholders})
    """)
    
    for row in cur.fetchall():
        parent_id = row['parent_id'] if row['parent_id'] else row['child_id']
        parent_name = row['parent_name'] if row['parent_name'] else row['child_name']
        categories_dict[parent_id] = parent_name

main_categories = list(categories_dict.values())
main_categories.sort()

print(f"Local DB categories for {VIN}:")
print(f"  Main categories: {len(main_categories)}\n")

print("Category list:")
for cat in main_categories:
    print(f"  - {cat}")

# Check for wrong categories
wrong_cats = [
    'Air Intake System',
    'Emission Exhaust System', 
    'Fuel Storage & Handling',
    'Power Energy Storage & Link Wire',
    'Power Generation'
]

print(f"\n{'='*70}")
print("Extra categories (NOT on source - should not be here):")
print(f"{'='*70}")
found_wrong = []
for cat in main_categories:
    if cat in wrong_cats:
        found_wrong.append(cat)
        print(f"  - {cat}")

if not found_wrong:
    print("  ✓ No wrong categories!")

# Check Fuel Storage products
cur.execute(f"""
    SELECT DISTINCT p.ID, p.post_title
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id
        AND pm.meta_key = '_sku' AND pm.meta_value != ''
    INNER JOIN {PREFIX}sku_vin_mapping svm
        ON pm.meta_value = svm.sku AND svm.vin = '{VIN}'
    INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
    INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
    WHERE p.post_type IN ('product','product_variation')
      AND p.post_status = 'publish'
      AND tt.taxonomy = 'product_cat'
      AND t.name = 'Fuel Storage & Handling'
    LIMIT 10
""")

fuel_products = cur.fetchall()
print(f"\n{'='*70}")
print("SPECIFIC CHECK: 'Fuel Storage and Handling'")
print(f"{'='*70}")
print(f"  Products with this category: {len(fuel_products)}")
if fuel_products:
    print(f"  Sample products:")
    for prod in fuel_products[:5]:
        print(f"    - {prod['post_title']} (ID: {prod['ID']})")

conn.close()

print(f"\n{'='*70}")
print("SUMMARY (REMOTE)")
print("=" * 70)
print(f"Total products: {len(all_ids)}")
print(f"Main categories: {len(main_categories)}")
print(f"Wrong categories: {len(found_wrong)}")
print(f"'Fuel Storage' products: {len(fuel_products)}")
