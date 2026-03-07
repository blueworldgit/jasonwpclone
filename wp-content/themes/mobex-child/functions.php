<?php

function mobex_enovathemes_child_scripts() {
    wp_enqueue_style( 'mobex_enovathemes-parent-style', get_template_directory_uri(). '/style.css' );

    // Enqueue Elementor header CSS - theme loads header with get_builder_content($id, false)
    // which skips CSS, so we need to load it manually for widget-level styles to apply
    $header_id = 8543;
    $header_css_file = '/elementor/css/post-' . $header_id . '.css';
    $header_css_path = wp_upload_dir()['basedir'] . $header_css_file;
    if (file_exists($header_css_path)) {
        wp_enqueue_style(
            'elementor-post-' . $header_id,
            wp_upload_dir()['baseurl'] . $header_css_file,
            [],
            filemtime($header_css_path)
        );
    }
}
add_action( 'wp_enqueue_scripts', 'mobex_enovathemes_child_scripts' );

add_action('after_switch_theme', 'mobex_child_repair_theme_mods_and_kirki_css');
add_action('admin_init', 'mobex_child_repair_theme_mods_and_kirki_css_once');

function mobex_child_repair_theme_mods_and_kirki_css_once() {
    // If we already repaired, skip.
    if (get_option('mobex_child_theme_mods_repaired')) {
        return;
    }
    $did = mobex_child_repair_theme_mods_and_kirki_css();
    if ($did) {
        update_option('mobex_child_theme_mods_repaired', 1);
    }
}

/**
 * Returns true if it actually migrated/changed anything.
 */
function mobex_child_repair_theme_mods_and_kirki_css() {
    $parent = get_template();
    $child  = get_stylesheet();
    if ($parent === $child) {
        // Not a child setup.
        return false;
    }
}

// =============================================================================
// FEATURED PRODUCTS: SWITCH TO MOST PURCHASED
// =============================================================================
// CURRENTLY: Homepage shows hand-picked featured products (Deliver 9 parts).
// TO SWITCH: Uncomment the add_action line below. This will automatically
// update the "featured" flag to the 18 most-ordered products each day.
// Once you have enough sales data, just uncomment and it will take over.
//
// add_action('wp_loaded', 'maxus_update_featured_to_best_sellers');
//
function maxus_update_featured_to_best_sellers() {
    if (get_transient('maxus_featured_updated')) return;

    global $wpdb;

    $top_products = $wpdb->get_col("
        SELECT oi_meta.meta_value as product_id
        FROM {$wpdb->prefix}woocommerce_order_items oi
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oi_meta
            ON oi_meta.order_item_id = oi.order_item_id AND oi_meta.meta_key = '_product_id'
        JOIN {$wpdb->prefix}posts o
            ON o.ID = oi.order_id AND o.post_status IN ('wc-completed','wc-processing')
        GROUP BY product_id
        ORDER BY SUM(
            (SELECT im2.meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta im2
             WHERE im2.order_item_id = oi.order_item_id AND im2.meta_key = '_qty')
        ) DESC
        LIMIT 18
    ");

    if (empty($top_products)) return;

    $featured_term = get_term_by('slug', 'featured', 'product_visibility');
    if (!$featured_term) return;

    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = %d",
        $featured_term->term_taxonomy_id
    ));

    foreach ($top_products as $pid) {
        $wpdb->insert($wpdb->prefix . 'term_relationships', [
            'object_id' => $pid,
            'term_taxonomy_id' => $featured_term->term_taxonomy_id,
            'term_order' => 0,
        ]);
    }

    wp_update_term_count($featured_term->term_taxonomy_id, 'product_visibility');
    set_transient('maxus_featured_updated', 1, DAY_IN_SECONDS);
}

// =============================================================================
// VEHICLE-SPECIFIC PAGES
// =============================================================================

/**
 * One-time flush of rewrite rules when vehicle config is updated
 */
add_action('init', 'maxus_maybe_flush_rewrite_rules', 99);
function maxus_maybe_flush_rewrite_rules() {
    $current_version = 'v5_db_config'; // Increment this when vehicle config changes
    if (get_option('maxus_rewrite_version') !== $current_version) {
        flush_rewrite_rules();
        update_option('maxus_rewrite_version', $current_version);
    }
}

/**
 * Vehicle slug to VIN mapping - All 17 vehicle models
 */
function maxus_get_vehicle_vins() {
    return [
        'maxus-a80-chassis' => [
            'vin' => 'LSH14JTC6FA621119',
            'name' => 'MAXUS A80 CHASSIS',
            'year' => '2015-2020',
        ],
        'maxus-deliver-7' => [
            'vin' => 'LSH14J7C3RV123225',
            'name' => 'MAXUS DELIVER 7',
            'year' => '2024-Present',
        ],
        'maxus-deliver-7-high-roof-diesel' => [
            'vin' => 'LSH14J7C4RV123458',
            'name' => 'MAXUS DELIVER 7 HIGH ROOF DIESEL',
            'year' => '2024-Present',
        ],
        'maxus-deliver-7-low-roof-diesel' => [
            'vin' => 'LSH14J7C9RV123360',
            'name' => 'MAXUS DELIVER 7 LOW ROOF DIESEL',
            'year' => '2024-Present',
        ],
        'maxus-deliver-9-fwd-lux' => [
            'vin' => 'LSH14J7CXMA114599',
            'name' => 'MAXUS DELIVER 9 FWD LUX',
            'year' => '2021-2024',
        ],
        'maxus-deliver-9-fwd-std' => [
            'vin' => 'LSH14J7C7MA114771',
            'name' => 'MAXUS DELIVER 9 FWD STD',
            'year' => '2021-2024',
        ],
        'maxus-deliver-9-rwd-chassis' => [
            'vin' => 'LSFAL11A5MA087816',
            'name' => 'MAXUS DELIVER 9 RWD CHASSIS',
            'year' => '2021-2024',
        ],
        'maxus-deliver-9-rwd-lux' => [
            'vin' => 'LSFAL11A4PA157987',
            'name' => 'MAXUS DELIVER 9 RWD LUX',
            'year' => '2023-2024',
        ],
        'maxus-deliver-9-rwd-std' => [
            'vin' => 'LSH14J7C2MA122115',
            'name' => 'MAXUS DELIVER 9 RWD STD',
            'year' => '2021-2024',
        ],
        'maxus-e-deliver-3' => [
            'vin' => 'LSH14C4C5NA129710',
            'name' => 'MAXUS E DELIVER 3',
            'year' => '2020-Present',
        ],
        'maxus-e-deliver-7' => [
            'vin' => 'LSH14J4C0RV121632',
            'name' => 'MAXUS E DELIVER 7',
            'year' => '2024-Present',
        ],
        'maxus-e-deliver-9' => [
            'vin' => 'LSH14J4CXMA165329',
            'name' => 'MAXUS E DELIVER 9',
            'year' => '2021-Present',
        ],
        'maxus-t60' => [
            'vin' => 'LSFAM11C6RA133899',
            'name' => 'MAXUS T60',
            'year' => '2019-2024',
        ],
        'maxus-t90-ev' => [
            'vin' => 'LSFAM120XNA160733',
            'name' => 'MAXUS T90 EV',
            'year' => '2022-Present',
        ],
        'maxus-v80-van' => [
            'vin' => 'LSKG5GL16KA060062',
            'name' => 'MAXUS V80 VAN',
            'year' => '2016-2020',
        ],
        'new-deliver-9-diesel' => [
            'vin' => 'LSH14J7C0SA082498',
            'name' => 'NEW DELIVER 9 DIESEL',
            'year' => '2025-Present',
        ],
        'new-t60-diesel' => [
            'vin' => 'LSFAM11C6RA144501',
            'name' => 'NEW T60 DIESEL',
            'year' => '2024-Present',
        ],
    ];
}

/**
 * Get vehicle info from slug
 */
function maxus_get_vehicle_by_slug($slug) {
    $vehicles = maxus_get_vehicle_vins();
    return isset($vehicles[$slug]) ? $vehicles[$slug] : null;
}

/**
 * Force vehicles taxonomy to show all terms regardless of post count.
 * Products are linked to vehicles via wp_sku_vin_mapping, not the taxonomy directly,
 * so hide_empty would incorrectly filter out most vehicles.
 */
add_filter('get_terms_args', function($args, $taxonomies) {
    if (is_array($taxonomies) && in_array('vehicles', $taxonomies)) {
        $args['hide_empty'] = false;
    }
    return $args;
}, 10, 2);

/**
 * Override the enovathemes vehicle list AJAX handler to return ALL vehicles.
 * The plugin's handler uses hide_empty which misses most vehicles since products
 * are linked via wp_sku_vin_mapping, not the taxonomy directly.
 * We hook at priority 5 to run BEFORE the plugin registers at default (10).
 */
add_action('init', function() {
    // Remove plugin's AJAX handlers (they get registered later, so also remove on wp_loaded)
    remove_all_actions('wp_ajax_fetch_vehicle_list');
    remove_all_actions('wp_ajax_nopriv_fetch_vehicle_list');

    // Register our override
    add_action('wp_ajax_fetch_vehicle_list', 'maxus_fetch_vehicle_list_override');
    add_action('wp_ajax_nopriv_fetch_vehicle_list', 'maxus_fetch_vehicle_list_override');
}, 999);

/**
 * Also re-register on wp_loaded in case the plugin hooks later
 */
add_action('wp_loaded', function() {
    remove_all_actions('wp_ajax_fetch_vehicle_list');
    remove_all_actions('wp_ajax_nopriv_fetch_vehicle_list');
    add_action('wp_ajax_fetch_vehicle_list', 'maxus_fetch_vehicle_list_override');
    add_action('wp_ajax_nopriv_fetch_vehicle_list', 'maxus_fetch_vehicle_list_override');
}, 999);

/**
 * Custom vehicle list AJAX handler that returns all vehicles (hide_empty=false).
 */
function maxus_fetch_vehicle_list_override() {
    $vehicles = get_terms([
        'taxonomy'   => 'vehicles',
        'hide_empty' => false,
    ]);

    $vehicle_params = apply_filters('vehicle_params', '');
    $vehicles_data = [];

    if (!is_wp_error($vehicles) && $vehicle_params != false) {
        foreach ($vehicles as $vehicle) {
            $vehicle_atts = [];
            foreach ($vehicle_params as $param) {
                if ($param == 'year') {
                    $year_val = get_term_meta($vehicle->term_id, 'vehicle_year', true);
                    $vehicle_years = [];
                    if ($year_val && function_exists('et_year_formatting')) {
                        $years = et_year_formatting($year_val);
                        if ($years) {
                            foreach ($years as $y) {
                                $vehicle_years[] = intval($y);
                            }
                        }
                    }
                    $vehicle_atts[$param] = $vehicle_years;
                } else {
                    $vehicle_atts[$param] = get_term_meta($vehicle->term_id, 'vehicle_' . $param, true);
                }
            }
            if (!empty($vehicle_atts)) {
                $vehicles_data[$vehicle->slug] = $vehicle_atts;
            }
        }
    }

    echo json_encode($vehicles_data);
    die();
}

/**
 * Override the first-parameter dropdown population.
 * The plugin's transient uses hide_empty=true by default, missing most vehicles.
 * We delete stale transients and pre-populate with correct data.
 */
add_action('init', function() {
    delete_transient('vehicle-list');
    delete_transient('vehicles-first-param-model');
    delete_transient('vehicles-first-param');

    // Pre-build the first-param transient with all vehicle models
    $vehicles = get_terms([
        'taxonomy'   => 'vehicles',
        'hide_empty' => false,
    ]);
    if (!is_wp_error($vehicles)) {
        $models = [];
        foreach ($vehicles as $v) {
            $model = get_term_meta($v->term_id, 'vehicle_model', true);
            if ($model) {
                $models[] = $model;
            }
        }
        $models = array_unique($models);
        $models = array_filter($models);
        sort($models);
        if (!empty($models)) {
            set_transient('vehicles-first-param-model', $models, 0);
        }
    }
});

/**
 * Add JS to make the yellow bar vehicle filter redirect to vehicle landing pages
 */
