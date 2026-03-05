<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Worldpay\Api\Enums\Api;
use Worldpay\Api\Enums\Environment;
use Worldpay\Api\Exceptions\AuthenticationException;
use Worldpay\Api\Providers\AccessWorldpayConfigProvider;
use Worldpay\Api\Providers\ProxyConfigProvider;
use Worldpay\Api\AccessWorldpay;
use Worldpay\Api\Entities\Customer;
use Worldpay\Api\ValueObjects\BillingAddress;
use Worldpay\Api\ValueObjects\ShippingAddress;
use Worldpay\Api\Entities\Order;
use Worldpay\Api\ValueObjects\UserAgent;

abstract class WC_Worldpay_Payment_Method extends WC_Payment_Gateway {

	use WC_Access_Worldpay_Field_Validators;
	use WC_Access_Worldpay_Credentials_Validators;
	use WC_Worldpay_Ecommerce_String_Utils;

	/**
	 * @var string
	 */
	public $api;

	/**
	 * Api config provider.
	 *
	 * @var AccessWorldpayConfigProvider
	 */
	protected $api_config_provider;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_form_fields();
		$this->init_settings();

		if ( is_admin() && ( strtolower( $this->id ) === wc_clean( sanitize_text_field( wp_unslash( $_GET['section'] ?? '' ) ) ) ) ) {
			woocommerce_worldpay_ecommerce_compatibility_check();
		}

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		if ( is_admin() ) {
			$this->add_admin_hooks();
		}

