<?php
/**
 * Dynamic Rules Pro Module Bootstrap
 * 
 * This file initializes the Dynamic Rules Pro module
 * 
 * @package B2BKing
 * @subpackage Dynamic Rules Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define module constants
define('B2BKING_DYNAMIC_RULES_PRO_PATH', plugin_dir_path(__FILE__));
define('B2BKING_DYNAMIC_RULES_PRO_URL', plugin_dir_url(__FILE__));

/**
 * Initialize the Dynamic Rules Pro module
 */
function b2bking_dynamic_rules_pro_init() {
    // Check if B2BKing is active
    if (!class_exists('B2BKing')) {
        return;
    }
    
    // Load the main class
    require_once B2BKING_DYNAMIC_RULES_PRO_PATH . 'class-dynamic-rules-pro.php';
    
    // Initialize the module
    B2BKing_Dynamic_Rules_Pro::get_instance();
}


// Initialize the module
add_action('plugins_loaded', 'b2bking_dynamic_rules_pro_init');
