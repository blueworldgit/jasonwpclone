<?php
/**
 * Custom Footer Template - Maxus Van Parts
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<style>
/* ── Footer Reset & Base ── */
.mvp-footer * { box-sizing: border-box; }

.mvp-footer {
    background: #1a1a2e;
    color: #ccc;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 14px;
    line-height: 1.7;
    padding: 0;
    margin: 0;
    width: 100%;
}

/* ── Main Footer Grid ── */
.mvp-footer-main {
    max-width: 1300px;
    margin: 0 auto;
    padding: 50px 30px 40px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr 1fr 1fr;
    gap: 30px;
}

.mvp-footer-col h4 {
    color: #fff;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 18px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #F29F05;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.mvp-footer-col ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.mvp-footer-col ul li {
    margin-bottom: 8px;
}

.mvp-footer-col ul li a {
    color: #ccc;
    text-decoration: none;
    transition: color 0.2s ease;
}

.mvp-footer-col ul li a:hover {
    color: #F29F05;
}

/* ── Company Info Column ── */
.mvp-footer-company-name {
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 4px 0;
}

.mvp-footer-trading {
    font-size: 12px;
    color: #999;
    margin-bottom: 16px;
}

.mvp-footer-contact {
    margin-bottom: 16px;
}

.mvp-footer-contact p {
    margin: 0 0 6px 0;
    color: #ccc;
    font-size: 13px;
    line-height: 1.6;
}

.mvp-footer-contact a {
    color: #F29F05;
    text-decoration: none;
}

.mvp-footer-contact a:hover {
    color: #fff;
}

.mvp-footer-phone {
    font-size: 16px !important;
    font-weight: 600;
    color: #fff !important;
}

.mvp-footer-reg {
    font-size: 12px;
    color: #888;
    margin-bottom: 16px;
}

.mvp-footer-reg p {
    margin: 0 0 2px 0;
}

