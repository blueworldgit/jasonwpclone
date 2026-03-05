<?php
/**
 * Find categories that have products but no thumbnail
 */

require_once('wp-load.php');

header('Content-Type: text/plain');

global $wpdb;

echo "=== CATEGORIES WITHOUT THUMBNAILS (with product counts) ===\n\n";

// Get categories without thumbnails that have products
$categories = $wpdb->get_results("
    SELECT t.term_id, t.name, t.slug, COUNT(DISTINCT tr.object_id) AS product_count
    FROM {$wpdb->terms} t
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND tt.taxonomy = 'product_cat'
    INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
    INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID AND p.post_type = 'product' AND p.post_status = 'publish'
    LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = 'thumbnail_id'
    WHERE (tm.meta_value IS NULL OR tm.meta_value = '' OR tm.meta_value = '0')
    AND t.slug NOT REGEXP '^ls[a-z0-9]{15}$'
    AND t.slug NOT IN ('maxus', 'uncategorized', 'imageupdated', 'priceupdated')
    GROUP BY t.term_id
    HAVING product_count > 0
    ORDER BY product_count DESC
    LIMIT 100
");

echo "Found " . count($categories) . " categories without thumbnails:\n\n";

$total_products_affected = 0;
foreach ($categories as $cat) {
    echo "{$cat->name} (slug: {$cat->slug}) - {$cat->product_count} products\n";
    $total_products_affected += $cat->product_count;
}

echo "\n=== SUMMARY ===\n";
echo "Categories without thumbnails shown: " . count($categories) . "\n";
echo "Total products in these categories: $total_products_affected\n";
