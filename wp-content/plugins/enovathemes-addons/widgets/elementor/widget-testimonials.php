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
class Elementor_Widget_Testimonials extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-testimonials'];
  }

	public function get_name() {
		return 'et_testimonials';
	}

	public function get_title() {
		return esc_html__( 'Testimonials', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-testimonial';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'testimonials'];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'text', [
				'label' => esc_html__( 'Text', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::WYSIWYG,
			]
		);

		$this->add_control(
			'name', [
				'label' => esc_html__( 'Name', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$this->add_control(
			'image', [
				'label' => esc_html__( 'Image', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'selectors'     => [
		      '{{WRAPPER}} .testimonials-item .image' => 'background-image: url({{URL}});',
		    ]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'styling',
			[
				'label' => esc_html__( 'Styling', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .testimonials-item' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'border_color',
				[
					'label' => esc_html__( 'Border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .testimonials-item' => 'border-color: {{VALUE}}',
				    ],
				    'default' => '#e0e0e0'
				]
			);

			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .testimonials-item' => 'color: {{VALUE}}',
				      '{{WRAPPER}} .testimonials-item .title' => 'color: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

		$this->end_controls_section();
		

	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		$class = array();
		$class[] = 'testimonials-item';


		$output.='<div class="'.implode(' ', $class).'">';

			$output.='<div class="info-wrap">';

				if (isset($image['url']) && !empty($image['url'])) {
					$output.='<div class="image"></div>';
				}

				if (isset($name) && !empty($name)) {
					$output.='<h6 class="name">';
						$output.=esc_html($name);
						$output.='<span class="rating"></span>';
					$output.='</h6>';
				}

			$output.='</div>';

			if (isset($text) && !empty($text)) {
				$output.='<div class="text">'.$text.'</div>';
			}

		$output.='</div>';

		if (!empty($output)) {
			echo $output;
		}
	}

	

}