<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Reader\Xml\Style;

use SimpleXMLElement;

class NumberFormat extends StyleBase
{
    public function parseStyle(SimpleXMLElement $styleAttributes): array
    {
        $style = [];

        $fromFormats = ['\-', '\ '];
        $toFormats = ['-', ' '];

        foreach ($styleAttributes as $styleAttributeKey => $styleAttributeValue) {
            $styleAttributeValue = str_replace($fromFormats, $toFormats, $styleAttributeValue);

            switch ($styleAttributeValue) {
                case 'Short Date':
                    $styleAttributeValue = 'dd/mm/yyyy';

                    break;
            }

            if ($styleAttributeValue > '') {
                $style['numberFormat']['formatCode'] = $styleAttributeValue;
            }
        }

        return $style;
    }
}
