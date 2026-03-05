<?php

namespace Worldpay\Api\Enums;

/**
 * Shipping valid methods.
 */
class ShippingMethod
{
    /**
     * Ship to customers billing address.
     */
    public const BILLING_ADDRESS = 'billingAddress';

    /**
     * Ship to another verified address on file with merchant.
     */
    public const VERIFIED_ADDRESS = 'verifiedAddress';

    /**
     * Ship to address that is different than billing address.
     */
    public const OTHER_ADDRESS = 'otherAddress';

    /**
     * Ship to store (store address should be populated on request).
     */
    public const STORE = 'store';

    /**
     * Digital goods.
     */
    public const DIGITAL = 'digital';

    /**
     * Travel and event tickets, not shipped.
     */
    public const UNSHIPPED_TICKETS = 'unshippedTickets';

    /**
     * Other.
     */
    public const OTHER = 'other';
}
