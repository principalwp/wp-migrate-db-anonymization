<?php

namespace WPMDB\Anonymization;

use WPMDB\Anonymization\Config\Config;
use WPMDB\Anonymization\Config\Rule;

/**
 * Anonymizes a single value for a table/column/row using the config rules.
 */
class Anonymizer {

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * Rule objects per table and column, built once.
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Anonymize a value. Returns the value unchanged when it is empty or no
	 * rule applies to the row.
	 *
	 * @param string $table  Unprefixed table name, as used in the config.
	 * @param string $column
	 * @param object $row    The full original row, used by rule constraints.
	 * @param mixed  $value  The original value.
	 *
	 * @return mixed
	 */
	public function anonymize_value( $table, $column, $row, $value ) {
		if ( self::is_empty( $value ) ) {
			return $value;
		}

		$rule = $this->matching_rule( $table, $column, $row );
		if ( null === $rule ) {
			return $value;
		}

		return $rule->anonymize( $row );
	}

	/**
	 * The first rule for the table/column that is valid for the row.
	 *
	 * @param string $table
	 * @param string $column
	 * @param object $row
	 *
	 * @return Rule|null
	 */
	public function matching_rule( $table, $column, $row ) {
		foreach ( $this->rules( $table, $column ) as $rule ) {
			if ( $rule->valid( $row ) ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * @param string $table
	 * @param string $column
	 *
	 * @return Rule[]
	 */
	public function rules( $table, $column ) {
		if ( ! isset( $this->rules[ $table ][ $column ] ) ) {
			$this->rules[ $table ][ $column ] = $this->config->has_column( $table, $column ) ? $this->config->rules( $table, $column ) : array();
		}

		return $this->rules[ $table ][ $column ];
	}

	/**
	 * Empty originals stay empty: never invent data where none existed.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function is_empty( $value ) {
		return null === $value || '' === $value;
	}

}
