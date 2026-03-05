<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Helper;

class Size
{
    const REGEXP_SIZE_VALIDATION = '/^(?P<size>\d*\.?\d+)(?P<unit>pt|px|em)?$/i';

    /**
     * @var bool
     */
    protected $valid;

    /**
     * @var string
     */
    protected $size = '';

    /**
     * @var string
     */
    protected $unit = '';

    public function __construct(string $size)
    {
        $this->valid = (bool) preg_match(self::REGEXP_SIZE_VALIDATION, $size, $matches);
        if ($this->valid) {
            $this->size = $matches['size'];
            $this->unit = $matches['unit'] ?? 'pt';
        }
    }

    public function valid(): bool
    {
        return $this->valid;
    }

    public function size(): string
    {
        return $this->size;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function __toString()
    {
        return $this->size . $this->unit;
    }
}
