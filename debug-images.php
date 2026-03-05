<?php
// Simple debug - no auth required
require_once __DIR__ . '/wp-load.php';

header('Content-Type: text/plain');

// Get subcategories of Deliver 9 FWD LUX (ID 3591)
$subcats = get_terms([
    'taxonomy' => 'product_cat',
    'parent' => 3591,
    'hide_empty' => false,
    'number' => 10,
]);

echo "Subcategories of Deliver 9 FWD LUX (3591):\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($subcats as $subcat) {
    echo "Category: {$subcat->name} (ID: {$subcat->term_id})\n";

    $thumbnail_id = get_term_meta($subcat->term_id, 'thumbnail_id', true);
    echo "  thumbnail_id meta: " . var_export($thumbnail_id, true) . "\n";

    if ($thumbnail_id) {
        $attachment = get_post($thumbnail_id);
        echo "  Attachment exists: " . ($attachment ? 'YES' : 'NO') . "\n";

        if ($attachment) {
            echo "  Attachment type: {$attachment->post_type}\n";
            echo "  Attachment status: {$attachment->post_status}\n";
        }

        $url = wp_get_attachment_url($thumbnail_id);
        echo "  wp_get_attachment_url(): " . var_export($url, true) . "\n";

        $image = wp_get_attachment_image($thumbnail_id, 'thumbnail');
        echo "  wp_get_attachment_image(): " . (empty($image) ? '(empty)' : substr($image, 0, 100) . '...') . "\n";

        $file = get_attached_file($thumbnail_id);
        echo "  get_attached_file(): $file\n";
        echo "  File exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";
    }

    echo "\n";
}
