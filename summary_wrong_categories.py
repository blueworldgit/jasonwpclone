#!/usr/bin/env python3
"""
Quick summary of products with wrong categories (WITH variant joins)
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
print("PRODUCTS WITH WRONG CATEGORIES (WITH VARIANT JOINS)")
print("=" * 80)
print()

wrong_cats = [
    'Air Intake System',
    'Emission Exhaust System',
    'Fuel Storage & Handling',
    'Power Energy Storage & Link Wire',
    'Power Generation'
]

placeholders = ','.join(['%s'] * len(wrong_cats))

cur.execute(f"""
    SELECT DISTINCT 
        p.ID as product_id,
        p.post_title,
        pm_sku.meta_value as sku,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as wrong_categories
    FROM wp_posts p
    INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id 
        AND pm_sku.meta_key = '_sku'
    LEFT JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id 
        AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN wp_sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
        AND svm.vin = %s
    INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id 
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        AND tt.taxonomy = 'product_cat'
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE p.post_type IN ('product', 'product_variation')
      AND p.post_status = 'publish'
      AND t.name IN ({placeholders})
    GROUP BY p.ID
    ORDER BY p.post_title
""", ('LSFAM120XNA160733',) + tuple(wrong_cats))

products = cur.fetchall()

print(f"Total: {len(products)} products\n")

for i, prod in enumerate(products, 1):
    print(f"{i}. {prod['post_title']}")
    print(f"   SKU: {prod['sku']}")
    print(f"   Wrong Categories: {prod['wrong_categories']}")
    print()

conn.close()

print("=" * 80)
print(f"Ready to apply fuzzy matching fix to these {len(products)} products")
print("=" * 80)
