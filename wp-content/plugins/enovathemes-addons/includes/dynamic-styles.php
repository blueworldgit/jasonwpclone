<?php

function enovathemes_addons_dynamic_styles_cached() {

    if ( false === ( $dynamic_css = get_transient( 'dynamic-styles-cached' ) ) ) {

	    $dynamic_css = $dynamic_css_1366 = $col_mob = $col_tab_land = $col_tab_port = $col_desktop = '';


		/* Typography
		/*-------------*/

			$main_typography = get_theme_mod('main_typography');

			if (!empty($main_typography)) {

				$typo_css = '';

				foreach ($main_typography as $key => $value) {
					if (!empty($value)) {
						if ($key == 'variant') {
							$key = 'font-weight';
						}
						if ($value == 'regular') {
							$value = '400';
						}
						$typo_css .=$key.':'.$value.';';
					}
				}
				
				if(isset($main_typography['font-family']) && !empty($main_typography['font-family'])){
					$dynamic_css .='.theme-main-font-family {font-family:'.$main_typography['font-family'].' !important;}';
				}

				if (!empty($typo_css)) {
					$dynamic_css .='body,p, textarea {'.$typo_css.'}';
				}
			}

			$headings_typography = get_theme_mod('headings_typography');
			$headings_color      = '';

			if (!empty($headings_typography)) {

				$typo_css = '';
				
				foreach ($headings_typography as $key => $value) {
					
					if (!empty($value)) {

						if ($key == 'color') {
							$headings_color = $value;
						}

						if ($key == 'variant') {
							$key = 'font-weight';
						}
						if ($value == 'regular') {
							$value = '400';
						}
					
						$typo_css .=$key.':'.$value.';';
					}
				}
				
				if(isset($headings_typography['font-family']) && !empty($headings_typography['font-family'])){
					$dynamic_css .='.theme-headings-font-family {font-family:'.$headings_typography['font-family'].' !important;}';
				}

				if (!empty($typo_css)) {
					$dynamic_css .='h1,h2,h3,h4,h5,h6 {'.$typo_css.'}';
				}

			}

		/* Vehicle filter
		/*-------------*/

			$vehicle_columns 		  = get_theme_mod('vehicle_columns');
			$vehicle_background_color = get_theme_mod('vehicle_background_color');
			$vehicle_text_color 	  = get_theme_mod('vehicle_text_color');

			$vehicle_columns   = (isset($vehicle_columns) && !empty($vehicle_columns)) ? $vehicle_columns : 1;
			$vehicle_background_color = (isset($vehicle_background_color) && !empty($vehicle_background_color)) ? $vehicle_background_color : '#f29f05';
			$vehicle_text_color = (isset($vehicle_text_color) && !empty($vehicle_text_color)) ? $vehicle_text_color : '#000';

			$dynamic_css .='.product-vehicle-filter {
				background-color:'.$vehicle_background_color.';
				color:'.$vehicle_text_color.';
			}';

		/* Color
		/*-------------*/

			$main_color   = get_theme_mod('main_color');
			$second_color = get_theme_mod('second_color');
			$accent_color = get_theme_mod('accent_color');

			$main_color   = (isset($main_color) && !empty($main_color)) ? $main_color : '#034c8c';
			$second_color = (isset($second_color) && !empty($second_color)) ? $second_color : '#f29f05';
			$accent_color = (isset($accent_color) && !empty($accent_color)) ? $accent_color : '#bf3617';


			if ($main_color) {

				$dynamic_css .='.enova-carousel .tns-controls button:hover,
				.gsap-lightbox-controls:hover,
				input[type="button"],
				input[type="reset"],
				input[type="submit"],
				button,
				.button,
				.restore-item,
				body .product .single_add_to_cart_button:hover,
				body .fbt-info .add_to_cart_all:hover,
				.product .button,
				.added_to_cart,
				.comment-reply-link,
				.checkout-button,
				.return-to-shop a,
				.woocommerce-mini-cart__buttons > a,
				.woocommerce-button,
				#page-links > a,
				.edit-link a,
				.woocommerce-message .button,
				.post-single-navigation a[rel="prev"]:hover:before,
				.post-single-navigation a[rel="next"]:hover:after,
				.post-social-share a:hover,
				.pf-slider .ui-slider-handle,
				.pf-slider .ui-slider-range,
				.product .button:hover:before,
				.product .added_to_cart:hover:before,
				.product .onsale, ul .product .label,
				.widget_layered_nav ul li a:after,
				.pf-item.list.attr ul li a:after,
				.pf-item.col.attr ul li a:after,
				.fbt-item:after,
				.quick-view-wrapper-close:hover,
				.woocommerce-MyAccount-navigation li.is-active a,
				.product-stock.outofstock,
				.mobile-total,
				.mobile-container.active > .mobile-toggle.active,
				.error404-button,
				.gsap-lightbox-toggle,
				.gsap-lightbox-nav,
				.et-login .info a:hover,
				.woocommerce-Address-title .edit:hover,
				.shop-page,
				.woocommerce-before-shop-loop .sale-products.chosen:after,
				.compare-table-toggle:hover,
				.post-ajax-button .button-back,
				.post-social-share a:hover:before,
				.product .button:after,
				.product .added_to_cart:after,
				.enovathemes-navigation li a:hover,
				.enovathemes-navigation li .current,
				.woocommerce-pagination li a:hover,
				.woocommerce-pagination li .current,
				ul .product .label,
				.single-product-wrapper > .label,
				.dashboard-mobile-toggle.active,
				.widget_price_filter .ui-slider .ui-slider-handle,
				.widget_price_filter .ui-slider-horizontal .ui-slider-range,
				.summary .wishlist-toggle:before, .summary .compare-toggle:before,
				.person-item .social-icons a:hover:before,
				.post-read-more:after,
				.cbt .cbt-button:after,
				.megamenu-list .view-all:after,
				.woocommerce-Tabs-panel--description ul li:before,
				.ask-form .ask-close:hover,
				.post-read-more:hover,
				.swiper-container[data-arrows-pos="inside"] .swiper-button:hover,
				.product > .post-social-share > .social-share:before,
				.popup-banner-toggle:hover,
				.transparent-header-underlay,
				.active-filters a:hover
				{background-color:'.$main_color.'}';

				$dynamic_css .='.comp .product .comp-form .button:hover,
				.comp .product .comp-form .added_to_cart:hover,
				.post-single-navigation a[rel="prev"]:hover:before,
				.post-single-navigation a[rel="next"]:hover:after,
				.product .buy-now-button.single_add_to_cart_button
				{background-color:'.$main_color.' !important}';

				$dynamic_css .='.enovathemes-navigation li a:hover,
				.enovathemes-navigation li .current,
				.woocommerce-pagination li a:hover,
				.woocommerce-pagination li .current
				{box-shadow:inset 0 0 0 1px '.$main_color.'}';

				$dynamic_css .='.ajax-add-to-cart-loading .tick {fill:'.$main_color.'}';

				$dynamic_css .='.product circle.loader-path {stroke:'.$main_color.'}';

				$dynamic_css .='a, .et-breadcrumbs a:hover,
				.loop-posts .post-title:hover, .post-categories a:hover,
				.post-meta a:hover,
				.widget_categories ul li a:hover,
				.widget_pages ul li a:hover,
				.widget_archive ul li a:hover,
				.widget_meta ul li a:hover,
				.widget_layered_nav ul li a:hover,
				.pf-item.list.attr ul li a:hover,
				.widget_nav_menu ul li a:hover,
				.widget_product_categories ul li a:hover,
				.wp-block-archives li a:hover,
				.pf-item.list.cat ul li a:hover,
				.pf-item.list.cat ul li a.chosen,
				.widget_tag_cloud .tagcloud a:hover,
				.post-tags a:hover,
				.widget_product_tag_cloud .tagcloud a:hover,
				.post-tags-single a:hover,
				.wp-block-tag-cloud a:hover,
				.post-single-navigation a[rel="prev"]:hover,
				.post-single-navigation a[rel="next"]:hover,
				.see-responses:hover,
				.comment-date-time .post-date > a:hover,
				.pf-item.label.attr ul li a:hover,
				.pf-item.label.attr ul li a.chosen,
				.filter-breadcrumbs a:hover,
				.product .post-category a:hover,
				.woocommerce-review-link:hover,
				.product .summary .price,
				.product_meta a:hover,
				.product .summary table.variations .reset_variations:hover,
				.cbt .cbt-button,
				.write-review:hover,
				.pf-item.col ul li a:hover,
				.entry-summary .wishlist-title:hover,
				.entry-summary .compare-title:hover,
				.entry-summary .ask-title:hover,
				.product-name a:hover,
				.product_list_widget .product-title a:hover,
				.elementor-widget a:not(.button):hover,
				.widget_user_vehicle_filter_widget .add-more,
				.product .button,
				.product .added_to_cart,
				.post-single-navigation a:hover,
				.post-read-more,
				.shop-widgets .widget .cat-item.current-cat > a
				{color:'.$main_color.'}';

				$dynamic_css .='.megamenu-list .view-all,
				.post-read-more,
				.et-login .widget_reglog .form-links .forgot:hover,
				.comp-details,
				.write-review:hover,
				.product-ask-toggle:hover
				{color:'.$main_color.' !important}';

				$dynamic_css .='.widget_tag_cloud .tagcloud a:hover,
				.post-tags a:hover,
				.widget_product_tag_cloud .tagcloud a:hover,
				.post-tags-single a:hover,
				.wp-block-tag-cloud a:hover,
				.pf-item.label.attr ul li a:hover,
				.pf-item.label.attr ul li a.chosen,
				.see-responses:hover,
				.pf-item.image.attr ul li:hover a,
				.pf-item.image.attr ul li a.chosen,
				.comp ul.products .product .button:hover,
				.comp ul.products .product .added_to_cart:hover,
				.write-review:hover
				{border-color:'.$main_color.'}';

				$dynamic_css .='.product-ask-toggle:hover
				{border-color:'.$main_color.' !important}';

				$dynamic_css .='circle.loader-path {stroke:'.$main_color.' !important;}';

			}

			if ($accent_color) {

				$dynamic_css .='.my-account-buttons li.logout a,
				.product-search .product-data .product-price,
				.product_list_widget > li .woocommerce-Price-amount,
				.product-search .product-data .sale-price,
				.cbt td.product-price,
				.product .price,
				.product .button:hover, .product .added_to_cart:hover,
				.fbt-info .selected > .total-price,
				.post-read-more:hover
				{color:'.$accent_color.' !important}';

				$dynamic_css .='.my-account-buttons li.logout a:before,
				.product .button:hover:after,
				.product .added_to_cart:hover:after,
				.post-date-side,
				.post-read-more:hover:after
				{background-color:'.$accent_color.' !important}';

				$dynamic_css .='.video-btn svg .back{fill:'.$accent_color.' !important}';

			}

			if ($second_color) {
				$dynamic_css .='.view-all > a {color:'.$second_color.' !important}';
				
				$dynamic_css .='.view-all > a > .arrow,
				.my-account-buttons .wishlist-contents,
				.product .onsale,
				.comp .product .comp-form .button,
				.comp .product .comp-form .added_to_cart,
				.layout-sidebar .widget_mailchimp,
				.product .single_add_to_cart_button,
				.section-accordion .section-accordion-title.active:after,
				body .product .add_to_cart_all,
				.sticky-dashboard span
				{background-color:'.$second_color.' !important}';

				$dynamic_css .='.widget_product_search button[type="submit"]:before,
				.et-accordion .accordion-title:after,
				.section-accordion .section-accordion-title:after,
				.et-accordion .accordion-title:after,
				form #searchsubmit + .search-icon,
				.sticky-dashboard .vehicle-filter-toggle.has-vehicle:before
				{background-color:'.$second_color.'}';

			}

		/* Site background
		/*-------------*/

			$site_background = get_theme_mod('site_background');
			$layout          = get_theme_mod('layout');

			if ($layout == 'boxed' && $site_background) {
				$back_css = '';
				foreach ($site_background as $key => $value) {
					if (!empty($value)) {
						$back_css .=$key.':'.$value.';';
					}
				}
				if (!empty($back_css)) {
					$dynamic_css .='html {'.$back_css.'}';
				}
			}

		/* Products
		---------------*/

			$title_min = get_theme_mod('product_title_min');
			$title_max = get_theme_mod('product_title_max');

			$title_min = (empty($title_min) || $title_min == null ? 40 : $title_min);
			$title_max = (empty($title_max) || $title_max == null ? 40 : $title_max);

			if ($title_min) {
				$dynamic_css .='.loop-products .post-title {
					min-height:'.$title_min.'px;
				}';
			}

			if ($title_max) {
				$dynamic_css .='.loop-products .post-title {
					max-height:'.$title_max.'px;
					overflow:hidden;
				}';
			}

		/*  Megamenu
        ---------------*/

        	if (function_exists('enovathemes_addons_megamenus')) {
				$megamenu = enovathemes_addons_megamenus();

				$grid = 1320;

	            if (!is_wp_error($megamenu)) {
	                foreach ($megamenu as $megam => $atts) {
	                    $megamenu_id = $megam;

						$tabbed  = get_post_meta($megamenu_id, 'enovathemes_addons_tabbed', true);
						$sidebar = get_post_meta($megamenu_id, 'enovathemes_addons_sidebar', true);

						if ($tabbed == "on" && $sidebar != 'on') {

							$tabs_background_color         = get_post_meta($megamenu_id, 'enovathemes_addons_tabs_background_color', true);
							$tabs_background_color_active  = get_post_meta($megamenu_id, 'enovathemes_addons_tabs_background_color_active', true);
							$tabs_content_background_color = get_post_meta($megamenu_id, 'enovathemes_addons_tabs_content_background_color', true);
							$tabs_text_color               = get_post_meta($megamenu_id, 'enovathemes_addons_tabs_text_color', true);
							$tabs_text_color_active        = get_post_meta($megamenu_id, 'enovathemes_addons_tabs_text_color_active', true);

							if(isset($tabs_background_color) && !empty($tabs_background_color)){
								$dynamic_css .= '#megamenu'.'-'.$megamenu_id.' .megamenu-tabset {background-color:'.$tabs_background_color.';}';
							}
							
							if(isset($tabs_background_color_active) && !empty($tabs_background_color_active)){
								$dynamic_css .= '#megamenu'.'-'.$megamenu_id.' .megamenu-tabset .tab-item.active {background-color:'.$tabs_background_color_active.';}';
							}
							
							if(isset($tabs_content_background_color) && !empty($tabs_content_background_color)){
								$dynamic_css .= '#megamenu'.'-'.$megamenu_id.' .megamenu-tabs-container {background-color:'.$tabs_content_background_color.';}';
							}
							
							if(isset($tabs_text_color) && !empty($tabs_text_color)){
								$dynamic_css .= '#megamenu'.'-'.$megamenu_id.' .megamenu-tabset .tab-item {color:'.$tabs_text_color.';}';
								$dynamic_css .= '#megamenu'.'-'.$megamenu_id.' .megamenu-tabset .tab-item > .megamenu-icon,
								#megamenu'.'-'.$megamenu_id.' .megamenu-tabset .tab-item > .arrow {background:'.$tabs_text_color.';}';
								
								$dynamic_css .= '#megamenu'.'-'.$megamenu_id.' .tab-item:after  {background:'.enovathemes_addons_hex_to_rgba($tabs_text_color,0.07).';}';
							}
							
							if(isset($tabs_text_color_active) && !empty($tabs_text_color_active)){
								$dynamic_css .= '#megamenu'.'-'.$megamenu_id.' .megamenu-tabset .tab-item.active {color:'.$tabs_text_color_active.';}';
								$dynamic_css .= '#megamenu'.'-'.$megamenu_id.' .megamenu-tabset .tab-item.active > .megamenu-icon,
								#megamenu'.'-'.$megamenu_id.' .megamenu-tabset .tab-item.active > .arrow {background:'.$tabs_text_color_active.';}';
							}

						}
						
	                }
	            }
            }

        /*  CSS
        ---------------*/

            $css = get_theme_mod('css');

		    if($css){
				$dynamic_css .= $css;
			}

			if (!empty($dynamic_css_1366)) {
				$dynamic_css .= '@media only screen and (min-width: 1366px)  {';
					$dynamic_css .= $dynamic_css_1366;
				$dynamic_css .= '}';
			}
		
		/*  Social links
		---------------*/

			$social_links_array = enovathemes_addons_social_icons(get_template_directory().'/images/icons/social/');

			foreach ($social_links_array as $social) {
				$dynamic_css .='.et-social-links a.'.$social.':before {mask:url('.THEME_SVG.'social/'.$social.'.svg) no-repeat 50% 50%;-webkit-mask:url('.THEME_SVG.'social/'.$social.'.svg) no-repeat 50% 50%; -webkit-mask-size: 40%;mask-size: 40%;}';
			}

		/*  Elements
		---------------*/

			for ($i=1; $i < 11; $i++) { 

				$col_desktop.='.grid.et-grid-items[data-cl="'.$i.'"] > ul,
				.carousel[data-carousel-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel
		    	{grid-template-columns: repeat('.$i.',minmax(0, 1fr));}';

		    	$col_desktop.='.carousel[data-carousel-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel > .swiper-slide:nth-child(n + '.($i+1).')
		    	{display:none !important;}';

		    	$col_tab_land.='.grid.et-grid-items[data-tb-ld-cl="'.$i.'"] > ul,
		    	.carousel[data-carousel-tablet-landscape-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel
		    	{grid-template-columns: repeat('.$i.',minmax(0, 1fr));}';

		    	$col_tab_land.='.carousel[data-carousel-tablet-landscape-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel > .swiper-slide:nth-child(n + '.($i+1).')
		    	{display:none !important;}';

		    	$col_tab_port.='.grid.et-grid-items[data-tb-pt-cl="'.$i.'"] > ul,
		    	.carousel[data-carousel-tablet-portrait-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel
		    	{grid-template-columns: repeat('.$i.',minmax(0, 1fr));}';

		    	$col_tab_port.='.carousel[data-carousel-tablet-portrait-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel > .swiper-slide:nth-child(n + '.($i+1).')
		    	{display:none !important;}';
			
			}

			for ($i=1; $i < 13; $i++) { 

				$col_desktop.='.grid.et-make[data-cl="'.$i.'"] > ul,
				.carousel.et-make[data-carousel-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel
		    	{grid-template-columns: repeat('.$i.',minmax(0, 1fr));}';

		    	$col_desktop.='.carousel.et-make[data-carousel-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel > .swiper-slide:nth-child(n + '.($i+1).')
		    	{display:none !important;}';
			
			}

			for ($i=1; $i < 5; $i++) { 

				$col_mob.='.grid.et-grid-items[data-mb-cl="'.$i.'"] > ul,
				.carousel[data-carousel-mobile-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel
		    	{grid-template-columns: repeat('.$i.',minmax(0, 1fr));}';

		 		$col_mob.='.carousel[data-carousel-mobile-columns="'.$i.'"] .swiper:not(.swiper-initialized) > .enova-carousel > .swiper-slide:nth-child(n + '.($i+1).')
		    	{display:none !important;}';
			
			}

		    for ($i=1; $i < 7; $i++) {

		    	$col_tab_port.='.swiper-container[data-tab-port-columns="'.$i.'"] .loop-posts {grid-template-columns: repeat('.$i.',minmax(0, 1fr));}';
		    	$col_tab_land.='.swiper-container[data-tab-land-columns="'.$i.'"] .loop-posts {grid-template-columns: repeat('.$i.',minmax(0, 1fr));}';

		    	$col_tab_port.='.swiper-container[data-tab-port-columns="'.$i.'"] .swiper-wrapper:not(.enova-carousel) {grid-template-columns: repeat('.($i+1).',minmax(0, 1fr));}';
		    	$col_tab_land.='.swiper-container[data-tab-land-columns="'.$i.'"] .swiper-wrapper:not(.enova-carousel) {grid-template-columns: repeat('.($i+1).',minmax(0, 1fr));}';


		    	$col_tab_port.='.swiper-container[data-tab-port-columns="'.$i.'"] .swiper-wrapper:not(.enova-carousel) > .post:nth-child(n + '.($i+2).') {display:none !important;}';
		    	$col_tab_land.='.swiper-container[data-tab-land-columns="'.$i.'"] .swiper-wrapper:not(.enova-carousel) > .post:nth-child(n + '.($i+2).') {display:none !important;}';

		    	$col_desktop.='.swiper-container[data-columns="'.$i.'"] .loop-posts {grid-template-columns: repeat('.$i.',minmax(0, 1fr));}';
		    	$col_desktop.='.swiper-container[data-columns="'.$i.'"] .swiper-wrapper:not(.enova-carousel) > .post:nth-child(n + '.($i+1).') {display:none !important;}';

		    	$width       = intval(100/($i+1));
                $widthVIN_ON = intval(100/($i+2));
                
                $dynamic_css .='.vehicle-filter.horizontal[data-count="'.$i.'"] > .atts {
	                grid-template-columns: repeat('.$i.',minmax(0, 1fr));
	                width:'.(100-$width).'%;
	            }';

	            $dynamic_css .='.vehicle-filter.horizontal[data-count="'.$i.'"] > .last {
	                width:'.$width.'%;
	            }';

	            $dynamic_css .='.vehicle-filter.horizontal.vin[data-count="'.$i.'"] > .atts {
                    width:'.(100-$widthVIN_ON*2).'%;
                }';

                $dynamic_css .='.vehicle-filter.horizontal.vin[data-count="'.$i.'"] > .last {
                    width:calc('.($widthVIN_ON*2).'% + 40px);
                }';

                $dynamic_css .='.vehicle-filter.horizontal.vin[data-count="'.$i.'"][data-rem="true"] > .atts {
                    width:'.(100-$width).'%;
                }';

                $dynamic_css .='.vehicle-filter.horizontal.vin[data-count="'.$i.'"][data-rem="true"] > .last {
                    width:calc('.$width.'%);
                }';

                if ($i <= 2) {
                	$dynamic_css .='.vehicle-filter.vertical[data-count="'.$i.'"] > .atts {
	                    grid-template-columns: repeat('.$i.',minmax(0, 1fr));
	                }';
                }

		    }

		/*  Responsive
		---------------*/

			$dynamic_css .= '@media only screen and (max-width: 767px)  {';
		
				$css767 = get_theme_mod('css-767');

				if($css767){
					$dynamic_css .= $css767;
				}

		    	$dynamic_css .= $col_mob;

		    	$dynamic_css .= '.grid .loop-products .product .button,
		    	.grid .loop-products .product .added_to_cart,
		    	.grid ul.products .product .button,
		    	.grid ul.products .product .added_to_cart,
		    	.related ul.products .product .button,
		    	.related ul.products .product .added_to_cart{
		    		background-color:'.$main_color.'
		    	}';

		    	if ($title_min) {
					$dynamic_css .='.comp .loop-products .post-title {
						min-height:'.$title_min.'px;
					}';
				}

				if ($title_max) {
					$dynamic_css .='.comp .loop-products .post-title {
						max-height:'.$title_max.'px;
						overflow:hidden;
					}';
				}

		    $dynamic_css .= '}';
		
			$css768 = get_theme_mod('css-768');

			if($css768){
				$dynamic_css .= '@media only screen and (min-width: 768px)  {';
					$dynamic_css .= $css768;
				$dynamic_css .= '}';
			}

		    $dynamic_css .= '@media only screen and (min-width: 768px) and (max-width: 1023px)  {';
		
				$css768_1023 = get_theme_mod('css-768-1023');

				if($css768_1023){
					$dynamic_css .= $css768_1023;
				}

				for ($i=5; $i < 7; $i++) { 
					
					$width       = intval(100/4);
	                $widthVIN_ON = intval(100/5);
	                
	                $dynamic_css .='.vehicle-filter.horizontal[data-count="'.$i.'"] > .atts {
		                grid-template-columns: repeat(3,minmax(0, 1fr));
		                width:'.(100-$width).'%;
		            }';

		            $dynamic_css .='.vehicle-filter.horizontal[data-count="'.$i.'"] > .last {
		                width:'.$width.'%;
		            }';

		            $dynamic_css .='.vehicle-filter.horizontal.vin[data-count="'.$i.'"] > .atts {
	                    width:'.(100-($widthVIN_ON)*2).'%;
	                }';
	                $dynamic_css .='.vehicle-filter.horizontal.vin[data-count="'.$i.'"] > .last {
	                    width:'.intval($widthVIN_ON*2).'%;
	                }';

			    }
		
		    	$dynamic_css .= $col_tab_port;
		    $dynamic_css .= '}';
		
			$dynamic_css .= '@media only screen and (max-width: 1023px)  {';
		
				$css1023 = get_theme_mod('css-1023');

				if($css1023){
					$dynamic_css .= $css1023;
				}
		
		    $dynamic_css .= '}';

		    $dynamic_css .= '@media only screen and (min-width: 1024px) and (max-width: 1279px)  {';
		
				$css1024_1279 = get_theme_mod('css-1024-1279');

				if($css1024_1279){
					$dynamic_css .= $css1024_1279;
				}


				for ($i=5; $i < 7; $i++) { 
					
					$width       = intval(100/4);
	                $widthVIN_ON = intval(100/5);
	                
	                $dynamic_css .='.vehicle-filter.horizontal[data-count="'.$i.'"] > .atts {
		                grid-template-columns: repeat(3,minmax(0, 1fr));
		                width:'.(100-$width).'%;
		            }';

		            $dynamic_css .='.vehicle-filter.horizontal[data-count="'.$i.'"] > .last {
		                width:'.$width.'%;
		            }';

		            $dynamic_css .='.vehicle-filter.horizontal.vin[data-count="'.$i.'"] > .atts {
	                    width:'.(100-($widthVIN_ON)*2).'%;
	                }';
	                $dynamic_css .='.vehicle-filter.horizontal.vin[data-count="'.$i.'"] > .last {
	                    width:'.intval($widthVIN_ON*2).'%;
	                }';

			    }

		
		    	$dynamic_css .= $col_tab_land;
		    $dynamic_css .= '}';
		
			$css1279 = get_theme_mod('css-1279');

			if($css1279){
				$dynamic_css .= '@media only screen and (max-width: 1279px)  {';
					$dynamic_css .= $css1279;
		    	$dynamic_css .= '}';
			}
		
			$css1280 = get_theme_mod('css-1280');

			$dynamic_css .= '@media only screen and (min-width: 1280px)  {';
	    		$dynamic_css .= $col_desktop;

				if($css1280){
					$dynamic_css .= $css1280;
				}
    		$dynamic_css .= '}';
		
			$css1280_1365 = get_theme_mod('css-1280-1365');

			if($css1280_1365){
				$dynamic_css .= '@media only screen and (min-width: 1280px) and (max-width: 1365px)  {';
					$dynamic_css .= $css1280_1365;
				$dynamic_css .= '}';
			}

			$css1366_1599 = get_theme_mod('css-1366-1599');

			if($css1366_1599){
				$dynamic_css .= '@media only screen and (min-width: 1366px) and (max-width: 1599px)  {';
					$dynamic_css .= $css1366_1599;
				$dynamic_css .= '}';
			}

			$css1600_1919 = get_theme_mod('css-1600-1919');

			if($css1600_1919){
				$dynamic_css .= '@media only screen and (min-width: 1600) and (max-width: 1919px)  {';
					$dynamic_css .= $css1600_1919;
				$dynamic_css .= '}';
			}

			$css1600 = get_theme_mod('css-1600');

			if($css1600){
				$dynamic_css .= '@media only screen and (min-width: 1600)  {';
					$dynamic_css .= $css1600;
				$dynamic_css .= '}';
			}
		
		$dynamic_css = enovathemes_addons_minify_css($dynamic_css);

		// do not set an empty transient - should help catch private or empty accounts.
		if ( ! empty( $dynamic_css ) ) {
			$dynamic_css = base64_encode(gzcompress ( serialize($dynamic_css) ));
			set_transient( 'dynamic-styles-cached', $dynamic_css, apply_filters( 'null_dynamic_css_cache_time', 0 ) );
		}
    }

    if ( ! empty( $dynamic_css ) ) {
        $dynamic_css = unserialize(gzuncompress(base64_decode($dynamic_css) ));

        return $dynamic_css;

    }

}

