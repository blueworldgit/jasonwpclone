<?php
/**
 * Admin interface — settings page, manual sync buttons, user profile integration, logs.
 */

defined( 'ABSPATH' ) || exit;

class MXCS_Admin {

    /** @var MXCS_Credit_Sync */
    private $credit_sync;

    public function __construct( MXCS_Credit_Sync $credit_sync ) {
        $this->credit_sync = $credit_sync;

        // Settings page.
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // User profile sync button.
        add_action( 'edit_user_profile', array( $this, 'user_profile_button' ), 20 );
        add_action( 'admin_post_mxcs_sync_user', array( $this, 'handle_sync_user' ) );

        // Bulk sync via admin page.
        add_action( 'admin_post_mxcs_sync_all', array( $this, 'handle_sync_all' ) );

        // WooCommerce order action.
        add_filter( 'woocommerce_order_actions', array( $this, 'add_order_action' ) );
        add_action( 'woocommerce_order_action_mxcs_sync_credit', array( $this, 'handle_order_action' ) );

        // Admin CSS.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Clear log action.
        add_action( 'admin_post_mxcs_clear_log', array( $this, 'handle_clear_log' ) );
    }

    /**
     * Enqueue admin assets on our settings page.
     */
    public function enqueue_assets( $hook ) {
        if ( 'settings_page_mxcs-settings' === $hook || 'user-edit.php' === $hook ) {
            wp_enqueue_style( 'mxcs-admin', MXCS_PLUGIN_URL . 'assets/admin.css', array(), MXCS_VERSION );
        }
    }

    // =========================================================================
    // Settings Page
    // =========================================================================

    public function add_menu_page() {
        add_options_page(
            'Xero Credit Sync',
            'Xero Credit Sync',
            'manage_woocommerce',
            'mxcs-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'mxcs_settings', 'mxcs_webhook_key', 'sanitize_text_field' );
        register_setting( 'mxcs_settings', 'mxcs_sync_frequency', array( $this, 'sanitize_frequency' ) );
        register_setting( 'mxcs_settings', 'mxcs_enable_webhook', array( $this, 'sanitize_checkbox' ) );
        register_setting( 'mxcs_settings', 'mxcs_enable_cron', array( $this, 'sanitize_checkbox' ) );
        register_setting( 'mxcs_settings', 'mxcs_enable_logging', array( $this, 'sanitize_checkbox' ) );
    }

    public function sanitize_frequency( $value ) {
        $valid = array( 'hourly', 'twicedaily_mxcs', 'daily' );
        $value = in_array( $value, $valid, true ) ? $value : 'hourly';

        // Reschedule cron whenever the frequency changes.
        MXCS_Cron_Sync::reschedule( $value );

        return $value;
    }

    public function sanitize_checkbox( $value ) {
        return '1' === $value ? '1' : '0';
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $xero_api     = mxcs()->get_xero_api();
        $is_connected = $xero_api->is_connected();
        $next_cron    = wp_next_scheduled( 'mxcs_cron_sync_event' );
        $log          = get_option( 'mxcs_sync_log', array() );
        $webhook_url  = rest_url( 'maxus-xero-sync/v1/webhook' );

        ?>
        <div class="wrap mxcs-settings">
            <h1>Xero Credit Sync</h1>

            <div class="mxcs-status-bar">
                <span class="mxcs-status <?php echo $is_connected ? 'mxcs-status--ok' : 'mxcs-status--error'; ?>">
                    Xero API: <?php echo $is_connected ? 'Connected' : 'Not Connected'; ?>
                </span>
                <?php if ( $next_cron ) : ?>
                    <span class="mxcs-status mxcs-status--info">
                        Next cron sync: <?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_cron ), 'M j, Y g:i A' ) ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'mxcs_settings' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mxcs_webhook_key">Webhook Signing Key</label></th>
                        <td>
                            <input type="text" id="mxcs_webhook_key" name="mxcs_webhook_key"
                                   value="<?php echo esc_attr( get_option( 'mxcs_webhook_key', '' ) ); ?>"
                                   class="regular-text" autocomplete="off" />
                            <p class="description">From the Xero Developer Portal &gt; Webhooks. Used to verify incoming webhook payloads.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook Endpoint URL</th>
                        <td>
                            <code><?php echo esc_html( $webhook_url ); ?></code>
                            <p class="description">Enter this URL in the Xero Developer Portal as your webhook delivery URL.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mxcs_sync_frequency">Cron Sync Frequency</label></th>
                        <td>
                            <?php $freq = get_option( 'mxcs_sync_frequency', 'hourly' ); ?>
                            <select id="mxcs_sync_frequency" name="mxcs_sync_frequency">
                                <option value="hourly" <?php selected( $freq, 'hourly' ); ?>>Hourly</option>
                                <option value="twicedaily_mxcs" <?php selected( $freq, 'twicedaily_mxcs' ); ?>>Twice Daily</option>
                                <option value="daily" <?php selected( $freq, 'daily' ); ?>>Daily</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Webhook</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mxcs_enable_webhook" value="1"
                                    <?php checked( get_option( 'mxcs_enable_webhook', '1' ), '1' ); ?> />
                                Process incoming Xero webhooks for real-time sync
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Cron Sync</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mxcs_enable_cron" value="1"
                                    <?php checked( get_option( 'mxcs_enable_cron', '1' ), '1' ); ?> />
                                Run periodic background sync of all credit users
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Logging</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mxcs_enable_logging" value="1"
                                    <?php checked( get_option( 'mxcs_enable_logging', '1' ), '1' ); ?> />
                                Log sync activity (viewable below)
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Save Settings' ); ?>
            </form>

