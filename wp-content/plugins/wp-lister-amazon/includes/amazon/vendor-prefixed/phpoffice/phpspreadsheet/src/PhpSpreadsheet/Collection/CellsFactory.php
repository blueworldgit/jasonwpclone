<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Collection;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Settings;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class CellsFactory
{
    /**
     * Initialise the cache storage.
     *
     * @param Worksheet $worksheet Enable cell caching for this worksheet
     *
     * */
    public static function getInstance(Worksheet $worksheet): Cells
    {
        return new Cells($worksheet, Settings::getCache());
    }
}
