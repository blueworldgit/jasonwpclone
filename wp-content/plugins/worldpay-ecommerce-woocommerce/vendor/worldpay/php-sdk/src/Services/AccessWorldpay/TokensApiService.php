<?php

namespace Worldpay\Api\Services\AccessWorldpay;

use Worldpay\Api\AccessWorldpay;
use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\RequestBuilder;
use Worldpay\Api\Builders\Tokens\Payload\PaymentTokenPayloadBuilder;
use Worldpay\Api\Builders\Tokens\PaymentTokenBuilder;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\PaymentTokenServiceInterface;

class TokensApiService extends AccessWorldpay implements PaymentTokenServiceInterface {
	/**
	 * Endpoint to create a new token.
	 */
	public const TOKENS_CREATE_ENDPOINT = '/tokens';

	/**
	 * Endpoint to update token.
	 */
	public const TOKENS_UPDATE_ENDPOINT = '/tokens/{tokenId}';

	/**
	 * Endpoint to delete token.
	 */
	public const TOKENS_DELETE_ENDPOINT = '/tokens/{tokenId}';

	/**
	 * Endpoint to retrieve token details.
	 */
	public const TOKENS_QUERY_ENDPOINT = '/tokens/{tokenId}';

	/**
	 * Accept Header.
	 */
	public const TOKENS_ACCEPT_HEADER = 'application/vnd.worldpay.tokens-v3.hal+json';

	/**
	 * Content-Type Header.
	 */
	public const TOKENS_CONTENT_TYPE_HEADER = 'application/vnd.worldpay.tokens-v3.hal+json';

	/**
	 * @param  PaymentTokenBuilder  $paymentTokenBuilder
	 *
	 * @return ApiResponse
	 * @throws \Worldpay\Api\Exceptions\InvalidArgumentException
	 */
	public static function createPaymentToken(PaymentTokenBuilder $paymentTokenBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		$payloadBuilder = new PaymentTokenPayloadBuilder($paymentTokenBuilder);

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withContentTypeHeader(self::TOKENS_CONTENT_TYPE_HEADER)
		                     ->withAcceptHeader(self::TOKENS_ACCEPT_HEADER)
		                     ->withBody($payloadBuilder->createTokenPayload())
		                     ->post(self::createPaymentTokenUrl($apiConfigProvider->environment));
	}

	/**
	 * @param  string  $environment
	 *
	 * @return string
	 */
	public static function createPaymentTokenUrl(string $environment): string {
		return parent::rootResource($environment) . self::TOKENS_CREATE_ENDPOINT;
	}

	/**
	 * @param  PaymentTokenBuilder  $paymentTokenBuilder
	 *
	 * @return ApiResponse
	 */
	public static function updatePaymentToken(PaymentTokenBuilder $paymentTokenBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		$payloadBuilder = new PaymentTokenPayloadBuilder($paymentTokenBuilder);

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withContentTypeHeader(self::TOKENS_CONTENT_TYPE_HEADER)
		                     ->withAcceptHeader(self::TOKENS_ACCEPT_HEADER)
		                     ->withBody($payloadBuilder->updateTokenPayload())
		                     ->put(self::updatePaymentTokenUrl($apiConfigProvider->environment, $paymentTokenBuilder->tokenId));
	}

	/**
	 * @param  string  $environment
	 * @param  string  $tokenIdOrHref
	 *
	 * @return string
	 */
	public static function updatePaymentTokenUrl(string $environment, string $tokenIdOrHref): string {
		if (strpos($tokenIdOrHref, 'https://') !== false ) {
			return $tokenIdOrHref;
		}

		return parent::rootResource($environment) . str_replace('{tokenId}', $tokenIdOrHref, self::TOKENS_UPDATE_ENDPOINT);
	}

	/**
	 * @param  PaymentTokenBuilder  $paymentTokenBuilder
	 *
	 * @return ApiResponse
	 */
	public static function deletePaymentToken(PaymentTokenBuilder $paymentTokenBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withContentTypeHeader(self::TOKENS_CONTENT_TYPE_HEADER)
		                     ->withAcceptHeader(self::TOKENS_ACCEPT_HEADER)
		                     ->delete(self::deletePaymentTokenUrl($apiConfigProvider->environment, $paymentTokenBuilder->tokenId));
	}

	/**
	 * @param  string  $environment
	 * @param  string  $tokenIdOrHref
	 *
	 * @return string
	 */
	public static function deletePaymentTokenUrl(string $environment, string $tokenIdOrHref): string {
		if (strpos($tokenIdOrHref, 'https://') !== false ) {
			return $tokenIdOrHref;
		}

		return parent::rootResource($environment) . str_replace('{tokenId}', $tokenIdOrHref, self::TOKENS_DELETE_ENDPOINT);
	}

	/**
	 * @param  PaymentTokenBuilder  $paymentTokenBuilder
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function queryPaymentToken(PaymentTokenBuilder $paymentTokenBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withContentTypeHeader(self::TOKENS_CONTENT_TYPE_HEADER)
		                     ->withAcceptHeader(self::TOKENS_ACCEPT_HEADER)
		                     ->withParams($paymentTokenBuilder->getQueryStringParams())
		                     ->get(self::queryPaymentTokenUrl($apiConfigProvider->environment, $paymentTokenBuilder->tokenId ?? ''));
	}

	/**
	 * @param  string  $environment
	 * @param  string  $tokenIdOrHref
	 *
	 * @return string
	 */
	public static function queryPaymentTokenUrl(string $environment, string $tokenIdOrHref): string {
		if (strpos($tokenIdOrHref, 'https://') !== false ) {
			return $tokenIdOrHref;
		}

		if (empty($tokenIdOrHref)) {
			return parent::rootResource($environment) . str_replace('/{tokenId}', '', self::TOKENS_QUERY_ENDPOINT);
		}

		return parent::rootResource($environment) . str_replace('{tokenId}', $tokenIdOrHref, self::TOKENS_QUERY_ENDPOINT);
	}
}
