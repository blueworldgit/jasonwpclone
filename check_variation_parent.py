"""
Check the parent of variation 192433 and its categories.
"""
import mysql.connector

DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

VAR_ID = 192433

# Get the variation's parent
cur.execute(f"""
    SELECT p.ID, p.post_parent, p.post_type, p.post_title,
           pm.meta_value as sku
    FROM {PREFIX}posts p
    LEFT JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
    WHERE p.ID = %s
""", (VAR_ID,))

var = cur.fetchone()
print(f"Variation {VAR_ID}:")
print(f"  Title: {var['post_title']}")
print(f"  SKU: {var['sku']}")
print(f"  Parent ID: {var['post_parent']}")
print()

# Get the parent product
parent_id = var['post_parent']
cur.execute(f"""
    SELECT p.ID, p.post_type, p.post_title,
           pm.meta_value as sku
    FROM {PREFIX}posts p
    LEFT JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
    WHERE p.ID = %s
""", (parent_id,))

parent = cur.fetchone()
print(f"Parent {parent_id} ({parent['post_type']}):")
print(f"  Title: {parent['post_title']}")
print(f"  SKU: {parent['sku']}")
print()

# Get parent's categories
cur.execute(f"""
    SELECT t.term_id, t.name
    FROM {PREFIX}term_relationships tr
    INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
    WHERE tr.object_id = %s
      AND tt.taxonomy = 'product_cat'
    ORDER BY t.name
""", (parent_id,))

parent_cats = cur.fetchall()
print(f"Parent's categories ({len(parent_cats)}):")
for cat in parent_cats:
    print(f"  - {cat['name']}")
print()

# Get ALL variations of this parent
cur.execute(f"""
    SELECT p.ID, pm.meta_value as sku
    FROM {PREFIX}posts p
    LEFT JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
    WHERE p.post_parent = %s
      AND p.post_type = 'product_variation'
    ORDER BY p.ID
""", (parent_id,))

variations = cur.fetchall()
print(f"All variations of parent {parent_id} ({len(variations)}):")
for v in variations:
    # Check VIN mapping
    cur.execute(f"""
        SELECT vin FROM {PREFIX}sku_vin_mapping
        WHERE sku = %s
    """, (v['sku'],))
    vins = [r['vin'] for r in cur.fetchall()]
    print(f"  - ID {v['ID']}, SKU {v['sku']}, VINs: {vins if vins else 'NONE'}")

cur.close()
conn.close()
