<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	th.column-details {
		width: 25%;
	}

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/amazon-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __( 'Profiles', 'wp-lister-for-amazon' ) ?></h2>
	<?php echo $wpl_message ?>

    <?php
    if ( $wpl_needs_conversion ):
    ?>
    <div class="message error">
        <p>
            <b><?php _e('Warning', 'wp-lister-for-amazon'); ?>:</b> <?php printf( _n('You have %d profile that still use Feed Templates.', 'You have %d profiles that still use Feed Templates.', $wpl_needs_conversion, 'wp-lister-for-amazon' ), $wpl_needs_conversion ); ?>
        </p>
        <p>
            <?php _e('Amazon is phasing out Feed Templates (Flat File Feeds) and moving to Product Types API to manage product attributes. This change impacts how WP-Lister structures and submits your product data—support for Flat File Feeds ends on June 30, 2025.', 'wp-lister-for-amazon'); ?>
            <br/><br/>
            <?php _e('Click the button below to open the Profile Conversion tool.', 'wp-lister-for-amazon'); ?>
        </p>
        <p><a class="button" href="<?php echo admin_url('admin.php?page=wpla-tools&tab=profile-converter'); ?>"><?php _e('Convert Profiles', 'wp-lister-for-amazon'); ?></a></p>
    </div>
    <?php endif; ?>

	<!-- show profiles table -->
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="profiles-filter" method="post" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>" />
        <!-- Now we can render the completed list table -->
		<?php $wpl_profilesTable->search_box( __( 'Search', 'wp-lister-for-amazon' ), 'profile-search-input' ); ?>
        <?php $wpl_profilesTable->display() ?>
    </form>

	<br style="clear:both;"/>

    <form id="profiles-addnew" method="get" action="<?php echo $wpl_form_action; ?>" style="display: inline;">
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>" />
        <input type="hidden" name="action" value="add_new_profile" />

		<input type="submit" value="<?php echo __( 'Add New Profile', 'wp-lister-for-amazon' ) ?>" name="submit" class="button">
    </form>

	<form id="profiles-upload" method="post" action="<?php echo $wpl_form_action; ?>" enctype="multipart/form-data" style="display: inline;">
        <a href="#" onclick="jQuery('#wpla_file_upload_profile').click();return false;" class="button-secondary">
        	<?php echo __( 'Upload Profile', 'wp-lister-for-amazon' ); ?>
        </a> 

        <input type="hidden" name="action" value="wpla_upload_listing_profile" />
        <?php wp_nonce_field( 'wpla_upload_listing_profile' ); ?>
        <input type="file" id="wpla_file_upload_profile" name="wpla_file_upload_profile" onchange="this.form.submit();" style="display:none" />
    </form>
	<br style="clear:both;"/>

	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
		
				// ask again before deleting
				jQuery('.row-actions .delete a').on('click', function() {
					return confirm("<?php echo __( 'Are you sure you want to delete this item?.', 'wp-lister-for-amazon' ) ?>");
				})

                jQuery('.migrate-listings').on( 'click', function(e) {
                    e.preventDefault();

                    if ( !confirm('Do you want to migrate the listings over to this new profile?') ) {
                        return false;
                    }

                    var params = {
                        'from': jQuery(this).data('src'),
                        'to': jQuery(this).data('id')
                    }

                    WPLA.JobRunner.runJob( 'moveListingsToProfile', 'Moving listings to a new profile...', params );
                    return false;
                });
	
			}
		);
	
	</script>

</div>