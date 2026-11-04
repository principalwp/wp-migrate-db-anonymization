<?php

namespace WPMDB\Anonymization\Tests\Unit;

use WPMDB\Anonymization\Anonymizer;
use WPMDB\Anonymization\Config\Replacement;
use WPMDB\Anonymization\Migration;
use WPMDB\Anonymization\Tests\TestCase;

class ReviewFixRulesTest extends TestCase {

	public function test_only_nonpublic_ordinary_comment_bodies_are_replaced() {
		$anonymizer = new Anonymizer( $this->make_config( include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/akismet.php' ) );
		foreach ( array( 'comment', '', 'pingback', 'trackback', 'order_note', 'review' ) as $type ) {
			foreach ( array( '0', 'spam', 'trash', 'post-trashed', '1' ) as $status ) {
				$row = $this->row( array( 'comment_ID' => 7, 'comment_type' => $type, 'comment_approved' => $status, 'comment_content' => 'Private message' ) );
				$expected = in_array( $type, array( 'comment', '', 'pingback', 'trackback' ), true ) && '1' !== $status ? 'comment content 7' : 'Private message';
				$this->assertSame( $expected, $anonymizer->anonymize_value( 'comments', 'comment_content', $row, $row->comment_content ), $type . ':' . $status );
			}
		}
	}

	public function test_elementor_nested_forms_lose_credentials_and_keep_layout_and_other_settings() {
		$value = '[{"id":"container","elType":"container","settings":{},"elements":[{"id":"form","widgetType":"form","settings":{"form_name":"Contact","mailchimp_api_key":"secret1","activecampaign_api_key":"secret2","convertkit_custom_api_key":"secret3","drip_custom_api_token":"secret4","getresponse_custom_api_key":"secret5","mailerlite_custom_api_key":"secret6","slack_webhook":"https://hooks.slack.com/services/secret","discord_webhook":"https://discordapp.com/api/webhooks/secret","form_fields":[{"field_label":"Email"}]},"elements":[]},{"id":"heading","widgetType":"heading","settings":{"title":"Hello","mailchimp_api_key":"unrelated-setting"},"elements":[]}]}]';
		$expected = '[{"id":"container","elType":"container","settings":{},"elements":[{"id":"form","widgetType":"form","settings":{"form_name":"Contact","form_fields":[{"field_label":"Email"}]},"elements":[]},{"id":"heading","widgetType":"heading","settings":{"title":"Hello","mailchimp_api_key":"unrelated-setting"},"elements":[]}]}]';
		$anonymizer = new Anonymizer( $this->make_config( include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/elementor-pro.php' ) );
		// Templates and revisions use these same postmeta rows, without a post-type constraint.
		$row = $this->row( array( 'meta_id' => 90, 'post_id' => 7, 'meta_key' => '_elementor_data', 'meta_value' => $value ) );
		$this->assertSame( json_encode( json_decode( $expected ) ), $anonymizer->anonymize_value( 'postmeta', 'meta_value', $row, $value ) );
		$this->assertSame( $value, $row->meta_value );
	}

	public function test_elementor_empty_form_settings_keep_their_container_type() {
		$value = '[{"widgetType":"form","settings":[],"elements":[]}]';
		$this->assertSame( $value, Replacement::elementor_form_credentials( $value ) );
	}

	public function test_invalid_document_json_stops_instead_of_exporting_credentials() {
		$this->expectException( \JsonException::class );
		Replacement::elementor_form_credentials( '[{"mailchimp_api_key":"secret"}' );
	}

	public function test_whole_document_replacement_keeps_migrated_urls_and_does_not_filter_nested_values() {
		$migration = new Migration( $this->make_config( include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/elementor-pro.php' ) );
		$migration->register();
		$this->assertTrue( has_filter( 'wpmdb_before_replace_custom_data' ) );
		$row = $this->row( array( 'meta_id' => 90, 'post_id' => 7, 'meta_key' => '_elementor_data', 'meta_value' => '[{"widgetType":"form","settings":{"mailchimp_api_key":"secret","redirect_to":"https://production.invalid/contact"},"elements":[]}]' ) );
		foreach ( array( 'savefile', 'push', 'pull' ) as $intent ) {
			$replace = new class( $intent, $row ) {
				private $intent;
				private $row;
				public function __construct( $intent, $row ) { $this->intent = $intent; $this->row = $row; }
				public function get_intent() { return $this->intent; }
				public function get_table() { return 'wp_2_postmeta'; }
				public function get_column() { return 'meta_value'; }
				public function get_row() { return $this->row; }
			};
			$this->assertSame( array( $row->meta_value, false, false ), $migration->hook_wpmdb_before_replace_custom_data( array( $row->meta_value, false, true ), $replace ) );
			$current = str_replace( 'https://production.invalid', 'https://copy.invalid', $row->meta_value );
			$after = $migration->hook_wpmdb_after_replace_custom_data( $current, false, $replace );
			$this->assertSame( array( 'redirect_to' => 'https://copy.invalid/contact' ), (array) json_decode( $after )[0]->settings );
		}
	}
}
