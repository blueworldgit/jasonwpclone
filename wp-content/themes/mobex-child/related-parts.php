<?php
/**
 * Related Parts from Same Diagram
 * Shows all parts that share the same diagram with callout number, name, price, and link
 */

if (!defined('ABSPATH')) exit;

/**
 * Get the diagram category (deepest category) for a product
 * Prefers categories that have both an SVG diagram AND a matching callout for this product
 */
function maxus_get_product_diagram_category($product_id) {
    global $wpdb;

    $terms = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'all'));

    if (empty($terms) || is_wp_error($terms)) {
        return null;
    }

    $candidates = array();

    foreach ($terms as $term) {
        // Skip utility categories
        if (in_array($term->slug, array('priceupdated', 'imageupdated', 'uncategorized'))) {
            continue;
        }

        $ancestors = get_ancestors($term->term_id, 'product_cat', 'taxonomy');
        $depth = count($ancestors);
        $has_svg = (bool) get_term_meta($term->term_id, 'svg_diagram', true);

        // Check if product has a callout for this category
        $has_callout = false;
        if ($has_svg) {
            $tt_id = $wpdb->get_var($wpdb->prepare(
                "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
                $term->term_id
            ));
            $has_callout = (bool) get_post_meta($product_id, 'callout_cat_' . $tt_id, true);
        }

        $candidates[] = array(
            'term'        => $term,
            'depth'       => $depth,
            'has_svg'     => $has_svg,
            'has_callout' => $has_callout,
        );
    }

    if (empty($candidates)) return null;

    // Sort: prefer has_svg+has_callout first, then has_svg, then deepest
    usort($candidates, function($a, $b) {
        $score_a = ($a['has_svg'] && $a['has_callout'] ? 2 : ($a['has_svg'] ? 1 : 0));
        $score_b = ($b['has_svg'] && $b['has_callout'] ? 2 : ($b['has_svg'] ? 1 : 0));
        if ($score_a !== $score_b) return $score_b - $score_a;
        return $b['depth'] - $a['depth'];
    });

    return $candidates[0]['term'];
}

/**
 * Get all related parts from the same diagram category
 * Shows one part per callout number (grouped by callout)
 * Always ensures the current product is shown for its callout
 */
function maxus_get_related_diagram_parts($product_id, $diagram_term_id, $limit = 500) {
    global $wpdb;

    // Get the term_taxonomy_id for this term
    $tt_id = $wpdb->get_var($wpdb->prepare(
        "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
        $diagram_term_id
    ));

    // Build the category-specific callout meta key
    $callout_meta_key = 'callout_cat_' . $tt_id;

    // Get the current product's callout so we can prioritize it
    $current_callout = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(pmc.meta_value, pmg.meta_value)
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pmc ON p.ID = pmc.post_id AND pmc.meta_key = %s
         LEFT JOIN {$wpdb->postmeta} pmg ON p.ID = pmg.post_id AND pmg.meta_key = 'callout_number'
         WHERE p.ID = %d",
        $callout_meta_key, $product_id
    ));

    // Get one product per callout number (to avoid showing all variants)
    // For the current product's callout, always show the current product
    // For other callouts, show MIN(ID)
    $query = $wpdb->prepare("
        SELECT p.ID, p.post_title, pm_sku.meta_value as sku,
               COALESCE(pm_callout_cat.meta_value, pm_callout.meta_value) as callout,
               pm_price.meta_value as price,
               variants.variant_count
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN {$wpdb->postmeta} pm_callout_cat ON p.ID = pm_callout_cat.post_id AND pm_callout_cat.meta_key = %s
        LEFT JOIN {$wpdb->postmeta} pm_callout ON p.ID = pm_callout.post_id AND pm_callout.meta_key = 'callout_number'
        LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
        INNER JOIN (
            SELECT COALESCE(pmc.meta_value, pmg.meta_value) as callout_num,
                   CASE
                       WHEN COALESCE(pmc.meta_value, pmg.meta_value) = %s THEN %d
                       ELSE MIN(p2.ID)
                   END as first_id,
                   COUNT(*) as variant_count
            FROM {$wpdb->posts} p2
            INNER JOIN {$wpdb->term_relationships} tr2 ON p2.ID = tr2.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
            LEFT JOIN {$wpdb->postmeta} pmc ON p2.ID = pmc.post_id AND pmc.meta_key = %s
            LEFT JOIN {$wpdb->postmeta} pmg ON p2.ID = pmg.post_id AND pmg.meta_key = 'callout_number'
            WHERE tt2.term_id = %d
            AND tt2.taxonomy = 'product_cat'
            AND p2.post_type = 'product'
            AND p2.post_status = 'publish'
            GROUP BY callout_num
        ) variants ON p.ID = variants.first_id
        WHERE tt.term_id = %d
        AND tt.taxonomy = 'product_cat'
        AND p.post_type = 'product'
        AND p.post_status = 'publish'
        ORDER BY CAST(COALESCE(pm_callout_cat.meta_value, pm_callout.meta_value, '999') AS UNSIGNED), p.post_title
        LIMIT %d
    ", $callout_meta_key, $current_callout, $product_id, $callout_meta_key, $diagram_term_id, $diagram_term_id, $limit);

    return $wpdb->get_results($query);
}

/**
 * Get vehicle compatibility for a SKU from the mapping table
 */
