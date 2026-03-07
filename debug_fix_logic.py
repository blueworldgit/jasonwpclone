"""
Debug: Check what categories the fix logic would assign to T90 EV products.
"""
import mysql.connector
import json

VIN = "LSFAM120XNA160733"
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

# Load the stored JSON for T90 EV from source
with open(f"categories_{VIN}.json", "r", encoding="utf-8") as f:
    source_data = json.load(f)

print(f"Source data for {VIN}:")
print(f"  Main categories: {len(source_data['main_categories'])}")
print(f"  Sub categories: {len(source_data['sub_categories'])}")
print(f"  Serial category ID: {source_data['serial_category']['id']}")
print()

# Build the valid_cat_ids set (what the fix script should filter to)
valid_cat_ids = {source_data['serial_category']['id']}
for mc in source_data['main_categories']:
    valid_cat_ids.add(mc['id'])
for sc in source_data['sub_categories']:
    valid_cat_ids.add(sc['id'])

print(f"valid_cat_ids set has {len(valid_cat_ids)} category IDs")
print()

# Check if "Fuel Storage & Handling" or "Air Intake System" are in valid_cat_ids
fuel_on_source = any(
    mc['name'] == 'Fuel Storage & Handling' 
    for mc in source_data['main_categories']
)
air_on_source = any(
    mc['name'] == 'Air Intake System' 
    for mc in source_data['main_categories']
)

print(f"'Fuel Storage & Handling' in source main categories: {fuel_on_source}")
print(f"'Air Intake System' in source main categories: {air_on_source}")
print()

# Now check what SKU B00003507 is mapped to in sku_vin_mapping
conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

cur.execute(f"""
    SELECT vin FROM {PREFIX}sku_vin_mapping WHERE sku = 'B00003507'
""")
vins = [r['vin'] for r in cur.fetchall()]
print(f"SKU B00003507 is mapped to VINs: {vins}")
print()

# Get the product ID for this SKU
cur.execute(f"""
    SELECT p.ID, pm.meta_value as sku
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id
    WHERE pm.meta_key = '_sku' AND pm.meta_value = 'B00003507'
    LIMIT 1
""")
product = cur.fetchone()
if product:
    print(f"Product ID {product['ID']} has SKU {product['sku']}")
    
    # Get its current categories
    cur.execute(f"""
        SELECT t.term_id, t.name, tt.parent
        FROM {PREFIX}term_relationships tr
        INNER JOIN {PREFIX}term_taxonomy tt 
            ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
        WHERE tr.object_id = %s
          AND tt.taxonomy = 'product_cat'
        ORDER BY tt.parent, t.name
    """, (product['ID'],))
    cats = cur.fetchall()
    print(f"\nCurrent categories for product {product['ID']}:")
    for cat in cats:
        parent_mark = "(main)" if cat['parent'] == 0 else f"(parent: {cat['parent']})"
        print(f"  - {cat['name']} (ID: {cat['term_id']}) {parent_mark}")
else:
    print("Product not found!")

cur.close()
conn.close()
