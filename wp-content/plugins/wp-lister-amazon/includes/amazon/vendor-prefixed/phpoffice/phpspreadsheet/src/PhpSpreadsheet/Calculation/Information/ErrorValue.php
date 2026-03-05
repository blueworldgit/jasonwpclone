<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Information;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;

class ErrorValue
{
    use ArrayEnabled;

    /**
     * IS_ERR.
     *
     * @param mixed $value Value to check
     *                      Or can be an array of values
     *
     * @return array|bool
     *         If an array of numbers is passed as an argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function isErr($value = '')
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        return self::isError($value) && (!self::isNa(($value)));
    }

    /**
     * IS_ERROR.
     *
     * @param mixed $value Value to check
     *                      Or can be an array of values
     *
     * @return array|bool
     *         If an array of numbers is passed as an argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function isError($value = '')
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array($value, ExcelError::ERROR_CODES, true);
    }

    /**
     * IS_NA.
     *
     * @param mixed $value Value to check
     *                      Or can be an array of values
     *
     * @return array|bool
     *         If an array of numbers is passed as an argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function isNa($value = '')
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        return $value === ExcelError::NA();
    }
}
