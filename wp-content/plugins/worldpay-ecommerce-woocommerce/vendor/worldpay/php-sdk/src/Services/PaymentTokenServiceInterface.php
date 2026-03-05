<?php

namespace Worldpay\Api\Services;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\Tokens\PaymentTokenBuilder;

interface PaymentTokenServiceInterface
{
	/**
	 * Submits a payment token creation request.
	 *
	 * @param  PaymentTokenBuilder  $paymentTokenBuilder
	 *
	 * @return ApiResponse
	 */
	public static function createPaymentToken(PaymentTokenBuilder $paymentTokenBuilder): ApiResponse;

	/**
	 * Provides API endpoint for payment token request.
	 *
	 * @param  string  $environment
	 *
	 * @return string
	 */
	public static function createPaymentTokenUrl(string $environment): string;
}
