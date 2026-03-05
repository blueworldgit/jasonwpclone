<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Confidence
{
    use ArrayEnabled;

    /**
     * CONFIDENCE.
     *
     * Returns the confidence interval for a population mean
     *
     * @param mixed $alpha As a float
     *                      Or can be an array of values
     * @param mixed $stdDev Standard Deviation as a float
     *                      Or can be an array of values
     * @param mixed $size As an integer
     *                      Or can be an array of values
     *
     * @return array|float|string
     *         If an array of numbers is passed as an argument, then the returned result will also be an array
     *            with the same dimensions
     */
    public static function CONFIDENCE($alpha, $stdDev, $size)
    {
        if (is_array($alpha) || is_array($stdDev) || is_array($size)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $alpha, $stdDev, $size);
        }

        try {
            $alpha = StatisticalValidations::validateFloat($alpha);
            $stdDev = StatisticalValidations::validateFloat($stdDev);
            $size = StatisticalValidations::validateInt($size);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($alpha <= 0) || ($alpha >= 1) || ($stdDev <= 0) || ($size < 1)) {
            return ExcelError::NAN();
        }
        /** @var float */
        $temp = Distributions\StandardNormal::inverse(1 - $alpha / 2);

        return Functions::scalar($temp * $stdDev / sqrt($size));
    }
}
