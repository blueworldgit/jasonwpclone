<?php

namespace Worldpay\Api\Enums;

class PaymentInstrumentType
{
	/**
	 * Card - instrument type plain
	 */
	public const PLAIN = 'plain';

	/**
	 * Card - instrument type token (stored card)
	 */
	public const TOKEN = 'token';

	/**
	 * Card - instrument type checkout (checkout sdk)
	 */
	public const CHECKOUT = 'checkout';

	/**
	 * Card - instrument type networkToken
	 */
	public const NETWORK_TOKEN = 'networkToken';

	/**
	 * Digital wallet - instrument type encrypted
	 */
	public const ENCRYPTED = 'encrypted';

	/**
	 * Tokens - instrument type
	 */
	public const TOKENS_CARD_FRONT = 'card/front';

	public const TOKENS_CARD_TOKENIZED = 'card/tokenized';
}
