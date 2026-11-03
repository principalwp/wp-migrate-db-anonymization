<?php

namespace WPMDB\Anonymization\Tests\Unit;

use WPMDB\Anonymization\Migration;
use WPMDB\Anonymization\Tests\TestCase;

class MigrationTest extends TestCase {

	protected function config_data() {
		return array(
			'users'    => array(
				'user_email' => array(
					'fake_data_type' => 'email',
				),
			),
			'usermeta' => array(
				'meta_value' => array(
					array(
						'constraint'     => array( 'umeta_id' => 90, 'user_id' => 7, 'meta_key' => 'first_name' ),
					),
				),
			),
		);
	}

	/**
	 * Stand-in for WP Migrate's replace object passed to the filter.
	 *
	 * @return object
	 */
	protected function replace( $intent, $table, $column, $row ) {
		return new class( $intent, $table, $column, $row ) {
			private $intent;
			private $table;
			private $column;
			private $row;

			public function __construct( $intent, $table, $column, $row ) {
				$this->intent = $intent;
				$this->table  = $table;
				$this->column = $column;
				$this->row    = $row;
			}

			public function get_intent() {
				return $this->intent;
			}

			public function get_table() {
				return $this->table;
			}

			public function get_column() {
				return $this->column;
			}

			public function get_row() {
				return $this->row;
			}
		};
	}

	protected function make_migration() {
		return new Migration( $this->make_config( $this->config_data() ) );
	}

	public function test_register_hooks_into_wp_migrate() {
		$this->make_migration()->register();

		$this->assertTrue( has_filter( 'wpmdb_after_replace_custom_data' ) );
	}

	/**
	 * @dataProvider intent_provider
	 */
	public function test_anonymizes_for_export_push_and_pull( $intent, $anonymized ) {
		$row     = $this->row( array( 'umeta_id' => 90, 'user_id' => 7, 'meta_key' => 'first_name' ) );
		$replace = $this->replace( $intent, 'wp_usermeta', 'meta_value', $row );

		$result = $this->make_migration()->hook_wpmdb_after_replace_custom_data( 'Jane', false, $replace );

		$this->assertSame( $anonymized ? 'first name 7' : 'Jane', $result );
	}

	public static function intent_provider() {
		return array(
			'export'          => array( 'savefile', true ),
			'pull'            => array( 'pull', true ),
			'push'            => array( 'push', true ),
			'find & replace'  => array( 'find_replace', false ),
			'import'          => array( 'import', false ),
		);
	}

	public function test_handles_migration_temp_tables() {
		$replace = $this->replace( 'pull', '_mig_wp_usermeta', 'meta_value', $this->row( array( 'umeta_id' => 90, 'user_id' => 7, 'meta_key' => 'first_name' ) ) );

		$this->assertSame( 'first name 7', $this->make_migration()->hook_wpmdb_after_replace_custom_data( 'Jane', false, $replace ) );
	}

	public function test_empty_data_is_left_alone() {
		$replace = $this->replace( 'pull', 'wp_usermeta', 'meta_value', $this->row( array( 'umeta_id' => 90, 'user_id' => 7, 'meta_key' => 'first_name' ) ) );

		$this->assertSame( '', $this->make_migration()->hook_wpmdb_after_replace_custom_data( '', false, $replace ) );
	}

	public function test_unconfigured_table_or_column_is_left_alone() {
		$migration = $this->make_migration();

		$posts = $this->replace( 'pull', 'wp_posts', 'post_title', $this->row( array() ) );
		$this->assertSame( 'Hello', $migration->hook_wpmdb_after_replace_custom_data( 'Hello', false, $posts ) );

		$login = $this->replace( 'pull', 'wp_users', 'user_login', $this->row( array( 'ID' => 7, 'user_login' => 'jane' ) ) );
		$this->assertSame( 'jane', $migration->hook_wpmdb_after_replace_custom_data( 'jane', false, $login ) );
	}

	public function test_row_not_matching_any_rule_is_left_alone() {
		$replace = $this->replace( 'pull', 'wp_usermeta', 'meta_value', $this->row( array( 'meta_key' => 'session_tokens' ) ) );

		$this->assertSame( 'a:0:{}', $this->make_migration()->hook_wpmdb_after_replace_custom_data( 'a:0:{}', false, $replace ) );
	}

	public function test_single_rule_column_is_anonymized() {
		$replace = $this->replace( 'savefile', 'wp_users', 'user_email', $this->row( array( 'ID' => 7, 'user_login' => 'jane' ) ) );

		$result = $this->make_migration()->hook_wpmdb_after_replace_custom_data( 'jane@example.com', false, $replace );

		$this->assertSame( 'email7@example.com', $result );
	}

