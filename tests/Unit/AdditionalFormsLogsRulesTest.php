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
			'Flamingo contact title'    => array( 'contact-form-7', 'posts', 'post_title', array( 'post_type' => 'flamingo_contact' ), 'post title 7' ),
			'Flamingo inbound content'  => array( 'contact-form-7', 'posts', 'post_content', array( 'post_type' => 'flamingo_inbound' ), 'post content 7' ),
			'Flamingo inbound excerpt'  => array( 'contact-form-7', 'posts', 'post_excerpt', array( 'post_type' => 'flamingo_inbound' ), 'post excerpt 7' ),
			'Flamingo contact slug'     => array( 'contact-form-7', 'posts', 'post_name', array( 'post_type' => 'flamingo_contact' ), 'post name 7' ),
			'Flamingo email'            => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_email' ), 'email7@example.com' ),
			'Flamingo field'            => array( 'contact-form-7', 'postmeta', 'meta_value', array( 'meta_key' => '_field_phone' ), 'field phone 7' ),
			'Elementor credential'      => array( 'elementor-pro', 'options', 'option_value', array( 'option_name' => 'elementor_pro_mailchimp_api_key' ), '' ),
			'Elementor IP'              => array( 'elementor-pro', 'e_submissions', 'user_ip', array(), 'user ip 7' ),
			'Elementor user agent'      => array( 'elementor-pro', 'e_submissions', 'user_agent', array(), 'user agent 7' ),
			'Elementor referer'         => array( 'elementor-pro', 'e_submissions', 'referer', array(), 'https://example.com/7' ),
			'Elementor page title'      => array( 'elementor-pro', 'e_submissions', 'referer_title', array(), 'referer title 7' ),
			'Elementor field value'     => array( 'elementor-pro', 'e_submissions_values', 'value', array(), 'value 7' ),
			'Elementor action log'      => array( 'elementor-pro', 'e_submissions_actions_log', 'log', array(), 'log 7' ),
			'History RSS secret'        => array( 'simple-history', 'options', 'option_value', array( 'option_name' => 'simple_history_rss_secret' ), '' ),
			'History report recipient'  => array( 'simple-history', 'options', 'option_value', array( 'option_name' => 'simple_history_email_report_recipients' ), 'email7@example.com' ),
			'History grouping hash'     => array( 'simple-history', 'simple_history', 'occasionsID', array(), 'occasionsID 7' ),
			'History message'           => array( 'simple-history', 'simple_history', 'message', array(), 'message 7' ),
			'History context'           => array( 'simple-history', 'simple_history_contexts', 'value', array( 'key' => 'post_title' ), 'value 7' ),
			'Akismet credential'        => array( 'akismet', 'options', 'option_value', array( 'option_name' => 'wordpress_api_key' ), '' ),
			'Akismet pending review'    => array( 'akismet', 'comments', 'comment_content', array( 'comment_type' => 'review', 'comment_approved' => '0' ), 'comment content 7' ),
			'Akismet moderator login'   => array( 'akismet', 'commentmeta', 'meta_value', array( 'meta_key' => 'akismet_user' ), 'akismet user 7' ),
			'Jetpack feedback title'    => array( 'jetpack-forms', 'posts', 'post_title', array( 'post_type' => 'feedback' ), 'post title 7' ),
			'Jetpack feedback excerpt'  => array( 'jetpack-forms', 'posts', 'post_excerpt', array( 'post_type' => 'feedback' ), 'post excerpt 7' ),
			'Jetpack feedback slug'     => array( 'jetpack-forms', 'posts', 'post_name', array( 'post_type' => 'feedback' ), 'post name 7' ),
			'Jetpack webhook error'     => array( 'jetpack-forms', 'postmeta', 'meta_value', array( 'meta_key' => '_jetpack_forms_webhook_error' ), 'jetpack forms webhook error 7' ),
			'Jetpack geoip cache'       => array( 'jetpack-forms', 'options', 'option_name', array( 'option_name' => '_transient_geoip_192.0.2.24' ), 'option name 7' ),
			'Jetpack signing secret'    => array( 'jetpack-forms', 'options', 'option_value', array( 'option_name' => 'jetpack_forms_secret_key' ), '' ),
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
