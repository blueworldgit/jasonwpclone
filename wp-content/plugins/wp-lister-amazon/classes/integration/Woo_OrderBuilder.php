<?php

require_once WPLA_PATH .'/includes/amazon/vendor/jlevers/selling-partner-api/lib/Model/OrdersV0/Money.php';

class WPLA_OrderBuilder {

	var $vat_enabled    = false;
	var $vat_total      = 0;
    var $vat_rates      = array();
    var $shipping_taxes = array();

	//
	// update woo order from ebay order
	//
	public function updateOrderFromAmazonOrder( $id, $post_id = false ) {
		// WPLA()->logger->info( 'updateOrderFromAmazonOrder #'.$id );

		// get order details
		$ordersModel    = new WPLA_OrdersModel();
		$amazon_order   = $ordersModel->getItem( $id );
		$details        = $amazon_order['details'];

		if ( ! $post_id ) {
		    $post_id = $amazon_order['post_id'];
        }

		// get order
		$order = wc_get_order( $post_id );

		if ( ! $order ) {
		    return false;
        }

		if ( !$this->orderCanBeUpdated( $order ) ) {
		    return $post_id;
        }

        // prevent WooCommerce from sending out notification emails when updating order status
        $this->disableEmailNotifications();

		$new_order_status = $this->mapAmazonOrderStatus( $amazon_order['status'] );

		// update order status
		if ( $order->get_status() != $new_order_status ) {

			$history_message = "Order #$post_id status was updated from {$order->get_status()} to $new_order_status";
			$history_details = array( 'post_id' => $post_id );
			WPLA_OrdersImporter::addHistory( $amazon_order['order_id'], 'update_order', $history_message, $history_details );

			$order->set_status( $new_order_status );

			// update order creation date
			$timestamp     = strtotime($amazon_order['date_created'].' UTC');
			$post_date     = WPLA_DateTimeHelper::convertTimestampToLocalTime( $timestamp );
			$post_date_gmt = date_i18n( 'Y-m-d H:i:s', $timestamp, true );

			$order->set_date_created( $post_date );

			// Set the date paid value #35371
            $payment_completed_status = apply_filters( 'woocommerce_payment_complete_order_status', $order->needs_processing() ? 'processing' : 'completed', $order->get_id(), $order );
            if ( $new_order_status == $payment_completed_status ) {
                // If payment complete status is reached, set paid now.
                $order->set_date_paid( $post_date );
            } elseif ( 'processing' === $payment_completed_status && $new_order_status == 'completed' ) {
                // If payment complete status was processing, but we've passed that and still have no date, set it now.
                $order->set_date_paid( $post_date );
            }

            $order->save();
		}

		return $post_id;
	} // updateOrderFromAmazonOrder()

    /**
     * Store Amazon order metadata into the WC order
     * @param array $amazon_order
     * @param WC_Order $wc_order
     * @return WC_Order
     */
    private function storeAmazonOrderMetaData( $amazon_order, $wc_order ) {
	    return $wc_order;
    }

	//
	// create woo order from amazon order
	//
	public function createWooOrderFromAmazonOrder( $id ) {
		// get order details
		$ordersModel    = new WPLA_OrdersModel();
		$amazon_order   = $ordersModel->getItem( $id );
		$details        = $amazon_order['details'];

        if ( !$amazon_order['items'] || empty( $amazon_order['items'] ) ) {
            WPLA()->logger->error( 'Could not create order for #'. $amazon_order['order_id'] .' because no order items were found.' );

            // add history record
            $history_message = "Skipped creating order for #{$amazon_order['order_id']}  because no order items were found.";
            $history_details = array();
            WPLA_OrdersImporter::addHistory( $amazon_order['order_id'], 'create_order', $history_message, $history_details );

            return false;
        }

        do_action( 'wpla_order_builder_before_create_order', $amazon_order );

		// Fix provided by Aelia Currency Switcher plugin #69819
		if ( $details->OrderTotal ) {
			$new_order_currency = $details->OrderTotal->CurrencyCode;
			// Replace the active currency while creating the order
			add_filter('woocommerce_currency', $currency_override_filter = function() use ($new_order_currency) {
				return $new_order_currency;
			}, 9999
			);
		}

		$order       = wc_create_order();
        $post_id     = $order->get_id();

		// Remove the filter that overrides the active currency
		remove_filter('woocommerce_currency', $currency_override_filter);

		$timestamp     = strtotime($amazon_order['date_created'].' UTC');
		$post_date     = WPLA_DateTimeHelper::convertTimestampToLocalTime( $timestamp );
		$post_date_gmt = date_i18n( 'Y-m-d H:i:s', $timestamp, true );

		// create order comment
		$order_comment = sprintf( __( 'Amazon Order ID: %s', 'wp-lister-for-amazon' ), $amazon_order['order_id'] );

		// Create shop_order post object
        $post_data = apply_filters( 'wpla_order_post_data', array(
            'post_excerpt'   => stripslashes( $order_comment ),
            'post_date'      => $post_date, //The time post was made.
            'post_date_gmt'  => $post_date_gmt, //The time post was made, in GMT.
        ), $id, $amazon_order );

        $id_storage = get_option( 'wpla_amazon_order_id_storage', 'notes' );
        if ( $id_storage == 'notes' ) {
            $order->add_order_note( $post_data['post_excerpt'] );
        } elseif ( $id_storage == 'excerpt' ) {
            $order->set_customer_note( $post_data['post_excerpt'] );
        } else {
            $order->add_order_note( $post_data['post_excerpt'] );
            $order->set_customer_note( $post_data['post_excerpt'] );
        }

        //$order->set_customer_note( $post_data['post_excerpt'] ); //Store this in a private note instead #38623
        $order->set_date_created( $post_data['post_date'] );

		// Update wp_order_id of order record
		$ordersModel->updateWpOrderID( $id, $post_id );

		/* the following code is inspired by woocommerce_process_shop_order_meta() in writepanel-order_data.php */

		// Set the currency first before anything else! #16900
        $order->set_currency( $details->OrderTotal->CurrencyCode );

		// Add key
        $order->set_order_key( 'wc_' . uniqid('order_') );
        $order->set_created_via( 'amazon' );

        $order = $this->storeAmazonOrderMetaData( $amazon_order, $order );

		// Update post data
        $order->update_meta_data( '_wpla_amazon_order_id', $amazon_order['order_id'] );
        $order->update_meta_data( '_wpla_amazon_fulfillment_channel', $details->FulfillmentChannel );

		// Store order properties
		$order->update_meta_data( '_wpla_amazon_prime_order', $details->IsPrime ?? 0 );
		$order->update_meta_data( '_wpla_amazon_business_order', $details->IsBusinessOrder ?? 0 );

		// Order Attribution
		$order = $this->addOrderAttributionTracking( $order );

        if ( isset( WPLA()->accounts[ $amazon_order['account_id'] ] ) ) {
            $title = WPLA()->accounts[ $amazon_order['account_id'] ]->title;
            $order->update_meta_data( '_wpla_amazon_account', $title );
        }

        $address = WPLA_OrdersModel::getShippingAddress( $amazon_order );
        $buyer_name = isset( $details->BuyerInfo->BuyerName ) ? $details->BuyerInfo->BuyerName : $amazon_order['buyer_name'];
        $buyer_email = isset( $details->BuyerInfo->BuyerEmail ) ? $details->BuyerInfo->BuyerEmail : $amazon_order['buyer_email'];

        if ( $address ) {
            $billing_details = $address;
            $shipping_details = $address;
        } else {
            $billing_details = new stdClass();
            $shipping_details = new stdClass();
        }

		// optional billing address / RegistrationAddress
		// if ( isset( $details->Buyer->RegistrationAddress ) ) {
		// 	$billing_details = $details->Buyer->RegistrationAddress;
		// }

		// if AddressLine1 is missing or empty, use AddressLine2 instead
		if ( empty( $billing_details->AddressLine1 ) ) {
			$billing_details->AddressLine1 = @$billing_details->AddressLine2;
			$billing_details->AddressLine2 = '';
		}
		if ( empty( $shipping_details->AddressLine1 ) ) {
			$shipping_details->AddressLine1 = @$shipping_details->AddressLine2;
			$shipping_details->AddressLine2 = '';
		}

		// optional fields
		if (empty( $billing_details->Phone ) || $billing_details->Phone == 'Invalid Request') $billing_details->Phone = '';
		$order->set_billing_phone( stripslashes( $billing_details->Phone ) );

		// billing address
		@list( $billing_firstname, $billing_lastname )     = explode( " ", $buyer_name, 2 );

		if ( is_null( $billing_lastname ) ) $billing_lastname = '';
		if ( empty( $billing_details->AddressLine2 ) ) $billing_details->AddressLine2 = '';

		$order->set_billing_first_name( stripslashes( $billing_firstname ) );
		$order->set_billing_last_name( stripslashes( $billing_lastname ) );
		$order->set_billing_address_1( stripslashes( @$billing_details->AddressLine1 ) );
		$order->set_billing_address_2( stripslashes( @$billing_details->AddressLine2 ) );
		$order->set_billing_city( stripslashes( @$billing_details->City ) );
		$order->set_billing_postcode( stripslashes( @$billing_details->PostalCode ) );
		$order->set_billing_country( stripslashes( @$billing_details->CountryCode ) );
		$order->set_billing_state( stripslashes( WPLA_CountryHelper::get_state_two_letter_code( @$billing_details->StateOrRegion ) ) );

		// shipping address
		@list( $shipping_firstname, $shipping_lastname )   = explode( " ", $shipping_details->Name, 2 );

		if ( is_null( $shipping_lastname ) ) $shipping_lastname = '';
		if ( empty( $shipping_details->AddressLine2 ) ) $shipping_details->AddressLine2 = '';

		$order->set_shipping_first_name( stripslashes( $shipping_firstname ) );
		$order->set_shipping_last_name( stripslashes( $shipping_lastname ) );
		$order->set_shipping_address_1( stripslashes( @$shipping_details->AddressLine1 ) );
		$order->set_shipping_address_2( stripslashes( @$shipping_details->AddressLine2 ) );
		$order->set_shipping_city( stripslashes( @$shipping_details->City ) );
        $order->set_shipping_postcode( stripslashes( @$shipping_details->PostalCode ) );
        $order->set_shipping_country( stripslashes( @$shipping_details->CountryCode ) );
        $order->set_shipping_state( stripslashes( WPLA_CountryHelper::get_state_two_letter_code( @$shipping_details->StateOrRegion ) ) );

		// convert state names to ISO code
		self::fixCountryStates( $post_id, $order );

		// email address - if enabled
		if ( ! get_option('wpla_create_orders_without_email') && is_email( $buyer_email ) ) {
		    $order->set_billing_email( $buyer_email );
		}

        // Add billing and shipping address index so order becomes searchable #28767
        $order->update_meta_data( '_billing_address_index', implode( ' ', $order->get_address( 'billing' ) ) );
        $order->update_meta_data( '_shipping_address_index', implode( ' ', $order->get_address( 'shipping' ) ) );

        // Store the ship dates in the order notes
        if ( apply_filters( 'wpla_store_ship_dates', true, $order, $amazon_order ) && $details->EarliestShipDate && $details->LatestShipDate ) {
	        $earliest_dt = new DateTime( $details->EarliestShipDate, new DateTimeZone('UTC') );
	        $latest_dt = new DateTime( $details->LatestShipDate, new DateTimeZone('UTC') );

	        $earliest_dt->setTimezone( wp_timezone() );
	        $latest_dt->setTimezone( wp_timezone() );

            $order->update_meta_data( '_wpla_earliest_ship_date', $earliest_dt->format('Y-m-d H:i:s') );
            $order->update_meta_data( '_wpla_latest_ship_date', $latest_dt->format('Y-m-d H:i:s') );

            $from   = $earliest_dt->format( wc_date_format() . ' ' . wc_time_format() );
            $to     = $latest_dt->format( wc_date_format() . ' ' . wc_time_format() );

            $note = sprintf( __('Amazon Shipping date from %s to %s', 'wp-lister-for-amazon' ), $from, $to );
            $order->add_order_note( $note );
        }

        // Record VAT details from business orders #51622
        self::recordTaxRegistrationDetails( $order, $details );

		// order details
        $order->set_customer_id( 0 );
		$order->set_prices_include_tax( $this->getPricesIncludeTax() );
		$order->set_discount_total( 0 );
        $order->set_total( $details->OrderTotal->Amount );

		// Payment method handling
		$payment_method = $details->PaymentMethod;

		$payment_gateway = get_option( 'wpla_orders_default_payment_method', '' );
		$payment_method_title = get_option( 'wpla_orders_default_payment_title', 'Other' );

		if ( !$payment_gateway ) {
		    $payment_gateway = $payment_method_title;

            if ( $payment_method == 'PayPal' ) $payment_gateway = 'paypal';
        }

		$order->set_payment_method( $payment_gateway );
		$order->set_payment_method_title( $payment_method_title );

		// Order line amazon_order(s)
		$this->processOrderLineItems( $amazon_order['items'], $post_id, $order );

		// shipping info
		$this->processOrderShipping( $post_id, $amazon_order, $order );

		// process tax
		$this->processOrderVAT( $post_id, $amazon_order, $order );

		// Sales tax
        $this->processSalesTax( $post_id, $amazon_order, $order );


		// prevent WooCommerce from sending out notification emails when updating order status or creating customers
		$this->disableEmailNotifications();

		// create user account for customer - if enabled
		if ( get_option( 'wpla_create_customers' ) ) {
			$user_id = $this->addCustomer( $amazon_order['buyer_email'], $details );
			$order->set_customer_id( $user_id );
		}

		// support for WooCommerce Sequential Order Numbers Pro 1.5.6
		if ( isset( $GLOBALS['wc_seq_order_number_pro'] ) && method_exists( $GLOBALS['wc_seq_order_number_pro'], 'set_sequential_order_number' ) )
			$GLOBALS['wc_seq_order_number_pro']->set_sequential_order_number( $post_id );

		// support for WooCommerce Sequential Order Numbers Pro 1.7.0+
		if ( function_exists('wc_seq_order_number_pro') && method_exists( wc_seq_order_number_pro(), 'set_sequential_order_number' ) )
			wc_seq_order_number_pro()->set_sequential_order_number( $post_id );


		// would be nice if this worked:
		// $order->calculate_taxes();
		// $order->update_taxes();

		// order status
		if ( $amazon_order['status'] == 'Unshipped') { // TODO: what's the status when payment is complete?
			// unshipped orders: use config
			$new_order_status = get_option( 'wpla_new_order_status', 'processing' );
		} elseif ( $amazon_order['status'] == 'Shipped') {
			// shipped orders: use config
			$new_order_status = get_option( 'wpla_shipped_order_status', 'completed' );
		} else {
			// anything else: on hold
			$new_order_status = 'on-hold';
		}

        // As of WC 3.5, stocks are getting reduced when an order's status gets update to processing or completed.
        // This tells WC that we're taking care of updating the stocks so they do not get reduced twice!
        if ( get_option( 'wpla_sync_inventory' ) == '1' ) {
            // Ensure stock is marked as "reduced" in case payment complete or other stock actions are called.
            //$order->get_data_store()->set_stock_reduced( $post_id, true );

	        if ( method_exists( $order, 'set_order_stock_reduced' ) ) {
		        $order->set_order_stock_reduced( true );
	        } else {
		        $order->get_data_store()->set_stock_reduced( $post_id, true );
	        }
        }

        // allow 3rd-party code to modify the new order's status #32567
        $new_order_status = apply_filters( 'wpla_order_builder_new_order_status', $new_order_status, $order, $amazon_order );

		$order->set_status( $new_order_status );

		// the date_paid value needs to be set as soon as the order gets to the processing status #40317
        if ( $new_order_status == get_option( 'wpla_new_order_status', 'processing' ) ) {
            $order->set_date_paid( $post_date );
        }

		// fix the completed date for completed orders - which is set to the current time by update_status()
		if ( $new_order_status == 'completed' ) {
		    $order->set_date_completed( $post_date );
		    $order->set_date_paid( $post_date ); // Also set the date_paid value #35371
		}

        // Handle sales tax collected by Amazon
        // Added debug lines #56293
        if ( get_option( 'wpla_orders_sales_tax_action', 'ignore' ) == 'remove' ) {
            WPLA()->logger->info( 'Removing sales tax from order total' );

            $total_sales_tax = $this->getSalesTaxTotal( $amazon_order );

            $order_total = $order->get_total();
            $new_total = $order_total - $total_sales_tax;
            WPLA()->logger->info( 'Order total: '. $order_total .' less sales tax: '. $total_sales_tax. ' = '. $new_total );


            $order->set_total( $new_total );
        }

        // Handle IOSS tax collected by Amazon
        $ioss_data = $this->getOrderIOSS( $amazon_order['items'] );

        // Record IOSS to postmeta and order notes
        $this->recordIOSS( $ioss_data, $order );

        do_action( 'wpla_create_order_pre_save', $order, $amazon_order );

        $order->save();

        // German Market support for temporary tax reduction #38976
        if ( function_exists( 'german_market_temporary_tax_reduction_checkout_order_processed' ) ) {
            german_market_temporary_tax_reduction_checkout_order_processed( $order->get_id(), null, $order );
        }

        // Clearing the session appears to be breaking other plugins that run after WPLA creates an order #60151
        if ( apply_filters( 'wpla_orderbuilder_cleanup_session', false ) ) {
            // Cleanup: remove this Amazon customer shipping data from WC()->customer #59472
            WC()->customer = new WC_Customer();
        }

		// allow other developers to post-process orders created by WP-Lister
		// if you hook into this, please check if get_product() actually returns a valid product object
		// WP-Lister might create order line items which do not exist in WooCommerce!
		//
		// bad code looks like this:
		// $product = get_product( $amazon_order['product_id'] );
		// echo $product->get_sku();
		//
		// good code should look like this:
		// $_product = $order->get_product_from_item( $amazon_order );
		// if ( $_product->exists() ) { ... };

		do_action( 'wpla_after_create_order_with_nonexisting_items', $post_id );
		do_action( 'wpla_after_create_order', $post_id );

		// trigger WooCommerce webhook order.created - by simulating an incoming WC REST API request
		do_action( 'woocommerce_api_create_order', $post_id, array(), $order );

		return $post_id;

	} // createWooOrderFromAmazonOrder()

