<?php

namespace Worldpay\Api\Enums;

class PaymentEvent
{
	public const AUTHORIZATION_REQUESTED = 'authorizationRequested';

	public const AUTHORIZATION_SUCCEEDED = 'authorizationSucceeded';

	public const AUTHORIZATION_REFUSED = 'authorizationRefused';

	public const AUTHORIZATION_FAILED = 'authorizationFailed';

	public const AUTHORIZATION_TIMED_OUT = 'authorizationTimedOut';

	public const SALE_REQUESTED = 'saleRequested';

	public const SALE_SUCCEEDED = 'saleSucceeded';

	public const SALE_REFUSED = 'saleRefused';

	public const SALE_FAILED = 'saleFailed';

	public const SALE_TIMED_OUT = 'saleTimedOut';

	public const CANCELLATION_REQUESTED = 'cancellationRequested';

	public const CANCELLATION_REQUEST_SUBMITTED = 'cancellationRequestSubmitted';

	public const CANCELLATION_REQUEST_SUBMISSION_FAILED = 'cancellationRequestSubmissionFailed';

	public const CANCELLATION_REQUEST_SUBMISSION_TIMED_OUT = 'cancellationRequestSubmissionTimedOut';

	public const SETTLEMENT_REQUESTED = 'settlementRequested';

	public const SETTLEMENT_REQUEST_SUBMITTED = 'settlementRequestSubmitted';

	public const SETTLEMENT_REQUEST_SUBMISSION_FAILED = 'settlementRequestSubmissionFailed';

	public const SETTLEMENT_REQUEST_SUBMISSION_TIMED_OUT = 'settlementRequestSubmissionTimedOut';

	public const REFUND_REQUESTED = 'refundRequested';

	public const REFUND_REQUEST_SUBMITTED = 'refundRequestSubmitted';

	public const REFUND_REQUEST_SUBMISSION_FAILED = 'refundRequestSubmissionFailed';

	public const REFUND_REQUEST_SUBMISSION_TIMED_OUT = 'refundRequestSubmissionTimedOut';

	public const REVERSAL_REQUESTED = 'reversalRequested';

	public const REVERSAL_REQUEST_SUBMITTED = 'reversalRequestSubmitted';
}
