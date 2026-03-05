<?php
/**
 * Find and assign diagram images to categories that are missing them
 * More aggressive matching for remaining categories
 */

require_once('wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

header('Content-Type: text/plain');
set_time_limit(0);

global $wpdb;

$action = isset($_GET['action']) ? $_GET['action'] : 'preview';

echo "=== FIX REMAINING CATEGORY DIAGRAMS ===\n";
echo "Action: $action\n\n";

// Get categories without thumbnails that have products without thumbnails
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

// Get all image attachments
$attachments = $wpdb->get_results("
    SELECT ID, post_title, guid
    FROM {$wpdb->posts}
    WHERE post_type = 'attachment'
    AND post_mime_type LIKE 'image/%'
");

// Build lookup with multiple normalized keys
$att_lookup = [];
foreach ($attachments as $att) {
    $filename = basename($att->guid);
    // Skip size variants
    if (preg_match('/-\d+x\d+\.(png|jpg)$/i', $filename)) continue;
    // Skip product images (start with B or C followed by digits)
    if (preg_match('/^[BC]\d{5,}/', $filename)) continue;

    $basename = preg_replace('/\.(png|jpg|jpeg)$/i', '', $filename);

    // Create normalized keys
    $keys = [];
    $keys[] = strtolower($basename);
    $keys[] = strtolower(preg_replace('/_[A-Za-z0-9]{7}$/', '', $basename)); // Remove suffix
    $keys[] = preg_replace('/[^a-z0-9]/', '', strtolower($basename));
    $keys[] = preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace('/_[A-Za-z0-9]{7}$/', '', $basename)));

    foreach ($keys as $key) {
        if (!isset($att_lookup[$key]) && strlen($key) > 3) {
            $att_lookup[$key] = $att->ID;
        }
    }
}

echo "Attachment lookup entries: " . count($att_lookup) . "\n\n";

// Function to normalize category name for matching
function normalize_cat_name($name) {
    // Remove code prefix (JE11CD001, XE11CH001, etc)
    $name = preg_replace('/^[JXQSCE]E?\d*[A-Z]?\d*\s*-?\s*/', '', $name);
    // Remove common suffixes
    $name = preg_replace('/\s*-?\s*(EURO|D20|FCV|PHEV|EV|LHD|RHD)\s*\d*\/?\d*\s*$/i', '', $name);
    // Normalize
    $name = strtolower($name);
    $name = str_replace(['&amp;', '&', ' and ', ',', '-', '(', ')', '/'], ' ', $name);
    $name = preg_replace('/[^a-z0-9\s]/', '', $name);
    $name = preg_replace('/\s+/', '', $name);
    return $name;
}

// Match categories to attachments
$matches = [];
$no_match = [];

foreach ($categories as $cat) {
    $norm = normalize_cat_name($cat->name);
    $found = false;

    // Try exact match
    if (isset($att_lookup[$norm])) {
        $matches[] = ['term_id' => $cat->term_id, 'name' => $cat->name, 'att_id' => $att_lookup[$norm], 'method' => 'exact'];
        $found = true;
    }

    // Try partial matches
    if (!$found) {
        foreach ($att_lookup as $key => $att_id) {
            if (strlen($norm) >= 8 && strlen($key) >= 8) {
                // Check if one contains the other
                if (strpos($key, $norm) !== false || strpos($norm, $key) !== false) {
                    $matches[] = ['term_id' => $cat->term_id, 'name' => $cat->name, 'att_id' => $att_id, 'method' => 'partial'];
                    $found = true;
                    break;
                }
                // Check similarity
                $shorter = strlen($norm) < strlen($key) ? $norm : $key;
                $longer = strlen($norm) >= strlen($key) ? $norm : $key;
                if (strlen($shorter) > 10 && strpos($longer, substr($shorter, 0, 10)) !== false) {
                    $matches[] = ['term_id' => $cat->term_id, 'name' => $cat->name, 'att_id' => $att_id, 'method' => 'prefix'];
                    $found = true;
                    break;
                }
            }
        }
    }

    if (!$found) {
        $no_match[] = "{$cat->name} ({$cat->product_count} products) [tried: $norm]";
    }
}

echo "=== RESULTS ===\n";
echo "Matched: " . count($matches) . "\n";
echo "Not matched: " . count($no_match) . "\n\n";

if ($action === 'preview') {
    echo "=== SAMPLE MATCHES (first 30) ===\n";
    foreach (array_slice($matches, 0, 30) as $m) {
        $att_title = get_the_title($m['att_id']);
        echo "'{$m['name']}' -> {$att_title} [{$m['method']}]\n";
    }

    echo "\n=== NOT MATCHED (first 30) ===\n";
    foreach (array_slice($no_match, 0, 30) as $nm) {
        echo "$nm\n";
    }

    echo "\nTo apply, add ?action=import\n";
}
elseif ($action === 'import') {
    echo "=== ASSIGNING ===\n";
    $assigned = 0;
    foreach ($matches as $m) {
        update_term_meta($m['term_id'], 'thumbnail_id', $m['att_id']);
        $assigned++;
        if ($assigned <= 20) {
            echo "Assigned to: {$m['name']}\n";
        }
    }
    echo "\nAssigned: $assigned\n";
}

// Now assign to products
echo "\n=== CURRENT STATUS ===\n";
$prods_with_thumb = $wpdb->get_var("
    SELECT COUNT(DISTINCT p.ID)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    AND pm.meta_value IS NOT NULL AND pm.meta_value != '' AND pm.meta_value != '0'
");
$total_prods = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
echo "Products with thumbnails: $prods_with_thumb / $total_prods\n";
