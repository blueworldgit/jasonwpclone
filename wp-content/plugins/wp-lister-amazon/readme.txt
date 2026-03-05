=== WP-Lister Lite for Amazon ===
Contributors: wp-lab
Tags: amazon, woocommerce, integration, products, import, export
Requires at least: 4.2
Tested up to: 6.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

List products from WordPress on Amazon.

== Description ==

WP-Lister for Amazon integrates your WooCommerce product catalog with your inventory on Amazon.

= Features =

* list any number of items
* supports product variations
* supports all official Amazon category feeds as well as custom feed templates
* supports Fulfillment By Amazon (FBA)
* import products from Amazon to WooCommerce
* view buy box price and competitor prices
* includes SKU generator tool

= More information and Pro version =

Visit <https://www.wplab.com/plugins/wp-lister-for-amazon/> to read more about WP-Lister and the Pro version - including documentation, installation instructions and user reviews.

WP-Lister Pro for Amazon will not only help you list items, but synchronize sales and orders across platforms and features an automatic repricing tool.

== Installation ==

1. Install WP-Lister for Amazon either via the WordPress.org plugin repository, or by uploading the files to your server.
2. After activating the plugin, visit the Amazon account settings page and follow our guide on [How to set up WP-Lister for Amazon](https://docs.wplab.com/article/85-first-time-setup).

== Frequently Asked Questions ==

= What are the requirements to run WP-Lister? =

WP-Lister requires a recent version of WordPress (4.2 or newer) and WooCommerce (3.0 or newer) installed. Your server should run on Linux and have PHP 7.0 or better with cURL support.

= Does WP-Lister support windows servers? =

No, and there are no plans on adding support for IIS.

= How can I report security bugs? =

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/wp-lister-for-amazon)

= Are there any more FAQ? =

Yes, there are. Please check out our growing knowledgebase at <https://www.wplab.com/plugins/wp-lister-for-amazon/faq/>

== Changelog ==

= 2.8.7 - 2025-08-20 =
New: Individual listing submissions now display on the Feeds page alongside batch feeds for better visibility
Fix: Handling of product-level generic_keyword and bullet_point properties
Fix: Include the "audience" property to set the validation rules to B2B if enabled on the Advanced Settings page
Fix: XSS prevention improvements in table output
Fix: TypeError: Illegal offset type in isset or empty in ProfileProductTypeConverter.php:1020
Fix: Added batteries_required to the allowed parent columns
Fix: Product-level Product Type must have priority over profile Product Type
Fix: Skip processing of feeds with FATAL processing status
Fix: Image fallback for variations not working
Fix: Properties like variation_theme are sometimes omitted from the feeds
Fix: Status should fall back to the Prepared status on profile update if the listing has no ASIN (unlisted)
Tweak: Allow shortcodes in Amazon B2B Price
Dev: New filter `wpla_json_feed_listing_attributes`

= 2.8.6 - 2025-08-13 =
Fix: Added a fallback to the gzdecode function when zlib is not installed
Fix: Remove the sale price start and end dates when sale price is empty
Fix: Namespace issue when calling WP_Error
Fix: Always exclude is_inventory_available from feeds
Fix: Image properties must be overridable at the product level
Fix: Parent listing column filtering not removing invalid properties
Fix: Use the product's assigned profile to add missing variations if parent listing isn't available
Fix: Handle boolean strings as strings instead of converting them to 1 and 0
Fix: Missing ActionScheduler group name in some calls
Fix: Check for existing listing variations when List on Amazon is executed
Fix: Custom attribute values (tags) are not getting rendered after saving the page
Fix: Removed the deprecated default values from AmazonProfile that's interfering with the saving of custom attributes at the product level
Tweak: Remove temporary mapping CSV file after processing

= 2.8.5 - 2025-08-04 =
Fix: Missing currency value for the list_price property
Fix: Added the compatibility_options property to the reindexArrays() method to fix its indices having gaps
Fix: Custom Size Map table failing to save when the field name contains square brackets
Fix: Cannot access offset of type string on string during profile conversion
Fix: Feed being generated using the old profile when switching over to a new profile
Fix: Do not send Sale Price value and dates when Sale Price is unmapped
Fix: Map legacy Item Condition values to use the new condition strings
Fix: Legacy attribute form displaying for products using an already converted profile
Fix: Edge case allows both legacy and JSON feeds to be generated at the same time for the same listing
Fix: Remove listings from queue that fails the canSubmitListing() check
Fix: Use the default schema currency if it's not set in the profile
Fix: Handling Time must be removed for FBA listings
Fix: Convert date format from YYYY/MM/DD to YYYY-MM-DD

