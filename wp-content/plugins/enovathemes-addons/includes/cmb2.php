<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
exit;
}


require_once(__DIR__ . '/cmb2/init.php');

add_action( 'cmb2_admin_init', 'enovathemes_addons_register_metabox' );

function enovathemes_addons_register_metabox() {

	$prefix = 'enovathemes_addons_';

	/*  Footer
	/*-------------------*/

		$cmb_footer_layout = new_cmb2_box( array(
			'id'            => $prefix.'footer_options_metabox',
			'title'         => esc_html__( 'Footer options', 'enovathemes-addons' ),
			'object_types'  => array( 'footer', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );

		$cmb_footer_layout->add_field( array(
			'name'             => esc_html__( 'Load footer asynchronous?', 'enovathemes-addons' ),
			'id'               => $prefix . 'footer_async',
			'type'             => 'select',
			'classes'          => 'select-230',
			'options'          => array(
				'false'     => esc_html__( 'False', 'enovathemes-addons' ),
				'true'      => esc_html__( 'True', 'enovathemes-addons' ),
			),
			'default' => 'false',
		) );

		$cmb_footer_layout->add_field( array(
			'name'             => esc_html__( 'Disable footer asynchronous load for blog?', 'enovathemes-addons' ),
			'id'               => $prefix . 'dis_async_blog',
			'type'             => 'checkbox',
		) );

		$cmb_footer_layout->add_field( array(
			'name'             => esc_html__( 'Disable footer asynchronous load for shop?', 'enovathemes-addons' ),
			'id'               => $prefix . 'dis_async_shop',
			'type'             => 'checkbox',
		) );

		$cmb_footer_layout->add_field( array(
			'name'             => esc_html__( 'Disable footer asynchronous load for pages (enter comma separated page IDs without space)?', 'enovathemes-addons' ),
			'id'               => $prefix . 'dis_async_page',
			'type'             => 'text',
		) );

		$cmb_footer_layout->add_field( array(
			'name'             => esc_html__( 'Footer placeholder height in px (input only integer value)', 'enovathemes-addons' ),
			'id'               => $prefix . 'footer_placeholder',
			'type'             => 'text_medium',
		) );

		$cmb_footer_layout->add_field( array(
			'name'    => esc_html__( 'Footer placeholder color', 'enovathemes-addons' ),
			'id'      => $prefix . 'footer_placeholder_color',
			'type'    => 'colorpicker',
			'default' => '#184363',
		) );

		$cmb_footer_layout->add_field( array(
			'name'             => esc_html__( 'Sticky?', 'enovathemes-addons' ),
			'id'               => $prefix . 'sticky',
			'type'             => 'checkbox',
		) );

	/*  Header
	/*-------------------*/

		$cmb_header_layout = new_cmb2_box( array(
			'id'            => $prefix.'header_options_metabox',
			'title'         => esc_html__( 'Header options', 'enovathemes-addons' ),
			'object_types'  => array( 'header', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );

		$cmb_header_layout->add_field( array(
			'name'             => esc_html__( 'Header type', 'enovathemes-addons' ),
			'id'               => $prefix . 'header_type',
			'type'             => 'select',
			'classes'          => 'select-230',
			'options'          => array(
				'desktop'      => esc_html__( 'Desktop', 'enovathemes-addons' ),
				'mobile'       => esc_html__( 'Mobile', 'enovathemes-addons' ),
				'sidebar'      => esc_html__( 'Sidebar', 'enovathemes-addons' ),
			),
			'default' => 'desktop',
		) );

		$cmb_header_layout->add_field( array(
			'name'             => esc_html__( 'Transparent', 'enovathemes-addons' ),
			'id'               => $prefix . 'transparent',
			'type'             => 'checkbox',
			'classes'          => 'sidebar-off',
		) );

		$cmb_header_layout->add_field( array(
			'name'             => esc_html__( 'Sticky', 'enovathemes-addons' ),
			'id'               => $prefix . 'sticky',
			'type'             => 'checkbox',
			'classes'          => 'sidebar-off',
		) );

		$cmb_header_layout->add_field( array(
			'name'             => esc_html__( 'Shadow', 'enovathemes-addons' ),
			'id'               => $prefix . 'shadow',
			'type'             => 'checkbox',
			'classes'          => 'sidebar-off',
		) );
		$cmb_header_layout->add_field( array(
			'name'             => esc_html__( 'Shadow on sticky', 'enovathemes-addons' ),
			'id'               => $prefix . 'shadow_sticky',
			'type'             => 'checkbox',
			'classes'          => 'sidebar-off',
		) );

	/*  Banner
	/*-------------------*/

		$cmb_banner_layout = new_cmb2_box( array(
			'id'            => $prefix.'banner_options_metabox',
			'title'         => esc_html__( 'Banner options', 'enovathemes-addons' ),
			'object_types'  => array( 'banner', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );

	/*  Megamenu
	/*-------------------*/

		$cmb_megamenu_layout = new_cmb2_box( array(
			'id'            => $prefix.'megamenu_options_metabox',
			'title'         => esc_html__( 'Megamenu options', 'enovathemes-addons' ),
			'object_types'  => array( 'megamenu', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Megamenu width', 'enovathemes-addons' ),
			'id'               => $prefix . 'megamenu_width',
			'type'             => 'select',
			'classes'          => 'select-230',
			'options'          => array(
				'100'=> '100%',
				'grid' => 'grid width',
				'80' => '80%',
				'70' => '70%',
				'60' => '60%',
				'50' => '50%',
				'40' => '40%',
				'30' => '30%',
				'20' => '20%',
			),
			'default' => 'grid',
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Megamenu position', 'enovathemes-addons' ),
			'id'               => $prefix . 'megamenu_position',
			'type'             => 'select',
			'classes'          => 'select-230 megamenu-toggle',
			'options'          => array(
				'left'=> 'left',
				'right' => 'right',
				'center' => 'center',
			),
			'default' => 'left',
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'    => esc_html__( 'Megamenu horizontal offset in px', 'enovathemes-addons' ),
			'description' => esc_html__( 'Enter negative or positive integer value without any string', 'enovathemes-addons' ),
			'id'      => $prefix . 'megamenu_offset',
			'classes'          => 'megamenu-toggle',
			'type'    => 'text_small',
			'default' => '',
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Use this megamenu as sidebar menu submenu?', 'enovathemes-addons' ),
			'id'               => $prefix . 'sidebar',
			'type'             => 'checkbox',
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Use this megamenu as tabbed megamenu?', 'enovathemes-addons' ),
			'description'      => esc_html__( 'Make sure to add tabs description to section element advanced settings / Enovathemes', 'enovathemes-addons' ),
			'id'               => $prefix . 'tabbed',
			'type'             => 'checkbox',
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Tabs background color', 'enovathemes-addons' ),
			'id'               => $prefix . 'tabs_background_color',
			'classes'          => 'custom-tab-styling',
			'type'             => 'colorpicker',
		) );
	
		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Tabs background color active', 'enovathemes-addons' ),
			'id'               => $prefix . 'tabs_background_color_active',
			'type'             => 'colorpicker',
			'classes'          => 'custom-tab-styling',
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Tabs content background color', 'enovathemes-addons' ),
			'id'               => $prefix . 'tabs_content_background_color',
			'type'             => 'colorpicker',
			'classes'          => 'custom-tab-styling',
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Tabs text color', 'enovathemes-addons' ),
			'id'               => $prefix . 'tabs_text_color',
			'type'             => 'colorpicker',
			'classes'          => 'custom-tab-styling',
		) );

		$cmb_megamenu_layout->add_field( array(
			'name'             => esc_html__( 'Tabs text color active', 'enovathemes-addons' ),
			'id'               => $prefix . 'tabs_text_color_active',
			'type'             => 'colorpicker',
			'classes'          => 'custom-tab-styling',
		) );

	/*  Pages
	/*-------------------*/

		$cmb_page_layout = new_cmb2_box( array(
			'id'            => $prefix.'page_options_metabox',
			'title'         => esc_html__( 'Page options', 'enovathemes-addons' ),
			'object_types'  => array( 'page', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );

		/*  Headers
		/*-------------------*/

			$header_list = enovathemes_addons_headers_list();
	
			$headers_array = array(
                'none'    => esc_html__( 'None', 'enovathemes-addons' ),
                'default' => esc_html__( 'Default', 'enovathemes-addons' ),
                'inherit' => esc_html__( 'Inherit', 'enovathemes-addons' ),
            );

			if (!is_wp_error($header_list)) {
				foreach($header_list as $id => $opt){
					$headers_array[$opt["ID"]] = $opt["title"];
				}
			}
	
	        $cmb_page_layout->add_field( array(
				'name'             => esc_html__( 'Mobile header', 'enovathemes-addons' ),
				'id'               => $prefix . 'mobile_header',
				'type'             => 'select',
				'classes'          => 'select-230',
				'options'          => $headers_array,
				'default' => 'inherit',
			) );
			$cmb_page_layout->add_field( array(
				'name'             => esc_html__( 'Desktop header', 'enovathemes-addons' ),
				'id'               => $prefix . 'desktop_header',
				'type'             => 'select',
				'classes'          => 'select-230',
				'options'          => $headers_array,
				'default' => 'inherit',
			) );

		/*  Footers
		/*-------------------*/

			$footer_list = enovathemes_addons_footers_list();

			$footers_array = array(
                'none'    => esc_html__( 'None', 'enovathemes-addons' ),
                'default' => esc_html__( 'Default', 'enovathemes-addons' ),
                'inherit' => esc_html__( 'Inherit', 'enovathemes-addons' ),
            );
	
			if (!is_wp_error($footer_list)) {
				foreach($footer_list as $id => $label){
					$footers_array[$opt["ID"]] = $opt["title"];
				}
			}

	        $cmb_page_layout->add_field( array(
				'name'             => esc_html__( 'Footer', 'enovathemes-addons' ),
				'id'               => $prefix . 'footer',
				'type'             => 'select',
				'classes'          => 'select-230',
				'options'          => $footers_array,
				'default' => 'inherit',
			) );

	    $cmb_page_layout->add_field( array(
			'name'             => esc_html__( 'Disable title section', 'enovathemes-addons' ),
			'id'               => $prefix . 'title_section',
			'type'             => 'checkbox',
		) );
	
		$cmb_page_layout->add_field( array(
			'name'             => esc_html__( 'Page full width', 'enovathemes-addons' ),
			'id'               => $prefix . 'page_full_width',
			'type'             => 'checkbox',
		) );

	/*  Posts
	/*-------------------*/

		$cmb_post_layout = new_cmb2_box( array(
			'id'            => $prefix.'post_options_metabox',
			'title'         => esc_html__( 'Post options', 'enovathemes-addons' ),
			'object_types'  => array( 'post', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );

		$cmb_post_layout->add_field( array(
			'name'    => esc_html__( 'Format gallery', 'enovathemes-addons' ),
			'id'      => $prefix . 'gallery',
			'type'    => 'file_list',
			'classes' => 'gallery-format post-data',
			'preview_size' => array( 100, 100 ),
			'query_args' => array( 'type' => 'image' ),
		) );

		$cmb_post_layout->add_field( array(
			'name'    => esc_html__( 'MP4 video file', 'enovathemes-addons' ),
			'desc'    => esc_html__( 'Upload an MP4 video file or enter an URL.', 'enovathemes-addons' ),
			'id'      => $prefix . 'video',
			'classes' => 'video-format post-data',
			'type'    => 'file',
			'query_args' => array(
				'type' => 'video/mp4',
			)
		) );

		$cmb_post_layout->add_field( array(
			'name'    => esc_html__( 'Video embed', 'enovathemes-addons' ),
			'id'      => $prefix . 'video_embed',
			'classes' => 'video-format post-data',
			'type'    => 'oembed',
		) );

	/*  Products
	/*-------------------*/

		$banner_list = enovathemes_addons_banners();

		$banner_array = array(
            'none' => esc_html__( 'None', 'enovathemes-addons' ),
        );

		if (!is_wp_error($banner_list)) {
			foreach ($banner_list as $key => $value) {
				$banner_array[$key] = $value['title'];
			}
		}

		$cmb_products_features = new_cmb2_box( array(
			'id'            => $prefix.'products_features_metabox',
			'title'         => esc_html__( 'Products features', 'enovathemes-addons' ),
			'object_types'  => array( 'product', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );
	
		$cmb_products_features->add_field( array(
			'name' => esc_html__( 'Features', 'enovathemes-addons' ),
			'type' => 'textarea',
			'id'   => $prefix .'features',
		));

		$cmb_products_features->add_field( array(
			'name' => esc_html__( 'Wishlist', 'enovathemes-addons' ),
			'type' => 'hidden',
			'id'   => $prefix .'wishlist',
		));

		/*----------------------------------------------------------------*/

		$cmb_products_optons = new_cmb2_box( array(
			'id'            => $prefix.'products_vehicles_metabox',
			'title'         => esc_html__( 'Products vehicles', 'enovathemes-addons' ),
			'object_types'  => array( 'product', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		));

		$cmb_products_uni = new_cmb2_box( array(
			'id'            => $prefix.'products_universal_vehicles_metabox',
			'title'         => esc_html__( 'Universal product', 'enovathemes-addons' ),
			'object_types'  => array( 'product', ), // Post type
			'context'       => 'side',
			'priority'      => 'high',
			'show_names'    => true
		));

		$cmb_products_uni->add_field( array(
			'name' => esc_html__( 'Universal product?', 'enovathemes-addons' ),
			'description' => esc_html__( 'Toggle this checkbox if you want to show this product for all vehicles', 'enovathemes-addons' ),
			'type' => 'checkbox',
			'id'   => $prefix .'universal',
		));

		/*----------------------------------------------------------------*/

		$cmb_products_banners = new_cmb2_box( array(
			'id'            => $prefix.'products_banners_metabox',
			'title'         => esc_html__( 'Products banners', 'enovathemes-addons' ),
			'object_types'  => array( 'product', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );

		$cmb_products_banners->add_field( array(
			'name'    => esc_html__( 'Summary banner', 'enovathemes-addons' ),
			'id'      => $prefix . 'summary_banner',
			'type'    => 'select',
			'classes' => 'select-230',
			'options' => $banner_array,
		) );

		$cmb_products_banners->add_field( array(
			'name'    => esc_html__( 'After summary banner', 'enovathemes-addons' ),
			'id'      => $prefix . 'after_summary_banner',
			'type'    => 'select',
			'classes' => 'select-230',
			'options' => $banner_array,
		) );

		$cmb_products_banners->add_field( array(
			'name'    => esc_html__( 'After main description banner', 'enovathemes-addons' ),
			'id'      => $prefix . 'after_description_banner',
			'type'    => 'select',
			'classes' => 'select-230',
			'options' => $banner_array,
		) );

		$cmb_products_banners->add_field( array(
			'name'    => esc_html__( 'Main description next banner', 'enovathemes-addons' ),
			'id'      => $prefix . 'next_description_banner',
			'type'    => 'select',
			'classes' => 'select-230',
			'options' => $banner_array,
		) );

		$cmb_products_banners->add_field( array(
			'name'    => esc_html__( 'Footer banner', 'enovathemes-addons' ),
			'id'      => $prefix . 'footer_banner',
			'type'    => 'select',
			'classes' => 'select-230',
			'options' => $banner_array,
		) );

		/*----------------------------------------------------------------*/

		$cmb_products_faq = new_cmb2_box( array(
			'id'            => $prefix.'products_faq_metabox',
			'title'         => esc_html__( 'FAQ', 'enovathemes-addons' ),
			'object_types'  => array( 'product', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true
		) );

		$cmb_products_faq->add_field( array(
		    'name' => esc_html__('FAQ','enovathemes-addons'),
		    'desc' => esc_html__('Product frequently asked questions','enovathemes-addons'),
		    'type' => 'title',
		    'id'   => 'faq_title'
		) );

		$cmb_faq = $cmb_products_faq->add_field( array(
			'id'  => 'faq',
			'type'=> 'group',
			'options'  => array(
				'sortable' => true,
			),
		) );

		$cmb_products_faq->add_group_field( $cmb_faq, array(
			'name' => esc_html__('Title','enovathemes-addons'),
			'id'   => 'title',
			'type' => 'text',
		) );

		$cmb_products_faq->add_group_field( $cmb_faq, array(
			'name' => esc_html__('Content','enovathemes-addons'),
			'id'   => 'value',
			'type' => 'wysiwyg',
		) );

	/*  User
	/*-------------------*/

		$cmb_user = new_cmb2_box( array(
			'id'               => $prefix.'user_vehicles',
			'title'            => esc_html__( 'Vehicles', 'enovathemes-addons' ),
			'object_types'     => array( 'user', ),
			'show_names'       => true,
			'new_user_section' => 'add-existing-user'
		) );

		$cmb_user->add_field( array(
			'name' => esc_html__( 'Vehicles', 'enovathemes-addons' ),
			'type' => 'text',
			'id'   => $prefix.'user_vehicles',
		));
}
?>