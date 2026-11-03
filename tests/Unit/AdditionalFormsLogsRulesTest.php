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
			'Other plugin post'         => array( 'contact-form-7', 'posts', 'post_content', array( 'post_type' => 'feedback' ), 'private data' ),
			'Flamingo sender name'      => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_from_name' ), 'from name 7' ),
			'Flamingo sender email'     => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_from_email' ), 'email7@example.com' ),
			'Flamingo field'            => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_field_phone' ), 'field phone 7' ),
			'Flamingo generic key kept' => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_name' ), 'private data' ),
			'Flamingo anchored prefix'  => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => 'other_field_phone' ), 'private data' ),
			'Elementor IP'              => array( 'elementor-pro', 'e_submissions', 'user_ip', array(), 'user ip 7' ),
			'Elementor user agent'      => array( 'elementor-pro', 'e_submissions', 'user_agent', array(), 'user agent 7' ),
			'Elementor referer'         => array( 'elementor-pro', 'e_submissions', 'referer', array(), 'https://example.com/7' ),
			'Elementor meta'            => array( 'elementor-pro', 'e_submissions', 'meta', array(), 'meta 7' ),
			'Elementor field value'     => array( 'elementor-pro', 'e_submissions_values', 'value', array(), 'value 7' ),
			'Elementor action log'      => array( 'elementor-pro', 'e_submissions_actions_log', 'log', array(), 'log 7' ),
			'Other plugin log'          => array( 'elementor-pro', 'other_submissions_actions_log', 'log', array(), 'private data' ),
			'History email'             => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'user_email' ), 'value 7' ),
			'History login'             => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'user_login' ), 'value 7' ),
			'History remote address'    => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'server_remote_addr' ), 'value 7' ),
			'History forwarded address' => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'http_x_forwarded_for' ), 'value 7' ),
			'History unrelated key'     => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'post_title' ), 'private data' ),
			'Other plugin context'      => array( 'simple-history', 'other_history_contexts', 'value', array( 'key' => 'user_email' ), 'private data' ),
			'Akismet submission'        => array( 'akismet', 'commentmeta', 'meta_value', array( 'meta_key' => 'akismet_as_submitted' ), 'akismet as submitted 7' ),
			'Other plugin commentmeta'  => array( 'akismet', 'commentmeta', 'meta_value', array( 'meta_key' => 'other_as_submitted' ), 'private data' ),
			'Jetpack feedback title'    => array( 'jetpack-forms', 'posts', 'post_title', array( 'post_type' => 'feedback' ), 'post title 7' ),
			'Jetpack feedback content'  => array( 'jetpack-forms', 'posts', 'post_content', array( 'post_type' => 'feedback' ), 'post content 7' ),
			'Jetpack feedback excerpt'  => array( 'jetpack-forms', 'posts', 'post_excerpt', array( 'post_type' => 'feedback' ), 'post excerpt 7' ),
			'Other plugin feedback'     => array( 'jetpack-forms', 'posts', 'post_content', array( 'post_type' => 'flamingo_inbound' ), 'private data' ),
			'Jetpack feedback meta'     => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => '_feedback_email' ), 'email7@example.com' ),
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
		$full_row = array_merge( array( 'id' => 7 ), $columns, array( $column => 'private data' ) );
		if ( isset( $columns['meta_key'] ) ) {
			$full_row['id'] = 90;
			$full_row[ $parent[ $file ] ] = 7;
		}
		$row = $this->row( $full_row );

		$this->assertSame( $expected, $anonymizer->anonymize_value( $table, $column, $row, $row->{$column} ) );
	}
}
