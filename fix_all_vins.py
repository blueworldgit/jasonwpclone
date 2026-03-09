"""
fix_all_vins.py — Master category-fix script for ALL 17 VINs.

Fixes category corruption on the local WAMP clone (maxussql) by using the
source site (maxusvanparts.co.uk) as the single source of truth.

What it does:
  1. Fetches ALL categories from the source WooCommerce API (~3,500 cats)
  2. For each of 17 VINs, discovers the serial category on source, fetches
     all products & variations under it, and collects valid SKUs + their
     category assignments.
  3. Cleans wp_sku_vin_mapping (removes rows where SKU is not valid for that VIN)
  4. Creates any missing category terms in the local DB
  5. Rebuilds wp_term_relationships for product_cat (DELETE old + INSERT correct)
  6. Recalculates wp_term_taxonomy.count for all product_cat terms
  7. Reports all changes — dry-run by default.

Usage:
    python fix_all_vins.py              # dry-run (report only, no changes)
    python fix_all_vins.py --fix        # apply all changes

Requirements:
    pip install mysql-connector-python aiohttp
"""
import asyncio
import aiohttp
import json
import re
import sys
import time
import mysql.connector
from collections import defaultdict

sys.stdout.reconfigure(encoding='utf-8')

# Force unbuffered output on Windows
import functools
print = functools.partial(print, flush=True)

# Debug SKUs to trace (set to empty list to disable debug logging)
DEBUG_SKUS = ["B00003507", "B00004085", "B00004151"]

# ── Configuration ────────────────────────────────────────────────────────────

WP_URL   = "https://maxusvanparts.co.uk"
CK       = "ck_573295ab285b1f112436b620f6bed208b5702503"
CS       = "cs_76f262092bbd1a4d0a2dde75c6a29e4e316c1109"
CONC     = 10
PREFIX   = "wp_"
DB_CFG   = dict(host="localhost", user="root", password="", database="maxussql")
DRY_RUN  = "--fix" not in sys.argv
USE_REMOTE = "--remote" in sys.argv

# All 17 VINs from the themed site's sku_vin_mapping
ALL_VINS = [
    "LSFAM120XNA160733",
    "LSFAM11C6RA133899",
    "LSFAM11C6RA144501",
    "LSFAL11A4PA157987",
    "LSFAL11A5MA087816",
    "LSH14J7C2MA122115",
    "LSH14J7CXMA114599",
    "LSH14J7C7MA114771",
    "LSH14J7C4RV123458",
    "LSH14J7C3RV123225",
    "LSH14J7C9RV123360",
    "LSH14J4CXMA165329",
    "LSH14J4C0RV121632",
    "LSH14J7C0SA082498",
    "LSH14C4C5NA129710",
    "LSH14JTC6FA621119",
    "LSKG5GL16KA060062",
]

REPORT_FILE = "fix_all_vins_report.txt"

# ── Helpers ──────────────────────────────────────────────────────────────────

def normalize(s):
    """Normalize name for fuzzy matching (& vs and, strip punctuation)."""
    s = re.sub(r'\s*&\s*', 'and', s)
    s = re.sub(r'[,\s\-_/()]', '', s)
    return s.lower()


def extract_original_sku(item):
    """Extract original_sku from a WooCommerce product/variation's meta_data."""
    for m in item.get("meta_data", []):
        if m["key"] == "original_sku":
            return m["value"].strip()
    return None


def extract_all_skus(item):
    """Return set of all usable SKU forms from a product/variation."""
    skus = set()
    orig = extract_original_sku(item)
    if orig:
        skus.add(orig)
    sku = item.get("sku", "").strip()
    if sku:
        skus.add(sku)
        # Strip hash suffix: "C00343611-5BF312" -> "C00343611"
        if "-" in sku:
            skus.add(sku.rsplit("-", 1)[0].strip())
    return skus


def log(msg, file_handle=None):
    """Print to stdout and optionally write to report file."""
    print(msg)
    if file_handle:
        file_handle.write(msg + "\n")


# ── Async API helpers ────────────────────────────────────────────────────────

