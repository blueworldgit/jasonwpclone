# WooCommerce SKU & Vehicle Compatibility Project

**Themed site (broken data):** maxusvanparts.acstestweb.co.uk  
**Source site (correct data):** maxusvanparts.co.uk  
**Local clone DB:** maxussql (WAMP, localhost, root, no password)  
**Project:** Fix category corruption on themed site using source site as authority  
**Date Range:** March 2–7, 2026

---

> **NOTE FOR AI READING THIS ON A NEW MACHINE**
> This document is the full handover context. The work is split across two sites:
> 1. **Source site** (`maxusvanparts.co.uk`) — correct products, correct category hierarchy, imported from Oscar parts DB. Use its WooCommerce REST API as the authority.
> 2. **Themed site** (`maxusvanparts.acstestweb.co.uk`) — has Mobex/Mobex-child theme with custom vehicle routing, but corrupt category assignments due to bad import. This is what we are fixing.
> 3. **Local WAMP clone** (`maxussql`) — exact copy of the themed site's DB used for safe development. Fix here first, then deploy to live.
>
> **Current status:** Diagnostics complete for VIN `LSFAM120XNA160733` (MAXUS T90 EV). Root cause identified. Fix scripts written but `fix_vin_mapping.py` hit a Windows cp1252 encoding error on the arrow character — see Known Issues section. Next step: build `fix_all_vins.py` to correct ALL 17 VINs in one pass.

---

