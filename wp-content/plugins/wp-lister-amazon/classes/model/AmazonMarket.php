<?php
/**
 * WPLA_AmazonMarket class
 *
 */

// class WPLA_AmazonMarket extends WPLA_NewModel {
class WPLA_AmazonMarket {

	const TABLENAME = 'amazon_markets';

	public $id;
	public $developer_id;
	public $title;
	public $code;
	public $url;
	public $marketplace_id;
	public $enabled;
	public $sort_order;
	public $group_title;


	function __construct( $id = null ) {
		
		$this->init();

		if ( $id ) {
			$this->id = $id;
			
			// load data into object
			$market = $this->getMarket( $id );
			foreach( $market AS $key => $value ){
			    $this->$key = $value;
			}

			return $this;
		}

	}

	function init()	{
	}

	// get single market
	static function getMarket( $id )	{
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		
		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE id = %d
		", $id
		), OBJECT);

		return $item;
	}

	static function getNamebyMarketplaceId( $marketplace_id ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT title FROM {$wpdb->prefix}amazon_markets WHERE marketplace_id = %s", $marketplace_id ) );
	}

	// get single market by country code (US)
	static function getMarketByCountyCode( $code )	{
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		
		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE code = %s
		", $code
		), OBJECT);

		return $item;
	}

	// get all markets
	static function getAll() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT *
			FROM $table
			ORDER BY sort_order ASC, title ASC
		", OBJECT_K);

		return $items;
	}

	/**
	 * Get all supported marketplaces that all accounts have access to
	 *
	 * This method removes all unsupported marketplaces like the Amazon Pay marketplace
	 *
	 * @return array
	 */
	static function getAllFromAccounts() {
		global $wpdb;

		$marketplaces = [];

		$all_markets = WPLA_AmazonMarket::getAll();
		$supported_markets = wp_list_pluck($all_markets, 'marketplace_id' );

		foreach ( WPLA()->accounts as $account ) {
			$markets = maybe_unserialize( $account->allowed_markets );

			if ( is_array($markets) ) {
				foreach ( $markets as $market ) {
					if ( !array_key_exists( $market->MarketplaceId, $marketplaces ) && in_array( $market->MarketplaceId, $supported_markets ) ) {
						$marketplaces[ $market->MarketplaceId ] = $market->Name;
					}
				}
			}
		}

		asort($marketplaces);
		return $marketplaces;
	}

	// get market code
	static function getMarketCode( $id )	{
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		
		$item = $wpdb->get_var( $wpdb->prepare("
			SELECT code
			FROM $table
			WHERE id = %d
		", $id ));

		return $item;
	}

	// get url
	static function getUrl( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$domain = $wpdb->get_var( $wpdb->prepare("
			SELECT domain
			FROM $table
			WHERE id = %d
		", $id ));

		return $domain;
	}

	// get url
	function getSignInUrl() {

        // $applicationName = 'WP-Lister for Amazon';
        // $applicationName = 'TEST';

        // $url = 'https://sellercentral.' . $this->url.
        //         '/gp/mws/registration/register.html?ie=UTF8&*Version*=1&*entries*=0' .
        //         '&applicationName=' . rawurlencode( $applicationName) .
        //         '&appDevMWSAccountId=' . $this->developer_id;

        //$url = 'https://sellercentral.' . $this->url.
        //        '/gp/mws/registration/register.html?ie=UTF8&*Version*=1&*entries*=0';

        // // Use the new User Permissions Page as the signin redirect URL
        // $url = 'https://sellercentral.'. $this->url .'/gp/account-manager/home.html';

        // Use the new "Manage your apps" Page as the signin redirect URL
        $url = 'https://sellercentral.'. $this->url .'/apps/manage';

        return $url;

	}

	function getOAuthUrl() {
        $api = new WPLA_Amazon_SP_API();
        return $api->getOAuthUri( $this->id );
    }


} // WPLA_AmazonMarket()

