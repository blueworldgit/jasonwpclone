<?php
/**
 * Core credit sync logic — bridges Xero outstanding balances with B2BKing consumed credit.
 */

defined( 'ABSPATH' ) || exit;

class MXCS_Credit_Sync {

    /** @var MXCS_Xero_Api */
    private $xero_api;

    public function __construct( MXCS_Xero_Api $xero_api ) {
        $this->xero_api = $xero_api;
    }

    /**
     * Sync a single user's credit consumed balance from Xero.
     *
     * @param int $user_id WordPress user ID.
     * @return array|false Result array with old/new balance, or false on failure.
     */
    public function sync_user_credit( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            MXCS_Xero_Api::log( "Sync skipped: user #{$user_id} not found." );
            return false;
        }

        $email = $user->user_email;
        if ( empty( $email ) ) {
            MXCS_Xero_Api::log( "Sync skipped: user #{$user_id} has no email." );
            return false;
        }

        // Check the user has a credit limit set (user-level, group-level, or site default).
        $credit_limit = $this->get_effective_credit_limit( $user_id );
        if ( empty( $credit_limit ) || floatval( $credit_limit ) <= 0 ) {
            MXCS_Xero_Api::log( "Sync skipped: user #{$user_id} ({$email}) has no credit limit." );
            return false;
        }

        // Check Xero connection.
        if ( ! $this->xero_api->is_connected() ) {
            MXCS_Xero_Api::log( 'Sync failed: Xero API not connected.' );
            return false;
        }

        // Query Xero for outstanding balance.
        $outstanding = $this->xero_api->get_outstanding_balance( $email );

        if ( false === $outstanding ) {
            MXCS_Xero_Api::log( "Sync failed: could not retrieve Xero balance for {$email}." );
            return false;
        }

        // Get current consumed balance.
        $old_consumed = (float) get_user_meta( $user_id, 'b2bking_user_credit_consumed_balance', true );
        $new_consumed = (float) $outstanding;

        // Only update if the value has actually changed.
        if ( abs( $old_consumed - $new_consumed ) < 0.01 ) {
            MXCS_Xero_Api::log( "Sync: user #{$user_id} ({$email}) — no change (balance: {$old_consumed})." );
            return array(
                'user_id'      => $user_id,
                'email'        => $email,
                'old_consumed' => $old_consumed,
                'new_consumed' => $new_consumed,
                'changed'      => false,
            );
        }

        // Update consumed balance.
        update_user_meta( $user_id, 'b2bking_user_credit_consumed_balance', $new_consumed );

        // Log to B2BKing credit history.
        $this->log_credit_history( $user_id, $old_consumed, $new_consumed );

        MXCS_Xero_Api::log( sprintf(
            'Sync: user #%d (%s) — consumed balance updated from %.2f to %.2f.',
            $user_id,
            $email,
            $old_consumed,
            $new_consumed
        ) );

