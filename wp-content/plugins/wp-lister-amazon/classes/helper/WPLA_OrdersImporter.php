<?php

class WPLA_OrdersImporter {

	var $account;
	public $result;
	public $updated_count = 0;
	public $imported_count = 0;
	public $throttling_is_active = false;

	public WPLA_Amazon_SP_API $api;

	const TABLENAME = 'amazon_orders';


    /**
     * todo: break this into smaller methods
     * @param WPLab\Amazon\SellingPartnerApi\Model\OrdersV0\Order $order
     * @param WPLA_AmazonAccount $account
     * @return bool|int|string|void|null
     */
	public function importOrder( $order, $account ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		// skip processing if requests are throttled already
		if ( $this->throttling_is_active == true ) return false;

		// check if order exists in WPLA and is already up to date (TODO: optimize)
		// (LastUpdateDate apparently isn't updated when OrderStatus changes from Pending to Canceled - so we need to compare date and status!)
		if ( $id = $this->order_id_exists( $order->getAmazonOrderId() ) ) {
			$om = new WPLA_OrdersModel();
			$amazon_order = $om->getItem( $id );
			if ( $amazon_order['LastTimeModified'] == $this->convertIsoDateToSql( $order->getLastUpdateDate() ) &&
				 $amazon_order['status']           == $order->getOrderStatus() ) {
				WPLA()->logger->info('Order '.$order->getAmazonOrderId().' has not been modified since '.$amazon_order['LastTimeModified'].' and is up to date.');
				wpla_show_message(   'Order '.$order->getAmazonOrderId().' has not been modified since '.$amazon_order['LastTimeModified'].' and is up to date.');

				// if "Filter orders" is enabled, make sure the order is assigned to the right account_id
				if ( get_option( 'wpla_fetch_orders_filter', 0 ) == 1 ) {
					if ( $amazon_order['account_id'] != $account->id ) {

						// update account_id on existing order
						$data = array( 'account_id' => $account->id );
						$wpdb->update( $table, $data, array( 'order_id' => $order->getAmazonOrderId() ) );

						WPLA()->logger->info('Order '.$order->getAmazonOrderId().' was switched from account ID '.$amazon_order['account_id'].' to: '.$account->id);
						wpla_show_message(   'Order '.$order->getAmazonOrderId().' was switched from account ID '.$amazon_order['account_id'].' to: '.$account->id);
					}
				}

				return null;
			}
		}

        // If FBA is disabled, we should skip storing FBA orders #45843
        if ( $order->getFulfillmentChannel() == 'AFN' && !get_option( 'wpla_fba_enabled' ) && !apply_filters( 'wpla_force_create_fba_orders', true, $order ) ) {
            WPLA()->logger->info( 'Skipped importing FBA order #'. $order->getAmazonOrderId() .' because FBA is disabled.' );
            return;
        }

        $api = new WPLA_Amazon_SP_API( $account->id );

        /*
         * Only pull shipping address and buyer info on paid orders
         */
        if ( !in_array( $order->getOrderStatus(), [\WPLab\Amazon\SellingPartnerApi\Model\OrdersV0\Order::ORDER_STATUS_PENDING, \WPLab\Amazon\SellingPartnerApi\Model\OrdersV0\Order::ORDER_STATUS_CANCELED ] ) ) {
            $order_address  = $api->getOrderAddress( $order->getAmazonOrderId() );
            $buyer_info     = $api->getOrderBuyerInfo( $order->getAmazonOrderId() );

            if ( WPLA_Amazon_SP_API::isError( $order_address ) ) {
                WPLA()->logger->error( 'GetOrderAddress #'. $order->getAmazonOrderId() .' Error: '. print_r( $order_address, 1 ) );

                if ( $order_address->ErrorCode == 429 || $order_address->ErrorCode == 500 ) {
                    $this->throttling_is_active = true;
                    wpla_show_message('GetOrderAddress requests are throttled. Skipping further order processing until next run.','warn');
                    return false;
                }
            }

            if ( WPLA_Amazon_SP_API::isError( $buyer_info ) ) {
                WPLA()->logger->error( 'GetOrderBuyerInfo #'. $order->getAmazonOrderId() .' Error: '. print_r( $buyer_info, 1 ) );

                if ( $buyer_info->ErrorCode == 429 || $buyer_info->ErrorCode == 500 ) {
                    $this->throttling_is_active = true;
                    wpla_show_message('GetBuyerInfo requests are throttled. Skipping further order processing until next run.','warn');
                    return false;
                }
            }

            // with the isError checks above, we shouldn't reach this point with an stdClass $order_address
            if ( !is_callable( array( $order_address, 'getShippingAddress' ) ) ) {
	            $this->throttling_is_active = true;
                WPLA()->logger->error( 'Invalid data type for $order_address. Possibly throttled '.  print_r( $order_address, 1) );
                return false;
            }

            $order->setShippingAddress( $order_address->getShippingAddress() );
            $order->setBuyerInfo( $buyer_info );

        }

		$buyer_name = '';
		if ( $order->getShippingAddress() && method_exists( $order->getShippingAddress(), 'getName' ) ) {
			$buyer_name = $order->getShippingAddress()->getName();
		}

		$buyer_email = '';
		if ( $order->getBuyerInfo() && method_exists($order->getBuyerInfo(), 'getBuyerEmail' ) ) {
			$buyer_email = $order->getBuyerInfo()->getBuyerEmail();
		}

		$data = array(
			'order_id'             => $order->getAmazonOrderId(),
			'status'               => $order->getOrderStatus(),
			// pending orders are missing some details
			'total'                => $order->getOrderTotal() ? $order->getOrderTotal()->getAmount() : '',
			'currency'             => $order->getOrderTotal() ? $order->getOrderTotal()->getCurrencyCode() : '',
			'buyer_name'           => $buyer_name,
			'buyer_email'          => $buyer_email,
			'PaymentMethod'        => $order->getPaymentMethod()? $order->getPaymentMethod() : '',
			'ShippingAddress_City' => $order->getShippingAddress() ? $order->getShippingAddress()->getCity() : '',
			'date_created'         => $this->convertIsoDateToSql( $order->getPurchaseDate() ),
			'LastTimeModified'     => $this->convertIsoDateToSql( $order->getLastUpdateDate() ),
			'account_id'		   => $account->id,
			'details'			   => json_encode( $order )
		);

		// fetch order line items from Amazon - required for both new and updated orders
		$this->api     = new WPLA_Amazon_SP_API( $account->id );

		// Don't check and update line items when the order has already been shipped/completed
        // to prevent throttling from Amazon #16649
        $items = false;
        $update_items = true;

        // No need to update order items on shipped orders when Conditional Order Item Updates is enabled
        if ( get_option( 'wpla_conditional_order_item_updates' ) == 1 && $order->getOrderStatus() == 'Shipped' ) {
            $update_items = false;
        }

        if ( $update_items ) {
            $items         = $this->api->getOrderItems( $order->getAmazonOrderId(), true );

	        // check if ListOrderItems request is throttled
	        // if true, skip ALL further requests / order processing until next cron run
	        if ( is_object($items) && ( $items->ErrorCode == 429 || $items->ErrorCode == 400 ) ) {
		        $this->throttling_is_active = true;
		        wpla_show_message('GetOrderItems requests are throttled. Skipping further order processing until next run.','warn');
		        return false;
	        }

            $data['items'] = maybe_serialize( self::flattenOrderItem( $items ) );
        }

        // check if ListOrderItems request is throttled
        // if true, skip ALL further requests / order processing until next cron run
        if ( is_object($items) && ( $items->ErrorCode == 429 || $items->ErrorCode == 400 ) ) {
            $this->throttling_is_active = true;
            wpla_show_message('GetOrderItems requests are throttled. Skipping further order processing until next run.','warn');
            return false;
        }

		// check if order exists in WPLA
		if ( $id = $this->order_id_exists( $order->getAmazonOrderId() ) ) {

			// load existing order record from wp_amazon_orders
			$ordersModel        = new WPLA_OrdersModel();
			$wpla_order         = $ordersModel->getItem( $id );
			$wpla_order_updated = false;

			// check if order status was updated
			// if pending -> Canceled: revert stock reduction by processing history records
			// if pending -> Shipped / Unshipped: create WooCommerce order if enabled (done in createOrUpdateWooCommerceOrder())
			if ( $order->getOrderStatus() != $wpla_order['status'] ) {

				$old_order_status = $wpla_order['status'];
				$new_order_status = $order->getOrderStatus();

				// add history record
				$history_message = "Order status has changed from ".$old_order_status." to ".$new_order_status;
				$history_details = array( 'id' => $id, 'new_status' => $new_order_status, 'old_status' => $old_order_status, 'LastTimeModified' => $data['LastTimeModified'] );
				self::addHistory( $data['order_id'], 'order_status_changed', $history_message, $history_details );
				## BEGIN PRO ##
				if ( $new_order_status == 'Canceled' ) {
                    if ( ! get_option( 'wpla_revert_stock_changes', 1 ) ) {
                        // Tell WC not to replenish the stocks for this order #39760
                        $wc_order = wc_get_order($wpla_order['post_id']);

                        if ( $wc_order ) {
                            //$wc_order->set_order_stock_reduced(false);
	                        if ( method_exists( $wc_order, 'set_order_stock_reduced' ) ) {
		                        $wc_order->set_order_stock_reduced( false );
	                        } else {
		                        $wc_order->get_data_store()->set_stock_reduced( $wpla_order['post_id'], false );
	                        }
	                        $wc_order->save();

                            // add history record
                            $history_message = "Skipped reverting stock due to the Revert Stock Changes setting being OFF";
                            $history_details = array('id' => $id);
                            self::addHistory($data['order_id'], 'revert_stock', $history_message, $history_details);
                        }
                    } else {
                        // if Pending|Unshipped -> Canceled: revert stock reduction by processing history records
                        if ( $old_order_status == 'Pending' || $old_order_status == 'Unshipped' ) {

                            $wc_order = wc_get_order($wpla_order['post_id']);

                            // Only revert stocks if stock_reduced is TRUE for the order
                            // WC apparently restocks orders when transitioning from Processing to Pending so we need to check this #37884
                            if ( $wc_order ) {
	                            $stock_reduced = method_exists( $wc_order, 'get_order_stock_reduced' ) ? $wc_order->get_order_stock_reduced() : $wc_order->get_data_store()->get_stock_reduced( $wc_order );
                                if ( $stock_reduced ) {
	                                // Don't restock if the WC order has already been refunded or cancelled #38791
	                                if ( in_array( $wc_order->get_status(), array( 'refunded', 'cancelled' ) ) ) {
		                                $history_message = sprintf( "Skipped reverting stock on %s order", $wc_order->get_status() );
		                                $history_details = array( 'order_id' => $order['post_id'] );
		                                $this->addHistory( $data['order_id'], 'wc_order_status_changed', $history_message, $history_details );
	                                } else {
		                                // revert stock reduction
		                                $this->revertStockReduction($wpla_order);
	                                }

                                    // add history record
                                    $history_message = "Stock levels have been replenished";
                                    $history_details = array('id' => $id);
                                    self::addHistory($data['order_id'], 'revert_stock', $history_message, $history_details);


                                    // Ensure stock is not marked as "reduced" anymore. #36903
	                                if ( method_exists( $wc_order, 'set_order_stock_reduced' ) ) {
		                                $wc_order->set_order_stock_reduced( false );
	                                } else {
		                                $wc_order->get_data_store()->set_stock_reduced( $wpla_order['post_id'], false );
	                                }
									$wc_order->save();
                                }
                            } else {
                                // There are no WC orders for Pending Amazon orders so replenish the stocks here
                                // revert stock reduction
                                $this->revertStockReduction($wpla_order);

                                // add history record
                                $history_message = "Stock levels have been replenished";
                                $history_details = array('id' => $id);
                                self::addHistory($data['order_id'], 'revert_stock', $history_message, $history_details);
                            }

                        }
                    }
                }

                // Update WC Order's status based on the new amazon order status
                if ( !empty( $wpla_order['post_id'] ) ) {
				    // Save the amazon order first so WPLA could adjust the WC order's status accordingly #22305
                    $wpdb->update( $table, $data, array( 'order_id' => $order->getAmazonOrderId() ) );
                    $wpla_order_updated = true;

				    $wob = new WPLA_OrderBuilder();
                    $wob->updateOrderFromAmazonOrder( $id, $wpla_order['post_id'] );
                }
				## END PRO ##

			} // if status changed

			// update existing order
            if ( !$wpla_order_updated ) {
			    $wpdb->update( $table, $data, array( 'order_id' => $order->getAmazonOrderId() ) );
            }
			$this->updated_count++;

			// add history record
			$history_message = "Order details were updated - ".$data['LastTimeModified'];
			$history_details = array( 'id' => $id, 'status' => $data['status'], 'LastTimeModified' => $data['LastTimeModified'] );
			self::addHistory( $data['order_id'], 'order_updated', $history_message, $history_details );

		} else {

			// insert new order
			$wpdb->insert( $table, $data );
			$this->imported_count++;
			$id = $wpdb->insert_id;
			echo $wpdb->last_error;

			// add history record
			$history_message = "Order was added with status: ".$data['status'];
			$history_details = array( 'id' => $id, 'status' => $data['status'], 'LastTimeModified' => $data['LastTimeModified'] );
			self::addHistory( $data['order_id'], 'order_inserted', $history_message, $history_details );

			// process ordered items - unless order has been cancelled
			if ( $data['status'] != 'Canceled') {
				if ( $items ) {
				    foreach ($items as $item) {
                        // process each item and reduce stock level
                        $success = $this->processListingItem( $item, $order );
                    }
                }
			}

		} // if order does not exist


		## BEGIN PRO ##
		// create woocommerce order - if enabled
		if ( get_option( 'wpla_create_orders' ) ) {
			$this->createOrUpdateWooCommerceOrder( $id );
		}
		## END PRO ##

		return $id;
	} // importOrder()

