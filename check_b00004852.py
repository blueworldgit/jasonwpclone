import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

cur.execute("""
    SELECT p.ID, p.post_title, GROUP_CONCAT(t.name ORDER BY t.name) as cats
    FROM wp_posts p
    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id 
        AND pm.meta_key = '_sku' AND pm.meta_value = 'B00004852'
    LEFT JOIN wp_term_relationships tr ON p.ID = tr.object_id
    LEFT JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
        AND tt.taxonomy = 'product_cat'
    LEFT JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE p.post_type IN ('product', 'product_variation')
    GROUP BY p.ID
""")

result = cur.fetchone()
print(f"ID: {result['ID']}")
print(f"Title: {result['post_title']}")
print(f"Categories: {result['cats']}")

conn.close()
