<?php
/**
 * @license BSD-3-Clause
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\SellingPartnerApi\Api;

use WPLab\Amazon\GuzzleHttp\Client;
use WPLab\Amazon\GuzzleHttp\ClientInterface;
use WPLab\Amazon\GuzzleHttp\RequestOptions;
use WPLab\Amazon\SellingPartnerApi\Configuration;
use WPLab\Amazon\SellingPartnerApi\HeaderSelector;

abstract class BaseApi
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var HeaderSelector
     */
    protected $headerSelector;

    /**
     * @param Configuration   $config
     * @param ClientInterface $client
     * @param HeaderSelector  $selector
     */
    public function __construct(
        Configuration $config,
        ClientInterface $client = null,
        HeaderSelector $selector = null
    ) {
        $this->config = $config;
        $this->client = $client ?: new Client();
        $this->headerSelector = $selector ?: new HeaderSelector($this->config);
    }

    /**
     * @return WPLab\Amazon\SellingPartnerApi\Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param WPLab\Amazon\SellingPartnerApi\Configuration $config
     * @return $this
     */
    public function setConfig(Configuration $config)
    {
        $this->config = $config;
        $this->headerSelector = new HeaderSelector($config);
        return $this;
    }

    /**
     * Create http client option
     *
     * @throws \RuntimeException on file opening failure
     * @return array of http client options
     */
    protected function createHttpClientOption()
    {
        $options = [];
        if ($this->config->getDebug()) {
            $options[RequestOptions::DEBUG] = fopen($this->config->getDebugFile(), 'a');
            if (!$options[RequestOptions::DEBUG]) {
                throw new \RuntimeException('Failed to open the debug file: ' . $this->config->getDebugFile());
            }
        }

        return $options;
    }

    /**
     * Writes to the debug log file
     *
     * @param any $data
     * @return void
     */
    protected function writeDebug($data)
    {
        if ($this->config->getDebug()) {
            if ($data instanceof Throwable) {
                $data = "$data";
            } else if ($data instanceof WPLab\Amazon\GuzzleHttp\Psr7\Request) {
                $data = "{$data->getMethod()} {$data->getUri()}\n" . implode("\n", $data->getHeaders());
            } else if ($data instanceof WPLab\Amazon\GuzzleHttp\Psr7\Response) {
                $data = "{$data->getStatusCode()} {$data->getReasonPhrase()}\n" . implode("\n", $data->getHeaders());
            } else {
                $data = print_r($data, true);
            }
            file_put_contents(
                $this->config->getDebugFile(),
                '[' . date('Y-m-d H:i:s') . ']: ' . $data . "\n",
                FILE_APPEND
            );
        }
    }
}
