<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace WPLab\Amazon\ZipStream\Option;

use WPLab\Amazon\MyCLabs\Enum\Enum;

/**
 * Class Version
 * @package WPLab\Amazon\ZipStream\Option
 *
 * @method static STORE(): Version
 * @method static DEFLATE(): Version
 * @method static ZIP64(): Version
 * @psalm-immutable
 */
class Version extends Enum
{
    public const STORE = 0x000A; // 1.00

    public const DEFLATE = 0x0014; // 2.00

    public const ZIP64 = 0x002D; // 4.50
}
