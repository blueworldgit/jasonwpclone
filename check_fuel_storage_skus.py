"""
Check why products with "Fuel Storage & Handling" are still linked to T90 EV.
Are they shared with other VINs?
"""
import mysql.connector

VIN = "LSFAM120XNA160733"
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

# Get SKUs that have "Fuel Storage" category AND are linked to T90 EV
cur.execute(f"""
    SELECT DISTINCT pm.meta_value AS sku
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id
        AND pm.meta_key = '_sku' AND pm.meta_value != ''
    INNER JOIN {PREFIX}sku_vin_mapping svm
        ON pm.meta_value = svm.sku AND svm.vin = %s
    INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
    INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        AND tt.taxonomy = 'product_cat'
    INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
        AND t.name LIKE %s
    WHERE p.post_type IN ('product','product_variation')
      AND p.post_status = 'publish'
""", (VIN, '%Fuel Storage%'))

fuel_skus = [r["sku"] for r in cur.fetchall()]

print(f"SKUs with 'Fuel Storage' category linked to T90 EV: {len(fuel_skus)}\n")

# For each SKU, check all VINs it's mapped to
for sku in fuel_skus[:5]:  # Check first 5
    cur.execute(f"""
        SELECT vin, vehicle_name
        FROM {PREFIX}sku_vin_mapping
        WHERE sku = %s
        ORDER BY vin
    """, (sku,))
    
    vins = cur.fetchall()
    print(f"SKU: {sku}")
    print(f"  Mapped to {len(vins)} VIN(s):")
    for v in vins:
        print(f"    - {v['vin']} ({v['vehicle_name']})")
    
    # Check if this SKU exists on source for any of those VINs
    # We can't check source live, but we can check the sku_to_cats that was built
    print()

cur.close()
conn.close()
