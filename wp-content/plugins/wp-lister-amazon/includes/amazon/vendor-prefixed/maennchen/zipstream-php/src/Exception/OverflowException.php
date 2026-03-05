<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace WPLab\Amazon\ZipStream\Exception;

use WPLab\Amazon\ZipStream\Exception;

/**
 * This Exception gets invoked if a counter value exceeds storage size
 */
class OverflowException extends Exception
{
    public function __construct()
    {
        parent::__construct('File size exceeds limit of 32 bit integer. Please enable "zip64" option.');
    }
}
