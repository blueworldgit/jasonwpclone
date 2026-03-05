<?php

/**
 * Definition that disallows all elements.
 * @warning validateChildren() in this class is actually never called, because
 *          empty elements are corrected in WPLab_Amazon_HTMLPurifier_Strategy_MakeWellFormed
 *          before child definitions are parsed in earnest by
 *          WPLab_Amazon_HTMLPurifier_Strategy_FixNesting.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_ChildDef_Empty extends WPLab_Amazon_HTMLPurifier_ChildDef
{
    /**
     * @type bool
     */
    public $allow_empty = true;

    /**
     * @type string
     */
    public $type = 'empty';

    public function __construct()
    {
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Node[] $children
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return array
     */
    public function validateChildren($children, $config, $context)
    {
        return array();
    }
}

// vim: et sw=4 sts=4
