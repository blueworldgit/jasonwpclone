<?php

namespace Worldpay\Api\Builders\PaymentProcessing;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Services\AccessWorldpay\PaymentsApiService;

class ThreeDSChallengeBuilder
{
	/**
	 * @var string
	 */
	public string $linkData;

	/**
	 * @return ApiResponse
	 * @throws ApiClientException
	 */
	public function execute(): ApiResponse {
		$configProvider = AccessWorldpayConfigProvider::instance();
		$apiService = $configProvider->getApiService();
		if (! class_exists($apiService) || ! ((new $apiService()) instanceof PaymentsApiService)) {
			throw new ApiClientException($configProvider->getApi() . ' does not support 3DS actions.');
		}

		return $apiService::verify3DSChallenge($this);
	}

	/**
	 * @param  string  $linkData
	 * @return ThreeDSChallengeBuilder
	 */
	public function withLinkData(string $linkData): ThreeDSChallengeBuilder {
		$this->linkData = $linkData;

		return $this;
	}
}
