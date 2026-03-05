<?php

namespace Worldpay\Api\Enums;

/**
 * Token option valid values (for HPP).
 */
class TokenOption
{
	/**
	 * The card details are always saved (you must already have customer consent). Default.
	 */
	public const SILENT = 'SILENT';

	/**
	 * The card details are always saved (you must already have customer consent). Customer will see this within HPP.
	 */
	public const NOTIFY = 'NOTIFY';

	/**
	 * The card details are saved if customer provides their consent.
	 * Adds a "Save payment details" tickbox to the page, which they tick to opt-in, or ignore to opt-out.
	 */
	public const ASK = 'ASK';
}
