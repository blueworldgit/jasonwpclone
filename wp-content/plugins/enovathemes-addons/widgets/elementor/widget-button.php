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
class Elementor_Widget_Button extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-button', plugins_url('../../js/widget-button.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-button'];
  }

	public function get_name() {
		return 'et_button';
	}

	public function get_title() {
		return esc_html__( 'Button', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-button';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'button'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

		$megamenus = enovathemes_addons_megamenus_names();

		$menu_list = array(esc_html__('Choose','enovathemes-addons') => "");
		if (!is_wp_error($megamenus)) {
			foreach ($megamenus as $id => $title) {
				$menu_list[$title] = $id;
			}
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
				'button_text',
				[
					'label' => esc_html__( 'Button text', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default'=> 'Read more'
				]
			);

			$this->add_control(
				'button_link',
				[
					'label' => esc_html__( 'Button link', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default'=> '#your-link'
				]
			);

			$this->add_control(
				'target',
				[
					'label' => esc_html__( 'Target', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'_self' => esc_html__('_self','enovathemes-addons'),
						'_blank' => esc_html__('_blank','enovathemes-addons'),
					],
					'default' => '_self'
				]
			);

			$this->add_control(
				'button_link_modal',
				[
					'label' => esc_html__( 'Open link in modal window?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'click_smooth',
				[
					'label' => esc_html__( 'Smooth click animation', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'megamenu',
				[
					'label' => esc_html__( 'Megamenu', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => $menu_list,
					'default' => ''
				]
			);

			$this->add_control(
				'megamenu_ajax',
				[
					'label' => esc_html__( 'Load megamenu asynchronous?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'condition' => [
						'megamenu!' => '',
					]
				]
			);

			$this->add_control(
				'submenu_toggle',
				[
					'label' => esc_html__( 'Megamenu toggle on', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'hover' => esc_html__('Hover','enovathemes-addons'),
						'click' => esc_html__('Click','enovathemes-addons'),
					],
					'default' => 'hover'
				]
			);

			$this->add_control(
				'submenu_appear',
				[
					'label' => esc_html__( 'Megamenu appear effect', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'fade' => esc_html__('Fade','enovathemes-addons'),
						'transform' => esc_html__('Transform','enovathemes-addons'),
					],
					'default' => 'fade',
					'condition' => [
						'megamenu!' => '',
						'submenu_toggle' => 'hover'
					]
				]
			);

			$this->add_control(
				'submenu_shadow',
				[
					'label' => esc_html__( 'Megamenu shadow', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'condition' => [
						'megamenu!' => '',
					]
				]
			);

			$this->add_control(
				'submenu_offset',
				[
					'label' => esc_html__( 'Megamenu offset', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 1000,
					'step' => 1,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button + .megamenu' => 'padding-top: {{VALUE}}px;',
				  ],
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
					'selector' => '{{WRAPPER}} > .elementor-widget-container > .et-button',
					'fields_options' => [
	            'typography' => ['default' => 'yes'],
	            'font_size'  => ['default' => ['size' => 16]],
	            'line_height' => ['default' => ['size' => 20]],
	            'font_weight' => ['default' => 700],
	        ],
				]
			);

			$this->add_control(
				'button_size',
				[
					'label' => esc_html__( 'Button size', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'small' => esc_html__('Small','enovathemes-addons'),
						'medium' => esc_html__('Medium','enovathemes-addons'),
						'large' => esc_html__('Large','enovathemes-addons'),
						'custom' => esc_html__('custom','enovathemes-addons'),
					],
					'default' => 'medium'
				]
			);

			$this->add_control(
				'width',
				[
					'label' => esc_html__( 'Width', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 1000,
					'step' => 1,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button' => 'width: {{VALUE}}px;',
				    ],
					'condition' => [
						'button_size' => 'custom',
					]
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
				        '{{WRAPPER}} > .elementor-widget-container > .et-button' => 'height: {{VALUE}}px;',
				    ],
					'condition' => [
						'button_size' => 'custom',
					]
				]
			);

			$this->add_control(
				'button_type',
				[
					'label' => esc_html__( 'Button type', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'square'  => esc_html__('Square','enovathemes-addons'),
						'rounded' => esc_html__('Rounded','enovathemes-addons'),
						'round'   => esc_html__('Round','enovathemes-addons'),
					],
					'default' => 'rounded'
				]
			);

			$this->add_control(
				'button_shadow',
				[
					'label' => esc_html__( 'Button shadow', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'button_color',
				[
					'label' => esc_html__( 'Button color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-button > .icon' => 'background: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'button_background_color',
				[
					'label' => esc_html__( 'Button background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button > .button-back' => 'background-color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'button_border_color',
				[
					'label' => esc_html__( 'Button border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} > .elementor-widget-container > .et-button > .button-back' => 'border-color:{{VALUE}}',
				    ]
				]
			);

			$this->add_control(
				'button_border_width',
				[
					'label' => esc_html__( 'Button border width', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'selectors'     => [
				      '{{WRAPPER}} > .elementor-widget-container > .et-button > .button-back' => 'border-width:{{VALUE}}px;border-style:solid',
				    ]
				]
			);

			$this->add_control(
				'button_color_hover',
				[
					'label' => esc_html__( 'Button color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button:hover' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-button:hover > .icon' => 'background: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'button_background_color_hover',
				[
					'label' => esc_html__( 'Button background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button:hover > .button-back' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'button_border_color_hover',
				[
					'label' => esc_html__( 'Button border color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button:hover > .button-back' => 'border-color:{{VALUE}}',
				    ]
				]
			);

			$this->add_control(
				'animate_hover',
				[
					'label' => esc_html__( 'Hover animation', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'default' => esc_html__('Default','enovathemes-addons'),
						'scale'   => esc_html__('Scale','enovathemes-addons'),
					],
					'default' => 'default'
				]
			);

		$this->end_controls_section();


		$this->start_controls_section(
			'icon_styling',
			[
				'label' => esc_html__( 'Icons', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'icon',
				[
					'label' => esc_html__( 'Icon left', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button > .icon1' => 'mask-image: url({{URL}});-webkit-mask-image: url({{URL}});',
				    ]
				]
			);

			$this->add_control(
				'size',
				[
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 128,
					'step' => 1,
				  'selectors'     => [
				      '{{WRAPPER}} > .elementor-widget-container > .et-button > .icon1' => 'width: {{VALUE}}px;height: {{VALUE}}px;',
				    ],
				  'default' => 16,
				]

			);

			$this->add_control(
				'icon2',
				[
					'label' => esc_html__( 'Icon right', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-button > .icon2' => 'mask-image: url({{URL}});-webkit-mask-image: url({{URL}});',
				    ]
				]
			);

			$this->add_control(
				'size2',
				[
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 128,
					'step' => 1,
				  'selectors'     => [
				      '{{WRAPPER}} > .elementor-widget-container > .et-button > .icon2' => 'width: {{VALUE}}px;height: {{VALUE}}px;',
				    ],
				  'default' => 16,
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

		$hide_default      = empty($hide_default) ? 'false' : $hide_default;
		$hide_sticky       = empty($hide_sticky) ? 'false' : $hide_sticky;
		$megamenu_ajax     = empty($megamenu_ajax) ? 'false' : $megamenu_ajax;
		$submenu_appear    = empty($submenu_appear) ? 'false' : $submenu_appear;
		$submenu_toggle    = empty($submenu_toggle) ? 'false' : $submenu_toggle;
		$submenu_shadow    = empty($submenu_shadow) ? 'false' : $submenu_shadow;
		$button_link_modal = empty($button_link_modal) ? 'false' : $button_link_modal;
		$animate_hover     = empty($animate_hover) ? 'false' : $animate_hover;
		$click_smooth      = empty($click_smooth) ? 'false' : $click_smooth;
		$button_shadow     = empty($button_shadow) ? 'false' : $button_shadow;
		$target            = empty($settings['target']) ? '_self' : $settings['target'];
		$button_type       = empty($settings['button_type']) ? 'rounded' : $settings['button_type'];
		$button_size       = empty($settings['button_size']) ? 'medium' : $settings['button_size'];
		$size              = empty($settings['size']) ? 's' : $settings['size'];
		$size2             = empty($settings['size2']) ? 's-2' : $settings['size2'];

		$output = '';

		$attributes = array();
		$class      = array();
		
		$class[] = 'et-button';
    $class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;
    $class[] = 'megamenu-ajax-'.$megamenu_ajax;
    $class[] = 'submenu-appear-'.$submenu_appear;

    if (!empty($megamenu)) {
    	$class[] = 'submenu-toggle-'.$submenu_toggle;
    }

    $class[] = 'submenu-shadow-'.$submenu_shadow;
    $class[] = 'modal-'.$button_link_modal;
    $class[] = 'hover-'.$animate_hover;
    $class[] = 'smooth-'.$click_smooth;
    $class[] = 'shadow-'.$button_shadow;
		$class[] = $button_type;
		$class[] = $button_size;
		$class[] = $size;
		$class[] = $size2;

		if ($button_link_modal == "true") {
			$target = "_self";
		}

		if (isset($click_smooth) && $click_smooth == "true") {
			$class[] = 'click-smooth';
		}

		if (!empty($megamenu)) {
    	$class[] = 'mm-true';
    	$attributes[] = 'data-megamenu="'.esc_attr($megamenu).'"';
    }

		$attributes[] = 'target="'.esc_attr($target).'"';
		$attributes[] = 'href="'.esc_url($button_link).'"';
		$attributes[] = 'data-effect="'.esc_attr($animate_hover).'"';
		$attributes[] = 'class="'.implode(" ", $class).'"';

		if (isset($button_text) && !empty($button_text) && isset($button_link) && !empty($button_link)) {

			$output .='<a '.implode(" ", $attributes).' >';
				if (!empty($settings['icon']['url'])) {
					$output .= '<span class="icon icon1"></span>';
				}
				$output .='<span class="text">'.esc_attr($button_text).'</span>';
				if (!empty($settings['icon2']['url'])) {
					$output .= '<span class="icon icon2"></span>';
				}
				$output .='<span class="button-back"></span>';
			$output .='</a>';

			if (is_singular('header')) {
				$megamenu_ajax = 'false';
			}

			if (!empty($megamenu) && $megamenu_ajax != 'true') {

				$megamenus = enovathemes_addons_megamenus();

				if (!is_wp_error($megamenus) && isset($megamenus[$megamenu])) {

					$pluginElementor = \Elementor\Plugin::instance();

					$content = (is_plugin_active( 'elementor/elementor.php' )) ? $pluginElementor->frontend->get_builder_content($megamenu,false) : get_the_content($megamenu);

          $megamenu_html = '<div id="megamenu-'. $megamenu . '" '.implode(' ', $megamenus[$megamenu]['data']).'>';
              $megamenu_html .= do_shortcode($content);
          $megamenu_html .= '</div>';

					$output .= $megamenu_html;
				}
			}

		}

		if (!empty($output)) {
			echo $output;
		}

	}

}