            <hr />

            <h2>Manual Sync</h2>
            <p>Sync all users with credit limits against their outstanding Xero invoices.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="mxcs_sync_all" />
                <?php wp_nonce_field( 'mxcs_sync_all' ); ?>
                <button type="submit" class="button button-primary" <?php echo $is_connected ? '' : 'disabled'; ?>>
                    Sync All Credit Users
                </button>
                <?php if ( ! $is_connected ) : ?>
                    <span class="description" style="color:#d63638;"> Xero is not connected — check WooCommerce Xero settings.</span>
                <?php endif; ?>
            </form>

            <?php $this->render_admin_notices(); ?>

            <hr />

            <h2>Sync Log</h2>
            <?php if ( ! empty( $log ) ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:10px;">
                    <input type="hidden" name="action" value="mxcs_clear_log" />
                    <?php wp_nonce_field( 'mxcs_clear_log' ); ?>
                    <button type="submit" class="button button-secondary button-small">Clear Log</button>
                </form>
                <div class="mxcs-log-container">
                    <table class="widefat striped">
                        <thead>
                            <tr><th>Time</th><th>Message</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ( array_reverse( $log ) as $entry ) : ?>
                                <tr>
                                    <td class="mxcs-log-time"><?php echo esc_html( $entry['time'] ); ?></td>
                                    <td><?php echo esc_html( $entry['message'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="description">No log entries yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Show admin notices for sync results stored in transients.
     */
    private function render_admin_notices() {
        $result = get_transient( 'mxcs_sync_result' );
        if ( $result ) {
            delete_transient( 'mxcs_sync_result' );
            if ( is_array( $result ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo sprintf(
                    'Bulk sync complete: %d users processed, %d synced, %d changed, %d failed.',
                    $result['total'],
                    $result['synced'],
                    $result['changed'],
                    $result['failed']
                );
                echo '</p></div>';
            } elseif ( is_string( $result ) ) {
                echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $result ) . '</p></div>';
            }
        }
    }

    // =========================================================================
    // Manual Sync Handlers
    // =========================================================================

    /**
     * Handle "Sync All" form submission.
     */
    public function handle_sync_all() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Unauthorized' );
        }
        check_admin_referer( 'mxcs_sync_all' );

        $result = $this->credit_sync->sync_all_users();
        set_transient( 'mxcs_sync_result', $result, 30 );

        wp_safe_redirect( admin_url( 'options-general.php?page=mxcs-settings' ) );
        exit;
    }

    /**
     * Handle single-user sync from the user profile.
     */
    public function handle_sync_user() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Unauthorized' );
        }

