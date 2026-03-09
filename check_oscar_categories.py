#!/usr/bin/env python3
"""
Query Oscar database for vehicle categories and sync to WordPress
"""
import psycopg2
from psycopg2.extras import RealDictCursor
import mysql.connector
import sys

# Oscar DB config
OSCAR_DB = {
    'dbname': 'parts_store',
    'user': 'postgres',
    'password': 'N0rwich!',
    'host': '80.95.207.42',
    'port': '5432'
}

# WordPress DB config
WP_DB = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

if len(sys.argv) < 2:
    print("Usage: python check_oscar_categories.py <VIN>")
    print("\nExample: python check_oscar_categories.py LSH14C4C5NA129710")
    sys.exit(1)

VEHICLE_VIN = sys.argv[1]

print("=" * 80)
print(f"CHECKING OSCAR DATABASE FOR {VEHICLE_VIN}")
print("=" * 80)
print()

# Connect to Oscar
try:
    oscar_conn = psycopg2.connect(**OSCAR_DB)
    print("✅ Connected to Oscar database")
except Exception as e:
    print(f"❌ Failed to connect to Oscar: {e}")
    sys.exit(1)

oscar_cur = oscar_conn.cursor(cursor_factory=RealDictCursor)

# Find vehicle in Oscar
print(f"\nSearching for vehicle: {VEHICLE_VIN}")

oscar_cur.execute("""
    SELECT *
    FROM motorpartsdata_serialnumber
    WHERE serial = %s
""", (VEHICLE_VIN,))

vehicle = oscar_cur.fetchone()

if not vehicle:
    print(f"❌ Vehicle {VEHICLE_VIN} not found in Oscar database")
    oscar_conn.close()
    sys.exit(1)

print(f"✅ Found vehicle:")
for key, value in vehicle.items():
    if key != 'id':
        print(f"   {key}: {value}")
print()

# Get all categories (parent titles) for this vehicle
oscar_cur.execute("""
    SELECT DISTINCT 
        pt.id,
        pt.title as main_category
    FROM motorpartsdata_parenttitle pt
    WHERE pt.serial_number_id = %s
    ORDER BY pt.title
""", (vehicle['id'],))

main_categories = oscar_cur.fetchall()

print(f"Main Categories in Oscar for {VEHICLE_VIN}: {len(main_categories)}")
print("-" * 80)

for mc in main_categories:
    # Get subcategories
    oscar_cur.execute("""
        SELECT DISTINCT
            ct.id,
            ct.title as sub_category
        FROM motorpartsdata_childtitle ct
        WHERE ct.parent_id = %s
        ORDER BY ct.title
    """, (mc['id'],))
    
    subcats = oscar_cur.fetchall()
    
    # Get part count
    oscar_cur.execute("""
        SELECT COUNT(DISTINCT p.id) as part_count
        FROM motorpartsdata_part p
        INNER JOIN motorpartsdata_childtitle ct ON p.child_title_id = ct.id
        WHERE ct.parent_id = %s
    """, (mc['id'],))
    
    part_count = oscar_cur.fetchone()['part_count']
    
    print(f"\n{mc['main_category']} ({part_count} parts)")
    if subcats:
        for sc in subcats[:5]:  # Show first 5 subcategories
            print(f"  - {sc['sub_category']}")
        if len(subcats) > 5:
            print(f"  ... and {len(subcats) - 5} more subcategories")

# Check for diesel-specific categories
print("\n" + "=" * 80)
print("CHECKING FOR DIESEL CATEGORIES")
print("=" * 80)

diesel_keywords = [
    'Engine', 'Fuel', 'Exhaust', 'Turbo', 'Cylinder', 'Crankshaft',
    'Piston', 'Injector', 'EGR', 'Diesel', 'Combustion'
]

diesel_cats_found = []
for mc in main_categories:
    for keyword in diesel_keywords:
        if keyword.lower() in mc['main_category'].lower():
            diesel_cats_found.append(mc['main_category'])
            break

if diesel_cats_found:
    print(f"\n⚠️  Found {len(diesel_cats_found)} potential diesel-related categories:")
    for cat in diesel_cats_found:
        print(f"   - {cat}")
    if 'vehicle_type' in vehicle:
        print(f"\n❓ Vehicle type: {vehicle['vehicle_type']}")
    print("   Are these categories correct for this vehicle?")
else:
    print("\n✅ No obvious diesel categories found")

# Compare with WordPress categories
print("\n" + "=" * 80)
print("COMPARING WITH WORDPRESS DATABASE")
print("=" * 80)

wp_conn = mysql.connector.connect(**WP_DB)
wp_cur = wp_conn.cursor(dictionary=True)

# Get product count for this VIN
wp_cur.execute(f"""
    SELECT COUNT(DISTINCT p.ID) as count
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id 
        AND pm.meta_key = '_sku'
    LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id 
        AND pm_var.meta_key = 'attribute_pa_variant'
    INNER JOIN {PREFIX}sku_vin_mapping svm ON pm.meta_value = svm.sku
        AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' 
             OR svm.variant_attribute = pm_var.meta_value)
        AND svm.vin = %s
    WHERE p.post_type IN ('product', 'product_variation')
      AND p.post_status = 'publish'
""", (VEHICLE_VIN,))

wp_count = wp_cur.fetchone()['count']
print(f"\nWordPress products for {VEHICLE_VIN}: {wp_count}")

# Get Oscar part count
oscar_cur.execute("""
    SELECT COUNT(DISTINCT p.id) as count
    FROM motorpartsdata_part p
    INNER JOIN motorpartsdata_childtitle ct ON p.child_title_id = ct.id
    INNER JOIN motorpartsdata_parenttitle pt ON ct.parent_id = pt.id
    WHERE pt.serial_number_id = %s
""", (vehicle['id'],))

oscar_count = oscar_cur.fetchone()['count']
print(f"Oscar parts for {VEHICLE_VIN}: {oscar_count}")

if wp_count != oscar_count:
    print(f"\n⚠️  Count mismatch! Difference: {abs(wp_count - oscar_count)}")

oscar_conn.close()
wp_conn.close()

print("\nDone.")
