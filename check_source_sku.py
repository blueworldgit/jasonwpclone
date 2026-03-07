"""
Check if B00003507 exists on source site and which categories it has.
"""
import requests
from requests.auth import HTTPBasicAuth

WP_URL = "https://maxusvanparts.co.uk"
CK = "ck_573295ab285b1f112436b620f6bed208b5702503"
CS = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"

TARGET_SKU = "B00003507"

# Search for ALL products in T90 EV category and look for this SKU
params = {
    "category": 4408,  # T90 EV serial category
    "per_page": 100,
    "page": 1
}

found = False
page = 1

while page <= 15:  # Check first 15 pages (1500 products)
    params["page"] = page
    r = requests.get(
        f"{WP_URL}/wp-json/wc/v3/products",
        params=params,
        auth=HTTPBasicAuth(CK, CS),
        timeout=30
    )
    
    if r.status_code != 200:
        print(f"Error on page {page}: {r.status_code}")
        break
    
    products = r.json()
    if not products:
        break
    
    print(f"Page {page}: {len(products)} products...", end="\r")
    
    for p in products:
        # Check original_sku meta
        orig_sku = None
        for m in p.get('meta_data', []):
            if m['key'] == 'original_sku':
                orig_sku = m['value']
                break
        
        # Also check direct SKU and stripped SKU
        sku = p.get('sku', '').strip()
        stripped = sku.rsplit("-", 1)[0].strip() if "-" in sku else sku
        
        if TARGET_SKU in [orig_sku, sku, stripped]:
            found = True
            print(f"\n\nFOUND on page {page}!")
            print(f"Product ID: {p['id']}")
            print(f"Name: {p['name']}")
            print(f"WC SKU: {sku}")
            print(f"Original SKU: {orig_sku}")
            print(f"Stripped SKU: {stripped}")
            print(f"Categories ({len(p.get('categories', []))}):")
            for cat in p.get('categories', []):
                print(f"  - [{cat['id']}] {cat['name']}")
            print()
            break
    
    if found:
        break
    
    page += 1

if not found:
    print(f"\n\nNOT FOUND: {TARGET_SKU} does not exist in T90 EV category on source site")
    print("This SKU should have been REMOVED from sku_vin_mapping but wasn't!")
