#!/usr/bin/env python3
"""
Find products still linked to Air Intake System or Power Generation
"""
import mysql.connector

DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"
T90_EV_VIN = "LSFAM120XNA160733"

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

wrong_cats = ['Air Intake System', 'Power Generation']

for cat_name in wrong_cats:
    print(f"\n{'='*70}")
    print(f"Products with: {cat_name}")
    print('='*70)
    
    cur.execute(f"""
        SELECT DISTINCT 
            p.ID,
            p.post_title,
            pm_sku.meta_value as sku,
            p.post_type
        FROM {PREFIX}posts p
        INNER JOIN {PREFIX}postmeta pm_sku ON p.ID = pm_sku.post_id 
            AND pm_sku.meta_key = '_sku'
        LEFT JOIN {PREFIX}postmeta pm_var ON p.ID = pm_var.post_id 
            AND pm_var.meta_key = 'attribute_pa_variant'
        INNER JOIN {PREFIX}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
            AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
            AND svm.vin = %s
        INNER JOIN {PREFIX}term_relationships tr ON p.ID = tr.object_id
        INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            AND tt.taxonomy = 'product_cat'
        INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
            AND t.name = %s
        WHERE p.post_type IN ('product', 'product_variation')
          AND p.post_status = 'publish'
        ORDER BY p.post_title
    """, (T90_EV_VIN, cat_name))
    
    products = cur.fetchall()
    
    if products:
        print(f"\nFound {len(products)} products:")
        for p in products:
            print(f"  {p['sku']}: {p['post_title']} [{p['post_type']}]")
    else:
        print(f"\n[OK] No products found with this category")

conn.close()
