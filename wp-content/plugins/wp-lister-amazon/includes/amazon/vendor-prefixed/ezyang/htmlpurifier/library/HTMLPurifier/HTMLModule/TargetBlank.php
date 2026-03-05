<?php

/**
 * Module adds the target=blank attribute transformation to a tags.  It
 * is enabled by HTML.TargetBlank
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_HTMLModule_TargetBlank extends WPLab_Amazon_HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'TargetBlank';

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $a = $this->addBlankElement('a');
        $a->attr_transform_post[] = new WPLab_Amazon_HTMLPurifier_AttrTransform_TargetBlank();
    }
}

// vim: et sw=4 sts=4