	## BEGIN PRO ##
	// create or update WooCommerce order from wpla_order
	function createOrUpdateWooCommerceOrder( $id ) {

		// load updated order record from wp_amazon_orders
		$ordersModel = new WPLA_OrdersModel();
		$wpla_order  = $ordersModel->getItem( $id );

		// return if no order found or if WC order has already been created
		if ( ! $wpla_order ) return;
		// if ( ! empty( $wpla_order['post_id'] ) ) return;

		// check if order has been cancelled or is still pending - don't create WooCommerce order then
		if ( in_array( $wpla_order['status'], array( 'Canceled', 'Pending' ) ) ) {
			WPLA()->logger->info( 'skipped woo order creation - status is '.$wpla_order['status'].' - order id #'.$wpla_order['order_id'] );
            $history_message = "Not creating order in WooCommerce - status is ".$wpla_order['status']." - order id #".$wpla_order['order_id']; // don't add history row - or check first whether it was already added!
            self::addHistory( $wpla_order['order_id'], 'skipped_create_order', $history_message, array() );
			return;
		}

		// check if order line items have been downloaded successfully
		// prevents invalid orders being created if request throtteling should kick in
        // TODO: retry after a few minutes so order does not get skipped or lost
		if ( ! is_array( $wpla_order['items'] ) || empty( $wpla_order['items'] ) ) {
			WPLA()->logger->info( 'skipped woo order creation - order line items are invalid (request throttled?)' );
            $history_message = "Not creating order in WooCommerce - order line items are invalid (request throttled?)"; // don't add history row - or check first whether it was already added!
            self::addHistory( $wpla_order['order_id'], 'skipped_create_order', $history_message, array() );
			return;
		}

		// maybe skip orders containing only foreign items
		if ( get_option( 'wpla_skip_foreign_item_orders' ) ) {

			// check if order line items exists in WP-Lister
			$order_items     = maybe_unserialize( $wpla_order['items'] );
			$has_known_items = false;
			$listingsModel   = new WPLA_ListingsModel();
			foreach ( $order_items as $item ) {
			    if ( 'asin' == get_option( 'wpla_order_item_matching_mode', 'asin' ) ) {
                    $listing = $listingsModel->getItemByASIN( $item->ASIN, false );
                } else {
                    $listing = $listingsModel->getItemBySkuAndAccount( $item->SellerSKU, $wpla_order['account_id'], false ); // Check using SKU since it is what WPLA_OrderBuilder::createOrderLineItem() uses when creating orders #49866
                }

				if ( $listing ) $has_known_items = true;
			}

			// skip if order contains no known items
			if ( ! $has_known_items ) {
				$history_message = "Not creating order in WooCommerce - no known items found"; // don't add history row - or check first whether it was already added!
				self::addHistory( $wpla_order['order_id'], 'skipped_create_order', $history_message, array() );
                WPLA()->logger->info( 'No known items found, skipped creating order in WooCommerce - order id #'.$wpla_order['order_id'] );
				return;
			}

		}

		// check if WooCommerce order already exists
		if ( empty( $wpla_order['post_id'] ) ) {
            WPLA()->logger->info( 't#29762 createOrUpdateWoocommerceOrder for order #'. $wpla_order['order_id'] );
            //WPLA()->logger->debug( print_r( debug_backtrace(), 1 ) );

			// allow other code/plugins to decide whether an order in WooCommerce should be created
			$skip_create_order_reason = apply_filters('wpla_reason_for_not_creating_wc_order', false, $wpla_order );
			if ( $skip_create_order_reason ) {
				// add history record
				$history_message = "Order was not created: ".$skip_create_order_reason;
				$history_details = array( 'reason' => $skip_create_order_reason );
				self::addHistory( $wpla_order['order_id'], 'hook_skipped_order', $history_message, $history_details );
				return;
			}

			// create WooCommerce order
			$ob = new WPLA_OrderBuilder();
			$order_post_id = $ob->createWooOrderFromAmazonOrder( $id );

			if ( $order_post_id ) {
                // add history record
                $history_message = "Order #$order_post_id was created";
                $history_details = array( 'post_id' => $order_post_id, 'status' => $wpla_order['status'], 'user_id' => get_current_user_id() );
                self::addHistory( $wpla_order['order_id'], 'create_order', $history_message, $history_details );
            }


		} else {

			// update WooCommerce order
			$ob = new WPLA_OrderBuilder();
            $ob->updateOrderFromAmazonOrder( $id, $wpla_order['post_id'] );

		}

	} // createOrUpdateWooCommerceOrder()
	## END PRO ##

