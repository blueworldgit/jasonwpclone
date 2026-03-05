<?php

/**
 * Module adds the target-based noopener attribute transformation to a tags.  It
 * is enabled by HTML.TargetNoopener
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_HTMLModule_TargetNoopener extends WPLab_Amazon_HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'TargetNoopener';

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     */
    public function setup($config) {
        $a = $this->addBlankElement('a');
        $a->attr_transform_post[] = new WPLab_Amazon_HTMLPurifier_AttrTransform_TargetNoopener();
    }
}
