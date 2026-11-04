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
	 * Remove Forms credentials from the migrated Elementor document.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function elementor_form_credentials( $value ) {
		$elements = json_decode( $value, false, 512, JSON_THROW_ON_ERROR );
		self::clear_elementor_credentials( $elements );

		return json_encode( $elements, JSON_THROW_ON_ERROR );
	}

	/**
	 * @param array $elements
	 */
	private static function clear_elementor_credentials( array $elements ) {
		foreach ( $elements as $element ) {
			if ( isset( $element->widgetType ) && 'form' === $element->widgetType ) {
				unset(
					$element->settings->mailchimp_api_key,
					$element->settings->activecampaign_api_key,
					$element->settings->convertkit_custom_api_key,
					$element->settings->drip_custom_api_token,
					$element->settings->getresponse_custom_api_key,
					$element->settings->mailerlite_custom_api_key,
					$element->settings->slack_webhook,
					$element->settings->discord_webhook
				);
			}
			if ( isset( $element->elements ) ) {
				self::clear_elementor_credentials( $element->elements );
			}
		}
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
	 * Clear Jetpack feedback while retaining the fields list its JSON reader expects.
	 *
	 * @return string
	 */
	public static function empty_feedback_json() {
		return '{"fields":[]}';
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
