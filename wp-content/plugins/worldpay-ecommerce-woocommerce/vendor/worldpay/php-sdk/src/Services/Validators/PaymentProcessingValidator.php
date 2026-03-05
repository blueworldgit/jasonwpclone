<?php

namespace Worldpay\Api\Services\Validators;

use Worldpay\Api\ValueObjects\BillingAddress;
use Worldpay\Api\ValueObjects\ShippingAddress;

class PaymentProcessingValidator extends BaseValidator
{
	/**
	 * @param  string  $validatorType
	 * @param  BillingAddress  $billingAddress
	 *
	 * @return bool
	 */
	public static function hasBillingAddressRequiredFields(string $validatorType, BillingAddress $billingAddress): bool {
		return parent::hasValidLength(
				$billingAddress->postalCode ?? '',
				$validatorType::BILLING_POSTALCODE_MIN_SIZE,
				$validatorType::BILLING_POSTALCODE_MAX_SIZE) &&
		       parent::hasValidLength(
			       $billingAddress->address1 ?? '',
			       $validatorType::BILLING_ADDRESS1_MIN_SIZE,
			       $validatorType::BILLING_ADDRESS1_MAX_SIZE) &&
		       parent::hasValidLength(
			       $billingAddress->city ?? '',
			       $validatorType::BILLING_CITY_MIN_SIZE,
			       $validatorType::BILLING_CITY_MAX_SIZE) &&
		       parent::hasValidCountryCode($billingAddress->countryCode ?? '');
	}

	/**
	 * @param  string  $validatorType
	 * @param  ShippingAddress  $shippingAddress
	 *
	 * @return bool
	 */
	public static function hasShippingAddressRequiredFields(string $validatorType, ShippingAddress $shippingAddress): bool {
		return parent::hasValidLength(
				$shippingAddress->postalCode ?? '',
				$validatorType::SHIPPING_POSTALCODE_MIN_SIZE,
				$validatorType::SHIPPING_POSTALCODE_MAX_SIZE) &&
		       parent::hasValidLength(
			       $shippingAddress->address1 ?? '',
			       $validatorType::SHIPPING_ADDRESS1_MIN_SIZE,
			       $validatorType::SHIPPING_ADDRESS1_MAX_SIZE) &&
		       parent::hasValidLength(
			       $shippingAddress->city ?? '',
			       $validatorType::SHIPPING_CITY_MIN_SIZE,
			       $validatorType::SHIPPING_CITY_MAX_SIZE) &&
		       parent::hasValidCountryCode($shippingAddress->countryCode ?? '');
	}

	/**
	 * Sanitize customer phone number to match HPP validation.
	 *
	 * @param  string  $phoneNumber
	 * @return string
	 */
	public static function sanitizeCustomerPhoneNumber(string $phoneNumber): string {
		$phoneNumberPattern = "/[^0-9]/";

		return preg_replace($phoneNumberPattern, '', $phoneNumber);
	}
}
