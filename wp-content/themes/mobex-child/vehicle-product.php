<?php
/**
 * Template: Vehicle Product Page
 * URL: /e-deliver-9/product/product-slug/
 * Shows single product within vehicle context with proper breadcrumbs
 */

get_header();

global $maxus_current_vehicle, $wpdb;

$vehicle = $maxus_current_vehicle;
$vin = $vehicle['vin'];
$product_slug = get_query_var('maxus_product');

// Get the product by slug
$product_post = get_page_by_path($product_slug, OBJECT, 'product');

if (!$product_post) {
    ?>
    <div class="maxus-vehicle-page">
        <nav class="maxus-breadcrumbs">
            <a href="<?php echo home_url(); ?>">Home</a>
            <span class="separator">&rsaquo;</span>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>"><?php echo esc_html($vehicle['name']); ?></a>
            <span class="separator">&rsaquo;</span>
            <span class="current">Product Not Found</span>
        </nav>
        <div class="vehicle-page-header">
            <h1>Product Not Found</h1>
            <p>The requested product was not found.</p>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>" class="back-link">&larr; Back to <?php echo esc_html($vehicle['name']); ?></a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Set up the product global for WooCommerce functions
global $post, $product;
$post = $product_post;
setup_postdata($post);
$product = wc_get_product($product_post->ID);

// Check for variation_id parameter - variable products need variation context
$variation_id = isset($_GET['variation_id']) ? intval($_GET['variation_id']) : 0;
$variation_post = null;
if ($variation_id) {
    $variation_post = get_post($variation_id);
    // Verify the variation belongs to this parent product
    if ($variation_post && $variation_post->post_parent !== $product_post->ID) {
        $variation_post = null;
        $variation_id = 0;
    }
}

// Get product SKU - prefer variation SKU if available
$sku = $product->get_sku();
if ($variation_post) {
    $variation_sku = get_post_meta($variation_id, '_sku', true);
    if ($variation_sku) {
        $sku = $variation_sku;
    }
}

// Get the product's categories - use cat_id from URL if available (preserves subcategory context)
$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
$terms = wp_get_post_terms($product_post->ID, 'product_cat', ['fields' => 'all']);
$breadcrumb_cat = null;
$parent_cat = null;

if ($cat_id) {
    // Use the specific category passed from the subcategory page
    $specific_term = get_term($cat_id, 'product_cat');
    if ($specific_term && !is_wp_error($specific_term)) {
        $breadcrumb_cat = $specific_term;
        if ($breadcrumb_cat->parent) {
            $parent_cat = get_term($breadcrumb_cat->parent, 'product_cat');
        }
    }
}

if (!$breadcrumb_cat && !empty($terms) && !is_wp_error($terms)) {
    // Fallback: filter out utility categories and sort by depth
    $valid_terms = array_filter($terms, function($term) {
        return !in_array($term->slug, ['priceupdated', 'imageupdated', 'uncategorized']);
    });

    // Sort by depth (deepest first), then prefer categories with SVG diagrams
    usort($valid_terms, function($a, $b) {
        $depth_a = count(get_ancestors($a->term_id, 'product_cat', 'taxonomy'));
        $depth_b = count(get_ancestors($b->term_id, 'product_cat', 'taxonomy'));
        if ($depth_b !== $depth_a) {
            return $depth_b - $depth_a;
        }
        // At same depth, prefer category with SVG diagram
        $svg_a = get_term_meta($a->term_id, 'svg_diagram', true) ? 1 : 0;
        $svg_b = get_term_meta($b->term_id, 'svg_diagram', true) ? 1 : 0;
        return $svg_b - $svg_a;
    });

    if (!empty($valid_terms)) {
        $breadcrumb_cat = reset($valid_terms);
        // Get parent category
        if ($breadcrumb_cat->parent) {
            $parent_cat = get_term($breadcrumb_cat->parent, 'product_cat');
        }
    }
}

// Get callout number for this category - check variation first, then parent
$callout = null;
if ($breadcrumb_cat) {
    $tt_id = $wpdb->get_var($wpdb->prepare(
        "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
        $breadcrumb_cat->term_id
    ));
    // Check variation meta first (has the correct category-specific callouts)
    if ($variation_id) {
        $callout = get_post_meta($variation_id, 'callout_cat_' . $tt_id, true);
        if (!$callout) {
            $callout = get_post_meta($variation_id, 'callout_number', true);
        }
    }
    // Fall back to parent product meta
    if (!$callout) {
        $callout = get_post_meta($product_post->ID, 'callout_cat_' . $tt_id, true);
    }
    if (!$callout) {
        $callout = get_post_meta($product_post->ID, 'callout_number', true);
    }
}

// Get SVG diagram path
$svg_path = $breadcrumb_cat ? get_term_meta($breadcrumb_cat->term_id, 'svg_diagram', true) : null;
$svg_full_path = $svg_path ? WP_CONTENT_DIR . '/uploads/' . $svg_path : null;
$has_svg = $svg_full_path && file_exists($svg_full_path);

// Get product image - check variation thumbnail, then parent
$image_id = $product->get_image_id();
if (!$image_id && $variation_id) {
    $image_id = get_post_meta($variation_id, '_thumbnail_id', true);
}
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : wc_placeholder_img_src('large');

// Check vehicle compatibility
$is_compatible = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sku_vin_mapping WHERE sku = %s AND vin = %s",
    $sku, $vin
));

