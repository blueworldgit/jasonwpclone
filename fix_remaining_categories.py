#!/usr/bin/env python3
"""
Fix the remaining 10% - Enhanced SKU matching with title comparison.

When multiple source products share the same base SKU, use title similarity
to pick the correct one instead of merging all their categories.
"""
import mysql.connector
import requests
import time
from difflib import SequenceMatcher
from collections import defaultdict

# Config
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

# Source API
SOURCE_URL = "https://maxusvanparts.co.uk"
SOURCE_CK = "ck_573295ab285b1f112436b620f6bed208b5702503"
SOURCE_CS = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"

VIN = "LSFAM120XNA160733"  # T90 EV
DRY_RUN = "--fix" not in __import__('sys').argv

print("=" * 70)
print("Enhanced Category Fix - Title-Based SKU Matching")
print("=" * 70)
print(f"Mode: {'DRY-RUN' if DRY_RUN else '*** APPLYING FIXES ***'}")
print(f"Target: T90 EV ({VIN})")
print()

def similarity(a, b):
    """Calculate similarity ratio between two strings."""
    return SequenceMatcher(None, a.lower(), b.lower()).ratio()

# Step 1: Get source categories for T90 EV
print("STEP 1: Fetching source products for T90 EV...")
print()

# Find serial category
response = requests.get(
    f"{SOURCE_URL}/wp-json/wc/v3/products/categories",
    auth=(SOURCE_CK, SOURCE_CS),
    params={"per_page": 100, "search": VIN},
    timeout=30
)
cats = response.json()
serial_cat = next((c for c in cats if VIN in c.get('name', '')), None)

if not serial_cat:
    print(f"ERROR: Could not find serial category for {VIN}")
    exit(1)

serial_cat_id = serial_cat['id']
print(f"Found serial category: {serial_cat['name']} (ID {serial_cat_id})")

# Fetch all products under this category
source_products = {}  # SKU -> list of {id, title, categories}
page = 1
while True:
    response = requests.get(
        f"{SOURCE_URL}/wp-json/wc/v3/products",
        auth=(SOURCE_CK, SOURCE_CS),
        params={
            "per_page": 100,
            "page": page,
            "category": serial_cat_id,
            "status": "publish"
        },
        timeout=30
    )
    
    if response.status_code != 200:
        break
    
    products = response.json()
    if not products:
        break
    
    for p in products:
        sku = p.get('sku', '').strip()
        if not sku:
            continue
        
        # Strip hash to get base SKU
        base_sku = sku.rsplit('-', 1)[0] if '-' in sku else sku
        
        if base_sku not in source_products:
            source_products[base_sku] = []
        
        source_products[base_sku].append({
            'id': p['id'],
            'sku': sku,  # Full SKU with hash
            'title': p['name'],
            'categories': [c['id'] for c in p.get('categories', [])]
        })
    
    print(f"  Fetched page {page}: {len(products)} products")
    page += 1
    time.sleep(0.5)

print(f"\nTotal base SKUs: {len(source_products)}")
multi_match = {k: v for k, v in source_products.items() if len(v) > 1}
print(f"Base SKUs with multiple variants: {len(multi_match)}")
print()

# Show examples
if multi_match:
    print("Examples of multi-variant SKUs:")
    for base_sku, variants in list(multi_match.items())[:5]:
        print(f"  {base_sku}:")
        for v in variants:
            print(f"    - {v['sku']}: {v['title'][:50]}")
    print()

# Step 2: Get problematic local products (those with wrong categories)
print("=" * 70)
print("STEP 2: Finding local products with wrong categories...")
print("=" * 70)

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

wrong_cats_names = [
    'Air Intake System',
    'Emission Exhaust System',
    'Fuel Storage & Handling',
    'Power Energy Storage & Link Wire',
    'Power Generation'
]

# Get term IDs for wrong categories
placeholders = ','.join(['%s'] * len(wrong_cats_names))
cur.execute(f"""
    SELECT t.term_id, t.name
    FROM {PREFIX}terms t
    INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'product_cat'
      AND t.name IN ({placeholders})
""", tuple(wrong_cats_names))

wrong_cat_ids = {r['term_id']: r['name'] for r in cur.fetchall()}
print(f"Wrong category IDs: {wrong_cat_ids}")
print()

