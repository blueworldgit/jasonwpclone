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
class Elementor_Widget_Mobile_Menu extends \Elementor\Widget_Base {

	public function get_name() {
		return 'et_mobile_menu';
	}

	public function get_title() {
		return esc_html__( 'Mobile menu', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-navigation-vertical';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'menu', 'navigation'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

		$menus = enovathemes_addons_get_all_menus();
		$menu_list = array(esc_html__('Choose','enovathemes-addons') => "");
		foreach ($menus as $menu => $attr) {
			$menu_list[$attr->name] = $attr->slug;
		}
		$menu_list = array_flip($menu_list);

		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'menu',
				[
					'label' => esc_html__( 'Menu name', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => $menu_list,
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

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'typography',
					'label'    => esc_html__( 'Typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} .mobile-menu .mi-link'
				]
			);

			$this->add_control(
				'menu_separator',
				[
					'label' => esc_html__( 'Items separator', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'menu_hover',
				[
					'label' => esc_html__( 'Underline menu item on hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'menu_separator_color',
				[
					'label' => esc_html__( 'Items separator color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'condition' => [
						'menu_separator' => 'true',
					],
					'selectors'     => [
				        '{{WRAPPER}} .mobile-menu li:before' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu li:after' => 'background: {{VALUE}}',
						'{{WRAPPER}} .mobile-menu li > a:before' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu li > a:after' => 'background: {{VALUE}}'
				    ]
				]
			);

			$this->add_control(
				'menu_color',
				[
					'label' => esc_html__( 'Menu color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .mobile-menu .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu .mi-link > .arrow' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu .mi-link > .menu-icon' => 'background: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

			$this->add_control(
				'menu_color_hover',
				[
					'label' => esc_html__( 'Menu color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .mobile-menu li:hover > .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu li:hover > .mi-link > .arrow' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu li:hover > .mi-link > .menu-icon' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu li.active > .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu li.active > .mi-link > .arrow' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .mobile-menu li.active > .mi-link > .menu-icon' => 'background: {{VALUE}}',
				    ],
					'default' => $main_color
				]
			);


		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		extract($settings);

		$menu_separator = empty($menu_separator) ? 'false' : $menu_separator;
		$menu_hover = empty($menu_hover) ? 'false' : $menu_hover;

		$output = '';

		$class   = array();
		$class[] = 'mobile-menu-container';
		$class[] = 'separator-'.$menu_separator;
		$class[] = 'underline-'.$menu_hover;

		if (empty($menu) || !isset($menu)) {
			if (has_nav_menu( 'header-menu' )) {
				$menu_arg = array(
					'theme_location'  => 'header-menu',
					'menu_class'      => 'mobile-menu et-clearfix',
					'container'       => 'nav',
					'container_class' => implode(" ", $class),
					'echo'            => false,
					'link_before'     => '<span class="txt">',
					'link_after'      => '</span><span class="arrow"></span>',
					'depth'           => 10,
					'walker'          => new et_scm_walker_light
				);
			}
		} else {
			$menu_arg = array(
				'menu'  => $menu,
				'menu_class'      => 'mobile-menu et-clearfix',
				'container'       => 'nav',
				'container_class' => implode(" ", $class),
				'echo'            => false,
				'link_before'     => '<span class="txt">',
				'link_after'      => '</span><span class="arrow"></span>',
				'depth'           => 10,
				'walker'          => new et_scm_walker_light
			);
		}

		$output .= wp_nav_menu($menu_arg);

		if (!empty($output)) {
			echo $output;
		}

	}

}