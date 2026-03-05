<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class EngineeringValidations
{
    /**
     * @param mixed $value
     */
    public static function validateFloat($value): float
    {
        if (!is_numeric($value)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (float) $value;
    }

    /**
     * @param mixed $value
     */
    public static function validateInt($value): int
    {
        if (!is_numeric($value)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (int) floor((float) $value);
    }
}
