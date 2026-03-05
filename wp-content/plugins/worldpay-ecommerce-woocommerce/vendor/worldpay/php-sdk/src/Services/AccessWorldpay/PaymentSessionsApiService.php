<?php

namespace Worldpay\Api\Services\AccessWorldpay;

use Worldpay\Api\AccessWorldpay;
use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\PaymentSession\Payload\PaymentSessionPayloadBuilder;
use Worldpay\Api\Builders\PaymentSession\PaymentSessionBuilder;
use Worldpay\Api\Builders\RequestBuilder;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\PaymentSessionServiceInterface;

class PaymentSessionsApiService
	extends AccessWorldpay
	implements PaymentSessionServiceInterface
{

	/**
	 * Endpoint to submit a payment request.
	 */
	public const PAYMENT_SESSIONS_ENDPOINT = '/sessions/card';

	/**
	 * PAYMENT_SESSIONS_ACCEPT_HEADER
	 */
	public const PAYMENT_SESSIONS_ACCEPT_HEADER = 'application/vnd.worldpay.sessions-v1.hal+json';

	/**
	 * PAYMENT_SESSIONS_CONTENT_TYPE
	 */
	public const PAYMENT_SESSIONS_CONTENT_TYPE_HEADER = 'application/vnd.worldpay.sessions-v1.hal+json';

	/**
	 * @param  PaymentSessionBuilder  $paymentSessionBuilder
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function setupPaymentSession(PaymentSessionBuilder $paymentSessionBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		$payloadBuilder = new PaymentSessionPayloadBuilder($paymentSessionBuilder);

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withContentTypeHeader(self::PAYMENT_SESSIONS_CONTENT_TYPE_HEADER)
		                     ->withAcceptHeader(self::PAYMENT_SESSIONS_ACCEPT_HEADER)
		                     ->withBody($payloadBuilder->createPayload())
		                     ->post(self::setupPaymentSessionUrl($apiConfigProvider->environment));
	}

	/**
	 * @param  string  $environment
	 *
	 * @return string
	 */
	public static function setupPaymentSessionUrl(string $environment): string {
		return parent::rootResource($environment) . self::PAYMENT_SESSIONS_ENDPOINT;
	}
}