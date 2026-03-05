<?php

/**
 * Post-transform that performs validation to the name attribute; if
 * it is present with an equivalent id attribute, it is passed through;
 * otherwise validation is performed.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_AttrTransform_NameSync extends WPLab_Amazon_HTMLPurifier_AttrTransform
{

    /**
     * @type WPLab_Amazon_HTMLPurifier_AttrDef_HTML_ID
     */
    public $idDef;

    public function __construct()
    {
        $this->idDef = new WPLab_Amazon_HTMLPurifier_AttrDef_HTML_ID();
    }

    /**
     * @param array $attr
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (!isset($attr['name'])) {
            return $attr;
        }
        $name = $attr['name'];
        if (isset($attr['id']) && $attr['id'] === $name) {
            return $attr;
        }
        $result = $this->idDef->validate($name, $config, $context);
        if ($result === false) {
            unset($attr['name']);
        } else {
            $attr['name'] = $result;
        }
        return $attr;
    }
}

// vim: et sw=4 sts=4