add_action('wp_footer', 'maxus_vehicle_filter_redirect_js', 20);
function maxus_vehicle_filter_redirect_js() {
    $vehicle_vins = maxus_get_vehicle_vins();

    // Build case-insensitive lookup: uppercase stripped name -> slug
    $upper_to_slug = [];
    foreach ($vehicle_vins as $slug => $data) {
        $stripped = strtoupper(preg_replace('/^MAXUS\s+/i', '', $data['name']));
        $upper_to_slug[$stripped] = $slug;
    }

    // Map taxonomy vehicle_model values to landing page slugs
    $model_to_slug = [];
    $terms = get_terms(['taxonomy' => 'vehicles', 'hide_empty' => false]);
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $model = get_term_meta($term->term_id, 'vehicle_model', true);
            if ($model) {
                $upper = strtoupper($model);
                if (isset($upper_to_slug[$upper])) {
                    $model_to_slug[$model] = $upper_to_slug[$upper];
                }
            }
        }
    }

    $home_url = home_url('/');
    $ajax_url = admin_url('admin-ajax.php');
    ?>
    <style>
    /* ── Vehicle filter bar: single-row layout ── */
    form.product-vehicle-filter.horizontal {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 10px 16px !important;
        position: relative;
    }
    form.product-vehicle-filter.horizontal > .atts {
        display: flex !important;
        width: auto !important;
        flex-shrink: 0;
        gap: 10px;
        align-items: center;
    }
    form.product-vehicle-filter.horizontal > .atts > .vf-item {
        min-width: 0;
        flex-shrink: 1;
    }
    form.product-vehicle-filter.horizontal > .atts > .vf-item.model {
        min-width: 208px;
    }
    form.product-vehicle-filter.horizontal > .atts > .vf-item.year {
        min-width: 120px;
    }
    form.product-vehicle-filter .select2-container {
        min-width: 100% !important;
    }
    form.product-vehicle-filter.horizontal > .last {
        display: flex !important;
        width: auto !important;
        flex: 1 1 auto !important;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        margin-left: 0 !important;
    }
    /* OR dividers + input containers */
    form.product-vehicle-filter .vin,
    form.product-vehicle-filter .reg {
        display: flex !important;
        align-items: center;
        gap: 10px;
        flex-shrink: 1;
        min-width: 0;
    }
    /* Hide the OR spans inside .vin and .reg — we inject standalone ones instead */
    form.product-vehicle-filter .vin > span,
    form.product-vehicle-filter .reg > span { display: none !important; }
    /* Standalone OR separators */
    form.product-vehicle-filter .vf-or {
        white-space: nowrap;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.85;
        padding: 0 2px;
        flex-shrink: 0;
    }
    /* ── Normalize ALL text inputs to same size ── */
    form.product-vehicle-filter .vin input.vin,
    form.product-vehicle-filter .reg input.reg-input {
        height: 36px !important;
        padding: 0 10px !important;
        font-size: 13px !important;
        font-family: inherit !important;
        border: none !important;
        border-radius: 4px !important;
        outline: none;
        color: #333 !important;
        background: #fff !important;
        box-sizing: border-box !important;
        width: 180px !important;
        min-width: 120px;
        flex-shrink: 1;
    }
    form.product-vehicle-filter .vin input.vin::placeholder,
    form.product-vehicle-filter .reg input.reg-input::placeholder {
        color: #999;
        font-size: 13px;
    }
    form.product-vehicle-filter .reg input.reg-input::placeholder {
        text-transform: none;
    }
    /* ── Normalize Select2 dropdowns to same height ── */
    form.product-vehicle-filter .select2-container .select2-selection--single {
        height: 36px !important;
        line-height: 36px !important;
    }
    form.product-vehicle-filter .select2-container .select2-selection__rendered {
        line-height: 36px !important;
        font-size: 13px !important;
        padding-left: 10px !important;
    }
    form.product-vehicle-filter .select2-container .select2-selection__arrow {
        height: 36px !important;
    }
    /* ── Search button: compact ── */
    form.product-vehicle-filter input[type="submit"] {
        height: 36px !important;
        padding: 0 16px !important;
        font-size: 13px !important;
        line-height: 36px !important;
        white-space: nowrap;
        flex-shrink: 0;
        flex-grow: 0 !important;
        width: auto !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
    }
    /* ── Reg result popup ── */
    form.product-vehicle-filter .reg-result {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 100;
        font-size: 12px;
        margin-top: 4px;
        padding: 8px 12px;
        border-radius: 4px;
        display: none;
        min-width: 260px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    form.product-vehicle-filter .reg-result.show { display: block; }
    form.product-vehicle-filter .reg-result.success { background: rgba(255,255,255,0.95); color: #333; }
    form.product-vehicle-filter .reg-result.error { background: rgba(0,0,0,0.15); color: #fff; }
    form.product-vehicle-filter .reg-result a { color: #BF3617; font-weight: 700; text-decoration: underline; }
    /* Spinning tyre loader */
    @keyframes mvs-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .mvs-loader { display: inline-flex; align-items: center; gap: 8px; }
    .mvs-loader svg { animation: mvs-spin 1s linear infinite; flex-shrink: 0; }
    /* Hide the reset link since it takes space */
    form.product-vehicle-filter > .reset { display: none; }
    </style>
    <script>
    (function(){
        var maxusModelToSlug = <?php echo json_encode($model_to_slug); ?>;
        var maxusHomeUrl = <?php echo json_encode($home_url); ?>;
        var maxusAjaxUrl = <?php echo json_encode($ajax_url); ?>;
        var mvsLoaderHtml = '<span class="mvs-loader"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" stroke-dasharray="32" stroke-dashoffset="16" stroke-linecap="round"/><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5"/><line x1="12" y1="2" x2="12" y2="5" stroke="currentColor" stroke-width="1.5"/><line x1="12" y1="19" x2="12" y2="22" stroke="currentColor" stroke-width="1.5"/><line x1="2" y1="12" x2="5" y2="12" stroke="currentColor" stroke-width="1.5"/><line x1="19" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.5"/></svg> Looking up vehicle...</span>';

        // Intercept submit to redirect to vehicle landing pages
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('input[type="submit"]');
            if (!btn) return;

            var form = btn.closest('form.product-vehicle-filter');
            if (!form) return;

            // Check registration input first
            var regInput = form.querySelector('input.reg-input');
            if (regInput && regInput.value.trim()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                doBarRegSearch(form, regInput);
                return;
            }

            var modelSelect = form.querySelector('select[name="model"]');
            var vinInput = form.querySelector('input.vin');
            var modelVal = modelSelect ? modelSelect.value : '';
            var vinVal = vinInput ? vinInput.value.trim() : '';

            // Check VIN input - redirect via AJAX lookup to vehicle landing page
            if (vinVal && vinVal.length === 17) {
                e.preventDefault();
                e.stopImmediatePropagation();
                doBarVinSearch(form, vinInput);
                return;
            }

            if (modelVal && !vinVal && maxusModelToSlug[modelVal]) {
                e.preventDefault();
                e.stopImmediatePropagation();
                window.location.href = maxusHomeUrl + maxusModelToSlug[modelVal] + '/';
            }
        }, true);

        function doBarVinSearch(form, vinInput) {
            var vin = vinInput.value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
            var resultEl = form.querySelector('.reg-result');
            if (!resultEl) {
                // Create a result element if reg-result doesn't exist
                resultEl = document.createElement('div');
                resultEl.className = 'reg-result';
                form.appendChild(resultEl);
            }

            if (vin.length !== 17) {
                resultEl.className = 'reg-result show error';
                resultEl.textContent = 'VIN must be 17 characters (' + vin.length + ' entered)';
                return;
            }
            resultEl.className = 'reg-result show';
            resultEl.innerHTML = mvsLoaderHtml;

            var fd = new FormData();
            fd.append('action', 'maxus_vin_lookup');
            fd.append('vin', vin);

            fetch(maxusAjaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.data.shop_url) {
                        window.location.href = data.data.shop_url;
                    } else {
                        resultEl.className = 'reg-result show error';
                        resultEl.textContent = (data.data && data.data.error) || 'No match found for this VIN';
                    }
                })
                .catch(function() {
                    resultEl.className = 'reg-result show error';
                    resultEl.textContent = 'An error occurred. Please try again.';
                });
        }

        function doBarRegSearch(form, regInput) {
            var reg = regInput.value.trim().replace(/\s+/g, '');
            var resultEl = form.querySelector('.reg-result');
            if (!resultEl) return;

            if (reg.length < 2) {
                resultEl.className = 'reg-result show error';
                resultEl.textContent = 'Please enter a valid registration number';
                return;
            }
            resultEl.className = 'reg-result show';
            resultEl.innerHTML = mvsLoaderHtml;

            var fd = new FormData();
            fd.append('action', 'maxus_reg_lookup');
            fd.append('reg', reg);

            fetch(maxusAjaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.data.shop_url) {
                        resultEl.className = 'reg-result show success';
                        resultEl.innerHTML = '<strong>' + data.data.vehicle_name +
                            ' (' + data.data.customer_year + ')</strong> &mdash; Redirecting...';
                        window.location.href = data.data.shop_url;
                    } else {
                        resultEl.className = 'reg-result show error';
                        resultEl.textContent = data.data.error || 'No match found';
                    }
                })
                .catch(function() {
                    resultEl.className = 'reg-result show error';
                    resultEl.textContent = 'An error occurred. Please try again.';
                });
        }

        // Inject OR separators and registration search field into vehicle filter forms
        function injectRegField() {
            document.querySelectorAll('form.product-vehicle-filter').forEach(function(form) {
                if (form.querySelector('.reg')) return; // already injected
                var lastDiv = form.querySelector('.last');
                if (!lastDiv) return;
                var submitBtn = lastDiv.querySelector('input[type="submit"]');
                var vinDiv = lastDiv.querySelector('.vin');

                // Insert standalone OR between .atts and .last (between Year and VIN)
                var or1 = document.createElement('span');
                or1.className = 'vf-or';
                or1.textContent = 'OR';
                form.insertBefore(or1, lastDiv);

                // Insert standalone OR between VIN and REG
                var or2 = document.createElement('span');
                or2.className = 'vf-or';
                or2.textContent = 'OR';

                var regDiv = document.createElement('div');
                regDiv.className = 'reg';
                regDiv.innerHTML = '<input type="text" class="reg-input" value="" placeholder="Search by Registration" maxlength="10" autocomplete="off">';

                var regResult = document.createElement('div');
                regResult.className = 'reg-result';

                // Insert OR then REG before submit button
                if (submitBtn) {
                    if (vinDiv) vinDiv.after(or2);
                    or2.after(regDiv);
                } else {
                    lastDiv.appendChild(or2);
                    lastDiv.appendChild(regDiv);
                }

                // Result popup on the form itself (absolutely positioned)
                form.appendChild(regResult);

                // Enter key on reg input triggers search
                regDiv.querySelector('.reg-input').addEventListener('keydown', function(ev) {
                    if (ev.key === 'Enter') {
                        ev.preventDefault();
                        doBarRegSearch(form, this);
                    }
                });

                // Clear reg when model/year changes
                form.querySelectorAll('select').forEach(function(sel) {
                    sel.addEventListener('change', function() {
                        var ri = form.querySelector('.reg-input');
                        var rr = form.querySelector('.reg-result');
                        if (ri) ri.value = '';
                        if (rr) { rr.className = 'reg-result'; rr.innerHTML = ''; }
                    });
                });
            });
        }

        // Fix Select2 dropdowns opening upward by changing dropdownParent to body.
        // The plugin sets dropdownParent to .vf-item which is too small, causing flip.
        function fixVehicleSelect2() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
            jQuery('form.product-vehicle-filter select').each(function() {
                var $sel = jQuery(this);
                if ($sel.data('select2')) {
                    var val = $sel.val();
                    var disabled = $sel.prop('disabled');
                    var opts = $sel.find('option').not('.default').clone();
                    $sel.select2('destroy');
                    $sel.select2({
                        dropdownAutoWidth: true,
                        dropdownParent: jQuery(document.body)
                    });
                    if (val) $sel.val(val).trigger('change.select2');
                    if (disabled) $sel.prop('disabled', true);
                }
            });
        }

        // Run after plugin's AJAX completes and Select2 is initialized
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.data && typeof settings.data === 'string' && settings.data.indexOf('fetch_vehicle_list') !== -1) {
                    setTimeout(fixVehicleSelect2, 300);
                }
            });
        }
        setTimeout(function() { fixVehicleSelect2(); injectRegField(); }, 1500);
    })();
    </script>
    <?php
}

/**
 * Get valid category structure for a vehicle — built dynamically from the DB.
 * Results are cached in a WordPress transient per vehicle (12 hours).
 * Returns: [ parent_slug => [ child_slug, ... ], ... ]
 */
function maxus_get_vehicle_categories($vehicle_slug) {
    $cache_key = 'maxus_vcats_' . md5($vehicle_slug);
    $cached    = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $vehicles = maxus_get_vehicle_vins();
    if (!isset($vehicles[$vehicle_slug])) {
        return [];
    }
    $vin = $vehicles[$vehicle_slug]['vin'];

    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT
             t_parent.slug AS parent_slug,
             t_child.slug  AS child_slug
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm
             ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value != ''
         INNER JOIN {$wpdb->prefix}sku_vin_mapping svm
             ON pm.meta_value = svm.sku AND svm.vin = %s
         INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
         INNER JOIN {$wpdb->term_taxonomy} tt
             ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat'
         INNER JOIN {$wpdb->terms} t_child ON tt.term_id = t_child.term_id
         INNER JOIN {$wpdb->term_taxonomy} tt_parent ON tt.parent = tt_parent.term_id
         INNER JOIN {$wpdb->terms} t_parent ON tt_parent.term_id = t_parent.term_id
         WHERE p.post_type IN ('product', 'product_variation')
           AND p.post_status = 'publish'
         ORDER BY t_parent.slug, t_child.slug",
        $vin
    ));

    $map = [];
    foreach ($rows as $row) {
        if (!isset($map[$row->parent_slug])) {
            $map[$row->parent_slug] = [];
        }
        $map[$row->parent_slug][] = $row->child_slug;
    }

    set_transient($cache_key, $map, 12 * HOUR_IN_SECONDS);
    return $map;
}


function maxus_is_valid_subcategory($vehicle_slug, $parent_slug, $subcategory_slug) {
    $categories = maxus_get_vehicle_categories($vehicle_slug);
    if (!isset($categories[$parent_slug])) {
        return false;
    }
    return in_array($subcategory_slug, $categories[$parent_slug]);
}

/**
 * Get valid subcategories for a parent category and vehicle
 */
function maxus_get_valid_subcategories($vehicle_slug, $parent_slug) {
    $categories = maxus_get_vehicle_categories($vehicle_slug);
    return isset($categories[$parent_slug]) ? $categories[$parent_slug] : [];
}

/**
 * Register custom query vars
 */
add_filter('query_vars', function($vars) {
    $vars[] = 'maxus_vehicle';
    $vars[] = 'maxus_category';
    $vars[] = 'maxus_subcategory';
    $vars[] = 'maxus_product';
    return $vars;
});

/**
 * Register rewrite rules for vehicle pages
 */
add_action('init', 'maxus_vehicle_rewrite_rules');
function maxus_vehicle_rewrite_rules() {
    $vehicles = array_keys(maxus_get_vehicle_vins());

    foreach ($vehicles as $vehicle_slug) {
        // Vehicle landing page: /vehicle-slug/
        add_rewrite_rule(
            '^' . $vehicle_slug . '/?$',
            'index.php?maxus_vehicle=' . $vehicle_slug,
            'top'
        );

        // Vehicle + product: /vehicle-slug/product/product-slug/ (must be before subcategory rule)
        add_rewrite_rule(
            '^' . $vehicle_slug . '/product/([^/]+)/?$',
            'index.php?maxus_vehicle=' . $vehicle_slug . '&maxus_product=$matches[1]',
            'top'
        );

        // Vehicle + category: /vehicle-slug/category/
        add_rewrite_rule(
            '^' . $vehicle_slug . '/([^/]+)/?$',
            'index.php?maxus_vehicle=' . $vehicle_slug . '&maxus_category=$matches[1]',
            'top'
        );

        // Vehicle + category + subcategory: /vehicle-slug/category/subcategory/
        add_rewrite_rule(
            '^' . $vehicle_slug . '/([^/]+)/([^/]+)/?$',
            'index.php?maxus_vehicle=' . $vehicle_slug . '&maxus_category=$matches[1]&maxus_subcategory=$matches[2]',
            'top'
        );
    }
}

/**
 * Load custom templates for vehicle pages
 */
add_filter('template_include', 'maxus_vehicle_templates');
function maxus_vehicle_templates($template) {
    $vehicle_slug = get_query_var('maxus_vehicle');

    if (!$vehicle_slug) {
        return $template;
    }

    $vehicle = maxus_get_vehicle_by_slug($vehicle_slug);
    if (!$vehicle) {
        return $template;
    }

    // Store vehicle info globally for templates
    global $maxus_current_vehicle;
    $maxus_current_vehicle = $vehicle;
    $maxus_current_vehicle['slug'] = $vehicle_slug;

    $product_slug = get_query_var('maxus_product');
    $subcategory = get_query_var('maxus_subcategory');
    $category = get_query_var('maxus_category');

    // Product page
    if ($product_slug) {
        $custom = get_stylesheet_directory() . '/vehicle-product.php';
        if (file_exists($custom)) return $custom;
    }
    // Subcategory page (diagram)
    elseif ($subcategory) {
        $custom = get_stylesheet_directory() . '/vehicle-subcategory.php';
        if (file_exists($custom)) return $custom;
    }
    // Category page
    elseif ($category) {
        $custom = get_stylesheet_directory() . '/vehicle-category.php';
        if (file_exists($custom)) return $custom;
    }
    // Vehicle landing page
    else {
        $custom = get_stylesheet_directory() . '/vehicle-landing.php';
        if (file_exists($custom)) return $custom;
    }

    return $template;
}

/**
 * Custom REST API endpoint to find products NOT in a specific category
 */
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/products-not-in-category', array(
        'methods' => 'GET',
        'callback' => 'get_products_not_in_category',
        'permission_callback' => function() {
            return current_user_can('edit_products');
        }
    ));
});

function get_products_not_in_category($request) {
    $exclude_category = $request->get_param('exclude_category');
    $page = $request->get_param('page') ?: 1;
    $per_page = 100;

    if (!$exclude_category) {
        return new WP_Error('missing_param', 'exclude_category parameter required', array('status' => 400));
    }

    global $wpdb;

    // Find all product IDs that DO NOT have the specified category
    // This excludes products that have this category in their term relationships
    $offset = ($page - 1) * $per_page;

    $query = "
        SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND p.ID NOT IN (
            SELECT object_id
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.term_id = %d
            AND tt.taxonomy = 'product_cat'
        )
        ORDER BY p.ID
        LIMIT %d OFFSET %d
    ";

    $product_ids = $wpdb->get_col($wpdb->prepare($query, $exclude_category, $per_page, $offset));

    // Get total count
    $count_query = "
        SELECT COUNT(DISTINCT p.ID)
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND p.ID NOT IN (
            SELECT object_id
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.term_id = %d
            AND tt.taxonomy = 'product_cat'
        )
    ";

    $total = $wpdb->get_var($wpdb->prepare($count_query, $exclude_category));
    $total_pages = ceil($total / $per_page);

    return array(
        'ids' => array_map('intval', $product_ids),
        'total' => (int)$total,
        'page' => (int)$page,
        'per_page' => $per_page,
        'total_pages' => (int)$total_pages
    );
}

/**
 * Check if we should show subcategories instead of products
 */
function maxus_should_show_subcategories_only() {
    // Only on main shop page with vehicle filter
    if (!is_shop() || is_product_category()) {
        return false;
    }

    $model = isset($_GET['model']) ? sanitize_text_field($_GET['model']) : '';
    return !empty($model);
}

/**
 * Helper function to find vehicle term and product category from URL params
 */
function maxus_get_vehicle_product_category() {
    $model = isset($_GET['model']) ? sanitize_text_field($_GET['model']) : '';
    $year = isset($_GET['yr']) ? sanitize_text_field($_GET['yr']) : '';

    if (empty($model)) {
        return null;
    }

    // Get all vehicles matching the model
    $vehicle_terms = get_terms([
        'taxonomy' => 'vehicles',
        'hide_empty' => false,
        'meta_query' => [
            [
                'key' => 'vehicle_model',
                'value' => $model,
                'compare' => '='
            ]
        ]
    ]);

    if (empty($vehicle_terms) || is_wp_error($vehicle_terms)) {
        return null;
    }

    // Find the best matching vehicle term
    $vehicle = null;
    foreach ($vehicle_terms as $v) {
        $v_year = get_term_meta($v->term_id, 'vehicle_year', true);

        // If no year filter, use first match
        if (empty($year)) {
            $vehicle = $v;
            break;
        }

        // Exact year match
        if ($v_year === $year) {
            $vehicle = $v;
            break;
        }

        // Check if year is within a range (e.g., "2021-2023" contains "2021")
        if (strpos($v_year, '-') !== false) {
            list($start, $end) = explode('-', $v_year);
            if (intval($year) >= intval($start) && intval($year) <= intval($end)) {
                $vehicle = $v;
                break;
            }
        }

        // Check if requested year starts with vehicle year (partial match)
        if (strpos($v_year, $year) === 0 || strpos($year, $v_year) === 0) {
            $vehicle = $v;
            break;
        }
    }

    // If still no match, use first result
    if (!$vehicle && !empty($vehicle_terms)) {
        $vehicle = $vehicle_terms[0];
    }

    if (!$vehicle) {
        return null;
    }

    // Get the linked product category from vehicle term meta
    $product_cat_id = get_term_meta($vehicle->term_id, 'vehicle_category', true);

    if (empty($product_cat_id)) {
        return null;
    }

    return [
        'vehicle' => $vehicle,
        'product_cat_id' => $product_cat_id
    ];
}

