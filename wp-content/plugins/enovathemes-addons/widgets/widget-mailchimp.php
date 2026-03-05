<?php

add_action('widgets_init', 'enovathemes_addons_register_mailchimp_widget');
function enovathemes_addons_register_mailchimp_widget(){
	register_widget( 'Enovathemes_Addons_WP_Widget_Mailchimp' );
}

class  Enovathemes_Addons_WP_Widget_Mailchimp extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'mailchimp',
			esc_html__('* Mailchimp', 'enovathemes-addons'),
			array( 'description' => esc_html__('Mailchimp form', 'enovathemes-addons'))
		);
	}

	public function widget( $args, $instance ) {

		extract($args);

		$output = "";

		$title         = isset($instance['title']) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$description   = $instance['description'] ? $instance['description'] : '';
		$name          = $instance['name'] ? $instance['name'] : 'false';
		$required_name = (isset($instance['required_name']) && $instance['required_name'] == 'true') ? $instance['required_name'] : 'false';
		$required_name = ($required_name == "true") ? 'data-required="true"' :  'data-required="false"'; 

		$list = $instance['list'] ? $instance['list'] : '';

		$output .= $before_widget;

		if ( ! empty( $title ) ){$output .= $before_title . $title . $after_title;}
		$output .='<div class="mailchimp-form name-'.esc_attr($name).'">';
			if (!empty($description)) {
				$output .= '<p class="mailchimp-description">'.$description.'</p>';
			}
			$output .='<form class="et-mailchimp-form" name="et-mailchimp-form" action="'.esc_url( admin_url('admin-post.php') ).'" method="POST">';

				if ($name == "true") {
					$output .='<div class="field-wrap">';
						$output .='<input '.$required_name.' class="field" type="text" value="" name="fname" placeholder="'.esc_html__("First name", 'enovathemes-addons').'">';
						$output .='<span class="alert warning">'.esc_html__('Please enter your First name', 'enovathemes-addons').'</span>';
					$output .='</div>';
				}

				$output .='<div class="field-wrap">';
					$output .='<input type="text" value="" class="field" name="email" placeholder="'.esc_html__("Email address", 'enovathemes-addons').'">';
					$output .='<span class="alert warning">'.esc_html__('Invalid or empty email', 'enovathemes-addons').'</span>';
				$output .= '</div>';
				
				$output .='<input type="hidden" value="'.$list.'" name="list">';
				$output .='<input type="hidden" name="action" value="et_mailchimp" />';
				$output .='<div class="send-div">';
			    	$output .='<button type="submit" class="button et-button medium" name="subscribe">'.esc_html__('Subscribe', 'enovathemes-addons');
			    		$output .='<span class="icon"></span>';
			    	$output .='</button>';
				$output .='</div>';
			    $output .='<div class="et-mailchimp-success alert final success">'.esc_html__('You have successfully subscribed to the newsletter.', 'enovathemes-addons').'</div>';
		        $output .='<div class="et-mailchimp-error alert final error">'.esc_html__('Something went wrong. Your subscription failed.', 'enovathemes-addons').'</div>';
		        
		        $element_id = rand(1,1000000);

				$output .='<input type="hidden" name="id" value="'.$element_id.'" />';
		        $output .= wp_nonce_field( "et_mailchimp_action", "et_mailchimp_nonce_".$element_id, false, false );
				$output .='<div class="sending"></div>';
			$output .='</form>';
		$output .='</div>';
		$output .= $after_widget;
		echo $output;
	}

 	public function form( $instance ) {

		$defaults = array(
 			'title'       => esc_html__('Subscribe', 'enovathemes-addons'),
 			'description' => '',
 			'list'       => '',
 			'name'  => 'false',
 			'required_name'  => 'false',
 		);

 		$instance = wp_parse_args((array) $instance, $defaults);

 		?>


			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo esc_html__( 'Title:', 'enovathemes-addons' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'list' ); ?>"><?php echo esc_html__( 'Audience ID:', 'enovathemes-addons' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'list' ); ?>" name="<?php echo $this->get_field_name( 'list' ); ?>" type="text" value="<?php echo esc_attr($instance['list']); ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php echo esc_html__( 'Description:', 'enovathemes-addons' ); ?></label> 
				<textarea class="widefat" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>" type="text"><?php echo $instance['description']; ?></textarea>
			</p>

			<p class="et-clearfix label-right">
				<label for="<?php echo $this->get_field_id('name'); ?>"><?php echo esc_html__( 'Show first name field', 'enovathemes-addons' ); ?>
					<input class="checkbox" type="checkbox" <?php checked($instance['name'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" /> 
				</label>
				<label for="<?php echo $this->get_field_id('required_name'); ?>"><?php echo esc_html__( 'Required?', 'enovathemes-addons' ); ?>
					<input class="checkbox" type="checkbox" <?php checked($instance['required_name'], 'true'); ?> value="true" id="<?php echo $this->get_field_id('required_name'); ?>" name="<?php echo $this->get_field_name('required_name'); ?>" /> 
				</label>
			</p>

		
	<?php }

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['name']  = strip_tags( $new_instance['name'] );
		$instance['description'] = strip_tags( $new_instance['description']);
		$instance['list']        = strip_tags( $new_instance['list']);
		$instance['required_name']  = strip_tags( $new_instance['required_name'] );
		return $instance;
	}
}

?>