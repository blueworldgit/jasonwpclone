#!/usr/bin/env python3
"""Test parameter binding in RemoteSQL."""
from sql_exec import RemoteSQL

print("Testing parameter binding...")
db = RemoteSQL()
cur = db.cursor(dictionary=True)

# Test with string parameter
test_sku = "B00003507"
cur.execute('SELECT COUNT(*) as cnt FROM wp_postmeta WHERE meta_key = %s AND meta_value = %s', ('_sku', test_sku))
result = cur.fetchone()
print(f"Products with SKU {test_sku}: {result['cnt']}")

# Test with integer parameter
cur.execute('SELECT post_title FROM wp_posts WHERE ID = %s', (219523,))
result = cur.fetchone()
print(f"Product 219523: {result}")

print("\n✓ Parameter binding works!")
db.close()
