<?php

/**
 * Definition that allows a set of elements, and allows no children.
 * @note This is a hack to reuse code from WPLab_Amazon_HTMLPurifier_ChildDef_Required,
 *       really, one shouldn't inherit from the other.  Only altered behavior
 *       is to overload a returned false with an array.  Thus, it will never
 *       return false.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_ChildDef_Optional extends WPLab_Amazon_HTMLPurifier_ChildDef_Required
{
    /**
     * @type bool
     */
    public $allow_empty = true;

    /**
     * @type string
     */
    public $type = 'optional';

    /**
     * @param array $children
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return array
     */
    public function validateChildren($children, $config, $context)
    {
        $result = parent::validateChildren($children, $config, $context);
        // we assume that $children is not modified
        if ($result === false) {
            if (empty($children)) {
                return true;
            } elseif ($this->whitespace) {
                return $children;
            } else {
                return array();
            }
        }
        return $result;
    }
}

// vim: et sw=4 sts=4