		$error = empty( $_GET[ $this->id ] ) ? '' : wc_clean( sanitize_text_field( wp_unslash( $_GET[ $this->id ] ) ) );
		if ( ! empty( $error ) ) {
			add_filter(
				'woocommerce_checkout_init',
				function () {
					wc_print_notice( 'Something went wrong while processing your payment. Please try again.', 'error' );
				}
			);
		}
		add_action(
			'woocommerce_api_worldpay_ecommerce_test_api_credentials',
			array(
				$this,
				'test_api_credentials_request',
			)
		);
		if ( $this->supports( 'tokenization' ) ) {
			add_action(
				'woocommerce_payment_token_deleted',
				array(
					$this,
					'delete_payment_token',
				),
				10,
				2
			);
			add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_disabled_tokens' ), 10, 2 );
			add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_expired' ), 99, 2 );
			add_action(
				'before_woocommerce_add_payment_method',
				function() {
					add_filter( 'woocommerce_available_payment_gateways', array( $this, 'remove_add_payment_method_availability' ) );
				}
			);
			add_action(
				'after_woocommerce_add_payment_method',
				function() {
					remove_filter( 'woocommerce_available_payment_gateways', array( $this, 'remove_add_payment_method_availability' ) );
				}
			);
		}
	}

	/**
	 * Add admin hooks.
	 *
	 * @return void
	 */
	public function add_admin_hooks() {
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		add_filter( 'woocommerce_generate_masked_totally_html', array( $this, 'generate_masked_totally_html' ) );
		add_filter( 'woocommerce_generate_masked_partially_html', array( $this, 'generate_masked_partially_html' ) );

		add_filter( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'test_api_credentials_save' )
		);
	}

	/**
	 * Generate html for custom masked password type.
	 *
	 * @param $key
	 * @param $data
	 *
	 * @return false|string
	 */
	public function generate_masked_totally_html( $key, $data ) {
		return $this->generate_masked_partially_html( $key, $data );
	}

	/**
	 * Generate html for custom masked text type.
	 *
	 * @param $key
	 * @param $data
	 *
	 * @return false|string
	 */
	public function generate_masked_partially_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data        = wp_parse_args( $data, $defaults );
		$field_value = $this->get_option( $key );
		if ( 'masked_totally' === $data['type'] ) {
			$field_value = $this->limit(
				$this->mask( $field_value, '*', 0, $this->length( $field_value, 'UTF-8' ) ),
				64,
				''
			);
		} else {
			$field_value = $this->mask( $field_value, '*', 0, $this->length( $field_value, 'UTF-8' ) - 4 );
		}
		$data['type'] = 'text';
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?>
					<?php
					echo wp_kses_post( $this->get_tooltip_html( $data ) );
					?>
				</label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
					</legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>"
							type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>"
							id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>"
							value="<?php echo esc_html( $field_value ); ?>"
							placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>"
						<?php disabled( $data['disabled'] ); ?>
						<?php
						echo wp_kses_post( $this->get_custom_attribute_html( $data ) ); // WPCS: XSS ok.
						?>
					/>
					<?php echo wp_kses_post( $this->get_description_html( $data ) ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Add admin scripts.
	 *
	 * @param string $hook_suffix current admin page.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
			return;
		}
		$current_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ?? '' ) );
		if ( 'checkout' !== $current_tab ) {
			return;
		}

		$current_section = sanitize_text_field( wp_unslash( $_GET['section'] ?? '' ) );
		if ( $this->id !== $current_section ) {
			return;
		}

		wp_enqueue_script(
			'worldpay-ecommerce-admin',
			woocommerce_worldpay_ecommerce_url( '/assets/admin/js/worldpay-ecommerce-admin.js' ),
			array( 'jquery' ),
			WC()->version,
			true
		);
		wp_localize_script(
			'worldpay-ecommerce-admin',
			'worldpay_ecommerce_admin_params',
			array(
				'_wpnonce'                 => wp_create_nonce( 'worldpay-ecommerce-test_api_credentials' ),
				'test_api_credentials_url' => WC()->api_request_url( 'worldpay_ecommerce_test_api_credentials' ),
				'payment_method_id'        => $this->id,
			)
		);
	}

	/**
	 * Initialize PHP SDK for payment gateway.
	 *
	 * @return AccessWorldpay
	 * @throws AuthenticationException
	 */
	public function initialize_api() {
		$this->init_user_agent( $this->is_production() );

		$this->configure_proxy();

		if ( $this->api_config_provider instanceof AccessWorldpayConfigProvider ) {
			return AccessWorldpay::config( $this->api_config_provider );
		}

		return AccessWorldpay::config( $this->configure_api() );
	}

	/**
	 * Configure API for payment gateway request.
	 *
	 * @return AccessWorldpayConfigProvider|null
	 */
	public function configure_api() {
		$api_config_provider                    = AccessWorldpayConfigProvider::instance();
		$api_config_provider->environment       = $this->get_api_environment();
		$api_config_provider->username          = $this->get_api_username();
		$api_config_provider->password          = $this->get_api_password();
		$api_config_provider->merchantEntity    = $this->get_merchant_entity();
		$api_config_provider->merchantNarrative = $this->get_merchant_narrative();
		$api_config_provider->api               = $this->api;

		return $api_config_provider;
	}

	/**
	 * Configure proxy for API requests.
	 *
	 * @return void
	 */
	public function configure_proxy() {
		$proxy_config_provider = ProxyConfigProvider::instance();

		$wp_proxy = new WP_HTTP_Proxy();
		if ( $wp_proxy->is_enabled() ) {
			$proxy_config_provider->host = $wp_proxy->host();
			$proxy_config_provider->port = $wp_proxy->port();
			if ( $wp_proxy->use_authentication() ) {
				$proxy_config_provider->proxyUsername = $wp_proxy->username();
				$proxy_config_provider->proxyPassword = $wp_proxy->password();
			}
		}
	}

	/**
	 * Get API environment - test or live.
	 *
	 * @return string
	 */
	public function get_api_environment() {
		return wc_string_to_bool( $this->get_option( 'is_live_mode' ) ) ?
			Environment::LIVE_MODE :
			Environment::TRY_MODE;
	}

	/**
	 * Get the status for card storage - enabled or disabled.
	 *
	 * @return bool
	 */
	public function has_tokens_enabled() {
		return wc_string_to_bool( $this->get_option( 'app_enable_tokens' ) );
	}

	/**
	 * Get API username for test or live environment.
	 *
	 * @return string
	 */
	public function get_api_username() {
		return wc_string_to_bool( $this->get_option( 'is_live_mode' ) ) ?
			$this->get_option( 'app_api_live_username' ) :
			$this->get_option( 'app_api_try_username' );
	}

	/**
	 * Get API password for test or live environment.
	 *
	 * @return string
	 */
	public function get_api_password() {
		return wc_string_to_bool( $this->get_option( 'is_live_mode' ) ) ?
			$this->get_option( 'app_api_live_password' ) :
			$this->get_option( 'app_api_try_password' );
	}

	/**
	 * Get API merchant entity.
	 *
	 * @return string
	 */
	public function get_merchant_entity() {
		return $this->get_option( 'app_merchant_entity' );
	}

	/**
	 * Get API merchant narrative.
	 *
	 * @return string
	 */
	public function get_merchant_narrative() {
		return $this->get_option( 'app_merchant_narrative' );
	}

	/**
	 * Get APP debug mode
	 *
	 * @return bool
	 */
	public function get_merchant_debug_mode() {
		return wc_string_to_bool( $this->get_option( 'app_debug' ) );
	}

	/**
	 * Get order data for API order initialization.
	 *
	 * @param  WC_Order $wc_order
	 *
	 * @return Order
	 */
	public function get_order_data( WC_Order $wc_order ) {
		$wc_order_data              = $wc_order->get_data();
		$api_order                  = new Order();
		$api_order->id              = $wc_order->get_id();
		$api_order->billingAddress  = $this->get_order_address( $wc_order_data, 'billing' );
		$api_order->shippingAddress = $this->get_order_address( $wc_order_data, 'shipping' );
		$api_order->customer        = $this->get_order_customer( $wc_order_data );
		$api_order->currency        = $wc_order->get_currency();

		return $api_order;
	}

	/**
	 * Get billing/shipping address for API order initialization.
	 *
	 * @param array  $wc_order_data
	 * @param string $address_type
	 *
	 * @return BillingAddress|ShippingAddress
	 */
	public function get_order_address( array $wc_order_data, string $address_type ) {
		$api_address              = 'billing' === $address_type ?
			new BillingAddress() :
			new ShippingAddress();
		$api_address->address1    = $wc_order_data[ $address_type ]['address_1'] ?? '';
		$api_address->address2    = $wc_order_data[ $address_type ]['address_2'] ?? '';
		$api_address->address3    = '';
		$api_address->postalCode  = $wc_order_data[ $address_type ]['postcode'] ?? '';
		$api_address->city        = $wc_order_data[ $address_type ]['city'] ?? '';
		$api_address->state       = $wc_order_data[ $address_type ]['state'] ?? '';
		$api_address->countryCode = $wc_order_data[ $address_type ]['country'] ?? '';

		return $api_address;
	}

	/**
	 * Get customer details for API order initialization.
	 *
	 * @param  array $wc_order_data
	 *
	 * @return Customer
	 */
	public function get_order_customer( array $wc_order_data ) {
		$api_customer              = new Customer();
		$api_customer->firstName   = $wc_order_data['billing']['first_name'] ?? '';
		$api_customer->lastName    = $wc_order_data['billing']['last_name'] ?? '';
		$api_customer->email       = $wc_order_data['billing']['email'] ?? '';
		$api_customer->phoneNumber = $wc_order_data['billing']['phone'] ?? '';

		return $api_customer;
	}

	/**
	 * @inheritDoc
	 */
	public function save_payment_method_checkbox() {
		$html = sprintf(
			'<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
				<input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
				<label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
			</p>',
			esc_attr( $this->id ),
			esc_html__( 'Save payment information to my account for future purchases.', 'worldpay-ecommerce-woocommerce' )
		);
		/**
		 * Filter the saved payment method checkbox HTML
		 *
		 * @since 2.6.0
		 * @param string $html Checkbox HTML.
		 * @param WC_Payment_Gateway $this Payment gateway instance.
		 * @return string
		 */
		echo apply_filters( 'woocommerce_payment_gateway_save_new_payment_method_option_html', $html, $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function should_save_payment_method() {
		return ! empty( wc_clean( sanitize_text_field( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ?? '' ) ) );
	}

	public function is_saved_payment_method() {
		return ( isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && 'new' !== $_POST[ 'wc-' . $this->id . '-payment-token' ] );
	}

	public function get_saved_payment_method_id() {
		return wc_clean( wp_unslash( $_POST[ 'wc-' . $this->id . '-payment-token' ] ?? null ) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_saved_payment_method_option_html( $token ) {
		return parent::get_saved_payment_method_option_html( new WC_Worldpay_Payment_Token( $token ) );
	}

	public function delete_payment_token( $token_id, WC_Payment_Token $token_object ): void {
		if ( $this->id !== $token_object->get_gateway_id() ) {
			return;
		}

		try {
			$this->api_config_provider      = $this->configure_api();
			$this->api_config_provider->api = Api::ACCESS_WORLDPAY_TOKENS_API;

			$api          = $this->initialize_api();
			$api_response = $api->paymentToken()
								->withTokenId( $token_object->get_token() )
								->delete();

			if ( ! $api_response->isSuccessful() ) {
				throw new Exception( 'Unable to delete payment method from your account.' );
			}
		} catch ( \Exception $e ) {
			if ( isset( $api_response ) ) {
				wc_get_logger()->info( $api_response->getResponseMetadata( $this->id_suffix . 'DeletePaymentToken', $e->getMessage() ) );
			}
			$message = __(
				'Your saved card has been removed from your account. However, we were unable to delete it from our payment provider.',
				'worldpay-ecommerce-woocommerce'
			);

			wc_add_notice( $message, 'error' );
		}
	}

	public function payment_methods_list_expired( $item, $payment_token ) {
		if ( $this->id !== $payment_token->get_gateway_id() ) {
			return $item;
		}

		$token = new WC_Worldpay_Payment_Token( $payment_token );
		if ( $token->is_expired() ) {
			$item['method']['display_brand']   = 'Expired - ' . $item['method']['brand'];
			$item['method']['brand']           = 'Expired - ' . $item['method']['brand'];
			$item[ $this->id . '_is_expired' ] = true;
		}

		return $item;
	}

	public function payment_methods_list_disabled_tokens( $item, $payment_token ) {
		if ( $this->id !== $payment_token->get_gateway_id() ) {
			return $item;
		}
		if ( $this->has_tokens_enabled() ) {
			return $item;
		}
		if ( is_checkout() ) {
			return array();
		}

		return $item;
	}

	public function remove_add_payment_method_availability( $available_gateways ) {
		if ( ! $this->supports( 'add_payment_method' ) ) {
			unset( $available_gateways[ $this->id ] );
		}

		return $available_gateways;
	}

	 /**
	  * Initiate and build the User-Agent to be sent with the API on each request.
	  *
	  * @param bool $is_enabled
	  *
	  * @return void
	  */
	public function init_user_agent( bool $is_enabled ): void {
		if ( ! $is_enabled ) {
			UserAgent::disable();

			return;
		}

		UserAgent::enable();

		$user_agent                  = UserAgent::getInstance();
		$user_agent->platformName    = 'WooCommerce';
		$user_agent->platformVersion = WC()->version;
		$user_agent->storeUrl        = parse_url( get_site_url(), PHP_URL_HOST );
		$user_agent->pluginName      = 'worldpay-ecommerce-woocommerce';
		$user_agent->pluginVersion   = WORLDPAY_ECOMMERCE_WOOCOMMERCE_PLUGIN_VERSION;
		$user_agent->cmsName         = 'WordPress';
		$user_agent->cmsVersion      = get_bloginfo( 'version' );

		if ( str_contains( $this->id, 'checkout' ) ) {
			$user_agent->integrationType = 'Onsite';
		} elseif ( str_contains( $this->id, 'hpp' ) ) {
			$user_agent->integrationType = 'Offsite';
		}

		$user_agent->integrationEnvironment = ( $this->get_api_environment() === Environment::LIVE_MODE ) ? 'Live' : 'Try';
	}

	/**
	 * Check whether we are on production environment
	 *
	 * @return bool true = (production); false = (dev/test/local)
	 */
	protected function is_production(): bool {
		return (bool) apply_filters( 'worldpay_ecommerce_is_production', wp_get_environment_type() === 'production' );
	}

	/**
	 * Checks an order to see if it contains a subscription.
	 *
	 * @param mixed $order A WC_Order object or the ID of the order.
	 *
	 * @return bool
	 */
	public function order_contains_subscription( $order ) {
		return function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order );
	}

	public function order_is_subscription( $order_id ) {
		return function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order_id );
	}

	/**
	 * Returns true when viewing the subscription add/change payment method page.
	 *
	 * @return bool
	 */
	public function is_subscription_change_payment_method_page() {
		return function_exists( 'wcs_is_subscription' ) && isset( $_GET['change_payment_method'] );
	}

	/**
	 * Wrapper for wc_get_order.
	 *
	 * @param $order_id
	 *
	 * @return WC_Order|WC_Order_Refund
	 * @throws Exception
	 */
	public function get_wc_order_by_id( $order_id ) {
		$wc_order = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $wc_order || ! $wc_order instanceof WC_Order ) {
			throw new \Exception(
				__(
					'Unable to retrieve order by ID ' . $order_id,
					'worldpay-ecommerce-woocommerce'
				)
			);
		}

		return $wc_order;
	}
}
