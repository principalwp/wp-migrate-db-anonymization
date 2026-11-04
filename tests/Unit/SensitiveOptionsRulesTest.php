<?php

namespace WPMDB\Anonymization\Tests\Unit;

use WPMDB\Anonymization\Anonymizer;
use WPMDB\Anonymization\Config\Loader;
use WPMDB\Anonymization\Tests\Doubles\TablesWpdb;
use WPMDB\Anonymization\Tests\TestCase;

class SensitiveOptionsRulesTest extends TestCase {

	protected function make_anonymizer() {
		$data = ( new Loader( WPMDB_ANONYMIZATION_PLUGIN_FILE ) )->load( new TablesWpdb() );

		return new Anonymizer( $this->make_config( $data ) );
	}

	public static function scalar_secret_provider() {
		return array(
			'akismet'        => array( 'wordpress_api_key' ),
			'mailchimp'      => array( 'elementor_pro_mailchimp_api_key' ),
			'activecampaign' => array( 'elementor_pro_activecampaign_api_key' ),
			'convertkit'     => array( 'elementor_pro_convertkit_api_key' ),
			'drip'           => array( 'elementor_pro_drip_api_token' ),
			'getresponse'    => array( 'elementor_pro_getresponse_api_key' ),
			'mailerlite'     => array( 'elementor_pro_mailerlite_api_key' ),
			'history RSS'    => array( 'simple_history_rss_secret' ),
			'forms JWT'      => array( 'jetpack_forms_secret_key' ),
		);
	}

	/**
	 * @dataProvider scalar_secret_provider
	 */
	public function test_scalar_credentials_are_emptied( $option_name ) {
		$anonymizer = $this->make_anonymizer();
		$row        = $this->row( array( 'option_id' => 7, 'option_name' => $option_name, 'option_value' => 'production-secret', 'autoload' => 'yes' ) );

		$this->assertSame( '', $anonymizer->anonymize_value( 'options', 'option_value', $row, $row->option_value ) );
		$this->assertSame( $option_name, $anonymizer->anonymize_value( 'options', 'option_name', $row, $row->option_name ) );
	}

	public function test_jetpack_private_tokens_become_an_empty_serialized_array() {
		$anonymizer = $this->make_anonymizer();
		$tokens     = serialize( array( 'blog_token' => 'blog.secret', 'user_tokens' => array( 7 => 'user.secret' ), 'purchase_token' => 'purchase.secret' ) );
		$row        = $this->row( array( 'option_id' => 8, 'option_name' => 'jetpack_private_options', 'option_value' => $tokens, 'autoload' => 'yes' ) );

		$this->assertSame( 'a:0:{}', $anonymizer->anonymize_value( 'options', 'option_value', $row, $row->option_value ) );
		$this->assertSame( 'jetpack_private_options', $anonymizer->anonymize_value( 'options', 'option_name', $row, $row->option_name ) );
	}

	protected function sync_payload() {
		$post = (object) array( 'ID' => 42, 'post_type' => 'feedback', 'post_title' => 'Jane: private request', 'post_content' => 'jane@example.org needs help' );

		return serialize( array( 'jetpack_sync_save_post', array( 42, $post, false, array( 'previous_status' => 'new' ) ), 7, 1767225600.25, false, array( 'user_id' => 7 ) ) );
	}

	public static function queue_option_provider() {
		return array(
			'incremental sync' => array( 'jpsq_sync-1767225600.250000-123456-1' ),
			'full sync'        => array( 'jpsq_full_sync-1767225600.250000-123456-2' ),
		);
	}

	/**
	 * @dataProvider queue_option_provider
	 */
	public function test_jetpack_option_queue_payload_and_identifier_are_replaced( $option_name ) {
		$anonymizer = $this->make_anonymizer();
		$row        = $this->row( array( 'option_id' => 9, 'option_name' => $option_name, 'option_value' => $this->sync_payload(), 'autoload' => 'no' ) );

		$this->assertSame( 'option name 9', $anonymizer->anonymize_value( 'options', 'option_name', $row, $row->option_name ) );
		$this->assertSame( 'a:0:{}', $anonymizer->anonymize_value( 'options', 'option_value', $row, $row->option_value ) );
		$this->assertSame( 'no', $anonymizer->anonymize_value( 'options', 'autoload', $row, $row->autoload ) );
	}

