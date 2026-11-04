<?php
$post_rule = array( 'constraint' => array( 'post_type' => 'feedback' ) );

return array(
	'posts'    => array(
		'post_title'   => $post_rule,
		'post_name'    => $post_rule,
		'post_content' => array(
			'constraint'            => array( 'post_type' => 'feedback' ),
			'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_json_object',
		),
		'post_excerpt' => $post_rule,
	),
	'postmeta' => array(
		'meta_value' => array(
			array(
				'constraint'            => array( 'meta_key' => array( 'in' => array( '_feedback_extra_fields', '_feedback_akismet_values', '_feedback_email' ) ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array',
			),
			array(
				'constraint'            => array( 'meta_key' => '_jetpack_forms_webhook_response' ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_json_object',
			),
			array(
				'constraint' => array( 'meta_key' => '_jetpack_forms_webhook_error' ),
			),
		),
	),
	'options'  => array(
		// Jetpack's country lookup embeds the visitor IP in both transient names.
		'option_name' => array(
			'constraint' => array( 'option_name' => array( 'regex' => '/^_transient_(timeout_)?geoip_/' ) ),
		),
		'option_value' => array(
			array(
				'constraint'     => array( 'option_name' => 'jetpack_forms_secret_key' ),
				'fake_data_type' => 'password',
			),
			array(
				'constraint'            => array( 'option_name' => 'jetpack_private_options' ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array',
			),
			array(
				'constraint'            => array( 'option_name' => array( 'regex' => '/^jpsq_[^-]+-/' ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array',
			),
		),
	),
	'jetpack_sync_queue' => array(
		'event_payload' => array( 'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array' ),
	),
);
