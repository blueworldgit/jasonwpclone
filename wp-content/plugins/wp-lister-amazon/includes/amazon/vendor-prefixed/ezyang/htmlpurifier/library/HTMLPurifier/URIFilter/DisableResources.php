<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_URIFilter_DisableResources extends WPLab_Amazon_HTMLPurifier_URIFilter
{
    /**
     * @type string
     */
    public $name = 'DisableResources';

    /**
     * @param WPLab_Amazon_HTMLPurifier_URI $uri
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return bool
     */
    public function filter(&$uri, $config, $context)
    {
        return !$context->get('EmbeddedURI', true);
    }
}

// vim: et sw=4 sts=4
