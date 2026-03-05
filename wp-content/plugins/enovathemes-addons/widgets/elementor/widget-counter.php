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
class Elementor_Widget_Counter extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-counter', plugins_url('../../js/widget-counter.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-counter'];
  }

	public function get_name() {
		return 'et_counter';
	}

	public function get_title() {
		return esc_html__( 'Counter', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'counter'];
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
				'number',
				[
					'label' => esc_html__( 'Number', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'step' => 1,
					'default'=> 100

				]
			);

			$this->add_control(
				'prefix',
				[
					'label' => esc_html__( 'Number prefix', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			$this->add_control(
				'postfix',
				[
					'label' => esc_html__( 'Number postfix', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			$this->add_control(
				'text',
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
				'icon',
				[
					'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-counter .icon' => 'mask-image: url({{URL}});-webkit-mask-image: url({{URL}});width:72px;height:72px;',
				    ]
				]
			);

			$this->add_responsive_control(
				'icon_size',
				[
					'type' => \Elementor\Controls_Manager::NUMBER,
					'label' => esc_html__( 'Icon size', 'enovathemes-addons' ),
					'min' => 1,
					'max' => 500,
					'step' => 1,
					'selectors' => [
						'{{WRAPPER}} > .elementor-widget-container > .et-counter .icon' => 'width: {{VALUE}}px;height: {{VALUE}}px;'
					],
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

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'typography',
					'label'    => esc_html__( 'Number typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} > .elementor-widget-container > .et-counter .counter-value'
				]
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'top_typography',
					'label'    => esc_html__( 'Title typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} > .elementor-widget-container > .et-counter .counter-title'
				]
			);

			$this->add_control(
				'number_color',
				[
					'label' => esc_html__( 'Counter color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-counter .counter-value' => 'color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'title_color',
				[
					'label' => esc_html__( 'Title color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-counter .counter-title' => 'color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'icon_color',
				[
					'label' => esc_html__( 'Icon color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-counter .icon' => 'background: {{VALUE}}',
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

		$class   = array();
		$class[] = 'et-counter';

		if (isset($content_alignment) && !empty($content_alignment)) {
			$class[] = $content_alignment;
		}

		if (isset($icon) && !empty($icon)) {
			$class[] = 'icon';
		}

		$attributes   = array();
		$attributes[] = 'data-value="'.$number.'"';
		$attributes[] = 'data-delay="'.esc_attr($delay).'"';

		if (isset($number) && !empty($number)) {

	    	$output .='<div class="'.implode(' ', $class).'" '.implode(' ', $attributes).'>';

	    		$output .='<div class="et-counter-inner">';

	    			if (isset($icon) && !empty($icon)) {
							$output .= '<div class="counter-icon icon"></div>';
						}

						$output .='<div class="counter-content">';

			    		$output .='<div class="counter-value in">';

			    			if (isset($prefix) && !empty($prefix)) {
				    			$output .='<span class="prefix">'.esc_html($prefix).'</span>';
				    		}

				    		$output .='<span class="counter">0</span>';

			    			if (isset($postfix) && !empty($postfix)) {
				    			$output .='<span class="postfix">'.esc_html($postfix).'</span>';
				    		}

				    	$output .='</div>';

			    		if (isset($text) && !empty($text)) {
			    			$output .='<'.$tag.' class="counter-title in">'.esc_html($text).'</'.$tag.'>';
			    		}

			    	$output .='</div>';

	    		$output .='</div>';

	    	$output .='</div>';

		}

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			view.addRenderAttribute( 'wrapper', 'class', 'et-counter' );
			
			if ( settings.icon.length) {
				view.addRenderAttribute( 'wrapper', 'class', 'icon' );
			}

			if ( settings.content_alignment.length) {
				view.addRenderAttribute( 'wrapper', 'class', settings.content_alignment);
			}

			view.addRenderAttribute( 'wrapper', 'data-value', settings.number );
			view.addRenderAttribute( 'wrapper', 'data-delay', settings.delay );

		#>

    	<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>

    		<div class="et-counter-inner">

					<div class="counter-icon icon"></div>

					<div class="counter-content">

		    		<div class="counter-value in">

		    			<# if ( settings.prefix.length ) { #>
								<span class="prefix">{{{ settings.prefix }}}</span>
							<# } #>

			    		<span class="counter">0</span>

		    			<# if ( settings.postfix.length ) { #>
								<span class="posfix">{{{ settings.postfix }}}</span>
							<# } #>

			    	</div>

			    	<# if ( settings.text.length ) { #>
						<{{{ settings.tag }}} class="counter-title in">{{{ settings.text }}}</{{{ settings.tag }}}>
						<# } #>

		    	</div>

    		</div>

    	</div>

	<?php }

}