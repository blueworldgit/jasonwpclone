<?php
/**
 * WPLA_CronActions
 *
 * This class contains action hooks that are usually trigger via wp_cron()
 *
 */

class WPLA_CronActions {

	var $dblogger;
	var $lockfile;
	var $message;

	public function __construct() {

		// add main cron handler
		add_action('wpla_update_schedule', 		array( &$this, 'cron_update_schedule' ) );
		add_action('wpla_daily_schedule', 		array( &$this, 'cron_daily_schedule' ) );
		add_action('wpla_fba_report_schedule', 	array( &$this, 'cron_fba_report_schedule' ) );

 		// handle external cron calls
		add_action('wp_ajax_wplister_run_scheduled_tasks', 			array( &$this, 'cron_update_schedule' ), 20 ); // wplister_run_scheduled_tasks
		add_action('wp_ajax_nopriv_wplister_run_scheduled_tasks', 	array( &$this, 'cron_update_schedule' ), 20 );
		add_action('wp_ajax_wpla_run_scheduled_tasks', 				array( &$this, 'cron_update_schedule' ), 20 ); // wpla_run_scheduled_tasks
		add_action('wp_ajax_nopriv_wpla_run_scheduled_tasks', 		array( &$this, 'cron_update_schedule' ), 20 );
		add_action('wp_ajax_wpla_request_inventory_report', 		array( &$this, 'request_daily_inventory_report' ) ); // wpla_request_inventory_report
		add_action('wp_ajax_nopriv_wpla_request_inventory_report', 	array( &$this, 'request_daily_inventory_report' ) );

		// add internal action hooks
		add_action('wpla_update_orders', 						array( &$this, 'action_update_orders' ) );
		add_action('wpla_update_reports', 						array( &$this, 'action_update_reports' ) );
		add_action('wpla_update_feeds', 						array( &$this, 'action_update_feeds' ) );
		add_action('wpla_submit_pending_feeds', 				array( &$this, 'action_submit_pending_feeds' ) );
		add_action('wpla_update_pricing_info',  				array( &$this, 'action_update_pricing_info' ) );
		add_action('wpla_update_missing_asins', 				array( &$this, 'action_update_missing_asins' ) );
		add_action('wpla_update_submitted_listings', 			array( &$this, 'action_update_submitted_listings' ) );
		add_action('wpla_clean_log_table', 						array( &$this, 'action_clean_log_table' ) );
		add_action('wpla_clean_tables', 						array( &$this, 'action_clean_tables' ) );
		add_action('wpla_reprice_products', 					array( &$this, 'action_reprice_products' ) );
		add_action('wpla_autosubmit_fba_orders', 				array( &$this, 'action_autosubmit_fba_orders' ) );
		add_action('wpla_request_daily_quality_report', 		array( &$this, 'request_daily_quality_report' ) );
		add_action('wpla_request_daily_fba_report', 			array( &$this, 'request_daily_fba_report' ) );
		add_action('wpla_request_daily_inventory_report', 		array( &$this, 'request_daily_inventory_report' ) );
		add_action('wpla_request_daily_order_report', 		    array( &$this, 'request_daily_order_report' ) );
		add_action('wpla_request_daily_fba_shipments_report', 	array( &$this, 'request_daily_fba_shipments_report' ) );

		add_action( WPLA_ListingsModel::PUBLISH_QUEUE_CRON,        array( $this, 'process_publish_queue_runner' ) );

		## BEGIN PRO ##
		// Background Inventory Check
        add_action( 'admin_init', array( $this, 'set_inventory_check_cron_schedule' ) );
        add_action( 'wpla_bg_inventory_check', array( $this, 'cron_bg_inventory_check' ) );
        add_action( 'wpla_bg_inventory_check_run', array( $this, 'cron_bg_inventory_check_run' ) );
        ## END PRO ##

		// add custom cron schedules
		add_filter( 'cron_schedules', array( &$this, 'cron_add_custom_schedules' ) );

		## BEGIN PRO ##
		// listen to checkout event
		add_action('woocommerce_reduce_order_stock', array( &$this, 'handle_reduce_order_stock'), 5, 1 );
		## END PRO ##

	}

	// run update schedule - called by wp_cron if activated
	public function cron_update_schedule() {
        WPLA()->logger->info("*** WP-CRON: cron_update_schedule()");

        // log cron run to db
		if ( get_option('wpla_log_to_db') == '1' ) {
			$dblogger = new WPLA_AmazonLogger();
			$dblogger->updateLog( array(
				'callname'    => 'cron_job_triggered',
				'request_url' => 'internal action hook',
				'request'     => maybe_serialize( $_REQUEST ),
				'response'    => 'last run: '.human_time_diff( get_option('wpla_cron_last_run') ).' ago',
				'success'     => 'Success'
	        ));
		}

        // check if this is a staging site
        if ( WPLA_Setup::isStagingSite() ) {
	        WPLA()->logger->info("WP-CRON: staging site detected! terminating execution...");

			update_option( 'wpla_cron_schedule', '' );
			update_option( 'wpla_create_orders', '' );

	        // remove scheduled event
		    $timestamp = wp_next_scheduled(  'wpla_update_schedule' );
	    	wp_unschedule_event( $timestamp, 'wpla_update_schedule' );

	        // remove scheduled event
		    $timestamp = wp_next_scheduled(  'wpla_fba_report_schedule' );
	    	wp_unschedule_event( $timestamp, 'wpla_fba_report_schedule' );

        	return;
        }

        // check if update is already running
        if ( ! $this->checkLock() ) {
	        WPLA()->logger->error("WP-CRON: already running! terminating execution...");
        	return;
        }

		// update reports - checks for submitted reports automatically now
		do_action('wpla_update_reports');

		// update feeds - checks for submitted feeds itself now
		do_action('wpla_update_feeds');

		// If Dedicated Orders Cron is enabled, wpla_update_orders will be called as an async task using ActionScheduler.
		if ( get_option( 'wpla_dedicated_orders_cron', 0 ) ) {
            // Create an async background task that checks for new orders
            wpla_enqueue_async_action( 'wpla_update_orders', [], 'WPLA' );
        } else {
            // update orders
            do_action('wpla_update_orders');
        }

		// submit pending feeds - after processing orders!
		do_action('wpla_submit_pending_feeds');

		// run full cron job not more often than every 5 minutes (270s = 4.5min / 30s buffer)
		$ts_last_full_run = get_option( 'wpla_cron_last_full_run', 0 );
		if ( $ts_last_full_run < time() - 270 ) {

	        // update pricing info
			do_action('wpla_update_pricing_info');

	        // update missing ASINs
			do_action('wpla_update_missing_asins');

	        // schedule background task to check submitted listings
			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action( 'wpla_update_submitted_listings', [], 'WPLA' );
			} else {
				as_schedule_single_action( time(), 'wpla_update_submitted_listings', [], 'WPLA' );
			}

			## BEGIN PRO ##
	        // run repricing tool - if enabled
			if ( get_option( 'wpla_enable_auto_repricing' ) )
				do_action('wpla_reprice_products');

	        // submit FBA orders - if enabled
			if ( get_option( 'wpla_fba_autosubmit_orders' ) )
				do_action('wpla_autosubmit_fba_orders');
			## END PRO ##

			// store timestamp
			update_option( 'wpla_cron_last_full_run', time() );

		}

		// If update interval is set to external cron, FBA Reports are only pulled every time the daily update schedule is run
        // Make sure we follow the Request FBA Reports schedule
        $fba_report_schedule = get_option( 'wpla_fba_report_schedule', 'daily' );
        $last_run            = get_option('wpla_fba_report_cron_last_run');
        $run_hours          = array( 'twelve_hours' => 12, 'six_hours' => 6, 'three_hours' => 3, 'daily' => 24 );
        $hours              = 24;
        if ( array_key_exists( $fba_report_schedule, $run_hours ) ) {
            $hours = $run_hours[ $fba_report_schedule ];
        }

        if ( $last_run < time() - ($hours * 3600) ) {
            do_action( 'wpla_fba_report_schedule' );
        }


