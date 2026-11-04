<?php


namespace WPMDB\Anonymization\Config;

class Rule {

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var string
	 */
	protected $column;

	/**
	 * @var
	 */
	protected $fake_data_type;

	/**
	 * @var
	 */
	protected $post_process_function;

	/**
	 * @var callable
	 */
	protected $replacement_function;

	/**
	 * @var
	 */
	protected $constraint;

	/**
	 * Rule constructor.
	 *
	 * @param string $table
	 * @param string $column
	 */
	public function __construct( $table, $column ) {
		$this->table  = $table;
		$this->column = $column;
	}

	/**
	 * @param array $data
	 */
	public function load( $data = array() ) {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Check a row is valid for the config rules constraint.
	 *
	 * @param object $row
	 *
	 * @return bool
	 */
	public function valid( $row ) {
		$pre = apply_filters( 'wpmdb_anonymization_pre_rule_valid', true, $this, $row );
		if ( ! $pre ) {
			return false;
		}

		if ( empty( $this->constraint ) ) {
			return true;
		}

		if ( ! $this->valid_constraint_callback( $row ) )  {
			return false;
		}

		if ( ! $this->valid_column_constraint( $row ) ) {
			return false;
		}

		return true;
	}

	protected function valid_constraint_callback( $row ) {
		if ( ! is_string( $this->constraint ) ) {
			return true;
		}

		$default_constraint_class = 'WPMDB\\Anonymization\\Config\\Constraint';

		$function = $this->constraint;
		if ( ! is_callable( $function ) ) {
			if ( ! method_exists( $default_constraint_class, $this->constraint ) ) {
				return true;
			}
			$function = array( $default_constraint_class, $this->constraint );
		}

		return call_user_func( $function, $row );
	}


	/**
	 * Check a row against a column constraint, e.g.
	 *
	 *     array( 'meta_key' => '_billing_email' )                         // Exact match
	 *     array( 'meta_key' => array( 'in' => array( 'a', 'b' ) ) )        // One of a list
	 *     array( 'meta_key' => array( 'a', 'b' ) )                         // Shorthand for 'in'
	 *     array( 'meta_key' => array( 'regex' => '/^_billing_/' ) )        // Regular expression
	 *
	 * When several columns are given, all of them must match. A column that is
	 * not present on the row is ignored.
	 *
	 * @param object $row
	 *
	 * @return bool
	 */
	protected function valid_column_constraint( $row ) {
		if ( ! is_array( $this->constraint ) ) {
			return true;
		}

		foreach ( $this->constraint as $column => $expected ) {
			if ( ! isset( $row->{$column} ) ) {
				continue;
			}

			if ( ! Constraint::column_matches( $row->{$column}, $expected ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether the rule transforms a complete value instead of a placeholder.
	 *
	 * @return bool
	 */
	public function replaces_whole_value() {
		return (bool) $this->replacement_function;
	}

	/**
	 * Transform the current migrated value, or generate its row's placeholder.
	 *
	 * @param object $row Original row used for labels and record IDs.
	 * @param mixed  $value Value after WP Migrate's search/replace.
	 * @return mixed
	 */
	public function anonymize_value( $row, $value ) {
		if ( $this->replaces_whole_value() ) {
			return call_user_func( $this->replacement_function, $value );
		}

		return $this->anonymize( $row );
	}

	/**
	 * Replace a value using the full original row in database column order.
	 * Meta rows carry their owning record's ID; other rows start with their ID.
	 *
	 * @param object $row
	 *
	 * @return mixed
	 */
	public function anonymize( $row ) {
		if ( 'password' === $this->fake_data_type ) {
			return '';
		}

		$columns = get_object_vars( $row );
		$id      = reset( $columns );
		$field   = $this->column;

		if ( isset( $row->meta_key ) ) {
			$parents = array_intersect_key( $columns, array_flip( array( 'user_id', 'post_id', 'comment_id', 'order_id', 'entry_id', 'customer_id', 'edd_customer_id', 'edd_order_id', 'payment_token_id' ) ) );
			$id      = reset( $parents );
			$field   = 'meta_key' === $this->column ? $this->column : $row->meta_key;
		}

		if ( in_array( $this->table, array( 'gf_draft_submissions', 'rg_incomplete_submissions' ), true ) ) {
			// The first column is a live resume token. Use 52 hash bits, exactly
			// representable even on 32-bit PHP, and keep `uuid N` within CHAR(32).
			$id = sprintf( '%.0f', hexdec( substr( hash( 'sha256', $id ), 0, 13 ) ) );
		}

		if ( 'email' === $this->fake_data_type || preg_match( '/email$/i', $field ) ) {
			$value = 'email' . $id . '@example.com';
		} elseif ( in_array( $this->fake_data_type, array( 'url', 'domainName' ), true ) || preg_match( '/url$/i', $field ) || in_array( $field, array( 'referer', 'referrer' ), true ) ) {
			$value = 'https://example.com/' . $id;
		} else {
			$value = str_replace( '_', ' ', ltrim( $field, '_' ) ) . ' ' . $id;
		}

		if ( $this->post_process_function ) {
			$value = call_user_func( $this->post_process_function, $value );
		}

		return $value;
	}

}
