<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class LookupRefValidations
{
    /**
     * @param mixed $value
     */
    public static function validateInt($value): int
    {
        if (!is_numeric($value)) {
            if (ErrorValue::isError($value)) {
                throw new Exception($value);
            }

            throw new Exception(ExcelError::VALUE());
        }

        return (int) floor((float) $value);
    }

    /**
     * @param mixed $value
     */
    public static function validatePositiveInt($value, bool $allowZero = true): int
    {
        $value = self::validateInt($value);

        if (($allowZero === false && $value <= 0) || $value < 0) {
            throw new Exception(ExcelError::VALUE());
        }

        return $value;
    }
}
