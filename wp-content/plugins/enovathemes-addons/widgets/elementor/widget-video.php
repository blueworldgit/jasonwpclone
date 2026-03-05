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
class Elementor_Widget_Video extends \Elementor\Widget_Base {

	public function __construct($data = [], $args = null) {
      parent::__construct($data, $args);
      if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
       wp_register_script( 'widget-video', plugins_url('../../js/widget-video.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }
  }

  public function get_script_depends() {
    return [ 'widget-video'];
  }

	public function get_name() {
		return 'et_video';
	}

	public function get_title() {
		return esc_html__( 'Video', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-video-camera';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'video'];
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
				'mp4', [
					'label' => esc_html__( 'Video mp4 link here', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			$this->add_control(
				'embed', [
					'label' => esc_html__( 'Video embed link here', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

			$this->add_control(
				'poster',
				[
					'label' => esc_html__( 'Video poster', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
				]
			);

			$this->add_control(
				'lightbox',
				[
					'label' => esc_html__( 'Open in lightbox?', 'enovathemes-addons' ),
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

		$output     = '';
		
		if (isset($embed) && !empty($embed)) {
			$embed = str_replace('watch?v=', 'embed/', $embed);
      $embed = str_replace('//vimeo.com/', '//player.vimeo.com/video/', $embed);
    }

    $class = ['et-video','post-video','post-media'];

		$output .='<div class="'.implode(' ', $class).'">';

      if (isset($poster) && !empty($poster['url'])){

      	$link_class = array('video-btn');
      	$attributes = array();

      	if (isset($lightbox) && $lightbox == 'true') {

      		$url = (isset($mp4) && !empty($mp4)) ? $mp4 : ((isset($embed) && !empty($embed)) ? $embed : '');

      		$link_class[] = 'video-modal';
      		$attributes[] = 'data-source="'.esc_url($url).'"';
      		$attributes[] = 'href="'.esc_url($url).'"';

        } else {
        	$attributes[] = 'href="#"';
        }

        $attributes[] = 'class="'.implode(" ", $link_class).'"';

	        $output .='<div class="image-container">';

							$output .= enovathemes_addons_inline_image_placeholder($poster['id'],'full');

	            $output .='<a '.implode(" ", $attributes).'>';
	                $output .='<svg viewBox="0 0 512 512">';
                    $output .='<path class="back" d="M501.64,132.36a64.13,64.13,0,0,0-45.13-45.13c-40.06-11-200.33-11-200.33-11s-160.26,0-200.32,10.55a65.46,65.46,0,0,0-45.13,45.55C.19,172.42.19,255.51.19,255.51s0,83.5,10.54,123.14a64.16,64.16,0,0,0,45.13,45.13c40.48,11,200.33,11,200.33,11s160.26,0,200.32-10.55a64.11,64.11,0,0,0,45.13-45.13c10.55-40.06,10.55-123.14,10.55-123.14S512.61,172.42,501.64,132.36Z" />';
                    $output .='<path class="play" d="M346.89,261.61,205.11,350c-4.76,3-11.11-.24-11.11-5.61V167.62c0-5.37,6.35-8.57,11.11-5.61l141.78,88.38A6.61,6.61,0,0,1,346.89,261.61Z"/>';
                  $output .='</svg>';
	            $output .='</a>';

	        $output .='</div>';
      }

      if (empty($lightbox)){
        if(!empty($embed) && empty($mp4)) {
        		if (!isset($poster) || empty($poster['url'])){
				    	$output .='<div class="flex-mod">';
				    }
            	$output .='<iframe width="1280" height="720" allowfullscreen="allowfullscreen" allow="autoplay" frameBorder="0" src="'.$embed.'" class="iframevideo video-element"></iframe>';
        		if (!isset($poster) || empty($poster['url'])){
				    	$output .='</div>';
				    }
        } elseif(!empty($mp4)) {
          $output .='<video poster="'.THEME_IMG.'transparent.png'.'" class="lazy video-element" playsinline controls>';
              $output .='<source data-src="'.$mp4.'" src="'.THEME_IMG.'video_placeholder.mp4'.'" type="video/mp4">';
          $output .='</video>';
        }
      }

		$output .='</div>';

		if (!empty($output)) {
			echo $output;
		}

	}

}