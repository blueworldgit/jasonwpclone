<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Trig;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Helpers;

class Cosine
{
    use ArrayEnabled;

    /**
     * COS.
     *
     * Returns the result of builtin function cos after validating args.
     *
     * @param mixed $number Should be numeric, or can be an array of numbers
     *
     * @return array|float|string cosine
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function cos($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return cos($number);
    }

    /**
     * COSH.
     *
     * Returns the result of builtin function cosh after validating args.
     *
     * @param mixed $number Should be numeric, or can be an array of numbers
     *
     * @return array|float|string hyperbolic cosine
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function cosh($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return cosh($number);
    }

    /**
     * ACOS.
     *
     * Returns the arccosine of a number.
     *
     * @param array|float $number Number, or can be an array of numbers
     *
     * @return array|float|string The arccosine of the number
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function acos($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return Helpers::numberOrNan(acos($number));
    }

    /**
     * ACOSH.
     *
     * Returns the arc inverse hyperbolic cosine of a number.
     *
     * @param array|float $number Number, or can be an array of numbers
     *
     * @return array|float|string The inverse hyperbolic cosine of the number, or an error string
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function acosh($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return Helpers::numberOrNan(acosh($number));
    }
}
