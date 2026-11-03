<?php

namespace WPMDB\Anonymization\Tests\Unit;

use WPMDB\Anonymization\Anonymizer;
use WPMDB\Anonymization\Config\Loader;
use WPMDB\Anonymization\Tests\Doubles\TablesWpdb;
use WPMDB\Anonymization\Tests\TestCase;

/**
 * Behaviour of the Gravity Forms, Redirection and credentials rules.
 */
class FormsLogsCredentialsRulesTest extends TestCase {

	/**
	 * @param string $file Config file name without extension.
	 *
	 * @return array
	 */
	protected function config_file( $file ) {
		return include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/' . $file . '.php';
	}

	/**
	 * The rules from credentials.php and credentials-easy-digital-downloads.php,
	 * merged by the Loader as on a site with EDD data.
	 *
	 * @return array
	 */
	protected function credentials_config() {
		add_filter(
			'wpmdb_anonymization_load_config_file',
			function ( $load, $name ) {
				return 0 === strpos( $name, 'credentials' ) ? $load : false;
			},
			10,
			2
		);

		return ( new Loader( WPMDB_ANONYMIZATION_PLUGIN_FILE ) )->load( new TablesWpdb( array( 'edd_orders' ) ) );
	}

	/**
	 * @param array $data
	 *
	 * @return Anonymizer
	 */
	protected function make_anonymizer( array $data ) {
		return new Anonymizer( $this->make_config( $data ) );
	}

	/**
	 * @return array
	 */
	public static function gravity_forms_field_key_provider() {
		return array(
			'field'          => array( '1', true ),
			'multi-digit'    => array( '12', true ),
			'sub-input'      => array( '2.3', true ),
			'add-on status'  => array( 'gravityformsmailchimp_is_fulfilled', false ),
			'partial entry'  => array( 'partial_entry_id', false ),
			'not a field ID' => array( '1.2.3', false ),
			'trailing text'  => array( '1a', false ),
		);
	}

	/**
	 * @dataProvider gravity_forms_field_key_provider
	 */
	public function test_gravity_forms_only_replaces_field_values( $meta_key, $replaced ) {
		$anonymizer = $this->make_anonymizer( $this->config_file( 'gravity-forms' ) );
		$row        = $this->row(
			array(
				'id'         => 90,
				'form_id'    => '3',
				'entry_id'   => '10',
				'meta_key'   => $meta_key,
				'meta_value' => 'jane@example.org',
			)
		);

		$value = $anonymizer->anonymize_value( 'gf_entry_meta', 'meta_value', $row, $row->meta_value );

		if ( $replaced ) {
			$this->assertSame( $meta_key . ' 10', $value );
		} else {
			$this->assertSame( 'jane@example.org', $value );
		}
	}

	public function test_gravity_forms_field_rule_can_be_overridden_per_form() {
		// The example from the README.
		add_filter(
			'wpmdb_anonymization_config',
			function ( $config ) {
				array_unshift(
					$config['gf_entry_meta']['meta_value'],
					array(
						'constraint'     => array(
							'form_id'  => '3',
							'meta_key' => '2',
						),
						'fake_data_type' => 'email',
					)
				);

				return $config;
			}
		);

		$loader     = new Loader( WPMDB_ANONYMIZATION_PLUGIN_FILE );
		$anonymizer = $this->make_anonymizer( $loader->load( new TablesWpdb( array( 'gf_entry' ) ) ) );

		$form_3 = $this->row( array( 'id' => 90, 'entry_id' => 10, 'form_id' => '3', 'meta_key' => '2', 'meta_value' => 'jane@example.org' ) );
		$form_4 = $this->row( array( 'id' => 91, 'entry_id' => 11, 'form_id' => '4', 'meta_key' => '2', 'meta_value' => 'jane@example.org' ) );

		$this->assertSame( 'email10@example.com', $anonymizer->anonymize_value( 'gf_entry_meta', 'meta_value', $form_3, $form_3->meta_value ) );
		$this->assertSame( '2 11', $anonymizer->anonymize_value( 'gf_entry_meta', 'meta_value', $form_4, $form_4->meta_value ) );
	}

