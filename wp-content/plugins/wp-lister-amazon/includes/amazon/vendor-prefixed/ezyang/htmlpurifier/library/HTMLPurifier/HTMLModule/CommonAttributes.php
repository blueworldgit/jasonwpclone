<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_HTMLModule_CommonAttributes extends WPLab_Amazon_HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'CommonAttributes';

    /**
     * @type array
     */
    public $attr_collections = array(
        'Core' => array(
            0 => array('Style'),
            // 'xml:space' => false,
            'class' => 'Class',
            'id' => 'ID',
            'title' => 'CDATA',
            'contenteditable' => 'ContentEditable',
        ),
        'Lang' => array(),
        'I18N' => array(
            0 => array('Lang'), // proprietary, for xml:lang/lang
        ),
        'Common' => array(
            0 => array('Core', 'I18N')
        )
    );
}

// vim: et sw=4 sts=4
