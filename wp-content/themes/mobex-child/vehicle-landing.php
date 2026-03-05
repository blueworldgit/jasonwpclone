<?php
/**
 * Template: Vehicle Landing Page
 * URL: /e-deliver-9/
 * Shows all categories with parts for this vehicle
 */

get_header();

global $maxus_current_vehicle, $wpdb;

$vehicle = $maxus_current_vehicle;
$vin = $vehicle['vin'];

// Get all SKUs for this vehicle
$vehicle_skus = $wpdb->get_col($wpdb->prepare(
    "SELECT sku FROM {$wpdb->prefix}sku_vin_mapping WHERE vin = %s",
    $vin
));

$total_parts = count($vehicle_skus);

// Get valid categories for this vehicle from the source mapping
$valid_categories = maxus_get_vehicle_categories($vehicle['slug']);
$valid_parent_slugs = array_keys($valid_categories);

// Get parent categories (where parent=0) that are valid for this vehicle
$parent_categories = [];

$all_parents = get_terms([
    'taxonomy' => 'product_cat',
    'parent' => 0,
    'hide_empty' => false,
]);

foreach ($all_parents as $parent) {
    // Only show categories that are in the valid list for this vehicle
    if (!in_array($parent->slug, $valid_parent_slugs)) {
        continue;
    }

    // Get valid subcategory slugs for this parent
    $valid_subcats = $valid_categories[$parent->slug];

    // Get term IDs for valid subcategories
    $subcat_terms = get_terms([
        'taxonomy' => 'product_cat',
        'slug' => $valid_subcats,
        'hide_empty' => false,
    ]);

    $subcat_ids = array_map(function($t) { return $t->term_id; }, $subcat_terms);

    if (empty($subcat_ids)) {
        continue;
    }

    $cat_ids_str = implode(',', array_map('intval', $subcat_ids));

    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT p.ID)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
        INNER JOIN {$wpdb->prefix}sku_vin_mapping svm ON pm.meta_value = svm.sku
        WHERE tt.term_id IN ({$cat_ids_str})
        AND tt.taxonomy = 'product_cat'
        AND p.post_type IN ('product', 'product_variation')
        AND svm.vin = %s
    ", $vin));

    if ($count > 0) {
        $parent_categories[] = [
            'term' => $parent,
            'count' => $count,
            'subcategory_count' => count($valid_subcats),
            'url' => home_url('/' . $vehicle['slug'] . '/' . $parent->slug . '/'),
        ];
    }
}

// Sort by name
usort($parent_categories, function($a, $b) {
    return strcmp($a['term']->name, $b['term']->name);
});
?>

<div class="maxus-vehicle-page">
    <!-- Breadcrumbs -->
    <nav class="maxus-breadcrumbs">
        <a href="<?php echo home_url(); ?>">Home</a>
        <span class="separator">&rsaquo;</span>
        <span class="current"><?php echo esc_html($vehicle['name']); ?></span>
    </nav>

    <!-- Header -->
    <div class="vehicle-page-header">
        <div class="vehicle-header-content">
            <div class="vehicle-header-text">
                <h1><?php echo esc_html($vehicle['name']); ?> <span class="year">(<?php echo esc_html($vehicle['year']); ?>)</span></h1>
                <p class="part-count"><?php echo number_format($total_parts); ?> parts available</p>
            </div>
            <?php
            // Get vehicle image from taxonomy term (term slug = lowercase VIN)
            $vin_slug = strtolower($vehicle['vin']);
            $vehicle_thumb_id = $wpdb->get_var($wpdb->prepare("
                SELECT tm.meta_value FROM {$wpdb->terms} t
                JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = 'thumbnail_id'
                WHERE t.slug = %s AND tt.taxonomy = 'vehicles'
                LIMIT 1
            ", $vin_slug));
            if ($vehicle_thumb_id):
            ?>
                <div class="vehicle-header-image">
                    <?php echo wp_get_attachment_image($vehicle_thumb_id, 'full'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Grid -->
    <div class="vehicle-category-grid">
        <?php foreach ($parent_categories as $cat): ?>
            <a href="<?php echo esc_url($cat['url']); ?>" class="vehicle-category-card">
                <div class="category-thumbnail">
                    <?php
                    $thumb_id = get_term_meta($cat['term']->term_id, 'thumbnail_id', true);
                    if ($thumb_id) {
                        echo wp_get_attachment_image($thumb_id, 'full');
                    } else {
                        echo '<div class="no-thumb-placeholder">No image</div>';
                    }
                    ?>
                </div>
                <h3 class="category-name"><?php echo esc_html($cat['term']->name); ?></h3>
                <span class="category-count"><?php echo number_format($cat['count']); ?> parts</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
.maxus-vehicle-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.maxus-breadcrumbs {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

.maxus-breadcrumbs a {
    color: #333;
    text-decoration: none;
}

.maxus-breadcrumbs a:hover {
    color: #F29F05;
    text-decoration: underline;
}

.maxus-breadcrumbs .separator {
    margin: 0 8px;
    color: #999;
}

.vehicle-page-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #F29F05;
}

.vehicle-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.vehicle-header-text {
    flex: 1;
}

.vehicle-header-image {
    flex: 0 0 auto;
    width: 200px;
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.vehicle-header-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.vehicle-page-header h1 {
    margin: 0 0 10px;
    font-size: 32px;
    color: #333;
}

.vehicle-page-header h1 .year {
    color: #666;
    font-weight: normal;
}

.vehicle-page-header .part-count {
    margin: 0;
    font-size: 18px;
    color: #666;
}

.vehicle-category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.vehicle-category-card {
    display: block;
    padding: 20px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
}

.vehicle-category-card:hover {
    border-color: #F29F05;
    box-shadow: 0 4px 12px rgba(255, 102, 0, 0.15);
    transform: translateY(-2px);
}

.category-thumbnail {
    width: 100%;
    aspect-ratio: 4 / 3;
    margin-bottom: 12px;
    background: #fff;
    border-radius: 6px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    box-sizing: border-box;
}

.category-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.no-thumb-placeholder {
    font-size: 13px;
    color: #aaa;
}

.vehicle-category-card .category-name {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.vehicle-category-card:hover .category-name {
    color: #F29F05;
}

.vehicle-category-card .category-count {
    font-size: 14px;
    color: #888;
}

@media (max-width: 768px) {
    .vehicle-category-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .vehicle-category-card {
        padding: 15px;
    }

    .vehicle-page-header h1 {
        font-size: 24px;
    }

    .vehicle-header-image {
        width: 120px;
        height: 90px;
    }
}
</style>

<?php get_footer(); ?>
