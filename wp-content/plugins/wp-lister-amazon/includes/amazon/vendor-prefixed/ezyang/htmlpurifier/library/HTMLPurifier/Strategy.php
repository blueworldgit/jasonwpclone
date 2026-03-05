<?php

/**
 * Supertype for classes that define a strategy for modifying/purifying tokens.
 *
 * While WPLab_Amazon_HTMLPurifier's core purpose is fixing HTML into something proper,
 * strategies provide plug points for extra configuration or even extra
 * features, such as custom tags, custom parsing of text, etc.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */


abstract class WPLab_Amazon_HTMLPurifier_Strategy
{

    /**
     * Executes the strategy on the tokens.
     *
     * @param WPLab_Amazon_HTMLPurifier_Token[] $tokens Array of WPLab_Amazon_HTMLPurifier_Token objects to be operated on.
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return WPLab_Amazon_HTMLPurifier_Token[] Processed array of token objects.
     */
    abstract public function execute($tokens, $config, $context);
}

// vim: et sw=4 sts=4
