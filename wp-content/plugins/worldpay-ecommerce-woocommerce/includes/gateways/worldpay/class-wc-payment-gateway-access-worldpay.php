<?php
/**
 * This file handles the integration and setup for Worldpay API in WooCommerce.
 *
 * @package worldpay-ecommerce-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Worldpay\Api\Entities\Event;
use Worldpay\Api\Enums\CustomerAgreementType;
use Worldpay\Api\Enums\EventType;
use Worldpay\Api\Enums\PaymentInstrumentType;
use Worldpay\Api\Enums\RequestMethods;
use Worldpay\Api\Enums\StoredCardUsage;
use Worldpay\Api\Enums\TokenOption;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Exceptions\ApiResponseException;
use Worldpay\Api\Exceptions\AuthenticationException;
use Worldpay\Api\Utils\AmountHelper;
use Worldpay\Api\Utils\Helper;
use Worldpay\Api\ValueObjects\PaymentMethods\CreditCard;
use Worldpay\Api\ValueObjects\ResultURLs;
use Worldpay\Api\Enums\Api;

/**
 * WC_Payment_Gateway_Access_Worldpay class.
 */
class WC_Payment_Gateway_Access_Worldpay extends WC_Worldpay_Payment_Method {

	/**
	 * Payment method id.
	 *
	 * @var string
	 */
	public $id = 'access_worldpay_hpp';

	/**
	 * Payment method id suffix.
	 *
	 * @var string
	 */
	public $id_suffix = 'hpp';

	/**
	 * Payment method icon.
	 *
	 * @var string
	 */
	public $icon = '';

	/**
	 * Payment method has fields.
	 *
	 * @var bool
	 */
	public $has_fields = false;

	/**
	 * Payment method title.
	 *
	 * @var string
	 */
	public $method_title = 'Worldpay Payments Offsite';

	/**
	 * Payment method description.
	 *
	 * @var string
	 */
	public $method_description = 'Accept payments via Worldpay.';

	/**
	 * Payment method supports.
	 *
	 * @var string[]
	 */
	public $supports = array(
		'products',
		'refunds',
		'tokenization',
	);

	/**
	 * @var string
	 */
	public $api = Api::ACCESS_WORLDPAY_HPP_API;

