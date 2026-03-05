<?php
/**
 * Price Enquiry Modal Form
 * Provides a "Request a Price" button + modal for products without a price.
 * Sends formatted HTML email to accounts@vanparts-direct.co.uk
 */

// Output the modal HTML once in the footer
add_action('wp_footer', 'maxus_price_enquiry_modal');

// Register AJAX handlers
add_action('wp_ajax_price_enquiry_submit', 'maxus_price_enquiry_submit');
add_action('wp_ajax_nopriv_price_enquiry_submit', 'maxus_price_enquiry_submit');

/**
 * Returns the HTML for a "Request a Price" button.
 * Use data attributes to pass SKU/name into the modal on click.
 */
function maxus_price_enquiry_button($sku, $name) {
    return '<button type="button" class="price-enquiry-btn" data-sku="' . esc_attr($sku) . '" data-name="' . esc_attr($name) . '">Request a Price</button>';
}

/**
 * Output the modal markup, CSS, and JS once in wp_footer.
 */
function maxus_price_enquiry_modal() {
    // Only output if we're on a page that could have the button
    // (vehicle templates or product pages). The JS is tiny so always outputting is fine.
    ?>
    <style>
        /* --- Price Enquiry Button --- */
        .price-enquiry-btn {
            background: #F29F05;
            color: #fff;
            border: none;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 3px;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .price-enquiry-btn:hover {
            background: #D98E04;
        }
        /* Larger variant for single product page */
        .price-enquiry-btn.btn-large {
            padding: 12px 32px;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* --- Modal Overlay --- */
        .pe-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 100000;
            justify-content: center;
            align-items: center;
        }
        .pe-modal-overlay.active {
            display: flex;
        }

        /* --- Modal Box --- */
        .pe-modal {
            background: #fff;
            width: 480px;
            max-width: 94vw;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 6px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #333;
        }
        .pe-modal * { box-sizing: border-box; }
        .pe-modal-header {
            background: #F29F05;
            color: #fff;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 6px 6px 0 0;
        }
        .pe-modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .pe-modal-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 22px;
            cursor: pointer;
            padding: 0 4px;
            line-height: 1;
            opacity: 0.85;
        }
        .pe-modal-close:hover { opacity: 1; }
        .pe-modal-body {
            padding: 20px;
        }
        .pe-modal-body .pe-field {
            margin-bottom: 14px;
        }
        .pe-modal-body .pe-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }
        .pe-modal-body .pe-field label .req {
            color: #c0392b;
        }
        .pe-modal-body .pe-field input,
        .pe-modal-body .pe-field textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
            color: #333;
            background: #fff;
            transition: border-color 0.2s;
        }
        .pe-modal-body .pe-field input:focus,
        .pe-modal-body .pe-field textarea:focus {
            outline: none;
            border-color: #F29F05;
            box-shadow: 0 0 0 2px rgba(242,159,5,0.15);
        }
        .pe-modal-body .pe-field input[readonly] {
            background: #f5f5f5;
            color: #666;
        }
        .pe-modal-body .pe-field textarea {
            resize: vertical;
            min-height: 70px;
        }
        .pe-modal-body .pe-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .pe-modal-submit {
            background: #F29F05;
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            margin-top: 4px;
        }
        .pe-modal-submit:hover { background: #D98E04; }
        .pe-modal-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        .pe-modal-message {
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 14px;
            display: none;
        }
        .pe-modal-message.success {
            background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: block;
        }
        .pe-modal-message.error {
            background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; display: block;
        }
        /* Honeypot */
        .pe-hp { position: absolute; left: -9999px; }

        @media (max-width: 540px) {
            .pe-modal { width: 100%; max-width: 100vw; border-radius: 0; max-height: 100vh; }
            .pe-modal-header { border-radius: 0; }
            .pe-modal-body .pe-row { grid-template-columns: 1fr; gap: 0; }
        }
    </style>

    <!-- Price Enquiry Modal -->
    <div class="pe-modal-overlay" id="pe-modal-overlay">
        <div class="pe-modal">
            <div class="pe-modal-header">
                <h3>Request a Price</h3>
                <button type="button" class="pe-modal-close" id="pe-modal-close">&times;</button>
            </div>
            <div class="pe-modal-body">
                <div class="pe-modal-message" id="pe-modal-message"></div>
                <form id="pe-enquiry-form" novalidate>
                    <?php wp_nonce_field('price_enquiry_nonce', 'pe_nonce'); ?>
                    <!-- Honeypot -->
                    <div class="pe-hp">
                        <label for="pe_website">Website</label>
                        <input type="text" name="pe_website" id="pe_website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="pe-row">
                        <div class="pe-field">
                            <label for="pe_sku">Part SKU</label>
                            <input type="text" name="pe_sku" id="pe_sku" readonly>
                        </div>
                        <div class="pe-field">
                            <label for="pe_part_name">Part Name</label>
                            <input type="text" name="pe_part_name" id="pe_part_name" readonly>
                        </div>
                    </div>

                    <div class="pe-field">
                        <label for="pe_name">Your Name <span class="req">*</span></label>
                        <input type="text" name="pe_name" id="pe_name" required>
                    </div>

                    <div class="pe-row">
                        <div class="pe-field">
                            <label for="pe_email">Email Address <span class="req">*</span></label>
                            <input type="email" name="pe_email" id="pe_email" required>
                        </div>
                        <div class="pe-field">
                            <label for="pe_telephone">Telephone <span class="req">*</span></label>
                            <input type="tel" name="pe_telephone" id="pe_telephone" required>
                        </div>
                    </div>

                    <div class="pe-field">
                        <label for="pe_message">Message</label>
                        <textarea name="pe_message" id="pe_message" rows="3"></textarea>
                    </div>

                    <button type="submit" class="pe-modal-submit" id="pe-submit-btn">Send Enquiry</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var overlay = document.getElementById('pe-modal-overlay');
        var closeBtn = document.getElementById('pe-modal-close');
        var form = document.getElementById('pe-enquiry-form');
        var msgEl = document.getElementById('pe-modal-message');
        var submitBtn = document.getElementById('pe-submit-btn');
        var skuField = document.getElementById('pe_sku');
        var nameField = document.getElementById('pe_part_name');
        var messageField = document.getElementById('pe_message');

        // Open modal when any .price-enquiry-btn is clicked
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.price-enquiry-btn');
            if (!btn) return;
            e.preventDefault();
            var sku = btn.getAttribute('data-sku') || '';
            var partName = btn.getAttribute('data-name') || '';
            skuField.value = sku;
            nameField.value = partName;
            messageField.value = 'Price enquiry for part ' + sku;
            // Reset form state but keep pre-populated fields
            document.getElementById('pe_name').value = '';
            document.getElementById('pe_email').value = '';
            document.getElementById('pe_telephone').value = '';
            document.getElementById('pe_website').value = '';
            msgEl.className = 'pe-modal-message';
            msgEl.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Enquiry';
            form.style.display = '';
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close modal
        function closeModal() {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay.classList.contains('active')) closeModal();
        });

        // Submit form via AJAX
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Client-side validation
            var name = document.getElementById('pe_name').value.trim();
            var email = document.getElementById('pe_email').value.trim();
            var tel = document.getElementById('pe_telephone').value.trim();
            var missing = [];
            if (!name) missing.push('Name');
            if (!email) missing.push('Email');
            if (!tel) missing.push('Telephone');
            if (missing.length) {
                showMsg('error', 'Please complete: ' + missing.join(', '));
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showMsg('error', 'Please enter a valid email address.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            showMsg('', '');

            var formData = new FormData(form);
            formData.append('action', 'price_enquiry_submit');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>');
            xhr.onload = function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Enquiry';
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) {
                        showMsg('success', resp.data.message || 'Your enquiry has been sent. We will be in touch shortly.');
                        form.style.display = 'none';
                    } else {
                        showMsg('error', resp.data.message || 'There was an error. Please try again.');
                    }
                } catch(ex) {
                    showMsg('error', 'Unexpected error. Please try again later.');
                }
            };
            xhr.onerror = function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Enquiry';
                showMsg('error', 'Network error. Please check your connection.');
            };
            xhr.send(formData);
        });

        function showMsg(type, text) {
            msgEl.className = 'pe-modal-message' + (type ? ' ' + type : '');
            msgEl.textContent = text;
            msgEl.style.display = text ? 'block' : 'none';
        }
    })();
    </script>
    <?php
}

