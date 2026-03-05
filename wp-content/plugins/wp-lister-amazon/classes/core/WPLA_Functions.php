<?php
/**
 * globally available functions
 */


// get instance of WP-Lister object (singleton)
function WPLA() {
    return WPLA_WPLister::get_instance();
}

function wpla_get_license_email() {
	$api_email = get_option( 'wpla_last_active_license_email', false );

	if ( !$api_email ) {
		$api_email = get_option( 'wpla_activation_email', '' );
	}

	return $api_email;
}

function wpla_get_license_key() {
	$api_key   = get_option( 'wpla_last_active_license_key', false );

	if ( !$api_key ) {
		$api_key = get_option( 'wpla_api_key' );
	}

	return $api_key;
}


// custom tooltips
function wpla_tooltip( $desc ) {
	if ( defined('WPLISTER_RESELLER_VERSION') ) $desc = apply_filters( 'wpla_tooltip_text', $desc );
	if ( defined('WPLISTER_RESELLER_VERSION') && apply_filters( 'wplister_reseller_disable_tooltips', false ) ) return;
    echo '<img class="help_tip" data-tip="' . esc_attr( $desc ) . '" src="' . WPLA_URL . '/img/help.png" height="16" width="16" />';
}

// compare SKUs taking into consideration the wpla_case_sensitive_sku_matching option
function wpla_check_sku_match( $sku1, $sku2 ) {
    if ( get_option( 'wpla_case_sensitive_sku_matching', 0 ) ) {
        return $sku1 == $sku2;
    } else {
        return strtolower( $sku1 ) == strtolower( $sku2 );
    }
}

// un-CamelCase string
function wpla_spacify( $str ) {
	return preg_replace('/([a-z])([A-Z])/', '$1 $2', $str);
}

// make logger available in static methods (obsolete since WPLA())
function wpla_logger_start_timer($key) {
	WPLA()->logger->startTimer($key);
}
function wpla_logger_end_timer($key) {
	WPLA()->logger->endTimer($key);
}

/**
 * Show admin message
 * @param $message
 * @param string $type info, warn or error
 * @param array $params accepts persistent and dismissible keys
 *
 * $params keys:
 * bool params[persistent]  Set to TRUE to store message as a transient to be shown on the next page load
 * bool params[dismissible] Set to TRUE to make the notification dismissible
 */
function wpla_show_message( $message, $type = 'info', $params = [] ) {
	$params = wp_parse_args( $params, array(
		'persistent'    => false,
		'dismissible'   => false,
	));

	WPLA()->messages->add_message( $message, $type, $params );
}

// Return TRUE if the current request is done via AJAX
function wpla_request_is_ajax() {
	return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) || ( isset($_POST['action']) && ( $_POST['action'] == 'editpost' ) ) ;
}

// Return TRUE if the current request is done via the REST API
function wpla_request_is_rest() {
	return ( (defined( 'WC_API_REQUEST' ) && WC_API_REQUEST) || (defined( 'REST_REQUEST' ) && REST_REQUEST) );
}


// register custom shortcode to be used in listing profiles
function wpla_register_profile_shortcode( $shortcode, $title, $callback ) {

	WPLA()->shortcodes[ $shortcode ] = array(
		'slug'       => $shortcode,
		'title'      => $title,
		'callback'   => $callback,
		'content'    => false,
	);

}

// Shorthand way to access a product's property
function wpla_get_product_meta( $product_id, $key ) {
    $product = $product_id;
    if ( !is_object( $product ) ) {
        $product = WPLA_ProductWrapper::getProduct( $product_id );
    }

    // Check for a valid product object
    if ( ! $product || ! $product->exists() ) {
        return false;
    }

    if ( $key == 'product_type' && is_callable( array( $product, 'get_type' ) ) ) {
        return $product->get_type();
    } elseif ( $key == 'stock' && is_callable( array( $product, 'get_stock_quantity')) ) {
        return $product->get_stock_quantity();
    }

    // custom WPLA postmeta
    if ( substr( $key, 0, 7 ) == 'amazon_' ) {
        return get_post_meta( $product_id, '_'. $key, true );
    }

    if ( is_callable( array( $product, 'get_'. $key ) ) ) {
        return call_user_func( array( $product, 'get_'. $key ) );
    } else {
        return $product->$key;
    }
}


function wpla_get_order_meta( $order_id, $key ) {
    $order = $order_id;
    if ( ! is_object( $order ) ) {
        $order = wc_get_order( $order_id );
    }

    if ( ! $order ) {
        return false;
    }

    if ( $key == 'order_date' && is_callable( array( $order, 'date_created' ) ) ) {
        return $order->get_date_created();
    }

    if ( is_callable( array( $order, 'get_'. $key ) ) ) {
        return call_user_func( array( $order, 'get_'. $key ) );
    } else {
        return $order->$key;
    }
}

