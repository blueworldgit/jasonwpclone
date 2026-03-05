<?php

namespace Worldpay\Api\Services\AccessWorldpay;

use Worldpay\Api\AccessWorldpay;
use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\PaymentManagement\PaymentManagementBuilder;
use Worldpay\Api\Builders\PaymentProcessing\Payload\PaymentsApiPayloadBuilder;
use Worldpay\Api\Builders\PaymentProcessing\PaymentProcessingBuilder;
use Worldpay\Api\Builders\PaymentProcessing\ThreeDSChallengeBuilder;
use Worldpay\Api\Builders\PaymentProcessing\ThreeDSDeviceDataBuilder;
use Worldpay\Api\Builders\PaymentQuery\PaymentQueryBuilder;
use Worldpay\Api\Builders\RequestBuilder;
use Worldpay\Api\Enums\PaymentOperation;
use Worldpay\Api\Exceptions\ApiException;
use Worldpay\Api\Exceptions\InvalidArgumentException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\PaymentManagementServiceInterface;
use Worldpay\Api\Services\PaymentProcessingServiceInterface;
use Worldpay\Api\Services\PaymentQueryServiceInterface;

class PaymentsApiService
	extends AccessWorldpay
	implements PaymentProcessingServiceInterface,
	PaymentQueryServiceInterface,
	PaymentManagementServiceInterface
{
	/**
	 * Endpoint to submit a payment request.
	 */
	public const PAYMENTS_ENDPOINT = '/api/payments';

	/**
	 * Endpoint to supply 3DS data.
	 */
	public const THREEDS_DEVICE_DATA_ENDPOINT = '/api/payments/{linkData}/3dsDeviceData';

	/**
	 * Endpoint to verify 3DS challenge result.
	 */
	public const THREEDS_CHALLENGE_ENDPOINT = '/api/payments/{linkData}/3dsChallenges';

	/**
	 * Endpoint to submit a payment refund.
	 */
	public const REFUND_ENDPOINT = '/api/payments/{linkData}/refunds';

	/**
	 * Endpoint to submit a partial payment refund.
	 */
	public const PARTIAL_REFUND_ENDPOINT = '/api/payments/{linkData}/partialRefunds';

	/**
	 * Endpoint to submit a payment settle.
	 */
	public const SETTLEMENTS_ENDPOINT = '/api/payments/{linkData}/settlements';

	/**
	 * Endpoint to submit a partial payment settle.
	 */
	public const PARTIAL_SETTLEMENTS_ENDPOINT = '/api/payments/{linkData}/partialSettlements';

	/**
	 * Endpoint to submit a payment cancel.
	 */
	public const CANCELLATIONS_ENDPOINT = '/api/payments/{linkData}/cancellations';

	/**
	 * Endpoint to query a payment.
	 */
	public const PAYMENT_QUERY_ENDPOINT = '/api/payments/{linkData}';

	/**
	 * @var array
	 */
	public static array $supports = [
		PaymentOperation::REFUND,
		PaymentOperation::PARTIAL_REFUND,
		PaymentOperation::SETTLE,
		PaymentOperation::PARTIAL_SETTLE,
		PaymentOperation::CANCEL,
	];

	/*
	 * --------------------------------------
	 * Payment Processing
	 * --------------------------------------
	 */
	/**
	 * @param  PaymentProcessingBuilder  $paymentProcessingBuilder
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function submitPayment(PaymentProcessingBuilder $paymentProcessingBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		$payloadBuilder = new PaymentsApiPayloadBuilder($paymentProcessingBuilder);

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
							->withContentTypeHeader()
							->withWpApiVersionHeader($apiConfigProvider->apiVersion)
							->withBody($payloadBuilder->createPayload())
							->post(self::submitPaymentUrl($apiConfigProvider->environment));
	}

	/**
	 * @param  string  $environment
	 * @return string
	 */
	public static function submitPaymentUrl(string $environment): string {
		return parent::rootResource($environment) . self::PAYMENTS_ENDPOINT;
	}

	/**
	 * @param  ThreeDSDeviceDataBuilder  $threeDSDeviceDataBuilder
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function supply3DSDeviceData(ThreeDSDeviceDataBuilder $threeDSDeviceDataBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();
		if (strpos($threeDSDeviceDataBuilder->linkData, 'https://') !== false ) {
			$endpoint = $threeDSDeviceDataBuilder->linkData;
		} else {
			$endpoint = self::supply3DSDeviceDataUrl($apiConfigProvider->environment, $threeDSDeviceDataBuilder->linkData);
		}
		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
							->withContentTypeHeader()
							->withWpApiVersionHeader($apiConfigProvider->apiVersion)
							->withBody($threeDSDeviceDataBuilder->createPayload())
							->post($endpoint);
	}

	/**
	 * @param  string  $environment
	 * @param  string  $linkData
	 *
	 * @return string
	 */
	public static function supply3DSDeviceDataUrl(string $environment, string $linkData): string {
		return parent::rootResource($environment) . str_replace('{linkData}', $linkData, self::THREEDS_DEVICE_DATA_ENDPOINT);
	}

	/**
	 * @param  ThreeDSChallengeBuilder  $threeDSChallengeBuilder
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function verify3DSChallenge(ThreeDSChallengeBuilder $threeDSChallengeBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();
		if (strpos($threeDSChallengeBuilder->linkData, 'https://') !== false ) {
			$endpoint = $threeDSChallengeBuilder->linkData;
		} else {
			$endpoint = self::verify3DSChallengeUrl($apiConfigProvider->environment, $threeDSChallengeBuilder->linkData);
		}

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withContentTypeHeader()
		                     ->withWpApiVersionHeader($apiConfigProvider->apiVersion)
		                     ->post($endpoint);
	}

	/**
	 * @param  string  $environment
	 * @param  string  $linkData
	 *
	 * @return string
	 */
	public static function verify3DSChallengeUrl(string $environment, string $linkData): string {
		return parent::rootResource($environment) . str_replace('{linkData}', $linkData, self::THREEDS_CHALLENGE_ENDPOINT);
	}

	/*
	 * --------------------------------------
	 * Payment Management
	 * --------------------------------------
	 */
	/**
	 * @param  string  $operationType
	 *
	 * @return bool
	 */
	public static function supports(string $operationType): bool {
		return in_array($operationType, self::$supports);
	}

	/**
	 * @param  PaymentManagementBuilder  $paymentManagementBuilder
	 *
	 * @return ApiResponse
	 * @throws ApiException
	 * @throws InvalidArgumentException
	 */
	public static function managePayment(PaymentManagementBuilder $paymentManagementBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();
		if (strpos($paymentManagementBuilder->linkData, 'https://') !== false ) {
			$endpoint = $paymentManagementBuilder->linkData;
		} else {
			$endpoint = self::managePaymentUrl($paymentManagementBuilder->paymentOperation, $apiConfigProvider->environment, $paymentManagementBuilder->linkData);
		}

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withWpApiVersionHeader($apiConfigProvider->apiVersion)
		                     ->withContentTypeHeader()
		                     ->withBody($paymentManagementBuilder->createPayload())
		                     ->post($endpoint);
	}

	/**
	 * @param  string  $paymentOperation
	 * @param  string  $environment
	 * @param  string  $linkData
	 *
	 * @return string|null
	 * @throws ApiException
	 */
	private static function managePaymentUrl(string $paymentOperation, string $environment, string $linkData): ?string {
		switch($paymentOperation) {
			case PaymentOperation::REFUND:
				$endpoint = self::REFUND_ENDPOINT;
				break;
			case PaymentOperation::PARTIAL_REFUND:
				$endpoint = self::PARTIAL_REFUND_ENDPOINT;
				break;
			case PaymentOperation::SETTLE:
				$endpoint = self::SETTLEMENTS_ENDPOINT;
				break;
			case PaymentOperation::PARTIAL_SETTLE:
				$endpoint = self::PARTIAL_SETTLEMENTS_ENDPOINT;
				break;
			case PaymentOperation::CANCEL:
				$endpoint = self::CANCELLATIONS_ENDPOINT;
				break;
			default:
				throw new ApiException('Payment operation not available.');
		}

		return parent::rootResource($environment) . str_replace('{linkData}', $linkData, $endpoint);
	}

	/*
	 * --------------------------------------
	 * Payment Queries
	 * --------------------------------------
	 */
	/**
	 * @param  PaymentQueryBuilder  $paymentQueryBuilder
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function queryPayment(PaymentQueryBuilder $paymentQueryBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();
		if (strpos($paymentQueryBuilder->linkData, 'https://') !== false ) {
			$endpoint = $paymentQueryBuilder->linkData;
		} else {
			$endpoint = self::queryPaymentUrl($apiConfigProvider->environment, $paymentQueryBuilder->linkData);
		}

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
							->withWpApiVersionHeader($apiConfigProvider->apiVersion)
							->get($endpoint);
	}

	/**
	 * @param  string  $environment
	 * @param  string  $linkData
	 *
	 * @return string
	 */
	public static function queryPaymentUrl(string $environment, string $linkData): string {
		return parent::rootResource($environment) . str_replace('{linkData}', $linkData, self::PAYMENT_QUERY_ENDPOINT);
	}
}
