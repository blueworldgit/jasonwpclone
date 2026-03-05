<?php

/**
 * @file
 * Defines a function wrapper for HTML Purifier for quick use.
 * @note ''WPLab_Amazon_HTMLPurifier()'' is NOT the same as ''new WPLab_Amazon_HTMLPurifier()''
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

/**
 * Purify HTML.
 * @param string $html String HTML to purify
 * @param mixed $config Configuration to use, can be any value accepted by
 *        WPLab_Amazon_HTMLPurifier_Config::create()
 * @return string
 */
function WPLab_Amazon_HTMLPurifier($html, $config = null)
{
    static $purifier = false;
    if (!$purifier) {
        $purifier = new WPLab_Amazon_HTMLPurifier();
    }
    return $purifier->purify($html, $config);
}

// vim: et sw=4 sts=4
