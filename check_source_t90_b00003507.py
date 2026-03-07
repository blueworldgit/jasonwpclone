#!/usr/bin/env python3
import requests

# Source API
url = 'https://maxusvanparts.co.uk/wp-json/wc/v3/products'
auth = ('ck_573295ab285b1f112436b620f6bed208b5702503', 'cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109')

# Check products under T90 EV serial category
# From previous runs, T90 EV serial category ID is 3590

print("Fetching T90 EV products from source...\n")

page = 1
products_with_b00003507 = []

while page < 200:  # Safety limit
    response = requests.get(url, auth=auth, params={'per_page': 100, 'page': page, 'category': '3590'})
    
    if response.status_code != 200:
        print(f"Error on page {page}: {response.status_code}")
        break
    
    products = response.json()
    if not products:
        break
    
    for p in products:
        sku = p.get('sku', '')
        if 'B00003507' in sku:
            products_with_b00003507.append({
                'id': p['id'],
                'name': p['name'],
                'sku': sku,
                'categories': len(p.get('categories', []))
            })
    
    page += 1

print(f"Found {len(products_with_b00003507)} products with B00003507 in SKU:\n")

for p in products_with_b00003507[:20]:  # Show first 20
    print(f"ID {p['id']:6} | SKU: {p['sku']:25} | cats: {p['categories']:2} | {p['name'][:50]}")
