<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Writer\Ods;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Writer\Ods;

abstract class WriterPart
{
    /**
     * Parent Ods object.
     *
     * @var Ods
     */
    private $parentWriter;

    /**
     * Get Ods writer.
     *
     * @return Ods
     */
    public function getParentWriter()
    {
        return $this->parentWriter;
    }

    /**
     * Set parent Ods writer.
     */
    public function __construct(Ods $writer)
    {
        $this->parentWriter = $writer;
    }

    abstract public function write(): string;
}
