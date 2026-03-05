<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

/**
 * WC_Checkout_Blocks_Access_Worldpay class.
 */
final class WC_Checkout_Blocks_Access_Worldpay extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug (matches id in WC_Payment_Gateway_Access_Worldpay).
	 *
	 * @var string
	 */
	protected $name = 'access_worldpay_hpp';

	private $gateway;

	/**
	 * {@inheritdoc}
	 */
	public function initialize() {
		$this->gateway = WC()->payment_gateways->payment_gateways()[ $this->name ];

		$this->settings = get_option( 'woocommerce_access_worldpay_hpp_settings', array() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_supported_features() {
		return $this->gateway->supports;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->gateway->title,
			'description' => $this->gateway->description,
			'canSaveCard' => is_user_logged_in() && $this->gateway->has_tokens_enabled(),
			'supports'    => $this->get_supported_features(),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-payment-method-access_worldpay_hpp',
			woocommerce_worldpay_ecommerce_url( '/assets/client/blocks/wc-payment-method-access_worldpay_hpp.js' ),
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-html-entities',
			),
			WC()->version,
			true
		);

		return array( 'wc-payment-method-access_worldpay_hpp' );
	}
}

/**
 * Register Worldpay eCommerce for WooCommerce Checkout Blocks.
 *
 * @param  PaymentMethodRegistry $payment_method_registry
 *
 * @return void
 */
function register_worldpay_ecommerce( PaymentMethodRegistry $payment_method_registry ): void {
	$payment_method_registry->register( new WC_Checkout_Blocks_Access_Worldpay() );
}

if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
	add_action(
		'woocommerce_blocks_loaded',
		function () {
			add_action( 'woocommerce_blocks_payment_method_type_registration', 'register_worldpay_ecommerce' );
		}
	);
}
