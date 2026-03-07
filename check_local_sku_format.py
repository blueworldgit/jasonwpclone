#!/usr/bin/env python3
import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

# Get sample SKUs from local database
cur.execute("""
    SELECT pm.meta_value as sku
    FROM wp_postmeta pm
    INNER JOIN wp_posts p ON pm.post_id = p.ID
    WHERE pm.meta_key = '_sku'
      AND pm.meta_value LIKE 'B%'
      AND p.post_status = 'publish'
    LIMIT 20
""")

print("Sample local SKUs:\n")
for r in cur.fetchall():
    print(f"  {r['sku']}")

# Check if any have hash suffixes
cur.execute("""
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN meta_value LIKE '%-%' THEN 1 ELSE 0 END) as with_hash,
        SUM(CASE WHEN meta_value NOT LIKE '%-%' THEN 1 ELSE 0 END) as without_hash
    FROM wp_postmeta pm
    INNER JOIN wp_posts p ON pm.post_id = p.ID
    WHERE pm.meta_key = '_sku'
      AND pm.meta_value != ''
      AND p.post_status = 'publish'
""")

stats = cur.fetchone()
print(f"\nLocal SKU format statistics:")
print(f"  Total SKUs: {stats['total']}")
print(f"  With hash suffix (contains '-'): {stats['with_hash']}")
print(f"  Without hash: {stats['without_hash']}")

cur.close()
conn.close()
