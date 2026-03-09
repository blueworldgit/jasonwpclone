#!/usr/bin/env python3
"""
Check SKU patterns specifically for variable products and variations
"""
import mysql.connector

DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print("=" * 80)
print("VARIABLE PRODUCTS & VARIATIONS SKU DIAGNOSTIC")
print("=" * 80)

# 1. Count by post type
print("\n=== 1. Product counts by type ===")
cur.execute(f"""
    SELECT post_type, COUNT(*) as c 
    FROM {PREFIX}posts 
    WHERE post_type IN ('product', 'product_variation') 
    AND post_status = 'publish'
    GROUP BY post_type
""")
for row in cur.fetchall():
    print(f"{row['post_type']}: {row['c']}")

# 2. SKU patterns for simple/variable products
print("\n=== 2. Simple/Variable Products (_sku patterns) ===")
cur.execute(f"""
    SELECT pm_sku.meta_value as sku
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    WHERE p.post_type = 'product'
    AND p.post_status = 'publish'
    AND pm_sku.meta_value != ''
    ORDER BY pm_sku.meta_value
    LIMIT 30
""")
rows = cur.fetchall()
print("Sample product SKUs:")
for r in rows[:15]:
    print(f"  {r['sku']}")

cur.execute(f"""
    SELECT COUNT(DISTINCT pm_sku.meta_value) as c
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    WHERE p.post_type = 'product'
    AND pm_sku.meta_value REGEXP '-[A-Fa-f0-9]{{5,6}}$'
""")
with_hash = cur.fetchone()['c']
print(f"\nProducts with hash suffix: {with_hash}")

# 3. SKU patterns for VARIATIONS
print("\n=== 3. Product Variations (_sku patterns) ===")
cur.execute(f"""
    SELECT pm_sku.meta_value as sku
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    WHERE p.post_type = 'product_variation'
    AND p.post_status = 'publish'
    AND pm_sku.meta_value != ''
    ORDER BY pm_sku.meta_value
    LIMIT 30
""")
rows = cur.fetchall()
print("Sample variation SKUs:")
for r in rows[:20]:
    print(f"  {r['sku']}")

cur.execute(f"""
    SELECT COUNT(DISTINCT pm_sku.meta_value) as c
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    WHERE p.post_type = 'product_variation'
    AND pm_sku.meta_value REGEXP '-[A-Fa-f0-9]{{5,6}}$'
""")
with_hash = cur.fetchone()['c']
cur.execute(f"""
    SELECT COUNT(DISTINCT pm_sku.meta_value) as c
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    WHERE p.post_type = 'product_variation'
    AND pm_sku.meta_value != ''
""")
total_var = cur.fetchone()['c']
print(f"\nTotal variation SKUs: {total_var}")
print(f"Variations with hash suffix: {with_hash}")
print(f"Variations without hash suffix: {total_var - with_hash}")

# 4. Look for pattern: same base SKU with different suffixes
print("\n=== 4. Base SKU reuse (same part in multiple contexts) ===")
cur.execute(f"""
    SELECT 
        SUBSTRING_INDEX(pm_sku.meta_value, '-', 1) as base_sku,
        COUNT(DISTINCT pm_sku.meta_value) as variant_count,
        GROUP_CONCAT(DISTINCT pm_sku.meta_value ORDER BY pm_sku.meta_value SEPARATOR ', ') as all_skus
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    WHERE p.post_type IN ('product', 'product_variation')
    AND pm_sku.meta_value LIKE '%-%'
    GROUP BY base_sku
    HAVING variant_count > 1
    ORDER BY variant_count DESC
    LIMIT 10
""")
rows = cur.fetchall()
if rows:
    print("Base SKUs with multiple hash suffixes (e.g., B00004111-XXXXX, B00004111-YYYYY):")
    for r in rows:
        skus = r['all_skus'][:100] + ('...' if len(r['all_skus']) > 100 else '')
        print(f"  {r['base_sku']}: {r['variant_count']} variants")
        print(f"    Examples: {skus}")
else:
    print("No base SKUs found with multiple hash suffixes")

# 5. Check if variations match mapping table
print("\n=== 5. Do variation SKUs match wp_sku_vin_mapping? ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT pm_sku.meta_value) as c
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE p.post_type = 'product_variation'
""")
matched = cur.fetchone()['c']
print(f"Variations matched to VIN mapping: {matched}")

cur.execute(f"""
    SELECT COUNT(DISTINCT pm_sku.meta_value) as c
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE p.post_type = 'product_variation'
    AND svm.sku IS NULL
    AND pm_sku.meta_value != ''
""")
unmatched = cur.fetchone()['c']
print(f"Variations NOT matched: {unmatched}")

# 6. Check for _sku_base or similar meta fields
print("\n=== 6. Check for base SKU meta fields ===")
cur.execute(f"""
    SELECT DISTINCT meta_key 
    FROM {PREFIX}postmeta 
    WHERE meta_key LIKE '%sku%' 
    OR meta_key LIKE '%original%'
    ORDER BY meta_key
""")
print("Meta keys containing 'sku' or 'original':")
for row in cur.fetchall():
    print(f"  {row['meta_key']}")

# 7. Example: Show a variable product with its variations
print("\n=== 7. Example variable product with variations ===")
cur.execute(f"""
    SELECT p.ID, p.post_type, pm_sku.meta_value as sku
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    WHERE p.post_type = 'product'
    AND p.post_status = 'publish'
    AND EXISTS (
        SELECT 1 FROM {PREFIX}posts child 
        WHERE child.post_parent = p.ID 
        AND child.post_type = 'product_variation'
    )
    LIMIT 1
""")
parent = cur.fetchone()
if parent:
    print(f"Parent variable product ID {parent['ID']}, SKU: {parent['sku']}")
    
    cur.execute(f"""
        SELECT p.ID, pm_sku.meta_value as sku, p.post_title
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        WHERE p.post_parent = %s
        AND p.post_type = 'product_variation'
        ORDER BY pm_sku.meta_value
    """, (parent['ID'],))
    variations = cur.fetchall()
    print(f"  Variations ({len(variations)}):")
    for v in variations[:10]:
        print(f"    ID {v['ID']}: {v['sku']}")
    if len(variations) > 10:
        print(f"    ... and {len(variations)-10} more")

print("\n" + "=" * 80)

cur.close()
conn.close()
