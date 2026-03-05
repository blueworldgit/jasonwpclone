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
class Elementor_Widget_Breadcrumbs extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-breadcrumbs'];
  }

	public function get_name() {
		return 'et_breadcrumbs';
	}

	public function get_title() {
		return esc_html__( 'Breadcrumbs', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-theme-builder';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'breadcrumbs'];
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


		$this->end_controls_section();

	}

	protected function render() {

		if (function_exists('enovathemes_addons_breadcrumbs')) {

			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {

				$text_before = '<span>';
        $text_after  = '</span>';
        $link_after  = '<span class="arrow"></span>';
        $output      = '';

        $home_text     = esc_html__('Home','enovathemes-addons');
				
				$output .= '<a href="#">' . $home_text . '</a>'.$link_after;
				$output .= $text_before . 'Your page' . $text_after;

	    	echo '<div class="et-breadcrumbs">'.$output.'</div>';
	    } else {
	    	enovathemes_addons_breadcrumbs();
	    }

			
		}

	}

}