/* ── Payment Icons ── */
.mvp-footer-payments {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.mvp-footer-payments .pay-icon {
    background: #fff;
    color: #333;
    border-radius: 4px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    height: 28px;
}

.mvp-footer-payments .pay-visa { color: #1a1f71; }
.mvp-footer-payments .pay-amex { color: #006fcf; }
.mvp-footer-payments .pay-maestro { color: #cc0000; }
.mvp-footer-payments .pay-mastercard { color: #eb001b; }

/* ── External Links ── */
.mvp-footer-col ul li a[target="_blank"]::after {
    content: " \2197";
    font-size: 11px;
    opacity: 0.6;
}

/* ── Bottom Bar ── */
.mvp-footer-bottom {
    border-top: 1px solid #2a2a3e;
    background: #151525;
}

.mvp-footer-bottom-inner {
    max-width: 1300px;
    margin: 0 auto;
    padding: 18px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.mvp-footer-copyright {
    color: #888;
    font-size: 13px;
    margin: 0;
}

.mvp-footer-bottom-links {
    display: flex;
    gap: 8px;
    align-items: center;
    font-size: 13px;
}

.mvp-footer-bottom-links a {
    color: #888;
    text-decoration: none;
    transition: color 0.2s ease;
}

.mvp-footer-bottom-links a:hover {
    color: #F29F05;
}

.mvp-footer-bottom-links .sep {
    color: #555;
}

/* ── Responsive: Tablet ── */
@media (max-width: 1024px) {
    .mvp-footer-main {
        grid-template-columns: 1fr 1fr;
        gap: 30px 40px;
        padding: 40px 24px 30px;
    }
    .mvp-footer-col:first-child {
        grid-column: 1 / -1;
    }
    .mvp-footer-payments {
        justify-content: flex-start;
    }
}

/* ── Responsive: Mobile ── */
@media (max-width: 600px) {
    .mvp-footer-main {
        grid-template-columns: 1fr;
        gap: 24px;
        padding: 30px 20px 24px;
    }
    .mvp-footer-col h4 {
        font-size: 15px;
        margin-bottom: 12px;
        padding-bottom: 8px;
    }
    .mvp-footer-bottom-inner {
        flex-direction: column;
        text-align: center;
        padding: 14px 20px;
    }
}
</style>

<footer class="mvp-footer">

    <!-- Main Footer -->
    <div class="mvp-footer-main">

        <!-- Column 1: Company Info -->
        <div class="mvp-footer-col">
            <h4>Maxus Parts Direct</h4>
            <div class="mvp-footer-trading">A trading name of Van Parts Direct Ltd</div>

            <div class="mvp-footer-contact">
                <p>Unit 1-10, Cherry Tree Road,<br>Tibenham, NR16 1PH</p>
                <p class="mvp-footer-phone"><a href="tel:01953528800">01953 528 800</a></p>
                <p><a href="mailto:accounts@vanparts-direct.co.uk">accounts@vanparts-direct.co.uk</a></p>
            </div>

            <div class="mvp-footer-reg">
                <p>Company Reg: 16322863</p>
                <p>VAT No: 490 9953 39</p>
            </div>

            <div class="mvp-footer-payments">
                <span class="pay-icon pay-visa">VISA</span>
                <span class="pay-icon pay-mastercard">Mastercard</span>
                <span class="pay-icon pay-amex">AMEX</span>
                <span class="pay-icon pay-maestro">Maestro</span>
            </div>
        </div>

        <!-- Column 2: Quick Links -->
        <div class="mvp-footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?php echo esc_url( home_url('/') ); ?>">Home</a></li>
                <li><a href="<?php echo esc_url( home_url('/shop/') ); ?>">Shop</a></li>
                <li><a href="<?php echo esc_url( home_url('/my-account/') ); ?>">My Account</a></li>
                <li><a href="<?php echo esc_url( home_url('/cart/') ); ?>">Cart</a></li>
                <li><a href="<?php echo esc_url( home_url('/wishlist/') ); ?>">Wishlist</a></li>
            </ul>
        </div>

        <!-- Column 3: Information -->
        <div class="mvp-footer-col">
            <h4>Information</h4>
            <ul>
                <li><a href="<?php echo esc_url( home_url('/about-us/') ); ?>">About Us</a></li>
                <li><a href="<?php echo esc_url( home_url('/contact-us/') ); ?>">Contact Us</a></li>
                <li><a href="<?php echo esc_url( home_url('/terms-and-conditions/') ); ?>">Terms &amp; Conditions</a></li>
                <li><a href="<?php echo esc_url( home_url('/privacy-policy/') ); ?>">Privacy Policy</a></li>
                <li><a href="<?php echo esc_url( home_url('/gdpr-data-protection/') ); ?>">GDPR Data Protection</a></li>
                <li><a href="<?php echo esc_url( home_url('/refund_returns/') ); ?>">Returns &amp; Exchanges</a></li>
            </ul>
        </div>

        <!-- Column 4: Vehicles -->
        <div class="mvp-footer-col">
            <h4>Vehicles</h4>
            <ul>
                <li><a href="<?php echo esc_url( home_url('/maxus-e-deliver-3/') ); ?>">E Deliver 3</a></li>
                <li><a href="<?php echo esc_url( home_url('/maxus-e-deliver-7/') ); ?>">E Deliver 7</a></li>
                <li><a href="<?php echo esc_url( home_url('/maxus-e-deliver-9/') ); ?>">E Deliver 9</a></li>
                <li><a href="<?php echo esc_url( home_url('/maxus-t90-ev/') ); ?>">T90 EV</a></li>
            </ul>
        </div>

        <!-- Column 5: Customer Service -->
        <div class="mvp-footer-col">
            <h4>Customer Service</h4>
            <ul>
                <li><a href="<?php echo esc_url( home_url('/my-account/') ); ?>">Login</a></li>
                <li><a href="<?php echo esc_url( home_url('/my-account/') ); ?>">Register</a></li>
                <li><a href="<?php echo esc_url( home_url('/my-account/orders/') ); ?>">Order History</a></li>
                <li><a href="<?php echo esc_url( home_url('/shipping-info/') ); ?>">Shipping Info</a></li>
                <li><a href="<?php echo esc_url( home_url('/faq/') ); ?>">FAQ</a></li>
                <li><a href="<?php echo esc_url( home_url('/trade-account/') ); ?>">Trade Account</a></li>
            </ul>
        </div>

        <!-- Column 6: Our Other Services -->
        <div class="mvp-footer-col">
            <h4>Our Other Services</h4>
            <ul>
                <li><a href="https://vansalesdirect.uk" target="_blank" rel="noopener">vansalesdirect.uk</a></li>
                <li><a href="https://direct-vanhire.co.uk" target="_blank" rel="noopener">direct-vanhire.co.uk</a></li>
                <li><a href="https://rapidfit.co.uk" target="_blank" rel="noopener">rapidfit.co.uk</a></li>
            </ul>
        </div>

    </div>

    <!-- Bottom Bar -->
    <div class="mvp-footer-bottom">
        <div class="mvp-footer-bottom-inner">
            <p class="mvp-footer-copyright">&copy; <?php echo date('Y'); ?> Van Parts Direct Ltd. All rights reserved.</p>
            <div class="mvp-footer-bottom-links">
                <a href="<?php echo esc_url( home_url('/privacy-policy/') ); ?>">Privacy Policy</a>
                <span class="sep">|</span>
                <a href="<?php echo esc_url( home_url('/terms-and-conditions/') ); ?>">Terms &amp; Conditions</a>
            </div>
        </div>
    </div>

</footer>
