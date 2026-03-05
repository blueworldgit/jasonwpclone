<?php

// must be called POST validation

/**
 * Adds rel="noreferrer" to any links which target a different window
 * than the current one.  This is used to prevent malicious websites
 * from silently replacing the original window, which could be used
 * to do phishing.
 * This transform is controlled by %HTML.TargetNoreferrer.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_AttrTransform_TargetNoreferrer extends WPLab_Amazon_HTMLPurifier_AttrTransform
{
    /**
     * @param array $attr
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (isset($attr['rel'])) {
            $rels = explode(' ', $attr['rel']);
        } else {
            $rels = array();
        }
        if (isset($attr['target']) && !in_array('noreferrer', $rels)) {
            $rels[] = 'noreferrer';
        }
        if (!empty($rels) || isset($attr['rel'])) {
            $attr['rel'] = implode(' ', $rels);
        }

        return $attr;
    }
}

