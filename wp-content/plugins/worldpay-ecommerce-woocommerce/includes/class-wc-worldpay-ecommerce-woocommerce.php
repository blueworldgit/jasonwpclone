<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WC_Worldpay_Ecommerce_Woocommerce {

	protected static $_instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->include_sdk();
		$this->includes();
	}

	/**
	 * Include all required files.
	 *
	 * @return void
	 */
	public function includes() {
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/admin/settings/class-wc-access-worldpay-base-form-fields.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/admin/settings/class-wc-access-worldpay-form-fields.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/admin/settings/class-wc-access-worldpay-checkout-form-fields.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/admin/settings/validators/trait-wc-access-worldpay-credentials-validators.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/admin/settings/validators/trait-wc-access-worldpay-field-validators.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/admin/helpers/trait-wc-worldpay-ecommerce-string-utils.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/tokens/wc-worldpay-payment-token.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/payment_methods/wc-worldpay-payment-method.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/services/payment-processing/class-wc-worldpay-payment-processing.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/gateways/worldpay/class-wc-checkout-blocks-access-worldpay.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/gateways/worldpay/class-wc-checkout-blocks-access-worldpay-checkout.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/gateways/worldpay/class-wc-payment-gateway-access-worldpay.php';
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/includes/gateways/worldpay/class-wc-payment-gateway-access-worldpay-checkout.php';
	}

	/**
	 * Include SDK.
	 *
	 * @return void
	 */
	public static function include_sdk() {
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/vendor/worldpay/php-sdk/autoload.php';
	}

	/**
	 * Returns instance.
	 *
	 * @return WC_Worldpay_Ecommerce_Woocommerce|null
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
