#!/usr/bin/env python3
"""
Verify if same SKU has multiple variant_attributes (which would cause incorrect matches)
"""
import mysql.connector

DB_CFG = dict(host='localhost', user='root', password='', database='maxussql')
PREFIX = 'wp_'
conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print("=" * 80)
print("CRITICAL TEST: Do SKUs have multiple variant_attributes?")
print("=" * 80)

# Find SKUs with multiple variant attributes
print("\n=== 1. SKUs with different variant_attribute values ===")
cur.execute(f"""
    SELECT 
        sku,
        COUNT(DISTINCT variant_attribute) as variant_count,
        GROUP_CONCAT(DISTINCT variant_attribute ORDER BY variant_attribute SEPARATOR ' | ') as variants,
        GROUP_CONCAT(DISTINCT vin ORDER BY vin SEPARATOR ', ') as vins
    FROM {PREFIX}sku_vin_mapping
    WHERE variant_attribute IS NOT NULL AND variant_attribute != ''
    GROUP BY sku
    HAVING variant_count > 1
    ORDER BY variant_count DESC
    LIMIT 15
""")
multi_variant = cur.fetchall()

if multi_variant:
    print(f"Found {len(multi_variant)} SKUs with multiple variant_attribute values:")
    for row in multi_variant:
        print(f"\nSKU: {row['sku']} ({row['variant_count']} different variants)")
        print(f"  Variants: {row['variants'][:120]}")
        print(f"  VINs: {row['vins'][:120]}")
else:
    print("No SKUs found with multiple variant_attribute values")
    print("(This would mean variant_attribute is NOT needed for matching)")

# Test case: For a SKU with multiple variants, check if product has one specific variant
if multi_variant:
    test_sku = multi_variant[0]['sku']
    print(f"\n=== 2. TEST CASE: SKU {test_sku} ===")
    
    # Get all mapping rows for this SKU
    cur.execute(f"""
        SELECT sku, vin, variant_attribute
        FROM {PREFIX}sku_vin_mapping
        WHERE sku = %s
        ORDER BY vin, variant_attribute
    """, (test_sku,))
    mapping_rows = cur.fetchall()
    print(f"\nMapping table has {len(mapping_rows)} rows for this SKU:")
    for mr in mapping_rows:
        print(f"  {test_sku} + VIN={mr['vin']} + variant={mr['variant_attribute']}")
    
    # Check if this SKU exists as a product
    cur.execute(f"""
        SELECT 
            p.ID,
            p.post_type,
            pm_sku.meta_value as sku,
            pm_var.meta_value as product_variant
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
        WHERE pm_sku.meta_value = %s
        AND p.post_type IN ('product', 'product_variation')
        LIMIT 5
    """, (test_sku,))
    products = cur.fetchall()
    
    if products:
        print(f"\nProducts with this SKU:")
        for prod in products:
            print(f"  Product ID {prod['ID']} ({prod['post_type']})")
            print(f"    SKU: {prod['sku']}")
            print(f"    product variant: {prod['product_variant'] or '(none)'}")
        
        # Show the problem
        print(f"\n>>> PROBLEM DEMONSTRATION:")
        print(f"    Current query: JOIN ... ON svm.sku = '{test_sku}'")
        print(f"    Would match ALL {len(mapping_rows)} mapping rows")
        print(f"    But product has specific variant: '{products[0]['product_variant']}'")
        print(f"    CORRECT: Should match only 1 or few rows with matching variant")
    else:
        print(f"\nNo products found with SKU {test_sku}")

# Count total impact
print("\n=== 3. IMPACT ANALYSIS ===")
cur.execute(f"""
    SELECT COUNT(*) as total_rows
    FROM {PREFIX}sku_vin_mapping
    WHERE variant_attribute IS NOT NULL AND variant_attribute != ''
""")
total_with_variant = cur.fetchone()['total_rows']
print(f"Mapping rows with variant_attribute: {total_with_variant}")

cur.execute(f"""
    SELECT COUNT(DISTINCT sku) as unique_skus
    FROM {PREFIX}sku_vin_mapping
    WHERE variant_attribute IS NOT NULL AND variant_attribute != ''
""")
unique_skus = cur.fetchone()['unique_skus']
print(f"Unique SKUs with variant_attribute: {unique_skus}")

cur.execute(f"""
    SELECT COUNT(*) as count
    FROM (
        SELECT sku
        FROM {PREFIX}sku_vin_mapping
        WHERE variant_attribute IS NOT NULL AND variant_attribute != ''
        GROUP BY sku
        HAVING COUNT(DISTINCT variant_attribute) > 1
    ) as multi
""")
skus_with_multi = cur.fetchone()['count']
print(f"SKUs with multiple variant values: {skus_with_multi}")
print(f"Percentage needing variant join: {skus_with_multi/unique_skus*100:.1f}%")

# Test actual query impact
print("\n=== 4. QUERY COMPARISON (E Deliver 9 VIN) ===")
test_vin = 'LSFAM120XNA160733'

cur.execute(f"""
    SELECT COUNT(DISTINCT p.ID) as count
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE p.post_type IN ('product', 'product_variation')
    AND svm.vin = %s
""", (test_vin,))
count_without_variant = cur.fetchone()['count']
print(f"Current query (SKU only): {count_without_variant} products")

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
count_with_variant = cur.fetchone()['count']
print(f"Proposed query (SKU + variant): {count_with_variant} products")
print(f"Difference: {count_without_variant - count_with_variant} products would be excluded")

print("\n" + "=" * 80)

cur.close()
conn.close()
