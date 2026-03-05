<?php

namespace WPLab\Amazon\Helper;

class AmazonSchemaFormGenerator {

	private array $schema;
	private array $groups;
	private \WPLA_AmazonProfile $profile;
	private int $product_id = 0;
	private array $custom_feed_columns = [];

	/**
	 * A list of the fields not rendered due to the product type not having a matching element for it.
	 * This starts out as a copy of the WPLA_AmazonProfile::$fields array and the fields that have been rendered are removed from this list.
	 * @var array
	 */
	private array $unused_fields;

	/**
	 * Product properties that are hidden and handled automatically by WP-Lister
	 * @var string[]
	 */
	protected const INTERNAL_FIELDS = array(
		// 'external_product_id_type',
		// 'external_product_id',

		// category feeds
		'skip_offer',
		//'update_delete',
		//'item_sku',
		//'quantity',
		//'parentage_level',
		//'child_parent_sku_relationship',
		//'child_parent_sku_relationship_type',
	);

	/**
	 * @param array $schema
	 * @param array $groups
	 * @param \WPLA_AmazonProfile $profile
	 */
	public function __construct( $schema = null, $groups = null, $profile = null, $product_id = 0 ) {
		if ( $schema ) {
			$this->schema = $schema;
		}

		if ( $groups ) {
			$this->groups = $groups;
		}

		if ( $profile ) {
			$this->profile = $profile;
			$this->unused_fields = $profile->fields;

			// remove internal fields
			unset( $this->unused_fields['__unmapped'], $this->unused_fields['__old_fields'], $this->unused_fields['details'] );
		}

		if ( $product_id ) {
			$this->product_id = $product_id;

			$custom_feed_columns = get_post_meta( $product_id, '_wpla_custom_feed_columns', true );

			if ( is_array( $custom_feed_columns ) ) {
				$converter = new ProfileProductTypeConverter( $profile );
				$custom_feed_columns = $converter->convertFromArray( $custom_feed_columns );
				$this->custom_feed_columns = $custom_feed_columns;
			}
		}
	}

	/**
	 * @param $schema
	 * @return $this
	 */
	public function setSchema( $schema ) {
		$this->schema = $schema;
		return $this;
	}

	/**
	 * @param $groups
	 * @return $this
	 */
	public function setGroups( $groups ) {
		$this->groups = $groups;
		return $this;
	}

	/**
	 * @param \WPLA_AmazonProfile $profile
	 * @return $this
	 */
	public function setProfile( $profile ) {
		$this->profile = $profile;
		return $this;
	}

	public function getFields() {
		$fields = [];

		foreach ( $this->schema['properties'] as $name => $property ) {
			$items  = $property['items'];
			$type   = $items['type'] ?? 'string';

			if ( $this->isRenderable( $type, $property ) ) {
				$fields[ $name ] = $property;
			}

			$fields = $this->getFieldsRecursively( $name, $property, $fields );
		}

		return $fields;
	}

