<?php
/**
 * Diagnostic: Compare SKU formats across wp_sku_vin_mapping, _sku postmeta, and original_sku postmeta
 */
require_once __DIR__ . '/wp-load.php';
global $wpdb;

echo "=== 1. wp_sku_vin_mapping table ===\n";
$sample = $wpdb->get_col("SELECT DISTINCT sku FROM {$wpdb->prefix}sku_vin_mapping ORDER BY sku LIMIT 20");
echo "Sample SKUs:\n";
foreach ($sample as $s) echo "  $s\n";
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sku_vin_mapping");
$distinct = $wpdb->get_var("SELECT COUNT(DISTINCT sku) FROM {$wpdb->prefix}sku_vin_mapping");
echo "Total rows: $total\n";
echo "Distinct SKUs: $distinct\n";

// Check if any have hash suffix
$with_suffix = $wpdb->get_var("SELECT COUNT(DISTINCT sku) FROM {$wpdb->prefix}sku_vin_mapping WHERE sku REGEXP '-[A-Fa-f0-9]{5,6}$'");
$without_suffix = $wpdb->get_var("SELECT COUNT(DISTINCT sku) FROM {$wpdb->prefix}sku_vin_mapping WHERE sku NOT REGEXP '-[A-Fa-f0-9]{5,6}$'");
echo "With hash suffix: $with_suffix\n";
echo "Without hash suffix: $without_suffix\n";

echo "\n=== 2. WooCommerce _sku postmeta ===\n";
$sku_sample = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != '' ORDER BY meta_value LIMIT 20");
echo "Sample _sku values:\n";
foreach ($sku_sample as $s) echo "  $s\n";
$sku_count = $wpdb->get_var("SELECT COUNT(DISTINCT meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''");
echo "Distinct _sku values: $sku_count\n";
$sku_with_suffix = $wpdb->get_var("SELECT COUNT(DISTINCT meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value REGEXP '-[A-Fa-f0-9]{5,6}$'");
echo "With hash suffix: $sku_with_suffix\n";

echo "\n=== 3. original_sku postmeta ===\n";
$orig_sample = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'original_sku' AND meta_value != '' ORDER BY meta_value LIMIT 20");
echo "Sample original_sku values:\n";
foreach ($orig_sample as $s) echo "  $s\n";
$orig_count = $wpdb->get_var("SELECT COUNT(DISTINCT meta_value) FROM {$wpdb->postmeta} WHERE meta_key = 'original_sku' AND meta_value != ''");
echo "Distinct original_sku values: $orig_count\n";

echo "\n=== 4. MATCH TEST: _sku JOIN wp_sku_vin_mapping ===\n";
$match_sku = $wpdb->get_var("
    SELECT COUNT(DISTINCT pm.meta_value) 
    FROM {$wpdb->postmeta} pm 
    INNER JOIN {$wpdb->prefix}sku_vin_mapping svm ON pm.meta_value = svm.sku 
    WHERE pm.meta_key = '_sku'
");
echo "Products matched via _sku: $match_sku\n";

echo "\n=== 5. MATCH TEST: original_sku JOIN wp_sku_vin_mapping ===\n";
$match_orig = $wpdb->get_var("
    SELECT COUNT(DISTINCT pm.meta_value) 
    FROM {$wpdb->postmeta} pm 
    INNER JOIN {$wpdb->prefix}sku_vin_mapping svm ON pm.meta_value = svm.sku 
    WHERE pm.meta_key = 'original_sku'
");
echo "Products matched via original_sku: $match_orig\n";

echo "\n=== 6. UNMATCHED via _sku (products not linking to any VIN) ===\n";
$unmatched_sku = $wpdb->get_var("
    SELECT COUNT(DISTINCT pm.meta_value) 
    FROM {$wpdb->postmeta} pm 
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID AND p.post_type IN ('product', 'product_variation')
    LEFT JOIN {$wpdb->prefix}sku_vin_mapping svm ON pm.meta_value = svm.sku 
    WHERE pm.meta_key = '_sku' AND svm.sku IS NULL
");
echo "Unmatched via _sku: $unmatched_sku\n";

echo "\n=== 7. UNMATCHED via original_sku (products not linking to any VIN) ===\n";
$unmatched_orig = $wpdb->get_var("
    SELECT COUNT(DISTINCT pm.meta_value) 
    FROM {$wpdb->postmeta} pm 
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID AND p.post_type IN ('product', 'product_variation')
    LEFT JOIN {$wpdb->prefix}sku_vin_mapping svm ON pm.meta_value = svm.sku 
    WHERE pm.meta_key = 'original_sku' AND svm.sku IS NULL
");
echo "Unmatched via original_sku: $unmatched_orig\n";

echo "\n=== 8. Side-by-side comparison (first 10 products) ===\n";
$comparison = $wpdb->get_results("
    SELECT p.ID, 
           pm_sku.meta_value as wc_sku, 
           pm_orig.meta_value as original_sku
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    LEFT JOIN {$wpdb->postmeta} pm_orig ON p.ID = pm_orig.post_id AND pm_orig.meta_key = 'original_sku'
    WHERE p.post_type IN ('product', 'product_variation') AND p.post_status = 'publish'
    LIMIT 10
");
printf("%-10s %-25s %-20s\n", "Post ID", "WC _sku", "original_sku");
printf("%-10s %-25s %-20s\n", str_repeat('-',10), str_repeat('-',25), str_repeat('-',20));
foreach ($comparison as $row) {
    printf("%-10s %-25s %-20s\n", $row->ID, $row->wc_sku, $row->original_sku ?? '(none)');
}
