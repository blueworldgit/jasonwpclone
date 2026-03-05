<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

use Kirki\Util\Helper;

add_action('init',function(){

$header_list = enovathemes_addons_headers_list();
$footer_list = enovathemes_addons_footers_list();

$headers_array = array(
    'none'    => esc_html__( 'None', 'enovathemes-addons' ),
    'default' => esc_html__( 'Default', 'enovathemes-addons' ),
);


$footers_array = array(
    'none'    => esc_html__( 'None', 'enovathemes-addons' ),
    'default' => esc_html__( 'Default', 'enovathemes-addons' ),
);

if (!is_wp_error($header_list)) {
    foreach($header_list as $header => $opt){
        if (isset($opt['ID'])) {
            $headers_array[$opt['ID']] = $opt['title'];
        }
    }
}

if (!is_wp_error($footer_list)) {
    foreach($footer_list as $footer => $opt){
        if (isset($opt['ID'])) {
            $footers_array[$opt['ID']] = $opt['title'];
        }
    }
}

new \Kirki\Panel(
    'enovathemes_panel',
    [
        'priority'    => 10,
        'title'       => esc_html__( 'Theme settings', 'enovathemes-addons' ),
    ]
);

$sections = [
    'styling'    => [ esc_html__( 'Styling', 'enovathemes-addons' ), '' ],
    'typography' => [ esc_html__( 'Typography', 'enovathemes-addons' ), '' ],
    'blog'       => [ esc_html__( 'Blog', 'enovathemes-addons' ), '' ],
    'shop'       => [ esc_html__( 'Woocommerce', 'enovathemes-addons' ), '' ],
    'vehicle'    => [ esc_html__( 'Vehicle filter', 'enovathemes-addons' ), '' ],
    'sticky_dashboard' => [ esc_html__( 'Mobile sticky dashboard', 'enovathemes-addons' ), '' ],
    'misc'       => [ esc_html__( 'Misc', 'enovathemes-addons' ), '' ],
];

foreach ( $sections as $section_id => $section ) {
    $section_args = [
        'title'       => $section[0],
        'description' => $section[1],
        'panel'       => 'enovathemes_panel',
    ];
    if ( isset( $section[2] ) ) {
        $section_args['type'] = $section[2];
    }
    new \Kirki\Section( str_replace( '-', '_', $section_id ) . '_section', $section_args );
}

global $wpdb;

// Get the 'woocommerce_myaccount_page_id' option value directly from the database
$myaccount_page_id = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'woocommerce_myaccount_page_id'");

// Get the permalink for the page ID
$myaccount_page_url = $wpdb->get_var($wpdb->prepare(
    "SELECT guid FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'page' AND post_status = 'publish'",
    $myaccount_page_id
));

/*  Styling
/*-------------------*/

    new \Kirki\Field\Color(
        [
            'settings'    => 'main_color',
            'label'       => esc_html__( 'Main color', 'enovathemes-addons' ),
            'section'     => 'styling_section',
            'default'     => '#034c8c',
        ],
    );

    new \Kirki\Field\Color(
        [
            'settings'    => 'second_color',
            'label'       => esc_html__( 'Second color', 'enovathemes-addons' ),
            'section'     => 'styling_section',
            'default'     => '#f29f05',
        ]
    );

    new \Kirki\Field\Color(
        [
            'settings'    => 'accent_color',
            'label'       => esc_html__( 'Accent color', 'enovathemes-addons' ),
            'section'     => 'styling_section',
            'default'     => '#bf3617',
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'layout',
            'label'       => esc_html__( 'Layout', 'enovathemes-addons' ),
            'section'     => 'styling_section',
            'default'     => 'wide',
            'placeholder' => esc_html__( 'Select layout', 'enovathemes-addons' ),
            'choices'     => [
                'wide'  => esc_html__( 'Wide', 'enovathemes-addons' ),
                'boxed' => esc_html__( 'Boxed', 'enovathemes-addons' ),
            ],
        ]
    );

    new \Kirki\Field\Background(
        [
            'settings'    => 'site_background',
            'label'       => esc_html__( 'Site background', 'enovathemes-addons' ),
            'section'     => 'styling_section',
            'default'     => [
                'background-color'      => '#ffffff',
                'background-image'      => '',
                'background-repeat'     => 'repeat',
                'background-position'   => 'center center',
                'background-size'       => 'cover',
                'background-attachment' => 'scroll',
            ],
            'active_callback' => [
                [
                    'setting'  => 'layout',
                    'operator' => '==',
                    'value'    => 'boxed',
                ]
            ]
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'desktop_header',
            'label'       => esc_html__( 'Desktop header', 'enovathemes-addons' ),
            'section'     => 'styling_section',
            'default'     => 'default',
            'placeholder' => esc_html__( 'Select header', 'enovathemes-addons' ),
            'choices'     => $headers_array,
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'mobile_header',
            'label'       => esc_html__( 'Mobile header', 'enovathemes-addons' ),
            'section'     => 'styling_section',
            'default'     => 'default',
            'placeholder' => esc_html__( 'Select header', 'enovathemes-addons' ),
            'choices'     => $headers_array,
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'footer',
            'label'       => esc_html__( 'Footer', 'enovathemes-addons' ),
            'section'     => 'styling_section',
            'default'     => 'default',
            'placeholder' => esc_html__( 'Select footer', 'enovathemes-addons' ),
            'choices'     => $footers_array,
        ]
    );

/*  Typography
/*-------------------*/

    new \Kirki\Field\Typography(
        [
            'settings'    => 'main_typography',
            'label'       => esc_html__( 'Main typography', 'enovathemes-addons' ),
            'section'     => 'typography_section',
            'priority'    => 10,
            'default'     => [
                'font-family'     => 'inter',
                'variant'         => 'regular',
                'color'           => '#777777',
                'font-size'       => '13px',
                'line-height'     => '1.8',
                'letter-spacing'  => '0',
            ],
            'choices'     => [
                'fonts' => [
                    'google',
                ],
            ],
        ]
    );

    new \Kirki\Field\Typography(
        [
            'settings'    => 'headings_typography',
            'label'       => esc_html__( 'Headings typography', 'enovathemes-addons' ),
            'section'     => 'typography_section',
            'priority'    => 10,
            'default'     => [
                'font-family'     => 'inter',
                'variant'         => '700',
                'color'           => '#111111',
                'line-height'     => '1.5',
                'letter-spacing'  => '0',
            ],
            'choices'     => [
                'fonts' => [
                    'google',
                ],
            ],
        ]
    );

/*  Blog
/*-------------------*/

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'blog_sidebar',
            'label'       => esc_html__( 'Archive sidebar', 'enovathemes-addons' ),
            'section'     => 'blog_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'post_sidebar',
            'label'       => esc_html__( 'Single post sidebar', 'enovathemes-addons' ),
            'section'     => 'blog_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'blog_navigation',
            'label'       => esc_html__( 'Navigation', 'enovathemes-addons' ),
            'section'     => 'blog_section',
            'default'     => 'pagination',
            'placeholder' => esc_html__( 'Select navigation', 'enovathemes-addons' ),
            'choices'     => [
                'pagination'  => esc_html__( 'Pagination', 'enovathemes-addons' ),
                'loadmore' => esc_html__( 'Load more button', 'enovathemes-addons' ),
                'infinite' => esc_html__( 'Scroll down to load', 'enovathemes-addons' ),
            ],
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'blog_layout',
            'label'       => esc_html__( 'Layout', 'enovathemes-addons' ),
            'section'     => 'blog_section',
            'default'     => 'masonry',
            'placeholder' => esc_html__( 'Select layout', 'enovathemes-addons' ),
            'choices'     => [
                'masonry'  => esc_html__( 'Masonry', 'enovathemes-addons' ),
                'grid'     => esc_html__( 'Grid 1', 'enovathemes-addons' ),
                'grid-2'   => esc_html__( 'Grid 2', 'enovathemes-addons' ),
                'grid-3'   => esc_html__( 'Grid 3', 'enovathemes-addons' ),
                'list'     => esc_html__( 'List', 'enovathemes-addons' ),
                'full'     => esc_html__( 'Full', 'enovathemes-addons' ),
            ],
        ]
    );

    new \Kirki\Field\Slider(
        [
            'settings'    => 'post_title_length',
            'label'       => esc_html__( 'Post title length (characters)', 'enovathemes-addons' ),
            'section'     => 'blog_section',
            'default'     => '56',
            'choices'     => [
                'min'  => 0,
                'max'  => 500,
                'step' => 1,
            ],
        ]
    );

    new \Kirki\Field\Slider(
        [
            'settings'    => 'post_excerpt_length',
            'label'       => esc_html__( 'Post excerpt length (characters)', 'enovathemes-addons' ),
            'section'     => 'blog_section',
            'default'     => '128',
            'choices'     => [
                'min'  => 0,
                'max'  => 500,
                'step' => 1,
            ],
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'post_social_share',
            'label'       => esc_html__( 'Single post social share', 'enovathemes-addons' ),
            'section'     => 'blog_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'post_related_posts',
            'label'       => esc_html__( 'Related posts', 'enovathemes-addons' ),
            'section'     => 'blog_section',
            'default'     => true,
        ]
    );