	/**
	 * @param WC_Order $order
	 *
	 * @return WC_Order
	 */
	private function addOrderAttributionTracking( $order ) {
		if ( $utm_source = get_option( 'wpla_order_utm_source', 'Amazon' ) ) {
			$order->update_meta_data( '_wc_order_attribution_source_type', 'utm' );
			$order->update_meta_data( '_wc_order_attribution_utm_source', $utm_source );
		}

		if ( $utm_campaign = get_option( 'wpla_order_utm_campaign' ) ) {
			$order->update_meta_data( '_wc_order_attribution_utm_campaign', $utm_campaign );
		}

		if ( $utm_medium = get_option( 'wpla_order_utm_medium', 'WP-Lister' ) ) {
			$order->update_meta_data( '_wc_order_attribution_utm_medium', $utm_medium );
		}

		return $order;
	}

	/**
     * convert country state names to ISO code (New South Wales -> NSW or Quebec -> QC)
     * @param int $post_id
     * @param WC_Order &$order
     */
	function fixCountryStates( $post_id, &$order ) {
		if ( ! class_exists('WC_Countries') ) return; // requires WC2.3+

        $billing_country_code = $order->get_billing_country();
        $billing_state_name   = $order->get_billing_state();

		$country_states       = WC()->countries->get_states( $billing_country_code );
		$state_code           = $country_states ? array_search( strtolower($billing_state_name), array_map('strtolower',$country_states) ) : false; // case insensitive array_search()

        if ( $state_code ) {
            $order->set_billing_state( $state_code );
		}

        $shipping_country_code = $order->get_shipping_country();
        $shipping_state_name   = $order->get_shipping_state();

		$country_states        = WC()->countries->get_states( $shipping_country_code );
		$state_code            = $country_states ? array_search( strtolower($shipping_state_name), array_map('strtolower',$country_states) ) : false; // case insensitive array_search()

        if ( $state_code ) {
            $order->set_shipping_state( $state_code );
		}
	} // fixCountryStates()

    /**
     * @param WC_Order $order
     * @param stdClass $details
     */
    public static function recordTaxRegistrationDetails( $order, $details ) {
        WPLA()->logger->info( 'recordTaxRegistrationDetails #'. $order->get_id() );

        if ( isset( $details->TaxRegistrationDetails ) && isset( $details->TaxRegistrationDetails->member ) ) {
            $vat_id = $details->TaxRegistrationDetails->member->taxRegistrationId;
            WPLA()->logger->info( 'Found VAT ID: '. $vat_id );

            if ( $vat_id ) {
                $order->update_meta_data( 'billing_vat', $vat_id );
                WPLA()->logger->info( 'Stored VAT ID '. $vat_id .' into billing_vat' );
            }
        }
    }


	/**
     * process shipping info - create shipping line item
     * @param int $post_id
     * @param array $item
     * @param WC_Order $order
     */
	function processOrderShipping( $post_id, $item, &$order = null ) {
        WPLA()->logger->debug( 'processOrderShipping #'. $post_id );

		// shipping fee (gross)
		$shipping_total = $this->getShippingTotal( $item['items'], $order, true );

		WPLA()->logger->debug( 'shipping_total: '. $shipping_total );

		// calculate shipping tax amount (VAT is usually applied to shipping fee)
        $shipping_tax_amount = $this->calculateShippingTaxAmount( $shipping_total, $post_id, $order );
        WPLA()->logger->debug( 'shipping_tax_amount: '. $shipping_tax_amount );

        if ( 'import' != get_option( 'wpla_orders_tax_mode', 'none' ) || apply_filters( 'wpla_order_builder_force_shipping_tax_deduction', false ) || get_option( 'wpla_orders_force_deduct_shipping_tax', 0 ) == 1 ) {
            // Do not deduct tax from shipping total if importing taxes from Amazon #20062
            $shipping_total = $shipping_total - $shipping_tax_amount;
            WPLA()->logger->debug( 'Deducted tax from shipping_total: '. $shipping_total );
        }

		// update shipping total (gross - without taxes)
        $order->set_shipping_total( $shipping_total );
        WPLA()->logger->debug( 'set order shipping_total: '. $shipping_total );

		// shipping method
		$details = $item['details'];
		$shipping_method_id_map    = apply_filters( 'wpla_shipping_service_id_map', array(), $order );
		$shipping_method_id        = array_key_exists($details->ShipServiceLevel, $shipping_method_id_map) ? $shipping_method_id_map[$details->ShipServiceLevel] : $details->ShipServiceLevel;
		$shipping_method_title_map = apply_filters( 'wpla_shipping_service_title_map', array(), $order );
		$shipping_method_title     = array_key_exists($details->ShipServiceLevel, $shipping_method_title_map) ? $shipping_method_title_map[$details->ShipServiceLevel] : $details->ShipServiceLevel;

        // Added for #40816
        $shipping_method_title = apply_filters( 'wpla_shipping_method_title', $shipping_method_title, $post_id, $item, $order );

        WPLA()->logger->debug( 'shipping_method_title: '. $shipping_method_title );

        $shipping_taxes = $this->shipping_taxes;
        $shipping_taxes['total'] = $shipping_taxes;
        $method_id = $shipping_total == 0 ? 'free_shipping' : $shipping_method_id;
        WPLA()->logger->debug( 'method_id: '. $method_id );

        // Allow 3rd-party code to set the instance_id #45677
        $instance_id = apply_filters( 'wpla_shipping_instance_id', 0, $shipping_method_id, $shipping_method_title );

        if ( $order ) {
            $line = new WC_Order_Item_Shipping();
            $line->set_method_title( $shipping_method_title );
            $line->set_total( floatval( $shipping_total ) );
            $line->set_method_id( $method_id );
            $line->set_taxes( $shipping_taxes );
            $line->set_instance_id( $instance_id );

            $order->add_item( $line );
            WPLA()->logger->debug( 'added shipping line item: '. print_r( $line,1 ) );
        } else {
            // create shipping info as order line items - WC2.2
            $item_id = wc_add_order_item( $post_id, array(
                'order_item_name' 		=> $shipping_method_title,
                'order_item_type' 		=> 'shipping'
            ) );
            if ( $item_id ) {
                wc_add_order_item_meta( $item_id, 'cost', 		$shipping_total );
                wc_add_order_item_meta( $item_id, 'method_id', $method_id );
                wc_add_order_item_meta( $item_id, 'taxes', 	$shipping_taxes );
                wc_add_order_item_meta( $item_id, 'total_tax', array_sum( $shipping_taxes['total'] ) );
            }
        }


		// filter usage:
		// add_filter( 'wpla_shipping_service_title_map', 'my_amazon_shipping_service_title_map' );
		// function my_amazon_shipping_service_title_map( $map ) {
		// 	$map = array_merge( $map, array(
		// 		'Std DE Dom' => 'DHL Paket'
		// 	));
		// 	return $map;
		// }
		// add_filter( 'wpla_shipping_service_id_map', 'my_amazon_shipping_service_id_map' );
		// function my_amazon_shipping_service_id_map( $map ) {
		// 	$map = array_merge( $map, array(
		// 		'Std DE Dom' => 'flat_rate'
		// 	));
		// 	return $map;
		// }

	} // processOrderShipping()

