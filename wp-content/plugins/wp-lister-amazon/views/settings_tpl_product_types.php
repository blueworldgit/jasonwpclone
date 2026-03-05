<style>
    .result-row {
        background: #f0f0f0;
        border: 1px solid #e9e9e9;
        border-radius: 5px;
        margin: 10px 0;
        padding: 5px;
    }
    .result-row.installed {
        border: 1px solid #bdcfe4;
        background: #e3f5ff;
    }
    .result-row h4 {
        margin: 5px 0 10px 0;
    }
    .result-row.installed h4 {
        color: #0866ab;
    }
    .result-row .remove, .result-row .remove:hover {
        color: #c21900;
        border-color: #c21900;
    }
    #pt_search_results .button {
        font-size: 0.8em;
        min-height: 27px;
    }
</style>
<div class="wrap wpla-page">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/amazon-32x32.png) no-repeat;" id="wpl-icon"><br /></div>

	<?php include_once( dirname(__FILE__).'/settings_tabs.php' ); ?>
	<?php echo $wpl_message ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="postbox-container-1" class="postbox-container">
                <div id="side-sortables" class="meta-box">
                    <!-- first sidebox -->
                    <div class="postbox" id="submitdiv">

                        <h3 class="hndle"><span><?php echo __( 'Add a Product Type', 'wp-lister-for-amazon' ); ?></span></h3>
                        <div class="inside">
                            <div id="submitpost" class="submitbox">

                                <div id="misc-publishing-actions">
                                    <div class="misc-pub-section" id="product_type_block">
                                        <form method="post" id="product_type_frm" action="<?php echo $wpl_form_action; ?>">
                                            <p>
                                                <?php echo __( 'Select your Marketplace and enter your product keyword to search for the matching Product Types.', 'wp-lister-for-amazon' ); ?>
                                            </p>

                                            <p>
                                                <label for="marketplace"><?php _e( 'Marketplace', 'wp-lister-for-amazon' ); ?></label>
                                                <br/>
                                                <select name="marketplace" id="marketplace">
                                                    <?php
                                                    foreach ( WPLA_AmazonMarket::getAllFromAccounts() as $id => $name ):
                                                        ?>
                                                        <option value="<?php echo esc_attr( $id ); ?>"><?php echo $name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </p>

                                            <p>
                                                <label for="keywords"><?php _e( 'Keywords', 'wp-lister-for-amazon' ); ?></label>
                                                <br/>
                                                <input type="text" name="keywords" id="keywords" />
                                                <a class="button" href="#" id="run_search">
                                                    <span class="dashicons dashicons-search" style="vertical-align: text-bottom;"></span>
                                                </a>
                                            </p>
                                        </form>

                                        <div id="pt_search_results">

                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div id="postbox-container-2" class="postbox-container">
                <div class="meta-box-sortables ui-sortable">

                    <div class="postbox wpla_installed_product_types">
                        <h3 class="hndle"><span><?php _e( 'Installed Product Types', 'wp-lister-for-amazon'); ?></span></h3>
                        <div class="inside">

                            <!-- show listings table -->
                            <?php $wpl_table->views(); ?>
                            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                            <form id="listings-filter" method="get" action="<?php echo $wpl_form_action; ?>" >
                                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                                <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?? '' ?>" />
                                <input type="hidden" name="tab"  value="<?php echo esc_attr( $_REQUEST['tab'] ?? '' ) ?>" />
                                <!-- Now we can render the completed list table -->
                                <?php $wpl_table->search_box( __( 'Search', 'wp-lister-for-amazon' ), 'listing-search-input' ); ?>
                                <?php $wpl_table->display() ?>
                            </form>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