	/**
	 * Initialize payment method gateway.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'woocommerce_api_worldpay_results', array( $this, 'process_payment_result_url' ) );

		if ( wc_bool_to_string( $this->get_option( 'app_webhooks' ) ) ) {
			add_action( 'woocommerce_api_worldpay_events', array( $this, 'process_event' ) );
		}
	}

	public function payment_fields() {
		parent::payment_fields();
		$is_checkout_or_pay = ( is_checkout() || is_checkout_pay_page() );
		$show_tokenization  = $this->supports( 'tokenization' ) && $is_checkout_or_pay && $this->has_tokens_enabled();

		if ( $show_tokenization ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
			$this->save_payment_method_checkbox();

			$this->checkout_scripts();
			$this->add_checkout_scripts_params( 'worldpay-ecommerce-hpp-scripts' );
		}
	}

	/**
	 * Initialize admin settings form.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = WC_Access_Worldpay_Form_Fields::init_form_fields();
	}

	/**
	 * Process status updates received from Access Worldpay via webhooks.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function process_event() {
		$data_to_log = array(
			'requestType' => 'webhook',
		);

		try {
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && RequestMethods::POST !== $_SERVER['REQUEST_METHOD'] ) {
				$data_to_log['requestMethod'] = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) );
				throw new Exception( 'Request method not supported.' );
			}

			$server      = rest_get_server();
			$raw_content = $server::get_raw_data();

			$event = new Event( $raw_content );

			$data_to_log['eventId']              = $event->id;
			$data_to_log['eventType']            = $event->type;
			$data_to_log['transactionReference'] = $event->transactionReference;

			if ( ! in_array(
				$event->type,
				array(
					EventType::SENT_FOR_SETTLEMENT,
					EventType::ERROR,
					EventType::REFUSED,
					EventType::SENT_FOR_REFUND,
					EventType::REFUND_FAILED,
				),
				true
			) ) {
				throw new Exception( 'Unsupported event type.' );
			}

			$args   = array(
				'limit'          => - 1,
				'payment_method' => $this->id,
				'transaction_id' => esc_attr( $event->transactionReference ),
			);
			$orders = $this->get_orders( $args );
			if ( empty( $orders ) ) {
				throw new Exception( 'Order retrieval failed.' );
			}
			foreach ( $orders as $wc_order ) {
				$data_to_log['orderId'] = $wc_order->get_id();
				switch ( $event->type ) {
					case EventType::SENT_FOR_SETTLEMENT:
						$event_note = __( 'sent for payment settlement', 'worldpay-ecommerce-woocommerce' );
						$wc_order->payment_complete();
						$wc_order->save();
						break;
					case EventType::ERROR:
						$event_note = __( 'payment was not completed', 'worldpay-ecommerce-woocommerce' );
						break;
					case EventType::REFUSED:
						$event_note = __( 'payment request declined', 'worldpay-ecommerce-woocommerce' );
						break;
					case EventType::SENT_FOR_REFUND:
						$event_note = __( 'payment refund sent', 'worldpay-ecommerce-woocommerce' );
						break;
					case EventType::REFUND_FAILED:
						$event_note = __( 'payment refund failed', 'worldpay-ecommerce-woocommerce' );
						break;
					default:
						throw new Exception( 'Unsupported event type.' );
				}
				$note_text = sprintf(
					'%s%s %s. Transaction reference: %s' . ( ! empty( $event->reference ) ? '. Partial refund reference %s' : '' ),
					get_woocommerce_currency_symbol( $event->currency ),
					AmountHelper::exponentToDecimalDelimiter( $event->amount ),
					$event_note ?? '',
					$event->transactionReference,
					$event->reference
				);
				$wc_order->add_order_note( $note_text );
				$wc_order->save();
			}
		} catch ( Exception $e ) {
			$data_to_log  = http_build_query( $data_to_log, '', ' | ' );
			$data_to_log .= ' | message=' . $e->getMessage();
			wc_get_logger()->info( $data_to_log );

			return;
		}
	}

	/**
	 * Process payment result url.
	 *
	 * @return bool|void
	 */
	public function process_payment_result_url() {
		$data = $this->get_result_url_params();
		if ( isset( $data['error'] ) ) {
			return wp_safe_redirect( $data['redirect_to'] );
		}

		$result = $this->process_payment_success_url( $data['wc_order'], $data['guid'] );
		if ( false === $result ) {
			$this->process_payment_failure_url( $data['wc_order'], $data['guid'], $data['page'] );
		}

		$this->terminate();
	}

	/**
	 * Get result url params.
	 *
	 * @return array
	 */
	public function get_result_url_params() {
		$page     = empty( $_GET['page'] ) ? '' : wc_clean( sanitize_text_field( wp_unslash( $_GET['page'] ) ) );
		$guid     = empty( $_GET['guid'] ) ? '' : wc_clean( sanitize_text_field( wp_unslash( $_GET['guid'] ) ) );
		$order_id = empty( $_GET['order_id'] ) ? '' : wc_clean( sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) );

