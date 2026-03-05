<?php

use WPLab\Amazon\Helper\AmazonSchemaFormGenerator;

/* @var WPLA_AmazonProductTypeAttribute $wpl_product_type */
$groups = $wpl_product_type->getPropertyGroups();

$schema                 = json_decode( $wpl_product_type->getSchema(), true );
$profile_editor_mode    = get_option('wpla_profile_editor_mode','default');
$is_expert_mode         = $profile_editor_mode == 'expert';
$form_gen               = new AmazonSchemaFormGenerator( $schema, $groups, $wpl_profile, $wpl_product_id );

$required_fields    = $schema['required'];

echo $form_gen->generateFilters();
?>

<table id="feed-template-data" style="clear:both;">
    <?php
    foreach ( $wpl_product_type->getPropertyGroups() as $group_key => $group ):
        echo $form_gen->generateGroupRow( $group->getTitle(), $group->getDescription() );

        foreach ( $group->getPropertyNames() as $key => $property_name ):
	        $property   = $schema['properties'][$property_name];
	        $type       = $property['type'] ?? 'string';

	        echo $form_gen->generateField( $property_name, $property );
        endforeach;
    endforeach;

    echo $form_gen->renderUnusedProperties();
?>
</table>
<div id="wpla_shortcode_selection_wrapper" style="display:none">
    <?php include('select_shortcode.php'); ?>
</div>
<script>

    //let current_field;
    // let do_replace;
    // let prefer_keyword;

    // open shortcode selector
    function wpla_select_shortcode( fieldname ) {
        current_field  = fieldname;
        do_replace     = false;
        prefer_keyword = false;

        // item_type has a special selector
        if ( fieldname == 'item_type')					// default BTG field
            return wpla_select_from_btg( fieldname );
        if ( fieldname == 'recommended_browse_nodes') 	// used by clothing feed template
            return wpla_select_from_btg( fieldname );
        if ( fieldname == 'recommended_browse_nodes1') 	// used by lighting feed template
            return wpla_select_from_btg( fieldname );
        if ( fieldname == 'recommended_browse_nodes2') 	// used by lighting feed template
            return wpla_select_from_btg( fieldname );

        var tbHeight = tb_getPageSize()[1] - 120;
        var tbURL = "#TB_inline?height="+tbHeight+"&width=640&inlineId=wpla_shortcode_selection_wrapper";
        tb_show("Select an attribute", tbURL);

    }

    // insert selected shortcode
    function wpla_insert_shortcode( shortcode ) {
        var inputField = jQuery(document.getElementById('tpl_col_'+current_field));

        if ( do_replace ) {
            inputField.val( shortcode ); // replace
        } else {
            inputField.val( inputField.val() + shortcode ); // append
        }
        tb_remove();
    }

    // insert selected browse node id / keyword
    function wpla_insert_selected_browse_node( node_id ) {
        var inputField = jQuery('#tpl_col_'+current_field);

        // item_type column should use keyword instead of browse node id
        if ( prefer_keyword ) {
            var keyword = node_id = jQuery('#wpla_node_id_'+node_id).data('keyword');
            if ( keyword ) node_id = keyword;
        }

        if ( do_replace ) {
            inputField.val( node_id ); // replace
        } else {
            inputField.val( inputField.val() + node_id ); // append
        }
        tb_remove();
    }

    // open browse tree selector
    function wpla_select_from_btg( fieldname ) {
        current_field = fieldname;
        do_replace = true;

        // item_type column should use keyword instead of browse node id
        if ( fieldname == 'item_type')
            prefer_keyword = true;

        var tbHeight = tb_getPageSize()[1] - 120;
        var tbURL = "#TB_inline?height="+tbHeight+"&width=500&inlineId=amazon_categories_tree_wrapper";
        tb_show("Select a category", tbURL);

    }

    // disable Enter key in filter field
    jQuery('#_wpla_tpl_col_filter').keypress(function(event) {
        wpla_update_filter();
        return event.keyCode != 13;
    });

    // handle field filter changes
    function wpla_update_filter() {
        //console.log('filtering');
        var only_required = jQuery('#_wpla_tpl_col_only_required').prop('checked');
        var show_unmapped = jQuery('#_wpla_tpl_col_show_unmapped').prop('checked');
        var hide_empty    = jQuery('#_wpla_tpl_col_hide_empty'   ).prop('checked');

        if ( ! only_required ) {
            jQuery('.wpla_optional_row').show();
            jQuery('.wpla_preferred_row').show();
        }

        var query = jQuery('#_wpla_tpl_col_filter').val();
        if ( query ) {
            jQuery('.wpla_tpl_row').each( function( index ){

                // check for query match
                if ( this.id.match( query ) ) {
                    jQuery(this).show();
                } else if ( jQuery(this).find('span.wpla_field_label').first().html().match( new RegExp(query, "i") ) ) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                }

            });
            jQuery('.wpla_tpl_section_header').hide();
        } else {
            jQuery('.wpla_tpl_row').show();
            jQuery('.wpla_tpl_section_header').show();
        }

        if ( only_required ) {
            jQuery('.wpla_optional_row').hide();
            jQuery('.wpla_preferred_row').hide();
        }

        if ( hide_empty ) {
            jQuery('.wpla_tpl_row').each( function(index, value){
                var input_field  = jQuery(this).find('input').first();
                var select_field = jQuery(this).find('select').first();
                if ( ! input_field.val() && ! select_field.val() ) {
                    jQuery(this).hide();
                }
            });
        }


    } // wpla_update_filter()

    jQuery(document).ready(function($) {
        jQuery('select.select2').select2({
            tags: true
        });
    });
</script>