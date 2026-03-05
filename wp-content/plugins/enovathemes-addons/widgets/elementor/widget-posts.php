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
class Elementor_Widget_Posts extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-posts', plugins_url('../../js/widget-posts.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-posts'];
  }

	public function get_name() {
		return 'et_post';
	}

	public function get_title() {
		return esc_html__( 'Posts', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-archive-posts';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'post'];
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
						'grid'     => esc_html__('Grid 1','enovathemes-addons'),
						'grid-2'   => esc_html__('Grid 2','enovathemes-addons'),
						'grid-3'   => esc_html__('Grid 3','enovathemes-addons'),
						'list'     => esc_html__('List','enovathemes-addons'),
						'full'     => esc_html__('Full','enovathemes-addons'),
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
				'columns',
				[
					'label' => esc_html__( 'Columns', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 4,
					'step' => 1,
					'default'=>4,
				]
			);

			$this->add_control(
				'columns_tab_land',
				[
					'label' => esc_html__( 'Columns tablet landscape', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 3,
					'step' => 1,
					'default'=>3,
				]
			);

			$this->add_control(
				'columns_tab_port',
				[
					'label' => esc_html__( 'Columns tablet portrait', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 2,
					'step' => 1,
					'default'=>2,
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
				'ajax',
				[
					'label' => esc_html__( 'Load posts with AJAX?', 'enovathemes-addons' ),
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

			$this->add_control(
				'excerpt',
				[
					'label' => esc_html__( 'Excerpt length', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 500,
					'step' => 1,
					'default' => 108,
				]
			);

			$this->add_control(
				'title_length',
				[
					'label' => esc_html__( 'Title length', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 500,
					'step' => 1,
					'default' => 59,
				]
			);

			$lang = get_bloginfo('language');
			$lang = explode('-', $lang);
			$lang = $lang[0];

			if (class_exists('SitePress') || function_exists('pll_the_languages')){
          $lang = (function_exists('pll_the_languages')) ? pll_current_language() : ICL_LANGUAGE_CODE;
      }

			$attributes = get_post_taxonomy_terms_list($lang);

			if (!is_wp_error($attributes)) {
				foreach ($attributes as $attribute => $opt) {

					$attribute_list = array();
					$terms = $opt['terms'];

					foreach ($terms as $slug => $obj) {
						$attribute_list[$slug] = $obj[$slug];
					}

					$this->add_control(
						$attribute,
						[
							'label' => $opt['label'].' '.esc_html__('filter','enovathemes-addons'),
							'type' => \Elementor\Controls_Manager::SELECT2,
							'multiple' => true,
							'options' => $attribute_list,
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
						'custom' => esc_html__('Custom','enovathemes-addons'),
					],
					'default' => 'recent',
				]
			);

			$this->add_control(
				'ids',
				[
					'label' => esc_html__('Enter comma separated post ids','enovathemes-addons'),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

		$this->end_controls_section();

	}

	protected function render() {

		$settings  = $this->get_settings_for_display();
		$unique_id = $this->get_id();
		$settings['unique_id'] = $unique_id;


		if ($settings['layout'] == "list" || $settings['layout'] == "full") {
			$settings['columns'] = 1;
			$settings['columns_tab_port'] = 1;
			$settings['columns_tab_land'] = 1;
		}

		extract($settings);

		global $post;

		
		$query_options = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $orderby,
			'order'               => $order,
			'posts_per_page' 	  => absint($quantity),
		);

		$tax_query = array();

		if ($type == "custom"){
			if ( ! empty( $ids ) ) {
				$query_options['post__in'] = array_map( 'trim', explode( ',', $ids ) );
			}
		}

		$lang = get_bloginfo('language');
		$lang = explode('-', $lang);
		$lang = $lang[0];

		if (class_exists('SitePress') || function_exists('pll_the_languages')){
        $lang = (function_exists('pll_the_languages')) ? pll_current_language() : ICL_LANGUAGE_CODE;
    }

		$attributes = get_post_taxonomy_terms_list($lang);
		
		if (!is_wp_error($attributes)) {

			foreach ($attributes as $attribute => $opt) {

				if ($settings[$attribute]) {

					$attribute_name  = $attribute;
					$attribute_terms = array();

					foreach ($settings[$attribute] as $attr ) {
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

		if (!empty($tax_query)) {
			if (count($tax_query) > 1) {
				$tax_query['relation'] = 'AND';
			}
			$query_options['tax_query'] = $tax_query;
		}

		$output = '';

		if (empty($ajax) || \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
			$output = et_posts_ajax($settings,$query_options);
			if (is_wp_error($output)) {
				$output = '';
			}
		} else {
			$atts = [
          'quantity'         		=> $quantity,
          'unique_id'        		=> $unique_id,
          'layout'           		=> $layout,
          'columns'          		=> $columns,
          'columns_tab_land' 		=> $columns_tab_land,
          'columns_tab_port' 		=> $columns_tab_port,
          'navigation_type'     => $navigation_type,
          'navigation_position' => $navigation_position,
          'carousel'            => $carousel,
					'autoplay'            => $autoplay,
					'excerpt'             => $excerpt,
					'title_length'        => $title_length,
      ];

			$output = posts_ajax_placeholder($atts,$query_options);
		}

		if (!empty($output)) {
			echo $output;
		}

	}

}