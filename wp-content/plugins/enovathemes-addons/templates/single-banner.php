<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<!-- META TAGS -->
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=8">
	<!-- LINK TAGS -->
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

    <?php if (is_super_admin()): ?>

        <?php if (have_posts()) : ?>
            <div id="et-content" class='content et-clearfix'>
                <?php while (have_posts()) : the_post(); ?>
                    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <main class="page-content et-clearfix">
                            <?php the_content(); ?>
                        </main>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <br><br>
        <div class="container">
            <div class="alert note"><div class="alert-message"><?php echo esc_html__("You don't have sufficient privileges to view this page.","enovathemes-addons") ?></div></div>
        </div>
    <?php endif ?>

<?php wp_footer(); ?>
</body>
</html>