        $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        check_admin_referer( 'mxcs_sync_user_' . $user_id );

        if ( ! $user_id ) {
            wp_die( 'Invalid user.' );
        }

        $result = $this->credit_sync->sync_user_credit( $user_id );

        if ( false === $result ) {
            set_transient( 'mxcs_sync_result', 'Sync failed for user #' . $user_id . '. Check the log for details.', 30 );
        } elseif ( $result['changed'] ) {
            set_transient( 'mxcs_sync_result', sprintf(
                'Credit synced for %s: consumed balance updated from %.2f to %.2f.',
                $result['email'],
                $result['old_consumed'],
                $result['new_consumed']
            ), 30 );
        } else {
            set_transient( 'mxcs_sync_result', sprintf(
                'Credit checked for %s: no change needed (balance: %.2f).',
                $result['email'],
                $result['old_consumed']
            ), 30 );
        }

        wp_safe_redirect( admin_url( 'user-edit.php?user_id=' . $user_id ) );
        exit;
    }

    /**
     * Clear the sync log.
     */
    public function handle_clear_log() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Unauthorized' );
        }
        check_admin_referer( 'mxcs_clear_log' );

        update_option( 'mxcs_sync_log', array() );

        wp_safe_redirect( admin_url( 'options-general.php?page=mxcs-settings' ) );
        exit;
    }

    // =========================================================================
    // User Profile
    // =========================================================================

    /**
     * Add a sync button to the user profile edit page.
     */
    public function user_profile_button( $user ) {
        $credit_limit = get_user_meta( $user->ID, 'b2bking_user_credit_limit', true );

        // Only show for users that have a credit limit.
        if ( empty( $credit_limit ) || floatval( $credit_limit ) <= 0 ) {
            return;
        }

        $consumed = get_user_meta( $user->ID, 'b2bking_user_credit_consumed_balance', true );
        $available = floatval( $credit_limit ) - floatval( $consumed );

        ?>
        <h2>Xero Credit Sync</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th>Credit Summary</th>
                <td>
                    <p>
                        <strong>Limit:</strong> <?php echo esc_html( wc_price( $credit_limit ) ); ?> &nbsp;|&nbsp;
                        <strong>Used:</strong> <?php echo esc_html( wc_price( $consumed ) ); ?> &nbsp;|&nbsp;
                        <strong>Available:</strong> <?php echo esc_html( wc_price( $available ) ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>Sync from Xero</th>
                <td>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                        <input type="hidden" name="action" value="mxcs_sync_user" />
                        <input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>" />
                        <?php wp_nonce_field( 'mxcs_sync_user_' . $user->ID ); ?>
                        <button type="submit" class="button button-secondary">Sync Credit from Xero</button>
                    </form>
                    <p class="description">Queries Xero for this customer's outstanding invoices and updates consumed credit balance.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    // =========================================================================
    // WooCommerce Order Action
    // =========================================================================

    /**
     * Add a custom order action to sync the customer's credit.
     */
    public function add_order_action( $actions ) {
        $actions['mxcs_sync_credit'] = 'Sync customer credit from Xero';
        return $actions;
    }

    /**
     * Handle the order action.
     *
     * @param WC_Order $order
     */
    public function handle_order_action( $order ) {
        $customer_id = $order->get_customer_id();

        if ( ! $customer_id ) {
            $order->add_order_note( 'Xero Credit Sync: no customer account linked to this order.' );
            return;
        }

        $result = $this->credit_sync->sync_user_credit( $customer_id );

        if ( false === $result ) {
            $order->add_order_note( 'Xero Credit Sync: sync failed — check Xero Credit Sync log.' );
        } elseif ( $result['changed'] ) {
            $order->add_order_note( sprintf(
                'Xero Credit Sync: consumed balance updated from %s to %s.',
                wc_price( $result['old_consumed'] ),
                wc_price( $result['new_consumed'] )
            ) );
        } else {
            $order->add_order_note( sprintf(
                'Xero Credit Sync: no change (balance: %s).',
                wc_price( $result['old_consumed'] )
            ) );
        }
    }
}
