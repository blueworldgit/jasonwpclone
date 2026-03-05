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
class Elementor_Widget_Image extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-image', plugins_url('../../js/widget-image.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-image'];
  }

	public function get_name() {
		return 'et_image';
	}

	public function get_title() {
		return esc_html__( 'Image', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-image-bold';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'image'];
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
				'image',
				[
					'label' => esc_html__( 'Image', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
				  'default' => [
						'url' => THEME_IMG.'/image-placeholder.png',
					]
				]
			);

			$this->add_responsive_control(
				'size',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'options' => [
						'thumbnail' => esc_html__('Thumbnail','enovathemes-addons'),
						'medium'    => esc_html__('Medium','enovathemes-addons'),
						'large'     => esc_html__('Large','enovathemes-addons'),
						'full'      => esc_html__('Full','enovathemes-addons'),
					],
					'default' => 'full'
				]
			);

			$this->add_responsive_control(
				'alignment',
				[
					'label' => esc_html__( 'Align', 'enovathemes-addons' ),
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
				'link_url', [
					'label' => esc_html__( 'Paste link here', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			$this->add_control(
				'link_target',
				[
					'label' => esc_html__( 'Link target', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'_self'  => '_self',
						'_blank' => '_blank',
					],
					'default' => '_self',
				]
			);

			$this->add_control(
				'border_radius',
				[
					'label' => esc_html__( 'Border radius', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'max' => 5000,
					'default' => 6,
					'step' => 1,
					'selectors' => [
						'{{WRAPPER}} .et-image img' => 'border-radius: {{SIZE}}px;',
					],
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'parallax',
			[
				'label' => esc_html__( 'Parallax', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'enable_parallax',
				[
					'label' => esc_html__( 'Enable parallax transform?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'parallax_x',
				[
					'label' => esc_html__( 'Offset X coordinate', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'max' => 5000,
					'default' => 0,
					'step' => 1,
					'condition' => [
						'enable_parallax' => 'true',
					]
				]
			);

			$this->add_control(
				'parallax_y',
				[
					'label' => esc_html__( 'Offset Y coordinate', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'max' => 5000,
					'default' => 0,
					'step' => 1,
					'condition' => [
						'enable_parallax' => 'true',
					]
				]
			);

			$this->add_control(
				'parallax_speed',
				[
					'label' => esc_html__( 'Parallax speed radtio', 'enovathemes-addons' ),
					'description' => esc_html__( 'The more the value is the slower the parallax effect is', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 50,
					'default' => 10,
					'step' => 1,
					'condition' => [
						'enable_parallax' => 'true',
					]
				]
			);

			$this->add_control(
				'parallax_limit',
				[
					'label' => esc_html__( 'Parallax limit', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 5000,
					'step' => 1,
					'condition' => [
						'enable_parallax' => 'true',
					]
				]
			);

		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		extract($settings);

		if (isset($image) && !empty($image)) {

			$image_id = $image['id'];
			$image    = wp_get_attachment_image_src($image['id'], $size);

			if ($image) {

				$image_width   = intval($image[1]);
        $image_height  = intval($image[2]);

				$output     = '';
				$class      = array();
				$attributes = array();

				$class[] = 'et-image';

				$parallax_x = (isset($parallax_x) && !empty($parallax_x) ? $parallax_x : '0');
				$parallax_y = (isset($parallax_y) && !empty($parallax_y) ? $parallax_y : '0');

				if (isset($enable_parallax) && $enable_parallax == "true") {
					$class[]      = 'parallax';
					$attributes[] = 'data-coordinatex="'.esc_attr($parallax_x).'"';
					$attributes[] = 'data-coordinatey="'.esc_attr($parallax_y).'"';
					$attributes[] = 'data-speed="'.esc_attr($parallax_speed).'"';
					if (isset($parallax_limit) && !empty($parallax_limit)) {
						$attributes[] = 'data-limit="'.esc_attr($parallax_limit).'"';
					}
					$attributes[] = 'style="width:'.$image_width.'px;height:'.$image_height.'px;max-width:'.$image_width.'px;max-height:'.$image_height.'px;transform:translate('.esc_attr($parallax_x).'px,'.esc_attr($parallax_y).'px)"';
				}

				$link_before = '';
				$link_after  = '';

				if (isset($link_url) && !empty($link_url)) {
					$class[] = 'link';
					$link_before = '<a target="'.$link_target.'" href="'.esc_url($link_url).'">';
					$link_after  = '</a>';
				}

				$output .='<div class="'.implode(' ', $class).'" '.implode(' ', $attributes).'>';
					$output .= $link_before;
							if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
									$output .= '<img src="'.$image[0].'">';
							} else {
									$output .= enovathemes_addons_inline_image_placeholder($image_id,$size);
			        }
					$output .=$link_after;
				$output .='</div>';

			}
	
		}

		if (!empty($output)) {
			echo $output;
		}

	}



}