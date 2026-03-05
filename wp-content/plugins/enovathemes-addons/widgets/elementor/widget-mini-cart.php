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
class Elementor_Widget_Mini_Cart extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-mini-cart', plugins_url('../../js/widget-mini-cart.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-mini-cart'];
  }

	public function get_name() {
		return 'et_mini_cart';
	}

	public function get_title() {
		return esc_html__( 'Mini cart', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-cart';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'cart'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

		$this->start_controls_section(
			'styling',
			[
				'label' => esc_html__( 'Styling', 'enovathemes-addons' ),
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
				'icon_color',
				[
					'label' => esc_html__( 'Icon color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .cart-toggle' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .cart-toggle:before' => 'background: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .cart-toggle .arrow' => 'background: {{VALUE}}',
				  ],
				  'default' => '#bdbdbd'
				]
			);

			$this->add_control(
				'bubble_color',
				[
					'label' => esc_html__( 'Bubble color', 'enovathemes-addons' ),
					'description' => esc_html__( 'Only for tablet and mobile devices', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .cart-contents' => 'color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'bubble_background_color',
				[
					'label' => esc_html__( 'Bubble background color', 'enovathemes-addons' ),
					'description' => esc_html__( 'Only for tablet and mobile devices', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .cart-contents' => 'background-color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);


			$this->add_control(
				'cart_title_color',
				[
					'label' => esc_html__( 'Cart box product title color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .cart-product-title' => 'color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'cart_title_color_hover',
				[
					'label' => esc_html__( 'Cart box product title color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .cart-product-title:hover' => 'color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'cart_color',
				[
					'label' => esc_html__( 'Cart box text color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .remove' => 'color: {{VALUE}}',
				  ],
				  'default' => '#777777'
				]
			);

			$this->add_control(
				'cart_background',
				[
					'label' => esc_html__( 'Cart box background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .cart-box' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);


			$this->add_control(
				'cart_button_color',
				[
					'label' => esc_html__( 'Cart button color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .woocommerce-mini-cart__buttons > a' => 'color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'cart_button_color_hover',
				[
					'label' => esc_html__( 'Cart button color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .woocommerce-mini-cart__buttons > a:hover' => 'color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'cart_button_back_color',
				[
					'label' => esc_html__( 'Cart button background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .woocommerce-mini-cart__buttons > a' => 'background-color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'cart_button_back_color_hover',
				[
					'label' => esc_html__( 'Cart button background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .mini-cart .woocommerce-mini-cart__buttons > a:hover' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#111111'
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

		global $woocommerce;

		$settings = $this->get_settings_for_display();

		extract($settings);

		$hide_default = empty($hide_default) ? 'false' : $hide_default;
		$hide_sticky  = empty($hide_sticky) ? 'false' : $hide_sticky;

		$output = '';

		$class   = array();
		$class[] = 'mini-cart';
    $class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;

    $output .= '<div class="'.implode(" ", $class).'">';

      $output .= '<div class="cart-toggle hbe-toggle">';

  			$output .= '<span class="cart-title">'.esc_html__('Cart','enovathemes-addons').'</span>';

      	if (class_exists('Woocommerce')) {
			
					if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {

						$output .= '<span class="cart-info">';
							$output .= '<span class="cart-contents">0</span>';
							$output .= '<span class="text">'.esc_html__('items','enovathemes-addons').'</span>';
						$output .= '</span>';

					} else {
						if ($woocommerce->cart->cart_contents_count) {

							$count = $GLOBALS['woocommerce']->cart->cart_contents_count;
							$text  = ($count > 1) ? esc_html__('items','enovathemes-addons') : esc_html__('item','enovathemes-addons');

							$output .= '<span class="cart-info">';
								$output .= '<span class="cart-contents">'.$GLOBALS['woocommerce']->cart->cart_contents_count.'</span>';
								$output .= '<span class="text">'.$text.'</span>';
							$output .= '</span>';

						} else {

							$output .= '<span class="cart-info">';
								$output .= '<span class="cart-contents">0</span>';
								$output .= '<span class="text">'.esc_html__('items','enovathemes-addons').'</span>';
							$output .= '</span>';

						}
					}
		      		
				} else {
					$output .= '<span class="cart-contents">';
						$output .= '<span class="cart-info">0</span>';
					$output .= '</span>';
				}

  		$output .= '</div>';

    	$output .= '<div class="cart-box box">';

    		$output .= '<div class="cart-toggle cart-off-toggle"></div><div class="et-clearfix"></div>';

    		if (class_exists('Woocommerce')){
    			$output .= enovathemes_addons_get_the_widget( 'WC_Widget_Cart', 'title=' );
    		} else {
    			$output .= esc_html__('Please install Woocommerce','enovathemes-addons');
    		}

    	$output .= '</div>';

    $output .= '</div>';



		if (!empty($output)) {
			echo $output;
		}

	}


}