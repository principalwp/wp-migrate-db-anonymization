<?php
return array(
	'users'    => array(
		'user_login'    => array(
			'constraint' => 'WPMDB\\Anonymization\\Config\\Constraint::is_not_whitelisted_user',
		),
		'user_pass'     => array(
			'fake_data_type'        => 'password',
			'constraint' => 'WPMDB\\Anonymization\\Config\\Constraint::is_not_whitelisted_user',
		),
		'user_nicename' => array(
			'constraint' => 'WPMDB\\Anonymization\\Config\\Constraint::is_not_whitelisted_user',
		),
		'user_email'    => array(
			'fake_data_type' => 'email',
			'constraint' => 'WPMDB\\Anonymization\\Config\\Constraint::is_not_whitelisted_user',
		),
		'user_url'      => array(
			'fake_data_type' => 'url',
			'constraint' => 'WPMDB\\Anonymization\\Config\\Constraint::is_not_whitelisted_user',
		),
		'display_name'  => array(
			'constraint' => 'WPMDB\\Anonymization\\Config\\Constraint::is_not_whitelisted_user',
		),
	),
	'usermeta' => array(
		'meta_value' => array(
			array(
				'constraint' => array( 'meta_key' => 'first_name' ),
			),
			array(
				'constraint' => array( 'meta_key' => 'last_name' ),
			),
			array(
				'constraint' => array( 'meta_key' => 'nickname' ),
			),
			array(
				'constraint' => array( 'meta_key' => 'description' ),
			),
			array(
				// Login sessions and the dashboard events location both store IP addresses,
				// and a pending email change stores the new address.
				'constraint' => array( 'meta_key' => array( 'in' => array( 'session_tokens', 'community-events-location', '_new_email' ) ) ),
			),
			array(
				// Registration IP, written by several membership and registration plugins.
				'constraint' => array( 'meta_key' => 'signup_ip' ),
			),
		),
	),
	'comments' => array(
		'comment_author'       => array(),
		'comment_author_email' => array(
			'fake_data_type' => 'email',
		),
		'comment_author_url'   => array(
			'fake_data_type' => 'url',
		),
		'comment_author_IP'    => array(),
		'comment_agent'        => array(),
	),
);
