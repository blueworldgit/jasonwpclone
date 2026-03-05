<?php

namespace Worldpay\Api\Builders\PaymentSession;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\PaymentSessionServiceInterface;

class PaymentSessionBuilder {

	/**
	 * @var string
	 */
	public string $identity;

	/**
	 * @var int
	 */
	public int $cardExpiryMonth;

	/**
	 * @var int
	 */
	public int $cardExpiryYear;

	/**
	 * @var string
	 */
	public string $cardCvc;

	/**
	 * @var string
	 */
	public string $cardNumber;

	/**
	 * @return ApiResponse
	 * @throws ApiClientException
	 * @throws \Exception
	 */
	public function execute(): ApiResponse {
		$configProvider = AccessWorldpayConfigProvider::instance();
		$apiService = $configProvider->getApiService();
		if (! class_exists($apiService) || ! ((new $apiService()) instanceof PaymentSessionServiceInterface)) {
			throw new ApiClientException($configProvider->getApi() . ' does not support payment sessions setup.');
		}

		return $apiService::setupPaymentSession($this);
	}

	/**
	 * @param  string  $checkoutId
	 * @return $this
	 */
	public function withIdentity(string $checkoutId): PaymentSessionBuilder {
		$this->identity = $checkoutId;

		return $this;
	}

	/**
	 * @param  int  $cardExpiryMonth
	 * @return $this
	 */
	public function withCardExpiryMonth(int $cardExpiryMonth): PaymentSessionBuilder {
		$this->cardExpiryMonth = $cardExpiryMonth;

		return $this;
	}

	/**
	 * @param  int  $cardExpiryYear
	 * @return $this
	 */
	public function withCardExpiryYear(int $cardExpiryYear): PaymentSessionBuilder {
		$this->cardExpiryYear = $cardExpiryYear;

		return $this;
	}

	/**
	 * @param  string  $cardCvc
	 * @return $this
	 */
	public function withCardCvc(string $cardCvc): PaymentSessionBuilder {
		$this->cardCvc = $cardCvc;

		return $this;
	}

	/**
	 * @param  string  $cardNumber
	 * @return $this
	 */
	public function withCardNumber(string $cardNumber): PaymentSessionBuilder {
		$this->cardNumber = $cardNumber;

		return $this;
	}
}
