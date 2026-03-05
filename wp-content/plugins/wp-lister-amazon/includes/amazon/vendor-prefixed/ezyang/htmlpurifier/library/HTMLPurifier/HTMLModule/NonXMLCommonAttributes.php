<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_HTMLModule_NonXMLCommonAttributes extends WPLab_Amazon_HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'NonXMLCommonAttributes';

    /**
     * @type array
     */
    public $attr_collections = array(
        'Lang' => array(
            'lang' => 'LanguageCode',
        )
    );
}

// vim: et sw=4 sts=4
