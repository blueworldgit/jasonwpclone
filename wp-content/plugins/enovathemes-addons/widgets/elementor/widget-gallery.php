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
class Elementor_Widget_Gallery extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-gallery', plugins_url('../../js/widget-gallery.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-gallery'];
  }

	public function get_name() {
		return 'et_gallery';
	}

	public function get_title() {
		return esc_html__( 'Gallery', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'gallery'];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'general',
			[
				'label' => esc_html__( 'General', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'images',
				[
					'label' => esc_html__( 'Images', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::GALLERY,
				]
			);

			$this->add_control(
				'size',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Size', 'enovathemes-addons' ),
					'options' => [
						'thumbnail' => esc_html__('Thumbnail','enovathemes-addons'),
						'medium'    => esc_html__('Medium','enovathemes-addons'),
						'large'     => esc_html__('Large','enovathemes-addons'),
						'full'      => esc_html__('Full','enovathemes-addons'),
					],
					'default' => 'full'
				]
			);

			$this->add_control(
				'layout',
				[
					'type' => \Elementor\Controls_Manager::SELECT,
					'label' => esc_html__( 'Layout', 'enovathemes-addons' ),
					'options' => [
						'grid'     => esc_html__('Grid','enovathemes-addons'),
						'carousel' => esc_html__('Carousel','enovathemes-addons'),
						'slider'   => esc_html__('Slider','enovathemes-addons'),
					],
					'default' => 'grid'
				]
			);

			$this->add_control(
				'lightbox',
				[
					'label' => esc_html__( 'Lightbox?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
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
						'layout' => ['carousel','slider'],
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
						'layout' => ['carousel','slider'],
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
					'condition' => [
						'layout' => ['carousel','grid'],
					]
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
					'condition' => [
						'layout' => ['carousel','grid'],
					]
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
					'condition' => [
						'layout' => ['carousel','grid'],
					]
				]
			);

			$this->add_control(
				'carousel_columns_mobile',
				[
					'label' => esc_html__( 'Columns mobile', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 3,
					'step' => 1,
					'default'=>2,
					'condition' => [
						'layout' => ['carousel','grid'],
					]
				]
			);

		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		extract($settings);

		$output     = '';
		$class      = array();
		$attributes = array();

		$class[] = 'et-gallery';
		$class[] = $layout;

		if ($layout == "carousel" || $layout == "slider") {

			$class[] = 'swiper-container';

			if ($layout == "carousel") {
				$attributes[] = 'data-carousel-gatter="8"';
			} else {
				$carousel_columns = 1;
				$carousel_columns_tablet_land = 1;
				$carousel_columns_tablet_port = 1;
				$carousel_navigation_position = 'inside';
			}

			$attributes[] = 'data-carousel-columns="'.$carousel_columns.'"';
			$attributes[] = ($layout == "carousel") ? 'data-carousel-mobile-columns="2"' : 'data-carousel-mobile-columns="1"';
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
			$attributes[] = 'data-mb-cl="'.$carousel_columns_mobile.'"';
		}

		if (isset($images) && !empty($images)) {

			$unique_id = $this->get_id();

			$output .='<div class="'.implode(' ', $class).'" '.implode(' ', $attributes).'>';

				if ($layout == "carousel" || $layout == "slider") {
					$output .= '<div id="swiper-'.$unique_id.'" class="swiper">';
						$output.='<ul class="swiper-wrapper enova-carousel">';
				} else {
					$output.='<ul>';
				}

					foreach ($settings['images'] as $image) {

						$link_before = '';
						$link_after  = '';

						$image_full = wp_get_attachment_image_src($image['id'], "full");
						$image_size = wp_get_attachment_image_src($image['id'], $size);

						if (isset($lightbox) && $lightbox == "true") {
							$link_before = '<a data-elementor-lightbox-slideshow="'.$unique_id.'" href="'.esc_url($image_full[0]).'">';
							$link_after  = '</a>';
						}

						$output .='<li class="et-gallery-item swiper-slide">';
							$output .=$link_before;

								if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
									$output .= '<img src="'.$image_size[0].'">';
								} else {
									$output .= enovathemes_addons_inline_image_placeholder($image['id'],$size);
								}
								
							$output .=$link_after;
						$output .='</li>';

					}

				$output .='</ul>';
				if ($layout == "carousel" || $layout == "slider") {
					$output.='</div>';
					$output .= '<div id="prev-'.$unique_id.'" class="container-swiper-nav swiper-button swiper-button-prev"></div><div id="next-'.$unique_id.'" class="container-swiper-nav swiper-button swiper-button-next"></div><div id="swiper-pagination-'.$unique_id.'" class="swiper-pagination container-swiper-nav"></div>';
				}
			$output .='</div>';
	
		}

		if (!empty($output)) {
			echo $output;
		}

	}

}