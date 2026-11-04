<?php

namespace WPMDB\Anonymization\Config;

class Replacement {

	/**
	 * Mark a WordPress transient as a cache miss after import.
	 *
	 * @return string
	 */
	public static function serialized_false() {
		return 'b:0;';
	}

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
	 * Clear structured data while retaining its JSON-list format.
	 *
	 * @return string
	 */
	public static function empty_json_array() {
		return '[]';
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
