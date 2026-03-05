<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class B2BKing_Dynamic_Rules_Pro {

    const SLUG = 'b2bking_dynamic_rules_pro';
    const EDITOR_SLUG = 'b2bking_dynamic_rule_pro_editor';

    protected static $_instance = null;

    public static function get_instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // AJAX handlers
        add_action('wp_ajax_b2bking_dynamic_rules_pro_load_rules', array($this, 'ajax_load_rules'));
        add_action('wp_ajax_b2bking_dynamic_rules_pro_toggle_status', array($this, 'ajax_toggle_status'));
        add_action('wp_ajax_b2bking_dynamic_rules_pro_delete_rule', array($this, 'ajax_delete_rule'));
        add_action('wp_ajax_b2bking_dynamic_rules_pro_update_order', array($this, 'ajax_update_order'));
        add_action('wp_ajax_b2bking_dynamic_rules_pro_save_rule', array($this, 'ajax_save_rule'));
        add_action('wp_ajax_b2bking_dynamic_rules_pro_bulk_enable', array($this, 'ajax_bulk_enable'));
        add_action('wp_ajax_b2bking_dynamic_rules_pro_bulk_disable', array($this, 'ajax_bulk_disable'));
        add_action('wp_ajax_b2bking_dynamic_rules_pro_bulk_delete', array($this, 'ajax_bulk_delete'));
    }

    public function register_admin_menu() {
        add_submenu_page(
            'b2bking',
            esc_html__('Dynamic Rules', 'b2bking'),
            esc_html__('Dynamic Rules', 'b2bking'),
            'manage_woocommerce',
            self::SLUG,
            array($this, 'dynamic_rules_pro_page_content'),
            8 // Position in the menu
        );

        // Individual editor page (hidden from menu)
        add_submenu_page(
            'b2bking', // Hidden
            esc_html__('Dynamic Rule Editor', 'b2bking'),
            esc_html__('Dynamic Rule Editor', 'b2bking'),
            apply_filters('b2bking_backend_capability_needed', 'manage_woocommerce'),
            self::EDITOR_SLUG,
            array($this, 'dynamic_rule_pro_editor_page_content'),
            26
        );

    }

    public function enqueue_scripts($hook) {
        // Only load on our pages
        if (strpos($hook, 'b2bking_dynamic_rules_pro') === false && 
            strpos($hook, 'b2bking_dynamic_rule_pro_editor') === false) {
            return;
        }

        $plugin_url = plugin_dir_url(__FILE__);


        // Enqueue CSS based on page
        if (strpos($hook, self::EDITOR_SLUG) !== false) {
            // Editor page - only load editor CSS
            wp_enqueue_style(
                'b2bking-dynamic-rule-pro-editor-css',
                $plugin_url . 'admin/assets/css/dynamic-rule-pro-editor.css',
                array(),
                B2BKING_VERSION
            );
        } else {
            // Main page - load main CSS
            wp_enqueue_style(
                'b2bking-dynamic-rules-pro-css',
                $plugin_url . 'admin/assets/css/dynamic-rules-pro.css',
                array(),
                B2BKING_VERSION
            );
        }
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'b2bking-dynamic-rules-pro-js',
            $plugin_url . 'admin/assets/js/dynamic-rules-pro.js',
            array('jquery'),
            B2BKING_VERSION,
            true
        );
        
            // Localize main script
            wp_localize_script('b2bking-dynamic-rules-pro-js', 'b2bking_dynamic_rules_pro', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('b2bking_dynamic_rules_pro_nonce'),
                'editor_page_url' => admin_url('admin.php?page=' . self::EDITOR_SLUG),
                'admin_url' => admin_url(),
            ));
        
            // Enqueue individual editor JavaScript if on editor page
            if (strpos($hook, self::EDITOR_SLUG) !== false) {
                // Enqueue SweetAlert2 for notifications
                wp_enqueue_script(
                    'b2bking-sweetalert2',
                    plugins_url('includes/assets/lib/sweetalert/sweetalert2.all.min.js', dirname(dirname(__FILE__))),
                    array(),
                    B2BKING_VERSION
                );
                
                wp_enqueue_script(
                    'b2bking-dynamic-rule-pro-editor-js',
                    $plugin_url . 'admin/assets/js/dynamic-rule-pro-editor.js',
                    array('jquery', 'b2bking-sweetalert2'),
                    B2BKING_VERSION,
                    true
                );
            
            // Localize editor script
            wp_localize_script('b2bking-dynamic-rule-pro-editor-js', 'b2bking_dynamic_rule_pro_editor', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('b2bking_dynamic_rules_pro_nonce'),
                'main_page_url' => admin_url('admin.php?page=' . self::SLUG),
                'admin_url' => admin_url(),
                'use_percentage_tiered' => intval(get_option('b2bking_enter_percentage_tiered_setting', 0)) === 1,
            ));
        }
    }

    public function dynamic_rules_pro_page_content() {
        include $this->get_template_path('dynamic-rules-pro-main.php');
    }

    public function dynamic_rule_pro_editor_page_content($rule_id = null) {
        // If rule_id is provided (from AJAX), set it in $_GET for template compatibility
        if ($rule_id !== null && $rule_id !== '') {
            $_GET['rule_id'] = $rule_id;
        }
        // Also check if rule_id is in URL parameters (for direct access)
        elseif (isset($_GET['rule_id']) && $_GET['rule_id'] !== '') {
            // rule_id is already in $_GET, no need to do anything
        }
        
        $template_path = $this->get_template_path('dynamic-rule-pro-editor.php');
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error">' . esc_html__('Template not found:', 'b2bking') . ' ' . esc_html($template_path) . '</div>';
        }
    }

    /**
     * Helper to get template path
     */
    private function get_template_path($template) {
        return plugin_dir_path(__FILE__) . 'includes/templates/' . $template;
    }

    /**
     * Get the WordPress page slug for a given page identifier
     * 
     * @param string $page Page identifier
     * @return string WordPress page slug
     */
    protected function get_page_slug($page) {
        $page_slug_map = array(
            'main' => self::SLUG,
            'editor' => self::EDITOR_SLUG,
        );
        
        return isset($page_slug_map[$page]) ? $page_slug_map[$page] : $page;
    }
    
    /**
     * AJAX: Load rules
     */
    public function ajax_load_rules() {
        // Capability check
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Failed capability check.');
            wp_die();
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_dynamic_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }

        $search = sanitize_text_field($_POST['search'] ?? '');
        $rule_type_filter = sanitize_text_field($_POST['rule_type_filter'] ?? '');
        $applies_to_filter = sanitize_text_field($_POST['applies_to_filter'] ?? '');
        $customer_group_filter = sanitize_text_field($_POST['customer_group_filter'] ?? '');
        $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
        
        // Pagination parameters
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = max(1, min(100, intval($_POST['per_page'] ?? 20))); // Limit to 100 items per page

        // Get all dynamic rules ordered by menu_order (include both published and draft)
        $args = [
            'post_type' => 'b2bking_rule',
            'post_status' => ['publish', 'draft'],
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Get all rules first to apply filters
        $rules = get_posts($args);
        $formatted_rules = [];

        // Get customer groups for names
        $customer_groups = get_posts([
            'post_type' => 'b2bking_group',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);
        
        $group_names = array();
        foreach ($customer_groups as $group) {
            $group_names[$group->ID] = $group->post_title;
        }

        foreach ($rules as $rule) {
            // Get rule meta for dynamic rules
            $enabled_meta = get_post_meta($rule->ID, 'b2bking_post_status_enabled', true);
            $rule_type = get_post_meta($rule->ID, 'b2bking_rule_what', true);
            $raise_price_meta = get_post_meta($rule->ID, 'b2bking_rule_raise_price', true);
            if ($raise_price_meta === 'yes' && $rule_type === 'discount_percentage') {
                $rule_type = 'raise_price';
            }
            
            // Check if rule_per_product is enabled - if so, use original values for display
            $rule_per_product = get_post_meta($rule->ID, 'b2bking_rule_per_product', true);
            $applies_to = get_post_meta($rule->ID, 'b2bking_rule_applies', true);
            $applies_to_multiple_options_original = null;
            
            if (intval($rule_per_product) === 1) {
                // Use original values for display
                $original_applies = get_post_meta($rule->ID, 'b2bking_rule_applies_original', true);
                $original_multiple = get_post_meta($rule->ID, 'b2bking_rule_applies_multiple_options_original', true);
                
                if (!empty($original_applies)) {
                    $applies_to = $original_applies;
                    // If original was multiple_options, use the original multiple options for display
                    if ($original_applies === 'multiple_options' && !empty($original_multiple)) {
                        $applies_to_multiple_options_original = $original_multiple;
                    }
                }
            }
            
            $customer_group = get_post_meta($rule->ID, 'b2bking_rule_who', true);
            $how_much = get_post_meta($rule->ID, 'b2bking_rule_howmuch', true);

            // Determine if rule is enabled based on post status (V1 logic)
            // Published = enabled, Draft = disabled
            $is_enabled = ($rule->post_status === 'publish');

            // Get rule type display name
            $rule_type_display = $this->get_rule_type_display($rule_type);
            
            // Get applies to display name - pass original multiple options if available
            $applies_to_display = $this->get_applies_to_display($applies_to, $rule->ID, $applies_to_multiple_options_original, $rule_type);
            
            // Get customer group display name
            $customer_group_display = $this->get_customer_group_display($customer_group, $group_names, $rule->ID);
            
            // Get how much display
            $how_much_display = $this->get_how_much_display($rule_type, $how_much, $rule->ID);

            // Apply filters
            if (!empty($rule_type_filter) && $rule_type !== $rule_type_filter) {
                continue;
            }
            
            if (!empty($applies_to_filter) && $applies_to !== $applies_to_filter) {
                continue;
            }
            
            if (!empty($customer_group_filter) && $customer_group !== $customer_group_filter) {
                continue;
            }
            
            if (!empty($status_filter)) {
                if (($status_filter === 'enabled' && !$is_enabled) || 
                    ($status_filter === 'disabled' && $is_enabled)) {
                    continue;
                }
            }
            
            $formatted_rules[] = [
                'id' => $rule->ID,
                'name' => $rule->post_title,
                'description' => $rule->post_content,
                'rule_type' => $rule_type,
                'rule_type_display' => $rule_type_display,
                'applies_to' => $applies_to,
                'applies_to_display' => $applies_to_display,
                'customer_group' => $customer_group,
                'customer_group_display' => $customer_group_display,
                'how_much' => $how_much,
                'how_much_display' => $how_much_display,
                'enabled' => $is_enabled,
                'post_status' => $rule->post_status
            ];
        }

        // Calculate stats and pagination
        $total_rules = count($formatted_rules);
        $active_rules = count(array_filter($formatted_rules, function($rule) {
            return $rule['enabled'];
        }));
        
        // Apply pagination to the filtered results
        $total_pages = ceil($total_rules / $per_page);
        $offset = ($page - 1) * $per_page;
        $paginated_rules = array_slice($formatted_rules, $offset, $per_page);

        wp_send_json_success([
            'rules' => $paginated_rules,
            'total_items' => $total_rules,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page,
            'stats' => [
                'total_rules' => $total_rules,
                'active_rules' => $active_rules,
                'total_transitions' => 0 // This would need to be calculated from actual transitions
            ]
        ]);
    }
    
    /**
     * AJAX: Toggle rule status
     */
    public function ajax_toggle_status() {
        // Check security nonce (same as V1 function)
        if (!wp_verify_nonce($_POST['security'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }

        // Capability check (same as V1 function)
        if (!current_user_can(apply_filters('b2bking_backend_capability_needed', 'manage_woocommerce'))) {
            wp_send_json_error('Failed capability check.');
            wp_die();
        }

        $enabled = strval($_POST['enabled']); // true or false
        $rule_id = intval($_POST['rule_id']);

        // Verify the post exists and is a dynamic rule
        $post = get_post($rule_id);
        if (!$post || $post->post_type !== 'b2bking_rule') {
            wp_send_json_error('Invalid rule.');
            wp_die();
        }

        // Use same logic as V1 b2bkingchangefield function
        if ($enabled === 'true') {
            // Set status to publish (enabled)
            b2bking()->update_status('publish', $rule_id);
        } else {
            // Set status to draft (disabled)
            b2bking()->update_status('draft', $rule_id);
        }

        // Regenerate calculations, clear caches etc.
        b2bking()->clear_caches_transients();
        require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
        B2bking_Admin::b2bking_calculate_rule_numbers_database();

        wp_send_json_success([
            'message' => $enabled === 'true' ? esc_html__('Rule enabled successfully!', 'b2bking') : esc_html__('Rule disabled successfully!', 'b2bking')
        ]);
    }
    
    /**
     * AJAX: Delete rule
     */
    public function ajax_delete_rule() {
        // Verify nonce (accept both dynamic rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_dynamic_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $rule_id = intval($_POST['rule_id'] ?? 0);
        
        if ($rule_id <= 0) {
            wp_send_json_error('Invalid rule ID');
        }
        
        $result = wp_delete_post($rule_id, true);
        
        if (!$result) {
            wp_send_json_error('Failed to delete rule');
        }

        // Regenerate calculations, clear caches etc.
        b2bking()->clear_caches_transients();
        require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
        B2bking_Admin::b2bking_calculate_rule_numbers_database();
        
        wp_send_json_success(array(
            'message' => esc_html__('Rule deleted successfully', 'b2bking')
        ));
    }
    
    /**
     * AJAX: Update rule order
     */
    public function ajax_update_order() {
        // Capability check
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Failed capability check.');
            wp_die();
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_dynamic_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }

        $order = $_POST['order'] ?? [];
        
        if (!is_array($order)) {
            wp_send_json_error('Invalid order data.');
            wp_die();
        }

        // Update menu_order for each rule using direct database update to avoid data loss
        global $wpdb;
        foreach ($order as $item) {
            $rule_id = intval($item['rule_id']);
            $new_order = intval($item['new_order']);
            
            // Verify the post exists and is a dynamic rule
            $post = get_post($rule_id);
            if ($post && $post->post_type === 'b2bking_rule') {
                // Use direct database update to only change menu_order, avoiding potential data loss
                $wpdb->update(
                    $wpdb->posts,
                    array('menu_order' => $new_order),
                    array('ID' => $rule_id),
                    array('%d'),
                    array('%d')
                );
            }
        }

        // Clear any caches
        wp_cache_flush();

        wp_send_json_success(array(
            'message' => esc_html__('Rule order updated successfully', 'b2bking')
        ));
    }
    
    /**
     * AJAX: Save rule
     */
    public function ajax_save_rule() {
        // Verify nonce (accept both dynamic rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_dynamic_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $rule_id = intval($_POST['rule_id'] ?? 0);
        $rule_name = sanitize_text_field($_POST['rule_name'] ?? '');
        $rule_what = sanitize_text_field($_POST['rule_what'] ?? '');
        $rule_applies = sanitize_text_field($_POST['rule_applies'] ?? '');
        $rule_applies_options = sanitize_text_field($_POST['rule_applies_options'] ?? '');
        $rule_who = sanitize_text_field($_POST['rule_who'] ?? '');
        $rule_who_options = sanitize_text_field($_POST['rule_who_multiple_options'] ?? '');
        $rule_howmuch = floatval($_POST['rule_howmuch'] ?? 0);
        $rule_discountname = sanitize_text_field($_POST['rule_discountname'] ?? '');
        $rule_show_everywhere = sanitize_text_field($_POST['rule_show_everywhere'] ?? '0');
        $rule_conditions = sanitize_text_field($_POST['rule_conditions'] ?? '');
        $rule_priority_raw = $_POST['rule_priority'] ?? '';
        $rule_priority = ($rule_priority_raw === '' || $rule_priority_raw === null) ? '' : intval($rule_priority_raw);
        
        // Payment method related fields
        $rule_paymentmethod = sanitize_text_field($_POST['rule_paymentmethod'] ?? '');
        $rule_paymentmethod_minmax = sanitize_text_field($_POST['rule_paymentmethod_minmax'] ?? '');
        $rule_paymentmethod_percentamount = sanitize_text_field($_POST['rule_paymentmethod_percentamount'] ?? '');
        $rule_paymentmethod_discountsurcharge = sanitize_text_field($_POST['rule_paymentmethod_discountsurcharge'] ?? '');
        $rule_paymentmethod_name = sanitize_text_field($_POST['rule_paymentmethod_name'] ?? '');
        
        // Shipping method field
        $rule_shippingmethod = sanitize_text_field($_POST['rule_shippingmethod'] ?? '');
        
        // Other fields
        $rule_quantity_value = sanitize_text_field($_POST['rule_quantity_value'] ?? '');
        $rule_currency = sanitize_text_field($_POST['rule_currency'] ?? '');
        $rule_countries = sanitize_text_field($_POST['rule_countries'] ?? '');
        $rule_requires = sanitize_text_field($_POST['rule_requires'] ?? '');
        $rule_showtax = sanitize_text_field($_POST['rule_showtax'] ?? '');
        $rule_tax_shipping = sanitize_text_field($_POST['rule_tax_shipping'] ?? '');
        $rule_tax_shipping_rate = sanitize_text_field($_POST['rule_tax_shipping_rate'] ?? '');
        $rule_taxname = sanitize_text_field($_POST['rule_taxname'] ?? '');
        $rule_tax_taxable = sanitize_text_field($_POST['rule_tax_taxable'] ?? '');
        $rule_per_product = sanitize_text_field($_POST['rule_per_product'] ?? '0');
        
        // Create or update rule
        if ($rule_id > 0) {
            // Update existing rule
            $post_data = array(
                'ID' => $rule_id,
                'post_title' => $rule_name,
                'post_status' => 'publish'
            );
            $result = wp_update_post($post_data);
        } else {
            // Create new rule - get the lowest menu_order from ALL rules (publish and draft) 
            // and subtract a safe amount to ensure it always appears first
            global $wpdb;
            // Query all rules regardless of status to get the true minimum
            $min_order = $wpdb->get_var("SELECT MIN(menu_order) FROM {$wpdb->posts} WHERE post_type = 'b2bking_rule' AND post_status IN ('publish', 'draft')");
            
            // If no rules exist, start at 0. Otherwise, subtract 100 to ensure new rule is always first
            // Using 100 instead of 1 prevents conflicts when multiple rules have negative values
            $new_order = ($min_order !== null) ? $min_order - 100 : 0;
            
            $post_data = array(
                'post_title' => $rule_name,
                'post_type' => 'b2bking_rule',
                'post_status' => 'publish',
                'menu_order' => $new_order
            );
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error('Failed to save rule');
        }
        
        $rule_id = $result;
        
        // Handle rule_per_product logic (same as legacy b2bking_rule_minimum_all)
        // This expands categories/tags/cart to individual products when rule applies to each product
        if ($rule_per_product !== NULL && ($rule_what === 'minimum_order' || $rule_what === 'maximum_order' || $rule_what === 'required_multiple')) {
            update_post_meta($rule_id, 'b2bking_rule_per_product', $rule_per_product);
            
            if (intval($rule_per_product) === 1) {
                // Save the regular content aside (original values from POST)
                $original_applies = $rule_applies;
                $original_multiple = $rule_applies_options;
                update_post_meta($rule_id, 'b2bking_rule_applies_original', $original_applies);
                update_post_meta($rule_id, 'b2bking_rule_applies_multiple_options_original', $original_multiple);
                
                // Build string of all product IDs
                $rule_per_product_string = '';
                
                if ($original_applies === 'cart_total' || $original_applies === 'cart') {
                    // Add all products to multiple options
                    $all_prods = new WP_Query(array(
                        'posts_per_page' => -1,
                        'post_type' => 'product',
                        'fields' => 'ids'
                    ));
                    $all_prod_ids = !empty($all_prods->posts) && is_object($all_prods->posts[0]) ? wp_list_pluck($all_prods->posts, 'ID') : $all_prods->posts; // Extract IDs if objects returned
                    foreach ($all_prod_ids as $prod_id) {
                        $rule_per_product_string .= 'product_' . $prod_id . ',';
                    }
                    // Remove last comma
                    $rule_per_product_string = substr($rule_per_product_string, 0, -1);
                } else if ($original_applies === 'multiple_options') {
                    // Multiple options already
                    $original_multiple_array = explode(',', $original_multiple);
                    foreach ($original_multiple_array as $multiple_element) {
                        $multiple_element = trim($multiple_element);
                        if (!empty($multiple_element)) {
                            $multiple_appliestemp = explode('_', $multiple_element);
                            if ($multiple_appliestemp[0] === 'category' || $multiple_appliestemp[0] === 'tag') {
                                // Get all products in that category and its subcategories
                                require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
                                $categories_list = B2bking_Admin::b2bking_get_all_categories_and_children($multiple_appliestemp[1]);
                                $products_list = B2bking_Admin::b2bking_get_all_products_in_category_list($categories_list);
                                foreach ($products_list as $prod_id) {
                                    $rule_per_product_string .= 'product_' . $prod_id . ',';
                                }
                            } else {
                                // Is product
                                $rule_per_product_string .= $multiple_element . ',';
                            }
                        }
                    }
                    // Remove last comma
                    $rule_per_product_string = substr($rule_per_product_string, 0, -1);
                } else {
                    // Handle category, tag, or product
                    $appliestemp = explode('_', $original_applies);
                    if ($appliestemp[0] === 'category' || $appliestemp[0] === 'tag') {
                        // Get all products in that category and its subcategories
                        require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
                        $categories_list = B2bking_Admin::b2bking_get_all_categories_and_children($appliestemp[1]);
                        $products_list = B2bking_Admin::b2bking_get_all_products_in_category_list($categories_list);
                        foreach ($products_list as $prod_id) {
                            $rule_per_product_string .= 'product_' . $prod_id . ',';
                        }
                        // Remove last comma
                        $rule_per_product_string = substr($rule_per_product_string, 0, -1);
                    } else if ($appliestemp[0] === 'product') {
                        $rule_per_product_string = $original_applies;
                    }
                }
                
                $rule_per_product_string = apply_filters('b2bking_save_rule_minmaxmultiple', $rule_per_product_string);
                
                // Update rule_applies and rule_applies_multiple_options to use the product list
                $rule_applies = 'multiple_options';
                $rule_applies_options = $rule_per_product_string;
            }
        } else {
            update_post_meta($rule_id, 'b2bking_rule_per_product', '');
        }
        
        // Save meta data for dynamic rules
        $is_raise_price_rule = ($rule_what === 'raise_price');
        $stored_rule_type = $is_raise_price_rule ? 'discount_percentage' : $rule_what;
        update_post_meta($rule_id, 'b2bking_rule_what', $stored_rule_type);
        update_post_meta($rule_id, 'b2bking_rule_raise_price', $is_raise_price_rule ? 'yes' : 'no');
        
        update_post_meta($rule_id, 'b2bking_rule_applies', $rule_applies);
        update_post_meta($rule_id, 'b2bking_rule_applies_multiple_options', $rule_applies_options);

        update_post_meta($rule_id, 'b2bking_rule_who_original', $rule_who);

        if ($rule_who === 'specific_users'){
            $rule_who = 'multiple_options';
        }
        
        update_post_meta($rule_id, 'b2bking_rule_who', $rule_who);
        update_post_meta($rule_id, 'b2bking_rule_who_multiple_options', $rule_who_options);
        
        update_post_meta($rule_id, 'b2bking_rule_howmuch', $rule_howmuch);
        update_post_meta($rule_id, 'b2bking_rule_discountname', $rule_discountname);
        update_post_meta($rule_id, 'b2bking_rule_discount_show_everywhere', $rule_show_everywhere);
        update_post_meta($rule_id, 'b2bking_rule_conditions', $rule_conditions);
        update_post_meta($rule_id, 'b2bking_standard_rule_priority', $rule_priority);
        update_post_meta($rule_id, 'b2bking_rule_priority', $rule_priority);
        
        // Save payment method related fields
        update_post_meta($rule_id, 'b2bking_rule_paymentmethod', $rule_paymentmethod);
        update_post_meta($rule_id, 'b2bking_rule_paymentmethod_minmax', $rule_paymentmethod_minmax);
        update_post_meta($rule_id, 'b2bking_rule_paymentmethod_percentamount', $rule_paymentmethod_percentamount);
        update_post_meta($rule_id, 'b2bking_rule_paymentmethod_discountsurcharge', $rule_paymentmethod_discountsurcharge);
        
        update_post_meta($rule_id, 'b2bking_rule_shippingmethod', $rule_shippingmethod);
        
        // Save other fields
        update_post_meta($rule_id, 'b2bking_rule_quantity_value', $rule_quantity_value);
        update_post_meta($rule_id, 'b2bking_rule_currency', $rule_currency);
        update_post_meta($rule_id, 'b2bking_rule_countries', $rule_countries);
        update_post_meta($rule_id, 'b2bking_rule_requires', $rule_requires);
        update_post_meta($rule_id, 'b2bking_rule_showtax', $rule_showtax);
        update_post_meta($rule_id, 'b2bking_rule_tax_shipping', $rule_tax_shipping);
        update_post_meta($rule_id, 'b2bking_rule_tax_shipping_rate', $rule_tax_shipping_rate);
        update_post_meta($rule_id, 'b2bking_rule_tax_taxable', $rule_tax_taxable);
        // Note: b2bking_rule_per_product is saved in the rule_per_product logic block above
        
        // Tax name (also used for payment method rename)
        update_post_meta($rule_id, 'b2bking_rule_taxname', $rule_taxname);
        // Payment method name also saves to taxname meta key
        if ($stored_rule_type !== 'add_tax_percentage' && $stored_rule_type !== 'add_tax_amount'){
            update_post_meta($rule_id, 'b2bking_rule_taxname', $rule_paymentmethod_name);
        }
        
        
        // Handle price tiers data (for tiered_price rule type)
        $price_tiers_quantity = isset($_POST['rule_price_tiers_quantity']) ? $_POST['rule_price_tiers_quantity'] : array();
        $price_tiers_price = isset($_POST['rule_price_tiers_price']) ? $_POST['rule_price_tiers_price'] : array();
        
        if (!empty($price_tiers_quantity) && !empty($price_tiers_price) && count($price_tiers_quantity) === count($price_tiers_price)) {
            $pricetiersstring = '';
            foreach ($price_tiers_quantity as $index => $quantity) {
                if (!empty($quantity) && isset($price_tiers_price[$index]) && !empty($price_tiers_price[$index])) {
                    // Sanitize quantity and price
                    $quantity = floatval($quantity);
                    $price = sanitize_text_field($price_tiers_price[$index]);
                    // Convert price to float, handling decimal separators
                    $price_float = b2bking()->tofloat($price);
                    
                    if ($quantity > 0 && $price_float > 0) {
                        $pricetiersstring .= $quantity . ':' . $price_float . ';';
                    }
                }
            }
            // Remove trailing semicolon
            $pricetiersstring = rtrim($pricetiersstring, ';');
            update_post_meta($rule_id, 'b2bking_product_pricetiers_group_b2c', $pricetiersstring);
        } else {
            // If no valid tiers, clear the meta
            update_post_meta($rule_id, 'b2bking_product_pricetiers_group_b2c', '');
        }
        
        // Handle info table rows data (for info_table rule type)
        $info_table_rows_label = isset($_POST['rule_info_table_rows_label']) ? $_POST['rule_info_table_rows_label'] : array();
        $info_table_rows_text = isset($_POST['rule_info_table_rows_text']) ? $_POST['rule_info_table_rows_text'] : array();
        
        if (!empty($info_table_rows_label) && !empty($info_table_rows_text) && count($info_table_rows_label) === count($info_table_rows_text)) {
            $customrowsstring = '';
            foreach ($info_table_rows_label as $index => $label) {
                if (!empty($label) && isset($info_table_rows_text[$index]) && !empty($info_table_rows_text[$index])) {
                    // Sanitize label and text
                    $label_sanitized = sanitize_text_field($label);
                    $text_sanitized = sanitize_text_field($info_table_rows_text[$index]);
                    
                    if (!empty($label_sanitized) && !empty($text_sanitized)) {
                        $customrowsstring .= $label_sanitized . ':' . $text_sanitized . ';';
                    }
                }
            }
            // Remove trailing semicolon
            $customrowsstring = rtrim($customrowsstring, ';');
            update_post_meta($rule_id, 'b2bking_product_customrows_group_b2c', $customrowsstring);
        } else {
            // If no valid rows, clear the meta
            update_post_meta($rule_id, 'b2bking_product_customrows_group_b2c', '');
        }
        
        update_post_meta($rule_id, 'b2bking_post_status_enabled', 1);
        
        // Regenerate calculations, clear caches etc. (same as V1 function)
        b2bking()->clear_caches_transients();
        require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
        B2bking_Admin::b2bking_calculate_rule_numbers_database();
        
        wp_send_json_success(array(
            'message' => esc_html__('Rule saved successfully', 'b2bking'),
            'rule_id' => $rule_id
        ));
    }
    
    /**
     * AJAX: Bulk enable rules
     */
    public function ajax_bulk_enable() {
        // Verify nonce (accept both dynamic rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_dynamic_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $rule_ids = isset($_POST['rule_ids']) ? array_map('intval', $_POST['rule_ids']) : array();
        
        if (empty($rule_ids)) {
            wp_send_json_error('No rules selected');
        }
        
        foreach ($rule_ids as $rule_id) {
            // Use same logic as V1 b2bkingchangefield function
            b2bking()->update_status('publish', $rule_id);
        }
        
        // Regenerate calculations, clear caches etc. (same as V1 function)
        b2bking()->clear_caches_transients();
        require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
        B2bking_Admin::b2bking_calculate_rule_numbers_database();
        
        wp_send_json_success(array(
            'message' => esc_html__('Rules enabled successfully', 'b2bking')
        ));
    }
    
    /**
     * AJAX: Bulk disable rules
     */
    public function ajax_bulk_disable() {
        // Verify nonce (accept both dynamic rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_dynamic_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $rule_ids = isset($_POST['rule_ids']) ? array_map('intval', $_POST['rule_ids']) : array();
        
        if (empty($rule_ids)) {
            wp_send_json_error('No rules selected');
        }
        
        foreach ($rule_ids as $rule_id) {
            // Use same logic as V1 b2bkingchangefield function
            b2bking()->update_status('draft', $rule_id);
        }
        
        // Regenerate calculations, clear caches etc. 
        b2bking()->clear_caches_transients();
        require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
        B2bking_Admin::b2bking_calculate_rule_numbers_database();
        
        wp_send_json_success(array(
            'message' => esc_html__('Rules disabled successfully', 'b2bking')
        ));
    }
    
    /**
     * AJAX: Bulk delete rules
     */
    public function ajax_bulk_delete() {
        // Verify nonce (accept both dynamic rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_dynamic_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $rule_ids = isset($_POST['rule_ids']) ? array_map('intval', $_POST['rule_ids']) : array();
        
        if (empty($rule_ids)) {
            wp_send_json_error('No rules selected');
        }
        
        foreach ($rule_ids as $rule_id) {
            wp_delete_post($rule_id, true);
        }

        // Regenerate calculations, clear caches etc.
        b2bking()->clear_caches_transients();
        require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
        B2bking_Admin::b2bking_calculate_rule_numbers_database();
        
        wp_send_json_success(array(
            'message' => esc_html__('Rules deleted successfully', 'b2bking')
        ));
    }
    
    /**
     * Format condition display text with proper operators
     * This is the single source of truth for condition display formatting
     */
    private function format_condition_display($condition, $operator, $rolling_days = null) {
        // Step 1: Get the base condition text (without operators)
        $base_text = $this->get_condition_base_text($condition);
        
        // Step 2: Add rolling days if it's a rolling period condition
        if (($condition === 'spent_rolling' || $condition === 'order_count_rolling' || 
             strpos($condition, '_rolling') !== false) && !empty($rolling_days)) {
            $base_text .= ' (' . intval($rolling_days) . ' days)';
        }
        
        // Step 3: Determine the operator symbol
        $operator_symbol = $this->get_operator_symbol($condition, $operator);
        
        // Step 4: Combine them
        return $base_text . $operator_symbol;
    }
    
    /**
     * Get the base text for a condition (without operators)
     */
    private function get_condition_base_text($condition) {
        $condition_map = array(
            // Old format conditions
            'order_value_total' => esc_html__('Total Spent (All-Time)', 'b2bking'),
            'order_value_yearly_higher' => esc_html__('Last Year Spent', 'b2bking'),
            'order_value_yearly_lower' => esc_html__('Last Year Spent', 'b2bking'),
            'order_value_monthly_higher' => esc_html__('Last Month Spent', 'b2bking'),
            'order_value_monthly_lower' => esc_html__('Last Month Spent', 'b2bking'),
            'order_value_quarterly_higher' => esc_html__('Last Quarter Spent', 'b2bking'),
            'order_value_quarterly_lower' => esc_html__('Last Quarter Spent', 'b2bking'),
            'order_value_current_year_higher' => esc_html__('This Year Spent', 'b2bking'),
            'order_value_current_year_lower' => esc_html__('This Year Spent', 'b2bking'),
            'order_value_current_month_higher' => esc_html__('This Month Spent', 'b2bking'),
            'order_value_current_month_lower' => esc_html__('This Month Spent', 'b2bking'),
            'order_value_current_quarter_higher' => esc_html__('This Quarter Spent', 'b2bking'),
            'order_value_current_quarter_lower' => esc_html__('This Quarter Spent', 'b2bking'),
            'order_value_rolling_higher' => esc_html__('Rolling Period Spent', 'b2bking'),
            'order_value_rolling_lower' => esc_html__('Rolling Period Spent', 'b2bking'),
            
            // New format conditions
            'total_spent' => esc_html__('Total Spent (All-Time)', 'b2bking'),
            'spent_yearly' => esc_html__('Last Year Spent', 'b2bking'),
            'spent_monthly' => esc_html__('Last Month Spent', 'b2bking'),
            'spent_quarterly' => esc_html__('Last Quarter Spent', 'b2bking'),
            'spent_current_year' => esc_html__('This Year Spent', 'b2bking'),
            'spent_current_month' => esc_html__('This Month Spent', 'b2bking'),
            'spent_current_quarter' => esc_html__('This Quarter Spent', 'b2bking'),
            'spent_rolling' => esc_html__('Rolling Period Spent', 'b2bking'),
            'order_count_total' => esc_html__('Total Orders', 'b2bking'),
            'order_count_yearly' => esc_html__('Last Year Orders', 'b2bking'),
            'order_count_monthly' => esc_html__('Last Month Orders', 'b2bking'),
            'order_count_quarterly' => esc_html__('Last Quarter Orders', 'b2bking'),
            'order_count_current_year' => esc_html__('This Year Orders', 'b2bking'),
            'order_count_current_month' => esc_html__('This Month Orders', 'b2bking'),
            'order_count_current_quarter' => esc_html__('This Quarter Orders', 'b2bking'),
            'order_count_rolling' => esc_html__('Rolling Period Orders', 'b2bking'),
            'days_since_first_order' => esc_html__('Days Since First Order', 'b2bking'),
            'days_since_last_order' => esc_html__('Days Since Last Order', 'b2bking'),
            
            // Cart conditions
            'cart_total_quantity' => esc_html__('Cart Total Quantity', 'b2bking'),
            'cart_total_value' => esc_html__('Cart Total Value', 'b2bking'),
            'category_product_quantity' => esc_html__('Category Product Quantity', 'b2bking'),
            'category_product_value' => esc_html__('Category Product Value', 'b2bking'),
            'product_quantity' => esc_html__('Product Quantity', 'b2bking'),
            'product_value' => esc_html__('Product Value', 'b2bking'),
        );
        
        return isset($condition_map[$condition]) ? $condition_map[$condition] : ucfirst(str_replace('_', ' ', $condition));
    }
    
    /**
     * Get the operator symbol for a condition
     */
    private function get_operator_symbol($condition, $operator) {
        // ALWAYS use the explicit operator from the database if it exists
        // This ensures that database operators take priority over embedded condition operators
        if (!empty($operator)) {
            $operator_map = array(
                'greater' => ' > ',
                'greater_equal' => ' ≥ ',
                'less' => ' < ',
                'less_equal' => ' ≤ ',
                'equal' => ' = ',
                'smaller' => ' < ',
                'between' => ' between ',
            );
            
            return isset($operator_map[$operator]) ? $operator_map[$operator] : ' > ';
        }
        
        // Only fall back to embedded operators if no explicit operator is set
        if (strpos($condition, '_higher') !== false) {
            return ' > ';
        } elseif (strpos($condition, '_lower') !== false) {
            return ' < ';
        }
        
        // Default operator
        return ' > ';
    }
    
    /**
     * Get rule type display name
     */
    private function get_rule_type_display($rule_type) {
        $rule_types = array(
            'discount_percentage' => esc_html__('Discount (Percentage)', 'b2bking'),
            'discount_amount' => esc_html__('Discount (Amount)', 'b2bking'),
            'raise_price' => esc_html__('Raise Price (Percentage)', 'b2bking'),
            'bogo_discount' => esc_html__('Buy X Get 1 Free', 'b2bking'),
            'fixed_price' => esc_html__('Fixed Price', 'b2bking'),
            'hidden_price' => esc_html__('Hidden Price', 'b2bking'),
            'tiered_price' => esc_html__('Tiered Price', 'b2bking'),
            'free_shipping' => esc_html__('Free Shipping', 'b2bking'),
            'minimum_order' => esc_html__('Minimum Order', 'b2bking'),
            'maximum_order' => esc_html__('Maximum Order', 'b2bking'),
            'required_multiple' => esc_html__('Required Multiple (Quantity Step)', 'b2bking'),
            'unpurchasable' => esc_html__('Non-Purchasable', 'b2bking'),
            'tax_exemption_user' => esc_html__('Tax Exemption', 'b2bking'),
            'tax_exemption' => esc_html__('Zero Tax Product', 'b2bking'),
            'add_tax_percentage' => esc_html__('Add Tax / Fee (Percentage)', 'b2bking'),
            'add_tax_amount' => esc_html__('Add Tax / Fee (Amount)', 'b2bking'),
            'replace_prices_quote' => esc_html__('Replace Cart with Quote System', 'b2bking'),
            'quotes_products' => esc_html__('Quotes on Specific Products', 'b2bking'),
            'set_currency_symbol' => esc_html__('Set Currency', 'b2bking'),
            'payment_method_minmax_order' => esc_html__('Payment Method Min / Max Order', 'b2bking'),
            'payment_method_discount' => esc_html__('Payment Method Discount / Surcharge', 'b2bking'),
            'payment_method_restriction' => esc_html__('Payment Method Product Restriction', 'b2bking'),
            'shipping_method_restriction' => esc_html__('Shipping Method Product Restriction', 'b2bking'),
            'rename_purchase_order' => esc_html__('Rename Payment Method', 'b2bking'),
            'info_table' => esc_html__('Add to Information Table', 'b2bking'),
        );
        
        return isset($rule_types[$rule_type]) ? $rule_types[$rule_type] : ucfirst(str_replace('_', ' ', $rule_type));
    }
    
    /**
     * Get applies to display name
     * @param string $applies_to The applies_to value
     * @param int|null $rule_id The rule ID (optional)
     * @param string|null $original_multiple_options The original multiple options string (used when rule_per_product is enabled)
     * @param string|null $rule_type The rule type (optional)
     */
    private function get_applies_to_display($applies_to, $rule_id = null, $original_multiple_options = null, $rule_type = null) {
        // For tax exemption rules, show em dash as they don't apply to specific products
        if ($rule_type === 'tax_exemption_user') {
            return '—';
        }
        
        // For replace_prices_quote rules, show em dash as they apply to all products globally
        if ($rule_type === 'replace_prices_quote') {
            return '—';
        }
        
        // For rename payment method rules, show the payment method name
        if ($rule_type === 'rename_purchase_order' && $rule_id) {
            $payment_method_id = get_post_meta($rule_id, 'b2bking_rule_paymentmethod', true);
            if (!empty($payment_method_id) && class_exists('WC_Payment_Gateways')) {
                $payment_gateways = WC()->payment_gateways->payment_gateways();
                if (isset($payment_gateways[$payment_method_id])) {
                    $payment_method = $payment_gateways[$payment_method_id];
                    if (isset($payment_method->title) && !empty($payment_method->title)) {
                        return $payment_method->title;
                    } elseif (method_exists($payment_method, 'get_title')) {
                        return $payment_method->get_title();
                    }
                }
            }
            return '—';
        }
        
        // For payment method min/max order rules, show the payment method name
        if ($rule_type === 'payment_method_minmax_order' && $rule_id) {
            $payment_method_id = get_post_meta($rule_id, 'b2bking_rule_paymentmethod', true);
            if (!empty($payment_method_id) && class_exists('WC_Payment_Gateways')) {
                $payment_gateways = WC()->payment_gateways->payment_gateways();
                if (isset($payment_gateways[$payment_method_id])) {
                    $payment_method = $payment_gateways[$payment_method_id];
                    if (isset($payment_method->title) && !empty($payment_method->title)) {
                        return $payment_method->title;
                    } elseif (method_exists($payment_method, 'get_title')) {
                        return $payment_method->get_title();
                    }
                }
            }
            return '—';
        }
        
        // For payment method discount/surcharge rules, show the payment method name
        if ($rule_type === 'payment_method_discount' && $rule_id) {
            $payment_method_id = get_post_meta($rule_id, 'b2bking_rule_paymentmethod', true);
            if (!empty($payment_method_id) && class_exists('WC_Payment_Gateways')) {
                $payment_gateways = WC()->payment_gateways->payment_gateways();
                if (isset($payment_gateways[$payment_method_id])) {
                    $payment_method = $payment_gateways[$payment_method_id];
                    if (isset($payment_method->title) && !empty($payment_method->title)) {
                        return $payment_method->title;
                    } elseif (method_exists($payment_method, 'get_title')) {
                        return $payment_method->get_title();
                    }
                }
            }
            return '—';
        }
        
        $use_brands_taxonomy = intval(get_option('b2bking_use_brands_taxonomy_setting', 0)) === 1;
        $tag_taxonomy = apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag');
        $tag_label_prefix = $use_brands_taxonomy ? esc_html__('Brand #', 'b2bking') : esc_html__('Tag #', 'b2bking');
        
        if (strpos($applies_to, 'product_') === 0) {
            $product_id = str_replace('product_', '', $applies_to);
            $product = wc_get_product($product_id);
            return $product ? $product->get_name() : sprintf(esc_html__('Product #%s', 'b2bking'), $product_id);
        } elseif (strpos($applies_to, 'category_') === 0) {
            $category_id = str_replace('category_', '', $applies_to);
            $category = get_term($category_id, 'product_cat');
            return $category ? $category->name : sprintf(esc_html__('Category #%s', 'b2bking'), $category_id);
        } elseif (strpos($applies_to, 'tag_') === 0) {
            $tag_id = str_replace('tag_', '', $applies_to);
            $tag = get_term($tag_id, $tag_taxonomy);
            return ($tag && !is_wp_error($tag)) ? $tag->name : $tag_label_prefix . $tag_id;
        } elseif ($applies_to === 'cart_total' || $applies_to === 'cart') {
            return esc_html__('Cart Total / All Products', 'b2bking');
        } elseif ($applies_to === 'multiple_options') {
            // If original_multiple_options is provided (from rule_per_product), use that for display
            $multiple_options = $original_multiple_options;
            
            // Otherwise, if rule_id is provided, get from meta
            if (empty($multiple_options) && $rule_id) {
                $multiple_options = get_post_meta($rule_id, 'b2bking_rule_applies_multiple_options', true);
            }
            
            if (!empty($multiple_options)) {
                $selected_items = explode(',', $multiple_options);
                $selected_items = array_map('trim', $selected_items);
                $selected_items = array_filter($selected_items);
                
                $item_names = array();
                foreach ($selected_items as $item) {
                    if (strpos($item, 'product_') === 0) {
                        $product_id = str_replace('product_', '', $item);
                        $product = wc_get_product($product_id);
                        if ($product) {
                            $item_names[] = $product->get_name();
                        } else {
                            $item_names[] = sprintf(esc_html__('Product #%s', 'b2bking'), $product_id);
                        }
                    } elseif (strpos($item, 'category_') === 0) {
                        $category_id = str_replace('category_', '', $item);
                        $category = get_term($category_id, 'product_cat');
                        if ($category) {
                            $item_names[] = $category->name;
                        } else {
                            $item_names[] = sprintf(esc_html__('Category #%s', 'b2bking'), $category_id);
                        }
                    } elseif (strpos($item, 'tag_') === 0) {
                        $tag_id = str_replace('tag_', '', $item);
                        $tag = get_term($tag_id, $tag_taxonomy);
                        if ($tag && !is_wp_error($tag)) {
                            $item_names[] = $tag->name;
                        } else {
                            $item_names[] = $tag_label_prefix . $tag_id;
                        }
                    }
                }
                if (!empty($item_names)) {
                    $total = count($item_names);
                    if ($total <= 2) {
                        return implode(', ', $item_names);
                    }
                    $remaining = $total - 2;
                    $first_two = array_slice($item_names, 0, 2);
                    return implode(', ', $first_two) . ', ' . sprintf(esc_html__('+%d more', 'b2bking'), $remaining);
                }
            }
            return esc_html__('Specific Items', 'b2bking');
        } elseif ($applies_to === 'excluding_multiple_options') {
            return esc_html__('All Products Except...', 'b2bking');
        } elseif ($applies_to === 'one_time') {
            return esc_html__('One Time Fee / Tax', 'b2bking');
        }
        
        return ucfirst(str_replace('_', ' ', $applies_to));
    }
    
    /**
     * Get customer group display name
     */
    private function get_customer_group_display($customer_group, $group_names, $rule_id = null) {

        if ($customer_group === 'multiple_options'){
            $rule_who_original = get_post_meta($rule_id, 'b2bking_rule_who_original', true);
            if ($rule_who_original === 'specific_users'){
                $customer_group = 'specific_users';
            }
        }

        if ($customer_group === 'all_registered') {
            return esc_html__('All Logged-in Users', 'b2bking');
        } elseif ($customer_group === 'everyone_registered_b2b') {
            return esc_html__('B2B Customers', 'b2bking');
        } elseif ($customer_group === 'everyone_registered_b2c') {
            return esc_html__('B2C Customers', 'b2bking');
        } elseif ($customer_group === 'user_0') {
            return esc_html__('Guest Visitors', 'b2bking');
        } elseif (strpos($customer_group, 'group_') === 0) {
            $group_id = str_replace('group_', '', $customer_group);
            return $group_names[$group_id] ?? esc_html__('Unknown Group', 'b2bking');
        } elseif ($customer_group === 'multiple_options') {
            // If rule_id is provided, check if we should show specific audience names
            if ($rule_id) {
                $who_multiple_options = get_post_meta($rule_id, 'b2bking_rule_who_multiple_options', true);
                if (!empty($who_multiple_options)) {
                    $selected_audiences = explode(',', $who_multiple_options);
                    $selected_audiences = array_map('trim', $selected_audiences);
                    $selected_audiences = array_filter($selected_audiences);
                    
                    $audience_names = array();
                    foreach ($selected_audiences as $audience) {
                        if ($audience === 'all_registered') {
                            $audience_names[] = esc_html__('All logged-in users', 'b2bking');
                        } elseif ($audience === 'everyone_registered_b2b') {
                            $audience_names[] = esc_html__('B2B customers', 'b2bking');
                        } elseif ($audience === 'everyone_registered_b2c') {
                            $audience_names[] = esc_html__('B2C customers', 'b2bking');
                        } elseif ($audience === 'user_0') {
                            $audience_names[] = esc_html__('Guest visitors', 'b2bking');
                        } elseif (strpos($audience, 'group_') === 0) {
                            $group_id = str_replace('group_', '', $audience);
                            $audience_names[] = $group_names[$group_id] ?? esc_html__('Unknown Group', 'b2bking');
                        }
                    }
                    if (!empty($audience_names)) {
                        $total = count($audience_names);
                        if ($total <= 2) {
                            return implode(', ', $audience_names);
                        }
                        $remaining = $total - 2;
                        $first_two = array_slice($audience_names, 0, 2);
                        return implode(', ', $first_two) . ', ' . sprintf(esc_html__('+%d more', 'b2bking'), $remaining);
                    }
                }
            }
            return esc_html__('Multiple Audiences', 'b2bking');
        } elseif ($customer_group === 'specific_users') {
            // If rule_id is provided, check if we should show specific user names
            if ($rule_id) {
                $who_multiple_options = get_post_meta($rule_id, 'b2bking_rule_who_multiple_options', true);
                if (!empty($who_multiple_options)) {
                    $selected_users = explode(',', $who_multiple_options);
                    $selected_users = array_map('trim', $selected_users);
                    $selected_users = array_filter($selected_users);
                    
                    $user_names = array();
                    foreach ($selected_users as $user_item) {
                        // Handle user_ prefix format
                        $user_id = $user_item;
                        if (strpos($user_item, 'user_') === 0) {
                            $user_id = str_replace('user_', '', $user_item);
                        }
                        
                        if (!empty($user_id) && is_numeric($user_id)) {
                            $user = get_user_by('ID', $user_id);
                            if ($user) {
                                $company = get_user_meta($user_id, 'billing_company', true);
                                $display_text = $user->display_name;
                                if (!empty($company)) {
                                    $display_text .= ' - ' . $company;
                                }
                                $user_names[] = $display_text;
                            } else {
                                $user_names[] = sprintf(esc_html__('User #%s', 'b2bking'), $user_id);
                            }
                        }
                    }
                    if (!empty($user_names)) {
                        $total = count($user_names);
                        if ($total <= 2) {
                            return implode(', ', $user_names);
                        }
                        $remaining = $total - 2;
                        $first_two = array_slice($user_names, 0, 2);
                        return implode(', ', $first_two) . ', ' . sprintf(esc_html__('+%d more', 'b2bking'), $remaining);
                    }
                }
            }
            return esc_html__('Specific Users', 'b2bking');
        }
        
        return ucfirst(str_replace('_', ' ', $customer_group));
    }
    
    /**
     * Get how much display
     */
    private function get_how_much_display($rule_type, $how_much, $rule_id = null) {
        // For tiered_price rule, calculate min/max from price tiers
        if ($rule_type === 'tiered_price' && $rule_id) {
            $price_tiers = get_post_meta($rule_id, 'b2bking_product_pricetiers_group_b2c', true);
            if (!empty($price_tiers)) {
                $price_tiers_array = explode(';', $price_tiers);
                $prices = array();
                
                foreach ($price_tiers_array as $tier) {
                    if (!empty($tier)) {
                        $tier_values = explode(':', $tier);
                        if (count($tier_values) >= 2) {
                            $price = b2bking()->tofloat($tier_values[1]);
                            if ($price > 0) {
                                $prices[] = $price;
                            }
                        }
                    }
                }
                
                if (!empty($prices)) {
                    $min_price = min($prices);
                    $max_price = max($prices);
                    
                    // Check if percentage setting is enabled
                    $use_percentage = intval(get_option('b2bking_enter_percentage_tiered_setting', 0)) === 1;
                    
                    if ($use_percentage) {
                        // Format as percentage range
                        $min_formatted = number_format($min_price, 2, '.', '');
                        $max_formatted = number_format($max_price, 2, '.', '');
                        return $min_formatted . '% - ' . $max_formatted . '%';
                    } else {
                        // Format as amount range
                        if (function_exists('wc_price')) {
                            $min_formatted = wc_price($min_price);
                            $max_formatted = wc_price($max_price);
                            // Remove HTML tags and extract just the price value
                            $min_value = strip_tags($min_formatted);
                            $max_value = strip_tags($max_formatted);
                            return $min_value . '-' . $max_value;
                        } else {
                            return '$' . number_format($min_price, 2, '.', '') . '-$' . number_format($max_price, 2, '.', '');
                        }
                    }
                }
            }
            return '—';
        }
        
        // For set currency rule, get the currency from meta
        if ($rule_type === 'set_currency_symbol' && $rule_id) {
            $currency = get_post_meta($rule_id, 'b2bking_rule_currency', true);
            if (!empty($currency)) {
                return $currency;
            }
            return '—';
        }
        
        // For rename payment method rule, get the new method name from meta
        if ($rule_type === 'rename_purchase_order' && $rule_id) {
            $payment_method_name = get_post_meta($rule_id, 'b2bking_rule_taxname', true);
            if (!empty($payment_method_name)) {
                return $payment_method_name;
            }
            return '—';
        }
        
        // For free_shipping rule, show condition if only 1 condition is set
        if ($rule_type === 'free_shipping' && $rule_id) {
            $conditions = get_post_meta($rule_id, 'b2bking_rule_conditions', true);
            if (!empty($conditions)) {
                $conditions_array = explode('|', $conditions);
                $valid_conditions = array();
                
                // Parse conditions (format: "name;operator;number")
                foreach ($conditions_array as $condition) {
                    if (!empty(trim($condition))) {
                        $parts = explode(';', $condition);
                        if (count($parts) >= 3) {
                            $valid_conditions[] = array(
                                'name' => trim($parts[0]),
                                'operator' => trim($parts[1]),
                                'number' => trim($parts[2])
                            );
                        }
                    }
                }
                
                // If exactly 1 condition, format and return it
                if (count($valid_conditions) === 1) {
                    $condition = $valid_conditions[0];
                    $base_text = $this->get_condition_base_text($condition['name']);
                    $operator_symbol = $this->get_operator_symbol($condition['name'], $condition['operator']);
                    return $base_text . $operator_symbol . $condition['number'];
                }
            }
            return '—';
        }
        
        if (empty($how_much)) {
            return '—';
        }
        
        // For percentage-based rules
        if (in_array($rule_type, ['discount_percentage', 'raise_price', 'add_tax_percentage'])) {
            return $how_much . '%';
        }
        
        // For amount-based rules
        if (in_array($rule_type, ['discount_amount', 'add_tax_amount', 'minimum_order', 'maximum_order'])) {
            return function_exists('wc_price') ? wc_price($how_much) : '$' . $how_much;
        }
        
        // For fixed price
        if ($rule_type === 'fixed_price') {
            return function_exists('wc_price') ? wc_price($how_much) : '$' . $how_much;
        }
        
        // For quantity-based rules
        if (in_array($rule_type, ['required_multiple', 'bogo_discount'])) {
            return $how_much . ' units';
        }
        
        return $how_much;
    }
    
    /**
     * Convert new condition format to old format for backward compatibility
     * Note: Order count and days conditions are NOT converted to old format since the old system doesn't support them
     */
    private function convert_new_condition_to_old($new_condition, $operator) {
        // Only convert amount spent conditions to old format
        // Order count and days conditions should stay in new format
        $condition_mapping = array(
            'total_spent' => 'order_value_total',
            'spent_yearly' => 'order_value_yearly_' . ($operator === 'less' ? 'lower' : 'higher'),
            'spent_monthly' => 'order_value_monthly_' . ($operator === 'less' ? 'lower' : 'higher'),
            'spent_quarterly' => 'order_value_quarterly_' . ($operator === 'less' ? 'lower' : 'higher'),
            'spent_current_year' => 'order_value_current_year_' . ($operator === 'less' ? 'lower' : 'higher'),
            'spent_current_month' => 'order_value_current_month_' . ($operator === 'less' ? 'lower' : 'higher'),
            'spent_current_quarter' => 'order_value_current_quarter_' . ($operator === 'less' ? 'lower' : 'higher'),
            'spent_rolling' => 'order_value_rolling_' . ($operator === 'less' ? 'lower' : 'higher'),
            // Order count conditions: keep in new format (old system doesn't support them)
            'order_count_total' => 'order_count_total',
            'order_count_yearly' => 'order_count_yearly',
            'order_count_monthly' => 'order_count_monthly',
            'order_count_quarterly' => 'order_count_quarterly',
            'order_count_current_year' => 'order_count_current_year',
            'order_count_current_month' => 'order_count_current_month',
            'order_count_current_quarter' => 'order_count_current_quarter',
            'order_count_rolling' => 'order_count_rolling',
            // Days conditions: keep in new format (old system doesn't support them)
            'days_since_first_order' => 'days_since_first_order',
            'days_since_last_order' => 'days_since_last_order',
        );
        
        return isset($condition_mapping[$new_condition]) ? $condition_mapping[$new_condition] : $new_condition;
    }
}

// Initialize the module
B2BKing_Dynamic_Rules_Pro::get_instance();