/**
 * B2BKing Dynamic Rules Pro - Dynamic Rules Management JavaScript
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
            $(document).on('change.b2bkingGroupRulesPro', '#rule_type_filter, #applies_to_filter, #customer_group_filter, #status_filter', (e) => {
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
            $(document).on('change.b2bkingGroupRulesPro', '.b2bking_dynamic_rules_pro_rule_checkbox', (e) => {
                const checkbox = $(e.target);
                const card = checkbox.closest('.b2bking_dynamic_rules_pro_rule_card');
                
                // Toggle selected class on the card
                if (checkbox.is(':checked')) {
                    card.addClass('selected');
                } else {
                    card.removeClass('selected');
                }
                
                this.updateBulkToolbar();
            });

            // Make the entire selection area clickable
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_dynamic_rules_pro_rule_selection', (e) => {
                // Don't trigger if clicking directly on the checkbox (to avoid double-triggering)
                if (e.target.type === 'checkbox') {
                    return;
                }
                
                const selectionArea = $(e.currentTarget);
                const checkbox = selectionArea.find('.b2bking_dynamic_rules_pro_rule_checkbox');
                const card = selectionArea.closest('.b2bking_dynamic_rules_pro_rule_card');
                
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
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_dynamic_rules_pro_view_btn', (e) => {
                e.preventDefault();
                const view = $(e.currentTarget).data('view');
                this.switchView(view);
            });

            // Grid view toggle - button with data attributes
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_dynamic_rules_pro_rule_toggle:not(.list_view)', (e) => {
                e.preventDefault();
                const ruleId = $(e.currentTarget).data('rule-id');
                // More robust enabled state checking - handle both string and boolean values
                const enabledData = $(e.currentTarget).data('enabled');
                const enabled = enabledData === '1' || enabledData === 1 || enabledData === true;
                this.toggleRuleStatus(ruleId, !enabled);
            });

            // List view toggle - click anywhere on the toggle button
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_dynamic_rules_pro_rule_toggle.list_view', (e) => {
                // Check if the click is directly on the toggle button, not on the inner toggle switch
                if ($(e.target).hasClass('b2bking_dynamic_rules_pro_rule_toggle_slider') || 
                    $(e.target).closest('.b2bking_dynamic_rules_pro_rule_toggle_slider').length > 0) {
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
                const ruleCard = $(e.currentTarget).closest('.b2bking_dynamic_rules_pro_rule_card');
                const currentState = ruleCard.hasClass('enabled');
                
                // Directly call toggleRuleStatus like grid view does
                this.toggleRuleStatus(ruleId, !currentState);
            });

            // List view toggle switch - checkbox input
            $(document).on('change.b2bkingGroupRulesPro', '.b2bking_dynamic_rules_pro_rule_toggle_switch input[type="checkbox"]', (e) => {
                const ruleId = $(e.currentTarget).data('rule-id');
                const isEnabled = $(e.currentTarget).is(':checked');
                
                if (!ruleId) {
                    //console.error('No rule ID found on checkbox');
                    return;
                }
                
                this.toggleRuleStatus(ruleId, isEnabled);
            });

            // Alternative list view handler - click on slider
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_dynamic_rules_pro_rule_toggle_slider', (e) => {
                const checkbox = $(e.currentTarget).siblings('input[type="checkbox"]');
                const ruleId = checkbox.data('rule-id');
                const currentState = checkbox.is(':checked');
                const newState = !currentState;
                
                if (!ruleId) {
                    //console.error('No rule ID found on slider checkbox');
                    return;
                }
                
                // Manually toggle checkbox and trigger change
                checkbox.prop('checked', newState).trigger('change');
            });

            // Rule delete (both grid and list view)
            $(document).on('click', '.b2bking_dynamic_rules_pro_rule_delete, .b2bking_dynamic_rules_pro_rule_action_delete', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation(); // Prevent other handlers from firing
                const ruleId = $(e.currentTarget).data('rule-id');
                this.deleteRule(ruleId);
            });

            // Title click handlers - trigger edit button
            $(document).on('click.b2bkingGroupRulesPro', '.b2bking_dynamic_rules_pro_rule_name.list_view, .b2bking_dynamic_rules_pro_rule_title_element', (e) => {
                e.preventDefault();
                const ruleId = $(e.currentTarget).data('rule-id');
                if (ruleId) {
                    // Find the edit button in the same card and trigger it
                    const card = $(e.currentTarget).closest('.b2bking_dynamic_rules_pro_rule_card');
                    const editButton = card.find('.b2bking_dynamic_rules_pro_rule_edit, .b2bking_dynamic_rules_pro_rule_action_edit');
                    if (editButton.length > 0) {
                        editButton[0].click();
                    }
                }
            });

            // Message close
            $(document).on('click', '.b2bking_dynamic_rules_pro_message_close', (e) => {
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

            $(document).on('click', '.b2bking_dynamic_rules_pro_pagination_page', (e) => {
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
                    action: 'b2bking_dynamic_rules_pro_toggle_status',
                    security: b2bking.security,
                    rule_id: ruleId,
                    enabled: enabled ? 'true' : 'false'
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
            const ruleCard = $(`.b2bking_dynamic_rules_pro_rule_card[data-rule-id="${ruleId}"]`);
            if (ruleCard.length) {
                if (enabled) {
                    ruleCard.addClass('enabled');
                } else {
                    ruleCard.removeClass('enabled');
                }
            }

            // Update grid view toggle
            const gridToggle = $(`.b2bking_dynamic_rules_pro_rule_toggle[data-rule-id="${ruleId}"]:not(.list_view)`);
            if (gridToggle.length) {
                gridToggle.data('enabled', enabled ? '1' : '0');
                const toggleSwitch = gridToggle.find('.b2bking_dynamic_rules_pro_toggle_switch');
                if (enabled) {
                    toggleSwitch.addClass('active');
                } else {
                    toggleSwitch.removeClass('active');
                }
            }

            // Update list view checkbox
            const listCheckbox = $(`.b2bking_dynamic_rules_pro_rule_toggle_switch input[data-rule-id="${ruleId}"]`);
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
            $('.b2bking_dynamic_rules_pro_content_container').removeClass('show').hide();
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
                const gridToggle = $(`.b2bking_dynamic_rules_pro_rule_toggle[data-rule-id="${ruleId}"]:not(.list_view)`);
                if (gridToggle.length) {
                    const dataEnabled = gridToggle.data('enabled');
                    const expectedDataEnabled = enabled ? 1 : 0;
                    
                    // Fix data attribute if it doesn't match
                    if (dataEnabled !== expectedDataEnabled) {
                       // console.warn(`Rule ${ruleId}: Fixed data-enabled mismatch. Was: ${dataEnabled}, Should be: ${expectedDataEnabled}`);
                        gridToggle.data('enabled', expectedDataEnabled);
                    }
                    
                    // Fix toggle switch visual state
                    const toggleSwitch = gridToggle.find('.b2bking_dynamic_rules_pro_toggle_switch');
                    const hasActiveClass = toggleSwitch.hasClass('active');
                    if (enabled && !hasActiveClass) {
                        toggleSwitch.addClass('active');
                    } else if (!enabled && hasActiveClass) {
                        toggleSwitch.removeClass('active');
                    }
                }
                
                // Check list view checkbox state
                const listCheckbox = $(`.b2bking_dynamic_rules_pro_rule_toggle_switch input[data-rule-id="${ruleId}"]`);
                if (listCheckbox.length) {
                    const isChecked = listCheckbox.is(':checked');
                    if (enabled !== isChecked) {
                       // console.warn(`Rule ${ruleId}: Fixed checkbox state mismatch. Was: ${isChecked}, Should be: ${enabled}`);
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
                    action: 'b2bking_dynamic_rules_pro_delete_rule',
                    nonce: b2bking_dynamic_rules_pro.nonce,
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
                    action: 'b2bking_dynamic_rules_pro_load_rules',
                    nonce: b2bking_dynamic_rules_pro.nonce,
                    search: searchTerm,
                    rule_type_filter: $('#rule_type_filter').val() || '',
                    applies_to_filter: $('#applies_to_filter').val() || '',
                    customer_group_filter: $('#customer_group_filter').val() || '',
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
            $('#rule_type_filter').val('');
            $('#applies_to_filter').val('');
            $('#customer_group_filter').val('');
            $('#status_filter').val('');
            
            // Reset pagination to first page
            this.currentPage = 1;
            
            // Update filter indicators and clear button state
            this.updateFilterIndicators();
            
            // Reload rules
            this.loadRules();
        }

        updateFilterVisibility() {
            const $filtersContainer = $('.b2bking_rulespro_filters');
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
            const $filtersContainer = $('.b2bking_rulespro_filters');
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
            localStorage.setItem('b2bking_dynamic_rules_pro_filters_enabled', this.filtersEnabled);
        }

        updateFilterIndicators() {
            let hasActiveFilters = false;

            // Check search input
            const searchValue = $('#rule_search').val() || $('#rules_search').val() || '';
            const $searchContainer = $('.b2bking_rulespro_search_container');
            if (searchValue.trim() !== '') {
                $searchContainer.addClass('filter-active');
                hasActiveFilters = true;
            } else {
                $searchContainer.removeClass('filter-active');
            }

            // Check filter dropdowns
            const filters = [
                { id: '#rule_type_filter', container: '#rule_type_filter' },
                { id: '#applies_to_filter', container: '#applies_to_filter' },
                { id: '#customer_group_filter', container: '#customer_group_filter' },
                { id: '#status_filter', container: '#status_filter' }
            ];

            filters.forEach(filter => {
                const $select = $(filter.id);
                const $container = $select.closest('.b2bking_rulespro_filter_group');
                const value = $select.val();
                
                if (value && value !== '') {
                    $container.addClass('filter-active');
                    hasActiveFilters = true;
                } else {
                    $container.removeClass('filter-active');
                }
            });

            // Update clear button state
            const $clearButton = $('.b2bking_rulespro_clear_filters_btn');
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
            const currentView = $('.b2bking_dynamic_rules_pro_view_btn.active').data('view') || 'grid';
            
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
                $('.b2bking_dynamic_rules_pro_rule_card').addClass('no-animate');
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
            // $pagination.show();
            
            // Don't show pagination immediately - let hideLoading() handle it
            // The pagination visibility will be handled in hideLoading() method
            
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
                    $pagesContainer.append('<span class="b2bking_dynamic_rules_pro_pagination_ellipsis">...</span>');
                }
            }
            
            // Add visible page numbers
            for (let i = startPage; i <= endPage; i++) {
                this.addPageButton(i);
            }
            
            // Add last page and ellipsis if needed
            if (endPage < this.totalPages) {
                if (endPage < this.totalPages - 1) {
                    $pagesContainer.append('<span class="b2bking_dynamic_rules_pro_pagination_ellipsis">...</span>');
                }
                this.addPageButton(this.totalPages);
            }
        }

        addPageButton(pageNumber) {
            const $pagesContainer = $('#pagination_pages');
            const isActive = pageNumber === this.currentPage;
            const $button = $(`<button class="b2bking_dynamic_rules_pro_pagination_page ${isActive ? 'active' : ''}" data-page="${pageNumber}">${pageNumber}</button>`);
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
            
            // Get rule type indicator elements
            const ruleTypeIndicator = this.getRuleTypeIndicator(rule.rule_type);
            const ruleTypeIcon = this.getRuleTypeIcon(rule.rule_type);
            const appliesPreviewMarkup = this.renderValuePreview(rule.applies_to_display || rule.applies_to);
            const usersPreviewMarkup = this.renderValuePreview(rule.customer_group_display || rule.customer_group);
            
            if (view === 'list') {
                return $(`
                    <div class="b2bking_dynamic_rules_pro_rule_card list_view ${rule.enabled ? 'enabled' : ''}" data-rule-id="${rule.id}" data-rule-index="${index}" data-rule-type="${rule.rule_type}">
                        ${ruleTypeIndicator}
                        <div class="b2bking_dynamic_rules_pro_rule_drag_handle list_view">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M2 4h12M2 8h12M2 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="b2bking_dynamic_rules_pro_rule_selection list_view">
                            <input type="checkbox" class="b2bking_dynamic_rules_pro_rule_checkbox" data-rule-id="${rule.id}">
                        </div>
                        <div class="b2bking_dynamic_rules_pro_rule_content list_view">
                            <div class="b2bking_dynamic_rules_pro_rule_info list_view">
                                <h4 class="b2bking_dynamic_rules_pro_rule_name list_view" data-rule-id="${rule.id}">${this.escapeHtml(rule.name)}</h4>
                            </div>
                            <div class="b2bking_dynamic_rules_pro_rule_type list_view">
                                <span class="b2bking_dynamic_rules_pro_rule_type_value list_view">
                                    ${ruleTypeIcon}
                                    ${this.escapeHtml(rule.rule_type_display || rule.rule_type)}
                                </span>
                            </div>
                            <div class="b2bking_dynamic_rules_pro_rule_applies_to list_view">
                                <span class="b2bking_dynamic_rules_pro_rule_applies_to_label list_view">${b2bking.drpro_applies_to}</span>
                                <span class="b2bking_dynamic_rules_pro_rule_applies_to_value list_view">${appliesPreviewMarkup}</span>
                            </div>
                            <div class="b2bking_dynamic_rules_pro_rule_customer_group list_view">
                                <span class="b2bking_dynamic_rules_pro_rule_customer_group_label list_view">${b2bking.drpro_users}</span>
                                <span class="b2bking_dynamic_rules_pro_rule_customer_group_value list_view">${usersPreviewMarkup}</span>
                            </div>
                            <div class="b2bking_dynamic_rules_pro_rule_how_much list_view">
                                <span class="b2bking_dynamic_rules_pro_rule_how_much_label list_view">${b2bking.drpro_value}</span>
                                <span class="b2bking_dynamic_rules_pro_rule_how_much_value list_view">${rule.rule_type === 'info_table' || rule.rule_type === 'replace_prices_quote' || rule.rule_type === 'quotes_products' ? '—' : (rule.how_much_display || rule.how_much || '—')}</span>
                            </div>
                            <div class="b2bking_dynamic_rules_pro_rule_actions list_view">
                                <div class="b2bking_dynamic_rules_pro_rule_toggle list_view">
                                    <div class="b2bking_dynamic_rules_pro_rule_toggle_switch">
                                        <input type="checkbox" ${rule.enabled ? 'checked' : ''} data-rule-id="${rule.id}">
                                        <span class="b2bking_dynamic_rules_pro_rule_toggle_slider"></span>
                                    </div>
                                </div>
                                <a href="${b2bking_dynamic_rules_pro.admin_url}admin.php?page=b2bking_dynamic_rule_pro_editor&rule_id=${rule.id}" class="b2bking_dynamic_rules_pro_rule_action_btn b2bking_dynamic_rules_pro_rule_action_edit">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                        <path d="M8.5 1.5L12.5 5.5L4.5 13.5H0.5V9.5L8.5 1.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                    ${b2bking.grpro_edit}
                                </a>
                                <button class="b2bking_dynamic_rules_pro_rule_action_btn b2bking_dynamic_rules_pro_rule_action_delete" data-rule-id="${rule.id}">
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
                    <div class="b2bking_dynamic_rules_pro_rule_card ${rule.enabled ? 'enabled' : ''}" data-rule-id="${rule.id}" data-rule-index="${index}" data-rule-type="${rule.rule_type}">
                        ${ruleTypeIndicator}
                        <div class="b2bking_dynamic_rules_pro_rule_drag_handle">
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
                        <div class="b2bking_dynamic_rules_pro_rule_header">
                            <div class="b2bking_dynamic_rules_pro_rule_selection">
                                <input type="checkbox" class="b2bking_dynamic_rules_pro_rule_checkbox" data-rule-id="${rule.id}">
                            </div>
                            <div class="b2bking_dynamic_rules_pro_rule_title">
                                <span class="b2bking_dynamic_rules_pro_rule_type">
                                    ${ruleTypeIcon}
                                    ${this.escapeHtml(rule.rule_type_display || rule.rule_type)}
                                </span>
                                <h4 class="b2bking_dynamic_rules_pro_rule_title_element" data-rule-id="${rule.id}">${this.escapeHtml(rule.name)}</h4>
                            </div>
                            <div class="b2bking_dynamic_rules_pro_rule_actions">
                                <button class="b2bking_dynamic_rules_pro_rule_toggle" data-rule-id="${rule.id}" data-enabled="${rule.enabled ? '1' : '0'}">
                                    <div class="b2bking_dynamic_rules_pro_toggle_switch ${toggleClass}">
                                        <div class="b2bking_dynamic_rules_pro_toggle_thumb"></div>
                                    </div>
                                </button>
                                <a href="${b2bking_dynamic_rules_pro.admin_url}admin.php?page=b2bking_dynamic_rule_pro_editor&rule_id=${rule.id}" 
                                   class="b2bking_dynamic_rules_pro_rule_edit">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                        <path d="M8.5 1.5L12.5 5.5L4.5 13.5H0.5V9.5L8.5 1.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </a>
                                <button class="b2bking_dynamic_rules_pro_rule_delete" data-rule-id="${rule.id}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                        <path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        <path d="M18.8332 8.5L18.3732 15.3991C18.1962 18.054 18.1077 19.3815 17.2427 20.1907C16.3777 21 15.0473 21 12.3865 21H11.6132C8.95235 21 7.62195 21 6.75694 20.1907C5.89194 19.3815 5.80344 18.054 5.62644 15.3991L5.1665 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        <path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="b2bking_dynamic_rules_pro_rule_content" style="margin-top: 1px !important;">
                            <div class="b2bking_dynamic_rules_pro_rule_description">
                                
                            </div>
                            <div class="b2bking_dynamic_rules_pro_rule_details">
                                <div class="b2bking_dynamic_rules_pro_rule_detail">
                                    <span class="b2bking_dynamic_rules_pro_rule_detail_label">${b2bking.drpro_applies_to}:</span>
                                    <span class="b2bking_dynamic_rules_pro_rule_detail_value">${appliesPreviewMarkup}</span>
                                </div>
                                <div class="b2bking_dynamic_rules_pro_rule_detail">
                                    <span class="b2bking_dynamic_rules_pro_rule_detail_label">${b2bking.drpro_users}:</span>
                                    <span class="b2bking_dynamic_rules_pro_rule_detail_value">${usersPreviewMarkup}</span>
                                </div>
                                <div class="b2bking_dynamic_rules_pro_rule_detail">
                                    <span class="b2bking_dynamic_rules_pro_rule_detail_label">${b2bking.drpro_value}:</span>
                                    <span class="b2bking_dynamic_rules_pro_rule_detail_value">${rule.rule_type === 'info_table' || rule.rule_type === 'replace_prices_quote' || rule.rule_type === 'quotes_products' ? '—' : (rule.how_much_display || rule.how_much || '—')}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }
        }

        getRuleTypeIndicator(ruleType) {
            return `<div class="b2bking_dynamic_rules_pro_rule_type_indicator"></div>`;
        }

        getRuleTypeIcon(ruleType) {
            let iconSvg = '';
            
            switch(ruleType) {
                // Discounts & Pricing
                case 'discount_amount':
                    iconSvg = `<svg viewBox="0 0 256 256" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M77.3,43.8C61.9,38,29.6,33.2,11.8,33.5l1.1-15c20.7-0.2,57.6,5.5,74.5,13.3L77.3,43.8z M194.9,30.1l49.7,187.3l-138.8,36.9 l-49.8-186L77.6,44c2.1,0.8,4.2,1.6,6.3,2.5c13.2,5.5,23.7,12.2,30.6,19.3c-3.8,5.5-5.3,12.5-3.5,19.5c3.3,12.5,16.2,20,28.7,16.6 c12.5-3.3,20-16.2,16.6-28.7c-3.3-12.5-16.2-20-28.7-16.6c-0.3,0.1-0.6,0.2-0.8,0.2c-9.6-10.6-24-19.2-39.1-25.2l25.5-28.9 L194.9,30.1z M191.2,179.5c-4.4-16.5-23.2-16.5-31.4-16.6c-15.4-0.2-18-5-18.6-7.8c-1.2-6.6,5.5-10.1,12.4-10.4 c5.5-0.2,11.6,1.4,15.2,2.9l6.5-13.5c-5.5-1.9-12.5-4.5-21.6-3.8l-2.9-10.7l-13.8,3.7l2.7,10.3c-13,4.6-19.7,15.3-16.6,26.8 c3,11.2,12.5,14.3,21.7,16.1c7.6,1.4,27.4-0.2,28.6,8.6c0.7,4.8-3.4,9.5-11.8,10.7c-7.3,1-16.5-2.9-16.5-2.9l-7.6,13.1 c7.6,3.4,15.1,4.8,22.7,4.3l2.7,10.1l13.8-3.7l-2.5-9.6C186.9,202,194.2,190.5,191.2,179.5z"/>
                    </svg>`;
                    break;
                case 'discount_percentage':
                    iconSvg = `<svg viewBox="0 0 512.001 512.001" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M459.457,77.988c-0.803-9.423-9.093-16.419-18.514-15.61c-60.401,5.139-75.137-5.891-114.517-35.374 c-5.528-4.139-11.246-8.42-17.648-13.076C285.622-2.913,265.206-1.45,252.157,2.747c-13.586,4.368-25.309,13.54-35.168,24.529 l-7.251-5.99c-8.785-7.256-21.484-7.256-30.269,0L61.111,119.056c-5.466,4.515-8.63,11.234-8.63,18.322v350.859 c0,13.125,10.639,23.764,23.764,23.764H312.96c13.125,0,23.765-10.64,23.765-23.764V137.378c0-7.088-3.164-13.807-8.63-18.322 l-84.711-69.975c5.956-6.497,12.442-11.538,19.256-13.73c5.169-1.663,13.49-2.821,25.995,6.274 c6.209,4.517,11.831,8.724,17.266,12.794c35.915,26.889,57.904,43.357,110.412,43.356c8.346,0,17.469-0.417,27.533-1.273 C453.269,95.7,460.258,87.412,459.457,77.988z M193.398,62.002c-8.902,16.808-13.928,31.336-15.049,34.721 c-2.973,8.977,1.894,18.667,10.871,21.64c1.787,0.592,3.602,0.873,5.387,0.873c7.182,0,13.871-4.553,16.254-11.744 c2.232-6.738,7.205-19.024,14.09-31.638c6.093,7.039,9.789,16.212,9.789,26.254c0,22.167-17.97,40.136-40.136,40.136 s-40.136-17.97-40.136-40.136C154.468,80.346,171.791,62.641,193.398,62.002z M106.093,316.027v-34.692 c0-24.186,15.881-32.982,36.646-32.982c20.522,0,36.647,8.796,36.647,32.982v34.692c0,24.186-16.125,32.982-36.647,32.982 C121.975,349.009,106.093,340.214,106.093,316.027z M291.523,360.003v34.692c0,24.186-16.124,32.982-36.646,32.982 c-20.766,0-36.646-8.795-36.646-32.982v-34.692c0-24.186,15.88-32.982,36.646-32.982 C275.4,327.02,291.523,335.815,291.523,360.003z M255.121,251.285c0,1.466-0.244,3.176-0.976,4.641l-85.997,176.146 c-1.71,3.665-6.353,6.107-10.994,6.107c-8.307,0-13.682-6.841-13.682-12.948c0-1.466,0.488-3.176,1.222-4.641l85.752-176.146 c1.954-4.153,5.863-6.107,10.261-6.107C247.548,238.337,255.121,243.468,255.121,251.285z"/>
                        </g>
                        <g>
                            <path d="M142.74,269.608c-7.573,0-11.97,3.665-11.97,11.726v34.692c0,8.062,4.397,11.727,11.97,11.727 c7.574,0,12.217-3.665,12.217-11.727v-34.692C154.956,273.273,150.314,269.608,142.74,269.608z"/>
                        </g>
                        <g>
                            <path d="M254.877,348.275c-7.573,0-11.972,3.665-11.972,11.727v34.692c0,8.062,4.398,11.726,11.972,11.726 c7.573,0,12.216-3.665,12.216-11.726v-34.692C267.093,351.94,262.45,348.275,254.877,348.275z"/>
                        </g>
                    </svg>`;
                    break;
                case 'bogo_discount':
                    iconSvg = `<svg viewBox="0 0 491.564 491.564" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <g>
                                <g>
                                    <path d="M490.673,157.974c-0.047-0.157-0.098-0.312-0.149-0.467c-0.139-0.422-0.293-0.842-0.462-1.261 c-0.061-0.152-0.115-0.307-0.179-0.457L428.446,12.435c-3.227-7.53-10.632-12.413-18.824-12.413H81.942 c-8.193,0-15.597,4.882-18.824,12.413L1.681,155.789c-0.064,0.15-0.118,0.305-0.179,0.457c-0.169,0.419-0.323,0.84-0.462,1.261 c-0.051,0.156-0.102,0.31-0.149,0.467c-0.691,2.274-0.969,4.566-0.869,6.799v306.289c0,11.311,9.169,20.48,20.48,20.48h450.56 c11.311,0,20.48-9.169,20.48-20.48V164.773C491.642,162.54,491.365,160.248,490.673,157.974z M95.447,40.982h300.671 l43.886,102.4H51.561L95.447,40.982z M450.582,450.582h-409.6v-266.24h409.6V450.582z"/>
                                    <path d="M137.384,321.704l5.998-5.998v73.437c0,11.311,9.169,20.48,20.48,20.48s20.48-9.169,20.48-20.48v-73.437l5.998,5.998 c7.998,7.998,20.965,7.998,28.963,0c7.998-7.998,7.998-20.965,0-28.963l-40.959-40.959c-0.477-0.477-0.98-0.929-1.502-1.357 c-0.236-0.194-0.485-0.362-0.727-0.544c-0.292-0.219-0.578-0.445-0.882-0.648c-0.291-0.195-0.594-0.364-0.892-0.542 c-0.275-0.164-0.543-0.337-0.826-0.488c-0.305-0.163-0.619-0.301-0.93-0.448c-0.295-0.139-0.584-0.286-0.886-0.411 c-0.302-0.125-0.611-0.226-0.918-0.336c-0.324-0.116-0.643-0.24-0.974-0.341c-0.307-0.093-0.62-0.161-0.93-0.239 c-0.337-0.085-0.67-0.179-1.013-0.247c-0.359-0.071-0.721-0.113-1.083-0.165c-0.3-0.043-0.595-0.1-0.898-0.13 c-0.672-0.066-1.346-0.102-2.021-0.102l0,0c0,0-0.001,0-0.001,0c-0.674,0-1.348,0.036-2.02,0.102 c-0.304,0.03-0.599,0.087-0.9,0.13c-0.361,0.052-0.723,0.094-1.081,0.165c-0.344,0.068-0.677,0.163-1.015,0.248 c-0.31,0.078-0.622,0.146-0.929,0.239c-0.331,0.1-0.651,0.225-0.975,0.341c-0.306,0.11-0.615,0.211-0.916,0.335 c-0.302,0.125-0.592,0.273-0.887,0.412c-0.311,0.146-0.624,0.284-0.929,0.447c-0.283,0.152-0.552,0.324-0.827,0.489 c-0.298,0.178-0.6,0.347-0.891,0.541c-0.304,0.204-0.591,0.43-0.884,0.65c-0.242,0.181-0.49,0.349-0.726,0.543 c-0.522,0.428-1.025,0.88-1.502,1.357l-40.959,40.959c-7.998,7.998-7.998,20.965,0,28.963 C116.418,329.702,129.386,329.702,137.384,321.704z"/>
                                    <path d="M301.224,321.704l5.998-5.998v73.437c0,11.311,9.169,20.48,20.48,20.48s20.48-9.169,20.48-20.48v-73.437l5.998,5.998 c7.998,7.998,20.965,7.998,28.963,0c7.998-7.998,7.998-20.965,0-28.963l-40.959-40.959c-0.477-0.477-0.979-0.929-1.502-1.357 c-0.237-0.194-0.486-0.363-0.729-0.545c-0.291-0.218-0.577-0.444-0.88-0.647c-0.291-0.195-0.594-0.364-0.893-0.542 c-0.275-0.164-0.543-0.337-0.826-0.488c-0.305-0.163-0.619-0.301-0.93-0.448c-0.295-0.139-0.584-0.286-0.887-0.411 c-0.302-0.125-0.611-0.226-0.917-0.336c-0.324-0.116-0.643-0.241-0.975-0.341c-0.307-0.093-0.619-0.161-0.929-0.239 c-0.337-0.085-0.67-0.179-1.014-0.248c-0.359-0.071-0.721-0.113-1.082-0.165c-0.3-0.043-0.595-0.1-0.899-0.13 c-1.344-0.133-2.698-0.132-4.042,0c-0.304,0.03-0.599,0.087-0.898,0.13c-0.361,0.052-0.724,0.094-1.083,0.165 c-0.343,0.068-0.676,0.162-1.013,0.247c-0.31,0.078-0.623,0.146-0.93,0.239c-0.331,0.1-0.65,0.224-0.974,0.341 c-0.307,0.11-0.616,0.211-0.918,0.336c-0.302,0.125-0.592,0.272-0.886,0.411c-0.311,0.147-0.625,0.285-0.93,0.448 c-0.283,0.151-0.551,0.324-0.826,0.488c-0.299,0.178-0.601,0.347-0.892,0.542c-0.304,0.203-0.59,0.429-0.882,0.648 c-0.242,0.182-0.491,0.35-0.727,0.544c-0.522,0.428-1.025,0.88-1.502,1.357l-40.959,40.959c-7.998,7.998-7.998,20.965,0,28.963 C280.258,329.702,293.226,329.702,301.224,321.704z"/>
                                </g>
                            </g>
                        </g>
                    </svg>`;
                    break;
                case 'tiered_price':
                    iconSvg = `<svg viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <g>
                                <path d="M22,7816 C22,7816.552 21.552,7817 21,7817 L7,7817 C6.448,7817 6,7816.552 6,7816 L6,7802 C6,7801.448 6.448,7801 7,7801 L9,7801 C9.552,7801 10,7801.448 10,7802 L10,7805 C10,7806.105 10.896,7807 12,7807 L15,7807 C15.552,7807 16,7807.448 16,7808 L16,7811 C16,7812.105 16.896,7813 18,7813 L21,7813 C21.552,7813 22,7813.448 22,7814 L22,7816 Z M22,7811 L19,7811 C18.448,7811 18,7810.552 18,7810 L18,7807 C18,7805.895 17.104,7805 16,7805 L12,7805 L12,7801 C12,7799.895 11.104,7799 10,7799 L6,7799 C4.896,7799 4,7799.895 4,7801 L4,7817 C4,7818.105 4.896,7819 6,7819 L22,7819 C23.104,7819 24,7818.105 24,7817 L24,7813 C24,7811.895 23.104,7811 22,7811 L22,7811 Z" transform="translate(-4, -7799)"/>
                            </g>
                        </g>
                    </svg>`;
                    break;
                case 'raise_price':
                    iconSvg = `<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                        <g>
                            <defs>
                                <style>.cls-1,.cls-2{fill:none;stroke:currentColor;stroke-miterlimit:10;stroke-width:1.91px;}.cls-1{stroke-linecap:square;}</style>
                            </defs>
                            <g>
                                <circle class="cls-1" cx="12" cy="15.84" r="2.86"></circle>
                                <polyline class="cls-1" points="9.14 5.34 12 2.48 14.86 5.34"></polyline>
                                <path class="cls-1" d="M12,3.43V7.25a1.91,1.91,0,0,0,1.91,1.91H22.5V22.52H1.5V9.16H8.18"></path>
                                <line class="cls-2" x1="4.36" y1="15.84" x2="6.27" y2="15.84"></line>
                                <line class="cls-2" x1="17.73" y1="15.84" x2="19.64" y2="15.84"></line>
                            </g>
                        </g>
                    </svg>`;
                    break;
                case 'fixed_price':
                    iconSvg = `<svg viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M256,0C114.6,0,0,114.6,0,256s114.6,256,256,256s256-114.6,256-256S397.4,0,256,0z M405.3,341.3c0,11.8-9.5,21.3-21.3,21.3 H128c-11.8,0-21.3-9.6-21.3-21.3v-42.7c0-11.8,9.5-21.3,21.3-21.3h256c11.8,0,21.3,9.6,21.3,21.3V341.3z M405.3,213.3 c0,11.8-9.5,21.3-21.3,21.3H128c-11.8,0-21.3-9.6-21.3-21.3v-42.7c0-11.8,9.5-21.3,21.3-21.3h256c11.8,0,21.3,9.6,21.3,21.3V213.3z"/>
                    </svg>`;
                    break;
                case 'hidden_price':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none">
                        <path d="M1 12S5 4 12 4S23 12 23 12S19 20 12 20S1 12 1 12Z" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                        <path d="M3 3L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>`;
                    break;
                
                // Order Rules
                case 'minimum_order':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <style>.cls-1{fill:none;stroke:currentColor;stroke-miterlimit:10;stroke-width:1.83px;}</style>
                        </defs>
                        <g>
                            <line class="cls-1" x1="14.7" y1="10.17" x2="14.7" y2="2.87"/>
                            <polyline class="cls-1" points="17.43 7.43 14.7 10.17 11.96 7.43"/>
                            <circle class="cls-1" cx="10.13" cy="20.22" r="1.83"/>
                            <circle class="cls-1" cx="18.35" cy="20.22" r="1.83"/>
                            <path class="cls-1" d="M1,2H3.54A2.74,2.74,0,0,1,6.16,3.89l3.06,9.94H8.76a2.28,2.28,0,0,0-2.28,2.28h0a2.28,2.28,0,0,0,2.28,2.28h9.59"/>
                            <polyline class="cls-1" points="22 5.61 22 7.43 20.17 13.83 9.22 13.83"/>
                        </g>
                    </svg>`;
                    break;
                case 'maximum_order':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <style>.cls-1{fill:none;stroke:currentColor;stroke-miterlimit:10;stroke-width:1.91px;}</style>
                        </defs>
                        <g>
                            <line class="cls-1" x1="14.85" y1="2.43" x2="14.85" y2="10.09"/>
                            <polyline class="cls-1" points="11.98 5.3 14.85 2.44 17.72 5.3"/>
                            <circle class="cls-1" cx="10.07" cy="20.61" r="1.91"/>
                            <circle class="cls-1" cx="18.67" cy="20.61" r="1.91"/>
                            <path class="cls-1" d="M.5,1.48H3.16a2.88,2.88,0,0,1,2.75,2l3.2,10.41H8.63A2.39,2.39,0,0,0,6.24,16.3h0a2.39,2.39,0,0,0,2.39,2.4h10"/>
                            <polyline class="cls-1" points="22.5 5.3 22.5 7.22 20.59 13.91 9.11 13.91"/>
                        </g>
                    </svg>`;
                    break;
                case 'required_multiple':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>`;
                    break;
                case 'free_shipping':
                    iconSvg = `<svg viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M102.783,354.362c-19.705,0-35.683,16.888-35.683,37.732c0,20.836,15.978,37.732,35.683,37.732 c19.696,0,35.664-16.896,35.664-37.732C138.447,371.249,122.479,354.362,102.783,354.362z"/>
                            <path d="M501.401,325.542H197.456c-5.855,0-10.598-5.017-10.598-11.2v-167.32c0-6.192-4.744-11.208-10.599-11.208 H79.712c-3.145,0-6.121,1.474-8.134,4.028L2.456,227.573C0.866,229.578,0,232.122,0,234.736v76.956v40.241 c0,6.183,4.752,11.199,10.59,11.199h53.277c8.629-12.93,22.832-21.418,38.916-21.418c16.066,0,30.277,8.488,38.906,21.418h217.418 c8.629-12.93,22.827-21.418,38.92-21.418c16.075,0,30.278,8.488,38.898,21.418h64.476c5.864,0,10.599-5.016,10.599-11.199v-15.175 C512,330.568,507.265,325.542,501.401,325.542z M38.694,243.648v-7.754c0-2.65,0.875-5.22,2.446-7.278l44.136-57.482 c2.076-2.702,5.185-4.265,8.47-4.265h43.235c6.042,0,10.935,5.166,10.935,11.543v65.236c0,6.377-4.893,11.544-10.935,11.544H49.62 C43.579,255.192,38.694,250.025,38.694,243.648z"/>
                            <path d="M398.027,354.362c-19.714,0-35.687,16.888-35.687,37.732c0,20.836,15.973,37.732,35.687,37.732 c19.696,0,35.665-16.896,35.665-37.732C433.692,371.249,417.722,354.362,398.027,354.362z"/>
                            <path d="M331.991,173.387h-12.127c-0.406,0-0.6,0.22-0.6,0.644v16.772c0,0.434,0.194,0.654,0.6,0.654h12.127 c5.653,0,9.301-3.542,9.301-8.991C341.292,176.901,337.644,173.387,331.991,173.387z"/>
                            <path d="M499.299,82.174H214.521c-7.022,0-12.701,6.014-12.701,13.452v200.681c0,7.419,5.679,13.434,12.701,13.434 h284.778c7.013,0,12.701-6.015,12.701-13.434V95.626C512,88.189,506.312,82.174,499.299,82.174z M263.55,173.387 c-0.407,0-0.601,0.22-0.601,0.644v15.28c0,0.424,0.194,0.645,0.601,0.645h19.405c4.337,0,6.871,2.782,6.871,6.73 c0,3.957-2.534,6.739-6.871,6.739H263.55c-0.407,0-0.601,0.212-0.601,0.628v20.738c0,5.044-3.135,8.338-7.384,8.338 c-4.337,0-7.472-3.294-7.472-8.338v-58.02c0-4.187,2.42-6.96,6.465-6.96H287.9c4.151,0,6.881,2.897,6.881,6.845 c0,3.842-2.73,6.731-6.881,6.731H263.55z M344.825,202.029l10.006,19.237c0.698,1.396,1.008,2.897,1.008,4.39 c0,4.063-2.624,7.472-7.075,7.472c-3.029,0-5.555-1.492-7.278-4.91l-11.517-23.406h-10.104c-0.406,0-0.6,0.212-0.6,0.627v19.352 c0,5.044-3.047,8.338-7.375,8.338c-4.355,0-7.482-3.294-7.482-8.338v-58.02c0-4.187,2.42-6.96,6.466-6.96h21.825 c14.149,0,23.441,9.291,23.441,22.548C356.14,191.237,351.9,198.294,344.825,202.029z M407.301,173.387h-24.36 c-0.406,0-0.601,0.22-0.601,0.644v14.432c0,0.433,0.195,0.637,0.601,0.637h19.502c4.151,0,6.783,2.782,6.783,6.73 c0,3.975-2.632,6.748-6.783,6.748h-19.502c-0.406,0-0.601,0.204-0.601,0.627v15.068c0,0.433,0.195,0.654,0.601,0.654h24.36 c4.133,0,6.872,2.87,6.872,6.731c0,3.957-2.739,6.845-6.872,6.845H373.95c-4.032,0-6.461-2.782-6.461-6.952v-58.779 c0-4.187,2.429-6.96,6.461-6.96h33.351c4.133,0,6.872,2.897,6.872,6.845C414.173,170.498,411.434,173.387,407.301,173.387z M464.411,173.387h-24.343c-0.406,0-0.618,0.22-0.618,0.644v14.432c0,0.433,0.212,0.637,0.618,0.637h19.502 c4.133,0,6.765,2.782,6.765,6.73c0,3.975-2.632,6.748-6.765,6.748h-19.502c-0.406,0-0.618,0.204-0.618,0.627v15.068 c0,0.433,0.212,0.654,0.618,0.654h24.343c4.15,0,6.871,2.87,6.871,6.731c0,3.957-2.721,6.845-6.871,6.845h-33.334 c-4.063,0-6.483-2.782-6.483-6.952v-58.779c0-4.187,2.42-6.96,6.483-6.96h33.334c4.15,0,6.871,2.897,6.871,6.845 C471.282,170.498,468.562,173.387,464.411,173.387z"/>
                        </g>
                    </svg>`;
                    break;
                case 'unpurchasable':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 5L19 12H7.37671M20 16H8L6 3H3M11 3L13.5 5.5M13.5 5.5L16 8M13.5 5.5L16 3M13.5 5.5L11 8M9 20C9 20.5523 8.55228 21 8 21C7.44772 21 7 20.5523 7 20C7 19.4477 7.44772 19 8 19C8.55228 19 9 19.4477 9 20ZM20 20C20 20.5523 19.5523 21 19 21C18.4477 21 18 20.5523 18 20C18 19.4477 18.4477 19 19 19C19.5523 19 20 19.4477 20 20Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>`;
                    break;
                
                // Taxes
                case 'tax_exemption_user':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20,2H6A2,2,0,0,0,4,4V15H3a1,1,0,0,0-1,1v2.5A3.5,3.5,0,0,0,5.5,22h13A3.5,3.5,0,0,0,22,18.5V4A2,2,0,0,0,20,2ZM9.29,9.29a1,1,0,0,1,1.42,0L12,10.59l3.29-3.3a1,1,0,1,1,1.42,1.42l-4,4a1,1,0,0,1-1.42,0l-2-2A1,1,0,0,1,9.29,9.29ZM5.5,20A1.5,1.5,0,0,1,4,18.5V17H15v1.5a3.74,3.74,0,0,0,.08.75l.07.22c0,.16.1.32.16.46l0,.07Z"/>
                    </svg>`;
                    break;
                case 'tax_exemption':
                    iconSvg = `<svg viewBox="0 0 32 32" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <defs>
                                <clipPath id="clip-box">
                                    <rect width="32" height="32"></rect>
                                </clipPath>
                            </defs>
                            <g id="box" clip-path="url(#clip-box)">
                                <g id="Group_3126" data-name="Group 3126" transform="translate(-260 -104)">
                                    <g id="Group_3116" data-name="Group 3116">
                                        <g id="Group_3115" data-name="Group 3115">
                                            <g id="Group_3114" data-name="Group 3114">
                                                <path id="Path_3990" data-name="Path 3990" d="M291.858,111.843a.979.979,0,0,0-.059-.257.882.882,0,0,0-.055-.14.951.951,0,0,0-.184-.231.766.766,0,0,0-.061-.077c-.006,0-.014,0-.02-.01a.986.986,0,0,0-.374-.18l-.008,0h0l-14.875-3.377a1.008,1.008,0,0,0-.444,0L260.9,110.944a.984.984,0,0,0-.382.184c-.006.005-.014.005-.02.01-.026.021-.038.054-.062.077a.971.971,0,0,0-.183.231.882.882,0,0,0-.055.14.979.979,0,0,0-.059.257c0,.026-.017.049-.017.076v16.162a1,1,0,0,0,.778.975l14.875,3.377a1,1,0,0,0,.444,0l14.875-3.377a1,1,0,0,0,.778-.975V111.919C291.875,111.892,291.86,111.869,291.858,111.843ZM276,114.27l-3.861-.877L282.328,111l4.029.915Zm-9.2-.038,3.527.8v5.335l-.568-.247a.5.5,0,0,0-.351-.018l-1.483.472-1.125-.836Zm9.2-4.664,4.1.931-10.19,2.389-4.269-.969Zm-13.875,3.6L265.8,114v5.985a.5.5,0,0,0,.2.4l1.532,1.139a.5.5,0,0,0,.3.1.485.485,0,0,0,.151-.023l1.549-.493,1.1.475a.5.5,0,0,0,.7-.459V115.26l3.674.833v14.112l-12.875-2.922Zm27.75,14.112L277,130.205V116.093l12.875-2.922Z" fill="currentColor"></path>
                                            </g>
                                        </g>
                                    </g>
                                    <g id="Group_3119" data-name="Group 3119">
                                        <g id="Group_3118" data-name="Group 3118">
                                            <g id="Group_3117" data-name="Group 3117">
                                                <path id="Path_3991" data-name="Path 3991" d="M278.841,127.452a.508.508,0,0,0,.11-.012l5.613-1.274a.5.5,0,0,0,.39-.488v-6.1a.5.5,0,0,0-.188-.39.5.5,0,0,0-.422-.1l-5.614,1.275a.5.5,0,0,0-.389.488v6.1a.5.5,0,0,0,.5.5Zm.5-6.2,4.613-1.047v5.074l-4.613,1.047Z" fill="currentColor"></path>
                                            </g>
                                        </g>
                                    </g>
                                    <g id="Group_3122" data-name="Group 3122">
                                        <g id="Group_3121" data-name="Group 3121">
                                            <g id="Group_3120" data-name="Group 3120">
                                                <path id="Path_3992" data-name="Path 3992" d="M280.688,123.093a.524.524,0,0,0,.111-.012l1.918-.435a.5.5,0,0,0-.221-.976l-1.918.435a.5.5,0,0,0,.11.988Z" fill="currentColor"></path>
                                            </g>
                                        </g>
                                    </g>
                                    <g id="Group_3125" data-name="Group 3125">
                                        <g id="Group_3124" data-name="Group 3124">
                                            <g id="Group_3123" data-name="Group 3123">
                                                <path id="Path_3993" data-name="Path 3993" d="M282.611,123.7l-2.029.44a.5.5,0,0,0,.106.989.492.492,0,0,0,.107-.011l2.029-.441a.5.5,0,0,0,.382-.594A.493.493,0,0,0,282.611,123.7Z" fill="currentColor"></path>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </g>
                    </svg>`;
                    break;
                case 'add_tax_percentage':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 2H6C3 2 2 3.79 2 6V7V21C2 21.83 2.94 22.3 3.6 21.8L5.31 20.52C5.71 20.22 6.27 20.26 6.63 20.62L8.29 22.29C8.68 22.68 9.32 22.68 9.71 22.29L11.39 20.61C11.74 20.26 12.3 20.22 12.69 20.52L14.4 21.8C15.06 22.29 16 21.82 16 21V4C16 2.9 16.9 2 18 2H7ZM6.57 8.03C7.12 8.03 7.57 8.48 7.57 9.03C7.57 9.58 7.12 10.03 6.57 10.03C6.02 10.03 5.57 9.58 5.57 9.03C5.57 8.48 6.02 8.03 6.57 8.03ZM11.43 14.08C10.87 14.08 10.43 13.63 10.43 13.08C10.43 12.53 10.88 12.08 11.43 12.08C11.98 12.08 12.43 12.53 12.43 13.08C12.43 13.63 11.98 14.08 11.43 14.08ZM12.26 8.8L6.8 14.26C6.65 14.41 6.46 14.48 6.27 14.48C6.08 14.48 5.89 14.41 5.74 14.26C5.45 13.97 5.45 13.49 5.74 13.2L11.2 7.74C11.49 7.45 11.97 7.45 12.26 7.74C12.55 8.03 12.55 8.51 12.26 8.8Z"/>
                        <path d="M18.01 2V3.5C18.67 3.5 19.3 3.77 19.76 4.22C20.24 4.71 20.5 5.34 20.5 6V8.42C20.5 9.16 20.17 9.5 19.42 9.5H17.5V4.01C17.5 3.73 17.73 3.5 18.01 3.5V2ZM18.01 2C16.9 2 16 2.9 16 4.01V11H19.42C21 11 22 10 22 8.42V6C22 4.9 21.55 3.9 20.83 3.17C20.1 2.45 19.11 2.01 18.01 2C18.02 2 18.01 2 18.01 2Z"/>
                    </svg>`;
                    break;
                case 'add_tax_amount':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 13H21V19C21 20.1046 20.1046 21 19 21M17 13V19C17 20.1046 17.8954 21 19 21M17 13V5.75707C17 4.85168 17 4.39898 16.8098 4.13646C16.6439 3.90746 16.3888 3.75941 16.1076 3.72897C15.7853 3.69408 15.3923 3.91868 14.6062 4.36788L14.2938 4.54637C14.0045 4.7117 13.8598 4.79438 13.7062 4.82675C13.5702 4.85539 13.4298 4.85539 13.2938 4.82675C13.1402 4.79438 12.9955 4.7117 12.7062 4.54637L10.7938 3.45359C10.5045 3.28826 10.3598 3.20559 10.2062 3.17322C10.0702 3.14457 9.92978 3.14457 9.79383 3.17322C9.64019 3.20559 9.49552 3.28826 9.20618 3.4536L7.29382 4.54637C7.00448 4.71171 6.85981 4.79438 6.70617 4.82675C6.57022 4.85539 6.42978 4.85539 6.29383 4.82675C6.14019 4.79438 5.99552 4.71171 5.70618 4.54637L5.39382 4.36788C4.60772 3.91868 4.21467 3.69408 3.89237 3.72897C3.61123 3.75941 3.35611 3.90746 3.1902 4.13646C3 4.39898 3 4.85168 3 5.75707V16.2C3 17.8801 3 18.7202 3.32698 19.362C3.6146 19.9264 4.07354 20.3854 4.63803 20.673C5.27976 21 6.11984 21 7.8 21H19M12 10.5C11.5 10.376 10.6851 10.3714 10 10.376C9.77091 10.3775 9.90941 10.3678 9.6 10.376C8.79258 10.4012 8.00165 10.7368 8 11.6875C7.99825 12.7003 9 13 10 13C11 13 12 13.2312 12 14.3125C12 15.1251 11.1925 15.4812 10.1861 15.5991C9.3861 15.5991 9 15.625 8 15.5M10 16V17M10 8.99998V9.99998" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>`;
                    break;
                case 'set_currency_symbol':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <style>.cls-1,.cls-2{fill:none;stroke:currentColor;stroke-miterlimit:10;stroke-width:1.92px;}.cls-1{stroke-linecap:square;}</style>
                        </defs>
                        <g>
                            <circle class="cls-1" cx="12.04" cy="12" r="2.87"/>
                            <path class="cls-2" d="M22.58,6.25v9.58a2.87,2.87,0,0,1-2.87,2.88H7.25l3.83,3.83"/>
                            <line class="cls-2" x1="4.38" y1="12" x2="6.29" y2="12"/>
                            <line class="cls-2" x1="17.79" y1="12" x2="19.71" y2="12"/>
                            <path class="cls-2" d="M1.5,17.75V8.17A2.88,2.88,0,0,1,4.38,5.29H16.83L13,1.46"/>
                        </g>
                    </svg>`;
                    break;
                
                // Advanced Rules
                case 'replace_prices_quote':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.9436 1.25H13.0564C14.8942 1.24998 16.3498 1.24997 17.489 1.40314C18.6614 1.56076 19.6104 1.89288 20.3588 2.64124C20.6516 2.93414 20.6516 3.40901 20.3588 3.7019C20.0659 3.9948 19.591 3.9948 19.2981 3.7019C18.8749 3.27869 18.2952 3.02502 17.2892 2.88976C16.2615 2.75159 14.9068 2.75 13 2.75H11C9.09318 2.75 7.73851 2.75159 6.71085 2.88976C5.70476 3.02502 5.12511 3.27869 4.7019 3.7019C4.27869 4.12511 4.02502 4.70476 3.88976 5.71085C3.75159 6.73851 3.75 8.09318 3.75 10V14C3.75 15.9068 3.75159 17.2615 3.88976 18.2892C4.02502 19.2952 4.27869 19.8749 4.7019 20.2981C5.12511 20.7213 5.70476 20.975 6.71085 21.1102C7.73851 21.2484 9.09318 21.25 11 21.25H13C14.9068 21.25 16.2615 21.2484 17.2892 21.1102C18.2952 20.975 18.8749 20.7213 19.2981 20.2981C19.994 19.6022 20.2048 18.5208 20.2414 15.9892C20.2474 15.575 20.588 15.2441 21.0022 15.2501C21.4163 15.2561 21.7472 15.5967 21.7412 16.0108C21.7061 18.4383 21.549 20.1685 20.3588 21.3588C19.6104 22.1071 18.6614 22.4392 17.489 22.5969C16.3498 22.75 14.8942 22.75 13.0564 22.75H10.9436C9.10583 22.75 7.65019 22.75 6.51098 22.5969C5.33856 22.4392 4.38961 22.1071 3.64124 21.3588C2.89288 20.6104 2.56076 19.6614 2.40314 18.489C2.24997 17.3498 2.24998 15.8942 2.25 14.0564V9.94358C2.24998 8.10582 2.24997 6.65019 2.40314 5.51098C2.56076 4.33856 2.89288 3.38961 3.64124 2.64124C4.38961 1.89288 5.33856 1.56076 6.51098 1.40314C7.65019 1.24997 9.10582 1.24998 10.9436 1.25ZM18.1131 7.04556C19.1739 5.98481 20.8937 5.98481 21.9544 7.04556C23.0152 8.1063 23.0152 9.82611 21.9544 10.8869L17.1991 15.6422C16.9404 15.901 16.7654 16.076 16.5693 16.2289C16.3387 16.4088 16.0892 16.563 15.8252 16.6889C15.6007 16.7958 15.3659 16.8741 15.0187 16.9897L12.9351 17.6843C12.4751 17.8376 11.9679 17.7179 11.625 17.375C11.2821 17.0321 11.1624 16.5249 11.3157 16.0649L11.9963 14.0232C12.001 14.0091 12.0056 13.9951 12.0102 13.9813C12.1259 13.6342 12.2042 13.3993 12.3111 13.1748C12.437 12.9108 12.5912 12.6613 12.7711 12.4307C12.924 12.2346 13.099 12.0596 13.3578 11.8009C13.3681 11.7906 13.3785 11.7802 13.3891 11.7696L18.1131 7.04556ZM20.8938 8.10622C20.4188 7.63126 19.6488 7.63126 19.1738 8.10622L18.992 8.288C19.0019 8.32149 19.0132 8.3571 19.0262 8.39452C19.1202 8.66565 19.2988 9.02427 19.6372 9.36276C19.9757 9.70125 20.3343 9.87975 20.6055 9.97382C20.6429 9.9868 20.6785 9.99812 20.712 10.008L20.8938 9.8262C21.3687 9.35124 21.3687 8.58118 20.8938 8.10622ZM19.5664 11.1536C19.2485 10.9866 18.9053 10.7521 18.5766 10.4234C18.2479 10.0947 18.0134 9.75146 17.8464 9.43357L14.4497 12.8303C14.1487 13.1314 14.043 13.2388 13.9538 13.3532C13.841 13.4979 13.7442 13.6545 13.6652 13.8202C13.6028 13.9511 13.5539 14.0936 13.4193 14.4976L13.019 15.6985L13.3015 15.981L14.5024 15.5807C14.9064 15.4461 15.0489 15.3972 15.1798 15.3348C15.3455 15.2558 15.5021 15.159 15.6468 15.0462C15.7612 14.957 15.8686 14.8513 16.1697 14.5503L19.5664 11.1536ZM7.25 9C7.25 8.58579 7.58579 8.25 8 8.25H14.5C14.9142 8.25 15.25 8.58579 15.25 9C15.25 9.41421 14.9142 9.75 14.5 9.75H8C7.58579 9.75 7.25 9.41421 7.25 9ZM7.25 13C7.25 12.5858 7.58579 12.25 8 12.25H10.5C10.9142 12.25 11.25 12.5858 11.25 13C11.25 13.4142 10.9142 13.75 10.5 13.75H8C7.58579 13.75 7.25 13.4142 7.25 13ZM7.25 17C7.25 16.5858 7.58579 16.25 8 16.25H9.5C9.91421 16.25 10.25 16.5858 10.25 17C10.25 17.4142 9.91421 17.75 9.5 17.75H8C7.58579 17.75 7.25 17.4142 7.25 17Z" fill="currentColor"/>
                        </g>
                    </svg>`;
                    break;
                case 'quotes_products':
                    iconSvg = `<svg viewBox="0 0 32 32" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22,22v6H6V4H16V2H6A2,2,0,0,0,4,4V28a2,2,0,0,0,2,2H22a2,2,0,0,0,2-2V22Z"/>
                        <path d="M29.54,5.76l-3.3-3.3a1.6,1.6,0,0,0-2.24,0l-14,14V22h5.53l14-14a1.6,1.6,0,0,0,0-2.24ZM14.7,20H12V17.3l9.44-9.45,2.71,2.71ZM25.56,9.15,22.85,6.44l2.27-2.27,2.71,2.71Z"/>
                    </svg>`;
                    break;
                case 'payment_method_minmax_order':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M2 8.5H14.5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 16.5H8" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10.5 16.5H14.5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 14.03V16.11C22 19.62 21.11 20.5 17.56 20.5H6.44C2.89 20.5 2 19.62 2 16.11V7.89C2 4.38 2.89 3.5 6.44 3.5H14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M20 9.5V3.5L22 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M20 3.5L18 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                    </svg>`;
                    break;
                case 'payment_method_discount':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M12 6H5C3.89543 6 3 6.89543 3 8V14M21 3L16 9M15 3L15 4M22 8L22 9M3 14V17C3 18.1046 3.89543 19 5 19H19C20.1046 19 21 18.1046 21 17V14H3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                    </svg>`;
                    break;
                case 'rename_purchase_order':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M1.99609 8.5H11.4961" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5.99609 16.5H7.99609" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10.4961 16.5H14.4961" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21.9961 12.03V16.11C21.9961 19.62 21.1061 20.5 17.5561 20.5H6.43609C2.88609 20.5 1.99609 19.62 1.99609 16.11V7.89C1.99609 4.38 2.88609 3.5 6.43609 3.5H14.4961" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M19.0761 4.13006L15.3661 7.84006C15.2261 7.98006 15.0861 8.26006 15.0561 8.46006L14.8561 9.88006C14.7861 10.3901 15.1461 10.7501 15.6561 10.6801L17.0761 10.4801C17.2761 10.4501 17.5561 10.3101 17.6961 10.1701L21.4061 6.46006C22.0461 5.82006 22.3461 5.08006 21.4061 4.14006C20.4561 3.19006 19.7161 3.49006 19.0761 4.13006Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.5461 4.65991C18.8661 5.78991 19.7461 6.66991 20.8661 6.97991" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                    </svg>`;
                    break;
                case 'payment_method_restriction':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M1.89844 10.0303H21.8984" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M11.4584 20.53H6.34839C2.79839 20.53 1.89844 19.65 1.89844 16.14V7.92004C1.89844 4.74004 2.63841 3.72004 5.42841 3.56004C5.70841 3.55004 6.01839 3.54004 6.34839 3.54004H17.4584C21.0084 3.54004 21.9084 4.42004 21.9084 7.93004V12.34" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17.8984 22.0303C20.1076 22.0303 21.8984 20.2394 21.8984 18.0303C21.8984 15.8211 20.1076 14.0303 17.8984 14.0303C15.6893 14.0303 13.8984 15.8211 13.8984 18.0303C13.8984 20.2394 15.6893 22.0303 17.8984 22.0303Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.9694 19.1503L16.8594 17.0303" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.9481 17.0603L16.8281 19.1703" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5.89844 16.0303H9.89844" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                    </svg>`;
                    break;
                case 'shipping_method_restriction':
                    iconSvg = `<svg viewBox="0 0 50 50" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.9902344 1.9902344 A 1.0001 1.0001 0 0 0 2.2929688 3.7070312L46.292969 47.707031 A 1.0001 1.0001 0 1 0 47.707031 46.292969L44.431641 43.017578C45.186337 42.190083 45.71522 41.155276 45.910156 40.001953L47 40.001953C48.542 40.001953 50 38.543953 50 37.001953L50 27.400391C50 25.440391 48.580391 23.358516 48.400391 23.103516L44.28125 17.576172C43.51225 16.614172 41.933 15 40 15L33 15L33 31.585938L31 29.585938L31 10.900391C31 9.3553906 29.644609 8 28.099609 8L9.4140625 8L3.7070312 2.2929688 A 1.0001 1.0001 0 0 0 2.9902344 1.9902344 z M 2.7988281 8C0.82882813 8.156 0 9.765625 0 10.890625L0 37C0 38.654 1.346 40 3 40L7.0839844 40C7.2561215 41.128645 7.7160789 42.217454 8.5019531 43.101562C9.5185588 44.245244 11.083334 45 13 45C15.960386 45 18.427922 42.828339 18.908203 40L31 40L31 35.216797L3.7832031 8L2.7988281 8 z M 38 17L40 17C40.789 17 41.805266 17.682828 42.697266 18.798828L46.779297 24.273438C47.080297 24.705437 47.804797 25.940953 47.966797 27.001953L39 27.001953C38.552 27.000953 38 26.448 38 26L38 17 z M 13 35C15.220986 35 17 36.779015 17 39C17 41.220985 15.220986 43 13 43C11.583334 43 10.648107 42.504756 9.9980469 41.773438C9.3479866 41.042119 9 40.027778 9 39C9 37.972222 9.3479866 36.957881 9.9980469 36.226562C10.648107 35.495244 11.583334 35 13 35 z M 40 35C42.206 35 44 36.794 44 39C44 39.998803 43.622779 40.901784 43.013672 41.599609L37.400391 35.986328C38.098216 35.377221 39.001197 35 40 35 z M 33 37.216797L33 40L34.089844 40C34.567844 42.833 37.032 45 40 45C40.245 45 40.48175 44.964547 40.71875 44.935547L38.478516 42.695312C37.494516 42.289313 36.708734 41.503531 36.302734 40.519531L33 37.216797 z"/>
                    </svg>`;
                    break;
                case 'info_table':
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M3 9.5H21M3 14.5H21M8 4.5V19.5M6.2 19.5H17.8C18.9201 19.5 19.4802 19.5 19.908 19.282C20.2843 19.0903 20.5903 18.7843 20.782 18.408C21 17.9802 21 17.4201 21 16.3V7.7C21 6.5799 21 6.01984 20.782 5.59202C20.5903 5.21569 20.2843 4.90973 19.908 4.71799C19.4802 4.5 18.9201 4.5 17.8 4.5H6.2C5.0799 4.5 4.51984 4.5 4.09202 4.71799C3.71569 4.90973 3.40973 5.21569 3.21799 5.59202C3 6.01984 3 6.57989 3 7.7V16.3C3 17.4201 3 17.9802 3.21799 18.408C3.40973 18.7843 3.71569 19.0903 4.09202 19.282C4.51984 19.5 5.07989 19.5 6.2 19.5Z" stroke="currentColor" stroke-width="2"/>
                        </g>
                    </svg>`;
                    break;
                default:
                    iconSvg = `<svg viewBox="0 0 24 24" fill="none">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>`;
            }
            
            return `<div class="b2bking_dynamic_rules_pro_rule_type_icon">${iconSvg}</div>`;
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
                handle: '.b2bking_dynamic_rules_pro_rule_drag_handle',
                placeholder: 'b2bking_dynamic_rules_pro_rule_placeholder',
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
                    ui.item.addClass('b2bking_dynamic_rules_pro_rule_dragging');
                    
                    // Optimize helper for better performance
                    ui.helper.addClass('b2bking_dynamic_rules_pro_rule_helper');
                    ui.helper.css({
                        'transform': 'rotate(2deg) scale(1.02)',
                        'transition': 'none',
                        'z-index': '9999',
                        'box-shadow': '0 8px 25px rgba(0, 0, 0, 0.15)',
                        'pointer-events': 'none',
                        'will-change': 'transform'
                    });
                    
                    // Disable transitions on all cards during drag for better performance
                    $('.b2bking_dynamic_rules_pro_rule_card').css({
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
                    ui.item.removeClass('b2bking_dynamic_rules_pro_rule_dragging');
                    
                    // Re-enable transitions on all cards
                    $('.b2bking_dynamic_rules_pro_rule_card').css({
                        'transition': '',
                        'will-change': ''
                    });
                    
                    // Get new order efficiently
                    const newOrder = [];
                    $('.b2bking_dynamic_rules_pro_rule_card').each(function(index) {
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
                    action: 'b2bking_dynamic_rules_pro_update_order',
                    nonce: b2bking_dynamic_rules_pro.nonce,
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
            $('.b2bking_dynamic_rules_pro_content_container').removeClass('show').hide();
            $('#rules_grid').hide();
            $('#rules_list').hide();
            $('#rules_empty').hide();
            $('#rules_pagination').hide();
            
            // Show loading container
            if ($('.b2bking_dynamic_rules_pro_loading_container').length === 0) {
                // Create loading container if it doesn't exist
                const loadingHtml = `
                    <div class="b2bking_dynamic_rules_pro_loading_container">
                        <div class="b2bking_dynamic_rules_pro_loading_spinner"></div>
                        <p>${b2bking.grpro_loading_rules}</p>
                    </div>
                `;
                
                // Insert loading container before the content containers
                if ($('.b2bking_dynamic_rules_pro_content_container').length > 0) {
                    $('.b2bking_dynamic_rules_pro_content_container').first().before(loadingHtml);
                } else {
                    // Fallback: append to main container
                    $('#b2bking_dynamic_rules_pro_main_container, .b2bking_dynamic_rules_pro_rules_container').first().append(loadingHtml);
                }
            }
            
            $('.b2bking_dynamic_rules_pro_loading_container').show();
        }

        hideLoading() {
            const elapsedTime = this.loadingStartTime ? Date.now() - this.loadingStartTime : 0;
            const remainingTime = Math.max(0, this.minLoadingTime - elapsedTime);
            
            const showContent = () => {
                // Remove loading class from body
                $('body').removeClass('loading');
                
                // Hide loading container
                $('.b2bking_dynamic_rules_pro_loading_container').hide();
                
                // Show content containers
                $('.b2bking_dynamic_rules_pro_content_container').addClass('show').show();
                
                // Show only the appropriate view container based on current view
                const currentView = $('.b2bking_dynamic_rules_pro_view_btn.active').data('view') || 'grid';
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
                        $('.b2bking_dynamic_rules_pro_pagination_controls').show();
                        $('#rules_pagination').removeClass('single-page');
                    } else {
                        $('.b2bking_dynamic_rules_pro_pagination_controls').hide();
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
                $('.b2bking_dynamic_rules_pro_rule_card').addClass('animate-in');
            }, animationDelay);
        }


        initializeAnimations() {
            // Legacy method - kept for compatibility but not used
            // Add entrance animations to cards
            $('.b2bking_dynamic_rules_pro_rule_card').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.1) + 's',
                    'animation': 'b2bking_dynamic_rules_pro_fadeInUp 0.6s ease forwards'
                });
            });
        }

        // View toggle functionality
        switchView(view) {
            // Update button states
            $('.b2bking_dynamic_rules_pro_view_btn').removeClass('active');
            $(`.b2bking_dynamic_rules_pro_view_btn[data-view="${view}"]`).addClass('active');

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
            localStorage.setItem('b2bking_dynamic_rules_pro_view', view);
        }

        loadSavedView() {
            const savedView = localStorage.getItem('b2bking_dynamic_rules_pro_view') || 'grid';
            this.switchView(savedView);
        }

        loadSavedFilterPreference() {
            const savedFilterState = localStorage.getItem('b2bking_dynamic_rules_pro_filters_enabled');
            this.filtersEnabled = savedFilterState === 'true';
        }

        loadSavedItemsPerPage() {
            const savedItemsPerPage = localStorage.getItem('b2bking_dynamic_rules_pro_items_per_page');
            if (savedItemsPerPage) {
                this.itemsPerPage = parseInt(savedItemsPerPage);
            }
        }

        saveItemsPerPage() {
            localStorage.setItem('b2bking_dynamic_rules_pro_items_per_page', this.itemsPerPage.toString());
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

        renderValuePreview(value) {
            const text = (value || '').trim();
            if (!text) {
                const emptySpan = $('<span></span>')
                    .addClass('b2bking_rulespro_value_item')
                    .text('—')
                    .attr('title', '—');
                return emptySpan.prop('outerHTML');
            }

            const segments = text.split(',').map(segment => segment.trim()).filter(Boolean);
            if (segments.length === 0) {
                segments.push(text);
            }

            const fragments = [];
            segments.forEach((segment, index) => {
                const span = $('<span></span>')
                    .addClass('b2bking_rulespro_value_item')
                    .text(segment)
                    .attr('title', segment);
                fragments.push(span.prop('outerHTML'));
                if (index < segments.length - 1) {
                    fragments.push('<span class="b2bking_rulespro_value_separator">,</span>');
                }
            });

            return fragments.join('');
        }


        cleanConditionText(conditionText) {
            // Remove the "> X" or "< X" part from condition text for display
            return conditionText
                .replace(/ > X$/, '')
                .replace(/ < X$/, '');
        }

        // ===== Bulk Actions Methods =====
        
        toggleBulkActions() {
            const toolbar = $('#b2bking_dynamic_rules_pro_bulk_toolbar');
            const $bulkBtn = $('#bulk_actions');
            const isVisible = toolbar.is(':visible');
            
            if (isVisible) {
                // Hide bulk actions and deselect all
                toolbar.slideUp(200);
                $('.b2bking_dynamic_rules_pro_rule_checkbox').prop('checked', false);
                $('.b2bking_dynamic_rules_pro_rule_card').removeClass('selected');
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
                $('.b2bking_dynamic_rules_pro_bulk_count_number').addClass('no-selection');
                $('.b2bking_dynamic_rules_pro_bulk_selected_count').addClass('no-selection');
                this.updateBulkToolbar();
                $('body').addClass('b2bking_bulk_actions_active');
                $bulkBtn.addClass('active');
            }
        }

        closeBulkActions() {
            const toolbar = $('#b2bking_dynamic_rules_pro_bulk_toolbar');
            const $bulkBtn = $('#bulk_actions');
            if (toolbar.is(':visible')) {
                toolbar.slideUp(200);
                // Deselect all rules when closing bulk actions
                $('.b2bking_dynamic_rules_pro_rule_checkbox').prop('checked', false);
                $('.b2bking_dynamic_rules_pro_rule_card').removeClass('selected');
                this.updateBulkToolbar();
                // Delay removing the class to sync with toolbar animation
                setTimeout(() => {
                    $('body').removeClass('b2bking_bulk_actions_active');
                }, 200);
                $bulkBtn.removeClass('active');
            }
        }

        updateBulkToolbar() {
            const selectedCount = $('.b2bking_dynamic_rules_pro_rule_checkbox:checked').length;
            const totalCount = $('.b2bking_dynamic_rules_pro_rule_checkbox').length;
            
            $('.b2bking_dynamic_rules_pro_bulk_count_number').text(selectedCount);
            
            // Update color states based on selection
            const countElement = $('.b2bking_dynamic_rules_pro_bulk_count_number');
            const textElement = $('.b2bking_dynamic_rules_pro_bulk_selected_count');
            
            if (selectedCount > 0) {
                countElement.removeClass('no-selection').addClass('has-selection');
                textElement.removeClass('no-selection').addClass('has-selection');
            } else {
                countElement.removeClass('has-selection').addClass('no-selection');
                textElement.removeClass('has-selection').addClass('no-selection');
            }
            
            // Enable/disable action buttons based on selection
            const actionButtons = $('.b2bking_dynamic_rules_pro_bulk_action_btn');
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
            $('.b2bking_dynamic_rules_pro_rule_checkbox').prop('checked', false);
            $('.b2bking_dynamic_rules_pro_rule_card').removeClass('selected');
            
            // Update the bulk toolbar to reflect the reset state
            this.updateBulkToolbar();
        }

        selectAllRules() {
            const selectedCount = $('.b2bking_dynamic_rules_pro_rule_checkbox:checked').length;
            const totalCount = $('.b2bking_dynamic_rules_pro_rule_checkbox').length;
            
            if (selectedCount > 0) {
                // Deselect all (if any rules are selected)
                $('.b2bking_dynamic_rules_pro_rule_checkbox').prop('checked', false);
                $('.b2bking_dynamic_rules_pro_rule_card').removeClass('selected');
            } else {
                // Select all (if no rules are selected)
                $('.b2bking_dynamic_rules_pro_rule_checkbox').prop('checked', true);
                $('.b2bking_dynamic_rules_pro_rule_card').addClass('selected');
            }
            
            this.updateBulkToolbar();
        }


        getSelectedRuleIds() {
            const selectedIds = [];
            $('.b2bking_dynamic_rules_pro_rule_checkbox:checked').each(function() {
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
                    action: 'b2bking_dynamic_rules_pro_bulk_enable',
                    nonce: b2bking_dynamic_rules_pro.nonce,
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
                    action: 'b2bking_dynamic_rules_pro_bulk_disable',
                    nonce: b2bking_dynamic_rules_pro.nonce,
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
                    action: 'b2bking_dynamic_rules_pro_bulk_delete',
                    nonce: b2bking_dynamic_rules_pro.nonce,
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
        // Check if we're on the Dynamic Rules Pro page
        if ($('#b2bking_dynamic_rules_pro_main_container').length > 0) {
            new B2BKingGroupRulesPro();
        }
    });

    // Add CSS for form validation
    const style = document.createElement('style');
    style.textContent = `
        .b2bking_dynamic_rules_pro_form_group.error label {
            color: #dc3545;
        }
        
        .b2bking_dynamic_rules_pro_error_message {
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

