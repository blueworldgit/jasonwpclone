/**
 * B2BKing Group Rules Pro - Group Rules Management JavaScript
 */

(function($) {
    'use strict';

    class B2BKingGroupRulesPro {
        constructor() {
            this.rules = [];
            this.isLoading = false;
            this.searchTimeout = null;
            this.orderUpdateTimeout = null;
            this.hasRenderedBefore = false;
            this.loadingStartTime = null;
            this.minLoadingTime = 200; // Minimum time to show loading (in ms)
            
            // Pagination state
            this.currentPage = 1;
            this.itemsPerPage = 20;
            this.totalItems = 0;
            this.totalPages = 0;
            
            // Filter state
            this.filtersEnabled = false;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadSavedView();
            this.loadSavedFilterPreference();
            this.loadSavedItemsPerPage();
            
            // Always start with loading state and clear any server-side content
            this.initializeEmptyState();
            
            // Update filter indicators on page load
            this.updateFilterIndicators();
            
            // Set the initial items per page value in the dropdown
            $('#items_per_page_select').val(this.itemsPerPage);
            
            this.loadRules();
            
        }

        /**
         * Reinitialize the component after AJAX page load
         */
        reinitialize() {
            // Clear any existing state
            this.rules = [];
            this.currentPage = 1;
            this.totalItems = 0;
            this.totalPages = 0;
            this.isLoading = false;
            
            // Reinitialize the component
            this.initializeEmptyState();
            this.updateFilterIndicators();
            this.loadRules();
        }

        bindEvents() {
            // Remove any existing event listeners for this instance to prevent duplicates
            $(document).off('.b2bkingGroupRulesPro');
            
            // Search functionality (both old and new search inputs)
            $(document).on('input.b2bkingGroupRulesPro', '#rules_search, #rule_search', (e) => {
                this.debounceSearch($(e.target).val());
                this.updateFilterIndicators(); // Update visual indicators
            });

            // Filter functionality
            $(document).on('change.b2bkingGroupRulesPro', '#source_group_filter, #target_group_filter, #condition_filter, #status_filter', (e) => {
                this.currentPage = 1; // Reset to first page when filters change
                this.updateFilterIndicators(); // Update visual indicators
                this.loadRules();
            });

            // Items per page functionality
            $(document).on('change.b2bkingGroupRulesPro', '#items_per_page_select', (e) => {
                this.itemsPerPage = parseInt($(e.target).val());
                this.currentPage = 1; // Reset to first page when items per page changes
                this.saveItemsPerPage(); // Save to localStorage
                this.loadRules();
            });

            // Clear filters
            $(document).on('click.b2bkingGroupRulesPro', '#clear_filters', (e) => {
                e.preventDefault();
                this.clearFilters();
            });

            // Bulk actions functionality
            $(document).on('click.b2bkingGroupRulesPro', '#bulk_actions', (e) => {
                e.preventDefault();
                this.toggleBulkActions();
            });

            // Filter toggle functionality
            $(document).on('click.b2bkingGroupRulesPro', '#filter_toggle', (e) => {
                e.preventDefault();
                this.toggleFilters();
            });


            // Individual rule checkbox selection
            $(document).on('change.b2bkingGroupRulesPro', '.b2bking_group_rules_pro_rule_checkbox', (e) => {
                const checkbox = $(e.target);
                const card = checkbox.closest('.b2bking_group_rules_pro_rule_card');
                
                // Toggle selected class on the card
                if (checkbox.is(':checked')) {
                    card.addClass('selected');
                } else {
                    card.removeClass('selected');
                }
                
                this.updateBulkToolbar();
            });

            // Make the entire selection area clickable
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_group_rules_pro_rule_selection', (e) => {
                // Don't trigger if clicking directly on the checkbox (to avoid double-triggering)
                if (e.target.type === 'checkbox') {
                    return;
                }
                
                const selectionArea = $(e.currentTarget);
                const checkbox = selectionArea.find('.b2bking_group_rules_pro_rule_checkbox');
                const card = selectionArea.closest('.b2bking_group_rules_pro_rule_card');
                
                // Toggle the checkbox
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            });

            // Select all / Deselect all
            $(document).on('click.b2bkingGroupRulesPro', '#select_all_rules', (e) => {
                e.preventDefault();
                this.selectAllRules();
            });


            // Bulk action buttons
            $(document).on('click.b2bkingGroupRulesPro', '#bulk_enable_rules', (e) => {
                e.preventDefault();
                this.bulkEnableRules();
            });

            $(document).on('click.b2bkingGroupRulesPro', '#bulk_disable_rules', (e) => {
                e.preventDefault();
                this.bulkDisableRules();
            });

            $(document).on('click.b2bkingGroupRulesPro', '#bulk_delete_rules', (e) => {
                e.preventDefault();
                this.bulkDeleteRules();
            });

            // View toggle functionality
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_group_rules_pro_view_btn', (e) => {
                e.preventDefault();
                const view = $(e.currentTarget).data('view');
                this.switchView(view);
            });

            // Testing buttons (temporary)
            $(document).on('click.b2bkingGroupRulesPro', '#create_test_rules', (e) => {
                e.preventDefault();
                this.createTestRules();
            });

            $(document).on('click.b2bkingGroupRulesPro', '#delete_test_rules', (e) => {
                e.preventDefault();
                this.deleteTestRules();
            });

            // Grid view toggle - button with data attributes
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_group_rules_pro_rule_toggle:not(.list_view)', (e) => {
                e.preventDefault();
                const ruleId = $(e.currentTarget).data('rule-id');
                // More robust enabled state checking - handle both string and boolean values
                const enabledData = $(e.currentTarget).data('enabled');
                const enabled = enabledData === '1' || enabledData === 1 || enabledData === true;
                this.toggleRuleStatus(ruleId, !enabled);
            });

            // List view toggle - click anywhere on the toggle button
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_group_rules_pro_rule_toggle.list_view', (e) => {
                // Check if the click is directly on the toggle button, not on the inner toggle switch
                if ($(e.target).hasClass('b2bking_group_rules_pro_rule_toggle_slider') || 
                    $(e.target).closest('.b2bking_group_rules_pro_rule_toggle_slider').length > 0) {
                    return; // Let the inner toggle switch handle the click
                }
                
                e.preventDefault();
                // Find the checkbox input within this toggle button
                const checkbox = $(e.currentTarget).find('input[type="checkbox"]');
                if (checkbox.length === 0) {
                    return; // No checkbox found, skip
                }
                
                const ruleId = checkbox.data('rule-id');
                if (!ruleId) {
                    return; // No rule ID found, skip
                }
                
                // Get current state from rule card's enabled class (same pattern as grid view uses data-enabled)
                const ruleCard = $(e.currentTarget).closest('.b2bking_group_rules_pro_rule_card');
                const currentState = ruleCard.hasClass('enabled');
                
                // Directly call toggleRuleStatus like grid view does
                this.toggleRuleStatus(ruleId, !currentState);
            });

            // List view toggle switch - checkbox input
            $(document).on('change.b2bkingGroupRulesPro', '.b2bking_group_rules_pro_rule_toggle_switch input[type="checkbox"]', (e) => {
                const ruleId = $(e.currentTarget).data('rule-id');
                const isEnabled = $(e.currentTarget).is(':checked');
                
                if (!ruleId) {
                    console.error('No rule ID found on checkbox');
                    return;
                }
                
                this.toggleRuleStatus(ruleId, isEnabled);
            });

            // Alternative list view handler - click on slider
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_group_rules_pro_rule_toggle_slider', (e) => {
                const checkbox = $(e.currentTarget).siblings('input[type="checkbox"]');
                const ruleId = checkbox.data('rule-id');
                const currentState = checkbox.is(':checked');
                const newState = !currentState;
                
                if (!ruleId) {
                    console.error('No rule ID found on slider checkbox');
                    return;
                }
                
                // Manually toggle checkbox and trigger change
                checkbox.prop('checked', newState).trigger('change');
            });

            // Rule delete (both grid and list view)
            $(document).on('click', '.b2bking_group_rules_pro_rule_delete, .b2bking_group_rules_pro_rule_action_delete', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation(); // Prevent other handlers from firing
                const ruleId = $(e.currentTarget).data('rule-id');
                this.deleteRule(ruleId);
            });

            // Title click handlers - trigger edit button
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_group_rules_pro_rule_name.list_view, .b2bking_group_rules_pro_rule_title_element', (e) => {
                e.preventDefault();
                const ruleId = $(e.currentTarget).data('rule-id');
                if (ruleId) {
                    // Find the edit button in the same card and trigger it
                    const card = $(e.currentTarget).closest('.b2bking_group_rules_pro_rule_card');
                    const editButton = card.find('.b2bking_group_rules_pro_rule_edit, .b2bking_group_rules_pro_rule_action_edit');
                    if (editButton.length > 0) {
                        editButton[0].click();
                    }
                }
            });

            // Create first rule button
            $(document).on('click', '#create_first_rule_btn', (e) => {
                // Link will handle navigation
            });

            // Rules Log button
            $(document).on('click', '.b2bking_grulespro_rules_log_btn', (e) => {
                if (b2bking.ajax_pages_load !== 'enabled'){
                    e.preventDefault();
                    window.location.href = b2bking_group_rules_pro.log_page_url;
                }
            });


            // Message close
            $(document).on('click', '.b2bking_group_rules_pro_message_close', (e) => {
                e.preventDefault();
                this.hideMessage();
            });

            // Pagination event handlers
            $(document).on('click', '#pagination_first', (e) => {
                e.preventDefault();
                this.goToPage(1);
            });

            $(document).on('click', '#pagination_prev', (e) => {
                e.preventDefault();
                this.goToPage(this.currentPage - 1);
            });

            $(document).on('click', '#pagination_next', (e) => {
                e.preventDefault();
                this.goToPage(this.currentPage + 1);
            });

            $(document).on('click', '#pagination_last', (e) => {
                e.preventDefault();
                this.goToPage(this.totalPages);
            });

            $(document).on('click', '.b2bking_group_rules_pro_pagination_page', (e) => {
                e.preventDefault();
                const page = parseInt($(e.currentTarget).data('page'));
                this.goToPage(page);
            });
        }

        toggleRuleStatus(ruleId, enabled) {
            if (this.isLoading) return;

            // Update visual state immediately
            this.updateRuleVisualState(ruleId, enabled);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2bking_group_rules_pro_toggle_status',
                    nonce: b2bking_group_rules_pro.nonce,
                    rule_id: ruleId,
                    enabled: enabled ? '1' : '0'
                },
                success: (response) => {
                    if (response.success) {
                        const statusText = enabled ? b2bking.grpro_enabled : b2bking.grpro_disabled;
                        this.showMessage('success', response.data.message || b2bking.grpro_rule_status_updated.replace('%s', statusText));
                        // Update the rules data
                        const rule = this.rules.find(r => r.id == ruleId);
                        if (rule) {
                            rule.enabled = enabled;
                        }
                    } else {
                        this.showMessage('error', response.data || b2bking.grpro_failed_to_update_rule_status);
                        // Revert visual state on failure
                        this.updateRuleVisualState(ruleId, !enabled);
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage('error', b2bking.grpro_error_updating_rule_status);
                    console.error('AJAX Error:', error);
                    // Revert visual state on error
                    this.updateRuleVisualState(ruleId, !enabled);
                }
            });
        }

        updateRuleVisualState(ruleId, enabled) {
            // Update rule card enabled class
            const ruleCard = $(`.b2bking_group_rules_pro_rule_card[data-rule-id="${ruleId}"]`);
            if (ruleCard.length) {
                if (enabled) {
                    ruleCard.addClass('enabled');
                } else {
                    ruleCard.removeClass('enabled');
                }
            }

            // Update grid view toggle
            const gridToggle = $(`.b2bking_group_rules_pro_rule_toggle[data-rule-id="${ruleId}"]:not(.list_view)`);
            if (gridToggle.length) {
                gridToggle.data('enabled', enabled ? '1' : '0');
                const toggleSwitch = gridToggle.find('.b2bking_group_rules_pro_toggle_switch');
                if (enabled) {
                    toggleSwitch.addClass('active');
                } else {
                    toggleSwitch.removeClass('active');
                }
            }

            // Update list view checkbox
            const listCheckbox = $(`.b2bking_group_rules_pro_rule_toggle_switch input[data-rule-id="${ruleId}"]`);
            if (listCheckbox.length) {
                listCheckbox.prop('checked', enabled);
            }

        }

        updateMultipleRulesVisualState(ruleIds, enabled) {
            // Update visual state for multiple rules at once
            ruleIds.forEach(ruleId => {
                this.updateRuleVisualState(ruleId, enabled);
            });
        }

        updateMultipleRulesData(ruleIds, enabled) {
            // Update the rules data array for multiple rules at once
            ruleIds.forEach(ruleId => {
                const rule = this.rules.find(r => r.id == ruleId);
                if (rule) {
                    rule.enabled = enabled;
                }
            });
        }

        initializeEmptyState() {
            // Hide all content containers and grid elements
            $('.b2bking_group_rules_pro_content_container').removeClass('show').hide();
            $('#rules_grid').hide();
            $('#rules_list').hide();
            $('#rules_empty').hide();
            $('#rules_pagination').hide(); // Ensure pagination is hidden during initialization
            
            // Clear any server-side rendered content
            $('#rules_grid').empty();
            $('#rules_list').empty();
            
            // Reset bulk actions selection during initialization
            this.resetBulkActionsSelection();
            
            // Show loading state immediately
            this.showLoading();
        }

        verifyVisualStates() {
            // Verify that all visual states match the data after rendering
            this.rules.forEach(rule => {
                const ruleId = rule.id;
                const enabled = rule.enabled;
                
                // Check grid view toggle state
                const gridToggle = $(`.b2bking_group_rules_pro_rule_toggle[data-rule-id="${ruleId}"]:not(.list_view)`);
                if (gridToggle.length) {
                    const dataEnabled = gridToggle.data('enabled');
                    const expectedDataEnabled = enabled ? 1 : 0;
                    
                    // Fix data attribute if it doesn't match
                    if (dataEnabled !== expectedDataEnabled) {
                      //  console.warn(`Rule ${ruleId}: Fixed data-enabled mismatch. Was: ${dataEnabled}, Should be: ${expectedDataEnabled}`);
                        gridToggle.data('enabled', expectedDataEnabled);
                    }
                    
                    // Fix toggle switch visual state
                    const toggleSwitch = gridToggle.find('.b2bking_group_rules_pro_toggle_switch');
                    const hasActiveClass = toggleSwitch.hasClass('active');
                    if (enabled && !hasActiveClass) {
                        toggleSwitch.addClass('active');
                    } else if (!enabled && hasActiveClass) {
                        toggleSwitch.removeClass('active');
                    }
                }
                
                // Check list view checkbox state
                const listCheckbox = $(`.b2bking_group_rules_pro_rule_toggle_switch input[data-rule-id="${ruleId}"]`);
                if (listCheckbox.length) {
                    const isChecked = listCheckbox.is(':checked');
                    if (enabled !== isChecked) {
                      //  console.warn(`Rule ${ruleId}: Fixed checkbox state mismatch. Was: ${isChecked}, Should be: ${enabled}`);
                        listCheckbox.prop('checked', enabled);
                    }
                }
                
            });
        }

        deleteRule(ruleId) {
            if (!confirm(b2bking.grpro_confirm_delete_rule)) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2bking_group_rules_pro_delete_rule',
                    nonce: b2bking_group_rules_pro.nonce,
                    rule_id: ruleId
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('success', response.data.message || b2bking.grpro_rule_deleted_successfully);
                        
                        // After individual rule delete, check if current page will be empty
                        // If we're deleting the last rule on current page and we're not on page 1,
                        // reset to page 1 to avoid showing empty state when other pages exist
                        const rulesOnCurrentPage = this.rules.length;
                        
                        if (rulesOnCurrentPage <= 1 && this.currentPage > 1) {
                            this.currentPage = 1;
                        }
                        
                        this.loadRules();
                    } else {
                        this.showMessage('error', response.data || b2bking.grpro_failed_to_delete_rule);
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage('error', b2bking.grpro_error_deleting_rule);
                    console.error('AJAX Error:', error);
                }
            });
        }

        loadRules() {
            if (this.isLoading) return;

            this.isLoading = true;
            
            // Only show loading if not already shown (to avoid double loading states)
            if (!$('#rules_loading').is(':visible')) {
                this.showLoading();
            }
            
            // Get search term from either search input
            const searchTerm = $('#rules_search').val() || $('#rule_search').val() || '';
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2bking_group_rules_pro_load_rules',
                    nonce: b2bking_group_rules_pro.nonce,
                    search: searchTerm,
                    source_group_filter: $('#source_group_filter').val() || '',
                    target_group_filter: $('#target_group_filter').val() || '',
                    condition_filter: $('#condition_filter').val() || '',
                    status_filter: $('#status_filter').val() || '',
                    page: this.currentPage,
                    per_page: this.itemsPerPage
                },
                success: (response) => {
                    if (response.success) {
                        this.rules = response.data.rules || [];
                        this.totalItems = response.data.total_items || 0;
                        this.totalPages = response.data.total_pages || 0;
                        this.currentPage = response.data.current_page || 1;
                        this.renderRules();
                        this.renderPagination();
                        this.hideLoading();
                    } else {
                        this.hideLoading();
                        this.showMessage('error', b2bking.grpro_failed_to_load_rules);
                    }
                },
                error: (xhr, status, error) => {
                    this.hideLoading();
                    this.showMessage('error', b2bking.grpro_error_loading_rules);
                    console.error('AJAX Error:', error);
                },
                complete: () => {
                    this.isLoading = false;
                }
            });
        }

        clearFilters() {
            // Clear all filter inputs
            $('#rule_search').val('');
            $('#rules_search').val('');
            $('#source_group_filter').val('');
            $('#target_group_filter').val('');
            $('#condition_filter').val('');
            $('#status_filter').val('');
            
            // Reset pagination to first page
            this.currentPage = 1;
            
            // Update filter indicators and clear button state
            this.updateFilterIndicators();
            
            // Reload rules
            this.loadRules();
        }

        updateFilterVisibility() {
            const $filtersContainer = $('.b2bking_grulespro_filters');
            const $toggleButton = $('#filter_toggle');
            
            // Always allow user to toggle filters, regardless of rule count
            $toggleButton.prop('disabled', false);
            
            // Show filters if user has toggled them on
            if (this.filtersEnabled) {
                $filtersContainer.addClass('show-filters');
                $toggleButton.addClass('active');
            } else {
                $filtersContainer.removeClass('show-filters');
                $toggleButton.removeClass('active');
            }
        }

        toggleFilters() {
            const $filtersContainer = $('.b2bking_grulespro_filters');
            const $toggleButton = $('#filter_toggle');
            
            // Add transition class only when user clicks (not on initial load)
            $filtersContainer.addClass('has-transition');
            
            this.filtersEnabled = !this.filtersEnabled;
            
            if (this.filtersEnabled) {
                $filtersContainer.addClass('show-filters');
                $toggleButton.addClass('active');
            } else {
                $filtersContainer.removeClass('show-filters');
                $toggleButton.removeClass('active');
            }
            
            // Save preference to localStorage
            localStorage.setItem('b2bking_group_rules_pro_filters_enabled', this.filtersEnabled);
        }

        updateFilterIndicators() {
            let hasActiveFilters = false;

            // Check search input
            const searchValue = $('#rule_search').val() || $('#rules_search').val() || '';
            const $searchContainer = $('.b2bking_grulespro_search_container');
            if (searchValue.trim() !== '') {
                $searchContainer.addClass('filter-active');
                hasActiveFilters = true;
            } else {
                $searchContainer.removeClass('filter-active');
            }

            // Check filter dropdowns
            const filters = [
                { id: '#source_group_filter', container: '#source_group_filter' },
                { id: '#target_group_filter', container: '#target_group_filter' },
                { id: '#condition_filter', container: '#condition_filter' },
                { id: '#status_filter', container: '#status_filter' }
            ];

            filters.forEach(filter => {
                const $select = $(filter.id);
                const $container = $select.closest('.b2bking_grulespro_filter_group');
                const value = $select.val();
                
                if (value && value !== '') {
                    $container.addClass('filter-active');
                    hasActiveFilters = true;
                } else {
                    $container.removeClass('filter-active');
                }
            });

            // Update clear button state
            const $clearButton = $('.b2bking_grulespro_clear_filters_btn');
            if (hasActiveFilters) {
                $clearButton.removeClass('clear-inactive').addClass('clear-active');
            } else {
                $clearButton.removeClass('clear-active').addClass('clear-inactive');
            }
        }

        renderRules() {
            const gridContainer = $('#rules_grid');
            const listContainer = $('#rules_list');
            const emptyState = $('#rules_empty');
            
            // Update filter visibility based on total rules count
            this.updateFilterVisibility();
            
            if (this.rules.length === 0) {
                gridContainer.empty();
                listContainer.empty();
                emptyState.show();
                // Hide pagination when there are no rules
                $('#rules_pagination').hide();
                // Reset bulk actions selection when there are no rules
                this.resetBulkActionsSelection();
                return;
            }
            
            emptyState.hide();
            
            // Clear existing content
            gridContainer.empty();
            listContainer.empty();
            
            // Reset bulk actions selection when rendering new rules
            this.resetBulkActionsSelection();
            
            // Always update pagination text when rendering rules
            this.updatePaginationText();
            
            // Get current view
            const currentView = $('.b2bking_group_rules_pro_view_btn.active').data('view') || 'grid';
            
            if (currentView === 'grid') {
                // Create grid view - render cards directly in the grid container
                this.rules.forEach((rule, index) => {
                    const ruleCard = this.createRuleCard(rule, index, 'grid');
                    gridContainer.append(ruleCard);
                });
            } else {
                // Create list view - render cards directly in the list container
                this.rules.forEach((rule, index) => {
                    const ruleCard = this.createRuleCard(rule, index, 'list');
                    listContainer.append(ruleCard);
                });
            }
            
            // Initialize sortable functionality
            this.initializeSortable();
            
            // Verify and correct visual states after rendering
            this.verifyVisualStates();
            
            // Apply smooth entrance animation only on first load
            if (!this.hasRenderedBefore) {
                this.applyEntranceAnimations();
                this.hasRenderedBefore = true;
            } else {
                // For subsequent renders, show cards immediately without animation
                $('.b2bking_group_rules_pro_rule_card').addClass('no-animate');
            }
            
        }

        renderPagination() {
            // Always update pagination text first
            this.updatePaginationText();
            
            const $pagination = $('#rules_pagination');
            
            if (this.totalPages <= 1) {
                $pagination.hide();
                return;
            }

            // Don't show pagination immediately - let hideLoading() handle it
            
            // Update button states
            $('#pagination_first, #pagination_prev').prop('disabled', this.currentPage === 1);
            $('#pagination_next, #pagination_last').prop('disabled', this.currentPage === this.totalPages);
            
            // Generate page numbers
            this.generatePageNumbers();
        }

        updatePaginationText() {
            // Update pagination info
            const start = (this.currentPage - 1) * this.itemsPerPage + 1;
            const end = Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
            
            $('#pagination_start').text(start);
            $('#pagination_end').text(end);
            $('#pagination_total').text(this.totalItems);
        }

        generatePageNumbers() {
            const $pagesContainer = $('#pagination_pages');
            $pagesContainer.empty();
            
            const maxVisiblePages = 5;
            let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(this.totalPages, startPage + maxVisiblePages - 1);
            
            // Adjust start page if we're near the end
            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }
            
            // Add first page and ellipsis if needed
            if (startPage > 1) {
                this.addPageButton(1);
                if (startPage > 2) {
                    $pagesContainer.append('<span class="b2bking_group_rules_pro_pagination_ellipsis">...</span>');
                }
            }
            
            // Add visible page numbers
            for (let i = startPage; i <= endPage; i++) {
                this.addPageButton(i);
            }
            
            // Add last page and ellipsis if needed
            if (endPage < this.totalPages) {
                if (endPage < this.totalPages - 1) {
                    $pagesContainer.append('<span class="b2bking_group_rules_pro_pagination_ellipsis">...</span>');
                }
                this.addPageButton(this.totalPages);
            }
        }

        addPageButton(pageNumber) {
            const $pagesContainer = $('#pagination_pages');
            const isActive = pageNumber === this.currentPage;
            const $button = $(`<button class="b2bking_group_rules_pro_pagination_page ${isActive ? 'active' : ''}" data-page="${pageNumber}">${pageNumber}</button>`);
            $pagesContainer.append($button);
        }

        goToPage(page) {
            if (page < 1 || page > this.totalPages || page === this.currentPage) {
                return;
            }
            
            this.currentPage = page;
            this.loadRules();
        }


        createRuleCard(rule, index, view = 'grid') {
            const statusClass = rule.enabled ? 'enabled' : 'disabled';
            const statusText = rule.enabled ? b2bking.grpro_active : b2bking.grpro_inactive;
            const toggleClass = rule.enabled ? 'active' : '';
            
            if (view === 'list') {
                return $(`
                    <div class="b2bking_group_rules_pro_rule_card list_view ${rule.enabled ? 'enabled' : ''}" data-rule-id="${rule.id}" data-rule-index="${index}">
                        <div class="b2bking_group_rules_pro_rule_drag_handle list_view">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M2 4h12M2 8h12M2 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="b2bking_group_rules_pro_rule_selection list_view">
                            <input type="checkbox" class="b2bking_group_rules_pro_rule_checkbox" data-rule-id="${rule.id}">
                        </div>
                        <div class="b2bking_group_rules_pro_rule_content list_view">
                            <div class="b2bking_group_rules_pro_rule_info list_view">
                                <h4 class="b2bking_group_rules_pro_rule_name list_view" data-rule-id="${rule.id}">${this.escapeHtml(rule.name)}</h4>
                                <p class="b2bking_group_rules_pro_rule_description list_view">${this.escapeHtml(rule.description || b2bking.grpro_auto_move_customers.replace('%1$s', rule.source_group_name).replace('%2$s', rule.target_group_name))}</p>
                            </div>
                            <div class="b2bking_group_rules_pro_rule_groups list_view">
                                <span class="b2bking_group_rules_pro_rule_groups_label list_view">${b2bking.grpro_groups}</span>
                                <span class="b2bking_group_rules_pro_rule_groups_value list_view">${this.escapeHtml(rule.source_group_name)} → ${this.escapeHtml(rule.target_group_name)}</span>
                            </div>
                            <div class="b2bking_group_rules_pro_rule_condition list_view">
                                <span class="b2bking_group_rules_pro_rule_condition_label list_view">${b2bking.grpro_condition}</span>
                                <span class="b2bking_group_rules_pro_rule_condition_value list_view">${this.escapeHtml(rule.condition)}</span>
                            </div>
                            <div class="b2bking_group_rules_pro_rule_threshold list_view">
                                <span class="b2bking_group_rules_pro_rule_threshold_label list_view">${b2bking.grpro_threshold}</span>
                                <span class="b2bking_group_rules_pro_rule_threshold_value list_view">${rule.threshold_formatted || rule.threshold}</span>
                            </div>
                            <div class="b2bking_group_rules_pro_rule_actions list_view">
                                <div class="b2bking_group_rules_pro_rule_toggle list_view">
                                    <div class="b2bking_group_rules_pro_rule_toggle_switch">
                                        <input type="checkbox" ${rule.enabled ? 'checked' : ''} data-rule-id="${rule.id}">
                                        <span class="b2bking_group_rules_pro_rule_toggle_slider"></span>
                                    </div>
                                </div>
                                <a href="${b2bking_group_rules_pro.admin_url}admin.php?page=b2bking_group_rule_pro_editor&rule_id=${rule.id}" class="b2bking_group_rules_pro_rule_action_btn b2bking_group_rules_pro_rule_action_edit">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                        <path d="M8.5 1.5L12.5 5.5L4.5 13.5H0.5V9.5L8.5 1.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                    ${b2bking.grpro_edit}
                                </a>
                                <button class="b2bking_group_rules_pro_rule_action_btn b2bking_group_rules_pro_rule_action_delete" data-rule-id="${rule.id}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                        <path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        <path d="M18.8332 8.5L18.3732 15.3991C18.1962 18.054 18.1077 19.3815 17.2427 20.1907C16.3777 21 15.0473 21 12.3865 21H11.6132C8.95235 21 7.62195 21 6.75694 20.1907C5.89194 19.3815 5.80344 18.054 5.62644 15.3991L5.1665 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        <path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path>
                                    </svg>
                                    ${b2bking.grpro_delete}
                                </button>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                // Grid view (default)
                return $(`
                    <div class="b2bking_group_rules_pro_rule_card ${rule.enabled ? 'enabled' : ''}" data-rule-id="${rule.id}" data-rule-index="${index}">
                        <div class="b2bking_group_rules_pro_rule_drag_handle">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <circle cx="2" cy="2" r="1" fill="currentColor"/>
                                <circle cx="6" cy="2" r="1" fill="currentColor"/>
                                <circle cx="10" cy="2" r="1" fill="currentColor"/>
                                <circle cx="2" cy="6" r="1" fill="currentColor"/>
                                <circle cx="6" cy="6" r="1" fill="currentColor"/>
                                <circle cx="10" cy="6" r="1" fill="currentColor"/>
                                <circle cx="2" cy="10" r="1" fill="currentColor"/>
                                <circle cx="6" cy="10" r="1" fill="currentColor"/>
                                <circle cx="10" cy="10" r="1" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="b2bking_group_rules_pro_rule_header">
                            <div class="b2bking_group_rules_pro_rule_selection">
                                <input type="checkbox" class="b2bking_group_rules_pro_rule_checkbox" data-rule-id="${rule.id}">
                            </div>
                            <div class="b2bking_group_rules_pro_rule_title">
                                <h4 class="b2bking_group_rules_pro_rule_title_element" data-rule-id="${rule.id}">${this.escapeHtml(rule.name)}</h4>
                            </div>
                            <div class="b2bking_group_rules_pro_rule_actions">
                                <button class="b2bking_group_rules_pro_rule_toggle" data-rule-id="${rule.id}" data-enabled="${rule.enabled ? '1' : '0'}">
                                    <div class="b2bking_group_rules_pro_toggle_switch ${toggleClass}">
                                        <div class="b2bking_group_rules_pro_toggle_thumb"></div>
                                    </div>
                                </button>
                                <a href="${b2bking_group_rules_pro.admin_url}admin.php?page=b2bking_group_rule_pro_editor&rule_id=${rule.id}" 
                                   class="b2bking_group_rules_pro_rule_edit">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                        <path d="M8.5 1.5L12.5 5.5L4.5 13.5H0.5V9.5L8.5 1.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </a>
                                <button class="b2bking_group_rules_pro_rule_delete" data-rule-id="${rule.id}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                        <path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        <path d="M18.8332 8.5L18.3732 15.3991C18.1962 18.054 18.1077 19.3815 17.2427 20.1907C16.3777 21 15.0473 21 12.3865 21H11.6132C8.95235 21 7.62195 21 6.75694 20.1907C5.89194 19.3815 5.80344 18.054 5.62644 15.3991L5.1665 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        <path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="b2bking_group_rules_pro_rule_content">
                            <div class="b2bking_group_rules_pro_rule_description">
                                <p>${this.escapeHtml(rule.description || b2bking.grpro_auto_move_customers.replace('%1$s', rule.source_group_name).replace('%2$s', rule.target_group_name))}</p>
                            </div>
                            <div class="b2bking_group_rules_pro_rule_details">
                                <div class="b2bking_group_rules_pro_rule_detail">
                                    <span class="b2bking_group_rules_pro_rule_detail_label">${b2bking.grpro_from}:</span>
                                    <span class="b2bking_group_rules_pro_rule_detail_value">${this.escapeHtml(rule.source_group_name)}</span>
                                </div>
                                <div class="b2bking_group_rules_pro_rule_detail">
                                    <span class="b2bking_group_rules_pro_rule_detail_label">${b2bking.grpro_to}:</span>
                                    <span class="b2bking_group_rules_pro_rule_detail_value">→ ${this.escapeHtml(rule.target_group_name)}</span>
                                </div>
                                <div class="b2bking_group_rules_pro_rule_detail">
                                    <span class="b2bking_group_rules_pro_rule_detail_label">${b2bking.grpro_when}:</span>
                                    <span class="b2bking_group_rules_pro_rule_detail_value">${this.escapeHtml(this.cleanConditionText(rule.condition))} ${rule.threshold_formatted || rule.threshold}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }
        }

        initializeSortable() {
            const self = this;
            
            // Destroy existing sortable to prevent multiple initializations
            if ($('#rules_grid').hasClass('ui-sortable')) {
                $('#rules_grid').sortable('destroy');
            }
            if ($('#rules_list').hasClass('ui-sortable')) {
                $('#rules_list').sortable('destroy');
            }
            
            // Initialize sortable on both containers
            $('#rules_grid, #rules_list').sortable({
                handle: '.b2bking_group_rules_pro_rule_drag_handle',
                placeholder: 'b2bking_group_rules_pro_rule_placeholder',
                helper: 'clone',
                forcePlaceholderSize: true,
                forceHelperSize: true,
                tolerance: 'pointer',
                cursor: 'grabbing',
                cursorAt: { top: 20, left: 20 },
                distance: 1,
                delay: 0,
                opacity: 0.9,
                zIndex: 9999,
                start: function(event, ui) {
                    // Add dragging class to original item
                    ui.item.addClass('b2bking_group_rules_pro_rule_dragging');
                    
                    // Optimize helper for better performance
                    ui.helper.addClass('b2bking_group_rules_pro_rule_helper');
                    ui.helper.css({
                        'transform': 'rotate(2deg) scale(1.02)',
                        'transition': 'none',
                        'z-index': '9999',
                        'box-shadow': '0 8px 25px rgba(0, 0, 0, 0.15)',
                        'pointer-events': 'none',
                        'will-change': 'transform'
                    });
                    
                    // Disable transitions on all cards during drag for better performance
                    $('.b2bking_group_rules_pro_rule_card').css({
                        'transition': 'none',
                        'will-change': 'auto'
                    });
                    
                    // Set placeholder height to match the dragged item
                    ui.placeholder.css('height', ui.item.height());
                },
                drag: function(event, ui) {
                    // Smooth drag performance - no additional processing needed
                },
                stop: function(event, ui) {
                    // Remove dragging class
                    ui.item.removeClass('b2bking_group_rules_pro_rule_dragging');
                    
                    // Re-enable transitions on all cards
                    $('.b2bking_group_rules_pro_rule_card').css({
                        'transition': '',
                        'will-change': ''
                    });
                    
                    // Get new order efficiently
                    const newOrder = [];
                    $('.b2bking_group_rules_pro_rule_card').each(function(index) {
                        const ruleId = $(this).data('rule-id');
                        newOrder.push({
                            rule_id: ruleId,
                            new_order: index + 1
                        });
                    });
                    
                    // Update order on server
                    self.updateRuleOrder(newOrder);
                }
            });
        }

        updateRuleOrder(newOrder) {
            if (this.isLoading) return;

            // Debounce the update to prevent multiple rapid calls
            clearTimeout(this.orderUpdateTimeout);
            this.orderUpdateTimeout = setTimeout(() => {
                this.performOrderUpdate(newOrder);
            }, 100);
        }

        performOrderUpdate(newOrder) {
            if (this.isLoading) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2bking_group_rules_pro_update_order',
                    nonce: b2bking_group_rules_pro.nonce,
                    order: newOrder
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('success', b2bking.grpro_rule_order_updated);
                        // Update the rules array with new order
                        this.rules = response.data.rules || this.rules;
                    } else {
                        this.showMessage('error', response.data || b2bking.grpro_failed_to_update_rule_order);
                        // Reload rules to reset order
                        this.loadRules();
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage('error', b2bking.grpro_error_updating_rule_order);
                    console.error('AJAX Error:', error);
                    // Reload rules to reset order
                    this.loadRules();
                }
            });
        }



        debounceSearch(searchTerm) {
            clearTimeout(this.searchTimeout);
            
            // Hide pagination immediately when user starts typing
            $('#rules_pagination').hide();
            
            this.searchTimeout = setTimeout(() => {
                this.currentPage = 1; // Reset to first page when searching
                this.loadRules();
            }, 300);
        }

        showLoading() {
            this.loadingStartTime = Date.now();
            
            // Add loading class to body for CSS control
            $('body').addClass('loading');
            
            // Hide all content containers and grid elements specifically
            $('.b2bking_group_rules_pro_content_container').removeClass('show').hide();
            $('#rules_grid').hide();
            $('#rules_list').hide();
            $('#rules_empty').hide();
            $('#rules_pagination').hide();
            
            // Show loading container
            if ($('.b2bking_group_rules_pro_loading_container').length === 0) {
                // Create loading container if it doesn't exist
                const loadingHtml = `
                    <div class="b2bking_group_rules_pro_loading_container">
                        <div class="b2bking_group_rules_pro_loading_spinner"></div>
                        <p>${b2bking.grpro_loading_rules}</p>
                    </div>
                `;
                
                // Insert loading container before the content containers
                if ($('.b2bking_group_rules_pro_content_container').length > 0) {
                    $('.b2bking_group_rules_pro_content_container').first().before(loadingHtml);
                } else {
                    // Fallback: append to main container
                    $('#b2bking_group_rules_pro_main_container, .b2bking_group_rules_pro_rules_container').first().append(loadingHtml);
                }
            }
            
            $('.b2bking_group_rules_pro_loading_container').show();
        }

        hideLoading() {
            const elapsedTime = this.loadingStartTime ? Date.now() - this.loadingStartTime : 0;
            const remainingTime = Math.max(0, this.minLoadingTime - elapsedTime);
            
            const showContent = () => {
                // Remove loading class from body
                $('body').removeClass('loading');
                
                // Hide loading container
                $('.b2bking_group_rules_pro_loading_container').hide();
                
                // Show content containers
                $('.b2bking_group_rules_pro_content_container').addClass('show').show();
                
                // Show only the appropriate view container based on current view
                const currentView = $('.b2bking_group_rules_pro_view_btn.active').data('view') || 'grid';
                if (currentView === 'grid') {
                    $('#rules_grid').show();
                    $('#rules_list').hide();
                } else {
                    $('#rules_grid').hide();
                    $('#rules_list').show();
                }
                
                // Show pagination after loading is complete (only if total rules > 20)
                if (this.totalItems > 20) {
                    $('#rules_pagination').show();
                    
                    // Show/hide pagination controls based on number of pages
                    if (this.totalPages > 1) {
                        $('.b2bking_group_rules_pro_pagination_controls').show();
                        $('#rules_pagination').removeClass('single-page');
                    } else {
                        $('.b2bking_group_rules_pro_pagination_controls').hide();
                        $('#rules_pagination').addClass('single-page');
                    }
                }
            };
            
            if (remainingTime > 0) {
                // Wait for the remaining time before showing content
                setTimeout(showContent, remainingTime);
            } else {
                // Minimum time has already passed, show content immediately
                showContent();
            }
        }

        showMessage(type, message) {
            // Use B2BKing's notification system for consistency
            this.showB2BKingNotification(type, message);
        }

        showB2BKingNotification(type, message) {
            // Use SweetAlert2 Toast like B2BKing's "Settings saved successfully" notifications
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: false,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                const icon = type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info');
                
                Toast.fire({
                    icon: icon,
                    title: message
                });
            }
        }

        hideMessage() {
            $('#message_container').hide();
        }

        initializeTooltips() {
            // Initialize tooltips if available
            if (typeof $.fn.tooltip === 'function') {
                $('[data-tooltip]').tooltip();
            }
        }

        applyEntranceAnimations() {
            // Apply smooth entrance animations only on first load
            // Calculate delay to ensure animations start after loading screen is hidden
            const elapsedTime = this.loadingStartTime ? Date.now() - this.loadingStartTime : 0;
            const remainingLoadingTime = Math.max(0, this.minLoadingTime - elapsedTime);
            const animationDelay = remainingLoadingTime + 150; // Extra 150ms after loading hides
            
            setTimeout(() => {
                // Apply animation to all cards simultaneously (no staggered delays)
                $('.b2bking_group_rules_pro_rule_card').addClass('animate-in');
            }, animationDelay);
        }


        initializeAnimations() {
            // Legacy method - kept for compatibility but not used
            // Add entrance animations to cards
            $('.b2bking_group_rules_pro_rule_card').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.1) + 's',
                    'animation': 'b2bking_group_rules_pro_fadeInUp 0.6s ease forwards'
                });
            });
        }

        // View toggle functionality
        switchView(view) {
            // Update button states
            $('.b2bking_group_rules_pro_view_btn').removeClass('active');
            $(`.b2bking_group_rules_pro_view_btn[data-view="${view}"]`).addClass('active');

            // Don't change container visibility if we're currently loading
            if (!$('body').hasClass('loading')) {
                // Show/hide appropriate containers
                if (view === 'grid') {
                    $('#rules_grid').addClass('active').show();
                    $('#rules_list').removeClass('active').hide();
                } else {
                    $('#rules_grid').removeClass('active').hide();
                    $('#rules_list').addClass('active').show();
                }
                
                // Re-render rules with the new view
                this.renderRules();
            }

            // Save preference to localStorage
            localStorage.setItem('b2bking_group_rules_pro_view', view);
        }

        loadSavedView() {
            const savedView = localStorage.getItem('b2bking_group_rules_pro_view') || 'grid';
            this.switchView(savedView);
        }

        loadSavedFilterPreference() {
            const savedFilterState = localStorage.getItem('b2bking_group_rules_pro_filters_enabled');
            this.filtersEnabled = savedFilterState === 'true';
        }

        loadSavedItemsPerPage() {
            const savedItemsPerPage = localStorage.getItem('b2bking_group_rules_pro_items_per_page');
            if (savedItemsPerPage) {
                this.itemsPerPage = parseInt(savedItemsPerPage);
            }
        }

        saveItemsPerPage() {
            localStorage.setItem('b2bking_group_rules_pro_items_per_page', this.itemsPerPage.toString());
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }


        cleanConditionText(conditionText) {
            // Remove the "> X" or "< X" part from condition text for display
            return conditionText
                .replace(/ > X$/, '')
                .replace(/ < X$/, '');
        }

        // ===== Bulk Actions Methods =====
        
        toggleBulkActions() {
            const toolbar = $('#b2bking_group_rules_pro_bulk_toolbar');
            const $bulkBtn = $('#bulk_actions');
            const isVisible = toolbar.is(':visible');
            
            if (isVisible) {
                // Hide bulk actions and deselect all
                toolbar.slideUp(200);
                $('.b2bking_group_rules_pro_rule_checkbox').prop('checked', false);
                $('.b2bking_group_rules_pro_rule_card').removeClass('selected');
                this.updateBulkToolbar();
                // Delay removing the class to sync with toolbar animation
                setTimeout(() => {
                    $('body').removeClass('b2bking_bulk_actions_active');
                }, 200);
                $bulkBtn.removeClass('active');
            } else {
                // Show bulk actions
                toolbar.slideDown(200);
                // Initialize with no-selection state
                $('.b2bking_group_rules_pro_bulk_count_number').addClass('no-selection');
                $('.b2bking_group_rules_pro_bulk_selected_count').addClass('no-selection');
                this.updateBulkToolbar();
                $('body').addClass('b2bking_bulk_actions_active');
                $bulkBtn.addClass('active');
            }
        }

        closeBulkActions() {
            const toolbar = $('#b2bking_group_rules_pro_bulk_toolbar');
            const $bulkBtn = $('#bulk_actions');
            if (toolbar.is(':visible')) {
                toolbar.slideUp(200);
                // Deselect all rules when closing bulk actions
                $('.b2bking_group_rules_pro_rule_checkbox').prop('checked', false);
                $('.b2bking_group_rules_pro_rule_card').removeClass('selected');
                this.updateBulkToolbar();
                // Delay removing the class to sync with toolbar animation
                setTimeout(() => {
                    $('body').removeClass('b2bking_bulk_actions_active');
                }, 200);
                $bulkBtn.removeClass('active');
            }
        }

        updateBulkToolbar() {
            const selectedCount = $('.b2bking_group_rules_pro_rule_checkbox:checked').length;
            const totalCount = $('.b2bking_group_rules_pro_rule_checkbox').length;
            
            $('.b2bking_group_rules_pro_bulk_count_number').text(selectedCount);
            
            // Update color states based on selection
            const countElement = $('.b2bking_group_rules_pro_bulk_count_number');
            const textElement = $('.b2bking_group_rules_pro_bulk_selected_count');
            
            if (selectedCount > 0) {
                countElement.removeClass('no-selection').addClass('has-selection');
                textElement.removeClass('no-selection').addClass('has-selection');
            } else {
                countElement.removeClass('has-selection').addClass('no-selection');
                textElement.removeClass('has-selection').addClass('no-selection');
            }
            
            // Enable/disable action buttons based on selection
            const actionButtons = $('.b2bking_group_rules_pro_bulk_action_btn');
            if (selectedCount > 0) {
                actionButtons.prop('disabled', false).removeClass('disabled');
            } else {
                actionButtons.prop('disabled', true).addClass('disabled');
            }
            
            // Update toggle button text based on selection state
            const toggleButton = $('#select_all_rules');
            if (selectedCount === 0) {
                toggleButton.text(b2bking.grpro_select_all);
            } else {
                toggleButton.text(b2bking.grpro_deselect_all);
            }
        }

        resetBulkActionsSelection() {
            // Reset all checkboxes (though they should already be cleared when content is emptied)
            $('.b2bking_group_rules_pro_rule_checkbox').prop('checked', false);
            $('.b2bking_group_rules_pro_rule_card').removeClass('selected');
            
            // Update the bulk toolbar to reflect the reset state
            this.updateBulkToolbar();
        }

        selectAllRules() {
            const selectedCount = $('.b2bking_group_rules_pro_rule_checkbox:checked').length;
            const totalCount = $('.b2bking_group_rules_pro_rule_checkbox').length;
            
            if (selectedCount > 0) {
                // Deselect all (if any rules are selected)
                $('.b2bking_group_rules_pro_rule_checkbox').prop('checked', false);
                $('.b2bking_group_rules_pro_rule_card').removeClass('selected');
            } else {
                // Select all (if no rules are selected)
                $('.b2bking_group_rules_pro_rule_checkbox').prop('checked', true);
                $('.b2bking_group_rules_pro_rule_card').addClass('selected');
            }
            
            this.updateBulkToolbar();
        }


        getSelectedRuleIds() {
            const selectedIds = [];
            $('.b2bking_group_rules_pro_rule_checkbox:checked').each(function() {
                selectedIds.push($(this).data('rule-id'));
            });
            return selectedIds;
        }

        bulkEnableRules() {
            const selectedIds = this.getSelectedRuleIds();
            if (selectedIds.length === 0) return;

            // Update visual state immediately for better UX
            this.updateMultipleRulesVisualState(selectedIds, true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2bking_group_rules_pro_bulk_enable',
                    nonce: b2bking_group_rules_pro.nonce,
                    rule_ids: selectedIds
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('success', b2bking.grpro_settings_saved_successfully);
                        // Update the rules data
                        this.updateMultipleRulesData(selectedIds, true);
                        // Close bulk actions panel after successful operation
                        this.closeBulkActions();
                    } else {
                        // Revert visual state on failure
                        this.updateMultipleRulesVisualState(selectedIds, false);
                        this.showMessage('error', b2bking.grpro_failed_to_save_settings);
                    }
                },
                error: () => {
                    // Revert visual state on error
                    this.updateMultipleRulesVisualState(selectedIds, false);
                    this.showMessage('error', b2bking.grpro_error_saving_settings);
                }
            });
        }

        bulkDisableRules() {
            const selectedIds = this.getSelectedRuleIds();
            if (selectedIds.length === 0) return;

            // Update visual state immediately for better UX
            this.updateMultipleRulesVisualState(selectedIds, false);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2bking_group_rules_pro_bulk_disable',
                    nonce: b2bking_group_rules_pro.nonce,
                    rule_ids: selectedIds
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('success', b2bking.grpro_settings_saved_successfully);
                        // Update the rules data
                        this.updateMultipleRulesData(selectedIds, false);
                        // Close bulk actions panel after successful operation
                        this.closeBulkActions();
                    } else {
                        // Revert visual state on failure
                        this.updateMultipleRulesVisualState(selectedIds, true);
                        this.showMessage('error', b2bking.grpro_failed_to_save_settings);
                    }
                },
                error: () => {
                    // Revert visual state on error
                    this.updateMultipleRulesVisualState(selectedIds, true);
                    this.showMessage('error', b2bking.grpro_error_saving_settings);
                }
            });
        }

        bulkDeleteRules() {
            const selectedIds = this.getSelectedRuleIds();
            if (selectedIds.length === 0) return;

            if (!confirm(b2bking.grpro_confirm_delete_rules.replace('%d', selectedIds.length))) {
                return;
            }

            this.showMessage('info', b2bking.grpro_deleting_rules.replace('%d', selectedIds.length));
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2bking_group_rules_pro_bulk_delete',
                    nonce: b2bking_group_rules_pro.nonce,
                    rule_ids: selectedIds
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('success', b2bking.grpro_rules_deleted_successfully.replace('%d', selectedIds.length));
                        
                        // After bulk delete, check if current page will be empty
                        // If we're deleting all rules on current page and we're not on page 1,
                        // reset to page 1 to avoid showing empty state when other pages exist
                        const rulesOnCurrentPage = this.rules.length;
                        const deletedCount = selectedIds.length;
                        
                        if (deletedCount >= rulesOnCurrentPage && this.currentPage > 1) {
                            this.currentPage = 1;
                        }
                        
                        this.loadRules();
                        // Close bulk actions panel after successful operation
                        this.closeBulkActions();
                    } else {
                        this.showMessage('error', response.data || b2bking.grpro_failed_to_delete_rules);
                    }
                },
                error: () => {
                    this.showMessage('error', b2bking.grpro_failed_to_delete_rules);
                }
            });
        }

    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Check if we're on the Group Rules Pro page
        if ($('#b2bking_group_rules_pro_main_container').length > 0) {
            new B2BKingGroupRulesPro();
        }
    });

    // Add CSS for form validation
    const style = document.createElement('style');
    style.textContent = `
        .b2bking_group_rules_pro_form_group.error label {
            color: #dc3545;
        }
        
        .b2bking_group_rules_pro_error_message {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #dc3545;
            font-weight: 500;
        }
        
        #save_rule_btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        #save_rule_btn:disabled:hover {
            transform: none !important;
            box-shadow: 0 2px 8px rgba(144, 106, 29, 0.3) !important;
        }
    `;
    document.head.appendChild(style);

})(jQuery);

