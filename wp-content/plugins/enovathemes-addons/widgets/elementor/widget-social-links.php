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
class Elementor_Widget_Social_Links extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-social-links'];
  }

	public function get_name() {
		return 'et_social_links';
	}

	public function get_title() {
		return esc_html__( 'Social Links', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-social-links';
	}

	public function get_categories() {
		return [ 'header-builder', 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'social links'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

		$social_links_array = enovathemes_addons_social_icons(get_template_directory().'/images/icons/social/');

		$this->start_controls_section(
			'links',
			[
				'label' => esc_html__( 'Social links', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			foreach ($social_links_array as $social) {

				$this->add_control(
					$social,
					[
						'label' => ucfirst($social).' '.esc_html__( 'link', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::TEXT,
					]
				);

			}

		$this->end_controls_section();

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
				'size',
				[
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 1000,
					'step' => 1,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-social-links a' => 'width: {{VALUE}}px;height: {{VALUE}}px;',
				  ],
				]
			);

			$this->add_control(
				'stretching',
				[
					'label' => esc_html__( 'Stretch links', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'original',
				[
					'label' => esc_html__( 'Original colors', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-social-links a:before' => 'background: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'color_hover',
				[
					'label' => esc_html__( 'Color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-social-links a:hover:before' => 'background: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-social-links a' => 'background: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'back_color_hover',
				[
					'label' => esc_html__( 'Background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-social-links a:hover' => 'background: {{VALUE}}',
				  ],
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

		$settings = $this->get_settings_for_display();
		$social_links_array = enovathemes_addons_social_icons(get_template_directory().'/images/icons/social/');

		extract($settings);

		$hide_default = empty($hide_default) ? 'false' : $hide_default;
		$hide_sticky  = empty($hide_sticky) ? 'false' : $hide_sticky;
		$stretching   = empty($stretching) ? 'false' : $stretching;
		$original     = empty($original) ? 'false' : $original;

		$output = '';

		$attributes = array();
		$class      = array();
		
		$class[] = 'et-social-links';
		$class[] = 'styling-original-'.$original;
		$class[] = 'stretching-'.$stretching;
		$class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;

		if ((!isset($back_color) || empty($back_color)) && (!isset($back_color_hover) || empty($back_color_hover)) && $original == 'false') {
			$class[] = 'free';
		}

		$output .= '<div class="'.implode(" ", $class).'">';
			foreach($settings as $social => $href) {
				if (in_array($social, $social_links_array) && !empty($href)) {
					$output .='<a class="'.$social.'" href="'.$href.'" title="'.ucfirst($social).'"></a>';
				}
			}
		$output .= '</div>';

		if (!empty($output)) {
			echo $output;
		}

	}

}