function maxus_get_sku_vehicles($sku) {
    global $wpdb;

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT vehicle_year, vehicle_name
        FROM {$wpdb->prefix}sku_vin_mapping
        WHERE sku = %s
        ORDER BY vehicle_name, vehicle_year
    ", $sku));

    if (empty($results)) {
        return '';
    }

    // Group by vehicle name
    $vehicles = [];
    foreach ($results as $row) {
        $name = $row->vehicle_name;
        if (!isset($vehicles[$name])) {
            $vehicles[$name] = [];
        }
        $vehicles[$name][] = $row->vehicle_year;
    }

    // Format output
    $parts = [];
    foreach ($vehicles as $name => $years) {
        sort($years);
        if (count($years) > 2) {
            // Show range if consecutive
            $parts[] = $years[0] . '-' . end($years) . ' ' . $name;
        } else {
            $parts[] = implode('/', $years) . ' ' . $name;
        }
    }

    return implode(', ', $parts);
}

/**
 * Get the SVG diagram for a category
 */
function maxus_get_diagram_svg($term_id) {
    $svg_path = get_term_meta($term_id, 'svg_diagram', true);
    if (!$svg_path) return null;

    $full_path = wp_upload_dir()['basedir'] . '/' . $svg_path;
    if (!file_exists($full_path)) return null;

    return file_get_contents($full_path);
}

/**
 * Display SVG diagram in place of product image
 */
