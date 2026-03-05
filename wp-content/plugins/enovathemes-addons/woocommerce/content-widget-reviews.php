<?php

defined( 'ABSPATH' ) || exit;

global $product;
?>
<li>
	<?php if ($product): ?>
		<?php do_action( 'woocommerce_widget_product_review_item_start', $args ); ?>
	
		<div class="product-image">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
				<div class="image-container">
					<?php echo mobex_enovathemes_build_post_media('woocommerce_thumbnail',false); ?>
				</div>
			</a>
		</div>

		<div class="product-body">
		
			<h6 class="product-title">
				<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>"><?php echo $product->get_name(); ?></a>
			</h6>
			<?php echo wc_get_rating_html( intval( get_comment_meta( $comment->comment_ID, 'rating', true ) ) );?>
			<span class="reviewer">
			<?php
				printf(
				    /* translators: %s: comment author name. */
				    esc_html__( 'by %s', 'enovathemes-addons' ),
				    esc_html( get_comment_author( $comment->comment_ID ) )
				);
			?>
			</span>

		</div>
		
		<?php do_action( 'woocommerce_widget_product_review_item_end', $args ); ?>
	<?php endif ?>
</li>
