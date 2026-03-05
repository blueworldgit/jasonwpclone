<?php

namespace Worldpay\Api\Builders;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\Enums\RequestMethods;
use Worldpay\Api\Services\Requests\RequestService;

/**
 * HTTP request generic builder.
 */
class RequestBuilder
{
	public const DEFAULT_TIMEOUT = 20;

    /**
     * @var string The URL to fetch.
     */
    public string $url;

    /**
     * @var string Request method to use.
     */
    public string $method;

    /**
     * @var array An array of HTTP header fields to set.
     */
    public array $headers = [];

    /**
     * @var string The full data to post in a HTTP "POST" operation.
     */
    public string $body;

    /**
     * @var array The query parameters in a HTTP "GET" operation.
     */
    public array $params = [];

    /**
     * @var int The maximum number of seconds to allow cURL functions to execute.
     */
    public int $timeout = self::DEFAULT_TIMEOUT;

    /**
     * @var int The number of seconds to wait while trying to connect.
     */
    public int $connectTimeout = self::DEFAULT_TIMEOUT;

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments) {
        return (new static)->$method(...$arguments);
    }

    /**
     * @param  string  $username
     * @param  string  $password
     * @return RequestBuilder
     */
    protected function withBasicAuth(string $username, string $password): RequestBuilder {
        $this->headers['Authorization'] = 'Basic ' . base64_encode("$username:$password");

        return $this;

    }

    /**
     * @param  array  $headers
     * @return RequestBuilder
     */
    public function withHeaders(array $headers): RequestBuilder {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * @param $contentType
     * @return RequestBuilder
     */
    public function withContentTypeHeader($contentType = 'application/json'): RequestBuilder {
        $this->headers['Content-type'] = $contentType;

        return $this;
    }

    /**
     * @param $accept
     * @return RequestBuilder
     */
    public function withAcceptHeader($accept = 'text/html'): RequestBuilder {
        $this->headers['Accept'] = $accept;

        return $this;
    }

	/**
	 * @param  string  $headerValue
	 *
	 * @return $this
	 */
	public function withWpApiVersionHeader(string $headerValue): RequestBuilder {
		$this->headers['WP-Api-Version'] = $headerValue;

		return $this;
	}

    /**
     * @param  string  $body
     * @return RequestBuilder
     */
    public function withBody(string $body): RequestBuilder {
        $this->body = $body;

        return $this;
    }

    /**
     * @param  array  $params
     * @return $this
     */
    public function withParams(array $params): RequestBuilder {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * @param  int  $seconds
     * @return RequestBuilder
     */
    public function withTimeout(int $seconds = self::DEFAULT_TIMEOUT): RequestBuilder {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * @param  int  $seconds
     * @return RequestBuilder
     */
    public function withConnectTimeout(int $seconds = self::DEFAULT_TIMEOUT): RequestBuilder {
        $this->connectTimeout = $seconds;

        return $this;
    }

    /**
     * @param $requestURL
     * @return ApiResponse
     * @throws \Exception
     */
    public function get($requestURL): ApiResponse {
        $this->url = $requestURL;
        if (!empty($this->params)) {
            $this->url .= '?' . http_build_query($this->params);
        }
        $this->method = RequestMethods::GET;

        return RequestService::process($this);
    }

    /**
     * @param $requestURL
     * @return ApiResponse
     * @throws \Exception
     */
    public function post($requestURL): ApiResponse {
        $this->url = $requestURL;
        $this->method = RequestMethods::POST;

        return RequestService::process($this);
    }

	/**
	 * @param $requestURL
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public function put($requestURL): ApiResponse {
		$this->url = $requestURL;
		$this->method = RequestMethods::PUT;

		return RequestService::process($this);
	}

	/**
	 * @param $requestURL
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public function delete($requestURL): ApiResponse {
		$this->url = $requestURL;
		$this->method = RequestMethods::DELETE;

		return RequestService::process($this);
	}
}
