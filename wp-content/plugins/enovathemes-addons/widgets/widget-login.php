<?php

	defined( 'ABSPATH' ) || exit;

	add_action('widgets_init', 'enovathemes_addons_register_reglog_widget');
	function enovathemes_addons_register_reglog_widget(){
		register_widget( 'Enovathemes_Addons_WP_Widget_Login' );
	}

	class  Enovathemes_Addons_WP_Widget_Login extends WP_Widget {

		public function __construct() {
			parent::__construct(
				'reglog',
				esc_html__('* Login form', 'enovathemes-addons'),
				array( 'description' => esc_html__('Front-end login form', 'enovathemes-addons'))
			);
		}

		public function widget( $args, $instance ) {

			if ($args) {
				
				extract($args);

				$home              = esc_url(home_url('/'));

				$title  		   = isset($instance['title']) ? apply_filters( 'widget_title', $instance['title'] ) : '';
				$my_account_link   = (isset($instance['my_account_link']) && !empty($instance['my_account_link'])) ? esc_url($instance['my_account_link']) : get_permalink(get_option('woocommerce_myaccount_page_id') );
				$registration_link = (isset($instance['registration_link']) && !empty($instance['registration_link'])) ? esc_url($instance['registration_link']) : $my_account_link;
				$forgot_link       = (isset($instance['forgot_link']) && !empty($instance['forgot_link'])) ? esc_url($instance['forgot_link']) : get_permalink(get_option('woocommerce_myaccount_page_id') ).'lost-password';
				$links             = enovathemes_addons_my_account_links($my_account_link);
				
				$output = "";
				echo $before_widget;
				if ( ! empty( $title ) ){echo $before_title . $title . $after_title;}

				$rand_id = rand();

				$args = array(
			        'echo'           => true,
			        'form_id'        => 'loginform-'.$rand_id,
			        'redirect'       => (!empty($my_account_link)) ? $my_account_link : '',
			        'label_username' => esc_html__('Username', 'enovathemes-addons'),
			        'label_password' => esc_html__('Password', 'enovathemes-addons'),
			        'label_remember' => esc_html__( 'Remember Me', 'enovathemes-addons'),
			        'label_log_in'   => esc_html__( 'Log In', 'enovathemes-addons'),
			        'id_username'    => 'user_login-'.$rand_id,
			        'id_password'    => 'user_pass-'.$rand_id,
			        'id_remember'    => 'rememberme-'.$rand_id,
			        'id_submit'      => 'wp-submit-'.$rand_id,
			        'remember'       => false,
			        'value_username' => '',
			        'value_remember' => false
				);

				$email = $avatar = $user = '';

				if ( is_user_logged_in() ) {
					$current_user = wp_get_current_user();
					$user         = ($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name;
					$avatar       = get_avatar($current_user->ID, '56');
					$email        = $current_user->user_email;
				}

				?>

				<div class="logged-in">
					<div class="user">
						<?php echo $avatar; ?>
						<div class="info">
							<span><?php echo esc_html($user); ?></span>
							<span><?php echo esc_html($email); ?></span>
							<a class="et-button small" href="<?php echo $my_account_link.'/edit-account'; ?>"><?php echo esc_html__("Edit profile","enovathemes-addons"); ?></a>
						</div>
					</div>
					<div class="my-account-buttons"><ul>

						<?php

							foreach ($links as $label => $link) {

								$this_label = (isset($instance[$label.'_label']) && !empty($instance[$label.'_label'])) ? $instance[$label.'_label'] : ucwords(str_replace('_',' ',$label));

								echo '<li class="'.$label.'">';
									echo '<a href="'.esc_url($link).'">'.$this_label;
								echo '</a></li>';
							}

						?>

					</ul></div>
				</div>

				<div class="logged-out">
					<?php

						wp_login_form( $args );

						echo '<div class="form-links">';

							if (!empty($forgot_link)) {
								echo '<a href="'.esc_url($forgot_link).'" class="forgot">'.esc_html__("Forgot password?", 'enovathemes-addons').'</a>';
							}
							if (!empty($registration_link)) {
								echo '<a href="'.esc_url($registration_link).'" class="signup">'.esc_html__("Sign up", 'enovathemes-addons').'</a>';
							}

						echo '</div>';

					?>
				</div>

				
				<?php echo $after_widget;

			}
		}

	 	public function form( $instance ) {

			$links = enovathemes_addons_my_account_links();

			$defaults = array(
	 			'title'  => esc_html__('Login', 'enovathemes-addons'),
	 			'registration_link'  => '',
	 			'forgot_link'  => '',
	 			'my_account_link'  => '',
	 		);

	 		foreach ( $links as $label => $link ) {
			    $key    = sanitize_key( $label ) . '_label';
			    $pretty = ucwords( str_replace( ['_', '-'], ' ', (string) $label ) );

			    // Use translated literal if we have one; otherwise just a clean, escaped fallback
			    $defaults[ $key ] = $label_map[ $label ] ?? esc_html( $pretty );
			}

	 		$instance = wp_parse_args((array) $instance, $defaults);
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo esc_html__( 'Title:', 'enovathemes-addons' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('my_account_link'); ?>"><?php echo esc_html__( 'My account link:', 'enovathemes-addons' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('my_account_link'); ?>" name="<?php echo $this->get_field_name('my_account_link'); ?>" type="text" value="<?php echo $instance['my_account_link']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('registration_link'); ?>"><?php echo esc_html__( 'Registration page link:', 'enovathemes-addons' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('registration_link'); ?>" name="<?php echo $this->get_field_name('registration_link'); ?>" type="text" value="<?php echo $instance['registration_link']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('forgot_link'); ?>"><?php echo esc_html__( 'Password recovery page:', 'enovathemes-addons' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('forgot_link'); ?>" name="<?php echo $this->get_field_name('forgot_link'); ?>" type="text" value="<?php echo $instance['forgot_link']; ?>" />
			</p>


				<?php foreach ( $links as $label => $link ) {
				    $pretty_label = ucwords( str_replace( ['_', '-'], ' ', (string) $label ) );
				    $field_key    = $label . '_label';
				    $field_id     = $this->get_field_id( $field_key );
				    $field_name   = $this->get_field_name( $field_key );
				    $field_value  = isset( $instance[ $field_key ] ) ? $instance[ $field_key ] : '';
				    ?>
				    <p>
				        <label for="<?php echo esc_attr( $field_id ); ?>">
				            <?php
				            echo sprintf(
				                /* translators: %s: control label text (e.g., "Facebook"). */
				                esc_html__( '%s label', 'enovathemes-addons' ),
				                esc_html( $pretty_label )
				            );
				            ?>
				        </label>
				        <input class="widefat"
				               id="<?php echo esc_attr( $field_id ); ?>"
				               name="<?php echo esc_attr( $field_name ); ?>"
				               type="text"
				               value="<?php echo esc_attr( $field_value ); ?>" />
				    </p>
				<?php }


		}

		public function update( $new_instance, $old_instance ) {

			$instance = $old_instance;
			$instance['title']  = strip_tags( $new_instance['title'] );
			$instance['registration_link']  = strip_tags( $new_instance['registration_link'] );
			$instance['forgot_link']  = strip_tags( $new_instance['forgot_link'] );
			$instance['my_account_link']  = strip_tags( $new_instance['my_account_link'] );

			$links = enovathemes_addons_my_account_links();

			// Define once (PHPCS wants the translators comment right above the i18n call).
			/* translators: %s: control label text (e.g., "Facebook"). */
			$label_format = __( '%s label', 'enovathemes-addons' );

			foreach ( $links as $label => $link ) {
			    $key          = sanitize_key( $label ) . '_label';
			    $pretty_label = ucwords( str_replace( ['_', '-'], ' ', (string) $label ) );
			    $default      = sprintf( $label_format, $pretty_label );

			    if ( ! isset( $instance[ $key ] ) || $instance[ $key ] === '' ) {
			        $instance[ $key ] = $default;
			    }

			    if ( ! isset( $new_instance[ $key ] ) || $new_instance[ $key ] === '' ) {
			        $new_instance[ $key ] = $default;
			    }

			    // Save sanitized (not escaped). Escape later when rendering.
			    $instance[ $key ] = sanitize_text_field( $new_instance[ $key ] );
			}


			return $instance;
		}
	}

?>