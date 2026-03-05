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
class Elementor_Widget_Wishlist extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-wishlist'];
  }

	public function get_name() {
		return 'et_wishlist';
	}

	public function get_title() {
		return esc_html__( 'Wishlist', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-wishlist';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'wishlist'];
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
		

			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-wishlist-icon:before' => 'background: {{VALUE}}',
				  ],
				  'default' => '#bdbdbd'
				]
			);

			$this->add_control(
				'text_color',
				[
					'label' => esc_html__( 'Bubble text color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-wishlist-icon .wishlist-contents' => 'color: {{VALUE}}',
				  ],
				  'default' => '#ffffff'
				]
			);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Bubble background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .et-wishlist-icon .wishlist-contents' => 'background-color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .et-wishlist-icon .wishlist-contents:after' => 'background-color: {{VALUE}}',
				  ],
				  'default' => $main_color
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

		$hide_default      = empty($hide_default) ? 'false' : $hide_default;
		$hide_sticky       = empty($hide_sticky) ? 'false' : $hide_sticky;

		$output = '';

		$attributes = array();
		$class      = array();
		
		$class[] = 'et-wishlist-icon';
		$class[] = 'hbe-toggle';
    $class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;

		$output .= '<a href="'.esc_url(get_permalink(get_option('woocommerce_myaccount_page_id') ).'wishlist').'" title="'.esc_attr__("Wishlist","enovathemes-addons").'" class="'.implode(" ", $class).'">';
      $output .='<span class="wishlist-contents">0</span>';
    $output .= '</a>';

		if (!empty($output)) {
			echo $output;
		}

	}

	protected function content_template() { ?>

		<#

			view.addRenderAttribute( 'wrapper', 'class', 'et-wishlist-icon' );
			view.addRenderAttribute( 'wrapper', 'class', 'hbe-toggle' );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-default-'+settings.hide_default );
			view.addRenderAttribute( 'wrapper', 'class', 'hide-sticky-'+settings.hide_sticky );
			view.addRenderAttribute( 'wrapper', 'href', settings.link );

		#>

		<a {{{ view.getRenderAttributeString( "wrapper" ) }}}>
			<span class="wishlist-contents">0</span>
		</a>
		
	<?php }

}