<?php

namespace Worldpay\Api\Builders\PaymentSession\Payload;

use Worldpay\Api\Builders\PaymentSession\PaymentSessionBuilder;
use Worldpay\Api\Exceptions\InvalidArgumentException;
use Worldpay\Api\Services\Validators\BaseValidator;
use Worldpay\Api\Services\Validators\PaymentSessionValidator;

class PaymentSessionPayloadBuilder
{
	/**
	 * @var PaymentSessionBuilder
	 */
	public PaymentSessionBuilder $paymentSessionBuilder;

	/**
	 * @param  PaymentSessionBuilder  $paymentSessionBuilder
	 */
	public function __construct(PaymentSessionBuilder $paymentSessionBuilder) {
		$this->paymentSessionBuilder = $paymentSessionBuilder;
	}

	/**
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function createPayload(): string {
		$payload = [];

		if (empty($this->paymentSessionBuilder->identity)) {
			throw new InvalidArgumentException('Identity is missing. This field is mandatory.');
		}
		$payload['identity'] = $this->paymentSessionBuilder->identity;
		if (empty($this->paymentSessionBuilder->cardExpiryMonth)) {
			throw new InvalidArgumentException('Card expiry month is missing. This field is mandatory.');
		}
		$payload['cardExpiryDate']['month'] = $this->paymentSessionBuilder->cardExpiryMonth;
		if (empty($this->paymentSessionBuilder->cardExpiryYear)) {
			throw new InvalidArgumentException('Card expiry year is missing. This field is mandatory.');
		}
		$payload['cardExpiryDate']['year'] = $this->paymentSessionBuilder->cardExpiryYear;
		if (empty($this->paymentSessionBuilder->cardNumber)) {
			throw new InvalidArgumentException('Card number is missing. This field is mandatory.');
		}
		$payload['cardNumber'] = $this->paymentSessionBuilder->cardNumber;
		if (!empty($this->paymentSessionBuilder->cardCvc)) {
			$payload['cvc'] = $this->paymentSessionBuilder->cardCvc;
		}

		return json_encode($payload);
	}
}
