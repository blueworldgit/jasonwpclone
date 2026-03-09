#!/usr/bin/env python3
import mysql.connector
DB_CFG = dict(host='localhost', user='root', password='', database='maxussql')
PREFIX = 'wp_'
conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print('=== variant_attribute column in wp_sku_vin_mapping ===')
cur.execute(f"""
    SELECT DISTINCT variant_attribute 
    FROM {PREFIX}sku_vin_mapping 
    WHERE variant_attribute IS NOT NULL 
    AND variant_attribute != ''
    ORDER BY variant_attribute 
    LIMIT 40
""")
rows = cur.fetchall()
if rows:
    print(f"Found {len(rows)} distinct variant_attribute values:")
    for r in rows:
        print(f"  {r['variant_attribute']}")
else:
    print("No variant_attribute values found")

cur.execute(f"""
    SELECT COUNT(*) as c 
    FROM {PREFIX}sku_vin_mapping 
    WHERE variant_attribute IS NOT NULL 
    AND variant_attribute != ''
""")
print(f"\nRows with variant_attribute: {cur.fetchone()['c']}")

cur.execute(f"SELECT COUNT(*) as c FROM {PREFIX}sku_vin_mapping")
print(f"Total rows in table: {cur.fetchone()['c']}")

# Test: Do any variant_attribute values match pa_variant values?
print('\n=== Testing if variant_attribute matches pa_variant ===')
cur.execute(f"""
    SELECT 
        svm.sku,
        svm.vin,
        svm.variant_attribute as mapping_variant,
        pm.meta_value as product_variant
    FROM {PREFIX}sku_vin_mapping svm
    INNER JOIN {PREFIX}postmeta pm_sku ON svm.sku = pm_sku.meta_value 
        AND pm_sku.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm ON pm_sku.post_id = pm.post_id 
        AND pm.meta_key = 'attribute_pa_variant'
    WHERE svm.variant_attribute IS NOT NULL
    AND svm.variant_attribute != ''
    LIMIT 20
""")
matches = cur.fetchall()
if matches:
    print("Sample SKU rows with variant_attribute:")
    print(f"{'SKU':<15} {'VIN':<20} {'Mapping Variant':<20} {'Prod Variant':<20}")
    print("-" * 80)
    for m in matches:
        mv = (m['mapping_variant'] or '')[:19]
        pv = (m['product_variant'] or '')[:19]
        print(f"{m['sku']:<15} {m['vin']:<20} {mv:<20} {pv:<20}")
else:
    print("No matches found")

cur.close()
conn.close()
