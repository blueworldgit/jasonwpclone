<?php
/**
 * Import Products from maxusparts.co.uk
 * Run via: wp eval-file wp-content/themes/mobex-child/import-products.php
 *
 * Start with one vehicle as a test
 */

if (!function_exists('wp_upload_bits')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}

// Ensure WooCommerce is loaded
if (!class_exists('WC_Product')) {
    echo "WooCommerce not loaded!\n";
    exit;
}

echo "=== Importing Products from maxusparts.co.uk ===\n\n";

$base_url = 'https://maxusparts.co.uk';

// Test with Deliver 9 FWD Base model (serial LSH14J7C2MA122115)
$vehicle_serial = 'LSH14J7C2MA122115';
$vehicle_name = 'Maxus Deliver 9 (2021)';

// Get the vehicle's product category in WooCommerce
$vehicle_cat = get_term_by('slug', 'lsh14j7c7ma114771', 'product_cat');
if (!$vehicle_cat) {
    // Try alternate slug
    $vehicle_cat = get_term_by('slug', 'maxus-edeliver-3-2021', 'product_cat');
}

echo "Importing products for: $vehicle_name\n";
echo "Vehicle serial: $vehicle_serial\n\n";

/**
 * Fetch a URL and return the HTML
 */
function fetch_url($url) {
    $response = wp_remote_get($url, [
        'timeout' => 30,
        'sslverify' => false,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);

    if (is_wp_error($response)) {
        echo "Error fetching $url: " . $response->get_error_message() . "\n";
        return false;
    }

    return wp_remote_retrieve_body($response);
}

/**
 * Parse product listing page and extract product URLs
 */
function get_product_urls_from_page($html) {
    $products = [];

    // Match product links like /catalogue/product-name_123/ but NOT category links
    if (preg_match_all('/<a[^>]+href="(\/catalogue\/(?!category\/)[^"]+_\d+\/)"[^>]*>/i', $html, $matches)) {
        foreach ($matches[1] as $url) {
            // Skip if it contains 'category' or 'serial'
            if (strpos($url, '/category/') === false && strpos($url, 'serial-') === false) {
                $products[] = $url;
            }
        }
        $products = array_unique($products);
    }

    return $products;
}

/**
 * Parse subcategory links from a category page
 */
function get_subcategory_urls_from_page($html) {
    $subcats = [];

    // Match category links with parent-child structure
    if (preg_match_all('/href="(\/catalogue\/category\/[^"]+)"/', $html, $matches)) {
        $subcats = array_unique($matches[1]);
    }

    return $subcats;
}

/**
 * Extract product data from a product page
 */
function parse_product_page($html, $url) {
    $product = [
        'url' => $url,
        'name' => '',
        'sku' => '',
        'upc' => '',
        'price' => 0,
        'stock' => 0,
        'description' => '',
        'image_url' => '',
        'category' => '',
    ];

    // Extract product name from h1, h2, or title
    if (preg_match('/<h1[^>]*>\s*([^<]+)\s*<\/h1>/is', $html, $m)) {
        $product['name'] = trim(html_entity_decode($m[1]));
    } elseif (preg_match('/<h2[^>]*>\s*([^<]+)\s*<\/h2>/is', $html, $m)) {
        $product['name'] = trim(html_entity_decode($m[1]));
    } elseif (preg_match('/<title>\s*([^<|]+)/i', $html, $m)) {
        $product['name'] = trim(html_entity_decode($m[1]));
    }

    // Clean up product name
    $product['name'] = preg_replace('/\s+/', ' ', $product['name']);

    // Extract UPC (more specific than SKU)
    if (preg_match('/UPC[:\s]+([A-Z0-9]+)/i', $html, $m)) {
        $product['upc'] = trim($m[1]);
    }

    // Extract SKU/Part number from diagram title or text
    if (preg_match('/([A-Z]{2}\d{3}[A-Z]\d{3})/i', $html, $m)) {
        $product['sku'] = trim($m[1]);
    }

    // Extract price - find all prices and use the first non-zero one
    if (preg_match_all('/£([\d,]+\.\d{2})/i', $html, $matches)) {
        foreach ($matches[1] as $price_str) {
            $price = floatval(str_replace(',', '', $price_str));
            if ($price > 0) {
                $product['price'] = $price;
                break;
            }
        }
    }

    // Extract stock - look for "Stock: X" or "X units"
    if (preg_match('/Stock[:\s]*(\d+)/i', $html, $m)) {
        $product['stock'] = intval($m[1]);
    } elseif (preg_match('/(\d+)\s*units?\s*available/i', $html, $m)) {
        $product['stock'] = intval($m[1]);
    } elseif (preg_match('/In\s*Stock/i', $html)) {
        $product['stock'] = 10; // Default stock if "In Stock" but no number
    }

    // Extract category from breadcrumb or page
    if (preg_match('/Category[:\s]+([^<\n]+)/i', $html, $m)) {
        $product['category'] = trim($m[1]);
    }

    // Extract image URL - look for product images (not icons/logos)
    if (preg_match('/src="([^"]*(?:media|uploads|products)[^"]*\.(?:jpg|jpeg|png|gif|webp))"/i', $html, $m)) {
        $img = $m[1];
        if (strpos($img, 'http') !== 0) {
            $img = 'https://maxusparts.co.uk' . $img;
        }
        $product['image_url'] = $img;
    }

    return $product;
}

/**
 * Find or create WooCommerce category by slug
 */
function get_or_create_category($slug, $name, $parent_id = 0) {
    $term = get_term_by('slug', $slug, 'product_cat');

    if ($term) {
        return $term->term_id;
    }

    // Create new category
    $result = wp_insert_term($name, 'product_cat', [
        'slug' => $slug,
        'parent' => $parent_id
    ]);

    if (is_wp_error($result)) {
        echo "Error creating category '$name': " . $result->get_error_message() . "\n";
        return 0;
    }

    return $result['term_id'];
}

/**
 * Download image and attach to product
 */
function import_product_image($image_url, $product_id, $product_name) {
    if (empty($image_url)) return false;

    $response = wp_remote_get($image_url, [
        'timeout' => 30,
        'sslverify' => false
    ]);

    if (is_wp_error($response)) return false;

    $image_data = wp_remote_retrieve_body($response);
    if (empty($image_data)) return false;

    $filename = basename(parse_url($image_url, PHP_URL_PATH));
    $upload = wp_upload_bits($filename, null, $image_data);

    if ($upload['error']) return false;

    $filetype = wp_check_filetype($filename);
    $attachment = [
        'post_mime_type' => $filetype['type'],
        'post_title' => $product_name,
        'post_content' => '',
        'post_status' => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $upload['file'], $product_id);

    if (!is_wp_error($attach_id)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail($product_id, $attach_id);
        return $attach_id;
    }

    return false;
}

/**
 * Create WooCommerce product
 */
function create_wc_product($product_data, $category_ids = []) {
    // Check if product already exists by SKU
    if (!empty($product_data['sku'])) {
        $existing_id = wc_get_product_id_by_sku($product_data['sku']);
        if ($existing_id) {
            echo "  SKU exists, skipping: {$product_data['sku']}\n";
            return $existing_id;
        }
    }

    // Check by UPC as SKU fallback
    if (!empty($product_data['upc'])) {
        $existing_id = wc_get_product_id_by_sku($product_data['upc']);
        if ($existing_id) {
            echo "  UPC exists, skipping: {$product_data['upc']}\n";
            return $existing_id;
        }
    }

    $product = new WC_Product_Simple();

    $product->set_name($product_data['name']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price($product_data['price']);

    // Use UPC as SKU (more unique than part number)
    $sku = !empty($product_data['upc']) ? $product_data['upc'] : $product_data['sku'];
    if (!empty($sku)) {
        $product->set_sku($sku);
    }

    if ($product_data['stock'] > 0) {
        $product->set_manage_stock(true);
        $product->set_stock_quantity($product_data['stock']);
        $product->set_stock_status('instock');
    }

    if (!empty($product_data['description'])) {
        $product->set_description($product_data['description']);
    }

    if (!empty($category_ids)) {
        $product->set_category_ids($category_ids);
    }

    // Store original part number as meta
    if (!empty($product_data['sku'])) {
        $product->update_meta_data('_part_number', $product_data['sku']);
    }

    $product_id = $product->save();

    // Import image
    if (!empty($product_data['image_url'])) {
        import_product_image($product_data['image_url'], $product_id, $product_data['name']);
    }

    return $product_id;
}

// ============================================
// MAIN IMPORT LOGIC
// ============================================

// Start with fetching the main category page for the vehicle
$vehicle_url = "$base_url/catalogue/category/serial-{$vehicle_serial}_612/";
echo "Fetching vehicle categories: $vehicle_url\n\n";

$html = fetch_url($vehicle_url);
if (!$html) {
    echo "Failed to fetch vehicle page\n";
    exit;
}

// Get all subcategory URLs
$subcats = get_subcategory_urls_from_page($html);
echo "Found " . count($subcats) . " category links\n\n";

// Limit to first 3 categories for test
$test_limit = 3;
$processed = 0;
$products_imported = 0;

foreach ($subcats as $subcat_url) {
    if ($processed >= $test_limit) break;

    // Skip if it's the same as vehicle URL
    if (strpos($subcat_url, $vehicle_serial) === false) continue;

    echo "Processing category: $subcat_url\n";

    $cat_html = fetch_url($base_url . $subcat_url);
    if (!$cat_html) continue;

    // Get product URLs from this category
    $product_urls = get_product_urls_from_page($cat_html);

    // If no products, might be another level of subcategories
    if (empty($product_urls)) {
        $deeper_subcats = get_subcategory_urls_from_page($cat_html);
        foreach ($deeper_subcats as $deeper_url) {
            if (strpos($deeper_url, $vehicle_serial) === false) continue;

            $deeper_html = fetch_url($base_url . $deeper_url);
            if ($deeper_html) {
                $product_urls = array_merge($product_urls, get_product_urls_from_page($deeper_html));
            }
        }
    }

    echo "  Found " . count($product_urls) . " products\n";

    // Import first 10 products from this category as test
    $product_limit = 10;
    $product_count = 0;

    foreach ($product_urls as $product_url) {
        if ($product_count >= $product_limit) break;

        $product_html = fetch_url($base_url . $product_url);
        if (!$product_html) continue;

        $product_data = parse_product_page($product_html, $product_url);

        if (empty($product_data['name']) || $product_data['price'] <= 0) {
            echo "  Skipping invalid product: $product_url\n";
            continue;
        }

        echo "  Importing: {$product_data['name']} - £{$product_data['price']}\n";

        // Find matching category in WooCommerce based on URL slug
        $cat_ids = [];

        // Try to match category from URL
        if (preg_match('/parent-(\d+)/', $subcat_url, $m)) {
            // Map parent IDs to category slugs (would need to build this mapping)
        }

        $product_id = create_wc_product($product_data, $cat_ids);

        if ($product_id) {
            $products_imported++;
            echo "    Created product ID: $product_id\n";
        }

        $product_count++;

        // Small delay to avoid hammering the server
        usleep(200000); // 0.2 second
    }

    $processed++;
}

echo "\n=== IMPORT SUMMARY ===\n";
echo "Categories processed: $processed\n";
echo "Products imported: $products_imported\n";
echo "\nDone!\n";
