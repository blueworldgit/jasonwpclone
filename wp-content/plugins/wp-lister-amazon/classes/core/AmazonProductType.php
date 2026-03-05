<?php

namespace WPLab\Amazon\Core;

use WPLab\Amazon\Models\AmazonProductTypesModel;

class AmazonProductType {

	protected int $id = 0;
	protected string $marketplace_id = '';
	protected string $product_type = '';
	protected string $display_name = '';
	protected string $version = '';
	protected array $property_groups = [];
	protected string $schema = '';

	public function __construct( $id = null ) {
		if ( !is_null( $id ) ) {
			$this->populate( $id );
		}
	}

	public function getId() {
		return $this->id;
	}

	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}

	public function getMarketplaceId() {
		return $this->marketplace_id;
	}

	public function setMarketplaceId( $marketplace_id ) {
		$this->marketplace_id = $marketplace_id;
		return $this;
	}

	public function getProductType() {
		return $this->product_type;
	}

	public function setProductType( $product_type ) {
		$this->product_type = $product_type;
		return $this;
	}

	public function getDisplayName() {
		return $this->display_name;
	}

	public function setDisplayName( $display_name ) {
		$this->display_name = $display_name;
		return $this;
	}

	public function getVersion() {
		return $this->version;
	}

	public function setVersion( $version ) {
		$this->version = $version;
		return $this;
	}

	public function getPropertyGroups() {
		return $this->property_groups;
	}

	/**
	 * @param string[]
	 *
	 * @return $this
	 */
	public function setPropertyGroups( $property_groups = [] ) {
		$this->property_groups = $property_groups;
		return $this;
	}

	public function getSchema() {
		return $this->schema;
	}

	public function setSchema( $schema ) {
		$this->schema = $schema;
		return $this;
	}

	/**
	 * Returns the default language of the schema or FALSE if no schema is found
	 * @return string|false
	 */
	public function getLanguage() {
		if ( empty( $this->schema ) ) {
			return false;
		}

		$schema = json_decode( $this->getSchema(), true );

		return $schema['$defs']['language_tag']['default'] ?? false;
	}

	/**
	 * @param int $id
	 * @return AmazonProductType|false
	 */
	protected function load( $id ) {
		$model = new AmazonProductTypesModel();
		return $model->getById( $id );
	}
	private function populate( $id ) {
		$row = $this->load( $id );

		if ( $row ) {
			$groups = maybe_unserialize( $row->property_groups );

			$this
				->setId( $id )
				->setDisplayName( $row->display_name )
				->setProductType( $row->product_type )
				->setMarketplaceId( $row->marketplace_id )
				->setVersion( $row->version )
				->setPropertyGroups( $groups )
				->setSchema( $row->schema );
		}
	}

	/**
	 * @return int ID of the Product Type saved
	 * @throws \Exception
	 */
	public function save() {
		$mdl    = new AmazonProductTypesModel();
		$result = $mdl->saveProductType( $this );

		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message(), 500 );
		}

		return $result->getId();
	}

}