<?php

namespace Worldpay\Api\Enums;

class PaymentOperation
{
	public const REFUND = 'refundPayment';

	public const PARTIAL_REFUND = 'partiallyRefundPayment';

	public const SETTLE = 'settlePayment';

	public const PARTIAL_SETTLE = 'partiallySettlePayment';

	public const CANCEL = 'cancelPayment';

	public const REVERSE = 'reversePayment';

	public const PARTIAL_REVERSE = 'partiallyReversePayment';
}
