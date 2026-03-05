<?php

namespace Worldpay\Api\Builders\Tokens\Payload;

use Worldpay\Api\Builders\Tokens\PaymentTokenBuilder;
use Worldpay\Api\Enums\PaymentInstrumentType;
use Worldpay\Api\Exceptions\InvalidArgumentException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\ValueObjects\BillingAddress;

class PaymentTokenPayloadBuilder
{
	/**
	 * @var PaymentTokenBuilder
	 */
	public PaymentTokenBuilder $paymentTokenBuilder;

	/**
	 * @param  PaymentTokenBuilder  $paymentTokenBuilder
	 */
	public function __construct(PaymentTokenBuilder $paymentTokenBuilder) {
		$this->paymentTokenBuilder = $paymentTokenBuilder;
	}

	/**
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function createTokenPayload(): string {
		$payload = [];
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();
		if (empty($apiConfigProvider->merchantEntity)) {
			throw new InvalidArgumentException('Merchant entity is missing. This field is mandatory.');
		}
		$payload['merchant']['entity'] = $apiConfigProvider->merchantEntity;

		if (empty($this->paymentTokenBuilder->card)) {
			throw new InvalidArgumentException('Payment instrument is missing. This field is mandatory.');
		}
		$payload['paymentInstrument']['type'] = PaymentInstrumentType::TOKENS_CARD_FRONT;
		$payload['paymentInstrument']['cardHolderName'] = $this->paymentTokenBuilder->card->cardHolderName ?? '';
		$payload['paymentInstrument']['cardExpiryDate']['month'] = $this->paymentTokenBuilder->card->cardExpiryMonth ?? '';
		$payload['paymentInstrument']['cardExpiryDate']['year'] = $this->paymentTokenBuilder->card->cardExpiryYear ?? '';
		$payload['paymentInstrument']['cardNumber'] = $this->paymentTokenBuilder->card->cardNumber ?? '';

		if (isset($this->paymentTokenBuilder->billingAddress) && $this->paymentTokenBuilder->billingAddress instanceof BillingAddress) {
			$payload['paymentInstrument']['billingAddress'] = $this->paymentTokenBuilder->billingAddress;
		}

		if (!empty($this->paymentTokenBuilder->description)) {
			$payload['description'] = $this->paymentTokenBuilder->description;
		}
		if (!empty($this->paymentTokenBuilder->expiryDateTime)) {
			$payload['tokenExpiryDateTime'] = $this->paymentTokenBuilder->expiryDateTime;
		}
		if (!empty($this->paymentTokenBuilder->namespace)) {
			$payload['namespace'] = $this->paymentTokenBuilder->namespace;
		}
		if (!empty($this->paymentTokenBuilder->schemeTransactionReference)) {
			$payload['schemeTransactionReference'] = $this->paymentTokenBuilder->schemeTransactionReference;
		}

		return json_encode($payload);
	}

	/**
	 * @return string
	 */
	public function updateTokenPayload(): string {
		$payload = [];

		return json_encode($payload);
	}
}