/**
 * Redirect to product category if no subcategories exist (runs early before output)
 */
add_action('template_redirect', 'maxus_vehicle_redirect_if_no_subcategories');
function maxus_vehicle_redirect_if_no_subcategories() {
    // Ensure WooCommerce is loaded
    if (!function_exists('is_shop')) {
        return;
    }

    // Only on main shop page, NOT on category pages
    if (!is_shop() || is_product_category()) {
        return;
    }

    $data = maxus_get_vehicle_product_category();
    if (!$data) {
        return;
    }

    $product_cat_id = $data['product_cat_id'];

    // Get subcategories of this product category
    $subcategories = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => $product_cat_id,
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    // If has subcategories, don't redirect - the shop page will show the grid
    if (!empty($subcategories) && !is_wp_error($subcategories)) {
        return;
    }

    // No subcategories - redirect to the product category page
    $cat_term = get_term($product_cat_id, 'product_cat');
    if ($cat_term && !is_wp_error($cat_term)) {
        $cat_link = get_term_link($cat_term);
        if (!is_wp_error($cat_link)) {
            wp_redirect($cat_link);
            exit;
        }
    }
}

/**
 * Hide products when showing subcategories on shop page with vehicle filter
 */
add_action('woocommerce_before_shop_loop', 'maxus_hide_products_show_categories_only', 5);
function maxus_hide_products_show_categories_only() {
    if (maxus_should_show_subcategories_only()) {
        // Add CSS to hide the products loop
        echo '<style>
            #loop-products,
            .woocommerce-pagination,
            .woocommerce-result-count,
            .woocommerce-ordering,
            .sale-products,
            .woocommerce-before-shop-loop {
                display: none !important;
            }
        </style>';
    }
}

/**
 * Override "no products found" with subcategory grid when vehicle filter is active
 */
add_action('woocommerce_no_products_found', 'maxus_show_subcategories_instead_of_no_products', 5);
function maxus_show_subcategories_instead_of_no_products() {
    // Only on main shop page with vehicle filter
    if (!is_shop() || is_product_category()) {
        return;
    }

    $data = maxus_get_vehicle_product_category();
    if (!$data) {
        return;
    }

    // Hide the "no products found" message
    echo '<style>.woocommerce-info { display: none !important; }</style>';

    // Display the subcategory grid
    maxus_display_subcategory_grid($data['product_cat_id']);
}

/**
 * Display the subcategory grid for a product category
 */
function maxus_display_subcategory_grid($product_cat_id) {
    $subcategories = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => $product_cat_id,
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (empty($subcategories) || is_wp_error($subcategories)) {
        // No subcategories - show link to category page
        $cat_term = get_term($product_cat_id, 'product_cat');
        if ($cat_term && !is_wp_error($cat_term)) {
            $cat_link = get_term_link($cat_term);
            echo '<div class="maxus-vehicle-subcategories">';
            echo '<p>View all ' . esc_html($cat_term->count) . ' products for this vehicle:</p>';
            echo '<a href="' . esc_url($cat_link) . '" class="button">Browse All Parts</a>';
            echo '</div>';
        }
        return;
    }

    // Get the category name for the heading
    $cat_term = get_term($product_cat_id, 'product_cat');
    $heading = $cat_term ? 'Browse Parts for ' . esc_html($cat_term->name) : 'Browse Parts by Category';

    echo '<div class="maxus-vehicle-subcategories">';
    echo '<h3>' . $heading . '</h3>';
    echo '<div class="subcategory-grid">';

    foreach ($subcategories as $subcat) {
        $cat_link = get_term_link($subcat);

        // Get thumbnail if exists
        $thumbnail_id = get_term_meta($subcat->term_id, 'thumbnail_id', true);
        $image = '';
        if ($thumbnail_id) {
            $image = wp_get_attachment_image($thumbnail_id, 'thumbnail');
            // Fallback to direct URL if wp_get_attachment_image returns empty (missing metadata)
            if (empty($image)) {
                $image_url = wp_get_attachment_url($thumbnail_id);
                if ($image_url) {
                    $image = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($subcat->name) . '" />';
                }
            }
        }

        echo '<a href="' . esc_url($cat_link) . '" class="subcategory-item">';
        if ($image) {
            echo '<div class="subcat-image">' . $image . '</div>';
        }
        echo '<span class="subcat-name">' . esc_html($subcat->name) . '</span>';
        echo '<span class="subcat-count">' . esc_html($subcat->count) . ' parts</span>';
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Show product subcategories first when vehicle filter is active
 */
add_action('woocommerce_before_shop_loop', 'maxus_show_vehicle_subcategories', 50);
function maxus_show_vehicle_subcategories() {
    // Only show on main shop page, NOT on category pages
    if (!is_shop() || is_product_category()) {
        return;
    }

    // Use the helper function to get vehicle data
    $data = maxus_get_vehicle_product_category();
    if (!$data) {
        return;
    }

    // Display the subcategory grid using shared function
    maxus_display_subcategory_grid($data['product_cat_id']);
}

// Add CSS and JS for the subcategory grid
add_action('wp_head', 'maxus_vehicle_subcategories_css');
function maxus_vehicle_subcategories_css() {
    ?>
    <style>
    /* Hide Archives and Categories widgets at bottom of shop pages */
    .shop-bottom-widgets {
        display: none !important;
    }

    /* Hide the old category terms carousel on homepage */
    .elementor-element-25b37be {
        display: none !important;
    }

    /* Container for auto-inserted vehicle carousel */
    .maxus-vehicle-carousel-container {
        width: 100%;
        padding: 0;
        margin-top: -15px;
        background: transparent;
    }

    /* Vehicle Model Carousel Styles - Matching original category carousel */
    .maxus-vehicle-carousel-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 5px 60px;
        position: relative;
    }
    .maxus-vehicle-carousel {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 10px 15px;
        justify-content: flex-start;
    }
    .maxus-vehicle-carousel::-webkit-scrollbar {
        display: none;
    }
    .maxus-vehicle-item {
        flex: 0 0 auto;
        width: 120px;
        text-align: center;
        text-decoration: none;
        color: #333;
        padding: 5px;
        transition: all 0.3s ease;
    }
    .maxus-vehicle-item:hover {
        transform: translateY(-3px);
    }
    .maxus-vehicle-item:hover .vehicle-image {
        border-color: #F29F05;
        box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3);
    }
    .maxus-vehicle-item:hover .vehicle-name {
        color: #F29F05;
    }
    .maxus-vehicle-item .vehicle-image {
        width: 100px;
        height: 100px;
        margin: 0 auto 8px;
        border-radius: 50%;
        overflow: hidden;
        background: #fff;
        border: 3px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    .maxus-vehicle-item .vehicle-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .maxus-vehicle-item .vehicle-image:empty::after {
        content: '';
        width: 50px;
        height: 50px;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ccc"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>') no-repeat center center;
        background-size: contain;
        opacity: 0.5;
    }
    /* Red ring for vehicles with no parts data */
    .maxus-vehicle-item.no-vin-data .vehicle-image {
        border: 3px solid #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.3);
    }
    .maxus-vehicle-item.no-vin-data:hover .vehicle-image {
        border-color: #dc3545;
        box-shadow: 0 0 0 5px rgba(220, 53, 69, 0.4);
    }
    .maxus-vehicle-item.no-vin-data .vehicle-name {
        color: #dc3545;
    }
    .no-data-badge {
        background: #dc3545;
        color: #fff;
        font-size: 9px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-top: 4px;
        display: inline-block;
    }
    .maxus-vehicle-item .vehicle-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 2px;
        color: #333;
        transition: color 0.3s ease;
    }
    .maxus-vehicle-item .vehicle-year {
        font-size: 12px;
        color: #666;
        margin-bottom: 2px;
    }
    .maxus-carousel-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 30px;
        height: 40px;
        background: transparent;
        color: #333;
        border: none;
        cursor: pointer;
        font-size: 24px;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    .maxus-carousel-nav:hover {
        color: #F29F05;
    }
    .maxus-carousel-nav.prev {
        left: 15px;
    }
    .maxus-carousel-nav.next {
        right: 15px;
    }
    @media (max-width: 767px) {
        .maxus-vehicle-carousel-wrapper {
            padding: 5px 40px;
        }
        .maxus-vehicle-carousel {
            gap: 10px;
            justify-content: flex-start;
        }
        .maxus-vehicle-item {
            width: 90px;
        }
        .maxus-vehicle-item .vehicle-image {
            width: 70px;
            height: 70px;
        }
        .maxus-vehicle-item .vehicle-name {
            font-size: 12px;
        }
        .maxus-carousel-nav {
            width: 24px;
            height: 30px;
            font-size: 20px;
        }
        .maxus-carousel-nav.prev {
            left: 10px;
        }
        .maxus-carousel-nav.next {
            right: 10px;
        }
    }

    .maxus-vehicle-subcategories {
        margin-bottom: 30px;
        padding: 20px;
        background: #f5f5f5;
        border-radius: 8px;
    }
    .maxus-vehicle-subcategories h3 {
        margin: 0 0 20px 0;
        font-size: 1.4em;
        color: #333;
    }
    .subcategory-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
    }
    .subcategory-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        text-decoration: none;
        color: #333;
        transition: all 0.2s ease;
    }
    .subcategory-item:hover {
        border-color: #F29F05;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .subcat-image {
        margin-bottom: 10px;
        background: #fff;
        padding: 10px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 120px;
    }
    .subcat-image img {
        width: 120px !important;
        height: 120px !important;
        max-width: 100% !important;
        object-fit: contain;
        mix-blend-mode: multiply;
        background: transparent;
    }
    .subcat-name {
        font-weight: 600;
        text-align: center;
        margin-bottom: 5px;
    }
    .subcat-count {
        font-size: 0.85em;
        color: #666;
    }
    @media (max-width: 767px) {
        .subcategory-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .subcategory-item {
            padding: 10px;
        }
    }
    </style>
    <?php
}

/**
 * Vehicle Model Carousel Shortcode
 * Displays all vehicle models in a scrollable carousel
 * Usage: [maxus_vehicle_carousel]
 */