= 2.8.4 - 2025-07-29 =
New: Support for multi-value attribute shortcodes using bracket notation ([attribute_color][0], [attribute_color][1])
Fix: Variations showing up individually on the Profile Converter instead of grouped
Fix: Include sale price fields in PnQ JSON feeds
Fix: Skip parent variable products for PnQ and Inventory Loader JSON feeds
Fix: Product Type attributes showing on Edit Product page when profile uses Feed Template
Fix: Enhanced filterEmptyFields() method to remove unnecessary elements in JSON feed
Fix: Convert value for California Proposition 65 field properly
Fix: Mark online listings as failed if ERROR severity issues are found
Fix: applyProfileToItem() setting unpublished items to changed status incorrectly
Fix: Added default profile shortcodes for product dimensions
Fix: Convert/Map attributes before replacing dashes with slashes
Fix: Remove listings from publishing queue if status is "already published"
Fix: Listings table not displaying profile-level quantities for stock-disabled products
Fix: Added compatible_with_vehicle_type to array data reindex list
Fix: Profile dates overriding empty discounted_price validation
Tweak: Added "— none —" option to boolean form fields
Dev: New hooks wpla_tools_debug and wpla_execute_tools_{$action}
Dev: canSubmitListing() validation now returns error for user visibility
Dev: Better UI for viewing JSON payload on Logs page
Dev: HTTP 400 errors in putListingsItem calls now show real error message

= 2.8.3.1 - 2025-07-21 =
Fix: SP-API validation error when sale price equals regular price in JSON feeds
Fix: Profile duplication not copying pricing options (price adjustments, percentage modifications)
Fix: Sale price handling when "Use Sale Price" option is globally disabled
Fix: Sorting by stock quantity failing for variable product listings

= 2.8.3 - 2025-07-18 =
New: Added Re-convert action for profiles to re-run the conversion process and fix any missed mappings from previous versions
New: Lock All and Unlock All added to the Tools page
New: Added a check and a download tool for the CSV Map File that is required to convert feed templates to product types
Fix: SKU Not Found handler for the Check for Listing Errors action
Fix: getListingProductType must prioritize the product-level Product Type
Fix: Sale Price dates not getting included in the feeds
Fix: Variation attributes not getting fetched correctly
Fix: $parent_var_columns whitelist for variables not getting executed
Fix: Implemented caching to prevent memory errors during the mapping of Product Types properties
Fix: DELETE feeds changing listing status back to SUBMITTED
Fix: Shortcodes not getting replaced
Fix: Handling of nested composites in items (e.g. Color Map)
Fix: Matched listings not getting the condition value causing them to fail
Fix: Country of Origin not mapping correctly
Fix: Converted profiles still showing on the list of available to convert
Fix: New Merchant Shipping Groups values not getting found
Fix: Map the old Merchant Shipping Group value to the new dropdown ID
Fix: Fallback to using the ASIN from the listings table if the meta _wpla_asin is empty
Fix: _wpla_asin sync when inserting and matching listings
Fix: Encoding issue in the JSON feeds causing values to get cut off
Tweak: Only fail matched and submitted listings on Listings Check
Tweak: Unhide variation-related properties

= 2.8.2 - 2025-07-07 =
New: Replace CSV delete feeds with JSON delete feed
Fix: Check the result of initAPI to prevent fatal errors
Fix: searchCatalogItems not returning results when matching
Fix: Error `call to a member function getSchema() on null`
Fix: Arrays in the JSON feed (eg `bullet_point`) having gaps in the indices causing feed errors
Fix: Manually mapping some properties that are not included in the CSM Map by Amazon
Fix: External Product ID not getting mapped when converting profiles
Fix: Prevent double-clicking when checking orders which could cause duplicate WC orders
Fix: Searching on the Feeds page
Fix: Escape values before adding them to the JSON feed
Fix: Undefined array key "custom_tracking_link"
Fix: Inconsistent Last Order Updated value
Fix: Sale prices missing from InventoryLoader feeds
Tweak: The `Check for Listing Errors` bulk action must also mark listings as online if no issues are found

= 2.8.1 - 2025-06-26 =
New: Background processing for checking the status of submitted listings
New: Throttling control for getItemOffers calls to prevent API rate limiting
Fix: Set the request type to Offer if putListingsItem is called with the PRODUCT product type
Fix: Product-level SP-API attributes not getting saved properly
Fix: Handling of arrays and strings in JSON feed data processing
Fix: Not being able to set a value to 0 in the JsonFeedDataBuilder class
Fix: Optimized the feeds page by excluding the data column from being fetched from the database
Fix: Uncaught RuntimeException during initAPI() initialization
Fix: Matching by EAN not finding matches when searching for products
Fix: Added exception handler to the saving of a ProductType object
Fix: UTF8-encode values before adding them to the JSON feed
Dev: Added amazon_product_types to the required tables to check for during runtime
Dev: Cleared console.log statements from debug code

