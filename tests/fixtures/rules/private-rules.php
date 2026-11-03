<?php
/**
 * Extra rules in the same format as the bundled config files. Used by the
 * config files filter tests.
 */

return array(
	'my_backup_table' => array(
		'email'     => array(
			'fake_data_type' => 'email',
		),
		'full_name' => array(),
	),
	'usermeta'        => array(
		'meta_value' => array(
			array(
				'constraint'     => array( 'meta_key' => 'private_phone' ),
			),
		),
	),
);
