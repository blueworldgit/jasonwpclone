<?php

/**
 * XHTML 1.1 Target Module, defines target attribute in link elements.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_HTMLModule_Target extends WPLab_Amazon_HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'Target';

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $elements = array('a');
        foreach ($elements as $name) {
            $e = $this->addBlankElement($name);
            $e->attr = array(
                'target' => new WPLab_Amazon_HTMLPurifier_AttrDef_HTML_FrameTarget()
            );
        }
    }
}

// vim: et sw=4 sts=4
