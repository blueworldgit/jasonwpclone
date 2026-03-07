#!/usr/bin/env python3
"""Check how many products have SKU B00003507"""
import mysql.connector

DB_CONFIG = {'host': 'localhost', 'user': 'root', 'password': '', 'database': 'maxussql'}
conn = mysql.connector.connect(**DB_CONFIG)
cur = conn.cursor(dictionary=True)

cur.execute("""
    SELECT p.ID, p.post_title, p.post_type, p.post_parent, pm.meta_value as sku
    FROM wp_posts p
    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
    WHERE pm.meta_value = 'B00003507'
      AND p.post_status = 'publish'
    ORDER BY p.post_type, p.ID
""")

products = cur.fetchall()
print(f"\nFound {len(products)} products with SKU B00003507:\n")
for p in products:
    print(f"  ID {p['ID']:6} | Type: {p['post_type']:18} | Parent: {p['post_parent']:6} | {p['post_title']}")

cur.close()
conn.close()
