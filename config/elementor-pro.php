<?php
return array(
	'e_submissions'             => array(
		'user_ip'       => array(),
		'user_agent'    => array(),
		'referer'       => array(),
		'referer_title' => array(),
		'meta'          => array(
			array(
				'constraint'            => array( 'meta' => array( 'regex' => '/^\s*\[/' ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_json_array',
			),
			array(
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_json_object',
			),
		),
	),
	'e_submissions_values'      => array(
		'value' => array(),
	),
	'e_submissions_actions_log' => array(
		'log' => array(),
	),
);
