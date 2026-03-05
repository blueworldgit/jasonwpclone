<?php
/**
 * Plugin Name: Worldpay eCommerce for WooCommerce
 * Description: Use the Worldpay eCommerce for WooCommerce plugin to easily integrate your WooCommerce Online Store into Worldpay eCommerce.
 * Version: 1.3.1
 * Requires at least: 6.4.2
 * Requires PHP: 7.4
 * Author: Worldpay
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 8.4.0
 * WC tested up to: 8.5.1
 *
 * @package worldpay-ecommerce-woocommerce
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WORLDPAY_ECOMM_WC_PLUGIN_FILE' ) ) {
	define( 'WORLDPAY_ECOMM_WC_PLUGIN_FILE', __FILE__ );
}

define( 'WORLDPAY_ECOMMERCE_WOOCOMMERCE_PHP_MIN_VERSION', '7.4' );
define( 'WORLDPAY_ECOMMERCE_WOOCOMMERCE_WP_MIN_VERSION', '6.4.2' );
define( 'WORLDPAY_ECOMMERCE_WOOCOMMERCE_WC_MIN_VERSION', '8.4.0' );
define( 'WORLDPAY_ECOMMERCE_WOOCOMMERCE_PLUGIN_VERSION', '1.3.1' );

register_uninstall_hook(
	WORLDPAY_ECOMM_WC_PLUGIN_FILE,
	'woocommerce_worldpay_ecommerce_uninstall'
);

// Check if WooCommerce is active.
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	add_action(
		'before_woocommerce_init',
		function () {
			if ( class_exists( FeaturesUtil::class ) ) {
				FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			}
		}
	);
	add_action(
		'before_woocommerce_init',
		function () {
			if ( class_exists( FeaturesUtil::class ) ) {
				FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}
	);

	if ( ! class_exists( 'WC_Worldpay_Ecommerce_Woocommerce', false ) ) {
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/class-wc-worldpay-ecommerce-woocommerce.php';
	}

	WC_Worldpay_Ecommerce_Woocommerce::instance();
} else {
	add_action( 'admin_notices', 'woocommerce_worldpay_ecommerce_wc_not_active_notice' );
}

/**
 * Plugin compatibility check.
 *
 * @return bool
 */
function woocommerce_worldpay_ecommerce_compatibility_check() {
	global $wp_version;

	$compatible = true;
	if ( version_compare( phpversion(), WORLDPAY_ECOMMERCE_WOOCOMMERCE_PHP_MIN_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'woocommerce_worldpay_ecommerce_php_min_version_notice' );
		$compatible = false;
	}
	if ( version_compare( $wp_version, WORLDPAY_ECOMMERCE_WOOCOMMERCE_WP_MIN_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'woocommerce_worldpay_ecommerce_wp_min_version_notice' );
		$compatible = false;
	}
	if ( version_compare( WC()->version, WORLDPAY_ECOMMERCE_WOOCOMMERCE_WC_MIN_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'woocommerce_worldpay_ecommerce_wc_min_version_notice' );
		$compatible = false;
	}
	if ( ! extension_loaded( 'curl' ) ) {
		add_action( 'admin_notices', 'woocommerce_worldpay_ecommerce_php_curl_notice' );
		$compatible = false;
	}
	if ( ! extension_loaded( 'mbstring' ) ) {
		add_action( 'admin_notices', 'woocommerce_worldpay_ecommerce_php_mbstring_notice' );
		$compatible = false;
	}
	if ( ! extension_loaded( 'intl' ) ) {
		add_action( 'admin_notices', 'woocommerce_worldpay_ecommerce_php_intl_notice' );
		$compatible = false;
	}

	return $compatible;
}

/**
 * Display php min version compatibility notice.
 *
 * @return void
 */
function woocommerce_worldpay_ecommerce_php_min_version_notice() {
	echo '<div class="error"><p><strong>' . sprintf(
		// Translators: %s is the minimum PHP version required.
		esc_html__(
			'Worldpay eCommerce for WooCommerce requires PHP %s and above to be enabled.',
			'worldpay-ecommerce-woocommerce'
		),
		esc_html( WORLDPAY_ECOMMERCE_WOOCOMMERCE_PHP_MIN_VERSION )
	) . '</strong></p></div>';
}

/**
 * Display wp min version compatibility notice.
 *
 * @return void
 */
function woocommerce_worldpay_ecommerce_wp_min_version_notice() {
	echo '<div class="error"><p><strong>' . sprintf(
		// Translators: %s is the minimum WP version required.
		esc_html__(
			'Worldpay eCommerce for WooCommerce requires WordPress %s and above to be enabled.',
			'worldpay-ecommerce-woocommerce'
		),
		esc_html( WORLDPAY_ECOMMERCE_WOOCOMMERCE_WP_MIN_VERSION )
	) . '</strong></p></div>';
}

/**
 * Display wc min version compatibility notice.
 *
 * @return void
 */
function woocommerce_worldpay_ecommerce_wc_min_version_notice() {
	echo '<div class="error"><p><strong>' . sprintf(
		// Translators: %s is the minimum WC version required.
		esc_html__(
			'Worldpay eCommerce for WooCommerce requires WooCommerce %s and above to be enabled.',
			'worldpay-ecommerce-woocommerce'
		),
		esc_html( WORLDPAY_ECOMMERCE_WOOCOMMERCE_WC_MIN_VERSION )
	) . '</strong></p></div>';
}

/**
 * Display wc is not active or not installed notice.
 *
 * @return void
 */
function woocommerce_worldpay_ecommerce_wc_not_active_notice() {
	echo '<div class="error"><p><strong>' . esc_html__(
		'Worldpay eCommerce for WooCommerce requires WooCommerce to be installed and active.',
		'worldpay-ecommerce-woocommerce'
	) . '</strong></p></div>';
}

/**
 * Display php-curl compatibility notice.
 *
 * @return void
 */
function woocommerce_worldpay_ecommerce_php_curl_notice() {
	echo '<div class="error"><p><strong>' . esc_html__(
		'Worldpay eCommerce for WooCommerce requires php-curl to be enabled.',
		'worldpay-ecommerce-woocommerce'
	) . '</strong></p></div>';
}

/**
 * Display php-mbstring compatibility notice.
 *
 * @return void
 */
function woocommerce_worldpay_ecommerce_php_mbstring_notice() {
	echo '<div class="error"><p><strong>' . esc_html__(
		'Worldpay eCommerce for WooCommerce requires php-mbstring to be enabled.',
		'worldpay-ecommerce-woocommerce'
	) . '</strong></p></div>';
}

/**
 * Display php-intl compatibility notice.
 *
 * @return void
 */
function woocommerce_worldpay_ecommerce_php_intl_notice() {
	echo '<div class="error"><p><strong>' . esc_html__(
		'Worldpay eCommerce for WooCommerce requires php-intl to be enabled.',
		'worldpay-ecommerce-woocommerce'
	) . '</strong></p></div>';
}

/**
 * Delete plugin options in db when it is uninstalled.
 *
 * @return void
 */
function woocommerce_worldpay_ecommerce_uninstall() {
	delete_option( 'woocommerce_access_worldpay_hpp_settings' );
}

/**
 * Get plugins url.
 *
 * @param string $path path.
 * @return string
 */
function woocommerce_worldpay_ecommerce_url( $path ) {
	return plugins_url( $path, __FILE__ );
}