	/**
	 * Run through the properties and render all renderable elements
	 *
	 * @param string $name
	 * @param array  $details
	 * @param array  $fields
	 *
	 * @return array
	 */
	private function getFieldsRecursively( $name, $details, $fields = [] ) {

		$properties = false;
		if ( isset( $details['items']['properties'] ) ) {
			$properties = $details['items']['properties'];
		} elseif ( isset( $details['properties'] ) ) {
			$properties = $details['properties'];
		}

		foreach ( (array)$properties as $prop_key => $property ) {
			if ( $prop_key == 'marketplace_id' ) {
				$property = $this->schema['$defs']['marketplace_id'];
				//continue;
			} elseif ( $prop_key == 'language_tag' ) {
				$property = $this->schema['$defs']['language_tag'];
			}

			if ( !isset( $property['type'] ) ) {
				continue;
			}

			if ( $this->isRenderable( $property['type'], $property ) ) {
				if ( $details['type'] == 'array' ) {
					if ( !isset( $details['maxUniqueItems']) ) {
						$details['maxUniqueItems'] = 1;
					}

					// limit to 5 fields per property for now
					$max = min( $details['maxUniqueItems'], 5 );

					for ( $idx = 0; $idx < $max; $idx++ ) {
						$new_name = $name .'['. $idx .']['. $prop_key .']';

						//$value = $this->getPropertyValue( $new_name );

						$is_required = in_array( $name, $this->schema['required'] );

						$custom_label = $property['title'] ?? ucfirst( $name );

						if ( $max > 1 ) {
							$label_idx = $idx+1;
							$custom_label = $custom_label .' '. $label_idx;
						}

						$fields[ $new_name ] = $property;
						//$html .= $this->renderElement( $new_name, $property, $property['type'], $value, $is_required, $custom_label );
					}
				} else {
					$new_name = $name .'['. $prop_key .']';

					$is_required = in_array( $name, $this->schema['required'] );
					$custom_label = $property['title'] ?? ucfirst( $name );

					$fields[ $new_name ] = $property;
				}
			} else {
				$sub_name = $name . '[0]['. $prop_key .']';
				$fields = $this->getFieldsRecursively( $sub_name, $property, $fields );
			}
		}
		return $fields;
	}

	/**
	 * Takes a property name ($name) from the current schema and recursively scans and renders all elements under it
	 *
	 * @param string $name The property name
	 * @param array  $details
	 *
	 * @return string
	 */
	public function generateField($name, $details ) {
		if ( !$this->canDisplayProperty( $name ) ) {
			return '';
		}

		$items  = $details['items'];
		$type   = $items['type'] ?? 'string';

		$html = "";

		if ( $this->isRenderable( $type, $items ) ) {
			$value = $this->getPropertyValue( $name );

			$is_required = in_array( $name, $items['required'] ) && $details['minItems'] >= 1;
			$html .= $this->renderElement( $name, $details, $type, $value, $is_required );
		}

		$html .= $this->recursivePropertiesRenderer( $name, $details );

		return $html;
	}

	public function generateFilters() {
		$unmapped_link = '';

		if ( !empty( $this->unused_fields ) ) {
			$unmapped_link = '<a href="#wpla_unmapped_group" class="button-link-delete unmapped-link" style="float: right; display:none;">'. __( 'There might be values that need mapping', 'wp-lister-for-amazon' ) .'</a>';
		}

		return '<div id="feed-template-searchbar">
					<input type="text" id="_wpla_tpl_col_filter" placeholder="Search..." onchange="wpla_update_filter();" />
					&nbsp;
					<input type="checkbox" id="_wpla_tpl_col_only_required" onchange="wpla_update_filter();" />
					<label for="_wpla_tpl_col_only_required">'. __( 'show only required fields', 'wp-lister-for-amazon' ) .'</label>
					&nbsp;
					<input type="checkbox" id="_wpla_tpl_col_hide_empty" onchange="wpla_update_filter();" />
					<label for="_wpla_tpl_col_hide_empty">'. __( 'hide empty fields', 'wp-lister-for-amazon' ) .'</label>
				
					'. $unmapped_link .'
				</div>';
	}

	public function generateGroupRow( $group_name, $description, $css_row_id = '' ) {
		/*if ( in_array( $group_key, array('images','variations') )  &&  ! $is_expert_mode ) {
		    continue;
        }*/
		// process group


		return '<tr class="wpla_tpl_section_header" id="'. $css_row_id .'">
			        <th colspan="3">
			            <h4>'. $group_name .'</h4>
			            <small>'. $description .'</small>
			        </th>
			    </tr>';
	}

