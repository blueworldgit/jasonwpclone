<?php
/**
 * add amazon feed attributes metaboxes to product edit page
 */

class WPLA_Product_Feed_MetaBox {

	function __construct() {

		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'woocommerce_process_product_meta', array( &$this, 'save_meta_box' ), 0, 2 );

        // add_action( 'wp_ajax_wpla_update_custom_feed_columns', 	array( &$this, 'wpla_update_custom_feed_columns' ) ); 
        add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

    function enqueue_scripts() {
        $screen = get_current_screen();

        if ( $screen && $screen->id != 'product' ) {
            return;
        }

        $post_id = $_REQUEST['post'] ?? 0;

	    wp_register_script( 'wpla_product_metabox', WPLA_URL.'js/classes/ProductTypesMetabox.js', array( 'jquery', 'jqueryFileTree' ), time() );
	    wp_localize_script( 'wpla_product_metabox', 'wpla_product_metabox', [
		    'post_id'               => $post_id,
            'nonce'                 => wp_create_nonce( 'wpla_ajax_nonce' ),
		    'current_tpl_id'        => get_post_meta( $post_id, '_wpla_custom_feed_tpl_id' , true ),
            'current_product_type'  => get_post_meta( $post_id, '_wpla_custom_product_type' , true ),
            'current_marketplace'   => get_post_meta( $post_id, '_wpla_custom_marketplace_id' , true ),
	    ] );
        wp_enqueue_script( 'wpla_product_metabox' );


	    //add_action( 'wp_print_scripts', array( $this, 'print_scripts' ) );
    }

    function print_scripts() {
        global $post;
        //$post_id = $_REQUEST['post'] ?? 0;


	    $this->add_inline_css();

    }

	function add_meta_boxes( $type, $post= null ) {
		$title = __( 'Amazon Feed Attributes', 'wp-lister-for-amazon' );
        $post_id = method_exists( $post, 'get_id' ) ? $post->get_id() : $post->ID;
        if ( !$this->usesFeedTemplate( $post_id ) ) {
	        $title = __( 'Amazon Product Type', 'wp-lister-for-amazon' );
        }

		add_meta_box( 'wpla-amazon-feed_columns', $title, array( &$this, 'meta_box_feed_columns' ), 'product', 'normal', 'default');

	}

	function meta_box_feed_columns( $post ) {
		$this->display_feed_template_selector( $post );

        // get custom feed_columns as array of attachment_ids
		// $custom_feed_tpl_id  = get_post_meta( $post->ID, '_wpla_custom_feed_tpl_id' , true );
		// $custom_feed_columns = get_post_meta( $post->ID, '_wpla_custom_feed_columns', true );
		// echo "<pre>";print_r($custom_feed_columns);echo"</pre>";#die();        

	} // meta_box_feed_columns()

