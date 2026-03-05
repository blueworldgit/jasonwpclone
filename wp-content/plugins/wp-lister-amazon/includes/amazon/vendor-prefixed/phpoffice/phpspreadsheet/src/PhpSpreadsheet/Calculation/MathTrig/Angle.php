<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Exception;

class Angle
{
    use ArrayEnabled;

    /**
     * DEGREES.
     *
     * Returns the result of builtin function rad2deg after validating args.
     *
     * @param mixed $number Should be numeric, or can be an array of numbers
     *
     * @return array|float|string Rounded number
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function toDegrees($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return rad2deg($number);
    }

    /**
     * RADIANS.
     *
     * Returns the result of builtin function deg2rad after validating args.
     *
     * @param mixed $number Should be numeric, or can be an array of numbers
     *
     * @return array|float|string Rounded number
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function toRadians($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return deg2rad($number);
    }
}
