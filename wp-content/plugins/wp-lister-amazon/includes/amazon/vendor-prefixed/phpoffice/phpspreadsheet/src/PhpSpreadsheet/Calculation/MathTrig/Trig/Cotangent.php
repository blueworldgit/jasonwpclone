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

class Cotangent
{
    use ArrayEnabled;

    /**
     * COT.
     *
     * Returns the cotangent of an angle.
     *
     * @param array|float $angle Number, or can be an array of numbers
     *
     * @return array|float|string The cotangent of the angle
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function cot($angle)
    {
        if (is_array($angle)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $angle);
        }

        try {
            $angle = Helpers::validateNumericNullBool($angle);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return Helpers::verySmallDenominator(cos($angle), sin($angle));
    }

    /**
     * COTH.
     *
     * Returns the hyperbolic cotangent of an angle.
     *
     * @param array|float $angle Number, or can be an array of numbers
     *
     * @return array|float|string The hyperbolic cotangent of the angle
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function coth($angle)
    {
        if (is_array($angle)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $angle);
        }

        try {
            $angle = Helpers::validateNumericNullBool($angle);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return Helpers::verySmallDenominator(1.0, tanh($angle));
    }

    /**
     * ACOT.
     *
     * Returns the arccotangent of a number.
     *
     * @param array|float $number Number, or can be an array of numbers
     *
     * @return array|float|string The arccotangent of the number
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function acot($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return (M_PI / 2) - atan($number);
    }

    /**
     * ACOTH.
     *
     * Returns the hyperbolic arccotangent of a number.
     *
     * @param array|float $number Number, or can be an array of numbers
     *
     * @return array|float|string The hyperbolic arccotangent of the number
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function acoth($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $result = ($number === 1) ? NAN : (log(($number + 1) / ($number - 1)) / 2);

        return Helpers::numberOrNan($result);
    }
}
