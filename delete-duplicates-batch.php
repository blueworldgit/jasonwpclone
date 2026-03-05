<?php
/**
 * Batch duplicate deletion - processes 50 at a time to avoid timeouts
 * Run repeatedly until done
 */
require_once('wp-load.php');
header('Content-Type: text/plain');
set_time_limit(60);
ignore_user_abort(true);

global $wpdb;

$batch_size = 50;

// Get batch of duplicate IDs
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
    LIMIT $batch_size
");

$count = count($duplicate_ids);

if ($count == 0) {
    // Update term counts when done
    $wpdb->query("
        UPDATE {$wpdb->term_taxonomy} tt
        SET count = (
            SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
            WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
        )
        WHERE taxonomy = 'product_cat'
    ");
    echo "DONE - No more duplicates found!\n";
    exit;
}

$ids = implode(',', array_map('intval', $duplicate_ids));

// Delete in one transaction
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids)");
$wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($ids)");
$wpdb->query("DELETE FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id IN ($ids)");
$wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ($ids)");

echo "DELETED $count products\n";
echo "IDs: $ids\n";
echo "Run again to delete more...\n";
