<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Access_Worldpay_Form_Fields extends WC_Access_Worldpay_Base_Form_Fields {

	/**
	 * Initialise settings form fields.
	 *
	 * @return array
	 */
	public static function init_form_fields() {

		$common_fields = self::common_form_fields();

		$common_fields['enabled']['label'] = __( 'Enable Worldpay Payments Offsite', 'worldpay-ecommerce-woocommerce' );

		$offsite_fields = array(
			'app_webhooks'             => array(
				'title'       => __( 'Enable webhooks', 'worldpay-ecommerce-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __(
					'Log into your <a href="https://dashboard.worldpay.com/" target="_blank" rel="noopener noreferrer">Worldpay Dashboard</a> and set the following URL ' . self::get_events_url(),
					'worldpay-ecommerce-woocommerce'
				),
				'label'       => __(
					'Receive status updates from Access Worldpay by setting up a webhook.',
					'worldpay-ecommerce-woocommerce'
				),
				'order'       => 8,
			),
			'app_merchant_description' => array(
				'title'             => __( 'Merchant description', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'text',
				'description'       => __(
					'An optional text, when supplied is displayed to your customer on payment pages',
					'worldpay-ecommerce-woocommerce'
				),
				'custom_attributes' => array(
					'minlength' => 1,
					'maxlength' => 128,
				),
				'order'             => 12,
			),
		);

		$fields = array_merge( $common_fields, $offsite_fields );

		return self::sort_fields_by_order( $fields );
	}

	/**
	 * Returns the webhook destination (URL) for receiving status updates from Access Worldpay via webhooks.
	 *
	 * @return string
	 */
	public static function get_events_url() {
		return WC()->api_request_url( 'worldpay_events' );
	}
}
