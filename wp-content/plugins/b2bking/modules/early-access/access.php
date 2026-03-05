<?php
/**
 * B2BKing Early Access Module
 * 
 * This module provides early access functionality for experimental features.
 * It's designed to be isolated from the main B2BKing codebase for easy management.
 * 
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2BKing_Early_Access_Module {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Only load if we're in admin
        if (is_admin()) {
            // Use a later priority to ensure main B2BKing menu and License menu are registered first
            add_action('admin_menu', array($this, 'add_early_access_menu'), 30);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_early_access_scripts'));
            
            // AJAX handlers
            add_action('wp_ajax_b2bking_early_access_toggle_feature', array($this, 'toggle_feature'));
            add_action('wp_ajax_b2bking_early_access_get_feature_info', array($this, 'get_feature_info'));
            add_action('wp_ajax_b2bking_early_access_submit_feedback', array($this, 'submit_feedback'));
        }
    }
    
    /**
     * Add Early Access menu item
     */
    public function add_early_access_menu() {
        // Check if the main B2BKing menu exists before adding submenu
        global $menu, $submenu;
        
        if (isset($submenu['b2bking'])) {
            add_submenu_page(
                'b2bking',
                esc_html__('Early Access', 'b2bking'),
                esc_html__('Early Access', 'b2bking') . '<span class="b2bking-menu-new">&nbsp;NEW!</span>',
                apply_filters('b2bking_backend_capability_needed', 'manage_woocommerce'),
                'b2bking_early_access',
                array($this, 'render_early_access_page'),
                25
            );
        }
    }
    
    /**
     * Enqueue Early Access scripts and styles
     */
    public function enqueue_early_access_scripts($hook) {
        if ($hook === 'b2bking_page_b2bking_early_access') {
            $module_url = plugin_dir_url(__FILE__);
            
            // Enqueue SweetAlert2 for consistent notifications
            wp_enqueue_script(
                'b2bking-sweetalert2',
                plugins_url('../../includes/assets/lib/sweetalert/sweetalert2.all.min.js', __FILE__),
                array(),
                B2BKING_VERSION
            );
            
            wp_enqueue_style(
                'b2bking_early_access_style',
                $module_url . 'access.css',
                array(),
                B2BKING_VERSION
            );
            
            wp_enqueue_script(
                'b2bking_early_access_script',
                $module_url . 'access.js',
                array('jquery', 'b2bking-sweetalert2'),
                B2BKING_VERSION,
                true
            );
        }
    }
    
    /**
     * Render Early Access page
     */
    public function render_early_access_page() {
        // Get header bar from main plugin
        if (method_exists('B2BKing_Admin', 'get_header_bar')) {
            echo B2BKing_Admin::get_header_bar();
        }
        
        // Get all Early Access features
        $early_access_features = $this->get_early_access_features();
        
        // Send data to JS
        $translation_array = array(
            'features_data' => $early_access_features,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2bking_early_access_nonce')
        );
        
        wp_localize_script('b2bking_early_access_script', 'b2bking_early_access', $translation_array);
        ?>
        
        <div id="b2bking_early_access_wrapper">
            <div class="b2bking-early-access-container">
                
                <!-- Header Section -->
                <div class="b2bking-early-access-header">
                    <div class="b2bking-header-content">
                        <div class="b2bking-header-left">
                            <svg class="b2bking-early-access-header-icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="32" height="32" rx="8" fill="#906a1d"/>
                                <g transform="translate(4, 4) scale(0.095)">
                                    <path d="M223.59033,199.76855,160,93.78418V40h8a8,8,0,0,0,0-16H88a8,8,0,0,0,0,16h8V93.78418l-40.17285,66.955c-.044.07067-.08643.1416-.12793.2135L32.40967,199.76807A15.99968,15.99968,0,0,0,46.12988,224H209.87012a15.99944,15.99944,0,0,0,13.72021-24.23145Zm-92.01269-38.92382c-14.25293-7.127-32.667-13.52124-50.31055-11.4076l28.45215-47.42053A15.99829,15.99829,0,0,0,112,93.78418V40h32V93.78418a15.99947,15.99947,0,0,0,2.28027,8.23193l38.86328,64.77222C172.03613,173.8999,153.69775,171.90405,131.57764,160.84473Z" fill="#191821"/>
                                </g>
                            </svg>
                            <div class="b2bking-header-text">
                                <h1 class="b2bking-early-access-title"><?php esc_html_e('Early Access Features', 'b2bking'); ?></h1>
                                <p class="b2bking-early-access-subtitle">
                                    <?php esc_html_e('Try new B2BKing features and upgrades at an early stage. Switch back and forth anytime.', 'b2bking'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="b2bking-header-right">
                            <div class="b2bking-header-utility-tabs">
                                <button class="b2bking-header-utility-tab" data-modal="safety-info" title="<?php esc_attr_e('Learn more about feature safety', 'b2bking'); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M12 16v-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        <path d="M12 8h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    <span><?php esc_html_e('Info', 'b2bking'); ?></span>
                                </button>
                                <button class="b2bking-header-utility-tab" data-modal="feedback-form" title="<?php esc_attr_e('Send feedback about early access features', 'b2bking'); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M13 8H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        <path d="M17 12H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    <span><?php esc_html_e('Feedback', 'b2bking'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Categories -->
                <div class="b2bking-feature-categories">
                    <div class="b2bking-category-tabs">
                        <button class="b2bking-category-tab active" data-category="all">
                            <?php esc_html_e('All Features', 'b2bking'); ?>
                            <span class="b2bking-tab-count"><?php echo count($early_access_features); ?></span>
                        </button>
                        <button class="b2bking-category-tab" data-category="ui">
                            <?php esc_html_e('UI/UX', 'b2bking'); ?>
                            <span class="b2bking-tab-count"><?php echo count(array_filter($early_access_features, function($feature) { 
                                $categories = is_array($feature['categories']) ? $feature['categories'] : array($feature['categories']);
                                return in_array('ui', $categories); 
                            })); ?></span>
                        </button>
                        <button class="b2bking-category-tab" data-category="functionality">
                            <?php esc_html_e('Functionality', 'b2bking'); ?>
                            <span class="b2bking-tab-count"><?php echo count(array_filter($early_access_features, function($feature) { 
                                $categories = is_array($feature['categories']) ? $feature['categories'] : array($feature['categories']);
                                return in_array('functionality', $categories); 
                            })); ?></span>
                        </button>
                        <button class="b2bking-category-tab" data-category="integration">
                            <?php esc_html_e('Integration', 'b2bking'); ?>
                            <span class="b2bking-tab-count"><?php echo count(array_filter($early_access_features, function($feature) { 
                                $categories = is_array($feature['categories']) ? $feature['categories'] : array($feature['categories']);
                                return in_array('integration', $categories); 
                            })); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Features Grid -->
                <div class="b2bking-features-grid">
                    <?php foreach ($early_access_features as $feature): ?>
                    <div class="b2bking-feature-card <?php echo $feature['enabled'] ? 'b2bking-feature-active' : 'b2bking-feature-inactive'; ?>" data-category="<?php echo esc_attr(is_array($feature['categories']) ? implode(' ', $feature['categories']) : $feature['categories']); ?>" data-feature-id="<?php echo esc_attr($feature['id']); ?>">
                        <div class="b2bking-feature-card-header">
                            <div class="b2bking-feature-info">
                                <div class="b2bking-feature-icon">
                                    <?php echo $feature['icon']; ?>
                                </div>
                                <div class="b2bking-feature-details">
                                    <h3 class="b2bking-feature-title"><?php echo esc_html($feature['title']); ?></h3>
                                    <p class="b2bking-feature-description"><?php echo esc_html($feature['description']); ?></p>
                                    
                                </div>
                            </div>
                            <div class="b2bking-feature-actions">
                                <label class="b2bking-toggle-switch">
                                    <input type="checkbox" class="b2bking-feature-enabled" data-feature-id="<?php echo esc_attr($feature['id']); ?>" <?php checked($feature['enabled']); ?>>
                                    <span class="b2bking-toggle-slider"></span>
                                </label>
                                <button class="b2bking-feature-info-btn" data-feature-id="<?php echo esc_attr($feature['id']); ?>" title="<?php esc_attr_e('More Info', 'b2bking'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 16V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="b2bking-feature-card-content">
                            <div class="b2bking-feature-details-section">
                                <div class="b2bking-feature-meta">
                                    <?php 
                                    $categories = is_array($feature['categories']) ? $feature['categories'] : array($feature['categories']);
                                    foreach ($categories as $category): 
                                    ?>
                                        <span class="b2bking-feature-category"><?php echo esc_html($this->get_category_display_name($category)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Feature Info Modal -->
        <div id="b2bking-feature-info-modal" class="b2bking-modal">
            <div class="b2bking-modal-content">
                <div class="b2bking-modal-header">
                    <h2 id="b2bking-modal-title"></h2>
                    <button class="b2bking-modal-close">&times;</button>
                </div>
                <div class="b2bking-modal-body">
                    <div id="b2bking-modal-content"></div>
                </div>
            </div>
        </div>

        <!-- Safety Info Modal -->
        <div id="b2bking-safety-info-modal" class="b2bking-modal">
            <div class="b2bking-modal-content">
                <div class="b2bking-modal-header">
                    <h2><?php esc_html_e('About Early Access Features', 'b2bking'); ?></h2>
                    <button class="b2bking-modal-close">&times;</button>
                </div>
                <div class="b2bking-modal-body">
                    <div class="b2bking-safety-modal-content">
                        <div class="b2bking-safety-section">
                            <h3><?php esc_html_e('🔄 Always Reversible', 'b2bking'); ?></h3>
                            <p><?php esc_html_e('These features are designed to be completely reversible. If they do not work out for you for any reason, you can turn them off / go back to the classic version.', 'b2bking'); ?></p>
                        </div>
                        
                        <div class="b2bking-safety-section">
                            <h3><?php esc_html_e('🚀 New Iterations of Existing Features', 'b2bking'); ?></h3>
                            <p><?php esc_html_e('Some options here represent improved versions of features you already know. They start as optional here, will become the default later, and eventually the old versions will be phased out.', 'b2bking'); ?></p>
                        </div>
                        
                        <div class="b2bking-safety-section">
                            <h3><?php esc_html_e('💬 Test & Provide Feedback', 'b2bking'); ?></h3>
                            <p><?php esc_html_e('We\'d love to hear your feedback! Let us know what works well, what could be improved, or if you encounter any issues.', 'b2bking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Modal -->
        <div id="b2bking-feedback-modal" class="b2bking-modal">
            <div class="b2bking-modal-content">
                <div class="b2bking-modal-header">
                    <h2><?php esc_html_e('Send Feedback', 'b2bking'); ?></h2>
                    <button class="b2bking-modal-close">&times;</button>
                </div>
                <div class="b2bking-modal-body">
                    <div class="b2bking-feedback-form">
                        <p class="b2bking-feedback-intro"><?php esc_html_e('Help us improve B2BKing by sharing your experience with early access features. Your feedback is valuable and helps shape the future of the plugin.', 'b2bking'); ?></p>
                        
                        <form id="b2bking-feedback-form">
                            <div class="b2bking-form-group">
                                <label for="feedback-type"><?php esc_html_e('Feedback Type', 'b2bking'); ?></label>
                                <select id="feedback-type" name="feedback_type" required>
                                    <option value=""><?php esc_html_e('Select feedback type...', 'b2bking'); ?></option>
                                    <option value="bug"><?php esc_html_e('🐛 Bug Report', 'b2bking'); ?></option>
                                    <option value="suggestion"><?php esc_html_e('💡 Feature Suggestion', 'b2bking'); ?></option>
                                    <option value="improvement"><?php esc_html_e('⚡ Improvement Idea', 'b2bking'); ?></option>
                                    <option value="praise"><?php esc_html_e('👍 Positive Feedback', 'b2bking'); ?></option>
                                    <option value="other"><?php esc_html_e('📝 Other', 'b2bking'); ?></option>
                                </select>
                            </div>
                            
                            <div class="b2bking-form-group">
                                <label for="feedback-feature"><?php esc_html_e('Related Feature (Optional)', 'b2bking'); ?></label>
                                <select id="feedback-feature" name="feature_id">
                                    <option value=""><?php esc_html_e('Select feature...', 'b2bking'); ?></option>
                                    <?php foreach ($early_access_features as $feature): ?>
                                    <option value="<?php echo esc_attr($feature['id']); ?>"><?php echo esc_html($feature['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="b2bking-form-group">
                                <label for="feedback-message"><?php esc_html_e('Your Feedback', 'b2bking'); ?> <span class="required">*</span></label>
                                <textarea id="feedback-message" name="message" rows="6" placeholder="<?php esc_attr_e('Please describe your feedback in detail. Include steps to reproduce if reporting a bug, or explain your suggestion clearly...', 'b2bking'); ?>" required></textarea>
                            </div>
                            
                            <div class="b2bking-form-group">
                                <label for="feedback-email"><?php esc_html_e('Your Email (Optional)', 'b2bking'); ?></label>
                                <input type="email" id="feedback-email" name="email" placeholder="<?php esc_attr_e('your@email.com', 'b2bking'); ?>">
                            </div>
                            
                            <div class="b2bking-feedback-actions">
                                <button type="button" class="b2bking-btn b2bking-btn-secondary" id="b2bking-feedback-cancel"><?php esc_html_e('Cancel', 'b2bking'); ?></button>
                                <button type="submit" class="b2bking-btn b2bking-btn-primary" id="b2bking-feedback-submit">
                                    <span class="b2bking-btn-text"><?php esc_html_e('Send Feedback', 'b2bking'); ?></span>
                                    <span class="b2bking-btn-loading" style="display: none;"><?php esc_html_e('Sending...', 'b2bking'); ?></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get category display name
     */
    private function get_category_display_name($category) {
        $category_names = array(
            'ui' => esc_html__('UI/UX','b2bking'),
            'functionality' => esc_html__('Functionality','b2bking'),
            'integration' => esc_html__('Integration','b2bking'),
            'performance' => esc_html__('Performance','b2bking')
        );
        
        return isset($category_names[$category]) ? $category_names[$category] : ucfirst($category);
    }
    
    /**
     * Get all Early Access features
     */
    public function get_early_access_features() {
        $features = array();
        
        /* ICONS:

        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    
        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="#906a1d" stroke-width="2"/><path d="M23 21V19C23 18.1645 22.7155 17.3541 22.2094 16.6977C21.7033 16.0413 20.9999 15.5754 20.2 15.375" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'

        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 3V21H21" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 9L12 6L16 10L20 6" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'

        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V7" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 7V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V7" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 11H16" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 15H12" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'

        
        // Conversations
        $features[] = array(
            'id' => 'conversations',
            'title' => esc_html__('Conversations', 'b2bking'),
            'description' => esc_html__('Next-generation conversation system with advanced sorting, search, modern UI, quick view, and AJAX loading', 'b2bking'),
            'category' => 'functionality',
            'version' => '3.0.0',
            'status' => 'alpha',
            'impact' => 'medium',
            'enabled' => get_option('b2bking_early_access_conversations', 'no') === 'yes',
            'benefits' => array(
                esc_html__('Advanced conversation sorting', 'b2bking'),
                esc_html__('Powerful search capabilities', 'b2bking'),
                esc_html__('Modern, intuitive interface', 'b2bking'),
                esc_html__('Quick view functionality', 'b2bking'),
                esc_html__('AJAX-powered loading', 'b2bking'),
                esc_html__('Enhanced user experience', 'b2bking')
            ),
            'notes' => esc_html__('This is a complete rewrite of the existing Conversations feature. Existing conversation data will be preserved and migrated automatically.', 'b2bking'),
            'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 9H16" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 13H12" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        );
        */
        
        // Group Rules
        $features[] = array(
            'id' => 'group_rules',
            'title' => esc_html__('Group Rules', 'b2bking'),
            'description' => esc_html__('Completely redesigned group management interface with modern UI, enhanced rule conditions, and intuitive individual rule editor', 'b2bking'),
            'categories' => array('ui', 'functionality'),
            'enabled' => get_option('b2bking_early_access_group_rules', 'no') === 'yes',
            'benefits' => array(
                esc_html__('Automatic user group transitions', 'b2bking'),
                esc_html__('Flexible spending period options', 'b2bking'),
                esc_html__('Enhanced rules configuration', 'b2bking'),
                esc_html__('Improved visual interface', 'b2bking'),
                esc_html__('Better rule management tools', 'b2bking'),
                esc_html__('Advanced condition settings', 'b2bking')
            ),
            'notes' => esc_html__('This is a complete redesign of the existing Group Rules feature. After enabling this, simply go to your existing Group Rules page and you\'ll see an entirely new, modern interface with enhanced functionality. All your existing rules will be preserved. This update also adds new functionality and many new group rule conditions.', 'b2bking'),
            'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 21L22 18M22 18L19 15M22 18H16M15.5 3.29076C16.9659 3.88415 18 5.32131 18 7C18 8.67869 16.9659 10.1159 15.5 10.7092M12 15H8C6.13623 15 5.20435 15 4.46927 15.3045C3.48915 15.7105 2.71046 16.4892 2.30448 17.4693C2 18.2044 2 19.1362 2 21M13.5 7C13.5 9.20914 11.7091 11 9.5 11C7.29086 11 5.5 9.20914 5.5 7C5.5 4.79086 7.29086 3 9.5 3C11.7091 3 13.5 4.79086 13.5 7Z" stroke="#906a1d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        );
        
        // Dynamic Rules
        $features[] = array(
            'id' => 'dynamic_rules',
            'title' => esc_html__('Dynamic Rules', 'b2bking'),
            'description' => esc_html__('Advanced UI with better filtering, search, organization, drag & drop, and new rule builder interface for all dynamic rule types', 'b2bking'),
            'categories' => array('ui', 'functionality'),
            'enabled' => get_option('b2bking_early_access_dynamic_rules', 'no') === 'yes',
            'benefits' => array(
                esc_html__('Advanced filtering and search', 'b2bking'),
                esc_html__('Drag & drop rule organization', 'b2bking'),
                esc_html__('New rule builder interface', 'b2bking'),
                esc_html__('Better visual organization', 'b2bking'),
                esc_html__('Enhanced user experience', 'b2bking')
            ),
            'notes' => esc_html__('This is a complete UI redesign of the existing Dynamic Rules feature. After enabling this, simply go to your existing Dynamic Rules page and you\'ll see an entirely new, modern interface with advanced filtering, search, and organization tools. All your existing rules will be preserved. Future releases will include new functionality like rule import & export capabilities.', 'b2bking'),
            'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.62442 4.4489C9.50121 3.69796 10.6208 3.25 12 3.25C13.3792 3.25 14.4988 3.69796 16.3756 4.4489L19.3451 5.6367C20.2996 6.01851 21.0728 6.32776 21.6035 6.60601C21.8721 6.74683 22.1323 6.90648 22.333 7.09894C22.5392 7.29668 22.75 7.59658 22.75 8C22.75 8.40342 22.5392 8.70332 22.333 8.90106C22.1323 9.09352 21.8721 9.25317 21.6035 9.39399C21.0728 9.67223 20.2996 9.98148 19.3451 10.3633L16.3756 11.5511C14.4988 12.302 13.3792 12.75 12 12.75C10.6208 12.75 9.50121 12.302 7.62443 11.5511L4.65495 10.3633C3.70037 9.98149 2.9272 9.67223 2.39647 9.39399C2.12786 9.25317 1.86765 9.09352 1.66701 8.90106C1.46085 8.70332 1.25 8.40342 1.25 8C1.25 7.59658 1.46085 7.29668 1.66701 7.09894C1.86765 6.90648 2.12786 6.74683 2.39647 6.60601C2.92721 6.32776 3.70037 6.01851 4.65496 5.63669L7.62442 4.4489Z" fill="#906a1d"/><path fill-rule="evenodd" clip-rule="evenodd" d="M2.50053 11.4415C2.50053 11.4415 2.50053 11.4415 2.50053 11.4415L2.49913 11.4402L2.50261 11.4432C2.50702 11.4471 2.51522 11.4541 2.52722 11.4641C2.55123 11.4842 2.59042 11.5161 2.64479 11.5581C2.75354 11.6422 2.92289 11.7663 3.1528 11.9154C3.61265 12.2136 4.31419 12.6115 5.25737 12.9887L8.06584 14.1121C10.0907 14.922 10.9396 15.25 12 15.25C13.0604 15.25 13.9093 14.922 15.9342 14.1121L18.7426 12.9887C19.6858 12.6115 20.3874 12.2136 20.8472 11.9154C21.0771 11.7663 21.2465 11.6422 21.3552 11.5581C21.4096 11.5161 21.4488 11.4842 21.4728 11.4641C21.4848 11.4541 21.493 11.4471 21.4974 11.4432L21.4995 11.4415C21.5 11.441 21.5006 11.4405 21.5011 11.44C21.8095 11.1652 22.2823 11.1915 22.5583 11.4992C22.8349 11.8075 22.8092 12.2817 22.5008 12.5583L22 12C22.5008 12.5583 22.501 12.5581 22.5008 12.5583L22.4994 12.5595L22.4977 12.5611L22.493 12.5652L22.4793 12.5772C22.4682 12.5868 22.4532 12.5997 22.4341 12.6155C22.3961 12.6473 22.3422 12.6911 22.2724 12.745C22.1329 12.8528 21.9299 13.001 21.6634 13.1739C21.1303 13.5196 20.3424 13.9644 19.2997 14.3814L16.4912 15.5048C16.4524 15.5204 16.4138 15.5358 16.3756 15.5511C14.4988 16.302 13.3792 16.75 12 16.75C10.6208 16.75 9.50121 16.302 7.62442 15.5511C7.58619 15.5358 7.54763 15.5204 7.50875 15.5048L4.70029 14.3814C3.65759 13.9644 2.86971 13.5196 2.33662 13.1739C2.07005 13.001 1.86705 12.8528 1.72757 12.745C1.65782 12.6911 1.60392 12.6473 1.56587 12.6155C1.54684 12.5997 1.53177 12.5868 1.52066 12.5772L1.50696 12.5652L1.50233 12.5611L1.50057 12.5595L1.4995 12.5586C1.49934 12.5584 1.49919 12.5583 2 12L1.4995 12.5586C1.19116 12.282 1.16512 11.8075 1.44171 11.4992C1.71775 11.1915 2.19075 11.1654 2.49913 11.4402M2.50053 11.4415C2.50053 11.4415 2.50053 11.4415 2.50053 11.4415V11.4415ZM2.49896 15.4401C2.19058 15.1652 1.71775 15.1915 1.44171 15.4992L2.49896 15.4401ZM2.49896 15.4401L2.50261 15.4432C2.50702 15.4471 2.51522 15.4541 2.52722 15.4641C2.55123 15.4842 2.59042 15.5161 2.64479 15.5581C2.75354 15.6422 2.92289 15.7663 3.1528 15.9154C3.61265 16.2136 4.31419 16.6114 5.25737 16.9887L8.06584 18.1121C10.0907 18.922 10.9396 19.25 12 19.25C13.0604 19.25 13.9093 18.922 15.9342 18.1121L18.7426 16.9887C19.6858 16.6114 20.3874 16.2136 20.8472 15.9154C21.0771 15.7663 21.2465 15.6422 21.3552 15.5581C21.4096 15.5161 21.4488 15.4842 21.4728 15.4641C21.4848 15.4541 21.493 15.4471 21.4974 15.4432L21.4995 15.4415C21.5 15.441 21.5006 15.4405 21.5011 15.44C21.8095 15.1652 22.2823 15.1915 22.5583 15.4992C22.8349 15.8075 22.8092 16.2817 22.5008 16.5583L22.0166 16.0185C22.5008 16.5583 22.501 16.5581 22.5008 16.5583L22.4994 16.5595L22.4977 16.5611L22.493 16.5652L22.4793 16.5772C22.4682 16.5868 22.4532 16.5997 22.4341 16.6155C22.3961 16.6473 22.3422 16.6911 22.2724 16.745C22.1329 16.8528 21.9299 17.001 21.6634 17.1739C21.1303 17.5196 20.3424 17.9644 19.2997 18.3814L16.4912 19.5048C16.4524 19.5204 16.4138 19.5358 16.3756 19.5511C14.4988 20.302 13.3792 20.75 12 20.75C10.6208 20.75 9.50121 20.302 7.62443 19.5511C7.58619 19.5358 7.54763 19.5204 7.50875 19.5048L4.70029 18.3814C3.65759 17.9644 2.86971 17.5196 2.33662 17.1739C2.07005 17.001 1.86705 16.8528 1.72757 16.745C1.65782 16.6911 1.60392 16.6473 1.56587 16.6155C1.54684 16.5997 1.53177 16.5868 1.52066 16.5772L1.50696 16.5652L1.50233 16.5611L1.50057 16.5595L1.4995 16.5586C1.49934 16.5584 1.49919 16.5583 2 16L1.4995 16.5586C1.19116 16.282 1.16512 15.8075 1.44171 15.4992" fill="#906a1d"/></svg>'
        );
        
        return $features;
    }
    
    /**
     * Toggle feature via AJAX
     */
    public function toggle_feature() {
        // Verify nonce - accept both early access nonce and main b2bking nonce
        $nonce_valid = false;
        
        // Check early access specific nonce
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'b2bking_early_access_nonce')) {
            $nonce_valid = true;
        }
        
        // Check main b2bking nonce (for AJAX page switching)
        if (!$nonce_valid && isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'b2bking_security_nonce')) {
            $nonce_valid = true;
        }
        
        if (!$nonce_valid) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can(apply_filters('b2bking_backend_capability_needed', 'manage_woocommerce'))) {
            wp_die('Insufficient permissions');
        }

        $feature_id = sanitize_text_field($_POST['feature_id']);
        $enabled = sanitize_text_field($_POST['enabled']) === 'true';

        // Update the feature option
        $option_name = 'b2bking_early_access_' . $feature_id;
        update_option($option_name, $enabled ? 'yes' : 'no');

        // Log the action
        error_log("B2BKing Early Access: Feature '{$feature_id}' " . ($enabled ? 'enabled' : 'disabled') . " by user " . get_current_user_id());

        wp_send_json_success(array(
            'message' => $enabled ? esc_html__('Feature enabled successfully', 'b2bking') : esc_html__('Feature disabled successfully', 'b2bking'),
            'feature_id' => $feature_id,
            'enabled' => $enabled
        ));
    }
    
    /**
     * Get feature info via AJAX
     */
    public function get_feature_info() {
        // Verify nonce - accept both early access nonce and main b2bking nonce
        $nonce_valid = false;
        
        // Check early access specific nonce
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'b2bking_early_access_nonce')) {
            $nonce_valid = true;
        }
        
        // Check main b2bking nonce (for AJAX page switching)
        if (!$nonce_valid && isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'b2bking_security_nonce')) {
            $nonce_valid = true;
        }
        
        if (!$nonce_valid) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can(apply_filters('b2bking_backend_capability_needed', 'manage_woocommerce'))) {
            wp_die('Insufficient permissions');
        }

        $feature_id = sanitize_text_field($_POST['feature_id']);
        $features = $this->get_early_access_features();
        
        $feature = null;
        foreach ($features as $f) {
            if ($f['id'] === $feature_id) {
                $feature = $f;
                break;
            }
        }

        if (!$feature) {
            wp_send_json_error(esc_html__('Feature not found', 'b2bking'));
        }

        wp_send_json_success($feature);
    }
    
    /**
     * Handle feedback form submission
     */
    public function submit_feedback() {
        // Verify nonce - accept both early access nonce and main b2bking nonce
        $nonce_valid = false;
        
        // Check early access specific nonce
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'b2bking_early_access_nonce')) {
            $nonce_valid = true;
        }
        
        // Check main b2bking nonce (for AJAX page switching)
        if (!$nonce_valid && isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'b2bking_security_nonce')) {
            $nonce_valid = true;
        }
        
        if (!$nonce_valid) {
            wp_send_json_error(esc_html__('Security check failed', 'b2bking'));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Insufficient permissions', 'b2bking'));
        }
        
        // Sanitize and validate input
        $feedback_type = sanitize_text_field($_POST['feedback_type']);
        $feature_id = sanitize_text_field($_POST['feature_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $email = sanitize_email($_POST['email']);
        
        // Validate required fields
        if (empty($feedback_type) || empty($message)) {
            wp_send_json_error(esc_html__('Please fill in all required fields', 'b2bking'));
        }
        
        // Get feature title if feature_id is provided
        $feature_title = '';
        if (!empty($feature_id)) {
            $features = $this->get_early_access_features();
            foreach ($features as $feature) {
                if ($feature['id'] === $feature_id) {
                    $feature_title = $feature['title'];
                    break;
                }
            }
        }
        
        // Get license information
        $license = get_option('b2bking_license_key_setting', '');
        $license_email = get_option('b2bking_license_email_setting', '');
        
        // Prepare email content
        $subject = sprintf(esc_html__('B2BKing Early Access Feedback: %s', 'b2bking'), ucfirst($feedback_type));
        
        $email_content = sprintf(
            esc_html__("New feedback received from B2BKing Early Access Features:\n\n", 'b2bking') .
            esc_html__("Feedback Type: %s\n", 'b2bking') .
            esc_html__("Message: %s\n\n", 'b2bking') .
            esc_html__("Site Information:\n", 'b2bking') .
            esc_html__("Site URL: %s\n", 'b2bking') .
            esc_html__("WordPress Version: %s\n", 'b2bking') .
            esc_html__("B2BKing Version: %s\n", 'b2bking') .
            esc_html__("User Email: %s\n", 'b2bking') .
            esc_html__("User Role: %s\n", 'b2bking') .
            esc_html__("License Key: %s\n", 'b2bking') .
            esc_html__("License Email: %s\n", 'b2bking') .
            esc_html__("Submitted: %s\n", 'b2bking'),
            ucfirst($feedback_type),
            $message,
            get_site_url(),
            get_bloginfo('version'),
            defined('B2BKING_VERSION') ? B2BKING_VERSION : 'Unknown',
            !empty($email) ? $email : esc_html__('Not provided', 'b2bking'),
            wp_get_current_user()->roles[0] ?? esc_html__('Unknown', 'b2bking'),
            !empty($license) ? $license : esc_html__('Not set', 'b2bking'),
            !empty($license_email) ? $license_email : esc_html__('Not set', 'b2bking'),
            current_time('mysql')
        );
        
        // Add feature information if provided
        if (!empty($feature_title)) {
            $email_content .= sprintf(esc_html__("\nRelated Feature: %s\n", 'b2bking'), $feature_title);
        }
        
        // Set email headers
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        if (!empty($email)) {
            $headers[] = sprintf('Reply-To: %s <%s>', get_bloginfo('name'), $email);
        }
        
        // Send email
        $sent = wp_mail('contact@webwizards.dev', $subject, $email_content, $headers);
        
        if ($sent) {
            wp_send_json_success(esc_html__('Thank you for your feedback! We appreciate your input.', 'b2bking'));
        } else {
            wp_send_json_error(esc_html__('Failed to send feedback. Please try again later.', 'b2bking'));
        }
    }
}

// Initialize the module
B2BKing_Early_Access_Module::get_instance();
