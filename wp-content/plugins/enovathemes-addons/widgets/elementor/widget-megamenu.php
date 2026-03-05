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
class Elementor_Widget_Megamenu extends \Elementor\Widget_Base {

	public function get_name() {
		return 'et_megamenu';
	}

	public function get_title() {
		return esc_html__( 'Megamenu', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-megamenu';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'menu', 'navigation','megamenu'];
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


			$this->add_control(
				'columns',
				[
					'label' => esc_html__( 'Columns', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'1'  => '1',
						'2'  => '2',
						'3'  => '3',
						'4'  => '4',
						'5'  => '5',
						'6'  => '6'
					],
					'default' => '3'
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'top_level',
			[
				'label' => esc_html__( 'Top level', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'top_typography',
					'label'    => esc_html__( 'Typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} .et-mm > .depth-0 > .mi-link'
				]
			);


			$this->add_control(
				'menu_color',
				[
					'label' => esc_html__( 'Menu color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-mm > .depth-0 > .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .et-mm > .depth-0 > .mi-link > .menu-icon' => 'background: {{VALUE}}',
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
				        '{{WRAPPER}} .et-mm > .depth-0:hover > .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .et-mm > .depth-0:hover > .mi-link > .menu-icon' => 'background: {{VALUE}}',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'megamenu_border_color',
				[
					'label' => esc_html__( 'Top level border bottom color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-mm > .depth-0 > .mi-link:before' => 'background-color: {{VALUE}}',
				    ]
				]
			);

			$this->add_control(
				'top_margin_bottom',
				[
					'label' => esc_html__( 'Top level margin bottom', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 500,
					'step' => 1,
					'selectors'     => [
				        '{{WRAPPER}} .et-mm > .depth-0 > .mi-link' => 'margin-bottom: {{VALUE}}px',
				    ]
				]
			);		

		$this->end_controls_section();


		$this->start_controls_section(
			'submenu',
			[
				'label' => esc_html__( 'Submenu', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name' => 'sub_typography',
					'label' => esc_html__( 'Typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} .et-mm .sub-menu .menu-item .mi-link'

				]
			);


			$this->add_control(
				'submenu_color',
				[
					'label' => esc_html__( 'Submenu color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-mm .sub-menu .menu-item .mi-link' => 'color: {{VALUE}};',
				        '{{WRAPPER}} .et-mm .sub-menu .menu-item .mi-link > .menu-icon' => 'background: {{VALUE}};',
				    ],
				    'default' => '#777777'
				]
			);

			$this->add_control(
				'submenu_color_hover',
				[
					'label' => esc_html__( 'Submenu color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-mm .sub-menu .menu-item:hover > .mi-link' => 'color: {{VALUE}};',
				        '{{WRAPPER}} .et-mm .sub-menu .menu-item:hover .mi-link > .menu-icon' => 'background: {{VALUE}};',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'submenu_back_color_hover',
				[
					'label' => esc_html__( 'Submenu background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .et-mm .sub-menu .menu-item:hover > .mi-link' => 'background-color: {{VALUE}};',
				    ]
				]
			);


			$this->add_control(
				'submenu_hover_underline',
				[
					'label' => esc_html__( 'Submenu hover underline', 'enovathemes-addons' ),
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

		$submenu_hover_underline = empty($submenu_hover_underline) ? 'false' : $submenu_hover_underline;

		extract($settings);

		$output      = '';

		$class   = array();
		$class[] = 'mm-container';
		$class[] = 'column-'.$columns;
		$class[] = 'submenu-hover-underline-'.$submenu_hover_underline;



		$menu_arg = array(
			'menu'  => $menu,
			'menu_class'      => 'et-mm et-clearfix',
			'container'       => 'div',
			'container_class' => implode(" ", $class),
			'echo'            => false,
			'link_before'     => '<span class="txt">',
			'link_after'      => '</span>',
			'depth'           => 3,
			'walker'          => new et_scm_walker
		);

		$output .= wp_nav_menu($menu_arg);

		if (!empty($output)) {
			echo $output;
		}

	}

}