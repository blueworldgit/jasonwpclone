#!/usr/bin/env python3
"""
Check current database state after variant fix
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
print("CHECKING CURRENT DATABASE STATE")
print("=" * 80)
print()

# 1. Check T90 EV product count WITH variant joins
print("1. T90 EV Product Count (WITH variant_attribute joins):")
print("-" * 80)

cur.execute("""
    SELECT COUNT(DISTINCT p.ID) as count
    FROM wp_posts p
    INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN wp_sku_vin_mapping svm 
        ON pm_sku.meta_value = svm.sku
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
    WHERE svm.vin = 'LSFAM120XNA160733'
        AND p.post_type IN ('product', 'product_variation')
        AND p.post_status = 'publish'
""")
with_variant = cur.fetchone()['count']
print(f"With variant joins: {with_variant} products")

# 2. Check WITHOUT variant joins (old way)
cur.execute("""
    SELECT COUNT(DISTINCT p.ID) as count
    FROM wp_posts p
    INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    INNER JOIN wp_sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    WHERE svm.vin = 'LSFAM120XNA160733'
        AND p.post_type IN ('product', 'product_variation')
        AND p.post_status = 'publish'
""")
without_variant = cur.fetchone()['count']
print(f"Without variant joins: {without_variant} products")
print(f"Difference: {without_variant - with_variant} products excluded by variant fix")
print()

# 3. Check if our 13 problem products still exist
print("2. Checking Our 13 Problem Products:")
print("-" * 80)

problem_skus = [
    'C00266185', 'B00004683', 'B00005351', 'B00004852', 'C00054625',
    'C00087976', 'B00003512', 'C00143826', 'C00053812', 'C00320368',
    'C00205188', 'B00005445', 'B90001243'
]

still_exist = 0
for sku in problem_skus:
    cur.execute("""
        SELECT p.ID, p.post_title, pm_sku.meta_value as sku,
               pm_var.meta_value as variant_attr
        FROM wp_posts p
        INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
        INNER JOIN wp_sku_vin_mapping svm 
            ON pm_sku.meta_value = svm.sku
            AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
        WHERE svm.vin = 'LSFAM120XNA160733'
            AND pm_sku.meta_value = %s
            AND p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
        LIMIT 1
    """, (sku,))
    result = cur.fetchone()
    if result:
        still_exist += 1

print(f"Still exist: {still_exist}/13 problem products")
print()

# 4. Check if normalization was applied
print("3. Checking Variant Attribute Normalization:")
print("-" * 80)

cur.execute("""
    SELECT COUNT(*) as count
    FROM wp_postmeta
    WHERE meta_key = 'attribute_pa_variant'
        AND meta_value LIKE '%-%'
""")
hyphenated = cur.fetchone()['count']

cur.execute("""
    SELECT COUNT(*) as count
    FROM wp_postmeta
    WHERE meta_key = 'attribute_pa_variant'
        AND (meta_value = 'Left' OR meta_value = 'Right')
""")
normalized = cur.fetchone()['count']

print(f"Hyphenated patterns (left-SKU, right-SKU): {hyphenated}")
print(f"Normalized (Left, Right): {normalized}")
print()

# 5. Check category state for T90 EV
print("4. Category State for T90 EV Products:")
print("-" * 80)

cur.execute("""
    SELECT t.name as category_name, COUNT(DISTINCT p.ID) as product_count
    FROM wp_posts p
    INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN wp_sku_vin_mapping svm 
        ON pm_sku.meta_value = svm.sku
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
    INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE svm.vin = 'LSFAM120XNA160733'
        AND p.post_type IN ('product', 'product_variation')
        AND p.post_status = 'publish'
        AND tt.taxonomy = 'product_cat'
        AND tt.parent != 0
    GROUP BY t.term_id, t.name
    ORDER BY t.name
""")

categories = cur.fetchall()
print(f"Total categories: {len(categories)}")
print()

wrong_cats = ['Air Intake System', 'Emission Exhaust System', 'Fuel Storage & Handling', 
              'Power Energy Storage & Link Wire', 'Power Generation']
              
print("Wrong categories (if still present):")
for cat in categories:
    if cat['category_name'] in wrong_cats:
        print(f"  ❌ {cat['category_name']}: {cat['product_count']} products")

conn.close()

print()
print("=" * 80)
print("SUMMARY")
print("=" * 80)
print()
print(f"✓ Variant joins are {'ACTIVE' if with_variant < without_variant else 'NOT WORKING'}")
print(f"✓ T90 EV now has {with_variant} products (was {without_variant} without variant fix)")
print(f"✓ Problem products: {still_exist}/13 still exist")
print(f"✓ Normalization: {'APPLIED' if hyphenated == 0 else 'NOT APPLIED'}")
print()