## Table of Contents
1. [Credentials & Connections](#credentials--connections)
2. [Site Architecture](#site-architecture)
3. [Problem Summary](#problem-summary)
4. [Root Cause: Category Corruption](#root-cause-category-corruption)
5. [The Fix Plan](#the-fix-plan)
6. [Database Structure](#database-structure)
7. [Source Site Structure](#source-site-structure)
8. [Vehicle VIN Reference](#vehicle-vin-reference)
9. [Scripts — Phase 1 (SKU Fixes, done)](#scripts--phase-1-sku-fixes-done)
10. [Scripts — Phase 2 (Category Fix, current)](#scripts--phase-2-category-fix-current)
11. [Key Findings & Diagnostics](#key-findings--diagnostics)
12. [Next Steps](#next-steps)

---

## Credentials & Connections

### Source Site API (maxusvanparts.co.uk)
```
URL    : https://maxusvanparts.co.uk
CK     : ck_573295ab285b1f112436b620f6bed208b5702503
CS     : cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109
Auth   : HTTP Basic (CK as username, CS as password)
API    : /wp-json/wc/v3/
```

### Themed Site API (maxusvanparts.acstestweb.co.uk)
```
URL    : https://maxusvanparts.acstestweb.co.uk
Note   : Different CK/CS — stored in correctwpsite.txt (DO NOT commit)
```

### Local WAMP DB (themed site clone)
```python
DB_CFG = dict(host="localhost", user="root", password="", database="maxussql")
PREFIX = "wp_"   # all table names: wp_posts, wp_postmeta, etc.
```

### Remote SQL Tool (for live themed site)
- PHP file: `sql_exec.php` — upload to WP root of themed site when needed
- Token: `maxus-sql-exec-a7f3k9z2-2026`
- Python module: `sql_exec.py` — `from sql_exec import RemoteSQL; db = RemoteSQL()`

### Python Environment
```
Location : c:\pythonstuff\wpimportcollection\.venv
Activate : .\.venv\Scripts\Activate.ps1
Python   : .\.venv\Scripts\python.exe
Key pkgs : mysql-connector-python, aiohttp==3.13.3, woocommerce, pandas
```

---

## Site Architecture

```
maxusvanparts.co.uk          (SOURCE — correct data)
    └── WooCommerce
    └── Oscar DB import → products have original_sku meta
    └── Category hierarchy: Brand → Serial/VIN → Main Cat → Sub Cat
    └── ~24,468 variations, ~2,642 simple products
    └── SKUs on products: "C00343611-5BF312" (clean: "C00343611")

maxusvanparts.acstestweb.co.uk   (THEMED SITE — broken categories)
    └── Mobex parent theme + mobex-child theme
    └── Custom vehicle routing: /vehicle/{slug}/category/{cat}/
    └── sku_vin_mapping table drives everything (not WP taxonomy)
    └── product_cat taxonomy assignments are corrupted (wrong VINs bleeding in)
    └── DB clone = maxussql on local WAMP

Local WAMP (c:\wamp64\www\jasonwpclone\)
    └── Exact clone of themed site
    └── DB: maxussql
    └── Fix/test here first, then deploy to live
```

---

---

## Problem Summary

### Phase 1 — SKU Visibility (SOLVED)
- SKUs visible in product list but NOT in product edit screen
- Custom system: SKUs stored in product slugs, not standard WooCommerce fields
- Custom table `wp_sku_vin_mapping` (25,646 rows) maps SKU → VIN
- DB corruption: `wp_postmeta.meta_id` missing AUTO_INCREMENT
- **Fixed:** Extracted SKUs from slugs, synced 8,315+ products to `wp_postmeta`

### Phase 2 — Category Corruption (CURRENT WORK)
- The themed site's `product_cat` taxonomy assignments are wrong
- Products for one VIN (e.g. T90 EV) show categories from other VINs (ICE engines, school buses)
- Root cause: import process assigned categories per-product across ALL VINs simultaneously
- Result: 505 of 1,029 T90 EV products are shared with other VINs and carry those VINs' categories
- **Source site has the correct category assignments** — use it as authority to fix local DB

---

## Root Cause: Category Corruption

### Diagnostic Results (VIN LSFAM120XNA160733 — T90 EV)

Run `diagnose_cat_bleed.py` to reproduce. Key findings:

```
Total products linked to T90 EV VIN: 1,029
  → Single-VIN (only T90 EV):         524
  → Multi-VIN (shared with others):   505   ← these carry bleed-through categories
```

**Bleed sources** (other VINs sharing products with T90 EV):
```
LSFAM11C6RA133899  →  418 shared products  (T60 variant)
LSFAM11C6RA144501  →  414 shared products  (T60 variant)
LSH14J4CXMA165329  →   82 shared products
LSH14C4C5NA129710  →   77 shared products
LSFAL11A4PA157987  →   65 shared products
... (16 other VINs bleed in)
```

**Category comparison result** (source vs local for T90 EV):
```
Source site : 42 main categories, 97 subcategories
Local DB    : 51 main categories, 348 subcategories

Main cats matched : 41/42  (10 fuzzy — & vs and naming)
Missing from local:  1     ('Pipe' — entire category absent)
Extra in local    : 10     (ICE-only: Air Intake, Emission Exhaust, Fuel Storage, etc.)
Missing sub cats  : 11
Extra sub cats    : 193    (cross-VIN bleed: school bus seats, clutch parts, gearbox variants)
```

**Why categories are wrong at product level:**
Categories are stored in `wp_term_relationships` linked to `post_id`. When a product (variation) is shared across VINs, its term_relationships contain categories from ALL those VINs. There is no per-VIN scoping — categories are global to the product. So querying "all products for VIN X" pulls all their categories, including ones that belong to VIN Y.

**Two sub-problems:**
1. `sku_vin_mapping` has entries for T90 EV that should not be there (wrong mappings from import)
2. Correctly-mapped shared products carry categories from their other VINs

---

## The Fix Plan

**Single-pass fix across all 17 VINs** — must be done in one operation because shared products must satisfy ALL their VINs simultaneously.

### Steps for `fix_all_vins.py` (TO BE BUILT)

```
1.  Fetch ALL category IDs from source site concurrently (already done — ~11s, 3,512 cats)
2.  For each of the 17 VINs:
    a. Find serial category on source (match by VIN string in cat name/slug)
    b. Fetch all products + variations under that serial category from source API
    c. Extract original_sku from each (meta_data key = 'original_sku')
    d. Also accept stripped SKU (remove hash suffix: "C00343611-5BF312" → "C00343611")
    e. Build: valid_skus_per_vin = { VIN: set(skus) }
    f. Build: sku_to_categories = { sku: { main_cat_id, [sub_cat_ids] } }
       using source category IDs mapped to local category term_ids (by name match)

3.  Fix sku_vin_mapping:
    For each VIN, remove any sku_vin_mapping row where sku NOT in valid_skus_per_vin[VIN]

4.  Create missing category terms in local DB:
    Any source category name not found locally → INSERT into wp_terms + wp_term_taxonomy

5.  Rebuild product_cat term_relationships:
    - Get all product IDs whose SKU appears in any valid_skus_per_vin
    - DELETE all their product_cat term_relationships
    - For each product, look up its SKU → source categories → local term_ids
    - INSERT correct term_relationships

6.  Recalculate wp_term_taxonomy.count for all affected terms

7.  Report:
    - SKU mappings removed per VIN
    - Category terms created
    - Products re-mapped
    - Any SKUs on source with no local product (import gap)
```

### Key constraints
- Category relationships are NOT VIN-scoped — a shared product gets the UNION of categories from all its valid VINs
- Must process all VINs before writing any relationships (shared products need complete picture first)
- `wp_term_taxonomy.count` MUST be updated — WP uses it to hide empty categories
- After fix, run WooCommerce → Status → Tools → Regenerate product lookup table on live site

### Name matching (source → local)
Source site uses `and`, local uses `&` (e.g. "Wiper and Washer" vs "Wiper & Washer").
Use the `normalize()` function from `compare_categories.py`:
```python
def normalize(s):
    s = re.sub(r'\s*&\s*', 'and', s)
    s = re.sub(r'[,\s\-_/()]', '', s)
    return s.lower()
```

---

## Source Site Structure

### Category hierarchy (4 levels)
```
Brand (parent=0)               e.g. "Maxus"  [id: 3590]
  └── Serial/VIN (parent=Brand)  e.g. "LSFAM120XNA160733"  [id: 4408]
        └── Main Category        e.g. "Brakes"  [id: 4421]
              └── Sub Category   e.g. "Front Brakes"  [id: 4453]
```

The themed site only uses Main → Sub (2 levels visible to user).
Brand and Serial levels exist on source but are not surfaced in the theme UI.

### Product meta fields on source
```
original_sku      : clean SKU without hash (e.g. "C00343611")  ← USE THIS for matching
oscar_part_id     : Oscar DB part ID
callout_number    : diagram callout number
unit_qty          : quantity per unit
lr                : Left/Right designation
vehicle_serial    : VIN this part belongs to
```

### SKU format on source WooCommerce
- Product SKU field (`.sku`): `C00343611-5BF312` (clean SKU + hyphen + 6-char hash)
- `original_sku` meta: `C00343611` — this matches `_sku` in the themed site's `wp_postmeta`
- Always use `original_sku` for cross-site matching, never the hashed version

### T90 EV serial category (already fetched)
```
VIN       : LSFAM120XNA160733
Brand     : Maxus  [source cat id: 3590]
Serial cat: LSFAM120XNA160733  [source cat id: 4408]
Main cats : 42
Sub cats  : 97
JSON file : categories_LSFAM120XNA160733.json  (in project dir)
```

---

## Vehicle VIN Reference

All 17 VINs in the themed site's `wp_sku_vin_mapping`:

| VIN | Vehicle Name | Parts Count |
|-----|-------------|-------------|
| LSFAM120XNA160733 | MAXUS T90 EV | ~1,029 |
| LSFAM11C6RA133899 | MAXUS T60 (variant 1) | ~1,488 |
| LSFAM11C6RA144501 | MAXUS T60 (variant 2) | ~1,488 |
| LSFAL11A4PA157987 | MAXUS Deliver 9 RWD | varies |
| LSFAL11A5MA087816 | MAXUS Deliver 9 variant | varies |
| LSH14J7C2MA122115 | MAXUS eDeliver 3 variant | ~1,621 |
| LSH14J7CXMA114599 | MAXUS eDeliver 3 variant | ~1,573 |
| LSH14J7C7MA114771 | MAXUS eDeliver 3 variant | ~1,528 |
| LSH14J7C4RV123458 | MAXUS eDeliver 3 variant | varies |
| LSH14J7C3RV123225 | MAXUS eDeliver 3 variant | varies |
| LSH14J7C9RV123360 | MAXUS eDeliver 3 variant | varies |
| LSH14J4CXMA165329 | MAXUS eDeliver 9 variant | varies |
| LSH14J4C0RV121632 | MAXUS eDeliver 9 variant | varies |
| LSH14J7C0SA082498 | MAXUS variant | varies |
| LSH14C4C5NA129710 | MAXUS variant | ~1,516 |
| LSH14JTC6FA621119 | MAXUS variant | varies |
| LSKG5GL16KA060062 | MAXUS variant | ~1,692 |

**Note:** The 18th VIN (`LSFAM11C4RA133898`) was a duplicate T60 artefact — merged and deleted in an earlier session (documented in db.md).

---

---

## SKU Visibility Issue

### Discovery Process

#### 1. Initial Investigation
- SKUs appearing via API but not in database `wp_postmeta` table
- Found custom `wp_sku_vin_mapping` table with 25,646 entries
- Discovered SKUs embedded in product slugs

#### 2. SKU Extraction Patterns
Three regex patterns used to extract SKUs from slugs:

```python
def extract_sku_from_slug(slug):
    # Pattern 1: C/B followed by numbers (C00017370, B00003511)
    match = re.search(r'([CB]\d{8})', slug)
    if match:
        return match.group(1)
    
    # Pattern 2: Numbers with hyphens (6948-7, 6947-14)
    match = re.search(r'(\d{4,5}-\d{1,3})', slug)
    if match:
        return match.group(1)
    
    # Pattern 3: Trailing numbers (last digits in slug)
    match = re.search(r'-(\d{4,5})$', slug)
    if match:
        return match.group(1)
    
    return None
```

#### 3. Database Corruption Found
**Issue:** `wp_postmeta.meta_id` column missing AUTO_INCREMENT attribute

```sql
-- BEFORE (corrupted):
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`meta_id`)
)

-- AFTER (fixed):
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`meta_id`)
)
```

**Fix Steps:**
1. Found and deleted 1 row with `meta_id = 0`
2. Ran `ALTER TABLE wp_postmeta MODIFY COLUMN meta_id bigint unsigned NOT NULL AUTO_INCREMENT`
3. Set AUTO_INCREMENT starting value to max_id + 1 (3,532,830)

#### 4. Sync Results
- **First sync:** 4,157 products in 2.98 seconds (1,394 products/sec)
- **Second sync:** 4,158 products in 2.77 seconds (1,499 products/sec)
- **Total:** 8,315+ products successfully synced

### Variable Products Behavior
**Important:** Variable products (with variations) don't show parent SKU in Inventory tab
- Parent SKU: Won't display in main Inventory tab
- Variation SKUs: Found in **Variations tab** → Expand each variation
- Example: Product 224469 (BOLT-BACK UP HORN)
  - Variation 192956: SKU `B00003511`
  - Variation 219394: SKU `C00285681`

---

## Database Structure

### Core WooCommerce Tables

#### wp_postmeta
**Purpose:** Stores all product meta data including SKUs  
**Key Fields:**
- `meta_id` - Primary key (AUTO_INCREMENT)
- `post_id` - Product ID
- `meta_key` - Field name (e.g., `_sku`)
- `meta_value` - Field value

**SKU Query:**
```sql
SELECT post_id, meta_value as sku
FROM wp_postmeta
WHERE meta_key = '_sku' AND meta_value != ''
```

#### wp_wc_product_meta_lookup
**Purpose:** WooCommerce lookup table for fast queries  
**Key Fields:**
- `product_id` - Product ID (primary key)
- `sku` - Product SKU
- `stock_quantity` - Stock level
- `stock_status` - In stock / out of stock

**Note:** Must regenerate this table in WooCommerce → Status → Tools after bulk updates

#### wp_posts
**Purpose:** Stores all WordPress content including products  
**Key Fields:**
- `ID` - Product ID
- `post_title` - Product name
- `post_name` - Slug (where SKUs were embedded)
- `post_type` - 'product' or 'product_variation'
- `post_parent` - Parent product ID (for variations)
- `post_status` - 'publish', 'draft', etc.

---

## Vehicle Compatibility System

### Two Systems Discovered

#### 1. VIN Mapping Table (PRIMARY - ACTIVELY USED)

**Table:** `wp_sku_vin_mapping`

**Structure:**
```sql
CREATE TABLE `wp_sku_vin_mapping` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL,
  `vin` varchar(17) NOT NULL,           -- Full 17-char VIN
  `vin_pattern` varchar(8) NOT NULL,    -- First 8 chars for matching
  `vehicle_name` varchar(50) NOT NULL,  -- Human readable name
  `vehicle_year` varchar(20) NOT NULL,  -- Year range
  `variant_attribute` varchar(100),     -- Optional (Left/Right/etc)
  `variation_id` bigint,                -- Links to product variation
  PRIMARY KEY (`id`),
  KEY `sku` (`sku`),
  KEY `vin` (`vin`),
  KEY `vin_pattern` (`vin_pattern`)
)
```

**Statistics:**
- Total mappings: **25,646**
- Unique SKUs: **8,355**
- Unique VINs: **18**
- Unique VIN patterns: **18**
- Average: 3.1 vehicles per SKU (some universal parts)

**Sample Data:**
```
SKU: C00091604
VIN: LSFAL11A4PA157987
VIN Pattern: LSFAL11A
Vehicle: MAXUS DELIVER 9 RWD LUX
Years: 2023-2024
```

**Most Popular Vehicles (by part count):**
1. LSKG5GL16KA060062 - 1,692 parts
2. LSH14J7C2MA122115 - 1,621 parts
3. LSH14J7CXMA114599 - 1,573 parts
4. LSH14J7C7MA114771 - 1,528 parts
5. LSH14C4C5NA129710 - 1,516 parts

**Query Examples:**
```sql
-- Find all parts for a specific vehicle
SELECT sku, vehicle_name, vehicle_year
FROM wp_sku_vin_mapping
WHERE vin_pattern = 'LSFAL11A'

-- Find all vehicles for a specific part
SELECT vehicle_name, vehicle_year, vin
FROM wp_sku_vin_mapping
WHERE sku = 'C00091604'

-- Count parts per vehicle
SELECT vehicle_name, COUNT(*) as part_count
FROM wp_sku_vin_mapping
GROUP BY vehicle_name
ORDER BY part_count DESC
```

#### 2. Vehicles Taxonomy (LEGACY - NOT ACTIVELY USED)

**Taxonomy:** `vehicles`  
**Tables:** `wp_terms`, `wp_term_taxonomy`, `wp_term_relationships`

**21 Vehicle Terms Defined:**
1. Maxus A80 Chassis (2015) - **0 products**
2. Maxus Deliver 7 (2024) - **0 products**
3. Maxus Deliver 7 High Roof Diesel (2024) - **0 products**
4. Maxus Deliver 7 Low Roof Diesel (2024) - **0 products**
5. Maxus Deliver 9 4x4 (2021-2024) - **0 products**
6. Maxus Deliver 9 FWD LUX (2021) - **0 products**
7. Maxus Deliver 9 FWD STD (2021) - **0 products**
8. Maxus Deliver 9 LWB (2021-2024) - **0 products**
9. Maxus Deliver 9 RWD Chassis (2021) - **0 products**
10. Maxus Deliver 9 RWD LUX (2023) - **0 products**
11. Maxus Deliver 9 RWD STD (2021) - **0 products**
12. Maxus Deliver 9 RWD Tipper (2021-2024) - **0 products**
13. Maxus E Deliver 3 (2022) - **0 products**
14. Maxus E Deliver 7 (2024) - **0 products**
15. Maxus E Deliver 9 (2021) - **0 products**
16. **Maxus eDeliver 3 (2020-2026) - 1,839 products** ✓ Only one with assignments
17. Maxus New Deliver 9 Diesel (2025) - **0 products**
18. Maxus New T60 Diesel (2024) - **0 products**
19. Maxus T60 (2024) - **0 products**
20. Maxus T90 EV (2022) - **0 products**
21. Maxus V80 Van (2019) - **0 products**

**Admin URL:** `wp-admin/edit-tags.php?taxonomy=vehicles&post_type=product`

**Query Examples:**
```sql
-- Get all products for a vehicle
SELECT p.ID, p.post_title
FROM wp_posts p
JOIN wp_term_relationships tr ON p.ID = tr.object_id
JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_id
JOIN wp_terms t ON tt.term_id = t.term_id
WHERE tt.taxonomy = 'vehicles'
AND t.slug = 'maxus-edeliver-3-2020-2026'
AND p.post_type = 'product'
AND p.post_status = 'publish'

-- Update term counts
UPDATE wp_term_taxonomy tt
SET count = (
    SELECT COUNT(*) 
    FROM wp_term_relationships tr
    WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
)
WHERE tt.taxonomy = 'vehicles'
```

### How Theme Uses Vehicle Data

**Current Behavior:**
- Theme queries `wp_sku_vin_mapping` table directly
- Ignores `vehicles` taxonomy
- Why: VIN mapping has actual data (25,646 rows), taxonomy mostly empty

**Result:**
- Admin shows: "Most vehicles = 0 products" (taxonomy count)
- Customers see: "All vehicles have ~1,400 parts" (VIN mapping)
- Discrepancy causes confusion

**Possible Solutions:**
1. **Sync VIN → Taxonomy:** Use `sync_vehicles_taxonomy.py` to assign products to taxonomy terms
2. **Update VIN mappings:** Replace entire `wp_sku_vin_mapping` table with JSON data
3. **Theme modification:** Change theme to respect taxonomy instead of VIN table

---

## Scripts — Phase 1 (SKU Fixes, done)

All scripts in `c:\pythonstuff\wpimportcollection\`

### Analysis Scripts (Phase 1)

#### analyze_sku.py
Initial database analysis — product counts, SKU locations, missing SKUs.

#### check_specific.py
Check any product by ID: `.\.venv\Scripts\python.exe check_specific.py 224469`

#### analyze_vehicle_mapping.py
Comprehensive VIN mapping analysis — structure, stats, sample data.

#### analyze_vehicles_taxonomy.py
Analyse vehicles taxonomy — 21 terms, product counts, hierarchy.

### Fix Scripts (Phase 1)

#### sync_skus_auto.py ⭐ MAIN SKU SYNC
Extract SKUs from slugs and sync to `wp_postmeta`.
- Performance: 1,400+ products/second
- Ran twice: 8,315+ products synced total

#### repair_table.py
Restored AUTO_INCREMENT to `wp_postmeta.meta_id`.

#### sync_vehicles_taxonomy.py
Syncs `wp_sku_vin_mapping` → `vehicles` taxonomy (legacy, not needed for phase 2 fix).

---

## Scripts — Phase 2 (Category Fix, current)

### get_serial_categories.py ⭐ ASYNC CATEGORY FETCHER
Fetches all categories for one VIN serial from source site and writes JSON tree.
Completes in ~11 seconds (async) vs ~10 minutes (sequential).

**Usage:**
```bash
python get_serial_categories.py                      # default: T90 EV
python get_serial_categories.py LSFAM11C6RA133899    # any VIN
```

**Output:** `categories_{VIN}.json`

**Already run for:** `LSFAM120XNA160733` → `categories_LSFAM120XNA160733.json` (42 main, 97 sub)

**Full script:**
```python
import asyncio, aiohttp, json, sys, time
from collections import defaultdict

WP_URL  = "https://maxusvanparts.co.uk"
CK      = "ck_573295ab285b1f112436b620f6bed208b5702503"
CS      = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"
SERIAL  = sys.argv[1] if len(sys.argv) > 1 else "LSFAM120XNA160733"
CONCURRENCY = 10

async def fetch_page(session, page):
    async with session.get(
        f"{WP_URL}/wp-json/wc/v3/products/categories",
        params={"per_page": 100, "page": page, "orderby": "id", "order": "asc"},
    ) as r:
        r.raise_for_status()
        data = await r.json()
        total_pages = int(r.headers.get("X-WP-TotalPages", 1))
        return data, total_pages

async def fetch_all_categories():
    auth = aiohttp.BasicAuth(CK, CS)
    connector = aiohttp.TCPConnector(limit=CONCURRENCY, ssl=True)
    timeout   = aiohttp.ClientTimeout(total=60)
    async with aiohttp.ClientSession(auth=auth, connector=connector, timeout=timeout) as session:
        first_batch, total_pages = await fetch_page(session, 1)
        print(f"  Total pages: {total_pages}")
        all_cats = list(first_batch)
        if total_pages > 1:
            sem = asyncio.Semaphore(CONCURRENCY)
            async def fetch_limited(page):
                async with sem:
                    data, _ = await fetch_page(session, page)
                    return data
            tasks   = [fetch_limited(p) for p in range(2, total_pages + 1)]
            results = await asyncio.gather(*tasks)
            for batch in results:
                all_cats.extend(batch)
    print(f"  Done -- {len(all_cats)} categories total.")
    return all_cats

async def main():
    t0       = time.perf_counter()
    all_cats = await fetch_all_categories()
    print(f"Fetch time: {time.perf_counter()-t0:.1f}s")
    by_id    = {c['id']: c for c in all_cats}
    children = defaultdict(list)
    for c in all_cats:
        children[c['parent']].append(c)
    serial_cat = next((c for c in all_cats
                       if c['name'].upper() == SERIAL.upper()
                       or c['slug'].upper() == SERIAL.upper()), None)
    if not serial_cat:
        print(f"ERROR: could not find category for '{SERIAL}'")
        return
    brand_cat  = by_id.get(serial_cat['parent'])
    main_cats  = sorted(children.get(serial_cat['id'], []), key=lambda x: x['name'])
    tree_nodes = []
    for mc in main_cats:
        sub_cats = sorted(children.get(mc['id'], []), key=lambda x: x['name'])
        tree_nodes.append({"id": mc['id'], "name": mc['name'], "slug": mc['slug'],
                           "count": mc['count'],
                           "subcategories": [{"id": sc['id'], "name": sc['name'],
                                              "slug": sc['slug'], "count": sc['count']}
                                             for sc in sub_cats]})
    output = {"serial": SERIAL, "brand": brand_cat['name'] if brand_cat else None,
              "serial_category_id": serial_cat['id'],
              "serial_category_slug": serial_cat['slug'],
              "serial_category_name": serial_cat['name'],
              "main_category_count": len(main_cats),
              "subcategory_count": sum(len(n['subcategories']) for n in tree_nodes),
              "categories": tree_nodes}
    out_file = f"categories_{SERIAL}.json"
    with open(out_file, "w", encoding="utf-8") as f:
        json.dump(output, f, indent=2, ensure_ascii=False)
    print(f"Saved: {out_file}")

asyncio.run(main())
```

---

### compare_categories.py ⭐ CATEGORY COMPARATOR
Compares source site JSON tree against local DB for a given VIN.
Identifies exact matches, fuzzy matches (& vs and), missing, and extra categories.

**Usage:**
```bash
python compare_categories.py   # uses categories_LSFAM120XNA160733.json + local DB
```

**Key logic — normalize() for fuzzy matching:**
```python
def normalize(s):
    s = re.sub(r'\s*&\s*', 'and', s)   # & → and
    s = re.sub(r'[,\s\-_/()]', '', s)
    return s.lower()
```

**Results for T90 EV:**
- 41/42 main categories matched (31 exact, 10 fuzzy)
- 1 missing main: `Pipe` (64 products on source)
- 10 extra main in local (ICE categories bleeding in)
- 11 missing sub cats, 193 extra sub cats

---

### diagnose_cat_bleed.py — ROOT CAUSE DIAGNOSTICS
Traces WHY wrong categories appear for a VIN. Run this on any VIN to understand the bleed.

**What it checks:**
1. Products at intersection of [wrong category] + [this VIN]
2. Multi-VIN product count (how many products are shared)
3. Which other VINs bleed in and how many shared products
4. Whether wrong categories are on variation or parent product

**Key finding from T90 EV run:**
- 505/1029 products are shared across multiple VINs
- LSFAM11C6RA133899 shares 418 products with T90 EV
- Wrong categories (clutch, exhaust, school bus) sit directly on the variation post

---

### fix_vin_mapping.py — SINGLE-VIN MAPPING FIX (partial — has encoding bug)
Fetches valid SKUs for one VIN from source, compares with local `sku_vin_mapping`,
removes entries that don't belong.

**Usage:**
```bash
python fix_vin_mapping.py          # dry-run
python fix_vin_mapping.py --fix    # apply
```

**Known issue:** Script uses Unicode arrow character (→) in print statements.
Windows PowerShell with cp1252 encoding raises `UnicodeEncodeError`.
**Fix:** Replace all `→` with `->` or add `import sys; sys.stdout.reconfigure(encoding='utf-8')`
at the top of the script before running on Windows.

**Strategy:**
1. Async-fetch all products under T90 EV serial category (cat id 4408) from source
2. Collect `original_sku` from product meta_data + strip hash from `.sku` field
3. Compare with local `wp_sku_vin_mapping` rows for this VIN
4. DELETE rows where SKU not in source valid set

---

### fix_all_vins.py — MASTER FIX SCRIPT (TO BE BUILT)
This is the next script to write. See "The Fix Plan" section above for full spec.

**Key design points:**
- Must load ALL 17 VINs' valid SKU sets before deleting/inserting anything
- Shared products get UNION of categories from all their valid VINs
- Must create missing `wp_terms` + `wp_term_taxonomy` rows for new categories
- Must recalculate `wp_term_taxonomy.count` after all changes
- Dry-run mode first: show all changes, write to `fix_all_vins_dryrun.txt`
- Apply mode: `python fix_all_vins.py --fix`
- Use `sys.stdout.reconfigure(encoding='utf-8')` at top to avoid cp1252 issues

---

#### check_product.py
**Purpose:** Check specific product SKU status  
**Usage:** Verifies product 224471 (hardcoded)

#### check_specific.py
**Purpose:** Check any product by ID  
**Usage:** `.\.venv\Scripts\python.exe check_specific.py 224469`  
**Features:**
- Shows product details
- Checks _sku in wp_postmeta
- Checks lookup table
- Lists all meta keys

#### check_variations.py
**Purpose:** Check if product has variations  
**Usage:** `.\.venv\Scripts\python.exe check_variations.py`  
**Features:**
- Detects variable vs simple products
- Lists all variations with SKUs

#### check_simple_products.py
**Purpose:** Find simple products without SKUs  
**Usage:** Distinguishes variable from simple products  
**Output:** Counts and lists products needing SKUs

#### check_table.py
**Purpose:** Examine table structure  
**Usage:** Shows CREATE TABLE statement and AUTO_INCREMENT status  
**Critical Finding:** Revealed missing AUTO_INCREMENT attribute

#### analyze_vehicle_mapping.py
**Purpose:** Comprehensive VIN mapping analysis  
**Usage:** `.\.venv\Scripts\python.exe analyze_vehicle_mapping.py`  
**Features:**
- Table structure
- Sample data (10 rows)
- Statistics (unique SKUs, VINs, mappings)
- Sample VIN values
- Most popular vehicles
- Vehicle compatibility examples

#### analyze_vehicles_taxonomy.py
**Purpose:** Analyze vehicles taxonomy structure  
**Usage:** `.\.venv\Scripts\python.exe analyze_vehicles_taxonomy.py`  
**Features:**
- Lists all 21 vehicle terms
- Shows hierarchy (parent/child)
- Product counts per vehicle
- Example product assignments

### Diagnostic Scripts

#### diagnose_sku_issue.py
**Purpose:** Compare local DB vs live API  
**Finding:** API returned phantom data, SKUs in custom locations

#### deep_investigation.py
**Purpose:** Found wp_sku_vin_mapping table  
**Impact:** Key discovery of custom vehicle system

#### focused_search.py
**Purpose:** Search variations/attachments for SKUs  
**Result:** Found SKUs in product slugs

### Fix Scripts

#### fix_duplicates.py
**Purpose:** Remove duplicate meta_id entries  
**Usage:** `.\.venv\Scripts\python.exe fix_duplicates.py`  
**Actions:**
- Finds duplicate meta_id values
- Detects meta_id = 0 entries
- Deletes corrupted rows
- Prepares for table repair

#### repair_table.py
**Purpose:** Add AUTO_INCREMENT to meta_id column  
**Usage:** `.\.venv\Scripts\python.exe repair_table.py`  
**Actions:**
- Alters table structure
- Sets AUTO_INCREMENT value
- Verifies changes

#### set_autoinc.py
**Purpose:** Set AUTO_INCREMENT starting value  
**Usage:** After repair_table.py if needed

#### sync_skus_auto.py ⭐ **MAIN SYNC SCRIPT**
**Purpose:** Extract SKUs from slugs and sync to database  
**Usage:** `.\.venv\Scripts\python.exe sync_skus_auto.py`  
**Performance:** 1,400+ products/second  
**Process:**
1. Finds products without _sku
2. Extracts SKUs from slugs (3 regex patterns)
3. Batch updates wp_postmeta (500 at a time)
4. Batch updates wp_wc_product_meta_lookup
5. Shows summary and next steps

**Results:**
- Run 1: 4,157 products synced
- Run 2: 4,158 products synced
- Total: 8,315+ products fixed

#### sync_vehicles_taxonomy.py ⭐ **VEHICLE SYNC SCRIPT**
**Purpose:** Sync wp_sku_vin_mapping → vehicles taxonomy  
**Usage:** `.\.venv\Scripts\python.exe sync_vehicles_taxonomy.py`  
**Process:**
1. Gets all vehicle taxonomy terms (21)
2. Gets unique vehicles from wp_sku_vin_mapping (18)
3. Fuzzy matches VIN vehicles to taxonomy terms
4. Builds SKU → Product ID mapping
5. Creates Product → Vehicles assignment list
6. Shows preview of changes
7. Asks for confirmation
8. Clears old relationships
9. Inserts new relationships (batch 500)
10. Updates term counts
11. Verifies results

**Note:** Ready to run when user provides JSON format

### Legacy Scripts (First Attempts)

- `fix_missing_skus.py` - Dry-run only version
- `fix_skus_via_api.py` - Slow synchronous API approach
- `fix_skus_fast.py` - Async API version (unused)
- `sync_skus.py` - Early batch sync with API verification (slow)
- `sync_skus_fast.py` - Optimized batch operations (unused)
- `fix_remaining.py` - Failed attempt (duplicate entry errors)
- `fix_final.py` - Multiple INSERT approaches (all failed)
- `fix_autoinc.py` - Early AUTO_INCREMENT attempt (failed)

### Documentation Scripts

#### quick_check.py
**Purpose:** Quick table status check  
**Output:** Confirms AUTO_INCREMENT present/absent

#### FINAL_STEPS.py
**Purpose:** Display completion summary and next steps  
**Output:** Shows what was done and what user needs to do

---

## Key Findings & Diagnostics

### 1. SKU Storage (Phase 1, resolved)
SKUs embedded in product slugs (`bolt-back-up-horn-C00285681` → `C00285681`).
Now synced to `wp_postmeta` (`_sku` key). `wp_sku_vin_mapping` is the join table.

### 2. Category Corruption (Phase 2, in progress)
- `wp_term_relationships` for `product_cat` are wrong for most products
- Source: import process didn't scope categories per VIN
- Result: querying products for one VIN drags in categories from all shared VINs
- The `wp_sku_vin_mapping` table itself also has wrong entries (SKUs mapped to VINs they don't belong to)

### 3. Source Site is the Authority
`original_sku` on source products = `_sku` on themed site. This is the join key.
Category hierarchy on source is correct. Use it to rebuild everything locally.

### 4. Naming Convention Difference
Source uses `and`: "Wiper and Washer"  
Local uses `&`: "Wiper & Washer"  
Use `normalize()` function for all cross-site name comparisons.

### 5. Windows Encoding Issue
Scripts with Unicode characters (arrows, etc.) in print statements fail in PowerShell
with cp1252 encoding. Add this immediately after imports in any new script:
```python
import sys
sys.stdout.reconfigure(encoding='utf-8')
```

### 6. After Any Bulk DB Change
Must regenerate WooCommerce lookup table:
WooCommerce → Status → Tools → "Regenerate product lookup table"
Also update `wp_term_taxonomy.count` in script (don't rely on WP to do it).

### 7. DB Changes History
- `LSFAM11C4RA133898` (duplicate T60 VIN) — merged 7 unique SKUs to canonical VIN, deleted, orphan taxonomy term removed. Applied to both local and live. See `db.md`.

---

## Next Steps

### Immediate — Fix Encoding Bug then Build fix_all_vins.py

1. **Add to top of any new Phase 2 script:**
   ```python
   import sys
   sys.stdout.reconfigure(encoding='utf-8')
   ```

2. **Build `fix_all_vins.py`** following the plan in "The Fix Plan" section:
   - Step 1: Fetch all 3,512 source categories concurrently (reuse get_serial_categories.py logic)
   - Step 2: For each of 17 VINs, find serial cat on source, fetch products + variations
   - Step 3: Build valid_skus_per_vin + sku_to_categories maps
   - Step 4: Clean sku_vin_mapping (remove bad entries per VIN)
   - Step 5: Create missing category terms locally
   - Step 6: DELETE + INSERT product_cat term_relationships
   - Step 7: Recalculate term counts
   - Step 8: Report

3. **Dry-run first** — write to `fix_all_vins_dryrun.txt`, review, then `--fix`

4. **After local fix** — run `compare_categories.py` for all 17 VINs to verify

5. **Deploy to live** — upload `sql_exec.php` to live site root, run equivalent fix via `sql_exec.py`

6. **Themed site** — deploy updated `functions.php` (dynamic `maxus_get_vehicle_categories()` already written)

### Verification After Fix
```bash
# Check T90 EV categories after fix
python compare_categories.py

# Then check another VIN
# Edit compare_categories.py: change VIN and JSON_FILE constants
# Run get_serial_categories.py for that VIN first if JSON not yet fetched
```

### Files to Check on New Machine
- `c:\pythonstuff\wpimportcollection\` — all Python scripts
- `c:\wamp64\www\jasonwpclone\wp-content\themes\mobex-child\functions.php` — updated with dynamic category function
- `c:\wamp64\www\jasonwpclone\wp-content\themes\mobex-child\functions.php.bak` — backup of original
- `c:\pythonstuff\wpimportcollection\db.md` — log of all DB changes made
- `c:\pythonstuff\wpimportcollection\categories_LSFAM120XNA160733.json` — T90 EV category tree from source

---
- **Pattern examples:**
  - `panel-assembly-upper-bulkhead-extension-6947-14` → SKU: `6947-14`
  - `bolt-back-up-horn-C00285681` → SKU: `C00285681`
  - `rear-lamp-B00003511` → SKU: `B00003511`

### 2. Vehicle Compatibility Disconnect
- **VIN mapping table:** Active, 25,646 mappings, theme uses this
- **Taxonomy:** Mostly empty, admin shows this
- **Result:** Counts don't match, confusion about inventory

### 3. Database Corruption
- **Issue:** Missing AUTO_INCREMENT on primary key
- **Cause:** Unknown (possibly manual alteration or migration error)
- **Impact:** Could not insert new rows, all got meta_id = 0
- **Fixed:** ALTER TABLE + cleanup

### 4. WooCommerce Caching
- Must regenerate lookup tables after bulk changes
- **Location:** WooCommerce → Status → Tools → "Regenerate product lookup table"
- Otherwise admin won't reflect database changes

### 5. Variable Product Behavior
- Parent product SKU not shown in Inventory tab (WooCommerce standard)
- SKUs only appear in Variations tab
- Can confuse users expecting to see parent SKU

---

## Git Repository

### Files to Commit
```bash
git add analyze_*.py check_*.py fix_*.py repair_*.py sync_*.py
git add get_serial_categories.py compare_categories.py
git add diagnose_cat_bleed.py fix_vin_mapping.py
git add PROJECT_DOCUMENTATION.md db.md
git add categories_LSFAM120XNA160733.json
```

### Files to .gitignore
```
.venv/
__pycache__/
*.pyc
.env
localdbcreds.txt
correctwpsite.txt
*_backup_*.sql
```

### Setup on New Machine
```bash
# 1. Install Python 3.12, install WAMP, restore maxussql DB
# 2. Clone/copy project files to c:\pythonstuff\wpimportcollection\
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install mysql-connector-python pandas aiohttp woocommerce

# 3. Test DB connection
.\.venv\Scripts\python.exe check_table.py

# 4. Test source API
.\.venv\Scripts\python.exe get_serial_categories.py
```

---

## Common Queries

### Products for a VIN
```sql
SELECT DISTINCT p.ID, p.post_title, pm.meta_value AS sku
FROM wp_posts p
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
INNER JOIN wp_sku_vin_mapping svm ON pm.meta_value = svm.sku AND svm.vin = 'LSFAM120XNA160733'
WHERE p.post_type IN ('product','product_variation') AND p.post_status = 'publish';
```

### Categories for a VIN's products
```sql
SELECT DISTINCT t.name, tt.parent, COUNT(DISTINCT tr.object_id) AS cnt
FROM wp_term_relationships tr
INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat'
INNER JOIN wp_terms t ON tt.term_id = t.term_id
WHERE tr.object_id IN (
    SELECT pm.post_id FROM wp_postmeta pm
    INNER JOIN wp_sku_vin_mapping svm ON pm.meta_value = svm.sku AND svm.vin = 'LSFAM120XNA160733'
    WHERE pm.meta_key = '_sku'
)
GROUP BY t.term_id ORDER BY tt.parent, t.name;
```

### SKUs mapped to more than one VIN
```sql
SELECT sku, COUNT(DISTINCT vin) AS vin_count, GROUP_CONCAT(vin SEPARATOR ', ') AS vins
FROM wp_sku_vin_mapping
GROUP BY sku HAVING vin_count > 1
ORDER BY vin_count DESC LIMIT 20;
```

### Recalculate term counts (run after bulk category changes)
```sql
UPDATE wp_term_taxonomy tt
SET count = (
    SELECT COUNT(*) FROM wp_term_relationships tr
    WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
)
WHERE tt.taxonomy = 'product_cat';
```

---

## Success Metrics

**Phase 1 (done):**
- [x] 8,315+ products have SKUs in wp_postmeta
- [x] SKU search working in WP admin
- [x] wp_postmeta AUTO_INCREMENT repaired
- [x] Duplicate T60 VIN cleaned up (local + live)

**Phase 2 (in progress):**
- [x] Root cause of category corruption identified
- [x] T90 EV category comparison tool built and run
- [x] Async category fetcher built (11s for all 3,512 cats)
- [ ] fix_all_vins.py built and dry-run verified
- [ ] Category fix applied to local DB
- [ ] Category fix deployed to live site
- [ ] Theme functions.php deployed to live site

---

*Last Updated: March 7, 2026*
