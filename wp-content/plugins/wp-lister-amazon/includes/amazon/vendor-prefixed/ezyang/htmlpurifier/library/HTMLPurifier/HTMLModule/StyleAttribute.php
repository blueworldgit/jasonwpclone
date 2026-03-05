<?php

/**
 * XHTML 1.1 Edit Module, defines editing-related elements. Text Extension
 * Module.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_HTMLModule_StyleAttribute extends WPLab_Amazon_HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'StyleAttribute';

    /**
     * @type array
     */
    public $attr_collections = array(
        // The inclusion routine differs from the Abstract Modules but
        // is in line with the DTD and XML Schemas.
        'Style' => array('style' => false), // see constructor
        'Core' => array(0 => array('Style'))
    );

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $this->attr_collections['Style']['style'] = new WPLab_Amazon_HTMLPurifier_AttrDef_CSS();
    }
}

// vim: et sw=4 sts=4
