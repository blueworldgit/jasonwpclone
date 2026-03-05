<?php

namespace Worldpay\Api\Utils;

use Worldpay\Api\Exceptions\InvalidArgumentException;

class AmountHelper
{
    /**
     * @param $amount The numeric currency value with a decimal separator only (no thousands).
     * @param $currency The 3 character ISO currency code.
     * @param $merchantLocale The locale in which the number would be formatted.
     * @return int
     * @throws InvalidArgumentException
     */
    public static function decimalToExponentDelimiter($amount, $currency = 'GBP', $merchantLocale = 'en-GB'): int {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Invalid amount.');
        }
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be greater than or equal to zero.');
        }
        if (!is_string($currency)) {
            throw new InvalidArgumentException('Invalid currency.');
        }
        if (!is_string($merchantLocale)) {
            throw new InvalidArgumentException('Invalid language tag.');
        }

        $formatter = new \NumberFormatter($merchantLocale, \NumberFormatter::CURRENCY);

        return (int) ltrim(preg_replace("/[^0-9]/","", $formatter->formatCurrency($amount, $currency)), "0");
    }

    /**
     * @param int $amount
     * @return string
     */
    public static function exponentToDecimalDelimiter(int $amount): string {
        return number_format(round($amount/100, 2, PHP_ROUND_HALF_UP), 2, '.', '');
    }
}