    public function displayProfileConversionMessage( $profile_id ) {
        $profile = new WPLA_AmazonProfile( $profile_id );
        ?>
        <div id="profile_conversion_message" style="border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 4px;">
            <h4 style="margin-top: 0;"><?php _e('Profile Conversion Required', 'wp-lister-for-amazon' ); ?></h4>
            <p style="margin-bottom: 10px;">
                <strong>This product is assigned to profile "<?php echo esc_html( $profile->profile_name ); ?>" which is still using the old feed template system.</strong>
            </p>
            <p style="margin-bottom: 15px;">
                <?php _e( 'To convert this product to use the new Product Types, you must first convert the profile. Individual products cannot be converted while their profile uses feed templates.' ,'wp-lister-for-amazon' ); ?>
            </p>
            <p style="margin-bottom: 0;">
                <a href="<?php echo admin_url('admin.php?page=wpla-tools&tab=profile-converter' ); ?>" class="button button-primary">
                    <?php _e( 'Profile Converter', 'wp-lister-for-amazon' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    public function displayProductTypeRecommendations( $recommendations, $installed ) {
        ?>
        <div id="product_type_recommendations">
            <p>
                Important: Amazon is discontinuing Custom Feed Templates. Please select the recommended Product Type, and WP-Lister will attempt to map your existing attributes to the new format.
            </p>
            <p>
                If you don't see a suitable Product Type for this listing, go to <a href="admin.php?page=wpla-settings&tab=product_types">Product Types</a>, then search for and install the appropriate one.
            </p>

            <label for="wpl-text-product_type" class="text_label">
		        <?php echo __( 'New Product Type', 'wp-lister-for-amazon' ); ?>
            </label>
            <select id="wpl_new_product_type" name="wpla_new_product_type" class="required-entry select" style="width:55%;">
            <?php if (! empty( $recommendations ) ) : ?>
                <optgroup label="<?php _e('Recommended Replacements', 'wp-lister-for-amazon' ); ?>">
            <?php
            foreach ( $recommendations as $recommendation ):
            ?>
                    <option value="<?php esc_attr_e( $recommendation->getProductType() ); ?>"><?php _e( $recommendation->getDisplayName() ); ?></option>
            <?php
            endforeach;
            ?>
                </optgroup>
            <?php endif; ?>
            <?php if (! empty( $installed ) ) : ?>
                <optgroup label="<?php _e('Installed Product Types', 'wp-lister-for-amazon' ); ?>">
                    <?php
                    foreach ( $installed as $product_type ):
                        ?>
                        <option value="<?php esc_attr_e( $product_type->getProductType() ); ?>"><?php _e( $product_type->getDisplayName() ); ?></option>
                    <?php
                    endforeach;
                    ?>
                </optgroup>
            <?php endif; ?>
            </select>
            <button class="button-secondary" type="button" id="convert_feed_template"><?php _e('Convert'); ?></button>
        </div>
        <hr/>
        <?php
	    $this->add_inline_css();
    }

    public function displayProductTypeSelector( $post ) {
	    $custom_product_type    = get_post_meta( $post->ID, '_wpla_custom_product_type', true );
	    $custom_marketplace_id  = get_post_meta( $post->ID, '_wpla_custom_marketplace_id', true );
        $product_types_array = [];

        if ( $custom_marketplace_id ) {
	        $product_types_array = \WPLab\Amazon\Models\AmazonProductTypesModel::getByMarketplace( $custom_marketplace_id );
        }

        ?>
        <label for="wpl_marketplace_id" class="text_label">
		    <?php echo __( 'Marketplace', 'wp-lister-for-amazon' ); ?>
        </label>
        <select id="wpl_marketplace_id" name="wpla_marketplace_id" class="required-entry select">
            <option value=""> -- use profile setting -- </option>
		    <?php
            $marketplaces = WPLA_AmazonMarket::getAllFromAccounts();

		    foreach ( $marketplaces as $marketplace_id => $marketplace ):
			    ?>
                <option <?php selected( $custom_marketplace_id, $marketplace_id); ?> value="<?php echo esc_attr($marketplace_id); ?>"><?php echo $marketplace; ?></option>
		    <?php endforeach; ?>
        </select>

        <label for="wpl_product_type" class="text_label">
		    <?php echo __( 'Product Type', 'wp-lister-for-amazon' ); ?>
		    <?php wpla_tooltip('') ?>
        </label>
        <select id="wpl_product_type" name="wpla_product_type" class="required-entry select select2" data-tags="false" style="width: 65%;">
            <option value=""> -- use profile setting -- </option>
            <?php foreach ( $product_types_array as $type ): ?>
            <option value="<?php esc_attr_e( $type->product_type ); ?>" <?php selected( $custom_product_type, $type->product_type ); ?>><?php echo $type->display_name; ?></option>
            <?php endforeach; ?>
        </select>
        <br class="clear" />
        <p class="desc">
		    <?php _e('You can add additional Product Types at <a href="admin.php?page=wpla-settings&tab=product_types">Amazon » Settings » Product Types</a>.', 'wp-lister-amazon' ); ?>
        </p>

        <div id="PropertiesDataBox">
            <hr>
            <!-- <h3 class="hndle"><span><?php echo __( 'Listing Properties', 'wp-lister-for-amazon' ); ?></span></h3> -->
            <div class="x-inside" id="wpla_feed_data_wrapper">
            </div>
        </div>
        <?php
	    $this->add_inline_css();
    }

	/**
	 * Display feed template selector or product type selector based on profile conversion status
	 * 
	 * @param WP_Post $post The product post object
	 * @return void
	 */
	function display_feed_template_selector( $post ) {
        // Determine whether to show old template selector or new Product Type selector
        // Priority: Converted profiles always show Product Type interface
		$custom_feed_tpl_id     = get_post_meta( $post->ID, '_wpla_custom_feed_tpl_id' , true );
        $custom_product_type    = get_post_meta( $post->ID, '_wpla_custom_product_type', true );
        
        // Check if product is assigned to a profile and get profile info
        $assigned_profile_uses_template = false;
        $assigned_profile_converted = false;
        $assigned_profile_id = WPLA_AmazonProfile::getProfileForProduct( $post->ID );
        if ( $assigned_profile_id ) {
            $assigned_profile = new WPLA_AmazonProfile( $assigned_profile_id );
            $assigned_profile_uses_template = !empty( $assigned_profile->tpl_id );
            $assigned_profile_converted = !empty( $assigned_profile->product_type );
        }

        // Show new Product Type selector if:
        // 1. Profile has been converted to Product Types, OR
        // 2. Product has custom product type, OR  
        // 3. No template dependencies exist
        $should_show_product_type_selector = (
            $assigned_profile_converted || 
            $custom_product_type || 
            (!$custom_feed_tpl_id && !$assigned_profile_uses_template)
        );

        if ( $should_show_product_type_selector ) {
            return $this->displayProductTypeSelector( $post );
        }

        // Look for Product Type recommendations to replace the current feed template
        $converter = new WPLab\Amazon\Helper\ProfileProductTypeConverter();
        $product_type_recs = $converter->getRecommendedProductTypeFromTemplate( $custom_feed_tpl_id );

		$marketplace_id = $converter->getFeedTemplateMarketplace( $custom_feed_tpl_id );
		$mdl    = new \WPLab\Amazon\Models\AmazonProductTypesModel();
		$filtered  = $mdl->getFiltered([
            'marketplace_id' => $marketplace_id
		]);
        $installed = $filtered['items'];

		// get templates
		$templates = WPLA_AmazonFeedTemplate::getAll();


		// separate ListingLoader templates
		$category_templates = array();
		$liloader_templates = array();
		foreach ($templates as $tpl) {
			if ( $tpl->title == 'Offer' ) {
				$tpl->title = "Listing Loader";
				$liloader_templates[] = $tpl;
			} elseif ( $tpl->title == 'Inventory Loader' ) {
				$liloader_templates[] = $tpl;
			} else {
				$category_templates[] = $tpl;
			}
		}

		// compatibility with profile code
		$wpl_category_templates = $category_templates;
		$wpl_liloader_templates = $liloader_templates;

        // Show profile conversion message if the assigned profile uses feed templates
        if ( $assigned_profile_uses_template ) {
            $this->displayProfileConversionMessage( $assigned_profile_id );
        } else {
            //if ( !empty($product_type_recs) ) {
                $this->displayProductTypeRecommendations( $product_type_recs, $installed );
            //}
        }

		?>
							<label for="wpl-text-tpl_id" class="text_label">
								<?php echo __( 'Feed Template', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Each main category on Amazon uses a different feed template with special fields for that particular category.<br>You need to select the right template for your category and make sure all the required fields are filled in - or are populated from product details or attributes.') ?>
							</label>
							<select id="wpl-text-tpl_id" name="wpla_tpl_id" class="required-entry select">
							<option value="">-- <?php echo __( 'Select feed template', 'wp-lister-for-amazon' ) ?> --</option>
							<optgroup label="Generic Feeds">
								<?php foreach ( $wpl_liloader_templates as $tpl ) : ?>
									<option value="<?php echo $tpl->id ?>" 
										<?php if ( $custom_feed_tpl_id == $tpl->id ) : ?>
											selected="selected"
										<?php endif; ?>
										<?php $site = WPLA()->memcache->getMarket( $tpl->site_id ); ?>
										><?php echo $tpl->title ?> (<?php echo $site ? $site->code : '?' ?>)</option>
								<?php endforeach; ?>
							</optgroup>
							<optgroup label="Category Specific Feeds">
								<?php foreach ( $wpl_category_templates as $tpl ) : ?>
									<option value="<?php echo $tpl->id ?>" 
										<?php if ( $custom_feed_tpl_id == $tpl->id ) : ?>
											selected="selected"
										<?php endif; ?>
										<?php $site = WPLA()->memcache->getMarket( $tpl->site_id ); ?>
										><?php echo ucfirst($tpl->title) ?> (<?php echo $site ? $site->code : '?' ?>) - version <?php echo $tpl->version ?> </option>
								<?php endforeach; ?>
							</optgroup>
							</select>
							<br class="clear" />
							<p class="desc" style="">
								<?php $link = sprintf( '<a href="%s">%s</a>', 'admin.php?page=wpla-settings&tab=categories', __( 'Amazon &raquo; Settings &raquo; Categories', 'wp-lister-for-amazon' ) ); ?>
								<?php echo sprintf( __( 'You can add additional feed templates at %s.', 'wp-lister-for-amazon' ), $link ); ?>
							</p>


					<div id="FeedDataBox">
						<hr>
						<!-- <h3 class="hndle"><span><?php echo __( 'Feed Attributes', 'wp-lister-for-amazon' ); ?></span></h3> -->
						<div class="x-inside" id="wpla_feed_data_wrapper">
						</div>
					</div>

					<!-- hidden ajax categories tree -->
					<div id="amazon_categories_tree_wrapper">
						<div id="amazon_categories_tree_container">TEST</div>
					</div>

		<?php
        $this->print_scripts();
	} // display_feed_template_selector()

	function add_inline_css() {
		?>
			<style type="text/css">
				#wpla-amazon-feed_columns p.desc {
					font-size: smaller;
					font-style: italic;
					margin-top: 0;
					margin-left: 35%;
				}
				#wpla-amazon-feed_columns label.text_label {
					display: block;
					float: left;
					width: 33%;
					margin: 1px;
					padding: 3px;
					/*white-space:nowrap;*/
				}
				#wpla-amazon-feed_columns input.text_input,
				#wpla-amazon-feed_columns textarea,
				#wpla-amazon-feed_columns select.select {
					width: 65%;
					margin-bottom: 5px;
					/*padding: 3px 8px;*/
				}
				#feed-template-data {
					width: 100%;
				}


