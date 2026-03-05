<?php

namespace Worldpay\Api\Providers;

class ProxyConfigProvider extends ConfigProvider
{

    /**
    * @var string
    */
    public string $host;

    /**
    * @var string
    */
    public string $port;

    /**
    * @var string
    */
    public string $proxyUsername;

    /**
    * @var string
    */
    public string $proxyPassword;
}
