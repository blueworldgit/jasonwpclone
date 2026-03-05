<?php

namespace Worldpay\Api\ValueObjects\PaymentMethods;

use Worldpay\Api\Enums\PaymentMethod;
use Worldpay\Api\ValueObjects\PaymentInstrument;

class CreditCard extends PaymentInstrument
{
	/**
	 * @var string The method of instruction.
	 */
	public string $method = PaymentMethod::CARD;

	/**
	 * @var string Enum. See PaymentInstrumentType.
	 */
	public string $type;

	/**
	 * @var string A http address that contains your Checkout session providing the card details.
	 */
	public string $sessionHref;

	/**
	 * @var string Customer's card number.
	 */
	public string $cardNumber;

	/**
	 * @var int Customer's card expiry month.
	 */
	public int $cardExpiryMonth;

	/**
	 * @var int Customer's card expiry year.
	 */
	public int $cardExpiryYear;

	/**
	 * @var string Customer's CVC.
	 */
	public string $cvc;

	/**
	 * @var string Href to the Checkout session providing the Card Verification Code (CVC).
	 */
	public string $cvcSessionHref;

	/**
	 * @var string The name on your customer's card.
	 */
	public string $cardHolderName;

	/**
	 * @var string A http address that contains your link to an Access Token.
	 */
	public string $tokenHref;

	/**
	 * @param  string  $type
	 */
	public function __construct(string $type) {
		$this->type = $type;
	}
}
