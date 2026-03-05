<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Chart\Renderer;

/**
 * Jpgraph is not oficially maintained in Composer.
 *
 * This renderer implementation uses package
 * https://packagist.org/packages/mitoteam/jpgraph
 *
 * This package is up to date for August 2022 and has PHP 8.1 support.
 */
class MtJpGraphRenderer extends JpGraphRendererBase
{
    protected static function init(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        \mitoteam\jpgraph\MtJpGraph::load([
            'bar',
            'contour',
            'line',
            'pie',
            'pie3d',
            'radar',
            'regstat',
            'scatter',
            'stock',
        ]);

        $loaded = true;
    }
}
