<?php

namespace Worldpay\Api\Builders\PaymentManagement;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Enums\PaymentOperation;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Exceptions\InvalidArgumentException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\PaymentManagementServiceInterface;

class PaymentManagementBuilder
{
	/**
	 * @var string
	 */
	public string $paymentOperation;

	/**
	 * @var string
	 */
	public string $transactionReference;

	/**
	 * @var int
	 */
	public int $amount;

	/**
	 * @var string
	 */
	public string $currency = 'GBP';

	/**
	 * @var string
	 */
	public string $partialOperationReference;

	/**
	 * @var string
	 */
	public string $linkData;

	/**
	 * @param  string  $paymentOperation
	 */
	public function __construct(string $paymentOperation) {
		$this->paymentOperation = $paymentOperation;
	}

	/**
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function execute(): ApiResponse {
		$configProvider = AccessWorldpayConfigProvider::instance();
		$apiService = $configProvider->getApiService();
		if (! class_exists($apiService) || ! ((new $apiService()) instanceof PaymentManagementServiceInterface) || ! $apiService::supports($this->paymentOperation)) {
			throw new ApiClientException($configProvider->getApi() . ' does not support payment ' .$this->paymentOperation . '.');
		}

		return $apiService::managePayment($this);
	}

	/**
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function createPayload(): string {
		switch ($this->paymentOperation) {
			case PaymentOperation::PARTIAL_REFUND:
			case PaymentOperation::PARTIAL_SETTLE:
			case PaymentOperation::PARTIAL_REVERSE:
				return $this->createPartialOperationPaymentPayload();
			default:
				return '';
		}
	}

	/**
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected function createPartialOperationPaymentPayload(): string {
		$payload = [];

		if(empty($this->amount)) {
			throw new InvalidArgumentException('Mandatory amount is missing.');
		}
		$payload['value']['amount'] = $this->amount;
		if(empty($this->currency)) {
			throw new InvalidArgumentException('Mandatory currency is missing.');
		}
		$payload['value']['currency'] = $this->currency;
		if(empty($this->partialOperationReference)) {
			throw new InvalidArgumentException('Invalid partial operation reference. Must supply a non empty-string. Maximum of 128 characters.');
		}
		$payload['reference'] = $this->partialOperationReference;

		return json_encode($payload);
	}

	/**
	 * @param  int  $amount
	 * @return PaymentManagementBuilder
	 */
	public function withAmount(int $amount): PaymentManagementBuilder {
		$this->amount = $amount;

		return $this;
	}

	/**
	 * @param  string  $currency
	 * @return PaymentManagementBuilder
	 */
	public function withCurrency(string $currency): PaymentManagementBuilder {
		$this->currency = $currency;

		return $this;
	}

	/**
	 * @param  string  $transactionReference
	 * @return PaymentManagementBuilder
	 */
	public function withTransactionReference(string $transactionReference): PaymentManagementBuilder {
		$this->transactionReference = $transactionReference;

		return $this;
	}

	/**
	 * @param  string  $partialOperationReference
	 * @return PaymentManagementBuilder
	 */
	public function withPartialOperationReference(string $partialOperationReference): PaymentManagementBuilder {
		$this->partialOperationReference = $partialOperationReference;

		return $this;
	}

	/**
	 * @param  string  $linkData
	 * @return $this
	 */
	public function withLinkData(string $linkData): PaymentManagementBuilder {
		$this->linkData = $linkData;

		return $this;
	}
}
