<?php

namespace Worldpay\Api\Services\AccessWorldpay;

use Worldpay\Api\AccessWorldpay;
use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\PaymentManagement\PaymentManagementBuilder;
use Worldpay\Api\Builders\PaymentProcessing\Payload\HPPApiPayloadBuilder;
use Worldpay\Api\Builders\PaymentProcessing\PaymentProcessingBuilder;
use Worldpay\Api\Builders\PaymentQuery\PaymentQueryBuilder;
use Worldpay\Api\Builders\RequestBuilder;
use Worldpay\Api\Enums\PaymentOperation;
use Worldpay\Api\Exceptions\ApiException;
use Worldpay\Api\Exceptions\ApiResponseException;
use Worldpay\Api\Exceptions\InvalidArgumentException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\PaymentManagementServiceInterface;
use Worldpay\Api\Services\PaymentProcessingServiceInterface;

class HPPApiService
	extends AccessWorldpay
	implements PaymentProcessingServiceInterface, PaymentManagementServiceInterface
{

	/**
	 * Endpoint to setup a HPP payment request.
	 */
	public const PAYMENTS_ENDPOINT = '/payment_pages';

	/**
	 * Headers for HPP payment request.
	 */
	public const HEADER_PAYMENT_PAGES_SETUP_CONTENT_TYPE = 'application/vnd.worldpay.payment_pages-v1.hal+json';
	public const HEADER_PAYMENT_PAGES_SETUP_ACCEPT = 'application/vnd.worldpay.payment_pages-v1.hal+json';

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

	/**
	 * @param  PaymentProcessingBuilder  $paymentProcessingBuilder
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public static function submitPayment(PaymentProcessingBuilder $paymentProcessingBuilder): ApiResponse {
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();

		$payloadBuilder = new HPPApiPayloadBuilder($paymentProcessingBuilder);

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withContentTypeHeader(self::paymentPagesSetupContentTypeHeader())
		                     ->withAcceptHeader(self::paymentPagesSetupAcceptHeader())
		                     ->withBody($payloadBuilder->createPayload())
		                     ->post(self::submitPaymentUrl($apiConfigProvider->environment));
	}

	/**
	 * @param  string  $environment
	 *
	 * @return string
	 */
	public static function submitPaymentUrl(string $environment): string {
		return parent::rootResource($environment) . self::PAYMENTS_ENDPOINT;
	}

	/**
	 * @return string
	 */
	public static function paymentPagesSetupContentTypeHeader(): string {
		return self::HEADER_PAYMENT_PAGES_SETUP_CONTENT_TYPE;
	}

	/**
	 * @return string
	 */
	public static function paymentPagesSetupAcceptHeader(): string {
		return self::HEADER_PAYMENT_PAGES_SETUP_ACCEPT;
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
		if (empty($paymentManagementBuilder->transactionReference)) {
			throw new InvalidArgumentException('Provide a transaction reference to find the matching card payment.');
		}

		switch($paymentManagementBuilder->paymentOperation) {
			case PaymentOperation::REFUND:
				$actionLinkKey = 'refund';
				$eMessage = 'Refund is not available. Unable to retrieve action link.';
				break;
			case PaymentOperation::PARTIAL_REFUND:
				$actionLinkKey = 'partialRefund';
				$eMessage = 'Partial refund is not available. Unable to retrieve action link.';
				break;
			case PaymentOperation::SETTLE:
				$actionLinkKey = 'settle';
				$eMessage = 'Settlement is not available. Unable to retrieve action link.';
				break;
			case PaymentOperation::PARTIAL_SETTLE:
				$actionLinkKey = 'partialSettle';
				$eMessage = 'Partial settlement is not available. Unable to retrieve action link.';
				break;
			case PaymentOperation::CANCEL:
				$actionLinkKey = 'cancel';
				$eMessage = 'Cancellation is not available. Unable to retrieve action link.';
				break;
			default:
				$actionLinkKey = '';
				$eMessage = 'Payment operation not available.';
		}
		$actionLink = self::managePaymentUrl($paymentManagementBuilder, $actionLinkKey);
		if (empty($actionLink)) {
			throw new ApiException($eMessage);
		}

		return RequestBuilder::withBasicAuth($apiConfigProvider->username, $apiConfigProvider->password)
		                     ->withContentTypeHeader(AccessWorldpay::paymentsEventsContentTypeHeader())
		                     ->withAcceptHeader(AccessWorldpay::paymentsEventsAcceptHeader())
		                     ->withBody($paymentManagementBuilder->createPayload())
		                     ->post($actionLink);
	}

	/**
	 * @param  PaymentManagementBuilder  $paymentManagementBuilder
	 * @param  string  $actionLinkKey
	 *
	 * @return string|null
	 */
	private static function managePaymentUrl(PaymentManagementBuilder $paymentManagementBuilder, string $actionLinkKey): ?string {
		$actionLinks = self::queryPaymentForActionLinks($paymentManagementBuilder);

		return $actionLinks->{"cardPayments:$actionLinkKey"}->href ?? $actionLinks->{"payments:$actionLinkKey"}->href ?? null;
	}

	/**
	 * @param  PaymentManagementBuilder  $paymentManagementBuilder
	 *
	 * @return object
	 * @throws ApiException
	 * @throws ApiResponseException
	 */
	private static function queryPaymentForActionLinks(PaymentManagementBuilder $paymentManagementBuilder): object {
		// Query payment by transaction reference to get payment id
		$apiResponse =self::queryPaymentByTransactionReference($paymentManagementBuilder->transactionReference);
		if (!$apiResponse->isSuccessful()) {
			throw new ApiException('Something went wrong while processing your request. Please contact support.', $apiResponse->statusCode);
		}
		$decodedApiResponse = $apiResponse->jsonDecode(true);
		$paymentUrlWithPaymentId = $decodedApiResponse['_embedded']['payments'][0]['_links']['self']['href'] ?? null;
		if (empty($paymentUrlWithPaymentId)) {
			throw new ApiException('No payments associated with the given parameters.');
		}

		$paymentId = explode('/', $paymentUrlWithPaymentId)[3] ?? null;
		if (empty($paymentId)) {
			throw new ApiException('Unable to retrieve payment Id.');
		}

		// Query payment by payment id to get refund url
		$apiResponse = self::queryPaymentByPaymentId($paymentId);
		if (!$apiResponse->isSuccessful()) {
			throw new ApiException('Something went wrong while processing your request. Please contact support.', $apiResponse->statusCode);
		}
		$decodedApiResponse = $apiResponse->jsonDecode();

		return $decodedApiResponse->_links;
	}

	/**
	 * @param  string  $transactionReference
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	private static function queryPaymentByTransactionReference(string $transactionReference): ApiResponse {
		$paymentQueryBuilder = (new PaymentQueryBuilder())->withTransactionReference($transactionReference);

		return PaymentQueriesApiService::queryPayment($paymentQueryBuilder);
	}

	/**
	 * @param  string  $paymentId
	 *
	 * @return ApiResponse
	 * @throws \Exception
	 */
	private static function queryPaymentByPaymentId(string $paymentId): ApiResponse {
		$paymentQueryBuilder = (new PaymentQueryBuilder())->withPaymentId($paymentId);

		return PaymentQueriesApiService::queryPaymentByPaymentId($paymentQueryBuilder);
	}
}
