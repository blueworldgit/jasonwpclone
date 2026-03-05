<?php

/**
 * Simple transformation, just change tag name to something else,
 * and possibly add some styling. This will cover most of the deprecated
 * tag cases.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_TagTransform_Simple extends WPLab_Amazon_HTMLPurifier_TagTransform
{
    /**
     * @type string
     */
    protected $style;

    /**
     * @param string $transform_to Tag name to transform to.
     * @param string $style CSS style to add to the tag
     */
    public function __construct($transform_to, $style = null)
    {
        $this->transform_to = $transform_to;
        $this->style = $style;
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Token_Tag $tag
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return string
     */
    public function transform($tag, $config, $context)
    {
        $new_tag = clone $tag;
        $new_tag->name = $this->transform_to;
        if (!is_null($this->style) &&
            ($new_tag instanceof WPLab_Amazon_HTMLPurifier_Token_Start || $new_tag instanceof WPLab_Amazon_HTMLPurifier_Token_Empty)
        ) {
            $this->prependCSS($new_tag->attr, $this->style);
        }
        return $new_tag;
    }
}

// vim: et sw=4 sts=4
