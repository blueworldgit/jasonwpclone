<?php

/**
 * Pre-transform that changes converts a boolean attribute to fixed CSS
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_AttrTransform_BoolToCSS extends WPLab_Amazon_HTMLPurifier_AttrTransform
{
    /**
     * Name of boolean attribute that is trigger.
     * @type string
     */
    protected $attr;

    /**
     * CSS declarations to add to style, needs trailing semicolon.
     * @type string
     */
    protected $css;

    /**
     * @param string $attr attribute name to convert from
     * @param string $css CSS declarations to add to style (needs semicolon)
     */
    public function __construct($attr, $css)
    {
        $this->attr = $attr;
        $this->css = $css;
    }

    /**
     * @param array $attr
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (!isset($attr[$this->attr])) {
            return $attr;
        }
        unset($attr[$this->attr]);
        $this->prependCSS($attr, $this->css);
        return $attr;
    }
}

// vim: et sw=4 sts=4
