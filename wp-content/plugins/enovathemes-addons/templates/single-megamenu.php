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
            <?php while (have_posts()) : the_post(); ?>

                <?php

                    $header_before = '';
                    $header_after  = '';

                    $megamenu_width = get_post_meta(get_the_ID(), 'enovathemes_addons_megamenu_width', true);

                    if ($megamenu_width != "100") {
                        $header_before = '<div class="container">';
                        $header_after = '</div>';
                    }

                    $header_style = '';

                    if ($megamenu_width != 'grid') {
                        $header_style = 'max-width:100%;width:100%;';
                    }

                    $class = array();

                    $class[] = 'sub-menu';
                    $class[] = 'megamenu-single';
                    $class[] = 'et-clearfix';

                ?>
                <?php echo $header_before;  ?>
                <header class="header et-desktop et-clearfix" style="<?php echo esc_attr($header_style); ?>">
                    <div class="header-menu-container nav-menu-container">
                        <div class="header-menu nav-menu megamenu-demo">
                            <div id="megamenu-<?php the_ID(); ?>" <?php post_class(implode(" ", $class)); ?>>
                                <?php the_content(); ?>
                            </div>
                        </div>
                    </div>
                </header>
                <?php echo $header_after;  ?>
            <?php endwhile; ?>
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
