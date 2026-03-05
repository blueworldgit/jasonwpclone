<?php

/**
 * Core strategy composed of the big four strategies.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_Strategy_Core extends WPLab_Amazon_HTMLPurifier_Strategy_Composite
{
    public function __construct()
    {
        $this->strategies[] = new WPLab_Amazon_HTMLPurifier_Strategy_RemoveForeignElements();
        $this->strategies[] = new WPLab_Amazon_HTMLPurifier_Strategy_MakeWellFormed();
        $this->strategies[] = new WPLab_Amazon_HTMLPurifier_Strategy_FixNesting();
        $this->strategies[] = new WPLab_Amazon_HTMLPurifier_Strategy_ValidateAttributes();
    }
}

// vim: et sw=4 sts=4
