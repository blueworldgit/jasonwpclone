<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Worldpay\Api\ApiResponse;
use Worldpay\Api\Enums\Api;
use Worldpay\Api\Enums\CustomerAgreementType;
use Worldpay\Api\Enums\RequestMethods;
use Worldpay\Api\AccessWorldpay;
use Worldpay\Api\Enums\StoredCardUsage;
use Worldpay\Api\Exceptions\FailedPaymentException;
use Worldpay\Api\Forms\DeviceDataCollection;
use Worldpay\Api\Forms\Challenge;
use Worldpay\Api\Enums\PaymentStatus;
use Worldpay\Api\Utils\Helper;
use Worldpay\Api\Utils\AmountHelper;
use Worldpay\Api\Enums\PaymentInstrumentType;
use Worldpay\Api\ValueObjects\PaymentMethods\CreditCard;
use Worldpay\Api\ValueObjects\ThreeDS;
use Worldpay\Api\Enums\Environment;
use Worldpay\Api\Exceptions\InvalidRequestException;
use Worldpay\Api\Enums\ChallengeWindowSize;

class WC_Payment_Gateway_Access_Worldpay_Checkout extends WC_Worldpay_Payment_Method {

	/**
	 * Payment method id.
	 */
	public $id = 'access_worldpay_checkout';

