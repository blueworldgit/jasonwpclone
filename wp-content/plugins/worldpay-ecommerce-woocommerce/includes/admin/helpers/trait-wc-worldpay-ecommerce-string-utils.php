<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait WC_Worldpay_Ecommerce_String_Utils {

	/**
	 * Limit string for masking.
	 *
	 * @param $value
	 * @param $limit
	 * @param $end
	 *
	 * @return mixed|string
	 */
	public function limit( $value, $limit = 100, $end = '...' ) {
		if ( mb_strwidth( $value, 'UTF-8' ) <= $limit ) {
			return $value;
		}

		return rtrim( mb_strimwidth( $value, 0, $limit, '', 'UTF-8' ) ) . $end;
	}

	/**
	 * Mask string.
	 *
	 * @param $str
	 * @param $character
	 * @param $index
	 * @param $length
	 * @param $encoding
	 *
	 * @return mixed|string
	 */
	public function mask( $str, $character, $index, $length = null, $encoding = 'UTF-8' ) {
		if ( '' === $character ) {
			return $str;
		}

		$segment = mb_substr( $str, $index, $length, $encoding );

		if ( '' === $segment ) {
			return $str;
		}

		$strlen      = mb_strlen( $str, $encoding );
		$start_index = $index;

		if ( $index < 0 ) {
			$start_index = $index < - $strlen ? 0 : $strlen + $index;
		}

		$start       = mb_substr( $str, 0, $start_index, $encoding );
		$segment_len = mb_strlen( $segment, $encoding );
		$end         = mb_substr( $str, $start_index + $segment_len );

		return $start . str_repeat( mb_substr( $character, 0, 1, $encoding ), $segment_len ) . $end;
	}

	/**
	 * Get string length.
	 *
	 * @param $value
	 * @param $encoding
	 *
	 * @return false|int
	 */
	public function length( $value, $encoding = null ) {
		return mb_strlen( $value, $encoding );
	}
}
