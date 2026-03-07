<?php
/**
 * Test Category Filtering - verify theme-level filtering prevents category bleed
 * URL: https://maxusvanparts.local/test-category-filtering.php
 */

require_once(__DIR__ . '/wp-load.php');

$test_sku = 'B00003507';
$test_vin = 'LSFAM120XNA160733'; // T90 EV

// Get the product with this SKU
global $wpdb;
$product_id = $wpdb->get_var($wpdb->prepare(
    "SELECT pm.post_id 
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_key = '_sku' 
    AND pm.meta_value = %s
    AND p.post_status = 'publish'
    LIMIT 1",
    $test_sku
));

if (!$product_id) {
    die("Product not found with SKU: $test_sku");
}

$post = get_post($product_id);

echo "<h1>Category Filtering Test</h1>";
echo "<p><strong>SKU:</strong> $test_sku</p>";
echo "<p><strong>VIN:</strong> $test_vin (T90 EV)</p>";
echo "<p><strong>Product ID:</strong> $product_id</p>";
echo "<p><strong>Product Type:</strong> {$post->post_type}</p>";

if ($post->post_type === 'product_variation') {
    echo "<p><strong>Parent Product ID:</strong> {$post->post_parent}</p>";
}

echo "<hr>";

// Get ALL categories (unfiltered - the old way)
echo "<h2>ALL Categories (Unfiltered)</h2>";
echo "<p>This is what WooCommerce returns - includes category bleed from other VINs:</p>";
$all_terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'all']);
echo "<p><strong>Total:</strong> " . count($all_terms) . " categories</p>";
echo "<ul>";
foreach ($all_terms as $term) {
    $is_main = ($term->parent == 0);
    $highlight = '';
    
    // Highlight problem categories
    if (in_array($term->slug, ['fuel-storage-handling', 'air-intake-system', 'emission-exhaust-system', 'power-generation'])) {
        $highlight = ' style="color: red; font-weight: bold;"';
    }
    
    echo "<li{$highlight}>{$term->name} " . ($is_main ? '(main)' : '(sub)') . "</li>";
}
echo "</ul>";

echo "<hr>";

// Get FILTERED categories (new way with our helper function)
echo "<h2>FILTERED Categories (VIN-Specific)</h2>";
echo "<p>This is what the theme now shows - only categories valid for T90 EV:</p>";
$filtered_terms = maxus_get_filtered_product_categories($product_id, $test_vin);
echo "<p><strong>Total:</strong> " . count($filtered_terms) . " categories</p>";
echo "<ul>";
foreach ($filtered_terms as $term) {
    $is_main = ($term->parent == 0);
    echo "<li>{$term->name} " . ($is_main ? '(main)' : '(sub)') . "</li>";
}
echo "</ul>";

echo "<hr>";

// Show which categories were filtered out
echo "<h2>Filtered Out (Category Bleed Removed)</h2>";
$all_names = array_map(function($t) { return $t->name; }, $all_terms);
$filtered_names = array_map(function($t) { return $t->name; }, $filtered_terms);
$removed = array_diff($all_names, $filtered_names);

if (empty($removed)) {
    echo "<p style='color: green;'><strong>✓ No category bleed detected!</strong></p>";
} else {
    echo "<p style='color: orange;'><strong>Categories removed by filter:</strong></p>";
    echo "<ul>";
    foreach ($removed as $name) {
        // Highlight the problem categories
        $style = '';
        if (strpos(strtolower($name), 'fuel') !== false || 
            strpos(strtolower($name), 'air intake') !== false ||
            strpos(strtolower($name), 'emission') !== false) {
            $style = ' style="color: red; font-weight: bold;"';
        }
        echo "<li{$style}>{$name}</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Valid Category Tree for T90 EV</h2>";
$valid_ids = maxus_get_valid_category_ids_for_vin($test_vin);
echo "<p><strong>Total valid categories:</strong> " . count($valid_ids) . "</p>";

// Organize by hierarchy
$main_cats = [];
$sub_cats = [];

foreach ($valid_ids as $term_id) {
    $term = get_term($term_id, 'product_cat');
    if ($term && !is_wp_error($term)) {
        if ($term->parent == 0) {
            $main_cats[] = $term;
        } else {
            if (!isset($sub_cats[$term->parent])) {
                $sub_cats[$term->parent] = [];
            }
            $sub_cats[$term->parent][] = $term;
        }
    }
}

echo "<p><strong>Main categories:</strong> " . count($main_cats) . "</p>";
echo "<ul>";
foreach ($main_cats as $main) {
    echo "<li>{$main->name}";
    if (isset($sub_cats[$main->term_id])) {
        echo " <span style='color: gray;'>(" . count($sub_cats[$main->term_id]) . " subcategories)</span>";
    }
    echo "</li>";
}
echo "</ul>";

// Add link to the actual product page
echo "<hr>";
echo "<p><a href='" . home_url('/e-deliver-9/product/nut-air-cleaner-inlet-duct-to-body/') . "'>View Product on Site</a></p>";
