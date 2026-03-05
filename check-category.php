<?php
require_once('wp-load.php');
header('Content-Type: text/plain');

$cat_slug = 'antenna';
$parent_slug = 'lsfal11a4pa157987';

// Get the category
$parent = get_term_by('slug', $parent_slug, 'product_cat');
$cat = get_term_by('slug', $cat_slug, 'product_cat');

if (!$cat) {
    echo "Category not found\n";
    exit;
}

echo "Category: {$cat->name} (ID: {$cat->term_id})\n";
echo "Parent: {$parent->name}\n";
echo "Product count: {$cat->count}\n\n";

// Get all products in this category
$products = get_posts([
    'post_type' => 'product',
    'posts_per_page' => -1,
    'tax_query' => [
        [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $cat->term_id,
        ]
    ],
    'orderby' => 'title',
    'order' => 'ASC',
]);

echo "Products in category:\n";
echo str_repeat('-', 80) . "\n";

$by_title = [];
foreach ($products as $p) {
    $sku = get_post_meta($p->ID, '_sku', true);
    echo "ID: {$p->ID} | SKU: $sku | {$p->post_title}\n";

    $by_title[$p->post_title][] = [
        'id' => $p->ID,
        'sku' => $sku,
    ];
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "Duplicate titles in this category:\n";

foreach ($by_title as $title => $items) {
    if (count($items) > 1) {
        echo "\n\"$title\" - " . count($items) . " products:\n";
        foreach ($items as $item) {
            echo "  ID: {$item['id']} | SKU: {$item['sku']}\n";
        }
    }
}
