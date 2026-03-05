<?php

namespace Worldpay\Api\Builders\PaymentProcessing;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\AccessWorldpay\PaymentsApiService;

class ThreeDSDeviceDataBuilder
{
	/**
	 * @var string
	 */
	public string $linkData;

	/**
	 * @var string
	 */
	public string $collectionReference;

	/**
	 * @return ApiResponse
	 * @throws ApiClientException
	 * @throws \Exception
	 */
	public function execute(): ApiResponse {
		$configProvider = AccessWorldpayConfigProvider::instance();
		$apiService = $configProvider->getApiService();
		if (! class_exists($apiService) || ! ((new $apiService()) instanceof PaymentsApiService)) {
			throw new ApiClientException($configProvider->getApi() . ' does not support 3DS actions.');
		}

		return $apiService::supply3DSDeviceData($this);
	}

	/**
	 * @return string
	 */
	public function createPayload(): string {
		$payload = [
			'collectionReference' => $this->collectionReference ?? '',
		];
		return json_encode($payload);
	}

	/**
	 * @param  string  $linkData
	 * @return ThreeDSDeviceDataBuilder
	 */
	public function withLinkData(string $linkData): ThreeDSDeviceDataBuilder {
		$this->linkData = $linkData;

		return $this;
	}

	/**
	 * @param  string  $collectionReference
	 * @return ThreeDSDeviceDataBuilder
	 */
	public function withCollectionReference(string $collectionReference): ThreeDSDeviceDataBuilder {
		$this->collectionReference = $collectionReference;

		return $this;
	}
}