// Store merged Elementor template IDs for this request.
$GLOBALS['et_merged_elementor_ids'] = [];

function enovathemes_addons_include_dynamic_styles() {

    $dynamic_css = "";
    $version = rand(1,10).'.'.rand(1,10).'.'.rand(1,10);

    $merged_ids = []; // track IDs whose styles we merge

    $dynamic_css_caches = enovathemes_addons_dynamic_styles_cached();
    if (!empty($dynamic_css_caches)) {
        $dynamic_css .= $dynamic_css_caches;
    }

    if (is_plugin_active('elementor/elementor.php')) {

        /* Sitekit */
        if (is_plugin_active('elementor/elementor.php')) {
            $upload_dir = wp_upload_dir();
            $kit = get_option('elementor_active_kit');
            if (isset($kit) && !empty($kit)) {
                $kit_css = $upload_dir['basedir'].'/elementor/css/post-'.$kit.'.css';
                if (file_exists($kit_css)) {
                    $dynamic_css .= file_get_contents($kit_css);
                    // NOTE: Per request: we do NOT dequeue the kit CSS. Only header/footer/mega/banner.
                }
            }
        }

        /* Headers */
        $headers = enovathemes_addons_headers();
        if (!is_wp_error($headers)) {

        	foreach ($headers as $header => $atts) {
                if (array_key_exists($header,$headers)) {
                    $dynamic_css .= $atts['styles'];
                    $merged_ids[] = (int)$header;
                }
            }
            
        }

        /* Footer */
        $footers = enovathemes_addons_footers();
        if (!is_wp_error($footers)) {
        	foreach ($footers as $footer => $atts) {
                if (array_key_exists($footer,$footers)) {
                    $dynamic_css .= $atts['styles'];
                    $merged_ids[] = (int)$footer;
                }
            }
        }

        /* Megamenus */
        $megamenus = enovathemes_addons_megamenus();
        if (!is_wp_error($megamenus)) {
            foreach ($megamenus as $megamenu => $atts) {
                if (array_key_exists($megamenu,$megamenus)) {
                    $dynamic_css .= $atts['styles'];
                    $merged_ids[] = (int)$megamenu;
                }
            }
        }

        /* Banners */
        $banners = enovathemes_addons_banners();
        if (!is_wp_error($banners)) {
            foreach ($banners as $banner => $atts) {
                if (array_key_exists($banner,$banners)) {
                    $dynamic_css .= $atts['styles'];
                    $merged_ids[] = (int)$banner;
                }
            }
        }
    }

    if (!empty($dynamic_css)) {
        $dynamic_css = enovathemes_addons_minify_css($dynamic_css);

        $file = ENOVATHEMES_ADDONS . '/css/dynamic-styles.css';
        if (is_file($file)) {
            file_put_contents($file, $dynamic_css);
            wp_enqueue_style('dynamic-styles', plugins_url('/css/dynamic-styles.css', dirname(__FILE__) ), '', $version);
        }

        // Save merged IDs for the late dequeue step
        $GLOBALS['et_merged_elementor_ids'] = array_values(array_unique(array_map('intval', $merged_ids)));
    }
}
add_action('wp_enqueue_scripts', 'enovathemes_addons_include_dynamic_styles', 20);

