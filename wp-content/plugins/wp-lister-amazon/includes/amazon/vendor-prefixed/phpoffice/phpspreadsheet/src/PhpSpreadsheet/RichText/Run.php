<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\RichText;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Style\Font;

class Run extends TextElement implements ITextElement
{
    /**
     * Font.
     *
     * @var Font
     */
    private $font;

    /**
     * Create a new Run instance.
     *
     * @param string $text Text
     */
    public function __construct($text = '')
    {
        parent::__construct($text);
        // Initialise variables
        $this->font = new Font();
    }

    /**
     * Get font.
     *
     * @return null|\WPLab\Amazon\PhpOffice\PhpSpreadsheet\Style\Font
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Set font.
     *
     * @param Font $font Font
     *
     * @return $this
     */
    public function setFont(?Font $font = null)
    {
        $this->font = $font;

        return $this;
    }

    /**
     * Get hash code.
     *
     * @return string Hash code
     */
    public function getHashCode()
    {
        return md5(
            $this->getText() .
            $this->font->getHashCode() .
            __CLASS__
        );
    }
}
