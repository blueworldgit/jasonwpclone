<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_DefinitionCache_Decorator extends WPLab_Amazon_HTMLPurifier_DefinitionCache
{

    /**
     * Cache object we are decorating
     * @type WPLab_Amazon_HTMLPurifier_DefinitionCache
     */
    public $cache;

    /**
     * The name of the decorator
     * @var string
     */
    public $name;

    public function __construct()
    {
    }

    /**
     * Lazy decorator function
     * @param WPLab_Amazon_HTMLPurifier_DefinitionCache $cache Reference to cache object to decorate
     * @return WPLab_Amazon_HTMLPurifier_DefinitionCache_Decorator
     */
    public function decorate(&$cache)
    {
        $decorator = $this->copy();
        // reference is necessary for mocks in PHP 4
        $decorator->cache =& $cache;
        $decorator->type = $cache->type;
        return $decorator;
    }

    /**
     * Cross-compatible clone substitute
     * @return WPLab_Amazon_HTMLPurifier_DefinitionCache_Decorator
     */
    public function copy()
    {
        return new WPLab_Amazon_HTMLPurifier_DefinitionCache_Decorator();
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Definition $def
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return mixed
     */
    public function add($def, $config)
    {
        return $this->cache->add($def, $config);
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Definition $def
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return mixed
     */
    public function set($def, $config)
    {
        return $this->cache->set($def, $config);
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Definition $def
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return mixed
     */
    public function replace($def, $config)
    {
        return $this->cache->replace($def, $config);
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return mixed
     */
    public function get($config)
    {
        return $this->cache->get($config);
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return mixed
     */
    public function remove($config)
    {
        return $this->cache->remove($config);
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return mixed
     */
    public function flush($config)
    {
        return $this->cache->flush($config);
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @return mixed
     */
    public function cleanup($config)
    {
        return $this->cache->cleanup($config);
    }
}

// vim: et sw=4 sts=4
