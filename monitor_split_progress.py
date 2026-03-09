#!/usr/bin/env python3
"""Monitor split progress by checking product count."""
from sql_exec import RemoteSQL
import time

db = RemoteSQL()
cur = db.cursor(dictionary=True)

print("Monitoring product count on themed site...\n")

for i in range(20):  # Check 20 times over ~2 minutes
    cur.execute('SELECT COUNT(*) as cnt FROM wp_posts WHERE post_type = "product"')
    result = cur.fetchone()
    product_count = result['cnt']
    
    cur.execute('SELECT COUNT(*) as cnt FROM wp_posts WHERE post_type = "product_variation"')
    result = cur.fetchone()
    variation_count = result['cnt']
    
    print(f"{time.strftime('%H:%M:%S')} - Products: {product_count}, Variations: {variation_count}")
    
    if i < 19:
        time.sleep(6)  # Wait 6 seconds between checks

db.close()
print("\nInitial counts: Products: 6,801, Variations: 24,470")
print("Expected after split: Products: ~19,000+, Variations: ~24,470")