	public function test_gravity_forms_field_can_be_kept_per_form() {
		// The example from the README.
		add_filter(
			'wpmdb_anonymization_pre_rule_valid',
			function ( $valid, $rule, $row ) {
				if ( isset( $row->entry_id, $row->form_id, $row->meta_key ) && '3' === (string) $row->form_id && '7' === $row->meta_key ) {
					return false;
				}

				return $valid;
			},
			10,
			3
		);

		$anonymizer = $this->make_anonymizer( $this->config_file( 'gravity-forms' ) );
		$kept       = $this->row( array( 'entry_id' => '10', 'form_id' => '3', 'meta_key' => '7', 'meta_value' => 'Large' ) );
		$replaced   = $this->row( array( 'entry_id' => '11', 'form_id' => '4', 'meta_key' => '7', 'meta_value' => 'Large' ) );

		$this->assertSame( 'Large', $anonymizer->anonymize_value( 'gf_entry_meta', 'meta_value', $kept, $kept->meta_value ) );
		$this->assertNotSame( 'Large', $anonymizer->anonymize_value( 'gf_entry_meta', 'meta_value', $replaced, $replaced->meta_value ) );
	}

	public function test_gravity_forms_draft_submission_becomes_an_empty_json_object() {
		$anonymizer = $this->make_anonymizer( $this->config_file( 'gravity-forms' ) );
		$row        = $this->row( array( 'uuid' => 'draft-7', 'submission' => '{"submitted_values":{"1":"Jane"}}' ) );

		foreach ( array( 'gf_draft_submissions', 'rg_incomplete_submissions' ) as $table ) {
			$this->assertSame( '{}', $anonymizer->anonymize_value( $table, 'submission', $row, $row->submission ) );
		}
	}

	public function test_gravity_forms_draft_uuid_is_replaced_with_a_placeholder() {
		$data       = $this->config_file( 'gravity-forms' );
		$anonymizer = $this->make_anonymizer( $data );
		// Same shape as GFFormsModel::get_uuid(): 32 lowercase hex characters.
		$row = $this->row( array( 'uuid' => '0123456789abcdef0123456789abcdef', 'email' => 'jane@example.org', 'source_url' => 'https://private.example.org', 'submission' => '{"1":"Jane"}' ) );
		$other = $this->row( array( 'uuid' => 'fedcba9876543210fedcba9876543210' ) );

		foreach ( array( 'gf_draft_submissions', 'rg_incomplete_submissions' ) as $table ) {
			$this->assertArrayHasKey( 'uuid', $data[ $table ], "{$table}.uuid has a rule." );

			$value = $anonymizer->anonymize_value( $table, 'uuid', $row, $row->uuid );

			$this->assertSame( 'uuid 1102929687319678', $value );
			$this->assertLessThanOrEqual( 32, strlen( $value ) );
			$this->assertStringNotContainsString( $row->uuid, $value );
			$this->assertSame( 'uuid 1330858607208381', $anonymizer->anonymize_value( $table, 'uuid', $other, $other->uuid ) );
			$this->assertSame( 'email1102929687319678@example.com', $anonymizer->anonymize_value( $table, 'email', $row, $row->email ) );
			$this->assertSame( 'https://example.com/1102929687319678', $anonymizer->anonymize_value( $table, 'source_url', $row, $row->source_url ) );
			$this->assertSame( '{}', $anonymizer->anonymize_value( $table, 'submission', $row, $row->submission ) );
		}
	}

