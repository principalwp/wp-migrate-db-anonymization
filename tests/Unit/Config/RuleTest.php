<?php

namespace WPMDB\Anonymization\Tests\Unit\Config;

use WPMDB\Anonymization\Config\Rule;
use WPMDB\Anonymization\Tests\TestCase;

/**
 * Global-scope style callback used as a string constraint.
 */
function rule_test_constraint_rejects_admin( $row ) {
	return ! ( isset( $row->user_login ) && 'admin' === $row->user_login );
}

class RuleTest extends TestCase {

	/**
	 * @param array  $data
	 * @param string $table
	 * @param string $column
	 *
	 * @return Rule
	 */
	protected function make_rule( array $data, $table = 'usermeta', $column = 'meta_value' ) {
		$rule = new Rule( $table, $column );
		$rule->load( $data );

		return $rule;
	}

	public static function constraint_rejects_editor( $row ) {
		return ! ( isset( $row->user_login ) && 'editor' === $row->user_login );
	}

	public function test_rule_without_constraint_is_always_valid() {
		$rule = $this->make_rule( array( 'fake_data_type' => 'email' ) );

		$this->assertTrue( $rule->valid( $this->row( array() ) ) );
		$this->assertTrue( $rule->valid( $this->row( array( 'meta_key' => 'anything' ) ) ) );
	}

	public function test_column_constraint_matches_row_value() {
		$rule = $this->make_rule(
			array(
				'constraint'     => array( 'meta_key' => 'billing_email' ),
				'fake_data_type' => 'email',
			)
		);

		$this->assertTrue( $rule->valid( $this->row( array( 'meta_key' => 'billing_email' ) ) ) );
		$this->assertFalse( $rule->valid( $this->row( array( 'meta_key' => 'billing_phone' ) ) ) );
	}

	public function test_column_constraint_is_strict() {
		$rule = $this->make_rule( array( 'constraint' => array( 'meta_key' => '1' ) ) );

		$this->assertFalse( $rule->valid( $this->row( array( 'meta_key' => 1 ) ) ) );
		$this->assertTrue( $rule->valid( $this->row( array( 'meta_key' => '1' ) ) ) );
	}

	public function test_column_constraint_passes_when_row_lacks_the_column() {
		$rule = $this->make_rule( array( 'constraint' => array( 'meta_key' => 'billing_email' ) ) );

		$this->assertTrue( $rule->valid( $this->row( array( 'user_login' => 'someone' ) ) ) );
	}

	public function test_function_name_constraint_is_called_with_row() {
		$rule = $this->make_rule( array( 'constraint' => __NAMESPACE__ . '\\rule_test_constraint_rejects_admin' ) );

		$this->assertFalse( $rule->valid( $this->row( array( 'user_login' => 'admin' ) ) ) );
		$this->assertTrue( $rule->valid( $this->row( array( 'user_login' => 'someone' ) ) ) );
	}

	public function test_static_method_string_constraint_is_called_with_row() {
		$rule = $this->make_rule( array( 'constraint' => __CLASS__ . '::constraint_rejects_editor' ) );

		$this->assertFalse( $rule->valid( $this->row( array( 'user_login' => 'editor' ) ) ) );
		$this->assertTrue( $rule->valid( $this->row( array( 'user_login' => 'someone' ) ) ) );
	}

	public function test_bare_method_name_resolves_to_default_constraint_class() {
		add_filter( 'wpmdb_anonymization_user_whitelisted', '__return_true' );
		$rule = $this->make_rule( array( 'constraint' => 'is_not_whitelisted_user' ), 'users', 'user_email' );

		$this->assertFalse( $rule->valid( $this->row( array( 'user_login' => 'someone' ) ) ) );
	}

	public function test_unknown_string_constraint_is_ignored() {
		$rule = $this->make_rule( array( 'constraint' => 'this_constraint_does_not_exist' ) );

		$this->assertTrue( $rule->valid( $this->row( array( 'user_login' => 'someone' ) ) ) );
	}

	public function test_pre_rule_valid_filter_can_reject_a_row() {
		add_filter( 'wpmdb_anonymization_pre_rule_valid', '__return_false' );
		$rule = $this->make_rule( array( 'fake_data_type' => 'email' ) );

		$this->assertFalse( $rule->valid( $this->row( array() ) ) );
	}

	public function test_pre_rule_valid_filter_receives_rule_and_row() {
		$received = array();
		add_filter(
			'wpmdb_anonymization_pre_rule_valid',
			function ( $valid, $rule, $row ) use ( &$received ) {
				$received = array( $valid, $rule, $row );

				return $valid;
			},
			10,
			3
		);

		$rule = $this->make_rule( array( 'fake_data_type' => 'email' ) );
		$row  = $this->row( array( 'meta_key' => 'billing_email' ) );
		$rule->valid( $row );

		$this->assertTrue( $received[0] );
		$this->assertSame( $rule, $received[1] );
		$this->assertSame( $row, $received[2] );
	}

