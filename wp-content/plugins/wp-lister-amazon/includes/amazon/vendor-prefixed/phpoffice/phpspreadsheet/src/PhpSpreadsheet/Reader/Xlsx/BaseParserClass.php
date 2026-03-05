<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class BaseParserClass
{
    /**
     * @param mixed $value
     */
    protected static function boolean($value): bool
    {
        if (is_object($value)) {
            $value = (string) $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return $value === strtolower('true');
    }
}
