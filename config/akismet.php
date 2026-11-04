<?php
return array(
	'options'     => array(
		'option_value' => array(
			'constraint'     => array( 'option_name' => 'wordpress_api_key' ),
			'fake_data_type' => 'password',
		),
	),
	'comments'    => array(
		'comment_content' => array(
			'constraint' => array(
				'comment_type'     => array( 'comment', '', 'pingback', 'trackback', 'review' ),
				'comment_approved' => array( '0', 'spam', 'trash', 'post-trashed' ),
			),
		),
	),
	'commentmeta' => array(
		'meta_value' => array(
			array(
				'constraint'            => array( 'meta_key' => array( 'in' => array( 'akismet_as_submitted', 'akismet_history' ) ) ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array',
			),
			array(
				'constraint' => array( 'meta_key' => 'akismet_user' ),
			),
		),
	),
);