= 2.8 - 2025-06-19 =
New: Implementation of the Product Types API to replace the Flat File Feed templates
New: Added a Template Converter tool that converts and maps the fields used by Feed Templates to match their Product Types counterpart
New: putListingsItem to publish new listings to Amazon
Fix: Changed calls triggered by the plugins_loaded to the init event to prevent getting the _load_textdomain_just_in_time warning
Fix: Added the missing South Africa Marketplace ID
Fix: Error when fetching the buyer's name and email during the order import process
Fix: Use GuzzleHttp to download feed and report documents
Fix: Max Feed Size setting not getting honored
Fix: Error when trying to match listings
Fix: Various code warnings
Fix: Pass the Merchant ID to fetch the Merchant Shipping Templates available to the seller
Fix: Failed API calls in GetOrders due to a network issue are not always getting retried
Fix: Only assign currency to the order if there is an OrderTotal property to prevent fatal errors
Fix: Warnings in the Edit Order page about accessing the ID directly
Fix: Error thrown when initAPI() is called without an account
Dev: Sandbox account flag not rendering properly
Dev: New filter `wpla_order_builder_skip_zero_priced_items`
Dev: Grouped ActionScheduler jobs
Dev: Replaced fgetcsv for better performance
Dev: Added back the missing non-prefixed SP-API classes required by the OrderBuilder class
Dev: Removed unnecessary files

= 2.7.6 - 2025-04-01 =
Fix: Added the Marketplace ID for IE
Fix: Replaced the deprecated utf8_decode() function
Fix: Error when running the UTF8 table conversion tool
Fix: Calling wc_update_product_stock() twice using the same stock quantity
Fix: Check for errors from the WPLA_Amazon_SP_API::getReport() call

= 2.7.5.1 - 2025-03-21 =
Hotfix: Fatal error when creating the WC Order due to the Aelia Currency Switcher integration

= 2.7.5 - 2025-03-21 =
New: Added a setting to deduct shipping tax from the shipping total
New: Added the ability to search Stock Logs using ASIN
New: Stored IsPrime and IsBusinessOrder in the order meta table
New: Added support for Amazon Ireland (Beta)
Tweak: Displayed the country name in the dropdown for Tax Rates on the Settings page
Fix: Recorded shipping promotions as order discounts
Fix: Changed the amazon_feeds results column type to longtext
Fix: Fixed an issue where the Aelia Currency Switcher plugin did not detect the currency used on Amazon
Fix: Ensured FBA shipment tracking is recorded in the WC Shipment Tracking format
Fix: substr_count(): Argument #1 ($haystack) must be of type string, stdClass given
Fix: Prevented the creation of a dynamic property WPLA_JobsModel::$tablename  

= 2.7.4 - 2025-02-27 =
Fix: `Creation of dynamic property` warnings
Fix: Warning `Attempt to read property "tax_rate" on null`
Fix: Feed submission results not getting stored in all orders
Fix: Use the core wc_update_product_stock() function when decreasing stocks from orders
Fix: Save the WC_Order after marking order_stock_reduced to TRUE
Fix: Updated the method of detection of the MSRP plugin
Fix: Order item discount causing order total error
Fix: Error ValueError: str_getcsv(): Argument #3 ($enclosure) must be a single character
Fix: count(): Argument #1 ($value) must be of type Countable|array, null given
Dev: Removed deprecated function utf8_encode()

= 2.7.3 - 2024-12-02 =
Fix: Error in the Repricing page when GetItemOffers returns an invalid response
Fix: Missing API class in the Repricing page
Fix: Improved support for the beta product feed templates
Fix: Warning `undefined variable $success`
Fix: Check for throttled getOrderItems calls
Fix: Promotional discount is included in the line item total when Tax Mode is set to Import from Amazon
Fix: Initialize the WC_Cart and WC_Session classes when FBA Shipping Methods is enabled

= 2.7.2 - 2024-10-29 =
Fix: Skip loading JS assets on the Elementor design page
Fix: Error `unsupported operand types` in the Repricer
Fix: Get the correct shipping tax class for orders
Fix: Fatal error thrown when Amazon returns an empty ReponsibleParty value in GetOrder calls
Fix: Background inventory check with inconsistent counts
Dev: Added back missing SP-API classes for backwards compatibility

= 2.7.1 - 2024-10-12 =
Fix: Match Product window not loading due to an error
Tweak: Retry a listing update in WPL_AmazonFeed::processListingDataResults() in case a deadlock error occurs

