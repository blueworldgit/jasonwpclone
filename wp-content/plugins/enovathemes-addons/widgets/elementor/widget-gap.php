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
class Elementor_Widget_Gap extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-gap'];
  }

	public function get_name() {
		return 'et_gap';
	}

	public function get_title() {
		return esc_html__( 'Gap', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-spacer';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return ['gap','space'];
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
				'height',
				[
					'label' => esc_html__( 'Height', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'step' => 1,
					'default'=> 100,
					'selectors'     => [
				   	'{{WRAPPER}} > .elementor-widget-container > .et-gap' => 'height: {{VALUE}}px;',
				  ]

				]
			);

			$breakpoints = array(
				'breakpoint-374'       => 'max-width 374px',
				'breakpoint-375'       => 'min-width 375px',
				'breakpoint-767'       => 'max-width 767px',
				'breakpoint-768'       => 'min-width 768px',
				'breakpoint-768-1023'  => '768px and max-width 1023px',
				'breakpoint-1024'      => 'min-width 1024px',
				'breakpoint-1024-1279' => '1024px and max-width 1279px',
				'breakpoint-1280'      => 'min-width 1280px',
				'breakpoint-1280-1367' => 'min-width 1280px and max-width 1367px',
				'breakpoint-1366-1599' => 'min-width 1366px and max-width 1599px',
				'breakpoint-1600'      => 'min-width 1600px'
			);

			foreach($breakpoints as $breakpoint => $label){

				$this->add_control(
					$breakpoint,
					[
						'label' => 'Hide on '.$label,
						'type' => \Elementor\Controls_Manager::SWITCHER,
						'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
						'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
						'return_value' => 'true',
					]
				);
			}
			

		$this->end_controls_section();


	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		$output = '';

		$class   = array();
		$class[] = 'et-gap';

		$breakpoints = array(
			'breakpoint-374',
			'breakpoint-375',
			'breakpoint-767',
			'breakpoint-768',
			'breakpoint-768-1023',
			'breakpoint-1024',
			'breakpoint-1024-1279',
			'breakpoint-1280',
			'breakpoint-1280-1367',
			'breakpoint-1366-1599',
			'breakpoint-1600'
		);
		
		foreach($breakpoints as $breakpoint){
			
			if (isset($settings[$breakpoint]) && !empty($settings[$breakpoint]) && $settings[$breakpoint] == "true") {
				$class[] = $breakpoint;
			}
		}

		if (isset($settings['height']) && !empty($settings['height'])) {
	    	$output .='<div class="'.implode(' ', $class).'"></div>';
		}

		if (!empty($output)) {
			echo $output;
		}

	}
}