	public static function structured_field_provider() {
		$serialized = serialize( array( 'private' => 'jane@example.org' ) );
		$json       = '{"private":"jane@example.org"}';

		return array(
			'login sessions'       => array( 'wordpress', 'usermeta', 'meta_value', array( 'umeta_id' => 90, 'user_id' => 7, 'meta_key' => 'session_tokens' ), $serialized, 'a:0:{}' ),
			'events location'      => array( 'wordpress', 'usermeta', 'meta_value', array( 'umeta_id' => 91, 'user_id' => 7, 'meta_key' => 'community-events-location' ), $serialized, 'a:0:{}' ),
			'pending email'        => array( 'wordpress', 'usermeta', 'meta_value', array( 'umeta_id' => 92, 'user_id' => 7, 'meta_key' => '_new_email' ), serialize( array( 'hash' => 'private-hash', 'newemail' => 'jane@example.org' ) ), 'a:0:{}' ),
			'application passwords' => array( 'credentials', 'usermeta', 'meta_value', array( 'umeta_id' => 93, 'user_id' => 7, 'meta_key' => '_application_passwords' ), $serialized, 'a:0:{}' ),
			'Two Factor backups'   => array( 'credentials', 'usermeta', 'meta_value', array( 'umeta_id' => 94, 'user_id' => 7, 'meta_key' => '_two_factor_backup_codes' ), $serialized, 'a:0:{}' ),
			'OAuth nonces'         => array( 'credentials', 'woocommerce_api_keys', 'nonces', array( 'key_id' => 8 ), $serialized, 'a:0:{}' ),
			'EDD customer address' => array( 'easy-digital-downloads', 'usermeta', 'meta_value', array( 'umeta_id' => 95, 'user_id' => 7, 'meta_key' => '_edd_user_address' ), $serialized, 'a:0:{}' ),
			'WooCommerce session'  => array( 'woocommerce', 'woocommerce_sessions', 'session_value', array( 'session_id' => 9 ), $serialized, 'a:0:{}' ),
			'GF draft'             => array( 'gravity-forms', 'gf_draft_submissions', 'submission', array( 'uuid' => '0123456789abcdef0123456789abcdef' ), $json, '{}' ),
			'legacy GF draft'      => array( 'gravity-forms', 'rg_incomplete_submissions', 'submission', array( 'uuid' => 'fedcba9876543210fedcba9876543210' ), $json, '{}' ),
			'Redirection 404'      => array( 'redirection', 'redirection_404', 'request_data', array( 'id' => 10 ), $json, '{}' ),
			'Redirection log'      => array( 'redirection', 'redirection_logs', 'request_data', array( 'id' => 11 ), $json, '{}' ),
			'EDD payment source'   => array( 'easy-digital-downloads', 'edd_payment_tokens', 'payment_source', array( 'id' => 12 ), $json, '{}' ),
		);
	}

	/**
	 * @dataProvider structured_field_provider
	 */
	public function test_shipped_rules_clear_structured_fields_to_their_original_empty_containers( $file, $table, $column, $columns, $before, $expected ) {
		$migration = new Migration( $this->make_config( include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/' . $file . '.php' ) );
		$row       = $this->row( array_merge( $columns, array( $column => $before ) ) );

		foreach ( array( 'savefile', 'push', 'pull' ) as $intent ) {
			$replace = $this->replace( $intent, 'wp_' . $table, $column, $row );

			$this->assertSame( $expected, $migration->hook_wpmdb_after_replace_custom_data( $before, false, $replace ) );
		}
	}

	public function test_shipped_totp_secret_is_cleared_in_exports_pushes_and_pulls() {
		$migration = new Migration( $this->make_config( include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/credentials.php' ) );
		$row       = $this->row( array( 'umeta_id' => 96, 'user_id' => 7, 'meta_key' => '_two_factor_totp_key', 'meta_value' => str_repeat( 'A', 32 ) ) );

		foreach ( array( 'savefile', 'push', 'pull' ) as $intent ) {
			$replace = $this->replace( $intent, 'wp_usermeta', 'meta_value', $row );

			$this->assertSame( '', $migration->hook_wpmdb_after_replace_custom_data( $row->meta_value, false, $replace ) );
		}
	}

	public function test_shipped_rules_transform_full_rows_and_keep_order_details() {
		$data = array_merge_recursive(
			include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/wordpress.php',
			include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/woocommerce.php'
		);
		$migration = new Migration( $this->make_config( $data ) );
		$rows = array(
			array(
				'users',
				array( 'ID' => 7, 'user_login' => 'jane', 'user_pass' => 'private-hash', 'user_email' => 'jane@example.org', 'display_name' => 'Jane Buyer', 'user_url' => 'https://private.example.org', 'user_registered' => '2026-10-01 12:00:00' ),
				array( 'ID' => 7, 'user_login' => 'user login 7', 'user_pass' => '', 'user_email' => 'email7@example.com', 'display_name' => 'display name 7', 'user_url' => 'https://example.com/7', 'user_registered' => '2026-10-01 12:00:00' ),
			),
			array(
				'usermeta',
				array( 'umeta_id' => 91, 'user_id' => 7, 'meta_key' => 'billing_first_name', 'meta_value' => 'Jane' ),
				array( 'umeta_id' => 91, 'user_id' => 7, 'meta_key' => 'billing_first_name', 'meta_value' => 'billing first name 7' ),
			),
			array(
				'comments',
				array( 'comment_ID' => 83, 'comment_post_ID' => 42, 'comment_author' => 'Jane Buyer', 'comment_author_email' => 'jane@example.org', 'comment_author_IP' => '203.0.113.7', 'comment_type' => 'order_note', 'comment_content' => 'Call Jane on 555-0142', 'comment_approved' => '1' ),
				array( 'comment_ID' => 83, 'comment_post_ID' => 42, 'comment_author' => 'comment author 83', 'comment_author_email' => 'email83@example.com', 'comment_author_IP' => 'comment author IP 83', 'comment_type' => 'order_note', 'comment_content' => 'comment content 83', 'comment_approved' => '1' ),
			),
		);
		foreach ( $rows as list( $table, $before, $expected ) ) {
			$row = $this->row( $before );
			$after = array();
			foreach ( $before as $column => $value ) {
				$replace = $this->replace( 'pull', 'wp_' . $table, $column, $row );
				$after[ $column ] = $migration->hook_wpmdb_after_replace_custom_data( $value, false, $replace );
			}
			$this->assertSame( $expected, $after );
		}
	}
}
