<?php
return array(
	'options'                 => array(
		'option_value' => array(
			array(
				'constraint'     => array( 'option_name' => 'simple_history_rss_secret' ),
				'fake_data_type' => 'password',
			),
			array(
				'constraint'            => array( 'option_name' => array( 'regex' => '/^_transient_sh_(quick_stats_data_[a-f0-9]{32}|oldest_event)$/' ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::serialized_false',
			),
			array(
				'constraint'     => array( 'option_name' => 'simple_history_email_report_recipients' ),
				'fake_data_type' => 'email',
			),
		),
	),
	'simple_history'          => array(
		'message'     => array(),
		'occasionsID' => array(),
	),
	'simple_history_contexts' => array(
		'value' => array(
			array(
				'constraint'            => array( 'value' => array( 'regex' => '/^\s*\[/' ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_json_array',
			),
			array(
				'constraint'            => array( 'value' => array( 'regex' => '/^\s*\{/' ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_json_object',
			),
			array(),
		),
	),
);
