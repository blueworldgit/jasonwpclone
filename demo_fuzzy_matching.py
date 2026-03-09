#!/usr/bin/env python3
"""
DEMO: Show fuzzy title matching results for products with wrong categories.
No changes made - just displays matching logic.
"""
import mysql.connector
import requests
import time
from difflib import SequenceMatcher

# Config
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"

# Source API
SOURCE_URL = "https://maxusvanparts.co.uk"
SOURCE_CK = "ck_573295ab285b1f112436b620f6bed208b5702503"
SOURCE_CS = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"

VIN = "LSFAM120XNA160733"  # T90 EV

print("=" * 80)
print("DEMO: Fuzzy Title Matching for Wrong Category Products")
print("=" * 80)
print()

def safe_str(text):
    """Convert text to safe ASCII for Windows console."""
    try:
        # Try to encode/decode - if it fails, replace problematic chars
        text.encode('cp1252')
        return text
    except (UnicodeEncodeError, AttributeError):
        return str(text).encode('ascii', 'replace').decode('ascii')

def similarity(a, b):
    """Calculate similarity ratio between two strings (0.0 to 1.0)."""
    return SequenceMatcher(None, a.lower(), b.lower()).ratio()

# Step 1: Get local products with wrong categories
print("STEP 1: Getting local products with wrong categories...")
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
        pm_sku.meta_value as sku
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
print(f"Found {len(problem_products)} products with wrong categories")
print()

# Step 2: For each product, fetch source variants and show matching
print("=" * 80)
print("STEP 2: Fetching source products and demonstrating matching...")
print("=" * 80)
print()