	public function test_pre_rule_valid_filter_returning_true_still_applies_constraint() {
		add_filter( 'wpmdb_anonymization_pre_rule_valid', '__return_true' );
		$rule = $this->make_rule( array( 'constraint' => array( 'meta_key' => 'billing_email' ) ) );

		$this->assertFalse( $rule->valid( $this->row( array( 'meta_key' => 'other' ) ) ) );
	}

	public function test_load_ignores_unknown_keys() {
		$rule = $this->make_rule( array( 'not_a_property' => 'ignored' ) );

		$this->assertFalse( property_exists( $rule, 'not_a_property' ) );
	}

	/**
	 * @dataProvider placeholder_provider
	 */
	public function test_numbered_placeholders( $table, $column, $data, $columns, $expected ) {
		$this->assertSame( $expected, $this->make_rule( $data, $table, $column )->anonymize( $this->row( $columns ) ) );
	}

	public static function placeholder_provider() {
		return array(
			'column label' => array( 'users', 'display_name', array(), array( 'ID' => 7, 'display_name' => 'Jane' ), 'display name 7' ),
			'first column is row ID' => array( 'custom', 'name', array(), array( 'custom_id' => 23, 'user_id' => 7 ), 'name 23' ),
			'user meta parent' => array( 'usermeta', 'meta_value', array(), array( 'umeta_id' => 91, 'user_id' => 7, 'meta_key' => 'first_name' ), 'first name 7' ),
			'post meta parent' => array( 'postmeta', 'meta_value', array(), array( 'meta_id' => 92, 'post_id' => 8, 'meta_key' => '_billing_first_name' ), 'billing first name 8' ),
			'comment meta parent' => array( 'commentmeta', 'meta_value', array(), array( 'meta_id' => 93, 'comment_id' => 9, 'meta_key' => 'akismet_as_submitted' ), 'akismet as submitted 9' ),
			'order meta parent' => array( 'wc_orders_meta', 'meta_value', array(), array( 'id' => 94, 'order_id' => 10, 'meta_key' => '_billing_phone' ), 'billing phone 10' ),
			'GF entry meta parent' => array( 'gf_entry_meta', 'meta_value', array(), array( 'id' => 95, 'form_id' => 3, 'entry_id' => 11, 'meta_key' => '2.3' ), '2.3 11' ),
			'legacy EDD customer meta parent' => array( 'edd_customermeta', 'meta_value', array(), array( 'meta_id' => 96, 'customer_id' => 12, 'meta_key' => 'company_name' ), 'company name 12' ),
			'EDD customer meta parent' => array( 'edd_customermeta', 'meta_value', array(), array( 'meta_id' => 97, 'edd_customer_id' => 13, 'meta_key' => 'phone' ), 'phone 13' ),
			'EDD order meta parent' => array( 'edd_ordermeta', 'meta_value', array(), array( 'meta_id' => 98, 'edd_order_id' => 14, 'meta_key' => '_edd_phone' ), 'edd phone 14' ),
			'WC payment token meta parent' => array( 'woocommerce_payment_tokenmeta', 'meta_value', array(), array( 'meta_id' => 99, 'payment_token_id' => 15, 'meta_key' => 'last4' ), 'last4 15' ),
			'meta key replacement hides key' => array( 'usermeta', 'meta_key', array(), array( 'umeta_id' => 90, 'user_id' => 7, 'meta_key' => 'secret-value' ), 'meta key 7' ),
			'email column' => array( 'users', 'user_email', array(), array( 'ID' => 7 ), 'email7@example.com' ),
			'email meta' => array( 'postmeta', 'meta_value', array(), array( 'meta_id' => 1, 'post_id' => 8, 'meta_key' => '_from_email' ), 'email8@example.com' ),
			'explicit email type' => array( 'gf_entry_meta', 'meta_value', array( 'fake_data_type' => 'email' ), array( 'id' => 1, 'entry_id' => 8, 'meta_key' => '2' ), 'email8@example.com' ),
			'email hash is a label' => array( 'wc_email_unsubscribes', 'email_hash', array(), array( 'id' => 11 ), 'email hash 11' ),
			'URL column' => array( 'users', 'user_url', array(), array( 'ID' => 7 ), 'https://example.com/7' ),
			'referrer column' => array( 'e_submissions', 'referer', array(), array( 'id' => 7 ), 'https://example.com/7' ),
			'explicit domain type' => array( 'edd_license_activations', 'site_name', array( 'fake_data_type' => 'domainName' ), array( 'id' => 11 ), 'https://example.com/11' ),
			'password' => array( 'users', 'user_pass', array( 'fake_data_type' => 'password', 'post_process_function' => 'strtoupper' ), array( 'ID' => 7 ), '' ),
			'post processing' => array( 'usermeta', 'meta_value', array( 'post_process_function' => 'strtoupper' ), array( 'umeta_id' => 1, 'user_id' => 7, 'meta_key' => 'first_name' ), 'FIRST NAME 7' ),
		);
	}
}
