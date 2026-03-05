<?php

namespace Worldpay\Api\ValueObjects;

/**
 * Contains the different URLs we redirect your customers to when we receive the payment result.
 */
class ResultURLs
{
    /**
     * @var string Customer redirect URL for a successful payment.
     */
    public string $successURL;

    /**
     * @var string Customer redirect URL for a pending payment transaction.
     */
    public string $pendingURL;

    /**
     * @var string Customer redirect URL for a failed payment.
     */
    public string $failureURL;

    /**
     * @var string Customer redirect URL for an erroneous payment.
     */
    public string $errorURL;

    /**
     * @var string Customer redirect URL for when your customer cancels a transaction.
     */
    public string $cancelURL;

    /**
     * @var string Customer redirect URL for when a customer leaves the payment transaction uncompleted within the maximum allowed time frame
     */
    public string $expiryURL;
}
