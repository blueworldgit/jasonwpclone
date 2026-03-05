<?php
// Use the original B2BKing header bar function
if (class_exists('B2bking_Admin')) {
    echo B2bking_Admin::get_header_bar();
}

// Determine taxonomy context (Tags vs Brands)
$b2bking_use_brands = intval(get_option('b2bking_use_brands_taxonomy_setting', 0)) === 1;
$b2bking_tag_taxonomy = apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag');
$b2bking_tag_label_text = $b2bking_use_brands ? esc_html__('Brand', 'b2bking') : esc_html__('Tag', 'b2bking');

// Get customer groups for the target group dropdown
		$customer_groups = get_posts([
			'post_type' => 'b2bking_group',
			'post_status' => 'publish',
			'numberposts' => -1,
		]);
		
		// Get rule ID from URL if editing existing rule
		$rule_id = isset($_GET['rule_id']) ? intval($_GET['rule_id']) : 0;
		$is_editing = $rule_id > 0;

		// Get rule data if editing
		$rule_data = array();
		if ($is_editing) {
			// Check if rule_per_product is enabled - if so, use original values for display
			$rule_per_product = get_post_meta($rule_id, 'b2bking_rule_per_product', true);
			$applies_to = get_post_meta($rule_id, 'b2bking_rule_applies', true);
			$applies_to_multiple_options = get_post_meta($rule_id, 'b2bking_rule_applies_multiple_options', true);
			
			if (intval($rule_per_product) === 1) {
				// Use original values for display
				$original_applies = get_post_meta($rule_id, 'b2bking_rule_applies_original', true);
				$original_multiple = get_post_meta($rule_id, 'b2bking_rule_applies_multiple_options_original', true);
				
				if (!empty($original_applies)) {
					$applies_to = $original_applies;
				}
				if ($original_multiple !== false && $original_multiple !== '') {
					$applies_to_multiple_options = $original_multiple;
				}
			}
			
			$rule_data = array(
				'name' => get_post($rule_id)->post_title,
				'rule_type' => get_post_meta($rule_id, 'b2bking_rule_what', true),
				'applies_to' => $applies_to,
				'applies_to_multiple_options' => $applies_to_multiple_options,
				'rule_who' => get_post_meta($rule_id, 'b2bking_rule_who', true),
				'who_multiple_options' => get_post_meta($rule_id, 'b2bking_rule_who_multiple_options', true),
				'how_much' => get_post_meta($rule_id, 'b2bking_rule_howmuch', true),
				'quantity_value' => get_post_meta($rule_id, 'b2bking_rule_quantity_value', true),
				'currency' => get_post_meta($rule_id, 'b2bking_rule_currency', true),
				'paymentmethod' => get_post_meta($rule_id, 'b2bking_rule_paymentmethod', true),
				'paymentmethod_minmax' => get_post_meta($rule_id, 'b2bking_rule_paymentmethod_minmax', true),
				'paymentmethod_percentamount' => get_post_meta($rule_id, 'b2bking_rule_paymentmethod_percentamount', true),
				'paymentmethod_discountsurcharge' => get_post_meta($rule_id, 'b2bking_rule_paymentmethod_discountsurcharge', true),
				'paymentmethod_name' => get_post_meta($rule_id, 'b2bking_rule_taxname', true), // taxname is used for renamed payment method
				'shippingmethod' => get_post_meta($rule_id, 'b2bking_rule_shippingmethod', true),
				'countries' => get_post_meta($rule_id, 'b2bking_rule_countries', true),
				'requires' => get_post_meta($rule_id, 'b2bking_rule_requires', true),
				'showtax' => get_post_meta($rule_id, 'b2bking_rule_showtax', true),
				'tax_shipping' => get_post_meta($rule_id, 'b2bking_rule_tax_shipping', true),
				'tax_shipping_rate' => get_post_meta($rule_id, 'b2bking_rule_tax_shipping_rate', true),
				'taxname' => get_post_meta($rule_id, 'b2bking_rule_taxname', true),
				'tax_taxable' => get_post_meta($rule_id, 'b2bking_rule_tax_taxable', true),
				'discount_name' => get_post_meta($rule_id, 'b2bking_rule_discountname', true),
				'show_everywhere' => get_post_meta($rule_id, 'b2bking_rule_discount_show_everywhere', true),
				'per_product' => get_post_meta($rule_id, 'b2bking_rule_per_product', true),
				'conditions' => get_post_meta($rule_id, 'b2bking_rule_conditions', true),
				'priority' => get_post_meta($rule_id, 'b2bking_standard_rule_priority', true),
				'price_tiers' => get_post_meta($rule_id, 'b2bking_product_pricetiers_group_b2c', true),
				'info_table_rows' => get_post_meta($rule_id, 'b2bking_product_customrows_group_b2c', true),
			);
			$raise_price_meta = get_post_meta($rule_id, 'b2bking_rule_raise_price', true);
			if ($raise_price_meta === 'yes' && ($rule_data['rule_type'] ?? '') === 'discount_percentage') {
				$rule_data['rule_type'] = 'raise_price';
			}

			if ($rule_data['rule_who'] === 'multiple_options'){
			    $rule_who_original = get_post_meta($rule_id, 'b2bking_rule_who_original', true);
			    if ($rule_who_original === 'specific_users'){
			        $rule_data['rule_who'] = 'specific_users';
			    }
			}
		}
		?>
		<div id="b2bking_dynamic_rule_pro_editor_main_container">
			<!-- Main Content -->
			<div class="b2bking-rule-builder">
				<!-- Header Section -->
				<div class="b2bking_dynamic_rule_pro_editor_header">
					<div class="b2bking_dynamic_rule_pro_editor_header_title">
						<div class="b2bking_dynamic_rule_pro_editor_header_text">
							<h1><?php echo $is_editing ? esc_html__('Edit Dynamic Rule', 'b2bking') : esc_html__('Create New Dynamic Rule', 'b2bking'); ?></h1>
						</div>
					</div>
					<div class="b2bking_dynamic_rule_pro_editor_header_actions">
						<a href="<?php echo admin_url('admin.php?page=b2bking_dynamic_rules_pro'); ?>" class="b2bking_dynamic_rule_pro_editor_back_btn">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
								<path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<?php esc_html_e('Back to Rules', 'b2bking'); ?>
						</a>
						<button type="submit" id="b2bking_dynamic_rule_pro_editor_save_top" class="btn-primary">
							<span class="btn-icon-container">
								<svg class="btn-checkmark" width="16" height="16" viewBox="0 0 16 16" fill="none">
									<path d="M13.3333 4L6 11.3333L2.66667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
								<img class="btn-loader" src="<?php echo esc_url(plugins_url('includes/assets/images/loadertransparent.svg', dirname(dirname(dirname(dirname(__FILE__)))))); ?>" style="display: none; width: 32px; height: 32px;" alt="">
							</span>
							<span class="btn-text"><?php echo $is_editing ? esc_html__('Update Rule', 'b2bking') : esc_html__('Create & Activate Rule', 'b2bking'); ?></span>
							<span class="btn-loading" style="display: none;">
								<svg class="spinner" width="16" height="16" viewBox="0 0 16 16" fill="none">
									<circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
										<animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
										<animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
									</circle>
								</svg>
								<span class="btn-loading-text"><?php echo $is_editing ? esc_html__('Updating...', 'b2bking') : esc_html__('Creating...', 'b2bking'); ?></span>
							</span>
						</button>
					</div>
				</div>
				
				<!-- Main Rule Builder -->
			<div class="b2bking-cards-container">
				<form id="b2bking_dynamic_rule_pro_editor_form" novalidate>
					<!-- Card 1: Rule Identity -->
					<div class="b2bking-card">
						<div class="card-header">
							<span class="card-number">1</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Name Your Rule', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Give your rule a memorable name for easy identification', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<input type="text" 
								   id="b2bking_rule_name_pro" 
								   name="b2bking_rule_name_pro"
								   class="b2bking-pro-rule-name-input" 
								   placeholder="<?php esc_attr_e('e.g., VIP Customer Discount', 'b2bking'); ?>"
								   value="<?php echo esc_attr($rule_data['name'] ?? ''); ?>" 
								   required>
						</div>
					</div>

					<!-- Card 2: Rule Type -->
					<div class="b2bking-card">
						<div class="card-header">
							<span class="card-number">2</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Rule Type', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Choose what type of rule you want to create', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_select_what_pro" name="b2bking_rule_what_pro" class="b2bking-pro-select" required>
								<option value=""><?php esc_html_e('— Select Rule Type —', 'b2bking'); ?></option>
								<optgroup label="<?php esc_attr_e('Discounts & Pricing', 'b2bking'); ?>">
									<option value="discount_amount" <?php selected($rule_data['rule_type'] ?? '', 'discount_amount'); ?>><?php esc_html_e('Discount (Amount)', 'b2bking'); ?></option>
									<option value="discount_percentage" <?php selected($rule_data['rule_type'] ?? '', 'discount_percentage'); ?>><?php esc_html_e('Discount (Percentage)', 'b2bking'); ?></option>
									<option value="raise_price" <?php selected($rule_data['rule_type'] ?? '', 'raise_price'); ?>><?php esc_html_e('Raise Price (Percentage)', 'b2bking'); ?></option>
									<option value="bogo_discount" <?php selected($rule_data['rule_type'] ?? '', 'bogo_discount'); ?>><?php esc_html_e('Buy X Get 1 Free', 'b2bking'); ?></option>
									<option value="tiered_price" <?php selected($rule_data['rule_type'] ?? '', 'tiered_price'); ?>><?php esc_html_e('Tiered Price', 'b2bking'); ?></option>
									<option value="fixed_price" <?php selected($rule_data['rule_type'] ?? '', 'fixed_price'); ?>><?php esc_html_e('Fixed Price', 'b2bking'); ?></option>
									<option value="hidden_price" <?php selected($rule_data['rule_type'] ?? '', 'hidden_price'); ?>><?php esc_html_e('Hidden Price', 'b2bking'); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e('Order Rules', 'b2bking'); ?>">
									<option value="free_shipping" <?php selected($rule_data['rule_type'] ?? '', 'free_shipping'); ?>><?php esc_html_e('Free Shipping', 'b2bking'); ?></option>
									<option value="unpurchasable" <?php selected($rule_data['rule_type'] ?? '', 'unpurchasable'); ?>><?php esc_html_e('Non-Purchasable', 'b2bking'); ?></option>
									<option value="minimum_order" <?php selected($rule_data['rule_type'] ?? '', 'minimum_order'); ?>><?php esc_html_e('Minimum Order', 'b2bking'); ?></option>
									<option value="maximum_order" <?php selected($rule_data['rule_type'] ?? '', 'maximum_order'); ?>><?php esc_html_e('Maximum Order', 'b2bking'); ?></option>
									<option value="required_multiple" <?php selected($rule_data['rule_type'] ?? '', 'required_multiple'); ?>><?php esc_html_e('Required Multiple (Quantity Step)', 'b2bking'); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e('Taxes', 'b2bking'); ?>">
									<option value="tax_exemption_user" <?php selected($rule_data['rule_type'] ?? '', 'tax_exemption_user'); ?>><?php esc_html_e('Tax Exemption', 'b2bking'); ?></option>
									<option value="tax_exemption" <?php selected($rule_data['rule_type'] ?? '', 'tax_exemption'); ?>><?php esc_html_e('Zero Tax Product', 'b2bking'); ?></option>
									<option value="add_tax_percentage" <?php selected($rule_data['rule_type'] ?? '', 'add_tax_percentage'); ?>><?php esc_html_e('Add Tax / Fee (Percentage)', 'b2bking'); ?></option>
									<option value="add_tax_amount" <?php selected($rule_data['rule_type'] ?? '', 'add_tax_amount'); ?>><?php esc_html_e('Add Tax / Fee (Amount)', 'b2bking'); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e('Advanced Rules', 'b2bking'); ?>">
									<option value="replace_prices_quote" <?php selected($rule_data['rule_type'] ?? '', 'replace_prices_quote'); ?>><?php esc_html_e('Replace Cart with Quote System', 'b2bking'); ?></option>
									<option value="quotes_products" <?php selected($rule_data['rule_type'] ?? '', 'quotes_products'); ?>><?php esc_html_e('Quotes on Specific Products', 'b2bking'); ?></option>
									<option value="set_currency_symbol" <?php selected($rule_data['rule_type'] ?? '', 'set_currency_symbol'); ?>><?php esc_html_e('Set Currency', 'b2bking'); ?></option>
									<option value="info_table" <?php selected($rule_data['rule_type'] ?? '', 'info_table'); ?>><?php esc_html_e('Add to Information Table', 'b2bking'); ?></option>
									<option value="rename_purchase_order" <?php selected($rule_data['rule_type'] ?? '', 'rename_purchase_order'); ?>><?php esc_html_e('Rename Payment Method', 'b2bking'); ?></option>
									<option value="payment_method_minmax_order" <?php selected($rule_data['rule_type'] ?? '', 'payment_method_minmax_order'); ?>><?php esc_html_e('Payment Method Min / Max Order', 'b2bking'); ?></option>
									<option value="payment_method_discount" <?php selected($rule_data['rule_type'] ?? '', 'payment_method_discount'); ?>><?php esc_html_e('Payment Method Discount / Surcharge', 'b2bking'); ?></option>
									<option value="payment_method_restriction" <?php selected($rule_data['rule_type'] ?? '', 'payment_method_restriction'); ?>><?php esc_html_e('Payment Method Product Restriction', 'b2bking'); ?></option>
									<option value="shipping_method_restriction" <?php selected($rule_data['rule_type'] ?? '', 'shipping_method_restriction'); ?>><?php esc_html_e('Shipping Method Product Restriction', 'b2bking'); ?></option>
								</optgroup>
							</select>
									</div>
					</div>

					<!-- Card 3: Applies To -->
					<div class="b2bking-card" id="applies-to-card">
						<div class="card-header">
							<span class="card-number">3</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Applies To', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Choose which products or categories this rule applies to', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<!-- Hidden dropdown for form submission -->
							<select id="b2bking_rule_select_applies_pro" name="b2bking_rule_applies_pro" class="b2bking-pro-select" required style="display: none;">
								<option value=""><?php esc_html_e('— Select Application —', 'b2bking'); ?></option>
								<optgroup label="<?php esc_attr_e('Cart', 'b2bking'); ?>">
									<option value="cart_total" <?php selected($rule_data['applies_to'] ?? '', 'cart_total'); ?>><?php esc_html_e('All Products / Cart Total', 'b2bking'); ?></option>
									<option value="multiple_options" <?php selected($rule_data['applies_to'] ?? '', 'multiple_options'); ?>><?php esc_html_e('Specific products, categories, or tags...', 'b2bking'); ?></option>
									<?php
									// Hide "excluding_multiple_options" option for minimum_order, maximum_order, and required_multiple rule types
									$rule_type = $rule_data['rule_type'] ?? '';
									$hide_excluding = in_array($rule_type, ['minimum_order', 'maximum_order', 'required_multiple']);
									if (!$hide_excluding) {
									?>
									<option value="excluding_multiple_options" <?php selected($rule_data['applies_to'] ?? '', 'excluding_multiple_options'); ?>><?php esc_html_e('All products except specific items...', 'b2bking'); ?></option>
									<?php } ?>
								</optgroup>
							</select>
							
							<!-- Action Grid -->
							<div class="action-grid">
								<div class="applies-option <?php echo ($rule_data['applies_to'] ?? '') === 'cart_total' ? 'selected' : ''; ?>" 
									 data-applies="cart_total">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
											<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
											<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
											<g id="SVGRepo_iconCarrier">
												<defs id="defs2"></defs>
												<g id="layer1" transform="translate(36,-292)">
													<path d="m -27,316.00586 c -1.645008,0 -3,1.35499 -3,3 0,1.64501 1.354992,3 3,3 1.645008,0 3,-1.35499 3,-3 0,-1.64501 -1.354992,-3 -3,-3 z m 0,2 c 0.564129,0 1,0.43587 1,1 0,0.56413 -0.435871,1 -1,1 -0.564129,0 -1,-0.43587 -1,-1 0,-0.56413 0.435871,-1 1,-1 z" id="circle5359" style="color:currentColor;fill:currentColor;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path>
													<path d="m -13,316.00586 c -1.645008,0 -3,1.35499 -3,3 0,1.64501 1.354992,3 3,3 1.645008,0 3,-1.35499 3,-3 0,-1.64501 -1.354992,-3 -3,-3 z m 0,2 c 0.564129,0 1,0.43587 1,1 0,0.56413 -0.435871,1 -1,1 -0.564129,0 -1,-0.43587 -1,-1 0,-0.56413 0.435871,-1 1,-1 z" id="circle5361" style="color:currentColor;fill:currentColor;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path>
													<path d="m -12,294.00586 c -3.30186,0 -6,2.69814 -6,6 0,3.30186 2.69814,6 6,6 3.30186,0 6,-2.69814 6,-6 0,-3.30186 -2.69814,-6 -6,-6 z m 0,2 c 2.2209809,0 4,1.77902 4,4 0,2.22098 -1.7790191,4 -4,4 -2.220981,0 -4,-1.77902 -4,-4 0,-2.22098 1.779019,-4 4,-4 z" id="circle43373-3" style="color:currentColor;fill:currentColor;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path>
													<path d="m -9.8984375,298.01172 a 1,1 0 0 0 -0.7343755,0.21875 l -2.029296,1.65625 -0.65625,-0.61133 a 1,1 0 0 0 -1.41211,0.0488 1,1 0 0 0 0.04883,1.41211 l 1.292969,1.20898 a 1.0001,1.0001 0 0 0 1.3125,0.043 l 2.7089845,-2.20703 a 1,1 0 0 0 0.1425781,-1.40625 1,1 0 0 0 -0.6738281,-0.36328 z" id="path43375-6" style="color:currentColor;fill:currentColor;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path>
													<path d="m -32.95117,294 a 1,1 0 0 0 -1,1 1,1 0 0 0 1,1 h 1.17969 l 2.65039,13.24219 c -1.07078,0.46018 -1.83008,1.52737 -1.83008,2.75781 0,1.6447 1.3553,3 3,3 h 17 a 1,1 0 0 0 1,-1 1,1 0 0 0 -1,-1 h -17 c -0.5713,0 -1,-0.4287 -1,-1 0,-0.5713 0.4287,-1 1,-1 h 15 a 1,1 0 0 0 0.0293,-0.006 h 2.9707 a 1.0001,1.0001 0 0 0 0.98828,-0.84375 l 0.93945,-5.96875 A 1,1 0 0 0 -8.85547,303.03697 1,1 0 0 0 -10,303.87095 l -0.80664,5.12319 H -27.13086 L -28.93164,300 h 12.08399 a 1,1 0 0 0 1,-1 1,1 0 0 0 -1,-1 h -12.48438 l -0.63867,-3.19531 A 1.0001,1.0001 0 0 0 -30.95117,294 Z" id="path21288" style="color:currentColor;fill:currentColor;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path>
												</g>
											</g>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('All Products', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Apply to all products in cart', 'b2bking'); ?></div>
								</div>
								
								<div class="applies-option <?php echo ($rule_data['applies_to'] ?? '') === 'multiple_options' ? 'selected' : ''; ?>" 
									 data-applies="multiple_options">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Select Products', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Choose specific products, categories, or tags', 'b2bking'); ?></div>
								</div>
								
								<?php
								// Hide "excluding_multiple_options" for minimum_order, maximum_order, and required_multiple rule types
								$rule_type = $rule_data['rule_type'] ?? '';
								$hide_excluding = in_array($rule_type, ['minimum_order', 'maximum_order', 'required_multiple']);
								?>
								<div class="applies-option <?php echo ($rule_data['applies_to'] ?? '') === 'excluding_multiple_options' ? 'selected' : ''; ?> <?php echo $hide_excluding ? 'b2bking-hide-excluding-option' : ''; ?>" 
									 data-applies="excluding_multiple_options"
									 style="<?php echo $hide_excluding ? 'display: none;' : ''; ?>">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
											<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
											<g id="SVGRepo_iconCarrier">
												<path d="M9 9L15 15M15 9L9 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											</g>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Exclude Products', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('All products except specific items', 'b2bking'); ?></div>
								</div>
							</div>
							
							<!-- Search Container (hidden by default) -->
							<div id="b2bking_rule_select_applies_multiple_options_container_pro" style="display: none; margin-top: 30px;">
								<div class="b2bking_rule_label"><?php esc_html_e('Search for items:', 'b2bking'); ?></div>
								
								<!-- Search Type Checkboxes -->
								<div class="b2bking_content_search_types" style="margin-bottom: 10px;">
									<label style="margin-right: 15px;">
										<input type="checkbox" id="search_products_pro" checked> <?php esc_html_e('Products', 'b2bking'); ?>
									</label>
									<label style="margin-right: 15px;">
										<input type="checkbox" id="search_categories_pro" checked> <?php esc_html_e('Categories', 'b2bking'); ?>
								</label>
									<label style="margin-right: 15px;">
										<input type="checkbox" id="search_tags_pro" <?php echo $b2bking_use_brands ? '' : 'checked'; ?>> <?php esc_html_e('Tags', 'b2bking'); ?>
									</label>
									<label>
										<input type="checkbox" id="search_brands_pro" <?php echo $b2bking_use_brands ? 'checked' : ''; ?>> <?php esc_html_e('Brands', 'b2bking'); ?>
									</label>
								</div>
								
								<!-- Select2 Search Input -->
								<select id="b2bking_content_selector_pro" class="b2bking-pro-content-selector" multiple style="width: 100%; display: flex; align-items: center;">
									<?php
									// Pre-populate with existing selections if editing
									if ($is_editing && !empty($rule_data['applies_to_multiple_options'] ?? '')) {
										$selected_items = explode(',', $rule_data['applies_to_multiple_options']);
										foreach ($selected_items as $item) {
											$item = trim($item);
											if (strpos($item, 'product_') === 0) {
												$product_id = str_replace('product_', '', $item);
												$product = wc_get_product($product_id);
												if ($product) {
													$product_name = strip_tags($product->get_name());
													$sku = $product->get_sku();
													
													// Build display text matching search format
													$display_text = $product_name;
													if (!empty($sku)) {
														$display_text .= ' (' . $sku . ')';
													}
													// Add type label (since multiple types are typically searched)
													$display_text .= ' (' . esc_html__('Product', 'b2bking') . ')';
													
													echo '<option value="' . esc_attr($item) . '" selected>' . esc_html($display_text) . '</option>';
												}
											} elseif (strpos($item, 'category_') === 0) {
												$cat_id = str_replace('category_', '', $item);
												$category = get_term($cat_id);
												if ($category) {
													echo '<option value="' . esc_attr($item) . '" selected>' . esc_html($category->name) . ' (' . esc_html__('Category', 'b2bking') . ')</option>';
												}
											} elseif (strpos($item, 'tag_') === 0) {
												$tag_id = str_replace('tag_', '', $item);
												$tag = get_term($tag_id, $b2bking_tag_taxonomy);
												if ($tag && !is_wp_error($tag)) {
													echo '<option value="' . esc_attr($item) . '" selected>' . esc_html($tag->name) . ' (' . esc_html($b2bking_tag_label_text) . ')</option>';
												} else {
													echo '<option value="' . esc_attr($item) . '" selected>' . esc_html($b2bking_tag_label_text . ' #' . $tag_id) . '</option>';
												}
											}
										}
									}
									?>
								</select>
								
								<!-- Hidden input to store the selected values -->
								<input type="hidden" id="b2bking_rule_select_applies_multiple_options_pro" name="b2bking_rule_applies_multiple_options_pro" value="<?php echo esc_attr($rule_data['applies_to_multiple_options'] ?? ''); ?>">
							</div>
						</div>
					</div>

					<!-- Card 4: For Who -->
					<div class="b2bking-card" id="for-who-card">
						<div class="card-header">
							<span class="card-number">4</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('For Who', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Choose which customers this rule applies to', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_select_who_pro" name="b2bking_rule_who_pro" class="b2bking-pro-select" required>
								<option value=""><?php esc_html_e('— Select Customer Group —', 'b2bking'); ?></option>
								<optgroup label="<?php esc_attr_e('User Types', 'b2bking'); ?>">
									<option value="all_registered" <?php selected($rule_data['rule_who'] ?? '', 'all_registered'); ?>><?php esc_html_e('All logged-in users', 'b2bking'); ?></option>
									<option value="everyone_registered_b2b" <?php selected($rule_data['rule_who'] ?? '', 'everyone_registered_b2b'); ?>><?php esc_html_e('B2B customers (logged-in)', 'b2bking'); ?></option>
									<option value="everyone_registered_b2c" <?php selected($rule_data['rule_who'] ?? '', 'everyone_registered_b2c'); ?>><?php esc_html_e('B2C customers (logged-in)', 'b2bking'); ?></option>
									<option value="user_0" <?php selected($rule_data['rule_who'] ?? '', 'user_0'); ?>><?php esc_html_e('Guest visitors (not logged-in)', 'b2bking'); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e('Custom Selection', 'b2bking'); ?>">
									<option value="multiple_options" <?php selected($rule_data['rule_who'] ?? '', 'multiple_options'); ?>><?php esc_html_e('Combine multiple audiences...', 'b2bking'); ?></option>
									<option value="specific_users" <?php selected($rule_data['rule_who'] ?? '', 'specific_users'); ?>><?php esc_html_e('Choose specific users...', 'b2bking'); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e('B2B Groups', 'b2bking'); ?>">
									<?php foreach ($customer_groups as $group): ?>
										<option value="group_<?php echo esc_attr($group->ID); ?>" <?php selected($rule_data['rule_who'] ?? '', 'group_' . $group->ID); ?>><?php echo esc_html($group->post_title); ?></option>
									<?php endforeach; ?>
								</optgroup>
							</select>
							
							<!-- Multiple Options Selector (hidden by default) -->
							<div id="b2bking_select_multiple_options_selector_pro" style="display: none; margin-top: 15px;">
								<div class="b2bking_rule_label"><?php esc_html_e('Combine user types & groups:', 'b2bking'); ?></div>
								<select class="b2bking-pro-select-multiple-options" name="b2bking_select_multiple_options_selector_select_pro[]" multiple style="width: 100%;">
									<?php
									// Get selected options if editing
									$selected_options = array();
									if ($is_editing && !empty($rule_data['who_multiple_options'] ?? '')) {
										$selected_options = explode(',', $rule_data['who_multiple_options']);
									}
									?>
									<optgroup label="<?php esc_attr_e('User Types', 'b2bking'); ?>">
										<option value="all_registered" <?php selected(in_array('all_registered', $selected_options), true); ?>><?php esc_html_e('All logged-in users', 'b2bking'); ?></option>
										<option value="everyone_registered_b2b" <?php selected(in_array('everyone_registered_b2b', $selected_options), true); ?>><?php esc_html_e('B2B customers (logged-in)', 'b2bking'); ?></option>
										<option value="everyone_registered_b2c" <?php selected(in_array('everyone_registered_b2c', $selected_options), true); ?>><?php esc_html_e('B2C customers (logged-in)', 'b2bking'); ?></option>
										<option value="user_0" <?php selected(in_array('user_0', $selected_options), true); ?>><?php esc_html_e('Guest visitors (not logged-in)', 'b2bking'); ?></option>
									</optgroup>
									<optgroup label="<?php esc_attr_e('B2B Groups', 'b2bking'); ?>">
										<?php foreach ($customer_groups as $group): ?>
											<option value="group_<?php echo esc_attr($group->ID); ?>" <?php selected(in_array('group_' . $group->ID, $selected_options), true); ?>><?php echo esc_html($group->post_title); ?></option>
										<?php endforeach; ?>
									</optgroup>
								</select>
								<input type="hidden" id="b2bking_rule_who_multiple_options_pro" name="b2bking_rule_who_multiple_options_pro" value="<?php echo esc_attr($rule_data['who_multiple_options'] ?? ''); ?>">
							</div>
									
							<!-- Specific Users Selector (hidden by default) -->
							<div id="b2bking_specific_users_selector_container_pro" style="display: none; margin-top: 15px;">
								<div class="b2bking_rule_label"><?php esc_html_e('Choose specific users:', 'b2bking'); ?></div>
								<select id="b2bking_specific_users_selector_pro" class="b2bking-pro-specific-users-selector" multiple style="width: 100%;">
									<?php
									// Pre-populate with existing users if editing and rule_who is specific_users
									if ($is_editing && ($rule_data['rule_who'] ?? '') === 'specific_users' && !empty($rule_data['who_multiple_options'] ?? '')) {
										$user_ids = explode(',', $rule_data['who_multiple_options']);
										foreach ($user_ids as $user_id) {
											$user_id = trim($user_id);
											// Handle user_ prefix format
											if (strpos($user_id, 'user_') === 0) {
												$user_id = str_replace('user_', '', $user_id);
											}
											if (!empty($user_id) && is_numeric($user_id)) {
												$user = get_user_by('ID', $user_id);
												if ($user) {
													$company = get_user_meta($user_id, 'billing_company', true);
													$display_text = $user->display_name . ' (' . $user->user_email . ')';
													if (!empty($company)) {
														$display_text .= ' - ' . $company;
													}
													echo '<option value="' . esc_attr($user_id) . '" selected>' . esc_html($display_text) . '</option>';
												}
											}
										}
									}
									?>
										</select>
								<?php
								// The hidden input is shared with multiple_options selector (b2bking_rule_who_multiple_options_pro)
								// For specific_users, the value will have user_ prefix format (e.g., user_123,user_456)
								?>
							</div>
										</div>
									</div>
									
					<!-- Card 5: Quantity or Value (for min/max order rules) -->
					<div class="b2bking-card" id="quantity-value-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">5</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Quantity or Value', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Choose whether to apply the rule based on quantity or monetary value', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<!-- Hidden dropdown for form submission -->
							<select id="b2bking_rule_quantity_value_pro" name="b2bking_rule_quantity_value_pro" class="b2bking-pro-select" style="display: none;">
								<option value=""><?php esc_html_e('— Select Type —', 'b2bking'); ?></option>
								<option value="quantity" <?php selected($rule_data['quantity_value'] ?? '', 'quantity'); ?>><?php esc_html_e('Quantity', 'b2bking'); ?></option>
								<option value="value" <?php selected($rule_data['quantity_value'] ?? '', 'value'); ?>><?php esc_html_e('Value', 'b2bking'); ?></option>
							</select>
							
							<!-- Action Grid -->
							<div class="action-grid">
								<div class="applies-option <?php echo ($rule_data['quantity_value'] ?? '') === 'quantity' ? 'selected' : ''; ?>" 
									 data-quantity-value="quantity">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M3 7V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V7M3 7L5 19H19L21 7M3 7H21M8 11V15M12 11V15M16 11V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Quantity', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Apply rule based on item quantity', 'b2bking'); ?></div>
								</div>
								
								<div class="applies-option <?php echo ($rule_data['quantity_value'] ?? '') === 'value' ? 'selected' : ''; ?>" 
									 data-quantity-value="value">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 2V22M17 5H9.5C8.57174 5 7.6815 5.36875 7.02513 6.02513C6.36875 6.6815 6 7.57174 6 8.5C6 9.42826 6.36875 10.3185 7.02513 10.9749C7.6815 11.6313 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 12.3687 16.9749 13.0251C17.6313 13.6815 18 14.5717 18 15.5C18 16.4283 17.6313 17.3185 16.9749 17.9749C16.3185 18.6313 15.4283 19 14.5 19H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Value', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Apply rule based on monetary value', 'b2bking'); ?></div>
								</div>
							</div>
						</div>
					</div>
									
					<!-- Card 6: How Much -->
					<div class="b2bking-card" id="how-much-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">6</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('How Much', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Set the amount, percentage, or value for this rule', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
											<input type="number" 
								   id="b2bking_rule_select_howmuch_pro" 
								   name="b2bking_rule_howmuch_pro"
								   value="<?php echo esc_attr($rule_data['how_much'] ?? ''); ?>" 
												   class="b2bking-pro-input-medium" 
												   step="0.01" 
												   placeholder="<?php esc_attr_e('Enter amount or percentage', 'b2bking'); ?>">
										</div>
									</div>
									
					<!-- Card 7: Price Tiers (only for tiered price rules) -->
					<div class="b2bking-card" id="price-tiers-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">7</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Price Tiers', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Configure tiered pricing levels for this rule', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<div id="b2bking_price_tiers_container_pro">
								<?php
								$decimals_number = apply_filters('b2bking_rounding_precision', get_option('woocommerce_price_num_decimals', 2));
								$decimal_separator = wc_get_price_decimal_separator();
								$price_tiers = $rule_data['price_tiers'] ?? '';
								$price_tiers_array = !empty($price_tiers) ? explode(';', $price_tiers) : array();
								$tiers_displayed = 0;
								
								// Display existing price tiers
								foreach ($price_tiers_array as $tier) {
									if (!empty($tier)) {
										$tier_values = explode(':', $tier);
										if (count($tier_values) >= 2) {
											$tiers_displayed++;
											?>
											<div class="b2bking-price-tier-row">
												<input name="b2bking_price_tiers_quantity_pro[]" 
												       placeholder="<?php esc_attr_e('Min. Quantity', 'b2bking'); ?>" 
												       class="b2bking-price-tier-quantity" 
												       type="number" 
												       min="1" 
												       step="1" 
												       value="<?php echo esc_attr(floatval($tier_values[0])); ?>" />
												<input name="b2bking_price_tiers_price_pro[]" 
												       placeholder="<?php echo apply_filters('b2bking_final_price_text', esc_attr__('Final Price', 'b2bking')); ?>" 
												       class="b2bking-price-tier-price" 
												       type="text" 
												       value="<?php echo esc_attr(number_format(b2bking()->tofloat($tier_values[1]), $decimals_number, $decimal_separator, '')); ?>" />
												<button type="button" class="b2bking-remove-tier-btn" aria-label="<?php esc_attr_e('Remove tier', 'b2bking'); ?>">
													<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
														<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
													</svg>
												</button>
											</div>
											<?php
										}
									}
								}
								
								// If no tiers exist, show one empty row by default
								if ($tiers_displayed === 0) {
									?>
									<div class="b2bking-price-tier-row">
										<input name="b2bking_price_tiers_quantity_pro[]" 
										       placeholder="<?php esc_attr_e('Min. Quantity', 'b2bking'); ?>" 
										       class="b2bking-price-tier-quantity" 
										       type="number" 
										       min="1" 
										       step="1" />
										<input name="b2bking_price_tiers_price_pro[]" 
										       placeholder="<?php echo apply_filters('b2bking_final_price_text', esc_attr__('Final Price', 'b2bking')); ?>" 
										       class="b2bking-price-tier-price" 
										       type="text" />
										<button type="button" class="b2bking-remove-tier-btn" aria-label="<?php esc_attr_e('Remove tier', 'b2bking'); ?>">
											<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
												<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
											</svg>
										</button>
									</div>
									<?php
								}
								?>
							</div>
							<div class="b2bking-price-tiers-actions">
								<button type="button" class="b2bking-add-tier-btn" id="b2bking_add_tier_pro">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
										<path d="M8 3V13M3 8H13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
									<?php esc_html_e('Add Tier', 'b2bking'); ?>
								</button>
							</div>
						</div>
					</div>
					
					<!-- Card 7a: Currency (only for set currency rules) -->
					<div class="b2bking-card" id="currency-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">7</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Currency', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Select the currency to apply', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_currency_pro" name="b2bking_rule_currency_pro" class="b2bking-pro-select">
								<option value=""><?php esc_html_e('— Select Currency —', 'b2bking'); ?></option>
								<?php
								if (function_exists('get_woocommerce_currency_symbols')){
									$symbols = get_woocommerce_currency_symbols();
									$selected_symbol = $rule_data['currency'] ?? '';
									foreach ($symbols as $symbolletters => $symbol){
										echo '<option value="' . esc_attr($symbolletters) . '" ' . selected($symbolletters, $selected_symbol, false) . '>' . esc_html($symbolletters) . ' → ' . esc_html($symbol) . '</option>';
									}
								}
								?>
							</select>
						</div>
					</div>
					
					<!-- Card 7b: Payment Method (for payment method rules) -->
					<div class="b2bking-card" id="payment-method-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">7</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Payment Method', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Select the payment method this rule applies to', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_paymentmethod_pro" name="b2bking_rule_paymentmethod_pro" class="b2bking-pro-select">
								<option value=""><?php esc_html_e('— Select Payment Method —', 'b2bking'); ?></option>
								<?php
								$selected_method = $rule_data['paymentmethod'] ?? '';
								// List all payment methods
								if (class_exists('WC_Payment_Gateways')) {
									$payment_methods = WC()->payment_gateways->payment_gateways();
									foreach ($payment_methods as $payment_method){
										if (isset($payment_method->title) && !empty($payment_method->title)){
											$method_title = esc_html($payment_method->title); 
										} else {
											$method_title = esc_html($payment_method->get_title());
										}
										if (!empty($method_title)){
											echo '<option value="' . esc_attr($payment_method->id) . '" ' . selected($payment_method->id, $selected_method, false) . '>' . esc_html($method_title) . '</option>';
										}
									}
								}
								?>
							</select>
						</div>
					</div>
					
					<!-- Card 7c: Payment Method Min/Max (for payment method min/max rules) -->
					<div class="b2bking-card" id="payment-method-minmax-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">8</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Minimum or Maximum', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Choose whether to set a minimum or maximum requirement', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<!-- Hidden dropdown for form submission -->
							<select id="b2bking_rule_paymentmethod_minmax_pro" name="b2bking_rule_paymentmethod_minmax_pro" class="b2bking-pro-select" style="display: none;">
								<option value=""><?php esc_html_e('— Select Type —', 'b2bking'); ?></option>
								<option value="minimum" <?php selected($rule_data['paymentmethod_minmax'] ?? '', 'minimum'); ?>><?php esc_html_e('Minimum Cart Value', 'b2bking'); ?></option>
								<option value="maximum" <?php selected($rule_data['paymentmethod_minmax'] ?? '', 'maximum'); ?>><?php esc_html_e('Maximum Cart Value', 'b2bking'); ?></option>
								<option value="minimumqty" <?php selected($rule_data['paymentmethod_minmax'] ?? '', 'minimumqty'); ?>><?php esc_html_e('Minimum Cart Quantity', 'b2bking'); ?></option>
								<option value="maximumqty" <?php selected($rule_data['paymentmethod_minmax'] ?? '', 'maximumqty'); ?>><?php esc_html_e('Maximum Cart Quantity', 'b2bking'); ?></option>
							</select>
							
							<!-- Action Grid -->
							<div class="action-grid">
								<div class="applies-option <?php echo ($rule_data['paymentmethod_minmax'] ?? '') === 'minimum' ? 'selected' : ''; ?>" 
									 data-payment-minmax="minimum">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 2V22M17 5H9.5C8.57174 5 7.6815 5.36875 7.02513 6.02513C6.36875 6.6815 6 7.57174 6 8.5C6 9.42826 6.36875 10.3185 7.02513 10.9749C7.6815 11.6313 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 12.3687 16.9749 13.0251C17.6313 13.6815 18 14.5717 18 15.5C18 16.4283 17.6313 17.3185 16.9749 17.9749C16.3185 18.6313 15.4283 19 14.5 19H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Minimum Cart Value', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Set minimum monetary value required', 'b2bking'); ?></div>
								</div>
								
								<div class="applies-option <?php echo ($rule_data['paymentmethod_minmax'] ?? '') === 'maximum' ? 'selected' : ''; ?>" 
									 data-payment-minmax="maximum">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 22V2M17 19H9.5C8.57174 19 7.6815 18.6313 7.02513 17.9749C6.36875 17.3185 6 16.4283 6 15.5C6 14.5717 6.36875 13.6815 7.02513 13.0251C7.6815 12.3687 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 11.6313 16.9749 10.9749C17.6313 10.3185 18 9.42826 18 8.5C18 7.57174 17.6313 6.6815 16.9749 6.02513C16.3185 5.36875 15.4283 5 14.5 5H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Maximum Cart Value', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Set maximum monetary value allowed', 'b2bking'); ?></div>
								</div>
								
								<div class="applies-option <?php echo ($rule_data['paymentmethod_minmax'] ?? '') === 'minimumqty' ? 'selected' : ''; ?>" 
									 data-payment-minmax="minimumqty">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M3 7V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V7M3 7L5 19H19L21 7M3 7H21M8 11V15M12 11V15M16 11V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Minimum Cart Quantity', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Set minimum item quantity required', 'b2bking'); ?></div>
								</div>
								
								<div class="applies-option <?php echo ($rule_data['paymentmethod_minmax'] ?? '') === 'maximumqty' ? 'selected' : ''; ?>" 
									 data-payment-minmax="maximumqty">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M3 7V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V7M3 7L5 19H19L21 7M3 7H21M8 15V11M12 15V11M16 15V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Maximum Cart Quantity', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Set maximum item quantity allowed', 'b2bking'); ?></div>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Card 7d: Shipping Method (for shipping method restriction rules) -->
					<div class="b2bking-card" id="shipping-method-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">7</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Shipping Method', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Select the shipping method this rule applies to', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_shippingmethod_pro" name="b2bking_rule_shippingmethod_pro" class="b2bking-pro-select">
								<option value=""><?php esc_html_e('— Select Shipping Method —', 'b2bking'); ?></option>
								<?php
								$selected_method = $rule_data['shippingmethod'] ?? '';
								// List all shipping methods
								$shipping_methods = array();
								$zone_names = array();
								$zone = 0;
								
								if (class_exists('WC_Shipping_Zones')) {
									$delivery_zones = WC_Shipping_Zones::get_zones();
									foreach ($delivery_zones as $key => $the_zone) {
										foreach ($the_zone['shipping_methods'] as $value) {
											array_push($shipping_methods, $value);
											array_push($zone_names, $the_zone['zone_name']);
										}
									}
									
									// Add UPS exception
									$shipping_methods_extra = WC()->shipping->get_shipping_methods();
									foreach ($shipping_methods_extra as $shipping_method){
										if ($shipping_method->id === 'wf_shipping_ups'){
											array_push($shipping_methods, $shipping_method);
											array_push($zone_names, 'UPS');
										}
									}
									
									foreach ($shipping_methods as $shipping_method){
										if( $shipping_method->enabled === 'yes' ){
											$method_value = $shipping_method->id . ':' . $shipping_method->instance_id;
											// Check if selected: match by full compound value or by ID only (for backward compatibility)
											$is_selected = ($method_value === $selected_method) || ($shipping_method->id === $selected_method);
											echo '<option value="' . esc_attr($method_value) . '" ' . selected($is_selected, true, false) . '>' . esc_html($shipping_method->title) . ' (' . esc_html($zone_names[$zone]) . ')' . '</option>';
											$zone++;
										}
									}
								}
								?>
							</select>
						</div>
					</div>
					
					<!-- Card 7e: Amount or Percentage (for payment method discount rules) -->
					<div class="b2bking-card" id="amount-percentage-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">7</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Amount or Percentage', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Choose whether to apply a fixed amount or percentage', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<!-- Hidden dropdown for form submission -->
							<select id="b2bking_rule_paymentmethod_percentamount_pro" name="b2bking_rule_paymentmethod_percentamount_pro" class="b2bking-pro-select" style="display: none;">
								<option value=""><?php esc_html_e('— Select Type —', 'b2bking'); ?></option>
								<option value="amount" <?php selected($rule_data['paymentmethod_percentamount'] ?? '', 'amount'); ?>><?php esc_html_e('Amount', 'b2bking'); ?></option>
								<option value="percentage" <?php selected($rule_data['paymentmethod_percentamount'] ?? '', 'percentage'); ?>><?php esc_html_e('Percentage', 'b2bking'); ?></option>
							</select>
							
							<!-- Action Grid -->
							<div class="action-grid">
								<div class="applies-option <?php echo ($rule_data['paymentmethod_percentamount'] ?? '') === 'amount' ? 'selected' : ''; ?>" 
									 data-payment-percentamount="amount">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 2V22M17 5H9.5C8.57174 5 7.6815 5.36875 7.02513 6.02513C6.36875 6.6815 6 7.57174 6 8.5C6 9.42826 6.36875 10.3185 7.02513 10.9749C7.6815 11.6313 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 12.3687 16.9749 13.0251C17.6313 13.6815 18 14.5717 18 15.5C18 16.4283 17.6313 17.3185 16.9749 17.9749C16.3185 18.6313 15.4283 19 14.5 19H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Amount', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Apply a fixed monetary amount', 'b2bking'); ?></div>
								</div>
								
								<div class="applies-option <?php echo ($rule_data['paymentmethod_percentamount'] ?? '') === 'percentage' ? 'selected' : ''; ?>" 
									 data-payment-percentamount="percentage">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M9 7H15M9 12H15M9 17H15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Percentage', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Apply as a percentage of the cart total', 'b2bking'); ?></div>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Card 7f: Discount or Surcharge (for payment method discount rules) -->
					<div class="b2bking-card" id="discount-surcharge-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">8</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Discount or Surcharge', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Choose whether to apply a discount or surcharge', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<!-- Hidden dropdown for form submission -->
							<select id="b2bking_rule_paymentmethod_discountsurcharge_pro" name="b2bking_rule_paymentmethod_discountsurcharge_pro" class="b2bking-pro-select" style="display: none;">
								<option value=""><?php esc_html_e('— Select Type —', 'b2bking'); ?></option>
								<option value="discount" <?php selected($rule_data['paymentmethod_discountsurcharge'] ?? '', 'discount'); ?>><?php esc_html_e('Discount', 'b2bking'); ?></option>
								<option value="surcharge" <?php selected($rule_data['paymentmethod_discountsurcharge'] ?? '', 'surcharge'); ?>><?php esc_html_e('Surcharge', 'b2bking'); ?></option>
							</select>
							
							<!-- Action Grid -->
							<div class="action-grid">
								<div class="applies-option <?php echo ($rule_data['paymentmethod_discountsurcharge'] ?? '') === 'discount' ? 'selected' : ''; ?>" 
									 data-payment-discountsurcharge="discount">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 22V2M17 5H9.5C8.57174 5 7.6815 5.36875 7.02513 6.02513C6.36875 6.6815 6 7.57174 6 8.5C6 9.42826 6.36875 10.3185 7.02513 10.9749C7.6815 11.6313 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 12.3687 16.9749 13.0251C17.6313 13.6815 18 14.5717 18 15.5C18 16.4283 17.6313 17.3185 16.9749 17.9749C16.3185 18.6313 15.4283 19 14.5 19H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Discount', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Reduce the order total', 'b2bking'); ?></div>
								</div>
								
								<div class="applies-option <?php echo ($rule_data['paymentmethod_discountsurcharge'] ?? '') === 'surcharge' ? 'selected' : ''; ?>" 
									 data-payment-discountsurcharge="surcharge">
									<div class="applies-icon">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 2V22M17 19H9.5C8.57174 19 7.6815 18.6313 7.02513 17.9749C6.36875 17.3185 6 16.4283 6 15.5C6 14.5717 6.36875 13.6815 7.02513 13.0251C7.6815 12.3687 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 11.6313 16.9749 10.9749C17.6313 10.3185 18 9.42826 18 8.5C18 7.57174 17.6313 6.6815 16.9749 6.02513C16.3185 5.36875 15.4283 5 14.5 5H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</div>
									<div class="applies-name"><?php esc_html_e('Surcharge', 'b2bking'); ?></div>
									<div class="applies-description"><?php esc_html_e('Increase the order total', 'b2bking'); ?></div>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Card 7g: Payment Method Name (for rename payment method rules) -->
					<div class="b2bking-card" id="payment-method-name-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">8</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Payment Method Name', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Enter the new name for this payment method', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<input type="text" 
								   id="b2bking_rule_paymentmethod_name_pro" 
								   name="b2bking_rule_paymentmethod_name_pro"
								   value="<?php echo esc_attr($rule_data['paymentmethod_name'] ?? ''); ?>" 
								   class="b2bking-pro-input-medium" 
								   placeholder="<?php esc_attr_e('Enter new payment method name', 'b2bking'); ?>">
						</div>
					</div>
					
					<!-- Card 7h: Information Table Rows (for info table rules) -->
					<div class="b2bking-card" id="information-table-rows-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">7</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Information Table Rows', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Configure information table rows for this rule', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<div id="b2bking_info_table_rows_container_pro">
								<?php
								$info_table_rows = $rule_data['info_table_rows'] ?? '';
								$info_table_rows = str_replace('&amp;', '&', $info_table_rows);
								$info_table_rows_array = !empty($info_table_rows) ? explode(';', $info_table_rows) : array();
								$rows_displayed = 0;
								
								// Display existing info table rows
								foreach ($info_table_rows_array as $row) {
									if (!empty($row)) {
										$row_values = explode(':', $row, 2);
										if (count($row_values) >= 2) {
											$rows_displayed++;
											?>
											<div class="b2bking-info-table-row">
												<input name="b2bking_info_table_rows_label_pro[]" 
												       placeholder="<?php esc_attr_e('Label', 'b2bking'); ?>" 
												       class="b2bking-info-table-label" 
												       type="text" 
												       value="<?php echo esc_attr($row_values[0]); ?>" />
												<input name="b2bking_info_table_rows_text_pro[]" 
												       placeholder="<?php esc_attr_e('Text', 'b2bking'); ?>" 
												       class="b2bking-info-table-text" 
												       type="text" 
												       value="<?php echo esc_attr($row_values[1]); ?>" />
												<button type="button" class="b2bking-remove-info-table-row-btn" aria-label="<?php esc_attr_e('Remove row', 'b2bking'); ?>">
													<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
														<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
													</svg>
												</button>
											</div>
											<?php
										}
									}
								}
								
								// If no rows exist, show one empty row by default
								if ($rows_displayed === 0) {
									?>
									<div class="b2bking-info-table-row">
										<input name="b2bking_info_table_rows_label_pro[]" 
										       placeholder="<?php esc_attr_e('Label', 'b2bking'); ?>" 
										       class="b2bking-info-table-label" 
										       type="text" />
										<input name="b2bking_info_table_rows_text_pro[]" 
										       placeholder="<?php esc_attr_e('Text', 'b2bking'); ?>" 
										       class="b2bking-info-table-text" 
										       type="text" />
										<button type="button" class="b2bking-remove-info-table-row-btn" aria-label="<?php esc_attr_e('Remove row', 'b2bking'); ?>">
											<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
												<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
											</svg>
										</button>
									</div>
									<?php
								}
								?>
							</div>
							<div class="b2bking-info-table-rows-actions">
								<button type="button" class="b2bking-add-info-table-row-btn" id="b2bking_add_info_table_row_pro">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
										<path d="M8 3V13M3 8H13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
									<?php esc_html_e('Add Row', 'b2bking'); ?>
								</button>
							</div>
							<p class="b2bking-info-table-description">
								<?php esc_html_e('Creates a new table or appends rows to the existing one.', 'b2bking'); ?>
							</p>
						</div>
					</div>
					
					<!-- Card 7i: Countries (for tax exemption and zero tax product rules) -->
					<div class="b2bking-card" id="countries-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">7</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Countries', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Select countries where this rule applies (multiple selection allowed)', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_countries_pro" name="b2bking_rule_countries_pro[]" class="b2bking-pro-select" multiple style="width: 100%;" data-selected-countries="<?php echo esc_attr($rule_data['countries'] ?? ''); ?>">
								<?php
								$selected_options_string = $rule_data['countries'] ?? '';
								// Trim whitespace and filter out empty values
								$selected_options = !empty($selected_options_string) ? array_filter(array_map('trim', explode(',', $selected_options_string))) : array();
								
								// Get countries list
								$countries_object = new WC_Countries;
								$countries_list = $countries_object->get_countries();
								$countries_list_eu = array('AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE');
								?>
								<optgroup label="<?php esc_attr_e('EU Countries', 'b2bking'); ?>" data-group="eu">
									<?php
									foreach($countries_list_eu as $eu_country){
										$country_is_selected = in_array($eu_country, $selected_options);
										?>
										<option value="<?php echo esc_attr($eu_country); ?>" <?php selected(true, $country_is_selected, false); ?>><?php echo esc_html($countries_list[$eu_country] ?? $eu_country); ?></option>
										<?php
									}
									?>
								</optgroup>
								<optgroup label="<?php esc_attr_e('All Other Countries', 'b2bking'); ?>" data-group="non-eu">
									<?php
									foreach($countries_list as $index => $country){
										// Skip EU countries as they're already listed
										if (!in_array($index, $countries_list_eu)){
											$country_is_selected = in_array($index, $selected_options);
											?>
											<option value="<?php echo esc_attr($index); ?>" <?php selected(true, $country_is_selected, false); ?>><?php echo esc_html($country); ?></option>
											<?php
										}
									}
									?>
								</optgroup>
							</select>
							<div class="b2bking-countries-select-buttons" style="margin-top: 12px; display: flex; gap: 10px; justify-content: flex-start;">
								<button type="button" class="b2bking-select-eu-countries b2bking-countries-button">
									<?php esc_html_e('Select All EU', 'b2bking'); ?>
								</button>
								<button type="button" class="b2bking-select-non-eu-countries b2bking-countries-button">
									<?php esc_html_e('Select All Non-EU', 'b2bking'); ?>
								</button>
							</div>
						</div>
					</div>
					
					<!-- Card 7j: Requires (for tax exemption and zero tax product rules) -->
					<div class="b2bking-card" id="requires-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">8</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Requires', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Select the validation requirement for this rule', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_requires_pro" name="b2bking_rule_requires_pro" class="b2bking-pro-select">
								<option value="nothing" <?php selected($rule_data['requires'] ?? '', 'nothing'); ?>><?php esc_html_e('Nothing', 'b2bking'); ?></option>
								<option value="validated_vat" <?php selected($rule_data['requires'] ?? '', 'validated_vat'); ?>><?php esc_html_e('VIES-Validated VAT ID', 'b2bking'); ?></option>
							</select>
						</div>
					</div>
					
					<!-- Card 7k: Pay Tax in Cart (for tax exemption rules) -->
					<div class="b2bking-card" id="pay-tax-in-cart-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">9</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Pay Tax in Cart', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Configure how tax is handled in the cart', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_showtax_pro" name="b2bking_rule_showtax_pro" class="b2bking-pro-select">
								<option value="no" <?php selected($rule_data['showtax'] ?? '', 'no'); ?>><?php esc_html_e('No', 'b2bking'); ?></option>
								<option value="yes" <?php selected($rule_data['showtax'] ?? '', 'yes'); ?>><?php esc_html_e('Yes', 'b2bking'); ?></option>
								<option value="display_only" <?php selected($rule_data['showtax'] ?? '', 'display_only'); ?>><?php esc_html_e('Display Only (Withholding Tax)', 'b2bking'); ?></option>
							</select>
							
							<!-- Include Shipping Cost (shown when display_only is selected) -->
							<div id="tax-shipping-container" style="display: none; margin-top: 20px;">
								<label for="b2bking_rule_tax_shipping_pro" style="display: block; margin-bottom: 8px; font-weight: 500;">
									<?php esc_html_e('Include shipping cost:', 'b2bking'); ?>
								</label>
								<select id="b2bking_rule_tax_shipping_pro" name="b2bking_rule_tax_shipping_pro" class="b2bking-pro-select">
									<option value="no" <?php selected($rule_data['tax_shipping'] ?? '', 'no'); ?>><?php esc_html_e('No', 'b2bking'); ?></option>
									<option value="yes" <?php selected($rule_data['tax_shipping'] ?? '', 'yes'); ?>><?php esc_html_e('Yes', 'b2bking'); ?></option>
								</select>
								
								<!-- Shipping Tax Rate (shown when include shipping = yes) -->
								<div id="tax-shipping-rate-container" style="display: none; margin-top: 20px;">
									<label for="b2bking_rule_tax_shipping_rate_pro" style="display: block; margin-bottom: 8px; font-weight: 500;">
										<?php esc_html_e('Shipping tax rate (%):', 'b2bking'); ?>
									</label>
									<input type="number" 
										   step="0.01" 
										   id="b2bking_rule_tax_shipping_rate_pro" 
										   name="b2bking_rule_tax_shipping_rate_pro"
										   value="<?php echo esc_attr($rule_data['tax_shipping_rate'] ?? ''); ?>" 
										   class="b2bking-pro-input-medium" 
										   placeholder="<?php esc_attr_e('Enter tax rate percentage', 'b2bking'); ?>">
								</div>
							</div>
						</div>
					</div>
					
					<!-- Card 7l: Tax Name (for add tax/fee rules) -->
					<div class="b2bking-card" id="tax-name-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">7</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Tax Name', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Enter the display name for this tax or fee (may show on frontend)', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<input type="text" 
								   id="b2bking_rule_taxname_pro" 
								   name="b2bking_rule_taxname_pro"
								   value="<?php echo esc_attr($rule_data['taxname'] ?? ''); ?>" 
								   class="b2bking-pro-input-medium" 
								   placeholder="<?php esc_attr_e('Enter tax or fee name', 'b2bking'); ?>">
						</div>
					</div>
					
					<!-- Card 7m: Taxable (for add tax/fee rules) -->
					<div class="b2bking-card" id="taxable-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">8</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Taxable', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Specify if this fee is taxable (whether WooCommerce taxes apply)', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<select id="b2bking_rule_tax_taxable_pro" name="b2bking_rule_tax_taxable_pro" class="b2bking-pro-select">
								<option value="no" <?php selected($rule_data['tax_taxable'] ?? '', 'no'); ?>><?php esc_html_e('No', 'b2bking'); ?></option>
								<option value="yes" <?php selected($rule_data['tax_taxable'] ?? '', 'yes'); ?>><?php esc_html_e('Yes', 'b2bking'); ?></option>
							</select>
						</div>
					</div>
									
					<!-- Card 8: Discount Options (only for discount rules) -->
                    <div class="b2bking-card" id="discount_options_card" style="display: none;">
						<div class="card-header">
							<span class="card-number">8</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Discount Options', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Optional settings for discount rules', 'b2bking'); ?></p>
											</div>
										</div>
						<div class="card-body">
                            <div class="discount-options">
                                <div class="discount-field discount-name-field">
                                    <div class="field-row">
                                        <label for="b2bking_rule_select_discountname_pro" class="discount-label">
                                            <?php esc_html_e('Display name', 'b2bking'); ?>
                                            <span class="optional-chip"><?php esc_html_e('Optional', 'b2bking'); ?></span>
                                        </label>
                                        <small class="field-help top-right"><?php esc_html_e('May appear on the product page or in cart.', 'b2bking'); ?></small>
                                    </div>
                                    <input type="text"
                                           id="b2bking_rule_select_discountname_pro"
                                           name="b2bking_rule_discountname_pro"
                                           value="<?php echo esc_attr($rule_data['discount_name'] ?? ''); ?>"
                                           class="b2bking-pro-input-medium"
                                           placeholder="<?php esc_attr_e('e.g., VIP Discount', 'b2bking'); ?>">
                                </div>

								<div class="discount-divider" aria-hidden="true"></div>

                                <div class="discount-field discount-saleprice-field clickable-field" id="discount_saleprice_field_container" role="button" tabindex="0" data-click-target="#b2bking_dynamic_rule_discount_show_everywhere_checkbox_input_pro">
                                    <div class="field-row">
                                        <label class="discount-label">
                                            <?php esc_html_e('Apply discount as sale price', 'b2bking'); ?>
                                            <span class="optional-chip"><?php esc_html_e('Optional', 'b2bking'); ?></span>
                                        </label>
                                        <small class="field-help top-right"><?php esc_html_e('Shows discounted price on product page.', 'b2bking'); ?></small>
                                    </div>
									<div class="saleprice-control">
										<label class="b2bking_dynamic_rule_pro_toggle_switch neutral-switch">
											<input type="checkbox"
											   id="b2bking_dynamic_rule_discount_show_everywhere_checkbox_input_pro"
											   name="b2bking_dynamic_rule_discount_show_everywhere_pro"
											   value="1"
											   <?php 
											   // Enable by default for new rules, or if existing rule has it enabled
											   $is_new_rule = empty($rule_id);
											   $show_everywhere_value = $rule_data['show_everywhere'] ?? '';
											   $is_enabled = ($show_everywhere_value === '1' || $show_everywhere_value === 'yes' || $show_everywhere_value === 'true');
											   if ($is_new_rule || $is_enabled) {
											   	echo 'checked="checked"';
											   }
											   ?>>
											<span class="b2bking_dynamic_rule_pro_rule_toggle_slider"></span>
										</label>
                                        <span class="b2bking-help-tip">
											<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
												<circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
												<path d="M8 12V8M8 4H8.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
											</svg>
											<div class="b2bking-help-tip-content">
												<div class="b2bking-help-tip-text"><?php esc_html_e('If enabled, this displays the discounted price as the product’s sale price. If disabled, the discount is applied in cart totals.', 'b2bking'); ?></div>
												<img class="b2bking-help-tip-image" src="https://kingsplugins.com/wp-content/uploads/2024/08/discount-sale-price-explained.webp" alt="Discount explanation">
											</div>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>

                    <style>
                        /* Scoped styles for Discount Options */
                        #discount_options_card .discount-options { display:flex; flex-direction:column; gap:12px; }
                        #discount_options_card .discount-field { background:#fff; border-radius:8px; padding:12px; }
                        div#discount_saleprice_field_container {
                            margin-left: 4px;
                        }
                        #discount_options_card .field-row { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:6px; }
                        #discount_options_card .discount-label { font-weight:600; color:#1F2937; display:block; margin-bottom:6px; }
                        #discount_options_card .field-row .discount-label { margin-bottom:0; }
                        #discount_options_card .optional-chip { margin-left:6px; color:#6B7280; font-weight:400; font-size:11px; }
                        #discount_options_card .field-help { color:#6B7280; font-size:12px; }
                        #discount_options_card .field-help.top-right { margin:0; }
						#discount_options_card .discount-divider { height:1px; background:#F3F4F6; margin:0 4px; }
						#discount_options_card .saleprice-control { display:flex; align-items:center; gap:10px; }
                        #discount_options_card .clickable-field { cursor:pointer; transition:border-color .15s ease, box-shadow .15s ease; }
                        #discount_options_card .clickable-field:hover { border-color:#906a1d; box-shadow:0 0 0 2px rgba(144,106,29,0.12); }
                        #discount_options_card .clickable-field.is-active { border-color:#906a1d; }
                        #discount_options_card .neutral-switch .b2bking_dynamic_rule_pro_rule_toggle_slider { background:#E5E7EB; }
                        #discount_options_card input[type="checkbox"]:checked + .b2bking_dynamic_rule_pro_rule_toggle_slider { background:#906a1d; }
					</style>

					<script>
						(function(){
							// Make the entire sale price field clickable (Variant A default, but works for all)
							var clickable = document.getElementById('discount_saleprice_field_container');
							var checkbox = document.getElementById('b2bking_dynamic_rule_discount_show_everywhere_checkbox_input_pro');
							function setActiveClass(){
								if (!clickable || !checkbox) return;
								if (checkbox.checked) {
									clickable.classList.add('is-active');
								} else {
									clickable.classList.remove('is-active');
								}
							}
							if (clickable && checkbox) {
								clickable.addEventListener('click', function(e){
									var helpTip = e.target.closest && e.target.closest('.b2bking-help-tip');
									if (helpTip) return; // do not toggle when interacting with the help tip
									checkbox.checked = !checkbox.checked;
									checkbox.dispatchEvent(new Event('change', { bubbles:true }));
									setActiveClass();
								});
								clickable.addEventListener('keydown', function(e){
									if (e.key === ' ' || e.key === 'Enter') {
										e.preventDefault();
										checkbox.checked = !checkbox.checked;
										checkbox.dispatchEvent(new Event('change', { bubbles:true }));
										setActiveClass();
									}
								});
								checkbox.addEventListener('change', setActiveClass);
								setActiveClass();
							}
						})();
					</script>

					<!-- Card 9: Additional Options (for min/max/required multiple rules) -->
					<div class="b2bking-card" id="additional-options-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">9</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Additional Options', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Configure additional settings for this rule', 'b2bking'); ?></p>
							</div>
						</div>
						<div class="card-body">
							<div class="discount-options">
								<div class="discount-field clickable-field" id="per_product_field_container" role="button" tabindex="0" data-click-target="#b2bking_dynamic_rule_per_product_checkbox_input_pro">
									<div class="field-row">
										<label class="discount-label">
											<?php esc_html_e('Apply to each individual product', 'b2bking'); ?>
											<span class="optional-chip"><?php esc_html_e('Optional', 'b2bking'); ?></span>
										</label>
										<small class="field-help top-right"><?php esc_html_e('Enables per-product rule enforcement', 'b2bking'); ?></small>
									</div>
									<div class="saleprice-control">
										<label class="b2bking_dynamic_rule_pro_toggle_switch neutral-switch">
											<input type="checkbox"
											   id="b2bking_dynamic_rule_per_product_checkbox_input_pro"
											   name="b2bking_dynamic_rule_per_product_pro"
											   value="1"
											   <?php 
											   $per_product_value = $rule_data['per_product'] ?? '';
											   $is_per_product_enabled = ($per_product_value === '1' || $per_product_value === 'yes' || $per_product_value === 'true');
											   if ($is_per_product_enabled) {
											   	echo 'checked="checked"';
											   }
											   ?>>
											<span class="b2bking_dynamic_rule_pro_rule_toggle_slider"></span>
										</label>
										<span class="b2bking-help-tip">
											<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
												<circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
												<path d="M8 12V8M8 4H8.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
											</svg>
											<div class="b2bking-help-tip-content">
												<div class="b2bking-help-tip-text"><?php esc_html_e('When enabled, the minimum, maximum, or multiple requirement applies to each individual product separately. For example, if you select a category, the requirement will apply to each product within that category individually, rather than to the total across all products.', 'b2bking'); ?></div>
											</div>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<style>
						/* Scoped styles for Additional Options */
						#additional-options-card .discount-options { display:flex; flex-direction:column; gap:12px; }
						#additional-options-card .discount-field { background:#fff; border-radius:8px; padding:12px; }
						#additional-options-card .field-row { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:6px; }
						#additional-options-card .discount-label { font-weight:600; color:#1F2937; display:block; margin-bottom:6px; }
						#additional-options-card .field-row .discount-label { margin-bottom:0; }
						#additional-options-card .optional-chip { margin-left:6px; color:#6B7280; font-weight:400; font-size:11px; }
						#additional-options-card .field-help { color:#6B7280; font-size:12px; }
						#additional-options-card .field-help.top-right { margin:0; }
						#additional-options-card .saleprice-control { display:flex; align-items:center; gap:10px; }
						#additional-options-card .clickable-field { cursor:pointer; transition:border-color .15s ease, box-shadow .15s ease; }
						#additional-options-card .clickable-field:hover { border-color:#906a1d; box-shadow:0 0 0 2px rgba(144,106,29,0.12); }
						#additional-options-card .clickable-field.is-active { border-color:#906a1d; }
						#additional-options-card .neutral-switch .b2bking_dynamic_rule_pro_rule_toggle_slider { background:#E5E7EB; }
						#additional-options-card input[type="checkbox"]:checked + .b2bking_dynamic_rule_pro_rule_toggle_slider { background:#906a1d; }
					</style>

					<script>
						(function(){
							// Make the entire per product field clickable
							var clickable = document.getElementById('per_product_field_container');
							var checkbox = document.getElementById('b2bking_dynamic_rule_per_product_checkbox_input_pro');
							function setActiveClass(){
								if (!clickable || !checkbox) return;
								if (checkbox.checked) {
									clickable.classList.add('is-active');
								} else {
									clickable.classList.remove('is-active');
								}
							}
							if (clickable && checkbox) {
								clickable.addEventListener('click', function(e){
									var helpTip = e.target.closest && e.target.closest('.b2bking-help-tip');
									if (helpTip) return;
									checkbox.checked = !checkbox.checked;
									checkbox.dispatchEvent(new Event('change', { bubbles:true }));
									setActiveClass();
								});
								clickable.addEventListener('keydown', function(e){
									if (e.key === ' ' || e.key === 'Enter') {
										e.preventDefault();
										checkbox.checked = !checkbox.checked;
										checkbox.dispatchEvent(new Event('change', { bubbles:true }));
										setActiveClass();
									}
								});
								checkbox.addEventListener('change', setActiveClass);
								setActiveClass();
							}
						})();
					</script>

					<!-- Card 10: Conditions -->
					<div class="b2bking-card" id="conditions-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">10</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Conditions', 'b2bking'); ?> <span class="optional-badge"><?php esc_html_e('Optional', 'b2bking'); ?></span></h3>
								<p><?php esc_html_e('Set additional conditions that must be met (all must apply)', 'b2bking'); ?></p>
							</div>
							<button type="button" class="card-toggle-btn" data-target="conditions-card-body">
								<span class="toggle-icon">+</span>
								<span class="toggle-text"><?php esc_html_e('Show', 'b2bking'); ?></span>
							</button>
						</div>
						<div class="card-body" id="conditions-card-body" style="display: none;">
							<input type="hidden" id="b2bking_rule_select_conditions_pro" name="b2bking_rule_conditions_pro" value="<?php echo esc_attr($rule_data['conditions'] ?? ''); ?>">
							
							<div id="b2bking_condition_number_1_pro" class="b2bking_rule_condition_container">
								<select class="b2bking_pro_rule_condition_name b2bking_condition_identifier_1" style="width: 200px; margin-right: 10px;">
									<option value="cart_total_quantity"><?php esc_html_e('Cart Total Quantity', 'b2bking'); ?></option>
									<option value="cart_total_value"><?php esc_html_e('Cart Total Value', 'b2bking'); ?></option>
									<option value="category_product_quantity"><?php esc_html_e('Category Product Quantity', 'b2bking'); ?></option>
									<option value="category_product_value"><?php esc_html_e('Category Product Value', 'b2bking'); ?></option>
									<option value="product_quantity"><?php esc_html_e('Product Quantity', 'b2bking'); ?></option>
									<option value="product_value"><?php esc_html_e('Product Value', 'b2bking'); ?></option>
								</select>
								<select class="b2bking_pro_rule_condition_operator b2bking_condition_identifier_1" style="width: 120px; margin-right: 10px;">
									<option value="greater"><?php esc_html_e('greater (>)', 'b2bking'); ?></option>
									<option value="equal"><?php esc_html_e('equal (=)', 'b2bking'); ?></option>
									<option value="smaller"><?php esc_html_e('smaller (<)', 'b2bking'); ?></option>
								</select>
								<input type="number" 
									   step="0.00001" 
									   class="b2bking_pro_rule_condition_number b2bking_condition_identifier_1" 
									   placeholder="<?php esc_attr_e('Enter the quantity/value', 'b2bking'); ?>"
									   style="width: 150px; margin-right: 10px;">
								<button type="button" class="b2bking_pro_rule_condition_add_button b2bking_condition_identifier_1"><?php esc_html_e('Add Condition', 'b2bking'); ?></button>
							</div>
						</div>
					</div>

					<!-- Card 11: Rule Priority -->
					<div class="b2bking-card" id="priority-card" style="display: none;">
						<div class="card-header">
							<span class="card-number">11</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Rule Priority', 'b2bking'); ?> <span class="optional-badge"><?php esc_html_e('Optional', 'b2bking'); ?></span></h3>
								<p><?php esc_html_e('Set priority for this rule', 'b2bking'); ?></p>
							</div>
							<button type="button" class="card-toggle-btn" data-target="priority-card-body">
								<span class="toggle-icon">+</span>
								<span class="toggle-text"><?php esc_html_e('Show', 'b2bking'); ?></span>
							</button>
						</div>
						<div class="card-body" id="priority-card-body" style="display: none;">
							<div class="form-group">
								<label for="b2bking_standard_rule_priority"><?php esc_html_e('Rule Priority:', 'b2bking'); ?></label>
								<input type="number" 
									   step="1" 
									   min="0" 
									   id="b2bking_standard_rule_priority_pro" 
									   name="b2bking_standard_rule_priority_pro"
									   value="<?php echo esc_attr($rule_data['priority'] ?? ''); ?>" 
									   class="b2bking-pro-input-small" 
									   placeholder="<?php esc_attr_e('e.g. 10', 'b2bking'); ?>">
								<small class="form-help">
									<?php esc_html_e('If you have multiple rules of the same type, the higher priority number will decide which one is used. If there is no priority configured, the plugin will give the best available rule / price to the customer. Priority is applied before conditions.', 'b2bking'); ?>
								</small>
							</div>
						</div>
					</div>

					<!-- Card 5: Actions -->
					<div class="b2bking-card final-card">
						<div class="final-card-content">
							<div class="final-card-header">
								<div class="final-card-icon">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</div>
								<div class="final-card-text">
									<h3><?php esc_html_e('Save & Activate', 'b2bking'); ?></h3>
									<p><?php esc_html_e('Save your changes and activate to apply this rule', 'b2bking'); ?></p>
								</div>
							</div>
							<div class="action-buttons">
								<button type="button" id="b2bking_dynamic_rule_pro_editor_cancel" class="btn-cancel">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
										<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
									<?php esc_html_e('Cancel', 'b2bking'); ?>
								</button>
								<button type="submit" id="b2bking_dynamic_rule_pro_editor_save" class="btn-primary">
									<span class="btn-icon-container">
										<svg class="btn-checkmark" width="16" height="16" viewBox="0 0 16 16" fill="none">
											<path d="M13.3333 4L6 11.3333L2.66667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										<img class="btn-loader" src="<?php echo esc_url(plugins_url('includes/assets/images/loadertransparent.svg', dirname(dirname(dirname(dirname(__FILE__)))))); ?>" style="display: none; width: 32px; height: 32px;" alt="">
									</span>
									<span class="btn-text"><?php echo $is_editing ? esc_html__('Update Rule', 'b2bking') : esc_html__('Create & Activate Rule', 'b2bking'); ?></span>
									<span class="btn-loading" style="display: none;">
										<svg class="spinner" width="16" height="16" viewBox="0 0 16 16" fill="none">
											<circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
												<animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
												<animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
											</circle>
										</svg>
										<span class="btn-loading-text"><?php echo $is_editing ? esc_html__('Updating...', 'b2bking') : esc_html__('Creating...', 'b2bking'); ?></span>
									</span>
								</button>
							</div>
						</div>
					</div>
					
					<?php if ($is_editing): ?>
						<input type="hidden" name="rule_id" value="<?php echo esc_attr($rule_id); ?>">
					<?php endif; ?>
				</form>
			</div>
			
			<!-- Live Preview -->
			<div class="b2bking-rule-preview">
				<div class="preview-content">
					<div class="preview-icon">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M1 12S5 4 12 4S23 12 23 12S19 20 12 20S1 12 1 12Z" stroke="currentColor" stroke-width="2"/>
							<circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
						</svg>
					</div>
					<div class="preview-text" id="rule-preview-text">
						<?php
						/* translators: Example preview text for dynamic rule. %1$s: condition (e.g., "amount spent in last 90 days"), %2$s: operator and value (e.g., "greater than $5,000"), %3$s: target group (e.g., "VIP Group") */
						printf(
							esc_html__('When customer\'s %1$s is %2$s → move to %3$s', 'b2bking'),
							'<strong>' . esc_html__('amount spent in last 90 days', 'b2bking') . '</strong>',
							'<strong>' . esc_html__('greater than $5,000', 'b2bking') . '</strong>',
							'<strong>' . esc_html__('VIP Group', 'b2bking') . '</strong>'
						);
						?>
					</div>
				</div>
			</div>
		</div>

		<!-- Success/Error Messages -->
		<div class="b2bking_dynamic_rule_pro_editor_message" id="message_container" style="display: none;">
			<div class="b2bking_dynamic_rule_pro_editor_message_content">
				<div class="b2bking_dynamic_rule_pro_editor_message_icon"></div>
				<div class="b2bking_dynamic_rule_pro_editor_message_text"></div>
				<button class="b2bking_dynamic_rule_pro_editor_message_close">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
			</div>
		</div>
		</div>
		<?php
