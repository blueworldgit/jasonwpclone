<?php

/**
 * Validates http (HyperText Transfer Protocol) as defined by RFC 2616
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_URIScheme_http extends WPLab_Amazon_HTMLPurifier_URIScheme
{
    /**
     * @type int
     */
    public $default_port = 80;

    /**
     * @type bool
     */
    public $browsable = true;

    /**
     * @type bool
     */
    public $hierarchical = true;

    /**
     * @param WPLab_Amazon_HTMLPurifier_URI $uri
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return bool
     */
    public function doValidate(&$uri, $config, $context)
    {
        $uri->userinfo = null;
        return true;
    }
}

// vim: et sw=4 sts=4
