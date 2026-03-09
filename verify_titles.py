import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

skus = ['B00005351', 'B00004852', 'B00006046']

for sku in skus:
    cur.execute("""
        SELECT p.post_title 
        FROM wp_posts p
        INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
        WHERE pm.meta_key = '_sku' AND pm.meta_value = %s
        LIMIT 1
    """, (sku,))
    result = cur.fetchone()
    if result:
        print(f"{sku}: {result['post_title']}")

conn.close()
