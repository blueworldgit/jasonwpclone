<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation;

class ExceptionHandler
{
    /**
     * Register errorhandler.
     */
    public function __construct()
    {
        /** @var callable */
        $callable = [Exception::class, 'errorHandlerCallback'];
        set_error_handler($callable, E_ALL);
    }

    /**
     * Unregister errorhandler.
     */
    public function __destruct()
    {
        restore_error_handler();
    }
}
