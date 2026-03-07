#!/usr/bin/env python3
import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

# Check if problematic SKUs are in sku_vin_mapping
test_skus = ['B00003507', 'B00004085', 'B00004151']

for sku in test_skus:
    cur.execute("""
        SELECT sku, vin, vehicle_name
        FROM wp_sku_vin_mapping
        WHERE sku = %s
    """, (sku,))
    
    results = cur.fetchall()
    print(f"\nSKU {sku}:")
    if results:
        for r in results:
            print(f"  VIN: {r['vin']} ({r['vehicle_name']})")
    else:
        print(f"  NOT FOUND in sku_vin_mapping!")

cur.close()
conn.close()
