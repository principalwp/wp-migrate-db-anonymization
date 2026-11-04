<?php
return array(
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
