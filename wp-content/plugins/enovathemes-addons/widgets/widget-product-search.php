<?php

    add_action('widgets_init', 'register_product_search_widget');
    function register_product_search_widget(){
    	register_widget( 'Enovathemes_Addons_WP_Product_Search' );
    }

    class Enovathemes_Addons_WP_Product_Search extends WP_Widget {

    	public function __construct() {
    		parent::__construct(
    			'product_search_widget',
    			esc_html__('* Product ajax search', 'enovathemes-addons'),
    			array( 'description' => esc_html__('Product ajax search', 'enovathemes-addons'))
    		);
    	}

    	public function widget( $args, $instance) {

    		extract($args);

    		$title          = isset($instance['atts']) ? apply_filters( 'widget_title', $instance['title'] ) : '';
            $category       = $instance['category'] ? $instance['category'] : 'false';
            $in_tag         = $instance['in_tag'] ? $instance['in_tag'] : 'false';
            $in_attr        = $instance['in_attr'] ? $instance['in_attr'] : 'false';
            $SKU            = $instance['SKU'] ? $instance['SKU'] : 'false';
            $description    = $instance['description'] ? $instance['description'] : 'false';
            $cache          = (class_exists('SitePress') || function_exists('pll_the_languages')) ? false : true;
           
    		$shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
            if ('' === get_option( 'permalink_structure' )) {
                $shop_link = get_home_url().'?post_type=product';
            }

            echo $before_widget;

    			if ( ! empty( $title ) ){echo $before_title . $title . $after_title;}

                $placeholder = esc_html__( 'Enter a keyword or product SKU', 'enovathemes-addons' );

                $data = array();

                $data[] = 'data-sku="'.esc_attr($SKU).'"';
                $data[] = 'data-description="'.esc_attr($description).'"';
                $data[] = 'data-tag="'.esc_attr($in_tag).'"';
                $data[] = 'data-attr="'.esc_attr($in_attr).'"';

                ?>

    			<div class="product-search hide-category-<?php echo esc_attr($category); ?>">
    				<form name="product-search" method="POST" <?php echo implode(' ', $data); ?>>
                        <?php $categories = get_product_categories_hierarchy($cache); ?>
                        <?php if ($category == "false" && !is_wp_error($categories) && !empty($categories)): ?>
                            <select name="category" class="category">
                                <option class="default" value=""><?php echo esc_html__( 'Select category', 'enovathemes-addons' ); ?></option>
                                <?php echo list_taxonomy_hierarchy_no_instance( $categories); ?>
                            </select>
                        <?php endif ?>
                        <div class="search-wrapper">
                            <input type="search" name="search" class="search" placeholder="<?php echo $placeholder; ?>" value="">
                            <span class="loading"></span>
                            <div class="search-results"></div>
                        </div>
                        <input data-shop="<?php echo esc_url($shop_link); ?>" type="submit" value="<?php echo esc_html__( 'Search', 'enovathemes-addons' ); ?>" class="small et-search-button et-button" />
                        <div class="input-after"></div>
                        <div class="search-results"></div>
    	            </form>
        		</div>

    		<?php echo $after_widget;
    	}

     	public function form( $instance ) {

     		$defaults = array(
                'title'       => esc_html__('Product search', 'enovathemes-addons'),
                'category'    => 'false',
                'SKU'         => 'false',
                'description' => 'false',
                'in_tag'      => 'false',
     			'in_attr'     => 'false',
     		);

     		$instance = wp_parse_args((array) $instance, $defaults);

    		?>

    		<div id="<?php echo esc_attr($this->get_field_id( 'widget_id' )); ?>">

    			<p>
    				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo esc_html__( 'Title:', 'enovathemes-addons' ); ?></label>
    				<input class="widefat <?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
    			</p>


                <p class="et-clearfix label-right">
                    <label for="<?php echo $this->get_field_id('category'); ?>"><?php echo esc_html__( 'Hide category select?', 'enovathemes-addons' ); ?>
                        <input class="checkbox" type="checkbox" <?php checked($instance['category'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>" /> 
                    </label>
                </p>

                <p class="et-clearfix label-right">
                    <label for="<?php echo $this->get_field_id('SKU'); ?>"><?php echo esc_html__( 'Search in SKU?', 'enovathemes-addons' ); ?>
                        <input class="checkbox" type="checkbox" <?php checked($instance['SKU'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('SKU'); ?>" name="<?php echo $this->get_field_name('SKU'); ?>" /> 
                    </label>
                </p>

                <p class="et-clearfix label-right">
                    <label for="<?php echo $this->get_field_id('description'); ?>"><?php echo esc_html__( 'Search in product description', 'enovathemes-addons' ); ?>
                        <input class="checkbox" type="checkbox" <?php checked($instance['description'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>" /> 
                    </label>
                </p>

    		</div>

    		<?php
    	}

    	public function update( $new_instance, $old_instance ) {
    		$instance = $old_instance;
            $instance['title']       = strip_tags( $new_instance['title'] );
            $instance['category']    = strip_tags( $new_instance['category'] );
            $instance['SKU']         = strip_tags( $new_instance['SKU'] );
            $instance['description'] = strip_tags( $new_instance['description'] );
            $instance['in_attr']     = strip_tags( $new_instance['in_attr'] );
    		$instance['in_tag']      = strip_tags( $new_instance['in_tag'] );
    		return $instance;
    	}

    }

?>