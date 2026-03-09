#!/usr/bin/env python3
"""
Fast async sync of vehicle product variations from live site
Usage: python sync_vehicle_fast.py <VIN> [--fix]
"""
import mysql.connector
import asyncio
import aiohttp
import sys
from typing import List, Dict

if len(sys.argv) < 2:
    print("Usage: python sync_vehicle_fast.py <VIN> [--fix]")
    print("\nExamples:")
    print("  python sync_vehicle_fast.py LSFAM120XNA160733")
    print("  python sync_vehicle_fast.py LSH14C4C5NA129710 --fix")
    sys.exit(1)

VEHICLE_VIN = sys.argv[1]
DRY_RUN = "--fix" not in sys.argv

# Config
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

SOURCE_URL = "https://maxusvanparts.co.uk"
SOURCE_CK = "ck_573295ab285b1f112436b620f6bed208b5702503"
SOURCE_CS = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"

# Async settings
CONCURRENT_REQUESTS = 5
BATCH_SIZE = 100

print("=" * 80)
print(f"FAST ASYNC SYNC - {VEHICLE_VIN} VARIATIONS FROM LIVE SITE")
print("=" * 80)
print(f"Mode: {'DRY-RUN' if DRY_RUN else '*** APPLYING FIXES ***'}")
print(f"Concurrency: {CONCURRENT_REQUESTS} parallel requests")
print()

async def fetch_products_page(session: aiohttp.ClientSession, page: int, category_id: int) -> List[Dict]:
    """Fetch a single page of products"""
    auth = aiohttp.BasicAuth(SOURCE_CK, SOURCE_CS)
    url = f"{SOURCE_URL}/wp-json/wc/v3/products"
    params = {
        "per_page": BATCH_SIZE,
        "page": page,
        "category": category_id,
        "status": "publish"
    }
    
    try:
        async with session.get(url, params=params, auth=auth, timeout=30) as response:
            if response.status == 200:
                return await response.json()
            return []
    except Exception as e:
        print(f"  [ERROR] Page {page} failed: {e}")
        return []

async def fetch_all_products(category_id: int) -> Dict[str, List[Dict]]:
    """Fetch all products using async batch requests"""
    print("Fetching products from live site (async)...")
    
    connector = aiohttp.TCPConnector(limit=CONCURRENT_REQUESTS)
    timeout = aiohttp.ClientTimeout(total=300)
    
    async with aiohttp.ClientSession(connector=connector, timeout=timeout) as session:
        products_page1 = await fetch_products_page(session, 1, category_id)
        
        if not products_page1:
            return {}
        
        print(f"  Page 1: {len(products_page1)} products")
        
        all_products_raw = products_page1.copy()
        current_page = 2
        
        while True:
            page_range = range(current_page, current_page + CONCURRENT_REQUESTS)
            tasks = [fetch_products_page(session, page, category_id) for page in page_range]
            results = await asyncio.gather(*tasks)
            
            pages_with_data = 0
            for page_num, products in zip(page_range, results):
                if products:
                    print(f"  Page {page_num}: {len(products)} products")
                    all_products_raw.extend(products)
                    pages_with_data += 1
            
            if pages_with_data == 0:
                break
                
            current_page += CONCURRENT_REQUESTS
            
            if current_page > 50:
                print("  [WARNING] Reached page limit (50), stopping")
                break
    
    print(f"\nTotal products fetched: {len(all_products_raw)}")
    print()
    
    # Organize by base SKU
    live_products = {}
    for p in all_products_raw:
        hash_sku = p.get('sku', '').strip()
        if not hash_sku:
            continue
        
        base_sku = hash_sku.rsplit('-', 1)[0] if '-' in hash_sku else hash_sku
        
        if base_sku not in live_products:
            live_products[base_sku] = []
        
        cat_names = [c['name'] for c in p.get('categories', []) 
                     if c['name'].lower() not in ['imageupdated', 'priceupdated', VEHICLE_VIN.lower()]]
        
        live_products[base_sku].append({
            'hash_sku': hash_sku,
            'title': p['name'],
            'category_names': cat_names
        })
    
    print(f"Unique base SKUs: {len(live_products)}")
    return live_products

