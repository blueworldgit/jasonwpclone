<?php

/**
 * Dummy AttrDef that mimics another AttrDef, BUT it generates clones
 * with make.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_AttrDef_Clone extends WPLab_Amazon_HTMLPurifier_AttrDef
{
    /**
     * What we're cloning.
     * @type WPLab_Amazon_HTMLPurifier_AttrDef
     */
    protected $clone;

    /**
     * @param WPLab_Amazon_HTMLPurifier_AttrDef $clone
     */
    public function __construct($clone)
    {
        $this->clone = $clone;
    }

    /**
     * @param string $v
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($v, $config, $context)
    {
        return $this->clone->validate($v, $config, $context);
    }

    /**
     * @param string $string
     * @return WPLab_Amazon_HTMLPurifier_AttrDef
     */
    public function make($string)
    {
        return clone $this->clone;
    }
}

// vim: et sw=4 sts=4
