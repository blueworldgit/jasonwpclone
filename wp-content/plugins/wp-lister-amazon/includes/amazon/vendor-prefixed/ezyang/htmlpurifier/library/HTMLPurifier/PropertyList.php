<?php

/**
 * Generic property list implementation
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_PropertyList
{
    /**
     * Internal data-structure for properties.
     * @type array
     */
    protected $data = array();

    /**
     * Parent plist.
     * @type WPLab_Amazon_HTMLPurifier_PropertyList
     */
    protected $parent;

    /**
     * Cache.
     * @type array
     */
    protected $cache;

    /**
     * @param WPLab_Amazon_HTMLPurifier_PropertyList $parent Parent plist
     */
    public function __construct($parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Recursively retrieves the value for a key
     * @param string $name
     * @throws WPLab_Amazon_HTMLPurifier_Exception
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return $this->data[$name];
        }
        // possible performance bottleneck, convert to iterative if necessary
        if ($this->parent) {
            return $this->parent->get($name);
        }
        throw new WPLab_Amazon_HTMLPurifier_Exception("Key '$name' not found");
    }

    /**
     * Sets the value of a key, for this plist
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Returns true if a given key exists
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * Resets a value to the value of it's parent, usually the default. If
     * no value is specified, the entire plist is reset.
     * @param string $name
     */
    public function reset($name = null)
    {
        if ($name == null) {
            $this->data = array();
        } else {
            unset($this->data[$name]);
        }
    }

    /**
     * Squashes this property list and all of its property lists into a single
     * array, and returns the array. This value is cached by default.
     * @param bool $force If true, ignores the cache and regenerates the array.
     * @return array
     */
    public function squash($force = false)
    {
        if ($this->cache !== null && !$force) {
            return $this->cache;
        }
        if ($this->parent) {
            return $this->cache = array_merge($this->parent->squash($force), $this->data);
        } else {
            return $this->cache = $this->data;
        }
    }

    /**
     * Returns the parent plist.
     * @return WPLab_Amazon_HTMLPurifier_PropertyList
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent plist.
     * @param WPLab_Amazon_HTMLPurifier_PropertyList $plist Parent plist
     */
    public function setParent($plist)
    {
        $this->parent = $plist;
    }
}

// vim: et sw=4 sts=4
