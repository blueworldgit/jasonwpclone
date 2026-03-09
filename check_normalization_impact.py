#!/usr/bin/env python3
"""
Check if incomplete normalization is causing unwanted exclusions
"""
import mysql.connector

DB_CONFIG = {
    'host': 'localhost',
    'port': 3306,
    'user': 'root',
    'password': '',
    'database': 'maxussql',
    'charset': 'utf8mb4'
}

conn = mysql.connector.connect(**DB_CONFIG)
cur = conn.cursor(dictionary=True)

print("=" * 80)
print("NORMALIZATION IMPACT ANALYSIS")
print("=" * 80)
print()

# 1. Check products with un-normalized variant attributes
print("1. Products with un-normalized variant attributes:")
print("-" * 80)

cur.execute("""
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN meta_value LIKE '%-%' THEN 1 END) as hyphenated,
        COUNT(CASE WHEN meta_value LIKE 'left-%' THEN 1 END) as left_pattern,
        COUNT(CASE WHEN meta_value LIKE 'right-%' THEN 1 END) as right_pattern,
        COUNT(CASE WHEN meta_value = 'Left' THEN 1 END) as normalized_left,
        COUNT(CASE WHEN meta_value = 'Right' THEN 1 END) as normalized_right
    FROM wp_postmeta
    WHERE meta_key = 'attribute_pa_variant'
""")
stats = cur.fetchone()

print(f"Total variant attributes: {stats['total']}")
print(f"  Hyphenated (left-*, right-*): {stats['hyphenated']}")
print(f"    left-* pattern: {stats['left_pattern']}")
print(f"    right-* pattern: {stats['right_pattern']}")
print(f"  Normalized 'Left': {stats['normalized_left']}")
print(f"  Normalized 'Right': {stats['normalized_right']}")
print()

# 2. Check if un-normalized products are being excluded from T90 EV
print("2. Are un-normalized products excluded from T90 EV?")
print("-" * 80)

# Products with hyphenated variants that have T90 EV in mapping
cur.execute("""
    SELECT 
        p.ID, 
        p.post_title,
        pm_sku.meta_value as sku,
        pm_var.meta_value as product_variant,
        svm.variant_attribute as mapping_variant,
        CASE 
            WHEN pm_var.meta_value = svm.variant_attribute THEN 'MATCH'
            WHEN svm.variant_attribute IS NULL OR svm.variant_attribute = '' THEN 'NULL_MATCH'
            ELSE 'NO_MATCH'
        END as match_status
    FROM wp_posts p
    INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    INNER JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN wp_sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE svm.vin = 'LSFAM120XNA160733'
        AND pm_var.meta_value LIKE '%-%'
        AND p.post_type IN ('product', 'product_variation')
        AND p.post_status = 'publish'
    LIMIT 10
""")

hyphenated_products = cur.fetchall()

if hyphenated_products:
    print(f"Found {len(hyphenated_products)} products with hyphenated variants for T90 EV:")
    print()
    for prod in hyphenated_products:
        print(f"  SKU: {prod['sku']}")
        print(f"  Title: {prod['post_title']}")
        print(f"  Product variant: '{prod['product_variant']}'")
        print(f"  Mapping variant: '{prod['mapping_variant']}'")
        print(f"  Status: {prod['match_status']}")
        print()
else:
    print("No products with hyphenated variants found for T90 EV")
    print()

# 3. Count how many products are affected
print("3. Exclusion counts:")
print("-" * 80)

# Count products excluded due to variant mismatch
cur.execute("""
    SELECT COUNT(DISTINCT p.ID) as count
    FROM wp_posts p
    INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN wp_sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE svm.vin = 'LSFAM120XNA160733'
        AND p.post_type IN ('product', 'product_variation')
        AND p.post_status = 'publish'
        AND pm_var.meta_value IS NOT NULL
        AND pm_var.meta_value != ''
        AND pm_var.meta_value LIKE '%-%'
        AND svm.variant_attribute IS NOT NULL
        AND svm.variant_attribute != ''
        AND pm_var.meta_value != svm.variant_attribute
""")
excluded_hyphenated = cur.fetchone()['count']

print(f"Products with hyphenated variants excluded: {excluded_hyphenated}")

# 4. Check what variants are in mapping table for T90 EV
print()
print("4. Variant values in mapping table for T90 EV:")
print("-" * 80)

cur.execute("""
    SELECT 
        variant_attribute,
        COUNT(*) as count,
        COUNT(DISTINCT sku) as distinct_skus
    FROM wp_sku_vin_mapping
    WHERE vin = 'LSFAM120XNA160733'
        AND variant_attribute IS NOT NULL
        AND variant_attribute != ''
    GROUP BY variant_attribute
    ORDER BY count DESC
""")

mapping_variants = cur.fetchall()
if mapping_variants:
    for var in mapping_variants:
        print(f"  '{var['variant_attribute']}': {var['count']} mappings, {var['distinct_skus']} SKUs")
else:
    print("  No variants in mapping table")

print()

# 5. Show example of potential mismatch
print("5. Example of potential mismatches:")
print("-" * 80)

cur.execute("""
    SELECT 
        pm_sku.meta_value as sku,
        pm_var.meta_value as product_variant
    FROM wp_postmeta pm_sku
    INNER JOIN wp_postmeta pm_var ON pm_sku.post_id = pm_var.post_id
    WHERE pm_sku.meta_key = '_sku'
        AND pm_var.meta_key = 'attribute_pa_variant'
        AND pm_var.meta_value LIKE 'left-%'
    LIMIT 5
""")

examples = cur.fetchall()
for ex in examples:
    # Check what's in mapping
    cur.execute("""
        SELECT DISTINCT variant_attribute
        FROM wp_sku_vin_mapping
        WHERE sku = %s 
            AND vin = 'LSFAM120XNA160733'
            AND variant_attribute IS NOT NULL
            AND variant_attribute != ''
    """, (ex['sku'],))
    mapping = cur.fetchone()
    
    if mapping:
        print(f"  SKU {ex['sku']}:")
        print(f"    Product has: '{ex['product_variant']}'")
        print(f"    Mapping has: '{mapping['variant_attribute']}'")
        print(f"    Match: {'❌ NO' if ex['product_variant'] != mapping['variant_attribute'] else '✓ YES'}")
        print()

conn.close()

print("=" * 80)
print("CONCLUSION")
print("=" * 80)
print()
if hyphenated_products or excluded_hyphenated > 0:
    print("⚠️  INCOMPLETE NORMALIZATION IS CAUSING EXCLUSIONS")
    print()
    print("Products with 'left-SKU' format won't match mapping table's 'Left' format")
    print("This means valid products are being incorrectly excluded from T90 EV")
    print()
    print("RECOMMENDATION: Complete the normalization to avoid data loss")
else:
    print("✓ No evidence of exclusions due to incomplete normalization")
    print()
    print("The 155 hyphenated variants may be for other vehicles or unused products")