	public function test_redirection_keeps_the_url_and_replaces_request_data() {
		$data       = $this->config_file( 'redirection' );
		$anonymizer = $this->make_anonymizer( $data );
		$row        = $this->row(
			array(
				'id'           => 7,
				'url'          => '/old-page',
				'request_data' => '{"headers":{"X-Forwarded-For":"203.0.113.9"}}',
			)
		);

		foreach ( array( 'redirection_404', 'redirection_logs' ) as $table ) {
			$this->assertArrayNotHasKey( 'url', $data[ $table ] );
			$this->assertArrayNotHasKey( 'domain', $data[ $table ] );
			$this->assertSame( '{}', $anonymizer->anonymize_value( $table, 'request_data', $row, $row->request_data ) );
		}
	}

	/**
	 * A 32-character hex value shaped like an EDD key, built at runtime so it
	 * isn't a hard-coded credential.
	 *
	 * @return string
	 */
	private static function sample_key() {
		return str_repeat( implode( '', range( 0, 9 ) ) . 'abcdef', 2 );
	}

	public function test_edd_api_keys_stored_as_meta_keys_are_replaced() {
		$anonymizer = $this->make_anonymizer( $this->config_file( 'credentials-easy-digital-downloads' ) );
		$stored_key = self::sample_key();
		$row        = $this->row(
			array(
				'umeta_id'   => 90,
				'user_id'    => 7,
				'meta_key'   => $stored_key,
				'meta_value' => 'edd_user_secret_key',
			)
		);

		$key = $anonymizer->anonymize_value( 'usermeta', 'meta_key', $row, $row->meta_key );
		$this->assertSame( 'meta key 7', $key );
		$this->assertNotSame( $stored_key, $key );
		$this->assertSame( 'edd_user_secret_key', $anonymizer->anonymize_value( 'usermeta', 'meta_value', $row, $row->meta_value ), 'The value marks the row as a secret key, so it is kept.' );

		$other = $this->row( array( 'meta_key' => 'first_name', 'meta_value' => 'Jane' ) );
		$this->assertSame( 'first_name', $anonymizer->anonymize_value( 'usermeta', 'meta_key', $other, $other->meta_key ) );
	}

	public function test_edd_api_keys_from_before_2_4_are_replaced() {
		$anonymizer = $this->make_anonymizer( $this->config_file( 'credentials-easy-digital-downloads' ) );
		$row        = $this->row(
			array(
				'umeta_id'   => 90,
				'user_id'    => 7,
				'meta_key'   => 'edd_user_public_key',
				'meta_value' => self::sample_key(),
			)
		);

		$this->assertSame( 'edd_user_public_key', $anonymizer->anonymize_value( 'usermeta', 'meta_key', $row, $row->meta_key ) );
		$this->assertSame( 'edd user public key 7', $anonymizer->anonymize_value( 'usermeta', 'meta_value', $row, $row->meta_value ) );
	}

	public function test_edd_api_request_log_keeps_public_requests() {
		$anonymizer = $this->make_anonymizer( $this->config_file( 'credentials-easy-digital-downloads' ) );
		$public     = $this->row( array( 'id' => 7, 'api_key' => 'public', 'token' => 'public' ) );
		$keyed      = $this->row( array( 'id' => 7, 'api_key' => self::sample_key(), 'token' => strrev( self::sample_key() ) ) );

		foreach ( array( 'api_key', 'token' ) as $column ) {
			$this->assertSame( 'public', $anonymizer->anonymize_value( 'edd_logs_api_requests', $column, $public, 'public' ) );

			$value = $anonymizer->anonymize_value( 'edd_logs_api_requests', $column, $keyed, $keyed->{$column} );
			$this->assertSame( str_replace( '_', ' ', $column ) . ' 7', $value );
			$this->assertNotSame( $keyed->{$column}, $value );
		}
	}

	/**
	 * @return array
	 */
	public static function edd_2_log_meta_key_provider() {
		return array(
			'key'   => array( '_edd_log_key' ),
			'token' => array( '_edd_log_token' ),
		);
	}