add_shortcode('maxus_vehicle_carousel', 'maxus_vehicle_carousel_shortcode');
function maxus_vehicle_carousel_shortcode($atts) {
    // Get all vehicle terms
    $vehicles = get_terms([
        'taxonomy' => 'vehicles',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (empty($vehicles) || is_wp_error($vehicles)) {
        return '';
    }

    // Group vehicles by model first
    $by_model = [];
    foreach ($vehicles as $vehicle) {
        $model = get_term_meta($vehicle->term_id, 'vehicle_model', true);
        $year = get_term_meta($vehicle->term_id, 'vehicle_year', true);
        $product_cat_id = get_term_meta($vehicle->term_id, 'vehicle_category', true);

        if (empty($model) || empty($year)) continue;

        // Get vehicle image from term meta or category thumbnail
        $image_id = get_term_meta($vehicle->term_id, 'thumbnail_id', true);
        if (!$image_id && $product_cat_id) {
            $image_id = get_term_meta($product_cat_id, 'thumbnail_id', true);
        }

        if (!isset($by_model[$model])) {
            $by_model[$model] = [
                'years' => [],
                'image_id' => $image_id,
            ];
        }

        $by_model[$model]['years'][$year] = [
            'year' => $year,
            'term_id' => $vehicle->term_id,
            'no_vin_data' => get_term_meta($vehicle->term_id, 'no_vin_data', true) ? true : false,
        ];

        // Use first available image
        if (!$by_model[$model]['image_id'] && $image_id) {
            $by_model[$model]['image_id'] = $image_id;
        }
    }

    if (empty($by_model)) {
        return '';
    }

    // Process each model - use year directly from meta (already may contain ranges like "2021-2023")
    $models = [];
    foreach ($by_model as $model_name => $model_data) {
        $years = array_keys($model_data['years']);

        // Each unique year string becomes a carousel entry
        foreach ($years as $year_str) {
            // Parse the year for sorting - handle ranges like "2021-2023"
            if (strpos($year_str, '-') !== false) {
                $parts = explode('-', $year_str);
                $year_start = intval($parts[0]);
            } else {
                $year_start = intval($year_str);
            }

            $models[] = [
                'model' => $model_name,
                'year_display' => $year_str, // Use the original year string (preserves "2021-2023")
                'year_start' => $year_start,
                'image_id' => $model_data['image_id'],
                'no_vin_data' => $model_data['years'][$year_str]['no_vin_data'] ?? false,
            ];
        }
    }

    // Sort by model name then year
    usort($models, function($a, $b) {
        $cmp = strcmp($a['model'], $b['model']);
        if ($cmp !== 0) return $cmp;
        return $a['year_start'] - $b['year_start'];
    });

    // Build name-to-slug lookup for vehicle landing pages
    $vehicle_vins = maxus_get_vehicle_vins();
    $name_to_slug = [];
    foreach ($vehicle_vins as $v_slug => $v_data) {
        $name_to_slug[strtoupper($v_data['name'])] = $v_slug;
        // Also map without "MAXUS " prefix (taxonomy stores model without it)
        $stripped = preg_replace('/^MAXUS\s+/i', '', $v_data['name']);
        $name_to_slug[strtoupper($stripped)] = $v_slug;
    }

    ob_start();
    ?>
    <div class="maxus-vehicle-carousel-wrapper">
        <button class="maxus-carousel-nav prev" onclick="maxusScrollCarousel(-1)">&#8249;</button>
        <div class="maxus-vehicle-carousel" id="maxus-vehicle-carousel">
            <?php foreach ($models as $vehicle):
                // Link to vehicle landing page
                $model_upper = strtoupper($vehicle['model']);
                if (isset($name_to_slug[$model_upper])) {
                    $link = home_url('/' . $name_to_slug[$model_upper] . '/');
                } else {
                    // Fallback: try with "maxus-" prefix on sanitized title
                    $try_slug = 'maxus-' . sanitize_title($vehicle['model']);
                    if (isset($vehicle_vins[$try_slug])) {
                        $link = home_url('/' . $try_slug . '/');
                    } else {
                        // Try without prefix
                        $try_slug = sanitize_title($vehicle['model']);
                        if (isset($vehicle_vins[$try_slug])) {
                            $link = home_url('/' . $try_slug . '/');
                        } else {
                            $link = add_query_arg([
                                'model' => $vehicle['model'],
                                'yr' => $vehicle['year_start']
                            ], home_url('/shop/'));
                        }
                    }
                }

                $image = '';
                if ($vehicle['image_id']) {
                    $image = wp_get_attachment_image($vehicle['image_id'], 'medium');
                }
            ?>
                <a href="<?php echo esc_url($link); ?>" class="maxus-vehicle-item<?php echo $vehicle['no_vin_data'] ? ' no-vin-data' : ''; ?>">
                    <div class="vehicle-image"><?php if ($image) echo $image; ?></div>
                    <div class="vehicle-name"><?php echo esc_html($vehicle['model']); ?></div>
                    <div class="vehicle-year"><?php echo esc_html($vehicle['year_display']); ?></div>
                    <?php if ($vehicle['no_vin_data']): ?>
                        <div class="no-data-badge">No Parts Data</div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <button class="maxus-carousel-nav next" onclick="maxusScrollCarousel(1)">&#8250;</button>
    </div>

    <?php

    return ob_get_clean();
}

/**
 * Auto-insert vehicle carousel on homepage using JavaScript
 */
add_action('wp_footer', 'maxus_insert_vehicle_carousel_on_homepage');
function maxus_insert_vehicle_carousel_on_homepage() {
    if (!is_front_page() && !is_home()) {
        return;
    }

    // Generate carousel HTML
    $carousel_html = do_shortcode('[maxus_vehicle_carousel]');

    // Encode for safe JavaScript insertion
    $encoded_html = json_encode($carousel_html);
    ?>
    <script>
    // Define the carousel scroll function globally (outside innerHTML)
    function maxusScrollCarousel(direction) {
        var carousel = document.getElementById('maxus-vehicle-carousel');
        if (carousel) {
            var scrollAmount = 220; // Width of one item plus gap
            carousel.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Find the hidden category carousel element
        var oldCarousel = document.querySelector('.elementor-element-25b37be');
        if (oldCarousel) {
            // Create new carousel container
            var newCarousel = document.createElement('div');
            newCarousel.className = 'maxus-vehicle-carousel-container';
            newCarousel.innerHTML = <?php echo $encoded_html; ?>;

            // Insert before the old carousel's parent column/section
            var parentColumn = oldCarousel.closest('.elementor-column') || oldCarousel.closest('.elementor-widget-wrap') || oldCarousel.parentNode;
            if (parentColumn && parentColumn.parentNode) {
                parentColumn.parentNode.insertBefore(newCarousel, parentColumn);
            }
        }
    });
    </script>
    <?php
}

// Include related parts functionality
require_once get_stylesheet_directory() . '/related-parts.php';

/**
 * Hide default product meta (categories, callout, qty) and show vehicle compatibility instead
 */
add_action('wp_head', 'maxus_hide_product_meta_css');
function maxus_hide_product_meta_css() {
    if (!is_product()) return;
    ?>
    <style>
    /* Hide WooCommerce product meta (SKU, categories, tags) - shown in our meta info line instead */
    .product_meta,
    .summary .sku {
        display: none !important;
    }
    /* Hide WooCommerce tabs (Description, Additional Info) */
    .woocommerce-tabs {
        display: none !important;
    }
    /* Space out product summary elements to match image height */
    .summary.entry-summary,
    .product-summary {
        display: flex;
        flex-direction: column;
        min-height: 450px;
    }
    .summary.entry-summary .product_title,
    .product-summary .product_title {
        margin-bottom: 20px;
    }
    .summary.entry-summary .price,
    .product-summary .price {
        margin-top: 15px;
        margin-bottom: 25px;
        font-size: 1.8em;
    }
    .summary.entry-summary .cart,
    .product-summary .cart,
    .summary.entry-summary form.cart,
    .product-summary form.cart {
        margin-top: 20px;
        margin-bottom: 30px;
    }
    .summary.entry-summary .product_meta,
    .product-summary .product_meta {
        margin-top: 20px;
        margin-bottom: 20px;
    }
    /* Style for estimated delivery time */
    .maxus-delivery-time {
        margin-top: 15px;
        margin-bottom: 15px;
        padding: 12px 15px;
        background: #fff8e6;
        border-radius: 6px;
        border-left: 4px solid #F29F05;
    }
    .maxus-delivery-time .delivery-label {
        font-weight: 600;
        color: #333;
        margin-right: 8px;
    }
    .maxus-delivery-time .delivery-value {
        color: #F29F05;
        font-weight: 600;
    }
    /* Style for vehicle compatibility - pushed to bottom */
    .maxus-vehicle-compatibility {
        margin-top: auto !important;
        padding: 15px;
        background: #f5f5f5;
        border-radius: 6px;
        border-left: 4px solid #F29F05;
    }
    /* Keep delivery time above compatibility */
    .maxus-delivery-time + .maxus-vehicle-compatibility {
        margin-top: 15px !important;
    }
    .summary.entry-summary .maxus-delivery-time,
    .product-summary .maxus-delivery-time {
        margin-top: auto !important;
    }
    .maxus-vehicle-compatibility h4 {
        margin: 0 0 10px 0;
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
    /* Close gap above description tabs */
    .woocommerce-tabs,
    .single-product .woocommerce-tabs,
    body.single-product .woocommerce-tabs {
        margin-top: 5px !important;
    }
    .single-product .content-product-bottom,
    .content-product-bottom {
        padding-top: 5px !important;
        margin-top: 0 !important;
    }
    .single-product .product-content,
    .product-content {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
    #content-product-bottom {
        padding-top: 5px !important;
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Find and hide elements containing "Callout:" and "Qty:" text
        var summary = document.querySelector('.summary, .entry-summary, .product-summary');
        if (summary) {
            // Check all text nodes and their parent elements
            var walker = document.createTreeWalker(summary, NodeFilter.SHOW_TEXT, null, false);
            var node;
            while (node = walker.nextNode()) {
                if (node.textContent && node.textContent.match(/Callout:\s*\d+/i)) {
                    // Hide the parent element
                    var parent = node.parentElement;
                    if (parent && parent.tagName !== 'BODY' && parent.tagName !== 'DIV') {
                        parent.style.display = 'none';
                    } else if (parent) {
                        // If parent is a div, try to hide just that text by wrapping
                        node.textContent = '';
                    }
                }
            }
        }

        // Close gap above description tabs
        var tabs = document.querySelector('.woocommerce-tabs');
        if (tabs) {
            tabs.style.marginTop = '5px';
            var parent = tabs.parentElement;
            while (parent && parent.tagName !== 'BODY') {
                parent.style.paddingTop = '0';
                parent.style.marginTop = '0';
                if (parent.classList.contains('content-product-bottom') ||
                    parent.id === 'content-product-bottom' ||
                    parent.classList.contains('product-content')) {
                    parent.style.paddingTop = '5px';
                    break;
                }
                parent = parent.parentElement;
            }
        }

        // Match summary height to image section height
        function matchSummaryToImage() {
            var imageSection = document.querySelector('.maxus-product-diagram-image');
            var summary = document.querySelector('.summary.entry-summary, .product-summary, div.summary');
            if (imageSection && summary) {
                var imageHeight = imageSection.offsetHeight;
                if (imageHeight > 100) {
                    summary.style.minHeight = imageHeight + 'px';
                    summary.style.display = 'flex';
                    summary.style.flexDirection = 'column';
                }
            }
        }
        setTimeout(matchSummaryToImage, 100);
        setTimeout(matchSummaryToImage, 500);
        window.addEventListener('resize', matchSummaryToImage);
    });
    </script>
    <?php
}

/**
 * Add Estimated Delivery Time custom field to WooCommerce product admin
 */
add_action('woocommerce_product_options_general_product_data', 'maxus_add_delivery_time_field');
function maxus_add_delivery_time_field() {
    woocommerce_wp_text_input(array(
        'id' => '_estimated_delivery_time',
        'label' => 'Estimated Delivery Time',
        'placeholder' => 'e.g., 3-5 working days',
        'desc_tip' => true,
        'description' => 'Enter the estimated delivery time for this product (e.g., "3-5 working days", "1-2 weeks")'
    ));
}

/**
 * Save Estimated Delivery Time field
 */
add_action('woocommerce_process_product_meta', 'maxus_save_delivery_time_field');
function maxus_save_delivery_time_field($post_id) {
    $delivery_time = isset($_POST['_estimated_delivery_time']) ? sanitize_text_field($_POST['_estimated_delivery_time']) : '';
    update_post_meta($post_id, '_estimated_delivery_time', $delivery_time);
}

/**
 * Display product meta info (SKU, Part No, Weight) on default WooCommerce product page
 * Matches the layout of vehicle-product.php
 */
add_action('woocommerce_single_product_summary', 'maxus_display_product_meta_info', 7);
function maxus_display_product_meta_info() {
    global $product, $wpdb;

    if (!$product) return;
    $product_id = $product->get_id();

    $sku = $product->get_sku();
    $weight = $product->get_weight();
    $weight_unit = get_option('woocommerce_weight_unit', 'kg');

    // For variable products, fall back to first variation's SKU/weight
    if ($product->is_type('variable')) {
        $children = $product->get_children();
        if (!empty($children)) {
            if (!$sku) {
                $sku = get_post_meta($children[0], '_sku', true);
            }
            if (!$weight || $weight <= 0) {
                $weight = get_post_meta($children[0], '_weight', true);
            }
        }
    }

    // Find callout number by checking all product_cat with svg_diagram
    $callout = null;
    $terms = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'all'));
    if (!empty($terms) && !is_wp_error($terms)) {
        usort($terms, function($a, $b) {
            return count(get_ancestors($b->term_id, 'product_cat', 'taxonomy'))
                 - count(get_ancestors($a->term_id, 'product_cat', 'taxonomy'));
        });
        foreach ($terms as $term) {
            if (in_array($term->slug, array('priceupdated', 'imageupdated', 'uncategorized'))) continue;
            $svg = get_term_meta($term->term_id, 'svg_diagram', true);
            if (!$svg) continue;
            $tt_id = $wpdb->get_var($wpdb->prepare(
                "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
                $term->term_id
            ));
            $co = get_post_meta($product_id, 'callout_cat_' . $tt_id, true);
            if ($co) { $callout = $co; break; }
        }
        if (!$callout) {
            $callout = get_post_meta($product_id, 'callout_number', true);
        }
    }

    if (!$sku && !$callout && !$weight) return;
    ?>
    <div class="product-meta-info" style="margin-bottom:15px; font-size:14px; color:#666;">
        <?php if ($sku) : ?>
            <span class="sku-label" style="color:#888;">SKU:</span>
            <span class="sku-value" style="font-family:monospace; color:#333; font-weight:600;"><?php echo esc_html($sku); ?></span>
        <?php endif; ?>
        <?php if ($callout) : ?>
            <?php if ($sku) : ?><span class="meta-separator" style="margin:0 12px; color:#ccc;">|</span><?php endif; ?>
            <span class="callout-label" style="color:#888;">Part No:</span>
            <span class="callout-value" style="font-family:monospace; color:#333; font-weight:600;"><?php echo esc_html($callout); ?></span>
        <?php endif; ?>
        <?php if ($weight && $weight > 0) : ?>
            <?php if ($sku || $callout) : ?><span class="meta-separator" style="margin:0 12px; color:#ccc;">|</span><?php endif; ?>
            <span class="weight-label" style="color:#888;">Weight:</span>
            <span class="weight-value" style="color:#333;"><?php echo esc_html($weight . $weight_unit); ?></span>
        <?php endif; ?>
    </div>
    <?php
}

// Remove Additional Information tab entirely
add_filter('woocommerce_product_tabs', 'maxus_remove_additional_info_tab', 98);
function maxus_remove_additional_info_tab($tabs) {
    unset($tabs['additional_information']);
    return $tabs;
}

// Remove default WooCommerce meta (SKU, categories, tags) and description tabs - we show SKU ourselves
add_action('wp', function() {
    if (is_product()) {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 25);
    }
});

/**
 * Display Estimated Delivery Time on product page
 */
add_action('woocommerce_single_product_summary', 'maxus_display_delivery_time', 44);
function maxus_display_delivery_time() {
    global $product;

    $delivery_time = get_post_meta($product->get_id(), '_estimated_delivery_time', true);

    // Show default if not set
    if (empty($delivery_time)) {
        $delivery_time = '3-5 working days';
    }
    ?>
    <div class="maxus-delivery-time">
        <span class="delivery-label">Estimated Delivery:</span>
        <span class="delivery-value"><?php echo esc_html($delivery_time); ?></span>
    </div>
    <?php
}

/**
 * Display vehicle compatibility instead of categories
 */
add_action('woocommerce_single_product_summary', 'maxus_display_vehicle_compatibility', 45);
function maxus_display_vehicle_compatibility() {
    global $product, $wpdb;

    $product_id = $product->get_id();

    // Collect all SKUs for this product (parent + variations)
    $skus = array();
    $parent_sku = $product->get_sku();
    if ($parent_sku) $skus[] = $parent_sku;

    if ($product->is_type('variable')) {
        $variation_ids = $product->get_children();
        foreach ($variation_ids as $vid) {
            $vsku = get_post_meta($vid, '_sku', true);
            if ($vsku) $skus[] = $vsku;
        }
    }

    if (empty($skus)) return;

    // Look up compatible vehicles from wp_sku_vin_mapping
    $placeholders = implode(',', array_fill(0, count($skus), '%s'));
    $compatible = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT vehicle_name, vehicle_year FROM {$wpdb->prefix}sku_vin_mapping WHERE sku IN ($placeholders) ORDER BY vehicle_name",
        ...$skus
    ));

    if (empty($compatible)) return;

    // Group by vehicle name
    $grouped = array();
    foreach ($compatible as $v) {
        if (!isset($grouped[$v->vehicle_name])) {
            $grouped[$v->vehicle_name] = array();
        }
        if ($v->vehicle_year) {
            $grouped[$v->vehicle_name][] = $v->vehicle_year;
        }
    }

    // Get vehicle slug mapping for links
    $vins_map = maxus_get_vehicle_vins();
    $name_to_slug = array();
    foreach ($vins_map as $slug => $vdata) {
        $name_to_slug[strtoupper($vdata['name'])] = $slug;
    }

    ?>
    <div class="maxus-vehicle-compatibility">
        <h4>Compatible Vehicles:</h4>
        <ul>
            <?php foreach ($grouped as $name => $years):
                $slug = isset($name_to_slug[strtoupper($name)]) ? $name_to_slug[strtoupper($name)] : '';
                $year_str = !empty($years) ? '(' . implode(', ', array_unique($years)) . ')' : '';
            ?>
            <li>
                <?php if ($slug): ?>
                    <a href="<?php echo esc_url(home_url('/' . $slug . '/')); ?>" class="vehicle-name"><?php echo esc_html($name); ?></a>
                <?php else: ?>
                    <span class="vehicle-name"><?php echo esc_html($name); ?></span>
                <?php endif; ?>
                <?php if ($year_str): ?>
                    <span class="vehicle-year"><?php echo esc_html($year_str); ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

/**
 * Filter get_the_terms to exclude utility categories from breadcrumbs
 * This works with the theme's enovathemes_addons_breadcrumbs() function
 */
add_filter('get_the_terms', 'maxus_filter_breadcrumb_terms', 10, 3);
function maxus_filter_breadcrumb_terms($terms, $post_id, $taxonomy) {
    // Only filter product_cat taxonomy on single product pages
    if ($taxonomy !== 'product_cat' || !is_product()) {
        return $terms;
    }

    if (empty($terms) || is_wp_error($terms)) {
        return $terms;
    }

    // Utility categories to exclude
    $exclude_slugs = array('priceupdated', 'imageupdated', 'uncategorized');

    // Filter out utility categories
    $filtered_terms = array_filter($terms, function($term) use ($exclude_slugs) {
        return !in_array($term->slug, $exclude_slugs);
    });

    // If we have filtered terms, sort by depth (deepest first) so breadcrumb picks the best one
    if (!empty($filtered_terms)) {
        usort($filtered_terms, function($a, $b) {
            $depth_a = count(get_ancestors($a->term_id, 'product_cat', 'taxonomy'));
            $depth_b = count(get_ancestors($b->term_id, 'product_cat', 'taxonomy'));
            return $depth_b - $depth_a; // Deepest first
        });
        return array_values($filtered_terms);
    }

    return $terms;
}

