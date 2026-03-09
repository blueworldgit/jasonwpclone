#!/usr/bin/env python3
"""Check T90 EV categories on remote themed site."""
from sql_exec import RemoteSQL

print("Checking T90 EV categories on themed site...")
print("=" * 70)

db = RemoteSQL()
cur = db.cursor(dictionary=True)

# T90 EV VIN
VIN = 'LSFAM120XNA160733'

# Get products for T90 EV
cur.execute("""
    SELECT COUNT(DISTINCT p.ID) as product_count
    FROM wp_posts p
    INNER JOIN wp_posts v ON v.post_parent = p.ID AND v.post_type = 'product_variation'
    INNER JOIN wp_postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
    INNER JOIN wp_sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE p.post_type = 'product'
      AND svm.vin = %s
""", (VIN,))
result = cur.fetchone()
product_count = int(result['product_count'])

print(f"Products for T90 EV: {product_count}")

# Get main categories (parent_id = 0) for T90 EV products
cur.execute("""
    SELECT DISTINCT t.term_id, t.name, t.slug
    FROM wp_terms t
    INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    INNER JOIN wp_term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
    INNER JOIN wp_posts p ON tr.object_id = p.ID
    INNER JOIN wp_posts v ON v.post_parent = p.ID AND v.post_type = 'product_variation'
    INNER JOIN wp_postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
    INNER JOIN wp_sku_vin_mapping svm ON pm.meta_value = svm.sku
    WHERE p.post_type = 'product'
      AND tt.taxonomy = 'product_cat'
      AND tt.parent = 0
      AND svm.vin = %s
    ORDER BY t.name
""", (VIN,))

categories = cur.fetchall()
print(f"\nMain categories: {len(categories)}")
print("\nCategory list:")
for cat in categories:
    print(f"  - {cat['name']} (slug: {cat['slug']})")

# Check for wrong categories
wrong_cats = [
    'Air Intake System',
    'Emission Exhaust System', 
    'Fuel Storage & Handling',
    'Power Energy Storage & Link Wire',
    'Power Generation'
]

print(f"\n{'='*70}")
print("Checking for wrong categories on T90 EV (electric vehicle):")
print("=" * 70)

found_wrong = []
for cat in categories:
    if cat['name'] in wrong_cats:
        found_wrong.append(cat['name'])
        print(f"  ❌ FOUND: {cat['name']}")

if not found_wrong:
    print("  ✓ No wrong categories found!")
else:
    print(f"\n❌ Found {len(found_wrong)} wrong categories")

# Check specific product that was problematic
print(f"\n{'='*70}")
print("Checking products with 'Fuel Storage & Handling':")
print("=" * 70)

cur.execute("""
    SELECT p.ID, p.post_title, COUNT(DISTINCT v.ID) as var_count
    FROM wp_posts p
    INNER JOIN wp_posts v ON v.post_parent = p.ID AND v.post_type = 'product_variation'
    INNER JOIN wp_postmeta pm ON v.ID = pm.post_id AND pm.meta_key = '_sku'
    INNER JOIN wp_sku_vin_mapping svm ON pm.meta_value = svm.sku
    INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE p.post_type = 'product'
      AND tt.taxonomy = 'product_cat'
      AND t.name = 'Fuel Storage & Handling'
      AND svm.vin = %s
    GROUP BY p.ID
    LIMIT 10
""", (VIN,))

fuel_products = cur.fetchall()
if fuel_products:
    print(f"Found {len(fuel_products)} products:")
    for prod in fuel_products:
        print(f"  - ID {prod['ID']}: {prod['post_title']} ({prod['var_count']} variations)")
else:
    print("  ✓ No products with 'Fuel Storage & Handling' found!")

# Check if products are split by VIN
print(f"\n{'='*70}")
print("Checking for VIN-specific product splits:")
print("=" * 70)

cur.execute("""
    SELECT COUNT(*) as count
    FROM wp_posts
    WHERE post_type = 'product'
      AND post_title LIKE '% - T90 EV'
      AND post_status = 'publish'
""")
result = cur.fetchone()
vin_split_count = int(result['count'])
print(f"Products with '- T90 EV' suffix: {vin_split_count}")

if vin_split_count > 100:
    print("  ✓ Products appear to be split by VIN!")
else:
    print("  ⚠ Products may not be fully split yet")

db.close()

print(f"\n{'='*70}")
print("SUMMARY")
print("=" * 70)
print(f"Products: {product_count}")
print(f"Main categories: {len(categories)}")
print(f"Wrong categories: {len(found_wrong)}")
print(f"VIN-split products: {vin_split_count}")

if len(categories) <= 42 and len(found_wrong) == 0 and vin_split_count > 100:
    print("\n✅ DATABASE UPLOAD SUCCESSFUL - All fixes appear to be applied!")
elif vin_split_count > 100:
    print(f"\n⚠️ Products split but still has {len(found_wrong)} wrong categories - may need fix_all_vins")
else:
    print("\n❌ Database upload incomplete - products not split yet")
