<?php

namespace WPMDB\Anonymization\Tests\Unit;

use WPMDB\Anonymization\Anonymizer;
use WPMDB\Anonymization\Tests\TestCase;

class AdditionalFormsLogsRulesTest extends TestCase {

	/**
	 * Config file, table, column, row columns and expected value.
	 *
	 * @return array
	 */
	public static function placeholder_rule_provider() {
		return array(
			'GF resume URL'             => array( 'gravity-forms', 'gf_entry_meta', 'meta_value', array( 'meta_key' => 'resume_url' ), 'https://example.com/7' ),
			'GF resume token'           => array( 'gravity-forms', 'gf_entry_meta', 'meta_value', array( 'meta_key' => 'resume_token' ), 'resume token 7' ),
			'GF unrelated meta'         => array( 'gravity-forms', 'gf_entry_meta', 'meta_value', array( 'meta_key' => 'resume_token_expires' ), 'private data' ),
			'Flamingo inbound title'    => array( 'contact-form-7', 'posts', 'post_title', array( 'post_type' => 'flamingo_inbound' ), 'post title 7' ),
			'Flamingo inbound content'  => array( 'contact-form-7', 'posts', 'post_content', array( 'post_type' => 'flamingo_inbound' ), 'post content 7' ),
			'Flamingo inbound excerpt'  => array( 'contact-form-7', 'posts', 'post_excerpt', array( 'post_type' => 'flamingo_inbound' ), 'post excerpt 7' ),
			'Flamingo contact title'    => array( 'contact-form-7', 'posts', 'post_title', array( 'post_type' => 'flamingo_contact' ), 'post title 7' ),
			'Flamingo contact content'  => array( 'contact-form-7', 'posts', 'post_content', array( 'post_type' => 'flamingo_contact' ), 'post content 7' ),
			'Flamingo contact excerpt'  => array( 'contact-form-7', 'posts', 'post_excerpt', array( 'post_type' => 'flamingo_contact' ), 'post excerpt 7' ),
			'Flamingo contact slug'     => array( 'contact-form-7', 'posts', 'post_name', array( 'post_type' => 'flamingo_contact' ), 'post name 7' ),
			'Flamingo inbound slug'     => array( 'contact-form-7', 'posts', 'post_name', array( 'post_type' => 'flamingo_inbound' ), 'post name 7' ),
			'Other plugin post'         => array( 'contact-form-7', 'posts', 'post_content', array( 'post_type' => 'feedback' ), 'private data' ),
			'Flamingo sender name'      => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_from_name' ), 'from name 7' ),
			'Flamingo sender email'     => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_from_email' ), 'email7@example.com' ),
			'Flamingo field'            => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_field_phone' ), 'field phone 7' ),
			'Flamingo contact name'     => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_name' ), 'name 7' ),
			'Flamingo contact email'    => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_email' ), 'email7@example.com' ),
			'Flamingo subject'          => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_subject' ), 'subject 7' ),
			'Flamingo sender header'    => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_from' ), 'from 7' ),
			'Flamingo submission hash'  => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_hash' ), 'hash 7' ),
			'Flamingo contact date'     => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_last_contacted' ), 'private data' ),
			'Flamingo status'           => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_submission_status' ), 'private data' ),
			'Flamingo anchored prefix'  => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => 'other_field_phone' ), 'private data' ),
			'Elementor IP'              => array( 'elementor-pro', 'e_submissions', 'user_ip', array(), 'user ip 7' ),
			'Elementor user agent'      => array( 'elementor-pro', 'e_submissions', 'user_agent', array(), 'user agent 7' ),
			'Elementor referer'         => array( 'elementor-pro', 'e_submissions', 'referer', array(), 'https://example.com/7' ),
			'Elementor page title'      => array( 'elementor-pro', 'e_submissions', 'referer_title', array(), 'referer title 7' ),
			'Elementor meta'            => array( 'elementor-pro', 'e_submissions', 'meta', array(), '{}' ),
			'Elementor field value'     => array( 'elementor-pro', 'e_submissions_values', 'value', array(), 'value 7' ),
			'Elementor action log'      => array( 'elementor-pro', 'e_submissions_actions_log', 'log', array(), 'log 7' ),
			'Other plugin log'          => array( 'elementor-pro', 'other_submissions_actions_log', 'log', array(), 'private data' ),
			'History grouping hash'    => array( 'simple-history', 'simple_history', 'occasionsID', array(), 'occasionsID 7' ),
			'History message'          => array( 'simple-history', 'simple_history', 'message', array(), 'message 7' ),
			'History email'             => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'user_email' ), 'value 7' ),
			'History login'             => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'user_login' ), 'value 7' ),
			'History remote address'    => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'server_remote_addr' ), 'value 7' ),
			'History forwarded address' => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'http_x_forwarded_for' ), 'value 7' ),
			'History logged title'      => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'post_title' ), 'value 7' ),
			'Other plugin context'      => array( 'simple-history', 'other_history_contexts', 'value', array( 'key' => 'user_email' ), 'private data' ),
			'Akismet submission'        => array( 'akismet', 'commentmeta', 'meta_value', array( 'meta_key' => 'akismet_as_submitted' ), 'a:0:{}' ),
			'Akismet moderator login'  => array( 'akismet', 'commentmeta', 'meta_value', array( 'meta_key' => 'akismet_user' ), 'akismet user 7' ),
			'Other plugin commentmeta'  => array( 'akismet', 'commentmeta', 'meta_value', array( 'meta_key' => 'other_as_submitted' ), 'private data' ),
			'Jetpack feedback title'    => array( 'jetpack-forms', 'posts', 'post_title', array( 'post_type' => 'feedback' ), 'post title 7' ),
			'Jetpack feedback content'  => array( 'jetpack-forms', 'posts', 'post_content', array( 'post_type' => 'feedback' ), '{"fields":[]}' ),
			'Jetpack feedback excerpt'  => array( 'jetpack-forms', 'posts', 'post_excerpt', array( 'post_type' => 'feedback' ), 'post excerpt 7' ),
			'Jetpack feedback slug'     => array( 'jetpack-forms', 'posts', 'post_name', array( 'post_type' => 'feedback' ), 'post name 7' ),
			'Other plugin feedback'     => array( 'jetpack-forms', 'posts', 'post_content', array( 'post_type' => 'flamingo_inbound' ), 'private data' ),
			'Jetpack feedback meta'     => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => '_feedback_email' ), 'a:0:{}' ),
			'Jetpack source link'       => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => '_feedback_source_post_id' ), 'private data' ),
			'Jetpack test flag'         => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => '_feedback_is_test' ), 'private data' ),
			'Jetpack webhook error'     => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => '_jetpack_forms_webhook_error' ), 'jetpack forms webhook error 7' ),
			'Jetpack geoip cache'       => array( 'jetpack-forms', 'options', 'option_name', array( 'option_name' => '_transient_geoip_192.0.2.24' ), 'option name 7' ),
			'Jetpack geoip timeout'     => array( 'jetpack-forms', 'options', 'option_name', array( 'option_name' => '_transient_timeout_geoip_192.0.2.24' ), 'option name 7' ),
			'Jetpack IPv6 cache'        => array( 'jetpack-forms', 'options', 'option_name', array( 'option_name' => '_transient_geoip_2001:db8::24' ), 'option name 7' ),
			'Other transient name'      => array( 'jetpack-forms', 'options', 'option_name', array( 'option_name' => '_transient_other' ), '_transient_other' ),
			'Jetpack country value'     => array( 'jetpack-forms', 'options', 'option_value', array( 'option_name' => '_transient_geoip_192.0.2.24' ), 'private data' ),
			'Jetpack anchored prefix'   => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => 'other_feedback_email' ), 'private data' ),
		);
	}

	/**
	 * @dataProvider placeholder_rule_provider
	 */
	public function test_private_values_become_placeholders_and_unrelated_values_are_kept( $file, $table, $column, $columns, $expected ) {
		$data       = include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/' . $file . '.php';
		$anonymizer = new Anonymizer( $this->make_config( $data ) );
		$parent = array( 'gravity-forms' => 'entry_id', 'contact-form-7' => 'post_id', 'akismet' => 'comment_id', 'jetpack-forms' => 'post_id' );
		$full_row = array_merge( array( 'id' => 7, $column => 'private data' ), $columns );
		if ( isset( $columns['meta_key'] ) ) {
			$full_row['id'] = 90;
			$full_row[ $parent[ $file ] ] = 7;
		}
		$row = $this->row( $full_row );

		$this->assertSame( $expected, $anonymizer->anonymize_value( $table, $column, $row, $row->{$column} ) );
	}
}
