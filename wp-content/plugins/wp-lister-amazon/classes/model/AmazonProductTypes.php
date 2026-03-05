<?php

namespace WPLab\Amazon\Models;

use WP_Error;
use WPLab\Amazon\Core\AmazonProductType;
use WPLab\Amazon\SellingPartnerApi\ApiException;

class AmazonProductTypesModel {
	const TABLENAME = 'amazon_product_types';


	public function __construct() {}

	public function installDefaultProductTypes() {
		global $wpdb;

		// Install the PRODUCT product type
		$marketplaces = \WPLA_AmazonMarket::getAllFromAccounts();
		$account = current(WPLA()->accounts);

		foreach ( $marketplaces as $marketplace_id => $marketplace ) {
			try {
				$type_obj = new \WPLab\Amazon\Models\AmazonProductTypesModel();
				$attribute = $type_obj->getDefinitionsProductType( 'PRODUCT', $marketplace_id, false, $account->id );

				if ( !is_wp_error( $attribute ) && $attribute !== false ) {
					$attribute->setDisplayName( 'PRODUCT' );
					$attribute->save();
				}
			} catch (\Exception $e) {
				// just skip
				WPLA()->logger->error( 'Exception caught! '. $e->getMessage() );
			}

		}
	}

	/**
	 * Get all installed Product Types
	 */
	public function getAll() {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}amazon_product_types ORDER BY display_name ASC" ) );
		$types   = [];

		foreach ( $results as $row ) {
			$groups = maybe_unserialize( $row->property_groups );

			$type = new AmazonProductType();
			$type
				->setId( $row->id )
				->setMarketplaceId( $row->marketplace_id )
				->setVersion( $row->version)
				->setDisplayName( $row->display_name )
				->setProductType( $row->product_type )
				->setPropertyGroups( $groups )
				->setSchema( $row->schema );

			$types[] = $type;
		}

