<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Functions;

abstract class VarianceBase
{
    protected static function datatypeAdjustmentAllowStrings($value)
    {
        if (is_bool($value)) {
            return (int) $value;
        } elseif (is_string($value)) {
            return 0;
        }

        return $value;
    }

    protected static function datatypeAdjustmentBooleans($value)
    {
        if (is_bool($value) && (Functions::getCompatibilityMode() == Functions::COMPATIBILITY_OPENOFFICE)) {
            return (int) $value;
        }

        return $value;
    }
}
