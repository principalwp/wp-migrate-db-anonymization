<?php

namespace WPMDB\Anonymization\Config;

class Replacement {

	/**
	 * Clear structured data while retaining its serialized-array format.
	 *
	 * @return string
	 */
	public static function empty_serialized_array() {
		return 'a:0:{}';
	}

	/**
	 * Clear structured data while retaining its JSON-object format.
	 *
	 * @return string
	 */
	public static function empty_json_object() {
		return '{}';
	}

	/**
	 * Wrap a placeholder in a serialized single-item list, for meta stored as an
	 * array of values, e.g. coupon email restrictions.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function serialized_list( $value ) {
		return serialize( array( $value ) );
	}
}
