<?php
/*
    Plugin Name: Enovathemes add-ons
    Plugin URI: http://www.enovathemes.com
    Text Domain: enovathemes-addons
    Domain Path: /languages/
    Description: Plugin comes with Enovathemes to extend theme functionality
    Author: Enovathemes
    Version: 3.3
    License: GNU General Public License version 3.0
    Author URI: http://enovathemes.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');

$plugin_file = 'enovathemes-addons/enovathemes-addons.php';
$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file,false,false);

define( 'ENOVATHEMES_ADDONS', plugin_dir_path( __FILE__ ));
define( 'THEME_IMG', get_template_directory_uri().'/images/');
define( 'THEME_SVG', THEME_IMG.'icons/');
define( 'PLUGIN_VERSION', $plugin_data['Version']);

function enovathemes_addons_load_plugin_textdomain() {
    load_plugin_textdomain( 'enovathemes-addons', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}
add_action( 'init', 'enovathemes_addons_load_plugin_textdomain' );

if (! defined( 'KIRKI_PLUGIN_FILE' ) && file_exists( ENOVATHEMES_ADDONS . 'includes/kirki-master/kirki.php' ) ) {
    require_once('includes/kirki-master/kirki.php' );
    require_once('includes/customize.php' );
}

require_once('includes/dynamic-styles.php' );
require_once('includes/cpt.php' );
require_once('includes/update-3.0.php' );
require_once('includes/cmb2.php' );
require_once('widgets/widget-banner.php' );
require_once('widgets/widget-login.php' );
require_once('widgets/widget-posts.php' );
require_once('widgets/widget-mailchimp.php' );
require_once('widgets/widget-facebook.php' );
require_once('widgets/widget-product-search.php' );
require_once('widgets/widget-product-filter.php' );
require_once('widgets/widget-product-vehicle-filter.php' );
require_once('widgets/widget-user-vehicle-filter.php' );
require_once('includes/elementor/custom-control-image-select.php' );
require_once('includes/MobileDetect/vendor/autoload.php');
require_once('includes/MailchimpMarketing/vendor/autoload.php');

/*  Optimize
/*-------------------*/

    function enovathemes_addons_disable_emojis() {

        $optimize  = (!empty(get_theme_mod('optimize'))) ? true : false;

        if ($optimize) {

            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
            remove_action( 'wp_print_styles', 'wp_enqueue_emoji_styles' );
            remove_action( 'admin_print_styles', 'wp_enqueue_emoji_styles' );    
            remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
            remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );  
            remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
            
            // Remove from TinyMCE
            add_filter( 'tiny_mce_plugins', 'enovathemes_addons_disable_emojis_tinymce' );
            
            
        }

        // Disable google fonts from elementor to collect them in dynamic styles from the website
        add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

        add_action('elementor/frontend/after_register_styles',function() {
            foreach( [ 'solid', 'regular', 'brands' ] as $style ) {
                wp_deregister_style( 'elementor-icons-fa-' . $style );
            }
        }, 20 );

        add_action( 'wp_enqueue_scripts', 'enovathemes_addons_disable_elementor_icons', 11 );
        function enovathemes_addons_disable_elementor_icons() {
            wp_deregister_style( 'elementor-fontawesome');
            wp_dequeue_style( 'elementor-fontawesome');
        }
    }
    add_action( 'init', 'enovathemes_addons_disable_emojis' );

    function enovathemes_addons_disable_emojis_tinymce( $plugins ) {
        if ( is_array( $plugins ) ) {
            return array_diff( $plugins, array( 'wpemoji' ) );
        } else {
            return array();
        }
    }


/*  Elementor widgets
/*-------------------*/


    add_action( 'init', function(){


        if (function_exists('is_plugin_active') && is_plugin_active( 'elementor/elementor.php' )) {

            add_action('elementor/frontend/after_register_styles',function() {
                $optimize      = !empty(get_theme_mod('optimize')) ? true : false;
                $font_awesome  = !empty(get_theme_mod('font_awesome')) ? true : false;

                if ($optimize) {
                    $font_awesome = true;
                }

                if ($font_awesome) {
                    foreach( [ 'solid', 'regular', 'brands' ] as $style ) {
                        wp_deregister_style( 'elementor-icons-fa-' . $style );
                    }
                }
            }, 20 );

            function enovathemes_addons_add_elementor_widget_categories( $elements_manager ) {
                // Add custom categories with high priority so they appear first
                $elements_manager->add_category(
                    'header-builder',
                    [
                        'title' => esc_html__( 'Header builder', 'enovathemes-addons' ),
                        'icon'  => 'fa-solid fa-e',
                    ]
                );

                $elements_manager->add_category(
                    'enovathemes',
                    [
                        'title' => esc_html__( 'Enovathemes', 'enovathemes-addons' ),
                        'icon'  => 'fa-solid fa-e',
                    ]
                );
            }

            // Using priority 1 to ensure the custom categories are added before others
            add_action( 'elementor/elements/categories_registered', 'enovathemes_addons_add_elementor_widget_categories', 1 );


            function enovathemes_addons_elementor_widgets( $widgets_manager ) {

                require_once('widgets/elementor/widget-logo.php' );
                require_once('widgets/elementor/widget-desktop-menu.php' );
                require_once('widgets/elementor/widget-breadcrumbs.php' );
                require_once('widgets/elementor/widget-mobile-menu.php' );
                require_once('widgets/elementor/widget-sidebar-menu.php' );
                require_once('widgets/elementor/widget-megamenu.php' );
                require_once('widgets/elementor/widget-button.php' );
                require_once('widgets/elementor/widget-product-search.php' );
                require_once('widgets/elementor/widget-currency-switcher.php' );
                require_once('widgets/elementor/widget-language-switcher.php' );
                require_once('widgets/elementor/widget-mini-cart.php' );
                require_once('widgets/elementor/widget-login.php' );
                require_once('widgets/elementor/widget-wishlist.php' );
                require_once('widgets/elementor/widget-compare.php' );
                require_once('widgets/elementor/widget-social-links.php' );
                require_once('widgets/elementor/widget-icon.php' );
                require_once('widgets/elementor/widget-mobile-toggle.php' );
                require_once('widgets/elementor/widget-heading.php' );
                require_once('widgets/elementor/widget-text.php' );
                require_once('widgets/elementor/widget-separator.php' );
                require_once('widgets/elementor/widget-gap.php' );
                require_once('widgets/elementor/widget-icon-list.php' );
                // require_once('widgets/elementor/widget-sticky-dashboard.php' );
                require_once('widgets/elementor/widget-menu-list.php' );
                require_once('widgets/elementor/widget-tabs.php' );
                require_once('widgets/elementor/widget-accordion.php' );
                require_once('widgets/elementor/widget-mailchimp.php' );
                require_once('widgets/elementor/widget-icon-box.php' );
                require_once('widgets/elementor/widget-pricing-table.php' );
                require_once('widgets/elementor/widget-testimonials.php' );
                require_once('widgets/elementor/widget-clients.php' );
                require_once('widgets/elementor/widget-person.php' );
                require_once('widgets/elementor/widget-image.php' );
                require_once('widgets/elementor/widget-gallery.php' );
                require_once('widgets/elementor/widget-video.php' );
                require_once('widgets/elementor/widget-counter.php' );
                require_once('widgets/elementor/widget-progress.php' );
                require_once('widgets/elementor/widget-timer.php' );
                require_once('widgets/elementor/widget-products.php' );
                require_once('widgets/elementor/widget-attribute.php' );
                require_once('widgets/elementor/widget-posts.php' );
                require_once('widgets/elementor/widget-terms.php' );
                require_once('widgets/elementor/widget-make.php' );
                require_once('widgets/elementor/widget-mobile-container-top.php' );
                require_once('widgets/elementor/widget-user-vehicle-filter.php' );
                require_once('widgets/elementor/widget-product-vehicle-filter.php' );

                $widgets_manager->register( new \Elementor_Widget_Logo() );
                $widgets_manager->register( new \Elementor_Widget_Desktop_Menu() );
                $widgets_manager->register( new \Elementor_Widget_Breadcrumbs() );
                $widgets_manager->register( new \Elementor_Widget_Mobile_Menu() );
                $widgets_manager->register( new \Elementor_Widget_Sidebar_Menu() );
                $widgets_manager->register( new \Elementor_Widget_Megamenu() );
                $widgets_manager->register( new \Elementor_Widget_Button() );
                $widgets_manager->register( new \Elementor_Widget_Product_Search() );
                $widgets_manager->register( new \Elementor_Widget_Currency_Switcher() );
                $widgets_manager->register( new \Elementor_Widget_Language_Switcher() );
                $widgets_manager->register( new \Elementor_Widget_Mini_Cart() );
                $widgets_manager->register( new \Elementor_Widget_Login() );
                $widgets_manager->register( new \Elementor_Widget_Wishlist() );
                $widgets_manager->register( new \Elementor_Widget_Compare() );
                $widgets_manager->register( new \Elementor_Widget_Social_Links() );
                $widgets_manager->register( new \Elementor_Widget_Icon() );
                $widgets_manager->register( new \Elementor_Widget_Mobile_Toggle() );
                $widgets_manager->register( new \Elementor_Widget_Heading() );
                $widgets_manager->register( new \Elementor_Widget_Text() );
                $widgets_manager->register( new \Elementor_Widget_Separator() );
                $widgets_manager->register( new \Elementor_Widget_Gap() );
                $widgets_manager->register( new \Elementor_Widget_Icon_List() );
                // $widgets_manager->register( new \Elementor_Widget_Sticky_Dashboard() );
                $widgets_manager->register( new \Elementor_Widget_Menu_List() );
                $widgets_manager->register( new \Elementor_Widget_Tabs() );
                $widgets_manager->register( new \Elementor_Widget_Et_Accordion() );
                $widgets_manager->register( new \Elementor_Widget_Et_Mailchimp() );
                $widgets_manager->register( new \Elementor_Widget_Icon_Box() );
                $widgets_manager->register( new \Elementor_Widget_Pricing_Table() );
                $widgets_manager->register( new \Elementor_Widget_Testimonials() );
                $widgets_manager->register( new \Elementor_Widget_Clients() );
                $widgets_manager->register( new \Elementor_Widget_Person() );
                $widgets_manager->register( new \Elementor_Widget_Image() );
                $widgets_manager->register( new \Elementor_Widget_Gallery() );
                $widgets_manager->register( new \Elementor_Widget_Video() );
                $widgets_manager->register( new \Elementor_Widget_Counter() );
                $widgets_manager->register( new \Elementor_Widget_Progress() );
                $widgets_manager->register( new \Elementor_Widget_Timer() );
                $widgets_manager->register( new \Elementor_Widget_Products() );
                $widgets_manager->register( new \Elementor_Widget_Attribute() );
                $widgets_manager->register( new \Elementor_Widget_Posts() );
                $widgets_manager->register( new \Elementor_Widget_Terms() );
                $widgets_manager->register( new \Elementor_Widget_Make() );
                $widgets_manager->register( new \Elementor_Widget_Mobile_Container_Top() );
                $widgets_manager->register( new \Elementor_Widget_User_Vehicle_Filter() );
                $widgets_manager->register( new \Elementor_Widget_Product_Vehicle_Filter() );

            }
            add_action( 'elementor/widgets/register', 'enovathemes_addons_elementor_widgets' );

            require_once('widgets/elementor/widget-extend.php' );

            
        }
    });

/*  Scripts
/*-------------------*/

    function enovathemes_addons_script(){

        global $wp_query;

        $mods = et_get_theme_mods();

        $shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
        if ('' === get_option( 'permalink_structure' )) {
            $shop_link = get_home_url().'?post_type=product';
        }

        $vehicle_params = apply_filters( 'vehicle_params','');
        $wishlist  = (!empty(get_theme_mod('product_wishlist'))) ? "true" : "false";
        $compare   = (!empty(get_theme_mod('product_compare'))) ? "true" : "false";
        $quickview = (!empty(get_theme_mod('product_quick_view'))) ? "true" : "false";

        wp_register_script( 'widget-product-filter-select', plugins_url('/js/widget-product-filter-select.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, true);
        wp_localize_script(
            'widget-product-filter-select',
            'pfilter_select_opt',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'error'   => esc_html__("Something was wrong, please try later or contact site administrator","enovathemes-addons"),
            )
        );

        wp_register_script( 'widget-product-filter', plugins_url('/js/widget-product-filter.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, true);
        wp_localize_script(
            'widget-product-filter',
            'pfilter_opt',
            array(
                'lang'           => (is_rtl() ? 'rtl' : 'ltr'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'error'   => esc_html__("Something was wrong, please try later or contact site administrator","enovathemes-addons"),
                'total'   => esc_html__("products found","enovathemes-addons"),
                'shopURL' => $shop_link,
                'shopName'=> sanitize_title_with_dashes(sanitize_title_with_dashes(get_bloginfo('name'))),
                'already' => esc_html__("Product already added", 'enovathemes-addons'),
                'noMore'  => esc_html__("No more", 'enovathemes-addons'),
            )
        );

        wp_enqueue_script( 'widget-product-vehicle-filter', plugins_url('/js/widget-product-vehicle-filter.js', __FILE__ ), array('jquery','plugins-combined'), PLUGIN_VERSION, true);
        wp_localize_script(
            'widget-product-vehicle-filter',
            'vehicle_filter_opt',
            array(
                'lang'    => (is_rtl() ? 'rtl' : 'ltr'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'shopURL' => $shop_link,
                'error'   => esc_html__("Something was wrong, please try later or contact site administrator","enovathemes-addons"),
                'close'   => esc_html__("Close","enovathemes-addons"),
                'vinTitle' => esc_html__('Decode results','enovathemes-addons'),
                'vehicleParams' => json_encode($vehicle_params)
            )
        );

        wp_enqueue_script( 'widget-user-vehicle-filter', plugins_url('/js/widget-user-vehicle-filter.js', __FILE__ ), array('jquery','plugins-combined'), PLUGIN_VERSION, true);
        wp_localize_script(
            'widget-user-vehicle-filter',
            'user_vehicle_filter_opt',
            array(
                'lang'           => (is_rtl() ? 'rtl' : 'ltr'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'shopURL' => $shop_link,
                'error'   => esc_html__("Something was wrong, please try later or contact site administrator","enovathemes-addons"),
                'noVehicle'   => esc_html__("No vehicle found","enovathemes-addons"),
                'removeVehicleMessage'   => esc_html__("Remove this vehicle?","enovathemes-addons"),
                'close'   => esc_html__("Close","enovathemes-addons"),
                'login'   => esc_html__("Login to save to dashboard","enovathemes-addons"),
                'addmore'   => esc_html__("Add more","enovathemes-addons"),
                'vehicleParams' => json_encode($vehicle_params)
            )
        );

        if ($quickview == "true") {
            if (class_exists('Woocommerce')) {
                wp_enqueue_script( 'wc-add-to-cart-variation', plugins_url() . '/woocommerce/assets/js/frontend/add-to-cart-variation.min.js', array('jquery', 'wp-util', 'jquery-blockui'), '', true );
                wp_enqueue_script( 'flexslider', plugins_url() . '/woocommerce/assets/js/flexslider/jquery.flexslider.min.js', array('jquery'), '', true );
                wp_enqueue_script( 'prettyPhoto', plugins_url() . '/woocommerce/assets/js/prettyPhoto/jquery.prettyPhoto.min.js', array('jquery'), '', true );
                wp_enqueue_script( 'zoom', plugins_url() . '/woocommerce/assets/js/zoom/jquery.zoom.min.js', array('jquery'), '', true );
                wp_enqueue_style( 'woocommerce_prettyPhoto_css', plugins_url() . '/woocommerce/assets/css/prettyPhoto.css', '', '', true );
            }
            if (defined('WCVS_PLUGIN_VERSION')) {
                wp_enqueue_script( 'tawcvs-frontend', plugins_url( '/variation-swatches-for-woocommerce/assets/js/frontend.js'), array( 'jquery' ), '', true );
            }
        }

        if ($wishlist == "true") {

            wp_enqueue_script( 'widget-product-wishlist', plugins_url('/js/widget-product-wishlist.js', __FILE__ ), array('jquery','plugins-combined'), '', true);
            wp_localize_script(
                'widget-product-wishlist',
                'wishlist_opt',
                array(
                    'ajaxUrl'        => admin_url('admin-ajax.php'),
                    'ajaxPost'       => admin_url('admin-post.php'),
                    'shopName'       => sanitize_title_with_dashes(sanitize_title_with_dashes(get_bloginfo('name'))),
                    'inWishlist'     => esc_html__("Added to wishlist","enovathemes-addons"),
                    'addedWishlist'  => esc_html__("In wishlist","enovathemes-addons"),
                    'error'          => esc_html__("Something went wrong, could not add to wishlist","enovathemes-addons"),
                    'noWishlist'     => esc_html__("No products found","enovathemes-addons"),
                    'confirm'        => esc_html__("Remove the item from wishlist?","enovathemes-addons"),
                )
            );
        }

        if ($compare == "true") {

            wp_enqueue_script( 'widget-product-compare', plugins_url('/js/widget-product-compare.js', __FILE__ ), array('jquery','plugins-combined'), '', true);
            wp_localize_script(
                'widget-product-compare',
                'compare_opt',
                array(
                    'ajaxUrl'   => admin_url('admin-ajax.php'),
                    'shopName'  => sanitize_title_with_dashes(sanitize_title_with_dashes(get_bloginfo('name'))),
                    'inCompare' => esc_html__("Added to compare","enovathemes-addons"),
                    'addedCompare' => esc_html__("In compare","enovathemes-addons"),
                    'error'     => esc_html__("Something went wrong, could not add to compare","enovathemes-addons"),
                    'noCompare' => esc_html__("No products found","enovathemes-addons"),
                    'confirm'   => esc_html__("Remove the item","enovathemes-addons"),
                )
            );
        }

        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'wc-blocks-style' );
        wp_dequeue_style( 'global-styles' );

        if (is_plugin_active( 'elementor/elementor.php' )) {
            wp_enqueue_style( 'elementor-frontend' );

            $font_awesome  = (!empty(get_theme_mod('font_awesome'))) ? true : false;

            if ($font_awesome) {
                wp_enqueue_style('elementor-fontawesome',ELEMENTOR_ASSETS_URL . 'lib/font-awesome/css/all.min.css' );
            }
        }

        $shop_page_id   = function_exists('wc_get_page_id') ? wc_get_page_id( 'shop' ) : '';
        $shop_link = '';

        if ($shop_page_id) {
            $shop_link = get_permalink( wc_get_page_id( 'shop' ) ); // Default permalink
        }

        if ( defined( 'ICL_LANGUAGE_CODE' ) && function_exists( 'apply_filters' ) ) {
            $shop_link = apply_filters( 'wpml_permalink', $shop_link, ICL_LANGUAGE_CODE );
        }elseif ( function_exists( 'pll_get_post' ) ) {
            $translated_page_id = pll_get_post( wc_get_page_id( 'shop' ) ); // Get translated post ID
            $shop_link = get_permalink( $translated_page_id );
        }

        if ('' === get_option( 'permalink_structure' )) {
            $shop_link = get_home_url().'?post_type=product';
        }

        $homepage_id = get_option('page_on_front');

        $product_ajax_filter             = $mods && isset($mods['product_ajax_filter']) && !empty($mods['product_ajax_filter']) ? 1 : 0;
        $product_ajax_filter_keywrods    = $mods && isset($mods['product_ajax_filter_keywrods']) && !empty($mods['product_ajax_filter_keywrods']) ? $mods['product_ajax_filter_keywrods'] : '';
        $product_ajax_search_threshold   = $mods && isset($mods['product_ajax_search_threshold']) && !empty($mods['product_ajax_search_threshold']) ? $mods['product_ajax_search_threshold'] : 0.1;
        $product_per_page                = $mods && isset($mods['product_number']) ? $mods['product_number'] : get_option( 'posts_per_page' );

        $permalinks = get_option('woocommerce_permalinks');
        $product_cat_base = ! empty( $permalinks['category_base'] ) 
            ? $permalinks['category_base'] 
            : 'product-category';

        $copt_args = array(
            'homeTitle'         => (empty($homepage_id) ? get_bloginfo('name') : get_the_title($homepage_id)),
            'shopName'          => sanitize_title_with_dashes(get_bloginfo('name')).'-'.et__current_language(),
            'ajaxUrl'           => admin_url('admin-ajax.php'),
            'siteUrl'           => esc_url(home_url('/')),
            'lang'              => (is_rtl() ? 'rtl' : 'ltr'),
            'threshold'         => $product_ajax_search_threshold,
            'siteUrl'           => esc_url(home_url('/')),
            'productCatBase'       => $product_cat_base
        );

        $dependance_scripts = ['jquery','plugins-combined'];

        $copt_strings = [
            'sidebarToggleShop' => esc_html__("Filter", 'enovathemes-addons'),
            'sidebarToggle'     => esc_html__("Sidebar toggle", 'enovathemes-addons'),
            'noProductsFound'   => esc_html__("No products found","enovathemes-addons"),
            'noLanguage'        => esc_html__("You can configure languages on you site using WPML or Polyland plugins. Theme supports both!", 'enovathemes-addons'),
            'termSearchText'    => esc_html__("Type a keyword","enovathemes-addons"),
            'noTermsFound'      => esc_html__("Nothing found","enovathemes-addons"),
            'widgetClear'       => esc_html__("Clear","enovathemes-addons"),
            'widgetClearAll'    => esc_html__("Clear all filters","enovathemes-addons"),
            'priceLabel'        => esc_html__("Price","enovathemes-addons"),
            'searchLabel'       => esc_html__("Search query","enovathemes-addons"),
            'defaultSortLabel'  => esc_html__("Default sorting","enovathemes-addons"),
            'clearSelection'    => esc_html__("Clear selection","enovathemes-addons"),
            'any'               => esc_html__("Any","enovathemes-addons"),
            'foundResult'       => esc_html__("Found ## result","enovathemes-addons"),
            'foundResults'      => esc_html__("Found ## results","enovathemes-addons"),
            'recommended'       => esc_html__("Recommended","enovathemes-addons"),
            'like'              => esc_html__("You may also like","enovathemes-addons"),
            'new'               => esc_html__("New in store","enovathemes-addons"),
            'viewCart'          => esc_html__("View cart","enovathemes-addons"),
            'productGallery'    => esc_html__("Gallery","enovathemes-addons"),
            'productFBT'        => esc_html__("Frequently bought together","enovathemes-addons"),
            'productDescription'=> esc_html__("Description","enovathemes-addons"),
            'productInformation'=> esc_html__("Additional information","enovathemes-addons"),
            'productReviews'    => esc_html__("Reviews","enovathemes-addons"),
            'productCompare'    => esc_html__("Compare products","enovathemes-addons"),
            'productRelated'    => esc_html__("Related products","enovathemes-addons"),
            'productViewed'     => esc_html__("Recently viewed","enovathemes-addons"),
        ];

        if (class_exists('Woocommerce')) {

            $dependance_scripts[] = 'select2';
            $dependance_scripts[] = 'wc-price-slider';
            $dependance_scripts[] = 'wc-cart-fragments';
            $dependance_scripts[] = 'wc-add-to-cart-variation';
            
            $copt_args['wc_cart_params'] = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wc_cart_nonce'),
            ];

            $shop_page_id = wc_get_page_id('shop');

            $copt_strings['SKU']                = esc_html__('SKU:','enovathemes-addons');
            $copt_strings['skuCopy']            = esc_html__('SKU ## copied','enovathemes-addons');
            $copt_strings['productInfo']        = esc_html__('Product information','enovathemes-addons');
            $copt_strings['details']            = esc_html__('Details','enovathemes-addons');
            $copt_strings['showingSingle']      = esc_html__( 'Showing the single result', 'enovathemes-addons');
            $copt_strings['showingAll']         = esc_html__( 'Showing all {total} results', 'enovathemes-addons');
            $copt_strings['showingRange']       = esc_html__( 'Showing {from}-{to} of {total} results', 'enovathemes-addons');
            $copt_strings['searchSuggestion']   = esc_html__( 'Did you mean', 'enovathemes-addons');
            $copt_strings['myAccountNav']       = esc_html__( 'My account navigation', 'enovathemes-addons');

            $copt_args['shopTitle']              = (empty($shop_page_id) ? esc_html__("Shop","enovathemes-addons") : get_the_title($shop_page_id));
            $copt_args['shopLink']               = $shop_link;
            $copt_args['productPerPage']         = $product_per_page;
            $copt_args['ajaxFilterKeywrods']     = $product_ajax_filter_keywrods;
            $copt_args['categoriesLabel']        = get_taxonomy( 'product_cat')->labels->singular_name;
            $copt_args['productAjaxFilter']      = $product_ajax_filter;
            $copt_args['currencySymbol']         = get_woocommerce_currency_symbol();
            $copt_args['currencyPosition']       = get_option( 'woocommerce_currency_pos' );
            $copt_args['activeCurrency']         = get_woocommerce_currency();
            $copt_args['defaultSort']            = get_option( 'woocommerce_default_catalog_orderby' );
            $copt_args['categoryBase']           = (get_option('woocommerce_permalinks')['category_base'] ? get_option('woocommerce_permalinks')['category_base'] : 'product-category');
            $copt_args['cartPage']               = wc_get_cart_url();

        }

        wp_enqueue_script( 'update-3.0', plugins_url('/js/update-3.0.js', __FILE__ ), $dependance_scripts, PLUGIN_VERSION, true);

        $copt_args['strings'] = $copt_strings;

        wp_localize_script(
            'update-3.0',
            'copt',
            $copt_args
        );

        /* < Dynamic google fonts
        ------------------------------------*/

            $global_dynamic_font = array();

            $desktop_header = get_theme_mod('desktop_header');
            $mobile_header  = get_theme_mod('mobile_header');
            $footer         = get_theme_mod('footer');

            if (empty($desktop_header)) {
                $desktop_header = 'default';
            }

            if (empty($mobile_header)) {
                $mobile_header = 'default';
            }

            if (empty($footer)) {
                $footer = 'default';
            }

            /* Typography
            /*-------------*/

                $main_typography     = get_theme_mod('main_typography');
                $headings_typography = get_theme_mod('headings_typography');

                if (!empty($main_typography) && array_key_exists('font-family',$main_typography)) {

                    if (!empty($main_typography['font-family'])) {
                        array_push($global_dynamic_font,$main_typography['font-family']);
                    }

                }

                if (!empty($headings_typography) && array_key_exists('font-family',$headings_typography)) {

                    if (!empty($headings_typography['font-family'])) {
                        array_push($global_dynamic_font,$headings_typography['font-family']);
                    }

                }

            /* Page
            ---------------*/

                if (is_page()) {

                    $page_desktop_header = get_post_meta( get_the_ID(), 'enovathemes_addons_desktop_header', true );
                    $page_mobile_header  = get_post_meta( get_the_ID(), 'enovathemes_addons_mobile_header', true );
                    $page_footer         = get_post_meta( get_the_ID(), 'enovathemes_addons_footer', true );

                    if ($page_desktop_header != "inherit" && !empty($page_desktop_header)) {
                        $desktop_header = $page_desktop_header;
                    }

                    if ($page_mobile_header != "inherit" && !empty($page_mobile_header)) {
                        $mobile_header = $page_mobile_header;
                    }

                    if ($page_footer != "inherit") {
                        $footer = $page_footer;
                    }

                    $elementor_data = get_post_meta(get_the_ID(),'_elementor_data',true);
                    $elementor_data = json_decode($elementor_data,true);
                    if (!empty($elementor_data)) {
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('top_typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('sub_typography_font_family',$elementor_data));
                    }

                }


            /*  Singular header
            ---------------*/

                elseif (is_singular('header')) {
                    $mobile_header = get_the_ID();
                    $desktop_header = get_the_ID();
                }

            /*  Singular footer
            ---------------*/

                elseif (is_singular('footer')) {
                    $footer = get_the_ID();
                }


            /*  Singular post
            ---------------*/

                elseif (is_singular('post')) {
                    $elementor_data = get_post_meta(get_the_ID(),'_elementor_data',true);
                    $elementor_data = json_decode($elementor_data,true);
                    if (!empty($elementor_data)) {
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('top_typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('sub_typography_font_family',$elementor_data));
                    }
                }

            /*  Singular product
            ---------------*/

                elseif (is_singular('product')) {
                    $elementor_data = get_post_meta(get_the_ID(),'_elementor_data',true);
                    $elementor_data = json_decode($elementor_data,true);
                    if (!empty($elementor_data)) {
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('top_typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('sub_typography_font_family',$elementor_data));
                    }
                }


            if ($desktop_header == $mobile_header && $desktop_header != "default") {
                $mobile_header = "none";
            }

            /*  Mobile header
            ---------------*/

                if ($mobile_header != "none" && $mobile_header != "default") {
                    $elementor_data = get_post_meta($mobile_header,'_elementor_data',true);
                    $elementor_data = json_decode($elementor_data,true);
                    if (!empty($elementor_data)) {
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('top_typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('sub_typography_font_family',$elementor_data));
                    }
                }

            /*  Desktop header
            ---------------*/

                if ($desktop_header != "none" && $desktop_header != "default") {
                    $elementor_data = get_post_meta($desktop_header,'_elementor_data',true);
                    $elementor_data = json_decode($elementor_data,true);

                    if (!empty($elementor_data)) {
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('top_typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('sub_typography_font_family',$elementor_data));
                    }
                }

             /*  Footer
            ---------------*/

                if ($footer != "none" && $footer != "default") {
                    $elementor_data = get_post_meta($footer , '_elementor_data', true);
                    $elementor_data = json_decode($elementor_data,true);
                    if (!empty($elementor_data)) {
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('top_typography_font_family',$elementor_data));
                        $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('sub_typography_font_family',$elementor_data));
                    }
                }

            /* Megamenu
            --------------*/

                $megamenu = enovathemes_addons_megamenus();
                if (!is_wp_error($megamenu)) {
                    foreach ($megamenu as $megam => $atts) {
                        $elementor_data = get_post_meta($megam, '_elementor_data', true);
                        $elementor_data = json_decode($elementor_data,true);
                        if (!empty($elementor_data)) {
                            $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('typography_font_family',$elementor_data));
                            $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('top_typography_font_family',$elementor_data));
                            $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('sub_typography_font_family',$elementor_data));
                        }
                    }
                }

            /*  Banners
            ---------------*/

                $banners = enovathemes_addons_banners();

                if (!is_wp_error($banners)) {
                    foreach ($banners as $banner => $atts) {
                        $elementor_data = get_post_meta($banner, '_elementor_data', true);
                        $elementor_data = json_decode($elementor_data,true);
                        if (!empty($elementor_data)) {
                            $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('typography_font_family',$elementor_data));
                            $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('top_typography_font_family',$elementor_data));
                            $global_dynamic_font = array_merge($global_dynamic_font,array_value_recursive('sub_typography_font_family',$elementor_data));
                        }
                    }
                }

            /*  Dynamic font enqueue
            ---------------*/

                if (!empty($global_dynamic_font)) {

                    $global_dynamic_font = array_unique($global_dynamic_font,SORT_REGULAR);
                    $global_dynamic_font_formatted = array();

                    $google_fonts = enovathemes_addons_google_fonts();
                    if (!is_wp_error($google_fonts)) {
                        foreach($google_fonts as $font) {
                            if (in_array($font['family'], $global_dynamic_font)) {
                                $global_dynamic_font_formatted[] = array($font['family'],implode(',', $font['variants']));
                            }
                        }
                    }

                    if (!empty($global_dynamic_font_formatted)) {

                        $global_dynamic_font_string   = '';

                        foreach ($global_dynamic_font_formatted as $font) {

                            $variants = str_replace('italic','i',$font[1]);
                            $variants = str_replace('regular','400',$variants);

                            $global_dynamic_font_string .= str_replace(' ', '+', $font[0]).':'.$variants.'|';
                        }

                        wp_enqueue_style( 'dynamic-google-fonts', '//fonts.googleapis.com/css?family='.rtrim($global_dynamic_font_string,'|'),array(), false );

                    }

                }

        /* Dynamic google fonts >
        ------------------------------------*/


    }
    add_action( 'wp_enqueue_scripts', 'enovathemes_addons_script' );

    function et__enqueue_customizer_fixes() {
        $handle = 'customizer-row-label-fix';
        $path   = __DIR__ . '/js/customizer-row-label-fix.js';
        $src    = plugins_url('/js/customizer-row-label-fix.js', __FILE__ );
        $ver    = file_exists($path) ? filemtime($path) : null;

        // Load in the Customizer controls pane, after its scripts
        wp_enqueue_script(
            $handle,
            $src,
            array('jquery', 'customize-controls'),
            $ver,
            true
        );
    }
    add_action('customize_controls_enqueue_scripts', 'et__enqueue_customizer_fixes');


/*  Header html
/*-------------------*/

    function enovathemes_addons_header_html($header_id, $header_type){

        $pluginElementor = \Elementor\Plugin::instance();

        $headers = enovathemes_addons_headers();

        if (!is_wp_error($headers) && array_key_exists($header_id,$headers)) {

            $class   = array();
            if ($header_type == "mobile") {
                $class[] = 'et-mobile';
                $class[] = 'mobile-true';
                $class[] = 'desktop-false';
            } elseif($header_type == "desktop"){
                $class[] = 'et-desktop';
                $class[] = 'mobile-false';
                $class[] = 'desktop-true';
            }

            $class   = array_merge($class,$headers[$header_id]['class']);
            $content = (is_plugin_active( 'elementor/elementor.php' )) ? $pluginElementor->frontend->get_builder_content($header_id,false) : get_the_content();

            echo '<header id="et-'.$header_type.'-'.esc_attr($header_id).'" class="'.esc_attr(implode(" ", $class)).'">'.do_shortcode($content).'</header>';
        } else {
            echo '<div class="container"><div class="alert error"><div class="alert-message">'.esc_html__("No custom header is found, make sure you create a one", "enovathemes-addons").'</div></div></div>';
        }
    }

    function enovathemes_addons_headers() {

        if ( false === ( $headers = get_transient( 'enovathemes-headers' ) ) ) {

            $query_options = array(
                'post_type'           => 'header',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page'      => -1,
                'suppress_filters'    => 'true',
                'lang'                => ''
            );

            $headers = array();
            $headers_query = new WP_Query($query_options);

            if ($headers_query->have_posts()){

                $upload_dir = wp_upload_dir();

                while($headers_query->have_posts()) { $headers_query->the_post();

                    $header_id = get_the_ID();

                    $transparent   = get_post_meta($header_id, 'enovathemes_addons_transparent', true);
                    $sticky        = get_post_meta($header_id, 'enovathemes_addons_sticky', true);
                    $shadow        = get_post_meta($header_id, 'enovathemes_addons_shadow', true);
                    $shadow_sticky = get_post_meta($header_id, 'enovathemes_addons_shadow_sticky', true);
                    $type          = get_post_meta($header_id, 'enovathemes_addons_header_type', true);

                    $transparent      = (empty($transparent)) ? "false" : "true";
                    $sticky           = (empty($sticky)) ? "false" : "true";
                    $shadow           = (empty($shadow)) ? "false" : "true";
                    $shadow_sticky    = (empty($shadow_sticky)) ? "false" : "true";

                    $class   = array();
                    $class[] = 'header';
                    $class[] = 'et-clearfix';
                    $class[] = 'transparent-'.$transparent;
                    $class[] = 'sticky-'.$sticky;
                    $class[] = 'shadow-'.$shadow;
                    $class[] = 'shadow-sticky-'.$shadow_sticky;
                    if ($type == "sidebar") {$class[] = 'side-true';}

                    $styles = '';

                    if ( did_action('elementor/loaded') && class_exists('\Elementor\Core\Files\CSS\Post') ) {
                        // Build & fetch the CSS string without touching the filesystem
                        $post_css = \Elementor\Core\Files\CSS\Post::create( (int) $header_id );
                        $styles   = $post_css->get_content(); // returns the full compiled CSS string
                    }

                    // Fallback (older Elementor / edge cases)
                    if ( $styles === '' ) {
                        $file = $upload_dir['basedir'] . '/elementor/css/post-' . (int) $header_id . '.css';
                        if ( is_file($file) ) {
                            $styles = file_get_contents($file);
                        }
                    }

                    $headers[$header_id] = array(
                        'class'   => $class,
                        'styles'  => $styles,
                    );

                }
                wp_reset_postdata();
            }

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $headers ) ) {
                $headers = base64_encode(serialize($headers ));
                set_transient( 'enovathemes-headers', $headers, apply_filters( 'null_headers_cache_time', 0 ) );
            }
        }

        if ( ! empty( $headers ) ) {

            return unserialize(base64_decode($headers));

        } else {

            return new WP_Error( 'no_headers', esc_html__( 'No headers.', 'enovathemes-addons' ) );

        }
    }

/*  Footer html
/*-------------------*/

    function enovathemes_addons_footer_html($footer_id){

        $pluginElementor = \Elementor\Plugin::instance();

        $footer_async   = get_post_meta($footer_id, 'enovathemes_addons_footer_async', true);
        $dis_async_blog = get_post_meta($footer_id, 'enovathemes_addons_dis_async_blog', true);
        $dis_async_shop = get_post_meta($footer_id, 'enovathemes_addons_dis_async_shop', true);
        $dis_async_page = get_post_meta($footer_id, 'enovathemes_addons_dis_async_page', true);

        $disable_async = 'false';

        $pages   = array();
        $is_page = false;

        if (!empty($dis_async_page)) {
           $dis_async_page = explode(',', $dis_async_page);
           if (is_array($dis_async_page)) {
                $pages = $dis_async_page;
                foreach($pages as $page){
                    if (is_page($page)) {
                        $is_page = true;
                    }
                }
           }
        }

        if (
            ($dis_async_blog == "on" && (is_home() || is_tax() || is_category() || is_tag() || is_single('post') || is_search() || is_404())) ||
            ($dis_async_shop == "on" && (is_post_type_archive( 'product' ) || is_tax( 'product_cat' ) || is_tax( 'product_tag' ) || is_singular( 'product' ))) ||
            (!empty($pages) && $is_page)
        ) {
            $disable_async = 'true';
        }

        $content   = (is_plugin_active( 'elementor/elementor.php' )) ? $pluginElementor->frontend->get_builder_content($footer_id,false) : get_the_content();

        $iframe = 'false';

        if( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' ) {$iframe = 'true';}

        if ($iframe == 'true' || $footer_async == "false" || $disable_async == "true") {
            $footers = enovathemes_addons_footers();
            if (!is_wp_error($footers)) {

                $sticky = get_post_meta($footer_id, 'enovathemes_addons_sticky', true);
                $sticky = (empty($sticky)) ? "false" : "true";

                $class   = array();
                $class[] = 'footer';
                $class[] = 'et-footer';
                $class[] = 'et-clearfix';
                $class[] = 'sticky-'.$sticky;

                echo '<footer id="et-footer-'.esc_attr($footer_id).'" class="'.implode(" ", $class).'">'.do_shortcode($content).'</footer>';
            } else {
                echo '<div class="alert error"><div class="alert-message">'.esc_html__("No custom footer is found, make sure you create a one", "enovathemes-addons").'</div></div>';
            }
        } else {

            $footer_placeholder        = get_post_meta($footer_id, 'enovathemes_addons_footer_placeholder', true);
            $footer_placeholder_color  = get_post_meta($footer_id, 'enovathemes_addons_footer_placeholder_color', true);
            $style = '';
            if (!empty($footer_placeholder)) {
                $style .= 'height:'.$footer_placeholder.'px;';
            }
            if (!empty($footer_placeholder_color)) {
                $style .= 'background:'.$footer_placeholder_color;
            }
            echo '<footer id="footer-placeholder-'.$footer_id.'" data-footer="'.$footer_id.'" style="'.$style.'" class="footer et-footer et-clearfix sticky-false footer-placeholder"></footer>';
            
        }

    }

    function enovathemes_addons_footers() {

        if ( false === ( $footers = get_transient( 'enovathemes-footers' ) ) ) {

            $query_options = array(
                'post_type'           => 'footer',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page'      => -1,
                'suppress_filters'    => 'true',
                'lang'                => ''
            );

            $footers = array();
            $footers_query = new WP_Query($query_options);
            if ($footers_query->have_posts()){

                $upload_dir = wp_upload_dir();

                while($footers_query->have_posts()) { $footers_query->the_post();

                    $footer_id = get_the_ID();

                    $styles = '';

                    if ( did_action('elementor/loaded') && class_exists('\Elementor\Core\Files\CSS\Post') ) {
                        // Build & fetch the CSS string without touching the filesystem
                        $post_css = \Elementor\Core\Files\CSS\Post::create( (int) $footer_id );
                        $styles   = $post_css->get_content(); // returns the full compiled CSS string
                    }

                    // Fallback (older Elementor / edge cases)
                    if ( $styles === '' ) {
                        $file = $upload_dir['basedir'] . '/elementor/css/post-' . (int) $footer_id . '.css';
                        if ( is_file($file) ) {
                            $styles = file_get_contents($file);
                        }
                    }

                    $footers[$footer_id] = array(
                        'styles' => $styles
                    );

                }
                wp_reset_postdata();
            }

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $footers ) ) {
                $footers = base64_encode(serialize( $footers ));
                set_transient( 'enovathemes-footers', $footers, apply_filters( 'null_footers_cache_time', 0 ) );
            }
        }

        if ( ! empty( $footers ) ) {

            return unserialize(base64_decode($footers));

        } else {

            return new WP_Error( 'no_footers', esc_html__( 'No footers.', 'enovathemes-addons' ) );

        }
    }

/*  Banners
---------------------*/

    function enovathemes_addons_banners() {

        if ( false === ( $banners = get_transient( 'enovathemes-banners' ) ) ) {

            $query_options = array(
                'post_type'           => 'banner',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page'      => -1,
                'suppress_filters'    => 'true',
                'lang'                => ''
            );

            $banners = array();
            $banner = new WP_Query($query_options);
            if ($banner->have_posts()){

                $upload_dir = wp_upload_dir();


                while($banner->have_posts()) { $banner->the_post();
                    $banner_id = get_the_ID();
                    
                    $styles = '';

                    if ( did_action('elementor/loaded') && class_exists('\Elementor\Core\Files\CSS\Post') ) {
                        // Build & fetch the CSS string without touching the filesystem
                        $post_css = \Elementor\Core\Files\CSS\Post::create( (int) $banner_id );
                        $styles   = $post_css->get_content(); // returns the full compiled CSS string
                    }

                    // Fallback (older Elementor / edge cases)
                    if ( $styles === '' ) {
                        $file = $upload_dir['basedir'] . '/elementor/css/post-' . (int) $banner_id . '.css';
                        if ( is_file($file) ) {
                            $styles = file_get_contents($file);
                        }
                    }

                    $banners[$banner_id] = array(
                        'id'     => $banner_id,
                        'title'  => get_the_title($banner_id),
                        'styles' => $styles,
                    );
                }
                wp_reset_postdata();
            }

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $banners ) ) {
                $banners = base64_encode(serialize($banners ));
                set_transient( 'enovathemes-banners', $banners, apply_filters( 'null_banners_cache_time', 0 ) );
            }
        }

        if ( ! empty( $banners ) ) {

            return unserialize(base64_decode($banners));

        } else {

            return new WP_Error( 'no_banners', esc_html__( 'No banners.', 'enovathemes-addons' ) );

        }
    }

/*  Megamenu
---------------------*/

    function enovathemes_addons_megamenus() {

        if ( false === ( $megamenu = get_transient( 'enovathemes-megamenu' ) ) ) {

            $query_options = array(
                'post_type'           => 'megamenu',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page'      => -1,
                'suppress_filters'    => 'true',
                'lang'                => ''
            );

            $megamenu = array();
            $megam = new WP_Query($query_options);
            if ($megam->have_posts()){

                $upload_dir = wp_upload_dir();

                while($megam->have_posts()) { $megam->the_post();
                    $megam_id = get_the_ID();

                    $megamenu_html  = '';
                    $megamenu_data  = array();
                    $megamenu_class = array('sub-menu','megamenu');

                    $megamenu_width = get_post_meta($megam_id, 'enovathemes_addons_megamenu_width', true);
                    $megamenu_position = get_post_meta($megam_id, 'enovathemes_addons_megamenu_position', true);
                    $megamenu_offset = get_post_meta($megam_id, 'enovathemes_addons_megamenu_offset', true);
                    $megamenu_tabbed = get_post_meta($megam_id, 'enovathemes_addons_tabbed', true);
                    $megamenu_sidebar = get_post_meta($megam_id, 'enovathemes_addons_sidebar', true);

                    if (!empty($megamenu_width)) {
                        $megamenu_data[] = 'data-width="'.$megamenu_width.'"';
                    }

                    if ($megamenu_tabbed == "on") {
                        $megamenu_data[]  = 'data-tabbed="true"';
                        $megamenu_class[] = 'megamenu-tab';
                    }

                    if ($megamenu_sidebar == "on") {
                        $megamenu_class[] = 'megamenu-sidebar';
                    }

                    if (!empty($megamenu_position)) {
                        $megamenu_data[] = 'data-position="'.$megamenu_position.'"';
                    }

                    if (!empty($megamenu_offset)) {
                        $megamenu_data[] = 'data-offset="'.$megamenu_offset.'"';
                    }

                    $megamenu_data[] = 'class="'.implode(' ', $megamenu_class).'"';

                    $styles = '';

                    if ( did_action('elementor/loaded') && class_exists('\Elementor\Core\Files\CSS\Post') ) {
                        // Build & fetch the CSS string without touching the filesystem
                        $post_css = \Elementor\Core\Files\CSS\Post::create( (int) $megam_id );
                        $styles   = $post_css->get_content(); // returns the full compiled CSS string
                    }

                    // Fallback (older Elementor / edge cases)
                    if ( $styles === '' ) {
                        $file = $upload_dir['basedir'] . '/elementor/css/post-' . (int) $megam_id . '.css';
                        if ( is_file($file) ) {
                            $styles = file_get_contents($file);
                        }
                    }

                    $megamenu[$megam_id] = array(
                        'id' => $megam_id,
                        'title' => get_the_title($megam_id),
                        'data' =>$megamenu_data,
                        'styles' =>$styles
                    );
                }
                wp_reset_postdata();
            }

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $megamenu ) ) {
                $megamenu = base64_encode(serialize($megamenu ));
                set_transient( 'enovathemes-megamenu', $megamenu, apply_filters( 'null_megamenu_cache_time', 0 ) );
            }
        }

        if ( ! empty( $megamenu ) ) {

            return unserialize(base64_decode($megamenu));

        } else {

            return new WP_Error( 'no_megamenu', esc_html__( 'No megamenu.', 'enovathemes-addons' ) );

        }
    }

    function enovathemes_addons_megamenus_names() {

        if ( false === ( $megamenu = get_transient( 'enovathemes-megamenu-names' ) ) ) {

            $query_options = array(
                'post_type'           => 'megamenu',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page'      => -1,
                'suppress_filters'    => 'true',
                'lang'                => ''
            );

            $megamenu = array();
            $megam = new WP_Query($query_options);
            if ($megam->have_posts()){

                $upload_dir = wp_upload_dir();

                $pluginElementor = \Elementor\Plugin::instance();

                while($megam->have_posts()) { $megam->the_post();
                    $megam_id = get_the_ID();
                    $megamenu[$megam_id] = get_the_title($megam_id);
                }
                wp_reset_postdata();
            }

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $megamenu ) ) {
                $megamenu = base64_encode(serialize($megamenu ));
                set_transient( 'enovathemes-megamenu-names', $megamenu, apply_filters( 'null_megamenu_cache_time', 0 ) );
            }
        }

        if ( ! empty( $megamenu ) ) {

            return unserialize(base64_decode($megamenu));

        } else {

            return new WP_Error( 'no_megamenu', esc_html__( 'No megamenu.', 'enovathemes-addons' ) );

        }
    }

/*  Headers/Footers list
---------------------*/

    function enovathemes_addons_headers_list() {

        if ( false === ( $header_list = get_transient( 'enovathemes-header-list' ) ) ) {


            global $wpdb;

            $headers_array = array();

            $et_header = $wpdb->get_results("
                SELECT ID, post_title
                FROM {$wpdb->posts}
                WHERE post_type = 'header'
                AND post_status = 'publish'
                ORDER BY post_date DESC
            ");

            if($et_header){
                foreach ($et_header as $header) {
                    $headers_array[] = [
                        'ID' => $header->ID,
                        'title' => $header->post_title
                    ];
                }

            }

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $headers_array ) ) {
                $header_list = base64_encode(serialize($headers_array ));
                set_transient( 'enovathemes-header-list', $header_list, apply_filters( 'null_header_list_cache_time', 0 ) );
            }
        }

        if ( ! empty( $header_list ) ) {

            return unserialize(base64_decode($header_list));

        } else {

            return new WP_Error( 'no_header_list', esc_html__( 'No headers.', 'enovathemes-addons' ) );

        }

    }

    function enovathemes_addons_footers_list() {

        if ( false === ( $footer_list = get_transient( 'enovathemes-footer-list' ) ) ) {


            global $wpdb;

            $footers_array = array();

            $et_footer = $wpdb->get_results("
                SELECT ID, post_title
                FROM {$wpdb->posts}
                WHERE post_type = 'footer'
                AND post_status = 'publish'
                ORDER BY post_date DESC
            ");

            if($et_footer){
                foreach ($et_footer as $footer) {
                    $footers_array[] = [
                        'ID' => $footer->ID,
                        'title' => $footer->post_title
                    ];
                }

            }

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $footers_array ) ) {
                $footer_list = base64_encode(serialize($footers_array ));
                set_transient( 'enovathemes-footer-list', $footer_list, apply_filters( 'null_footer_list_cache_time', 0 ) );
            }
        }

        if ( ! empty( $footer_list ) ) {

            return unserialize(base64_decode($footer_list));

        } else {

            return new WP_Error( 'no_footer_list', esc_html__( 'No footers.', 'enovathemes-addons' ) );

        }
        
    }

/* Vehicle logos
/*-------------------*/

    function enovathemes_addons_vehicle_logos($dir) {

        if ( false === ( $vehicle_logos = get_transient( 'enovathemes-vehicle-logos' ) ) ) {

            $vehicle_logos = array_diff(scandir($dir), array('..', '.'));

            $vehicle_logos_array = array();

            foreach ($vehicle_logos as $logo) {
                array_push($vehicle_logos_array,basename($logo,'.svg'));
            }

            $vehicle_logos = $vehicle_logos_array;

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $vehicle_logos ) ) {
                $vehicle_logos = base64_encode(serialize( $vehicle_logos ));
                set_transient( 'enovathemes-vehicle-logos', $vehicle_logos, apply_filters( 'null_vehicle_logos_cache_time', 0 ) );
            }
        }

        if ( ! empty( $vehicle_logos ) ) {

            return unserialize(base64_decode($vehicle_logos));

        } else {

            return new WP_Error( 'no_logos', esc_html__( 'No logos.', 'enovathemes-addons' ) );

        }
    }

/*  Vehicle filter
---------------------*/

    function et_year_formatting($year){
        $years = [];

        if (strpos($year, "-") !== false) {
            $year = explode("-", $year);

            $min = intval($year[0]);
            $max = $year[1];

            if ($max == '*') {
                $max = date('Y');
            }

            $max = intval($year[1]);

            for (
                $i = $min;
                $i <= $max;
                $i++
            ) {
                array_push($years, $i);
            }
        } elseif (strpos($year, ",") !== false) {
            $year = explode(",", $year);

            foreach ($year as $value) {
                $years[] = intval($value);
            }

        } else {
            array_push($years, $year);
        }

        $years = array_unique($years);
        $years = array_filter($years);
        sort($years);

        return (!empty($years)) ? $years : false;
    }

    function et_array_has_one(array $search, array $haystack){
        if(!count(array_intersect($search, $haystack)) === FALSE){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    add_action('init',function(){

        if (class_exists('Woocommerce')) {

            update_option( 'wc_feature_woocommerce_brands_enabled', 'no' );

            register_taxonomy('vehicles', 'product', array(
                'hierarchical' => true,
                'has_archive'  => true,
                'labels' => array(
                    'name'              => esc_html__( 'Vehicles', 'enovathemes-addons' ),
                    'singular_name'     => esc_html__( 'Vehicle', 'enovathemes-addons' ),
                    'search_items'      => esc_html__( 'Search vVehicles', 'enovathemes-addons' ),
                    'all_items'         => esc_html__( 'All Vehicles', 'enovathemes-addons' ),
                    'parent_item'       => esc_html__( 'Parent vehicle', 'enovathemes-addons' ),
                    'parent_item_colon' => esc_html__( 'Parent vehicle', 'enovathemes-addons' ),
                    'edit_item'         => esc_html__( 'Edit vehicle', 'enovathemes-addons' ),
                    'update_item'       => esc_html__( 'Update vehicle', 'enovathemes-addons' ),
                    'add_new_item'      => esc_html__( 'Add new vehicle', 'enovathemes-addons' ),
                    'new_item_name'     => esc_html__( 'New vehicle', 'enovathemes-addons' ),
                    'menu_name'         => esc_html__( 'Vehicles', 'enovathemes-addons' ),
                ),
                'rewrite' => array(
                    'slug'         => 'vehicles',
                    'with_front'   => true,
                    'hierarchical' => true
                ),
                'show_in_quick_edit'    => false,
                'show_in_nav_menus'     => true,
                'show_modelcloud'       => true,
                'show_admin_column'     => false,
                'show_in_rest'          => true,
                'rest_controller_class' => 'WP_REST_Terms_Controller',
                'rest_base'             => 'vehicles',
            ));

            add_action( 'admin_menu', 'enovathemes_addons_remove_vehicle_meta_box');
            function enovathemes_addons_remove_vehicle_meta_box(){
               remove_meta_box('vehiclesdiv', 'product', 'side');
            }

            add_action('admin_menu', function(){
                add_submenu_page(
                    'edit.php?post_type=product', 
                    esc_html__( 'Import vehicles', 'enovathemes-addons' ), 
                    esc_html__( 'Import vehicles', 'enovathemes-addons' ),
                    'manage_options', 
                    'vehicles_import',
                    'enovathemes_addons_vehicle_import',
                    5
                );
            },10);

            /*  Custom fields
            ---------------------*/

                add_action( 'cmb2_admin_init', 'enovathemes_addons_vehicle_data_cmb2_admin_init');
                function enovathemes_addons_vehicle_data_cmb2_admin_init(){

                    $vehicle_params = apply_filters( 'vehicle_params','');

                    $prefix = 'vehicle_';

                    $cmb_term_vehicle = new_cmb2_box( array(
                        'id'               => $prefix . 'data',
                        'title'            => esc_html__( 'Vehicle', 'enovathemes-addons' ),
                        'object_types'     => array( 'term' ),
                        'taxonomies'       => array( 'vehicles'),
                    ));

                    if ($vehicle_params != false) {

                        foreach ($vehicle_params as $param) {
                            $cmb_term_vehicle->add_field( array(
                                'name'    => esc_html( ucwords( str_replace( ['_', '-'], ' ', (string) $param ) ) ),
                                'description'    => ($param == 'year') ? esc_html__( 'Enter individual year, or range of years or comma separated years. If your year range does not end, put * as a range end (example 2010-*).', 'enovathemes-addons' ) : '',
                                'id'      => $prefix . $param,
                                'type'    => 'text_medium',
                                'default' => '',
                            ));
                        }

                    }

                    $cmb_term_vehicle->add_field( array(
                        'name'    => esc_html__( 'Vehicle data', 'enovathemes-addons' ),
                        'id'      => $prefix . 'data',
                        'type'    => 'hidden',
                        'default' => '',
                    ));

                }

            /*  Custom product columns
            ---------------------*/

                add_filter("manage_edit-product_columns", function($columns){


                    $product_attributes = get_theme_mod('product_attributes');

                    if (!empty($product_attributes)) {
                        foreach ($product_attributes as $attribute) {

                            $taxonomy_object = get_taxonomy('pa_'.$attribute);


                            if ($taxonomy_object) {
                                $columns['attr-'.$attribute] = ucfirst($taxonomy_object->labels->singular_name);
                            }
                        }
                    }

                    $columns['universal'] = esc_html__("Universal product", 'enovathemes-addons');

                    return $columns;
                });

                add_action("manage_product_posts_custom_column", function($column){

                    global $post;

                    if ($column == 'universal') {
                        $universal = get_post_meta($post->ID,'enovathemes_addons_universal',true);

                        if ($universal == "on") {
                            echo '<span class="custom-meta-ind indicator-5">'.esc_html__("Universal","enovathemes-addons").'</span>';
                        }
                    }

                    $product_attributes = get_theme_mod('product_attributes');

                    if (!empty($product_attributes)) {


                        foreach ($product_attributes as $attribute) {

                            $taxonomy_object = get_taxonomy('pa_'.$attribute);

                            if ($taxonomy_object) {
                                if ($column == 'attr-'.$attribute) {
                                    $terms = wp_get_post_terms($post->ID, 'pa_'.$attribute);

                                    if (!is_wp_error($terms) && !empty($terms)) {
                                        foreach ($terms as $term) {
                                            $filter_url = admin_url('edit.php?post_type=product&' . 'pa_'.$attribute . '=' . $term->slug);
                                            echo '<a href="' . esc_url($filter_url) . '">' . esc_html($term->name) . '</a><br>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                });

                function et_attribute_taxonomy_query($query) {
                    global $pagenow, $typenow;

                    if ($pagenow == 'edit.php' && $typenow == 'product') {
                        foreach (wc_get_attribute_taxonomies() as $tax) {
                            $attribute_slug = wc_attribute_taxonomy_name($tax->attribute_name);

                            if (isset($_GET[$attribute_slug]) && !empty($_GET[$attribute_slug])) {
                                $query->query_vars['tax_query'][] = array(
                                    'taxonomy' => $attribute_slug,
                                    'field' => 'slug',
                                    'terms' => $_GET[$attribute_slug],
                                );
                            }
                        }
                    }
                }
                add_filter('parse_query', 'et_attribute_taxonomy_query');

            /*  Universal product builk edit
            ---------------------*/

                add_action( 'woocommerce_product_bulk_edit_end', 'enovathemes_addons_product_bulk_quick_edit',99);
                add_action( 'woocommerce_product_quick_edit_end', 'enovathemes_addons_product_bulk_quick_edit',99);
                function enovathemes_addons_product_bulk_quick_edit(){

                    $universal = get_post_meta(get_the_ID(),'enovathemes_addons_universal',true);

                    ?>

                    <div class="inline-edit-group">
                      <label class="alignleft">
                         <span class="title"><?php _e( 'Universal?', 'enovathemes-addons' ); ?></span>
                         <span class="input-text-wrap">
                            <input type="checkbox" name="enovathemes_addons_universal" <?php checked( $universal, "on" ); ?> value="on">
                         </span>
                        </label>
                    </div>

                <?php }
          
                add_action( 'woocommerce_product_bulk_and_quick_edit', 'enovathemes_addons_product_bulk_quick_edit_save',10,2);
                function enovathemes_addons_product_bulk_quick_edit_save( $post_id, $post ) {

                    

                    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                        return $post_id;
                    }
                    if ( 'product' !== $post->post_type ) return $post_id;

                    if (isset($_REQUEST['enovathemes_addons_universal'])) {
                        $universal_checked = ( isset( $_REQUEST['enovathemes_addons_universal'] ) ) ? "on" : "off";
                        update_post_meta($post_id, "enovathemes_addons_universal", $universal_checked);
                    } else {
                        delete_post_meta( $post_id, 'enovathemes_addons_universal' );
                    }

                }

                add_action('admin_footer',function(){ ?>

                    <script>
                        jQuery( function( $ ){

                            if (typeof(inlineEditPost) != "undefined") {

                                const wp_inline_edit_function = inlineEditPost.edit;

                                // we overwrite the it with our own
                                inlineEditPost.edit = function( post_id ) {

                                    // let's merge arguments of the original function
                                    wp_inline_edit_function.apply( this, arguments );

                                    // get the post ID from the argument
                                    if ( typeof( post_id ) == 'object' ) { // if it is object, get the ID number
                                        post_id = parseInt( this.getId( post_id ) );
                                    }

                                    // add rows to variables
                                    const edit_row = $( '#edit-' + post_id )
                                    const post_row = $( '#post-' + post_id )

                                    const universalProduct = $( '.column-universal', post_row ).text() ? true : false;

                                    $( ':input[name="enovathemes_addons_universal"]', edit_row ).prop( 'checked', universalProduct );
                                    
                                }

                            }
                        });
                    </script>

                <?php });

            /*  Custom taxonomy columns
            ---------------------*/

               add_action( 'manage_vehicles_custom_column', 'enovathemes_addons_show_vehicles_meta_info_in_columns', 10, 3 );
               function enovathemes_addons_show_vehicles_meta_info_in_columns( $string, $columns, $term_id ) {

                    $vehicle_params = apply_filters( 'vehicle_params','');

                    if ($vehicle_params != false) {

                        foreach ($vehicle_params as $param) {
                            if ($columns == 'vehicle_'.$param) {
                                echo esc_html( get_term_meta( $term_id, 'vehicle_'.$param, true ) );
                            }
                        }
                    
                    }
                }

                add_filter( 'manage_edit-vehicles_columns', 'enovathemes_addons_add_new_vehicles_columns' );
                function enovathemes_addons_add_new_vehicles_columns( $columns ) {

                    unset($columns['slug']);
                    unset($columns['description']);
                    unset($columns['posts']);

                    $vehicle_params = apply_filters( 'vehicle_params','');

                    $columns['name'] = esc_html__( 'Actions','enovathemes-addons' );

                    if ($vehicle_params != false) {

                        foreach ( $vehicle_params as $param ) {
                            $columns[ 'vehicle_' . $param ] = $vehicle_param_labels[ $param ]
                                ?? esc_html( ucwords( str_replace( ['_', '-'], ' ', (string) $param ) ) ); // fallback (not translated)
                        }

                    }
                        
                    $columns['posts'] = esc_html__( 'Count','enovathemes-addons' );
                    return $columns;
                }

                function enovathemes_addons_vehicles_data_sortable($columns) {

                    $vehicle_params = apply_filters( 'vehicle_params','');

                    unset($columns['name']);

                    if ($vehicle_params != false) {

                        foreach ($vehicle_params as $param) {
                            $columns['vehicle_'.$param] = $param;
                        }

                    }

                    return $columns;
                }
                add_filter('manage_edit-vehicles_sortable_columns', 'enovathemes_addons_vehicles_data_sortable');

                add_filter( 'terms_clauses', function( $pieces, $taxonomies, $args ) {

                    global $pagenow, $wpdb;

                    $vehicle_params = apply_filters( 'vehicle_params','');

                    $orderby = ( isset( $_GET[ 'orderby' ] ) ) ? trim( sanitize_text_field( $_GET[ 'orderby' ] ) ) : '';
                    if ( empty( $orderby ) ) { return $pieces; }

                    $taxonomy = isset($taxonomies[ 0 ]) ? $taxonomies[ 0 ] : false;

                    if ( ! is_admin() || 'edit-tags.php' !== $pagenow || ! in_array( $taxonomy, [ 'vehicles' ] ) ) {
                        return $pieces;
                    }

                    if ($vehicle_params != false) {

                        foreach ($vehicle_params as $param) {
                            if ($param == $orderby) {
                                $pieces[ 'join' ] .= ' INNER JOIN ' . $wpdb->termmeta . ' AS tm ON t.term_id = tm.term_id ';
                                $pieces[ 'orderby' ]  = ' ORDER BY tm.meta_value ';
                                $pieces[ 'where' ] .= ' AND tm.meta_key = "vehicle_'.$param.'"';
                            }
                        }

                    }

                    return $pieces;

                }, 10, 3 );

            /*  Transients
            ---------------------*/

                function array_iunique($array) {

                    usort($array, 'strcmp');

                    $finalArray = array(); /* Declare it as blank first, just in case) */
                    $referenceArray = array();

                    foreach($array as $item) {

                    if(!in_array(strtolower($item), $referenceArray)) {
                        $finalArray[] = $item;
                        $referenceArray[] = strtolower($item);
                        }
                    }

                    return $finalArray;

                }

                function enovathemes_addons_vehicle_first_param($first_parameter = false,$hide_empty = true) {

                    $vehicles_first_param = $first_parameter ? get_transient( 'vehicles-first-param-'.$first_parameter ) : get_transient( 'vehicles-first-param' );

                    if ( false === $vehicles_first_param ) {

                        $vehicles_terms = get_terms( array(
                            'taxonomy'   => 'vehicles',
                            'hide_empty' => $hide_empty,
                        ));

                        $vehicle_params = apply_filters( 'vehicle_params','');

                        $vehicles_first_param = array();

                        if (!is_wp_error($vehicles_terms) && $vehicle_params != false) {

                            $first_parameter = ($first_parameter && in_array($first_parameter,$vehicle_params)) ? $first_parameter : $vehicle_params[0];

                            foreach ($vehicles_terms as $vehicle) {

                                $first_param = get_term_meta($vehicle->term_id, 'vehicle_'.$first_parameter, true );

                                if (($vehicle_params[0] == "year" || $first_parameter == "year") && !empty($first_param)) {
                                    $years = et_year_formatting($first_param);
                                    if ($years) {
                                        foreach ($years as $year) {
                                            $vehicles_first_param[] = $year;
                                        }
                                    }
                                } else {
                                    $vehicles_first_param[] = esc_html($first_param);
                                }

                            }
                        }

                        $vehicles_first_param = ($vehicle_params[0] == "year") ? array_unique($vehicles_first_param) : array_iunique($vehicles_first_param);
                        $vehicles_first_param = array_filter($vehicles_first_param);

                        sort($vehicles_first_param);

                        if ( ! empty( $vehicles_first_param ) ) {
                            if ($first_parameter) {
                                set_transient( 'vehicles-first-param-'.$first_parameter, $vehicles_first_param, apply_filters( 'null_vehicles_first_param_cache_time', 0 ) );
                            } else {
                                set_transient( 'vehicles-first-param', $vehicles_first_param, apply_filters( 'null_vehicles_first_param_cache_time', 0 ) );
                            }
                        }
                    }

                    if ( ! empty( $vehicles_first_param ) ) {
                        return $vehicles_first_param;
                    } else {
                        return new WP_Error( 'no_vehicle_first_param', esc_html__( 'No vehicles.', 'enovathemes-addons' ) );
                    }
                }

                function enovathemes_addons_universal_products() {

                    if ( false === ( $uni_products = get_transient( 'universal-products' ) ) ) {

                        $universal_products_array = array();

                        $query_options = array(
                            'post_type'     => 'product',
                            'meta_key'      => 'enovathemes_addons_universal',
                            'orderby'       => 'meta_value_num',
                            'order'         => 'ASC',
                            'posts_per_page'=> -1,
                            'meta_query'    => array(
                                array(
                                    'key'     => 'enovathemes_addons_universal',
                                    'value'   => array('on'),
                                    'compare' => 'IN',
                                ),
                            ),
                            'tax_query'      => array(
                                array(
                                    'taxonomy'  => 'product_visibility',
                                    'terms'     => array( 'exclude-from-catalog' ),
                                    'field'     => 'name',
                                    'operator'  => 'NOT IN'
                                )
                            )
                        );
                        $universal_products = new WP_Query($query_options);

                        if($universal_products->have_posts()){
                            while ($universal_products->have_posts() ) {
                                $universal_products->the_post();
                                array_push($universal_products_array, get_the_ID());
                            }
                            wp_reset_postdata();
                        }

                        if ( ! empty( $universal_products_array ) ) {
                            $uni_products = $universal_products_array;
                            set_transient( 'universal-products', $uni_products, apply_filters( 'null_uni_cache_time', 0 ) );
                        }
                    }

                    if ( ! empty( $uni_products ) ) {

                        return $uni_products;

                    } else {

                        return new WP_Error( 'no_uni', esc_html__( 'No universal products.', 'enovathemes-addons' ) );

                    }
                }

            /*  Vehicle import
            ---------------------*/

                function enovathemes_addons_vehicle_import(){ ?>
                    <h1><?php esc_html_e("Import vehicles","enovathemes-addons"); ?></h1>
                    <div class="vehicle-import-progress-form-wrapper woocommerce-progress-form-wrapper">
                        <ol class="progress-steps wc-progress-steps">
                            <li class="active"><?php esc_html_e('Upload CSV file','enovathemes-addons'); ?></li>
                            <li><?php esc_html_e('Column mapping','enovathemes-addons'); ?></li>
                            <li><?php esc_html_e('Import','enovathemes-addons'); ?></li>
                            <li><?php esc_html_e('Done!','enovathemes-addons'); ?></li>
                        </ol>
                        <form class="import-vehicles wc-progress-form-content woocommerce-importer">
                            <header>
                                <h2><?php esc_html_e('Import vehicles from a CSV file','enovathemes-addons'); ?></h2>
                                <p><?php esc_html_e('This tool allows you to import vehicle data to your site from a CSV file.','enovathemes-addons'); ?></p>
                            </header>
                            <section class="change csv-import">
                                <table class="form-table woocommerce-importer-options">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="csv"><?php esc_html_e('Choose a CSV file from your computer:','enovathemes-addons'); ?></label>
                                            </th>
                                            <td>
                                                <input type="file"   id="csv" name="csv" accept="csv/*">
                                                <input type="hidden" id="nonce" name="nonce" value="<?php echo esc_attr(wp_create_nonce('vehicle-csv')); ?>">
                                                <input type="hidden" id="action" name="action" value="csv_upload">
                                                <br>
                                                <small><?php esc_html_e('Maximum size:','enovathemes-addons'); ?> <?php echo ini_get('upload_max_filesize'); ?></small><br>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </section>
                            <div class="import-actions">
                                <button type="submit" class="button button-primary button-next" value="Continue" name="csv_upload_button"><?php esc_html_e('Continue','enovathemes-addons'); ?></button>
                            </div>
                            <div class="blockUI blockOverlay"></div>    
                        </form>
                    </div>
                <?php }

                function enovathemes_addons_upload_csv(){

                    $vehicle_params = apply_filters( 'vehicle_params','');

                    check_ajax_referer('vehicle-csv', 'nonce');

                    $fileNameOriginal = preg_replace('/\s+/', '-', $_FILES["csv"]["name"]);
                    $fileNameOriginal = preg_replace('/[^A-Za-z0-9.\-]/', '', $fileNameOriginal);

                    $upload = wp_upload_bits($fileNameOriginal, null, file_get_contents($_FILES["csv"]["tmp_name"]));

                    if($upload['error'] == false)
                    {
                        
                        $attachment_id = wp_insert_attachment_from_url($upload['url'],$upload['file'],$fileNameOriginal);

                        $csv     = $upload['url'];

                        if ($csv) {
                            $getcsv  = array_map("str_getcsv",file($csv));
                            $headers = array_filter(array_shift($getcsv));
                            $sample  = array_filter($getcsv[0]);

                            $html = '<header>
                                <h2>'.esc_html__('Map CSV fields to vehicles','enovathemes-addons').'</h2>
                                <p>'.esc_html__('Select fields from your CSV file to map against vehicles fields, or to ignore during import.','enovathemes-addons').'</p>
                            </header>
                            <section class="change csv-map">
                                <table class="form-table wc-importer-mapping-table-wrapper">
                                    <thead>
                                        <tr>
                                            <th>'.esc_html__('Column name','enovathemes-addons').'</th>
                                            <th>'.esc_html__('Map to field','enovathemes-addons').'</th>
                                        </tr>
                                    </thead>
                                    <tbody>';


                                        if ($vehicle_params != false) {

                                            $masterLoop = $vehicle_params;

                                            if (sizeof($headers) > sizeof($vehicle_params)) {
                                                $masterLoop = $headers;
                                            }

                                            foreach ($masterLoop as $key => $value) {
                                                $html .= '<tr>';

                                                    if (array_key_exists($key, $headers)) {
                                                        $html .= '<td class="wc-importer-mapping-table-name" data-column="'.$headers[$key].'">'.ucfirst($headers[$key]);
                                                            if (array_key_exists($key, $sample)) {
                                                                $html .= '<span class="description">'.esc_html__('Sample:','enovathemes-addons').' <code>'.$sample[$key].'</code></span>';
                                                            }
                                                        $html .= '</td>';
                                                    }

                                                    $html .= '<td class="wc-importer-mapping-table-field">';
                                                        $html .= '<select name="map_to">';
                                                            $html .= '<option value="">'.esc_html__('Do not import','enovathemes-addons').'</option>';
                                                            foreach ($vehicle_params as $params) {
                                                                $html .= '<option value="'.$params.'">'.ucfirst($params).'</option>';
                                                            }
                                                        $html .= '</select>';
                                                    $html .= '</td>';

                                                $html .= '</tr>';
                                            }

                                        }

                                        
                                    $html .= '</tbody>
                                </table>

                                <input type="hidden" id="nonce" name="nonce" value="'.esc_attr(wp_create_nonce('vehicle-map')).'">
                                <input type="hidden" id="action" name="action" value="csv_map">
                                <input type="hidden" id="map" name="map" value="">
                                <input type="hidden" id="csv-file" name="csv-file" value="'.$csv.'">


                            </section>
                            <div class="import-actions">
                                <button type="submit" class="button button-primary button-next" value="Run the importer" name="csv_map_button">'.esc_html__('Run the importer','enovathemes-addons').'</button>
                            </div>
                            <div class="blockUI blockOverlay"></div>';

                            $data['html'] = $html;

                            echo json_encode($data);
                        }

                        
                    }
                    else{
                        echo json_encode(esc_html__('Upload error','enovathemes-addons'));
                    }

                    die;
                }
                add_action( 'wp_ajax_csv_upload', 'enovathemes_addons_upload_csv' );


                function enovathemes_addons_map_csv(){

                    // Keep nonce & input contract as-is
                    check_ajax_referer('vehicle-map', 'nonce');

                    if ( isset($_POST['map']) && !empty($_POST['map']) && isset($_POST['csv-file']) && !empty($_POST['csv-file']) ) {

                        // Make the request resilient
                        ignore_user_abort(true);
                        if (function_exists('set_time_limit')) { @set_time_limit(0); }
                        @ini_set('max_execution_time', '0');
                        @ini_set('memory_limit', '-1');

                        // Open file streaming (no memory blowups)
                        $csv_path = $_POST['csv-file'];
                        if (($handle = fopen($csv_path, "r")) === false) {
                            echo json_encode(esc_html__('Upload error','enovathemes-addons'));
                            die;
                        }

                        // Prepare log file in uploads
                        $uploads     = wp_upload_dir();
                        $log_dir     = trailingslashit($uploads['basedir']) . 'mobex-import';
                        if (!is_dir($log_dir)) { wp_mkdir_p($log_dir); }
                        $log_file    = $log_dir . '/vehicles-import-' . date('Ymd-His') . '.log';
                        $log_url     = trailingslashit($uploads['baseurl']) . 'mobex-import/' . basename($log_file);

                        $log = function($msg) use ($log_file){
                            file_put_contents($log_file, '['.date('H:i:s').'] '.$msg.PHP_EOL, FILE_APPEND);
                        };

                        // Decode mapping once
                        $map = json_decode( stripslashes($_POST["map"]), true );

                        $skip          = 0;
                        $done          = 0;
                        $skipped_names = [];   // list of skipped vehicle strings
                        $chunk_index   = 0;

                        // Read headers
                        $headers = fgetcsv($handle);
                        if (!$headers) {
                            fclose($handle);
                            echo json_encode(esc_html__('Upload error','enovathemes-addons'));
                            die;
                        }

                        // Batch settings
                        $batch       = [];
                        $batch_size  = 1000;

                        // Process a batch of rows (1,000 each)
                        $process_batch = function(array $rows) use (&$done, &$skip, &$skipped_names, $map) {

                            foreach ($rows as $row) {
                                $vehicle_name = [];
                                $vehicle_meta = [];

                                foreach ($row as $key => $value) {
                                    if (in_array($key, array_keys($map), true)) {
                                        // key is CSV column header; value is cell
                                        $vehicle_name[] = $value;
                                        $vehicle_meta['vehicle_'.$map[$key]] = $value;
                                    }
                                }

                                // Compose vehicle term name the same way as before
                                $vehicle = implode(', ', $vehicle_name);

                                if ( ! term_exists($vehicle, 'vehicles') ) {

                                    $term = wp_insert_term( $vehicle, 'vehicles' );

                                    if ( ! is_wp_error( $term ) ) {
                                        $term_id = isset( $term['term_id'] ) ? $term['term_id'] : false;
                                        if ($term_id) {

                                            foreach ($vehicle_meta as $mkey => $mval) {
                                                update_term_meta( $term_id, $mkey, $mval );

                                                // Original YEAR handling preserved
                                                if ($mkey === 'vehicle_year') {
                                                    $years = [];

                                                    if ( strpos($mval, "-") !== false ) {
                                                        $pair = explode("-", $mval);
                                                        $min  = intval($pair[0]);
                                                        $max  = intval($pair[1]);
                                                        for ($i = $min; $i <= $max; $i++) {
                                                            $years[] = $i;
                                                        }
                                                    } elseif ( strpos($mval, ",") !== false ) {
                                                        foreach ( explode(",", $mval) as $yv ) {
                                                            $years[] = intval($yv);
                                                        }
                                                    } else {
                                                        $years[] = $mval;
                                                    }

                                                    $years = array_unique(array_filter($years));
                                                    sort($years);
                                                    // Keep any of your original follow-up logic on $years here if needed
                                                }
                                            }

                                            $done++;
                                        }
                                    }

                                } else {
                                    $skip++;
                                    // Keep a readable record in the final list
                                    if (!empty($vehicle)) {
                                        $skipped_names[] = $vehicle;
                                    }
                                }
                            }
                        };

                        // STREAM rows → fill batch → process every 1000
                        $row_num = 0;
                        while ( ($row = fgetcsv($handle)) !== false ) {

                            // Map to associative array by header => value (like your original $data rows)
                            $vehicle_data = [];
                            foreach ($headers as $idx => $h) {
                                $vehicle_data[$h] = isset($row[$idx]) ? $row[$idx] : '';
                            }

                            // Skip completely empty lines
                            if (!empty(array_filter($vehicle_data))) {
                                $batch[] = $vehicle_data;
                                $row_num++;
                            }

                            if (count($batch) >= $batch_size) {
                                $chunk_index++;
                                $process_batch($batch);

                                // Log each chunk exactly as requested
                                $log("Chunk {$chunk_index} (".count($batch)." vehicles) imported.");

                                // Reset
                                $batch = [];
                                if (function_exists('gc_collect_cycles')) { gc_collect_cycles(); }
                            }
                        }

                        // Process remaining rows (< 1000)
                        if (!empty($batch)) {
                            $chunk_index++;
                            $process_batch($batch);
                            $log("Chunk {$chunk_index} (".count($batch)." vehicles) imported.");
                        }

                        fclose($handle);

                        // Transient cleanup (kept intact + safe existence checks)
                        if ( function_exists('delete_transient') ) {
                            delete_transient( 'vehicles-first-param' );
                            if ( function_exists('delete_transients_with_prefix') ) {
                                delete_transients_with_prefix( 'vehicles-first-param-' );
                                delete_transients_with_prefix( 'vin_decode_' );
                            }
                            delete_transient( 'vehicles' );
                            delete_transient( 'vehicle-list' );
                            delete_transient( 'universal-products' );
                        }

                        // Summaries
                        $log("All done. Inserted: {$done}. Skipped: {$skip}.");

                        // Build the SAME response your JS expects, with extra info embedded
                        $skipped_unique = array_values(array_unique(array_filter($skipped_names)));

                        // Put extras into the existing "taxonomy-link" container so no JS/HTML changes are needed
                        $taxonomy_link_html  = '<a class="button button-primary" target="_blank" href="' . esc_url( admin_url('edit-tags.php?taxonomy=vehicles') ) . '">' . esc_html__("View vehicles","enovathemes-addons") . '</a> ';
                        $taxonomy_link_html .= '<a class="button button-primary download-log" target="_blank" href="' . esc_url($log_url) . '">' . esc_html__("Download log","enovathemes-addons") . '</a>';

                        // Append skipped list (count + <details>) into the same block
                        $taxonomy_link_html .= '<div style="margin-top:12px">';
                        $taxonomy_link_html .= '<strong>' . esc_html__("Skipped vehicles","enovathemes-addons") . ':</strong> ' . intval(count($skipped_unique));
                        if (!empty($skipped_unique)) {
                            $taxonomy_link_html .= '<details style="margin-top:6px"><summary>' . esc_html__("Show list","enovathemes-addons") . '</summary><ul style="margin-top:6px;max-height:220px;overflow:auto;">';
                            foreach ($skipped_unique as $name) {
                                $taxonomy_link_html .= '<li>' . esc_html($name) . '</li>';
                            }
                            $taxonomy_link_html .= '</ul></details>';
                        }
                        $taxonomy_link_html .= '</div>';

                        $output = array(
                            'done'           => $done,                    // JS reads this
                            'skip'           => $skip,                    // (kept for completeness)
                            'taxonomy-link'  => $taxonomy_link_html       // JS injects this block → shows log link + skipped list
                        );

                        echo json_encode($output);
                        die;

                    } else {
                        echo json_encode(esc_html__('Upload error','enovathemes-addons'));
                        die;
                    }
                }
                add_action( 'wp_ajax_csv_map', 'enovathemes_addons_map_csv' );

                function enovathemes_addons_fetch_vehicle_params(){

                    $params = false;

                    $vehicle_params = get_theme_mod('vehicle_params');

                    if (empty($vehicle_params)) {
                        $params = array(
                            'make',
                            'model',
                            'year',
                            'trim',
                            'engine',
                            'transmission'
                        );
                    } else {
                        $vehicle_params = explode(', ', sanitize_text_field($vehicle_params));
                        if (is_array($vehicle_params)) {
                            $params = $vehicle_params;
                        }
                    }

                    return $params;
                }

                add_filter('vehicle_params','enovathemes_addons_vehicle_params');
                function enovathemes_addons_vehicle_params($params){

                    if (empty($params)) {
                        $params = enovathemes_addons_fetch_vehicle_params();
                    }

                    return $params;
                }

            /*  Single product ajaxes
            ---------------------*/

                function et_output_post_vehicle_table($post,$checkbox,$vehicle_params){
                    $product_terms = get_the_terms( $post, 'vehicles');

                    if (! is_wp_error( $product_terms ) && $product_terms) {

                        $output = '';

                        foreach ($product_terms as $term) {

                            $terms_output = '';

                            $vehicle_data = array();
                            
                            foreach ($vehicle_params as $param) {
                                $term_meta = get_term_meta($term->term_id,'vehicle_'.$param, true);
                                $terms_output .= '<td>'.$term_meta.'</td>';

                                $vehicle_data[$param] = $term_meta;

                            }

                            $output .= '<tr class="vehicle-tr" data-vehicle="'.htmlspecialchars(json_encode($vehicle_data)).'">';
                                if ($checkbox) {
                                    $output .= '<td><input checked name="'.$term->term_id.'" type="checkbox" value="'.$term->term_id.'" /></td>';
                                }
                                $output .= $terms_output;
                            $output .= '</tr>';
                            
                        }

                        if (!empty($output)) {
                            return $output;
                        }

                    }

                    return false;
                }

                function et_render_vehicles_table($post,$terms = true,$checkbox = true){


                    if (isset($post) && !empty($post)) {
                        $vehicle_params = apply_filters( 'vehicle_params','');
                        if ($vehicle_params != false) {

                            $output = $thead = $tbody = "";

                            foreach ( $vehicle_params as $param ) {
                                $label = $vehicle_param_labels[ $param ]
                                    ?? esc_html( ucwords( str_replace( ['_', '-'], ' ', (string) $param ) ) );

                                $thead .= '<th>' . $label . '</th>';
                            }

                            if (is_array($post)) {

                                foreach ($post as $id) {

                                    $post_vehicle_table = et_output_post_vehicle_table($id,$checkbox,$vehicle_params);

                                    if ($post_vehicle_table) {

                                        $tbody .= '<tr class="post" id="'.$id.'">';
                                            $tbody .= '<td colspan="'.(count($vehicle_params) + 1).'">';

                                                $thumbnail = get_the_post_thumbnail_url($id,'thumbnail');

                                                if ($thumbnail) {
                                                    $tbody .= '<img src="'.esc_url($thumbnail).'" width="40" height="40" />';
                                                }

                                                $tbody .= '<h6>'.get_the_title($id).'</h6>';

                                            $tbody .= '</td>';
                                        $tbody .= '</tr>';
                                        $tbody .= $post_vehicle_table; 

                                    }
                                }

                                if(!empty($tbody)){

                                    if (!empty($thead)) {
                                        $output .= '<thead class="hidden"><tr>';
                                            if ($checkbox) {
                                                $output .= '<th><input checked name="all" type="checkbox" value="*" /></th>';
                                            }
                                            $output .= $thead;
                                        $output .= '</tr></thead>';
                                    }

                                    $output .= '<tbody>'.$tbody.'</tbody>';
                                    $output .= '<tfoot class="hidden"><tr><td colspan="20"><a class="vehicle-assign-action button button-primary button-large" href="#">'.esc_html__('Update','enovathemes-addons').'</a><input type="hidden" id="assign-nonce" name="assign-nonce" value="'.esc_attr(wp_create_nonce('vehicle-assign')).'"></td></tr></tfoot>'; 

                                } else {
                                    $output = '';
                                }

                            } else {

                                $post_vehicle_table = et_output_post_vehicle_table($post,$checkbox,$vehicle_params);

                                if ($post_vehicle_table) {

                                    if (!empty($thead)) {
                                        $output .= '<thead><tr>';
                                            if ($checkbox) {
                                                $output .= '<th><input checked name="all" type="checkbox" value="*" /></th>';
                                            }
                                            $output .= $thead;
                                        $output .= '</tr></thead>';
                                    }

                                    $output .= '<tbody>'.$post_vehicle_table.'</tbody>'; 
                                    $output .= '<tfoot class="hidden"><tr><td colspan="20"><a class="vehicle-assign-action button button-primary button-large" href="#">'.esc_html__('Update','enovathemes-addons').'</a><input type="hidden" id="assign-nonce" name="assign-nonce" value="'.esc_attr(wp_create_nonce('vehicle-assign')).'"></td></tr></tfoot>'; 
                                
                                }
                            }

                            if (!empty($output)) {
                                return $output;
                            }
                            
                        }
                    }

                    return false;
                }

                function enovathemes_addons_fetch_vehicles_params(){
                    $vehicle_params = apply_filters( 'vehicle_params','');
                    if ($vehicle_params != false) {

                        $first_param_values = enovathemes_addons_vehicle_first_param('',false);

                        $form_output = '';

                        $i = 1;

                        foreach ($vehicle_params as $param) {

                            if ($i == 1) {

                                $param_key  = sanitize_key( $param );
                                $label      = ucwords( str_replace( ['_','-'], ' ', $param_key ) );
                                $placeholder = sprintf(
                                    /* translators: %s is a vehicle parameter label like "Make". */
                                    esc_html__( 'Choose %s', 'enovathemes-addons' ),
                                    $label
                                );

                                $form_output .= '<div class="select-wrapper"><select class="vehicle-param" name="' . esc_attr( $param_key ) . '" data-placeholder="' . esc_attr( $placeholder ) . '"><option></option>';

                                if (!is_wp_error($first_param_values)) {
                                    foreach ($first_param_values as $value) {
                                        $form_output .= '<option value="'.$value.'">'.$value.'</option>';
                                    }
                                }
                            } else {
                                $param_key   = sanitize_key( $param );
                                $label       = ucwords( str_replace( ['_', '-'], ' ', $param_key ) );
                                $placeholder = sprintf(
                                    /* translators: %s is a vehicle parameter label like "Make". */
                                    esc_html__( 'Choose %s', 'enovathemes-addons' ),
                                    $label
                                );

                                // For multiple selects, use [] so PHP receives an array.
                                $name_attr = $param_key . '[]';

                                $form_output .= '<div class="select-wrapper"><select class="vehicle-param" name="' . esc_attr( $name_attr ) . '" data-placeholder="' . esc_attr( $placeholder ) . '" multiple="multiple"><option></option>';

                            }
                            $form_output .= '</select></div>';
                        
                            $i++;
                        }

                        if (!empty($form_output)) {
                            $form_output = '<form class="vehicle-admin-filter">'.$form_output.'</form>';
                        }

                        $data = array();
                        $data['form'] = $form_output;

                        if (isset($_POST['products'])){
                            $data['html'] = et_render_vehicles_table($_POST['products']);
                        } elseif(!isset($_POST['form'])) {
                            $data['html'] = et_render_vehicles_table($_POST['post_id']);
                        }
                        
                        echo json_encode($data);

                    }
                    die;
                }
                add_action( 'wp_ajax_fetch_vehicles_params', 'enovathemes_addons_fetch_vehicles_params' );

                function enovathemes_addons_fetch_product_vehicles(){

                    $vehicle_params  = apply_filters( 'vehicle_params','');
                    $post_attributes = json_decode( stripslashes ($_POST['attributes']),true);

                    if ($vehicle_params != false) {

                        $vehicles   = array();
                        $next       = array();
                        $meta_query = array();
                        $vehicles_terms = false;
                        $off        = array('next','year','post_id');

                        foreach ($post_attributes as $key => $value) {

                            if (!in_array($key,$off) && $value && in_array($key,$vehicle_params)) {
                                
                                $compare = (is_array($value)) ? "IN" : "=";

                                $meta_query[] = [
                                    "key" => "vehicle_" . $key,
                                    "value" => $value,
                                    "compare" => $compare,
                                ]; 
                                
                            }
                           
                        }

                        if (!empty($meta_query)) {

                            $meta_query["relation"] = "AND";

                            $args = [
                                "taxonomy" => "vehicles",
                                "hide_empty" => false,
                                "meta_query" => $meta_query,
                            ];

                            $vehicles_terms = get_terms($args);

                        } elseif(array_key_exists('year', $post_attributes) && !empty($post_attributes['year'])) {

                            $args = [
                              "taxonomy" => "vehicles",
                              "hide_empty" => false,
                            ];

                            $vehicles_terms = get_terms($args);

                            if (!is_wp_error($vehicles_terms)) {
                                $vehicles_terms_with_year = array();


                                foreach ($vehicles_terms as $vehicle) {
                                    $year  = get_term_meta($vehicle->term_id, 'vehicle_year', true );
                                    $years = et_year_formatting($year);

                                    if (is_array($years) && in_array($post_attributes['year'], $years)) {
                                        $vehicles_terms_with_year[] = $vehicle;
                                    }

                                }

                                if (!empty($vehicles_terms_with_year)) {
                                    $vehicles_terms = $vehicles_terms_with_year;
                                }


                            }

                        }

                        if (!is_wp_error($vehicles_terms)) {

                            if (isset($post_attributes['year']) && !empty($post_attributes['year']) && !empty($meta_query)) {
                                
                                $vehicles_terms_with_year = array();

                                foreach ($vehicles_terms as $vehicle) {
                                    $year  = get_term_meta($vehicle->term_id, 'vehicle_year', true );
                                    $years = et_year_formatting($year);

                                    if (is_array($years) && in_array($post_attributes['year'], $years)) {
                                        $vehicles_terms_with_year[] = $vehicle;
                                    }

                                }

                                if (!empty($vehicles_terms_with_year)) {
                                    $vehicles_terms = $vehicles_terms_with_year;
                                }
                            }


                            foreach ($vehicles_terms as $vehicle) {

                                $params = array();

                                foreach ($vehicle_params as $param) {
                                    $param_value = esc_html(get_term_meta($vehicle->term_id, 'vehicle_'.$param, true ));

                                    if (str_contains($param_value, '*')) {
                                        $param_value = str_replace("*", date('Y'), $param_value);
                                    }

                                    $params[$param] = $param_value;

                                }

                                if (isset($post_attributes['next']) && !empty($post_attributes['next'])) {
                                    if ($post_attributes['next'] == "year") {
                                        $year = get_term_meta($vehicle->term_id, 'vehicle_'.$post_attributes['next'], true );

                                        $years = et_year_formatting($year);

                                        if(!empty($years)){
                                            foreach ($years as $year) {
                                                $next[] = $year;
                                            }
                                        }

                                    } else {
                                        $next[] = esc_html(get_term_meta($vehicle->term_id, 'vehicle_'.$post_attributes['next'], true ));
                                    }
                                }

                                if (!empty($params)) {
                                    $vehicles[$vehicle->term_id] = $params;
                                }

                            }

                            $vehicles = array_unique($vehicles,SORT_REGULAR);
                            $vehicles = array_filter($vehicles);

                            $next = array_unique($next,SORT_REGULAR);
                            $next = array_filter($next);


                            $output = '';

                            if (!empty($vehicles)) {

                                $product_terms = (isset($post_attributes['post_id'])) ? get_the_terms( $post_attributes['post_id'], 'vehicles') : false;
                                $product_terms_array = array();

                                if (! is_wp_error( $product_terms ) && $product_terms) {
                                    foreach ($product_terms as $term) {
                                        $product_terms_array[] = $term->term_id;
                                    }
                                }

                                $tbody = $thead = '';

                                foreach ($vehicle_params as $param) {
                                    $thead .= '<th>'.ucfirst($param).'</th>';
                                }

                                if (!empty($thead)) {
                                    $output .= '<thead><tr><th><input name="all" type="checkbox" value="*" /></th>'.$thead.'</tr></thead>';
                                } 

                                foreach ($vehicles as $key => $value) {
                                    $tbody .= '<tr>';

                                        $checked = (!empty($product_terms_array) && in_array($key, $product_terms_array)) ? 'checked' : '';

                                        $tbody .= '<td><input '.$checked.' name="'.$key.'" type="checkbox" value="'.$key.'" /></td>';
                                        foreach ($value as $name => $val) {
                                            if ($name != 'name') {
                                                $tbody .= '<td>'.$val.'</td>';
                                            }
                                        }
                                    $tbody .= '</tr>';
                                }
                                
                                if (!empty($tbody)) {
                                    $output .= '<tbody>'.$tbody.'</tbody>'; 
                                }

                                $output .= '<tfoot class="hidden"><tr><td colspan="20"><a class="vehicle-assign-action button button-primary button-large" href="#">'.esc_html__('Update','enovathemes-addons').'</a><input type="hidden" id="assign-nonce" name="assign-nonce" value="'.esc_attr(wp_create_nonce('vehicle-assign')).'"></td></tr></tfoot>'; 

                            }

                            $next_output = '';


                            if (!empty($next)) {
                                foreach ($next as $value) {
                                    $next_output .= '<option value="'.$value.'">'.$value.'</option>';
                                }
                            }

                            $data = array();
                            $data['html'] = $output;
                            $data['next']  = $next_output;
                            $data['dev']   = $next;
                            
                            echo json_encode($data);

                        }

                    }
                    
                    die;
                }
                add_action( 'wp_ajax_fetch_product_vehicles', 'enovathemes_addons_fetch_product_vehicles' );

                function enovathemes_addons_assign_product_vehicles(){

                    wp_verify_nonce( $_POST['nonce'], 'vehicle-assign' );

                    $assign  = (isset($_POST['assign']) && !empty($_POST['assign'])) ? json_decode(stripslashes($_POST['assign']),true) : false;
                    $unsign  = (isset($_POST['unsign']) && !empty($_POST['unsign'])) ? json_decode(stripslashes($_POST['unsign']),true) : false;
                    $post_id = (isset($_POST['post_id']) && !empty($_POST['post_id'])) ? $_POST['post_id'] : false;
                    $products = (isset($_POST['products']) && !empty($_POST['products'])) ? $_POST['products'] : false;

                    if ($post_id || $products) {

                        if ($assign) {
                            $assign_IDs = array();
          
                            foreach ($assign as $vehicle) {
                                $assign_IDs[] = intval($vehicle);
                            }

                            if ($products) {
                                foreach ($products as $product) {

                                    // $terms = get_the_terms($product,'vehicles');
                                    // if ($terms && !is_wp_error($terms)) {
                                    //     foreach ($terms as $term) {
                                    //         wp_remove_object_terms($product, $term->term_id, 'vehicles');
                                    //     }
                                    // }

                                    wp_set_post_terms($product,$assign_IDs,'vehicles',true);

                                }
                            } elseif($post_id){
                                wp_set_post_terms($post_id,$assign_IDs,'vehicles',true);
                            }

                        }

                        if ($unsign) {

                            $unsign_IDs = array();
          
                            foreach ($unsign as $vehicle) {
                                $unsign_IDs[] = intval($vehicle);
                            }

                            if ($products) {
                                foreach ($products as $product) {
                                    wp_remove_object_terms($product,$unsign_IDs,'vehicles');
                                }
                            } elseif($post_id){
                                wp_remove_object_terms($post_id,$unsign_IDs,'vehicles');

                            }

                        }
                            
                    }

                    die;
                }
                add_action( 'wp_ajax_assign_product_vehicles', 'enovathemes_addons_assign_product_vehicles' );

            /* Product import/export vehicles
            ---------------------*/

                /* WooCommerce Export
                ---------------------*/

                    function enovathemes_addons_add_columns_to_woo_export( $columns ) {
                        $columns[ 'vehicles' ]  = 'Vehicles';
                        return $columns;
                    }
                    add_filter( 'woocommerce_product_export_column_names', 'enovathemes_addons_add_columns_to_woo_export' );
                    add_filter( 'woocommerce_product_export_product_default_columns', 'enovathemes_addons_add_columns_to_woo_export' );

                    function enovathemes_addons_export_vehicles_with_products( $value, $product ) {

                        $vehicles  = get_terms( array( 'object_ids' => $product->get_ID(), 'taxonomy' => 'vehicles' ) );

                        $data = array();

                        if ( ! is_wp_error( $vehicles ) ) {
                            foreach ( (array) $vehicles as $term ) {
                                $data[] = $term->name;
                            }
                        }

                        if (!empty($data)) {
                            $value = implode('|', $data);
                        }

                        return $value;
                    }
                    add_filter( 'woocommerce_product_export_product_column_vehicles', 'enovathemes_addons_export_vehicles_with_products', 10, 2 );

                /* WooCommerce Import
                ---------------------*/

                    function enovathemes_addons_map_vehicle_column( $columns ) {
                        $columns[ 'vehicles' ]  = 'Vehicles';
                        return $columns;
                    }
                    add_filter( 'woocommerce_csv_product_import_mapping_options', 'enovathemes_addons_map_vehicle_column' );

                    function enovathemes_addons_add_columns_to_woo_export_to_mapping_screen( $columns ) {
                        $columns['Vehicles']   = 'vehicles';
                        return $columns;
                    }
                    add_filter( 'woocommerce_csv_product_import_mapping_default_columns', 'enovathemes_addons_add_columns_to_woo_export_to_mapping_screen' );

                    function enovathemes_addons_parse_taxonomy_explode( $parsed_data, $importer ) {

                        if ( ! empty( $parsed_data[ 'vehicles' ] ) ) {
                            $data = explode('|',str_replace(' | ','|',$parsed_data[ 'vehicles' ]));
                            if ( is_array( $data ) ) {
                                unset( $parsed_data[ 'vehicles' ] );
                                $parsed_data[ 'vehicles' ] = array();
                                foreach ( $data as $vehicle_data ) {
                                    $parsed_data[ 'vehicles' ][] = $vehicle_data;
                                }
                            }
                        }

                        return $parsed_data;
                    }
                    add_filter( 'woocommerce_product_importer_parsed_data', 'enovathemes_addons_parse_taxonomy_explode', 10, 2 );

                    function enovathemes_addons_set_vehicle_taxonomy( $product, $data ) {
                        if ( is_a( $product, 'WC_Product' ) ) {
                            if( ! empty( $data[ 'vehicles' ] ) ) {

                                $vehicle_params = apply_filters( 'vehicle_params','');
                                
                                if ($vehicle_params != false) {

                                    $vehicles = is_array($data[ 'vehicles' ]) ? $data[ 'vehicles' ] : array($data[ 'vehicles' ]);

                                    wp_set_object_terms( $product->get_id(),  (array) $vehicles, 'vehicles' );

                                    $terms  = wp_get_post_terms( $product->get_ID(), 'vehicles');

                                    if ( ! is_wp_error( $terms )) {
                                        foreach ( $terms as $vehicle ) {
                                            $vehicle_data = explode(',', str_replace(', ',',',$vehicle->name));

                                            foreach ($vehicle_params as $index => $param) {
                                                if (array_key_exists($index,$vehicle_data)) {
                                                    update_term_meta( $vehicle->term_id,'vehicle_'.$param,$vehicle_data[$index]);
                                                }
                                            }

                                        }
                                    }

                                }
                            }
                        }


                        // Vehicle transients
                        delete_transient( 'vehicles-first-param' );
                        delete_transients_with_prefix( 'vehicles-first-param-' );
                        delete_transients_with_prefix( 'vin_decode_' );
                        delete_transient( 'vehicles' );
                        delete_transient( 'vehicle-list' );
                        delete_transient( 'universal-products' );

                        return $product;
                    }
                    add_filter( 'woocommerce_product_import_inserted_product_object', 'enovathemes_addons_set_vehicle_taxonomy', 10, 2 );

                /* WP All Import
                ---------------------*/

                    add_filter( 'wp_all_import_set_post_terms', 'enovathemes_addons_wp_all_import_set_post_terms', 10, 4 );
                    function enovathemes_addons_wp_all_import_set_post_terms( $term_taxonomy_ids, $tx_name, $pid, $import_id ) {
                        if ( $tx_name == 'vehicles' ){
                                $vehicle_params = apply_filters( 'vehicle_params','');
                            
                                if ($vehicle_params != false) {

                                    $terms  = get_terms(array(
                                        'taxonomy'   => 'vehicles',
                                        'hide_empty' => false,
                                        'include'    => $term_taxonomy_ids
                                    ));

                                    if ( ! is_wp_error( $terms )) {

                                        foreach ( $terms as $vehicle ) {
                                            $vehicle_data = explode(',', str_replace(', ',',',$vehicle->name));

                                            foreach ($vehicle_params as $index => $param) {
                                                if (array_key_exists($index,$vehicle_data)) {
                                                    update_term_meta( $vehicle->term_id,'vehicle_'.$param,$vehicle_data[$index]);
                                                }
                                            }

                                        }
                                    }
                                }
                        }

                        // Vehicle transients
                        delete_transient( 'vehicles-first-param' );
                        delete_transients_with_prefix( 'vehicles-first-param-' );
                        delete_transients_with_prefix( 'vin_decode_' );
                        delete_transient( 'vehicles' );
                        delete_transient( 'vehicle-list' );
                        delete_transient( 'universal-products' );

                        return $term_taxonomy_ids;
                    }

            /*  Vehicle filter
            ---------------------*/

                function enovathemes_addons_render_vehicle_filter_attribute($attribute,$first){

                    if ($first) {

                        $vehicles_first_param = enovathemes_addons_vehicle_first_param($attribute['attr']);

                        if (!is_wp_error($vehicles_first_param) && !empty($vehicles_first_param)){

                            $data   = array();
                            $data[] = 'data-attribute="'.$attribute['attr'].'"';
                            /* translators: %s: attribute label (e.g., Engine). */
                            $label = wp_strip_all_tags( (string) ( $attribute['label'] ?? '' ) );

                            /* translators: %s: attribute label (e.g., Engine). */
                            $format = _x( 'Attribute: %s', 'data-label for attribute dropdown', 'enovathemes-addons' );

                            $data[] = 'data-label="' . esc_attr( sprintf( $format, $label ) ) . '"';
                            
                            $output = '<div class="vf-item '.$attribute['attr'].'" '.implode(' ', $data).'>';
                                $output .= '<select name="'.$attribute['attr'].'" disabled>';
                                    $output .= '<option class="default" value="">'
                                    . esc_html__( (string) ( $attribute['label'] ?? '' ),"enovathemes-addons" )
                                    . '</option>';

                                    foreach ($vehicles_first_param as $param) {
                                        $output .= '<option value="'.$param.'">'.$param.'</option>';
                                    }
                                $output .= '</select>';
                            $output .= '</div>';

                            echo $output;

                        }

                    } else {
                       $output = '<div class="vf-item '.$attribute['attr'].'">';
                            $output .= '<select name="'.$attribute['attr'].'" disabled>';
               
                                $output .= '<option class="default" value="">'
                                    . esc_html__( (string) ( $attribute['label'] ?? '' ),"enovathemes-addons" )
                                    . '</option>';

                            $output .= '</select>';
                        $output .= '</div>';
                        echo $output; 
                    }

                }

                function enovathemes_addons_vehicle_list() {

                    if ( false === ( $vehicles_data = get_transient( 'vehicle-list' ) ) ) {

                        $vehicles = get_terms( array(
                            'taxonomy'   => 'vehicles',
                            'hide_empty' => true,
                        ));

                        $vehicle_params = apply_filters( 'vehicle_params','');

                        $vehicles_data = [];

                        if (!is_wp_error($vehicles) && $vehicle_params != false) {

                            foreach ($vehicles as $vehicle){

                                $vehicle_atts = [];

                                foreach ($vehicle_params as $param) {
                                    if ($param == "year") {

                                        $vehicle_years = [];

                                        $year = get_term_meta($vehicle->term_id,'vehicle_'.$param,true);

                                        $years = et_year_formatting($year);

                                        if ($years) {
                                            foreach ($years as $year) {
                                                $vehicle_years[] = intval($year);
                                            }
                                        }

                                        $vehicle_atts[$param] = $vehicle_years;

                                    } else {
                                        $vehicle_atts[$param] = get_term_meta($vehicle->term_id,'vehicle_'.$param,true);
                                    }
                                }

                                if (!empty($vehicle_atts)) {
                                    $vehicles_data[$vehicle->slug] = $vehicle_atts;
                                }
                            }

                            // do not set an empty transient - should help catch private or empty accounts.
                            if ( ! empty( $vehicles_data ) ) {
                                $vehicles_data = base64_encode( serialize( $vehicles_data ) );
                                set_transient( 'vehicle-list', $vehicles_data, apply_filters( 'null_filter_cache_time', 0 ) );
                            }

                        }

                    }

                    if ( ! empty( $vehicles_data ) ) {

                        return unserialize( base64_decode( $vehicles_data ) );

                    } else {

                        return new WP_Error( 'no_vehilce_list', esc_html__( 'No vehicle.', 'enovathemes-addons' ) );

                    }
                }

                function fetch_enovathemes_addons_vehicle_list() {
                    $vehicles = enovathemes_addons_vehicle_list();
                    if (!is_wp_error($vehicles)) {
                        echo json_encode($vehicles);
                    }
                    die();
                }
                add_action( 'wp_ajax_fetch_vehicle_list', 'fetch_enovathemes_addons_vehicle_list' );
                add_action( 'wp_ajax_nopriv_fetch_vehicle_list', 'fetch_enovathemes_addons_vehicle_list' );

                function save_user_enovathemes_addons_vehicle_list() {

                    wp_verify_nonce( $_POST['nonce'], 'user-vehicle-filter' );

                    if (isset($_POST['vehicle']) && !empty($_POST['vehicle'])) {

                        $shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
                        if ('' === get_option( 'permalink_structure' )) {
                            $shop_link = get_home_url().'?post_type=product';
                        }

                        $user_vehicles = get_user_meta( get_current_user_id(), 'enovathemes_addons_user_vehicles', true);
                        $return        = '';
                        $vehicle_data  = '';
                        $output        = array();

                        if (!strpos($shop_link, '?')){$shop_link .= '?';}

                        if (is_array($_POST['vehicle'])) {

                            $vehicles = array();

                            foreach ($_POST['vehicle'] as $vehicle) {

                                $vehicle_values = array();
                                $vehicle_encode = base64_encode($vehicle);
                                $vehicle        = json_decode( html_entity_decode( stripslashes ($vehicle)),true );
                                $this_link      = $shop_link;

                                foreach ($vehicle as $key => $value) {
                                    if (!empty($value)) {
                                        if ($key == 'year') {$key = 'yr';}
                                        $this_link .= '&'.$key.'='.$value;
                                        $vehicle_values[] = $value;
                                    }
                                }

                                $this_link = str_replace('?&', '?', $this_link);

                                $vehicles[] = array(
                                    'base64_encode'=>$vehicle_encode,
                                    'vehicle_values'=>implode(', ', $vehicle_values),
                                    'link'=>$this_link,
                                );
                            }

                            if ($user_vehicles) {
                                $user_vehicles = explode(',', $user_vehicles);

                                foreach ($vehicles as $key => $value) {
                                    if (!in_array($value['base64_encode'], $user_vehicles)) {
                                        $user_vehicles[] = $value['base64_encode'];
                                        $return .= '<li class="new" data-vehicle="'.$value['base64_encode'].'"><a href="'.esc_url($value['link']).'">'.$value['vehicle_values'].'</a><span class="remove"></span></li>';
                                    }
                                }

                                $user_vehicles = implode(',', $user_vehicles);
                            } else {

                                $user_vehicles = array();

                                foreach ($vehicles as $key => $value) {
                                    $user_vehicles[] = $value['base64_encode'];
                                    $return .= '<li class="new" data-vehicle="'.$value['base64_encode'].'"><a href="'.esc_url($value['link']).'">'.$value['vehicle_values'].'</a><span class="remove"></span></li>';
                                }

                                $user_vehicles = implode(',', $user_vehicles);
                            }

                        } else {

                            $vehicle_values = array();
                            $vehicle = json_decode( html_entity_decode( stripslashes ($_POST['vehicle'])),true );
                            $base64_encode_vehicle = base64_encode($_POST['vehicle']);

                            if (isset($vehicle['vin']) && !empty($vehicle['vin'])) {

                                $vehicle_data = enovathemes_addons_vin_decoder($vehicle['vin']);

                                if (!isset($vehicle_data['error'])) {
                                    $vehicle = $vehicle_data;
                                    $base64_encode_vehicle = base64_encode(json_encode($vehicle_data));
                                } else {
                                    $vehicle = false;
                                }
                            }

                            if ($vehicle) {

                                $vehicle_encode = $base64_encode_vehicle;

                                foreach ($vehicle as $key => $value) {
                                    if (!empty($value)) {
                                        if ($key == 'year') {$key = 'yr';}
                                        $shop_link .= '&'.$key.'='.$value;
                                        $vehicle_values[] = $value;
                                    }
                                }

                                $shop_link = str_replace('?&', '?', $shop_link);

                                if ($user_vehicles) {
                                    $user_vehicles = explode(',', $user_vehicles);

                                    if (!in_array($vehicle_encode, $user_vehicles)) {
                                        $user_vehicles[] = $vehicle_encode;
                                        $return .='<li class="new" data-vehicle="'.$vehicle_encode.'"><a href="'.esc_url($shop_link).'">'.implode(', ', $vehicle_values).'</a><span class="remove"></span></li>';
                                    }

                                    $user_vehicles = implode(',', $user_vehicles);
                                } else {
                                    $user_vehicles = $vehicle_encode;
                                    $return .='<li class="new" data-vehicle="'.$vehicle_encode.'"><a href="'.esc_url($shop_link).'">'.implode(', ', $vehicle_values).'</a><span class="remove"></span></li>';
                                }

                            }

                        }

                        if (!empty($user_vehicles)) {
                            update_user_meta( get_current_user_id(), 'enovathemes_addons_user_vehicles', $user_vehicles);
                        }

                        if (!empty($return)) {
                            $output['output'] = $return;

                            if (!empty($vehicle_data) && !isset($vehicle_data['error'])) {
                                $output['vehicle'] = $vehicle_data;
                            }

                        } else {

                            if (!empty($vehicle_data) && isset($vehicle_data['error'])) {
                                $output['error'] = $vehicle_data['error'];
                            } else {
                                $output['error'] = esc_html__("Duplicate record","enovathemes-addons");
                            }
                            
                        }

                        if (!empty($output)) {
                            echo json_encode($output);
                        }

                    }

                    die();
                }
                add_action( 'wp_ajax_save_user_vehicle_list', 'save_user_enovathemes_addons_vehicle_list' );
                add_action( 'wp_ajax_nopriv_save_user_vehicle_list', 'save_user_enovathemes_addons_vehicle_list' );


                function fetch_user_enovathemes_addons_vehicle_list() {

                    wp_verify_nonce( $_POST['nonce'], 'user-vehicle-filter' );

                    $shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
                    if ('' === get_option( 'permalink_structure' )) {
                        $shop_link = get_home_url().'?post_type=product';
                    }

                    $user_vehicles = get_user_meta( get_current_user_id(), 'enovathemes_addons_user_vehicles', true);
                    $return        = '';
                    $vehicle_data  = '';
                    $output        = array();

                    if (!strpos($shop_link, '?')){$shop_link .= '?';}

                    if ($user_vehicles) {

                        $user_vehicles = explode(',', $user_vehicles);

                        foreach ($user_vehicles as $vehicle) {

                            $base64_encode_vehicle = $vehicle;
                            $vehicle               = json_decode( html_entity_decode( stripslashes (base64_decode($vehicle))),true );
                            $this_link             = $shop_link;
                            $vehicle_values        = array();

                            foreach ($vehicle as $key => $value) {
                                if (!empty($value)) {
                                    if ($key == 'year') {$key = 'yr';}
                                    $this_link .= '&'.$key.'='.$value;
                                    $vehicle_values[] = $value;
                                }
                            }

                            $this_link = str_replace('?&', '?', $this_link);

                            $return .= '<li data-vehicle="'.$base64_encode_vehicle.'"><a href="'.esc_url($this_link).'">'.implode(', ', $vehicle_values).'</a><span class="remove"></span></li>';

                        }

                    }

                    if (!empty($return)) {
                        $output['output'] = $return;
                    }

                    if (!empty($output)) {
                        echo json_encode($output);
                    }

                    die();
                }
                add_action( 'wp_ajax_fetch_user_vehicle_list', 'fetch_user_enovathemes_addons_vehicle_list' );


                function remove_user_enovathemes_addons_vehicle_list() {

                    wp_verify_nonce( $_POST['nonce'], 'user-vehicle-filter' );

                    if (isset($_POST['vehicle']) && !empty($_POST['vehicle'])) {

                        $user_vehicles = get_user_meta( get_current_user_id(), 'enovathemes_addons_user_vehicles', true);

                        if ($user_vehicles) {

                            $user_vehicles = explode(',', $user_vehicles);

                            unset($user_vehicles[array_search($_POST['vehicle'],$user_vehicles)]); 

                            $user_vehicles = array_values($user_vehicles);

                            $user_vehicles = implode(',', $user_vehicles);

                            update_user_meta( get_current_user_id(), 'enovathemes_addons_user_vehicles', $user_vehicles);

                            delete_transient( 'dynamic-styles-cached' );
                            delete_transient( 'enovathemes-megamenu' );
                            delete_transient( 'enovathemes-megamenu-names' );
                            delete_transient( 'enovathemes-headers' );
                            delete_transient( 'enovathemes-footers' );
                            delete_transient( 'enovathemes-header-list' );
                            delete_transient( 'enovathemes-footer-list' );

                            echo $_POST['vehicle'];
                        }   

                    }
                   
                    die();
                }
                add_action( 'wp_ajax_remove_user_vehicle_list', 'remove_user_enovathemes_addons_vehicle_list' );

                /*  Custom user columns
                ---------------------*/

                    function enovathemes_addons_custom_user_column($columns) {
                        $columns['user_vehicles'] = 'User Vehicles';
                        return $columns;
                    }
                    add_filter('manage_users_columns', 'enovathemes_addons_custom_user_column');

                    function enovathemes_addons_custom_user_column_content($value, $column_name, $user_id) {
                        if ($column_name == 'user_vehicles') {
                            $user_vehicles = get_user_meta($user_id, 'enovathemes_addons_user_vehicles', true);

                            if (!empty($user_vehicles)) {

                                    $return = '';
                                    
                                    $user_vehicles = explode(',', $user_vehicles);

                                    foreach ($user_vehicles as $vehicle) {

                                        $vehicle               = json_decode( html_entity_decode( stripslashes (base64_decode($vehicle))),true );
                                        $vehicle_values        = array();

                                        foreach ($vehicle as $key => $value) {
                                            if (!empty($value)) {
                                                if ($key == 'year') {$key = 'yr';}
                                                $vehicle_values[] = $value;
                                            }
                                        }

                                        $return .= '<li>'.implode(', ', $vehicle_values).'</li>';

                                    }

                                    return '<ul class="user-vehicle-list">'.$return.'</ul>';


                            }
                        }
                        return $value;
                    }
                    add_filter('manage_users_custom_column', 'enovathemes_addons_custom_user_column_content', 10, 3);

            /*  Universal products before
            ---------------------*/

                add_action( 'woocommerce_before_shop_loop', 'enovathemes_before_shop_loop_universal_products_title', 45 );
                function enovathemes_before_shop_loop_universal_products_title() {
                    if (isset($_POST['universal']) && $_POST['universal'] == 1 && !isset($_GET['ajax'])) {

                        $universal_products = enovathemes_addons_universal_products();

                        if (!is_wp_error($universal_products)) {
                            $shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
                            if ('' === get_option( 'permalink_structure' )) {
                                $shop_link = get_home_url().'?post_type=product';
                            }
                            echo '<a class="shop-page et-button medium button" href="'.esc_url($shop_link).'">'.esc_html__("Go back to shop","enovathemes-addons").'</a>';
                            echo '<h5 class="universal-title woocommerce-no-products-found">'.esc_html__("No products found matching the filter criteria. But here are some universal products:","enovathemes-addons").'</h5>';
                        }

                    }
                }

        }

    });

/*  Dashboard
--------------------*/

    function et__current_language(){

        $is_wpml     = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_current_language');

        $current_language = get_locale();
        $current_language = substr($current_language, 0, 2);

        if ($is_wpml) {
            $current_language = apply_filters('wpml_current_language', null);
        } elseif($is_polylang){
            $current_language = pll_current_language();
        }

        return $current_language;
    }

    function et__list_languages() {
        $is_wpml     = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_the_languages');

        if ($is_wpml) {
            // WPML: get active languages
            $languages = apply_filters( 'wpml_active_languages', null, [] );
            if (!empty($languages) && is_array($languages)) {
                return array_keys($languages); // return only language codes
            }
            return false;
        } elseif ($is_polylang) {
            // Polylang: get available languages
            $languages = pll_the_languages([ 'raw' => 1, 'hide_if_empty' => 0 ]);
            if (!empty($languages) && is_array($languages)) {
                return array_keys($languages); // return only language codes
            }
            return false;
        }

        return false; // default, no multilingual plugin
    }

    function et__default_language() {
        $is_wpml     = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_default_language');

        if ($is_wpml) {
            // WPML: get default language
            $default_lang = apply_filters('wpml_default_language', null);
            return $default_lang ? $default_lang : false;
        } elseif ($is_polylang) {
            // Polylang: get default language
            $default_lang = pll_default_language();
            return $default_lang ? $default_lang : false;
        }

        return false; // no multilingual plugin
    }

    function et__pick_localized(array $item, string $base_key, string $lang, ?string $default_lang = null) {
        // Try explicit language
        if (!empty($item["{$base_key}_{$lang}"])) {
            return $item["{$base_key}_{$lang}"];
        }
        // Try site default language (if provided)
        if ($default_lang && !empty($item["{$base_key}_{$default_lang}"])) {
            return $item["{$base_key}_{$default_lang}"];
        }
        // Fallback to base key
        return $item[$base_key] ?? '';
    }

    add_action('wp_footer','enovathemes_addons_dashboard');
    function enovathemes_addons_dashboard(){

        $mods = et_get_theme_mods();
        $sticky_dashboard = $mods && isset($mods['sticky_dashboard']) && !empty($mods['sticky_dashboard']) ? $mods['sticky_dashboard'] : false;
        if ($sticky_dashboard) {

            $compare      = (get_theme_mod('product_compare') != null && !empty(get_theme_mod('product_compare'))) ? true : false;
            $wishlist     = (get_theme_mod('product_wishlist') != null && !empty(get_theme_mod('product_wishlist'))) ? true : false;
            $blog_sidebar = (isset($mods['blog_sidebar']) && $mods['blog_sidebar'] == 1) ? true : false;
            $post_sidebar = (isset($mods['post_sidebar']) && $mods['post_sidebar'] == 1) ? true : false;
            $shop_sidebar = (isset($mods['shop_sidebar']) && $mods['shop_sidebar'] == 1) ? true : false;

            if ($compare) {
                $sticky_dashboard[] = [
                    'link_text'   => esc_html__('Compare',"enovathemes-addons"),
                    'list_class'   => 'compare hidden',
                    'link_class'   => 'et-compare-icon',
                    'link_url'    => '#',
                    'link_target' => '_self',
                    'link_icon'   => THEME_SVG.'compare.svg',
                ];
            }

            if (is_active_sidebar('shop-widgets') && $shop_sidebar) {
                $shop_sidebar = 'true';
            }

            if (is_active_sidebar('blog-widgets') && $blog_sidebar) {
                $blog_sidebar = 'true';
            }
            if (
                ( is_singular('post') && is_active_sidebar('blog-single-widgets') && $post_sidebar )
                ||
                ( ( is_home() || is_category() || is_tag() ) && is_active_sidebar('blog-widgets') && $blog_sidebar )
            ){
                $sticky_dashboard[] = [
                    'link_text'   => esc_html__('Sidebar',"enovathemes-addons"),
                    'list_class'   => 'post-sidebar',
                    'link_class'   => 'post-sidebar content-sidebar-toggle blog',
                    'link_url'    => '#',
                    'link_target' => '_self',
                    'link_icon'   => THEME_SVG.'sidebar.svg',
                ];
            } elseif(
                class_exists('Woocommerce') && 
                (
                    (is_shop() || is_tax('product_cat') || is_tax('product_tag')) && 
                    (is_active_sidebar('shop-widgets') && $shop_sidebar)
                )
            ) {
                $sticky_dashboard[] = [
                    'link_text'   => esc_html__('Filter',"enovathemes-addons"),
                    'list_class'   => 'shop-filter active',
                    'link_class'   => 'content-sidebar-toggle shop',
                    'link_url'    => '#',
                    'link_target' => '_self',
                    'link_icon'   => THEME_SVG.'filter.svg',
                ];
            }

            $sticky_dashboard[] = [
                'link_text'   => esc_html__('Top',"enovathemes-addons"),
                'list_class'   => 'arrow-top',
                'link_class'   => 'to-top',
                'link_url'    => '#wrap',
                'link_target' => '_self',
                'link_icon'   => THEME_SVG.'arrow-up.svg',
            ];
            
            // Current & default languages (from your previously defined helpers)
            $current_lang = function_exists('et__current_language') ? et__current_language() : '';
            $default_lang = function_exists('et__default_language') ? et__default_language() : '';

            echo '<ul class="sticky-dashboard">';
            foreach ($sticky_dashboard as $item) {
                
                $url  = et__pick_localized($item, 'link_url',  $current_lang, $default_lang);
                $text = et__pick_localized($item, 'link_text', $current_lang, $default_lang);
                
                echo '<li class="'.esc_attr($item['list_class']).'"><a 
                    href="'.esc_url($url).'"
                    title="'.esc_attr($text).'"
                    target="'.esc_attr($item['link_target']).'"
                    class="'.esc_attr($item['link_class']).'"
                >';
                    if ($item['link_class'] == "et-compare-icon") {
                        echo '<span class="compare-contents count"></span>';
                    }
                    if ($item['link_icon']) {
                        echo '<img src="'.esc_url($item['link_icon']).'" />';
                    }
                    echo esc_html($text);
                echo '</a></li>';
            }
            echo '</ul>';
        }

    }

    function enovathemes_addons_dashboard_myaccount(){

        if (class_exists('Woocommerce')) {
            do_action( 'woocommerce_account_navigation' );
        }

        die();
    }
    add_action( 'wp_ajax_fetch_dashboard_myaccount', 'enovathemes_addons_dashboard_myaccount' );

    function enovathemes_addons_dashboard_categories(){

        if (class_exists('Woocommerce')) {

            $args = array(
                'parent'     => 0,
                'hide_empty' => true,
                'meta_key'   => 'order',
                'orderby'    => 'meta_value_num'
            );

            if (isset($_POST['term_id']) && !empty($_POST['term_id'])) {
                $children = get_term_children($_POST['term_id'],'product_cat');

                if (!is_wp_error($children) && is_array($children) && !empty($children)) {
                    $args['include'] = $children;
                    unset($args['parent']);
                } else {
                    $args = array();
                }
            }

            if (!empty($args)) {

                $terms = get_terms( 'product_cat', $args);

                if (!empty($terms) && !is_wp_error($terms)) {
                    
                    if (isset($args['include']) && !empty($args['include'])) {

                        $thisTerm = get_term_by('id',$_POST['term_id'],'product_cat');

                        if ( $thisTerm && $thisTerm->parent ) {
                            echo '<a data-id="' . esc_attr( $term_id ) . '" href="#" class="back">';
                        } else {
                            echo '<a href="#" class="back">';
                        }

                        echo esc_html__('Back', 'enovathemes-addons') . '</a>';

                    }
                    echo '<ul class="loop-categories">';
                        foreach ( $terms as $term ){

                            $link = get_term_link($term->term_id,'product_cat');
                            $id   = count( get_term_children( $term->term_id, 'product_cat' ) ) === 0 ? '' : 'data-id="'.esc_attr($term->term_id).'"';

                            echo '<li class="category-item"><a '.$id.' href="'.esc_url($link).'">';
                                $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                                if ($thumbnail_id) {
                                    echo '<div class="image-container">';
                                        echo mobex_enovathemes_build_post_media('thumbnail',$thumbnail_id,'product');
                                    echo '</div>';
                                }
                                echo '<h5>'.esc_html($term->name).'</h5>';
                            echo '</a></li>';
                        }
                    echo '</ul>';

                    if (isset($args['include']) && !empty($args['include'])) {

                        $parent_term = get_term_by('id',$_POST['term_id'],'product_cat');
                        if ($parent_term) {
                            echo '<a class="et-button button small" href="'.esc_url(get_term_link($parent_term->term_id,'product_cat')).'">'.esc_html__('View all','enovathemes-addons').'</a>';
                        }
                        
                    }
                }

            }

        }

        die();
    }
    add_action( 'wp_ajax_fetch_dashboard_categories', 'enovathemes_addons_dashboard_categories' );
    add_action( 'wp_ajax_nopriv_fetch_dashboard_categories', 'enovathemes_addons_dashboard_categories' );

/*  After import
/*-------------------*/

    function enovathemes_addons_ocdi_before_content_import( $selected_import ) {
        update_option('uploads_use_yearmonth_folders', false);

        if (class_exists('Woocommerce')) {
            $shop_page_id = get_option('woocommerce_shop_page_id');
            if ($shop_page_id) {
                wp_delete_post($shop_page_id);
            }

        }

        $hello_world_posts = get_posts(array('category' => 1, 'post_type' => 'post', 'numberposts' => 1));
        if ($hello_world_posts) {
            $hello_world_post_id = $hello_world_posts[0]->ID;
            wp_delete_post($hello_world_post_id, true);
        }

    }
    add_action( 'ocdi/before_content_import', 'enovathemes_addons_ocdi_before_content_import' );


    function enovathemes_addons_ocdi_after_import( $selected_import ) {

        global $wpdb;

        $old_url = 'https://enovathemes.com/mobex/';
        $new_url = esc_url(home_url('/'));

        $old_url_elementor = 'https:\/\/enovathemes.com\/mobex\/';
        $new_url_elementor = str_replace('/','\/',$new_url);

        $posts_table = $wpdb->prefix . "posts";
        $meta_table  = $wpdb->prefix . "postmeta";

        $sql_1 = $wpdb->prepare( "UPDATE {$posts_table} SET post_content  = REPLACE (post_content, %s, '{$new_url}') ",$old_url);
        $sql_2 = $wpdb->prepare( "UPDATE {$posts_table} SET guid  = REPLACE (guid, %s, '{$new_url}') ",$old_url);
        $sql_3 = $wpdb->prepare( "UPDATE {$meta_table} SET meta_value  = REPLACE (meta_value, %s, '{$new_url}') ",$old_url);
        $sql_4 = $wpdb->prepare( "UPDATE {$meta_table} SET meta_value  = REPLACE (meta_value, %s, '{$new_url_elementor}') ",$old_url_elementor);


        if (isset($old_url) && !empty($old_url) && $old_url != $new_url) {
            $wpdb->query($sql_1);
            $wpdb->query($sql_2);
            $wpdb->query($sql_3);
            $wpdb->query($sql_4);
        }

        // After import fix categories
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);

        if (!mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD)) {  die('Could not connect: ' . mysqli_error());  }
        if (!mysqli_select_db($conn,DB_NAME)) {  die('Could not connect: ' . mysqli_error());  }

        // Set elementor settings
        $opt = array('post','page','banner','footer','megamenu','header');
        update_option('elementor_cpt_support',$opt);
        update_option('elementor_disable_color_schemes','yes');
        update_option('elementor_disable_typography_schemes','yes');
        update_option('elementor_unfiltered_files_upload',1);

        $kit = get_option( 'elementor_active_kit' );
        if (isset($kit) && !empty($kit)) {
            $kit_value = 'a:25:{s:16:"site_description";s:38:"Auto Parts WordPress WooCommerce Theme";s:13:"system_colors";a:4:{i:0;a:3:{s:3:"_id";s:7:"primary";s:5:"title";s:7:"Primary";s:5:"color";s:7:"#034C8C";}i:1;a:3:{s:3:"_id";s:9:"secondary";s:5:"title";s:9:"Secondary";s:5:"color";s:7:"#F29F05";}i:2;a:3:{s:3:"_id";s:4:"text";s:5:"title";s:4:"Text";s:5:"color";s:7:"#444444";}i:3;a:3:{s:3:"_id";s:6:"accent";s:5:"title";s:6:"Accent";s:5:"color";s:7:"#BF3617";}}s:13:"custom_colors";a:0:{}s:17:"system_typography";a:4:{i:0;a:4:{s:3:"_id";s:7:"primary";s:5:"title";s:7:"Primary";s:21:"typography_typography";s:6:"custom";s:22:"typography_font_weight";s:3:"400";}i:1;a:4:{s:3:"_id";s:9:"secondary";s:5:"title";s:9:"Secondary";s:21:"typography_typography";s:6:"custom";s:22:"typography_font_weight";s:3:"400";}i:2;a:4:{s:3:"_id";s:4:"text";s:5:"title";s:4:"Text";s:21:"typography_typography";s:6:"custom";s:22:"typography_font_weight";s:3:"400";}i:3;a:4:{s:3:"_id";s:6:"accent";s:5:"title";s:6:"Accent";s:21:"typography_typography";s:6:"custom";s:22:"typography_font_weight";s:3:"400";}}s:17:"custom_typography";a:0:{}s:21:"default_generic_fonts";s:10:"Sans-serif";s:26:"body_typography_typography";s:6:"custom";s:33:"link_normal_typography_typography";s:6:"custom";s:24:"h2_typography_typography";s:6:"custom";s:28:"button_typography_typography";s:6:"custom";s:9:"site_name";s:44:"Mobex Auto Parts WordPress WooCommerce Theme";s:19:"page_title_selector";s:14:"h1.entry-title";s:15:"activeItemIndex";i:1;s:11:"viewport_md";i:768;s:11:"viewport_lg";i:1024;s:15:"container_width";a:3:{s:4:"unit";s:2:"px";s:4:"size";i:1320;s:5:"sizes";a:0:{}}s:21:"space_between_widgets";a:6:{s:6:"column";s:2:"24";s:3:"row";s:2:"24";s:8:"isLinked";b:1;s:4:"unit";s:2:"px";s:4:"size";i:24;s:5:"sizes";a:0:{}}s:15:"viewport_tablet";i:1023;s:21:"viewport_tablet_extra";i:1279;s:15:"viewport_laptop";i:1365;s:22:"container_width_laptop";a:3:{s:4:"unit";s:2:"px";s:4:"size";i:1240;s:5:"sizes";a:0:{}}s:28:"container_width_tablet_extra";a:3:{s:4:"unit";s:1:"%";s:4:"size";d:93.75;s:5:"sizes";a:0:{}}s:22:"container_width_tablet";a:3:{s:4:"unit";s:1:"%";s:4:"size";i:95;s:5:"sizes";a:0:{}}s:18:"active_breakpoints";a:4:{i:0;s:15:"viewport_mobile";i:1;s:15:"viewport_tablet";i:2;s:21:"viewport_tablet_extra";i:3;s:15:"viewport_laptop";}s:22:"container_width_mobile";a:3:{s:4:"unit";s:1:"%";s:4:"size";i:95;s:5:"sizes";a:0:{}}}';
            $kit_value = unserialize($kit_value);
            update_post_meta( $kit, '_elementor_page_settings',$kit_value);
        }

        // Set the homepage and blog page

        $home_query = new WP_Query(
            array(
                'post_type'              => 'page',
                'title'                  => 'Home',
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
            )
        );
         
        if ( ! empty( $home_query->post ) ) {
            update_option( 'page_on_front', $home_query->posts[0]->ID );
        }

        $post_query = new WP_Query(
            array(
                'post_type'              => 'page',
                'title'                  => 'Blog',
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
            )
        );
         
        if ( ! empty( $post_query->post ) ) {
            update_option( 'page_for_posts', $post_query->posts[0]->ID );
        }

        update_option( 'show_on_front', 'page' );

        if (class_exists('Alg_WC_Currency_Switcher')) {
            update_option( 'alg_currency_switcher_format', '%currency_symbol% %currency_code%' );
            update_option( 'alg_wc_currency_switcher_link_list_separator', '' );
        }

        if (class_exists('Woocommerce')) {
            $shop = get_page_by_path( 'shop' );
            if ($shop) {
                update_option( 'woocommerce_shop_page_id', $shop->ID );
            }
        }

        // Set default menu
        $header_menu = get_term_by('name', 'Header menu 1', 'nav_menu');
        $locations['header-menu'] = $header_menu->term_id;
        set_theme_mod( 'nav_menu_locations', $locations );

        if ( class_exists( 'RevSlider' ) ) {
            
            $slider_array = array(
                get_template_directory()."/demo/slider-1.zip",
                get_template_directory()."/demo/slider-2.zip",
                get_template_directory()."/demo/slider-3.zip",
                get_template_directory()."/demo/slider-4.zip",
            );

            $slider = new RevSlider();

            foreach($slider_array as $filepath){
                $slider->importSliderFromPost(true,true,$filepath);  
            }

        }

        // Delete transients
        delete_transient( 'enovathemes-product-categories' );
        delete_transient( 'enovathemes-attributes-filter' );
        delete_transient( 'dynamic-styles-cached' );
        delete_transient( 'enovathemes-banners' );
        delete_transient( 'enovathemes-megamenu' );
        delete_transient( 'enovathemes-megamenu-names' );
        delete_transient( 'enovathemes-headers' );
        delete_transient( 'enovathemes-footers' );
        delete_transient( 'enovathemes-header-list' );
        delete_transient( 'enovathemes-footer-list' );
        delete_transient( 'product-categories-hierarchy' );
        delete_transient( 'product-categories-raw' );
        delete_transient('enovathemes-product-filter');
        delete_transient( 'enovathemes-products-navigation-pagination');
        delete_transients_with_prefix( 'et_product_' );
        delete_transients_with_prefix( 'search_keyword_' );
        delete_transients_with_prefix( 'et_post_' );
        delete_transients_with_prefix( 'product-taxonomy-terms-list-' );

        // Vehicle transients
        delete_transient( 'vehicles-first-param' );
        delete_transients_with_prefix( 'vehicles-first-param-' );
        delete_transients_with_prefix( 'vin_decode_' );
        delete_transient( 'vehicles' );
        delete_transient( 'vehicle-list' );
        delete_transient( 'universal-products' );


        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%category%/%postname%/');
        $wp_rewrite->flush_rules();

        if ( class_exists( 'WooCommerce' ) ) {
            if ( function_exists( 'wc_regenerate_product_lookup_tables' ) ) {
                wc_regenerate_product_lookup_tables();
            }
            if ( function_exists( 'wc_delete_expired_transients' ) ) {
                wc_delete_expired_transients();
            }

            if ( function_exists( 'wc_delete_product_transients' ) && function_exists( 'wc_delete_shop_order_transients' ) ) {
                wc_delete_product_transients();
                wc_delete_shop_order_transients();
            }

            if ( function_exists( 'wc_update_term_count' ) ) {
                $taxonomy = 'product_cat';
                $terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
                foreach ( $terms as $term ) {
                    wc_update_term_count( $term->term_id, $taxonomy );
                }

                $woo_attributes = wc_get_attribute_taxonomies();
                if (!empty($woo_attributes) && !is_wp_error($woo_attributes)) {
                    foreach( $woo_attributes as $attribute) {

                        $attribute_terms = get_terms( array(
                            'taxonomy' => 'pa_'.$attribute->attribute_name,
                            'hide_empty' => false,
                        ));

                        foreach ( $attribute_terms as $term ) {
                            wc_update_term_count( $term->term_id, 'pa_'.$attribute->attribute_name );
                        }

                    }
                }

            }
        }

    }
    add_action( 'ocdi/after_import', 'enovathemes_addons_ocdi_after_import' );

/*  Actions/Filters
/*-------------------*/

    function enovathemes_addons_svg_mime_types($mimes) {
        $mimes['svg'] = 'image/svg+xml';
            return $mimes;
        }
    add_filter('upload_mimes', 'enovathemes_addons_svg_mime_types');

    function enovathemes_addons_execute_on_permalink_structure_changed_event($old_permalink_structure, $permalink_structure){
        // Delete transients
        delete_transient( 'enovathemes-product-categories' );
        delete_transient( 'enovathemes-attributes-filter' );
        delete_transient( 'dynamic-styles-cached' );
        delete_transient( 'enovathemes-banners' );
        delete_transient( 'enovathemes-megamenu' );
        delete_transient( 'enovathemes-megamenu-names' );
        delete_transient( 'enovathemes-headers' );
        delete_transient( 'enovathemes-footers' );
        delete_transient( 'enovathemes-header-list' );
        delete_transient( 'enovathemes-footer-list' );
        delete_transient( 'product-categories-hierarchy' );
        delete_transient( 'product-categories-raw' );
        delete_transients_with_prefix( 'et_product_' );
        delete_transients_with_prefix( 'search_keyword_' );
        delete_transients_with_prefix( 'et_post_' );
        delete_transients_with_prefix( 'product-taxonomy-terms-list-' );

        // Vehicle transients
        delete_transient( 'vehicles-first-param' );
        delete_transients_with_prefix( 'vehicles-first-param-' );
        delete_transients_with_prefix( 'vin_decode_' );
        delete_transient( 'vehicles' );
        delete_transient( 'vehicle-list' );
        delete_transient( 'universal-products' );

    }
    add_action( "permalink_structure_changed", "enovathemes_addons_execute_on_permalink_structure_changed_event" , 10, 2);

    function enovathemes_addons_customize_save_after(){
        delete_transient( 'dynamic-styles-cached' );
        delete_transient( 'enovathemes-banners' );
        delete_transient( 'enovathemes-megamenu' );
        delete_transient( 'enovathemes-megamenu-names' );
        delete_transient( 'enovathemes-headers' );
        delete_transient( 'enovathemes-footers' );

        // Vehicle transients
        delete_transient( 'vehicles-first-param' );
        delete_transients_with_prefix( 'vehicles-first-param-' );
        delete_transients_with_prefix( 'vin_decode_' );
        delete_transient( 'vehicles' );
        delete_transient( 'vehicle-list' );
        delete_transient( 'universal-products' );
    }
    add_action( 'customize_save_after', 'enovathemes_addons_customize_save_after' );


    if (!is_customize_preview() ) {
        add_filter( 'kirki_output_inline_styles', '__return_false' );
    }
    add_filter( 'kirki/config', function( $config = array() ) {
        $config['styles_priority'] = 10;
        return $config;
    } );

    add_filter('body_class', 'enovathemes_addons_general_body_classes');
    function enovathemes_addons_general_body_classes($classes) {

            $custom_class = array();
            $custom_class[] = "addon-active";

            $classes[] = implode(" ", $custom_class);
            return $classes;
    }

    add_action( 'init', 'enovathemes_addons_init' );
    function enovathemes_addons_init(){

        add_action( 'save_post', 'enovathemes_addons_save_elements_styles', 99, 3);
        function enovathemes_addons_save_elements_styles( $post_id )
        {

            if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

            $post_info = get_post($post_id);

            if (!is_wp_error($post_info) && is_object($post_info)) {

                $content   = $post_info->post_content;
                $post_type = $post_info->post_type;

                switch($post_type){
                    case "megamenu":
                        if (isset($_POST['enovathemes_addons_megamenu_width']) && $_POST['enovathemes_addons_megamenu_width'] == 100) {
                            update_post_meta($post_id, "enovathemes_addons_megamenus_position","left");
                            update_post_meta($post_id, "enovathemes_addons_megamenus_offset","");
                        }
                        delete_transient( 'enovathemes-megamenu' );
                        delete_transient( 'enovathemes-megamenu-names' );
                        delete_transient( 'enovathemes-headers' );
                        delete_transient( 'enovathemes-footers' );
                        delete_transients_with_prefix( 'et_product_' );
                        delete_transients_with_prefix( 'et_post_' );
                    break;
                    case "header":
                        if (isset($_POST['enovathemes_addons_header_type']) && $_POST['enovathemes_addons_header_type'] == 'sidebar') {
                            update_post_meta($post_id, "enovathemes_addons_transparent", "");
                            update_post_meta($post_id, "enovathemes_addons_sticky", "");
                            update_post_meta($post_id, "enovathemes_addons_shadow", "");
                        }
                        delete_transient( 'enovathemes-header-list' );
                        delete_transient( 'enovathemes-headers' );
                    break;
                    case "footer":
                        delete_transient( 'enovathemes-footer-list' );
                        delete_transient( 'enovathemes-footers' );
                    break;
                    case "product":
                        delete_transients_with_prefix( 'et_product_' );
                        delete_transients_with_prefix( 'product-taxonomy-terms-list-' );
                        delete_transients_with_prefix( 'search_keyword_' );
                        delete_transient( 'enovathemes-product-categories' );
                        delete_transient( 'enovathemes-attributes-filter' );
                        delete_transient('enovathemes-products-navigation-pagination');
                        

                        // Vehicle transients
                        delete_transient( 'vehicles-first-param' );
                        delete_transients_with_prefix( 'vehicles-first-param-' );
                        delete_transients_with_prefix( 'vin_decode_' );
                        delete_transient( 'vehicles' );
                        delete_transient( 'vehicle-list' );
                        delete_transient( 'universal-products' );

                        $universal = get_post_meta($post_id,'enovathemes_addons_universal',true);

                        if ($universal == 'on') {
                            wp_delete_object_term_relationships($post_id,'vehicles');
                        }

                    break;
                    case "post":
                        delete_transients_with_prefix( 'et_post_' );
                        delete_transients_with_prefix( 'et_product_' );
                    break;
                    case "page":
                        delete_transients_with_prefix( 'et_product_' );
                        delete_transients_with_prefix( 'et_post_' );
                    break;
                    case "banner":
                        delete_transient( 'enovathemes-banners' );
                        delete_transients_with_prefix( 'et_product_' );
                        delete_transients_with_prefix( 'et_post_' );
                    break;
                    case "elementor_library":
                        delete_transients_with_prefix( 'et_product_' );
                        delete_transients_with_prefix( 'et_post_' );
                        delete_transients_with_prefix( 'product-taxonomy-terms-list-' );
                        delete_transients_with_prefix( 'search_keyword_' );
                        delete_transient( 'enovathemes-banners' );
                        delete_transient( 'enovathemes-product-categories' );
                        delete_transient( 'enovathemes-attributes-filter' );
                        delete_transient( 'enovathemes-banners' );
                        delete_transient( 'enovathemes-footer-list' );
                        delete_transient( 'enovathemes-footers' );
                        delete_transient( 'enovathemes-header-list' );
                        delete_transient( 'enovathemes-headers' );
                        delete_transient( 'enovathemes-megamenu' );
                        delete_transient( 'enovathemes-megamenu-names' );
                    break;
                }

                delete_transient( 'dynamic-styles-cached' );
            }

        }
        
        add_action( 'wp_update_nav_menu', function() {
            delete_transient( 'dynamic-styles-cached' );
            delete_transient( 'enovathemes-megamenu' );
            delete_transient( 'enovathemes-megamenu-names' );
            delete_transient( 'enovathemes-headers' );
            delete_transient( 'enovathemes-footers' );
            delete_transient( 'enovathemes-header-list' );
            delete_transient( 'enovathemes-footer-list' );
        });

        if (class_exists("Woocommerce")) {
            add_action('woocommerce_after_cart_totals',function(){ ?>
                <a class="button medium checkout-button wc-forward" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                    <?php esc_html_e( 'Continue Shopping', 'enovathemes-addons' ); ?>
                </a>
            <?php });
        }

    }

    function enovathemes_addons_search_join( $join ){
       global $wpdb;
       $join .= " LEFT JOIN $wpdb->postmeta gm ON (" . 
       $wpdb->posts . ".ID = gm.post_id AND gm.meta_key='_sku')"; // change to your meta key if not woo

       return $join;
    }

    function enovathemes_addons_search_where( $where ){
       global $wpdb;
       $where = preg_replace(
         "/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
         "({$wpdb->posts}.post_title LIKE $1) OR (gm.meta_value LIKE $1)", $where );
       return $where;
    }
    /* grouping by id to make sure no dupes */
    function enovathemes_addons_search_groupby( $groupby ){
       global $wpdb;
       $mygroupby = "{$wpdb->posts}.ID";
       if( preg_match( "/$mygroupby/", $groupby )) {
         // grouping we need is already there
         return $groupby;
       }
       if( !strlen(trim($groupby))) {
          // groupby was empty, use ours
          return $mygroupby;
       }
       // wasn't empty, append ours
       return $groupby . ", " . $mygroupby;
    }

    add_action( 'pre_get_posts', 'enovathemes_addons_pre_get_post',99);
    function enovathemes_addons_pre_get_post( $query ) {

        $queried_object = $query->get_queried_object();
 
        if($query->is_main_query() && !is_admin() && ($query->is_post_type_archive( 'product' ) || (isset( $queried_object->taxonomy ) && ( $queried_object->taxonomy === 'product_cat' || $queried_object->taxonomy === 'product_tag' )))) {

            $product_number  = get_theme_mod('product_number');

            if (empty($product_number)) {
                $product_number =  get_option( 'posts_per_page' );
            }
            
            $data_shop  = (isset($_GET["data_shop"]) && !empty($_GET["data_shop"])) ? $_GET["data_shop"] : "default";
            if($data_shop == 'nosidebar' || $data_shop == 'filter'){
                $product_number = 24;
            }

            $query->set( 'posts_per_page', $product_number );

            $vehicle_params = apply_filters( 'vehicle_params','');

            $vehicle_attributes = [];

            foreach ($_GET as $key => $value) {

                $key = ($key == 'yr') ? 'year' : $key;

                if (in_array($key, $vehicle_params)) {
                    $vehicle_attributes[$key] = urldecode($value);
                }
            }

            $universal_products = enovathemes_addons_universal_products();

            if (isset($_GET['vin']) && !empty($_GET['vin'])) {
                $vehicle_attributes = enovathemes_addons_vin_decoder($_GET['vin']);
            } else {
                $vehicle_attributes = vehicle_set_from_cookies_if_empty($vehicle_attributes);
            }


            if ($vehicle_attributes && isset($vehicle_attributes['error']) && !empty($vehicle_attributes['error'])) {
                $query->set("p", -1);
            }elseif ($vehicle_attributes && !empty($vehicle_attributes)) {
                $vehicles = vehicle_filter_component($vehicle_attributes);

                $products = [];

                if ($vehicles && !empty($vehicles)) {

                    $products_with_vehicles = new WP_Query( array (
                        'post_type'     => 'product',
                        'posts_per_page'=> -1,
                        'tax_query'    => array(
                            'relation' => 'AND',
                            array(
                                'taxonomy'  => 'product_visibility',
                                'terms'     => array( 'exclude-from-catalog' ),
                                'field'     => 'name',
                                'operator'  => 'NOT IN'
                            ),
                            array(
                                "taxonomy" => "vehicles",
                                "field" => "term_id",
                                "terms" => $vehicles,
                                "operator" => "IN",
                            ),
                        )
                    ));

                    if ($products_with_vehicles->have_posts()){
                        while($products_with_vehicles->have_posts()) { $products_with_vehicles->the_post();
                            $products[] = get_the_ID();
                        }
                        wp_reset_postdata();
                        unset($_POST['universal']);
                    } else {
                        $_POST['universal'] = 1;
                    }

                    $universal_products = enovathemes_addons_universal_products();

                    if (!is_wp_error($universal_products)) {
                        foreach($universal_products as $product){
                            $products[] = $product;
                        }         
                    }

                    if (!empty($products)) {

                        $products = array_unique($products);
                        $products = array_filter($products);

                        $query->set("post__in",$products);
                    } else {
                        $query->set("p", -1);
                    }

                } else {


                    $universal_products = enovathemes_addons_universal_products();

                    if (!is_wp_error($universal_products)) {

                        $_POST['universal'] = 1;

                        foreach($universal_products as $product){
                            $products[] = $product;
                        }
                    }

                    if (!empty($products)) {
                        $products = array_unique($products);
                        $products = array_filter($products);
                        $query->set("post__in",$products);
                    } else {
                        $query->set("p", -1);
                    }

                }
            }

            if (isset($_GET["s"]) && !empty($_GET["s"]) && $query->is_search) {

                $mods                          = et_get_theme_mods();
                $product_ajax_search_threshold = $mods && isset($mods['product_ajax_search_threshold']) && !empty($mods['product_ajax_search_threshold']) ? $mods['product_ajax_search_threshold'] : 0.1;

                $is_wpml     = defined('ICL_SITEPRESS_VERSION');
                $is_polylang = function_exists('pll_current_language');

                if ($is_wpml) {
                    $current_language = apply_filters('wpml_current_language', null);
                } elseif($is_polylang){
                    $current_language = pll_current_language();
                } else {
                    $current_language = 'default';
                }

                /* Fuse
                -------*/

                    $fuse_active = false;

                    if (class_exists('Fuse')) {
                        $fuse_active = true;
                    } else {
                        $Fuse = WP_PLUGIN_DIR . '/enovathemes-addons/includes/vendor/autoload.php';
                        if (file_exists($Fuse)) {
                            require_once $Fuse;
                            $fuse_active = true;
                        }
                    }

                    $product_index = get_transient('et-woo-product-index');

                    if ($fuse_active && $product_index) {

                        $product_index = $product_index[$current_language];

                        $fuse_keys = et__get_search_in_keys($product_index);

                        $options = [
                            'keys' => $fuse_keys,
                            'threshold' => floatval($product_ajax_search_threshold),
                            'includeScore' => false,
                            'ignoreLocation' => true,
                            'useExtendedSearch' => true
                        ];

                        $fuse = new \Fuse\Fuse($product_index, $options);
                        $product_IDs = $fuse->search(urldecode($_GET["s"]));

                        if (!empty($product_IDs) && is_array($product_IDs)) {

                            $product_IDs = array_filter(array_map(function($product) {
                                return $product['item']['id'];
                            }, $product_IDs));

                            $product_IDs = array_unique($product_IDs,SORT_REGULAR);

                            $query->set( 's', '' );
                            $query->set("post__in",$product_IDs);

                        } else {
                            add_filter('posts_join', 'enovathemes_addons_search_join' );
                            add_filter('posts_where', 'enovathemes_addons_search_where' );
                            add_filter('posts_groupby', 'enovathemes_addons_search_groupby' );
                        }

                    } else {
                        add_filter('posts_join', 'enovathemes_addons_search_join' );
                        add_filter('posts_where', 'enovathemes_addons_search_where' );
                        add_filter('posts_groupby', 'enovathemes_addons_search_groupby' );
                    }
                
            
            }

        }

    }

    add_action('init',function(){

        function enovathemes_addons_disable_gutenberg_post($is_enabled, $post_type) {
            if ($post_type === 'post') return false;
            return $is_enabled;
        }
        // add_filter('use_block_editor_for_post_type', 'enovathemes_addons_disable_gutenberg_post', 10, 2);

        function enovathemes_addons_disable_gutenberg_page($is_enabled, $post_type) {
            if ($post_type === 'page') return false;
            return $is_enabled;
        }
        add_filter('use_block_editor_for_post_type', 'enovathemes_addons_disable_gutenberg_page', 10, 2);

        function enovathemes_addons_disable_gutenberg_product($is_enabled, $post_type) {
            if ($post_type === 'product') return false;
            return $is_enabled;
        }
         add_filter('use_block_editor_for_post_type', 'enovathemes_addons_disable_gutenberg_product', 10, 2);

        function enovathemes_addons_disable_gutenberg_banner($is_enabled, $post_type) {
            if ($post_type === 'banner') return false;
            return $is_enabled;
        }
        add_filter('use_block_editor_for_post_type', 'enovathemes_addons_disable_gutenberg_banner', 10, 2);

        function enovathemes_addons_disable_gutenberg_megamenu($is_enabled, $post_type) {
            if ($post_type === 'megamenu') return false;
            return $is_enabled;
        }
        add_filter('use_block_editor_for_post_type', 'enovathemes_addons_disable_gutenberg_megamenu', 10, 2);

        add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
        add_filter( 'use_widgets_block_editor', '__return_false' );

    });

    function wp_insert_attachment_from_url( $url, $file, $prefix ) {

        if ( ! class_exists( 'WP_Http' ) ) {
            require_once ABSPATH . WPINC . '/class-http.php';
        }

        $http     = new WP_Http();
        $response = $http->request( $url );
        if ( 200 !== $response['response']['code'] ) {
            return false;
        }

        $file_path        = $file;
        $file_name        = ($prefix) ? $prefix : basename( $file_path );
        $file_type        = wp_check_filetype( $file_name, null );
        $attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
        $wp_upload_dir    = wp_upload_dir();

        $post_info = array(
            'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
            'post_mime_type' => $file_type['type'],
            'post_title'     => $attachment_title,
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        // Create the attachment.
        $attach_id = wp_insert_attachment( $post_info, $file_path, false );

        // Include image.php.
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Generate the attachment metadata.
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

        // Assign metadata to attachment.
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;

    }

    function enovathemes_addons_et_elementor_ajax(){

        if (isset($_POST["post_id"]) && !empty($_POST["post_id"])) {

            $post_id   = $_POST["post_id"];
            $post_info = get_post($post_id);

            if (!is_wp_error($post_info) && is_object($post_info)) {

                $post_type = $post_info->post_type;

                switch($post_type){
                    case "megamenu":
                        delete_transient( 'enovathemes-megamenu' );
                        delete_transient( 'enovathemes-megamenu-names' );
                        delete_transient( 'enovathemes-headers' );
                        delete_transient( 'enovathemes-footers' );
                        delete_transients_with_prefix( 'et_product_' );
                        delete_transients_with_prefix( 'et_post_' );
                    break;
                    case "header":
                        delete_transient( 'enovathemes-header-list' );
                        delete_transient( 'enovathemes-headers' );
                    break;
                    case "footer":
                        delete_transient( 'enovathemes-footer-list' );
                        delete_transient( 'enovathemes-footers' );
                    break;
                    case "product":
                        delete_transients_with_prefix( 'et_product_' );
                        delete_transients_with_prefix( 'search_keyword_' );
                        delete_transients_with_prefix( 'product-taxonomy-terms-list-' );
                        delete_transient( 'enovathemes-product-categories' );
                        delete_transient( 'enovathemes-attributes-filter' );
                        delete_transient('enovathemes-products-navigation-pagination');

                        // Vehicle transients
                        delete_transient( 'vehicles-first-param' );
                        delete_transients_with_prefix( 'vehicles-first-param-' );
                        delete_transients_with_prefix( 'vin_decode_' );
                        delete_transient( 'vehicles' );
                        delete_transient( 'vehicle-list' );
                        delete_transient( 'universal-products' );

                    break;
                    case "post":
                        delete_transients_with_prefix( 'et_post_' );
                        delete_transients_with_prefix( 'et_product_' );
                    break;
                    case "banner":
                        delete_transient( 'enovathemes-banners' );
                    break;
                    case "page":
                        delete_transients_with_prefix( 'et_post_' );
                        delete_transients_with_prefix( 'et_product_' );
                    break;
                    case "elementor_library":
                        delete_transients_with_prefix( 'et_post_' );
                        delete_transients_with_prefix( 'et_product_' );
                        delete_transients_with_prefix( 'product-taxonomy-terms-list-' );
                        delete_transients_with_prefix( 'search_keyword_' );
                        delete_transient( 'enovathemes-banners' );
                        delete_transient( 'enovathemes-product-categories' );
                        delete_transient( 'enovathemes-attributes-filter' );
                        delete_transient( 'enovathemes-banners' );
                        delete_transient( 'enovathemes-footer-list' );
                        delete_transient( 'enovathemes-footers' );
                        delete_transient( 'enovathemes-header-list' );
                        delete_transient( 'enovathemes-headers' );
                        delete_transient( 'enovathemes-megamenu' );
                        delete_transient( 'enovathemes-megamenu-names' );
                    break;
                }

                delete_transient( 'dynamic-styles-cached' );

            }

        }

        die();

    }

    add_action('wp_ajax_nopriv_et_elementor_ajax', 'enovathemes_addons_et_elementor_ajax');
    add_action('wp_ajax_et_elementor_ajax', 'enovathemes_addons_et_elementor_ajax');

    add_filter( 'mime_types', 'enovathemes_addons_mime_types' );
    function enovathemes_addons_mime_types( $existing_mimes ) {
        // Add csv to the list of allowed mime types
        $existing_mimes['csv'] = 'text/csv';

        return $existing_mimes;
    }

/*  Dokan
/*-------------------*/

    function enovathemes_addons_dokan_display_vehicle_fields() { ?>

        <div class="dokan-product-vehicle-assign dokan-edit-row">
            
            <div class="dokan-section-heading" data-togglehandler="dokan_product_inventory">
                <h2><i class="fas fa-car" aria-hidden="true"></i> <?php echo esc_html__("Products vehicles","enovathemes-addons"); ?></h2>
                <a href="#" class="dokan-section-toggle">
                    <i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i>
                </a>
                <div class="dokan-clearfix"></div>
            </div>

            <div class="dokan-section-content">

                <?php
                        $product_id = $_GET['product_id'];
                                                                
                        if(!empty($product_id) && class_exists( 'WooCommerce' ) &&  wc_get_product( $product_id )){ ?>
                            <div id="enovathemes_addons_products_vehicles_metabox">
                                <div class="inside"></div>
                            </div>
                        <?php } else {
                            echo '<div class="vehicle-product-inactive">'.esc_html__("To assign vehicles to a product, you must first save the product.","enovathemes-addons").'</div>';
                        }
                ?>
                                
            </div><!-- .dokan-side-right -->
        </div>

    <?php }

    add_action( 'dokan_product_edit_after_main', 'enovathemes_addons_dokan_display_vehicle_fields' );

/*  Mailchimp
/*-------------------*/

    function enovathemes_addons_mailchimp_subscribe(){

        global $post;
        if (! isset( $_POST['id'] )) {
            exit;
        } else {

            $nonce = 'et_mailchimp_nonce_'.$_POST['id'];

            if ( ! isset( $_POST[$nonce] ) || !wp_verify_nonce( $_POST[$nonce], 'et_mailchimp_action' )) {
               echo esc_html__("Sorry, your nonce did not verify.", "enovathemes-addons");
               exit;
            } else {

                $list    = strip_tags(trim($_POST["list"]));

                if (!empty($list)) {

                    $email   = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
                    $fname   = strip_tags(trim($_POST["fname"]));
                    $api_key = get_theme_mod('mailchimp_key');

                    $mailchimp = new MailchimpMarketing\ApiClient();

                    $mailchimp->setConfig([
                      'apiKey' => $api_key,
                      'server' => explode('-', $api_key)[1]
                    ]);

                    $list_member_info = [
                        'email_address' => $email,
                        'status' => 'subscribed',
                    ];

                    if (!empty($fname)) {
                        $list_member_info['merge_fields'] = [
                            'FNAME' => $fname, // Add the first name here
                        ];
                    }

                    try {
                        $response = $mailchimp->lists->addListMember($list, $list_member_info);
                        echo "Successfully subscribed!";
                    } catch (Exception $e) {
                        echo "Error: " . $e->getMessage();
                    }

                }

                die;
            }
        }
    }

    add_action('admin_post_nopriv_et_mailchimp', 'enovathemes_addons_mailchimp_subscribe');
    add_action('admin_post_et_mailchimp', 'enovathemes_addons_mailchimp_subscribe');

/*  Post social share
/*-------------------*/

    function enovathemes_addons_post_social_share($class){
        $url = wp_get_attachment_url( get_post_thumbnail_id(get_the_ID()) );
        $output = '<div id="post-social-share" class="post-social-share '.esc_attr($class).' et-social-links">';
            $output .= '<div class="social-links et-social-links styling-original-true">';
                $output .= '<a title="'.esc_html__("Share on Facebook", 'enovathemes-addons').'" class="post-facebook-share facebook" target="_blank" href="//facebook.com/sharer.php?u='.urlencode(get_the_permalink(get_the_ID())).'"></a>';
                $output .= '<a title="'.esc_html__("Tweet this!", 'enovathemes-addons').'" class="post-twitter-share twitter" target="_blank" href="//twitter.com/intent/tweet?text='.urlencode(get_the_title(get_the_ID()).' - '.get_the_permalink(get_the_ID())).'"></a>';
                $output .= '<a title="'.esc_html__("Share on Pinterest", 'enovathemes-addons').'" class="post-pinterest-share pinterest" target="_blank" href="//pinterest.com/pin/create/button/?url='.urlencode(get_the_permalink(get_the_ID())).'&media='.urlencode(esc_url($url)).'&description='.rawurlencode(get_the_title(get_the_ID())).'"></a>';
                $output .= '<a title="'.esc_html__("Share on LinkedIn", 'enovathemes-addons').'" class="post-linkedin-share linkedin" target="_blank" href="//www.linkedin.com/shareArticle?mini=true&url='.urlencode(get_the_permalink(get_the_ID())).'&title='.rawurlencode(get_the_title(get_the_ID())).'"></a>';
                $output .= '<a title="'.esc_html__("Share on Whatsapp", 'enovathemes-addons').'" class="whatsapp post-whatsapp-share" target="_blank" href="whatsapp://send?text='.urlencode(get_the_permalink(get_the_ID())).'"></a>';
                $output .= '<a title="'.esc_html__("Share on Viber", 'enovathemes-addons').'" class="viber post-viber-share" target="_blank" href="viber://forward?text='.urlencode(get_the_permalink(get_the_ID())).'"></a>';
                $output .= '<a title="'.esc_html__("Share on Telegram", 'enovathemes-addons').'" class="telegram post-telegram-share" target="_blank" href="tg://msg_url?url='.urlencode(get_the_permalink(get_the_ID())).'&text='.rawurlencode(get_the_title(get_the_ID())).'"></a>';
            $output .= '</div>';
            $output .= '<div class="social-share"></div>';
        $output .= '</div>';
        return $output;
    }

    add_action('wp_head', 'enovathemes_addons_open_graph_tags');
    function enovathemes_addons_open_graph_tags(){ ?>
        <?php

        if (defined( 'WPSEO_PATH' )) {
            return;
        }

        global $post;

        $sitename    = get_bloginfo('name');
        $image       = wp_get_attachment_image_src( get_post_thumbnail_id(get_the_ID()),"full");
        $url         = get_the_permalink(get_the_ID());
        $title       = get_the_title(get_the_ID());
        $description = (has_excerpt(get_the_ID())) ? get_the_excerpt(get_the_ID()) : '';

        ?>
        <?php if ($title): ?>
            <meta property="og:site_name" content="<?php echo esc_attr($sitename); ?>" />
            <meta name="twitter:title" content="<?php echo esc_attr($sitename); ?>">
        <?php endif ?>
        <?php if ($url): ?>
            <meta property="og:url" content="<?php echo esc_url($url); ?>" />
            <meta property="og:type" content="article" />
        <?php endif ?>
        <?php if ($title): ?>
            <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
        <?php endif ?>
        <?php if ($description): ?>
            <meta property="og:description" content="<?php echo esc_attr($description); ?>" />
            <meta name="twitter:description" content="<?php echo esc_attr($description); ?>">
        <?php endif ?>
        <?php if ($image): ?>
            <meta property="og:image" content="<?php echo esc_url($image[0]); ?>" />
            <meta property="og:image:width" content="<?php echo esc_attr($image[1]); ?>" />
            <meta property="og:image:height" content="<?php echo esc_attr($image[2]); ?>" />
            <meta name="twitter:image" content="<?php echo esc_url($image[0]); ?>">
            <meta name="twitter:card" content="summary_large_image">
        <?php endif ?>

    <?php }

/* Social icons
/*-------------------*/

    function enovathemes_addons_social_icons($dir) {

        if ( false === ( $social = get_transient( 'enovathemes-social-icons' ) ) ) {

            $social = array_diff(scandir($dir), array('..', '.'));

            $social_array = array();

            foreach ($social as $icon) {
                array_push($social_array,basename($icon,'.svg'));
            }

            $social = $social_array;

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $social ) ) {
                $social = base64_encode(serialize( $social ));
                set_transient( 'enovathemes-social-icons', $social, apply_filters( 'null_social_cache_time', 0 ) );
            }
        }

        if ( ! empty( $social ) ) {

            return unserialize(base64_decode($social));

        } else {

            return new WP_Error( 'no_icons', esc_html__( 'No icons.', 'enovathemes-addons' ) );

        }
    }

/*  Inline image placeholder
/*-------------------*/

    function enovathemes_addons_inline_image_placeholder($id,$thumb_size = 'full',$class = '',$post_type ='post'){

        $placeholder  = !empty(get_theme_mod('placeholder')) ? false : true;

        $output = '';

        $thumbnail_id  = ($id) ? $id: get_post_thumbnail_id( get_the_ID() );
        $image         = wp_get_attachment_image_src($thumbnail_id,$thumb_size);

        if ($image) {

            $image_src     = $image[0];
            $image_width   = intval($image[1]);
            $image_height  = intval($image[2]);

            $thumbnail_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true); 
            $image_caption = get_the_post_thumbnail_caption($image);
            $image_alt     = (empty($image_caption)) ? ((empty($thumbnail_alt)) ? get_bloginfo('name') : $thumbnail_alt) : $image_caption;
            
            $cl = array('lazy-inline-image');

            if(!empty($class)){
                $cl[]= $class;  
            }

            if ($placeholder) {
                $lazy_image = wp_get_attachment_image_src($thumbnail_id,'lazy_img');
                $output .= '<img class="lazy" data-src="'.esc_url($image_src).'" src="'.esc_url($lazy_image[0]).'" width="'.esc_attr($image_width).'" height="'.esc_attr($image_height).'" alt="'.esc_attr($image_alt).'" />';
            } else {
                $output .= '<img '.implode(' ',$attributes).' src="'.esc_url($image_src).'" width="'.esc_attr($image_width).'" height="'.esc_attr($image_height).'" alt="'.esc_attr($image_alt).'" />';
            }

        } elseif($post_type == 'product') {
            $output .= wc_placeholder_img( $thumb_size);
        }

        if (!empty($output)) {
            return $output;
        }
        
    }

/*  Breadcrumbs
/*-------------------*/

    function et__get_custom_page_title($page_option, $default_title) {
        $page_id = get_option($page_option);
        return $page_id ? get_the_title($page_id) : esc_html__($default_title, 'bigxon');
    }

    function enovathemes_addons_breadcrumbs() {

        
        if (!is_front_page()) {

            $raquo = '<span class="arrow"></span>';

            echo '<nav class="et-breadcrumbs"><a href="' . esc_url(home_url()) . '">' . esc_html__('Home', 'enovathemes-addons') . '</a> '.$raquo.' ';
            
            if (is_archive() && !is_tax() && !is_category() && !is_tag()) {
                echo esc_html(post_type_archive_title('', false));
            } elseif(is_home()){
                $label = et__get_custom_page_title('page_for_posts', 'Blog');
                echo esc_html($label);
            } elseif (is_single()) {

                $post_type = get_post_type();
                $post_type_obj = get_post_type_object($post_type);

                switch ($post_type) {
                    case 'post':
                        $blog_page_id = get_option( 'page_for_posts' );
                        // Check if the blog page is set
                        if ( $blog_page_id ) {
                            $blog_page_title = get_the_title( $blog_page_id );
                        } else {
                            $blog_page_title = esc_html__("Blog","enovathemes-addons"); // Default title if no page is set
                        }
                        $label = $blog_page_title;
                        break;
                    case 'product':
                        $shop_page_id = get_option( 'woocommerce_shop_page_id' );

                        // Check if the Shop page is set
                        if ( $shop_page_id ) {
                            $shop_page_title = get_the_title( $shop_page_id );
                        } else {
                            $shop_page_title = esc_html__("Shop","enovathemes-addons"); // Default title if no Shop page is set
                        }
                        $label = $shop_page_title;
                        break;
                    default:
                        $label = esc_html__($post_type_obj->labels->singular_name, 'enovathemes-addons');
                        break;
                }

                echo '<a href="' . esc_url(get_post_type_archive_link($post_type)) . '">' . $label . '</a> '.$raquo.' ';

                $taxonomies = get_object_taxonomies($post_type, 'objects');
                foreach ($taxonomies as $taxonomy) {
                    if ($taxonomy->hierarchical) {
                        $terms = get_the_terms(get_the_ID(), $taxonomy->name);
                        if ($terms) {
                            $term = current($terms);
                            $term_links = [];
                            while ($term->parent) {
                                array_unshift($term_links, '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>');
                                $term = get_term($term->parent, $taxonomy->name);
                            }
                            array_unshift($term_links, '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>');
                            echo implode(' '.$raquo.' ', $term_links) . ' '.$raquo.' ';
                            break;
                        }
                    }
                }
                echo '<span>' . esc_html(get_the_title()) . '</span>';
            } elseif (is_page()) {
                global $post;
                $ancestors = get_post_ancestors($post);
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $ancestor) {
                    echo '<a href="' . esc_url(get_permalink($ancestor)) . '">' . esc_html(get_the_title($ancestor)) . '</a> '.$raquo.' ';
                }
                echo '<span>' . esc_html(get_the_title()) . '</span>';
            } elseif (is_category() || is_tax()) {

                if (is_tax('product_cat') || is_tax('product_tag')) {

                    $label = et__get_custom_page_title('woocommerce_shop_page_id', 'Shop');

                    echo '<a href="' . esc_url(get_post_type_archive_link('product')) . '">' . $label . '</a> ' . $raquo . ' ';

                } elseif (is_category() || is_tag()) {

                    $label = et__get_custom_page_title('page_for_posts', 'Blog');

                    echo '<a href="' . esc_url(get_post_type_archive_link('post')) . '">' . $label . '</a> '.$raquo.' ';

                } else {
                    $post_type = get_post_type();
                    if ($post_type) {
                        $post_type_obj = get_post_type_object($post_type);
                        switch ($post_type) {
                            case 'post':
                                $label = et__get_custom_page_title('page_for_posts', 'Blog');
                                break;
                            case 'product':
                                $label = et__get_custom_page_title('woocommerce_shop_page_id', 'Shop');
                                break;
                            default:
                                $label = esc_html__($post_type_obj->labels->singular_name, 'enovathemes-addons');
                                break;
                        }
                        echo '<a href="' . esc_url(get_post_type_archive_link($post_type)) . '">' . $label . '</a> '.$raquo.' ';
                    }
                }

                $term = get_queried_object();

                if ($term) {
                    if ($term->parent) {
                        $parent_terms = [];
                        while ($term->parent) {
                            $parent_term = get_term($term->parent, $term->taxonomy);
                            $parent_terms[] = '<a href="' . esc_url(get_term_link($parent_term)) . '">' . esc_html($parent_term->name) . '</a>';
                            $term = $parent_term;
                        }
                        echo implode(' '.$raquo.' ', array_reverse($parent_terms)) . ' '.$raquo.' ';
                    }
                    echo '<span>' . esc_html(single_term_title('', false)) . '</span>';
                }
            } elseif (is_search()) {
                echo '<span>' . esc_html__('Search results for:', 'enovathemes-addons') . ' "' . esc_html(get_search_query()) . '"</span>';
            } elseif (is_404()) {
                echo '<span>' . esc_html__('404 Not Found', 'enovathemes-addons') . '</span>';
            }

            echo '</nav>';
        }

    }

/*  Minify CSS
/*-------------------*/

    function enovathemes_addons_minify_css($css) {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = str_replace(': ', ':', $css);
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        return $css;
    }

/*  Get the widget
/*-------------------*/

    if( !function_exists('enovathemes_addons_get_the_widget') ){
  
        function enovathemes_addons_get_the_widget( $widget, $instance = '', $args = '' ){
            ob_start();
            the_widget($widget, $instance, $args);
            return ob_get_clean();
        }
        
    }

/*  Theme colors
/*-------------------*/

    function ed_theme_color($code = 'main') {
        $main_color = (isset($main_color) && !empty($main_color)) ? $main_color : '#034c8c';
        $headings_typography = (isset($headings_typography) && !empty($headings_typography) && $headings_typography['color']) ? $headings_typography['color'] : '#034c8c';
        return ($code == 'main') ? $main_color : $headings_typography;
    }

/*  Hex to rgba
/*-------------------*/

    function enovathemes_addons_hex_to_rgba($hex, $o) {
        $hex = (string) $hex;
        $hex = str_replace("#", "", $hex);
        $hex = array_map('hexdec', str_split($hex, 2));
        return 'rgba('.implode(",", $hex).','.$o.')';
    }

/*  Hex to rgb shade
/*-------------------*/

    function enovathemes_addons_hex_to_rgb_shade($hex, $o) {
        $hex = (string) $hex;
        $hex = str_replace("#", "", $hex);
        $hex = array_map('hexdec', str_split($hex, 2));
        $hex[0] -= $o;
        $hex[1] -= $o;
        $hex[2] -= $o;
        return 'rgb('.implode(",", $hex).')';
    }

/*  Brightness detection
/*-------------------*/

    function enovathemes_addons_brightness($hex) {
        $hex = (string) $hex;
        $hex = str_replace("#", "", $hex);
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $output = 'dark';

        if($r + $g + $b > 382){
            $output = 'light';
        }else{
            $output = 'dark';
        }

        return $output;
    }

/*  Woocommerce
/*-------------------*/

    if ( ! function_exists( 'woocommerce_content' ) ) {

        function woocommerce_content() {

            $product_gap = "false";

            $show = (isset($_GET['ajax']) && $_GET['ajax'] == "true")? false : true;

            if ( is_singular( 'product' ) ) {

                while ( have_posts() ) :
                    the_post();
                    wc_get_template_part( 'content', 'single-product' );
                endwhile;

            } else {
                ?>

                <?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>

                    <h1 class="page-title"><?php woocommerce_page_title(); ?></h1>

                <?php endif; ?>

                <?php do_action( 'woocommerce_archive_description' ); ?>

                <?php if ( have_posts() ) : ?>

                    <?php do_action( 'woocommerce_before_shop_loop' ); ?>

                    <?php woocommerce_product_loop_start(); ?>

                    <?php if ($show): ?>
                        
                        <?php if ( wc_get_loop_prop( 'total' ) ) : ?>
                            <?php while ( have_posts() ) : ?>
                                <?php the_post(); ?>
                                <?php include(ENOVATHEMES_ADDONS.'woocommerce/content-product.php'); ?>
                            <?php endwhile; ?>
                        <?php endif; ?>

                    <?php endif ?>

                    <?php woocommerce_product_loop_end(); ?>

                    <?php do_action( 'woocommerce_after_shop_loop' ); ?>

                <?php endif;

            }
        }
    }

    add_filter('woocommerce_account_menu_items', 'enovathemes_addons_remove_downloads_myaccount', 999);
    function enovathemes_addons_remove_downloads_myaccount($items){
        unset($items['downloads']);
        return $items;
    }

    add_action( 'woocommerce_before_account_navigation',function(){
        echo '<div class="dashboard-mobile-toggle">'.esc_html__("Account navigation","enovathemes-addons").'</div>';
    });


    add_filter( 'woocommerce_account_menu_items', 'enovathemes_addons_myaccount_wishlist_endpoints' );
    function enovathemes_addons_myaccount_wishlist_endpoints( $items ) {
       $save_for_later = array( 'wishlist' => esc_html__('Wishlist','enovathemes-addons') ); // SAVE TAB
       $items = array_merge( array_slice( $items, 0, 1 ), $save_for_later, array_slice( $items, 1 ) ); // PLACE TAB AFTER POSITION 2
       return $items;
    }

    add_filter( 'woocommerce_account_menu_items', 'enovathemes_addons_myaccount_history_endpoints' );
    function enovathemes_addons_myaccount_history_endpoints( $items ) {
       $save_for_later = array( 'history' => esc_html__('History','enovathemes-addons') ); // SAVE TAB
       $items = array_merge( array_slice( $items, 0, 3 ), $save_for_later, array_slice( $items, 3 ) ); // PLACE TAB AFTER POSITION 2
       return $items;
    }

    add_filter( 'woocommerce_account_menu_items', 'enovathemes_addons_myaccount_garage_endpoints' );
    function enovathemes_addons_myaccount_garage_endpoints( $items ) {
       $save_for_later = array( 'garage' => esc_html__('My garage','enovathemes-addons') ); // SAVE TAB
       $items = array_merge( array_slice( $items, 0, 7 ), $save_for_later, array_slice( $items, 7 ) ); // PLACE TAB AFTER POSITION 2
       return $items;
    }

    add_action( 'init', 'enovathemes_addons_myaccount_garage_endpoints_rewrite' );
    function enovathemes_addons_myaccount_garage_endpoints_rewrite() {
        add_rewrite_endpoint( 'garage', EP_PAGES );
    }

    add_action( 'init', 'enovathemes_addons_myaccount_wishlist_endpoints_rewrite' );
    function enovathemes_addons_myaccount_wishlist_endpoints_rewrite() {
        add_rewrite_endpoint( 'wishlist', EP_PAGES );
    }

    add_action( 'init', 'enovathemes_addons_myaccount_history_endpoints_rewrite' );
    function enovathemes_addons_myaccount_history_endpoints_rewrite() {
        add_rewrite_endpoint( 'history', EP_PAGES );
    }

    add_action( 'woocommerce_account_garage_endpoint', 'enovathemes_addons_myaccount_garage_endpoints_content' );
    function enovathemes_addons_myaccount_garage_endpoints_content(){

        $vehicle_params = apply_filters( 'vehicle_params','');

        if ($vehicle_params) {

            wp_enqueue_script('widget-user-vehicle-filter');

            $filter_atts = array();

            $vin_user_vehicles = (get_theme_mod('vin_user_vehicles') != null && !empty(get_theme_mod('vin_user_vehicles'))) ? true : false;

            foreach ( $vehicle_params as $attribute ) {
                $filter_atts[] = array('attr'=>$attribute,'label'=>ucfirst($attribute));
            }

            if (!empty($filter_atts)) {

                $filter_instance = array(
                    'atts'      => json_encode($filter_atts),
                    'vin'       => 'off',
                    'columns'   => 1,
                );

                if ($vin_user_vehicles) {
                    $filter_instance['vin'] = 'on';
                }

                $filter_args = array(
                    'before_title'  => '<h5 class="widget_title">',
                    'after_title'   => '</h5>',
                );

                the_widget( 'Enovathemes_Addons_WP_User_Vehicle_Filter', $filter_instance,$filter_args);
            }

        }

    }

    add_action( 'woocommerce_account_wishlist_endpoint', 'enovathemes_addons_myaccount_wishlist_endpoints_content' );
    function enovathemes_addons_myaccount_wishlist_endpoints_content(){
        echo do_shortcode('[wishlist]');
    }

    add_action( 'woocommerce_account_history_endpoint', 'enovathemes_addons_myaccount_history_endpoints_content' );
    function enovathemes_addons_myaccount_history_endpoints_content(){
        if (isset($_COOKIE["woocommerce_recently_viewed"]) && !empty($_COOKIE["woocommerce_recently_viewed"])) {
            echo do_shortcode('[et_products ids="'.str_replace('|', ',', $_COOKIE["woocommerce_recently_viewed"]).'" type="custom" columns="5" columns_tab_land="3" columns_tab_port="2" ajax="true"]');
        } else {
            echo esc_html__("No products found","enovathemes-addons");
        }
    }

    add_action('wp_footer',function(){
        echo '<div class="my-account-nav-wrapper"><span class="dashboard-mobile-toggle-off"></span><nav class="woocommerce-MyAccount-navigation"></nav></div>';
    });

    add_action('init',function(){

        $my_account_vehicles = (get_theme_mod('my_account_vehicles') != null && !empty(get_theme_mod('my_account_vehicles'))) ? get_theme_mod('my_account_vehicles') : false;

        if ($my_account_vehicles == false) {
            remove_filter( 'woocommerce_account_menu_items', 'enovathemes_addons_myaccount_garage_endpoints' );
            remove_action( 'init', 'enovathemes_addons_myaccount_garage_endpoints_rewrite' );
            remove_action('woocommerce_account_garage_endpoint','enovathemes_addons_myaccount_garage_endpoints_content');
        }


        $product_wishlist = (get_theme_mod('product_wishlist') != null && !empty(get_theme_mod('product_wishlist'))) ? get_theme_mod('product_wishlist') : false;

        if ($product_wishlist == false) {
            remove_filter( 'woocommerce_account_menu_items', 'enovathemes_addons_myaccount_wishlist_endpoints' );
            remove_action( 'init', 'enovathemes_addons_myaccount_wishlist_endpoints_rewrite' );
            remove_action('woocommerce_account_wishlist_endpoint','enovathemes_addons_myaccount_wishlist_endpoints_content');
        }

        if (class_exists('Woocommerce')) {
            add_action( 'woocommerce_before_order_notes', 'enovathemes_addons_vin_checkout_field' );
            function enovathemes_addons_vin_checkout_field( $checkout ) { 
               $current_user = wp_get_current_user();
               $saved_vin = $current_user->vin;
               woocommerce_form_field( 'vin', array(        
                  'type' => 'text',        
                  'class' => array( 'form-row-wide' ),        
                  'label' => esc_html__('VIN','enovathemes-addons'),        
                  'placeholder' => esc_html__('Example: 4T4BE46K79R107189','enovathemes-addons'),        
                  'required' => false,        
                  'default' => $saved_vin,        
               ), $checkout->get_value( 'vin' ) ); 
            }

            add_action( 'woocommerce_checkout_update_order_meta', 'enovathemes_addons_save_vin_checkout_field' );
            function enovathemes_addons_save_vin_checkout_field( $order_id ) { 
                if ( $_POST['vin'] ) update_post_meta( $order_id, '_vin', esc_attr( $_POST['vin'] ) );
            }
             
            add_action( 'woocommerce_thankyou', 'enovathemes_addons_show_vin_checkout_field_thankyou' );
            function enovathemes_addons_show_vin_checkout_field_thankyou( $order_id ) {    
               if ( get_post_meta( $order_id, '_vin', true ) ) echo '<p><strong>'.esc_html__('VIN','enovathemes-addons').':</strong> ' . get_post_meta( $order_id, '_vin', true ) . '</p>';
            }

            add_action( 'woocommerce_view_order', 'enovathemes_addons_show_vin_view_order' );
            function enovathemes_addons_show_vin_view_order( $order_id ) {    
               if ( get_post_meta( $order_id, '_vin', true ) ) echo '<p><strong>'.esc_html__('VIN','enovathemes-addons').':</strong> ' . get_post_meta( $order_id, '_vin', true ) . '</p>';
            }
              
            add_action( 'woocommerce_admin_order_data_after_billing_address', 'enovathemes_addons_show_vin_checkout_field_order' );
            function enovathemes_addons_show_vin_checkout_field_order( $order ) {    
               $order_id = $order->get_id();
               if ( get_post_meta( $order_id, '_vin', true ) ) echo '<p><strong>'.esc_html__('VIN','enovathemes-addons').':</strong> ' . get_post_meta( $order_id, '_vin', true ) . '</p>';
            }
             
            add_action( 'woocommerce_email_after_order_table', 'enovathemes_addons_show_vin_checkout_field_emails', 20, 4 );
            function enovathemes_addons_show_vin_checkout_field_emails( $order, $sent_to_admin, $plain_text, $email ) {
                if ( get_post_meta( $order->get_id(), '_vin', true ) ) echo '<p><strong>'.esc_html__('VIN','enovathemes-addons').':</strong> ' . get_post_meta( $order->get_id(), '_vin', true ) . '</p>';
            }
        }

    });

    function enovathemes_addons_my_account_links($my_account_link = ''){


        if (empty($my_account_link)) {
            $my_account_link   = get_permalink(get_option('woocommerce_myaccount_page_id') );

        }

        $product_wishlist    = (get_theme_mod('product_wishlist') != null && !empty(get_theme_mod('product_wishlist'))) ? get_theme_mod('product_wishlist') : false;
        $my_account_vehicles = (get_theme_mod('my_account_vehicles') != null && !empty(get_theme_mod('my_account_vehicles'))) ? get_theme_mod('my_account_vehicles') : false;

        $links = array(
            'dashboard' => $my_account_link,
            'wishlist'  => $my_account_link.'wishlist',
            'orders'    => $my_account_link.'orders',
            'history'   => $my_account_link.'history',
            'addresses' => $my_account_link.'edit-address',
            'my_garage' => $my_account_link.'garage',
            'logout'    => wp_logout_url( home_url() ),
        );

        if ($product_wishlist == false) {
            unset($links['wishlist']);
        }

        if ($my_account_vehicles == false) {
            unset($links['my_garage']);
        }

        return $links;
    }

/*  Clear extra space from string
/*-------------------*/

    function enovathemes_addons_extra_white_space($text){
        $text = preg_replace('/[\t\n\r\0\x0B]/', '', $text);
        $text = preg_replace('/([\s])\1+/', ' ', $text);
        $text = trim($text);
        return $text;
    }

/*  Get all menus
/*-------------------*/

    function enovathemes_addons_get_all_menus(){
        return get_terms( 'nav_menu', array( 'hide_empty' => false ) ); 
    }

/*  CPT Templates
/*-------------------*/

    function enovathemes_addons_header_single_template($single_template) {
        global $post;
        if ($post->post_type == 'header') {
            if ( $theme_file = locate_template( array ( 'single-header.php' ) ) ) {
                $single_template = $theme_file;
            } else {
                $single_template = ENOVATHEMES_ADDONS . 'templates/single-header.php';
            }
        }
        return $single_template;
    }
    add_filter( "single_template", "enovathemes_addons_header_single_template", 20 );

    function enovathemes_addons_megamenus_single_template($single_template) {
        global $post;
        if ($post->post_type == 'megamenu') {
            if ( $theme_file = locate_template( array ( 'single-megamenu.php' ) ) ) {
                $single_template = $theme_file;
            } else {
                $single_template = ENOVATHEMES_ADDONS . 'templates/single-megamenu.php';
            }
        }
        return $single_template;
    }
    add_filter( "single_template", "enovathemes_addons_megamenus_single_template", 20 );

    function enovathemes_addons_footer_single_template($single_template) {
        global $post;
        if ($post->post_type == 'footer') {
            if ( $theme_file = locate_template( array ( 'single-footer.php' ) ) ) {
                $single_template = $theme_file;
            } else {
                $single_template = ENOVATHEMES_ADDONS . 'templates/single-footer.php';
            }
        }
        return $single_template;
    }
    add_filter( "single_template", "enovathemes_addons_footer_single_template", 20 );

    function enovathemes_addons_banner_single_template($single_template) {
        global $post;
        if ($post->post_type == 'banner') {
            if ( $theme_file = locate_template( array ( 'single-banner.php' ) ) ) {
                $single_template = $theme_file;
            } else {
                $single_template = ENOVATHEMES_ADDONS . 'templates/single-banner.php';
            }
        }
        return $single_template;
    }
    add_filter( "single_template", "enovathemes_addons_banner_single_template", 20 );

    add_filter( 'woocommerce_locate_template', 'enovathemes_addons_woocommerce_locate_template', 10, 3 );
    function enovathemes_addons_woocommerce_locate_template( $template, $template_name, $template_path ) {
      global $woocommerce;

      $_template = $template;

      if ( ! $template_path ) $template_path = $woocommerce->template_url;

      $plugin_path  = ENOVATHEMES_ADDONS . '/woocommerce/';

      // Look within passed path within the theme - this is priority
      $template = locate_template(

        array(
          $template_path . $template_name,
          $template_name
        )
      );

      // Modification: Get the template from this plugin, if it exists
      if ( ! $template && file_exists( $plugin_path . $template_name ) )
        $template = $plugin_path . $template_name;

      // Use default template
      if ( ! $template )
        $template = $_template;

      // Return what we found
      return $template;
    }

/*  Array value recursive
/*-------------------*/

    function array_value_recursive($key, array $arr)
    {
        $val = array();
        array_walk_recursive($arr, function ($v, $k) use ($key, &$val) {
            if ($k == $key) array_push($val, $v);
        });
        return $val;
    }

/*  Google fonts
/*-------------------*/

    function enovathemes_addons_google_fonts() {

        $api_key = 'AIzaSyCLHkGg3ymoX7XDOGNrQyKckEwq6pB-Ki0';
        $url     = 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . $api_key;

        $transient_prefix = $api_key;

        if ( false === ( $google_fonts = get_transient( 'gfonts-' . $transient_prefix . '-enovathemes' ) ) ) {

            $google_fonts = [];

            $remote = wp_remote_get( $url );

            if ( is_wp_error( $remote ) ) {
                return new WP_Error( 'site_down', esc_html__( 'Unable to communicate with Google fonts.', 'enovathemes-addons' ) );
            }

            if ( 200 !== wp_remote_retrieve_response_code( $remote ) ) {
                return new WP_Error( 'invalid_response', esc_html__( 'Google fonts did not return a 200.', 'enovathemes-addons' ) );
            }

            $gfonts_array = json_decode( $remote['body'], true );

            if ( ! $gfonts_array ) {
                return new WP_Error( 'bad_json', esc_html__( 'Google fonts has returned invalid data.', 'enovathemes-addons' ) );
            }

            if ( isset( $gfonts_array['items'] ) ) {
                $fonts = $gfonts_array['items'];
            } else {
                return new WP_Error( 'bad_json_2', esc_html__( 'Google fonts has returned invalid data.', 'enovathemes-addons' ) );
            }

            if ( ! is_array( $fonts ) ) {
                return new WP_Error( 'bad_array', esc_html__( 'Google fonts has returned invalid data.', 'enovathemes-addons' ) );
            }

            $google_fonts = array();

            foreach ( $fonts as $font ) {
                $google_fonts[] = array(
                    'family'   => $font['family'],
                    'variants' => $font['variants'],
                );
            } // End foreach().

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $google_fonts ) ) {
                $google_fonts = base64_encode(serialize( $google_fonts ));
                set_transient( 'gfonts-' . $transient_prefix . '-enovathemes', $google_fonts, apply_filters( 'null_gfonts_cache_time', MONTH_IN_SECONDS * 2 ) );
            }
        }

        if ( ! empty( $google_fonts ) ) {

            return unserialize(base64_decode( $google_fonts ));

        } else {

            return new WP_Error( 'no_fonts', esc_html__( 'Google fonts did not return any fonts.', 'enovathemes-addons' ) );

        }
    }

/*  Get taxonomy hierarchy
/*-------------------*/

    function get_taxonomy_hierarchy( $taxonomy, $parent = 0, $exclude = 0, $include = false) {

        $taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;

        $args = array(
            'parent' => $parent,
            'hide_empty' => true,
            'exclude' => $exclude,
            'meta_key'   => 'order',
            'orderby'    => 'meta_value_num'
        );

        if ($include && is_array($include)) {
            $args['include'] = $include;
        }

        $terms = get_terms( $taxonomy, $args);

        $children = array();
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ( $terms as $term ){
                $term->children = get_taxonomy_hierarchy( $taxonomy, $term->term_id, $exclude,$include);
                $children[ $term->term_id ] = $term;
            }
        }
        return $children;

    }

/*  List taxonomy hierarchy
/*-------------------*/

    function set_current_cat($current,$slug,$output){
        if ($current == $slug) {
            return $output;
        }
    }

    function list_taxonomy_hierarchy_no_instance($taxonomies,$level = '',$display = 'default') {

        $option = $class = $current_cat = '';

        if (is_tax('product_cat')) {
            $current_cat = get_queried_object();
            $current_cat = $current_cat->slug;
        }

        if (!empty($current_cat)) {
            $class  = 'class="chosen"';
            $option = 'selected="selected"';
        }


        $output   = '';

        foreach ( $taxonomies as $taxonomy ) {

            $children = $taxonomy->children;
            $parent   = $taxonomy->parent;

            switch ($display) {
                case 'list':
                    $output .='<li><a href="#" title="'.esc_attr($taxonomy->name).'" data-value="'.$taxonomy->slug.'" data-id="'.$taxonomy->term_id.'">'.$taxonomy->name;
                        if (is_array($children) && !empty($children)){$output .='<span class="toggle"></span>';}
                    $output .='</a>';
                    break;
                case 'image-list':

                    $image = get_term_meta($taxonomy->term_id,'thumbnail_id',true);

                    $output .='<li><a href="#" title="'.esc_attr($taxonomy->name).'" data-value="'.$taxonomy->slug.'" data-id="'.$taxonomy->term_id.'">';
                    
                        if (!empty($image) && !is_wp_error($image)) {
                            $image = wp_get_attachment_image_src( $image, 'thumbnail' );
                            if (!is_wp_error($image)) {
                                $output .='<img src="'.$image[0].'" />';
                            }
                        }

                        $output .= $taxonomy->name;    

                        if (is_array($children) && !empty($children)){$output .='<span class="toggle"></span>';}

                    $output .='</a>';
                    break;
                case 'image':

                    // $children = false;
                    $image    = get_term_meta($taxonomy->term_id,'thumbnail_id',true);

                    $output .='<li><a href="#" title="'.esc_attr($taxonomy->name).'" data-value="'.$taxonomy->slug.'" data-id="'.$taxonomy->term_id.'">';
                        if (!empty($image) && !is_wp_error($image)) {
                            $image = wp_get_attachment_image_src( $image, 'thumbnail' );
                            if (!is_wp_error($image)) {
                                $output .='<img src="'.$image[0].'" />';
                            }
                        }
                    $output .= '<span class="cat-name">'.$taxonomy->name.'</span></a>';
                    break;
                default:
                    $output .='<option value="'.$taxonomy->slug.'" data-id="'.$taxonomy->term_id.'" '.set_current_cat($current_cat,$taxonomy->slug,$option).'>'.$level.$taxonomy->name.'</option>';
                    break;
            }

            if (is_array($children) && !empty($children)){
                if($display == 'default'){
                    $level .=  '&nbsp;&nbsp;&nbsp;';
                } else {
                    $level =  '';
                    if ($display == 'list' || $display == 'image' || $display == 'image-list') {
                        $output .= '<ul>';
                    }
                }
                $output .= list_taxonomy_hierarchy_no_instance($children,$level,$display);
                if($display == 'default'){
                    $level =  ($parent) ? '&nbsp;&nbsp;&nbsp;' : '';
                } else {
                    $level =  '';
                    if ($display == 'list' || $display == 'image'|| $display == 'image-list') {
                        $output .= '</ul>';
                    }
                }
            }

            if ($display == 'list' || $display == 'image' || $display == 'image-list') {
                $output .= '</li>';
            }
        }

        return $output;
                
    }

    function list_taxonomy_hierarchy_no_instance_widget($taxonomies,$instance,$level = '') {
    ?>
        <?php foreach ( $taxonomies as $taxonomy ) { ?>

            <?php

                $children = $taxonomy->children;
                $parent   = $taxonomy->parent;

            ?>

            <option value="<?php echo $taxonomy->term_id; ?>" <?php selected( $instance['category'], $taxonomy->term_id ); ?>><?php echo $level.$taxonomy->name; ?></option>

            <?php if (is_array($children) && !empty($children)): ?>
                <?php $level .= '&nbsp;&nbsp;&nbsp;'; ?>
                <?php list_taxonomy_hierarchy_no_instance_widget($children,$instance,$level); ?>
                <?php $level = ($parent) ? '&nbsp;&nbsp;&nbsp;' : ''; ?>
            <?php endif ?>

        <?php } ?>
                
    <?php
    }

/*  Tax and children
/*-------------------*/

    function enovathemes_addons_is_or_descendant_tax( $term,$taxonomy){

        $term        = get_term_by( 'id', $term, $taxonomy);
        $descendants = get_term_children( $term->term_id, $taxonomy);
        $is_child    = false;

        foreach ($descendants as $tax) {
            if (is_tax($taxonomy, $tax)) {
                $is_child = true;
            }
        }

        if (is_tax($taxonomy, $term) || $is_child){
            return true;
        }

        return false;

    }

/*  Product categories transient
/*-------------------*/

    function get_product_categories_hierarchy($cache = true,$include = false) {

        if ($cache) {
            if ( false === ( $categories = get_transient( 'product-categories-hierarchy' ) )) {

                $categories = get_taxonomy_hierarchy( 'product_cat', 0, 0);

                // do not set an empty transient - should help catch private or empty accounts.
                if ( ! empty( $categories ) ) {
                    $categories = base64_encode(serialize( $categories ));
                    set_transient( 'product-categories-hierarchy', $categories, apply_filters( 'null_categories_cache_time', 0 ) );
                }
            }
        } else {

            $categories = get_taxonomy_hierarchy( 'product_cat', 0, 0, $include);
        }

        if ( ! empty( $categories ) ) {

            return $cache ? unserialize(base64_decode($categories)) : $categories;

        } else {

            return new WP_Error( 'no_categories', esc_html__( 'No categories.', 'enovathemes-addons' ) );

        }
    }

    function get_product_categories_raw() {

        if ( false === ( $categories = get_transient( 'product-categories-raw' ) )) {

            $categories = get_terms( array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ));

            // do not set an empty transient - should help catch private or empty accounts.
            if ( ! empty( $categories ) ) {

                $categories_list = array();

                foreach ($categories as $category) {

                    $category_list = array();

                    $category_list['name'] = $category->name;
                    $category_list['link'] = get_term_link($category->term_id,'product_cat');

                    $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true ); 

                    if (!empty($thumbnail_id)) {
                        $image = wp_get_attachment_image_src( $thumbnail_id, 'woocommerce_thumbnail');
                        $category_list['image'] = $image[0];
                        $category_list['width'] = $image[1];
                        $category_list['height'] = $image[2];
                    }

                    $children = get_term_children( $category->term_id, 'product_cat');

                    if (!empty($children)) {
                        $category_list_children = array();

                        foreach ($children as $child) {
                            $term = get_term_by( 'id', $child, 'product_cat');
                            $category_list_child['name'] = $term->name;
                            $category_list_child['link'] = get_term_link($term->term_id,'product_cat');

                            $category_list_children[$term->slug] = $category_list_child;
                        }

                        if (!empty($category_list_children)) {
                            $category_list['children'] = $category_list_children;
                        }
                    }

                    $categories_list[$category->slug] = $category_list;
                    
                }

                $categories = base64_encode(serialize( $categories_list ));
                set_transient( 'product-categories-raw', $categories, apply_filters( 'null_categories_cache_time', 0 ) );
            }
        }

        if ( ! empty( $categories ) ) {

            return unserialize(base64_decode($categories));

        } else {

            return new WP_Error( 'no_categories', esc_html__( 'No categories.', 'enovathemes-addons' ) );

        }
    }

    function get_product_taxonomy_terms_list($lang) {

        if ( false === ( $attributes = get_transient( 'product-taxonomy-terms-list-'.$lang ) )) {

            
            $attributes = array();

            $categories = get_terms( array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ));

            if ( ! empty( $categories ) && !is_wp_error($categories)) {

                $categories_list = array('label' => get_taxonomy('product_cat')->labels->name);
                $terms           = array();

                foreach ($categories as $category) {
                    $terms[$category->slug] = array($category->slug => $category->name);
                }

                $categories_list['terms'] = $terms;

                $attributes['category'] = $categories_list;

                
            }

            $woo_attributes = wc_get_attribute_taxonomies();

            if (!empty($woo_attributes) && !is_wp_error($woo_attributes)) {
                foreach( $woo_attributes as $attribute) {

                    $attribute_terms = get_terms( array(
                        'taxonomy' => 'pa_'.$attribute->attribute_name,
                        'hide_empty' => false,
                    ));

                    if (!empty($attribute_terms) && !is_wp_error($attribute_terms)) {

                        $data  = array('label' => $attribute->attribute_label);
                        $terms = array();

                        foreach ($attribute_terms as $term) {
                            $terms[$term->slug] = array($term->slug => $term->name);
                        }

                        $data['terms'] = $terms;

                        $attributes[$attribute->attribute_name] = $data;
                    }
                }
            }

            $attributes = base64_encode(serialize( $attributes ));
            set_transient( 'product-taxonomy-terms-list-'.$lang, $attributes, apply_filters( 'null_taxonomy_terms_cache_time', 0 ) );
        }

        if ( ! empty( $attributes ) ) {

            return unserialize(base64_decode($attributes));

        } else {

            return new WP_Error( 'no_attributes_list', esc_html__( 'No attributes list.', 'enovathemes-addons' ) );

        }
    }

    function get_post_taxonomy_terms_list($lang) {

        if ( false === ( $attributes = get_transient( 'post-taxonomy-terms-list-'.$lang ) )) {

            
            $attributes = array();

            $categories = get_terms( array(
                'taxonomy' => 'category',
                'hide_empty' => false,
            ));

            if ( ! empty( $categories ) && !is_wp_error($categories)) {

                $categories_list = array('label' => get_taxonomy('category')->labels->name);
                $terms           = array();

                foreach ($categories as $category) {
                    $terms[$category->slug] = array($category->slug => $category->name);
                }

                $categories_list['terms'] = $terms;

                $attributes['category'] = $categories_list;
                
            }

            $tags = get_terms( array(
                'taxonomy' => 'tag',
                'hide_empty' => false,
            ));

            if ( ! empty( $tags ) && !is_wp_error($tags)) {

                $tags_list = array('label' => get_taxonomy('tag')->labels->name);
                $terms           = array();

                foreach ($tags as $tag) {
                    $terms[$tag->slug] = array($tag->slug => $tag->name);
                }

                $tags_list['terms'] = $terms;

                $attributes['tag'] = $tags_list;
                
            }

            $attributes = base64_encode(serialize( $attributes ));
            set_transient( 'post-taxonomy-terms-list-'.$lang, $attributes, apply_filters( 'null_taxonomy_terms_cache_time', 0 ) );
        }

        if ( ! empty( $attributes ) ) {

            return unserialize(base64_decode($attributes));

        } else {

            return new WP_Error( 'no_attributes_list', esc_html__( 'No attributes list.', 'enovathemes-addons' ) );

        }
    }

/*  Delete transient on taxonomies
/*-------------------*/

    function enovathemes_addons_edit_product_term($term_id, $tt_id, $taxonomy) {
        $term = get_term($term_id,$taxonomy);
        if (!is_wp_error($term) && is_object($term)) {

            delete_transient( 'enovathemes-attributes-filter' );
            delete_transient( 'enovathemes-product-categories' );
            delete_transient( 'dynamic-styles-cached' );
            delete_transient( 'enovathemes-banners' );
            delete_transient( 'enovathemes-megamenu' );
            delete_transient( 'enovathemes-megamenu-names' );
            delete_transient( 'enovathemes-headers' );
            delete_transient( 'enovathemes-footers' );
            delete_transient( 'enovathemes-title-sections' );
            delete_transients_with_prefix( 'et_post_' );
            delete_transient('enovathemes-products-navigation-pagination');

            $taxonomy = $term->taxonomy;
            if ($taxonomy == "product_cat") {
                delete_transient( 'product-categories-hierarchy' );
                delete_transient( 'product-categories-raw' );
                delete_transients_with_prefix( 'et_product_' );
                delete_transients_with_prefix( 'product-taxonomy-terms-list-' );
                delete_transients_with_prefix( 'search_keyword_' );
            }

            if ($taxonomy == "product_tag") {
                delete_transients_with_prefix( 'search_keyword_' );
            }

            if ($taxonomy == "vehicles") {
                // Vehicle transients
                delete_transient( 'vehicles-first-param' );
                delete_transients_with_prefix( 'vehicles-first-param-' );
                delete_transients_with_prefix( 'vin_decode_' );
                delete_transient( 'vehicles' );
                delete_transient( 'vehicle-list' );
                delete_transient( 'universal-products' );
            }
        }
    }

    function enovathemes_addons_delete_product_term($term_id, $tt_id, $taxonomy, $deleted_term) {
        if (!is_wp_error($deleted_term) && is_object($deleted_term)) {

            delete_transient( 'enovathemes-attributes-filter' );
            delete_transient( 'enovathemes-product-categories' );
            delete_transient( 'dynamic-styles-cached' );
            delete_transient( 'enovathemes-banners' );
            delete_transient( 'enovathemes-megamenu' );
            delete_transient( 'enovathemes-megamenu-names' );
            delete_transient( 'enovathemes-headers' );
            delete_transient( 'enovathemes-footers' );
            delete_transient( 'enovathemes-title-sections' );
            delete_transients_with_prefix( 'et_post_' );
            delete_transient('enovathemes-products-navigation-pagination');

            $taxonomy = $deleted_term->taxonomy;
            if ($taxonomy == "product_cat") {
                delete_transient( 'product-categories-hierarchy' );
                delete_transient( 'product-categories-raw' );
                delete_transients_with_prefix( 'et_product_' );
                delete_transients_with_prefix( 'product-taxonomy-terms-list-' );
                delete_transients_with_prefix( 'search_keyword_' );
            }

            if ($taxonomy == "product_tag") {
                delete_transients_with_prefix( 'search_keyword_' );
            }

            if ($taxonomy == "vehicles") {
                // Vehicle transients
                delete_transient( 'vehicles-first-param' );
                delete_transients_with_prefix( 'vehicles-first-param-' );
                delete_transients_with_prefix( 'vin_decode_' );
                delete_transient( 'vehicles' );
                delete_transient( 'vehicle-list' );
                delete_transient( 'universal-products' );
            }
        }
    }
    add_action( 'create_term', 'enovathemes_addons_edit_product_term', 99, 3 );
    add_action( 'edit_term', 'enovathemes_addons_edit_product_term', 99, 3 );
    add_action( 'delete_term', 'enovathemes_addons_delete_product_term', 99, 4 );

/*  Search action
/*-------------------*/

    add_filter('posts_where', 'keyword_filter', 10, 2);

    function keyword_filter($where, $wp_query) {
        global $wpdb;

        if ($search_terms = $wp_query->get('search_prod_title')) {
            $title_parts = [];
            foreach ($search_terms as $term) {
                $like = '%' . $wpdb->esc_like($term) . '%';
                $title_parts[] = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", $like);
            }

            $where .= ' AND (' . implode(' OR ', $title_parts) . ')';

            if ($wp_query->get('search_prod_content')) {
                $content_parts = [];
                foreach ($search_terms as $term) {
                    $like = '%' . $wpdb->esc_like($term) . '%';
                    $content_parts[] = $wpdb->prepare("{$wpdb->posts}.post_content LIKE %s", $like);
                }

                $where .= ' OR (' . implode(' OR ', $content_parts) . ')';
            }
        }

        return $where;
    }

/*  Wishlist
/*-------------------*/

    // Get current user data
    function fetch_user_data() {
        if (is_user_logged_in()){
            $current_user = wp_get_current_user();
            $current_user_wishlist = get_user_meta( $current_user->ID, 'wishlist',true);
            echo json_encode(array('user_id' => $current_user->ID,'wishlist' => $current_user_wishlist));
        }
        die();
    }
    add_action( 'wp_ajax_fetch_user_data', 'fetch_user_data' );

    // Wishlist option in the user profile
    add_action( 'show_user_profile', 'wishlist_user_profile_field' );
    add_action( 'edit_user_profile', 'wishlist_user_profile_field' );
    function wishlist_user_profile_field( $user ) { ?>
        <table class="form-table wishlist-data">
            <tr>
                <th><?php echo esc_attr__("Wishlist","enovathemes-addons"); ?></th>
                <td>
                    <input type="text" name="wishlist" id="wishlist" value="<?php echo esc_attr( get_the_author_meta( 'wishlist', $user->ID ) ); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
    <?php }

    

    add_action( 'personal_options_update', 'save_wishlist_user_profile_field' );
    add_action( 'edit_user_profile_update', 'save_wishlist_user_profile_field' );
    function save_wishlist_user_profile_field( $user_id ) {
        if ( !current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }
        update_user_meta( $user_id, 'wishlist', $_POST['wishlist'] );
    }

    function user_wishlist_update(){
        if (isset($_POST["user_id"]) && !empty($_POST["user_id"])) {
            $user_id   = $_POST["user_id"];
            $user_obj = get_user_by('id', $user_id);
            if (!is_wp_error($user_obj) && is_object($user_obj)) {
                update_user_meta( $user_id, 'wishlist', $_POST["wishlist"]);
            }
        }
        die();
    }
    add_action('admin_post_nopriv_user_wishlist_update', 'user_wishlist_update');
    add_action('admin_post_user_wishlist_update', 'user_wishlist_update');

    function wishlist_count_update(){
        if (isset($_POST["product_id"]) && !empty($_POST["product_id"])) {

            $product_id = $_POST["product_id"];
            $wishlist   = get_post_meta($product_id, 'enovathemes_addons_wishlist', true );

            $wishlist++;

            update_post_meta( $product_id, 'enovathemes_addons_wishlist',$wishlist);
        }
        die();
    }
    add_action('admin_post_nopriv_wishlist_count_update', 'wishlist_count_update');
    add_action('admin_post_wishlist_count_update', 'wishlist_count_update');

    // Get current user data
    function wishlist_fetch() {

        if (isset($_POST["wishlist"]) && !empty($_POST["wishlist"])) {
            $output = '';
            $wishlist = $_POST["wishlist"];
            
            echo do_shortcode('[et_products ids="'.$_POST["wishlist"].'" type="custom" columns="5" columns_tab_land="3" columns_tab_port="2" wishlist="true" quantity="'.count(explode(',', $wishlist)).'"]');

        }
        die();
    }
    add_action( 'wp_ajax_wishlist_fetch', 'wishlist_fetch' );
    add_action( 'wp_ajax_nopriv_wishlist_fetch', 'wishlist_fetch' );


/*  Compare
/*-------------------*/

    // Get current user data
    function compare_products_fetch($compare = '') {

        $native = false;

        if (isset($_POST["compare"]) && !empty($_POST["compare"]) && isset($_POST["aj"]) && !empty($_POST["aj"])) {
           $compare = $_POST["compare"];

           $native = true;
        }

        if (!empty($compare)) {

            global $product;

            $compare = explode(',', $compare);
            $length  = sizeof($compare) + 1;
            $query_options = array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'post__in'       => $compare,
                'orderby'        =>'post__in'
            );

            $first_image = '';

            $compare_query = new WP_Query($query_options);

            if($compare_query->have_posts()){

                $currency           = get_woocommerce_currency_symbol();
                $currency_pos       = get_option('woocommerce_currency_pos');
                $price_num_decimals = get_option('woocommerce_price_num_decimals');

                $product_attributes = array();

                foreach ($compare as $p) {
                   $p = wc_get_product( $p );
                   $attributes = $p->get_attributes();
                   foreach ($attributes as $attribute => $options) {
                       array_push($product_attributes, $attribute);
                    }
                }

                $product_attributes = array_unique($product_attributes,SORT_REGULAR);

                $product_image  = '<tr><th class="product-image"><div class="cell-inner"><svg width="300" height="300" viewBox="0 0 300 300"><path d="M0,0H300V300H0V0Z" /></svg></div></th>';
                $product_title  = '<tr><th class="product-title"><div class="cell-inner"></div></th>';
                $product_rating = '<tr><th class="product-rating"><div class="cell-inner">'.esc_html__('Rating','enovathemes-addons').'</div></th>';
                $product_price  = '<tr><th class="product-price"><div class="cell-inner">'.esc_html__('Price','enovathemes-addons').'</div></th>';
                $product_atts   = array();

                foreach ($product_attributes as $attribute) {
                    $taxonomy = get_taxonomy($attribute);
                    if (is_object($taxonomy)) {
                        $product_atts[$taxonomy->name] = '<tr><th><div class="cell-inner">'.$taxonomy->labels->singular_name.'</div></th>';
                    }
                }

                while ($compare_query->have_posts() ) {
                    $compare_query->the_post();
                    
                    global $product;

                    $rating     = $product->get_average_rating();

                    if($product->is_type( 'variable' ) )
                    {
                        $price      = $product->get_variation_regular_price();
                        $price_sale = $product->get_variation_price();
                    } else {
                        $price       = $product->get_regular_price();
                        $price_sale  = $product->get_sale_price();
                    }

                    if ($price) {
                        $price = round($price,$price_num_decimals);
                    }

                    if ($price_sale) {
                        $price_sale = round($price_sale,$price_num_decimals);
                    }

                    switch ($currency_pos) {
                        case 'left':
                            $price_output = $currency.$price;
                            $price_sale_output = $currency.$price_sale;
                            break;
                        case 'left_space':
                            $price_output = $currency.' '.$price;
                            $price_sale_output = $currency.' '.$price_sale;
                            break;
                        case 'right':
                            $price_output = $price.$currency;
                            $price_sale_output = $price_sale.$currency;
                            break;
                        case 'right_space':
                            $price_output = $price.' '.$currency;
                            $price_sale_output = $price_sale.' '.$currency;
                            break;
                    }

                    $rating = (!empty($rating)) ? wc_get_rating_html( $product->get_average_rating() ) : esc_attr__('n/a','enovathemes-addons');
                    $price  = (!empty($price)) ? ((!empty($price_sale)) ? '<span class="sale-price">'.$price_sale_output.'</span>' : '<span class="regular-price">'.$price_output.'</span>') : esc_attr__('n/a','enovathemes-addons');

                    $product_image  .= '<td class="product-image"><div class="cell-inner"><a data-product="'.esc_attr($product->get_id()).'" class="compare-remove" title="'.esc_attr__('Remove item','enovathemes-addons').'" href="#"></a><a href="'.get_permalink( $product->get_id() ).'" title="'.esc_attr($product->get_name()).'">'.$product->get_image().'</a></div></td>';
                    $product_title  .= '<td class="product-title"><div class="cell-inner"><a href="'.get_permalink( $product->get_id() ).'" title="'.esc_attr($product->get_name()).'">'.$product->get_name().'</a></div></td>';
                    $product_rating .= '<td class="product-rating"><div class="cell-inner">'.$rating.'</div></td>';
                    $product_price  .= '<td class="product-price"><div class="cell-inner">'.$price.'</div></td>'; 

                    if (!empty($product_attributes)) {

                        foreach ($product_attributes as $attribute) {

                            if (taxonomy_exists($attribute)) {

                                $attr = get_the_terms($product->get_id(),$attribute);
                                $taxonomy = get_taxonomy($attribute);

                                if ($attr && !is_wp_error($attr)) {
                                    if (sizeof($attr) > 1) {
                                        $product_atts[$taxonomy->name] .= '<td class="attr overflow"><div class="cell-inner">';
                                            foreach ($attr as $key => $term) {

                                                $color = get_term_meta($term->term_id,'enova_'.$attribute.'_color',true);

                                                if ($color) {
                                                    $brightness = enovathemes_addons_brightness($color);
                                                    $product_atts[$taxonomy->name] .= '<span class="attr color '.$brightness.'"><span style="background:'.esc_attr($color).';" title="'.esc_attr($term->name).'"></span></span>';
                                                } else {
                                                    $product_atts[$taxonomy->name] .= '<span class="attr">'.$term->name.'</span>';
                                                }
                                            }
                                        $product_atts[$taxonomy->name] .= '</div></td>';
                                    } else {
                                        foreach ($attr as $key => $term) {
                                            $color = get_term_meta($term->term_id,'color',true);
                                            if ($color) {
                                                $brightness = enovathemes_addons_brightness($color);
                                                $product_atts[$taxonomy->name] .= '<td class="attr color '.$brightness.'"><div class="cell-inner"><span style="background:'.esc_attr($color).';" title="'.esc_attr($term->name).'"></span></div></td>';
                                            } else {
                                                $product_atts[$taxonomy->name] .= '<td class="attr"><div class="cell-inner">'.$term->name.'</div></td>';
                                            }
                                        }
                                    }
                                    
                                } else {
                                    $product_atts[$taxonomy->name] .= '<td class="attr"><div class="cell-inner">-</div></td>';
                                }

                            }
                            
                        }
                    }

                }

                wp_reset_postdata();

            }


            $inc   = 5;
            $class = array('cbt-wrapper','et-clearfix');
            $wrapper_class = array('compare-table-wrapper');

            $style = '';

            if ($native) {
                $class[]         = 'modal';
                $wrapper_class[] = 'modal';
                $wrapper_class[] = 'container';
           } else {
                $class[]         = 'single';
                $wrapper_class[] = 'single';
           }

            if ($length > $inc) {
               $style = 'style="width:calc('.(($length*100)/($inc+1)).'% + 16px)"';
            }

            $output = '<div class="'.implode(' ', $wrapper_class).'"><div class="compare-table-toggle"></div><a href="#" class="clear">'.esc_html__("Clear","enovathemes-addons").'</a>';
            if ($length > 6) {
                $output .= '<div class="cbt-nav"><a class="nav prev disabled" href="#">Prev</a><a class="nav next" href="#">Next</a></div>';
            }
            $output .= '<div class="'.implode(' ', $class).'"><table data-length="'.$length.'" class="compare-table cbt" '.$style.'><tbody>';
                $output .= $product_image.'</tr>';
                $output .= $product_title.'</tr>';
                $output .= $product_rating.'</tr>';
                $output .= $product_price.'</tr>';
                foreach ($product_atts as $key => $value) {
                    $output .= $value.'</tr>';
                }
            $output .= '</tbody></table></div></div>';

            echo $output;

        }
        if ($native) {
            die();
        }
    }
    add_action( 'wp_ajax_compare_products_fetch', 'compare_products_fetch' );
    add_action( 'wp_ajax_nopriv_compare_products_fetch', 'compare_products_fetch' );

/*  FBT
---------------------*/

    function enovathemes_addons_add_to_cart_all_ajax_handler() {

        $products = isset($_POST['products']) ? json_decode($_POST['products']) : false;

        if ($products) {
            foreach ($products as $product) {
                WC()->cart->add_to_cart($product);
            }
        }
        wp_die();
    }

    add_action('wp_ajax_add_to_cart_all', 'enovathemes_addons_add_to_cart_all_ajax_handler');
    add_action('wp_ajax_nopriv_add_to_cart_all', 'enovathemes_addons_add_to_cart_all_ajax_handler');

    function enovathemes_addons_update_mini_cart_ajax_handler() {
        ob_start();

        // Output the updated mini cart content
        woocommerce_mini_cart();

        $mini_cart = ob_get_clean();

        echo $mini_cart;
        wp_die();
    }

    add_action('wp_ajax_update_mini_cart_content', 'enovathemes_addons_update_mini_cart_ajax_handler');
    add_action('wp_ajax_nopriv_update_mini_cart_content', 'enovathemes_addons_update_mini_cart_ajax_handler');

/*  Product save custom fields
/*-------------------*/

    add_action( 'woocommerce_process_product_meta', 'enovathemes_addons_save_custom_fields' );
    function enovathemes_addons_save_custom_fields( $post_id ) {
        update_post_meta($post_id, "mobex_enovathemes_label1",sanitize_text_field($_POST["mobex_enovathemes_label1"]));
        update_post_meta($post_id, "mobex_enovathemes_label2",sanitize_text_field($_POST["mobex_enovathemes_label2"]));
        update_post_meta($post_id, "mobex_enovathemes_label1_color",sanitize_text_field($_POST["mobex_enovathemes_label1_color"]));
        update_post_meta($post_id, "mobex_enovathemes_label2_color",sanitize_text_field($_POST["mobex_enovathemes_label2_color"]));
        if (isset($_POST["fbt_ids"])) {
            update_post_meta($post_id, "fbt_ids",$_POST["fbt_ids"]);
        }
        if (isset($_POST["ss_ids"])) {
            update_post_meta($post_id, "ss_ids",$_POST["ss_ids"]);
        }
        if (isset($_POST["compare_ids"])) {
            update_post_meta($post_id, "compare_ids",$_POST["compare_ids"]);
        }
    }

/*  Filter by attributes
---------------------*/

    function et_get_min_max_price_meta_query( $args ) {

        $current_min_price = isset( $args['min_price'] ) ? floatval( $args['min_price'] ) : 0;
        $current_max_price = isset( $args['max_price'] ) ? floatval( $args['max_price'] ) : PHP_INT_MAX;

        return apply_filters(
            'woocommerce_get_min_max_price_meta_query',
            array(
                'key'     => '_price',
                'value'   => array( $current_min_price, $current_max_price ),
                'compare' => 'BETWEEN',
                'type'    => 'DECIMAL(10,' . wc_get_price_decimals() . ')',
            ),
            $args
        );
    }

    function enovathemes_addons_starts_with ($string, $startString) {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    function enovathemes_addons_get_filtered_price($include = false) {global $wpdb;

        $sql = "SELECT min( min_price ) as min_price, MAX( max_price ) as max_price";
        $sql .= " FROM {$wpdb->wc_product_meta_lookup}";

        if ($include) {
            $sql .= " WHERE product_id IN (".implode(',',$include).")";
        } else {
            $sql .= " WHERE product_id IN (";
            $sql .= "SELECT ID FROM {$wpdb->posts}";
            $sql .= " WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')";
            $sql .= " AND {$wpdb->posts}.post_status = 'publish')";
        }

        $sql = apply_filters( 'woocommerce_price_filter_sql', $sql);

        return $wpdb->get_row( $sql );
    }

    function enovathemes_addons_get_filtered_product_count($rating,$include = false) {global $wpdb;

        $product_visibility_terms = wc_get_product_visibility_term_ids();

        $tax_query[] = array(
            'taxonomy'      => 'product_visibility',
            'field'         => 'term_taxonomy_id',
            'terms'         => $product_visibility_terms[ 'rated-' . $rating ],
            'operator'      => 'IN',
            'rating_filter' => true,
        );

        $tax_query      = new WP_Tax_Query( $tax_query );
        $tax_query_sql  = $tax_query->get_sql( $wpdb->posts, 'ID' );

        if ($include) {

            $sql  = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) FROM {$wpdb->posts} ";
            $sql .= $tax_query_sql['join'];
            $sql .= " WHERE {$wpdb->posts}.post_type = 'product'";
            $sql .= " AND {$wpdb->posts}.post_status = 'publish' ";

            if ($include) {
                $sql .= " AND {$wpdb->posts}.ID IN (".implode(',',$include).")";
            }

            $sql .= $tax_query_sql['where'];

        } else {

            $meta_query     = WC_Query::get_main_meta_query();
            $meta_query     = new WP_Meta_Query( $meta_query );
            $meta_query_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );

            $sql  = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) FROM {$wpdb->posts} ";
            $sql .= $tax_query_sql['join'] . $meta_query_sql['join'];
            $sql .= " WHERE {$wpdb->posts}.post_type = 'product'";
            $sql .= " AND {$wpdb->posts}.post_status = 'publish' ";

            if ($include) {
                $sql .= " AND {$wpdb->posts}.ID IN (".implode(',',$include).")";
            }

            $sql .= $tax_query_sql['where'] . $meta_query_sql['where'];
        }

        return absint( $wpdb->get_var( $sql ) );
    }

    function enovathemes_addons_get_attribute_taxonomies($category = ''){ global $wpdb;

        $multilingual     = (class_exists('SitePress') || function_exists('pll_the_languages')) ? true : false;
        $woo_attributes   = wc_get_attribute_taxonomies();
        $attributes       = array();

        if (!empty($category) && !empty($woo_attributes) && !is_wp_error($woo_attributes)) {

            $args = array(
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 0,
                'orderby'             => 'title',
                'order'               => 'DESC',
                'posts_per_page'      => -1,
                'tax_query'           => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'id',
                        'terms'    => $category,
                        'operator' => 'IN'
                    )
                )
            );

            $querystr = "SELECT DISTINCT * FROM $wpdb->posts AS p
                LEFT JOIN $wpdb->term_relationships AS r ON (p.ID = r.object_id)
                INNER JOIN $wpdb->term_taxonomy AS x ON (r.term_taxonomy_id = x.term_taxonomy_id)
                INNER JOIN $wpdb->terms AS t ON (r.term_taxonomy_id = t.term_id)
                WHERE p.post_type IN ('product')
                AND p.post_status = 'publish'
                AND x.taxonomy = 'product_cat'
                AND (x.term_id = {$category})
                ORDER BY t.name ASC, p.post_date DESC;";

            if ($multilingual) {
                $query_results  = new WP_Query($args);
                $query_results  = $query_results->posts;
            } else {
                $query_results  = $wpdb->get_results($querystr);
            }

            if (!empty($query_results)) {

                $data = array();
                $post_woo_attributes = array();

                foreach ($query_results as $result) {

                    foreach( get_post_taxonomies($result->ID) as $attribute) {
                        if (enovathemes_addons_starts_with ($attribute, 'pa_')) {

                            $terms = get_the_terms( $result->ID, $attribute);

                            if($terms && !is_wp_error($terms)){

                                array_push($post_woo_attributes, substr($attribute, 3));

                                foreach ( $terms as $term ) {
                                    $data[substr($attribute, 3)][$term->term_id] = $term->name;
                                }

                            }
                        }
                    }

                }

                wp_reset_postdata();

            }

            if (!empty($woo_attributes) && !is_wp_error($woo_attributes)) {
                foreach( $woo_attributes as $attribute) {
                    if (in_array($attribute->attribute_name, $post_woo_attributes)) {
                        $attributes[$attribute->attribute_name]['name']  = $attribute->attribute_name;
                        $attributes[$attribute->attribute_name]['label'] = ucfirst($attribute->attribute_label);
                        $attributes[$attribute->attribute_name]['type']  = $attribute->attribute_type;
                        $attributes[$attribute->attribute_name]['terms'] = $data[$attribute->attribute_name];
                    }
                }
            }

        } else {

            if (!empty($woo_attributes) && !is_wp_error($woo_attributes)) {
                foreach( $woo_attributes as $attribute) {

                    $attribute_terms = get_terms( array(
                        'taxonomy' => 'pa_'.$attribute->attribute_name,
                        'hide_empty' => false,
                    ));

                    if ($attribute_terms) {

                        $data = array();

                        foreach ($attribute_terms as $term) {
                            $data[$term->term_id] = array($term->name,$term->slug);
                        }
                        $attributes[$attribute->attribute_name]['name']  = $attribute->attribute_name;
                        $attributes[$attribute->attribute_name]['label'] = ucfirst($attribute->attribute_label);
                        $attributes[$attribute->attribute_name]['type']  = $attribute->attribute_type;
                        $attributes[$attribute->attribute_name]['terms'] = $data;
                    }
                }
            }

        }

        return $attributes;
    }

    function enovathemes_addons_build_filter_attributes($cache = true,$category = '') {

        if (!empty($category)) {
            $cache = false;
        }

        if (class_exists('Woocommerce')) {

            if ($cache) {
                if ( false === ( $attributes = get_transient( 'enovathemes-attributes-filter' ) )) {

                    $attributes = enovathemes_addons_get_attribute_taxonomies();

                    // do not set an empty transient - should help catch private or empty accounts.
                    if ( ! empty( $attributes ) ) {
                        $attributes = base64_encode(serialize($attributes ));
                        set_transient( 'enovathemes-attributes-filter', $attributes, apply_filters( 'null_filter_cache_time', 0 ) );
                    }
                }
            }else {
                $attributes = enovathemes_addons_get_attribute_taxonomies($category);
            }

            if ( ! empty( $attributes ) ) {

                return $cache ? unserialize(base64_decode($attributes )) : $attributes;

            } else {

                return new WP_Error( 'no_filter_attributes', esc_html__( 'No filter.', 'enovathemes-addons' ) );

            }

        } else {
            return false;
        }
    }

    function list_attribute_no_instance($taxonomies,$display = 'select') {

        $output   = '';

        foreach ( $taxonomies as $term_id => $term ) {

            switch ($display) {
                case 'list':
                case 'label':
                    $output .='<li><a href="#" title="'.$term[0].'" data-value="'.$term[1].'" data-id="'.$term_id.'">'.$term[0].'</a></li>';
                    break;
                case 'image':

                    $output .='<li><a href="#" data-value="'.$term[1].'" data-id="'.$term_id.'" title="'.esc_attr($term[0]).'">';

                        $image = get_term_meta($term_id,'image',true);

                        if (!empty($image) && !is_wp_error($image)) {
                            $image = wp_get_attachment_image_src( $image, 'full' );
                            if (!is_wp_error($image)) {
                                $output .='<img alt="'.$term[0].'" src="'.$image[0].'" />';
                            }
                        } else {
                            $output .= '<span class="attr-name">'.$term[0].'</span>';
                        }
                    $output .= '</a></li>';

                break;
                case 'col':

                    $color = get_term_meta($term_id,'color',true);
                    if (!empty($color) && !is_wp_error($color)) {
                        $output .='<li><a href="#" data-value="'.$term[1].'" title="'.esc_attr($term[0]).'" data-id="'.$term_id.'">';
                           $class = enovathemes_addons_brightness($color);
                           $output .= '<span class="attr-color '.esc_attr($class).'" style="background:'.$color.';"></span>';
                           $output .= '<span class="attr-name">'.$term[0].'</span>';
                        $output .= '</a></li>';
                    } else {
                        $output = '';
                    }

                break;
                default:
                    $output .='<option value="'.$term[1].'" data-id="'.$term_id.'">'.$term[0].'</option>';
                    break;
            }
        }

        return $output;
                
    }

    /* Filter attributes render
    /*----------------*/

        function enovathemes_addons_render_price_filter_attribute($include = false){
            // Round values to nearest 10 by default.
            $step = max( apply_filters( 'woocommerce_price_filter_widget_step', 10 ), 1 );

            // Find min and max price in current result set.
            $prices    = enovathemes_addons_get_filtered_price($include);
            $min_price = $prices->min_price;
            $max_price = $prices->max_price;

            // Check to see if we should add taxes to the prices if store are excl tax but display incl.
            $tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

            if ( wc_tax_enabled() && ! wc_prices_include_tax() && 'incl' === $tax_display_mode ) {
                $tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
                $tax_rates = WC_Tax::get_rates( $tax_class );

                if ( $tax_rates ) {
                    $min_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min_price, $tax_rates ) );
                    $max_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $max_price, $tax_rates ) );
                }
            }

            $min_price = apply_filters( 'woocommerce_price_filter_widget_min_amount', floor( $min_price / $step ) * $step );
            $max_price = apply_filters( 'woocommerce_price_filter_widget_max_amount', ceil( $max_price / $step ) * $step );

            $values = '';

            if ($include && isset($_POST['min_price'])) {

                $values = array();

                $values[] = $_POST['min_price'];

                if (isset($_POST['max_price']) && !empty($_POST['max_price'])) {
                    $values[] = $_POST['max_price'];
                }

                if (count($values) == 2) {
                    $values = implode(',', $values);
                    $values = 'data-values="'.$values.'"';
                }
            }

            // If both min and max are equal, we don't need a slider.
            if ( $min_price != $max_price ) {

                $current_min_price = $min_price; // WPCS: input var ok, CSRF ok.
                $current_max_price = $max_price; // WPCS: input var ok, CSRF ok.
                
                $currency     = get_woocommerce_currency_symbol();
                $currency_pos = get_option('woocommerce_currency_pos');
                $price_num_decimals = get_option('woocommerce_price_num_decimals');

                if(shortcode_exists('woocommerce_currency_switcher_link_list')) {
                    if ( 'yes' === get_option( 'alg_wc_currency_switcher_enabled', 'yes' ) ) {

                        $exchange_rate = alg_wc_cs_get_currency_exchange_rate( alg_get_current_currency_code() );

                        $current_min_price = round($current_min_price*$exchange_rate,$price_num_decimals);
                        $current_max_price = round($current_max_price*$exchange_rate,$price_num_decimals);
                    }
                }

                switch ($currency_pos) {
                    case 'left':
                        $display_price_min = $currency.$current_min_price;
                        $display_price_max = $currency.$current_max_price;
                        break;
                    case 'left_space':
                        $display_price_min = $currency.' '.$current_min_price;
                        $display_price_max = $currency.' '.$current_max_price;
                        break;
                    case 'right':
                        $display_price_min = $current_min_price.$currency;
                        $display_price_max = $current_max_price.$currency;
                        break;
                    case 'right_space':
                        $display_price_min = $current_min_price.' '.$currency;
                        $display_price_max = $current_max_price.' '.$currency;
                        break;
                }

                $attributes = array();

                if (!empty($values)) {
                    $attributes[] = $values;
                }

                $attributes[] = 'data-min="'.$current_min_price.'"';
                $attributes[] = 'data-max="'.$current_max_price.'"';
                $attributes[] = 'data-step="'.esc_attr( $step ).'"';
                $attributes[] = 'data-currency="'.esc_attr( $currency ).'"';
                $attributes[] = 'data-position="'.esc_attr( $currency_pos ).'"';

                $output = '<div class="pf-item price" data-attribute="price">';
                    $output .= '<h5 class="widget_title">'.esc_html__('Price','enovathemes-addons').'<span class="clear-attribute">'.esc_html__("Any price","enovathemes-addons").'</span></h5>';
                    $output .= '<div class="inner-wrap">';
                        $output .= '<div class="pf-slider slider" '.implode(' ',$attributes).'  >';
                            $output .= '<div class="ui-slider-handle"><span class="ui-slider-handle-bubble min">'.wc_price($current_min_price).'</span></div><div class="ui-slider-handle"><span class="ui-slider-handle-bubble max">'.wc_price($current_max_price).'</span></div>';
                        $output .= '</div>';
                        $output .= '<input type="number" name="min" value="'.esc_attr( $current_min_price ).'" />';
                        $output .= '<span class="desh">-</span>';
                        $output .= '<input type="number" name="max" value="'.esc_attr( $current_max_price ).'" />';
                    $output .= '</div>';
                $output .= '</div>';

                return $output;

            }
        }

        function enovathemes_addons_render_rating_filter_attribute($include = false){
            $rating_filter = array(); // WPCS: input var ok, CSRF ok, sanitization ok.
            $found         = false;
            $rating_output = '';

            for ( $rating = 5; $rating >= 1; $rating-- ) {
                $count = enovathemes_addons_get_filtered_product_count($rating,$include);
                if ( empty( $count ) ) {
                    continue;
                }
                $found = true;
                $rdata  = '';

                if ( in_array( $rating, $rating_filter, true ) ) {
                    $rdata_ratings = implode( ',', array_diff( $rating_filter, array( $rating ) ) );
                } else {
                    $rdata_ratings = implode( ',', array_merge( $rating_filter, array( $rating ) ) );
                }

                $class       = in_array( $rating, $rating_filter, true ) ? 'wc-layered-nav-rating chosen' : 'wc-layered-nav-rating';
                $rdata       = apply_filters( 'woocommerce_rating_filter_link', $rdata_ratings ? $rdata_ratings : '' );
                $rating_html = wc_get_star_rating_html( $rating );
                $rating_output .= '<li class="'.esc_attr( $class ).'"><a href="#" data-value="'.$rdata.'" title="'.esc_html__('Rating:','enovathemes-addons').' '.$rating.'"><span class="star-rating">'.$rating_html.'</span></a></li>';
            }

            if ($found) {

                $output = '<div class="pf-item rating clickable widget_rating_filter" data-attribute="rating">';
                    $output .= '<h5 class="widget_title">'.esc_html__('Rating','enovathemes-addons').'<span class="clear-attribute">'.esc_html__("Any rating","enovathemes-addons").'</span></h5>';
                    $output .= '<div class="inner-wrap">';
                        $output .= '<ul>';
                            if (!empty($rating_output)) {
                                $output .= $rating_output;
                            }
                        $output .= '</ul>';
                    $output .= '</div>';
                $output .= '</div>';

                return $output;
            }
        }

        function enovathemes_addons_render_category_filter_attribute($attribute,$cache,$include = false){


            $categories = is_array($include) && $attribute['lock'] == 'false' ? get_product_categories_hierarchy(false,$include) : get_product_categories_hierarchy($cache);

            if ((!empty($categories) && !is_wp_error($categories))){

                $type = (in_array($attribute['display'],array('list','image','image-list'))) ? 'clickable' : 'selectable';

                $data   = array();
                $data[] = 'data-attribute="ca"';
                $data[] = 'data-display="'.esc_attr($attribute['display']).'"';
                $data[] = 'data-lock="'.esc_attr($attribute['lock']).'"';
                $data[] = 'data-label="'.esc_attr(esc_html__( 'Category', 'enovathemes-addons' )).'"';

                if ($attribute['columns']) {
                    $data[] = 'data-columns="'.esc_attr($attribute['columns']).'"';
                }

                $class = array();

                $class[] = $type;

                if ($attribute['display'] == 'image-list') {
                    $class[] = 'image-list';
                    $class[] = 'list';
                } else {
                    $class[] = $attribute['display'];
                }

                $class[] = 'cat';
                
                $output = '<div class="pf-item '.implode(' ', $class).'" '.implode(' ', $data).'>';

                    $display_output = '';

                    switch ($attribute['display']) {
                        case 'list':
                        case 'image-list':
                        case 'image':
                            $display_output .= '<h5 class="widget_title">'.esc_html__( 'Shop by Categories', 'enovathemes-addons' ).'</h5>';
                            $display_output .= '<div class="inner-wrap">';
                                $display_output .= '<span class="back clear-attribute"><span class="arrow"></span>'.esc_html__( 'Clear', 'enovathemes-addons' ).'</span>';

                                $display_output .= '<ul data-name="category" class="category" data-columns="'.esc_attr($attribute['columns']).'">';
                                    if (!empty($categories) && !is_wp_error($categories)){
                                        $display_output .= list_taxonomy_hierarchy_no_instance($categories,'',$attribute['display']);
                                    }
                                $display_output .= '</ul>';
                            $display_output .= '</div>';
                            break;
                        default:
                            $display_output .= '<span class="clear-attribute">'.esc_html__("Clear","enovathemes-addons").'</span><select name="category" class="category">';
                                $display_output .= '<option class="default" value="">'.esc_html__( 'Category', 'enovathemes-addons' ).'</option>';
                                if (!empty($categories) && !is_wp_error($categories)){
                                    $display_output .= list_taxonomy_hierarchy_no_instance($categories,'','default');
                                }
                            $display_output .= '</select>';
                            break;
                    }

                    $output .= $display_output;
                    
                $output .= '</div>';

                return $output;

            }
        }

        function enovathemes_addons_render_attribute_filter_attribute($attribute,$include = false){

            $attribute_terms = $include ? $include : (isset($attribute['terms']) ? $attribute['terms'] : '');

            if ($attribute['lock'] == "true" && $include) {

                $get_attribute_terms = get_terms( array(
                    'taxonomy' => 'pa_'.$attribute['name'],
                    'hide_empty' => true,
                ));

                if ($get_attribute_terms) {

                    $attribute_terms = array();

                    foreach ($get_attribute_terms as $term) {
                        $attribute_terms[$term->term_id] = array($term->name,$term->slug);
                    }
                }

            }

            if (isset($attribute_terms) && !empty($attribute_terms)) {

                $data   = array();
                $class  = array();

                $type = ($attribute['display'] == 'list' || $attribute['display'] == 'image'  || $attribute['display'] == 'label' || $attribute['display'] == 'col') ? 'clickable' : 'selectable';

                $data[] = 'data-attribute="'.esc_attr($attribute['name']).'"';
                $data[] = 'data-display="'.esc_attr($attribute['display']).'"';
                $data[] = 'data-lock="'.esc_attr($attribute['lock']).'"';
                $data[] = 'data-label="'.esc_attr($attribute['label']).'"';

                if ($attribute['columns']) {
                    $data[] = 'data-columns="'.esc_attr($attribute['columns']).'"';
                }

                $class[] = 'pf-item';
                $class[] = esc_attr($attribute['display']);
                $class[] = $attribute['name'];
                $class[] = 'attr';
                $class[] = $type;

                if (isset($attribute['category']) && !empty($attribute['category'])) {

                    if (is_array($attribute['category'])) {

                        $category_array = array();
                        $children_array = array();

                        foreach($attribute['category'] as $cat) {
                            $category = get_term_by('slug',$cat,'product_cat');

                            if (is_object($category) && !is_wp_error($category)) {
                                array_push($category_array,$category->slug);

                                $children = get_terms(array(
                                    'taxonomy' => 'product_cat',
                                    'parent'=>$category->term_id)
                                );

                                if (!empty($children)) {
                                    foreach ($children as $child) {
                                        array_push($children_array, $child->slug);
                                    }
                                }
                            }
                        }

                        if (!empty($category_array)) {
                            $data[] = 'data-category="'.implode(',', $category_array).'"';
                        }
                        
                        if ($attribute['children'] == "true") {
                            if (!empty($children_array)) {
                                $data[] = 'data-children="'.implode(',', $children_array).'"';
                            }
                        } else {
                            $data[] = 'data-children="false"';
                        }
                        
                        $class[] = 'cat-active';

                    } else {

                        $category = get_term_by('slug',$attribute['category'],'product_cat');

                        if (is_object($category) && !is_wp_error($category)) {
                            $data[] = 'data-category="'.esc_attr($category->slug).'"';
                            if ($attribute['children'] == "true") {
                                $children = get_terms(array(
                                    'taxonomy' => 'product_cat',
                                    'parent'=>$category->term_id)
                                );
                                if (!empty($children)) {
                                    $children_array = array();
                                    foreach ($children as $child) {
                                        array_push($children_array, $child->slug);
                                    }
                                    if (!empty($children_array)) {
                                        $data[] = 'data-children="'.implode(',', $children_array).'"';
                                    }
                                }
                            } else {
                                $data[] = 'data-children="false"';
                            }
                            $class[] = 'cat-active';
                        }

                    }
                    
                }

                if (isset($attribute['category-hide']) && !empty($attribute['category-hide'])) {

                    if (is_array($attribute['category-hide'])) {

                        $category_array = array();
                        $children_array = array();

                        foreach($attribute['category-hide'] as $cat) {
                            $category = get_term_by('slug',$cat,'product_cat');

                            if (is_object($category) && !is_wp_error($category)) {
                                array_push($category_array,$category->slug);

                                $children = get_terms(array(
                                    'taxonomy' => 'product_cat',
                                    'parent'=>$category->term_id)
                                );

                                if (!empty($children)) {
                                    foreach ($children as $child) {
                                        array_push($children_array, $child->slug);
                                    }
                                }
                            }
                        }

                        if (!empty($category_array)) {
                            $data[] = 'data-category-hide="'.implode(',', $category_array).'"';
                        }
                        
                        if ($attribute['children-hide'] == "true") {
                            if (!empty($children_array)) {
                                $data[] = 'data-children-hide="'.implode(',', $children_array).'"';
                            }
                        } else {
                            $data[] = 'data-children-hide="false"';
                        }
                        
                        $class[] = 'cat-hide-active';

                    } else {

                        $category = get_term_by('slug',$attribute['category-hide'],'product_cat');

                        if (is_object($category) && !is_wp_error($category)) {
                            $data[] = 'data-category-hide="'.esc_attr($category->slug).'"';
                            if ($attribute['children-hide'] == "true") {
                                $children = get_terms(array(
                                    'taxonomy' => 'product_cat',
                                    'parent'=>$category->term_id)
                                );
                                if (!empty($children)) {
                                    $children_array = array();
                                    foreach ($children as $child) {
                                        array_push($children_array, $child->slug);
                                    }
                                    if (!empty($children_array)) {
                                        $data[] = 'data-children-hide="'.implode(',', $children_array).'"';
                                    }
                                }
                            } else {
                                $data[] = 'data-children-hide="false"';
                            }
                            $class[] = 'cat-hide-active';
                        }

                    }
                }

                $display_output = '';
                $display_output .= '<div class="'.implode(' ', $class).'" '.implode(' ', $data).'>';
                $clear = '';

                $term_count = count($attribute_terms);

                switch ($attribute['display']) {
                    case 'list':
                    case 'label':
                    case 'image':
                    case 'col':
                        $display_output .= '<h5 class="widget_title">'.esc_html($attribute['label']).'<span class="clear-attribute">'.esc_html__("Clear","enovathemes-addons").'</span></h5>';
                        $display_output .= '<div class="inner-wrap">';
                            

                            if (in_array($attribute['display'], array('list','image','label')) && $term_count  > 10) {
                                $display_output .= '<input class="attribute-search" type="text" placeholder="'.esc_attr__("Search for","enovathemes-addons").' '.lcfirst($attribute['label']).'">';
                            }

                            $class = array(esc_attr($attribute['name']));

                            if ($term_count  > 10) {
                                $class[]= 'max';
                            }

                            $display_output .= '<ul data-name="'.esc_attr($attribute['name']).'" class="'.implode(' ', $class).'" data-columns="'.esc_attr($attribute['columns']).'">';
                                $display_output .= list_attribute_no_instance($attribute_terms,$attribute['display']);
                            $display_output .= '</ul>';
                        $display_output .= '</div>';
                    break;
                    case 'slider':

                        $list = array();

                        foreach ($attribute_terms as $term) {
                            array_push($list, intval($term[0]));
                        };

                        $min = esc_attr(min($list));
                        $max = esc_attr(max($list));
                        $min = floor(floatval($min));
                        $max = ceil(floatval($max));

                        $values = '';

                        if ($include && isset($_POST[$attribute['name'].'_min_value'])) {

                            $values = array();

                            $values[] = $_POST[$attribute['name'].'_min_value'];

                            if (isset($_POST[$attribute['name'].'_max_value']) && !empty($_POST[$attribute['name'].'_max_value'])) {
                                $values[] = $_POST[$attribute['name'].'_max_value'];
                            }

                            if (count($values) == 2) {
                                $values = implode(',', $values);
                                $values = 'data-values="'.$values.'"';
                            }
                        }

                        $attributes = array();

                        if (!empty($values)) {
                            $attributes[] = $values;
                        }

                        $attributes[] = 'data-min="'.$min.'"';
                        $attributes[] = 'data-max="'.$max.'"';
                        $attributes[] = 'data-name="'.esc_attr($attribute['name']).'"';

                        $display_output .= '<h5 class="widget_title">'.esc_html($attribute['label']).'<span class="clear-attribute">'.esc_html__("Clear","enovathemes-addons").'</span></h5>';
                        $display_output .= '<div class="inner-wrap">';
                            $display_output .= '<div '.implode(' ', $attributes).' class="pf-slider slider '.esc_attr($attribute['name']).'"><div class="ui-slider-handle"><span class="ui-slider-handle-bubble min">'.$min.'</span></div><div class="ui-slider-handle"><span class="ui-slider-handle-bubble max">'.$max.'</span></div></div>';
                            $display_output .= '<input type="number" value="'.$min.'" name="min"><span class="desh">-</span><input type="number" value="'.$max.'" name="max">';
                        $display_output .= '</div>';
                    break;
                    default:
                        $display_output .= '<span class="clear-attribute">'.esc_html__("Clear","enovathemes-addons").'</span><select name="'.esc_attr($attribute['name']).'">';
                            $display_output .= '<option class="default" value="">'.esc_html($attribute['label']).'</option>';
                            if ($attribute_terms){
                                $display_output .= list_attribute_no_instance($attribute_terms,$attribute['display']);
                            }
                        $display_output .= '</select>';
                    break;
                }
                    
                $display_output .= '</div>';

                return $display_output;

            }

        }

    /* Filter attributes action
    /*----------------*/

        function vehicle_filter_component($vehicle_attributes){

            $vehicle_params = apply_filters( 'vehicle_params','');

            if (!empty($vehicle_attributes) && !is_wp_error($vehicle_attributes)) {

                $vehicles   = array();
                $meta_query = array();

                foreach ($vehicle_attributes as $key => $value) {

                    if ($key != 'year' && $value && in_array($key,$vehicle_params)) {

                        if ($key == "engine") {
                            // if (is_numeric($value)) {
                                $meta_query[] = [
                                    "key" => "vehicle_" . $key,
                                    "value" => $value,
                                    "compare" => '='
                                ];
                            // }
                        } elseif ($key == "transmission") {
                            $meta_query[] = [
                                "key" => "vehicle_" . $key,
                                "value" => $value,
                                "compare" => 'LIKE'
                            ];
                        } else {
                            $meta_query[] = [
                                "key" => "vehicle_" . $key,
                                "value" => $value,
                                "compare" => '='
                            ];
                        }
                        
                    }
                   
                }

                $args = [
                    "taxonomy" => "vehicles",
                    "hide_empty" => false,
                ];

                if (!empty($meta_query)) {
                    $meta_query["relation"] = "AND";
                    $args["meta_query"] = $meta_query;
                }

                $vehicles_terms = get_terms($args);

                if (!is_wp_error($vehicles_terms)) {


                    if (isset($vehicle_attributes['year']) && !empty($vehicle_attributes['year'])) {
                        
                        $vehicles_terms_with_year = array();

                        foreach ($vehicles_terms as $vehicle) {
                            $year  = get_term_meta($vehicle->term_id, 'vehicle_year', true );
                            $years = et_year_formatting($year);

                            if (is_array($years) && in_array($vehicle_attributes['year'],$years)) {
                                $vehicles_terms_with_year[] = $vehicle;
                            }

                        }

                        if (!empty($vehicles_terms_with_year)) {
                            $vehicles_terms = $vehicles_terms_with_year;
                        }
                    }


                    foreach ($vehicles_terms as $vehicle) {
                        $vehicles[] = $vehicle->term_id;
                    }

                    $vehicles = array_unique($vehicles,SORT_REGULAR);
                    $vehicles = array_filter($vehicles);

                    return $vehicles;

                }
            }

            return false;

        }

        function vehicle_set_from_cookies_if_empty($vehicle_attributes){
            $vehicle_cookies = (get_theme_mod('vehicle_cookies') != null && !empty(get_theme_mod('vehicle_cookies'))) ? true : false;
            if ($vehicle_cookies) {
                $COOKIE_Vehicle = (isset($_COOKIE['vehicle'])) ? json_decode( html_entity_decode( stripslashes ($_COOKIE['vehicle'])),true ) : false;
                $vehicle_attributes = empty($vehicle_attributes) ? $COOKIE_Vehicle : $vehicle_attributes;
            }

            return $vehicle_attributes;
        }

        function enovathemes_addons_vin_decoder($vin,$data_only = false){

            $vin_decoder    = (get_theme_mod('vin_decoder') != null && !empty(get_theme_mod('vin_decoder'))) ? get_theme_mod('vin_decoder') : false;
            $vin_key        = (get_theme_mod('vin_key') != null && !empty(get_theme_mod('vin_key'))) ? get_theme_mod('vin_key') : false;
            $vin_secret     = (get_theme_mod('vin_secret') != null && !empty(get_theme_mod('vin_secret'))) ? get_theme_mod('vin_secret') : false;
            $vehicle_params = apply_filters( 'vehicle_params','');

            if ($vin && $vin_decoder && $vin_key && $vehicle_params) {

                $vin = str_replace('#', '', $vin);
                $vin = str_replace('?', '', $vin);
                $vin = str_replace('&', '', $vin);

                $unique = 'vin_decode_'.$vin.'_'.$vin_key.'_'.$data_only;

                if ( false === ( $return = get_transient( $unique ) ) ) {

                    $data               = array();
                    $vehicle_attributes = array();
                    $return             = false;
                    $vin_error          = false;

                    switch ($vin_decoder) {
                        case 'https://api.vindecoder.eu/3.2':
                            
                            $id  = "decode";
                            $vin = mb_strtoupper($vin);
                            $controlsum = substr(sha1("{$vin}|{$id}|{$vin_key}|{$vin_secret}"), 0, 10);
                            $url = array($vin_decoder,$vin_key,$controlsum,'decode',$vin.'.json');
                            $url = implode('/', $url);

                            $curl = curl_init();
                            $headers = array('Content-Type: application/json');
                            curl_setopt($curl, CURLOPT_URL, $url );
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                            $result =  curl_exec($curl);


                            if ($result) {

                                $result = json_decode($result,true);

                                if (isset($result['decode'])) {
                                    foreach ($result['decode'] as $key => $params) {

                                        $key = strtolower($params['label']);
                                        $val = $params['value'];

                                        $key = str_replace('array', '', $key);
                                        $key = str_replace('model year', 'year', $key);
                                        $key = str_replace('engine displacement (ccm)', 'engine', $key);

                                        if (is_array($val)) {
                                            $val = implode(', ', $val);
                                        }

                                        if ($key == 'engine') {
                                            $val = number_format(round($val/1000,1), 1, '.', '');
                                        }

                                        $data[$key] = $val;
                                        
                                    }
                                } else {$vin_error = true;}
                            }

                        break;
                        case 'https://auto.dev/api/vin':

                            $url = $vin_decoder.'/'.$vin.'?apiKey='.$vin_key;

                            $curl = curl_init();
                            $headers = array('Content-Type: application/json');
                            curl_setopt($curl, CURLOPT_URL, $url );
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                            $result =  curl_exec($curl);

                            if ($result) {
                                $result = json_decode($result,true);

                                $off = array('options','colors','manufacturerCode','price','categories','squishVin','matchingType','mpg');

                                if(isset($result['status']) && ($result['status'] == 'BAD_REQUEST' || $result['status'] == 'NOT_FOUND')){
                                    $vin_error = true;
                                } else {
                                    foreach ($result as $key => $params) {
                                        if (!in_array($key, $off)) {

                                            if ('numOfDoors' == $key) {
                                                $key = 'Number of doors';
                                            } elseif('drivenWheels' == $key){
                                                $key = 'Driven wheels';
                                            }

                                            if (is_array($params)) {
                                                if ($key == "years") {
                                                    $data['year'] = $params[0]['year'];
                                                    $data['trim'] = $params[0]['styles'][0]['trim'];
                                                } else {

                                                    switch ($key) {
                                                        case 'engine':
                                                            if (array_key_exists('size', $params)) {
                                                                $data[$key] = number_format($params['size'], 1, '.', '');
                                                            }
                                                            break;
                                                        case 'transmission':
                                                            if (array_key_exists('transmissionType', $params)) {
                                                                $data[$key] = ucfirst(strtolower($params['transmissionType']));
                                                            }
                                                            break;
                                                        default:
                                                            $data[$key] = $params['name'];
                                                            break;
                                                    }
                                                }
                                            } else {
                                                $data[$key] = $params;
                                            }
                                        }
                                    }
                                }
                                
                            }

                        break;
                        case 'https://api.vehicledatabases.com/europe-vin-decode':

                            $url = array($vin_decoder,$vin);
                            $url = implode('/', $url);

                            $curl = curl_init();
                            $headers = array('x-AuthKey: '.$vin_key);
                            curl_setopt($curl, CURLOPT_URL, $url );
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                            curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                            curl_setopt($curl, CURLOPT_ENCODING, '');
                            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                            $result =  curl_exec($curl);

                            if ($result) {

                                $result = json_decode($result,true);

                                if (isset($result['status']) && $result['status'] == 'success' && isset($result['data'])) {

                                    foreach ($result['data'] as $key => $value) {

                                        if ($key != 'Standard equipment' && $key != 'Optional equipment' && $key != 'Vin number analize') {

                                            foreach ($value as $opt => $val) {

                                                $opt = strtolower($opt);

                                                $opt = str_replace('model year', 'year', $opt);

                                                if ($opt == 'displacement Nominal') {
                                                    $opt = "engine";
                                                }

                                                $data[$opt] = $val;
                                            }

                                        }

                                    }
                                    
                                } else {$vin_error = true;}
                            }

                        break;
                        case 'https://api.vehicledatabases.com/uk-registration-decode':

                            $url = array($vin_decoder,$vin);
                            $url = implode('/', $url);

                            $curl = curl_init();
                            $headers = array('x-AuthKey: '.$vin_key);
                            curl_setopt($curl, CURLOPT_URL, $url );
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                            curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                            curl_setopt($curl, CURLOPT_ENCODING, '');
                            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                            $result =  curl_exec($curl);

                            if ($result) {

                                $result = json_decode($result,true);

                                if (isset($result['status']) && $result['status'] == 'success' && isset($result['data'])) {

                                    foreach ($result['data'] as $key => $value) {

                                        if ($key == 'vehicle_description') {

                                            foreach ($value as $opt => $val) {

                                                $opt = strtolower($opt);

                                                $data[$opt] = $val;
                                            }

                                        }

                                    }
                                    
                                } else {$vin_error = true;}
                            }

                        break;
                        case 'https://uk1.ukvehicledata.co.uk/api/datapackage/VehicleData':


                            $url = $vin_decoder.'?v=2&api_nullitems=1&auth_apikey='.$vin_key.'&key_VRM='.$vin;

                            $curl = curl_init();
                            $headers = array(
                                'Content-Type: application/json'
                            );
                            curl_setopt($curl, CURLOPT_URL, $url );
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                            $result =  curl_exec($curl);

                            if ($result) {

                                $result = json_decode($result,true);

                                if (isset($result['Response']) && $result['Response']['StatusCode'] == 'Success') {

                                    foreach ($result['Response']['DataItems']['VehicleRegistration'] as $key => $value) {

                                        if ($key =="YearOfManufacture") {
                                            $key = "Year";
                                        }

                                        $data[strtolower($key)] = $value;
                                    }


                                }

                            }

                        break;
                        case 'https://specifications.vinaudit.com/v3/specifications':

                            $url = $vin_decoder.'?vin='.$vin.'&key='.$vin_key.'&format=json';

                            $curl = curl_init();
                            $headers = array('Content-Type: application/json');
                            curl_setopt($curl, CURLOPT_URL, $url );
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                            $result =  curl_exec($curl);

                            if ($result) {

                                $result = json_decode($result,true);

                                if (isset($result['success']) && $result['success']) {

                                    foreach ($result['attributes'] as $key => $value) {

                                        if ($key != "engine") {

                                            $key = strtolower($key);

                                            $key = str_replace('_', ' ', $key);

                                            if ($key == 'engine size') {
                                                $key = "engine";
                                            }

                                            $data[$key] = $value;

                                        }

                                    }
                                    
                                } else {$vin_error = true;}
                            }

                        break;
                        case 'http://api.marketcheck.com/v2/decode/car':

                            $url = $vin_decoder.'/'.$vin.'/specs?api_key='.$vin_key;

                            $curl = curl_init();
                            $headers = array(
                                'Host:marketcheck-prod.apigee.net',
                                'Content-Type: application/json'
                            );
                            curl_setopt($curl, CURLOPT_URL, $url );
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                            $result =  curl_exec($curl);

                            if ($result) {

                                $result = json_decode($result,true);


                                if (isset($result['code']) && ($result['code'] == '400' || $result['code'] == '422')) {
                                    $vin_error = true;
                                } else {

                                    foreach ($result as $key => $value) {

                                        if (!is_array($key)) {

                                            $key = strtolower($key);

                                            $key = str_replace('_', ' ', $key);

                                            $data[$key] = $value;

                                        }

                                    }


                                }

                            }

                        break;
                        case 'https://app.auto-ways.net/api/v1':

                            $vin_country = (get_theme_mod('country') != null && !empty(get_theme_mod('country'))) ? get_theme_mod('country') : false;

                            if ($vin_country) {

                                $url = $vin_decoder.'/'.$vin_country.'/?token='.$vin_key.'&plaque='.$vin;

                                $curl = curl_init();
                                $headers = array(
                                    'Content-Type: application/json'
                                );
                                curl_setopt($curl, CURLOPT_URL, $url );
                                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                                $result =  curl_exec($curl);

                                if ($result) {

                                    $result = json_decode($result,true);


                                    if (isset($result['code']) && ($result['code'] != '200' || $result['error'] == true)) {
                                        $vin_error = true;
                                    } else {

                                        foreach ($result['data'] as $key => $value) {

                                            if (!is_array($key)) {

                                                $key = str_replace('AWN_', '', $key);
                                                $key = strtolower($key);
                                                $key = str_replace('_', ' ', $key);

                                                switch($key){
                                                    case 'marque':
                                                        $key = 'make';
                                                    break;
                                                    case 'modele':
                                                        $key = 'model';
                                                    break;
                                                    case 'version':
                                                        $key = 'trim';
                                                    break;
                                                    case 'annee de debut modele':
                                                        $key = 'year';
                                                    break;
                                                    case 'type boite vites':
                                                        $key = 'transmission';
                                                    break;
                                                }

                                                $data[$key] = $value;

                                            }

                                        }


                                    }

                                }

                            }

                        break;
                        case 'https://api.biluppgifter.se/api/v1/vehicle/regno':
                        case 'https://api.biluppgifter.se/api/v1/vehicle/vin':

                            $vin_country = (get_theme_mod('country_biluppgifter') != null && !empty(get_theme_mod('country_biluppgifter'))) ? get_theme_mod('country_biluppgifter') : false;

                            if ($vin_country) {

                                $url = $vin_decoder.'/'.$vin;

                                $curl = curl_init();
                                $headers = array(
                                    'Content-Type: application/json'
                                );

                                $dt = [
                                    "country_code" => $vin_country,
                                    "api_token" => $vin_key
                                ];
                                
                                curl_setopt($curl, CURLOPT_URL, $url );
                                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
                                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dt));

                                $result =  curl_exec($curl);

                                if ($result) {

                                    $result = json_decode($result,true);

                                    if (!isset($result['data']['basic']) || empty($result['data']['basic'])) {
                                        $vin_error = true;
                                    } elseif(isset($result['data']['basic']['data'])) {

                                        foreach ($result['data']['basic']['data'] as $key => $value) {

                                            if (!is_array($key)) {

                                                $key = strtolower($key);
                                                $key = str_replace('_', ' ', $key);

                                                if($key == 'vehicle year'){
                                                    $key = 'year';
                                                }

                                                $data[$key] = $value;

                                            }

                                        }


                                    }

                                }

                            }

                        break;
                    }

                    if (!empty($data)) {

                        foreach ($data as $key => $value) {
                            if (in_array($key,$vehicle_params)) {
                                $vehicle_attributes[$key] = $value;
                            }
                        }

                        if ($data_only) {
                            if (!empty($data)) {
                                $return = $data;
                            }
                        } else {
                            if (!empty($vehicle_attributes)) {
                                $return = $vehicle_attributes;
                            }
                        }

                        if (is_array($return)) {
                            $return = array_unique($return);
                            ksort($return);
                        }

                        if ($return && !empty($return)) {
                            $return = base64_encode(serialize($return ));
                            set_transient( $unique, $return, WEEK_IN_SECONDS );
                        }
                    }

                }


                if ( $return && ! empty( $return ) ) {

                    return unserialize(base64_decode($return));

                } elseif($vin_error) {

                    $providers = [
                        'vindecoder.eu',
                        'auto.dev',
                        'vinaudit.com',
                        'vehicledatabases.com',
                        'vehicledatabases.com (UK registration number)',
                        'ukvehicledata.co.uk (UK registration number)',
                        'marketcheck.com',
                        'auto-ways.net (decode by plate)',
                        'biluppgifter.se (decode by registration number)',
                        'biluppgifter.se (decode by VIN number)',
                    ];

                    $output  = '<p>' . esc_html__(
                        "The VIN decoder is powered by several integrated providers. To enable it, you’ll need to obtain an API key from one of these providers. The theme includes ready-to-use integrations for all of them.",
                        "enovathemes-addons"
                    ) . '</p>';

                    $output .= '<ul>';
                    foreach ($providers as $label) {
                        $output .= '<li>' . esc_html($label) . '</li>';
                    }
                    $output .= '</ul>';

                    return array('error' => $output);
                }
                

            }

            return false;
        }

        function filter_breadcrumbs_output($params,$cs = ''){
            $cs = array($cs);
            return '<div class="filter-breadcrumbs'.implode(' ',$cs).'">'.implode('', $params).'<a href="#" class="clear-all-attribute active">'.esc_html__('Reset','enovathemes-addons').'</a><div class="share"><a href="#" class="active" title="'.esc_html__('Share search results','enovathemes-addons').'">'.esc_html__('Share','enovathemes-addons').'</a>'.enovathemes_addons_post_social_share('filter').'</div></div>';
        }

        function generate_breadcrumbs($cat){

            $output      = '';

            $text_before = '<span>';
            $text_after  = '</span>';
            $link_after  = '<span class="arrow"></span>';
            $home_text   = esc_html__('Home','enovathemes-addons');

            if(!empty(get_option('page_on_front')))
            $home_text = get_the_title( get_option('page_on_front') );
            $product_text  = (!empty(get_option('woocommerce_shop_page_id'))) ? get_the_title( get_option('woocommerce_shop_page_id') ) : get_bloginfo();
            
            $home_link = esc_url(home_url('/'));
            $shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';

            $output .= '<a href="' . $home_link . '">' . $home_text . '</a>'.$link_after;

            if ( $cat ) {

                $this_tax    = get_term_by('slug', $cat, 'product_cat');
                if($this_tax){

                    $this_parents = ($this_tax) ? get_ancestors( $this_tax->term_id, 'product_cat' ) : '';

                    $output .= '<a href="' . get_post_type_archive_link('product') . '">' . $product_text . '</a>'.$link_after;

                    if (is_array($this_parents) && !empty($this_parents)) {
                        foreach (array_reverse($this_parents) as $this_parent_ID) {
                            $this_parent = get_term($this_parent_ID, 'product_cat');
                            $output .= '<a href="'.get_term_link( $this_parent->slug, 'product_cat').'">'. $this_parent->name .'</a>'.$link_after;
                        }
                        $output .= $text_before . $this_tax->name . $text_after;
                    } else {
                        $output .= $text_before . $this_tax->name . $text_after;
                    }
                }

            } else {
                    $output .= $text_before . $product_text . $text_after;
            }

            return $output;
        }

        function filter_attributes() {
            
            if (isset($_POST['ajax']) && !empty($_POST['ajax'])) {

                $atts = $filter = $filter_output = array();

                $off = array(
                    'plt',
                    'psz',
                    'onsale',
                    'sel',
                    'action',
                    'display',
                    'value',
                    'attribute',
                    'ajax',
                    'pn',
                    'yr',
                    'alg_currency',
                    'dgwt_wcas',
                    'lang',
                    'post_type',
                    'product_cat',
                    'rating_filter',
                    'vin',
                    'universal',
                    'data_shop',
                    'page',
                );

                $vehicle_params = apply_filters( 'vehicle_params','');

                $vehicle_attributes = [];

                if ($vehicle_params) {
                    foreach ($vehicle_params as $param) {
                        array_push($off, $param);
                    }
                }

                if (isset($_POST['product_cat']) && !empty($_POST['product_cat'])) {
                    $_POST['ca'] = $_POST['product_cat'];
                    $_POST['product_cat'] = '';
                }

                if (isset($_POST['rating_filter']) && !empty($_POST['rating_filter'])) {
                    $_POST['rating'] = $_POST['rating_filter'];
                    $_POST['rating_filter'] = '';
                }


                foreach ($_POST as $key => $value) {
                    if (!empty($value)) {

                        $key = ($key == 'yr') ? 'year' : $key;

                        if (in_array($key, $vehicle_params)) {
                            $vehicle_attributes[$key] = $value;
                        }

                        if (!in_array($key, $off)) {

                            $atts[$key] = $value;

                            if ($key != 'attributes') {
                                $filter[$key] = $value;
                            }

                        }
                    }
                }

                if (!empty($atts)) {

                    $shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
                    if ('' === get_option( 'permalink_structure' )) {
                        $shop_link = get_home_url().'?post_type=product';
                    }

                    $cat_title = (!empty(get_option('woocommerce_shop_page_id'))) ? get_the_title( get_option('woocommerce_shop_page_id') ) : get_bloginfo();

                    $nav_output = $products_output = $found_output = $cat_description = $cat_children = $bread_output = $title_section_breadcrumbs_output = '';

                    $total = 0;
                    $max   = 0;

                    $orderby  = 'menu_order title';
                    $order    = 'ASC';
                    $meta_key = '';

                    $product_number  = (null != get_theme_mod('product_number') && !empty(get_theme_mod('product_number'))) ? get_theme_mod('product_number') : 20;
                    $shop_navigation = (null != get_theme_mod('shop_navigation') && !empty(get_theme_mod('shop_navigation'))) ? get_theme_mod('shop_navigation') : 'pagination';
                    $shop_layout     = (null != get_theme_mod('shop_layout') && !empty(get_theme_mod('shop_layout'))) ? get_theme_mod('shop_layout') : 'grid';
                    $cache           = (class_exists('SitePress') || function_exists('pll_the_languages')) ? false : true;
                    
                    $data_shop  = (isset($_GET["data_shop"]) && !empty($_GET["data_shop"])) ? $_GET["data_shop"] : "default";
                    if($data_shop == 'infinite'){
                        $shop_navigation = 'infinite';
                    } elseif($data_shop == 'loadmore'){
                        $shop_navigation = 'loadmore';
                    }

                    if (empty($product_number)) {
                        $product_number =  get_option( 'posts_per_page' );
                    }

                    if (empty($shop_navigation)) {
                        $shop_navigation = 'pagination';
                    }

                    if (empty($shop_layout)) {
                        $shop_layout = 'grid';
                    }

                    if (isset($_POST['plt']) && $_POST['plt']) {
                        $shop_layout = $_POST['plt'];
                    }

                    $args = array(
                        'post_type'           => 'product',
                        'post_status'         => 'publish',
                        'ignore_sticky_posts' => 0,
                        'orderby'             => 'menu_order title',
                        'order'               => 'ASC',
                        'posts_per_page'      => -1,
                        'fields'              => 'ids'
                    );

                    $meta_query = $tax_query = $filter_breadcrumbs = array();

                    if (isset($_POST['onsale']) && $_POST['onsale'] == 1) {
                        $args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
                    }

                    if (!empty($filter)) {

                        foreach ($filter as $key => $value) {
                            if (!empty($value)) {
                                switch ($key) {
                                    case 'ca':

                                        $tax_query[] = array(
                                            'taxonomy' => 'product_cat',
                                            'field'    => 'slug',
                                            'terms'    => $value,
                                            'operator' => 'IN'
                                        );

                                        $term = get_term_by('slug',$value,'product_cat');

                                        if (is_object($term) && !is_wp_error($term)) {
                                            $filter_breadcrumbs[] = '<span class="breadcrumbs-item">'.esc_html__('Category',"enovathemes-addons").': '.$term->name.'</span>';
                                        }

                                    break;
                                    case 'orderby':

                                        switch ($value) {
                                            case 'menu_order':
                                                $orderby  = 'menu_order title';
                                                $order    = 'ASC';
                                                $meta_key = '';
                                                break;
                                            case 'popularity':
                                                $orderby  = 'meta_value_num';
                                                $order    = 'DESC';
                                                $meta_key = 'total_sales';
                                                break;
                                            case 'rating':
                                                $orderby  = 'meta_value_num';
                                                $order    = 'DESC';
                                                $meta_key = '_wc_average_rating';
                                                break;
                                            case 'date':
                                                $orderby  = 'date';
                                                $order    = 'DESC';
                                                $meta_key = '';
                                                break;
                                            case 'price':
                                                $orderby  = 'meta_value_num';
                                                $order    = 'ASC';
                                                $meta_key = '_price';
                                                break;
                                            case 'price-desc':
                                                $orderby  = 'meta_value_num';
                                                $order    = 'DESC';
                                                $meta_key = '_price';
                                                break;
                                            default:
                                                $orderby  = 'menu_order title';
                                                $order    = 'ASC';
                                                $meta_key = '';
                                                break;
                                        }

                                        $args['orderby'] = $orderby;
                                        $args['order']   = $order;

                                        if (!empty($meta_key)) {
                                            $args['meta_key'] = $meta_key;
                                        }
                                        
                                    break;
                                    case 'rating':

                                        $from = absint($value) - 0.3;
                                        $to   = absint($value) + 0.3;

                                        $meta_query[] = array(
                                            'relation' => 'AND',
                                            array(
                                                'key'     => '_wc_average_rating',
                                                'value'   => $from,
                                                'compare' => '>=',
                                            ),
                                            array(
                                                'key'     => '_wc_average_rating',
                                                'value'   => $to,
                                                'compare' => '<=',
                                            )
                                        );

                                        $filter_breadcrumbs[] = '<span class="breadcrumbs-item">'.esc_html__('Rating',"enovathemes-addons").': '.$value.'</span>';
                                        
                                    break;
                                    case 'min_price':
                                    case 'max_price':
                                        
                                        $min_price = (isset($filter['min_price']) && !empty($filter['min_price'])) ? $filter['min_price'] : 0;
                                        $max_price = (isset($filter['max_price']) && !empty($filter['max_price'])) ? $filter['max_price'] : 0;

                                        $price_args = array('min_price' => $min_price,'max_price' => $max_price);
                                        $meta_query[] = et_get_min_max_price_meta_query($price_args);

                                        $currency           = get_woocommerce_currency_symbol();
                                        $currency_pos       = get_option('woocommerce_currency_pos');

                                        switch ($currency_pos) {
                                            case 'left':
                                                $min_price = $currency.$min_price;
                                                $max_price = $currency.$max_price;
                                                break;
                                            case 'left_space':
                                                $min_price = $currency.' '.$min_price;
                                                $max_price = $currency.' '.$max_price;
                                                break;
                                            case 'right':
                                                $min_price = $min_price.$currency;
                                                $max_price = $max_price.$currency;
                                                break;
                                            case 'right_space':
                                                $min_price = $min_price.' '.$currency;
                                                $max_price = $max_price.' '.$currency;
                                                break;
                                        }

                                        $filter_breadcrumbs[] = '<span class="breadcrumbs-item">'.esc_html__('Price',"enovathemes-addons").': '.$min_price.' - '.$max_price.'</span>';

                                    break;
                                    case 's':

                                        $args['s'] = $value;

                                        $filter_breadcrumbs[] = '<span class="breadcrumbs-item">'.esc_html__('Keyword',"enovathemes-addons").': "'.$value.'"</span>';

                                    break;
                                    default:

                                        $terms = array();

                                        if (strpos($key,'_max_value') !== false) {

                                            $key_terms_array = array();

                                            $key = explode('_', $key);
                                            $key = $key[0];

                                            $max = intval($filter[$key.'_max_value']);
                                            $min = intval($filter[$key.'_min_value']);

                                            $key_terms = get_terms(array('taxonomy' => 'pa_'.$key,'hide_empty' => true));

                                            if (!empty($key_terms)) {
                                                foreach ($key_terms as $term) {
                                                    $name = intval($term->name);
                                                    if ($name >= $min && $name <= $max) {
                                                        array_push($key_terms_array, $term->term_id);
                                                    }
                                                }
                                            }

                                            if (!empty($key_terms_array)) {

                                                $tax_query[] = array(
                                                    'taxonomy' => 'pa_'.$key,
                                                    'field'    => 'id',
                                                    'terms'    => $key_terms_array,
                                                    'operator' => 'IN'
                                                );

                                            } else {
                                                $args['post__in']   = array(0);
                                            }

                                            $min_value = ($filter[$key.'_min_value']) ? $filter[$key.'_min_value'] : '0';

                                            $the_taxonomy = get_taxonomy('pa_'.$key);

                                            if (is_object($the_taxonomy)) {
                                                $filter_breadcrumbs[] = '<span class="breadcrumbs-item">'.ucfirst($the_taxonomy->labels->name).': '.$min_value.'-'.$filter[$key.'_max_value'].'</span>';
                                            }


                                        } elseif (strpos($key,'_min_value') == false) {

                                            $key_display = str_replace('filter_', '', $key);
                                            $key_display = str_replace('pa_', '', $key_display);

                                            if (strpos($key,'pa_') !== false) {
                                                $key_term = get_term_by('slug',$value,$key);

                                                if (is_object($key_term) && !is_wp_error($key_term)) {
                                                    $terms = array($key_term->slug);
                                                }

                                            } elseif(strpos($key,'filter_') !== false){

                                                $key      = str_replace('filter_', 'pa_', $key);
                                                $key_term = get_term_by('slug',$value,$key);

                                                if (is_object($key_term) && !is_wp_error($key_term)) {
                                                    $terms = array($key_term->slug);
                                                }

                                            } else {


                                                $key = 'pa_'.$key;
                                                $terms = explode(',', $value);
                                            }

                                            if (!empty($terms)) {
                                                $tax_query[] = array(
                                                    'taxonomy' => $key,
                                                    'field'    => 'slug',
                                                    'terms'    => $terms,
                                                    'operator' => 'IN'
                                                );

                                                $terms_name = array();

                                                foreach ($terms as $term) {
                                                    $term = get_term_by('slug',$term,$key);

                                                    if (is_object($term) && !is_wp_error($term)) {
                                                        array_push($terms_name, $term->name);
                                                    }
                                                }

                                                if (!empty($terms_name)) {
                                                    $filter_breadcrumbs[] = '<span class="breadcrumbs-item">'.ucfirst($key_display).': '.implode(', ', $terms_name).'</span>';
                                                }

                                            }
                                        }

                                    break;
                                }
                            }
                        }

                    }

                    $tax_query[] = array(
                        'taxonomy'  => 'product_visibility',
                        'terms'     => array( 'exclude-from-catalog' ),
                        'field'     => 'name',
                        'operator'  => 'NOT IN',
                    );

                    // vehicle filter
                    if (isset($_POST['vin']) && !empty($_POST['vin'])) {
                        $vehicle_attributes = enovathemes_addons_vin_decoder($_POST['vin']);
                        $vehicle_data       = enovathemes_addons_vin_decoder($_POST['vin'],true);

                        if ($vehicle_attributes == false || empty($vehicle_attributes) || (isset($vehicle_attributes['error']) && $vehicle_attributes['error'])) {
                            $args['post__in'] = array(0);
                            $products_output = '<p class="vin-error">'.$vehicle_attributes['error'].'</p>';
                        }

                    } else {
                        $vehicle_attributes = vehicle_set_from_cookies_if_empty($vehicle_attributes);
                    }

                    $vehicles = vehicle_filter_component($vehicle_attributes);

                    if ($vehicles && !empty($vehicles)) {

                        $tax_query[] = array(
                            "taxonomy" => "vehicles",
                            "field" => "term_id",
                            "terms" => $vehicles,
                            "operator" => "IN",
                        );

                    }

                    if (isset($_POST['vin']) && !empty($_POST['vin']) && ($vehicles == false || empty($vehicles))) {
                        $args['post__in']   = array(0);
                    }

                    if (!empty($tax_query)) {
                        $tax_query = array_unique($tax_query,SORT_REGULAR);
                        if (count($tax_query) > 1) {
                            $tax_query['relation'] = 'AND';
                        }
                        $args['tax_query'] = $tax_query;
                    }

                    if (!empty($meta_query)) {
                        $meta_query = array_unique($meta_query,SORT_REGULAR);
                        if (count($meta_query) > 1) {
                            $meta_query['relation'] = 'AND';
                        }
                        $args['meta_query'] = $meta_query;
                    }

                    $output      = array();
                    $product_IDs = [];
                    $total       = 0;

                    $query_filter = (empty($filter)) ? (($vehicles && !empty($vehicles)) ? true : false) : true;

                    $query_results  = new WP_Query($args);

                    $include_universal_in_search  = (get_theme_mod('include_universal_in_search') != null && !empty(get_theme_mod('include_universal_in_search'))) ? "true" : "false";

                    if ($include_universal_in_search == "true") {

                        $universal_products = enovathemes_addons_universal_products();

                        if (!is_wp_error($universal_products)) {
                            foreach($universal_products as $product){
                                $product_IDs[] = $product;
                            }

                            $total +=count($product_IDs)   ;     
                        }

                    }

                    if ($query_results->have_posts()) {
                        $product_IDs = array_merge($product_IDs,$query_results->posts);
                        $total += $query_results->post_count;
                    }   elseif(!is_wp_error($universal_products)){

                        $args['tax_query'] = array_filter($args['tax_query'], function($item) {
                            return !(is_array($item) && isset($item['taxonomy']) && $item['taxonomy'] === 'vehicles');
                        });

                    }   elseif(empty($product_IDs)) {

                        $query_filter = false;

                        $products_output  = '<li class="no-products"><p>'.esc_html__("No products found matching the filter criteria.","enovathemes-addons").'</p>';

                        $product_notfound_form = get_theme_mod('product_notfound_form');

                        if (!isset($product_notfound_form) || empty($product_notfound_form)) {
                            $products_output .= '<a href="'.$shop_link.'" class="button et-button medium">'.esc_html__("Go back to shop","enovathemes-addons").'</a>';
                        }

                        $products_output .= '</li>';

                    }

                    if ($total) {
                    
                        $current      = (isset($_POST['page']) && !empty($_POST['page'])) ? $_POST['page'] : 1;
                        $pages        = ceil($total/$product_number);
                        $max          = ceil($total/$product_number);

                        if(!$pages){$pages = 1;}

                        /*Found products
                        ----------------------------------*/

                            $layout = (isset($_POST['data_shop']) && !empty($_POST['data_shop'])) ? ((has_filter('efp_layout_filter')) ? apply_filters('efp_layout_filter',$_POST['data_shop'],10,1) : $shop_layout) : $shop_layout;

                            if ($layout == false) {
                                $layout = $shop_layout;
                            }

                            if (empty($products_output)) {

                                foreach ($atts['attributes'] as $att => $opt) {
                                    $name = $opt['name'];
                                    if ($name != 'price' && $name != 'rating') {
                                        $$name = array();
                                    }
                                }

                                if (!empty($product_IDs)) {

                                    $product_IDs = array_unique($product_IDs);

                                    foreach ($product_IDs as $product_ID) {

                                        if ($query_filter) {
                                            foreach ($atts['attributes'] as $att => $opt) {

                                                $name = $opt['name'];

                                                switch ($name){
                                                    case 'ca':


                                                        $product_terms = wp_get_post_terms($product_ID,'product_cat',array( 'fields' => 'ids' ));

                                                        if (!is_wp_error($product_terms) && !empty($product_terms)) {

                                                            foreach ($product_terms as $id) {
                                                                ${$name}[]= $id;
                                                            }
                                                        }

                                                    break;

                                                    default:

                                                        $product_terms = wp_get_post_terms($product_ID,'pa_'.$name,array( 'fields' => 'all' ));

                                                        if (!is_wp_error($product_terms) && !empty($product_terms)) {

                                                            foreach ($product_terms as $term => $term_opt) {
                                                                ${$name}[$term_opt->term_id] = array($term_opt->name,$term_opt->slug);
                                                            }
                                                        }

                                                    break;
                                                }
                                            }
                                        }

                                    }
                                    
                                    if ($query_filter) {
                                        foreach ($atts['attributes'] as $att => $opt) {

                                            $name = $opt['name'];

                                            $$name = (!empty($$name)) ? array_unique($$name,SORT_REGULAR) : false;

                                            switch ($name){
                                                case 'ca':
                                                    $filter_output[$name] = enovathemes_addons_render_category_filter_attribute($opt,$cache,$$name);
                                                break;
                                                case 'price':
                                                    $filter_output[$name] = enovathemes_addons_render_price_filter_attribute($product_IDs);
                                                break;
                                                case 'rating':
                                                    $filter_output[$name] = enovathemes_addons_render_rating_filter_attribute($product_IDs);
                                                break;
                                                default:
                                                    $filter_output[$name] = enovathemes_addons_render_attribute_filter_attribute($opt,$$name);
                                                break;
                                            }
                                           
                                        }
                                    }

                                    $page = (isset($_POST['page']) && !empty($_POST['page'])) ? $_POST['page'] : ((get_query_var('paged')) ? get_query_var('paged') : 1);
                                    
                                    $args = array(
                                        'post_type'           => 'product',
                                        'post_status'         => 'publish',
                                        'ignore_sticky_posts' => 0,
                                        'posts_per_page'      => $product_number,
                                        'paged'               => $page,
                                        'fields'              => 'ids',
                                        'post__in'            => $product_IDs
                                    );

                                    $args['orderby'] = $orderby;
                                    $args['order']   = $order;

                                    if (!empty($meta_key)) {
                                        $args['meta_key'] = $meta_key;
                                    }

                                    $query_results = null;
                                    $query_results = new WP_Query($args);

                                    if ($query_results->have_posts()) {

                                        while ($query_results->have_posts() ) {
                                            $query_results->the_post();

                                            global $product;

                                            $products_output .= '<li class="'.join( ' ', get_post_class('post')).'" id="product-'.$product->get_id().'">';

                                                $products_output .='<div class="post-inner et-item-inner">';

                                                    if(get_option( 'woocommerce_enable_ajax_add_to_cart' ) === "yes"){
                                                        $products_output .='<div class="ajax-add-to-cart-loading">';
                                                            $products_output .='<svg viewBox="0 0 56 56"><circle class="loader-path" cx="28" cy="28" r="20" /></svg>';
                                                            $products_output .='<svg viewBox="0 0 511.999 511.999" class="tick"><path d="M506.231 75.508c-7.689-7.69-20.158-7.69-27.849 0l-319.21 319.211L33.617 269.163c-7.689-7.691-20.158-7.691-27.849 0-7.69 7.69-7.69 20.158 0 27.849l139.481 139.481c7.687 7.687 20.16 7.689 27.849 0l333.133-333.136c7.69-7.691 7.69-20.159 0-27.849z"/></svg>';
                                                        $products_output .='</div>';
                                                    }
                                                    
                                                    $products_output .= mobex_enovathemes_loop_product_thumbnail($layout);
                                                    $products_output .= mobex_enovathemes_loop_product_title($layout);
                                                    $products_output .= mobex_enovathemes_loop_product_inner_close($layout);

                                                $products_output .='</div>';
                                            $products_output .= '</li>';

                                        }

                                    }

                                    wp_reset_postdata();

                                }

                            }

                        /*Navigation
                        ----------------------------------*/

                            if ($pages > 1) {

                                if ($shop_navigation == "pagination") {

                                    $nav_output .= '<ul class="page-numbers ajax">';
                                        if ($current > 1) {
                                            $nav_output .= '<li><a class="prev page-numbers" href="#"></a></li>';
                                        }

                                        $inc = ($current >= 5 && $pages > 8) ? $current - 3 : 1;
                                        $inc = ($current > ($pages - 3) && $pages > 8) ? $pages - 8 : $inc;

                                        for ($i=$inc; $i <= $pages; $i++) {

                                            if ($pages > 8 && $i == (4+$inc) && $i != $current && $current != ($pages - 3)) {
                                                $nav_output .= '<li><span class="page-numbers dots">…</span></li>';
                                                $i = $pages - 3;
                                            } else {
                                                if ($current == $i) {
                                                    $nav_output .= '<li><span aria-current="page" class="page-numbers current">'.$i.'</span></li>';
                                                } else {
                                                    $nav_output .= '<li><a class="page-numbers" data-page="'.$i.'" href="'.$shop_link.'page/'.$i.'/">'.$i.'</a></li>';
                                                }
                                            }

                                        }
                                        if ($current != $pages) {
                                            $nav_output .= '<li><a class="next page-numbers" href="#"></a></li>';
                                        }
                                    $nav_output .= '</ul>';

                                }

                            } else {

                                $nav_output = '<ul class="page-numbers ajax"></ul>';
                            }

                        /*Found results
                        ----------------------------------*/

                            // phpcs:disable WordPress.Security
                            if ( 1 === intval( $total ) ) {
                                $found_output = esc_html__( 'Showing the single result', 'enovathemes-addons' );
                            } elseif ( $total <= $product_number || -1 === $product_number ) {
                                /* translators: %d: total results */
                                $found_output = sprintf( _n( 'Showing all %d result', 'Showing all %d results', $total, 'enovathemes-addons' ), $total );
                            } else {

                                if ($shop_navigation == 'pagination') {
                                    $first = ( $product_number * $current ) - $product_number + 1;
                                    $last  = min( $total, $product_number * $current );
                                } else {
                                    if ($current > 1) {
                                        $first = 1;
                                        $last  = min( $total, $product_number * $current );
                                    } else {
                                        $first = ( $product_number * $current ) - $product_number + 1;
                                        $last  = min( $total, $product_number * $current );
                                    }
                                }

                                $found_output = sprintf(
                                    _nx(
                                        'Showing %1$s–%2$s of %3$s result',
                                        'Showing %1$s–%2$s of %3$s results',
                                        $total,
                                        'with first and last result',
                                        'enovathemes-addons'
                                    ),
                                    number_format_i18n( $first ),
                                    number_format_i18n( $last ),
                                    number_format_i18n( $total )
                                );


                            }

                    }

                    /*Breadcrumbs
                    ----------------------------------*/

                        if (!empty($filter_breadcrumbs)) {
                            $filter_breadcrumbs = array_unique($filter_breadcrumbs,SORT_REGULAR);
                            $bread_output = filter_breadcrumbs_output($filter_breadcrumbs,' cs');
                        }
                        
                        if (isset($_POST['ca'])) {
                            $title_section_breadcrumbs_output = generate_breadcrumbs($_POST['ca']);
                        }

                    /*Cat description
                    ----------------------------------*/

                        $category_args = array(
                            'hide_empty' => true,
                            'meta_key'   => 'order',
                            'orderby'    => 'meta_value_num',
                            'parent'     => 0
                        );

                        if (isset($_POST['ca']) && !empty($_POST['ca'])) {

                            $the_category = get_term_by('slug', $_POST['ca'], 'product_cat');

                            if ($the_category) {

                                $cat_description = $the_category->description;
                                $cat_title = $the_category->name;

                                $category_id = get_term_by('slug', $_POST['ca'], 'product_cat');

                                if ($category_id) {

                                    $children = get_term_children($category_id->term_id,'product_cat');

                                    if (!is_wp_error($children) && is_array($children) && !empty($children)) {
                                        $category_args['include'] = $children;
                                        unset($category_args['parent']);
                                    } else {
                                        $category_args = array();
                                    }

                                }

                            }
                        } else {

                            $category_args = array(
                                'hide_empty' => true,
                                'meta_key'   => 'order',
                                'orderby'    => 'meta_value_num',
                                'parent'     => 0
                            );

                        }

                        if (!empty($category_args)) {
                            
                            $category_terms = get_terms( 'product_cat', $category_args);

                            if (!empty($category_terms) && !is_wp_error($category_terms)) {

                                $cat_children .= '<div class="loop-categories-wrapper swiper">';
                                    
                                    $cat_children .= '<ul id="loop-categories" class="loop-categories swiper-wrapper">';
                                        
                                        foreach ( $category_terms as $term ){
                                            $cat_children .= '<li class="category-item swiper-slide"><a href="'.get_term_link($term->term_id,'product_cat').'">';
                                                $cat_children .= '<div class="image-container">';
                                                    $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                                                    if ($thumbnail_id) {
                                                        $cat_children .= mobex_enovathemes_build_post_media('thumbnail',$thumbnail_id,'product');
                                                    }
                                                $cat_children .= '</div>';
                                                $cat_children .= '<h5>'.esc_html($term->name).'</h5>';
                                            $cat_children .= '</a></li>';
                                        }

                                    $cat_children .= '</ul>';

                                $cat_children .= '</div>';

                                $cat_children .= '<div class="swiper-button swiper-button-prev loop-categories-prev"></div><div class="swiper-button swiper-button-next loop-categories-next"></div>';

                            }

                        }

                    if ($query_filter == false) {
                        $filter_output = get_transient('enovathemes-product-filter');

                        if ($shop_navigation == "pagination" && !isset($_POST['page'])) {
                            $nav_output = get_transient('enovathemes-products-navigation-pagination');
                        }

                    }

                    $output['found']              = $found_output;
                    $output['products']           = $products_output;
                    $output['filter_output']      = $filter_output;
                    $output['nav']                = $nav_output;
                    $output['total']              = $total;
                    $output['max']                = $max;
                    $output['next_posts']         = $shop_link.'page/2/';
                    $output['bread']              = $bread_output;
                    $output['breadcrumbs']        = $title_section_breadcrumbs_output;
                    $output['vehicle_attributes'] = (isset($_POST['vin']) && !empty($_POST['vin']) && !empty($vehicle_attributes) && !isset($vehicle_attributes['error'])) ? $vehicle_attributes : '';
                    $output['vehicle_data']       = (isset($output['vehicle_attributes']) && !empty($output['vehicle_attributes'])) ? $vehicle_data : '';
                    $output['cat_description']    = $cat_description;
                    $output['cat_children']       = $cat_children;
                    $output['cat_title']          = $cat_title;
                    $output['dev']                = $args;


                    echo json_encode($output);

                }
            }

            die();
        }
        add_action( 'wp_ajax_filter_attributes', 'filter_attributes' );
        add_action( 'wp_ajax_nopriv_filter_attributes', 'filter_attributes' );


    /* Filter by select action
    /*----------------*/

        function filter_select() {

            $next = (isset($_POST["next"]) && !empty($_POST["next"])) ? $_POST["next"] : false;

            $output = array();
            $output_terms  = '';

            $atts       = (isset($_POST["atts"]) && !empty($_POST["atts"])) ? json_decode( stripslashes ($_POST["atts"]),true ) : false;
            $tax_query  = array();

            if ($atts) {

                foreach ($atts as $param => $value) {
                    if ($param == 'category') {
                        $tax_query[] = array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'slug',
                            'terms'    => $value,
                            'operator' => 'IN'
                        );
                    } else {
                        $tax_query[] = array(
                            'taxonomy' => 'pa_'.$param,
                            'field'    => 'slug',
                            'terms'    => $value,
                            'operator' => 'IN'
                        );
                    }
                }

            }

            $args = array(
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 0,
                'posts_per_page'      => -1,
            );

            if (!empty($tax_query)) {
                $tax_query = array_unique($tax_query,SORT_REGULAR);
                if (count($tax_query) > 1) {
                    $tax_query['relation'] = 'AND';
                }
                $args['tax_query'] = $tax_query;
            }

            $query_results  = new WP_Query($args);

            if ($query_results->have_posts() && $next) {

                $next  = (($next == 'category') ? 'product_cat' : 'pa_'.$next);
                $terms  = array();
                
                while ($query_results->have_posts() ) {
                    $query_results->the_post();
                    $id = get_the_ID();
                    $product_terms = get_the_terms($id,$next);
                    if (!is_wp_error($product_terms) && !empty($product_terms)) {
                        foreach ($product_terms as $term) {
                            $terms[$term->slug] = $term->name;
                        }
                    }
                }
                wp_reset_postdata();

                $terms         = array_unique($terms,SORT_REGULAR);

                if (!empty($terms)) {
                    foreach ($terms as $key => $value) {
                        $output_terms .= '<option value="'.$key.'">'.$value.'</option>';
                    }
                }

            } else {
                $output_terms = '';
            }

            if (!empty($output_terms)) {
                $output['terms'] = $output_terms;
            }

            $output['args'] = $args;

            echo json_encode($output);

            die();
        }
        add_action( 'wp_ajax_filter_select', 'filter_select' );
        add_action( 'wp_ajax_nopriv_filter_select', 'filter_select' );

/* Shortcodes
/*----------------*/

    add_shortcode('year',function(){
        return date("Y");
    });

    // Wishlist table shortcode
    add_shortcode('wishlist', 'wishlist');
    function wishlist( $atts, $content = null ) {
        extract(shortcode_atts(array(), $atts));

        $class      = ['et-woo-products', 'only', 'post-layout', 'grid'];
        $list_class = ['loop-products', 'loop-posts', 'products'];
        
        $attributes = [
            'data-columns'          => 5,
            'data-tab-land-columns' => 3,
            'data-tab-port-columns' => 2,
        ];

        $attributes_output = '';

        foreach ($attributes as $key => $value) {
            $attributes_output .= $key.'="'.$value.'" ';
        }

        $output  = '<div class="wishlist-table"><div class="swiper-container" ' . $attributes_output . '>';
        $output .= '<div class="' . esc_attr(implode(' ', $class)) . '">';
        $output .= '<ul class="' . esc_attr(implode(' ', $list_class)) . '">';

        $col = 5;

        if (class_exists('\Detection\MobileDetect')) {

            $detect  = new \Detection\MobileDetect;

            if ($detect->isMobile() && !$detect->isTablet()) {
                $col = 2;
            } elseif ($detect->isTablet()){
                $col = 3;
            }

        }

        for ($i = 1; $i <= $col; $i++) {

            $output .= '<li class="product post placeholder">';
                $output .= '<svg class="post-image" viewBox="0 0 300 300"><rect width="100%" height="100%" rx="6%" ry="6%" /></svg>';
                $output .= '<span class="placeholder-body">';
                    $output .= '<span class="post-wcq"></span>';
                    $output .= '<span class="post-title"></span>';
                    $output .= '<span class="star-rating empty"></span>';
                    $output .= '<span class="price"></span>';
                    $output .= '<span class="button"></span>';
                $output .= '</span>';
            $output .= '</li>';
        }

        $output .= '</ul></div></div></div>';

        return $output;
    }

    function et_products($atts, $content = null) {

        $shortcode_atts = shortcode_atts(
            array(
                'ajax'                  => 'false',
                'layout'                => 'grid',
                'navigation_type'       => 'arrows',
                'navigation_position'   => 'side',
                'autoplay'              => 'false',
                'carousel'              => 'false',
                'columns'               => '1',
                'columns_tab_port'      => '',
                'columns_tab_land'      => '',
                'rows'                  => '1',
                'quantity'              => '12',
                'category'              => '',
                'attribute'             => '',
                'ids'                   => '',
                'operator'              => 'IN',
                'orderby'               => 'date',
                'order'                 => 'ASC',
                'type'                  => 'recent',
                'wishlist'              => 'false',
                'unique_id'             => rand()
        ), $atts);

        if (class_exists('Woocommerce')) {

            global $woocommerce;

            if ($shortcode_atts['layout'] == "list" && $shortcode_atts['columns'] > 5) {
                $shortcode_atts['columns'] = 4;
            } elseif($shortcode_atts['layout'] == "grid" && $shortcode_atts['columns'] > 6){
                $shortcode_atts['columns'] = 6;
            }

            if (empty($shortcode_atts['columns_tab_port'])) {

                if ($shortcode_atts['layout'] == "grid") {
                    $shortcode_atts['columns_tab_port'] = 3;
                } else {
                    $shortcode_atts['columns_tab_port'] = 2;
                }

            } elseif($shortcode_atts['columns_tab_port'] > 3 && $shortcode_atts['layout'] == "grid") {
                $shortcode_atts['columns_tab_port'] = 3;
            } elseif($shortcode_atts['columns_tab_port'] > 2 && $shortcode_atts['layout'] == "list") {
                $shortcode_atts['columns_tab_port'] = 2;
            }

            if (empty($shortcode_atts['columns_tab_land'])) {
                if ($shortcode_atts['layout'] == "grid") {
                    $shortcode_atts['columns_tab_land'] = 4;
                } else {
                    $shortcode_atts['columns_tab_land'] = 3;
                }
            } elseif($shortcode_atts['columns_tab_land'] > 4 && $shortcode_atts['layout'] == "grid") {
                $shortcode_atts['columns_tab_land'] = 4;
            } elseif($shortcode_atts['columns_tab_land'] > 3 && $shortcode_atts['layout'] == "list") {
                $shortcode_atts['columns_tab_land'] = 3;
            }

            extract($shortcode_atts);


            $tax_query = array();

            $query_options = array(
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'orderby'             => $orderby,
                'order'               => $order,
                'posts_per_page'      => absint($quantity),
            );

            $tax_query[] =  array(
                'taxonomy'  => 'product_visibility',
                'terms'     => array( 'exclude-from-catalog' ),
                'field'     => 'name',
                'operator'  => 'NOT IN'
            );

            if ($type == "custom"){
                if ( ! empty( $ids ) ) {
                    $query_options['post__in'] = array_map( 'trim', explode( ',', $ids ) );
                }
            } elseif ($type == "featured"){

                $tax_query[] =  array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => 'featured',
                    'operator' => 'IN',
                );

            } elseif($type == "sale"){

                $sales_ids = wc_get_product_ids_on_sale();

                if (!is_wp_error($sales_ids) && !empty($sales_ids)) {
                    $query_options['post__in'] = array_merge( array( 0 ), $sales_ids );
                }

            } elseif($type == "best_selling"){

                $query_options['orderby']  = 'meta_value_num';
                $query_options['post__in'] = 'total_sales';
                
            } elseif($type == "attribute"){

                $tax_query[] =  array(
                    'taxonomy' => strstr( $attribute, 'pa_' ) ? sanitize_title( $attribute ) : 'pa_' . sanitize_title( $attribute ),
                    'terms'    => array_map( 'sanitize_title', explode( ',', $filter ) ),
                    'field'    => 'slug',
                );
                
            }

            if ($type != "custom" && isset($category) && !empty($category)) {

                $tax_query[] = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => explode(',',$category),
                    'operator' => $operator
                );

            }

            $output = '';

            if (!empty($tax_query)) {
                $tax_query['relation'] = 'AND';

                $query_options['tax_query'] = $tax_query;
            }

            if ($ajax == "false") {
                $output = woo_products_ajax($shortcode_atts,$query_options,false);
            } else {
                $output = woo_products_ajax_placeholder($shortcode_atts,$query_options);
            }

            if (!empty($output)) {
                return $output;
            }

        }
    }
    add_shortcode('et_products', 'et_products');

    function et_social_links($atts, $content = null) {

        extract(shortcode_atts(
            array(
                'target'      => '_self',
                'size'        => 'small',
                'stretching'  => 'false'
            ), $atts)
        );

        static $id_counter = 1;

        $output      = '';

        $class = array();

        $social_links_array = enovathemes_addons_social_icons(get_template_directory().'/images/icons/social/');

        $class[] = 'et-social-links';
        $class[] = 'styling-original-true';
        $class[] = 'size-'.$size;
        $class[] = 'stretching-'.$stretching;

        $output .= '<div class="'.implode(" ", $class).'">';
            foreach($atts as $social => $href) {
                if(in_array($social, $social_links_array) && !empty($href)) {
                    $output .='<a class="'.$social.'" href="'.$href.'" target="'.esc_attr($target).'" title="'.$social.'"></a>';
                }
            }
        $output .= '</div>';

        $id_counter++;

        return $output;
    }
    add_shortcode('et_social_links', 'et_social_links');

/* Posts shortcode actions
/*----------------*/

    function et_get_transient_keys_with_prefix( $prefix ) {
        global $wpdb;

        $prefix = $wpdb->esc_like( '_transient_' . $prefix );
        $sql    = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";
        $keys   = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A );

        if ( is_wp_error( $keys ) ) {
            return [];
        }

        return array_map( function( $key ) {
            // Remove '_transient_' from the option name.
            return str_replace('_transient_','',$key['option_name']);
        }, $keys );
    }

    function delete_transients_with_prefix( $prefix ) {
        foreach ( et_get_transient_keys_with_prefix( $prefix ) as $key ) {
            delete_transient( $key );
        }
    }

    function et_posts_ajax_query($atts="",$query_options=""){

        extract($atts);

        $posts = new WP_Query($query_options);

        if($posts->have_posts()){

            $output     = '';
            $class      = array();
            $list_class = array();
            $attributes = array();

            $list_class[] = 'only-posts';
            $list_class[] = 'loop-posts';

            $class[] = 'et-shortcode-posts';
            $class[] = 'et-posts';
            $class[] = 'post-layout';
            $class[] = $layout;

            if ($layout == "grid-2" || $layout == "grid-3") {
                $class[] = 'grid';
            }

            $thumbnail = ($layout == "full") ? 'post_img' : 'medium';

            if ($carousel == "true") {
                $class[]      = 'swiper';
                $list_class[] = 'swiper-wrapper';
                if (isset($autoplay) && !empty($autoplay)) {
                    $attributes[] = 'data-autoplay="'.esc_attr($autoplay).'"';
                }
                $attributes[] = 'data-navigation-type="'.esc_attr($navigation_type).'"';
                $attributes[] = 'data-arrows-pos="'.esc_attr($navigation_position).'"';
            }

            $attributes[] = 'data-columns="'.esc_attr($columns).'"';
            $attributes[] = 'data-tab-land-columns="'.esc_attr($columns_tab_land).'"';
            $attributes[] = 'data-tab-port-columns="'.esc_attr($columns_tab_port).'"';

            $output .= '<div class="swiper-container" '.implode(' ', $attributes).'>';
                $output .= '<div id="et-shortcode-posts-'.$unique_id.'" class="'.esc_attr(implode(' ', $class)).'">';
                    $output .= '<div class="'.esc_attr(implode(' ', $list_class)).'">';
                            
                        while ($posts->have_posts() ) {
                            $posts->the_post();
                            $output .= mobex_enovathemes_post($layout,$excerpt,$title_length,$thumbnail);
                        }

                        wp_reset_postdata();

                    $output .= '</div>';
                $output .= '</div>';

                if ($carousel == "true") {
                    $output .= '<div id="prev-'.$unique_id.'" class="swiper-button swiper-button-prev"></div><div id="next-'.$unique_id.'" class="swiper-button swiper-button-next"></div>';
                    $output .= '<div id="swiper-pagination-'.$unique_id.'" class="swiper-pagination"></div>';
                }

            $output .= '</div>';

        }

        if ( ! empty( $output ) ) {

            return $output;

        } else {

            return new WP_Error( 'no_posts', esc_html__( 'No posts.', 'enovathemes-addons' ) );

        }
    }

    function posts_ajax_placeholder($atts,$query_options) {

        extract($atts);

        if (!$quantity) {
            return false;
        }

        $class      = ['et-shortcode-posts', 'et-posts', 'ajax', 'post-layout', $layout];
        $list_class = ['only-posts', 'loop-posts'];
        
        $attributes = [
            'data-columns' => esc_attr($columns),
            'data-tab-land-columns' => esc_attr($columns_tab_land),
            'data-tab-port-columns' => esc_attr($columns_tab_port),
            'data-navigation-type' => esc_attr($navigation_type),
            'data-arrows-pos'      => esc_attr($navigation_position)
        ];

        if ($layout == "grid-2" || $layout == "grid-3") {
            $class[] = 'grid';
        }

        if ($carousel == "true") {
            $class[] = 'swiper';
            $list_class[] = 'swiper-wrapper';
            if (!empty($autoplay)) {
                $attributes['data-autoplay'] = esc_attr($autoplay);
            }
        }

        $attributes['data-atts']  = base64_encode(json_encode($atts));
        $attributes['data-query'] = base64_encode(json_encode($query_options));


        $attributes_output = '';

        foreach ($attributes as $key => $value) {
            $attributes_output .= $key.'="'.$value.'" ';
        }

        $counter = 1;

        $col = $columns;

        $style = '';

        if (class_exists('\Detection\MobileDetect')) {

            $detect  = new \Detection\MobileDetect;

            if ($detect->isMobile() && !$detect->isTablet()) {
                $col = (($layout == 'list' || $layout == 'full') ? 1 : 3);
            } elseif ($detect->isTablet()){
                $col = $columns_tab_land + 1;
            }

        }

        $output  = '<div class="swiper-container" ' . $attributes_output . '>';
        $output .= '<div id="et-shortcode-posts-' . $unique_id . '" class="' . esc_attr(implode(' ', $class)) . '">';
        $output .= '<ul class="' . esc_attr(implode(' ', $list_class)) . '">';

        $placeholder = $carousel ? $col : min($quantity, $col * 2);

        for ($i = 1; $i <= $placeholder; $i++) {
            
            $swiper_slide = '';

            $output .= '<li class="post swiper-slide placeholder">';
                    if ($layout == 'full') {
                        $output .= '<div class="post-image post-media"><svg class="post-image" viewBox="0 0 1320 560"><rect width="100%" height="100%" /></svg></div>';
                    } else {
                        $output .= '<div class="post-image post-media"><svg class="post-image" viewBox="0 0 300 200"><rect width="100%" height="100%" /></svg></div>';
                    }
                    $output .= '<span class="placeholder-body post-body">';
                        if ($layout != 'grid-2') {
                            $output .= '<span class="post-meta"></span>';
                        }
                        $output .= '<span class="post-title"></span>';
                        if (in_array($layout, array('full','grid','list')) && $excerpt > 0) {
                            $output .= '<span class="post-excerpt"></span>';
                        }
                        if ($layout == 'list' || $layout == 'full') {
                            $output .= '<span class="button post-read-more"></span>';
                        }
                    $output .= '</span>';
                    if (!in_array($layout, array('grid-2','list','full'))) {
                        $output .= '<span class="button post-read-more"></span>';
                    }
                $output .= '</li>';

            $counter++;
        }

        $output .= '</ul></div></div>';
        return $output;
    }

    function et_posts_ajax($atts="",$query_options="",$ajax=true){

        if (isset($_POST['ajax']) && !empty($_POST['ajax'])) {

            $ajax       = 'true';
            $ajax_calls = $_POST['ajax_calls'];
            $ajax_calls = explode(',', $ajax_calls);

            $output = array();

            foreach($ajax_calls as $args){
                $args             = explode('|', $args);
                $atts             = json_decode(base64_decode($args[1]),true);
                $query_options    = json_decode(base64_decode($args[2]),true);
                $output[$args[0]] = et_posts_ajax_query($atts,$query_options);
                
            }
            if (!empty($output)) {
                echo json_encode($output);
            }

        } else {
            return et_posts_ajax_query($atts,$query_options);
        }

        if ($ajax == 'true') {
            die();
        }
    }
    add_action( 'wp_ajax_et_posts_ajax', 'et_posts_ajax' );
    add_action( 'wp_ajax_nopriv_et_posts_ajax', 'et_posts_ajax' );

/* Woocommerce shortcode actions
/*----------------*/

    function woo_products_ajax_placeholder($atts,$query_options) {

        extract($atts);

        if (!$quantity) {
            return false;
        }

        $class      = ['et-woo-products', 'only', 'ajax', 'post-layout', $layout];
        $list_class = ['loop-products', 'loop-posts', 'products'];
        
        $attributes = [
            'data-columns' => esc_attr($columns),
            'data-tab-land-columns' => esc_attr($columns_tab_land),
            'data-tab-port-columns' => esc_attr($columns_tab_port),
            'data-navigation-type' => esc_attr($navigation_type),
            'data-arrows-pos'      => esc_attr($navigation_position)
        ];

        if ($carousel == "true") {
            $class[] = 'swiper';
            $list_class[] = 'swiper-wrapper';
            if (!empty($autoplay)) {
                $attributes['data-autoplay'] = esc_attr($autoplay);
            }
        }

        $attributes['data-atts']  = base64_encode(json_encode($atts));
        $attributes['data-query'] = base64_encode(json_encode($query_options));


        $attributes_output = '';

        foreach ($attributes as $key => $value) {
            $attributes_output .= $key.'="'.$value.'" ';
        }

        $counter = 1;

        $col = $columns;

        $style = '';

        if (class_exists('\Detection\MobileDetect')) {

            $detect  = new \Detection\MobileDetect;

            if ($detect->isMobile() && !$detect->isTablet()) {
                $col = ($layout == 'list' ? 2 : 3);
            } elseif ($detect->isTablet()){
                $col = $columns_tab_land + 1;
            }

        }

        $output  = '<div class="swiper-container" ' . $attributes_output . '>';
        $output .= '<div id="et-woo-products-' . $unique_id . '" class="' . esc_attr(implode(' ', $class)) . '">';
        $output .= '<ul class="' . esc_attr(implode(' ', $list_class)) . '">';

        $placeholder = $carousel ? $col : min($quantity, $col * 2);

        for ($i = 1; $i <= $placeholder; $i++) {
            
            $swiper_slide = '';

            $output .= '<li class="product post swiper-slide placeholder">';
                    $output .= '<svg class="post-image" viewBox="0 0 300 300"><rect width="100%" height="100%" rx="6%" ry="6%" /></svg>';
                    $output .= '<span class="placeholder-body">';
                        $output .= '<span class="post-wcq"></span>';
                        $output .= '<span class="post-title"></span>';
                        $output .= '<span class="star-rating empty"></span>';
                        $output .= '<span class="price"></span>';
                        $output .= '<span class="button"></span>';
                    $output .= '</span>';
                $output .= '</li>';

            $counter++;
        }

        $output .= '</ul></div></div>';
        return $output;
    }

    function woo_products_ajax_query($atts="",$query_options=""){

        extract($atts);

        $output = '';

        $products = new WP_Query($query_options);

        if($products->have_posts()){

            $current_rate = (isset($atts['currency']) && !empty($atts['currency'])) ? et__get_currency_rate($atts['currency']) : 1;

            $class      = array();
            $list_class = array();
            $attributes = array();

            $list_class[] = 'loop-products';
            $list_class[] = 'loop-posts';
            $list_class[] = 'products';

            $class[] = 'et-woo-products';
            $class[] = 'only';
            $class[] = 'post-layout';
            $class[] = $layout;

            if ($carousel == "true") {
                $class[]      = 'swiper';
                $list_class[] = 'swiper-wrapper';
                if (isset($autoplay) && !empty($autoplay)) {
                    $attributes[] = 'data-autoplay="'.esc_attr($autoplay).'"';
                }
                $attributes[] = 'data-navigation-type="'.esc_attr($navigation_type).'"';
                $attributes[] = 'data-arrows-pos="'.esc_attr($navigation_position).'"';
            }

            $attributes[] = 'data-columns="'.esc_attr($columns).'"';
            $attributes[] = 'data-tab-land-columns="'.esc_attr($columns_tab_land).'"';
            $attributes[] = 'data-tab-port-columns="'.esc_attr($columns_tab_port).'"';

            $counter = 1;

            $output .= '<div class="swiper-container" '.implode(' ', $attributes).'>';
                $output .= '<div id="et-woo-products-'.$unique_id.'" class="'.esc_attr(implode(' ', $class)).'">';
                    $output .= '<ul class="'.esc_attr(implode(' ', $list_class)).'">';

                        while ($products->have_posts() ) {
                            $products->the_post();

                            global $product;

                            $swiper_slide = '';

                            if (($counter % 2 == 1 && $rows == 2) || ($counter % 3 == 1 && $rows == 3)){
                                $output .= '<li class="row-item swiper-slide"><ul>';
                            } else {
                                $swiper_slide = 'swiper-slide';
                            }

                            $output .= '<li class="product post '.$swiper_slide.'" data-product="'.$product->get_id().'" id="product-'.esc_attr($product->get_id()).'">';

                                $output .='<div class="post-inner et-item-inner">';

                                    if (isset($atts['wishlist']) && $atts['wishlist'] == 'true') {
                                        $output .='<span class="wishlist-remove"></span>';
                                    }

                                    if(get_option( 'woocommerce_enable_ajax_add_to_cart' ) === "yes"){
                                        $output .='<div class="ajax-add-to-cart-loading">';
                                            $output .='<svg viewBox="0 0 56 56"><circle class="loader-path" cx="28" cy="28" r="20" /></svg>';
                                            $output .= '<svg viewBox="0 0 511.999 511.999" class="tick"><path d="M506.231 75.508c-7.689-7.69-20.158-7.69-27.849 0l-319.21 319.211L33.617 269.163c-7.689-7.691-20.158-7.691-27.849 0-7.69 7.69-7.69 20.158 0 27.849l139.481 139.481c7.687 7.687 20.16 7.689 27.849 0l333.133-333.136c7.69-7.691 7.69-20.159 0-27.849z"/></svg>';
                                        $output .='</div>';
                                    }

                                     if (isset($atts['currency']) && !empty($atts['currency'])) {

                                        $base_price      = get_post_meta(get_the_ID(), '_price', true);
                                        $converted_price = $base_price * $current_rate;
                                        $current_currency = sanitize_text_field($atts['currency']);

                                        add_filter('woocommerce_get_price_html', function($price, $product) use ($converted_price, $current_currency) {
                                            return wc_price($converted_price, ['currency' => $current_currency]);
                                        }, 10, 2);

                                    }

                                    $output .= mobex_enovathemes_loop_product_thumbnail($layout,false);
                                    $output .= mobex_enovathemes_loop_product_title($layout);
                                    $output .= mobex_enovathemes_loop_product_inner_close($layout);
                                $output .='</div>';

                            $output .= '</li>';

                            if (($counter % 2 == 0 && $rows == 2) || ($counter % 3 == 0 && $rows == 3) || ($counter % 4 == 0 && $rows == 4)){
                                $output .= '</ul></li>';
                            }

                            $counter++;

                        }

                        wp_reset_postdata();

                    $output .= '</ul>';
                $output .= '</div>';


                if ($carousel == "true") {
                    $output .= '<div id="prev-'.$unique_id.'" class="swiper-button swiper-button-prev"></div><div id="next-'.$unique_id.'" class="swiper-button swiper-button-next"></div>';
                    $output .= '<div id="swiper-pagination-'.$unique_id.'" class="swiper-pagination"></div>';
                }


            $output .= '</div>';


        } else {
            $output .= '<p>'.esc_html__("No products found","enovathemes-addons").'</p>';
        }

        if ( ! empty( $output ) ) {
            return $output;
        } else {

            return new WP_Error( 'no_products', esc_html__( 'No products.', 'enovathemes-addons' ) );

        }
    }

    function woo_products_ajax($atts="",$query_options="",$ajax=true){
        
        if (isset($_POST['ajax']) && !empty($_POST['ajax'])) {

            $ajax       = 'true';
            $ajax_calls = $_POST['ajax_calls'];
            $currency   = $_POST['currency'];
            $ajax_calls = explode(',', $ajax_calls);

            $output = array();

            foreach($ajax_calls as $args){
                $args             = explode('|', $args);
                $atts             = json_decode(base64_decode($args[1]),true);
                $query_options    = json_decode(base64_decode($args[2]),true);
                
                if (!empty($currency)) {
                    $atts['currency'] = $currency;
                }

                $output[$args[0]] = woo_products_ajax_query($atts,$query_options);
                
            }
            if (!empty($output)) {
                echo json_encode($output);
            }

        } else {
            return woo_products_ajax_query($atts,$query_options);
        }

        if ($ajax) {
            die();
        }
    }
    add_action( 'wp_ajax_woo_products_ajax', 'woo_products_ajax' );
    add_action( 'wp_ajax_nopriv_woo_products_ajax', 'woo_products_ajax' );

/* AJAX
------------------*/
    
    function enovathemes_addons_footer_load($footer) {

        if (isset($_POST["footer"]) && !empty($_POST["footer"])){
           
            $footers = enovathemes_addons_footers();
            if (!is_wp_error($footers)) {
                $data = array();
                $data[$_POST["footer"]] = apply_filters('the_content', gzuncompress($footers[$_POST["footer"]]['content']));
                wp_send_json($data);
            }
            
        }
      
        die();
    }
    add_action( 'wp_ajax_footer_load', 'enovathemes_addons_footer_load');
    add_action( 'wp_ajax_nopriv_footer_load', 'enovathemes_addons_footer_load');


    function enovathemes_addons_megamenu_load($megamenues) {

        $pluginElementor = \Elementor\Plugin::instance();

        if (isset($_POST["megamenues"]) && !empty($_POST["megamenues"])){
            $megamenu  = enovathemes_addons_megamenus();
            $megamenues = explode("|", $_POST["megamenues"]);
            if (!is_wp_error($megamenu)) {
                $data = array();
                foreach ($megamenues as $mega_menu){
                    if (isset($megamenu[$mega_menu])) {

                        $content = (is_plugin_active( 'elementor/elementor.php' )) ? $pluginElementor->frontend->get_builder_content($mega_menu,false) : get_the_content($mega_menu);

                        $megamenu_html = '<div id="megamenu-'. $mega_menu . '" '.implode(' ', $megamenu[$mega_menu]['data']).'>';
                            $megamenu_html .= do_shortcode($content);
                        $megamenu_html .= '</div>';

                        $data[$mega_menu] = $megamenu_html;
                    }
                }
                if (!empty($data)) {
                    wp_send_json($data);
                }
            }

        }
      
        die();
    }
    add_action( 'wp_ajax_megamenu_load', 'enovathemes_addons_megamenu_load');
    add_action( 'wp_ajax_nopriv_megamenu_load', 'enovathemes_addons_megamenu_load');
?>