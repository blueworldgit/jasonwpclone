<?php
/**
 * WPLA_AmazonProfile class
 *
 */

class WPLA_AmazonProfile {

	const TABLENAME = 'amazon_profiles';

	public $id;
	public $data;
	public $fieldnames;
	public $old_fields;
	public $unmapped_fields;

	public $profile_id;
	public $profile_name;
	public $profile_description;
	public $marketplace_id;
	public $product_type;
	public $feed_type;
	public $details;
	public $fields;
	public $tpl_id;
	public $account_id;
	public $total_items;

	public function __construct( $id = null ) {
		
		$this->init();

		if ( $id ) {

			$this->id = $id;
			
			// load data into object
			$profile = self::getProfile( $id );
			foreach( $profile AS $key => $value ){
			    $this->$key = $value;
			}

			$this->fields  = maybe_unserialize( $this->fields );
			$this->details = maybe_unserialize( $this->details );

			// Fix for a previous bug where the details array is double-serialized during cloning
			if ( !is_array( $this->details ) && !empty( $this->details ) ) {
				// try unserializing a second time
				$this->details = maybe_unserialize( $this->details );
			}

			if ( !is_array( $this->fields ) && !empty( $this->fields ) ) {
				$this->fields = maybe_unserialize( $this->fields );
			}

			if ( empty( $this->fields ) )
				$this->initDefaultFields();

			return $this;

		} else {

			foreach( $this->fieldnames AS $key ){
			    $this->$key = null;
			}
			$this->initDefaultFields();

		}

	}

	function init()	{

		$this->fieldnames = array(
			'profile_id',
			'profile_name',
			'profile_description',
			'feed_type',
			'details',
			'fields',
			'tpl_id',
			'product_type',
			'marketplace_id',
			'account_id'
		);

	}

	// set default fields for new profiles
	function initDefaultFields() {
		if ( ! empty( $this->fields ) ) return;

		// Get WooCommerce dimension unit and convert it to Amazon format
		$wc_dimension_unit = get_option( 'woocommerce_dimension_unit', 'in' );
		$amazon_dimension_unit = self::convertWooCommerceUnitToAmazon( strtoupper($wc_dimension_unit) );

		$this->fields = array(
			// JSON feeds
			'externally_assigned_product_identifier[0][value]'                  => '[amazon_product_id]',
			'item_name[0][value]'                                               => '[product_title]',
			'product_description[0][value]'                                     => '[product_content]',
			'purchasable_offer[0][our_price][schedule][0][value_with_tax]'         => '[product_price]',
			'purchasable_offer[0][discounted_price][schedule][0][value_with_tax]'  => '[product_sale_price]',
			'purchasable_offer[0][discounted_price][schedule][0][start_at]'        => '[product_sale_start]',
			'purchasable_offer[0][discounted_price][schedule][0][end_at]'          => '[product_sale_end]',
			'item_dimensions[0][length][value]'         => '[product_length]',
			'item_dimensions[0][length][unit]'          => $amazon_dimension_unit,
			'item_dimensions[0][width][value]'          => '[product_width]',
			'item_dimensions[0][width][unit]'           => $amazon_dimension_unit,
			'item_dimensions[0][height][value]'         => '[product_height]',
			'item_dimensions[0][height][unit]'          => $amazon_dimension_unit,
			/*
			### DEPRECATED FIELDS ###
			// category feeds
			'external_product_id' => '[amazon_product_id]',
			'item_name'           => '[product_title]',
			'product_description' => '[product_content]',
			'standard_price'      => '[product_price]',
			'sale_price'          => '[product_sale_price]',
			'sale_from_date'      => '[product_sale_start]',
			'sale_end_date'       => '[product_sale_end]',
			'item_length'         => '[product_length]',
			'item_width'          => '[product_width]',
			'item_height'         => '[product_height]',
			// 'item_weight'         => '[product_weight]',		// disabled due to troubles when item_weight would be populated automatically
																// while no unit was provided and the feed failed with error 8058 (#24959 / #25776)
			// ListingLoader
			'product-id'          => '[amazon_product_id]',
			'title'               => '[product_title]',
			'price'      		  => '[product_price]',
			'sale-price'          => '[product_sale_price]',
			'sale-start-date'     => '[product_sale_start]',
			'sale-end-date'       => '[product_sale_end]',
			*/
		);

	}

