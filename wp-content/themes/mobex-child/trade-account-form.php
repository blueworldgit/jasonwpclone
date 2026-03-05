<?php
/**
 * Trade Account Application Form
 * Shortcode: [trade_account_form]
 * Sends formatted HTML email to accounts@vanparts-direct.co.uk
 */

// Register shortcode
add_shortcode('trade_account_form', 'maxus_trade_account_form');

// Register AJAX handlers
add_action('wp_ajax_trade_account_submit', 'maxus_trade_account_submit');
add_action('wp_ajax_nopriv_trade_account_submit', 'maxus_trade_account_submit');

/**
 * Render the trade account application form
 */
function maxus_trade_account_form() {
    ob_start();
    ?>
    <style>
        .ta-form {
            max-width: 860px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #333;
        }
        .ta-form * { box-sizing: border-box; }
        .ta-form .section-header {
            background: #F29F05;
            color: #fff;
            padding: 10px 16px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 28px;
            margin-bottom: 0;
        }
        .ta-form .section-header:first-child { margin-top: 0; }
        .ta-form .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border: 1px solid #ddd;
            border-top: none;
        }
        .ta-form .form-group {
            padding: 10px 14px;
            border-bottom: 1px solid #eee;
            border-right: 1px solid #eee;
        }
        .ta-form .form-group.full {
            grid-column: 1 / -1;
            border-right: none;
        }
        .ta-form .form-group:nth-child(2n) {
            border-right: none;
        }
        .ta-form .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }
        .ta-form .form-group label .req {
            color: #c0392b;
        }
        .ta-form .form-group input[type="text"],
        .ta-form .form-group input[type="email"],
        .ta-form .form-group input[type="tel"],
        .ta-form .form-group input[type="number"],
        .ta-form .form-group textarea,
        .ta-form .form-group select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
            color: #333;
            background: #fff;
            transition: border-color 0.2s;
        }
        .ta-form .form-group input:focus,
        .ta-form .form-group textarea:focus,
        .ta-form .form-group select:focus {
            outline: none;
            border-color: #F29F05;
            box-shadow: 0 0 0 2px rgba(242,159,5,0.15);
        }
        .ta-form .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .ta-form .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
            padding-top: 4px;
        }
        .ta-form .radio-group label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            text-transform: none;
            font-weight: 400;
            color: #333;
        }
        .ta-form .radio-group input[type="radio"] {
            margin: 0;
        }
        .ta-form .sub-header {
            background: #f5f5f5;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            border: 1px solid #ddd;
            border-top: none;
            text-transform: uppercase;
        }
        .ta-form .terms-box {
            border: 1px solid #ddd;
            border-top: none;
            padding: 18px 20px;
            font-size: 13px;
            line-height: 1.7;
            color: #444;
            background: #fafafa;
        }
        .ta-form .terms-box ol {
            margin: 0 0 16px 0;
            padding-left: 24px;
        }
        .ta-form .terms-box ol li {
            margin-bottom: 8px;
        }
        .ta-form .terms-box h4 {
            margin: 16px 0 6px;
            font-size: 13px;
            text-transform: uppercase;
            color: #333;
        }
        .ta-form .terms-box p {
            margin: 0 0 10px;
        }
        .ta-form .declaration-box {
            border: 1px solid #ddd;
            border-top: none;
            padding: 18px 20px;
        }
        .ta-form .declaration-box .checkbox-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 18px;
        }
        .ta-form .declaration-box .checkbox-row input[type="checkbox"] {
            margin-top: 3px;
            flex-shrink: 0;
        }
        .ta-form .declaration-box .checkbox-row label {
            font-size: 14px;
            font-weight: 400;
            text-transform: none;
            color: #333;
            cursor: pointer;
        }
        .ta-form .declaration-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .ta-form .declaration-grid .form-group {
            padding: 0;
            border: none;
        }
        .ta-form .submit-row {
            margin-top: 24px;
            text-align: center;
        }
        .ta-form .submit-row button {
            background: #F29F05;
            color: #fff;
            border: none;
            padding: 14px 48px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .ta-form .submit-row button:hover {
            background: #D98E04;
        }
        .ta-form .submit-row button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .ta-form .form-message {
            padding: 14px 18px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        .ta-form .form-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        .ta-form .form-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        .ta-form .ref-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #ddd;
            border-top: none;
        }
        .ta-form .ref-column {
            padding: 0;
        }
        .ta-form .ref-column:first-child {
            border-right: 1px solid #ddd;
        }
        .ta-form .ref-column .ref-title {
            background: #f5f5f5;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            border-bottom: 1px solid #eee;
        }
        .ta-form .ref-column .form-group {
            border-right: none;
        }
        /* Honeypot */
        .ta-form .ta-hp { position: absolute; left: -9999px; }

        @media (max-width: 640px) {
            .ta-form .form-grid,
            .ta-form .declaration-grid,
            .ta-form .ref-columns {
                grid-template-columns: 1fr;
            }
            .ta-form .form-group:nth-child(2n) {
                border-right: none;
            }
            .ta-form .ref-column:first-child {
                border-right: none;
                border-bottom: 1px solid #ddd;
            }
        }
    </style>

    <div class="ta-form" id="trade-account-form">
        <div class="form-message" id="ta-form-message"></div>

        <form id="ta-application-form" novalidate>
            <?php wp_nonce_field('trade_account_nonce', 'ta_nonce'); ?>

            <!-- Honeypot -->
            <div class="ta-hp">
                <label for="ta_website">Website</label>
                <input type="text" name="ta_website" id="ta_website" tabindex="-1" autocomplete="off">
            </div>

            <!-- COMPANY DETAILS -->
            <div class="section-header">Company Details</div>
            <p style="background:#f9f0db;border:1px solid #ddd;border-top:none;padding:10px 14px;margin:0;font-size:12px;color:#666;">
                Whether a company or a limited company, the name of the proprietor(s) is required.
            </p>
            <div class="form-grid">
                <div class="form-group full">
                    <label for="company_name">Company Name <span class="req">*</span></label>
                    <input type="text" name="company_name" id="company_name" required>
                </div>
                <div class="form-group full">
                    <label for="proprietor_name">Proprietor's Name <span class="req">*</span></label>
                    <input type="text" name="proprietor_name" id="proprietor_name" required>
                </div>
                <div class="form-group full">
                    <label for="company_address">Company Address <span class="req">*</span></label>
                    <textarea name="company_address" id="company_address" rows="2" required></textarea>
                </div>
                <div class="form-group">
                    <label for="postcode">Post Code <span class="req">*</span></label>
                    <input type="text" name="postcode" id="postcode" required>
                </div>
                <div class="form-group">
                    <label for="landline">Landline Number(s)</label>
                    <input type="tel" name="landline" id="landline">
                </div>
                <div class="form-group">
                    <label for="mobile">Mobile Number(s)</label>
                    <input type="tel" name="mobile" id="mobile">
                </div>
                <div class="form-group full">
                    <label for="contact_name">Contact Name <small>(if a company or limited company)</small></label>
                    <input type="text" name="contact_name" id="contact_name">
                </div>
                <div class="form-group full">
                    <label for="email_address">Email Address <span class="req">*</span></label>
                    <input type="email" name="email_address" id="email_address" required>
                </div>
                <div class="form-group">
                    <label for="nature_of_business">Nature of Business</label>
                    <input type="text" name="nature_of_business" id="nature_of_business">
                </div>
                <div class="form-group">
                    <label for="years_trading">No. of Years Trading</label>
                    <input type="text" name="years_trading" id="years_trading">
                </div>
                <div class="form-group full">
                    <label for="registered_office">Registered Office <small>(if different from above)</small></label>
                    <input type="text" name="registered_office" id="registered_office">
                </div>
                <div class="form-group">
                    <label for="registered_postcode">Registered Office Post Code</label>
                    <input type="text" name="registered_postcode" id="registered_postcode">
                </div>
                <div class="form-group">
                    <label for="company_reg_number">Company Registration Number</label>
                    <input type="text" name="company_reg_number" id="company_reg_number">
                </div>
                <div class="form-group">
                    <label for="vat_number">VAT Registration Number</label>
                    <input type="text" name="vat_number" id="vat_number">
                </div>
            </div>

            <!-- ACCOUNTS CONTACT DETAILS -->
            <div class="section-header">Accounts Contact Details</div>
            <div class="form-grid">
                <div class="form-group full">
                    <label for="accounts_contact_1">Contact Name 1</label>
                    <input type="text" name="accounts_contact_1" id="accounts_contact_1">
                </div>
                <div class="form-group">
                    <label for="accounts_job_title_1">Job Title</label>
                    <input type="text" name="accounts_job_title_1" id="accounts_job_title_1">
                </div>
                <div class="form-group">
                    <label for="accounts_email_1">Email Address</label>
                    <input type="email" name="accounts_email_1" id="accounts_email_1">
                </div>
                <div class="form-group full">
                    <label for="accounts_contact_2">Contact Name 2</label>
                    <input type="text" name="accounts_contact_2" id="accounts_contact_2">
                </div>
                <div class="form-group">
                    <label for="accounts_job_title_2">Job Title</label>
                    <input type="text" name="accounts_job_title_2" id="accounts_job_title_2">
                </div>
                <div class="form-group">
                    <label for="accounts_email_2">Email Address</label>
                    <input type="email" name="accounts_email_2" id="accounts_email_2">
                </div>
            </div>

            <!-- GROUP MEMBERSHIP & CREDIT -->
            <div class="section-header">Additional Details</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Is your company a member of a larger group?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="group_member" value="yes"> Yes</label>
                        <label><input type="radio" name="group_member" value="no" checked> No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="group_details">If yes, give details</label>
                    <input type="text" name="group_details" id="group_details">
                </div>
                <div class="form-group">
                    <label for="monthly_credit">Monthly Credit Amount Required</label>
                    <input type="text" name="monthly_credit" id="monthly_credit" placeholder="&pound;">
                </div>
                <div class="form-group">
                    <label for="purchase_order_details">Purchase Order Required for Works? <small>(if yes, supply details)</small></label>
                    <input type="text" name="purchase_order_details" id="purchase_order_details">
                </div>
            </div>

            <!-- BANK ACCOUNT DETAILS -->
            <div class="section-header">Bank Account Details</div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="bank_account_name">Name (as it appears on the A/C)</label>
                    <input type="text" name="bank_account_name" id="bank_account_name">
                </div>
                <div class="form-group">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name">
                </div>
                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input type="text" name="account_number" id="account_number">
                </div>
                <div class="form-group">
                    <label for="sort_code">Sort Code</label>
                    <input type="text" name="sort_code" id="sort_code">
                </div>
            </div>

            <!-- TRADE REFERENCES -->
            <div class="section-header">Trade References</div>
            <div class="ref-columns">
                <div class="ref-column">
                    <div class="ref-title">Trade Reference 1</div>
                    <div class="form-group">
                        <label for="ref1_name">Name</label>
                        <input type="text" name="ref1_name" id="ref1_name">
                    </div>
                    <div class="form-group">
                        <label for="ref1_address">Address</label>
                        <textarea name="ref1_address" id="ref1_address" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="ref1_postcode">Post Code</label>
                        <input type="text" name="ref1_postcode" id="ref1_postcode">
                    </div>
                    <div class="form-group">
                        <label for="ref1_email">Email</label>
                        <input type="email" name="ref1_email" id="ref1_email">
                    </div>
                </div>
                <div class="ref-column">
                    <div class="ref-title">Trade Reference 2</div>
                    <div class="form-group">
                        <label for="ref2_name">Name</label>
                        <input type="text" name="ref2_name" id="ref2_name">
                    </div>
                    <div class="form-group">
                        <label for="ref2_address">Address</label>
                        <textarea name="ref2_address" id="ref2_address" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="ref2_postcode">Post Code</label>
                        <input type="text" name="ref2_postcode" id="ref2_postcode">
                    </div>
                    <div class="form-group">
                        <label for="ref2_email">Email</label>
                        <input type="email" name="ref2_email" id="ref2_email">
                    </div>
                </div>
            </div>

            <!-- TERMS & CONDITIONS -->
            <div class="section-header">Credit Account Terms</div>
            <div class="terms-box">
                <ol>
                    <li>Your application will be processed as quickly as possible. We reserve the right to refuse credit facilities without giving reasons.</li>
                    <li>Payment terms are strictly 14 days from date of invoice.</li>
                    <li>We reserve the right to charge interest on all accounts in excess of our terms at the rate of 4% above Barclays Bank PLC base lending rate per month or per part month.</li>
                    <li>Queries or disputes regarding invoices or statements must be notified to our credit control department within seven days of receipt. Credit facilities will be withdrawn and legal action taken to recover accounts which become overdue without explanation.</li>
                    <li>We reserve the right to alter or amend credit facilities offered to you or to withdraw the facilities at any given time.</li>
                </ol>

                <h4>Limited Companies</h4>
                <p>The director of the company named in this agreement personally and unconditionally guarantees the payment of all liabilities, losses, damages and costs due to Van Parts Direct Ltd under this contract, including where such amounts cannot be recovered from the company. By signing this agreement, I acknowledge that I have had the opportunity to seek independent legal advice and confirm that I fully understand and accept the personal obligations and consequences of entering into this contract.</p>

                <h4>General Data Protection Regulation</h4>
                <p>Van Parts Direct Ltd holds some contact details for you, such as details of your business address, email address and telephone numbers. We hold these because you have given them to us so we can send you details regarding special offers, orders and general information involving the supply of sales vehicles, hire vehicles, servicing, maintenance, parts and labour.</p>
            </div>

            <!-- DECLARATION -->
            <div class="section-header">Declaration</div>
            <div class="declaration-box">
                <div class="checkbox-row">
                    <input type="checkbox" name="declaration_agree" id="declaration_agree" value="1" required>
                    <label for="declaration_agree">I/We have read and agree to the terms and conditions above. <span class="req">*</span></label>
                </div>
                <div class="declaration-grid">
                    <div class="form-group">
                        <label for="declaration_name">Name (Printed) <span class="req">*</span></label>
                        <input type="text" name="declaration_name" id="declaration_name" required>
                    </div>
                    <div class="form-group">
                        <label for="declaration_company">Company</label>
                        <input type="text" name="declaration_company" id="declaration_company">
                    </div>
                    <div class="form-group">
                        <label for="declaration_position">Position <span class="req">*</span></label>
                        <input type="text" name="declaration_position" id="declaration_position" required>
                    </div>
                    <div class="form-group">
                        <label for="declaration_date">Date</label>
                        <input type="text" name="declaration_date" id="declaration_date" value="<?php echo esc_attr(date('d/m/Y')); ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="submit-row">
                <button type="submit" id="ta-submit-btn">Submit Application</button>
            </div>
        </form>
    </div>

    <script>
    (function() {
        var form = document.getElementById('ta-application-form');
        var msgEl = document.getElementById('ta-form-message');
        var btn = document.getElementById('ta-submit-btn');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Client-side validation
            var required = [
                {id: 'company_name', label: 'Company Name'},
                {id: 'proprietor_name', label: "Proprietor's Name"},
                {id: 'company_address', label: 'Company Address'},
                {id: 'postcode', label: 'Post Code'},
                {id: 'email_address', label: 'Email Address'},
                {id: 'declaration_name', label: 'Name (Printed)'},
                {id: 'declaration_position', label: 'Position'}
            ];

            var missing = [];
            for (var i = 0; i < required.length; i++) {
                var el = document.getElementById(required[i].id);
                if (!el || !el.value.trim()) {
                    missing.push(required[i].label);
                    if (el) el.style.borderColor = '#c0392b';
                } else {
                    if (el) el.style.borderColor = '';
                }
            }

            var checkbox = document.getElementById('declaration_agree');
            if (!checkbox.checked) {
                missing.push('Declaration agreement');
            }

            if (missing.length > 0) {
                showMessage('error', 'Please complete the following required fields: ' + missing.join(', '));
                return;
            }

            // Email validation
            var emailField = document.getElementById('email_address');
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailField.value.trim())) {
                showMessage('error', 'Please enter a valid email address.');
                emailField.style.borderColor = '#c0392b';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Submitting...';
            showMessage('', '');

            var formData = new FormData(form);
            formData.append('action', 'trade_account_submit');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>');
            xhr.onload = function() {
                btn.disabled = false;
                btn.textContent = 'Submit Application';
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) {
                        showMessage('success', resp.data.message || 'Your application has been submitted successfully. We will be in touch shortly.');
                        form.reset();
                        document.getElementById('declaration_date').value = '<?php echo esc_js(date('d/m/Y')); ?>';
                        form.style.display = 'none';
                    } else {
                        showMessage('error', resp.data.message || 'There was an error submitting your application. Please try again.');
                    }
                } catch(ex) {
                    showMessage('error', 'There was an unexpected error. Please try again later.');
                }
            };
            xhr.onerror = function() {
                btn.disabled = false;
                btn.textContent = 'Submit Application';
                showMessage('error', 'Network error. Please check your connection and try again.');
            };
            xhr.send(formData);
        });

        function showMessage(type, text) {
            msgEl.className = 'form-message' + (type ? ' ' + type : '');
            msgEl.textContent = text;
            msgEl.style.display = text ? 'block' : 'none';
            if (text) {
                msgEl.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Handle AJAX form submission
 */
function maxus_trade_account_submit() {
    // Verify nonce
    if (!isset($_POST['ta_nonce']) || !wp_verify_nonce($_POST['ta_nonce'], 'trade_account_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
    }

    // Honeypot check
    if (!empty($_POST['ta_website'])) {
        wp_send_json_error(array('message' => 'Submission rejected.'));
    }

    // Sanitize all fields
    $fields = array(
        'company_name'        => sanitize_text_field($_POST['company_name'] ?? ''),
        'proprietor_name'     => sanitize_text_field($_POST['proprietor_name'] ?? ''),
        'company_address'     => sanitize_textarea_field($_POST['company_address'] ?? ''),
        'postcode'            => sanitize_text_field($_POST['postcode'] ?? ''),
        'landline'            => sanitize_text_field($_POST['landline'] ?? ''),
        'mobile'              => sanitize_text_field($_POST['mobile'] ?? ''),
        'contact_name'        => sanitize_text_field($_POST['contact_name'] ?? ''),
        'email_address'       => sanitize_email($_POST['email_address'] ?? ''),
        'nature_of_business'  => sanitize_text_field($_POST['nature_of_business'] ?? ''),
        'years_trading'       => sanitize_text_field($_POST['years_trading'] ?? ''),
        'registered_office'   => sanitize_text_field($_POST['registered_office'] ?? ''),
        'registered_postcode' => sanitize_text_field($_POST['registered_postcode'] ?? ''),
        'company_reg_number'  => sanitize_text_field($_POST['company_reg_number'] ?? ''),
        'vat_number'          => sanitize_text_field($_POST['vat_number'] ?? ''),
        // Accounts contacts
        'accounts_contact_1'    => sanitize_text_field($_POST['accounts_contact_1'] ?? ''),
        'accounts_job_title_1'  => sanitize_text_field($_POST['accounts_job_title_1'] ?? ''),
        'accounts_email_1'      => sanitize_email($_POST['accounts_email_1'] ?? ''),
        'accounts_contact_2'    => sanitize_text_field($_POST['accounts_contact_2'] ?? ''),
        'accounts_job_title_2'  => sanitize_text_field($_POST['accounts_job_title_2'] ?? ''),
        'accounts_email_2'      => sanitize_email($_POST['accounts_email_2'] ?? ''),
        // Group & credit
        'group_member'          => sanitize_text_field($_POST['group_member'] ?? 'no'),
        'group_details'         => sanitize_text_field($_POST['group_details'] ?? ''),
        'monthly_credit'        => sanitize_text_field($_POST['monthly_credit'] ?? ''),
        'purchase_order_details'=> sanitize_text_field($_POST['purchase_order_details'] ?? ''),
        // Bank
        'bank_account_name'   => sanitize_text_field($_POST['bank_account_name'] ?? ''),
        'bank_name'           => sanitize_text_field($_POST['bank_name'] ?? ''),
        'account_number'      => sanitize_text_field($_POST['account_number'] ?? ''),
        'sort_code'           => sanitize_text_field($_POST['sort_code'] ?? ''),
        // Trade references
        'ref1_name'           => sanitize_text_field($_POST['ref1_name'] ?? ''),
        'ref1_address'        => sanitize_textarea_field($_POST['ref1_address'] ?? ''),
        'ref1_postcode'       => sanitize_text_field($_POST['ref1_postcode'] ?? ''),
        'ref1_email'          => sanitize_email($_POST['ref1_email'] ?? ''),
        'ref2_name'           => sanitize_text_field($_POST['ref2_name'] ?? ''),
        'ref2_address'        => sanitize_textarea_field($_POST['ref2_address'] ?? ''),
        'ref2_postcode'       => sanitize_text_field($_POST['ref2_postcode'] ?? ''),
        'ref2_email'          => sanitize_email($_POST['ref2_email'] ?? ''),
        // Declaration
        'declaration_agree'   => !empty($_POST['declaration_agree']) ? 'Yes' : 'No',
        'declaration_name'    => sanitize_text_field($_POST['declaration_name'] ?? ''),
        'declaration_company' => sanitize_text_field($_POST['declaration_company'] ?? ''),
        'declaration_position'=> sanitize_text_field($_POST['declaration_position'] ?? ''),
        'declaration_date'    => sanitize_text_field($_POST['declaration_date'] ?? date('d/m/Y')),
    );

    // Validate required fields
    $required = array(
        'company_name'        => 'Company Name',
        'proprietor_name'     => "Proprietor's Name",
        'company_address'     => 'Company Address',
        'postcode'            => 'Post Code',
        'email_address'       => 'Email Address',
        'declaration_name'    => 'Name (Printed)',
        'declaration_position'=> 'Position',
    );

    $missing = array();
    foreach ($required as $key => $label) {
        if (empty($fields[$key])) {
            $missing[] = $label;
        }
    }

    if ($fields['declaration_agree'] !== 'Yes') {
        $missing[] = 'Declaration agreement';
    }

    if (!empty($missing)) {
        wp_send_json_error(array('message' => 'Required fields missing: ' . implode(', ', $missing)));
    }

    if (!is_email($fields['email_address'])) {
        wp_send_json_error(array('message' => 'Please provide a valid email address.'));
    }

    // Build HTML email
    $f = $fields; // shorthand
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#333;max-width:700px;margin:0 auto;">';
    $html .= '<h2 style="background:#F29F05;color:#fff;padding:14px 20px;margin:0;">New Trade Account Application</h2>';

    // Helper for sections
    $html .= maxus_ta_email_section('Company Details', array(
        'Company Name'             => $f['company_name'],
        "Proprietor's Name"        => $f['proprietor_name'],
        'Company Address'          => nl2br(esc_html($f['company_address'])),
        'Post Code'                => $f['postcode'],
        'Landline'                 => $f['landline'],
        'Mobile'                   => $f['mobile'],
        'Contact Name'             => $f['contact_name'],
        'Email Address'            => $f['email_address'],
        'Nature of Business'       => $f['nature_of_business'],
        'Years Trading'            => $f['years_trading'],
        'Registered Office'        => $f['registered_office'],
        'Registered Office Post Code' => $f['registered_postcode'],
        'Company Reg Number'       => $f['company_reg_number'],
        'VAT Number'               => $f['vat_number'],
    ));

    $html .= maxus_ta_email_section('Accounts Contact Details', array(
        'Contact 1 Name'       => $f['accounts_contact_1'],
        'Contact 1 Job Title'  => $f['accounts_job_title_1'],
        'Contact 1 Email'      => $f['accounts_email_1'],
        'Contact 2 Name'       => $f['accounts_contact_2'],
        'Contact 2 Job Title'  => $f['accounts_job_title_2'],
        'Contact 2 Email'      => $f['accounts_email_2'],
    ));

    $html .= maxus_ta_email_section('Additional Details', array(
        'Member of Larger Group'   => ucfirst($f['group_member']),
        'Group Details'            => $f['group_details'],
        'Monthly Credit Required'  => $f['monthly_credit'],
        'Purchase Order Details'   => $f['purchase_order_details'],
    ));

    $html .= maxus_ta_email_section('Bank Account Details', array(
        'Name on Account'  => $f['bank_account_name'],
        'Bank Name'        => $f['bank_name'],
        'Account Number'   => $f['account_number'],
        'Sort Code'        => $f['sort_code'],
    ));

    $html .= maxus_ta_email_section('Trade Reference 1', array(
        'Name'      => $f['ref1_name'],
        'Address'   => nl2br(esc_html($f['ref1_address'])),
        'Post Code' => $f['ref1_postcode'],
        'Email'     => $f['ref1_email'],
    ));

    $html .= maxus_ta_email_section('Trade Reference 2', array(
        'Name'      => $f['ref2_name'],
        'Address'   => nl2br(esc_html($f['ref2_address'])),
        'Post Code' => $f['ref2_postcode'],
        'Email'     => $f['ref2_email'],
    ));

    $html .= maxus_ta_email_section('Declaration', array(
        'Agreed to Terms' => $f['declaration_agree'],
        'Name (Printed)'  => $f['declaration_name'],
        'Company'          => $f['declaration_company'],
        'Position'         => $f['declaration_position'],
        'Date'             => $f['declaration_date'],
    ));

    $html .= '<p style="font-size:12px;color:#999;padding:10px 20px;">Submitted via the Trade Account Application form on ' . esc_html(get_bloginfo('name')) . '.</p>';
    $html .= '</body></html>';

    // Send email
    $to = 'accounts@vanparts-direct.co.uk';
    $subject = 'New Trade Account Application - ' . $f['company_name'];
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <no-reply@' . parse_url(home_url(), PHP_URL_HOST) . '>',
    );

    // Add reply-to if applicant provided a valid email
    if (is_email($f['email_address'])) {
        $headers[] = 'Reply-To: ' . $f['email_address'];
    }

    $sent = wp_mail($to, $subject, $html, $headers);

    if ($sent) {
        wp_send_json_success(array('message' => 'Your trade account application has been submitted successfully. We will review your application and be in touch shortly.'));
    } else {
        wp_send_json_error(array('message' => 'There was an error sending your application. Please try again later or contact us directly.'));
    }
}

/**
 * Helper to build an email section
 */
function maxus_ta_email_section($title, $rows) {
    $html = '<h3 style="background:#f5f5f5;padding:10px 20px;margin:24px 0 0;font-size:14px;border-bottom:2px solid #F29F05;">' . esc_html($title) . '</h3>';
    $html .= '<table style="width:100%;border-collapse:collapse;">';
    foreach ($rows as $label => $value) {
        $display = $value !== '' ? $value : '<span style="color:#999;">—</span>';
        $html .= '<tr>';
        $html .= '<td style="padding:8px 20px;border-bottom:1px solid #eee;font-weight:600;width:40%;vertical-align:top;font-size:13px;">' . esc_html($label) . '</td>';
        $html .= '<td style="padding:8px 20px;border-bottom:1px solid #eee;font-size:13px;">' . $display . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}
