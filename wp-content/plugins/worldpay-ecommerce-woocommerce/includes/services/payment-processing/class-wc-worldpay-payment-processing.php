<?php

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Enums\ChallengePreference;
use Worldpay\Api\Enums\CustomerAgreementType;
use Worldpay\Api\Enums\FraudType;
use Worldpay\Api\Enums\PaymentInstrumentType;
use Worldpay\Api\Enums\RiskFactors;
use Worldpay\Api\Enums\Status;
use Worldpay\Api\Enums\StoredCardUsage;
use Worldpay\Api\Enums\TokenOption;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Exceptions\AuthenticationException;
use Worldpay\Api\Exceptions\InvalidArgumentException;
use Worldpay\Api\Utils\AmountHelper;
use Worldpay\Api\Utils\Helper;
use Worldpay\Api\ValueObjects\PaymentMethods\CreditCard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Worldpay_Payment_Processing {
	protected $api;
	protected $payment_gateway;
	public $transaction_reference;
	public $wc_order;
	public $wc_order_amount;
	public $wc_order_converted_amount;
	public $wc_order_currency;
	public $wc_token_id;

	/**
	 * @param  WC_Worldpay_Payment_Method $payment_gateway
	 * @param  WC_Order                   $wc_order
	 * @param  int|null                   $wc_token_id
	 *
	 * @throws AuthenticationException
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		WC_Worldpay_Payment_Method $payment_gateway,
		WC_Order $wc_order,
		?int $wc_token_id = null
	) {
		$this->payment_gateway = $payment_gateway;
		$this->api             = $this->payment_gateway->initialize_api();

		$this->wc_order                  = $wc_order;
		$this->wc_order_amount           = $this->wc_order->get_total();
		$this->wc_order_currency         = $this->wc_order->get_currency();
		$this->wc_order_converted_amount = AmountHelper::decimalToExponentDelimiter(
			$this->wc_order_amount,
			$this->wc_order_currency,
			get_locale()
		);
		$this->transaction_reference     = Helper::generateString( 12 );
		if ( ! empty( $wc_token_id ) ) {
			$this->wc_token_id = $wc_token_id;
		}
	}

	/**
	 * @param $success_guid
	 * @param $failure_guid
	 *
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function process_offsite_payment( $success_guid, $failure_guid ) {
		$is_stored_payment_method = $this->payment_gateway->is_saved_payment_method();
		$should_store_card        = $this->payment_gateway->should_save_payment_method();

		$results_urls = $this->payment_gateway->get_result_urls( $this->wc_order, $success_guid, $failure_guid );

		$api_request = $this->api->initiatePayment( $this->wc_order_converted_amount )
								 ->withCurrency( $this->wc_order_currency )
								 ->withTransactionReference( $this->transaction_reference )
								 ->withOptionalOrder( $this->payment_gateway->get_order_data( $this->wc_order ) )
								 ->withResultURLs( $results_urls );

		if ( ! empty( $this->payment_gateway->get_merchant_description() ) ) {
			$api_request = $api_request->withDescription( $this->payment_gateway->get_merchant_description() );
		}

		if ( $is_stored_payment_method ) {
			$wc_token = WC_Payment_Tokens::get( $this->wc_token_id );
			if ( ! isset( $wc_token ) ) {
				throw new \Exception( 'Unable to retrieve token by id ' . $this->wc_token_id );
			}
			if ( $wc_token->get_user_id() !== get_current_user_id() ) {
				throw new \Exception( 'Invalid token id ' . $this->wc_token_id . ' for current user id ' . get_current_user_id() );
			}

			$payment_instrument            = new CreditCard( PaymentInstrumentType::TOKENS_CARD_TOKENIZED );
			$payment_instrument->tokenHref = $wc_token->get_token();

			$api_request = $api_request->withPaymentInstrument( $payment_instrument )
									   ->withCustomerAgreement( CustomerAgreementType::CARD_ON_FILE, StoredCardUsage::SUBSEQUENT );
		} elseif ( $should_store_card ) {
			$api_request = $api_request->withTokenCreation()
									   ->withTokenOption( TokenOption::NOTIFY )
									->withTokenNamespace(
										WC_Worldpay_Payment_Token::get_customer_tokens_namespace(
											$this->wc_order->get_user_id(),
											$this->payment_gateway->id
										)
									)
									   ->withCustomerAgreement( CustomerAgreementType::CARD_ON_FILE, StoredCardUsage::FIRST );
		}

		return $api_request->execute();
	}

	/**
	 * @param $sessionHref
	 * @param $card_holder_name
	 *
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function process_onsite_payment( $sessionHref, $card_holder_name ) {
		$order_contains_subscription = $this->payment_gateway->order_contains_subscription( $this->wc_order );
		$is_stored_payment_method    = $this->payment_gateway->is_saved_payment_method();
		$should_store_card           = $this->payment_gateway->should_save_payment_method();

		$api_request = $this->api->initiatePayment( $this->wc_order_converted_amount )
								 ->withCurrency( $this->wc_order_currency )
								 ->withTransactionReference( $this->transaction_reference )
								 ->withOptionalOrder( $this->payment_gateway->get_order_data( $this->wc_order ) )
								 ->withFraudType( FraudType::FRAUD_SIGHT );

		if ( $this->wc_order_converted_amount > 0 ) {
			$api_request = $api_request->withAutoSettlement()
									   ->withSettlementCancellationOn( RiskFactors::AVS_NOT_MATCHED, Status::DISABLED );

		}

		$three_ds = $this->payment_gateway->create_three_ds_object();

		// Is the order paid with a plain card or with a stored token?
		if ( $is_stored_payment_method ) {
			$wc_token = WC_Payment_Tokens::get( $this->wc_token_id );
			if ( ! isset( $wc_token ) ) {
				throw new \Exception( 'Unable to retrieve token by id ' . $this->wc_token_id );
			}
			if ( $wc_token->get_user_id() !== get_current_user_id() ) {
				throw new \Exception( 'Invalid token id ' . $this->wc_token_id . ' for current user id ' . get_current_user_id() );
			}

			$payment_instrument                 = new CreditCard( PaymentInstrumentType::TOKEN );
			$payment_instrument->tokenHref      = $wc_token->get_token();
			$payment_instrument->cvcSessionHref = $sessionHref;

			$api_request = $api_request->withPaymentInstrument( $payment_instrument );

			$customer_agreement_type = $order_contains_subscription ? CustomerAgreementType::SUBSCRIPTION : CustomerAgreementType::CARD_ON_FILE;
			$stored_card_usage       = $order_contains_subscription ? StoredCardUsage::FIRST : StoredCardUsage::SUBSEQUENT;
			$api_request             = $api_request->withCustomerAgreement(
				$customer_agreement_type,
				$stored_card_usage
			);

			if ( $order_contains_subscription ) {
				$three_ds->challengePreference = ChallengePreference::CHALLENGE_MANDATED;
			}
		} else {
			$payment_instrument                 = new CreditCard( PaymentInstrumentType::CHECKOUT );
			$payment_instrument->sessionHref    = $sessionHref;
			$payment_instrument->cardHolderName = $card_holder_name;

			$api_request = $api_request->withPaymentInstrument( $payment_instrument );

			if ( $should_store_card || $order_contains_subscription ) {
				$api_request = $api_request->withTokenCreation()
										->withTokenNamespace(
											WC_Worldpay_Payment_Token::get_customer_tokens_namespace(
												$this->wc_order->get_user_id(),
												$this->payment_gateway->id
											)
										);

				$customer_agreement_type = $order_contains_subscription ? CustomerAgreementType::SUBSCRIPTION : CustomerAgreementType::CARD_ON_FILE;
				$api_request             = $api_request->withCustomerAgreement(
					$customer_agreement_type,
					StoredCardUsage::FIRST
				);

				$three_ds->challengePreference = ChallengePreference::CHALLENGE_MANDATED;
			}
		}
		$api_request->withThreeDS( $three_ds );

		return $api_request->execute();
	}

	/**
	 * @param  string $scheme_reference
	 *
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function process_subscription_payment( string $scheme_reference ) {
		$wc_token = WC_Payment_Tokens::get( $this->wc_token_id );
		if ( ! isset( $wc_token ) ) {
			throw new \Exception( 'Unable to retrieve token by id ' . $this->wc_token_id );
		}

		$payment_instrument            = new CreditCard( PaymentInstrumentType::TOKEN );
		$payment_instrument->tokenHref = $wc_token->get_token();

		return $this->api->initiatePayment( $this->wc_order_converted_amount )
						 ->withCurrency( $this->wc_order_currency )
						 ->withTransactionReference( $this->transaction_reference )
						 ->withOptionalOrder( $this->payment_gateway->get_order_data( $this->wc_order ) )
						 ->withFraudType( FraudType::FRAUD_SIGHT )
						 ->withAutoSettlement()
						 ->withSettlementCancellationOn( RiskFactors::AVS_NOT_MATCHED, Status::DISABLED )
						 ->withPaymentInstrument( $payment_instrument )
						 ->withCustomerAgreement( CustomerAgreementType::SUBSCRIPTION, StoredCardUsage::SUBSEQUENT )
						 ->withSchemeReference( $scheme_reference )
						 ->execute();

	}

	public function process_onsite_payment_method_change( $sessionHref, $card_holder_name ) {
		$is_stored_payment_method = $this->payment_gateway->is_saved_payment_method();
		$api_request              = $this->api->initiatePayment( $this->wc_order_converted_amount )
											  ->withCurrency( $this->wc_order_currency )
											  ->withTransactionReference( $this->transaction_reference )
											  ->withOptionalOrder( $this->payment_gateway->get_order_data( $this->wc_order ) )
											  ->withFraudType( FraudType::FRAUD_SIGHT )
											  ->withCustomerAgreement( CustomerAgreementType::SUBSCRIPTION, StoredCardUsage::FIRST );

		$three_ds                      = $this->payment_gateway->create_three_ds_object();
		$three_ds->challengePreference = ChallengePreference::CHALLENGE_MANDATED;
		$api_request                   = $api_request->withThreeDS( $three_ds );

		// Is the order paid with a plain card or with a stored token?
		if ( $is_stored_payment_method ) {
			$wc_token = WC_Payment_Tokens::get( $this->wc_token_id );
			if ( ! isset( $wc_token ) ) {
				throw new \Exception( 'Unable to retrieve token by id ' . $this->wc_token_id );
			}
			if ( $wc_token->get_user_id() !== get_current_user_id() ) {
				throw new \Exception( 'Invalid token id ' . $this->wc_token_id . ' for current user id ' . get_current_user_id() );
			}

			$payment_instrument                 = new CreditCard( PaymentInstrumentType::TOKEN );
			$payment_instrument->tokenHref      = $wc_token->get_token();
			$payment_instrument->cvcSessionHref = $sessionHref;

			$api_request = $api_request->withPaymentInstrument( $payment_instrument );
		} else {
			$payment_instrument                 = new CreditCard( PaymentInstrumentType::CHECKOUT );
			$payment_instrument->sessionHref    = $sessionHref;
			$payment_instrument->cardHolderName = $card_holder_name;

			$api_request = $api_request->withPaymentInstrument( $payment_instrument );

			$api_request = $api_request->withTokenCreation()
									->withTokenNamespace(
										WC_Worldpay_Payment_Token::get_customer_tokens_namespace(
											$this->wc_order->get_user_id(),
											$this->payment_gateway->id
										)
									);
		}

		return $api_request->execute();
	}
}