= 2.7.0 - 2024-10-05 =
New: Ability to match products using EAN
Tweak: Improved the handling of throttled calls
Fix: Skip reverting stocks on cancelled Amazon orders if the WC order's status is already refunded or cancelled
Fix: Show line discounts in WC orders
Fix: Skip completing orders again during HPOS sync
Fix: Error `call to a member function getOrders on null`
Fix: Fatal error when throwing an Exception
Fix: When bulk updating orders, download line items if they do not exist
Fix: Check getOrderItems response to prevent getting a fatal error
Fix: Error during implode() call
Fix: Typo in Up Price setting
Fix: Background Inventory Check frequency setting not getting used properly
Fix: GetOrders parameters order fix
Fix: Namespace errors preventing orders from being imported
Fix: Warnings when checking if product update came from WP All Import
Fix: Undefined property stdClass::$success warning
Fix: Load the correct type from the FeedType class
Fix: Allow 0 value from profile item specifics
Fix: Call to getAttributes() on null
Fix: Fatal error when calling getCompetitivePricing
Fix: HPOS compatibility for submitting FBA orders automatically
Dev: New action wpla_out_of_sync_products_found
Dev: Update Amazon libraries and prefixed/namespaced classes
Dev: Deprecated code warnings
Dev: Deprecated utf8_decode()

= 2.6.17 - 2024-05-30 =
Fix: More compatibility issue with WC_Order::get_stock_reduced()
Fix: Order fulfillment feed status update to prevent missed order updates
Dev: Security fix

= 2.7.0-beta1 - 2024-07-01 =
* New: Support for Product Matching using EAN
* Fix: Warning when checking if product update came from WP All Import
* Fix: More deprecated code warnings
* Dev: Updated all package libraries
* Dev: Prefixed all classes with WPLab_Amazon

= 2.6.16 - 2024-05-23 =
* Hotfix: Feeds stuck in the Pending status

= 2.6.15 - 2024-05-23 =
* Fix: Deprecated code warnings
* Fix: Update pending feeds to add affected products after a WC Import run
* Fix: Backwards compatibility with WC_Order::get_data_store()->get_stock_reduced()
* Fix: Mark feeds as processing to prevent them from being updated during submission
* Fix: Error in the WPLA_InventoryCheck when an invalid data type is passed to round()

= 2.6.14 - 2024-05-01 =
* New: Added support for the GET_ORDER_REPORT_DATA_SHIPPING report
* Fix: Warning trying to decode item details array
* Fix: Error when the weight value from WooCommerce is not an int or float
* Dev: View to list the fulfillment order items table
* Dev: New filter wpla_listing_get_item_quantity

= 2.6.13 - 2024-04-17 =
* Fix: Error in the Feeds page when trying to read from an invalid feed
* Fix: Warnings trying to access listing data
* Fix: Deprecated str_replace() warning in the FeedDataBuilder class
* Fix: Use local WC_Customer class object when getting tax rates
* Fix: Logs not clearing automatically
* Tweak: Rearranged the settings page so FBA Stock Sync is above the Fallback to Seller Fulfilled setting
* Dev: Warning when installing templates without a template_name element
* Dev: Cleared more deprecated warnings

= 2.6.12 - 2024-03-22 =
* Fix: SQL error in the Edit Product page
* Fix: Warning when checking if an order is FBA-fulfillable
* Fix: Backward-compatibility with set_order_stock_reduced and get_order_stock_reduced
* Fix: Product-level bullet points not getting overwritten by the profile value
* Fix: Inherit the parent's profile when matching new variations
* Dev: Announcements adjustments
* Dev: Removed deprecated code that's causing notices
* Dev: Performance enhancement in the Feeds table
* Dev: Security fixes
* Dev: Added missing country marketplaces in the Endpoints.php file

= 2.6.11 - 2024-02-28 =
* New: Added support for the Order Attribution Tracking in WooCommerce
* Fix: Missing flags of the newly added marketplaces
* Fix: Variable ASINs not getting pulled after getting listed
* Tweak: Make announcements hideable

= 2.6.10 - 2024-02-13 =
* New: Added a setting to override the Prices Include Taxes in WooCommerce
* Fix: Category Template installer not recognizing valid values for the IT marketplace
* Fix: Check for the required tables using the information_schema table
* Fix: Added context to the processShortcodeInContent() method to prevent attributes from being run through wpautop()
* Fix: Uninstall routine does not clear WPLA data from the postmeta table

= 2.6.9 - 2024-01-25 =
* Fix: Escape input data from URL
* Fix: Allow the installation of Feed Templates for the newly added marketplaces
* Fix: When uploading invoices to Amazon, send the order number if the invoice number is not set

= 2.6.8 - 2024-01-17 =
* Hotfix - Feed value for standard_price and sale_price not getting set properly

= 2.6.7 - 2024-01-16 =
* New: Added the ability to parse the newer listing templates from Amazon
* Fix: Javascript issue preventing the Change Profile bulk action tool from working
* Fix: Convert price value to use a decimal point character if coming from a profile field attribute

