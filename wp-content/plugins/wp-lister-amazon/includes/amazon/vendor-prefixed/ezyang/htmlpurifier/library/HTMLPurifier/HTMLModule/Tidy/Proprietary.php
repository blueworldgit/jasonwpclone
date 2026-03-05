<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_HTMLModule_Tidy_Proprietary extends WPLab_Amazon_HTMLPurifier_HTMLModule_Tidy
{

    /**
     * @type string
     */
    public $name = 'Tidy_Proprietary';

    /**
     * @type string
     */
    public $defaultLevel = 'light';

    /**
     * @return array
     */
    public function makeFixes()
    {
        $r = array();
        $r['table@background'] = new WPLab_Amazon_HTMLPurifier_AttrTransform_Background();
        $r['td@background']    = new WPLab_Amazon_HTMLPurifier_AttrTransform_Background();
        $r['th@background']    = new WPLab_Amazon_HTMLPurifier_AttrTransform_Background();
        $r['tr@background']    = new WPLab_Amazon_HTMLPurifier_AttrTransform_Background();
        $r['thead@background'] = new WPLab_Amazon_HTMLPurifier_AttrTransform_Background();
        $r['tfoot@background'] = new WPLab_Amazon_HTMLPurifier_AttrTransform_Background();
        $r['tbody@background'] = new WPLab_Amazon_HTMLPurifier_AttrTransform_Background();
        $r['table@height']     = new WPLab_Amazon_HTMLPurifier_AttrTransform_Length('height');
        return $r;
    }
}

// vim: et sw=4 sts=4
