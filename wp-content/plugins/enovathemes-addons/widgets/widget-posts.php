<?php

add_action('widgets_init', 'enovathemes_addons_register_recent_posts_widget');
function enovathemes_addons_register_recent_posts_widget(){
	register_widget( 'Enovathemes_Addons_WP_Widget_Posts' );
}

class Enovathemes_Addons_WP_Widget_Posts extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_et_recent_entries', 'description' => esc_html__( "Your site&#8217;s most recent Posts.", 'enovathemes-addons') );
		parent::__construct('et-recent-posts', esc_html__('*Recent Posts', 'enovathemes-addons'), $widget_ops);
		$this->alt_option_name = 'widget_et_recent_entries';
	}

	function widget($args, $instance) {

		global $post;

		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_recent_posts', 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();
		extract($args);

		$title  = isset($instance['title']) ? apply_filters( 'widget_title', $instance['title'] ) : '';

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( esc_attr($instance['number']) ) : 5;
		if ( ! $number )
			$number = 5;

		/**
		 * Filter the arguments for the Recent Posts widget.
		 *
		 * @since 3.4.0
		 *
		 * @see WP_Query::get_posts()
		 *
		 * @param array $args An array of arguments used to retrieve the recent posts.
		 */
		$r = new WP_Query( apply_filters( 'widget_posts_args', array(
			'posts_per_page'      => $number,
			'no_found_rows'       => true,
			'post_status'         => 'publish'
		) ) );

		if ($r->have_posts()) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<div class="post-layout list"><ul class="only-posts loop-posts">
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>

			<li <?php post_class(); ?>>
				<div class="post-inner et-item-inner"> 	
					<?php if (has_post_thumbnail()): ?>
						<div class="post-image overlay-hover post-media">
							<div class="image-container">
								<?php echo mobex_enovathemes_build_post_media('thumbnail',false); ?>
								<?php echo mobex_enovathemes_post_image_overlay('list'); ?>
							</div>
						</div>
					<?php endif ?>
					<div class="post-body">

						<div class="post-meta">

		                    <div class="post-date"><?php echo get_the_date(); ?></div>


		                </div>


						<h4 class="post-title entry-title"><a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a></h4>

					</div>

				</div>
			</li>

		<?php endwhile; ?>
		</ul></div>
		<?php echo $after_widget; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'widget_et_recent_posts', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_et_recent_entries']) )
			delete_option('widget_et_recent_entries');

		return $instance;
	}

	function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo esc_html__( 'Title:', 'enovathemes-addons'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php echo esc_html__( 'Number of posts to show:', 'enovathemes-addons'); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

<?php
	}
}