for i, prod in enumerate(problem_products, 1):
    local_sku = prod['sku']
    local_title = prod['post_title']
    
    # Get base SKU (strip hash suffix)
    base_sku = local_sku.rsplit('-', 1)[0] if '-' in local_sku else local_sku
    
    print(f"[{i}/{len(problem_products)}]")
    print(f"=" * 80)
    print(f"LOCAL PRODUCT:")
    print(f"  Title: {local_title}")
    print(f"  SKU:   {local_sku}")
    print(f"  Base:  {base_sku}")
    print()
    
    # Strategy 1: Search by exact SKU
    print(f"  [SEARCH 1] Exact SKU search...")
    response = requests.get(
        f"{SOURCE_URL}/wp-json/wc/v3/products",
        auth=(SOURCE_CK, SOURCE_CS),
        params={
            "sku": local_sku,
            "per_page": 50,
            "status": "publish"
        },
        timeout=30
    )
    source_products = response.json() if response.status_code == 200 else []
    print(f"  Found: {len(source_products)}")
    
    # Strategy 2: Search by base SKU
    if not source_products:
        print(f"  [SEARCH 2] Base SKU search...")
        response = requests.get(
            f"{SOURCE_URL}/wp-json/wc/v3/products",
            auth=(SOURCE_CK, SOURCE_CS),
            params={
                "sku": base_sku,
                "per_page": 50,
                "status": "publish"
            },
            timeout=30
        )
        source_products = response.json() if response.status_code == 200 else []
        print(f"  Found: {len(source_products)}")
    
    # Strategy 3: Search by SKU pattern in search field (finds hash-suffixed SKUs)
    if not source_products:
        print(f"  [SEARCH 3] SKU pattern search (finds {base_sku}-*)...")
        response = requests.get(
            f"{SOURCE_URL}/wp-json/wc/v3/products",
            auth=(SOURCE_CK, SOURCE_CS),
            params={
                "search": base_sku,
                "per_page": 100,
                "status": "publish"
            },
            timeout=30
        )
        if response.status_code == 200:
            all_results = response.json()
            # Filter to products whose SKU starts with base_sku
            source_products = []
            for p in all_results:
                p_sku = p.get('sku', '')
                if p_sku.startswith(base_sku):
                    source_products.append(p)
            print(f"  Found: {len(source_products)} products with SKU starting with {base_sku}")
    
    # Strategy 4: Search by product title
    if not source_products:
        print(f"  [SEARCH 4] Title search...")
        # Extract key words from title (remove common words)
        title_words = local_title.replace('-', ' ').split()
        search_term = ' '.join([w for w in title_words if len(w) > 3][:3])  # First 3 significant words
        print(f"  Searching for: '{search_term}'")
        
        response = requests.get(
            f"{SOURCE_URL}/wp-json/wc/v3/products",
            auth=(SOURCE_CK, SOURCE_CS),
            params={
                "search": search_term,
                "per_page": 50,
                "status": "publish"
            },
            timeout=30
        )
        if response.status_code == 200:
            source_products = response.json()
            print(f"  Found: {len(source_products)}")
    
    if not source_products:
        print(f"  [NOT FOUND] No source products found with any search method")
        print()
        print("-" * 80)
        print()
        continue
    
    print()
    print(f"SOURCE PRODUCTS FOUND: {len(source_products)}")
    print()
    
    if len(source_products) == 1:
        # Only one match
        source = source_products[0]
        sim = similarity(local_title, source['name'])
        
        print(f"  >> SINGLE MATCH (auto-selected)")
        print()
        print(f"     LOCAL:  {local_title}")
        print(f"     SOURCE: {source['name']}")
        print(f"     SKU:    {source['sku']}")
        print(f"     Match:  {sim:.1%}")
        print()
        
        # Filter out imageupdated and priceupdated
        cat_names = [safe_str(c['name']) for c in source.get('categories', []) 
                     if c['name'].lower() not in ['imageupdated', 'priceupdated']]
        print(f"     Categories ({len(cat_names)}): {', '.join(cat_names[:5])}")
        if len(cat_names) > 5:
            print(f"                                 ... and {len(cat_names)-5} more")
    else:
        # Multiple matches - calculate similarities
        print(f"  >> MULTIPLE MATCHES - Ranking by Title Similarity:")
        print()
        
        matches = []
        for source in source_products:
            sim = similarity(local_title, source['name'])
            matches.append((sim, source))
        
        # Sort by similarity (highest first)
        matches.sort(reverse=True, key=lambda x: x[0])
        
        print(f"     LOCAL TITLE: {local_title}")
        print()
        
        for rank, (sim, source) in enumerate(matches, 1):
            marker = ">>> BEST" if rank == 1 else "   "
            print(f"  {marker} #{rank}: {sim:.1%} similarity")
            print(f"       SOURCE: {source['name']}")
            print(f"       SKU:    {source['sku']}")
            
            # Filter out imageupdated and priceupdated
            cat_names = [safe_str(c['name']) for c in source.get('categories', []) 
                         if c['name'].lower() not in ['imageupdated', 'priceupdated']]
            print(f"       Cats:   {', '.join(cat_names[:4])}")
            if len(cat_names) > 4:
                print(f"               ... and {len(cat_names)-4} more")
            print()
        
        # Show decision
        best_sim, best_match = matches[0]
        threshold = 0.3  # Lowered from 0.5
        if best_sim >= threshold:
            print(f"  [DECISION] Use match #1 (similarity {best_sim:.1%} >= {threshold:.0%} threshold)")
        else:
            print(f"  [WARNING] Skip (best similarity {best_sim:.1%} < {threshold:.0%} threshold)")
    
    print()
    print("-" * 80)
    print()
    
    time.sleep(0.5)  # Rate limiting

conn.close()

print("=" * 80)
print("DEMO COMPLETE")
print("=" * 80)
print()
print("Review the matching results above:")
print("  - Similarity threshold: 30% (products below this are skipped)")
print("  - Check that BEST matches make sense")
print("  - Verify category assignments look correct")
print()
print("If the fuzzy matching looks good, run:")
print("  python fix_remaining_categories.py --fix")
