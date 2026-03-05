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
class Elementor_Widget_Icon_Box extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-icon-box', plugins_url('../../js/widget-icon-box.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-icon-box'];
  }

	public function get_name() {
		return 'et_icon_box';
	}

	public function get_title() {
		return esc_html__( 'Icon box', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-icon-box';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'icon box'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

		$this->start_controls_section(
			'icon_settings',
			[
				'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
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
				'icon',
				[
					'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon:before' => 'mask-image: url({{URL}});-webkit-mask-image: url({{URL}});',
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon.original:before' => 'background-image: url({{URL}});',
				    ],
				    'default' => [
						'url' => THEME_IMG.'/elementor/icon.svg',
					]
				]
			);

			$this->add_control(
				'icon_original',
				[
					'label' => esc_html__( 'Icon original colors?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_responsive_control(
				'icon_size',
				[
					'type' => \Elementor\Controls_Manager::NUMBER,
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'range' => [
						'px' => [
							'min' => 0,
							'max' => 128
						],
					],
					'desktop_default' => 72,
					'selectors' => [
						'{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon' => 'width: {{VALUE}}px;min-width: {{VALUE}}px;height: {{VALUE}}px;'
					],
				]
			);
		
			$this->add_responsive_control(
				'icon_margin',
				[
					'type' => \Elementor\Controls_Manager::NUMBER,
					'label' => esc_html__( 'Icon margin bottom', 'enovathemes-addons' ),
					'min' => 0,
					'max' => 500,
					'step' => 1,
					'selectors' => [
						'{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon' => 'margin-bottom: {{VALUE}}px !important;'
					],
				]
			);

			$this->add_responsive_control(
				'icon_y_offset',
				[
					'type' => \Elementor\Controls_Manager::SLIDER,
					'label' => esc_html__( 'Icon vertical offset', 'enovathemes-addons' ),
					'size_units' => [ 'px'],
					'range' => [
						'px' => [
							'min' => -100,
							'max' => 100,
							'step' => 1,
						],
					],
					'selectors' => [
						'{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon' => 'transform:translateY({{SIZE}}{{UNIT}});'
					],
				]
			);
		
			$this->add_control(
				'icon_position',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon position', 'enovathemes-addons' ),
					'options' => [
						'left'     => esc_html__('Left','enovathemes-addons'),
						'top'      => esc_html__('Top','enovathemes-addons'),
						'right'    => esc_html__('Right','enovathemes-addons'),
					],
					'default' => 'top'
				]
			);

			$this->add_control(
				'icon_position_tablet',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon position tablet', 'enovathemes-addons' ),
					'options' => [
						'inherit'  => esc_html__('Inherit','enovathemes-addons'),
						'left'     => esc_html__('Left','enovathemes-addons'),
						'top'      => esc_html__('Top','enovathemes-addons'),
						'right'    => esc_html__('Right','enovathemes-addons'),
					],
					'default' => 'inherit'
				]
			);

			$this->add_control(
				'icon_position_mobile',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon position mobile', 'enovathemes-addons' ),
					'options' => [
						'inherit'  => esc_html__('Inherit','enovathemes-addons'),
						'left'  => esc_html__('Left','enovathemes-addons'),
						'top'   => esc_html__('Top','enovathemes-addons'),
						'right' => esc_html__('Right','enovathemes-addons'),
					],
					'default' => 'inherit'
				]
			);

			$this->add_control(
				'icon_alignment',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon alignment', 'enovathemes-addons' ),
					'options' => [
						'left'  => esc_html__('Left','enovathemes-addons'),
						'center'   => esc_html__('Center','enovathemes-addons'),
						'right' => esc_html__('Right','enovathemes-addons'),
					],
					'default' => 'center'
				]
			);

			$this->add_control(
				'icon_alignment_tablet',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon alignment tablet', 'enovathemes-addons' ),
					'options' => [
						'inherit'  => esc_html__('Inherit','enovathemes-addons'),
						'left'  => esc_html__('Left','enovathemes-addons'),
						'center'   => esc_html__('Center','enovathemes-addons'),
						'right' => esc_html__('Right','enovathemes-addons'),
					],
					'default' => 'inherit'
				]
			);

			$this->add_control(
				'icon_alignment_mobile',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon alignment mobile', 'enovathemes-addons' ),
					'options' => [
						'inherit'  => esc_html__('Inherit','enovathemes-addons'),
						'left'  => esc_html__('Left','enovathemes-addons'),
						'center'   => esc_html__('Center','enovathemes-addons'),
						'right' => esc_html__('Right','enovathemes-addons'),
					],
					'default' => 'inherit'
				]
			);

			$this->add_control(
				'icon_vertical_alignment',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon vertical alignment', 'enovathemes-addons' ),
					'options' => [
						'top'  => esc_html__('Top','enovathemes-addons'),
						'center'   => esc_html__('Middle','enovathemes-addons'),
						'bottom' => esc_html__('Bottom','enovathemes-addons'),
					],
					'default' => 'top',
					'condition' => [
						'icon_position' =>['left','right'],
					],
				]
			);

			$this->add_control(
				'icon_vertical_alignment_tablet',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon vertical alignment tablet', 'enovathemes-addons' ),
					'options' => [
						'inherit'  => esc_html__('Inherit','enovathemes-addons'),
						'top'  => esc_html__('Top','enovathemes-addons'),
						'center'   => esc_html__('Middle','enovathemes-addons'),
						'bottom' => esc_html__('Bottom','enovathemes-addons'),
					],
					'condition' => [
						'icon_position_tablet' =>['left','right'],
					],
					'default' => 'inherit'
				]
			);

			$this->add_control(
				'icon_vertical_alignment_mobile',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Icon vertical alignment mobile', 'enovathemes-addons' ),
					'options' => [
						'inherit'  => esc_html__('Inherit','enovathemes-addons'),
						'top'  => esc_html__('Top','enovathemes-addons'),
						'center'   => esc_html__('Middle','enovathemes-addons'),
						'bottom' => esc_html__('Bottom','enovathemes-addons'),
					],
					'condition' => [
						'icon_position_mobile' =>['left','right'],
					],
					'default' => 'inherit'
				]
			);

			$this->add_control(
				'icon_color',
				[
					'label' => esc_html__( 'Icon color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon:before' => 'background: {{VALUE}};',
				  ],
				  'condition' => [
						'icon_original!' => 'true',
					],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'icon_background_color',
				[
					'label' => esc_html__( 'Icon background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
				  'condition' => [
						'icon_original!' => 'true',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon' => 'background: {{VALUE}};',
				  ],
				]
			);

			$this->add_control(
				'icon_full_back',
				[
					'label' => esc_html__( 'Icon full?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'default' => 'true',
					'condition' => [
						'icon_background_color!' => '',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon:before' => '-webkit-mask-size: 50% !important;mask-size: 50% !important;',
				  ]
				]
			);

			$this->add_control(
				'icon_full_border',
				[
					'label' => esc_html__( 'Icon full?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'default' => 'true',
					'condition' => [
						'icon_border_color!' => '',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon:before' => '-webkit-mask-size: 50% !important;mask-size: 50% !important;border-style: solid;border-width: 1px;',
				  ]
				]
			);

			$this->add_control(
				'icon_border_color',
				[
					'label' => esc_html__( 'Icon border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-icon-box .icon' => 'border-color:{{VALUE}};',
				    ]
				]
			);

			$this->add_control(
				'icon_color_hover',
				[
					'label' => esc_html__( 'Icon color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container:hover > .et-icon-box .icon:before' => 'background: {{VALUE}};',
				  ],
				  'condition' => [
						'icon_original!' => 'true',
					],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'icon_background_color_hover',
				[
					'label' => esc_html__( 'Icon background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'condition' => [
						'icon_original!' => 'true',
					],
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container:hover > .et-icon-box .icon' => 'background: {{VALUE}};',
				  ],
				]
			);

			$this->add_control(
				'icon_border_color_hover',
				[
					'label' => esc_html__( 'Icon border color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container:hover > .et-icon-box .icon' => 'border-color:{{VALUE}};',

				    ]
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
				'animation',
				[
					'label' => esc_html__( 'Hover animation', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'' => esc_html__('Choose','enovathemes-addons'),
						'transform' => esc_html__('Transform','enovathemes-addons'),
						'scale'     => esc_html__('Icon scale','enovathemes-addons'),
					],
				]
			);

			$this->add_control(
				'tag',
				[
					'label' => esc_html__( 'Title tag', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'h1'    => esc_html__('H1','enovathemes-addons'),
						'h2'    => esc_html__('H2','enovathemes-addons'),
						'h3'    => esc_html__('H3','enovathemes-addons'),
						'h4'    => esc_html__('H4','enovathemes-addons'),
						'h5'    => esc_html__('H5','enovathemes-addons'),
						'h6'    => esc_html__('H6','enovathemes-addons'),
					],
					'default' => 'h4'
				]
			);

			$this->add_responsive_control(
				'title_margin',
				[
					'type' => \Elementor\Controls_Manager::NUMBER,
					'label' => esc_html__( 'Title margin bottom', 'enovathemes-addons' ),
					'min' => 1,
					'max' => 500,
					'step' => 0,
					'desktop_default' => 16,
					'selectors' => [
						'{{WRAPPER}} > .elementor-widget-container .icon-box-title' => 'margin-bottom: {{VALUE}}px;'
					],
				]
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'typography',
					'label'    => esc_html__( 'Title typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} > .elementor-widget-container .icon-box-title'
				]
			);

			$this->add_control(
				'title_color',
				[
					'label' => esc_html__( 'Title color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .icon-box-title' => 'color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'title_color_hover',
				[
					'label' => esc_html__( 'Title color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container:hover .icon-box-title' => 'color: {{VALUE}}',
				  ]
				]
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name'     => 'typography-content',
					'label'    => esc_html__( 'Content typography', 'enovathemes-addons' ),
					'selector' => '{{WRAPPER}} > .elementor-widget-container .icon-box-content, {{WRAPPER}} > .elementor-widget-container .icon-box-content *'
				]
			);

			$this->add_control(
				'content_color',
				[
					'label' => esc_html__( 'Content color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .icon-box-content' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .icon-box-content *' => 'color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'content_color_hover',
				[
					'label' => esc_html__( 'Content color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container:hover .icon-box-content' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container:hover .icon-box-content *' => 'color: {{VALUE}}',
				  ]
				]
			);

			$this->add_control(
				'box_shadow',
				[
					'label' => esc_html__( 'Box shadow?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container' => 'box-shadow:0 0 24px rgba(0,0,0,0.1)',
				  ]
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'title',
				[
					'label' => esc_html__( 'Title', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default'=> 'Title goes here'
				]
			);

			$this->add_control(
				'link',
				[
					'label' => esc_html__( 'Link', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default'=> ''
				]
			);

			$this->add_control(
				'content',
				[
					'label' => esc_html__( 'Content', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::WYSIWYG,
					'default'=> 'Content goes here'
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
		$hide_sticky  = empty($hide_sticky) ? 'false' : $hide_sticky;

		$output = '';

		$attributes = array();
		$class      = array();
		
		$class[] = 'et-icon-box';
    $class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;

		if(isset($icon_position) && !empty($icon_position)){
			$class[] = 'icon-position-'.$icon_position;
		}

		if(isset($icon_position_tablet) && !empty($icon_position_tablet)){
			$class[] = 'icon-position-tablet-'.$icon_position_tablet;
		}

		if(isset($icon_position_mobile) && !empty($icon_position_mobile)){
			$class[] = 'icon-position-mobile-'.$icon_position_mobile;
		}
		
		if(isset($icon_alignment) && !empty($icon_alignment)){
			$class[] = 'icon-alignment-'.$icon_alignment;
		}

		if(isset($icon_alignment_tablet) && !empty($icon_alignment_tablet)){
			$class[] = 'icon-alignment-tablet-'.$icon_alignment_tablet;
		}

		if(isset($icon_alignment_mobile) && !empty($icon_alignment_mobile)){
			$class[] = 'icon-alignment-mobile-'.$icon_alignment_mobile;
		}

		if(isset($icon_vertical_alignment) && !empty($icon_vertical_alignment)){
			$class[] = 'icon-vertical-alignment-'.$icon_vertical_alignment;
		}

		if(isset($icon_vertical_alignment_tablet) && !empty($icon_vertical_alignment_tablet)){
			$class[] = 'icon-vertical-alignment-tablet-'.$icon_vertical_alignment_tablet;
		}

		if(isset($icon_vertical_alignment_mobile) && !empty($icon_vertical_alignment_mobile)){
			$class[] = 'icon-vertical-alignment-mobile-'.$icon_vertical_alignment_mobile;
		}

		if (isset($icon_size) && !empty($icon_size) && $icon_size <= 40) {
			$class[] = 'margin-small';
		}

		if (
			(isset($icon_background_color) && !empty($icon_background_color)) ||
			(isset($icon_border_color) && !empty($icon_border_color)) ||
			(isset($settings['__globals__']['icon_background_color']) && !empty($settings['__globals__']['icon_background_color'])) ||
			(isset($settings['__globals__']['icon_border_color']) && !empty($settings['__globals__']['icon_border_color']))
		) {
			$class[] = 'full';
		}

		if (!isset($content) || empty($content)) {
			$class[] = 'no-content';
		} else {
			$class[] = 'with-content';
		}

		if (isset($animation) && !empty($animation)) {
				$class[] = $animation;
		}

		$output .='<div class="'.implode(" ", $class).'" >';

			if (isset($link) && !empty($link)) {
				$output .= '<a href="'.esc_url($link).'">';
			}

				if (isset($icon) && !empty($icon)) {
					$output .= (($icon_original == "true") ? '<div class="icon original"></div>' : '<div class="icon"></div>');
				}

				$output .= '<div class="icon-box-content-wrap">';

					if (isset($title) && !empty($title)) {
						$output .= '<'.$tag.' class="icon-box-title">'.esc_html($title).'</'.$tag.'>';
					}

					if (isset($content) && !empty($content)) {
						$output .= '<div class="icon-box-content">'.$content.'</div>';
					}

				$output .='</div>';

			if (isset($link) && !empty($link)) {
				$output .= '</a>';
			}

		$output .='</div>';

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			view.addRenderAttribute( 'wrapper', 'class', 'et-icon-box' );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-default-'+settings.hide_default );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-sticky-'+settings.hide_sticky );

			view.addRenderAttribute( 'wrapper', 'class', 'icon-position-'+settings.icon_position );
			view.addRenderAttribute( 'wrapper', 'class', 'icon-alignment-'+settings.icon_alignment );
			view.addRenderAttribute( 'wrapper', 'class', 'icon-alignment-vertical-'+settings.icon_alignment_vertical );

			view.addRenderAttribute( 'wrapper', 'class', 'icon-position-tablet-'+settings.icon_position_tablet );
			view.addRenderAttribute( 'wrapper', 'class', 'icon-alignment-tablet-'+settings.icon_alignment_tablet );
			view.addRenderAttribute( 'wrapper', 'class', 'icon-alignment-vertical-tablet-'+settings.icon_alignment_vertical_tablet );

			view.addRenderAttribute( 'wrapper', 'class', 'icon-position-mobile-'+settings.icon_position_mobile );
			view.addRenderAttribute( 'wrapper', 'class', 'icon-alignment-mobile-'+settings.icon_alignment_mobile );
			view.addRenderAttribute( 'wrapper', 'class', 'icon-alignment-vertical-mobile-'+settings.icon_alignment_vertical_mobile );

			if(settings.animation){
				view.addRenderAttribute( 'wrapper', 'class', settings.animation );
			}

			if(settings.icon_size && settings.icon_size <= 40){
				view.addRenderAttribute( 'wrapper', 'class', 'margin-small' );
			}

			if(!settings.content){
				view.addRenderAttribute( 'wrapper', 'class', 'no-content' );
			}

		#>


			<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>

					<# if ( settings.link.length ) { #>
						<a href="{{{ settings.link }}}">
					<# } #>

						<# if ( settings.icon_original.length ) { #>
							<div class="icon original"></div>
						<# } else { #>
							<div class="icon"></div>
						<# } #>

						<div class="icon-box-content-wrap">

							<# if ( settings.title.length ) { #>
								<{{{ settings.tag }}} class="icon-box-title">{{{ settings.title }}}</{{{ settings.tag }}}>
							<# } #>

							<# if ( settings.content.length ) { #>
								<div class="icon-box-content">{{{ settings.content }}}</div>
							<# } #>

						</div>

					<# if ( settings.link.length ) { #>
						</a>
					<# } #>

			</div>

		
	<?php }

}