function maxus_display_svg_product_image() {
    global $product, $wpdb;
    if (!$product) return;

    $product_id = $product->get_id();
    $diagram_cat = maxus_get_product_diagram_category($product_id);
    if (!$diagram_cat) {
        // No diagram, show default image
        woocommerce_show_product_images();
        return;
    }

    $svg_content = maxus_get_diagram_svg($diagram_cat->term_id);
    if (!$svg_content) {
        // No SVG, show default image
        woocommerce_show_product_images();
        return;
    }

    // Get category-specific callout, fall back to generic
    $tt_id = $wpdb->get_var($wpdb->prepare(
        "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
        $diagram_cat->term_id
    ));
    $current_callout = get_post_meta($product_id, 'callout_cat_' . $tt_id, true);
    if (!$current_callout) {
        $current_callout = get_post_meta($product_id, 'callout_number', true);
    }
    ?>
    <div class="maxus-product-diagram-image">
        <div class="diagram-svg-wrapper" data-current-callout="<?php echo esc_attr($current_callout); ?>">
            <div class="diagram-svg-container" id="product-svg-container">
                <?php echo $svg_content; ?>
            </div>
            <div class="diagram-callout-indicator">
                <button type="button" class="zoom-btn zoom-out" id="product-zoom-out" title="Zoom out">&#8722;</button>
                <?php if ($current_callout): ?>
                    <span class="callout-center">Find this part: <span class="highlight-callout"><?php echo esc_html($current_callout); ?></span></span>
                <?php else: ?>
                    <span class="callout-center">Diagram</span>
                <?php endif; ?>
                <button type="button" class="zoom-btn zoom-in" id="product-zoom-in" title="Zoom in">&#43;</button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Display related parts table with SVG diagram side by side
 */
function maxus_display_related_parts($product_id = null) {
    if (!$product_id) {
        global $product;
        if (!$product) return;
        $product_id = $product->get_id();
    }

    $diagram_cat = maxus_get_product_diagram_category($product_id);
    if (!$diagram_cat) return;

    $related_parts = maxus_get_related_diagram_parts($product_id, $diagram_cat->term_id);
    if (empty($related_parts)) return;

    // Get category-specific callout, fall back to generic
    global $wpdb;
    $tt_id = $wpdb->get_var($wpdb->prepare(
        "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
        $diagram_cat->term_id
    ));
    $current_callout = get_post_meta($product_id, 'callout_cat_' . $tt_id, true);
    if (!$current_callout) {
        $current_callout = get_post_meta($product_id, 'callout_number', true);
    }
    $svg_content = maxus_get_diagram_svg($diagram_cat->term_id);
    ?>
    <div class="maxus-related-parts">
        <h3>All Parts in This Diagram</h3>
        <p class="diagram-name"><?php echo esc_html($diagram_cat->name); ?> (<?php echo count($related_parts); ?> positions)</p>

        <div class="diagram-layout" data-current-callout="<?php echo esc_attr($current_callout); ?>">
            <!-- Table on left -->
            <div class="diagram-table-column">
                <div class="related-parts-table-wrapper">
                    <table class="related-parts-table">
                        <thead>
                            <tr>
                                <th class="col-callout">#</th>
                                <th class="col-name">Part Name</th>
                                <th class="col-sku">SKU</th>
                                <th class="col-fits">Fits</th>
                                <th class="col-price">Price</th>
                                <th class="col-cart"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($related_parts as $part):
                                $is_current = ($part->ID == $product_id);
                                $row_class = $is_current ? 'current-part' : '';
                                $permalink = get_permalink($part->ID);
                                $price_display = $part->price ? wc_price($part->price) : '<span class="price-na">N/A</span>';
                                $variant_count = isset($part->variant_count) ? (int)$part->variant_count : 1;
                                $vehicles = maxus_get_sku_vehicles($part->sku);
                            ?>
                            <tr class="<?php echo $row_class; ?>" data-callout="<?php echo esc_attr($part->callout); ?>">
                                <td class="col-callout">
                                    <span class="callout-number"><?php echo esc_html($part->callout); ?></span>
                                </td>
                                <td class="col-name">
                                    <?php if ($is_current): ?>
                                        <strong><?php echo esc_html($part->post_title); ?></strong>
                                        <span class="current-badge">Current</span>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($part->post_title); ?></a>
                                    <?php endif; ?>
                                    <?php if ($variant_count > 1): ?>
                                        <span class="variant-badge" title="<?php echo $variant_count; ?> variant parts available for this position">+<?php echo ($variant_count - 1); ?> variants</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-sku"><?php echo esc_html($part->sku); ?></td>
                                <td class="col-fits"><span class="fits-vehicles"><?php echo esc_html($vehicles); ?></span></td>
                                <td class="col-price"><?php echo $price_display; ?></td>
                                <td class="col-cart">
                                    <?php if ($part->price && $part->price > 0): ?>
                                        <a href="<?php echo esc_url(wc_get_cart_url() . '?add-to-cart=' . $part->ID); ?>" class="add-to-cart-btn" data-product-id="<?php echo $part->ID; ?>">Add</a>
                                    <?php else: ?>
                                        <?php echo maxus_price_enquiry_button($part->sku, $part->post_title); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SVG on right -->
            <?php if ($svg_content): ?>
            <div class="diagram-svg-column">
                <div class="diagram-svg-wrapper">
                    <div class="diagram-svg-container diagram-svg-full">
                        <?php echo $svg_content; ?>
                    </div>
                    <div class="diagram-callout-indicator">
                        Part Number #<span class="highlight-callout"><?php echo esc_html($current_callout); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Remove default WooCommerce product image and replace with SVG diagram
add_action('wp', function() {
    if (is_product()) {
        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
    }
});
add_action('woocommerce_before_single_product_summary', 'maxus_display_svg_product_image', 20);

// Only show the related parts table on vehicle-context pages, not the default WooCommerce product page
// (Vehicle templates call maxus_display_related_parts() directly)

// Add JavaScript for SVG callout highlighting
add_action('wp_footer', 'maxus_svg_highlight_script');
function maxus_svg_highlight_script() {
    if (!is_product()) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle product image SVG (top of page)
        var productImageWrapper = document.querySelector('.maxus-product-diagram-image .diagram-svg-wrapper');
        if (productImageWrapper) {
            initSvgHighlighting(productImageWrapper, true);
        }

        // Handle diagram layout SVG (in parts section)
        var diagramLayout = document.querySelector('.diagram-layout');
        var diagramWrapper = diagramLayout ? diagramLayout.querySelector('.diagram-svg-wrapper') : null;
        if (diagramWrapper) {
            initSvgHighlighting(diagramWrapper, false, diagramLayout);
        }
    });

    function initSvgHighlighting(wrapper, isProductImage, layout) {
        var currentCallout = (layout && layout.getAttribute('data-current-callout')) || wrapper.getAttribute('data-current-callout');
        if (!currentCallout) return;

        var svg = wrapper.querySelector('svg');
        if (!svg) return;

        var container = wrapper.querySelector('.diagram-svg-container');

        // Fix SVG viewBox to show all content (some SVGs have transforms that push content outside the declared viewBox)
        try {
            var bbox = svg.getBBox();
            if (bbox.width > 0 && bbox.height > 0) {
                var pad = 10;
                svg.setAttribute('viewBox', (bbox.x - pad) + ' ' + (bbox.y - pad) + ' ' + (bbox.width + pad * 2) + ' ' + (bbox.height + pad * 2));
            }
        } catch(e) {}

        // Find all text elements and line elements in the SVG
        var textElements = svg.querySelectorAll('text');
        var allPaths = svg.querySelectorAll('path, line, polyline');

        // Store direct references to currently highlighted elements
        var highlightedLines = [];
        var highlightedParts = [];
        var highlightedTexts = [];

        // For product image SVG: set up viewBox-based zoom/pan
        var origVB = null;
        if (isProductImage) {
            var vbStr = svg.getAttribute('viewBox');
            if (!vbStr) {
                vbStr = '0 0 ' + (svg.getAttribute('width') || 500) + ' ' + (svg.getAttribute('height') || 500);
                svg.setAttribute('viewBox', vbStr);
            }
            origVB = vbStr.split(/[\s,]+/).map(Number);
            // Make SVG fill container via viewBox
            svg.style.width = '100%';
            svg.style.minWidth = '100%';
            svg.style.height = '100%';
            svg.removeAttribute('width');
            svg.removeAttribute('height');
            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
            if (container) {
                container.style.overflow = 'hidden';
            }

            // Zoom buttons
            var zoomInBtn = document.getElementById('product-zoom-in');
            var zoomOutBtn = document.getElementById('product-zoom-out');

            function getCurrentVB() {
                var vb = svg.getAttribute('viewBox');
                if (!vb) return null;
                var p = vb.split(/[\s,]+/).map(Number);
                return { x: p[0], y: p[1], w: p[2], h: p[3] };
            }

            function applyZoom(factor) {
                var cur = getCurrentVB();
                if (!cur) return;
                var cx = cur.x + cur.w / 2, cy = cur.y + cur.h / 2;
                var nw = Math.min(Math.max(cur.w * factor, origVB[2] * 0.15), origVB[2]);
                var nh = Math.min(Math.max(cur.h * factor, origVB[3] * 0.15), origVB[3]);
                var nx = Math.max(origVB[0], Math.min(cx - nw / 2, origVB[0] + origVB[2] - nw));
                var ny = Math.max(origVB[1], Math.min(cy - nh / 2, origVB[1] + origVB[3] - nh));
                svg.setAttribute('viewBox', nx + ' ' + ny + ' ' + nw + ' ' + nh);
            }

            if (zoomInBtn) zoomInBtn.addEventListener('click', function() { applyZoom(0.7); });
            if (zoomOutBtn) zoomOutBtn.addEventListener('click', function() { applyZoom(1.4); });

            // Mouse wheel zoom
            if (container) {
                container.addEventListener('wheel', function(e) {
                    e.preventDefault();
                    applyZoom(e.deltaY > 0 ? 1.15 : 0.85);
                }, { passive: false });
            }

            // Click-and-drag panning
            var isPanning = false, panStartX, panStartY, panVB;
            if (container) {
                container.addEventListener('mousedown', function(e) {
                    if (e.button !== 0) return;
                    isPanning = true;
                    container.classList.add('is-panning');
                    panStartX = e.clientX;
                    panStartY = e.clientY;
                    panVB = getCurrentVB();
                    e.preventDefault();
                });
                window.addEventListener('mousemove', function(e) {
                    if (!isPanning || !panVB) return;
                    var svgRect = svg.getBoundingClientRect();
                    var scaleX = panVB.w / svgRect.width;
                    var scaleY = panVB.h / svgRect.height;
                    var nx = panVB.x - (e.clientX - panStartX) * scaleX;
                    var ny = panVB.y - (e.clientY - panStartY) * scaleY;
                    nx = Math.max(origVB[0], Math.min(nx, origVB[0] + origVB[2] - panVB.w));
                    ny = Math.max(origVB[1], Math.min(ny, origVB[1] + origVB[3] - panVB.h));
                    svg.setAttribute('viewBox', nx + ' ' + ny + ' ' + panVB.w + ' ' + panVB.h);
                });
                window.addEventListener('mouseup', function() {
                    if (isPanning) {
                        isPanning = false;
                        container.classList.remove('is-panning');
                    }
                });
            }
        }

        // Navigate to a callout position
        function focusCallout(cx, cy, minX, minY, maxX, maxY) {
            if (isProductImage && origVB) {
                // ViewBox-based zoom for product image
                var spanW = (maxX || cx) - (minX || cx);
                var spanH = (maxY || cy) - (minY || cy);
                var padW = Math.max(spanW * 0.75, origVB[2] * 0.2);
                var padH = Math.max(spanH * 0.75, origVB[3] * 0.2);
                var zw = Math.min(Math.max(spanW + padW * 2, origVB[2] * 0.55), origVB[2]);
                var zh = Math.min(Math.max(spanH + padH * 2, origVB[3] * 0.55), origVB[3]);
                var zcx = minX !== undefined ? (minX + maxX) / 2 : cx;
                var zcy = minY !== undefined ? (minY + maxY) / 2 : cy;
                var nx = Math.max(origVB[0], Math.min(zcx - zw / 2, origVB[0] + origVB[2] - zw));
                var ny = Math.max(origVB[1], Math.min(zcy - zh / 2, origVB[1] + origVB[3] - zh));
                svg.setAttribute('viewBox', nx + ' ' + ny + ' ' + zw + ' ' + zh);
            } else if (container) {
                // Scroll-based for related parts diagram
                var viewBox = svg.viewBox.baseVal;
                var svgViewWidth = viewBox && viewBox.width ? viewBox.width : 1;
                var svgViewHeight = viewBox && viewBox.height ? viewBox.height : 1;
                var svgRect = svg.getBoundingClientRect();
                var scaleX = svgRect.width / svgViewWidth;
                var scaleY = svgRect.height / svgViewHeight;
                var scrollLeft = Math.max(0, cx * scaleX - container.clientWidth / 2);
                var scrollTop = Math.max(0, cy * scaleY - container.clientHeight / 2);
                try { container.scrollTo({ left: scrollLeft, top: scrollTop, behavior: 'instant' }); } catch(e) {
                    container.scrollLeft = scrollLeft; container.scrollTop = scrollTop;
                }
                setTimeout(function() {
                    try { container.scrollTo({ left: scrollLeft, top: scrollTop, behavior: 'instant' }); } catch(e) {
                        container.scrollLeft = scrollLeft; container.scrollTop = scrollTop;
                    }
                }, 500);
            }
        }

        // Helper function to get distance between two points
        function getDistance(x1, y1, x2, y2) {
            return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
        }

        // Get the actual position of a text element accounting for its transform
        function getTextPosition(textEl) {
            var bbox = textEl.getBBox();
            var transform = textEl.getAttribute('transform');

            // Default position from bbox
            var x = bbox.x;
            var y = bbox.y;
            var width = bbox.width;
            var height = bbox.height;

            // Parse transform matrix if present: matrix(a b c d e f) where e=translateX, f=translateY
            if (transform) {
                var matrixMatch = transform.match(/matrix\s*\(\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*\)/);
                if (matrixMatch) {
                    var e = parseFloat(matrixMatch[5]); // translateX
                    var f = parseFloat(matrixMatch[6]); // translateY
                    x = e + bbox.x;
                    y = f + bbox.y;
                }

                // Also check for simple translate
                var translateMatch = transform.match(/translate\s*\(\s*([\d.-]+)\s*[\s,]\s*([\d.-]+)\s*\)/);
                if (translateMatch) {
                    x = parseFloat(translateMatch[1]) + bbox.x;
                    y = parseFloat(translateMatch[2]) + bbox.y;
                }
            }

            return { x: x, y: y, width: width, height: height };
        }

        // Helper function to parse path d attribute and get start/end points
        function getPathEndpoints(pathEl) {
            var d = pathEl.getAttribute('d');
            if (!d) return null;

            // Get start point (M command)
            var moveMatch = d.match(/M\s*([\d.-]+)[,\s]*([\d.-]+)/i);
            var startX = moveMatch ? parseFloat(moveMatch[1]) : null;
            var startY = moveMatch ? parseFloat(moveMatch[2]) : null;

            // Try to get end point from various commands
            var endX = startX, endY = startY;

            // Look for line commands (L, l, H, h, V, v)
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
                    // Cubic bezier - end point is last pair
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

        // Helper function to get line endpoints
        function getLineEndpoints(lineEl) {
            return {
                startX: parseFloat(lineEl.getAttribute('x1')),
                startY: parseFloat(lineEl.getAttribute('y1')),
                endX: parseFloat(lineEl.getAttribute('x2')),
                endY: parseFloat(lineEl.getAttribute('y2'))
            };
        }

        // Find leader lines connected to a text element
        // EPC diagrams typically have lines going from parts to a legend area where text labels are
        function findConnectedLines(textBbox, searchRadius, textEl) {
            var connected = [];
            var textCx = textBbox.x + textBbox.width / 2;
            var textCy = textBbox.y + textBbox.height / 2;
            var textRight = textBbox.x + textBbox.width;
            var textLeft = textBbox.x;

            // Method 0: Structural approach - look for lines in sibling <g> element
            // Many SVGs have structure: <g><text>17</text></g><g><line.../></g>
            if (textEl) {
                var parentG = textEl.parentElement;
                if (parentG && parentG.tagName.toLowerCase() === 'g') {
                    var nextG = parentG.nextElementSibling;
                    if (nextG && nextG.tagName.toLowerCase() === 'g') {
                        var siblingLines = nextG.querySelectorAll('line, path, polyline');
                        siblingLines.forEach(function(el) {
                            // Skip white lines (stroke="#FFFFFF" or "white")
                            var stroke = el.getAttribute('stroke');
                            if (stroke) {
                                var strokeUpper = stroke.toUpperCase();
                                if (strokeUpper === '#FFFFFF' || strokeUpper === 'WHITE' || strokeUpper === '#FFF') return;
                            }

                            var endpoints;
                            if (el.tagName.toLowerCase() === 'line') {
                                endpoints = getLineEndpoints(el);
                            } else if (el.tagName.toLowerCase() === 'path' || el.tagName.toLowerCase() === 'polyline') {
                                endpoints = getPathEndpoints(el);
                            }
                            if (endpoints && endpoints.startX !== null) {
                                connected.push({
                                    element: el,
                                    endpoints: endpoints,
                                    connectedAtStart: false, // Part is at the start (line goes from part to text area)
                                    distance: 0
                                });
                            }
                        });
                    }
                }
            }

            // If structural approach found lines, use those
            if (connected.length > 0) {
                return connected;
            }

            allPaths.forEach(function(el) {
                var endpoints;
                if (el.tagName === 'line') {
                    endpoints = getLineEndpoints(el);
                } else if (el.tagName === 'path' || el.tagName === 'polyline') {
                    endpoints = getPathEndpoints(el);
                }

                if (!endpoints || endpoints.startX === null) return;

                // Skip very short lines (they're usually part of the drawing, not leaders)
                var lineLength = getDistance(endpoints.startX, endpoints.startY, endpoints.endX, endpoints.endY);
                if (lineLength < 20) return;

                // Method 1: Direct proximity to text center (very tight - for diagrams where lines touch the number)
                var distToStart = getDistance(textCx, textCy, endpoints.startX, endpoints.startY);
                var distToEnd = getDistance(textCx, textCy, endpoints.endX, endpoints.endY);
                var tightProximity = 15; // Increased tolerance

                if (distToStart < tightProximity || distToEnd < tightProximity) {
                    connected.push({
                        element: el,
                        endpoints: endpoints,
                        connectedAtStart: distToStart < distToEnd,
                        distance: Math.min(distToStart, distToEnd)
                    });
                    return;
                }

                // Method 2: Legend-style - line ends near text Y position
                // These are solid lines from parts to the text legend area
                var yTolerance = 8; // Increased Y tolerance

                // Check if either endpoint is near the text's Y coordinate
                var startNearTextY = Math.abs(endpoints.startY - textCy) < yTolerance;
                var endNearTextY = Math.abs(endpoints.endY - textCy) < yTolerance;

                // The endpoint near the text should be close to the text's X
                // Lines can be to the LEFT (x < textLeft) or RIGHT (x > textLeft) of text
                var startNearTextArea = Math.abs(endpoints.startX - textLeft) < 20;
                var endNearTextArea = Math.abs(endpoints.endX - textLeft) < 20;

                if (startNearTextY && startNearTextArea) {
                    connected.push({
                        element: el,
                        endpoints: endpoints,
                        connectedAtStart: true, // Part is at the END
                        distance: Math.abs(endpoints.startY - textCy)
                    });
                    return;
                }

                if (endNearTextY && endNearTextArea) {
                    connected.push({
                        element: el,
                        endpoints: endpoints,
                        connectedAtStart: false, // Part is at the START
                        distance: Math.abs(endpoints.endY - textCy)
                    });
                    return;
                }
            });

            // Only keep the single closest line
            if (connected.length > 1) {
                connected.sort(function(a, b) { return a.distance - b.distance; });
                connected = connected.slice(0, 1);
            }

            return connected;
        }

        // Find all connected line segments (they may be chained)
        function findAllConnectedDashedLines(startLines) {
            // For now, just return the starting lines
            // Could be extended to follow chains of connected lines
            return startLines;
        }

        // Find part geometry near the end of a leader line (the end pointing to the part, not the text)
        function findPartGeometry(lines, searchRadius) {
            var parts = [];
            var partEndpoints = [];

            // Get the endpoint that points to the part (opposite of where text connects)
            lines.forEach(function(lineInfo) {
                var ep = lineInfo.endpoints;
                // connectedAtStart means text is at start, so part is at end
                if (lineInfo.connectedAtStart) {
                    partEndpoints.push({ x: ep.endX, y: ep.endY });
                } else {
                    partEndpoints.push({ x: ep.startX, y: ep.startY });
                }
            });

            // Find shapes very close to the part endpoints
            partEndpoints.forEach(function(point) {

                allPaths.forEach(function(el) {
                    // Skip <line> elements - they are leader lines, not parts
                    // Parts are drawn with <path> elements
                    if (el.tagName.toLowerCase() === 'line') return;

                    // Skip the leader lines themselves
                    var isLeaderLine = lines.some(function(l) { return l.element === el; });
                    if (isLeaderLine) return;

                    try {
                        var bbox = el.getBBox();

                        // Skip very small elements (likely decorative)
                        if (bbox.width < 3 || bbox.height < 3) return;

                        // Check if the line endpoint actually touches this element
                        var tightRadius = 15; // Increased radius for better matching
                        var inBbox = point.x >= bbox.x - tightRadius &&
                                     point.x <= bbox.x + bbox.width + tightRadius &&
                                     point.y >= bbox.y - tightRadius &&
                                     point.y <= bbox.y + bbox.height + tightRadius;

                        if (inBbox && parts.indexOf(el) === -1) {
                            parts.push(el);
                        }
                    } catch(e) {}
                });
            });

            // Limit to reasonable number of parts
            if (parts.length > 20) {
                parts = parts.slice(0, 20);
            }

            return parts;
        }

        // Highlight a callout with its connected elements
        function highlightCalloutFull(callout, doHighlight, isPermanent) {
            var color = isPermanent ? '#F29F05' : '#ff0000';
            var opacity = isPermanent ? '0.3' : '0.4';
            var processedGroups = []; // Track which parent groups we've processed

            textElements.forEach(function(textEl) {
                if (textEl.textContent.trim() !== callout) return;

                // Skip if we already processed this text's parent group (handles duplicate text elements)
                var parentG = textEl.parentElement;
                if (parentG && processedGroups.indexOf(parentG) !== -1) return;
                if (parentG) processedGroups.push(parentG);

                var bbox;
                try {
                    bbox = getTextPosition(textEl);
                } catch(e) {
                    return;
                }

                var cx = bbox.x + bbox.width / 2;
                var cy = bbox.y + bbox.height / 2;

                if (doHighlight) {
                    // Highlight the text
                    textEl.style.fill = color;
                    textEl.style.fontWeight = 'bold';
                    textEl.style.fontSize = isPermanent ? '12px' : '14px';
                    textEl.setAttribute('data-highlighted', 'true');
                    highlightedTexts.push(textEl); // Store reference

                    // Find initial connected leader lines
                    var initialLines = findConnectedLines(bbox, 10, textEl);

                    // Follow the chain of dashed lines
                    var allConnectedLines = findAllConnectedDashedLines(initialLines);

                    // Track bounding area of callout + leader lines for zoom
                    var minX = cx, maxX = cx, minY = cy, maxY = cy;

                    // Highlight all connected leader lines using CSS classes AND inline styles
                    var lineClass = isPermanent ? 'svg-highlight-line' : 'svg-highlight-line-hover';
                    var lineColor = isPermanent ? '#F29F05' : '#ff0000';
                    allConnectedLines.forEach(function(lineInfo) {
                        var el = lineInfo.element;
                        // Store original values for restoration
                        if (el._originalStroke === undefined) {
                            el._originalStroke = el.getAttribute('stroke') || '';
                            el._originalStrokeWidth = el.getAttribute('stroke-width') || '';
                        }
                        el.classList.add(lineClass);
                        // Also set inline styles to ensure highlighting works
                        el.style.stroke = lineColor;
                        el.style.strokeWidth = '4px';
                        el.setAttribute('stroke', lineColor);
                        el.setAttribute('stroke-width', '4');
                        highlightedLines.push(el); // Store reference
                        // Expand bounds
                        var ep = lineInfo.endpoints;
                        if (ep && ep.startX !== null) {
                            minX = Math.min(minX, ep.startX, ep.endX);
                            maxX = Math.max(maxX, ep.startX, ep.endX);
                            minY = Math.min(minY, ep.startY, ep.endY);
                            maxY = Math.max(maxY, ep.startY, ep.endY);
                        }
                    });

                    // Find part geometry at the ends of the line chain
                    var partClass = isPermanent ? 'svg-highlight-part' : 'svg-highlight-part-hover';
                    var partColor = isPermanent ? '#F29F05' : '#ff0000';
                    var parts = findPartGeometry(allConnectedLines, 25);
                    parts.forEach(function(partEl) {
                        // Store original values for restoration
                        if (partEl._originalStroke === undefined) {
                            partEl._originalStroke = partEl.getAttribute('stroke') || '';
                            partEl._originalStrokeWidth = partEl.getAttribute('stroke-width') || '';
                        }
                        partEl.classList.add(partClass);
                        // Also set inline styles
                        partEl.style.stroke = partColor;
                        partEl.style.strokeWidth = '3px';
                        partEl.setAttribute('stroke', partColor);
                        partEl.setAttribute('stroke-width', '3');
                        highlightedParts.push(partEl); // Store reference
                    });

                    // Create highlight circle for permanent highlighting
                    if (isPermanent) {
                        var radius = Math.max(bbox.width, bbox.height) * 0.8;

                        var highlight = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        highlight.setAttribute('cx', cx);
                        highlight.setAttribute('cy', cy);
                        highlight.setAttribute('r', radius);
                        highlight.setAttribute('fill', color);
                        highlight.setAttribute('class', 'callout-highlight');
                        highlight.style.opacity = opacity;

                        var ring = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        ring.setAttribute('cx', cx);
                        ring.setAttribute('cy', cy);
                        ring.setAttribute('r', radius * 1.5);
                        ring.setAttribute('fill', 'none');
                        ring.setAttribute('stroke', color);
                        ring.setAttribute('stroke-width', '2');
                        ring.setAttribute('class', 'callout-ring');

                        textEl.parentNode.insertBefore(highlight, textEl);
                        textEl.parentNode.insertBefore(ring, textEl);
                    }

                    // Focus on the highlighted callout
                    focusCallout(cx, cy, minX, minY, maxX, maxY);
                }
            });
        }

        // Track currently active highlight
        var activeCallout = null;

        // Clear all highlighting from SVG
        function clearAllHighlights() {
            // Reset tracked text elements
            highlightedTexts.forEach(function(textEl) {
                textEl.style.fill = '';
                textEl.style.fontWeight = '';
                textEl.style.fontSize = '';
                textEl.removeAttribute('data-highlighted');
            });
            highlightedTexts = [];

            // Remove all highlight circles and rings
            var highlights = svg.querySelectorAll('.callout-highlight, .callout-ring');
            highlights.forEach(function(el) {
                el.remove();
            });

            // Remove highlight classes and inline styles from tracked line elements
            highlightedLines.forEach(function(el) {
                el.classList.remove('svg-highlight-line', 'svg-highlight-line-hover');
                el.style.stroke = '';
                el.style.strokeWidth = '';
                // Restore original attributes if stored
                if (el._originalStroke !== undefined) {
                    el.setAttribute('stroke', el._originalStroke);
                    el.setAttribute('stroke-width', el._originalStrokeWidth || '');
                }
            });
            highlightedLines = [];

            // Remove highlight classes and inline styles from tracked part elements
            highlightedParts.forEach(function(el) {
                el.classList.remove('svg-highlight-part', 'svg-highlight-part-hover');
                el.style.stroke = '';
                el.style.strokeWidth = '';
                // Restore original attributes if stored
                if (el._originalStroke !== undefined) {
                    el.setAttribute('stroke', el._originalStroke);
                    el.setAttribute('stroke-width', el._originalStrokeWidth || '');
                }
            });
            highlightedParts = [];

            activeCallout = null;
        }

        // Highlight a specific callout (clears previous first)
        function showCallout(callout, isPermanent) {
            // Always clear and re-highlight to ensure clean state
            clearAllHighlights();
            highlightCalloutFull(callout, true, isPermanent);
            activeCallout = callout;
        }

        // Initial highlight for current product's callout
        showCallout(currentCallout, true);

        // Add hover interaction for parts table (only for diagram layout, not product image)
        if (!isProductImage) {
            var tableRows = document.querySelectorAll('.related-parts-table tbody tr');
            tableRows.forEach(function(row) {
                row.addEventListener('mouseenter', function() {
                    var callout = this.getAttribute('data-callout');
                    showCallout(callout, callout === currentCallout);
                });
                row.addEventListener('mouseleave', function() {
                    // Restore current product's callout
                    showCallout(currentCallout, true);
                });
            });
        }
    }

    // Zoom feature for related parts diagram SVG - hover to zoom
    document.addEventListener('DOMContentLoaded', function() {
        var zoomContainer = document.querySelector('.diagram-svg-full');
        if (!zoomContainer) return;

        var svg = zoomContainer.querySelector('svg');
        if (!svg) return;

        zoomContainer.addEventListener('mouseenter', function(e) {
            zoomContainer.classList.add('zoomed');
            updateZoomPosition(e);
        });

        zoomContainer.addEventListener('mousemove', function(e) {
            updateZoomPosition(e);
        });

        zoomContainer.addEventListener('mouseleave', function() {
            zoomContainer.classList.remove('zoomed');
            svg.style.transformOrigin = 'center center';
        });

        function updateZoomPosition(e) {
            var rect = zoomContainer.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width) * 100;
            var y = ((e.clientY - rect.top) / rect.height) * 100;
            svg.style.transformOrigin = x + '% ' + y + '%';
        }
    });

    // Match table height to SVG wrapper height (including Part Number banner)
    document.addEventListener('DOMContentLoaded', function() {
        function matchTableToSvg() {
            var svgWrapper = document.querySelector('.diagram-layout .diagram-svg-wrapper');
            var tableWrapper = document.querySelector('.diagram-layout .related-parts-table-wrapper');
            if (svgWrapper && tableWrapper) {
                var wrapperHeight = svgWrapper.offsetHeight;
                if (wrapperHeight > 100) {
                    tableWrapper.style.height = wrapperHeight + 'px';
                    tableWrapper.style.maxHeight = wrapperHeight + 'px';
                }
            }
        }
        // Run after SVG loads
        setTimeout(matchTableToSvg, 100);
        setTimeout(matchTableToSvg, 500);
        window.addEventListener('resize', matchTableToSvg);
    });
    </script>
    <style>
    .callout-ring {
        animation: pulse-ring 1.5s ease-out infinite;
    }
    @keyframes pulse-ring {
        0% { opacity: 1; transform-origin: center; }
        100% { opacity: 0; r: 25; }
    }
    .related-parts-table tbody tr {
        cursor: pointer;
    }
    .related-parts-table tbody tr:hover .callout-number {
        transform: scale(1.2);
        background: #ff0000;
    }
    .col-fits {
        font-size: 11px;
        color: #666;
        max-width: 150px;
    }
    .fits-vehicles {
        display: block;
        line-height: 1.3;
    }
    .variant-badge {
        display: inline-block;
        background: #6c757d;
        color: #fff;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
        vertical-align: middle;
    }
    /* Highlighting classes for SVG elements */
    .svg-highlight-line {
        stroke: #F29F05 !important;
        stroke-width: 4px !important;
        stroke-opacity: 1 !important;
    }
    .svg-highlight-line-hover {
        stroke: #ff0000 !important;
        stroke-width: 4px !important;
        stroke-opacity: 1 !important;
    }
    .svg-highlight-part {
        stroke: #F29F05 !important;
        stroke-width: 3px !important;
        stroke-opacity: 0.8 !important;
        fill-opacity: 0.2 !important;
    }
    .svg-highlight-part-hover {
        stroke: #ff0000 !important;
        stroke-width: 3px !important;
        stroke-opacity: 0.8 !important;
        fill-opacity: 0.2 !important;
    }
    /* Product image replacement styles */
    .maxus-product-diagram-image {
        width: 500px;
        max-width: 100%;
        margin-right: 40px;
    }
    .maxus-product-diagram-image .diagram-svg-wrapper {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 10px;
        width: 100%;
        box-sizing: border-box;
    }
    .maxus-product-diagram-image .diagram-svg-container {
        width: 100%;
        height: 400px;
        overflow: hidden;
        position: relative;
        background: #fafafa;
        border-radius: 6px;
    }
    .maxus-product-diagram-image .diagram-svg-container svg {
        width: 100%;
        height: 100%;
        display: block;
    }
    .maxus-product-diagram-image .diagram-callout-indicator {
        background: #F29F05;
        color: #fff;
        padding: 8px 15px;
        font-weight: bold;
        border-radius: 0 0 4px 4px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }
    .maxus-product-diagram-image .diagram-callout-indicator .callout-center {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
    }
    .maxus-product-diagram-image .diagram-callout-indicator .highlight-callout {
        display: inline-block;
        background: #fff;
        color: #F29F05;
        padding: 2px 12px;
        border-radius: 12px;
        font-weight: bold;
        margin-left: 5px;
    }
    .maxus-product-diagram-image .zoom-btn {
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
    .maxus-product-diagram-image .zoom-btn:hover {
        background: rgba(255,255,255,0.25);
    }
    .maxus-product-diagram-image .diagram-svg-container {
        cursor: grab;
    }
    .maxus-product-diagram-image .diagram-svg-container.is-panning {
        cursor: grabbing;
    }
    /* Reduce gap between product summary and description */
    .single-product .woocommerce-tabs,
    .woocommerce-tabs,
    div.woocommerce-tabs {
        margin-top: 5px !important;
        padding-top: 0 !important;
    }
    .woocommerce-tabs .wc-tabs-wrapper,
    .woocommerce-tabs .panel {
        margin-top: 0 !important;
    }
    /* Reduce gap below product image/summary area */
    .maxus-product-diagram-image {
        margin-bottom: 5px !important;
    }
    .summary.entry-summary,
    .product-summary,
    div.summary {
        margin-bottom: 5px !important;
        padding-bottom: 0 !important;
    }
    .single-product div.product,
    .woocommerce div.product {
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    .single-product .product-top,
    .single-product .product-content-top,
    .product-content-top,
    .product-top {
        margin-bottom: 5px !important;
        padding-bottom: 0 !important;
    }
    /* Target the row/container between summary and tabs */
    .single-product .row,
    .single-product .product-row {
        margin-bottom: 5px !important;
    }
    .woocommerce-product-details__short-description {
        margin-bottom: 5px !important;
    }
    </style>
    <?php
}
