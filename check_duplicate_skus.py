"""
Check how many local products have the same SKU.
"""
import mysql.connector

DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

SKU = "B00003507"

# Find all products with this SKU
cur.execute(f"""
    SELECT p.ID, p.post_title, p.post_type, pm.meta_value as sku
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}postmeta pm ON p.ID = pm.post_id
    WHERE pm.meta_key = '_sku' AND pm.meta_value = %s
    AND p.post_status = 'publish'
    ORDER BY p.ID
""", (SKU,))

products = cur.fetchall()
print(f"Found {len(products)} products with SKU '{SKU}':\n")

for prod in products:
    # Get categories for this product
    cur.execute(f"""
        SELECT t.term_id, t.name, tt.parent
        FROM {PREFIX}term_relationships tr
        INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
        WHERE tr.object_id = %s
          AND tt.taxonomy = 'product_cat'
        ORDER BY t.name
    """, (prod['ID'],))
    
    cats = cur.fetchall()
    print(f"Product ID {prod['ID']} ({prod['post_type']}): {prod['post_title'][:60]}")
    print(f"  Categories: {', '.join([c['name'] for c in cats])}")
    
    # Check if in sku_vin_mapping
    cur.execute(f"""
        SELECT vin FROM {PREFIX}sku_vin_mapping
        WHERE sku = %s
    """, (SKU,))
    vins = [r['vin'] for r in cur.fetchall()]
    print(f"  VIN mappings: {', '.join(vins) if vins else 'NONE'}")
    print()

cur.close()
conn.close()