				/* BTG selector */
				#amazon_categories_tree_wrapper {
					/*max-height: 320px;*/
					/*margin-left: 35%;*/
					overflow: auto;
					width: 65%;
					display: none;
				}


				/* Tooltips */
				#wpla-amazon-feed_columns img.help_tip {
					vertical-align: bottom;
					float: right;
					margin: 0;
					margin-top: 2px;
				}

				#wpla-amazon-feed_columns th img.help_tip {
					float: none;
					margin: -2px;
				}

                #feed-template-data {
                    width: 100%;
                    margin-top: 1em;
                }

                #feed-template-data th {
                    text-align: left;
                }

                #feed-template-data th h4 {
                    margin-bottom: 0;
                }

                #feed-template-data input, #feed-template-data select {
                    width: 90%;
                }

                #feed-template-searchbar {
                    padding-bottom: 0.5em;
                    border-bottom: 1px solid #eee;
                }

                .select2-container {
                    box-sizing: border-box;
                    display: inline-block;
                    margin-bottom: 5px !important;
                }

			</style>
		<?php
	} // add_inline_css()


	function add_inline_js( $post ) {

	} // add_inline_js()


	function save_meta_box( $post_id, $post ) {

		if ( isset( $_POST['wpla_tpl_id'] ) ) {
            // update selected template
            update_post_meta( $post_id, '_wpla_custom_feed_tpl_id',	wpla_clean( $_POST['wpla_tpl_id'] ) );

            if ( empty( $_POST['wpla_tpl_id'] ) ) {
                // delete the custom feed columns if no template is set at the product level
                delete_post_meta( $post_id, '_wpla_custom_feed_columns' );
            } else {
                // update template columns
                $tpl_columns = $this->getPreprocessedTemplateColumns();
                update_post_meta( $post_id, '_wpla_custom_feed_columns', $tpl_columns );
            }
		}

        if ( isset( $_POST['wpla_product_type'] ) ) {
	        $tpl_columns = $this->getPreprocessedProductTypeColumns();
	        update_post_meta( $post_id, '_wpla_custom_feed_columns', $tpl_columns );
	        update_post_meta( $post_id, '_wpla_custom_product_type',    wpla_clean( $_POST['wpla_product_type'] ) );
	        update_post_meta( $post_id, '_wpla_custom_marketplace_id',  wpla_clean( $_POST['wpla_marketplace_id'] ) );
        }

	} // save_meta_box()

	public function getPreprocessedTemplateColumns() {

		$prefix     = 'tpl_col_';
		$skip_empty = true; 
		$field_data = array();

		foreach ( $_POST as $key => $val ) {
			if ( ! $val && $skip_empty ) continue;
			if ( substr( $key, 0, strlen($prefix) ) == $prefix ) {
				$field = substr( $key, strlen($prefix) );
				$val   = stripslashes( $val );
				
				$field_data[$field] = $val;	
			}
		}

		return $field_data;
	} // getPreprocessedPostData()

	public function getPreprocessedProductTypeColumns() {

        $converter = new WPLab\Amazon\Helper\ProfileProductTypeConverter();
		$prefix     = 'tpl_col_';
		$field_data = array();

		foreach ( $_POST as $key => $val ) {
			if ( $this->isEffectivelyEmpty( $val ) ) continue;
			if ( substr( $key, 0, strlen($prefix) ) == $prefix ) {
				$field = substr( $key, strlen($prefix) );
				$val   = $this->processNestedValue( $val );
				
				// If the value is a nested array, flatten it to form field format
				if ( is_array( $val ) ) {
					$flattened = $converter->flattenNestedArrayToFormFields( $val, $field );
					$field_data = array_merge( $field_data, $flattened );
				} else {
					$field_data[$field] = $val;
				}
			}
		}

		return $field_data;
	} // getPreprocessedPostData()


    public function returnJSON( $data ) {
        header('content-type: application/json; charset=utf-8');
        echo json_encode( $data );
    }

    private function usesFeedTemplate( $product_id ) {
        // Check product-level feed template setting
	    $custom_feed_tpl_id = get_post_meta( $product_id, '_wpla_custom_feed_tpl_id' , true );
	    if ( $custom_feed_tpl_id ) {
	        return true;
	    }
	    
	    // Check if product is assigned to a profile that uses feed templates
        $assigned_profile_id = WPLA_AmazonProfile::getProfileForProduct( $product_id );
        if ( $assigned_profile_id ) {
            $assigned_profile = new WPLA_AmazonProfile( $assigned_profile_id );
            return !empty( $assigned_profile->tpl_id );
        }
        
        return false;
    }

	/**
	 * Recursively process nested array values, applying stripslashes and filtering empty values
	 *
	 * @param mixed $value The value to process
	 * @param bool $skip_empty Whether to skip empty values
	 * @return mixed Processed value
	 */
	private function processNestedValue( $value, $skip_empty = true ) {
		if ( is_array( $value ) ) {
			$processed = array();
			
			foreach ( $value as $key => $item ) {
				$processed_item = $this->processNestedValue( $item, $skip_empty );
				
				// Skip empty values if configured to do so
				if ( $skip_empty && $this->isEffectivelyEmpty( $processed_item ) ) {
					continue;
				}
				
				$processed[$key] = $processed_item;
			}
			
			return $processed;
		} else {
			// Process scalar values
			return is_string( $value ) ? stripslashes( $value ) : $value;
		}
	}

	/**
	 * Check if a value is effectively empty (handles nested arrays)
	 *
	 * @param mixed $value The value to check
	 * @return bool True if the value is effectively empty
	 */
	private function isEffectivelyEmpty( $value ) {
		if ( is_array( $value ) ) {
			// An array is empty if it has no elements or all elements are effectively empty
			if ( empty( $value ) ) {
				return true;
			}
			
			foreach ( $value as $item ) {
				if ( ! $this->isEffectivelyEmpty( $item ) ) {
					return false;
				}
			}
			
			return true;
		} else {
			// For scalar values, use standard empty check but treat '0' as non-empty
			return empty( $value ) && $value !== '0' && $value !== 0;
		}
	}


} // class WPLA_Product_Feed_MetaBox
// $WPLA_Product_Feed_MetaBox = new WPLA_Product_Feed_MetaBox();
