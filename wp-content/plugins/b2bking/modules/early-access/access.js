/**
 * B2BKing Early Access Features JavaScript
 */

(function($) {
    'use strict';

    class B2BKingEarlyAccessManager {
        constructor() {
            this.currentCategory = 'all';
            this.features = {};
            this.linkMapping = {
                'group_rules': {
                    old: 'edit.php?post_type=b2bking_grule',
                    new: 'admin.php?page=b2bking_group_rules_pro'
                },
                'dynamic_rules': {
                    old: 'edit.php?post_type=b2bking_rule',
                    new: 'admin.php?page=b2bking_dynamic_rules_pro'
                }
            };
            this.linkStates = {}; // Track which link each feature should use
            this.toggleTimeouts = {}; // Track toggle timeouts to prevent rapid clicking
            this.init();
        }

        init() {
            this.bindEvents();
            this.initializeFeatures();
            this.initializeLinkStates();
            this.interceptNavigation();
            this.animateCards();
        }

        bindEvents() {
            // Category tab switching - use event delegation for AJAX-loaded content
            $(document).on('click', '.b2bking-category-tab', this.handleCategoryChange.bind(this));
            
            // Feature toggle switches - use event delegation for AJAX-loaded content
            $(document).on('change', '.b2bking-feature-enabled', this.handleFeatureToggle.bind(this));
            
            // Feature info buttons - use event delegation for AJAX-loaded content
            $(document).on('click', '.b2bking-feature-info-btn', this.handleFeatureInfo.bind(this));
            
            // Safety info icon - use event delegation for AJAX-loaded content
            $(document).on('click', '.b2bking-info-icon', this.handleSafetyInfo.bind(this));
            
            // Header utility tabs - use event delegation for AJAX-loaded content
            $(document).on('click', '.b2bking-header-utility-tab', this.handleUtilityTab.bind(this));
            
            
            // Modal events
            $('.b2bking-modal-close').on('click', this.closeModal.bind(this));
            $(document).on('click', '.b2bking-modal', function(e) {
                if (e.target === this) {
                    B2BKingEarlyAccess.closeModal();
                }
            });
            
            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboardNavigation.bind(this));
            
            // Feedback form
            $('#b2bking-feedback-form').on('submit', this.handleFeedbackSubmit.bind(this));
            $('#b2bking-feedback-cancel').on('click', this.closeModal.bind(this));
        }

        initializeFeatures() {
            // Initialize features from localized data
            if (typeof b2bking_early_access !== 'undefined' && b2bking_early_access.features_data) {
                b2bking_early_access.features_data.forEach(feature => {
                    this.features[feature.id] = {
                        enabled: feature.enabled,
                        title: feature.title,
                        description: feature.description,
                        categories: feature.categories || (feature.category ? [feature.category] : []),
                        version: feature.version,
                        status: feature.status,
                        impact: feature.impact,
                        notes: feature.notes || ''
                    };
                });
            }
        }

        handleCategoryChange(e) {
            e.preventDefault();
            const $tab = $(e.currentTarget);
            const category = $tab.data('category');
            
            // Update active tab
            $('.b2bking-category-tab').removeClass('active');
            $tab.addClass('active');
            
            this.currentCategory = category;
            this.filterFeatures(category);
        }

        filterFeatures(category) {
            const $cards = $('.b2bking-feature-card');
            
            $cards.each(function() {
                const $card = $(this);
                const cardCategories = $card.data('category').split(' ');
                
                if (category === 'all' || cardCategories.includes(category)) {
                    $card.show().addClass('b2bking-card-visible');
                } else {
                    $card.hide().removeClass('b2bking-card-visible');
                }
            });
            
            // Animate visible cards
            this.animateCards();
            
            // Re-enable hover effects by ensuring CSS transitions are preserved
            setTimeout(() => {
                $('.b2bking-feature-card.b2bking-card-visible').each(function() {
                    const $card = $(this);
                    // Force a reflow to ensure CSS transitions work properly
                    $card[0].offsetHeight;
                });
            }, 50);
        }

        handleFeatureToggle(e) {
            const $toggle = $(e.currentTarget);
            const featureId = $toggle.data('feature-id');
            const enabled = $toggle.is(':checked');
            
            // Check if this feature is currently in a timeout period
            if (this.toggleTimeouts[featureId]) {
                e.preventDefault();
                return false;
            }
            
            // Set timeout for this feature (700ms to match animation duration)
            this.toggleTimeouts[featureId] = true;
            
            // Add disabled class to the toggle switch
            const $toggleSwitch = $toggle.closest('.b2bking-toggle-switch');
            $toggleSwitch.addClass('disabled');
            
            setTimeout(() => {
                delete this.toggleTimeouts[featureId];
                $toggleSwitch.removeClass('disabled');
            }, 700);
            
            // Show status changing animation
            const $card = $toggle.closest('.b2bking-feature-card');
            
            // Add appropriate status changing class based on action
            if (enabled) {
                $card.addClass('b2bking-status-enabling');
            } else {
                $card.addClass('b2bking-status-disabling');
            }
            
            // Update feature status immediately for better UX
            this.updateFeatureStatus(featureId, enabled);
            
            // Send AJAX request
            $.ajax({
                url: typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof b2bking_early_access !== 'undefined' ? b2bking_early_access.ajax_url : ''),
                type: 'POST',
                data: {
                    action: 'b2bking_early_access_toggle_feature',
                    feature_id: featureId,
                    enabled: enabled,
                    // Use main B2BKing nonce if available (for AJAX page switching), otherwise use early access nonce
                    security: typeof b2bking !== 'undefined' ? b2bking.security : (typeof b2bking_early_access !== 'undefined' ? b2bking_early_access.nonce : ''),
                    nonce: typeof b2bking_early_access !== 'undefined' ? b2bking_early_access.nonce : ''
                },
                success: (response) => {
                    if (response.success) {
                        // Delay notification to sync with toggle timeout (700ms)
                        setTimeout(() => {
                            this.showNotification(response.data.message, 'success');
                        }, 700);
                        this.updateStats();
                        // Update link states instead of reloading
                        this.updateLinkStates(featureId, enabled);
                    } else {
                        // Show error immediately for better UX
                        this.showNotification(response.data || b2bking.ea_error_occurred, 'error');
                        // Revert toggle state
                        $toggle.prop('checked', !enabled);
                        this.updateFeatureStatus(featureId, !enabled);
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotification(b2bking.ea_network_error, 'error');
                    // Revert toggle state
                    $toggle.prop('checked', !enabled);
                    this.updateFeatureStatus(featureId, !enabled);
                },
                complete: () => {
                    // Remove status changing animation after 1 second
                    setTimeout(() => {
                        $card.removeClass('b2bking-status-enabling b2bking-status-disabling');
                    }, 1000);
                    // Note: timeout is already set for 2 seconds, so no need to clear it here
                }
            });
        }

        updateFeatureStatus(featureId, enabled) {
            // Update the feature card classes for opacity
            const $card = $(`.b2bking-feature-card[data-feature-id="${featureId}"]`);
            
            if (enabled) {
                $card.removeClass('b2bking-feature-inactive').addClass('b2bking-feature-active');
            } else {
                $card.removeClass('b2bking-feature-active').addClass('b2bking-feature-inactive');
            }
            
            // Update features data
            if (this.features[featureId]) {
                this.features[featureId].enabled = enabled;
            }
        }

        updateStats() {
            const totalFeatures = Object.keys(this.features).length;
            const activeFeatures = Object.values(this.features).filter(f => f.enabled).length;
            
            $('#b2bking-total-features').text(totalFeatures);
            $('#b2bking-active-features').text(activeFeatures);
        }

        handleFeatureInfo(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const featureId = $btn.data('feature-id');
            
            // Show loading state
            $btn.addClass('b2bking-loading');
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2bking_early_access_get_feature_info',
                    feature_id: featureId,
                    // Use main B2BKing nonce if available (for AJAX page switching), otherwise use early access nonce
                    security: typeof b2bking !== 'undefined' ? b2bking.security : (typeof b2bking_early_access !== 'undefined' ? b2bking_early_access.nonce : ''),
                    nonce: typeof b2bking_early_access !== 'undefined' ? b2bking_early_access.nonce : ''
                },
                success: (response) => {
                    if (response.success) {
                        this.showFeatureModal(response.data);
                    } else {
                        this.showNotification(response.data || b2bking.ea_feature_not_found, 'error');
                    }
                },
                error: () => {
                    this.showNotification(b2bking.ea_network_error, 'error');
                },
                complete: () => {
                    $btn.removeClass('b2bking-loading');
                }
            });
        }

        showFeatureModal(feature) {
            const $modal = $('#b2bking-feature-info-modal');
            const $modalTitle = $('#b2bking-modal-title');
            const $modalContent = $('#b2bking-modal-content');
            
            // Update modal title
            $modalTitle.text(feature.title);
            
            // Build modal content
            let content = `
                <div class="b2bking-feature-modal-info">
                    <div class="b2bking-feature-modal-description">
                        <p>${feature.description}</p>
                    </div>
                    
                    <div class="b2bking-feature-modal-details">
                        <div class="b2bking-modal-detail-grid">
                            <div class="b2bking-modal-detail-item">
                                <strong>${b2bking.ea_categories_label}</strong>
                                <span>${feature.categories ? feature.categories.map(cat => this.getCategoryDisplayName(cat)).join(', ') : this.getCategoryDisplayName(feature.category)}</span>
                            </div>
                        </div>
                    </div>
            `;
            
            
            if (feature.notes) {
                content += `
                    <div class="b2bking-feature-modal-notes">
                        <h4>${b2bking.ea_notes_label}</h4>
                        <p>${feature.notes}</p>
                    </div>
                `;
            }
            
            content += '</div>';
            
            $modalContent.html(content);
            $modal.addClass('show');
        }


        handleSafetyInfo(e) {
            e.preventDefault();
            $('#b2bking-safety-info-modal').addClass('show');
        }

        handleUtilityTab(e) {
            e.preventDefault();
            const $tab = $(e.currentTarget);
            const modal = $tab.data('modal');
            
            if (modal === 'safety-info') {
                $('#b2bking-safety-info-modal').addClass('show');
            } else if (modal === 'feedback-form') {
                $('#b2bking-feedback-modal').addClass('show');
            }
        }

        handleFeedbackSubmit(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const $submitBtn = $('#b2bking-feedback-submit');
            const $btnText = $submitBtn.find('.b2bking-btn-text');
            const $btnLoading = $submitBtn.find('.b2bking-btn-loading');
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            
            // Get form data
            const formData = {
                action: 'b2bking_early_access_submit_feedback',
                feedback_type: $('#feedback-type').val(),
                feature_id: $('#feedback-feature').val(),
                message: $('#feedback-message').val(),
                email: $('#feedback-email').val(),
                // Use main B2BKing nonce if available (for AJAX page switching), otherwise use early access nonce
                security: typeof b2bking !== 'undefined' ? b2bking.security : (typeof b2bking_early_access !== 'undefined' ? b2bking_early_access.nonce : ''),
                nonce: typeof b2bking_early_access !== 'undefined' ? b2bking_early_access.nonce : ''
            };
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Thank you for your feedback! We appreciate your input.', 'success');
                        $form[0].reset();
                        this.closeModal();
                    } else {
                        this.showNotification(response.data || 'Failed to send feedback. Please try again.', 'error');
                    }
                },
                error: () => {
                    this.showNotification(b2bking.ea_network_error, 'error');
                },
                complete: () => {
                    // Reset button state
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            });
        }

        closeModal() {
            $('.b2bking-modal').removeClass('show');
        }

        handleKeyboardNavigation(e) {
            // Close modal with Escape key
            if (e.key === 'Escape') {
                this.closeModal();
            }
        }

        showNotification(message, type = 'info') {
            // Use SweetAlert2 Toast for consistent notifications
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // Map notification types to SweetAlert icons
            let icon = 'info';
            if (type === 'success') {
                icon = 'success';
            } else if (type === 'error') {
                icon = 'error';
            } else if (type === 'warning') {
                icon = 'warning';
            }

            Toast.fire({
                icon: icon,
                title: message
            });
        }

        animateCards() {
            $('.b2bking-feature-card.b2bking-card-visible').each(function(index) {
                const $card = $(this);
                setTimeout(() => {
                    $card.addClass('b2bking-card-animate');
                    // Remove animation class after animation completes to prevent interference with hover effects
                    setTimeout(() => {
                        $card.removeClass('b2bking-card-animate');
                    }, 600); // Match the animation duration
                }, index * 100);
            });
        }

        getCategoryDisplayName(category) {
            const categoryNames = {
                'ui': b2bking.ea_category_ui,
                'functionality': b2bking.ea_category_functionality,
                'integration': b2bking.ea_category_integration,
                'performance': b2bking.ea_category_performance
            };
            
            return categoryNames[category] || category.charAt(0).toUpperCase() + category.slice(1);
        }

        initializeLinkStates() {
            // Initialize link states based on current feature states
            Object.keys(this.linkMapping).forEach(featureId => {
                if (this.features[featureId]) {
                    this.linkStates[featureId] = this.features[featureId].enabled ? 'new' : 'old';
                }
            });
        }

        updateLinkStates(featureId, enabled) {
            // Update the link state for a specific feature
            if (this.linkMapping[featureId]) {
                this.linkStates[featureId] = enabled ? 'new' : 'old';
            }
        }

        interceptNavigation() {
            // Intercept clicks on admin menu links
            $(document).on('click', 'a[href*="edit.php?post_type=b2bking_grule"], a[href*="edit.php?post_type=b2bking_rule"], a[href*="admin.php?page=b2bking_group_rules_pro"], a[href*="admin.php?page=b2bking_dynamic_rules_pro"]', (e) => {
                const $link = $(e.currentTarget);
                const href = $link.attr('href');
                
                // Determine which feature this link belongs to
                let featureId = null;
                let currentLinkType = null;
                
                if (href.includes('post_type=b2bking_grule')) {
                    featureId = 'group_rules';
                    currentLinkType = 'old';
                } else if (href.includes('post_type=b2bking_rule')) {
                    featureId = 'dynamic_rules';
                    currentLinkType = 'old';
                } else if (href.includes('page=b2bking_group_rules_pro')) {
                    featureId = 'group_rules';
                    currentLinkType = 'new';
                } else if (href.includes('page=b2bking_dynamic_rules_pro')) {
                    featureId = 'dynamic_rules';
                    currentLinkType = 'new';
                }
                
                if (featureId && this.linkStates[featureId]) {
                    const expectedLinkType = this.linkStates[featureId];
                    
                    // If the clicked link doesn't match the expected state, redirect
                    if (currentLinkType !== expectedLinkType) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const correctHref = this.linkMapping[featureId][expectedLinkType];
                        window.location.href = correctHref;
                        return false;
                    }
                }
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        window.B2BKingEarlyAccess = new B2BKingEarlyAccessManager();
    });

})(jQuery);
