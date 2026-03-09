#!/usr/bin/env python3
"""
Check how parent variable products are supposed to link to VINs
"""
import mysql.connector

DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

print("=" * 80)
print("PARENT PRODUCT VIN LINKING")
print("=" * 80)

# Get a sample parent product
print("\n=== Sample parent variable product ===")
cur.execute(f"""
    SELECT p.ID, p.post_title, p.post_name
    FROM {PREFIX}posts p
    WHERE p.post_type = 'product'
    AND EXISTS (
        SELECT 1 FROM {PREFIX}posts child
        WHERE child.post_parent = p.ID
        AND child.post_type = 'product_variation'
    )
    LIMIT 1
""")
parent = cur.fetchone()
if parent:
    parent_id = parent['ID']
    print(f"Parent ID: {parent_id}")
    print(f"Title: {parent['post_title']}")
    print(f"Slug: {parent['post_name']}")
    
    # Get all meta for this parent
    print(f"\nAll meta data for parent {parent_id}:")
    cur.execute(f"""
        SELECT meta_key, meta_value
        FROM {PREFIX}postmeta
        WHERE post_id = %s
        ORDER BY meta_key
    """, (parent_id,))
    metas = cur.fetchall()
    for m in metas:
        val = m['meta_value'][:80] + ('...' if len(m['meta_value']) > 80 else '')
        print(f"  {m['meta_key']}: {val}")
    
    # Get variations of this parent
    print(f"\nVariations of parent {parent_id}:")
    cur.execute(f"""
        SELECT p.ID, pm_sku.meta_value as sku
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        WHERE p.post_parent = %s
        AND p.post_type = 'product_variation'
        ORDER BY pm_sku.meta_value
        LIMIT 10
    """, (parent_id,))
    variations = cur.fetchall()
    for v  in variations:
        # Check if this variation SKU is in mapping
        cur.execute(f"""
            SELECT COUNT(*) as c, GROUP_CONCAT(DISTINCT vin) as vins
            FROM {PREFIX}sku_vin_mapping
            WHERE sku = %s
        """, (v['sku'],))
        mapping_info = cur.fetchone()
        if mapping_info['c'] > 0:
            print(f"    Variation {v['ID']}: {v['sku']} → VINs: {mapping_info['vins'][:50]}")
        else:
            print(f"    Variation {v['ID']}: {v['sku']} → NOT in mapping")
    
    # Check product attributes (for variable products)
    print(f"\nProduct attributes for parent {parent_id}:")
    cur.execute(f"""
        SELECT meta_value
        FROM {PREFIX}postmeta
        WHERE post_id = %s
        AND meta_key = '_product_attributes'
    """, (parent_id,))
    attr_row = cur.fetchone()
    if attr_row and attr_row['meta_value']:
        import pickle, base64
        try:
            attrs = pickle.loads(base64.b64decode(attr_row['meta_value']))
            print(f"  Attributes: {list(attrs.keys())}")
        except:
            print(f"  Raw: {attr_row['meta_value'][:200]}")
    else:
        print("  No _product_attributes meta found")

# Check attributes stored in variation meta
print("\n=== Variation attribute meta (attribute_*) ===")
cur.execute(f"""
    SELECT DISTINCT meta_key
    FROM {PREFIX}postmeta
    WHERE meta_key LIKE 'attribute_%'
    ORDER BY meta_key
    LIMIT 20
""")
attrs = cur.fetchall()
if attrs:
    print("Found attribute meta keys:")
    for a in attrs:
        cur.execute(f"""
            SELECT COUNT(*) as c, GROUP_CONCAT(DISTINCT meta_value ORDER BY meta_value SEPARATOR ', ') as vals
            FROM {PREFIX}postmeta
            WHERE meta_key = %s
        """, (a['meta_key'],))
        info = cur.fetchone()
        vals = info['vals'][:100] + ('...' if info['vals'] and len(info['vals']) > 100 else '')
        print(f"  {a['meta_key']}: {info['c']} rows")
        print(f"    Sample values: {vals}")
else:
    print("No attribute_* meta keys found")

# Check terms/taxonomy for attributes
print("\n=== Product attribute taxonomies ===")
cur.execute(f"""
    SELECT DISTINCT taxonomy
    FROM {PREFIX}term_taxonomy
    WHERE taxonomy LIKE 'pa_%'
""")
taxonomies = cur.fetchall()
if taxonomies:
    print("Found attribute taxonomies:")
    for t in taxonomies:
        cur.execute(f"""
            SELECT COUNT(DISTINCT tr.object_id) as product_count
            FROM {PREFIX}term_relationships tr
            INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy = %s
        """, (t['taxonomy'],))
        count = cur.fetchone()['product_count']
        print(f"  {t['taxonomy']}: {count} products")
else:
    print("No pa_* taxonomies found")

print("\n" + "=" * 80)

cur.close()
conn.close()
