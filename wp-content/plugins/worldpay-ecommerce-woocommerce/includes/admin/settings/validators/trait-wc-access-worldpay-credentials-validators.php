<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Worldpay\Api\AccessWorldpay;
use Worldpay\Api\ApiResponse;
use Worldpay\Api\Entities\Order;
use Worldpay\Api\Enums\Environment;
use Worldpay\Api\Enums\Api;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Exceptions\AuthenticationException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Utils\Helper;
use Worldpay\Api\Enums\RequestMethods;

trait WC_Access_Worldpay_Credentials_Validators {

	/**
	 * Test api credentials request.
	 *
	 * @return void
	 */
	public function test_api_credentials_request() {
		try {
			if ( RequestMethods::POST !== $_SERVER['REQUEST_METHOD'] ) {
				throw new Exception(
					__(
						'Method not allowed for this request. Please use one of the allowed methods.',
						'worldpay-ecommerce-woocommerce'
					)
				);
			}
			// Check and sanitize the nonce.
			$wpnonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';
			if ( ! wp_verify_nonce( $wpnonce, 'worldpay-ecommerce-test_api_credentials' ) ) {
				throw new \Exception( __( 'Invalid test API credentials request.', 'worldpay-ecommerce-woocommerce' ) );
			}

			$request_method_id = isset( $_REQUEST['method_id'] ) ? $_REQUEST['method_id'] : '';
			if ( $this->id !== $request_method_id ) {
				return;
			}

			// Check and sanitize the app_mode.
			$app_mode = isset( $_REQUEST['app_mode'] ) ? $_REQUEST['app_mode'] : '';
			if ( 'live' === $app_mode ) {
				$environment  = Environment::LIVE_MODE;
				$password_key = 'app_api_live_password';
			} else {
				$environment  = Environment::TRY_MODE;
				$password_key = 'app_api_try_password';
			}

			// Check and sanitize the app_username.
			$app_username = isset( $_REQUEST['app_username'] ) ? $_REQUEST['app_username'] : '';

			// Check and sanitize the app_password.
			$app_password = isset( $_REQUEST['app_password'] ) ? $_REQUEST['app_password'] : '';

			// Check and sanitize the app_merchant_entity.
			$app_merchant_entity = isset( $_REQUEST['app_merchant_entity'] ) ? $_REQUEST['app_merchant_entity'] : '';

			$app_merchant_checkout_id = isset( $_REQUEST['app_checkout_id'] ) ? $_REQUEST['app_checkout_id'] : '';

			$this->api_config_provider                    = AccessWorldpayConfigProvider::instance();
			$this->api_config_provider->environment       = $environment;
			$this->api_config_provider->username          = $app_username;
			$this->api_config_provider->password          = $this->replace_mask( $password_key, $app_password );
			$this->api_config_provider->merchantEntity    = $this->replace_mask(
				'app_merchant_entity',
				$app_merchant_entity,
				true
			);
			$this->api_config_provider->merchantNarrative = 'Test API Credentials';
			$this->api_config_provider->checkoutId        = $app_merchant_checkout_id;
			$this->api_config_provider->api               = $this->id === 'access_worldpay_hpp' ? Api::ACCESS_WORLDPAY_HPP_API : Api::ACCESS_WORLDPAY_PAYMENTS_API;
			$api_response                                 = $this->test_api_credentials();
			if ( $api_response->isSuccessful() ) {
				wp_send_json(
					array(
						'status'  => 'success',
						'message' => __(
							'Worldpay Payments connected successfully to the payment gateway with your provided credentials.',
							'worldpay-ecommerce-woocommerce'
						),
					)
				);

				return;
			}
			if ( $api_response->hasServerError() ) {
				throw new \Exception( 'Worldpay Payments could not connect to Access Worldpay API. Please try again later.' );
			}
			throw new \Exception( 'Worldpay Payments could not connect to the payment gateway with your provided credentials.' );
		} catch ( \Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->info( $api_response->getResponseMetadata( $this->id_suffix . 'TestCredentials', $e->getMessage() ) );
			}
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Test api credentials save.
	 *
	 * @return void
	 */
	public function test_api_credentials_save() {
		try {
			$api_response = $this->test_api_credentials();
			if ( $api_response->isSuccessful() ) {
				WC_Admin_Settings::add_message(
					__(
						'Worldpay Payments connected successfully to the payment gateway with your provided credentials.',
						'worldpay-ecommerce-woocommerce'
					)
				);

				return;
			}
			if ( $api_response->hasServerError() ) {
				throw new \Exception( 'Your settings were saved, but Worldpay Payments could not connect to Access Worldpay API. Please try again later.' );
			}
			throw new \Exception( 'Your settings were saved, but Worldpay Payments could not connect to the payment gateway with your provided credentials.' );
		} catch ( \Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->info( $api_response->getResponseMetadata( $this->id_suffix . 'TestCredentials', $e->getMessage() ) );
			}
			WC_Admin_Settings::add_error( $e->getMessage() );
		}
	}