		return $types;
	}

	/**
	 * @param int $id
	 *
	 * @return AmazonProductType|false
	 */
	public function getById( $id ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}amazon_product_types WHERE id = %d", $id ) );

		if ( $row ) {
			$groups = maybe_unserialize( $row->property_groups );

			$type = new AmazonProductType();
			$type
				->setId( $id )
				->setMarketplaceId( $row->marketplace_id )
				->setVersion( $row->version)
				->setDisplayName( $row->display_name )
				->setProductType( $row->product_type )
				->setSchema( $row->schema );

			if ( $groups ) {
				$type->setPropertyGroups( $groups );
			}

			return $type;
		}

		return false;
	}

	public function getFiltered( $filter = [] ) {
		global $wpdb;

		$default = [
			'keywords'          => '',
			'marketplace_id'    => '',
			'product_type'      => '',
			'version'           => '',
			'order_by'          => 'display_name',
			'order_sort'        => 'ASC',
			'page'              => 1,
			'per_page'          => 20
		];
		$filter = wp_parse_args( $filter, $default );

		$offset   = ( $filter['page'] - 1 ) * $filter['per_page'];
		$per_page = esc_sql( $filter['per_page'] );

		// handle filters
		$where_sql = ' WHERE 1 = 1 ';
		$tables_sql = "{$wpdb->prefix}amazon_product_types t";

		if ( !empty( $filter['keywords'] ) ) {
			$where_sql .= " AND display_name LIKE '%". $wpdb->esc_like($filter['keywords']) ."%' ";
		}

		if ( !empty( $filter['marketplace_id'] ) ) {
			$where_sql .= " AND marketplace_id = '". $wpdb->esc_like($filter['marketplace_id']) ."' ";
		}

		// get items
		$results = $wpdb->get_results("
            SELECT DISTINCT t.id
            FROM {$tables_sql}
            $where_sql
            ORDER BY {$filter['order_by']} {$filter['order_sort']}
            LIMIT $offset, $per_page
        ", ARRAY_A);

		$items = [];
		foreach ( $results as $row ) {
			$items[] = new AmazonProductType( $row['id'] );
		}

		$return = [
			'total_items'   => 0,
			'items'         => $items
		];

		// get total items count - if needed
		if ( ( $filter['page'] == 1 ) && ( count( $items ) < $filter['per_page'] ) ) {
			$return['total_items'] = count( $items );
		} else {
			$return['total_items'] = $wpdb->get_var("
                SELECT COUNT(*)
                FROM $tables_sql
                $where_sql
                ORDER BY {$filter['order_by']} {$filter['order_sort']}
            ");
		}

		return $return;
	}

	/**
	 * @param string $marketplace_id
	 *
	 * @return array
	 */
	public static function getByMarketplace( $marketplace_id ) {
		global $wpdb;

		return $wpdb->get_results($wpdb->prepare("SELECT product_type, display_name FROM {$wpdb->prefix}amazon_product_types WHERE marketplace_id = %s", $marketplace_id ) );
	}

	public static function add( $product_type, $display_name, $marketplace_id ) {
		global $wpdb;

		$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}amazon_product_types WHERE product_type = %s AND marketplace_id = %s", $product_type, $marketplace_id ) );

		if ( $count == 0 ) {
			$wpdb->insert( $wpdb->prefix .'amazon_product_types', ['product_type' => $product_type, 'display_name' => $display_name, 'marketplace_id' => $marketplace_id] );
		}
	}

	/**
	 * @param array $marketplace_ids
	 *
	 * @return void
	 */
	public static function downloadForMarketplace( $marketplace_ids ) {
		$api = new WPLA_Amazon_SP_API( get_option( 'wpla_default_account_id' ) );
		$product_types = $api->searchDefinitionsProductTypes( $marketplace_ids );

		foreach ( $product_types->getProductTypes() as $product_type ) {
			foreach ( $marketplace_ids as $market ) {
				AmazonProductTypesModel::add( $product_type->getName(), $product_type->getDisplayName(), $market );
			}
		}
	}

	public static function getTypesAsDropdownOptions( $account_id, $selected = '' ) {
		global $wpdb;

		$allowed_markets = isset( WPLA()->accounts[ $account_id ] ) ? WPLA()->accounts[ $account_id ]->allowed_markets : 0;

		if ( empty( $allowed_markets ) ) return [];

		$allowed_markets     = array_keys( maybe_unserialize( $allowed_markets ) );
		$allowed_markets_csv =  "'". implode( "','", $allowed_markets ) ."'";

		$rows = $wpdb->get_results(
			"SELECT * 
				FROM {$wpdb->prefix}amazon_product_types 
				WHERE marketplace_id IN (". $allowed_markets_csv .")
				ORDER BY product_type ASC",
			ARRAY_A );
		$options = [];

		foreach ( $rows as $row ) {
			$options[ $row['marketplace_id'] ][] = [
				'product_type'  => $row['product_type'],
				'display_name'  => $row['display_name']
			];
		}

		return $options;
	}

	public static function getTypesGroupedByMarketplace( $account_id ) {
		global $wpdb;

		$allowed_markets = isset( WPLA()->accounts[ $account_id ] ) ? WPLA()->accounts[ $account_id ]->allowed_markets : 0;

		if ( empty( $allowed_markets ) ) return [];

		$allowed_markets     = array_keys( maybe_unserialize( $allowed_markets ) );
		$allowed_markets_csv =  "'". implode( "','", $allowed_markets ) ."'";

		$rows = $wpdb->get_results(
			"SELECT * 
				FROM {$wpdb->prefix}amazon_product_types 
				WHERE marketplace_id IN (". $allowed_markets_csv .")
				ORDER BY product_type ASC",
			ARRAY_A );
		$options = [];

		foreach ( $rows as $row ) {
			$options[ $row['marketplace_id'] ][] = [
				'product_type'  => $row['product_type'],
				'display_name'  => $row['display_name']
			];
		}

		return $options;
	}

	/**
	 * Returns the AmazonProductType object for the given product type and marketplace
	 *
	 * @param string $product_type
	 * @param string $marketplace
	 * @param bool $store_locally Pass FALSE to only return the AmazonProductType object without it being stored in the DB
	 *
	 * @return AmazonProductType|WP_Error
	 */
	public function getDefinitionsProductType( $product_type, $marketplace, $store_locally = false, $account_id = null ) {
		try {

			if ( !$account_id ) {
				$local = $this->getProductType( $product_type, $marketplace );

				if ( $local ) {
					return $local;
				}
			}

			$account_id = $account_id ?? \WPLA_AmazonAccount::getAccountWithMarketplace( $marketplace );

			if ( ! $account_id ) {
				$account_id = get_option( 'wpla_default_account_id' );
			}

			// No cached schema found. Download and store
			$api            = new \WPLA_Amazon_SP_API( $account_id );
			$resp           = $api->getDefinitionsProductType( $product_type, $marketplace );

			// Check for SP-API errors
			if ( \WPLA_Amazon_SP_API::isError( $resp ) ) {
				\WPLA_Amazon_SP_API::handleApiError(
					$resp,
					sprintf( 'Product Type Definition Retrieval for "%s"', $product_type )
				);
				return false;
			}

			// Validate response structure
			if ( ! is_object($resp) || ! method_exists($resp, 'getSchema') ) {
				WPLA()->logger->error( 'Invalid response structure from getDefinitionsProductType for ' . $product_type );

				if ( ! wp_doing_cron() && ! wpla_request_is_rest() ) {
					wpla_show_message(
						sprintf(
							'Received invalid data for product type "%s". Please try again or contact support.',
							$product_type
						),
						'error'
					);
				}

				return false;
			}

			//$remote_version = $resp->getProductTypeVersion()->getVersion();

			$schema_url = $resp->getSchema()->getLink()->getResource();
			$schema     = $this->downloadSchemaFromUrl( $schema_url );

			if ( $schema ) {
				$schema = $this->filterSchemaProperties( $schema );

				if ( $store_locally ) {
					$attribute = $this->storeLocalSchemaForProductType( $product_type, $marketplace, $resp, $schema );
				} else {
					$attribute = new AmazonProductType();

					$attribute
						->setId( 0 )
						->setProductType( $product_type )
						->setMarketplaceId( $marketplace )
						->setVersion( $resp->getProductTypeVersion()->getVersion() )
						->setPropertyGroups( $resp->getPropertyGroups() )
						->setSchema( $schema );
				}
			} else {
				return new WP_Error( 'wpla_error', 'Could not download schema. Please try again later.' );
			}


			return $attribute;
		} catch ( ApiException|Exception $e ) {
			return new WP_Error( 'wpla_error', $e->getMessage() );
		}

	}

	/**
	 * @param $product_type
	 * @param $marketplace
	 *
	 * @return false|AmazonProductType
	 */
	public function getProductType( $product_type, $marketplace ) {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare("
			SELECT id 
			FROM {$wpdb->prefix}amazon_product_types 
			WHERE marketplace_id = %s 
			AND product_type = %s",
			$marketplace,
			$product_type
		) );

		if ( $id ) {
			return new AmazonProductType( $id );
		}

		return false;
	}

	/**
	 * @param AmazonProductType $product_type
	 *
	 * @return AmazonProductType
	 */
	public function saveProductType( $product_type ) {
		global $wpdb;

		$data = [
			'product_type'      => $product_type->getProductType(),
			'display_name'      => $product_type->getDisplayName(),
			'marketplace_id'    => $product_type->getMarketplaceId(),
			'version'           => $product_type->getVersion(),
			'property_groups'   => maybe_serialize($product_type->getPropertyGroups()),
			'schema'            => $product_type->getSchema()
		];

		if ( empty( $data['display_name'] ) ) {
			$data['display_name'] = $data['product_type'];
			WPLA()->logger->info( "saveProductType empty display name!" );
			//WPLA()->logger->info( print_r(debug_backtrace(),1) );
		}

		// If there is an existing product type for the same marketplace, we switch this to an update request instead of creating a duplicate
		$existing_id = $this->getProductTypeId( $data['product_type'], $data['marketplace_id'] );

		if ( $existing_id ) {
			$product_type->setId( $existing_id );
		}

		if ( $product_type->getId() == 0 ) {
			// create new
			$wpdb->insert( $wpdb->prefix . 'amazon_product_types',  $data );

			$new_id = $wpdb->insert_id;

			if ( $new_id ) {
				$product_type->setId( $new_id );
				return $product_type;
			} else {
				WPLA()->logger->error( 'Could not create new product type. SQL Error: ' . $wpdb->last_error );
				WPLA()->logger->debug( print_r($data,1) );
				return new WP_Error( 'wpla_error', 'Could not create new product type.' );
			}
		} else {
			// update
			$wpdb->update( $wpdb->prefix .'amazon_product_types', $data, ['id' => $product_type->getId()] );

			return $product_type;
		}
	}

	/**
	 * Search the database for an existing Product Type and return its ID if found.
	 * @param string $product_type
	 * @param string $marketplace
	 *
	 * @return false|int
	 */
	public function getProductTypeId( $product_type, $marketplace ) {
		global $wpdb;

		$id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}amazon_product_types WHERE product_type = %s AND marketplace_id = %s", $product_type, $marketplace ) );

		if ( $id ) {
			return $id;
		}

		return false;
	}

	/**
	 * @param int $id
	 *
	 * @return int|false Number of affected rows or false on error
	 */
	public function deleteById( $id ) {
		global $wpdb;

		$where = [
			'id' => $id
		];
		return $wpdb->delete( $wpdb->prefix .'amazon_product_types', $where );
	}

	/**
	 * @param string $product_type
	 * @param string $marketplace
	 *
	 * @return int|false Number of affected rows or false on error
	 */
	public function deleteByProductType( $product_type, $marketplace ) {
		global $wpdb;

		$where = [
			'marketplace_id'    => $marketplace,
			'product_type'      => $product_type
		];
		return $wpdb->delete( $wpdb->prefix .'amazon_product_types', $where );
	}

	/**
	 * @param $product_type
	 * @param $marketplace
	 * @param \WPLab\Amazon\SellingPartnerApi\Model\ProductTypeDefinitionsV20200901\ProductTypeDefinition $definition
	 * @param string $schema
	 *
	 * @return AmazonProductType
	 */
	protected function storeLocalSchemaForProductType( $product_type, $marketplace, $definition, $schema ) {
		$attr_obj = new AmazonProductType();

		$old_obj = $this->getProductType( $product_type, $marketplace );

		if ( $old_obj ) {
			$attr_obj->setId( $old_obj->getId() );
		} else {
			// there's no old record, so we need to pull the name from Amazon
			$attr_obj->setDisplayName( $this->getDisplayNameForProductType( $product_type, $marketplace ) );
		}

		try {
			$attr_obj
				->setProductType( $product_type )
				->setMarketplaceId( $marketplace )
				->setVersion( $definition->getProductTypeVersion()->getVersion() )
				->setPropertyGroups( $definition->getPropertyGroups() )
				->setSchema( $schema );

			$attr_obj->save();
			return $attr_obj;
		} catch ( \Exception $e ) {
			WPLA()->logger->error( 'Product Type save failed! '. $e->getMessage() );
		}

	}

	protected function downloadSchemaFromUrl( $schema_url ) {
		try {
			$client = new \WPLab\Amazon\GuzzleHttp\Client();
			$resp = $client->get($schema_url);

			return (string)$resp->getBody();
			//return json_decode($body);
		} catch ( Exception $e ) {
			WPLA()->logger->error( 'Could not download schema from Amazon. '. $e->getMessage() );
			return false;
		}

	}

	/**
	 * Remove all unnecessary properties from the schema, such as the AllOf validation properties.
	 *
	 * @param string $schema
	 * @return string
	 */
	protected function filterSchemaProperties( $schema ) {
		$obj = json_decode( $schema, true );
		//unset( $obj['anyOf'], $obj['allOf'], $obj['oneOf'] );

		return json_encode($obj);
	}

	/**
	 * @param $product_type
	 * @param $marketplace
	 *
	 * @return string
	 */
	private function getDisplayNameForProductType($product_type, $marketplace) {
		$account_id = \WPLA_AmazonAccount::getAccountWithMarketplace( $marketplace );
		$api = new \WPLA_Amazon_SP_API( $account_id );
		$results = $api->searchDefinitionsProductTypes([$marketplace], $product_type);

		foreach ( $results->getProductTypes() as $result ) {
			if ( $result->getName() == $product_type ) {
				return $result->getDisplayName();
			}
		}

		return $product_type;
	}
}