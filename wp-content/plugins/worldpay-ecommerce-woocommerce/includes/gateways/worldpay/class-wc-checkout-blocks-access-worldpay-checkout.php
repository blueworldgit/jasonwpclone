<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

/**
 * WC_Checkout_Blocks_Access_Worldpay_Checkout class.
 */
final class WC_Checkout_Blocks_Access_Worldpay_Checkout extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug (matches id in WC_Payment_Gateway_Access_Worldpay_Checkout).
	 *
	 * @var string
	 */
	protected $name = 'access_worldpay_checkout';

	private $gateway;

	/**
	 * {@inheritdoc}
	 */
	public function initialize() {
		$this->gateway = WC()->payment_gateways->payment_gateways()[ $this->name ];

		$this->settings = get_option( 'woocommerce_access_worldpay_checkout_settings', array() );
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
			'title'       => $this->gateway->get_title(),
			'description' => $this->gateway->get_description(),
			'checkoutId'  => $this->gateway->get_checkout_id(),
			'isTryMode'   => ! wc_string_to_bool( $this->gateway->get_option( 'is_live_mode' ) ),
			'icons'       => $this->get_icons(),
			'canSaveCard' => is_user_logged_in() && $this->gateway->has_tokens_enabled(),
			'supports'    => $this->get_supported_features(),
		);
	}

	/**
	 * @return array
	 */
	public function get_icons() {
		$icons           = array();
		$card_brands     = $this->gateway->get_card_brands();
		$card_brand_urls = $this->gateway->get_card_brands_url_path();
		foreach ( $card_brands as $index => $card_brand ) {
			$icons[] = array(
				'id'    => $card_brand,
				'src'   => esc_url( $card_brand_urls[ $index ] ),
				'alt'   => esc_attr( ucfirst( $card_brand ) ),
				'class' => $this->gateway->id . '-card-brands',
			);
		}

		return $icons;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_payment_method_script_handles() {
		$this->gateway->enqueue_checkout_sdk_script();
		wp_register_script(
			'wc-payment-method-access_worldpay_checkout',
			woocommerce_worldpay_ecommerce_url( '/assets/client/blocks/wc-payment-method-access_worldpay_checkout.js' ),
			array(
				'jquery',
				'react',
				'wc-blocks-registry',
				'wc-settings',
				'wp-html-entities',
				'wp-element',
				'wp-i18n',
				'worldpay-ecommerce-checkout-sdk',
			),
			WC()->version,
			true
		);
		$this->gateway->add_checkout_scripts_params( 'wc-payment-method-access_worldpay_checkout' );
		$this->gateway->checkout_styles();

		return array( 'wc-payment-method-access_worldpay_checkout' );
	}
}

/**
 * Register Worldpay eCommerce for WooCommerce Checkout Blocks.
 *
 * @param  PaymentMethodRegistry  $payment_method_registry
 *
 * @return void
 */
function register_access_worldpay_checkout( PaymentMethodRegistry $payment_method_registry ): void {
	$payment_method_registry->register( new WC_Checkout_Blocks_Access_Worldpay_Checkout() );
}

if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
	add_action(
		'woocommerce_blocks_loaded',
		function () {
			add_action( 'woocommerce_blocks_payment_method_type_registration', 'register_access_worldpay_checkout' );
		}
	);
}
