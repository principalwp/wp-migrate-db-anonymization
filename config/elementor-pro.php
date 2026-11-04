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
						'elementor_pro_stripe_live_secret_key',
						'elementor_pro_stripe_test_secret_key',
						'elementor_pro_recaptcha_secret_key',
						'elementor_pro_recaptcha_v3_secret_key',
					),
				),
			),
			'fake_data_type' => 'password',
		),
	),
	'usermeta'                  => array(
		'meta_value' => array(
			array(
				'constraint'            => array( 'meta_key' => array( 'regex' => '/elementor_connect_common_data$/' ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array',
			),
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
