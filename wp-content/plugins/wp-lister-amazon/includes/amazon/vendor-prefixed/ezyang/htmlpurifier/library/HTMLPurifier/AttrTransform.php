<?php

/**
 * Processes an entire attribute array for corrections needing multiple values.
 *
 * Occasionally, a certain attribute will need to be removed and popped onto
 * another value.  Instead of creating a complex return syntax for
 * WPLab_Amazon_HTMLPurifier_AttrDef, we just pass the whole attribute array to a
 * specialized object and have that do the special work.  That is the
 * family of WPLab_Amazon_HTMLPurifier_AttrTransform.
 *
 * An attribute transformation can be assigned to run before or after
 * WPLab_Amazon_HTMLPurifier_AttrDef validation.  See WPLab_Amazon_HTMLPurifier_HTMLDefinition for
 * more details.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

abstract class WPLab_Amazon_HTMLPurifier_AttrTransform
{

    /**
     * Abstract: makes changes to the attributes dependent on multiple values.
     *
     * @param array $attr Assoc array of attributes, usually from
     *              WPLab_Amazon_HTMLPurifier_Token_Tag::$attr
     * @param WPLab_Amazon_HTMLPurifier_Config $config Mandatory WPLab_Amazon_HTMLPurifier_Config object.
     * @param WPLab_Amazon_HTMLPurifier_Context $context Mandatory WPLab_Amazon_HTMLPurifier_Context object
     * @return array Processed attribute array.
     */
    abstract public function transform($attr, $config, $context);

    /**
     * Prepends CSS properties to the style attribute, creating the
     * attribute if it doesn't exist.
     * @param array &$attr Attribute array to process (passed by reference)
     * @param string $css CSS to prepend
     */
    public function prependCSS(&$attr, $css)
    {
        $attr['style'] = isset($attr['style']) ? $attr['style'] : '';
        $attr['style'] = $css . $attr['style'];
    }

    /**
     * Retrieves and removes an attribute
     * @param array &$attr Attribute array to process (passed by reference)
     * @param mixed $key Key of attribute to confiscate
     * @return mixed
     */
    public function confiscateAttr(&$attr, $key)
    {
        if (!isset($attr[$key])) {
            return null;
        }
        $value = $attr[$key];
        unset($attr[$key]);
        return $value;
    }
}

// vim: et sw=4 sts=4
