/**
 * B2BKing Grule Individual Editor JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize Grule Individual Editor (Card-Based)
    initGruleIndividualEditor();
});

// Grule Individual Editor Functions
function initGruleIndividualEditor() {
    // Check if we're on the Editor page
    if (jQuery('.b2bking-rule-builder').length === 0) {
        return;
    }

    // Initialize form state
    initializeFormState();
    
    // Bind events
    bindEditorEvents();
    
    // Update preview
    updateRulePreview();
}

function initializeFormState() {
    // Show/hide rolling period field based on condition type
    updateRollingPeriodVisibility();
    
    // Show/hide between values based on operator
    updateBetweenValuesVisibility();
    
    // Update field descriptions
    updateFieldDescription();
    
    // Update source option selection
    updateSourceOptionSelection();
    
    // Update target group selection
    updateTargetGroupSelection();
    
    // Initialize gold border functionality
    initializeGoldBorders();
    
    // Update rule preview
    updateRulePreview();
}

function bindEditorEvents() {
    // Rule name change - immediate preview updates
    var ruleNameTimeout;
    jQuery('#b2bking_rule_name').off('input change keyup').on('input change keyup', function() {
        clearTimeout(ruleNameTimeout);
        ruleNameTimeout = setTimeout(function() {
            updateRulePreview();
        }, 100);
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Condition type change
    jQuery('#b2bking_rule_select_applies').on('change', function() {
        updateRollingPeriodVisibility();
        updateFieldDescription();
        updateRulePreview();
    });
    
    // Operator change
    jQuery('#b2bking_rule_operator').on('change', function() {
        updateBetweenValuesVisibility();
        updateRulePreview();
    });
    
    // Rolling days change - multiple events for immediate feedback
    var rollingDaysTimeout;
    jQuery('#b2bking_rule_rolling_days').off('input change keyup').on('input change keyup', function() {
        clearTimeout(rollingDaysTimeout);
        rollingDaysTimeout = setTimeout(function() {
            updateRulePreview();
        }, 100);
    });
    
    // Threshold values change - multiple events for immediate feedback
    var thresholdTimeout;
    jQuery('#b2bking_rule_threshold, #b2bking_rule_threshold_min, #b2bking_rule_threshold_max').off('input change keyup').on('input change keyup', function() {
        clearTimeout(thresholdTimeout);
        thresholdTimeout = setTimeout(function() {
            updateRulePreview();
        }, 100);
    });
    
    // Group selection
    jQuery('.group-option').on('click', function() {
        jQuery('.group-option').removeClass('selected');
        jQuery(this).addClass('selected');
        var targetValue = jQuery(this).data('group');
        // Sync hidden radios for native validation
        jQuery('input[name="b2bking_rule_target_group"]').prop('checked', false);
        jQuery('input[name="b2bking_rule_target_group"][value="' + targetValue + '"]').prop('checked', true);
        updateRulePreview();
    });
    
    // Source group selection
    jQuery('.source-option').on('click', function() {
        var value = jQuery(this).find('input[type="radio"]').val();
        
        // Remove selected class from all options
        jQuery('.source-option').removeClass('selected');
        
        // Add selected class to clicked option
        jQuery(this).addClass('selected');
        
        // Update radio button selection
        jQuery('input[name="b2bking_rule_source_groups"]').prop('checked', false);
        jQuery(this).find('input[type="radio"]').prop('checked', true);
        // Sync proxy input for native validation
        jQuery('#b2bking_rule_source_groups_proxy').val(value);
        
        updateRulePreview();
    });
    
    // Form submission
    jQuery('#b2bking_group_rule_pro_editor_form').on('submit', function(e) {
        e.preventDefault();
        saveRule();
    });
    
    // Top save button click
    jQuery('#b2bking_group_rule_pro_editor_save_top').on('click', function(e) {
        e.preventDefault();
        
        // Create a temporary submit button to trigger native validation
        var tempSubmitBtn = document.createElement('button');
        tempSubmitBtn.type = 'submit';
        tempSubmitBtn.style.display = 'none';
        jQuery('#b2bking_group_rule_pro_editor_form').append(tempSubmitBtn);
        tempSubmitBtn.click();
        jQuery(tempSubmitBtn).remove();
    });
    
    // Cancel button
    jQuery('#b2bking_group_rule_pro_editor_cancel').on('click', function() {
        if (confirm(b2bking.grpro_confirm_cancel)) {
            if (typeof b2bking !== 'undefined' && b2bking.ajax_pages_load === 'enabled') {
                page_switch('group_rules_pro');
            } else {
                window.location.href = b2bking_group_rule_pro_editor.main_page_url;
            }
        }
    });
    
    // Message close button
    jQuery('.b2bking_group_rule_pro_editor_message_close').on('click', function() {
        jQuery('.b2bking_group_rule_pro_editor_message').fadeOut();
    });
    
    // View affected customers button
    jQuery('#view-affected-customers').on('click', function() {
        alert(b2bking.grpro_affected_customers_preview);
    });
}

function updateRollingPeriodVisibility() {
    var conditionType = jQuery('#b2bking_rule_select_applies').val();
    var rollingGroup = jQuery('#rolling-period-group');
    
    if (conditionType === 'spent_rolling' || conditionType === 'order_count_rolling') {
        rollingGroup.show();
    } else {
        rollingGroup.hide();
    }
}

function updateBetweenValuesVisibility() {
    var operator = jQuery('#b2bking_rule_operator').val();
    var singleGroup = jQuery('#single-value-group');
    var betweenGroup = jQuery('#between-value-group');
    
    if (operator === 'between') {
        singleGroup.hide();
        betweenGroup.show();
    } else {
        singleGroup.show();
        betweenGroup.hide();
    }
}

function updateFieldDescription() {
    var conditionType = jQuery('#b2bking_rule_select_applies').val();
    var prefix = '$';
    
    // Update prefix based on condition type
    if (conditionType && conditionType.includes('order_count')) {
        prefix = '#';
    } else if (conditionType && conditionType.includes('days_since')) {
        prefix = '';
    }
    
    jQuery('#value-prefix, #min-value-prefix, #max-value-prefix').text(prefix);
}

function updateSourceOptionSelection() {
    var selectedValue = jQuery('input[name="b2bking_rule_source_groups"]:checked').val();
    jQuery('.source-option').removeClass('selected');
    jQuery('.source-option input[value="' + selectedValue + '"]').closest('.source-option').addClass('selected');
    // Ensure proxy reflects current selection (or empty)
    jQuery('#b2bking_rule_source_groups_proxy').val(selectedValue || '');
}

function updateTargetGroupSelection() {
    var selectedValue = jQuery('input[name="b2bking_rule_target_group"]:checked').val() || jQuery('#b2bking_rule_target_group').val();
    jQuery('.group-option').removeClass('selected');
    if (selectedValue) {
        jQuery('.group-option[data-group="' + selectedValue + '"]').addClass('selected');
    }
}

function updateRulePreview() {
    var ruleName = jQuery('#b2bking_rule_name').val();
    var conditionType = jQuery('#b2bking_rule_select_applies').val();
    var operator = jQuery('#b2bking_rule_operator').val();
    var rollingDays = jQuery('#b2bking_rule_rolling_days').val();
    var threshold = jQuery('#b2bking_rule_threshold').val();
    var thresholdMin = jQuery('#b2bking_rule_threshold_min').val();
    var thresholdMax = jQuery('#b2bking_rule_threshold_max').val();
    var targetGroup = jQuery('input[name="b2bking_rule_target_group"]:checked').val() || jQuery('#b2bking_rule_target_group').val();
    var sourceGroups = jQuery('input[name="b2bking_rule_source_groups"]:checked').val();
    
    // Build condition text
    var conditionText = '';
    if (conditionType) {
        var conditionLabels = {
            'total_spent': b2bking.grpro_total_amount_spent,
            'spent_rolling': b2bking.grpro_amount_spent_rolling.replace('%d', rollingDays || '90'),
            'spent_yearly': b2bking.grpro_amount_spent_yearly,
            'spent_quarterly': b2bking.grpro_amount_spent_quarterly,
            'spent_monthly': b2bking.grpro_amount_spent_monthly,
            'spent_current_year': b2bking.grpro_amount_spent_current_year,
            'spent_current_quarter': b2bking.grpro_amount_spent_current_quarter,
            'spent_current_month': b2bking.grpro_amount_spent_current_month,
            'order_count_total': b2bking.grpro_total_number_orders,
            'order_count_rolling': b2bking.grpro_number_orders_rolling.replace('%d', rollingDays || '90'),
            'order_count_yearly': b2bking.grpro_number_orders_yearly,
            'order_count_quarterly': b2bking.grpro_number_orders_quarterly,
            'order_count_monthly': b2bking.grpro_number_orders_monthly,
            'order_count_current_year': b2bking.grpro_number_orders_current_year,
            'order_count_current_quarter': b2bking.grpro_number_orders_current_quarter,
            'order_count_current_month': b2bking.grpro_number_orders_current_month,
            'days_since_first_order': b2bking.grpro_days_since_first_order,
            'days_since_last_order': b2bking.grpro_days_since_last_order
        };
        
        conditionText = conditionLabels[conditionType] || conditionType;
    }
    
    // Build operator text
    var operatorText = '';
    if (operator) {
        var operatorLabels = {
            'greater': b2bking.grpro_greater_than,
            'greater_equal': b2bking.grpro_greater_than_or_equal,
            'less': b2bking.grpro_less_than,
            'less_equal': b2bking.grpro_less_than_or_equal,
            'between': b2bking.grpro_between
        };
        
        operatorText = operatorLabels[operator] || operator;
    }
    
    // Build value text
    var valueText = '';
    if (operator === 'between') {
        var prefix = '';
        if (conditionType && conditionType.includes('order_count')) {
            prefix = '#';
        } else if (conditionType && conditionType.includes('spent')) {
            prefix = '$';
        }
        valueText = prefix + (thresholdMin || '0') + ' ' + b2bking.grpro_and + ' ' + prefix + (thresholdMax || '0');
    } else {
        var prefix = '';
        if (conditionType && conditionType.includes('order_count')) {
            prefix = '#';
        } else if (conditionType && conditionType.includes('spent')) {
            prefix = '$';
        }
        valueText = prefix + (threshold || '0');
    }
    
    // Build target group text
    var targetGroupText = '';
    if (targetGroup) {
        var selectedGroup = jQuery('.group-option[data-group="' + targetGroup + '"] .group-name').text();
        targetGroupText = selectedGroup || b2bking.grpro_selected_group;
    }
    
    // Build source groups text
    var sourceGroupsText = '';
    if (sourceGroups === 'all_groups') {
        sourceGroupsText = b2bking.grpro_all_groups;
    } else if (sourceGroups) {
        var selectedSourceGroup = jQuery('.source-option input[value="' + sourceGroups + '"]').closest('.source-option').find('strong').text();
        sourceGroupsText = selectedSourceGroup || b2bking.grpro_selected_group;
    }
    
    // Progressive preview - show what's configured so far
    var previewParts = [];
    var previewText = '';
    
    if (conditionType) {
        previewParts.push('<strong>' + conditionText + '</strong>');
    }
    
    if (operator && conditionType) {
        previewParts.push(b2bking.grpro_is + ' <strong>' + operatorText + '</strong>');
    }
    
    if ((threshold || (thresholdMin && thresholdMax)) && operator && conditionType) {
        previewParts.push('<strong>' + valueText + '</strong>');
    }
    
    if (targetGroup && previewParts.length > 0) {
        previewParts.push(b2bking.grpro_move_to_arrow + ' <strong>' + targetGroupText + '</strong>');
    }
    
    if (sourceGroups && previewParts.length > 0) {
        previewParts.unshift(b2bking.grpro_when + ' <strong>' + sourceGroupsText + '</strong>');
    }
    
    if (previewParts.length > 0) {
        previewText = previewParts.join(' ');
    } else {
        previewText = b2bking.grpro_configure_rule_preview;
    }
    
    // Add rule name if provided, with visual separation
    var fullPreviewText = '';
    if (ruleName && ruleName.trim() !== '') {
        fullPreviewText = '<div style="font-size: 15px; font-weight: 600; color: #191821; margin-bottom: 8px;">' + ruleName + '</div>' + previewText;
    } else {
        fullPreviewText = previewText;
    }
    
    jQuery('#rule-preview-text').html(fullPreviewText);
    
    // Update card summaries
    if (conditionType && operator && (threshold || (thresholdMin && thresholdMax))) {
        jQuery('#condition-summary').text(conditionText + ' ' + operatorText + ' ' + valueText);
    }
    
    if (targetGroup) {
        jQuery('#action-summary').text(b2bking.grpro_move_to.replace('%s', targetGroupText));
    }
    
    if (sourceGroups) {
        if (sourceGroups === 'all_groups') {
            jQuery('#source-summary').text(b2bking.grpro_all_groups);
        } else {
            var selectedSourceGroup = jQuery('.source-option input[value="' + sourceGroups + '"]').closest('.source-option').find('strong').text();
            jQuery('#source-summary').text(selectedSourceGroup || b2bking.grpro_selected_group);
        }
    }
}

// Global submission guard to prevent duplicate submissions
var isSubmitting = false;

function saveRule() {
    // Prevent multiple simultaneous submissions
    if (isSubmitting) {
        //Submission already in progress, ignoring duplicate call
        return;
    }
    
    isSubmitting = true;
    
    // Validate form
    if (!validateGroupRuleForm()) {
        isSubmitting = false; // Reset guard on validation failure
        return;
    }
        
    // Get form data
    var formData = {
        action: 'b2bking_group_rules_pro_save_rule',
        nonce: b2bking_group_rule_pro_editor.nonce,
        rule_name: jQuery('#b2bking_rule_name').val(),
        rule_applies: jQuery('#b2bking_rule_select_applies').val(),
        rule_operator: jQuery('#b2bking_rule_operator').val(),
        rule_threshold: jQuery('#b2bking_rule_threshold').val(),
        rule_threshold_min: jQuery('#b2bking_rule_threshold_min').val(),
        rule_threshold_max: jQuery('#b2bking_rule_threshold_max').val(),
        rule_rolling_days: jQuery('#b2bking_rule_rolling_days').val(),
        rule_source_groups: jQuery('input[name="b2bking_rule_source_groups"]:checked').val(),
        rule_target_group: jQuery('input[name="b2bking_rule_target_group"]:checked').val() || jQuery('#b2bking_rule_target_group').val()
    };
    
    // Add rule ID if editing
    var ruleId = jQuery('input[name="rule_id"]').val();
    if (ruleId) {
        formData.rule_id = ruleId;
    }
    
    // Show loading state for both buttons
    var saveButton = jQuery('#b2bking_group_rule_pro_editor_save');
    var saveButtonTop = jQuery('#b2bking_group_rule_pro_editor_save_top');
    var originalText = saveButton.find('.btn-text').text();
    var loadingStartTime = Date.now();
    var isEditing = jQuery('input[name="rule_id"]').val() ? true : false;
    
    // Disable both buttons
    saveButton.prop('disabled', true);
    saveButtonTop.prop('disabled', true);
    
    // Hide checkmark and show loader for both buttons
    saveButton.find('.btn-checkmark').hide();
    saveButton.find('.btn-loader').show();
    saveButtonTop.find('.btn-checkmark').hide();
    saveButtonTop.find('.btn-loader').show();
    
    // Change text content for both buttons
    saveButton.find('.btn-text').text(isEditing ? b2bking.grpro_updating : b2bking.grpro_creating);
    saveButtonTop.find('.btn-text').text(isEditing ? b2bking.grpro_updating : b2bking.grpro_creating);
    
    // Submit via AJAX
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                showMessageEditor('success', b2bking.grpro_rule_saved_successfully, 1000, true);
                showSaveSuccessNotification();
                scrollEditorToTop();

                var wasNewRule = !ruleId && response.data && response.data.rule_id;
                if (wasNewRule) {
                    var newRuleId = response.data.rule_id;

                    if (!jQuery('input[name="rule_id"]').length) {
                        jQuery('#b2bking_group_rule_pro_editor_form').append('<input type="hidden" name="rule_id" value="' + newRuleId + '">');
                    } else {
                        jQuery('input[name="rule_id"]').val(newRuleId);
                    }

                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('rule_id', newRuleId);
                    window.history.pushState({}, '', currentUrl.toString());

                    jQuery('.b2bking_group_rule_pro_editor_header_text h1').text(b2bking.grpro_edit_group_rule);

                    var updateText = b2bking.grpro_update_rule;
                    var updatingText = b2bking.grpro_updating;

                    jQuery('#b2bking_group_rule_pro_editor_save .btn-text').text(updateText);
                    jQuery('#b2bking_group_rule_pro_editor_save_top .btn-text').text(updateText);
                    jQuery('#b2bking_group_rule_pro_editor_save .btn-loading-text').text(updatingText);
                    jQuery('#b2bking_group_rule_pro_editor_save_top .btn-loading-text').text(updatingText);

                    originalText = updateText;
                }
            } else {
                showMessageEditor('error', response.data || b2bking.grpro_error_saving_rule);
            }
        },
        error: function(xhr, status, error) {
            showMessageEditor('error', b2bking.grpro_error_saving_rule_retry);
        },
        complete: function() {
            // Reset submission guard
            isSubmitting = false;
            
            // Ensure minimum loading time of 0.5 seconds
            var elapsedTime = Date.now() - loadingStartTime;
            var minLoadingTime = 500; // 0.5 seconds
            
            if (elapsedTime < minLoadingTime) {
                setTimeout(function() {
                    restoreButtonState();
                }, minLoadingTime - elapsedTime);
            } else {
                restoreButtonState();
            }
            
            function restoreButtonState() {
                saveButton.prop('disabled', false);
                saveButtonTop.prop('disabled', false);
                saveButton.find('.btn-text').text(originalText);
                saveButtonTop.find('.btn-text').text(originalText);
                // Show checkmark and hide loader for both buttons
                saveButton.find('.btn-checkmark').show();
                saveButton.find('.btn-loader').hide();
                saveButtonTop.find('.btn-checkmark').show();
                saveButtonTop.find('.btn-loader').hide();
            }
        }
    });
}

function validateGroupRuleForm() {
    var form = document.getElementById('b2bking_group_rule_pro_editor_form');

    // Clear previous custom validity
    jQuery('#b2bking_rule_threshold, #b2bking_rule_threshold_min, #b2bking_rule_threshold_max, #b2bking_rule_rolling_days').each(function(){
        this.setCustomValidity('');
    });

    // Conditional validations via customValidity so native bubble is used
    var operator = jQuery('#b2bking_rule_operator').val();
    if (operator === 'between') {
        var minEl = document.getElementById('b2bking_rule_threshold_min');
        var maxEl = document.getElementById('b2bking_rule_threshold_max');
        if (!jQuery(minEl).val()) {
            minEl.setCustomValidity(b2bking.grpro_between_values_required);
        }
        if (!jQuery(maxEl).val()) {
            maxEl.setCustomValidity(b2bking.grpro_between_values_required);
        }
    } else {
        var singleEl = document.getElementById('b2bking_rule_threshold');
        if (!jQuery(singleEl).val()) {
            singleEl.setCustomValidity(b2bking.grpro_threshold_value_required);
        }
    }

    var conditionType = jQuery('#b2bking_rule_select_applies').val();
    if ((conditionType === 'spent_rolling' || conditionType === 'order_count_rolling')) {
        var rollingEl = document.getElementById('b2bking_rule_rolling_days');
        if (!jQuery(rollingEl).val()) {
            rollingEl.setCustomValidity(b2bking.grpro_rolling_period_required);
        }
    }

    // Sync source proxy in case nothing clicked yet
    var selectedSource = jQuery('input[name="b2bking_rule_source_groups"]:checked').val() || '';
    jQuery('#b2bking_rule_source_groups_proxy').val(selectedSource);

    // Use native validation bubbles
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    return true;
}

function scrollEditorToTop() {
    jQuery('html, body').stop(true, false).animate({ scrollTop: 0 }, 300);
}

function showMessageEditor(type, message, time = 3000, hidepreview = false) {
    var previewElement = null;
    var previewWasVisible = false;

    if (hidepreview === true) {
        previewElement = jQuery('.b2bking-rule-preview');
        if (previewElement.length) {
            previewWasVisible = previewElement.is(':visible');
            if (previewWasVisible) {
                previewElement.stop(true, true).fadeOut(150);
            }
        }
    }

    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: time,
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
        }).then(function() {
            if (hidepreview === true && previewElement && previewWasVisible) {
                previewElement.stop(true, true).fadeIn(150);
            }
        });
    } else {
        alert(message);
        if (hidepreview === true && previewElement && previewWasVisible) {
            previewElement.stop(true, true).fadeIn(150);
        }
    }
}

function showSaveSuccessNotification() {
    var header = jQuery('.b2bking_group_rule_pro_editor_header');

    jQuery('.b2bking_group_rule_pro_editor_save_success_notification').remove();

    var notification = jQuery('<div class="b2bking_group_rule_pro_editor_save_success_notification">' +
        '<div class="b2bking_group_rule_pro_editor_save_success_notification_content">' +
        '<svg class="b2bking_group_rule_pro_editor_save_success_notification_icon" width="20" height="20" viewBox="0 0 20 20" fill="none">' +
        '<path d="M16.6667 5L7.50004 14.1667L3.33337 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        '</svg>' +
        '<span class="b2bking_group_rule_pro_editor_save_success_notification_text">' + b2bking.grpro_rule_saved_successfully + '</span>' +
        '</div>' +
        '</div>');

    header.before(notification);

    setTimeout(function() {
        notification.addClass('show');
    }, 10);
}

// Gold Border Functionality - Name and Conditions Only
function initializeGoldBorders() {
    // Check all form elements on initialization
    checkAllFormElements();
    
    // Bind events to name and condition elements only
    bindGoldBorderEvents();
}

function bindGoldBorderEvents() {
    // Rule name input
    jQuery('#b2bking_group_rule_pro_editor_main_container #b2bking_rule_name').on('input change keyup', function() {
        checkElementContent(jQuery(this));
    });
    
    // Condition elements
    jQuery('#b2bking_group_rule_pro_editor_main_container .condition-builder input, #b2bking_group_rule_pro_editor_main_container .condition-builder select').on('input change keyup', function() {
        checkElementContent(jQuery(this));
    });
    
    // Handle dynamically added elements (like conditions)
    jQuery(document).on('input change keyup', '#b2bking_group_rule_pro_editor_main_container .condition-builder input, #b2bking_group_rule_pro_editor_main_container .condition-builder select', function() {
        checkElementContent(jQuery(this));
    });
}

function checkAllFormElements() {
    // Check rule name input
    jQuery('#b2bking_group_rule_pro_editor_main_container #b2bking_rule_name').each(function() {
        checkElementContent(jQuery(this));
    });
    
    // Check condition elements
    jQuery('#b2bking_group_rule_pro_editor_main_container .condition-builder input, #b2bking_group_rule_pro_editor_main_container .condition-builder select').each(function() {
        checkElementContent(jQuery(this));
    });
}

function checkElementContent($element) {
    var hasContent = false;
    var elementType = $element.prop('tagName').toLowerCase();
    var elementValue = $element.val();
    
    if (elementType === 'input') {
        var inputType = $element.attr('type');
        if (inputType === 'checkbox' || inputType === 'radio') {
            hasContent = $element.is(':checked');
        } else {
            hasContent = elementValue && elementValue.trim() !== '';
        }
    } else if (elementType === 'select') {
        // Check if it's not the default "select x" option
        var firstOption = $element.find('option:first');
        var firstOptionText = firstOption.text().toLowerCase();
        var isDefaultOption = firstOptionText.includes('select') || firstOptionText.includes('—') || firstOptionText === '';
        
        if (isDefaultOption) {
            hasContent = elementValue && elementValue !== '' && elementValue !== firstOption.val();
        } else {
            hasContent = elementValue && elementValue !== '';
        }
    }
    
    // Add or remove the has-content class
    if (hasContent) {
        $element.addClass('has-content');
    } else {
        $element.removeClass('has-content');
    }
}
