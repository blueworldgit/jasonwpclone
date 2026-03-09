import mysql.connector

conn = mysql.connector.connect(host='localhost', user='root', password='', database='maxussql')
cur = conn.cursor(dictionary=True)

# Find Fuel Storage category
cur.execute("""
    SELECT t.term_id, t.name, tt.parent
    FROM wp_terms t
    INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    WHERE t.name LIKE '%Fuel Storage%'
    AND tt.taxonomy = 'product_cat'
""")

fuel_cat = cur.fetchone()
if fuel_cat:
    print(f"Fuel Storage category: {fuel_cat['name']} (ID: {fuel_cat['term_id']})")
    print()
    
    # Find its subcategories
    cur.execute("""
        SELECT t.term_id, t.name
        FROM wp_terms t
        INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
        WHERE tt.parent = %s
        AND tt.taxonomy = 'product_cat'
        ORDER BY t.name
    """, (fuel_cat['term_id'],))
    
    subcats = cur.fetchall()
    print(f"Subcategories of '{fuel_cat['name']}':")
    for sc in subcats:
        print(f"  - {sc['name']} (ID: {sc['term_id']})")
        
        # Check if B00004852 has this subcategory
        cur.execute("""
            SELECT COUNT(*) as cnt
            FROM wp_posts p
            INNER JOIN wp_postmeta pm ON p.ID = pm.post_id 
                AND pm.meta_key = '_sku' AND pm.meta_value = 'B00004852'
            INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
            INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.term_id = %s
        """, (sc['term_id'],))
        
        if cur.fetchone()['cnt'] > 0:
            print(f"    ^^ B00004852 HAS THIS CATEGORY!")

conn.close()
