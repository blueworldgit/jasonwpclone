<?php 
	
	add_action('widgets_init', 'enovathemes_addons_register_facebook_like_widget');
	function enovathemes_addons_register_facebook_like_widget(){
		register_widget( 'Enovathemes_Addons_WP_Widget_Facebook' );
	} 

	class Enovathemes_Addons_WP_Widget_Facebook extends WP_Widget {

		public function __construct() {
			parent::__construct(
				'facebook',
				esc_html__('* Facebook', 'enovathemes-addons'),
				array( 'description' => esc_html__('Facebook widget', 'enovathemes-addons'))
			);
		}

		public function widget( $args, $instance ) {

			extract($args);

			$title              = isset($instance['atts']) ? apply_filters( 'widget_title', $instance['title'] ) : '';
			
			$app_id 	   = (isset($instance['app_id'])) ? esc_attr($instance['app_id']) : "";
			$language_code = (isset($instance['language_code'])) ? esc_attr($instance['language_code']) : "en_US";
			
			$href   = (isset($instance['href'])) ? esc_url($instance['href']) : "";
			$width  = (isset($instance['width'])) ? esc_attr($instance['width']) : "";
			$height = (isset($instance['height'])) ? esc_attr($instance['height']) : "";
			
			$tabs   = array();

			$timeline = $instance['timeline'] ? 'timeline' : '';
			$messages = $instance['messages'] ? 'messages' : '';
			$events   = $instance['events'] ? 'events' : '';

			if ($instance['timeline']) {array_push($tabs, 'timeline');}
			if ($instance['messages']) {array_push($tabs, 'messages');}
			if ($instance['events']) {array_push($tabs, 'events');}

			$tabs = implode(',', $tabs);

			$hide_cover 		   = $instance['hide_cover'] ? "true" : "false";
			$show_facepile 		   = $instance['show_facepile'] ? "true" : "false";
			$small_header 		   = $instance['small_header'] ? "true" : "false";
			$adapt_container_width = $instance['adapt_container_width'] ? "true" : "false";

			$data_array = array();

			if (!empty($href)) {array_push($data_array, 'data-href="'.$href.'"');}
			if (!empty($width)) {array_push($data_array, 'data-width="'.$width.'"');}
			if (!empty($height)) {array_push($data_array, 'data-height="'.$height.'"');}
			if (!empty($tabs)) {array_push($data_array, 'data-tabs="'.$tabs.'"');}

			if ($hide_cover == "true") {array_push($data_array, 'data-hide-cover="true"');}
			if ($show_facepile == "true") {array_push($data_array, 'data-show-facepile="true"');}
			if ($small_header == "true") {array_push($data_array, 'data-small-header="true"');}
			if ($adapt_container_width == "true") {array_push($data_array, 'data-adapt-container-width="true"');}

			echo $before_widget;

				if ( ! empty( $title ) ){echo $before_title . $title . $after_title;}
			
				if($href && $app_id): ?>
					<div id="fb-root"></div>
					<script>
						(function(d, s, id) {
							var js, fjs = d.getElementsByTagName(s)[0];
							if (d.getElementById(id)) return;
							js = d.createElement(s); js.id = id;
							js.src = 'https://connect.facebook.net/<?php echo $language_code; ?>/sdk.js#xfbml=1&version=v3.1&appId=<?php echo $app_id; ?>&autoLogAppEvents=1';
							fjs.parentNode.insertBefore(js, fjs);
						}(document, 'script', 'facebook-jssdk'));
					</script>
					<div class="fb-page" <?php echo implode(' ', $data_array) ?>></div>
				<?php endif;

			echo $after_widget;
		}

	 	public function form( $instance ) {

	 		$defaults = array(
	 			'title'         	    => esc_html__('Find us on Facebook', 'enovathemes-addons'),
	 			'app_id'        	    => '',
				'language_code' 	    => 'en_US',
				'href'          	    => '',
				'width'         	    => '',
				'height'        	    => '',
				'timeline'      	    => 'true',
				'messages'      	    => 'true',
				'events'        	    => 'true',
				'hide_cover'    	    => 'false',
				'show_facepile' 	    => 'true',
				'small_header'  	    => 'false',
				'adapt_container_width' => 'true',
	 		);

	 		$instance = wp_parse_args((array) $instance, $defaults);

			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo esc_html__( 'Title:', 'enovathemes-addons' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('app_id'); ?>"><?php echo esc_html__( 'App ID from the app dashboard:', 'enovathemes-addons' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('app_id'); ?>" name="<?php echo $this->get_field_name('app_id'); ?>" type="text" value="<?php echo $instance['app_id']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('language_code'); ?>"><?php echo esc_html__( 'Language:', 'enovathemes-addons' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('language_code'); ?>" name="<?php echo $this->get_field_name('language_code'); ?>" type="text" value="<?php echo $instance['language_code']; ?>" />
				<p><?php echo esc_html__( 'You can change the language of the Page plugin by loading a localized version of the Facebook JavaScript SDK. Replace en_US with your locale, e.g., ru_RU for Russian (Russia):', 'enovathemes-addons' ); ?></p>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('href'); ?>"><?php echo esc_html__( 'The URL of the Facebook Page:', 'enovathemes-addons' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('href'); ?>" name="<?php echo $this->get_field_name('href'); ?>" type="text" value="<?php echo $instance['href']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('width'); ?>"><?php echo esc_html__( 'The pixel width of the plugin. Min. is 180 & Max. is 500:', 'enovathemes-addons' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $instance['width']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('height'); ?>"><?php echo esc_html__( 'The pixel height of the plugin. Min. is 70:', 'enovathemes-addons' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $instance['height']; ?>" />
			</p>

			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['timeline'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('timeline'); ?>" name="<?php echo $this->get_field_name('timeline'); ?>" /> 
				<label for="<?php echo $this->get_field_id('timeline'); ?>"><?php echo esc_html__( 'Show timeline', 'enovathemes-addons' ); ?></label>
			</p>

			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['events'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('events'); ?>" name="<?php echo $this->get_field_name('events'); ?>" /> 
				<label for="<?php echo $this->get_field_id('events'); ?>"><?php echo esc_html__( 'Show events', 'enovathemes-addons' ); ?></label>
			</p>

			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['messages'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('messages'); ?>" name="<?php echo $this->get_field_name('messages'); ?>" /> 
				<label for="<?php echo $this->get_field_id('messages'); ?>"><?php echo esc_html__( 'Show messages', 'enovathemes-addons' ); ?></label>
			</p>

			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['hide_cover'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('hide_cover'); ?>" name="<?php echo $this->get_field_name('hide_cover'); ?>" /> 
				<label for="<?php echo $this->get_field_id('hide_cover'); ?>"><?php echo esc_html__( 'Hide cover photo in the header', 'enovathemes-addons' ); ?></label>
			</p>

			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['show_facepile'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('show_facepile'); ?>" name="<?php echo $this->get_field_name('show_facepile'); ?>" /> 
				<label for="<?php echo $this->get_field_id('show_facepile'); ?>"><?php echo esc_html__( 'Show profile photos when friends like this', 'enovathemes-addons' ); ?></label>
			</p>

			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['small_header'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('small_header'); ?>" name="<?php echo $this->get_field_name('small_header'); ?>" /> 
				<label for="<?php echo $this->get_field_id('small_header'); ?>"><?php echo esc_html__( 'Use the small header instead', 'enovathemes-addons' ); ?></label>
			</p>

			<p>
				<input class="checkbox" type="checkbox" <?php checked($instance['adapt_container_width'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('adapt_container_width'); ?>" name="<?php echo $this->get_field_name('adapt_container_width'); ?>" /> 
				<label for="<?php echo $this->get_field_id('adapt_container_width'); ?>"><?php echo esc_html__( 'Try to fit inside the container width', 'enovathemes-addons' ); ?></label>
			</p>
			
			<?php
		}

		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			$instance['title']       = strip_tags( $new_instance['title'] );
			$instance['href']        = strip_tags( $new_instance['href']);
			$instance['app_id']      = strip_tags($new_instance['app_id']);
			$instance['language_code'] = strip_tags( $new_instance['language_code'] );

			$instance['width']         	       = $new_instance['width'];
			$instance['height']        	       = $new_instance['height'];
			$instance['timeline']      	       = $new_instance['timeline'];
			$instance['messages']      	       = $new_instance['messages'];
			$instance['events']        	       = $new_instance['events'];
			$instance['hide_cover']    	       = $new_instance['hide_cover'];
			$instance['show_facepile'] 	       = $new_instance['show_facepile'];
			$instance['small_header']  	       = $new_instance['small_header'];
			$instance['adapt_container_width'] = $new_instance['adapt_container_width'];
			
			return $instance;
		}
	}
?>