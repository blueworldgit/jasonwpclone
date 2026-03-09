#!/usr/bin/env python3
"""
Check which of our 13 problem products were excluded by variant fix
"""
import mysql.connector

DB_CONFIG = {
    'host': 'localhost',
    'port': 3306,
    'user': 'root',
    'password': '',
    'database': 'maxussql',
    'charset': 'utf8mb4'
}

conn = mysql.connector.connect(**DB_CONFIG)
cur = conn.cursor(dictionary=True)

problem_skus = [
    'C00266185', 'B00004683', 'B00005351', 'B00004852', 'C00054625',
    'C00087976', 'B00003512', 'C00143826', 'C00053812', 'C00320368',
    'C00205188', 'B00005445', 'B90001243'
]

print("=" * 80)
print("CHECKING WHICH PROBLEM PRODUCTS WERE EXCLUDED")
print("=" * 80)
print()

for sku in problem_skus:
    # Check without variant join
    cur.execute("""
        SELECT p.ID, p.post_title, pm_sku.meta_value as sku,
               pm_var.meta_value as variant_attr
        FROM wp_posts p
        INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
        INNER JOIN wp_sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
        WHERE svm.vin = 'LSFAM120XNA160733'
            AND pm_sku.meta_value = %s
            AND p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
        LIMIT 1
    """, (sku,))
    without_variant = cur.fetchone()
    
    # Check with variant join
    cur.execute("""
        SELECT p.ID, p.post_title, pm_sku.meta_value as sku,
               pm_var.meta_value as variant_attr
        FROM wp_posts p
        INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
        INNER JOIN wp_sku_vin_mapping svm 
            ON pm_sku.meta_value = svm.sku
            AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
        WHERE svm.vin = 'LSFAM120XNA160733'
            AND pm_sku.meta_value = %s
            AND p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
        LIMIT 1
    """, (sku,))
    with_variant = cur.fetchone()
    
    if without_variant and not with_variant:
        print(f"❌ EXCLUDED: {sku}")
        print(f"   Title: {without_variant['post_title']}")
        print(f"   Variant: {without_variant['variant_attr'] or 'NULL'}")
        
        # Check what's in mapping table
        cur.execute("""
            SELECT DISTINCT variant_attribute
            FROM wp_sku_vin_mapping
            WHERE sku = %s AND vin = 'LSFAM120XNA160733'
        """, (sku,))
        mapping_variants = [r['variant_attribute'] for r in cur.fetchall()]
        print(f"   Mapping has variants: {mapping_variants}")
        print()
    elif with_variant:
        print(f"✓ INCLUDED: {sku} - {with_variant['post_title']}")

print()
print("=" * 80)
print("CHECKING WRONG CATEGORIES ON REMAINING 9 PRODUCTS")
print("=" * 80)
print()

# Get categories for remaining products
for sku in problem_skus:
    cur.execute("""
        SELECT p.ID, p.post_title, pm_sku.meta_value as sku,
               GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as categories
        FROM wp_posts p
        INNER JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN wp_postmeta pm_var ON p.ID = pm_var.post_id AND pm_var.meta_key = 'attribute_pa_variant'
        INNER JOIN wp_sku_vin_mapping svm 
            ON pm_sku.meta_value = svm.sku
            AND (svm.variant_attribute IS NULL OR svm.variant_attribute = '' OR svm.variant_attribute = pm_var.meta_value)
        LEFT JOIN wp_term_relationships tr ON p.ID = tr.object_id
        LEFT JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat'
        LEFT JOIN wp_terms t ON tt.term_id = t.term_id
        WHERE svm.vin = 'LSFAM120XNA160733'
            AND pm_sku.meta_value = %s
            AND p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
        GROUP BY p.ID
        LIMIT 1
    """, (sku,))
    result = cur.fetchone()
    if result:
        print(f"{sku}: {result['post_title']}")
        print(f"  Categories: {result['categories']}")
        print()

conn.close()