	// revert stock reduction by processing history records
	function revertStockReduction( $wpla_order ) {
		global $wpdb;

		if ( ! is_array( $wpla_order['history'] ) ) return;

		foreach ( $wpla_order['history'] as $history_record ) {

			// filter reduce_stock actions
			if ( $history_record->action != 'reduce_stock' ) continue;

			// make sure purchased qty was recorded (since 0.9.2.8)
			$details = $history_record->details;
			if ( ! isset( $details['qty_purchased'] ) ) continue;
			$quantity_purchased = $details['qty_purchased'];

			// handle non-FBA quantity
			if ( ! isset( $details['fba_quantity'] ) && isset( $details['sku'] ) ) {

				// get listing item
				$lm = new WPLA_ListingsModel();
				$listing = $lm->getItemBySKU( $details['sku'] );

				// update quantity for FBA orders
				$quantity      = $listing->quantity      + $quantity_purchased;
				$quantity_sold = $listing->quantity_sold - $quantity_purchased;

				$wpdb->update( $wpdb->prefix.'amazon_listings',
					array(
						'quantity'  => $quantity,
						'quantity_sold' => $quantity_sold
					),
					array( 'sku' => $details['sku'] )
				);

			}

			// handle FBA quantity
			if ( isset( $details['fba_quantity'] ) && isset( $details['sku'] ) ) {

				// get listing item
				$lm = new WPLA_ListingsModel();
				$listing = $lm->getItemBySKU( $details['sku'] );

				// update quantity for FBA orders
				$fba_quantity  = $listing->fba_quantity  + $quantity_purchased;
				$quantity_sold = $listing->quantity_sold - $quantity_purchased;

				$wpdb->update( $wpdb->prefix.'amazon_listings',
					array(
						'fba_quantity'  => $fba_quantity,
						'quantity_sold' => $quantity_sold
					),
					array( 'sku' => $details['sku'] )
				);

			}

            do_action( 'wpla_inventory_before_change', $details, $wpla_order);

            // handle WooCommerce quantity
            if ( isset( $details['product_id'] ) ) {

                // increase product stock
                $post_id = $details['product_id'];
                $newstock = WPLA_ProductWrapper::increaseStockBy( $post_id, $quantity_purchased, $wpla_order['order_id'] );
                WPLA()->logger->info( 'increased product stock for #'.$post_id.' by '.$quantity_purchased.' - new qty: '.$newstock );

                // notify WP-Lister for eBay (and other plugins)
                do_action( 'wpla_inventory_status_changed', $post_id );
                if ( isset($details['parent_id']) && $details['parent_id'] ) {
                    do_action( 'wpla_inventory_status_changed', $details['parent_id'] );
                }
            }

		} // each history record

	} // revertStockReduction()

