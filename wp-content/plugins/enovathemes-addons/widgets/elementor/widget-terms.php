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
class Elementor_Widget_Terms extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-terms', plugins_url('../../js/widget-terms.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-terms'];
  }

	public function get_name() {
		return 'et_terms';
	}

	public function get_title() {
		return esc_html__( 'Terms', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-product-categories';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'terms', 'brands', 'categories', 'attributes'];
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
			'name', [
				'label' => esc_html__( 'Name', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Term name' , 'enovathemes-addons' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'link', [
				'label' => esc_html__( 'Link', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'image', [
				'label' => esc_html__( 'Image', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'label_block' => true,
			]
		);

		$this->add_control(
			'list',
			[
				'label' => esc_html__( 'Term list', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'name' => esc_html__( 'Term #1', 'enovathemes-addons' ),
					],
				],
				'title_field' => '{{{ name }}}',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'layout_option',
			[
				'label' => esc_html__( 'Layout', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'layout',
				[
					'label' => esc_html__( 'Layout', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'carousel' => esc_html__('Carousel','enovathemes-addons'),
						'grid'     => esc_html__('Grid','enovathemes-addons'),
					],
					'default' => 'grid',
				]
			);

			$this->add_control(
				'version',
				[
					'label' => esc_html__( 'Version', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'simple' => esc_html__('Simple','enovathemes-addons'),
						'alternative' => esc_html__('Alternative','enovathemes-addons'),
						'list'     => esc_html__('List','enovathemes-addons'),
					],
					'default' => 'simple',
				]
			);

			$this->add_control(
				'carousel_autoplay',
				[
					'label' => esc_html__( 'Autoplay', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
					'condition' => [
						'layout' => 'carousel',
					]
				]
			);

			$this->add_control(
				'carousel_navigation_position',
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
						'layout' => 'carousel',
					]
				]
			);

			$this->add_control(
				'carousel_navigation_type',
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
						'layout' => 'carousel',
					]
				]
			);

			$this->add_control(
				'carousel_columns',
				[
					'label' => esc_html__( 'Columns', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 10,
					'step' => 1,
					'default'=>6,
				
				]
			);

			$this->add_control(
				'carousel_columns_tablet_land',
				[
					'label' => esc_html__( 'Columns tablet landscape', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 10,
					'step' => 1,
					'default'=>6,
				]
			);

			$this->add_control(
				'carousel_columns_tablet_port',
				[
					'label' => esc_html__( 'Columns tablet portrait', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 10,
					'step' => 1,
					'default'=>4,
				]
			);

			$this->add_control(
				'carousel_columns_mobile',
				[
					'label' => esc_html__( 'Columns mobile', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 10,
					'step' => 1,
					'default'=>3,
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'styling_option',
			[
				'label' => esc_html__( 'Styling', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);


			$this->add_control(
				'title_color',
				[
					'label' => esc_html__( 'Title color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .term-title' => 'color: {{VALUE}}',
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
				        '{{WRAPPER}} > .elementor-widget-container .terms-item:hover .term-title' => 'color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'button_color',
				[
					'label' => esc_html__( 'Button color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .alternative .terms-item .term-link' => 'color: {{VALUE}};border-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .alternative .term-link:after' => 'background-color: {{VALUE}};',
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
				        '{{WRAPPER}} > .elementor-widget-container .alternative .terms-item:hover .term-link' => 'color: {{VALUE}};border-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .alternative .terms-item:hover .term-link:after' => 'background-color: {{VALUE}};',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .alternative .terms-item' => 'background-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .simple .terms-item .term-image' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#f5f5f5',
				]
			);

			$this->add_control(
				'border_color',
				[
					'label' => esc_html__( 'Border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .alternative .terms-item' => 'outline-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .simple .terms-item .term-image' => 'outline-color: {{VALUE}}',
				  ],
				  'default' => '#f5f5f5',
				]
			);

			$this->add_control(
				'back_color_hover',
				[
					'label' => esc_html__( 'Background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .alternative .terms-item:hover' => 'background-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .simple .terms-item .term-image:hover' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#f5f5f5',
				]
			);

			$this->add_control(
				'border_color_hover',
				[
					'label' => esc_html__( 'Border color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .alternative .terms-item:hover' => 'outline-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container .simple .terms-item .term-image:hover' => 'outline-color: {{VALUE}}',
				  ],
				  'default' => '#f5f5f5',
				]
			);

			$this->add_control(
				'image_width',
				[
					'label' => esc_html__( 'Image width', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'step' => 1,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .terms-item .term-image' => 'width: {{VALUE}}px;min-width: {{VALUE}}px;max-width: {{VALUE}}px',
				  ]
				]
			);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$unique_id = $this->get_id();
		extract($settings);

		$output = '';

		$class      = array();
		$attributes = array();
		$class[]    = 'et-terms';
		$class[]    = $layout;
		$class[]    = $version;

		if ($layout == "carousel") {

			$class[] = 'swiper-container';

			$attributes[] = ($version == 'alternative' ? 'data-carousel-gatter="16"' : 'data-carousel-gatter="24"');
			$attributes[] = 'data-carousel-columns="'.$carousel_columns.'"';
			$attributes[] = 'data-carousel-mobile-columns="'.$carousel_columns_mobile.'"';
			$attributes[] = 'data-carousel-tablet-landscape-columns="'.$carousel_columns_tablet_land.'"';
			$attributes[] = 'data-carousel-tablet-portrait-columns="'.$carousel_columns_tablet_port.'"';
			$attributes[] = 'data-arrows-pos="'.$carousel_navigation_position.'"';
			$attributes[] = 'data-navigation-type="'.$carousel_navigation_type.'"';

			if ($carousel_autoplay) {
				$attributes[] = 'data-carousel-autoplay="'.$carousel_autoplay.'"';
			}

		} else {
			$class[]      = 'et-grid-items';
			$attributes[] = 'data-cl="'.$carousel_columns.'"';
			$attributes[] = 'data-mb-cl="'.$carousel_columns_mobile.'"';
			$attributes[] = 'data-tb-ld-cl="'.$carousel_columns_tablet_land.'"';
			$attributes[] = 'data-tb-pt-cl="'.$carousel_columns_tablet_port.'"';
		}


		if ( $settings['list'] ) {

			$output.='<div class="'.implode(' ', $class).'" '.implode(' ', $attributes).'>';

				if ($layout == "carousel") {
					$output .= '<div id="swiper-'.$unique_id.'" class="swiper">';
						$output.='<ul class="swiper-wrapper enova-carousel">';
				} else {
						$output.='<ul>';
				}

							foreach (  $settings['list'] as $item ) {
								$output .= '<li class="terms-item swiper-slide elementor-repeater-item-' . esc_attr( $item['_id'] ) . '">';
									$output .= '<a href="'.esc_url($item['link']).'" title="'.esc_attr($item['name']).'">';
										
										if ($version == 'simple' || $version == 'list') {
											$output .= '<div class="term-image">';
										}

											if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
												$output .= '<img src="'.$item['image']['url'].'">';
											} else {
												$output .= enovathemes_addons_inline_image_placeholder($item['image']['id'],'full');
											}

										if ($version == 'alternative') {
											$output .= '<span class="term-link">'.esc_html__('Shop now','enovathemes-addons').'</span>';
										} else {
												$output.='<svg class="term-image-back" viewBox="0 0 1000 1000"><rect width="100%" height="100%" fill="none" /></svg>';
											$output.='</div>';
										}

										$output .= '<h4 class="term-title">'.esc_html($item['name']).'</h4>';

									$output .= '</a>';
								$output .= '</li>';
							}

					$output.='</ul>';

				if ($layout == "carousel") {
					$output.='</div>';
					$output .= '<div id="prev-'.$unique_id.'" class="container-swiper-nav swiper-button swiper-button-prev"></div><div id="next-'.$unique_id.'" class="container-swiper-nav swiper-button swiper-button-next"></div><div id="swiper-pagination-'.$unique_id.'" class="swiper-pagination container-swiper-nav"></div>';
				}

			$output.='</div>';
		}

		if (!empty($output)) {
			echo $output;
		}
	}


}