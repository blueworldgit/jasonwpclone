<?php
/**
 * CLI script to delete duplicate products
 * Run via: php delete-duplicates-cli.php
 */

// No time limit for CLI
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once('wp-load.php');

echo "Deleting Duplicate Products (CLI Mode)\n";
echo str_repeat('=', 60) . "\n\n";

global $wpdb;

// First, get the IDs of duplicates - products with same base SKU that share a category
// Keep the one with lower ID, delete the one with higher ID
echo "Finding duplicate product IDs...\n";

$duplicate_ids = $wpdb->get_col("
    SELECT DISTINCT p2.ID
    FROM {$wpdb->postmeta} p1_sku
    JOIN {$wpdb->postmeta} p2_sku ON
        SUBSTRING_INDEX(p1_sku.meta_value, '-', 1) = SUBSTRING_INDEX(p2_sku.meta_value, '-', 1)
        AND p1_sku.post_id < p2_sku.post_id
    JOIN {$wpdb->posts} p1 ON p1_sku.post_id = p1.ID
        AND p1.post_type = 'product'
        AND p1.post_status = 'publish'
    JOIN {$wpdb->posts} p2 ON p2_sku.post_id = p2.ID
        AND p2.post_type = 'product'
        AND p2.post_status = 'publish'
    JOIN {$wpdb->term_relationships} tr1 ON p1.ID = tr1.object_id
    JOIN {$wpdb->term_relationships} tr2 ON p2.ID = tr2.object_id
        AND tr1.term_taxonomy_id = tr2.term_taxonomy_id
    JOIN {$wpdb->term_taxonomy} tt ON tr1.term_taxonomy_id = tt.term_taxonomy_id
        AND tt.taxonomy = 'product_cat'
    WHERE p1_sku.meta_key = '_sku'
    AND p2_sku.meta_key = '_sku'
    AND p1_sku.meta_value LIKE '%-%'
");

$total = count($duplicate_ids);
echo "Found $total duplicate products to delete\n\n";

if ($total == 0) {
    echo "No duplicates found!\n";
    exit;
}

// Delete in chunks of 100
$chunk_size = 100;
$chunks = array_chunk($duplicate_ids, $chunk_size);
$deleted = 0;

foreach ($chunks as $i => $chunk) {
    $ids = implode(',', array_map('intval', $chunk));

    // Delete postmeta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids)");

    // Delete term relationships
    $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($ids)");

    // Delete from WooCommerce lookup tables if they exist
    $wpdb->query("DELETE FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id IN ($ids)");

    // Delete posts
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ($ids)");

    $deleted += count($chunk);
    $pct = round(($deleted / $total) * 100, 1);
    echo "Deleted $deleted / $total ($pct%)\n";

    // Flush periodically
    if ($i % 10 == 0) {
        wp_cache_flush();
    }
}

// Update term counts
echo "\nUpdating category counts...\n";
$wpdb->query("
    UPDATE {$wpdb->term_taxonomy} tt
    SET count = (
        SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
        WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
    )
    WHERE taxonomy = 'product_cat'
");

echo "\nDONE! Deleted $deleted duplicate products.\n";
