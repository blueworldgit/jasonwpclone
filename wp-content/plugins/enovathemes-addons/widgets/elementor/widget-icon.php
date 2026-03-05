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
class Elementor_Widget_Icon extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-icon'];
  }

	public function get_name() {
		return 'et_icon';
	}

	public function get_title() {
		return esc_html__( 'Icon', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-icon';
	}

	public function get_categories() {
		return [ 'header-builder', 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'icon' ];
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
				'link',
				[
					'label' => esc_html__( 'Icon link', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			$this->add_control(
				'icon',
				[
					'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon:before' => 'mask: url({{URL}});-webkit-mask: url({{URL}});',
				    ],
				    'default' => [
						'url' => THEME_IMG.'/elementor/icon.svg',
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


			$this->add_responsive_control(
				'size',
				[
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 1000,
					'step' => 1,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon' => 'width: {{VALUE}}px;height: {{VALUE}}px;',
				  ],
				]
			);

			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon:before' => 'background: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'color_hover',
				[
					'label' => esc_html__( 'Color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon:hover:before' => 'background: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon' => 'background: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'icon_full_back',
				[
					'label' => esc_html__( 'Icon full?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'default' => 'true',
					'condition' => [
						'back_color!' => '',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon:before' => '-webkit-mask-size: 50% !important;mask-size: 50% !important;',
				  ]
				]
			);

			$this->add_control(
				'icon_full_border',
				[
					'label' => esc_html__( 'Icon full?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'default' => 'true',
					'condition' => [
						'border_color!' => '',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon:before' => '-webkit-mask-size: 50% !important;mask-size: 50% !important;',
				  ]
				]
			);

			$this->add_control(
				'back_color_hover',
				[
					'label' => esc_html__( 'Background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon:hover' => 'background: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'border_color',
				[
					'label' => esc_html__( 'Border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon' => 'border-color:{{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'border_color_hover',
				[
					'label' => esc_html__( 'Border color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon:hover' => 'border-color:{{VALUE}}',
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

		$hide_default = empty($hide_default) ? 'false' : $hide_default;
		$hide_sticky  = empty($hide_sticky) ? 'false' : $hide_sticky;

		$output = '';

		$class   = array();
		$class[] = 'et-icon';
		$class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;

		if (
			(isset($back_color) && !empty($back_color)) ||
			(isset($border_color) && !empty($border_color)) ||
			(isset($settings['__globals__']['back_color']) && !empty($settings['__globals__']['back_color'])) ||
			(isset($settings['__globals__']['border_color']) && !empty($settings['__globals__']['border_color']))
		) {
			$class[] = 'full';
		}

		if (isset($icon) && !empty($icon)) {
			if(isset($link) && !empty($link)){
				$output .= '<a class="'.implode(" ", $class).'" href="'.esc_url($link).'"></a>';
			} else {
				$output .= '<div class="'.implode(" ", $class).'"></div>';
			}
		}

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			view.addRenderAttribute( 'wrapper', 'class', 'et-icon' );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-default-'+settings.hide_default );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-sticky-'+settings.hide_sticky );

		#>

		<div {{{ view.getRenderAttributeString( "wrapper" ) }}}></div>
		
	<?php }

}