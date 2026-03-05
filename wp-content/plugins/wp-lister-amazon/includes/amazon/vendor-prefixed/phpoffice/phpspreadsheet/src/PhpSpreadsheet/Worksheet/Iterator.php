<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Worksheet;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * @implements \Iterator<int, Worksheet>
 */
class Iterator implements \Iterator
{
    /**
     * Spreadsheet to iterate.
     *
     * @var Spreadsheet
     */
    private $subject;

    /**
     * Current iterator position.
     *
     * @var int
     */
    private $position = 0;

    /**
     * Create a new worksheet iterator.
     */
    public function __construct(Spreadsheet $subject)
    {
        // Set subject
        $this->subject = $subject;
    }

    /**
     * Rewind iterator.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Current Worksheet.
     */
    public function current(): Worksheet
    {
        return $this->subject->getSheet($this->position);
    }

    /**
     * Current key.
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Next value.
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Are there more Worksheet instances available?
     */
    public function valid(): bool
    {
        return $this->position < $this->subject->getSheetCount() && $this->position >= 0;
    }
}
