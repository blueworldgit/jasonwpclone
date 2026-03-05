<?php 
	
	defined( 'ABSPATH' ) || exit;
	
	add_action('widgets_init', 'enovathemes_addons_register_banner_widget');
	function enovathemes_addons_register_banner_widget(){
		register_widget( 'Enovathemes_Addons_WP_Widget_Banner' );
	} 

	class Enovathemes_Addons_WP_Widget_Banner extends WP_Widget {

		public function __construct() {
			parent::__construct(
				'banner',
				esc_html__('* Banner', 'enovathemes-addons'),
				array( 'description' => esc_html__('Banner', 'enovathemes-addons'))
			);
		}

		public function widget( $args, $instance) {

			extract($args);

			$title              = isset($instance['atts']) ? apply_filters( 'widget_title', $instance['title'] ) : '';
			$category = isset($instance['category']) ? esc_attr($instance['category']) : "";
			$banner   = isset($instance['banner']) ? esc_attr($instance['banner']) : "";
			$children = (isset($instance['children']) && $instance['children'] == "true") ? true : false;
			$shop     = (isset($instance['shop']) && $instance['shop'] == "true") ? true : false;

			if ($banner) {

                $pluginElementor = \Elementor\Plugin::instance();

				$banners = enovathemes_addons_banners();

				if (!is_wp_error($banners) && $banner != "none") {

					$current_banner = $banners[$banner];


					if (is_array($current_banner)) {

                    	$content = (is_plugin_active( 'elementor/elementor.php' )) ? $pluginElementor->frontend->get_builder_content($banner,false) : get_the_content();

						$output = $before_widget;
			                $output .= '<div class="banner-content">'.do_shortcode($content).'</div>';
						$output .= $after_widget;

						if ($shop == true && is_shop()) {
							echo $output;
						} else {
							if ( $category ) {
								if ($children == true) {
									echo (enovathemes_addons_is_or_descendant_tax($category,'product_cat')) ? $output : '';
								} else {
									echo (is_tax('product_cat',$category)) ? $output : '';
								}
							} else {
								echo $output;
							}
						}

					}

				}

			}
		}

	 	public function form( $instance ) {

	 		$defaults = array(
	 			'title'    => esc_html__('Banner', 'enovathemes-addons'),
	 			'banner'   => '',
	 			'category' => '',
	 			'children' => '',
	 			'shop'     => '',
	 		);

	 		$instance   = wp_parse_args((array) $instance, $defaults);
			$banners    = enovathemes_addons_banners();
			$categories = get_product_categories_hierarchy();
	 		$title      = $instance['title'];

			?>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'enovathemes-addons' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</label>
			</p>

			<?php if (!is_wp_error($banners)): ?>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'banner' ) ); ?>">
						<?php echo esc_html__( 'Banner:', 'enovathemes-addons' ); ?>
					</label>
					<select class="widefat custom-select" id="<?php echo $this->get_field_id( 'banner' ); ?>" name="<?php echo $this->get_field_name( 'banner' ); ?>" >
						<option value="" <?php selected( $instance['banner'],'' ); ?>><?php echo esc_html__("- Select banner -","enovathemes-addons"); ?></option>
						<?php foreach ($banners as $banner): ?>
							<option value="<?php echo $banner['id']; ?>" <?php selected( $instance['banner'], $banner['id'] ); ?>><?php echo $banner['title']; ?></option>
						<?php endforeach ?>
					</select>
				</p>
			<?php endif ?>

			<?php if (!is_wp_error($categories)): ?>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>">
						<?php echo esc_html__( 'Show only for selected category:', 'enovathemes-addons' ); ?>
					</label>
					<select class="widefat custom-select" id="<?php echo $this->get_field_id( 'category' ); ?>" name="<?php echo $this->get_field_name( 'category' ); ?>" >
						<option value="" <?php selected( $instance['category'],'' ); ?>><?php echo esc_html__("- Select category -","enovathemes-addons"); ?></option>
						<?php list_taxonomy_hierarchy_no_instance_widget( $categories, $instance); ?>
					</select>
				</p>

				<p>
					<input class="checkbox" type="checkbox" <?php checked($instance['children'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('children'); ?>" name="<?php echo $this->get_field_name('children'); ?>" /> 
					<label for="<?php echo $this->get_field_id('children'); ?>"><?php echo esc_html__( 'Including child categories?', 'enovathemes-addons' ); ?></label>
				</p>

				<p>
					<input class="checkbox" type="checkbox" <?php checked($instance['shop'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('shop'); ?>" name="<?php echo $this->get_field_name('shop'); ?>" /> 
					<label for="<?php echo $this->get_field_id('shop'); ?>"><?php echo esc_html__( 'Limit to shop page only?', 'enovathemes-addons' ); ?></label>
				</p>
			<?php endif ?>

		<?php }

		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title']    = strip_tags( $new_instance['title'] );
			$instance['banner']   = esc_attr($new_instance['banner']);
			$instance['category'] = esc_attr($new_instance['category']);
			$instance['children'] = esc_attr($new_instance['children']);
			$instance['shop']     = esc_attr($new_instance['shop']);
			return $instance;
		}

	}

?>