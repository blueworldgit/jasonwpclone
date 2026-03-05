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
class Elementor_Widget_Clients extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-clients', plugins_url('../../js/widget-clients.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-clients'];
  }

	public function get_name() {
		return 'et_clients';
	}

	public function get_title() {
		return esc_html__( 'Clients', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-slider-3d';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'clients', 'brands'];
	}

	protected function register_controls() {

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
					'default' => esc_html__( 'Brand name' , 'enovathemes-addons' ),
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


			$repeater->add_control(
				'color', [
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'label_block' => true,
					'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}}' => 'background-color: {{VALUE}};border-color:{{VALUE}};',
							],
				]
			);

			$repeater->add_control(
				'background_img', [
					'label' => esc_html__( 'Background image', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'label_block' => true,
					'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}}' => 'background-image: url({{URL}});',
							],
				]
			);

		$this->add_control(
			'list',
			[
				'label' => esc_html__( 'Client list', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'name' => esc_html__( 'Client #1', 'enovathemes-addons' ),
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
					'default' => 'carousel',
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


		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$unique_id = $this->get_id();

		extract($settings);

		$output = '';

		$class      = array();
		$attributes   = [];
		$class[]    = 'et-clients';
		$class[]    = $layout;

		if ($layout == "carousel") {

			$class[] = 'swiper-container';

			$attributes[] = 'data-carousel-gatter="8"';
			$attributes[] = 'data-carousel-rows="'.$carousel_rows.'"';
			$attributes[] = 'data-carousel-columns="'.$carousel_columns.'"';
			$attributes[] = 'data-carousel-mobile-columns="2"';
			$attributes[] = 'data-carousel-tablet-landscape-columns="'.$carousel_columns_tablet_land.'"';
			$attributes[] = 'data-carousel-tablet-portrait-columns="'.$carousel_columns_tablet_port.'"';
			$attributes[] = 'data-arrows-pos="'.$carousel_navigation_position.'"';
			$attributes[] = 'data-navigation-type="'.$carousel_navigation_type.'"';

			if ($carousel_autoplay) {
				$attributes[] = 'data-carousel-autoplay="'.$carousel_autoplay.'"';
			}

		} else {
			$class[]    = 'et-grid-items';
			$attributes[] = 'data-cl="'.$carousel_columns.'"';
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

							$swiper_slide = '';

							if (($counter % 2 == 1 && $carousel_rows == 2) || ($counter % 3 == 1 && $carousel_rows == 3)){
		              $output .= '<li class="row-item swiper-slide"><ul>';
		          } else {
		              $swiper_slide = 'swiper-slide';
		          }

							$output .= '<li class="clients-item '.$swiper_slide.' elementor-repeater-item-' . esc_attr( $item['_id'] ) . '">';
								if (isset($item['link']) && !empty($item['link'])) {
									$output .= '<a href="'.esc_url($item['link']).'" title="'.esc_attr($item['name']).'">';
								}

									$output .= '<img src="'.esc_url($item['image']['url']).'" alt="'.esc_attr($item['name']).'" />';

								if (isset($item['link']) && !empty($item['link'])) {
									$output .= '</a>';
								}
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