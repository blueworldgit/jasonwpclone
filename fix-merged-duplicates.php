<?php
require_once('wp-load.php');
header('Content-Type: text/plain');
set_time_limit(300);

echo "Fixing Duplicate Products - Direct SQL Mode\n";
echo str_repeat('=', 80) . "\n\n";

global $wpdb;

// Step 1: Find all duplicate product IDs using SQL only
// Products with same base SKU (everything before last dash) that share at least one category

$duplicates = $wpdb->get_results("
    SELECT p2.ID as duplicate_id, p2_sku.meta_value as sku
    FROM {$wpdb->postmeta} p1_sku
    JOIN {$wpdb->postmeta} p2_sku ON
        SUBSTRING_INDEX(p1_sku.meta_value, '-', 1) = SUBSTRING_INDEX(p2_sku.meta_value, '-', 1)
        AND p1_sku.post_id < p2_sku.post_id
    JOIN {$wpdb->posts} p1 ON p1_sku.post_id = p1.ID AND p1.post_type = 'product' AND p1.post_status = 'publish'
    JOIN {$wpdb->posts} p2 ON p2_sku.post_id = p2.ID AND p2.post_type = 'product' AND p2.post_status = 'publish'
    JOIN {$wpdb->term_relationships} tr1 ON p1.ID = tr1.object_id
    JOIN {$wpdb->term_relationships} tr2 ON p2.ID = tr2.object_id AND tr1.term_taxonomy_id = tr2.term_taxonomy_id
    JOIN {$wpdb->term_taxonomy} tt ON tr1.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat'
    WHERE p1_sku.meta_key = '_sku'
    AND p2_sku.meta_key = '_sku'
    AND p1_sku.meta_value LIKE '%-%'
    GROUP BY p2.ID
");

$total = count($duplicates);
echo "Found $total duplicate products to delete\n\n";

if ($total == 0) {
    echo "No duplicates found! All clean.\n";
    exit;
}

if (!isset($_GET['apply'])) {
    echo "First 20 duplicates:\n";
    $count = 0;
    foreach ($duplicates as $d) {
        if ($count++ >= 20) break;
        echo "  ID: {$d->duplicate_id} | SKU: {$d->sku}\n";
    }
    echo "\nAdd ?apply=1 to delete all duplicates\n";
    exit;
}

echo "DELETING using direct SQL...\n\n";

$ids = array_map(function($d) { return $d->duplicate_id; }, $duplicates);
$id_list = implode(',', $ids);

// Delete in chunks to avoid issues
$chunks = array_chunk($ids, 500);
$deleted = 0;

foreach ($chunks as $i => $chunk) {
    $chunk_ids = implode(',', $chunk);

    // Delete postmeta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($chunk_ids)");

    // Delete term relationships
    $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($chunk_ids)");

    // Delete posts
    $result = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ($chunk_ids)");

    $deleted += count($chunk);
    echo "Deleted chunk " . ($i + 1) . "/" . count($chunks) . " ($deleted total)\n";
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
