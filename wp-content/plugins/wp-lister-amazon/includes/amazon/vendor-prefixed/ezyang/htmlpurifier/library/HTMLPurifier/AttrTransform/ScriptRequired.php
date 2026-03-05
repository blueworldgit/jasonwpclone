<?php

/**
 * Implements required attribute stipulation for <script>
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_AttrTransform_ScriptRequired extends WPLab_Amazon_HTMLPurifier_AttrTransform
{
    /**
     * @param array $attr
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (!isset($attr['type'])) {
            $attr['type'] = 'text/javascript';
        }
        return $attr;
    }
}

// vim: et sw=4 sts=4