    // calculate shipping tax amount based on global VAT rate
    // (VAT is usually applied to shipping fee)
    function calculateShippingTaxAmount( $shipping_total, $post_id, $order = null ) {
        WPLA()->logger->info( '[tax] calculateShippingTaxAmount('. $shipping_total .', '. $post_id .')' );

        // get global VAT rate
        $tax_mode           = get_option( 'wpla_orders_tax_mode' );
        $vat_percent        = get_option( 'wpla_orders_fixed_vat_rate' );
        $shipping_tax_amount= 0;
        $shipping_taxes     = array();

        WPLA()->logger->info( '[tax] tax_mode: '. $tax_mode );
        WPLA()->logger->info( '[tax] vat_percent: '. $vat_percent );

        if ( ! $tax_mode ) {
            $shipping_taxes = array(); // do nothing
        } elseif ( $tax_mode == 'autodetect' ) {
            // Do nothing. Taxes will be calculated in the processOrderVAT method
        } elseif ( $tax_mode == 'fixed' && $vat_percent ) {
            // calculate VAT
            $tax_rate_id        = get_option( 'wpla_orders_tax_rate_id' );
            $shipping_tax_amount = $shipping_total / ( 1 + ( 1 / ( $vat_percent / 100 ) ) );	// calc VAT from gross amount
            $shipping_taxes     = $shipping_tax_amount == 0 ? array() : array( $tax_rate_id => $shipping_tax_amount );

            WPLA()->logger->info( '[tax] tax_rate_id: '. $tax_rate_id );
        } elseif ( $tax_mode == 'import' ) {
            // Amazon shipping taxes are stored in WPLA_OrderBuilder::shipping_taxes
            return array_sum( $this->shipping_taxes );
        }

        // Allow 3rd-party plugins to modify or remove entirely the shipping taxes #38926
        $shipping_taxes = apply_filters( 'wpla_order_shipping_taxes', $shipping_taxes, $shipping_total, $post_id, $order );

        if ( !empty( $shipping_taxes ) ) {
            $this->shipping_taxes = $shipping_taxes;
            $shipping_tax_amount = array_sum( $shipping_taxes );
        }

        WPLA()->logger->info( '[tax] shipping_tax_amount: '. $shipping_tax_amount );

        return $shipping_tax_amount;
    }

