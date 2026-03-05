<?php

/**
 * Validates a URI as defined by RFC 3986.
 * @note Scheme-specific mechanics deferred to WPLab_Amazon_HTMLPurifier_URIScheme
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_AttrDef_URI extends WPLab_Amazon_HTMLPurifier_AttrDef
{

    /**
     * @type WPLab_Amazon_HTMLPurifier_URIParser
     */
    protected $parser;

    /**
     * @type bool
     */
    protected $embedsResource;

    /**
     * @param bool $embeds_resource Does the URI here result in an extra HTTP request?
     */
    public function __construct($embeds_resource = false)
    {
        $this->parser = new WPLab_Amazon_HTMLPurifier_URIParser();
        $this->embedsResource = (bool)$embeds_resource;
    }

    /**
     * @param string $string
     * @return WPLab_Amazon_HTMLPurifier_AttrDef_URI
     */
    public function make($string)
    {
        $embeds = ($string === 'embedded');
        return new WPLab_Amazon_HTMLPurifier_AttrDef_URI($embeds);
    }

    /**
     * @param string $uri
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($uri, $config, $context)
    {
        if ($config->get('URI.Disable')) {
            return false;
        }

        $uri = $this->parseCDATA($uri);

        // parse the URI
        $uri = $this->parser->parse($uri);
        if ($uri === false) {
            return false;
        }

        // add embedded flag to context for validators
        $context->register('EmbeddedURI', $this->embedsResource);

        $ok = false;
        do {

            // generic validation
            $result = $uri->validate($config, $context);
            if (!$result) {
                break;
            }

            // chained filtering
            $uri_def = $config->getDefinition('URI');
            $result = $uri_def->filter($uri, $config, $context);
            if (!$result) {
                break;
            }

            // scheme-specific validation
            $scheme_obj = $uri->getSchemeObj($config, $context);
            if (!$scheme_obj) {
                break;
            }
            if ($this->embedsResource && !$scheme_obj->browsable) {
                break;
            }
            $result = $scheme_obj->validate($uri, $config, $context);
            if (!$result) {
                break;
            }

            // Post chained filtering
            $result = $uri_def->postFilter($uri, $config, $context);
            if (!$result) {
                break;
            }

            // survived gauntlet
            $ok = true;

        } while (false);

        $context->destroy('EmbeddedURI');
        if (!$ok) {
            return false;
        }
        // back to string
        return $uri->toString();
    }
}

// vim: et sw=4 sts=4