// Add CSS for related parts table
add_action('wp_head', 'maxus_related_parts_css');
function maxus_related_parts_css() {
    if (!is_product()) return;
    ?>
    <style>
    .maxus-related-parts {
        margin-top: 15px;
        padding: 25px;
        background: #f9f9f9;
        border-radius: 8px;
    }
    /* Side by side layout */
    .diagram-layout {
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }
    .diagram-table-column {
        flex: 0 0 550px;
        max-width: 550px;
    }
    .diagram-table-column .related-parts-table-wrapper {
        overflow-y: auto;
    }
    .diagram-svg-column {
        flex: 1;
        min-width: 0;
    }
    /* SVG Diagram Display */
    .diagram-svg-wrapper {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
    }
    .diagram-svg-column .diagram-svg-wrapper {
        display: flex;
        flex-direction: column;
    }
    .diagram-svg-column .diagram-svg-full {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .diagram-svg-container {
        max-height: 500px;
        overflow: auto;
        text-align: center;
        background: #fff;
    }
    .diagram-svg-full {
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        cursor: crosshair;
        padding: 10px;
        max-height: 625px;
    }
    .diagram-svg-container svg {
        max-width: 100%;
        height: auto;
        display: inline-block;
    }
    .diagram-svg-full svg {
        max-width: 100%;
        max-height: 600px;
        width: auto;
        height: auto;
        display: block;
        transition: transform 0.1s ease-out;
        transform-origin: center center;
    }
    .diagram-svg-full.zoomed {
        cursor: zoom-out;
        overflow: hidden;
    }
    .diagram-svg-full.zoomed svg {
        transform: scale(2.5);
    }
    @media (max-width: 900px) {
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
        }
    }
    .diagram-callout-indicator {
        margin-top: 15px;
        padding: 10px 15px;
        background: #F29F05;
        color: #fff;
        border-radius: 6px;
        text-align: center;
        font-size: 0.95em;
    }
    .highlight-callout {
        display: inline-block;
        background: #F29F05;
        color: #fff;
        padding: 2px 10px;
        border-radius: 12px;
        font-weight: bold;
        margin-left: 5px;
    }
    .maxus-related-parts h3 {
        margin: 0 0 5px 0;
        font-size: 1.4em;
        color: #333;
    }
    .maxus-related-parts .diagram-name {
        margin: 0 0 20px 0;
        color: #666;
        font-size: 0.95em;
    }
    .related-parts-table-wrapper {
        max-height: 600px;
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
        background: #f5f5f5;
    }
    .related-parts-table .current-part {
        background: #fff8e6 !important;
    }
    .related-parts-table .current-part:hover {
        background: #fff3d1 !important;
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
    .current-part .callout-number {
        background: #333;
    }
    .col-name {
        min-width: 200px;
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
        width: 150px;
        font-family: monospace;
        font-size: 1em;
        color: #666;
    }
    .col-price {
        width: 100px;
        text-align: right;
        font-weight: 600;
    }
    .price-na {
        color: #999;
        font-weight: normal;
    }
    .col-cart {
        width: 70px;
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
    }
    </style>
    <?php
}

// Override mobile header background from blue to orange for consistent branding
add_action('wp_head', 'maxus_mobile_header_orange_override', 99);
function maxus_mobile_header_orange_override() {
    ?>
    <style>
    .elementor-435 .elementor-element.elementor-element-60c0b2d:not(.elementor-motion-effects-element-type-background),
    .elementor-435 .elementor-element.elementor-element-60c0b2d > .elementor-motion-effects-container > .elementor-motion-effects-layer {
        background-color: #F29F05 !important;
    }
    </style>
    <?php
}

// Department-to-category mapping for homepage drill-down
function maxus_get_department_mapping() {
    return [
        'Air conditioning' => ['frontexterior-hvac-airflow', 'frontinterior-hvac-airflow', 'rearinterior-hvac-airflow', 'alternative-hvac', 'hvac-powertrain-coolingin'],
        'Belts, rollers'   => ['power-generation'],
        'Body'             => ['body-lower-exterior-trim', 'body-lower-structure', 'body-upper-exterior-trim', 'body-upper-structure', 'body-window', 'bumpersfascia-grille', 'front-closures-front-end-sheetmetal', 'side-closures', 'rear-closure', 'sealant-body-attachment'],
        'Brakes'           => ['brakes'],
        'Damping'          => ['mounts'],
        'Electrics'        => ['power-signaldistribution', 'customer-switches', 'charging-energystorage', 'body-interior-exterior-electronics'],
        'Engine'           => ['power-generation', 'powertrain-control-diagnostic', 'powertrain-driver-interface', 'emission-exhaust-system', 'fuel-storage-handling'],
        'Filters'          => ['air-intake-system'],
        'Induction'        => ['air-intake-system'],
        'Ignition'         => ['powertrain-control-diagnostic'],
        'Interior'         => ['interior-trim', 'interior-lamp', 'instrument-panel-console', 'interior-acoustic-syetem', 'seats', 'entertainment'],
        'Lighting'         => ['front-lamp', 'rear-lamp', 'interior-lamp'],
        'Oils and fluids'  => [],
        'Wiper and washers'=> ['wiper-washer'],
        'Suspension'       => ['suspension'],
        'Tires'            => ['tirewheelswheel-trim'],
        'Steering'         => ['steering'],
        'Transmission'     => ['power-transmission'],
    ];
}

// Vehicle selection grid for homepage department clicks (inline drill-down)
add_action('wp_footer', 'maxus_department_vehicle_grid');
function maxus_department_vehicle_grid() {
    if (!is_front_page()) return;

    global $wpdb;

    $vehicles = maxus_get_vehicle_vins();
    $home_url = home_url('/');
    $dept_mapping = maxus_get_department_mapping();

    // Pre-fetch all parent product_cat terms
    $all_parent_terms = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => 0,
        'hide_empty' => false,
    ]);
    $term_info = [];
    foreach ($all_parent_terms as $t) {
        $thumb_id = get_term_meta($t->term_id, 'thumbnail_id', true);
        $thumb_url = '';
        if ($thumb_id) {
            $img = wp_get_attachment_image_src($thumb_id, 'medium');
            if ($img) $thumb_url = $img[0];
        }
        $term_info[$t->slug] = [
            'name'  => $t->name,
            'thumb' => $thumb_url,
        ];
    }

    // Build vehicle data
    $vehicle_data = [];
    foreach ($vehicles as $slug => $data) {
        $name = preg_replace('/^MAXUS\s+/i', '', $data['name']);
        $name = ucwords(strtolower($name));
        $year = str_replace('Present', date('Y'), $data['year']);

        $vin_slug = strtolower($data['vin']);
        $thumb_url = '';
        $thumb_id = $wpdb->get_var($wpdb->prepare("
            SELECT tm.meta_value FROM {$wpdb->terms} t
            JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = 'thumbnail_id'
            WHERE t.slug = %s AND tt.taxonomy = 'vehicles'
            LIMIT 1
        ", $vin_slug));
        if ($thumb_id) {
            $img = wp_get_attachment_image_src($thumb_id, 'medium');
            if ($img) $thumb_url = $img[0];
        }

        $vehicle_data[$slug] = [
            'name' => $name,
            'year' => $year,
            'img'  => $thumb_url,
        ];
    }

    // Build department drill-down data: dept → vehicles → categories
    $drill_data = [];
    foreach ($dept_mapping as $dept_name => $cat_slugs) {
        if (empty($cat_slugs)) continue;

        $dept_vehicles = [];
        foreach ($vehicles as $v_slug => $v_info) {
            $v_cats = maxus_get_vehicle_categories($v_slug);
            $matching_cats = [];
            foreach ($cat_slugs as $cs) {
                if (isset($v_cats[$cs]) && isset($term_info[$cs])) {
                    $matching_cats[] = [
                        'slug' => $cs,
                        'name' => $term_info[$cs]['name'],
                        'thumb' => $term_info[$cs]['thumb'],
                        'url'  => $home_url . $v_slug . '/' . $cs . '/',
                    ];
                }
            }
            if (!empty($matching_cats)) {
                $dept_vehicles[$v_slug] = $matching_cats;
            }
        }

        if (!empty($dept_vehicles)) {
            $drill_data[$dept_name] = $dept_vehicles;
        }
    }

    $js_data = [
        'vehicles' => $vehicle_data,
        'departments' => $drill_data,
        'homeUrl' => $home_url,
    ];
    ?>

    <style>
    .maxus-vg-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 3px solid #F29F05;
    }
    .maxus-vg-title {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #333;
    }
    .maxus-vg-back {
        background: none;
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 13px;
        color: #555;
        cursor: pointer;
        transition: all 0.2s;
    }
    .maxus-vg-back:hover {
        border-color: #F29F05;
        color: #F29F05;
    }
    .maxus-vg-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
    }
    .maxus-vg-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 16px 12px;
        background: #fafafa;
        border: 2px solid #e8e8e8;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .maxus-vg-card:hover {
        border-color: #F29F05;
        background: #fff;
        box-shadow: 0 4px 16px rgba(242,159,5,0.18);
        transform: translateY(-2px);
    }
    .maxus-vg-card-img {
        width: 100%;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
    }
    .maxus-vg-card-img img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .maxus-vg-card-noimg {
        background: #f0f0f0;
        border-radius: 6px;
    }
    .maxus-vg-card-name {
        font-size: 14px;
        font-weight: 700;
        color: #333;
        margin-bottom: 4px;
        line-height: 1.3;
    }
    .maxus-vg-card:hover .maxus-vg-card-name {
        color: #F29F05;
    }
    .maxus-vg-card-year {
        font-size: 13px;
        color: #777;
        font-weight: 500;
    }
    .maxus-vg-cat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }
    .maxus-vg-cat-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 16px 12px;
        background: #fafafa;
        border: 2px solid #e8e8e8;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .maxus-vg-cat-card:hover {
        border-color: #F29F05;
        background: #fff;
        box-shadow: 0 4px 16px rgba(242,159,5,0.18);
        transform: translateY(-2px);
    }
    .maxus-vg-cat-img {
        width: 100%;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        background: #fff;
        border-radius: 6px;
        padding: 8px;
        box-sizing: border-box;
    }
    .maxus-vg-cat-img img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .maxus-vg-cat-noimg {
        background: #f0f0f0;
    }
    .maxus-vg-cat-name {
        font-size: 14px;
        font-weight: 700;
        color: #333;
        line-height: 1.3;
    }
    .maxus-vg-cat-card:hover .maxus-vg-cat-name {
        color: #F29F05;
    }
    .maxus-vg-breadcrumb {
        font-size: 13px;
        color: #777;
        margin-bottom: 16px;
    }
    .maxus-vg-breadcrumb span {
        color: #333;
        font-weight: 600;
    }
    @media (max-width: 600px) {
        .maxus-vg-grid, .maxus-vg-cat-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .maxus-vg-header {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
    }
    </style>

    <script>
    (function(){
        var rightCol = document.querySelector('[data-id="631db85"]');
        if (!rightCol) return;

        var DATA = <?php echo json_encode($js_data); ?>;
        var originalContent = null;
        var currentDept = null;

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function saveOriginal() {
            if (!originalContent) originalContent = rightCol.innerHTML;
        }

        function restoreOriginal() {
            if (originalContent) {
                rightCol.innerHTML = originalContent;
                originalContent = null;
                currentDept = null;
            }
        }

        function showVehicles(deptName) {
            saveOriginal();
            currentDept = deptName;

            var deptData = DATA.departments[deptName];
            if (!deptData) return;

            var html = '<div class="maxus-vg-header">';
            html += '<h4 class="maxus-vg-title">' + esc(deptName) + ' &mdash; Select Your Vehicle</h4>';
            html += '<button class="maxus-vg-back" data-action="home">&larr; Back</button>';
            html += '</div>';
            html += '<div class="maxus-vg-grid">';

            var vSlugs = Object.keys(deptData).sort(function(a, b) {
                return DATA.vehicles[a].name.localeCompare(DATA.vehicles[b].name);
            });

            for (var i = 0; i < vSlugs.length; i++) {
                var vs = vSlugs[i];
                var v = DATA.vehicles[vs];
                html += '<div class="maxus-vg-card" data-dept="' + esc(deptName) + '" data-vehicle="' + vs + '">';
                if (v.img) {
                    html += '<div class="maxus-vg-card-img"><img src="' + esc(v.img) + '" alt="' + esc(v.name) + '" loading="lazy"></div>';
                } else {
                    html += '<div class="maxus-vg-card-img maxus-vg-card-noimg"></div>';
                }
                html += '<div class="maxus-vg-card-name">' + esc(v.name) + '</div>';
                html += '<div class="maxus-vg-card-year">' + esc(v.year) + '</div>';
                html += '</div>';
            }
            html += '</div>';

            rightCol.innerHTML = html;
            rightCol.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function showCategories(deptName, vehicleSlug) {
            var cats = DATA.departments[deptName][vehicleSlug];
            var v = DATA.vehicles[vehicleSlug];

            // Single category — navigate directly
            if (cats.length === 1) {
                window.location.href = cats[0].url;
                return;
            }

            var html = '<div class="maxus-vg-header">';
            html += '<h4 class="maxus-vg-title">' + esc(deptName) + ' &mdash; ' + esc(v.name) + '</h4>';
            html += '<button class="maxus-vg-back" data-back-dept="' + esc(deptName) + '">&larr; Back to Vehicles</button>';
            html += '</div>';
            html += '<div class="maxus-vg-breadcrumb">' + esc(deptName) + ' &rsaquo; <span>' + esc(v.name) + ' (' + esc(v.year) + ')</span></div>';
            html += '<div class="maxus-vg-cat-grid">';
            for (var i = 0; i < cats.length; i++) {
                var c = cats[i];
                html += '<a href="' + esc(c.url) + '" class="maxus-vg-cat-card">';
                if (c.thumb) {
                    html += '<div class="maxus-vg-cat-img"><img src="' + esc(c.thumb) + '" alt="' + esc(c.name) + '" loading="lazy"></div>';
                } else {
                    html += '<div class="maxus-vg-cat-img maxus-vg-cat-noimg"></div>';
                }
                html += '<div class="maxus-vg-cat-name">' + esc(c.name) + '</div>';
                html += '</a>';
            }
            html += '</div>';

            rightCol.innerHTML = html;
            rightCol.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Single document-level handler for ALL clicks
        document.addEventListener('click', function(e) {
            // 1. Back to home (featured products)
            var backHome = e.target.closest('[data-action="home"]');
            if (backHome) {
                e.preventDefault();
                restoreOriginal();
                return;
            }

            // 2. Back to vehicles list
            var backVehicles = e.target.closest('[data-back-dept]');
            if (backVehicles) {
                e.preventDefault();
                showVehicles(backVehicles.dataset.backDept);
                return;
            }

            // 3. Vehicle card click → show categories
            var vCard = e.target.closest('.maxus-vg-card[data-vehicle]');
            if (vCard) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var dept = vCard.dataset.dept;
                var vehicle = vCard.dataset.vehicle;
                if (dept && vehicle && DATA.departments[dept] && DATA.departments[dept][vehicle]) {
                    showCategories(dept, vehicle);
                }
                return;
            }

            // 4. Sidebar menu department links
            var link = e.target.closest('.sidebar-menu a.mi-link, .sidebar-menu-container a.mi-link');
            if (link) {
                var href = link.getAttribute('href');
                if (!href || href === '#' || href.endsWith('#')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var txt = link.querySelector('.txt');
                    var deptName = txt ? txt.textContent.trim() : link.textContent.trim();
                    if (DATA.departments[deptName]) {
                        showVehicles(deptName);
                    }
                    return;
                }
            }

            // 5. Category carousel links
            var carouselLink = e.target.closest('.terms-item a[href*="?ca="]');
            if (carouselLink) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var carouselTitle = carouselLink.querySelector('.term-title');
                var cName = carouselTitle ? carouselTitle.textContent.trim() : '';
                if (DATA.departments[cName]) {
                    showVehicles(cName);
                }
                return;
            }
        }, true);
    })();
    </script>
    <?php
}

// =============================================================================
// MENU HOVER SEARCH DROPDOWNS (VIN & Registration Lookup)
// =============================================================================