	/**
	 * Payment method id suffix.
	 *
	 * @var string
	 */
	public $id_suffix = 'checkout';

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
	public $method_title = 'Worldpay Payments Onsite';

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
		'subscriptions',
		'subscription_cancellation',
		'subscription_suspension',
		'subscription_reactivation',
		'subscription_amount_changes',
		'subscription_date_changes',
		'subscription_payment_method_change',
		'subscription_payment_method_change_customer',
		'subscription_payment_method_change_admin',
	// 'multiple_subscriptions',
	);

	/**
	 * @var string
	 */
	public $api = Api::ACCESS_WORLDPAY_PAYMENTS_API;

	/**
	 * Initialize payment method gateway.
	 */
	public function __construct() {
		if ( $this->has_tokens_enabled() ) {
			array_push( $this->supports, 'add_payment_method' );
		}

		parent::__construct();

		add_action(
			'woocommerce_api_access_worldpay_checkout_log_frontend_error',
			array(
				$this,
				'access_worldpay_checkout_log_frontend_error_endpoint_handler',
			)
		);

		add_action(
			'woocommerce_api_access_worldpay_checkout_device_data_collection',
			array(
				$this,
				'access_worldpay_checkout_device_data_collection_endpoint_handler',
			)
		);

		add_action(
			'woocommerce_api_access_worldpay_checkout_submit_3ds_device_data',
			array(
				$this,
				'access_worldpay_checkout_submit_3ds_device_data_endpoint_handler',
			)
		);

		add_action(
			'woocommerce_api_access_worldpay_checkout_3ds_challenge',
			array(
				$this,
				'access_worldpay_checkout_3ds_challenge_endpoint_handler',
			)
		);

		add_action(
			'woocommerce_api_access_worldpay_checkout_3ds_challenge_return',
			array(
				$this,
				'access_worldpay_checkout_3ds_challenge_return_endpoint_handler',
			)
		);

		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			array(
				$this,
				'process_blocks_payment',
			),
			1000,
			2
		);

		add_action(
			'woocommerce_scheduled_subscription_payment_' . $this->id,
			array(
				$this,
				'scheduled_subscription_payment',
			),
			10,
			2
		);

		// Update the current request logged_in cookie after a guest user is created to avoid nonce inconsistencies.
		add_action( 'set_logged_in_cookie', array( $this, 'set_cookie_on_current_request' ) );
	}

	public function get_icon() {
		$card_brands     = $this->get_card_brands();
		$card_brand_urls = $this->get_card_brands_url_path();

		$images_html = '<span class="' . $this->id . '-payment-method-images">';
		foreach ( $card_brands as $index => $card_brand ) {
			$images_html .= '<img src="' . esc_url( $card_brand_urls[ $index ] ) . '" alt="' . esc_attr( ucfirst( $card_brand ) ) . '" class="' . $this->id . '-card-brands" />';
		}
		$images_html .= '</span>';

		return $images_html;
	}

	/**
	 * Display payment fields.
	 *
	 * @return void
	 */
	public function payment_fields() {
		$is_checkout_or_pay = ( is_checkout() || is_checkout_pay_page() );
		$show_tokenization  = $this->supports( 'tokenization' ) && $is_checkout_or_pay && $this->has_tokens_enabled();

		if ( $show_tokenization ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
			$this->checkout_form( true, 'token' );
			$this->checkout_form();
			$this->save_payment_method_checkbox();
		} else {
			$this->checkout_form();
		}

		$this->checkout_scripts();
		$this->checkout_styles();
	}

	/**
	 * Display checkout form.
	 *
	 * @param bool   $display
	 * @param  string $type
	 *
	 * @return false|string|void
	 */
	public function checkout_form( bool $display = true, string $type = 'cc' ) {
		ob_start();
		?>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-<?php echo $type; ?>-form" class="wc-credit-<?php echo $this->get_checkout_form_class( $type ); ?>-form" style="display: none;">
			<?php
			do_action( 'woocommerce_credit_card_form_start', $this->id );

			if ( Environment::LIVE_MODE !== $this->get_api_environment() ) {
				echo '<div class="woocommerce-' . esc_attr( $this->id ) . '-info"><strong>Test Mode</strong> - This is not a live transaction.</div>';
			}

			echo ( $type === 'cc' ) ? $this->get_creditcard_form_inputs() : $this->get_cvv_form_inputs();

			do_action( 'woocommerce_credit_card_form_end', $this->id );
			?>

			<div class="clear"></div>
		</fieldset>
		<?php
		if ( $type === 'cc' ) {
			echo $this->get_checkout_extra_html();
		}

		$output = ob_get_clean();
		if ( $display ) {
			echo $output;

			return;
		}

		return $output;
	}

	/**
	 * Return checkout form class based on type.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	protected function get_checkout_form_class( string $type = 'cc' ) {
		$class  = 'wc-credit-';
		$class .= $type === 'cc' ? 'card' : 'token';
		$class .= '-form wc-';
		$class .= $type === 'cc' ? 'payment' : 'token';

		return $class;
	}

	/***
	 * Return extra HTML for checkout.
	 *
	 * @return string
	 */
	protected function get_checkout_extra_html() {
		return '<div id="' . esc_attr( $this->id ) . '-ddc-iframe"></div>
		<div id="' . esc_attr( $this->id ) . '-modal-overlay"></div>
		<div id="' . esc_attr( $this->id ) . '-modal">
			<div id="' . esc_attr( $this->id ) . '-modal-content">
				<div id="' . esc_attr( $this->id ) . '-challenge-iframe"></div>
			</div>
		</div>
		<input type="hidden" id="' . esc_attr( $this->id ) . '-session" name="sessionState" value=""/>';
	}

	/**
	 * Get CVV form inputs.
	 *
	 * @return string
	 */
	protected function get_cvv_form_inputs() {
		return '<div class="form-row form-row-first">
					<label for="' . esc_attr( $this->id ) . '-card-cvc">
						' . esc_html(
						translate(
							'Card Code (CVC)',
							'worldpay-ecommerce-woocommerce'
						)
					) . '
						&nbsp;<span class="required">*</span></label>
					<div id="' . esc_attr( $this->id ) . '-card-cvc"
						class="wc-credit-card-form-card-cvc wc-payment-input input-text"></div>
				</div>';
	}

	/**
	 * Get credit card form inputs.
	 *
	 * @return string
	 */
	protected function get_creditcard_form_inputs() {
		return '<div class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-number">
					' . esc_html(
					translate(
						'Card Number',
						'worldpay-ecommerce-woocommerce'
					)
				) . '
					&nbsp;<span class="required">*</span></label>
				<div id="' . esc_attr( $this->id ) . '-card-number"
					class="wc-wp-credit-card-form-card-number wc-payment-input input-text"></div>
			</div>
			<div class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">
					' . esc_html(
					translate(
						'Expiry Date (MM/YY)',
						'worldpay-ecommerce-woocommerce'
					)
				) . '
					&nbsp;<span class="required">*</span></label>
				<div id="' . esc_attr( $this->id ) . '-card-expiry"
					class="wc-credit-card-form-card-expiry wc-payment-input input-text"></div>
			</div>
			<div class="form-row form-row-last">
				<label for="' . esc_attr( $this->id ) . '-card-cvc">
					' . esc_html(
					translate(
						'Card Code (CVC)',
						'worldpay-ecommerce-woocommerce'
					)
				) . '
					&nbsp;<span class="required">*</span></label>
				<div id="' . esc_attr( $this->id ) . '-card-cvc"
					class="wc-credit-card-form-card-cvc wc-payment-input input-text"></div>
			</div>
			<div class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-holder-name-input">
					' . esc_html(
					translate(
						'Card Holder Name',
						'worldpay-ecommerce-woocommerce'
					)
				) . '
					&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-holder-name-input" name="card_holder_name"
						type="text" autocomplete="off" class="input-text wc-credit-card-form-card-holder-name"/>
			</div>';
	}

	/**
	 * Enqueue checkout scripts for Checkout SDK.
	 *
	 * @return void
	 */
	public function checkout_scripts() {
		$this->enqueue_checkout_sdk_script();
		wp_enqueue_script(
			'worldpay-ecommerce-checkout-library-scripts',
			woocommerce_worldpay_ecommerce_url( '/assets/frontend/js/access-worldpay-checkout.js' ),
			array( 'jquery' ),
			WC()->version,
			true
		);
		wp_enqueue_script(
			'worldpay-ecommerce-checkout-scripts',
			woocommerce_worldpay_ecommerce_url( '/assets/frontend/js/worldpay-ecommerce-checkout.js' ),
			array( 'jquery', 'wc-checkout', 'worldpay-ecommerce-checkout-sdk', 'worldpay-ecommerce-checkout-library-scripts' ),
			WC()->version,
			true
		);
		$this->add_checkout_scripts_params( 'worldpay-ecommerce-checkout-scripts' );
	}

	public function add_checkout_scripts_params( string $handle ) {
		wp_localize_script(
			$handle,
			'access_worldpay_checkout_params',
			array(
				'checkout_id'                         => $this->get_checkout_id(),
				'card_brands'                         => $this->get_card_brands(),
				'submitThreeDsDeviceDataEndpoint'     => wp_nonce_url(
					WC()->api_request_url( 'access_worldpay_checkout_submit_3ds_device_data' ),
					'access_worldpay_checkout_submit_3ds_device_data',
					'_submit_3ds_device_data_wpnonce'
				),
				'logFrontendErrorEndpoint'            => wp_nonce_url(
					WC()->api_request_url( 'access_worldpay_checkout_log_frontend_error' ),
					'access_worldpay_checkout_log_frontend_error',
					'_log_frontend_error_wpnonce'
				),
				'threeDSChallengeEndpoint'            => wp_nonce_url(
					WC()->api_request_url( 'access_worldpay_checkout_3ds_challenge' ),
					'access_worldpay_checkout_3ds_challenge',
					'_3ds_challenge_wpnonce'
				),
				'threeDSDataRequiredStatus'           => PaymentStatus::THREE_DS_DEVICE_DATA_REQUIRED,
				'authorizedStatus'                    => PaymentStatus::AUTHORIZED,
				'sentForSettlementStatus'             => PaymentStatus::SENT_FOR_SETTLEMENT,
				'threeDSDataChallengedStatus'         => PaymentStatus::THREE_DS_CHALLENGED,
				'threeDSAuthenticationApplicationUrl' => Environment::LIVE_MODE === $this->get_api_environment() ?
					AccessWorldpay::LIVE_CARDINAL_URL : AccessWorldpay::TRY_CARDINAL_URL,
				'threeDSChallengeDisplayLightbox'     => true,
				'isPayForOrder'                       => is_checkout_pay_page(),
				'isCheckout'                          => is_checkout() && ! is_checkout_pay_page(),
				'paymentMethodId'                     => $this->id,
				'debugMode'                           => $this->get_merchant_debug_mode(),
			)
		);
	}

	/**
	 * Enqueues checkout js script for Checkout SDK.
	 *
	 * @return void
	 */
	public function enqueue_checkout_sdk_script() {
		$src = AccessWorldpay::checkoutSdkUrl( $this->get_api_environment() );
		wp_enqueue_script(
			'worldpay-ecommerce-checkout-sdk',
			$src,
			array( 'jquery' ),
			WC()->version,
			true
		);
	}

	/**
	 * Enqueue checkout stylesheets for Checkout SDK.
	 *
	 * @return void
	 */
	public function checkout_styles() {
		wp_register_style(
			'worldpay-ecommerce-checkout-styles',
			woocommerce_worldpay_ecommerce_url( 'assets/frontend/css/worldpay-ecommerce-checkout.css' ),
			array(),
			WC()->version
		);
		wp_enqueue_style( 'worldpay-ecommerce-checkout-styles' );
	}

	/**
	 * Initialize admin settings form.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = WC_Access_Worldpay_Checkout_Form_Fields::init_form_fields();
	}

	/**
	 * Get API merchant checkout id.
	 *
	 * @return string
	 */
	public function get_checkout_id() {
		return Environment::LIVE_MODE === $this->get_api_environment() ?
			$this->get_option( 'app_merchant_live_checkout_id' ) :
			$this->get_option( 'app_merchant_try_checkout_id' );
	}

	/**
	 * Get API merchant card brands.
	 *
	 * @return array|string[]
	 */
	public function get_card_brands() {
		$card_brands = $this->get_option( 'app_card_brands' );

		if ( is_string( $card_brands ) ) {
			$card_brands = explode( ',', $card_brands );
		}

		return array_map( 'trim', $card_brands );
	}

	/**
	 * Get Card Brands url path.
	 *
	 * @return array|string[]
	 */
	public function get_card_brands_url_path() {
		$card_brands = $this->get_card_brands();

		return array_map(
			function ( $card_brand ) {
				return woocommerce_worldpay_ecommerce_url( 'assets/frontend/images/' . $card_brand . '.png' );
			},
			$card_brands
		);
	}

	/**
	 * Process payment.
	 *
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {
		$result       = array();
		$api_response = null;

		try {
			$wc_order    = $this->get_wc_order_by_id( $order_id );
			$wc_token_id = (int) $this->get_saved_payment_method_id();

			$session_href = $_POST['sessionState'] ?? ( $_POST['sessionstate'] ?? '' );
			$session_href = sanitize_url( wp_unslash( $session_href ) );
			if ( ! $session_href ) {
				throw new Exception( 'OrderID: ' . $order_id . '. Payment session is not set up.' );
			}
			$card_holder_name = isset( $_POST['card_holder_name'] )
				? sanitize_text_field( wp_unslash( $_POST['card_holder_name'] ) )
				: '';
			if ( empty( $wc_token_id ) && empty( $card_holder_name ) ) {
				throw new Exception( 'OrderID: ' . $order_id . '. Card holder name is not set up.' );
			}

			$payment_processing_service = new WC_Worldpay_Payment_Processing( $this, $wc_order, $wc_token_id );

			if ( $this->order_is_subscription( $order_id ) ) {
				$api_response = $payment_processing_service->process_onsite_payment_method_change(
					$session_href,
					$card_holder_name
				);
			} else {
				$api_response = $payment_processing_service->process_onsite_payment(
					$session_href,
					$card_holder_name
				);
			}

			if ( ! $api_response->isSuccessful() ) {
				throw new \Exception( 'OrderID: ' . $order_id . '. Unable to initiate payment.' );
			}
			$api_decoded_response = $api_response->jsonDecode();
			switch ( $api_decoded_response->outcome ) {

				case PaymentStatus::AUTHORIZED:
				case PaymentStatus::SENT_FOR_SETTLEMENT:
					$this->payment_completed( $wc_order, $payment_processing_service, $api_decoded_response );

					$result = array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $wc_order ),
					);
					break;

				case PaymentStatus::THREE_DS_DEVICE_DATA_REQUIRED:
					$wc_order->update_meta_data(
						'_access_worldpay_checkout_supply3dsDeviceLinkData',
						$api_decoded_response->_actions->supply3dsDeviceData->href ?? ''
					);
					$wc_order->update_meta_data( '_access_worldpay_checkout_tokenId', $wc_token_id );
					$wc_order->save();
					$this->payment_pending( $wc_order, $api_decoded_response->transactionReference );
					$result = array(
						'result'                          => 'success',
						'messages'                        => '',
						'outcome'                         => $api_decoded_response->outcome,
						'transaction_reference'           => $api_decoded_response->transactionReference,
						'order_id'                        => $order_id,
						'deviceDataCollectionUrl'         => add_query_arg(
							array(
								'_device_data_collection_wpnonce' => wp_create_nonce( 'access_worldpay_checkout_device_data_collection' ),
								'jwt' => $api_decoded_response->deviceDataCollection->jwt ?? '',
								'url' => $api_decoded_response->deviceDataCollection->url ?? '',
								'bin' => $api_decoded_response->deviceDataCollection->bin ?? '',
							),
							WC()->api_request_url( 'access_worldpay_checkout_device_data_collection' )
						),
						'submitThreeDsDeviceDataEndpoint' => add_query_arg(
							array(
								'_submit_3ds_device_data_wpnonce' => wp_create_nonce( 'access_worldpay_checkout_submit_3ds_device_data' ),
							),
							WC()->api_request_url( 'access_worldpay_checkout_submit_3ds_device_data' )
						),
					);
					break;

				case PaymentStatus::SENT_FOR_CANCELLATION:
				case PaymentStatus::FRAUD_HIGH_RISK:
				case PaymentStatus::REFUSED:
					$this->payment_failed( $wc_order, $api_decoded_response->transactionReference );
					throw new FailedPaymentException(
						__(
							'Payment failed. Please try again.',
							'worldpay-ecommerce-woocommerce'
						)
					);

				default:
					throw new \Exception( 'OrderID: ' . $order_id . '. Unexpected payment outcome received: ' . $api_decoded_response->outcome );
			}
		} catch ( \Exception $e ) {
			WC()->session->refresh_totals = true;
			if ( isset( $api_response ) ) {
				wc_get_logger()->alert( $api_response->getResponseMetadata( 'checkoutInitiatePayment', $e->getMessage() ) );
			}
			$this->log( $order_id ?? '', $api_response ?? null, $e->getMessage(), 'checkoutInitiatePayment' );
			$message = __(
				'Something went wrong while processing payment. Please try again.',
				'worldpay-ecommerce-woocommerce'
			);
			if ( $e instanceof FailedPaymentException ) {
				$message = $e->getMessage();
			}

			$result = array(
				'result'   => 'failure',
				'messages' => $message,
			);

			wc_add_notice( $message, 'error' );
		}
		if ( is_checkout_pay_page() || $this->is_subscription_change_payment_method_page() ) {
			wp_send_json( $result );
			exit();
		}

		return $result;
	}

	/**
	 * Process payment with context for Blocks Checkout.
	 *
	 * @param  PaymentContext $context
	 * @param  PaymentResult  $payment_result
	 *
	 * @return void
	 * @throws \Worldpay\Api\Exceptions\InvalidArgumentException
	 */
	public function process_blocks_payment( PaymentContext $context, PaymentResult &$payment_result ) {
		if ( $this->id !== $context->payment_method ) {
			return;
		}
		if ( in_array( $payment_result->status, array( 'failure', 'error' ), true ) ) {
			return;
		}
		if ( PaymentStatus::THREE_DS_DEVICE_DATA_REQUIRED === $payment_result->payment_details['outcome'] ) {
			$payment_result->set_status( 'pending' );
		}
		$payment_result->set_redirect_url( $context->order->get_checkout_order_received_url() );
	}

	/**
	 * Process refund.
	 *
	 * @param $order_id
	 * @param $amount
	 * @param $reason
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$api_response = null;
		if ( empty( $amount ) || $amount <= 0 ) {
			throw new \Exception( 'Invalid amount value: ' . esc_html( $amount ) );
		}
		try {
			$wc_order = wc_get_order( $order_id );

			$api = $this->initialize_api();

			$payment_link_data = $wc_order->get_meta( 'payment_link_data' );
			if ( ! isset( $payment_link_data ) ) {
				throw new \Exception( 'Invalid payment link data: ' . esc_html( $payment_link_data ) );
			}
			$payment_amount = $wc_order->get_meta( 'worldpay_transaction_amount' );
			if ( ! isset( $payment_amount ) ) {
				throw new \Exception( 'Invalid payment amount: ' . esc_html( $payment_amount ) );
			}
			$refund_amount            = AmountHelper::decimalToExponentDelimiter(
				$amount,
				$wc_order->get_currency(),
				get_locale()
			);
			$partial_refund_reference = Helper::generateString( 12 ) . '-' . $order_id;

			if ( $refund_amount < $payment_amount ) {
				$api_response = $api->partialRefund( $refund_amount )
									->withPartialOperationReference( $partial_refund_reference )
									->withLinkData( $payment_link_data )
									->withCurrency( $wc_order->get_currency() )
									->execute();
			} else {
				$api_response = $api->refund( $refund_amount )
									->withLinkData( $payment_link_data )
									->execute();
			}

			if ( $api_response->isSuccessful() ) {
				$note_text = sprintf(
					'%s%s sent for payment refund.' . ( ( $amount < $wc_order->get_total() ) ? ' Partial refund reference: %s' : '' ),
					get_woocommerce_currency_symbol( $wc_order->get_currency() ),
					$amount,
					$partial_refund_reference
				);
				$wc_order->add_order_note( $note_text );

				return true;
			} else {
				throw new \Exception( 'Something went wrong while requesting payment refund.' );
			}
		} catch ( \Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->info( $api_response->getResponseMetadata( 'checkoutRefund', $e->getMessage() ) );
			}
			$this->log( $order_id, $api_response, $e->getMessage(), 'checkoutRefund' );

			throw new Exception( __( 'Something went wrong while requesting payment refund.', 'worldpay-ecommerce-woocommerce' ) );
		}
	}

	/**
	 * @param  WC_Order                       $wc_order
	 * @param  WC_Worldpay_Payment_Processing $payment_processing_service
	 * @param $api_decoded_response
	 *
	 * @return void
	 */
	public function payment_completed(
		WC_Order $wc_order,
		WC_Worldpay_Payment_Processing $payment_processing_service,
		$api_decoded_response
	): void {
		$wc_token_id = $payment_processing_service->wc_token_id ?? null;
		// if token present in response, a token was requested
		if ( isset( $api_decoded_response->token->href ) ) {
			$token       = new WC_Worldpay_Payment_Token();
			$wc_token_id = $token->save_payment_token(
				$wc_order->get_user_id(),
				$this->id,
				$api_decoded_response->token->href ?? '',
				$api_decoded_response->token->tokenId ?? '',
				$api_decoded_response->paymentInstrument->cardBrand ?? '',
				$api_decoded_response->token->cardNumber ?? '',
				$api_decoded_response->token->cardExpiry->year ?? '',
				$api_decoded_response->token->cardExpiry->month ?? ''
			);
		}

		$wc_token = WC_Payment_Tokens::get( $wc_token_id );
		if ( $this->order_contains_subscription( $wc_order ) ) {
			$subscriptions = wcs_get_subscriptions_for_order( $wc_order );
			foreach ( $subscriptions as $subscription ) {
				$subscription->add_payment_token( $wc_token );
				$subscription->add_meta_data( 'worldpay_scheme_reference', $api_decoded_response->schemeReference ?? '', true );
				$subscription->save_meta_data();
			}
		}
		if ( $this->order_is_subscription( $wc_order->get_id() ) ) {
			$wc_order->add_payment_token( $wc_token );
			$wc_order->add_meta_data( 'worldpay_scheme_reference', $api_decoded_response->schemeReference ?? '', true );

			$order_note = sprintf(
				'Payment method successfully verified via Worldpay. Transaction reference: %s',
				$api_decoded_response->transactionReference
			);
		} else {
			$wc_order->add_meta_data( 'worldpay_transaction_amount', $payment_processing_service->wc_order_converted_amount, true );
			$wc_order->add_meta_data( 'payment_env', $this->get_api_environment(), true );
			$selfRef = explode( '/', $api_decoded_response->_links->self->href ?? '' );
			$wc_order->add_meta_data( 'payment_link_data', array_pop( $selfRef ), true );

			$order_note = sprintf(
				'%s%s. Payment successful via Worldpay. Transaction reference: %s',
				$payment_processing_service->wc_order_amount,
				get_woocommerce_currency_symbol( $payment_processing_service->wc_order_currency ),
				$api_decoded_response->transactionReference
			);
		}

		$wc_order->add_order_note( $order_note );

		$wc_order->save_meta_data();
		$wc_order->save();

		$wc_order->payment_complete( $api_decoded_response->transactionReference );
	}

	/**
	 * Payment is pending.
	 *
	 * @param  WC_Order $wc_order
	 * @param $transaction_reference
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function payment_pending( WC_Order $wc_order, $transaction_reference ): void {
		if ( $this->order_is_subscription( $wc_order->get_id() ) ) {
			return;
		}

		$wc_order->update_status( 'pending' );
		$wc_order->set_transaction_id( $transaction_reference );
		$order_note = sprintf(
			'%s%s. Awaiting payment via Worldpay. Transaction reference: %s',
			$wc_order->get_total(),
			get_woocommerce_currency_symbol( $wc_order->get_currency() ),
			$transaction_reference
		);
		$wc_order->add_order_note( $order_note );
		$wc_order->save();
	}

	/**
	 * Payment is failed.
	 *
	 * @param  WC_Order $wc_order
	 * @param $transaction_reference
	 * @param  string   $message
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function payment_failed( WC_Order $wc_order, $transaction_reference, $message = '' ): void {
		if ( $this->order_is_subscription( $wc_order->get_id() ) ) {
			$order_note = sprintf(
				'Payment method failed verification via Worldpay. Transaction reference: %s',
				$transaction_reference
			);
			$wc_order->add_order_note( $order_note );
			$wc_order->save();

			return;
		}

		$wc_order->set_transaction_id( $transaction_reference );
		$note = 'Payment failed via Worldpay';
		if ( $message ) {
			$note .= ' ' . $message;
		}
		$order_note = sprintf(
			'%s%s. %s Transaction reference: %s',
			$wc_order->get_total(),
			get_woocommerce_currency_symbol( $wc_order->get_currency() ),
			$note,
			$transaction_reference
		);
		$wc_order->add_order_note( $order_note );
		$wc_order->update_status( 'failed' );
		$wc_order->save();
	}

	/**
	 * Adds order note.
	 *
	 * @param  WC_Order $wc_order
	 * @param  string   $order_note
	 * @param  string   $transaction_reference
	 *
	 * @return void
	 */
	public function payment_order_note( WC_Order $wc_order, string $order_note, string $transaction_reference = '' ) {
		$order_note = 'Worldpay Onsite. ' . $order_note;
		if ( $transaction_reference ) {
			$order_note .= ' Transaction reference: ' . $transaction_reference;
		}
		$wc_order->add_order_note( $order_note );
		$wc_order->save();
	}

	/**
	 * Log errors if encountered at checkout sdk setup.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function access_worldpay_checkout_log_frontend_error_endpoint_handler() {
		try {
			if ( ! check_ajax_referer(
				'access_worldpay_checkout_log_frontend_error',
				'_log_frontend_error_wpnonce',
				false
			) ) {
				throw new \Exception( 'Invalid request nonce.' );
			}
			if ( RequestMethods::POST !== $_SERVER['REQUEST_METHOD'] ) {
				throw new \Exception( 'Invalid request method: ' . $_SERVER['REQUEST_METHOD'] );
			}

			$error_message = isset( $_REQUEST['message'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['message'] ) ) : '';
			wc_get_logger()->alert(
				'messageType=logFrontendError | orderId=' . absint( WC()->session->get( 'order_awaiting_payment' ) ) . ' | detailMessage=' . $error_message
			);
			wp_send_json(
				array(
					__(
						'Something went wrong while processing your payment. Please try again later.',
						'worldpay-ecommerce-woocommerce'
					),
				)
			);

			exit();
		} catch ( \Exception $e ) {
			wc_get_logger()->alert( 'messageType=logFrontendError | detailMessage=' . $e->getMessage() );
			exit();
		}
	}

	/**
	 * Render device data collection form.
	 *
	 * @return void
	 */
	public function access_worldpay_checkout_device_data_collection_endpoint_handler() {
		try {
			$url = isset( $_GET['url'] ) ? sanitize_url( wp_unslash( $_GET['url'] ) ) : '';
			$bin = isset( $_GET['bin'] ) ? sanitize_text_field( wp_unslash( $_GET['bin'] ) ) : '';
			$jwt = isset( $_GET['jwt'] ) ? sanitize_text_field( wp_unslash( $_GET['jwt'] ) ) : '';

			if ( ! check_ajax_referer(
				'access_worldpay_checkout_device_data_collection',
				'_device_data_collection_wpnonce',
				false
			) ) {
				throw new \Exception( 'Invalid request nonce.' );
			}
			if ( RequestMethods::GET !== $_SERVER['REQUEST_METHOD'] ) {
				throw new \Exception( 'Invalid request method: ' . $_SERVER['REQUEST_METHOD'] );
			}
			if ( ! $url ) {
				throw new \Exception( 'Invalid DDC Url.' );
			}

			$device_data_collection_form = new DeviceDataCollection( $this->id . '-ddc-form', $url, $bin, $jwt );
			echo $device_data_collection_form->render();
			exit();
		} catch ( \Exception $e ) {
			$data_to_log = array(
				'requestType'   => 'renderDDCForm',
				'detailMessage' => $e->getMessage(),
				'url'           => $url ?? '',
				'bin'           => $bin ?? '',
				'jwt'           => $jwt ?? '',
			);
			wc_get_logger()->error(
				implode(
					' | ',
					array_map( fn( $k, $v ) => "$k=$v", array_keys( $data_to_log ), $data_to_log )
				)
			);
			$result = array(
				'error'    => true,
				'messages' => $e->getMessage(),
			);
			wp_send_json_error( $result );
			exit();
		}
	}

	/**
	 * Data device collection provider endpoint handler.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function access_worldpay_checkout_submit_3ds_device_data_endpoint_handler() {
		$api_response = null;
		$result       = array();
		try {
			$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
			$wc_order = $this->get_wc_order_by_id( $order_id );

			if ( ! check_ajax_referer(
				'access_worldpay_checkout_submit_3ds_device_data',
				'_submit_3ds_device_data_wpnonce',
				false
			) ) {
				throw new \Exception( 'Order ID: ' . $order_id . '. Invalid request nonce.' );
			}
			if ( RequestMethods::POST !== $_SERVER['REQUEST_METHOD'] ) {
				throw new \Exception( 'Order ID: ' . $order_id . '. Invalid request method: ' . $_SERVER['REQUEST_METHOD'] );
			}

			$api = $this->initialize_api();

			$request_transaction_reference = isset( $_POST['transaction_reference'] )
				? sanitize_text_field( wp_unslash( $_POST['transaction_reference'] ) )
				: '';
			$order_transaction_reference   = $wc_order->get_transaction_id();
			if ( $request_transaction_reference !== $order_transaction_reference ) {
				$note = sprintf(
					'Transaction reference mismatch. Expecting %s, found %s.',
					$request_transaction_reference,
					$order_transaction_reference
				);
				$this->payment_order_note( $wc_order, $note );
				$wc_order->save();
				throw new \Exception(
					sprintf(
						'OrderID: %s. Transaction reference mismatch. Expecting %s, found %s',
						$order_id,
						$request_transaction_reference,
						$order_transaction_reference
					)
				);
			}

			$collection_reference = isset( $_POST['collection_reference'] )
				? sanitize_text_field( wp_unslash( $_POST['collection_reference'] ) )
				: '';

			$link_data = $wc_order->get_meta( '_access_worldpay_checkout_supply3dsDeviceLinkData' );
			if ( empty( $link_data ) ) {
				$this->payment_order_note( $wc_order, 'Unable to retrieve supply3dsDeviceLinkData from order meta.', $request_transaction_reference );
				$wc_order->save();
				throw new \Exception( 'OrderID: ' . $order_id . '. Unable to retrieve supply3dsDeviceLinkData from order meta.' );
			}

			$api_response = $api->provide3DSDeviceData()
											  ->withLinkData( $link_data )
											  ->withCollectionReference( $collection_reference )
											  ->execute();
			if ( ! $api_response->isSuccessful() ) {
				$this->payment_order_note(
					$wc_order,
					'Unable to supply 3DS device data.',
					$request_transaction_reference
				);
				$wc_order->save();
				throw new \Exception( 'OrderID: ' . $order_id . '. Unable to supply 3DS device data.' );
			}

			$api_decoded_response = $api_response->jsonDecode();
			$result               = array(
				'error'                 => false,
				'outcome'               => $api_decoded_response->outcome ?? '',
				'transaction_reference' => $api_decoded_response->transactionReference ?? '',
			);

			switch ( $api_decoded_response->outcome ) {
				case PaymentStatus::AUTHORIZED:
				case PaymentStatus::SENT_FOR_SETTLEMENT:
					$result['result']   = 'success';
					$result['redirect'] = $wc_order->get_checkout_order_received_url();

					$token_id                   = $wc_order->get_meta( '_access_worldpay_checkout_tokenId' );
					$payment_processing_service = new WC_Worldpay_Payment_Processing( $this, $wc_order, $token_id );
					$this->payment_completed( $wc_order, $payment_processing_service, $api_decoded_response );
					break;

				case PaymentStatus::THREE_DS_CHALLENGED:
					$wc_order->update_meta_data(
						'_access_worldpay_checkout_complete3DSChallengeLinkData',
						$api_decoded_response->_actions->complete3dsChallenge->href ?? ''
					);
					$wc_order->save();
					$result['result']       = 'success';
					$result['challengeUrl'] = add_query_arg(
						array(
							'_3ds_challenge_wpnonce' => wp_create_nonce( 'access_worldpay_checkout_3ds_challenge' ),
							'url'                    => $api_decoded_response->challenge->url ?? '',
							'jwt'                    => $api_decoded_response->challenge->jwt ?? '',
							'payload'                => $api_decoded_response->challenge->payload ?? '',
							'MD'                     => base64_encode(
								json_encode(
									array(
										'order_id' => $order_id,
										'transaction_reference' => $api_decoded_response->transactionReference ?? '',
									)
								)
							),
						),
						WC()->api_request_url( 'access_worldpay_checkout_3ds_challenge' )
					);

					$challenge_window_size = $this->get_challenge_window_size( $api_decoded_response->challenge->payload ?? '' );
					if ( ! empty( $challenge_window_size ) ) {
						$result['challengeWindowSize'] = $challenge_window_size;
					}
					break;

				case PaymentStatus::SENT_FOR_CANCELLATION:
				case PaymentStatus::REFUSED:
				case PaymentStatus::THREE_DS_AUTHENTICATION_FAILED:
				case PaymentStatus::THREE_DS_UNAVAILABLE:
					$reason           = '.';
					$result['result'] = 'failure';
					if ( PaymentStatus::THREE_DS_AUTHENTICATION_FAILED === $api_decoded_response->outcome ) {
						$reason = ' because 3DS authentication did not succeed.';
					} elseif ( PaymentStatus::THREE_DS_UNAVAILABLE === $api_decoded_response->outcome ) {
						$reason = ' because 3DS is not available at the moment.';
					}
					$result['messages'] = 'Payment failed' . $reason . ' Please try again.';
					$this->payment_failed( $wc_order, $api_decoded_response->transactionReference, $reason );
					break;

				default:
					$this->payment_order_note( $wc_order, 'Unexpected supply 3DS device data outcome received: ' . $api_decoded_response->outcome, $request_transaction_reference );
					$wc_order->save();
					throw new \Exception( 'OrderID: ' . $order_id . '. Unexpected provide3DSDeviceData outcome received: ' . $api_decoded_response->outcome );
			}
		} catch ( \Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->info( $api_response->getResponseMetadata( 'provide3DSDeviceData', $e->getMessage() ) );
			}
			$this->log(
				$order_id ?? '',
				$api_response ?? null,
				$e->getMessage(),
				'provide3DSDeviceData'
			);
			$result = array(
				'error'    => true,
				'result'   => 'failure',
				'messages' => __(
					'Something went wrong while processing your payment. Please try again later.',
					'worldpay-ecommerce-woocommerce'
				),
			);
		}

		wp_send_json( $result );
		exit();
	}

	/**
	 * 3DS challenge form renderer.
	 *
	 * @return void
	 */
	public function access_worldpay_checkout_3ds_challenge_endpoint_handler() {
		try {
			$url = isset( $_GET['url'] ) ? sanitize_url( wp_unslash( $_GET['url'] ) ) : '';
			$md  = isset( $_GET['MD'] ) ? sanitize_text_field( wp_unslash( $_GET['MD'] ) ) : '';
			$jwt = isset( $_GET['jwt'] ) ? sanitize_text_field( wp_unslash( $_GET['jwt'] ) ) : '';

			if ( ! check_ajax_referer( 'access_worldpay_checkout_3ds_challenge', '_3ds_challenge_wpnonce', false ) ) {
				throw new \Exception( 'Invalid request nonce.' );
			}
			if ( RequestMethods::GET !== $_SERVER['REQUEST_METHOD'] ) {
				throw new \Exception( 'Invalid request method: ' . $_SERVER['REQUEST_METHOD'] );
			}

			$challenge_form = new Challenge( $this->id . '-challenge-form', $url, $jwt, $md );
			echo $challenge_form->render();
			exit();
		} catch ( \Exception $e ) {
			$data_to_log = array(
				'requestType'   => 'renderChallengeForm',
				'detailMessage' => $e->getMessage(),
				'url'           => $url ?? '',
				'MD'            => $md ?? '',
				'jwt'           => $jwt ?? '',
			);
			wc_get_logger()->error(
				implode(
					' | ',
					array_map( fn( $k, $v ) => "$k=$v", array_keys( $data_to_log ), $data_to_log )
				)
			);
			echo __(
				'Something went wrong while processing your payment challenge. Please try again.',
				'worldpay-ecommerce-woocommerce'
			);
			exit();
		}
	}

	/**
	 * 3DS challenge return endpoint handler.
	 *
	 * @return void
	 */
	public function access_worldpay_checkout_3ds_challenge_return_endpoint_handler() {
		$api_response = null;
		$order_id     = null;
		try {
			if ( ! isset( $_POST['MD'] ) ) {
				throw new \Exception( 'Invalid request. MD field not present in issuer challenge returnUrl.' );
			}
			$MD       = json_decode( base64_decode( $_POST['MD'] ) );
			$order_id = $MD->order_id ?? 0;
			$wc_order = $this->get_wc_order_by_id( $order_id );

			if ( RequestMethods::POST !== $_SERVER['REQUEST_METHOD'] ) {
				throw new \Exception( throw new \Exception( 'OrderID: ' . $order_id . '. Invalid request method: ' . $_SERVER['REQUEST_METHOD'] ) );
			}
			$this->validate_request( $_REQUEST, $order_id );

			$request_transaction_reference = $MD->transaction_reference ?? '';
			$order_transaction_reference   = $wc_order->get_transaction_id();
			if ( $request_transaction_reference !== $order_transaction_reference ) {
				$note = sprintf(
					'Transaction reference mismatch. Expecting %s, found %s.',
					$request_transaction_reference,
					$order_transaction_reference
				);
				$this->payment_order_note( $wc_order, $note );
				$wc_order->save();
				throw new \Exception(
					sprintf(
						'OrderId: %s. Transaction reference mismatch. Expecting %s, found %s.',
						$order_id,
						$request_transaction_reference,
						$order_transaction_reference
					)
				);
			}

			$link_data = $wc_order->get_meta( '_access_worldpay_checkout_complete3DSChallengeLinkData' );
			if ( empty( $link_data ) ) {
				$this->payment_order_note(
					$wc_order,
					'Unable to retrieve complete3DSChallengeLinkData from order meta.',
					$request_transaction_reference
				);
				$wc_order->save();
				throw new \Exception( 'OrderID: ' . $order_id . '. Unable to retrieve complete3DSChallengeLinkData from order meta.' );
			}

			$api          = $this->initialize_api();
			$api_response = $api->challenge3DSResult()
								->withLinkData( $link_data )
								->execute();
			if ( ! $api_response->isSuccessful() ) {
				$this->payment_order_note(
					$wc_order,
					'Unable to continue with payment after 3DS challenge.',
					$request_transaction_reference
				);
				$wc_order->save();
				throw new \Exception( 'OrderID: ' . $order_id . '. Unable to continue with payment after 3DS challenge.' );
			}
			$api_decoded_response = $api_response->jsonDecode();

			switch ( $api_decoded_response->outcome ) {

				case PaymentStatus::AUTHORIZED:
				case PaymentStatus::SENT_FOR_SETTLEMENT:
					$result = array(
						'result'                => 'success',
						'outcome'               => $api_decoded_response->outcome,
						'transaction_reference' => $api_decoded_response->transactionReference,
						'redirect'              => $wc_order->get_checkout_order_received_url(),
					);

					$token_id                   = $wc_order->get_meta( '_access_worldpay_checkout_tokenId' );
					$payment_processing_service = new WC_Worldpay_Payment_Processing( $this, $wc_order, $token_id );
					$this->payment_completed( $wc_order, $payment_processing_service, $api_decoded_response );
					break;

				case PaymentStatus::SENT_FOR_CANCELLATION:
				case PaymentStatus::REFUSED:
				case PaymentStatus::THREE_DS_AUTHENTICATION_FAILED:
				case PaymentStatus::THREE_DS_UNAVAILABLE:
					$reason = '';
					if ( PaymentStatus::THREE_DS_AUTHENTICATION_FAILED === $api_decoded_response->outcome ) {
						$reason = ' because 3DS authentication did not succeed.';
					} elseif ( PaymentStatus::THREE_DS_UNAVAILABLE === $api_decoded_response->outcome ) {
						$reason = ' because 3DS is not available at the moment.';
					} else {
						$reason = '.';
					}
					$result['result']   = 'failure';
					$result['messages'] = 'Payment failed' . $reason . ' Please try again.';
					$this->payment_failed( $wc_order, $api_decoded_response->transactionReference, $reason );
					break;

				default:
					$this->payment_order_note( $wc_order, 'Unexpected challenge 3DS outcome received: ' . $api_decoded_response->outcome, $request_transaction_reference );
					$wc_order->save();
					throw new \Exception( 'OrderID: ' . $order_id . '. Unexpected challenge3DSResult outcome received: ' . $api_decoded_response->outcome );
			}
		} catch ( \Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->alert( $api_response->getResponseMetadata( 'challenge3DSResult', $e->getMessage() ) );
			}
			$exceptionMessage = __(
				'Something went wrong while processing your payment. Please try again.',
				'worldpay-ecommerce-woocommerce'
			);
			$result           = array(
				'error'    => true,
				'messages' => $exceptionMessage,
				'refresh'  => true,
				'result'   => 'failure',
			);
			$this->log( $order_id ?? '', $api_response ?? null, $e->getMessage(), 'challenge3DSResult' );
		}

		$data = json_encode( $result );
		include_once dirname( WORLDPAY_ECOMM_WC_PLUGIN_FILE ) . '/templates/payment_result_after_3ds_challenge.php';
		echo $output ?? '';
		exit();
	}

	/**
	 * Validate challenge return request with a custom way because nonce is not applicable.
	 *
	 * @param $request
	 * @param $order_id
	 *
	 * @return void
	 * @throws InvalidRequestException
	 */
	public function validate_request( $request, $order_id ) {
		$timestamp = $request['timestamp'] ?? '';
		$hash      = $request['hash'] ?? '';

		$expected_hash = hash_hmac( 'sha256', $timestamp, wp_salt() );
		if ( abs( time() - $timestamp ) > 900 || ! hash_equals( $expected_hash, $hash ) ) {
			throw new InvalidRequestException( 'OrderID: ' . $order_id . '. Invalid request because it expired or the hash is invalid.' );
		}
	}

	/**
	 * Create payment instrument object.
	 *
	 * @param $session_href
	 * @param $card_holder_name
	 *
	 * @return CreditCard
	 */
	public function create_payment_instrument_object( $session_href, $card_holder_name ) {
		$payment_instrument                 = new CreditCard( PaymentInstrumentType::CHECKOUT );
		$payment_instrument->sessionHref    = $session_href;
		$payment_instrument->cardHolderName = $card_holder_name;

		return $payment_instrument;
	}

	/**
	 * Create 3DS object.
	 *
	 * @return ThreeDS
	 */
	public function create_three_ds_object() {
		$challenge_return_url_hash_data  = $this->create_validation_hash_for_url();
		$three_ds                        = new ThreeDS();
		$three_ds->challengeReturnUrl    = add_query_arg(
			array(
				'hash'      => $challenge_return_url_hash_data['hash'],
				'timestamp' => $challenge_return_url_hash_data['timestamp'],
			),
			WC()->api_request_url( 'access_worldpay_checkout_3ds_challenge_return' )
		);
		$three_ds->deviceDataAgentHeader = wc_get_user_agent();

		return $three_ds;
	}

	/**
	 * Create unique hash for the challenge return url for request verification.
	 *
	 * @return array
	 */
	public function create_validation_hash_for_url() {
		$timestamp = time();

		return array(
			'hash'      => hash_hmac( 'sha256', $timestamp, wp_salt() ),
			'timestamp' => $timestamp,
		);
	}

	/**
	 * Set link data for refund urls for order metadata.
	 *
	 * @param  string $url
	 *
	 * @return array
	 */
	public function get_link_data_for_payment_management( string $url ) {
		$data = array(
			'payment_env' => Environment::LIVE_MODE,
		);
		if ( str_contains( $url, 'try' ) ) {
			$data['payment_env'] = Environment::TRY_MODE;
		}
		$url_strings       = explode( '/', str_replace( 'https://', '', $url ) );
		$data['link_data'] = $url_strings[3];

		return $data;
	}

	/**
	 * Log requests and responses in case of issues.
	 *
	 * @return void
	 */
	public function log( $order_id, $api_response, $message, $requestType ) {
		if ( ! wc_string_to_bool( $this->get_option( 'app_debug' ) ) ) {
			return;
		}
		$data_to_log  = array(
			'requestType' => $requestType,
			'orderId'     => $order_id ?? '',
		);
		$data_to_log  = http_build_query( $data_to_log, '', ' | ' );
		$data_to_log .= ' | message=' . $message;
		if ( $api_response instanceof ApiResponse ) {
			$data_to_log .= $api_response->rawRequest ? ' | apiRawRequest=' . $api_response->rawRequest : '';
			$data_to_log .= $api_response->rawResponse ? ' | apiRawResponse=' . $api_response->rawResponse : '';
			$data_to_log .= $api_response->statusCode ? ' | apiResponseStatus=' . $api_response->statusCode : '';
		}
		wc_get_logger()->debug( $data_to_log );
	}

	/**
	 * Return challenge window size from payload
	 *
	 * @param $payload
	 *
	 * @return array
	 */
	public function get_challenge_window_size( $payload = null ) {
		$challenge_window_size = array();

		if ( empty( $payload ) ) {
			return $challenge_window_size;
		}

		$payload = base64_decode( $payload );
		if ( empty( $payload ) ) {
			return $challenge_window_size;
		}

		$payload = json_decode( $payload, true );
		if ( empty( $payload ) ) {
			return $challenge_window_size;
		}

		$challenge_window_mode = $payload['challengeWindowSize'] ?? '';
		if ( empty( $challenge_window_mode ) ) {
			return $challenge_window_size;
		}

		$challenge_window_size = ChallengeWindowSize::$challengeWindowSizeMapping[ $challenge_window_mode ] ?? array();
		if ( '05' == $challenge_window_mode || '5' == $challenge_window_mode ) {
			$challenge_window_size = array(
				'width'  => 600,
				'height' => 700,
			);
		}

		return $challenge_window_size;
	}

	protected function store_payment_token_from_response( $user_id, $api_decoded_response ) {
		if ( ! $user_id ) {
			return;
		}

		if ( empty( $api_decoded_response->token->href ) ) {
			wc_get_logger()->debug(
				'Invalid or missing payment token fields for order ' . $api_decoded_response->transactionReference
			);

			return;
		}

		$token = new WC_Worldpay_Payment_Token();
		$token->save_payment_token(
			$user_id,
			$this->id,
			$api_decoded_response->token->href ?? '',
			$api_decoded_response->token->tokenId ?? '',
			$api_decoded_response->paymentInstrument->cardBrand ?? '',
			$api_decoded_response->token->cardNumber ?? '',
			$api_decoded_response->token->cardExpiry->year ?? '',
			$api_decoded_response->token->cardExpiry->month ?? ''
		);
	}

	public function add_payment_method(): array {
		try {
			$session_href = $_POST['sessionState'] ?? ( $_POST['sessionstate'] ?? '' );
			if ( empty( $session_href ) ) {
				throw new Exception( 'Payment session is not set up.' );
			}

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				throw new Exception( 'You must be logged in to add a payment method.' );
			}

			$payment_instrument = $this->create_payment_instrument_object(
				$session_href,
				wc_clean( sanitize_text_field( $_POST['card_holder_name'] ) )
			);

			$api                   = $this->initialize_api();
			$transaction_reference = Helper::generateString( 12 );
			$api_response          = $api->initiatePayment( 0 )
										->withCurrency( get_woocommerce_currency() )
										->withTransactionReference( $transaction_reference )
										->withPaymentInstrument( $payment_instrument )
										->withTokenCreation()
										->withTokenNamespace( WC_Worldpay_Payment_Token::get_customer_tokens_namespace( $user_id, $this->id ) )
										->withCustomerAgreement( CustomerAgreementType::CARD_ON_FILE, StoredCardUsage::FIRST )
										->execute();

			if ( ! $api_response->isSuccessful() ) {
				throw new Exception( 'Unable to add payment method to your account.' );
			}

			$this->store_payment_token_from_response( $user_id, $api_response->jsonDecode() );

			return array(
				'result'   => 'success',
				'redirect' => wc_get_endpoint_url( 'payment-methods', '', wc_get_page_permalink( 'myaccount' ) ),
			);

		} catch ( Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->info( $api_response->getResponseMetadata( 'checkoutAddPaymentMethod', $e->getMessage() ) );
			}

			return array(
				'result'   => 'failure',
				'messages' => $e->getMessage(),
			);
		}
	}

	/**
	 * @param $order_total
	 * @param $order
	 *
	 * @return void
	 */
	public function scheduled_subscription_payment( $renewal_order_total, $renewal_order ) {
		$subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );
		foreach ( $subscriptions as $subscription ) {
			try {
				$payment_tokens   = $subscription->get_payment_tokens();
				$wc_token_id      = array_pop( $payment_tokens );
				$scheme_reference = $subscription->get_meta( 'worldpay_scheme_reference' );

				$payment_processing_service = new WC_Worldpay_Payment_Processing( $this, $renewal_order, $wc_token_id );
				$api_response               = $payment_processing_service->process_subscription_payment( $scheme_reference );
				if ( ! $api_response->isSuccessful() ) {
					throw new Exception( 'Process subscription payment failed.' );
				}

				$api_decoded_response = $api_response->jsonDecode();
				switch ( $api_decoded_response->outcome ) {
					case PaymentStatus::AUTHORIZED:
					case PaymentStatus::SENT_FOR_SETTLEMENT:
						$this->payment_completed( $renewal_order, $payment_processing_service, $api_decoded_response );
						WC_Subscriptions_Manager::process_subscription_payments_on_order( $renewal_order );
						break;
					case PaymentStatus::REFUSED:
					case PaymentStatus::FRAUD_HIGH_RISK:
					case PaymentStatus::SENT_FOR_CANCELLATION:
						$order_note = sprintf(
							'%s%s. Payment failed via Worldpay. Transaction reference: %s',
							$renewal_order->get_total(),
							get_woocommerce_currency_symbol( $renewal_order->get_currency() ),
							$api_decoded_response->transactionReference
						);
						$renewal_order->add_order_note( $order_note );
						WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $subscription->get_parent() );
						break;
					default:
						throw new \Exception( 'Renewal orderID: ' . $renewal_order->get_id() . '. Unexpected subscription payment outcome received: ' . $api_decoded_response->outcome );
				}
			} catch ( \Exception $e ) {
				wc_get_logger()->info( $e->getMessage() );
				if ( isset( $api_response ) ) {
					wc_get_logger()->alert( $api_response->getResponseMetadata( 'processSubscriptionPayment' ) );
				}
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $subscription->get_parent() );
			}
		}
	}

	/**
	 * When the login cookies are set, they are not available until the next page reload. For DDC, specifically
	 *  for returning updated nonces, we need this to be available immediately.
	 *
	 * Only apply during the checkout process with the account creation.
	 *
	 * @param  string $cookie  New cookie value.
	 */
	public function set_cookie_on_current_request( $cookie ) {
		if ( $this->id !== WC()->session->get( 'chosen_payment_method' ) ) {
			return;
		}
		if ( ! defined( 'LOGGED_IN_COOKIE' ) ) {
			return;
		}
		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT && did_action( 'woocommerce_created_customer' ) > 0 ) {
			$_COOKIE[ LOGGED_IN_COOKIE ] = $cookie;
		}
	}
}

/**
 * Add Worldpay Checkout to payment options for WooCommerce.
 *
 * @param $methods
 *
 * @return mixed
 */
function add_worldpay_gateway_checkout( $methods ) {
	$methods[] = 'WC_Payment_Gateway_Access_Worldpay_Checkout';

	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_worldpay_gateway_checkout' );
