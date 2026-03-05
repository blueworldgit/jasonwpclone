<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Helper functions
---------------*/

	if( !function_exists('et__get_attribute_display_type') ){

	    function et__get_attribute_display_type($attribute_id) {
	        return get_option('attribute_display_type_' . $attribute_id, 'select');
	    }

	}

    if( !function_exists('et__substrwords') ){

        function et__substrwords($text, $maxchar, $end = '..') {
            // Check if the text is longer than the max character limit
            if ($maxchar && strlen($text) > $maxchar || $text == '') {
                // Cut the string based on max characters
                $output = substr($text, 0, $maxchar);
                $output .= $end; // Add the ending string (e.g., '...' or other)
            } else {
                $output = $text; // If the text is already within the limit, return it as is
            }
            return $output;
        }

    }

	if( !function_exists('et__get_attribute_taxonomies') ){

	    function et__get_attribute_taxonomies() {
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

	}

	if( !function_exists('et__is_light_color') ){

	    function et__is_light_color($hex) {
	        // Remove the hash symbol (#) if it exists
	        $hex = ltrim($hex, '#');
	        
	        // If the hex code is 3 characters, convert it to 6 characters
	        if (strlen($hex) == 3) {
	            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	        }
	        
	        // Convert the hex color to RGB values
	        list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");
	        
	        // Calculate the brightness using the luminance formula
	        $brightness = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
	        
	        // Return true if brightness is high enough to be considered light
	        return $brightness > 127.5; // 127.5 is the threshold (0 to 255 range)
	    }

	}

	if( !function_exists('et__current_language') ){

	    function et__current_language(){

	        $is_wpml     = defined('ICL_SITEPRESS_VERSION');
	        $is_polylang = function_exists('pll_current_language');

	        $current_language = get_locale();
	        $current_language = substr($current_language, 0, 2);

	        if ($is_wpml) {
	            $current_language = apply_filters('wpml_current_language', null);
	        } elseif($is_polylang){
	            $current_language = pll_current_language();
	        }

	        return $current_language;
	    }

	}

    if( !function_exists('et__prepare_html_template') ) {

        function et__prepare_html_template($html) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            et__remove_text_nodes($dom);
            et__cleanup_template($dom);

            // Apply filter before returning
            $html = $dom->saveHTML();
            return apply_filters('et__cleanup_product_template', $html, $dom);
        }


    }

    function et__list_taxonomy_hierarchy_no_instance( $taxonomies, $level = '', $selected = array() ) {
        $output        = '';
        $selected_slugs = array_map( 'sanitize_title', (array) $selected );

        foreach ( (array) $taxonomies as $taxonomy ) {
            // Expecting object with: term_id, slug, name, parent, children (array)
            $term_id  = isset( $taxonomy->term_id ) ? (int) $taxonomy->term_id : 0;
            $slug     = isset( $taxonomy->slug ) ? (string) $taxonomy->slug : '';
            $name     = isset( $taxonomy->name ) ? (string) $taxonomy->name : '';
            $children = ( isset( $taxonomy->children ) && is_array( $taxonomy->children ) ) ? $taxonomy->children : array();

            $is_selected = in_array( $slug, $selected_slugs, true );
            $output     .= '<option value="' . esc_attr( $slug ) . '" data-id="' . esc_attr( $slug ) . '"'
                         . ( $is_selected ? ' selected="selected"' : '' )
                         . '>' . $level . esc_html( $name ) . '</option>';

            if ( ! empty( $children ) ) {
                $next_level = $level . '&nbsp;&nbsp;&nbsp;';
                $output .= et__list_taxonomy_hierarchy_no_instance( $children, $next_level, $selected_slugs );
            }

        }

        return $output;
    }


/* Widgets
---------------*/

    class Et_Widget_WC_Widget_Product_Categories {

        protected $defaults = array(
            'display_type' => 'list'
        );

        protected $widgets = array(
            'woocommerce_product_categories',
        );

        public static $current_display_type = ''; // Static variable to store the display type globally

        function __construct() {
            add_action( 'in_widget_form', array( $this, 'add_settings' ), 10, 3 );
            add_filter( 'widget_update_callback', array( $this, 'save_settings' ), 10, 4 );
            add_filter( 'widget_display_callback', array( $this, 'frontend_settings' ), 10, 3 );
            add_filter( 'wp_list_categories', array( $this, 'modify_category_list' ), 10, 2 );
        }

        public function add_settings( $widget, $return, $instance ) {
            if ( ! $this->is_supported( $widget ) ) {
                return null;
            }

            $instance = wp_parse_args( $instance, $this->defaults ); ?>

            <p>
                <label for="<?php echo $widget->get_field_id('display_type'); ?>"><?php _e('Display Type:', 'enovathemes-addons'); ?></label>
                <select class="widefat" id="<?php echo $widget->get_field_id('display_type'); ?>" name="<?php echo $widget->get_field_name('display_type'); ?>">
                    <option value="list" <?php selected(isset($instance['display_type']) ? $instance['display_type'] : '', 'list'); ?>><?php _e('List', 'enovathemes-addons'); ?></option>
                    <option value="image_list" <?php selected(isset($instance['display_type']) ? $instance['display_type'] : '', 'image_list'); ?>><?php _e('Image List', 'enovathemes-addons'); ?></option>
                    <option value="dropdown" <?php selected(isset($instance['display_type']) ? $instance['display_type'] : '', 'dropdown'); ?>><?php _e('Dropdown', 'enovathemes-addons'); ?></option>
                </select>
            </p>
        <?php }

        public function save_settings( $instance, $new_instance, $old_instance, $widget ) {
            if ( ! $this->is_supported( $widget ) ) {
                return $instance;
            }

            $instance = wp_parse_args( $instance, $this->defaults );

            if ( isset( $new_instance['display_type'] ) ) {
                $display_type = esc_html( $new_instance['display_type'] );
                $instance['display_type'] = $display_type;

                $mods                = et_get_theme_mods();
                $product_ajax_filter = $mods && isset($mods['product_ajax_filter']) && !empty($mods['product_ajax_filter']) ? 1 : 0;

                if ($product_ajax_filter == 0 && $display_type == 'dropdown') {
                    $instance['dropdown'] = 1;
                } else {
                    $instance['dropdown'] = 0;
                }

            }

            return $instance;
        }

        public function frontend_settings( $instance, $widget, $args ) {
            if ( ! $this->is_supported( $widget ) ) {
                return $instance;
            }

            $instance = wp_parse_args( $instance, $this->defaults );

            // Set the global display type variable
            self::$current_display_type = isset($instance['display_type']) ? $instance['display_type'] : 'list';

            return $instance;
        }

        public function modify_category_list( $output, $args ) {
            // Use the global display type variable
            $display_type = self::$current_display_type;

            if (isset($display_type)) {

                if (in_array($display_type, ['image_list', 'list'])) {

                    $output = preg_replace_callback(
                        '/(<li.*?class="[^"]*cat-item[^"]*cat-parent[^"]*"[^>]*>)(.*?)(<ul.*?>)/s',
                        function ($matches) {
                            // Insert the toggle span before the nested <ul>
                            return $matches[1] . $matches[2] . '<span class="cat-toggle"></span>' . $matches[3];
                        },
                        $output
                    );
                }

                // Check if display_type is set to 'image_list'
                if ($display_type === 'image_list') {
                    // Modify the category output to add images for each category item
                    $output = preg_replace_callback(
                        '/(<li.*?class="cat-item cat-item-(\d+).*?>)(<a.*?>)(.*?<\/a>)/i',
                        function ($matches) {
                            // Get the category ID
                            $category_id = $matches[2];

                            $return = $matches[0];

                            // Get the category object

                            // Get the category image URL
                            $thumbnail_id = get_term_meta($category_id, 'thumbnail_id', true);
                            $image        = wp_get_attachment_image_src($thumbnail_id,'woocommerce_thumbnail');
                            $category     = get_term_by('id', $category_id, 'product_cat');

                            $title = '';

                            if ($category) {
                                $title = 'title="' . esc_attr($category->name) . '"';
                            } 

                            // If an image exists, insert it inside the <a> tag
                            if ($image) {
                                
                                $alt = '';

                                if ($category) {
                                    $alt   = 'alt="' . esc_attr($category->name) . '"';
                                } 

                                $return = sprintf(
                                    '%s<a href="%s" %s><img src="%s" %s width="56" height="56" class="category-image" />%s',
                                    $matches[1],
                                    esc_url(get_category_link($category_id)),
                                    $title,
                                    esc_url($image[0]),
                                    $alt,
                                    $matches[4]
                                );

                            } else {

                                $return = sprintf(
                                    '%s<a href="%s" %s>%s',
                                    $matches[1],
                                    esc_url(get_category_link($category_id)),
                                    $title,
                                    $matches[4]
                                );

                            }

                            return $return;

                        },
                        $output
                    );
                } else {
                    $output = preg_replace_callback(
                        '/(<li.*?class="cat-item cat-item-(\d+).*?>)(<a.*?>)(.*?<\/a>)/i',
                        function ($matches) {
                            // Get the category ID
                            $category_id = $matches[2];

                            $category = get_term_by('id', $category_id, 'product_cat');

                            $title = '';

                            if ($category) {
                                $title = 'title="' . esc_attr($category->name) . '"';
                            } 

                            $return = sprintf(
                                '%s<a href="%s" %s>%s',
                                $matches[1],
                                esc_url(get_category_link($category_id)),
                                $title,
                                $matches[4]
                            );

                            return $return;

                            return $matches[0]; // Return the original HTML if no image exists
                        },
                        $output
                    );
                }

                if (isset($args['show_count']) && $args['show_count']) {
                    $output = preg_replace_callback(
                        '/(<li.*?class="([^"]*cat-item[^"]*).*?"[^>]*>)(.*?)/s',
                        function ($matches) {
                            // Append 'count-active' to the class attribute
                            $updated_class = $matches[2] . ' count-active';
                            return str_replace($matches[2], $updated_class, $matches[1]) . $matches[3];
                        },
                        $output
                    );
                }

            }

            return $output;
        }

        protected function is_supported( WP_Widget $widget ) {
            return in_array( $widget->id_base, $this->widgets, true );
        }
    }

    class Et_Widget_WC_Widget_Layered_Nav {

        protected $defaults = array(
            'disp_type'         => 'list',
            // store as array of slugs
            'category'          => array(),
        );

        protected $widgets = array(
            'woocommerce_layered_nav',
        );

        public static $current_display_type = '';
        /** @var string[] slugs */
        public static $current_category     = array();

        function __construct() {
            add_action( 'in_widget_form', array( $this, 'add_settings' ), 10, 3 );
            add_filter( 'widget_update_callback', array( $this, 'save_settings' ), 10, 4 );
            add_filter( 'widget_display_callback', array( $this, 'frontend_settings' ), 10, 3 );
            add_filter( 'woocommerce_layered_nav_term_html', array( $this, 'layered_nav_term_html' ), 10, 4 );
        }

        public function add_settings( $widget, $return, $instance ) {
            if ( ! $this->is_supported( $widget ) ) {
                return null;
            }

            $instance   = wp_parse_args( (array) $instance, $this->defaults );
            $categories = function_exists('get_product_categories_hierarchy')
                ? get_product_categories_hierarchy(false)
                : array();

            // Ensure we always work with an array of slugs for selected categories
            $selected_categories = array_map( 'sanitize_title', (array) ( $instance['category'] ?? array() ) );
            ?>
            <p>
                <label for="<?php echo esc_attr($widget->get_field_id('disp_type')); ?>">
                    <?php _e('Display Type:', 'enovathemes-addons'); ?>
                </label>
                <select class="widefat"
                        id="<?php echo esc_attr($widget->get_field_id('disp_type')); ?>"
                        name="<?php echo esc_attr($widget->get_field_name('disp_type')); ?>">
                    <option value="list" <?php selected(($instance['disp_type'] ?? ''), 'list'); ?>>
                        <?php _e('List', 'enovathemes-addons'); ?>
                    </option>
                    <option value="image_list" <?php selected(($instance['disp_type'] ?? ''), 'image_list'); ?>>
                        <?php _e('Image List', 'enovathemes-addons'); ?>
                    </option>
                    <option value="color" <?php selected(($instance['disp_type'] ?? ''), 'color'); ?>>
                        <?php _e('Color', 'enovathemes-addons'); ?>
                    </option>
                    <option value="dropdown" <?php selected(($instance['disp_type'] ?? ''), 'dropdown'); ?>>
                        <?php _e('Dropdown', 'enovathemes-addons'); ?>
                    </option>
                </select>
            </p>

            <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                <p>
                    <label for="<?php echo esc_attr($widget->get_field_id('category')); ?>">
                        <?php _e('Limit to category:', 'enovathemes-addons'); ?>
                    </label>
                    <!-- IMPORTANT: [] so multiple selections save -->
                    <select class="widefat"
                            id="<?php echo esc_attr($widget->get_field_id('category')); ?>"
                            name="<?php echo esc_attr($widget->get_field_name('category')); ?>[]"
                            multiple>
                        <option value=""><?php _e('Choose', 'enovathemes-addons'); ?></option>
                        <?php
                            // The helper now accepts $selected slugs
                            echo et__list_taxonomy_hierarchy_no_instance( $categories, '', $selected_categories );
                        ?>
                    </select>
                    <small><?php _e('Hold Ctrl/Cmd to select multiple categories.', 'enovathemes-addons'); ?></small>
                </p>

            <?php endif;
        }

        public function save_settings( $instance, $new_instance, $old_instance, $widget ) {
            if ( ! $this->is_supported( $widget ) ) {
                return $instance;
            }

            $instance = wp_parse_args( (array) $instance, $this->defaults );

            // Display type
            if ( isset( $new_instance['disp_type'] ) ) {
                $disp_type               = sanitize_text_field( $new_instance['disp_type'] );
                $instance['disp_type']   = $disp_type;
                $instance['display_type'] = ( $disp_type === 'dropdown' ) ? 'dropdown' : 'list';
            }

            // Category limit (array of slugs)
            if ( isset( $new_instance['category'] ) ) {
                $slug_list = (array) $new_instance['category'];
                // sanitize, dedupe, drop empties
                $slug_list = array_filter( array_map( 'sanitize_title', $slug_list ) );
                $slug_list = array_values( array_unique( $slug_list ) );
                $instance['category'] = $slug_list;
            } else {
                $instance['category'] = array();
            }

            return $instance;
        }

        public function frontend_settings( $instance, $widget, $args ) {
            if ( ! $this->is_supported( $widget ) ) {
                return $instance;
            }

            $instance = wp_parse_args( (array) $instance, $this->defaults );

            self::$current_display_type = isset( $instance['disp_type'] ) ? $instance['disp_type'] : 'list';
            // Always slugs
            self::$current_category     = array_map( 'sanitize_title', (array) ( $instance['category'] ?? array() ) );

            return $instance;
        }

        public function layered_nav_term_html( $term_html, $term, $link, $count ) {
            $display_type = self::$current_display_type;

            // Always include term slug + title
            $term_html = preg_replace(
                '/(<a[^>]*)(>)/',
                '$1 data-term="' . esc_attr( $term->slug ) . '" title="' . esc_attr( $term->name ) . '"$2',
                $term_html
            );

            // Expose category limit (slugs) and include-children toggle
            if ( ! empty( self::$current_category ) ) {
                $data_val = implode( ',', array_map( 'sanitize_title', self::$current_category ) );
                if (!empty($data_val)) {
                    $term_html = preg_replace(
                        '/(<a[^>]*)(>)/',
                        '$1 data-category-limit="' . esc_attr( $data_val ) . '"$2',
                        $term_html
                    );
                }
            }

            // Display-specific decorations
            if ( isset( $display_type ) ) {
                if ( in_array( $display_type, array( 'image_list', 'image' ), true ) ) {
                    $term_image_url = get_term_meta( $term->term_id, 'enova_' . $term->taxonomy . '_image', true );

                    if ( $term_image_url ) {
                        $image_html = '<span class="term-image-wrapper"><img src="' . esc_url( $term_image_url ) . '" alt="' . esc_attr( $term->name ) . '" title="' . esc_attr( $term->name ) . '" class="term-image"></span>';

                        $term_html = preg_replace_callback(
                            '/(<a[^>]*)(>)(.*?)(<\/a>)/',
                            function ( $matches ) use ( $image_html ) {
                                $new_content = $image_html . $matches[3];
                                return $matches[1] . $matches[2] . $new_content . $matches[4];
                            },
                            $term_html
                        );
                    }
                } elseif ( $display_type === 'color' ) {
                    $attribute_id = wc_attribute_taxonomy_id_by_name( str_replace( 'pa_', '', $term->taxonomy ) );
                    $term_color   = get_term_meta( $term->term_id, 'enova_' . $term->taxonomy . '_color', true );

                    $class      = array( 'term-color' );
                    $color_html = '';

                    if ( $term_color ) {
                        if ( function_exists('et__is_light_color') && et__is_light_color( $term_color ) ) {
                            $class[] = 'light';
                        }
                        $color_html = '<span style="background-color:' . esc_attr( $term_color ) . '" class="' . esc_attr( implode( ' ', $class ) ) . '"></span>';
                    } elseif ( get_option( 'attribute_display_type_' . $attribute_id, 'select' ) === 'color' ) {
                        $class[]   = 'empty';
                        $color_html = '<span class="' . esc_attr( implode( ' ', $class ) ) . '"></span>';
                    }

                    // prepend the color swatch inside <a>
                    $term_html = preg_replace( '/(<a[^>]*>)/', '$1' . $color_html, $term_html );
                }
            }

            return $term_html;
        }

        protected function is_supported( WP_Widget $widget ) {
            return in_array( $widget->id_base, $this->widgets, true );
        }
    }


    new Et_Widget_WC_Widget_Product_Categories();
    new Et_Widget_WC_Widget_Layered_Nav();

    add_filter('dynamic_sidebar_params', 'et__add_data_attribute_to_wc_filter_widgets');
    function et__add_data_attribute_to_wc_filter_widgets($params) {

        // Check if the widget is a WooCommerce layered nav widget
        if (strpos($params[0]['widget_id'], 'woocommerce_layered_nav-') === 0) {
            // Get all WooCommerce layered nav widget settings
            $widget_settings = get_option('widget_woocommerce_layered_nav');

            // Extract the widget number from the widget ID
            $widget_id_base = str_replace('woocommerce_layered_nav-', '', $params[0]['widget_id']);
            
            // Get the attribute from the widget settings
            if (isset($widget_settings[$widget_id_base]['attribute'])) {

                $attribute_slug = sanitize_title($widget_settings[$widget_id_base]['attribute']);

                $taxonomy = get_taxonomy( 'pa_'.$attribute_slug );

                $replace_html = '<div data-attribute="' . esc_attr($attribute_slug) . '"';

                if ($taxonomy) {
                    $replace_html .= ' data-attribute-label="' . esc_attr($taxonomy->labels->singular_name) . '"';
                }

                if ($widget_settings[$widget_id_base]['disp_type']) {
                    $replace_html .= ' data-display-type="' . esc_attr($widget_settings[$widget_id_base]['disp_type']) . '"';
                }

                if ($widget_settings[$widget_id_base]['query_type']) {
                    $replace_html .= ' data-query-type="' . esc_attr($widget_settings[$widget_id_base]['query_type']) . '"';
                }

            }

            if (isset($widget_settings[$widget_id_base]['category']) && !empty($widget_settings[$widget_id_base]['category'])) {
                $replace_html .= ' data-category-limit="' . htmlspecialchars(implode(',',$widget_settings[$widget_id_base]['category'])) . '"';
            }

            // Add the data-attribute to the widget's wrapper div
            $params[0]['before_widget'] = str_replace(
                '<div',
                $replace_html,
                $params[0]['before_widget']
            );

        } elseif(strpos($params[0]['widget_id'], 'woocommerce_product_categories-') === 0){
            // Get all WooCommerce layered nav widget settings
            $widget_settings = get_option('widget_woocommerce_product_categories');
            
            // Extract the widget number from the widget ID
            $widget_id_base = str_replace('woocommerce_product_categories-', '', $params[0]['widget_id']);

            if (!empty($widget_settings[$widget_id_base]) && is_array($widget_settings[$widget_id_base])) {
                foreach ($widget_settings[$widget_id_base] as $key => $value) {
                    if (isset($value) && !empty($value)) {
                        // Generate the data attribute key by normalizing it
                        $normalized_key = str_replace(['[', ']', '_'], ['', '', '-'], $key);
                        $data_attributes[] = sprintf('data-%s="%s"', $normalized_key, esc_attr($value));
                    }
                }
            }

            if (!empty($data_attributes)) {
                $replace_html = '<div '.implode(' ',$data_attributes);
                // Add the data-attribute to the widget's wrapper div
                $params[0]['before_widget'] = str_replace(
                    '<div',
                    $replace_html,
                    $params[0]['before_widget']
                );
            }

        }

        return $params;
    }

    add_action('woocommerce_product_query', 'et__constrain_products_by_vehicle', 9);
    function et__constrain_products_by_vehicle( $q ) {
        // Build vehicle attributes from VIN or cookies
        $vehicle_attributes = [];

        if ( isset($_GET['vin']) && !empty($_GET['vin']) ) {
            $vehicle_attributes = enovathemes_addons_vin_decoder( sanitize_text_field($_GET['vin']) );
        } else {
            $vehicle_attributes = vehicle_set_from_cookies_if_empty( $vehicle_attributes );
        }

        // Get matching vehicle term IDs
        $vehicles = vehicle_filter_component( $vehicle_attributes );

        if ( empty($vehicles) ) {
            return; // no constraint
        }

        // Ensure integer term IDs
        $vehicles = array_values( array_filter( array_map('intval', (array) $vehicles ) ) );
        if ( empty($vehicles) ) {
            return;
        }

        // Merge into existing tax_query with AND relation
        $tax_query = (array) $q->get('tax_query');

        // Make sure relation is AND to intersect with other filters
        if ( empty($tax_query) || !isset($tax_query['relation']) ) {
            $tax_query['relation'] = 'AND';
        }

        $tax_query[] = array(
            'taxonomy' => 'vehicles',
            'field'    => 'term_id',
            'terms'    => $vehicles,
            'operator' => 'IN',
            'include_children' => false, // change to true if you want descendants included
        );

        $q->set('tax_query', $tax_query);

        // If post__in was already set (e.g., from another feature), keep intersection semantics
        $existing_in = (array) $q->get('post__in');
        if ( !empty($existing_in) ) {
            // Let Woo do the intersect implicitly via tax_query; no need to mutate post__in here.
            // If you MUST intersect with post__in, uncomment the next lines and compute IDs.
            // $ids = et__get_ids_matching_vehicle_terms($vehicles); // implement if needed
            // $q->set('post__in', array_values(array_intersect($existing_in, $ids)));
        }
    }

    add_filter('woocommerce_product_query_tax_query', function($tax_query, $query) {
        // Mirror the same logic as above so any internal counts also see the vehicles constraint
        $vehicle_attributes = [];

        if ( isset($_GET['vin']) && !empty($_GET['vin']) ) {
            $vehicle_attributes = enovathemes_addons_vin_decoder( sanitize_text_field($_GET['vin']) );
        } else {
            $vehicle_attributes = vehicle_set_from_cookies_if_empty( $vehicle_attributes );
        }

        $vehicles = vehicle_filter_component( $vehicle_attributes );
        $vehicles = array_values( array_filter( array_map('intval', (array) $vehicles ) ) );

        if ( empty($vehicles) ) {
            return $tax_query;
        }

        if ( empty($tax_query) || !isset($tax_query['relation']) ) {
            $tax_query['relation'] = 'AND';
        }

        $tax_query[] = array(
            'taxonomy' => 'vehicles',
            'field'    => 'term_id',
            'terms'    => $vehicles,
            'operator' => 'IN',
            'include_children' => false,
        );

        return $tax_query;
    }, 10, 2);

    add_filter('woocommerce_price_filter_sql', 'et__price_filter_with_vehicle_terms', 10, 3);
    function et__price_filter_with_vehicle_terms( $sql, $meta_query, $tax_query ) {
        global $wpdb;

        // Build current vehicle TERM IDs (term_id)
        $vehicle_attributes = [];

        if ( isset($_GET['vin']) && !empty($_GET['vin']) ) {
            $vehicle_attributes = enovathemes_addons_vin_decoder( sanitize_text_field($_GET['vin']) );
        } else {
            $vehicle_attributes = vehicle_set_from_cookies_if_empty( $vehicle_attributes );
        }
        $vehicle_term_ids = array_values( array_filter( array_map('intval', (array) vehicle_filter_component($vehicle_attributes) ) ) );
        if ( empty($vehicle_term_ids) ) return $sql;

        // Map term_id -> term_taxonomy_id
        $taxonomy     = 'vehicles';
        $placeholders = implode(',', array_fill(0, count($vehicle_term_ids), '%d'));
        $tt_sql = $wpdb->prepare(
            "SELECT term_taxonomy_id
             FROM {$wpdb->term_taxonomy}
             WHERE taxonomy = %s
               AND term_id IN ($placeholders)",
            array_merge([ $taxonomy ], $vehicle_term_ids)
        );
        $tt_ids = array_map('intval', (array) $wpdb->get_col($tt_sql));
        if ( empty($tt_ids) ) return $sql;

        $tt_in = implode(',', $tt_ids);

        // Our EXISTS clause
        $exists_sql = "EXISTS (
            SELECT 1
            FROM {$wpdb->term_relationships} tr
            WHERE tr.object_id = {$wpdb->wc_product_meta_lookup}.product_id
              AND tr.term_taxonomy_id IN ($tt_in)
        )";

        // $sql can be string or array. In your case it's a string.
        if ( is_string($sql) ) {
            // If there's already a WHERE (with any whitespace/newlines), append AND ...; otherwise add WHERE ...
            if ( preg_match('/\bWHERE\b/i', $sql) ) {
                $sql .= " AND $exists_sql";
            } else {
                $sql .= " WHERE $exists_sql";
            }
            // Optional: error_log($sql);
            return $sql;
        }

        if ( is_array($sql) ) {
            $sql['where'] = isset($sql['where']) ? $sql['where'] : '';
            $sql['where'] .= (trim($sql['where']) === '' ? " AND " : " AND ") . $exists_sql;
            return $sql;
        }

        return $sql;
    }



