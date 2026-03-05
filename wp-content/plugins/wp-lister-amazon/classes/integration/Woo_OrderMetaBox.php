<?php
/**
 * add amazon metabox to order edit page
 */

## BEGIN PRO ##

class WPLA_Order_MetaBox {
	var $providers;

	static $wc_order_cache = [];

	/**
	 * Constructor
	 */
	function __construct() {

		add_action( 'add_meta_boxes',                       array( &$this, 'add_meta_boxes' ), 10, 2 );
        add_action( 'wp_ajax_wpla_update_amazon_shipment', 	array( &$this, 'update_amazon_shipment_ajax' ) );
        add_action( 'wp_ajax_wpla_submit_order_to_fba', 	array( &$this, 'submit_order_to_fba' ) );

        // Listen to shipping tracking details from the DHL for WooCommerce plugin #40388
        add_action( 'pr_save_dhl_label_tracking', array( $this, 'save_dhl_tracking_details' ), 10, 2 );

        // Listing to UPS tracking number from the UPS plugin (PluginHive) #43545
        add_action( 'woocommerce_process_shop_order_meta', array($this, 'save_ups_tracking_details'), 20 );

		// handle order status changed to "completed" - and complete Amazon order
		add_action( 'woocommerce_order_status_completed', array( $this, 'handle_woocommerce_order_status_update_completed' ), 0, 1 );

		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box' ), 0, 2 );
	}

    /**
     * @param $order_id
     * @return WC_Order
     */
	public static function get_wc_order( $order_id ) {
	    //return wc_get_order($order_id);
	    if ( isset( self::$wc_order_cache[ $order_id ] ) ) {
	        return self::$wc_order_cache[ $order_id ];
        }

	    self::$wc_order_cache[ $order_id ] = wc_get_order( $order_id );
	    return self::$wc_order_cache[ $order_id ];
    }

    /**
     * The list of valid carrier-code from the Shipping Confirmation template
     * <CarrierCode>: https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/amzn-base.xsd
     * @return array
     */
	static function getShippingProviders() {
	    $providers = array(
			'4PX'               => array(
			    'QZ-4PX-Global Express', 'OH/QC-4PX-PostLink Standard Registered Mail', 'PY/PX-4PX-PostLink priority Registered',
                'HW-4PX-PostLink Standard2 Registered', 'JW/O5-4PX-PostLink Economy Registered Mail', 'JY-4PX-PostLink Economy SRM Registered Mail',
                'NX-4PX-PostLink Standard Ordinary', 'FY-4PX-PostLink Standard large letter Ordinary Mail', 'FV-4PX-PostLink Economy large letter Ordinary Mail',
                'ED-4PX-PostLink Economy2 Registered', 'DS-4PX-PostLink Economy2 Ordinary Mail', 'LR-4PX-PostLink Economy railway Ordinary Mail'
            ),
            'A-1'               => array(),
            'AAA Cooper'        => array(),
            'ABF'               => array(),
            'Australia Post-Consignment' => array(),
            'Australia Post-ArticleID' => array(
                'International Post International Courier', 'International Post International Economy Air',
                'International Post International Express', 'International Post International Standard',
                'Letter Post Express Post letter', 'Letter Post Ordinary letter', 'Letter Post Registered Post',
                'Parcel Post Express Post parcel', 'Parcel Post Regular parcel', 'Other'
            ),
            'Asendia'   => array(),
            'Best Buy'  => array(),
            'Blue Package'  => array(),
            'Canada Post'   => array(),
            'CEVA'  => array(),
            'China Post'    => array(
                'China Post e-Courier Packet', 'China Post e-Courier Priority', 'China Post ePacket', 'China Post e-EMS',
                'China Post Ordinary Airmail', 'China Post Registered Airmail'
            ),
            'Conway'    => array(),
            'Couriers Please' => array(),
            'CTTExpress' => array(),
            'DHL' => array(
                'Paket', 'Prio', 'EuroPaket', 'Paket International', 'Express'
            ),
            'DHL eCommerce' => array(
                'Parcel International Direct-Semi', 'Parcel International Direct-Standard', 'Parcel International Direct-Expedited',
                'Packet International Economy', 'Packet International Standard', 'Packet Plus International',
            ),
            'Deutsche Post'     => array(
                'Warenpost', 'Warenpost International', 'Standardbrief', 'Kompaktbrief', 'Großbrief', 'Maxibrief',
                'Standardbrief Einschreiben', 'Kompaktbrief Einschreiben', 'Großbrief Einschreiben', 'Maxibrief Einschreiben',
                'Standardbrief International', 'Kompaktbrief International', 'Großbrief International', 'Maxibrief International',
                'Standardbrief Einschreiben International', 'Kompaktbrief Einschreiben International', 'Großbrief Einschreiben International',
                'Maxibrief Einschreiben International', 'Bücher-/Warensendung',
            ),
            'DPD' => array(
                'DPD Prio', 'DPD Classic', 'DPD Express',
            ),
            'DX Freight' => array(
                'DX Courier',
            ),
            'Estes' => array(),
            'FedEx' => array(),
            'Fedex Freight' => array(),
            'FedEx SmartPost' => array(),
            'First Mile' => array(),
            'GLS'   => array(
                'Business Parcel', 'Business Small Parcel', 'Euro Business Parcel', 'EuroBusinessSmallParcel', 'ExpressParcel',
                'EuroExpressParcel', 'GlobalExpressParcel', 'ExpeditedGlobalParcel', 'ExpeditedLocalParcel',
            ),
            'Hermes'        => array(
                'Eilservice', 'Standard'
            ),
            'Hermes UK'        => array(
                'Eilservice', 'Standard'
            ),
            'Hermes Logistik Gruppe'        => array(
                'Eilservice', 'Standard'
            ),
            'Hongkong Post' => array(),
            'Hunter Logistics' => array(),
            'India Post' => array(),
            'JCEX' => array( 'Jia-Packet' ),
            'Lasership' => array(),
            'Newgistics' => array(),
            'Old Dominion' => array(),
            'OnTrac' => array(),
            'OSM' => array(),
            'Pilot Freight' => array(),
            'R+L' => array(),
            'Roadrunner' => array(),
            'Royal Mail' => array(
                'Royal Mail 2nd Class', 'Royal Mail Special Delivery Guaranteed', 'Royal Mail Tracked 24', 'Royal Mail Tracked 48'
            ),
            'Saia' => array(),
            'Seur' => array( 'SEUR 24' ),
            'SF Express' => array(
                'Ordinary Parcel-Standard', 'Registered Parcel-Standard', 'Ordinary Parcel-Economy', 'Registered Parcel-Economy',
                'Ordinary Parcel-Special Line', 'Registered Parcel-Special Line', 'E-Commerce Express Standard',
                'E-Commerce Express Standard', 'E-Commerce Express CD'
            ),
            'SFC' => array(
                'JPEXP-Japan Special Line', 'STEXPFS-SFC Textile Line', 'STEXPTH-SFC Special Line', 'STEXPTHPH-SFC Special Line(General cargo)'
            ),
            'South Eastern Freight Lines' => array(),
            'StreamLite' => array(),
            'UPS' => array(
                'Standard', 'Express Saver'
            ),
            'UPS Freight' => array(),
            'UPS Mail Innovations' => array(),
            'Urban Express' => array(),
            'USPS' => array(),
            'WanB Express' => array(
                'WANB EXPRESS', 'WANB Smart Track', 'WANB Semi Track', 'WANB Post Smart', 'WANB Post Economy'
            ),
            'Watkins and Shepard' => array(),
            'XPO Freight' => array(),
            'Yanwen' => array( 'AIR ECONOMY MAIL', 'AIR REGISTERED MAIL', 'Direct Line Tracked Packet', 'Direct Line Express' ),
            'Yellow Freight' => array(),
            'Yun Express' => array(
                'YunExpress Global Direct line (standard )-Tracked', 'YunExpress Global Direct line with Battery-Tracked',
                'YunExpress Global Direct line non Battery-Tracked', 'YunExpress Global Direct line (standard )-Untracked',
                'YunExpress Global Direct line with Battery-Untracked', 'YunExpress Global Direct line non Battery-Untracked'
            ),
            'Self Delivery' => array( 'Self Delivery' ),
            'StarTrack-Consignment' => array(),
            'StarTrack-ArticleID' => array(),
            'Other' => array(),
		);

		return apply_filters( 'wpla_available_shipping_providers', $providers );
	}

