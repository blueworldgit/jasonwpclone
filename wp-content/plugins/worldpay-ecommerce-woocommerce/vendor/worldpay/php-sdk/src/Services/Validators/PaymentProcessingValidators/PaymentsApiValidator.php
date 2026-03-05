<?php

namespace Worldpay\Api\Services\Validators\PaymentProcessingValidators;

use Worldpay\Api\Services\Validators\PaymentProcessingValidator;

class PaymentsApiValidator extends PaymentProcessingValidator
{
	/**
	 * CUSTOMER_FIRST_NAME_MIN_SIZE
	 */
	public const CUSTOMER_FIRST_NAME_MIN_SIZE = 1;

	/**
	 * CUSTOMER_FIRST_NAME_MAX_SIZE
	 */
	public const CUSTOMER_FIRST_NAME_MAX_SIZE = 22;

	/**
	 * CUSTOMER_LAST_NAME_MIN_SIZE
	 */
	public const CUSTOMER_LAST_NAME_MIN_SIZE = 1;

	/**
	 * CUSTOMER_LAST_NAME_MAX_SIZE
	 */
	public const CUSTOMER_LAST_NAME_MAX_SIZE = 22;

	/**
	 * CUSTOMER_PHONE_NUMBER_MIN_SIZE
	 */
	public const CUSTOMER_PHONE_NUMBER_MIN_SIZE = 4;

	/**
	 * CUSTOMER_PHONE_NUMBER_MAX_SIZE
	 */
	public const CUSTOMER_PHONE_NUMBER_MAX_SIZE = 20;

	/**
	 * CUSTOMER_EMAIL_MIN_SIZE
	 */
	public const CUSTOMER_EMAIL_MIN_SIZE = 3;

	/**
	 * CUSTOMER_EMAIL_MAX_SIZE
	 */
	public const CUSTOMER_EMAIL_MAX_SIZE = 128;

	/**
	 * BILLING_ADDRESS1_MIN_SIZE
	 */
	public const BILLING_ADDRESS1_MIN_SIZE = 1;

	/**
	 * BILLING_ADDRESS1_MAX_SIZE
	 */
	public const BILLING_ADDRESS1_MAX_SIZE = 80;

	/**
	 * BILLING_ADDRESS2_MIN_SIZE
	 */
	public const BILLING_ADDRESS2_MIN_SIZE = 1;

	/**
	 * BILLING_ADDRESS2_MAX_SIZE
	 */
	public const BILLING_ADDRESS2_MAX_SIZE = 80;

	/**
	 * BILLING_ADDRESS3_MIN_SIZE
	 */
	public const BILLING_ADDRESS3_MIN_SIZE = 1;

	/**
	 * BILLING_ADDRESS3_MAX_SIZE
	 */
	public const BILLING_ADDRESS3_MAX_SIZE = 80;

	/**
	 * BILLING_POSTAL_CODE_MIN_SIZE
	 */
	public const BILLING_POSTALCODE_MIN_SIZE = 1;

	/**
	 * BILLING_POSTAL_CODE_MAX_SIZE
	 */
	public const BILLING_POSTALCODE_MAX_SIZE = 15;

	/**
	 * BILLING_CITY_MIN_SIZE
	 */
	public const BILLING_CITY_MIN_SIZE = 1;

	/**
	 * BILLING_CITY_MAX_SIZE
	 */
	public const BILLING_CITY_MAX_SIZE = 50;

	/**
	 * BILLING_STATE_MIN_SIZE
	 */
	public const BILLING_STATE_MIN_SIZE = 1;

	/**
	 * BILLING_STATE_MAX_SIZE
	 */
	public const BILLING_STATE_MAX_SIZE = 30;

	/**
	 * SHIPPING_ADDRESS1_MIN_SIZE
	 */
	public const SHIPPING_ADDRESS1_MIN_SIZE = 1;

	/**
	 * SHIPPING_ADDRESS1_MAX_SIZE
	 */
	public const SHIPPING_ADDRESS1_MAX_SIZE = 80;

	/**
	 * SHIPPING_ADDRESS2_MIN_SIZE
	 */
	public const SHIPPING_ADDRESS2_MIN_SIZE = 1;

	/**
	 * SHIPPING_ADDRESS2_MAX_SIZE
	 */
	public const SHIPPING_ADDRESS2_MAX_SIZE = 80;

	/**
	 * SHIPPING_ADDRESS3_MIN_SIZE
	 */
	public const SHIPPING_ADDRESS3_MIN_SIZE = 1;

	/**
	 * SHIPPING_ADDRESS3_MAX_SIZE
	 */
	public const SHIPPING_ADDRESS3_MAX_SIZE = 80;

	/**
	 * SHIPPING_POSTAL_CODE_MIN_SIZE
	 */
	public const SHIPPING_POSTALCODE_MIN_SIZE = 1;

	/**
	 * SHIPPING_POSTAL_CODE_MAX_SIZE
	 */
	public const SHIPPING_POSTALCODE_MAX_SIZE = 15;

	/**
	 * SHIPPING_CITY_MIN_SIZE
	 */
	public const SHIPPING_CITY_MIN_SIZE = 1;

	/**
	 * SHIPPING_CITY_MAX_SIZE
	 */
	public const SHIPPING_CITY_MAX_SIZE = 50;

	/**
	 * SHIPPING_STATE_MIN_SIZE
	 */
	public const SHIPPING_STATE_MIN_SIZE = 1;

	/**
	 * SHIPPING_STATE_MAX_SIZE
	 */
	public const SHIPPING_STATE_MAX_SIZE = 30;
}
