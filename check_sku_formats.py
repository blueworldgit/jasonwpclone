#!/usr/bin/env python3
"""
Diagnostic: Compare SKU formats across wp_sku_vin_mapping, _sku postmeta, and original_sku postmeta
"""
import mysql.connector

DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print("=" * 80)
print("SKU FORMAT DIAGNOSTIC")
print("=" * 80)

# 1. wp_sku_vin_mapping
print("\n=== 1. wp_sku_vin_mapping table ===")
cur.execute(f"SELECT DISTINCT sku FROM {PREFIX}sku_vin_mapping ORDER BY sku LIMIT 20")
rows = cur.fetchall()
print("Sample SKUs:")
for r in rows:
    print(f"  {r['sku']}")
cur.execute(f"SELECT COUNT(*) as c FROM {PREFIX}sku_vin_mapping")
total = cur.fetchone()['c']
cur.execute(f"SELECT COUNT(DISTINCT sku) as c FROM {PREFIX}sku_vin_mapping")
distinct = cur.fetchone()['c']
print(f"Total rows: {total}")
print(f"Distinct SKUs: {distinct}")

# Check suffix pattern
cur.execute(f"SELECT COUNT(DISTINCT sku) as c FROM {PREFIX}sku_vin_mapping WHERE sku REGEXP '-[A-Fa-f0-9]{{5,6}}$'")
with_suffix = cur.fetchone()['c']
cur.execute(f"SELECT COUNT(DISTINCT sku) as c FROM {PREFIX}sku_vin_mapping WHERE sku NOT REGEXP '-[A-Fa-f0-9]{{5,6}}$'")
without_suffix = cur.fetchone()['c']
print(f"With hash suffix (like B00004111-BF9845): {with_suffix}")
print(f"Without hash suffix (like B00004111): {without_suffix}")

# 2. WooCommerce _sku
print("\n=== 2. WooCommerce _sku postmeta ===")
cur.execute(f"SELECT DISTINCT meta_value FROM {PREFIX}postmeta WHERE meta_key = '_sku' AND meta_value != '' ORDER BY meta_value LIMIT 20")
rows = cur.fetchall()
print("Sample _sku values:")
for r in rows:
    print(f"  {r['meta_value']}")
cur.execute(f"SELECT COUNT(DISTINCT meta_value) as c FROM {PREFIX}postmeta WHERE meta_key = '_sku' AND meta_value != ''")
print(f"Distinct _sku values: {cur.fetchone()['c']}")

cur.execute(f"SELECT COUNT(DISTINCT meta_value) as c FROM {PREFIX}postmeta WHERE meta_key = '_sku' AND meta_value REGEXP '-[A-Fa-f0-9]{{5,6}}$'")
print(f"  With hash suffix: {cur.fetchone()['c']}")
cur.execute(f"SELECT COUNT(DISTINCT meta_value) as c FROM {PREFIX}postmeta WHERE meta_key = '_sku' AND meta_value != '' AND meta_value NOT REGEXP '-[A-Fa-f0-9]{{5,6}}$'")
print(f"  Without hash suffix: {cur.fetchone()['c']}")

# 3. original_sku
print("\n=== 3. original_sku postmeta ===")
cur.execute(f"SELECT DISTINCT meta_value FROM {PREFIX}postmeta WHERE meta_key = 'original_sku' AND meta_value != '' ORDER BY meta_value LIMIT 20")
rows = cur.fetchall()
print("Sample original_sku values:")
for r in rows:
    print(f"  {r['meta_value']}")
cur.execute(f"SELECT COUNT(DISTINCT meta_value) as c FROM {PREFIX}postmeta WHERE meta_key = 'original_sku' AND meta_value != ''")
orig_count = cur.fetchone()['c']
print(f"Distinct original_sku values: {orig_count}")

# 4. MATCH TEST: _sku vs wp_sku_vin_mapping
print("\n=== 4. MATCH TEST: _sku JOIN wp_sku_vin_mapping ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT pm.meta_value) as c
    FROM {PREFIX}postmeta pm
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE pm.meta_key = '_sku'
""")
match_sku = cur.fetchone()['c']
print(f"Products matched via _sku: {match_sku}")

# 5. MATCH TEST: original_sku vs wp_sku_vin_mapping
print("\n=== 5. MATCH TEST: original_sku JOIN wp_sku_vin_mapping ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT pm.meta_value) as c
    FROM {PREFIX}postmeta pm
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE pm.meta_key = 'original_sku'
""")
match_orig = cur.fetchone()['c']
print(f"Products matched via original_sku: {match_orig}")

# 6. UNMATCHED counts
print("\n=== 6. UNMATCHED via _sku ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT pm.meta_value) as c
    FROM {PREFIX}postmeta pm
    INNER JOIN {PREFIX}posts p ON pm.post_id = p.ID AND p.post_type IN ('product', 'product_variation')
    LEFT JOIN {PREFIX}sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE pm.meta_key = '_sku' AND svm.sku IS NULL AND pm.meta_value != ''
""")
print(f"Unmatched via _sku: {cur.fetchone()['c']}")

print("\n=== 7. UNMATCHED via original_sku ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT pm.meta_value) as c
    FROM {PREFIX}postmeta pm
    INNER JOIN {PREFIX}posts p ON pm.post_id = p.ID AND p.post_type IN ('product', 'product_variation')
    LEFT JOIN {PREFIX}sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE pm.meta_key = 'original_sku' AND svm.sku IS NULL AND pm.meta_value != ''
""")
print(f"Unmatched via original_sku: {cur.fetchone()['c']}")

# 8. Side-by-side comparison
print("\n=== 8. Side-by-side comparison (first 15 products) ===")
cur.execute(f"""
    SELECT p.ID,
           pm_sku.meta_value as wc_sku,
           pm_orig.meta_value as original_sku
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_orig ON p.ID = pm_orig.post_id AND pm_orig.meta_key = 'original_sku'
    WHERE p.post_type IN ('product', 'product_variation') AND p.post_status = 'publish'
    AND pm_sku.meta_value != ''
    LIMIT 15
""")
rows = cur.fetchall()
print(f"{'Post ID':<10} {'WC _sku':<30} {'original_sku':<20}")
print(f"{'-'*10} {'-'*30} {'-'*20}")
for r in rows:
    print(f"{r['ID']:<10} {r['wc_sku']:<30} {r['original_sku'] or '(none)':<20}")

# 9. Check what the current queries actually return
print("\n=== 9. Current template query simulation (vehicle-subcategory) ===")
# Pick a real VIN from mapping table 
cur.execute(f"SELECT DISTINCT vin FROM {PREFIX}sku_vin_mapping LIMIT 3")
vins = [r['vin'] for r in cur.fetchall()]
for vin in vins:
    # Current query: joins _sku to svm.sku
    cur.execute(f"""
        SELECT COUNT(DISTINCT p.ID) as c
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
        WHERE p.post_type IN ('product', 'product_variation')
        AND svm.vin = %s
    """, (vin,))
    current = cur.fetchone()['c']
    
    # Proposed query: joins original_sku to svm.sku
    cur.execute(f"""
        SELECT COUNT(DISTINCT p.ID) as c
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = 'original_sku'
        INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
        WHERE p.post_type IN ('product', 'product_variation')
        AND svm.vin = %s
    """, (vin,))
    proposed = cur.fetchone()['c']
    
    print(f"VIN {vin}: current (_sku join) = {current} products, proposed (original_sku join) = {proposed} products")

print("\n" + "=" * 80)
print("SUMMARY")
print("=" * 80)

cur.close()
conn.close()
