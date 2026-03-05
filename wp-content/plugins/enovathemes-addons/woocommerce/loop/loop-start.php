<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shop_navigation = get_theme_mod('shop_navigation');
$display         = woocommerce_get_loop_display_mode();

if (empty($shop_navigation)) {
	$shop_navigation = 'pagination';
}

$data_shop  = (isset($_GET["data_shop"]) && !empty($_GET["data_shop"])) ? $_GET["data_shop"] : "default";
if($data_shop == 'infinite'){
	$shop_navigation = 'infinite';
} elseif($data_shop == 'loadmore'){
	$shop_navigation = 'loadmore';
}

$class   = array();
$class[] = 'loop-posts';
$class[] = 'loop-products';
$class[] = 'products';
$class[] = $display;
$class[] = 'nav-'.$shop_navigation;

?>
<ul id="loop-products" class="<?php echo esc_attr(implode(' ', $class)); ?>" data-nav="<?php echo esc_attr($shop_navigation); ?>">