    /**
     * @param $post_id
     * @param $item
     * @param WC_Order $order
     */
	function processOrderVAT( $post_id, $item, &$order ) {
		global $wpdb;

		$tax_mode           = get_option( 'wpla_orders_tax_mode' );

		WPLA()->logger->info( '[tax] processOrderVAT('. $post_id .')' );
		WPLA()->logger->info( '[tax] tax_mode: '. $tax_mode );
		WPLA()->logger->debug( '[tax] vat_rates: '. print_r( $this->vat_rates, true ) );
		WPLA()->logger->debug( '[tax] shipping_taxes: '. print_r( $this->shipping_taxes, true ) );

        // don't add VAT tax mode isn't set or disabled
        if ( ! $tax_mode ) {
            return;
        }

        $prices_include_tax = $this->getPricesIncludeTax();

        if ( $tax_mode == 'autodetect' && is_callable( array( $order, 'calculate_taxes' ) ) ) {
            if ( $prices_include_tax == 'yes' || ( function_exists( 'wc_prices_include_tax' ) && wc_prices_include_tax() ) ) {
                add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'fixGrossTaxCalculation' ), 10, 2 );
                add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( $this, 'fixGrossShippingTaxCalculation' ), 10, 2 );
            }

            if ( function_exists( 'german_market_temporary_tax_reduction_find_is_frontend_activated' ) && german_market_temporary_tax_reduction_find_is_frontend_activated() ) {
                add_filter( 'woocommerce_find_rates', 'german_market_temporary_tax_reduction_find_rates', 60, 2 );
                add_filter( 'woocommerce_rate_percent', 'german_market_temporary_tax_reduction_rate_percent', 60, 2 );
            }
            $order->calculate_taxes();

            // now that we have the correct line taxes, adjust the line item totals so they add up properly
            $this->deductTaxesFromLineTotals( $order );

            // also adjust the shipping total to remove the tax
            $this->deductTaxesFromShipping( $order );
            return;
        }

		// shipping fee (gross)
		$shipping_total      = $this->getShippingTotal( $item['items'], $order );

        // calculate shipping tax (from gross amount)
        $shipping_tax_amount = $this->calculateShippingTaxAmount( $shipping_total, $post_id, $order );

        // store shipping taxes separately if $vat_rates is empty #17729
        if ( empty( $this->vat_rates ) && !empty( $this->shipping_taxes ) ) {
            foreach ( $this->shipping_taxes as $rate_id => $tax_amount ) {
                $this->addOrderLineTax( $post_id, $rate_id, 0, $tax_amount );
            }
        } else {
            foreach ( $this->vat_rates as $tax_rate_id => $tax_amount ) {
                // Pull the correct shipping tax for the current tax rate
                $shipping_tax = isset( $this->shipping_taxes[ $tax_rate_id ] ) ? $this->shipping_taxes[ $tax_rate_id ] : 0;

                // remove shipping taxes that have been already recorded
                unset( $this->shipping_taxes[ $tax_rate_id ] );

                $this->addOrderLineTax( $post_id, $tax_rate_id, $tax_amount, $shipping_tax );
            }

            // record other shipping taxes
            foreach ( $this->shipping_taxes as $rate_id => $tax_amount ) {
                $this->addOrderLineTax( $post_id, $rate_id, 0, $tax_amount );
            }
        }

        // store total order tax
        WPLA()->logger->info( '[tax] Storing _order_tax: '. $this->vat_total );
        WPLA()->logger->info( '[tax] Storing _order_shipping_tax: '. $shipping_tax_amount );

        $order->set_shipping_tax( $this->format_decimal( $shipping_tax_amount ) );

        // set _order_tax for backwards compatibility #29912
        $order->update_meta_data( '_order_tax', $this->format_decimal( $this->vat_total ) );


        // if autodetect taxes is enabled and woocommerce_prices_include_tax is disabled,
        // add the tax total to the order total #15043
        //
        // Added the 'wplister_include_vat_in_order_total' filter to allow external code to prevent VAT from being added to the order total #16294
        if ( $tax_mode == 'autodetect' && $prices_include_tax == 'no' && apply_filters( 'wpla_include_vat_in_order_total', true, $post_id, $item ) ) {

            $order_total = $order->get_total();
            $order->set_total( $order_total + $this->vat_total );

        }
	} // processOrderVAT()

    function processSalesTax( $post_id, $item, &$order = null ) {
        global $wpdb;

        WPLA()->logger->info( 'processSalesTax' );

        if ( get_option( 'wpla_orders_sales_tax_action', 'ignore' ) != 'record' ) {
            // If Sales Tax Action is not set to record, do nothing
            WPLA()->logger->info( 'wpla_orders_sales_tax_action is not set to record. Skipping.' );

            return;
        }

        $amount = $this->getSalesTaxTotal( $item );

        if ( ! $amount ) {
            WPLA()->logger->info( 'getSalesTaxTotal returned 0. Skipping.' );
            return;
        }

        // get tax rate
        $tax_rate_id = get_option( 'wpla_orders_sales_tax_rate_id' );
        // do not store sales tax if no sales tax rate ID is selected #18242
        if ( ! $tax_rate_id ) {
            WPLA()->logger->info( 'No tax rate found for Sales Tax. Skipping.' );
            return;
        }

        $this->addOrderLineTax( $post_id, $tax_rate_id, $amount );
    }

    /**
     * Override WC_Order_Item::calculate_taxes() method to pass the gross amount to WC_Tax::calc_tax()
     * @param WC_Order_Item $item
     * @param array $calculate_tax_for
     *
     * @return bool
     */
    public function fixGrossTaxCalculation( $item, $calculate_tax_for ) {
        if ( ! isset( $calculate_tax_for['country'], $calculate_tax_for['state'], $calculate_tax_for['postcode'], $calculate_tax_for['city'] ) ) {
            return false;
        }
        if ( '0' !== $item->get_tax_class() && 'taxable' === $item->get_tax_status() && wc_tax_enabled() ) {
            $calculate_tax_for['tax_class'] = $item->get_tax_class();
            $tax_rates                      = WC_Tax::find_rates( $calculate_tax_for );
            $taxes                          = WC_Tax::calc_tax( $item->get_total(), $tax_rates, true );

            if ( method_exists( $item, 'get_subtotal' ) ) {
                $subtotal_taxes = WC_Tax::calc_tax( $item->get_subtotal(), $tax_rates, true );
                $item->set_taxes(
                    array(
                        'total'    => $taxes,
                        'subtotal' => $subtotal_taxes,
                    )
                );
            } else {
                $item->set_taxes( array( 'total' => $taxes ) );
            }
        } else {
            $item->set_taxes( false );
        }
    }

    public function getOrderIOSS( $items ) {
        WPLA()->logger->info( 'getOrderIOSS()' );
        $data = array();

        foreach ( $items as $item ) {
            // Record IOSS number from Amazon
            if ( isset( $item->IossNumber ) && $item->IossNumber ) {
                $data[] = $item->IossNumber;
            }

        }
        WPLA()->logger->debug( 'IOSS Data:' . print_r( $data, 1) );

        return $data;
    }

    /**
     * Store IOSS data in order meta and order notes
     * @param array $ioss
     * @param WC_Order $order
     */
    public function recordIOSS( $ioss, $order ) {
        if ( empty( $ioss ) ) return;

        add_post_meta( $order->get_id(), '_wpla_order_ioss', implode( ', ', $ioss ) );

        $order->add_order_note( 'Amazon order IOSS: '. implode( ', ', $ioss ) );

    }

    /**
     * Override WC_Order_Item::calculate_taxes() method to pass the gross amount to WC_Tax::calc_tax()
     * @param WC_Order_Item $item
     * @param array $calculate_tax_for
     *
     * @return bool
     */
    public function fixGrossShippingTaxCalculation( $item, $calculate_tax_for ) {
        if ( ! isset( $calculate_tax_for['country'], $calculate_tax_for['state'], $calculate_tax_for['postcode'], $calculate_tax_for['city'] ) ) {
            return false;
        }

        if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' ) {
            $tax_rates = WC_Tax::find_shipping_rates( $calculate_tax_for );
            $taxes     = WC_Tax::calc_tax( $item->get_total(), $tax_rates, true );
            $item->set_taxes( array( 'total' => $taxes ) );
        } else {
            $item->set_taxes( false );
        }
    }

    /**
     * @param $item
     * @param $post_id
     * @param WC_Order $order
     * @todo clean up
     */
	function createOrderLineItem( $item, $post_id, &$order = null ) {
	    $item = apply_filters( 'wpla_before_order_builder_line_item', $item, $post_id, $order );

		// get listing item from db
		$listingsModel = new WPLA_ListingsModel();

        if ( 'asin' == get_option( 'wpla_order_item_matching_mode', 'asin' ) ) {
            $listingItem = $listingsModel->getItemByASIN( $item->ASIN );
        } else {
            $listingItem   = $listingsModel->getItemBySKU( $item->SellerSKU );
        }


		$product_id			= $listingItem ? $listingItem->post_id : '0';
		$wc_product         = ( $product_id ) ? wc_get_product( $product_id ) : false;
		$item_name 			= $listingItem ? $listingItem->listing_title : $item->Title;
		$item_quantity 		= $item->QuantityOrdered;

		$line_subtotal		= $item->ItemPrice->Amount;
		$line_total 		= $item->ItemPrice->Amount;
		$item_discount      = 0;

		// Skip processing if QuantityOrders is 0 #36607
        if ( $item_quantity == 0 ) {
            WPLA()->logger->info( 'Skipped createOrderLineItem for '. $item->SellerSKU .' because the quantity is 0' );
            return;
        }

        // Skip processing if ItemPrice is empty #63017
        if ( empty( $line_total ) && apply_filters( 'wpla_order_builder_skip_zero_priced_items', true ) ) {
            WPLA()->logger->info( 'Skipped createOrderLineItem for '. $item->SellerSKU .' because the ItemPrice is empty' );
            return;
        }

        // Record product bundles #47565
        $bundle_id = 0;
        if ( $wc_product && $wc_product->get_type() === 'bundle' && class_exists('WC_PB_Order')) {
            $instance = \WC_PB_Order::instance();
            $bundle_id = $instance->add_bundle_to_order( $wc_product, $order, $item->QuantityOrdered );

            if ( is_wp_error( $bundle_id ) ) {
                $bundle_id = 0;
            }
        }

		// Record promotional discounts
		if ( isset( $item->PromotionDiscount ) ) {
			$discount_amount = $item->PromotionDiscount->Amount;
            WPLA()->logger->info( 'discount amount: '. $discount_amount );
			$this->recordOrderDiscount( $discount_amount, $order );

			if ( apply_filters( 'wpla_deduct_discounts_from_line_total', true ) ) {
				// Reduce the line total so the taxes will be adjusted accordingly
				$line_total -= $discount_amount;
			}
		}

        $product_price = $line_total / $item_quantity;

        WPLA()->logger->info( '[tax] createOrderLineItem() for #'. $post_id );
        WPLA()->logger->info( '[tax] quantity: '. $item_quantity );
        WPLA()->logger->info( '[tax] line_total: '. $line_total );
        WPLA()->logger->info( '[tax] product_price: '. $product_price );

		// default to no tax
		$vat_enabled        = false;
		$line_subtotal_tax	= '0.00';
		$line_tax		 	= '0.00';
		$item_tax_class		= '';

        $taxes = $this->getProductTax( $product_price, $product_id, $item_quantity, $order, $item );
		WPLA()->logger->debug( "[tax] getProductTax() returned: ".print_r($taxes,1) );

		if ( $taxes['line_tax'] > 0 ) {
            $vat_enabled = true;
        }

		// process VAT if enabled
		if ( $vat_enabled ) {

	        if ( $taxes['line_subtotal_tax'] ) {
	            $line_subtotal_tax = $taxes['line_subtotal_tax'];
	        }

	        if ( $taxes['line_tax'] ) {
	            $line_tax = $taxes['line_tax'];
	        }

	        if ( $taxes['tax_rate_id'] ) {
	            $tax_rate_id = $taxes['tax_rate_id'];
	        }

	        if ( $taxes['line_total'] ) {
		        if ( $item_discount ) {
	                $line_total = $taxes['line_total'] - $item_discount;
				} else {
			        $line_total = $taxes['line_total'];
		        }
	        }

	        if ( $taxes['line_subtotal'] ) {
				$line_subtotal = $taxes['line_subtotal'];
	        }

			// keep record of total VAT
            $vat_tax = $line_tax;
			$this->vat_enabled = true;
			$this->vat_total  += $vat_tax;

            // Use $taxes['line_tax_data'] to store multiple tax rates if available #13585
            if ( is_array( $taxes['line_tax_data']['total'] ) ) {
                foreach ( $taxes['line_tax_data']['total'] as $rate_id => $amount ) {
                    @$this->vat_rates[ $rate_id ] += $amount;
                }
            }

			// $vat_tax = wc_round_tax_total( $vat_tax );
			$vat_tax = $this->format_decimal( $vat_tax );
			WPLA()->logger->info( '[tax] VAT: '.$vat_tax );
			WPLA()->logger->info( '[tax] vat_total: '.$this->vat_total );

			$line_subtotal_tax	= $vat_tax;
			$line_tax		 	= $vat_tax;
		}

        // set tax class
        if ( $wc_product && is_object($wc_product) )
            $item_tax_class		= $wc_product->get_tax_class();

        WPLA()->logger->info( "[tax] tax_class for product ID $product_id: ".$item_tax_class );

		// check if item is variation - and get variation_id and parent post_id
		$isVariation = $listingItem ? $listingItem->product_type == 'variation' : false;
		if ( $isVariation ) {
			$product_id   = $listingItem->parent_id;
			$variation_id = $listingItem->post_id;
		}

		// Record last sale date for repricing purposes #10477
        $last_sale_date = false;
        if ( ! empty( $variation_id ) ) {
            $product_post_id    = $variation_id;
            $last_sale_date     = current_time( 'mysql' );
        } elseif ( ! empty( $product_id ) ) {
            $product_post_id    = $product_id;
            $last_sale_date     = current_time( 'mysql' );
        }

        if ( get_option( 'wpla_use_local_product_name_in_orders', 0 ) && !empty( $product_post_id ) ) {
            $product = wc_get_product( $product_post_id );

            if ( $product && $product->exists() ) {
                $item_name = $product->get_name();
            }
        }

        if ( $last_sale_date && !empty($product_post_id) ) {
            update_post_meta( $product_post_id, '_wpla_last_purchase_date', $last_sale_date );
        }

		$order_item = array();

		$order_item['product_id'] 			= $product_id;
		$order_item['variation_id'] 		= $variation_id ?? '0';
		$order_item['name'] 				= $item_name;
		$order_item['tax_class']			= $item_tax_class;
		$order_item['qty'] 					= $item_quantity;
		$order_item['line_subtotal'] 		= $this->format_decimal( $line_subtotal );
		$order_item['line_subtotal_tax'] 	= $line_subtotal_tax;
		$order_item['line_total'] 			= $this->format_decimal( $line_total );
		$order_item['line_tax'] 			= $line_tax;
		$order_item['line_tax_data'] 		= array(
            'total'     => $taxes['line_tax_data']['total'],
            'subtotal'  => $taxes['line_tax_data']['subtotal']
		);

        $order_item = apply_filters( 'wpla_order_builder_line_item', $order_item, $post_id, $item );

		WPLA()->logger->debug( '[tax] order_item: '. print_r( $order_item, true ) );

		if ( $order ) {
		    WPLA()->logger->info( 'adding line item to order' );
		    $line = new WC_Order_Item_Product( $bundle_id );

            if ( $wc_product ) {
                $line->set_product( $wc_product );
                WPLA()->logger->info( 'wc_product assigned to line item' );
            }

		    $line->set_name( $order_item['name'] );
		    $line->set_quantity( $order_item['qty'] );
		    $line->set_tax_class( $order_item['tax_class'] );
		    $line->set_order_id( $order->get_id() );
		    $line->set_total( $order_item['line_total'] );
		    $line->set_subtotal( $order_item['line_subtotal'] );
		    $line->set_total_tax( $order_item['line_tax'] );
		    $line->set_subtotal_tax( $order_item['line_subtotal_tax'] );
		    $line->set_taxes( $order_item['line_tax_data'] );

		    if ( isset( $variation_id ) ) {
		        try {
                    $line->set_variation_id( $variation_id );
                    WPLA()->logger->info( 'set variation id: '. $variation_id );
                } catch ( WC_Data_Exception $exception ) {
		            WPLA()->logger->info( 'Error assigning variation ID: '. $variation_id );
		            WPLA()->logger->info( $exception->getMessage() );
                }
            }

            $item_meta = array();

		    if ( get_option( 'wpla_amazon_store_sku_as_order_meta', 1 ) ) {
		        $item_meta['SKU'] = $item->SellerSKU;
            }

            // This tells WC that we're taking care of updating the stocks so they do not get reduced twice!
            // This is also used to tell WC to restock this item in case of a refund #31230 #31062
            if ( get_option( 'wpla_sync_inventory' ) == '1' ) {
                $item_meta['_reduced_stock'] = $order_item['qty'];
            }

            // Record IOSS number from Amazon
            if ( isset( $item->IossNumber ) && $item->IossNumber ) {
                $item_meta['IOSS'] = $item->IossNumber;
            }

            // handle GiftMessageText
            if ( isset( $item->GiftMessageText ) && $item->GiftMessageText ) {
                $item_meta['Gift Message'] = $item->GiftMessageText;
            }

            // handle BuyerCustomizedInfo
            if ( isset( $item->BuyerInfo ) && isset( $item->BuyerInfo->CustomizedURL ) ) {
                WPLA()->logger->info( 'downloading custom buyer info' );
                $properties = $this->downloadBuyerCustomizedInfo( $item );

                if ( is_array( $properties ) ) {
                    foreach ( $properties as $property ) {
                        $value = '';

                        if ( !empty( $property['value'] ) ) {
                            $value = $property['value'] .'<br/>';
                        }

                        if ( !empty( $property['font'] ) || !empty( $property['color'] ) ) {
                            $value .= '<small>(' . $property['font'] . ' ' . $property['color'] .')</small>';
                        }
                        $item_meta[ $property['label'] ] = $value;
                    }
                }
            }

            $item_meta = apply_filters( 'wpla_new_order_item_meta', $item_meta, $line, $item, $post_id, $order_item );
            WPLA()->logger->debug( 'item_meta array: '. print_r( $item_meta, 1 ) );

            if ( $item_meta ) {
                foreach ( $item_meta as $key => $value ) {
                    $line->add_meta_data( $key, $value );
                }
                $line->save_meta_data();
            }
            WPLA()->logger->info( 'Added item_meta to line_item' );

            $line = apply_filters( 'wpla_new_order_item_product', $line, $item, $post_id, $order_item );
            WPLA()->logger->debug( 'final line_item: '. print_r( $line, 1 ) );
		    $order->add_item( $line );
            WPLA()->logger->info( 'added line item to order' );

        } else {
            // Add line item
            $item_id = wc_add_order_item( $post_id, array(
                'order_item_name' 		=> $order_item['name'],
                'order_item_type' 		=> 'line_item'
            ) );

            // Add line item meta
            if ( $item_id ) {
                wc_add_order_item_meta( $item_id, '_qty', 				$order_item['qty'] );
                wc_add_order_item_meta( $item_id, '_tax_class', 		$order_item['tax_class'] );
                wc_add_order_item_meta( $item_id, '_product_id', 		$order_item['product_id'] );
                wc_add_order_item_meta( $item_id, '_variation_id', 	$order_item['variation_id'] );
                wc_add_order_item_meta( $item_id, '_line_subtotal', 	$order_item['line_subtotal'] );
                wc_add_order_item_meta( $item_id, '_line_subtotal_tax',$order_item['line_subtotal_tax'] );
                wc_add_order_item_meta( $item_id, '_line_total', 		$order_item['line_total'] );
                wc_add_order_item_meta( $item_id, '_line_tax', 		$order_item['line_tax'] );
                wc_add_order_item_meta( $item_id, '_line_tax_data', 	$order_item['line_tax_data'] );
                wc_add_order_item_meta( $item_id, 'SKU', 				$item->SellerSKU );

                // This tells WC that we're taking care of updating the stocks so they do not get reduced twice!
                // This is also used to tell WC to restock this item in case of a refund #31230 #31062
                if ( get_option( 'wpla_sync_inventory' ) == '1' ) {
                    wc_add_order_item_meta( $item_id, '_reduced_stock', $order_item['qty'] );
                }

                // handle GiftMessageText
                if ( isset( $item->GiftMessageText ) && $item->GiftMessageText ) {
                    wc_add_order_item_meta( $item_id, 'Gift Message', 	$item->GiftMessageText );
                }

                // handle BuyerCustomizedInfo
                if ( isset( $item->BuyerInfo ) && isset( $item->BuyerInfo->CustomizedURL ) ) {
                    $properties = $this->downloadBuyerCustomizedInfo( $item );

                    if ( is_array( $properties ) ) {
                        foreach ( $properties as $property ) {
                            $value = '';

                            if ( !empty( $property['value'] ) ) {
                                $value = $property['value'] .'<br/>';
                            }

                            if ( !empty( $property['font'] ) || !empty( $property['color'] ) ) {
                                $value .= '<small>(' . $property['font'] . ' ' . $property['color'] .')</small>';
                            }
                            wc_add_order_item_meta( $item_id, $property['label'], $value );
                        }
                    }
                }

                do_action( 'wpla_added_order_item_meta', $item_id, $item, $post_id, $order_item );
            }
        }

	 	// // add variation attributes as order item meta (WC2.2)
	 	// if ( $item_id && $isVariation ) {
	 	// 	foreach ($VariationSpecifics as $attribute_name => $value) {
		//  	woocommerce_add_order_item_meta( $item_id, $attribute_name,	$value );
	 	// 	}
	 	// }

        if ( get_option( 'wpla_orders_record_gift_wrap_items', 1 ) ) {
            $this->processGiftWrapOption( $item, $post_id, $order );
        }

	} // createOrderLineItem()

    /**
     * If prices are set to include taxes, we need to deduct the line item taxes from
     * the line item totals so they would add up nicely in the WC Order page and invoices
     *
     * @param WC_Order $order
     */
    private function deductTaxesFromLineTotals( $order ) {
        $prices_include_tax = $this->getPricesIncludeTax();

	    if ( $prices_include_tax == 'yes' || apply_filters( 'wpla_order_builder_force_deduct_line_taxes', false, $order ) ) {
	        $items = $order->get_items();

	        foreach ( $items as $item ) {
	            $new_total  = $item->get_total() - $item->get_total_tax();
	            $item->set_subtotal( $new_total );
	            $item->set_total( $new_total );
	            $item->save();
            }
        }
    }

    /**
     * Since using WC_Order::calculate_totals() adds tax to the shipping total, this
     * method deducts the calculated shipping tax from the shipping total to prevent order total inaccuracy
     * @param WC_Order &$order
     * @throws WC_Data_Exception
     */
    private function deductTaxesFromShipping( &$order ) {
        $shipping = $order->get_items( 'shipping' );
        $shipping_total = 0;

        foreach ( $shipping as $item ) {
            $new_total = $item->get_total() - $item->get_total_tax();
            $shipping_total += $new_total;

            $item->set_total( $new_total );
            $item->save();
        }

        // Apply filters to deduct the shipping tax from the shipping total if prices include tax #51556
        $prices_include_tax = $this->getPricesIncludeTax();
        if ( $prices_include_tax == 'yes' ) {
            $order->set_shipping_total( $shipping_total );
        }

    }

	// process optional gift wrap option
	function processGiftWrapOption( $item, $post_id, &$order = null ) {
	    // Check both the GiftWrap Amount and Level to see if we should proceed or not #29493
		//if ( ! isset( $item->GiftWrapLevel ) ) return;
        $amount = isset( $item->BuyerInfo->GiftWrapPrice->Amount ) ? floatval( $item->BuyerInfo->GiftWrapPrice->Amount ) : 0;
        if ( !isset( $item->BuyerInfo->GiftWrapLevel ) && empty( $amount ) ) return;

		// gift wrap price and title
		$giftwrap_total = $item->BuyerInfo->GiftWrapPrice->Amount;
		$label          = isset( $item->BuyerInfo->GiftWrapLevel ) ? 'Gift wrap option: ' . $item->BuyerInfo->GiftWrapLevel . ' (' . $item->SellerSKU . ')' : 'Gift Wrap ('. $item->SellerSKU .')';

        /* Just download the GiftWrapTax from the order data
        // calculate VAT tax amount
        $giftwrap_tax_amount = 0;
        if ( $this->vat_enabled ) {
            $vat_percent         = get_option( 'wpla_orders_fixed_vat_rate' );
            $giftwrap_tax_amount = $giftwrap_total / ( 1 + ( 1 / ( $vat_percent / 100 ) ) );	// calc VAT from gross amount
            $giftwrap_tax_amount = $vat_percent ? $giftwrap_tax_amount : 0;						// disable VAT if no percentage set
            // $giftwrap_total   = $giftwrap_total - $giftwrap_tax_amount;

            // adjust line item price if prices include tax
            // if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {
                $giftwrap_total  = $giftwrap_total - $giftwrap_tax_amount;
            // }

            $this->vat_total  	+= $giftwrap_tax_amount;
        }*/
		$giftwrap_tax_amount = $item->BuyerInfo->GiftWrapTax->Amount;

		// get global tax rate id for order item array (TODO: make this a global option - Shipping Tax Rate)
		$tax_rate_id = get_option( 'wpla_orders_tax_rate_id' );

		if ( $order ) {
		    /* @var $order WC_Order */
		    $fee = new WC_Order_Item_Fee();
		    $fee->set_name( $label );
		    $fee->set_amount( $giftwrap_total );
		    $fee->set_total( $giftwrap_total );

            if ( $giftwrap_tax_amount ) {
                $tax_data = array( $tax_rate_id => $giftwrap_tax_amount );
                $fee->set_taxes( array( 'total' => $tax_data, 'subtotal' => $tax_data ) );
                $fee->set_total_tax( $giftwrap_tax_amount );
            }

		    $fee->save();
		    $order->add_item( $fee );
        } else {
            // German Market is hooking into the wc_add_order_item hook too soon so remove that for now #40792
            add_filter( 'german_market_ppu_co_woocommerce_add_order_item_meta_wc_3_return', '__return_true' );

            // create shipping info as order line items - WC2.2
            $item_id = wc_add_order_item( $post_id, array(
                'order_item_name' 		=> $label,
                'order_item_type' 		=> 'shipping'
            ) );
            if ( $item_id ) {
                wc_add_order_item_meta( $item_id, 'cost', 		$giftwrap_total );
                wc_add_order_item_meta( $item_id, 'method_id', $giftwrap_total == 0 ? 'free_shipping' : 'other' );
                wc_add_order_item_meta( $item_id, 'taxes', 	$giftwrap_tax_amount == 0 ? array() : array( $tax_rate_id => $giftwrap_tax_amount ) );
            }
        }

	} // processGiftWrapOption()

    /**
     * Downloads the ZIP file from the provided URL in Item.BuyerCustomizedInfo.CustomizedURL and parses the
     * JSON data to return an associative array of the buyer's customizations
     *
     * @param $item
     * @return array|bool Array of properties or FALSE on error
     */
    function downloadBuyerCustomizedInfo( $item ) {
        // need to make sure WP_Filesystem() is available #30598
        if ( !function_exists( 'WP_Filesystem' ) ) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        WP_Filesystem();

        WPLA()->logger->info( 'Handling BuyerCustomizedInfo: '. $item->BuyerInfo->CustomizedURL );

        // Download the ZIP from Amazon then extract to get the JSON file
        $zip    = download_url( $item->BuyerInfo->CustomizedURL );
        $json   = false;

        if ( is_wp_error( $zip ) ) {
            WPLA()->logger->error( 'Error downloading the Customized Info from '. $item->BuyerInfo->CustomizedURL .' because '. $zip->get_error_message() );
            return false;
        } else {
            $tmpdir = sys_get_temp_dir() .'/'. $item->OrderItemId;
            @mkdir( $tmpdir );
            $unzip = unzip_file( $zip, $tmpdir );

            if ( is_wp_error( $unzip ) ) {
                WPLA()->logger->error( 'Error unzipping the file ('. $unzip->get_error_message() .')' );
                return false;
            } else {
                // copy the zip file to $tmpdir
                copy( $zip, $tmpdir . '/'. $item->OrderItemId .'.zip' );

                // search for the json file
                $json_file = current(glob( $tmpdir .'/*.json' ));

                if ( $json_file && file_exists( $json_file ) ) {
                    $json_str = file_get_contents( $json_file );
                    $json = $this->processCustomizationJson( $json_str, $tmpdir );
                } else {
                    WPLA()->logger->error( 'JSON file not found in '. $tmpdir .'/'. $item->OrderItemId .'.json' );
                    return false;
                }
            }

            @unlink( $zip );
        }

        return $json;
    }

    /**
     * Parses the JSON string and returns a usable $properties array
     * @param string $json
     * @param string $archive_dir
     * @return array
     */
    function processCustomizationJson( $json, $archive_dir = '' ) {
        $json = json_decode( $json, true );
        $properties     = array();
        $upload_dir     = wp_upload_dir();
        $basedir_name   = get_option('wpla_import_images_basedir_name', 'imported/');
        $images_dir     = $upload_dir['basedir'] .'/'. $basedir_name;
        $images_url     = $upload_dir['baseurl'] .'/'. $basedir_name;


        WPLA()->logger->info( 'Found JSON object: '. print_r( $json, 1 ) );

        if ( $json ) {
            if ( !empty( $json['customizationData'] ) ) {
                $properties = $this->flatten_customization_array( $this->parseCustomizationData( $json['customizationData'], $images_url, $images_dir, $archive_dir ) );
            } elseif ( !empty( $json['version3.0'] ) ) {
                // See if v3 data exists
                $surfaces = $json['version3.0']['customizationInfo']['surfaces'];

                foreach ( $surfaces as $sfc_group ) {
                    foreach ( $sfc_group['areas'] as $prop ) {
                        if ( $prop['customizationType'] == 'ImagePrinting' ) {
                            $value = '';
                            if ( !empty( $prop['svgImage'] ) && file_exists( $archive_dir .'/'. $prop['svgImage'] ) ) {
                                // copy the supplied image to the uploads directory
                                copy( $archive_dir .'/'. $prop['svgImage'], $images_dir .'/'. $prop['svgImage'] );
                                $value = trailingslashit( $images_url ) . $prop['svgImage'];
                            }

                            $properties[] = array(
                                'label' => $prop['name'],
                                'value' => $value,
                                'font'  => '',
                                'color' => '',
                            );
                        } elseif ( isset( $prop['label'] ) ) {
                            // we don't need to do another foreach
                            $color = '';
                            if ( !empty( $prop['colorName'] ) ) $color = $prop['colorName'];
                            if ( !empty( $prop['fill'] ) ) $color .= ' ('. $prop['fill'] .')';
                            $properties[] = array(
                                'label' => $prop['label'],
                                'value' => !empty( $prop['optionValue'] ) ? $prop['optionValue'] : $prop['text'],
                                'font'  => @$prop['fontFamily'],
                                'color' => $color,
                            );
                        } else {
                            $props = $prop;
                            foreach ( $props as $prop ) {
                                $properties[] = array(
                                    'label' => $prop['label'],
                                    'value' => $prop['optionValue'],
                                    'font'  => @$prop['fontFamily'],
                                    'color' => @$prop['fill'],
                                );
                            }
                        }

                    }
                }
            } elseif ( !empty( $json['customizationInfo'] ) ) {
                $container = $json['customizationInfo'];

                if ( !empty( $container['aspects'] ) ) {
                    foreach ( $container['aspects'] as $aspect ) {
                        $property = array(
                            'label'     => $aspect['title'],
                            'value'     => !empty( $aspect['text'] ) ? $aspect['text']['value'] : '',
                            'font'      => !empty( $aspect['font'] ) ? $aspect['font']['value'] : '',
                            'color'     => !empty( $aspect['color'] ) ? $aspect['color']['value'] : ''
                        );
                        $properties[] = $property;
                    }
                } else {
                    // use the version3.0 data array
                    // start traversing the DEEP arrays of data
                    $surfaces = $container['version3.0']['surfaces'];
                    foreach ( $surfaces as $sfc_group ) {
                        foreach ( $sfc_group['areas'] as $prop ) {
                            if ( isset( $prop['label'] ) ) {
                                // we don't need to do another foreach
                                $properties[] = array(
                                    'label' => $prop['label'],
                                    'value' => isset( $prop['optionValue'] ) ? $prop['optionValue'] : $prop['text'],
                                    'font'  => @$prop['fontFamily'],
                                    'color' => @$prop['fill'],
                                );
                            } else {
                                $props = $prop;
                                foreach ( $props as $prop ) {
                                    $properties[] = array(
                                        'label' => $prop['label'],
                                        'value' => isset( $prop['optionValue'] ) ? $prop['optionValue'] : $prop['text'],
                                        'font'  => @$prop['fontFamily'],
                                        'color' => @$prop['fill'],
                                    );
                                }
                            }

                        }
                    }
                }
            }
        }

        // link to the zip file if it exists
        if ( file_exists( trailingslashit( $archive_dir ) . $json['orderItemId'] .'.zip' ) ) {
            copy( trailingslashit( $archive_dir ) . $json['orderItemId'] .'.zip', $images_dir .'/'. $json['orderItemId'] .'.zip' );
            $properties[] = array(
                'label' => 'Download Archive',
                'value' => trailingslashit( $images_url ) . $json['orderItemId'] .'.zip',
                'font'  => '',
                'color' => '',
            );
        }

        return $properties;
    }

    function parseCustomizationData( $element, $images_url, $images_dir, $archive_dir, $data = array() ) {
        if ( strstr( $element['type'], 'Container' ) !== false ) {
            // parse the children
            foreach ( $element['children'] as $child ) {
                $data[] = $this->parseCustomizationData( $child, $images_url, $images_dir, $archive_dir, $data );
            }

            return $data;
        }

        if ( $element['type'] == 'OptionCustomization' ) {
            $option_label = isset( $element['optionSelection']['label'] ) ? $element['optionSelection']['label'] : '';
            $data = array(
                'label' => $element['label'],
                'value' => $option_label,
                'meta' => array()
            );
        } elseif ( $element['type'] == 'TextCustomization' ) {
            $data = array(
                'label' => $element['label'],
                'value' => $element['inputValue']
            );
        } elseif ( $element['type'] == 'ImageCustomization' ) {
            $data = array(
                'label' => $element['label'],
                'value' => trailingslashit( $images_url ) . $element['image']['imageName']
            );
            copy( $archive_dir .'/'. $element['image']['imageName'], $images_dir .'/'. $element['image']['imageName'] );
        } elseif ( $element['type'] == 'FontCustomization' ) {
            $data = array(
                'label' => $element['label'],
                'value' => $element['fontSelection']['family']
            );
        } elseif ( $element['type'] == 'ColorCustomization' ) {
            $data = array(
                'label' => $element['label'],
                'value' => $element['colorSelection']['name'] .' ('. $element['colorSelection']['value'] .')'
            );
        }
        return $data;
    }

    function flatten_customization_array(array $array) {
        $return = array();
        $labels = array();
        $values = array();
        array_walk_recursive($array, function($a,$b) use (&$labels, &$values) {
            if ( $b == 'label' ) $labels[] = $a;
            if ( $b == 'value' ) $values[] = $a;
        });

        foreach ( $labels as $i => $label ) {
            $return[] = array(
                'label' => $label,
                'value' => $values[ $i ]
            );
            //$return[ $label ] = $values[ $i ];
        }
        return $return;
    }


	function processOrderLineItems( $items, $post_id, &$order ) {

		// WC 2.0 only
		if ( ! function_exists('woocommerce_add_order_item_meta') ) return;

		#echo "<pre>";print_r($items);echo"</pre>";die();

        if ( $items ) {
            foreach ( $items as $item ) {
                $this->createOrderLineItem( $item, $post_id, $order );
            }
        }

	} // processOrderLineItems()


	function getShippingTotal( $items, &$order, $record_discounts = false ) {
		$shipping_total = 0;

		if ( $items ) {
            foreach ( $items as $item ) {
	            if ( isset( $item->ShippingPrice ) ) {
                    $shipping_total += $item->ShippingPrice->Amount;
                }

				if ( isset( $item->ShippingDiscount ) ) {
	                // Record shipping discounts
	                $discount_amount = $item->ShippingDiscount->Amount;

					if ( $record_discounts ) {
						$this->recordOrderDiscount( $discount_amount, $order );
					}

	                if ( apply_filters( 'wpla_deduct_discounts_from_line_total', true ) ) {
		                // Reduce the line total so the taxes will be adjusted accordingly
		                $shipping_total -= $discount_amount;
	                }
                }
            }
        }
		
        // added for #49320
		return apply_filters( 'wpla_shipping_total', $shipping_total, $items );
	} // getShippingTotal()

	/**
	 * Records an order discount and returns the modified line total. The discount will be deducted from the line total
	 * if the hook `wpla_deduct_discounts_from_line_total` is enabled/set to true
	 *
	 * @param float $discount_amount
	 * @param float $line_total
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function recordOrderDiscount( $discount_amount, &$order ) {
		if ( get_option( 'wpla_record_discounts', 0 ) ) {
			WPLA()->logger->info( 'Recording ShippingDiscount' );
			WPLA()->logger->info( 'discount amount: '. $discount_amount );

			if ( $discount_amount ) {
				WPLA()->logger->info( '[tax] discount: '. $discount_amount );

				$order_discount = $order->get_discount_total();

				if ( ! $order_discount ) {
					$order_discount = 0;
				}

				$order_discount += $discount_amount;

				$order->set_discount_total( $order_discount );
			}
		}
	}

	/**
	 * addCustomer, adds a new WordPress user account
	 *
	 * @param unknown $customers_name
	 * @return $customers_id
	 */
	public function addCustomer( $user_email, $details ) {
		// skip if user_email exists
		if ( $user_id = email_exists( $user_email ) ) {
			// $this->show_message('Error: email already exists: '.$user_email, 1 );
			WPLA()->logger->info( "email already exists $user_email" );
			return $user_id;
		}

		// get user data
		$amazon_user_email  = $details->BuyerInfo->BuyerEmail;

		// get shipping address with first and last name
		$shipping_details = $details->ShippingAddress;
		@list( $shipping_firstname, $shipping_lastname ) = explode( " ", $shipping_details->Name, 2 );
		$user_firstname  = sanitize_user( $shipping_firstname, true );
		$user_lastname   = sanitize_user( $shipping_lastname, true );
		$user_fullname   = sanitize_user( $shipping_details->Name, true );

		// generate password
		$random_password = wp_generate_password( 12, false );

		// create wp_user
		$wp_user = array(
			'user_login' => $amazon_user_email,
			'user_email' => $user_email,
			'first_name' => $user_firstname,
			'last_name'  => $user_lastname,
			// 'user_registered' => date( 'Y-m-d H:i:s', strtotime($customer['customers_info_date_account_created']) ),
			'user_pass' => $random_password,
			'role'      => get_option( 'wpla_new_customer_role', 'customer' )
			);
		$user_id = wp_insert_user( $wp_user ) ;

		if ( is_wp_error($user_id)) {

			WPLA()->logger->error( 'error creating user '.$user_email.' - WP said: '.$user_id->get_error_message() );
			return false;

		} else {

			// add user meta
			update_user_meta( $user_id, '_amazon_user_email', 	$amazon_user_email );
			update_user_meta( $user_id, 'billing_email', 		$user_email );
			update_user_meta( $user_id, 'paying_customer', 		1 );

			// optional phone number
			if ($shipping_details->Phone == 'Invalid Request') $shipping_details->Phone = '';
			update_user_meta( $user_id, 'billing_phone', 		stripslashes( $shipping_details->Phone ));

			// if AddressLine1 is missing or empty, use AddressLine2 instead
			if ( empty( $shipping_details->AddressLine1 ) ) {
				$shipping_details->AddressLine1 = @$shipping_details->AddressLine2;
				$shipping_details->AddressLine2 = '';
			}

			// billing
			update_user_meta( $user_id, 'billing_first_name', 	$user_firstname );
			update_user_meta( $user_id, 'billing_last_name', 	$user_lastname );
			update_user_meta( $user_id, 'billing_company', 		stripslashes( @$shipping_details->CompanyName ) );
			update_user_meta( $user_id, 'billing_address_1', 	stripslashes( @$shipping_details->AddressLine1 ) );
			update_user_meta( $user_id, 'billing_address_2', 	stripslashes( @$shipping_details->AddressLine2 ) );
			update_user_meta( $user_id, 'billing_city', 		stripslashes( @$shipping_details->City ) );
			update_user_meta( $user_id, 'billing_postcode', 	stripslashes( @$shipping_details->PostalCode ) );
			update_user_meta( $user_id, 'billing_country', 		stripslashes( @$shipping_details->CountryCode ) );
			update_user_meta( $user_id, 'billing_state', 		stripslashes( WPLA_CountryHelper::get_state_two_letter_code( @$shipping_details->StateOrRegion ) ) );

			// shipping
			update_user_meta( $user_id, 'shipping_first_name', 	$user_firstname );
			update_user_meta( $user_id, 'shipping_last_name', 	$user_lastname );
			update_user_meta( $user_id, 'shipping_company', 	stripslashes( @$shipping_details->CompanyName ) );
			update_user_meta( $user_id, 'shipping_address_1', 	stripslashes( @$shipping_details->AddressLine1 ) );
			update_user_meta( $user_id, 'shipping_address_2', 	stripslashes( @$shipping_details->AddressLine2 ) );
			update_user_meta( $user_id, 'shipping_city', 		stripslashes( @$shipping_details->City ) );
			update_user_meta( $user_id, 'shipping_postcode', 	stripslashes( @$shipping_details->PostalCode ) );
			update_user_meta( $user_id, 'shipping_country', 	stripslashes( @$shipping_details->CountryCode ) );
			update_user_meta( $user_id, 'shipping_state', 		stripslashes( WPLA_CountryHelper::get_state_two_letter_code( @$shipping_details->StateOrRegion ) ) );

			WPLA()->logger->info( "added customer $user_id ".$user_email." ($amazon_user_email) " );

		}

		return $user_id;

	} // addCustomer()

	function disableEmailNotifications() {

		// prevent WooCommerce from sending out notification emails when updating order status
		if ( get_option( 'wpla_disable_new_order_emails', 1 ) )
			add_filter( 'woocommerce_email_enabled_new_order', '__return_false', 10, 2 );

		if ( get_option( 'wpla_disable_completed_order_emails', 1 ) )
			add_filter( 'woocommerce_email_enabled_customer_completed_order', '__return_false', 10, 2 );

		if ( get_option( 'wpla_disable_on_hold_order_emails', 1 ) )
            add_filter( 'woocommerce_email_enabled_customer_on_hold_order', '__return_false', 10, 2 );

		if ( get_option( 'wpla_disable_processing_order_emails', 1 ) )
			add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false', 10, 2 );

		if ( get_option( 'wpla_disable_new_account_emails', 1 ) )
			add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false', 10, 2 );

	}

	function format_decimal( $number ) {

		// wc_format_decimal() exists in WC 2.1+ only
		if ( function_exists('wc_format_decimal') )
			return wc_format_decimal( $number );

		$dp     = get_option( 'woocommerce_price_num_decimals' );
		$number = number_format( floatval( $number ), $dp, '.', '' );
		return $number;

	} // format_decimal()


    /**
     * Calculate the taxes based on the product's tax class and the order's shipping address
     *
     * @param float $product_price
     * @param int $product_id
     * @param int $quantity
     * @param int $order_id
     * @param object $item
     * @return array
     */
    public function getProductTax( $product_price, $product_id, $quantity, $order, $item ) {
        global $woocommerce;
		WPLA()->logger->info( "calling getProductTax( $product_price, $product_id, $quantity, {$order->get_id()} )" );

		$tax_mode = get_option( 'wpla_orders_tax_mode' );
		WPLA()->logger->info( 'Tax Mode: '. $tax_mode );

        $prices_include_tax = $this->getPricesIncludeTax();

		if ( ! $tax_mode ) {
            $line_total    = $product_price;
            $line_subtotal = $product_price;

            return array(
                'line_total'            => $line_total,
                'line_tax'              => 0,
                'line_subtotal'         => $line_subtotal,
                'line_subtotal_tax'     => 0,
                'line_tax_data'         => array(
                    'total' 	=> array(),
                    'subtotal' 	=> array(),
                ),
                'tax_rate_id'           => '',
            );
        } elseif ( $tax_mode == 'autodetect' ) {
            $this->loadCartClasses();

            $cart       = $woocommerce->cart;
            $product    = wc_get_product( $product_id );
            WPLA()->logger->debug( "getProductTax() cart object: ".print_r($cart,1) );

            if ( !$product || !is_object($product) ) {
                return array(
                    'line_total'            => $product_price * $quantity,
                    'line_tax'              => '0.0',
                    'line_subtotal'         => $product_price * $quantity,
                    'line_subtotal_tax'     => '0.0',
                    'line_tax_data'         => array('total' => array(), 'subtotal' => array())
                );
            }

            $tax_rates      = array();
            $shop_tax_rates = array();

            // set the shipping location to the order's shipping address
            // so WC can determine whether or not this zone is taxable
            $shipping_location = apply_filters( 'wpla_order_shipping_location', array(
                'country'   => wpla_get_order_meta( $order, 'shipping_country' ),
                'state'     => wpla_get_order_meta( $order, 'shipping_state' ),
                'postcode'  => wpla_get_order_meta( $order, 'shipping_postcode' ),
                'city'      => wpla_get_order_meta( $order, 'shipping_city' )
            ) );
            $billing_location = apply_filters( 'wpla_order_billing_location', array(
                'country'   => $order->get_billing_country(),
                'state'     => $order->get_billing_state(),
                'postcode'  => $order->get_billing_postcode(),
                'city'      => $order->get_billing_city()
            ) );


			$customer = new WC_Customer();
			$customer->set_shipping_location(
				$shipping_location['country'],
				$shipping_location['state'],
				$shipping_location['postcode'],
				$shipping_location['city']
			);
			$customer->set_billing_location(
				$billing_location['country'],
				$billing_location['state'],
				$billing_location['postcode'],
				$billing_location['city']
			);

            WPLA()->logger->debug( 'Customer shipping location set to '. print_r( $shipping_location, 1 ) );

            // prevent fatal error:
            // Call to a member function needs_shipping() on a non-object in woocommerce/includes/class-wc-customer.php line 333
            add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );

            /**
             * Defer tax calculation later (in processOrderVAT) after creating all line items
             */
            if ( is_callable( array( $order, 'calculate_taxes' ) ) ) {
                return array(
                    'line_total'            => $product_price * $quantity,
                    'line_tax'              => '0.0',
                    'line_subtotal'         => $product_price * $quantity,
                    'line_subtotal_tax'     => '0.0',
                    'line_tax_data'         => array('total' => array(), 'subtotal' => array())
                );
            }

            $line_price         = $product_price * $quantity;
            $line_subtotal      = 0;
            $line_subtotal_tax  = 0;
            $tax_class          = $product->get_tax_class( 'unfiltered' );

            // calculate subtotal
            if ( !$product->is_taxable() ) {

                WPLA()->logger->info( "getProductTax() step 1 - not taxable (mode 1)" );

                $line_subtotal = $line_price;

            } elseif ( $prices_include_tax == 'yes' ) {
                WPLA()->logger->info( 'Found tax class: '. $tax_class );

                // Get base tax rates
                if ( empty( $shop_tax_rates[ $tax_class ] ) ) {
                    $shop_tax_rates[ $tax_class ] = WC_Tax::get_base_tax_rates( $tax_class );
                }

                // Get item tax rates
                if ( empty( $tax_rates[ $tax_class ] ) ) {
                    $tax_rates[ $tax_class ] = WC_Tax::get_rates( $tax_class, $customer);
                }

                $base_tax_rates = $shop_tax_rates[ $tax_class ];
                $item_tax_rates = $tax_rates[ $tax_class ];

                /**
                 * ADJUST TAX - Calculations when base tax is not equal to the item tax
                 */
                if ( $item_tax_rates !== $base_tax_rates ) {

                    WPLA()->logger->info( "getProductTax() step 1 - prices include tax (mode 2a)" );

                    // Work out a new base price without the shop's base tax
                    $taxes                 = WC_Tax::calc_tax( $line_price, $base_tax_rates, true, true );

                    // Now we have a new item price (excluding TAX)
                    $line_subtotal         = $line_price - array_sum( $taxes );

                    // Now add modifed taxes
                    $tax_result            = WC_Tax::calc_tax( $line_subtotal, $item_tax_rates );
                    $line_subtotal_tax     = array_sum( $tax_result );

                    /**
                     * Regular tax calculation (customer inside base and the tax class is unmodified
                     */
                } else {

                    WPLA()->logger->info( "getProductTax() step 1 - prices include tax (mode 2b)" );

                    // Calc tax normally
                    $taxes                 = WC_Tax::calc_tax( $line_price, $item_tax_rates, true );
                    $line_subtotal_tax     = array_sum( $taxes );
                    $line_subtotal         = $line_price - array_sum( $taxes );

                }

                /**
                 * Prices exclude tax
                 *
                 * This calculation is simpler - work with the base, untaxed price.
                 */
            } else {

                WPLA()->logger->info( "getProductTax() step 1 - prices exclude tax (mode 3)" );

                // Get item tax rates
                if ( empty( $tax_rates[ $tax_class ] ) ) {
                    $tax_rates[ $tax_class ] = WC_Tax::get_rates( $tax_class, $customer );
                }

                $item_tax_rates        = $tax_rates[ $tax_class ];

                // Base tax for line before discount - we will store this in the order data
                $taxes                 = WC_Tax::calc_tax( $line_price, $item_tax_rates );
                $line_subtotal_tax     = array_sum( $taxes );
                $line_subtotal         = $line_price;
            }

            WPLA()->logger->info( "getProductTax() mid - line_subtotal    : $line_subtotal" );
            WPLA()->logger->info( "getProductTax() mid - line_subtotal_tax: $line_subtotal_tax" );

            // calculate line tax

            // Prices
            $base_price = $product_price;
            $line_price = $product_price * $quantity;

            // Tax data
            $taxes = array();
            $discounted_taxes = array();

            if ( !$product->is_taxable() ) {

                WPLA()->logger->info( "getProductTax() step 2 - not taxable (mode 1)" );

                // Discounted Price (price with any pre-tax discounts applied)
                $discounted_price      = $base_price;
                $line_subtotal_tax     = 0;
                $line_subtotal         = $line_price;
                $line_tax              = 0;
                $line_total            = wc_round_tax_total( $discounted_price * $quantity );

                /**
                 * Prices include tax
                 */
                // } elseif ( $cart->prices_include_tax ) { // this doesn't work - $cart is empty!
            } elseif ( $prices_include_tax == 'yes' ) {

                $base_tax_rates = $shop_tax_rates[ $tax_class ];
                $item_tax_rates = $tax_rates[ $tax_class ];

                /**
                 * ADJUST TAX - Calculations when base tax is not equal to the item tax
                 */
                if ( $item_tax_rates !== $base_tax_rates ) {

                    WPLA()->logger->info( "getProductTax() step 2 - prices include tax (mode 2a)" );

                    // Work out a new base price without the shop's base tax
                    $taxes             = WC_Tax::calc_tax( $line_price, $base_tax_rates, true, true );

                    // Now we have a new item price (excluding TAX)
                    $line_subtotal     = wc_round_tax_total( $line_price - array_sum( $taxes ) );

                    // Now add modifed taxes
                    $taxes             = WC_Tax::calc_tax( $line_subtotal, $item_tax_rates );
                    $line_subtotal_tax = array_sum( $taxes );

                    // Adjusted price (this is the price including the new tax rate)
                    $adjusted_price    = ( $line_subtotal + $line_subtotal_tax ) / $quantity;

                    // Apply discounts
                    $discounted_price  = $adjusted_price;
                    $discounted_taxes  = WC_Tax::calc_tax( $discounted_price * $quantity, $item_tax_rates, true );
                    $line_tax          = array_sum( $discounted_taxes );
                    $line_total        = ( $discounted_price * $quantity ) - $line_tax;

                    /**
                     * Regular tax calculation (customer inside base and the tax class is unmodified
                     */
                } else {

                    WPLA()->logger->info( "getProductTax() step 2 - prices include tax (mode 2b)" );

                    // Work out a new base price without the shop's base tax
                    $taxes             = WC_Tax::calc_tax( $line_price, $item_tax_rates, true );

                    // Now we have a new item price (excluding TAX)
                    $line_subtotal     = $line_price - array_sum( $taxes );
                    $line_subtotal_tax = array_sum( $taxes );

                    // Calc prices and tax (discounted)
                    $discounted_price = $base_price;
                    $discounted_taxes = WC_Tax::calc_tax( $discounted_price * $quantity, $item_tax_rates, true );
                    $line_tax         = array_sum( $discounted_taxes );
                    $line_total       = ( $discounted_price * $quantity ) - $line_tax;
                }

                /**
                 * Prices exclude tax
                 */
            } else {

                WPLA()->logger->info( "getProductTax() step 2 - prices exclude tax (mode 3)" );

                $item_tax_rates        = $tax_rates[ $tax_class ];

                // Work out a new base price without the shop's base tax
                $taxes                 = WC_Tax::calc_tax( $line_price, $item_tax_rates );

                // Now we have the item price (excluding TAX)
                $line_subtotal         = $line_price;
                $line_subtotal_tax     = array_sum( $taxes );

                // Now calc product rates
                $discounted_price      = $base_price;
                $discounted_taxes      = WC_Tax::calc_tax( $discounted_price * $quantity, $item_tax_rates );
                $discounted_tax_amount = array_sum( $discounted_taxes );
                $line_tax              = $discounted_tax_amount;
                $line_total            = $discounted_price * $quantity;
            }

            $tax_rate_id = '';

            foreach ( $item_tax_rates as $rate_id => $rate ) {
                $tax_rate_id = $rate_id;
                break;
            }

            WPLA()->logger->info( "getProductTax() end - line_subtotal    : $line_subtotal" );
            WPLA()->logger->info( "getProductTax() end - line_subtotal_tax: $line_subtotal_tax" );
            WPLA()->logger->info( "getProductTax() end - item_tax_rates   : ".print_r($item_tax_rates,1) );

            return array(
                'tax_rate_id'           => $tax_rate_id,
                'line_total'            => $line_total,
                'line_tax'              => $line_tax,
                'line_subtotal'         => $line_subtotal,
                'line_subtotal_tax'     => $line_subtotal_tax,
                'line_tax_data'         => array('total' => $discounted_taxes, 'subtotal' => $taxes )
            );
        } elseif ( $tax_mode == 'fixed' ) {
            $vat_percent = get_option( 'wpla_orders_fixed_vat_rate' );
            WPLA()->logger->info( 'VAT% (global): ' . $vat_percent );

            $vat_percent = floatval( $vat_percent );

            // convert single price to total price
            $product_price = $product_price * $quantity;

            // get global tax rate id for order item array
            $tax_rate_id = get_option( 'wpla_orders_tax_rate_id' );
            $vat_tax     = 0;

            // allow filters to change the rate ID and percentage
            $vat_percent = apply_filters( 'wpla_order_builder_fixed_vat_percent', $vat_percent, $product_price, $product_id, $quantity, $order, $item );
            $tax_rate_id = apply_filters( 'wpla_order_builder_fixed_tax_rate_id', $tax_rate_id, $product_price, $product_id, $quantity, $order, $item );

            if ( $tax_rate_id === 'autodetect' ) {
                $tax_rate_id = $this->getTaxRateId( $order, $product_id );
            }

            if ( $vat_percent ) {
                $vat_tax = $product_price / ( 1 + ( 1 / ( $vat_percent / 100 ) ) );	// calc VAT from gross amount
                $vat_tax = $this->format_decimal( $vat_tax );
            }

            // adjust item price if prices include tax (no, always subtract tax)
            // (apparently line item prices should always be stored without taxes!)
            // if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {
            $line_total    = $product_price - $vat_tax;
            $line_subtotal = $product_price - $vat_tax;
            // }

            return array(
                'line_total'            => $line_total,
                'line_tax'              => $vat_tax,
                'line_subtotal'         => $line_subtotal,
                'line_subtotal_tax'     => $vat_tax,
                'line_tax_data'         => array(
                    'total' 	=> array( $tax_rate_id => $vat_tax ),
                    'subtotal' 	=> array( $tax_rate_id => $vat_tax ),
                ),
                'tax_rate_id'           => $tax_rate_id,
            );
        } elseif ( $tax_mode == 'import' ) {
            $line_subtotal		= $item->ItemPrice->Amount;
            $line_total 		= $item->ItemPrice->Amount;
            $tax_rate_id        = get_option( 'wpla_orders_tax_rate_id' );
            $amazon_item_tax    = $item->ItemTax->Amount;

            if ( $tax_rate_id === 'autodetect' ) {
                $tax_rate_id = $this->getTaxRateId( $order, $product_id );
            }

            // Record shipping tax #20062
            // This is reported as an error in #31493
			if ( isset( $item->ShippingTax ) && $item->ShippingTax->Amount ) {
				// Commented out since 2.7.2 for unnecessarily adding shipping tax to the item tax #68016
				//$amazon_item_tax += $item->ShippingTax->Amount;
				$tax_rate_id = $this->getTaxRateId( $order, $product_id, 'shipping' );
				
                if ( isset( $this->shipping_taxes[ $tax_rate_id ] ) ) {
                    $this->shipping_taxes[ $tax_rate_id ] += $item->ShippingTax->Amount;
                } else {
                    $this->shipping_taxes[ $tax_rate_id ] = $item->ShippingTax->Amount;
                }
            }

            // Disabled due to causing double taxes #19699
            //if ( $amazon_item_tax ) {
            //    $this->vat_enabled = true;
            //    $this->vat_total   += $amazon_item_tax;
            //    @$this->vat_rates[ $tax_rate_id ] += $amazon_item_tax;
            //}

            // deduct taxes from the line totals #26116
            // only do this is prices_include_tax is YES #40398
            if ( $prices_include_tax == 'yes' ) {
                $line_total    = $line_total - $amazon_item_tax;
                $line_subtotal = $line_subtotal - $amazon_item_tax;
            }

			// Record promotional discounts
			if ( 1 == get_option( 'wpla_record_discounts', 0 ) ) {
				WPLA()->logger->info( 'Recording discounts' );
				if ( isset( $item->PromotionDiscount ) ) {
					$discount_amount = $item->PromotionDiscount->Amount;
					WPLA()->logger->info( 'discount amount: '. $discount_amount );

					if ( $discount_amount ) {
						WPLA()->logger->info( '[tax] discount: '. $discount_amount );

						if ( apply_filters( 'wpla_deduct_discounts_from_line_total', true ) ) {
							// Reduce the line total so the taxes will be adjusted accordingly #20833
							$line_total     -= $discount_amount;
							$line_subtotal  -= $discount_amount;
						}
					}
				}
			}

            return array(
                'line_total'            => $line_total,
                'line_tax'              => $amazon_item_tax,
                'line_subtotal'         => $line_subtotal,
                'line_subtotal_tax'     => $amazon_item_tax,
                'line_tax_data'         => array(
                    'total' 	=> array( $tax_rate_id => $amazon_item_tax ),
                    'subtotal' 	=> array( $tax_rate_id => $amazon_item_tax ),
                ),
                'tax_rate_id'           => $tax_rate_id,
            );
        }
    } // getProductTax()

    /**
     * Get all applicable taxes for the given order
     *
     * @deprecated
     * @param int $post_id
     * @return array
     */
    public function getOrderTaxes( $post_id ) {
		_doing_it_wrong( 'WPLA_OrderBuilder::getOrderTaxes', 'Method is deprecated and will be removed soon.', '2.7.4' );

		$order                  = wc_get_order( $post_id );
        $taxes                  = array();
        $shipping_taxes         = array();
        $order_item_tax_classes = array();
        $order_address          = self::getOrderTaxAddress( $order );

        WPLA()->logger->info( 'getOrderTaxes for #'. $post_id );

        $items = $order->get_items();
        $is_vat_exempt = $order->get_meta( '_is_vat_exempt', true );

        if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' && $is_vat_exempt != 'yes' ) {
            $line_total = $line_subtotal = array();

            foreach ( $items as $item_id => $item ) {
                // Prevent undefined warnings
                if ( ! isset( $item['line_tax'] ) ) {
                    $item['line_tax'] = array();
                }

                if ( ! isset( $item['line_subtotal_tax'] ) ) {
                    $item['line_subtotal_tax'] = array();
                }

                $item['order_taxes'] = array();
                $item_id                            = absint( $item_id );
                $line_total[ $item_id ]             = isset( $item['line_total'] ) ? wc_format_decimal( $item['line_total'] ) : 0;
                $line_subtotal[ $item_id ]          = isset( $item['line_subtotal'] ) ? wc_format_decimal( $item['line_subtotal'] ) : $line_total[ $item_id ];
                $order_item_tax_classes[ $item_id ] = isset( $item['order_item_tax_class'] ) ? sanitize_text_field( $item['order_item_tax_class'] ) : '';
                $product_id                         = $item['product_id'];

                // Get product details
                if ( get_post_type( $product_id ) == 'product' ) {
                    $_product        = wc_get_product( $product_id );
                    $item_tax_status = $_product->get_tax_status();
                } else {
                    $item_tax_status = 'taxable';
                }

                if ( '0' !== $order_item_tax_classes[ $item_id ] && 'taxable' === $item_tax_status ) {
                    $tax_rates = WC_Tax::find_rates( array(
                        'country'   => $order_address['country'],
                        'state'     => $order_address['state'],
                        'postcode'  => $order_address['postcode'],
                        'city'      => $order_address['city'],
                        'tax_class' => $order_item_tax_classes[ $item_id ]
                    ) );

                    $line_taxes          = WC_Tax::calc_tax( $line_total[ $item_id ], $tax_rates, false );
                    $line_subtotal_taxes = WC_Tax::calc_tax( $line_subtotal[ $item_id ], $tax_rates, false );

                    // Set the new line_tax
                    foreach ( $line_taxes as $_tax_id => $_tax_value ) {
                        $item['line_tax'][ $_tax_id ] = $_tax_value;
                    }

                    // Set the new line_subtotal_tax
                    foreach ( $line_subtotal_taxes as $_tax_id => $_tax_value ) {
                        $item['line_subtotal_tax'][ $_tax_id ] = $_tax_value;
                    }

                    // Sum the item taxes
                    foreach ( array_keys( $taxes + $line_taxes ) as $key ) {
                        $taxes[ $key ] = ( isset( $line_taxes[ $key ] ) ? $line_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
                    }
                }
            }

            return $taxes;
        }
    }

    public function getSalesTaxTotal( $order ) {
        WPLA()->logger->info( 'getSalesTaxTotal()' );
        $total = 0;

        foreach ( $order['items'] as $item ) {
            $amazon_collected = isset($item->TaxCollection->ResponsibleParty) && $item->TaxCollection->ResponsibleParty == 'Amazon Services, Inc.';
            if ( $amazon_collected || apply_filters( 'wpla_force_amazon_collected_taxes', false, $order ) ) {
                if ( isset( $item->ShippingTax ) ) {
                    $total += $item->ShippingTax->Amount;
                    WPLA()->logger->info( 'Found shipping tax: '. $item->ShippingTax->Amount );
                }

                $total += $item->ItemTax->Amount;
                WPLA()->logger->info( 'Found item tax: '. $item->ItemTax->Amount );
            }
        }

        WPLA()->logger->info( 'Total sales tax: '. $total );
        return $total;
    } // getSalesTaxTotal()

    /**
     * Include cart files because WC only preloads them when the request
     * is coming from the frontend
     */
    public function loadCartClasses() {
        global $woocommerce;

        if ( file_exists($woocommerce->plugin_path() .'/classes/class-wc-cart.php') ) {
            require_once $woocommerce->plugin_path() .'/classes/abstracts/abstract-wc-session.php';
            require_once $woocommerce->plugin_path() .'/classes/class-wc-session-handler.php';
            require_once $woocommerce->plugin_path() .'/classes/class-wc-cart.php';
            require_once $woocommerce->plugin_path() .'/classes/class-wc-checkout.php';
            require_once $woocommerce->plugin_path() .'/classes/class-wc-customer.php';
        } else {
            require_once $woocommerce->plugin_path() .'/includes/abstracts/abstract-wc-session.php';
            require_once $woocommerce->plugin_path() .'/includes/class-wc-session-handler.php';
            require_once $woocommerce->plugin_path() .'/includes/class-wc-cart.php';
            require_once $woocommerce->plugin_path() .'/includes/class-wc-checkout.php';
            require_once $woocommerce->plugin_path() .'/includes/class-wc-customer.php';
        }

        if (! $woocommerce->session ) {
            $woocommerce->session = new WC_Session_Handler();
            if ( is_callable( array( $woocommerce->session, 'init_session_cookie' ) ) ) {
                $woocommerce->session->init_session_cookie();
            }
        }

        if (! $woocommerce->customer ) {
            $woocommerce->customer = new WC_Customer();
        }
    } // loadCartClasses()

	private function getPricesIncludeTax() {
		$force_prices_include_tax = get_option('wpla_orders_force_prices_include_tax', 'ignore' );
		if ( $force_prices_include_tax != 'ignore' ) {
			$prices_include_tax = $force_prices_include_tax == 'force_yes' ? 'yes' : 'no';
		} else {
			// This filter allows 3rd-party code to override the Prices Include Tax setting in WooCommerce while calculating
			// for taxes in eBay order. This is because there are users with eBay stores where the prices already include taxes
			// yet their WC prices do not, which causes inaccurate prices and totals #32656
			// 'yes' or 'no' values ONLY
			$prices_include_tax = apply_filters( 'wpla_orderbuilder_prices_include_tax', get_option( 'woocommerce_prices_include_tax' ) );
		}

		return $prices_include_tax;
	}

    /**
     * Returns location of the order where the tax is based on (shipping/billing/shop)
     * @param int|WC_Order $order
     * @return array
     */
    public static function getOrderTaxAddress( $order ) {
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }

        $tax_based_on = get_option( 'woocommerce_tax_based_on' );

        switch( $tax_based_on ) {
            case 'shipping':
                $country    = wpla_get_order_meta( $order, 'shipping_country' );
                $state      = wpla_get_order_meta( $order, 'shipping_state' );
                $postcode   = wpla_get_order_meta( $order, 'shipping_postcode' );
                $city       = wpla_get_order_meta( $order, 'shipping_city' );
                break;

            case 'billing':
                $country    = wpla_get_order_meta( $order, 'billing_country' );
                $state      = wpla_get_order_meta( $order, 'billing_state' );
                $postcode   = wpla_get_order_meta( $order, 'billing_postcode' );
                $city       = wpla_get_order_meta( $order, 'billing_city' );
                break;

            default:
                $default  = wc_get_base_location();
                $country  = $default['country'];
                $state    = $default['state'];
                $postcode = '';
                $city     = '';
                break;
        }

        return array(
            'country'   => $country,
            'state'     => $state,
            'postcode'  => $postcode,
            'city'      => $city
        );
    }



    /**
     * Adds a 'tax' line item to the specified order
     *
     * @param int       $order_id
     * @param int       $tax_rate_id
     * @param float     $tax_amount
     * @param float     $shipping_tax_amount
     * @param WC_Order &$order
     * @return void
     */
    private function addOrderLineTax( $order_id, $tax_rate_id, $tax_amount = 0.0, $shipping_tax_amount = 0.0, &$order = null ) {
        global $wpdb;

        // get tax rate
        $tax_rate    = $wpdb->get_row( "SELECT tax_rate_id, tax_rate, tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_priority FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = '$tax_rate_id'" );
        $tax_rate_label = WC_Tax::get_rate_label( $tax_rate_id );
        $tax_rate_percent = WC_Tax::get_rate_percent( $tax_rate_id );
        WPLA()->logger->debug( '$tax_rate: '. print_r( $tax_rate, true ) );

        $code      = WC_Tax::get_rate_code( $tax_rate_id );
        $tax_code  = $code ? $code : __( 'VAT', 'wp-lister-for-amazon' );
        $tax_label = $tax_rate_id ? $tax_rate_label : WC()->countries->tax_or_vat();

        if ( $order ) {
            $line = new WC_Order_Item_Tax();
            $line->set_name( $tax_label );
            $line->set_compound( false );
            $line->set_tax_total( $this->format_decimal( $tax_amount ) );
            $line->set_shipping_tax_total( $this->format_decimal( $shipping_tax_amount ) );
            $line->set_rate_id( $tax_rate_id );
            $line->set_rate_percent( $tax_rate_percent );
            $line->set_label( $tax_label );

            $order->add_item( $line );
        } else {
            $item_id = wc_add_order_item( $order_id, array(
                'order_item_name' 		=> $tax_code,
                'order_item_type' 		=> 'tax'
            ) );
            WPLA()->logger->info( 'Added order tax item: '. $item_id );

            // Add line item meta
            if ( $item_id ) {
                wc_add_order_item_meta( $item_id, 'compound', 0 );
                wc_add_order_item_meta( $item_id, 'tax_amount', $this->format_decimal( $tax_amount ) );
                wc_add_order_item_meta( $item_id, 'shipping_tax_amount', $this->format_decimal( $shipping_tax_amount ) );

                wc_add_order_item_meta( $item_id, 'rate_id', $tax_rate_id );
                wc_add_order_item_meta( $item_id, 'rate_percent', $tax_rate_percent );
                wc_add_order_item_meta( $item_id, 'label', $tax_label );
            }
        }
    }

    /**
     * Detect the appropriate tax rate based on the order's billing and shipping address and the
     * product's tax class.
     *
     * @param WC_Order  $order
     * @param int       $product_id
     * @param string    $line_type product or shipping
     *
     * @return bool|int The Tax Rate ID if one is found. Otherwise, FALSE is returned.
     */
    private function getTaxRateId( $order, $product_id, $line_type = 'product' ) {
        global $woocommerce;

        $this->loadCartClasses();

        $product = wc_get_product( $product_id );

        // set the shipping location to the order's shipping address
        // so WC can determine whether or not this zone is taxable
        $shipping_location = apply_filters( 'wpla_order_shipping_location', array(
            'country'   => wpla_get_order_meta( $order, 'shipping_country' ),
            'state'     => wpla_get_order_meta( $order, 'shipping_state' ),
            'postcode'  => wpla_get_order_meta( $order, 'shipping_postcode' ),
            'city'      => wpla_get_order_meta( $order, 'shipping_city' )
        ) );
        $billing_location = apply_filters( 'wpla_order_billing_location', array(
            'country'   => $order->get_billing_country(),
            'state'     => $order->get_billing_state(),
            'postcode'  => $order->get_billing_postcode(),
            'city'      => $order->get_billing_city()
        ) );

		$customer = new WC_Customer();
        $customer->set_shipping_location(
            $shipping_location['country'],
            $shipping_location['state'],
            $shipping_location['postcode'],
            $shipping_location['city']
        );
        $customer->set_billing_location(
            $billing_location['country'],
            $billing_location['state'],
            $billing_location['postcode'],
            $billing_location['city']
        );
        WPLA()->logger->info( 'Customer shipping location set to '. print_r( $shipping_location, 1 ) );

        // prevent fatal error:
        // Call to a member function needs_shipping() on a non-object in woocommerce/includes/class-wc-customer.php line 333
        add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );

        $tax_class = '';

        if ( $product ) {
			$tax_class = $product->get_tax_class();
        }

		if ( $line_type == 'shipping' ) {
			$tax_rates = WC_Tax::get_shipping_tax_rates( $tax_class, $customer );
		} else {
			$tax_rates = WC_Tax::get_rates( $tax_class, $customer );
		}

        $tax_rate_id = false;
        if ( !empty( $tax_rates ) ) {
            foreach ( $tax_rates as $rate_id => $rate ) {
                $tax_rate_id = $rate_id;
                break;
            }
        }

        return $tax_rate_id;
    }

    /**
     *
     * @param WC_Order $order
     * @return bool
     */
    public function orderCanBeUpdated( $order ) {
        // do nothing if order is already marked as completed, refunded, cancelled or failed
        // if ( $order->status == 'completed' ) return $post_id;
        if ( in_array( $order->get_status(), apply_filters( 'wpla_order_builder_update_skip_statuses', array( 'completed', 'cancelled', 'refunded', 'failed' ) ) ) ) {
            return false;
        }

        if ( ! apply_filters( 'wpla_update_custom_order_status', false ) ) {
            // the above blacklist won't work for custom order statuses created by the WooCommerce Order Status Manager extension
            // a custom order status should be left untouched as it probably serves a custom purpose - so whitelist all values used by WP-Lister:
            if ( ! in_array( $order->get_status(), array( 'pending', 'processing', 'on-hold', 'completed' ) ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $amazon_order_status
     * @return string The WC Order Status
     */
    public function mapAmazonOrderStatus( $amazon_order_status ) {

        switch ( $amazon_order_status ) {
            case 'Unshipped':
                // unshipped orders: use config
                $new_order_status = get_option( 'wpla_new_order_status', 'processing' );
                break;

            case 'Shipped':
                $new_order_status = get_option('wpla_shipped_order_status', 'completed');
                break;

            case 'Canceled':
                $new_order_status = get_option('wpla_cancelled_order_status', 'on-hold');
                break;

            default:
                $new_order_status = 'on-hold';
                break;

        }

        return $new_order_status;
    }


} // class WPLA_OrderBuilder