# Get products with these wrong categories that are mapped to T90 EV
cur.execute(f"""
    SELECT DISTINCT 
        p.ID as product_id,
        p.post_title,
        pm_sku.meta_value as sku,
        GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as wrong_cats
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
    WHERE p.post_type IN ('product', 'product_variation')
      AND p.post_status = 'publish'
      AND t.name IN ({placeholders})
    GROUP BY p.ID
    ORDER BY p.post_title
""", (VIN,) + tuple(wrong_cats_names))

problem_products = cur.fetchall()
print(f"Found {len(problem_products)} products with wrong categories:")
for prod in problem_products:
    print(f"  - {prod['post_title'][:60]} (SKU: {prod['sku']})")
    print(f"    Wrong cats: {prod['wrong_cats']}")
print()

# Step 3: Fix each product using title matching
print("=" * 70)
print("STEP 3: Fixing products with title-based matching...")
print("=" * 70)
print()

fixes_applied = 0
fixes_needed = 0

for prod in problem_products:
    local_sku = prod['sku']
    local_title = prod['post_title']
    local_id = prod['product_id']
    
    # Get base SKU
    base_sku = local_sku.rsplit('-', 1)[0] if '-' in local_sku else local_sku
    
    if base_sku not in source_products:
        print(f"⚠ {local_title[:50]}")
        print(f"  SKU {base_sku} not found in source")
        continue
    
    source_variants = source_products[base_sku]
    
    if len(source_variants) == 1:
        # Only one match, use it
        best_match = source_variants[0]
        print(f"✓ {local_title[:50]}")
        print(f"  Single source match: {best_match['title'][:50]}")
    else:
        # Multiple matches - use title similarity
        similarities = []
        for variant in source_variants:
            sim = similarity(local_title, variant['title'])
            similarities.append((sim, variant))
        
        similarities.sort(reverse=True, key=lambda x: x[0])
        best_match = similarities[0][1]
        best_sim = similarities[0][0]
        
        print(f"🔍 {local_title[:50]}")
        print(f"  {len(source_variants)} source variants found:")
        for sim, var in similarities[:3]:
            marker = "✓" if var == best_match else " "
            print(f"    {marker} {sim:.2f}: {var['title'][:50]}")
        
        if best_sim < 0.5:
            print(f"  ⚠ Low similarity ({best_sim:.2f}) - skipping")
            continue
    
    # Get correct categories for this match
    correct_cat_ids = best_match['categories']
    
    # Check if any are wrong categories
    has_wrong_cats = any(cat_id in wrong_cat_ids for cat_id in correct_cat_ids)
    
    if not has_wrong_cats:
        fixes_needed += 1
        print(f"  → Should remove wrong categories (source has {len(correct_cat_ids)} correct cats)")
        
        if not DRY_RUN:
            # Remove wrong categories from this product
            wrong_ids_str = ','.join(str(id) for id in wrong_cat_ids.keys())
            cur.execute(f"""
                DELETE FROM {PREFIX}term_relationships
                WHERE object_id = %s
                  AND term_taxonomy_id IN (
                    SELECT term_taxonomy_id FROM {PREFIX}term_taxonomy
                    WHERE term_id IN ({wrong_ids_str})
                      AND taxonomy = 'product_cat'
                  )
            """, (local_id,))
            deleted = cur.rowcount
            fixes_applied += 1
            print(f"  ✅ Removed {deleted} wrong category relationships")
    else:
        print(f"  → Source also has wrong categories - keeping as is")
    
    print()

if not DRY_RUN and fixes_applied > 0:
    conn.commit()
    print(f"\n✅ Applied {fixes_applied} fixes")
    
    # Recalculate category counts
    print("\nRecalculating category counts...")
    cur.execute(f"""
        UPDATE {PREFIX}term_taxonomy tt
        SET count = (
            SELECT COUNT(DISTINCT tr.object_id)
            FROM {PREFIX}term_relationships tr
            INNER JOIN {PREFIX}posts p ON tr.object_id = p.ID
            WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
              AND p.post_type IN ('product', 'product_variation')
              AND p.post_status = 'publish'
        )
        WHERE tt.taxonomy = 'product_cat'
    """)
    conn.commit()
    print("✅ Category counts updated")

conn.close()

print("\n" + "=" * 70)
print("SUMMARY")
print("=" * 70)
print(f"Mode: {'DRY-RUN' if DRY_RUN else 'APPLIED'}")
print(f"Problem products found: {len(problem_products)}")
print(f"Fixes needed: {fixes_needed}")
print(f"Fixes applied: {fixes_applied}")
print()

if DRY_RUN:
    print("Run with --fix to apply changes")
else:
    print("✅ All fixes applied! Run verify_t90_categories.py to check results.")
