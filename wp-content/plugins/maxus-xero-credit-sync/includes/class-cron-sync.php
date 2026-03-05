<?php
/**
 * WP Cron integration — periodic fallback sync of all credit users.
 */

defined( 'ABSPATH' ) || exit;

class MXCS_Cron_Sync {

    /** @var MXCS_Credit_Sync */
    private $credit_sync;

    public function __construct( MXCS_Credit_Sync $credit_sync ) {
        $this->credit_sync = $credit_sync;

        add_action( 'mxcs_cron_sync_event', array( $this, 'run' ) );
        add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
    }

    /**
     * Add custom cron schedules.
     *
     * @param array $schedules
     * @return array
     */
    public function add_schedules( $schedules ) {
        $schedules['twicedaily_mxcs'] = array(
            'interval' => 12 * HOUR_IN_SECONDS,
            'display'  => __( 'Twice Daily (Xero Credit Sync)', 'maxus-xero-credit-sync' ),
        );
        return $schedules;
    }

    /**
     * Run the cron sync job.
     */
    public function run() {
        if ( get_option( 'mxcs_enable_cron', '1' ) !== '1' ) {
            MXCS_Xero_Api::log( 'Cron sync: disabled, skipping.' );
            return;
        }

        MXCS_Xero_Api::log( 'Cron sync: starting...' );

        $result = $this->credit_sync->sync_all_users();

        MXCS_Xero_Api::log( sprintf(
            'Cron sync: finished — %d total, %d synced, %d changed, %d failed.',
            $result['total'],
            $result['synced'],
            $result['changed'],
            $result['failed']
        ) );
    }

    /**
     * Reschedule the cron event with a new frequency.
     *
     * @param string $frequency WP cron recurrence name (hourly, twicedaily_mxcs, daily).
     */
    public static function reschedule( $frequency ) {
        wp_clear_scheduled_hook( 'mxcs_cron_sync_event' );

        $valid = array( 'hourly', 'twicedaily_mxcs', 'daily' );
        if ( ! in_array( $frequency, $valid, true ) ) {
            $frequency = 'hourly';
        }

        wp_schedule_event( time(), $frequency, 'mxcs_cron_sync_event' );
    }
}
