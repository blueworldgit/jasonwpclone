<?php
/**
 * Find products without diagram images and their categories
 */

require_once('wp-load.php');

header('Content-Type: text/plain');

global $wpdb;

echo "=== PRODUCTS WITHOUT DIAGRAM IMAGES ===\n\n";

// Get products without thumbnails and their categories
$results = $wpdb->get_results("
    SELECT p.ID, p.post_title,
           GROUP_CONCAT(DISTINCT t.name SEPARATOR ' | ') AS categories,
           GROUP_CONCAT(DISTINCT t.slug SEPARATOR ' | ') AS cat_slugs
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} thumb ON p.ID = thumb.post_id AND thumb.meta_key = '_thumbnail_id'
    LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
    LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat'
    LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    AND (thumb.meta_value IS NULL OR thumb.meta_value = '' OR thumb.meta_value = '0')
    AND t.slug NOT REGEXP '^ls[a-z0-9]{15}$'
    AND t.slug NOT IN ('maxus', 'uncategorized', 'imageupdated', 'priceupdated')
    GROUP BY p.ID
    LIMIT 50
");

echo "Sample products without diagrams:\n\n";
foreach ($results as $r) {
    echo "Product {$r->ID}: {$r->post_title}\n";
    echo "  Categories: {$r->categories}\n\n";
}

// Get unique categories that have products without thumbnails
echo "\n=== CATEGORIES NEEDING DIAGRAMS ===\n\n";
$cats = $wpdb->get_results("
    SELECT t.term_id, t.name, t.slug, COUNT(DISTINCT p.ID) as product_count
    FROM {$wpdb->terms} t
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND tt.taxonomy = 'product_cat'
    INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
    INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID AND p.post_type = 'product' AND p.post_status = 'publish'
    LEFT JOIN {$wpdb->postmeta} thumb ON p.ID = thumb.post_id AND thumb.meta_key = '_thumbnail_id'
    LEFT JOIN {$wpdb->termmeta} cat_thumb ON t.term_id = cat_thumb.term_id AND cat_thumb.meta_key = 'thumbnail_id'
    WHERE (thumb.meta_value IS NULL OR thumb.meta_value = '' OR thumb.meta_value = '0')
    AND t.slug NOT REGEXP '^ls[a-z0-9]{15}$'
    AND t.slug NOT IN ('maxus', 'uncategorized', 'imageupdated', 'priceupdated')
    GROUP BY t.term_id
    ORDER BY product_count DESC
");

$total = 0;
foreach ($cats as $cat) {
    $has_thumb = get_term_meta($cat->term_id, 'thumbnail_id', true);
    $thumb_status = ($has_thumb && $has_thumb != '0') ? "HAS THUMB ($has_thumb)" : "NO THUMB";
    echo "{$cat->product_count} products: {$cat->name} [{$thumb_status}]\n";
    $total += $cat->product_count;
}

echo "\nTotal products affected: $total\n";
echo "Unique categories: " . count($cats) . "\n";
