<?php
/**
 * Plugin Name: Maxus Xero Credit Sync
 * Description: Syncs B2BKing customer credit balances with outstanding Xero invoices. When invoices are paid in Xero, available credit updates automatically.
 * Version: 1.0.0
 * Author: Maxus Van Parts
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: maxus-xero-credit-sync
 */

defined( 'ABSPATH' ) || exit;

define( 'MXCS_VERSION', '1.0.0' );
define( 'MXCS_PLUGIN_FILE', __FILE__ );
define( 'MXCS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MXCS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 */
final class Maxus_Xero_Credit_Sync {

    private static $instance = null;

    private $xero_api;
    private $credit_sync;
    private $webhook_handler;
    private $cron_sync;
    private $admin;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Check that required plugins are active.
     */
    private function check_dependencies() {
        $missing = array();

        if ( ! class_exists( 'WC_Xero' ) ) {
            $missing[] = 'WooCommerce Xero';
        }

        if ( ! class_exists( 'B2bking' ) && ! defined( 'B2BKING_DIR' ) ) {
            $missing[] = 'B2BKing';
        }

        return $missing;
    }

    /**
     * Initialise plugin after dependencies are loaded.
     */
    public function init() {
        $missing = $this->check_dependencies();

        if ( ! empty( $missing ) ) {
            add_action( 'admin_notices', function () use ( $missing ) {
                $list = implode( ', ', $missing );
                echo '<div class="notice notice-error"><p>';
                echo '<strong>Maxus Xero Credit Sync</strong> requires the following plugins to be active: ' . esc_html( $list );
                echo '</p></div>';
            });
            return;
        }

        $this->load_classes();
        $this->init_components();
    }

    /**
     * Load class files.
     */
    private function load_classes() {
        require_once MXCS_PLUGIN_DIR . 'includes/class-xero-api.php';
        require_once MXCS_PLUGIN_DIR . 'includes/class-credit-sync.php';
        require_once MXCS_PLUGIN_DIR . 'includes/class-webhook-handler.php';
        require_once MXCS_PLUGIN_DIR . 'includes/class-cron-sync.php';
        require_once MXCS_PLUGIN_DIR . 'includes/class-admin.php';
    }

    /**
     * Instantiate components.
     */
    private function init_components() {
        $this->xero_api        = new MXCS_Xero_Api();
        $this->credit_sync     = new MXCS_Credit_Sync( $this->xero_api );
        $this->webhook_handler = new MXCS_Webhook_Handler( $this->credit_sync );
        $this->cron_sync       = new MXCS_Cron_Sync( $this->credit_sync );
        $this->admin           = new MXCS_Admin( $this->credit_sync );
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        if ( ! get_option( 'mxcs_webhook_key' ) ) {
            update_option( 'mxcs_webhook_key', '' );
        }
        if ( ! get_option( 'mxcs_sync_frequency' ) ) {
            update_option( 'mxcs_sync_frequency', 'hourly' );
        }
        if ( ! get_option( 'mxcs_enable_webhook' ) ) {
            update_option( 'mxcs_enable_webhook', '1' );
        }
        if ( ! get_option( 'mxcs_enable_cron' ) ) {
            update_option( 'mxcs_enable_cron', '1' );
        }
        if ( ! get_option( 'mxcs_enable_logging' ) ) {
            update_option( 'mxcs_enable_logging', '1' );
        }

        // Schedule cron on activation.
        if ( ! wp_next_scheduled( 'mxcs_cron_sync_event' ) ) {
            wp_schedule_event( time(), get_option( 'mxcs_sync_frequency', 'hourly' ), 'mxcs_cron_sync_event' );
        }
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        wp_clear_scheduled_hook( 'mxcs_cron_sync_event' );
    }

    public function get_xero_api() {
        return $this->xero_api;
    }

    public function get_credit_sync() {
        return $this->credit_sync;
    }
}

/**
 * Access the plugin instance.
 */
function mxcs() {
    return Maxus_Xero_Credit_Sync::instance();
}

mxcs();