    static public function findMatchingTrackingProvider( $provider_name ) {
	    $found_provider = false;
        $providers = self::getShippingProviders();

        foreach ( $providers as $name => $services ) {
            // backwards-compatibility check
            if ( !is_array( $services ) ) {
                $name = $services;
                $services = array();
            }

            // return lower case match
            if ( strtolower($name) == strtolower($provider_name) ) {
                $found_provider = $name;
                break;
            }

            // try replacing spaces with dashes
            $dashed = str_replace( ' ', '-', $name );
            if ( strtolower($dashed) == strtolower($provider_name) ) {
                $found_provider = $name;
                break;
            }

        }

        // allow 3rd-party code to run their own checks #50611
        $found_provider = apply_filters( 'wpla_find_matching_tracking_provider', $found_provider, $provider_name, $providers );

        // Strip invalid characters. According to the API
        // only letters, numbers, and dashes are allowed
        // #23738 #23514
        $found_provider = str_replace( '_', '-', $found_provider );
        //$found_provider = preg_replace( '/[^\da-z\-]/i', '', $found_provider );

        if ( $found_provider ) return $found_provider;

        return 'Other';
        //return $provider_name; // if no match is found, return original provider name
    } // findMatchingTrackingProvider()


	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @param WP_Screen $screen
	 * @param WC_Order $wc_order
	 * @access public
	 */
	function add_meta_boxes( $screen = null, $wc_order = null ) {
		global $post;

		if ( $screen && !in_array( $screen, ['shop_order', 'woocommerce_page_wc-orders'] ) ) return;

		//if ( ! isset( $_GET['post'] ) ) return;

		$amazon_order_id = false;
		$wc_order = ( $wc_order instanceof WP_Post ) ? wc_get_order( $wc_order ) : $wc_order;

		if ( $wc_order ) {
			$amazon_order_id = $wc_order->get_meta( '_wpla_amazon_order_id', true );
		}

		if ( $amazon_order_id ) {

			// show meta box for Amazon orders
			$title = __( 'Amazon', 'wp-lister-for-amazon' ) . ' <small style="color:#999"> #' . $amazon_order_id . '</small>';
			add_meta_box( 'woocommerce-amazon-details', $title, array( &$this, 'meta_box_for_amazon_orders' ), $screen, 'side', 'core');

		} elseif ( get_option( 'wpla_fba_enabled' ) ) {

			// show FBA meta box for Non-Amazon orders
			$title = __( 'Amazon', 'wp-lister-for-amazon' );
			add_meta_box( 'woocommerce-amazon-details', $title, array( &$this, 'meta_box_for_non_amazon_orders' ), $screen, 'side', 'core');

		}

	}


	/**
	 * Show the FBA meta box for Non-Amazon orders
	 *
	 * @access public
	 */
	function meta_box_for_non_amazon_orders( $post ) {
		if ( $post instanceof WC_Order ) {
			$order = $post;
		} else {
			$order = self::get_wc_order( $post->ID );
		}

		// check if order has already been fulfilled by Amazon
        $submission_status = $order->get_meta( '_wpla_fba_submission_status', true );
        if ( $submission_status == 'shipped')
        	return $this->show_fba_tracking_details( $order );

        if ( $submission_status == 'failed' ) {
	        echo '<p><b>' . __( 'There was a problem submitting this order to be fulfilled by Amazon!', 'wp-lister-for-amazon' ) . '</b></p>';
	        // $this->show_fba_tracking_details( $post ); // TODO: store and show last used _wpla_DeliverySLA for automatic submissions as well
	    }

	    // held submissions can be resubmitted
        if ( $submission_status == 'hold' ) {
	        echo '<p>' . __( 'The ordered items(s) have been held back on FBA until this order is completed. To ship the held items please visit Seller Central.', 'wp-lister-for-amazon' ) . '</p>';
        }

		// check if order is eligible to be fulfilled via FBA
		$checkresult = WPLA_FbaHelper::orderCanBeFulfilledViaFBA( $post );
		if ( ! is_array( $checkresult ) ) {
	        echo '<p>' . $checkresult . '</p>';
	        return;
		}

		// all right - this order can be fulfilled via FBA
        if ( $submission_status == 'failed' ) {
	        echo '<p>' . __( 'You can try to submit this order again.', 'wp-lister-for-amazon' ) . '</p>';
	    } elseif ( $submission_status == 'hold' ) {
	        echo '<p>' . __( 'You can submit this on-hold order again.', 'wp-lister-for-amazon' ) . '</p>';
	    } else {
    	    echo '<p>' . __( 'This order can be fulfilled by Amazon.', 'wp-lister-for-amazon' ) . '</p>';
	    }

        echo '<table style="width:100%">';
    	echo '<tr>';
    	echo '<th style="text-align:left;">'.'ASIN'.'</th>';
    	echo '<th style="text-align:left;">'.'Purchased'.'</th>';
    	echo '<th style="text-align:left;">'.'FBA Qty'.'</th>';
    	echo '</tr>';

		$items_available_on_fba = $checkresult;
        foreach ( $items_available_on_fba as $listing ) {
        	echo '<tr>';
        	echo '<td>'.$listing->asin.'</td>';
        	echo '<td>'.$listing->purchased_qty.'</td>';
        	echo '<td>'.$listing->fba_quantity.'</td>';
        	echo '</tr>';
        }
        echo '</table>';

        // DeliverySLA option
        $default_sla = get_option( 'wpla_fba_default_delivery_sla', 'Standard' );
		echo '<p class="form-field wpla_DeliverySLA_field"><label for="wpla_DeliverySLA">' . __( 'Shipping service', 'wp-lister-for-amazon' ) . '</label><br/><select id="wpla_DeliverySLA" name="wpla_DeliverySLA" class="chosen_select" style="width:100%;">';
		echo '<option value="Standard"  '.( $default_sla == 'Standard'  ? 'selected' : '').' > '  . __( 'Standard', 'wp-lister-for-amazon' ) . ' (3-5 business days)</option>';
		echo '<option value="Expedited" '.( $default_sla == 'Expedited' ? 'selected' : '').' > ' . __( 'Expedited', 'wp-lister-for-amazon' ) . ' (2 business days)</option>';
		echo '<option value="Priority"  '.( $default_sla == 'Priority'  ? 'selected' : '').' > '  . __( 'Priority', 'wp-lister-for-amazon' ) . ' (1 business day)</option>';
		echo '</select> ';

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpla_NotificationEmail',
			'label' 		=> __( 'Notification Email', 'wp-lister-for-amazon' ),
			'placeholder' 	=> '',
			'description' 	=> '',
			'value'			=> $order->get_meta( '_billing_email', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpla_DisplayableOrderComment',
			'label' 		=> __( 'Packing Slip Comment', 'wp-lister-for-amazon' ),
			'placeholder' 	=> 'Thank you for your order.',
			'description' 	=> '',
			'value'			=> get_option( 'wpla_fba_default_order_comment' )
		) );


