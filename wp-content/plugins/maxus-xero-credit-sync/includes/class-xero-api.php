<?php
/**
 * Xero API wrapper — reuses WooCommerce Xero's OAuth2 connection and SDK.
 */

defined( 'ABSPATH' ) || exit;

class MXCS_Xero_Api {

    /**
     * Check whether the Xero API connection is ready.
     *
     * @return bool
     */
    public function is_connected() {
        if ( ! class_exists( 'WC_XR_OAuth20' ) ) {
            return false;
        }
        return WC_XR_OAuth20::can_use_oauth20() && WC_XR_OAuth20::is_api_ready();
    }

    /**
     * Get an AccountingApi instance configured with current OAuth tokens.
     *
     * @return \Automattic\WooCommerce\Xero\Vendor\XeroAPI\XeroPHP\Api\AccountingApi
     * @throws \Exception If connection is not ready.
     */
    private function get_accounting_api() {
        if ( ! $this->is_connected() ) {
            throw new \Exception( 'Xero API connection is not ready.' );
        }

        // Load the WC Xero vendor autoloader.
        $autoloader = WP_PLUGIN_DIR . '/woocommerce-xero/lib/packages/autoload.php';
        if ( file_exists( $autoloader ) ) {
            require_once $autoloader;
        }

        $config_class = 'Automattic\\WooCommerce\\Xero\\Vendor\\XeroAPI\\XeroPHP\\Configuration';
        $client_class = 'Automattic\\WooCommerce\\Xero\\Vendor\\GuzzleHttp\\Client';
        $api_class    = 'Automattic\\WooCommerce\\Xero\\Vendor\\XeroAPI\\XeroPHP\\Api\\AccountingApi';

        $config = $config_class::getDefaultConfiguration()
            ->setAccessToken( (string) WC_XR_OAuth20::get_access_token() );
        $config->setHost( 'https://api.xero.com/api.xro/2.0' );

        return new $api_class( new $client_class(), $config );
    }

    /**
     * Get Xero tenant ID.
     *
     * @return string
     */
    private function get_tenant_id() {
        return (string) WC_XR_OAuth20::get_xero_tenant_id();
    }

    /**
     * Get total outstanding (unpaid) balance for a contact by email.
     *
     * Queries AUTHORISED invoices (approved but unpaid) and sums AmountDue.
     *
     * @param string $email Customer email address.
     * @return float|false Total outstanding amount, or false on failure.
     */
    public function get_outstanding_balance( $email ) {
        try {
            $api       = $this->get_accounting_api();
            $tenant_id = $this->get_tenant_id();

            // Build the where filter for outstanding invoices by contact email.
            $where = 'Contact.EmailAddress=="' . $email . '" AND Type=="ACCREC"';

            // Use the statuses parameter for faster filtering (per Xero docs).
            $statuses = array( 'AUTHORISED' );

            $response = $api->getInvoices(
                $tenant_id,       // xero_tenant_id
                null,             // if_modified_since
                $where,           // where
                null,             // order
                null,             // ids
                null,             // invoice_numbers
                null,             // contact_ids
                $statuses,        // statuses
                null,             // page
                null,             // include_archived
                null,             // created_by_my_app
                null,             // unitdp
                true              // summary_only
            );

            $invoices = $response->getInvoices();

            if ( empty( $invoices ) ) {
                return 0.0;
            }

            $total = 0.0;
            foreach ( $invoices as $invoice ) {
                $amount_due = $invoice->getAmountDue();
                if ( is_numeric( $amount_due ) ) {
                    $total += (float) $amount_due;
                }
            }

            return round( $total, 2 );

        } catch ( \Exception $e ) {
            self::log( 'API error for ' . $email . ': ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Fetch a single invoice by ID and return the contact's email address.
     *
     * @param string $invoice_id Xero invoice ID (GUID).
     * @return string|false Contact email, or false on failure.
     */
    public function get_invoice_contact_email( $invoice_id ) {
        try {
            $api       = $this->get_accounting_api();
            $tenant_id = $this->get_tenant_id();

            $response = $api->getInvoices(
                $tenant_id,
                null,                       // if_modified_since
                null,                       // where
                null,                       // order
                array( $invoice_id ),       // ids
                null,                       // invoice_numbers
                null,                       // contact_ids
                null,                       // statuses
                null,                       // page
                null,                       // include_archived
                null,                       // created_by_my_app
                null,                       // unitdp
                true                        // summary_only
            );

            $invoices = $response->getInvoices();

            if ( ! empty( $invoices ) ) {
                $contact = $invoices[0]->getContact();
                if ( $contact ) {
                    return $contact->getEmailAddress();
                }
            }
        } catch ( \Exception $e ) {
            self::log( 'Failed to fetch invoice ' . $invoice_id . ': ' . $e->getMessage() );
        }

        return false;
    }

    /**
     * Verify a Xero webhook signature.
     *
     * @param string $payload  Raw request body.
     * @param string $signature The X-Xero-Signature header value.
     * @return bool
     */
    public function verify_webhook_signature( $payload, $signature ) {
        $key = get_option( 'mxcs_webhook_key', '' );

        if ( empty( $key ) ) {
            return false;
        }

        $expected = base64_encode( hash_hmac( 'sha256', $payload, $key, true ) );

        return hash_equals( $expected, $signature );
    }

    /**
     * Log a message if logging is enabled.
     *
     * @param string $message
     */
    public static function log( $message ) {
        if ( get_option( 'mxcs_enable_logging', '1' ) !== '1' ) {
            return;
        }

        $log = get_option( 'mxcs_sync_log', array() );

        $log[] = array(
            'time'    => current_time( 'mysql' ),
            'message' => $message,
        );

        // Keep last 500 entries.
        if ( count( $log ) > 500 ) {
            $log = array_slice( $log, -500 );
        }

        update_option( 'mxcs_sync_log', $log, false );
    }
}
