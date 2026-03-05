<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Worldpay_Payment_Token extends WC_Payment_Token_CC {
	/**
	 * @inheritDoc
	 */
	public function get_display_name( $deprecated = '' ) {
		return sprintf(
			/* translators: 1: credit card type 2: last 4 digits 3: expiry month 4: expiry year */
			__( '%5$s%1$s ending in %2$s (expires %3$s/%4$s)', 'woocommerce' ),
			wc_get_credit_card_type_label( $this->get_card_type() ),
			$this->get_last4(),
			$this->get_expiry_month(),
			substr( $this->get_expiry_year(), 2 ),
			$this->is_expired() ? 'Expired - ' : ''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function set_last4( $last4 ) {
		$last4 = strlen( $last4 ) >= 4 ? substr( $last4, - 4 ) : $last4;

		parent::set_last4( $last4 );
	}

	/**
	 * @inheritDoc
	 */
	public function set_card_type( $type ) {
		switch ( $type ) {
			case 'ECMC':
				$type = 'mastercard';
				break;
			case 'CB':
				$type = 'Cartes Bancaires';
				break;
		}
		$this->set_prop( 'card_type', $type );
	}

	/**
	 * Returns the Worldpay's internal identifier for a token.
	 *
	 * @return string Worldpay internal identifier tokenId.
	 */
	public function get_token_id() {
		return $this->get_meta( 'worldpay_token_id', true );
	}

	/**
	 * Sets the Worldpay's internal identifier for a token.
	 *
	 * @param string $token_id Worldpay internal identifier tokenId.
	 */
	public function set_token_id( $token_id ) {
		$this->add_meta_data( 'worldpay_token_id', $token_id );
	}

	/**
	 * Save token to DB.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param string $gateway_id Gateway ID.
	 * @param string $token Payment token.
	 * @param string $token_id Worldpay internal identifier tokenId.
	 * @param string $type Credit card type (mastercard, visa, ...).
	 * @param string $last4 Credit card last four digits.
	 * @param string $year Credit card expiration year.
	 * @param string $month Credit card expiration month.
	 *
	 * @return int
	 */
	public function save_payment_token( $customer_id, $gateway_id, $token, $token_id, $type, $last4, $year, $month ) {
		$wc_token_id = $this->payment_token_exists( $token_id, $customer_id, $gateway_id );
		if ( ! empty( $wc_token_id ) ) {
			return $wc_token_id;
		}

		$this->set_user_id( $customer_id );
		$this->set_gateway_id( $gateway_id );
		$this->set_token( $token );
		$this->set_token_id( $token_id );
		$this->set_card_type( $type );
		$this->set_last4( $last4 );
		$this->set_expiry_year( $year );
		$this->set_expiry_month( $month );

		$this->save();

		return $this->get_id();
	}

	/**
	 * Check if payment token is already saved.
	 *
	 * @param string $token_id Worldpay internal identifier tokenId.
	 * @param int    $customer_id Customer ID.
	 * @param string $gateway_id Gateway ID.
	 *
	 * @return mixed|null
	 */
	public function payment_token_exists( $token_id, $customer_id, $gateway_id = '' ) {
		$wc_stored_tokens = self::get_customer_tokens_ids( $customer_id, $gateway_id );

		return $wc_stored_tokens[ $token_id ] ?? null;
	}

	/**
	 * @param int    $customer_id Customer ID.
	 * @param string $gateway_id Optional Gateway ID for getting tokens for a specific gateway.
	 *
	 * @return array Array of stored Worldpay tokenIds with their corresponding WC id.
	 */
	public static function get_customer_tokens_ids( $customer_id, $gateway_id = '' ) {
		$wc_tokens        = WC_Payment_Tokens::get_customer_tokens( $customer_id, $gateway_id );
		$wc_stored_tokens = array();
		foreach ( $wc_tokens as $wc_token ) {
			$token_meta_id                      = $wc_token->get_meta( 'worldpay_token_id', true );
			$wc_stored_tokens[ $token_meta_id ] = $wc_token->get_id();
		}

		return $wc_stored_tokens;
	}

	/**
	 * @param  int    $customer_id  Customer ID.
	 * @param  string $gateway_id  Optional Gateway ID for getting tokens for a specific gateway.
	 *
	 * @return string Token namespace.
	 */
	public static function get_customer_tokens_namespace( $customer_id, $gateway_id = '' ) {
		$token_namespace = get_user_meta( $customer_id, $gateway_id . '_token_namespace', true );
		if ( empty( $token_namespace ) ) {
			$token_namespace = wp_generate_uuid4();
			add_user_meta( $customer_id, $gateway_id . '_token_namespace', $token_namespace, true );
		}

		return $token_namespace;
	}

	/**
	 * Verify if the token is expired.
	 *
	 * @return bool
	 */
	public function is_expired() {
		$current_year  = (int) date( 'Y' );
		$current_month = (int) date( 'm' );

		$token_expiry_year  = (int) $this->get_expiry_year();
		$token_expiry_month = (int) $this->get_expiry_month();

		if ( $token_expiry_year < $current_year || ( $token_expiry_year === $current_year && $token_expiry_month < $current_month ) ) {
			return true;
		}

		return false;
	}
}
