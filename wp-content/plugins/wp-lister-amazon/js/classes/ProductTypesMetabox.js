var wpla_post_id = wpla_product_metabox.post_id;
var register_event_handlers = {};

// load template data
function loadTemplateData() {
    var tpl_id = jQuery('#wpl-text-tpl_id')[0].value;


    // jQuery('#wpla_feed_data_wrapper').slideUp(500);
    // jQuery('#FeedDataBox .loadingMsg').slideDown(500);
    jQuery('#wpla_feed_data_wrapper').html('<p><i>loading feed template...</i></p>');

    // fetch category conditions
    var params = {
        action: 'wpla_load_template_data_for_product',
        tpl_id: tpl_id,
        post_id: wpla_post_id,
        _wpnonce: wpla_product_metabox.nonce
    };

    var jqxhr = jQuery('#wpla_feed_data_wrapper').load( ajaxurl, params, function( response, status, xhr ) {
        if ( status == 'error' ) {
            var msg = 'Sorry but there was an error: ';
            jQuery( '#error' ).html( msg + xhr.status + ' ' + xhr.statusText );
        } else {

            // init tooltips
            jQuery('#FeedDataBox .help_tip').tipTip({
                'attribute' : 'data-tip',
                'maxWidth' : '250px',
                'fadeIn' : 50,
                'fadeOut' : 50,
                'delay' : 200
            });

        }
    });

}

// init
jQuery( document ).ready( function () {

    jQuery('#wpl-text-tpl_id').change(function() {
        if ( jQuery('#wpl-text-tpl_id').val() != '' ) {
            loadTemplateData();
        }
    });
    jQuery('#wpl-text-tpl_id').change();

    // jqueryFileTree - amazon categories / browse tree guide
    jQuery('#amazon_categories_tree_container').fileTree({
        root: '/0/',
        script: ajaxurl+'?action=wpla_get_amazon_categories_tree',
        expandSpeed: 400,
        collapseSpeed: 400,
        loadMessage: 'loading browse tree guide...',
        multiFolder: false
    }, function(catpath) {

        // console.log('catpath: ',catpath);

        // get cat id from full path
        var cat_id = catpath.split('/').pop(); // get last item - like php basename()

        var cat_array = catpath.split('/');
        if ( cat_array[ cat_array.length - 1 ] == '' ) {
            cat_id = cat_array[ cat_array.length - 2 ];
        }

        // get name of selected category
        // var cat_name = '';

        // var pathname = wpl_getCategoryPathName( catpath.split('/') );
        // var pathname = catpath;
        // console.log('cat_id: ',cat_id);

        // insert shortcode / value
        wpla_insert_selected_browse_node( cat_id );

        // update fields
        // jQuery('#amazon_category_id_'+wpla_selecting_cat).prop( 'value', cat_id );
        // jQuery('#amazon_category_name_'+wpla_selecting_cat).html( pathname );

        // close thickbox
        // tb_remove();


    });

    let marketplace_select = jQuery('#wpl_marketplace_id');
    let product_type_select = jQuery('#wpl_product_type');
    let product_types = [];
    let selected_product_type = wpla_product_metabox.current_product_type;
    let current_tpl_id = wpla_product_metabox.current_tpl_id;

    jQuery( 'select.select2' ).select2();

    register_event_handlers = function() {
        jQuery('#wpla-amazon-feed_columns').on('change', '#wpl_marketplace_id', load_product_types);
        jQuery('#wpla-amazon-feed_columns').on('change', '#wpl_product_type', render_product_attributes);
    }

    if ( marketplace_select ) {
        //load_product_types();
        register_event_handlers();
        product_type_select.change();
    }

    jQuery('#product_type_recommendations').on('click', '#convert_feed_template', convertFeedTemplate);

    function convertFeedTemplate() {
        wpla_block('#wpla-amazon-feed_columns');

        fetch(ajaxurl+'?action=wpla_convert_product_feed_template&product='+ wpla_post_id +'&from='+current_tpl_id+'&product_type='+ jQuery('#wpl_new_product_type').val() )
            .then(response => response.json())
            .then(result => {
                jQuery('#wpla-amazon-feed_columns .inside').load(ajaxurl+'?action=wpla_product_type_metabox_html&product='+ wpla_post_id, function(resp) {
                    jQuery('#wpla-amazon-feed_columns').on('change', '#wpl_marketplace_id', load_product_types);
                    jQuery('#wpla-amazon-feed_columns').on('change', '#wpl_product_type', render_product_attributes);

                    marketplace_select  = jQuery('#wpl_marketplace_id');
                    product_type_select = jQuery('#wpl_product_type');

                    jQuery( 'select.select2' ).select2();
                    jQuery('#wpl_product_type').change();

                    wpla_unblock('#wpla-amazon-feed_columns');
                });


            })
    }

    function load_product_types() {
        // loading screen
        wpla_block('#wpla-amazon-feed_columns');

        fetch(ajaxurl+'?action=wpla_load_marketplace_product_types&marketplace='+marketplace_select.val() )
            .then(response => response.json())
            .then(result => {
                product_types = result;
                //init_display();
                wpla_unblock('#wpla-amazon-feed_columns');
                redraw_product_types_dropdown();
            })
    }

    async function render_marketplaces() {
        if ( account_id <= 0 ) return;

        const resp = await fetch(ajaxurl+'?action=wpla_get_marketplaces_html&account='+account_id )
            .then(response => response.json())
            .then(result => {
                redraw_marketplaces_dropdown( result );
            })
    }

    function redraw_marketplaces_dropdown( options ) {
        reset_dropdown( marketplace_select );
        add_dropdown_options( marketplace_select, options );
        load_product_types();
        //redraw_product_types_dropdown();
    }

     function render_product_attributes() {
        let product_type    = product_type_select.val();
        let marketplace_id  = marketplace_select.val();

        if (!product_type || !marketplace_id) {
            return;
        }

        let container = document.getElementById('PropertiesDataBox');
        container.innerHTML = '&nbsp;';

        wpla_block('#wpla-amazon-feed_columns');

        const resp = fetch(ajaxurl+'?action=wpla_get_product_type_attributes_html&product_type='+ product_type +'&marketplace='+marketplace_id +'&product_id='+ wpla_post_id  )
            .then(response => {
                return response.text()
            })
            .then(html => {
                jQuery('#PropertiesDataBox').html( html );

                // init tooltips
                jQuery('#wpla-amazon-feed_columns .help_tip').tipTip({
                    'attribute' : 'data-tip',
                    'maxWidth' : '250px',
                    'fadeIn' : 50,
                    'fadeOut' : 50,
                    'delay' : 200
                });
                wpla_unblock('#wpla-amazon-feed_columns');
            })
            .catch(error => {
                wpla_unblock('#wpla-amazon-feed_columns');
                console.error('Failed to fetch page: ', error)
            });

    }

    function redraw_product_types_dropdown() {
        let types = product_types;
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
        select.empty();
    }

    function add_dropdown_options( select, options, selected = '' ) {
        for ( let i in options ) {
            select.append(jQuery('<option>', {
                value: options[i].value,
                text: options[i].text,
                selected: selected == options[i].value
            }));
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