async def fetch_page(session, endpoint, params, sem):
    """Fetch one page from the WC REST API."""
    async with sem:
        async with session.get(
            f"{WP_URL}/wp-json/wc/v3/{endpoint}", params=params
        ) as r:
            r.raise_for_status()
            data = await r.json()
            total_pages = int(r.headers.get("X-WP-TotalPages", 1))
            return data, total_pages


async def fetch_all_pages(session, endpoint, base_params, sem):
    """Fetch page 1 to discover total, then fetch remaining concurrently."""
    first, total = await fetch_page(
        session, endpoint, {**base_params, "page": 1}, sem
    )
    if total == 1:
        return first
    tasks = [
        fetch_page(session, endpoint, {**base_params, "page": p}, sem)
        for p in range(2, total + 1)
    ]
    rest = await asyncio.gather(*tasks)
    return first + [item for batch, _ in rest for item in batch]


async def fetch_all_categories(session, sem):
    """Fetch every product_cat from the source site."""
    print("  Fetching all source categories...")
    cats = await fetch_all_pages(
        session, "products/categories",
        {"per_page": 100, "orderby": "id", "order": "asc"}, sem
    )
    print(f"  -> {len(cats)} categories fetched")
    return cats


async def fetch_vin_products(session, sem, cat_id, vin, valid_cat_ids=None):
    """
    Fetch all products + their variations under a source serial category.
    Returns: (valid_skus: set, sku_to_source_cats: dict)
      sku_to_source_cats maps original_sku -> set of source category IDs
      
    Args:
        valid_cat_ids: Set of category IDs that belong to this VIN's tree.
                       Used to filter out bleed-through categories.
    """
    valid_skus = set()
    sku_to_source_cats = defaultdict(set)

    # Fetch parent products
    products = await fetch_all_pages(
        session, "products",
        {"category": cat_id, "per_page": 100, "status": "publish"}, sem
    )
    print(f"    [{vin}] {len(products)} parent products")

    variable_ids = []
    for p in products:
        p_skus = extract_all_skus(p)
        valid_skus.update(p_skus)
        # Map SKU -> source category IDs (FILTERED to this VIN's tree only)
        cat_ids = {c["id"] for c in p.get("categories", [])}
        cat_ids_before = cat_ids.copy()  # For debug logging
        # CRITICAL FIX: Filter to only include categories within this VIN's tree
        if valid_cat_ids:
            cat_ids = cat_ids & valid_cat_ids  # Intersection
        
        # Use exact WooCommerce SKU (don't group by original_sku)
        sku = p.get("sku", "").strip()
        if sku:
            # DEBUG LOGGING for tracked SKUs
            if DEBUG_SKUS and sku in DEBUG_SKUS:
                cat_names_before = [c["name"] for c in p.get("categories", [])]
                print(f"\n    DEBUG [{vin}] SKU: {sku}")
                print(f"      Product ID: {p['id']}, Name: {p['name']}")
                print(f"      Categories BEFORE filter ({len(cat_ids_before)}): {cat_ids_before}")
                print(f"      Category names: {cat_names_before}")
                print(f"      Categories AFTER filter ({len(cat_ids)}): {cat_ids}")
                print(f"      Filtered out: {cat_ids_before - cat_ids}")
            
            sku_to_source_cats[sku].update(cat_ids)
        if p.get("type") == "variable":
            variable_ids.append(p["id"])

    # Fetch variations in chunks
    all_variations = []
    chunk_size = 50
    for i in range(0, len(variable_ids), chunk_size):
        chunk = variable_ids[i:i + chunk_size]
        tasks = [
            fetch_all_pages(
                session, f"products/{pid}/variations",
                {"per_page": 100, "status": "publish"}, sem
            )
            for pid in chunk
        ]
        results = await asyncio.gather(*tasks)
        for vlist in results:
            all_variations.extend(vlist)

    print(f"    [{vin}] {len(all_variations)} variations")

    for v in all_variations:
        v_skus = extract_all_skus(v)
        valid_skus.update(v_skus)
        # Use exact WooCommerce SKU for variations
        sku = v.get("sku", "").strip()
        if sku:
            # Variations inherit parent's categories — look up parent
            # The API doesn't return categories on variations, they inherit
            # from the parent. We already captured those above.
            pass  # Categories already collected from parent products above

    return valid_skus, sku_to_source_cats


