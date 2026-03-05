<?php

/**
 * Generic schema interchange format that can be converted to a runtime
 * representation (WPLab_Amazon_HTMLPurifier_ConfigSchema) or HTML documentation. Members
 * are completely validated.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_ConfigSchema_Interchange
{

    /**
     * Name of the application this schema is describing.
     * @type string
     */
    public $name;

    /**
     * Array of Directive ID => array(directive info)
     * @type WPLab_Amazon_HTMLPurifier_ConfigSchema_Interchange_Directive[]
     */
    public $directives = array();

    /**
     * Adds a directive array to $directives
     * @param WPLab_Amazon_HTMLPurifier_ConfigSchema_Interchange_Directive $directive
     * @throws WPLab_Amazon_HTMLPurifier_ConfigSchema_Exception
     */
    public function addDirective($directive)
    {
        if (isset($this->directives[$i = $directive->id->toString()])) {
            throw new WPLab_Amazon_HTMLPurifier_ConfigSchema_Exception("Cannot redefine directive '$i'");
        }
        $this->directives[$i] = $directive;
    }

    /**
     * Convenience function to perform standard validation. Throws exception
     * on failed validation.
     */
    public function validate()
    {
        $validator = new WPLab_Amazon_HTMLPurifier_ConfigSchema_Validator();
        return $validator->validate($this);
    }
}

// vim: et sw=4 sts=4