add_action('wp_footer', 'maxus_menu_search_dropdowns');
function maxus_menu_search_dropdowns() {
    $ajax_url = admin_url('admin-ajax.php');
    $vehicles = maxus_get_vehicle_vins();
    $home_url = home_url('/');

    // Build model → slug mapping (same approach as maxus_vehicle_filter_redirect_js)
    $upper_to_slug = [];
    foreach ($vehicles as $slug => $data) {
        $stripped = strtoupper(preg_replace('/^MAXUS\s+/i', '', $data['name']));
        $upper_to_slug[$stripped] = $slug;
    }

    // Build vehicleData: { "MODEL NAME": { years: ["2021-2024"], slug: "vehicle-slug" }, ... }
    $vehicle_data = [];
    $terms = get_terms(['taxonomy' => 'vehicles', 'hide_empty' => false]);
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $model = get_term_meta($term->term_id, 'vehicle_model', true);
            $year  = get_term_meta($term->term_id, 'vehicle_year', true);
            if (!$model) continue;
            $upper = strtoupper($model);
            if (!isset($upper_to_slug[$upper])) continue;
            $slug = $upper_to_slug[$upper];

            if (!isset($vehicle_data[$model])) {
                $vehicle_data[$model] = ['slug' => $slug, 'years' => []];
            }
            if ($year && !in_array($year, $vehicle_data[$model]['years'])) {
                $vehicle_data[$model]['years'][] = $year;
            }
        }
    }

    // Also include vehicles from vins array that may not have taxonomy terms
    foreach ($vehicles as $slug => $v) {
        $name = preg_replace('/^MAXUS\s+/i', '', $v['name']);
        $display = ucwords(strtolower($name));
        if (!isset($vehicle_data[$display])) {
            $found = false;
            foreach ($vehicle_data as $existing_model => $d) {
                if (strtoupper($existing_model) === strtoupper($name)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $vehicle_data[$display] = ['slug' => $slug, 'years' => [$v['year']]];
            }
        }
    }

    ksort($vehicle_data);

    // Group vehicles by model family for the Vehicles dropdown
    $groups = [
        'Deliver 7' => [],
        'Deliver 9' => [],
        'E Deliver' => [],
        'Pickup' => [],
        'Other' => [],
    ];
    foreach ($vehicles as $slug => $v) {
        $name = preg_replace('/^MAXUS\s+/i', '', $v['name']);
        $name = ucwords(strtolower($name));
        $entry = ['slug' => $slug, 'name' => $name, 'year' => $v['year']];

        if (stripos($v['name'], 'E DELIVER') !== false || stripos($v['name'], 'E-DELIVER') !== false) {
            $groups['E Deliver'][] = $entry;
        } elseif (stripos($v['name'], 'DELIVER 7') !== false) {
            $groups['Deliver 7'][] = $entry;
        } elseif (stripos($v['name'], 'DELIVER 9') !== false || stripos($v['name'], 'NEW DELIVER 9') !== false) {
            $groups['Deliver 9'][] = $entry;
        } elseif (stripos($v['name'], 'T60') !== false || stripos($v['name'], 'T90') !== false) {
            $groups['Pickup'][] = $entry;
        } else {
            $groups['Other'][] = $entry;
        }
    }
    $groups = array_filter($groups);

    // Fetch vehicle thumbnails
    global $wpdb;
    $vehicle_thumbs = [];
    foreach ($vehicles as $slug => $v) {
        $vin_slug = strtolower($v['vin']);
        $thumb_id = $wpdb->get_var($wpdb->prepare("
            SELECT tm.meta_value FROM {$wpdb->terms} t
            JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = 'thumbnail_id'
            WHERE t.slug = %s AND tt.taxonomy = 'vehicles' AND tm.meta_value > 0
            LIMIT 1
        ", $vin_slug));
        if ($thumb_id) {
            $img = wp_get_attachment_image_src($thumb_id, 'thumbnail');
            if ($img) $vehicle_thumbs[$slug] = $img[0];
        }
    }

    // Build category data for each vehicle (for flyout submenu)
    $all_cat_slugs = [];
    $vehicle_cat_data = [];
    foreach ($vehicles as $slug => $v) {
        $cats = maxus_get_vehicle_categories($slug);
        $vehicle_cat_data[$slug] = $cats;
        foreach ($cats as $parent_slug => $sub_slugs) {
            $all_cat_slugs[$parent_slug] = true;
        }
    }

    // Batch fetch category names from DB
    $cat_names = [];
    $slug_list = array_keys($all_cat_slugs);
    if (!empty($slug_list)) {
        $placeholders = implode(',', array_fill(0, count($slug_list), '%s'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT t.slug, t.name FROM {$wpdb->terms} t
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
             WHERE tt.taxonomy = 'product_cat' AND t.slug IN ($placeholders)",
            ...$slug_list
        ));
        foreach ($rows as $r) {
            $cat_names[$r->slug] = $r->name;
        }
    }

    // Category groupings for display
    $cat_groups = [
        'Engine & Powertrain' => [
            'air-intake-system', 'emission-exhaust-system', 'fuel-storage-handling',
            'power-generation', 'powertrain-control-diagnostic', 'powertrain-driver-interface',
            'mounts', 'power-transmission',
        ],
        'Electrical & Electronics' => [
            'body-interior-exterior-electronics', 'customer-switches', 'driver-information',
            'entertainment', 'communicate', 'power-signaldistribution',
            'charging-energystorage', 'power-energy-storage-link-wire', 'antenna',
        ],
        'Brakes, Steering & Suspension' => [
            'brakes', 'steering', 'suspension', 'tirewheelswheel-trim',
        ],
        'Body & Exterior' => [
            'body-lower-structure', 'body-upper-structure', 'body-lower-exterior-trim',
            'body-upper-exterior-trim', 'bumpersfascia-grille', 'chassis-structure',
            'body-window', 'sealant-body-attachment',
        ],
        'Doors & Closures' => [
            'front-closures-front-end-sheetmetal', 'rear-closure', 'side-closures',
        ],
        'Interior' => [
            'instrument-panel-console', 'interior-trim', 'interior-lamp',
            'interior-acoustic-syetem', 'seats', 'safety-belt',
        ],
        'Heating & Cooling' => [
            'frontexterior-hvac-airflow', 'frontinterior-hvac-airflow',
            'rearinterior-hvac-airflow', 'hvac-powertrain-coolingin', 'alternative-hvac',
        ],
        'Lighting & Visibility' => [
            'front-lamp', 'rear-lamp', 'rear-view-mirror', 'wiper-washer',
        ],
        'Safety & Other' => [
            'airbag', 'safety-avoidance', 'on-vehicle-attachments-tools', 'pickup-box',
        ],
    ];

    // Build grouped JSON: { vehicle_slug: [ {g: "Group Name", cats: [{s: slug, n: name}]} ] }
    $cat_flyout_data = [];
    foreach ($vehicle_cat_data as $v_slug => $cats) {
        $parent_slugs = array_keys($cats);
        $grouped = [];
        foreach ($cat_groups as $group_name => $group_slugs) {
            $items = [];
            foreach ($group_slugs as $gs) {
                if (in_array($gs, $parent_slugs)) {
                    $pname = isset($cat_names[$gs]) ? $cat_names[$gs] : ucwords(str_replace('-', ' ', $gs));
                    $items[] = ['s' => $gs, 'n' => $pname];
                }
            }
            if (!empty($items)) {
                $grouped[] = ['g' => $group_name, 'cats' => $items];
            }
        }
        // Any categories not in a group
        $all_grouped = [];
        foreach ($cat_groups as $gs) { $all_grouped = array_merge($all_grouped, $gs); }
        $ungrouped = [];
        foreach ($parent_slugs as $ps) {
            if (!in_array($ps, $all_grouped)) {
                $pname = isset($cat_names[$ps]) ? $cat_names[$ps] : ucwords(str_replace('-', ' ', $ps));
                $ungrouped[] = ['s' => $ps, 'n' => $pname];
            }
        }
        if (!empty($ungrouped)) {
            $grouped[] = ['g' => 'Other', 'cats' => $ungrouped];
        }
        if (!empty($grouped)) {
            $cat_flyout_data[$v_slug] = $grouped;
        }
    }
    ?>
    <style>
    /* Hide the original My Vehicle megamenu content */
    #megamenu-1546 { display: none !important; }

    /* Vehicles list dropdown */
    #maxus-dd-vehicles {
        display: none;
        position: fixed;
        background: #F29F05;
        min-width: 320px;
        width: 320px;
        padding: 10px 0;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 999999;
        max-height: 80vh;
        overflow-y: auto;
        box-sizing: border-box;
    }
    #maxus-dd-vehicles.is-open { display: block; }
    #maxus-dd-vehicles::-webkit-scrollbar { width: 6px; }
    #maxus-dd-vehicles::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 3px; }
    #maxus-dd-vehicles .vdd-group { padding: 0; }
    #maxus-dd-vehicles .vdd-group-title {
        color: rgba(255,255,255,0.7);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin: 0;
        padding: 10px 16px 6px;
    }
    #maxus-dd-vehicles .vdd-group + .vdd-group .vdd-group-title {
        border-top: 1px solid rgba(255,255,255,0.2);
        margin-top: 4px;
        padding-top: 12px;
    }
    #maxus-dd-vehicles .vdd-group ul { list-style: none; margin: 0; padding: 0; }
    #maxus-dd-vehicles .vdd-group li { margin: 0; padding: 0; }
    #maxus-dd-vehicles .vdd-group a {
        display: flex;
        align-items: center;
        padding: 8px 16px;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: background 0.15s;
        line-height: 1.3;
    }
    #maxus-dd-vehicles .vdd-group a:hover { background: rgba(255,255,255,0.2); }
    #maxus-dd-vehicles .vdd-thumb {
        width: 36px;
        height: 36px;
        border-radius: 4px;
        object-fit: cover;
        margin-right: 10px;
        flex-shrink: 0;
        background: rgba(255,255,255,0.15);
    }
    #maxus-dd-vehicles .vdd-thumb-placeholder {
        display: inline-block;
        width: 36px;
        height: 36px;
        border-radius: 4px;
        margin-right: 10px;
        flex-shrink: 0;
        background: rgba(255,255,255,0.1);
    }
    #maxus-dd-vehicles .vdd-info {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 0;
    }
    #maxus-dd-vehicles .vdd-name {
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #maxus-dd-vehicles .vdd-year {
        color: rgba(255,255,255,0.6);
        font-size: 11px;
        font-weight: 400;
        margin-top: 1px;
    }

    /* Category flyout (appears right of vehicles list) */
    #maxus-dd-cats {
        display: none;
        position: fixed;
        background: #FBB836;
        padding: 10px 0;
        border-radius: 0 8px 8px 0;
        box-shadow: 4px 8px 24px rgba(0,0,0,0.2);
        z-index: 999998;
        width: 660px;
        max-height: 80vh;
        overflow-y: auto;
        box-sizing: border-box;
    }
    #maxus-dd-cats.is-open { display: block; }
    #maxus-dd-cats::-webkit-scrollbar { width: 6px; }
    #maxus-dd-cats::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 3px; }
    #maxus-dd-cats .cdd-title {
        color: rgba(0,0,0,0.5);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 6px 16px 8px;
        margin: 0;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }
    #maxus-dd-cats .cdd-body { padding: 8px 12px; column-count: 3; column-gap: 12px; }
    #maxus-dd-cats .cdd-group { break-inside: avoid; margin-bottom: 10px; }
    #maxus-dd-cats .cdd-group-title {
        color: rgba(0,0,0,0.45);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin: 0 0 2px 4px;
        padding: 0;
    }
    #maxus-dd-cats ul { list-style: none; margin: 0; padding: 0; }
    #maxus-dd-cats li { margin: 0; padding: 0; }
    #maxus-dd-cats a {
        display: block;
        padding: 5px 8px;
        color: #333;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: background 0.15s;
        line-height: 1.3;
        border-radius: 4px;
    }
    #maxus-dd-cats a:hover { background: rgba(255,255,255,0.4); }

    /* Our custom vehicle selector panel */
    #maxus-vehicle-panel {
        display: none;
        position: fixed;
        background: #F29F05;
        padding: 20px 24px;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 999999;
        min-width: 380px;
        box-sizing: border-box;
    }
    #maxus-vehicle-panel.is-open { display: block; }
    #maxus-vehicle-panel .mvs-label {
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }
    #maxus-vehicle-panel .mvs-row {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
    }
    #maxus-vehicle-panel .mvs-select {
        flex: 1;
        padding: 10px 12px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        color: #333;
        background: #fff;
        height: 42px;
        box-sizing: border-box;
        outline: none;
        cursor: pointer;
        appearance: auto;
    }
    #maxus-vehicle-panel .mvs-select:disabled {
        background: #e8e8e8;
        color: #999;
        cursor: not-allowed;
    }
    #maxus-vehicle-panel .mvs-btn {
        background: #BF3617;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        height: 42px;
        box-sizing: border-box;
        transition: background 0.2s;
    }
    #maxus-vehicle-panel .mvs-btn:hover { background: #a02e13; }
    #maxus-vehicle-panel .mvs-btn:disabled {
        background: #9a7a6a;
        cursor: not-allowed;
    }
    #maxus-vehicle-panel .mvs-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 14px 0;
        color: rgba(255,255,255,0.8);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    #maxus-vehicle-panel .mvs-divider::before,
    #maxus-vehicle-panel .mvs-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(255,255,255,0.35);
    }
    #maxus-vehicle-panel .mvs-vin-label {
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }
    #maxus-vehicle-panel .mvs-vin-row {
        display: flex;
    }
    #maxus-vehicle-panel .mvs-vin-row input {
        flex: 1;
        padding: 10px 14px;
        border: none;
        border-radius: 4px 0 0 4px;
        font-size: 14px;
        outline: none;
        color: #333;
        background: #fff;
        height: 42px;
        box-sizing: border-box;
    }
    #maxus-vehicle-panel .mvs-vin-row input::placeholder { color: #999; }
    #maxus-vehicle-panel .mvs-vin-row button {
        background: #BF3617;
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 0 4px 4px 0;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        height: 42px;
        box-sizing: border-box;
        transition: background 0.2s;
    }
    #maxus-vehicle-panel .mvs-vin-row button:hover { background: #a02e13; }
    #maxus-vehicle-panel .mvs-hint {
        color: rgba(255,255,255,0.85);
        font-size: 11px;
        margin: 6px 0 0 0;
    }
    #maxus-vehicle-panel .mvs-result {
        margin-top: 10px;
        padding: 10px 14px;
        border-radius: 4px;
        font-size: 13px;
        display: none;
    }
    #maxus-vehicle-panel .mvs-result.show { display: block; }
    #maxus-vehicle-panel .mvs-result.success {
        background: rgba(255,255,255,0.95);
        color: #333;
    }
    #maxus-vehicle-panel .mvs-result.error {
        background: rgba(0,0,0,0.15);
        color: #fff;
    }
    #maxus-vehicle-panel .mvs-result a {
        color: #BF3617;
        font-weight: 700;
        text-decoration: underline;
    }
    @keyframes mvs-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .mvs-loader { display: inline-flex; align-items: center; gap: 8px; }
    .mvs-loader svg { animation: mvs-spin 1s linear infinite; flex-shrink: 0; }
    </style>

    <div id="maxus-dd-vehicles">
        <?php foreach ($groups as $group_name => $entries): ?>
            <div class="vdd-group">
                <p class="vdd-group-title"><?php echo esc_html($group_name); ?></p>
                <ul>
                    <?php foreach ($entries as $v):
                        $vthumb = isset($vehicle_thumbs[$v['slug']]) ? $vehicle_thumbs[$v['slug']] : '';
                    ?>
                        <li>
                            <a href="<?php echo esc_url($home_url . $v['slug'] . '/'); ?>" data-vehicle="<?php echo esc_attr($v['slug']); ?>">
                                <?php if ($vthumb): ?>
                                    <img class="vdd-thumb" src="<?php echo esc_url($vthumb); ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="vdd-thumb vdd-thumb-placeholder"></span>
                                <?php endif; ?>
                                <span class="vdd-info">
                                    <span class="vdd-name"><?php echo esc_html($v['name']); ?></span>
                                    <span class="vdd-year"><?php echo esc_html($v['year']); ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="maxus-dd-cats">
        <p class="cdd-title" id="cdd-title">Categories</p>
        <div class="cdd-body" id="cdd-body"></div>
    </div>

    <div id="maxus-vehicle-panel">
        <div class="mvs-section-model">
            <p class="mvs-label">Find parts for your vehicle</p>
            <div class="mvs-row">
                <select id="mvs-model" class="mvs-select">
                    <option value="">Select Model</option>
                </select>
                <select id="mvs-year" class="mvs-select" disabled>
                    <option value="">Select Year</option>
                </select>
                <button type="button" id="mvs-go" class="mvs-btn" disabled>Go</button>
            </div>
        </div>
        <div class="mvs-section-divider-vin"><div class="mvs-divider">or</div></div>
        <div class="mvs-section-vin">
            <p class="mvs-vin-label">Search by VIN</p>
            <div class="mvs-vin-row">
                <input type="text" id="mvs-vin-input" placeholder="Enter 17-character VIN" maxlength="17" autocomplete="off">
                <button type="button" id="mvs-vin-btn">Search</button>
            </div>
            <p class="mvs-hint">VIN is found on your V5C document or driver's side dashboard</p>
            <div class="mvs-result" id="mvs-vin-result"></div>
        </div>
        <div class="mvs-section-divider-reg"><div class="mvs-divider">or</div></div>
        <div class="mvs-section-reg">
            <p class="mvs-vin-label">Search by Registration</p>
            <div class="mvs-vin-row">
                <input type="text" id="mvs-reg-input" placeholder="e.g. AB12 CDE" maxlength="10" autocomplete="off">
                <button type="button" id="mvs-reg-btn">Search</button>
            </div>
            <p class="mvs-hint">UK registration plate number</p>
            <div class="mvs-result" id="mvs-reg-result"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
        var homeUrl = <?php echo json_encode($home_url); ?>;
        var vehicleData = <?php echo json_encode($vehicle_data, JSON_UNESCAPED_UNICODE); ?>;
        var catData = <?php echo json_encode($cat_flyout_data, JSON_UNESCAPED_UNICODE); ?>;
        var mvsLoaderHtml = '<span class="mvs-loader"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" stroke-dasharray="32" stroke-dashoffset="16" stroke-linecap="round"/><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5"/><line x1="12" y1="2" x2="12" y2="5" stroke="currentColor" stroke-width="1.5"/><line x1="12" y1="19" x2="12" y2="22" stroke="currentColor" stroke-width="1.5"/><line x1="2" y1="12" x2="5" y2="12" stroke="currentColor" stroke-width="1.5"/><line x1="19" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.5"/></svg> Looking up vehicle...</span>';

        var panel = document.getElementById('maxus-vehicle-panel');
        var modelSel = document.getElementById('mvs-model');
        var yearSel = document.getElementById('mvs-year');
        var goBtn = document.getElementById('mvs-go');
        var vinInput = document.getElementById('mvs-vin-input');
        var vinBtn = document.getElementById('mvs-vin-btn');
        var vinResult = document.getElementById('mvs-vin-result');

        var closeTimer = null;
        var isOpen = false;

        function cancelClose() { clearTimeout(closeTimer); }
        function scheduleClose() {
            closeTimer = setTimeout(function() {
                panel.classList.remove('is-open');
                isOpen = false;
            }, 250);
        }

        // Populate model dropdown
        Object.keys(vehicleData).forEach(function(model) {
            var opt = document.createElement('option');
            opt.value = model;
            opt.textContent = model;
            modelSel.appendChild(opt);
        });

        // Model change -> populate years
        modelSel.addEventListener('change', function() {
            var model = this.value;
            yearSel.innerHTML = '<option value="">Select Year</option>';
            yearSel.disabled = true;
            goBtn.disabled = true;

            if (!model || !vehicleData[model]) return;

            var years = vehicleData[model].years;
            if (years.length === 0) {
                goBtn.disabled = false;
                return;
            }
            if (years.length === 1) {
                var opt = document.createElement('option');
                opt.value = years[0];
                opt.textContent = years[0];
                opt.selected = true;
                yearSel.appendChild(opt);
                yearSel.disabled = false;
                goBtn.disabled = false;
                return;
            }
            years.forEach(function(y) {
                var opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                yearSel.appendChild(opt);
            });
            yearSel.disabled = false;
        });

        // Year change -> enable Go
        yearSel.addEventListener('change', function() {
            goBtn.disabled = !this.value;
        });

        // Go button -> redirect to vehicle landing page
        goBtn.addEventListener('click', function() {
            var model = modelSel.value;
            if (!model || !vehicleData[model]) return;
            window.location.href = homeUrl + vehicleData[model].slug + '/';
        });

        // VIN search
        vinInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        function doVinSearch() {
            var vin = vinInput.value.trim();
            if (vin.length !== 17) {
                vinResult.className = 'mvs-result show error';
                vinResult.textContent = 'VIN must be 17 characters (' + vin.length + ' entered)';
                return;
            }
            vinResult.className = 'mvs-result show';
            vinResult.innerHTML = mvsLoaderHtml;

            var fd = new FormData();
            fd.append('action', 'maxus_vin_lookup');
            fd.append('vin', vin);

            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.data.shop_url) {
                        vinResult.className = 'mvs-result show success';
                        vinResult.innerHTML = '<strong>' + data.data.vehicle_name +
                            ' (' + data.data.customer_year + ')</strong> &mdash; Redirecting...';
                        window.location.href = data.data.shop_url;
                    } else {
                        vinResult.className = 'mvs-result show error';
                        vinResult.textContent = (data.data && data.data.error) || 'No match found';
                    }
                })
                .catch(function() {
                    vinResult.className = 'mvs-result show error';
                    vinResult.textContent = 'An error occurred. Please try again.';
                });
        }

        vinBtn.addEventListener('click', function(e) { e.preventDefault(); doVinSearch(); });
        vinInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); doVinSearch(); } });

        // Registration search
        var regInput = document.getElementById('mvs-reg-input');
        var regBtn = document.getElementById('mvs-reg-btn');
        var regResult = document.getElementById('mvs-reg-result');

        regInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        function doRegSearch() {
            var reg = regInput.value.trim().replace(/\s+/g, '');
            if (reg.length < 2) {
                regResult.className = 'mvs-result show error';
                regResult.textContent = 'Please enter a valid registration number';
                return;
            }
            regResult.className = 'mvs-result show';
            regResult.innerHTML = mvsLoaderHtml;

            var fd = new FormData();
            fd.append('action', 'maxus_reg_lookup');
            fd.append('reg', reg);

            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.data.shop_url) {
                        regResult.className = 'mvs-result show success';
                        regResult.innerHTML = '<strong>' + data.data.vehicle_name +
                            ' (' + data.data.customer_year + ')</strong> &mdash; Redirecting...';
                        window.location.href = data.data.shop_url;
                    } else {
                        regResult.className = 'mvs-result show error';
                        regResult.textContent = (data.data && data.data.error) || 'No match found';
                    }
                })
                .catch(function() {
                    regResult.className = 'mvs-result show error';
                    regResult.textContent = 'An error occurred. Please try again.';
                });
        }

        regBtn.addEventListener('click', function(e) { e.preventDefault(); doRegSearch(); });
        regInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); doRegSearch(); } });

        // Section elements
        var sectionModel = panel.querySelector('.mvs-section-model');
        var sectionDivVin = panel.querySelector('.mvs-section-divider-vin');
        var sectionVin = panel.querySelector('.mvs-section-vin');
        var sectionDivReg = panel.querySelector('.mvs-section-divider-reg');
        var sectionReg = panel.querySelector('.mvs-section-reg');

        // Show/hide sections: 'full' = everything, 'vin' = VIN only, 'reg' = registration only
        function setPanelMode(mode) {
            var all = [sectionModel, sectionDivVin, sectionVin, sectionDivReg, sectionReg];
            if (mode === 'reg') {
                all.forEach(function(el) { el.style.display = 'none'; });
                sectionReg.style.display = '';
            } else if (mode === 'vin') {
                all.forEach(function(el) { el.style.display = 'none'; });
                sectionVin.style.display = '';
            } else {
                all.forEach(function(el) { el.style.display = ''; });
            }
        }

        // Position and open the panel below an element
        function openPanel(anchor, mode) {
            cancelClose();
            // Close vehicles dropdown and category flyout if open
            var vdd = document.getElementById('maxus-dd-vehicles');
            if (vdd) vdd.classList.remove('is-open');
            var cdd = document.getElementById('maxus-dd-cats');
            if (cdd) cdd.classList.remove('is-open');
            setPanelMode(mode || 'full');
            var rect = anchor.getBoundingClientRect();
            var pw = panel.offsetWidth || 380;
            var left = rect.left;
            if (left + pw > window.innerWidth - 8) left = window.innerWidth - pw - 8;
            if (left < 8) left = 8;
            panel.style.top = rect.bottom + 'px';
            panel.style.left = left + 'px';
            panel.classList.add('is-open');
            isOpen = true;
        }

        function togglePanel(anchor, mode) {
            if (isOpen) {
                panel.classList.remove('is-open');
                isOpen = false;
            } else {
                openPanel(anchor, mode);
            }
        }

        // Keep panel open when mouse is inside it
        panel.addEventListener('mouseenter', cancelClose);
        panel.addEventListener('mouseleave', scheduleClose);

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!isOpen) return;
            if (panel.contains(e.target)) return;
            if (e.target.closest('.elementor-element-c170644') ||
                e.target.closest('#menu-item-224992') ||
                e.target.closest('#menu-item-224993') ||
                e.target.closest('#menu-item-224994')) return;
            panel.classList.remove('is-open');
            isOpen = false;
        });

        // ── Attach to the red "My Vehicle" button (Elementor widget c170644) ──
        var myVehicleBtn = document.querySelector('.elementor-element-c170644 a.et-button') ||
                           document.querySelector('a.et-button[data-megamenu="1546"]');

        if (myVehicleBtn) {
            myVehicleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                myVehicleBtn.classList.remove('active');
                togglePanel(myVehicleBtn, 'full');
            }, true);

            var btnContainer = myVehicleBtn.closest('.elementor-widget-container') || myVehicleBtn.parentElement;
            btnContainer.addEventListener('mouseenter', function() { openPanel(myVehicleBtn, 'full'); });
            btnContainer.addEventListener('mouseleave', scheduleClose);
        }

        // ── Attach to nav menu items ──
        // Vehicles (224992) → vehicle list dropdown
        var vehDD = document.getElementById('maxus-dd-vehicles');
        var vehMenuItem = document.querySelector('#menu-item-224992');
        var vehCloseTimer = null;

        function openVehDD(anchor) {
            clearTimeout(vehCloseTimer);
            // Close the panel if open
            panel.classList.remove('is-open');
            isOpen = false;
            // Position and show vehicles dropdown
            var rect = anchor.getBoundingClientRect();
            vehDD.style.top = rect.bottom + 'px';
            vehDD.style.left = rect.left + 'px';
            vehDD.classList.add('is-open');
        }
        function scheduleVehClose() {
            vehCloseTimer = setTimeout(function() {
                vehDD.classList.remove('is-open');
            }, 200);
        }

        if (vehMenuItem && vehDD) {
            vehMenuItem.addEventListener('mouseenter', function() { openVehDD(vehMenuItem); });
            vehMenuItem.addEventListener('mouseleave', function() { scheduleVehClose(); });
            var vehLink = vehMenuItem.querySelector('a');
            if (vehLink) vehLink.addEventListener('click', function(e) { e.preventDefault(); });
            vehDD.addEventListener('mouseenter', function() { clearTimeout(vehCloseTimer); clearTimeout(catCloseTimer); });
            vehDD.addEventListener('mouseleave', function() { scheduleVehClose(); });
        }

        // ── Category flyout (appears right of vehicles dropdown on vehicle hover) ──
        var catDD = document.getElementById('maxus-dd-cats');
        var cddTitle = document.getElementById('cdd-title');
        var cddBody = document.getElementById('cdd-body');
        var catCloseTimer = null;
        var activeVehLink = null;

        function openCatFlyout(vehLink) {
            var slug = vehLink.getAttribute('data-vehicle');
            if (!slug || !catData[slug]) {
                closeCatFlyout();
                return;
            }
            clearTimeout(catCloseTimer);

            // Highlight active vehicle link
            if (activeVehLink) activeVehLink.style.background = '';
            activeVehLink = vehLink;
            activeVehLink.style.background = 'rgba(255,255,255,0.2)';

            // Get vehicle display name
            var nameEl = vehLink.querySelector('.vdd-name');
            cddTitle.textContent = nameEl ? nameEl.textContent : 'Categories';

            // Populate grouped categories
            cddBody.innerHTML = '';
            catData[slug].forEach(function(group) {
                var div = document.createElement('div');
                div.className = 'cdd-group';
                var h = document.createElement('p');
                h.className = 'cdd-group-title';
                h.textContent = group.g;
                div.appendChild(h);
                var ul = document.createElement('ul');
                group.cats.forEach(function(cat) {
                    var li = document.createElement('li');
                    var a = document.createElement('a');
                    a.href = homeUrl + slug + '/' + cat.s + '/';
                    a.textContent = cat.n;
                    li.appendChild(a);
                    ul.appendChild(li);
                });
                div.appendChild(ul);
                cddBody.appendChild(div);
            });

            // Position to the right of the vehicles dropdown
            var vddRect = vehDD.getBoundingClientRect();
            catDD.style.top = vddRect.top + 'px';
            catDD.style.left = (vddRect.right - 2) + 'px';
            catDD.style.maxHeight = (window.innerHeight - vddRect.top - 10) + 'px';
            catDD.classList.add('is-open');
        }

        function closeCatFlyout() {
            catDD.classList.remove('is-open');
            if (activeVehLink) {
                activeVehLink.style.background = '';
                activeVehLink = null;
            }
        }

        function scheduleCatClose() {
            catCloseTimer = setTimeout(closeCatFlyout, 200);
        }

        // Attach hover events to vehicle links inside the dropdown
        if (vehDD && catDD) {
            vehDD.querySelectorAll('a[data-vehicle]').forEach(function(link) {
                link.addEventListener('mouseenter', function() {
                    clearTimeout(catCloseTimer);
                    openCatFlyout(link);
                });
            });

            // Keep cat flyout open when hovering it
            catDD.addEventListener('mouseenter', function() {
                clearTimeout(catCloseTimer);
                clearTimeout(vehCloseTimer);
            });
            catDD.addEventListener('mouseleave', function() {
                scheduleCatClose();
                scheduleVehClose();
            });

            // Override scheduleVehClose to also close cat flyout
            scheduleVehClose = function() {
                vehCloseTimer = setTimeout(function() {
                    vehDD.classList.remove('is-open');
                    closeCatFlyout();
                }, 200);
            };
        }

        // VIN Lookup (224993) → VIN only
        var vinMenuItem = document.querySelector('#menu-item-224993');
        if (vinMenuItem) {
            vinMenuItem.addEventListener('mouseenter', function() { openPanel(vinMenuItem, 'vin'); });
            vinMenuItem.addEventListener('mouseleave', scheduleClose);
            var vinMenuLink = vinMenuItem.querySelector('a');
            if (vinMenuLink) vinMenuLink.addEventListener('click', function(e) { e.preventDefault(); });
        }

        // Registration Lookup (224994) → registration only
        var regMenuItem = document.querySelector('#menu-item-224994');
        if (regMenuItem) {
            regMenuItem.addEventListener('mouseenter', function() { openPanel(regMenuItem, 'reg'); });
            regMenuItem.addEventListener('mouseleave', scheduleClose);
            var regLink = regMenuItem.querySelector('a');
            if (regLink) regLink.addEventListener('click', function(e) { e.preventDefault(); });
        }
    });
    </script>
    <?php
}

