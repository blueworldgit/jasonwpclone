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
class Elementor_Widget_Desktop_Menu extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);
		if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
			wp_register_script( 'widget-desktop-menu', plugins_url('../../js/widget-desktop-menu.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
		}
	}

	public function get_script_depends() {
		return [ 'widget-desktop-menu' ];
	}

	public function get_name() {
		return 'et_desktop_menu';
	}

	public function get_title() {
		return esc_html__( 'Desktop menu', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'menu', 'navigation'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$accent_color = get_theme_mod('accent_color');

		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';
		$accent_color = (isset($accent_color) && !empty($accent_color)) ? $accent_color : '#bf3617';

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
				'menu',
				[
					'label' => esc_html__( 'Menu name', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => $menu_list,
					'default' => ''
				]
			);


			$this->add_control(
				'height',
				[
					'label' => esc_html__( 'Height', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 1000,
					'step' => 1,
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu' => 'height: {{VALUE}}px;',
				    ],
					'default' => '40'
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
					'selector' => '{{WRAPPER}} .nav-menu > .depth-0 > .mi-link',
					'fields_options' => [
			            'typography' => ['default' => 'yes'],
			            'font_size'  => ['default' => ['size' => 16]],
			            'line_height' => ['default' => ['size' => 20]],
			            'font_weight' => ['default' => 700],
			        ],
				]
			);

			$this->add_responsive_control(
				'menu_space',
				[
					'type' => \Elementor\Controls_Manager::SLIDER,
					'label' => esc_html__( 'Space between menu items', 'enovathemes-addons' ),
					'size_units' => [ 'px'],
					'range' => [
						'px' => [
							'min' => 0,
							'max' => 500,
							'step' => 1,
						],
					],
					'default' => [
						'unit' => 'px',
						'size' => 40,
					],
					'selectors' => [
						'{{WRAPPER}} .nav-menu > .depth-0' => 'padding-left: calc({{SIZE}}px / 2);padding-right: calc({{SIZE}}px / 2);',
				        '{{WRAPPER}} .nav-menu > .depth-0 > .sub-menu' => 'left: calc({{SIZE}}px / 2)',
				        '{{WRAPPER}} .nav-menu > .depth-0.submenu-left > .sub-menu' => 'right: calc({{SIZE}}px / 2)',
				        '{{WRAPPER}} .nav-menu > .depth-0 > .sub-menu[data-position="right"]' => 'right: calc({{SIZE}}px / 2)'
					],
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
				'menu_separator_color',
				[
					'label' => esc_html__( 'Items separator color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'condition' => [
						'menu_separator' => 'true',
					],
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .depth-0:before' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0:after' => 'background: {{VALUE}}'
				    ]
				]
			);

			$this->add_control(
				'menu_separator_height',
				[
					'label' => esc_html__( 'Items separator height', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 200,
					'step' => 1,
					'default' => 16,
					'condition' => [
						'menu_separator' => 'true',
					],
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .depth-0:before' => 'height: {{VALUE}}px',
				        '{{WRAPPER}} .nav-menu > .depth-0:after' => 'height: {{VALUE}}px'
				    ]
				]
			);

			$this->add_control(
				'submenu_indicator',
				[
					'label' => esc_html__( 'Submenu indicator', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'menu_color',
				[
					'label' => esc_html__( 'Menu color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .depth-0 > .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0 > .mi-link > .arrow' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0 > .mi-link > .menu-icon' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0.active.using > .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0.active.using > .mi-link > .menu-icon' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0.active.using > .mi-link > .arrow' => 'background: {{VALUE}}',
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
				        '{{WRAPPER}} .nav-menu > .depth-0:hover > .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0:hover > .mi-link > .arrow' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0:hover > .mi-link > .menu-icon' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0.active > .mi-link' => 'color: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0.active > .mi-link > .arrow' => 'background: {{VALUE}}',
				        '{{WRAPPER}} .nav-menu > .depth-0.active > .mi-link > .menu-icon' => 'background: {{VALUE}}',
				    ],
				    'default' => $accent_color
				]
			);

			$this->add_control(
				'menu_hover',
				[
					'label' => esc_html__( 'Menu hover effect', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						''    => esc_html__('Choose','enovathemes-addons'),
						'underline-default' => esc_html__('Underline static','enovathemes-addons'),
						'underline' => esc_html__('Underline','enovathemes-addons'),
						'overline'  => esc_html__('Overline','enovathemes-addons'),
						'outline'   => esc_html__('Outline','enovathemes-addons'),
						'box'       => esc_html__('Box','enovathemes-addons'),
						'fill'      => esc_html__('Fill','enovathemes-addons')
					],
					'default' => ''
				]
			);

			$this->add_control(
				'menu_effect_color',
				[
					'label' => esc_html__( 'Menu hover effect color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .depth-0 > .mi-link .effect' => 'background-color: {{VALUE}};box-shadow:inset 0 0 0 2px {{VALUE}};',
				    ],
					'default' => $accent_color
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
					'selector' => '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu .menu-item .mi-link',
					'fields_options' => [
			            'typography' => ['default' => 'yes'],
			            'font_size'  => ['default' => ['size' => 15]],
			            'line_height' => ['default' => ['size' => 28]],
			            'font_weight' => ['default' => 400],
			        ],
				]
			);

			$this->add_control(
				'submenuoffset',
				[
					'label' => esc_html__( 'Offset', 'enovathemes-addons' ),
					'description'=> esc_html__('Leave blank to have 100% offset','enovathemes-addons'),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 500,
					'step' => 1,
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .menu-item > .sub-menu' => 'top: {{VALUE}}%;',
				    ]
				]
			);

			$this->add_control(
				'submenu_color',
				[
					'label' => esc_html__( 'Submenu color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu .menu-item .mi-link' => 'color: {{VALUE}};',
				        '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu .menu-item .mi-link > .arrow' => 'background: {{VALUE}};',
				        '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu .menu-item .mi-link > .menu-icon' => 'background: {{VALUE}};',
				    ],
				    'default' => '#444444'
				]
			);

			$this->add_control(
				'submenu_color_hover',
				[
					'label' => esc_html__( 'Submenu color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu .menu-item:hover > .mi-link' => 'color: {{VALUE}};',
				        '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu .menu-item:hover .mi-link > .arrow' => 'background: {{VALUE}};',
				        '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu .menu-item:hover .mi-link > .menu-icon' => 'background: {{VALUE}};',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'submenu_back_color',
				[
					'label' => esc_html__( 'Submenu background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu' => 'background-color: {{VALUE}};',
				        '{{WRAPPER}} .nav-menu > .mm-true > .sub-menu' => 'background-color: {{VALUE}};',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'submenu_back_color_hover',
				[
					'label' => esc_html__( 'Submenu background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} .nav-menu > .menu-item:not(.mm-true) .sub-menu .menu-item:hover > .mi-link' => 'background-color: {{VALUE}};',
				    ]
				]
			);

			$this->add_control(
				'submenu_shadow',
				[
					'label' => esc_html__( 'Submenu shadow', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'default' => 'true'
				]
			);

			$this->add_control(
				'submenu_submenu_indicator',
				[
					'label' => esc_html__( 'Submenu indicator', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'submenu_appear',
				[
					'label' => esc_html__( 'Submenu appear effect', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'fade' => esc_html__('Fade','enovathemes-addons'),
						'transform' => esc_html__('Transform','enovathemes-addons')
					],
					'default' => 'fade'
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'visibility',
			[
				'label' => esc_html__( 'Visibility', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);


			$this->add_control(
				'hide_default',
				[
					'label' => esc_html__( 'Hide from default header version?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'hide_sticky',
				[
					'label' => esc_html__( 'Hide from sticky header version?', 'enovathemes-addons' ),
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

		extract($settings);

		$hide_default = empty($hide_default) ? 'false' : $hide_default;
		$hide_sticky = empty($hide_sticky) ? 'false' : $hide_sticky;
		$offset = empty($offset) ? 'false' : $offset;
		$submenu_shadow = empty($submenu_shadow) ? 'false' : $submenu_shadow;
		$submenu_indicator = empty($submenu_indicator) ? 'false' : $submenu_indicator;
		$submenu_submenu_indicator = empty($submenu_submenu_indicator) ? 'false' : $submenu_submenu_indicator;
		$menu_separator = empty($menu_separator) ? 'false' : $menu_separator;
		$menu_hover = empty($menu_hover) ? 'none' : $menu_hover;

		$output = '';

		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';
		$accent_color = (isset($accent_color) && !empty($accent_color)) ? $accent_color : '#bf3617';

		$class   = array();
		$class[] = 'header-menu-container';
		$class[] = 'nav-menu-container';
		$class[] = 'one-page-offset-'.$offset;
		$class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;
		$class[] = 'menu-hover-'.$menu_hover;
		$class[] = 'submenu-appear-'.$submenu_appear;
		$class[] = 'submenu-shadow-'.$submenu_shadow;
		$class[] = 'tl-submenu-ind-'.$submenu_indicator;
		$class[] = 'sl-submenu-ind-'.$submenu_submenu_indicator;
		$class[] = 'top-separator-'.$menu_separator;

		if($menu_hover == "underline") {
			$link_after  = '<span class="effect"></span></span><span class="arrow"></span>';
		} else {
			$link_after  = '</span><span class="arrow"></span><span class="effect"></span>';
		}

		$menu_color       = isset($menu_color) ? $menu_color : '#111111';
		$menu_color_hover = isset($menu_color_hover) ? $menu_color_hover : $accent_color;

		if (empty($menu) || !isset($menu)) {

			$menu_arg = array(
				'theme_location'  => 'header-menu',
				'menu_class'      => 'header-menu nav-menu hbe-inner et-clearfix',
				'container'       => 'nav',
				'container_class' => implode(" ", $class),
				'items_wrap'      => '<ul id="%1$s" class="%2$s" data-color="'.esc_attr($menu_color).'" data-color-hover="'.esc_attr($menu_color_hover).'">%3$s</ul>',
				'echo'            => false,
				'link_before'     => '<span class="txt">',
				'link_after'      => $link_after,
				'depth'           => 10,
				'walker'          => new et_scm_walker
			);

		} else {

			$menu_arg = array(
				'menu'  => $menu,
				'menu_class'      => 'header-menu nav-menu hbe-inner et-clearfix',
				'container'       => 'nav',
				'container_class' => implode(" ", $class),
				'items_wrap'      => '<ul id="%1$s" class="%2$s" data-color="'.esc_attr($menu_color).'" data-color-hover="'.esc_attr($menu_color_hover).'">%3$s</ul>',
				'echo'            => false,
				'link_before'     => '<span class="txt">',
				'link_after'      => $link_after,
				'depth'           => 10,
				'walker'          => new et_scm_walker
			);

		}

		$output .= wp_nav_menu($menu_arg);

		if (!empty($output)) {
			echo $output;
		}

	}

}