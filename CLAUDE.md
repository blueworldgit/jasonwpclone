# Project instructions for Claude Code

This is a LocalWP WordPress development site.

Environment:
- LocalWP on Windows
- Local development only
- NOT production

Rules:
- Only modify files inside wp-content/ unless explicitly instructed otherwise
- Never modify wp-config.php, .env files, or server configs unless explicitly asked
- Never touch production URLs, API keys, secrets, or credentials
- Never enable or configure live payment gateways
- Never enable real outbound email

Preferred workflows:
- Prefer WP-CLI for content, options, users, and database changes
- Prefer editing existing plugins/themes over creating new ones unless asked
- Keep changes minimal and explain what was changed

Safety:
- Assume this site may be restored from production data
- Disable outgoing email and payments in development
- Do not perform destructive database actions unless explicitly requested

Confirmation:
- If an instruction is ambiguous or risky, ask before acting

---

## Database Access

```python
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 10005,
    'user': 'root',
    'password': 'root',
    'database': 'local',
    'charset': 'utf8mb4'
}
```

## Site Details

- **Live site:** https://maxusvanparts.co.uk
- **Local site:** https://maxusvanparts.local
- **Uploads directory:** `C:\Users\JasonPink\Local Sites\maxusvanparts\app\public\wp-content\uploads`

## Completed Work

### Product Diagram Image Linking (January 2026)

**Problem:** Imported WooCommerce products (~20,337) were missing their diagram thumbnail images. Each diagram shows multiple parts with callout numbers - many products share the same diagram image.

**Key insight:** Products don't follow a simple SKU-to-image naming pattern. B00 SKU products often use C00 diagram images. The only reliable source of truth is the live site's actual product-to-image mappings.

**Solution:** Created `scrape_and_link_diagrams.py` which:
1. Queries local products missing valid `_thumbnail_id` attachments
2. Scrapes each product page from live site (maxusvanparts.co.uk)
3. Extracts image URL from schema markup/og:image/wp-post-image
4. Matches to existing local attachment by filename (or downloads if missing)
5. Updates `_thumbnail_id` meta for each product

**Results:**
- Products linked: 19,632
- Images downloaded: 132
- Not found on live: 678
- Errors: 27
- Final products with valid thumbnails: 34,449

**Scripts created in project root (`C:\Users\JasonPink\Local Sites\maxusvanparts\`):**
- `scrape_and_link_diagrams.py` - Main solution (scrape live + link)
- `scrape_diagrams.py` - Download-only version
- `debug_diagrams.py` - Diagnostic script for thumbnail analysis
- `fix_product_diagrams.py` - Earlier SKU-based approach (partial success)
- `check_live_products.py` - Script to analyze live site mappings

### SVG Diagram Callout Highlighting (January 2026)

**Problem:** Product pages display SVG diagrams with callout numbers, but users couldn't easily identify which part in the diagram corresponds to the current product or parts in the related parts table.

**Solution:** Added interactive SVG highlighting to `wp-content/themes/mobex-child/related-parts.php`:

**Features:**
- Current product's callout number highlighted with orange circle and pulsing ring
- Leader line connecting callout to part is highlighted
- Part geometry in diagram is highlighted with semi-transparent stroke
- Hovering over table rows highlights that callout instead (red color)
- Only ONE callout highlighted at a time - previous highlights clear automatically
- Auto-scrolls diagram to show highlighted callout

**Technical implementation:**
- `getTextPosition()` - Parses SVG transform matrices to get actual text coordinates
- `findConnectedLines()` - Finds leader lines connected to callout text (legend-style matching with tight Y tolerance of 4px)
- `findPartGeometry()` - Finds part shapes at the end of leader lines (skips `<line>` elements, only matches `<path>` elements)
- Direct element reference tracking for reliable highlight clearing (stores references in arrays instead of querying by class)
- CSS classes with `!important` for highlighting: `.svg-highlight-line`, `.svg-highlight-part` (orange), `.svg-highlight-line-hover`, `.svg-highlight-part-hover` (red)

**Key challenges solved:**
- SVG text elements use transform matrices - getBBox() returns local coordinates, needed to parse matrix for actual position
- Leader lines end to the LEFT of text in legend-style diagrams
- Diagonal lines have large bounding boxes - filtered out `<line>` elements when finding parts
- Highlight clearing was unreliable with querySelectorAll - switched to direct element references

**Files:**
- `wp-content/themes/mobex-child/related-parts.php` - Main implementation
- `wp-content/uploads/diagrams/*.svg` - SVG diagram files

### Vehicle-Specific URL Structure (January 2026)

**Problem:** Users needed to browse parts filtered by their specific vehicle (e.g., E Deliver 9) rather than seeing all parts for all vehicles.

**Solution:** Created vehicle-specific URL structure with custom rewrite rules and templates:

**URL Structure:**
- `/e-deliver-9/` - Vehicle landing page (shows all categories with parts for this vehicle)
- `/e-deliver-9/brakes/` - Category page (shows subcategories/diagrams)
- `/e-deliver-9/brakes/brake-modulator/` - Subcategory page (shows parts list with SVG diagram)
- `/e-deliver-9/product/part-name/` - Product page (single product with vehicle context)

**Files created:**
- `wp-content/themes/mobex-child/vehicle-landing.php`
- `wp-content/themes/mobex-child/vehicle-category.php`
- `wp-content/themes/mobex-child/vehicle-subcategory.php`
- `wp-content/themes/mobex-child/vehicle-product.php`

**Key functions added to functions.php:**
- `maxus_get_vehicle_vins()` - Maps vehicle slugs to VIN data
- `maxus_vehicle_rewrite_rules()` - Registers URL rewrite rules
- `maxus_vehicle_templates()` - Loads correct template based on URL

**Database table:**
- `wp_sku_vin_mapping` - Maps SKUs to VINs (25,646 rows after cleanup, 8,355 unique SKUs, 18 VINs)
- Columns: id, sku, vin, vin_pattern, vehicle_name, vehicle_year, variant_attribute

**Variant Parts Issue - RESOLVED (January 2026):**
Source HTML used `class="parts-item dn"` for parts that don't apply to a specific vehicle. The `extract_variant_attributes.py` script was run to:
- Remove 78,381 incorrect SKU-VIN mappings (hidden parts with `dn` class)
- Add `variant_attribute` column with values like "FWD", "RWD", "Euro 5/6", etc.
- Result: Only applicable parts now show for each vehicle

See `C:\Users\JasonPink\Local Sites\maxusvanparts\VARIANT_ISSUE_NOTES.md` for full investigation notes.

### Variable Products Implementation (COMPLETED - January 2026)

**Goal:** Convert simple products to WooCommerce variable products for automatic VIN-based filtering.

**Specification:** See `C:\Users\JasonPink\Local Sites\maxusvanparts\SPECIFICATION.md`

**Migration Results:**
- Variable Products Created: 5,000
- Simple Products Remaining: 2,303
- Product Variations: 24,809
- Total Purchasable Items: 27,112

**Scripts:**
- `create_variable_products.py` - Restructures simple products into variable products by category+callout grouping

**Status:**
- Phase 1 (Variable Products): COMPLETED - All multi-SKU callout groups converted
- Phase 2 (Template Updates): COMPLETED - Templates updated to handle variations
- Phase 3 (VIN/Reg Lookup): Pending

**Template Updates:**
- `vehicle-subcategory.php` - Updated to query both products and product_variations
- `vehicle-category.php` - Updated part counting for variations
- Add-to-cart links now handle variations correctly
- Product URLs link to parent variable product with variation_id parameter
