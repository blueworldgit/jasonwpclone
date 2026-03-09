"""
Verify T90 EV categories after the fix - compare local vs source.
"""
import mysql.connector
import json
import re

VIN = "LSFAM120XNA160733"
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

def normalize(s):
    """Normalize category name for comparison."""
    s = re.sub(r'\s*&\s*', 'and', s)
    s = re.sub(r'[,\s\-_/()]', '', s)
    return s.lower()

# Connect to DB
conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

# Get all products linked to this VIN (with variant attribute join)
cur.execute(f"""
    SELECT DISTINCT p.ID
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id
        AND pm.meta_key = '_sku' AND pm.meta_value != ''
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id 
        AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm
        ON pm.meta_value = svm.sku AND svm.vin = %s
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
    WHERE p.post_type IN ('product','product_variation')
      AND p.post_status = 'publish'
""", (VIN,))

all_ids = [r["ID"] for r in cur.fetchall()]
print(f"Products linked to {VIN}: {len(all_ids)}\n")

if not all_ids:
    print("ERROR: No products found!")
    exit(1)

# Get all categories assigned to these products
placeholders = ",".join(["%s"] * len(all_ids))
cur.execute(f"""
    SELECT DISTINCT
        t_parent.term_id AS parent_id,
        t_parent.name AS parent_name,
        t_child.term_id AS child_id,
        t_child.name AS child_name,
        COUNT(DISTINCT tr.object_id) AS product_count
    FROM {PREFIX}term_relationships tr
    INNER JOIN {PREFIX}term_taxonomy tt_child
        ON tr.term_taxonomy_id = tt_child.term_taxonomy_id
        AND tt_child.taxonomy = 'product_cat'
    INNER JOIN {PREFIX}terms t_child ON tt_child.term_id = t_child.term_id
    LEFT JOIN {PREFIX}term_taxonomy tt_parent ON tt_child.parent = tt_parent.term_id
    LEFT JOIN {PREFIX}terms t_parent ON tt_parent.term_id = t_parent.term_id
    WHERE tr.object_id IN ({placeholders})
    GROUP BY t_child.term_id
    ORDER BY t_parent.name, t_child.name
""", tuple(all_ids))

local_rows = cur.fetchall()
cur.close()
conn.close()

# Organize local categories
local_main = set()
local_sub = {}  # parent_name -> set of child_names

for row in local_rows:
    if row["parent_id"] is None:
        # Main category
        local_main.add(row["child_name"])
    else:
        # Sub category
        parent = row["parent_name"]
        if parent not in local_sub:
            local_sub[parent] = set()
        local_sub[parent].add(row["child_name"])

print(f"Local DB categories for {VIN}:")
print(f"  Main categories: {len(local_main)}")
print(f"  Subcategories: {sum(len(subs) for subs in local_sub.values())}\n")

# Load source categories from JSON
json_file = f"c:\\pythonstuff\\wpimportcollection\\categories_{VIN}.json"
try:
    with open(json_file, 'r', encoding='utf-8') as f:
        source = json.load(f)
except FileNotFoundError:
    print(f"ERROR: {json_file} not found. Run get_serial_categories.py first.")
    exit(1)

source_main = set()
source_sub = {}

for mc in source["categories"]:
    source_main.add(mc["name"])
    if mc["subcategories"]:
        source_sub[mc["name"]] = {sc["name"] for sc in mc["subcategories"]}

print(f"Source site categories for {VIN}:")
print(f"  Main categories: {len(source_main)}")
print(f"  Subcategories: {sum(len(subs) for subs in source_sub.values())}\n")

# Compare main categories
print("="*70)
print("MAIN CATEGORY COMPARISON")
print("="*70)

# Normalize for comparison
local_main_norm = {normalize(name): name for name in local_main}
source_main_norm = {normalize(name): name for name in source_main}

matched = set(local_main_norm.keys()) & set(source_main_norm.keys())
local_only = set(local_main_norm.keys()) - set(source_main_norm.keys())
source_only = set(source_main_norm.keys()) - set(local_main_norm.keys())

print(f"\nMatched: {len(matched)}/{len(source_main_norm)}")
if local_only:
    print(f"\nExtra in local (NOT on source - should not be here):")
    for norm in sorted(local_only):
        print(f"  - {local_main_norm[norm]}")
if source_only:
    print(f"\nMissing from local (ON source but not local):")
    for norm in sorted(source_only):
        print(f"  - {source_main_norm[norm]}")

# Check for "Fuel Storage and Handling" specifically
fuel_norm = normalize("Fuel Storage and Handling")
print(f"\n{'='*70}")
print("SPECIFIC CHECK: 'Fuel Storage and Handling'")
print(f"{'='*70}")
print(f"  On source: {fuel_norm in source_main_norm}")
print(f"  In local DB: {fuel_norm in local_main_norm}")

if fuel_norm in local_main_norm:
    # Find products with this category
    conn = mysql.connector.connect(**DB_CFG)
    cur = conn.cursor(dictionary=True)
    
    cur.execute(f"""
        SELECT COUNT(DISTINCT p.ID) AS cnt
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id
            AND pm.meta_key = '_sku' AND pm.meta_value != ''
        LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id 
            AND pm_var.meta_key = 'attribute_pa_variant'
        INNER JOIN {PREFIX}sku_vin_mapping svm
            ON pm.meta_value = svm.sku AND svm.vin = %s
            AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
        INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
        INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            AND tt.taxonomy = 'product_cat'
        INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
            AND t.name LIKE %s
        WHERE p.post_type IN ('product','product_variation')
          AND p.post_status = 'publish'
    """, (VIN, '%Fuel Storage%'))
    
    result = cur.fetchone()
    print(f"  Products with this category: {result['cnt']}")
    
    # Sample some SKUs
    cur.execute(f"""
        SELECT DISTINCT pm.meta_value AS sku, p.ID
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id
            AND pm.meta_key = '_sku' AND pm.meta_value != ''
        LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id 
            AND pm_var.meta_key = 'attribute_pa_variant'
        INNER JOIN {PREFIX}sku_vin_mapping svm
            ON pm.meta_value = svm.sku AND svm.vin = %s
            AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
        INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
        INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            AND tt.taxonomy = 'product_cat'
        INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
            AND t.name LIKE %s
        WHERE p.post_type IN ('product','product_variation')
          AND p.post_status = 'publish'
        LIMIT 10
    """, (VIN, '%Fuel Storage%'))
    
    samples = cur.fetchall()
    if samples:
        print(f"  Sample SKUs with 'Fuel Storage' category:")
        for s in samples:
            print(f"    - {s['sku']} (ID: {s['ID']})")
    
    cur.close()
    conn.close()

print("\nDone.")