		try {
			if ( empty( $guid ) ) {
				throw new Exception( 'Unable to process payment result url. Invalid guid.' );
			}
			if ( empty( $order_id ) ) {
				throw new Exception( 'Unable to process payment result url. Invalid order Id.' );
			}
			$wc_order = wc_get_order( $order_id );
			if ( ! $wc_order instanceof WC_Order ) {
				throw new Exception( 'Unable to process payment result url. Order not found.' );
			}
			if ( $this->id !== $wc_order->get_payment_method() ) {
				throw new Exception( 'Unable to process payment result url. Payment method not found.' );
			}

			return array(
				'page'     => $page,
				'guid'     => $guid,
				'wc_order' => $wc_order,
			);
		} catch ( Exception $e ) {
			$data_to_log  = array(
				'page'    => $page ?? '',
				'guid'    => $guid ?? '',
				'orderId' => $order_id ?? '',
			);
			$data_to_log  = http_build_query( $data_to_log, '', ' | ' );
			$data_to_log .= ' | message=' . $e->getMessage();
			wc_get_logger()->info( $data_to_log );

			$wc_order    = new WC_Order();
			$redirect_to = ( 'checkout' === $page ) ? wc_get_checkout_url() : $wc_order->get_checkout_payment_url();
			wc_get_logger()->info( $redirect_to );

			return array(
				'error'       => true,
				'redirect_to' => add_query_arg( $this->id, 'error', $redirect_to ),
			);
		}
	}

	/**
	 * Process payment success result.
	 *
	 * @return false|void
	 * @throws ApiClientException
	 * @throws ApiResponseException
	 * @throws AuthenticationException
	 */
	public function process_payment_success_url( WC_Order $wc_order, string $guid ) {
		$success_guid = $wc_order->get_meta( 'worldpay_success_guid' );
		if ( $guid !== $success_guid ) {
			return false;
		}

		if ( wc_string_to_bool( $this->get_option( 'app_webhooks' ) ) ) {
			$wc_order->update_status( 'on-hold' );
		} else {
			$wc_order->payment_complete();
		}
		$order_note = sprintf(
			'Payment successful via Worldpay. Transaction reference %s',
			$wc_order->get_transaction_id()
		);
		$wc_order->add_order_note( $order_note );
		$wc_order->save();

		if ( ! $wc_order->get_meta( 'worldpay_save_card' ) ) {
			wp_safe_redirect( $wc_order->get_checkout_order_received_url() );
			return;
		}
		$this->api_config_provider      = $this->configure_api();
		$this->api_config_provider->api = Api::ACCESS_WORLDPAY_TOKENS_API;
		$api                            = $this->initialize_api();
		$api_response                   = $api->paymentToken()
											  ->withNamespace( WC_Worldpay_Payment_Token::get_customer_tokens_namespace( $wc_order->get_user_id(), $this->id ) )
											  ->query();
		if ( $api_response->isSuccessful() ) {
			$api_decoded_response = $api_response->jsonDecode();
			$wp_tokens            = $api_decoded_response->_embedded->tokens ?? array();

			$wc_stored_tokens = WC_Worldpay_Payment_Token::get_customer_tokens_ids( $wc_order->get_user_id(), $this->id );

			foreach ( $wp_tokens as $wp_token ) {
				if ( isset( $wc_stored_tokens[ $wp_token->tokenId ] ) ) {
					continue;
				}
				$this->store_payment_token_from_response( $wc_order->get_user_id(), $wp_token );
			}
		} else {
			wc_get_logger()->info( $api_response->getResponseMetadata( 'hppQueryTokens' ) );
		}

		wp_safe_redirect( $wc_order->get_checkout_order_received_url() );
	}

	/**
	 * Process payment failure result in checkout or pay for order.
	 *
	 * @param  WC_Order $wc_order order.
	 * @param  string   $guid guid.
	 * @param  string   $page page.
	 *
	 * @return false
	 */
	public function process_payment_failure_url( WC_Order $wc_order, string $guid, string $page ) {
		$failure_guid = $wc_order->get_meta( 'worldpay_failure_guid' );
		if ( $guid === $failure_guid ) {
			$order_note = sprintf(
				'Payment failed via Worldpay. Transaction reference %s',
				$wc_order->get_transaction_id()
			);
			$wc_order->add_order_note( $order_note );
			$wc_order->delete_meta_data( 'worldpay_transaction_reference' );
			$wc_order->save();

			$redirect_to = ( 'checkout' === $page ) ? wc_get_checkout_url() : $wc_order->get_checkout_payment_url();

			wp_safe_redirect( add_query_arg( $this->id, 'error', $redirect_to ) );
		}

		return false;
	}

	/**
	 * Process refund.
	 *
	 * @param $order_id
	 * @param $amount
	 * @param $reason
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if ( empty( $amount ) || $amount <= 0 ) {
			throw new Exception( 'Invalid amount value: ' . esc_html( $amount ) );
		}

		try {
			$wc_order = $this->get_order( $order_id );

			$api = $this->initialize_api();

			$converted_amount         = AmountHelper::decimalToExponentDelimiter( $amount, $wc_order->get_currency(), get_locale() );
			$partial_refund_reference = Helper::generateString( 12 ) . '-' . $order_id;

			$api_call = $converted_amount == $wc_order->get_meta( 'worldpay_transaction_amount' ) ?
				$api->refund() :
				$api->partialRefund( $converted_amount );

			$api_response = $api_call->withTransactionReference( $wc_order->get_transaction_id() )
									 ->withPartialOperationReference( $partial_refund_reference )
									 ->withCurrency( $wc_order->get_currency() )
									 ->execute();

			if ( $api_response->isSuccessful() ) {
				$note_text = sprintf(
					'%s%s sent for payment refund.' . ( ( $amount < $wc_order->get_total() ) ? ' Partial refund reference: %s' : '' ),
					get_woocommerce_currency_symbol( $wc_order->get_currency() ),
					AmountHelper::exponentToDecimalDelimiter( $converted_amount ),
					$partial_refund_reference
				);
				$wc_order->add_order_note( $note_text );

				return true;
			} else {
				throw new Exception( 'Something went wrong while requesting payment refund.' );
			}
		} catch ( Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->info( $api_response->getResponseMetadata( 'hppRefund', $e->getMessage() ) );
			}

			$data_to_log = array(
				'requestType'     => 'hppRefund',
				'orderId'         => $order_id ?? null,
				'amount'          => $amount ?? null,
				'convertedAmount' => $converted_amount ?? null,
				'errorMessage'    => $e->getMessage(),
			);
			$data_to_log = http_build_query( $data_to_log, '', ' | ' );

			wc_get_logger()->info( $data_to_log );

			throw new Exception( __( 'Something went wrong while requesting payment refund.', 'worldpay-ecommerce-woocommerce' ) );
		}
	}

	/**
	 * Process a payment.
	 *
	 * @param $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {
		$result       = array();
		$api_response = null;

		try {
			$wc_order = $this->get_order( $order_id );
			if ( ! $wc_order ) {
				throw new Exception( __( 'Invalid order', 'woocommerce' ) );
			}

			$success_guid = wp_generate_uuid4();
			$failure_guid = wp_generate_uuid4();

			$wc_token_id                = (int) $this->get_saved_payment_method_id();
			$payment_processing_service = new WC_Worldpay_Payment_Processing( $this, $wc_order, $wc_token_id );
			$api_response               = $payment_processing_service->process_offsite_payment( $success_guid, $failure_guid );

			if ( $api_response->isSuccessful() ) {
				$decoded_api_response = $api_response->jsonDecode();
				$hppURL               = $decoded_api_response->url;

				$transaction_id = $payment_processing_service->transaction_reference . '_' . $order_id;
				$note_text      = sprintf(
					'%s%s awaiting payment via Worldpay. Transaction reference %s',
					get_woocommerce_currency_symbol( $payment_processing_service->wc_order_currency ),
					$payment_processing_service->wc_order_amount,
					$transaction_id
				);
				$wc_order->update_status( 'pending' );
				$wc_order->add_order_note( $note_text );
				$wc_order->set_transaction_id( $transaction_id );

				$wc_order->add_meta_data( 'worldpay_success_guid', $success_guid, true );
				$wc_order->add_meta_data( 'worldpay_failure_guid', $failure_guid, true );
				$wc_order->add_meta_data( 'worldpay_transaction_reference', $transaction_id, true );
				$wc_order->add_meta_data( 'worldpay_transaction_amount', $payment_processing_service->wc_order_converted_amount, true );
				$wc_order->add_meta_data( 'worldpay_save_card', $this->should_save_payment_method(), true );
				$wc_order->save_meta_data();

				$wc_order->save();

				return array(
					'result'   => 'success',
					'redirect' => esc_url_raw( $hppURL ),
				);

			} else {
				throw new Exception( 'Unable to retrieve payment URL.' );
			}
		} catch ( Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->info( $api_response->getResponseMetadata( 'hppInitiatePayment', $e->getMessage() ) );
			}
			if ( isset( $api_response ) && wc_string_to_bool( $this->get_option( 'app_debug' ) ) ) {
				wc_get_logger()->info( $api_response->rawRequest );
				wc_get_logger()->info( $api_response->statusCode . ' | ' . $api_response->rawResponse );
			}
			wc_get_logger()->debug( 'orderId=' . $order_id . ' | ' . $e->getMessage() );

			throw new Exception( 'Something went wrong while processing payment. Please try again.' );
		}
	}

	/**
	 * Get API merchant description.
	 *
	 * @return string
	 */
	public function get_merchant_description() {
		return $this->get_option( 'app_merchant_description' );
	}

	/**
	 * Get result urls for payment processing.
	 *
	 * @param  WC_Order $wc_order
	 * @param  string   $success_guid
	 * @param  string   $failure_guid
	 *
	 * @return ResultURLs
	 */
	public function get_result_urls( WC_Order $wc_order, string $success_guid, string $failure_guid ) {
		$resultUrls = new ResultURLs();

		$resultUrls->cancelURL = is_checkout_pay_page() ? $wc_order->get_checkout_payment_url() : wc_get_checkout_url();

		$page                   = is_checkout_pay_page() ? 'orderpay' : 'checkout';
		$resultUrls->successURL = add_query_arg( 'page', $page, WC()->api_request_url( 'worldpay_results', true ) );
		$resultUrls->successURL = add_query_arg( 'order_id', $wc_order->get_id(), $resultUrls->successURL );
		$resultUrls->successURL = add_query_arg( 'guid', $success_guid, $resultUrls->successURL );

		$resultUrls->failureURL = add_query_arg( 'page', $page, WC()->api_request_url( 'worldpay_results' ), true );
		$resultUrls->failureURL = add_query_arg( 'order_id', $wc_order->get_id(), $resultUrls->failureURL );
		$resultUrls->failureURL = add_query_arg( 'guid', $failure_guid, $resultUrls->failureURL );

		$resultUrls->errorURL   = $resultUrls->failureURL;
		$resultUrls->expiryURL  = $resultUrls->failureURL;
		$resultUrls->pendingURL = $resultUrls->failureURL;

		return $resultUrls;
	}

	protected function store_payment_token_from_response( $user_id, $api_decoded_response ): void {
		if ( ! $user_id ) {
			return;
		}

		if ( empty( $api_decoded_response->tokenPaymentInstrument->href ) ) {
			wc_get_logger()->debug(
				'Invalid or missing payment token fields for order ' . $api_decoded_response->transactionReference ?? ''
			);

			return;
		}

		$token = new WC_Worldpay_Payment_Token();
		$token->save_payment_token(
			$user_id,
			$this->id,
			$api_decoded_response->tokenPaymentInstrument->href ?? '',
			$api_decoded_response->tokenId ?? '',
			$api_decoded_response->paymentInstrument->brand ?? '',
			$api_decoded_response->paymentInstrument->cardNumber ?? '',
			$api_decoded_response->paymentInstrument->cardExpiryDate->year ?? '',
			$api_decoded_response->paymentInstrument->cardExpiryDate->month ?? ''
		);
	}

	/**
	 * Enqueue checkout scripts for Checkout SDK.
	 *
	 * @return void
	 */
	public function checkout_scripts() {
		wp_enqueue_script(
			'worldpay-ecommerce-hpp-scripts',
			woocommerce_worldpay_ecommerce_url( '/assets/frontend/js/worldpay-ecommerce-hpp.js' ),
			array( 'jquery', 'wc-checkout' ),
			WC()->version,
			true
		);
	}

	/**
	 * Send data to javascript.
	 *
	 * @param string $handle
	 *
	 * @return void
	 */
	public function add_checkout_scripts_params( string $handle ) {
		wp_localize_script(
			$handle,
			'access_worldpay_hpp_params',
			array(
				'isPayForOrder' => is_checkout_pay_page(),
			)
		);
	}

	protected function get_order( $order_id ) {
		return wc_get_order( $order_id );
	}

	protected function get_orders( array $args ): array {
		return wc_get_orders( $args );
	}

	protected function terminate(): void {
		exit();
	}
}

/**
 * Add Worldway to payment options for WooCommerce.
 *
 * @param $methods
 *
 * @return mixed
 */
function add_worldpay_gateway( $methods ) {
	$methods[] = 'WC_Payment_Gateway_Access_Worldpay';

	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_worldpay_gateway' );
