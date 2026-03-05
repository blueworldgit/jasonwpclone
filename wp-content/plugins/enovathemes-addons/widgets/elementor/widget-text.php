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
class Elementor_Widget_Text extends \Elementor\Widget_Base {

	public function get_script_depends() {
		return [ 'widget-text' ];
	}

	public function get_name() {
		return 'et_text';
	}

	public function get_title() {
		return esc_html__( 'Text', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-text';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return ['text'];
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
				        '{{WRAPPER}}' => 'justify-content: {{VALUE}};display:flex;',
				  ],
					'default' => ''
				]
			);

			$this->add_control(
				'text',
				[
					'label' => esc_html__( 'Text', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::WYSIWYG ,
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
				        '{{WRAPPER}} .et-text' => 'text-align: {{VALUE}}',
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
					'selector' => '{{WRAPPER}} .et-text, {{WRAPPER}} .et-text *'
				]
			);
		
			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-text, {{WRAPPER}} .et-text *' => 'color: {{VALUE}}',
				    ],
				    'default' => '#444444'
				]
			);

		$this->end_controls_section();

	}

	protected function render() {
		
		$output = '';
		
		$this->add_inline_editing_attributes( 'text', 'basic' );
		$settings = $this->get_settings_for_display();

		extract($settings);

		$class   = array();
		$class[] = 'et-text';

		if (isset($text) && !empty($text)) {
			$output .= '<div class="'.implode(" ",$class).'">';
				$output .= $text;
			$output .= '</div>';
		}

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#
			view.addRenderAttribute( 'wrapper', 'class', 'et-text' );

		#>
		<# view.addInlineEditingAttributes( 'text', 'basic' ); #>
		<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>
			{{{ settings.text }}}
		</div>
		
	<?php }

}