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
class Elementor_Widget_Separator extends \Elementor\Widget_Base {


	public function get_name() {
		return 'et_separator';
	}

	public function get_title() {
		return esc_html__( 'Separator', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-divider';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'separator', 'divider'];
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
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-separator' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#e0e0e0'
				]
			);

			$this->add_control(
				'width',
				[
					'label' => esc_html__( 'Width in px (leave blank for 100%)', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER ,
					'selectors'     => [
				        '{{WRAPPER}} .et-separator' => 'width: {{VALUE}}px',
				    ],
				]
			);

			$this->add_control(
				'height',
				[
					'label' => esc_html__( 'Height in px', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER ,
					'selectors'     => [
				        '{{WRAPPER}} .et-separator' => 'height: {{VALUE}}px',
				    ],
					'default' => 1
				]
			);


		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		extract($settings);

		$class[] = 'et-separator';
		$class[] = 'et-clearfix';

		$output = '<div class="'.implode(" ", $class).'" ></div>';

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#
			view.addRenderAttribute( 'wrapper', 'class', 'et-separator' );
			view.addRenderAttribute( 'wrapper', 'class', 'et-clearfix' );

			if(settings.type){
				view.addRenderAttribute( 'wrapper', 'class', settings.type );
			}

		#>

		<div {{{ view.getRenderAttributeString( "wrapper" ) }}}></div>
		
	<?php }

}