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
class Elementor_Widget_Products extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-products', plugins_url('../../js/widget-products.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-products'];
  }

	public function get_name() {
		return 'et_products';
	}

	public function get_title() {
		return esc_html__( 'Products', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-woocommerce';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'products'];
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
				'layout',
				[
					'label' => esc_html__( 'Layout', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'grid'     => esc_html__('Grid','enovathemes-addons'),
						'list'     => esc_html__('List','enovathemes-addons'),
					], 
					'default' => 'grid'
				]
			);

			$this->add_control(
				'carousel',
				[
					'label' => esc_html__( 'Carousel', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'rows',
				[
					'label' => esc_html__( 'Rows', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 3,
					'step' => 1,
					'condition' => [
						'carousel' => 'true',
					],
					'default'=>1
				]
			);

			$this->add_control(
				'columns',
				[
					'label' => esc_html__( 'Columns', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 6,
					'step' => 1,
					'default'=>6,
				]
			);

			$this->add_control(
				'columns_tab_land',
				[
					'label' => esc_html__( 'Columns tablet landscape', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 5,
					'step' => 1,
					'default'=>4
				]
			);

			$this->add_control(
				'columns_tab_port',
				[
					'label' => esc_html__( 'Columns tablet portrait', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 4,
					'step' => 1,
					'default'=>3
				]
			);

			$this->add_control(
				'autoplay',
				[
					'label' => esc_html__( 'Autoplay', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				    'condition' => [
						'carousel' => 'true',
					],
				]
			);

			$this->add_control(
				'navigation_position',
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
				    	'carousel' => 'true',
					],
				]
			);

			$this->add_control(
				'navigation_type',
				[
					'label' => esc_html__( 'Navigation type', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'arrows'     => esc_html__('Arrows','enovathemes-addons'),
						'pagination' => esc_html__('Pagination','enovathemes-addons'),
						'both'       => esc_html__('Both','enovathemes-addons'),
					], 
					'default' => 'arrows'
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'content',
			[
				'label' => esc_html__( 'Content', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'nocache',
				[
					'label' => esc_html__( 'No caching', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'ajax',
				[
					'label' => esc_html__( 'Load products asynchronous?', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label_on' => esc_html__( 'Yes', 'enovathemes-addons' ),
					'label_off' => esc_html__( 'No', 'enovathemes-addons' ),
					'return_value' => 'true',
				]
			);

			$this->add_control(
				'quantity',
				[
					'label' => esc_html__( 'Quantity', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 50,
					'step' => 1,
					'default' => 12,
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
				foreach ($attributes as $attribute => $opt) {

					$this->add_control(
						$attribute,
						[
							'label' => ucfirst(str_replace('-',' ',$opt['label'])).' '.esc_html__('filter','enovathemes-addons'),
							'description' => esc_html__('Enter attribute term slugs (comma separated, no space)','enovathemes-addons'),
							'type' => \Elementor\Controls_Manager::TEXT,
						]
					);

					$this->add_control(
						$attribute.'_operator',
						[
							'label' => esc_html__('Operator','enovathemes-addons'),
							'type' => \Elementor\Controls_Manager::SELECT,
							'options' => [
								'IN' => esc_html__('IN','enovathemes-addons'),
								'NOT IN'  => esc_html__('NOT IN','enovathemes-addons'),
								'AND'=> esc_html__('AND','enovathemes-addons'),
							],
							'condition' => [
				    		$attribute.'!' => ''
							],
							'default' => 'IN',
						]
					);

				}
			}

			$this->add_control(
				'orderby',
				[
					'label' => esc_html__('Order by','enovathemes-addons'),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'date' => esc_html__('Date','enovathemes-addons'),
						'ID' => esc_html__('ID','enovathemes-addons'),
						'author' => esc_html__('Author','enovathemes-addons'),
						'title' => esc_html__('Title','enovathemes-addons'),
						'modified' => esc_html__('Modified','enovathemes-addons'),
						'rand' => esc_html__('Random','enovathemes-addons'),
						'comment_count' => esc_html__('Comment count','enovathemes-addons'),
						'menu_order' => esc_html__('Menu order','enovathemes-addons'),
					],
					'default' => 'date',
					'condition' => [
			    		'type!' => ['top_rated','best_selling']
						]
				]
			);

			$this->add_control(
				'order',
				[
					'label' => esc_html__('Sort order','enovathemes-addons'),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'ASC' => esc_html__('Ascending','enovathemes-addons'),
						'DESC' => esc_html__('Descending','enovathemes-addons'),
					],
					'default' => 'DESC',
				]
			);

			$this->add_control(
				'type',
				[
					'label' => esc_html__('Type','enovathemes-addons'),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'recent' => esc_html__('Recent','enovathemes-addons'),
						'sale' => esc_html__('Sale','enovathemes-addons'),
						'best_selling' => esc_html__('Best selling','enovathemes-addons'),
						'top_rated' => esc_html__('Top rated','enovathemes-addons'),
						'featured' => esc_html__('Featured','enovathemes-addons'),
						'custom' => esc_html__('Custom','enovathemes-addons'),
					],
					'default' => 'recent',
				]
			);

			$this->add_control(
				'ids',
				[
					'label' => esc_html__('Enter comma separated products ids','enovathemes-addons'),
					'type' => \Elementor\Controls_Manager::TEXT,
					'condition' => [
						'type' => 'custom',
					],
				]
			);

		$this->end_controls_section();

	}

	protected function render() {

		$settings  = $this->get_settings_for_display();
		$unique_id = $this->get_id();
		$settings['unique_id'] = $unique_id;

		if (class_exists('Woocommerce')) {

			extract($settings);

			if ($layout == "list") {
		    if ($columns > 5) {
		        $columns = 4;
		    }
		    if ($columns_tab_port > 2) {
		        $columns_tab_port = 2;
		    }
		    if ($columns_tab_land > 3) {
		        $columns_tab_land = 3;
		    }
			}

			global $post, $woocommerce;

			$tax_query = [];

			$query_options = [
			    'post_type'           => 'product',
			    'post_status'         => 'publish',
			    'ignore_sticky_posts' => 1,
			    'orderby'             => $orderby,
			    'order'               => $order,
			    'posts_per_page'      => absint($quantity),
			];

			$tax_query[] = [
			    'taxonomy'  => 'product_visibility',
			    'terms'     => ['exclude-from-catalog'],
			    'field'     => 'name',
			    'operator'  => 'NOT IN',
			];

			if ($type == "custom" && !empty($ids)) {
			    $query_options['post__in'] = array_map('trim', explode(',', $ids));
			} elseif ($type == "featured") {
			    $tax_query[] = [
			        'taxonomy' => 'product_visibility',
			        'field'    => 'name',
			        'terms'    => 'featured',
			        'operator' => 'IN',
			    ];
			} elseif ($type == "sale") {
			    $sales_ids = wc_get_product_ids_on_sale();
			    if (!is_wp_error($sales_ids) && !empty($sales_ids)) {
			        $query_options['post__in'] = array_merge([0], $sales_ids);
			    }
			} elseif ($type == "best_selling") {
			    $query_options['orderby']  = 'meta_value_num';
			    $query_options['meta_key'] = 'total_sales'; // Updated this line
			}

			if ($type != "custom") {

				$lang = get_bloginfo('language');
				$lang = explode('-', $lang);
				$lang = $lang[0];

				if (class_exists('SitePress') || function_exists('pll_the_languages')){
	          $lang = (function_exists('pll_the_languages')) ? pll_current_language() : ICL_LANGUAGE_CODE;
	      }

				$attributes = get_product_taxonomy_terms_list($lang);
				
				if (!is_wp_error($attributes)) {

					foreach ($attributes as $attribute => $opt) {

						if ($settings[$attribute]) {

							$the_attributes = (is_array($settings[$attribute])) ? $settings[$attribute] : explode(',', $settings[$attribute]);

							$attribute_name  = ($attribute == 'category') ? 'product_cat' : 'pa_'.$attribute;
							$attribute_terms = array();

							foreach ($the_attributes as $attr ) {
									array_push($attribute_terms,$attr);
							}

							if (!empty($attribute_terms)) {

								$tax_query[] = array(
									'taxonomy' => $attribute_name,
									'field'    => 'slug',
									'terms'    => $attribute_terms,
									'operator' => $settings[$attribute.'_operator'],
								);

							}
							
						}

					}
				}

			}

			if (!empty($tax_query)) {
				if (count($tax_query) > 1) {
					$tax_query['relation'] = 'AND';
				}
				$query_options['tax_query'] = $tax_query;
			}

			$output = '';

			if (empty($ajax) || \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
				$output = woo_products_ajax($settings,$query_options);
			} else {

				$atts = [
            'quantity'         		=> $quantity,
            'unique_id'        		=> $unique_id,
            'layout'           		=> $layout,
            'columns'          		=> $columns,
            'navigation_type'     => $navigation_type,
            'columns_tab_land' 		=> $columns_tab_land,
            'columns_tab_port' 		=> $columns_tab_port,
            'navigation_position' => $navigation_position,
            'carousel'            => $carousel,
						'rows'            		=> $rows,
						'autoplay'            => $autoplay
        ];

				$output = woo_products_ajax_placeholder($atts,$query_options);
			}

			if (!empty($output)) {
				echo $output;
			}

		}

	}

}