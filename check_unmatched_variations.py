#!/usr/bin/env python3
"""
Check unmatched variation SKUs - what format are they in?
"""
import mysql.connector

DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print("=" * 80)
print("UNMATCHED VARIATIONS ANALYSIS")
print("=" * 80)

# Get unmatched variations
print("\n=== Unmatched variations (not in wp_sku_vin_mapping) ===")
cur.execute(f"""
    SELECT 
        p.ID,
        p.post_parent,
        pm_sku.meta_value as sku,
        p.post_title
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE p.post_type = 'product_variation'
    AND p.post_status = 'publish'
    AND svm.sku IS NULL
    AND pm_sku.meta_value != ''
    ORDER BY pm_sku.meta_value
    LIMIT 30
""")
rows = cur.fetchall()
print(f"Sample of {len(rows)} unmatched variation SKUs:")
for r in rows:
    parent_info = f"(parent:{r['post_parent']})" if r['post_parent'] else "(no parent)"
    print(f"  {r['sku']:<20} {parent_info}")

# Check if SKUs have ANY special format
print("\n=== SKU format analysis (all SKUs with dashes) ===")
cur.execute(f"""
    SELECT pm_sku.meta_value as sku
    FROM {PREFIX}postmeta pm_sku
    WHERE pm_sku.meta_key = '_sku'
    AND pm_sku.meta_value LIKE '%-%'
    LIMIT 50
""")
rows = cur.fetchall()
if rows:
    print(f"Found {len(rows)} SKUs containing dashes:")
    for r in rows:
        print(f"  {r['sku']}")
else:
    print("NO SKUs found with dashes in them")

# Check specific patterns
print("\n=== Pattern matching tests ===")
patterns = [
    ('-[A-Fa-f0-9]{6}$', 'Ends with -XXXXXX (6 hex chars)'),
    ('-[A-Fa-f0-9]{5}$', 'Ends with -XXXXX (5 hex chars)'),
    ('-[0-9]+$', 'Ends with -numbers'),
    ('[A-Z][0-9]{8}-', 'Starts like B00004111- (hashed format)'),
]

for pattern, desc in patterns:
    cur.execute(f"""
        SELECT COUNT(*) as c
        FROM {PREFIX}postmeta pm_sku
        WHERE pm_sku.meta_key = '_sku'
        AND pm_sku.meta_value REGEXP %s
    """, (pattern,))
    count = cur.fetchone()['c']
    print(f"  {desc}: {count} matches")

# Check for the existence of parent products for these variations
print("\n=== Do unmatched variations have parent products? ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT p.ID) as var_count,
           COUNT(DISTINCT parent.ID) as parent_count
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    LEFT JOIN {PREFIX}posts parent ON p.post_parent = parent.ID AND parent.post_type = 'product'
    WHERE p.post_type = 'product_variation'
    AND svm.sku IS NULL
    AND pm_sku.meta_value != ''
""")
r = cur.fetchone()
print(f"Unmatched variations: {r['var_count']}")
print(f"With valid parent products: {r['parent_count']}")

# Get parent SKU for a few examples
print("\n=== Example: Variation vs Parent SKU ===")
cur.execute(f"""
    SELECT 
        p.ID as var_id,
        pm_sku.meta_value as var_sku,
        parent.ID as parent_id,
        pm_parent_sku.meta_value as parent_sku
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    INNER JOIN {PREFIX}posts parent ON p.post_parent = parent.ID AND parent.post_type = 'product'
    LEFT JOIN {PREFIX}postmeta pm_parent_sku ON parent.ID = pm_parent_sku.post_id AND pm_parent_sku.meta_key = '_sku'
    WHERE p.post_type = 'product_variation'
    AND svm.sku IS NULL
    AND pm_sku.meta_value != ''
    LIMIT 10
""")
rows = cur.fetchall()
if rows:
    print("Variation SKU                 Parent SKU")
    print("-" * 60)
    for r in rows:
        print(f"{r['var_sku']:<30} {r['parent_sku'] or '(none)'}")
else:
    print("No examples found")

# Check if parent SKUs are in the mapping
print("\n=== Are PARENT SKUs in the mapping table? ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT parent.ID) as total,
           COUNT(DISTINCT CASE WHEN svm.sku IS NOT NULL THEN parent.ID END) as in_mapping
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}posts parent ON p.post_parent = parent.ID AND parent.post_type = 'product'
    LEFT JOIN {PREFIX}postmeta pm_parent_sku ON parent.ID = pm_parent_sku.post_id AND pm_parent_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}sku_vin_mapping svm ON pm_parent_sku.meta_value = svm.sku
    WHERE p.post_type = 'product_variation'
""")
r = cur.fetchone()
print(f"Total parent products: {r['total']}")
print(f"Parents in VIN mapping: {r['in_mapping']}")
print(f"Parents NOT in mapping: {r['total'] - r['in_mapping']}")

print("\n" + "=" * 80)

cur.close()
conn.close()
