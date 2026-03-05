<?php

namespace Worldpay\Api\ValueObjects;

class UserAgent
{
	/**
	 * Singleton instance of the UserAgent
	 *
	 * @var UserAgent|null
	 */
	private static ?UserAgent $instance = null;

	/**
	 * Enable User-Agent header injection
	 *
	 * @var bool
	 */
	private static bool $enabled = true;

	/**
	 *  Name of the platform used, e.g. WooCommerce
	 * 
	 * @var string|null $platformName
	 */
	public ?string $platformName = '';

	/**
	 * Version of the platform used, e.g. 8.9.1
	 *
	 * @var string|null $platformVersion
	 */
	public ?string $platformVersion = '';

	/**
	 * Url of the store, e.g. https://example.com
	 *
	 * @var string|null $storeUrl
	 */
	public ?string $storeUrl = '';

	/**
	 * Name of the plugin used, e.g. worldpay-woocommerce
	 *
	 * @var string|null $pluginName
	 */
	public ?string $pluginName = '';

	/**
	 * Version of the plugin used, e.g. 1.2.3
	 *
	 * @var string|null $pluginVersion
	 */
	public ?string $pluginVersion = '';

	/**
	 * Name of the language used, defaults to 'PHP'
	 *
	 * @var string $languageName
	 */
	public string $languageName = 'PHP';

	/**
	 * Version of the language used, defaults to the current PHP version from phpversion()
	 *
	 * @var string|null $languageVersion
	 */
	public ?string $languageVersion = '';

	/**
	 * Name of the CMS used, e.g. WordPress
	 *
	 * @var string|null $cmsName
	 */
	public ?string $cmsName = '';

	/**
	 * Version of the CMS used, e.g. 6.5.3
	 *
	 * @var string|null $cmsVersion
	 */
	public ?string $cmsVersion = '';

	/**
	 * Type of integration, e.g. Onsite or Offsite.
	 *
	 * @var string|null $integrationType
	 */
	public ?string $integrationType = '';

	/**
	 * Integration environment, e.g. Try or Live
	 *
	 * @var string|null $integrationEnvironment
	 */
	public ?string $integrationEnvironment = '';

	/**
	 * Turn on User-Agent header injection
	 *
	 * @return void
	 */
	public static function enable(): void {
		self::$enabled = true;
	}

	/**
	 * Turn off User-Agent header injection
	 *
	 * @return void
	 */
	public static function disable(): void {
		self::$enabled = false;
	}

	/**
	 * Check the current state of the User-Agent injection
	 *
	 * @return bool
	 */
	public static function isEnabled(): bool {
		return self::$enabled;
	}

	/**
	 * Protected constructor to enforce singleton pattern.
	 * Initializes languageVersion using the current PHP runtime version.
	 */
	protected function __construct() {
		$this->languageVersion = phpversion();
	}

	/**
	 * Get the singleton instance of the UserAgent.
	 *
	 * @return UserAgent
	 */
	public static function getInstance(): UserAgent {
		if (self::$instance === null) {
			self::$instance = new UserAgent();
		}

		return self::$instance;
	}

	/**
	 * Build the User-Agent header string for API requests.
	 *
	 * Example:
	 * WooCommerce/8.9.1 (https://example.com) worldpay-woocommerce/1.2.3 PHP/8.1.17 WordPress/6.5.3 API TEST
	 *
	 * @return string
	 */
	public function getUserAgentHeader(): string {
		$segments = [
			$this->makeHeaderSegment($this->platformName, $this->platformVersion),
			$this->storeUrl ? "({$this->storeUrl})" : null,
			$this->makeHeaderSegment($this->pluginName, $this->pluginVersion),
			"{$this->languageName}/{$this->languageVersion}",
			$this->makeHeaderSegment($this->cmsName, $this->cmsVersion),
			$this->integrationType ?: null,
			$this->integrationEnvironment ?: null,
		];

		return implode(' ', array_filter($segments));
	}

	/**
	 * Build a “name/version” header segment, or fall back to whichever part is present or empty if none
	 *
	 * @param string|null $headerName
	 * @param string|null $headerVersion
	 *
	 * @return string|null
	 */
	private function makeHeaderSegment(?string $headerName, ?string $headerVersion): ?string {
		$headerParts = array_filter([
			$headerName,
			$headerVersion
		]);

		if(empty($headerParts)) {
			return null;
		}

		return implode('/', $headerParts);
	}
}
