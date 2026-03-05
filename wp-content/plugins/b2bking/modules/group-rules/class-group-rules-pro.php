<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class B2BKing_Group_Rules_Pro {

    const SLUG = 'b2bking_group_rules_pro';
    const EDITOR_SLUG = 'b2bking_group_rule_pro_editor';
    const LOG_SLUG = 'b2bking_group_rules_pro_log';
	const RULES_CACHE_KEY = 'b2bking_grpro_rules_all';
	const RULES_CACHE_TTL = 3600; // 60 minutes
	const SPENT_TTL = 21600; // 6 hours
	const COUNT_TTL = 21600; // 6 hours

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

        // Frontend/hooks used by PRO rules engine
        add_action('woocommerce_order_status_changed', array($this, 'on_order_status_changed'), 10, 4);

		// Periodic cron (batch apply periodic rules to all users)
		add_action('init', array($this, 'maybe_schedule_periodic_cron'));
		add_action('b2bking_grpro_periodic_cron', array($this, 'run_periodic_cron_batch'));

        // Log group changes when rules are applied
        add_action('b2bking_group_rules_pro_applied', array($this, 'log_rule_applied'), 10, 5);

        // AJAX handlers
        add_action('wp_ajax_b2bking_group_rules_pro_load_rules', array($this, 'ajax_load_rules'));
        add_action('wp_ajax_b2bking_group_rules_pro_toggle_status', array($this, 'ajax_toggle_status'));
        add_action('wp_ajax_b2bking_group_rules_pro_delete_rule', array($this, 'ajax_delete_rule'));
        add_action('wp_ajax_b2bking_group_rules_pro_update_order', array($this, 'ajax_update_order'));
        add_action('wp_ajax_b2bking_group_rules_pro_save_rule', array($this, 'ajax_save_rule'));
        add_action('wp_ajax_b2bking_group_rules_pro_bulk_enable', array($this, 'ajax_bulk_enable'));
        add_action('wp_ajax_b2bking_group_rules_pro_bulk_disable', array($this, 'ajax_bulk_disable'));
        add_action('wp_ajax_b2bking_group_rules_pro_bulk_delete', array($this, 'ajax_bulk_delete'));
    }

    public function register_admin_menu() {
        add_submenu_page(
            'b2bking',
            esc_html__('Group Rules', 'b2bking'),
            esc_html__('Group Rules', 'b2bking'),
            'manage_woocommerce',
            self::SLUG,
            array($this, 'group_rules_pro_page_content'),
            3 // Position in the menu
        );

        // Individual editor page (hidden from menu)
        add_submenu_page(
            'b2bking', // Hidden
            esc_html__('Group Rule Editor', 'b2bking'),
            esc_html__('Group Rule Editor', 'b2bking'),
            apply_filters('b2bking_backend_capability_needed', 'manage_woocommerce'),
            self::EDITOR_SLUG,
            array($this, 'group_rule_pro_editor_page_content'),
            26
        );

        // Rules Log page (hidden from menu)
        add_submenu_page(
            'b2bking', // Hidden
            esc_html__('Group Rules Log', 'b2bking'),
            esc_html__('Group Rules Log', 'b2bking'),
            apply_filters('b2bking_backend_capability_needed', 'manage_woocommerce'),
            self::LOG_SLUG,
            array($this, 'group_rules_pro_log_page_content'),
            27
        );
    }

    public function enqueue_scripts($hook) {
        // Only load on our pages
        if (strpos($hook, 'b2bking_group_rules_pro') === false && 
            strpos($hook, 'b2bking_group_rule_pro_editor') === false &&
            strpos($hook, 'b2bking_group_rules_pro_log') === false) {
            return;
        }

        $plugin_url = plugin_dir_url(__FILE__);


        // Enqueue CSS based on page
        if (strpos($hook, self::EDITOR_SLUG) !== false) {
            // Editor page - only load editor CSS
            wp_enqueue_style(
                'b2bking-group-rule-pro-editor-css',
                $plugin_url . 'admin/assets/css/group-rule-pro-editor.css',
                array(),
                B2BKING_VERSION
            );
        } else {
            // Main page - load main CSS
            wp_enqueue_style(
                'b2bking-group-rules-pro-css',
                $plugin_url . 'admin/assets/css/group-rules-pro.css',
                array(),
                B2BKING_VERSION
            );
        }

        // Enqueue rules log CSS if on log page
        if (strpos($hook, self::LOG_SLUG) !== false) {
            wp_enqueue_style(
                'b2bking-group-rules-pro-log-css',
                $plugin_url . 'admin/assets/css/group-rules-pro-log.css',
                array(),
                B2BKING_VERSION
            );
        }
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'b2bking-group-rules-pro-js',
            $plugin_url . 'admin/assets/js/group-rules-pro.js',
            array('jquery'),
            B2BKING_VERSION,
            true
        );
        
        // Localize main script
        wp_localize_script('b2bking-group-rules-pro-js', 'b2bking_group_rules_pro', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2bking_group_rules_pro_nonce'),
            'editor_page_url' => admin_url('admin.php?page=' . self::EDITOR_SLUG),
            'log_page_url' => admin_url('admin.php?page=' . self::LOG_SLUG),
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
                'b2bking-group-rule-pro-editor-js',
                $plugin_url . 'admin/assets/js/group-rule-pro-editor.js',
                array('jquery', 'b2bking-sweetalert2'),
                B2BKING_VERSION,
                true
            );
        
            // Localize editor script
            wp_localize_script('b2bking-group-rule-pro-editor-js', 'b2bking_group_rule_pro_editor', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('b2bking_group_rules_pro_nonce'),
                'main_page_url' => admin_url('admin.php?page=' . self::SLUG),
                'admin_url' => admin_url(),
            ));
        }
    }

    public function group_rules_pro_page_content() {
        include $this->get_template_path('group-rules-pro-main.php');
    }

    public function group_rule_pro_editor_page_content($rule_id = null) {
        // If rule_id is provided (from AJAX), set it in $_GET for template compatibility
        if ($rule_id !== null && $rule_id !== '') {
            $_GET['rule_id'] = $rule_id;
        }
        // Also check if rule_id is in URL parameters (for direct access)
        elseif (isset($_GET['rule_id']) && $_GET['rule_id'] !== '') {
            // rule_id is already in $_GET, no need to do anything
        }
        
        $template_path = $this->get_template_path('group-rule-pro-editor.php');
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error">' . esc_html__('Template not found:', 'b2bking') . ' ' . esc_html($template_path) . '</div>';
        }
    }

    public function group_rules_pro_log_page_content() {
        $template_path = $this->get_template_path('group-rules-pro-log.php');
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
     * AJAX: Load rules
     */
    public function ajax_load_rules() {
        // Capability check
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Failed capability check.');
            wp_die();
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_group_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }

        $search = sanitize_text_field($_POST['search'] ?? '');
        $source_group_filter = sanitize_text_field($_POST['source_group_filter'] ?? '');
        $target_group_filter = sanitize_text_field($_POST['target_group_filter'] ?? '');
        $condition_filter = sanitize_text_field($_POST['condition_filter'] ?? '');
        $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
        
        // Pagination parameters
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = max(1, min(100, intval($_POST['per_page'] ?? 20))); // Limit to 100 items per page

        // Get all group rules ordered by menu_order (first get total count)
        $args = [
            'post_type' => 'b2bking_grule',
            'post_status' => 'publish',
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
            // Get rule meta using backward-compatible approach (try new keys first, then old keys)
            $enabled = get_post_meta($rule->ID, 'b2bking_post_status_enabled', true);
            $condition = get_post_meta($rule->ID, 'b2bking_rule_applies', true);
            $threshold = get_post_meta($rule->ID, 'b2bking_rule_threshold', true) ?: get_post_meta($rule->ID, 'b2bking_rule_howmuch', true);
            $target_group = get_post_meta($rule->ID, 'b2bking_rule_target_group', true) ?: get_post_meta($rule->ID, 'b2bking_rule_who', true);
            $source_group = get_post_meta($rule->ID, 'b2bking_rule_source_groups', true) ?: get_post_meta($rule->ID, 'b2bking_rule_agents_who', true);
            $operator = get_post_meta($rule->ID, 'b2bking_rule_operator', true);
            $rolling_days = get_post_meta($rule->ID, 'b2bking_rule_rolling_days', true);

            // Parse target group
            $target_group_id = null;
            $target_group_name = esc_html__('Unknown Group', 'b2bking');
            if (strpos($target_group, 'group_') === 0) {
                $target_group_id = str_replace('group_', '', $target_group);
                $target_group_name = $group_names[$target_group_id] ?? esc_html__('Unknown Group', 'b2bking');
            }

            // Parse source group
            $source_group_id = null;
            $source_group_name = esc_html__('All Groups', 'b2bking');
            if (strpos($source_group, 'group_') === 0) {
                $source_group_id = str_replace('group_', '', $source_group);
                $source_group_name = $group_names[$source_group_id] ?? esc_html__('Unknown Group', 'b2bking');
            }

            // Infer operator from old condition names if not explicitly set
            if (empty($operator)) {
                if (strpos($condition, '_higher') !== false) {
                    $operator = 'greater';
                } elseif (strpos($condition, '_lower') !== false) {
                    $operator = 'less';
                } else {
                    $operator = 'greater'; // default
                }
            }
            
            // Clean, reliable condition formatting
            $condition_text = $this->format_condition_display($condition, $operator, $rolling_days);

            // Apply filters
            if (!empty($source_group_filter) && $source_group_id != $source_group_filter) {
                continue;
            }
            
            if (!empty($target_group_filter) && $target_group_id != $target_group_filter) {
                continue;
            }
            
            if (!empty($condition_filter)) {
                // Normalize condition to new keys for reliable filtering
                $normalized = $this->normalize_condition_key_for_filter($condition);
                // Allow direct match to either normalized or legacy key
                if (!in_array($condition_filter, array($normalized, $condition), true)) {
                    continue;
                }
            }
            
            if (!empty($status_filter)) {
                $rule_enabled = intval($enabled) === 1;
                if (($status_filter === 'enabled' && !$rule_enabled) || 
                    ($status_filter === 'disabled' && $rule_enabled)) {
                    continue;
                }
            }

            // Format threshold display based on condition type
            $threshold_display = $threshold;
            $is_order_count_condition = strpos($condition, 'order_count') === 0;
            
            if ($operator === 'between') {
                $threshold_min = get_post_meta($rule->ID, 'b2bking_rule_threshold_min', true);
                $threshold_max = get_post_meta($rule->ID, 'b2bking_rule_threshold_max', true);
                if ($threshold_min && $threshold_max) {
                    if ($is_order_count_condition) {
                        $threshold_display = '#' . $threshold_min . ' - #' . $threshold_max;
                    } else {
                        $threshold_display = function_exists('wc_price') ? wc_price($threshold_min) . ' - ' . wc_price($threshold_max) : $threshold_min . ' - ' . $threshold_max;
                    }
                }
            } else {
                if ($is_order_count_condition) {
                    $threshold_display = '#' . $threshold;
                } else {
                    $threshold_display = function_exists('wc_price') ? wc_price($threshold) : $threshold;
                }
            }
            
            // Build concise description (avoid redundancy), allow override via filter
            $raw_description = $rule->post_content;
            if (trim((string) $raw_description) === '') {
                $raw_description = $this->generate_rule_description($condition_text, $condition);
            }
            $description = apply_filters(
                'b2bking_group_rules_pro_rule_description',
                $raw_description,
                array(
                    'rule_id' => $rule->ID,
                    'condition_key' => $condition,
                    'condition_text' => $condition_text,
                    'operator' => $operator,
                    'threshold' => $threshold,
                    'threshold_formatted' => $threshold_display,
                    'source_group_name' => $source_group_name,
                    'target_group_name' => $target_group_name,
                    'rolling_days' => $rolling_days,
                )
            );
            
            $formatted_rules[] = [
                'id' => $rule->ID,
                'name' => $rule->post_title,
                'description' => $description,
                'condition' => $condition_text,
                'threshold' => $threshold,
                'threshold_formatted' => $threshold_display,
                'source_group_name' => $source_group_name,
                'target_group_name' => $target_group_name,
                'enabled' => intval($enabled) === 1
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
        // Capability check
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Failed capability check.');
            wp_die();
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_group_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }

        $rule_id = intval($_POST['rule_id']);
        $enabled = $_POST['enabled'] === '1';

        // Verify the post exists and is a group rule
        $post = get_post($rule_id);
        if (!$post || $post->post_type !== 'b2bking_grule') {
            wp_send_json_error('Invalid rule.');
            wp_die();
        }

        // Update the rule status using both old and new meta keys
        $status = $enabled ? 1 : 0;
        
        // Update both old and new meta keys for full compatibility
        update_post_meta($rule_id, 'b2bking_post_status_enabled', $status);
        update_post_meta($rule_id, 'b2bking_rule_enabled', $status);
        
        // Clear cache for this post
        wp_cache_delete($rule_id, 'post_meta');

        // Bust rules cache
        delete_transient(self::RULES_CACHE_KEY);

        wp_send_json_success([
            'message' => $enabled ? esc_html__('Rule enabled successfully!', 'b2bking') : esc_html__('Rule disabled successfully!', 'b2bking')
        ]);
    }
    
    /**
     * AJAX: Delete rule
     */
    public function ajax_delete_rule() {
        // Verify nonce (accept both group rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_group_rules_pro_nonce') && 
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
        // Bust rules cache
        delete_transient(self::RULES_CACHE_KEY);

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
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_group_rules_pro_nonce') && 
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
            
            // Verify the post exists and is a group rule
            $post = get_post($rule_id);
            if ($post && $post->post_type === 'b2bking_grule') {
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

        // Clear rules list cache only (avoid heavy global cache flush)
        delete_transient(self::RULES_CACHE_KEY);

        wp_send_json_success(array(
            'message' => esc_html__('Rule order updated successfully', 'b2bking')
        ));
    }
    
    /**
     * AJAX: Save rule
     */
    public function ajax_save_rule() {
        // Verify nonce (accept both group rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_group_rules_pro_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'b2bking_security_nonce')) {
            wp_send_json_error('Invalid nonce.');
            wp_die();
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $rule_id = intval($_POST['rule_id'] ?? 0);
        $rule_name = sanitize_text_field($_POST['rule_name'] ?? '');
        $rule_applies = sanitize_text_field($_POST['rule_applies'] ?? '');
        $rule_operator = sanitize_text_field($_POST['rule_operator'] ?? '');
        $rule_threshold = floatval($_POST['rule_threshold'] ?? 0);
        $rule_threshold_min = floatval($_POST['rule_threshold_min'] ?? 0);
        $rule_threshold_max = floatval($_POST['rule_threshold_max'] ?? 0);
        $rule_rolling_days = intval($_POST['rule_rolling_days'] ?? 0);
        $rule_source_groups = sanitize_text_field($_POST['rule_source_groups'] ?? '');
        $rule_target_group = sanitize_text_field($_POST['rule_target_group'] ?? '');
        
        // Validate required fields
        if (empty($rule_name) || empty($rule_applies) || empty($rule_operator) || empty($rule_target_group)) {
            wp_send_json_error('Required fields are missing');
        }
        
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
            // Create new rule - get the lowest menu_order and subtract 1 to make it appear first
            global $wpdb;
            $min_order = $wpdb->get_var("SELECT MIN(menu_order) FROM {$wpdb->posts} WHERE post_type = 'b2bking_grule' AND post_status = 'publish'");
            $new_order = ($min_order !== null) ? $min_order - 1 : 0;
            
            $post_data = array(
                'post_title' => $rule_name,
                'post_type' => 'b2bking_grule',
                'post_status' => 'publish',
                'menu_order' => $new_order
            );
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error('Failed to save rule');
        }
        
        $rule_id = $result;
        
        // Convert new condition format to old format for backward compatibility
        $old_condition = $this->convert_new_condition_to_old($rule_applies, $rule_operator);
        
        // Save meta data using BOTH new and old meta keys for full backward compatibility
        // New meta keys (for new UI)
        update_post_meta($rule_id, 'b2bking_rule_name', $rule_name);
        update_post_meta($rule_id, 'b2bking_rule_applies', $rule_applies);
        update_post_meta($rule_id, 'b2bking_rule_operator', $rule_operator);
        update_post_meta($rule_id, 'b2bking_rule_threshold', $rule_threshold);
        update_post_meta($rule_id, 'b2bking_rule_threshold_min', $rule_threshold_min);
        update_post_meta($rule_id, 'b2bking_rule_threshold_max', $rule_threshold_max);
        update_post_meta($rule_id, 'b2bking_rule_rolling_days', $rule_rolling_days);
        update_post_meta($rule_id, 'b2bking_rule_source_groups', $rule_source_groups);
        update_post_meta($rule_id, 'b2bking_rule_target_group', $rule_target_group);
        update_post_meta($rule_id, 'b2bking_rule_enabled', 1);
        
        // Old meta keys (for old UI compatibility)
        update_post_meta($rule_id, 'b2bking_rule_what', 'change_group');
        update_post_meta($rule_id, 'b2bking_rule_applies', $old_condition);
        update_post_meta($rule_id, 'b2bking_rule_howmuch', $rule_threshold);
        update_post_meta($rule_id, 'b2bking_rule_who', $rule_target_group);
        update_post_meta($rule_id, 'b2bking_rule_agents_who', $rule_source_groups);
        update_post_meta($rule_id, 'b2bking_post_status_enabled', 1);
        
        // Bust rules cache
        delete_transient(self::RULES_CACHE_KEY);

        wp_send_json_success(array(
            'message' => esc_html__('Rule saved successfully', 'b2bking'),
            'rule_id' => $rule_id
        ));
    }
    
    /**
     * AJAX: Bulk enable rules
     */
    public function ajax_bulk_enable() {
        // Verify nonce (accept both group rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_group_rules_pro_nonce') && 
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
            update_post_meta($rule_id, 'b2bking_rule_enabled', '1');
            update_post_meta($rule_id, 'b2bking_post_status_enabled', '1');
        }
        
        // Bust rules cache
        delete_transient(self::RULES_CACHE_KEY);

        wp_send_json_success(array(
            'message' => esc_html__('Rules enabled successfully', 'b2bking')
        ));
    }
    
    /**
     * AJAX: Bulk disable rules
     */
    public function ajax_bulk_disable() {
        // Verify nonce (accept both group rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_group_rules_pro_nonce') && 
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
            update_post_meta($rule_id, 'b2bking_rule_enabled', '0');
            update_post_meta($rule_id, 'b2bking_post_status_enabled', '0');
        }
        
        // Bust rules cache
        delete_transient(self::RULES_CACHE_KEY);

        wp_send_json_success(array(
            'message' => esc_html__('Rules disabled successfully', 'b2bking')
        ));
    }
    
    /**
     * AJAX: Bulk delete rules
     */
    public function ajax_bulk_delete() {
        // Verify nonce (accept both group rules pro nonce and main plugin nonce)
        if (!wp_verify_nonce($_POST['nonce'], 'b2bking_group_rules_pro_nonce') && 
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
        
        // Bust rules cache
        delete_transient(self::RULES_CACHE_KEY);

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
     * Normalize a condition key (old/new) to a canonical new-format key for filtering
     */
    private function normalize_condition_key_for_filter($condition_key) {
        // Legacy supported only these keys; normalize them to new format
        $map = array(
            'order_value_total' => 'total_spent',
            'order_value_yearly_higher' => 'spent_yearly',
            'order_value_yearly_lower' => 'spent_yearly',
            'order_value_monthly_higher' => 'spent_monthly',
            'order_value_monthly_lower' => 'spent_monthly',
        );
        return isset($map[$condition_key]) ? $map[$condition_key] : $condition_key;
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
     * Build a concise, non-redundant description for a rule card based on condition semantics.
     * Examples:
     * - Quarterly → "Quarterly spending-based progression"
     * - Total / All-Time → "Lifetime value milestone"
     * - Order* → "Purchase frequency-based upgrade"
     * - Rolling → "Recent activity-based progression"
     * - Monthly → "Monthly spending-based progression"
     * - Yearly → "Yearly spending-based progression"
     * - Days Since Last Order → "Reactivation trigger"
     * - Days Since First Order → "Onboarding progression"
     */
    private function generate_rule_description($condition_text, $condition_key) {
        $label = trim((string) $condition_text);
        $key = (string) $condition_key;
        $l = strtolower($label);
        $k = strtolower($key);
        
        // Days-based conditions first
        if (strpos($k, 'days_since_last_order') !== false || strpos($l, 'days since last order') !== false) {
            return esc_html__('Activity recency-based rule', 'b2bking');
        }
        if (strpos($k, 'days_since_first_order') !== false || strpos($l, 'days since first order') !== false) {
            return esc_html__('Time since joining-based rule', 'b2bking');
        }
        
        // Rolling window
        if (strpos($k, 'rolling') !== false || strpos($l, 'rolling') !== false) {
            return esc_html__('Recent activity-based progression', 'b2bking');
        }
        
        // Total / All-Time spending
        if (strpos($k, 'total_spent') !== false || strpos($l, 'all-time') !== false) {
            return esc_html__('Lifetime value milestone', 'b2bking');
        }
        
        // Order count semantics: total vs per-period
        if (strpos($k, 'order_count') !== false || strpos($l, 'orders') !== false) {
            if (strpos($k, 'order_count_total') !== false || strpos($l, 'total orders') !== false) {
                return esc_html__('Order volume-based rule', 'b2bking');
            }
            return esc_html__('Purchase frequency-based rule', 'b2bking');
        }
        
        // Order value semantics (spent_*) except total/rolling handled above
        if (strpos($k, 'spent_') === 0 || strpos($l, 'spent') !== false) {
            return esc_html__('Order value-based rule', 'b2bking');
        }
        
        // Quarter / Month / Year fallback (kept for non-spend non-count keys)
        if (strpos($l, 'quarter') !== false) {
            return esc_html__('Quarterly spending-based rule', 'b2bking');
        }
        if (strpos($l, 'month') !== false) {
            return esc_html__('Monthly spending-based rule', 'b2bking');
        }
        if (strpos($l, 'year') !== false) {
            return esc_html__('Yearly spending-based rule', 'b2bking');
        }
        
        // Fallback generic
        return esc_html__('Progression rule', 'b2bking');
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

	/*
	 * =============================
	 * PRO GROUP RULES APPLY LOGIC
	 * =============================
	 * The following static methods implement the new group rules engine used when
	 * option `b2bking_early_access_group_rules` is enabled. They are self-contained
	 * and do not modify legacy logic. Public methods return whether a user group was
	 * changed, so callers can clear caches just like the old flow.
	 */

	/**
	 * Apply PRO group rules for a checkout/order event.
	 * @param WC_Order $order
	 * @return bool $group_changed
	 */
	public static function apply_group_rules_pro_on_order($order){
		if (get_option('b2bking_early_access_group_rules', 'no') !== 'yes'){
			return false;
		}
		if (! $order){
			return false;
		}
		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0){
			return false;
		}
		$user_id = b2bking()->get_top_parent_account($user_id);
		return self::apply_group_rules_pro_for_user($user_id, array('order' => $order));
	}

	/**
	 * Apply PRO group rules for the current logged-in user on periodic/init contexts
	 * (monthly/yearly/rolling checks). Light-weight and cached.
	 * @param int $user_id
	 * @return bool $group_changed
	 */
	public static function apply_group_rules_pro_periodic_for_user($user_id){
		if (get_option('b2bking_early_access_group_rules', 'no') !== 'yes'){
			return false;
		}
		$user_id = (int) $user_id;
		if ($user_id <= 0){
			return false;
		}
		$user_id = b2bking()->get_top_parent_account($user_id);
		// Per-user once-per-day throttle
		$today = date('Ymd', current_time('timestamp'));
		$last_run = get_user_meta($user_id, 'b2bking_grpro_last_periodic_run', true);
		if ($last_run === $today){
			return false;
		}
		$changed = self::apply_group_rules_pro_for_user($user_id, array('context' => 'periodic'));
		update_user_meta($user_id, 'b2bking_grpro_last_periodic_run', $today);
		return $changed;
	}

	/**
	 * Core evaluator applying all enabled rules for a user.
	 * Handles: total, monthly/yearly/quarterly (current and last), rolling N days,
	 * order counts, days since first/last order. Uses caching for performance.
	 * @param int $user_id
	 * @param array $context Optional: ['order'=>WC_Order, 'context'=>'periodic']
	 * @return bool
	 */
	private static function apply_group_rules_pro_for_user($user_id, $context = array()){
		// Only B2B users are moved by rules
		if (get_user_meta($user_id,'b2bking_b2buser', true) !== 'yes'){
			return false;
		}

		$current_group_id = b2bking()->get_user_group($user_id);
		$rules = self::get_enabled_rules_cached();
		if (empty($rules)){
			return false;
		}

		$group_changed = false;
		foreach ($rules as $rule){
			// Source group check (empty = all groups)
			$source_group_raw = get_post_meta($rule->ID, 'b2bking_rule_source_groups', true) ?: get_post_meta($rule->ID, 'b2bking_rule_agents_who', true);
			$source_group_id = self::parse_group_id($source_group_raw);
			if (!empty($source_group_id) && (string)$current_group_id !== (string)$source_group_id){
				continue;
			}

			$enabled = get_post_meta($rule->ID,'b2bking_post_status_enabled', true);
			if ((int)$enabled !== 1){
				continue;
			}

			$condition = get_post_meta($rule->ID,'b2bking_rule_applies', true);
			$operator  = get_post_meta($rule->ID,'b2bking_rule_operator', true);
			$threshold = get_post_meta($rule->ID,'b2bking_rule_threshold', true) ?: get_post_meta($rule->ID,'b2bking_rule_howmuch', true);
			$threshold_min = get_post_meta($rule->ID,'b2bking_rule_threshold_min', true);
			$threshold_max = get_post_meta($rule->ID,'b2bking_rule_threshold_max', true);
			$rolling_days = (int) get_post_meta($rule->ID,'b2bking_rule_rolling_days', true);
			$target_group_raw = get_post_meta($rule->ID,'b2bking_rule_target_group', true) ?: get_post_meta($rule->ID,'b2bking_rule_who', true);
			$target_group_id = self::parse_group_id($target_group_raw);
			if (empty($target_group_id)){
				continue;
			}

			$value = self::compute_condition_value($user_id, $condition, $rolling_days, $context);
			if ($value === null){
				continue;
			}

			if (self::compare_value($value, $operator, $threshold, $threshold_min, $threshold_max, $condition)){
				// Promote/Demote to target group
				if ((string)$current_group_id !== (string)$target_group_id){
					$old_group_id = $current_group_id;
					b2bking()->update_user_group($user_id, $target_group_id);
					$current_group_id = $target_group_id;
					$group_changed = true;

					if (apply_filters('b2bking_use_wp_roles', false)){
						$user_obj = new WP_User($user_id);
						$groups = get_posts([
						  'post_type' => 'b2bking_group',
						  'post_status' => 'publish',
						  'numberposts' => -1,
						  'fields' => 'ids',
						]);
						$user_obj->remove_role(apply_filters('b2bking_b2c_role_name', 'b2bking_role_b2cuser'));
						foreach ($groups as $grouprole){
							$user_obj->remove_role('b2bking_role_'.$grouprole);
						}
						$user_obj->add_role('b2bking_role_'.$target_group_id);
						if (apply_filters('b2bking_use_wp_roles_only_b2b', false)){
							$user_obj->set_role('b2bking_role_'.$target_group_id);
						}
					}

					do_action('b2bking_group_rules_pro_applied', $rule->ID, $user_id, $old_group_id, $target_group_id, $value);
				}
			}
		}

		return $group_changed;
	}

	/* =====================
	 * Order index (usermeta)
	 * ===================== */

	const ORDER_INDEX_MAX_DAYS = 400;

	/**
	 * Update per-user index when order status changes (incremental updates).
	 */
	public function on_order_status_changed($order_id, $old_status, $new_status, $order){
		if (get_option('b2bking_early_access_group_rules', 'no') !== 'yes'){
			return;
		}
		// Count only selected statuses
		$statuses = apply_filters('b2bking_group_rules_statuses', array('wc-completed'));
		$old_in = in_array('wc-'.$old_status, $statuses, true);
		$new_in = in_array('wc-'.$new_status, $statuses, true);
		if ($old_in === $new_in){
			return;
		}
		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0){ return; }
		$user_id = b2bking()->get_top_parent_account($user_id);
		$sign = $new_in ? 1 : -1;

		// Ensure index exists and is fully initialized
		$data = self::load_order_index($user_id);
		if (empty($data) || empty($data['init'])){
			self::ensure_order_index_recent_days($user_id);
		} else {
			// Apply delta only if already initialized
			self::apply_order_delta_to_index($user_id, $order, $sign);
		}

		// Immediately evaluate rules for this user (bypass daily throttle)
		$changed = self::apply_group_rules_pro_for_user($user_id, array('context' => 'status_change'));
		if ($changed){
			b2bking()->clear_caches_transients();
			b2bking()->b2bking_clear_rules_caches();
		}
	}

	private static function apply_order_delta_to_index($user_id, $order, $sign){
		$data = self::load_order_index($user_id);
		$created = $order->get_date_created();
		if (!$created){ return; }
		$day = $created->date('Ymd');
		$amount = (float) $order->get_total();
		$data += array('v'=>1,'all'=>array('c'=>0,'a'=>0.0),'days'=>array());
		if (!isset($data['days'][$day])){ $data['days'][$day] = array('c'=>0,'a'=>0.0); }
		$data['days'][$day]['c'] += (int)$sign;
		$data['days'][$day]['a'] += (float)($sign*$amount);
		$data['all']['c'] += (int)$sign;
		$data['all']['a'] += (float)($sign*$amount);
		$data['last'] = $day;
		$data['days'] = self::prune_days($data['days']);

        // save order index
		update_user_meta($user_id, 'b2bking_user_order_info', wp_json_encode($data));
	}

	private static function load_order_index($user_id){
		$raw = get_user_meta($user_id, 'b2bking_user_order_info', true);
		if (empty($raw)){
			return array('v'=>1,'all'=>array('c'=>0,'a'=>0.0),'days'=>array());
		}
		if (is_array($raw)){
			return $raw;
		}
		$decoded = json_decode($raw, true);
		if (is_array($decoded)){
			return $decoded;
		}
		return array('v'=>1,'all'=>array('c'=>0,'a'=>0.0),'days'=>array());
	}

	private static function prune_days($days){
		$keys = array_keys($days);
		rsort($keys, SORT_STRING);
		if (count($keys) <= self::ORDER_INDEX_MAX_DAYS){
			return $days;
		}
		$keep = array_slice($keys, 0, self::ORDER_INDEX_MAX_DAYS);
		$keep = array_flip($keep);
		$pruned = array();
		foreach ($days as $k => $v){
			if (isset($keep[$k])){ $pruned[$k] = $v; }
		}
		return $pruned;
	}

    private static function ensure_order_index_recent_days($user_id){
        $data = self::load_order_index($user_id);
        // If we already have a fully initialized index, return it
        if (!empty($data['days']) && !empty($data['init'])){
            return $data;
        }

        // Build (or rebuild) last ORDER_INDEX_MAX_DAYS from scratch to avoid partial indexes
        $statuses = apply_filters('b2bking_group_rules_statuses', array('wc-completed'));
        $end = current_time('Y-m-d');
        $start_ts = strtotime('-'.self::ORDER_INDEX_MAX_DAYS.' days', current_time('timestamp'));
        $start = date('Y-m-d', $start_ts);
        $orders = wc_get_orders(array(
            'limit' => -1,
            'customer_id' => $user_id,
            'type' => 'shop_order',
            'status' => $statuses,
            'date_created' => $start.'...'.$end,
        ));

        $rebuilt = array('v'=>1,'all'=>array('c'=>0,'a'=>0.0),'days'=>array());
        foreach ($orders as $order){
            $created = $order->get_date_created();
            if (!$created){ continue; }
            $day = $created->date('Ymd');
            if (!isset($rebuilt['days'][$day])){ $rebuilt['days'][$day] = array('c'=>0,'a'=>0.0); }
            $rebuilt['days'][$day]['c'] += 1;
            $rebuilt['days'][$day]['a'] += (float) $order->get_total();
            $rebuilt['all']['c'] += 1;
            $rebuilt['all']['a'] += (float) $order->get_total();
            $rebuilt['last'] = $day;
        }
        // Mark as initialized so we don't backfill again
        $rebuilt['init'] = 1;

        update_user_meta($user_id, 'b2bking_user_order_info', wp_json_encode($rebuilt));
        return $rebuilt;
    }

	/* -------------------- Helpers (private static) -------------------- */

	private static function get_enabled_rules_cached(){
		$cache_key = self::RULES_CACHE_KEY;
		$rules = get_transient($cache_key);
		if ($rules === false){
			$rules = get_posts([
				'post_type' => 'b2bking_grule',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby' => 'menu_order',
				'order' => 'ASC',
			]);
			set_transient($cache_key, $rules, self::RULES_CACHE_TTL);
		}
		return $rules;
	}


	private static function parse_group_id($raw){
		if (empty($raw)){
			return '';
		}
		if (strpos($raw, 'group_') === 0){
			return str_replace('group_', '', $raw);
		}
		return $raw;
	}

	private static function compute_condition_value($user_id, $condition, $rolling_days, $context){
		$condition = self::normalize_condition($condition);

		// Amount spent conditions
		if (in_array($condition, array('total_spent','spent_yearly','spent_monthly','spent_quarterly','spent_current_year','spent_current_month','spent_current_quarter','spent_rolling'), true)){
			return self::compute_spent($user_id, $condition, $rolling_days);
		}

		// Order count conditions
		if (in_array($condition, array('order_count_total','order_count_yearly','order_count_monthly','order_count_quarterly','order_count_current_year','order_count_current_month','order_count_current_quarter','order_count_rolling'), true)){
			return self::compute_order_count($user_id, $condition, $rolling_days);
		}

		// Days since first/last order
		if ($condition === 'days_since_first_order'){
			$first = self::get_user_first_last_order_date($user_id, 'first');
			if (!$first){ return null; }
			return self::days_between_dates($first, current_time('mysql'));
		}
		if ($condition === 'days_since_last_order'){
			$last = self::get_user_first_last_order_date($user_id, 'last');
			if (!$last){ return null; }
			return self::days_between_dates($last, current_time('mysql'));
		}

		return null;
	}

	private static function normalize_condition($condition){
		// Map legacy amount conditions to new normalized names
		$map = array(
			'order_value_total' => 'total_spent',
			'order_value_yearly_higher' => 'spent_yearly',
			'order_value_yearly_lower' => 'spent_yearly',
			'order_value_monthly_higher' => 'spent_monthly',
			'order_value_monthly_lower' => 'spent_monthly',
			'order_value_quarterly_higher' => 'spent_quarterly',
			'order_value_quarterly_lower' => 'spent_quarterly',
			'order_value_current_year_higher' => 'spent_current_year',
			'order_value_current_year_lower' => 'spent_current_year',
			'order_value_current_month_higher' => 'spent_current_month',
			'order_value_current_month_lower' => 'spent_current_month',
			'order_value_current_quarter_higher' => 'spent_current_quarter',
			'order_value_current_quarter_lower' => 'spent_current_quarter',
			'order_value_rolling_higher' => 'spent_rolling',
			'order_value_rolling_lower' => 'spent_rolling',
		);
		return isset($map[$condition]) ? $map[$condition] : $condition;
	}

	private static function compute_spent($user_id, $type, $rolling_days){
		$cache_key = 'b2bking_grpro_spent_'.$type.'_'.$rolling_days.'_u'.$user_id;
		$cached = get_transient($cache_key);
		if ($cached !== false){ return $cached; }

		list($start, $end) = self::date_range_for_type($type, $rolling_days);
		$data = self::ensure_order_index_recent_days($user_id);
		if ($type === 'total_spent'){
			$amount = isset($data['all']['a']) ? (float)$data['all']['a'] : 0.0;
			set_transient($cache_key, $amount, self::SPENT_TTL);
			return $amount;
		}
		$total = self::sum_days_between($data['days'], $start, $end, 'a');
		set_transient($cache_key, $total, HOUR_IN_SECONDS);
		return $total;
	}

	private static function compute_order_count($user_id, $type, $rolling_days){
		$cache_key = 'b2bking_grpro_count_'.$type.'_'.$rolling_days.'_u'.$user_id;
		$cached = get_transient($cache_key);
		if ($cached !== false){ return (int)$cached; }

		list($start, $end) = self::date_range_for_type($type, $rolling_days);
		$data = self::ensure_order_index_recent_days($user_id);
		if ($type === 'order_count_total'){
			$count = isset($data['all']['c']) ? (int)$data['all']['c'] : 0;
			set_transient($cache_key, $count, self::COUNT_TTL);
			return $count;
		}
		$count = (int) self::sum_days_between($data['days'], $start, $end, 'c');
		set_transient($cache_key, $count, HOUR_IN_SECONDS);
		return $count;
	}

	private static function sum_days_between($daysMap, $start, $end, $key){
		$total = 0.0;
		if ($start && $end){
			$start_key = date('Ymd', strtotime($start));
			$end_key = date('Ymd', strtotime($end));
			foreach ($daysMap as $day => $vals){
				if ($day >= $start_key && $day <= $end_key){
					$total += (float) ($vals[$key] ?? 0);
				}
			}
		}
		return $total;
	}

	private static function get_user_first_last_order_date($user_id, $which = 'first'){
		$cache_key = 'b2bking_grpro_'.$which.'_order_date_u'.$user_id;
		$cached = get_transient($cache_key);
		if ($cached !== false){ return $cached; }
		$order = wc_get_orders(array(
			'limit' => 1,
			'customer_id' => $user_id,
			'orderby' => 'date',
			'order' => $which === 'first' ? 'ASC' : 'DESC',
			'status' => apply_filters('b2bking_group_rules_statuses', array('wc-completed')),
		));
		if (!empty($order)){
			$date = $order[0]->get_date_created();
			$val = $date ? $date->date('Y-m-d H:i:s') : null;
			set_transient($cache_key, $val, DAY_IN_SECONDS);
			return $val;
		}
		set_transient($cache_key, null, HOUR_IN_SECONDS);
		return null;
	}

	private static function days_between_dates($from_mysql, $to_mysql){
		$from = new DateTime($from_mysql);
		$to = new DateTime($to_mysql);
		return (int)$to->diff($from)->format('%a');
	}

	private static function date_range_for_type($type, $rolling_days){
		$now = current_time('timestamp');
		$year = (int) date('Y', $now);
		$month = (int) date('m', $now);
		$quarter = (int) ceil($month/3);

		switch ($type){
			case 'spent_yearly':
			case 'order_count_yearly':
				$y = $year - 1;
				$start = $y.'-01-01';
				$end   = $y.'-12-31';
				break;
			case 'spent_monthly':
			case 'order_count_monthly':
				$prev = strtotime('first day of previous month', $now);
				$start = date('Y-m-01', $prev);
				$end   = date('Y-m-t', $prev);
				break;
			case 'spent_quarterly':
			case 'order_count_quarterly':
				$prev_q_end_month = ($quarter-1)*3;
				if ($prev_q_end_month <= 0){
					$prev_q_end_month = 12;
					$y = $year - 1;
				} else {
					$y = $year;
				}
				$prev_q_start_month = $prev_q_end_month - 2;
				$start = sprintf('%04d-%02d-01', $y, $prev_q_start_month);
				$end   = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $y, $prev_q_end_month)));
				break;
			case 'spent_current_year':
			case 'order_count_current_year':
				$start = $year.'-01-01';
				$end   = $year.'-12-31';
				break;
			case 'spent_current_month':
			case 'order_count_current_month':
				$start = date('Y-m-01', $now);
				$end   = date('Y-m-t', $now);
				break;
			case 'spent_current_quarter':
			case 'order_count_current_quarter':
				$q_start_month = ($quarter-1)*3 + 1;
				$q_end_month = $q_start_month + 2;
				$start = sprintf('%04d-%02d-01', $year, $q_start_month);
				$end   = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $q_end_month)));
				break;
			case 'spent_rolling':
			case 'order_count_rolling':
				$days = max(1, (int)$rolling_days);
				$start = date('Y-m-d', strtotime('-'.$days.' days', $now));
				$end   = date('Y-m-d', $now);
				break;
			default:
				$start = $end = null;
		}

		return array($start, $end);
	}

	private static function compare_value($value, $operator, $threshold, $min, $max, $condition){
		// Default operator fallback from legacy condition naming
		if (empty($operator)){
			if (strpos($condition, 'lower') !== false){ $operator = 'less'; }
			elseif (strpos($condition, 'higher') !== false){ $operator = 'greater'; }
			else { $operator = 'greater'; }
		}

		switch ($operator){
			case 'greater':
				return (float)$value > (float)$threshold;
			case 'greater_equal':
				return (float)$value >= (float)$threshold;
			case 'less':
				return (float)$value < (float)$threshold;
			case 'less_equal':
				return (float)$value <= (float)$threshold;
			case 'between':
				return (float)$value >= (float)$min && (float)$value <= (float)$max;
		}
		return false;
	}

	/* =====================
	 * Periodic cron (batch)
	 * ===================== */

	public function maybe_schedule_periodic_cron(){
		if (get_option('b2bking_early_access_group_rules', 'no') !== 'yes'){
			return;
		}
		if (!wp_next_scheduled('b2bking_grpro_periodic_cron')){
			// Hourly batches by default; site owners can change via filter if needed
			wp_schedule_event(time() + 300, apply_filters('b2bking_grpro_cron_recurrence', 'hourly'), 'b2bking_grpro_periodic_cron');
		}
	}

	public function run_periodic_cron_batch(){
		if (get_option('b2bking_early_access_group_rules', 'no') !== 'yes'){
			return;
		}
		$today = date('Ymd', current_time('timestamp'));
		$last_full = get_option('b2bking_grpro_cron_last_full_sweep', '');
		if ($last_full === $today){
			// already completed today
			return;
		}
		$batch_size = (int) apply_filters('b2bking_grpro_cron_batch_size', 500);
		if ($batch_size < 1){ $batch_size = 100; }
		$offset = (int) get_option('b2bking_grpro_cron_offset', 0);

		$args = array(
			'number' => $batch_size,
			'offset' => $offset,
			'fields' => 'ID',
			'meta_key' => 'b2bking_b2buser',
			'meta_value' => 'yes',
			'orderby' => 'ID',
			'order' => 'ASC',
		);
		$users = get_users($args);
		$processed = 0;
		foreach ($users as $uid){
			$uid = b2bking()->get_top_parent_account($uid);
			$changed = self::apply_group_rules_pro_periodic_for_user($uid);
			if ($changed){
				b2bking()->clear_caches_transients();
				b2bking()->b2bking_clear_rules_caches();
			}
			$processed++;
		}

		if ($processed < $batch_size){
			// reached the end; mark full sweep for today and reset offset
			update_option('b2bking_grpro_cron_last_full_sweep', $today);
			update_option('b2bking_grpro_cron_offset', 0);
		} else {
			update_option('b2bking_grpro_cron_offset', $offset + $processed);
		}
	}
	/**
	 * Log listener for applied group rules
	 */
	public function log_rule_applied($rule_id, $user_id, $old_group_id, $new_group_id, $value){
		if (!class_exists('B2BKing_Group_Rules_Log')){ return; }
		$logger = B2BKing_Group_Rules_Log::get_instance();
		$logger->add_log($user_id, $rule_id, $old_group_id, $new_group_id);
	}
}

// Initialize the module
B2BKing_Group_Rules_Pro::get_instance();