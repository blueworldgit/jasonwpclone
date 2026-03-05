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
class Elementor_Widget_Person extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-person'];
  }

	public function get_name() {
		return 'et_person';
	}

	public function get_title() {
		return esc_html__( 'Person', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'person'];
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
			'name', [
				'label' => esc_html__( 'Name', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$this->add_control(
			'image', [
				'label' => esc_html__( 'Image', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
			]
		);

		$social_links_array = enovathemes_addons_social_icons(get_template_directory().'/images/icons/social/');

		foreach ($social_links_array as $social) {

			$this->add_control(
				$social,
				[
					'label' => ucfirst($social).' '.esc_html__( 'link', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::TEXT,
				]
			);

		}

		$this->end_controls_section();
		

	}

	protected function render() {

		$social_links_array = enovathemes_addons_social_icons(get_template_directory().'/images/icons/social/');

		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		$class = array();
		$class[] = 'person-item';


		$output.='<div class="'.implode(' ', $class).'">';

			if (isset($image['url']) && !empty($image['url'])) {
				$output.='<img alt="'.esc_attr($name).'" src="'.$image['url'].'" />';
			}

			$output.='<div class="info-wrap">';

				if (isset($name) && !empty($name)) {
					$output.='<h6 class="name">';
						$output.=esc_html($name);
						if (isset($title) && !empty($title)) {
							$output.='<span class="title">'.esc_html($title).'</span>';
						}
					$output.='</h6>';


					$output.='<div class="social-icons et-social-links">';

						foreach($settings as $social => $href) {
							if (in_array($social, $social_links_array) && !empty($href)) {
								$output .='<a class="'.$social.'" href="'.$href.'" title="'.ucfirst($social).'"></a>';
							}
						}

					$output.='</div>';

				}

			$output.='</div>';

		$output.='</div>';

		if (!empty($output)) {
			echo $output;
		}
	}

	protected function content_template() {
		?>

			<#
			
				view.addRenderAttribute( 'wrapper', 'class', 'person-item' );

			#>	


			<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>

				<# if (settings.image['url'].length) { #>
					<img src="{{{ settings.image['url'] }}}" alt="{{{ settings.name }}}"></div>
				<# } #>

				<div class="info-wrap">

					<# if (settings.name.length) { #>
						<h6 class="name">
							{{{ settings.name }}}
							<# if (settings.title.length) { #>
								<span class="title">{{{ settings.title }}}</span>
							<# } #>

						</h6>
					<# } #>

				</div>

			</div>

		<?php
	}

}