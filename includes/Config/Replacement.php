<?php

namespace WPMDB\Anonymization\Config;

class Replacement {

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
