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
class Elementor_Widget_Product_Vehicle_Filter extends \Elementor\Widget_Base {


	public function get_name() {
		return 'et_vehicle_filter';
	}

	public function get_title() {
		return esc_html__( 'Vehicle filter', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-filter';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'filter', 'vehicle'];
	}

	protected function register_controls() {

		$vehicle_params = apply_filters( 'vehicle_params','');

		$this->start_controls_section(
			'styling',
			[
				'label' => esc_html__( 'Content', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$options = array();

			foreach ($vehicle_params as $param) {
				$options[$param] = $param;
			}

			$this->add_control(
				'title', [
					'label' => esc_html__( 'Title', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'label_block' => true,
				]
			);

			$this->add_control(
			'filter_attributes',
				[
					'label' => esc_html__( 'Available filter attributes', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT2,
					'label_block' => true,
					'multiple' => true,
					'options' => $options,
				]
			);

			foreach ($vehicle_params as $param) {
				$this->add_control(
					$param.'_label', [
						'label' => ucfirst($param).' '.esc_html__( 'label', 'enovathemes-addons' ),
						'description' => esc_html__( 'Leave blank to inherit default value', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::TEXT,
						'label_block' => true,
					]
				);
			}

			$this->add_control(
			'vin',
				[
					'label' => esc_html__( 'Enable VIN search', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'type',
				[
					'label' => esc_html__( 'Layout', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'horizontal' => esc_html__('Horizontal','enovathemes-addons'),
						'vertical'   => esc_html__('Vertical','enovathemes-addons'),
					],
					'default' => 'horizontal',
				]
			);

			$this->add_control(
				'columns',
				[
					'label' => esc_html__( 'Columns', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 6,
					'step' => 1,
					'default'=>1,
					'condition' => [
						'type' => 'horizontal',
					]
				]
			);

			$this->add_control(
				'columns_vertical',
				[
					'label' => esc_html__( 'Columns', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 2,
					'step' => 1,
					'default'=>1,
					'condition' => [
						'type' => 'vertical',
					]
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'styling_option',
			[
				'label' => esc_html__( 'Styling', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Text color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .product-vehicle-filter' => 'color: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .product-vehicle-filter' => 'background-color: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'button_color',
				[
					'label' => esc_html__( 'Button text color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container input[type="submit"]' => 'color: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'button_back_color',
				[
					'label' => esc_html__( 'Button background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container input[type="submit"]' => 'background-color: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'button_color_hover',
				[
					'label' => esc_html__( 'Button text color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container input[type="submit"]:hover' => 'color: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'button_back_color_hover',
				[
					'label' => esc_html__( 'Button background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container input[type="submit"]:hover' => 'background-color: {{VALUE}}',
				  ],
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'responsive',
			[
				'label' => esc_html__( 'Responsive settings', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
	
		$resp_array = array(
			'breakpoint_767' => esc_html__( 'Hide on mobile', 'enovathemes-addons' ),
			'breakpoint_768_1023' => esc_html__( 'Hide on tablet portrait', 'enovathemes-addons' ),
			'breakpoint_1024_1279' => esc_html__( 'Hide on tablet landscape', 'enovathemes-addons' ),
			'breakpoint_1280_1365' => esc_html__( 'Hide on tablet landscape extra', 'enovathemes-addons' ),
			'breakpoint_1366' => esc_html__( 'Hide on desktop', 'enovathemes-addons' ),
		);


	    foreach($resp_array as $key => $value){
			$this->add_control(
		        $key,
		        [
		            'label' => $value,
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

		global $woocommerce;

		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		if (isset($filter_attributes) && !empty($filter_attributes)) {

			$class   = array();
			$class[] = 'et-vehicle-filter';

			$resp_array = array(
				'breakpoint_767',
				'breakpoint_768_1023',
				'breakpoint_1024_1279',
				'breakpoint_1280_1365',
				'breakpoint_1366',
			);

			foreach($resp_array as $key){
				if ($$key == 'true') {
					$class[] = str_replace('_','-',$key);
				}
			}

		    $output .= '<div class="'.implode(" ", $class).'">';

	    		$filter_atts = array();

				foreach ( $filter_attributes as $attribute ) {

					$label = (isset($settings[$attribute.'_label']) && !empty($settings[$attribute.'_label'])) ? mb_convert_encoding($settings[$attribute.'_label'], 'UTF-8') : $attribute;

					$filter_atts[] = array('attr'=>$attribute,'label'=>ucfirst($label));
				}

				$vin = empty($vin) ? 'off' : 'on';

	    		$filter_instance = array(
					'title' 	  => $title,
			        'atts'  	  => json_encode($filter_atts,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
			        'vin'   	  => $vin,
			        'columns'     => ($type == 'horizontal' ? $columns : $columns_vertical),
			        'type'        => $type,
				);

				$filter_args = array(
					'before_title'  => '<h5 class="widget_title">',
		      		'after_title'   => '</h5>',
				);

				$output .= enovathemes_addons_get_the_widget( 'Enovathemes_Addons_WP_Product_Vehicle_Filter', $filter_instance,$filter_args);

		    $output .= '</div>';

			if (!empty($output)) {
				echo $output;
			}

		}

	}

}