<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Worldpay\Api\Services\Validators\BaseValidator;

trait WC_Access_Worldpay_Field_Validators {

	/**
	 * Validate live username field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_api_live_username_field( $key, $value ) {
		if ( wc_string_to_bool( $this->get_option( 'is_live_mode' ) ) && empty( trim( $value ) ) ) {
			WC_Admin_Settings::add_error( __( 'The API Live username is mandatory when you enable Live mode.' ) );
			$value = $this->get_option( $key );
		}

		return $value;
	}

	/**
	 * Validate live password field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_api_live_password_field( $key, $value ) {
		$value = $this->replace_mask( $key, $value );
		if ( wc_string_to_bool( $this->get_option( 'is_live_mode' ) ) && empty( trim( $value ) ) ) {
			WC_Admin_Settings::add_error( __( 'The API Live password is mandatory when you enable Live mode.' ) );
			$value = '';
		}

		return $value;
	}

	/**
	 * Validate try username field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_api_try_username_field( $key, $value ) {
		if ( ! wc_string_to_bool( $this->get_option( 'is_live_mode' ) ) && empty( trim( $value ) ) ) {
			WC_Admin_Settings::add_error( __( 'The API Try username is mandatory when you enable Try mode.' ) );
			$value = $this->get_option( $key );
		}

		return $value;
	}

	/**
	 * Validate try password field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_api_try_password_field( $key, $value ) {
		$value = $this->replace_mask( $key, $value );
		if ( ! wc_string_to_bool( $this->get_option( 'is_live_mode' ) ) && empty( trim( $value ) ) ) {
			WC_Admin_Settings::add_error( __( 'The API Try password is mandatory when you enable Try mode.' ) );
			$value = '';
		}

		return $value;
	}

	/**
	 * Validate merchant entity field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_merchant_entity_field( $key, $value ) {
		$value = $this->replace_mask( $key, $value, true );
		if ( empty( trim( $value ) ) || strlen( $value ) > 32 ) {
			WC_Admin_Settings::add_error( __( 'The merchant entity is mandatory. Maximum of 32 characters.' ) );
			$value = $this->get_option( $key );
		}

		return $value;
	}

	/**
	 * Validate merchant narrative field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_merchant_narrative_field( $key, $value ) {
		if ( empty( trim( $value ) ) || strlen( $value ) > 24 || ! BaseValidator::hasValidMerchantNarrative( $value ) ) {
			WC_Admin_Settings::add_error( __( 'The merchant narrative is mandatory. Maximum of 24 characters. Valid characters are: A-Z, a-z, 0-9, hyphen(-), full stop(.), commas(,), space.' ) );
			$value = $this->get_option( $key );
		}

		return $value;
	}

	/**
	 * Validate merchant description field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_merchant_description_field( $key, $value ) {
		if ( ! empty( trim( $value ) ) && strlen( $value ) > 128 ) {
			WC_Admin_Settings::add_error( __( 'The merchant description must have a maximum of 128 characters.' ) );
			$value = $this->get_option( $key );
		}

		return $value;
	}

	/**
	 * Validate merchant try checkout id field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_merchant_try_checkout_id_field( $key, $value ) {
		if ( empty( trim( $value ) ) ) {
			WC_Admin_Settings::add_error( __( 'The merchant try checkout id is mandatory.' ) );
			$value = $this->get_option( $key );
		}

		return $value;
	}

	/**
	 * Validate merchant live checkout id field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|string
	 */
	public function validate_app_merchant_live_checkout_id_field( $key, $value ) {
		if ( wc_string_to_bool( $this->get_option( 'is_live_mode' ) ) && empty( trim( $value ) ) ) {
			WC_Admin_Settings::add_error( __( 'The merchant live checkout id is mandatory.' ) );
			$value = $this->get_option( $key );
		}

		return $value;
	}

	/**
	 * Validate merchant card brands.
	 *
	 * @param $key
	 * @param $value
	 * @return mixed|string
	 */
	public function validate_app_card_brands_field( $key, $value ) {
		if ( empty( $value ) ) {
			WC_Admin_Settings::add_error( __( 'The merchant card brands field is mandatory.' ) );
			$value = $this->get_option( $key );
		}

		return $value;
	}

	/**
	 * Replace mask.
	 *
	 * @param $key
	 * @param $value
	 * @param  bool $partial
	 *
	 * @return mixed|string
	 */
	protected function replace_mask( $key, $value, bool $partial = false ) {
		$configured_value = $this->get_option( $key );
		$pattern          = $partial ? '/^\*+[^*]{4}$/' : '/^[*]+$/';
		if ( ! empty( $configured_value ) && preg_match( $pattern, $value )
			&& $value != $configured_value ) {
			$value = $configured_value;
		}

		return $value;
	}
}
