<?php
/**
 * Explore actual category structure in database
 */

require_once(__DIR__ . '/wp-load.php');
global $wpdb;

echo "<h1>Category Structure Analysis</h1>";

// Get the Maxus brand category
$maxus = get_term_by('name', 'Maxus', 'product_cat');
if (!$maxus) {
    $maxus = get_term_by('slug', 'maxus', 'product_cat');
}

if ($maxus) {
    echo "<h2>Found 'Maxus' brand category</h2>";
    echo "<ul>";
    echo "<li>ID: {$maxus->term_id}</li>";
    echo "<li>Name: {$maxus->name}</li>";
    echo "<li>Slug: {$maxus->slug}</li>";
    echo "</ul>";
    
    // Get children of Maxus (should be VIN/model categories)
    echo "<h3>Children of Maxus (VIN categories):</h3>";
    $vins = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => $maxus->term_id,
        'hide_empty' => false,
    ]);
    
    echo "<p>Found " . count($vins) . " VIN categories:</p>";
    echo "<ul>";
    foreach ($vins as $vin) {
        echo "<li>ID: {$vin->term_id}, Name: {$vin->name}, Slug: {$vin->slug}";
        
        // Check if this is T90 EV
        if (strpos($vin->name, 'LSFAM120XNA160733') !== false || strpos($vin->slug, 't90') !== false) {
            echo " <strong style='color: green;'>&larr; T90 EV?</strong>";
        }
        echo "</li>";
    }
    echo "</ul>";
    
    // Check for T90 EV specifically
    echo "<h3>Looking for T90 EV category:</h3>";
    $t90_searches = [
        'LSFAM120XNA160733',
        'lsfam120xna160733',
        't90',
        'T90',
        'maxus-t90-ev',
    ];
    
    foreach ($t90_searches as $search) {
        $term = get_term_by('name', $search, 'product_cat');
        if (!$term) {
            $term = get_term_by('slug', $search, 'product_cat');
        }
        if ($term) {
            echo "<p>✓ Found with search '$search':</p>";
            echo "<ul>";
            echo "<li>ID: {$term->term_id}</li>";
            echo "<li>Name: {$term->name}</li>";
            echo "<li>Slug: {$term->slug}</li>";
            echo "<li>Parent: {$term->parent}</li>";
            echo "</ul>";
            
            // Get its main categories
            $mains = get_terms([
                'taxonomy' => 'product_cat',
                'parent' => $term->term_id,
                'hide_empty' => false,
            ]);
            echo "<p>Main categories under this: " . count($mains) . "</p>";
            if (!empty($mains)) {
                echo "<ul>";
                foreach (array_slice($mains, 0, 10) as $main) {
                    echo "<li>{$main->name}</li>";
                }
                echo "</ul>";
            }
            break;
        }
    }
} else {
    echo "<p style='color: red;'>Maxus brand category not found!</p>";
    
    // List all top-level categories
    echo "<h2>All top-level categories (parent=0):</h2>";
    $top_cats = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => 0,
        'hide_empty' => false,
    ]);
    
    echo "<ul>";
    foreach ($top_cats as $cat) {
        echo "<li>ID: {$cat->term_id}, Name: {$cat->name}, Slug: {$cat->slug}</li>";
    }
    echo "</ul>";
}

// Check sku_vin_mapping
echo "<h2>SKU-VIN Mapping Check</h2>";
$vin_check = 'LSFAM120XNA160733';
$count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sku_vin_mapping WHERE vin = %s",
    $vin_check
));
echo "<p>SKUs mapped to VIN '$vin_check': $count</p>";
