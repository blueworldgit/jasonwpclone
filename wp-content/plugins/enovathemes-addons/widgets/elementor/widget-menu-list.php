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
class Elementor_Widget_Menu_List extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-menu-list'];
  }

	public function get_name() {
		return 'et_menu_list';
	}

	public function get_title() {
		return esc_html__( 'Menu List', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'menu list'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';


		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'text', [
				'label' => esc_html__( 'Text', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Text' , 'enovathemes-addons' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'link', [
				'label' => esc_html__( 'Link', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( '#link' , 'enovathemes-addons' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'highlight',
			[
				'label' => esc_html__( 'highlight ?', 'enovathemes-addons' ),
				'description' => esc_html__( 'Activate if last item', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
				'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
				'return_value' => 'true',
				'default' => 'false',
			]
		);

		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
		
		$this->add_control(
			'list',
			[
				'label' => esc_html__( 'Menu List', 'enovathemes-addons' ),
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

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'typography',
					'label'    => esc_html__( 'Typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} .menu-list-item'
				]
			);

			$this->add_control(
				'menu_list_color',
				[
					'label' => esc_html__( 'Link color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .menu-list-item' => 'color: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

			$this->add_control(
				'menu_list_color_hover',
				[
					'label' => esc_html__( 'Link color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .menu-list-item:hover' => 'color: {{VALUE}}',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'menu_list_color_highlight',
				[
					'label' => esc_html__( 'Highlight color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .menu-list-item.highlight' => 'color: {{VALUE}}',
				      '{{WRAPPER}} .menu-list-item.highlight:after' => 'background: {{VALUE}}',
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

		$class = array();
		$class[] = 'menu-list-item';


		if ( $settings['list'] ) {
			$output.='<ul class="et-menu-list">';
				foreach (  $settings['list'] as $item ) {
					if (isset($item['link'])) {

						if ($item['highlight'] == "true") {
							$class[] = 'highlight';
						}

						$output .= '<li><a href="'.esc_url($item['link']).'" class="'.implode(' ', $class).'">' . esc_html($item['text']) . '</a></li>';
					}
				}
			$output.='</ul>';
		}

		if (!empty($output)) {
			echo $output;
		}
	}

	protected function content_template() {
		?>
		<# if ( settings.list.length ) { #>

		<#
		
			view.addRenderAttribute( 'wrapper', 'class', 'et-menu-list' );

		#>	


		<ul {{{ view.getRenderAttributeString( "wrapper" ) }}}>
			<# _.each( settings.list, function( item ) { #>
				<# if ( item.highlight == "true" ) { #>
					<li><a class="menu-list-item highlight" href="{{{ item.link }}}">{{{ item.text }}}</a></li>
				<# }else {#>
					<li><a class="menu-list-item" href="{{{ item.link }}}">{{{ item.text }}}</a></li>
				<# }#>
			<# }); #>
			</ul>
		<# } #>
		<?php
	}

}