#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Normalize product attribute_pa_variant values to match wp_sku_vin_mapping variant_attribute format
"""
import mysql.connector
import re
import sys

# Ensure UTF-8 output
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

DB_CFG = dict(host='localhost', user='root', password='', database='maxussql')
PREFIX = 'wp_'

def normalize_variant(value):
    """Normalize variant value to match mapping table format"""
    if not value:
        return value
    
    # Pattern: left-CXXXXXXXX or right-CXXXXXXXX -> Left / Right
    if re.match(r'^(left|right)-[bc]\d', value, re.IGNORECASE):
        if value.lower().startswith('left'):
            return 'Left'
        elif value.lower().startswith('right'):
            return 'Right'
    
    # Pattern: lowercase words that should be capitalized
    # Check if this matches a mapping variant when capitalized
    return value

def main(dry_run=True):
    conn = mysql.connector.connect(**DB_CFG)
    cur = conn.cursor(dictionary=True)
    
    print("=" * 80)
    print("NORMALIZE PRODUCT VARIANT ATTRIBUTES")
    print("=" * 80)
    print(f"Mode: {'DRY RUN (no changes)' if dry_run else 'LIVE (applying changes)'}")
    print()
    
    # Get all distinct product variants
    cur.execute(f"""
        SELECT DISTINCT meta_value as product_variant
        FROM {PREFIX}postmeta
        WHERE meta_key = 'attribute_pa_variant'
        AND meta_value != ''
        ORDER BY meta_value
    """)
    product_variants = [r['product_variant'] for r in cur.fetchall()]
    print(f"Total distinct product variants: {len(product_variants)}")
    
    # Get all distinct mapping variants
    cur.execute(f"""
        SELECT DISTINCT variant_attribute
        FROM {PREFIX}sku_vin_mapping
        WHERE variant_attribute IS NOT NULL
        AND variant_attribute != ''
        ORDER BY variant_attribute
    """)
    mapping_variants = set(r['variant_attribute'] for r in cur.fetchall())
    print(f"Total distinct mapping variants: {len(mapping_variants)}")
    
    # Build normalization rules
    normalizations = {}
    
    # Rule 1: left-SKU / right-SKU patterns (fast local check)
    left_right_pattern = re.compile(r'^(left|right)-[bc]\d', re.IGNORECASE)
    for pv in product_variants:
        if left_right_pattern.match(pv):
            if pv.lower().startswith('left'):
                normalizations[pv] = 'Left'
            elif pv.lower().startswith('right'):
                normalizations[pv] = 'Right'
    
    print(f"Rule 1 (left-/right- patterns): {len(normalizations)} normalizations")
    
    # Rule 2: Case-insensitive exact matches (use mapping_variants as lookup)
    mapping_lower_map = {mv.lower(): mv for mv in mapping_variants}
    for pv in product_variants:
        if pv in normalizations:
            continue
        pv_lower = pv.lower()
        if pv_lower in mapping_lower_map and pv != mapping_lower_map[pv_lower]:
            # Avoid circular mappings (where both exist in product AND mapping)
            if mapping_lower_map[pv_lower] not in product_variants:
                normalizations[pv] = mapping_lower_map[pv_lower]
    
    print(f"Rule 2 (case-insensitive): +{len(normalizations) - len([k for k, v in normalizations.items() if v in ['Left', 'Right']])} more")
    
    # Rule 3: Skip complex matching for performance (can add later if needed)
    
    print(f"\n=== Normalization Rules ({len(normalizations)} variants will be changed) ===")
    
    # Group by change type
    left_right_changes = {k: v for k, v in normalizations.items() if v in ['Left', 'Right']}
    case_changes = {k: v for k, v in normalizations.items() if k.lower() == v.lower() and v not in ['Left', 'Right']}
    other_changes = {k: v for k, v in normalizations.items() if k not in left_right_changes and k not in case_changes}
    
    print(f"\n1. Left/Right normalization ({len(left_right_changes)} variants):")
    for old, new in sorted(left_right_changes.items())[:20]:
        print(f"   '{old}' -> '{new}'")
    if len(left_right_changes) > 20:
        print(f"   ... and {len(left_right_changes)-20} more")
    
    print(f"\n2. Case normalization ({len(case_changes)} variants):")
    for old, new in sorted(case_changes.items())[:20]:
        print(f"   '{old}' -> '{new}'")
    if len(case_changes) > 20:
        print(f"   ... and {len(case_changes)-20} more")
    
    if other_changes:
        print(f"\n3. Other normalizations ({len(other_changes)} variants):")
        for old, new in sorted(other_changes.items())[:20]:
            print(f"   '{old}' -> '{new}'")
        if len(other_changes) > 20:
            print(f"   ... and {len(other_changes)-20} more")
    
    # Count affected products
    total_affected = 0
    for old_val in normalizations.keys():
        cur.execute(f"""
            SELECT COUNT(*) as c
            FROM {PREFIX}postmeta
            WHERE meta_key = 'attribute_pa_variant'
            AND meta_value = %s
        """, (old_val,))
        count = cur.fetchone()['c']
        total_affected += count
    
    print(f"\n=== Impact ===")
    print(f"Total product/variation records to update: {total_affected}")
    
    if not dry_run:
        print(f"\n=== Applying Changes ===")
        updated = 0
        for old_val, new_val in normalizations.items():
            cur.execute(f"""
                UPDATE {PREFIX}postmeta
                SET meta_value = %s
                WHERE meta_key = 'attribute_pa_variant'
                AND meta_value = %s
            """, (new_val, old_val))
            rows = cur.rowcount
            updated += rows
            if rows > 0:
                print(f"  Updated {rows} records: '{old_val}' -> '{new_val}'")
        
        conn.commit()
        print(f"\nTotal records updated: {updated}")
        print("✅ Normalization complete!")
    else:
        print(f"\n⚠️  DRY RUN - No changes made")
        print(f"   Run with --apply to apply these changes")
    
    print("\n" + "=" * 80)
    
    cur.close()
    conn.close()

if __name__ == '__main__':
    import sys
    dry_run = '--apply' not in sys.argv
    main(dry_run=dry_run)
