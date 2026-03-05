<?php
/**
 * Template: Vehicle Subcategory Page (Diagram Page)
 * URL: /e-deliver-9/brakes/rear-suspension/
 * Shows parts list with SVG diagram for this vehicle
 */

get_header();

global $maxus_current_vehicle, $wpdb;

$vehicle = $maxus_current_vehicle;
$vin = $vehicle['vin'];
$category_slug = get_query_var('maxus_category');
$subcategory_slug = get_query_var('maxus_subcategory');

// Get the parent category
$parent_category = get_term_by('slug', $category_slug, 'product_cat');

// Get the subcategory (diagram category)
$subcategory = get_term_by('slug', $subcategory_slug, 'product_cat');

if (!$parent_category || !$subcategory) {
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
            <p>The requested category was not found.</p>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>" class="back-link">&larr; Back to <?php echo esc_html($vehicle['name']); ?></a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Get SVG diagram path
$svg_path = get_term_meta($subcategory->term_id, 'svg_diagram', true);
$svg_full_path = $svg_path ? WP_CONTENT_DIR . '/uploads/' . $svg_path : null;
$has_svg = $svg_full_path && file_exists($svg_full_path);

// Get the term_taxonomy_id for callout lookup
$tt_id = $wpdb->get_var($wpdb->prepare(
    "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
    $subcategory->term_id
));

// Get products in this subcategory that match vehicle SKUs
// Supports both simple products and product variations
// Variations keep their original category relationships
$product_ids = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT p.ID
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
    INNER JOIN {$wpdb->prefix}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku
    INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    WHERE tt.term_id = %d
    AND tt.taxonomy = 'product_cat'
    AND p.post_type IN ('product', 'product_variation')
    AND svm.vin = %s
", $subcategory->term_id, $vin));

$products = [];
if (!empty($product_ids)) {
    $ids_placeholder = implode(',', array_map('intval', $product_ids));
    $products = $wpdb->get_results($wpdb->prepare("
        SELECT
            p.ID,
            p.post_title,
            p.post_name,
            p.post_type,
            p.post_parent,
            pm_sku.meta_value as sku,
            pm_price.meta_value as price,
            COALESCE(pm_callout.meta_value, pm_callout_generic.meta_value) as callout,
            svm.variant_attribute
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN {$wpdb->prefix}sku_vin_mapping svm ON pm_sku.meta_value = svm.sku AND svm.vin = %s
        LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
        LEFT JOIN {$wpdb->postmeta} pm_callout ON p.ID = pm_callout.post_id AND pm_callout.meta_key = %s
        LEFT JOIN {$wpdb->postmeta} pm_callout_generic ON p.ID = pm_callout_generic.post_id AND pm_callout_generic.meta_key = 'callout_number'
        WHERE p.ID IN ({$ids_placeholder})
        ORDER BY CAST(COALESCE(pm_callout.meta_value, pm_callout_generic.meta_value, '999') AS UNSIGNED), p.post_title
    ", $vin, 'callout_cat_' . $tt_id));
}

$total_parts = count($products);

// Count unique callouts for display
$unique_callouts = count(array_unique(array_column($products, 'callout')));
?>

<div class="maxus-vehicle-page maxus-diagram-page">
    <!-- Breadcrumbs -->
    <nav class="maxus-breadcrumbs">
        <a href="<?php echo home_url(); ?>">Home</a>
        <span class="separator">&rsaquo;</span>
        <a href="<?php echo home_url('/' . $vehicle['slug'] . '/'); ?>"><?php echo esc_html($vehicle['name']); ?></a>
        <span class="separator">&rsaquo;</span>
        <a href="<?php echo home_url('/' . $vehicle['slug'] . '/' . $parent_category->slug . '/'); ?>"><?php echo esc_html($parent_category->name); ?></a>
        <span class="separator">&rsaquo;</span>
        <span class="current"><?php echo esc_html($subcategory->name); ?></span>
    </nav>

    <!-- Header -->
    <div class="vehicle-page-header">
        <h1><?php echo esc_html($subcategory->name); ?></h1>
        <p class="vehicle-context">
            <span class="vehicle-name"><?php echo esc_html($vehicle['name']); ?></span>
            <span class="year">(<?php echo esc_html($vehicle['year']); ?>)</span>
            <span class="separator">|</span>
            <span class="parent-cat"><?php echo esc_html($parent_category->name); ?></span>
        </p>
        <p class="part-count"><?php echo number_format($total_parts); ?> parts</p>
    </div>

    <?php if (empty($products)): ?>
        <div class="no-parts-message">
            <p>No parts found in this diagram for <?php echo esc_html($vehicle['name']); ?>.</p>
            <a href="<?php echo home_url('/' . $vehicle['slug'] . '/' . $parent_category->slug . '/'); ?>" class="back-link">&larr; Back to <?php echo esc_html($parent_category->name); ?></a>
        </div>
    <?php else: ?>
        <div class="diagram-layout">
            <!-- Parts Table -->
            <div class="diagram-table-column">
                <div class="related-parts-table-wrapper">
                    <table class="related-parts-table">
                        <thead>
                            <tr>
                                <th class="col-callout">#</th>
                                <th class="col-name">Part Name</th>
                                <th class="col-sku">SKU</th>
                                <th class="col-price">Price</th>
                                <th class="col-cart"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product):
                                // For variations, link to parent product with variation parameter
                                if ($product->post_type === 'product_variation' && $product->post_parent) {
                                    $parent = get_post($product->post_parent);
                                    $product_url = home_url('/' . $vehicle['slug'] . '/product/' . $parent->post_name . '/?variation_id=' . $product->ID . '&cat_id=' . $subcategory->term_id);
                                } else {
                                    $product_url = home_url('/' . $vehicle['slug'] . '/product/' . $product->post_name . '/?cat_id=' . $subcategory->term_id);
                                }
                                $price = $product->price;
                                $callout = $product->callout;
                                $variant_attr = isset($product->variant_attribute) ? $product->variant_attribute : '';
                            ?>
                                <tr data-callout="<?php echo esc_attr($callout); ?>">
                                    <td class="col-callout">
                                        <?php if ($callout): ?>
                                            <span class="callout-number"><?php echo esc_html($callout); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-name">
                                        <a href="<?php echo esc_url($product_url); ?>"><?php echo esc_html($product->post_title); ?></a>
                                        <?php if ($variant_attr): ?>
                                            <span class="variant-attribute"><?php echo esc_html($variant_attr); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-sku"><?php echo esc_html($product->sku); ?></td>
                                    <td class="col-price">
                                        <?php if ($price && $price > 0): ?>
                                            <?php echo wc_price($price); ?>
                                        <?php else: ?>
                                            <span class="price-na">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-cart">
                                        <?php if ($price && $price > 0): ?>
                                            <?php
                                            // For variations, add to cart with variation_id parameter
                                            if ($product->post_type === 'product_variation' && $product->post_parent) {
                                                $add_url = wc_get_cart_url() . '?add-to-cart=' . $product->post_parent . '&variation_id=' . $product->ID;
                                            } else {
                                                $add_url = wc_get_cart_url() . '?add-to-cart=' . $product->ID;
                                            }
                                            ?>
                                            <a href="<?php echo esc_url($add_url); ?>" class="add-to-cart-btn">Add</a>
                                        <?php else: ?>
                                            <?php echo maxus_price_enquiry_button($product->sku, $product->post_title); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SVG Diagram -->
            <?php if ($has_svg): ?>
                <div class="diagram-svg-column">
                    <div class="diagram-svg-wrapper">
                        <div class="diagram-svg-full" id="diagram-svg-container">
                            <?php echo file_get_contents($svg_full_path); ?>
                        </div>
                        <div class="diagram-controls">
                            <button type="button" class="zoom-btn" id="zoom-toggle">Toggle Zoom</button>
                            <span class="zoom-hint">Click diagram to zoom, hover table rows to highlight</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.maxus-vehicle-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.maxus-diagram-page {
    max-width: 1400px;
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
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #F29F05;
}

.vehicle-page-header h1 {
    margin: 0 0 8px;
    font-size: 28px;
    color: #333;
}

.vehicle-page-header .vehicle-context {
    margin: 0 0 8px;
    font-size: 15px;
    color: #666;
}

.vehicle-page-header .vehicle-context .vehicle-name {
    color: #F29F05;
    font-weight: 600;
}

.vehicle-page-header .vehicle-context .year {
    color: #888;
}

.vehicle-page-header .vehicle-context .separator {
    margin: 0 10px;
    color: #ccc;
}

.vehicle-page-header .vehicle-context .parent-cat {
    color: #666;
}

.vehicle-page-header .part-count {
    margin: 0;
    font-size: 16px;
    color: #888;
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

/* Diagram Layout */
.diagram-layout {
    display: flex;
    gap: 25px;
    align-items: flex-start;
}

.diagram-table-column {
    flex: 0 0 550px;
    max-width: 550px;
}

.diagram-svg-column {
    flex: 1;
    min-width: 0;
}

/* Parts Table */
.related-parts-table-wrapper {
    max-height: 650px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fff;
}

.related-parts-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9em;
}

.related-parts-table thead {
    position: sticky;
    top: 0;
    background: #F29F05;
    color: #fff;
    z-index: 10;
}

.related-parts-table th {
    padding: 12px 10px;
    text-align: left;
    font-weight: 600;
}

.related-parts-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.related-parts-table tbody tr:hover {
    background: #fff8e6;
}

.related-parts-table tbody tr.highlight-row {
    background: #ffe0cc !important;
}

.col-callout {
    width: 50px;
    text-align: center;
}

.callout-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #F29F05;
    color: #fff;
    border-radius: 50%;
    font-weight: bold;
    font-size: 0.85em;
}

.col-name {
    min-width: 180px;
}

.col-name a {
    color: #333;
    text-decoration: none;
}

.col-name a:hover {
    text-decoration: underline;
}

.variant-attribute {
    display: inline-block;
    background: #e8f4f8;
    color: #0077aa;
    font-size: 0.75em;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
    vertical-align: middle;
    font-weight: 500;
}

.col-sku {
    width: 130px;
    font-family: monospace;
    font-size: 0.95em;
    color: #666;
}

.col-price {
    width: 90px;
    text-align: right;
    font-weight: 600;
}

.price-na {
    color: #999;
    font-weight: normal;
}

.col-cart {
    width: 60px;
    text-align: center;
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
    transition: background 0.2s ease;
}

.add-to-cart-btn:hover {
    background: #D98E04;
    color: #fff;
}

/* SVG Diagram */
.diagram-svg-wrapper {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
}

.diagram-svg-full {
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: crosshair;
    padding: 10px;
    max-height: 600px;
    background: #fafafa;
    border-radius: 6px;
}

.diagram-svg-full svg {
    max-width: 100%;
    max-height: 580px;
    width: auto;
    height: auto;
    display: block;
    transition: transform 0.1s ease-out;
    transform-origin: center center;
}

.diagram-svg-full.zoomed {
    cursor: zoom-out;
}

.diagram-svg-full.zoomed svg {
    transform: scale(2.5);
}

.diagram-controls {
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.zoom-btn {
    background: #F29F05;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
}

.zoom-btn:hover {
    background: #D98E04;
}

.zoom-hint {
    font-size: 12px;
    color: #888;
}

/* SVG Highlighting */
.svg-highlight-line {
    stroke: #F29F05 !important;
    stroke-width: 3px !important;
    stroke-opacity: 1 !important;
}

.svg-highlight-part {
    stroke: #F29F05 !important;
    stroke-width: 2px !important;
    fill-opacity: 0.3 !important;
}

.svg-highlight-text {
    fill: #F29F05 !important;
    font-weight: bold !important;
}

/* Responsive */
@media (max-width: 1100px) {
    .diagram-layout {
        flex-direction: column;
    }

    .diagram-table-column {
        flex: 1;
        max-width: 100%;
        order: 2;
    }

    .diagram-svg-column {
        order: 1;
        margin-bottom: 20px;
        width: 100%;
    }
}

@media (max-width: 767px) {
    .related-parts-table-wrapper {
        max-height: 400px;
    }

    .col-sku {
        display: none;
    }

    .related-parts-table th,
    .related-parts-table td {
        padding: 8px 6px;
    }

    .add-to-cart-btn {
        padding: 4px 8px;
        font-size: 0.75em;
    }

    .vehicle-page-header h1 {
        font-size: 22px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var svgContainer = document.getElementById('diagram-svg-container');
    var zoomBtn = document.getElementById('zoom-toggle');

    if (svgContainer && zoomBtn) {
        // Toggle zoom on button click
        zoomBtn.addEventListener('click', function() {
            svgContainer.classList.toggle('zoomed');
        });

        // Toggle zoom on diagram click
        svgContainer.addEventListener('click', function() {
            svgContainer.classList.toggle('zoomed');
        });
    }

    // SVG highlighting setup
    var svg = svgContainer ? svgContainer.querySelector('svg') : null;
    if (!svg) return;

    var textElements = svg.querySelectorAll('text');
    var allPaths = svg.querySelectorAll('path, line, polyline');
    var highlightedLines = [];
    var highlightedTexts = [];

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
            if (type === 'L') { endX = parseFloat(nums[nums.length - 2] || nums[0]); endY = parseFloat(nums[nums.length - 1] || nums[1]); }
            else if (type === 'l') { endX += parseFloat(nums[nums.length - 2] || nums[0]); endY += parseFloat(nums[nums.length - 1] || nums[1]); }
            else if (type === 'C' && nums.length >= 6) { endX = parseFloat(nums[nums.length - 2]); endY = parseFloat(nums[nums.length - 1]); }
            else if (type === 'c' && nums.length >= 6) { endX += parseFloat(nums[nums.length - 2]); endY += parseFloat(nums[nums.length - 1]); }
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
                        if (stroke) { var s = stroke.toUpperCase(); if (s === '#FFFFFF' || s === 'WHITE' || s === '#FFF') return; }
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

            // Highlight text
            textEl.style.fill = color;
            textEl.style.fontWeight = 'bold';
            highlightedTexts.push(textEl);

            // Find and highlight leader lines
            var connectedLines = findConnectedLines(bbox, 10, textEl);
            connectedLines.forEach(function(lineInfo) {
                var el = lineInfo.element;
                if (el._originalStroke === undefined) {
                    el._originalStroke = el.getAttribute('stroke') || '';
                    el._originalStrokeWidth = el.getAttribute('stroke-width') || '';
                }
                el.style.stroke = color;
                el.style.strokeWidth = '4px';
                el.setAttribute('stroke', color);
                el.setAttribute('stroke-width', '4');
                highlightedLines.push(el);
            });
        });
    }

    function clearHighlights() {
        highlightedTexts.forEach(function(t) { t.style.fill = ''; t.style.fontWeight = ''; });
        highlightedTexts = [];
        highlightedLines.forEach(function(el) {
            el.style.stroke = '';
            el.style.strokeWidth = '';
            if (el._originalStroke !== undefined) {
                el.setAttribute('stroke', el._originalStroke);
                el.setAttribute('stroke-width', el._originalStrokeWidth || '');
            }
        });
        highlightedLines = [];
    }

    // Highlight callouts on table row hover
    var tableRows = document.querySelectorAll('.related-parts-table tbody tr');
    tableRows.forEach(function(row) {
        row.addEventListener('mouseenter', function() {
            var callout = this.getAttribute('data-callout');
            if (callout) {
                clearHighlights();
                highlightCallout(callout);
                this.classList.add('highlight-row');
            }
        });
        row.addEventListener('mouseleave', function() {
            clearHighlights();
            this.classList.remove('highlight-row');
        });
    });
});
</script>

<?php get_footer(); ?>
