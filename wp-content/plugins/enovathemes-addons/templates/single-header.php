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

<div id="wrap">

    <?php if (is_super_admin()): ?>

        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>

                <?php

                $transparent  = get_post_meta(get_the_ID(), 'enovathemes_addons_transparent', true);
                $sticky       = get_post_meta(get_the_ID(), 'enovathemes_addons_sticky', true);
                $shadow       = get_post_meta(get_the_ID(), 'enovathemes_addons_shadow', true);
                $type         = get_post_meta(get_the_ID(), 'enovathemes_addons_header_type', true);
                $transparent      = (empty($transparent)) ? "false" : "true";
                $sticky           = (empty($sticky)) ? "false" : "true";
                $shadow           = (empty($shadow)) ? "false" : "true";

                $class = array();

                ?>

                <?php if ($type == "mobile"): ?>

                    <?php

                        $class[] = 'header et-mobile et-clearfix';
                        $class[] = 'transparent-false';
                        $class[] = 'sticky-false';
                        $class[] = 'shadow-'.$shadow;
                        $class[] = 'mobile-true';
                        $class[] = 'desktop-true';

                    ?>
                    <header id="et-mobile-<?php the_ID(); ?>" <?php post_class(implode(" ", $class)); ?>>
                        <?php the_content(); ?>
                    </header>
                <?php elseif($type == "desktop"): ?>

                    <?php

                        $class[] = 'header';
                        $class[] = 'et-desktop';
                        $class[] = 'et-clearfix';

                        if ($type == "sidebar") {
                            $class[] = 'side-true';
                        }
                        
                        $class[] = 'transparent-'.$transparent;
                        $class[] = 'sticky-false';
                        $class[] = 'shadow-'.$shadow;
                        $class[] = 'mobile-true';
                        $class[] = 'desktop-true';

                    ?>
                    <header id="et-desktop-<?php the_ID(); ?>" <?php post_class(implode(" ", $class)); ?>>
                        <?php the_content(); ?>
                    </header>
                <?php endif; ?>

                <?php if ($transparent == "true"): ?>
                    <div class="transparent-header-underlay"></div>
                <?php endif ?>

            <?php endwhile; ?>
        <?php endif; ?>

    <?php else: ?>
        <br><br>
        <div class="container">
            <div class="alert note"><div class="alert-message"><?php echo esc_html__("You don't have sufficient privileges to view this page.","enovathemes-addons") ?></div></div>
        </div>
    <?php endif ?>

</div>

<?php wp_footer(); ?>
</body>
</html>
