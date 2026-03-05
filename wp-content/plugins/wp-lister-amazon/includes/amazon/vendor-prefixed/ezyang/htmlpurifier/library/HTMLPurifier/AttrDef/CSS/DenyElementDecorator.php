<?php

/**
 * Decorator which enables CSS properties to be disabled for specific elements.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_AttrDef_CSS_DenyElementDecorator extends WPLab_Amazon_HTMLPurifier_AttrDef
{
    /**
     * @type WPLab_Amazon_HTMLPurifier_AttrDef
     */
    public $def;
    /**
     * @type string
     */
    public $element;

    /**
     * @param WPLab_Amazon_HTMLPurifier_AttrDef $def Definition to wrap
     * @param string $element Element to deny
     */
    public function __construct($def, $element)
    {
        $this->def = $def;
        $this->element = $element;
    }

    /**
     * Checks if CurrentToken is set and equal to $this->element
     * @param string $string
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($string, $config, $context)
    {
        $token = $context->get('CurrentToken', true);
        if ($token && $token->name == $this->element) {
            return false;
        }
        return $this->def->validate($string, $config, $context);
    }
}

// vim: et sw=4 sts=4
