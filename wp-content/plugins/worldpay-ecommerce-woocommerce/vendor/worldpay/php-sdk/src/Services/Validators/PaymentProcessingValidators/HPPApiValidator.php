<?php

namespace Worldpay\Api\Services\Validators\PaymentProcessingValidators;

use Worldpay\Api\Services\Validators\PaymentProcessingValidator;

class HPPApiValidator extends PaymentProcessingValidator
{
    /**
     * riskData.transaction.phoneNumber minimum size.
     */
    public const CUSTOMER_PHONE_NUMBER_MIN_SIZE = 4;

    /**
     * riskData.transaction.phoneNumber maximum size.
     */
    public const CUSTOMER_PHONE_NUMBER_MAX_SIZE = 20;

    /**
     * riskData.transaction.firstName minimum size.
     */
    public const CUSTOMER_FIRST_NAME_MIN_SIZE = 1;

    /**
     * riskData.transaction.firstName maximum size.
     */
    public const CUSTOMER_FIRST_NAME_MAX_SIZE = 22;

    /**
     * riskData.transaction.lastName minimum size.
     */
    public const CUSTOMER_LAST_NAME_MIN_SIZE = 1;

    /**
     * riskData.transaction.lastName maximum size.
     */
    public const CUSTOMER_LAST_NAME_MAX_SIZE = 22;

    /**
     * riskData.account.email minimum size.
     */
    public const CUSTOMER_EMAIL_MIN_SIZE = 3;

    /**
     * riskData.account.email maximum size.
     */
    public const CUSTOMER_EMAIL_MAX_SIZE = 128;

    /**
     * billingAddress.address1 minimum size.
     */
    public const BILLING_ADDRESS1_MIN_SIZE = 1;

    /**
     * billingAddress.address1 maximum size.
     */
    public const BILLING_ADDRESS1_MAX_SIZE = 85;

    /**
     * billingAddress.address2 minimum size.
     */
    public const BILLING_ADDRESS2_MIN_SIZE = 0;

    /**
     * billingAddress.address2 maximum size.
     */
    public const BILLING_ADDRESS2_MAX_SIZE = 85;

    /**
     * billingAddress.address3 minimum size.
     */
    public const BILLING_ADDRESS3_MIN_SIZE = 0;

    /**
     * billingAddress.address3 maximum size.
     */
    public const BILLING_ADDRESS3_MAX_SIZE = 85;

    /**
     * billingAddress.postalCode minimum size.
     */
    public const BILLING_POSTALCODE_MIN_SIZE = 1;

    /**
     * billingAddress.postalCode maximum size.
     */
    public const BILLING_POSTALCODE_MAX_SIZE = 15;

    /**
     * billingAddress.city minimum size.
     */
    public const BILLING_CITY_MIN_SIZE = 1;

    /**
     * billingAddress.city maximum size.
     */
    public const BILLING_CITY_MAX_SIZE = 50;

    /**
     * billingAddress.state minimum size.
     */
    public const BILLING_STATE_MIN_SIZE = 0;

    /**
     * billingAddress.state maximum size.
     */
    public const BILLING_STATE_MAX_SIZE = 50;

    /**
     * billingAddress.postalCode minimum size.
     */
    public const BILLING_COUNTRYCODE_MIN_SIZE = 2;

    /**
     * billingAddress.postalCode maximum size.
     */
    public const BILLING_COUNTRYCODE_MAX_SIZE = 2;

    /**
     * shipping.address1 minimum size.
     */
    public const SHIPPING_ADDRESS1_MIN_SIZE = 1;

    /**
     * shipping.address1 maximum size.
     */
    public const SHIPPING_ADDRESS1_MAX_SIZE = 80;

    /**
     * shipping.address2 minimum size.
     */
    public const SHIPPING_ADDRESS2_MIN_SIZE = 0;

    /**
     * shipping.address2 maximum size.
     */
    public const SHIPPING_ADDRESS2_MAX_SIZE = 80;

    /**
     * shipping.address3 minimum size.
     */
    public const SHIPPING_ADDRESS3_MIN_SIZE = 0;

    /**
     * shipping.address3 maximum size.
     */
    public const SHIPPING_ADDRESS3_MAX_SIZE = 80;

    /**
     * shipping.postalCode minimum size.
     */
    public const SHIPPING_POSTALCODE_MIN_SIZE = 1;

    /**
     * shipping.postalCode maximum size.
     */
    public const SHIPPING_POSTALCODE_MAX_SIZE = 15;

    /**
     * shipping.city minimum size.
     */
    public const SHIPPING_CITY_MIN_SIZE = 1;

    /**
     * shipping.city maximum size.
     */
    public const SHIPPING_CITY_MAX_SIZE = 50;

    /**
     * shipping.state minimum size.
     */
    public const SHIPPING_STATE_MIN_SIZE = 1;

    /**
     * shipping.state maximum size.
     */
    public const SHIPPING_STATE_MAX_SIZE = 30;
}
