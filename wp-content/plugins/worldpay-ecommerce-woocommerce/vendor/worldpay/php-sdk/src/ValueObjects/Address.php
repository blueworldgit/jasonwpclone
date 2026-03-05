<?php

namespace Worldpay\Api\ValueObjects;

abstract class Address
{
    /**
     * @var string
     */
    public string $address1;

    /**
     * @var string
     */
    public string $address2;

    /**
     * @var string
     */
    public string $address3;

    /**
     * @var string
     */
    public string $postalCode;

    /**
     * @var string
     */
    public string $city;

    /**
     * @var string For the United States or China.
     * (must be ISO-3611-2, only provide two characters after "US-" or "CN-", e.g. FL for Florida or BJ for Beijing).
     */
    public string $state;

    /**
     * @var string Must be a valid ISO3166-1 alpha-2 country code
     */
    public string $countryCode;
}
