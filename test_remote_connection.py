#!/usr/bin/env python3
"""Test remote SQL connection."""
from sql_exec import RemoteSQL

print("Testing remote SQL connection...")
db = RemoteSQL()
cur = db.cursor(dictionary=True)

# Test query
cur.execute('SELECT COUNT(*) as cnt FROM wp_posts WHERE post_type = "product"')
result = cur.fetchone()
print(f"Products on themed site: {result['cnt']}")

cur.execute('SELECT COUNT(*) as cnt FROM wp_posts WHERE post_type = "product_variation"')
result = cur.fetchone()
print(f"Variations on themed site: {result['cnt']}")

cur.execute('SELECT COUNT(DISTINCT vin) as cnt FROM wp_sku_vin_mapping')
result = cur.fetchone()
print(f"VINs in mapping: {result['cnt']}")

print("\n✓ Connection successful!")
db.close()