    /**
     *  update listing sold quantity and status
     * @paramWPLab\Amazon\SellingPartnerApi\Model\OrdersV0\OrderItem $item
     * @paramWPLab\Amazon\SellingPartnerApi\Model\OrdersV0\Order $order
     * @return bool
     */
	function processListingItem( $item, $order ) {
		global $wpdb;

		// abort if item data is invalid
		if ( ! $item->getAsin() && ! $item->getQuantityOrdered() ) {
			$history_message = "Error fetching order line items - request throttled?";
			$history_details = array();
			self::addHistory( $order->getAmazonOrderId(), 'request_throttled', $history_message, $history_details );
			return false;
		}

		do_action( 'wpla_before_process_listing_item', $item, $order );

		$order_id           = $order->getAmazonOrderId();
		$asin               = $item->getAsin();
		$sku                = $item->getSellerSku();
		$quantity_purchased = $item->getQuantityOrdered();

		// get listing item
		$lm = new WPLA_ListingsModel();
		$listing = $lm->getItemBySKU( $sku );

		// skip if this listing does not exist in WP-Lister
		if ( ! $listing ) {
			$history_message = "Skipped unknown SKU {$sku} ({$asin})";
			$history_details = array( 'sku' => $sku, 'asin' => $asin );
			self::addHistory( $order_id, 'skipped_item', $history_message, $history_details );
			return true;
		}


		// handle FBA orders
		if ( $order->getFulfillmentChannel() == 'AFN' ) {
		    // Only process FBA stocks if FBA is enabled in the settings page #44252
		    if (! get_option( 'wpla_fba_enabled' ) ) {
		        return false;
            }
            // update quantity for FBA orders
            $fba_quantity  = $listing->fba_quantity  - $quantity_purchased;
            $quantity_sold = $listing->quantity_sold + $quantity_purchased;

            $wpdb->update( $wpdb->prefix.'amazon_listings',
                array(
                    'fba_quantity'  => $fba_quantity,
                    'quantity_sold' => $quantity_sold
                ),
                array( 'sku' => $sku )
            );

            // add history record
            $history_message = "FBA quantity reduced by $quantity_purchased for listing {$sku} ({$asin}) - FBA stock $fba_quantity ($quantity_sold sold)";
            $history_details = array( 'fba_quantity' => $fba_quantity, 'sku' => $sku, 'asin' => $asin, 'qty_purchased' => $quantity_purchased, 'listing_id' => $listing->id );
            self::addHistory( $order_id, 'reduce_stock', $history_message, $history_details );
		} else {

			// update quantity for non-FBA orders
			$quantity_total = $listing->quantity      - $quantity_purchased;
			$quantity_sold  = $listing->quantity_sold + $quantity_purchased;
			$wpdb->update( $wpdb->prefix.'amazon_listings',
				array(
					'quantity'      => $quantity_total,
					'quantity_sold' => $quantity_sold
				),
				array( 'sku' => $sku )
			);

			// add history record
			$history_message = "Quantity reduced by $quantity_purchased for listing {$sku} ({$asin}) - new stock: $quantity_total ($quantity_sold sold)";
			$history_details = array( 'newstock' => $quantity_total, 'sku' => $sku, 'asin' => $asin, 'qty_purchased' => $quantity_purchased, 'listing_id' => $listing->id );
			self::addHistory( $order_id, 'reduce_stock', $history_message, $history_details );

		}

		## BEGIN PRO ##
		// reduce product stock - if enabled
		if ( get_option( 'wpla_sync_inventory' ) == '1' ) {

			// skip if no post_id set (imported products which have not yet been created in WooCommerce)
			if ( ! $listing->post_id ) {

				// add history record
				$history_message = "No product found for SKU $sku (".$listing->status.")";
				$history_details = array( 'sku' => $sku, 'asin' => $asin, 'id' => $listing->id );
				self::addHistory( $order_id, 'skipped_product', $history_message, $history_details );

				return false;
			}

			if ( apply_filters( 'wpla_skip_quantity_sync', false, $listing, $sku, $asin, $order ) ) {
                $history_message = 'Skipped product sync because of wpla_skip_quantity_sync';
                $history_details = array( 'sku' => $sku, 'asin' => $asin, 'id' => $listing->id );
			    self::addHistory( $order_id,'skipped_product', $history_message, $history_details );
			    return false;
            }

			// reduce product stock
			// $post_id = $wpdb->get_var( 'SELECT post_id FROM '.$wpdb->prefix.'amazon_listings WHERE asin = '.$asin );
			$post_id   = $listing->post_id;
			$parent_id = $listing->parent_id;
			$newstock = WPLA_ProductWrapper::decreaseStockBy( $post_id, $quantity_purchased, $order_id );
			WPLA()->logger->info( 'reduced product stock for #'.$post_id.' by '.$quantity_purchased.' - new qty: '.$newstock );

			// notify WP-Lister for eBay (and other plugins)
			do_action( 'wpla_inventory_status_changed', $post_id );
			if ( $parent_id ) do_action( 'wpla_inventory_status_changed', $parent_id ); // trigger stock update for parent variation as well

            // update other listings with the same post/parent ID of the stock update (using $skip_updating_feeds = true to improve performance)
            // (only if there are more than one account)
            if ( sizeof( WPLA()->accounts ) > 1 ) {
            	do_action( 'wpla_product_has_changed', $post_id, true );
            	if ( $parent_id ) do_action( 'wpla_product_has_changed', $parent_id, true );
            }

			// add history record
			$history_message = "Stock reduced by $quantity_purchased for product {$sku} (#$post_id) - new stock is $newstock";
			$history_details = array( 'product_id' => $post_id, 'parent_id' => $parent_id, 'newstock' => $newstock, 'qty_purchased' => $quantity_purchased );
			self::addHistory( $order_id, 'reduce_stock', $history_message, $history_details );

		} else {
            // add history record - sync sales is disabled
            $history_message = "Synchronize sales is disabled - stock will not be reduced";
            self::addHistory( $order_id, 'inventory_sync_off', $history_message, array() );
        }
		## END PRO ##

		return true;
	} // processListingItem()



