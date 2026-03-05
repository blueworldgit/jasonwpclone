<?php
/**
 * Xero webhook handler — REST API endpoint for real-time invoice updates.
 */

defined( 'ABSPATH' ) || exit;

class MXCS_Webhook_Handler {

    /** @var MXCS_Credit_Sync */
    private $credit_sync;

    public function __construct( MXCS_Credit_Sync $credit_sync ) {
        $this->credit_sync = $credit_sync;
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register the webhook REST route.
     */
    public function register_routes() {
        register_rest_route( 'maxus-xero-sync/v1', '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_webhook' ),
            'permission_callback' => '__return_true', // Auth handled via signature verification.
        ) );
    }

    /**
     * Handle an incoming Xero webhook.
     *
     * Xero sends two types of requests:
     * 1. Intent-to-receive validation (empty events array) — must return 200 with correct signature check.
     * 2. Event notifications — contain invoice/contact update events.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_webhook( WP_REST_Request $request ) {
        // Check if webhooks are enabled.
        if ( get_option( 'mxcs_enable_webhook', '1' ) !== '1' ) {
            return new WP_REST_Response( null, 200 );
        }

        $payload   = $request->get_body();
        $signature = $request->get_header( 'x-xero-signature' );

        if ( empty( $signature ) ) {
            MXCS_Xero_Api::log( 'Webhook: missing X-Xero-Signature header.' );
            return new WP_REST_Response( null, 401 );
        }

        // Load the Xero API class for signature verification.
        $xero_api = mxcs()->get_xero_api();

        if ( ! $xero_api->verify_webhook_signature( $payload, $signature ) ) {
            MXCS_Xero_Api::log( 'Webhook: invalid signature.' );
            // Xero expects 401 for failed signature validation.
            return new WP_REST_Response( null, 401 );
        }

        $body = json_decode( $payload, true );

        if ( ! is_array( $body ) ) {
            MXCS_Xero_Api::log( 'Webhook: invalid JSON payload.' );
            return new WP_REST_Response( null, 200 );
        }

        $events = isset( $body['events'] ) ? $body['events'] : array();

        // Intent-to-receive check: Xero sends an empty events array on first setup.
        // Must return 200 to confirm the webhook is working.
        if ( empty( $events ) ) {
            MXCS_Xero_Api::log( 'Webhook: intent-to-receive validation — responded 200.' );
            return new WP_REST_Response( null, 200 );
        }

        // Process events asynchronously to respond quickly.
        // Schedule a single event to process the batch.
        $event_data = $this->extract_invoice_events( $events );

        if ( ! empty( $event_data ) ) {
            // Use a one-time cron event to process outside this request.
            wp_schedule_single_event( time(), 'mxcs_process_webhook_events', array( $event_data ) );

            MXCS_Xero_Api::log( sprintf(
                'Webhook: received %d invoice event(s), scheduled for processing.',
                count( $event_data )
            ) );
        }

        // Always respond 200 quickly so Xero doesn't retry.
        return new WP_REST_Response( null, 200 );
    }

    /**
     * Extract invoice-related events from the Xero webhook payload.
     *
     * @param array $events
     * @return array Array of resource IDs (invoice IDs).
     */
    private function extract_invoice_events( $events ) {
        $invoice_ids = array();

        foreach ( $events as $event ) {
            $resource_url = isset( $event['resourceUrl'] ) ? $event['resourceUrl'] : '';
            $event_type   = isset( $event['eventType'] ) ? $event['eventType'] : '';
            $category     = isset( $event['eventCategory'] ) ? $event['eventCategory'] : '';

            // We care about INVOICE events (create, update, status change).
            if ( 'INVOICE' === $category ) {
                // Extract the invoice ID from the resource URL.
                // Format: https://api.xero.com/api.xro/2.0/Invoices/{InvoiceID}
                $parts = explode( '/', rtrim( $resource_url, '/' ) );
                $invoice_id = end( $parts );

                if ( ! empty( $invoice_id ) ) {
                    $invoice_ids[] = $invoice_id;
                }
            }
        }

        return array_unique( $invoice_ids );
    }

    /**
     * Process queued webhook events — look up invoices and sync affected users.
     *
     * @param array $invoice_ids
     */
    public function process_events( $invoice_ids ) {
        if ( empty( $invoice_ids ) || ! is_array( $invoice_ids ) ) {
            return;
        }

        $xero_api = mxcs()->get_xero_api();

        if ( ! $xero_api->is_connected() ) {
            MXCS_Xero_Api::log( 'Webhook processing: Xero API not connected.' );
            return;
        }

        // We need to find which contact each invoice belongs to.
        // Fetch each invoice to get the contact email, then sync that user.
        $emails_synced = array();

        foreach ( $invoice_ids as $invoice_id ) {
            try {
                $email = $xero_api->get_invoice_contact_email( $invoice_id );

                if ( $email && ! in_array( $email, $emails_synced, true ) ) {
                    $this->credit_sync->sync_user_by_email( $email );
                    $emails_synced[] = $email;
                }
            } catch ( \Exception $e ) {
                MXCS_Xero_Api::log( 'Webhook processing error for invoice ' . $invoice_id . ': ' . $e->getMessage() );
            }

            usleep( 500000 ); // Rate limit protection.
        }
    }
}

// Register the async event handler globally so WP cron can find it.
add_action( 'mxcs_process_webhook_events', function ( $invoice_ids ) {
    $sync = mxcs()->get_credit_sync();
    if ( $sync ) {
        $handler = new MXCS_Webhook_Handler( $sync );
        $handler->process_events( $invoice_ids );
    }
} );
