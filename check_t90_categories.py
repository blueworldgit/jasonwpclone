#!/usr/bin/env python3
"""
Check all T90 EV product categories
"""
import mysql.connector
from collections import Counter

DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"
T90_EV_VIN = "LSFAM120XNA160733"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print("=" * 80)
print("T90 EV PRODUCT CATEGORIES")
print("=" * 80)
print()

# Get all T90 EV products with their categories
cur.execute(f"""
    SELECT DISTINCT 
        p.ID,
        p.post_title,
        p.post_type,
        pm_sku.meta_value as sku,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as categories
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id 
        AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id 
        AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
        AND svm.vin = %s
    INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
    INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        AND tt.taxonomy = 'product_cat'
    INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
    WHERE p.post_type IN ('product', 'product_variation')
      AND p.post_status = 'publish'
    GROUP BY p.ID
    ORDER BY p.post_title
    LIMIT 50
""", (T90_EV_VIN,))

products = cur.fetchall()

print(f"Showing first {len(products)} products:\n")

for p in products:
    print(f"{p['sku']}: {p['post_title']}")
    print(f"  Categories: {p['categories']}")
    print()

# Get category distribution
cur.execute(f"""
    SELECT 
        t.name as category_name,
        COUNT(DISTINCT p.ID) as product_count
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id 
        AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id 
        AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
        AND svm.vin = %s
    INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
    INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        AND tt.taxonomy = 'product_cat'
    INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
    WHERE p.post_type IN ('product', 'product_variation')
      AND p.post_status = 'publish'
      AND t.name NOT IN ('imageupdated', 'priceupdated', %s)
    GROUP BY t.name
    ORDER BY product_count DESC
""", (T90_EV_VIN, T90_EV_VIN))

categories = cur.fetchall()

print("=" * 80)
print("CATEGORY DISTRIBUTION")
print("=" * 80)
print()

for cat in categories:
    print(f"{cat['category_name']}: {cat['product_count']} products")

print()
print(f"Total unique categories: {len(categories)}")

# Check for wrong categories
wrong_cats = [
    'Air Intake System',
    'Emission Exhaust System',
    'Fuel Storage & Handling',
    'Power Energy Storage & Link Wire',
    'Power Generation'
]

print()
print("=" * 80)
print("CHECKING FOR WRONG CATEGORIES")
print("=" * 80)
print()

for cat_name in wrong_cats:
    count = sum(1 for c in categories if c['category_name'] == cat_name)
    if count > 0:
        print(f"[WARNING] {cat_name}: Found in results")
    else:
        print(f"[OK] {cat_name}: Not found (correct)")

conn.close()
