<?php

namespace Worldpay\Api\Entities;

use Worldpay\Api\ValueObjects\BillingAddress;
use Worldpay\Api\ValueObjects\ShippingAddress;

class Order
{
    /**
     * @var string Order id.
     */
    public string $id;

    /**
     * @var int The payment amount. This is a whole number with an exponent.
     */
    public int $amount;

    /**
     * @var string The 3 character ISO currency code.
     */
    public string $currency;

    /**
     * @var Customer information.
     */
    public Customer $customer;

    /**
     * @var BillingAddress Billing address information.
     */
    public BillingAddress $billingAddress;

    /**
     * @var ShippingAddress Shipping address information.
     */
    public ShippingAddress $shippingAddress;
}
