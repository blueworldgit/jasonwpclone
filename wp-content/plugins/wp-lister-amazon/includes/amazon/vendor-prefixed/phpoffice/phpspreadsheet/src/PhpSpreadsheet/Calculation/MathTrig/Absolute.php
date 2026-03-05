<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Exception;

class Absolute
{
    use ArrayEnabled;

    /**
     * ABS.
     *
     * Returns the result of builtin function abs after validating args.
     *
     * @param mixed $number Should be numeric, or can be an array of numbers
     *
     * @return array|float|int|string rounded number
     *         If an array of numbers is passed as the argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function evaluate($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return abs($number);
    }
}
