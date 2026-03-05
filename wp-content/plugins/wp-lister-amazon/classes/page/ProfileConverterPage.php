<?php

namespace WPLab\Amazon\Pages;

use WPLab\Amazon\Core\AmazonProductType;
use WPLab\Amazon\Helper\ProfileProductTypeConverter;

/**
 * WPLA_ProfileConverterPage class
 * 
 */

class ProfileConverterPage extends \WPLA_Page {

	const slug = 'tools';
	var $existing_skus = array();
	var $skugenTable;

	public function onWpInit() {
	}

	

	public function handleSubmit() {
		if ( ! current_user_can('manage_amazon_listings') ) return;

		$action = $_POST['action'] ?? '';

		if ( $action == 'wpla_convert_profiles' ) {
			check_admin_referer( 'wpla_convert_profiles' );

			$converter          = new \WPLab\Amazon\Helper\ProfileProductTypeConverter();
			$tpl_actions        = $_POST['tpl_actions'] ?? [];
			$new_product_types  = $_POST['tpl_replacements'] ?? [];

			$converted_profiles = get_option( 'wpla_json_converted_profiles', [] );

			foreach ( $tpl_actions as $tpl_id => $convert ) {
				if ( !$convert ) {
					continue;
				}

				$feed_tpl = new \WPLA_AmazonFeedTemplate( $tpl_id );
				$product_type = $new_product_types[ $tpl_id ] ?? false;

				if ( !$product_type ) {
					wpla_show_message( sprintf(__('Could not convert %s because no Product Type could be found', 'wp-lister-for-amazon'), $feed_tpl->title), ['persistent' => true] );
					continue;
				}

				$all_products   = $converter->getProductsUsingFeedTemplate( $tpl_id );

				$marketplace = $converter->getFeedTemplateMarketplace( $tpl_id );

				// make sure the Product Type is installed for the marketplace
				$type_mdl           = new \WPLab\Amazon\Models\AmazonProductTypesModel();
				$product_type_obj   = $type_mdl->getDefinitionsProductType( $product_type, $marketplace, false );

				if ( is_wp_error( $product_type_obj ) ) {
					wpla_show_message( sprintf( __('Could not fetch the Product Type definitions for "%s" from the API for %s. Please try again later.', 'wp-lister-for-amazon'), $product_type, $feed_tpl->title ), ['persistent' => true] );
					continue;
				}

				$product_type_obj->setDisplayName( $product_type );
				$product_type_obj->save();

				// duplicate and convert here!

				// get all the profiles using $tpl_id that we need to convert
				$profiles = \WPLA_AmazonProfile::getAllUsingTemplate( $tpl_id );

				foreach ( $profiles as $profile ) {
					$new_id = \WPLA_AmazonProfile::duplicateProfile( $profile->profile_id );
					$converted_profiles[ $profile->profile_id ] = $new_id;

					// start working on the new profile
					$new_profile = new \WPLA_AmazonProfile( $new_id );
					$converter = new ProfileProductTypeConverter( $new_profile, $new_product_types[ $tpl_id ] );
					$converter->convertProfile();
				}

				foreach ( $all_products as $product_id ) {
					$custom_fields_old = get_post_meta( $product_id, '_wpla_custom_feed_columns', true );
					
					// Ensure we have an array before conversion
					if ( !is_array( $custom_fields_old ) ) {
						$custom_fields_old = [];
					}
					
					$converter = new \WPLab\Amazon\Helper\ProfileProductTypeConverter();
					$custom_fields = $converter->convertFromArray( $custom_fields_old );

					update_post_meta( $product_id, '_wpla_custom_feed_columns_old', $custom_fields_old );
					update_post_meta( $product_id, '_wpla_custom_feed_columns', $custom_fields );
					update_post_meta( $product_id, '_wpla_custom_product_type', $product_type );
					update_post_meta( $product_id, '_wpla_custom_marketplace_id', $marketplace );
				}
			}

			update_option( 'wpla_json_converted_profiles', $converted_profiles, false );

			$msg = __('The selected profile(s) and products have been converted.<br/>Go to the <a href="admin.php?page=wpla-profiles">Profiles page</a> to begin migrating listings to the new Product Type profiles.', 'wp-lister-for-amazon');
			wpla_show_message( $msg, 'info', ['persistent' => true] );
			wp_safe_redirect('admin.php?page=wpla-tools&tab=profile-converter');
			exit;
		}
	}

	public function displayPage() {

		$converter      = new \WPLab\Amazon\Helper\ProfileProductTypeConverter();
		$all_profiles   = \WPLA_AmazonProfile::getProfilesThatNeedConversion();
		$all_products   = $converter->getAllProductsUsingFeedTemplates();
		$all_products_templates = array_keys( $all_products );
		$old_profiles       = [];
		$tpl_replacements   = [];
		$templates          = [];

		foreach ( $all_profiles as $profile ) {
			if ( $profile->tpl_id ) {
				$templates[ $profile->tpl_id ]      = new \WPLA_AmazonFeedTemplate( $profile->tpl_id );
				$old_profiles[ $profile->tpl_id ][] = $profile;

				$tpl_replacements[ $profile->tpl_id ] = $converter->getRecommendedProductTypeFromTemplate( $profile->tpl_id );
			}
		}

		foreach ( $all_products_templates as $product_tpl_id ) {
			if ( !isset( $templates[ $product_tpl_id ] ) ) {
				$templates[ $product_tpl_id ]      = new \WPLA_AmazonFeedTemplate( $product_tpl_id );
				//$old_profiles[ $product_tpl_id ][] = $profile;

				$tpl_replacements[ $product_tpl_id ] = $converter->getRecommendedProductTypeFromTemplate( $product_tpl_id );
			}
		}

		$mdl    = new \WPLab\Amazon\Models\AmazonProductTypesModel();
		$types  = $mdl->getFiltered([
			'per_page'  => 100
		]);

		$installed = [];
		foreach ( $types['items'] as $type ) {
			// don't include in installed if PT is already in the replacements array
			$installed[ $type->getMarketplaceId() ][] = $type;
		}

		//$this->checkForMessages();

	    // create table and fetch items to show
		$active_tab = 'profile-converter';
		$aData = array(
			'plugin_url'	=> self::$PLUGIN_URL,
			'message'		=> $this->message,

			'tools_url'		=> 'admin.php?page='.self::ParentMenuId.'-tools',
			'form_action'	=> 'admin.php?page='.self::ParentMenuId.'-tools'.'&tab='.$active_tab,

			'old_profiles'  => $old_profiles,
			'templates'     => $templates,
			'replacements'  => $tpl_replacements,
			'installed'     => $installed,
			'all_products'  => $all_products
		);
		$this->display( 'tools_profile_converter', $aData );
	} // displaySkuGenPage()

	private function checkForMessages() {
		if ( !empty( $_GET['converted'] ) ) {
			$msg = __('The selected profile(s) and products have been converted.<br/>Go to the <a href="admin.php?page=wpla-profiles">Profiles page</a> to begin migrating listings to the new Product Type profiles.', 'wp-lister-for-amazon');
			$this->message = '<div class="message updated"><p>'. $msg .'</p></div>';
		}
	}

}
