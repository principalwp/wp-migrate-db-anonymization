<?php
return array(
	'usermeta' => array(
		'meta_value' => array(
			array(
				'constraint'     => array( 'meta_key' => 'billing_phone' ),
			),
		),
	),
	'postmeta' => array(
		'meta_value' => array(
			array(
				'constraint'     => array( 'meta_key' => '_billing_email' ),
				'fake_data_type' => 'email',
			),
		),
	),
);
