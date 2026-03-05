<?php

namespace Worldpay\Api\Enums;

/**
 * Environment valid values.
 */
class Environment
{
    /**
     * For processing try/test transactions.
     */
    public const TRY_MODE = 'TEST';

    /**
     * For processing live/production transactions.
     */
    public const LIVE_MODE = 'PRODUCTION';
}
