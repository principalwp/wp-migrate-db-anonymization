<?php

namespace WPMDB\Anonymization\Tests\Unit;

use WPMDB\Anonymization\Config\Rule;
use WPMDB\Anonymization\Tests\TestCase;

/**
 * Sanity checks for every rule shipped in config/*.php.
 *
 * New config files are picked up automatically.
 */
class ShippedConfigTest extends TestCase {

	/**
	 * Keys a rule may define, matching the properties Rule::load() accepts.
	 *
	 * @var string[]
	 */
	protected static $rule_keys = array( 'fake_data_type', 'post_process_function', 'replacement_function', 'constraint' );

	/**
	 * One data set per rule: file, table, column, rule index and rule data.
	 *
	 * @return array
	 */
	public static function rule_provider() {
		$sets = array();
		foreach ( self::shipped_config_files() as $file => $tables ) {
			foreach ( $tables as $table => $columns ) {
				foreach ( $columns as $column => $rules ) {
					if ( ! isset( $rules[0] ) || ! is_array( $rules[0] ) ) {
						$rules = array( $rules );
					}

					foreach ( $rules as $index => $rule ) {
						$sets[ "{$file} {$table}.{$column}[{$index}]" ] = array( $table, $column, $rule );
					}
				}
			}
		}

		return $sets;
	}

	public function test_config_files_exist_and_return_arrays() {
		$files = self::shipped_config_files();

		$this->assertNotEmpty( $files );
		foreach ( $files as $file => $data ) {
			$this->assertIsArray( $data, "{$file} returns an array." );
			$this->assertNotEmpty( $data, "{$file} is not empty." );
		}
	}

	/**
	 * @dataProvider rule_provider
	 */
	public function test_rule_only_uses_known_keys( $table, $column, array $rule ) {
		$this->assertSame( array(), array_diff( array_keys( $rule ), self::$rule_keys ) );
	}

	/**
	 * @dataProvider rule_provider
	 */
	public function test_special_format_is_email_url_or_password( $table, $column, array $rule ) {
		if ( isset( $rule['fake_data_type'] ) ) {
			$this->assertContains( $rule['fake_data_type'], array( 'email', 'url', 'domainName', 'password' ) );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

	/**
	 * @dataProvider rule_provider
	 */
	public function test_callbacks_are_callable( $table, $column, array $rule ) {
		if ( isset( $rule['replacement_function'] ) ) {
			$this->assertTrue( is_callable( $rule['replacement_function'] ), 'replacement_function is callable.' );
		}
		if ( isset( $rule['post_process_function'] ) ) {
			$this->assertTrue( is_callable( $rule['post_process_function'] ), 'post_process_function is callable.' );
		}

		if ( isset( $rule['constraint'] ) && is_string( $rule['constraint'] ) ) {
			$this->assertTrue( is_callable( $rule['constraint'] ), 'String constraint is callable.' );
		}

		if ( isset( $rule['constraint'] ) && is_array( $rule['constraint'] ) ) {
			// Every column listed must match (see Rule::valid_column_constraint()).
			$this->assertNotEmpty( $rule['constraint'], 'Column constraints name at least one column.' );
			foreach ( $rule['constraint'] as $constraint_column => $expected ) {
				$this->assertIsString( $constraint_column, 'Column constraints are keyed by column name.' );
				if ( is_array( $expected ) && isset( $expected['regex'] ) ) {
					$this->assertNotFalse( @preg_match( $expected['regex'], '' ), "Regex for {$constraint_column} compiles." );
				}
			}
		}

		$this->addToAssertionCount( 1 );
	}

	/**
	 * @dataProvider rule_provider
	 */
	public function test_rule_produces_a_database_string( $table, $column, array $rule ) {
		$object = new Rule( $table, $column );
		$object->load( $rule );

		$input = '[]';
		if ( isset( $rule['replacement_function'] ) && 'WPMDB\\Anonymization\\Config\\Replacement::cf7_credentials' === $rule['replacement_function'] ) {
			$input = serialize( array( 'version' => '6.2.4.1' ) );
		}
		$value = $object->anonymize_value( $this->row( array( 'ID' => 7 ) ), $input );

		$this->assertIsString( $value );
		if ( isset( $rule['fake_data_type'] ) && 'password' === $rule['fake_data_type'] ) {
			$this->assertSame( '', $value );
		}
	}
}