	// add order history entry
	static function addHistory( $order_id, $action, $msg, $details = array(), $success = true ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLENAME;

		// build history record
		$record = new stdClass();
		$record->action  = $action;
		$record->msg     = $msg;
		$record->details = $details;
		$record->success = $success;
		$record->time    = time();

		// load history
		$history = $wpdb->get_var( $wpdb->prepare("
			SELECT history
			FROM $table
			WHERE order_id = %s
		", $order_id) );

		// init with empty array
		$history = maybe_unserialize( $history );
		if ( ! $history ) $history = array();

		// prevent fatal error if $history is not an array
		if ( ! is_array( $history ) ) {
			WPLA()->logger->error( "invalid history value in OrdersImporter::addHistory(): ".$history);

			// build history record
			$rec = new stdClass();
			$rec->action  = 'reset_history';
			$rec->msg     = 'Corrupted history data was cleared';
			$rec->details = array();
			$rec->success = 'ERROR';
			$rec->time    = time();

			$history = array();
			$history[] = $record;
		}

		// add record
		$history[] = $record;

		// update history
		$history = serialize( $history );
		$wpdb->query( $wpdb->prepare("
			UPDATE $table
			SET history = %s
			WHERE order_id = %s
		", $history, $order_id) );

	}


	/*
	// decrease stock quantity for WooCommerce product
	static function decreaseStockBy( $post_id, $by, $VariationSpecifics = array(), $order_id = false ) {

		if ( count( $VariationSpecifics ) == 0 ) {
			$product = self::getProduct( $post_id );
		} else {
			$variation_id = self::findVariationID( $post_id, $VariationSpecifics );
			$product = self::getProduct( $variation_id, true );

			// add history record
			if ( $order_id ) {
				$om = new WPLA_OrdersModel();
				// $history_message = "Stock reduced by $by for variation #$variation_id";
				// $history_details = array( 'variation_id' => $variation_id );
				// $om->addHistory( $order_id, 'reduce_stock', $history_message, $history_details );
			}

		}
		if ( ! $product ) return false;

		// patch backorders product config unless backorders were enabled in settings
		if ( $product->backorders_allowed() ) {
			if ( get_option( 'wpla_allow_backorders', 0 ) == 1 ) {
				$product->backorders = 'no';
			} elseif ( $order_id ) {
				$om = new WPLA_OrdersModel();
				// $history_message = "Warning: backorders are enabled for product #$post_id";
				// $history_details = array( 'post_id' => $post_id );
				// $om->addHistory( $order_id, 'backorders_allowed', $history_message, $history_details );
			}
		}

		// check if stock management is enabled for product
		if ( $product->managing_stock() ) {
			// if yes, call reduce_stock()
			$stock = $product->reduce_stock( $by );
		}

		// // check if stock management is enabled for product
		// if ( ! $product->managing_stock() && ! $product->backorders_allowed() ) {
		// 	// if not, just mark it as out of stock
		// 	update_post_meta($product->id, '_stock_status', 'outofstock');
		// 	$stock = 0;
		// } else {
		// 	// if yes, call reduce_stock()
		// 	$stock = $product->reduce_stock( $by );
		// }

		return $stock;
	}
	*/

    /**
     * @paramWPLab\Amazon\SellingPartnerApi\Model\OrdersV0\Order[] $orders
     * @param $account
     */
	public function importOrders( $orders, $account ) {

		// $this->api     = new WPLA_AmazonAPI( $account->id );
		// $this->account = $account;

        // regard ignore_orders_before_ts timestamp if set
        $orders_before_ts = false;
        if ( $ts = get_option('wpla_ignore_orders_before_ts') ) {
            WPLA()->logger->info( "getDateOfFirstOrder() - using ignore_orders_before_ts: $ts (raw)");
            $orders_before_ts = strtotime( $ts );
        }

		$number_of_orders = 0;
		foreach ( $orders as $order ) {
		    // Check ignore_orders_before against PurchaseDate instead of LastUpdateDate #54103
            //if ( $orders_before_ts && strtotime($order->LastUpdateDate) < $orders_before_ts ) {
            if ( $orders_before_ts && strtotime($order->getPurchaseDate()) < $orders_before_ts ) {
                WPLA()->logger->info( 'Skipping old order #'. $order->getAmazonOrderId() .' because of ignore_orders_before_ts' );
                continue;
            }

			if ( $this->importOrder( $order, $account ) ) {
				$number_of_orders++;
			}

			if ( $this->throttling_is_active ) {
				// throttling detected, stop all further processing
				break;
			}
		}

		// Disabled for now as it is generating a large number of reports that gets throttled
		//if ( $number_of_orders ) {
		//	WPLA_AmazonReport::createVatInvoiceDataReport();
		//}

	}

    /**
     * @param \WPLab\Amazon\SellingPartnerApi\Model\OrdersV0\OrderItem[] $items
     * @param string $order_id
     */
	public function importOrderItems( $items, $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		// echo "<pre>";print_r($order_id);echo"</pre>";#die();
		// echo "<pre>";print_r($items);echo"</pre>";#die();

		$data = array(
			'items'			   => maybe_serialize( $items )
		);

		$wpdb->update( $table, $data, array( 'order_id' => $order_id ) );
		echo $wpdb->last_error;
	}

	function order_id_exists( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$id = $wpdb->get_var( $wpdb->prepare("
			SELECT id
			FROM $table
			WHERE order_id = %s
		", $order_id) );

		return $id;
	}

	// convert 2013-02-14T08:00:58.000Z to 2013-02-14 08:00:58
	public function convertIsoDateToSql( $iso_date ) {
		$search = array( 'T', '.000Z', 'Z' );
		$replace = array( ' ', '' );
		$sql_date = str_replace( $search, $replace, $iso_date );
		return $sql_date;
	}

    /**
     * @paramWPLab\Amazon\SellingPartnerApi\Model\OrdersV0\OrderItem[] $items
     */
	public static function flattenOrderItem( $items ) {
	    $data_array = [];

	    foreach ( $items as $item ) {
	        $data       = new stdClass();
            $getters    = $item::getters();
            $map        = $item::attributeMap();

            foreach ( $getters as $attr => $method ) {
                $key = $map[ $attr ];

                if ( $attr == 'product_info' ) {
                    $field = new stdClass();
                    $field->NumberOfItems = $item->getProductInfo()->getNumberOfItems();
                    $data->$key = $field;
                } elseif ( $attr == 'item_price' ) {
                    $field = new stdClass();
                    $field->Amount = ($item->getItemPrice()) ? $item->getItemPrice()->getAmount() : '';
                    $field->CurrencyCode = ($item->getItemPrice()) ? $item->getItemPrice()->getCurrencyCode() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'promotion_discount' && $item->getPromotionDiscount() ) {
                    $field = new stdClass();
                    $field->Amount = ($item->getPromotionDiscount()) ? $item->getPromotionDiscount()->getAmount() : '';
                    $field->CurrencyCode = ($item->getPromotionDiscount()) ? $item->getPromotionDiscount()->getCurrencyCode() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'item_tax' && $item->getItemTax() ) {
                    $field = new stdClass();
                    $field->Amount = ($item->getItemTax()) ? $item->getItemTax()->getAmount() : '';
                    $field->CurrencyCode = ($item->getItemTax()) ? $item->getItemTax()->getCurrencyCode() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'shipping_price' && $item->getShippingPrice() ) {
                    $field = new stdClass();
                    $field->Amount = ($item->getShippingPrice()) ? $item->getShippingPrice()->getAmount() : '';
                    $field->CurrencyCode = ($item->getShippingPrice()) ? $item->getShippingPrice()->getCurrencyCode() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'shipping_discount' && $item->getShippingDiscount() ) {
                    $field = new stdClass();
                    $field->Amount = ($item->getShippingDiscount()) ? $item->getShippingDiscount()->getAmount() : '';
                    $field->CurrencyCode = ($item->getShippingDiscount()) ? $item->getShippingDiscount()->getCurrencyCode() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'shipping_tax' && $item->getShippingTax() ) {
                    $field = new stdClass();
                    $field->Amount = ($item->getShippingTax()) ? $item->getShippingTax()->getAmount() : '';
                    $field->CurrencyCode = ($item->getShippingTax()) ? $item->getShippingTax()->getCurrencyCode() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'promotion_discount_tax' && $item->getPromotionDiscountTax() ) {
                    $field = new stdClass();
                    $field->Amount = ($item->getPromotionDiscountTax()) ? $item->getPromotionDiscountTax()->getAmount() : '';
                    $field->CurrencyCode = ($item->getPromotionDiscountTax()) ? $item->getPromotionDiscountTax()->getCurrencyCode() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'buyer_requested_cancel' && $item->getBuyerRequestedCancel() ) {
                    $field = new stdClass();
                    $field->IsBuyerRequestedCancel = ($item->getBuyerRequestedCancel()) ? $item->getBuyerRequestedCancel()->getIsBuyerRequestedCancel() : '';
                    $field->BuyerCancelReason = ($item->getBuyerRequestedCancel()) ? $item->getBuyerRequestedCancel()->getBuyerCancelReason() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'tax_collection' && $item->getTaxCollection() ) {
                    $field = new stdClass();
                    $field->ResponsibleParty = ($item->getTaxCollection()) ? $item->getTaxCollection()->getResponsibleParty() : '';
                    $field->Model = ($item->getTaxCollection()) ? $item->getTaxCollection()->getModel() : '';
                    $data->$key = $field;
                } elseif ( $attr == 'buyer_info' && $item->getBuyerInfo() ) {
                    $field = new stdClass();
                    $field->GiftMessageText = ($item->getBuyerInfo()) ? $item->getBuyerInfo()->getGiftMessageText() : '';
                    $field->GiftWrapLevel = ($item->getBuyerInfo()) ? $item->getBuyerInfo()->getGiftWrapLevel() : '';
                    $field->GiftWrapPrice = new stdClass();
                    $field->GiftWrapTax = new stdClass();
                    $field->GiftWrapPrice->Amount = ($item->getBuyerInfo() && $item->getBuyerInfo()->getGiftWrapPrice() ) ? $item->getBuyerInfo()->getGiftWrapPrice()->getAmount() : '';
                    $field->GiftWrapTax->Amount = ($item->getBuyerInfo() && $item->getBuyerInfo()->getGiftWrapTax() ) ? $item->getBuyerInfo()->getGiftWrapTax()->getAmount() : '';

                    if ( $item->getBuyerInfo() && $item->getBuyerInfo()->getBuyerCustomizedInfo() ) {
                        $field->CustomizedURL = $item->getBuyerInfo()->getBuyerCustomizedInfo()->getCustomizedUrl();
                    }

                    //$field->BuyerCustomizedInfo = ($item->getBuyerInfo()) ? $item->getBuyerInfo()->getBuyerCustomizedInfo() : '';
                    $data->$key = $field;
                } else {
                    $data->$key = call_user_func( array( $item, $method ) );
                }
            }

            $data_array[] = $data;
        }


	    return $data_array;
    }

}

