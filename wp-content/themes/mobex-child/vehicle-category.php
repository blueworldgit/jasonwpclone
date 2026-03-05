<?php
/**
 * Template: Vehicle Category Page
 * URL: /e-deliver-9/brakes/
 * Shows subcategories (diagrams) within a parent category for this vehicle
 */

get_header();

global $maxus_current_vehicle, $wpdb;

$vehicle = $maxus_current_vehicle;
$category_slug = get_query_var('maxus_category');

// Template: vehicle-category.php

// Make sure we have vehicle data
if (!$vehicle || !isset($vehicle['vin'])) {
    echo '<div class="maxus-vehicle-page"><p>Error: Vehicle data not loaded. Vehicle slug: ' . esc_html(get_query_var('maxus_vehicle')) . '</p></div>';
    get_footer();
    return;
}

$vin = $vehicle['vin'];

// Get the parent category by slug
$parent_category = get_term_by('slug', $category_slug, 'product_cat');

if (!$parent_category) {
    // Category not found - show 404-like message
    ?>
    <div class="maxus-vehicle-page">
        <nav class="maxus-breadcrumbs">
            <a href="<?php echo home_url(); ?>">Home</a>
            <span class="separator">&rsaquo;</span>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>"><?php echo esc_html($vehicle['name']); ?></a>
            <span class="separator">&rsaquo;</span>
            <span class="current">Not Found</span>
        </nav>
        <div class="vehicle-page-header">
            <h1>Category Not Found</h1>
            <p>The category "<?php echo esc_html($category_slug); ?>" was not found.</p>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>" class="back-link">&larr; Back to <?php echo esc_html($vehicle['name']); ?></a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Get valid subcategories for this vehicle and parent category
$valid_subcat_slugs = maxus_get_valid_subcategories($vehicle['slug'], $parent_category->slug);

$subcategories = [];

if (!empty($valid_subcat_slugs)) {
    // Get subcategories that are valid for this vehicle
    $all_subcats = $wpdb->get_results($wpdb->prepare("
        SELECT t.term_id, t.name, t.slug, tt.count
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        WHERE tt.taxonomy = 'product_cat'
        AND tt.parent = %d
        AND t.slug IN ('" . implode("','", array_map('esc_sql', $valid_subcat_slugs)) . "')
        ORDER BY t.name
    ", $parent_category->term_id));

    foreach ($all_subcats as $subcat) {
        // Count products in this subcategory that match vehicle SKUs
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
            INNER JOIN {$wpdb->prefix}sku_vin_mapping svm ON pm.meta_value = svm.sku
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.term_id = %d
            AND tt.taxonomy = 'product_cat'
            AND p.post_type IN ('product', 'product_variation')
            AND svm.vin = %s
        ", $subcat->term_id, $vin));

        if ($count > 0) {
            // Get SVG diagram path if exists
            $svg_path = get_term_meta($subcat->term_id, 'svg_diagram', true);

            $subcategories[] = [
                'term' => $subcat,
                'count' => $count,
                'svg_path' => $svg_path,
                'url' => home_url('/' . $vehicle['slug'] . '/' . $parent_category->slug . '/' . $subcat->slug . '/'),
            ];
        }
    }
}


// Sort by name
usort($subcategories, function($a, $b) {
    return strcmp($a['term']->name, $b['term']->name);
});

// Calculate total parts in this category for this vehicle
$total_parts = array_sum(array_column($subcategories, 'count'));
?>

<div class="maxus-vehicle-page">
    <!-- Breadcrumbs -->
    <nav class="maxus-breadcrumbs">
        <a href="<?php echo home_url(); ?>">Home</a>
        <span class="separator">&rsaquo;</span>
        <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>"><?php echo esc_html($vehicle['name']); ?></a>
        <span class="separator">&rsaquo;</span>
        <span class="current"><?php echo esc_html($parent_category->name); ?></span>
    </nav>

    <!-- Header -->
    <div class="vehicle-page-header">
        <h1><?php echo esc_html($parent_category->name); ?></h1>
        <p class="vehicle-context">
            <span class="vehicle-name"><?php echo esc_html($vehicle['name']); ?></span>
            <span class="year">(<?php echo esc_html($vehicle['year']); ?>)</span>
        </p>
        <p class="part-count"><?php echo number_format($total_parts); ?> parts in <?php echo count($subcategories); ?> diagrams</p>
    </div>

    <?php if (empty($subcategories)): ?>
        <div class="no-parts-message">
            <p>No parts found in this category for <?php echo esc_html($vehicle['name']); ?>.</p>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>" class="back-link">&larr; Browse all categories</a>
        </div>
    <?php else: ?>
        <!-- Subcategory Grid -->
        <div class="vehicle-subcategory-grid">
            <?php foreach ($subcategories as $subcat): ?>
                <a href="<?php echo esc_url($subcat['url']); ?>" class="vehicle-subcategory-card">
                    <div class="subcategory-thumbnail">
                        <?php
                        if ($subcat['svg_path']) {
                            $svg_url = content_url('/uploads/' . $subcat['svg_path']);
                            echo '<img class="svg-thumb" src="' . esc_url($svg_url) . '" alt="" loading="lazy">';
                        } else {
                            $thumb_id = get_term_meta($subcat['term']->term_id, 'thumbnail_id', true);
                            if ($thumb_id) {
                                echo wp_get_attachment_image($thumb_id, 'medium');
                            } else {
                                echo '<div class="no-thumb-placeholder">No diagram</div>';
                            }
                        }
                        ?>
                    </div>
                    <h3 class="subcategory-name"><?php echo esc_html($subcat['term']->name); ?></h3>
                    <span class="subcategory-count"><?php echo number_format($subcat['count']); ?> parts</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

.vehicle-page-header h1 {
    margin: 0 0 8px;
    font-size: 32px;
    color: #333;
}

.vehicle-page-header .vehicle-context {
    margin: 0 0 10px;
    font-size: 16px;
    color: #666;
}

.vehicle-page-header .vehicle-context .vehicle-name {
    color: #F29F05;
    font-weight: 600;
}

.vehicle-page-header .vehicle-context .year {
    color: #888;
}

.vehicle-page-header .part-count {
    margin: 0;
    font-size: 18px;
    color: #666;
}

.no-parts-message {
    text-align: center;
    padding: 40px;
    background: #f9f9f9;
    border-radius: 8px;
}

.no-parts-message p {
    margin: 0 0 20px;
    font-size: 18px;
    color: #666;
}

.back-link {
    color: #333;
    text-decoration: none;
}

.back-link:hover {
    text-decoration: underline;
}

.vehicle-subcategory-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.vehicle-subcategory-card {
    display: block;
    padding: 20px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
}

.vehicle-subcategory-card:hover {
    border-color: #F29F05;
    box-shadow: 0 4px 12px rgba(255, 102, 0, 0.15);
    transform: translateY(-2px);
}

.subcategory-thumbnail {
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

.svg-thumb-wrap {
    max-width: 100%;
    max-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.svg-thumb-wrap svg {
    width: 100%;
    height: 100%;
}

.subcategory-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.no-thumb-placeholder {
    font-size: 13px;
    color: #aaa;
}

.vehicle-subcategory-card .subcategory-name {
    margin: 0 0 8px;
    font-size: 15px;
    font-weight: 600;
    color: #333;
    line-height: 1.3;
}

.vehicle-subcategory-card:hover .subcategory-name {
    color: #F29F05;
}

.vehicle-subcategory-card .subcategory-count {
    font-size: 14px;
    color: #888;
}

@media (max-width: 768px) {
    .vehicle-subcategory-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .vehicle-subcategory-card {
        padding: 15px;
    }

    .vehicle-page-header h1 {
        font-size: 24px;
    }

    .vehicle-subcategory-card .subcategory-name {
        font-size: 13px;
    }
}
</style>

<?php get_footer(); ?>
