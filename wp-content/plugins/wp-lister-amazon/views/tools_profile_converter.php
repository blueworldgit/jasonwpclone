<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>


<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/amazon-32x32.png) no-repeat;" id="wpl-icon"><br /></div>

	<?php include_once( dirname(__FILE__).'/tools_tabs.php' ); ?>
	<?php echo $wpl_message ?>

    <style>

        #right p {
            font-size: 14px;
             font-family: 'Arial', sans-serif;
             line-height: 1.6;
             color: #333;

         }

        #right ul {
            padding-left: 20px;
            margin-bottom: 15px;
        }

        .conversion-info ul li {
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 8px;
            margin-left: 25px;
            list-style-type: disc;
            color: #444;
        }

        .template-card {
            background: #e2e2e2;
            padding: 20px;
            margin-bottom: 15px;
            /*border-radius: 10px;*/
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .template-card.active {
            background: #fff;
        }
        .template-card h2 {
            margin: 0;
        }
        .profiles, .products {
            margin-top: 10px;
            border: 1px solid #ccc;
            /*border-radius: 5px;*/
            overflow: hidden;
            display: none;
        }
        .profile, .product {
            background: #f9f9f9;
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }
        .profile:last-child, .product:last-child {
            border-bottom: none;
        }
        .highlight {
            background: #eee;
        }
        .toggle-link {
            color: blue;
            cursor: pointer;
            text-decoration: underline;
        }
        .template-action-box {
            text-align: left;
            float: right;
            width: 50%;
        }
        .template-action-box select {
            width: 90%;
            margin-bottom:5px;
        }
        .convert-button {
            width: 100%;
            padding: 0px;
            font-size: 21px !important;
        }
        /* Small devices (portrait tablets and large phones, 576px and up) */
        @media (max-width: 1099px) {
            .container {
                width: 100%;
            }
            .conversion-info {
                display:block;
            }
            #left {
                float: none;
                width: 100%;
            }
            #right {
                float: none;
                width: 100%;
            }
            #submit_card {
                display: none;
                position: absolute;
                margin-right: 30px;
            }
            #submit_card_fixed {
                display:block;
            }
        }

        /* Large devices (laptops/desktops, 992px and up) */
        @media (min-width: 1100px) {
            .container {
                width: 600px;
            }
            .conversion-info {
                display:block;
            }
            #left {
                float: left;
                width: 50%;
            }
            #right {
                float: right;
                width: 35%;
            }
            #submit_card {
                display: block;
                position: absolute;
                margin-right: 30px;
            }
            #submit_card_fixed {
                display:none;
            }
        }
    </style>
    <script>
        let minTop;
        jQuery(document).ready(function() {
            minTop = jQuery('#submit_card').position().top;
            console.log( minTop );
            jQuery('.toggle-profile-link').on('click', function(e) {
                e.preventDefault();
                jQuery(this).parents('.template-card').find('.profiles').slideToggle();
            });
            jQuery('.toggle-product-link').on('click', function(e) {
                e.preventDefault();
                jQuery(this).parents('.template-card').find('.products').slideToggle();
            });

            jQuery('#toggle_include_all').on('click', function(e) {
                e.preventDefault();

                jQuery('.tpl-action').each( function() {
                    jQuery(this).val(1);
                });

                jQuery('.tpl-action').trigger('change');
            });
            jQuery('#toggle_skip_all').on('click', function(e) {
                e.preventDefault();

                jQuery('.tpl-action').each( function() {
                    jQuery(this).val(0);
                });
                jQuery('.tpl-action').trigger('change');
            });
            jQuery('.tpl-action').on('change', function() {
                let disabled = true;

                jQuery('.tpl-action').each(function() {
                    const card = jQuery(this).parents('.template-card');

                    jQuery(card).removeClass('active');
                    if ( jQuery(this).val() == 1 ) {
                        jQuery(card).addClass('active');
                        disabled = false;
                        //return false; // break
                    }
                });

                jQuery('.convert-button').prop('disabled', disabled);
            });
            jQuery('.no-replacements-tip').tipTip();
        });

        document.addEventListener("scroll", function () {
            let floatingDiv = document.getElementById("submit_card");
            let scrollY = window.scrollY; // Get the scroll position
            let newTop = Math.max(minTop+10, scrollY+100);
            floatingDiv.style.top = newTop + "px"; // Move smoothly
        });
    </script>



    <form action="admin.php?page=wpla-tools&tab=profile-converter" method="POST">
    <div id="left">
        <div class="conversion-info">
            <h3>Why We Need to Convert WP-Lister Profiles</h3>
            <p>Amazon is phasing out support for Flat File Feeds and replacing them with JSON Feeds, which utilize the Product Types API to manage product attributes. This transition requires significant changes in how product data is structured and submitted to Amazon.</p>

            <h3>What This Means for Users</h3>

            <ul>
                <li>Existing WP-Lister profiles that rely on Flat File Feeds must be converted to the new JSON-based format.</li>
                <li>Users may need to provide additional product attributes to comply with Amazon’s updated requirements.</li>
                <li>Some field mappings will be automatic, but manual adjustments may be necessary for certain attributes.</li>
            </ul>

            <p>The WP-Lister team is actively working on making the transition as smooth as possible.
                By converting WP-Lister profiles, we ensure that users can continue listing and managing their products on
                Amazon without interruption while also benefiting from a more modern and reliable feed system.
            </p>
        </div>

        <div class="align-right">
            <a href="#" id="toggle_include_all"><?php _e('Include All', 'wp-lister-for-amazon'); ?></a>
            /
            <a href="#" id="toggle_skip_all"><?php _e('Skip All', 'wp-lister-for-amazon.zip'); ?></a>
        </div>

        <div class="container">
		    <?php
		    foreach ( $wpl_templates as $tpl_id => $template ):
                if ( empty( $template->site_id ) ) {
                    continue;
                }

			    $count = count( $wpl_old_profiles[ $tpl_id ]?? [] );
			    $market = new WPLA_AmazonMarket( $template->site_id );

			    $installed_templates = $wpl_installed[ $market->marketplace_id ] ?? [];
			    ?>
                <div class="template-card">
                    <div class="template-action-box">
                        <select name="tpl_actions[<?php echo $tpl_id; ?>]" class="tpl-action">
                            <option value="0"><?php _e('Skip converting this template', 'wp-lister-for-amazon'); ?></option>
                            <?php if ( !empty( $wpl_replacements[$tpl_id] ) || !empty( $installed_templates ) ): ?>
                            <option value="1"><?php _e('Convert this template', 'wp-lister-for-amazon'); ?></option>
                            <?php endif; ?>
                        </select>
                        <br/>
                        <?php if ( empty( $wpl_replacements[ $tpl_id ] ) && empty( $installed_templates ) ): ?>
                        <em>
                            <?php _e('No recommended replacements found.', 'wp-lister-for-amazon' ); ?>
                            <br/><br/>
                            Go to the <a href="admin.php?page=wpla-settings&tab=product_types">Product Types page</a>, then search for and install the appropriate replacement for this feed template in the same marketplace.
                        </em>
                        <?php else: ?>
                        <label>New Product Type:</label>
                        <select name="tpl_replacements[<?php echo $tpl_id; ?>]" class="tpl-replacement">
                            <?php if ( !empty( $wpl_replacements[ $tpl_id ] ) ): ?>
                            <optgroup label="Recommended Product Types">
                                <?php
                                foreach ( $wpl_replacements[ $tpl_id ] as $product_type ):
                                ?>
                                    <option value="<?php esc_attr_e( $product_type->getProductType() ); ?>"><?php echo $product_type->getDisplayName(); ?></option>
                                <?php
                                endforeach;
                                ?>
                            </optgroup>
                            <?php
                            endif;
                            if ( !empty( $installed_templates ) ):
                            ?>
                            <optgroup label="Installed Product Types">
	                            <?php
	                            foreach ( $installed_templates as $product_type ):
	                            ?>
                                    <option value="<?php esc_attr_e( $product_type->getProductType() ); ?>"><?php echo $product_type->getDisplayName(); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php
                            endif;
                            ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <h2><?php echo $template->title; ?> - <?php echo $market->title; ?></h2>
                    <p>
                        Used by:
                        <?php if ( $count ): ?>
                        <br><a href="#" class="toggle-profile-link"><?php printf( _n( '%d profile', '%d profiles', $count, 'wp-lister-for-amazon' ), $count ); ?></a>
                        <?php endif; ?>
                        <?php
                        if ( isset( $wpl_all_products[ $tpl_id ] ) ) {
                            $count = count($wpl_all_products[ $tpl_id ]);
	                        printf( '<a href="#" class="toggle-product-link">'. _n('<br>%d product without a profile', '<br>%d products without a profile', $count, 'wp-lister-for-amazon' ) .'</a>', $count );
                        }
                        ?>
                    </p>

                    <div class="profiles">
					    <?php
                        if ( isset( $wpl_old_profiles[ $tpl_id ] ) ):
                            foreach( $wpl_old_profiles[ $tpl_id ] as $profile ):
                        ?>
                            <div class="profile">
                                <a href="admin.php?page=wpla-profiles&action=edit&profile=<?php echo $profile->profile_id; ?>" target="_blank"><?php echo $profile->profile_name; ?></a>
                            </div>
					    <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                    <div class="products">
		                <?php
                        $i = 0;
                        if ( isset( $wpl_all_products[ $tpl_id ] ) ) {
	                        foreach( $wpl_all_products[ $tpl_id ] as $product_id ):
		                        $i++;
		                        $wc_product = wc_get_product( $product_id );
                                if ( $wc_product ):
		                        ?>
                                <div class="product">
                                    <a href="post.php?post=<?php echo $wc_product->get_id(); ?>&action=edit" target="_blank"><?php echo $wc_product->get_title(); ?></a>
                                </div>
		                        <?php
                                endif;
		                        if ( $i >= 10 ) break;
	                        endforeach;
                        }
                        ?>
                    </div>
                    <div class="clear"></div>
                </div>
		    <?php
		    endforeach;
		    ?>
        </div>
    </div>
    <div id="right">


        <div class="template-card active submit-card" id="submit_card">
            <p>
                <b>Important:</b> Before converting your profiles, we recommend creating a snapshot of your store. This beta feature is still undergoing testing and improvements.
            </p>
            <p>
                To begin the conversion process, click the button below. WP-Lister will create duplicate profiles with a "(Converted)" suffix and work only on these duplicates. Once the conversion is complete, you will have the option to migrate your listings to the newly created profiles.
            </p>
            <p>
                <input type="hidden" name="action" value="wpla_convert_profiles" />
                <?php wp_nonce_field( 'wpla_convert_profiles' ); ?>
                <input type="submit" disabled class="button-primary convert-button" id="convert_button" value="<?php _e('Begin Conversion', 'wp-lister-for-amazon'); ?>" />
            </p>
        </div>
        <div class="template-card active submit-card" id="submit_card_fixed">
            <p>
                <b>Important:</b> Before converting your profiles, we recommend creating a snapshot of your store. This beta feature is still undergoing testing and improvements.
            </p>
            <p>
                To begin the conversion process, click the button below. WP-Lister will create duplicate profiles with a "(Converted)" suffix and work only on these duplicates. Once the conversion is complete, you will have the option to migrate your listings to the newly created profiles.
            </p>
            <p>
                <input type="hidden" name="action" value="wpla_convert_profiles" />
			    <?php wp_nonce_field( 'wpla_convert_profiles' ); ?>
                <input type="submit" disabled class="button-primary convert-button" id="convert_button2" value="<?php _e('Begin Conversion', 'wp-lister-for-amazon'); ?>" />
            </p>
        </div>
    </div>
    </form>

</div>