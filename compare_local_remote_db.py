#!/usr/bin/env python3
"""Compare local and remote database post type counts."""
import mysql.connector
from sql_exec import RemoteSQL

print("=" * 70)
print("LOCAL DATABASE (WAMP)")
print("=" * 70)

local = mysql.connector.connect(host="localhost", user="root", password="", database="maxussql")
cur = local.cursor(dictionary=True)

cur.execute("SELECT post_type, post_status, COUNT(*) as count FROM wp_posts GROUP BY post_type, post_status ORDER BY post_type, count DESC")
local_results = cur.fetchall()

for row in local_results:
    print(f"  {row['post_type']:25} {row['post_status']:15} {row['count']:6}")

cur.execute("SELECT COUNT(*) as total FROM wp_posts")
local_total = cur.fetchone()['total']
print(f"\n  TOTAL: {local_total}")

# Check categories
cur.execute("""
    SELECT COUNT(DISTINCT t.term_id) as count
    FROM wp_terms t
    INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'product_cat' AND tt.parent = 0
""")
local_main_cats = cur.fetchone()['count']
print(f"  Main categories: {local_main_cats}")

local.close()

print("\n" + "=" * 70)
print("REMOTE DATABASE (Themed Site)")
print("=" * 70)

remote = RemoteSQL()
cur = remote.cursor(dictionary=True)

cur.execute("SELECT post_type, post_status, COUNT(*) as count FROM wp_posts GROUP BY post_type, post_status ORDER BY post_type, count DESC")
remote_results = cur.fetchall()

for row in remote_results:
    print(f"  {row['post_type']:25} {row['post_status']:15} {int(row['count']):6}")

cur.execute("SELECT COUNT(*) as total FROM wp_posts")
remote_total = int(cur.fetchone()['total'])
print(f"\n  TOTAL: {remote_total}")

# Check categories
cur.execute("""
    SELECT COUNT(DISTINCT t.term_id) as count
    FROM wp_terms t
    INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'product_cat' AND tt.parent = 0
""")
remote_main_cats = int(cur.fetchone()['count'])
print(f"  Main categories: {remote_main_cats}")

remote.close()

print("\n" + "=" * 70)
print("COMPARISON")
print("=" * 70)
print(f"  Total posts:      Local={local_total:6}  Remote={remote_total:6}  Diff={remote_total-local_total:+6}")
print(f"  Main categories:  Local={local_main_cats:6}  Remote={remote_main_cats:6}  Diff={remote_main_cats-local_main_cats:+6}")

if abs(remote_total - local_total) < 100:
    print("\n  ✓ Post counts are very close - likely just revisions/auto-saves")
else:
    print("\n  ⚠ Post counts differ significantly")

if local_main_cats == remote_main_cats:
    print("  ✓ Category counts match")
else:
    print(f"  ❌ Category counts differ - remote has {remote_main_cats - local_main_cats} more")
