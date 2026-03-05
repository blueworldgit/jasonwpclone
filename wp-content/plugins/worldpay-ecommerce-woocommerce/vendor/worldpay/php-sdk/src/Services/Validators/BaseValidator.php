<?php

namespace Worldpay\Api\Services\Validators;

class BaseValidator
{
    /**
     * @param  string  $value
     * @param  int  $minLength
     * @param  int  $maxLength
     * @return bool
     */
    public static function hasValidLength(string $value, int $minLength, int $maxLength): bool {
        $length = strlen($value);

        return ($length >= $minLength) && ($length <= $maxLength);
    }

    /**
     * @param  string  $countryCode
     * @return bool
     */
    public static function hasValidCountryCode(string $countryCode): bool {
        $countryCode = strtoupper($countryCode);
        $countryCodePattern = "/^[A-Z]{2}$/";

        return preg_match($countryCodePattern, $countryCode);
    }

	/**
	 * Validate merchant narrative.
	 *
	 * @param  string  $merchantNarrative
	 * @return bool
	 */
	public static function hasValidMerchantNarrative(string $merchantNarrative): bool {
		$merchantNarrativePattern = "/^[a-zA-Z0-9\-., ]+$/";
		return preg_match($merchantNarrativePattern, $merchantNarrative);
	}
}