= 2.6.6 - 2024-01-10 =
* New: Added new beta support for these marketplaces: Belgium, Egypt, Mexico, Poland, Singapore, South Africa, Sweden, Turkey, Polan
* Fix: Added the missing MY_VOEC reseller category
* Fix: List on Amazon and Change Profile window not working
* Dev: Display local time in the log files

= 2.6.5 - 2024-01-02 =
* Fix: List on Amazon not working when WC Block Editor is enabled
* Fix: Link to download personalization archive not showing up
* Fix: Lowest offer details window not showing Lowest Offer Data
* Dev: The wpla_mcf_enabled_order_statuses filter must have the same values in both places where it is used
* Dev: Log files not getting created
* Dev: Display local time in the order details window

= 2.6.4 - 2023-12-06 =
* Tweak: Remove the deduction of the promotional discount from the subtotal to display discount on the edit order page
* Fix: Invalid argument passed to wpla_delayed_amazon_order_completion
* Fix: Error accessing get_meta() on null
* Fix: Uninitialized cart object when accessing from the REST API
* Fix: Run checks on the GetOrders response to prevent fatal errors
* Fix: Add the non-prefixed order status when checking for an order's ability to be submitted to FBA

= 2.6.3 - 2023-11-16 =
* Tweak: Removed the Supported Marketplace text for B2B Price support
* Fix: Show Problems button in the Feeds page not working
* Fix: Unclosed tag causing display issues in the product pages
* Fix: Do not override variation images with custom gallery images
* Fix: Missing plugin update notification on some sites
* Fix: License form disabled with empty license details
* Dev: New filter: wpla_mark_item_as_modified_data
* Dev: New filter: wpla_delayed_amazon_order_completion

= 2.6.2 - 2023-10-19 =
* Fix: Orders table not showing WPLA columns and meta boxes

= 2.6.1 - 2023-10-18 =
* New: Added views for Locked and Unlocked listings
* Fix: Unable to parse FBA Manage Inventory reports due to the missing seller-sku column
* Fix: Make sure the out-of-stock threshold is of int data type
* Fix: Gather shipment rates from all active accounts
* Fix: Store the date_paid and ship_dates using the local time
* Fix: Skip variables (parents) when looking for listings with missing ASINs
* Fix: Missing dompdf autoloader
* Dev: Rotate logs on a daily basis
* Dev: Added back the AWS data directory

= 2.6.0 - 2023-09-28 =
* New: Support for WooCommerce's High Performance Order Storage feature
* New: Enhanced searching performance in the Listings table
* New: Added the ability to lock listings to only update their stock quantity and price (Beta)
* New: Added the option of using a dedicated DB table when creating fulfillment feeds (Beta)
* Tweak: Split products list into several rows when running inventory checks
* Fix: Generating empty order fulfillment feeds
* Fix: Warnings in the Feeds and Edit Product pages
* Fix: Error when the filter woocommerce_get_product_from_item is triggered
* Fix: Prevent query string from being URL-decoded
* Fix: Variables are left without ASINs after they are published to Amazon
* Fix: Matching using SKU does not return any results
* Fix: Using the Parent option for the Variation Title setting should also pull the Listing Title when available
* Fix: Added support for the item-condition column for InventoryLoader feeds
* Fix: Size mapping case sensitivity issue
* Dev: Added the filter wpla_order_builder_update_skip_statuses
* Dev: Added the action wpla_order_builder_before_create_order
* Dev: Added the action wpla_item_updated_from_report
* Dev: Update the Dompdf library to address a vulnerability
* Dev: Added the ability to limit the results when generating pending feeds to prevent timeouts
* Dev: Removed the /data directory from the AWS library

= 2.5.5 - 2023-07-10 =
* New: Added the shipping methods ExpeditedGlobalParcel and ExpeditedLocalParcel to the GLS carrier
* New: Added the shipping carrier DX Freight
* Tweak: Increase the timeout value when trying to activate the plugin license
* Fix: Personalization data from orders not getting recorded
* Fix: Product-level _amazon_price value must have priority over profile value
* Fix: Added the missing Reseller Category value JE_VOEC
* Dev: Handled errors in the Accounts page
* Dev: Removed MWS fields from the Accounts page
* Dev: Added the filter wpla_force_amazon_collected_taxes

= 2.5.4 – 2023-05-17 =
* New: Added the option of saving the Amazon Order ID as both a meta value and an order note
* Tweak: Readability of the status text for tools that run in batches (Step X / Y items processed)
* Dev: Handle errors from the getReports call
* Fixed: Warnings from the WPLA_InventoryCheck class
* Fixed: Warnings in the Listings page
* Fixed: Added a Primary Key to the amazon_shipping table
* Fixed: Empty quantity value in the Repricing table
* Fixed: Importer attempting to create variable products from simple listings

