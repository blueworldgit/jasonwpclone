<?php

namespace Worldpay\Api\Builders\Tokens;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\PaymentTokenServiceInterface;
use Worldpay\Api\ValueObjects\BillingAddress;
use Worldpay\Api\ValueObjects\PaymentMethods\CreditCard;

class PaymentTokenBuilder {
	/**
	 * @var BillingAddress Contains the billing address information.
	 */
	public BillingAddress $billingAddress;

	/**
	 * @var CreditCard An object that contains the payment type and details.
	 */
	public CreditCard $card;

	/**
	 * @var string The date/time after which the token is unavailable, expressed in ISO 8601 format.
	 */
	public string $expiryDateTime;

	/**
	 * @var string A description of your token.
	 */
	public string $description;

	/**
	 * @var string A namespace is used to group up to 16 cards, e.g. for one customer.
	 */
	public string $namespace;

	/**
	 * @var string
	 */
	public string $tokenId;

	/**
	 * @var string A value provided by Visa or Mastercard which tracks recurring transactions.
	 */
	public string $schemeTransactionReference;

	/**
	 * @param  string  $command
	 *
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	protected function execute(string $command): ApiResponse {
		$configProvider = AccessWorldpayConfigProvider::instance();
		$apiService = $configProvider->getApiService();
		if (! class_exists($apiService) || ! ((new $apiService()) instanceof PaymentTokenServiceInterface)) {
			throw new ApiClientException($configProvider->getApi() . ' does not support tokens.');
		}

		return $apiService::$command($this);
	}

	/**
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function create(): ApiResponse {
		return $this->execute('createPaymentToken');
	}

	/**
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function update(): ApiResponse {
		return $this->execute('updatePaymentToken');
	}

	/**
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function delete(): ApiResponse {
		return $this->execute('deletePaymentToken');
	}

	/**
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function query(): ApiResponse {
		return $this->execute('queryPaymentToken');
	}

	/**
	 * @return array
	 */
	public function getQueryStringParams(): array {
		return array_filter([
			'namespace' => $this->namespace ?? '',
		]);
	}

	/**
	 * @param  string  $tokenIdOrHref
	 *
	 * @return $this
	 */
	public function withTokenId(string $tokenIdOrHref): PaymentTokenBuilder {
		$this->tokenId = $tokenIdOrHref;

		return $this;
	}

	/**
	 * @param  CreditCard  $card
	 *
	 * @return $this
	 */
	public function withPaymentInstrument(CreditCard $card): PaymentTokenBuilder {
		$this->card = $card;

		return $this;
	}

	/**
	 * @param  BillingAddress  $billingAddress
	 *
	 * @return $this
	 */
	public function withBillingAddress(BillingAddress $billingAddress): PaymentTokenBuilder {
		$this->billingAddress = $billingAddress;

		return $this;
	}

	/**
	 * @param  string  $description
	 *
	 * @return $this
	 */
	public function withDescription(string $description): PaymentTokenBuilder {
		$this->description = $description;

		return $this;
	}

	/**
	 * @param  string  $namespace
	 *
	 * @return $this
	 */
	public function withNamespace(string $namespace): PaymentTokenBuilder {
		$this->namespace = $namespace;

		return $this;
	}

	/**
	 * @param  string  $expiryDateTime
	 *
	 * @return $this
	 */
	public function withExpiryDateTime(string $expiryDateTime): PaymentTokenBuilder {
		$this->expiryDateTime = $expiryDateTime;

		return $this;
	}

	/**
	 * @param  string  $schemeTransactionReference
	 *
	 * @return $this
	 */
	public function withSchemeTransactionReference(string $schemeTransactionReference): PaymentTokenBuilder {
		$this->schemeTransactionReference = $schemeTransactionReference;

		return $this;
	}
}