		$submit_button_label = in_array( $submission_status, array('failed','hold') ) ? 'Submit again' : 'Submit to FBA';
        echo '<p>';
        echo '<div id="btn_submit_order_to_fba_spinner" style="float:right;display:none"><img src="'.WPLA_URL.'/img/ajax-loader.gif"/></div>';
        echo '<div class="spinner"></div>';
        echo '<a href="#" id="btn_submit_order_to_fba" class="button button-primary">'.$submit_button_label.'</a>';
        echo '<div id="amazon_result_info" class="updated" style="display:none"><p></p></div>';
        echo '</p>';

        $nonce = wp_create_nonce( 'wpla_ajax_nonce' );
        wc_enqueue_js("

            var wpla_submitOrderToFBA = function ( post_id ) {

				var wpla_DeliverySLA             = jQuery('#wpla_DeliverySLA').val();
				var wpla_NotificationEmail       = jQuery('#wpla_NotificationEmail').val();
				var wpla_DisplayableOrderComment = jQuery('#wpla_DisplayableOrderComment').val();

                // prepare request
                var params = {
					action: 'wpla_submit_order_to_fba',
					order_id: post_id,
					wpla_DeliverySLA: wpla_DeliverySLA,
					wpla_NotificationEmail: wpla_NotificationEmail,
					wpla_DisplayableOrderComment: wpla_DisplayableOrderComment,
                    _wpnonce: '{$nonce}'
                };
                var jqxhr = jQuery.getJSON( 
                    ajaxurl, 
                    params,
                    function( response ) { 

                        jQuery('#woocommerce-amazon-details .spinner').hide();
    
                        if ( response.success ) {
    
                            var logMsg = 'Order was submitted to Amazon.';
                            jQuery('#amazon_result_info p').html( logMsg );
                            jQuery('#amazon_result_info').addClass( 'updated' ).removeClass('error');
                            jQuery('#amazon_result_info').slideDown();
                            jQuery('#btn_submit_order_to_fba').hide('fast');
    
                        } else {
    
                            var logMsg = '<b>There was a problem submitting this order to Amazon</b><br><br>'+response.error;
                            jQuery('#amazon_result_info p').html( logMsg );
                            jQuery('#amazon_result_info').addClass( 'error' ).removeClass('updated');
                            jQuery('#amazon_result_info').slideDown();
                            
                            // Show error even if the wc admin plugin is installed
                            jQuery('#wp__notice-list')
                                .removeClass('woocommerce-layout__notice-list-hide')
                                .addClass('woocommerce-layout__notice-list-show');
    
                            jQuery('#btn_submit_order_to_fba').removeClass('disabled');
                        } 
                    }    
                )
                .fail( function(e,xhr,error) { 
                    jQuery('#amazon_result_info p').html( 'The server responded: ' + e.responseText + '<br>' );
                    jQuery('#amazon_result_info').addClass( 'error' ).removeClass('updated');
                    jQuery('#amazon_result_info').slideDown();
                    
                    // Show error even if the wc admin plugin is installed
                    jQuery('#wp__notice-list')
                        .removeClass('woocommerce-layout__notice-list-hide')
                        .addClass('woocommerce-layout__notice-list-show');

                    jQuery('#woocommerce-amazon-details .spinner').hide();
                    jQuery('#btn_submit_order_to_fba').removeClass('disabled');

                    console.log( 'error', xhr, error ); 
                    console.log( e.responseText ); 
                });

            }

            jQuery('#btn_submit_order_to_fba').click(function(){

                var post_id = jQuery('#post_ID').val();

                // jQuery('#btn_submit_order_to_fba_spinner').show();
                jQuery('#woocommerce-amazon-details .spinner').show();
                jQuery(this).addClass('disabled');
                wpla_submitOrderToFBA( post_id );

                return false;
            });


        ");

	} // meta_box_for_non_amazon_orders()

	function show_fba_tracking_details( $order ) {

        echo '<p>' . __( 'This order has been fulfilled by Amazon.', 'wp-lister-for-amazon' ) . '</p>';

        echo '<table style="width:100%">';

    	echo '<tr>';
    	echo '<th style="text-align:left;">'.'Tracking #'.'</th>';
    	echo '<td style="text-align:left;">'. $order->get_meta( '_wpla_fba_tracking_number', true ) .'</td>';
    	echo '</tr>';

    	echo '<tr>';
    	echo '<th style="text-align:left;">'.'Carrier'.'</th>';
    	echo '<td style="text-align:left;">'. $order->get_meta( '_wpla_fba_ship_carrier', true ) .'</td>';
    	echo '</tr>';

    	$date = $order->get_meta( '_wpla_fba_shipment_date', true );
    	$date = date( 'Y-m-d H:i', strtotime( $date ) );
    	echo '<tr>';
    	echo '<th style="text-align:left;">'.'Shipped'.'</th>';
    	echo '<td style="text-align:left;">'. $date .'</td>';
    	echo '</tr>';

    	$date = $order->get_meta( '_wpla_fba_estimated_arrival_date', true );
    	$date = date( 'Y-m-d', strtotime( $date ) );
    	echo '<tr>';
    	echo '<th style="text-align:left;">'.'Est. arrival'.'</th>';
    	echo '<td style="text-align:left;">'. $date .'</td>';
    	echo '</tr>';

    	echo '<tr>';
    	echo '<th style="text-align:left;">'.'Service level'.'</th>';
    	echo '<td style="text-align:left;">'. $order->get_meta( '_wpla_fba_ship_service_level', true ) .'</td>';
    	echo '</tr>';

        echo '</table>';

	} // show_fba_tracking_details()

