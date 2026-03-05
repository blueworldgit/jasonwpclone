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
class Elementor_Widget_Attribute extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-attributes'];
  }

	public function get_name() {
		return 'et_attributes';
	}

	public function get_title() {
		return esc_html__( 'Attribute', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-product-categories';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'attributes'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

		$this->start_controls_section(
			'content',
			[
				'label' => esc_html__( 'Content', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$lang = get_bloginfo('language');
			$lang = explode('-', $lang);
			$lang = $lang[0];

			if (class_exists('SitePress') || function_exists('pll_the_languages')){
          $lang = (function_exists('pll_the_languages')) ? pll_current_language() : ICL_LANGUAGE_CODE;
      }

			$attributes = get_product_taxonomy_terms_list($lang);

			if (!is_wp_error($attributes)) {

				$attribute_list = array();

				foreach ($attributes as $attribute => $opt) {
					$attribute_list[$attribute] = $opt['label'];
				}

				$this->add_control(
					'attribute',
					[
						'label' => esc_html__('Attribute','enovathemes-addons'),
						'type' => \Elementor\Controls_Manager::SELECT,
						'multiple' => true,
						'options' => $attribute_list,
					]
				);

				$this->add_control(
					'columns',
					[
						'label' => esc_html__( 'Columns', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 10,
						'step' => 1,
						'default'=>1,
					]
				);

				$this->add_control(
					'columns_tablet_land',
					[
						'label' => esc_html__( 'Columns tablet landscape', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 10,
						'step' => 1,
						'default'=>1,
					]
				);

				$this->add_control(
					'columns_tablet_port',
					[
						'label' => esc_html__( 'Columns tablet portrait', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 10,
						'step' => 1,
						'default'=>1,
					]
				);

				$this->add_control(
					'columns_mobile',
					[
						'label' => esc_html__( 'Columns mobile', 'enovathemes-addons' ),
						'type' => \Elementor\Controls_Manager::NUMBER,
						'min' => 1,
						'max' => 10,
						'step' => 1,
						'default'=>1,
					]
				);

			}


		$this->end_controls_section();

	}

	protected function render() {

		$output = '';

		$settings = $this->get_settings_for_display();

		extract($settings);


		if (isset($attribute) && !empty($attribute)) {

			$attribute = ($attribute == "category" ? 'product_cat' : 'pa_'.$attribute);
			
			if (taxonomy_exists($attribute)) {

				$args = array(
			    'taxonomy'   => $attribute,
			    'hide_empty' => true
				);

				$terms = get_terms($args);

				if (!is_wp_error($terms) && !empty($terms)) {

					$attibute_terms = [];

					foreach ($terms as $term) {
						$attibute_terms[$term->term_id] = array($term->name,$term->slug);
					}

					if (!empty($attibute_terms)) {

						$shop_link = (function_exists('wc_get_page_id')) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
            if ('' === get_option( 'permalink_structure' )) {
                $shop_link = get_home_url().'?post_type=product';
            }

						$separator = stripos($shop_link, '?') !== false ? '&' : '?';

						$output .= '<div class="attribute-search-wrapper grid et-grid-items" data-cl="'.esc_attr($columns).'" data-tb-ld-cl="'.esc_attr($columns_tablet_land).'" data-tb-pt-cl="'.esc_attr($columns_tablet_port).'" data-mb-cl="'.esc_attr($columns_mobile).'">';

				      $output .= '<input class="attr-search" type="text" placeholder="'.esc_attr__("Search for","enovathemes-addons").' '.strtolower(get_taxonomy($attribute)->labels->name).'">';

				      $output .= '<ul>';
				      		foreach ($attibute_terms as $key => $term) {

				      			$attribute = $attribute == 'product_cat' ? 'product_cat' : 'filter_'.$settings['attribute'];
										$term_link = $shop_link.$separator . $attribute . '=' . $term[1];

				          	$output .='<li>';
				          		$output .='<a href="'.esc_url($term_link).'" title="'.$term[0].'" data-value="'.$term[1].'">'.ucfirst(strtolower($term[0])).'</a>';
				          	$output .='</li>';
				      		}
				      $output .= '</ul>';

			      $output .= '</div>';

		      }

		      if (!empty($output)) {
		      	echo $output;
		      }

	      }

      }
		}

	}

}