<script>
jQuery(document).ready(function() {
    let installed = <?php echo json_encode( $wpl_installed ); ?>;

    jQuery('#product_type_frm').on( 'click', '#run_search', function(e) {
        e.preventDefault();
        jQuery('#product_type_frm').submit();
    });

    jQuery('#product_type_frm').on( 'submit', function() {
        const marketplace   = jQuery('#marketplace').val();
        const keywords      = jQuery('#keywords').val();

        wpla_block('#product_type_block');

        fetch(ajaxurl+"?action=wpla_search_product_types&marketplace="+marketplace+"&keywords="+keywords )
            .then(response => response.json())
            .then(result => {
                renderResults(result);
                wpla_unblock('#product_type_block');
            })
            .catch(exception => {
                console.log(exception);
            });

        return false;
    });

    jQuery('#pt_search_results').on( 'click', 'a.install', function(e) {
        e.preventDefault();

        wpla_block('#product_type_block');

        const marketplace   = jQuery(this).data('marketplace');
        const product_type  = jQuery(this).data('product_type');
        const name          = jQuery(this).data('name');

        const btn   = jQuery(this);
        const el    = jQuery(this).parent('.result-row');

        fetch( ajaxurl+'?action=wpla_install_product_type', {
            method: 'POST',
            headers: {
                'Accept': 'application/json, text/plain, */*',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'marketplace='+ marketplace +'&product_type='+ product_type +'&name='+ encodeURIComponent( name )
        })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    jQuery(el).addClass('installed');
                    jQuery(btn).replaceWith(jQuery('<a class="install button"  disabled="" href="#">Installed <span class="dashicons dashicons-saved"></span></a>'))
                } else {
                    alert('There was an error while processing your request: '+ response.message);
                }
                wpla_unblock('#product_type_block');
            })
            .catch(error => console.log(error));

    });

    jQuery('#pt_search_results').on( 'click', 'a.remove', function(e) {
        e.preventDefault();

        wpla_block('#product_type_block');

        const nonce         = '<?php echo wp_create_nonce( 'wpla-delete-product-type' ); ?>';
        const marketplace   = jQuery(this).data('marketplace');
        const product_type  = jQuery(this).data('product_type');
        const name          = jQuery(this).data('name');

        const btn   = jQuery(this);
        const el    = jQuery(this).parent('.result-row');

        fetch( ajaxurl+'?action=wpla_remove_product_type', {
            method: 'POST',
            headers: {
                'Accept': 'application/json, text/plain, */*',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'marketplace='+ marketplace +'&product_type='+ product_type +'&nonce='+ nonce
        })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    jQuery(el).removeClass('installed');
                    jQuery(el).find('a.install').remove();
                    jQuery(btn).replaceWith(jQuery('<a class="button install" href="#" data-marketplace="'+ marketplace +'" data-product_type="'+ product_type +'" data-name="'+ name +'">Install</a>'))
                    // '<a class="button install" href="#" data-marketplace="'+ marketplace +'" data-product_type="'+ name +'" data-name="'+ type_row.displayName +'">Install</a>'
                } else {
                    alert('There was an error while processing your request: '+ response.message);
                }
                wpla_unblock('#product_type_block');
            })
            .catch(error => console.log(error));

    });

    jQuery('table.producttypes').on('click', '.product-type-delete', function(e) {
        e.preventDefault();

        const id    = jQuery(this).data('id');
        const nonce = jQuery(this).data('nonce');
        const row   = jQuery(this).parents('tr');
        const table = jQuery(this).parents('table');
        wpla_block(table);

        fetch( ajaxurl+'?action=wpla_remove_product_type', {
            method: 'POST',
            headers: {
                'Accept': 'application/json, text/plain',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id='+ id +'&nonce='+ nonce
        })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    jQuery(row).remove();
                } else {
                    alert('There was an error while processing your request: '+ response.message);
                }
                wpla_unblock(table);
            })
            .catch(error => console.log(error));
    });

    function renderResults( results ) {
        const el = jQuery('#pt_search_results');

        resetList();

        for ( let i in results.productTypes ) {
            let is_installed = false;
            let type_row = results.productTypes[i];
            let marketplace = type_row.marketplaceIds[0];

            let name        = type_row.name;
            let row_class   = 'result-row';
            let actions     = '<a class="button install" href="#" data-marketplace="'+ marketplace +'" data-product_type="'+ name +'" data-name="'+ type_row.name +'">Install</a>';

            if ( installed[ marketplace ] && installed[marketplace].includes( type_row.name ) ) {
                is_installed = true;
                row_class += ' installed';
                actions = '<a class="button install"  disabled="" href="#">Installed <span class="dashicons dashicons-saved"></span></a>';
                actions += ' <a href="#" class="button remove" data-marketplace="'+ marketplace +'" data-product_type="'+ name +'" data-name="'+ type_row.name +'">Remove</a>';
            }

            let row = jQuery('<div class="'+ row_class +'">' +
                '<h4>'+ type_row.name + '</h4>' +
                actions +
                '</div>');
            el.append(row);
        }

    }

    function resetList() {
        jQuery('#pt_search_results .result-row').remove();
    }

    function wpla_block(el) {
        jQuery(el).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    }

    function wpla_unblock(el) {
        jQuery(el).unblock();
    }
});
</script>