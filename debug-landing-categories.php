<?php
/**
 * Debug vehicle landing page category logic
 */

require_once(__DIR__ . '/wp-load.php');

$vin = 'LSFAM120XNA160733'; // T90 EV

echo "<h1>Debug Vehicle Landing Page</h1>";
echo "<p>VIN: $vin</p><hr>";

// Step 1: Get valid category IDs
echo "<h2>Step 1: Get valid category IDs</h2>";
$valid_category_ids = maxus_get_valid_category_ids_for_vin($vin);
echo "<p>Valid category IDs count: " . count($valid_category_ids) . "</p>";
if (!empty($valid_category_ids)) {
    echo "<p>First 10 IDs: " . implode(', ', array_slice($valid_category_ids, 0, 10)) . "</p>";
}

// Step 2: Find the serial term
echo "<h2>Step 2: Find serial term</h2>";
$serial_term = get_term_by('name', $vin, 'product_cat');
if ($serial_term) {
    echo "<p>✓ Serial term found:</p>";
    echo "<ul>";
    echo "<li>Term ID: {$serial_term->term_id}</li>";
    echo "<li>Name: {$serial_term->name}</li>";
    echo "<li>Slug: {$serial_term->slug}</li>";
    echo "<li>Parent: {$serial_term->parent}</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Serial term NOT found!</p>";
    
    // Try to find it by slug
    echo "<h3>Trying by slug...</h3>";
    $serial_term_by_slug = get_term_by('slug', strtolower($vin), 'product_cat');
    if ($serial_term_by_slug) {
        echo "<p>✓ Found by slug:</p>";
        echo "<ul>";
        echo "<li>Term ID: {$serial_term_by_slug->term_id}</li>";
        echo "<li>Name: {$serial_term_by_slug->name}</li>";
        echo "<li>Slug: {$serial_term_by_slug->slug}</li>";
        echo "</ul>";
        $serial_term = $serial_term_by_slug;
    }
    
    // Search all terms for partial match
    echo "<h3>Searching all terms containing VIN...</h3>";
    $all_terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'search' => $vin,
    ]);
    if (!empty($all_terms)) {
        echo "<p>Found " . count($all_terms) . " terms matching '$vin':</p>";
        echo "<ul>";
        foreach ($all_terms as $term) {
            echo "<li>ID: {$term->term_id}, Name: {$term->name}, Slug: {$term->slug}, Parent: {$term->parent}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No terms found</p>";
    }
}

if ($serial_term) {
    // Step 3: Get main categories
    echo "<h2>Step 3: Get main categories (children of serial term)</h2>";
    $main_categories = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => $serial_term->term_id,
        'hide_empty' => false,
    ]);
    
    echo "<p>Main categories found: " . count($main_categories) . "</p>";
    
    if (!empty($main_categories)) {
        echo "<ul>";
        foreach (array_slice($main_categories, 0, 10) as $cat) {
            echo "<li>ID: {$cat->term_id}, Name: {$cat->name}, Parent: {$cat->parent}</li>";
        }
        if (count($main_categories) > 10) {
            echo "<li>... and " . (count($main_categories) - 10) . " more</li>";
        }
        echo "</ul>";
    }
}
