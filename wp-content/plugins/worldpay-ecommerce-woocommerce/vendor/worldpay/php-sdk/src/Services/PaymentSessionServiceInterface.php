<?php

namespace Worldpay\Api\Services;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\PaymentSession\PaymentSessionBuilder;

interface PaymentSessionServiceInterface
{
	/**
	 * Submits a payment session creation request.
	 *
	 * @param  PaymentSessionBuilder  $paymentSessionBuilder
	 *
	 * @return ApiResponse
	 */
	public static function setupPaymentSession(PaymentSessionBuilder $paymentSessionBuilder): ApiResponse;

	/**
	 * Provides API endpoint for payment session request.
	 *
	 * @param  string  $environment
	 *
	 * @return string
	 */
	public static function setupPaymentSessionUrl(string $environment): string;
}
