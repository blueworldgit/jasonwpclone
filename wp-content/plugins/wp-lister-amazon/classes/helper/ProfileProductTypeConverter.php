<?php

namespace WPLab\Amazon\Helper;

use WPLab\Amazon\Core\AmazonProductType;
use WPLab\Amazon\Models\AmazonProductTypesModel;
use WPLab\Amazon\SellingPartnerApi\Model\ProductTypeDefinitionsV20200901\ProductType;

/**
 * Helper class that converts Flat File profile fields to the new Product Types API
 * @todo Add a routine to check for CSV updates
 * @todo Add a tool to update the CSV on demand
 */
class ProfileProductTypeConverter {

	private string $remote_file = 'https://update.wplister.com/wpla/json-mapping.csv';
	private string $file_path;
	private string $file;
	private \WPLA_AmazonProfile $profile;

	private $product_type;
	
	/**
	 * Static cache for the mapping array to prevent multiple loads during the same request
	 * @var array|null
	 */
	private static $map_cache = null;

	/**
	 * Initialize the converter with optional profile and product type
	 *
	 * @param \WPLA_AmazonProfile|null $profile The Amazon profile instance
	 * @param string|null $product_type The product type identifier
	 */
	public function __construct( $profile = null, $product_type = null ) {
		$this->file_path = $this->getFilePath();

		if ( is_null( $profile ) ) {
			$profile = new \WPLA_AmazonProfile();
		}

		if ( !is_null( $product_type ) ) {
			$this->product_type = $product_type;
		}

		$this->profile = $profile;
	}

	/**
	 * Set the Amazon profile instance
	 *
	 * @param \WPLA_AmazonProfile $profile The Amazon profile instance
	 * @return $this For method chaining
	 */
	public function setProfile( $profile ) {
		$this->profile = $profile;
		return $this;
	}

	/**
	 * Get the current Amazon profile instance
	 *
	 * @return \WPLA_AmazonProfile The Amazon profile instance
	 */
	public function getProfile() {
		return $this->profile;
	}

	/**
	 * Set the product type identifier
	 *
	 * @param string $product_type The product type identifier
	 * @return $this For method chaining
	 */
	public function setProductType( $product_type ) {
		$this->product_type = $product_type;
		return $this;
	}

	/**
	 * Get the current product type identifier
	 *
	 * @return string|null The product type identifier
	 */
	public function getProductType() {
		return $this->product_type;
	}

	/**
	 * Map feed template fields to their appropriate product type properties
	 *
	 * @param \WPLA_AmazonProfile $profile
	 *
	 * @return \WPLA_AmazonProfile
	 */
	public function convertFields() {
		$this->setMarketplaceFromTemplate();

		// assign feed_product_type to the product type profile property
		$product_type = $this->product_type ?? $this->getProductTypeFromProfile();
		$this->profile->product_type = $product_type;
		$this->profile->fields = $this->convertFromArray( maybe_unserialize( $this->profile->fields ) );

		return $this->profile;
	}

	/**
	 * Convert an entire profile from old format to new product type format and save it
	 *
	 * @return \WPLA_AmazonProfile The converted and updated profile
	 */
	public function convertProfile() {
		// handle double-serialized fields
		$this->profile->fields = maybe_unserialize( $this->profile->fields );
		$this->convertFields();
		$this->profile->profile_name = $this->replaceLastString( '(duplicated)', '(Converted)', $this->profile->profile_name );
		$this->profile->tpl_id = 0;
		$this->profile->update();
		return $this->profile;
	}

	/**
	 * Convert product-level attributes to product type properties
	 *
	 * @param array|mixed $fields Array of fields to convert, or any other type (will return empty array)
	 *
	 * @return array Converted fields array, or empty array if input is not an array
	 */
	public function convertFromArray( $fields ) {
		$old_fields = [];
		$unmapped   = [];

		// Handle non-array inputs (empty strings, null, etc.)
		if ( !is_array( $fields ) ) {
			return [];
		}

		if ( !$this->needsConversion( $fields ) ) {
			return $fields;
		}

		// This is for checking if the field names have already been mapped
		$map        = $this->getMap();
		$map_values = array_values( $map );

		foreach ( $fields as $key => $value ) {
			if ( in_array( $key, $map_values ) || strpos( $key, '[marketplace_id]' ) || strpos( $key, '[language_tag]' ) ) {
				// already mapped
				continue;
			}

			if ( isset( $map[ $key ] ) ) {
				// found a matching field name!
				$new_key            = $map[ $key ];
				$old_fields[ $key ] = $value;

				// Apply unit conversion for unit fields
				$unit_keys = [
					'_unit_of_measure', '_measurement', 'weight', 'dimensions',
				];
				foreach ( $unit_keys as $unit_key ) {
					if ( strpos( $key, $unit_key ) !== false ) {
						$value = $this->convertUnit( $value );
					}
				}
				
				// Apply field-specific conversions
				$value = $this->convertFieldValue( $key, $value );

				$fields[ $new_key ] = $value;
			} else {
				$unmapped[ $key ] = $value;
			}
			unset( $fields[ $key ] );
		}
		
		// Handle ASIN conversion after all fields are processed
		$this->handleAsinConversion( $fields );
		
		$fields['__unmapped']   = $unmapped;
		$fields['__old_fields'] = $old_fields;

		return $fields;
	}

