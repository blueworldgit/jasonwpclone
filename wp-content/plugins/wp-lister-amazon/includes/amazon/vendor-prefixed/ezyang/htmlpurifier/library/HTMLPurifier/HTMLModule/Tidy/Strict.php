<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_HTMLModule_Tidy_Strict extends WPLab_Amazon_HTMLPurifier_HTMLModule_Tidy_XHTMLAndHTML4
{
    /**
     * @type string
     */
    public $name = 'Tidy_Strict';

    /**
     * @type string
     */
    public $defaultLevel = 'light';

    /**
     * @return array
     */
    public function makeFixes()
    {
        $r = parent::makeFixes();
        $r['blockquote#content_model_type'] = 'strictblockquote';
        return $r;
    }

    /**
     * @type bool
     */
    public $defines_child_def = true;

    /**
     * @param WPLab_Amazon_HTMLPurifier_ElementDef $def
     * @return WPLab_Amazon_HTMLPurifier_ChildDef_StrictBlockquote
     */
    public function getChildDef($def)
    {
        if ($def->content_model_type != 'strictblockquote') {
            return parent::getChildDef($def);
        }
        return new WPLab_Amazon_HTMLPurifier_ChildDef_StrictBlockquote($def->content_model);
    }
}

// vim: et sw=4 sts=4
