<?php

namespace Worldpay\Api\Builders\PaymentProcessing\Payload;

use Worldpay\Api\ValueObjects\ShippingAddress;
use Worldpay\Api\Builders\PaymentProcessing\PaymentProcessingBuilder;
use Worldpay\Api\Entities\Customer;
use Worldpay\Api\Enums\PaymentInstrumentType;
use Worldpay\Api\Enums\PaymentMethod;
use Worldpay\Api\Exceptions\InvalidArgumentException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\ValueObjects\BillingAddress;
use Worldpay\Api\ValueObjects\PaymentInstrument;
use Worldpay\Api\ValueObjects\ThreeDS;

class PaymentsApiPayloadBuilder
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
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function createPayload(): string {
		/**
		 * Transaction reference
		 */
		if (empty($this->paymentProcessingBuilder->transactionReference)) {
			throw new InvalidArgumentException('Transaction reference is missing. This field is mandatory.');
		}
		$payload['transactionReference'] = $this->paymentProcessingBuilder->transactionReference;

		/**
		 * Merchant
		 */
		$apiConfigProvider = AccessWorldpayConfigProvider::instance();
		if (empty($apiConfigProvider->merchantEntity)) {
			throw new InvalidArgumentException('Invalid merchant entity. This field is mandatory.');
		}
		$payload['merchant']['entity'] = $apiConfigProvider->merchantEntity;

		/**
		 * Instruction
		 */
		if (empty($this->paymentProcessingBuilder->paymentInstrument) || !$this->paymentProcessingBuilder->paymentInstrument instanceof PaymentInstrument) {
			throw new InvalidArgumentException('Invalid payment instrument.');
		}

		/**
		 * Instruction Method
		 */
		$payload['instruction']['method'] = $this->paymentProcessingBuilder->paymentInstrument->method;

		/**
		 * Instruction Payment Instrument
		 */
		if ($this->paymentProcessingBuilder->paymentInstrument->method === PaymentMethod::CARD) {
			$payload['instruction']['paymentInstrument']['type'] = $this->paymentProcessingBuilder->paymentInstrument->type;
			switch($this->paymentProcessingBuilder->paymentInstrument->type) {
				case PaymentInstrumentType::PLAIN:
					$payload['instruction']['paymentInstrument']['cardNumber'] = $this->paymentProcessingBuilder->paymentInstrument->cardNumber ?? '';
					$payload['instruction']['paymentInstrument']['expiryDate']['year'] = $this->paymentProcessingBuilder->paymentInstrument->cardExpiryYear ?? null;
					$payload['instruction']['paymentInstrument']['expiryDate']['month'] = $this->paymentProcessingBuilder->paymentInstrument->cardExpiryMonth ?? null;
					if (!empty($this->paymentProcessingBuilder->paymentInstrument->cvc)) {
						$payload['instruction']['paymentInstrument']['cvc'] = $this->paymentProcessingBuilder->paymentInstrument->cvc;
					}
					break;
				case PaymentInstrumentType::TOKEN:
					$payload['instruction']['paymentInstrument']['href'] = $this->paymentProcessingBuilder->paymentInstrument->tokenHref ?? '';
					if (!empty($this->paymentProcessingBuilder->paymentInstrument->cvc)) {
						$payload['instruction']['paymentInstrument']['cvc'] = $this->paymentProcessingBuilder->paymentInstrument->cvc;
					}
					if (!empty($this->paymentProcessingBuilder->paymentInstrument->cvcSessionHref)) {
						$payload['instruction']['paymentInstrument']['cvcSessionHref'] = $this->paymentProcessingBuilder->paymentInstrument->cvcSessionHref;
					}
					break;
				case PaymentInstrumentType::CHECKOUT:
					$payload['instruction']['paymentInstrument']['sessionHref'] = $this->paymentProcessingBuilder->paymentInstrument->sessionHref ?? '';
			}
			if (!empty($this->paymentProcessingBuilder->paymentInstrument->cardHolderName)) {
				if (in_array(
					$this->paymentProcessingBuilder->paymentInstrument->type, [
						PaymentInstrumentType::PLAIN,
						PaymentInstrumentType::CHECKOUT,
						PaymentInstrumentType::NETWORK_TOKEN
					])) {
					$payload['instruction']['paymentInstrument']['cardHolderName'] = $this->paymentProcessingBuilder->paymentInstrument->cardHolderName;
				}
			}
			if (isset($this->paymentProcessingBuilder->billingAddress) && $this->paymentProcessingBuilder->billingAddress instanceof BillingAddress) {
				if (in_array(
					$this->paymentProcessingBuilder->paymentInstrument->type, [
						PaymentInstrumentType::PLAIN,
						PaymentInstrumentType::CHECKOUT,
						PaymentInstrumentType::NETWORK_TOKEN])) {
					$payload['instruction']['paymentInstrument']['billingAddress'] = $this->paymentProcessingBuilder->billingAddress;
				}
			}
			if (!empty($this->paymentProcessingBuilder->fraudType)) {
				$payload['instruction']['fraud']['type'] = $this->paymentProcessingBuilder->fraudType;
			}
		}

		/**
		 * Instruction Narrative
		 */
		if (empty($apiConfigProvider->merchantNarrative)) {
			throw new InvalidArgumentException('Invalid merchant narrative.');
		}
		$payload['instruction']['narrative']['line1'] = $apiConfigProvider->merchantNarrative;

		/**
		 * Instruction Token Creation
		 */
		if ($this->paymentProcessingBuilder->tokenCreation === true) {
			$payload['instruction']['tokenCreation']['type'] = $this->paymentProcessingBuilder->tokenType;
			if (!empty($this->paymentProcessingBuilder->tokenNamespace)) {
				$payload['instruction']['tokenCreation']['namespace'] = $this->paymentProcessingBuilder->tokenNamespace;
			}
			if (!empty($this->paymentProcessingBuilder->tokenDescription)) {
				$payload['instruction']['tokenCreation']['description'] = $this->paymentProcessingBuilder->tokenDescription;
			}
		}

		/**
		 * Instruction Customer Agreement
		 */
		if (isset($this->paymentProcessingBuilder->customerAgreementType)) {
			$payload['instruction']['customerAgreement']['type'] = $this->paymentProcessingBuilder->customerAgreementType;
		}
		if (isset($this->paymentProcessingBuilder->storedCardUsage)) {
			$payload['instruction']['customerAgreement']['storedCardUsage'] = $this->paymentProcessingBuilder->storedCardUsage;
		}
		if (isset($this->paymentProcessingBuilder->schemeReference)) {
			$payload['instruction']['customerAgreement']['schemeReference'] = $this->paymentProcessingBuilder->schemeReference;
		}

		/**
		 * Instruction Value (Currency/Amount)
		 */
		if (empty($this->paymentProcessingBuilder->currency)) {
			throw new InvalidArgumentException('Invalid currency.');
		}
		$payload['instruction']['value']['currency'] = $this->paymentProcessingBuilder->currency;
		if (!isset($this->paymentProcessingBuilder->amount)) {
			throw new InvalidArgumentException('Invalid amount.');
		}
		$payload['instruction']['value']['amount'] = $this->paymentProcessingBuilder->amount;

		/**
		 * Instruction Settlement
		 */
		if (isset($this->paymentProcessingBuilder->autoSettlement)) {
			$payload['instruction']['settlement']['auto'] = $this->paymentProcessingBuilder->autoSettlement;
		}
		if (!empty($this->paymentProcessingBuilder->cancelOnCVCNotMatched)) {
			$payload['instruction']['settlement']['cancelOn']['cvcNotMatched'] = $this->paymentProcessingBuilder->cancelOnCVCNotMatched;
		}
		if (!empty($this->paymentProcessingBuilder->cancelOnAVSNotMatched)) {
			$payload['instruction']['settlement']['cancelOn']['avsNotMatched'] = $this->paymentProcessingBuilder->cancelOnAVSNotMatched;
		}

		/**
		 * Instruction Three DS
		 */
		if (!empty($this->paymentProcessingBuilder->threeDS) && $this->paymentProcessingBuilder->threeDS instanceof ThreeDS) {
			$payload['instruction']['threeDS']['type'] = $this->paymentProcessingBuilder->threeDS->type;
			$payload['instruction']['threeDS']['mode'] = $this->paymentProcessingBuilder->threeDS->mode;
			$payload['instruction']['threeDS']['deviceData']['acceptHeader'] = $this->paymentProcessingBuilder->threeDS->deviceDataAcceptHeader;
			if (!empty($this->paymentProcessingBuilder->threeDS->deviceDataAgentHeader)) {
				$payload['instruction']['threeDS']['deviceData']['userAgentHeader'] = $this->paymentProcessingBuilder->threeDS->deviceDataAgentHeader;
			}
			if (!empty($this->paymentProcessingBuilder->threeDS->challengeReturnUrl)) {
				$payload['instruction']['threeDS']['challenge']['returnUrl'] = $this->paymentProcessingBuilder->threeDS->challengeReturnUrl;
			}
			if (!empty($this->paymentProcessingBuilder->threeDS->challengeWindowSize)) {
				$payload['instruction']['threeDS']['challenge']['windowSize'] = $this->paymentProcessingBuilder->threeDS->challengeWindowSize;
			}
			if (!empty($this->paymentProcessingBuilder->threeDS->challengePreference)) {
				$payload['instruction']['threeDS']['challenge']['preference'] = $this->paymentProcessingBuilder->threeDS->challengePreference;
			}
		}

		/**
		 * Instruction Customer
		 */
		if (!empty($this->paymentProcessingBuilder->customer) && $this->paymentProcessingBuilder->customer instanceof Customer) {
			if (!empty($this->paymentProcessingBuilder->customer->firstName)) {
				$payload['instruction']['customer']['firstName'] = $this->paymentProcessingBuilder->customer->firstName;
			}
			if (!empty($this->paymentProcessingBuilder->customer->lastName)) {
				$payload['instruction']['customer']['lastName'] = $this->paymentProcessingBuilder->customer->lastName;
			}
			if (!empty($this->paymentProcessingBuilder->customer->phoneNumber)) {
				$payload['instruction']['customer']['phone'] = $this->paymentProcessingBuilder->customer->phoneNumber;
			}
			if (!empty($this->paymentProcessingBuilder->customer->email)) {
				$payload['instruction']['customer']['email'] = $this->paymentProcessingBuilder->customer->email;
			}
		}

		/**
		 * Instruction Shipping
		 */
		if (!empty($this->paymentProcessingBuilder->shippingAddress) && $this->paymentProcessingBuilder->shippingAddress instanceof ShippingAddress) {
			$payload['instruction']['shipping']['address'] = $this->paymentProcessingBuilder->shippingAddress;
		}
		if (!empty($this->paymentProcessingBuilder->shippingEmail)) {
			$payload['instruction']['shipping']['email'] = $this->paymentProcessingBuilder->shippingEmail;
		}
		if (!empty($this->paymentProcessingBuilder->shippingMethod)) {
			$payload['instruction']['shipping']['method'] = $this->paymentProcessingBuilder->shippingMethod;
		}

		return json_encode($payload);
	}
}
