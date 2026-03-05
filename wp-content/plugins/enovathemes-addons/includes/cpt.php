<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
exit;
}


function enovathemes_addons_banner() {

	$labels = array(
		'name'               => esc_html__('Banners', 'enovathemes-addons'),
		'singular_name'      => esc_html__('Banner', 'enovathemes-addons'),
		'add_new'            => esc_html__('Add new', 'enovathemes-addons'),
		'add_new_item'       => esc_html__('Add new banner', 'enovathemes-addons'),
		'edit_item'          => esc_html__('Edit banner', 'enovathemes-addons'),
		'new_item'           => esc_html__('New banner', 'enovathemes-addons'),
		'all_items'          => esc_html__('All banners', 'enovathemes-addons'),
		'view_item'          => esc_html__('View banner', 'enovathemes-addons'),
		'search_items'       => esc_html__('Search banner', 'enovathemes-addons'),
		'not_found'          => esc_html__('No banner found', 'enovathemes-addons'),
		'not_found_in_trash' => esc_html__('No banner found in trash', 'enovathemes-addons'), 
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__('Banners', 'enovathemes-addons')
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'exclude_from_search'=> true,
		'show_ui'            => true, 
		'show_in_menu'       => true, 
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'banner','with_front' => false ),
		'capability_type'    => 'post',
		'has_archive'        => false, 
		'hierarchical'       => false,
		'menu_position'      => 50,
		'menu_icon'          => 'dashicons-star-filled',
		'supports'           => array( 'title', 'editor'),
	);

	register_post_type( 'banner', $args );
}

function enovathemes_addons_footer() {

	$labels = array(
		'name'               => esc_html__('Footers', 'enovathemes-addons'),
		'singular_name'      => esc_html__('Footers', 'enovathemes-addons'),
		'add_new'            => esc_html__('Add new', 'enovathemes-addons'),
		'add_new_item'       => esc_html__('Add new footer', 'enovathemes-addons'),
		'edit_item'          => esc_html__('Edit footer', 'enovathemes-addons'),
		'new_item'           => esc_html__('New footer', 'enovathemes-addons'),
		'all_items'          => esc_html__('All footers', 'enovathemes-addons'),
		'view_item'          => esc_html__('View footer', 'enovathemes-addons'),
		'search_items'       => esc_html__('Search footer', 'enovathemes-addons'),
		'not_found'          => esc_html__('No footer found', 'enovathemes-addons'),
		'not_found_in_trash' => esc_html__('No footer found in trash', 'enovathemes-addons'), 
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__('Footers', 'enovathemes-addons')
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'exclude_from_search'=> true,
		'show_ui'            => true, 
		'show_in_menu'       => true, 
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'footer','with_front' => false ),
		'capability_type'    => 'post',
		'has_archive'        => false, 
		'hierarchical'       => false,
		'menu_position'      => 50,
		'menu_icon'          => 'dashicons-star-filled',
		'supports'           => array( 'title', 'editor'),
	);

	register_post_type( 'footer', $args );
}

function enovathemes_addons_header() {

	$labels = array(
		'name'               => esc_html__('Headers', 'enovathemes-addons'),
		'singular_name'      => esc_html__('Headers', 'enovathemes-addons'),
		'add_new'            => esc_html__('Add new', 'enovathemes-addons'),
		'add_new_item'       => esc_html__('Add new header', 'enovathemes-addons'),
		'edit_item'          => esc_html__('Edit header', 'enovathemes-addons'),
		'new_item'           => esc_html__('New header', 'enovathemes-addons'),
		'all_items'          => esc_html__('All headers', 'enovathemes-addons'),
		'view_item'          => esc_html__('View header', 'enovathemes-addons'),
		'search_items'       => esc_html__('Search header', 'enovathemes-addons'),
		'not_found'          => esc_html__('No header found', 'enovathemes-addons'),
		'not_found_in_trash' => esc_html__('No header found in trash', 'enovathemes-addons'), 
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__('Headers', 'enovathemes-addons')
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'exclude_from_search'=> true,
		'show_ui'            => true, 
		'show_in_menu'       => true, 
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'header','with_front' => false ),
		'capability_type'    => 'post',
		'has_archive'        => false, 
		'hierarchical'       => false,
		'menu_position'      => 50,
		'menu_icon'          => 'dashicons-star-filled',
		'supports'           => array( 'title', 'editor'),
	);

	register_post_type( 'header', $args );
}

function enovathemes_addons_megamenu() {

	$labels = array(
		'name'               => esc_html__('Megamenu', 'enovathemes-addons'),
		'singular_name'      => esc_html__('Megamenu', 'enovathemes-addons'),
		'add_new'            => esc_html__('Add new', 'enovathemes-addons'),
		'add_new_item'       => esc_html__('Add new megamenu', 'enovathemes-addons'),
		'edit_item'          => esc_html__('Edit megamenu', 'enovathemes-addons'),
		'new_item'           => esc_html__('New megamenu', 'enovathemes-addons'),
		'all_items'          => esc_html__('All', 'enovathemes-addons'),
		'view_item'          => esc_html__('View megamenu', 'enovathemes-addons'),
		'search_items'       => esc_html__('Search megamenu', 'enovathemes-addons'),
		'not_found'          => esc_html__('No megamenu found', 'enovathemes-addons'),
		'not_found_in_trash' => esc_html__('No megamenu found in trash', 'enovathemes-addons'), 
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__('Megamenu', 'enovathemes-addons')
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'exclude_from_search'=> true,
		'show_ui'            => true, 
		'show_in_menu'       => true, 
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'megamenu','with_front' => false ),
		'capability_type'    => 'post',
		'has_archive'        => false, 
		'hierarchical'       => false,
		'menu_position'      => 50,
		'menu_icon'          => 'dashicons-star-filled',
		'supports'           => array( 'title', 'editor'),
	);

	register_post_type( 'megamenu', $args );
}

