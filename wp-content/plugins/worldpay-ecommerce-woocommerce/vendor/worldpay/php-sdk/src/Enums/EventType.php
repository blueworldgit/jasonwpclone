<?php

namespace Worldpay\Api\Enums;

class EventType
{
    /**
     * The API requested permission (from customer's card issuer) to process customer's payment.
     */
    public const SENT_FOR_AUTHORIZATION = 'sentForAuthorization';

    /**
     * The payment has been approved and the funds have been reserved in customer's account.
     */
    public const AUTHORIZED = 'authorized';

    /**
     * Merchant or Access Worldpay have requested to remove the reserved funds in customer's account and transfer them to merchant's Worldpay account.
     */
    public const SENT_FOR_SETTLEMENT = 'sentForSettlement';

    /**
     * Transaction stopped before it has been sent for settlement.
     */
    public const CANCELLED = 'cancelled';

    /**
     * The payment wasn't completed. The customer may want to reattempt the payment.
     */
    public const ERROR = 'error';

    /**
     * The authorization period ended before a settlement or cancel request was made.
     */
    public const EXPIRED = 'expired';

    /**
     * The payment request has been declined by a third party.
     */
    public const REFUSED = 'refused';

    /**
     * Funds were requested to be sent back to customer's account.
     */
    public const SENT_FOR_REFUND = 'sentForRefund';

    /**
     * The refund couldn't be processed and the funds were returned to merchant's account.
     */
    public const REFUND_FAILED = 'refundFailed';
}
