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
class Elementor_Widget_Logo extends \Elementor\Widget_Base {

	
	public function get_name() {
		return 'et_logo';
	}

	public function get_title() {
		return esc_html__( 'Logo', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-logo';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'logo'];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
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
			'logo',
			[
				'label' => esc_html__( 'Normal logo', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
			]
		);


		$this->add_control(
			'sticky_logo',
			[
				'label' => esc_html__( 'Sticky logo', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
			]
		);

		$this->add_control(
			'logo_width',
			[
				'type' => \Elementor\Controls_Manager::NUMBER,
				'label' => esc_html__( 'Logo width', 'enovathemes-addons' ),
				'min' => 1,
				'max' => 500,
				'step' => 1,
				'selectors' => [
					'{{WRAPPER}} > .elementor-widget-container > .header-logo' => 'width: {{VALUE}}px;'
				],
				'default' => '164'
			]
		);


		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		if (isset($logo) && !empty($logo)) {

			$image = wp_get_attachment_image_src($logo['id'],'full');

			$url = empty($image['0']) ? THEME_IMG.'logo.svg' : $image['0'];
			
			$output .= '<div class="header-logo">';
				$output .= '<a href="'.esc_url(home_url('/')).'" title="'.get_bloginfo('name').'">';
					$output .= '<img class="logo" src="'.$url.'" alt="'.get_bloginfo('name').'">';
					if (isset($sticky_logo) && !empty($sticky_logo) && array_key_exists('url',$sticky_logo) && !empty($sticky_logo['url'])) {
						
						$image = wp_get_attachment_image_src($sticky_logo['id'],'full');
						
						$output .= '<img class="sticky-logo" width="'.intval($image[1]).'" height="'.intval($image[2]).'" src="'.$sticky_logo['url'].'" alt="'.get_bloginfo('name').'">';
					} else {
						$output .= '<img class="sticky-logo" src="'.$url.'" alt="'.get_bloginfo('name').'">';
					}
				$output .= '</a>';
			$output .= '</div>';
		}
		
		if (!empty($output)) {
			echo $output;
		}

	}

}