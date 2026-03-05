<?php

namespace Worldpay\Api\Services\AccessWorldpay;

use Worldpay\Api\AccessWorldpay;
use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\PaymentQuery\PaymentQueryBuilder;
use Worldpay\Api\Builders\RequestBuilder;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\PaymentQueryServiceInterface;

class PaymentQueriesApiService
	extends AccessWorldpay
	implements PaymentQueryServiceInterface
{
	/**
	 * Endpoint to query payments with filters.
	 */
	public const QUERY_PAYMENT_ENDPOINT = '/paymentQueries/payments';

	/**
	 * Endpoint to retrieve a payment by payment id.
	 */
	public const QUERY_PAYMENT_BY_ID_ENDPOINT = '/paymentQueries/payments/{paymentId}';

	/**
	 * Endpoint to query historical payments.
	 */
	public const QUERY_ARCHIVED_PAYMENTS_ENDPOINT = '/paymentQueries/archivedPayments';

	/**
	 * Accept Header used for payment queries requests.
	 */
	public const PAYMENT_QUERIES_ACCEPT_HEADER = 'application/vnd.worldpay.payment-queries-v1.hal+json';

	/**
	 * Content Type Header used for payment queries requests.
	 */
	public const PAYMENT_QUERIES_CONTENT_TYPE_HEADER = 'application/vnd.worldpay.payment-queries-v1.hal+json';

	/**
	 * @param  PaymentQueryBuilder  $paymentQueryBuilder
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function queryPayment(PaymentQueryBuilder $paymentQueryBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withAcceptHeader(self::PAYMENT_QUERIES_ACCEPT_HEADER)
							 ->withContentTypeHeader(self::PAYMENT_QUERIES_CONTENT_TYPE_HEADER)
		                     ->withParams($paymentQueryBuilder->getQueryStringParams())
		                     ->get(self::queryPaymentUrl($apiConfigProvider->environment));
	}

	/**
	 * @param  string  $environment
	 * @return string
	 */
	public static function queryPaymentUrl(string $environment): string {
		return parent::rootResource($environment) . self::QUERY_PAYMENT_ENDPOINT;
	}

	/**
	 * @param  PaymentQueryBuilder  $paymentQueryBuilder
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function queryPaymentByPaymentId(PaymentQueryBuilder $paymentQueryBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withAcceptHeader(self::PAYMENT_QUERIES_ACCEPT_HEADER)
							 ->withContentTypeHeader(self::PAYMENT_QUERIES_CONTENT_TYPE_HEADER)
		                     ->get(self::queryPaymentByPaymentIdUrl($apiConfigProvider->environment, $paymentQueryBuilder->paymentId));
	}

	/**
	 * @param  string  $environment
	 * @param  string  $paymentId
	 * @return string
	 */
	public static function queryPaymentByPaymentIdUrl(string $environment, string $paymentId): string {
		return parent::rootResource($environment) . str_replace('{paymentId}', $paymentId, self::QUERY_PAYMENT_BY_ID_ENDPOINT);
	}
}
