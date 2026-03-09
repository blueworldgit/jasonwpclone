#!/usr/bin/env python3
import mysql.connector
DB_CFG = dict(host='localhost', user='root', password='', database='maxussql')
PREFIX = 'wp_'
conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print('=== pa_variant values (first 50) ===')
cur.execute(f"""
    SELECT DISTINCT meta_value 
    FROM {PREFIX}postmeta 
    WHERE meta_key = 'attribute_pa_variant'
    AND meta_value != ''
    ORDER BY meta_value
    LIMIT 50
""")
for r in cur.fetchall():
    val = r['meta_value'][:100]
    print(f'  {val}')

print('\n=== VINs in mapping table (first 20) ===')
cur.execute(f"""
    SELECT DISTINCT vin 
    FROM {PREFIX}sku_vin_mapping 
    ORDER BY vin 
    LIMIT 20
""")
for r in cur.fetchall():
    print(f'  {r["vin"]}')

print('\n=== Do any variant values match VINs? ===')
cur.execute(f"""
    SELECT COUNT(*) as c
    FROM {PREFIX}postmeta pm
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm.meta_value = svm.vin
    WHERE pm.meta_key = 'attribute_pa_variant'
""")
print(f"Direct matches: {cur.fetchone()['c']}")

cur.close()
conn.close()