	/**
	 * Test api credentials request.
	 *
	 * @return ApiResponse
	 * @throws AuthenticationException|ApiClientException
	 * @throws Exception
	 */
	public function test_api_credentials(): ApiResponse {
		$api                 = $this->initialize_api();
		$api_config_provider = AccessWorldpayConfigProvider::instance();
		$api_response        = null;
		if ( 'access_worldpay_hpp' === $this->id ) {
			$api_response = $this->test_hpp_api_credentials( $api_config_provider, $api );
		} elseif ( 'access_worldpay_checkout' === $this->id ) {
			$api_response = $this->test_payments_api_credentials( $api_config_provider, $api );
		}

		if ( wc_string_to_bool( $this->get_option( 'app_debug' ) ) ) {
			$data_to_log = array(
				'rawRequest=' . $api_response->rawRequest,
				'rawResponse=' . $api_response->rawResponse,
				'statusCode=' . $api_response->statusCode,
				'curlError=' . $api_response->curlError,
				'headers=' . json_encode( $api_response->headers ),
				'sentHeaders=' . json_encode( $api_response->sentHeaders ),
			);
			$data_to_log = implode( ' | ', $data_to_log );
			wc_get_logger()->debug( $data_to_log );
		}

		return $api_response;
	}

	/**
	 * Test payments api credentials request.
	 *
	 * @param $api_config_provider
	 * @param  AccessWorldpay $api
	 *
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function test_payments_api_credentials( $api_config_provider, AccessWorldpay $api ): ApiResponse {
		$api_config_provider->checkoutId = $api_config_provider->checkoutId ?? $this->get_checkout_id();
		$api_config_provider->api        = Api::ACCESS_WORLDPAY_PAYMENT_SESSIONS_API;
		$api_response                    = $api->createPaymentSession( $api_config_provider->checkoutId )
											   ->withCardExpiryMonth( date( 'n', strtotime( '+1 month' ) ) )
											   ->withCardExpiryYear( date( 'Y', strtotime( '+1 year' ) ) )
											   ->withCardNumber( '4444333322221111' )
											   ->withCardCvc( '123' )
											   ->execute();
		if ( $api_response->isSuccessful() ) {
			$decoded_api_response = json_decode( $api_response->rawResponse, true );
			$session_url          = $decoded_api_response['_links']['sessions:session']['href'] ?? null;
			if ( $session_url ) {
				$api_config_provider->api = Api::ACCESS_WORLDPAY_PAYMENTS_API;
				$payment_instrument       = $this->create_payment_instrument_object( $session_url, '' );
				$api_response             = $api->initiatePayment( 1 )
												->withCurrency( 'GBP' )
												->withTransactionReference( Helper::generateString( 12 ) . '_test' )
												->withPaymentInstrument( $payment_instrument )
												->withAutoSettlement()
												->execute();
			} else {
				throw new Exception( 'Payment session is not set up, but username, password and checkout id are valid.' );
			}
		}

		return $api_response;
	}

	/**
	 * Test hpp api credentials request.
	 *
	 * @param $api_config_provider
	 * @param  AccessWorldpay $api
	 *
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function test_hpp_api_credentials( $api_config_provider, AccessWorldpay $api ): ApiResponse {
		$api_config_provider->api = Api::ACCESS_WORLDPAY_HPP_API;
		$api_response             = $api->initiatePayment( 1 )
										->withCurrency( 'GBP' )
										->withTransactionReference( Helper::generateString( 12 ) . '_test' )
										->execute();

		return $api_response;
	}
}
