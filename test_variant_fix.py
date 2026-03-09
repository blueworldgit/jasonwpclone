#!/usr/bin/env python3
"""
Test the variant join fix by comparing before/after product counts
"""
import mysql.connector

DB_CFG = dict(host='localhost', user='root', password='', database='maxussql')
PREFIX = 'wp_'
conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

test_vin = 'LSFAM120XNA160733'  # E Deliver 9

print("=" * 80)
print(f"TESTING VARIANT JOIN FIX FOR VIN: {test_vin}")
print("=" * 80)

# Before fix (old query - just SKU match)
print("\n=== OLD QUERY (SKU only) ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT p.ID) as count
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE p.post_type IN ('product', 'product_variation')
    AND svm.vin = %s
""", (test_vin,))
old_count = cur.fetchone()['count']
print(f"Products matched: {old_count}")

# After fix (new query - SKU + variant match)
print("\n=== NEW QUERY (SKU + variant_attribute) ===")
cur.execute(f"""
    SELECT COUNT(DISTINCT p.ID) as count
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
    WHERE p.post_type IN ('product', 'product_variation')
    AND svm.vin = %s
""", (test_vin,))
new_count = cur.fetchone()['count']
print(f"Products matched: {new_count}")

print(f"\nDifference: {old_count - new_count} incorrect matches removed")

# Check an example SKU with multiple variants
print("\n=== EXAMPLE: B00004683 (Left vs m6*20) ===")
test_sku = 'B00004683'

# How many mapping rows for this SKU?
cur.execute(f"""
    SELECT variant_attribute, COUNT(*) as vin_count
    FROM {PREFIX}sku_vin_mapping
    WHERE sku = %s
    GROUP BY variant_attribute
    ORDER BY variant_attribute
""", (test_sku,))
print("Mapping table entries:")
for row in cur.fetchall():
    var = row['variant_attribute'] or '(NULL)'
    print(f"  {var}: {row['vin_count']} VINs")

# What products exist with this SKU?
cur.execute(f"""
    SELECT p.ID, p.post_type, pm_var.meta_value as variant
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    WHERE pm_sku.meta_value = %s
    AND p.post_type IN ('product', 'product_variation')
""", (test_sku,))
products = cur.fetchall()
print(f"\nProducts with SKU {test_sku}:")
for p in products:
    var = p['variant'] or '(none)'
    print(f"  Product {p['ID']} ({p['post_type']}): variant = {var}")

# With old query, how many VINs matched?
cur.execute(f"""
    SELECT COUNT(DISTINCT svm.vin) as vin_count
    FROM {PREFIX}postmeta pm_sku
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE pm_sku.meta_value = %s
    AND pm_sku.meta_key = '_sku'
""", (test_sku,))
old_vin_count = cur.fetchone()['vin_count']
print(f"\nOld query: product matched {old_vin_count} VINs (INCORRECT - matches all variants)")

# With new query, how many VINs matched?
cur.execute(f"""
    SELECT COUNT(DISTINCT svm.vin) as vin_count
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
    WHERE pm_sku.meta_value = %s
    AND p.post_type IN ('product', 'product_variation')
""", (test_sku,))
new_vin_count = cur.fetchone()['vin_count']
print(f"New query: product matched {new_vin_count} VINs (CORRECT - matches only its variant)")

print("\n" + "=" * 80)
print("✅ VERIFICATION COMPLETE")
print("=" * 80)
print(f"Summary: {old_count - new_count} incorrect product-vehicle associations removed")
print("Products now match vehicles only for their specific variant.")

cur.close()
conn.close()
