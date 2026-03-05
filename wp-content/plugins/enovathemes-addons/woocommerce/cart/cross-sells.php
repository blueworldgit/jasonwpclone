<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $cross_sells ) : ?>

	<?php

	    $class = array();

	    $size = 'medium';

	    $class[] = 'post-layout';
	    $class[] = 'product-layout';
	    $class[] = $size;
	    $class[] = 'layout-sidebar-none';

	?>
	<div class="related-products">

		<div class="cross-sells <?php echo implode(' ', $class); ?>">

			<h4><?php _e( 'You may be interested in&hellip;', 'enovathemes-addons' ) ?></h4>

			<?php woocommerce_product_loop_start(); ?>

				<?php foreach ( $cross_sells as $cross_sell ) : ?>

					<?php
					 	$post_object = get_post( $cross_sell->get_id() );

						setup_postdata( $GLOBALS['post'] =& $post_object );

						include(ENOVATHEMES_ADDONS.'woocommerce/content-product.php'); ?>

				<?php endforeach; ?>

			<?php woocommerce_product_loop_end(); ?>

		</div>

	</div>

<?php endif;

wp_reset_postdata();