/**
 * Handle AJAX form submission
 */
function maxus_price_enquiry_submit() {
    // Verify nonce
    if (!isset($_POST['pe_nonce']) || !wp_verify_nonce($_POST['pe_nonce'], 'price_enquiry_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
    }

    // Honeypot check
    if (!empty($_POST['pe_website'])) {
        wp_send_json_error(array('message' => 'Submission rejected.'));
    }

    // Sanitize fields
    $sku       = sanitize_text_field($_POST['pe_sku'] ?? '');
    $part_name = sanitize_text_field($_POST['pe_part_name'] ?? '');
    $name      = sanitize_text_field($_POST['pe_name'] ?? '');
    $email     = sanitize_email($_POST['pe_email'] ?? '');
    $telephone = sanitize_text_field($_POST['pe_telephone'] ?? '');
    $message   = sanitize_textarea_field($_POST['pe_message'] ?? '');

    // Validate required fields
    $missing = array();
    if (empty($name))      $missing[] = 'Name';
    if (empty($email))     $missing[] = 'Email';
    if (empty($telephone)) $missing[] = 'Telephone';

    if (!empty($missing)) {
        wp_send_json_error(array('message' => 'Required fields missing: ' . implode(', ', $missing)));
    }

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Please provide a valid email address.'));
    }

    // Build HTML email
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
          . '<body style="font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;">';
    $html .= '<h2 style="background:#F29F05;color:#fff;padding:14px 20px;margin:0;">Price Enquiry</h2>';
    $html .= '<table style="width:100%;border-collapse:collapse;">';

    $rows = array(
        'Part SKU'   => $sku,
        'Part Name'  => $part_name,
        'Name'       => $name,
        'Email'      => $email,
        'Telephone'  => $telephone,
        'Message'    => nl2br(esc_html($message)),
    );
    foreach ($rows as $label => $value) {
        $display = $value !== '' ? $value : '<span style="color:#999;">&mdash;</span>';
        $html .= '<tr>';
        $html .= '<td style="padding:10px 20px;border-bottom:1px solid #eee;font-weight:600;width:35%;vertical-align:top;font-size:14px;">' . esc_html($label) . '</td>';
        $html .= '<td style="padding:10px 20px;border-bottom:1px solid #eee;font-size:14px;">' . $display . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $html .= '<p style="font-size:12px;color:#999;padding:10px 20px;">Submitted via the Price Enquiry form on ' . esc_html(get_bloginfo('name')) . '.</p>';
    $html .= '</body></html>';

    // Send email
    $to = 'accounts@vanparts-direct.co.uk';
    $subject = 'Price Enquiry - ' . $sku . ' - ' . $part_name;
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <no-reply@' . parse_url(home_url(), PHP_URL_HOST) . '>',
    );
    if (is_email($email)) {
        $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    }

    $sent = wp_mail($to, $subject, $html, $headers);

    if ($sent) {
        wp_send_json_success(array('message' => 'Your price enquiry has been sent successfully. We will be in touch shortly.'));
    } else {
        wp_send_json_error(array('message' => 'There was an error sending your enquiry. Please try again later or contact us directly.'));
    }
}
