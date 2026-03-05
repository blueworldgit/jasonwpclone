<?php

/**
 * Null cache object to use when no caching is on.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_DefinitionCache_Null extends WPLab_Amazon_HTMLPurifier_DefinitionCache
{

    /**
     * @param WPLab_Amazon_HTMLPurifier_Definition $def
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return bool
     */
    public function add($def, $config)
    {
        return false;
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Definition $def
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return bool
     */
    public function set($def, $config)
    {
        return false;
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Definition $def
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return bool
     */
    public function replace($def, $config)
    {
        return false;
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return bool
     */
    public function remove($config)
    {
        return false;
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return bool
     */
    public function get($config)
    {
        return false;
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return bool
     */
    public function flush($config)
    {
        return false;
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return bool
     */
    public function cleanup($config)
    {
        return false;
    }
}

// vim: et sw=4 sts=4
