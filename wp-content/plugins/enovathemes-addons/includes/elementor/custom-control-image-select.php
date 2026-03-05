<?php

namespace ElementorControls;
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Elementor_Custom_Controls {

	public function includes() {
		require_once(ENOVATHEMES_ADDONS.'includes/elementor/image-selector-control.php');
	}

	public function register_controls() {
		$this->includes();
		$controls_manager = \Elementor\Plugin::$instance->controls_manager;
		$controls_manager->register_control(\Elementor\CustomControl\ImageSelector_Control::ImageSelector, new \Elementor\CustomControl\ImageSelector_Control());
	}

	public function __construct() {
		add_action('elementor/controls/controls_registered', [$this, 'register_controls']);
	}

}
new Elementor_Custom_Controls();


?>