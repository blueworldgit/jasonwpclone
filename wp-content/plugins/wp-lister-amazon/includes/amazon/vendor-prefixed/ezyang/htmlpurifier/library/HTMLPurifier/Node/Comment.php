<?php

/**
 * Concrete comment node class.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_Node_Comment extends WPLab_Amazon_HTMLPurifier_Node
{
    /**
     * Character data within comment.
     * @type string
     */
    public $data;

    /**
     * @type bool
     */
    public $is_whitespace = true;

    /**
     * Transparent constructor.
     *
     * @param string $data String comment data.
     * @param int $line
     * @param int $col
     */
    public function __construct($data, $line = null, $col = null)
    {
        $this->data = $data;
        $this->line = $line;
        $this->col = $col;
    }

    public function toTokenPair() {
        return array(new WPLab_Amazon_HTMLPurifier_Token_Comment($this->data, $this->line, $this->col), null);
    }
}
