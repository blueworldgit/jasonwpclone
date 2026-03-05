<?php

namespace Worldpay\Api\Enums;

/**
 * The processing arrangement agreed with customer for the transaction.
 */
class CustomerAgreementType
{
	/**
	 * The customer is actively participating in making a payment at the point of authorization
	 * using card details you have previously stored/ intend to store.
	 * Customer Initiated Transactions (CIT).
	 */
	public const CARD_ON_FILE = 'cardOnFile';

	/**
	 * For processing recurring payments.
	 * Merchant-Initiated Transactions (MIT).
	 */
	public const SUBSCRIPTION = 'subscription';

	public const INSTALLMENT = 'installment';

	public const UNSCHEDULED = 'unscheduled';
}
