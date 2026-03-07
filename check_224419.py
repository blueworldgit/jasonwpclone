#!/usr/bin/env python3
import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

# Check product 224419
cur.execute("""
    SELECT p.ID, p.post_type, p.post_title, p.post_parent,
           pm.meta_value as sku
    FROM wp_posts p
    LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
    WHERE p.ID = 224419
""")

product = cur.fetchone()
print(f"\nProduct 224419:")
print(f"  Type: {product['post_type']}")
print(f"  Title: {product['post_title']}")
print(f"  Parent: {product['post_parent']}")
print(f"  SKU: {product['sku']}")

# Get its categories
cur.execute("""
    SELECT t.name
    FROM wp_term_relationships tr
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE tr.object_id = 224419
      AND tt.taxonomy = 'product_cat'
    ORDER BY t.name
""")

categories = [r['name'] for r in cur.fetchall()]
print(f"\n  Has {len(categories)} categories:")
if 'Fuel Storage & Handling' in categories:
    print(f"    - Fuel Storage & Handling ❌")
for cat in categories[:10]:
    if cat != 'Fuel Storage & Handling':
        print(f"    - {cat}")

# Check VIN mapping
if product['sku']:
    cur.execute("SELECT vin, vehicle_name FROM wp_sku_vin_mapping WHERE sku = %s", (product['sku'],))
    vins = cur.fetchall()
    print(f"\n  VIN mappings:")
    for v in vins:
        print(f"    - {v['vin']} ({v['vehicle_name']})")

cur.close()
conn.close()
