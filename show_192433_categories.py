#!/usr/bin/env python3
"""
Check what happened: Did fix_all_vins.py update product 192433?
Look at what categories it has now.
"""
import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

# Get categories for variation 192433
cur.execute("""
    SELECT t.term_id, t.name, t.slug
    FROM wp_term_relationships tr
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE tr.object_id = 192433
      AND tt.taxonomy = 'product_cat'
    ORDER BY t.name
""")

categories = cur.fetchall()

print(f"Variation 192433 (SKU B00003507) has {len(categories)} categories:\n")

# Group by type
main_cats = []
subcats = []

for cat in categories:
    # Check if it has a parent
    cur.execute("SELECT parent FROM wp_term_taxonomy WHERE term_id = %s AND taxonomy = 'product_cat'", (cat['term_id'],))
    parent = cur.fetchone()['parent']
    
    if parent == 0:
        main_cats.append(cat['name'])
    else:
        subcats.append(cat['name'])

print(f"Main categories ({len(main_cats)}):")
for name in sorted(main_cats):
    print(f"  - {name}")

print(f"\nSubcategories ({len(subcats)}):")
for name in sorted(subcats)[:20]:  # Show first 20
    print(f"  - {name}")

if len(subcats) > 20:
    print(f"  ... and {len(subcats) - 20} more")

cur.close()
conn.close()
