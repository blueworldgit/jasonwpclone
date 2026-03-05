<?php

namespace Worldpay\Api\Services\Requests;

use Worldpay\Api\ApiResponse;
use Worldpay\Api\ValueObjects\UserAgent;
use Worldpay\Api\Builders\RequestBuilder;
use Worldpay\Api\Providers\ProxyConfigProvider;

/**
 * API communication service.
 */
class RequestService
{
    /**
     * @var array
     */
    protected static array $responseHeaders;

    /**
     * @param  RequestBuilder  $builder
     * @return ApiResponse
     * @throws \Exception
     */
    public static function process(RequestBuilder $builder) {
        self::$responseHeaders = [];

        if(UserAgent::isEnabled() && empty($builder->headers['User-Agent'])) {
            $builder->headers['User-Agent'] = UserAgent::getInstance()->getUserAgentHeader();
        }

        $proxyConfigProvider = ProxyConfigProvider::instance();
        try {
            $curlHandle = curl_init($builder->url);

            $headers = [];
            foreach($builder->headers as $headerName => $headerValue) {
                $headers[] = "$headerName: $headerValue";
            }

            curl_setopt($curlHandle, CURLOPT_HEADER, true);
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curlHandle, CURLOPT_HEADERFUNCTION, array(RequestService::class, 'responseHeaders'));
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curlHandle, CURLOPT_VERBOSE, false);
            if (isset($builder->connectTimeout)) {
                curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, $builder->connectTimeout);
            }
            if (isset($builder->timeout)) {
                curl_setopt($curlHandle, CURLOPT_TIMEOUT, $builder->timeout);
            }
            curl_setopt($curlHandle, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $builder->method);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $builder->body ?? '');

            if (!empty($proxyConfigProvider->host)) {
                curl_setopt($curlHandle, CURLOPT_PROXY, $proxyConfigProvider->host);
                if (!empty($proxyConfigProvider->port)) {
                    curl_setopt($curlHandle, CURLOPT_PROXYPORT, $proxyConfigProvider->port);
                }
                 if (!empty($proxyConfigProvider->proxyUsername) && !empty($proxyConfigProvider->proxyPassword)) {
                    curl_setopt($curlHandle, CURLOPT_PROXYUSERPWD, $proxyConfigProvider->proxyUsername.':'.$proxyConfigProvider->proxyPassword);
                }
            }

            $curlResponse = curl_exec($curlHandle);

            $curlErrNo = curl_errno($curlHandle);
            $curlError = curl_error($curlHandle);
            $curlInfo = curl_getinfo($curlHandle);
            $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);

            curl_close($curlHandle);

            $apiResponse = new ApiResponse();
            $apiResponse->statusCode = (int)$curlInfo['http_code'];
            $apiResponse->headers = self::$responseHeaders;
            $apiResponse->rawResponse = substr($curlResponse, $headerSize);
            $apiResponse->curlError = $curlError;
            $apiResponse->curlErrorNo = $curlErrNo;
            $apiResponse->rawRequest = $builder->body ?? '';
            $apiResponse->endpoint = $builder->url ?? '';
            $apiResponse->sentHeaders = $builder->headers ?? '';

            return $apiResponse;
        } catch (\Exception $e) {
            throw new \Exception(
                'API communication error.',
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get response headers array.
     *
     * @param $curl
     * @param $header
     * @return int
     */
    public static function responseHeaders($curl, $header) {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2) {// ignore invalid headers
            return $len;
        }

        self::$responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

        return $len;
    }
}