	public function test_jetpack_table_queue_payload_and_identifier_are_replaced() {
		$anonymizer = $this->make_anonymizer();
		$row        = $this->row(
			array(
				'ID'            => '18446744073709551615',
				'queue_id'      => 'sync',
				'event_id'      => 'jpsq_sync-1767225600.250000-123456-1',
				'event_payload' => $this->sync_payload(),
				'timestamp'     => '2026-01-01 00:00:00',
			)
		);

		$queue_id = $anonymizer->anonymize_value( 'jetpack_sync_queue', 'queue_id', $row, $row->queue_id );
		$this->assertSame( 'queue id 18446744073709551615', $queue_id );
		$this->assertLessThanOrEqual( 50, strlen( $queue_id ) );
		$this->assertSame( 'a:0:{}', $anonymizer->anonymize_value( 'jetpack_sync_queue', 'event_payload', $row, $row->event_payload ) );
		$this->assertSame( $row->event_id, $anonymizer->anonymize_value( 'jetpack_sync_queue', 'event_id', $row, $row->event_id ) );
		$this->assertSame( $row->timestamp, $anonymizer->anonymize_value( 'jetpack_sync_queue', 'timestamp', $row, $row->timestamp ) );
	}

	public static function history_cache_provider() {
		return array(
			'quick stats' => array(
				'_transient_sh_quick_stats_data_' . md5( serialize( array( 30, 7, array(), true, true ) ) ),
				serialize( array( 'top_users' => array( array( 'id' => 7, 'display_name' => 'Jane', 'user_email' => 'jane@example.org', 'avatar' => 'https://example.org/jane-avatar' ) ) ) ),
			),
			'oldest event' => array(
				'_transient_sh_oldest_event',
				serialize( array( 'id' => 7, 'message' => 'Jane signed in', 'occasionsID' => 'private-fingerprint' ) ),
			),
		);
	}

	/**
	 * @dataProvider history_cache_provider
	 */
	public function test_history_caches_use_the_false_cache_miss_marker( $option_name, $payload ) {
		$anonymizer = $this->make_anonymizer();
		$row        = $this->row( array( 'option_id' => 10, 'option_name' => $option_name, 'option_value' => $payload, 'autoload' => 'off' ) );
		$value      = $anonymizer->anonymize_value( 'options', 'option_value', $row, $row->option_value );

		$this->assertSame( 'b:0;', $value );
		$this->assertFalse( unserialize( $value ) );
		$this->assertSame( $option_name, $anonymizer->anonymize_value( 'options', 'option_name', $row, $row->option_name ) );
	}

	public function test_history_report_recipients_use_an_email_placeholder() {
		$anonymizer = $this->make_anonymizer();
		$row        = $this->row( array( 'option_id' => 11, 'option_name' => 'simple_history_email_report_recipients', 'option_value' => "jane@example.org\njohn@example.org", 'autoload' => 'on' ) );

		$this->assertSame( 'email11@example.com', $anonymizer->anonymize_value( 'options', 'option_value', $row, $row->option_value ) );
	}

	public static function retained_option_provider() {
		return array(
			'unrelated option'    => array( 'other_plugin_option', 'original value' ),
			'Elementor API URL'    => array( 'elementor_pro_activecampaign_api_url', 'https://example.org/api' ),
			'Jetpack queue lock'   => array( 'jpsq_sync_checkout', 'buffer-id' ),
			'Jetpack settings'     => array( 'jetpack_options', 'a:1:{s:2:"id";i:42;}' ),
			'quick stats timeout'  => array( '_transient_timeout_sh_quick_stats_data_' . md5( 'cache' ), '1767225600' ),
			'oldest event timeout' => array( '_transient_timeout_sh_oldest_event', '1767225600' ),
			'report enabled'       => array( 'simple_history_email_report_enabled', '1' ),
		);
	}

	/**
	 * @dataProvider retained_option_provider
	 */
	public function test_other_options_are_kept( $option_name, $option_value ) {
		$anonymizer = $this->make_anonymizer();
		$row        = $this->row( array( 'option_id' => 12, 'option_name' => $option_name, 'option_value' => $option_value, 'autoload' => 'no' ) );

		$this->assertSame( $option_name, $anonymizer->anonymize_value( 'options', 'option_name', $row, $row->option_name ) );
		$this->assertSame( $option_value, $anonymizer->anonymize_value( 'options', 'option_value', $row, $row->option_value ) );
	}
}
