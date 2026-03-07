"""
Test the category filter fix for T90 EV only - quick validation before full run.
"""
import asyncio
import aiohttp
import json
import re
from collections import defaultdict

WP_URL = "https://maxusvanparts.co.uk"
CK = "ck_573295ab285b1f112436b620f6bed208b5702503"
CS = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"
VIN = "LSFAM120XNA160733"
CONC = 10

def normalize(s):
    s = re.sub(r'\s*&\s*', 'and', s)
    s = re.sub(r'[,\s\-_/()]', '', s)
    return s.lower()

async def fetch_page(session, endpoint, params, sem):
    async with sem:
        async with session.get(f"{WP_URL}/wp-json/wc/v3/{endpoint}", params=params) as r:
            r.raise_for_status()
            data = await r.json()
            total_pages = int(r.headers.get("X-WP-TotalPages", 1))
            return data, total_pages

async def fetch_all_pages(session, endpoint, base_params, sem):
    first, total = await fetch_page(session, endpoint, {**base_params, "page": 1}, sem)
    if total == 1:
        return first
    tasks = [fetch_page(session, endpoint, {**base_params, "page": p}, sem)
             for p in range(2, total + 1)]
    rest = await asyncio.gather(*tasks)
    return first + [item for batch, _ in rest for item in batch]

async def main():
    print(f"\n{'='*70}")
    print(f"Testing category filter fix for VIN: {VIN}")
    print(f"{'='*70}\n")
    
    auth = aiohttp.BasicAuth(CK, CS)
    connector = aiohttp.TCPConnector(limit=CONC, ssl=True)
    timeout = aiohttp.ClientTimeout(total=180)
    sem = asyncio.Semaphore(CONC)
    
    async with aiohttp.ClientSession(auth=auth, connector=connector, timeout=timeout) as session:
        # Fetch all categories
        print("Fetching all source categories...")
        all_cats = await fetch_all_pages(session, "products/categories",
                                          {"per_page": 100, "orderby": "id"}, sem)
        print(f"  -> {len(all_cats)} categories total\n")
        
        # Build lookups
        by_id = {c["id"]: c for c in all_cats}
        children = defaultdict(list)
        for c in all_cats:
            children[c["parent"]].append(c)
        
        # Find serial category
        serial_cat = next((c for c in all_cats 
                          if c["name"].upper() == VIN.upper()), None)
        if not serial_cat:
            print(f"ERROR: Serial category not found for {VIN}")
            return
        
        print(f"Serial category: {serial_cat['name']} (ID: {serial_cat['id']})")
        
        # Build valid category ID set for this VIN
        main_cats = children.get(serial_cat["id"], [])
        valid_cat_ids = {serial_cat["id"]}
        for mc in main_cats:
            valid_cat_ids.add(mc["id"])
            for sc in children.get(mc["id"], []):
                valid_cat_ids.add(sc["id"])
        
        print(f"Valid categories for this VIN: {len(valid_cat_ids)}")
        print(f"  Main: {len(main_cats)}")
        print(f"  Sub: {len(valid_cat_ids) - len(main_cats) - 1}\n")
        
        # Fetch products in T90 EV category
        print("Fetching T90 EV products from source...")
        products = await fetch_all_pages(session, "products",
                                          {"category": serial_cat["id"], 
                                           "per_page": 100, "status": "publish"}, sem)
        print(f"  -> {len(products)} products\n")
        
        # Collect categories BEFORE filter
        cats_before_filter = defaultdict(int)
        cats_after_filter = defaultdict(int)
        products_with_extra_cats = 0
        
        for p in products:
            all_cat_ids = {c["id"] for c in p.get("categories", [])}
            filtered_cat_ids = all_cat_ids & valid_cat_ids
            
            if len(all_cat_ids) > len(filtered_cat_ids):
                products_with_extra_cats += 1
            
            # Count BEFORE filter
            for cat in p.get("categories", []):
                cats_before_filter[cat["name"]] += 1
            
            # Count AFTER filter
            for cat in p.get("categories", []):
                if cat["id"] in valid_cat_ids:
                    cats_after_filter[cat["name"]] += 1
        
        print(f"Products with bleed-through categories: {products_with_extra_cats}/{len(products)}\n")
        
        # Find categories that will be FILTERED OUT
        filtered_out = set(cats_before_filter.keys()) - set(cats_after_filter.keys())
        
        print(f"{'='*70}")
        print("CATEGORIES THAT WILL BE FILTERED OUT (bleed-through):")
        print(f"{'='*70}")
        
        if filtered_out:
            for cat_name in sorted(filtered_out):
                print(f"  ✗ {cat_name} ({cats_before_filter[cat_name]} products)")
        else:
            print("  (none - all categories are valid)")
        
        print(f"\n{'='*70}")
        print("CATEGORIES THAT WILL BE KEPT:")
        print(f"{'='*70}")
        
        main_cats_kept = set()
        for cat_name in sorted(cats_after_filter.keys()):
            # Check if it's a main category
            norm = normalize(cat_name)
            is_main = any(normalize(mc["name"]) == norm for mc in main_cats)
            if is_main:
                main_cats_kept.add(cat_name)
        
        print(f"  Total: {len(cats_after_filter)} categories")
        print(f"  Main: {len(main_cats_kept)}")
        print(f"  Sub: {len(cats_after_filter) - len(main_cats_kept)}")
        
        # Compare with expected (from source JSON)
        json_file = f"c:\\pythonstuff\\wpimportcollection\\categories_{VIN}.json"
        try:
            with open(json_file, 'r', encoding='utf-8') as f:
                source = json.load(f)
            expected_main = len(source["categories"])
            expected_sub = source["subcategory_count"]
            
            print(f"\nExpected from source JSON:")
            print(f"  Main: {expected_main}")
            print(f"  Sub: {expected_sub}")
            
            if len(main_cats_kept) == expected_main:
                print(f"\n✅ Main category count MATCHES!")
            else:
                print(f"\n❌ Main category count MISMATCH (expected {expected_main}, got {len(main_cats_kept)})")
            
            # Check for specific wrong categories
            wrong_cats = ["Fuel Storage", "Air Intake", "Emission Exhaust"]
            print(f"\nChecking for specific wrong categories:")
            for wc in wrong_cats:
                found = any(wc.lower() in cat_name.lower() for cat_name in filtered_out)
                status = "✅ Will be filtered" if found else "⚠️  Not found in bleed list"
                print(f"  {wc}: {status}")
                
        except FileNotFoundError:
            print(f"\nWARNING: {json_file} not found for comparison")

asyncio.run(main())
print("\nTest complete. If results look good, run: python fix_all_vins.py --fix")