	/**
	 * @dataProvider edd_2_log_meta_key_provider
	 */
	public function test_edd_2_api_request_log_meta_keeps_public_requests( $meta_key ) {
		$anonymizer = $this->make_anonymizer( $this->credentials_config() );

		$public = $this->row( array( 'post_id' => '5', 'meta_key' => $meta_key, 'meta_value' => 'public' ) );
		$this->assertSame( 'public', $anonymizer->anonymize_value( 'postmeta', 'meta_value', $public, $public->meta_value ) );

		foreach ( array( self::sample_key(), 'publicity', 'not-public' ) as $original ) {
			$keyed = $this->row( array( 'post_id' => '6', 'meta_key' => $meta_key, 'meta_value' => $original ) );
			$value = $anonymizer->anonymize_value( 'postmeta', 'meta_value', $keyed, $keyed->meta_value );

			$this->assertSame( 'edd log ' . substr( $meta_key, 9 ) . ' 6', $value );
			$this->assertNotSame( $original, $value );
		}
	}

	/**
	 * @return array
	 */
	public static function edd_api_key_transient_provider() {
		return array(
			'public key' => array( 'edd_api_user_public_key' ),
			'secret key' => array( 'edd_api_user_secret_key' ),
		);
	}

	/**
	 * @dataProvider edd_api_key_transient_provider
	 */
	public function test_edd_api_keys_cached_in_transients_are_replaced( $cache_key ) {
		$anonymizer = $this->make_anonymizer( $this->credentials_config() );
		$hash       = md5( $cache_key . '42' );
		$transient  = $this->row( array( 'option_id' => '9', 'option_name' => '_transient_' . $hash, 'option_value' => self::sample_key() ) );

		$value = $anonymizer->anonymize_value( 'options', 'option_value', $transient, $transient->option_value );
		$this->assertSame( 'option value 9', $value );
		$this->assertNotSame( self::sample_key(), $value );

		$timeout = $this->row( array( 'option_id' => '10', 'option_name' => '_transient_timeout_' . $hash, 'option_value' => '1767225600' ) );
		$this->assertSame( '1767225600', $anonymizer->anonymize_value( 'options', 'option_value', $timeout, $timeout->option_value ) );
	}

	/**
	 * Options that aren't a cached EDD API key.
	 *
	 * @return array
	 */
	public static function option_kept_provider() {
		$key = str_repeat( implode( '', range( 0, 9 ) ) . 'abcdef', 2 );

		return array(
			'user ID cached by public key' => array( '_transient_' . md5( 'edd_api_user_' . $key ), '42' ),
			'serialized cache'             => array( '_transient_' . md5( 'other' ), 'a:1:{i:0;s:3:"foo";}' ),
			'named transient'              => array( '_transient_edd_total_api_keys', $key ),
			'site transient'               => array( '_site_transient_' . md5( 'other' ), $key ),
			'option'                       => array( 'my_plugin_hash', $key ),
		);
	}

	/**
	 * @dataProvider option_kept_provider
	 */
	public function test_other_options_are_kept( $option_name, $option_value ) {
		$anonymizer = $this->make_anonymizer( $this->credentials_config() );
		$row        = $this->row( array( 'option_id' => '11', 'option_name' => $option_name, 'option_value' => $option_value ) );

		$this->assertSame( $option_value, $anonymizer->anonymize_value( 'options', 'option_value', $row, $row->option_value ) );
	}


