#!/usr/bin/env python3
import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

# Check parent "NUT-AIR CLEANER INLET DUCT TO BODY - T90 EV" and its variations
cur.execute("""
    SELECT 
        p.ID,
        p.post_type,
        p.post_title,
        p.post_parent,
        pm_sku.meta_value as sku,
        pm_orig.meta_value as orig_sku
    FROM wp_posts p
    LEFT JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN wp_postmeta pm_orig ON p.ID = pm_orig.post_id AND pm_orig.meta_key = 'original_sku'
    WHERE (p.ID = 231815 OR p.post_parent = 231815)
      AND p.post_status = 'publish'
    ORDER BY p.post_type, p.ID
""")

print("\nParent 231815 and its variations:\n")
for r in cur.fetchall():
    print(f"ID {r['ID']:6} ({r['post_type']:17}) | Parent: {r['post_parent'] or 'N/A':6} | SKU: {r['sku'] or 'None':20} | orig_sku: {r['orig_sku'] or 'None':20} | {r['post_title'][:60]}")

cur.close()
conn.close()