/* Product filter
---------------*/

    function et__product_filter(){

        $base_url = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
        if ('' === get_option( 'permalink_structure' )) {
            $base_url = get_home_url().'?post_type=product';
        }

        $active_filters = isset($_POST['active_filters']) ? json_decode(stripslashes($_POST['active_filters']),true) : false;

        $mods             = et_get_theme_mods();
        $product_per_page = $mods && isset($mods['product_number']) ? $mods['product_number'] : get_option( 'posts_per_page' );
        $product_ajax_search_threshold = $mods && isset($mods['product_ajax_search_threshold']) && !empty($mods['product_ajax_search_threshold']) ? $mods['product_ajax_search_threshold'] : 0.1;

        if (!isset($active_filters['orderby'])) {
            $active_filters['orderby'] = get_option( 'woocommerce_default_catalog_orderby' ); // changed from menu_order
        }

        /* Variables
        -------*/

            $price_filter          = '';
            $product_terms         = '';
            $url_params            = '';
            $rating_filter         = '';
            $current_results_title = '';
            $categories_carousel   = et__product_categories_carousel();
            $raquo                 = '<span class="arrow"></span>';
            $homepage_id           = get_option('page_on_front');
            $breadcrumbs           = '<a href="' . esc_url(home_url()) . '">' . (empty($homepage_id) ? get_bloginfo('name') : get_the_title($homepage_id)) . '</a> '.$raquo.' ';
            $found_posts           = 0;
            $is_wpml               = defined('ICL_SITEPRESS_VERSION');
            $is_polylang           = function_exists('pll_current_language');
            $vehicle_params        = apply_filters( 'vehicle_params','');

            $exclude_from_taxonomies_args = [];
            $tax_query  = [];
            $meta_query = [];
            $vehicle_attributes = [];

            $return_products = '';

        /* Fuse
        -------*/

            $fuse_active = false;

            if ($active_filters && $active_filters['s']) {
                if (class_exists('Fuse')) {
                    $fuse_active = true;
                } else {
                    $Fuse = WP_PLUGIN_DIR . '/enovathemes-addons/includes/vendor/autoload.php';
                    if (file_exists($Fuse)) {
                        require_once $Fuse;
                        $fuse_active = true;
                    }
                }
            }

        /* Breadcrumbs
        -------*/

            $shop_page_id = wc_get_page_id('shop');

            // Check if the Shop page is set
            if ( $shop_page_id ) {
                $shop_page_title = get_the_title( $shop_page_id );
            } else {
                $shop_page_title = esc_html__("Shop","enovathemes-addons"); // Default title if no Shop page is set
            }

            $current_results_title = $shop_page_title;

            $breadcrumbs .= ($active_filters && isset($active_filters['category']) && !empty($active_filters['category'])) ? 
            '<a href="' . esc_url(get_post_type_archive_link('product')) . '">' . $shop_page_title . '</a> '.$raquo.' ' :
            '<span>' . $shop_page_title . '</span>';

        /* Pagination 
        -------*/

            $paged = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;

            if (isset($_POST['base_url']) && !empty($_POST['base_url'])) {
                $base_url = $_POST['base_url'];
            }

            $base_url = preg_replace('#page/\d+/?#', '', $base_url);
            $base_url = esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', $base_url ) ) );

            $pagination_args = [
                'base'      => $base_url . '%_%',
                'format'    => 'page/%#%/',
                'current'   => $paged,
                'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
                'next_text' => is_rtl() ? '&larr;' : '&rarr;',
                'type'      => 'list', // Outputs <ul> for better styling
                'end_size'  => 3,
                'mid_size'  => 3,
            ];

            if (isset($_POST['url_params']) && !empty($_POST['url_params'])) {
                $pagination_args['add_fragment'] = isset($_POST['url_params']) 
                ? '?' . ltrim($_POST['url_params'], '?') 
                : '';

                $url_params = explode('&', $_POST['url_params']);

                $exclude_from_taxonomies_args = array_filter(array_map(function($param) {
                    $parts = explode('=', $param);
                    if (str_contains($parts[0], 'query_type_') && $parts[1] === 'or') 
                    {
                        return str_replace('query_type_', 'pa_', $parts[0]); 
                    }
                }, $url_params));

            }

        /* Query
        -------*/

            $args = array(
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page'      => $product_per_page,
                'paged'               => $paged,
                'fields'              => 'ids'
            );

            if ($is_wpml) {
                $current_language = isset($_POST['lang']) && !empty($_POST['lang']) ? $_POST['lang'] :  apply_filters('wpml_current_language', null);
                $args['suppress_filters'] = false;
            } elseif($is_polylang){
                $current_language = isset($_POST['lang']) && !empty($_POST['lang']) ? $_POST['lang'] :  pll_current_language();
                $args['lang'] = $current_language;
            }

            $tax_query[] = [
                'taxonomy'  => 'product_visibility',
                'terms'     => array( 'exclude-from-catalog' ),
                'field'     => 'name',
                'operator'  => 'NOT IN',
            ];

            if ('yes' == get_option( 'woocommerce_hide_out_of_stock_items' )) {
                $meta_query[] = [
                    'key' => '_stock_status', // Meta key for stock status
                    'value' => 'instock', // Only in-stock products
                    'compare' => '=', // Ensure the value is 'instock'
                ];
            }

            if ($active_filters) {

                if (isset($active_filters['category']) && !empty($active_filters['category'])) {

                    $categories_carousel = et__product_categories_carousel($active_filters['category']);

                    $active_category = get_term_by('slug',$active_filters['category'],'product_cat');
                    if ($active_category) {
                        $current_results_title = $active_category->name;

                        $term_links = [];
                        $current_category = $active_category;

                        while ($current_category->parent) {
                            $parent_category = get_term($current_category->parent, 'product_cat');

                            if (!is_wp_error($parent_category) && $parent_category) {
                                array_unshift($term_links, '<a href="' . esc_url(get_term_link($parent_category)) . '">' . esc_html($parent_category->name) . '</a>');
                            }

                            $current_category = $parent_category; // Move up the hierarchy
                        }

                        $term_links[] = ' <span>' . esc_html($active_category->name) . '</span>';

                        $breadcrumbs .= implode(' ' . $raquo . ' ', $term_links);
                    }

                }

                foreach ($active_filters as $filter => $value) {

                    if (in_array($filter, $vehicle_params)) {
                        $vehicle_attributes[$filter] = $value;
                    } elseif (
                        !in_array($filter, ['min_price','max_price','paged']) && 
                        strpos($filter, 'query_type_') !== 0 &&
                        !in_array($filter, $vehicle_params) &&
                        $filter != "vin" && $filter != "yr"
                    ) {
                        
                        switch ($filter) {
                            
                            case 'category':

                                $tax_query[] = [
                                    'taxonomy'  => 'product_cat',
                                    'terms'     => $active_filters['category'],
                                    'field'     => 'slug',
                                    'operator'  => 'IN',
                                ];

                            break;
                            
                            case 'orderby':

                                switch ($value) {
                                    case 'menu_order':
                                        $orderby  = 'menu_order title';
                                        $order    = 'ASC';
                                        $meta_key = '';
                                        break;
                                    case 'popular':
                                        $orderby  = 'meta_value_num date title';
                                        $order    = 'DESC';
                                        $meta_key = 'total_sales';
                                        break;
                                    case 'reviews':
                                        $orderby  = 'meta_value_num date title';
                                        $order    = 'DESC';
                                        $meta_key = '_wc_average_rating';
                                        break;
                                    case 'latest':
                                        $orderby  = 'date title';
                                        $order    = 'DESC';
                                        $meta_key = '';
                                        break;
                                    case 'price':
                                        $orderby  = 'meta_value_num';
                                        $order    = 'ASC';
                                        $meta_key = '_price';
                                        break;
                                    case 'price-desc':
                                        $orderby  = 'meta_value_num';
                                        $order    = 'DESC';
                                        $meta_key = '_price';
                                        break;
                                    default:
                                        $orderby  = 'date menu_order title';
                                        $order    = 'DESC';
                                        $meta_key = '';
                                        break;
                                }

                                $args['orderby'] = $orderby;
                                $args['order']   = $order;

                                if (!empty($meta_key)) {
                                    $args['meta_key'] = $meta_key;
                                }

                            break;

                            case 'rating_filter':
                                
                                $rating_value = absint($value);
                                $meta_query[] = [
                                    [
                                        'key'     => '_wc_average_rating',
                                        'value'   => $rating_value - 0.5,
                                        'compare' => '>=',
                                    ],
                                    [
                                        'key'     => '_wc_average_rating',
                                        'value'   => $rating_value + 0.5,
                                        'compare' => '<=',
                                    ],
                                ];

                            break;

                            case 's':

                                $product_index = get_transient('et-woo-product-index');
                                
                                if ($product_index) {

                                    $product_index = isset($_POST['lang']) && isset($product_index[$_POST['lang']]) ? $product_index[$_POST['lang']] : $product_index['default'];

                                    if ($fuse_active) {

                                        $fuse_keys = et__get_search_in_keys($product_index);

                                        $options = [
                                            'keys' => $fuse_keys,
                                            'threshold' => floatval($product_ajax_search_threshold),
                                            'includeScore' => false,
                                            'ignoreLocation' => true,
                                            'useExtendedSearch' => true
                                        ];

                                        $fuse = new \Fuse\Fuse($product_index, $options);
                                        $product_IDs = $fuse->search(urldecode($value));
                                        if (!empty($product_IDs) && is_array($product_IDs)) {

                                            $product_IDs = array_filter(array_map(function($product) {
                                                return $product['item']['id'];
                                            }, $product_IDs));

                                            $product_IDs = array_unique($product_IDs,SORT_REGULAR);

                                            $args['post__in'] = $product_IDs;

                                        } else {
                                            $args['s'] = urldecode($value);
                                        }

                                    } else {
                                        $product_IDs = et__search_similar_products($product_index, urldecode($value), $threshold = 80);
                                        if (!empty($product_IDs) && is_array($product_IDs)) {
                                            $args['post__in'] = $product_IDs;
                                        } else {
                                            $args['s'] = urldecode($value);
                                        }
                                    }
                                    
                                } else {
                                    $args['s'] = urldecode($value);
                                }

                            break;

                            case 'sale':
                                
                                $meta_query[] = [
                                    [
                                        'key'     => '_sale_price',
                                        'value'   => 0,
                                        'compare' => '>',
                                        'type'    => 'NUMERIC',
                                    ]
                                ];

                            break;
                                
                            default:

                                if (strpos($filter, 'filter_') != -1) {
                                    $tax_query[] = [
                                        'taxonomy'  => str_replace('filter_', 'pa_', $filter),
                                        'terms'     => explode(',', $value),
                                        'field'     => 'slug',
                                        'operator'  => 'IN',
                                    ];
                                }

                            break;
                        }

                    }
                }

                if (
                    isset($active_filters['min_price']) && 
                    isset($active_filters['max_price'])
                ) {
                    $meta_query[] = [
                        'key'     => '_price',
                        'value'   => [(float) $active_filters['min_price'], (float) $active_filters['max_price']],
                        'compare' => 'BETWEEN',
                        'type'    => 'DECIMAL',
                    ];

                }

                /* Vehicle filter 
                ----------*/

                    if (isset($active_filters['vin']) && !empty($active_filters['vin'])) {
                        $vehicle_attributes = enovathemes_addons_vin_decoder($active_filters['vin']);
                        $vehicle_data       = enovathemes_addons_vin_decoder($active_filters['vin'],true);

                        if ($vehicle_attributes == false || empty($vehicle_attributes) || (isset($vehicle_attributes['error']) && $vehicle_attributes['error'])) {
                            $args['post__in'] = array(0);
                            $return_products = '<div class="vin-error">'.$vehicle_attributes['error'].'</div>';
                        }

                    } else {
                        $vehicle_attributes = vehicle_set_from_cookies_if_empty($vehicle_attributes);
                    }

                    $vehicles = vehicle_filter_component($vehicle_attributes);

                    if ($vehicles && !empty($vehicles)) {

                        $tax_query[] = array(
                            "taxonomy" => "vehicles",
                            "field" => "term_id",
                            "terms" => $vehicles,
                            "operator" => "IN",
                        );

                    }

                    if (isset($active_filters['vin']) && !empty($active_filters['vin']) && ($vehicles == false || empty($vehicles))) {
                        $args['post__in']   = array(0);
                    }

                if (!empty($tax_query)) {
                    if (count($tax_query) > 1) {
                        $tax_query['relation'] = 'AND';
                    }
                    $args['tax_query'] = $tax_query;
                }

                if (!empty($meta_query)) {
                    if (count($meta_query) > 1) {
                        $meta_query['relation'] = 'AND';
                    }
                    $args['meta_query'] = $meta_query;
                }

            }

            $return = [
                'args'          => $args,
                'products'      => $return_products,
                'not_found'     => '',
                'pagination'    => '',
                'found_results' => ''
            ];

            $include_universal_in_search  = (get_theme_mod('include_universal_in_search') != null && !empty(get_theme_mod('include_universal_in_search'))) ? "true" : "false";

            if (
                $include_universal_in_search == "true" && 
                $vehicles && !empty($vehicles) &&
                !isset($active_filters['category'])
            ) {

                $universal_products = enovathemes_addons_universal_products();

                if (!is_wp_error($universal_products)) {
                    $args['posts_per_page'] = -1;
                    unset($args['paged']);

                    $product_ids = get_posts( $args );
                    $product_ids = array_values( array_unique( array_merge( $product_ids, $universal_products ) ) );

                    if (empty( $product_ids )) {
                        ob_start(); // Start output buffering

                        do_action('woocommerce_no_products_found');

                        $return['not_found'] = ob_get_clean();
                    } else {
                        $args = array(
                            'post_type'           => 'product',
                            'post_status'         => 'publish',
                            'ignore_sticky_posts' => 1,
                            'posts_per_page'      => $product_per_page,
                            'paged'               => $paged,
                            'fields'              => 'ids',
                            'post__in'            => $product_ids
                        );
                    }

                }

            }

            add_filter('posts_search', 'et__woocommerce_product_search_in_query', 10, 2);
            $WP_Query = new WP_Query($args);
            remove_filter('posts_search', 'et__woocommerce_product_search_in_query', 10);

            if($WP_Query->have_posts()){

                $current_rate = (isset($_POST['currency']) && !empty($_POST['currency'])) ? et__get_currency_rate($_POST['currency']) : 1;
                $args_pass = $args;

                if (!empty($exclude_from_taxonomies_args) && isset($args_pass['tax_query'])) {
                    $args_pass['tax_query'] = array_filter($args_pass['tax_query'], function($taxonomy) use ($exclude_from_taxonomies_args) {
                        // If $taxonomy is a string, return it as is
                        if (is_string($taxonomy)) {
                            return true; // Keep it in the array
                        }
                        // If $taxonomy is an array, check the 'taxonomy' key
                        return is_array($taxonomy) && !in_array($taxonomy['taxonomy'], $exclude_from_taxonomies_args);
                    });
                }

                $product_terms = et__get_product_terms($args_pass);
                $price_filter  = et__render_price_filter($args);
                $rating_filter = et__render_rating_filter($args,$base_url,$url_params);

                ob_start();
                while ($WP_Query->have_posts() ) {
                $WP_Query->the_post();

                    if (isset($_POST['currency']) && !empty($_POST['currency'])) {

                        $base_price      = get_post_meta(get_the_ID(), '_price', true);
                        $converted_price = $base_price * $current_rate;
                        $current_currency = sanitize_text_field($_POST['currency']);

                        add_filter('woocommerce_get_price_html', function($price, $product) use ($converted_price, $current_currency) {
                            return wc_price($converted_price, ['currency' => $current_currency]);
                        }, 10, 2);

                    }

                    wc_get_template_part('content', 'product'); // Load the product template
                }
                $return['products'] = ob_get_clean();

                $pagination_args['total'] = $WP_Query->max_num_pages;

                $found_posts = $WP_Query->found_posts;

                $return['pagination'] = paginate_links(
                    apply_filters(
                        'woocommerce_pagination_args',
                        $pagination_args
                    )
                );

            } else {

                ob_start(); // Start output buffering

                do_action('woocommerce_no_products_found');

                $return['not_found'] = ob_get_clean();

            }

        /*Found results
        -------*/

            if ($found_posts) {

                $return['found_total'] = $found_posts;

                // phpcs:disable WordPress.Security
                if ( 1 === intval( $found_posts ) ) {
                    $return['found_results'] = esc_html__( 'Showing the single result', 'enovathemes-addons' );
                } elseif ( $found_posts <= $product_per_page || -1 === $product_per_page ) {
                    /* translators: %d: total results */
                    $return['found_results'] = sprintf( _n( 'Showing all %d result', 'Showing all %d results', $found_posts, 'enovathemes-addons' ), $found_posts );
                } else {

                    $first = ( $product_per_page * $paged ) - $product_per_page + 1;
                    $last  = min( $found_posts, $product_per_page * $paged );

                    $return['found_results'] = sprintf( _nx( 'Showing %1$d&ndash;%2$d of %3$d result', 'Showing %1$d&ndash;%2$d of %3$d results', $found_posts, 'with first and last result', 'enovathemes-addons' ), $first, $last, $found_posts );

                }

            }


        wp_reset_postdata();

        if ($price_filter && !empty($price_filter)) {
            $return['price_filter'] = $price_filter;
        }

        if ($rating_filter && !empty($rating_filter)) {
            $return['rating_filter'] = $rating_filter;
        }

        if ($product_terms && !empty($product_terms)) {
            $return['product_terms'] = $product_terms;
        }

        if ($current_results_title && !empty($current_results_title)) {
            $return['current_results_title'] = html_entity_decode($current_results_title);
        }

        if ($breadcrumbs && !empty($breadcrumbs)) {
            $return['breadcrumbs'] = $breadcrumbs;
        }

        if (!empty($categories_carousel)) {
            $return['categories_carousel'] = $categories_carousel;
        }

        $return['dev'] = $vehicle_attributes;

        echo json_encode($return);

        wp_die();
    }
    add_action('wp_ajax_et__product_filter', 'et__product_filter');
    add_action('wp_ajax_nopriv_et__product_filter', 'et__product_filter');

    function et__term_info($is_wpml,$is_polylang,$lang){

        global $wpdb;
        
        $attributes = wc_get_attribute_taxonomies();
        $term_info = [];

        foreach ($attributes as $attribute) {
            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

            // Skip non-existing taxonomies
            if (!taxonomy_exists($taxonomy)) continue;

            // Query the terms using wpdb for faster performance, filtering by language
            $query = "
                SELECT t.term_id, t.name, t.slug, tt.count
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            ";

            $params = [$taxonomy]; // Array to store parameters for prepare()

            // Add language condition based on the current language
            if ($is_wpml) {
                // WPML language filter
                $query .= "
                    INNER JOIN {$wpdb->prefix}icl_translations it ON t.term_id = it.element_id
                    WHERE tt.taxonomy = %s AND it.language_code = %s
                ";
                $params[] = $lang;
            } elseif ($is_polylang) {
                // Polylang language filter
                $query .= "
                    INNER JOIN {$wpdb->prefix}pll_term_taxonomy tt_lang ON tt.term_taxonomy_id = tt_lang.term_taxonomy_id
                    WHERE tt.taxonomy = %s AND tt_lang.language = %s
                ";
                $params[] = $lang;
            } else {
                // Default query when no language plugin is active
                $query .= " WHERE tt.taxonomy = %s";
            }

            // Execute query safely with all parameters
            $results = $wpdb->get_results($wpdb->prepare($query, ...$params));


            if ($results) {

                usort($results, function ($a, $b) {
                    return strcmp($a->name, $b->name);
                });

                foreach ($results as $term) {
                    
                    $term_data = [
                        'slug'  => $term->slug,
                        'name'  => $term->name,
                        'count' => $term->count,
                    ];

                    // Retrieve term meta (image and color)
                    $term_image_url = get_term_meta($term->term_id, 'enova_' . $taxonomy . '_image', true);
                    $term_color = get_term_meta($term->term_id, 'enova_' . $taxonomy . '_color', true);

                    if ($term_image_url) {
                        $term_data['img'] = esc_url($term_image_url);
                    }

                    if ($term_color) {
                        $term_data[et__is_light_color($term_color) ? 'color-light' : 'color'] = $term_color;
                    }

                    // Store data
                    $term_info[$taxonomy][] = $term_data;
                }
            }
        }

        return $term_info;
    }

    function et__fetch_products_data(){

        $is_wpml = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_current_language');

        $lang = isset($_POST['lang']) && !empty($_POST['lang']) ? $_POST['lang'] : '';

        if ($is_wpml) {
            $lang = !empty($lang) ? $lang : apply_filters('wpml_current_language', null);
        } elseif($is_polylang){
            $lang = !empty($lang) ? $lang : pll_current_language();
        } elseif(empty($lang)){
            $lang = substr(get_locale(), 0, 2);
        }

        $output = [];

        $atts_cache_key = 'et__wc_attributes_terms_'.$lang;
        $term_info = get_transient($atts_cache_key);

        $price_cache_key = 'et__wc_price_filter_'.$lang;
        $price_filter = get_transient($price_cache_key);

        $rating_cache_key = 'et__wc_rating_filter_'.$lang;
        $rating_filter = get_transient($rating_cache_key);

        if ($term_info != false) {
            $output['attributes_terms'] = $term_info;
        } else {
            $term_info = et__term_info($is_wpml,$is_polylang,$lang);
            if ($term_info && !empty($term_info)) {
                $output['attributes_terms'] = $term_info;
            }
        }

        if ($price_filter != false) {
            $output['price_filter'] = $price_filter;
        } else {

            $args = array(
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_visibility',
                        'terms'    => array('exclude-from-catalog'),
                        'field'    => 'name',
                        'operator' => 'NOT IN',
                    ),
                ),
            );

            $price_filter = et__render_price_filter($args);

            if ($price_filter && !empty($price_filter)) {
                $output['price_filter'] = $price_filter;
            }
        }

        if ($rating_filter != false) {
            $output['rating_filter'] = $rating_filter;
        } else {

            $base_url = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
            if ('' === get_option( 'permalink_structure' )) {
                $base_url = get_home_url().'?post_type=product';
            }

            $args = array(
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_visibility',
                        'terms'    => array('exclude-from-catalog'),
                        'field'    => 'name',
                        'operator' => 'NOT IN',
                    ),
                ),
                'lang' => $lang
            );

            $rating_filter = et__render_rating_filter($args,$base_url,false);

            if ($rating_filter && !empty($rating_filter)) {
                $output['rating_filter'] = $rating_filter;
            }
        }

        // Cache results
        set_transient($atts_cache_key, $term_info, WEEK_IN_SECONDS);
        set_transient($price_cache_key, $price_filter, WEEK_IN_SECONDS);
        set_transient($rating_cache_key, $rating_filter, WEEK_IN_SECONDS);

        if (!empty($output)) {
            echo json_encode($output);
        }

        wp_die();

    }
    add_action('wp_ajax_et__fetch_products_data', 'et__fetch_products_data');
    add_action('wp_ajax_nopriv_et__fetch_products_data', 'et__fetch_products_data');

