# Worldpay eCommerce PHP SDK change log
## [v1.2.2]
* Added support for payment settlement and cancellation.
* Added support for 3DS challenge preference.
* Added new fluent method throwUnlessStatus to check the status of the API response
* Updated Accept header string for compatibility with v7 version of Card Payments API

## [v1.2.1]
* Added support for configuration when a payment is sentForCancellation automatically.

## [v1.2.0]
* Added support for Tokens API.
* Added support for token creation after payment and payment initiation with token.
* Added User-Agent Headers for better traffic analysis and log correlation.
* Allow zero amount in payment request.
* Added fallback for refund keys to maintain compatibility with the gateway.

## [v1.1.1]
* Added Payments Queries API support for payment queries within a date range, page size and received events filters.

## [v1.1.0]
* Added Payments API support for credit card payments via Checkout SDK with 3DS authentication and FraudSight risk assessment.

## [v1.0.5]
* Added validation for merchant narrative field.

## [v1.0.4]
* Added helper validation method for GUIDs.

## [v1.0.3]
* Added API response helpers.

## [v1.0.2]
* Fix generateString returned length.

## [v1.0.1]
* Added length validations for customer phone number, first name, last name, email.
* Added helper for guid generation.
* Updated config provider singleton.
* Removed whitelist of IPS.

## [v1.0.0]
* Initial release.
