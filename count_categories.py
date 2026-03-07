#!/usr/bin/env python3
import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor()

# Count categories on variation
cur.execute("""
    SELECT COUNT(*) 
    FROM wp_term_relationships tr 
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
    WHERE tr.object_id=192433 AND tt.taxonomy='product_cat'
""")
var_count = cur.fetchone()[0]

# Count categories on parent
cur.execute("""
    SELECT COUNT(*) 
    FROM wp_term_relationships tr 
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
    WHERE tr.object_id=231815 AND tt.taxonomy='product_cat'
""")
parent_count = cur.fetchone()[0]

print(f"Variation 192433 (B00003507): {var_count} categories in term_relationships")
print(f"Parent 231815 (NUT-AIR CLEANER - T90 EV): {parent_count} categories in term_relationships")

if var_count > 0:
    print("\n⚠️  VARIATION HAS ITS OWN CATEGORIES - this is unusual for WooCommerce")
else:
    print("\n✓ Variation has NO categories (normal - inherits from parent)")

cur.close()
conn.close()
