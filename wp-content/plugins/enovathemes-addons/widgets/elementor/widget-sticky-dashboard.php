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
class Elementor_Widget_Sticky_Dashboard extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-sticky-dashboard'];
  }

	public function get_name() {
		return 'et_sticky_dashboard';
	}

	public function get_title() {
		return esc_html__( 'Sticky Dashboard', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-device-mobile';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'sticky','dashboard'];
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
			'text', [
				'label' => esc_html__( 'Text', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Text' , 'enovathemes-addons' ),
				'label_block' => true,
			]);

		$repeater->add_control(
			'link', [
				'label' => esc_html__( 'Link', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$repeater->add_control(
			'class', [
				'label' => esc_html__( 'Class', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);
		
		$repeater->add_control(
			'link_class', [
				'label' => esc_html__( 'Link class', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$repeater->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'selectors'     => [
			        '{{WRAPPER}} {{CURRENT_ITEM}} .icon' => 'mask: url({{URL}}) no-repeat 50% 50%;-webkit-mask: url({{URL}}) no-repeat 50% 50%;',
			   ]
			]
		);
		
		$this->add_control(
			'list',
			[
				'label' => esc_html__( 'Sticky Dashboard', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'text' => esc_html__( 'Text #1', 'enovathemes-addons' )
					],
				],
				'title_field' => '{{{ text }}}',
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		if ( $settings['list'] ) {
			$output.='<ul class="sticky-dashboard manual">';
				foreach (  $settings['list'] as $item ) {
					$output .= '<li class="sticky-dashboard-item '.$item['class'].' elementor-repeater-item-' . esc_attr( $item['_id'] ).'">';
						if(isset($item['link'])){
							$output .= '<a class="'.esc_attr($item['link_class']).'" href="'.esc_url($item['link']).'" title="'.esc_attr($item['text']).'">';
						}
							$output .= '<span class="icon"></span>';
							if(isset($item['text'])){
								$output .= '<span class="text">'.esc_html($item['text']).'</span>';
							}
						if(isset($item['link'])){
							$output .= '</a>';
						}
					$output .= '</li>';
				}
			$output.='</ul>';
		}

		if (!empty($output)) {
			echo $output;
		}
	}


}