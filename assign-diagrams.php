<?php
/**
 * Assign category diagram images to products without thumbnails
 */

require_once('wp-load.php');

header('Content-Type: text/plain');
set_time_limit(0);

global $wpdb;

$action = isset($_GET['action']) ? $_GET['action'] : 'preview';

echo "=== ASSIGN DIAGRAMS TO PRODUCTS ===\n";
echo "Action: $action\n\n";

// Get products without thumbnails
$products = $wpdb->get_results("
    SELECT p.ID, p.post_title
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} thumb ON p.ID = thumb.post_id AND thumb.meta_key = '_thumbnail_id'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    AND (thumb.meta_value IS NULL OR thumb.meta_value = '' OR thumb.meta_value = '0')
");

echo "Products without thumbnails: " . count($products) . "\n\n";

$matches = [];
$no_cat_image = 0;

foreach ($products as $product) {
    // Get product's categories
    $categories = wp_get_post_terms($product->ID, 'product_cat', ['fields' => 'ids']);

    $cat_thumb_id = null;
    foreach ($categories as $cat_id) {
        $cat = get_term($cat_id, 'product_cat');
        // Skip vehicle categories
        if (preg_match('/^ls[a-z0-9]{15}$/i', $cat->slug) || $cat->slug === 'maxus') {
            continue;
        }

        $thumb_id = get_term_meta($cat_id, 'thumbnail_id', true);
        if ($thumb_id && $thumb_id != '0') {
            $cat_thumb_id = $thumb_id;
            $cat_name = $cat->name;
            break;
        }
    }

    if ($cat_thumb_id) {
        $matches[] = [
            'product_id' => $product->ID,
            'title' => $product->post_title,
            'cat_name' => $cat_name,
            'thumb_id' => $cat_thumb_id
        ];
    } else {
        $no_cat_image++;
    }
}

echo "=== RESULTS ===\n";
echo "Products matched: " . count($matches) . "\n";
echo "No category image: $no_cat_image\n\n";

if ($action === 'preview') {
    echo "=== SAMPLE (first 20) ===\n";
    foreach (array_slice($matches, 0, 20) as $m) {
        echo "{$m['title']} -> {$m['cat_name']}\n";
    }
    echo "\nTo assign, add ?action=import\n";
}
elseif ($action === 'import') {
    echo "=== ASSIGNING ===\n";
    $assigned = 0;
    foreach ($matches as $m) {
        set_post_thumbnail($m['product_id'], $m['thumb_id']);
        $assigned++;
        if ($assigned % 500 === 0) {
            echo "Progress: $assigned...\n";
        }
    }
    echo "\nAssigned: $assigned\n";
}

// Summary
$prods_with_thumb = $wpdb->get_var("
    SELECT COUNT(DISTINCT p.ID)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    AND pm.meta_value IS NOT NULL AND pm.meta_value != '' AND pm.meta_value != '0'
");
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
echo "\n=== SUMMARY ===\n";
echo "Products with thumbnails: $prods_with_thumb / $total (" . round($prods_with_thumb/$total*100, 1) . "%)\n";