async def main():
    # Step 1: Find vehicle category
    print(f"Step 1: Finding {VEHICLE_VIN} serial category...")
    
    auth = aiohttp.BasicAuth(SOURCE_CK, SOURCE_CS)
    async with aiohttp.ClientSession() as session:
        async with session.get(
            f"{SOURCE_URL}/wp-json/wc/v3/products/categories",
            params={"per_page": 100, "search": VEHICLE_VIN},
            auth=auth,
            timeout=30
        ) as response:
            cats = await response.json()
    
    serial_cat = next((c for c in cats if VEHICLE_VIN in c.get('name', '')), None)
    if not serial_cat:
        print(f"ERROR: Could not find serial category for {VEHICLE_VIN}")
        return
    
    serial_cat_id = serial_cat['id']
    print(f"Found: {serial_cat['name']} (ID: {serial_cat_id})")
    print()
    
    # Step 2: Fetch all products (async)
    print("=" * 80)
    print("Step 2: Fetching all products...")
    print("=" * 80)
    print()
    
    live_products = await fetch_all_products(serial_cat_id)
    
    # Step 3: Get local products with wrong categories
    print("=" * 80)
    print("Step 3: Finding local products with wrong categories...")
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
    """, (VEHICLE_VIN,) + tuple(wrong_cats_names))
    
    local_products = cur.fetchall()
    print(f"Found {len(local_products)} local products with wrong categories")
    print()
    
    if len(local_products) == 0:
        print("No products need updating!")
        conn.close()
        return
    
    # Step 4: Match and update
    print("=" * 80)
    print("Step 4: Matching and updating...")
    print("=" * 80)
    print()
    
    updates_made = 0
    skipped = 0
    
    for local in local_products:
        local_id = local['product_id']
        local_sku = local['sku']
        local_title = local['post_title']
        local_type = local['post_type']
        
        base_sku = local_sku.rsplit('-', 1)[0] if '-' in local_sku else local_sku
        
        if base_sku not in live_products:
            print(f"[SKIP] {local_sku} - No match on live site")
            skipped += 1
            continue
        
        live_variants = live_products[base_sku]
        
        print(f"\n[{local_type}] {local_sku}: {local_title}")
        print(f"  Wrong categories: {local['wrong_categories']}")
        print(f"  Live site has {len(live_variants)} variant(s):")
        
        # Pick best match
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
        
        # 2. Remove wrong categories (and their subcategories)
        cur.execute(f"""
            SELECT t.term_id
            FROM {PREFIX}terms t
            INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_cat'
              AND t.name IN ({placeholders})
        """, tuple(wrong_cats_names))
        
        wrong_cat_ids = [r['term_id'] for r in cur.fetchall()]
        
        if wrong_cat_ids:
            id_placeholders = ','.join(['%s'] * len(wrong_cat_ids))
            cur.execute(f"""
                DELETE tr FROM {PREFIX}term_relationships tr
                INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = %s
                  AND tt.taxonomy = 'product_cat'
                  AND (tt.term_id IN ({id_placeholders}) OR tt.parent IN ({id_placeholders}))
            """, (local_id,) + tuple(wrong_cat_ids) + tuple(wrong_cat_ids))
        
        # 3. Add correct categories
        for cat_name in best_match['category_names']:
            cur.execute(f"""
                SELECT t.term_id, tt.term_taxonomy_id
                FROM {PREFIX}terms t
                INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id
                WHERE t.name = %s AND tt.taxonomy = 'product_cat'
                LIMIT 1
            """, (cat_name,))
            
            result = cur.fetchone()
            if result:
                cur.execute(f"""
                    SELECT 1 FROM {PREFIX}term_relationships
                    WHERE object_id = %s AND term_taxonomy_id = %s
                """, (local_id, result['term_taxonomy_id']))
                
                if not cur.fetchone():
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
        print(f"  python sync_vehicle_fast.py {VEHICLE_VIN} --fix")
    else:
        print("Updates applied successfully!")

if __name__ == "__main__":
    asyncio.run(main())