// Get all compatible vehicles for this SKU
$compatible_vehicles = $wpdb->get_results($wpdb->prepare("
    SELECT DISTINCT vehicle_name, vehicle_year
    FROM {$wpdb->prefix}sku_vin_mapping
    WHERE sku = %s
    ORDER BY vehicle_name, vehicle_year
", $sku));
?>

<div class="maxus-vehicle-page maxus-product-page">
    <!-- Breadcrumbs -->
    <nav class="maxus-breadcrumbs">
        <a href="<?php echo home_url(); ?>">Home</a>
        <span class="separator">&rsaquo;</span>
        <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>"><?php echo esc_html($vehicle['name']); ?></a>
        <?php if ($parent_cat): ?>
            <span class="separator">&rsaquo;</span>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/' . $parent_cat->slug . '/'); ?>"><?php echo esc_html($parent_cat->name); ?></a>
        <?php endif; ?>
        <?php if ($breadcrumb_cat && (!$parent_cat || $breadcrumb_cat->term_id !== $parent_cat->term_id)): ?>
            <span class="separator">&rsaquo;</span>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/' . ($parent_cat ? $parent_cat->slug : $breadcrumb_cat->slug) . '/' . $breadcrumb_cat->slug . '/'); ?>"><?php echo esc_html($breadcrumb_cat->name); ?></a>
        <?php endif; ?>
        <span class="separator">&rsaquo;</span>
        <span class="current"><?php echo esc_html($product->get_name()); ?></span>
    </nav>

    <div class="product-main-content">
        <!-- Product Image / Diagram -->
        <div class="product-image-section">
            <?php if ($has_svg): ?>
                <div class="maxus-product-diagram-image">
                    <div class="diagram-svg-full" id="diagram-svg-container">
                        <?php echo file_get_contents($svg_full_path); ?>
                    </div>
                    <div class="diagram-callout-indicator">
                        <button type="button" class="zoom-btn zoom-out" id="zoom-out-btn" title="Zoom out">&#8722;</button>
                        <?php if ($callout): ?>
                            <span class="callout-center">Find this part: <span class="highlight-callout"><?php echo esc_html($callout); ?></span></span>
                        <?php endif; ?>
                        <button type="button" class="zoom-btn zoom-in" id="zoom-in-btn" title="Zoom in">&#43;</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="product-image-wrapper">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Summary -->
        <div class="product-summary-section">
            <h1 class="product-title"><?php echo esc_html($product->get_name()); ?></h1>

            <div class="product-meta-info">
                <span class="sku-label">SKU:</span>
                <span class="sku-value"><?php echo esc_html($sku); ?></span>
                <?php if ($callout): ?>
                    <span class="meta-separator">|</span>
                    <span class="callout-label">Part No:</span>
                    <span class="callout-value"><?php echo esc_html($callout); ?></span>
                <?php endif; ?>
                <?php
                $weight = $product->get_weight();
                if ((!$weight || $weight <= 0) && $variation_id) {
                    $weight = get_post_meta($variation_id, '_weight', true);
                }
                if ($weight && $weight > 0): ?>
                    <span class="meta-separator">|</span>
                    <span class="weight-label">Weight:</span>
                    <span class="weight-value"><?php echo esc_html($weight); ?><?php echo esc_html(get_option('woocommerce_weight_unit', 'kg')); ?></span>
                <?php endif; ?>
            </div>

            <div class="product-price">
                <?php
                // Show specific variation price if available, not the parent's range
                $display_price = null;
                if ($variation_id) {
                    $variation_obj = wc_get_product($variation_id);
                    if ($variation_obj && $variation_obj->get_price()) {
                        $display_price = $variation_obj->get_price();
                        echo wc_price($display_price);
                    } else {
                        // Variation exists but has no price — don't fall through to parent range
                        echo '<span class="price-request">Price on request</span>';
                    }
                } elseif ($product->get_price()) {
                    $display_price = $product->get_price();
                    echo $product->get_price_html();
                } else {
                    echo '<span class="price-request">Price on request</span>';
                }
                ?>
            </div>

            <div class="product-stock-status">
                <?php if ($product->is_in_stock()): ?>
                    <span class="in-stock">In Stock</span>
                <?php else: ?>
                    <span class="out-of-stock">Out of Stock</span>
                <?php endif; ?>
            </div>

            <?php
            // Use variation for purchasability check and add-to-cart if available
            $cart_product = ($variation_id && isset($variation_obj) && $variation_obj) ? $variation_obj : $product;
            $has_price = ($variation_id && isset($variation_obj) && $variation_obj) ? $variation_obj->get_price() : $product->get_price();
            if ($has_price && $cart_product->is_purchasable() && $cart_product->is_in_stock()):
            ?>
                <form class="cart" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
                    <div class="quantity-wrapper">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="99" class="qty-input">
                    </div>
                    <?php if ($variation_id): ?>
                        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>">
                        <input type="hidden" name="variation_id" value="<?php echo esc_attr($variation_id); ?>">
                    <?php else: ?>
                        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>">
                    <?php endif; ?>
                    <button type="submit" class="add-to-cart-button">Add to Cart</button>
                </form>
            <?php elseif (!$has_price): ?>
                <div class="price-enquiry-wrapper">
                    <?php echo str_replace('class="price-enquiry-btn"', 'class="price-enquiry-btn btn-large"', maxus_price_enquiry_button($sku, $product->get_name())); ?>
                </div>
            <?php endif; ?>

            <div class="maxus-delivery-time">
                <span class="delivery-label">Estimated Delivery:</span>
                <span class="delivery-value">
                    <?php
                    $delivery_time = get_post_meta($product->get_id(), '_estimated_delivery_time', true);
                    echo esc_html($delivery_time ?: '3-5 working days');
                    ?>
                </span>
            </div>


            <!-- Vehicle Compatibility -->
            <div class="maxus-vehicle-compatibility">
                <h4>Compatible Vehicles:</h4>
                <?php if (!empty($compatible_vehicles)): ?>
                    <ul>
                        <?php
                        // Group by vehicle name
                        $grouped = [];
                        foreach ($compatible_vehicles as $v) {
                            if (!isset($grouped[$v->vehicle_name])) {
                                $grouped[$v->vehicle_name] = [];
                            }
                            $grouped[$v->vehicle_name][] = $v->vehicle_year;
                        }
                        foreach ($grouped as $name => $years): ?>
                            <li>
                                <span class="vehicle-name"><?php echo esc_html($name); ?></span>
                                <span class="vehicle-year">(<?php echo esc_html(implode(', ', array_unique($years))); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="compatibility-note">Compatibility data not available for this product.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    // Product description - full width below main content
    $description = $product->get_description();
    if ($description): ?>
        <div class="maxus-product-description">
            <h4>Description</h4>
            <p><?php echo wp_kses_post($description); ?></p>
        </div>
    <?php endif; ?>

    <?php
    // Related products section - products from the same subcategory
    if ($breadcrumb_cat) {
        $related_args = [
            'post_type'      => ['product', 'product_variation'],
            'post_status'    => 'publish',
            'posts_per_page' => 5,
            'post__not_in'   => [$product_post->ID],
            'orderby'        => 'rand',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $breadcrumb_cat->term_id,
                ],
            ],
        ];

        $related_query = new WP_Query($related_args);

        if ($related_query->have_posts()):
    ?>
    <div class="maxus-related-products">
        <h3>Related Products</h3>
        <div class="related-products-grid">
            <?php while ($related_query->have_posts()): $related_query->the_post();
                $rel_product = wc_get_product(get_the_ID());
                if (!$rel_product) continue;

                // For variations, get the parent product for the URL slug
                $rel_post = get_post(get_the_ID());
                $rel_is_variation = ($rel_post->post_type === 'product_variation');
                $rel_parent_id = $rel_is_variation ? $rel_post->post_parent : $rel_post->ID;
                $rel_parent_post = $rel_is_variation ? get_post($rel_parent_id) : $rel_post;

                $rel_sku = $rel_product->get_sku();
                $rel_price = $rel_product->get_price();
                $rel_image_id = $rel_product->get_image_id();
                if (!$rel_image_id && $rel_is_variation) {
                    $rel_image_id = get_post_thumbnail_id($rel_parent_id);
                }
                $rel_image_url = $rel_image_id ? wp_get_attachment_image_url($rel_image_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');

                // Build vehicle-context URL
                $rel_url = home_url('/' . $vehicle['slug'] . '/product/' . $rel_parent_post->post_name . '/');
                $rel_url_params = [];
                if ($cat_id) $rel_url_params['cat_id'] = $cat_id;
                if ($rel_is_variation) $rel_url_params['variation_id'] = $rel_post->ID;
                if ($rel_url_params) $rel_url = add_query_arg($rel_url_params, $rel_url);

                $rel_title = $rel_is_variation ? get_the_title($rel_parent_id) : get_the_title();
                // Append variation attributes to title if it's a variation
                if ($rel_is_variation) {
                    $variant_attr = get_post_meta($rel_post->ID, 'variant_display_name', true);
                    if ($variant_attr) {
                        $rel_title .= ' - ' . $variant_attr;
                    }
                }
            ?>
            <div class="related-product-card">
                <a href="<?php echo esc_url($rel_url); ?>" class="related-product-link">
                    <div class="related-product-image">
                        <img src="<?php echo esc_url($rel_image_url); ?>" alt="<?php echo esc_attr($rel_title); ?>" loading="lazy">
                    </div>
                    <div class="related-product-info">
                        <h4 class="related-product-title"><?php echo esc_html($rel_title); ?></h4>
                        <?php if ($rel_sku): ?>
                            <span class="related-product-sku">SKU: <?php echo esc_html($rel_sku); ?></span>
                        <?php endif; ?>
                        <div class="related-product-price">
                            <?php if ($rel_price): ?>
                                <?php echo $rel_product->get_price_html(); ?>
                            <?php else: ?>
                                <span class="price-request">Price on request</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php if ($rel_product->is_purchasable() && $rel_product->is_in_stock()): ?>
                    <a href="<?php echo esc_url($rel_product->add_to_cart_url()); ?>" class="related-product-atc add-to-cart-btn" data-product_id="<?php echo esc_attr($rel_product->get_id()); ?>">Add to cart</a>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php
        endif;
        wp_reset_postdata();
        // Re-setup the main product post data
        $post = $product_post;
        setup_postdata($post);
    }
    ?>
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

/* Product Main Content */
.product-main-content {
    display: flex;
    gap: 40px;
    margin-bottom: 40px;
}

.product-image-section {
    flex: 0 0 55%;
    max-width: 55%;
}

.product-summary-section {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Product Image */
.product-image-wrapper {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.product-image-wrapper img {
    max-width: 100%;
    height: auto;
}

/* Diagram Image */
.maxus-product-diagram-image {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
}

.diagram-svg-full {
    overflow: auto;
    position: relative;
    padding: 10px;
    max-height: 500px;
    background: #fafafa;
    border-radius: 6px;
}

.diagram-svg-full svg {
    width: auto;
    height: auto;
}

.diagram-callout-indicator {
    margin-top: 12px;
    padding: 10px 15px;
    background: #F29F05;
    color: #fff;
    border-radius: 6px;
    font-size: 0.95em;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
}

.diagram-callout-indicator .callout-center {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.zoom-btn {
    width: 32px;
    height: 32px;
    border: 2px solid #fff;
    border-radius: 50%;
    background: transparent;
    color: #fff;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    padding: 0;
    transition: background 0.2s;
    flex-shrink: 0;
}

.zoom-btn:hover {
    background: rgba(255,255,255,0.25);
}

.highlight-callout {
    display: inline-block;
    background: #fff;
    color: #F29F05;
    padding: 2px 12px;
    border-radius: 12px;
    font-weight: bold;
    margin-left: 5px;
}

/* Product Summary */
.product-title {
    margin: 0 0 15px;
    font-size: 26px;
    color: #333;
    line-height: 1.3;
}

.product-meta-info {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

.sku-label, .callout-label {
    color: #888;
}

.sku-value, .callout-value {
    font-family: monospace;
    color: #333;
    font-weight: 600;
}

.meta-separator {
    margin: 0 12px;
    color: #ccc;
}

.product-price {
    margin-bottom: 15px;
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.price-request {
    font-size: 18px;
    color: #666;
    font-weight: normal;
}

.product-stock-status {
    margin-bottom: 20px;
}

.in-stock {
    color: #28a745;
    font-weight: 600;
}

.out-of-stock {
    color: #dc3545;
    font-weight: 600;
}

/* Add to Cart Form */
.cart {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.quantity-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-wrapper label {
    font-size: 14px;
    color: #666;
}

.qty-input {
    width: 70px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    text-align: center;
}

.add-to-cart-button {
    background: #F29F05;
    color: #fff;
    border: none;
    padding: 12px 30px;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
}

.add-to-cart-button:hover {
    background: #D98E04;
}

/* Delivery Time */
.maxus-delivery-time {
    margin-bottom: 20px;
    padding: 12px 15px;
    background: #fff8e6;
    border-radius: 6px;
    border-left: 4px solid #F29F05;
}

.delivery-label {
    font-weight: 600;
    color: #333;
    margin-right: 8px;
}

.delivery-value {
    color: #F29F05;
    font-weight: 600;
}

/* Product Description */
.maxus-product-description {
    margin-top: 30px;
    padding: 25px;
    background: #f9f9f9;
    border-radius: 8px;
}

.maxus-product-description h4 {
    margin: 0 0 10px;
    font-size: 1.2em;
    color: #333;
}

.maxus-product-description p {
    margin: 0;
    font-size: 14px;
    line-height: 1.7;
    color: #555;
}

/* Product Weight */
.maxus-product-weight {
    margin-bottom: 20px;
    font-size: 14px;
}

.weight-label {
    font-weight: 600;
    color: #333;
    margin-right: 6px;
}

.weight-value {
    color: #555;
}

/* Vehicle Compatibility */
.maxus-vehicle-compatibility {
    margin-top: auto;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 6px;
    border-left: 4px solid #F29F05;
}

.maxus-vehicle-compatibility h4 {
    margin: 0 0 10px;
    font-size: 0.95em;
    color: #333;
    font-weight: 600;
}

.maxus-vehicle-compatibility ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.maxus-vehicle-compatibility li {
    padding: 5px 0;
    border-bottom: 1px solid #e0e0e0;
    font-size: 0.9em;
}

.maxus-vehicle-compatibility li:last-child {
    border-bottom: none;
}

.maxus-vehicle-compatibility .vehicle-name {
    font-weight: 600;
    color: #333;
}

.maxus-vehicle-compatibility .vehicle-year {
    color: #666;
    margin-left: 5px;
}

.compatibility-note {
    margin: 0;
    color: #888;
    font-size: 0.9em;
}


.col-name a {
    color: #333;
    text-decoration: none;
}

.col-name a:hover {
    text-decoration: underline;
}

.current-badge {
    display: inline-block;
    background: #333;
    color: #fff;
    font-size: 0.7em;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
    vertical-align: middle;
}

.col-sku {
    font-family: monospace;
    color: #666;
}

.col-price {
    text-align: right;
    font-weight: 600;
}

.price-na {
    color: #999;
    font-weight: normal;
}

.add-to-cart-btn {
    display: inline-block;
    background: #F29F05;
    color: #fff;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: 600;
    text-decoration: none;
}

.add-to-cart-btn:hover {
    background: #D98E04;
    color: #fff;
}

/* Related Products */
.maxus-related-products {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #e0e0e0;
}

.maxus-related-products h3 {
    margin: 0 0 20px;
    font-size: 1.4em;
    color: #333;
}

.related-products-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
}

.related-product-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.2s ease;
}

.related-product-card:hover {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.related-product-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.related-product-image {
    background: #fafafa;
    padding: 10px;
    text-align: center;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.related-product-image img {
    max-width: 100%;
    max-height: 100%;
    height: auto;
    object-fit: contain;
}

.related-product-info {
    padding: 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.related-product-title {
    margin: 0 0 6px;
    font-size: 0.85em;
    color: #333;
    line-height: 1.3;
    font-weight: 600;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.related-product-sku {
    font-size: 0.75em;
    color: #888;
    font-family: monospace;
    margin-bottom: 8px;
}

.related-product-price {
    margin-top: auto;
    font-size: 0.95em;
    font-weight: 700;
    color: #333;
}

.related-product-price .price-request {
    font-size: 0.85em;
    color: #666;
    font-weight: normal;
}

.related-product-atc {
    display: block;
    text-align: center;
    margin: 0 12px 12px;
    padding: 8px 12px;
    font-size: 0.8em;
}

/* Responsive */
@media (max-width: 900px) {
    .product-main-content {
        flex-direction: column;
    }

    .product-image-section {
        flex: 1;
        max-width: 100%;
    }

    .product-summary-section {
        order: 2;
    }

    .related-products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 767px) {
    .cart {
        flex-direction: column;
        align-items: stretch;
    }

    .quantity-wrapper {
        margin-bottom: 10px;
    }

    .product-title {
        font-size: 22px;
    }

    .product-price {
        font-size: 24px;
    }


    .col-sku {
        display: none;
    }

    .related-products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
}
</style>

<style>
.callout-ring {
    animation: pulse-ring 1.5s ease-out infinite;
}
@keyframes pulse-ring {
    0% { opacity: 1; transform-origin: center; }
    100% { opacity: 0; r: 25; }
}
.svg-highlight-line, .svg-highlight-line-hover {
    stroke-width: 4px !important;
}
.svg-highlight-line { stroke: #F29F05 !important; }
.svg-highlight-line-hover { stroke: #ff0000 !important; }
.svg-highlight-part, .svg-highlight-part-hover {
    stroke-width: 3px !important;
}
.svg-highlight-part { stroke: #F29F05 !important; }
.svg-highlight-part-hover { stroke: #ff0000 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var currentCallout = '<?php echo esc_js($callout); ?>';
    var container = document.getElementById('diagram-svg-container');
    if (!currentCallout || !container) return;

    var svg = container.querySelector('svg');
    if (!svg) return;

    // Fix viewBox to show all content (some SVGs have transforms that push content outside the declared viewBox)
    try {
        var bbox = svg.getBBox();
        if (bbox.width > 0 && bbox.height > 0) {
            var pad = 10;
            svg.setAttribute('viewBox', (bbox.x - pad) + ' ' + (bbox.y - pad) + ' ' + (bbox.width + pad * 2) + ' ' + (bbox.height + pad * 2));
        }
    } catch(e) {}

    var textElements = svg.querySelectorAll('text');
    var allPaths = svg.querySelectorAll('path, line, polyline');

    var highlightedLines = [];
    var highlightedParts = [];
    var highlightedTexts = [];

    // Store original viewBox for reset
    var origViewBox = svg.getAttribute('viewBox');
    var origVBParts = origViewBox ? origViewBox.split(/[\s,]+/).map(Number) : null;

    // Zoom in/out buttons
    var zoomInBtn = document.getElementById('zoom-in-btn');
    var zoomOutBtn = document.getElementById('zoom-out-btn');

    function getCurrentViewBox() {
        var vb = svg.getAttribute('viewBox');
        if (!vb) return null;
        var parts = vb.split(/[\s,]+/).map(Number);
        return { x: parts[0], y: parts[1], w: parts[2], h: parts[3] };
    }

    function applyZoom(factor) {
        var cur = getCurrentViewBox();
        if (!cur || !origVBParts) return;

        var centerX = cur.x + cur.w / 2;
        var centerY = cur.y + cur.h / 2;

        var newW = cur.w * factor;
        var newH = cur.h * factor;

        // Don't zoom out beyond original
        newW = Math.min(newW, origVBParts[2]);
        newH = Math.min(newH, origVBParts[3]);

        // Don't zoom in beyond 15% of original
        newW = Math.max(newW, origVBParts[2] * 0.15);
        newH = Math.max(newH, origVBParts[3] * 0.15);

        var newX = centerX - newW / 2;
        var newY = centerY - newH / 2;

        // Clamp to SVG bounds
        newX = Math.max(origVBParts[0], Math.min(newX, origVBParts[0] + origVBParts[2] - newW));
        newY = Math.max(origVBParts[1], Math.min(newY, origVBParts[1] + origVBParts[3] - newH));

        svg.setAttribute('viewBox', newX + ' ' + newY + ' ' + newW + ' ' + newH);
    }

    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function() { applyZoom(0.7); });
    }
    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function() { applyZoom(1.4); });
    }

    // Zoom the SVG viewBox to fit the callout number + leader line with padding
    function zoomToCallout(minX, minY, maxX, maxY) {
        var vb = svg.viewBox.baseVal;
        if (!vb || !vb.width) return;

        // Calculate region needed to show number + line
        var spanW = maxX - minX;
        var spanH = maxY - minY;

        // Add generous padding around the content (75% of span, minimum 20% of full diagram)
        var padW = Math.max(spanW * 0.75, vb.width * 0.2);
        var padH = Math.max(spanH * 0.75, vb.height * 0.2);

        var zoomWidth = spanW + padW * 2;
        var zoomHeight = spanH + padH * 2;

        // Ensure minimum zoom (don't zoom in more than ~55% of diagram)
        zoomWidth = Math.max(zoomWidth, vb.width * 0.55);
        zoomHeight = Math.max(zoomHeight, vb.height * 0.55);

        // Don't zoom out beyond full diagram
        zoomWidth = Math.min(zoomWidth, vb.width);
        zoomHeight = Math.min(zoomHeight, vb.height);

        // Center on the content
        var centerX = (minX + maxX) / 2;
        var centerY = (minY + maxY) / 2;
        var newX = centerX - zoomWidth / 2;
        var newY = centerY - zoomHeight / 2;

        // Clamp to SVG bounds
        newX = Math.max(vb.x, Math.min(newX, vb.x + vb.width - zoomWidth));
        newY = Math.max(vb.y, Math.min(newY, vb.y + vb.height - zoomHeight));

        svg.setAttribute('viewBox', newX + ' ' + newY + ' ' + zoomWidth + ' ' + zoomHeight);
    }

    function getDistance(x1, y1, x2, y2) {
        return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
    }

    function getTextPosition(textEl) {
        var bbox = textEl.getBBox();
        var transform = textEl.getAttribute('transform');
        var x = bbox.x, y = bbox.y, width = bbox.width, height = bbox.height;

        if (transform) {
            var matrixMatch = transform.match(/matrix\s*\(\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*\)/);
            if (matrixMatch) {
                x = parseFloat(matrixMatch[5]) + bbox.x;
                y = parseFloat(matrixMatch[6]) + bbox.y;
            }
            var translateMatch = transform.match(/translate\s*\(\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*\)/);
            if (translateMatch) {
                x = parseFloat(translateMatch[1]) + bbox.x;
                y = parseFloat(translateMatch[2]) + bbox.y;
            }
        }
        return { x: x, y: y, width: width, height: height };
    }

    function getPathEndpoints(pathEl) {
        var d = pathEl.getAttribute('d');
        if (!d) return null;
        var moveMatch = d.match(/M\s*([\d.-]+)[,\s]*([\d.-]+)/i);
        var startX = moveMatch ? parseFloat(moveMatch[1]) : null;
        var startY = moveMatch ? parseFloat(moveMatch[2]) : null;
        var endX = startX, endY = startY;

        var commands = d.match(/[LlHhVvCcSsQqTtAa][^LlHhVvCcSsQqTtAaMmZz]*/g) || [];
        commands.forEach(function(cmd) {
            var type = cmd[0];
            var nums = cmd.slice(1).match(/-?[\d.]+/g);
            if (!nums) return;
            if (type === 'L') {
                endX = parseFloat(nums[nums.length - 2] || nums[0]);
                endY = parseFloat(nums[nums.length - 1] || nums[1]);
            } else if (type === 'l') {
                endX += parseFloat(nums[nums.length - 2] || nums[0]);
                endY += parseFloat(nums[nums.length - 1] || nums[1]);
            } else if (type === 'C' || type === 'c') {
                if (nums.length >= 6) {
                    if (type === 'C') {
                        endX = parseFloat(nums[nums.length - 2]);
                        endY = parseFloat(nums[nums.length - 1]);
                    } else {
                        endX += parseFloat(nums[nums.length - 2]);
                        endY += parseFloat(nums[nums.length - 1]);
                    }
                }
            }
        });
        return { startX: startX, startY: startY, endX: endX, endY: endY };
    }

    function getLineEndpoints(lineEl) {
        return {
            startX: parseFloat(lineEl.getAttribute('x1')),
            startY: parseFloat(lineEl.getAttribute('y1')),
            endX: parseFloat(lineEl.getAttribute('x2')),
            endY: parseFloat(lineEl.getAttribute('y2'))
        };
    }

    function findConnectedLines(textBbox, searchRadius, textEl) {
        var connected = [];
        var textCx = textBbox.x + textBbox.width / 2;
        var textCy = textBbox.y + textBbox.height / 2;
        var textLeft = textBbox.x;

        // Structural approach - sibling <g> element
        if (textEl) {
            var parentG = textEl.parentElement;
            if (parentG && parentG.tagName.toLowerCase() === 'g') {
                var nextG = parentG.nextElementSibling;
                if (nextG && nextG.tagName.toLowerCase() === 'g') {
                    var siblingLines = nextG.querySelectorAll('line, path, polyline');
                    siblingLines.forEach(function(el) {
                        var stroke = el.getAttribute('stroke');
                        if (stroke) {
                            var s = stroke.toUpperCase();
                            if (s === '#FFFFFF' || s === 'WHITE' || s === '#FFF') return;
                        }
                        var endpoints;
                        if (el.tagName.toLowerCase() === 'line') endpoints = getLineEndpoints(el);
                        else endpoints = getPathEndpoints(el);
                        if (endpoints && endpoints.startX !== null) {
                            // Validate the line is actually near this text (not a different callout's line)
                            var dStart = getDistance(textCx, textCy, endpoints.startX, endpoints.startY);
                            var dEnd = getDistance(textCx, textCy, endpoints.endX, endpoints.endY);
                            if (dStart < 30 || dEnd < 30) {
                                connected.push({ element: el, endpoints: endpoints, connectedAtStart: dStart < dEnd, distance: Math.min(dStart, dEnd) });
                            }
                        }
                    });
                }
            }
        }
        if (connected.length > 0) return connected;

        // Proximity-based fallback
        allPaths.forEach(function(el) {
            var endpoints;
            if (el.tagName === 'line') endpoints = getLineEndpoints(el);
            else if (el.tagName === 'path' || el.tagName === 'polyline') endpoints = getPathEndpoints(el);
            if (!endpoints || endpoints.startX === null) return;

            var lineLength = getDistance(endpoints.startX, endpoints.startY, endpoints.endX, endpoints.endY);
            if (lineLength < 3) return;

            var distToStart = getDistance(textCx, textCy, endpoints.startX, endpoints.startY);
            var distToEnd = getDistance(textCx, textCy, endpoints.endX, endpoints.endY);

            if (distToStart < 15 || distToEnd < 15) {
                connected.push({ element: el, endpoints: endpoints, connectedAtStart: distToStart < distToEnd, distance: Math.min(distToStart, distToEnd) });
                return;
            }

            var yTolerance = 8;
            var startNearTextY = Math.abs(endpoints.startY - textCy) < yTolerance;
            var endNearTextY = Math.abs(endpoints.endY - textCy) < yTolerance;
            var startNearTextArea = Math.abs(endpoints.startX - textLeft) < 20;
            var endNearTextArea = Math.abs(endpoints.endX - textLeft) < 20;

            if (startNearTextY && startNearTextArea) {
                connected.push({ element: el, endpoints: endpoints, connectedAtStart: true, distance: Math.abs(endpoints.startY - textCy) });
            } else if (endNearTextY && endNearTextArea) {
                connected.push({ element: el, endpoints: endpoints, connectedAtStart: false, distance: Math.abs(endpoints.endY - textCy) });
            }
        });

        if (connected.length > 1) {
            connected.sort(function(a, b) { return a.distance - b.distance; });
            connected = connected.slice(0, 1);
        }
        return connected;
    }

    function findPartGeometry(lines, searchRadius) {
        var parts = [];
        var partEndpoints = [];

        lines.forEach(function(lineInfo) {
            var ep = lineInfo.endpoints;
            if (lineInfo.connectedAtStart) {
                partEndpoints.push({ x: ep.endX, y: ep.endY });
            } else {
                partEndpoints.push({ x: ep.startX, y: ep.startY });
            }
        });

        partEndpoints.forEach(function(point) {
            allPaths.forEach(function(el) {
                if (el.tagName.toLowerCase() === 'line') return;
                var isLeaderLine = lines.some(function(l) { return l.element === el; });
                if (isLeaderLine) return;
                try {
                    var bbox = el.getBBox();
                    if (bbox.width < 3 || bbox.height < 3) return;
                    var r = 15;
                    if (point.x >= bbox.x - r && point.x <= bbox.x + bbox.width + r &&
                        point.y >= bbox.y - r && point.y <= bbox.y + bbox.height + r) {
                        if (parts.indexOf(el) === -1) parts.push(el);
                    }
                } catch(e) {}
            });
        });

        if (parts.length > 20) parts = parts.slice(0, 20);
        return parts;
    }

    function highlightCallout(calloutNum) {
        var color = '#F29F05';
        var processedGroups = [];

        textElements.forEach(function(textEl) {
            if (textEl.textContent.trim() !== calloutNum) return;

            var parentG = textEl.parentElement;
            if (parentG && processedGroups.indexOf(parentG) !== -1) return;
            if (parentG) processedGroups.push(parentG);

            var bbox;
            try { bbox = getTextPosition(textEl); } catch(e) { return; }

            var cx = bbox.x + bbox.width / 2;
            var cy = bbox.y + bbox.height / 2;

            // Highlight the callout number text in orange
            textEl.style.fill = color;
            textEl.style.fontWeight = 'bold';
            highlightedTexts.push(textEl);

            // Find and highlight leader lines
            var connectedLines = findConnectedLines(bbox, 10, textEl);
            // Track bounding area of callout number + leader lines
            var minX = cx, maxX = cx, minY = cy, maxY = cy;
            connectedLines.forEach(function(lineInfo) {
                var el = lineInfo.element;
                el.classList.add('svg-highlight-line');
                el.style.stroke = color;
                el.style.strokeWidth = '4px';
                el.setAttribute('stroke', color);
                el.setAttribute('stroke-width', '4');
                highlightedLines.push(el);
                // Expand bounds to include line endpoints
                var ep = lineInfo.endpoints;
                if (ep.startX !== null) {
                    minX = Math.min(minX, ep.startX, ep.endX);
                    maxX = Math.max(maxX, ep.startX, ep.endX);
                    minY = Math.min(minY, ep.startY, ep.endY);
                    maxY = Math.max(maxY, ep.startY, ep.endY);
                }
            });

            // Zoom to fit callout number + leader line
            zoomToCallout(minX, minY, maxX, maxY);
        });
    }

    // Click-and-drag panning on the SVG
    var isPanning = false;
    var panStartX, panStartY;
    var panViewBox = { x: 0, y: 0, w: 0, h: 0 };

    container.style.cursor = 'grab';

    container.addEventListener('mousedown', function(e) {
        if (e.button !== 0) return; // left click only
        isPanning = true;
        container.style.cursor = 'grabbing';
        panStartX = e.clientX;
        panStartY = e.clientY;
        var vb = svg.viewBox.baseVal;
        panViewBox = { x: vb.x, y: vb.y, w: vb.width, h: vb.height };
        e.preventDefault();
    });

    window.addEventListener('mousemove', function(e) {
        if (!isPanning) return;
        var svgRect = svg.getBoundingClientRect();
        var scaleX = panViewBox.w / svgRect.width;
        var scaleY = panViewBox.h / svgRect.height;
        var dx = (e.clientX - panStartX) * scaleX;
        var dy = (e.clientY - panStartY) * scaleY;
        svg.setAttribute('viewBox',
            (panViewBox.x - dx) + ' ' + (panViewBox.y - dy) + ' ' + panViewBox.w + ' ' + panViewBox.h
        );
    });

    window.addEventListener('mouseup', function() {
        if (isPanning) {
            isPanning = false;
            container.style.cursor = 'grab';
        }
    });

    // Run highlighting
    highlightCallout(currentCallout);

});
</script>

<?php
wp_reset_postdata();
get_footer();
?>
