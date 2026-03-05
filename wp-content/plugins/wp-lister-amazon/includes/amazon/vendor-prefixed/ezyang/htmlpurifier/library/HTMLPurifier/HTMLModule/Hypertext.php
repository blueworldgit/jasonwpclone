<?php

/**
 * XHTML 1.1 Hypertext Module, defines hypertext links. Core Module.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_HTMLModule_Hypertext extends WPLab_Amazon_HTMLPurifier_HTMLModule
{

    /**
     * @type string
     */
    public $name = 'Hypertext';

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $a = $this->addElement(
            'a',
            'Inline',
            'Inline',
            'Common',
            array(
                // 'accesskey' => 'Character',
                // 'charset' => 'Charset',
                'href' => 'URI',
                // 'hreflang' => 'LanguageCode',
                'rel' => new WPLab_Amazon_HTMLPurifier_AttrDef_HTML_LinkTypes('rel'),
                'rev' => new WPLab_Amazon_HTMLPurifier_AttrDef_HTML_LinkTypes('rev'),
                // 'tabindex' => 'Number',
                // 'type' => 'ContentType',
            )
        );
        $a->formatting = true;
        $a->excludes = array('a' => true);
    }
}

// vim: et sw=4 sts=4
