<?php
// Use the original B2BKing header bar function
if (class_exists('B2bking_Admin')) {
    B2bking_Admin::get_header_bar();
}
?>
<div id="b2bking_group_rules_pro_main_container">
	<!-- Header Section -->
	<div class="b2bking_grulespro_header">
		<div class="b2bking_grulespro_header_title">
			<svg class="b2bking_grulespro_header_icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect width="32" height="32" rx="8" fill="#906a1d"/>
				<path d="M22 24L24 22M24 22L22 20M24 22H19M18.5 6.5C19.5 7 20.5 8 20.5 9.5C20.5 11 19.5 12 18.5 12.5M14 18H11C9.5 18 9 18 8.5 18.2C7.5 18.5 7 19 6.5 20C6 20.5 6 21 6 24M16 9.5C16 11.5 14.5 13 12.5 13C10.5 13 9 11.5 9 9.5C9 7.5 10.5 6 12.5 6C14.5 6 16 7.5 16 9.5Z" stroke="#191821" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
			<div class="b2bking_grulespro_header_text">
				<h1><?php esc_html_e('Group Rules', 'b2bking'); ?></h1>
				<p class="b2bking_grulespro_header_subtitle"><?php esc_html_e('Automatically promote or move customers between groups based on their purchase behavior and history', 'b2bking'); ?></p>
			</div>
		</div>
		<div class="b2bking_grulespro_header_actions">
			<button class="b2bking_grulespro_rules_log_btn">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M15.6111 1.5837C17.2678 1.34703 18.75 2.63255 18.75 4.30606V5.68256C19.9395 6.31131 20.75 7.56102 20.75 9.00004V19C20.75 21.0711 19.0711 22.75 17 22.75H7C4.92893 22.75 3.25 21.0711 3.25 19V5.00004C3.25 4.99074 3.25017 4.98148 3.2505 4.97227C3.25017 4.95788 3.25 4.94344 3.25 4.92897C3.25 4.02272 3.91638 3.25437 4.81353 3.12621L15.6111 1.5837ZM4.75 6.75004V19C4.75 20.2427 5.75736 21.25 7 21.25H17C18.2426 21.25 19.25 20.2427 19.25 19V9.00004C19.25 7.7574 18.2426 6.75004 17 6.75004H4.75ZM5.07107 5.25004H17.25V4.30606C17.25 3.54537 16.5763 2.96104 15.8232 3.06862L5.02566 4.61113C4.86749 4.63373 4.75 4.76919 4.75 4.92897C4.75 5.10629 4.89375 5.25004 5.07107 5.25004ZM7.25 12C7.25 11.5858 7.58579 11.25 8 11.25H16C16.4142 11.25 16.75 11.5858 16.75 12C16.75 12.4143 16.4142 12.75 16 12.75H8C7.58579 12.75 7.25 12.4143 7.25 12ZM7.25 15.5C7.25 15.0858 7.58579 14.75 8 14.75H13.5C13.9142 14.75 14.25 15.0858 14.25 15.5C14.25 15.9143 13.9142 16.25 13.5 16.25H8C7.58579 16.25 7.25 15.9143 7.25 15.5Z" fill="currentColor"/>
				</svg>
				<?php esc_html_e('Rules Log', 'b2bking'); ?>
			</button>
			<a href="<?php echo admin_url('admin.php?page=b2bking_group_rule_pro_editor'); ?>" class="b2bking_grulespro_add_rule_btn">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
					<path d="M8 1v14M1 8h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
				<?php esc_html_e('Add New Rule', 'b2bking'); ?>
			</a>
		</div>
	</div>

	<!-- Main Content -->
	<div class="b2bking_group_rules_pro_content">
		<?php
		// Get customer groups for filtering
		$customer_groups = get_posts([
			'post_type' => 'b2bking_group',
			'post_status' => 'publish',
			'numberposts' => -1,
		]);
		?>
		
		<!-- Filters Section -->
		<div class="b2bking_grulespro_filters">
			<div class="b2bking_grulespro_filters_row">
				<div class="b2bking_grulespro_search_container">
					<svg class="b2bking_grulespro_search_icon" width="15" height="15" viewBox="0 0 20 20" fill="none">
						<path d="M19 19L13 13M15 8A7 7 0 1 1 1 8A7 7 0 0 1 15 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<input type="text" id="rule_search" placeholder="<?php esc_attr_e('Search rules by name or condition...', 'b2bking'); ?>" class="b2bking_grulespro_search_input">
				</div>
				
				<div class="b2bking_grulespro_filter_group">
					<select id="source_group_filter" class="b2bking_grulespro_filter_select">
						<option value=""><?php esc_html_e('All Source Groups', 'b2bking'); ?></option>
						<?php foreach ($customer_groups as $group): ?>
							<option value="<?php echo esc_attr($group->ID); ?>"><?php echo esc_html($group->post_title); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div class="b2bking_grulespro_filter_group">
					<select id="target_group_filter" class="b2bking_grulespro_filter_select">
						<option value=""><?php esc_html_e('All Target Groups', 'b2bking'); ?></option>
						<?php foreach ($customer_groups as $group): ?>
							<option value="<?php echo esc_attr($group->ID); ?>"><?php echo esc_html($group->post_title); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div class="b2bking_grulespro_filter_group">
					<select id="condition_filter" class="b2bking_grulespro_filter_select">
						<option value=""><?php esc_html_e('All Conditions', 'b2bking'); ?></option>
						<optgroup label="<?php esc_attr_e('Customer Lifetime', 'b2bking'); ?>">
							<option value="total_spent"><?php esc_html_e('Total amount spent', 'b2bking'); ?></option>
							<option value="order_count_total"><?php esc_html_e('Total number of orders', 'b2bking'); ?></option>
							<option value="days_since_first_order"><?php esc_html_e('Days since first order', 'b2bking'); ?></option>
							<option value="days_since_last_order"><?php esc_html_e('Days since last order', 'b2bking'); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e('Rolling Period (Last X Days)', 'b2bking'); ?>">
							<option value="spent_rolling"><?php esc_html_e('Amount spent', 'b2bking'); ?></option>
							<option value="order_count_rolling"><?php esc_html_e('Number of orders', 'b2bking'); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e('Previous Period (Completed)', 'b2bking'); ?>">
							<option value="spent_yearly"><?php esc_html_e('Amount spent - Previous year', 'b2bking'); ?></option>
							<option value="spent_quarterly"><?php esc_html_e('Amount spent - Previous quarter', 'b2bking'); ?></option>
							<option value="spent_monthly"><?php esc_html_e('Amount spent - Previous month', 'b2bking'); ?></option>
							<option value="order_count_yearly"><?php esc_html_e('Order count - Previous year', 'b2bking'); ?></option>
							<option value="order_count_quarterly"><?php esc_html_e('Order count - Previous quarter', 'b2bking'); ?></option>
							<option value="order_count_monthly"><?php esc_html_e('Order count - Previous month', 'b2bking'); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e('Current Period (To-Date)', 'b2bking'); ?>">
							<option value="spent_current_year"><?php esc_html_e('Amount spent - Year to date', 'b2bking'); ?></option>
							<option value="spent_current_quarter"><?php esc_html_e('Amount spent - Quarter to date', 'b2bking'); ?></option>
							<option value="spent_current_month"><?php esc_html_e('Amount spent - Month to date', 'b2bking'); ?></option>
							<option value="order_count_current_year"><?php esc_html_e('Order count - Year to date', 'b2bking'); ?></option>
							<option value="order_count_current_quarter"><?php esc_html_e('Order count - Quarter to date', 'b2bking'); ?></option>
							<option value="order_count_current_month"><?php esc_html_e('Order count - Month to date', 'b2bking'); ?></option>
						</optgroup>
					</select>
				</div>
				
				<div class="b2bking_grulespro_filter_group">
					<select id="status_filter" class="b2bking_grulespro_filter_select">
						<option value=""><?php esc_html_e('All Status', 'b2bking'); ?></option>
						<option value="enabled"><?php esc_html_e('Enabled', 'b2bking'); ?></option>
						<option value="disabled"><?php esc_html_e('Disabled', 'b2bking'); ?></option>
					</select>
				</div>
				
				<button class="b2bking_grulespro_clear_filters_btn" id="clear_filters">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
					<?php esc_html_e('Clear', 'b2bking'); ?>
				</button>
			</div>
		</div>

		<!-- Rules Container -->
		<div class="b2bking_grulespro_rules_container">
			<div class="b2bking_grulespro_rules_header">
			<div class="b2bking_grulespro_rules_actions">
				<div class="b2bking_grulespro_left_actions">
					<button class="b2bking_grulespro_bulk_actions_btn" id="bulk_actions">
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
				<button class="b2bking_grulespro_filter_toggle_btn" id="filter_toggle" title="<?php esc_attr_e('Toggle Filters', 'b2bking'); ?>">
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
				<div class="b2bking_group_rules_pro_view_toggle">
					<button class="b2bking_group_rules_pro_view_btn active" data-view="grid" title="<?php esc_attr_e('Grid View', 'b2bking'); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
							<rect x="1" y="1" width="6" height="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="9" y="1" width="6" height="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="1" y="9" width="6" height="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="9" y="9" width="6" height="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
						</svg>
					</button>
					<button class="b2bking_group_rules_pro_view_btn" data-view="list" title="<?php esc_attr_e('List View', 'b2bking'); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
							<rect x="1" y="1" width="14" height="2" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="1" y="7" width="14" height="2" stroke="currentColor" stroke-width="1.5" fill="none"/>
							<rect x="1" y="13" width="14" height="2" stroke="currentColor" stroke-width="1.5" fill="none"/>
						</svg>
					</button>
				</div>
				
				<!-- Bulk Actions Toolbar (hidden by default) -->
					<div id="b2bking_group_rules_pro_bulk_toolbar" class="b2bking_group_rules_pro_bulk_toolbar" style="display: none;">
						<div class="b2bking_group_rules_pro_bulk_toolbar_content">
							<div class="b2bking_group_rules_pro_bulk_toolbar_left">
								<span class="b2bking_group_rules_pro_bulk_selected_count"><span class="b2bking_group_rules_pro_bulk_count_number">0</span> <?php esc_html_e('rules selected', 'b2bking'); ?></span>
								<button class="b2bking_group_rules_pro_bulk_select_all" id="select_all_rules"><?php esc_html_e('Select All', 'b2bking'); ?></button>
							</div>
							<div class="b2bking_group_rules_pro_bulk_toolbar_right">
								<button class="b2bking_group_rules_pro_bulk_action_btn b2bking_group_rules_pro_bulk_enable" id="bulk_enable_rules">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
										<path fill="currentColor" d="M17 7H7a5 5 0 1 0 0 10h10a5 5 0 1 0 0-10Zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"></path>
									</svg>
									<?php esc_html_e('Enable Selected', 'b2bking'); ?>
								</button>
								<button class="b2bking_group_rules_pro_bulk_action_btn b2bking_group_rules_pro_bulk_disable" id="bulk_disable_rules">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
										<path fill="currentColor" d="M17 6H7c-3.31 0-6 2.69-6 6s2.69 6 6 6h10c3.31 0 6-2.69 6-6s-2.69-6-6-6Zm0 10H7c-2.21 0-4-1.79-4-4s1.79-4 4-4h10c2.21 0 4 1.79 4 4s-1.79 4-4 4ZM7 9c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3Z"></path>
									</svg>
									<?php esc_html_e('Disable Selected', 'b2bking'); ?>
								</button>
								<button class="b2bking_group_rules_pro_bulk_action_btn b2bking_group_rules_pro_bulk_delete" id="bulk_delete_rules">
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
			<div class="b2bking_group_rules_pro_rules_grid active" id="rules_grid">
				<!-- Rules will be loaded here via AJAX -->
			</div>
			
			<!-- List View -->
			<div class="b2bking_group_rules_pro_rules_list" id="rules_list">
				<!-- Rules will be loaded here via AJAX -->
			</div>
			
			<!-- Pagination -->
			<div class="b2bking_group_rules_pro_pagination" id="rules_pagination" style="display: none;">
				<!-- Items Per Page Control -->
				<div class="b2bking_group_rules_pro_pagination_items_per_page">
					<label for="items_per_page_select"><?php esc_html_e('Items per page:', 'b2bking'); ?></label>
					<select id="items_per_page_select" class="b2bking_group_rules_pro_items_per_page_select">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
					</select>
				</div>
				
				<!-- Pagination Controls (middle) -->
				<div class="b2bking_group_rules_pro_pagination_controls">
					<button class="b2bking_group_rules_pro_pagination_btn" id="pagination_first" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
							<path d="M18.41 7.41L17 6L11 12L17 18L18.41 16.59L13.83 12L18.41 7.41Z" fill="currentColor"/>
							<path d="M13 6L7 12L13 18L14.41 16.59L9.83 12L14.41 7.41L13 6Z" fill="currentColor"/>
						</svg>
					</button>
					<button class="b2bking_group_rules_pro_pagination_btn" id="pagination_prev" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
							<path d="M15.41 7.41L14 6L8 12L14 18L15.41 16.59L10.83 12L15.41 7.41Z" fill="currentColor"/>
						</svg>
					</button>
					<div class="b2bking_group_rules_pro_pagination_pages" id="pagination_pages">
						<!-- Page numbers will be generated here -->
					</div>
					<button class="b2bking_group_rules_pro_pagination_btn" id="pagination_next" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
							<path d="M8.59 16.59L10 18L16 12L10 6L8.59 7.41L13.17 12L8.59 16.59Z" fill="currentColor"/>
						</svg>
					</button>
					<button class="b2bking_group_rules_pro_pagination_btn" id="pagination_last" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none">
							<path d="M5.59 7.41L7 6L13 12L7 18L5.59 16.59L10.17 12L5.59 7.41Z" fill="currentColor"/>
							<path d="M11 6L17 12L11 18L9.59 16.59L14.17 12L9.59 7.41L11 6Z" fill="currentColor"/>
						</svg>
					</button>
				</div>
				
				<!-- Pagination Info (right) -->
				<div class="b2bking_group_rules_pro_pagination_info">
					<span class="b2bking_group_rules_pro_pagination_text"><?php
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
			<div class="b2bking_grulespro_loading" id="rules_loading" style="display: none;">
				<div class="b2bking_grulespro_loading_spinner"></div>
				<p><?php esc_html_e('Loading rules...', 'b2bking'); ?></p>
			</div>
			
			<!-- Empty State -->
			<div class="b2bking_grulespro_empty" id="rules_empty" style="display: none;">
				<svg class="b2bking_grulespro_empty_icon" width="64" height="64" viewBox="0 0 64 64" fill="none">
					<path d="M32 0C14.327 0 0 14.327 0 32s14.327 32 32 32 32-14.327 32-32S49.673 0 32 0zm0 56C18.745 56 8 45.255 8 32S18.745 8 32 8s24 10.745 24 24-10.745 24-24 24z" fill="#e5e7eb"/>
					<path d="M32 16c-8.836 0-16 7.164-16 16s7.164 16 16 16 16-7.164 16-16-7.164-16-16-16zm0 24c-4.418 0-8-3.582-8-8s3.582-8 8-8 8 3.582 8 8-3.582 8-8 8z" fill="#e5e7eb"/>
				</svg>
				<h3><?php esc_html_e('No group rules found', 'b2bking'); ?></h3>
				<p><?php esc_html_e('Create your first group rule to automatically move customers between groups.', 'b2bking'); ?></p>
				<a href="<?php echo admin_url('admin.php?page=b2bking_group_rule_pro_editor'); ?>" class="b2bking_grulespro_empty_action_btn">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M8 1v14M1 8h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
					<?php esc_html_e('Create First Rule', 'b2bking'); ?>
				</a>
			</div>
		</div>
	</div>
</div>