		// check daily schedule - trigger now if not executed within 36 hours
        $last_run = get_option('wpla_daily_cron_last_run');
        if ( $last_run < time() - 36 * 3600 ) {
	        WPLA()->logger->warn('*** WP-CRON: Daily schedule has NOT run since '.human_time_diff( $last_run ).' ago');
			do_action( 'wpla_daily_schedule' );
			do_action( 'wpla_fba_report_schedule' ); // if the daily schedule didn't run, we can assume the FBA schedule didn't run either
        }

		// Check for listings left in the queue and try to publish them again
		WPLA_ListingsModel::maybeSchedulePublishingCron();

		// clean up
		$this->removeLock();

		// store timestamp
		update_option( 'wpla_cron_last_run', time() );

        WPLA()->logger->info("*** WP-CRON: cron_update_schedule() finished");
	}

	// run daily schedule - called by wp_cron
	public function cron_daily_schedule() {
        WPLA()->logger->info("*** WP-CRON: cron_daily_schedule()");

    	// check timestamp - do not run daily schedule more often than every 24 hours (except when triggered manually)
        $last_run = get_option('wpla_daily_cron_last_run');
        $manually = isset($_REQUEST['action']) && $_REQUEST['action'] == 'wpla_run_daily_schedule' ? true : false;
        if ( $last_run > time() - 24 * 3600 && ! $manually ) {
	        WPLA()->logger->warn('*** WP-CRON: cron_daily_schedule() EXIT - last run: '.human_time_diff( $last_run ).' ago');
	        return;
        }

		// request daily inventory report
		if ( get_option( 'wpla_autofetch_inventory_report' ) && ! WPLA_Setup::isStagingSite() )
			do_action('wpla_request_daily_inventory_report');

        // request daily order report
        if ( get_option( 'wpla_autofetch_order_report' ) && ! WPLA_Setup::isStagingSite() )
            do_action('wpla_request_daily_order_report');

		// request daily listing quality report
		if ( get_option( 'wpla_autofetch_listing_quality_feeds', 1 ) && ! WPLA_Setup::isStagingSite() )
			do_action('wpla_request_daily_quality_report');

		// clean tables
		do_action('wpla_clean_log_table');
		do_action('wpla_clean_tables');

		// store timestamp
		update_option( 'wpla_daily_cron_last_run', time() );

        WPLA()->logger->info("*** WP-CRON: cron_daily_schedule() finished");
        if ( $manually ) wpla_show_message('Daily maintenance schedule was executed successfully.');
	}

	// run FBA report schedule - called by wp_cron
	public function cron_fba_report_schedule() {
        WPLA()->logger->info("*** WP-CRON: cron_fba_report_schedule()");

    	// check timestamp - do not run FBA schedule more often than every 3 hours
        $last_run = get_option('wpla_fba_report_cron_last_run');
        if ( $last_run > time() -  3 * 3600 ) {
	        WPLA()->logger->warn('*** WP-CRON: cron_fba_report_schedule() EXIT - last run: '.human_time_diff( $last_run ).' ago');
	        return;
        }

		// request FBA shipments report
		if ( get_option( 'wpla_fba_enabled' ) && ! WPLA_Setup::isStagingSite() )
			do_action('wpla_request_daily_fba_shipments_report');

		// request daily FBA inventory report
		if ( get_option( 'wpla_fba_enabled' ) && ! WPLA_Setup::isStagingSite() )
			do_action('wpla_request_daily_fba_report');

		// store timestamp
		update_option( 'wpla_fba_report_cron_last_run', time() );

        WPLA()->logger->info("*** WP-CRON: cron_fba_report_schedule() finished");
	}


	// fetch missing ASINs - called by do_action()
	public function action_update_missing_asins() {
        WPLA()->logger->info("do_action: wpla_update_missing_asins");

		$accounts      = WPLA_AmazonAccount::getAll();
		$listingsModel = new WPLA_ListingsModel();
		$batch_size    = 10; // update 10 items at a time

		foreach ($accounts as $account ) {
            WPLA()->logger->info("Fetching listings for account #".$account->id);

			$account_id    = $account->id;
			$listings      = $listingsModel->getAllOnlineWithoutASIN( $account_id, 10, OBJECT_K );

			if ( empty($listings) ) {
                WPLA()->logger->info("No listings found for account");
			    continue;
            }

			// process one listing at a time (for now)
			foreach ( $listings as $listing ) {
				WPLA()->logger->info('fetching ASIN for SKU '.$listing->sku.' ('.$listing->id.') - type: '.$listing->product_type );

				$api        = new WPLA_Amazon_SP_API( $account->id );
				$result     = $api->searchCatalogItemsByIdentifier( $listing->sku , 'SKU' );

				if ( ! WPLA_Amazon_SP_API::isError( $result ) )  {
                    foreach( $result->getItems() as $product ) {
                        // update listing ASIN
                        $listingsModel->updateWhere( array( 'id' => $listing->id ), array( 'asin' => $product->getAsin() ) );
                        
                        // Sync ASIN to product meta field
                        update_post_meta( $listing->post_id, '_wpla_asin', $product->getAsin() );

                        WPLA()->logger->info('new ASIN for listing #'.$listing->id . ': '.$product->getAsin() );
                    }
				} elseif ( isset($result->Error) && $result->Error->Message ) {
					$errors  = sprintf( __( 'There was a problem fetching product details for %s.', 'wp-lister-for-amazon' ), $listing->sku ) .'<br>Error: '. $result->Error->Message;
					WPLA()->logger->error( $errors );
				} else {
					$errors  = sprintf( __( 'There was a problem fetching product details for %s.', 'wp-lister-for-amazon' ), $listing->sku );
					WPLA()->logger->error( $errors );
				}

			} // foreach listing

		} // each account

	} // action_update_missing_asins ()


	// check submitted listings for issues and status updates - called by Action Scheduler
	public function action_update_submitted_listings() {
		WPLA()->logger->info("do_action: wpla_update_submitted_listings (background task)");

		$accounts = WPLA_AmazonAccount::getAll();
		$listingsModel = new WPLA_ListingsModel();
		$total_checked = 0;
		$total_updated = 0;
		$total_skipped = 0;
		$api_calls_count = 0;
		$start_time = time();
		$max_execution_time = 45; // Stay under 45 seconds to allow buffer
		$batch_size = 50; // Process up to 50 listings per account
		$max_requests_per_run = 200; // Reasonable limit to avoid overwhelming the API

		foreach ($accounts as $account) {
			// Check if we're approaching time limit
			$elapsed_time = time() - $start_time;
			if ($elapsed_time >= $max_execution_time) {
				WPLA()->logger->info("Approaching time limit ({$elapsed_time}s), scheduling continuation task");
				// Schedule another task to continue processing
				if ( function_exists( 'as_enqueue_async_action' ) ) {
					as_enqueue_async_action( 'wpla_update_submitted_listings', [], 'WPLA' );
				}
				break;
			}

			WPLA()->logger->info("Checking submitted listings for account #".$account->id);

			// Get submitted listings for this account
			$submitted_listings = $listingsModel->getSubmittedListings($account->id, $batch_size);

			if (empty($submitted_listings)) {
				WPLA()->logger->info("No submitted listings found for account #".$account->id);
				continue;
			}

			WPLA()->logger->info("Found ".count($submitted_listings)." submitted listings for account #".$account->id);

			foreach ($submitted_listings as $listing) {
				// Check execution time before each API call
				$elapsed_time = time() - $start_time;
				if ($elapsed_time >= $max_execution_time || $api_calls_count >= $max_requests_per_run) {
					WPLA()->logger->info("Time/request limit reached ({$elapsed_time}s, {$api_calls_count} calls), scheduling continuation");
					// Schedule another task to continue processing
					if ( function_exists( 'as_enqueue_async_action' ) ) {
						as_enqueue_async_action( 'wpla_update_submitted_listings', [], 'WPLA' );
					}
					break 2; // Exit both loops
				}

				WPLA()->logger->info('Checking submitted listing: '.$listing['sku'].' (ID: '.$listing['id'].')');

				$result = $listingsModel->checkSubmittedListingStatus($listing);
				$total_checked++;
				
				// Handle different result types
				if ($result['success']) {
					if (isset($result['skipped']) && $result['skipped']) {
						// Rate limited - don't count as API call, will retry later
						$total_skipped++;
						WPLA()->logger->debug('Skipped listing '.$listing['sku'].': '.$result['message']);
						continue; // Skip rate limiting sleep since no API call was made
					} else {
						// Successful API call
						$api_calls_count++;
						// Check if status was actually updated (not just warnings saved)
						$updated_listing = $listingsModel->getItem($listing['id']);
						if ($updated_listing['status'] !== WPLA_ListingsModel::STATUS_SUBMITTED) {
							$total_updated++;
							WPLA()->logger->info('Status updated for listing '.$listing['sku'].': '.$result['message']);
						}
					}
				} else {
					// Failed API call
					$api_calls_count++;
					$total_updated++;
					WPLA()->logger->error('Failed to check listing '.$listing['sku'].': '.$result['message']);
				}

				// Rate limiting: 5 requests per second max, so sleep if we're going too fast
				if ($api_calls_count % 5 == 0) {
					$elapsed = time() - $start_time;
					if ($elapsed < ($api_calls_count / 5)) {
						$sleep_time = ($api_calls_count / 5) - $elapsed;
						WPLA()->logger->debug("Rate limiting: sleeping for {$sleep_time} seconds");
						sleep($sleep_time);
					}
				}
			}
		}

		$total_time = time() - $start_time;
		$avg_rate = $total_time > 0 ? round($api_calls_count / $total_time, 2) : 0;
		WPLA()->logger->info("Checked {$total_checked} submitted listings ({$total_skipped} skipped due to rate limiting), {$total_updated} status updates made in {$total_time}s (avg {$avg_rate} req/s)");
	} // action_update_submitted_listings()


	// fetch lowest prices - called by do_action()
	public function action_update_pricing_info() {
        WPLA()->logger->info("do_action: wpla_update_pricing_info");
        // WPLA()->logger->debug( print_r( debug_backtrace(), 1 ) );

		$accounts = WPLA_AmazonAccount::getAll();
		// $listingsModel = new WPLA_ListingsModel();
		$batch_size = 200; // 10 requests per batch (for now - maximum should be 20 requests = 400 items / max. 600 items per minute)

		foreach ($accounts as $account ) {

			$account_id    = $account->id;
			$listings      = WPLA_ListingQueryHelper::getItemsDueForPricingUpdateForAcccount( $account_id, $batch_size );
			$listing_ASINs = array();
			WPLA()->logger->info( sprintf( '%s items with outdated pricing info found for account %s.', sizeof($listings), $account->title ) );

			// build array of ASINs
        	foreach ($listings as $listing) {
        		// skip duplicate ASINs - they throw an error from Amazon
        		if ( in_array( $listing->asin, $listing_ASINs ) ) continue;

       			$listing_ASINs[] = $listing->asin;
        	}
        	if ( empty($listing_ASINs) ) continue;


        	// process smaller batches due to 0.5 requests/second rate limit
        	$batch_size = get_option( 'wpla_pricing_asin_batch_size', 10 ); // smaller batches
        	$total_batches = ceil( count($listing_ASINs) / $batch_size );
        	$successful_batches = 0;
        	$base_delay = get_option( 'wpla_pricing_batch_delay', 3 ); // minimum 3 seconds for 0.5/sec rate
        	
        	for ($page=0; $page < $total_batches; $page++) {
        		$page_size = $batch_size;

        		// splice ASINs
        		$offset = $page * $page_size;
        		$ASINs_for_this_batch = array_slice( $listing_ASINs, $offset, $page_size );
        		if ( empty($ASINs_for_this_batch) ) continue;

        		WPLA()->logger->info( 'Processing batch ' . ($page + 1) . '/' . $total_batches . ' (' . count($ASINs_for_this_batch) . ' ASINs)' );

        		// run update with error handling
	        	$result = $this->update_pricing_info_for_asins( $ASINs_for_this_batch, $account_id );
	        	
	        	if ( is_object($result) && isset($result->success) && !$result->success ) {
	        		// If we got an error (including 429), increase delay for next batch
	        		if ( isset($result->StatusCode) && $result->StatusCode == 429 ) {
	        			$delay = max( $base_delay * 4, 10 ); // Increase delay significantly after 429
	        			WPLA()->logger->info( 'Rate limiting detected, extending delay to ' . $delay . ' seconds' );
	        			
	        			// For large stores, consider breaking and resuming later
	        			if ( $total_batches > 20 && ($page + 1) < $total_batches ) {
	        				WPLA()->logger->info( 'Large store detected with rate limiting. Consider enabling background processing.' );
	        			}
	        		} else {
	        			$delay = $base_delay;
	        		}
	        	} else {
	        		$successful_batches++;
	        		$delay = $base_delay;
	        	}

				WPLA()->logger->info( 'Sleeping for ' . $delay . 's to prevent rate limiting' );
				sleep($delay);
        	}
        	
        	WPLA()->logger->info( sprintf( '%s/%s batches completed successfully for account %s.', $successful_batches, $total_batches, $account->title ) );


		} // each account

	} // action_update_pricing_info ()


	// fetch and process lowest price info for up to 20 ASINs
	public function update_pricing_info_for_asins( $listing_ASINs, $account_id ) {
		$listingsModel = new WPLA_ListingsModel();

    	// fetch Buy Box pricing info and process result
		$api = new WPLA_Amazon_SP_API( $account_id );
		$result = $api->getCompetitivePricing( $listing_ASINs );
        $listingsModel->processBuyBoxPricingResult( $result, $account_id );
        
        // Check for errors in competitive pricing
        if ( is_object($result) && isset($result->success) && !$result->success ) {
        	WPLA()->logger->warn( 'getCompetitivePricing failed: ' . $result->ErrorMessage );
        	if ( isset($result->StatusCode) && $result->StatusCode == 429 ) {
        		return $result; // Return error to trigger delay adjustment
        	}
        }

		// return if lowest offers are disabled
		// if ( ! get_option('wpla_repricing_use_lowest_offer') ) return;

    	// fetch Lowest Offer info and process result
		$api = new WPLA_Amazon_SP_API( $account_id );
        $result = $api->getItemOffers( $listing_ASINs );
        $listingsModel->processLowestOfferPricingResult( $result, $account_id );
        
        // Return the final result (could be error or success)
        return $result;

	} // update_pricing_info_for_asins ()


	// apply lowest prices - called by do_action()
	public function action_reprice_products() {
		## BEGIN PRO ##
        WPLA()->logger->info("do_action: wpla_reprice_products");
        // WPLA()->logger->info( print_r( debug_backtrace(), 1 ) );

        // reprice products
		$changed_product_ids = WPLA_RepricingHelper::repriceProducts();

		do_action( 'wpla_repriced_products', $changed_product_ids );

		WPLA()->logger->info( sprintf( '%s product prices have been updated.', sizeof($changed_product_ids) ) );
		## END PRO ##
	} // action_reprice_products ()


	// auto submit FBA orders - called by do_action()
	public function action_autosubmit_fba_orders() {
		## BEGIN PRO ##
        WPLA()->logger->info("do_action: wpla_autosubmit_fba_orders");

        // only run if FBA is enabled #15403
        if ( ! get_option( 'wpla_fba_enabled' ) ) {
            WPLA()->logger->info( 'action_autosubmit_fba_orders() skipped because FBA is disabled' );
            return;
        }

        $orders = WPLA_FbaHelper::getRecentOrders();
		$msg    = '';
		// $msg .= "total orders: ".count($orders)."<br>";
        $num_orders = count($orders);
        WPLA()->logger->info( 'found '. $num_orders .' recent orders');

		foreach ($orders as $order_post) {

			// check if order can be fulfilled via FBA
			$checkresult = WPLA_FbaHelper::orderCanBeFulfilledViaFBA( $order_post, true );

			// if there are items found for FBA, create new FBA feed
			if ( is_array($checkresult) && ! empty($checkresult) ) {

				WPLA_FbaHelper::submitOrderToFBA( $order_post->ID );

				$msg = "Order ID {$order_post->ID} from {$order_post->post_date} was submitted to FBA.";
		        wpla_show_message($msg);
		        WPLA()->logger->info($msg);

			} else {
				$msg .= "Order ID {$order_post->ID} from {$order_post->post_date} was skipped: $checkresult <br>";
                WPLA()->logger->info($msg);
			}

		} // foreach order

        if ( $msg ) wpla_show_message($msg);
		## END PRO ##
	} // action_autosubmit_fba_orders ()


	// fetch new orders - called by do_action()
	public function action_update_orders() {
        WPLA()->logger->info("do_action: wpla_update_orders");
        WPLA()->logger->startTimer( 'action_update_orders' );

		$accounts = WPLA_AmazonAccount::getAll();

		foreach ($accounts as $account ) {

			$api = new WPLA_Amazon_SP_API( $account->id );

			// get date of last order
			$om = new WPLA_OrdersModel();
			$lastdate = $om->getDateOfLastOrder( $account->id );
			WPLA()->logger->info('getDateOfLastOrder() returned: '.$lastdate);

			$days = isset($_REQUEST['days']) && ! empty($_REQUEST['days']) ? wpla_clean($_REQUEST['days']) : false;
			if ( ! $lastdate && ! $days ) $days = 1;

			// get orders
			$orders = $api->getOrders( $lastdate, $days );
			// echo "<pre>";print_r($orders);echo"</pre>";#die();

			if ( is_array( $orders ) ) {

				// run the import
				$importer = new WPLA_OrdersImporter();
				$importer->importOrders( $orders, $account );

				$msg  = sprintf( __( '%s order(s) were processed for account %s.', 'wp-lister-for-amazon' ), sizeof($orders), $account->title );
				if ( $importer->updated_count  > 0 ) $msg .= "\n".'Updated orders: '.$importer->updated_count ;
				if ( $importer->imported_count > 0 ) $msg .= "\n".'Created orders: '.$importer->imported_count;
				WPLA()->logger->info( $msg );
				$this->showMessage( nl2br($msg),0,1 );

			} elseif ( isset($orders->ErrorMessage) ) {
				$msg = sprintf( __( 'There was a problem downloading orders for account %s.', 'wp-lister-for-amazon' ), $account->title ) .' - Error: '. $orders->ErrorMessage;
				WPLA()->logger->error( $msg );
				$this->showMessage( nl2br($msg),1,1 );
			} else {
				$msg = sprintf( __( 'There was a problem downloading orders for account %s.', 'wp-lister-for-amazon' ), $account->title );
				WPLA()->logger->error( $msg );
				$this->showMessage( nl2br($msg),1,1 );
			}

		}
		$this->message = '';

		WPLA()->logger->endTimer( 'action_update_orders' );
		WPLA()->logger->logSpentTime( 'action_update_orders' );

        // store timestamp
        update_option( 'wpla_orders_cron_last_run', time() );
        
        // If dedicated orders cron is disabled, also update main cron timestamp
        // since orders are part of the main cron process
        if ( ! get_option( 'wpla_dedicated_orders_cron', 0 ) ) {
            update_option( 'wpla_cron_last_run', time() );
        }

	} // action_update_orders()


	// update submitted reports - called by do_action()
	public function action_update_reports( $inventory_sync = false ) {
        WPLA()->logger->info("do_action: wpla_update_reports");

        WPLA_AmazonReport::resetReportsInProgress( $inventory_sync );

		$accounts = WPLA_AmazonAccount::getAll();

		foreach ($accounts as $account ) {

			// get all submitted reports for this account
			$submitted_reports = WPLA_AmazonReport::getSubmittedReportsForAccount( $account->id );
			$ReportRequestIds = array();
			foreach ($submitted_reports as $report) {
				$ReportRequestIds[] = $report->ReportRequestId;
			}

			// do nothing if no submitted reports are found (disable to fetch all recent reports)
			if ( empty( $ReportRequestIds ) ) {
				$msg  = sprintf( __( 'No pending report request(s) for account %s.', 'wp-lister-for-amazon' ), $account->title );
				WPLA()->logger->info( $msg );

				if ( !$inventory_sync ) {
                    $this->showMessage( nl2br($msg),0,1 );
                }

				continue;
			}

            $api = new WPLA_Amazon_SP_API( $account->id );
            $reports = [];
			foreach ( $ReportRequestIds as $report_id ) {
			    $report = $api->getReport( $report_id );

				if ( !empty( $report->errors ) && isset( $report->errors[0]->message ) ) {
					$this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->errors[0]->message, 1 );
				} elseif ( isset( $report->ErrorMessage ) ) {
                    $msg = sprintf( __( 'There was a problem fetching report #%s for account %s.', 'wp-lister-for-amazon' ), $report_id, $account->title ) .' - Error: '. $report->ErrorMessage;
                    WPLA()->logger->error( $msg );

                    if ( !$inventory_sync ) {
                        $this->showMessage( nl2br($msg),1,1 );
                    }
                    continue;
                }

                WPLA_AmazonReport::processReport( $report, $account, true, $inventory_sync );
                $reports[] = $report;
            }

            $msg  = sprintf( __( '%s report request(s) were found for account %s.', 'wp-lister-for-amazon' ), sizeof($reports), $account->title );
            WPLA()->logger->info( $msg );

            if ( !$inventory_sync ) {
                $this->showMessage( nl2br($msg),0,1 );
            }

		}

	} // action_update_reports()


	// update submitted feeds - called by do_action()
	public function action_update_feeds() {
        WPLA()->logger->info("do_action: wpla_update_feeds");

		$accounts = WPLA_AmazonAccount::getAll();
		$feeds_in_progress = 0;

		foreach ( $accounts as $account ) {
            $api = new WPLA_Amazon_SP_API( $account->id );

			// get all submitted feeds for this account
			$submitted_feeds = WPLA_AmazonFeed::getSubmittedFeedsForAccount( $account->id );

			// Test Data
            /*$test_feed = new stdClass();
            $test_feed->FeedSubmissionId = 'feedId1';
            $submitted_feeds = [$test_feed];*/


			$feeds = array();

			foreach ($submitted_feeds as $feed_row) {
			    $feed = $api->getFeed( $feed_row->FeedSubmissionId );

			    if ( WPLA_Amazon_SP_API::isError( $feed ) ) {
			        $msg = sprintf( __( 'Unable to retrieve feed from Amazon: %s. Reason: %s', 'wp-lister-for-amazon' ), $feed_row->FeedSubmissionId, $feed->ErrorMessage );
			        WPLA()->logger->error( $msg );
			        $this->showMessage( $msg, true, true );
			        continue;
                }

			    // TEST DATA
                //$feed->setResultFeedDocumentId( '0356cf79-b8b0-4226-b4b9-0ee058ea5760' );
			    //$feed->setProcessingStatus( 'DONE' );

                $feeds[] = $feed;

			}

			// do nothing if no submitted feeds are found (disable to fetch all recent feeds)
			if ( empty( $feeds ) ) {
				$msg  = sprintf( __( 'No submitted feeds found for account %s.', 'wp-lister-for-amazon' ), $account->title );
				WPLA()->logger->info( $msg );
				$this->showMessage( nl2br($msg),0,1 );
				continue;
			}

			if ( is_array( $feeds ) )  {
				// process feed submission list
				$feeds_in_progress += WPLA_AmazonFeed::processFeedsSubmissionList( $feeds, $account );

				$msg  = sprintf( __( '%s feed submission(s) were found for account %s.', 'wp-lister-for-amazon' ), sizeof($feeds), $account->title );
				WPLA()->logger->info( $msg );
				$this->showMessage( nl2br($msg),0,1 );

			} elseif ( !empty( $feeds->Error->Message ) ) {
				$msg = sprintf( __( 'There was a problem fetching feed submissions for account %s.', 'wp-lister-for-amazon' ), $account->title ) .' - Error: '. $feeds->ErrorMessage;
				WPLA()->logger->error( $msg );
				$this->showMessage( nl2br($msg),1,1 );
			} else {
				$msg = sprintf( __( 'There was a problem fetching feed submissions for account %s.', 'wp-lister-for-amazon' ), $account->title );
				WPLA()->logger->error( $msg );
				$this->showMessage( nl2br($msg),1,1 );
			}

		}

		// update feed progress status
		update_option( 'wpla_feeds_in_progress', $feeds_in_progress );

	} // action_update_feeds()


	// submit pending feeds - called by do_action()
	public function action_submit_pending_feeds() {
        WPLA()->logger->info("do_action: wpla_submit_pending_feeds");

		$accounts = WPLA_AmazonAccount::getAll();

		foreach ($accounts as $account ) {

			// refresh feeds
			WPLA_AmazonFeed::updatePendingFeedForAccount( $account );

			// get pending feeds for account
			$feeds = WPLA_AmazonFeed::getAllPendingFeedsForAccount( $account->id );
	        WPLA()->logger->info("found ".sizeof($feeds)." pending feeds for account {$account->id}");

			foreach ($feeds as $feed) {

				$autosubmit_feeds = array(
					'_POST_FLAT_FILE_PRICEANDQUANTITYONLY_UPDATE_DATA_',	// Price and Quantity Update Feed
					'_POST_FLAT_FILE_LISTINGS_DATA_',						// Flat File Listings Feed
					'_POST_FLAT_FILE_FULFILLMENT_DATA_',					// Order Fulfillment Feed
					'_POST_FLAT_FILE_FULFILLMENT_ORDER_REQUEST_DATA_',		// Flat File FBA Shipment Injection Fulfillment Feed
					'_POST_FLAT_FILE_INVLOADER_DATA_',						// Inventory Loader Feed (Product Removal)
                    '_UPLOAD_VAT_INVOICE_',                                  // VAT Invoice uploading
                    // SP-API Feed types
                    'POST_FLAT_FILE_PRICEANDQUANTITYONLY_UPDATE_DATA',	// Price and Quantity Update Feed
					'POST_FLAT_FILE_LISTINGS_DATA',						// Flat File Listings Feed
					'POST_FLAT_FILE_FULFILLMENT_DATA',					// Order Fulfillment Feed
					'POST_FLAT_FILE_FULFILLMENT_ORDER_REQUEST_DATA',		// Flat File FBA Shipment Injection Fulfillment Feed
					'POST_FLAT_FILE_INVLOADER_DATA',						// Inventory Loader Feed (Product Removal)
                    'UPLOAD_VAT_INVOICE',                                  // VAT Invoice uploading

					// JSON
					'JSON_LISTINGS_FEED',
				);

				if ( ! in_array( $feed->FeedType, $autosubmit_feeds ) ) {
			        WPLA()->logger->info("skipped pending feed {$feed->id} ({$feed->FeedType}) for account {$account->id} - autosubmit disabled for feed type");
			        continue;
				}

				// submit feed
				$feed->submit();

		        WPLA()->logger->info("submitted pending feed {$feed->id} ({$feed->FeedType}) for account {$account->id}");
			}

		}

	} // action_submit_pending_feeds()




	// request listing quality reports for all active accounts
	public function request_daily_quality_report() {

		$report_type = 'GET_MERCHANT_LISTINGS_DEFECT_DATA';
		$accounts    = WPLA_AmazonAccount::getAll();

		foreach ($accounts as $account ) {

			$api = new WPLA_Amazon_SP_API( $account->id );

			// request report - returns request list as array on success
			$reports = $api->getReports( [$report_type] );

			if ( WPLA_Amazon_SP_API::isError( $reports ) ) {
			    WPLA()->logger->error( 'getReports error: '. $reports->ErrorMessage );
            } elseif ( is_array( $reports ) )  {
                foreach ( $reports as $report ) {
                    // process the result
                    WPLA_AmazonReport::processReport( $report, $account, true );
                }
            }

		} // foreach account

	} // request_daily_quality_report()


	// request FBA inventory reports for all active accounts
	public function request_daily_fba_report() {

		//$report_type = '_GET_AFN_INVENTORY_DATA_';
        $report_type = 'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA'; // Use Manage FBA Inventory Report #32733
		$accounts    = WPLA_AmazonAccount::getAll();

		foreach ($accounts as $account ) {

			$api = new WPLA_Amazon_SP_API( $account->id );

            $report_id = $api->createReport( $report_type );

            if ( is_object($report_id) && isset( $report_id->ErrorMessage ) ) {
                WPLA()->logger->error( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report_id->ErrorMessage );
            } else {
                $report = $api->getReport( $report_id );

	            if ( !empty( $report->errors ) && isset( $report->errors[0]->message ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->errors[0]->message, 1 );
	            } elseif ( isset( $report->ErrorMessage ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->ErrorMessage, 1 );
	            } else {
		            WPLA_AmazonReport::processReport( $report, $account, true );
		            WPLA()->logger->info( sprintf( __( 'Report requested for account %s.', 'wp-lister-for-amazon' ), $account->title ) );
	            }
            }

		} // foreach account

	} // request_daily_fba_report()

	/**
	 * Loop handler: processes the queue in batches and reschedules itself if needed.
	 */
	public function process_publish_queue_runner() {
		if ( WPLA_Setup::isStagingSite() ) {
			return;
		}
		WPLA()->logger->debug( 'Running process_publish_queue_runner' );

		// Lock settings
		$lock_key       = 'wpla_amazon_publish_queue_lock';
		$lock_ttl       = 5 * 60; // 5 minutes in seconds

		$lock = get_option( $lock_key );
		if ( $lock ) {
			$locked_at = intval( $lock );
			if ( time() - $locked_at < $lock_ttl ) {
				WPLA()->logger->debug( 'Another process_publish_queue_runner is active; exiting.' );
				return;
			}
			// Lock expired — clear it
			delete_option( $lock_key );
		}
		// Acquire fresh lock (store current timestamp)
		add_option( $lock_key, time(), '', 'no' );

		$already_published_statuses = [
			WPLA_ListingsModel::STATUS_SUBMITTED,
			WPLA_ListingsModel::STATUS_ONLINE,
			WPLA_ListingsModel::STATUS_SOLD,
			WPLA_ListingsModel::STATUS_ARCHIVED,
			WPLA_ListingsModel::STATUS_TRASH,
		];

		try {
			// Rate limit settings
			$batch_size     = 5;    // max items per batch
			$batch_interval = 1;    // seconds between batches
			// Maximum execution time to avoid host-enforced timeouts (seconds)
			$max_runtime    = 30;

			$mdl            = new WPLA_ListingsModel();
			$json_builder   = new \WPLab\Amazon\Helper\JsonFeedDataBuilder();

			$profiles_cache = [];

			$queue      = get_option( 'wpla_amazon_publish_queue', array() );
			$start_time = time();

			WPLA()->logger->debug( 'Current queue size: '. count( $queue ) );

			// Prepare a buffer for items that need retrying due to throttling
			$retry_queue = [];

			while ( ! empty( $queue ) ) {
				// Prevent running past safe runtime
				if ( time() - $start_time >= $max_runtime ) {
					// Persist remaining *plus* any throttled items, and reschedule
					$new_queue = array_merge( $retry_queue, $queue );
					update_option( 'wpla_amazon_publish_queue', $new_queue, false );
					wp_schedule_single_event( time() + $batch_interval, 'wpla_process_publish_queue_runner' );
					$elapsed = time() - $start_time;
					WPLA()->logger->debug( 'Terminating early to prevent timeout. Elapsed: ' . $elapsed .'s' );
					return;
				}

				$chunk = array_splice( $queue, 0, $batch_size );
				foreach ( $chunk as $listing_id ) {
					WPLA()->logger->debug( 'Submitting '. $listing_id );
					try {
						$listing    = $mdl->getItem( $listing_id );
						$profile_id = $listing['profile_id'];

						// Check if listing is already published - if so, just remove from queue
						if ( in_array( $listing['status'], $already_published_statuses ) ) {
							WPLA()->logger->info( "Listing {$listing_id} already published (status: {$listing['status']}), removing from queue" );
							WPLA_ListingsModel::removeListingFromPublishingQueue( $listing_id );
							continue;
						}

						if ( isset( $profiles_cache[ $profile_id ] ) ) {
							$profile = $profiles_cache[ $profile_id ];
						} else {
							$profile = new WPLA_AmazonProfile( $profile_id );
							$profiles_cache[ $profile_id ] = $profile;
						}

						$validation_result = $json_builder->canSubmitListing( $listing, $profile );
						if ( is_wp_error( $validation_result ) ) {
							$history = [
								'errors' => [
									[
										'error-code'    => $validation_result->get_error_code(),
										'error-message' => $validation_result->get_error_message(),
										'error-type'    => 'Error'
									]
								],
								'warnings' => []
							];
							$data = [
								'status' => WPLA_ListingsModel::STATUS_FAILED,
								'history' => serialize( $history )
							];
							$mdl->updateListing( $listing_id, $data );
							WPLA_ListingsModel::removeListingFromPublishingQueue( $listing_id );
							continue;
							//throw new Exception( $validation_result->get_error_message(), 401 );
						}

						//$api->setAccountId( $profile->account_id );
						$api = new WPLA_Amazon_SP_API( $profile->account_id );
						$result = $api->putListingsItem( $listing, $profile );

						if ( !WPLA_Amazon_SP_API::isError( $result ) ) {
							if ( $result->getStatus() == 'ACCEPTED' ) {
								// SUCCESS: update listing status to online so it gets marked as "needs update" to fetch the ASIN
								$update_data = [
									'status' => WPLA_ListingsModel::STATUS_SUBMITTED
								];

								/*if ( isset( $result['Identifiers']['MarketplaceASIN']['ASIN'] ) ) {
									$asin = sanitize_text_field( $result['Identifiers']['MarketplaceASIN']['ASIN'] );
									$update_data['asin'] = $asin;
									update_post_meta( $listing['post_id'], '_wpla_asin', $asin );
								}*/
								$mdl->updateListing( $listing_id, $update_data );
								
								// Remove successfully submitted listing from publishing queue
								WPLA_ListingsModel::removeListingFromPublishingQueue( $listing_id );
							} else {
								$history = [
									'errors' => [],
									'warnings' => []
								];
								foreach ( $result->getIssues() as $issue ) {
									$error = [
										'error-code'    => $issue->getCode(),
										'error-message' => $issue->getMessage(),
										'error-type'    => $issue->getSeverity()
									];

									if ( $issue->getSeverity() == 'ERROR' ) {
										$history['errors'][] = $error;
									} elseif ( $issue->getSeverity() == 'WARNING' ) {
										$history['warnings'][] = $error;
									}
								}

								$data = [
									'status' => WPLA_ListingsModel::STATUS_FAILED,
									'history' => serialize( $history )
								];
								$mdl->updateListing( $listing_id, $data );
							}
						} else {
							// API returned an error payload
							// Check for Amazon throttling
							if ( in_array( $result->StatusCode, [429, 503], true ) ) {
								WPLA()->logger->info( "Throttled on {$listing_id}, will retry next run (code: {$result->StatusCode})" );
								$retry_queue[] = $listing_id;
							} else {
								// Non-retryable error: mark failed
								$error_message = $result->ErrorMessage;
								$error_code = $result->ErrorCode ?? $result->StatusCode;
								
								// Include error details if available
								if (isset($result->ErrorDetails) && !empty($result->ErrorDetails)) {
									$error_message .= ' - ' . $result->ErrorDetails;
								}
								
								// Debug logging to see what we actually received
								WPLA()->logger->info( "Debug - Error object: " . print_r($result, true) );
								
								WPLA()->logger->info( "Publish failed for {$listing_id} (Amazon Error {$error_code}: {$error_message})" );
								$history = [
									'errors' => [],
									'warnings' => []
								];

								$error = [
									'error-code'    => $error_code,
									'error-message' => $error_message,
									'error-type'    => 'ERROR'
								];
								$history['errors'][] = $error;

								$mdl->updateListing( $listing_id, ['status' => WPLA_ListingsModel::STATUS_FAILED, 'history' => serialize($history)] );

								WPLA_ListingsModel::removeListingFromPublishingQueue( $listing_id );
							}
						}

					} catch ( Exception $e ) {
						// Exception during HTTP call / parsing
						$code = $e->getCode();
						if ( intval($code) === 429 ) {
							WPLA()->logger->warn( "Caught HTTP 429 for {$listing_id}, re-queuing" );
							$retry_queue[] = $listing_id;
						} else {
							WPLA()->logger->error( sprintf(
								'Amazon Queue Exception for %d: %s',
								$listing_id,
								$e->getMessage()
							) );

							$history = [
								'errors' => [],
								'warnings' => []
							];

							$error = [
								'error-code'    => $e->getCode(),
								'error-message' => $e->getMessage(),
								'error-type'    => 'ERROR'
							];

							$history['errors'][] = $error;

							if ( isset( $listing_id ) ) {
								$data = [
									'status' => WPLA_ListingsModel::STATUS_FAILED,
									'history' => serialize( $history )
								];

								$mdl->updateListing( $listing_id, $data );
								WPLA_ListingsModel::removeListingFromPublishingQueue( $listing_id );
							}
						}
					}
				}

				// Merge throttled items back to front of queue and clear buffer
				if ( ! empty( $retry_queue ) ) {
					$queue = array_merge( $retry_queue, $queue );
					$retry_queue = [];
				}

				// Small pause to respect rate limit
				WPLA()->logger->debug( 'Sleeping for '. $batch_interval .'s' );
				sleep( $batch_interval );
			}

			// Queue fully processed
			WPLA()->logger->debug( 'Finished the queue!' );
			delete_option( WPLA_ListingsModel::PUBLISH_QUEUE_KEY );
		} finally {
			// Release the lock
			delete_option( $lock_key );
		}
	}

	// request FBA fulfilled shipment reports for all active accounts
	public function request_daily_fba_shipments_report() {

		$report_type = 'GET_AMAZON_FULFILLED_SHIPMENTS_DATA_GENERAL';
		$accounts    = WPLA_AmazonAccount::getAll();

		foreach ($accounts as $account ) {

			$api = new WPLA_Amazon_SP_API( $account->id );

            $report_id = $api->createReport( $report_type );

            if ( is_object($report_id) && isset( $report_id->ErrorMessage ) ) {
                WPLA()->logger->error( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report_id->ErrorMessage );
            } else {
                $report = $api->getReport( $report_id );

	            if ( !empty( $report->errors ) && isset( $report->errors[0]->message ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->errors[0]->message, 1 );
	            } elseif ( isset( $report->ErrorMessage ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->ErrorMessage, 1 );
	            } else {
		            WPLA_AmazonReport::processReport( $report, $account, true );
		            WPLA()->logger->info( sprintf( __( 'Report requested for account %s.', 'wp-lister-for-amazon' ), $account->title ) );
	            }

            }

		} // foreach account

	} // request_daily_fba_shipments_report()


	// request merchant inventory reports for all active accounts
	public function request_daily_inventory_report() {

		$report_type = 'GET_MERCHANT_LISTINGS_DATA';
		$accounts    = WPLA_AmazonAccount::getAll();

		foreach ($accounts as $account ) {

			$api = new WPLA_Amazon_SP_API( $account->id );

            $report_id = $api->createReport( $report_type );

            if ( is_object($report_id) && isset( $report_id->ErrorMessage ) ) {
                WPLA()->logger->error( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report_id->ErrorMessage );
            } else {
                $report = $api->getReport( $report_id );

	            if ( !empty( $report->errors ) && isset( $report->errors[0]->message ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->errors[0]->message, 1 );
	            } elseif ( isset( $report->ErrorMessage ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->ErrorMessage, 1 );
	            } else {
		            WPLA_AmazonReport::processReport( $report, $account, true );
		            WPLA()->logger->info( sprintf( __( 'Report requested for account %s.', 'wp-lister-for-amazon' ), $account->title ) );
	            }

            }

		} // foreach account

	} // request_daily_inventory_report()

    // request order reports for all active accounts
    public function request_daily_order_report() {

        $report_type = 'GET_FLAT_FILE_ORDER_REPORT_DATA_INVOICING';
        $accounts    = WPLA_AmazonAccount::getAll();

        foreach ($accounts as $account ) {

            $api = new WPLA_Amazon_SP_API( $account->id );

            $report_id = $api->createReport( $report_type );

            if ( is_object($report_id) && isset( $report_id->ErrorMessage ) ) {
                WPLA()->logger->error( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report_id->ErrorMessage );
            } else {
                $report = $api->getReport( $report_id );

	            if ( !empty( $report->errors ) && isset( $report->errors[0]->message ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->errors[0]->message, 1 );
	            } elseif ( isset( $report->ErrorMessage ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->ErrorMessage, 1 );
	            } else {
		            WPLA_AmazonReport::processOrderReportData( $report, $account, true );
		            WPLA()->logger->info( sprintf( __( 'Report requested for account %s.', 'wp-lister-for-amazon' ), $account->title ) );
	            }

            }

        } // foreach account

    } // request_daily_inventory_report()

    ## BEGIN PRO ##
    public function set_inventory_check_cron_schedule() {
        // Make sure WooCommerce is installed/activated before proceeding #41102
        if ( !function_exists( 'as_next_scheduled_action' ) ) {
            return;
        }

		$frequency_changed = get_option( 'wpla_inventory_check_frequency_changed', false );

        if ( get_option( 'wpla_run_background_inventory_check', 1) ) {
			if ( $frequency_changed ) {
				as_unschedule_all_actions('wpla_bg_inventory_check', [], 'WPLA');
				as_unschedule_all_actions('wpla_update_reports', array('inventory_sync' => 1), 'WPLA');
				as_unschedule_all_actions('wpla_bg_inventory_check_run', [], 'WPLA');
			}

            // Turn it on
            if ( ! as_next_scheduled_action( 'wpla_bg_inventory_check' ) || $frequency_changed ) {
                $frequency = get_option( 'wpla_inventory_check_frequency', 24 );
                as_schedule_recurring_action( time(), $frequency * 3600, 'wpla_bg_inventory_check', [], 'WPLA' );
				delete_option( 'wpla_inventory_check_frequency_changed' );
            }
        } else {
            if (as_next_scheduled_action('wpla_bg_inventory_check')) {
                as_unschedule_all_actions('wpla_update_reports', array('inventory_sync' => 1), 'WPLA');
                as_unschedule_all_actions('wpla_bg_inventory_check', [], 'WPLA');
                as_unschedule_all_actions('wpla_bg_inventory_check_run', [], 'WPLA');
            }
        }
    }

    /**
     * Check product inventory in the background
     *
     * This initiates the background inventory check by pulling new reports for all active accounts. Once the reports
     * are ready, they are then processed by WPLA_InventoryCheck::checkInventorySync() which takes care of the notification
     * if inconsistencies are found.
     * @todo Migrate to SP-API
     */
    public function cron_bg_inventory_check( $check = false ) {
        WPLA()->logger->info( 'cron_bg_inventory_check invoked' );
        WPLA()->logger->info( 'Requesting Merchant Listings report for all accounts' );

        $accounts    = WPLA_AmazonAccount::getAll();

        foreach ($accounts as $account ) {

            $api = new WPLA_Amazon_SP_API( $account->id );

            // request report - returns request list as array on success
            //$reports = $api->requestReport( '_GET_MERCHANT_LISTINGS_DATA_' );

            $report_id = $api->createReport( 'GET_MERCHANT_LISTINGS_DATA' );

            if ( is_object($report_id) && isset( $report_id->ErrorMessage ) ) {
                WPLA()->logger->error( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report_id->ErrorMessage );
            } else {
                $report = $api->getReport( $report_id );

	            if ( !empty( $report->errors ) && isset( $report->errors[0]->message ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->errors[0]->message, 1 );
	            } elseif ( isset( $report->ErrorMessage ) ) {
		            $this->showMessage( sprintf( __( 'There was a problem requesting the report for account %s.', 'wp-lister-for-amazon' ), $account->title ) .'<br>Error: '. $report->ErrorMessage, 1 );
	            } else {
		            WPLA_AmazonReport::processReport( $report, $account, true );
		            WPLA()->logger->info( sprintf( __( 'Report requested for account %s.', 'wp-lister-for-amazon' ), $account->title ) );

		            as_schedule_recurring_action( time() + 120, 120, 'wpla_update_reports', array('inventory_sync' => 1), 'WPLA' );
	            }

            }

        } // foreach account

    }

    public function cron_bg_inventory_check_run( $report_id ) {
        // Remove the update_reports cron action
        as_unschedule_all_actions( 'wpla_update_reports', array('inventory_sync' => 1), 'WPLA' );

        $ic = new WPLA_InventoryCheck( 'wpla_bg_inventory_check_queue_data' );
        $ic->checkInventorySyncFromReport( $report_id );
    }
    ## END PRO ##

	## BEGIN PRO ##
	public function handle_reduce_order_stock( $order ) {
		global $woocommerce;

		// check if inventory sync is enabled
		if ( ! get_option( 'wpla_sync_inventory' ) ) return;

		$listingsModel = new WPLA_ListingsModel();
		// $cart = $woocommerce->cart->get_cart();

		WPLA()->logger->info('handle order #'.wpla_get_order_meta( $order, 'id' ) );
		// WPLA()->logger->info('posted: '.print_r($posted,1) );
		// WPLA()->logger->info('cart: '.print_r($cart,1) );

		// Reduce stock levels and do any other actions with products in the cart
		$post_IDs   = array();
		$cart_items = array();
		foreach ( $order->get_items() as $item ) {

			if ( (@$item['id']>0) || (@$item['product_id']>0) ) {

				// get post ID for WC2.0 and WC1.x
				$post_id = isset( $item['product_id'] ) ? $item['product_id'] : $item['id'];
				WPLA()->logger->info('processing reduce stock for product #' . $post_id . '');

				// check if this is a variable product and get SKU
				$variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : '';
				if ( $variation_id ) {
					$sku = get_post_meta( $variation_id, '_sku', true );
					WPLA()->logger->info('processing variation ID ' . $variation_id . ' - SKU: '.$sku);
				}

                if ( is_callable( array( $item, 'get_product' ) ) ) {
                    $_product = $item->get_product();
                } else {
                    $_product = $order->get_product_from_item( $item );
                }
				
				if ( $_product && $_product->exists() && $_product->managing_stock() ) {

					// reduce quantity for simple product
					if ( ! $variation_id ) {

						// check if this product has a listing
						$listing = $listingsModel->getItemByPostID( $post_id );
						if ( ! $listing ) continue;

						// update stock level
						$listingsModel->setListingQuantity( $post_id, wpla_get_product_meta( $_product, 'stock' ) );
						WPLA()->logger->info('new stock: ' . wpla_get_product_meta( $_product, 'stock' ) . '');

						// check if listing is online
						if ( $listing->status != 'online' ) continue;

						// mark as modified
						$listingsModel->markItemAsModified( $post_id, true );
						WPLA()->logger->info('marked item as modified: ' . $post_id . '');
						$post_IDs[] = $post_id;

						// $cart_item = new stdClass();
						// $cart_item->post_id      = $post_id;
						// $cart_item->variation_id = $variation_id;
						// $cart_item->sku          = $_product->get_sku();
						// $cart_items[] = $cart_item;

					} elseif ( $variation_id ) {
						// reduce quantity for variation

						// check if this product has a listing
						$listing = $listingsModel->getItemByPostID( $variation_id );
						if ( ! $listing ) continue;

						// update stock level
						$listingsModel->setListingQuantity( $variation_id, wpla_get_product_meta( $_product, 'stock' ) );
						WPLA()->logger->info('new stock: ' . wpla_get_product_meta( $_product, 'stock' ) . '');

						// check if listing is online
						if ( $listing->status != 'online' ) continue;

						// mark as modified - using parent post_id will mark all child variations as modified
						$listingsModel->markItemAsModified( $post_id, true );
						WPLA()->logger->info('marked item as modified: ' . $post_id . '');
						$post_IDs[] = $variation_id;

					// } elseif ( $variation_id && $sku ) {
						// TODO: reduce quantity for SKU
					}


				} // if product exists

			} // if product_id

		} // foreach cart item


		// add order note
		if ( count($post_IDs) > 0 ) {

			$order->add_order_note( sprintf( __( 'Scheduled inventory update for %s item(s) on Amazon...', 'wp-lister-for-amazon' ), count($post_IDs) ) .' '. join(', ',$post_IDs) );
			// WPLA()->logger->info('cart_items:' . print_r($cart_items,1) );

            if ( function_exists( 'as_enqueue_async_action' ) ) {
                as_enqueue_async_action( 'wpla_update_feeds', [], 'WPLA' );
            } else {
                as_schedule_single_action( time(), 'wpla_update_feeds', [], 'WPLA' );
            }

		}

	} // handle_reduce_order_stock()
	## END PRO ##


	public function action_clean_log_table() {
		global $wpdb;

		// clean log table
		$days_to_keep = get_option( 'wpla_log_days_limit', 30 );
		$rows = $wpdb->query('DELETE FROM '.$wpdb->prefix.'amazon_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL '.intval($days_to_keep).' DAY )');
		WPLA()->logger->info('Cleaned table amazon_log - affected rows: ' . $rows);

		WPLA()->logger->deleteOldLogs( $days_to_keep );

		// clean stock log table
		$days_to_keep = get_option( 'wpla_stock_days_limit', 180 );
		$rows = $wpdb->query('DELETE FROM '.$wpdb->prefix.'amazon_stock_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL '.$days_to_keep.' DAY )');
		WPLA()->logger->info('Cleaned table amazon_stock_log - affected rows: ' . $rows);

		// Optimize the tables
        $wpdb->query('OPTIMIZE TABLE '. $wpdb->prefix.'amazon_log');
        $wpdb->query('OPTIMIZE TABLE '. $wpdb->prefix.'amazon_stock_log');

	} // action_clean_log_table()

	public function action_clean_tables() {
		global $wpdb;

		// clean feeds table (date_created)
		$days_to_keep = get_option( 'wpla_feeds_days_limit', 90 );
		if ( $days_to_keep ) {
			$rows = $wpdb->query('DELETE FROM '.$wpdb->prefix.'amazon_feeds WHERE date_created < DATE_SUB(NOW(), INTERVAL '.$days_to_keep.' DAY )');
			WPLA()->logger->info('Cleaned table amazon_feeds - affected rows: ' . $rows);

			$wpdb->query("DELETE FROM {$wpdb->prefix}amazon_fulfillment_feed_items WHERE date_added < DATE_SUB(NOW(), INTERVAL '. $days_to_keep .' DAY )");
		}

		// clean reports table (SubmittedDate)
		$days_to_keep = get_option( 'wpla_reports_days_limit', 90 );
		if ( $days_to_keep ) {
			$rows = $wpdb->query('DELETE FROM '.$wpdb->prefix.'amazon_reports WHERE SubmittedDate < DATE_SUB(NOW(), INTERVAL '.$days_to_keep.' DAY )');
			WPLA()->logger->info('Cleaned table amazon_reports - affected rows: ' . $rows);
		}

        // clean jobs table (date_created)
        $days_to_keep = get_option( 'wpla_reports_days_limit', '' );
        if ( $days_to_keep ) {
            $rows = $wpdb->query('DELETE FROM '.$wpdb->prefix.'amazon_jobs WHERE date_created < DATE_SUB(NOW(), INTERVAL '.$days_to_keep.' DAY )');
            WPLA()->logger->info('Cleaned table amazon_jobs - affected rows: ' . $rows);
        }

		// clean orders table (date_created)
		$days_to_keep = get_option( 'wpla_orders_days_limit', '' );
		if ( $days_to_keep ) {
			$rows = $wpdb->query('DELETE FROM '.$wpdb->prefix.'amazon_orders WHERE date_created < DATE_SUB(NOW(), INTERVAL '.$days_to_keep.' DAY )');
			WPLA()->logger->info('Cleaned table amazon_orders - affected rows: ' . $rows);
		}

	} // action_clean_tables()


	public function checkLock() {

		// get full path to lockfile
		$uploads        = wp_upload_dir();
		$lockfile       = $uploads['basedir'] . '/' . 'wpla_sync.lock';
		$this->lockfile = $lockfile;

		// skip locking if lockfile is not writeable
		if ( ! is_writable( $lockfile ) && ! is_writable( dirname( $lockfile ) ) ) {
	        WPLA()->logger->error("lockfile not writable: ".$lockfile);
	        return true;
		}

		// create lockfile if it doesn't exist
        // using is_readable checks that the lockfile exists AND is readable  #36512
		if ( ! is_readable( $lockfile ) ) {
			$ts = time();
			file_put_contents( $lockfile, $ts );
	        WPLA()->logger->info("lockfile created at TS $ts: ".$lockfile);
	        return true;
		}

		// lockfile exists - check TS
		$ts = (int) file_get_contents($lockfile);

		// check if TS is outdated (after 10min.)
		if ( $ts < ( time() - 600 ) ) {
	        WPLA()->logger->info("stale lockfile found for TS ".$ts.' - '.human_time_diff( $ts ).' ago' );

	        // update lockfile
			$ts = time();
			file_put_contents( $lockfile, $ts );

	        WPLA()->logger->info("lockfile updated for TS $ts: ".$lockfile);
	        return true;
		} else {
			// process is still alive - can not run twice
	        WPLA()->logger->info("SKIP CRON - sync already running with TS ".$ts.' - '.human_time_diff( $ts ).' ago' );
			return false;
		}

		return true;
	} // checkLock()

	public function removeLock() {
		if ( file_exists( $this->lockfile ) ) {
			unlink( $this->lockfile );
	        WPLA()->logger->info("lockfile was removed: ".$this->lockfile);
		}
	}

	public function cron_add_custom_schedules( $schedules ) {
		$schedules['five_min'] = array(
			'interval' => 60 * 5,
			'display' => 'Once every five minutes'
		);
		$schedules['ten_min'] = array(
			'interval' => 60 * 10,
			'display' => 'Once every ten minutes'
		);
		$schedules['fifteen_min'] = array(
			'interval' => 60 * 15,
			'display' => 'Once every fifteen minutes'
		);
		$schedules['thirty_min'] = array(
			'interval' => 60 * 30,
			'display' => 'Once every thirty minutes'
		);
		$schedules['three_hours'] = array(
			'interval' => 60 * 60 * 3,
			'display' => 'Once every three hours'
		);
		$schedules['six_hours'] = array(
			'interval' => 60 * 60 * 6,
			'display' => 'Once every six hours'
		);
		$schedules['twelve_hours'] = array(
			'interval' => 60 * 60 * 12,
			'display' => 'Once every twelve hours'
		);
		return $schedules;
	}

	public function showMessage($message, $errormsg = false, $echo = false) {

		// don't output message when doing cron
		if ( defined('DOING_CRON') && DOING_CRON ) return;

		if ( defined('WPLISTER_RESELLER_VERSION') ) $message = apply_filters( 'wpla_tooltip_text', $message );

		$class = ($errormsg) ? 'error' : 'updated fade';
		$class = ($errormsg == 2) ? 'updated update-nag notice notice-warning' : $class; 	// warning
		$message = '<div id="message" class="'.$class.'" style="display:block !important"><p>'.$message.'</p></div>'."\n";
		if ($echo) {
			echo $message;
		} else {
			$this->message .= $message;
		}
	}

}
// $WPLA_CronActions = new WPLA_CronActions();