        return array(
            'user_id'      => $user_id,
            'email'        => $email,
            'old_consumed' => $old_consumed,
            'new_consumed' => $new_consumed,
            'changed'      => true,
        );
    }

    /**
     * Sync credit for all users that have a credit limit.
     *
     * @return array Summary of results.
     */
    public function sync_all_users() {
        $user_ids = $this->get_credit_user_ids();

        if ( empty( $user_ids ) ) {
            MXCS_Xero_Api::log( 'Bulk sync: no users with credit limits found.' );
            return array(
                'total'   => 0,
                'synced'  => 0,
                'changed' => 0,
                'failed'  => 0,
            );
        }

        $synced  = 0;
        $changed = 0;
        $failed  = 0;

        foreach ( $user_ids as $user_id ) {
            $result = $this->sync_user_credit( $user_id );

            if ( false === $result ) {
                $failed++;
            } else {
                $synced++;
                if ( $result['changed'] ) {
                    $changed++;
                }
            }

            // Brief pause to respect Xero rate limits (60 calls/minute).
            usleep( 500000 ); // 0.5 seconds
        }

        MXCS_Xero_Api::log( sprintf(
            'Bulk sync complete: %d users processed, %d synced, %d changed, %d failed.',
            count( $user_ids ),
            $synced,
            $changed,
            $failed
        ) );

        return array(
            'total'   => count( $user_ids ),
            'synced'  => $synced,
            'changed' => $changed,
            'failed'  => $failed,
        );
    }

    /**
     * Sync credit for a user identified by email address.
     * Used by the webhook handler when we only know the Xero contact email.
     *
     * @param string $email
     * @return array|false
     */
    public function sync_user_by_email( $email ) {
        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            MXCS_Xero_Api::log( "Sync by email: no WP user found for {$email}." );
            return false;
        }

        return $this->sync_user_credit( $user->ID );
    }

    /**
     * Get the effective credit limit for a user (user → group → site default).
     *
     * @param int $user_id
     * @return float
     */
    private function get_effective_credit_limit( $user_id ) {
        // Check for subaccount — use parent's credit if applicable.
        $account_type = get_user_meta( $user_id, 'b2bking_account_type', true );
        if ( 'subaccount' === $account_type ) {
            $parent_id = get_user_meta( $user_id, 'b2bking_account_parent', true );
            if ( $parent_id ) {
                $user_id = (int) $parent_id;
            }
        }

        // 1. User-specific limit.
        $limit = get_user_meta( $user_id, 'b2bking_user_credit_limit', true );
        if ( is_numeric( $limit ) && (float) $limit > 0 ) {
            return (float) $limit;
        }

        // 2. Group default.
        $group_id = get_user_meta( $user_id, 'b2bking_customergroup', true );
        if ( $group_id ) {
            $group_limit = get_post_meta( $group_id, 'b2bking_group_credit_limit', true );
            if ( is_numeric( $group_limit ) && (float) $group_limit > 0 ) {
                return (float) $group_limit;
            }
        }

        // 3. Site-wide default.
        $default = get_option( 'b2bking_default_credit_limit_setting', 0 );
        return (float) $default;
    }

    /**
     * Get all user IDs that have a credit limit (directly or via group/default).
     *
     * @return int[]
     */
    private function get_credit_user_ids() {
        global $wpdb;

        // Get users with an explicit credit limit.
        $user_ids_with_limit = $wpdb->get_col(
            "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'b2bking_user_credit_limit'
             AND meta_value > 0"
        );

        // Also get users whose group has a credit limit.
        $groups_with_credit = $wpdb->get_col(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = 'b2bking_group_credit_limit'
             AND meta_value > 0"
        );

        if ( ! empty( $groups_with_credit ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $groups_with_credit ), '%s' ) );
            $group_users  = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->usermeta}
                     WHERE meta_key = 'b2bking_customergroup'
                     AND meta_value IN ({$placeholders})",
                    ...$groups_with_credit
                )
            );
            $user_ids_with_limit = array_merge( $user_ids_with_limit, $group_users );
        }

        // If there's a site-wide default > 0, include all B2BKing users.
        $site_default = (float) get_option( 'b2bking_default_credit_limit_setting', 0 );
        if ( $site_default > 0 ) {
            $all_b2b_users = $wpdb->get_col(
                "SELECT user_id FROM {$wpdb->usermeta}
                 WHERE meta_key = 'b2bking_customergroup'"
            );
            $user_ids_with_limit = array_merge( $user_ids_with_limit, $all_b2b_users );
        }

        return array_unique( array_map( 'intval', $user_ids_with_limit ) );
    }

    /**
     * Append an entry to B2BKing's credit history for a user.
     *
     * Format: DATE:OPERATION:AMOUNT:NEW_BALANCE:NOTE separated by semicolons.
     *
     * @param int   $user_id
     * @param float $old_consumed
     * @param float $new_consumed
     */
    private function log_credit_history( $user_id, $old_consumed, $new_consumed ) {
        $history = get_user_meta( $user_id, 'b2bking_user_credit_history', true );

        $diff = $new_consumed - $old_consumed;
        $operation = $diff > 0 ? 'consume' : 'reimburse';
        $amount    = abs( $diff );

        $credit_limit = $this->get_effective_credit_limit( $user_id );
        $available    = $credit_limit - $new_consumed;

        $entry = sprintf(
            '%s:%s:%.2f:%.2f:Xero sync (outstanding invoices)',
            current_time( 'Y/m/d' ),
            $operation,
            $amount,
            $available
        );

        if ( ! empty( $history ) ) {
            $history .= ';' . $entry;
        } else {
            $history = $entry;
        }

        update_user_meta( $user_id, 'b2bking_user_credit_history', $history );
    }
}
