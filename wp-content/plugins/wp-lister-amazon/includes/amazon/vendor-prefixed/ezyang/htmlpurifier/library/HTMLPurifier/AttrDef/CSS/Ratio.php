<?php

/**
 * Validates a ratio as defined by the CSS spec.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_AttrDef_CSS_Ratio extends WPLab_Amazon_HTMLPurifier_AttrDef
{
    /**
     * @param   string               $ratio   Ratio to validate
     * @param   WPLab_Amazon_HTMLPurifier_Config  $config  Configuration options
     * @param   WPLab_Amazon_HTMLPurifier_Context $context Context
     *
     * @return  string|boolean
     *
     * @warning Some contexts do not pass $config, $context. These
     *          variables should not be used without checking WPLab_Amazon_HTMLPurifier_Length
     */
    public function validate($ratio, $config, $context)
    {
        $ratio = $this->parseCDATA($ratio);

        $parts = explode('/', $ratio, 2);
        $length = count($parts);

        if ($length < 1 || $length > 2) {
            return false;
        }

        $num = new \HTMLPurifier_AttrDef_CSS_Number();

        if ($length === 1) {
            return $num->validate($parts[0], $config, $context);
        }

        $num1 = $num->validate($parts[0], $config, $context);
        $num2 = $num->validate($parts[1], $config, $context);

        if ($num1 === false || $num2 === false) {
            return false;
        }

        return $num1 . '/' . $num2;
    }
}

// vim: et sw=4 sts=4
