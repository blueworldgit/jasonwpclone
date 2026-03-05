<?php

namespace Worldpay\Api\Builders\PaymentProcessing\Payload;

use Worldpay\Api\Builders\PaymentProcessing\PaymentProcessingBuilder;
use Worldpay\Api\Entities\Customer;
use Worldpay\Api\Exceptions\InvalidArgumentException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\ValueObjects\BillingAddress;
use Worldpay\Api\ValueObjects\ResultURLs;
use Worldpay\Api\ValueObjects\ShippingAddress;

class HPPApiPayloadBuilder
{
	/**
	 * @var PaymentProcessingBuilder
	 */
	public PaymentProcessingBuilder $paymentProcessingBuilder;

	/**
	 * @param  PaymentProcessingBuilder  $paymentProcessingBuilder
	 */
	public function __construct(PaymentProcessingBuilder $paymentProcessingBuilder) {
		$this->paymentProcessingBuilder = $paymentProcessingBuilder;
	}

	/**
	 * @return false|string
	 * @throws InvalidArgumentException
	 */
	public function createPayload() {
		$payload = [];

		if (empty($this->paymentProcessingBuilder->transactionReference)) {
			throw new InvalidArgumentException('Transaction reference is missing. This field is mandatory.');
		}
		$payload['transactionReference'] = $this->paymentProcessingBuilder->transactionReference;

		$apiConfigProvider = AccessWorldpayConfigProvider::instance();
		if (empty($apiConfigProvider->merchantEntity)) {
			throw new InvalidArgumentException('Invalid merchant entity. This field is mandatory.');
		}
		$payload['merchant']['entity'] = $apiConfigProvider->merchantEntity;

		if (empty($apiConfigProvider->merchantNarrative)) {
			throw new InvalidArgumentException('Invalid merchant narrative. This field is mandatory.');
		}
		$payload['narrative']['line1'] = $apiConfigProvider->merchantNarrative;

		if (!empty($this->paymentProcessingBuilder->description)) {
			$payload['description'] = $this->paymentProcessingBuilder->description;
		}

		if (isset($this->paymentProcessingBuilder->billingAddress) && $this->paymentProcessingBuilder->billingAddress instanceof BillingAddress) {
			$payload['billingAddress'] = $this->paymentProcessingBuilder->billingAddress;
		}

		$payload['value']['currency'] = $this->paymentProcessingBuilder->currency;
		$payload['value']['amount'] = $this->paymentProcessingBuilder->amount;

		if (isset($this->paymentProcessingBuilder->resultURLs) && $this->paymentProcessingBuilder->resultURLs instanceof ResultURLs) {
			$payload['resultURLs'] = $this->paymentProcessingBuilder->resultURLs;
		}

		if (isset($this->paymentProcessingBuilder->shippingAddress) && $this->paymentProcessingBuilder->shippingAddress instanceof ShippingAddress) {
			$payload['riskData']['shipping']['address'] = $this->paymentProcessingBuilder->shippingAddress;
		}
		if (!empty($this->paymentProcessingBuilder->shippingEmail)) {
			$payload['riskData']['shipping']['email'] = $this->paymentProcessingBuilder->shippingEmail;
		}
		if (!empty($this->paymentProcessingBuilder->shippingMethod)) {
			$payload['riskData']['shipping']['method'] = $this->paymentProcessingBuilder->shippingMethod;
		}
		if (isset($this->paymentProcessingBuilder->customer) && $this->paymentProcessingBuilder->customer instanceof Customer) {
			if (!empty($this->paymentProcessingBuilder->customer->email)) {
				$payload['riskData']['account']['email'] = $this->paymentProcessingBuilder->customer->email;
			}
			if (!empty($this->paymentProcessingBuilder->customer->firstName) && !empty($this->paymentProcessingBuilder->customer->lastName)) {
				$payload['riskData']['transaction']['firstName'] = $this->paymentProcessingBuilder->customer->firstName;
				$payload['riskData']['transaction']['lastName'] = $this->paymentProcessingBuilder->customer->lastName;
			}
			if (!empty($this->paymentProcessingBuilder->customer->phoneNumber)) {
				$payload['riskData']['transaction']['phoneNumber'] = $this->paymentProcessingBuilder->customer->phoneNumber;
			}
		}

		if (!empty($this->paymentProcessingBuilder->expiry)) {
			$payload['expiry'] = $this->paymentProcessingBuilder->expiry;
		}

		if ($this->paymentProcessingBuilder->tokenCreation === true) {
			$payload['createToken']['type'] = $this->paymentProcessingBuilder->tokenType;
			if (!empty($this->paymentProcessingBuilder->tokenNamespace)) {
				$payload['createToken']['namespace'] = $this->paymentProcessingBuilder->tokenNamespace;
			}
			if (!empty($this->paymentProcessingBuilder->tokenDescription)) {
				$payload['createToken']['description'] = $this->paymentProcessingBuilder->tokenDescription;
			}
			if (!empty($this->paymentProcessingBuilder->tokenOption)) {
				$payload['createToken']['optIn'] = $this->paymentProcessingBuilder->tokenOption;
			}
		}

		if (isset($this->paymentProcessingBuilder->customerAgreementType)) {
			$payload['customerAgreement']['type'] = $this->paymentProcessingBuilder->customerAgreementType;
		}
		if (isset($this->paymentProcessingBuilder->storedCardUsage)) {
			$payload['customerAgreement']['storedCardUsage'] = $this->paymentProcessingBuilder->storedCardUsage;
		}

		if (isset($this->paymentProcessingBuilder->paymentInstrument)) {
			$payload['paymentInstrument']['type'] = $this->paymentProcessingBuilder->paymentInstrument->type ?? '';
			$payload['paymentInstrument']['href'] = $this->paymentProcessingBuilder->paymentInstrument->tokenHref ?? '';
		}

		/**
		 * Settlement
		 */
		if (isset($this->paymentProcessingBuilder->autoSettlement)) {
			$payload['settlement']['auto'] = $this->paymentProcessingBuilder->autoSettlement;
		}
		if (!empty($this->paymentProcessingBuilder->cancelOnCVCNotMatched)) {
			$payload['settlement']['cancelOn']['cvcNotMatched'] = $this->paymentProcessingBuilder->cancelOnCVCNotMatched;
		}

		return json_encode($payload);
	}
}
