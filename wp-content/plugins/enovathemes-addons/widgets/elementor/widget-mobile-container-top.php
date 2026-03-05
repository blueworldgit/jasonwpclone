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
class Elementor_Widget_Mobile_Container_Top extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-mobile-container-top'];
  }

	public function get_name() {
		return 'et_mobile_container_top';
	}

	public function get_title() {
		return esc_html__( 'Mobile container top', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-account';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'login', 'account', 'mobile'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

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
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-mobile-container-top' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-mobile-container-top .et-button' => 'border-color:{{VALUE}};color:{{VALUE}};',
				        '{{WRAPPER}} > .elementor-widget-container > .et-mobile-container-top .avatar-placeholder' => 'border-color:{{VALUE}};',
				        '{{WRAPPER}} > .elementor-widget-container > .et-mobile-container-top .avatar-placeholder:before' => 'background-color:{{VALUE}};',
				        '{{WRAPPER}} > .elementor-widget-container > .et-mobile-container-top .mobile-toggle.active:before' => 'background-color:{{VALUE}};',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'background_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-mobile-container-top' => 'background-color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

		$this->end_controls_section();

	}

	protected function render() {

		global $woocommerce;

		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		$class   = array();
		$class[] = 'et-mobile-container-top';

    $output .= '<div class="'.implode(" ", $class).'">';

    	$output .= '<div class="mobile-toggle active hbe-toggle"></div>';

    	$avatar = $email = $user = '';

    	if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$user         = ($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name;
				$avatar       = get_avatar($current_user->ID, '56');
				$email        = $current_user->user_email;
			}

			$my_account_link = (empty($my_account_link)) ? 

			class_exists("woocommerce") ? (get_option('woocommerce_myaccount_page_id')) ? get_permalink(get_option('woocommerce_myaccount_page_id')) : '' : '' : $my_account_link;

    	$output .= '<div class="logged-in info-wrap">';

    		$output .= $avatar;
				$output .='<div class="info">';
					if (!empty($user)) {
						$output .='<span>'.esc_html($user).'</span>';
					}

					if (!empty($email)) {
						$output .='<span>'.esc_html($email).'</span>';
					}
				$output .= '</div>';

				if (!empty($my_account_link)) {
					$output .= '<a href="'.esc_url($my_account_link).'" class="et-button small">'.esc_html__("Dashboard","enovathemes-addons").'</a>';
				}

    	$output .= '</div>';

    	$output .= '<div class="logged-out info-wrap">';

    		$output .= '<div class="avatar-placeholder"></div>';
				$output .='<div class="info">';
					$output .='<span>'.esc_html__("Hello Guest","enovathemes-addons").'</span>';
					$output .='<span>'.esc_html__("For better experience login","enovathemes-addons").'</span>';
				$output .= '</div>';
				if (!empty($my_account_link)) {
					$output .= '<a href="'.esc_url($my_account_link).'" class="et-button small">'.esc_html__("Login","enovathemes-addons").'</a>';
				}
    	$output .= '</div>';


			

    $output .= '</div>';

		if (!empty($output)) {
			echo $output;
		}

	}

}