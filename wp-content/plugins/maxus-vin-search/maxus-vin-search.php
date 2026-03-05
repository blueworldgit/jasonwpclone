<?php
/**
 * Plugin Name: Maxus VIN Search
 * Description: VIN pattern matching search for Maxus van parts
 * Version: 1.0.0
 * Author: Maxus Van Parts
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maxus_VIN_Search {

    // VIN pattern to friendly name mapping
    private $vehicle_names = [
        'LSFAL11A' => 'Maxus Deliver 9 / eDeliver 9',
        'LSH14J7C' => 'Maxus eDeliver 3',
    ];

    // Model year codes (position 10 of VIN)
    private $year_codes = [
        'A' => 2010, 'B' => 2011, 'C' => 2012, 'D' => 2013, 'E' => 2014,
        'F' => 2015, 'G' => 2016, 'H' => 2017, 'J' => 2018, 'K' => 2019,
        'L' => 2020, 'M' => 2021, 'N' => 2022, 'P' => 2023, 'R' => 2024,
        'S' => 2025, 'T' => 2026, 'V' => 2027, 'W' => 2028, 'X' => 2029,
        'Y' => 2030,
        '1' => 2001, '2' => 2002, '3' => 2003, '4' => 2004, '5' => 2005,
        '6' => 2006, '7' => 2007, '8' => 2008, '9' => 2009,
    ];

    public function __construct() {
        add_shortcode('maxus_vin_search', [$this, 'render_search_form']);
        add_action('wp_ajax_maxus_vin_lookup', [$this, 'ajax_vin_lookup']);
        add_action('wp_ajax_nopriv_maxus_vin_lookup', [$this, 'ajax_vin_lookup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Get all reference VINs from product categories
     */
    private function get_reference_vins() {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        $vins = [];
        foreach ($categories as $cat) {
            // Match VIN pattern by SLUG (17 chars starting with ls) - allows friendly display names
            if (preg_match('/^(ls[a-z0-9]{15})$/i', $cat->slug, $matches)) {
                $full_vin = strtoupper($matches[1]);
                $pattern = substr($full_vin, 0, 8);
                $year_code = substr($full_vin, 9, 1);
                $year = $this->year_codes[$year_code] ?? null;

                $vins[] = [
                    'full_vin' => $full_vin,
                    'pattern' => $pattern,
                    'year_code' => $year_code,
                    'year' => $year,
                    'category_id' => $cat->term_id,
                    'category_slug' => $cat->slug,
                    'category_name' => $cat->name, // Display name (can be friendly)
                    'part_count' => $cat->count,
                    'vehicle_name' => $this->vehicle_names[$pattern] ?? 'Maxus Vehicle',
                ];
            }
        }

        return $vins;
    }

    /**
     * Find matching reference VIN for customer input
     */
    private function find_matching_vin($customer_vin) {
        $customer_vin = strtoupper(preg_replace('/[^A-Z0-9]/', '', $customer_vin));

        // Validate VIN length
        if (strlen($customer_vin) !== 17) {
            return ['error' => 'VIN must be exactly 17 characters'];
        }

        // Check it's a Maxus VIN (starts with LS)
        if (substr($customer_vin, 0, 2) !== 'LS') {
            return ['error' => 'This does not appear to be a Maxus VIN (should start with LS)'];
        }

        $customer_pattern = substr($customer_vin, 0, 8);
        $customer_year_code = substr($customer_vin, 9, 1);
        $customer_year = $this->year_codes[$customer_year_code] ?? null;

        $reference_vins = $this->get_reference_vins();
        $matches = [];

        // Find all VINs with matching pattern
        foreach ($reference_vins as $ref) {
            if ($ref['pattern'] === $customer_pattern) {
                $matches[] = $ref;
            }
        }

        if (empty($matches)) {
            // No exact pattern match - try to find closest
            return [
                'error' => 'No parts found for VIN pattern: ' . $customer_pattern,
                'suggestion' => 'We may not have parts for this specific Maxus model yet.',
                'customer_vin' => $customer_vin,
                'customer_pattern' => $customer_pattern,
                'customer_year' => $customer_year,
            ];
        }

        // If multiple matches, find closest year
        $best_match = null;
        $smallest_diff = PHP_INT_MAX;

        foreach ($matches as $match) {
            if ($customer_year && $match['year']) {
                $diff = abs($customer_year - $match['year']);
                if ($diff < $smallest_diff) {
                    $smallest_diff = $diff;
                    $best_match = $match;
                }
            }
        }

        // If no year match, just use first pattern match
        if (!$best_match) {
            $best_match = $matches[0];
        }

        return [
            'success' => true,
            'customer_vin' => $customer_vin,
            'customer_year' => $customer_year,
            'match' => $best_match,
            'all_matches' => $matches,
            'vehicle_name' => $best_match['vehicle_name'],
        ];
    }

    /**
     * AJAX handler for VIN lookup
     */
    public function ajax_vin_lookup() {
        $vin = isset($_POST['vin']) ? sanitize_text_field($_POST['vin']) : '';

        if (empty($vin)) {
            wp_send_json_error(['message' => 'Please enter a VIN']);
        }

        $customer_vin = strtoupper(preg_replace('/[^A-Z0-9]/', '', $vin));

        if (strlen($customer_vin) !== 17) {
            wp_send_json_error(['error' => 'VIN must be exactly 17 characters']);
        }

        if (substr($customer_vin, 0, 2) !== 'LS') {
            wp_send_json_error(['error' => 'This does not appear to be a Maxus VIN (should start with LS)']);
        }

        $customer_pattern = substr($customer_vin, 0, 8);
        $customer_year_code = substr($customer_vin, 9, 1);
        $customer_year = $this->year_codes[$customer_year_code] ?? null;
        $home_url = home_url('/');

        // Match against vehicle VIN mappings from the theme
        if (!function_exists('maxus_get_vehicle_vins')) {
            wp_send_json_error(['error' => 'Vehicle data not available']);
        }

        $vehicles = maxus_get_vehicle_vins();
        $matches = [];

        // Find all vehicles with matching 8-char VIN pattern
        foreach ($vehicles as $slug => $v) {
            $v_pattern = substr(strtoupper($v['vin']), 0, 8);
            if ($v_pattern === $customer_pattern) {
                $matches[$slug] = $v;
            }
        }

        if (empty($matches)) {
            wp_send_json_error([
                'error' => 'No vehicle found for VIN pattern: ' . $customer_pattern,
                'suggestion' => 'We may not have parts for this specific Maxus model yet.',
                'customer_vin' => $customer_vin,
                'customer_pattern' => $customer_pattern,
                'customer_year' => $customer_year,
            ]);
        }

        // If single match, use it directly
        if (count($matches) === 1) {
            $slug = array_key_first($matches);
            $v = $matches[$slug];
            wp_send_json_success([
                'vehicle_name' => $v['name'],
                'customer_year' => $customer_year,
                'shop_url' => $home_url . $slug . '/',
            ]);
        }

        // Multiple matches - narrow by year
        $best_slug = null;
        if ($customer_year) {
            foreach ($matches as $slug => $v) {
                if (preg_match('/(\d{4})\s*-\s*(\S+)/', $v['year'], $m)) {
                    $start = intval($m[1]);
                    $end = ($m[2] === 'Present') ? 2030 : intval($m[2]);
                    if ($customer_year >= $start && $customer_year <= $end) {
                        $best_slug = $slug;
                        break;
                    }
                }
            }
        }

        // Fallback to first match
        if (!$best_slug) {
            $best_slug = array_key_first($matches);
        }

        $v = $matches[$best_slug];
        wp_send_json_success([
            'vehicle_name' => $v['name'],
            'customer_year' => $customer_year,
            'shop_url' => $home_url . $best_slug . '/',
        ]);
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (has_shortcode(get_post()->post_content ?? '', 'maxus_vin_search')) {
            wp_enqueue_style(
                'maxus-vin-search',
                plugin_dir_url(__FILE__) . 'style.css',
                [],
                '1.0.0'
            );
        }
    }

    /**
     * Render the search form shortcode
     */
    public function render_search_form($atts) {
        $atts = shortcode_atts([
            'title' => 'Find Parts For Your Maxus Van',
            'button_text' => 'Search Parts',
        ], $atts);

        // Get reference VINs for display
        $reference_vins = $this->get_reference_vins();

        ob_start();
        ?>
        <div class="maxus-vin-search-container">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            <p class="maxus-vin-description">Enter your 17-character VIN to find compatible parts for your vehicle.</p>

            <form class="maxus-vin-form" id="maxus-vin-form">
                <div class="maxus-vin-input-wrapper">
                    <input
                        type="text"
                        id="maxus-vin-input"
                        name="vin"
                        placeholder="Enter VIN (e.g., LSFAL11A5MA087816)"
                        maxlength="17"
                        pattern="[A-Za-z0-9]{17}"
                        required
                    >
                    <button type="submit" class="maxus-vin-submit">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
                <p class="maxus-vin-hint">Your VIN can be found on your V5C document or on the driver's side dashboard.</p>
            </form>

            <div class="maxus-vin-result" id="maxus-vin-result" style="display:none;"></div>

            <div class="maxus-vin-vehicles">
                <h3>Vehicles We Stock Parts For:</h3>
                <ul>
                    <?php foreach ($reference_vins as $vin): ?>
                        <li>
                            <strong><?php echo esc_html($vin['vehicle_name']); ?></strong>
                            (<?php echo esc_html($vin['year']); ?>) -
                            <?php echo number_format($vin['part_count']); ?> parts available
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('maxus-vin-form');
            const input = document.getElementById('maxus-vin-input');
            const resultDiv = document.getElementById('maxus-vin-result');

            // Auto-uppercase input
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const vin = input.value.trim();
                if (vin.length !== 17) {
                    showResult('error', 'VIN must be exactly 17 characters. You entered ' + vin.length + ' characters.');
                    return;
                }

                // Show loading
                resultDiv.style.display = 'block';
                resultDiv.className = 'maxus-vin-result loading';
                resultDiv.innerHTML = '<p><span class="mvs-loader"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="animation:mvs-spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" stroke-dasharray="32" stroke-dashoffset="16" stroke-linecap="round"/><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5"/><line x1="12" y1="2" x2="12" y2="5" stroke="currentColor" stroke-width="1.5"/><line x1="12" y1="19" x2="12" y2="22" stroke="currentColor" stroke-width="1.5"/><line x1="2" y1="12" x2="5" y2="12" stroke="currentColor" stroke-width="1.5"/><line x1="19" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.5"/></svg> Searching for parts...</span></p>';

                // AJAX request
                const formData = new FormData();
                formData.append('action', 'maxus_vin_lookup');
                formData.append('vin', vin);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.shop_url) {
                        const result = data.data;
                        let html = '<div class="maxus-vin-success">';
                        html += '<h3>Vehicle Found!</h3>';
                        html += '<p><strong>' + result.vehicle_name + ' (' + result.customer_year + ')</strong> &mdash; Redirecting...</p>';
                        html += '</div>';
                        showResult('success', html);
                        window.location.href = result.shop_url;
                    } else if (data.success) {
                        showResult('error', '<p>Vehicle found but no matching parts page available.</p>');
                    } else {
                        let html = '<div class="maxus-vin-error">';
                        html += '<h3>✗ No Match Found</h3>';
                        html += '<p>' + data.data.error + '</p>';
                        if (data.data.suggestion) {
                            html += '<p>' + data.data.suggestion + '</p>';
                        }
                        html += '<p>VIN Pattern: ' + (data.data.customer_pattern || 'N/A') + '</p>';
                        html += '<p>Please <a href="/contact">contact us</a> for assistance.</p>';
                        html += '</div>';
                        showResult('error', html);
                    }
                })
                .catch(error => {
                    showResult('error', '<p>An error occurred. Please try again.</p>');
                });
            });

            function showResult(type, html) {
                resultDiv.style.display = 'block';
                resultDiv.className = 'maxus-vin-result ' + type;
                resultDiv.innerHTML = html;
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
new Maxus_VIN_Search();
