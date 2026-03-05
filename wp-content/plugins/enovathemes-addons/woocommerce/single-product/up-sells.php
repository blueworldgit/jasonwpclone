<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $upsells ) : ?>

	<?php


	    $class = array();

	    $class[] = 'product-layout';
	    $class[] = 'grid';

	?>

	<div class="related-products">

		<section class="up-sells upsells product-carousel grid products <?php echo implode(' ', $class); ?>">

			<h4><?php esc_html_e( 'You may also like&hellip;', 'enovathemes-addons' ) ?></h4>

			<?php woocommerce_product_loop_start(); ?>

				<?php foreach ( $upsells as $upsell ) : ?>

					<?php
						$post_object = get_post( $upsell->get_id() );

						setup_postdata( $GLOBALS['post'] =& $post_object );

						global $product;

                        $output = '<li data-product="'.esc_attr($product->get_id()).'" class="post product" id="product-'.esc_attr($product->get_id()).'">';

                            $output .='<div class="post-inner et-item-inner">';
                                if(get_option( 'woocommerce_enable_ajax_add_to_cart' ) === "yes"){
                                    $output .='<div class="ajax-add-to-cart-loading">';
                                        $output .='<svg viewBox="0 0 56 56"><circle class="loader-path" cx="28" cy="28" r="20" /></svg>';
                                        $output .= '<svg viewBox="0 0 511.999 511.999" class="tick"><path d="M506.231 75.508c-7.689-7.69-20.158-7.69-27.849 0l-319.21 319.211L33.617 269.163c-7.689-7.691-20.158-7.691-27.849 0-7.69 7.69-7.69 20.158 0 27.849l139.481 139.481c7.687 7.687 20.16 7.689 27.849 0l333.133-333.136c7.69-7.691 7.69-20.159 0-27.849z"/></svg>';
                                    $output .='</div>';
                                }
                                $output .= mobex_enovathemes_loop_product_thumbnail('grid',false);
                                $output .= mobex_enovathemes_loop_product_title('grid');
                                $output .= mobex_enovathemes_loop_product_inner_close('grid');
                            $output .='</div>';
                            
                        $output .= '</li>';

                        echo $output;
					?>

				<?php endforeach; ?>

			<?php woocommerce_product_loop_end(); ?>

		</section>

	</div>

<?php endif;

wp_reset_postdata();
