<?php

namespace Worldpay\Api\Services;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Builders\PaymentQuery\PaymentQueryBuilder;

interface PaymentQueryServiceInterface
{
	/**
	 * Submits a payment query request.
	 *
	 * @param  PaymentQueryBuilder  $paymentQueryBuilder
	 *
	 * @return ApiResponse
	 */
	public static function queryPayment(PaymentQueryBuilder $paymentQueryBuilder): ApiResponse;
}
