<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
?>
<li>
	<?php do_action( 'woocommerce_widget_product_item_start', $args ); ?>
	<div class="product-image">
		<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<div class="image-container">
				<?php echo mobex_enovathemes_build_post_media('woocommerce_thumbnail',false); ?>
			</div>
		</a>
	</div>
	<div class="product-body">
	
		<h6 class="product-title">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo $product->get_name(); ?></a>
		</h6>
		
		<?php if ( ! empty( $show_rating ) ) : ?>
			<?php echo wc_get_rating_html( $product->get_average_rating() ); ?>
		<?php endif; ?>

		<?php echo $product->get_price_html(); ?>
	</div>

	<?php do_action( 'woocommerce_widget_product_item_end', $args ); ?>
</li>