/*  Shop
/*-------------------*/

    function et_get_attribute_taxonomies() {
        global $wpdb;

        // Define the attribute taxonomy table name
        $attribute_taxonomies_table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';

        // Build and run the SQL query
        $sql = "SELECT * FROM $attribute_taxonomies_table";
        $results = $wpdb->get_results($sql);

        // Convert results to array of objects
        $attribute_taxonomies = array_map(function ($result) {
            return (object) $result;
        }, $results);

        return $attribute_taxonomies;
    }

    $woo_attributes_opt = array();

    $woo_attributes = et__get_attribute_taxonomies();
    if (!empty($woo_attributes) && !is_wp_error($woo_attributes)) {
        foreach( $woo_attributes as $attribute) {
            $woo_attributes_opt[$attribute->attribute_name] = $attribute->attribute_label;
        }
    }

    if (!empty($woo_attributes_opt)) {
        new \Kirki\Field\Select(
            [
                'settings'    => 'product_attributes',
                'label'       => esc_html__( 'Product attributes in admin product table', 'enovathemes-addons' ),
                'description' => esc_html__( 'Select global attributes for products that you want to add to the product table in the admin area.', 'enovathemes-addons' ),
                'section'     => 'shop_section',
                'multiple'    => 5,
                'choices'     => $woo_attributes_opt,
            ]
        );
    }

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'product_ajax_filter',
            'label'       => esc_html__( 'Product ajax filter', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Textarea(
        [
            'settings' => 'product_ajax_filter_keywrods',
            'label'    => esc_html__( 'Filter keywords for product ajax filter', 'enovathemes-addons' ),
            'default'    => 'post_type, lang',
            'description'    => esc_html__( 'Enter comma separated keywords', 'enovathemes-addons' ),
            'section'  => 'shop_section',
            'active_callback' => [
                [
                    'setting'  => 'product_ajax_filter',
                    'operator' => '===',
                    'value'    => true,
                ]
            ]
        ]
    );

    $threshold = [];

    for ($i = 0.0; $i <= 1.0; $i = round($i + 0.1, 1)) {
        $key = sprintf("%.1f", $i); // Ensures correct string representation
        $threshold[$key] = $key;
    }

    new \Kirki\Field\Select(
        [
            'settings'    => 'product_ajax_search_threshold',
            'label'       => esc_html__( 'Product ajax threshold', 'enovathemes-addons' ),
            'description' => esc_html__( 'At what point does the match algorithm give up. A threshold of 0.0 requires a perfect match (of both letters and location), a threshold of 1.0 would match anything.', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => '0.1',
            'choices'     => $threshold
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'product_ajax_search_in',
            'label'       => esc_html__( 'Product ajax search in:', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'placeholder' => esc_html__( 'Select search areas', 'enovathemes-addons' ),
            'multiple'    => 10,
            // 'default'     => ['title','description','sku'],
            'choices'     => [
                'title'             => esc_html__( 'Title', 'enovathemes-addons' ),
                'description'       => esc_html__( 'Description', 'enovathemes-addons' ),
                'sku'               => esc_html__( 'SKU', 'enovathemes-addons' ),
                'ean'               => esc_html__( 'GTIN, UPC, EAN, or ISBN', 'enovathemes-addons' ),
                'category'          => esc_html__( 'Categories', 'enovathemes-addons' ),
                'tag'               => esc_html__( 'Tags', 'enovathemes-addons' ),
                'global_attributes' => esc_html__( 'Global attributes', 'enovathemes-addons' ),
                'custom_attributes' => esc_html__( 'Custom attributes', 'enovathemes-addons' ),
            ],
        ]
    );

    if (!empty($woo_attributes_opt)) {
        new \Kirki\Field\Select(
            [
                'settings'    => 'product_ajax_search_in_global_attributes',
                'label'       => esc_html__( 'Product global attributes to search in', 'enovathemes-addons' ),
                'description' => esc_html__( 'Select global attributes that you want to search in.', 'enovathemes-addons' ),
                'section'     => 'shop_section',
                'multiple'    => 5,
                'choices'     => $woo_attributes_opt,
                'active_callback' => [
                    [
                        'setting'  => 'product_ajax_search_in',
                        'operator' => 'contains',
                        'value'    => 'global_attributes',
                    ]
                ]
            ]
        );
    }

    new \Kirki\Field\Textarea(
        [
            'settings' => 'product_ajax_search_in_custom_attributes',
            'label'       => esc_html__( 'Product custom attributes to search in', 'enovathemes-addons' ),
            'description' => esc_html__( 'Enter comma separated custom attributes that you want to search in.', 'enovathemes-addons' ),
            'section'  => 'shop_section',
            'active_callback' => [
                [
                    'setting'  => 'product_ajax_search_in',
                    'operator' => 'contains',
                    'value'    => 'custom_attributes',
                ]
            ]
        ]
    );

    new \Kirki\Field\Slider(
        [
            'settings'    => 'product_number',
            'label'       => esc_html__( 'Number of products', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => 20,
            'choices'     => [
                'min'  => 0,
                'max'  => 50,
                'step' => 1,
            ],
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'category_carousel',
            'label'       => esc_html__( 'Categories carousel', 'enovathemes-addons' ),
            'description' => esc_html__( 'Before main loop area', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'include_universal_in_search',
            'label'       => esc_html__( 'Include universal products in filter results?', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => false,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'shop_sidebar',
            'label'       => esc_html__( 'Archive sidebar', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'shop_sidebar_toggle',
            'label'       => esc_html__( 'Archive sidebar with toggle?', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => false,
            'active_callback' => [
                [
                    'setting'  => 'shop_sidebar',
                    'operator' => '==',
                    'value'    => true,
                ]
            ]
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'shop_navigation',
            'label'       => esc_html__( 'Navigation', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => 'pagination',
            'placeholder' => esc_html__( 'Select navigation', 'enovathemes-addons' ),
            'description' => esc_html__( 'This functionality is only available with the old version of the product AJAX filter. In the new AJAX filter, pagination is the only supported option.', 'enovathemes-addons' ),
            'choices'     => [
                'pagination'  => esc_html__( 'Pagination', 'enovathemes-addons' ),
                'loadmore' => esc_html__( 'Load more button', 'enovathemes-addons' ),
                'infinite' => esc_html__( 'Scroll down to load', 'enovathemes-addons' ),
            ],
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'shop_layout',
            'label'       => esc_html__( 'Archive layout', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => 'grid',
            'placeholder' => esc_html__( 'Select layout', 'enovathemes-addons' ),
            'choices'     => [
                'grid' => esc_html__( 'Grid', 'enovathemes-addons' ),
                'list' => esc_html__( 'List', 'enovathemes-addons' ),
                'comp' => esc_html__( 'Detailed', 'enovathemes-addons' ),
            ],
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'product_layout',
            'label'       => esc_html__( 'Single product layout', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => 'simple',
            'placeholder' => esc_html__( 'Select product layout', 'enovathemes-addons' ),
            'choices'     => [
                'simple'   => esc_html__( 'Simple', 'enovathemes-addons' ),
                'advanced' => esc_html__( 'Advanced', 'enovathemes-addons' ),
            ],
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'product_quick_buy',
            'label'       => esc_html__( 'Buy now button?', 'enovathemes-addons' ),
            'section'     => 'shop_section',
        ]
    );

    new \Kirki\Field\Text(
        [
            'settings'    => 'product_notfound_form',
            'label'       => esc_html__( 'Contact form id to display if no products found', 'enovathemes-addons' ),
            'section'     => 'shop_section',
        ]
    );

    new \Kirki\Field\Slider(
        [
            'settings'    => 'product_title_length',
            'label'       => esc_html__( 'Product title length (characters)', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => '56',
            'choices'     => [
                'min'  => 0,
                'max'  => 500,
                'step' => 1,
            ],
        ]
    );

    new \Kirki\Field\Slider(
        [
            'settings'    => 'product_title_min',
            'label'       => esc_html__( 'Product title minimum height (px)', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => '48',
            'choices'     => [
                'min'  => 0,
                'max'  => 150,
                'step' => 1,
            ],
        ]
    );

    new \Kirki\Field\Slider(
        [
            'settings'    => 'product_title_max',
            'label'       => esc_html__( 'Product title maximum height (px)', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => '48',
            'choices'     => [
                'min'  => 0,
                'max'  => 150,
                'step' => 1,
            ],
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'product_quick_view',
            'label'       => esc_html__( 'AJAX Product quick view', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'product_wishlist',
            'label'       => esc_html__( 'AJAX Product wishlist', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => true,

        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'product_compare',
            'label'       => esc_html__( 'AJAX Product compare', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Text(
        [
            'settings'    => 'product_ask_form',
            'label'       => esc_html__( 'Single product question form id', 'enovathemes-addons' ),
            'section'     => 'shop_section',
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'product_social_share',
            'label'       => esc_html__( 'Single product social share', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'product_related_products',
            'label'       => esc_html__( 'Related products', 'enovathemes-addons' ),
            'section'     => 'shop_section',
            'default'     => true,
        ]
    );

/*  Vehicle
/*-------------------*/

    new \Kirki\Field\Textarea(
        [
            'settings' => 'vehicle_params',
            'label'    => esc_html__( 'Vehicle parameters', 'enovathemes-addons' ),
            'default'    => 'make, model, year, trim',
            'description'    => esc_html__( 'Enter comma separated vehicle parameters (english only!), all lowercase, dash istead of space between words', 'enovathemes-addons' ),
            'section'  => 'vehicle_section',
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'my_account_vehicles',
            'label'       => esc_html__( 'Show "My garage" section in my account?"', 'enovathemes-addons' ),
            'section'     => 'vehicle_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'vehicle_cookies',
            'label'       => esc_html__( 'Use vehicle cookies in vehicle filter?', 'enovathemes-addons' ),
            'description' => esc_html__( 'Once filtered, the vehicle is saved in cookies and all the time users get product list in shop, categories based on the selected vehicle, untill filter reset', 'enovathemes-addons' ),
            'section'     => 'vehicle_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'vin_user_vehicles',
            'label'       => esc_html__( 'Enable VIN search in user dashboard "My garage" section?', 'enovathemes-addons' ),
            'section'     => 'vehicle_section',
            'default'     => false,
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'vin_decoder',
            'label'       => esc_html__( 'Choose VIN decoder', 'enovathemes-addons' ),
            'section'     => 'vehicle_section',
            'default'     => '//api.vindecoder.eu/3.2',
            'placeholder' => esc_html__( 'Select VIN decoder', 'enovathemes-addons' ),
            'choices'     => [
                'https://api.vindecoder.eu/3.2' => 'vindecoder.eu',
                'https://auto.dev/api/vin' => 'auto.dev',
                'https://specifications.vinaudit.com/v3/specifications' => 'vinaudit.com',
                'https://api.vehicledatabases.com/europe-vin-decode' => 'vehicledatabases.com',
                'https://api.vehicledatabases.com/uk-registration-decode' => 'vehicledatabases.com (UK registration number)',
                'https://uk1.ukvehicledata.co.uk/api/datapackage/VehicleData' => 'ukvehicledata.co.uk (UK registration number)',
                'http://api.marketcheck.com/v2/decode/car' => 'marketcheck.com',
                'https://app.auto-ways.net/api/v1' => 'auto-ways.net (decode by plate)',
                'https://api.biluppgifter.se/api/v1/vehicle/regno' => 'biluppgifter.se (decode by registration number)',
                'https://api.biluppgifter.se/api/v1/vehicle/vin' => 'biluppgifter.se (decode by VIN number)',
            ],
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'country',
            'label'       => esc_html__( 'Choose PLATE decoder country', 'enovathemes-addons' ),
            'section'     => 'vehicle_section',
            'choices'     => [
                'fr' => 'France',
                'es' => 'Spain',
                'it' => 'Italy',
                'pt' => 'Portugal',
                'be' => 'Belgium'
            ],
            'active_callback' => [
                [
                    'setting'  => 'vin_decoder',
                    'operator' => '==',
                    'value'    => 'https://app.auto-ways.net/api/v1',
                ]
            ]
        ]
    );

    new \Kirki\Field\Select(
        [
            'settings'    => 'country_biluppgifter',
            'label'       => esc_html__( 'Choose decoder country', 'enovathemes-addons' ),
            'section'     => 'vehicle_section',
            'choices'     => [
                'SE'  => 'Sweden',
                'NO'  => 'Norway',
                'NO2' => 'Finland',
                'DK'  => 'Denmark',
            ],
            'default' => 'SE',
            'active_callback' => function(){
                $vin_decoder = get_theme_mod('vin_decoder');
                
                return ($vin_decoder === 'https://api.biluppgifter.se/api/v1/vehicle/regno' || $vin_decoder === 'https://api.biluppgifter.se/api/v1/vehicle/vin');
            }
        ]
    );

    new \Kirki\Field\Text(
        [
            'settings' => 'vin_key',
            'label'    => esc_html__( 'Decoder api key/token', 'enovathemes-addons' ),
            'section'  => 'vehicle_section',
        ]
    );

    new \Kirki\Field\Text(
        [
            'settings' => 'vin_secret',
            'label'    => esc_html__( 'Decoder secret key', 'enovathemes-addons' ),
            'section'  => 'vehicle_section',
            'active_callback' => [
                [
                    'setting'  => 'vin_decoder',
                    'operator' => '==',
                    'value'    => 'https://api.vindecoder.eu/3.2',
                ]
            ]
        ]
    );

    new \Kirki\Field\Color(
        [
            'settings'    => 'vehicle_background_color',
            'label'       => esc_html__( 'Background color', 'enovathemes-addons' ),
            'section'     => 'vehicle_section',
            'default'     => '#f29f05',
        ]
    );

    new \Kirki\Field\Color(
        [
            'settings'    => 'vehicle_text_color',
            'label'       => esc_html__( 'Text color', 'enovathemes-addons' ),
            'section'     => 'vehicle_section',
            'default'     => '#000000',
        ]
    );

/*  Mobile sticky dashboard
/*-------------------*/
    
    $languages    = function_exists('et__list_languages') ? et__list_languages() : false;
    $default_lang = function_exists('et__default_language') ? et__default_language() : false;

    $fields = [
        'list_class'   => [
            'type'        => 'text',
            'label'       => esc_html__( 'List class', 'enovathemes-addons' ),
            'default'     => '',
        ],
        'link_class'   => [
            'type'        => 'text',
            'label'       => esc_html__( 'Link Class', 'enovathemes-addons' ),
            'default'     => '',
        ],
        'link_target' => [
            'type'        => 'select',
            'label'       => esc_html__( 'Link target', 'enovathemes-addons' ),
            'default'     => '_self',
            'choices'     => [
                '_blank' => esc_html__( 'New Window', 'enovathemes-addons' ),
                '_self'  => esc_html__( 'Same Frame', 'enovathemes-addons' ),
            ],
        ],
        'link_icon' => [
            'type'        => 'upload',
            'label'       => esc_html__( 'Link icon', 'enovathemes-addons' ),
        ]
    ];

    $fields_atts = [
        'link_text'   => [
            'type'        => 'text',
            'label'       => esc_html__( 'Label', 'enovathemes-addons' ),
            'default'     => '',
        ],
        'link_url'    => [
            'type'        => 'text',
            'label'       => esc_html__( 'Link url', 'enovathemes-addons' ),
            'default'     => '',
        ]
    ];

    if ($languages) {

        foreach ($languages as $lang) {

            if ($lang != $default_lang) {

                $fields_atts['link_text_'.$lang] = [
                    'type'        => 'text',
                    'label'       => strtoupper($lang).' '.esc_html__( 'label', 'enovathemes-addons' ),
                    'default'     => '',
                ];
                $fields_atts['link_url_'.$lang] = [
                    'type'        => 'text',
                    'label'       => strtoupper($lang).' '.esc_html__( 'link url', 'enovathemes-addons' ),
                    'default'     => '',
                ];
            }
        }

    }

    $fields = array_merge($fields_atts,$fields);

    new \Kirki\Field\Repeater(
        [
            'settings' => 'sticky_dashboard',
            'label'    => esc_html__( 'Sticky dashboard', 'enovathemes-addons' ),
            'section'  => 'sticky_dashboard_section',
            'priority' => 10,
            'row_label' => [
                'type'     => 'callback',
                'callback' => function( $row ) {
                    return ! empty( $row['link_text'] ) ? $row['link_text'] : esc_html__( 'Link', 'enovathemes-addons' );
                },
            ],
            'default'  => [
                [
                    'link_text'   => 'Account',
                    'list_class'   => 'account',
                    'link_class'   => '',
                    'link_url'    => esc_url($myaccount_page_url),
                    'link_target' => '_self',
                    'link_icon'   => THEME_SVG.'user.svg',
                ],
                [
                    'link_text'   => 'Categories',
                    'list_class'   => 'categories',
                    'link_class'   => '',
                    'link_url'    => (get_page_by_path('categories') ? home_url('/categories') : ''),
                    'link_target' => '_self',
                    'link_icon'   => THEME_SVG.'categories.svg',
                ],
                [
                    'link_text'   => 'Car filter',
                    'list_class'   => 'vfilter-toggle',
                    'link_class'   => 'vehicle-filter-toggle',
                    'link_url'    => '#',
                    'link_target' => '_self',
                    'link_icon'   => THEME_SVG.'vehicle.svg',
                ],
                [
                    'link_text'   => 'Search',
                    'list_class'   => 'product-search',
                    'link_class'   => 'product-search-toggle',
                    'link_url'    => '#',
                    'link_target' => '_self',
                    'link_icon'   => THEME_SVG.'search.svg',

                ]
            ],
            'fields'   => $fields
        ]
    );

/*  Misc
/*-------------------*/

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'optimize',
            'label'       => esc_html__( 'Optimize site by removing unnecessary assets?', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'font_awesome',
            'label'       => esc_html__( 'Font Awesome support for all the theme?', 'enovathemes-addons' ),
            'description' => esc_html__( 'By default Font Awesome does not work for headers, footers, megamenues, banners. If you use Font Awesome based elements outside of pages/posts toggle this option', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'default'     => false,
        ]
    );

	 new \Kirki\Field\Dropdown_Pages(
        [
            'settings'    => 'error',
            'label'       => esc_html__( 'Error page', 'enovathemes-addons' ),
            'section'     => 'misc_section',
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'instagram',
            'label'       => esc_html__( 'Disable theme styles for Smash Balloon Instagram Feed plugin', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'default'     => true,
        ]
    );

    new \Kirki\Field\Checkbox_Switch(
        [
            'settings'    => 'placeholder',
            'label'       => esc_html__( 'Disable image placeholder', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'default'     => false,
        ]
    );

    new \Kirki\Field\Text(
        [
            'settings' => 'mailchimp_key',
            'label'    => esc_html__( 'Mailchimp api key', 'enovathemes-addons' ),
            'section'  => 'misc_section',
        ]
    );

    new \Kirki\Field\Code(
        [
            'settings'    => 'css',
            'label'       => esc_html__( 'Additional css', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-767',
            'label'       => esc_html__( 'Max width 767 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-768',
            'label'       => esc_html__( 'Min width 768 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-768-1023',
            'label'       => esc_html__( 'Min width 768 and max width 1023 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-1023',
            'label'       => esc_html__( 'Max width 1023 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-1024-1279',
            'label'       => esc_html__( 'Min width 1024 and max width 1279 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-1279',
            'label'       => esc_html__( 'Max width 1279 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-1280-1365',
            'label'       => esc_html__( 'Min width 1280 and max width 1365 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-1280',
            'label'       => esc_html__( 'Min width 1280 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-1366-1599',
            'label'       => esc_html__( 'Min width 1366 and max width 1599 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-1600-1919',
            'label'       => esc_html__( 'Min width 1600 and max width 1919 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

	new \Kirki\Field\Code(
        [
            'settings'    => 'css-1600',
            'label'       => esc_html__( 'Min width 1600 screen width', 'enovathemes-addons' ),
            'section'     => 'misc_section',
            'choices'     => [
                'language' => 'css',
            ]
        ]
    );

});

?>