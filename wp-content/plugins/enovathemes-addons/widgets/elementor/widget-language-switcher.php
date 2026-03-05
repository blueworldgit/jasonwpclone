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
class Elementor_Widget_Language_Switcher extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-language-switcher', plugins_url('../../js/widget-language-switcher.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-language-switcher'];
  }

	public function get_name() {
		return 'et_language_switcher';
	}

	public function get_title() {
		return esc_html__( 'Language switcher', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-language-switcher';
	}

	public function get_categories() {
		return [ 'header-builder' ];
	}

	public function get_keywords() {
		return [ 'language'];
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
				'type',
				[
					'label' => esc_html__( 'Type', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'horizontal' => esc_html__("Horizontal","enovathemes-addons"),
						'dropdown'   => esc_html__("Dropdown","enovathemes-addons"),
					],
					'default' => 'dropdown'
				]
			);

			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher .language-toggle' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher .language-toggle .arrow' => 'background: {{VALUE}}',
				  ],
				  'default' => '#bdbdbd',
				  'condition' => [
						'type' => 'dropdown',
					],
				]
			);

			$this->add_control(
				'background_color',
				[
					'label' => esc_html__( 'Background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher .language-toggle' => 'background-color: {{VALUE}}',
				  ],
				  'condition' => [
						'type' => 'dropdown',
					],
				]
			);

			$this->add_control(
				'border_color',
				[
					'label' => esc_html__( 'Border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher .language-toggle' => 'border-color:{{VALUE}}',
				  ],
				  'condition' => [
						'type' => 'dropdown',
					],
				]
			);

			$this->add_control(
				'border_width',
				[
					'label' => esc_html__( 'Border width', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher .language-toggle' => 'border-width:{{VALUE}}px',
				  ],
				  'condition' => [
						'type' => 'dropdown',
					],
				]
			);

			$this->add_control(
				'submenu_position',
				[
					'label' => esc_html__( 'Submenu  position', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'bottom'=> esc_html__('Bottom','enovathemes-addons'),
						'top'  => esc_html__('Top','enovathemes-addons'),
					],
					'default' => 'bottom',
					'condition' => [
						'type' => 'dropdown',
					],
				]
			);

			$this->add_control(
				'submenu_alignment',
				[
					'label' => esc_html__( 'Submenu  alignment', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => [
						'left'=> esc_html__('Left','enovathemes-addons'),
						'center'  => esc_html__('Center','enovathemes-addons'),
						'right'  => esc_html__('Right','enovathemes-addons'),
					],
					'default' => 'center',
					'condition' => [
						'type' => 'dropdown',
					],
				]
			);

			$this->add_control(
				'submenu_color',
				[
					'label' => esc_html__( 'Submenu color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher .language-box a' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher.horizontal a' => 'color: {{VALUE}}',
				  ],
				  'default' => '#111111'
				]
			);

			$this->add_control(
				'submenu_color_hover',
				[
					'label' => esc_html__( 'Submenu color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher .language-box a:hover' => 'color: {{VALUE}}',
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher.horizontal a:hover' => 'color: {{VALUE}}',
				  ],
				  'default' => $main_color
				]
			);

			$this->add_control(
				'submenu_background_color',
				[
					'label' => esc_html__( 'Submenu background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				        '{{WRAPPER}} > .elementor-widget-container > .language-switcher .language-box' => 'background-color: {{VALUE}}',
				  ],
				  'default' => '#ffffff',
				  'condition' => [
						'type' => 'dropdown',
					],
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
		
		$class[] = 'language-switcher';
    $class[] = 'hide-default-'.$hide_default;
		$class[] = 'hide-sticky-'.$hide_sticky;
		$class[] = $submenu_position;
		$class[] = $type;
		$class[] = $submenu_alignment;
		$class[] = 'box-align-center';
		// $class[] = 'no-ls';

		if (
			(isset($background_color) && !empty($background_color)) ||
			(isset($border_color) && !empty($border_color)) ||
			(isset($border_width) && !empty($border_width))
		) {
			$class[] = 'boxy';
		}

		$output .= '<div class="'.implode(" ", $class).'">';

			if ($type == "dropdown") {

	    	$output .= '<div class="language-toggle hbe-toggle">';

	    		if(function_exists('pll_the_languages')){
	    			$output .= '<span class="current-lang">'.pll_current_language().'</span>';
	    		} elseif(class_exists('SitePress')) {
	    			$output .= '<span class="current-lang">'.ICL_LANGUAGE_CODE.'</span>';
	    		} else {
	    			$output .= '<span class="current-lang">En</span>';
	    		}

	    		$output .= '<span class="arrow"></span>';
	    	$output .= '</div>';

    	}

    	if ($type == "dropdown") {
	    	$output .= '<div class="box language-box">';
		      $output .= '<div class="language-switcher-content">';
	    }

	      	if (class_exists('SitePress')){

	          $languages = icl_get_languages('skip_missing=0');

        		if(1 < count($languages)){
        			$output .= '<ul class="wpml-ls">';
						    foreach($languages as $l){
						    	$output .= '<li><a lang="'.$l['code'].'" href="'.$l['url'].'"><img src="'.$l['country_flag_url'].'" />'.$l['translated_name'].'</a></li>';
						    }
				    	$output .= '</ul>';
						}

					}elseif(function_exists('pll_the_languages')) {
						$output .= '<ul class="polylang-ls">';
							$output .=pll_the_languages(
								array(
									'echo'=>0,
									'show_flags'=>1,
									'hide_if_empty'=>0
								)
							);
						$output .= '</ul>';
					} else {
						$output .= '<ul class="no-ls">';
							$output .= '<li><a lang="en" href="#"><span style="margin-left:0.3em;">English</span></a></li>';
							$output .= '<li><a lang="de" href="#"><span style="margin-left:0.3em;">Deutsch</span></a></li>';
							$output .= '<li><a lang="fr" href="#"><span style="margin-left:0.3em;">Français</span></a></li>';
						$output .= '</ul>';
					}

			if ($type == "dropdown") {
					$output .= '</div>';
				$output .= '</div>';
			}

    $output .= '</div>';

		if (!empty($output)) {
			echo $output;
		}

	}

}