# ── Main logic ───────────────────────────────────────────────────────────────

async def main():
    t0 = time.perf_counter()

    mode_label = "DRY RUN (use --fix to apply)" if DRY_RUN else "*** LIVE FIX ***"
    print(f"\n{'='*70}")
    print(f"  fix_all_vins.py — Master category fix for all {len(ALL_VINS)} VINs")
    print(f"  Mode: {mode_label}")
    print(f"{'='*70}\n")

    report = open(REPORT_FILE, "w", encoding="utf-8")

    auth = aiohttp.BasicAuth(CK, CS)
    connector = aiohttp.TCPConnector(limit=CONC, ssl=True)
    timeout = aiohttp.ClientTimeout(total=300)
    sem = asyncio.Semaphore(CONC)

    async with aiohttp.ClientSession(
        auth=auth, connector=connector, timeout=timeout
    ) as session:

        # ══════════════════════════════════════════════════════════════════
        # STEP 1: Fetch all source categories
        # ══════════════════════════════════════════════════════════════════
        log("STEP 1: Fetching all source categories...", report)
        all_cats = await fetch_all_categories(session, sem)
        by_id = {c["id"]: c for c in all_cats}
        children = defaultdict(list)
        for c in all_cats:
            children[c["parent"]].append(c)
        log(f"  Total source categories: {len(all_cats)}", report)

        # ══════════════════════════════════════════════════════════════════
        # STEP 2: For each VIN, find serial cat + fetch valid SKUs
        # ══════════════════════════════════════════════════════════════════
        log("\nSTEP 2: Fetching valid SKUs for each VIN from source...", report)

        # valid_skus_per_vin[vin] = set of valid SKUs
        valid_skus_per_vin = {}
        # sku_cats_per_vin[vin] = { sku: set(source_cat_ids) }
        sku_cats_per_vin = {}
        # serial_cat_tree[vin] = { main_cats: [...], sub_cats: [...] }
        serial_cat_tree = {}
        vins_not_found = []

        for vin in ALL_VINS:
            # Find serial category on source
            serial_cat = None
            for c in all_cats:
                if c["name"].upper() == vin.upper() or c["slug"].upper() == vin.lower():
                    serial_cat = c
                    break

            if not serial_cat:
                log(f"  WARNING: No serial category found for VIN {vin} on source!", report)
                vins_not_found.append(vin)
                continue

            log(f"\n  VIN: {vin}  (source cat id: {serial_cat['id']})", report)

            # Get main + sub categories under this serial
            main_cats = children.get(serial_cat["id"], [])
            sub_cats = []
            for mc in main_cats:
                sub_cats.extend(children.get(mc["id"], []))

            serial_cat_tree[vin] = {
                "serial_cat_id": serial_cat["id"],
                "main_cats": main_cats,
                "sub_cats": sub_cats,
            }
            log(f"    Source tree: {len(main_cats)} main, {len(sub_cats)} sub categories", report)

            # Build set of valid category IDs for this VIN (to filter bleed-through)
            valid_cat_ids = {serial_cat["id"]}  # Include the serial category itself
            for mc in main_cats:
                valid_cat_ids.add(mc["id"])
                for sc in children.get(mc["id"], []):
                    valid_cat_ids.add(sc["id"])

            # Fetch products + variations
            valid_skus, sku_source_cats = await fetch_vin_products(
                session, sem, serial_cat["id"], vin, valid_cat_ids
            )
            valid_skus_per_vin[vin] = valid_skus
            sku_cats_per_vin[vin] = sku_source_cats
            log(f"    Valid SKUs: {len(valid_skus)}", report)

    fetch_time = time.perf_counter() - t0
    log(f"\n  Source data fetch complete in {fetch_time:.1f}s", report)

    # ══════════════════════════════════════════════════════════════════════
    # STEP 3: Connect to local DB and clean sku_vin_mapping
    # ══════════════════════════════════════════════════════════════════════
    log(f"\n{'='*70}", report)
    log("STEP 3: Cleaning sku_vin_mapping...", report)
    log(f"{'='*70}", report)

    if USE_REMOTE:
        log("  Connecting to remote SQL endpoint...", report)
        from sql_exec import RemoteSQL
        conn = RemoteSQL()
    else:
        log("  Connecting to local database...", report)
        conn = mysql.connector.connect(**DB_CFG)
    cur = conn.cursor(dictionary=True)

    total_removed = 0
    vin_removal_details = {}

    for vin in ALL_VINS:
        if vin in vins_not_found:
            continue

        valid = valid_skus_per_vin[vin]

        cur.execute(
            f"SELECT sku FROM {PREFIX}sku_vin_mapping WHERE vin = %s", (vin,)
        )
        local_skus = {r["sku"] for r in cur.fetchall()}
        to_remove = local_skus - valid
        to_keep = local_skus & valid

        vin_removal_details[vin] = {
            "local": len(local_skus),
            "keep": len(to_keep),
            "remove": len(to_remove),
        }

        log(f"\n  {vin}: {len(local_skus)} local, {len(to_keep)} valid, {len(to_remove)} to remove", report)

        if to_remove and not DRY_RUN:
            remove_list = list(to_remove)
            chunk_size = 500
            removed = 0
            for i in range(0, len(remove_list), chunk_size):
                chunk = remove_list[i:i + chunk_size]
                ph = ",".join(["%s"] * len(chunk))
                cur.execute(
                    f"DELETE FROM {PREFIX}sku_vin_mapping WHERE vin = %s AND sku IN ({ph})",
                    (vin, *chunk),
                )
                removed += cur.rowcount
            total_removed += removed
            log(f"    -> Deleted {removed} rows", report)

    if not DRY_RUN:
        conn.commit()
    log(f"\n  Total sku_vin_mapping rows to remove: {sum(d['remove'] for d in vin_removal_details.values())}", report)

    # ══════════════════════════════════════════════════════════════════════
    # STEP 4: Build source-to-local category name mapping & create missing
    # ══════════════════════════════════════════════════════════════════════
    log(f"\n{'='*70}", report)
    log("STEP 4: Mapping source categories to local terms + creating missing...", report)
    log(f"{'='*70}", report)

    # Get all local product_cat terms
    cur.execute(f"""
        SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id, tt.parent, tt.count
        FROM {PREFIX}terms t
        INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id
        WHERE tt.taxonomy = 'product_cat'
    """)
    local_cats = cur.fetchall()

    # Build lookup: normalized_name -> local term row
    local_by_norm = {}
    local_by_id = {}
    for lc in local_cats:
        local_by_norm[normalize(lc["name"])] = lc
        local_by_id[lc["term_id"]] = lc

    # Collect all source main + sub category names we need locally
    # source_cat_id -> { name, parent_name, parent_source_id }
    source_cats_needed = {}
    for vin in ALL_VINS:
        if vin not in serial_cat_tree:
            continue
        tree = serial_cat_tree[vin]
        for mc in tree["main_cats"]:
            if mc["id"] not in source_cats_needed:
                source_cats_needed[mc["id"]] = {
                    "name": mc["name"],
                    "parent_name": None,
                    "parent_source_id": None,
                    "is_main": True,
                }
            for sc in children.get(mc["id"], []):
                if sc["id"] not in source_cats_needed:
                    source_cats_needed[sc["id"]] = {
                        "name": sc["name"],
                        "parent_name": mc["name"],
                        "parent_source_id": mc["id"],
                        "is_main": False,
                    }

    # Map source_cat_id -> local_term_id (by fuzzy name match)
    source_to_local = {}  # source_cat_id -> local term_id
    missing_cats = []  # source cats with no local match

    # First pass: map main categories
    for src_id, info in source_cats_needed.items():
        if not info["is_main"]:
            continue
        norm = normalize(info["name"])
        if norm in local_by_norm:
            source_to_local[src_id] = local_by_norm[norm]["term_id"]
        else:
            missing_cats.append(src_id)

    # Second pass: map sub categories (need parent mapped first)
    for src_id, info in source_cats_needed.items():
        if info["is_main"]:
            continue
        norm = normalize(info["name"])
        # Try to find by name, scoped to correct parent
        parent_local_id = source_to_local.get(info["parent_source_id"])
        found = False
        if parent_local_id is not None:
            # Look for a local cat with this name AND this parent
            for lc in local_cats:
                if normalize(lc["name"]) == norm and lc["parent"] == parent_local_id:
                    source_to_local[src_id] = lc["term_id"]
                    found = True
                    break
        if not found:
            # Fallback: just match by name
            if norm in local_by_norm:
                source_to_local[src_id] = local_by_norm[norm]["term_id"]
            else:
                missing_cats.append(src_id)

    log(f"\n  Source categories mapped to local: {len(source_to_local)}", report)
    log(f"  Source categories missing locally: {len(missing_cats)}", report)

    # Deduplicate missing categories by (name, parent_local_id) to avoid creating duplicates
    # Multiple source IDs can refer to the same category name (e.g., "Pipe" appears under different VINs)
    unique_missing = {}  # (name, parent_local_id) -> [list of source_ids that need this]
    for src_id in missing_cats:
        info = source_cats_needed[src_id]
        parent_local_id = 0
        if info["parent_source_id"] and info["parent_source_id"] in source_to_local:
            parent_local_id = source_to_local[info["parent_source_id"]]
        key = (info["name"], parent_local_id)
        if key not in unique_missing:
            unique_missing[key] = []
        unique_missing[key].append(src_id)

    log(f"  Unique categories to create (after deduplication): {len(unique_missing)}", report)

    # Create missing categories
    created_count = 0
    for (cat_name, parent_local_id), src_ids in unique_missing.items():
        cat_slug = re.sub(r'[^a-z0-9]+', '-', cat_name.lower()).strip('-')

        log(f"    Creating: '{cat_name}' (slug: {cat_slug}, parent: {parent_local_id})", report)

        if not DRY_RUN:
            # Insert into wp_terms
            cur.execute(
                f"INSERT INTO {PREFIX}terms (name, slug, term_group) VALUES (%s, %s, 0)",
                (cat_name, cat_slug),
            )
            new_term_id = cur.lastrowid

            # Insert into wp_term_taxonomy
            cur.execute(
                f"""INSERT INTO {PREFIX}term_taxonomy
                    (term_id, taxonomy, description, parent, count)
                    VALUES (%s, 'product_cat', '', %s, 0)""",
                (new_term_id, parent_local_id),
            )

            # Map ALL source IDs that refer to this category to the new term_id
            for src_id in src_ids:
                source_to_local[src_id] = new_term_id

            # Add to local lookups
            local_by_norm[normalize(cat_name)] = {
                "term_id": new_term_id,
                "name": cat_name,
                "slug": cat_slug,
                "parent": parent_local_id,
            }
            local_by_id[new_term_id] = local_by_norm[normalize(cat_name)]
            created_count += 1
        else:
            # In dry-run, still map all source IDs to a placeholder term_id for consistency
            # This ensures the rest of the dry-run logic works correctly
            placeholder_term_id = 99999 + created_count
            for src_id in src_ids:
                source_to_local[src_id] = placeholder_term_id
            created_count += 1

    if not DRY_RUN and created_count:
        conn.commit()
    log(f"  Categories created: {created_count}", report)

    # ══════════════════════════════════════════════════════════════════════
    # STEP 5: Build the correct product -> category assignments
    # ══════════════════════════════════════════════════════════════════════
    log(f"\n{'='*70}", report)
    log("STEP 5: Building correct product_cat assignments...", report)
    log(f"{'='*70}", report)

    # Build SKU -> set of local term_ids (UNION across all VINs)
    # A product's correct categories = union of source categories from ALL its valid VINs
    sku_to_local_cats = defaultdict(set)

    for vin in ALL_VINS:
        if vin not in sku_cats_per_vin:
            continue
        for sku, src_cat_ids in sku_cats_per_vin[vin].items():
            for src_cat_id in src_cat_ids:
                local_id = source_to_local.get(src_cat_id)
                if local_id:
                    sku_to_local_cats[sku].add(local_id)
                    # Also add the parent (main category) if we mapped a sub
                    if local_id in local_by_id:
                        parent_id = local_by_id[local_id].get("parent", 0)
                        if parent_id and parent_id in local_by_id:
                            sku_to_local_cats[sku].add(parent_id)

    log(f"  SKUs with category assignments: {len(sku_to_local_cats)}", report)

    # Get SKU -> product_id mapping from local DB
    cur.execute(f"""
        SELECT pm.post_id, pm.meta_value AS sku
        FROM {PREFIX}postmeta pm
        INNER JOIN {PREFIX}posts p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_sku' AND pm.meta_value != ''
          AND p.post_type IN ('product', 'product_variation')
          AND p.post_status = 'publish'
    """)
    sku_to_post_ids = defaultdict(set)
    for row in cur.fetchall():
        sku_to_post_ids[row["sku"]].add(row["post_id"])

    # Build post_id -> set of local term_ids
    # Source SKUs may have hash suffixes (B00003507-5BF312)
    # Local SKUs are base format (B00003507)
    # Strip hash from source SKU to match local
    post_to_cats = defaultdict(set)
    matched_skus = 0
    for source_sku, local_term_ids in sku_to_local_cats.items():
        # Strip hash suffix if present: "B00003507-5BF312" -> "B00003507"
        base_sku = source_sku.rsplit("-", 1)[0] if "-" in source_sku else source_sku
        
        post_ids = sku_to_post_ids.get(base_sku, set())
        if post_ids:
            matched_skus += 1
            for pid in post_ids:
                post_to_cats[pid].update(local_term_ids)

    log(f"  SKUs matched to local products: {matched_skus}", report)
    log(f"  Products to update: {len(post_to_cats)}", report)

    # Also need parent products — variations' categories should also be on the parent
    cur.execute(f"""
        SELECT ID, post_parent FROM {PREFIX}posts
        WHERE post_type = 'product_variation' AND post_status = 'publish'
          AND post_parent > 0
    """)
    variation_parents = {}
    for row in cur.fetchall():
        variation_parents[row["ID"]] = row["post_parent"]

    # Propagate variation categories to parent product
    for var_id, parent_id in variation_parents.items():
        if var_id in post_to_cats:
            post_to_cats[parent_id].update(post_to_cats[var_id])

    log(f"  Products after parent propagation: {len(post_to_cats)}", report)

    # Remove variations from post_to_cats - categories should ONLY be on parents
    # Variations inherit categories from parents in WooCommerce
    for var_id in variation_parents.keys():
        post_to_cats.pop(var_id, None)
    
    log(f"  Products to write (parents only): {len(post_to_cats)}", report)

    # ══════════════════════════════════════════════════════════════════════
    # STEP 6: Rebuild wp_term_relationships for product_cat
    # ══════════════════════════════════════════════════════════════════════
    log(f"\n{'='*70}", report)
    log("STEP 6: Rebuilding product_cat term_relationships...", report)
    log(f"{'='*70}", report)

    # Get term_taxonomy_id for each term_id (needed for wp_term_relationships)
    cur.execute(f"""
        SELECT term_id, term_taxonomy_id
        FROM {PREFIX}term_taxonomy
        WHERE taxonomy = 'product_cat'
    """)
    term_to_tt = {r["term_id"]: r["term_taxonomy_id"] for r in cur.fetchall()}

    affected_post_ids = list(post_to_cats.keys())
    log(f"  Posts to rewrite: {len(affected_post_ids)}", report)

    if affected_post_ids:
        if not DRY_RUN:
            # First, delete ALL category relationships from variations (they inherit from parents)
            cur.execute(f"""
                DELETE tr FROM {PREFIX}term_relationships tr
                INNER JOIN {PREFIX}posts p ON tr.object_id = p.ID
                INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE p.post_type = 'product_variation'
                  AND tt.taxonomy = 'product_cat'
            """)
            var_deleted = cur.rowcount
            log(f"  Deleted {var_deleted} category relationships from all variations", report)
            
            # DELETE existing product_cat relationships for affected posts (parents)
            chunk_size = 500
            deleted = 0
            for i in range(0, len(affected_post_ids), chunk_size):
                chunk = affected_post_ids[i:i + chunk_size]
                ph = ",".join(["%s"] * len(chunk))
                # Only delete product_cat relationships, preserve other taxonomies
                cur.execute(f"""
                    DELETE tr FROM {PREFIX}term_relationships tr
                    INNER JOIN {PREFIX}term_taxonomy tt
                        ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE tt.taxonomy = 'product_cat'
                      AND tr.object_id IN ({ph})
                """, tuple(chunk))
                deleted += cur.rowcount
            log(f"  Deleted {deleted} old product_cat relationships", report)

            # INSERT new correct relationships
            inserted = 0
            batch = []
            for post_id, term_ids in post_to_cats.items():
                for tid in term_ids:
                    tt_id = term_to_tt.get(tid)
                    if tt_id:
                        batch.append((post_id, tt_id, 0))
                    else:
                        log(f"  WARNING: No term_taxonomy_id for term_id {tid}", report)

                # Flush batch periodically
                if len(batch) >= 5000:
                    cur.executemany(
                        f"""INSERT IGNORE INTO {PREFIX}term_relationships
                            (object_id, term_taxonomy_id, term_order)
                            VALUES (%s, %s, %s)""",
                        batch,
                    )
                    inserted += cur.rowcount
                    batch = []

            if batch:
                cur.executemany(
                    f"""INSERT IGNORE INTO {PREFIX}term_relationships
                        (object_id, term_taxonomy_id, term_order)
                        VALUES (%s, %s, %s)""",
                    batch,
                )
                inserted += cur.rowcount

            conn.commit()
            log(f"  Inserted {inserted} new product_cat relationships", report)
        else:
            # Dry run stats
            total_new = sum(len(tids) for tids in post_to_cats.values())
            log(f"  Would delete and re-insert ~{total_new} product_cat relationships", report)

    # ══════════════════════════════════════════════════════════════════════
    # STEP 7: Recalculate wp_term_taxonomy.count
    # ══════════════════════════════════════════════════════════════════════
    log(f"\n{'='*70}", report)
    log("STEP 7: Recalculating term counts...", report)
    log(f"{'='*70}", report)

    if not DRY_RUN:
        cur.execute(f"""
            UPDATE {PREFIX}term_taxonomy tt
            SET count = (
                SELECT COUNT(*)
                FROM {PREFIX}term_relationships tr
                WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
            )
            WHERE tt.taxonomy = 'product_cat'
        """)
        conn.commit()
        log(f"  Updated {cur.rowcount} term counts", report)
    else:
        log("  (skipped in dry-run mode)", report)

    # ══════════════════════════════════════════════════════════════════════
    # STEP 8: Summary report
    # ══════════════════════════════════════════════════════════════════════
    elapsed = time.perf_counter() - t0
    log(f"\n{'='*70}", report)
    log("SUMMARY", report)
    log(f"{'='*70}", report)
    log(f"  Mode           : {'DRY RUN' if DRY_RUN else 'APPLIED'}", report)
    log(f"  Total time     : {elapsed:.1f}s", report)
    log(f"  VINs processed : {len(ALL_VINS) - len(vins_not_found)}/{len(ALL_VINS)}", report)
    if vins_not_found:
        log(f"  VINs not found : {', '.join(vins_not_found)}", report)
    log("", report)
    log("  sku_vin_mapping changes:", report)
    for vin, d in vin_removal_details.items():
        if d["remove"] > 0:
            log(f"    {vin}: -{d['remove']} rows (kept {d['keep']}/{d['local']})", report)
    log(f"    Total removed: {sum(d['remove'] for d in vin_removal_details.values())}", report)
    log("", report)
    log(f"  Categories created : {created_count}", report)
    log(f"  Products updated   : {len(post_to_cats)}", report)
    log("", report)

    if DRY_RUN:
        log("  To apply these changes, run: python fix_all_vins.py --fix", report)
    else:
        log("  IMPORTANT: Run WooCommerce -> Status -> Tools -> 'Regenerate product lookup table'", report)
        log("  on the WordPress admin after this fix.", report)

    report.close()
    cur.close()
    conn.close()
    log(f"\n  Report saved to: {REPORT_FILE}", None)
    print("\nDone.")


asyncio.run(main())