= 2.5.3 – 2023-04-27 =
* New: Added the ability to search the Stocks Logs using the Amazon Product ID
* New: Added a setting to skip adding line item SKUs to created WooCommerce orders
* Tweak: Enhanced the search results for the Listings and Repricing tables
* Tweak: Show the total stocks and FBA stocks in the Repricing table
* Fixed: Some custom feed templates not showing dropdown options for valid values
* Fixed: Undefined variable [wpl_repricing_pricing_options] error in WP-Lister for Amazon Lite
* Fixed: Revert stocks of cancelled orders from the Unshipped order status
* Dev: New filter added: wpla_orderbuilder_cleanup_session
* Dev: Better error handling when calling getCatalogItem()
* Dev: Removed the file gen_stub.php from the AWS library (false-positive malware report)

= 2.5.2 – 2023-03-09 =
* Tweak: Use basedir in storing log files
* Fixed: Amazon customer data not getting cleared from session after importing orders
* Fixed: Use the correct UserAgent in the API calls
* Fixed: Added the missing KZ_VOEC property
* Fixed: Repricer not setting to the max possible price

= 2.5.1 – 2023-02-06 =
* New: Longer data retention options for Amazon orders
* New: Import the listing description from the Amazon catalog
* New: Added a setting to periodically pull Order Reports to be able to import Business Tax IDs for business orders
* Tweak: Enhanced the frontend checkout by sending the feed updates to a background task
* Tweak: Lowered the number of ASINs when querying for price updates to prevent getting throttled
* Tweak: WP capability check - changed edit_others_pages to edit_others_shop_orders and edit_orders_products
* Tweak: When generating a new feed, exclude all invalid listings (missing SKUs, etc) so they do not get included in the CSV file
* Tweak: Update price check date on ASINs with error to prevent from staying at the front of the queue
* Fixed: $SubmittedDate undefined notice
* Fixed: Added the missing SG_VOEC property
* Fixed: Error when trying to count attributes from an imported listing
* Fixed: Handle throttled responses for GetOrderAddress and GetOrderBuyInfo calls
* Fixed: Warning "foreach() argument must be of type array" when importing some orders
* Fixed: Fatal error when the parent ASIN could not be loaded
* Fixed: Dompdf library clash when the Invoice Upload setting is enabled
* Dev: Log errors from getCompetitivePricing

= 2.5 – 2023-01-02 =
* New: Now you can request and download the Pending Order Report from within WP-Lister
* Tweak: Delay consecutive calls to the getCompetitivePricing API to avoid triggering the rate limit
* Fixed: WP-Lister will now get notified of changes made via the Admin Columns Pro plugin
* Fixed: PHP Error: Call to undefined method getFeedId()
* Fixed: Invoice upload feed should always be sent as UTF-8
* Fixed: Library clash for FontLib\Autoloader and Dompdf\Autoloader
* Dev: Added a custom namespace to the Dompdf library to prevent collisions
* Dev: More verbose debug log allows for more precise bug squashing when processing shipping fees
* Dev: Allow plugins to control whether feed content should be utf8_decode'd using the new wpla_utf8_decode_feed_content filter
* Dev: Fully compatible with WooCommerce 7.2

= 2.4.4 – 2022-12-08 =
* Security: Fixed a Reflected Cross-Site Scripting (XSS) vulnerability reported by Animesh Gaurav
* Tweak: Make the list of prepared listing issues expandable to save screen space
* Fixed: Invoice uploaded to amazon could show as blank PDF due to invalid characters
* Fixed: Issue where "There are no shipping options available" was shown incorrectly
* Fixed: Shipping method ID not being accessed correctly
* Fixed: Invalid argument for foreach in ListingsModel.php
* Fixed: Call to undefined method getFeedId() in AmazonFeed.php
* Fixed: Check for an existing Dompdf class before including the autoloader (3rd party plugin compatibility)
* Dev: Force download of log files instead of directly linking to them

= 2.4.3 – 2022-11-24 =
* Tweak: Hide parent container items when filtering listings to only show items without ASINs
* Fixed: Repricing did not reset to max price if seller has buy box and no competition
* Fixed: Improved downloading gallery thumbnails when importing items from Amazon
* Dev: Added definition to be able to access the Contributors property when fetching catalog items
* Dev: Added parameter 'product_node' to the wpla_filter_imported_product_data filter

= 2.4.2 – 2022-11-08 =
* Tweak: Removed old report types which are no longer supported by Amazon's new SP-API
* Tweak: Regularly clean the amazon_jobs table
* Fixed: Uploading Invoices to the SP-API
* Fixed: Import Book attributes from the API
* Fixed: Use the ReportDocument::getCompressionAlgorithm() method to determine if the report body needs to be decoded
* Fixed: Call to a member function get_regular_price() on bool when passed an invalid/nonexistent product
* Fixed: Attempt to read property "account_id" on array
* Fixed: Error when trying to access null as object
* Fixed: Call to function getAttributes() on bool
* Fixed: GuzzleHttp conflict with other plugins
* Dev: Added the method getEligibleShipmentServices()

