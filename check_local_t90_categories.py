#!/usr/bin/env python3
"""Check T90 EV categories on LOCAL database (exact same query as remote check)."""
import mysql.connector

print("Checking T90 EV categories on LOCAL database...")
print("=" * 70)

db = mysql.connector.connect(host="localhost", user="root", password="", database="maxussql")
cur = db.cursor(dictionary=True)

# T90 EV VIN
VIN = 'LSFAM120XNA160733'

# Get products for T90 EV
cur.execute("""
    SELECT COUNT(DISTINCT p.ID) as product_count
    FROM wp_posts p
    INNER JOIN wp_posts v ON v.post_parent = p.ID AND v.post_type = 'product_variation'
    INNER JOIN wp_postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
    INNER JOIN wp_sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE p.post_type = 'product'
      AND svm.vin = %s
""", (VIN,))
result = cur.fetchone()
product_count = result['product_count']

print(f"Products for T90 EV: {product_count}")

# Get main categories (parent_id = 0) for T90 EV products
cur.execute("""
    SELECT DISTINCT t.term_id, t.name, t.slug
    FROM wp_terms t
    INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    INNER JOIN wp_term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
    INNER JOIN wp_posts p ON tr.object_id = p.ID
    INNER JOIN wp_posts v ON v.post_parent = p.ID AND v.post_type = 'product_variation'
    INNER JOIN wp_postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
    INNER JOIN wp_sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE p.post_type = 'product'
      AND tt.taxonomy = 'product_cat'
      AND tt.parent = 0
      AND svm.vin = %s
    ORDER BY t.name
""", (VIN,))

categories = cur.fetchall()
print(f"\nMain categories: {len(categories)}")

# Check for wrong categories
wrong_cats = [
    'Air Intake System',
    'Emission Exhaust System', 
    'Fuel Storage & Handling',
    'Power Energy Storage & Link Wire',
    'Power Generation'
]

found_wrong = []
for cat in categories:
    if cat['name'] in wrong_cats:
        found_wrong.append(cat['name'])

print(f"Wrong categories found: {len(found_wrong)}")
if found_wrong:
    for name in found_wrong:
        print(f"  - {name}")

# Check products with Fuel Storage
cur.execute("""
    SELECT COUNT(*) as count
    FROM wp_posts p
    INNER JOIN wp_posts v ON v.post_parent = p.ID AND v.post_type = 'product_variation'
    INNER JOIN wp_postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
    INNER JOIN wp_sku_vin_mapping svm ON pm.meta_value = svm.sku
    INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE p.post_type = 'product'
      AND tt.taxonomy = 'product_cat'
      AND t.name = 'Fuel Storage & Handling'
      AND svm.vin = %s
""", (VIN,))
fuel_count = cur.fetchone()['count']
print(f"\nProducts with 'Fuel Storage & Handling': {fuel_count}")

# Check VIN-specific splits
cur.execute("""
    SELECT COUNT(*) as count
    FROM wp_posts
    WHERE post_type = 'product'
      AND post_title LIKE '% - T90 EV'
      AND post_status = 'publish'
""")
vin_split_count = cur.fetchone()['count']
print(f"Products with '- T90 EV' suffix: {vin_split_count}")

db.close()

print(f"\n{'='*70}")
print("SUMMARY (LOCAL)")
print("=" * 70)
print(f"Products: {product_count}")
print(f"Main categories: {len(categories)}")
print(f"Wrong categories: {len(found_wrong)}")
print(f"Products with Fuel Storage: {fuel_count}")
print(f"VIN-split products: {vin_split_count}")
