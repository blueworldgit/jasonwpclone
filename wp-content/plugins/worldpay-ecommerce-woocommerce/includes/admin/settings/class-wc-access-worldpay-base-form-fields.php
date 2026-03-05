<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Access_Worldpay_Base_Form_Fields {

	/**
	 * Common fields shared between onsite and offsite.
	 *
	 * @return array
	 */
	protected static function common_form_fields() {
		return array(
			'general_settings'       => array(
				'type'  => 'title',
				'title' => __( 'General Settings', 'worldpay-ecommerce-woocommerce' ),
				'order' => 1,
			),
			'enabled'                => array(
				'title'   => __( 'Enable/Disable', 'worldpay-ecommerce-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Worldpay Payments', 'worldpay-ecommerce-woocommerce' ),
				'default' => false,
				'order'   => 2,
			),
			'title'                  => array(
				'title'       => __( 'Title', 'worldpay-ecommerce-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Payment method title that the customer will see at checkout.', 'worldpay-ecommerce-woocommerce' ),
				'default'     => __( 'Pay with Card (Worldpay)', 'worldpay-ecommerce-woocommerce' ),
				'desc_tip'    => true,
				'order'       => 3,
			),
			'description'            => array(
				'title'       => __( 'Description', 'worldpay-ecommerce-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see at checkout.', 'worldpay-ecommerce-woocommerce' ),
				'default'     => __( 'Pay with Credit Card', 'worldpay-ecommerce-woocommerce' ),
				'desc_tip'    => true,
				'order'       => 4,
			),
			'is_live_mode'           => array(
				'title'       => __( 'Enable live mode', 'worldpay-ecommerce-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Choose between try/test or live/production mode which will influence the used credentials.', 'worldpay-ecommerce-woocommerce' ),
				'order'       => 5,
			),
			'app_enable_tokens'      => array(
				'title'       => __( 'Enable tokens', 'worldpay-ecommerce-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Allow customers to save card details for future initiated transactions (CIT).', 'worldpay-ecommerce-woocommerce' ),
				'order'       => 7,
			),
			'Merchant Settings'      => array(
				'type'  => 'title',
				'title' => __( 'Merchant Settings', 'worldpay-ecommerce-woocommerce' ),
				'order' => 9,
			),
			'app_merchant_entity'    => array(
				'title'             => __( 'Merchant entity *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'masked_partially',
				'description'       => __(
					'Format: POxxxxxxx <br> You can find your entity in your <a href="https://dashboard.worldpay.com/" target="_blank" rel="noopener noreferrer">Worldpay Dashboard</a> under the Developer Tools tab.',
					'worldpay-ecommerce-woocommerce'
				),
				'custom_attributes' => array(
					'required'     => 'required',
					'minlength'    => 1,
					'maxlength'    => 32,
					'autocomplete' => 'off',
				),
				'order'             => 10,
			),
			'app_merchant_narrative' => array(
				'title'             => __( 'Merchant narrative *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'text',
				'description'       => __(
					'Helps your customers better identify you on their statement.' .
					' Valid characters are: A-Z, a-z, 0-9, hyphen(-), full stop(.), commas(,), space.',
					'worldpay-ecommerce-woocommerce'
				),
				'custom_attributes' => array(
					'required'  => 'required',
					'minlength' => 1,
					'maxlength' => 24,
				),
				'order'             => 11,
			),
			'try_settings'           => array(
				'type'  => 'title',
				'title' => __( 'Try API Credentials', 'worldpay-ecommerce-woocommerce' ),
				'order' => 13,
			),
			'app_api_try_username'   => array(
				'title'             => __( 'Username *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'text',
				'custom_attributes' => array(
					'required'     => 'required',
					'autocomplete' => 'off',
				),
				'order'             => 14,
			),
			'app_api_try_password'   => array(
				'title'             => __( 'Password *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'masked_totally',
				'description'       => __(
					'Get your credentials from your <a href="https://dashboard.worldpay.com/" target="_blank" rel="noopener noreferrer">Worldpay Dashboard</a> under the Developer Tools tab.',
					'worldpay-ecommerce-woocommerce'
				),
				'custom_attributes' => array(
					'required'     => 'required',
					'autocomplete' => 'off',
				),
				'order'             => 15,
			),
			'test_try_credentials'   => array(
				'label'             => __( 'Test try credentials', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'button',
				'class'             => 'button-primary worldpay-ecommerce-test-credentials',
				'default'           => __( 'Test try credentials', 'worldpay-ecommerce-woocommerce' ),
				'css'               => 'width: 150px',
				'custom_attributes' => array( 'data-app-mode' => 'try' ),
				'order'             => 18,
			),
			'live_settings'          => array(
				'type'  => 'title',
				'title' => __( 'Live API Credentials', 'worldpay-ecommerce-woocommerce' ),
				'order' => 19,
			),
			'app_api_live_username'  => array(
				'title'             => __( 'Username *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'text',
				'custom_attributes' => array( 'autocomplete' => 'off' ),
				'order'             => 20,
			),
			'app_api_live_password'  => array(
				'title'             => __( 'Password *', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'masked_totally',
				'description'       => __(
					'Get your credentials from your <a href="https://dashboard.worldpay.com/" target="_blank" rel="noopener noreferrer">Worldpay Dashboard</a> under the Developer Tools tab.',
					'worldpay-ecommerce-woocommerce'
				),
				'custom_attributes' => array( 'autocomplete' => 'off' ),
				'order'             => 21,
			),
			'test_live_credentials'  => array(
				'label'             => __( 'Test live credentials', 'worldpay-ecommerce-woocommerce' ),
				'type'              => 'button',
				'class'             => 'button-primary worldpay-ecommerce-test-credentials',
				'default'           => __( 'Test live credentials', 'worldpay-ecommerce-woocommerce' ),
				'css'               => 'width: 150px',
				'custom_attributes' => array( 'data-app-mode' => 'live' ),
				'order'             => 23,
			),
			'debug_settings'         => array(
				'type'  => 'title',
				'title' => __( 'Debug', 'worldpay-ecommerce-woocommerce' ),
				'order' => 24,
			),
			'app_debug'              => array(
				'title'       => __( 'Debug mode', 'worldpay-ecommerce-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Debug', 'worldpay-ecommerce-woocommerce' ),
				'description' => __( 'Debug your connection to Worldpay.', 'worldpay-ecommerce-woocommerce' ),
				'order'       => 25,
			),
		);
	}

	/**
	 * Sorts the fields array by the 'order' key.
	 *
	 * @param array $fields
	 * @return array
	 */
	protected static function sort_fields_by_order( array $fields ): array {
		uasort(
			$fields,
			function( $a, $b ) {
				return $a['order'] <=> $b['order'];
			}
		);

		return $fields;
	}
}
