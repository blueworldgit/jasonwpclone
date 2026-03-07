#!/usr/bin/env python3
import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

# Check if variation 192433 has term_relationships
cur.execute("""
    SELECT tr.object_id, tt.taxonomy, t.name
    FROM wp_term_relationships tr
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE tr.object_id = 192433
      AND tt.taxonomy = 'product_cat'
""")

results = cur.fetchall()

if results:
    print(f"Variation 192433 HAS {len(results)} category relationships:")
    for r in results[:10]:
        print(f"  - {r['name']}")
else:
    print("Variation 192433 has NO category relationships (inherits from parent)")

# Check parent
cur.execute("""
    SELECT tr.object_id, tt.taxonomy, t.name
    FROM wp_term_relationships tr
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE tr.object_id = 231815
      AND tt.taxonomy = 'product_cat'
""")

parent_cats = cur.fetchall()
print(f"\nParent 231815 has {len(parent_cats)} category relationships")

cur.close()
conn.close()