/* Product search
---------------*/

    add_action('wp_ajax_search_product', 'search_product');
    add_action('wp_ajax_nopriv_search_product', 'search_product');

    function search_product() {
        global $wpdb;

        $keyword  = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        if (empty($keyword)) {
            return;
        }

        $args = array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => 1,
            'posts_per_page'         => 20,
            'no_found_rows'          => true,     // ✅ big win
            'cache_results'          => true,     // keep true if you have persistent object cache (Redis/Memcached)
            'update_post_meta_cache' => true,     // rendering price/attrs needs meta
            'update_post_term_cache' => true,     // categories/visibility terms
            'fields'                 => 'ids',    // keep memory light; we’ll preload below
        );


        $mods                          = et_get_theme_mods();
        $is_wpml                       = defined('ICL_SITEPRESS_VERSION');
        $is_polylang                   = function_exists('pll_current_language');
        $tax_query                     = [];
        $meta_query                    = [];
        $product_ajax_search_threshold = $mods && isset($mods['product_ajax_search_threshold']) && !empty($mods['product_ajax_search_threshold']) ? $mods['product_ajax_search_threshold'] : 0.1;
        
        if ($is_wpml) {
            $current_language = isset($_POST['lang']) && !empty($_POST['lang']) ? $_POST['lang'] :  apply_filters('wpml_current_language', null);
            $args['suppress_filters'] = false;
        } elseif($is_polylang){
            $current_language = isset($_POST['lang']) && !empty($_POST['lang']) ? $_POST['lang'] :  pll_current_language();
            $args['lang'] = $current_language;
        }

        if (!empty($category)) {
            $tax_query[] = [
                'taxonomy'  => 'product_cat',
                'terms'     => $category,
                'field'     => 'slug',
                'operator'  => 'IN',
            ];
        }

        $visibility = wc_get_product_visibility_term_ids();

        $tax_query[] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => array('exclude-from-catalog'),
            'operator' => 'NOT IN',
        ];

        if ('yes' === get_option('woocommerce_hide_out_of_stock_items')) {
            // Faster than meta_query on _stock_status
            $tax_query[] = [
                'taxonomy' => 'product_visibility',
                'field'    => 'term_taxonomy_id',
                'terms'    => array( $visibility['outofstock'] ),
                'operator' => 'NOT IN',
            ];
        }


        /* Fuse
        -------*/

            $fuse_active = false;

            if (class_exists('Fuse')) {
                $fuse_active = true;
            } else {
                $Fuse = WP_PLUGIN_DIR . '/enovathemes-addons/includes/vendor/autoload.php';
                if (file_exists($Fuse)) {
                    require_once $Fuse;
                    $fuse_active = true;
                }
            }

            $product_index = get_transient('et-woo-product-index');

            if ($product_index && $fuse_active) {

                $product_index = isset($_POST['lang']) && isset($product_index[$_POST['lang']]) ? $product_index[$_POST['lang']] : $product_index['default'];

                $fuse_keys = et__get_search_in_keys($product_index);

                $options = [
                    'keys' => $fuse_keys,
                    'threshold' => floatval($product_ajax_search_threshold),
                    'includeScore' => false,
                    'ignoreLocation' => true,
                    'useExtendedSearch' => true
                ];

                $fuse = new \Fuse\Fuse($product_index, $options);
                $product_IDs = $fuse->search(urldecode($keyword));
                if (!empty($product_IDs) && is_array($product_IDs)) {

                    $product_IDs = array_filter(array_map(function($product) {
                        return $product['item']['id'];
                    }, $product_IDs));

                    $product_IDs = array_unique($product_IDs,SORT_REGULAR);
                    $product_IDs = array_slice($product_IDs, 0, 200); // ✅ short IN() lists are faster

                    $args['post__in'] = $product_IDs;
                    $args['orderby'] = 'post__in'; // ✅ keep Fuse order

                } else {
                    $args['s'] = urldecode($keyword);
                }

            } else {
                $args['s'] = urldecode($keyword);
            }

        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $args['tax_query'] = $tax_query;
        }

        if (!empty($meta_query)) {
            if (count($meta_query) > 1) {
                $meta_query['relation'] = 'AND';
            }
            $args['meta_query'] = $meta_query;
        }

        $return = [
            'args' => $args,
        ];
        
        add_filter('posts_search', 'et__woocommerce_product_search_in_query', 10, 2);
        $WP_Query = new WP_Query($args);
        remove_filter('posts_search', 'et__woocommerce_product_search_in_query', 10);

        $post_ids = $WP_Query->posts;

        if($WP_Query->have_posts()){

            $current_rate = (isset($_POST['currency']) && !empty($_POST['currency'])) ? et__get_currency_rate($_POST['currency']) : 1;

            ob_start();
            while ($WP_Query->have_posts() ) {
            $WP_Query->the_post();

                $thumb_size = 'woocommerce_thumbnail';
                $product    = wc_get_product(get_the_ID());

                if (isset($_POST['currency']) && !empty($_POST['currency'])) {

                    $base_price      = get_post_meta(get_the_ID(), '_price', true);
                    $converted_price = $base_price * $current_rate;
                    $current_currency = sanitize_text_field($_POST['currency']);

                    add_filter('woocommerce_get_price_html', function($price, $product) use ($converted_price, $current_currency) {
                        return wc_price($converted_price, ['currency' => $current_currency]);
                    }, 10, 2);

                } ?>

                <li class="product post">
                    <a href="<?php the_permalink() ?>">
                        <div class="product-image-wrapper">
                            <div class="product-image">
                                <?php echo mobex_enovathemes_build_post_media($thumb_size,$product->get_image_id(),'product'); ?>
                            </div>
                        </div>
                        <div class="product-data">
                            <div class="product-categories">
                                <?php

                                    $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
                                    foreach ($categories as $category) {
                                        echo '<span>'.esc_html($category).'</span>';
                                    }

                                    $sku = $product->get_sku();

                                    if ($sku) {
                                        echo '<span>'.esc_html__("SKU","enovathemes-addons").': '.esc_html($sku).'</span>';
                                    }

                                ?>
                            </div>

                            <h3><?php echo esc_html($product->get_name()); ?></h3>

                            <div class="product-price">
                                <?php echo $product->get_price_html(); ?>
                            </div>

                        </div>
                    </a>
                </li>

                <?php
            }
            $return['products'] = ob_get_clean();

        } else {

            ob_start(); // Start output buffering

            do_action('woocommerce_no_products_found');

            $return['not_found'] = ob_get_clean();

        }

        wp_reset_postdata();
     
        echo json_encode($return);
        wp_die();
    }

