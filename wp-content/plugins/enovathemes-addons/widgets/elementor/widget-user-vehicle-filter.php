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
class Elementor_Widget_User_Vehicle_Filter extends \Elementor\Widget_Base {


	public function get_name() {
		return 'et_my_garage';
	}

	public function get_title() {
		return esc_html__( 'My garage', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-account';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'my-garage', 'vehicle'];
	}

	protected function register_controls() {

		$vehicle_params = apply_filters( 'vehicle_params','');

		$this->start_controls_section(
			'styling',
			[
				'label' => esc_html__( 'Styling', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$options = array();

			foreach ($vehicle_params as $param) {
				$options[$param] = ucfirst($param);
			}

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

		$this->end_controls_section();

	}

	protected function render() {

		global $woocommerce;

		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		if (isset($filter_attributes) && !empty($filter_attributes)) {

			$class   = array();
			$class[] = 'et-my-garage';

		    $output .= '<div class="'.implode(" ", $class).'">';

	    		$filter_atts = array();

				foreach ( $filter_attributes as $attribute ) {
					$label = (isset($settings[$attribute.'_label']) && !empty($settings[$attribute.'_label'])) ? mb_convert_encoding($settings[$attribute.'_label'], 'UTF-8') : $attribute;
					$filter_atts[] = array('attr'=>$attribute,'label'=>ucfirst($label));
				}

				$vin = empty($vin) ? 'off' : 'on';

	    		$filter_instance = array(
					'title' 	  => '',
			        'atts'  	  => json_encode($filter_atts,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
			        'vin'   	  => $vin,
			        'columns'   => 1,
			        'type'  => 'vertical',
				);

				$filter_args = array(
					'before_title'  => '<h5 class="widget_title">',
		      		'after_title'   => '</h5>',
				);

				$output .= enovathemes_addons_get_the_widget( 'Enovathemes_Addons_WP_User_Vehicle_Filter', $filter_instance,$filter_args);

		    $output .= '</div>';

			if (!empty($output)) {
				echo $output;
			}

		}

	}

}