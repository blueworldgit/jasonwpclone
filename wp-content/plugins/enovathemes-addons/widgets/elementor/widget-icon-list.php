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
class Elementor_Widget_Icon_List extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-icon-list'];
  }

	public function get_name() {
		return 'et_icon_list';
	}

	public function get_title() {
		return esc_html__( 'Icon List', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-editor-list-ul';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'icon list'];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'text', [
				'label' => esc_html__( 'Text', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Text' , 'enovathemes-addons' ),
				'label_block' => true,
			]
		);
		
		$this->add_control(
			'list',
			[
				'label' => esc_html__( 'Icon List', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'text' => esc_html__( 'Text #1', 'enovathemes-addons' ),
					],
				],
				'title_field' => '{{{ text }}}',
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
				'icon', [
					'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'selectors'     => [
				        '{{WRAPPER}} .icon-list-icon:before' => 'mask: url({{URL}}) no-repeat 50% 50%;-webkit-mask: url({{URL}}) no-repeat 50% 50%;',
				    ]
				]
			);

			$this->add_control(
				'icon_list_color',
				[
					'label' => esc_html__( 'Text color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .icon-list-item' => 'color: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

			$this->add_control(
				'icon_list_icon_color',
				[
					'label' => esc_html__( 'Icon list icon color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .icon-list-item .icon-list-icon:before' => 'background: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

			$this->add_control(
				'icon_size',
				[
					'label' => esc_html__( 'Icon size', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'selectors'     => [
				        '{{WRAPPER}} .icon-list-item .icon-list-icon' => 'min-width:{{VALUE}}px;width:{{VALUE}}px;height:{{VALUE}}px;margin-right:calc({{VALUE}}px / 2);',
				        '{{WRAPPER}} .icon-list-item ' => 'margin-bottom:calc({{VALUE}}px / 2);',
				    ],
				]
			);


			$this->add_control(
				'verticle_alignment',
				[
					'label' => esc_html__( 'Content vertical alignment', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						''    => esc_html__('Choose','enovathemes-addons'),
						'flex-start' => esc_html__('Start','enovathemes-addons'),
						'center'  => esc_html__('Center','enovathemes-addons'),
						'flex-end'   => esc_html__('End','enovathemes-addons'),
					],
					'selectors'     => [
				        '{{WRAPPER}} .icon-list-item' => 'align-items: {{VALUE}};',
				  ],
					'default' => 'center'
				]
			);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		$class = array();
		$class[] = 'et-icon-list';

		if ( $settings['list'] ) {
			$output.='<ul class="'.implode(' ', $class).'">';
				foreach (  $settings['list'] as $item ) {
					$output .= '<li class="icon-list-item"><span class="icon-list-icon"></span>' . esc_html($item['text']) . '</li>';
				}
			$output.='</ul>';
		}

		if (!empty($output)) {
			echo $output;
		}
	}


}