/*  Attribute display type
/*-------------------*/

	add_action( 'cmb2_admin_init', function() {

		$prefix = 'et__';
	
		if (class_exists("Woocommerce")) {

			$attribute_taxonomies = wc_get_attribute_taxonomies();

		    foreach ($attribute_taxonomies as $attribute) {
		        $taxonomy = 'pa_' . $attribute->attribute_name; // WooCommerce prepends 'pa_' to attribute taxonomies

		        $display_type = et__get_attribute_display_type($attribute->attribute_id);

		        // Create a CMB2 box for the attribute taxonomy
		        $cmb_product_attributes = new_cmb2_box(array(
		            'id'           => $taxonomy . '_display_type',
		            'title'        => esc_html__('Display Type', 'enovathemes-addons'),
		            'object_types' => array('term'), // Apply to terms
		            'taxonomies'   => array($taxonomy), // Specific taxonomy
		        ));

		        if ($display_type == "image") {
		        	// Add a select field for Display Type
			        $cmb_product_attributes->add_field(array(
			            'name'    => sprintf(esc_html__('Select %s image', 'enovathemes-addons'),strtolower($attribute->attribute_label)),
			            'id'      => 'enova_'.$taxonomy . '_image',
			            'type'    => 'file',
			            'options' => array(
					        'url' => false, // Hide the text input for the url
					    ),
					    'query_args' => array(
					        'type' => array(
					            'image/gif',
					            'image/jpeg',
					            'image/png',
					            'image/svg',
					            'image/webp',
					            'image/heic',
					        ),
					    ),
					    'preview_size' => 'large', // Image size to use when previewing in the admin.
			        ));

		        } elseif ($display_type == "color") {
		        	
		        	$cmb_product_attributes->add_field(array(
			            'name'    => esc_html__('Select color', 'enovathemes-addons'),
			            'id'      => 'enova_'.$taxonomy . '_color',
			            'type'    => 'colorpicker',
			            'default' => ''
			        ));

		        }

		        
		    }
		}
	});

    add_action('woocommerce_after_edit_attribute_fields', 'et__add_display_type_option_to_attribute');
    add_action('woocommerce_after_add_attribute_fields', 'et__add_display_type_option_to_attribute');
    function et__add_display_type_option_to_attribute($attribute) {
        $attribute_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
        $current_value = $attribute_id ? get_option('attribute_display_type_' . $attribute_id, 'select') : 'select';

        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="attribute_display_type"><?php esc_html_e('Display Type', 'enovathemes-addons'); ?></label>
            </th>
            <td>
                <select name="attribute_display_type" id="attribute_display_type">
                    <option value="select" <?php selected($current_value, 'select'); ?>><?php esc_html_e('Select', 'enovathemes-addons'); ?></option>
                    <option value="image" <?php selected($current_value, 'image'); ?>><?php esc_html_e('Image', 'enovathemes-addons'); ?></option>
                    <option value="label" <?php selected($current_value, 'label'); ?>><?php esc_html_e('Label', 'enovathemes-addons'); ?></option>
                    <option value="color" <?php selected($current_value, 'color'); ?>><?php esc_html_e('Color', 'enovathemes-addons'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Select how this attribute should be displayed.', 'enovathemes-addons'); ?></p>
            </td>
        </tr>
        <?php
    }

    // Save the custom field when an attribute is added or updated
    add_action('woocommerce_attribute_added', 'et__save_display_type_option', 10, 2);
    add_action('woocommerce_attribute_updated', 'et__save_display_type_option', 10, 3);
    function et__save_display_type_option($attribute_id) {
        if (isset($_POST['attribute_display_type'])) {
            update_option('attribute_display_type_' . $attribute_id, sanitize_text_field($_POST['attribute_display_type']));
        }
    }

    function et__render_display_type_column($content, $column_name, $term_id) {
        if ('display_type' === $column_name) {
            // Get the taxonomy and attribute ID dynamically
            $term = get_term($term_id);
            if ($term && !is_wp_error($term)) {
                $taxonomy = $term->taxonomy;
                $attribute_id = wc_attribute_taxonomy_id_by_name(str_replace('pa_', '', $taxonomy));

                // Get the display type for this attribute
                $display_type = get_option('attribute_display_type_' . $attribute_id, 'select');

                switch ($display_type) {
                    case 'image':
                        $image_url = get_term_meta($term_id, 'enova_' . $taxonomy . '_image', true);
                        if ($image_url) {
                            $content = '<img class="attribute-term-image" width="100" src="'.esc_url($image_url).'" alt="attribute-term-'.$term_id.'-image" />'; // Capitalize the first letter
                        } else {
                            $content = '<a class="term-display-type-edit" href="'.esc_url(get_edit_term_link($term_id,$taxonomy)).'" title="'.esc_html__("Add image","enovathemes-addons").'">'.esc_html__("Add image","enovathemes-addons").'</a>';
                        }
                        break;
                    case 'color':
                        $color = get_term_meta($term_id, 'enova_' . $taxonomy . '_color', true);
                        if ($color) {

                            $style = et__is_light_color($color) ? 'background:'.esc_attr($color).';border:1px solid #ccc;' : 'background:'.esc_attr($color).';';

                            $content = '<div class="attribute-term-color" style="'.esc_attr($style).'"></div>'; // Capitalize the first letter
                        } else {
                            $content = '<a class="term-display-type-edit" href="'.esc_url(get_edit_term_link($term_id,$taxonomy)).'" title="'.esc_html__("Add color","enovathemes-addons").'">'.esc_html__("Add color","enovathemes-addons").'</a>';

                        }
                        break;
                    default:
                        $content = ucfirst($display_type); // Capitalize the first letter
                        break;
                }

            }
        }
        return $content;
    }

    // Register the column for all WooCommerce attributes
    function et__register_display_type_column() {
        if (function_exists('wc_get_attribute_taxonomies')) {
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            if ($attribute_taxonomies) {
                foreach ($attribute_taxonomies as $attribute) {
                    $taxonomy = 'pa_' . $attribute->attribute_name;

                    $display_type = et__get_attribute_display_type($attribute->attribute_id);


                    if (in_array($display_type, ['color','image'])) {
                        add_filter('manage_edit-' . $taxonomy . '_columns', function ($columns) use ($display_type) {
                            $columns['display_type'] = ($display_type === 'color')
                                ? esc_html__('Color', 'enovathemes-addons')
                                : esc_html__('Image', 'enovathemes-addons');
                            return $columns;
                        });
                        add_filter('manage_' . $taxonomy . '_custom_column', 'et__render_display_type_column', 10, 3);
                    }
                }
            }
        }
    }
    add_action('init', 'et__register_display_type_column');

/*  Custom order logic
---------------------*/

    add_filter('woocommerce_get_catalog_ordering_args', 'et__custom_woocommerce_products_sort', 20, 2);
    function et__custom_woocommerce_products_sort($args,$orderby) {

        if ($orderby) {
            switch ($orderby) {
                case 'popular':
                    $args['orderby']  = "meta_value_num date title"; // Correct way to order by multiple fields
                    $args['order']    = 'DESC'; // Descending order
                    $args['meta_key'] = 'total_sales'; // Sorting by sales
                break;
                case 'reviews':
                    $args['orderby']  = "meta_value_num date title"; // Correct way to order by multiple fields
                    $args['order']    = 'DESC'; // Descending order
                    $args['meta_key'] = '_wc_average_rating'; // Sorting by sales
                break;
                case 'latest':
                    $args['orderby']  = "date title"; // Correct way to order by multiple fields
                    $args['order']    = 'DESC'; // Descending order
                    $args['meta_key'] = ''; // Sorting by sales
                break;
            }
        }

        return $args;
    }

    add_filter( 'woocommerce_catalog_orderby', 'et__custom_woocommerce_sort_by_dropdown' );
    add_filter('woocommerce_default_catalog_orderby_options', 'et__custom_woocommerce_sort_by_dropdown');
    function et__custom_woocommerce_sort_by_dropdown( $options ) {
        unset($options['popularity']);
        unset($options['rating']);
        unset($options['date']);
        unset($options['price']);
        unset($options['price-desc']);
        $options['popular'] = esc_html__( 'Sort by popularity', 'enovathemes-addons' );
        $options['reviews'] = esc_html__( 'Sort by average rating', 'enovathemes-addons' );
        $options['latest'] = esc_html__( 'Sort by latest', 'enovathemes-addons' );
        $options['price'] = esc_html__( 'Sort by price: low to high', 'enovathemes-addons' );
        $options['price-desc'] = esc_html__( 'Sort by price: high to low', 'enovathemes-addons' );
        return $options;
    }

/*  Product index
---------------------*/

    add_action('admin_menu', function(){
        add_submenu_page(
            'edit.php?post_type=product', 
            esc_html__( 'Index products', 'enovathemes-addons' ), 
            esc_html__( 'Index products', 'enovathemes-addons' ),
            'manage_options', 
            'product_index',
            'et__index_products',
            5
        );
    }, 10);

    function et__index_products(){

        $mods = et_get_theme_mods();
        $product_ajax_filter = $mods && isset($mods['product_ajax_filter']) && !empty($mods['product_ajax_filter']) ? 1 : 0;
        $button_label = esc_html__("Index products", "enovathemes-addons");

        if (get_transient('et-woo-product-index')) {
            $button_label = esc_html__("Re-index products", "enovathemes-addons");
        }

        // Handle form submission and save values (for AJAX)
        if (isset($_POST['et-woo-product-index-submit'])) {
            // Check for nonce and validate
            if (isset($_POST['et_woo_product_index_nonce']) && wp_verify_nonce($_POST['et_woo_product_index_nonce'], 'et_woo_product_index_action')) {

                // Save checkbox value
                $automage_product_index = isset($_POST['automage_product_index']) ? 1 : 0;
                update_option('automage_product_index', $automage_product_index);

                // Save select dropdown value
                $product_index_interval = isset($_POST['product_index_interval']) ? sanitize_text_field($_POST['product_index_interval']) : '';
                update_option('product_index_interval', $product_index_interval);
                
                // Handle further actions like re-indexing here if necessary...
            }
        }

        // Get saved values for checkbox and select
        $saved_automage_product_index = get_option('automage_product_index', 0);
        $saved_product_index_interval = get_option('product_index_interval', 'Weekly');

        ?>
        <form class="et-woo-product-index" method="post">
            <h1><?php esc_html_e("Index products", "enovathemes-addons"); ?></h1>
            <p><?php echo esc_html__("Index products for faster AJAX filtering and search.", "enovathemes-addons"); ?></p>

            <?php if ($product_ajax_filter): ?>
                <?php 
                $last_index = get_theme_mod('product_index');
                ?>
                <?php if (!empty($last_index)): ?>
                    <p class="index-stats">
                        <?php echo sprintf('%s %s', 'Last index:', $last_index); ?>
                    </p>
                <?php endif ?>
                
                <!-- Button for AJAX form submission -->
                <button type="submit" class="button button-primary" name="et-woo-product-index-submit"><?php echo $button_label; ?></button>
            <?php else: ?>
                <p class="warning"><?php echo esc_html__("Make sure to activate AJAX product filter from appearance >> customize >> theme options >> WooCommerce", "enovathemes-addons"); ?></p>
            <?php endif ?>

            <!-- Nonce field for security -->
            <?php wp_nonce_field('et_woo_product_index_action', 'et_woo_product_index_nonce'); ?>
        </form>
        <br />
        <hr />

        <!-- Custom Fields Outside of the Form (for AJAX) -->
        <div class="custom-fields">
            <h2><?php esc_html_e('Product Index Settings', 'enovathemes-addons'); ?></h2>
            
            <form name="save_product_index_settings" method="post" action="">
                <!-- Nonce field for security -->
                <?php wp_nonce_field('save_product_index_settings_action', 'save_product_index_settings_nonce'); ?>
                <input type="hidden" name="action" value="save_product_index_settings" />

                <div class="form-field">
                    <input type="checkbox" name="automage_product_index" id="automage_product_index" value="1" <?php checked($saved_automage_product_index, 1); ?> />
                    <label for="automage_product_index"><?php esc_html_e('Automated Product Indexing', 'enovathemes-addons'); ?></label>
                    <p class="description"><?php esc_html_e('Enable to automatically index products.', 'enovathemes-addons'); ?></p>
                </div>

                <div class="form-field interval">
                    <!-- Select for product_index_interval -->
                    <label for="product_index_interval"><?php esc_html_e('Product Index Interval', 'enovathemes-addons'); ?></label>
                    <select name="product_index_interval" id="product_index_interval">
                        <option value="weekly" <?php selected($saved_product_index_interval, 'weekly'); ?>><?php esc_html_e('Weekly', 'enovathemes-addons'); ?></option>
                        <option value="daily" <?php selected($saved_product_index_interval, 'daily'); ?>><?php esc_html_e('Daily', 'enovathemes-addons'); ?></option>
                        <option value="hourly" <?php selected($saved_product_index_interval, 'hourly'); ?>><?php esc_html_e('Hourly', 'enovathemes-addons'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Choose how often the product index should run.', 'enovathemes-addons'); ?></p>
                </div>

                <button type="submit" class="button button-secondary"><?php esc_html_e('Save Settings', 'enovathemes-addons'); ?></button>
            </form>

        </div>

        <?php
        
    }

    function et__save_product_index_settings() {
        // Verify nonce for security
        if (isset($_POST['save_product_index_settings_nonce']) && wp_verify_nonce($_POST['save_product_index_settings_nonce'], 'save_product_index_settings_action')) {

            // Save checkbox value
            $automage_product_index = isset($_POST['automage_product_index']) ? 1 : 0;
            update_option('automage_product_index', $automage_product_index);

            // Save select dropdown value
            $product_index_interval = isset($_POST['product_index_interval']) ? sanitize_text_field($_POST['product_index_interval']) : '';
            update_option('product_index_interval', $product_index_interval);

            // Prepare response data
            $response = [
                'automage_product_index' => $automage_product_index,
                'product_index_interval' => $product_index_interval,
                'output' => '<div class="updated"><p>' . esc_html__('Settings saved successfully.', 'enovathemes-addons') . '</p></div>'
            ];

            // Return the response as JSON
            echo json_encode($response);
        }

        wp_die(); // End the AJAX request
    }
    add_action('wp_ajax_save_product_index_settings', 'et__save_product_index_settings');

    function et__get_product_data($product_id, $lang = '') {

        $mods       = et_get_theme_mods();
        $product_title_length = isset($mods['product_title_length']) ? $mods['product_title_length'] : 56;
        $product_catalog_mode = $mods && isset($mods['product_catalog_mode']) && !empty($mods['product_catalog_mode']) ? true : false;

        $product_ajax_search_in                   = isset($mods['product_ajax_search_in']) ? $mods['product_ajax_search_in'] : ['title','description','sku'];
        $product_ajax_search_in_global_attributes = isset($mods['product_ajax_search_in_global_attributes']) ? $mods['product_ajax_search_in_global_attributes'] : [];
        $product_ajax_search_in_custom_attributes = isset($mods['product_ajax_search_in_custom_attributes']) ? explode(', ', $mods['product_ajax_search_in_custom_attributes']) : [];
        $product_brand_attribute                  = isset($mods['product_brand_attribute']) && !empty($mods['product_brand_attribute']) ? $mods['product_brand_attribute'] : 'brand';

        if (!empty($product_ajax_search_in_custom_attributes)) {
            $product_ajax_search_in_custom_attributes = array_map('sanitize_title_with_dashes', $product_ajax_search_in_custom_attributes);
        }

        $product = wc_get_product($product_id);
        if (!$product) return null;

        $product_info = [
            'id' => $product_id,
            'classes' => wc_get_product_class('', $product_id),
            'title' => et__substrwords($product->get_name(),$product_title_length),
            'link' => get_permalink($product_id),
            'sku' => $product->get_sku(),
            'price_html' => $product->get_price_html(),
            'image'  => [],
            'categories'  => [],
        ];

        // Append prices for all currencies (YayCurrency or WCML)
        $prices_by_currency = et__get_prices_across_currencies( $product );
        // if ( ! empty( $prices_by_currency ) ) {
            $product_info['prices_by_currency'] = $prices_by_currency;
        // }

        $image_id = $product->get_image_id(); // Get main image ID

        $image_id = $image_id ? $image_id : get_option('woocommerce_placeholder_image', 0);

        if ($image_id) {

            $image_data = wp_get_attachment_image_src($image_id, 'woocommerce_thumbnail');

            if ($image_data) {

                $lazy_image = wp_get_attachment_image_src($image_id,'lazy_img');

                $image_url = $image_data[0];
                $image_width = $image_data[1];
                $image_height = $image_data[2];

                $thumbnail_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true); 
                $image_alt     = !empty($thumbnail_alt) ? $thumbnail_alt : $product->get_name();

                $product_info['image']['url'] = $image_data[0];
                $product_info['image']['width'] = $image_data[1];
                $product_info['image']['height'] = $image_data[2];

                if (!empty($image_alt)) {
                    $product_info['image']['alt'] = $image_alt;
                }

                if ($lazy_image) {
                    $product_info['image']['lazy'] = $lazy_image[0];
                }

            }
        }

        $categories = wp_get_post_terms($product_id, 'product_cat', ['lang' => $lang]); // Fetch categories based on language
        
        $search_terms = [];

        foreach ($categories as $category) {
            $category_info = [
                'name' => htmlspecialchars_decode($category->name),
                'slug' => $category->slug,
            ];

            $product_info['categories'][] = $category_info;
        }

        $search_in_global  = [];
       
        if ( $product->is_type( 'variable' ) ) {
            $variations = $product->get_children(); // Get variation IDs

            foreach ( $variations as $variation_id ) {
                $variation = wc_get_product( $variation_id );
                $search_in_global[] = $variation->get_sku();
            }
            
        }

        if (in_array('title', $product_ajax_search_in)) {
            $search_in_global[] = htmlspecialchars_decode($product->get_name());

        }

        if (in_array('description', $product_ajax_search_in)) {
            $product_info['search_in_description'] = htmlspecialchars_decode($product->get_description());
        }

        if (in_array('sku', $product_ajax_search_in)) {
            $search_in_global[] = $product->get_sku();
        }

        if (in_array('ean', $product_ajax_search_in)) {
            $search_in_global[] = get_post_meta( $product->get_id(), '_global_unique_id', true );
        }

        if (taxonomy_exists('pa_'.$product_brand_attribute)) {

            $brand_attr = get_the_terms($product->get_id(),'pa_'.$product_brand_attribute);

            if ($brand_attr && !is_wp_error($brand_attr)) {

                foreach ($brand_attr as $key => $term) {
                    $search_in_global[] = htmlspecialchars_decode($term->name);
                }
                
            }
        }

        // Handle categories
        $categories = wp_get_post_terms($product_id, 'product_cat', ['lang' => $lang]); // Fetch categories based on language
        
        $search_terms = [];

        foreach ($categories as $category) {
            $search_terms[] = htmlspecialchars_decode($category->name);
            $search_in_global[] = htmlspecialchars_decode($category->name);
        }

        if (in_array('category', $product_ajax_search_in)) {
            $search_in_global[] = implode(' ', $search_terms);
        }

        // Handle tags
        $tags = wp_get_post_terms($product_id, 'product_tag', ['lang' => $lang]); // Fetch tags based on language
        
        $search_terms = [];

        foreach ($tags as $tag) {
            $search_terms[] = htmlspecialchars_decode($tag->name);
        }

        if (in_array('tag', $product_ajax_search_in)) {
            $search_in_global[] = implode(' ', $search_terms);
        }

        // Handle attributes
        $attributes = $product->get_attributes();

        if ($attributes) {
            
            foreach ($attributes as $attribute_name => $attribute) {
                if ($attribute->is_taxonomy()) {

                    $terms = wp_get_post_terms($product_id, $attribute->get_name());
                    
                    $search_terms = [];

                    foreach ($terms as $term) {
                        $search_terms[] = htmlspecialchars_decode($term->name);
                    }

                    if (
                        in_array('global_attributes', $product_ajax_search_in) && 
                        in_array(str_replace('pa_', '', $attribute_name), $product_ajax_search_in_global_attributes)
                    ) {
                        $search_in_global[] = implode(' ', $search_terms);
                    }

                } else {

                    $search_terms = [];

                    foreach ($attribute->get_options() as $value) {
                        $search_terms[] = $value;
                    }

                    if (
                        in_array('custom_attributes', $product_ajax_search_in) && 
                        in_array($attribute_name, $product_ajax_search_in_custom_attributes)
                    ) {
                        $search_in_global[] = implode(' ', $search_terms);
                    }

                }
            }

        }

        // Handle vehicles
        $vehicles = wp_get_post_terms($product_id, 'vehicles', ['lang' => $lang]); // Fetch tags based on language
        
        $search_vehicles = [];

        foreach ($vehicles as $vehicle) {

            $make = get_term_meta($vehicle->term_id,'vehicle_make',true);
            $model = get_term_meta($vehicle->term_id,'vehicle_model',true);

            $search_vehicles[] = trim($make.' '.$model);
        }

        if (!empty($search_vehicles)) {
            $search_vehicles = array_unique($search_vehicles,SORT_REGULAR);

            foreach ($search_vehicles as $vehicle) {
                $search_in_global[] = $vehicle;
            }

        }

        if (!empty($search_in_global)) {
            $product_info["search_in_global"] = implode(' ', $search_in_global);
        }

        return apply_filters('et__get_product_data_from_index', $product_info, $product);
    }


    function et__get_category_data($category_id, $lang = '') {

        $category = get_term($category_id, 'product_cat');
        if ($category) {

            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
            $image_url    = wp_get_attachment_url($thumbnail_id);

            $category_info = [
                'id' => $category->term_id,
                'name' => html_entity_decode($category->name),
                'slug' => $category->slug,
                'link' => get_term_link($category),
                'parent_id' => $category->parent,
            ];

            if ($image_url) {
                $category_info['image'] = esc_url($image_url);
                $category_info['image_id'] = $thumbnail_id;
            }

            return $category_info;
            
        }

        return null;

    }

    function et__woo_product_index() {
        if (!isset($_POST['index-nonce']) || !wp_verify_nonce($_POST['index-nonce'], 'et-woo-product-index')) {
            wp_send_json_error(['error' => 'Invalid nonce']);
        }

        global $wpdb;

        $index = 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 500;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        // Check if WPML or Polylang is active
        $is_wpml = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_languages_list');

        // Check if out-of-stock products should be hidden
        $hide_out_of_stock = get_option('woocommerce_hide_out_of_stock_items') === 'yes';

        $grouped_product_data = [];
        $grouped_category_data = [];

        // Prepare SQL condition for product visibility
        $visibility_condition = "
            p.ID NOT IN (
                SELECT tr.object_id FROM {$wpdb->prefix}term_relationships tr
                INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
                WHERE tt.taxonomy = 'product_visibility' AND t.slug = 'exclude-from-catalog'
            )
        ";

        // Prepare SQL condition for stock status
        $stock_condition = $hide_out_of_stock ? "AND pm.meta_key = '_stock_status' AND pm.meta_value = 'instock'" : "";

        if ($is_wpml) {
            $languages = icl_get_languages('skip_missing=0');
            foreach ($languages as $lang) {
                $product_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p
                    INNER JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id
                    LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
                    WHERE p.post_type = 'product' AND p.post_status = 'publish' 
                    AND t.language_code = %s 
                    AND $visibility_condition 
                    $stock_condition
                    LIMIT %d OFFSET %d
                ", $lang['language_code'], $limit, $offset));

                if ($product_ids) {
                    $grouped_product_data[$lang['language_code']] = [];
                    foreach ($product_ids as $product_id) {
                        $product_info = et__get_product_data($product_id, $lang['language_code']);
                        if ($product_info) {
                            $grouped_product_data[$lang['language_code']][] = $product_info;
                            $index++;
                        }
                    }

                    if (!empty($product_ids)) {
                        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
                        $query = $wpdb->prepare("
                            SELECT t.term_id FROM {$wpdb->prefix}terms t
                            INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
                            INNER JOIN {$wpdb->prefix}term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                            WHERE tt.taxonomy = 'product_cat' AND tr.object_id IN ($placeholders)
                            GROUP BY t.term_id
                        ", ...$product_ids);

                        $category_ids = $wpdb->get_col($query);

                        foreach ($category_ids as $category_id) {
                            $category_info = et__get_category_data($category_id, $lang['language_code']);
                            if ($category_info) {
                                $grouped_category_data[$lang['language_code']][] = $category_info;
                            }
                        }
                    }

                }

            }
        } elseif ($is_polylang) {
            $languages = pll_languages_list();
            foreach ($languages as $lang) {
                $product_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p
                    INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
                    INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
                    WHERE p.post_type = 'product' AND p.post_status = 'publish' 
                    AND tt.taxonomy = 'product_cat' 
                    AND $visibility_condition 
                    $stock_condition
                    LIMIT %d OFFSET %d
                ", $limit, $offset));

                if ($product_ids) {
                    $grouped_product_data[$lang] = [];
                    foreach ($product_ids as $product_id) {
                        $product_info = et__get_product_data($product_id, $lang);
                        if ($product_info) {
                            $grouped_product_data[$lang][] = $product_info;
                            $index++;
                        }
                    }

                    // Fetch product categories per language
                    if (!empty($product_ids)) {
                        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
                        $query = $wpdb->prepare("
                            SELECT t.term_id FROM {$wpdb->prefix}terms t
                            INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
                            INNER JOIN {$wpdb->prefix}term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                            WHERE tt.taxonomy = 'product_cat' AND tr.object_id IN ($placeholders)
                            GROUP BY t.term_id
                        ", ...$product_ids);

                        $category_ids = $wpdb->get_col($query);

                        foreach ($category_ids as $category_id) {
                            $category_info = et__get_category_data($category_id, $lang);
                            if ($category_info) {
                                $grouped_category_data[$lang][] = $category_info;
                            }
                        }
                    }
                }
            }
        } else {
            $product_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p
                LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
                WHERE p.post_type = 'product' AND p.post_status = 'publish'
                AND $visibility_condition 
                $stock_condition
                LIMIT %d OFFSET %d
            ", $limit, $offset));

            if ($product_ids) {
                $grouped_product_data['default'] = [];
                foreach ($product_ids as $product_id) {
                    $product_info = et__get_product_data($product_id);
                    if ($product_info) {
                        $grouped_product_data['default'][] = $product_info;
                        $index++;
                    }
                }

                // Fetch product categories
                if (!empty($product_ids)) {
                    $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
                    $query = $wpdb->prepare("
                        SELECT t.term_id FROM {$wpdb->prefix}terms t
                        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
                        INNER JOIN {$wpdb->prefix}term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                        WHERE tt.taxonomy = 'product_cat' AND tr.object_id IN ($placeholders)
                        GROUP BY t.term_id
                    ", ...$product_ids);

                    $category_ids = $wpdb->get_col($query);

                    foreach ($category_ids as $category_id) {
                        $category_info = et__get_category_data($category_id);
                        if ($category_info) {
                            $grouped_category_data['default'][] = $category_info;
                        }
                    }
                }
            }

        }

        $has_mode = !empty($product_ids) && count($product_ids) >= $limit;

        if ($has_mode == false) {
            set_transient('et-woo-product-index', $grouped_product_data, 0);
            set_transient('et-woo-category-index', $grouped_category_data, 0);
            date_default_timezone_set('UTC');
            set_theme_mod('product_index', sprintf('%d %s %s', $index, esc_html__('products at', 'enovathemes-addons'), date('F j, Y')));
        }

        echo json_encode([
            'products' => $grouped_product_data,
            'categories' => $grouped_category_data,
            'offset' => $offset + count($product_ids), // Use actual count
            'has_more' => $has_mode
        ]);

        wp_die();
    }
    add_action('wp_ajax_et-woo-product-index', 'et__woo_product_index');
    add_action('et__woo_product_index_cron_hook', 'et__woo_product_index');

    if (!wp_next_scheduled('et__woo_product_index_cron_hook')) {

        $automage_product_index = get_option('automage_product_index',0);
        $product_index_interval = get_option('product_index_interval','weekly');

        if ($automage_product_index) {
            wp_schedule_event(time(), $product_index_interval, 'et__woo_product_index_cron_hook');
        }

    }

    function et__no_products_found(){
        ob_start(); // Start output buffering

        do_action('woocommerce_no_products_found');

        $template = ob_get_clean();

        return $template;
    }

    function et__fetch_product_index(){

        $product_index = get_transient('et-woo-product-index');
        if ($product_index) {

            $return = [
                'products' => $product_index,
            ];

            $no_products_found = et__no_products_found();

            if (!empty($no_products_found)) {
                $return['no_products_found'] = $no_products_found;
            }

            if (!empty($return)) {
                echo json_encode($return);
            }

        }

        wp_die();

    }
    add_action('wp_ajax_et__fetch_product_index', 'et__fetch_product_index');
    add_action('wp_ajax_nopriv_et__fetch_product_index', 'et__fetch_product_index');

/*  Woo Hooks
/*-------------------*/

    add_filter( 'woocommerce_post_class', function( $classes, $product ) {
       
        // Example: add a custom marker class
        $classes[] = 'post';

        return $classes;
    }, 10, 2 );


    function et__product_ajax_search($embed = true){

        $class = ["et__product_ajax_search"];

        if ($embed) {
            $class[] = "embed";
        }

        ?>
        <form action="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" name="et__product_ajax_search" class="<?php echo esc_attr(implode(' ', $class)) ?>">
            <input type="text" name="s" class="query" placeholder="<?php echo esc_attr__("What are you looking for?","enovathemes-addons") ?>" value="<?php echo (isset($_GET['s']) && !empty($_GET['s']) ? $_GET['s'] : ''); ?>">
            <?php if ($embed == false): ?>
                <?php wp_nonce_field('', 'et__product_ajax_search'); ?>
            <?php endif ?>
        </form>
    <?php }

    function et__is_product_in_cart( $product_id ) {
        if ( WC()->cart && ! WC()->cart->is_empty() ) {
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                if ( $cart_item['product_id'] == $product_id ) {
                    return true;
                }
            }
        }
        return false;
    }

    function et__get_currency_rate($current_currency = null) {

        if (empty($current_currency)) {
            $current_currency = get_woocommerce_currency();
        }

        $rate = 1;

        // Handle YayCurrency plugin
        if (defined('YAY_CURRENCY_FILE') && class_exists('Yay_Currency\Helpers\YayCurrencyHelper')) {

            $currency_obj     = Yay_Currency\Helpers\YayCurrencyHelper::get_currency_by_currency_code($current_currency);
            $rate             = Yay_Currency\Helpers\YayCurrencyHelper::get_rate_fee($currency_obj);

        }
        // Handle WPML Currency Switcher
        elseif (function_exists('wcml_get_currency') && function_exists('wcml_get_exchange_rate')) {

            $default_currency = get_option('woocommerce_currency');

            if ($current_currency !== $default_currency) {
                $rate = wcml_get_exchange_rate($current_currency);
            } else {
                $rate = 1;
            }

        }

        return $rate;
    }

    function et__get_prices_across_currencies( WC_Product $product ) {
        $data = [];

        // --- figure out base (store-currency) effective price bounds ---
        // For simple: single price; for variable: min/max effective (sale-aware).
        $is_variable = $product && $product->is_type('variable');

        if ( $is_variable ) {
            // Effective (sale-aware) variation prices in base currency
            $base_min = (float) $product->get_variation_price('min', false); // false = raw, no tax adjustment
            $base_max = (float) $product->get_variation_price('max', false);

            // Fallbacks if missing (rare)
            if ($base_min <= 0 || $base_max <= 0) {
                $base_min = (float) $product->get_variation_regular_price('min', false);
                $base_max = (float) $product->get_variation_regular_price('max', false);
            }
        } else {
            $regular = (float) $product->get_regular_price();
            $sale    = (float) $product->get_sale_price();
            $effective = ($product->is_on_sale() && $sale > 0) ? $sale : $regular;

            $base_min = (float) $effective;
            $base_max = (float) $effective;
        }

        // Nothing to convert?
        if ($base_min <= 0 && $base_max <= 0) {
            return $data;
        }

        // Helper to format either a single or range for a given currency code
        $format_html = static function( $min_val, $max_val, $code ) {
            $min_html = wc_price( $min_val, ['currency' => $code] );
            if ( $max_val > 0 && $max_val !== $min_val ) {
                $max_html = wc_price( $max_val, ['currency' => $code] );
                return sprintf( '%s – %s', $min_html, $max_html );
            }
            return $min_html;
        };

        // --- Branch A: YayCurrency ---
        if (
            function_exists('Yay_Currency\\plugin_init') &&
            class_exists('Yay_Currency\\Helpers\\YayCurrencyHelper') &&
            class_exists('Yay_Currency\\Helpers\\Helper')
        ) {
            $yay_currency_posts = get_posts([
                'post_type'   => 'yay-currency-manage',
                'numberposts' => -1,
                'post_status' => 'publish',
            ]);

            $currencies = \Yay_Currency\Helpers\Helper::converted_currencies($yay_currency_posts); // array of rows

            foreach ( (array) $currencies as $row ) {
                if ( empty($row['currency']) ) { continue; }
                $code  = $row['currency'];

                // "Apply" array that carries rate, rounding, fees, manual prices config, etc
                $apply = \Yay_Currency\Helpers\YayCurrencyHelper::get_currency_by_currency_code( $code );
                if ( empty($apply) ) { continue; }

                // Convert both ends of the range with YayCurrency’s converter (respects its settings)
                $min_conv = $base_min > 0 ? (float) apply_filters('yay_currency_convert_price', $base_min, $apply) : 0.0;
                $max_conv = $base_max > 0 ? (float) apply_filters('yay_currency_convert_price', $base_max, $apply) : 0.0;

                $html = $format_html($min_conv, $max_conv, $code);
                $data[$code] = [$html];
            }

            return $data;
        }

        // --- Branch B: WPML / WCML Multicurrency ---
        if ( defined('WCML_VERSION') ) {
            // Active currencies from WCML settings
            $wcml_settings = get_option('_wcml_settings');
            $active = [];
            if ( isset($wcml_settings['currency_options']) && is_array($wcml_settings['currency_options']) ) {
                $active = array_keys($wcml_settings['currency_options']);
            }
            // Fallback: use current FE currency if list not found
            if ( empty($active) ) {
                $current = apply_filters('wcml_price_currency', null);
                if ( $current ) { $active = [$current]; }
            }

            foreach ( $active as $code ) {
                // Convert both ends with WCML converter (raw amounts)
                $min_conv = $base_min > 0 ? (float) apply_filters('wcml_raw_price_amount', $base_min, $code) : 0.0;
                $max_conv = $base_max > 0 ? (float) apply_filters('wcml_raw_price_amount', $base_max, $code) : 0.0;

                // Format price HTML in that currency
                $html = $format_html($min_conv, $max_conv, $code);

                $data[$code] = [$html];
            }

            return $data;
        }

        // No multicurrency plugin — nothing to do
        return $data;
    }

    function et__woocommerce_product_search_in_query($search, $wp_query) {
        global $wpdb;

        // Get search term and normalize it
        $search_term = $wp_query->get('s');
        if (empty($search_term)) {
            return $search;
        }

        // Normalize and escape the search term
        $search_term     = urldecode($search_term);
        $normalized_term = strtolower($search_term);
        $normalized_term = str_replace(['_', '-'], '%', $normalized_term);
        $normalized_term = str_replace("'", '', $normalized_term); // Removing single quotes for security
        $normalized_term = '%' . $wpdb->esc_like($normalized_term) . '%';

        // Prepare SQL query for search
        $search = " AND (
            {$wpdb->posts}.post_type = 'product' AND (
                {$wpdb->posts}.post_title LIKE '{$normalized_term}'
                OR {$wpdb->posts}.post_content LIKE '{$normalized_term}'
                OR {$wpdb->posts}.post_excerpt LIKE '{$normalized_term}'
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta}
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                    AND ({$wpdb->postmeta}.meta_key LIKE '_product_attributes' OR {$wpdb->postmeta}.meta_key = '_sku')
                    AND LOWER({$wpdb->postmeta}.meta_value) LIKE '{$normalized_term}'
                )
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                    WHERE tr.object_id = {$wpdb->posts}.ID
                    AND LOWER(t.name) LIKE '{$normalized_term}'
                )
            )
        )";

        return $search;
    }

    function et__woocommerce_product_search($search){

        global $wpdb;

        // Normalize and escape the search term
        $search_term     = urldecode($search);
        $normalized_term = strtolower($search_term);
        $normalized_term = str_replace(['_', '-'], '%', $normalized_term);
        $normalized_term = str_replace("'", '', $normalized_term); // Removing single quotes for security
        $normalized_term = '%' . $wpdb->esc_like($normalized_term) . '%';

        // Prepare SQL query for search
        $sql = $wpdb->prepare(" AND (
            {$wpdb->posts}.post_type = 'product' AND (
                {$wpdb->posts}.post_title LIKE '{$normalized_term}'
                OR {$wpdb->posts}.post_content LIKE '{$normalized_term}'
                OR {$wpdb->posts}.post_excerpt LIKE '{$normalized_term}'
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta}
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                    AND ({$wpdb->postmeta}.meta_key LIKE '_product_attributes' OR {$wpdb->postmeta}.meta_key = '_sku')
                    AND LOWER({$wpdb->postmeta}.meta_value) LIKE '{$normalized_term}'
                )
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                    WHERE tr.object_id = {$wpdb->posts}.ID
                    AND LOWER(t.name) LIKE '{$normalized_term}'
                )
            )
        )");

        return $sql;

    }

    function et__get_product_terms($args) {
        global $wpdb;

        $tax_query  = isset($args['tax_query']) ? $args['tax_query'] : [];
        $meta_query = isset($args['meta_query']) ? $args['meta_query'] : [];
        $lang       = isset($args['lang']) ? $args['lang'] : substr(get_locale(), 0, 2);
        $post__in   = !empty($args['post__in']) ? array_map('absint', $args['post__in']) : [];

        $meta_query = new WP_Meta_Query($meta_query);
        $tax_query  = new WP_Tax_Query($tax_query);

        $meta_query_sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');
        $tax_query_sql  = $tax_query->get_sql($wpdb->posts, 'ID');

        $sql = "
            SELECT DISTINCT ID
            FROM {$wpdb->posts}
            {$tax_query_sql['join']} 
            {$meta_query_sql['join']}
            WHERE {$wpdb->posts}.post_type = 'product'
            AND {$wpdb->posts}.post_status = 'publish'
            {$tax_query_sql['where']} 
            {$meta_query_sql['where']}
        ";

        if (!empty($post__in)) {
            $sql .= " AND {$wpdb->posts}.ID IN (" . implode(',', $post__in) . ")";
        }

        if (!empty($args['s'])) {
            $sql .= et__woocommerce_product_search($args['s']);
        }

        $products = $wpdb->get_col($sql);

        if (empty($products)) {
            return [];
        }

        $is_wpml = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_current_language');

        if ($is_polylang && $lang !== pll_default_language()) {
            $products = array_filter(array_map(function ($post_id) use ($lang) {
                return pll_get_post($post_id, $lang);
            }, $products));
        } elseif ($is_wpml && $lang !== apply_filters('wpml_default_language', null)) {
            $products = array_filter(array_map(function ($post_id) use ($lang) {
                return apply_filters('wpml_object_id', $post_id, 'product', false, $lang);
            }, $products));
        }

        if (empty($products)) {
            return [];
        }

        $attributes = wc_get_attribute_taxonomies();
        $taxonomy_cache = [];
        $term_info      = [];
        $term_counts    = [];

        foreach ($attributes as $attribute) {
            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }
            $taxonomy_cache[] = $taxonomy;
        }

        $sql = "
            SELECT tr.term_taxonomy_id, tt.taxonomy, COUNT(tr.object_id) as term_count
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE tr.object_id IN (" . implode(',', $products) . ")
            AND tt.taxonomy IN ('" . implode("','", $taxonomy_cache) . "')
        ";

        $sql .= " GROUP BY tr.term_taxonomy_id";

        // Execute the query
        $all_terms = $wpdb->get_results($sql);

        foreach ($all_terms as $term) {
            $term_counts[$term->taxonomy][$term->term_taxonomy_id] = $term->term_count;
        }

        $term_objects = wp_get_object_terms($products, $taxonomy_cache);

        foreach ($term_objects as $term) {
            if (!isset($term_info[$term->taxonomy])) {
                $term_info[$term->taxonomy] = [];
            }

            $term_image_url = get_term_meta($term->term_id, 'enova_' . $term->taxonomy . '_image', true);
            $term_color = get_term_meta($term->term_id, 'enova_' . $term->taxonomy . '_color', true);

            $term_info_data = [
                'slug' => $term->slug,
                'name' => $term->name,
                'count' => isset($term_counts[$term->taxonomy][$term->term_id]) ? $term_counts[$term->taxonomy][$term->term_id] : 0,
            ];

            if ($term_image_url) {
                $term_info_data['img'] = esc_url($term_image_url);
            }

            if ($term_color) {
                if (et__is_light_color($term_color)) {
                    $term_info_data['color-light'] = $term_color;
                } else {
                    $term_info_data['color'] = $term_color;
                }
            }

            $term_info[$term->taxonomy][] = $term_info_data;
        }

        return $term_info;
    }

    function et__get_filtered_price($args) {
        global $wpdb;

        $tax_query  = isset($args['tax_query']) ? $args['tax_query'] : [];
        $meta_query = isset($args['meta_query']) ? $args['meta_query'] : [];
        $post__in   = !empty($args['post__in']) ? array_map('absint', $args['post__in']) : [];

        // Remove existing price and rating filters
        foreach ($meta_query + $tax_query as $key => $query) {
            if (!empty($query['price_filter']) || !empty($query['rating_filter'])) {
                unset($meta_query[$key]);
            }
        }

        $meta_query     = new WP_Meta_Query($meta_query);
        $tax_query      = new WP_Tax_Query($tax_query);
        $meta_query_sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');
        $tax_query_sql  = $tax_query->get_sql($wpdb->posts, 'ID');

        // Detect WPML or Polylang
        $is_wpml = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_get_post_language');

        $current_lang = isset($args['lang']) ? $args['lang'] : substr(get_locale(), 0, 2);
        $lang_filter = '';

        if ($is_wpml) {
            $lang_filter = $wpdb->prepare("
                INNER JOIN {$wpdb->prefix}icl_translations icl 
                ON {$wpdb->posts}.ID = icl.element_id 
                AND icl.element_type = 'post_product' 
                AND icl.language_code = %s", $current_lang);
        } elseif ($is_polylang) {
            $lang_filter = $wpdb->prepare("
                INNER JOIN {$wpdb->prefix}term_relationships pll_rel 
                ON {$wpdb->posts}.ID = pll_rel.object_id
                INNER JOIN {$wpdb->prefix}term_taxonomy pll_tax 
                ON pll_rel.term_taxonomy_id = pll_tax.term_taxonomy_id
                INNER JOIN {$wpdb->prefix}terms pll_terms 
                ON pll_tax.term_id = pll_terms.term_id
                AND pll_tax.taxonomy = 'language'
                AND pll_terms.slug = %s", $current_lang);
        }

        // Build inner SELECT with optional post__in
        $inner_sql = "
            SELECT ID 
            FROM {$wpdb->posts}
            {$tax_query_sql['join']} 
            {$meta_query_sql['join']} 
            $lang_filter
            WHERE {$wpdb->posts}.post_type IN ('" . implode("','", array_map('esc_sql', apply_filters('woocommerce_price_filter_post_type', ['product']))) . "')
            AND {$wpdb->posts}.post_status = 'publish'
            {$tax_query_sql['where']} 
            {$meta_query_sql['where']}
        ";

        if (!empty($post__in)) {
            $inner_sql .= " AND {$wpdb->posts}.ID IN (" . implode(',', $post__in) . ")";
        }

        if (!empty($args['s'])) {
            $inner_sql .= et__woocommerce_product_search($args['s']);
        }

        // Final full query for price range
        $sql = "
            SELECT MIN(min_price) as min_price, MAX(max_price) as max_price
            FROM {$wpdb->wc_product_meta_lookup}
            WHERE product_id IN (
                $inner_sql
            )
        ";

        $sql = apply_filters('woocommerce_price_filter_sql', $sql, $meta_query_sql, $tax_query_sql);

        return $wpdb->get_row($sql);
    }

    function et__render_price_filter($args) {

        // Round values to nearest 10 by default.
        $step = max( apply_filters( 'woocommerce_price_filter_widget_step', 10 ), 1 );

        // Find min and max price in current result set.
        $prices    = et__get_filtered_price($args);
        $min_price = $prices->min_price;
        $max_price = $prices->max_price;

        // Check to see if we should add taxes to the prices if store are excl tax but display incl.
        $tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

        if ( wc_tax_enabled() && ! wc_prices_include_tax() && 'incl' === $tax_display_mode ) {
            $tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
            $tax_rates = WC_Tax::get_rates( $tax_class );

            if ( $tax_rates ) {
                $min_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min_price, $tax_rates ) );
                $max_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $max_price, $tax_rates ) );
            }
        }

        $min_price = apply_filters( 'woocommerce_price_filter_widget_min_amount', floor( $min_price / $step ) * $step );
        $max_price = apply_filters( 'woocommerce_price_filter_widget_max_amount', ceil( $max_price / $step ) * $step );

        // If both min and max are equal, we don't need a slider.
        if ( $min_price === $max_price ) {
            return false;
        }

        if (($max_price - $min_price) < 10) {
            $min_price = $max_price - 10;
        }

        return array(
            'min_price' => $min_price,
            'max_price' => $max_price,
        );

    }

    function et__get_filtered_product_count($rating, $args) {
        global $wpdb;

        $tax_query  = isset($args['tax_query']) ? $args['tax_query'] : [];
        $meta_query = isset($args['meta_query']) ? $args['meta_query'] : [];
        $post__in   = !empty($args['post__in']) ? array_map('absint', $args['post__in']) : [];

        // Unset current rating filter
        foreach ($tax_query as $key => $query) {
            if (!empty($query['rating_filter'])) {
                unset($tax_query[$key]);
                break;
            }
        }

        // Detect WPML or Polylang
        $is_wpml = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_get_post_language');

        $current_lang = isset($args['lang']) ? $args['lang'] : substr(get_locale(), 0, 2);

        $lang_filter = '';

        if ($is_wpml) {
            $lang_filter = "
                INNER JOIN {$wpdb->prefix}icl_translations icl 
                ON {$wpdb->posts}.ID = icl.element_id 
                AND icl.element_type = 'post_product' 
                AND icl.language_code = '" . esc_sql($current_lang) . "'
            ";
        } elseif ($is_polylang) {
            $lang_filter = "
                INNER JOIN {$wpdb->prefix}term_relationships pll_rel 
                ON {$wpdb->posts}.ID = pll_rel.object_id
                INNER JOIN {$wpdb->prefix}term_taxonomy pll_tax 
                ON pll_rel.term_taxonomy_id = pll_tax.term_taxonomy_id
                INNER JOIN {$wpdb->prefix}terms pll_terms 
                ON pll_tax.term_id = pll_terms.term_id
                AND pll_tax.taxonomy = 'language'
                AND pll_terms.slug = '" . esc_sql($current_lang) . "'
            ";
        }

        // Set new rating filter
        $product_visibility_terms = wc_get_product_visibility_term_ids();
        $tax_query[] = [
            'taxonomy'      => 'product_visibility',
            'field'         => 'term_taxonomy_id',
            'terms'         => $product_visibility_terms['rated-' . $rating],
            'operator'      => 'IN',
            'rating_filter' => true,
        ];

        $meta_query     = new WP_Meta_Query($meta_query);
        $tax_query      = new WP_Tax_Query($tax_query);
        $meta_query_sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');
        $tax_query_sql  = $tax_query->get_sql($wpdb->posts, 'ID');

        $sql  = "SELECT COUNT(DISTINCT {$wpdb->posts}.ID) FROM {$wpdb->posts} ";
        $sql .= $tax_query_sql['join'] . $meta_query_sql['join'];
        $sql .= $lang_filter;
        $sql .= " WHERE {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish' ";
        $sql .= $tax_query_sql['where'] . $meta_query_sql['where'];

        // Add post__in support
        if (!empty($post__in)) {
            $sql .= " AND {$wpdb->posts}.ID IN (" . implode(',', $post__in) . ")";
        }

        if (!empty($args['s'])) {
            $sql .= et__woocommerce_product_search($args['s']);
        }

        return absint($wpdb->get_var($sql)); // WPCS: unprepared SQL ok.
    }

    function et__render_rating_filter($args,$base_url,$url_params){

        $found           = false;
        $url_params_list = array(); // WPCS: input var ok, CSRF ok, sanitization ok.
        $base_link       = remove_query_arg( 'paged', $base_url );

        if (isset( $url_params ) && $url_params) {

            foreach ($url_params as $param) {
                $param = explode('=', $param);
                $url_params_list[$param[0]] = $param[1];
            }

        }

        $rating_filter = isset($url_params_list['rating_filter']) ? [absint($url_params_list['rating_filter'])] : [];

        unset($url_params_list['rating_filter']);

        if (!empty($url_params_list)) {
            foreach ($url_params_list as $key => $value) {
                $base_link = add_query_arg( $key, $value, $base_link);
            }
        }

        $output = '';

        for ( $rating = 5; $rating >= 1; $rating-- ) {
            $count = et__get_filtered_product_count( $rating, $args);

            if ( $count == 0 ) {
                continue;
            }
            $found = true;
            $link  = $base_link;

            if ( in_array( $rating, $rating_filter, true ) ) {
                $link_ratings = implode( ',', array_diff( $rating_filter, array( $rating ) ) );
            } else {
                $link_ratings = implode( ',', array_merge( $rating_filter, array( $rating ) ) );
            }

            $class       = in_array( $rating, $rating_filter, true ) ? 'wc-layered-nav-rating chosen' : 'wc-layered-nav-rating';
            $link        = apply_filters( 'woocommerce_rating_filter_link', $link_ratings ? add_query_arg( 'rating_filter', $link_ratings, $link ) : remove_query_arg( 'rating_filter',$link) );
            $rating_html = wc_get_star_rating_html( $rating );
            $count_html  = wp_kses(
                apply_filters( 'woocommerce_rating_filter_count', "({$count})", $count, $rating ),
                array(
                    'em'     => array(),
                    'span'   => array(),
                    'strong' => array(),
                )
            );

            $output .= sprintf(
                '<li class="%s"><a href="%s"><span class="star-rating">%s</span> %s</a></li>',
                esc_attr( $class ),
                esc_url( $link ),
                $rating_html,
                $count_html
            );

        }

        if ( $found && !empty($output) ) {
            return $output;
        }

        return null;

    }

    function et__search_similar_products($products, $keyword, $threshold = 70) {
        $results = [];

        foreach ($products as $product) {
            if (!isset($product['id'])) continue; // Ensure product has an ID

            $best_score = 0;

            foreach ($product as $key => $value) {
                if (strpos($key, 'search_in_') === 0 && !empty($value)) {
                    similar_text(strtolower($keyword), strtolower($value), $percent);
                    if ($percent > $best_score) {
                        $best_score = $percent; // Track highest match for this product
                    }
                }
            }

            if ($best_score >= $threshold) {
                $results[] = ['id' => $product['id'], 'score' => $best_score];
            }
        }

        // Sort results by highest similarity score (best match first)
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Return only the sorted product IDs
        return array_column($results, 'id');
    }

    function et__get_search_in_keys($products) {
        $search_in_keys = [];

        // Loop through each product
        foreach ($products as $product) {
            // Loop through each key in the product
            foreach ($product as $key => $value) {
                // Check if the key starts with 'search_in'
                if (strpos($key, 'search_in') === 0) {
                    // Add the key to the array if it's not already in the array
                    if (!in_array($key, $search_in_keys)) {
                        $search_in_keys[] = $key;
                    }
                }
            }
        }

        return $search_in_keys;
    }

    function et__product_categories_carousel_template($categories=false){
        
        $columns = [
            'cl-d'   => 6,
            'cl-tbl' => 4,
            'cl-tb'  => 3,
            'cl-mb'  => 2,
            'cl-mbs' => 2
        ];

        $data_columns = array_map(fn($key, $value) => 'data-' . $key . '="' . $value . '"', array_keys($columns), $columns);

        $output = '<div class="swiper-container categories-carousel-container" 
            '.implode(' ', $data_columns).'
            data-arrows-pos="top-right"
            >';
            
            $output .= '<div class="swiper">';

                $output .= '<div class="categories-carousel swiper-wrapper">';

                    if ($categories) {
                        foreach ($categories as $category) {

                            $cat_class = ['swiper-slide','category', $category['slug']];

                            $output .= '<div class="'.implode(' ', $cat_class).'">';
                                $output .= '<a href="'.esc_url($category['link']).'" title="'.esc_attr($category['name']).'">';

                                    if (isset($category['image'])) {
                                        
                                        $output .= '<div class="image-container">';
                                            
                                            $lazy_image    = wp_get_attachment_image_src($category['image_id'],'lazy_img');
                                            
                                            $image         = wp_get_attachment_image_src($category['image_id'],'woocommerce_thumbnail');
                                            $image_src     = $image[0];
                                            $image_width   = $image[1];
                                            $image_height  = $image[2];
                                            $image_alt     = esc_html($category['name']); 

                                            $output .= '<img class="lazy" data-src="'.esc_url($image_src).'" src="'.esc_url($lazy_image[0]).'" width="'.esc_attr($image_width).'" height="'.esc_attr($image_height).'" alt="'.esc_attr($image_alt).'" />';
                                            $output .= '<svg viewBox="0 0 '.esc_attr($image_width).' '.esc_attr($image_width).'"><path d="M0,0H'.$image_width.'V'.$image_width.'H0V0Z" /></svg>';


                                        $output .= '</div>';

                                    }

                                    $output .= '<h3>'.esc_html($category['name']).'</h3>';
                                $output .= '</a>';
                            $output .= '</div>';
                        }
                    }

                $output .= '</div>';

            $output .= '</div>';

           
        $output .= '</div>';

        return $output;
    }

    function et__product_categories_carousel($current_cat = false){

        $mods = et_get_theme_mods();

        $product_categories_carousel = $mods && isset($mods['category_carousel']) && !empty($mods['category_carousel']) ? 1 : 0;

        if ($product_categories_carousel) {

            $is_wpml     = defined('ICL_SITEPRESS_VERSION');
            $is_polylang = function_exists('pll_current_language');

            if ($is_wpml) {
                $current_language = apply_filters('wpml_current_language', null);
            } elseif($is_polylang){
                $current_language = pll_current_language();
            } else {
                $current_language = 'default';
            }

            $category_index = get_transient('et-woo-category-index');

            if ($category_index) {

                $category_index = $category_index[$current_language];

                $queried_object = ($current_cat) ? get_term_by('slug',$current_cat,'product_cat') : get_queried_object();

                if (
                    $queried_object && 
                    isset( $queried_object->taxonomy ) && 
                    $queried_object->taxonomy === 'product_cat'
                ) {

                    $term_id = $queried_object->term_id;

                    $categories = array_filter(array_map(function ($category) use ($term_id) {
                        return ($category['parent_id'] == $term_id) ? $category : null;
                    }, $category_index));


                } else {

                    $categories = array_filter(array_map(function($category) {
                        if ($category['parent_id'] == 0) {
                            return $category;
                        }
                        return null;
                    }, $category_index));

                }

                
                
            } else {

                $queried_object = ($current_cat) ? get_term_by('slug',$current_cat,'product_cat') : get_queried_object();

                if (
                    $queried_object && 
                    isset( $queried_object->taxonomy ) && 
                    $queried_object->taxonomy === 'product_cat'
                ) {

                    $term_id = $queried_object->term_id;

                    $child_categories = get_terms([
                        'taxonomy'   => 'product_cat', // For WooCommerce categories
                        'hide_empty' => true, // Set to true if you want only categories with products
                        'parent'     => $term_id, // Get only children of this category
                        'fields'     => 'ids'
                    ]);

                    // Output the results
                    if (!is_wp_error($child_categories) && !empty($child_categories) && function_exists('et__get_category_data')) {

                        $categories = [];

                        foreach ($child_categories as $category) {
                            $categories[] = et__get_category_data($category);
                        }
                    }

                } else {

                    $categories = get_terms([
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => true,
                        'parent'     => 0,
                        'fields'     => 'ids'
                    ]);

                    if (!is_wp_error($categories)) {

                        $filtered_categories = [];

                        foreach ($categories as $category) {

                            $translated_term_id = $is_wpml 
                            ? apply_filters('wpml_object_id', $category, 'product_cat', true, $current_language) 
                            : ($is_polylang 
                                ? pll_get_term($category, $current_language) 
                                : false);
                            
                            $category = $translated_term_id ? $translated_term_id : $category;
                            $filtered_categories[] = et__get_category_data($category);
                        }

                        $categories = $filtered_categories;

                    }

                }

            }

            if (isset($categories) && !empty($categories)) {
                return et__product_categories_carousel_template($categories);
            }

        }
    }

    function et__product_history_carousel(){

        global $product;

        $is_wpml     = defined('ICL_SITEPRESS_VERSION');
        $is_polylang = function_exists('pll_current_language');

        $recently_viewed = [];

        if (isset($_COOKIE["woocommerce_recently_viewed"]) && !empty($_COOKIE["woocommerce_recently_viewed"])) {
            $recently_viewed = explode('|', $_COOKIE["woocommerce_recently_viewed"]);
        }

        $product_id = $product->get_id();
        $recently_viewed = array_filter($recently_viewed, function($value) use ($product_id) {
            return $value != $product_id;
        });

        $recently_viewed = array_values($recently_viewed);

        if (empty($recently_viewed)) {
            return;
        }
        
        $columns = [
            'cl-d'   => 6,
            'cl-lp'  => 5,
            'cl-tbl' => 3,
            'cl-tb'  => 2,
            'cl-mb'  => 1,
            'cl-mbs' => 1
        ];

        $unique_id = uniqid( 'related-products-swiper-' );

        $data_columns = array_map(fn($key, $value) => 'data-' . $key . '="' . $value . '"', array_keys($columns), $columns); ?>
        <section class="products history">
            <h2><?php echo esc_html__("Recently viewed products","enovathemes-addons"); ?></h2>

            <div
                id="<?php echo esc_attr( $unique_id ); ?>"
                class="swiper-container related-products-container grid" 
                <?php echo implode(' ', $data_columns) ?>
                data-arrows-pos="top"
            >
                
                <div class="swiper">

                    <ul class="products swiper-wrapper">
                            <?php

                                $args = array(
                                    'post_type'           => 'product',
                                    'post_status'         => 'publish',
                                    'ignore_sticky_posts' => 1,
                                    'posts_per_page'      => 10,
                                    'fields'              => 'ids',
                                    'post__in'            => $recently_viewed
                                );

                                if ($is_wpml) {
                                    $args['suppress_filters'] = false;
                                } elseif($is_polylang){
                                    $current_language = pll_current_language();
                                    $args['lang'] = $current_language;
                                }

                                $WP_Query = new WP_Query($args);

                                if($WP_Query->have_posts()){
                                    while ($WP_Query->have_posts() ) {
                                    $WP_Query->the_post();
                                        wc_get_template_part('content', 'product'); // Load the product template
                                    }
                                }

                            ?>

                    </ul>

                </div>

            </div>
        </section>
    <?php }

    /* Cart
    ---------------*/

        function et__cart(){
            if (class_exists('Woocommerce')) {
            
                $mods                 = et_get_theme_mods();
                $product_catalog_mode = $mods && isset($mods['product_catalog_mode']) && !empty($mods['product_catalog_mode']) ? true : false;

                if($product_catalog_mode == false) {
                    echo '<aside class="et__cart">';
                        echo '<a href="#" class="et__cart-toggle-remove" title="'.esc_attr__('Close the cart','enovathemes-addons').'"></a>';
                        echo '<h3>'.esc_html__('My cart','enovathemes-addons').'</h3>';
                        echo '<div class="side-cart-content">';
                            get_sidebar('shop-cart-before-mini-cart');
                                echo et__get_the_widget( 'WC_Widget_Cart', 'title=' );
                            get_sidebar('shop-cart-after-buttons');
                        echo '</div>';
                    echo '</aside>';
                    echo '<div class="et__cart-shadow"></div>';
                }


            }
        }

    /* Variation swatches
    ---------------*/

        function et__variation_display($variation){

            $output = '';

            if (isset($variation['options'])) {

                if ($variation['display'] == 'select') {

                    $variation_item_class = [
                        'variation-item-opt',
                        esc_attr($variation['display'])
                    ];

                    $output .= '<select class="'.implode(' ', $variation_item_class).'">';

                        $output .= '<option 
                            value="">';
                            $output .= esc_html__("Choose an option","enovathemes-addons");
                        $output .= '</option>';

                        if (isset($variation['taxonomy'])) {
                            foreach ($variation['options'] as $term_id) {

                                $term      = get_term_by('id',$term_id,$variation['taxonomy']);
                                $attribute = str_replace('pa_', '', $variation['taxonomy']);

                                if ($term) {

                                    $output .= '<option 
                                        value="'.esc_attr($term->slug).'">';
                                        $output .= esc_html($term->name);
                                    $output .= '</option>';

                                }

                            }
                        } else {
                            foreach ($variation['options'] as $term_id) {

                                $output .= '<option 
                                    value="'.esc_attr($term_id).'">';
                                    $output .= esc_html($term_id);
                                $output .= '</option>';

                            }
                        }

                    $output .= '</select>';

                } else {

                    foreach ($variation['options'] as $term_id) {

                        $output_inner = "";

                        $term      = get_term_by('id',$term_id,$variation['taxonomy']);
                        $attribute = str_replace('pa_', '', $variation['taxonomy']);

                        if ($term) {

                            $variation_item_class = [
                                'variation-item-opt',
                                esc_attr($variation['display'])
                            ];

                            if ($variation['display'] == 'image') {

                                $image_url = get_term_meta($term->term_id, 'enova_pa_' . $attribute . '_image', true);

                                if ($image_url) {
                                    $output_inner = '<img width="56" src="'.esc_url($image_url).'" alt="attribute-term-'.$term->term_id.'-image" />';
                                } else {
                                    $variation_item_class[] = 'empty';
                                }

                            } elseif($variation['display'] == 'color'){

                                $color = get_term_meta($term->term_id, 'enova_pa_' . $attribute . '_color', true);
                                    
                                if ($color) {

                                    $style = et__is_light_color($color) ? 'background:'.esc_attr($color).';border:1px solid #ccc;' : 'background:'.esc_attr($color).';';
                                    $output_inner = '<span style="'.esc_attr($style).'"></span>';
                                
                                } else {
                                    $variation_item_class[] = 'empty';
                                }

                            }

                            $output .= '<a 
                                href="#" 
                                class="'.implode(' ', $variation_item_class).'" 
                                title="'.esc_attr($term->name).'" 
                                data-value="'.esc_attr($term->slug).'">';
                                $output .= esc_html($term->name);
                                $output .= $output_inner;
                            $output .= '</a>';

                        }

                    }

                }
             
            }

            if (!empty($output)) {
                return $output;
            }
            
        }

        add_action( 'woocommerce_single_product_summary', 'et__single_product_variation_swatches', 21 );
        function et__single_product_variation_swatches(){

            global $product;

            if ($product && $product->is_type('variable')) {

                $variation_attributes = $product->get_variation_attributes();
                $attributes           = $product->get_attributes();

                if (!empty($variation_attributes)) {

                    echo '<ul class="et__variation-swatches">';

                        foreach ($variation_attributes as $attribute_slug => $values) {
                            
                            $variation = [];

                            $attribute_label = wc_attribute_label($attribute_slug, $product);

                            $attribute_id = '';
                            
                            if (
                                isset($attributes[$attribute_slug]) && 
                                $attributes[$attribute_slug]->is_taxonomy()
                            ) {

                                $attribute_display_slug = $attribute_slug;

                                $display_type = get_option('attribute_display_type_' . $attributes[$attribute_slug]['id'], 'select');

                                $variation['display'] = $display_type;
                                $variation['options'] = $attributes[$attribute_slug]['options'];
                                $variation['taxonomy']= $attributes[$attribute_slug]['name'];

                                $attribute_id = $variation['taxonomy'];

                            } else {

                                $attribute_display_slug = sanitize_title($attribute_label); // Custom attribute (generate slug)
                                
                                $variation['display'] = 'select';
                                $variation['options'] = $values;

                                $attribute_id = $attribute_display_slug;

                            }

                            $variation_class = ['variation-item', $attribute_display_slug];

                            echo '<li id="attr-'.esc_attr($attribute_id).'" class="'.implode(' ', $variation_class).'">';
                                echo '<span class="variation-label">'.esc_html($attribute_label).':</span>';
                                echo et__variation_display($variation);
                            echo '</li>';

                        }

                        echo '<li class="et__clear_variation-swatches">';
                            echo '<a href="#" class="clear">'.esc_html__("Clear","enovathemes-addons").'</a>';
                        echo '</li>';

                    echo '</ul>';

                }

            }


        }

    /* Disocunt output
    ---------------*/

        function et__discout_html($discount_percentage,$discount_amount){
            echo '<span class="savings-wrapper">';

                echo '<span class="savings-discount">'.esc_html('-'.$discount_percentage).'</span>';

                echo sprintf(
                    '<span class="savings">%s&nbsp;%s</span>',
                    '<span class="savings-label">'.esc_html__('You save', 'enovathemes-addons').'</span>',
                    '<span class="savings-price">'.wc_price($discount_amount).'</span>',
                );

            echo '</span>';
        }

        function et__get_similar_products_by_category($limit = 4) {
            if (!is_product()) {
                return [];
            }

            $current_product_id = get_the_ID();
            $terms = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));

            if (empty($terms)) {
                return [];
            }

            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => $limit,
                'post__not_in'   => array($current_product_id),
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $terms,
                    ),
                ),
                'fields' => 'ids',
            );

            $query = new WP_Query($args);

            if ($query->posts) {
                return $query->posts; // Returns an array of product IDs
            }

            return [];

        }

?>