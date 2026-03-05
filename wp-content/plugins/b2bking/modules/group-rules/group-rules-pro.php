<?php
/**
 * Group Rules Pro Module Bootstrap
 * 
 * This file initializes the Group Rules Pro module
 * 
 * @package B2BKing
 * @subpackage Group Rules Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define module constants
define('B2BKING_GROUP_RULES_PRO_PATH', plugin_dir_path(__FILE__));
define('B2BKING_GROUP_RULES_PRO_URL', plugin_dir_url(__FILE__));

/**
 * Initialize the Group Rules Pro module
 */
function b2bking_group_rules_pro_init() {
    // Check if B2BKing is active
    if (!class_exists('B2BKing')) {
        return;
    }
    
    // Load the log system first (needed globally)
    require_once B2BKING_GROUP_RULES_PRO_PATH . 'class-group-rules-pro-log.php';
    
    // Load the main class
    require_once B2BKING_GROUP_RULES_PRO_PATH . 'class-group-rules-pro.php';
    
    // Initialize the module
    B2BKing_Group_Rules_Pro::get_instance();
}

// Initialize the module
add_action('plugins_loaded', 'b2bking_group_rules_pro_init');