	/**
	 * Get the field mapping array from CSV file
	 * Downloads the mapping file if it doesn't exist locally
	 *
	 * @return array Associative array mapping old field names to new field names
	 */
	public function getMap() {
		if ( !$this->mapFileExists() ) {
			$this->downloadMapFile();
		}

		return $this->loadMapFile();
	}

	/**
	 * Get a specific field mapping without loading the entire map
	 * More memory-efficient for single key lookups
	 *
	 * @param string $old_field_name The old field name to look up
	 * @return string|null The new field name or null if not found
	 */
	public function getFieldMapping( $old_field_name ) {
		if ( !$this->mapFileExists() ) {
			$this->downloadMapFile();
		}

		// Check static cache first
		if ( self::$map_cache !== null ) {
			return self::$map_cache[ $old_field_name ] ?? null;
		}

		// Check hardcoded mappings first (fast lookup)
		$hardcoded = $this->getHardcodedMapping( $old_field_name );
		if ( $hardcoded !== null ) {
			return $hardcoded;
		}

		// Check cache file
		$cache_file = $this->getCacheFilePath();
		if ( $this->isCacheFileValid( $cache_file ) ) {
			$cached_data = unserialize( file_get_contents( $cache_file ) );
			self::$map_cache = $cached_data;
			return $cached_data[ $old_field_name ] ?? null;
		}

		// Last resort: search in CSV file directly (avoid full load if possible)
		return $this->searchInCsvFile( $old_field_name );
	}

