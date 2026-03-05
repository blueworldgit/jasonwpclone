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
class Elementor_Widget_Pricing_Table extends \Elementor\Widget_Base {

  public function get_script_depends() {
    return [ 'widget-pricing-table'];
  }

	public function get_name() {
		return 'et_pricing_table';
	}

	public function get_title() {
		return esc_html__( 'Pricing table', 'enovathemes-addons' );
	}

	public function get_icon() {
		return 'eicon-price-table';
	}

	public function get_categories() {
		return [ 'enovathemes' ];
	}

	public function get_keywords() {
		return [ 'pricing table'];
	}

	protected function register_controls() {

		$main_color   = get_theme_mod('main_color');
		$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#f29f05';

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
				'label' => esc_html__( 'Item', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Item' , 'enovathemes-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'price', [
				'label' => esc_html__( 'Price', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'default' => 50,
			]
		);

		$this->add_control(
			'currency', [
				'label' => esc_html__( 'Currency', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '$',
			]
		);

		$this->add_control(
			'currency_pos',
			[
				'label' => esc_html__( 'Currency position', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'before' => esc_html__('Before price','enovathemes-addons'),
					'after'  => esc_html__('After price','enovathemes-addons'),
				],
				'default' => 'before'
			]
		);

		$this->add_control(
			'label', [
				'label' => esc_html__( 'Label', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'Starter plan',
			]
		);

		$this->add_control(
			'plan', [
				'label' => esc_html__( 'Plan', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'Monthly',
			]
		);

		$this->add_control(
			'description', [
				'label' => esc_html__( 'Description', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
			]
		);

		$this->add_control(
			'link', [
				'label' => esc_html__( 'Link', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '#link',
			]
		);

		$this->add_control(
			'link_text', [
				'label' => esc_html__( 'Button text', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'Discover more',
			]
		);

		$this->add_control(
			'list',
			[
				'label' => esc_html__( 'Pricing table items', 'enovathemes-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'text' => esc_html__( 'Text #1', 'enovathemes-addons' ),
					],
				],
				'title_field' => '{{{ text }}}',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'styling',
			[
				'label' => esc_html__( 'Styling', 'enovathemes-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'back_color',
				[
					'label' => esc_html__( 'Pricing table background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'border_color',
				[
					'label' => esc_html__( 'Pricing table border color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item' => 'outline-color: {{VALUE}}',
				    ],
				    'default' => '#e0e0e0'
				]
			);

			$this->add_control(
				'color',
				[
					'label' => esc_html__( 'Pricing table color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item' => 'color: {{VALUE}}',
				      '{{WRAPPER}} .pricing-table-item .title' => 'color: {{VALUE}}',
				      '{{WRAPPER}} .pricing-table-item ul li:nth-child(odd):before' => 'background: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

			$this->add_control(
				'label_color',
				[
					'label' => esc_html__( 'Pricing table label background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item .label' => 'background-color: {{VALUE}}',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'label_back_color',
				[
					'label' => esc_html__( 'Pricing table label color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item .label' => 'color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'button_color',
				[
					'label' => esc_html__( 'Pricing table button color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item .button' => 'color: {{VALUE}}',
				      '{{WRAPPER}} .pricing-table-item .button .icon' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'button_back_color',
				[
					'label' => esc_html__( 'Pricing table button background color', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item .button' => 'background-color: {{VALUE}}',
				    ],
				    'default' => $main_color
				]
			);

			$this->add_control(
				'button_back_color_hover',
				[
					'label' => esc_html__( 'Pricing table button color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item .button:hover' => 'color: {{VALUE}}',
				      '{{WRAPPER}} .pricing-table-item .button:hover .icon' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#ffffff'
				]
			);

			$this->add_control(
				'button_color_hover',
				[
					'label' => esc_html__( 'Pricing table button background color hover', 'enovathemes-addons' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors'     => [
				      '{{WRAPPER}} .pricing-table-item .button:hover' => 'background-color: {{VALUE}}',
				    ],
				    'default' => '#111111'
				]
			);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		extract($settings);

		$output = '';

		$class = array();
		$class[] = 'pricing-table-item';

		if ( $settings['list'] ) {

			$output.='<div class="'.implode(' ', $class).'">';

				if (isset($label) && !empty($label)) {
					$output.='<span class="label">'.esc_html($label).'</span>';
				}

				$output.='<div class="price-wrap">';

					if (isset($price) && !empty($price)) {

						$currency  = esc_html($currency);

						$price = (($currency_pos == 'before') ? $currency.$price : $price.$currency);

						$output.='<span class="price">'.$price.'</span>';
					}

					if (isset($plan) && !empty($plan)) {
						$output.='<span class="plan">'.esc_html($plan).'</span>';
					}

				$output.='</div>';

				if (isset($title) && !empty($title)) {
					$output.='<h5 class="title">'.esc_html($title).'</h5>';
				}

				if (isset($description) && !empty($description)) {
					$output.='<p class="description">'.esc_html($description).'</p>';
				}

				$output.='<ul>';
					foreach (  $settings['list'] as $item ) {
						$output .= '<li>' . esc_html($item['text']) . '</li>';
					}
				$output.='</ul>';

				if (isset($link) && !empty($link)) {
					$output.='<a href="'.esc_url($link).'" class="et-button button">'.esc_html($link_text).'<span class="icon"></span></a>';
				}	

			$output.='</div>';
		}

		if (!empty($output)) {
			echo $output;
		}
	}

	protected function content_template() {
		?>
		<# if ( settings.list.length ) { #>

			<#
			
				view.addRenderAttribute( 'wrapper', 'class', 'pricing-table-item' );

				var price = ((settings.currency_pos == 'before') ? settings.currency+settings.price : settings.price+settings.currency);

			#>	


			<div {{{ view.getRenderAttributeString( "wrapper" ) }}}>

				<# if (settings.label.length) { #>
					<span class="label">{{{ settings.label }}}</span>
				<# } #>

				<div class="price-wrap">

					<# if (price.length) { #>
						<span class="price">{{{ price }}}</span>
					<# } #>

					<# if (settings.plan.length) { #>
						<span class="plan">{{{ settings.plan }}}</span>
					<# } #>

				</div>

				<# if (settings.title.length) { #>
					<h4 class="title">{{{ settings.title }}}</h4>
				<# } #>

				<# if (settings.description.length) { #>
					<p class="description">{{{ settings.description }}}</p>
				<# } #>

				<ul>
					<# _.each( settings.list, function( item ) { #>
						<li>{{{ item.text }}}</li>
					<# }); #>
				</ul>

				<# if (settings.link.length) { #>
					<a href="{{{ settings.link }}}" class="et-button button">{{{ settings.link_text }}}</a>
				<# } #>

			</div>

		<# } #>
		<?php
	}

}