<?php

namespace Worldpay\Api;

use Worldpay\Api\Exceptions\ApiResponseException;

class ApiResponse
{
    /**
     * @var int The last response code.
     */
    public int $statusCode;

    /**
     * @var array All headers received.
     */
    public array $headers;

    /**
     * @var string The result.
     */
    public string $rawResponse;

    /**
     * @var string
     */
    public string $curlError;

    /**
     * @var int
     */
    public int $curlErrorNo;

    /**
     * @var string
     */
    public string $rawRequest;

    /**
     * @var string
     */
    public string $endpoint;

	/**
	 * @var array $sentHeaders
	 */
	public array $sentHeaders;

    /**
     * Determine if the request was successful (status codes in the 2xx range).
     *
     * @return bool
     */
    public function isSuccessful(): bool {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return bool
     */
    public function hasError(): bool {
        return !empty($this->curlErrorNo);
    }

    /**
     * Determine if the response indicates problems on the server side of the connection (status codes in the 5xx range).
     *
     * @return bool
     */
    public function hasServerError(): bool {
        return $this->statusCode >= 500;
    }

    /**
     * Determine if the response indicates problems with the request which must be resolved (status codes in the 4xx range).
     *
     * @return bool
     */
    public function hasClientError(): bool {
        return $this->statusCode >= 400 && $this->statusCode <500;
    }

    /**
     * @param  bool|null  $associative
     *
     * @return object|array
     * @throws ApiResponseException
     */
    public function jsonDecode(?bool $associative = null) {
        $decodedResponse = json_decode($this->rawResponse, $associative);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiResponseException(json_last_error_msg());
        }

        return $decodedResponse;
    }

	/**
	 * @return mixed|null
	 */
	public function getWPCorrelationId() {
		if (!isset($this->headers['WP-CorrelationId']) && !isset($this->headers['wp-correlationid'])) {
			return null;
		}

		return $this->headers['WP-CorrelationId'][0] ?? $this->headers['wp-correlationid'][0];
	}

	/**
	 * @param  string  $requestType
	 * @param  string  $detailMessage
	 * @param  array  $additionalData
	 *
	 * @return string
	 */
	public function getResponseMetadata(string $requestType, string $detailMessage='', array $additionalData=[]) {
		$data['requestType'] = $requestType;
		if ($detailMessage) {
			$data['detailMessage'] = $detailMessage;
		}
		$data['WPCorrelationId'] = $this->getWPCorrelationId();
		$data['statusCode'] = $this->statusCode;
		if (!$this->isSuccessful()) {
			if ($this->hasError()) {
				$data['curlErrorNo'] = $this->curlErrorNo ?? '';
				$data['curlErrorMessage'] = $this->curlError ?? '';
			}
			try {
				$errorResponse = $this->jsonDecode();
				$data['errorName'] = $errorResponse->errorName ?? '';
				$data['errorMessage'] = $errorResponse->message ?? '';
			} catch(\Exception $e) {
				$data['jsonDecodeErrorMessage'] = $e->getMessage();
			}

		}
		if (!empty($additionalData)) {
			$data = array_merge($data, $additionalData);
		}

		return implode(' | ', array_map(fn($k, $v) => "$k=$v", array_keys($data), $data));
	}

	/**
	 * Throw an exception unless the response status code matches the given code.
	 *
	 * @param int $expectedStatus
	 *
	 * @return ApiResponse
	 * @throws ApiResponseException
	 */
	public function throwUnlessStatus(int $expectedStatus)
	{
		if ($this->statusCode !== $expectedStatus) {
			throw new ApiResponseException(
				"Unexpected HTTP status: expected $expectedStatus, got $this->statusCode"
			);
		}

		return $this;
	}
}