	/**
	 * Get the marketplace ID for a given feed template
	 *
	 * @param int $tpl_id The feed template ID
	 * @return string|null The marketplace ID or null if not found
	 */
	public function getFeedTemplateMarketplace( $tpl_id ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare(
			"SELECT m.marketplace_id
			FROM {$wpdb->prefix}amazon_feed_templates t, {$wpdb->prefix}amazon_markets m
			WHERE t.site_id = m.id
			AND t.id = %d",
			$tpl_id
		) );
	}

	/**
	 * @param $tpl_id
	 *
	 * @return false|AmazonProductType[]
	 */
	public function getRecommendedProductTypeFromTemplate( $tpl_id ) {
		global $wpdb;

		$info = $wpdb->get_row( $wpdb->prepare(
			"SELECT t.title, m.marketplace_id
			FROM {$wpdb->prefix}amazon_feed_templates t, {$wpdb->prefix}amazon_markets m
			WHERE t.site_id = m.id
			AND t.id = %d",
			$tpl_id
		) );

		if ( !$info ) {
			return false;
		}

		if ( in_array( strtolower( $info->title ), ['offer', 'inventoryloader', 'inventory loader'] ) ) {
			$type = new \WPLab\Amazon\Core\AmazonProductType();
			$type
				->setDisplayName( 'PRODUCT')
				->setProductType('PRODUCT');
			return [
				$type
			];
		}

		$account_id = \WPLA_AmazonAccount::getAccountWithMarketplace( $info->marketplace_id );

		if ( !$account_id ) {
			$account_id = get_option( 'wpla_default_account_id', 1 );
		}
		$api = new \WPLA_Amazon_SP_API( $account_id );
		$result = $api->searchDefinitionsProductTypes( [$info->marketplace_id], $info->title );

		if ( \WPLA_Amazon_SP_API::isError( $result ) ) {
			WPLA()->logger->error( 'Error in searchDefinitionsProductTypes. '. $result->ErrorMessage );
			return false;
		}

		$recommendations = [];

		foreach ( $result->getProductTypes() as $item ) {
			$product_type = new AmazonProductType();
			$product_type
				->setDisplayName( $item->getDisplayName() )
				->setProductType( $item->getName() );
			$recommendations[] = $product_type;
		}

		return $recommendations;
	}

	/**
	 * Look for products that use custom feed templates and return them grouped by the template ID
	 * 
	 * @return array Associative array with template IDs as keys and arrays of product IDs as values
	 */
	public function getAllProductsUsingFeedTemplates() {
		global $wpdb;

		$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wpla_custom_feed_tpl_id' AND meta_value <> ''" );
		$products = [];

		foreach ( $rows as $row ) {
			// Only show products and skip `product_variations` post types (by Aniket Patel)
			if ( get_post_meta( $row->post_id, '_wpla_custom_product_type', true ) || get_post_type( $row->post_id ) !== 'product' ) {
				continue;
			}

			$products[ $row->meta_value ][] = $row->post_id;
		}

		return $products;
	}

	/**
	 * Look for products that use a specific custom feed template
	 * 
	 * @param int $tpl_id The feed template ID to search for
	 * @return array Array of product IDs using the specified template
	 */
	public function getProductsUsingFeedTemplate( $tpl_id ) {
		global $wpdb;

		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT post_id, meta_value 
			FROM {$wpdb->postmeta} 
			WHERE meta_key = '_wpla_custom_feed_tpl_id' 
			AND meta_value = %d", $tpl_id
		) );
		$products = [];

		foreach ( $rows as $row ) {
			if ( get_post_meta( $row->post_id, '_wpla_custom_product_type', true ) ) {
				continue;
			}
			$products[] = $row->post_id;
		}

		return $products;
	}

	/**
	 * Set the marketplace ID on the profile based on the associated feed template
	 * 
	 * @return void
	 */
	private function setMarketplaceFromTemplate() {
		global $wpdb;

		if ( empty( $this->profile->marketplace_id ) && $this->profile->tpl_id ) {
			$marketplace = $wpdb->get_var($wpdb->prepare(
				"SELECT m.marketplace_id
					FROM {$wpdb->prefix}amazon_markets m, {$wpdb->prefix}amazon_feed_templates t
					WHERE m.id = t.site_id
					AND t.id = %d",
				$this->profile->tpl_id) );

			if ( $marketplace ) {
				$this->profile->marketplace_id = $marketplace;
			}
		}
	}

	/**
	 * Extract and return the product type from the profile's feed_product_type field
	 * Removes the field from the profile fields after extraction
	 *
	 * @return string|null The product type in uppercase, or null if not found
	 */
	private function getProductTypeFromProfile() {
		$product_type = null;

		// assign feed_product_type to the product type profile property
		if ( empty( $this->profile->product_type ) && isset( $this->profile->fields['feed_product_type'] ) ) {
			$product_type = strtoupper( $this->profile->fields['feed_product_type'] );
			unset( $this->profile->fields['feed_product_type'] );
		}

		return $product_type;
	}

	/**
	 * Replace the last occurrence of a string in the given string
	 *
	 * @param string $search The string to search for
	 * @param string $replace The replacement string
	 * @param string $str The string to search in
	 * @return string The modified string
	 */
	private function replaceLastString( $search , $replace , $str ) {
	    if( ( $pos = strrpos( $str , $search ) ) !== false ) {
	        $search_length  = strlen( $search );
	        $str    = substr_replace( $str , $replace , $pos , $search_length );
	    }
		return $str;
	}

	/**
	 * Download the field mapping CSV file from the remote server
	 * Invalidates the cache when a new file is downloaded
	 * 
	 * @todo Add a routine to check for CSV updates
	 * @return bool True if download and file move was successful, false otherwise
	 */
	public function downloadMapFile() {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		\WP_Filesystem();
		$temp_filename = download_url( $this->remote_file );

		if ( is_wp_error( $temp_filename ) ) {
			return false;
		}

		$success = rename( $temp_filename, $this->file_path );
		
		if ( $success ) {
			// Invalidate the cache when new file is downloaded
			$this->clearMapCache();
			WPLA()->logger->info( 'Product type converter map file downloaded and cache cleared' );
		} else {
			// Log the error when rename fails
			WPLA()->logger->error( 'Failed to rename temporary file from ' . $temp_filename . ' to ' . $this->file_path );
			
			// Clean up the temporary file since rename failed
			if ( file_exists( $temp_filename ) ) {
				unlink( $temp_filename );
				WPLA()->logger->info( 'Cleaned up temporary file: ' . $temp_filename );
			}
		}

		return $success;
	}

	/**
	 * Check if the mapping CSV file exists locally
	 *
	 * @return bool True if the file exists, false otherwise
	 */
	public function mapFileExists() {
		return file_exists( $this->file_path );
	}

	/**
	 * Clear the cached mapping data
	 * 
	 * @return bool True if cache was cleared successfully
	 */
	public function clearMapCache() {
		// Clear static cache
		self::$map_cache = null;
		
		// Clear file cache
		$cache_file = $this->getCacheFilePath();
		if ( file_exists( $cache_file ) ) {
			$deleted = unlink( $cache_file );
			if ( $deleted ) {
				WPLA()->logger->info( 'Product type converter map cache file cleared' );
			}
			return $deleted;
		}
		
		return true;
	}

	/**
	 * Load and parse the mapping CSV file into an associative array
	 * Uses file-based caching and static properties to prevent memory issues
	 * 
	 * @return array Associative array mapping old field names to new field names
	 */
	private function loadMapFile() {
		if ( !$this->mapFileExists() ) {
			return [];
		}

		// Check static cache first (request-level caching)
		if ( self::$map_cache !== null ) {
			return self::$map_cache;
		}

		// Check for serialized cache file
		$cache_file = $this->getCacheFilePath();
		if ( $this->isCacheFileValid( $cache_file ) ) {
			WPLA()->logger->info( 'Using cached product type converter map from file' );
			$cached_data = unserialize( file_get_contents( $cache_file ) );
			self::$map_cache = $cached_data;
			return $cached_data;
		}

		// Log memory usage before loading
		$memory_before = memory_get_usage( true );
		WPLA()->logger->info( 'Loading product type converter map from CSV. Memory before: ' . size_format( $memory_before ) );

		$csv = [];
		$fp = fopen( $this->file_path, 'r' );

		if ( $fp ) {
			while ( ! feof( $fp ) ) {
				$line = fgets( $fp );
				if ( $line === false || trim( $line ) === '' ) {
					continue;
				}

				$row = explode( ',', trim( $line ) );

				// Make sure there are at least 3 columns
				if ( count( $row ) >= 3 ) {
					$old_key = $row[0];
					$new_key = $this->convertPathToFieldname( $row[2] );
					$csv[ $old_key ] = $new_key;
				}
			}

			fclose( $fp );
		}

		// Add fields that are not in the map file
		$csv = $this->addHardcodedMappings( $csv );

		// Remove the header
		array_shift( $csv );

		// Apply filters
		$csv = apply_filters( 'wpla_product_type_converter_map', $csv );

		// Cache the result to a file
		$this->saveCacheFile( $cache_file, $csv );

		// Cache in static property for this request
		self::$map_cache = $csv;

		// Log memory usage after loading
		$memory_after = memory_get_usage( true );
		$memory_used = $memory_after - $memory_before;
		WPLA()->logger->info( 'Product type converter map loaded. Memory used: ' . size_format( $memory_used ) . ', Total entries: ' . count( $csv ) );

		return $csv;
	}

	/**
	 * Get the path to the cache file
	 * 
	 * @return string The full path to the cache file
	 */
	private function getCacheFilePath() {
		$upload_dir = wp_upload_dir();
		$basedir_name = 'wp-lister/';
		return $upload_dir['basedir'] . '/' . $basedir_name . 'product-types-map.cache';
	}

	/**
	 * Check if the cache file is valid based on modification time
	 * 
	 * @param string $cache_file The cache file path
	 * @return bool True if cache is still valid, false otherwise
	 */
	private function isCacheFileValid( $cache_file ) {
		// Always return false when debug mode is enabled (level 7)
		if ( defined('WPLA_DEBUG') && WPLA_DEBUG >= 7 ) {
			WPLA()->logger->info( 'Product type converter map cache disabled due to DEBUG mode' );
			return false;
		}
		
		if ( !file_exists( $cache_file ) ) {
			return false;
		}

		// Check if CSV file has been modified since cache was created
		$csv_mtime = filemtime( $this->file_path );
		$cache_mtime = filemtime( $cache_file );
		
		if ( $csv_mtime > $cache_mtime ) {
			WPLA()->logger->info( 'Product type converter map cache invalidated due to CSV file modification' );
			return false;
		}

		return true;
	}

	/**
	 * Save the mapping data to a cache file
	 * 
	 * @param string $cache_file The cache file path
	 * @param array $data The mapping data to cache
	 * @return bool True if cache was saved successfully
	 */
	private function saveCacheFile( $cache_file, $data ) {
		// Ensure directory exists
		$cache_dir = dirname( $cache_file );
		if ( !is_dir( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
		}

		$serialized_data = serialize( $data );
		$bytes_written = file_put_contents( $cache_file, $serialized_data );
		
		if ( $bytes_written !== false ) {
			WPLA()->logger->info( 'Product type converter map cached to file. Size: ' . size_format( $bytes_written ) );
			return true;
		}

		return false;
	}

	/**
	 * Add hardcoded field mappings that are not in the CSV file
	 * 
	 * @param array $csv The current CSV mappings
	 * @return array The CSV mappings with hardcoded mappings added
	 */
	private function addHardcodedMappings( $csv ) {
		$hardcoded = $this->getHardcodedMappings();
		return array_merge( $csv, $hardcoded );
	}

	/**
	 * Get hardcoded field mappings as an array
	 * 
	 * @return array Hardcoded field mappings
	 */
	private function getHardcodedMappings() {
		return [
			'fulfillment_latency'               => 'fulfillment_availability[0][lead_time_to_ship_max_days]',
			'standard_price'                    => 'purchasable_offer[0][our_price][0][schedule][0][value_with_tax]',
			'sale_price'                        => 'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]',
			'sale_from_date'                    => 'purchasable_offer[0][discounted_price][0][schedule][0][start_at]',
			'sale_end_date'                     => 'purchasable_offer[0][discounted_price][0][schedule][0][end_at]',
			'package_height_unit_of_measure'   => 'item_package_dimensions[0][height][unit]',
			'package_width_unit_of_measure'    => 'item_package_dimensions[0][width][unit]',
			'package_length_unit_of_measure'   => 'item_package_dimensions[0][length][unit]',
			'package_weight_unit_of_measure'   => 'item_package_weight[0][unit]',
			'item_height_unit_of_measure'      => 'item_dimensions[0][height][unit]',
			'item_width_unit_of_measure'       => 'item_dimensions[0][width][unit]',
			'item_length_unit_of_measure'      => 'item_dimensions[0][length][unit]',
		];
	}

	/**
	 * Get a specific hardcoded mapping
	 * 
	 * @param string $old_field_name The old field name to look up
	 * @return string|null The new field name or null if not found
	 */
	private function getHardcodedMapping( $old_field_name ) {
		$hardcoded = $this->getHardcodedMappings();
		return $hardcoded[ $old_field_name ] ?? null;
	}

	/**
	 * Search for a specific field mapping in the CSV file without loading the entire file
	 * 
	 * @param string $old_field_name The old field name to search for
	 * @return string|null The new field name or null if not found
	 */
	private function searchInCsvFile( $old_field_name ) {
		$fp = fopen( $this->file_path, 'r' );
		if ( !$fp ) {
			return null;
		}

		// Skip header line
		fgets( $fp );

		while ( !feof( $fp ) ) {
			$line = fgets( $fp );
			if ( $line === false || trim( $line ) === '' ) {
				continue;
			}

			$row = explode( ',', trim( $line ) );
			if ( count( $row ) >= 3 && $row[0] === $old_field_name ) {
				fclose( $fp );
				return $this->convertPathToFieldname( $row[2] );
			}
		}

		fclose( $fp );
		return null;
	}

	/**
	 * Convert a JSON path from the API schema to a form field name
	 * 
	 * @param string $path The JSON path (e.g., '/attributes/purchasable_offer/0/our_price')
	 * @return string The converted field name (e.g., 'purchasable_offer[0][our_price]')
	 */
	private function convertPathToFieldname( $path ) {
		// remove the /attributes/ prefix
		$path = str_replace( '/attributes/', '', $path );

		$parts  = explode( '/', $path );
		$fields = $parts[0];

		// remove the attribute name;
		array_shift( $parts );

		foreach ( $parts as $part ) {
			$fields .= '['. $part .']';
		}

		return $fields;
	}

	/**
	 * Convert old unit values to new unit values
	 * 
	 * @param string $old_unit The old unit value
	 * @return string The new unit value
	 */
	private function convertUnit( $old_unit ) {
		// Ensure we have a string value to work with
		if ( ! is_string( $old_unit ) ) {
			WPLA()->logger->info('convertUnit: Invalid old_unit type: ' . gettype($old_unit) . ', value: ' . print_r($old_unit, true));
			return $old_unit;
		}
		
		$unit_mapping = [
			// Length/Distance units (old => new)
			'Angstrom'                  => 'angstrom',
			'Mils'                      => 'mils',
			'Yards'                     => 'yards', 
			'Picometer'                 => 'picometer',
			'Miles'                     => 'miles',
			'DM'                        => 'decimeters',
			'MM'                        => 'millimeters',
			'M'                         => 'meters',
			'IN'                        => 'inches',
			'FT'                        => 'feet',
			'CM'                        => 'centimeters',
			'Hundredths-Inches'         => 'hundredths_inches',
			'Nanometer'                 => 'nanometer',
			'uM'                        => 'micrometer',
			'Kilometers'                => 'kilometers',
			'Millimeters'               => 'millimeters',
			'Meters'                    => 'meters',
			'Inches'                    => 'inches',
			'Feet'                      => 'feet',
			'Centimeters'               => 'centimeters',
			'Micron'                    => 'micrometer',
			'Decimeters'                => 'decimeters',
			
			// Weight units (old => new)
			'LB'                        => 'pounds',
			'KG'                        => 'kilograms',
			'GR'                        => 'grams',
			'Hundredths Pounds'         => 'hundredths_pounds',
			'MG'                        => 'milligrams',
			'Tons'                      => 'tons',
			'OZ'                        => 'ounces',
		];

		return isset( $unit_mapping[ $old_unit ] ) ? $unit_mapping[ $old_unit ] : strtolower( $old_unit );
	}

	/**
	 * Convert the Supplier Dangerous Goods values to their new format
	 *
	 * @param string $value The display value from old feed template
	 * @return string The corresponding value for the new system
	 */
	private function convertSupplierDeclaredDangerousGoods( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		// The new value just needs to be lowercase and the spaces replaced by an underscore
		return strtolower( str_replace( ' ', '_', $value) );
	}

	/**
	 * Convert California Proposition 65 compliance type values to their new format
	 *
	 * @param string $value The display value from old feed template
	 * @return string The corresponding value for the new system
	 */
	private function convertCaliforniaProposition65ComplianceType( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		// Convert title case with spaces to lowercase with underscores
		return strtolower( str_replace( ' ', '_', $value) );
	}

	/**
	 * Convert merchant shipping group display values to IDs using schema-based lookup
	 * 
	 * @param string $display_value The display value from old feed template
	 * @return string The corresponding ID for the new system
	 */
	private function convertShippingGroupValue( $display_value ) {
		if ( empty( $display_value ) ) {
			return $display_value;
		}
		
		// Get the allowed values from the product type schema
		$allowed_values = $this->getFieldEnumOptions( 'merchant_shipping_group' );
		
		if ( empty( $allowed_values ) ) {
			// Fallback to original value if no schema options found
			return $display_value;
		}
		
		// Try exact match against display names
		foreach ( $allowed_values as $id => $display_name ) {
			if ( $display_name === $display_value ) {
				return $id;
			}
		}
		
		// Try case-insensitive match against display names
		$display_value_lower = strtolower( $display_value );
		foreach ( $allowed_values as $id => $display_name ) {
			if ( strtolower( $display_name ) === $display_value_lower ) {
				return $id;
			}
		}
		
		// Try partial matches for common patterns
		foreach ( $allowed_values as $id => $display_name ) {
			$display_name_lower = strtolower( $display_name );
			if ( stripos( $display_name_lower, $display_value_lower ) !== false || 
				 stripos( $display_value_lower, $display_name_lower ) !== false ) {
				return $id;
			}
		}
		
		// If no match found, return original value (might already be an ID)
		return $display_value;
	}
	
	/**
	 * Convert field values based on field type requirements
	 * 
	 * @param string $key The field key
	 * @param string $value The original value
	 * @return string The converted value
	 */
	private function convertFieldValue( $key, $value ) {
		// Date field conversion - convert MM/DD/YYYY to YYYY-MM-DD for Amazon API compatibility
		if ( in_array( $key, ['sale_from_date', 'sale_end_date'] ) || strpos( $key, 'start_at' ) !== false || strpos( $key, 'end_at' ) !== false ) {
			$value = \WPLA_DateTimeHelper::convertDateFormatForAmazon( $value );
		}
		
		// Boolean field normalization - handle all old boolean formats
		if ( preg_match('/\[0\]\[value\]$/', $key) ) {
			$value_lower = strtolower(trim($value));
			
			// Convert all boolean variations to lowercase strings
			if ( in_array($value_lower, ['true', '1', 'yes']) ) {
				return 'true';
			} elseif ( in_array($value_lower, ['false', '0', 'no']) ) {
				return 'false';
			}
			// Empty string ('') remains empty - means "not set"
		}
		
		// Merchant Shipping Group conversion
		if ( strpos( $key, 'merchant_shipping_group' ) !== false ) {
			return $this->convertShippingGroupValue( $value );
		}
		
		// Country of Origin conversion  
		if ( strpos( $key, 'country_of_origin' ) !== false ) {
			return $this->convertCountryOfOriginValue( $value );
		}

		// Country of Origin conversion
		if ( strpos( $key, 'supplier_declared_dg_hz_regulation' ) !== false ) {
			return $this->convertSupplierDeclaredDangerousGoods( $value );
		}
		
		// California Proposition 65 compliance type conversion
		if ( strpos( $key, 'california_proposition_65' ) !== false && strpos( $key, 'compliance_type' ) !== false ) {
			return $this->convertCaliforniaProposition65ComplianceType( $value );
		}
		
		// Future field conversions can be added here
		// if ( strpos( $key, 'another_field' ) !== false ) {
		//     return $this->convertAnotherFieldValue( $value );
		// }
		
		return $value; // No conversion needed
	}
	
	/**
	 * Convert country names to ISO 2-letter codes using schema-based lookup
	 * 
	 * @param string $country_value The country name or code from old feed template
	 * @return string The corresponding ISO code for SP-API
	 */
	private function convertCountryOfOriginValue( $country_value ) {
		if ( empty( $country_value ) ) {
			return $country_value;
		}
		
		if ( ! is_string( $country_value ) ) {
			WPLA()->logger->info('convertCountryOfOriginValue: Invalid country_value type: ' . gettype($country_value) . ', value: ' . print_r($country_value, true));
			return $country_value;
		}
		
		// Get the allowed values from the product type schema
		$allowed_values = $this->getFieldEnumOptions( 'country_of_origin' );
		
		if ( empty( $allowed_values ) ) {
			// Fallback to hardcoded mapping if no schema options found
			return $this->getCountryCodeFallback( $country_value );
		}
		
		// Try exact match against display names
		foreach ( $allowed_values as $code => $country_name ) {
			if ( $country_name === $country_value ) {
				return $code;
			}
		}
		
		// Try case-insensitive match against display names
		$country_value_lower = strtolower( $country_value );
		foreach ( $allowed_values as $code => $country_name ) {
			if ( strtolower( $country_name ) === $country_value_lower ) {
				return $code;
			}
		}
		
		// Try partial matches for common patterns
		foreach ( $allowed_values as $code => $country_name ) {
			$country_name_lower = strtolower( $country_name );
			if ( stripos( $country_name_lower, $country_value_lower ) !== false || 
				 stripos( $country_value_lower, $country_name_lower ) !== false ) {
				return $code;
			}
		}
		
		// Fallback to hardcoded mapping
		$fallback_code = $this->getCountryCodeFallback( $country_value );
		if ( $fallback_code !== $country_value ) {
			return $fallback_code;
		}
		
		// If no match found, return original value (might already be a code)
		return $country_value;
	}
	
	/**
	 * Fallback country name to ISO code mapping for common cases
	 * 
	 * @param string $country_value The country name
	 * @return string The ISO code or original value if not found
	 */
	private function getCountryCodeFallback( $country_value ) {
		// Ensure we have a string value to work with
		if ( ! is_string( $country_value ) ) {
			WPLA()->logger->info('getCountryCodeFallback: Invalid country_value type: ' . gettype($country_value) . ', value: ' . print_r($country_value, true));
			return $country_value;
		}
		
		$country_mapping = [
			// Common country names to ISO codes
			'United States' => 'US',
			'United States of America' => 'US',
			'USA' => 'US',
			'Canada' => 'CA',
			'Mexico' => 'MX',
			'United Kingdom' => 'GB',
			'Great Britain' => 'GB',
			'UK' => 'GB',
			'Germany' => 'DE',
			'France' => 'FR',
			'Italy' => 'IT',
			'Spain' => 'ES',
			'Japan' => 'JP',
			'China' => 'CN',
			'India' => 'IN',
			'Australia' => 'AU',
			'Brazil' => 'BR',
			'Netherlands' => 'NL',
			'Belgium' => 'BE',
			'Switzerland' => 'CH',
			'Austria' => 'AT',
			'Sweden' => 'SE',
			'Norway' => 'NO',
			'Denmark' => 'DK',
			'Finland' => 'FI',
			'Poland' => 'PL',
			'South Korea' => 'KR',
			'Singapore' => 'SG',
			'Taiwan' => 'TW',
			'Hong Kong' => 'HK',
			'Thailand' => 'TH',
			'Malaysia' => 'MY',
			'Indonesia' => 'ID',
			'Philippines' => 'PH',
			'Vietnam' => 'VN',
			'Turkey' => 'TR',
			'Israel' => 'IL',
			'South Africa' => 'ZA',
			'Egypt' => 'EG',
			'United Arab Emirates' => 'AE',
			'Saudi Arabia' => 'SA',
			'Russia' => 'RU',
			'Czech Republic' => 'CZ',
			'Hungary' => 'HU',
			'Romania' => 'RO',
			'Bulgaria' => 'BG',
			'Croatia' => 'HR',
			'Slovakia' => 'SK',
			'Slovenia' => 'SI',
			'Estonia' => 'EE',
			'Latvia' => 'LV',
			'Lithuania' => 'LT',
			'Ireland' => 'IE',
			'Portugal' => 'PT',
			'Greece' => 'GR',
			'Cyprus' => 'CY',
			'Malta' => 'MT',
			'Luxembourg' => 'LU',
		];
		
		// Try exact match
		if ( isset( $country_mapping[ $country_value ] ) ) {
			return $country_mapping[ $country_value ];
		}
		
		// Try case-insensitive match
		$country_value_lower = strtolower( $country_value );
		foreach ( $country_mapping as $name => $code ) {
			if ( strtolower( $name ) === $country_value_lower ) {
				return $code;
			}
		}
		
		// Return original value if no match found
		return $country_value;
	}
	
	/**
	 * Get enum options for a specific field from the product type schema
	 * 
	 * @param string $field_name The field name to look for
	 * @return array Array of [id => display_name] or empty array if not found
	 */
	private function getFieldEnumOptions( $field_name ) {
		// Get current product type
		$product_type = $this->product_type ?? $this->getProductTypeFromProfile();
		
		if ( empty( $product_type ) ) {
			return [];
		}
		
		// Get marketplace from profile
		$marketplace_id = $this->profile->marketplace_id ?? 'ATVPDKIKX0DER'; // Default to US
		
		// Load product type schema
		try {
			$type_mdl = new AmazonProductTypesModel();
			$type_obj = $type_mdl->getDefinitionsProductType( $product_type, $marketplace_id, false, $this->profile->account_id );

			$schema = $type_obj->getSchema();
			/*global $wpdb;
			$table = $wpdb->prefix . 'amazon_product_types';
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT schema FROM $table WHERE product_type = %s AND marketplace_id = %s",
				$product_type,
				$marketplace_id
			));
			
			if ( ! $row || empty( $row->schema ) ) {
				return [];
			}*/
			
			$schema = json_decode( $schema, true );
			if ( ! $schema ) {
				return [];
			}
			
			// Find the field in the schema properties
			$field_schema = $this->findFieldInSchema( $schema, $field_name );
			
			if ( ! $field_schema ) {
				return [];
			}
			
			// Extract enum options using shared utility method
			return AmazonSchemaFormGenerator::extractEnumOptionsFromSchema( $field_schema );
			
		} catch ( Exception $e ) {
			return [];
		}
	}
	
	/**
	 * Find a field definition in the schema
	 * 
	 * @param array $schema The full schema array
	 * @param string $field_name The field name to find
	 * @return array|null The field schema or null if not found
	 */
	private function findFieldInSchema( $schema, $field_name ) {
		// Check direct properties
		if ( isset( $schema['properties'][ $field_name ] ) ) {
			return $schema['properties'][ $field_name ];
		}
		
		// Check nested properties recursively
		if ( isset( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $prop_name => $prop_schema ) {
				if ( strpos( $prop_name, $field_name ) !== false ) {
					return $prop_schema;
				}
				
				// Check nested properties
				if ( isset( $prop_schema['properties'] ) ) {
					$nested_result = $this->findFieldInSchema( $prop_schema, $field_name );
					if ( $nested_result ) {
						return $nested_result;
					}
				}
			}
		}
		
		return null;
	}
	

	/**
	 * Handle ASIN conversion special case
	 * If external_product_id_type is ASIN, move the external_product_id value to merchant_suggested_asin
	 * 
	 * @param array &$fields Reference to the fields array
	 */
	private function handleAsinConversion( &$fields ) {
		// Check if we have ASIN type and external product ID
		$external_id_type_field  = 'externally_assigned_product_identifier[0][type]';
		$external_id_value_field = 'externally_assigned_product_identifier[0][value]';
		
		if ( isset( $fields[ $external_id_type_field ] ) && 
			 $fields[ $external_id_type_field ] === 'ASIN' && 
			 isset( $fields[ $external_id_value_field ] ) ) {
			
			// Move the ASIN value to merchant_suggested_asin
			$fields['merchant_suggested_asin[0][value]'] = $fields[ $external_id_value_field ];
			
			// Remove the external_product_id fields since ASIN is now in its own field
			unset( $fields[ $external_id_type_field ] );
			unset( $fields[ $external_id_value_field ] );
		}
	}

	/**
	 * Get the local file path for the mapping CSV file
	 *
	 * @return string The full path to the mapping file
	 */
	private function getFilePath() {
		$upload_dir   = wp_upload_dir();
		$basedir_name = 'wp-lister/';
		return $upload_dir['basedir'].'/'.$basedir_name .'product-types-map.csv';
	}

	/**
	 * Transform nested arrays into flat form field names
	 * 
	 * This method converts nested array structures back into the flat field names
	 * that the form expects. For example:
	 * ['purchasable_offer' => [0 => ['our_price' => ['schedule' => [0 => ['value_with_tax' => '[product_price]']]]]]]
	 * becomes:
	 * ['purchasable_offer[0][our_price][schedule][0][value_with_tax]' => '[product_price]']
	 *
	 * @param array $data The nested array data
	 * @param string $prefix Current field prefix for recursion
	 * @return array Flattened array with form field names as keys
	 */
	public function flattenNestedArrayToFormFields( $data, $prefix = '' ) {
		$flattened = [];
		
		if ( !is_array( $data ) ) {
			return [ $prefix => $data ];
		}
		
		foreach ( $data as $key => $value ) {
			$current_key = $prefix === '' ? $key : $prefix . '[' . $key . ']';
			
			if ( is_array( $value ) && !empty( $value ) ) {
				// Recursively flatten nested arrays
				$nested_flattened = $this->flattenNestedArrayToFormFields( $value, $current_key );
				$flattened = array_merge( $flattened, $nested_flattened );
			} else {
				// This is a leaf value
				$flattened[ $current_key ] = $value;
			}
		}
		
		return $flattened;
	}

	/**
	 * Transform flat form field names into nested arrays
	 * 
	 * This method converts flat field names back into nested array structures.
	 * For example:
	 * ['purchasable_offer[0][our_price][schedule][0][value_with_tax]' => '[product_price]']
	 * becomes:
	 * ['purchasable_offer' => [0 => ['our_price' => ['schedule' => [0 => ['value_with_tax' => '[product_price]']]]]]]
	 *
	 * @param array $data The flat array data with form field names as keys
	 * @return array Nested array structure
	 */
	public function expandFormFieldsToNestedArray( $data ) {
		$nested = [];
		
		foreach ( $data as $field_name => $value ) {
			$this->setNestedValue( $nested, $field_name, $value );
		}
		
		return $nested;
	}

	/**
	 * Set a value in a nested array using a field name path
	 * 
	 * @param array &$array The array to modify (passed by reference)
	 * @param string $field_name The field name path (e.g., 'purchasable_offer[0][our_price]')
	 * @param mixed $value The value to set
	 */
	private function setNestedValue( &$array, $field_name, $value ) {
		// Parse the field name to extract the path components
		$path = $this->parseFieldNamePath( $field_name );
		
		// Navigate through the nested array, creating structure as needed
		$current = &$array;
		foreach ( $path as $key ) {
			if ( !isset( $current[ $key ] ) ) {
				$current[ $key ] = [];
			}
			$current = &$current[ $key ];
		}
		
		// Set the final value
		$current = $value;
	}

	/**
	 * Parse a field name path into its components
	 * 
	 * @param string $field_name The field name (e.g., 'purchasable_offer[0][our_price][schedule][0][value_with_tax]')
	 * @return array Array of path components
	 */
	private function parseFieldNamePath( $field_name ) {
		$path = [];
		
		// Split on brackets to get the main field and sub-fields
		if ( preg_match('/^([^\[]+)(.*)$/', $field_name, $matches) ) {
			$path[] = $matches[1]; // Main field name
			
			// Extract all bracketed components
			if ( !empty( $matches[2] ) ) {
				preg_match_all('/\[([^\]]+)\]/', $matches[2], $bracket_matches);
				foreach ( $bracket_matches[1] as $component ) {
					// Convert numeric strings to integers for array indices
					$path[] = is_numeric( $component ) ? (int)$component : $component;
				}
			}
		}
		
		return $path;
	}


	/**
	 * Checks if the given fields/properties need to be converted or mapped
	 *
	 * @param array|mixed $fields Fields to check, or any other type (will return false)
	 *
	 * @return bool True if conversion is needed, false otherwise
	 */
	private function needsConversion( $fields ) {
		// Handle non-array inputs
		if ( !is_array( $fields ) ) {
			return false;
		}

		// look for the pattern field_name[0][value]. If this pattern is found, then there's no need to convert
		$found = false;

		foreach ( $fields as $field => $value ) {
			if ( strpos( $field, '[0][value]' ) !== false ) {
				$found = true;
				break;
			}
		}

		return !$found;
	}

}