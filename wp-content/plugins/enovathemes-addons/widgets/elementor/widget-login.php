<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor oEmbed Widget.
 *
 * Elementor widget that inserts an embbedable content into the page, from any given URL.
 *
 * @since 1.0.0
 */
class Elementor_Widget_Login extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-login', plugins_url('../../js/widget-login.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-login'];
  }

	public function get_name() {
		return 'et_login';
	}

	public function get_title() {
		return esc_html__( 'Login', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-account';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'login', 'account'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';
		$links        = enovathemes_addons_my_account_links();

		$this->start_controls_section(
			'styling',
			[
				'label' => esc_html__( 'Styling', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_responsive_control(
				'self_align',
				[
					'label' => esc_html__( 'Horizontal self alignment', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'margin:0;' => esc_html__('Choose','enovathemes-addons'),
						'margin-right:auto;' => esc_html__('Start','enovathemes-addons'),
						'margin-left:auto;margin-right:auto;'   => esc_html__('Center','enovathemes-addons'),
						'margin-left:auto;'   => esc_html__('End','enovathemes-addons'),
					],
					'selectors'     => [
				        '{{WRAPPER}}' => '{{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'my_account_link',
				[
					'label' => esc_html__( 'My account link', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			$this->add_control(
				'registration_link',
				[
					'label' => esc_html__( 'Registration page link', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			$this->add_control(
				'forgot_link',
				[
					'label' => esc_html__( 'Password recovery page', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			foreach ( $links as $label => $link ) {
		    $control_id   = sanitize_key( $label ) . '_label';
		    $pretty_label = ucwords( str_replace( ['_', '-'], ' ', (string) $label ) );

		    $this->add_control(
		        $control_id,
		        [
		            'label' => sprintf(
		                /* translators: %s: control label text (e.g., "Facebook"). */
		                esc_html__( '%s label', 'enovathemes-addons' ),
		                esc_html( $pretty_label )
		            ),
		            'type'  => \Elementor\Controls_Manager::TEXT,
		        ]
		    );
			}


			$this->add_control(
				'icon_color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-login .login-toggle' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-login .login-toggle:before' => 'background: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-login .login-toggle .arrow' => 'background: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
			'icon_size',
			[
				'type' => \Elementor\Controls_Manager::NUMBER,
				'label' => esc_html__( 'Icon size', 'enovathemes-addons' ),
				'min' => 1,
				'step' => 1,
				'selectors' => [
					'{{WRAPPER}} > .elementor-widget-container > .et-login .login-toggle:before' => 'width: {{VALUE}}px;height: {{VALUE}}px;'
				],
			]);

			$this->add_control(
				'icon_background_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-login .login-toggle' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'typography',
					'label'    => esc_html__( 'Typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} > .elementor-widget-container > .et-login .login-title',
				]
			);

			$this->add_control(
				'login_button_color',
				[
					'label' => esc_html__( 'Login box button color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-login .login-box .button' => 'color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'login_button_color_hover',
				[
					'label' => esc_html__( 'Login box button color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-login .login-box .button:hover' => 'color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'login_button_back_color',
				[
					'label' => esc_html__( 'Login box button background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-login .login-box .button' => 'background-color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'login_button_back_color_hover',
				[
					'label' => esc_html__( 'Login box button background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-login .login-box .button:hover' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);


		$this->end_controls_section();

		$this->start_controls_section(
			'visibility',
			[
				'label' => esc_html__( 'Visibility', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);


			$this->add_control(
				'hide_default',
				[
					'label' => esc_html__( 'Hide from default header version?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'hide_sticky',
				[
					'label' => esc_html__( 'Hide from sticky header version?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

		

		$this->end_controls_section();

	}

	protected function render() {

		global $woocommerce;

		$settings = $this->get_settings_for_display();

		extract($settings);

		$hide_default = empty($hide_default) ? 'false' : $hide_default;
		$hide_sticky  = empty($hide_sticky) ? 'false' : $hide_sticky;

		$output = '';

		$class   = array();
		$class[] = 'et-login';
    $class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;

    $output .= '<div class="'.implode(" ", $class).'">';

    	if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$user = ($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name;
			}

      $output .= '<div class="login-toggle hbe-toggle">';
				$output .= '<div class="login-title login"><span class="my-account-text">'.esc_html__("My account","enovathemes-addons").'</span>';
				$output .= '<span class="login-text">'.esc_html__("Login","enovathemes-addons").'</span></div>';
			$output .= '</div>';

			$output .= '<div class="login-box box">';
				$instance = array(
					'title'=> '',
					'registration_link'=>$registration_link,
					'forgot_link'=>$forgot_link,
					'my_account_link'=>$my_account_link,
				);

				$links = enovathemes_addons_my_account_links();
				foreach ($links as $label => $link) {
					$instance[$label.'_label'] = $settings[$label.'_label'];
				}

				$output .= enovathemes_addons_get_the_widget( 'Enovathemes_Addons_WP_Widget_Login', $instance,'');
			$output .= '</div>';

    $output .= '</div>';

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			view.addRenderAttribute( 'wrapper', 'class', 'et-login' );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-default-'+settings.hide_default );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-sticky-'+settings.hide_sticky );

		#>

		<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>
			<div class="login-toggle hbe-toggle"><div class="login-title login">My account</div></div>
		</div>
		
	<?php }

}