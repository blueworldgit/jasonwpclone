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
class Elementor_Widget_Product_Search extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);
		if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
			wp_register_script( 'widget-product-search', plugins_url('../../js/widget-product-search.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
		}
	}

	public function get_script_depends() {
		return [ 'widget-product-search'];
	}

	public function get_name() {
		return 'et_product_search';
	}

	public function get_title() {
		return esc_html__( 'Product search', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'search'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

        $vehicle_params = apply_filters( 'vehicle_params','');

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
				'toggle',
				[
					'label' => esc_html__( 'Display as toggle?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'filter',
				[
					'label' => esc_html__( 'Combine with filter?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'condition' => [
						'toggle' => 'true',
					],
				]
			);

			if ($vehicle_params) {


				$options = array();

				foreach ($vehicle_params as $param) {
					$options[$param] = ucfirst($param);
				}

				$this->add_control(
				'filter_attributes',
					[
						'label' => esc_html__( 'Available filter attributes', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SELECT2,
						'label_block' => true,
						'multiple' => true,
						'options' => $options,
						'condition' => [
							'filter' => 'true',
						]
					]
				);

				foreach ($vehicle_params as $param) {
					$this->add_control(
						$param.'_label', [
							'label' => ucfirst($param).' '.esc_html__( 'label', 'enovathemes-addons' ),
							'description' => esc_html__( 'Leave blank to inherit default value', 'enovathemes-addons' ),
							'type' => \Elementor\Controls_Manager::TEXT,
							'label_block' => true,
							'condition' => [
								'filter' => 'true',
							]
						]
					);
				}

				$this->add_control(
				'vin',
					[
						'label' => esc_html__( 'Enable VIN search', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::SWITCHER,
						'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
						'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
						'return_value' => 'true',
						'condition' => [
							'filter' => 'true',
						]
					]
				);
			}


			$this->add_control(
				'hide_category',
				[
					'label' => esc_html__( 'Hide category select', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'sku',
				[
					'label' => esc_html__( 'Search in SKU', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'description',
				[
					'label' => esc_html__( 'Search in description', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'attr',
				[
					'label' => esc_html__( 'Search in attributes', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'tag',
				[
					'label' => esc_html__( 'Search in tags', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
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
				'toggle_color',
				[
					'label' => esc_html__( 'Toggle icon color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .toggle-icon' => 'background: {{VALUE}}',
				  ],
				  'default' => $main_color,
				  'condition' => [
						'toggle' => 'true',
					],
				]
			);

			$this->add_control(
				'toggle_background_color',
				[
					'label' => esc_html__( 'Toggle background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .toggle' => 'background: {{VALUE}}',
				  ],
				  'default' => '#ffffff',
				  'condition' => [
						'toggle' => 'true',
					],
				]
			);

			$this->add_control(
				'toggle_border_color',
				[
					'label' => esc_html__( 'Toggle background border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .toggle' => 'border:1px solid {{VALUE}}',
				  ]
				]
			);

			$this->add_control(
				'toggle_text_color',
				[
					'label' => esc_html__( 'Toggle text color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .toggle' => 'color: {{VALUE}}',
				  ],
				  'default' => '#444444',
				  'condition' => [
						'toggle' => 'true',
					],
				]
			);

			$this->add_control(
				'button_color',
				[
					'label' => esc_html__( 'Button color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .et-search-button' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .et-search-button + .input-after:after' => 'background: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .et-search-button' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .et-search-button + .input-after:after' => 'background: {{VALUE}}',
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
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .et-search-button + .input-after' => 'background-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .et-search-button + .input-after' => 'background-color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'button_color_hover',
				[
					'label' => esc_html__( 'Button color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .et-search-button:hover' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .et-search-button:hover + .input-after:after' => 'background: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .et-search-button:hover' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .et-search-button:hover + .input-after:after' => 'background: {{VALUE}}',
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
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .et-search-button:hover + .input-after' => 'background-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .et-search-button:hover + .input-after' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'search_color',
				[
					'label' => esc_html__( 'Search box color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .search' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .search::-webkit-input-placeholder' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search .search::-moz-placeholder' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search select' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .search' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .search::-webkit-input-placeholder' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle .search::-moz-placeholder' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle select' => 'color: {{VALUE}}',
				  ],
				  'default' => '#444444'
				]
			);

			$this->add_control(
				'search_background_color',
				[
					'label' => esc_html__( 'Search box background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search form' => 'background-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle form' => 'background-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle form select' => 'background-color: {{VALUE}} !important',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle form .select2-container--default .select2-selection--single' => 'background-color: {{VALUE}} !important',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle form .search-wrapper' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'search_border_color',
				[
					'label' => esc_html__( 'Search box border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search form' => 'border:1px solid {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle form' => 'border:1px solid {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle form select' => 'border:1px solid {{VALUE}} !important',
				        '{{WRAPPER}} > .elementor-widget-container > .et-product-search-toggle form .search-wrapper' => 'border:1px solid {{VALUE}}',
				  ]
				]
			);

			$this->add_control(
			'width',
			[
				'type' => \Elementor\Controls_Manager::NUMBER,
				'label' => esc_html__( 'Width', 'enovathemes-addons' ),
				'min' => 1,
				'step' => 1,
				'selectors' => [
					'{{WRAPPER}} > .elementor-widget-container > .et-product-search' => 'width: {{VALUE}}px;'
				],
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
		$toggle       = empty($toggle) ? 'false' : $toggle;
		$vin          = empty($vin) ? 'off' : 'on';

		$output = '';

		$class   = array();
		$class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;

		$instance = array(
			'title' 	  => '',
			'category'    => empty($hide_category) ? 'false' : 'true',
			'SKU' 	      => $sku,
			'description' => $description,
			'in_tag' 	  => $tag,
			'in_attr' 	  => $attr,
		);

		$args = array(
			'before_title'  => '<h5 class="widget_title">',
            'after_title'   => '</h5>',
		);

		if ($toggle == 'true') {
			
			$class[] = 'et-product-search-toggle et-product-search';

			$output .= '<div class="'.implode(" ", $class).'">';
				$output .= '<div class="toggle">';
					$output .= '<div class="search-toggle hbe-toggle toggle-icon"></div>';
					$output .= '<div class="toggle-placeholder">'.esc_html__("What are you looking for?","enovathemes-addons").'</div>';
					if ($filter == 'true') {
						$output .= '<div class="filter-toggle hbe-toggle toggle-icon"></div>';
					}
				$output .= '</div>';
				$output .= '<div class="search-box">';
					$output .= '<div class="search-toggle-off et-icon size-medium"></div>';
					$output .= '<div class="et-clearfix"></div>';
					$output .= enovathemes_addons_get_the_widget( 'Enovathemes_Addons_WP_Product_Search', $instance,$args);
				$output .= '</div>';

				if ($filter == 'true' && !empty($filter_attributes)) {

            		wp_enqueue_script('widget-product-vehicle-filter');

					$filter_atts = array();

					foreach ( $filter_attributes as $attribute ) {

						$label = (isset($settings[$attribute.'_label']) && !empty($settings[$attribute.'_label'])) ? mb_convert_encoding($settings[$attribute.'_label'], 'UTF-8') : $attribute;

						$filter_atts[] = array('attr'=>$attribute,'label'=>ucfirst($label));
					}

					$output .= '<div class="filter-box">';
						$output .= '<div class="filter-toggle-off et-icon size-medium"></div>';
						$output .= '<div class="et-clearfix"></div>';

						$filter_instance = array(
							'title' => '',
			                'atts'  	  => json_encode($filter_atts,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
			                'vin'   => $vin,
			                'columns'   => 1,
			                'type'  => 'vertical',
						);

						$filter_args = array(
							'before_title'  => '<h5 class="widget_title">',
				            'after_title'   => '</h5>',
						);

						$output .= enovathemes_addons_get_the_widget( 'Enovathemes_Addons_WP_Product_Vehicle_Filter', $filter_instance,$filter_args);

					$output .= '</div>';

				}

			$output .= '</div>';


		} else {

			$class[] = 'et-product-search';

			$args['before_widget'] = '<div class="'.implode(" ", $class).'">';
			$args['after_widget'] = '</div>';

			$output .= enovathemes_addons_get_the_widget( 'Enovathemes_Addons_WP_Product_Search', $instance,$args);

		}

		if (!empty($output)) {
			echo $output;
		}

	}
}