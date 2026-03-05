<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Reader\Ods;

use DOMElement;
use DOMNode;

class AutoFilter extends BaseLoader
{
    public function read(DOMElement $workbookData): void
    {
        $this->readAutoFilters($workbookData);
    }

    protected function readAutoFilters(DOMElement $workbookData): void
    {
        $databases = $workbookData->getElementsByTagNameNS($this->tableNs, 'database-ranges');

        foreach ($databases as $autofilters) {
            foreach ($autofilters->childNodes as $autofilter) {
                $autofilterRange = $this->getAttributeValue($autofilter, 'target-range-address');
                if ($autofilterRange !== null) {
                    $baseAddress = FormulaTranslator::convertToExcelAddressValue($autofilterRange);
                    $this->spreadsheet->getActiveSheet()->setAutoFilter($baseAddress);
                }
            }
        }
    }

    protected function getAttributeValue(?DOMNode $node, string $attributeName): ?string
    {
        if ($node !== null && $node->attributes !== null) {
            $attribute = $node->attributes->getNamedItemNS(
                $this->tableNs,
                $attributeName
            );

            if ($attribute !== null) {
                return $attribute->nodeValue;
            }
        }

        return null;
    }
}
