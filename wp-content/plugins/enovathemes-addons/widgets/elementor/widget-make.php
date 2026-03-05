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
class Elementor_Widget_Make extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-make', plugins_url('../../js/widget-make.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-make'];
  }

	public function get_name() {
		return 'et_make';
	}

	public function get_title() {
		return esc_html__( 'Vehicle brands', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-logo';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'make', 'brands', 'vehicle', 'car'];
	}

	protected function register_controls() {

		$vehicle_logos_array = enovathemes_addons_vehicle_logos(get_template_directory().'/images/vehicle-logos/');

		$vehicle_logos_array_values = array();

		$vehicle_logos_array_values['none']['title'] = esc_html__('None','enovathemes-addons');
		$vehicle_logos_array_values['none']['url'] = THEME_IMG.'vehicle-logos/none.webp';

		if (!is_wp_error($vehicle_logos_array)) {
			foreach ($vehicle_logos_array as $logo) {
				if ($logo != 'thumbnails') {
					$extension = pathinfo($logo, PATHINFO_EXTENSION);
					$title = str_replace('-', ' ', substr($logo, 0, -strlen($extension) - 1));
					$vehicle_logos_array_values[$title]['title'] = ucfirst($title);
					$vehicle_logos_array_values[$title]['url'] = THEME_IMG.'vehicle-logos/'.$logo;
				}
			}
		}

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
				'label_block' => true,
				'condition' => [
						'logo' => ['','none'],
					]
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
				'condition' => [
						'logo' => ['','none'],
					]
			]
		);

		if (!empty($vehicle_logos_array_values)) {

			$repeater->add_control(
				'logo',
				[
					'label' => esc_html__('Vehicle logos', 'enovathemes-addons'),
					'type' => \Elementor\CustomControl\ImageSelector_Control::ImageSelector,
					'options' => $vehicle_logos_array_values,
					'default' => 'none'
				]
			);

		}

		$this->add_control(
			'list',
			[
				'label' => esc_html__( 'Vehicle logo list', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'name' => esc_html__( 'Vehicle logo #1', 'enovathemes-addons' ),
					],
				],
				'title_field' => '{{{ logo }}}',
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
				'carousel_rows',
				[
					'label' => esc_html__( 'Rows', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 3,
					'step' => 1,
					'default'=>1,
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
					'max' => 12,
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
					'max' => 4,
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
				'back_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container .make-item' => 'background-color: {{VALUE}}',
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
				        '{{WRAPPER}} > .elementor-widget-container .make-item' => 'outline-color: {{VALUE}}',
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
				        '{{WRAPPER}} > .elementor-widget-container .make-item:hover' => 'background-color: {{VALUE}}',
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
				        '{{WRAPPER}} > .elementor-widget-container .make-item:hover' => 'outline-color: {{VALUE}}',
				  ],
				  'default' => '#f5f5f5',
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
		$class[]    = 'et-make';
		$class[]    = $layout;

		if ($layout == "carousel") {

			$class[] = 'swiper-container';

			$attributes[] = 'data-carousel-gatter="8"';
			$attributes[] = 'data-carousel-rows="'.$carousel_rows.'"';
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

				$counter = 1;

				foreach (  $settings['list'] as $item ) {

					$title = (isset($item['logo']) && !empty($item['logo']) && $item['logo'] != "none") ? ucfirst($item['logo']) : $item['name'];

					$swiper_slide = '';

					if (($counter % 2 == 1 && $carousel_rows == 2) || ($counter % 3 == 1 && $carousel_rows == 3)){
              $output .= '<li class="row-item swiper-slide"><ul>';
          } else {
              $swiper_slide = 'swiper-slide';
          }

					$output .= '<li class="make-item '.$swiper_slide.' elementor-repeater-item-' . esc_attr( $item['_id'] ) . '">';
						$output .= '<a href="'.esc_url($item['link']).'" title="'.esc_attr($title).'">';
								if (isset($item['image']['url']) && !empty($item['image']['url'])) {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
										$output .= '<img src="'.$item['image']['url'].'">';
									} else {
										$output .= enovathemes_addons_inline_image_placeholder($item['image']['id'],'full');
									}
								} elseif(isset($item['logo']) && !empty($item['logo']) && $item['logo'] != "none") {
									if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
										$output .= '<img width="256" height="256" alt="'.$item['logo'].'" src="'.THEME_IMG.'/vehicle-logos/'.str_replace(' ', '-', $item['logo']).'.webp">';
									} else {
										$output .= '<img width="256" height="256" alt="'.$item['logo'].'" class="lazy" data-src="'.THEME_IMG.'/vehicle-logos/'.str_replace(' ', '-', $item['logo']).'.webp" src="'.THEME_IMG.'/vehicle-logos/thumbnails/'.str_replace(' ', '-', $item['logo']).'.webp">';
									}
								}
							$output.='<svg class="make-image-back" viewBox="0 0 1000 1000"><rect width="100%" height="100%" fill="none" /></svg>';
						$output .= '</a>';
					$output .= '</li>';

					if (($counter % 2 == 0 && $carousel_rows == 2) || ($counter % 3 == 0 && $carousel_rows == 3) || ($counter % 4 == 0 && $carousel_rows == 4)){
              $output .= '</ul></li>';
          }

          $counter++;

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