=== Worldpay eCommerce for WooCommerce ===
Contributors: Worldpay
Tags: woocommerce, woo, ecommerce, payments, worldpay, hpp, 3DS, card payments, apple pay, google pay, mobile wallets
Version: 1.3.1
Requires at least: 6.4.2
Requires PHP: 7.4
WC requires at least: 8.4.0
WC tested up to: 8.5.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==
Worldpay eCommerce helps enhance your online checkout experience and payments processing, so your customers can easily and safely pay how they want, which may result in fewer abandoned carts, less fraud and more sales.

= Features =
- Hosted Payment Pages
- Embedded Checkout
- Pay with a new card
- Pay with a Mobile Wallet (Apple Pay or Google Pay)
- Webhooks events

== Support ==
For any other issues or further support log into <a href="https://dashboard.worldpay.com/" target="_blank">Worldpay Dashboard</a> and visit our support centre.

== Installation ==
After you have installed and configured the main WooCommerce plugin use the following steps to install the Worldpay eCommerce for WooCommerce:
1. In your WordPress Admin Dashboard, go to Plugins > Add New Plugin and upload plugin
2. Click Install, once installed click Activate
3. Configure and Enable

== Retrieve your credentials ==
1. Log into your <a href="https://dashboard.worldpay.com/" target="_blank">Worldpay Dashboard</a>.
2. Click on "Account & Settings".
3. Click on "Configuration Settings".
4. Click on "API credentials".
5. Switch between "Try mode" and "Live mode" and retrieve your username and password. You need:
- a Try API username
- a Try API password
6. If you're using Payments Onsite (with embedded checkout) you will also need the Checkout Id (please contact support).

== Retrieve your entity ==
1. Log into your <a href="https://dashboard.worldpay.com/" target="_blank">Worldpay Dashboard</a>.
2. Click on "Account & Settings".
3. Click on "Manage Account".
4. Click on "Business Details".
5. Use the second POxxx from the top as your entity.

== Webhooks ==
Receive status updates from Access Worldpay by setting up a webhook <a href="https://developer.worldpay.com/docs/access-worldpay/hpp/webhooks" target="_blank">Worldpay Webhooks</a>.

== Go live ==
1. Log into your dashboard and get your TRY credentials (see steps below). You need:
- a Live API username
- a Live API password
- an entity
- a Checkout Id (optional)
These will be different from any other worldpay credentials you have already.
2. Navigate to the WooCommerce config screen.
3. Copy and paste the credentials from your dashboard to the config screen, this time making sure they are going into the "Live" section.
4. Ensure the "debug" toggle is off.
5. Ensure the new "Live" entity reference is entered.
6. You can now initiate a Live transaction.

== Changelog ==

= 1.3.1 (10/25)
* Fixed embedded checkout 3DS flow.
* Added improved order notes and logs.

= 1.3.0 (10/01/25)
* Added subscriptions for embedded checkout.

= 1.2.1 (08/25/25)
* Fixed embedded checkout fields loading.

= 1.2.0 (07/09/25)
* Added card tokenization support for Onsite & Offsite payment methods.
* Added API requests User Agent header.
* Disabled settlement cancellation on AVS not matched.

= 1.1.2 (06/11/25)
* Added fallback refund keys.

= 1.1.1 (01/29/25)
* Fixed Payments Api partial refund bug by adding currency to request.

= 1.1.0 (01/15/25) =
* Added Payments API support for credit card payments via Checkout SDK with 3DS authentication and FraudSight risk assessment.

= 1.0.4 (07/12/24) =
* Added regex validation for merchant narrative.
* Added WooCommerce installation check.
* Fixed Blocks Checkout bug.
* Fixed coding standards.
* Removed GBP checkout restriction.

= 1.0.3 (06/06/24) =
* Added support for Blocks Checkout.
* Added Description setting.
* Generate new transaction reference and guids for each order.

= 1.0.2 (03/28/24) =
* Added Test Credentials.
* Added support for high-performance order storage.
* Removed webhooks IPs whitelist check.

= 1.0.1 (02/07/24) =
* Fixed merchant settings mb_strlen warning.

= 1.0.0 (01/18/24) =
* Initial release.
