/**
 * B2BKing Rule Individual Editor JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize Rule Individual Editor  (Card-Based)
    initRuleIndividualEditor();
});

// Also initialize when page loads via AJAX (for B2BKing AJAX page loading)
jQuery(document).on('b2bking_ajax_page_loaded', function() {
    // Re-initialize if we're on the editor page
    if (jQuery('.b2bking-rule-builder').length > 0) {
        initRuleIndividualEditor();
    }
});

// Fallback: Also check after a short delay in case AJAX content was just inserted
setTimeout(function() {
    if (jQuery('.b2bking-rule-builder').length > 0 && !jQuery('#b2bking_rule_countries_pro').hasClass('select2-hidden-accessible')) {
        // If editor is present but countries Select2 isn't initialized, initialize it
        var ruleType = jQuery('#b2bking_rule_select_what_pro').val();
        if (ruleType === 'tax_exemption_user' || ruleType === 'tax_exemption') {
            if (jQuery('#countries-card').is(':visible')) {
                initializeCountriesSelect2();
            }
        }
    }
}, 500);

// Rule Individual Editor  Functions
function initRuleIndividualEditor() {
    // Check if we're on the Editor  page
    if (jQuery('.b2bking-rule-builder').length === 0) {
        return;
    }

    // Initialize form state
    initializeFormState();
    
    // Bind events
    bindEditorEvents();
    
    // Update card numbers after everything is initialized
    // The debounced function in showHideDiscountOptions will handle this
    // But ensure it runs after all initialization is complete
    setTimeout(function() {
        updateCardNumbers();
    }, 100);
    
    // Update preview with fallback retries to capture delayed field rendering
    refreshPreviewReliably({ attempts: 4, delay: 250 });
}

function initializeFormState() {
    // Initialize show/hide states
    showHideDiscountOptions();
    showHideRuleTypeDescription();
    showHideSearchContainer();
    showHideUserSelectors();
    
    // Initialize Select2 for search functionality
    initializeSelect2Search();
    initializeSpecificUsersSelector();
    initializeMultipleOptionsSelector();
    
    // Countries Select2 initialization is handled in showHideDiscountOptions()
    
    // Initialize conditional visibility for Pay Tax in Cart on page load
    setTimeout(function() {
        var showtaxValue = jQuery('#b2bking_rule_showtax_pro').val();
        if (showtaxValue === 'display_only') {
            jQuery('#tax-shipping-container').show();
            var taxShippingValue = jQuery('#b2bking_rule_tax_shipping_pro').val();
            if (taxShippingValue === 'yes') {
                jQuery('#tax-shipping-rate-container').show();
            }
        }
    }, 100);
    
    // Initialize card toggles
    initializeCardToggles();
    
    // Initialize existing conditions
    initializeExistingConditions();
    
    // Initialize price tiers handlers
    initializePriceTiers();
    
    // Initialize info table rows handlers
    initializeInfoTableRows();
    
    // Initialize help tips
    initializeHelpTips();
    
    // Initialize gold border functionality
    initializeGoldBorders();
    
    // Initialize custom dropdown with icons
    initializeCustomRuleTypeDropdown();
    
    // Update rule preview
    updateRulePreview();
}

// Show/hide rule type description
function showHideRuleTypeDescription() {
    var ruleType = jQuery('#b2bking_rule_select_what_pro').val();
    var $description = jQuery('#b2bking_rule_type_description');
    
    // Return early if description container doesn't exist yet
    if ($description.length === 0) {
        return;
    }
    
    // Define descriptions for rule types that need them
    var descriptions = {
        'free_shipping': b2bking.drpro_free_shipping_description,
        'payment_method_restriction': b2bking.drpro_payment_method_restriction_description,
        'shipping_method_restriction': b2bking.drpro_shipping_method_restriction_description
    };
    
    if (descriptions[ruleType]) {
        // Info icon SVG
        var infoIcon = '<svg class="b2bking-rule-type-description-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 16V12M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        $description.html('<span class="b2bking-rule-type-description-icon-wrapper">' + infoIcon + '</span><span class="b2bking-rule-type-description-text">' + descriptions[ruleType] + '</span>').show();
    } else {
        $description.hide();
    }
}

function bindEditorEvents() {
    // Rule name change - immediate preview updates
    var ruleNameTimeout;
    jQuery('#b2bking_rule_name_pro').off('input change keyup').on('input change keyup', function() {
        clearTimeout(ruleNameTimeout);
        clearCardError(jQuery(this));
        ruleNameTimeout = setTimeout(function() {
            updateRulePreview();
        }, 100);
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Rule type change
    jQuery('#b2bking_rule_select_what_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        // Update card visibility first
        showHideDiscountOptions();
        showHideRuleTypeDescription();
        // Update preview after cards are hidden/shown (with small delay to ensure DOM updates)
        setTimeout(function() {
            updateRulePreview();
        }, 10);
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Applies to change
    jQuery('#b2bking_rule_select_applies_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        showHideSearchContainer();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Action grid selection for Applies To
    jQuery('.applies-option').off('click').on('click', function() {
        var appliesValue = jQuery(this).data('applies');
        
        // Remove selected class from all options in this grid
        jQuery(this).closest('.action-grid').find('.applies-option').removeClass('selected');
        
        // Add selected class to clicked option
        jQuery(this).addClass('selected');
        
        // Update the hidden dropdown
        jQuery('#b2bking_rule_select_applies_pro').val(appliesValue);
        
        // Trigger change event to update preview and show/hide containers
        jQuery('#b2bking_rule_select_applies_pro').trigger('change');
        
        // Check for gold border
        checkElementContent(jQuery('#b2bking_rule_select_applies_pro'));
    });
    
    // Action grid selection for Quantity/Value
    jQuery('[data-quantity-value]').off('click').on('click', function() {
        var quantityValue = jQuery(this).data('quantity-value');
        
        // Remove selected class from all options in this grid
        jQuery(this).closest('.action-grid').find('.applies-option').removeClass('selected');
        
        // Add selected class to clicked option
        jQuery(this).addClass('selected');
        
        // Update the hidden dropdown
        jQuery('#b2bking_rule_quantity_value_pro').val(quantityValue);
        
        // Trigger change event (this will also update the preview via the change handler)
        jQuery('#b2bking_rule_quantity_value_pro').trigger('change');
        
        // Update preview immediately
        updateRulePreview();
        
        // Check for gold border
        checkElementContent(jQuery('#b2bking_rule_quantity_value_pro'));
    });
    
    // Action grid selection for Payment Method Min/Max
    jQuery('[data-payment-minmax]').off('click').on('click', function() {
        var paymentMinmax = jQuery(this).data('payment-minmax');
        
        // Remove selected class from all options in this grid
        jQuery(this).closest('.action-grid').find('.applies-option').removeClass('selected');
        
        // Add selected class to clicked option
        jQuery(this).addClass('selected');
        
        // Update the hidden dropdown
        jQuery('#b2bking_rule_paymentmethod_minmax_pro').val(paymentMinmax);
        
        // Trigger change event
        jQuery('#b2bking_rule_paymentmethod_minmax_pro').trigger('change');
        
        // Check for gold border
        checkElementContent(jQuery('#b2bking_rule_paymentmethod_minmax_pro'));
    });
    
    // Action grid selection for Amount/Percentage
    jQuery('[data-payment-percentamount]').off('click').on('click', function() {
        var percentamount = jQuery(this).data('payment-percentamount');
        
        // Remove selected class from all options in this grid
        jQuery(this).closest('.action-grid').find('.applies-option').removeClass('selected');
        
        // Add selected class to clicked option
        jQuery(this).addClass('selected');
        
        // Update the hidden dropdown
        jQuery('#b2bking_rule_paymentmethod_percentamount_pro').val(percentamount);
        
        // Trigger change event
        jQuery('#b2bking_rule_paymentmethod_percentamount_pro').trigger('change');
        
        // Update preview
        updateRulePreview();
        
        // Check for gold border
        checkElementContent(jQuery('#b2bking_rule_paymentmethod_percentamount_pro'));
    });
    
    // Payment method percent/amount change
    jQuery('#b2bking_rule_paymentmethod_percentamount_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        checkElementContent(jQuery(this));
    });
    
    // Action grid selection for Discount/Surcharge
    jQuery('[data-payment-discountsurcharge]').off('click').on('click', function() {
        var discountsurcharge = jQuery(this).data('payment-discountsurcharge');
        
        // Remove selected class from all options in this grid
        jQuery(this).closest('.action-grid').find('.applies-option').removeClass('selected');
        
        // Add selected class to clicked option
        jQuery(this).addClass('selected');
        
        // Update the hidden dropdown
        jQuery('#b2bking_rule_paymentmethod_discountsurcharge_pro').val(discountsurcharge);
        
        // Trigger change event
        jQuery('#b2bking_rule_paymentmethod_discountsurcharge_pro').trigger('change');
        
        // Check for gold border
        checkElementContent(jQuery('#b2bking_rule_paymentmethod_discountsurcharge_pro'));
    });
    
    // Customer group change
    jQuery('#b2bking_rule_select_who_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        showHideUserSelectors();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Currency change
    jQuery('#b2bking_rule_currency_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Payment method change
    jQuery('#b2bking_rule_paymentmethod_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Payment method min/max change
    jQuery('#b2bking_rule_paymentmethod_minmax_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Quantity/Value change (for minimum_order and maximum_order rules)
    jQuery('#b2bking_rule_quantity_value_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Shipping method change
    jQuery('#b2bking_rule_shippingmethod_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Payment method name change
    jQuery('#b2bking_rule_paymentmethod_name_pro').off('input change').on('input change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Countries change (multiple select)
    jQuery('#b2bking_rule_countries_pro').off('change').on('change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Countries select all buttons
    var countriesSelectEU = jQuery('.b2bking-select-eu-countries');
    var countriesSelectNonEU = jQuery('.b2bking-select-non-eu-countries');
    var countriesSelectElement = jQuery('#b2bking_rule_countries_pro');
    
    // EU countries list (must match PHP array)
    var euCountriesList = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'];
    
    // Select all EU countries button
    countriesSelectEU.off('click').on('click', function(e) {
        e.preventDefault();
        var currentValues = countriesSelectElement.val() || [];
        var allValues = countriesSelectElement.find('option').map(function() {
            return jQuery(this).val();
        }).get();
        
        // Get all non-EU countries that are currently selected
        var nonEUValues = jQuery.grep(allValues, function(value) {
            return euCountriesList.indexOf(value) === -1 && currentValues.indexOf(value) !== -1;
        });
        
        // Combine EU countries with currently selected non-EU countries
        var newValues = euCountriesList.concat(nonEUValues);
        countriesSelectElement.val(newValues).trigger('change');
    });
    
    // Select all non-EU countries button
    countriesSelectNonEU.off('click').on('click', function(e) {
        e.preventDefault();
        var currentValues = countriesSelectElement.val() || [];
        var allValues = countriesSelectElement.find('option').map(function() {
            return jQuery(this).val();
        }).get();
        
        // Get all EU countries that are currently selected
        var euValues = jQuery.grep(currentValues, function(value) {
            return euCountriesList.indexOf(value) !== -1;
        });
        
        // Get all non-EU countries
        var nonEUValues = jQuery.grep(allValues, function(value) {
            return euCountriesList.indexOf(value) === -1;
        });
        
        // Combine currently selected EU countries with all non-EU countries
        var newValues = euValues.concat(nonEUValues);
        countriesSelectElement.val(newValues).trigger('change');
    });
    
    // Requires change
    jQuery('#b2bking_rule_requires_pro').off('change').on('change', function() {
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Pay Tax in Cart change (with conditional show/hide)
    jQuery('#b2bking_rule_showtax_pro').off('change').on('change', function() {
        var showtaxValue = jQuery(this).val();
        var taxShippingContainer = jQuery('#tax-shipping-container');
        
        if (showtaxValue === 'display_only') {
            taxShippingContainer.show();
            // Check current shipping value to show/hide shipping rate
            var taxShippingValue = jQuery('#b2bking_rule_tax_shipping_pro').val();
            if (taxShippingValue === 'yes') {
                jQuery('#tax-shipping-rate-container').show();
            } else {
                jQuery('#tax-shipping-rate-container').hide();
            }
        } else {
            taxShippingContainer.hide();
            jQuery('#tax-shipping-rate-container').hide();
        }
        
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Tax Shipping change (conditional show/hide for shipping rate)
    jQuery('#b2bking_rule_tax_shipping_pro').off('change').on('change', function() {
        var taxShippingValue = jQuery(this).val();
        if (taxShippingValue === 'yes') {
            jQuery('#tax-shipping-rate-container').show();
        } else {
            jQuery('#tax-shipping-rate-container').hide();
        }
        
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Tax Shipping Rate change
    jQuery('#b2bking_rule_tax_shipping_rate_pro').off('input change').on('input change', function() {
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Tax Name change
    jQuery('#b2bking_rule_taxname_pro').off('input change').on('input change', function() {
        clearCardError(jQuery(this));
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Taxable change
    jQuery('#b2bking_rule_tax_taxable_pro').off('change').on('change', function() {
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // How much change - multiple events for immediate feedback
    var howMuchTimeout;
    jQuery('#b2bking_rule_select_howmuch_pro').off('input change keyup').on('input change keyup', function() {
        clearCardError(jQuery(this));
        clearTimeout(howMuchTimeout);
        howMuchTimeout = setTimeout(function() {
            updateRulePreview();
        }, 100); // Small delay to prevent excessive updates while typing
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Multiple options selector change
    jQuery('.b2bking-pro-select-multiple-options').off('change').on('change', function() {
        var selectedValues = jQuery(this).val();
        jQuery('#b2bking_rule_who_multiple_options_pro').val(selectedValues ? selectedValues.join(',') : '');
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Specific users selector change
    jQuery('#b2bking_specific_users_selector_pro').off('change').on('change', function() {
        var selectedValues = jQuery(this).val();
        // Format user IDs with 'user_' prefix
        var formattedValues = selectedValues ? selectedValues.map(function(id) {
            return 'user_' + id;
        }).join(',') : '';
        jQuery('#b2bking_rule_who_multiple_options_pro').val(formattedValues);
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Content selector change
    jQuery('#b2bking_content_selector_pro').off('change').on('change', function() {
        var selectedValues = jQuery(this).val();
        jQuery('#b2bking_rule_select_applies_multiple_options_pro').val(selectedValues ? selectedValues.join(',') : '');
        updateRulePreview();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Condition changes
    jQuery('.b2bking_pro_rule_condition_name, .b2bking_pro_rule_condition_operator, .b2bking_pro_rule_condition_number').off('change input').on('change input', function() {
        updateConditionsHiddenField();
        // Check for gold border
        checkElementContent(jQuery(this));
    });
    
    // Add condition button (use off() to prevent duplicate bindings)
    jQuery('.b2bking_pro_rule_condition_add_button').off('click').on('click', function() {
        addNewCondition();
    });
    
    // Form submission (prevent default browser validation)
    jQuery('#b2bking_dynamic_rule_pro_editor_form').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // Trigger form validation and save directly (same as top button)
        if (validateDynamicRuleForm()) {
            saveRule();
        }
    });
    
    // Top save button click
    jQuery('#b2bking_dynamic_rule_pro_editor_save_top').off('click').on('click', function(e) {
        e.preventDefault();
        
        // Trigger form validation and save directly
        if (validateDynamicRuleForm()) {
            saveRule();
        }
    });
    
    // Bottom save button click (also handle directly to ensure same behavior)
    jQuery('#b2bking_dynamic_rule_pro_editor_save').off('click').on('click', function(e) {
        e.preventDefault();
        
        // Trigger form validation and save directly (same as top button)
        if (validateDynamicRuleForm()) {
            saveRule();
        }
    });
    
    // Cancel button
    jQuery('#b2bking_dynamic_rule_pro_editor_cancel').on('click', function() {
        if (confirm(b2bking.grpro_confirm_cancel)) {
            if (typeof b2bking !== 'undefined' && b2bking.ajax_pages_load === 'enabled') {
                page_switch('dynamic_rules_pro');
            } else {
                window.location.href = b2bking_dynamic_rule_pro_editor.main_page_url;
            }
        }
    });
    
    // Message close button
    jQuery('.b2bking_dynamic_rule_pro_editor_message_close').on('click', function() {
        jQuery('.b2bking_dynamic_rule_pro_editor_message').fadeOut();
    });
    
    // View affected customers button
    jQuery('#view-affected-customers').on('click', function() {
        alert(b2bking.grpro_affected_customers_preview);
    });
    
    // Toggle group click handler - make entire toggle-group clickable
    jQuery('.form-group.toggle-group').off('click').on('click', function(e) {
        // Don't trigger if clicking directly on the checkbox or label
        if (jQuery(e.target).is('input[type="checkbox"]') || jQuery(e.target).closest('label').length > 0) {
            return;
        }
        
        // Find the checkbox within this toggle-group and toggle it
        var $checkbox = jQuery(this).find('input[type="checkbox"]');
        if ($checkbox.length > 0) {
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
        }
    });
}


// Helper function to check if a card is visible
function isCardVisible(cardId) {
    var $card = jQuery(cardId);
    if (!$card.length) {
        return false;
    }
    // Check multiple visibility conditions
    return $card.is(':visible') && 
           $card.css('display') !== 'none' && 
           !$card.hasClass('hidden') &&
           $card.outerWidth() > 0 &&
           $card.outerHeight() > 0;
}

// Manage preview refresh retries so the preview reflects pre-filled data reliably
var previewRefreshFallbackTimers = [];

function clearPreviewRefreshFallbackTimers() {
    while (previewRefreshFallbackTimers.length) {
        clearTimeout(previewRefreshFallbackTimers.pop());
    }
}

function refreshPreviewReliably(options) {
    options = options || {};
    var attempts = Math.max(1, options.attempts || 3);
    var delay = options.delay || 200;
    
    clearPreviewRefreshFallbackTimers();
    
    for (var i = 0; i < attempts; i++) {
        (function(attemptIndex) {
            var timeoutId = setTimeout(function() {
                updateRulePreview();
            }, delay * attemptIndex);
            previewRefreshFallbackTimers.push(timeoutId);
        })(i);
    }
}

function scrollEditorToTop() {
    jQuery('html, body').stop(true, false).animate({ scrollTop: 0 }, 300);
}

function updateRulePreview() {
    var ruleName = jQuery('#b2bking_rule_name_pro').val();
    var ruleType = jQuery('#b2bking_rule_select_what_pro').val();
    
    // Only read values from visible cards
    var appliesTo = isCardVisible('#applies-to-card') ? jQuery('#b2bking_rule_select_applies_pro').val() : '';
    var customerGroup = isCardVisible('#for-who-card') ? jQuery('#b2bking_rule_select_who_pro').val() : '';
    var howMuch = isCardVisible('#how-much-card') ? jQuery('#b2bking_rule_select_howmuch_pro').val() : '';
    
    
    // Build rule type text
    var ruleTypeText = '';
    if (ruleType) {
        var ruleTypeLabels = {
            'discount_amount': b2bking.drpro_rule_type_discount_amount,
            'discount_percentage': b2bking.drpro_rule_type_discount_percentage,
            'raise_price': b2bking.drpro_rule_type_raise_price,
            'bogo_discount': b2bking.drpro_rule_type_bogo_discount,
            'fixed_price': b2bking.drpro_rule_type_fixed_price,
            'hidden_price': b2bking.drpro_rule_type_hidden_price,
            'tiered_price': b2bking.drpro_rule_type_tiered_price,
            'free_shipping': b2bking.drpro_rule_type_free_shipping,
            'minimum_order': b2bking.drpro_rule_type_minimum_order,
            'maximum_order': b2bking.drpro_rule_type_maximum_order,
            'required_multiple': b2bking.drpro_rule_type_required_multiple,
            'unpurchasable': b2bking.drpro_rule_type_unpurchasable,
            'tax_exemption_user': b2bking.drpro_rule_type_tax_exemption_user,
            'tax_exemption': b2bking.drpro_rule_type_tax_exemption,
            'add_tax_percentage': b2bking.drpro_rule_type_add_tax_percentage,
            'add_tax_amount': b2bking.drpro_rule_type_add_tax_amount,
            'replace_prices_quote': b2bking.drpro_rule_type_replace_prices_quote,
            'quotes_products': b2bking.drpro_rule_type_quotes_products,
            'set_currency_symbol': b2bking.drpro_rule_type_set_currency_symbol,
            'payment_method_minmax_order': b2bking.drpro_rule_type_payment_method_minmax_order,
            'payment_method_discount': b2bking.drpro_rule_type_payment_method_discount,
            'payment_method_restriction': b2bking.drpro_rule_type_payment_method_restriction,
            'shipping_method_restriction': b2bking.drpro_rule_type_shipping_method_restriction,
            'rename_purchase_order': b2bking.drpro_rule_type_rename_purchase_order,
            'info_table': b2bking.drpro_rule_type_info_table
        };
        
        ruleTypeText = ruleTypeLabels[ruleType] || ruleType;
    }
    
    // For set_currency_symbol rule, track if we need special formatting
    var setCurrencyWithCode = false;
    var currencyCodeText = '';
    if (ruleType === 'set_currency_symbol') {
        var currencySelector = jQuery('#b2bking_rule_currency_pro');
        var selectedCurrencyValue = currencySelector.val();
        if (selectedCurrencyValue) {
            // Get the option text which contains "CHF → Fr" format
            var selectedOption = currencySelector.find('option[value="' + selectedCurrencyValue + '"]');
            if (selectedOption.length) {
                var optionText = selectedOption.text();
                // Extract currency code (part before " → ")
                var currencyCode = optionText.split(' → ')[0].trim();
                if (currencyCode) {
                    setCurrencyWithCode = true;
                    currencyCodeText = currencyCode;
                }
            }
        }
    }
    
    // Build applies to text (only if applies-to card is visible)
    var appliesToText = '';
    if (appliesTo && isCardVisible('#applies-to-card')) {
        if (appliesTo === 'cart_total') {
            appliesToText = b2bking.drpro_cart_total_all_products;
        } else if (appliesTo === 'multiple_options') {
            // Check if specific items are selected
            var selectedItems = jQuery('#b2bking_content_selector_pro').val();
            if (selectedItems && selectedItems.length > 0) {
                if (selectedItems.length <= 2) {
                    // Show actual item names for 1-2 items
                    var itemNames = selectedItems.map(function(itemId) {
                        var option = jQuery('#b2bking_content_selector_pro option[value="' + itemId + '"]');
                        return option.text() || b2bking.drpro_item + ' ' + itemId;
                    });
                    appliesToText = itemNames.join(', ');
                } else {
                    // Too many items, show generic
                    appliesToText = b2bking.drpro_specific_items;
                }
                } else {
                    appliesToText = b2bking.drpro_specific_items;
                }
        } else if (appliesTo === 'excluding_multiple_options') {
            // Check if specific items are selected for exclusion
            var selectedItems = jQuery('#b2bking_content_selector_pro').val();
            if (selectedItems && selectedItems.length > 0) {
                if (selectedItems.length <= 2) {
                    // Show actual item names for 1-2 items
                    var itemNames = selectedItems.map(function(itemId) {
                        var option = jQuery('#b2bking_content_selector_pro option[value="' + itemId + '"]');
                        return option.text() || b2bking.drpro_item + ' ' + itemId;
                    });
                    appliesToText = b2bking.drpro_all_products_except + ' ' + itemNames.join(', ');
                } else {
                    // Too many items, show generic
                    appliesToText = b2bking.drpro_all_products_except_specific;
                }
            } else {
                appliesToText = b2bking.drpro_all_products_except_specific;
            }
        } else {
            appliesToText = appliesTo;
        }
    }
    
    // Build customer group text (only if for-who card is visible)
    var customerGroupText = '';
    if (customerGroup && isCardVisible('#for-who-card')) {
        if (customerGroup === 'all_registered') {
            customerGroupText = b2bking.drpro_all_logged_in_users;
        } else if (customerGroup === 'everyone_registered_b2b') {
            customerGroupText = b2bking.drpro_b2b_customers_logged_in;
        } else if (customerGroup === 'everyone_registered_b2c') {
            customerGroupText = b2bking.drpro_b2c_customers_logged_in;
        } else if (customerGroup === 'user_0') {
            customerGroupText = b2bking.drpro_guest_visitors;
        } else if (customerGroup === 'multiple_options') {
            // Check if multiple audiences are selected
            var selectedAudiences = jQuery('.b2bking-pro-select-multiple-options').val();
            if (selectedAudiences && selectedAudiences.length > 0) {
                if (selectedAudiences.length <= 2) {
                    // Show actual audience names for 1-2 selections
                    var audienceNames = selectedAudiences.map(function(audienceId) {
                        var option = jQuery('.b2bking-pro-select-multiple-options option[value="' + audienceId + '"]');
                        return option.text() || b2bking.drpro_audience + ' ' + audienceId;
                    });
                    customerGroupText = audienceNames.join(', ');
                } else {
                    // Too many audiences, show generic
                    customerGroupText = b2bking.drpro_multiple_audiences;
                }
                } else {
                    customerGroupText = b2bking.drpro_multiple_audiences;
                }
        } else if (customerGroup === 'specific_users') {
            // Check if specific users are selected
            var selectedUsers = jQuery('#b2bking_specific_users_selector_pro').val();
            if (selectedUsers && selectedUsers.length > 0) {
                if (selectedUsers.length <= 2) {
                    // Show actual user names for 1-2 users
                    var userNames = selectedUsers.map(function(userId) {
                        var option = jQuery('#b2bking_specific_users_selector_pro option[value="' + userId + '"]');
                        return option.text() || b2bking.drpro_user + ' ' + userId;
                    });
                    customerGroupText = userNames.join(', ');
                } else {
                    // Too many users, show generic
                    customerGroupText = b2bking.drpro_specific_users;
                }
            } else {
                customerGroupText = b2bking.drpro_specific_users;
            }
        } else if (customerGroup.startsWith('group_')) {
            var groupId = customerGroup.replace('group_', '');
            var groupName = jQuery('#b2bking_rule_select_who_pro option[value="' + customerGroup + '"]').text();
            customerGroupText = groupName || b2bking.drpro_selected_b2b_group;
        }
    }
    
    // Build how much text (only if how-much card is visible)
    var howMuchText = '';
    if (howMuch && isCardVisible('#how-much-card')) {
        // Special handling for minimum_order and maximum_order - check if it's quantity or value
        if (ruleType === 'minimum_order' || ruleType === 'maximum_order') {
            var quantityValue = jQuery('#b2bking_rule_quantity_value_pro').val();
            if (quantityValue === 'value') {
                // Show with currency symbol for value
                howMuchText = '$' + howMuch;
            } else {
                // Show without currency symbol for quantity
                howMuchText = howMuch;
            }
        }
        // Special handling for payment method discount - check if it's amount or percentage
        else if (ruleType === 'payment_method_discount') {
            var percentAmount = jQuery('#b2bking_rule_paymentmethod_percentamount_pro').val();
            if (percentAmount === 'percentage') {
                howMuchText = howMuch + '%';
            } else if (percentAmount === 'amount') {
                howMuchText = '$' + howMuch;
            } else {
                howMuchText = howMuch;
            }
        } else if (ruleType && (ruleType.includes('percentage') || ruleType.includes('tax_percentage'))) {
            howMuchText = howMuch + '%';
        } else if (ruleType && (ruleType.includes('amount') || ruleType.includes('price'))) {
            howMuchText = '$' + howMuch;
        } else {
            howMuchText = howMuch;
        }
    }
    
    // Build tiered price range text (for tiered_price rule type)
    var tieredPriceRangeText = '';
    if (ruleType === 'tiered_price' && isCardVisible('#price-tiers-card')) {
        var prices = [];
        jQuery('#b2bking_price_tiers_container_pro .b2bking-price-tier-row').each(function() {
            var priceInput = jQuery(this).find('.b2bking-price-tier-price').val();
            if (priceInput && priceInput.trim() !== '') {
                // Parse price value (handle currency symbols, commas, etc.)
                var priceValue = parseFloat(priceInput.toString().replace(/[^0-9.-]/g, ''));
                if (!isNaN(priceValue) && priceValue > 0) {
                    prices.push(priceValue);
                }
            }
        });
        
        if (prices.length > 0) {
            var minPrice = Math.min.apply(Math, prices);
            var maxPrice = Math.max.apply(Math, prices);
            
            // Check if percentage setting is enabled
            var usePercentage = typeof b2bking_dynamic_rule_pro_editor !== 'undefined' && 
                                b2bking_dynamic_rule_pro_editor.use_percentage_tiered === true;
            
            if (usePercentage) {
                // Format as percentage range
                var minFormatted = parseFloat(minPrice.toFixed(2));
                var maxFormatted = parseFloat(maxPrice.toFixed(2));
                tieredPriceRangeText = minFormatted + '% - ' + maxFormatted + '%';
            } else {
                // Format as amount range
                // Use simple currency formatting (WooCommerce currency symbol handling would require additional setup)
                var minFormatted = parseFloat(minPrice.toFixed(2));
                var maxFormatted = parseFloat(maxPrice.toFixed(2));
                tieredPriceRangeText = '$' + minFormatted + '-$' + maxFormatted;
            }
        }
    }
    
    // Progressive preview - show what's configured so far
    var previewParts = [];
    var previewText = '';
    
    if (ruleType) {
        // Special handling for set_currency_symbol with currency code
        if (setCurrencyWithCode && currencyCodeText) {
            previewParts.push('<strong>' + b2bking.drpro_set_currency + '</strong> ' + b2bking.drpro_to + ' <strong>' + currencyCodeText + '</strong>');
        } else {
            previewParts.push('<strong>' + ruleTypeText + '</strong>');
        }
    }
    
    // Include tiered price range if available
    if (tieredPriceRangeText) {
        previewParts.push(b2bking.drpro_of + ' <strong>' + tieredPriceRangeText + '</strong>');
    }
    // Only include how much if the card is visible and not tiered_price
    else if (howMuch && ruleType && isCardVisible('#how-much-card')) {
        previewParts.push(b2bking.drpro_of + ' <strong>' + howMuchText + '</strong>');
    }
    
    // Only include customer group if the card is visible
    if (customerGroup && isCardVisible('#for-who-card')) {
        previewParts.push(b2bking.drpro_for + ' <strong>' + customerGroupText + '</strong>');
    }
    
    // Get payment method name for payment method related rules
    var paymentMethodName = '';
    var paymentShippingRuleTypes = ['rename_purchase_order', 'payment_method_minmax_order', 'payment_method_discount', 'payment_method_restriction'];
    if (paymentShippingRuleTypes.indexOf(ruleType) !== -1 && isCardVisible('#payment-method-card')) {
        var paymentMethodSelector = jQuery('#b2bking_rule_paymentmethod_pro');
        var selectedPaymentMethodId = paymentMethodSelector.val();
        if (selectedPaymentMethodId) {
            var selectedPaymentOption = paymentMethodSelector.find('option[value="' + selectedPaymentMethodId + '"]');
            if (selectedPaymentOption.length) {
                paymentMethodName = selectedPaymentOption.text().trim();
            }
        }
    }
    
    // Get shipping method name for shipping method restriction rules
    var shippingMethodName = '';
    if (ruleType === 'shipping_method_restriction' && isCardVisible('#shipping-method-card')) {
        var shippingMethodSelector = jQuery('#b2bking_rule_shippingmethod_pro');
        var selectedShippingMethodId = shippingMethodSelector.val();
        if (selectedShippingMethodId) {
            var selectedShippingOption = shippingMethodSelector.find('option[value="' + selectedShippingMethodId + '"]');
            if (selectedShippingOption.length) {
                // Extract method name (remove zone name in parentheses if present)
                var methodText = selectedShippingOption.text().trim();
                // Format: "Method Name (Zone Name)" -> extract just "Method Name"
                var methodNameMatch = methodText.match(/^([^(]+)/);
                if (methodNameMatch) {
                    shippingMethodName = methodNameMatch[1].trim();
                } else {
                    shippingMethodName = methodText;
                }
            }
        }
    }
    
    // Add payment or shipping method name to preview if available
    // For payment/shipping method rules, prioritize showing the method name over appliesTo
    var hasPaymentShippingMethod = paymentMethodName || shippingMethodName;
    
    if (hasPaymentShippingMethod) {
        // Show payment or shipping method name
        if (paymentMethodName) {
            previewParts.push(b2bking.drpro_on + ' <strong>' + paymentMethodName + '</strong>');
        } else if (shippingMethodName) {
            previewParts.push(b2bking.drpro_on + ' <strong>' + shippingMethodName + '</strong>');
        }
        // Skip appliesTo for payment/shipping method rules to avoid redundancy
    } else {
        // Only include applies to if the card is visible and no payment/shipping method is shown
        if (appliesTo && isCardVisible('#applies-to-card')) {
            // Handle special case for "all products except" to avoid highlighting connecting words
            if (appliesToText.startsWith(b2bking.drpro_all_products_except + ' ')) {
                var actualItems = appliesToText.replace(b2bking.drpro_all_products_except + ' ', '');
                previewParts.push(b2bking.drpro_on_all_products_except + ' <strong>' + actualItems + '</strong>');
            } else {
                previewParts.push(b2bking.drpro_on + ' <strong>' + appliesToText + '</strong>');
            }
        }
    }
    
    if (previewParts.length > 0) {
        previewText = previewParts.join(' ');
    } else {
        previewText = b2bking.drpro_configure_rule_preview;
    }
    
    // Add rule name if provided, with visual separation
    var fullPreviewText = '';
    if (ruleName && ruleName.trim() !== '') {
        fullPreviewText = '<div style="font-size: 15px; font-weight: 600; color: #191821; margin-bottom: 8px;">' + ruleName + '</div>' + previewText;
    } else {
        fullPreviewText = previewText;
    }
    
    // Update the preview icon if rule type is selected
    if (ruleType) {
        // Use the existing getRuleTypeIcon function from the dropdown
        var ruleTypeIcon = window.getRuleTypeIcon ? window.getRuleTypeIcon(ruleType) : getRuleTypeIcon(ruleType);
        jQuery('.preview-icon').html(ruleTypeIcon);
        jQuery('.preview-icon').attr('data-rule-type', ruleType);
    } else {
        // Default icon
        jQuery('.preview-icon').html('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12S5 4 12 4S23 12 23 12S19 20 12 20S1 12 1 12Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>');
        jQuery('.preview-icon').removeAttr('data-rule-type');
    }
    
    jQuery('#rule-preview-text').html(fullPreviewText);
}

// Global submission guard to prevent duplicate submissions
var isSubmitting = false;

function saveRule() {
    // Prevent multiple simultaneous submissions
    if (isSubmitting) {
        console.log('Submission already in progress, ignoring duplicate call');
        return;
    }
    
    isSubmitting = true;
    
    // Validate form
    if (!validateDynamicRuleForm()) {
        isSubmitting = false; // Reset guard on validation failure
        return;
    }
        
    // Get form data
    var formData = {
        action: 'b2bking_dynamic_rules_pro_save_rule',
        nonce: b2bking_dynamic_rule_pro_editor.nonce,
        rule_name: jQuery('#b2bking_rule_name_pro').val(),
        rule_what: jQuery('#b2bking_rule_select_what_pro').val(),
        rule_applies: jQuery('#b2bking_rule_select_applies_pro').val(),
        rule_applies_options: jQuery('#b2bking_rule_select_applies_multiple_options_pro').val(),
        rule_who: jQuery('#b2bking_rule_select_who_pro').val(),
        rule_who_multiple_options: jQuery('#b2bking_rule_who_multiple_options_pro').val(),
        rule_howmuch: jQuery('#b2bking_rule_select_howmuch_pro').val(),
        rule_quantity_value: jQuery('#b2bking_rule_quantity_value_pro').val(),
        rule_currency: jQuery('#b2bking_rule_currency_pro').val(),
        rule_paymentmethod: jQuery('#b2bking_rule_paymentmethod_pro').val(),
        rule_paymentmethod_minmax: jQuery('#b2bking_rule_paymentmethod_minmax_pro').val(),
        rule_paymentmethod_percentamount: jQuery('#b2bking_rule_paymentmethod_percentamount_pro').val(),
        rule_paymentmethod_discountsurcharge: jQuery('#b2bking_rule_paymentmethod_discountsurcharge_pro').val(),
        rule_paymentmethod_name: jQuery('#b2bking_rule_paymentmethod_name_pro').val(),
        rule_shippingmethod: jQuery('#b2bking_rule_shippingmethod_pro').val(),
        rule_countries: jQuery('#b2bking_rule_countries_pro').val() ? jQuery('#b2bking_rule_countries_pro').val().join(',') : '',
        rule_requires: jQuery('#b2bking_rule_requires_pro').val(),
        rule_showtax: jQuery('#b2bking_rule_showtax_pro').val(),
        rule_tax_shipping: jQuery('#b2bking_rule_tax_shipping_pro').val(),
        rule_tax_shipping_rate: jQuery('#b2bking_rule_tax_shipping_rate_pro').val(),
        rule_taxname: jQuery('#b2bking_rule_taxname_pro').val(),
        rule_tax_taxable: jQuery('#b2bking_rule_tax_taxable_pro').val(),
        rule_discountname: jQuery('#b2bking_rule_select_discountname_pro').val(),
        rule_show_everywhere: jQuery('#b2bking_dynamic_rule_discount_show_everywhere_checkbox_input_pro').is(':checked') ? '1' : '0',
        rule_per_product: jQuery('#b2bking_dynamic_rule_per_product_checkbox_input_pro').is(':checked') ? '1' : '0',
        rule_conditions: jQuery('#b2bking_rule_select_conditions_pro').val(),
        rule_priority: jQuery('#b2bking_standard_rule_priority_pro').val(),
    };
    
    // Collect price tiers data
    var priceTiersQuantity = [];
    var priceTiersPrice = [];
    jQuery('#b2bking_price_tiers_container_pro .b2bking-price-tier-row').each(function() {
        var quantity = jQuery(this).find('.b2bking-price-tier-quantity').val();
        var price = jQuery(this).find('.b2bking-price-tier-price').val();
        if (quantity && price) {
            priceTiersQuantity.push(quantity);
            priceTiersPrice.push(price);
        }
    });
    formData.rule_price_tiers_quantity = priceTiersQuantity;
    formData.rule_price_tiers_price = priceTiersPrice;
    
    // Collect info table rows data
    var infoTableRowsLabel = [];
    var infoTableRowsText = [];
    jQuery('#b2bking_info_table_rows_container_pro .b2bking-info-table-row').each(function() {
        var label = jQuery(this).find('.b2bking-info-table-label').val();
        var text = jQuery(this).find('.b2bking-info-table-text').val();
        if (label && text) {
            infoTableRowsLabel.push(label);
            infoTableRowsText.push(text);
        }
    });
    formData.rule_info_table_rows_label = infoTableRowsLabel;
    formData.rule_info_table_rows_text = infoTableRowsText;
    
    // Add rule ID if editing
    var ruleId = jQuery('input[name="rule_id"]').val();
    if (ruleId) {
        formData.rule_id = ruleId;
    }
    
    // Show loading state for both buttons
    var saveButton = jQuery('#b2bking_dynamic_rule_pro_editor_save');
    var saveButtonTop = jQuery('#b2bking_dynamic_rule_pro_editor_save_top');
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
                showMessageEditor('success', b2bking.grpro_rule_saved_successfully, 1000, hidepreview = true); // set time to 500 so it only appears quickly
                showSaveSuccessNotification();
                scrollEditorToTop();
                
                // If this was a new rule, update the page to reflect edit mode
                var wasNewRule = !ruleId && response.data && response.data.rule_id;
                if (wasNewRule) {
                    var newRuleId = response.data.rule_id;
                    
                    // Add hidden input field for rule_id if it doesn't exist
                    if (!jQuery('input[name="rule_id"]').length) {
                        jQuery('#b2bking_dynamic_rule_pro_editor_form').append('<input type="hidden" name="rule_id" value="' + newRuleId + '">');
                    } else {
                        jQuery('input[name="rule_id"]').val(newRuleId);
                    }
                    
                    // Update URL to include rule_id (WordPress-style)
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('rule_id', newRuleId);
                    window.history.pushState({}, '', currentUrl.toString());
                    
                    // Update page header from "Create" to "Edit"
                    jQuery('.b2bking_dynamic_rule_pro_editor_header_text h1').text(b2bking.drpro_edit_dynamic_rule);
                    
                    // Update button texts from "Create & Activate Rule" to "Update Rule"
                    var updateText = b2bking.drpro_update_rule;
                    var updatingText = b2bking.drpro_updating;
                    
                    jQuery('#b2bking_dynamic_rule_pro_editor_save .btn-text').text(updateText);
                    jQuery('#b2bking_dynamic_rule_pro_editor_save_top .btn-text').text(updateText);
                    jQuery('#b2bking_dynamic_rule_pro_editor_save .btn-loading-text').text(updatingText);
                    jQuery('#b2bking_dynamic_rule_pro_editor_save_top .btn-loading-text').text(updatingText);
                    
                    // Update originalText for button state restoration
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

// Helper function to clear error state from a card when field changes
function clearCardError($field) {
    if (!$field || !$field.length) return;
    
    var $card = $field.closest('.b2bking-card');
    if ($card.length) {
        $card.find('.card-error-badge').remove();
        $field.removeClass('error');
    }
}

function validateDynamicRuleForm() {
    var isValid = true;
    var firstInvalidCard = null;
    
    // Helper function to find parent card and add error badge
    function markCardAsInvalid($field) {
        if (!$field || !$field.length) return;
        
        // Find the parent card
        var $card = $field.closest('.b2bking-card');
        if (!$card.length) return;
        
        // Add error badge to card header if it doesn't exist
        var $cardHeader = $card.find('.card-header');
        if ($cardHeader.length && !$cardHeader.find('.card-error-badge').length) {
            var $errorBadge = jQuery('<span class="card-error-badge">' + b2bking.drpro_required + '</span>');
            $cardHeader.append($errorBadge);
        }
        
        // Track first invalid card for scrolling
        if (!firstInvalidCard) {
            firstInvalidCard = $card[0];
        }
        
        // Add error class to the field itself for styling
        $field.addClass('error');
    }
    
    // Clear previous error states
    jQuery('.b2bking-card .card-error-badge').remove();
    jQuery('.b2bking-rule-builder input, .b2bking-rule-builder select').removeClass('error');
    jQuery('.b2bking-custom-rule-type-dropdown').removeClass('error');
    
    // Validate rule name
    var $ruleName = jQuery('#b2bking_rule_name_pro');
    if (!$ruleName.val().trim()) {
        markCardAsInvalid($ruleName);
        if ($ruleName[0] && $ruleName[0].reportValidity) {
            $ruleName[0].reportValidity();
        }
        isValid = false;
    }
    
    // Validate rule type
    var $ruleTypeSelect = jQuery('#b2bking_rule_select_what_pro');
    if (!$ruleTypeSelect.val()) {
        markCardAsInvalid($ruleTypeSelect);
        if ($ruleTypeSelect[0] && $ruleTypeSelect[0].reportValidity) {
            $ruleTypeSelect[0].reportValidity();
        }
        isValid = false;
    }
    
    // Get rule type for conditional validations
    var ruleType = $ruleTypeSelect.val();
    
    // Validate applies to - only required for rule types that show the applies to card
    // Rule types that DON'T require applies_to: replace_prices_quote, set_currency_symbol, 
    // payment_method_minmax_order, payment_method_discount, rename_purchase_order, tax_exemption_user
    var requiresAppliesTo = ['replace_prices_quote', 'set_currency_symbol', 'payment_method_minmax_order', 
                             'payment_method_discount', 'rename_purchase_order', 'tax_exemption_user'].indexOf(ruleType) === -1;
    
    if (requiresAppliesTo) {
        var $appliesTo = jQuery('#b2bking_rule_select_applies_pro');
        if (!$appliesTo.val()) {
            markCardAsInvalid($appliesTo);
            if ($appliesTo[0] && $appliesTo[0].reportValidity) {
                $appliesTo[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate customer group
    var $customerGroup = jQuery('#b2bking_rule_select_who_pro');
    if (!$customerGroup.val()) {
        markCardAsInvalid($customerGroup);
        if ($customerGroup[0] && $customerGroup[0].reportValidity) {
            $customerGroup[0].reportValidity();
        }
        isValid = false;
    }
    
    // Validate how much - only required for certain rule types
    var requiresHowMuch = ['discount_amount', 'discount_percentage', 'raise_price', 'bogo_discount', 'fixed_price', 'minimum_order', 'maximum_order', 'required_multiple', 'payment_method_minmax_order', 'payment_method_discount', 'add_tax_percentage', 'add_tax_amount'].indexOf(ruleType) !== -1;
    
    if (requiresHowMuch) {
        var $howMuch = jQuery('#b2bking_rule_select_howmuch_pro');
        if (!$howMuch.val()) {
            markCardAsInvalid($howMuch);
            if ($howMuch[0] && $howMuch[0].reportValidity) {
                $howMuch[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate quantity/value - only required for min/max order rules
    var requiresQuantityValue = ['minimum_order', 'maximum_order'].indexOf(ruleType) !== -1;
    
    if (requiresQuantityValue) {
        var $quantityValue = jQuery('#b2bking_rule_quantity_value_pro');
        if (!$quantityValue.val()) {
            markCardAsInvalid($quantityValue);
            if ($quantityValue[0] && $quantityValue[0].reportValidity) {
                $quantityValue[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate currency - only required for set_currency_symbol rule
    var requiresCurrency = ['set_currency_symbol'].indexOf(ruleType) !== -1;
    
    if (requiresCurrency) {
        var $currency = jQuery('#b2bking_rule_currency_pro');
        if (!$currency.val()) {
            markCardAsInvalid($currency);
            if ($currency[0] && $currency[0].reportValidity) {
                $currency[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate payment method - only required for payment_method_minmax_order rule
    var requiresPaymentMethod = ['payment_method_minmax_order'].indexOf(ruleType) !== -1;
    
    if (requiresPaymentMethod) {
        var $paymentMethod = jQuery('#b2bking_rule_paymentmethod_pro');
        if (!$paymentMethod.val()) {
            markCardAsInvalid($paymentMethod);
            if ($paymentMethod[0] && $paymentMethod[0].reportValidity) {
                $paymentMethod[0].reportValidity();
            }
            isValid = false;
        }
        
        var $paymentMethodMinMax = jQuery('#b2bking_rule_paymentmethod_minmax_pro');
        if (!$paymentMethodMinMax.val()) {
            markCardAsInvalid($paymentMethodMinMax);
            if ($paymentMethodMinMax[0] && $paymentMethodMinMax[0].reportValidity) {
                $paymentMethodMinMax[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate payment method - required for payment_method_discount, payment_method_restriction, rename_purchase_order
    var requiresPaymentMethodOther = ['payment_method_discount', 'payment_method_restriction', 'rename_purchase_order'].indexOf(ruleType) !== -1;
    
    if (requiresPaymentMethodOther) {
        var $paymentMethodOther = jQuery('#b2bking_rule_paymentmethod_pro');
        if (!$paymentMethodOther.val()) {
            markCardAsInvalid($paymentMethodOther);
            if ($paymentMethodOther[0] && $paymentMethodOther[0].reportValidity) {
                $paymentMethodOther[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate amount/percentage and discount/surcharge for payment_method_discount
    if (ruleType === 'payment_method_discount') {
        var $paymentMethodPercentAmount = jQuery('#b2bking_rule_paymentmethod_percentamount_pro');
        if (!$paymentMethodPercentAmount.val()) {
            markCardAsInvalid($paymentMethodPercentAmount);
            if ($paymentMethodPercentAmount[0] && $paymentMethodPercentAmount[0].reportValidity) {
                $paymentMethodPercentAmount[0].reportValidity();
            }
            isValid = false;
        }
        
        var $paymentMethodDiscountSurcharge = jQuery('#b2bking_rule_paymentmethod_discountsurcharge_pro');
        if (!$paymentMethodDiscountSurcharge.val()) {
            markCardAsInvalid($paymentMethodDiscountSurcharge);
            if ($paymentMethodDiscountSurcharge[0] && $paymentMethodDiscountSurcharge[0].reportValidity) {
                $paymentMethodDiscountSurcharge[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate shipping method for shipping_method_restriction
    if (ruleType === 'shipping_method_restriction') {
        var $shippingMethod = jQuery('#b2bking_rule_shippingmethod_pro');
        if (!$shippingMethod.val()) {
            markCardAsInvalid($shippingMethod);
            if ($shippingMethod[0] && $shippingMethod[0].reportValidity) {
                $shippingMethod[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate payment method name for rename_purchase_order
    if (ruleType === 'rename_purchase_order') {
        var $paymentMethodName = jQuery('#b2bking_rule_paymentmethod_name_pro');
        if (!$paymentMethodName.val().trim()) {
            markCardAsInvalid($paymentMethodName);
            if ($paymentMethodName[0] && $paymentMethodName[0].reportValidity) {
                $paymentMethodName[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate countries for tax_exemption_user and tax_exemption
    var requiresCountries = ['tax_exemption_user', 'tax_exemption'].indexOf(ruleType) !== -1;
    if (requiresCountries) {
        var $countries = jQuery('#b2bking_rule_countries_pro');
        if (!$countries.val() || $countries.val().length === 0) {
            markCardAsInvalid($countries);
            if ($countries[0] && $countries[0].reportValidity) {
                $countries[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Validate tax name for add_tax_percentage and add_tax_amount
    var requiresTaxName = ['add_tax_percentage', 'add_tax_amount'].indexOf(ruleType) !== -1;
    if (requiresTaxName) {
        var $taxName = jQuery('#b2bking_rule_taxname_pro');
        if (!$taxName.val().trim()) {
            markCardAsInvalid($taxName);
            if ($taxName[0] && $taxName[0].reportValidity) {
                $taxName[0].reportValidity();
            }
            isValid = false;
        }
    }
    
    // Initialize conditional visibility for Pay Tax in Cart
    var showtaxValue = jQuery('#b2bking_rule_showtax_pro').val();
    if (showtaxValue === 'display_only') {
        jQuery('#tax-shipping-container').show();
        var taxShippingValue = jQuery('#b2bking_rule_tax_shipping_pro').val();
        if (taxShippingValue === 'yes') {
            jQuery('#tax-shipping-rate-container').show();
        }
    }
    
    // Scroll to first invalid card
    if (!isValid && firstInvalidCard) {
        firstInvalidCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    return isValid;
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
    // Use SweetAlert2 Toast like B2BKing's notifications for consistency
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
        // Fallback if SweetAlert2 is not available
        alert(message);
        if (hidepreview === true && previewElement && previewWasVisible) {
            previewElement.stop(true, true).fadeIn(150);
        }
    }
}

function showSaveSuccessNotification() {
    var header = jQuery('.b2bking_dynamic_rule_pro_editor_header');
    
    // Remove any existing notification
    jQuery('.b2bking_dynamic_rule_pro_editor_save_success_notification').remove();
    
    // Create notification element
    var notification = jQuery('<div class="b2bking_dynamic_rule_pro_editor_save_success_notification">' +
        '<div class="b2bking_dynamic_rule_pro_editor_save_success_notification_content">' +
        '<svg class="b2bking_dynamic_rule_pro_editor_save_success_notification_icon" width="20" height="20" viewBox="0 0 20 20" fill="none">' +
        '<path d="M16.6667 5L7.50004 14.1667L3.33337 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        '</svg>' +
        '<span class="b2bking_dynamic_rule_pro_editor_save_success_notification_text">' + b2bking.drpro_rule_saved_successfully + '</span>' +
        '</div>' +
        '</div>');
    
    // Insert before the header
    header.before(notification);
    
    // Trigger animation
    setTimeout(function() {
        notification.addClass('show');
    }, 10);
}

// Dynamic card numbering function with debouncing to prevent multiple rapid calls
var updateCardNumbersTimeout = null;
var updateCardNumbersPending = false;

function updateCardNumbers() {
    // Clear any pending update
    if (updateCardNumbersTimeout) {
        clearTimeout(updateCardNumbersTimeout);
    }
    
    // Skip if an update is already pending (prevents duplicate calls)
    if (updateCardNumbersPending) {
        return;
    }
    
    // Mark as pending
    updateCardNumbersPending = true;
    
    // Use requestAnimationFrame twice to ensure DOM updates are complete
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            // Get all cards except the final-card (Save & Activate)
            // Check visibility explicitly to avoid timing issues with :visible selector
            var allCards = jQuery('.b2bking-card').not('.final-card');
            var cardNumber = 1;
            
            allCards.each(function() {
                var $card = jQuery(this);
                var cardNumberElement = $card.find('.card-number');
                
                // Check if card is actually visible (using multiple methods for reliability)
                var isVisible = $card.is(':visible') && 
                               $card.css('display') !== 'none' && 
                               !$card.hasClass('hidden') &&
                               $card.outerWidth() > 0 &&
                               $card.outerHeight() > 0;
                
                // Only number visible cards that have a card-number element
                if (isVisible && cardNumberElement.length) {
                    cardNumberElement.text(cardNumber);
                    cardNumber++;
                }
            });
            
            // Reset pending flag
            updateCardNumbersPending = false;
        });
    });
}

// Initialize Select2 for countries selector
function initializeCountriesSelect2(retryCount) {
    retryCount = retryCount || 0;
    var $countriesSelect = jQuery('#b2bking_rule_countries_pro');
    if (!$countriesSelect.length) {
        // Retry if element doesn't exist yet (for AJAX-loaded content)
        if (retryCount < 3) {
            setTimeout(function() {
                initializeCountriesSelect2(retryCount + 1);
            }, 200);
        }
        return;
    }
    
    // Read selected countries from data attribute (try multiple methods)
    var dataCountries = $countriesSelect.data('selected-countries') || 
                     $countriesSelect.attr('data-selected-countries') ||
                     $countriesSelect[0].getAttribute('data-selected-countries');
    var selectedValues = [];
    
    if (dataCountries && dataCountries.trim() !== '') {
        // Parse comma-separated string and trim whitespace
        selectedValues = dataCountries.split(',').map(function(v) { 
            return v ? v.trim() : ''; 
        }).filter(function(v) { 
            return v !== ''; 
        });
    }
    
    // If no data attribute found, retry (might not be set yet in AJAX-loaded content)
    if (selectedValues.length === 0 && retryCount < 2) {
        setTimeout(function() {
            initializeCountriesSelect2(retryCount + 1);
        }, 200);
        return;
    }
    
    // Destroy existing Select2 if present
    if ($countriesSelect.hasClass('select2-hidden-accessible')) {
        $countriesSelect.select2('destroy');
    }
    
    // Set values on the select element
    if (selectedValues.length > 0) {
        $countriesSelect.val(selectedValues);
    }
    
    // Initialize Select2
    $countriesSelect.select2({
        placeholder: b2bking.drpro_select_countries,
        allowClear: true,
        width: '100%'
    });
    
    // Set values in Select2 after initialization
    if (selectedValues.length > 0) {
        setTimeout(function() {
            $countriesSelect.val(selectedValues).trigger('change');
        }, 100);
    }
}

// Show/hide cards based on rule type
function showHideDiscountOptions() {
    var ruleType = jQuery('#b2bking_rule_select_what_pro').val();
    
    // Get all card elements
    var appliesToCard = jQuery('#applies-to-card');
    var forWhoCard = jQuery('#for-who-card');
    var quantityValueCard = jQuery('#quantity-value-card');
    var howMuchCard = jQuery('#how-much-card');
    var priceTiersCard = jQuery('#price-tiers-card');
    var currencyCard = jQuery('#currency-card');
    var paymentMethodCard = jQuery('#payment-method-card');
    var paymentMethodMinmaxCard = jQuery('#payment-method-minmax-card');
    var shippingMethodCard = jQuery('#shipping-method-card');
    var amountPercentageCard = jQuery('#amount-percentage-card');
    var discountSurchargeCard = jQuery('#discount-surcharge-card');
    var paymentMethodNameCard = jQuery('#payment-method-name-card');
    var informationTableRowsCard = jQuery('#information-table-rows-card');
    var countriesCard = jQuery('#countries-card');
    var requiresCard = jQuery('#requires-card');
    var payTaxInCartCard = jQuery('#pay-tax-in-cart-card');
    var taxNameCard = jQuery('#tax-name-card');
    var taxableCard = jQuery('#taxable-card');
    var discountCard = jQuery('#discount_options_card');
    var additionalOptionsCard = jQuery('#additional-options-card');
    var conditionsCard = jQuery('#conditions-card');
    var priorityCard = jQuery('#priority-card');
    
    // Reset all cards to default state (hidden initially, will show based on rule type)
    // Always show for who
    forWhoCard.show();

    // Hide all conditional cards initially
    appliesToCard.hide();
    quantityValueCard.hide();
    howMuchCard.hide();
    priceTiersCard.hide();
    currencyCard.hide();
    paymentMethodCard.hide();
    paymentMethodMinmaxCard.hide();
    shippingMethodCard.hide();
    amountPercentageCard.hide();
    discountSurchargeCard.hide();
    paymentMethodNameCard.hide();
    informationTableRowsCard.hide();
    countriesCard.hide();
    requiresCard.hide();
    payTaxInCartCard.hide();
    taxNameCard.hide();
    taxableCard.hide();
    discountCard.hide();
    additionalOptionsCard.hide();
    conditionsCard.hide();
    priorityCard.hide();
    
    if (!ruleType) {
        // No rule type selected - show only applies to and for who
        // Don't call updateCardNumbers here - it will be called at the end of the function
        return;
    }
    
    // Define rule type groups
    var discountRules = ['discount_amount', 'discount_percentage'];
    var standardRules = ['raise_price', 'bogo_discount', 'fixed_price'];
    var hiddenPriceRule = ['hidden_price'];
    var unpurchasableRule = ['unpurchasable'];
    var tieredPriceRule = ['tiered_price'];
    var freeShippingRule = ['free_shipping'];
    var minMaxOrderRules = ['minimum_order', 'maximum_order'];
    var requiredMultipleRule = ['required_multiple'];
    var replacePricesQuoteRule = ['replace_prices_quote'];
    var quotesProductsRule = ['quotes_products'];
    var setCurrencyRule = ['set_currency_symbol'];
    var paymentMethodMinmaxRule = ['payment_method_minmax_order'];
    var paymentMethodDiscountRule = ['payment_method_discount'];
    var paymentMethodRestrictionRule = ['payment_method_restriction'];
    var shippingMethodRestrictionRule = ['shipping_method_restriction'];
    var renamePaymentMethodRule = ['rename_purchase_order'];
    var infoTableRule = ['info_table'];
    var taxExemptionUserRule = ['tax_exemption_user'];
    var taxExemptionRule = ['tax_exemption'];
    var addTaxPercentageRule = ['add_tax_percentage'];
    var addTaxAmountRule = ['add_tax_amount'];
    
    // Handle discount rules (discount_amount, discount_percentage)
    if (discountRules.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, how_much, discount_options, conditions, rule_priority
        howMuchCard.show();
        discountCard.show();
        conditionsCard.show();
        priorityCard.show();
        appliesToCard.show();

    }
    // Handle raise_price, bogo_discount, fixed_price rules
    else if (standardRules.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, how_much, conditions, rule_priority (NO discount_options)
        howMuchCard.show();
        conditionsCard.show();
        priorityCard.show();
        appliesToCard.show();

    }
    // Handle hidden_price rule
    else if (hiddenPriceRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who (ONLY these two)
        // All other cards already hidden
        appliesToCard.show();

    }
    // Handle unpurchasable rule (same as hidden_price)
    else if (unpurchasableRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who (ONLY these two)
        // All other cards already hidden
        appliesToCard.show();

    }
    // Handle tiered_price rule
    else if (tieredPriceRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, price_tiers, and priority
        priceTiersCard.show();
        priorityCard.show();
        appliesToCard.show();

    }
    // Handle free_shipping rule
    else if (freeShippingRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, conditions
        conditionsCard.show();
        appliesToCard.show();

    }
    // Handle minimum_order and maximum_order rules
    else if (minMaxOrderRules.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, quantity/value, how_much, additional_options, priority
        quantityValueCard.show();
        howMuchCard.show();
        additionalOptionsCard.show();
        priorityCard.show();
        appliesToCard.show();

    }
    // Handle required_multiple rule
    else if (requiredMultipleRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, how_much, additional_options, conditions, priority
        // Note: NO quantity/value card for required_multiple
        howMuchCard.show();
        additionalOptionsCard.show();
        conditionsCard.show();
        priorityCard.show();
        appliesToCard.show();
    }

    // Handle replace_prices_quote rule
    else if (replacePricesQuoteRule.indexOf(ruleType) !== -1) {
        // Show: for_who ONLY (no applies_to)
    }
    // Handle quotes_products rule
    else if (quotesProductsRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who
        // All other cards already hidden
        appliesToCard.show();
    }
    // Handle set_currency_symbol rule
    else if (setCurrencyRule.indexOf(ruleType) !== -1) {
        // Show: for_who, currency
        currencyCard.show();
    }
    // Handle payment_method_minmax_order rule
    else if (paymentMethodMinmaxRule.indexOf(ruleType) !== -1) {
        // Show: for_who, how_much, payment_method, payment_method_minmax
        howMuchCard.show();
        paymentMethodCard.show();
        paymentMethodMinmaxCard.show();
    }
    // Handle payment_method_discount rule
    else if (paymentMethodDiscountRule.indexOf(ruleType) !== -1) {
        // Show: for_who, payment_method, amount_percentage, discount_surcharge, how_much
        paymentMethodCard.show();
        amountPercentageCard.show();
        discountSurchargeCard.show();
        howMuchCard.show();
    }
    // Handle payment_method_restriction rule
    else if (paymentMethodRestrictionRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, payment_method
        paymentMethodCard.show();
        appliesToCard.show();
    }
    // Handle shipping_method_restriction rule
    else if (shippingMethodRestrictionRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, shipping_method
        shippingMethodCard.show();
        appliesToCard.show();
    }
    // Handle rename_purchase_order rule
    else if (renamePaymentMethodRule.indexOf(ruleType) !== -1) {
        // Show: for_who, payment_method, payment_method_name
        paymentMethodCard.show();
        paymentMethodNameCard.show();
    }
    // Handle info_table rule
    else if (infoTableRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, information_table_rows
        informationTableRowsCard.show();
        appliesToCard.show();

    }
    // Handle tax_exemption_user rule
    else if (taxExemptionUserRule.indexOf(ruleType) !== -1) {
        // Show: for_who, countries, requires, pay_tax_in_cart
        countriesCard.show();
        requiresCard.show();
        payTaxInCartCard.show();
    }
    // Handle tax_exemption rule (zero tax product)
    else if (taxExemptionRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, countries, requires
        countriesCard.show();
        requiresCard.show();
        appliesToCard.show();

    }
    // Handle add_tax_percentage and add_tax_amount rules
    else if (addTaxPercentageRule.indexOf(ruleType) !== -1 || addTaxAmountRule.indexOf(ruleType) !== -1) {
        // Show: applies_to, for_who, how_much, tax_name, taxable, conditions, priority
        howMuchCard.show();
        taxNameCard.show();
        taxableCard.show();
        conditionsCard.show();
        priorityCard.show();
        appliesToCard.show();
    }
    // For any other rule types, show applies_to and for_who only
    else {
        // Show: applies_to, for_who (default for unknown rule types)
        appliesToCard.show();
    }
    
    // Initialize Select2 for countries if card is shown
    if (countriesCard.is(':visible')) {
        // Use longer delay for AJAX-loaded content to ensure DOM is fully ready
        setTimeout(initializeCountriesSelect2, 300);
    }
    
    // Hide/show "excluding_multiple_options" option for specific rule types
    var excludingOption = jQuery('.applies-option[data-applies="excluding_multiple_options"]');
    var excludingSelectOption = jQuery('#b2bking_rule_select_applies_pro option[value="excluding_multiple_options"]');
    var hideExcludingTypes = ['minimum_order', 'maximum_order', 'required_multiple'];
    var shouldHideExcluding = hideExcludingTypes.indexOf(ruleType) !== -1;
    
    if (shouldHideExcluding) {
        // Hide the option in the action-grid
        excludingOption.hide();
        // Remove the option from the hidden select dropdown
        if (excludingSelectOption.length > 0) {
            // Store the option text in a data attribute for potential restoration
            if (!excludingSelectOption.data('original-text')) {
                excludingSelectOption.data('original-text', excludingSelectOption.text());
            }
            excludingSelectOption.remove();
        }
        
        // If "excluding_multiple_options" is currently selected, reset to "cart_total"
        var currentAppliesTo = jQuery('#b2bking_rule_select_applies_pro').val();
        if (currentAppliesTo === 'excluding_multiple_options') {
            jQuery('#b2bking_rule_select_applies_pro').val('cart_total');
            // Update the action-grid selection
            jQuery('.applies-option[data-applies="cart_total"]').addClass('selected');
            excludingOption.removeClass('selected');
            // Trigger change to update preview and hide search container
            jQuery('#b2bking_rule_select_applies_pro').trigger('change');
        }
    } else {
        // Show the option in the action-grid
        excludingOption.show();
        // Restore the option in the hidden select dropdown if it was removed
        if (excludingSelectOption.length === 0) {
            var optgroup = jQuery('#b2bking_rule_select_applies_pro optgroup[label="Cart"]');
            if (optgroup.length === 0) {
                // Try to find the optgroup by checking all optgroups
                optgroup = jQuery('#b2bking_rule_select_applies_pro optgroup').first();
            }
            if (optgroup.length > 0) {
                // Use stored text or get from action-grid option, or use default
                var optionText = b2bking.drpro_all_products_except_specific_dots;
                if (excludingOption.length > 0) {
                    // Try to get text from the action-grid option's description or name
                    var descText = excludingOption.find('.applies-description').text();
                    if (descText) {
                        optionText = descText + '...';
                    } else {
                        var nameText = excludingOption.find('.applies-name').text();
                        if (nameText) {
                            optionText = nameText + '...';
                        }
                    }
                }
                optgroup.append('<option value="excluding_multiple_options">' + optionText + '</option>');
            }
        }
    }
    
    // Update card numbers after showing/hiding
    // The debounced function will handle timing
    updateCardNumbers();
}

// Show/hide search container based on applies to selection
function showHideSearchContainer() {
    var appliesTo = jQuery('#b2bking_rule_select_applies_pro').val();
    var searchContainer = jQuery('#b2bking_rule_select_applies_multiple_options_container_pro');
    
    if (appliesTo === 'multiple_options' || appliesTo === 'excluding_multiple_options') {
        searchContainer.show();
    } else {
        searchContainer.hide();
    }
    
    // Update card numbers after showing/hiding
    // The debounced function will handle timing
    updateCardNumbers();
}

// Show/hide user selectors based on who selection
function showHideUserSelectors() {
    var who = jQuery('#b2bking_rule_select_who_pro').val();
    var multipleOptionsSelector = jQuery('#b2bking_select_multiple_options_selector_pro');
    var specificUsersSelector = jQuery('#b2bking_specific_users_selector_container_pro');
    
    if (who === 'multiple_options') {
        multipleOptionsSelector.show();
        specificUsersSelector.hide();
        
        // Refresh Select2 to ensure proper display
        setTimeout(function() {
            jQuery('.b2bking-pro-select-multiple-options').select2('destroy').select2({
                placeholder: b2bking.drpro_select_user_types_groups,
                allowClear: true,
                width: '100%'
            });
        }, 100);
    } else if (who === 'specific_users') {
        multipleOptionsSelector.hide();
        specificUsersSelector.show();
    } else {
        multipleOptionsSelector.hide();
        specificUsersSelector.hide();
    }
    
    // Update card numbers after showing/hiding
    // The debounced function will handle timing
    updateCardNumbers();
}

// Initialize Select2 search functionality
function initializeSelect2Search() {
    if (jQuery('#b2bking_content_selector_pro').length) {
        var isClearing = false;
        jQuery('#b2bking_content_selector_pro').select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    // Get selected search types
                    var searchTypes = [];
                    if (jQuery('#search_products_pro').is(':checked')) searchTypes.push('products');
                    if (jQuery('#search_categories_pro').is(':checked')) searchTypes.push('categories');
                    if (jQuery('#search_tags_pro').is(':checked')) searchTypes.push('tags');
                    if (jQuery('#search_brands_pro').is(':checked')) searchTypes.push('brands');
                    
                    return {
                        action: 'b2bking_admin_content_search',
                        security: b2bking.security,
                        search: params.term,
                        types: searchTypes
                    };
                },
                processResults: function (data) {
                    if (data.success) {
                        return {
                            results: data.data
                        };
                    }
                    return { results: [] };
                },
                cache: true
            },
            minimumInputLength: 3,
            placeholder: b2bking.drpro_enter_item_name,
            allowClear: true,
            width: '100%'
        });
        
        // Update hidden input when selection changes
        jQuery('#b2bking_content_selector_pro').on('change', function() {
            var selectedIds = jQuery(this).val();
            if (selectedIds && selectedIds.length > 0) {
                jQuery('#b2bking_rule_select_applies_multiple_options_pro').val(selectedIds.join(','));
            } else {
                jQuery('#b2bking_rule_select_applies_multiple_options_pro').val('');
            }
            // Check for gold border
            checkElementContent(jQuery(this));
        });
        
        // Handle select2 clear (general X button) by destroying and recreating
        jQuery('#b2bking_content_selector_pro').on('select2:clear', function() {
            var $select = jQuery(this);
            isClearing = true;
            
            setTimeout(function() {
                $select.select2('destroy');
                $select.select2({
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            var searchTypes = [];
                            if (jQuery('#search_products_pro').is(':checked')) searchTypes.push('products');
                            if (jQuery('#search_categories_pro').is(':checked')) searchTypes.push('categories');
                            if (jQuery('#search_tags_pro').is(':checked')) searchTypes.push('tags');
                            if (jQuery('#search_brands_pro').is(':checked')) searchTypes.push('brands');
                            
                            return {
                                action: 'b2bking_admin_content_search',
                                security: b2bking.security,
                                search: params.term,
                                types: searchTypes
                            };
                        },
                        processResults: function (data) {
                            if (data.success) {
                                return { results: data.data };
                            }
                            return { results: [] };
                        },
                        cache: true
                    },
                    minimumInputLength: 3,
                    placeholder: b2bking.drpro_enter_item_name,
                    allowClear: true,
                    width: '100%'
                });
                
                // Clear the hidden input
                jQuery('#b2bking_rule_select_applies_multiple_options_pro').val('');
                isClearing = false;
            }, 10);
        });
        
        // Handle select2 unselect by destroying and recreating
        jQuery('#b2bking_content_selector_pro').on('select2:unselect', function(e) {
            // Skip if we're in the middle of a clear operation
            if (isClearing) {
                return;
            }
            
            var $select = jQuery(this);
            var unselectedValue = e.params.data.id;
            
            setTimeout(function() {
                // Get values after the unselect has actually happened
                var currentValues = $select.val() || [];
                
                $select.select2('destroy');
                $select.select2({
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            var searchTypes = [];
                            if (jQuery('#search_products_pro').is(':checked')) searchTypes.push('products');
                            if (jQuery('#search_categories_pro').is(':checked')) searchTypes.push('categories');
                            if (jQuery('#search_tags_pro').is(':checked')) searchTypes.push('tags');
                            if (jQuery('#search_brands_pro').is(':checked')) searchTypes.push('brands');
                            
                            return {
                                action: 'b2bking_admin_content_search',
                                security: b2bking.security,
                                search: params.term,
                                types: searchTypes
                            };
                        },
                        processResults: function (data) {
                            if (data.success) {
                                return { results: data.data };
                            }
                            return { results: [] };
                        },
                        cache: true
                    },
                    minimumInputLength: 3,
                    placeholder: b2bking.drpro_enter_item_name,
                    allowClear: true,
                    width: '100%'
                });
                
                // Only restore if there are actually remaining values
                if (currentValues && currentValues.length > 0) {
                    $select.val(currentValues).trigger('change');
                }
            }, 10);
        });
        
        // Handle mutual exclusivity between tags and brands
        jQuery('#search_tags_pro, #search_brands_pro').on('change', function() {
            var $this = jQuery(this);
            var $other = $this.attr('id') === 'search_tags_pro' ? jQuery('#search_brands_pro') : jQuery('#search_tags_pro');
            
            if ($this.is(':checked')) {
                // If the other one is checked, we're switching - show warning
                if ($other.is(':checked')) {
                    var message = $this.attr('id') === 'search_brands_pro' 
                        ? b2bking.drpro_switch_to_brands
                        : b2bking.drpro_switch_to_tags;
                    
                    if (!confirm(message)) {
                        $this.prop('checked', false);
                        return false;
                    }
                    
                    $other.prop('checked', false);
                    jQuery.post(ajaxurl, {
                        action: 'b2bking_save_brands_setting',
                        security: b2bking.security,
                        use_brands: $this.attr('id') === 'search_brands_pro' ? 1 : 0
                    });
                } else {
                    // Just ensuring the other is off and saving state
                    $other.prop('checked', false);
                    jQuery.post(ajaxurl, {
                        action: 'b2bking_save_brands_setting',
                        security: b2bking.security,
                        use_brands: $this.attr('id') === 'search_brands_pro' ? 1 : 0
                    });
                }
            }
        });
        
        // Refresh search when checkboxes change
        jQuery('.b2bking_content_search_types input[type="checkbox"]').on('change', function() {
            // Trigger a new search if there's a search term
            var searchInput = jQuery('.select2-search__field');
            if (searchInput.length && searchInput.val().length >= 3) {
                jQuery('#b2bking_content_selector_pro').trigger('select2:open');
                searchInput.trigger('input');
            }
        });
    }
}

// Initialize specific users selector
function initializeSpecificUsersSelector() {
    if (jQuery('#b2bking_specific_users_selector_pro').length) {
        var isClearing = false;
        jQuery('#b2bking_specific_users_selector_pro').select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        action: 'b2bking_admin_user_search',
                        security: b2bking.security,
                        search: params.term
                    };
                },
                processResults: function (data) {
                    if (data.success) {
                        return {
                            results: data.data
                        };
                    }
                    return { results: [] };
                },
                cache: true
            },
            minimumInputLength: 3,
            placeholder: b2bking.drpro_search_for_users,
            allowClear: true,
            width: '100%'
        });
        
        // Handle select2 clear (general X button) by destroying and recreating
        jQuery('#b2bking_specific_users_selector_pro').on('select2:clear', function() {
            var $select = jQuery(this);
            isClearing = true;
            
            setTimeout(function() {
                $select.select2('destroy');
                $select.select2({
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            return {
                                action: 'b2bking_admin_user_search',
                                security: b2bking.security,
                                search: params.term
                            };
                        },
                        processResults: function (data) {
                            if (data.success) {
                                return { results: data.data };
                            }
                            return { results: [] };
                        },
                        cache: true
                    },
                    minimumInputLength: 3,
                    placeholder: b2bking.drpro_search_for_users,
                    allowClear: true,
                    width: '100%'
                });
                isClearing = false;
            }, 10);
        });
        
        // Handle select2 unselect by destroying and recreating
        jQuery('#b2bking_specific_users_selector_pro').on('select2:unselect', function(e) {
            // Skip if we're in the middle of a clear operation
            if (isClearing) {
                return;
            }
            
            var $select = jQuery(this);
            var unselectedValue = e.params.data.id;
            
            setTimeout(function() {
                // Get values after the unselect has actually happened
                var currentValues = $select.val() || [];
                
                $select.select2('destroy');
                $select.select2({
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            return {
                                action: 'b2bking_admin_user_search',
                                security: b2bking.security,
                                search: params.term
                            };
                        },
                        processResults: function (data) {
                            if (data.success) {
                                return { results: data.data };
                            }
                            return { results: [] };
                        },
                        cache: true
                    },
                    minimumInputLength: 3,
                    placeholder: b2bking.drpro_search_for_users,
                    allowClear: true,
                    width: '100%'
                });
                
                // Only restore if there are actually remaining values
                if (currentValues && currentValues.length > 0) {
                    $select.val(currentValues).trigger('change');
                }
            }, 10);
        });
    }
}

// Initialize multiple options selector
function initializeMultipleOptionsSelector() {
    if (jQuery('.b2bking-pro-select-multiple-options').length) {
        var $selector = jQuery('.b2bking-pro-select-multiple-options');
        var isClearing = false;
        
        // Get existing selected values from the hidden field
        var existingValues = jQuery('#b2bking_rule_who_multiple_options_pro').val();
        if (existingValues && existingValues.trim() !== '') {
            var valuesArray = existingValues.split(',');
            $selector.val(valuesArray);
        }
        
        $selector.select2({
            placeholder: 'Select user types and groups...',
            allowClear: true,
            width: '100%'
        });
        
        // Handle select2 clear (general X button) by destroying and recreating
        $selector.on('select2:clear', function() {
            var $select = jQuery(this);
            isClearing = true;
            
            setTimeout(function() {
                $select.select2('destroy');
                $select.select2({
                    placeholder: b2bking.drpro_select_user_types_groups,
                    allowClear: true,
                    width: '100%'
                });
                
                // Clear the hidden input
                jQuery('#b2bking_rule_who_multiple_options_pro').val('');
                isClearing = false;
            }, 10);
        });
        
        // Handle select2 unselect by destroying and recreating
        $selector.on('select2:unselect', function(e) {
            // Skip if we're in the middle of a clear operation
            if (isClearing) {
                return;
            }
            
            var $select = jQuery(this);
            var unselectedValue = e.params.data.id;
            
            setTimeout(function() {
                // Get values after the unselect has actually happened
                var currentValues = $select.val() || [];
                
                $select.select2('destroy');
                $select.select2({
                    placeholder: b2bking.drpro_select_user_types_groups,
                    allowClear: true,
                    width: '100%'
                });
                
                // Only restore if there are actually remaining values
                if (currentValues && currentValues.length > 0) {
                    $select.val(currentValues).trigger('change');
                }
            }, 10);
        });
    }
}

// Initialize card toggles
function initializeCardToggles() {
    // Check if conditions card has content and auto-expand if needed
    var conditionsValue = jQuery('#b2bking_rule_select_conditions_pro').val();
    if (conditionsValue && conditionsValue.trim() !== '') {
        toggleCard('conditions-card-body', true);
    }
    
    // Check if priority card has content and auto-expand if needed
    var priorityValue = jQuery('#b2bking_standard_rule_priority_pro').val();
    if (priorityValue && priorityValue.trim() !== '') {
        toggleCard('priority-card-body', true);
    }
    
    // Bind toggle button events
    jQuery('.card-toggle-btn').off('click').on('click', function() {
        var targetId = jQuery(this).data('target');
        var isCurrentlyExpanded = jQuery('#' + targetId).is(':visible');
        toggleCard(targetId, !isCurrentlyExpanded);
    });
    
    // Bind card header click events
    jQuery('.card-header').off('click').on('click', function(e) {
        // Don't trigger if clicking on the toggle button (to avoid double trigger)
        if (jQuery(e.target).closest('.card-toggle-btn').length > 0) {
            return;
        }
        
        var toggleBtn = jQuery(this).find('.card-toggle-btn');
        if (toggleBtn.length > 0) {
            var targetId = toggleBtn.data('target');
            var isCurrentlyExpanded = jQuery('#' + targetId).is(':visible');
            toggleCard(targetId, !isCurrentlyExpanded);
        }
    });
}

// Toggle card visibility
function toggleCard(targetId, show) {
    var cardBody = jQuery('#' + targetId);
    var toggleBtn = jQuery('[data-target="' + targetId + '"]');
    var toggleIcon = toggleBtn.find('.toggle-icon');
    var toggleText = toggleBtn.find('.toggle-text');
    
    if (show) {
        // Show card
        cardBody.css({
            'display': 'block',
            'max-height': '0px',
            'opacity': '0',
            'padding-top': '0px',
            'padding-bottom': '0px',
            'margin-top': '0px',
            'margin-bottom': '0px'
        });
        
        // Force reflow
        cardBody[0].offsetHeight;
        
        // Animate to expanded state
        cardBody.css({
            'max-height': '1000px',
            'opacity': '1',
            'padding-top': '20px',
            'padding-bottom': '20px',
            'margin-top': '0px',
            'margin-bottom': '0px'
        });
        
        toggleBtn.addClass('expanded');
        toggleIcon.text('−');
        toggleText.text(b2bking.drpro_hide);
    } else {
        // Hide card
        cardBody.css({
            'max-height': '0px',
            'opacity': '0',
            'padding-top': '0px',
            'padding-bottom': '0px',
            'margin-top': '0px',
            'margin-bottom': '0px'
        });
        
        toggleBtn.removeClass('expanded');
        toggleIcon.text('+');
        toggleText.text(b2bking.drpro_show);
        
        // Hide completely after animation
        setTimeout(function() {
            if (!cardBody.hasClass('expanded')) {
                cardBody.css('display', 'none');
            }
        }, 300);
    }
}

// Initialize existing conditions from saved data
function initializeExistingConditions() {
    var conditionsValue = jQuery('#b2bking_rule_select_conditions_pro').val();
    
    if (conditionsValue && conditionsValue.trim() !== '') {
        var conditions = conditionsValue.split('|');
        
        // Clear any existing condition containers first
        jQuery('.b2bking_rule_condition_container').remove();
        
        // Create conditions from saved data
        conditions.forEach(function(condition, index) {
            if (condition.trim() !== '') {
                var parts = condition.split(';');
                if (parts.length === 3) {
                    var name = parts[0];
                    var operator = parts[1];
                    var number = parts[2];
                    
                    // Create condition container for each condition
                    var conditionContainer = createConditionContainer(index + 1);
                    if (conditionContainer) {
                        populateConditionContainer(conditionContainer, name, operator, number);
                    }
                }
            }
        });
    }
}

// Initialize price tiers functionality
function initializePriceTiers() {
    // Handle add tier button
    jQuery(document).off('click', '#b2bking_add_tier_pro').on('click', '#b2bking_add_tier_pro', function(e) {
        e.preventDefault();
        addPriceTierRow();
        // Update preview after adding tier
        updateRulePreview();
    });
    
    // Handle remove tier button (using event delegation for dynamically added buttons)
    jQuery(document).off('click', '.b2bking-remove-tier-btn').on('click', '.b2bking-remove-tier-btn', function(e) {
        e.preventDefault();
        var $row = jQuery(this).closest('.b2bking-price-tier-row');
        var $container = jQuery('#b2bking_price_tiers_container_pro');
        
        // Only remove if there's more than one row
        if ($container.find('.b2bking-price-tier-row').length > 1) {
            $row.fadeOut(200, function() {
                jQuery(this).remove();
                // Update preview after removing tier
                updateRulePreview();
            });
        } else {
            // If it's the last row, just clear the values
            $row.find('.b2bking-price-tier-quantity').val('');
            $row.find('.b2bking-price-tier-price').val('');
            // Update preview after clearing values
            updateRulePreview();
        }
    });
    
    // Handle price tier input changes (using event delegation for dynamically added inputs)
    jQuery(document).off('input change', '.b2bking-price-tier-price, .b2bking-price-tier-quantity').on('input change', '.b2bking-price-tier-price, .b2bking-price-tier-quantity', function() {
        // Update preview when price tier values change
        updateRulePreview();
    });
}

// Add a new price tier row
function addPriceTierRow() {
    var $container = jQuery('#b2bking_price_tiers_container_pro');
    var minQuantityText = b2bking.drpro_min_quantity_text;
    var finalPriceText = b2bking.drpro_final_price_text;
    
    var newRow = jQuery('<div class="b2bking-price-tier-row">' +
        '<input name="b2bking_price_tiers_quantity_pro[]" ' +
        'placeholder="' + minQuantityText + '" ' +
        'class="b2bking-price-tier-quantity" ' +
        'type="number" ' +
        'min="1" ' +
        'step="1" />' +
        '<input name="b2bking_price_tiers_price_pro[]" ' +
        'placeholder="' + finalPriceText + '" ' +
        'class="b2bking-price-tier-price" ' +
        'type="text" />' +
        '<button type="button" class="b2bking-remove-tier-btn" aria-label="' + b2bking.drpro_remove_tier + '">' +
        '<svg width="16" height="16" viewBox="0 0 16 16" fill="none">' +
        '<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
        '</svg>' +
        '</button>' +
        '</div>');
    
    $container.append(newRow);
    newRow.hide().fadeIn(200);
    newRow.find('.b2bking-price-tier-quantity').focus();
}

// Initialize info table rows functionality
function initializeInfoTableRows() {
    // Handle add row button
    jQuery(document).off('click', '#b2bking_add_info_table_row_pro').on('click', '#b2bking_add_info_table_row_pro', function(e) {
        e.preventDefault();
        addInfoTableRow();
    });
    
    // Handle remove row button (using event delegation for dynamically added buttons)
    jQuery(document).off('click', '.b2bking-remove-info-table-row-btn').on('click', '.b2bking-remove-info-table-row-btn', function(e) {
        e.preventDefault();
        var $row = jQuery(this).closest('.b2bking-info-table-row');
        var $container = jQuery('#b2bking_info_table_rows_container_pro');
        
        // Only remove if there's more than one row
        if ($container.find('.b2bking-info-table-row').length > 1) {
            $row.fadeOut(200, function() {
                jQuery(this).remove();
            });
        } else {
            // If it's the last row, just clear the values
            $row.find('.b2bking-info-table-label').val('');
            $row.find('.b2bking-info-table-text').val('');
        }
    });
}

// Add a new info table row
function addInfoTableRow() {
    var $container = jQuery('#b2bking_info_table_rows_container_pro');
    var labelText = b2bking.drpro_label_text;
    var textText = b2bking.drpro_text_text;
    
    var newRow = jQuery('<div class="b2bking-info-table-row">' +
        '<input name="b2bking_info_table_rows_label_pro[]" ' +
        'placeholder="' + labelText + '" ' +
        'class="b2bking-info-table-label" ' +
        'type="text" />' +
        '<input name="b2bking_info_table_rows_text_pro[]" ' +
        'placeholder="' + textText + '" ' +
        'class="b2bking-info-table-text" ' +
        'type="text" />' +
        '<button type="button" class="b2bking-remove-info-table-row-btn" aria-label="' + b2bking.drpro_remove_row + '">' +
        '<svg width="16" height="16" viewBox="0 0 16 16" fill="none">' +
        '<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
        '</svg>' +
        '</button>' +
        '</div>');
    
    $container.append(newRow);
    newRow.hide().fadeIn(200);
    newRow.find('.b2bking-info-table-label').focus();
}

// Create a new condition container
function createConditionContainer(conditionNumber) {
    // Create the condition container HTML
    var conditionHtml = `
        <div id="b2bking_condition_number_${conditionNumber}_pro" class="b2bking_rule_condition_container">
            <select class="b2bking_pro_rule_condition_name b2bking_condition_identifier_${conditionNumber}" style="width: 200px; margin-right: 10px;">
                <option value="cart_total_quantity">Cart Total Quantity</option>
                <option value="cart_total_value">Cart Total Value</option>
                <option value="category_product_quantity">Category Product Quantity</option>
                <option value="category_product_value">Category Product Value</option>
                <option value="product_quantity">Product Quantity</option>
                <option value="product_value">Product Value</option>
            </select>
            <select class="b2bking_pro_rule_condition_operator b2bking_condition_identifier_${conditionNumber}" style="width: 120px; margin-right: 10px;">
                <option value="greater">greater (>)</option>
                <option value="equal">equal (=)</option>
                <option value="smaller">smaller (<)</option>
            </select>
            <input type="number" class="b2bking_pro_rule_condition_number b2bking_condition_identifier_${conditionNumber}" style="width: 100px; margin-right: 10px;" placeholder="0">
            <button type="button" class="b2bking_pro_rule_condition_add_button b2bking_condition_identifier_${conditionNumber}">Add Condition</button>
        </div>
    `;
    
    var newCondition = jQuery(conditionHtml);
    
    // If this is not the first condition, change the button to "Remove"
    if (conditionNumber > 1) {
        newCondition.find('.b2bking_pro_rule_condition_add_button').text(b2bking.drpro_remove).removeClass('b2bking_pro_rule_condition_add_button').addClass('b2bking_dynamic_rule_condition_remove_button');
    }
    
    // Insert after the last condition container or append to the conditions card body
    var lastCondition = jQuery('.b2bking_rule_condition_container').last();
    if (lastCondition.length > 0) {
        newCondition.insertAfter(lastCondition);
    } else {
        // Find the conditions card body and append to it
        var conditionsCardBody = jQuery('#conditions-card-body');
        if (conditionsCardBody.length > 0) {
            newCondition.appendTo(conditionsCardBody);
        } else {
            console.error('Conditions card body not found');
            return null;
        }
    }
    
    // Bind remove event for non-first conditions
    if (conditionNumber > 1) {
        newCondition.find('.b2bking_dynamic_rule_condition_remove_button').on('click', function() {
            newCondition.remove();
            updateConditionsHiddenField();
        });
    } else {
        // Bind add condition event for the first condition
        newCondition.find('.b2bking_pro_rule_condition_add_button').on('click', function() {
            addNewCondition();
        });
    }
    
    // Bind change events
    newCondition.find('.b2bking_pro_rule_condition_name, .b2bking_pro_rule_condition_operator, .b2bking_pro_rule_condition_number').on('change input', function() {
        updateConditionsHiddenField();
    });
    
    return newCondition;
}

// Populate a condition container with values
function populateConditionContainer(container, name, operator, number) {
    container.find('.b2bking_pro_rule_condition_name').val(name);
    container.find('.b2bking_pro_rule_condition_operator').val(operator);
    container.find('.b2bking_pro_rule_condition_number').val(number);
}

// Update conditions hidden field
function updateConditionsHiddenField() {
    var conditions = [];
    
    jQuery('.b2bking_rule_condition_container').each(function() {
        var name = jQuery(this).find('.b2bking_pro_rule_condition_name').val();
        var operator = jQuery(this).find('.b2bking_pro_rule_condition_operator').val();
        var number = jQuery(this).find('.b2bking_pro_rule_condition_number').val();
        
        if (name && operator && number) {
            conditions.push(name + ';' + operator + ';' + number);
        }
    });
    
    jQuery('#b2bking_rule_select_conditions_pro').val(conditions.join('|'));
}

// Add new condition
function addNewCondition() {
    var conditionCount = jQuery('.b2bking_rule_condition_container').length + 1;
    var newCondition = jQuery('#b2bking_condition_number_1_pro').clone();
    
    newCondition.attr('id', 'b2bking_condition_number_' + conditionCount + '_pro');
    newCondition.find('.b2bking_condition_identifier_1').removeClass('b2bking_condition_identifier_1').addClass('b2bking_condition_identifier_' + conditionCount);
    newCondition.find('.b2bking_pro_rule_condition_name').val('cart_total_quantity');
    newCondition.find('.b2bking_pro_rule_condition_operator').val('greater');
    newCondition.find('.b2bking_pro_rule_condition_number').val('');
    newCondition.find('.b2bking_pro_rule_condition_add_button').text('Remove').removeClass('b2bking_pro_rule_condition_add_button').addClass('b2bking_dynamic_rule_condition_remove_button');
    
    newCondition.appendTo('.b2bking-rule-builder .card-body:has(#b2bking_condition_number_1_pro)');
    
    // Bind remove event
    newCondition.find('.b2bking_dynamic_rule_condition_remove_button').on('click', function() {
        newCondition.remove();
        updateConditionsHiddenField();
    });
    
    // Bind change events
    newCondition.find('.b2bking_pro_rule_condition_name, .b2bking_pro_rule_condition_operator, .b2bking_pro_rule_condition_number').on('change input', function() {
        updateConditionsHiddenField();
    });
    
    updateConditionsHiddenField();
}

function initializeHelpTips() {
    jQuery('.b2bking-help-tip').each(function() {
        var $tip = jQuery(this);
        var $content = $tip.find('.b2bking-help-tip-content');
        
        if ($content.length > 0) {
            // Move tooltip to body to escape overflow hidden
            $content.appendTo('body');
            
            $tip.on('mouseenter', function() {
                var rect = this.getBoundingClientRect();
                
                // Position relative to viewport
                var left = rect.right + 10;
                var top = rect.top;
                
                $content.css({
                    'position': 'fixed',
                    'left': left + 'px',
                    'top': top + 'px',
                    'opacity': '1',
                    'visibility': 'visible',
                    'z-index': '999999'
                });
            });
            
            $tip.on('mouseleave', function() {
                $content.css({
                    'opacity': '0',
                    'visibility': 'hidden'
                });
            });
        }
    });
}

// Gold Border Functionality
function initializeGoldBorders() {
    // Check all form elements on initialization
    checkAllFormElements();
    
    // Bind events to all form elements
    bindGoldBorderEvents();
}

function bindGoldBorderEvents() {
    // Regular inputs and selects
    jQuery('#b2bking_dynamic_rule_pro_editor_main_container input, #b2bking_dynamic_rule_pro_editor_main_container select').on('input change keyup', function() {
        checkElementContent(jQuery(this));
    });
    
    // Select2 elements
    jQuery('#b2bking_dynamic_rule_pro_editor_main_container .select2-container').on('change', function() {
        checkSelect2Content(jQuery(this));
    });
    
    // Handle dynamically added elements (like conditions)
    jQuery(document).on('input change keyup', '#b2bking_dynamic_rule_pro_editor_main_container input, #b2bking_dynamic_rule_pro_editor_main_container select', function() {
        checkElementContent(jQuery(this));
    });
}

function checkAllFormElements() {
    // Check all regular inputs and selects
    jQuery('#b2bking_dynamic_rule_pro_editor_main_container input, #b2bking_dynamic_rule_pro_editor_main_container select').each(function() {
        checkElementContent(jQuery(this));
    });
    
    // Check all Select2 containers
    jQuery('#b2bking_dynamic_rule_pro_editor_main_container .select2-container').each(function() {
        checkSelect2Content(jQuery(this));
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

function checkSelect2Content($select2Container) {
    var hasContent = false;
    var $select = $select2Container.prev('select');
    
    if ($select.length > 0) {
        var selectedValues = $select.val();
        var isMultiple = $select.prop('multiple');
        
        if (isMultiple) {
            hasContent = selectedValues && selectedValues.length > 0;
        } else {
            // Check if it's not the default "select x" option
            var firstOption = $select.find('option:first');
            var firstOptionText = firstOption.text().toLowerCase();
            var isDefaultOption = firstOptionText.includes('select') || firstOptionText.includes('—') || firstOptionText === '';
            
            if (isDefaultOption) {
                hasContent = selectedValues && selectedValues !== '' && selectedValues !== firstOption.val();
            } else {
                hasContent = selectedValues && selectedValues !== '';
            }
        }
    }
    
    // Add or remove the has-content class
    if (hasContent) {
        $select2Container.addClass('has-content');
    } else {
        $select2Container.removeClass('has-content');
    }
}

function checkCustomDropdownContent() {
    var $customDropdown = jQuery('.b2bking-custom-rule-type-dropdown');
    var $originalSelect = jQuery('#b2bking_rule_select_what_pro');
    
    if ($customDropdown.length === 0 || $originalSelect.length === 0) return;
    
    var hasContent = false;
    var selectedValue = $originalSelect.val();
    
    if (selectedValue) {
        // Check if it's not the default "select x" option
        var firstOption = $originalSelect.find('option:first');
        var firstOptionText = firstOption.text().toLowerCase();
        var isDefaultOption = firstOptionText.includes('select') || firstOptionText.includes('—') || firstOptionText === '';
        
        if (isDefaultOption) {
            hasContent = selectedValue !== '' && selectedValue !== firstOption.val();
        } else {
            hasContent = selectedValue !== '';
        }
    }
    
    // Add or remove the has-content class
    if (hasContent) {
        $customDropdown.addClass('has-content');
    } else {
        $customDropdown.removeClass('has-content');
    }
}

// Custom Rule Type Dropdown with Icons
function initializeCustomRuleTypeDropdown() {
    const $originalSelect = jQuery('#b2bking_rule_select_what_pro');
    if ($originalSelect.length === 0) return;
    
    // Check if custom dropdown already exists
    if (jQuery('.b2bking-custom-rule-type-dropdown').length > 0) return;
    
    // Hide the original select
    $originalSelect.hide();
    
    // Create custom dropdown container
    const $customDropdown = jQuery('<div class="b2bking-custom-rule-type-dropdown"></div>');
    const $dropdownButton = jQuery('<button type="button" class="b2bking-custom-dropdown-button"></button>');
    const $dropdownList = jQuery('<div class="b2bking-custom-dropdown-list"></div>');
    const $searchInput = jQuery('<input type="text" class="b2bking-custom-dropdown-search" placeholder="' + b2bking.drpro_search + '" autocomplete="off" />');
    
    // Get the exact rule type icon function from the existing code
    window.getRuleTypeIcon = function(ruleType) {
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
            case 'info_table':
                iconSvg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M3 9.5H21M3 14.5H21M8 4.5V19.5M6.2 19.5H17.8C18.9201 19.5 19.4802 19.5 19.908 19.282C20.2843 19.0903 20.5903 18.7843 20.782 18.408C21 17.9802 21 17.4201 21 16.3V7.7C21 6.5799 21 6.01984 20.782 5.59202C20.5903 5.21569 20.2843 4.90973 19.908 4.71799C19.4802 4.5 18.9201 4.5 17.8 4.5H6.2C5.0799 4.5 4.51984 4.5 4.09202 4.71799C3.71569 4.90973 3.40973 5.21569 3.21799 5.59202C3 6.01984 3 6.57989 3 7.7V16.3C3 17.4201 3 17.9802 3.21799 18.408C3.40973 18.7843 3.71569 19.0903 4.09202 19.282C4.51984 19.5 5.07989 19.5 6.2 19.5Z" stroke="currentColor" stroke-width="2"></path>
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
            default:
                iconSvg = `<svg viewBox="0 0 24 24" fill="none">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>`;
        }
        
        return iconSvg;
    };
    
    // Build dropdown options from original select
    const $options = $originalSelect.find('option');
    const $optgroups = $originalSelect.find('optgroup');
    
    // Create dropdown button with current selection
    function updateDropdownButton() {
        const selectedValue = $originalSelect.val();
        const $selectedOption = $originalSelect.find('option:selected');
        const selectedText = $selectedOption.text();
        
        // Set data-value attribute on the button for CSS targeting
        if (selectedValue) {
            $dropdownButton.attr('data-value', selectedValue);
        } else {
            $dropdownButton.removeAttr('data-value');
        }
        
        if (selectedValue) {
            const iconSvg = getRuleTypeIcon(selectedValue);
            $dropdownButton.html(`
                <div class="b2bking-custom-dropdown-selected">
                    <div class="b2bking-custom-dropdown-icon">${iconSvg}</div>
                    <span class="b2bking-custom-dropdown-text">${selectedText}</span>
                </div>
                <div class="b2bking-custom-dropdown-arrow">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            `);
        } else {
            $dropdownButton.html(`
                <div class="b2bking-custom-dropdown-selected">
                    <span class="b2bking-custom-dropdown-text">${b2bking.drpro_select_rule_type}</span>
                </div>
                <div class="b2bking-custom-dropdown-arrow">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            `);
        }
    }
    
    // Build dropdown list
    function buildDropdownList() {
        $dropdownList.empty();
        
        // Add search input at the top
        $dropdownList.append($searchInput);
        
        $optgroups.each(function() {
            const $optgroup = jQuery(this);
            const groupLabel = $optgroup.attr('label');
            
            // Create optgroup header with data attribute for filtering
            const $groupHeader = jQuery(`<div class="b2bking-custom-dropdown-group-header" data-group-label="${groupLabel}">${groupLabel}</div>`);
            $dropdownList.append($groupHeader);
            
            // Create options for this group
            $optgroup.find('option').each(function() {
                const $option = jQuery(this);
                const value = $option.val();
                const text = $option.text();
                
                if (value) { // Skip empty options
                    const iconSvg = getRuleTypeIcon(value);
                    const $optionElement = jQuery(`
                        <div class="b2bking-custom-dropdown-option" data-value="${value}" data-search-text="${(value + ' ' + text).toLowerCase()}" data-group-label="${groupLabel}">
                            <div class="b2bking-custom-dropdown-option-icon">${iconSvg}</div>
                            <span class="b2bking-custom-dropdown-option-text">${text}</span>
                        </div>
                    `);
                    
                    $dropdownList.append($optionElement);
                }
            });
        });
    }
    
    // Filter dropdown options based on search text
    function filterDropdownOptions(searchText) {
        const searchLower = searchText.toLowerCase().trim();
        
        if (searchLower === '') {
            // Show all options and headers
            $dropdownList.find('.b2bking-custom-dropdown-option, .b2bking-custom-dropdown-group-header').show();
            return;
        }
        
        // Track which group labels should be visible
        const visibleGroupLabels = new Set();
        
        // Filter options first
        $dropdownList.find('.b2bking-custom-dropdown-option').each(function() {
            const $option = jQuery(this);
            const searchData = $option.data('search-text') || '';
            const matches = searchData.includes(searchLower);
            
            if (matches) {
                $option.show();
                // Mark the associated group label as visible
                const groupLabel = $option.data('group-label');
                if (groupLabel) {
                    visibleGroupLabels.add(groupLabel);
                }
            } else {
                $option.hide();
            }
        });
        
        // Show/hide group headers based on visible options
        $dropdownList.find('.b2bking-custom-dropdown-group-header').each(function() {
            const $header = jQuery(this);
            const headerLabel = $header.data('group-label');
            if (visibleGroupLabels.has(headerLabel)) {
                $header.show();
            } else {
                $header.hide();
            }
        });
    }
    
    // Initialize dropdown
    updateDropdownButton();
    buildDropdownList();
    
    // Assemble custom dropdown
    $customDropdown.append($dropdownButton);
    // Don't append dropdown list to custom dropdown - append to body instead
    
    // Insert after original select
    $originalSelect.after($customDropdown);
    
    // Create description container and insert after custom dropdown
    const $descriptionContainer = jQuery('<div class="b2bking-rule-type-description" id="b2bking_rule_type_description" style="display: none;"></div>');
    $customDropdown.after($descriptionContainer);
    
    // Append dropdown list to body to avoid parent transform issues
    jQuery('body').append($dropdownList);
    
    // Track if dropdown has been opened before in this session
    let hasBeenOpenedBefore = false;
    
    // Function to position dropdown list
    function positionDropdownList() {
        const buttonRect = $dropdownButton[0].getBoundingClientRect();
        $dropdownList.css({
            'top': buttonRect.bottom + 'px',
            'left': buttonRect.left + 'px',
            'width': buttonRect.width + 'px'
        });
    }
    
    // Handle dropdown toggle
    $dropdownButton.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        $customDropdown.toggleClass('open');
        
        if ($customDropdown.hasClass('open')) {
            // Position and show instantly
            positionDropdownList();
            $dropdownList.show();
            
            // Only focus on search input on first open
            if (!hasBeenOpenedBefore) {
                // Clear search and focus on search input
                $searchInput.val('').trigger('input');
                setTimeout(function() {
                    $searchInput.focus();
                }, 50);
                hasBeenOpenedBefore = true;
            }
        } else {
            $dropdownList.hide();
        }
    });
    
    // Handle option selection
    $dropdownList.on('click', '.b2bking-custom-dropdown-option', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const value = jQuery(this).data('value');
        const text = jQuery(this).find('.b2bking-custom-dropdown-option-text').text();
        
        // Update original select
        $originalSelect.val(value).trigger('change');
        
        // Update dropdown button
        updateDropdownButton();
        
        // Check for has-content class
        checkCustomDropdownContent();
        
        // Close dropdown
        $customDropdown.removeClass('open');
        $dropdownList.hide();
        
        // Clear search
        $searchInput.val('');
    });
    
    // Handle search input
    $dropdownList.on('input', '.b2bking-custom-dropdown-search', function(e) {
        e.stopPropagation();
        const searchText = jQuery(this).val();
        filterDropdownOptions(searchText);
    });
    
    // Handle keyboard in search input
    $dropdownList.on('keydown', '.b2bking-custom-dropdown-search', function(e) {
        e.stopPropagation();
        
        if (e.key === 'Escape') {
            $customDropdown.removeClass('open');
            $dropdownList.hide();
            $searchInput.val('');
            filterDropdownOptions('');
            $dropdownButton.focus();
        } else if (e.key === 'Enter') {
            // Select first visible option
            const $firstVisible = $dropdownList.find('.b2bking-custom-dropdown-option:visible').first();
            if ($firstVisible.length) {
                $firstVisible.trigger('click');
            }
        }
    });
    
    // Prevent search input from closing dropdown
    $dropdownList.on('click', '.b2bking-custom-dropdown-search', function(e) {
        e.stopPropagation();
    });
    
    // Close dropdown when clicking outside
    jQuery(document).on('click', function(e) {
        if (!jQuery(e.target).closest('.b2bking-custom-rule-type-dropdown').length) {
            $customDropdown.removeClass('open');
            $dropdownList.hide();
        }
    });
    
    // Handle keyboard navigation
    $dropdownButton.on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $dropdownButton.trigger('click');
        }
    });
    
    $dropdownList.on('keydown', '.b2bking-custom-dropdown-option', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            jQuery(this).trigger('click');
        }
    });
    
    // Reposition dropdown on scroll and resize
    jQuery(window).on('scroll resize', function() {
        if ($customDropdown.hasClass('open')) {
            positionDropdownList();
        }
    });
    
    // Check for has-content class on initialization
    checkCustomDropdownContent();
    
    // Show description if rule type is already selected
    showHideRuleTypeDescription();
}
