<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">
	#LicenseBox .checkbox_input {
		/*margin-top: 5px;*/
		/*margin-left: 5px;*/
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
						<h3 class="hndle"><span><?php echo __( 'Update', 'wp-lister-for-amazon' ); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">
									<div class="misc-pub-section">
										<?php if ( class_exists('WPLA_Update_API') ) : ?>
											<p><?php echo __( 'To find your latest license key, please visit your account on wplab.com.', 'wp-lister-for-amazon' ) ?></p>
										<?php else : ?>
											<p><?php echo __( 'Note: You are still using the old update API. Please switch to the new updater and enter your new license key.', 'wp-lister-for-amazon' ) ?></p>
										<?php endif; ?>
									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_wpla_license" >
                                        <?php wp_nonce_field( 'wpla_save_license' ); ?>
										<?php if ( $wpl_license_activated == '1' ) : ?>
											<input type="submit" value="<?php echo __( 'Update license', 'wp-lister-for-amazon' ); ?>" id="save_settings" class="button-primary" name="save">
										<?php else : ?>
											<input type="submit" value="<?php echo __( 'Activate license', 'wp-lister-for-amazon' ); ?>" id="save_settings" class="button-primary" name="save">
										<?php endif; ?>
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>

					<div class="postbox" id="VersionInfoBox">
						<h3 class="hndle"><span><?php echo __( 'Version Info', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">

							<table style="width:100%">
								<tr><td>WP-Lister</td><td>	<?php echo WPLA_VERSION ?> </td></tr>
								<tr><td>Database</td><td> <?php echo get_option('wpla_db_version') ?> </td></tr>
								<tr><td>WordPress</td><td> <?php global $wp_version; echo $wp_version ?> </td></tr>
								<tr><td>WooCommerce</td><td> <?php echo defined('WC_VERSION') ? WC_VERSION : WOOCOMMERCE_VERSION ?> </td></tr>
							</table>

						</div>
					</div>

				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">

					<div class="postbox" id="LicenseBox" style="">
						<h3 class="hndle"><span><?php echo __( 'License', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">

							<label for="wpl-text-license_email" class="text_label">
								<?php echo __( 'License email', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Your license email is the email address you used for purchasing WP-Lister for Amazon.') ?>
							</label>
							<input type="text" name="wpla_text_license_email" id="wpl-text-license_email" value="<?php echo esc_attr($wpl_text_license_email); ?>" class="text_input" <?php if ( $wpl_license_activated == '1' && !empty($wpl_text_license_email) ) : ?>disabled<?php endif; ?> />

							<label for="wpl-text-license_key" class="text_label">
								<?php echo __( 'License key', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('You can find you license key in your order confirmation email which you have received right after your purchase.<br>If you have lost your license key please visit the <i>Lost License</i> page on wplab.com.') ?>
							</label>
							<input type="text" name="wpla_text_license_key" id="wpl-text-license_key" value="<?php echo esc_attr($wpl_text_license_key); ?>" class="text_input" <?php if ( $wpl_license_activated == '1' && !empty($wpl_text_license_email) ) : ?>disabled<?php endif; ?> />

							<?php if ( $wpl_license_activated == '1' ) : ?>

								<label for="wpl-deactivate_license" class="text_label">
									<?php echo __( 'Deactivate license', 'wp-lister-for-amazon' ); ?>
	                                <?php wpla_tooltip('You can deactivate your license on this site any time and activate it again on a different site or domain.') ?>
								</label>
								<input type="checkbox" name="wpla_deactivate_license" id="wpl-deactivate_license" value="1" class="checkbox_input" />
								<span style="line-height: 30px">
									<?php echo __( 'Yes, I want to deactivate this license for', 'wp-lister-for-amazon' ); ?>
									<i><?php echo str_replace( 'http://','', get_bloginfo( 'url' ) ) ?></i>
								</span>
							
							<?php elseif ( $wpl_text_license_key && $wpl_text_license_email ) : ?>
								
								<p class="desc" style="color:darkred;">
									<?php echo __( 'Your license is currently deactivated on this site.', 'wp-lister-for-amazon' ); ?>
								</p>

							<?php endif; ?>
						
						</div>
					</div>

					<?php if ( ( ! is_multisite() ) || ( is_main_site() ) ) : ?>
					<div class="postbox" id="UpdateSettingsBox">
						<h3 class="hndle"><span><?php echo __( 'Beta testers', 'wp-lister-for-amazon' ) ?></span></h3>
						<div class="inside">

							<p>
								<?php echo __( 'If you want to test new features before they are released, select the "beta" channel.', 'wp-lister-for-amazon' ); ?>
							</p>
							<label for="wpl-option-update_channel" class="text_label">
								<?php echo __( 'Update channel', 'wp-lister-for-amazon' ); ?>
                                <?php wpla_tooltip('Please keep in mind that beta versions might have known bugs or experimental features. Unless WP Lab support told you to update to the latest beta version, it is recommended to keep the update channel set to <i>stable</i>.') ?>
							</label>
							<select id="wpl-option-update_channel" name="wpla_update_channel" title="Update channel" class=" required-entry select">
								<option value="stable"  <?php if ( $wpl_update_channel == 'stable'  ): ?>selected="selected"<?php endif; ?>><?php echo 'stable'  ?></option>
								<option value="beta"    <?php if ( $wpl_update_channel == 'beta'    ): ?>selected="selected"<?php endif; ?>><?php echo 'beta'    ?></option>
								<option value="nightly" <?php if ( $wpl_update_channel == 'nightly' ): ?>selected="selected"<?php endif; ?>><?php echo 'nightly' ?> (not recommended)</option>
							</select>

						</div>
					</div>
					<?php endif; ?>

					<p style="margin-top: 0; float: left;">
	                    <a href="<?php echo $wpl_form_action ?>&action=wpla_force_update_check&_wpnonce=<?php echo wp_create_nonce( 'wpla_force_update_check' ); ?>" class="button"><?php echo __( 'Force update check', 'wp-lister-for-amazon' ); ?></a>
    	                &nbsp; Last check: <?php echo $wpl_last_update ?>
					</p>

					<?php if ( $wpl_text_license_email ) : ?>
	        		<p style="margin-top: 0; float: right;">
	                    <a href="<?php echo $wpl_form_action ?>&action=wpla_check_license_status&_wpnonce=<?php echo wp_create_nonce( 'wpla_check_license_status' ); ?>" class="button"><?php echo __( 'Check license activation', 'wp-lister-for-amazon' ); ?></a>
						<!-- &nbsp; -->
						<!-- <input type="submit" value="<?php echo __( 'Update license', 'wp-lister-for-amazon' ) ?>" name="submit" class="button-primary"> -->
					</p>
					<?php endif; ?>
	

				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 -->



		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>


</div>