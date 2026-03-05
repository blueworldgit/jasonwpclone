<?php

// It's not clear to me whether or not Punycode means that hostnames
// do not have canonical forms anymore. As far as I can tell, it's
// not a problem (punycoding should be identity when no Unicode
// points are involved), but I'm not 100% sure
class WPLab_Amazon_HTMLPurifier_URIFilter_HostBlacklist extends WPLab_Amazon_HTMLPurifier_URIFilter
{
    /**
     * @type string
     *
     * @license LGPL-2.1-or-later
     * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
     */
    public $name = 'HostBlacklist';

    /**
     * @type array
     */
    protected $blacklist = array();

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return bool
     */
    public function prepare($config)
    {
        $this->blacklist = $config->get('URI.HostBlacklist');
        return true;
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_URI $uri
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return bool
     */
    public function filter(&$uri, $config, $context)
    {
        foreach ($this->blacklist as $blacklisted_host_fragment) {
            if ($uri->host !== null && strpos($uri->host, $blacklisted_host_fragment) !== false) {
                return false;
            }
        }
        return true;
    }
}

// vim: et sw=4 sts=4
