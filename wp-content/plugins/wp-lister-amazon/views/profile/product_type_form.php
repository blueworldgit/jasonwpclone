<div class="postbox" id="ProductTypeBox">
	<h3 class="hndle"><span><?php echo __( 'Product Type', 'wp-lister-for-amazon' ); ?></span></h3>
	<div class="inside">

        <div class="no-account-selected" style="text-align: center">
            <h4><?php printf(__('Please select an account from the sidebar. If there are no accounts listed, please create one from the <a href="%s">Accounts page</a>.', 'wp-lister-for-amazon' ), admin_url('admin.php?page=wpla-settings&tab=accounts') ); ?></h4>
        </div>

        <div class="with-account-selected">
            <p>
		        <?php echo __( 'Select the Marketplace and Product Type to see the appropriate attributes. ', 'wp-lister-for-amazon' ); ?>
            </p>

            <label for="wpl-text-marketplace_id" class="text_label">
		        <?php echo __( 'Marketplace', 'wp-lister-for-amazon' ); ?>
            </label>
            <select id="wpl-text-marketplace_id" name="wpla_marketplace_id" class="required-entry select">
		        <?php
                $feed_template = new WPLA_AmazonFeedTemplate( $wpl_profile->tpl_id );
                //$current_marketplace = \WPLab\Amazon\Helper\JsonFeedDataBuilder::getMarketplaceIdFromTemplateId( $wpl_profile->tpl_id );
                $current_marketplace = $wpl_profile->marketplace_id;
		        $marketplaces = array_keys( $wpl_product_types );
		        foreach ( $marketplaces as $marketplace_id ):
			        $marketplace = WPLA_AmazonMarket::getNamebyMarketplaceId( $marketplace_id );
			        ?>
                    <option <?php selected( $current_marketplace, $marketplace_id); ?> value="<?php echo esc_attr($marketplace_id); ?>"><?php echo $marketplace; ?></option>
		        <?php endforeach; ?>
            </select>

            <label for="wpl-text-product_type" class="text_label">
		        <?php echo __( 'Product Type', 'wp-lister-for-amazon' ); ?>
		        <?php wpla_tooltip('') ?>
            </label>
            <select id="wpl-text-product_type" name="wpla_product_type" class="required-entry select select2" data-tags="false" style="width: 65%;">

            </select>
            <br class="clear" />
            <p class="desc">
                <?php _e('You can add additional Product Types at <a href="admin.php?page=wpla-settings&tab=product_types">Amazon » Settings » Product Types</a>.', 'wp-lister-amazon' ); ?>
            </p>
        </div>

		<br class="clear" />

	</div>
</div>

<div class="postbox" id="ProductTypeDataBox">
	<h3 class="hndle"><span><?php echo __( 'Product Attributes', 'wp-lister-for-amazon' ); ?></span></h3>
	<div class="inside" id="ProductAttributesBody">

		<p class="" style="">
			<i><?php echo __( 'No Product Type selected.', 'wp-lister-for-amazon' ); ?></i>
		</p>


	</div>
</div>
<script>
    // init
    const marketplace_select = document.getElementById('wpl-text-marketplace_id');
    const product_type_select = document.getElementById('wpl-text-product_type');

    let profile_id      = '<?php echo $wpl_profile->id; ?>';
    let account_id      = '<?php echo $wpl_account_id; ?>';
    let product_types   = <?php echo json_encode( $wpl_product_types ); ?>;
    let selected_product_type = '<?php echo $wpl_profile->product_type; ?>';

    jQuery( document ).ready( function () {
        init_display();

        redraw_product_types_dropdown( marketplace_select.value );
        jQuery( 'select.select2' ).select2();
        jQuery('input[type=radio][name=wpla_account_id]').change(account_switched);

        marketplace_select.addEventListener("change", redraw_product_types_dropdown)
        jQuery("#wpl-text-product_type").on("change", render_product_attributes);

        function account_switched() {
            // load new product types data for the selected account
            account_id = jQuery('input[name=wpla_account_id]:checked').val()
            load_product_types();
        }

        function load_product_types() {
            // loading screen
            wpla_block('#ProductTypeBox');

            fetch(ajaxurl+"?action=wpla_load_product_types&account="+account_id )
                .then(response => response.json())
                .then(result => {
                    product_types = result;
                    render_marketplaces();
                    init_display();
                    wpla_unblock('#ProductTypeBox');
                })
        }

        async function render_marketplaces() {
            // console.log(product_types);
            if ( account_id <= 0 ) return;

            const resp = await fetch(ajaxurl+"?action=wpla_get_marketplaces_html&account="+account_id )
                .then(response => response.json())
                .then(result => {
                    redraw_marketplaces_dropdown( result );
                })
        }

        function redraw_marketplaces_dropdown( options ) {
            // console.log(options);
            reset_dropdown( marketplace_select );
            add_dropdown_options( marketplace_select, options );
            redraw_product_types_dropdown();
        }

        async function render_product_attributes() {

            let product_type = product_type_select.value;
            let marketplace_id = marketplace_select.value;

            if (!product_type || !marketplace_id) {
                return;
            }

            let container = document.getElementById('ProductAttributesBody');
            container.innerHTML = "&nbsp;";

            wpla_block('#ProductTypeDataBox');

            const resp = await fetch(ajaxurl+"?action=wpla_get_product_type_attributes_html&product_type="+ product_type +"&marketplace="+marketplace_id +"&profile_id="+ profile_id +"&account_id="+ account_id  )
                .then(response => {
                    return response.text()
                })
                .then(html => {
                    jQuery('#ProductAttributesBody').html( html );

                    // init tooltips
                    jQuery('#ProductTypeDataBox .help_tip').tipTip({
                        'attribute' : 'data-tip',
                        'maxWidth' : '250px',
                        'fadeIn' : 50,
                        'fadeOut' : 50,
                        'delay' : 200
                    });
                    wpla_unblock('#ProductTypeDataBox');
                })
                .catch(error => {
                    wpla_unblock('#ProductTypeDataBox');
                    console.error('Failed to fetch page: ', error)
                });

        }

        function redraw_product_types_dropdown() {
            let types = product_types[ marketplace_select.value ];
            let types_options = [];
            for ( let i in types ) {
                types_options[i] = {
                    text: types[i].display_name,
                    value: types[i].product_type
                };
            }

            types_options.unshift( { text: '', value: ''} );

            reset_dropdown(product_type_select);
            add_dropdown_options(product_type_select, types_options, selected_product_type);
            render_product_attributes();
        }

        function reset_dropdown( select ) {
            let length = select.options.length;
            for (let i = length-1; i >= 0;i--) {
                select.remove(i);
            }
        }

        function add_dropdown_options( select, options, selected = '' ) {
            for ( let i in options ) {
                let option = document.createElement("option");
                option.text = options[i].text;
                option.value = options[i].value;
                option.selected = selected == options[i].value;

                select.appendChild(option);
            }
        }

        function render_props( attributes ) {
            jQuery('#ProductAttributesBody').html( attributes );
        }

        function init_display() {
            jQuery('.no-account-selected').hide();
            jQuery('.with-account-selected').hide();

            if ( account_id > 0 ) {
                jQuery('.with-account-selected').show();
            } else {
                jQuery('.no-account-selected').show();
            }

        }

    });
</script>