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
class Elementor_Widget_Progress extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-progress', plugins_url('../../js/widget-progress.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-progress'];
  }

	public function get_name() {
		return 'et_progress';
	}

	public function get_title() {
		return esc_html__( 'Progress', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-flash';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'progress'];
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

			$this->add_control(
				'percentage',
				[
					'label' => esc_html__( 'Number', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 100,
					'step' => 1,
					'default'=> 100,
					'selectors' => [
				      '{{WRAPPER}} > .elementor-widget-container > .et-progress .bar' => 'width: {{VALUE}}%',
				  ],
				]
			);

			$this->add_control(
				'title',
				[
					'label' => esc_html__( 'Text', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default'=> 'Projects done!'
				]
			);

			$this->add_control(
				'tag',
				[
					'label' => esc_html__( 'Tag', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'h1'    => esc_html__('H1','enovathemes-addons'),
						'h2'    => esc_html__('H2','enovathemes-addons'),
						'h3'    => esc_html__('H3','enovathemes-addons'),
						'h4'    => esc_html__('H4','enovathemes-addons'),
						'h5'    => esc_html__('H5','enovathemes-addons'),
						'h6'    => esc_html__('H6','enovathemes-addons'),
					],
					'default' => 'h1'
				]
			);

			$this->add_control(
				'delay',
				[
					'label' => esc_html__( 'Delay', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 5000,
					'step' => 1,
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
				'version',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Version', 'enovathemes-addons' ),
					'options' => [
						'default' => esc_html__('Default','enovathemes-addons'),
						'circle'  => esc_html__('Circle','enovathemes-addons'),
					],
					'default' => 'default'
				]
			);

			$this->add_control(
				'progress_color',
				[
					'label' => esc_html__( 'Progress color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-progress .bar' => 'background: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-progress .bar-circle' => 'stroke: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'track_color',
				[
					'label' => esc_html__( 'Progress track color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-progress .track' => 'background: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-progress .track-circle' => 'stroke: {{VALUE}}',
				  ],
				  'default' => '#f0f0f0'
				]
			);

			$this->add_control(
				'title_color',
				[
					'label' => esc_html__( 'Title color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-progress .title' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-progress.circle .percent' => 'color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'percent_typography',
					'label'    => esc_html__( 'Value typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} > .elementor-widget-container > .et-progress.circle .percent',
					'condition' => [
						'version' => 'circle',
					],
				]
			);

		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		$attributes = array();

		if(!is_numeric($percentage) || $percentage < 0){$percentage = "";}
		elseif ($percentage > 100) {$percentage = "100";}

		$attributes[] = 'data-delay="'.esc_attr($delay).'"';
		$attributes[] = 'data-percentage="'.absint($percentage).'"';

		if (isset($percentage) && !empty($percentage)) {

			$output .= '<div class="et-progress '.$version.'" '.implode(' ', $attributes).'>';

				if ($version == "circle") {

					$output .= '<div class="text">';
						$output .= '<span class="percent">0</span>';
			    		$output .='<'.$tag.' class="title">'.esc_html($title).'</'.$tag.'>';
					$output .= '</div>';

					$output .='<svg viewBox="0 0 56 56">';
                        $output .='<circle class="track-circle" cx="28" cy="28" r="27" />';
                        $output .='<circle class="bar-circle" cx="28" cy="28" r="27" />';
                    $output .='</svg>';

				} else {

					$output .= '<div class="text">';
			    		$output .='<'.$tag.' class="title">'.esc_html($title).'</'.$tag.'>';
					$output .= '</div>';

					$output .= '<div class="track-bar">';
						$output .= '<div class="bar" data-percent=""></div>';
						$output .= '<div class="track"></div>';
					$output .= '</div>';
				}
			$output .= '</div>';

		}

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			view.addRenderAttribute( 'wrapper', 'class', 'et-progress' );
			
			if ( settings.version.length) {
				view.addRenderAttribute( 'wrapper', 'class', settings.version );
			}

			view.addRenderAttribute( 'wrapper', 'data-percentage', settings.percentage );
			view.addRenderAttribute( 'wrapper', 'data-delay', settings.delay );

		#>

    	<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>

    		<# if ( settings.version == 'circle' ) { #>

					<div class="text">
						<span class="percent">0</span>
			    	<{{{ settings.tag }}} class="title">{{{ settings.title }}}</{{{ settings.tag }}}>
					</div>

					<svg viewBox="0 0 56 56">
            <circle class="track-circle" cx="28" cy="28" r="27" />
            <circle class="bar-circle" cx="28" cy="28" r="27" />
          </svg>

        <# } else { #>

					<div class="text">
			    		<{{{ settings.tag }}} class="title">{{{ settings.title }}}</{{{ settings.tag }}}>
					</div>

					<div class="track-bar">
						<div class="bar" data-percent=""></div>
						<div class="track"></div>
					</div>

				<# } #>

    	</div>

	<?php }

}