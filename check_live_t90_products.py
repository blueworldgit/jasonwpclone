#!/usr/bin/env python3
"""
Check if the 10 problem products actually exist on live T90 EV page
"""
import requests
from bs4 import BeautifulSoup
import time

SOURCE_URL = "https://maxusvanparts.co.uk"
SOURCE_CK = "ck_573295ab285b1f112436b620f6bed208b5702503"
SOURCE_CS = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"

# VIN for T90 EV from our mapping
T90_EV_VIN = "LSFAM120XNA160733"

# The 10 problematic SKUs
problem_skus = {
    'C00266185': 'BATTERY BRACKET-REAR',
    'B00005351': 'BOLT-AIR CLEANER OUTLET HOSE BRACKET',
    'B00004852': 'BOLT-EVP BRACKET',
    'B00006046': 'BOLT-HIGH PRESSURE PUMP',
    'B00004213': 'BOLT-TURBINE TO INTERCOOLER PIPE BRACKET',
    'C00143826': 'BOLT/SCREW-FRONT MUDGUARD',
    'C00320368': 'COVER-CDU BEAUTY',
    'C00205188': 'COVER-COUNTER SHAFT BEARING',
    'B00005445': 'NUT-POWERTRAIN CONTROL MODULE UPPER BRACKET',
    'B90001243': 'SEAL-O RING'
}

print("=" * 80)
print("CHECKING LIVE SITE FOR T90 EV PRODUCTS")
print("=" * 80)
print(f"Source: {SOURCE_URL}")
print(f"VIN: {T90_EV_VIN}")
print()

# Step 1: Find the T90 EV serial category
print("Step 1: Finding T90 EV serial category...")
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
print(f"Found: {serial_cat['name']} (ID: {serial_cat_id})")
print()

# Step 2: Fetch all products in this category
print("Step 2: Fetching ALL products for T90 EV from live site...")
all_products = []
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
    
    all_products.extend(products)
    print(f"  Page {page}: {len(products)} products")
    page += 1
    time.sleep(0.5)

print(f"\nTotal products on live T90 EV: {len(all_products)}")
print()

# Step 3: Check which of our 10 problem SKUs are on live site
print("=" * 80)
print("CHECKING EACH PROBLEM SKU")
print("=" * 80)
print()

# Build lookup by base SKU (strip hash)
live_products_by_base_sku = {}
for prod in all_products:
    sku = prod.get('sku', '').strip()
    if sku:
        base_sku = sku.rsplit('-', 1)[0] if '-' in sku else sku
        if base_sku not in live_products_by_base_sku:
            live_products_by_base_sku[base_sku] = []
        live_products_by_base_sku[base_sku].append({
            'sku': sku,
            'name': prod['name'],
            'categories': [c['name'] for c in prod.get('categories', [])]
        })

found_on_live = []
not_found_on_live = []

for sku, title in problem_skus.items():
    if sku in live_products_by_base_sku:
        variants = live_products_by_base_sku[sku]
        found_on_live.append(sku)
        print(f"[FOUND] {sku} - {title}")
        print(f"  Variants on live site: {len(variants)}")
        for v in variants[:3]:  # Show first 3
            cats = [c for c in v['categories'] if c.lower() not in ['imageupdated', 'priceupdated', T90_EV_VIN]]
            print(f"    - {v['sku']}: {v['name'][:50]}")
            print(f"      Cats: {', '.join(cats[:5])}")
        if len(variants) > 3:
            print(f"    ... and {len(variants)-3} more variants")
        print()
    else:
        not_found_on_live.append(sku)
        print(f"[NOT FOUND] {sku} - {title}")
        print(f"  This SKU does NOT appear on live T90 EV page!")
        print()

print("=" * 80)
print("SUMMARY")
print("=" * 80)
print()
print(f"Found on live site: {len(found_on_live)}/10")
print(f"NOT found on live: {len(not_found_on_live)}/10")
print()

if not_found_on_live:
    print("SKUs NOT on live site (should be removed from local mapping):")
    for sku in not_found_on_live:
        print(f"  - {sku}: {problem_skus[sku]}")
    print()

if found_on_live:
    print("SKUs on live site (categories need fixing):")
    for sku in found_on_live:
        print(f"  - {sku}: {problem_skus[sku]}")
