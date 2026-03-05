<?php
/**
 * Checkout & Cart Page Customizations
 * - Red buttons (replacing WooCommerce Blocks default blue)
 * - Payment options layout fix
 * - Vehicle verification section
 */

add_action('wp_head', 'maxus_checkout_cart_styles', 99);
function maxus_checkout_cart_styles() {
    if (!is_cart() && !is_checkout()) {
        return;
    }
    ?>
    <style>
        /* =============================================
           RED BUTTONS - Cart & Checkout
           ============================================= */

        /* Primary buttons (Place Order, Proceed to Checkout, Apply Coupon, etc.) */
        .wc-block-components-button:not(.is-link),
        .wc-block-cart__submit-button,
        .wc-block-components-checkout-place-order-button,
        .wp-block-woocommerce-proceed-to-checkout-block .wc-block-cart__submit-button,
        .wc-block-components-totals-coupon__button {
            background-color: #BF3617 !important;
            color: #fff !important;
            border-color: #BF3617 !important;
        }
        /* Ensure button text inside span is also white */
        .wc-block-components-button:not(.is-link) .wc-block-components-button__text,
        .wc-block-cart__submit-button .wc-block-components-button__text,
        .wc-block-components-checkout-place-order-button .wc-block-components-button__text,
        .wc-block-components-totals-coupon__button .wc-block-components-button__text,
        .wc-block-components-button:not(.is-link) span,
        .wc-block-cart__submit-button span {
            color: #fff !important;
        }
        .wc-block-components-button:not(.is-link):hover,
        .wc-block-cart__submit-button:hover,
        .wc-block-components-checkout-place-order-button:hover,
        .wc-block-components-totals-coupon__button:hover {
            background-color: #a02e13 !important;
            border-color: #a02e13 !important;
        }
        .wc-block-components-button:not(.is-link):focus,
        .wc-block-cart__submit-button:focus,
        .wc-block-components-checkout-place-order-button:focus {
            background-color: #a02e13 !important;
            border-color: #a02e13 !important;
            box-shadow: 0 0 0 2px rgba(191,54,23,0.3) !important;
        }

        /* Outlined / secondary buttons */
        .wc-block-components-button.outlined,
        .wc-block-components-button.is-link {
            color: #BF3617 !important;
            border-color: #BF3617 !important;
        }
        .wc-block-components-button.outlined:hover,
        .wc-block-components-button.is-link:hover {
            color: #a02e13 !important;
            border-color: #a02e13 !important;
        }

        /* Radio/checkbox accent colors */
        .wc-block-components-radio-control__input:checked,
        .wc-block-checkout__payment-method .wc-block-components-radio-control__input:checked {
            border-color: #BF3617 !important;
            background-color: #BF3617 !important;
        }

        /* Text links in checkout */
        .wc-block-checkout a,
        .wc-block-cart a:not(.wc-block-components-product-name) {
            color: #BF3617 !important;
        }
        .wc-block-checkout a:hover,
        .wc-block-cart a:not(.wc-block-components-product-name):hover {
            color: #a02e13 !important;
        }

        /* Input focus border */
        .wc-block-checkout .wc-block-components-text-input input:focus,
        .wc-block-checkout .wc-block-components-text-input textarea:focus,
        .wc-block-checkout select:focus {
            border-color: #BF3617 !important;
            box-shadow: 0 0 0 1px #BF3617 !important;
        }

        /* Spinner / loading indicator */
        .wc-block-components-spinner::after {
            border-color: #BF3617 transparent transparent !important;
        }

        /* =============================================
           PAYMENT OPTIONS LAYOUT FIX (Worldpay Blocks)
           ============================================= */

        /* Card fields container - stack vertically */
        .wc-block-card-elements {
            width: 100% !important;
        }

        /* Each card field wrapper: stack label above input */
        .wc-block-gateway-container {
            display: flex !important;
            flex-direction: column-reverse !important;
            margin-bottom: 16px !important;
            width: 100% !important;
        }

        /* Labels: display normally above the input */
        .wc-block-gateway-container label {
            display: block !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #333 !important;
            margin-bottom: 6px !important;
            writing-mode: horizontal-tb !important;
            white-space: nowrap !important;
        }

        /* The div that holds the Worldpay iframe */
        .wc-block-gateway-input {
            width: 100% !important;
            height: 44px !important;
            border: 1px solid #ccc !important;
            border-radius: 4px !important;
            background: #fff !important;
            overflow: hidden !important;
        }
        .wc-block-gateway-input:focus-within {
            border-color: #BF3617 !important;
            box-shadow: 0 0 0 1px #BF3617 !important;
        }

        /* Worldpay iframe inside the input div */
        .wc-block-gateway-input iframe {
            width: 100% !important;
            height: 44px !important;
            border: none !important;
            display: block !important;
        }

        /* Expiry and CVC side by side */
        .wc-block-card-elements {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 16px !important;
        }
        .wc-block-gateway-container.wc-card-number-element {
            flex: 0 0 100% !important;
        }
        .wc-block-gateway-container.wc-card-expiry-element,
        .wc-block-gateway-container.wc-card-cvc-element {
            flex: 1 1 calc(50% - 8px) !important;
            min-width: 140px !important;
        }

        /* Card holder name input */
        .wc-card-holder-name-element {
            margin-top: 4px !important;
        }

        /* Payment method content area - prevent overflow */
        .wc-block-components-radio-control-accordion-option__content {
            max-width: 100% !important;
            overflow: hidden !important;
        }

        /* Payment method icons */
        .wc-block-components-payment-method-icons,
        .wc-block-components-payment-method-label {
            flex-wrap: wrap;
        }
        .wc-block-components-payment-method-icons__icon {
            max-height: 24px;
            width: auto;
        }

        /* =============================================
           ESTIMATED DELIVERY DATE IN ORDER SUMMARY
           ============================================= */
        .maxus-delivery-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .maxus-delivery-section h4 {
            margin: 0 0 10px;
            font-size: 15px;
            color: #333;
        }
        .maxus-delivery-section .delivery-item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 4px 0;
            gap: 12px;
        }
        .maxus-delivery-section .delivery-item + .delivery-item {
            border-top: 1px solid #f0f0f0;
        }
        .maxus-delivery-section .delivery-product {
            color: #555;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .maxus-delivery-section .delivery-date {
            color: #333;
            font-weight: 600;
            white-space: nowrap;
        }

        /* =============================================
           HIDE PAYMENT UNTIL VEHICLE VERIFICATION DONE
           ============================================= */
        .wp-block-woocommerce-checkout-payment-block,
        .wc-block-checkout__payment-method {
            display: none;
        }
        .vv-payment-unlocked .wp-block-woocommerce-checkout-payment-block,
        .vv-payment-unlocked .wc-block-checkout__payment-method {
            display: block;
        }

        /* =============================================
           VEHICLE VERIFICATION SECTION
           ============================================= */

        .vehicle-verification-section {
            background: #fff;
            border: 2px solid #F29F05;
            border-radius: 6px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .vehicle-verification-section h3 {
            margin: 0 0 8px;
            font-size: 18px;
            color: #333;
        }
        .vehicle-verification-section .vv-description {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            margin: 0 0 20px;
        }
        .vehicle-verification-section .vv-entry {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .vehicle-verification-section .vv-field {
            flex: 1;
            min-width: 180px;
        }
        .vehicle-verification-section .vv-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 4px;
        }
        .vehicle-verification-section .vv-field input {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .vehicle-verification-section .vv-field input:focus {
            outline: none;
            border-color: #F29F05;
            box-shadow: 0 0 0 2px rgba(242,159,5,0.15);
        }
        .vehicle-verification-section .vv-remove {
            background: none;
            border: 1px solid #ccc;
            color: #999;
            width: 36px;
            height: 36px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            flex-shrink: 0;
        }
        .vehicle-verification-section .vv-remove:hover {
            border-color: #c0392b;
            color: #c0392b;
        }
        .vehicle-verification-section .vv-add-btn {
            background: none;
            border: 1px dashed #ccc;
            color: #555;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .vehicle-verification-section .vv-add-btn:hover {
            border-color: #F29F05;
            color: #F29F05;
        }
        .vehicle-verification-section .vv-skip-row {
            border-top: 1px solid #eee;
            padding-top: 16px;
        }
        .vehicle-verification-section .vv-skip-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
        }
        .vehicle-verification-section .vv-skip-label input {
            margin-top: 3px;
            flex-shrink: 0;
        }
        .vehicle-verification-section .vv-warning {
            display: none;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 12px 16px;
            margin-top: 12px;
            font-size: 13px;
            line-height: 1.6;
            color: #664d03;
        }
        .vehicle-verification-section .vv-warning strong {
            display: block;
            margin-bottom: 4px;
        }
        .vehicle-verification-section .vv-validation-error {
            display: none;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 14px;
            line-height: 1.5;
            color: #721c24;
        }
        .vehicle-verification-section.vv-has-error {
            border-color: #c0392b;
        }
        .vehicle-verification-section.vv-has-error .vv-validation-error {
            display: block;
        }

        @media (max-width: 640px) {
            .vehicle-verification-section .vv-entry {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            .vehicle-verification-section .vv-remove {
                align-self: flex-end;
            }
        }
    </style>
    <?php
}

/**
 * Inject vehicle verification section on checkout page
 */
add_action('wp_footer', 'maxus_vehicle_verification_checkout', 20);
function maxus_vehicle_verification_checkout() {
    if (!is_checkout()) {
        return;
    }
    ?>
    <script>
    (function() {
        'use strict';

        var sectionHTML = '<div class="vehicle-verification-section" id="vehicle-verification">'
            + '<h3>Vehicle Verification</h3>'
            + '<div class="vv-validation-error" id="vv-validation-error">Please enter a Registration Number or VIN for at least one vehicle, or tick the checkbox to continue without providing vehicle details.</div>'
            + '<p class="vv-description">'
            + 'To help ensure you receive the correct parts, please provide the registration number or VIN for each vehicle these parts are intended for. '
            + 'While we make every effort to keep our site accurate and up to date, part numbers can change and descriptions may vary between manufacturers.'
            + '</p>'
            + '<div id="vv-entries">'
            + '<div class="vv-entry" data-index="0">'
            + '<div class="vv-field"><label>Registration Number</label><input type="text" name="vv_reg[]" placeholder="e.g. AB12 CDE" autocomplete="off"></div>'
            + '<div class="vv-field"><label>VIN Number</label><input type="text" name="vv_vin[]" placeholder="e.g. 4T4BE46K79R107189" autocomplete="off"></div>'
            + '<button type="button" class="vv-remove" title="Remove" style="visibility:hidden;">&times;</button>'
            + '</div>'
            + '</div>'
            + '<button type="button" class="vv-add-btn" id="vv-add-vehicle">+ Add another vehicle</button>'
            + '<div class="vv-skip-row">'
            + '<label class="vv-skip-label">'
            + '<input type="checkbox" id="vv-skip-check">'
            + ' Continue without providing vehicle details'
            + '</label>'
            + '<div class="vv-warning" id="vv-warning">'
            + '<strong>Please note:</strong>'
            + 'Without registration or VIN details, we will be unable to perform a manual compatibility check before dispatching your order. '
            + 'If parts are found to be incorrect, a refund will only be issued for the cost of the part(s) — not postage. '
            + 'The buyer will also be responsible for all return postage costs.'
            + '</div>'
            + '</div>'
            + '</div>';

        function insertSection() {
            // Insert directly before the payment block so it appears above it
            var paymentBlock = document.querySelector('.wp-block-woocommerce-checkout-payment-block')
                || document.querySelector('.wc-block-checkout__payment-method');

            if (paymentBlock) {
                paymentBlock.insertAdjacentHTML('beforebegin', sectionHTML);
                bindEvents();
                return;
            }

            // Fallback: insert at the end of the checkout fields block
            var fieldsBlock = document.querySelector('.wp-block-woocommerce-checkout-fields-block');
            if (fieldsBlock) {
                fieldsBlock.insertAdjacentHTML('beforeend', sectionHTML);
                bindEvents();
            }
        }

        function updateRemoveButtons() {
            var entries = document.querySelectorAll('#vv-entries .vv-entry');
            entries.forEach(function(entry, i) {
                var btn = entry.querySelector('.vv-remove');
                btn.style.visibility = entries.length > 1 ? 'visible' : 'hidden';
            });
        }

        function bindEvents() {
            var addBtn = document.getElementById('vv-add-vehicle');
            var skipCheck = document.getElementById('vv-skip-check');
            var warning = document.getElementById('vv-warning');

            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    var entries = document.getElementById('vv-entries');
                    var count = entries.querySelectorAll('.vv-entry').length;
                    var newEntry = document.createElement('div');
                    newEntry.className = 'vv-entry';
                    newEntry.setAttribute('data-index', count);
                    newEntry.innerHTML = '<div class="vv-field"><label>Registration Number</label><input type="text" name="vv_reg[]" placeholder="e.g. AB12 CDE" autocomplete="off"></div>'
                        + '<div class="vv-field"><label>VIN Number</label><input type="text" name="vv_vin[]" placeholder="e.g. 4T4BE46K79R107189" autocomplete="off"></div>'
                        + '<button type="button" class="vv-remove" title="Remove">&times;</button>';
                    entries.appendChild(newEntry);
                    updateRemoveButtons();
                });
            }

            // Event delegation for remove buttons
            document.getElementById('vv-entries').addEventListener('click', function(e) {
                if (e.target.classList.contains('vv-remove')) {
                    e.target.closest('.vv-entry').remove();
                    updateRemoveButtons();
                    checkPaymentVisibility();
                }
            });

            if (skipCheck) {
                skipCheck.addEventListener('change', function() {
                    warning.style.display = this.checked ? 'block' : 'none';
                    document.getElementById('vehicle-verification').classList.remove('vv-has-error');
                    checkPaymentVisibility();
                });
            }

            // Clear validation error and check payment visibility when user types
            document.getElementById('vv-entries').addEventListener('input', function() {
                document.getElementById('vehicle-verification').classList.remove('vv-has-error');
                checkPaymentVisibility();
            });

            // Save data to order notes before WooCommerce submits
            interceptCheckoutSubmit();
        }

        function checkPaymentVisibility() {
            var skipCheck = document.getElementById('vv-skip-check');
            var entries = document.querySelectorAll('#vv-entries .vv-entry');
            var hasVehicleData = false;

            entries.forEach(function(entry) {
                var reg = entry.querySelector('input[name="vv_reg[]"]');
                var vin = entry.querySelector('input[name="vv_vin[]"]');
                if ((reg && reg.value.trim()) || (vin && vin.value.trim())) hasVehicleData = true;
            });

            var unlocked = hasVehicleData || (skipCheck && skipCheck.checked);
            var checkoutForm = document.querySelector('.wp-block-woocommerce-checkout, .wc-block-checkout');
            if (checkoutForm) {
                if (unlocked) {
                    checkoutForm.classList.add('vv-payment-unlocked');
                } else {
                    checkoutForm.classList.remove('vv-payment-unlocked');
                }
            }
        }

        function validateVehicleVerification() {
            var section = document.getElementById('vehicle-verification');
            var skipCheck = document.getElementById('vv-skip-check');
            var entries = document.querySelectorAll('#vv-entries .vv-entry');
            var hasVehicleData = false;

            entries.forEach(function(entry) {
                var reg = entry.querySelector('input[name="vv_reg[]"]').value.trim();
                var vin = entry.querySelector('input[name="vv_vin[]"]').value.trim();
                if (reg || vin) hasVehicleData = true;
            });

            var isValid = hasVehicleData || (skipCheck && skipCheck.checked);

            if (!isValid) {
                section.classList.add('vv-has-error');
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            section.classList.remove('vv-has-error');
            return true;
        }

        function interceptCheckoutSubmit() {
            // Watch for WooCommerce Blocks checkout submission
            function bindPlaceOrder(placeOrderBtn) {
                if (placeOrderBtn && !placeOrderBtn._vvBound) {
                    placeOrderBtn._vvBound = true;
                    placeOrderBtn.addEventListener('click', function(e) {
                        if (!validateVehicleVerification()) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            return false;
                        }
                        saveVehicleDataToNotes();
                    }, true);
                }
            }

            var observer = new MutationObserver(function() {
                var placeOrderBtn = document.querySelector('.wc-block-components-checkout-place-order-button');
                bindPlaceOrder(placeOrderBtn);
            });
            observer.observe(document.body, { childList: true, subtree: true });

            // Also try immediately
            bindPlaceOrder(document.querySelector('.wc-block-components-checkout-place-order-button'));
        }

        function saveVehicleDataToNotes() {
            var skipCheck = document.getElementById('vv-skip-check');
            var entries = document.querySelectorAll('#vv-entries .vv-entry');
            var vehicleLines = [];

            entries.forEach(function(entry, i) {
                var reg = entry.querySelector('input[name="vv_reg[]"]').value.trim();
                var vin = entry.querySelector('input[name="vv_vin[]"]').value.trim();
                if (reg || vin) {
                    var line = 'Vehicle ' + (i + 1) + ':';
                    if (reg) line += ' Reg: ' + reg;
                    if (vin) line += ' VIN: ' + vin;
                    vehicleLines.push(line);
                }
            });

            if (skipCheck && skipCheck.checked && vehicleLines.length === 0) {
                vehicleLines.push('[Customer opted to skip vehicle verification - returns policy acknowledged]');
            }

            if (vehicleLines.length > 0) {
                // Find the order notes textarea (WooCommerce Blocks)
                var notesToggle = document.querySelector('.wc-block-checkout__add-note');
                if (notesToggle) {
                    notesToggle.click(); // Open notes if closed
                }

                // Small delay to let the textarea render
                setTimeout(function() {
                    var textarea = document.querySelector('textarea[name="order_comments"], .wc-block-components-textarea');
                    if (textarea) {
                        var existing = textarea.value.trim();
                        var vehicleText = '--- Vehicle Details ---\n' + vehicleLines.join('\n');
                        if (existing && existing.indexOf('--- Vehicle Details ---') === -1) {
                            textarea.value = existing + '\n\n' + vehicleText;
                        } else if (existing.indexOf('--- Vehicle Details ---') !== -1) {
                            // Replace existing vehicle section
                            textarea.value = existing.replace(/--- Vehicle Details ---[\s\S]*$/, vehicleText);
                        } else {
                            textarea.value = vehicleText;
                        }
                        // Trigger React state update
                        var evt = new Event('input', { bubbles: true });
                        textarea.dispatchEvent(evt);
                        var changeEvt = new Event('change', { bubbles: true });
                        textarea.dispatchEvent(changeEvt);
                    }
                }, 300);
            }
        }

        // Wait for WooCommerce Blocks to render
        function waitForCheckout() {
            if (document.querySelector('.wp-block-woocommerce-checkout-fields-block')) {
                insertSection();
            } else {
                var observer = new MutationObserver(function(mutations, obs) {
                    if (document.querySelector('.wp-block-woocommerce-checkout-fields-block')) {
                        obs.disconnect();
                        insertSection();
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', waitForCheckout);
        } else {
            waitForCheckout();
        }
    })();
    </script>
    <?php
}

/**
 * Inject estimated delivery dates into checkout order summary
 */
add_action('wp_footer', 'maxus_checkout_delivery_dates', 21);
function maxus_checkout_delivery_dates() {
    if (!is_checkout()) {
        return;
    }

    // Build product-name-to-date map from cart items
    $delivery_map = array();
    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $name = $product->get_name();
            $date = maxus_get_estimated_delivery_date($product_id);
            $delivery_map[$name] = $date;
        }
    }

    if (empty($delivery_map)) {
        return;
    }
    // Build the HTML server-side
    $items_html = '';
    foreach ($delivery_map as $name => $date) {
        $items_html .= '<div class="delivery-item">'
            . '<span class="delivery-product">' . esc_html($name) . '</span>'
            . '<span class="delivery-date">' . esc_html($date) . '</span>'
            . '</div>';
    }
    ?>
    <script>
    (function() {
        'use strict';

        var sectionHTML = '<div class="maxus-delivery-section" id="maxus-delivery-estimates">'
            + '<h4>Estimated Delivery</h4>'
            + <?php echo wp_json_encode($items_html); ?>
            + '</div>';

        function insertSection() {
            if (document.getElementById('maxus-delivery-estimates')) return;

            // Insert after the order summary block (as a sibling, outside React DOM)
            var summary = document.querySelector('.wp-block-woocommerce-checkout-order-summary-block');
            if (summary) {
                summary.insertAdjacentHTML('afterend', sectionHTML);
                return true;
            }
            return false;
        }

        function start() {
            if (insertSection()) return;

            var observer = new MutationObserver(function(mutations, obs) {
                if (insertSection()) {
                    obs.disconnect();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
    })();
    </script>
    <?php
}

/**
 * Save vehicle verification data to order meta
 * This hooks into the WooCommerce order creation to capture the data from order notes
 */
add_action('woocommerce_checkout_order_created', 'maxus_save_vehicle_verification_data');
function maxus_save_vehicle_verification_data($order) {
    $notes = $order->get_customer_note();
    if (strpos($notes, '--- Vehicle Details ---') !== false) {
        // Extract vehicle details section
        $parts = explode('--- Vehicle Details ---', $notes);
        if (isset($parts[1])) {
            $vehicle_info = trim($parts[1]);
            $order->update_meta_data('_vehicle_verification', $vehicle_info);
            $order->save();
        }
    }
}

/**
 * Display vehicle verification info in admin order view
 */
add_action('woocommerce_admin_order_data_after_shipping_address', 'maxus_display_vehicle_verification_admin');
function maxus_display_vehicle_verification_admin($order) {
    $vehicle_info = $order->get_meta('_vehicle_verification');
    if ($vehicle_info) {
        echo '<div class="clear"></div>';
        echo '<h3>Vehicle Verification</h3>';
        echo '<p>' . nl2br(esc_html($vehicle_info)) . '</p>';
    }
}
