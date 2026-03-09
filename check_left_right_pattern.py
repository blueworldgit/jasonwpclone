#!/usr/bin/env python3
import mysql.connector
DB_CFG = dict(host='localhost', user='root', password='', database='maxussql')
conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print('=== Pattern check: lowercase + SKU suffix ===')
cur.execute("""
    SELECT meta_value 
    FROM wp_postmeta 
    WHERE meta_key = 'attribute_pa_variant'
    AND meta_value REGEXP '^(left|right)-[bc][0-9]'
    LIMIT 20
""")
print('Product variants with pattern (left|right)-SKU:')
for r in cur.fetchall():
    print(f'  {r["meta_value"]}')

cur.execute("""
    SELECT DISTINCT variant_attribute 
    FROM wp_sku_vin_mapping 
    WHERE variant_attribute IN ('Left', 'Right', 'left', 'right')
    ORDER BY variant_attribute
""")
print('\nMapping variants (Left/Right):')
for r in cur.fetchall():
    print(f'  {r["variant_attribute"]}')

cur.execute("""
    SELECT COUNT(*) as c
    FROM wp_postmeta
    WHERE meta_key = 'attribute_pa_variant'
    AND meta_value REGEXP '^(left|right)-[bc][0-9]'
""")
print(f'\nTotal products with (left|right)-SKU pattern: {cur.fetchone()["c"]}')

cur.execute("""
    SELECT COUNT(*) as c
    FROM wp_sku_vin_mapping
    WHERE variant_attribute IN ('Left', 'Right')
""")
print(f'Total mapping rows with Left/Right: {cur.fetchone()["c"]}')

conn.close()
