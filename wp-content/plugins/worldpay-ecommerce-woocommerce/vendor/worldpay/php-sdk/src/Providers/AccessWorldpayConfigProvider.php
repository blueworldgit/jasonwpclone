<?php

namespace Worldpay\Api\Providers;

/**
 * Access Worldpay API configuration provider.
 */
class AccessWorldpayConfigProvider extends ConfigProvider
{
    /**
     * @var string API Auth user name.
     */
    public string $username;

    /**
     * @var string API Auth password.
     */
    public string $password;

    /**
     * @var string Merchant entity.
     */
    public string $merchantEntity;

    /**
     * @var string Merchant narrative.
     */
    public string $merchantNarrative;

	/**
	 * @var string Checkout id.
	 */
	public string $checkoutId;
}
