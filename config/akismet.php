<?php
return array(
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
