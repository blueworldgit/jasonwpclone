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
class Elementor_Widget_Mobile_Toggle extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-mobile-toggle'];
  }

	public function get_name() {
		return 'et_mobile_toggle';
	}

	public function get_title() {
		return esc_html__( 'Mobile Toggle', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-mobile-toggle';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'mobile toggle' ];
	}

	protected function register_controls() {


		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
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

			$this->add_control(
				'icon',
				[
					'label' => esc_html__( 'Mobile Toggle', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'default' => [
						'url' => THEME_IMG.'icons/mobile-toggle.svg',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle:before' => 'mask: url({{URL}}) no-repeat 50% 50%;-webkit-mask: url({{URL}}) no-repeat 50% 50%;',
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
				'size',
				[
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 1000,
					'step' => 1,
					'default' => 28,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle' => 'width: {{VALUE}}px;height: {{VALUE}}px;',
				  ],
				]
			);

			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle:before' => 'background: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'color_hover',
				[
					'label' => esc_html__( 'Color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle:hover:before' => 'background: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle' => 'background: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle:before' => '-webkit-mask-size: 50% !important;mask-size: 50% !important',
				  ],
				]
			);

			$this->add_control(
				'back_color_hover',
				[
					'label' => esc_html__( 'Background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle:hover' => 'background: {{VALUE}}',
				  ],
				]
			);

			$this->add_control(
				'border_color',
				[
					'label' => esc_html__( 'Border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle' => 'border:1px solid {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle:before' => '-webkit-mask-size: 50% !important;mask-size: 50% !important',
				  ],
				]
			);

			$this->add_control(
				'border_color_hover',
				[
					'label' => esc_html__( 'Border color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mobile-toggle:hover' => 'border:1px solid {{VALUE}}',
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
		$class[] = 'mobile-toggle';
		$class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;

		if (isset($icon) && !empty($icon)) {
			$output .= '<div class="'.implode(" ", $class).'"></div>';
		}

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			view.addRenderAttribute( 'wrapper', 'class', 'mobile-toggle' );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-default-'+settings.hide_default );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-sticky-'+settings.hide_sticky );

		#>

		<div {{{ view.getRenderAttributeString( "wrapper" ) }}}></div>
		
	<?php }

}