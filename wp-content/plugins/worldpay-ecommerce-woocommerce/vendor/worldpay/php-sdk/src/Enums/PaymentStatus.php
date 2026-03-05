<?php

namespace Worldpay\Api\Enums;

class PaymentStatus
{
	public const AUTHORIZED = 'authorized';

	public const SENT_FOR_SETTLEMENT = 'sentForSettlement';

	public const SENT_FOR_CANCELLATION = 'sentForCancellation';

	public const FRAUD_HIGH_RISK = 'fraudHighRisk';

	public const THREE_DS_DEVICE_DATA_REQUIRED = '3dsDeviceDataRequired';

	public const THREE_DS_UNAVAILABLE = '3dsUnavailable';

	public const THREE_DS_AUTHENTICATION_FAILED = '3dsAuthenticationFailed';

	public const THREE_DS_CHALLENGED = '3dsChallenged';

	public const REFUSED = 'refused';

	public const SENT_FOR_REFUND = 'sentForRefund';

	public const SENT_FOR_PARTIAL_REFUND = 'sentForPartialRefund';
}
