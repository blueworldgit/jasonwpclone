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
class Elementor_Widget_Et_Accordion extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);
		if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
			wp_register_script( 'widget-accordion', plugins_url('../../js/widget-accordion.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
		}
	}

  public function get_script_depends() {
    return [ 'widget-accordion'];
  }

	public function get_name() {
		return 'et_accordion';
	}

	public function get_title() {
		return esc_html__( 'Accordion', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'accordion'];
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

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Title' , 'enovathemes-addons' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'selectors'     => [
			        '{{WRAPPER}} {{CURRENT_ITEM}} .accordion-icon' => 'mask: url({{URL}}) no-repeat 50% 50%;-webkit-mask: url({{URL}}) no-repeat 50% 50%;margin-right: 8px;width: 20px;height: 20px;',
			    ]
			]
		);

		$repeater->add_control(
			'content', [
				'label' => esc_html__( 'Content', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::WYSIWYG,
				'default' => esc_html__( 'Content here' , 'enovathemes-addons' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'active', [
				'label' => esc_html__( 'Active', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'active',
			]
		);

		$this->add_control(
			'list',
			[
				'label' => esc_html__( 'Accordion', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'title' => esc_html__( 'Title #1', 'enovathemes-addons' ),
						'content' => esc_html__( 'Content #1 goes here', 'enovathemes-addons' ),
						'icon' => '',
					],
					[
						'title' => esc_html__( 'Title #2', 'enovathemes-addons' ),
						'content' => esc_html__( 'Content #2 goes here', 'enovathemes-addons' ),
						'icon' => '',
					],
					[
						'title' => esc_html__( 'Title #3', 'enovathemes-addons' ),
						'content' => esc_html__( 'Content #3 goes here', 'enovathemes-addons' ),
						'icon' => '',
					],
				],
				'title_field' => '{{{ title }}}',
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
				'accordion_accordion_color',
				[
					'label' => esc_html__( 'Accordion color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .accordion-title' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .accordion-title .accordion-icon' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .accordion-title:after' => 'background: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

			$this->add_control(
				'accordion_accordion_color_active',
				[
					'label' => esc_html__( 'Accordion accordion color active', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .accordion-title.active' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .accordion-title.active .accordion-icon' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .accordion-title.active:after' => 'background: {{VALUE}}',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'accordion_accordion_border_color',
				[
					'label' => esc_html__( 'Accordion accordion border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .accordion-title' => 'border-color: {{VALUE}}',
				        '{{WRAPPER}} .accordion-content' => 'border-color: {{VALUE}}',
				    ],
				    'default' => '#e0e0e0'
				]
			);

			$this->add_control(
				'accordion_accordion_border_color_active',
				[
					'label' => esc_html__( 'Accordion accordion border color active', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .accordion-title.active' => 'border-color: {{VALUE}}',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'accordion_accordion_background_color',
				[
					'label' => esc_html__( 'Accordion accordion background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .accordion-title' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'accordion_accordion_background_color_active',
				[
					'label' => esc_html__( 'Accordion accordion background color active', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .accordion-title.active' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'type',
				[
					'label' => esc_html__( 'Collapsible', 'enovathemes-addons' ),
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

		$output = '';


		if ( $settings['list'] ) {
			$output.='<div class="et-accordion collapsible-'.$settings['type'].'">';

				foreach (  $settings['list'] as $item ) {
					$output .= '<div class="accordion-title '.$item['active'].' elementor-repeater-item-' . esc_attr( $item['_id'] ) . '"><span class="accordion-icon"></span>' . esc_html($item['title']) . '</div>';
					$output .= '<div class="accordion-content '.$item['active'].'">' . $item['content'] . '</div>';
				}
			
			$output.='</div>';
		}

		if (!empty($output)) {
			echo $output;
		}
	}

	protected function content_template() {
		?>
		<# if ( settings.list.length ) { #>
			<div class="et-accordion collapsible-{{{ settings.type }}}">
					<# _.each( settings.list, function( item ) { #>
						<div class="accordion-title {{{ item.active }}} elementor-repeater-item-{{ item._id }}"><span class="accordion-icon"></span>{{{ item.title }}}</div>
						<div class="accordion-content {{{ item.active }}}">{{{ item.content }}}</div>
					<# }); #>
			</div>
		<# } #>
		<?php
	}

}