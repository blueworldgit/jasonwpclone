<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Shared;

class IntOrFloat
{
    /**
     * Help some functions with large results operate correctly on 32-bit,
     * by returning result as int when possible, float otherwise.
     *
     * @param float|int $value
     *
     * @return float|int
     */
    public static function evaluate($value)
    {
        $iValue = (int) $value;

        return ($value == $iValue) ? $iValue : $value;
    }
}