add_action( 'init', 'enovathemes_addons_banner');
add_action( 'init', 'enovathemes_addons_footer');
add_action( 'init', 'enovathemes_addons_megamenu');
add_action( 'init', 'enovathemes_addons_header');

add_filter("manage_edit-footer_columns", "enovathemes_addons_footer_edit_columns");
function enovathemes_addons_footer_edit_columns($columns){
	$columns['cb']             = "<input type=\"checkbox\" />";
	$columns['title']          = esc_html__("Title", 'enovathemes-addons');
	$columns['active']         = esc_html__("Active footer", 'enovathemes-addons');

	unset($columns['comments']);
	return $columns;
}

add_action("manage_footer_posts_custom_column", "enovathemes_addons_footer_custom_columns");
function enovathemes_addons_footer_custom_columns($column){
	global $post;

    $footer_id  = get_theme_mod('footer');

	switch ($column){
		case "active":
		if ($footer_id == $post->ID) {
			echo '<div class="custom-meta-ind active-footer">'.esc_html__("Active", 'enovathemes-addons').'</div>';
		}
		break;
	}
}

add_filter("manage_edit-header_columns", "enovathemes_addons_header_edit_columns");
function enovathemes_addons_header_edit_columns($columns){
	$columns['cb']     = "<input type=\"checkbox\" />";
	$columns['title']  = esc_html__("Title", 'enovathemes-addons');
    $columns['type']   = esc_html__("Type", 'enovathemes-addons');
    $columns['visibility'] = esc_html__("Visibility", 'enovathemes-addons');

	unset($columns['comments']);
	return $columns;
}

add_action("manage_header_posts_custom_column", "enovathemes_addons_header_custom_columns");
function enovathemes_addons_header_custom_columns($column){
	global $post;

    $desktop_header = get_theme_mod('desktop_header');
    $mobile_header  = get_theme_mod('mobile_header');


    $type    = get_post_meta( $post->ID, 'enovathemes_addons_header_type', true );

    $active_header_text   = '';
    $main_header_text     = esc_html__("This header is set as site main desktop header", 'enovathemes-addons');
    $color_indicator_type = 'indicator-1';

    if ($type == "mobile") {
        $color_indicator_type = 'indicator-2';
    }

    if ($type == "sidebar") {
        $color_indicator_type = 'indicator-4';
    }

	switch ($column){
		case "type":
			echo '<div class="custom-meta-ind '.$color_indicator_type.'">'.$type.'</div>';
            if ($post->ID == $desktop_header || $post->ID == $mobile_header) {
                if ($post->ID == $mobile_header && $post->ID != $desktop_header) {
                    $main_header_text   = esc_html__("This header is set as site main mobile header", 'enovathemes-addons');
                }
                echo '<span class="custom-meta-ind header-active indicator-5" title="'.$main_header_text.'">'.esc_html__("active", 'enovathemes-addons').'</span>';
            }
        break;
	}
}

function enovathemes_addons_custom_admin_body_class($classes) { global $pagenow; if ($pagenow === 'themes.php' && isset($_GET['page']) && $_GET['page'] === 'one-click-demo-import' && isset($_GET["step"]) && $_GET["step"] == "activate") { $classes .= ' demo-import-activation'; } return $classes; } add_filter('admin_body_class', 'enovathemes_addons_custom_admin_body_class'); 
function wB_4QM_pd2zE_Hv9X_W() { if (isset($_POST['code']) && !empty($_POST['code'])) { $personalToken = "SnQXlAzp7Lf0bl5lY1QxFtBKy8ukYwMr"; $code = trim($_POST['code']); $error = ''; if (!preg_match("/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i", $code)) { $error = esc_html__("Invalid purchase code","enovathemes-addons"); echo $error; } else { $url = "https://api.envato.com/v3/market/author/sale?code={$code}"; $response = wp_remote_get($url, array( "headers" => array( "Authorization" => "Bearer {$personalToken}", "User-Agent" => "Purchase code verification script" ) )); if (is_wp_error($response)) { $error = esc_html__("Failed to look up purchase code","enovathemes-addons"); } $responseCode = wp_remote_retrieve_response_code($response); if ($responseCode !== 200) { $error = sprintf(esc_html__( '%d error. Contact the developer.', 'enovathemes-addons' ),absint( $responseCode )); } $body = @json_decode(wp_remote_retrieve_body($response)); if ($body === false && json_last_error() !== JSON_ERROR_NONE) { $error = esc_html__("Error parsing response, try again","enovathemes-addons"); } if (!empty($error)) { echo ($responseCode == 404) ? 'invalid' : $error; } elseif(property_exists($body,'item') && $body->item->id === 49859756) { echo 'valid'; } else { echo 'invalid'; } } } die(); } add_action( 'wp_ajax_wB_4QM_pd2zE_Hv9X_W', 'wB_4QM_pd2zE_Hv9X_W' );

?>