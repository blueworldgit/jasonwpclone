<?php

namespace Worldpay\Api\Services;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\PaymentProcessing\PaymentProcessingBuilder;

interface PaymentProcessingServiceInterface
{
	/**
	 * Submit a payment request.
	 *
	 * @param  PaymentProcessingBuilder  $paymentProcessingBuilder
	 *
	 * @return ApiResponse
	 */
	public static function submitPayment(PaymentProcessingBuilder $paymentProcessingBuilder): ApiResponse;

	/**
	 * Provides API endpoint for payment request.
	 *
	 * @param  string  $environment
	 *
	 * @return string
	 */
	public static function submitPaymentUrl(string $environment): string;
}
