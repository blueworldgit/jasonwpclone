<?php

namespace Worldpay\Api\ValueObjects\PaymentMethods;

use Worldpay\Api\Enums\PaymentInstrumentType;
use Worldpay\Api\ValueObjects\PaymentInstrument;

class Wallet extends PaymentInstrument
{
	/**
	 * @var string
	 */
	public string $method;

	/**
	 * @var string
	 */
	public string $type = PaymentInstrumentType::ENCRYPTED;

	/**
	 * @var string
	 */
	public string $walletToken;

	/**
	 * @param  string  $method
	 */
	public function __construct(string $method) {
		$this->method = $method;
	}
}
