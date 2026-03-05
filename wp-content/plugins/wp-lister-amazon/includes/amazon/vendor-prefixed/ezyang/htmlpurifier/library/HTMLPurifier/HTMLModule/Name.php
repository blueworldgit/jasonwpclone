<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_HTMLModule_Name extends WPLab_Amazon_HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'Name';

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $elements = array('a', 'applet', 'form', 'frame', 'iframe', 'img', 'map');
        foreach ($elements as $name) {
            $element = $this->addBlankElement($name);
            $element->attr['name'] = 'CDATA';
            if (!$config->get('HTML.Attr.Name.UseCDATA')) {
                $element->attr_transform_post[] = new WPLab_Amazon_HTMLPurifier_AttrTransform_NameSync();
            }
        }
    }
}

// vim: et sw=4 sts=4