	public function renderUnusedProperties() {
		$fields = [];

		foreach ( $this->unused_fields as $name => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$fields[ $name ] = $value;
		}

		if ( empty( $fields ) ) {
			return '';
		}

		$desc = __('These fields were carried over from a previous template but were not mapped because no matching field was found in the current Product Type.', 'wp-lister-for-amazon');
		$html = $this->generateGroupRow( __( 'Fields That Might Need Mapping', 'wp-lister-for-amazon'), $desc, 'wpla_unmapped_group' );
		$html .= '<tr>
						<th>'. __('New Property', 'wp-lister-for-amazon') .'</th>
						<th>'. __( 'Current Value', 'wp-lister-for-amazon') .'</th>
					</tr>';
		foreach ( $this->unused_fields as $name => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			$html .= '<tr>
							<td>'. $name .'</td>
							<td>'. $this->renderString( $name, [], $value ) .'</td>
						</tr>';
		}

		foreach ( (array)$this->profile->unmapped_fields as $name => $value ) {
			if ( $name == 'details' ) {
				continue;
			}

			if ( strpos( $name, 'marketplace_id' ) || strpos( $name, 'language_tag' ) ) {
				$html .=  $this->renderHiddenField( $name, [], $value );
			} else {
				$html .= '<tr>
							<td>'. $name .'</td>
							<td>'. $this->renderString( $name, [], $value ) .'</td>
						</tr>';
			}
		}

		$html .= '<script>jQuery(".unmapped-link").show();</script>';
		return $html;
	}

	/**
	 * Returns as array of the internal fields that should be hidden by the form
	 *
	 * @return array|string[]
	 */
	private function getHiddenProperties() {
		// default hidden properties
		$fields = self::INTERNAL_FIELDS;

		$offer_images   = get_option( 'wpla_enable_product_offer_images', 0 );
		$editor_mode    = get_option( 'wpla_profile_editor_mode', 'default' );

		if ( $offer_images != 1 ) {
			$fields = array_merge( $fields, [
				'main_offer_image_locator',
				'other_offer_image_locator_1',
				'other_offer_image_locator_2',
				'other_offer_image_locator_3',
				'other_offer_image_locator_4',
				'other_offer_image_locator_5',
			]);
		}

		/*if ( $editor_mode != 'expert' ) {
			$fields = array_merge( $fields, [
				//'fulfillment_availability',
				//'purchasable_offer[0][currency]',
				//'purchasable_offer[0][our_price][schedule][0][value_with_tax]',
				'main_product_image_locator',
				'other_product_image_locator_1',
				'other_product_image_locator_2',
				'other_product_image_locator_3',
				'other_product_image_locator_4',
				'other_product_image_locator_5',
				'other_product_image_locator_6',
				'other_product_image_locator_7',
				'other_product_image_locator_8',
			] );
		}*/

		return $fields;
	}

