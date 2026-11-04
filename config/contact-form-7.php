<?php
// Contact Form 7 submissions and contacts stored by Flamingo.
$post_rule = array( 'constraint' => array( 'post_type' => array( 'flamingo_inbound', 'flamingo_contact' ) ) );

return array(
	'options'  => array(
		'option_value' => array(
			'constraint'           => array( 'option_name' => 'wpcf7' ),
			'replacement_function' => 'WPMDB\\Anonymization\\Config\\Replacement::cf7_credentials',
		),
	),
	'posts'    => array(
		'post_title'   => $post_rule,
		'post_content' => $post_rule,
		'post_excerpt' => $post_rule,
		// Contacts use the email as their slug; inbound slugs derive from subjects.
		'post_name'    => $post_rule,
	),
	'postmeta' => array(
		'meta_value' => array(
			array( 'constraint' => array( 'meta_key' => array( '_email', '_name', '_subject', '_from', '_from_name', '_from_email' ) ) ),
			array(
				'constraint'            => array( 'meta_key' => array( '_props', '_fields', '_meta', '_akismet', '_recaptcha', '_spam_log', '_consent' ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array',
			),
			// Multi-value form fields are arrays; scalar fields keep numbered labels.
			array(
				'constraint'            => array(
					'meta_key'   => array( 'regex' => '/^_field_/' ),
					'meta_value' => array( 'regex' => '/^a:\d+:\{/' ),
				),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array',
			),
			array( 'constraint' => array( 'meta_key' => array( 'regex' => '/^_field_/' ) ) ),
		),
	),
);
