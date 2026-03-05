<?php
/**
 * Regenerate attachment metadata for category thumbnails
 * Run via: https://maxusvanparts.local/regenerate-thumbnails.php
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Please log in as admin first.');
}

echo "<h1>Regenerating Attachment Metadata</h1>\n";
echo "<pre>\n";

// Get all category thumbnails that are missing metadata
global $wpdb;

$attachments = $wpdb->get_results("
    SELECT DISTINCT tm.meta_value as attachment_id
    FROM {$wpdb->termmeta} tm
    WHERE tm.meta_key = 'thumbnail_id'
    AND tm.meta_value != ''
    AND tm.meta_value != '0'
    AND NOT EXISTS (
        SELECT 1 FROM {$wpdb->postmeta} pm
        WHERE pm.post_id = tm.meta_value
        AND pm.meta_key = '_wp_attachment_metadata'
    )
");

echo "Found " . count($attachments) . " attachments needing metadata regeneration\n\n";

$fixed = 0;
$errors = 0;

foreach ($attachments as $att) {
    $attachment_id = intval($att->attachment_id);

    // Get file path
    $file = get_attached_file($attachment_id);

    if (!$file || !file_exists($file)) {
        echo "ERROR: Attachment $attachment_id - file not found: $file\n";
        $errors++;
        continue;
    }

    // Generate metadata
    $metadata = wp_generate_attachment_metadata($attachment_id, $file);

    if (empty($metadata)) {
        echo "ERROR: Attachment $attachment_id - failed to generate metadata\n";
        $errors++;
        continue;
    }

    // Save metadata
    wp_update_attachment_metadata($attachment_id, $metadata);

    $title = get_the_title($attachment_id);
    echo "OK: $attachment_id - $title\n";
    $fixed++;

    // Flush output
    if (ob_get_level()) ob_flush();
    flush();
}

echo "\n";
echo "=" . str_repeat("=", 60) . "\n";
echo "Fixed: $fixed\n";
echo "Errors: $errors\n";
echo "</pre>\n";
echo "<p><strong>Done!</strong> <a href='/shop/?model=Deliver%209%20FWD%20LUX&yr=2021'>Check the page now</a></p>";
