<?php
// Use the original B2BKing header bar function
if (class_exists('B2bking_Admin')) {
    echo B2bking_Admin::get_header_bar();
}

// Get log data
$log = B2BKing_Group_Rules_Log::get_instance();
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get logs with pagination and search
if (!empty($search)) {
    $result = $log->search_logs($search, $page, $per_page);
} else {
    $result = $log->get_logs($page, $per_page);
}
$logs = $result['logs'];
$total = $result['total'];
$total_pages = ceil($total / $per_page);
?>
<div id="b2bking_group_rules_pro_log_main_container">
			<!-- Simple Header -->
			<div class="b2bking_group_rules_pro_log_header">
				<div class="b2bking_group_rules_pro_log_header_title">
					<h1><?php esc_html_e('Group Rules Log', 'b2bking'); ?></h1>
					<p class="b2bking_group_rules_pro_log_subtitle"><?php esc_html_e('Track automatic customer group changes made by your group rules.', 'b2bking'); ?></p>
				</div>
				<div class="b2bking_group_rules_pro_log_header_actions">
					<form method="get" class="b2bking_group_rules_pro_log_search_form">
						<input type="hidden" name="page" value="b2bking_group_rules_pro_log">
						<input type="text" name="search" value="<?php echo esc_attr($search); ?>" 
							   placeholder="<?php esc_attr_e('Search by customer or rule...', 'b2bking'); ?>" 
							   class="b2bking_group_rules_pro_log_search_input">
						<button type="submit" class="b2bking_group_rules_pro_log_search_btn">
							<?php esc_html_e('Search', 'b2bking'); ?>
						</button>
						<?php if (!empty($search)) : ?>
							<a href="<?php echo admin_url('admin.php?page=b2bking_group_rules_pro_log'); ?>" class="b2bking_group_rules_pro_log_clear_btn">
								<?php esc_html_e('Clear', 'b2bking'); ?>
							</a>
						<?php endif; ?>
					</form>
					<a href="<?php echo admin_url('admin.php?page=b2bking_group_rules_pro'); ?>" class="b2bking_group_rules_pro_log_back_btn">
						<?php esc_html_e('Back to Rules', 'b2bking'); ?>
					</a>
				</div>
			</div>

			<!-- Main Content -->
			<div class="b2bking_group_rules_pro_log_content">
				<!-- Simple Table -->
				<table class="b2bking_group_rules_pro_log_table">
					<thead>
						<tr>
							<th><?php esc_html_e('Date', 'b2bking'); ?></th>
							<th><?php esc_html_e('Customer', 'b2bking'); ?></th>
							<th><?php esc_html_e('Rule', 'b2bking'); ?></th>
							<th><?php esc_html_e('Group Change', 'b2bking'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if (!empty($logs)) : ?>
							<?php foreach ($logs as $log_entry) : ?>
								<tr>
									<td><?php echo esc_html(date('M j, Y g:i A', strtotime($log_entry->date_created))); ?></td>
									<td>
										<?php if ($log_entry->user_id) : ?>
											<a href="<?php echo admin_url('user-edit.php?user_id=' . $log_entry->user_id); ?>" target="_blank">
												<?php echo esc_html($log_entry->display_name ?: 'User #' . $log_entry->user_id); ?>
											</a>
											<?php if ($log_entry->user_email) : ?>
												<br><small><?php echo esc_html($log_entry->user_email); ?></small>
											<?php endif; ?>
										<?php else : ?>
											<span class="b2bking_group_rules_pro_log_no_user"><?php esc_html_e('Unknown User', 'b2bking'); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ($log_entry->rule_id && $log_entry->rule_name) : ?>
											<a href="<?php echo admin_url('admin.php?page=b2bking_group_rule_pro_editor&rule_id=' . $log_entry->rule_id); ?>" 
											   class="b2bking_group_rules_pro_log_rule_link" 
											   data-rule-id="<?php echo esc_attr($log_entry->rule_id); ?>">
												<?php echo esc_html($log_entry->rule_name); ?>
											</a>
										<?php else : ?>
											<span class="b2bking_group_rules_pro_log_no_rule"><?php esc_html_e('Unknown Rule', 'b2bking'); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ($log_entry->old_group_name) : ?>
											<span class="b2bking_group_rules_pro_log_group_from"><?php echo esc_html($log_entry->old_group_name); ?></span> → 
										<?php endif; ?>
										<span class="b2bking_group_rules_pro_log_group_to"><?php echo esc_html($log_entry->new_group_name ?: 'Group #' . $log_entry->new_group_id); ?></span>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="4" class="b2bking_group_rules_pro_log_empty">
									<div class="b2bking_group_rules_pro_log_empty_content">
										<p><?php esc_html_e('No group rule changes have been logged yet.', 'b2bking'); ?></p>
										<p><small><?php esc_html_e('Group changes will appear here when rules are triggered.', 'b2bking'); ?></small></p>
									</div>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<!-- Pagination -->
				<?php if ($total > 0) : ?>
					<div class="b2bking_group_rules_pro_log_pagination">
						<?php
						$start = (($page - 1) * $per_page) + 1;
						$end = min($page * $per_page, $total);
						?>
						<span class="b2bking_group_rules_pro_log_pagination_info">
							<?php printf(esc_html__('Showing %d-%d of %d entries', 'b2bking'), $start, $end, $total); ?>
						</span>
						<div class="b2bking_group_rules_pro_log_pagination_controls">
							<?php 
							$prev_url = admin_url('admin.php?page=b2bking_group_rules_pro_log&paged=' . ($page - 1));
							$next_url = admin_url('admin.php?page=b2bking_group_rules_pro_log&paged=' . ($page + 1));
							if (!empty($search)) {
								$prev_url .= '&search=' . urlencode($search);
								$next_url .= '&search=' . urlencode($search);
							}
							?>
							<?php if ($page > 1) : ?>
								<a href="<?php echo $prev_url; ?>" class="b2bking_group_rules_pro_log_pagination_btn">
									<?php esc_html_e('Previous', 'b2bking'); ?>
								</a>
							<?php else : ?>
								<span class="b2bking_group_rules_pro_log_pagination_btn" disabled><?php esc_html_e('Previous', 'b2bking'); ?></span>
							<?php endif; ?>
							
							<?php if ($page < $total_pages) : ?>
								<a href="<?php echo $next_url; ?>" class="b2bking_group_rules_pro_log_pagination_btn">
									<?php esc_html_e('Next', 'b2bking'); ?>
								</a>
							<?php else : ?>
								<span class="b2bking_group_rules_pro_log_pagination_btn" disabled><?php esc_html_e('Next', 'b2bking'); ?></span>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Handle rule link clicks
			$(document).on('click', '.b2bking_group_rules_pro_log_rule_link', function(e) {
				// Only use AJAX if enabled
				if (typeof b2bking !== 'undefined' && b2bking.ajax_pages_load === 'enabled') {
					e.preventDefault();
					var ruleId = $(this).data('rule-id');
					if (ruleId && typeof page_switch === 'function') {
						page_switch('group_rule_pro_editor', 0, ruleId);
					}
				}
				// If AJAX is disabled, let the default href behavior work
			});
		});
		</script>
