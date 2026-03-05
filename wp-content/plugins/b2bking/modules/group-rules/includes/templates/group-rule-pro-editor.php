<?php
// Use the original B2BKing header bar function
if (class_exists('B2bking_Admin')) {
    echo B2bking_Admin::get_header_bar();
}

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
			// Get the raw condition value
			$raw_condition = get_post_meta($rule_id, 'b2bking_rule_applies', true);
			
			// Map old condition values to new ones for backward compatibility
			$condition_mapping = array(
				'order_value_total' => 'total_spent',
				'order_value_yearly_higher' => 'spent_yearly',
				'order_value_yearly_lower' => 'spent_yearly',
				'order_value_monthly_higher' => 'spent_monthly',
				'order_value_monthly_lower' => 'spent_monthly',
				'order_value_quarterly_higher' => 'spent_quarterly',
				'order_value_quarterly_lower' => 'spent_quarterly',
				'order_value_current_year_higher' => 'spent_current_year',
				'order_value_current_year_lower' => 'spent_current_year',
				'order_value_current_month_higher' => 'spent_current_month',
				'order_value_current_month_lower' => 'spent_current_month',
				'order_value_current_quarter_higher' => 'spent_current_quarter',
				'order_value_current_quarter_lower' => 'spent_current_quarter',
				'order_value_rolling_higher' => 'spent_rolling',
				'order_value_rolling_lower' => 'spent_rolling',
				// Order count conditions: keep as-is (they're already in new format)
				'order_count_total' => 'order_count_total',
				'order_count_yearly' => 'order_count_yearly',
				'order_count_monthly' => 'order_count_monthly',
				'order_count_quarterly' => 'order_count_quarterly',
				'order_count_current_year' => 'order_count_current_year',
				'order_count_current_month' => 'order_count_current_month',
				'order_count_current_quarter' => 'order_count_current_quarter',
				'order_count_rolling' => 'order_count_rolling',
				// Days conditions: keep as-is (they're already in new format)
				'days_since_first_order' => 'days_since_first_order',
				'days_since_last_order' => 'days_since_last_order',
			);
			
			$mapped_condition = isset($condition_mapping[$raw_condition]) ? $condition_mapping[$raw_condition] : $raw_condition;
			
			// Infer operator from old condition names if not explicitly set
			$inferred_operator = 'greater'; // default
			if (strpos($raw_condition, '_higher') !== false) {
				$inferred_operator = 'greater';
			} elseif (strpos($raw_condition, '_lower') !== false) {
				$inferred_operator = 'less';
			}
			
			// Try new meta keys first, then fall back to old meta keys for backward compatibility
			$rule_data = array(
				'name' => get_post_meta($rule_id, 'b2bking_rule_name', true) ?: get_post($rule_id)->post_title,
				'applies' => $mapped_condition,
				'operator' => get_post_meta($rule_id, 'b2bking_rule_operator', true) ?: $inferred_operator,
				'threshold' => get_post_meta($rule_id, 'b2bking_rule_threshold', true) ?: get_post_meta($rule_id, 'b2bking_rule_howmuch', true),
				'threshold_min' => get_post_meta($rule_id, 'b2bking_rule_threshold_min', true),
				'threshold_max' => get_post_meta($rule_id, 'b2bking_rule_threshold_max', true),
				'rolling_days' => get_post_meta($rule_id, 'b2bking_rule_rolling_days', true) ?: '90',
				'source_groups' => get_post_meta($rule_id, 'b2bking_rule_source_groups', true) ?: get_post_meta($rule_id, 'b2bking_rule_agents_who', true) ?: 'all_groups',
				'target_group' => get_post_meta($rule_id, 'b2bking_rule_target_group', true) ?: get_post_meta($rule_id, 'b2bking_rule_who', true),
			);
		}
		?>
		<div id="b2bking_group_rule_pro_editor_main_container">
			<!-- Main Content -->
			<div class="b2bking-rule-builder">
				<!-- Header Section -->
				<div class="b2bking_group_rule_pro_editor_header">
					<div class="b2bking_group_rule_pro_editor_header_title">
						<div class="b2bking_group_rule_pro_editor_header_text">
							<h1><?php echo $is_editing ? esc_html__('Edit Group Rule', 'b2bking') : esc_html__('Create New Group Rule', 'b2bking'); ?></h1>
						</div>
					</div>
					<div class="b2bking_group_rule_pro_editor_header_actions">
						<a href="<?php echo admin_url('admin.php?page=b2bking_group_rules_pro'); ?>" class="b2bking_group_rule_pro_editor_back_btn">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
								<path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<?php esc_html_e('Back to Rules', 'b2bking'); ?>
						</a>
						<button type="submit" id="b2bking_group_rule_pro_editor_save_top" class="btn-primary">
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
				<form id="b2bking_group_rule_pro_editor_form">
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
								   id="b2bking_rule_name" 
								   name="b2bking_rule_name"
								   class="b2bking-rule-name-input" 
								   placeholder="<?php esc_attr_e('e.g., VIP Customer Upgrade', 'b2bking'); ?>"
								   value="<?php echo esc_attr($rule_data['name'] ?? ''); ?>" 
								   required>
						</div>
					</div>

					<!-- Card 2: Apply To -->
					<div class="b2bking-card">
						<div class="card-header">
							<span class="card-number">2</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Apply To', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Choose which customers this rule should apply to', 'b2bking'); ?></p>
							</div>
							<span class="card-summary" id="source-summary"><?php esc_html_e('All Groups', 'b2bking'); ?></span>
						</div>
						<div class="card-body">
							<div class="source-options">
						<label class="source-option selected">
							<input type="radio" name="b2bking_rule_source_groups" value="all_groups" <?php checked($rule_data['source_groups'] ?? '', 'all_groups'); ?> required>
									<div class="option-content">
										<strong><?php esc_html_e('All Groups', 'b2bking'); ?></strong>
										<span><?php esc_html_e('Apply to all customer groups', 'b2bking'); ?></span>
									</div>
								</label>
								<?php foreach ($customer_groups as $group): ?>
									<label class="source-option">
										<input type="radio" name="b2bking_rule_source_groups" value="group_<?php echo esc_attr($group->ID); ?>" <?php checked($rule_data['source_groups'] ?? '', 'group_' . $group->ID); ?>>
										<div class="option-content">
											<strong><?php echo esc_html($group->post_title); ?></strong>
											<span><?php esc_html_e('Apply only to this group', 'b2bking'); ?></span>
										</div>
									</label>
								<?php endforeach; ?>
						</div>
						<input type="text" id="b2bking_rule_source_groups_proxy" aria-label="<?php esc_attr_e('Source group', 'b2bking'); ?>" value="<?php echo esc_attr($rule_data['source_groups'] ?? ''); ?>" required style="width:0;height:0;border:0;padding:0;opacity:0;" />
						</div>
					</div>

					<!-- Card 3: Conditions -->
					<div class="b2bking-card expanded">
						<div class="card-header">
							<span class="card-number">3</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Set Conditions', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Set the criteria that will trigger this rule', 'b2bking'); ?></p>
							</div>
							<span class="card-summary" id="condition-summary"><?php esc_html_e('Amount spent in last 90 days > $5,000', 'b2bking'); ?></span>
						</div>
						<div class="card-body">
							<div class="condition-builder">
								<div class="condition-row">
									<div class="field-group metric">
										<label><?php esc_html_e('When customer\'s', 'b2bking'); ?></label>
										<select id="b2bking_rule_select_applies" name="b2bking_rule_select_applies" class="b2bking-select" required>
											<option value=""><?php esc_html_e('— Select Condition —', 'b2bking'); ?></option>
											<optgroup label="<?php esc_attr_e('Customer Lifetime', 'b2bking'); ?>">
												<option value="total_spent" <?php selected($rule_data['applies'] ?? '', 'total_spent'); ?>><?php esc_html_e('Total amount spent', 'b2bking'); ?></option>
												<option value="order_count_total" <?php selected($rule_data['applies'] ?? '', 'order_count_total'); ?>><?php esc_html_e('Total number of orders', 'b2bking'); ?></option>
												<option value="days_since_first_order" <?php selected($rule_data['applies'] ?? '', 'days_since_first_order'); ?>><?php esc_html_e('Days since first order', 'b2bking'); ?></option>
												<option value="days_since_last_order" <?php selected($rule_data['applies'] ?? '', 'days_since_last_order'); ?>><?php esc_html_e('Days since last order', 'b2bking'); ?></option>
											</optgroup>
											<optgroup label="<?php esc_attr_e('Rolling Period (Last X Days)', 'b2bking'); ?>">
												<option value="spent_rolling" <?php selected($rule_data['applies'] ?? '', 'spent_rolling'); ?>><?php esc_html_e('Amount spent', 'b2bking'); ?></option>
												<option value="order_count_rolling" <?php selected($rule_data['applies'] ?? '', 'order_count_rolling'); ?>><?php esc_html_e('Number of orders', 'b2bking'); ?></option>
											</optgroup>
											<optgroup label="<?php esc_attr_e('Previous Period (Completed)', 'b2bking'); ?>">
												<option value="spent_yearly" <?php selected($rule_data['applies'] ?? '', 'spent_yearly'); ?>><?php esc_html_e('Amount spent - Previous year', 'b2bking'); ?></option>
												<option value="spent_quarterly" <?php selected($rule_data['applies'] ?? '', 'spent_quarterly'); ?>><?php esc_html_e('Amount spent - Previous quarter', 'b2bking'); ?></option>
												<option value="spent_monthly" <?php selected($rule_data['applies'] ?? '', 'spent_monthly'); ?>><?php esc_html_e('Amount spent - Previous month', 'b2bking'); ?></option>
												<option value="order_count_yearly" <?php selected($rule_data['applies'] ?? '', 'order_count_yearly'); ?>><?php esc_html_e('Order count - Previous year', 'b2bking'); ?></option>
												<option value="order_count_quarterly" <?php selected($rule_data['applies'] ?? '', 'order_count_quarterly'); ?>><?php esc_html_e('Order count - Previous quarter', 'b2bking'); ?></option>
												<option value="order_count_monthly" <?php selected($rule_data['applies'] ?? '', 'order_count_monthly'); ?>><?php esc_html_e('Order count - Previous month', 'b2bking'); ?></option>
											</optgroup>
											<optgroup label="<?php esc_attr_e('Current Period (To-Date)', 'b2bking'); ?>">
												<option value="spent_current_year" <?php selected($rule_data['applies'] ?? '', 'spent_current_year'); ?>><?php esc_html_e('Amount spent - Year to date', 'b2bking'); ?></option>
												<option value="spent_current_quarter" <?php selected($rule_data['applies'] ?? '', 'spent_current_quarter'); ?>><?php esc_html_e('Amount spent - Quarter to date', 'b2bking'); ?></option>
												<option value="spent_current_month" <?php selected($rule_data['applies'] ?? '', 'spent_current_month'); ?>><?php esc_html_e('Amount spent - Month to date', 'b2bking'); ?></option>
												<option value="order_count_current_year" <?php selected($rule_data['applies'] ?? '', 'order_count_current_year'); ?>><?php esc_html_e('Order count - Year to date', 'b2bking'); ?></option>
												<option value="order_count_current_quarter" <?php selected($rule_data['applies'] ?? '', 'order_count_current_quarter'); ?>><?php esc_html_e('Order count - Quarter to date', 'b2bking'); ?></option>
												<option value="order_count_current_month" <?php selected($rule_data['applies'] ?? '', 'order_count_current_month'); ?>><?php esc_html_e('Order count - Month to date', 'b2bking'); ?></option>
											</optgroup>
										</select>
									</div>
									
									<div class="field-group period" id="rolling-period-group" style="display: none;">
										<label><?php esc_html_e('Period', 'b2bking'); ?><span class="field-suffix"><?php esc_html_e('days', 'b2bking'); ?></span></label>
										<input type="number" 
											   id="b2bking_rule_rolling_days" 
											   name="b2bking_rule_rolling_days"
											   value="<?php echo esc_attr($rule_data['rolling_days'] ?? '90'); ?>" 
											   class="b2bking-input-small" 
											   min="1" max="3650">
									</div>
									
									<div class="field-group operator">
										<label><?php esc_html_e('Is', 'b2bking'); ?></label>
										<select id="b2bking_rule_operator" name="b2bking_rule_operator" class="b2bking-select-small" required>
											<option value=""><?php esc_html_e('— Select —', 'b2bking'); ?></option>
											<option value="greater" <?php selected($rule_data['operator'] ?? '', 'greater'); ?>><?php esc_html_e('Greater than (>)', 'b2bking'); ?></option>
											<option value="greater_equal" <?php selected($rule_data['operator'] ?? '', 'greater_equal'); ?>><?php esc_html_e('Greater than or equal to (≥)', 'b2bking'); ?></option>
											<option value="less" <?php selected($rule_data['operator'] ?? '', 'less'); ?>><?php esc_html_e('Less than (<)', 'b2bking'); ?></option>
											<option value="less_equal" <?php selected($rule_data['operator'] ?? '', 'less_equal'); ?>><?php esc_html_e('Less than or equal to (≤)', 'b2bking'); ?></option>
											<option value="between" <?php selected($rule_data['operator'] ?? '', 'between'); ?>><?php esc_html_e('Between (inclusive)', 'b2bking'); ?></option>
										</select>
									</div>
									
									<div class="field-group value" id="single-value-group">
										<label><?php esc_html_e('Value', 'b2bking'); ?></label>
										<div class="input-with-prefix">
											<span class="prefix" id="value-prefix">$</span>
											<input type="number" 
												   id="b2bking_rule_threshold" 
												   name="b2bking_rule_threshold"
												   value="<?php echo esc_attr($rule_data['threshold'] ?? '5000'); ?>" 
												   class="b2bking-input-medium" 
												   step="0.01" 
												   required>
										</div>
									</div>
									
									<div class="field-group value" id="between-value-group" style="display: none;">
										<label><?php esc_html_e('Between', 'b2bking'); ?></label>
										<div class="range-inputs">
											<div class="input-with-prefix">
												<span class="prefix" id="min-value-prefix">$</span>
												<input type="number" 
													   id="b2bking_rule_threshold_min" 
													   name="b2bking_rule_threshold_min"
													   value="<?php echo esc_attr($rule_data['threshold_min'] ?? '1000'); ?>" 
													   class="b2bking-input-small" 
													   step="0.01">
											</div>
											<span class="range-separator"><?php esc_html_e('and', 'b2bking'); ?></span>
											<div class="input-with-prefix">
												<span class="prefix" id="max-value-prefix">$</span>
												<input type="number" 
													   id="b2bking_rule_threshold_max" 
													   name="b2bking_rule_threshold_max"
													   value="<?php echo esc_attr($rule_data['threshold_max'] ?? '5000'); ?>" 
													   class="b2bking-input-small" 
													   step="0.01">
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Card 4: Choose Action -->
					<div class="b2bking-card">
						<div class="card-header">
							<span class="card-number">4</span>
							<div class="card-title-section">
								<h3><?php esc_html_e('Move To', 'b2bking'); ?></h3>
								<p><?php esc_html_e('Select the destination group when conditions are met', 'b2bking'); ?></p>
							</div>
							<span class="card-summary" id="action-summary"><?php esc_html_e('Move to VIP Group', 'b2bking'); ?></span>
						</div>
						<div class="card-body">
							<div class="action-grid">
								<?php foreach ($customer_groups as $index => $group): ?>
									<div class="group-option <?php echo ($rule_data['target_group'] ?? '') === 'group_' . $group->ID ? 'selected' : ''; ?>" 
										 data-group="group_<?php echo esc_attr($group->ID); ?>">
										<div class="group-icon">
											<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z" fill="#906a1d"></path>
											</svg>
										</div>
										<div class="group-name"><?php echo esc_html($group->post_title); ?></div>
										<div class="group-count"><?php echo rand(5, 50); ?> <?php esc_html_e('customers', 'b2bking'); ?></div>
									</div>
								<?php endforeach; ?>
							</div>
						<div style="width:0;height:0;opacity:0;overflow:hidden;">
							<?php foreach ($customer_groups as $index => $group): ?>
								<input type="radio" name="b2bking_rule_target_group" value="group_<?php echo esc_attr($group->ID); ?>" <?php checked($rule_data['target_group'] ?? '', 'group_' . $group->ID); ?> required>
							<?php endforeach; ?>
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
								<button type="button" id="b2bking_group_rule_pro_editor_cancel" class="btn-cancel">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
										<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
									<?php esc_html_e('Cancel', 'b2bking'); ?>
								</button>
								<button type="submit" id="b2bking_group_rule_pro_editor_save" class="btn-primary">
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
						/* translators: Example preview text for group rule. %1$s: condition (e.g., "amount spent in last 90 days"), %2$s: operator and value (e.g., "greater than $5,000"), %3$s: target group (e.g., "VIP Group") */
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
		<div class="b2bking_group_rule_pro_editor_message" id="message_container" style="display: none;">
			<div class="b2bking_group_rule_pro_editor_message_content">
				<div class="b2bking_group_rule_pro_editor_message_icon"></div>
				<div class="b2bking_group_rule_pro_editor_message_text"></div>
				<button class="b2bking_group_rule_pro_editor_message_close">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
			</div>
		</div>
		</div>
		<?php
