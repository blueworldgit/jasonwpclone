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
class Elementor_Widget_Heading extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);
		if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
			wp_register_script( 'widget-heading', plugins_url('../../js/widget-heading.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
		}
	}

	public function get_script_depends() {
		return [ 'widget-heading' ];
	}

	public function get_name() {
		return 'et_heading';
	}

	public function get_title() {
		return esc_html__( 'Heading', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-heading';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'heading', 'text'];
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
				'text',
				[
					'label' => esc_html__( 'Text', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXTAREA ,
				]
			);

			$this->add_control(
				'link',
				[
					'label' => esc_html__( 'Link', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT ,
				]
			);

			$this->add_control(
				'target',
				[
					'label' => esc_html__( 'Target', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'_self'  => '_self',
						'_blank' => '_blank'
					],
					'default' => '_self'
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
						'p'    => esc_html__('p','enovathemes-addons'),
						'div'    => esc_html__('div','enovathemes-addons'),
					],
					'default' => 'h1'
				]
			);

			$this->add_responsive_control(
				'text_align',
				[
					'label' => esc_html__( 'Text align', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'left'    => esc_html__('Left','enovathemes-addons'),
						'right'    => esc_html__('Right','enovathemes-addons'),
						'center'    => esc_html__('Center','enovathemes-addons'),
						'justify'    => esc_html__('Justify','enovathemes-addons'),
					],
					'selectors'     => [
				        '{{WRAPPER}} .et-heading' => 'text-align: {{VALUE}}',
				    ],
					'default' => 'left'
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
					'label'    => esc_html__( 'Typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} .et-heading'
				]
			);
		
			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-heading' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .et-heading *' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .et-heading .icon' => 'background: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

			$this->add_control(
				'color_hover',
				[
					'label' => esc_html__( 'Color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-heading:hover' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .et-heading:hover *' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .et-heading:hover .icon' => 'background: {{VALUE}}',

				    ]
				]
			);

			$this->add_control(
				'background_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-heading' => 'background-color: {{VALUE}};',
				    ]
				]
			);

			$this->add_control(
				'background_color_hover',
				[
					'label' => esc_html__( 'Background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-heading:hover' => 'background-color: {{VALUE}}',
				    ]
				]
			);


		$this->end_controls_section();

		$this->start_controls_section(
			'icon',
			[
				'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'icon_file',
				[
					'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-heading .icon' => 'mask-image: url({{URL}});-webkit-mask-image: url({{URL}});width: 16px;height:16px;margin-right: 8px;',
				    ]
				]
			);

			$this->add_control(
				'icon_size',
				[
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 1000,
					'step' => 1,
					'condition' => [
						'icon_file!' => '',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-heading .icon' => 'width: {{VALUE}}px;height: {{VALUE}}px;',
				    ]
				]
			);

			$this->add_control(
				'icon_margin',
				[
					'label' => esc_html__( 'Margin', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 1000,
					'step' => 1,
					'condition' => [
						'icon_file!' => '',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-heading .icon' => 'margin-right: {{VALUE}}px;',
				    ]
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'animation',
			[
				'label' => esc_html__( 'Animation', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'animation_type',
				[
					'label' => esc_html__( 'Animation', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						''        => esc_html__( 'Choose', 'enovathemes-addons' ),
						'curtain' => esc_html__( 'Curtain', 'enovathemes-addons' ),
						'letter'  => esc_html__( 'Letter', 'enovathemes-addons' ),
						'words'   => esc_html__( 'Words', 'enovathemes-addons' ),
						'rows'    => esc_html__( 'Rows', 'enovathemes-addons' ),
					],
				]
			);

			$this->add_control(
				'animation_color',
				[
					'label' => esc_html__( 'Curtain color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-heading .curtain' => 'background-color: {{VALUE}}',
				    ],
				    'condition' => [
						'animation_type' => 'curtain',
					],
				]
			);

			$this->add_control(
				'delay',
				[
					'label' => esc_html__( 'Start delay in ms', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER ,
				]
			);

		$this->end_controls_section();

	}

	protected function render() {
		$this->add_inline_editing_attributes( 'text', 'basic' );
		$settings = $this->get_settings_for_display();

		extract($settings);

		$class   = array();
		$class[] = 'et-heading';

		if (!empty($animation_type)) {
			$class[] = 'animate-true';
			$class[] = $animation_type;
		}

		$output = '';

		if (isset($text) && !empty($text)) {
			$output .= '<'.$tag.' class="'.implode(" ",$class).'" data-delay="'.esc_attr(absint($delay)).'">';

				if (isset($link) && !empty($link)) {
					$output .= '<a href="'.esc_url($link).'" target="'.esc_attr($target).'"></a>';
				}

				$output .= '<span class="text-wrapper">';
					$output .= '<span class="text">';
						if (isset($icon_file) && !empty($icon_file['url'])) {
							$output .= '<span class="icon"></span>';
						}
						$output .= '<span '.$this->get_render_attribute_string( 'text' ).'>'.$text.'</span>';
					$output .= '</span>';
					if ($animation_type == "curtain") {
						$output .= '<span class="curtain"></span>';
					}
				$output .= '</span>';

			$output .= '</'.$tag.'>';
		}

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#
			view.addRenderAttribute( 'wrapper', 'class', 'et-heading' );

			if(settings.animation_type){
				view.addRenderAttribute( 'wrapper', 'class', settings.animation_type );
				view.addRenderAttribute( 'wrapper', 'class', 'animate-true' );
			}

			if(settings.delay){
				view.addRenderAttribute( 'wrapper', 'data-delay', settings.delay );
			}
		#>
		<# view.addInlineEditingAttributes( 'text', 'basic' ); #>
		<{{{ settings.tag }}} {{{ view.getRenderAttributeString( "wrapper" ) }}}>

			<# if ( settings.link ) { #>
				<a href="{{{ settings.link }}}" target="{{{ settings.target }}}"></a>
			<# } #>

			<span class="text-wrapper">
				<span class="text">
					<# if ( settings.icon_file && settings.icon_file['url'] ) { #><span class="icon"></span><# } #>
					<span {{{ view.getRenderAttributeString( 'text' ) }}}>{{{ settings.text }}}</span>
				</span>
			</span>

			<# if ( settings.animation_type == 'curtain' ) { #>
				<span class="curtain"></span>
			<# } #>

		</{{{ settings.tag }}}>
		
	<?php }

}