	public function test_edd_2_api_request_log_query_string_is_replaced() {
		$anonymizer = $this->make_anonymizer( $this->credentials_config() );
		$query      = 'edd-api=sales&key=' . self::sample_key() . '&token=' . strrev( self::sample_key() ) . '&email=jane%40example.org';
		$log        = $this->row( array( 'ID' => '5', 'post_type' => 'edd_log', 'post_excerpt' => $query ) );

		$this->assertSame( 'post excerpt 5', $anonymizer->anonymize_value( 'posts', 'post_excerpt', $log, $log->post_excerpt ) );

		$empty = $this->row( array( 'ID' => '6', 'post_type' => 'edd_log', 'post_excerpt' => '' ) );
		$this->assertSame( '', $anonymizer->anonymize_value( 'posts', 'post_excerpt', $empty, $empty->post_excerpt ), 'Other log types have no excerpt, which stays empty.' );

		foreach ( array( 'post', 'download', 'edd_payment' ) as $post_type ) {
			$other = $this->row( array( 'ID' => '7', 'post_type' => $post_type, 'post_excerpt' => 'A short summary.' ) );
			$this->assertSame( 'A short summary.', $anonymizer->anonymize_value( 'posts', 'post_excerpt', $other, $other->post_excerpt ) );
		}
	}


	public function test_edd_software_licensing_2_keys_have_no_public_exception() {
		$anonymizer = $this->make_anonymizer( $this->credentials_config() );

		foreach ( array( '_edd_sl_key', '_edd_sl_renewal_key' ) as $meta_key ) {
			$row = $this->row( array( 'post_id' => '7', 'meta_key' => $meta_key, 'meta_value' => 'public' ) );

			$this->assertSame( str_replace( '_', ' ', ltrim( $meta_key, '_' ) ) . ' 7', $anonymizer->anonymize_value( 'postmeta', 'meta_value', $row, $row->meta_value ) );
		}
	}

	public function test_credentials_use_numbered_placeholders() {
		$anonymizer = $this->make_anonymizer( $this->credentials_config() );
		$cases = array(
			array( 'woocommerce_api_keys', 'consumer_key', array( 'key_id' => 7 ), 'consumer key 7' ),
			array( 'woocommerce_api_keys', 'consumer_secret', array( 'key_id' => 7 ), 'consumer secret 7' ),
			array( 'woocommerce_api_keys', 'truncated_key', array( 'key_id' => 7 ), 'truncated key 7' ),
			array( 'postmeta', 'meta_value', array( 'meta_id' => 92, 'post_id' => 8, 'meta_key' => '_wcdn_invoice_pdf_token' ), 'wcdn invoice pdf token 8' ),
			array( 'wc_orders_meta', 'meta_value', array( 'id' => 93, 'order_id' => 8, 'meta_key' => '_metorik_cart_token' ), 'metorik cart token 8' ),
			array( 'edd_licenses', 'license_key', array( 'id' => 9 ), 'license key 9' ),
		);
		foreach ( $cases as list( $table, $column, $columns, $expected ) ) {
			$row = $this->row( array_merge( $columns, array( $column => 'original-secret-value' ) ) );
			$this->assertSame( $expected, $anonymizer->anonymize_value( $table, $column, $row, $row->{$column} ) );
		}
	}

	public function test_webhook_status_and_destination_use_placeholders() {
		$anonymizer = $this->make_anonymizer( $this->config_file( 'credentials' ) );
		$webhook = $this->row( array( 'webhook_id' => 1, 'status' => 'active', 'delivery_url' => 'https://hooks.example.org/order-created' ) );

		$this->assertSame( 'https://example.com/1', $anonymizer->anonymize_value( 'wc_webhooks', 'delivery_url', $webhook, $webhook->delivery_url ) );
		$this->assertSame( 'status 1', $anonymizer->anonymize_value( 'wc_webhooks', 'status', $webhook, $webhook->status ) );
	}

	public function test_credentials_php_has_no_easy_digital_downloads_rules() {
		$data = $this->config_file( 'credentials' );

		// credentials.php loads on every site, so it mustn't add a usermeta.meta_value constraint.
		$this->assertArrayNotHasKey( 'meta_key', $data['usermeta'] );
		$this->assertStringNotContainsString( 'edd', strtolower( serialize( $data ) ) );
	}
}
