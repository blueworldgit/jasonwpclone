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
class Elementor_Widget_Tabs extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);
		if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
			wp_register_script( 'widget-tabs', plugins_url('../../js/widget-tabs.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
		}
	}

  public function get_script_depends() {
    return [ 'widget-tabs'];
  }

	public function get_name() {
		return 'et_tabs';
	}

	public function get_title() {
		return esc_html__( 'Tabs', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-tabs';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'tabs'];
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
			        '{{WRAPPER}} {{CURRENT_ITEM}} .tab-icon' => 'mask: url({{URL}}) no-repeat 50% 50%;-webkit-mask: url({{URL}}) no-repeat 50% 50%;margin-right: 8px;width: 20px;height: 20px;',
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
				'label' => esc_html__( 'Tabs', 'enovathemes-addons' ),
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
				'tabs_tab_color',
				[
					'label' => esc_html__( 'Tab color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .tab-item' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .tab-item .tab-icon' => 'background: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

			$this->add_control(
				'tabs_tab_color_active',
				[
					'label' => esc_html__( 'Tabs tab color active', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .tab-item.active' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .tab-item.active .tab-icon' => 'background: {{VALUE}}',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'tabs_tab_border_color',
				[
					'label' => esc_html__( 'Tabs tab border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .tab-item' => 'border-color: {{VALUE}}',
				    ],
				    'default' => '#e0e0e0'
				]
			);

			$this->add_control(
				'tabs_tab_border_color_active',
				[
					'label' => esc_html__( 'Tabs tab border color active', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .tab-item.active' => 'border-color: {{VALUE}}',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'tabs_tab_background_color',
				[
					'label' => esc_html__( 'Tabs tab background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .tab-item' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'tabs_tab_background_color_active',
				[
					'label' => esc_html__( 'Tabs tab background color active', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .tab-item.active' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'type',
				[
					'label' => esc_html__( 'Type', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'horizontal' => esc_html__('Horizontal','enovathemes-addons'),
						'vertical' => esc_html__('Vertical','enovathemes-addons'),
						'center' => esc_html__('Center','enovathemes-addons'),
					],
					'default' => 'horizontal'
				]
			);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$output = $tabset = $tabcontent = '';

		if ( $settings['list'] ) {
			$output.='<div class="et-tabs '.$settings["type"].'">';

				foreach (  $settings['list'] as $item ) {
					$tabset     .= '<div class="tab tab-item '.$item['active'].' elementor-repeater-item-' . esc_attr( $item['_id'] ) . '"><span class="tab-icon"></span>' . esc_html($item['title']) . '</div>';
					$tabcontent .= '<div class="tab-content '.$item['active'].'">' . $item['content'] . '</div>';
				}

				$output.='<div class="tabset">'.$tabset.'</div>';
				$output.='<div class="tabs-container et-clearfix">'.$tabcontent.'</div>';

			$output.='</div>';
		}

		if (!empty($output)) {
			echo $output;
		}
	}

	protected function content_template() {
		?>
		<# if ( settings.list.length ) { #>
			<div class="et-tabs {{{ settings.type }}}">
					<div class="tabset">
						<# _.each( settings.list, function( item ) { #>
							<div class="tab tab-item {{{ item.active }}} elementor-repeater-item-{{ item._id }}"><span class="tab-icon"></span>{{{ item.title }}}</div>
						<# }); #>
					</div>
					<div class="tabs-container">
						<# _.each( settings.list, function( item ) { #>
							<div class="tab-content {{{ item.active }}}">{{{ item.content }}}</div>
						<# }); #>
					</div>
			</div>
		<# } #>
		<?php
	}

}