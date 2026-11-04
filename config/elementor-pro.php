<?php
return array(
	'options'                   => array(
		'option_value' => array(
			'constraint'     => array(
				'option_name' => array(
					'in' => array(
						'elementor_pro_mailchimp_api_key',
						'elementor_pro_activecampaign_api_key',
						'elementor_pro_convertkit_api_key',
						'elementor_pro_drip_api_token',
						'elementor_pro_getresponse_api_key',
						'elementor_pro_mailerlite_api_key',
					),
				),
			),
			'fake_data_type' => 'password',
		),
	),
	'postmeta'                  => array(
		'meta_value' => array(
			'constraint'           => array( 'meta_key' => '_elementor_data' ),
			'replacement_function' => 'WPMDB\\Anonymization\\Config\\Replacement::elementor_form_credentials',
		),
	),
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