	/**
	 * Show the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	function meta_box_for_amazon_orders( $post ) {
		$order_id = 0;
		if ( $post instanceof WC_Order ) {
			$wc_order = $post;
			$order_id = $wc_order->get_id();
		} else {
			$wc_order = self::get_wc_order( $post->ID );
			$order_id = $post->ID;
		}

        if ( !$wc_order ) return;

		$amazon_order_id    = $wc_order->get_meta( '_wpla_amazon_order_id', true );
		$selected_provider  = $wc_order->get_meta( '_wpla_tracking_provider', true );
		$shipping_providers = self::getShippingProviders();

		// get order details
		$om    = new WPLA_OrdersModel();
		$order = $om->getOrderByOrderID( $amazon_order_id );

        if ( $order ) {

	        // display amazon account
	        $account = WPLA_AmazonAccount::getAccount( $order->account_id );
	        if ( $account ) {
	            $market = new WPLA_AmazonMarket( $account->market_id );
	            $order_url = sprintf( 'https://sellercentral.%s/orders-v3/order/%s', $market->url, $amazon_order_id );
		        echo '<p>';
		        echo __( 'This order was placed on Amazon.', 'wp-lister-for-amazon' );
		        echo '('.$account->title.')';
		        echo '<br/> View in [<a href="admin.php?page=wpla-orders&s='.$amazon_order_id.'" target="_blank">WP-Lister</a>] or [<a href="'. $order_url .'" target="_blank">Amazon</a>]';
		        echo '</p>';
	        }

			// check if order has already been fulfilled by Amazon
	        $submission_status = $wc_order->get_meta( '_wpla_fba_submission_status', true );
	        if ( $submission_status == 'shipped') {
	        	return $this->show_fba_tracking_details( $wc_order );
	        }

			// check for FBA
        	$order_details = json_decode( $order->details );
	        if ( is_object( $order_details ) && ( $order_details->FulfillmentChannel == 'AFN' ) ) {
		        echo '<p>';
		        echo __( 'This order will be fulfilled by Amazon.', 'wp-lister-for-amazon' );
		        echo '</p>';
		        return;
	        }

        }

		echo '<p class="form-field wpla_tracking_provider_field"><label for="wpla_tracking_provider">' . __( 'Shipping service', 'wp-lister-for-amazon' ) . ':</label><br/><select id="wpla_tracking_provider" name="wpla_tracking_provider" class="chosen_select" style="width:100%;">';

		echo '<option value="">-- ' . __( 'Select shipping service', 'wp-lister-for-amazon' ) . ' --</option>';
		foreach ( $shipping_providers as $provider => $services ) {
            if ( !is_array( $services ) ) {
                $provider = $services;
                $services = array();
            }
			echo '<option value="' . $provider . '" ' . selected( $provider, $selected_provider, false ) . '>' . $provider . '</option>';
		}

		echo '</select> ';

		// Add filters to the tracking number and provider
        $tracking_provider      = apply_filters( 'wpla_set_tracking_service_for_order', $wc_order->get_meta( '_wpla_tracking_service_name', true ), $order_id );
        $tracking_number        = apply_filters( 'wpla_set_tracking_number_for_order', $wc_order->get_meta( '_wpla_tracking_number', true ), $order_id );
        $tracking_ship_method   = apply_filters( 'wpla_set_tracking_ship_method_for_order', $wc_order->get_meta( '_wpla_tracking_ship_method', true ), $order_id );
        $tracking_ship_from     = apply_filters( 'wpla_set_tracking_ship_from_for_order', $wc_order->get_meta( '_wpla_tracking_ship_from', true ), $order_id );

        // get the default ship-from address
        $default_ship_from = get_option( 'wpla_ship_from_default_address', '' );
        if ( empty( $tracking_ship_from ) && !empty( $default_ship_from ) ) {
            $tracking_ship_from = $default_ship_from;
        }

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpla_tracking_service_name',
			'label' 		=> __( 'Service provider', 'wp-lister-for-amazon' ),
			'placeholder' 	=> '',
			'description' 	=> '',
			'value'			=> $tracking_provider
		) );

		$ship_method_value = $tracking_ship_method ? '<option value="'. esc_attr( $tracking_ship_method ) .'" selected>'. $tracking_ship_method .'</option>' : '';
        echo '<p class="form-field wpla_tracking_ship_method_field"><label for="wpla_tracking_ship_method">' . __( 'Shipping method', 'wp-lister-for-amazon' ) . ':</label><br/><select id="wpla_tracking_ship_method" name="wpla_tracking_ship_method" style="width:100%;" data-placeholder="Enter the shipping method">'. $ship_method_value .'</select>';

        /*woocommerce_wp_text_input( array(
            'id' 			=> 'wpla_tracking_ship_method',
            'label' 		=> __( 'Shipping method', 'wp-lister-for-amazon' ),
            'placeholder' 	=> 'e.g. First Class',
            'description' 	=> '',
            'value'			=> $tracking_ship_method
        ) );*/

