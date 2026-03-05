<?php

namespace Worldpay\Api;

use Worldpay\Api\Builders\PaymentManagement\PaymentManagementBuilder;
use Worldpay\Api\Builders\PaymentProcessing\PaymentProcessingBuilder;
use Worldpay\Api\Builders\PaymentProcessing\ThreeDSChallengeBuilder;
use Worldpay\Api\Builders\PaymentProcessing\ThreeDSDeviceDataBuilder;
use Worldpay\Api\Builders\PaymentQuery\PaymentQueryBuilder;
use Worldpay\Api\Builders\PaymentSession\PaymentSessionBuilder;
use Worldpay\Api\Builders\Tokens\PaymentTokenBuilder;
use Worldpay\Api\Enums\Environment;
use Worldpay\Api\Enums\PaymentOperation;
use Worldpay\Api\Exceptions\AuthenticationException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;

class AccessWorldpay implements ApiInterface
{
	/**
	 * Access Worldpay TRY API URL
	 */
	public const TRY_ACCESS_WORLDPAY_URL = 'https://try.access.worldpay.com';

	/**
	 * Access Worldpay LIVE API URL
	 */
	public const LIVE_ACCESS_WORLDPAY_URL = 'https://access.worldpay.com';

	/**
	 * Access Worldpay Checkout SDK URL.
	 */
	public const CHECKOUT_SDK_URL = '/access-checkout/v2/checkout.js';

	/**
	 * Third party application url used for 3DS authentication for TRY
	 */
	public const TRY_CARDINAL_URL = 'https://centinelapistag.cardinalcommerce.com';

	/**
	 * Third party application url used for 3DS authentication for LIVE
	 */
	public const LIVE_CARDINAL_URL = 'https://centinelapi.cardinalcommerce.com';

	/**
	 * Card Payments v7 headers
	 */
	public const HEADER_PAYMENTS_EVENTS_CONTENT_TYPE = 'application/vnd.worldpay.payments-v7+json';
	public const HEADER_PAYMENTS_EVENTS_ACCEPT = 'application/vnd.worldpay.payments-v7+json';

	/**
	 * @var AccessWorldpayConfigProvider
	 */
	protected AccessWorldpayConfigProvider $apiConfigProvider;

	/**
	 * @param $method
	 * @param $arguments
	 * @return mixed
	 */
	public static function __callStatic($method, $arguments)
	{
		return (new static)->$method(...$arguments);
	}

	/**
	 * @param  AccessWorldpayConfigProvider  $apiConfigProvider
	 *
	 * @return AccessWorldpay
	 * @throws AuthenticationException
	 */
	protected function config(AccessWorldpayConfigProvider $apiConfigProvider): AccessWorldpay {
		$this->apiConfigProvider = $apiConfigProvider;
		if (empty($apiConfigProvider->username) ||
			empty($apiConfigProvider->password)) {
			throw new AuthenticationException('Invalid authentication credentials.');
		}

		return $this;
	}

	/**
	 * @param  int  $amount
	 *
	 * @return PaymentProcessingBuilder
	 */
	public function initiatePayment(int $amount): PaymentProcessingBuilder {
		return (new PaymentProcessingBuilder())->withAmount($amount);
	}

	/**
	 * @param  string  $checkout
	 *
	 * @return PaymentSessionBuilder
	 */
	public function createPaymentSession(string $checkout): PaymentSessionBuilder {
		return (new PaymentSessionBuilder())->withIdentity($checkout);
	}

	/**
	 * @return PaymentTokenBuilder
	 */
	public function paymentToken(): PaymentTokenBuilder {
		return (new PaymentTokenBuilder());
	}

	/**
	 * @return PaymentQueryBuilder
	 */
	public function queryPayments(): PaymentQueryBuilder {
		return (new PaymentQueryBuilder());
	}

	/**
	 * @return PaymentManagementBuilder
	 */
	public function refund(): PaymentManagementBuilder {
		return (new PaymentManagementBuilder(PaymentOperation::REFUND));
	}

	/**
	 * @param  int  $amount
	 * @return PaymentManagementBuilder
	 */
	public function partialRefund(int $amount): PaymentManagementBuilder {
		return (new PaymentManagementBuilder(PaymentOperation::PARTIAL_REFUND))
			->withAmount($amount);
	}

	/**
	 * @return PaymentManagementBuilder
	 */
	public function settle(): PaymentManagementBuilder {
		return (new PaymentManagementBuilder(PaymentOperation::SETTLE));
	}

	/**
	 * @param  int  $amount
	 * @return PaymentManagementBuilder
	 */
	public function partialSettle(int $amount): PaymentManagementBuilder {
		return (new PaymentManagementBuilder(PaymentOperation::PARTIAL_SETTLE))
			->withAmount($amount);
	}

	/**
	 * @return PaymentManagementBuilder
	 */
	public function cancel(): PaymentManagementBuilder {
		return (new PaymentManagementBuilder(PaymentOperation::CANCEL));
	}

	/**
	 * @return ThreeDSDeviceDataBuilder
	 */
	public function provide3DSDeviceData(): ThreeDSDeviceDataBuilder {
		return (new ThreeDSDeviceDataBuilder());
	}

	/**
	 * @return ThreeDSChallengeBuilder
	 */
	public function challenge3DSResult(): ThreeDSChallengeBuilder {
		return (new ThreeDSChallengeBuilder());
	}

	/**
	 * @param  string  $environment
	 * @return string
	 */
	public static function rootResource(string $environment): string {
		return $environment === Environment::LIVE_MODE ? self::LIVE_ACCESS_WORLDPAY_URL : self::TRY_ACCESS_WORLDPAY_URL;
	}

	/**
	 * @param  string  $environment
	 * @return string
	 */
	public static function checkoutSdkUrl(string $environment): string {
		return self::rootResource($environment) . self::CHECKOUT_SDK_URL;
	}

	/**
	 * @return string
	 */
	public static function paymentsEventsContentTypeHeader(): string {
		return self::HEADER_PAYMENTS_EVENTS_CONTENT_TYPE;
	}

	/**
	 * @return string
	 */
	public static function paymentsEventsAcceptHeader(): string {
		return self::HEADER_PAYMENTS_EVENTS_ACCEPT;
	}
}
