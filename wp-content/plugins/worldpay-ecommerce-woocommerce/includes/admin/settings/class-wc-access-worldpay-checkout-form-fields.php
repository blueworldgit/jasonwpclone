<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Access_Worldpay_Checkout_Form_Fields extends WC_Access_Worldpay_Base_Form_Fields {

	/**
	 * Initialise settings form fields.
	 *
	 * @return array
	 */
	public static function init_form_fields() {

		$common_fields = self::common_form_fields();

		$common_fields['enabled']['label'] = __( 'Enable Worldpay Payments Onsite', 'worldpay-ecommerce-woocommerce' );

		$offsite_fields = array(
			'app_card_brands'               => array(
				'title'             => __( 'Card brands *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'multiselect',
				'description'       => __(
					'Select the card brands displayed and used in checkout.',
					'worldpay-ecommerce-woocommerce'
				),
				'options'           => array(
					'visa'       => __( 'Visa', 'worldpay-ecommerce-woocommerce' ),
					'mastercard' => __( 'Mastercard', 'worldpay-ecommerce-woocommerce' ),
					'amex'       => __( 'American Express', 'worldpay-ecommerce-woocommerce' ),
				),
				'custom_attributes' => array(
					'required'         => 'required',
					'data-placeholder' => __( 'Select card brands.', 'worldpay-ecommerce-woocommerce' ),
				),
				'default'           => array(
					'visa',
					'mastercard',
					'amex',
				),
				'class'             => 'wc-enhanced-select',
				'order'             => 12,
			),
			'app_merchant_try_checkout_id'  => array(
				'title'             => __( 'Merchant checkout id *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'text',
				'description'       => __(
					'Checkout id that is required to create a payment session.',
					'worldpay-ecommerce-woocommerce'
				),
				'custom_attributes' => array(
					'required'  => 'required',
					'minlength' => 1,
				),
				'order'             => 17,
			),
			'app_merchant_live_checkout_id' => array(
				'title'             => __( 'Merchant checkout id *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'text',
				'description'       => __(
					'Checkout id that is required to create a payment session.',
					'worldpay-ecommerce-woocommerce'
				),
				'custom_attributes' => array(
					'required'  => 'required',
					'minlength' => 1,
				),
				'order'             => 22,
			),
		);

		$fields = array_merge( $common_fields, $offsite_fields );

		return self::sort_fields_by_order( $fields );
	}
}
