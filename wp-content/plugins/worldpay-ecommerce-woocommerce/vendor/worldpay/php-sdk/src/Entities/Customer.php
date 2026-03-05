<?php

namespace Worldpay\Api\Entities;

class Customer
{
    /**
     * @var string Customer's first name.
     */
    public string $firstName;

    /**
     * @var string Customer's last name.
     */
    public string $lastName;

    /**
     * @var string Customer's phone number.
     */
    public string $phoneNumber;

    /**
     * @var string The customer's email address.
     */
    public string $email;
}
