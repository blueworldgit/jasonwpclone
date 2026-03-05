<?php
// Use the original B2BKing header bar function
if (class_exists('B2bking_Admin')) {
    B2bking_Admin::get_header_bar();
}
?>
<div id="b2bking_dynamic_rules_pro_main_container">
	<!-- Header Section -->
	<div class="b2bking_rulespro_header">
		<div class="b2bking_rulespro_header_title">
			<svg class="b2bking_rulespro_header_icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect width="32" height="32" rx="8" fill="#906a1d"/>
				<g transform="translate(4, 4) scale(1)">
					<path d="M7.62442 4.4489C9.50121 3.69796 10.6208 3.25 12 3.25C13.3792 3.25 14.4988 3.69796 16.3756 4.4489L19.3451 5.6367C20.2996 6.01851 21.0728 6.32776 21.6035 6.60601C21.8721 6.74683 22.1323 6.90648 22.333 7.09894C22.5392 7.29668 22.75 7.59658 22.75 8C22.75 8.40342 22.5392 8.70332 22.333 8.90106C22.1323 9.09352 21.8721 9.25317 21.6035 9.39399C21.0728 9.67223 20.2996 9.98148 19.3451 10.3633L16.3756 11.5511C14.4988 12.302 13.3792 12.75 12 12.75C10.6208 12.75 9.50121 12.302 7.62443 11.5511L4.65495 10.3633C3.70037 9.98149 2.9272 9.67223 2.39647 9.39399C2.12786 9.25317 1.86765 9.09352 1.66701 8.90106C1.46085 8.70332 1.25 8.40342 1.25 8C1.25 7.59658 1.46085 7.29668 1.66701 7.09894C1.86765 6.90648 2.12786 6.74683 2.39647 6.60601C2.92721 6.32776 3.70037 6.01851 4.65496 5.63669L7.62442 4.4489Z" fill="#191821"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M2.50053 11.4415C2.50053 11.4415 2.50053 11.4415 2.50053 11.4415L2.49913 11.4402L2.50261 11.4432C2.50702 11.4471 2.51522 11.4541 2.52722 11.4641C2.55123 11.4842 2.59042 11.5161 2.64479 11.5581C2.75354 11.6422 2.92289 11.7663 3.1528 11.9154C3.61265 12.2136 4.31419 12.6115 5.25737 12.9887L8.06584 14.1121C10.0907 14.922 10.9396 15.25 12 15.25C13.0604 15.25 13.9093 14.922 15.9342 14.1121L18.7426 12.9887C19.6858 12.6115 20.3874 12.2136 20.8472 11.9154C21.0771 11.7663 21.2465 11.6422 21.3552 11.5581C21.4096 11.5161 21.4488 11.4842 21.4728 11.4641C21.4848 11.4541 21.493 11.4471 21.4974 11.4432L21.4995 11.4415C21.5 11.441 21.5006 11.4405 21.5011 11.44C21.8095 11.1652 22.2823 11.1915 22.5583 11.4992C22.8349 11.8075 22.8092 12.2817 22.5008 12.5583L22 12C22.5008 12.5583 22.501 12.5581 22.5008 12.5583L22.4994 12.5595L22.4977 12.5611L22.493 12.5652L22.4793 12.5772C22.4682 12.5868 22.4532 12.5997 22.4341 12.6155C22.3961 12.6473 22.3422 12.6911 22.2724 12.745C22.1329 12.8528 21.9299 13.001 21.6634 13.1739C21.1303 13.5196 20.3424 13.9644 19.2997 14.3814L16.4912 15.5048C16.4524 15.5204 16.4138 15.5358 16.3756 15.5511C14.4988 16.302 13.3792 16.75 12 16.75C10.6208 16.75 9.50121 16.302 7.62442 15.5511C7.58619 15.5358 7.54763 15.5204 7.50875 15.5048L4.70029 14.3814C3.65759 13.9644 2.86971 13.5196 2.33662 13.1739C2.07005 13.001 1.86705 12.8528 1.72757 12.745C1.65782 12.6911 1.60392 12.6473 1.56587 12.6155C1.54684 12.5997 1.53177 12.5868 1.52066 12.5772L1.50696 12.5652L1.50233 12.5611L1.50057 12.5595L1.4995 12.5586C1.49934 12.5584 1.49919 12.5583 2 12L1.4995 12.5586C1.19116 12.282 1.16512 11.8075 1.44171 11.4992C1.71775 11.1915 2.19075 11.1654 2.49913 11.4402M2.50053 11.4415C2.50053 11.4415 2.50053 11.4415 2.50053 11.4415V11.4415ZM2.49896 15.4401C2.19058 15.1652 1.71775 15.1915 1.44171 15.4992L2.49896 15.4401ZM2.49896 15.4401L2.50261 15.4432C2.50702 15.4471 2.51522 15.4541 2.52722 15.4641C2.55123 15.4842 2.59042 15.5161 2.64479 15.5581C2.75354 15.6422 2.92289 15.7663 3.1528 15.9154C3.61265 16.2136 4.31419 16.6114 5.25737 16.9887L8.06584 18.1121C10.0907 18.922 10.9396 19.25 12 19.25C13.0604 19.25 13.9093 18.922 15.9342 18.1121L18.7426 16.9887C19.6858 16.6114 20.3874 16.2136 20.8472 15.9154C21.0771 15.7663 21.2465 15.6422 21.3552 15.5581C21.4096 15.5161 21.4488 15.4842 21.4728 15.4641C21.4848 15.4541 21.493 15.4471 21.4974 15.4432L21.4995 15.4415C21.5 15.441 21.5006 15.4405 21.5011 15.44C21.8095 15.1652 22.2823 15.1915 22.5583 15.4992C22.8349 15.8075 22.8092 16.2817 22.5008 16.5583L22.0166 16.0185C22.5008 16.5583 22.501 16.5581 22.5008 16.5583L22.4994 16.5595L22.4977 16.5611L22.493 16.5652L22.4793 16.5772C22.4682 16.5868 22.4532 16.5997 22.4341 16.6155C22.3961 16.6473 22.3422 16.6911 22.2724 16.745C22.1329 16.8528 21.9299 17.001 21.6634 17.1739C21.1303 17.5196 20.3424 17.9644 19.2997 18.3814L16.4912 19.5048C16.4524 19.5204 16.4138 19.5358 16.3756 19.5511C14.4988 20.302 13.3792 20.75 12 20.75C10.6208 20.75 9.50121 20.302 7.62443 19.5511C7.58619 19.5358 7.54763 19.5204 7.50875 19.5048L4.70029 18.3814C3.65759 17.9644 2.86971 17.5196 2.33662 17.1739C2.07005 17.001 1.86705 16.8528 1.72757 16.745C1.65782 16.6911 1.60392 16.6473 1.56587 16.6155C1.54684 16.5997 1.53177 16.5868 1.52066 16.5772L1.50696 16.5652L1.50233 16.5611L1.50057 16.5595L1.4995 16.5586C1.49934 16.5584 1.49919 16.5583 2 16L1.4995 16.5586C1.19116 16.282 1.16512 15.8075 1.44171 15.4992" fill="#191821"/>
				</g>
			</svg>
		<div class="b2bking_rulespro_header_text">
			<h1><?php esc_html_e('Dynamic Rules', 'b2bking'); ?></h1>
			<p class="b2bking_rulespro_header_subtitle"><?php esc_html_e('Create pricing rules, discounts, order restrictions, and more based on customer groups and product criteria', 'b2bking'); ?></p>
		</div>
		</div>
		<div class="b2bking_rulespro_header_actions">
			<button class="b2bking_rulespro_import_export_btn b2bking_rulespro_import_export_btn_disabled" disabled>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
					<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
					<g id="SVGRepo_iconCarrier"> 
						<path fill-rule="evenodd" d="M19.7903934,18.6127185 L19.7072026,18.7069258 L16.7071326,21.7069258 C16.6801187,21.7339397 16.6515664,21.7594153 16.6216183,21.7832098 L16.500353,21.8659223 L16.500353,21.8659223 L16.427064,21.9043128 L16.427064,21.9043128 L16.3400271,21.9405322 L16.3400271,21.9405322 L16.2335653,21.9723902 L16.2335653,21.9723902 L16.116647,21.9930913 L16.033029,21.9992768 L16.033029,21.9992768 L15.9409671,21.9980859 L15.8251966,21.9845213 L15.8251966,21.9845213 L15.6878494,21.9500809 L15.6878494,21.9500809 L15.5767675,21.9061457 L15.5767675,21.9061457 L15.4792778,21.8538236 L15.4792778,21.8538236 L15.3832241,21.7870331 L15.2928749,21.7069258 L12.2927974,18.7069258 C11.902263,18.3164015 11.902263,17.6832365 12.2927974,17.2927122 C12.6532907,16.9322283 13.2205364,16.9044987 13.6128377,17.2095236 L13.7070475,17.2927122 L14.9998966,18.584819 L14.9999741,8.99981902 C14.9999741,8.48698318 15.3860143,8.06431186 15.883353,8.00654675 L16.0000259,7.99981902 C16.5523106,7.99981902 17.0000259,8.44753427 17.0000259,8.99981902 L16.9998966,18.584819 L18.2929525,17.2927122 C18.6534458,16.9322283 19.2206915,16.9044987 19.6129929,17.2095236 L19.7072026,17.2927122 C20.0376548,17.6231559 20.0884936,18.1273245 19.859719,18.511222 L19.7903934,18.6127185 L19.7903934,18.6127185 Z M4.29279737,5.29255711 L7.29286736,2.29255711 L7.40481484,2.1959774 L7.51569719,2.12453966 L7.51569719,2.12453966 L7.62891562,2.07076785 L7.62891562,2.07076785 L7.73413453,2.03538486 L7.73413453,2.03538486 L7.82519664,2.01496161 L7.82519664,2.01496161 L7.94096709,2.00139699 L8.05914398,2.00139699 L8.05914398,2.00139699 L8.17466132,2.0149356 L8.17466132,2.0149356 L8.31274961,2.04953478 L8.31274961,2.04953478 L8.36670687,2.06905084 L8.45385903,2.10832658 L8.45385903,2.10832658 L8.52068604,2.14573132 L8.52068604,2.14573132 L8.60170489,2.20078783 L8.60170489,2.20078783 L8.66547577,2.25320781 L8.66547577,2.25320781 L8.70713264,2.29255711 L11.7072026,5.29255711 L11.7903934,5.38676445 C12.0700068,5.74636472 12.0700068,6.25296306 11.7903934,6.61256333 L11.7072026,6.70677067 L11.6129929,6.78995928 C11.2533833,7.06956543 10.7467718,7.06956543 10.3871623,6.78995928 L10.2929525,6.70677067 L8.99989658,5.41466389 L9.00002585,14.9996639 C9.00002585,15.5124997 8.61398566,15.9351711 8.11664698,15.9929362 L8.00002585,15.9996639 L7.88335302,15.9929362 C7.42427116,15.9396145 7.06002351,15.5753669 7.00670188,15.116285 L6.99997415,14.9996639 L6.99989658,5.41466389 L5.7070475,6.70677067 L5.61283773,6.78995928 C5.22053638,7.09498417 4.65329066,7.06725463 4.29279737,6.70677067 C3.93230409,6.34628671 3.90457384,5.77905565 4.20960662,5.38676445 L4.29279737,5.29255711 Z" fill="currentColor"></path> 
					</g>
				</svg>
				<?php esc_html_e('Import / Export', 'b2bking'); ?>
				<span class="b2bking_rulespro_coming_soon_badge"><?php esc_html_e('Coming Soon...', 'b2bking'); ?></span>
			</button>
			<a href="<?php echo admin_url('admin.php?page=b2bking_dynamic_rule_pro_editor'); ?>" class="b2bking_rulespro_add_rule_btn">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
					<path d="M8 1v14M1 8h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
				<?php esc_html_e('Add New Rule', 'b2bking'); ?>
			</a>
		</div>
	</div>

	<!-- Main Content -->
	<div class="b2bking_dynamic_rules_pro_content">
		<?php
		// Get customer groups for filtering
		$customer_groups = get_posts([
			'post_type' => 'b2bking_group',
			'post_status' => 'publish',
			'numberposts' => -1,
		]);
		?>
		
		<!-- Filters Section -->
		<div class="b2bking_rulespro_filters">
			<div class="b2bking_rulespro_filters_row">
				<div class="b2bking_rulespro_search_container">
					<svg class="b2bking_rulespro_search_icon" width="15" height="15" viewBox="0 0 20 20" fill="none">
						<path d="M19 19L13 13M15 8A7 7 0 1 1 1 8A7 7 0 0 1 15 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<input type="text" id="rule_search" placeholder="<?php esc_attr_e('Search rules by name or condition...', 'b2bking'); ?>" class="b2bking_rulespro_search_input">
				</div>
				
				<div class="b2bking_rulespro_filter_group">
					<select id="rule_type_filter" class="b2bking_rulespro_filter_select">
						<option value=""><?php esc_html_e('All Rule Types', 'b2bking'); ?></option>
						<optgroup label="<?php esc_attr_e('Discounts & Pricing', 'b2bking'); ?>">
							<option value="discount_amount"><?php esc_html_e('Discount (Amount)', 'b2bking'); ?></option>
							<option value="discount_percentage"><?php esc_html_e('Discount (Percentage)', 'b2bking'); ?></option>
							<option value="raise_price"><?php esc_html_e('Raise Price (Percentage)', 'b2bking'); ?></option>
							<option value="bogo_discount"><?php esc_html_e('Buy X Get 1 Free', 'b2bking'); ?></option>
							<option value="fixed_price"><?php esc_html_e('Fixed Price', 'b2bking'); ?></option>
							<option value="hidden_price"><?php esc_html_e('Hidden Price', 'b2bking'); ?></option>
							<option value="tiered_price"><?php esc_html_e('Tiered Price', 'b2bking'); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e('Order Rules', 'b2bking'); ?>">
							<option value="free_shipping"><?php esc_html_e('Free Shipping', 'b2bking'); ?></option>
							<option value="minimum_order"><?php esc_html_e('Minimum Order', 'b2bking'); ?></option>
							<option value="maximum_order"><?php esc_html_e('Maximum Order', 'b2bking'); ?></option>
							<option value="required_multiple"><?php esc_html_e('Required Multiple (Quantity Step)', 'b2bking'); ?></option>
							<option value="unpurchasable"><?php esc_html_e('Non-Purchasable', 'b2bking'); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e('Taxes', 'b2bking'); ?>">
							<option value="tax_exemption_user"><?php esc_html_e('Tax Exemption', 'b2bking'); ?></option>
							<option value="tax_exemption"><?php esc_html_e('Zero Tax Product', 'b2bking'); ?></option>
							<option value="add_tax_percentage"><?php esc_html_e('Add Tax / Fee (Percentage)', 'b2bking'); ?></option>
							<option value="add_tax_amount"><?php esc_html_e('Add Tax / Fee (Amount)', 'b2bking'); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e('Advanced Rules', 'b2bking'); ?>">
							<option value="replace_prices_quote"><?php esc_html_e('Replace Cart with Quote System', 'b2bking'); ?></option>
							<option value="quotes_products"><?php esc_html_e('Quotes on Specific Products', 'b2bking'); ?></option>
							<option value="set_currency_symbol"><?php esc_html_e('Set Currency', 'b2bking'); ?></option>
							<option value="payment_method_minmax_order"><?php esc_html_e('Payment Method Min / Max Order', 'b2bking'); ?></option>
							<option value="payment_method_discount"><?php esc_html_e('Payment Method Discount / Surcharge', 'b2bking'); ?></option>
							<option value="payment_method_restriction"><?php esc_html_e('Payment Method Product Restriction', 'b2bking'); ?></option>
							<option value="shipping_method_restriction"><?php esc_html_e('Shipping Method Product Restriction', 'b2bking'); ?></option>
							<option value="rename_purchase_order"><?php esc_html_e('Rename Payment Method', 'b2bking'); ?></option>
							<option value="info_table"><?php esc_html_e('Add to Information Table', 'b2bking'); ?></option>
						</optgroup>
					</select>
				</div>
				
				<div class="b2bking_rulespro_filter_group">
					<select id="applies_to_filter" class="b2bking_rulespro_filter_select">
						<option value=""><?php esc_html_e('All Applications', 'b2bking'); ?></option>
						<option value="cart_total"><?php esc_html_e('Cart Total', 'b2bking'); ?></option>
						<option value="multiple_options"><?php esc_html_e('Specific Items', 'b2bking'); ?></option>
						<option value="excluding_multiple_options"><?php esc_html_e('All Except...', 'b2bking'); ?></option>
					</select>
				</div>
				
				<div class="b2bking_rulespro_filter_group">
					<select id="customer_group_filter" class="b2bking_rulespro_filter_select">
						<option value=""><?php esc_html_e('All Customers', 'b2bking'); ?></option>
						<optgroup label="<?php esc_attr_e('General Categories', 'b2bking'); ?>">
							<option value="all_registered"><?php esc_html_e('All Logged-in Users', 'b2bking'); ?></option>
							<option value="everyone_registered_b2b"><?php esc_html_e('B2B Customers', 'b2bking'); ?></option>
							<option value="everyone_registered_b2c"><?php esc_html_e('B2C Customers', 'b2bking'); ?></option>
							<option value="user_0"><?php esc_html_e('Guest Visitors', 'b2bking'); ?></option>
						</optgroup>
						<?php if (!empty($customer_groups)): ?>
						<optgroup label="<?php esc_attr_e('Customer Groups', 'b2bking'); ?>">
							<?php foreach ($customer_groups as $group): ?>
								<option value="group_<?php echo esc_attr($group->ID); ?>"><?php echo esc_html($group->post_title); ?></option>
							<?php endforeach; ?>
						</optgroup>
						<?php endif; ?>
					</select>
				</div>
				
				<div class="b2bking_rulespro_filter_group">
					<select id="status_filter" class="b2bking_rulespro_filter_select">
						<option value=""><?php esc_html_e('All Status', 'b2bking'); ?></option>
						<option value="enabled"><?php esc_html_e('Enabled', 'b2bking'); ?></option>
						<option value="disabled"><?php esc_html_e('Disabled', 'b2bking'); ?></option>
					</select>
				</div>
				
				<button class="b2bking_rulespro_clear_filters_btn" id="clear_filters">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
					<?php esc_html_e('Clear', 'b2bking'); ?>
				</button>
			</div>
		</div>

		<!-- Rules Container -->
		<div class="b2bking_rulespro_rules_container">
			<div class="b2bking_rulespro_rules_header">
			<div class="b2bking_rulespro_rules_actions">
				<div class="b2bking_rulespro_left_actions">
					<button class="b2bking_rulespro_bulk_actions_btn" id="bulk_actions">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
							<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
							<g id="SVGRepo_iconCarrier"> 
								<path fill-rule="evenodd" clip-rule="evenodd" d="M10.9436 1.25H13.0564C14.8942 1.24998 16.3498 1.24997 17.489 1.40314C18.6614 1.56076 19.6104 1.89288 20.3588 2.64124C20.6516 2.93414 20.6516 3.40901 20.3588 3.7019C20.0659 3.9948 19.591 3.9948 19.2981 3.7019C18.8749 3.27869 18.2952 3.02502 17.2892 2.88976C16.2615 2.75159 14.9068 2.75 13 2.75H11C9.09318 2.75 7.73851 2.75159 6.71085 2.88976C5.70476 3.02502 5.12511 3.27869 4.7019 3.7019C4.27869 4.12511 4.02502 4.70476 3.88976 5.71085C3.75159 6.73851 3.75 8.09318 3.75 10V14C3.75 15.9068 3.75159 17.2615 3.88976 18.2892C4.02502 19.2952 4.27869 19.8749 4.7019 20.2981C5.12511 20.7213 5.70476 20.975 6.71085 21.1102C7.73851 21.2484 9.09318 21.25 11 21.25H13C14.9068 21.25 16.2615 21.2484 17.2892 21.1102C18.2952 20.975 18.8749 20.7213 19.2981 20.2981C19.994 19.6022 20.2048 18.5208 20.2414 15.9892C20.2474 15.575 20.588 15.2441 21.0022 15.2501C21.4163 15.2561 21.7472 15.5967 21.7412 16.0108C21.7061 18.4383 21.549 20.1685 20.3588 21.3588C19.6104 22.1071 18.6614 22.4392 17.489 22.5969C16.3498 22.75 14.8942 22.75 13.0564 22.75H10.9436C9.10583 22.75 7.65019 22.75 6.51098 22.5969C5.33856 22.4392 4.38961 22.1071 3.64124 21.3588C2.89288 20.6104 2.56076 19.6614 2.40314 18.489C2.24997 17.3498 2.24998 15.8942 2.25 14.0564V9.94358C2.24998 8.10582 2.24997 6.65019 2.40314 5.51098C2.56076 4.33856 2.89288 3.38961 3.64124 2.64124C4.38961 1.89288 5.33856 1.56076 6.51098 1.40314C7.65019 1.24997 9.10582 1.24998 10.9436 1.25ZM18.1131 7.04556C19.1739 5.98481 20.8937 5.98481 21.9544 7.04556C23.0152 8.1063 23.0152 9.82611 21.9544 10.8869L17.1991 15.6422C16.9404 15.901 16.7654 16.076 16.5693 16.2289C16.3387 16.4088 16.0892 16.563 15.8252 16.6889C15.6007 16.7958 15.3659 16.8741 15.0187 16.9897L12.9351 17.6843C12.4751 17.8376 11.9679 17.7179 11.625 17.375C11.2821 17.0321 11.1624 16.5249 11.3157 16.0649L11.9963 14.0232C12.001 14.0091 12.0056 13.9951 12.0102 13.9813C12.1259 13.6342 12.2042 13.3993 12.3111 13.1748C12.437 12.9108 12.5912 12.6613 12.7711 12.4307C12.924 12.2346 13.099 12.0596 13.3578 11.8009C13.3681 11.7906 13.3785 11.7802 13.3891 11.7696L18.1131 7.04556ZM20.8938 8.10622C20.4188 7.63126 19.6488 7.63126 19.1738 8.10622L18.992 8.288C19.0019 8.32149 19.0132 8.3571 19.0262 8.39452C19.1202 8.66565 19.2988 9.02427 19.6372 9.36276C19.9757 9.70125 20.3343 9.87975 20.6055 9.97382C20.6429 9.9868 20.6785 9.99812 20.712 10.008L20.8938 9.8262C21.3687 9.35124 21.3687 8.58118 20.8938 8.10622ZM19.5664 11.1536C19.2485 10.9866 18.9053 10.7521 18.5766 10.4234C18.2479 10.0947 18.0134 9.75146 17.8464 9.43357L14.4497 12.8303C14.1487 13.1314 14.043 13.2388 13.9538 13.3532C13.841 13.4979 13.7442 13.6545 13.6652 13.8202C13.6028 13.9511 13.5539 14.0936 13.4193 14.4976L13.019 15.6985L13.3015 15.981L14.5024 15.5807C14.9064 15.4461 15.0489 15.3972 15.1798 15.3348C15.3455 15.2558 15.5021 15.159 15.6468 15.0462C15.7612 14.957 15.8686 14.8513 16.1697 14.5503L19.5664 11.1536ZM7.25 9C7.25 8.58579 7.58579 8.25 8 8.25H14.5C14.9142 8.25 15.25 8.58579 15.25 9C15.25 9.41421 14.9142 9.75 14.5 9.75H8C7.58579 9.75 7.25 9.41421 7.25 9ZM7.25 13C7.25 12.5858 7.58579 12.25 8 12.25H10.5C10.9142 12.25 11.25 12.5858 11.25 13C11.25 13.4142 10.9142 13.75 10.5 13.75H8C7.58579 13.75 7.25 13.4142 7.25 13ZM7.25 17C7.25 16.5858 7.58579 16.25 8 16.25H9.5C9.91421 16.25 10.25 16.5858 10.25 17C10.25 17.4142 9.91421 17.75 9.5 17.75H8C7.58579 17.75 7.25 17.4142 7.25 17Z" fill="currentColor"></path> 
							</g>
						</svg>
					<?php esc_html_e('Bulk Actions', 'b2bking'); ?>
				</button>
				
				<!-- Filter Toggle Button -->
				<button class="b2bking_rulespro_filter_toggle_btn" id="filter_toggle" title="<?php esc_attr_e('Toggle Filters', 'b2bking'); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" overflow="visible">
						<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
						<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
						<g id="SVGRepo_iconCarrier"> 
							<!-- Original Filter Icon -->
							<path fill-rule="evenodd" clip-rule="evenodd" d="M4.95301 2.25C4.96862 2.25 4.98429 2.25 5.00001 2.25L19.047 2.25C19.7139 2.24997 20.2841 2.24994 20.7398 2.30742C21.2231 2.36839 21.6902 2.50529 22.0738 2.86524C22.4643 3.23154 22.6194 3.68856 22.6875 4.16405C22.7501 4.60084 22.7501 5.14397 22.75 5.76358L22.75 6.54012C22.75 7.02863 22.75 7.45095 22.7136 7.80311C22.6743 8.18206 22.5885 8.5376 22.3825 8.87893C22.1781 9.2177 21.9028 9.4636 21.5854 9.68404C21.2865 9.8917 20.9045 10.1067 20.4553 10.3596L17.5129 12.0159C16.8431 12.393 16.6099 12.5288 16.4542 12.6639C16.0966 12.9744 15.8918 13.3188 15.7956 13.7504C15.7545 13.9349 15.75 14.1672 15.75 14.8729L15.75 17.605C15.7501 18.5062 15.7501 19.2714 15.6574 19.8596C15.5587 20.4851 15.3298 21.0849 14.7298 21.4602C14.1434 21.827 13.4975 21.7933 12.8698 21.6442C12.2653 21.5007 11.5203 21.2094 10.6264 20.8599L10.5395 20.826C10.1208 20.6623 9.75411 20.519 9.46385 20.3691C9.1519 20.208 8.8622 20.0076 8.64055 19.6957C8.41641 19.3803 8.32655 19.042 8.28648 18.6963C8.24994 18.381 8.24997 18.0026 8.25 17.5806L8.25 14.8729C8.25 14.1672 8.24555 13.9349 8.20442 13.7504C8.1082 13.3188 7.90342 12.9744 7.54584 12.6639C7.39014 12.5288 7.15692 12.393 6.48714 12.0159L3.54471 10.3596C3.09549 10.1067 2.71353 9.8917 2.41458 9.68404C2.09724 9.4636 1.82191 9.2177 1.61747 8.87893C1.41148 8.5376 1.32571 8.18206 1.28645 7.80311C1.24996 7.45094 1.24998 7.02863 1.25 6.54012L1.25001 5.81466C1.25001 5.79757 1.25 5.78054 1.25 5.76357C1.24996 5.14396 1.24991 4.60084 1.31251 4.16405C1.38064 3.68856 1.53576 3.23154 1.92618 2.86524C2.30983 2.50529 2.77695 2.36839 3.26024 2.30742C3.71592 2.24994 4.28607 2.24997 4.95301 2.25ZM3.44796 3.79563C3.1143 3.83772 3.0082 3.90691 2.95251 3.95916C2.90359 4.00505 2.83904 4.08585 2.79734 4.37683C2.75181 4.69454 2.75001 5.12868 2.75001 5.81466V6.50448C2.75001 7.03869 2.75093 7.38278 2.77846 7.64854C2.8041 7.89605 2.84813 8.01507 2.90174 8.10391C2.9569 8.19532 3.0485 8.298 3.27034 8.45209C3.50406 8.61444 3.82336 8.79508 4.30993 9.06899L7.22296 10.7088C7.25024 10.7242 7.2771 10.7393 7.30357 10.7542C7.86227 11.0685 8.24278 11.2826 8.5292 11.5312C9.12056 12.0446 9.49997 12.6682 9.66847 13.424C9.75036 13.7913 9.75022 14.2031 9.75002 14.7845C9.75002 14.8135 9.75 14.843 9.75 14.8729V17.5424C9.75 18.0146 9.75117 18.305 9.77651 18.5236C9.79942 18.7213 9.83552 18.7878 9.8633 18.8269C9.89359 18.8695 9.95357 18.9338 10.152 19.0363C10.3644 19.146 10.6571 19.2614 11.1192 19.442C12.0802 19.8177 12.7266 20.0685 13.2164 20.1848C13.695 20.2985 13.8527 20.2396 13.9343 20.1885C14.0023 20.146 14.1073 20.0597 14.1757 19.626C14.2478 19.1686 14.25 18.5234 14.25 17.5424V14.8729C14.25 14.843 14.25 14.8135 14.25 14.7845C14.2498 14.2031 14.2496 13.7913 14.3315 13.424C14.5 12.6682 14.8794 12.0446 15.4708 11.5312C15.7572 11.2826 16.1377 11.0685 16.6964 10.7542C16.7229 10.7393 16.7498 10.7242 16.7771 10.7088L19.6901 9.06899C20.1767 8.79508 20.496 8.61444 20.7297 8.45209C20.9515 8.298 21.0431 8.19532 21.0983 8.10391C21.1519 8.01507 21.1959 7.89605 21.2215 7.64854C21.2491 7.38278 21.25 7.03869 21.25 6.50448V5.81466C21.25 5.12868 21.2482 4.69454 21.2027 4.37683C21.161 4.08585 21.0964 4.00505 21.0475 3.95916C20.9918 3.90691 20.8857 3.83772 20.5521 3.79563C20.2015 3.75141 19.727 3.75 19 3.75H5.00001C4.27297 3.75 3.79854 3.75141 3.44796 3.79563Z" fill="currentColor"></path>
							<!-- Search Icon Overlay with white background circle -->
							<g transform="translate(11, 6) scale(0.55)">
								<!-- White background circle -->
								<circle cx="10" cy="10" r="9" fill="white" stroke="white" stroke-width="2"></circle>
								<!-- Search icon -->
								<path d="M14.9536 14.9458L21 21M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
							</g>
						</g>
					</svg>
				</button>
				</div>
				
				<!-- View Toggle -->
				<div class="b2bking_dynamic_rules_pro_view_toggle">
					<button class="b2bking_dynamic_rules_pro_view_btn active" data-view="grid" title="<?php esc_attr_e('Grid View', 'b2bking'); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
							<rect x="1" y="1" width="6" height="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="9" y="1" width="6" height="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="1" y="9" width="6" height="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="9" y="9" width="6" height="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
						</svg>
					</button>
					<button class="b2bking_dynamic_rules_pro_view_btn" data-view="list" title="<?php esc_attr_e('List View', 'b2bking'); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
							<rect x="1" y="1" width="14" height="2" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="1" y="7" width="14" height="2" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="1" y="13" width="14" height="2" stroke="currentColor" stroke-width="1.5" fill="none"/>
						</svg>
					</button>
				</div>
				
				<!-- Bulk Actions Toolbar (hidden by default) -->
					<div id="b2bking_dynamic_rules_pro_bulk_toolbar" class="b2bking_dynamic_rules_pro_bulk_toolbar" style="display: none;">
						<div class="b2bking_dynamic_rules_pro_bulk_toolbar_content">
							<div class="b2bking_dynamic_rules_pro_bulk_toolbar_left">
								<span class="b2bking_dynamic_rules_pro_bulk_selected_count"><span class="b2bking_dynamic_rules_pro_bulk_count_number">0</span> <?php esc_html_e('rules selected', 'b2bking'); ?></span>
								<button class="b2bking_dynamic_rules_pro_bulk_select_all" id="select_all_rules"><?php esc_html_e('Select All', 'b2bking'); ?></button>
							</div>
							<div class="b2bking_dynamic_rules_pro_bulk_toolbar_right">
								<button class="b2bking_dynamic_rules_pro_bulk_action_btn b2bking_dynamic_rules_pro_bulk_enable" id="bulk_enable_rules">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
										<path fill="currentColor" d="M17 7H7a5 5 0 1 0 0 10h10a5 5 0 1 0 0-10Zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"></path>
									</svg>
									<?php esc_html_e('Enable Selected', 'b2bking'); ?>
								</button>
								<button class="b2bking_dynamic_rules_pro_bulk_action_btn b2bking_dynamic_rules_pro_bulk_disable" id="bulk_disable_rules">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
										<path fill="currentColor" d="M17 6H7c-3.31 0-6 2.69-6 6s2.69 6 6 6h10c3.31 0 6-2.69 6-6s-2.69-6-6-6Zm0 10H7c-2.21 0-4-1.79-4-4s1.79-4 4-4h10c2.21 0 4 1.79 4 4s-1.79 4-4 4ZM7 9c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3Z"></path>
									</svg>
									<?php esc_html_e('Disable Selected', 'b2bking'); ?>
								</button>
								<button class="b2bking_dynamic_rules_pro_bulk_action_btn b2bking_dynamic_rules_pro_bulk_delete" id="bulk_delete_rules">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none">
										<path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
										<path d="M18.8332 8.5L18.3732 15.3991C18.1962 18.054 18.1077 19.3815 17.2427 20.1907C16.3777 21 15.0473 21 12.3865 21H11.6132C8.95235 21 7.62195 21 6.75694 20.1907C5.89194 19.3815 5.80344 18.054 5.62644 15.3991L5.1665 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
										<path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path>
									</svg>
									<?php esc_html_e('Delete', 'b2bking'); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Grid View -->
			<div class="b2bking_dynamic_rules_pro_rules_grid active" id="rules_grid">
				<!-- Rules will be loaded here via AJAX -->
			</div>
			
			<!-- List View -->
			<div class="b2bking_dynamic_rules_pro_rules_list" id="rules_list">
				<!-- Rules will be loaded here via AJAX -->
			</div>
			
			<!-- Pagination -->
			<div class="b2bking_dynamic_rules_pro_pagination" id="rules_pagination" style="display: none;">
				<!-- Items Per Page Control -->
				<div class="b2bking_dynamic_rules_pro_pagination_items_per_page">
					<label for="items_per_page_select"><?php esc_html_e('Items per page:', 'b2bking'); ?></label>
					<select id="items_per_page_select" class="b2bking_dynamic_rules_pro_items_per_page_select">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
					</select>
				</div>
				
				<!-- Pagination Controls (middle) -->
				<div class="b2bking_dynamic_rules_pro_pagination_controls">
					<button class="b2bking_dynamic_rules_pro_pagination_btn" id="pagination_first" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
							<path d="M18.41 7.41L17 6L11 12L17 18L18.41 16.59L13.83 12L18.41 7.41Z" fill="currentColor"/>
							<path d="M13 6L7 12L13 18L14.41 16.59L9.83 12L14.41 7.41L13 6Z" fill="currentColor"/>
						</svg>
					</button>
					<button class="b2bking_dynamic_rules_pro_pagination_btn" id="pagination_prev" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
							<path d="M15.41 7.41L14 6L8 12L14 18L15.41 16.59L10.83 12L15.41 7.41Z" fill="currentColor"/>
						</svg>
					</button>
					<div class="b2bking_dynamic_rules_pro_pagination_pages" id="pagination_pages">
						<!-- Page numbers will be generated here -->
					</div>
					<button class="b2bking_dynamic_rules_pro_pagination_btn" id="pagination_next" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
							<path d="M8.59 16.59L10 18L16 12L10 6L8.59 7.41L13.17 12L8.59 16.59Z" fill="currentColor"/>
						</svg>
					</button>
					<button class="b2bking_dynamic_rules_pro_pagination_btn" id="pagination_last" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
							<path d="M5.59 7.41L7 6L13 12L7 18L5.59 16.59L10.17 12L5.59 7.41Z" fill="currentColor"/>
							<path d="M11 6L17 12L11 18L9.59 16.59L14.17 12L9.59 7.41L11 6Z" fill="currentColor"/>
						</svg>
					</button>
				</div>
				
				<!-- Pagination Info (right) -->
				<div class="b2bking_dynamic_rules_pro_pagination_info">
					<span class="b2bking_dynamic_rules_pro_pagination_text"><?php
					/* translators: Pagination text. %1$s: start number, %2$s: end number, %3$s: total number */
					printf(
						esc_html__('Showing %1$s to %2$s of %3$s rules', 'b2bking'),
						'<span id="pagination_start">0</span>',
						'<span id="pagination_end">0</span>',
						'<span id="pagination_total">0</span>'
					);
					?></span>
				</div>
			</div>
			
			<!-- Loading State -->
			<div class="b2bking_rulespro_loading" id="rules_loading" style="display: none;">
				<div class="b2bking_rulespro_loading_spinner"></div>
				<p><?php esc_html_e('Loading rules...', 'b2bking'); ?></p>
			</div>
			
			<!-- Empty State -->
			<div class="b2bking_rulespro_empty" id="rules_empty" style="display: none;">
				<svg class="b2bking_rulespro_empty_icon" width="64" height="64" viewBox="0 0 64 64" fill="none">
					<path d="M32 0C14.327 0 0 14.327 0 32s14.327 32 32 32 32-14.327 32-32S49.673 0 32 0zm0 56C18.745 56 8 45.255 8 32S18.745 8 32 8s24 10.745 24 24-10.745 24-24 24z" fill="#e5e7eb"/>
					<path d="M32 16c-8.836 0-16 7.164-16 16s7.164 16 16 16 16-7.164 16-16-7.164-16-16-16zm0 24c-4.418 0-8-3.582-8-8s3.582-8 8-8 8 3.582 8 8-3.582 8-8 8z" fill="#e5e7eb"/>
				</svg>
				<h3><?php esc_html_e('No dynamic rules found', 'b2bking'); ?></h3>
				<p><?php esc_html_e('Create your first dynamic rule to set up pricing, discounts, and order restrictions for your customers.', 'b2bking'); ?></p>
				<a href="<?php echo admin_url('admin.php?page=b2bking_dynamic_rule_pro_editor'); ?>" class="b2bking_rulespro_empty_action_btn">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M8 1v14M1 8h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
					<?php esc_html_e('Create First Rule', 'b2bking'); ?>
				</a>
			</div>
		</div>
	</div>
</div>