= 2.4.1 – 2022-10-24 =
* New: Importing listings is now using the Catalog API (SP-API)
* Tweak: Removed the option to import using the old Listings API
* Fixed: Various minor issues related to the new SP-API 
* Fixed: Database error over large attribute sets

= 2.4.0 – 2022-10-18 =
* New: Use the new SP-API for feeds and catalog requests, including repricing and importing tasks (beta)

= 2.3.0 – 2022-10-13 =
* New: Use the new Catalog API to pull images and bullet points when importing listings
* New: Added shipping methods for the Australia Post-ArticleID carrier
* Tweak: Improved performance in SQL query when building large feeds
* Tweak: Display "Sync sales is disabled" in the order history log for products where syncing has been disabled
* Fixed: Removed redundant code causing a "Undefined variable awsToken" notice
* Fixed: Added the missing constant AU_VOEC in the SP-API library
* Fixed: Undefined offset warnings when saving variable products
* Dev: Added the filter wpla_run_plugin_update_check to override the frequency of update checks from the license server
* Dev: Added filter wpla_attribute_shortcode_value

= 2.2.9 – 2022-09-17 =
* New: Added the CTTExpress shipping provider
* Tweak: Improved Product Bundles support: Update bundle quantity when child components are updated
* Fixed: Issue where requesting the FBA Shipment report would fail
* Fixed: Plugin conflict with Advanced Shipping Tracking Pro
* Fixed: Avoid double-encoding data from the Merchant Listings report
* Fixed: Invalid value 'NZ_VOEC' for 'deemed_reseller_category' error
* Fixed: Use WC_Product_Bundle::get_bundle_stock_quantity() to get stock quantity of bundled products
* Dev: Added filter wpla_shipment_tracking_instance

= 2.2.8 – 2022-08-30 =
* Fixed: Record the correct Gift Wrap amount from Amazon
* Fixed: Undefined function get_stock_managed_by_id() (since 2.2.7)
* Fixed: Undefined variable wpl_is_reg_brand
* Fixed: Warnings on the Advanced Settings page

= 2.2.7 – 2022-08-26 =
* Tweak: Exclude product meta when cloning products
* Fixed: Issue where scheduled reports not getting requested
* Fixed: Amazon Business flag was not shown on the Orders page
* Fixed: Improved support for variations with parent-level stock management (from Yatin @ multidots) 

= 2.2.6 – 2022-08-09 =
* Fixed: Download customized order item data
* Fixed: Reports not being downloaded and displayed properly
* Fixed: Create Customers action failing due to missing order email address
* Fixed: Skip automatically generating feeds when running imports
* Fixed: Support for Price Based on Country plugin
* Fixed: Automatically process supported reports
* Fixed: Still show the search form when trying to match products and the initial search returned no results
* Dev: Removed unnecessary use of GuzzleHttp\json_encode()

= 2.2.5 – 2022-08-02 =
* Fixed: Missing buyer details from orders
* Fixed: Compatibility with PHP 7.4 restored
* Fixed: Improved compatibility with Amazon's new SP-API
* Fixed: Matching a WooCommerce product to Amazon would return no results
* Tweak: Do not fetch address and buyer info on pending and cancelled orders
* Tweak: Improved handling of throttling when importing orders and order addresses
* Dev: Result from GetOrders call was too large to be stored in log

= 2.2.4 – 2022-08-01 =
* New: WP-Lister now uses Amazon's new SP-API to fetch orders and request reports
* Tweak: Updated the Accounts pages to allow users to login and fetch token from existing accounts
* Fixed: Fix French character encoding when importing from Amazon
* Fixed: Feed Error Emails still sending despite the setting being off
* Fixed: Include the SKU and error message in the feed submission error email
* Fixed: Prevent fatal errors if the data being accessed was not provided by Amazon
* Dev: Compatible with WooCommerce 6.7
* Dev: Added the ability to toggle calls between sandbox and production APIs
* Dev: Added wpla_update_feeds action to force WPLA to generate feeds for changed listings

= 2.2.3 – 2022-07-11 =
* New: Added option to send an email to site admin in case of any feed submission error
* Tweak: Apply the wpla_orderbuilder_prices_include_tax filter every time the woocommerce_prices_include_tax option is pulled
* Fixed: Check All button on the Import page was not working
* Fixed: Variation main image fallback must first be set prior to attempting to load from the product gallery
* Fixed: Undefined array key "leadtime-to-ship" warning
* Fixed: Uncaught TypeError: round(): Argument #1 must be of type int|float, string given
* Fixed: Warning Undefined index: listing_title
* Dev: Added filter wpla_override_variation_attribute_with_profile to allow WPLA to use variation attribute value from the profile
* Dev: Use WC_Product methods to get and set product price in Woo_ProductWrapper.php
* Dev: Lock table for reading when using the wpla_lock_feeds_table filter

