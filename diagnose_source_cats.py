"""
Compare which main categories are under T90 EV on source vs what's in the JSON.
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
    print(f"Diagnosing T90 EV category structure on source site...\n")
    
    auth = aiohttp.BasicAuth(CK, CS)
    connector = aiohttp.TCPConnector(limit=CONC, ssl=True)
    timeout = aiohttp.ClientTimeout(total=180)
    sem = asyncio.Semaphore(CONC)
    
    async with aiohttp.ClientSession(auth=auth, connector=connector, timeout=timeout) as session:
        # Fetch all categories
        print("Fetching categories...")
        all_cats = await fetch_all_pages(session, "products/categories",
                                          {"per_page": 100, "orderby": "id"}, sem)
        
        # Build lookups
        children = defaultdict(list)
        for c in all_cats:
            children[c["parent"]].append(c)
        
        # Find serial category
        serial_cat = next((c for c in all_cats 
                          if c["name"].upper() == VIN.upper()), None)
        
        print(f"Serial: {serial_cat['name']} (ID: {serial_cat['id']})\n")
        
        # Get main categories (direct children of serial)
        main_cats_live = children.get(serial_cat["id"], [])
        
        print(f"Main categories LIVE on source (direct children of serial {serial_cat['id']}):")
        print(f"  Total: {len(main_cats_live)}\n")
        
        for mc in sorted(main_cats_live, key=lambda x: x['name']):
            sub_count = len(children.get(mc["id"], []))
            print(f"  [{mc['id']:5d}] {mc['name']:<50} ({sub_count} subs, {mc['count']} products)")
        
        # Load expected from JSON
        json_file = f"c:\\pythonstuff\\wpimportcollection\\categories_{VIN}.json"
        with open(json_file, 'r', encoding='utf-8') as f:
            source_json = json.load(f)
        
        print(f"\n\nMain categories in JSON (fetched {source_json.get('serial_category_name')}):")
        print(f"  Total: {len(source_json['categories'])}\n")
        
        json_names = {mc['name'] for mc in source_json['categories']}
        live_names = {mc['name'] for mc in main_cats_live}
        
        # Compare
        print(f"{'='*70}")
        print("COMPARISON:")
        print(f"{'='*70}")
        
        extra_on_live = live_names - json_names
        missing_from_live = json_names - live_names
        
        if extra_on_live:
            print(f"\nExtra on LIVE (not in JSON - possibly wrong):")
            for name in sorted(extra_on_live):
                mc = next(c for c in main_cats_live if c['name'] == name)
                print(f"  + {name} (ID: {mc['id']}, {mc['count']} products)")
        
        if missing_from_live:
            print(f"\nMissing from LIVE (in JSON but not found):")
            for name in sorted(missing_from_live):
                print(f"  - {name}")
        
        if not extra_on_live and not missing_from_live:
            print("\n✅ Perfect match!")
        
        # Check for the specific problem categories
        print(f"\n{'='*70}")
        print("SPECIFIC CHECKS:")
        print(f"{'='*70}")
        
        problem_cats = ["Fuel Storage & Handling", "Air Intake System", 
                       "Emission Exhaust System", "Power Energy Storage & Link Wire"]
        
        for pcat in problem_cats:
            norm = normalize(pcat)
            found_live = any(normalize(mc['name']) == norm for mc in main_cats_live)
            found_json = any(normalize(mc['name']) == norm for mc in source_json['categories'])
            
            print(f"\n'{pcat}':")
            print(f"  In LIVE source: {found_live}")
            print(f"  In JSON: {found_json}")
            
            if found_live:
                mc = next((c for c in main_cats_live if normalize(c['name']) == norm), None)
                if mc:
                    print(f"  -> ID: {mc['id']}, Products: {mc['count']}")
                    # Check if products in this category are ACTUALLY under the T90 EV serial
                    print(f"  -> This IS a direct child of serial {serial_cat['id']}")

asyncio.run(main())
