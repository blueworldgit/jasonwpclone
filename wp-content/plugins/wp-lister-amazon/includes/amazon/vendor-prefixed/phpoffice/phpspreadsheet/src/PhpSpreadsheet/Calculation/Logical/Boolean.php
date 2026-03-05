<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Logical;

class Boolean
{
    /**
     * TRUE.
     *
     * Returns the boolean TRUE.
     *
     * Excel Function:
     *        =TRUE()
     *
     * @return bool True
     */
    public static function true(): bool
    {
        return true;
    }

    /**
     * FALSE.
     *
     * Returns the boolean FALSE.
     *
     * Excel Function:
     *        =FALSE()
     *
     * @return bool False
     */
    public static function false(): bool
    {
        return false;
    }
}
