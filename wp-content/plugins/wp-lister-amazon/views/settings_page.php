<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	#AuthSettingsBox ol li {
		margin-bottom: 25px;
	}
	#AuthSettingsBox ol li > small {
		margin-left: 4px;
	}

	#side-sortables .postbox input.text_input,
	#side-sortables .postbox select.select {
	    width: 50%;
	}
	#side-sortables .postbox label.text_label {
	    width: 45%;
	}
	#side-sortables .postbox p.desc {
	    margin-left: 5px;
	}

</style>

<div class="wrap wpla-page">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/amazon-32x32.png) no-repeat;" id="wpl-icon"><br /></div>

	<?php include_once( dirname(__FILE__).'/settings_tabs.php' ); ?>
	<?php echo $wpl_message ?>

	<form method="post" id="settingsForm" action="<?php echo $wpl_form_action; ?>">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box">


					<!-- first sidebox -->
					<div class="postbox" id="submitdiv">
						<!--<div title="Click to toggle" class="handlediv"><br></div>-->
						<h3 class="hndle"><span><?php echo __( 'Sync Status', 'wp-lister-for-amazon' ); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">
									<div class="misc-pub-section">
									<?php /* if ( @$wpl_amazon_token_userid ): ?>
										<p>
											<!-- <b><?php echo __( 'Account Details', 'wp-lister-for-amazon' ) ?></b> -->
											<table style="width:95%">
												<tr><td><?php echo __( 'User ID', 'wp-lister-for-amazon' ) . ':</td><td>' . $wpl_amazon_token_userid ?></td></tr>
												<tr><td><?php echo __( 'Status', 'wp-lister-for-amazon' ) . ':</td><td>' . $wpl_amazon_user->Status ?></td></tr>
												<tr><td><?php echo __( 'Score', 'wp-lister-for-amazon' ) . ':</td><td>' . $wpl_amazon_user->FeedbackScore ?></td></tr>
												<tr><td><?php echo __( 'Site', 'wp-lister-for-amazon' ) . ':</td><td>' . $wpl_amazon_user->Site ?></td></tr>
												<?php if ( $wpl_amazon_user->StoreOwner ) : ?>
												<tr><td><?php echo __( 'Store', 'wp-lister-for-amazon' ) . ':</td><td>' ?><a href="<?php echo $wpl_amazon_user->StoreURL ?>" target="_blank"><?php echo __('visit store', 'wp-lister-for-amazon' ) ?></a></td></tr>
												<?php endif; ?>
											</table>
										</p>
									<?php endif; */ ?>

									<?php if ( empty( WPLA()->accounts ) ): ?>
										<p><?php echo __( 'No Amazon account has been set up yet.', 'wp-lister-for-amazon' ) ?></p>
									<?php elseif ( $wpl_option_cron_schedule && $wpl_option_sync_inventory ): ?>
										<p><?php echo __( 'Sync is enabled.', 'wp-lister-for-amazon' ) ?></p>
										<p><?php echo __( 'Sales will be synchronized between WooCommerce and Amazon.', 'wp-lister-for-amazon' ) ?></p>
									<?php elseif ( WPLA_LIGHT ): ?>
										<p><?php echo __( 'Sync is not available in WP-Lister Lite.', 'wp-lister-for-amazon' ) ?></p>
										<p><?php echo __( 'To synchronize sales across Amazon and WooCommerce you need to upgrade to WP-Lister Pro.', 'wp-lister-for-amazon' ) ?></p>
									<?php else: ?>
										<p><?php echo __( 'Sync is currently disabled.', 'wp-lister-for-amazon' ) ?></p>
										<p><?php echo __( 'Amazon and WooCommerce sales will not be synchronized!', 'wp-lister-for-amazon' ) ?></p>
									<?php endif; ?>

									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="submit" value="<?php echo __( 'Update Settings', 'wp-lister-for-amazon' ); ?>" id="save_settings" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>

					<?php if ( $wpl_is_staging_site ) : ?>
					<div class="postbox" id="StagingSiteBox">
						<h3 class="hndle"><span><?php echo __( 'Staging Site', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">
							<p>
								<span style="color:darkred; font-weight:bold">
									Note: Automatic background updates and order creation have been disabled on this staging site.
								</span>
							</p>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( $wpl_option_cron_schedule ) : ?>
					<div class="postbox" id="UpdateScheduleBox">
						<h3 class="hndle"><span><?php echo __( 'Update Schedule', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">

							<p>
							<?php if ( wp_next_scheduled( 'wpla_update_schedule' ) ) : ?>
								<?php echo __( 'Next scheduled update', 'wp-lister-for-amazon' ); ?>:
								<?php echo human_time_diff( wp_next_scheduled( 'wpla_update_schedule' ), current_time('timestamp',1) ) ?>
								<?php echo wp_next_scheduled( 'wpla_update_schedule' ) < current_time('timestamp',1) ? 'ago' : '' ?>
							<?php elseif ( $wpl_option_cron_schedule == 'external' ) : ?>
								<?php echo __( 'Background updates are handled by an external cron job.', 'wp-lister-for-amazon' ); ?>
								<a href="#TB_inline?height=420&width=900&inlineId=cron_setup_instructions" class="thickbox">
									<?php echo __( 'Details', 'wp-lister-for-amazon' ); ?>
								</a>

								<div id="cron_setup_instructions" style="display: none;">
									<h2>
										<?php echo __( 'How to set up an external cron job', 'wp-lister-for-amazon' ); ?>
									</h2>
									<p>
										<?php echo __( 'Luckily, you don\'t have to be a server admin to set up an external cron job.', 'wp-lister-for-amazon' ); ?>
										<?php echo __( 'You can ask your server admin to set up a cron job on your own server - or use a 3rd party web based cron service, which provides a user friendly interface and additional features for a small annual fee.', 'wp-lister-for-amazon' ); ?>
									</p>

									<h3>
										<?php echo __( 'Option A: Web cron service', 'wp-lister-for-amazon' ); ?>
									</h3>
									<p>
										<?php $ec_link = '<a href="https://www.easycron.com/" target="_blank">www.easycron.com</a>' ?>
										<?php echo sprintf( __( 'The easiest way to set up a cron job is to sign up with %s and use the following URL to create a new task.', 'wp-lister-for-amazon' ), $ec_link ); ?><br>
									</p>
									<code>
										<?php echo bloginfo('url') ?>/wp-admin/admin-ajax.php?action=wplister_run_scheduled_tasks
									</code>

									<h3>
										<?php echo __( 'Option B: Server cron job', 'wp-lister-for-amazon' ); ?>
									</h3>
									<p>
										<?php echo __( 'If you prefer to set up a cron job on your own server you can create a cron job that will execute the following command:', 'wp-lister-for-amazon' ); ?>
									</p>

									<code style="font-size:0.8em;">
										wget -q -O - <?php echo bloginfo('url') ?>/wp-admin/admin-ajax.php?action=wplister_run_scheduled_tasks >/dev/null 2>&1
									</code>

									<p>
										<?php echo __( 'Note: Your cron job should run at least every 15 minutes but not more often than every 5 minutes.', 'wp-lister-for-amazon' ); ?>
									</p>
								</div>

							<?php else: ?>
								<span style="color:darkred; font-weight:bold">
									Warning: Update schedule is disabled.
								</span></p><p>
								Please click the "Save Settings" button above in order to reset the update schedule.
							<?php endif; ?>
							</p>

							<?php if ( get_option('wpla_cron_last_run') ) : ?>
							<p>
								<?php echo __( 'Last run', 'wp-lister-for-amazon' ); ?>:
								<?php echo human_time_diff( get_option('wpla_cron_last_run'), current_time('timestamp',1) ) ?> ago
							</p>
							<?php endif; ?>

                            <?php if ( get_option('wpla_dedicated_orders_cron', 0) && get_option('wpla_orders_cron_last_run') ) : ?>
                                <p>
                                    <?php echo __( 'Orders last checked', 'wp-lister-for-amazon' ); ?>:
                                    <?php echo human_time_diff( get_option('wpla_orders_cron_last_run'), current_time('timestamp',1) ) ?> ago
                                </p>
                            <?php endif; ?>

						</div>
					</div>
					<?php endif; ?>

				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">

					<input type="hidden" name="action" value="save_wpla_settings" >
                    <?php wp_nonce_field( 'wpla_save_settings' ); ?>



					<div class="postbox" id="UpdateOptionBox">
						<h3 class="hndle"><span><?php echo __( 'Background Tasks', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">
							<!-- <p><?php echo __( 'Enable to update listings and transactions using WP-Cron.', 'wp-lister-for-amazon' ); ?></p> -->

							<label for="wpl-option-cron_schedule" class="text_label">
								<?php echo __( 'Update interval', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Select how often WP-Lister should run background jobs like checking for new sales on Amazon, submitting pending feeds and checking for processing results, etc.<br><br>It is recommended to use an external cron job or set this interval to 5 - 15 minutes.<br><br>Setting the update interval <i>to manually</i> will disable all background tasks and should only be used for testing and debuging but never on a live production site.') ?>
							</label>
							<select id="wpl-option-cron_schedule" name="wpla_option_cron_schedule" class=" required-entry select">
								<option value="five_min" 	<?php if ( $wpl_option_cron_schedule == 'five_min'    ): ?>selected="selected"<?php endif; ?>><?php echo __( '5 min.', 'wp-lister-for-amazon' ) ?></option>
								<option value="ten_min" 	<?php if ( $wpl_option_cron_schedule == 'ten_min'     ): ?>selected="selected"<?php endif; ?>><?php echo __( '10 min.', 'wp-lister-for-amazon' ) ?></option>
								<option value="fifteen_min" <?php if ( $wpl_option_cron_schedule == 'fifteen_min' ): ?>selected="selected"<?php endif; ?>><?php echo __( '15 min.', 'wp-lister-for-amazon' ) ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="thirty_min" 	<?php if ( $wpl_option_cron_schedule == 'thirty_min'  ): ?>selected="selected"<?php endif; ?>><?php echo __( '30 min.', 'wp-lister-for-amazon' ) ?></option>
								<option value="hourly" 		<?php if ( $wpl_option_cron_schedule == 'hourly'      ): ?>selected="selected"<?php endif; ?>><?php echo __( 'hourly', 'wp-lister-for-amazon' ) ?></option>
								<option value="daily" 		<?php if ( $wpl_option_cron_schedule == 'daily'       ): ?>selected="selected"<?php endif; ?>><?php echo __( 'daily', 'wp-lister-for-amazon' ) ?> (<?php echo __('not recommended', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="" 			<?php if ( $wpl_option_cron_schedule == ''            ): ?>selected="selected"<?php endif; ?>><?php echo __( 'manually', 'wp-lister-for-amazon' ) ?> (<?php echo __('not recommended', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="external" 	<?php if ( $wpl_option_cron_schedule == 'external'    ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Use external cron job', 'wp-lister-for-amazon' ) ?> (<?php echo __('recommended', 'wp-lister-for-amazon' ) ?>)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Select how often to run background jobs, like checking for new sales on Amazon.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-option-dedicated_orders_cron" class="text_label">
                                <?php echo __( 'Dedicated orders cron', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Create a dedicated cron job solely for checking and importing orders from Amazon.') ?>
                            </label>
                            <select id="wpl-option-dedicated_orders_cron" name="wpla_dedicated_orders_cron" class=" required-entry select">
                                <option value="0" <?php selected( $wpl_dedicated_orders_cron, 0 ); ?>><?php _e( 'No', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
                                <option value="1" <?php selected( $wpl_dedicated_orders_cron, 1 ); ?>><?php _e( 'Yes', 'wp-lister-for-amazon' ); ?></option>
                            </select>
                            <p class="desc" style="display: block;">
                                <?php echo __( 'Create a dedicated cron job solely for checking and importing orders from Amazon.', 'wp-lister-for-amazon' ); ?>
                            </p>


					        <!-- ## BEGIN PRO ## -->
							<label for="wpl-option-sync_inventory" class="text_label">
								<?php echo __( 'Synchronize sales', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Do you want WP-Lister to reduce the stock quantity in WooCommerce when an item is sold on Amazon - and vice versa?') ?>
							</label>
							<select id="wpl-option-sync_inventory" name="wpla_option_sync_inventory" class=" required-entry select">
								<option value="1" <?php if ( $wpl_option_sync_inventory == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php echo __('recommended', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="0" <?php if ( $wpl_option_sync_inventory != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Automatically reduce the stock level in WooCommerce when an item is sold on Amazon, and vice versa.', 'wp-lister-for-amazon' ); ?>
							</p>
					        <!-- ## END PRO ## -->

						</div>
					</div>


					<div class="postbox" id="FBAOptionsBox">
						<h3 class="hndle"><span><?php echo __( 'Fulfillment by Amazon (FBA)', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">

							<label for="wpl-fba_enabled" class="text_label">
								<?php echo __( 'Enable FBA', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Enable this if you are using FBA for any or all of your products. This will automatically generate a daily FBA inventory feed and process it to keep WP-Lister up to date with your stock levels on FBA.') ?>
							</label>
							<select id="wpl-fba_enabled" name="wpla_fba_enabled" class=" required-entry select">
								<option value="0" <?php if ( $wpl_fba_enabled != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_fba_enabled == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Enable this if you are using Fulfillment by Amazon.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-fba_fulfillment_center_id" class="fba_option text_label">
								<?php echo __( 'Fulfillment Center', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Select either Amazon US or Amazon EU.') ?>
							</label>
							<select id="wpl-fba_fulfillment_center_id" name="wpla_fba_fulfillment_center_id" class="fba_option required-entry select">
								<option value="AMAZON_NA"  <?php if ( $wpl_fba_fulfillment_center_id == 'AMAZON_NA'  ): ?>selected="selected"<?php endif; ?>><?php echo 'Amazon US' ?> </option>
								<option value="AMAZON_EU"  <?php if ( $wpl_fba_fulfillment_center_id == 'AMAZON_EU'  ): ?>selected="selected"<?php endif; ?>><?php echo 'Amazon EU' ?> </option>
								<option value="AMAZON_CA"  <?php if ( $wpl_fba_fulfillment_center_id == 'AMAZON_CA'  ): ?>selected="selected"<?php endif; ?>><?php echo 'Amazon CA' ?> (experimental)</option>
								<option value="AMAZON_IN"  <?php if ( $wpl_fba_fulfillment_center_id == 'AMAZON_IN'  ): ?>selected="selected"<?php endif; ?>><?php echo 'Amazon IN' ?> (experimental)</option>
								<option value="AMAZON_AU"  <?php if ( $wpl_fba_fulfillment_center_id == 'AMAZON_AU'  ): ?>selected="selected"<?php endif; ?>><?php echo 'Amazon AU' ?> (experimental)</option>
								<option value="AMAZON_JP"  <?php if ( $wpl_fba_fulfillment_center_id == 'AMAZON_JP'  ): ?>selected="selected"<?php endif; ?>><?php echo 'Amazon JP' ?> (experimental)</option>
							</select>
							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'Select your Fullfillment Center ID.', 'wp-lister-for-amazon' ); ?>
							</p>

					        <!-- ## BEGIN PRO ## -->
							<label for="wpl-fba_default_delivery_sla" class="fba_option text_label">
								<?php echo __( 'Default Shipping service', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('This default value will be used for all orders which are automatically submitted to FBA.') ?>
							</label>
							<select id="wpl-fba_default_delivery_sla" name="wpla_fba_default_delivery_sla" class="fba_option required-entry select">
								<option value="Standard"   <?php if ( $wpl_fba_default_delivery_sla == 'Standard'  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Standard', 'wp-lister-for-amazon' ); ?> (3-5 business days)</option>
								<option value="Expedited"  <?php if ( $wpl_fba_default_delivery_sla == 'Expedited' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Expedited', 'wp-lister-for-amazon' ); ?> (2 business days)</option>
								<option value="Priority"   <?php if ( $wpl_fba_default_delivery_sla == 'Priority'  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Priority', 'wp-lister-for-amazon' ); ?> (1 business day)</option>
							</select>
							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'These default values will be used when orders are submitted automatically.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-fba_default_order_comment" class="fba_option text_label">
								<?php echo __( 'Default Packing Slip Comment', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('This default value will be used for all orders which are automatically submitted to FBA.') ?>
							</label>
							<input type="text" name="wpla_fba_default_order_comment" id="wpl-fba_default_order_comment" value="<?php echo $wpl_fba_default_order_comment; ?>" placeholder="Thank you for your order" class="fba_option text_input" />
							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'These default values will be used when orders are submitted automatically.', 'wp-lister-for-amazon' ); ?>
							</p>
					        <!-- ## END PRO ## -->

                            <label for="wpl-fba_stock_sync" class="fba_option text_label">
								<?php echo __( 'Enable FBA stock sync', 'wp-lister-for-amazon' ) ?>
								<?php wpla_tooltip('When processing an FBA Inventory Report, all FBA stock levels will be synchronized to WooCommerce.<br><br>Falling back to seller fulfillment will be disabled.') ?>
                            </label>
                            <select id="wpl-fba_stock_sync" name="wpla_fba_stock_sync" class="fba_option required-entry select">
                                <option value="0" <?php if ( $wpl_fba_stock_sync != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
                                <option value="1" <?php if ( $wpl_fba_stock_sync == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
                            </select>
                            <p class="desc fba_option" style="display: block;">
								<?php echo __( 'Enable this to sync FBA stock levels back to WooCommerce.', 'wp-lister-for-amazon' ); ?>
                            </p>

							<label for="wpl-fba_enable_fallback" class="fba_option text_label">
								<?php echo __( 'Fallback to Seller Fulfilled', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('With this option enabled, an item will be switched from FBA to being seller-fulfilled when there is no stock in FBA but there is still stock left in WooCommerce.<br><br><b>This setting cannot be enabled when FBA Stock Sync is enabled.</b>') ?>
							</label>
							<select id="wpl-fba_enable_fallback" name="wpla_fba_enable_fallback" class="fba_option required-entry select">
                                <option value="0" <?php if ( $wpl_fba_enable_fallback != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_fba_enable_fallback == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>
							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'Fall back to remaining WooCommerce stock when FBA stock reaches zero.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-fba_only_mode" class="fba_option text_label">
								<?php echo __( 'Enable FBA only mode', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('With this option enabled, WP-Lister will assume that all your items are fulfilled by Amazon, so it will enable FBA in all your product feeds automatically.<br><br>FBA Stock Sync will be enabled automatically, so when processing an FBA Inventory Report, all FBA stock levels will be synchronized to WooCommerce.<br><br>Falling back to seller fulfillment will be disabled.') ?>
							</label>
							<select id="wpl-fba_only_mode" name="wpla_fba_only_mode" class="fba_option required-entry select">
								<option value="0" <?php if ( $wpl_fba_only_mode != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_fba_only_mode == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>
							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'Enable this if all your products use FBA.', 'wp-lister-for-amazon' ); ?>
							</p>

					        <!-- ## BEGIN PRO ## -->
							<label for="wpl-fba_autosubmit_orders" class="fba_option text_label">
								<?php echo __( 'Enable Multi-Channel Fulfillment', 'wp-lister-for-amazon' ) ?>
								<?php // $allowed_order_statuses = apply_filters( 'wpla_mcf_enabled_order_statuses', array( 'wc-completed', 'wc-processing', 'wc-on-hold' ) ); ?>
								<?php $allowed_order_statuses = apply_filters( 'wpla_mcf_enabled_order_statuses', array( 'wc-completed', 'wc-processing' ) ); // removed on-hold for now - until "hold" FBA action is implemented ?>
                                <?php wpla_tooltip('This will check for new WooCommerce orders (within 24h) where all order line items are available in FBA and submit them to be fulfilled by Amazon automatically.<br>(only for orders with status: '.join( ', ', $allowed_order_statuses ).')') ?>
							</label>
							<select id="wpl-fba_autosubmit_orders" name="wpla_fba_autosubmit_orders" class="fba_option required-entry select">
								<option value="0" <?php if ( $wpl_fba_autosubmit_orders != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_fba_autosubmit_orders == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>
							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'Fulfill new WooCommerce orders via FBA automatically.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-fba_wc_shipping_options" class="fba_option text_label">
								<?php echo __( 'Enable FBA Shipping Options', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Enable this option to allow your customers to select from available FBA shipping options (Standard, Expedited, Priority) when purchasing FBA items on your website.<br><br>(Shows actual shipping fees from Amazon.<br>Only  if all cart items are available on FBA.<br>Requires Multi-Channel Fulfillment.)') ?>
							</label>
							<select id="wpl-fba_wc_shipping_options" name="wpla_fba_wc_shipping_options" class="fba_option required-entry select">
								<option value="0" <?php if ( $wpl_fba_wc_shipping_options != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_fba_wc_shipping_options == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>

							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'Enable FBA shipping options (Standard, Expedited, Priority) on WooCommerce checkout.', 'wp-lister-for-amazon' ); ?>
								<?php if ( $wpl_fba_wc_shipping_options == '1' ): ?>
									<a href="admin.php?page=wc-settings&amp;tab=shipping&amp;section=wpla_shipping_method">Settings</a>
								<?php endif; ?>
							</p>

							<label for="wpl-fba_default_notification" class="fba_option text_label">
								<?php echo __( 'Enable customer notification', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('This will use the customer billing email address as <i>NotificationEmail</i> when submitting orders to FBA.') ?>
							</label>
							<select id="wpl-fba_default_notification" name="wpla_fba_default_notification" class="fba_option required-entry select">
								<option value="0" <?php if ( $wpl_fba_default_notification != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_fba_default_notification == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>

							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'Let Amazon notify the customer about FBA shipments.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-fba_complete_shipped_orders" class="fba_option text_label">
                                <?php echo __( 'Complete WooCommerce Orders', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('This updates the WooCommerce order when the FBA order submission is successful.') ?>
                            </label>
                            <select id="wpl-fba_complete_shipped_orders" name="wpla_fba_complete_shipped_orders" class="fba_option required-entry select">
                                <option value="0" <?php selected( $wpl_fba_complete_shipped_orders, 0 ); ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
                                <option value="1" <?php selected( $wpl_fba_complete_shipped_orders, 1 ); ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
                            </select>

                            <p class="desc fba_option" style="display: block;">
                                <?php echo __( 'Update WooCommerce orders\' status when the FBA order submission is successful. Uses the <b>Status for Shipped Orders</b> setting below.', 'wp-lister-for-amazon' ); ?>
                            </p>
					        <!-- ## END PRO ## -->

							<label for="wpl-fba_report_schedule" class="fba_option text_label">
								<?php echo __( 'Request FBA reports', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('If you use multi-channel fulfillment with eBay orders, you should lower this option to 6 hours.') ?>
							</label>
							<select id="wpl-fba_report_schedule" name="wpla_fba_report_schedule" class="fba_option required-entry select">
								<option value="daily"        <?php if ( $wpl_fba_report_schedule == 'daily'        ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Daily', 'wp-lister-for-amazon' ) ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
								<option value="twelve_hours" <?php if ( $wpl_fba_report_schedule == 'twelve_hours' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Every 12 hours', 'wp-lister-for-amazon' ) ?></option>
								<option value="six_hours"    <?php if ( $wpl_fba_report_schedule == 'six_hours'    ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Every 6 hours', 'wp-lister-for-amazon' ) ?></option>
								<option value="three_hours"  <?php if ( $wpl_fba_report_schedule == 'three_hours'  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Every 3 hours', 'wp-lister-for-amazon' ) ?></option>
							</select>
							<p class="desc fba_option" style="display: block;">
								<?php echo __( 'Select how often FBA Shipment and Inventory Reports should be fetched from Amazon.', 'wp-lister-for-amazon' ); ?>
							</p>

						</div>
					</div>


			        <!-- ## BEGIN PRO ## -->
					<div class="postbox" id="OtherSettingsBox">
						<h3 class="hndle"><span><?php echo __( 'WooCommerce orders', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">

							<label for="wpl-option-create_orders" class="text_label">
								<?php echo __( 'Create orders', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Enable this if you want WP-Lister to create orders in WooCommerce from sales on Amazon.') ?>
							</label>
							<select id="wpl-option-create_orders" name="wpla_option_create_orders" class=" required-entry select">
								<option value="1" <?php if ( $wpl_option_create_orders == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php echo __('recommended', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="0" <?php if ( $wpl_option_create_orders != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Enable this to create orders in WooCommerce from sales on Amazon.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-option-new_order_status" class="text_label">
								<?php echo __( 'Status for unshipped orders', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Select the WooCommerce order status for orders which have been completed on Amazon and are waiting to be shipped.<br>The default status is <i>Processing</i>.') ?>
							</label>
							<select id="wpl-option-new_order_status" name="wpla_option_new_order_status" class=" required-entry select">
								<?php foreach ( wc_get_order_statuses() as $status_slug => $status_name ) : ?>
									<?php $status_slug = str_replace( 'wc-', '', $status_slug ); ?>
									<option value="<?php echo $status_slug ?>" <?php if ( $wpl_option_new_order_status == $status_slug ): ?>selected="selected"<?php endif; ?>><?php echo $status_name ?>
								<?php endforeach; ?>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Select the WooCommerce order status for orders which have been completed on Amazon and are waiting to be shipped.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-option-shipped_order_status" class="text_label">
								<?php echo __( 'Status for shipped orders', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Select the WooCommerce order status for orders which have been completed on Amazon and are marked as shipped.<br>The default status is <i>Completed</i>.') ?>
							</label>
							<select id="wpl-option-shipped_order_status" name="wpla_option_shipped_order_status" class=" required-entry select">
								<?php foreach ( wc_get_order_statuses() as $status_slug => $status_name ) : ?>
									<?php $status_slug = str_replace( 'wc-', '', $status_slug ); ?>
									<option value="<?php echo $status_slug ?>" <?php if ( $wpl_option_shipped_order_status == $status_slug ): ?>selected="selected"<?php endif; ?>><?php echo $status_name ?>
								<?php endforeach; ?>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Select the WooCommerce order status for orders which have been completed on Amazon and are marked as shipped.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-option-cancelled_order_status" class="text_label">
                                <?php echo __( 'Status for cancelled orders', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Select the WooCommerce order status for orders which have been cancelled on Amazon.<br>The default status is <i>On-hold</i>.') ?>
                            </label>
                            <select id="wpl-option-cancelled_order_status" name="wpla_option_cancelled_order_status" class=" required-entry select">
                                <?php foreach ( wc_get_order_statuses() as $status_slug => $status_name ) : ?>
                                <?php $status_slug = str_replace( 'wc-', '', $status_slug ); ?>
                                <option value="<?php echo $status_slug ?>" <?php if ( $wpl_option_cancelled_order_status == $status_slug ): ?>selected="selected"<?php endif; ?>><?php echo $status_name ?>
                                    <?php endforeach; ?>
                            </select>
                            <p class="desc" style="display: block;">
                                <?php echo __( 'Select the WooCommerce order status for orders which have been cancelled on Amazon.', 'wp-lister-for-amazon' ); ?>
                            </p>

							<label for="wpl-option-use_amazon_order_number" class="text_label">
								<?php echo __( 'Use Amazon Order Number', 'wp-lister-for-amazon' ) ?>
								<?php wpla_tooltip('Enable this if you want WP-Lister use the order number from Amazon when creating new orders') ?>
							</label>
							<select id="wpl-option-use_amazon_order_number" name="wpla_option_use_amazon_order_number" class=" required-entry select">
								<option value="1" <?php selected( $wpl_option_use_amazon_order_number, 1 ); ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
								<option value="0" <?php selected( $wpl_option_use_amazon_order_number, 0 ); ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Enable to use the original order number from Amazon when creating orders in WooCommerce.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-text-amazon_order_id_storage" class="text_label">
                                <?php echo __( 'Store Amazon order ID as', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Select where WP-Lister should store the Amazon Order ID when creating WooCommerce orders.') ?>
                            </label>
                            <select id="wpl-option-amazon_order_id_storage" name="wpla_amazon_order_id_storage" class=" required-entry select">
                                <option value="notes" <?php selected( $wpl_amazon_order_id_storage, 'notes' ); ?>><?php echo __( 'Order Notes', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
                                <option value="excerpt" <?php selected( $wpl_amazon_order_id_storage, 'excerpt' ); ?>><?php echo __( 'Customer Note', 'wp-lister-for-amazon' ); ?> (<?php echo __('legacy', 'wp-lister-for-amazon' ) ?>)</option>
                                <option value="both" <?php selected( $wpl_amazon_order_id_storage, 'both' ); ?>><?php echo __( 'Customer Note and Order Note', 'wp-lister-for-amazon' ); ?></option>
                            </select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Select where to store the Amazon Order ID when creating orders in WooCommerce.', 'wp-lister-for-amazon' ); ?><br>
							</p>

                            <label for="wpl-text-amazon_store_sku_as_order_meta" class="text_label">
                                <?php echo __( 'Store SKU as line item meta field', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Disable this option if you do not want the SKU to appear in a separate row in WooCommerce') ?>
                            </label>
                            <select id="wpl-option-amazon_store_sku_as_order_meta" name="wpla_amazon_store_sku_as_order_meta" class=" required-entry select">
                                <option value="1" <?php selected( $wpl_amazon_store_sku_as_order_meta, '1' ); ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
                                <option value="0" <?php selected( $wpl_amazon_store_sku_as_order_meta, '0' ); ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
                            </select>
                            <p class="desc" style="display: block;">
								<?php echo __( 'The order item SKU is displayed as an order item meta by default. Disable this if you see two SKUs in your order items.', 'wp-lister-for-amazon' ); ?><br>
                            </p>

							<label for="wpl-skip_foreign_item_orders" class="text_label">
								<?php echo __( 'Skip orders for foreign items', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('If you have items listed on Amazon which do not exist in WP-Lister, you can enable this option to skip orders which do not contain any known order line items.<br><br>Orders which contain both known and foreign items will still be created in WooCommerce.') ?>
							</label>
							<select id="wpl-skip_foreign_item_orders" name="wpla_skip_foreign_item_orders" class=" required-entry select">
								<option value="0" <?php if ( $wpl_skip_foreign_item_orders != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
								<option value="1" <?php if ( $wpl_skip_foreign_item_orders == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Enable this to create orders in WooCommerce only for items which exist in WP-Lister.', 'wp-lister-for-amazon' ); ?><br>
							</p>

                            <label for="wpl-order_item_matching_mode" class="text_label">
                                <?php echo __( 'Match order items using', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('If you are seeing orders getting skipped due to the order items having different ASINs, set this to match using SKU.') ?>
                            </label>
                            <select id="wpl-order_item_matching_mode" name="wpla_order_item_matching_mode" class=" required-entry select">
                                <option value="asin" <?php selected( $wpl_order_item_matching_mode, 'asin' ); ?>><?php echo __( 'ASIN', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
                                <option value="sku" <?php selected( $wpl_order_item_matching_mode, 'sku' ); ?>><?php echo __( 'SKU', 'wp-lister-for-amazon' ); ?></option>
                            </select>
                            <p class="desc" style="display: block;">
                                <?php echo __( 'Select whether to use the ASIN or SKU to match Amazon listings to products.', 'wp-lister-for-amazon' ); ?><br>
                            </p>

							<label for="wpl-create_orders_without_email" class="text_label">
								<?php echo __( 'Leave email address empty', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Enable this option to create WooCommerce orders without email address.<br><br>The email addresses provided by Amazon are not real customer email addresses and should not be used for marketing purposes.') ?>
							</label>
							<select id="wpl-create_orders_without_email" name="wpla_create_orders_without_email" class="required-entry select">
								<option value=""  <?php if ( $wpl_create_orders_without_email == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="1" <?php if ( $wpl_create_orders_without_email == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php echo __('recommended', 'wp-lister-for-amazon' ) ?>)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Create orders without email addresses to make sure that no plugin sends marketing emails via Amazon.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-orders_default_payment_method" class="text_label">
                                <?php echo __( 'Payment gateway to use', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Select the WooCommerce payment gateway to assign the created orders to.') ?>
                            </label>
                            <select id="wpl-orders_default_payment_method" name="wpla_orders_default_payment_method" class="required-entry select">
                                <option value=""  <?php selected( $wpl_orders_default_payment_method, '' ); ?>><?php echo __( 'Other', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
                                <?php foreach ( $wpl_payment_methods as $method ): ?>
                                    <option value="<?php esc_attr_e( $method->id ); ?>" <?php selected( $wpl_orders_default_payment_method, $method->id ); ?>><?php echo $method->title; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="desc" style="display: block;">
                                <?php echo __( 'Select the WooCommerce payment gateway to assign the created orders to.', 'wp-lister-for-amazon' ); ?>
                            </p>

							<label for="wpl-text-orders_default_payment_title" class="text_label show-if-custom-payment-gateway">
								<?php echo __( 'Custom payment title', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('The payment method in Amazon orders often defaults to "Other". Enter your own payment title here which will be used instead of "Other" when creating orders in WooCommerce.') ?>
							</label>
							<input type="text" name="wpla_orders_default_payment_title" id="wpl-text-orders_default_payment_title" value="<?php echo $wpl_orders_default_payment_title; ?>" placeholder="Other" class="text_input show-if-custom-payment-gateway" />
							<p class="desc show-if-custom-payment-gateway" style="display: block;">
								<?php echo __( 'Enter your own payment title here which will be used instead of "Other" when creating orders in WooCommerce.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-record_gift_wrap_items" class="text_label">
                                <?php echo __( 'Record gift wrap line items', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Enable this to process Gift Wrap option and record it as an order fee in WooCommerce. ') ?>
                            </label>
                            <select id="wpl-orders_record_gift_wrap_items" name="wpla_orders_record_gift_wrap_items" class=" required-entry select">
                                <option value="1" <?php selected( $wpl_orders_record_gift_wrap_items, 1 ); ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
                                <option value="0" <?php selected( $wpl_orders_record_gift_wrap_items, 0 ); ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
                            </select>
                            <p class="desc" style="display: block;">
                                <?php echo __( 'Record Gift Wrap option as an order fee.', 'wp-lister-for-amazon' ); ?><br>
                            </p>

                        </div>
					</div>

                    <div class="postbox" id="OrderAttributionSettingsBox">
                        <h3 class="hndle"><span><?php echo __( 'WooCommerce Order Attribution Tracking', 'wp-lister-for-amazon' ) ?></span></h3>
                        <div class="inside">

                            <label for="wpl-order_utm_source" class="text_label">
                                <?php echo __( 'UTM Source', 'wp-lister-for-amazon' ) ?>
                            </label>
                            <input type="text" name="wpla_order_utm_source" id="wpl-order_utm_source" value="<?php echo esc_attr($wpl_order_utm_source); ?>" class="text_input" />

                            <label for="wpl-order_utm_campaign" class="text_label">
                                <?php echo __( 'UTM Campaign', 'wp-lister-for-amazon' ) ?>
                            </label>
                            <input type="text" name="wpla_order_utm_campaign" id="wpl-order_utm_campaign" value="<?php echo esc_attr($wpl_order_utm_campaign); ?>" class="text_input" />

                            <label for="wpl-order_utm_medium" class="text_label">
                                <?php echo __( 'UTM Medium', 'wp-lister-for-amazon' ) ?>
                            </label>
                            <input type="text" name="wpla_order_utm_medium" id="wpl-order_utm_medium" value="<?php echo esc_attr($wpl_order_utm_medium); ?>" class="text_input" />
                        </div>
                    </div>

					<div class="postbox" id="OrderProcessingSettingsBox">
						<h3 class="hndle"><span><?php echo __( 'Order Processing', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">

							<label for="wpl-auto_complete_sales" class="text_label">
								<?php echo __( 'Mark as shipped on Amazon', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('This completes an Amazon order with the shipping date set to today when the order status is changed to completed.<br>Only applicable if default new order status is <em>processing</em>.') ?>
							</label>
							<select id="wpl-auto_complete_sales" name="wpla_auto_complete_sales" class="required-entry select">
								<option value=""  <?php if ( $wpl_auto_complete_sales == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="1" <?php if ( $wpl_auto_complete_sales == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('recommended', 'wp-lister-for-amazon' ); ?>)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Automatically mark an order as shipped on Amazon when it is completed in WooCommerce.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-default_shipping_provider" class="text_label">
								<?php echo __( 'Default Shipping Provider', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Select which shipping provider should be used when marking orders as shipped automatically on Amazon.') ?>
							</label>
							<select id="wpl-default_shipping_provider" name="wpla_default_shipping_provider" class=" required-entry select">
								<option value=""   <?php if ( $wpl_default_shipping_provider == '' ):   ?>selected="selected"<?php endif; ?>>-- <?php echo __( 'none', 'wp-lister-for-amazon' ) ?> --</option>
				                <?php foreach (WPLA_Order_MetaBox::getShippingProviders() as $provider => $services ) : ?>
									<option value="<?php echo $provider ?>"   <?php if ( $wpl_default_shipping_provider == $provider ):   ?>selected="selected"<?php endif; ?>><?php echo $provider ?></option>
				                <?php endforeach; ?>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Select the default shipping provider to be used when marking orders as shipped on Amazon.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-default_shipping_service_name" class="text_label other_shipping_option">
								<?php echo __( 'Default Shipping Provider Name', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Enter the name of your shipping provider.') ?>
							</label>
							<input type="text" name="wpla_default_shipping_service_name" id="wpl-default_shipping_service_name" value="<?php echo esc_attr($wpl_default_shipping_service_name); ?>" class="text_input other_shipping_option" />
							<p class="desc" style="display: block;">
								<?php echo __( 'Enter the name of your shipping provider.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-default_shipping_method" class="text_label">
                                <?php echo __( 'Default Shipping Method', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Enter the name of your default shipping method.') ?>
                            </label>
                            <input type="text" name="wpla_default_shipping_method" id="wpl-default_shipping_method" value="<?php echo esc_attr($wpl_default_shipping_method); ?>" class="text_input" />
                            <p class="desc" style="display: block;">
                                <?php echo __( 'e.g. First Class', 'wp-lister-for-amazon' ); ?>
                            </p>

							<label for="wpl-option-record-discounts" class="text_label">
								<?php echo __( 'Apply Amazon discounts', 'wp-lister-for-amazon' ); ?>
								<?php wpla_tooltip('Import and apply discounts from Amazon') ?>
							</label>
							<select id="wpl-option-record-discounts" name="wpla_option_record_discounts" class=" required-entry select">
								<option value="0" <?php selected( 0, $wpl_option_record_discounts ); ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="1" <?php selected( 1, $wpl_option_record_discounts ); ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Import and apply discounts from Amazon.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-option-create_customers" class="text_label">
								<?php echo __( 'Create customers', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Enable this to create Amazon customers as WordPress users when creating orders.<br><br>Note: Amazon hides the customers real email address. It only provides an anonymized email address which will be used to create user accounts.') ?>
							</label>
							<select id="wpl-option-create_customers" name="wpla_option_create_customers" class=" required-entry select">
								<option value="0" <?php if ( $wpl_option_create_customers != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
								<option value="1" <?php if ( $wpl_option_create_customers == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Create WordPress user accounts for your customers.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-option-new_customer_role" class="text_label show-if-create-customers">
                                <?php echo __( 'Assign Customer Role', 'wp-lister-for-amazon' ) ?>
                            </label>
                            <select id="wpl-option-new_customer_role" name="wpla_option_new_customer_role" class=" required-entry select show-if-create-customers">
                                <?php
                                $roles = wp_roles();
                                foreach ( $roles->get_names() as $role => $name ):
                                ?>
                                <option value="<?php echo esc_attr( $role ); ?>" <?php selected( $wpl_option_new_customer_role, $role ); ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="desc show-if-create-customers" style="display: block;">
                                <?php echo __( 'Select the role that will be assigned to the added customers.', 'wp-lister-for-amazon' ); ?>
                            </p>

                            <label for="wpl-text-ignore_orders_before_ts" class="text_label">
                                <?php echo __( 'Ignore orders before', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Orders placed before this date will be ignored.') ?>
                            </label>
                            <input type="text" name="wpla_ignore_orders_before_ts" id="wpl-text-ignore_orders_before_ts" value="<?php echo esc_attr($wpl_ignore_orders_before_ts); ?>" placeholder="" class="text_input" />
                            <p class="desc">
                                <?php _e( 'Example', 'wp-lister-for-amazon' ); ?>:
								<?php echo gmdate('Y-m-d H:i:s T') ?>
                            </p>

							<label for="wpl-fetch_orders_filter" class="text_label">
								<?php echo __( 'Filter orders', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Select whether you want to import orders placed on all allowed marketplaces, or only orders placed on the marketplace defined in the account settings.<br><br>Please enable this option if you have added multiple Amazon sites / marketplaces using the same Seller ID.<br><br>Important: If you enable that option, you need to add an account for every marketplace you are selling on, or WP-Lister will not be able to fetch all orders.') ?>
							</label>
							<select id="wpl-fetch_orders_filter" name="wpla_fetch_orders_filter" class="required-entry select">
								<option value="0" <?php if ( $wpl_fetch_orders_filter == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No, import all orders', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
								<option value="1" <?php if ( $wpl_fetch_orders_filter == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes, import only orders from main marketplace', 'wp-lister-for-amazon' ); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Please enable this option if you have added multiple Amazon sites / marketplaces using the same Seller ID.<br><!br>Important: If you enable that option, you need to add an account for every marketplace you are selling on, or WP-Lister will not be able to fetch all orders.', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-revert_stock_changes" class="text_label">
                                <?php echo __( 'Revert stock changes on cancelled orders', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip(__('WP-Lister reverts all stock changes by default when an order gets cancelled on Amazon. The exception to this is if the status of the WooCommerce order is already <code>refunded</code> or <code>cancelled</code>', 'wp-lister-for-amazon') ) ?>
                            </label>
                            <select id="wpl-revert_stock_changes" name="wpla_revert_stock_changes" class="required-entry select">
                                <option value="1" <?php selected( $wpl_revert_stock_changes, 1 ); ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon' ); ?>)</option>
                                <option value="0" <?php selected( $wpl_revert_stock_changes, 0 ); ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
                            </select>
                            <p class="desc" style="display: block;">
                                <?php echo __( 'Disable this if you are overselling due to the stocks getting added back when cancelling an order on Amazon', 'wp-lister-for-amazon' ); ?>
                            </p>

                        </div>
					</div>

					<div class="postbox" id="OrderNotificationSettingsBox">
						<h3 class="hndle"><span><?php echo __( 'Order Notifications', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">

							<p>
								<?php echo __( 'WooCommerce sends out various notifications when an order status is changed.', 'wp-lister-for-amazon' ); ?>
								<?php echo __( 'You can disable these emails when Amazon orders are created in WooCommerce.', 'wp-lister-for-amazon' ); ?>
							</p>


							<label for="wpl-disable_new_order_emails" class="text_label">
								<?php echo __( 'Disable New Order emails', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Disable New Order notifications being sent to the admin when an Amazon order is created.') ?>
							</label>
							<select id="wpl-disable_new_order_emails" name="wpla_disable_new_order_emails" class="required-entry select">
								<option value=""  <?php if ( $wpl_disable_new_order_emails == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_disable_new_order_emails == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('recommended', 'wp-lister-for-amazon' ); ?>)</option>
							</select>

                            <label for="wpl-disable_on_hold_order_emails" class="text_label">
                                <?php echo __( 'Disable On-hold Order emails', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Disable email notifications being sent to the customer when an Amazon order is created with status on-hold.') ?>
                            </label>
                            <select id="wpl-disable_on_hold_order_emails" name="wpla_disable_on_hold_order_emails" class="required-entry select">
                                <option value="0"  <?php selected( $wpl_disable_on_hold_order_emails, 0 ); ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
                                <option value="1" <?php selected( $wpl_disable_on_hold_order_emails, 1 ); ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('recommended', 'wp-lister-for-amazon' ); ?>)</option>
                            </select>

							<label for="wpl-disable_processing_order_emails" class="text_label">
								<?php echo __( 'Disable Processing Order emails', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Disable email notifications being sent to the customer when an Amazon order is created with status processing.') ?>
							</label>
							<select id="wpl-disable_processing_order_emails" name="wpla_disable_processing_order_emails" class="required-entry select">
								<option value=""  <?php if ( $wpl_disable_processing_order_emails == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_disable_processing_order_emails == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('recommended', 'wp-lister-for-amazon' ); ?>)</option>
							</select>

							<label for="wpl-disable_completed_order_emails" class="text_label">
								<?php echo __( 'Disable Completed Order emails', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Disable email notifications being sent to the customer when an Amazon order is created with status completed.') ?>
							</label>
							<select id="wpl-disable_completed_order_emails" name="wpla_disable_completed_order_emails" class="required-entry select">
								<option value=""  <?php if ( $wpl_disable_completed_order_emails == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_disable_completed_order_emails == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('recommended', 'wp-lister-for-amazon' ); ?>)</option>
							</select>

							<label for="wpl-disable_changed_order_emails" class="text_label">
								<?php echo __( 'Disable emails on status change', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Disable email notifications being sent to the customer when the order status of an Amazon order is changed manually.') ?>
							</label>
							<select id="wpl-disable_changed_order_emails" name="wpla_disable_changed_order_emails" class="required-entry select">
								<option value=""  <?php if ( $wpl_disable_changed_order_emails == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_disable_changed_order_emails == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('recommended', 'wp-lister-for-amazon' ); ?>)</option>
							</select>

							<label for="wpl-disable_new_account_emails" class="text_label">
								<?php echo __( 'Disable New Account emails', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Disable New Account notifications being sent to the user when a customer account is created.') ?>
							</label>
							<select id="wpl-disable_new_account_emails" name="wpla_disable_new_account_emails" class="required-entry select">
								<option value=""  <?php if ( $wpl_disable_new_account_emails == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?></option>
								<option value="1" <?php if ( $wpl_disable_new_account_emails == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?> (<?php _e('recommended', 'wp-lister-for-amazon' ); ?>)</option>
							</select>

							<!--
							<p class="desc" style="display: block;">
								<?php //echo __( 'WooCommerce sends out various notifications when an order status is changed.', 'wp-lister-for-amazon' ); ?><br>
								<?php //echo __( 'WP-Lister can disable these notifications when creating Amazon orders in WooCommerce.', 'wp-lister-for-amazon' ); ?>
							</p>
							-->

						</div>
					</div>
					<div class="postbox" id="OrderTaxSettingsBox">
						<h3 class="hndle"><span><?php echo __( 'Order Taxes', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">


                            <label for="wpl-option-orders-tax-mode" class="text_label">
                                <?php _e( 'Order Tax Processing', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip( 'How should WP-Lister handle order taxes?<br/><br/><strong>None</strong><br/>No taxes will be processed.<br/><br/><strong>Automatically detect taxes</strong><br/>Automatically calculate the taxes based on the products\' tax class and your WooCommerce tax rates.<br/><br/><strong>Fixed Rate (VAT)</strong><br/>Set a global VAT rate for all order items.<br/><br/><strong>Import from Amazon</strong><br/>Use sales tax data as provided by Amazon.'); ?>
                            </label>
                            <select id="wpl-option-orders-tax-mode" name="wpla_orders_tax_mode" class="required-entry select">
                                <option value=""            <?php selected( $wpl_orders_tax_mode, '' ); ?>><?php _e( 'None', 'wp-lister-for-amazon' ); ?></option>
                                <option value="autodetect"  <?php selected( $wpl_orders_tax_mode, 'autodetect' ); ?>><?php _e( 'Automatically detect taxes', 'wp-lister-for-amazon' ); ?></option>
                                <option value="fixed"       <?php selected( $wpl_orders_tax_mode, 'fixed' ); ?>><?php _e( 'Fixed rate (VAT)', 'wp-lister-for-amazon' ); ?></option>
                                <option value="import"      <?php selected( $wpl_orders_tax_mode, 'import' ); ?>><?php _e( 'Import from Amazon', 'wp-lister-for-amazon' ); ?></option>
                            </select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Select how to process taxes on orders imported from Amazon.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-option-orders_tax_rate_id" class="text_label fixed_tax import_tax">
								<?php echo __( 'Tax Rate', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('Select the WooCommerce tax rate that will used for creating orders.<br>(Required for both "Fixed Rate" and "Import" tax processing mode.)<br><br>When importing taxes from Amazon, the selected tax rate will only be used as a label in the WooCommerce orders.') ?>
							</label>
							<select id="wpl-option-orders_tax_rate_id" name="wpla_orders_tax_rate_id" class="required-entry select fixed_tax import_tax">
								<option value="">-- <?php echo __( 'no tax rate', 'wp-lister-for-amazon' ); ?> --</option>
								<option value="autodetect" <?php selected( $wpl_orders_tax_rate_id, 'autodetect' ); ?>><?php echo __( 'Automatic detection', 'wp-lister-for-amazon' ); ?></option>
								<?php foreach ($wpl_tax_rates as $rate) :
                                   $tax_rate_country = empty($rate->tax_rate_country) ? '*' : $rate->tax_rate_country;
                                    ?>
									<option value="<?php echo $rate->tax_rate_id ?>" <?php if ( $wpl_orders_tax_rate_id == $rate->tax_rate_id ): ?>selected="selected"<?php endif; ?>><?php echo $rate->tax_rate_name .' ('. $tax_rate_country .')' ?></option>
								<?php endforeach; ?>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __( 'Select the WooCommerce tax rate that will used for creating orders.', 'wp-lister-for-amazon' ); ?>
							</p>

							<label for="wpl-text-orders_fixed_vat_rate" class="text_label fixed_tax">
								<?php echo __( 'Tax Rate (percent)', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('To apply VAT to created orders, enter the tax rate here.<br>Example: For 19% VAT enter "19".') ?>
							</label>
							<input type="text" name="wpla_orders_fixed_vat_rate" id="wpl-text-orders_fixed_vat_rate" value="<?php echo esc_attr($wpl_orders_fixed_vat_rate); ?>" class="text_input fixed_tax" />
							<p class="desc" style="display: block;">
								<?php echo __( 'To apply VAT to created orders, enter the tax rate here. Example: For 19% VAT enter "19".', 'wp-lister-for-amazon' ); ?>
							</p>

                            <label for="wpl-option-order_sales_tax_action" class="text_label">
                                <?php echo __( 'Sales Tax Action', 'wp-lister-for-amazon' ) ?>
                                <?php wpla_tooltip('With Amazon collecting taxes on behalf of the sellers, the order totals often become inaccurate. Use this setting to control how the Sales Tax should be handled in your WooCommerce orders.<br><br><b>Ignore:</b> Sales tax will be ignored and orders will be left as is.<br/><b>Remove:</b> Sales tax amount will be deducted from the order total.<br/><b>Record:</b> The sales tax will be recorded as an order tax.') ?>
                            </label>
                            <select id="wpl-option-orders_sales_tax_action" name="wpla_orders_sales_tax_action" class="required-entry select">
                                <option value="ignore" <?php selected( $wpl_orders_sales_tax_action, 'ignore' ); ?>><?php _e( 'Ignore', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon'); ?>)</option>
                                <option value="remove" <?php selected( $wpl_orders_sales_tax_action, 'remove' ); ?>><?php _e( 'Remove', 'wp-lister-for-amazon' ); ?></option>
                                <option value="record" <?php selected( $wpl_orders_sales_tax_action, 'record' ); ?>><?php _e( 'Record', 'wp-lister-for-amazon' ); ?></option>
                            </select>

                            <label for="wpl-option-order_sales_tax_rate_id" class="text_label show-if-sales-tax-record">
                                <?php _e( 'Sales Tax rate', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip( 'Set the tax rate to assign to the sales tax from Amazon' ); ?>
                            </label>
                            <select id="wpl-option-orders_sales_tax_rate_id" name="wpla_orders_sales_tax_rate_id" class="required-entry select show-if-sales-tax-record">
                                <option value="">-- <?php echo __( 'no tax rate', 'wp-lister-for-amazon' ); ?> --</option>
                                <?php foreach ($wpl_tax_rates as $rate) :
	                                $tax_rate_country = empty($rate->tax_rate_country) ? '*' : $rate->tax_rate_country;
                                    ?>
                                    <option value="<?php echo $rate->tax_rate_id ?>" <?php selected( $wpl_orders_sales_tax_rate_id, $rate->tax_rate_id ); ?>><?php echo $rate->tax_rate_name .' ('. $tax_rate_country .')'; ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label for="wpl-option-orders_force_prices_include_tax" class="text_label">
								<?php _e( 'Prices Include Tax Override', 'wp-lister-for-amazon' ); ?>
								<?php wpla_tooltip( 'This is applicable if your order totals do not match or the line item totals are incorrect' ); ?>
                            </label>
                            <select id="wpl-option-orders_force_prices_include_tax" name="wpla_orders_force_prices_include_tax" class="required-entry select">
                                <option value="ignore"      <?php selected( $wpl_orders_force_prices_include_tax, 'ignore' ); ?>     ><?php echo __( 'Use WooCommerce Setting', 'wp-lister-for-amazon' ); ?> (<?php _e('default', 'wp-lister-for-amazon'); ?>)</option>
                                <option value="force_yes"   <?php selected( $wpl_orders_force_prices_include_tax, 'force_yes' ); ?>  ><?php echo __( 'Prices inclusive of tax', 'wp-lister-for-amazon' ); ?></option>
                                <option value="force_no"    <?php selected( $wpl_orders_force_prices_include_tax, 'force_no' ); ?>   ><?php echo __( 'Prices exclusive of tax', 'wp-lister-for-amazon' ); ?></option>
                            </select>
                            <p class="desc" style="display: block;">
								<?php echo __( 'Choose to override if you need WP-Lister to include/exclude taxes in the prices coming from Amazon.', 'wp-lister-for-amazon' ); ?>
                            </p>

                            <label for="wpl-option-orders_force_deduct_shipping_tax" class="text_label">
								<?php echo __( 'Deduct Shipping Tax from Shipping Total', 'wp-lister-for-amazon' ); ?>
								<?php wpla_tooltip('Subtract the shipping tax from the shipping total.') ?>
                            </label>
                            <select id="wpl-option-orders_force_deduct_shipping_tax" name="wpla_orders_force_deduct_shipping_tax" class=" required-entry select">
                                <option value="0" <?php selected( 0, $wpl_orders_force_deduct_shipping_tax ); ?>><?php echo __( 'No', 'wp-lister-for-amazon' ); ?> (<?php echo __('default', 'wp-lister-for-amazon' ) ?>)</option>
                                <option value="1" <?php selected( 1, $wpl_orders_force_deduct_shipping_tax ); ?>><?php echo __( 'Yes', 'wp-lister-for-amazon' ); ?></option>
                            </select>
                            <p class="desc" style="display: block;">
								<?php echo __( 'Enable this if your shipping total is incorrect due to tax being added on top of it.', 'wp-lister-for-amazon' ); ?>
                            </p>

						</div>
					</div>
			        <!-- ## END PRO ## -->


				<?php if ( ( is_multisite() ) && ( is_main_site() ) ) : ?>
				<p>
					<b>Warning:</b> Deactivating WP-Lister on a multisite network will remove all settings and data from all sites.
				</p>
				<?php endif; ?>


				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 -->



		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>






	<script type="text/javascript">
		jQuery( document ).ready( function($) {

			// hide FBA options if FBA is disabled
			$('#wpl-fba_enabled').change(function() {
				if ( $('#wpl-fba_enabled').val() != 1 ) {
					$('#FBAOptionsBox .fba_option').hide();
				} else {
					$('#FBAOptionsBox .fba_option').show();
				}
			}).change();

			// hide shipping provider name option unless "Other" is selected
			$('#wpl-default_shipping_provider').change(function() {
				if ( $('#wpl-default_shipping_provider').val() != 'Other' ) {
					$('#OtherSettingsBox .other_shipping_option').hide();
				} else {
					$('#OtherSettingsBox .other_shipping_option').show();
				}
			}).change();

			// Toggle Fixed Tax elements
            $('#wpl-option-orders-tax-mode').change(function() {

                // initially hide the elements
                $('.fixed_tax, .import_tax').hide();

                if ( $(this).val() == 'fixed' ) {
                    $('.fixed_tax').show();
                } else if ( $(this).val() == 'import' ) {
                    $('.import_tax').show();
                }
            }).change();

            $('#wpl-option-orders_sales_tax_action').change(function() {
                $('.show-if-sales-tax-record').hide();

                if ( $(this).val() == 'record' ) $('.show-if-sales-tax-record').show();
            }).change();

            // Toggle customer role select
            $('#wpl-option-create_customers').change(function() {
                if ( $(this).val() == 0 ) {
                    $(".show-if-create-customers").hide();
                } else {
                    $(".show-if-create-customers").show();
                }
            }).change();

            $('#wpl-orders_default_payment_method').change(function() {
                if ( $(this).val() == "" ) {
                    $(".show-if-custom-payment-gateway").show();
                } else {
                    $(".show-if-custom-payment-gateway").hide();
                }
            }).change();



		});

	</script>


</div>