/**
 * Our own version of wc_clean to prevent errors in case WC gets deactivated
 * @param  array|string $var
 * @return array|string
 */
function wpla_clean( $var ) {
    if ( is_callable( 'wc_clean' ) ) {
        return wc_clean( $var );
    } else {
        if ( is_array( $var ) ) {
            return array_map( 'wpla_clean', $var );
        } else {
            return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
        }
    }
}

/**
 * Clean and escape the value of `$var` so it's safe to use as an HTML attribute
 * @param  array|string $var
 * @return array|string
 */
function wpla_clean_attr( $var ) {
	return esc_attr( wpla_clean( $var ) );
}

// fetch Amazon items by column
// example: wpla_get_listings_where( 'status', 'changed' );
function wpla_get_listings_where( $column, $value ) {
    return WPLA_ListingQueryHelper::getWhere( $column, $value );
}

function wpla_get_ship_from_select_options() {
    $addresses = get_option( 'wpla_ship_from_addresses', array() );
    $options = array( '-1' => __( '--- Ignore Ship From ---', 'wp-lister-for-amazon' ) );
    if ( !empty( $addresses ) ) {
        foreach ( $addresses as $idx => $address ) {
            $options[ $address['name'] ] = $address['name'];
        }
    }
    return $options;
}

function wpla_get_ship_from_address( $idx ) {
    if ( $idx == -1 ) return false; // -1 means to ignore ship-from

    $addresses = get_option( 'wpla_ship_from_addresses', array() );

    $names = wp_list_pluck( $addresses, 'name' );

    $idx = array_search( $idx, $names );

    if ( $idx !== false && isset( $addresses[ $idx ] ) ) {
        return $addresses[ $idx ];
    }

    return false;
}

/**
 * Wrapper function for as_enqueue_async_action since it is not available in some old WC installations. If the async
 * function is not available, as_schedule_single_action() is called instead and passing in the current time so it is triggered
 * on the next cron run.
 *
 * @param string $hook The hook to trigger.
 * @param array  $args Arguments to pass when the hook triggers.
 * @param string $group The group to assign this job to.
 * @return int The action ID.
 */
function wpla_enqueue_async_action( $hook, $args = array(), $group = '' ) {
    if ( function_exists( 'as_enqueue_async_action' ) ) {
        return as_enqueue_async_action( $hook, $args, $group );
    } else {
        return as_schedule_single_action( time(), $hook, $args, $group );
    }
}

/**
 * Check if the given $str is a JSON object
 * https://stackoverflow.com/a/43244302
 *
 * @param $str
 * @return bool
 */
function wpla_is_json($str) {
    $json = json_decode($str);
    return $json && $str != $json;
}

/**
 * Check if the given $date is a valid date
 * @param string $date
 * @param string $format
 * @return bool
 */
function wpla_is_valid_date($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $date === $d->format($format);
}

/**
 * URL-encode the image filename without affecting the query string
 *
 * @param string $url
 *
 * @return string
 */
function wpla_encode_image_url( $url ) {
	// urlencode utf8 characters in image filename
	$query_string       = parse_url( $url, PHP_URL_QUERY );
	$url_without_query  = str_replace( '?'. $query_string, '', $url );

	$filename_before = basename( $url_without_query );
	$filename_after  = rawurlencode( $filename_before );
	$url_without_query = str_replace( $filename_before, $filename_after, $url_without_query );
	$url = ( $query_string ) ? $url_without_query .'?'. $query_string : $url_without_query;

	$url = wpla_remove_https( $url );

	return $url;
}

function wpla_remove_https( $url ) {
	// fix relative urls
	if ( '/wp-content/' == substr( $url, 0, 12 ) ) {
		$url = str_replace('/wp-content', content_url(), $url);
	}

	// fix https urls
	$url = str_replace( 'https://', 'http://', $url );
	$url = str_replace( ':443', '', $url );

	return $url;
}

function wpla_convert_legacy_item_condition( $condition ) {
	$legacy_map = [
		'Club'                  => 'club_club',
		'CollectibleAcceptable' => 'collectible_acceptable',
		'CollectibleGood'       => 'collectible_good',
		'CollectibleLikeNew'    => 'collectible_like_new',
		'CollectibleVeryGood'   => 'collectible_very_good',
		'New'                   => 'new_new',
		'NewOem'                => 'new_oem',
		'NewOpenBox'            => 'new_open_box',
		'Refurbished'           => 'refurbished_refurbished',
		'UsedAcceptable'        => 'used_acceptable',
		'UsedGood'              => 'used_good',
		'UsedLikeNew'           => 'used_like_new',
		'UsedVeryGood'          => 'used_very_good'
	];

	if ( isset( $legacy_map[ $condition ] ) ) {
		$condition = $legacy_map[ $condition ];
	}

	return $condition;
}