	/**
	 * Check if the property can be displayed/processed
	 *
	 * @param $name
	 * @return bool
	 */
	private function canDisplayProperty( $name ) {
		if ( in_array( $name, $this->getHiddenProperties() ) ) {
			return false;
		}

		if ( !isset($this->schema['properties'][$name]) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns TRUE if the type can be rendered. Currently only string, boolean, number and integer are listed as renderable types.
	 *
	 * @param string $type
	 * @param array $property
	 * @return bool
	 */
	private function isRenderable( $type, $property ) {
		$renderable = false;
		$renderable_types = ['string', 'boolean', 'number', 'integer'];

		if ( in_array( $type, $renderable_types ) ) {
			$renderable = true;
		} elseif ( $type === 'array' && !empty( $property['items']['type'] ) && $property['items']['type'] === 'string' ) {
			$renderable = true;
		}

		return $renderable;
	}

	/**
	 * Get the raw, unprocessed value of the given property/field. Unprocessed in this context means that the value can
	 * still be a shortcode or a variable
	 *
	 * Product-level values will always have precedence and profile values will only be used when there's no matching
	 * product-level value found for the property/column.
	 *
	 * @param string $name
	 * @param bool $run_value_substitutions
	 *
	 * @return mixed|null
	 */
	private function getPropertyValue( $name, $run_value_substitutions = true ) {
		$value = null;

		// load from product
		if ( isset( $this->custom_feed_columns[ $name ] ) ) {
			$value = $this->custom_feed_columns[ $name ];
		}

		// if still empty, load from profile
		if ( $value === null ) {
			$value = $this->getValueFromProfile( $name );
		}

		if ( $run_value_substitutions ) {
			// we might need some processing done like the ASIN value is not included in the product id type in the new
			// API so we need to transfer the ASIN to another field if product-id-type is set to ASIN
			$value = $this->doValueSubstitutions( $value, $name );
		}

		return $value ?? ''; // Return an empty string if value is still null at this point
	}

	private function doValueSubstitutions( $value, $name ) {
		/**
		 * merchant_suggested_asin[0][value] can also read the ASIN from externally_assigned_product_identifier[0][value]
		 */
		if ( empty( $value ) && $name == 'merchant_suggested_asin[0][value]' ) {
			if ( $this->getPropertyValue( 'externally_assigned_product_identifier[0][type]', false ) == 'ASIN' ) {
				$value = $this->getPropertyValue( 'externally_assigned_product_identifier[0][value]', false );
			}
		}

		/**
		 * If the externally_assigned_product_identifier[0][type] is ASIN, empty its value since it's been assigned to merchant_suggested_asin already
		 */
		if ( $name == 'externally_assigned_product_identifier[0][value]' ) {
			if ( $this->getPropertyValue( 'externally_assigned_product_identifier[0][type]', false ) == 'ASIN' ) {
				$value = '';
			}
		}

		/**
		 * externally_assigned_product_identifier[0][type] does not accept the ASIN value so remove it
		 */
		if ( $name == 'externally_assigned_product_identifier[0][type]' && $value == 'ASIN' ) {
			$value = '';
		}

		return $value;
	}

	/**
	 * @param string $name The name/property
	 * @return mixed|string
	 */
	private function getValueFromProfile( $name ) {
		$value = '';
		if ( isset($this->profile->fields) && isset($this->profile->fields[$name]) && $this->profile->fields[$name] !== '' ) {
			$value = $this->profile->fields[ $name ];
		}

		return $value;
	}

	/**
	 * Run through the properties and render all renderable elements
	 *
	 * @param string $name
	 * @param array  $details
	 *
	 * @return string
	 */
	private function recursivePropertiesRenderer( $name, $details ) {
		$html = '';

		$properties = false;
		if ( isset( $details['items']['properties'] ) ) {
			$properties = $details['items']['properties'];
		} elseif ( isset( $details['properties'] ) ) {
			$properties = $details['properties'];
		}

		if ( !$properties ) {
			return $html;
		}

		foreach ( (array)$properties as $prop_key => $property ) {
			if ( $prop_key == 'marketplace_id' ) {
				$property = $this->schema['$defs']['marketplace_id'];
				//continue;
			} elseif ( $prop_key == 'language_tag' ) {
				$property = $this->schema['$defs']['language_tag'];
			}

			if ( !isset( $property['type'] ) ) {
				continue;
			}

			if ( $this->isRenderable( $property['type'], $property ) ) {
				if ( $details['type'] == 'array' ) {
					if ( !isset( $details['maxUniqueItems']) ) {
						$details['maxUniqueItems'] = 1;
					}

					// limit to 5 fields per property for now
					$max = min( $details['maxUniqueItems'], 5 );

					for ( $idx = 0; $idx < $max; $idx++ ) {
						$new_name = $name .'['. $idx .']['. $prop_key .']';
						//$value = '';

						// some malformed fields need to be mapped
						//$new_name = $this->fixMalformedProperty( $new_name );

						$value = $this->getPropertyValue( $new_name );

						/* @todo how to actually determine if an element is a required field */
						$is_required = in_array( $name, $this->schema['required'] );

						$custom_label = $property['title'] ?? ucfirst( $name );

						if ( $max > 1 ) {
							$label_idx = $idx+1;
							$custom_label = $custom_label .' '. $label_idx;
						}

						$html .= $this->renderElement( $new_name, $property, $property['type'], $value, $is_required, $custom_label );
					}
				} else {
					$new_name = $name .'['. $prop_key .']';
					//$value = '';

					$value = $this->getPropertyValue( $new_name );

					/* @todo how to actually determine if an element is a required field */
					$is_required = in_array( $name, $this->schema['required'] );

					$custom_label = $property['title'] ?? ucfirst( $name );

					$html .= $this->renderElement( $new_name, $property, $property['type'], $value, $is_required, $custom_label );
				}
			} else {
				$sub_name = $name . '[0]['. $prop_key .']';
				$html .= $this->recursivePropertiesRenderer( $sub_name, $property );
			}
		}
		return $html;
	}

	/**
	 * Renders a renderable element
	 *
	 * @param string $name
	 * @param array  $property
	 * @param string $type
	 * @param string $value
	 * @param bool   $required
	 *
	 * @return string
	 */
	private function renderElement( $name, $property, $type, $value = '', $required = false, $custom_label = null ) {
		$html = '';

		unset( $this->unused_fields[ $name ] );

		if ( in_array( $name, $this->getHiddenProperties() ) ) {
			return '';
		}

		// detect any enum definitions (direct or composite) → treat as dropdown
		if ( $type === 'string' ) {
			$enumOptions = $this->extractEnumOptions( $property );
			if ( ! empty( $enumOptions ) ) {
				$type = 'select';
				// stash options into the property for renderSelectbox()
				$property['_enumOptions'] = $enumOptions;
			}
		}


		if ( $property['hidden'] ) {
			$type = 'hidden';

			// use the default value for hidden elements
			if ( empty( $value ) && !empty( $property['default'] ) ) {
				$value = $property['default'];
			}
		}

		switch ( $type ) {
			case 'string':
				$html = $this->renderString( $name, $property, $value );
				break;

			case 'number':
			case 'integer':
				$html = $this->renderNumber( $name, $property, $value );
				break;

			case 'boolean':
				$html = $this->renderBoolean( $name, $property, $value );
				break;

			case 'select':
				$html = $this->renderSelectbox( $name, $property, $value );
				break;

			case 'hidden':
				$html = $this->renderHiddenField( $name, $property, $value );
				break;

			case 'array':
				// Check if it's an array of enums (like features field)
				$enumOptions = $this->extractEnumOptions( $property );
				if ( ! empty( $enumOptions ) ) {
					// stash options into the property for renderSelectbox()
					$property['_enumOptions'] = $enumOptions;
					$html = $this->renderSelectbox($name, $property, $value, true);
				}
				break;
		}

		$required_html = '<span style="color:silver">'. __( 'Optional', 'wp-lister-for-amazon') .'</span>';

		if ( $required ) {
			$required_html = '<b>' . __('Required', 'wp-lister-for-amazon') .'</b>';
		}

		if ( $type != 'hidden' ) {
			$label = $custom_label ?? $property['title'];

			return $this->renderLabel( $label, $name, $property, $required ) .
			       '<td width="50%">' . $html . '</td>' .
			       '<td width="10%" class="col_required"> '. $required_html .'</td></tr>';
		} else {
			return $html;
		}

	}

	/**
	 * Handles the Boolean type which renders a dropdown of YES/NO options
	 *
	 * @param string $name
	 * @param array  $property
	 * @param string $value
	 *
	 * @return string
	 */
	private function renderBoolean( $name, $property, $value = '' ) {
		$html = "<select name='tpl_col_$name' id='tpl_col_$name' class='select2'>";

		// Add the "none" option first (like buildOptionGroups does)
		$none_selected = $value === '' ? 'selected="selected"' : '';
		$html .= "<option value='' $none_selected>&mdash; " . __('none', 'wp-lister-for-amazon') . " &mdash;</option>";

		foreach ($property["enum"] as $key => $val) {
			// Convert boolean values to strings for form compatibility
			$string_val = $val ? 'true' : 'false';
			$selected_str = $value == $string_val ? 'selected="selected"' : '';
			$html .= "<option value='$string_val' $selected_str>" . ($property["enumNames"][$key] ?? $val) . "</option>";
		}

		$html .= "</select>";

		return $html;
	}

	/**
	 * Handles the Select type. @uses self::buildOptionGroups() to inject attributes and custom fields into the dropdown.
	 *
	 * @param string $name
	 * @param array  $property
	 * @param string $value
	 *
	 * @return string
	 */
	private function renderSelectbox( $name, $property, $value = '', $multiple = false ) {
		$multiple_attr = $multiple ? 'multiple="multiple"' : '';
		$name = $multiple ? $name .'[]' : $name;
		$html = "<select name='tpl_col_$name' id='tpl_col_$name' class='select2' {$multiple_attr}>";
		/*$allowed_values = [];

		foreach ($property["enum"] as $key => $val) {
			$allowed_values[ $key ] = $property['enumNames'][$key] ?? $val;
		}

		$html .= $this->buildOptionGroups( $allowed_values, $value, $property['enum'] );*/

		// first, check if extractEnumOptions provided us a list
		$allowed_values = [];
		if ( ! empty( $property['_enumOptions'] ) ) {
			$allowed_values = $property['_enumOptions'];
		} elseif ( ! empty( $property['enum'] ) ) {
			foreach ( $property['enum'] as $idx => $val ) {
				$allowed_values[ $val ] = $property['enumNames'][ $idx ] ?? $val;
			}
		} elseif ( !empty( $property['items']['enum'] ) ) {
			foreach ( $property['items']['enum'] as $idx => $val ) {
				$allowed_values[ $val ] = $property['items']['enumNames'][ $idx ] ?? $val;
			}
		}

		// use the option-groups builder, passing the raw keys too
		$keys = array_keys( $allowed_values );
		$html .= $this->buildOptionGroups( $allowed_values, $value, $keys );


		$html .= "</select>";

		return $html;
	}

	/**
	 * Handles the String type.
	 *
	 * @param string $name
	 * @param array  $property
	 * @param string $value
	 *
	 * @return string
	 */
	private function renderString( $name, $property, $value = '' ) {
		return '<input type="text" name="tpl_col_'. $name .'" id="tpl_col_'. $name .'" value="'. esc_attr($value) .'" placeholder="" />
                <a href="#" onclick="wpla_select_shortcode(\''. $name .'\');return false;" title="Select attribute">
                    <img class="browse_shortcodes" data-tip="" src="'. WPLA_URL .'img/search2.png" height="16" width="16" />
                </a>';
	}

	/**
	 * @param $name
	 * @param $property
	 * @param $value
	 *
	 * @return string
	 */
	private function renderHiddenField( $name, $property, $value = '' ) {
		return '<input type="hidden" name="tpl_col_'. $name .'" id="tpl_col_'. $name .'" value="'. esc_attr($value) .'" placeholder="" />';
	}

	/**
	 * Handles the number type. This simply calls the @see self::renderString() to generate the element
	 *
	 * @param string $name
	 * @param array  $property
	 * @param string $value
	 *
	 * @return string
	 */
	private function renderNumber( $name, $property, $value = '' ) {
		return $this->renderString( $name, $property, $value );
	}

	/**
	 * Generates the label row for the element
	 *
	 * @param string $name
	 * @param array  $property
	 * @param bool   $required
	 *
	 * @return string
	 */
	private function renderLabel( $label, $field, $property, $required = false, $css_class = '' ) {
		$description = $property['description'] ?? '';
		$tip = '<b>Definition</b><br><i>' . $field . '</i><br>' . $description;
		$class = $required ? 'wpla_required_row' : 'wpla_optional_row';

		return '<tr id="wpla_tpl_row_'. $field .'" class="wpla_tpl_row '. $class .' '. $css_class. '">
            <td width="40%">
                <span class="wpla_field_label">'. $label .'</span>
                <img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . WPLA_URL . '/img/help.png" height="16" width="16" />
            </td>';


	}

	/**
	 * Generates the additional options for the SELECT elements which include the product attributes and custom fields
	 * @param array  $allowed_values
	 * @param string $selected
	 *
	 * @return string
	 */
	private function buildOptionGroups( $allowed_values, $selected = '', $keys = [] ) {
		$product_attributes = \WPLA_ProductWrapper::getAttributeTaxonomies();

        // Render the Allowed Values optgroup
		$html = '<optgroup label="'. __('Select from Allowed Values', 'wp-lister-for-amazon' ) .'"><option value="">&mdash; '. __('none', 'wp-lister-for-amazon') .' &mdash;</option>';

        foreach ( $allowed_values as $key => $value ) {
			$selected_attr = '';
			if ( $key != "" && in_array( $key, $keys ) ) {
				//$key = $keys[ $key ];
				if ( is_array($selected) ) {
					if ( in_array( $key, $selected ) ) {
						$selected_attr = 'selected="selected"';
					}
				} elseif ( $key == $selected ) {
					$selected_attr = 'selected="selected"';
				}

			} else {
				$key = $value;
				$selected_attr = $value == $selected ? 'selected="selected"' : '';
			}

            $html .= '<option value="'. esc_attr( $key ) .'" '. $selected_attr .'>'. $value .'</option>';
		}

        $html .= '</optgroup>';

        // Render the Product Attributes optgroup
		$html .= '<optgroup label="'. __('Pull value from Product Attribute', 'wp-lister-for-amazon') .'">';

		foreach ( $product_attributes as $attribute ) {
			$value = '[' . str_replace( 'pa_', 'attribute_', $attribute->name ) . ']';

			$selected_str = '';
			if ( is_array( $selected ) ) {
				if ( in_array( $value, $selected ) ) {
					$selected_str = 'selected="selected"';
				}
			} elseif ( $selected == $value ) {
				$selected_str = 'selected="selected"';
			}

			$html .= '<option value="'. $value .'" '. $selected_str .'>'. $attribute->label .'</option>';
		}

		$html .= '</optgroup>';

        // Render all Custom Values
        $html .= '<optgroup label="'. __('Custom Values', 'wp-lister-for-amazon' ) .'">';



        /*$field_name = $field['field'];
        $wpl_values_array = !is_array( $wpl_values[ $field_name ]['values'] ) ? explode('|', $wpl_values[ $field_name ]['values'] ) : $wpl_values[ $field_name ];
        if ( isset( $wpl_profile_field_data[ $field_name ] ) )   if ( ! in_array( $wpl_profile_field_data[ $field_name ], $wpl_values_array ) ) {
            echo '<option value="'. $wpl_profile_field_data[ $field_name ] .'" selected>'. $wpl_profile_field_data[ $field_name ] .'</option>';
        }*/

        $wpl_other_shortcodes = array(
            '[---]' => '-- leave empty --',
        );

        // handle custom shortcodes registered by wpla_register_profile_shortcode()
        foreach (WPLA()->getShortcodes() as $key => $custom_shortcode) {
            $wpl_other_shortcodes[ "[$key]" ] = $custom_shortcode['title'];
        }

        // handle custom variation meta fields
        $variation_meta_fields = get_option('wpla_variation_meta_fields', array() );
        foreach ( $variation_meta_fields as $key => $varmeta ) {
            $key = 'meta_'.$key;
            $wpl_other_shortcodes[ "[$key]" ] = $varmeta['label'];
        }

        foreach ( $wpl_other_shortcodes as $value => $label ) {
			$selected_attr = '';

			if ( is_array( $selected ) ) {
				if ( in_array( $value, $selected ) ) {
					$selected_attr = 'selected="selected"';
				}
			} elseif ($value == $selected) {
				$selected_attr = 'selected="selected"';
			}

            $html .= '<option value="'. $value .'" '. $selected_attr .'>'. $label .'</option>';
        }
        
        // Check if the selected value is a custom value that's not in any of the predefined lists
        if ( !empty( $selected ) && !is_array( $selected ) ) {
            $is_custom_value = true;
            
            // Check if it's in allowed values
            if ( array_key_exists( $selected, $allowed_values ) || in_array( $selected, $allowed_values ) ) {
                $is_custom_value = false;
            }
            
            // Check if it's a product attribute shortcode
            foreach ( $product_attributes as $attribute ) {
                $attr_value = '[' . str_replace( 'pa_', 'attribute_', $attribute->name ) . ']';
                if ( $selected == $attr_value ) {
                    $is_custom_value = false;
                    break;
                }
            }
            
            // Check if it's in other shortcodes
            if ( array_key_exists( $selected, $wpl_other_shortcodes ) ) {
                $is_custom_value = false;
            }
            
            // If it's a custom value, add it as a selected option
            if ( $is_custom_value ) {
                $html .= '<option value="'. esc_attr( $selected ) .'" selected="selected">'. esc_html( $selected ) .'</option>';
            }
        }
        
        $html .= '</optgroup>';

		return $html;
	}

	/**
	 * Extracts allowed values from enum, anyOf, oneOf or allOf.
	 *
	 * @param array $schema  The property schema.
	 * @return array    [value => label, …] or empty array if none found.
	 */
	protected function extractEnumOptions( array $schema ) {
		return self::extractEnumOptionsFromSchema( $schema );
	}

	/**
	 * Static utility to extract enum options from schema.
	 * Used by both AmazonSchemaFormGenerator and ProfileProductTypeConverter.
	 *
	 * @param array $schema The field schema
	 * @return array Array of [value => label] or empty array if none found
	 */
	public static function extractEnumOptionsFromSchema( array $schema ) {
		// Direct enum
		if ( isset( $schema['enum'] ) ) {
			$labels = $schema['enumNames'] ?? $schema['enum'];
			return array_combine( $schema['enum'], $labels );
		}
		
		// Direct items enum (items.enum)
		if ( isset( $schema['items']['enum'] ) ) {
			$enum_values = $schema['items']['enum'];
			$enum_labels = $schema['items']['enumNames'] ?? $enum_values;
			return array_combine( $enum_values, $enum_labels );
		}
		
		// Nested enum (items.properties.value.enum)
		if ( isset( $schema['items']['properties']['value']['enum'] ) ) {
			$enum_values = $schema['items']['properties']['value']['enum'];
			$enum_labels = $schema['items']['properties']['value']['enumNames'] ?? $enum_values;
			return array_combine( $enum_values, $enum_labels );
		}
		
		// Composites (anyOf, oneOf, allOf)
		foreach ( ['anyOf', 'oneOf', 'allOf'] as $composite ) {
			if ( ! empty( $schema[ $composite ] ) && is_array( $schema[ $composite ] ) ) {
				$options = [];
				foreach ( $schema[ $composite ] as $entry ) {
					if ( isset( $entry['enum'] ) ) {
						$labels = $entry['enumNames'] ?? $entry['enum'];
						$options += array_combine( $entry['enum'], $labels );
					}
				}
				return $options;
			}
		}
		
		// Nested composites in items (items.anyOf, items.oneOf, items.allOf)
		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			foreach ( ['anyOf', 'oneOf', 'allOf'] as $composite ) {
				if ( ! empty( $schema['items'][ $composite ] ) && is_array( $schema['items'][ $composite ] ) ) {
					$options = [];
					foreach ( $schema['items'][ $composite ] as $entry ) {
						if ( isset( $entry['enum'] ) ) {
							$labels = $entry['enumNames'] ?? $entry['enum'];
							$options += array_combine( $entry['enum'], $labels );
						}
					}
					if ( ! empty( $options ) ) {
						return $options;
					}
				}
			}
		}
		
		return [];
	}


}