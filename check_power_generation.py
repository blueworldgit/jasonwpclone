#!/usr/bin/env python3
"""
Check Power Generation category for E DELIVER 3
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
print(f"CHECKING POWER GENERATION CATEGORY FOR {VIN}")
print("=" * 80)

# Get serial number ID
oscar_cur.execute("""
    SELECT id FROM motorpartsdata_serialnumber WHERE serial = %s
""", (VIN,))
serial_id = oscar_cur.fetchone()['id']

# Check if Power Generation category exists for this vehicle
oscar_cur.execute("""
    SELECT pt.id, pt.title
    FROM motorpartsdata_parenttitle pt
    WHERE pt.serial_number_id = %s
        AND pt.title ILIKE '%power generation%'
""", (serial_id,))

power_gen = oscar_cur.fetchone()

if power_gen:
    print(f"\n⚠️  Power Generation category EXISTS for this vehicle!")
    print(f"   Category ID: {power_gen['id']}")
    print(f"   Title: {power_gen['title']}")
    
    # Get parts in this category
    oscar_cur.execute("""
        SELECT 
            p.part_number,
            p.usage_name,
            ct.title as subcategory
        FROM motorpartsdata_part p
        INNER JOIN motorpartsdata_childtitle ct ON p.child_title_id = ct.id
        WHERE ct.parent_id = %s
        ORDER BY ct.title, p.part_number
        LIMIT 30
    """, (power_gen['id'],))
    
    parts = oscar_cur.fetchall()
    print(f"\n   First 30 parts in Power Generation:")
    for p in parts:
        print(f"   {p['part_number']}: {p['usage_name']}")
        print(f"     → {p['subcategory']}")
else:
    print("\n✅ No Power Generation category found")

# Check all categories for this vehicle
print("\n" + "=" * 80)
print("ALL CATEGORIES FOR THIS VEHICLE")
print("=" * 80)

oscar_cur.execute("""
    SELECT 
        pt.title,
        COUNT(DISTINCT p.id) as part_count
    FROM motorpartsdata_parenttitle pt
    LEFT JOIN motorpartsdata_childtitle ct ON ct.parent_id = pt.id
    LEFT JOIN motorpartsdata_part p ON p.child_title_id = ct.id
    WHERE pt.serial_number_id = %s
    GROUP BY pt.title
    ORDER BY pt.title
""", (serial_id,))

categories = oscar_cur.fetchall()

diesel_keywords = ['engine', 'fuel', 'exhaust', 'power generation', 'cylinder', 'piston', 'turbo', 'injection', 'combustion']
electric_keywords = ['ept', 'battery', 'electric', 'charging', 'motor controller']

print("\nCategories:")
for cat in categories:
    cat_lower = cat['title'].lower()
    is_diesel = any(kw in cat_lower for kw in diesel_keywords)
    is_electric = any(kw in cat_lower for kw in electric_keywords)
    
    marker = ""
    if is_diesel:
        marker = " ⚠️ DIESEL"
    elif is_electric:
        marker = " ✅ ELECTRIC"
    
    print(f"  {cat['title']}: {cat['part_count']} parts{marker}")

oscar_conn.close()
