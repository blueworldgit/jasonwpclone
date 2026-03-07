<?php
/**
 * Clear category filtering cache
 * Run this after updating the category filtering functions
 */

require_once(__DIR__ . '/wp-load.php');

// Get all vehicle slugs
$vehicles = maxus_get_vehicle_vins();
$vehicle_slugs = array_keys($vehicles);

echo "<h1>Clearing Category Cache</h1>\n";
echo "<p>Deleting transient cache for all vehicles...</p>\n";

$cleared = 0;
foreach ($vehicle_slugs as $slug) {
    $cache_key = 'maxus_vcats_' . md5($slug);
    if (delete_transient($cache_key)) {
        echo "<p>✓ Cleared cache for vehicle: $slug</p>\n";
        $cleared++;
    }
}

echo "\n<hr>\n";
echo "<p><strong>Cache cleared for $cleared vehicles.</strong></p>\n";
echo "<p>Categories will now be refreshed from database.</p>\n";
echo "<p><a href='" . home_url('/maxus-t90-ev/') . "'>Visit T90 EV Page</a></p>\n";
