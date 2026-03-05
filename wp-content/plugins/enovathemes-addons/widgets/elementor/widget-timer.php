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
class Elementor_Widget_Timer extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-timer', plugins_url('../../js/widget-timer.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-timer'];
  }

	public function get_name() {
		return 'et_timer';
	}

	public function get_title() {
		return esc_html__( 'Timer', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-clock-o';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'timer'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_responsive_control(
				'content_alignment',
				[
					'label' => esc_html__( 'Content horizontal alignment', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						''    => esc_html__('Choose','enovathemes-addons'),
						'flex-start' => esc_html__('Start','enovathemes-addons'),
						'center'  => esc_html__('Center','enovathemes-addons'),
						'flex-end'   => esc_html__('End','enovathemes-addons'),
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container' => 'justify-content: {{VALUE}};display:flex;',
				  ],
					'default' => ''
				]
			);

			$this->add_control(
				'enddate',
				[
					'label' => esc_html__( 'Number', 'enovathemes-addons' ),
					'description' => esc_html__( 'Use format : June 7, 2025 15:03:25', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default'=> 'June 7, 2025 15:03:25',
				]
			);

			$this->add_control(
				'gmt',
				[
					'label' => esc_html__( 'GMT offset (like +4)', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default'=> '+4',
				]
			);

			$this->add_control(
				'number',
				[
					'label' => esc_html__( 'Extend by N days automatically on expire', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
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
				'color',
				[
					'label' => esc_html__( 'Timer color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-timer li div' => 'color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Timer track color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-timer li div' => 'background: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-timer li div:after' => 'color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		$attributes = array();

		if (isset($number) && !empty($number)) {
			$attributes[] = 'data-number="'.absint($number).'"';
		}

		if (isset($gmt) && !empty($gmt)) {
			$attributes[] = 'data-gmt="'.absint($gmt).'"';
		}

		if (isset($enddate) && !empty($enddate)) {
			$attributes[] = 'data-enddate="'.esc_attr($enddate).'"';
		}

		if (isset($enddate) && !empty($enddate)) {

			$output .='<div '.implode(" ", $attributes).' class="et-timer">';
				$output .='<ul>';
				  $output .='<li><div><span class="timer-count days">00</span></div></li>';
					$output .='<li><div><span class="timer-count hours">00</span></div></li>';
					$output .='<li><div><span class="timer-count minutes">00</span></div></li>';
					$output .='<li><div><span class="timer-count seconds">00</span></div></li>';
				$output .='</ul>';
			$output .='</div>';

		}

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			view.addRenderAttribute( 'wrapper', 'class', 'et-timer' );
			
			if ( settings.number.length) {
				view.addRenderAttribute( 'wrapper', 'data-number', settings.number );
			}

			if ( settings.gmt.length) {
				view.addRenderAttribute( 'wrapper', 'data-gmt', settings.gmt );
			}

			if ( settings.enddate.length) {
				view.addRenderAttribute( 'wrapper', 'data-enddate', settings.enddate );
			}

		#>

  	<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>
  		<ul>
			  <li><div><span class="timer-count days">00</span></div></li>
				<li><div><span class="timer-count hours">00</span></div></li>
				<li><div><span class="timer-count minutes">00</span></div></li>
				<li><div><span class="timer-count seconds">00</span></div></li>
			</ul>
  	</div>

	<?php }

}