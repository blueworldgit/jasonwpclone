<?php
/**
 * Final attempt to match remaining categories with manual mappings
 */

require_once('wp-load.php');

header('Content-Type: text/plain');
set_time_limit(0);

global $wpdb;

$action = isset($_GET['action']) ? $_GET['action'] : 'preview';

echo "=== FINAL DIAGRAM FIX ===\n";
echo "Action: $action\n\n";

// Manual mappings: category name fragment -> image name fragment
$manual_mappings = [
    'floorconsole' => 'floor_console',
    'floor console' => 'floor_console',
    'aircleaner' => 'air_cleaner',
    'air cleaner' => 'air_cleaner',
    'wiper' => 'wiper',
    'front wiper' => 'wiper',
    'endgate' => 'end_gate',
    'frame' => 'frame',
    'brakeplumbing' => 'brake_pipes',
    'brake plumbing' => 'brake_pipes',
    'rearbrakecorner' => 'rear_brakes',
    'rear brake corner' => 'rear_brakes',
    'coolant pump' => 'coolant_pump',
    'oil cooler' => 'oil_cooler',
    'accessory' => 'accessory',
    'accessorydrive' => 'accessory_drive',
    'side sliding door' => 'side_sliding_door',
    'fusebox' => 'fuse',
    'lubricant' => 'fluids',
    'player' => 'entertainment',
    'front washer' => 'washer',
    'front seat' => 'front_seat',
    'rear seat' => 'rear_seat',
    'second row' => 'seat',
    'body attachment' => 'body',
    'electric drive' => 'electric',
    'motor controller' => 'motor',
    'high voltage' => 'harness',
    'reduction' => 'reduction',
];

// Get all image attachments
$attachments = $wpdb->get_results("
    SELECT ID, post_title, guid
    FROM {$wpdb->posts}
    WHERE post_type = 'attachment'
    AND post_mime_type LIKE 'image/%'
");

// Build lookup
$att_lookup = [];
foreach ($attachments as $att) {
    $filename = basename($att->guid);
    if (preg_match('/-\d+x\d+\.(png|jpg)$/i', $filename)) continue;
    if (preg_match('/^[BC]\d{5,}/', $filename)) continue;

    $basename = strtolower(preg_replace('/\.(png|jpg|jpeg)$/i', '', $filename));
    $att_lookup[$basename] = $att->ID;
}

echo "Attachment entries: " . count($att_lookup) . "\n\n";

// Get categories without thumbnails
$categories = $wpdb->get_results("
    SELECT DISTINCT t.term_id, t.name, t.slug, COUNT(DISTINCT p.ID) as product_count
    FROM {$wpdb->terms} t
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND tt.taxonomy = 'product_cat'
    INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
    INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID AND p.post_type = 'product' AND p.post_status = 'publish'
    LEFT JOIN {$wpdb->postmeta} thumb ON p.ID = thumb.post_id AND thumb.meta_key = '_thumbnail_id'
    LEFT JOIN {$wpdb->termmeta} cat_thumb ON t.term_id = cat_thumb.term_id AND cat_thumb.meta_key = 'thumbnail_id'
    WHERE (thumb.meta_value IS NULL OR thumb.meta_value = '' OR thumb.meta_value = '0')
    AND (cat_thumb.meta_value IS NULL OR cat_thumb.meta_value = '' OR cat_thumb.meta_value = '0')
    AND t.slug NOT REGEXP '^ls[a-z0-9]{15}$'
    AND t.slug NOT IN ('maxus', 'uncategorized', 'imageupdated', 'priceupdated')
    GROUP BY t.term_id
    ORDER BY product_count DESC
");

echo "Categories needing diagrams: " . count($categories) . "\n\n";

$matches = [];
$no_match = [];

foreach ($categories as $cat) {
    $name_lower = strtolower(html_entity_decode($cat->name));
    // Remove code prefix
    $name_clean = preg_replace('/^[jxqsce]e?\d*[a-z]?\d*\s*-?\s*/i', '', $name_lower);

    $found = false;

    // Try manual mappings first
    foreach ($manual_mappings as $pattern => $image_pattern) {
        if (strpos($name_clean, $pattern) !== false || strpos($name_lower, $pattern) !== false) {
            // Find matching attachment
            foreach ($att_lookup as $att_name => $att_id) {
                if (strpos($att_name, $image_pattern) !== false) {
                    $matches[] = [
                        'term_id' => $cat->term_id,
                        'name' => $cat->name,
                        'att_id' => $att_id,
                        'pattern' => $pattern
                    ];
                    $found = true;
                    break 2;
                }
            }
        }
    }

    if (!$found) {
        $no_match[] = "{$cat->name} ({$cat->product_count} products)";
    }
}

echo "=== RESULTS ===\n";
echo "Matched: " . count($matches) . "\n";
echo "Not matched: " . count($no_match) . "\n\n";

if ($action === 'preview') {
    echo "=== MATCHES ===\n";
    foreach ($matches as $m) {
        echo "'{$m['name']}' matched via '{$m['pattern']}'\n";
    }

    echo "\n=== NOT MATCHED ===\n";
    foreach (array_slice($no_match, 0, 30) as $nm) {
        echo "$nm\n";
    }

    echo "\nTo apply, add ?action=import\n";
}
elseif ($action === 'import') {
    echo "=== ASSIGNING ===\n";
    foreach ($matches as $m) {
        update_term_meta($m['term_id'], 'thumbnail_id', $m['att_id']);
        echo "Assigned to: {$m['name']}\n";
    }
}

$prods = $wpdb->get_var("
    SELECT COUNT(DISTINCT p.ID)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    AND pm.meta_value IS NOT NULL AND pm.meta_value != '' AND pm.meta_value != '0'
");
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
echo "\n=== STATUS ===\n";
echo "Products with thumbnails: $prods / $total (" . round($prods/$total*100, 1) . "%)\n";