= 2.2.2 – 2022-05-26 =
* Tweak: Adjusted the shipping methods for the GLS courier
* Tweak: Store the Amazon account title with each order when creating orders in WooCommerce
* Tweak: Increased the timeout limit when fetching converted template from conversion server
* Fixed: Error message "Trying to access offset of null in WPLA_AjaxHandler.php"
* Fixed: Fallback to accessing post data from $_REQUEST for sites that cannot read from php://input
* Fixed: Possible issue processing special characters (introduced in 2.2.1)
* Dev: Include Amazon account name and Merchant ID in the WC REST API response for GetOrder calls
* Dev: Store the IOSS in the order metadata

= 2.2.1 – 2022-05-09 =
* New: Added invoice uploading for the WooCommerce Print Invoices plugin by SkyVerge
* Tweak: Convert merchant-shipping-group field value to UTF-8 prior to importing
* Tweak: Handle new AFN quantity fields (afn-fulfillable-quantity-local and afn-fulfillable-quantity-remote)
* Fixed: PDF Invoice missing images and fonts
* Fixed: French characters not displaying correctly
* Fixed: Rare issue where a product's stock level could be set to 0 when updating the product in WooCommerce
* Fixed: Removed shirt_size fields from the parent_var_columns array
* Dev: Added logging for checking FBA autosubmit orders
* Dev: Added the filter wpla_update_woo_stock_skip_status_array
* Dev: Added filter wpla_filter_skip_listing_feed_item to skip certain listings from being added to feeds

= 2.2.0 – 2022-04-06 =
* New: Added "Couriers Please" to the list of available shipping providers
* New: Retry failed requests due to temporary network issues automatically (HTTP code 502)
* New: Added more parent variation columns (age_range_description, fabric_type, shirt_size, shirt_size_class, shirt_size_system)
* Tweak: Delay creating orders without order items to circumvent temporary network issues (502 bad gateway)
* Tweak: Check ignore_orders_before against PurchaseDate instead of LastUpdateDate
* Tweak: Convert listing titles to UTF8 before importing
* Fixed: Skip listing parent variables if variations mode is flat
* Fixed: When Background Inventory Check is off, also unschedule the  wpla_bg_inventory_check_run jobs
* Fixed: Prevent creating orders without any items
* Fixed: Undefined property stdClass::ShippingTax
* Fixed: Selected value in the Feed Attributes list showing up as Custom Values
* Dev: Added the function wpla_is_json()
* Dev: Added logging to the handle_woocommerce_order_status_update_completed checks
* Dev: Added the filters wpla_import_update_product_price and wpla_import_update_amazon_price
* Dev: Increased cURL timeout limit from 15 to 30 seconds
* Dev: Store the Earliest and Latest Ship Dates in the order postmeta
* Dev: Commented out the code block with ATUM plugin support which now is causing a fatal error
* Dev: Passed post_id parameter is in JSON format when using the REST API. Decode and extract the correct post_id if necessary
* Dev: Check for a valid order_id before adding a history entry that the order was created
* Dev: Compatible with WooCommerce 6.3.1

= 2.1.0 – 2022-02-18 =
* New: Added support for custom templates with multi-marketplace fields
* New: Added AU shipping carriers Australia Post and StarTrack
* Tweak: Skip checking for stock sync issue if the quantity property is empty to avoid checking against FBA listings
* Tweak: Include the time when filtering the stocks log by date 
* Fixed: Convert units to a dot decimal character for the length, width and height properties
* Fixed: Check for the ups_shipment_ids index to prevent getting an Undefined Index warning
* Fixed: Load thickbox library on the Products table to make sure product matching works
* Fixed: Load thickbox library on the Import page
* Dev: Added $order to the wpla_shipping_service_id_map and wpla_shipping_service_title_map filters
* Dev: Compatible with WooCommerce 6.2

= 2.0.8 – 2022-01-28 =
* New: Added DE colors to the color map list and switch to using text input with autocomplete because Amazon now requires localised color names
* Fixed: Improved script loading to make sure plugin scripts are only loaded within WP-Lister pages
* Fixed: Assigning the main_image_url for each variation was using the primary image of the parent product instead
* Dev: Added filter wpla_duplicate_product_excluded_meta to modify or remove the excluded product meta when duplicating WooCommerce products


View the full changelog at https://www.wplab.com/plugins/wp-lister-for-amazon/changelog/