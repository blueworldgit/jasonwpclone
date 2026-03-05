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
class Elementor_Widget_Currency_Switcher extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-currency-switcher', plugins_url('../../js/widget-currency-switcher.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-currency-switcher'];
  }

	public function get_name() {
		return 'et_currency_switcher';
	}

	public function get_title() {
		return esc_html__( 'Currency switcher', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-currency-switcher';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'currency'];
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

		extract($settings);

		$hide_default      = empty($hide_default) ? 'false' : $hide_default;
		$hide_sticky       = empty($hide_sticky) ? 'false' : $hide_sticky;

		$output = '';

		$attributes = array();
		$class      = array();
		
		$class[] = 'currency-switcher';
    $class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;
		$class[] = 'box-align-center';

		$output .= '<div class="'.implode(" ", $class).'">';
    	if(shortcode_exists('yaycurrency-switcher')) {
				$output .= do_shortcode('[yaycurrency-switcher]');
			} else {
				$output .= '<a target="_blank" href="//wordpress.org/plugins/yaycurrency/">'.esc_html__("Currency switcher","enovathemes-addons").'</a>';
			}
    $output .= '</div>';

		if (!empty($output)) {
			echo $output;
		}

	}

}