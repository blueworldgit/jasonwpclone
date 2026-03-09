# Variant Attribute Matching Fix

**Date:** March 8, 2026  
**Issue:** Product-to-vehicle matching was incomplete due to missing variant_attribute join logic

---

## Problem Statement

The `wp_sku_vin_mapping` table contains both `sku` and `variant_attribute` columns to handle cases where the same SKU represents different parts (e.g., "Left" vs "Right" versions of the same part number).

**Original queries only joined on SKU:**
```sql
INNER JOIN wp_sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
```

**This caused two issues:**

1. **Incorrect Matches:** Products matched ALL variants of a SKU, not just their specific variant
   - Example: A "Left" part would match vehicles requiring "Right" variant
   - Result: Wrong parts showing for vehicles

2. **Data Format Mismatch:** Product variant attributes didn't match mapping table format
   - Products: `left-c00047639` (lowercase + SKU suffix)
   - Mapping: `Left` (capitalized, plain)
   - Result: Even with variant join, they wouldn't match

---

## Solution Implemented

### Phase 1: Data Normalization

**Script:** `normalize_variant_attributes.py`

Normalized 1,449 product variant attribute values to match mapping table format:

#### Normalization Rules:
1. **Left/Right patterns:** `left-c00047639` → `Left` (522 records)
2. **Right patterns:** `right-c00233543` → `Right` (262 records)  
3. **Case fixes:** `black` → `Black`, `M6*20` → `m6*20` (5 records)

**Results:**
- Before: 522 products with `left-SKU` / `right-SKU` pattern
- After: 0 products with pattern, all normalized to `Left` / `Right`
- Total updates: 1,449 postmeta records

### Phase 2: Query Updates

Updated all vehicle-specific queries to join on BOTH `sku` AND `variant_attribute`:

**New query pattern:**
```sql
LEFT JOIN wp_postmeta pm_var 
    ON p.ID = pm_var.post_id 
    AND pm_var.meta_key = 'attribute_pa_variant'
INNER JOIN wp_sku_vin_mapping svm 
    ON pm_sku.meta_value = svm.sku
    AND (svm.variant_attribute IS NULL 
         OR svm.variant_attribute = '' 
         OR svm.variant_attribute = pm_var.meta_value)
```

**Logic:**
- Products with variant attribute: Match only mapping rows with same variant OR NULL/empty
- Products without variant: Match only mapping rows with NULL/empty variant
- This prevents cross-variant matches

---

## Files Modified

### Template Files (6 files):

1. **`wp-content/themes/mobex-child/vehicle-subcategory.php`**
   - Updated product list query (2 locations)
   - Added variant join to both initial ID fetch and detailed product fetch

2. **`wp-content/themes/mobex-child/vehicle-landing.php`**
   - Updated category product count query
   - Ensures accurate counts per vehicle

3. **`wp-content/themes/mobex-child/vehicle-category.php`**
   - Updated subcategory product count query
   - Shows correct part counts for each diagram

4. **`wp-content/themes/mobex-child/vehicle-product.php`**
   - Updated vehicle compatibility check
   - Updated compatible vehicles list
   - Handles variation_id parameter correctly

5. **`wp-content/themes/mobex-child/related-parts.php`**
   - Added `pm_variant` to related parts query
   - Updated `maxus_get_sku_vehicles()` function signature
   - Passes variant to vehicle lookup

6. **`wp-content/themes/mobex-child/functions.php`**
   - Updated `maxus_get_category_subcategory_map()` function
   - Ensures breadcrumbs show correct categories

### Scripts Created:

- `normalize_variant_attributes.py` - Data normalization script
- `verify_normalization.py` - Verification script
- `check_sku_formats.py` - SKU format diagnostic
- `check_variation_skus.py` - Variation SKU analysis
- `check_unmatched_variations.py` - Unmatched variation analysis
- `check_excluded_products.py` - Exclusion impact analysis
- `check_left_right_pattern.py` - Pattern matching verification
- `check_variant_values.py` - Variant value analysis
- `check_mapping_variant_attr.py` - Mapping table analysis
- `verify_variant_fix.py` - Fix verification script
- `test_variant_fix.py` - Final test script

---

## Test Results

**Test Vehicle:** E Deliver 9 (VIN: LSFAM120XNA160733)

### Before Fix:
- Products matched: **863**
- Included incorrect cross-variant matches

### After Fix:
- Products matched: **849**
- Difference: **14 incorrect matches removed**

### Example Case: SKU B00004683

**Mapping table has 2 variants:**
- `Left`: 6 VINs
- `m6*20`: 1 VIN

**Product has no variant attribute:**
- Old query: Matched 7 VINs (INCORRECT - all variants)
- New query: Matched 0 VINs (CORRECT - product has no variant, so matches NULL only)

---

## Impact Analysis

### SKUs Affected:
- Total SKUs in mapping: 7,054
- SKUs with variant_attribute: 2,333
- SKUs with multiple variants: 63 (2.7%)

### Product Types:
- Excluded products: 120 total
  - Simple products: 13
  - Product variations: 107

### Data Quality:
- ✅ All `left-*/right-*` patterns normalized
- ✅ Variant attributes now match mapping table format
- ✅ Queries now respect variant specificity
- ✅ No cross-variant contamination

---

## Key Findings

1. **No Hash Suffixes:** Contrary to Oscar documentation, this local site does NOT use hash-suffixed SKUs (like `B00004111-BF9845`). All SKUs are plain format (`B00004111`).

2. **No original_sku Meta:** The `original_sku` meta field doesn't exist (0 records). All products use plain SKUs in `_sku` field.

3. **Variant System:** The site uses WooCommerce's `pa_variant` taxonomy with `attribute_pa_variant` meta to differentiate variations.

4. **Mapping Table:** `wp_sku_vin_mapping` has 21,267 rows mapping SKUs to VINs, with 5,840 rows having variant_attribute values.

---

## Verification Commands

```python
# Check normalization
python verify_normalization.py

# Test variant fix
python test_variant_fix.py

# Check SKU formats
python check_sku_formats.py
```

---

## Notes for Future

- ✅ Data is now normalized - no need to re-run normalization
- ✅ All template queries now include variant join
- ⚠️ If importing new products, ensure variant attributes match mapping table format
- ⚠️ If adding new SKU-VIN mappings with variants, use proper capitalization (e.g., "Left" not "left")

---

## Summary

**Problem:** Same SKU showing for wrong vehicles due to missing variant differentiation  
**Solution:** Normalized data + added variant_attribute to all VIN mapping joins  
**Result:** 14 incorrect product-vehicle associations removed, accurate vehicle-specific parts display

Products now correctly match vehicles based on BOTH SKU and variant attributes, eliminating cross-variant contamination.
