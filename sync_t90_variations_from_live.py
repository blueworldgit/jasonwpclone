#!/usr/bin/env python3
"""
Sync T90 EV product variations from live site (titles + categories)

Live site uses hash-suffixed SKUs (B00005351-39BB)
Local uses WooCommerce variations (product_variation post type)

This script:
1. Fetches all T90 EV products from live site
2. Matches to local products by base SKU
3. Updates local variation titles and categories to match live
"""
import mysql.connector
import requests
import time
import sys

# Config
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

SOURCE_URL = "https://maxusvanparts.co.uk"
SOURCE_CK = "ck_573295ab285b1f112436b620f6bed208b5702503"
SOURCE_CS = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"

T90_EV_VIN = "LSFAM120XNA160733"
DRY_RUN = "--fix" not in sys.argv

print("=" * 80)
print("SYNC T90 EV VARIATIONS FROM LIVE SITE")
print("=" * 80)
print(f"Mode: {'DRY-RUN' if DRY_RUN else '*** APPLYING FIXES ***'}")
print(f"Live site: {SOURCE_URL}")
print(f"VIN: {T90_EV_VIN}")
print()

# Step 1: Fetch all T90 EV products from live site
print("Step 1: Fetching T90 EV products from live site...")
print()

response = requests.get(
    f"{SOURCE_URL}/wp-json/wc/v3/products/categories",
    auth=(SOURCE_CK, SOURCE_CS),
    params={"per_page": 100, "search": T90_EV_VIN},
    timeout=30
)
cats = response.json()
serial_cat = next((c for c in cats if T90_EV_VIN in c.get('name', '')), None)

if not serial_cat:
    print(f"ERROR: Could not find serial category for {T90_EV_VIN}")
    exit(1)

serial_cat_id = serial_cat['id']
print(f"Found serial category: {serial_cat['name']} (ID: {serial_cat_id})")
print()

# Fetch all products
live_products = {}  # base_sku -> list of {hash_sku, title, categories}
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
        hash_sku = p.get('sku', '').strip()
        if not hash_sku:
            continue
        
        # Get base SKU (strip hash)
        base_sku = hash_sku.rsplit('-', 1)[0] if '-' in hash_sku else hash_sku
        
        if base_sku not in live_products:
            live_products[base_sku] = []
        
        # Get category names (exclude meta categories)
        cat_names = [c['name'] for c in p.get('categories', []) 
                     if c['name'].lower() not in ['imageupdated', 'priceupdated', T90_EV_VIN.lower()]]
        
        live_products[base_sku].append({
            'hash_sku': hash_sku,
            'title': p['name'],
            'category_names': cat_names
        })
    
    print(f"  Page {page}: {len(products)} products")
    page += 1
    time.sleep(0.5)

print(f"\nTotal base SKUs from live: {len(live_products)}")
print()

# Step 2: Get local products with wrong categories
print("=" * 80)
print("Step 2: Finding local products with wrong categories...")
print("=" * 80)
print()

conn = mysql.connector.connect(**DB_CFG)
cur = conn.cursor(dictionary=True)

wrong_cats_names = [
    'Air Intake System',
    'Emission Exhaust System',
    'Fuel Storage & Handling',
    'Power Energy Storage & Link Wire',
    'Power Generation'
]

placeholders = ','.join(['%s'] * len(wrong_cats_names))

cur.execute(f"""
    SELECT DISTINCT 
        p.ID as product_id,
        p.post_title,
        p.post_type,
        pm_sku.meta_value as sku,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as wrong_categories
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
""", (T90_EV_VIN,) + tuple(wrong_cats_names))

local_products = cur.fetchall()
print(f"Found {len(local_products)} local products with wrong categories")
print()

# Step 3: Match and update
print("=" * 80)
print("Step 3: Matching and updating...")
print("=" * 80)
print()

updates_made = 0
skipped = 0

for local in local_products:
    local_id = local['product_id']
    local_sku = local['sku']
    local_title = local['post_title']
    local_type = local['post_type']
    
    # Get base SKU (might already be base, or might have hash)
    base_sku = local_sku.rsplit('-', 1)[0] if '-' in local_sku else local_sku
    
    if base_sku not in live_products:
        print(f"[SKIP] {local_sku} - No match on live site")
        skipped += 1
        continue
    
    live_variants = live_products[base_sku]
    
    print(f"\n[{local_type}] {local_sku}: {local_title}")
    print(f"  Wrong categories: {local['wrong_categories']}")
    print(f"  Live site has {len(live_variants)} variant(s):")
    
    # Pick best match (first one for now, or exact hash match if available)
    best_match = live_variants[0]
    for variant in live_variants:
        if variant['hash_sku'] == local_sku:
            best_match = variant
            break
    
    print(f"    -> Using: {best_match['hash_sku']}")
    print(f"       Title: {best_match['title']}")
    print(f"       Categories: {', '.join(best_match['category_names'][:5])}")
    
    if DRY_RUN:
        print(f"  [DRY-RUN] Would update title and categories")
        updates_made += 1
        continue
    
    # Apply updates
    
    # 1. Update title
    cur.execute(f"""
        UPDATE {PREFIX}posts 
        SET post_title = %s,
            post_name = %s
        WHERE ID = %s
    """, (best_match['title'], best_match['title'].lower().replace(' ', '-'), local_id))
    
    # 2. Remove wrong categories
    cur.execute(f"""
        DELETE tr FROM {PREFIX}term_relationships tr
        INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
        WHERE tr.object_id = %s
          AND tt.taxonomy = 'product_cat'
          AND t.name IN ({placeholders})
    """, (local_id,) + tuple(wrong_cats_names))
    
    # 3. Add correct categories
    for cat_name in best_match['category_names']:
        # Find or create category term
        cur.execute(f"""
            SELECT t.term_id, tt.term_taxonomy_id
            FROM {PREFIX}terms t
            INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id
            WHERE t.name = %s AND tt.taxonomy = 'product_cat'
            LIMIT 1
        """, (cat_name,))
        
        result = cur.fetchone()
        if result:
            # Check if relationship already exists
            cur.execute(f"""
                SELECT 1 FROM {PREFIX}term_relationships
                WHERE object_id = %s AND term_taxonomy_id = %s
            """, (local_id, result['term_taxonomy_id']))
            
            if not cur.fetchone():
                # Add relationship
                cur.execute(f"""
                    INSERT INTO {PREFIX}term_relationships (object_id, term_taxonomy_id, term_order)
                    VALUES (%s, %s, 0)
                """, (local_id, result['term_taxonomy_id']))
    
    conn.commit()
    print(f"  [UPDATED] Title and categories synced from live")
    updates_made += 1

conn.close()

print()
print("=" * 80)
print("SUMMARY")
print("=" * 80)
print()
print(f"Products processed: {len(local_products)}")
print(f"Updates {'planned' if DRY_RUN else 'applied'}: {updates_made}")
print(f"Skipped (not on live): {skipped}")
print()

if DRY_RUN:
    print("This was a DRY-RUN. To apply changes, run:")
    print("  python sync_t90_variations_from_live.py --fix")
else:
    print("Updates applied successfully!")
    print("Verify results by checking T90 EV products on local site")