// =============================================================================
// Registration Lookup via checkcardetails.co.uk API
// =============================================================================
add_action('wp_ajax_maxus_reg_lookup', 'maxus_reg_lookup');
add_action('wp_ajax_nopriv_maxus_reg_lookup', 'maxus_reg_lookup');
function maxus_reg_lookup() {
    $reg = isset($_POST['reg']) ? sanitize_text_field($_POST['reg']) : '';
    $reg = preg_replace('/\s+/', '', strtoupper($reg));

    if (empty($reg) || strlen($reg) < 2) {
        wp_send_json_error(['error' => 'Please enter a valid registration number']);
    }

    // Call the checkcardetails.co.uk API
    $api_key = 'd54fb43716925ad8f4dc415a4e2f962d';
    $api_url = 'https://api.checkcardetails.co.uk/vehicledata/vehicleregistration?apikey=' . $api_key . '&vrm=' . urlencode($reg);

    $response = wp_remote_get($api_url, ['timeout' => 10]);

    if (is_wp_error($response)) {
        wp_send_json_error(['error' => 'Could not connect to vehicle lookup service']);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code === 404 || empty($body)) {
        wp_send_json_error(['error' => 'No vehicle found for registration: ' . $reg]);
    }

    if ($code !== 200) {
        wp_send_json_error(['error' => 'Vehicle lookup failed. Please try again.']);
    }

    $make  = isset($body['make']) ? strtoupper(trim($body['make'])) : '';
    $model = isset($body['model']) ? trim($body['model']) : '';
    $year  = isset($body['yearOfManufacture']) ? intval($body['yearOfManufacture']) : '';
    $fuel  = isset($body['fuelType']) ? trim($body['fuelType']) : '';

    // Check if this is a Maxus/LDV vehicle
    $is_maxus = in_array($make, ['MAXUS', 'LDV', 'SAIC', 'MG']);

    if (!$is_maxus) {
        wp_send_json_error([
            'error' => 'This is a ' . ucwords(strtolower($make)) . ' ' . $model . ' (' . $year . '). We only stock Maxus/LDV parts.',
            'vehicle_info' => $body,
        ]);
    }

    // Try to match the model to a vehicle landing page
    $vehicles = maxus_get_vehicle_vins();
    $home_url = home_url('/');
    $best_slug = '';
    $best_name = '';
    $model_upper = strtoupper($model);

    // Build matching candidates from vehicle vins
    foreach ($vehicles as $slug => $v) {
        $v_name = strtoupper(preg_replace('/^MAXUS\s+/i', '', $v['name']));

        // Try direct substring match
        if (stripos($model_upper, $v_name) !== false || stripos($v_name, $model_upper) !== false) {
            $best_slug = $slug;
            $best_name = $v['name'];
            break;
        }

        // Try keyword matching (e.g. "DELIVER 9" in model string)
        $keywords = ['DELIVER 9', 'DELIVER 7', 'E DELIVER 9', 'E DELIVER 7', 'E DELIVER 3', 'E-DELIVER', 'T60', 'T90', 'V80', 'A80'];
        foreach ($keywords as $kw) {
            if (stripos($model_upper, $kw) !== false && stripos($v_name, $kw) !== false) {
                // Check electric vs diesel
                $is_electric = (stripos($fuel, 'ELECTRIC') !== false);
                $v_is_electric = (stripos($v_name, 'E DELIVER') !== false || stripos($v_name, 'E-DELIVER') !== false || stripos($v_name, 'EV') !== false);

                if ($is_electric === $v_is_electric) {
                    $best_slug = $slug;
                    $best_name = $v['name'];
                    break 2;
                }
                // Keep as fallback if no fuel match yet
                if (empty($best_slug)) {
                    $best_slug = $slug;
                    $best_name = $v['name'];
                }
            }
        }
    }

    $display_name = ucwords(strtolower($make . ' ' . $model));

    if (!empty($best_slug)) {
        wp_send_json_success([
            'vehicle_name' => $display_name,
            'customer_year' => $year,
            'shop_url' => $home_url . $best_slug . '/',
            'matched_vehicle' => $best_name,
        ]);
    } else {
        // Vehicle is Maxus but we couldn't match a specific model
        wp_send_json_success([
            'vehicle_name' => $display_name,
            'customer_year' => $year,
            'shop_url' => $home_url . 'shop/',
            'matched_vehicle' => '',
            'note' => 'Could not match exact model. Showing all parts.',
        ]);
    }
}

