#!/usr/bin/env python3
"""
Deep inspection of vehicle serial in Oscar database
"""
import psycopg2
from psycopg2.extras import RealDictCursor

OSCAR_DB = {
    'dbname': 'parts_store',
    'user': 'postgres',
    'password': 'N0rwich!',
    'host': '80.95.207.42',
    'port': '5432'
}

VIN = "LSH14C4C5NA129710"

oscar_conn = psycopg2.connect(**OSCAR_DB)
oscar_cur = oscar_conn.cursor(cursor_factory=RealDictCursor)

print("=" * 80)
print(f"DEEP INSPECTION OF {VIN} IN OSCAR DATABASE")
print("=" * 80)

# Get all details about this serial
oscar_cur.execute("""
    SELECT *
    FROM motorpartsdata_serialnumber
    WHERE serial = %s
""", (VIN,))

vehicle = oscar_cur.fetchone()

print("\nVehicle Record in Oscar:")
print("-" * 80)
for key, value in vehicle.items():
    print(f"  {key}: {value}")

# Check if there are multiple serials with similar patterns
print("\n" + "=" * 80)
print("SEARCHING FOR SIMILAR SERIALS")
print("=" * 80)

oscar_cur.execute("""
    SELECT id, serial, vehicle_brand
    FROM motorpartsdata_serialnumber
    WHERE serial LIKE 'LSH14%'
    ORDER BY serial
""")

similar = oscar_cur.fetchall()
print(f"\nFound {len(similar)} serials starting with 'LSH14':")
for s in similar:
    oscar_cur.execute("""
        SELECT COUNT(DISTINCT pt.id) as cat_count
        FROM motorpartsdata_parenttitle pt
        WHERE pt.serial_number_id = %s
    """, (s['id'],))
    cat_count = oscar_cur.fetchone()['cat_count']
    
    oscar_cur.execute("""
        SELECT COUNT(DISTINCT p.id) as part_count
        FROM motorpartsdata_part p
        INNER JOIN motorpartsdata_childtitle ct ON p.child_title_id = ct.id
        INNER JOIN motorpartsdata_parenttitle pt ON ct.parent_id = pt.id
        WHERE pt.serial_number_id = %s
    """, (s['id'],))
    part_count = oscar_cur.fetchone()['part_count']
    
    print(f"  {s['serial']}: {cat_count} categories, {part_count} parts")

# Sample some parts to see their types
print("\n" + "=" * 80)
print("SAMPLE PARTS FOR THIS VEHICLE")
print("=" * 80)

oscar_cur.execute("""
    SELECT 
        p.part_number,
        p.usage_name,
        ct.title as subcategory,
        pt.title as main_category
    FROM motorpartsdata_part p
    INNER JOIN motorpartsdata_childtitle ct ON p.child_title_id = ct.id
    INNER JOIN motorpartsdata_parenttitle pt ON ct.parent_id = pt.id
    WHERE pt.serial_number_id = %s
    ORDER BY pt.title, ct.title
    LIMIT 20
""", (vehicle['id'],))

parts = oscar_cur.fetchall()
print("\nFirst 20 parts:")
for p in parts:
    print(f"  {p['part_number']}: {p['usage_name']}")
    print(f"    → {p['main_category']} / {p['subcategory']}")

# Check for electric-specific categories
print("\n" + "=" * 80)
print("CHECKING FOR ELECTRIC VS DIESEL INDICATORS")
print("=" * 80)

# Electric indicators
oscar_cur.execute("""
    SELECT COUNT(DISTINCT p.id) as count
    FROM motorpartsdata_part p
    INNER JOIN motorpartsdata_childtitle ct ON p.child_title_id = ct.id
    INNER JOIN motorpartsdata_parenttitle pt ON ct.parent_id = pt.id
    WHERE pt.serial_number_id = %s
        AND (pt.title ILIKE '%EPT%' 
         OR pt.title ILIKE '%electric%' 
         OR pt.title ILIKE '%battery%'
         OR pt.title ILIKE '%charging%')
""", (vehicle['id'],))

electric_parts = oscar_cur.fetchone()['count']

# Diesel indicators
oscar_cur.execute("""
    SELECT COUNT(DISTINCT p.id) as count
    FROM motorpartsdata_part p
    INNER JOIN motorpartsdata_childtitle ct ON p.child_title_id = ct.id
    INNER JOIN motorpartsdata_parenttitle pt ON ct.parent_id = pt.id
    WHERE pt.serial_number_id = %s
        AND (pt.title ILIKE '%engine%'
         OR pt.title ILIKE '%fuel%'
         OR pt.title ILIKE '%exhaust%'
         OR pt.title ILIKE '%cylinder%'
         OR pt.title ILIKE '%piston%'
         OR pt.title ILIKE '%injection%')
""", (vehicle['id'],))

diesel_parts = oscar_cur.fetchone()['count']

print(f"\nElectric-related categories: {electric_parts} parts")
print(f"Diesel-related categories: {diesel_parts} parts")

if diesel_parts > electric_parts:
    print("\n⚠️  WARNING: More diesel parts than electric parts!")
    print("   This serial may have wrong data in Oscar database")
elif electric_parts > diesel_parts:
    print("\n✅ Predominantly electric parts - seems correct")
else:
    print("\n? Equal number of electric/diesel parts - hybrid or data issue?")

oscar_conn.close()
