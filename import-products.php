<?php
/**
 * Product Import Script for Maxus Van Parts
 * Imports products from WordPress XML export files with WooCommerce support
 *
 * Usage: Access via browser at https://maxusvanparts.local/import-products.php
 * Add ?file=0 to start with first file, ?file=1 for second, etc.
 */

// Increase limits for large import
ini_set('max_execution_time', 0);
ini_set('memory_limit', '2G');
error_reporting(E_ALL);

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Security check
$allowed = (
    in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) ||
    (isset($_GET['key']) && $_GET['key'] === 'maxus2026import')
);

if (!$allowed) {
    die('Access denied. Add ?key=maxus2026import to URL');
}

echo "<!DOCTYPE html><html><head><title>Product Import</title></head><body><pre>\n";
echo "=== Maxus Van Parts Product Import ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
flush();

// Get XML files from site root directory (Local Sites/maxusvanparts/)
// Script is at: app/public/import-products.php
// XML files at: maxusvanparts/mywebsite.*.xml (2 levels up)
$xml_dir = dirname(__FILE__) . '/../..';
$xml_files = glob($xml_dir . '/mywebsite.wordpress.2026-01-30.*.xml');
sort($xml_files);

if (empty($xml_files)) {
    die("No XML files found in: $xml_dir\n");
}

echo "Found " . count($xml_files) . " XML files\n";

// Determine which file to process
$file_index = isset($_GET['file']) ? (int)$_GET['file'] : 0;

if ($file_index < 0 || $file_index >= count($xml_files)) {
    echo "\nAll files processed! Start with ?file=0 to reimport.\n";
    echo "</pre></body></html>";
    exit;
}

$xml_file = $xml_files[$file_index];
echo "Processing file " . ($file_index + 1) . "/" . count($xml_files) . ": " . basename($xml_file) . "\n\n";
flush();

// Parse XML
$xml = simplexml_load_file($xml_file);
if (!$xml) {
    die("ERROR: Could not parse XML file\n");
}

// Register namespaces
$namespaces = $xml->getNamespaces(true);

// Track stats
$imported = 0;
$skipped = 0;
$errors = 0;
$attachments_mapped = 0;

// First pass: Build attachment URL to ID mapping from existing uploads
echo "Building attachment mapping...\n";
$attachment_map = array();

// Map by filename from uploads
$upload_dir = wp_upload_dir();
$upload_base = $upload_dir['basedir'];

// Get items (posts)
$items = $xml->channel->item;
$item_count = count($items);
echo "Found $item_count items in this file\n\n";

// Process attachments first to build mapping
echo "Pass 1: Processing attachments...\n";
foreach ($items as $item) {
    $wp_data = $item->children($namespaces['wp']);
    $post_type = (string)$wp_data->post_type;

    if ($post_type === 'attachment') {
        $attachment_url = (string)$wp_data->attachment_url;
        $guid = (string)$item->guid;

        // Extract filename
        $filename = basename(parse_url($attachment_url, PHP_URL_PATH));

        // Check if this file exists in our uploads
        $files = glob($upload_base . '/**/' . $filename, GLOB_NOSORT);
        if (!empty($files)) {
            $local_file = $files[0];
            // Try to find existing attachment by file
            global $wpdb;
            $attachment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid LIKE %s",
                '%' . $filename
            ));

            if (!$attachment_id) {
                // Create attachment record
                $filetype = wp_check_filetype($local_file);
                $attachment_data = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attachment_id = wp_insert_attachment($attachment_data, $local_file);

                if ($attachment_id && !is_wp_error($attachment_id)) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $local_file);
                    wp_update_attachment_metadata($attachment_id, $attach_data);
                }
            }

            if ($attachment_id) {
                $attachment_map[$attachment_url] = $attachment_id;
                $attachment_map[$guid] = $attachment_id;
                $attachments_mapped++;
            }
        }
    }
}
echo "Mapped $attachments_mapped attachments\n\n";

// Process products
echo "Pass 2: Importing products...\n";
foreach ($items as $item) {
    $wp_data = $item->children($namespaces['wp']);
    $content_data = $item->children($namespaces['content']);
    $excerpt_data = $item->children($namespaces['excerpt']);

    $post_type = (string)$wp_data->post_type;
    if ($post_type !== 'product') {
        continue;
    }

    $title = (string)$item->title;
    $slug = (string)$wp_data->post_name;
    $status = (string)$wp_data->status;
    $content = isset($content_data->encoded) ? (string)$content_data->encoded : '';
    $excerpt = isset($excerpt_data->encoded) ? (string)$excerpt_data->encoded : '';

    // Check if product already exists
    global $wpdb;
    $existing_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'product'",
        $slug
    ));

    if ($existing_id) {
        $skipped++;
        continue;
    }

    // Create product
    $product_data = array(
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_status' => $status === 'publish' ? 'publish' : 'draft',
        'post_type' => 'product',
    );

    $product_id = wp_insert_post($product_data, true);

    if (is_wp_error($product_id)) {
        echo "ERROR: $title - " . $product_id->get_error_message() . "\n";
        $errors++;
        continue;
    }

    // Import post meta
    $thumbnail_id = null;
    $gallery_ids = array();

    foreach ($wp_data->postmeta as $meta) {
        $meta_key = (string)$meta->meta_key;
        $meta_value = (string)$meta->meta_value;

        // Skip some internal meta
        if (in_array($meta_key, ['_edit_lock', '_edit_last'])) {
            continue;
        }

        // Handle thumbnail
        if ($meta_key === '_thumbnail_id') {
            // Will be remapped after
            $old_thumb_id = $meta_value;
            continue;
        }

        // Handle product gallery
        if ($meta_key === '_product_image_gallery') {
            // Will be remapped after
            $old_gallery = $meta_value;
            continue;
        }

        // Handle serialized data
        $unserialized = @unserialize($meta_value);
        if ($unserialized !== false) {
            $meta_value = $unserialized;
        }

        update_post_meta($product_id, $meta_key, $meta_value);
    }

    // Import categories
    $categories = array();
    foreach ($item->category as $cat) {
        $domain = (string)$cat['domain'];
        $nicename = (string)$cat['nicename'];

        if ($domain === 'product_cat') {
            $term = get_term_by('slug', $nicename, 'product_cat');
            if ($term) {
                $categories[] = $term->term_id;
            }
        }
    }

    if (!empty($categories)) {
        wp_set_post_terms($product_id, $categories, 'product_cat');
    }

    // Update WooCommerce lookup table
    if (function_exists('wc_get_product')) {
        $product = wc_get_product($product_id);
        if ($product) {
            $product->save();
        }
    }

    $imported++;

    if ($imported % 100 === 0) {
        echo "Progress: $imported products imported...\n";
        flush();
    }
}

echo "\n=== File Complete ===\n";
echo "Products imported: $imported\n";
echo "Products skipped: $skipped\n";
echo "Errors: $errors\n";

// Link to next file
$next_file = $file_index + 1;
if ($next_file < count($xml_files)) {
    echo "\n<a href='?file=$next_file&key=maxus2026import'>Continue to file " . ($next_file + 1) . " &gt;&gt;</a>\n";
} else {
    echo "\n=== ALL FILES COMPLETE ===\n";

    // Update category counts
    echo "Updating category counts...\n";
    $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
    foreach ($terms as $term) {
        wp_update_term_count_now(array($term->term_id), 'product_cat');
    }

    // Final count
    $total_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'");
    echo "Total products in database: $total_products\n";
}

echo "</pre></body></html>";
