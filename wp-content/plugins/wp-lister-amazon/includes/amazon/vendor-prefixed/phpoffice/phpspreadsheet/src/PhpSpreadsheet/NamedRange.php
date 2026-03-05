<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NamedRange extends DefinedName
{
    /**
     * Create a new Named Range.
     */
    public function __construct(
        string $name,
        ?Worksheet $worksheet = null,
        string $range = 'A1',
        bool $localOnly = false,
        ?Worksheet $scope = null
    ) {
        if ($worksheet === null && $scope === null) {
            throw new Exception('You must specify a worksheet or a scope for a Named Range');
        }
        parent::__construct($name, $worksheet, $range, $localOnly, $scope);
    }

    /**
     * Get the range value.
     */
    public function getRange(): string
    {
        return $this->value;
    }

    /**
     * Set the range value.
     */
    public function setRange(string $range): self
    {
        if (!empty($range)) {
            $this->value = $range;
        }

        return $this;
    }

    public function getCellsInRange(): array
    {
        $range = $this->value;
        if (substr($range, 0, 1) === '=') {
            $range = substr($range, 1);
        }

        return Coordinate::extractAllCellReferencesInRange($range);
    }
}
