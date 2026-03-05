<?php

namespace Worldpay\Api\Services;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\PaymentManagement\PaymentManagementBuilder;

interface PaymentManagementServiceInterface
{
	/**
	 * @param  string  $operationType
	 *
	 * @return bool
	 */
	public static function supports(string $operationType): bool;

	/**
	 * @param  PaymentManagementBuilder  $paymentManagementBuilder
	 *
	 * @return ApiResponse
	 */
	public static function managePayment(PaymentManagementBuilder $paymentManagementBuilder): ApiResponse;
}
