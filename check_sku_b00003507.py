#!/usr/bin/env python3
import mysql.connector

conn = mysql.connector.connect(
    host='localhost',
    user='root', 
    password='',
    database='maxussql'
)

cur = conn.cursor(dictionary=True)

# Find all products with B00003507 SKU (with or without hash suffix)
cur.execute("""
    SELECT p.ID, p.post_title, p.post_type,
           pm1.meta_value as sku,
           pm2.meta_value as orig_sku
    FROM wp_posts p
    LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_sku'
    LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'original_sku'
    WHERE pm1.meta_value LIKE 'B00003507%'
       OR pm2.meta_value = 'B00003507'
    ORDER BY p.ID
""")

results = cur.fetchall()

print(f"\nProducts with SKU B00003507*: {len(results)}\n")

for r in results:
    title = (r['post_title'] or '')[:60]
    sku = r['sku'] or 'None'
    orig = r['orig_sku'] or 'None'
    print(f"ID {r['ID']:6} ({r['post_type']:17}) | {title:60} | SKU: {sku:20} | orig: {orig}")

cur.close()
conn.close()
