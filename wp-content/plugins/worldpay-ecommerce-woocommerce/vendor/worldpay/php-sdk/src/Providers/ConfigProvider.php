<?php

namespace Worldpay\Api\Providers;

use Worldpay\Api\Enums\Api;
use Worldpay\Api\Exceptions\ApiClientException;
use Worldpay\Api\Services\AccessWorldpay\HPPApiService;
use Worldpay\Api\Services\AccessWorldpay\PaymentQueriesApiService;
use Worldpay\Api\Services\AccessWorldpay\PaymentsApiService;
use Worldpay\Api\Services\AccessWorldpay\PaymentSessionsApiService;
use Worldpay\Api\Services\AccessWorldpay\TokensApiService;

abstract class ConfigProvider
{
    /**
     * @var null
     */
    private static $instances = [];

    /**
     * @var string
     */
    public string $api = Api::ACCESS_WORLDPAY_HPP_API;

	/**
	 * @var string
	 */
	protected string $apiService;

    /**
     * @var string
     */
    public string $environment;

	/**
	 * @var string
	 */
	public string $apiVersion = '2024-06-01';

    /**
     * @return mixed|static
     */
    public static function instance() {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }

        return self::$instances[$class];
    }

    /**
     * @return string
     */
    public function getApi(): string {
        return $this->api;
    }

	/**
	 * @return string
	 * @throws ApiClientException
	 */
	public function getApiService(): string {
		switch ($this->api) {
			case Api::ACCESS_WORLDPAY_HPP_API:
				$this->apiService = HPPApiService::class;
				break;
			case Api::ACCESS_WORLDPAY_PAYMENTS_API:
				$this->apiService = PaymentsApiService::class;
				break;
			case Api::ACCESS_WORLDPAY_PAYMENT_QUERIES_API:
				$this->apiService = PaymentQueriesApiService::class;
				break;
			case Api::ACCESS_WORLDPAY_PAYMENT_SESSIONS_API:
				$this->apiService = PaymentSessionsApiService::class;
				break;
			case Api::ACCESS_WORLDPAY_TOKENS_API:
				$this->apiService = TokensApiService::class;
				break;
			default:
				throw new ApiClientException('API not supported.');
		}

		return $this->apiService;
	}
}
