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
	public static function blank_rule_provider() {
		return array(
			'GF resume URL'             => array( 'gravity-forms', 'gf_entry_meta', 'meta_value', array( 'meta_key' => 'resume_url' ), '' ),
			'GF resume token'           => array( 'gravity-forms', 'gf_entry_meta', 'meta_value', array( 'meta_key' => 'resume_token' ), '' ),
			'GF unrelated meta'         => array( 'gravity-forms', 'gf_entry_meta', 'meta_value', array( 'meta_key' => 'resume_token_expires' ), 'private data' ),
			'Flamingo inbound title'    => array( 'contact-form-7', 'posts', 'post_title', array( 'post_type' => 'flamingo_inbound' ), '' ),
			'Flamingo inbound content'  => array( 'contact-form-7', 'posts', 'post_content', array( 'post_type' => 'flamingo_inbound' ), '' ),
			'Flamingo inbound excerpt'  => array( 'contact-form-7', 'posts', 'post_excerpt', array( 'post_type' => 'flamingo_inbound' ), '' ),
			'Flamingo contact title'    => array( 'contact-form-7', 'posts', 'post_title', array( 'post_type' => 'flamingo_contact' ), '' ),
			'Flamingo contact content'  => array( 'contact-form-7', 'posts', 'post_content', array( 'post_type' => 'flamingo_contact' ), '' ),
			'Flamingo contact excerpt'  => array( 'contact-form-7', 'posts', 'post_excerpt', array( 'post_type' => 'flamingo_contact' ), '' ),
			'Other plugin post'         => array( 'contact-form-7', 'posts', 'post_content', array( 'post_type' => 'feedback' ), 'private data' ),
			'Flamingo sender name'      => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_from_name' ), '' ),
			'Flamingo sender email'     => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_from_email' ), '' ),
			'Flamingo field'            => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_field_phone' ), '' ),
			'Flamingo generic key kept' => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_name' ), 'private data' ),
			'Flamingo anchored prefix'  => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => 'other_field_phone' ), 'private data' ),
			'Elementor IP'              => array( 'elementor-pro', 'e_submissions', 'user_ip', array(), '' ),
			'Elementor user agent'      => array( 'elementor-pro', 'e_submissions', 'user_agent', array(), '' ),
			'Elementor referer'         => array( 'elementor-pro', 'e_submissions', 'referer', array(), '' ),
			'Elementor meta'            => array( 'elementor-pro', 'e_submissions', 'meta', array(), '' ),
			'Elementor field value'     => array( 'elementor-pro', 'e_submissions_values', 'value', array(), '' ),
			'Elementor action log'      => array( 'elementor-pro', 'e_submissions_actions_log', 'log', array(), '' ),
			'Other plugin log'          => array( 'elementor-pro', 'other_submissions_actions_log', 'log', array(), 'private data' ),
			'History email'             => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'user_email' ), '' ),
			'History login'             => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'user_login' ), '' ),
			'History remote address'    => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'server_remote_addr' ), '' ),
			'History forwarded address' => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'http_x_forwarded_for' ), '' ),
			'History unrelated key'     => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'post_title' ), 'private data' ),
			'Other plugin context'      => array( 'simple-history', 'other_history_contexts', 'value', array( 'key' => 'user_email' ), 'private data' ),
			'Akismet submission'        => array( 'akismet', 'commentmeta', 'meta_value', array( 'meta_key' => 'akismet_as_submitted' ), '' ),
			'Other plugin commentmeta'  => array( 'akismet', 'commentmeta', 'meta_value', array( 'meta_key' => 'other_as_submitted' ), 'private data' ),
			'Jetpack feedback title'    => array( 'jetpack-forms', 'posts', 'post_title', array( 'post_type' => 'feedback' ), '' ),
			'Jetpack feedback content'  => array( 'jetpack-forms', 'posts', 'post_content', array( 'post_type' => 'feedback' ), '' ),
			'Jetpack feedback excerpt'  => array( 'jetpack-forms', 'posts', 'post_excerpt', array( 'post_type' => 'feedback' ), '' ),
			'Other plugin feedback'     => array( 'jetpack-forms', 'posts', 'post_content', array( 'post_type' => 'flamingo_inbound' ), 'private data' ),
			'Jetpack feedback meta'     => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => '_feedback_email' ), '' ),
			'Jetpack anchored prefix'   => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => 'other_feedback_email' ), 'private data' ),
		);
	}

	/**
	 * @dataProvider blank_rule_provider
	 */
	public function test_private_values_are_blanked_and_unrelated_values_are_kept( $file, $table, $column, $columns, $expected ) {
		$data       = include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/' . $file . '.php';
		$anonymizer = new Anonymizer( $this->make_config( $data ), $this->make_faker() );
		$row        = $this->row( array_merge( $columns, array( $column => 'private data' ) ) );

		$this->assertSame( $expected, $anonymizer->anonymize_value( $table, $column, $row, $row->{$column} ) );
	}
}