/**
 * Dequeue/deregister Elementor's per-post CSS we already merged into our global CSS.
 * Works for both head (group 0) and footer (group 1) styles.
 */
function et_dequeue_elementor_post_css() {
    if (empty($GLOBALS['et_merged_elementor_ids']) || !is_array($GLOBALS['et_merged_elementor_ids'])) {
        return;
    }

    foreach ($GLOBALS['et_merged_elementor_ids'] as $id) {
        $id = (int) $id;
        if ($id <= 0) continue;

        $handle = 'elementor-post-' . $id;

        // Don't touch core styles.
        if (in_array($handle, ['admin-bar', 'dashicons'], true)) continue;

        if (wp_style_is($handle, 'enqueued')) {
            wp_dequeue_style($handle);
        }
        if (wp_style_is($handle, 'registered')) {
            wp_deregister_style($handle);
        }
    }
}

// 1) Early in head-print phase (before WP prints head styles):
add_action('wp_print_styles', 'et_dequeue_elementor_post_css', 1);

// 2) Early in footer-print phase (before WP prints footer styles via print_late_styles):
add_action('wp_print_footer_scripts', 'et_dequeue_elementor_post_css', 1);

// 3) Keep your original call too (in case Elementor enqueues late but before print):
add_action('wp_enqueue_scripts', 'et_dequeue_elementor_post_css', 9999);

/**
 * Final safety net: if a handle still tries to render, suppress its <link> tag.
 */
function et_filter_style_loader_tag($html, $handle, $href, $media) {
    if (empty($GLOBALS['et_merged_elementor_ids']) || !is_array($GLOBALS['et_merged_elementor_ids'])) {
        return $html;
    }
    foreach ($GLOBALS['et_merged_elementor_ids'] as $id) {
        if ($handle === 'elementor-post-' . (int)$id) {
            // Don't output the tag at all.
            return '';
        }
    }
    return $html;
}
add_filter('style_loader_tag', 'et_filter_style_loader_tag', 10, 4);


?>