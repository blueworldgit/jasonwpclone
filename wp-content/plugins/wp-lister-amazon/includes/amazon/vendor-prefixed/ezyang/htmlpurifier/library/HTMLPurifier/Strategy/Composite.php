<?php

/**
 * Composite strategy that runs multiple strategies on tokens.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
abstract class WPLab_Amazon_HTMLPurifier_Strategy_Composite extends WPLab_Amazon_HTMLPurifier_Strategy
{

    /**
     * List of strategies to run tokens through.
     * @type WPLab_Amazon_HTMLPurifier_Strategy[]
     */
    protected $strategies = array();

    /**
     * @param WPLab_Amazon_HTMLPurifier_Token[] $tokens
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return WPLab_Amazon_HTMLPurifier_Token[]
     */
    public function execute($tokens, $config, $context)
    {
        foreach ($this->strategies as $strategy) {
            $tokens = $strategy->execute($tokens, $config, $context);
        }
        return $tokens;
    }
}

// vim: et sw=4 sts=4
