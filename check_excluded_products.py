#!/usr/bin/env python3
"""
Check WHAT would be excluded with variant join - simple products or variations?
"""
import mysql.connector

DB_CFG = dict(host='localhost', user='root', password='', database='maxussql')
PREFIX = 'wp_'
conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

test_vin = 'LSFAM120XNA160733'

print("=" * 80)
print(f"ANALYZING EXCLUDED PRODUCTS FOR VIN: {test_vin}")
print("=" * 80)

# Get products that would be EXCLUDED
print("\n=== Products matched by current query but NOT by variant join ===")
cur.execute(f"""
    SELECT DISTINCT p.ID, p.post_type, pm_sku.meta_value as sku, pm_var.meta_value as product_variant
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm_current ON pm_sku.meta_value = svm_current.sku AND svm_current.vin = %s
    WHERE p.post_type IN ('product', 'product_variation')
    AND NOT EXISTS (
        SELECT 1 FROM {PREFIX}sku_vin_mapping svm_new
        WHERE svm_new.sku = pm_sku.meta_value 
        AND svm_new.vin = %s
        AND (svm_new.variant_attribute IS NULL OR svm_new.variant_attribute = '' OR svm_new.variant_attribute = pm_var.meta_value)
    )
    LIMIT 30
""", (test_vin, test_vin))

excluded = cur.fetchall()
print(f"\nFound {len(excluded)} examples of excluded products:")
print(f"{'ID':<10} {'Type':<20} {'SKU':<15} {'Product Variant':<30}")
print("-" * 85)

for row in excluded:
    pv = (row['product_variant'] or '(none)')[:29]
    print(f"{row['ID']:<10} {row['post_type']:<20} {row['sku']:<15} {pv:<30}")
    
    # Show mapping rows for this SKU
    if excluded.index(row) < 5:  # Detail for first 5 only
        cur.execute(f"""
            SELECT variant_attribute 
            FROM {PREFIX}sku_vin_mapping 
            WHERE sku = %s AND vin = %s
        """, (row['sku'], test_vin))
        mapping_variants = cur.fetchall()
        print(f"    Mapping has variant_attribute: {', '.join([m['variant_attribute'] or '(NULL)' for m in mapping_variants])}")

# Count by type
print("\n=== Breakdown by post_type ===")
cur.execute(f"""
    SELECT p.post_type, COUNT(DISTINCT p.ID) as count
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm_current ON pm_sku.meta_value = svm_current.sku AND svm_current.vin = %s
    WHERE p.post_type IN ('product', 'product_variation')
    AND NOT EXISTS (
        SELECT 1 FROM {PREFIX}sku_vin_mapping svm_new
        WHERE svm_new.sku = pm_sku.meta_value 
        AND svm_new.vin = %s
        AND (svm_new.variant_attribute IS NULL OR svm_new.variant_attribute = '' OR svm_new.variant_attribute = pm_var.meta_value)
    )
    GROUP BY p.post_type
""", (test_vin, test_vin))

for row in cur.fetchall():
    print(f"  {row['post_type']}: {row['count']}")

# Check if these are variations with mismatched variants
print("\n=== Are these variations with wrong variant attributes? ===")
cur.execute(f"""
    SELECT 
        p.ID,
        pm_sku.meta_value as sku,
        pm_var.meta_value as product_variant,
        svm.variant_attribute as mapping_variant
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku AND svm.vin = %s
    WHERE p.post_type = 'product_variation'
    AND pm_var.meta_value IS NOT NULL 
    AND pm_var.meta_value != ''
    AND svm.variant_attribute IS NOT NULL
    AND svm.variant_attribute != ''
    AND svm.variant_attribute != pm_var.meta_value
    LIMIT 20
""", (test_vin,))

mismatched = cur.fetchall()
if mismatched:
    print(f"\nFound {len(mismatched)} variations with mismatched variants:")
    print(f"{'SKU':<15} {'Prod Variant':<25} {'Mapping Variant':<25}")
    print("-" * 70)
    for m in mismatched:
        print(f"{m['sku']:<15} {m['product_variant']:<25} {m['mapping_variant']:<25}")
else:
    print("No variations with mismatched variants found")

print("\n" + "=" * 80)

cur.close()
conn.close()