	/**
	 * Convert WooCommerce dimension unit to Amazon format
	 * 
	 * @param string $wc_unit WooCommerce unit (uppercase)
	 * @return string Amazon unit format
	 */
	private static function convertWooCommerceUnitToAmazon( $wc_unit ) {
		$unit_mapping = [
			// Length/Distance units (WooCommerce => Amazon)
			'IN' => 'inches',
			'CM' => 'centimeters',
			'M'  => 'meters',
			'MM' => 'millimeters',
			'FT' => 'feet',
		];

		return isset( $unit_mapping[ $wc_unit ] ) ? $unit_mapping[ $wc_unit ] : 'inches';
	}

	/**
	 * Returns TRUE if this profile uses the outdated Listings Feed Templates.
	 *
	 * A profile is considered a legacy profile if all the following conditions are true
	 * 1) it has a profile ID
	 * 2) it has a tpl_id
	 * 3) it doesn't have a product_type value
	 *
	 * @return bool
	 */
	public function isLegacyProfile() {
		if ( $this->id && $this->tpl_id && empty( $this->product_type ) ) {
			return true;
		}

		return false;
	}

	// get single profile
	public static function getProfile( $id )	{
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		
		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE profile_id = %d
		", $id
		), OBJECT);

		return $item;
	}

	// get all profiles
	public static function getAll() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT *
			FROM $table
			ORDER BY profile_name ASC
		", OBJECT_K);

		return $items;
	}

	public static function getAllUsingTemplate( $tpl_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		return $wpdb->get_results($wpdb->prepare( "
			SELECT *
			FROM $table
			WHERE tpl_id = %d
			ORDER BY profile_name ASC
		", $tpl_id ), OBJECT_K);
	}

	public static function getAllNames() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$results = $wpdb->get_results("
			SELECT profile_id, profile_name 
			FROM $table
			ORDER BY profile_name ASC
		");		

		$profiles = array();
		foreach( $results as $result ) {
			$profiles[ $result->profile_id ] = $result->profile_name;
		}

		return $profiles;		
	}

	public static function getAllTemplateNames() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$results = $wpdb->get_results("
			SELECT profile_id, tpl_id 
			FROM $table
		");		

		$templates = array();
		foreach( $results as $result ) {
			$template                         = WPLA_AmazonFeedTemplate::getFeedTemplate( $result->tpl_id );
			$templates[ $result->profile_id ] = $template ? $template->title : false;
		}

		return $templates;		
	}

	// Get the first profile ID found for listings linked to $post_id
	public static function getProfileForProduct( $post_id ) {
	    $lm = new WPLA_ListingsModel();
	    $listings = $lm->getAllItemsByPostOrParentID( $post_id );
	    $profile_id = 0;

	    foreach ( $listings as $listing ) {
	        if ( $listing->profile_id ) {
	            $profile_id = $listing->profile_id;
	            break;
            }
        }

	    return $profile_id;
    }

	// count items using profile and status (optimized version of the above methods)
	public static function countProfilesUsingTemplate( $tpl_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$item_count = $wpdb->get_var( $wpdb->prepare("
			SELECT count(profile_id) 
			FROM $table
			WHERE tpl_id = %s
		", $tpl_id ));

		return $item_count;
	}

	public static function getProfilesThatNeedConversion() {
		$profiles_mdl   = new \WPLA_AmazonProfile();
		$all_profiles   = $profiles_mdl->getAll();

		//$converted_profiles = get_option( 'wpla_json_converted_profiles', [] );
		$converted_profiles = self::getConvertedProfiles();

		foreach ( $all_profiles as $idx => $profile ) {
			if ( $profile->product_type ) {
				unset( $all_profiles[ $idx ] );
			} elseif ( in_array( $profile->profile_id, $converted_profiles ) ) {
				unset( $all_profiles[ $idx ] );
			}
		}

		return $all_profiles;
	}

	public static function getConvertedProfiles() {
		global $wpdb;

		$converted_profiles = get_option( 'wpla_json_converted_profiles', [] );
		$old_profiles_to_hide = [];

		if ( !empty( $converted_profiles ) ) {
			// Get the new profile IDs (values) to check if they still exist
			$new_profile_ids = array_values( $converted_profiles );
			$placeholders = implode( ',', array_fill( 0, count( $new_profile_ids ), '%d' ) );
			$sql = $wpdb->prepare(
				"SELECT profile_id FROM {$wpdb->prefix}amazon_profiles WHERE profile_id IN ($placeholders)",
				...$new_profile_ids
			);
			$existing_new_profiles = $wpdb->get_col( $sql );
			
			// Return old profile IDs whose new profiles still exist
			foreach ( $converted_profiles as $old_id => $new_id ) {
				if ( in_array( $new_id, $existing_new_profiles ) ) {
					$old_profiles_to_hide[] = $old_id;
				}
			}
		}

		return $old_profiles_to_hide;
	}

	public static function duplicateProfile($id) {
		$base = new WPLA_AmazonProfile( $id );
		$dupe = clone $base;
		$dupe->profile_id = null;
		$dupe->profile_name = $dupe->profile_name . ' (duplicated)';
		return $dupe->add();
	}

	// add profile
	public function add() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		// echo "<pre>";print_r($this);echo"</pre>";die();

		$data = array();
		foreach ( $this->fieldnames as $key ) {
			if ( isset( $this->$key ) && ! is_null( $this->$key ) ) {
				$this->$key = maybe_serialize( $this->$key );
				$data[ $key ] = $this->$key;
			} 
		}

		if ( sizeof( $data ) > 0 ) {
			$result = $wpdb->insert( $table, $data );
			echo $wpdb->last_error;

			return $wpdb->insert_id;		
		}

	} // add()

	// update profile
	public function update() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$data = array();
		foreach ( $this->fieldnames as $key ) {
			if ( isset( $this->$key ) && ! is_null( $this->$key ) ) {
				if ( in_array( $key, ['details', 'fields'] ) && !is_string( $this->$key ) ) {
					$data[ $key ] = maybe_serialize( $this->$key );
				} else {
					$data[ $key ] = $this->$key;
				}
			} 
		}

		// check if MySQL server has gone away and reconnect if required - WP 3.9+
		if ( method_exists( $wpdb, 'check_connection') ) $wpdb->check_connection();
		

		if ( sizeof( $data ) > 0 ) {
			$result = $wpdb->update( $table, $data, array( 'profile_id' => $this->id ) );
			echo $wpdb->last_error;
		}

	} // update()



	// populate profile fields from data array
	public function fillFromArray( $data ) {

		foreach ( $this->fieldnames as $key ) {
			if ( isset( $data[$key] ) ) {
				$this->$key = $data[ $key ];
			} 
		}

	} // fillFromArray()


	public function delete() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		if ( ! $this->id ) return;

		$wpdb->delete( $table, array( 'profile_id' => $this->id ), array( '%d' ) );
		echo $wpdb->last_error;

	} // delete()



	public function processProfilePrice( $price ) {
		if ( ! $this->id ) return $price;
		if ( ! $price ) return false;

		$details              = maybe_unserialize( $this->details );
		$price_add_percentage = isset( $details['price_add_percentage'] ) ? $details['price_add_percentage'] : false;
		$price_add_amount     = isset( $details['price_add_amount'] ) ? $details['price_add_amount'] : false;

		if ( $price_add_percentage ) {
			$price += $price * floatval( $price_add_percentage ) / 100;
		}

		if ( $price_add_amount ) {
			$price += floatval( $price_add_amount );
		}

		$price = number_format( $price, 2, null, '' );
		return $price;
	} // processProfilePrice()

	public function reverseProfilePrice( $price ) {
		if ( ! $this->id ) return $price;
		if ( ! $price ) return false;

		$details              = maybe_unserialize( $this->details );
		$price_add_percentage = isset( $details['price_add_percentage'] ) ? $details['price_add_percentage'] : false;
		$price_add_amount     = isset( $details['price_add_amount'] ) ? $details['price_add_amount'] : false;

		if ( $price_add_amount ) {
			$price -= floatval( $price_add_amount );
		}

		if ( $price_add_percentage ) {
			// reversed: $newprice = $price + $price * $price_add_percentage / 100;
			$net_price = $price - $price / ( 1 + ( 1 / ( floatval($price_add_percentage) / 100 ) ) );	// calc net from gross amount
			$price = $net_price;
		}

		$price = round($price,2);
		return $price;
	} // reverseProfilePrice()



	public function getPageItems( $current_page, $per_page ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$orderby  = (!empty($_REQUEST['orderby'])) ? esc_sql( wpla_clean($_REQUEST['orderby']) ) : 'profile_name'; //If no sort, default to title
		$order    = (!empty($_REQUEST['order']))   ? esc_sql( wpla_clean($_REQUEST['order'])   ) : 'asc'; //If no order, default to asc
		$offset   = ( $current_page - 1 ) * $per_page;
		$per_page = esc_sql( $per_page );

        $join_sql  = '';
        $where_sql = 'WHERE 1 = 1 ';

        // filter search_query
		$search_query = isset($_REQUEST['s']) ? esc_sql( wpla_clean($_REQUEST['s']) ) : false;
		if ( $search_query ) {
			$where_sql .= "
				AND  ( profile_name        LIKE '%".$search_query."%'
					OR profile_description LIKE '%".$search_query."%' )
			";
		} 

        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $table
            $join_sql 
	        $where_sql
			ORDER BY $orderby $order
            LIMIT $offset, $per_page
		", ARRAY_A);

		// get total items count - if needed
		if ( ( $current_page == 1 ) && ( count( $items ) < $per_page ) ) {
			$this->total_items = count( $items );
		} else {
			$this->total_items = $wpdb->get_var("
				SELECT COUNT(*)
				FROM $table
	            $join_sql 
    	        $where_sql
				ORDER BY $orderby $order
			");			
		}

		return $items;
	} // getPageItems()

	public function getAttributeNamesMap() {
		return [
			'feed_product_type' => 'product_type',
			'standard_price'    => 'purchasable_offer[0][our_price][schedule][0][value_with_tax]',
			'quantity'          => 'fulfillment_availability[0][quantity]',
			'main_image_url'    => 'main_product_image_locator[0][media_location]',
			'other_image_url1'    => 'other_product_image_locator1[0][media_location]',
			'other_image_url2'    => 'other_product_image_locator2[0][media_location]',
			'other_image_url3'    => 'other_product_image_locator3[0][media_location]',
			'other_image_url4'    => 'other_product_image_locator4[0][media_location]',
			'other_image_url5'    => 'other_product_image_locator5[0][media_location]',
			'other_image_url6'    => 'other_product_image_locator6[0][media_location]',
			'other_image_url7'    => 'other_product_image_locator7[0][media_location]',
			'other_image_url8'    => 'other_product_image_locator8[0][media_location]',

			// unknowns
			'shaft_style_type' => 'shaft_style_type',
			'fulfillment_center_id' => 'fulfillment_center_id',
		];
	}

} // WPLA_AmazonProfile()

