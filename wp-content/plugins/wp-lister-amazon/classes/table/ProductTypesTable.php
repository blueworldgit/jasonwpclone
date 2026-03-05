<?php

namespace WPLab\Amazon\Tables;

/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if(!class_exists('\WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

use WPLab\Amazon\Core\AmazonProductType;
use WPLab\Amazon\Models\AmazonProductTypesModel;

class ProductTypesTable extends \WP_List_Table {

    const TABLENAME = 'amazon_product_types';
    public $total_items;

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'product type',     //singular name of the listed records
            'plural'    => 'product types',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    
    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     *
     * @param AmazonProductType $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
        switch($column_name){
            case 'marketplace':
                return \WPLA_AmazonMarket::getNamebyMarketplaceId( $item->getMarketplaceId() );
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

	/**
	 * @param AmazonProductType $item
	 *
	 * @return string
	 */
    function column_product_type($item){
	    $actions = array(
		    'update'    => sprintf('<a data-id="%d" class="product-type-update" href="#">%s</a>',$item->getId(),__( 'Update', 'wp-lister-for-amazon' )),
		    'delete'    => sprintf('<a data-id="%d" data-nonce="%s" class="product-type-delete" href="#">%s</a>',$item->getId(), wp_create_nonce( 'wpla-delete-product-type' ), __( 'Delete', 'wp-lister-for-amazon' )),
	    );

	    $title = $item->getDisplayName();

	    //Return the title contents
	    return sprintf('%1$s <br>%2$s',
		    /*$1%s*/ $title,
		    /*$2%s*/ $this->row_actions($actions)
	    );

        return $item->getDisplayName();
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("listing")
            /*$2%s*/ $item->getId()                //The value of the checkbox should be the record's id
        );
    }
        
    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            //'cb'                => '<input type="checkbox" />', //Render a checkbox instead of text
            'product_type'     	=> __( 'Product Type', 'wp-lister-for-amazon' ),
            'marketplace'       => __( 'Marketplace', 'wp-lister-for-amazon' ),
        );
        return $columns;
    }

    function extra_tablenav( $which ) {
        return;

    }

	function process_bulk_action() {
		global $wbdb;

		//Detect when a bulk action is being triggered...


	}
    
    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items( $data = false ) {
	    // process bulk actions
	    $this->process_bulk_action();

        // get pagination state
        $current_page = $this->get_pagenum();
        $per_page = $this->get_items_per_page('listings_per_page', 20);
        
        // define columns
        $this->_column_headers = [$this->get_columns()];
        
        // fetch logs
        $this->items = $this->getPageItems( $current_page, $per_page );
        $total_items = $this->total_items;

        // register our pagination options & calculations.
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );

    }

    function getPageItems( $current_page, $per_page ) {
        $mdl = new AmazonProductTypesModel();
        $result = $mdl->getFiltered([
            'page'      => $current_page,
            'per_page'  => $per_page,
            'keywords'  => $_REQUEST['s'] ?? ''
        ]);

        $this->total_items = $result['total_items'];

        return (array)$result['items'];
    }
}

