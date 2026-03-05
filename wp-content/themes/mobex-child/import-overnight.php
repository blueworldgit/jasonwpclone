<?php
/**
 * Overnight Import - One Vehicle (~1700 products)
 * Timed to complete in approximately 12 hours
 *
 * Run via: wp eval-file wp-content/themes/mobex-child/import-overnight.php
 */

// Increase limits for long-running script
set_time_limit(0);
ini_set('memory_limit', '512M');

if (!function_exists('wp_upload_bits')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}

if (!class_exists('WC_Product')) {
    echo "WooCommerce not loaded!\n";
    exit;
}

function log_progress($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
    flush();
}

log_progress("=== OVERNIGHT IMPORT STARTED ===");
log_progress("Target: ~1700 products over 12 hours");

$base_url = 'https://maxusparts.co.uk';

// Import Deliver 9 2021 (serial LSH14J7C2MA122115)
$vehicle_serial = 'LSH14J7C2MA122115';
$vehicle_name = 'Maxus Deliver 9 (2021)';
$vehicle_page_id = '612';

// Calculate delay to spread over 12 hours
// 12 hours = 43200 seconds / 1700 products = ~25 seconds per product
// But fetching takes ~10-15 sec, so add ~10 sec delay
$delay_between_products = 10; // seconds

log_progress("Vehicle: $vehicle_name");
log_progress("Serial: $vehicle_serial");
log_progress("Delay between products: {$delay_between_products}s");

/**
 * Fetch URL with retry
 */
function fetch_url($url, $retries = 3) {
    for ($i = 0; $i < $retries; $i++) {
        $response = wp_remote_get($url, [
            'timeout' => 60,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);

        if (!is_wp_error($response)) {
            return wp_remote_retrieve_body($response);
        }

        sleep(5); // Wait before retry
    }

    return false;
}

/**
 * Get product URLs from a page (excluding category links)
 */
function get_product_urls($html) {
    $products = [];
    if (preg_match_all('/href="(\/catalogue\/(?!category\/)[^"]+_\d+\/)"/i', $html, $matches)) {
        foreach ($matches[1] as $url) {
            if (strpos($url, 'serial-') === false && strpos($url, '/category/') === false) {
                $products[] = $url;
            }
        }
    }
    return array_unique($products);
}

/**
 * Get subcategory URLs
 */
function get_subcategory_urls($html, $serial) {
    $subcats = [];
    if (preg_match_all('/href="(\/catalogue\/category\/[^"]*' . preg_quote($serial, '/') . '[^"]*)"/i', $html, $matches)) {
        $subcats = array_unique($matches[1]);
    }
    return $subcats;
}

/**
 * Parse product page
 */
function parse_product($html, $url) {
    $product = [
        'url' => $url,
        'name' => '',
        'sku' => '',
        'upc' => '',
        'price' => 0,
        'stock' => 0,
    ];

    // Name from h1 or title
    if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/is', $html, $m)) {
        $product['name'] = trim(html_entity_decode($m[1]));
    } elseif (preg_match('/<title>([^<|]+)/i', $html, $m)) {
        $product['name'] = trim(html_entity_decode($m[1]));
    }
    $product['name'] = preg_replace('/\s+/', ' ', $product['name']);

    // UPC
    if (preg_match('/UPC[:\s]*([A-Z0-9]+)/i', $html, $m)) {
        $product['upc'] = trim($m[1]);
    }

    // Part number
    if (preg_match('/([A-Z]{2}\d{3}[A-Z]\d{3})/i', $html, $m)) {
        $product['sku'] = trim($m[1]);
    }

    // Price (skip zero prices)
    if (preg_match_all('/£([\d,]+\.\d{2})/i', $html, $matches)) {
        foreach ($matches[1] as $price_str) {
            $price = floatval(str_replace(',', '', $price_str));
            if ($price > 0) {
                $product['price'] = $price;
                break;
            }
        }
    }

    // Stock
    if (preg_match('/Stock[:\s]*(\d+)/i', $html, $m)) {
        $product['stock'] = intval($m[1]);
    } elseif (preg_match('/(\d+)\s*units?\s*available/i', $html, $m)) {
        $product['stock'] = intval($m[1]);
    } elseif (preg_match('/In\s*Stock/i', $html)) {
        $product['stock'] = 10;
    }

    return $product;
}

/**
 * Find WooCommerce category by slug pattern
 */
function find_category_id($slug_pattern) {
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'search' => $slug_pattern,
    ]);

    if (!empty($terms) && !is_wp_error($terms)) {
        return $terms[0]->term_id;
    }

    return 0;
}