// Change "Select options" to "View" for variable products in product loops
add_filter('woocommerce_product_add_to_cart_text', 'maxus_change_variable_button_text', 10, 2);
function maxus_change_variable_button_text($text, $product) {
    if ($product->is_type('variable')) {
        return 'View';
    }
    return $text;
}

// Show first variation SKU for variable products in product loops
add_filter('woocommerce_product_get_sku', 'maxus_variable_product_sku_fallback', 10, 2);
function maxus_variable_product_sku_fallback($sku, $product) {
    if (empty($sku) && $product->is_type('variable')) {
        $children = $product->get_children();
        if (!empty($children)) {
            $child_sku = get_post_meta($children[0], '_sku', true);
            if ($child_sku) return $child_sku;
        }
    }
    return $sku;
}

// Product loop styling: grey text buttons, hide stars, red search button
add_action('wp_head', 'maxus_homepage_button_styles');
function maxus_homepage_button_styles() {
    ?>
    <style>
    /* Product loop buttons: grey text link style instead of blue filled */
    ul.products .product .button,
    ul.products .product .added_to_cart {
        background-color: transparent !important;
        color: #888 !important;
        box-shadow: none !important;
    }
    ul.products .product .button:hover,
    ul.products .product .added_to_cart:hover {
        background-color: transparent !important;
        color: #333 !important;
    }
    ul.products .product .button:before,
    ul.products .product .button:after,
    ul.products .product .added_to_cart:before,
    ul.products .product .added_to_cart:after {
        display: none !important;
    }
    /* Hide star ratings in product loops */
    ul.products .product .star-rating-wrap {
        display: none !important;
    }
    /* Vehicle filter search button: red to match header search */
    .vehicle-filter input[type="submit"] {
        background-color: #BF3617 !important;
        color: #fff !important;
        border-color: #BF3617 !important;
    }
    .vehicle-filter input[type="submit"]:hover {
        background-color: #a02e13 !important;
        border-color: #a02e13 !important;
    }
    /* Fix vehicle filter Select2 dropdowns opening upward */
    .vehicle-filter .vf-item {
        overflow: visible !important;
        position: relative;
    }
    .vehicle-filter .vf-item .select2-container--open .select2-dropdown--above {
        top: auto !important;
        bottom: auto !important;
    }
    .vehicle-filter .select2-container {
        overflow: visible !important;
    }
    /* Homepage main section: left column natural height, right column stretches */
    .elementor-element-fca08fb > .e-con-inner {
        align-items: flex-start !important;
    }
    .elementor-element-fca08fb > .e-con-inner > .e-child:last-child {
        display: flex !important;
        flex-direction: column !important;
        align-self: stretch !important;
    }
    /* Show SKU under product title in loops (parent theme hides it) */
    .product .product-sku {
        display: block !important;
        font-size: 12px;
        color: #888;
        margin: 2px 0 6px;
    }
    /* Why Use Us grid: equal height icon boxes */
    .why-use-us-grid > .e-con-inner,
    .why-use-us-grid > .e-con-inner > .e-child > .e-con-inner {
        display: flex !important;
        align-items: stretch !important;
    }
    .why-use-us-grid > .e-con-inner > .e-child {
        display: flex !important;
        flex-direction: column !important;
    }
    .why-use-us-grid .elementor-widget-et_icon_box {
        flex: 1 !important;
    }
    .why-use-us-grid .elementor-widget-et_icon_box > .elementor-widget-container,
    .why-use-us-grid .elementor-widget-et_icon_box .et-icon-box {
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
    }
    .why-use-us-grid .et-icon-box-content {
        flex: 1 !important;
    }
    /* Push Why Use Us section to fill remaining space in right column */
    .why-use-us-grid {
        flex: 1 !important;
    }
    </style>
    <?php
}

require_once get_stylesheet_directory() . '/trade-account-form.php';
require_once get_stylesheet_directory() . '/price-enquiry-form.php';
require_once get_stylesheet_directory() . '/checkout-customizations.php';

// Branded login page
add_action('login_enqueue_scripts', 'maxus_login_styles');
function maxus_login_styles() { ?>
    <style>
        html, body, body.login, body.login-action-login {
            background: #F29F05 !important;
            background-color: #F29F05 !important;
        }
        #login h1 a {
            background-image: url('<?php echo esc_url(content_url('/uploads/new-maxus-parts-direct-logo-site2-scaled-1-660x144.webp')); ?>') !important;
            background-size: contain !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            width: 320px !important;
            height: 70px !important;
            margin-bottom: 20px !important;
        }
        .login form {
            background: #fff !important;
            border: none !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3) !important;
        }
        .login form .input,
        .login form input[type="text"],
        .login form input[type="password"] {
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            background: #f9f9f9 !important;
        }
        .login form .input:focus,
        .login form input[type="text"]:focus,
        .login form input[type="password"]:focus {
            border-color: #F29F05 !important;
            box-shadow: 0 0 0 1px #F29F05 !important;
        }
        .wp-core-ui .button-primary {
            background: #F29F05 !important;
            border-color: #D98E04 !important;
            color: #fff !important;
            border-radius: 4px !important;
            text-shadow: none !important;
            box-shadow: none !important;
        }
        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus {
            background: #D98E04 !important;
            border-color: #c07d03 !important;
        }
        .login #nav a,
        .login #backtoblog a {
            color: #333 !important;
        }
        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: #000 !important;
        }
        .login .message,
        .login .success {
            border-left-color: #F29F05 !important;
        }
    </style>
<?php }

add_filter('login_headerurl', 'maxus_login_url');
function maxus_login_url() {
    return home_url('/');
}

add_filter('login_headertext', 'maxus_login_text');
function maxus_login_text() {
    return 'Maxus Parts Direct';
}

// WooCommerce email branding
add_action('woocommerce_email_styles', 'maxus_email_styles');
function maxus_email_styles($css) {
    $css .= '
        #wrapper { background-color: #f5f5f5 !important; }
        #template_header {
            background-color: #F29F05 !important;
            color: #ffffff !important;
            border-bottom: none !important;
            border-radius: 6px 6px 0 0 !important;
        }
        #template_header h1 {
            color: #ffffff !important;
        }
        #template_header_image {
            background-color: #F29F05 !important;
            padding: 20px 0 10px !important;
        }
        #template_header_image img {
            max-width: 200px !important;
        }
        #template_body .td { color: #333 !important; }
        #template_footer #credit { color: #888 !important; font-size: 12px !important; }
        .order_item td { border-bottom: 1px solid #eee !important; }
        h2 { color: #1a1a2e !important; }
        a { color: #F29F05 !important; }
    ';
    return $css;
}

// PDF Invoice - force shop address display + right-align
add_action('wpo_wcpdf_before_shop_address', 'maxus_pdf_render_shop_address', 10, 2);
function maxus_pdf_render_shop_address($type, $order) {
    echo 'Unit 1-10, Cherry Tree Road<br>Tibenham, Norwich<br>NR16 1PH<br>United Kingdom';
}
add_filter('wpo_wcpdf_shop_address', '__return_empty_string');

add_action('wpo_wcpdf_custom_styles', 'maxus_pdf_custom_styles', 10, 2);
function maxus_pdf_custom_styles($type, $document) {
    echo 'td.shop-info { text-align: right; }';
    echo '.vehicle-verification-pdf { margin-bottom: 5mm; padding: 3mm 4mm; border: 0.5mm solid #F29F05; font-size: 8pt; }';
    echo '.vehicle-verification-pdf strong { font-size: 9pt; }';
    echo '.order-details td.price, .order-details th.price { text-align: right; }';
    echo '.order-details td.quantity, .order-details th.quantity { text-align: right; }';
    echo 'table.totals td.price, table.totals th.description { text-align: right; }';
    echo '.delivery-estimate { margin: 1mm 0 0; font-size: 8pt; color: #555; }';
}

// PDF Invoice - show VIN/Reg details above products
add_action('wpo_wcpdf_before_order_details', 'maxus_pdf_vehicle_verification', 10, 2);
function maxus_pdf_vehicle_verification($type, $order) {
    $vehicle_info = $order->get_meta('_vehicle_verification');
    if (empty($vehicle_info)) {
        // Fallback: check order notes for vehicle details
        $notes = $order->get_customer_note();
        if (strpos($notes, '--- Vehicle Details ---') !== false) {
            $parts = explode('--- Vehicle Details ---', $notes);
            if (isset($parts[1])) {
                $vehicle_info = trim($parts[1]);
            }
        }
    }
    echo '<div class="vehicle-verification-pdf">';
    echo '<strong>VIN / Reg Numbers Supplied by Customer:</strong><br>';
    if (!empty($vehicle_info)) {
        echo nl2br(esc_html($vehicle_info));
    } else {
        echo 'None Provided';
    }
    echo '</div>';
}

/**
 * Calculate estimated delivery date by adding N working days (skip Sat/Sun)
 */
function maxus_get_estimated_delivery_date($product_id) {
    $delivery_time = get_post_meta($product_id, '_estimated_delivery_time', true);
    if (empty($delivery_time)) {
        $delivery_time = '3-5 working days';
    }

    // Parse the max number from the string (e.g. "3-5 working days" → 5)
    if (preg_match('/(\d+)\s*(?:-\s*(\d+))?\s*working/i', $delivery_time, $matches)) {
        $max_days = isset($matches[2]) ? intval($matches[2]) : intval($matches[1]);
    } else {
        $max_days = 5; // fallback
    }

    // Add working days (skip Saturday and Sunday)
    $date = new DateTime('now', new DateTimeZone('Europe/London'));
    $added = 0;
    while ($added < $max_days) {
        $date->modify('+1 day');
        $dow = (int) $date->format('N'); // 1=Mon ... 7=Sun
        if ($dow <= 5) {
            $added++;
        }
    }

    // Format: "Mon 17th Feb 2026"
    return $date->format('D jS M Y');
}

/**
 * PDF Invoice - show estimated delivery date per line item
 */
add_action('wpo_wcpdf_after_item_meta', 'maxus_pdf_delivery_estimate', 10, 3);
function maxus_pdf_delivery_estimate($type, $item, $order) {
    if ($type !== 'invoice') {
        return;
    }
    $product_id = $item['product_id'];
    if (!$product_id) {
        return;
    }
    $date = maxus_get_estimated_delivery_date($product_id);
    echo '<p class="delivery-estimate" style="margin:4px 0 0; font-size:8pt; color:#555;">Est. Delivery: ' . esc_html($date) . '</p>';
}

// Custom footer - replace parent theme footer
remove_action('mobex_enovathemes_footer', 'mobex_enovathemes_footer');
add_action('mobex_enovathemes_footer', 'maxus_custom_footer');
function maxus_custom_footer() {
    include get_stylesheet_directory() . '/footer-custom.php';
}

// ============================================================
// FAVICON - Use custom SVG instead of default WordPress logo
// ============================================================
add_action('wp_head', function () {
    $favicon_url = content_url('/uploads/favicon.svg');
    echo '<link rel="icon" href="' . esc_url($favicon_url) . '" type="image/svg+xml">' . "\n";
    echo '<link rel="shortcut icon" href="' . esc_url($favicon_url) . '">' . "\n";
});
// Also show in wp-admin
add_action('admin_head', function () {
    $favicon_url = content_url('/uploads/favicon.svg');
    echo '<link rel="icon" href="' . esc_url($favicon_url) . '" type="image/svg+xml">' . "\n";
});
// Also show on login page
add_action('login_head', function () {
    $favicon_url = content_url('/uploads/favicon.svg');
    echo '<link rel="icon" href="' . esc_url($favicon_url) . '" type="image/svg+xml">' . "\n";
});

// ============================================================
// SECURITY HARDENING
// ============================================================

// 1. Restrict REST API to authenticated users only (allow WooCommerce cart/checkout)
add_filter('rest_authentication_errors', function ($result) {
    if (true === $result || is_wp_error($result)) {
        return $result;
    }
    // Allow logged-in users full access
    if (is_user_logged_in()) {
        return $result;
    }
    // Allow WooCommerce store API (needed for cart/checkout)
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($request_uri, '/wc/store/') !== false) {
        return $result;
    }
    // Allow nonce-authenticated requests (e.g. AJAX from frontend)
    if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_rest')) {
        return $result;
    }
    // Block everything else
    return new WP_Error('rest_forbidden', 'REST API access restricted.', ['status' => 403]);
});

// 2. Remove WordPress version from head, feeds, and styles
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');
add_filter('style_loader_src', 'maxus_remove_version_query', 9999);
add_filter('script_loader_src', 'maxus_remove_version_query', 9999);
function maxus_remove_version_query($src) {
    if (strpos($src, 'ver=') !== false) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}

// 3. Disable XML-RPC completely
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', function ($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

// 4. Block user enumeration via ?author=N
add_action('template_redirect', function () {
    if (!is_admin() && isset($_REQUEST['author']) && !is_user_logged_in()) {
        wp_redirect(home_url(), 301);
        exit;
    }
});

// 5. Remove unnecessary head links
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

// 6. Security headers
add_action('send_headers', function () {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
});

// 7. Disable file editing from admin dashboard
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

// 8. Prevent image right-click/drag and block common scraping
add_action('wp_head', function () {
    if (!is_user_logged_in()) {
        ?>
        <style>
        /* Prevent image dragging and selection */
        img { -webkit-user-drag: none; user-select: none; -moz-user-select: none; -webkit-user-select: none; }
        </style>
        <script>
        (function(){
            // Disable right-click on images
            document.addEventListener('contextmenu', function(e) {
                if (e.target.tagName === 'IMG' || e.target.closest('img')) {
                    e.preventDefault();
                }
            });
            // Disable image drag
            document.addEventListener('dragstart', function(e) {
                if (e.target.tagName === 'IMG') {
                    e.preventDefault();
                }
            });
        })();
        </script>
        <?php
    }
});

// 9. Rate-limit AJAX/REST requests per IP (simple in-memory via transients)
add_action('init', function () {
    if (!defined('DOING_AJAX') || !DOING_AJAX) return;
    if (is_user_logged_in()) return;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'rate_' . md5($ip);
    $hits = (int) get_transient($key);
    if ($hits > 60) { // Max 60 AJAX requests per minute per IP
        wp_send_json_error(['error' => 'Rate limit exceeded. Please try again later.'], 429);
    }
    set_transient($key, $hits + 1, 60);
});

// 10. Block direct access to sensitive upload files
add_action('init', function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    // Block access to SQL dumps, log files, and PHP files in uploads
    if (preg_match('#/wp-content/uploads/.*\.(sql|log|php|sh|py|bak)(\?|$)#i', $uri)) {
        status_header(403);
        exit('Forbidden');
    }
});
// =============================================================================
// SEARCH BY SKU: Include product SKU in WooCommerce search results
// =============================================================================
add_filter('posts_search', 'maxus_search_by_sku', 10, 2);
function maxus_search_by_sku($search, $wp_query) {
    global $wpdb;

    if (!$wp_query->is_search() || !$wp_query->is_main_query() || is_admin()) {
        return $search;
    }

    $search_term = $wp_query->get('s');
    if (empty($search_term)) {
        return $search;
    }

    // Find product IDs matching the SKU (direct products)
    $product_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_sku' AND meta_value LIKE %s
         AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish')",
        '%' . $wpdb->esc_like($search_term) . '%'
    ));

    // Find parent product IDs for variations matching the SKU
    $variation_parent_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.post_parent FROM {$wpdb->postmeta} pm
         JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_sku' AND pm.meta_value LIKE %s
         AND p.post_type = 'product_variation' AND p.post_status = 'publish'
         AND p.post_parent IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish')",
        '%' . $wpdb->esc_like($search_term) . '%'
    ));

    $all_ids = array_unique(array_merge($product_ids, $variation_parent_ids));

    if (!empty($all_ids)) {
        $id_list = implode(',', array_map('intval', $all_ids));
        $search = str_replace(
            'AND ((',
            "AND (({$wpdb->posts}.ID IN ({$id_list})) OR (",
            $search
        );
    }

    return $search;
}

// Also ensure the search form on the shop sends post_type=product
add_filter('get_search_form', 'maxus_search_form_product_type');
function maxus_search_form_product_type($form) {
    if (is_woocommerce() || is_shop() || is_product_category() || is_product_tag()) {
        $form = str_replace(
            '</form>',
            '<input type="hidden" name="post_type" value="product" /></form>',
            $form
        );
    }
    return $form;
}
