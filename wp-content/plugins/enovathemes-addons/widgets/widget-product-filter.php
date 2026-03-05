<?php

    add_action('widgets_init', 'register_product_filter_widget');
    function register_product_filter_widget(){
        register_widget( 'Enovathemes_Addons_WP_Product_Filter' );
    }

    class Enovathemes_Addons_WP_Product_Filter extends WP_Widget {

        public function __construct() {
            parent::__construct(
                'product_filter_widget',
                esc_html__('* Product ajax filter', 'enovathemes-addons'),
                array( 'description' => esc_html__('Product ajax filter', 'enovathemes-addons'))
            );
        }

        public function widget( $args, $instance) { global $wpdb;

            if ( WC()->query->get_main_query() == null || ! WC()->query->get_main_query()->post_count ) {
                return;
            }

            extract($args);

            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_script('widget-product-filter');

            $title  = isset($instance['title']) ? apply_filters( 'widget_title', $instance['title'] ) : '';
            $atts   = isset($instance['atts']) ? esc_attr($instance['atts']) : '';
            $cache  = (class_exists('SitePress') || function_exists('pll_the_languages')) ? false : true;

            echo $before_widget;

                // if ( ! empty( $title ) ){echo $before_title . $title . $after_title;}

                $shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
                if ('' === get_option( 'permalink_structure' )) {
                    $shop_link = get_home_url().'?post_type=product';
                }

                ?>

                <div class="product-filter" data-shop="<?php echo esc_url($shop_link); ?>">
                    <form name="product-filter" class="product-filter" method="POST">

                        <?php

                            if (!empty($atts)) {

                                $atts        = json_decode( html_entity_decode( stripslashes ($atts)),true );
                                $atts_filter = array();
                                $attributes  = enovathemes_addons_build_filter_attributes($cache);


                                foreach ($atts as $att) {

                                    $id       = $att['attr'];
                                    $display  = array_key_exists('display',$att) ? $att['display'] : 'default';
                                    $columns  = array_key_exists('column',$att) ? $att['column'] : 2;
                                    $category = array_key_exists('category',$att) ? $att['category'] : false;
                                    $children = array_key_exists('children',$att) ? $att['children'] : false;
                                    $lock     = array_key_exists('lock',$att) ? $att['lock'] : 'false';
                                    $category_hide = array_key_exists('category-hide',$att) ? $att['category-hide'] : false;
                                    $children_hide = array_key_exists('children-hide',$att) ? $att['children-hide'] : false;

                                    if ($id == 'cat' || $id == 'price' || $id == 'rating') {

                                        if ($id == 'cat') {$id = 'ca';}

                                        $push = array();

                                        if (empty($display)) {
                                            $display = 'default';
                                        }

                                        $push['name']    = $id;
                                        $push['display'] = $display;
                                        $push['columns'] = $columns;
                                        $push['lock']    = $lock;

                                        array_push($atts_filter, $push);

                                    } else {
                                        if (!empty($attributes) && !is_wp_error($attributes)) {

                                            if (array_key_exists($id, $attributes)) {
                                                $attributes[$id]['display'] = $display;
                                                $attributes[$id]['columns'] = $columns;
                                                $attributes[$id]['category'] = $category;
                                                $attributes[$id]['children'] = $children;
                                                $attributes[$id]['lock'] = $lock;
                                                $attributes[$id]['category-hide'] = $category_hide;
                                                $attributes[$id]['children-hide'] = $children_hide;
                                                array_push($atts_filter, $attributes[$id]);
                                            }
                                        }
                                    }

                                }

                                if (!empty($atts_filter)){

                                    $output       = '';
                                    $term_posts   = false;
                                    $current_term = get_queried_object();
                                    $search       = (isset($_GET["s"]) && !empty($_GET["s"])) ? $_GET["s"] : false;

                                    $args = array(
                                        'post_type'           => 'product',
                                        'post_status'         => 'publish',
                                        'ignore_sticky_posts' => 0,
                                        'orderby'             => 'menu_order title',
                                        'order'               => 'ASC',
                                        'posts_per_page'      => -1,
                                        'fields'              => 'ids',
                                    );

                                    if ($search) {
                                        $args['s'] = $search;
                                    }

                                    $tax_query = $vehicle_attributes = array();

                                    if (
                                        $current_term && 
                                        isset($current_term->taxonomy) && 
                                        ($current_term->taxonomy == 'product_cat' || $current_term->taxonomy == 'product_tag')
                                    ) {

                                        $tax_query[] = array(
                                            'taxonomy' => $current_term->taxonomy,
                                            'field'    => 'slug',
                                            'terms'    => $current_term->slug,
                                            'operator' => 'IN'
                                        );

                                    }

                                    $vehicle_params = apply_filters( 'vehicle_params','');

                                    foreach ($_GET as $key => $value) {

                                        $key = ($key == 'yr') ? 'year' : $key;

                                        if (in_array($key, $vehicle_params)) {
                                            $vehicle_attributes[$key] = urldecode($value);
                                        }
                                    }


                                    if (isset($_GET['vin']) && !empty($_GET['vin'])) {
                                        $vehicle_attributes = enovathemes_addons_vin_decoder($_GET['vin']);
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

                                    if (!empty($tax_query)) {
                                        $args['tax_query'] = array($tax_query);
                                    } elseif($search == false) {
                                        $args = false;
                                    }

                                    if ($args) {

                                        $query_results  = new WP_Query($args);

                                        if (!empty($query_results)) {

                                            $term_posts  = $query_results->posts;

                                            foreach ($atts_filter as $attribute){
                                                $name = $attribute['name'];
                                                if (!in_array($name, array('price','rating'))) {
                                                    $$name = array();
                                                }
                                            }

                                            if ($term_posts) {
                                                foreach ($term_posts as $id) {

                                                    foreach ($atts_filter as $attribute){

                                                        $name = $attribute['name'];

                                                        if (!in_array($name, array('price','rating'))) {
                                                            
                                                            $post_terms = ($name == 'ca') ? 'product_cat' : 'pa_'.$name;

                                                            $product_terms = wp_get_post_terms($id,$post_terms,array( 'fields' => 'all' ));
                                                            if (!is_wp_error($product_terms) && !empty($product_terms)) {
                                                                foreach ($product_terms as $term => $term_opt) {
                                                                    if ($name == 'ca') {
                                                                        ${$name}[] = $term_opt->term_id;
                                                                    } else {
                                                                        ${$name}[$term_opt->term_id] = array($term_opt->name,$term_opt->slug);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                }
                                            }

                                        }

                                        wp_reset_postdata();


                                    }


                                    foreach ($atts_filter as $attribute){
                                        switch ($attribute['name']){


                                            case 'ca':

                                                $name = $attribute['name'];

                                                $include = (isset($$name) && !empty($$name)) ? array_unique($$name,SORT_REGULAR) : false;
                                                $output .= enovathemes_addons_render_category_filter_attribute($attribute,$cache,$include);

                                            break;
                                            case 'price':

                                                $output .= enovathemes_addons_render_price_filter_attribute($term_posts);

                                            break;
                                            case 'rating':

                                                $output .= enovathemes_addons_render_rating_filter_attribute($term_posts);

                                            break;
                                            default:

                                                $name = $attribute['name'];

                                                $include = (isset($$name) && !empty($$name)) ? array_unique($$name,SORT_REGULAR) : false;
                                                $output .= enovathemes_addons_render_attribute_filter_attribute($attribute,$include);

                                            break;
                                        }
                                    }

                                    if (!empty($output)) {
                                        echo $output;
                                        if ($include == false) {
                                            set_transient( 'enovathemes-product-filter', $output, apply_filters( 'null_product_filter_cache_time', 0 ) );
                                        }
                                    }
                                }

                            }

                        ?>
                    </form>
                    <a href="#" class="clear-all-attribute"><?php echo esc_html__("Reset all","enovathemes-addons"); ?></a>
                    <a href="#" class="reload-all-attribute"></a>
                </div>

            <?php echo $after_widget;
        }

        public function form( $instance ) {

            $defaults = array(
                'title' => esc_html__('Product filter', 'enovathemes-addons'),
                'atts'  => '',
            );

            $instance = wp_parse_args((array) $instance, $defaults);

            $categories = get_product_categories_hierarchy(false);

            ?>

            <div id="<?php echo esc_attr($this->get_field_id( 'widget_id' )); ?>" class="widget-product-filter">

                <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo esc_html__( 'Title:', 'enovathemes-addons' ); ?></label>
                    <input class="widefat <?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
                </p>

                <div class="sortable-droppable-attributes">

                    <?php

                    $attributes  = enovathemes_addons_build_filter_attributes();

                    $options_cat  = (!empty($categories) && !is_wp_error($categories)) ? '<label>'.esc_html__( 'Limit to category', 'enovathemes-addons' ).'<select class="cats" multiple><option value="">'.esc_html__( 'All', 'enovathemes-addons' ).'</option>'.list_taxonomy_hierarchy_no_instance($categories,'','default').'</select></label>' : '';
                    $options_cat .= '<label class="include"><input name="children" type="checkbox" value="true" />'.esc_html__( 'Include child categories?', 'enovathemes-addons' ).'</label><br/><br/>';

                    $options_cat_hide  = (!empty($categories) && !is_wp_error($categories)) ? '<label>'.esc_html__( 'Hide on category', 'enovathemes-addons' ).'<select class="cats-hide" multiple><option value="">'.esc_html__( 'All', 'enovathemes-addons' ).'</option>'.list_taxonomy_hierarchy_no_instance($categories,'','default').'</select></label>' : '';
                    $options_cat_hide .= '<label class="include"><input name="children-hide" type="checkbox" value="true" />'.esc_html__( 'Include child categories?', 'enovathemes-addons' ).'</label><br/><br/>';

                    $options = '<span class="remove" title="'.esc_html__( 'Remove', 'enovathemes-addons' ).'"></span>
                    <span class="display" title="'.esc_html__( 'Display type', 'enovathemes-addons' ).'"></span>
                    <div>
                        '.$options_cat.'
                        '.$options_cat_hide.'
                        <label>'.esc_html__( 'Display type', 'enovathemes-addons' ).'
                        <select class="dis">
                            <option value="select">'.esc_html__( 'Select', 'enovathemes-addons' ).'</option>
                            <option value="list">'.esc_html__( 'List', 'enovathemes-addons' ).'</option>
                            <option value="image">'.esc_html__( 'Image', 'enovathemes-addons' ).'</option>
                            <option value="label">'.esc_html__( 'Label', 'enovathemes-addons' ).'</option>
                            <option value="col">'.esc_html__( 'Color', 'enovathemes-addons' ).'</option>
                            <option value="slider">'.esc_html__( 'Slider', 'enovathemes-addons' ).'</option>
                        </select></label>
                        <label class="image-on">'.esc_html__( 'Columns', 'enovathemes-addons' ).'
                        <select>
                            <option value="2">2</option>
                        </select></label>
                        <p>'.esc_html__( "For color, image display types make sure you set the correct type from this attribute settings, found under products / attributes. For slider display type, make sure your attribute is numeric", "enovathemes-addons" ).'</p>
                        <br><label class="lock"><input name="lock" type="checkbox" value="true"'.esc_html__("Lock this attribute?","enovathemes-addons").'</label>
                        <p>'.esc_html__( "If active, filter results will not affect attribute data", "enovathemes-addons" ).'</p>
                   </div>';

                    $options2 = '<span class="remove" title="'.esc_html__( 'Remove', 'enovathemes-addons' ).'"></span>
                    <span class="display" title="'.esc_html__( 'Display type', 'enovathemes-addons' ).'"></span>
                    <div>
                        <label>'.esc_html__( 'Display type', 'enovathemes-addons' ).'
                        <select class="dis">
                            <option value="select">'.esc_html__( 'Select', 'enovathemes-addons' ).'</option>
                            <option value="list">'.esc_html__( 'List', 'enovathemes-addons' ).'</option>
                            <option value="image">'.esc_html__( 'Image', 'enovathemes-addons' ).'</option>
                            <option value="image-list">'.esc_html__( 'Image list', 'enovathemes-addons' ).'</option>
                        </select></label>
                        <label class="image-on">'.esc_html__( 'Columns', 'enovathemes-addons' ).'
                        <select>
                            <option value="2">2</option>
                        </select></label>
                        <p>'.esc_html__( "For image display type make sure you set the product category image from the Products / Categories", "enovathemes-addons" ).'</p>
                        <br><label class="lock"><input name="lock" type="checkbox" value="true"'.esc_html__("Lock this attribute?","enovathemes-addons").'</label>
                        <p>'.esc_html__( "If active, filter results will not affect attribute data", "enovathemes-addons" ).'</p>
                    </div>';

                    ?>

                    <?php if ($attributes && !is_wp_error($attributes)): ?>
                        <h4><?php echo esc_html__( 'Available filter options', 'enovathemes-addons' ); ?></h4>
                        <ul class="draggable">
                            <li data-attribute='{"attr":"cat","label":"<?php echo esc_html__( 'Category', 'enovathemes-addons' ); ?>"}' data-title="<?php echo esc_html__( 'Category', 'enovathemes-addons' ); ?>" class="draggable-item">
                                <?php echo esc_html__( 'Category', 'enovathemes-addons' ); ?>
                                <?php echo $options2; ?>
                            </li>
                            <li data-attribute='{"attr":"price","label":"<?php echo esc_html__( 'Price', 'enovathemes-addons' ); ?>"}' data-title="<?php echo esc_html__( 'Price', 'enovathemes-addons' ); ?>" class="draggable-item">
                                <?php echo esc_html__( 'Price', 'enovathemes-addons' ); ?>
                                <span class="remove" title="<?php echo esc_html__( 'Remove attribute', 'enovathemes-addons' ); ?>"></span>
                            </li>
                            <li data-attribute='{"attr":"rating","label":"<?php echo esc_html__( 'Rating', 'enovathemes-addons' ); ?>"}' data-title="<?php echo esc_html__( 'Rating', 'enovathemes-addons' ); ?>" class="draggable-item">
                                <?php echo esc_html__( 'Rating', 'enovathemes-addons' ); ?>
                                <span class="remove" title="<?php echo esc_html__( 'Remove attribute', 'enovathemes-addons' ); ?>"></span>
                            </li>
                            <?php foreach ($attributes as $attribute => $data): ?>
                                <li data-attribute='{"attr":"<?php echo esc_attr($attribute); ?>","label":"<?php echo esc_attr($data['label']); ?>"}' data-title="<?php echo esc_attr($data['label']); ?>" class="draggable-item">
                                    <?php echo esc_html($data['label']); ?>
                                    <?php echo $options; ?>
                                </li>
                            <?php endforeach ?>
                        </ul>
                        <h4><?php echo esc_html__( 'Drop here filter attributes', 'enovathemes-addons' ); ?></h4>
                        <ul class="sortable"></ul>
                    <?php endif ?>

                    <input class="atts" type="hidden" id="<?php echo $this->get_field_id('atts'); ?>" name="<?php echo $this->get_field_name('atts'); ?>" value="<?php echo esc_attr( $instance['atts'] ); ?>" />

                </div>

            </div>

            <?php
        }

        public function update( $new_instance, $old_instance ) {

            delete_transient('enovathemes-product-filter');

            $instance = $old_instance;
            $instance['title'] = strip_tags( $new_instance['title'] );
            $instance['atts']  = strip_tags( $new_instance['atts'] );
            return $instance;
        }

    }

?>