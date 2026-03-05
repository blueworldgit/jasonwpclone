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
class Elementor_Widget_Et_Mailchimp extends \Elementor\Widget_Base {

	public function get_name() {
		return 'et_mailchimp';
	}

	public function get_title() {
		return esc_html__( 'Mailchimp', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-mailchimp';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'mailchimp'];
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
				'orientation',
				[
					'label' => esc_html__( 'Orientation', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'horizontal' => esc_html__('Horizontal','enovathemes-addons'),
						'vertical' => esc_html__('Vertical','enovathemes-addons'),
					],
					'default' => 'horizontal'
				]
			);

			$this->add_control(
				'name',
				[
					'label' => esc_html__( 'Include name field?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'list',
				[
					'label' => esc_html__( 'Mailchimp audience ID', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => ''
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
				'mailchimp_color',
				[
					'label' => esc_html__( 'Mailchimp button color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp .et-button' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp .et-button span' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'mailchimp_background_color',
				[
					'label' => esc_html__( 'Mailchimp button background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp .et-button' => 'background-color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'mailchimp_border_color',
				[
					'label' => esc_html__( 'Mailchimp button border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp .et-button' => 'box-shadow:inset 0 0 0 1px {{VALUE}}',
				    ]
				]
			);

			$this->add_control(
				'mailchimp_color_hover',
				[
					'label' => esc_html__( 'Mailchimp button color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp .et-button:hover' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp .et-button:hover span' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'mailchimp_background_color_hover',
				[
					'label' => esc_html__( 'Mailchimp button background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp .et-button:hover' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'mailchimp_border_color_hover',
				[
					'label' => esc_html__( 'Mailchimp button border color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp .et-button:hover' => 'box-shadow:inset 0 0 0 1px {{VALUE}}',
				    ]
				]
			);

			$this->add_control(
				'mailchimp_fields_color',
				[
					'label' => esc_html__( 'Mailchimp fields color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp input' => 'color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'mailchimp_fields_background_color',
				[
					'label' => esc_html__( 'Mailchimp fields background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp input' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'mailchimp_fields_border_color',
				[
					'label' => esc_html__( 'Mailchimp fields border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp input' => 'border: 1px solid {{VALUE}}',
				    ],
				    'default' => '#e0e0e0'
				]
			);

			$this->add_control(
				'mailchimp_fields_color_focus',
				[
					'label' => esc_html__( 'Mailchimp fields color focus', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp input:focus' => 'color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'mailchimp_fields_background_color_focus',
				[
					'label' => esc_html__( 'Mailchimp fields background color focus', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp input:focus' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'mailchimp_fields_border_color_focus',
				[
					'label' => esc_html__( 'Mailchimp fields border color focus', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .et-mailchimp input:focus' => 'border: 1px solid {{VALUE}}',
				    ]
				]
			);

		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		extract($settings);

		$name  = empty($name) ? 'false' : 'true';

		$output = '';

		$args = array(
			'before_widget' => '<div class="et-mailchimp '.$orientation.' name-'.$name.' widget_mailchimp">',
			'after_widget'  => '</div>',
			'before_title'  => '',
      		'after_title'   => '',
		);

		$instance = array(
			'title'                => '',
 			'description'          => '',
 			'list'                 => $list,
 			'name'                 => $name,
 			'required_first_name'  => false,
		);

		$output .= enovathemes_addons_get_the_widget( 'Enovathemes_Addons_WP_Widget_Mailchimp', $instance,$args);

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			let name = (settings.name) ? 'true' : 'false';

			view.addRenderAttribute( 'wrapper', 'class', 'et-mailchimp' );
			view.addRenderAttribute( 'wrapper', 'class', 'name-'+name );
			view.addRenderAttribute( 'wrapper', 'class', 'widget_mailchimp' );
			view.addRenderAttribute( 'wrapper', 'class', settings.orientation );

		#>

		<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>
			<div class="mailchimp-form">
				<form class="et-mailchimp-form" name="et-mailchimp-form" action="/" method="POST">
					
					<# if ( settings.name.length ) { #>
						<div class="field-wrap">
							<input class="field" type="text" value="" name="fname" placeholder="<?php echo esc_html__('First name','enovathemes-addons') ?>">
						</div>
					<# } #>

					<div class="field-wrap">
						<input type="text" value="" class="field" name="email" placeholder="<?php echo esc_html__('Email address','enovathemes-addons') ?>">
					</div>

					<div class="send-div">
						<button type="submit" class="button et-button" name="subscribe"><?php echo esc_html__('Subscribe','enovathemes-addons') ?><span class="icon"></span></button>
					</div>
				</form>
			</div>
		</div>
		
	<?php }

}