/**
 * Create WooCommerce product
 */
function create_product($data, $cat_ids = []) {
    // Check if exists by UPC
    if (!empty($data['upc'])) {
        $existing = wc_get_product_id_by_sku($data['upc']);
        if ($existing) {
            return ['id' => $existing, 'skipped' => true];
        }
    }

    $product = new WC_Product_Simple();
    $product->set_name($data['name']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price($data['price']);

    $sku = !empty($data['upc']) ? $data['upc'] : $data['sku'];
    if (!empty($sku)) {
        $product->set_sku($sku);
    }

    if ($data['stock'] > 0) {
        $product->set_manage_stock(true);
        $product->set_stock_quantity($data['stock']);
        $product->set_stock_status('instock');
    }

    if (!empty($cat_ids)) {
        $product->set_category_ids($cat_ids);
    }

    if (!empty($data['sku'])) {
        $product->update_meta_data('_part_number', $data['sku']);
    }

    $product_id = $product->save();
    return ['id' => $product_id, 'skipped' => false];
}

// ============================================
// MAIN IMPORT
// ============================================

$start_time = time();
$total_imported = 0;
$total_skipped = 0;
$total_errors = 0;

// Fetch main vehicle page
$vehicle_url = "$base_url/catalogue/category/serial-{$vehicle_serial}_{$vehicle_page_id}/";
log_progress("Fetching: $vehicle_url");

$html = fetch_url($vehicle_url);
if (!$html) {
    log_progress("ERROR: Failed to fetch vehicle page");
    exit;
}

// Get all category links
$main_categories = get_subcategory_urls($html, $vehicle_serial);
log_progress("Found " . count($main_categories) . " main categories");

// Process each category
foreach ($main_categories as $cat_index => $cat_url) {
    log_progress("\n--- Category " . ($cat_index + 1) . "/" . count($main_categories) . " ---");
    log_progress("URL: $cat_url");

    $cat_html = fetch_url($base_url . $cat_url);
    if (!$cat_html) {
        log_progress("  ERROR: Failed to fetch category");
        continue;
    }

    // Get products directly on this page
    $product_urls = get_product_urls($cat_html);

    // Also check subcategories
    $subcats = get_subcategory_urls($cat_html, $vehicle_serial);
    foreach ($subcats as $subcat_url) {
        if ($subcat_url === $cat_url) continue;

        $subcat_html = fetch_url($base_url . $subcat_url);
        if ($subcat_html) {
            $sub_products = get_product_urls($subcat_html);
            $product_urls = array_merge($product_urls, $sub_products);

            // Go one more level deep
            $subsubcats = get_subcategory_urls($subcat_html, $vehicle_serial);
            foreach ($subsubcats as $subsubcat_url) {
                if ($subsubcat_url === $subcat_url) continue;
                $subsubcat_html = fetch_url($base_url . $subsubcat_url);
                if ($subsubcat_html) {
                    $product_urls = array_merge($product_urls, get_product_urls($subsubcat_html));
                }
            }
        }
    }

    $product_urls = array_unique($product_urls);
    log_progress("  Found " . count($product_urls) . " products");

    // Import products
    foreach ($product_urls as $prod_url) {
        $prod_html = fetch_url($base_url . $prod_url);
        if (!$prod_html) {
            $total_errors++;
            continue;
        }

        $prod_data = parse_product($prod_html, $prod_url);

        if (empty($prod_data['name']) || $prod_data['price'] <= 0) {
            $total_errors++;
            continue;
        }

        $result = create_product($prod_data);

        if ($result['skipped']) {
            $total_skipped++;
            log_progress("  SKIP: {$prod_data['name']} (exists)");
        } else {
            $total_imported++;
            log_progress("  OK: {$prod_data['name']} - £{$prod_data['price']} (ID: {$result['id']})");
        }

        // Delay to spread over 12 hours
        sleep($delay_between_products);
    }

    // Progress update
    $elapsed = time() - $start_time;
    $elapsed_hours = round($elapsed / 3600, 2);
    log_progress("\n  Progress: Imported=$total_imported, Skipped=$total_skipped, Errors=$total_errors");
    log_progress("  Elapsed time: {$elapsed_hours} hours");
}

// Final summary
$total_time = time() - $start_time;
$hours = round($total_time / 3600, 2);

log_progress("\n=== IMPORT COMPLETE ===");
log_progress("Total imported: $total_imported");
log_progress("Total skipped: $total_skipped");
log_progress("Total errors: $total_errors");
log_progress("Total time: $hours hours");
