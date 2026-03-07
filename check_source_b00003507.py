#!/usr/bin/env python3
import requests

# Source site API credentials
url = 'https://maxusvanparts.co.uk/wp-json/wc/v3/products'
auth = ('ck_573295ab285b1f112436b620f6bed208b5702503', 'cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109')

# Search for products with B00003507 in SKU or meta
print("Searching source site for B00003507...\n")

page = 1
found = []

while page < 100:  # Safety limit
    response = requests.get(url, auth=auth, params={'per_page': 100, 'page': page, 'search': 'B00003507'})
    
    if response.status_code != 200:
        print(f"Error: {response.status_code}")
        break
    
    products = response.json()
    if not products:
        break
    
    for p in products:
        sku = p.get('sku', '')
        orig_sku = None
        
        # Extract original_sku from meta_data
        for m in p.get('meta_data', []):
            if m['key'] == 'original_sku':
                orig_sku = m['value']
                break
        
        # Check if B00003507 appears in SKU or original_sku
        if 'B00003507' in sku or (orig_sku and 'B00003507' in orig_sku):
            found.append({
                'id': p['id'],
                'name': p['name'],
                'sku': sku,
                'orig_sku': orig_sku,
                 'num_categories': len(p.get('categories', []))
            })
    
    page += 1

print(f"Found {len(found)} products with B00003507:\n")

for p in found:
    print(f"ID {p['id']:6} | {p['name'][:50]:50} | SKU: {p['sku']:20} | orig: {p['orig_sku']} | cats: {p['num_categories']}")