        woocommerce_wp_select( array(
            'id'            => 'wpla_tracking_ship_from',
            'label'         => __( 'Ship from', 'wp-lister-for-amazon' ) . ' <a style="float: right; text-decoration: none;" href="'. admin_url( 'admin.php?page=wpla-settings&tab=advanced#ShipFromAddressBox' ) .'" target="_blank"><span class="dashicons dashicons-admin-generic"></span></a>',
            'value'         => $tracking_ship_from,
            'options'       => wpla_get_ship_from_select_options(),
        ) );
        /*woocommerce_wp_text_input( array(
            'id' 			=> 'wpla_tracking_ship_from',
            'label' 		=> __( 'Ship From', 'wp-lister-for-amazon' ),
            'placeholder' 	=> 'e.g. Warehouse',
            'description' 	=> '',
            'value'			=> $tracking_ship_from
        ) );*/

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpla_tracking_number',
			'label' 		=> __( 'Tracking ID', 'wp-lister-for-amazon' ),
			'placeholder' 	=> '',
			'description' 	=> '',
			'value'			=> $tracking_number
		) );

		// get current local time
		$tz = WPLA_DateTimeHelper::getLocalTimeZone();
		$nw = new DateTime('now', new DateTimeZone( $tz ));

		// convert stored date/time from UTC to local time
		$wpla_date_shipped = $wc_order->get_meta( '_wpla_date_shipped', true );
		$wpla_time_shipped = $wc_order->get_meta( '_wpla_time_shipped', true );

		// check if date and time are both valid
		if ( DateTime::createFromFormat('Y-m-d H:i:s', $wpla_date_shipped.' '.$wpla_time_shipped) ) {

			// convert date/time from UTC to local timezone
			$tz = WPLA_DateTimeHelper::getLocalTimeZone();
			$dt = new DateTime( $wpla_date_shipped.' '.$wpla_time_shipped, new DateTimeZone( 'UTC' ) );
			$dt->setTimeZone( new DateTimeZone( $tz ) );
			$wpla_date_shipped = $dt->format('Y-m-d');
			$wpla_time_shipped = $dt->format('H:i:s');

		}

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpla_date_shipped',
			'label' 		=> __( 'Shipping date', 'wp-lister-for-amazon' ),
			'placeholder' 	=> 'Current date: ' . $nw->format('Y-m-d'),
			'description' 	=> '',
			'class'			=> 'date-picker-field',
			'value'			=> $wpla_date_shipped
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpla_time_shipped',
			'label' 		=> __( 'Shipping time', 'wp-lister-for-amazon' ),
			'placeholder' 	=> 'Current time: ' . $nw->format('H:i:s'), // . ' ' . $tz,
			'description' 	=> '<small>Timezone: '.$tz.'</small>',
			// 'desc_tip'		=>  true,
			'class'			=> 'time-picker-field',
			'value'			=> $wpla_time_shipped
		) );

        // echo '<p>';
        // echo '<small>Local timezone: '.$tz.'</small>';
        // echo '</p>';

		// woocommerce_wp_checkbox( array( 'id' => 'wpla_update_amazon_on_save', 'wrapper_class' => 'update_amazon', 'label' => __( 'Update on save?', 'wp-lister-for-amazon' ) ) );

		// show submission status if it exists
        if ( $submission_status = $wc_order->get_meta( '_wpla_submission_result', true ) ) {
	        echo '<p>';
	        if ( $submission_status == 'success' ) {
		        echo 'Submitted to Amazon: yes';
	        } else {
	        	$history = maybe_unserialize( $submission_status );
		        echo 'Submission Log:';
		        echo '<div style="color:darkred; font-size:0.8em;">';
	            if ( is_array( $history ) ) {
	                foreach ($history['errors'] as $result) {
	                    echo '<b>'.$result['error-type'].':</b> '.$result['error-message'].' ('.$result['error-code'].')<br>';
	                }
	                foreach ($history['warnings'] as $result) {
	                    echo '<b>'.$result['error-type'].':</b> '.$result['error-message'].' ('.$result['error-code'].')<br>';
	                }
	            }
		        echo '</div>';
	        }
	        echo '</p>';
        }

        echo '<p>';
        echo '<div id="btn_update_amazon_shipment_spinner" style="float:right;display:none"><img src="'.WPLA_URL.'/img/ajax-loader.gif"/></div>';
        echo '<div class="spinner"></div>';
        echo '<a href="#" id="btn_update_amazon_shipment" class="button button-primary">'.__( 'Mark as shipped on Amazon', 'wp-lister-for-amazon' ).'</a>';
        echo '<div id="amazon_result_info" class="updated" style="display:none"><p></p></div>';
        echo '</p>';

        $nonce = wp_create_nonce( 'wpla_ajax_nonce' );
        wc_enqueue_js("
            var ship_methods = ". json_encode( $shipping_providers ) .";
            
            jQuery('#wpla_tracking_ship_method').selectWoo({
                tags: true,
                allowClear: true
            });
            
            function update_shipping_method_select( provider ) {
                var select = jQuery('#wpla_tracking_ship_method') 
                
                select.selectWoo('destroy');
                select.find('option').remove();
               
                if ( provider && ship_methods[provider].length > 0 ) {
                    for (var i = 0; i < ship_methods[ provider ].length; i++ ) {
                        var text = ship_methods[provider][i];
                        var option = new Option(text, text, false, false);
                        select.append(option);
                    }
                }
                select.selectWoo({
                    tags: true,
                    allowClear: true
                });
                select.trigger('change');
            }
            
            var wpla_updateAmazonFeedback = function ( post_id ) {
                var tracking_provider 		= jQuery('#wpla_tracking_provider').val();
                var tracking_service_name 	= jQuery('#wpla_tracking_service_name').val();
                var tracking_number 		= jQuery('#wpla_tracking_number').val();
                var shipping_method         = jQuery('#wpla_tracking_ship_method').val();
                var ship_from               = jQuery('#wpla_tracking_ship_from').val();
                var date_shipped 			= jQuery('#wpla_date_shipped').val();
                var time_shipped 			= jQuery('#wpla_time_shipped').val();
                
                // load task list
                var params = {
                    action: 'wpla_update_amazon_shipment',
                    order_id: post_id,
                    wpla_tracking_provider: tracking_provider,
                    wpla_tracking_service_name: tracking_service_name,
                    wpla_tracking_number: tracking_number,
                    wpla_shipping_method: shipping_method,
                    wpla_ship_from: ship_from,
                    wpla_date_shipped: date_shipped,
                    wpla_time_shipped: time_shipped,
                    _wpnonce: '{$nonce}'
                };
                var jqxhr = jQuery.getJSON( 
                    ajaxurl, 
                    params,
                    function( response ) { 

                        // jQuery('#btn_update_amazon_shipment_spinner').hide();
                        jQuery('#woocommerce-amazon-details .spinner').hide();
    
                        if ( response.success ) {
    
                            var logMsg = 'Shipping status was updated and will be submitted to Amazon.';
                            jQuery('#amazon_result_info p').html( logMsg );
                            jQuery('#amazon_result_info').addClass( 'updated' ).removeClass('error');
                            jQuery('#amazon_result_info').slideDown();
                            jQuery('#btn_update_amazon_shipment').hide('fast');
    
                        } else {
    
                            var logMsg = '<b>There was a problem updating this order on Amazon</b><br><br>'+response.error;
                            jQuery('#amazon_result_info p').html( logMsg );
                            jQuery('#amazon_result_info').addClass( 'error' ).removeClass('updated');
                            jQuery('#amazon_result_info').slideDown();
                            
                            // Show error even if the wc admin plugin is installed
                            jQuery('#wp__notice-list')
                                .removeClass('woocommerce-layout__notice-list-hide')
                                .addClass('woocommerce-layout__notice-list-show');
    
                            jQuery('#btn_update_amazon_shipment').removeClass('disabled');
                        }
                    }
                 )
                .fail( function(e,xhr,error) { 
                    jQuery('#amazon_result_info p').html( 'The server responded: ' + e.responseText + '<br>' );
                    jQuery('#amazon_result_info').addClass( 'error' ).removeClass('updated');
                    jQuery('#amazon_result_info').slideDown();

                    // jQuery('#btn_update_amazon_shipment_spinner').hide();
                    jQuery('#woocommerce-amazon-details .spinner').hide();
                    jQuery('#btn_update_amazon_shipment').removeClass('disabled');
                    
                    // Show error even if the wc admin plugin is installed
                    jQuery('#wp__notice-list')
                        .removeClass('woocommerce-layout__notice-list-hide')
                        .addClass('woocommerce-layout__notice-list-show');

                    console.log( 'error', xhr, error ); 
                    console.log( e.responseText ); 
                });

            }

            jQuery('#btn_update_amazon_shipment').click(function(){

                var post_id = jQuery('#post_ID').val();

                // jQuery('#btn_update_amazon_shipment_spinner').show();
                jQuery('#woocommerce-amazon-details .spinner').show();
                jQuery(this).addClass('disabled');
                wpla_updateAmazonFeedback( post_id );

                return false;
            });

            jQuery('#wpla_tracking_provider').change(function(){

                var tracking_provider = jQuery('#wpla_tracking_provider').val();
                // alert(tracking_provider);

                if ( tracking_provider == 'Other' ) {
	                jQuery('.wpla_tracking_service_name_field').slideDown();
                } else {
	                jQuery('.wpla_tracking_service_name_field').slideUp();
                }
                
                update_shipping_method_select( tracking_provider );

                return false;
            });
			if ( 'Other' != jQuery('#wpla_tracking_provider').val() ) {
	            jQuery('.wpla_tracking_service_name_field').hide();
			}

            // fix jQuery datepicker today button
            jQuery('body').on('click', 'button.ui-datepicker-current', function() {
			    jQuery.datepicker._curInst.input.datepicker('setDate', new Date()).datepicker('hide').blur();
			});

        ");

	} // meta_box_for_amazon_orders()

	public function save_meta_box( $post_id ) {
	    $wc_order = self::get_wc_order( $post_id );
		// check if this order came in from amazon
		if ( ! $wc_order->get_meta( '_wpla_amazon_order_id', true ) ) return;

		// check if this order has already been submitted to Amazon
		if ( $wc_order->get_meta( '_wpla_date_shipped', true ) != '' ) return;

		$tracking_provider		= trim( wpla_clean( @$_POST['wpla_tracking_provider'] ) );
		$tracking_number 		= trim( wpla_clean( @$_POST['wpla_tracking_number'] ) );
		$shipping_method 		= trim( wpla_clean( @$_POST['wpla_tracking_ship_method'] ) );
		$ship_from      		= trim( wpla_clean( @$_POST['wpla_tracking_ship_from'] ) );
		$date_shipped			= trim( wpla_clean( @$_POST['wpla_date_shipped'] ) );
		$time_shipped			= trim( wpla_clean( @$_POST['wpla_time_shipped'] ) );
		$tracking_service_name	= trim( wpla_clean( @$_POST['wpla_tracking_service_name'] ) );

		if ( ! empty( $tracking_provider ) && ! empty( $tracking_number ) ) {

			// validate shipping time
			if ( $time_shipped && ! DateTime::createFromFormat('H:i:s', $time_shipped) && ! DateTime::createFromFormat('H:i', $time_shipped) ) {
				$time_shipped = '';
			}

			// validate shipping date
			if ( $date_shipped ) {

				// if valid, convert from local timezone to UTC
				if ( DateTime::createFromFormat('Y-m-d', $date_shipped) ) {

					// if shipping time is empty, set to current local time before converting to UTC
					if ( ! $time_shipped ) {
						$tz = WPLA_DateTimeHelper::getLocalTimeZone();
						$dt = new DateTime('now', new DateTimeZone( $tz ));
						$time_shipped = $dt->format('H:i:s'); // current local time
					}

					// convert date/time from local timezone to UTC
					$tz = WPLA_DateTimeHelper::getLocalTimeZone();
					$dt = new DateTime( $date_shipped.' '.$time_shipped, new DateTimeZone( $tz ) );
					$dt->setTimeZone( new DateTimeZone('UTC') );
					$date_shipped = $dt->format('Y-m-d'); // current date in UTC
					$time_shipped = $dt->format('H:i:s'); // current time in UTC

				} else {
					// if invalid, set date to today
					$dt = new DateTime( 'now', new DateTimeZone('UTC') );
					$date_shipped = $dt->format('Y-m-d'); // current date in UTC
					$time_shipped = $dt->format('H:i:s'); // current time in UTC
				}

			}

			// if date is missing, but tracking number is set, set date to today
			if ( ! $date_shipped && $tracking_number ) {
				$dt = new DateTime( 'now', new DateTimeZone('UTC') );
				$date_shipped = $dt->format('Y-m-d'); // current date in UTC
				$time_shipped = $dt->format('H:i:s'); // current time in UTC
			}

			self::process_ship_from_address( $post_id, $ship_from );

			$wc_order->update_meta_data( '_wpla_tracking_provider', 		$tracking_provider );
			$wc_order->update_meta_data( '_wpla_tracking_number', 		    $tracking_number );
			$wc_order->update_meta_data( '_wpla_tracking_ship_method',	    $shipping_method );
			//update_post_meta( $post_id, '_wpla_tracking_ship_from',	    $ship_from );
			$wc_order->update_meta_data( '_wpla_date_shipped', 			$date_shipped );
			$wc_order->update_meta_data( '_wpla_time_shipped', 			$time_shipped );
			$wc_order->update_meta_data( '_wpla_tracking_service_name', 	$tracking_service_name );
			$wc_order->save();

            self::$wc_order_cache[ $post_id ] = $wc_order;

			$feed = new WPLA_AmazonFeed();
			$feed->updateShipmentFeed( $post_id );

		} // if tracking data set

	} // save_meta_box()


	// handle order status changed to "completed" - and complete amazon order
    public function handle_woocommerce_order_status_update_completed( $post_id ) {
	    WPLA()->logger->info('handle_woocommerce_order_status_update_completed for #'. $post_id);

	    $wc_order = self::get_wc_order( $post_id );

    	// check if auto complete option is enabled
    	if ( get_option( 'wpla_auto_complete_sales' ) != 1 ) {
    	    WPLA()->logger->info( 'Skipping: auto_complete_sales disabled' );
    	    return;
        }

    	// check if default status for new created orders is completed - skip further processing if it is
		if ( get_option( 'wpla_new_order_status', 'processing' ) == 'completed' ) {
            WPLA()->logger->info( 'Skipping: new_order_status already set to "completed"' );
		    return;
        }

        // check if this order came in from amazon
        if ( ! $wc_order->get_meta( '_wpla_amazon_order_id', true ) ) {
            WPLA()->logger->info( 'Skipping: _wpla_amazon_order_id not found' );
            return;
        }

        // check if this order has already been submitted to Amazon
        if ( $wc_order->get_meta( '_wpla_date_shipped', true ) != '' ) {
            WPLA()->logger->info( 'Skipping: _wpla_date_shipped already set' );
            return;
        }

	    // Skip completing orders again during order sync with HPOS
	    if ( did_action( 'wc-admin_import_orders' ) ) {
		    return;
	    }


	    if ( apply_filters( 'wpla_delayed_amazon_order_completion', false ) ) {
			WPLA()->logger->info( '['. $post_id .'] Scheduled complete_sale to run in the background');
			as_enqueue_async_action( 'wpla_complete_sale_on_amazon', [$post_id], 'WPLA' );
		} else {
			self::call_complete_order( $post_id );
		}

    } // handle_woocommerce_order_status_update_completed()

    public static function call_complete_order( $post_id ) {
	    $wc_order = self::get_wc_order( $post_id );

        // get order details from wp_amazon_orders
        $amazon_order_id = $wc_order->get_meta( '_wpla_amazon_order_id', true );
        $om              = new WPLA_OrdersModel();
        $order           = $om->getOrderByOrderID( $amazon_order_id );

        // check if this order is already marked as Shipped on Amazon - skip if it is
        $order_status = $order ? $order->status : false;
        if ( $order_status == 'Shipped' ) {
            WPLA()->logger->info('auto complete sales: skipped already shipped order from Shipment Feed - order id: '.$post_id);
            return; // prevent resetting the shipment date when importing old shipped orders (like after restoring a backup)
        }

        // check if this is an FBA order - skip if it is
        $order_details = $order ? json_decode( $order->details ) : false;
        if ( is_object( $order_details ) && ( $order_details->FulfillmentChannel == 'AFN' ) ) {
            WPLA()->logger->info('auto complete sales: skipped FBA order from Shipment Feed - order id: '.$post_id);
            return; // FBA orders don't need to be completed
        }

        self::set_default_shipment_data( $post_id, $order );

        self::process_third_party_tracking_plugins( $post_id, $order );

        self::process_ship_from_address( $post_id );

        // update shipment feed
        $feed = new WPLA_AmazonFeed();
        $feed->updateShipmentFeed( $post_id );
    }

    /**
     * update shipping date and tracking details on amazon (ajax)
     */
    function update_amazon_shipment_ajax() {

        // check nonce and permissions
        check_admin_referer( 'wpla_ajax_nonce' );
		if ( ! current_user_can('manage_amazon_listings') ) return;

		// get field values
        $post_id 					= wpla_clean($_REQUEST['order_id']);
        $wc_order                   = wc_get_order( $post_id );
		$wpla_tracking_provider		= trim( wpla_clean( $_REQUEST['wpla_tracking_provider'] ) );
		$wpla_tracking_number 		= trim( wpla_clean( $_REQUEST['wpla_tracking_number'] ) );
		$wpla_shipping_method       = trim( wpla_clean( $_REQUEST['wpla_shipping_method'] ) );
		$wpla_ship_from             = trim( wpla_clean( $_REQUEST['wpla_ship_from'] ) );
		$wpla_date_shipped			= trim( wpla_clean( $_REQUEST['wpla_date_shipped'] ) );
		$wpla_time_shipped			= trim( wpla_clean( $_REQUEST['wpla_time_shipped'] ) );
		$wpla_tracking_service_name	= trim( wpla_clean( $_REQUEST['wpla_tracking_service_name'] ) );
	    // WPLA()->logger->info( 'update_amazon_shipment_ajax request data: ' . print_r( wpla_clean($_REQUEST), true ) );

		// validate shipping time
		if ( $wpla_time_shipped && ! DateTime::createFromFormat('H:i:s', $wpla_time_shipped) && ! DateTime::createFromFormat('H:i', $wpla_time_shipped) ) {
			$wpla_time_shipped = '';
		}

		// validate shipping date
		if ( $wpla_date_shipped ) {

			// if valid, convert from local timezone to UTC
			if ( DateTime::createFromFormat('Y-m-d', $wpla_date_shipped) ) {

				// if shipping time is empty, set to current local time before converting to UTC
				if ( ! $wpla_time_shipped ) {
					$tz = WPLA_DateTimeHelper::getLocalTimeZone();
					$dt = new DateTime('now', new DateTimeZone( $tz ));
					$wpla_time_shipped = $dt->format('H:i:s'); // current local time
				}

				// convert date/time from local timezone to UTC
				$tz = WPLA_DateTimeHelper::getLocalTimeZone();
				$dt = new DateTime( $wpla_date_shipped.' '.$wpla_time_shipped, new DateTimeZone( $tz ) );
				$dt->setTimeZone( new DateTimeZone('UTC') );
				$wpla_date_shipped = $dt->format('Y-m-d'); // current date in UTC
				$wpla_time_shipped = $dt->format('H:i:s'); // current time in UTC

			} else {
				// if invalid, set date to today
				$dt = new DateTime( 'now', new DateTimeZone('UTC') );
				$wpla_date_shipped = $dt->format('Y-m-d'); // current date in UTC
				$wpla_time_shipped = $dt->format('H:i:s'); // current time in UTC
			}

		}

		// if date is missing, but tracking number is set, set date to today
		if ( ! $wpla_date_shipped && $wpla_tracking_number ) {
			$dt = new DateTime( 'now', new DateTimeZone('UTC') );
			$wpla_date_shipped = $dt->format('Y-m-d'); // current date in UTC
			$wpla_time_shipped = $dt->format('H:i:s'); // current time in UTC
		}

        // get the full ship-from address
        self::process_ship_from_address( $post_id, $wpla_ship_from );

		// update order data
		$wc_order->update_meta_data( '_wpla_tracking_provider', 		$wpla_tracking_provider );
		$wc_order->update_meta_data( '_wpla_tracking_number', 		$wpla_tracking_number );
		$wc_order->update_meta_data( '_wpla_tracking_ship_method', 	$wpla_shipping_method );
		$wc_order->update_meta_data( '_wpla_tracking_ship_from', 	    $wpla_ship_from );
		$wc_order->update_meta_data( '_wpla_date_shipped', 			$wpla_date_shipped );
		$wc_order->update_meta_data( '_wpla_time_shipped', 			$wpla_time_shipped );
		$wc_order->update_meta_data( '_wpla_tracking_service_name', 	$wpla_tracking_service_name );
		$wc_order->save();


		$response = new stdClass();

		if ( ! $wpla_date_shipped ) {
			$response->success = false;
			$response->error = 'You need to select a shipping date.';
		} else {
			$feed = new WPLA_AmazonFeed();
			$feed->updateShipmentFeed( $post_id );
			$response->success = true;
		}

        $this->returnJSON( $response );
        exit();

    } // update_amazon_shipment_ajax()


    /**
     * submit order to be fulfilled via FBA (ajax)
     */
    function submit_order_to_fba() {

        // check nonce and permissions
        check_admin_referer( 'wpla_ajax_nonce' );
		if ( ! current_user_can('manage_amazon_listings') ) return;

        // only run if FBA is enabled #15403
        if ( ! get_option( 'wpla_fba_enabled' ) ) {
            WPLA()->logger->info( 'submit_order_to_fba() skipped because FBA is disabled' );
            $response = new stdClass();
            $response->success = false;
            $response->error = 'FBA is disabled';

            $this->returnJSON( $response );
            exit();
        }

		// get field values
        $post_id = wpla_clean($_REQUEST['order_id']);
        $wc_order = wc_get_order( $post_id );

		// update order data
		$wc_order->update_meta_data( '_wpla_DeliverySLA', 			 trim( wpla_clean( $_REQUEST['wpla_DeliverySLA'] ) ) );
		$wc_order->update_meta_data( '_wpla_NotificationEmail', 		 trim( wpla_clean( $_REQUEST['wpla_NotificationEmail'] ) ) );
		$wc_order->update_meta_data( '_wpla_DisplayableOrderComment', trim( wpla_clean( $_REQUEST['wpla_DisplayableOrderComment'] ) ) );
		$wc_order->save();
		// update_post_meta( $post_id, '_wpla_fba_submission_status',   'submitted' );

		// create FBA feed
		$response = WPLA_FbaHelper::submitOrderToFBA( $post_id );

		// if ( $missing ) {
		// 	$response = new stdClass();
		// 	$response->success = false;
		// 	$response->error = 'You need to select a shipping date.';
		// }

        $this->returnJSON( $response );
        exit();

    } // submit_order_to_fba()

    public function returnJSON( $data ) {
        header('content-type: application/json; charset=utf-8');
        echo json_encode( $data );
    }

    /**
     * Compatibility function for DHL for WooCommerce plugin. Stores tracking data from DHL.
     * @param int $order_id
     * @param array $tracking_details
     */
    static function save_dhl_tracking_details( $order_id, $tracking_details ) {
        $wc_order = wc_get_order( $order_id );
        $wc_order->update_meta_data( '_wpla_tracking_provider',      'DHL' );
        //update_post_meta( $order_id, '_wpla_tracking_service_name',  $tracking_details['carrier'] );
        $wc_order->update_meta_data( '_wpla_tracking_number',        $tracking_details['tracking_number'] );
        $wc_order->save();

        WPLA()->logger->info('Saved DHL tracking data for #'. $order_id);
    }

    static function save_ups_tracking_details( $order_id ) {
        if ( isset( $_POST['ups_shipment_ids'] ) && is_array( $_POST['ups_shipment_ids'] ) ) {
            $shipment_ids = array_pop($_POST['ups_shipment_ids']);

            $wc_order = wc_get_order( $order_id );

            $wc_order->update_meta_data( '_wpla_tracking_provider',      'UPS' );
            $wc_order->update_meta_data( '_wpla_tracking_number',        $shipment_ids );
            $wc_order->save();

            WPLA()->logger->info('Saved UPS tracking data for #'. $order_id);
        }
    }

    public static function set_default_shipment_data( $post_id, $order = null ) {
        $wc_order = self::get_wc_order( $post_id );
        // set shipping date and time to now
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $wc_order->update_meta_data( '_wpla_date_shipped', 			$dt->format('Y-m-d') );
        $wc_order->update_meta_data( '_wpla_time_shipped', 			$dt->format('H:i:s') ); // UTC timezone

        // only use the default shipping provider if it hasn't been previously set yet
        if ( $wc_order->get_meta( '_wpla_tracking_provider', true ) == '' ) {
            $wc_order->update_meta_data( '_wpla_tracking_provider', 		get_option( 'wpla_default_shipping_provider', '' ) );
            $wc_order->update_meta_data( '_wpla_tracking_service_name', 	get_option( 'wpla_default_shipping_service_name', '' ) );
        }

        if ( $wc_order->get_meta( '_wpla_tracking_ship_method', true ) == '' ) {
            $wc_order->update_meta_data( '_wpla_tracking_ship_method', get_option( 'wpla_default_shipping_method', '' ) );
        }

        $wc_order->save();
        self::$wc_order_cache[ $post_id ] = $wc_order;

    }

    public static function process_third_party_tracking_plugins( $post_id, $order = null ) {
		WPLA()->logger->info( 'process_third_party_tracking_plugins #'. $post_id );

        $wc_order = self::get_wc_order( $post_id );

        // Check for tracking data from YITH WC Order Tracking #49624
        $yith_carrier = $wc_order->get_meta( 'ywot_carrier_id', true );
        $yith_tracking_number = $wc_order->get_meta( 'ywot_tracking_code', true );
        if ( $yith_carrier ) {
            // YITH uses DHL_DE instead of just DHL
            if ( $yith_carrier == 'DHL_DE' ) {
                $yith_carrier = 'DHL';
            }

	        WPLA()->logger->info( 'Found tracking data from YITH WC Order Tracking' );
	        WPLA()->logger->debug( 'Carrier: '. $yith_carrier );
	        WPLA()->logger->debug( 'Tracking Number: '. $yith_tracking_number );

            $provider = WPLA_Order_MetaBox::findMatchingTrackingProvider( $yith_carrier );

            if ( $provider == 'Other' ) {
                $wc_order->update_meta_data( '_wpla_tracking_provider',      'Other' );
                $wc_order->update_meta_data( '_wpla_tracking_service_name',  $yith_carrier );
                $wc_order->update_meta_data( '_wpla_tracking_number',        $yith_tracking_number );
            } else {
                $wc_order->update_meta_data( '_wpla_tracking_provider',      $provider );
                $wc_order->update_meta_data( '_wpla_tracking_service_name',  '' );
                $wc_order->update_meta_data( '_wpla_tracking_number',        $yith_tracking_number );
            }

            $yith_shipping_date = $wc_order->get_meta( 'ywot_pick_up_date', true );

            if ( $yith_shipping_date ) {
                $wc_order->update_meta_data( '_wpla_date_shipped', $yith_shipping_date );
            }
        }

        // check if there are tracking details stored by other plugins - like Shipstation or Shipment Tracking
        $wpl_tracking_provider = trim( $wc_order->get_meta( '_tracking_provider', true ) );
        $wpl_tracking_number   = trim( $wc_order->get_meta( '_tracking_number', true ) );
        if ( $wpl_tracking_number && $wpl_tracking_provider ) {
            $provider = WPLA_Order_MetaBox::findMatchingTrackingProvider( $wpl_tracking_provider );

	        WPLA()->logger->info( 'Found tracking data from _tracking_provider meta' );
	        WPLA()->logger->debug( 'Carrier: '. $wpl_tracking_provider );
	        WPLA()->logger->debug( 'Tracking Number: '. $wpl_tracking_number );

            $wc_order->update_meta_data( '_wpla_tracking_provider', 		$provider );
            $wc_order->update_meta_data( '_wpla_tracking_number', 		    $wpl_tracking_number );

            if ( $provider == 'Other' ) {
                $wc_order->update_meta_data( '_wpla_tracking_provider', 		'Other' );
                $wc_order->update_meta_data( '_wpla_tracking_service_name', 	$wpl_tracking_provider );
            } else {
                $wc_order->update_meta_data( '_wpla_tracking_service_name', 	'' );
            }
        }

        // check for tracking details stored by WooForce Shipment Tracking plugin
        $wf_wc_shipment_source = maybe_unserialize( $wc_order->get_meta( 'wf_wc_shipment_source', true ) );
        if ( is_array( $wf_wc_shipment_source ) && empty( $wpl_tracking_number ) ) {
	        WPLA()->logger->info( 'Found tracking data from WooForce Shipment Tracking' );
	        WPLA()->logger->debug( 'Carrier: '. $wf_wc_shipment_source['shipping_service'] );
	        WPLA()->logger->debug( 'Tracking Number: '. $wf_wc_shipment_source['shipment_id_cs'] );

            $wc_order->update_meta_data( '_wpla_tracking_provider', 		'Other' );
            $wc_order->update_meta_data( '_wpla_tracking_service_name', 	$wf_wc_shipment_source['shipping_service'] );
            $wc_order->update_meta_data( '_wpla_tracking_number', 		$wf_wc_shipment_source['shipment_id_cs'] );
        }

        // add support for WC Shipment Tracking v1.6.6 which stores tracking data using a different meta key
        $wc_tracking_data = $wc_order->get_meta( '_wc_shipment_tracking_items', true );
        if ( $wc_tracking_data ) {
	        WPLA()->logger->info( 'Found tracking data from WC Shipment Tracking' );
	        WPLA()->logger->debug( print_r( $wc_tracking_data,1) );

            $wc_tracking_data = current( $wc_tracking_data );

            // try to get the formatted tracking provider #20091
            $tracking_provider = $wc_tracking_data['tracking_provider'];
            if ( class_exists( 'WC_Shipment_Tracking_Actions' ) ) {
                $shipment_tracking = WC_Shipment_Tracking_Actions::get_instance();
                $shipment_tracking = apply_filters('wpla_shipment_tracking_instance', $shipment_tracking); #57187

                // Advanced Shipment Tracking plugin also uses the class WC_Shipment_Tracking_Action which causes
                // an undefined function call error #57187
                if ( method_exists( $shipment_tracking, 'get_formatted_tracking_item' ) ) {
                    $formatted_tracking_item = $shipment_tracking->get_formatted_tracking_item( $post_id, $wc_tracking_data );

                    if ( $formatted_tracking_item['formatted_tracking_provider'] ) {
                        $tracking_provider = $formatted_tracking_item['formatted_tracking_provider'];
                    }
                }
            }

            $provider = WPLA_Order_MetaBox::findMatchingTrackingProvider( $tracking_provider );

	        WPLA()->logger->debug( 'Carrier: '. $provider .' ('. $tracking_provider .')' );
	        WPLA()->logger->debug( 'Tracking Number: '. $wc_tracking_data['tracking_number'] );

            if ( $provider == 'Other' || $provider == 'Hermes' ) {
                $wc_order->update_meta_data( '_wpla_tracking_provider',      'Other' );
                $wc_order->update_meta_data( '_wpla_tracking_service_name',  $tracking_provider );
                $wc_order->update_meta_data( '_wpla_tracking_number',        $wc_tracking_data['tracking_number'] );
            } else {
                $wc_order->update_meta_data( '_wpla_tracking_provider',      $provider );
                $wc_order->update_meta_data( '_wpla_tracking_service_name',  '' );
                $wc_order->update_meta_data( '_wpla_tracking_number',        $wc_tracking_data['tracking_number'] );
            }

        }

        // If there's still no tracking data, check for any UPS tracking number in the order meta
        if ( !$wc_order->get_meta( '_wpla_tracking_provider', true ) && !$wc_order->get_meta( '_wpla_tracking_number', true ) ) {
            $ups = $wc_order->get_meta( 'ups_shipment_ids', true );

            if ( isset( $ups[0] ) ) {
	            WPLA()->logger->info( 'Found tracking data from UPS Tracking' );
	            WPLA()->logger->debug( 'Carrier: UPS' );
	            WPLA()->logger->debug( 'Tracking Number: '. $ups[0] );

                $wc_order->update_meta_data( '_wpla_tracking_provider',      'UPS' );
                $wc_order->update_meta_data( '_wpla_tracking_number',        $ups[0] );
            }
        }

        // if there's no tracking number, try pulling it from the tracking_code meta #50434
        if ( ! $wc_order->get_meta( '_wpla_tracking_number', true ) ) {
            $tracking_number = $wc_order->get_meta( 'tracking_code', true );

            if ( $tracking_number ) {
                $wc_order->update_meta_data( '_wpla_tracking_number', $tracking_number );
            }
        }

        $wc_order->save();
        self::$wc_order_cache[ $post_id ] = $wc_order;
        do_action( 'wpla_processed_third_party_tracking', $post_id );
	    WPLA()->logger->info( 'DONE process_third_party_tracking_plugins' );
    }

    public static function process_ship_from_address( $post_id, $ship_from = '' ) {
        $wc_order = self::get_wc_order( $post_id );

        WPLA()->logger->info( 'process_ship_from_address ('. $ship_from .') #'. $post_id );
        // get the full ship-from address
        WPLA()->logger->info( 'Loading ship-from address for #'. $post_id );
        WPLA()->logger->info( '_wpla_tracking_ship_from_name: '. $wc_order->get_meta( '_wpla_tracking_ship_from_name', true ) );

        if ( !empty( $wc_order->get_meta( '_wpla_tracking_ship_from_name', true ) ) ) {
            // ship-from already processed
            WPLA()->logger->info( 'ship-from already in place. Skipping');
        }

        if ( $ship_from ) {
            $address = wpla_get_ship_from_address( $ship_from );
        } else {
            $ship_from = get_option( 'wpla_ship_from_default_address' ); // load the default ship-from address
            WPLA()->logger->info( 'default ship-from address: '. $ship_from );
            $address = wpla_get_ship_from_address( $ship_from );
        }

        WPLA()->logger->info( 'loaded address: '. print_r( $address, 1 ) );

        if ( $address !== false ) {
            $wc_order->update_meta_data( '_wpla_tracking_ship_from_name', $address['name'] );
            $wc_order->update_meta_data( '_wpla_tracking_ship_from_line_1', $address['line_1'] );
            $wc_order->update_meta_data( '_wpla_tracking_ship_from_line_2', $address['line_2'] );
            $wc_order->update_meta_data( '_wpla_tracking_ship_from_city', $address['city'] );
            $wc_order->update_meta_data( '_wpla_tracking_ship_from_state', $address['state'] );
            $wc_order->update_meta_data( '_wpla_tracking_ship_from_postcode', $address['postal'] );
            $wc_order->update_meta_data( '_wpla_tracking_ship_from_country', $address['country'] );
            $wc_order->save();
            self::$wc_order_cache[ $post_id ] = $wc_order;
        }

    }


} // class WPLA_Order_MetaBox
// $WPLA_Order_MetaBox = new WPLA_Order_MetaBox();

## END PRO ##

