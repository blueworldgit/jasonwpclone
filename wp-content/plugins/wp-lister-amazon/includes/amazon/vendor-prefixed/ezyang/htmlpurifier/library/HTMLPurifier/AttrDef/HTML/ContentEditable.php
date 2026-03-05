<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_AttrDef_HTML_ContentEditable extends WPLab_Amazon_HTMLPurifier_AttrDef
{
    public function validate($string, $config, $context)
    {
        $allowed = array('false');
        if ($config->get('HTML.Trusted')) {
            $allowed = array('', 'true', 'false');
        }

        $enum = new WPLab_Amazon_HTMLPurifier_AttrDef_Enum($allowed);

        return $enum->validate($string, $config, $context);
    }
}
