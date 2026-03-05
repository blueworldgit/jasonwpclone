<?php

namespace Worldpay\Api\Entities;

class Event
{
    /**
     * @var string The unique identifier for the event.
     */
    public string $id;

    /**
     * @var string The transaction reference supplied in the payment.
     */
    public string $transactionReference;

    /**
     * @var string Event type.
     */
    public string $type;

    /**
     * @var string The unique reference provided for a partial settlement or partial refund.
     */
    public string $reference;

    /**
     * @var int The authorization, partial refund, or the whole or partial settlement amount.
     * This is a whole number with an exponent of 2 e.g. 250 would be 2.50.
     */
    public int $amount;

    /**
     * @var string The currency code.
     */
    public string $currency;

    /**
     * @param  string  $payload
     * @throws \JsonException
     */
    public function __construct(string $payload) {
        $values = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);

        $this->id = $values->eventId ?? '';
        $this->type = $values->eventDetails->type ?? '';
        $this->transactionReference = $values->eventDetails->transactionReference ?? '';
        $this->reference = $values->eventDetails->reference ?? '';
        $this->amount = $values->eventDetails->amount->value ?? 0;
        $this->currency = $values->eventDetails->amount->currencyCode ?? '';
    }
}
