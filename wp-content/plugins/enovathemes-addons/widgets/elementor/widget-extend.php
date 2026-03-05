<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


add_action( 'elementor/element/container/section_effects/before_section_start', 'enovathemes_addons_extended_settings', 10, 2);

function enovathemes_addons_extended_settings($element,$args){

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';


	    $element->start_controls_section(
	        'styling',
	        [
	            'label' => esc_html__( 'Enovathemes', 'enovathemes-addons' ),
	            'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
	        ]
	    );

	    	/* Responsive
			----------------------------*/

				$resp_array = array(
					'breakpoint_767' => esc_html__( 'Hide on mobile', 'enovathemes-addons' ),
					'breakpoint_768_1023' => esc_html__( 'Hide on tablet portrait', 'enovathemes-addons' ),
					'breakpoint_1024_1279' => esc_html__( 'Hide on tablet landscape', 'enovathemes-addons' ),
					'breakpoint_1280_1365' => esc_html__( 'Hide on tablet landscape extra', 'enovathemes-addons' ),
					'breakpoint_1366' => esc_html__( 'Hide on desktop', 'enovathemes-addons' ),
				);


			    $element->add_control(
			        'responsive_settings',
			        [
			            'label' => esc_html__( 'Responsive settings', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			        ]
			    );

			    foreach($resp_array as $key => $value){
					$element->add_control(
				        $key,
				        [
				            'label' => $value,
				            'type' => \Elementor\Controls_Manager::SWITCHER,
				            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
				            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
				            'return_value' => 'true',
				        ]
				    );
				}

	    	/* Header visibility 
			----------------------------*/

			    $element->add_control(
			        'header_builder',
			        [
			            'label' => esc_html__( 'Header builder', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			        ]
			    );

			    $element->add_control(
			        'sticky_background',
			        [
			            'label' => esc_html__( 'Sticky background color?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::COLOR,
			            'selectors'     => [
					        '.sticky-true.active {{WRAPPER}}' => 'background: {{VALUE}}'
					    ]
			        ]
			    );

			    $element->add_control(
			        'hide_sticky',
			        [
			            'label' => esc_html__( 'Hide from sticky header version?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			        ]
			    );

			/* Goldshine
			----------------------------*/

			    $element->add_control(
			        'goldshine_heading',
			        [
			            'label' => esc_html__( 'Goldshine effect', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			        ]
			    );

			    $element->add_control(
			        'goldshine',
			        [
			            'label' => esc_html__( 'Add goldshine background effect?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			        ]
			    );

			/* Gradient
			----------------------------*/

			    $element->add_control(
			        'gradient_heading',
			        [
			            'label' => esc_html__( 'Gradient overlay effect', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			        ]
			    );

			    $element->add_control(
			        'gradient',
			        [
			            'label' => esc_html__( 'Add dark gradient overlay effect?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			        ]
			    );

			    $element->add_group_control(
					\Elementor\Group_Control_Background::get_type(),
					[
						'name' => 'gradient_color',
						'types' => ['gradient'],
						'selector' => '{{WRAPPER}}.gradient:before',
					]
				);

			/* Parallax
			----------------------------*/

			    $element->add_control(
			        'parallax_heading',
			        [
			            'label' => esc_html__( 'Parallax', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			        ]
			    );

			    $element->add_control(
			        'parallax',
			        [
			            'label' => esc_html__( 'Add parallax background image?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			        ]
			    );

			    $element->add_control(
			        'parallax_image',
			        [
			            'label' => esc_html__( 'Parallax image', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::MEDIA,
			            'condition' => [
							'parallax' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'parallax_duration',
			        [
			            'label' => esc_html__( 'Parallax duration', 'enovathemes-addons' ),
			            'description' => esc_html__( 'Enter parallax duration in ms', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::NUMBER,
			            'default'=>30,
						'min' => 0,
						'max' => 1000,
						'step' => 1,
						'condition' => [
							'parallax' => 'true',
						]
			        ]
			    );

			/* Megamenu tab
			----------------------------*/

			    $element->add_control(
			        'megamenu_tab_heading',
			        [
			            'label' => esc_html__( 'Megamenu tab', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			            'condition' => [
							'mobile_container!' => 'true',
							'mobile_tab_item!'  => 'true',
							'section_popup!' => 'true',
							'section_toggle!' => 'true',
							'section_tab_item!'    => 'true',
							'section_accordion_item!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'megamenu_tab',
			        [
			            'label' => esc_html__( 'Turn container into megamenu tab?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'mobile_container!' => 'true',
							'mobile_tab_item!'  => 'true',
							'section_popup!' => 'true',
							'section_toggle!' => 'true',
							'section_tab_item!'    => 'true',
							'section_accordion_item!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'megamenu_tab_title',
			        [
			            'label' => esc_html__( 'Megamenu tab title', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::TEXT,
			            'frontend_available'=> false,
			            'render_type' => 'none',
			            'condition' => [
							'megamenu_tab!' => '',
						]
			        ]
			    );
			    $element->add_control(
			        'megamenu_tab_icon',
			        [
			            'label' => esc_html__( 'Megamenu tab icon', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::MEDIA,
			            'render_type' => 'none',
			            'condition' => [
							'megamenu_tab!' => '',
						]
			        ]
			    );

			/* Mobile tabs
			----------------------------*/

			    $element->add_control(
			        'mobile_container_heading',
			        [
			            'label' => esc_html__( 'Mobile container', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			            'condition' => [
							'megamenu_tab!' => 'true',
							'mobile_tab_item!' => 'true',
							'section_toggle!' => 'true',
							'section_popup!' => 'true',
							'stagger!' => 'true',
							'section_tab!' => 'true',
							'section_tab_item!'    => 'true',
							'section_accordion!' => 'true',
							'section_accordion_item!' => 'true',
							'section_carousel!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'mobile_container',
			        [
			            'label' => esc_html__( 'Set as mobile toggle container?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'megamenu_tab!' => 'true',
							'mobile_tab_item!' => 'true',
							'section_toggle!' => 'true',
							'section_popup!' => 'true',
							'stagger!' => 'true',
							'section_tab!' => 'true',
							'section_tab_item!'    => 'true',
							'section_accordion!' => 'true',
							'section_accordion_item!' => 'true',
							'section_carousel!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
					'mobile_tab_color',
					[
						'label' => esc_html__( 'Mobile tab color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .mobile-tabset .mobile-tab-item' => 'color: {{VALUE}}',
					        '{{WRAPPER}} .mobile-tabset .mobile-tab-item .mobile-icon' => 'background: {{VALUE}}',
					    ],
					    'condition' => [
							'mobile_container' => 'true',
						],
					    'default' => '#111111'
					]
				);

				$element->add_control(
					'mobile_tab_color_active',
					[
						'label' => esc_html__( 'Mobile tab color active', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .mobile-tabset .mobile-tab-item.active' => 'color: {{VALUE}}',
					        '{{WRAPPER}} .mobile-tabset .mobile-tab-item.active .mobile-icon' => 'background: {{VALUE}}',
					    ],
					    'condition' => [
							'mobile_container' => 'true',
						],
					    'default' => '#ffffff'
					]
				);

				$element->add_control(
					'mobile_tab_background_color',
					[
						'label' => esc_html__( 'Mobile tab background color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .mobile-tabset .mobile-tab-item' => 'background-color: {{VALUE}}',
					    ],
					    'condition' => [
							'mobile_container' => 'true',
						],
					    'default' => '#ffffff'
					]
				);

				$element->add_control(
					'mobile_tab_background_color_active',
					[
						'label' => esc_html__( 'Mobile tab background color active', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .mobile-tabset .mobile-tab-item.active' => 'background-color: {{VALUE}}',
					    ],
					    'condition' => [
							'mobile_container' => 'true',
						],
					    'default' => '#111111'
					]
				);

				/* Mobile tab item
				----------------------------*/

					$element->add_control(
				        'mobile_tab_item_heading',
				        [
				            'label' => esc_html__( 'Mobile tab item', 'enovathemes-addons' ),
				            'type' => \Elementor\Controls_Manager::HEADING,
				            'separator' => 'before',
				            'condition' => [
								'mobile_container!' => 'true',
								'megamenu_tab!' => 'true',
								'section_toggle!' => 'true',
								'section_popup!' => 'true',
								'section_tab_item!'    => 'true',
								'section_accordion_item!' => 'true',
							],
				        ]
				    );

				    $element->add_control(
				        'mobile_tab_item',
				        [
				            'label' => esc_html__( 'Turn container into mobile tab item?', 'enovathemes-addons' ),
				            'type' => \Elementor\Controls_Manager::SWITCHER,
				            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
				            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
				            'return_value' => 'true',
				            'condition' => [
								'mobile_container!' => 'true',
								'megamenu_tab!' => 'true',
								'section_toggle!' => 'true',
								'section_popup!' => 'true',
								'section_tab_item!'    => 'true',
								'section_accordion_item!' => 'true',
							],
				        ]
				    );

				    $element->add_control(
				        'mob_tab_title',
				        [
				            'label' => esc_html__( 'Mobile tab title', 'enovathemes-addons' ),
				            'type' => \Elementor\Controls_Manager::TEXT,
				            'render_type' => 'none',
				            'condition' => [
								'mobile_tab_item' => 'true',
							],
				        ]
				    );
				    $element->add_control(
				        'mob_tab_icon',
				        [
				            'label' => esc_html__( 'Mobile tab icon', 'enovathemes-addons' ),
				            'type' => \Elementor\Controls_Manager::MEDIA,
			            	'render_type' => 'none',
				            'condition' => [
								'mobile_tab_item' => 'true',
							],
				        ]
				    );
		
			/* Popup banner 
			----------------------------*/

				$element->add_control(
			        'section_popup_heading',
			        [
			            'label' => esc_html__( 'Popup container', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			            'condition' => [
							'megamenu_tab!' => 'true',
							'mobile_container!' => 'true',
							'mobile_tab_item!' => 'true',
							'section_toggle!' => 'true',
							'section_tab_item!' => 'true',
							'section_accordion_item!' => 'true'
						]
			        ]
			    );

			    $element->add_control(
			        'section_popup',
			        [
			            'label' => esc_html__( 'Turn container into popup?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'megamenu_tab!' => 'true',
							'mobile_container!' => 'true',
							'mobile_tab_item!' => 'true',
							'section_toggle!' => 'true',
							'section_tab_item!' => 'true',
							'section_accordion_item!' => 'true'
						]
			        ]
			    );

			    $element->add_control(
					'section_popup_width',
					[
						'label' => esc_html__( 'Width in px', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 1200,
						'step' => 1,
						'selectors'     => [
					        '{{WRAPPER}}' => 'width: {{VALUE}}px',
					    ],
						'default'=>720,
					    'condition' => [
							'section_popup' => 'true',
						]
					]
				);

				$element->add_control(
					'section_popup_height',
					[
						'label' => esc_html__( 'Height in px', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 1200,
						'step' => 1,
						'selectors'     => [
					        '{{WRAPPER}}' => 'height: {{VALUE}}px',
					    ],
						'default'=>400,
					    'condition' => [
							'section_popup' => 'true',
						]
					]
				);

			    $element->add_control(
			        'section_cookie',
			        [
			            'label' => esc_html__( 'Use cookie?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'section_popup' => 'true',
						]
			        ]
			    );

			    $element->add_control(
					'section_delay',
					[
						'label' => esc_html__( 'Delay', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 5000,
						'step' => 1,
						'default'=>1000,
					    'condition' => [
							'section_popup' => 'true',
						]
					]
				);

				$element->add_control(
					'section_popup_effects',
					[
						'label' => esc_html__( 'Effect', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => [
							'fade-in-scale'    => esc_html__('Fade in and scale','enovathemes-addons'),
							'slide-in-right'   => esc_html__('Slide in right','enovathemes-addons'),
							'slide-in-bottom'  => esc_html__('Slide in bottom','enovathemes-addons'),
							'flip-horizonatal' => esc_html__('3d flip horizontal','enovathemes-addons'),
							'flip-vertical'    => esc_html__('3d flip vertical','enovathemes-addons'),
						],
						'default' => 'fade-in-scale',
					    'condition' => [
					    	'section_popup' => 'true',
						],
					]
				);

			/* Toggle banner 
			----------------------------*/

				$element->add_control(
			        'section_toggle_heading',
			        [
			            'label' => esc_html__( 'Toggle container', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			            'condition' => [
							'megamenu_tab!' => 'true',
							'mobile_container!' => 'true',
							'mobile_tab_item!' => 'true',
							'section_popup!' => 'true',
							'section_tab_item!' => 'true',
							'section_accordion_item!' => 'true'
						]
			        ]
			    );

			    $element->add_control(
			        'section_toggle',
			        [
			            'label' => esc_html__( 'Turn container into toggle?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'megamenu_tab!' => 'true',
							'mobile_container!' => 'true',
							'mobile_tab_item!' => 'true',
							'section_popup!' => 'true',
							'section_tab_item!' => 'true',
							'section_accordion_item!' => 'true'
						]
			        ]
			    );

			    $element->add_control(
					'section_toggle_color',
					[
						'label' => esc_html__( 'Container toggle color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .toggle-banner-toggle' => 'background: {{VALUE}}',
					    ],
					    'default' => '#111111',
					    'condition' => [
							'section_toggle' => 'true',
						],
					]
				);

			    $element->add_control(
			        'section_cookie_toggle',
			        [
			            'label' => esc_html__( 'Use cookie?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'section_toggle' => 'true',
						]
			        ]
			    );

			/* Stagger box
			----------------------------*/

				$element->add_control(
			        'stagger_header',
			        [
			            'label' => esc_html__( 'Stagger box', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			            'condition' => [
							'mobile_container!' => 'true',
							'section_tab!'      => 'true',
							'section_accordion!' => 'true',
							'section_carousel!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'stagger',
			        [
			            'label' => esc_html__( 'Turn container into stagger box?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'mobile_container!' => 'true',
							'section_tab!'    => 'true',
							'section_accordion!' => 'true',
							'section_carousel!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'stagger_effect',
			        [
			            'label' => esc_html__( 'Stagger effect', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SELECT,
						'options' => [
							'top'    => esc_html__('Stagger from top','enovathemes-addons'),
							'bottom' => esc_html__('Stagger from bottom','enovathemes-addons'),
							'left'   => esc_html__('Stagger from left','enovathemes-addons'),
							'right'  => esc_html__('Stagger from right','enovathemes-addons'),
						],
						'default' => 'left',
						'condition' => [
							'stagger' => 'true',
						]
			        ]
			    );

			    $ms = [];

			    for ($i = 0; $i < 2000; $i += 50) { 
			    	$ms[$i] = $i;
			    }

			    $element->add_control(
					'stagger_interval',
					[
						'label' => esc_html__( 'Stagger interval in ms', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'default' => 50,
						'options' => $ms,
						'condition' => [
							'stagger' => 'true',
						]
					]
				);

				$element->add_control(
					'stagger_delay',
					[
						'label' => esc_html__( 'Stagger delay in ms', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => $ms,
						'default' => 0,
						'condition' => [
							'stagger' => 'true',
						]
					]
				);

			/* Tabs 
			----------------------------*/

				$element->add_control(
			        'section_tab_heading',
			        [
			            'label' => esc_html__( 'Tabs', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			            'condition' => [
							'mobile_container!' => 'true',
							'stagger!' => 'true',
							'section_tab_item!'    => 'true',
							'section_accordion!' => 'true',
							'section_carousel!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'section_tab',
			        [
			            'label' => esc_html__( 'Turn container into tabs?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'mobile_container!' => 'true',
							'stagger!' => 'true',
							'section_tab_item!'    => 'true',
							'section_accordion!' => 'true',
							'section_carousel!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
					'section_tabs_tab_min_width',
					[
						'label' => esc_html__( 'Tabs min width', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 500,
						'step' => 1,
					    'selectors'     => [
					        '{{WRAPPER}} .tabset > .section-tab-item' => 'min-width: {{VALUE}}px',
					    ],
					    'condition' => [
							'section_tab' => 'true',
						],
					]
				);

				$element->add_control(
					'section_tabs_tab_color',
					[
						'label' => esc_html__( 'Tabs color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .tabset > .section-tab-item' => 'color: {{VALUE}}',
					        '{{WRAPPER}} .tabset > .section-tab-item .section-tab-icon' => 'background: {{VALUE}}',
					        '{{WRAPPER}} .tabset > .section-tab-item .section-tab-icon:after' => 'background-color: {{VALUE}}',
					    ],
					    'default' => '#111111',
					    'condition' => [
							'section_tab' => 'true',
						],
					]
				);

				$element->add_control(
					'section_tabs_tab_color_active',
					[
						'label' => esc_html__( 'Tabs tab color active', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .tabset > .section-tab-item.active' => 'color: {{VALUE}}',
					        '{{WRAPPER}} .tabset > .section-tab-item.active .section-tab-icon' => 'background: {{VALUE}}',
					        '{{WRAPPER}} .tabset > .section-tab-item.active .section-tab-icon:after' => 'background-color: {{VALUE}}',
					    ],
					    'default' => $main_color,
					    'condition' => [
							'section_tab' => 'true',
						],
					]
				);

				$element->add_control(
					'section_tabs_tab_border_color',
					[
						'label' => esc_html__( 'Tabs tab border color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .tabset > .section-tab-item' => 'border-color: {{VALUE}}',
					    ],
					    'default' => '#e0e0e0',
					    'condition' => [
							'section_tab' => 'true',
						],
					]
				);

				$element->add_control(
					'section_tabs_tab_border_color_active',
					[
						'label' => esc_html__( 'Tabs tab border color active', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .tabset > .section-tab-item.active' => 'border-color: {{VALUE}}',
					    ],
					    'default' => $main_color,
					    'condition' => [
							'section_tab' => 'true',
						],
					]
				);

				$element->add_control(
					'section_tabs_tab_background_color',
					[
						'label' => esc_html__( 'Tabs tab background color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .tabset > .section-tab-item' => 'background-color: {{VALUE}}',
					    ],
					    'default' => '#ffffff',
					    'condition' => [
							'section_tab' => 'true',
						],
					]
				);

				$element->add_control(
					'section_tabs_tab_background_color_active',
					[
						'label' => esc_html__( 'Tabs tab background color active', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .tabset > .section-tab-item.active' => 'background-color: {{VALUE}}',
					    ],
					    'default' => '#ffffff',
					    'condition' => [
							'section_tab' => 'true',
						],
					]
				);

				$element->add_control(
					'section_tabs_type',
					[
						'label' => esc_html__( 'Type', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => [
							'horizontal' => esc_html__('Horizontal','enovathemes-addons'),
							'vertical' => esc_html__('Vertical','enovathemes-addons'),
							'center' => esc_html__('Center','enovathemes-addons'),
						],
						'default' => 'horizontal',
						'condition' => [
							'section_tab' => 'true',
						],
					]
				);

				/* Tab item 
				----------------------------*/

					$element->add_control(
				        'section_tab_item_heading',
				        [
				            'label' => esc_html__( 'Tab item', 'enovathemes-addons' ),
				            'type' => \Elementor\Controls_Manager::HEADING,
				            'separator' => 'before',
				            'condition' => [
								'megamenu_tab!' => 'true',
								'mobile_container!' => 'true',
								'mobile_tab_item!' => 'true',
								'section_toggle!' => 'true',
								'section_popup!' => 'true',
								'section_tab!' => 'true',
								'section_accordion_item!' => 'true',
							],
				        ]
				    );

					$element->add_control(
				        'section_tab_item',
				        [
				            'label' => esc_html__( 'Turn container into tab item?', 'enovathemes-addons' ),
				            'type' => \Elementor\Controls_Manager::SWITCHER,
				            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
				            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
				            'return_value' => 'true',
				            'condition' => [
								'megamenu_tab!' => 'true',
								'mobile_container!' => 'true',
								'mobile_tab_item!' => 'true',
								'section_toggle!' => 'true',
								'section_popup!' => 'true',
								'section_tab!' => 'true',
								'section_accordion_item!' => 'true',
							],
				        ]
				    );

				    $element->add_control(
						'section_tab_title', [
							'label' => esc_html__( 'Title', 'enovathemes-addons' ),
							'type' => \Elementor\Controls_Manager::TEXT,
							'condition' => [
								'section_tab_item' => 'true',
							],
						]
					);

					$element->add_control(
						'section_tab_icon', [
							'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
							'type' => \Elementor\Controls_Manager::MEDIA,
							'condition' => [
								'section_tab_item' => 'true',
							],
						]
					);

					$element->add_control(
						'section_tab_active', [
							'label' => esc_html__( 'Active tab?', 'enovathemes-addons' ),
							'type' => \Elementor\Controls_Manager::SWITCHER,
							'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
							'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
							'return_value' => 'active',
							'condition' => [
								'section_tab_item' => 'true',
							],
						]
					);
		
			/* Accordion 
			----------------------------*/

				$element->add_control(
			        'section_accordion_heading',
			        [
			            'label' => esc_html__( 'Accordion', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
						'condition' => [
							'mobile_container!' => 'true',
							'stagger!' => 'true',
							'section_tab!' => 'true',
							'section_accordion_item!' => 'true',
							'section_carousel!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'section_accordion',
			        [
			            'label' => esc_html__( 'Turn container into accordion?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'mobile_container!' => 'true',
							'stagger!' => 'true',
							'section_tab!' => 'true',
							'section_accordion_item!' => 'true',
							'section_carousel!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
					'section_accordion_color',
					[
						'label' => esc_html__( 'Accordion color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .section-accordion-title' => 'color: {{VALUE}}',
					        '{{WRAPPER}} .section-accordion-title .accordion-icon' => 'background: {{VALUE}}',
					        '{{WRAPPER}} .section-accordion-title:after' => 'background-color: {{VALUE}} !important',
					    ],
					    'default' => '#111111',
					    'condition' => [
							'section_accordion' => 'true',
						],
					]
				);

				$element->add_control(
					'section_accordion_color_active',
					[
						'label' => esc_html__( 'Accordion accordion color active', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .section-accordion-title.active' => 'color: {{VALUE}}',
					        '{{WRAPPER}} .section-accordion-title.active .accordion-icon' => 'background: {{VALUE}}',
					        '{{WRAPPER}} .section-accordion-title.active:after' => 'background-color: {{VALUE}} !important',
					    ],
					    'default' => $main_color,
					    'condition' => [
							'section_accordion' => 'true',
						],
					]
				);

				$element->add_control(
					'section_accordion_border_color',
					[
						'label' => esc_html__( 'Accordion accordion border color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .section-accordion-title' => 'border-color: {{VALUE}}',
					    ],
					    'default' => '#e0e0e0',
					    'condition' => [
							'section_accordion' => 'true',
						],
					]
				);

				$element->add_control(
					'section_accordion_border_color_active',
					[
						'label' => esc_html__( 'Accordion accordion border color active', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .section-accordion-title.active' => 'border-color: {{VALUE}}',
					    ],
					    'default' => $main_color,
					    'condition' => [
							'section_accordion' => 'true',
						],
					]
				);

				$element->add_control(
					'section_accordion_background_color',
					[
						'label' => esc_html__( 'Accordion accordion background color', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .section-accordion-title' => 'background-color: {{VALUE}}',
					    ],
					    'default' => '#ffffff',
					    'condition' => [
							'section_accordion' => 'true',
						],
					]
				);

				$element->add_control(
					'section_accordion_background_color_active',
					[
						'label' => esc_html__( 'Accordion accordion background color active', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::COLOR,
						'selectors'     => [
					        '{{WRAPPER}} .section-accordion-title.active' => 'background-color: {{VALUE}}',
					    ],
					    'default' => '#ffffff',
					    'condition' => [
							'section_accordion' => 'true',
						],
					]
				);

				$element->add_control(
					'section_accordion_type',
					[
						'label' => esc_html__( 'Collapsible', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SWITCHER,
						'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
						'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
						'return_value' => 'true',
					    'condition' => [
							'section_accordion' => 'true',
						],
					]
				);


				/* Accordion item
				----------------------------*/

					$element->add_control(
				        'section_accordion_item_heading',
				        [
				            'label' => esc_html__( 'Accordion item', 'enovathemes-addons' ),
				            'type' => \Elementor\Controls_Manager::HEADING,
				            'separator' => 'before',
				            'condition' => [
								'megamenu_tab!' => 'true',
								'mobile_container!' => 'true',
								'mobile_tab_item!' => 'true',
								'section_toggle!' => 'true',
								'section_popup!' => 'true',
								'section_tab_item!'    => 'true',
								'section_accordion!' => 'true',
							],
				        ]
				    );

				    $element->add_control(
				        'section_accordion_item',
				        [
				            'label' => esc_html__( 'Turn container into accordion item?', 'enovathemes-addons' ),
				            'type' => \Elementor\Controls_Manager::SWITCHER,
				            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
				            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
				            'return_value' => 'true',
				            'condition' => [
								'megamenu_tab!' => 'true',
								'mobile_container!' => 'true',
								'mobile_tab_item!' => 'true',
								'section_toggle!' => 'true',
								'section_popup!' => 'true',
								'section_tab_item!'    => 'true',
								'section_accordion!' => 'true',
							],
				        ]
				    );

				    $element->add_control(
						'section_accordion_title', [
							'label' => esc_html__( 'Title', 'enovathemes-addons' ),
							'type' => \Elementor\Controls_Manager::TEXT,
							'condition' => [
								'section_accordion_item' => 'true',
							],
						]
					);

					$element->add_control(
						'section_accordion_icon', [
							'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
							'type' => \Elementor\Controls_Manager::MEDIA,
							'condition' => [
								'section_accordion_item' => 'true',
							],
						]
					);

					$element->add_control(
						'section_accordion_active', [
							'label' => esc_html__( 'Active accordion item', 'enovathemes-addons' ),
							'type' => \Elementor\Controls_Manager::SWITCHER,
							'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
							'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
							'return_value' => 'active',
							'condition' => [
								'section_accordion_item' => 'true',
							],
						]
					);

			/* Carousel 
			----------------------------*/

				$element->add_control(
			        'section_carousel_heading',
			        [
			            'label' => esc_html__( 'Carousel', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::HEADING,
			            'separator' => 'before',
			            'condition' => [
							'mobile_container!' => 'true',
							'stagger!' => 'true',
							'section_tab!'    => 'true',
							'section_accordion!' => 'true',
						]
			        ]
			    );

			    $element->add_control(
			        'section_carousel',
			        [
			            'label' => esc_html__( 'Turn container into carousel?', 'enovathemes-addons' ),
			            'type' => \Elementor\Controls_Manager::SWITCHER,
			            'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
			            'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
			            'return_value' => 'true',
			            'condition' => [
							'mobile_container!' => 'true',
							'stagger!' => 'true',
							'section_tab!'    => 'true',
							'section_accordion!' => 'true',
						]
			        ]
			    );

				$element->add_control(
					'section_carousel_autoplay',
					[
						'label' => esc_html__( 'Autoplay', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SWITCHER,
						'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
						'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
						'return_value' => 'true',
					    'condition' => [
							'section_carousel' => 'true',
						],
					]
				);

				$element->add_control(
					'section_carousel_navigation_position',
					[
						'label' => esc_html__( 'Arrows position', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => [
							'side'         => esc_html__('Side','enovathemes-addons'),
							'inside'       => esc_html__('Inside','enovathemes-addons'),
							'top-right'    => esc_html__('Top','enovathemes-addons'),
						],
						'default' => 'side',
					    'condition' => [
					    	'section_carousel' => 'true',
						],
					]
				);

				$element->add_control(
					'section_carousel_navigation_type',
					[
						'label' => esc_html__( 'Navigation type', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => [
							'arrows'     => esc_html__('Arrows','enovathemes-addons'),
							'pagination' => esc_html__('Pagination','enovathemes-addons'),
							'both'       => esc_html__('Both','enovathemes-addons'),
						], 
						'default' => 'arrows',
						'condition' => [
					    	'section_carousel' => 'true',
						]
					]
				);

				$element->add_control(
					'section_carousel_gatter',
					[
						'label' => esc_html__( 'Gatter', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 100,
						'step' => 1,
						'default'=>24,
					    'condition' => [
							'section_carousel' => 'true',
						]
					]
				);

				$element->add_control(
					'section_carousel_columns',
					[
						'label' => esc_html__( 'Columns', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 10,
						'step' => 1,
					    'condition' => [
							'section_carousel' => 'true',
						],
						'default'=>3
					]
				);

				$element->add_control(
					'section_carousel_columns_tablet_land',
					[
						'label' => esc_html__( 'Columns tablet landscape', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 10,
						'step' => 1,
					    'condition' => [
							'section_carousel' => 'true',
						],
						'default'=>3
					]
				);

				$element->add_control(
					'section_carousel_columns_tablet_port',
					[
						'label' => esc_html__( 'Columns tablet portrait', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 10,
						'step' => 1,
					    'condition' => [
							'section_carousel' => 'true',
						],
						'default'=>2
					]
				);

				$element->add_control(
					'section_carousel_columns_mobile',
					[
						'label' => esc_html__( 'Columns mobile', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 10,
						'step' => 1,
					    'condition' => [
							'section_carousel' => 'true',
						],
						'default'=>1
					]
				);
	    
	    $element->end_controls_section();

}

add_action( 'elementor/frontend/before_render', 'enovathemes_addons_before_render_section');
function enovathemes_addons_before_render_section($element) {

	/* Responsive
	----------------------------*/

		$resp_array = array(
			'breakpoint_767',
			'breakpoint_768_1023',
			'breakpoint_1024_1279',
			'breakpoint_1280_1365',
			'breakpoint_1366',
		);

		foreach($resp_array as $key){
			if ($element->get_settings( $key ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'class' => str_replace('_','-',$key),
					]
				);
			}
		}

	/* Header visibility 
	----------------------------*/

		if ($element->get_settings( 'hide_default' ) ) {
			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'hide-default',
				]
			);
		}

		if ($element->get_settings( 'hide_sticky' ) ) {
			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'hide-sticky',
				]
			);
		}

	/* Goldshine
	----------------------------*/

		if ($element->get_settings( 'goldshine' ) ) {
			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'gold-shine',
				]
			);
		}

	/* Gradient
	----------------------------*/

		if ($element->get_settings( 'gradient' ) ) {
			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'gradient',
				]
			);
		}

	/* Parallax
	----------------------------*/

		if ($element->get_settings( 'parallax' ) ) {

			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'et-parallax',
				]
			);

			if ($element->get_settings( 'parallax_image' ) && !empty($element->get_settings( 'parallax_image' )['url'])) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-parallax-image' => $element->get_settings( 'parallax_image' )['url'],
					]
				);
			}

			if ($element->get_settings( 'parallax_duration' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-parallax-duration' => $element->get_settings( 'parallax_duration' ),
					]
				);
			}

		}

	/* Megamenu tab 
	----------------------------*/

		if ($element->get_settings( 'megamenu_tab' ) == 'true') {
	
			if ($element->get_settings( 'megamenu_tab_title' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-tab-title' => $element->get_settings( 'megamenu_tab_title' ),
						'class' => 'megamenu-tab-item tab-content',
					]
				);
			}

			if ($element->get_settings( 'megamenu_tab_icon' ) && !empty($element->get_settings( 'megamenu_tab_icon' )['url'])) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-tab-icon' => $element->get_settings( 'megamenu_tab_icon' )['url'],
					]
				);
			}

		}

	/* Mobile tabs
	----------------------------*/

		if ($element->get_settings( 'mobile_container' ) ) {
			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'mobile-container',
				]
			);
		} 

		/* Mobile tab 
		----------------------------*/

			if ($element->get_settings( 'mobile_tab_item' ) == 'true') {

				if ($element->get_settings( 'mob_tab_title' ) ) {
					$element->add_render_attribute(
						'_wrapper',
						[
							'data-mob-tab-title' => $element->get_settings( 'mob_tab_title' ),
							'class' => 'mobile-tab-item tab-content',
						]
					);
				}

				if ($element->get_settings( 'mob_tab_icon' ) && !empty($element->get_settings( 'mob_tab_icon' )['url'])) {
					$element->add_render_attribute(
						'_wrapper',
						[
							'data-mob-tab-icon' => $element->get_settings( 'mob_tab_icon' )['url'],
						]
					);
				}
			}

	/* Popup banner
	----------------------------*/

		if ($element->get_settings( 'section_popup' ) ) {

			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'et-popup-banner',
				]
			);

			if ($element->get_settings( 'section_popup_effects' ) ) {

				$element->add_render_attribute(
					'_wrapper',
					[
						'data-popup-effect' => $element->get_settings( 'section_popup_effects' ),
					]
				);

			}

			if ($element->get_settings( 'section_delay' ) ) {

				$element->add_render_attribute(
					'_wrapper',
					[
						'data-popup-delay' => $element->get_settings( 'section_delay' ),
					]
				);

			}

			if ($element->get_settings( 'section_cookie' ) ) {

				$element->add_render_attribute(
					'_wrapper',
					[
						'data-popup-cookie' => $element->get_settings( 'section_cookie' ),
					]
				);

			}

		}

	/* Toggle banner
	----------------------------*/

		if ($element->get_settings( 'section_toggle' ) ) {

			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'et-toggle-banner',
				]
			);

			if ($element->get_settings( 'section_cookie_toggle' ) ) {

				$element->add_render_attribute(
					'_wrapper',
					[
						'data-toggle-cookie' => $element->get_settings( 'section_cookie_toggle' ),
					]
				);

			}

		}

	/* Stagger box
	----------------------------*/

		if ($element->get_settings( 'stagger' ) ) {
			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'et-stagger-box',
				]
			);
		

			if ($element->get_settings( 'stagger_effect' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-stagger' => $element->get_settings( 'stagger_effect' ),
					]
				);
			}

			if ($element->get_settings( 'stagger_interval' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-interval' => $element->get_settings( 'stagger_interval' ),
					]
				);
			}

			if ($element->get_settings( 'stagger_delay' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-delay' => $element->get_settings( 'stagger_delay' ),
					]
				);
			}

		}

	/* Tabs
	----------------------------*/

		if ($element->get_settings( 'section_tab' ) ) {

			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'section-tab',
				]
			);
			

			if ($element->get_settings( 'section_tabs_type' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-section-tabs-type' => $element->get_settings( 'section_tabs_type' ),
					]
				);
			}

		}

		if($element->get_settings( 'section_tab_item' )) {

			if ($element->get_settings( 'section_tab_title' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-section-tab-title' => $element->get_settings( 'section_tab_title' ),
						'class' => 'section-tab-item tab-content',
					]
				);
			}

			if ($element->get_settings( 'section_tab_icon' ) && !empty($element->get_settings( 'section_tab_icon' )['url'])) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-section-tab-icon' => $element->get_settings( 'section_tab_icon' )['url'],
					]
				);
			}

			if ($element->get_settings( 'section_tab_active' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'class' => 'active',
					]
				);
			}
		}

	/* Accordion 
	----------------------------*/

		if ($element->get_settings( 'section_accordion' ) ) {

			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'section-accordion',
				]
			);
		

			if ($element->get_settings( 'section_accordion_type' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-section-accordion-type' => 'collapsible-'.$element->get_settings( 'section_accordion_type' ),
					]
				);
			}

		}

		if($element->get_settings( 'section_accordion_item' )) {

			if ($element->get_settings( 'section_accordion_title' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-section-accordion-title' => $element->get_settings( 'section_accordion_title' ),
						'class' => 'section-accordion-item accordion-content section-accordion-content',
					]
				);
			}

			if ($element->get_settings( 'section_accordion_icon' ) && !empty($element->get_settings( 'section_accordion_icon' )['url'])) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-section-accordion-icon' => $element->get_settings( 'section_accordion_icon' )['url'],
					]
				);
			}

			if ($element->get_settings( 'section_accordion_active' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'class' => 'active',
					]
				);
			}

		}
	
	/* Carousel
	----------------------------*/

		if ($element->get_settings( 'section_carousel' ) ) {

			$element->add_render_attribute(
				'_wrapper',
				[
					'class' => 'section-carousel',
				]
			);

			if ($element->get_settings( 'section_carousel_autoplay' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-carousel-autoplay' => $element->get_settings( 'section_carousel_autoplay' ),
					]
				);
			}

			if ($element->get_settings( 'section_carousel_navigation_position' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-arrows-pos' => $element->get_settings( 'section_carousel_navigation_position' ),
					]
				);
			}

			if ($element->get_settings( 'section_carousel_navigation_type' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-navigation-type' => $element->get_settings( 'section_carousel_navigation_type' ),
					]
				);
			}

			if ($element->get_settings( 'section_carousel_gatter' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-carousel-gatter' => $element->get_settings( 'section_carousel_gatter' ),
					]
				);
			}

			if ($element->get_settings( 'section_carousel_columns' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-carousel-columns' => $element->get_settings( 'section_carousel_columns' ),
					]
				);
			}

			if ($element->get_settings( 'section_carousel_columns_tablet_land' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-carousel-tablet-landscape-columns' => $element->get_settings( 'section_carousel_columns_tablet_land' ),
					]
				);
			}

			if ($element->get_settings( 'section_carousel_columns_tablet_port' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-carousel-tablet-portrait-columns' => $element->get_settings( 'section_carousel_columns_tablet_port' ),
					]
				);
			}

			if ($element->get_settings( 'section_carousel_columns_mobile' ) ) {
				$element->add_render_attribute(
					'_wrapper',
					[
						'data-carousel-mobile-columns' => $element->get_settings( 'section_carousel_columns_mobile' ),
					]
				);
			}

		}
	

}

add_action( 'elementor/container/print_template', 'enovathemes_addons_add_section_parallax_preview_template', 10, 2);
function enovathemes_addons_add_section_parallax_preview_template($template, $widget){

	$new_template = $template;

	ob_start();
	?>

		<# 

			view.addRenderAttribute( 'wrapper', 'class', 'element-shadow' );

			/* Responsive
			----------------------------*/

				if ( settings.breakpoint_767 && settings.breakpoint_767 == 'true' ) {view.addRenderAttribute( 'wrapper', 'class', 'breakpoint-767' );}
				if ( settings.breakpoint_768_1023 && settings.breakpoint_768_1023 == 'true' ) {view.addRenderAttribute( 'wrapper', 'class', 'breakpoint-768-1023' );}
				if ( settings.breakpoint_1024_1279 && settings.breakpoint_1024_1279 == 'true' ) {view.addRenderAttribute( 'wrapper', 'class', 'breakpoint-1024-1279' );}
				if ( settings.breakpoint_1280_1365 && settings.breakpoint_1280_1365 == 'true' ) {view.addRenderAttribute( 'wrapper', 'class', 'breakpoint-1280-1365' );}
				if ( settings.breakpoint_1366 && settings.breakpoint_1366 == 'true' ) {view.addRenderAttribute( 'wrapper', 'class', 'breakpoint-1366' );}

			/* Goldshine 
			----------------------------*/

				if (settings.goldshine == 'true') {
					view.addRenderAttribute( 'wrapper', 'class', 'gold-shine' );
				}

			/* Gradient 
			----------------------------*/

				if (settings.gradient == 'true') {
					view.addRenderAttribute( 'wrapper', 'class', 'gradient' );
				}

			/* Effects/Parallax 
			----------------------------*/

				if (settings.parallax == 'true' && settings.parallax_image) {

					view.addRenderAttribute( 'wrapper', 'class', 'et-parallax' );
					view.addRenderAttribute( 'wrapper', 'data-parallax-duration', settings.parallax_duration );
					view.addRenderAttribute( 'wrapper', 'data-parallax-image', settings.parallax_image["url"] );
					
				}

			/* Container as megamenu tab
			----------------------------*/

				if ( settings.megamenu_tab == 'true' ) {
					view.addRenderAttribute( 'wrapper', 'class', 'megamenu-tab-item tab-content' );
					view.addRenderAttribute( 'wrapper', 'data-tab-title', settings.tab_title );
					view.addRenderAttribute( 'wrapper', 'data-tab-icon', settings.megamenu_tab_icon["url"] );
				}

			/* Container as mobile tab container
			----------------------------*/

				if ( settings.mobile_container == 'true' ) {
					view.addRenderAttribute( 'wrapper', 'class', 'mobile-container' );
				}

				/* Container as mobile tab
				----------------------------*/

					if (settings.mob_tab_title) {
						view.addRenderAttribute( 'wrapper', 'data-mob-tab-title', settings.mob_tab_title );
						view.addRenderAttribute( 'wrapper', 'class', 'mobile-tab-item tab-content' );
						
						if (settings.mob_tab_icon) {
							view.addRenderAttribute( 'wrapper', 'data-mob-tab-icon', settings.mob_tab_icon['url'] );
						}
					}

			/* Container as stagger box
			----------------------------*/

				if ( settings.stagger == 'true' ) {

					view.addRenderAttribute( 'wrapper', 'class', 'et-stagger-box' );

					if (settings.stagger_effect) {
						view.addRenderAttribute( 'wrapper', 'data-stagger', settings.stagger_effect );
					}

					if (settings.stagger_interval) {
						view.addRenderAttribute( 'wrapper', 'data-interval', settings.stagger_interval );
					}

					if (settings.stagger_delay) {
						view.addRenderAttribute( 'wrapper', 'data-delay', settings.stagger_delay );
					}
					
				}			

			/* Container as popup
			----------------------------*/

				if ( settings.section_popup == 'true' ) {
					view.addRenderAttribute( 'wrapper', 'class', 'et-popup-banner' );


					if (settings.section_popup_effects) {
						view.addRenderAttribute( 'wrapper', 'data-popup-effect', settings.section_popup_effects  );
					}

					if (settings.section_delay) {
						view.addRenderAttribute( 'wrapper', 'data-popup-delay', settings.section_delay );
					}

					if (settings.section_cookie) {
						view.addRenderAttribute( 'wrapper', 'data-popup-cookie', settings.section_cookie );
					}

				}

			/* Container as toggle
			----------------------------*/

				if ( settings.section_toggle == 'true' ) {
					view.addRenderAttribute( 'wrapper', 'class', 'et-toggle-banner' );

					if (settings.section_cookie_toggle) {
						view.addRenderAttribute( 'wrapper', 'data-toggle-cookie', settings.section_cookie_toggle );
					}

				}

			/* Container as tab
			----------------------------*/

				if ( settings.section_tab == 'true' ) {
					view.addRenderAttribute( 'wrapper', 'class', 'section-tab' );

					if ( settings.section_tabs_type) {
						view.addRenderAttribute( 'wrapper', 'data-section-tabs-type', settings.section_tabs_type );
					}
				}

				/* Container as tab item
				----------------------------*/

					if (settings.section_tab_item && settings.section_tab_title) {
						view.addRenderAttribute( 'wrapper', 'data-section-tab-title', settings.section_tab_title );
						view.addRenderAttribute( 'wrapper', 'class', 'section-tab-item tab-content section-tab-content' );

						if (settings.section_tab_icon) {
							view.addRenderAttribute( 'wrapper', 'data-section-tab-icon', settings.section_tab_icon['url'] );
						}

						if (settings.section_tab_active) {
							view.addRenderAttribute( 'wrapper', 'class', 'active' );
						}
					}

			/* Container as accordion
			----------------------------*/

				if ( settings.section_accordion == 'true' ) {
					view.addRenderAttribute( 'wrapper', 'class', 'section-accordion' );

					if ( settings.section_accordion_type == 'true' ) {
						view.addRenderAttribute( 'wrapper', 'data-section-accordion-type', settings.section_accordion_type );
					}
				}

				/* Container as accordion item
				----------------------------*/

					if (settings.section_accordion_item == 'true' && settings.section_accordion_title) {
						view.addRenderAttribute( 'wrapper', 'data-section-accordion-title', settings.section_accordion_title );
						view.addRenderAttribute( 'wrapper', 'class', 'section-accordion-item accordion-content section-accordion-content' );

						if (settings.section_accordion_icon) {
							view.addRenderAttribute( 'wrapper', 'data-section-accordion-icon', settings.section_accordion_icon['url'] );
						}

						if (settings.section_accordion_active) {
							view.addRenderAttribute( 'wrapper', 'class', 'active' );
						}

					}	

			/* Container as carousel
			----------------------------*/

				if ( settings.section_carousel == 'true' ) {

					view.addRenderAttribute( 'wrapper', 'class', 'section-carousel' );

					if (settings.section_carousel_autoplay) {
						view.addRenderAttribute( 'wrapper', 'data-carousel-autoplay', settings.section_carousel_autoplay );
					}

					if (settings.section_carousel_navigation_position) {
						view.addRenderAttribute( 'wrapper', 'data-carousel-navigation-position', settings.section_carousel_navigation_position );
					}

					if (settings.section_carousel_navigation_type) {
						view.addRenderAttribute( 'wrapper', 'data-carousel-navigation-type', settings.section_carousel_navigation_type );
					}

					if (settings.section_carousel_gatter) {
						view.addRenderAttribute( 'wrapper', 'data-carousel-gatter', settings.section_carousel_gatter );
					}

					if (settings.section_carousel_columns) {
						view.addRenderAttribute( 'wrapper', 'data-carousel-columns', settings.section_carousel_columns );
					}

					if (settings.section_carousel_columns_tablet_land) {
						view.addRenderAttribute( 'wrapper', 'data-carousel-tablet-landscape-columns', settings.section_carousel_columns_tablet_land );
					}

					if (settings.section_carousel_columns_tablet_port) {
						view.addRenderAttribute( 'wrapper', 'data-carousel-tablet-portrait-columns', settings.section_carousel_columns_tablet_port );
					}

					if (settings.section_carousel_columns_mobile) {
						view.addRenderAttribute( 'wrapper', 'data-carousel-mobile-columns', settings.section_carousel_columns_mobile );
					}

				}
			

		#>

	<?php
	$script = ob_get_clean();

	if ( 'container' === $widget->get_name()) {
		$new_template .= '<div {{{ view.getRenderAttributeString( "wrapper" ) }}}></div>';
	}

	$new_template = $script . "\n" .$new_template;

	return $new_template;
}


?>