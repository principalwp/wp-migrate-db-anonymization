<?php
// Contact Form 7 submissions and contacts stored by Flamingo.
$post_rule = array( 'constraint' => array( 'post_type' => array( 'flamingo_inbound', 'flamingo_contact' ) ) );

return array(
	'posts'    => array(
		'post_title'   => $post_rule,
		'post_content' => $post_rule,
		'post_excerpt' => $post_rule,
	),
	'postmeta' => array(
		'meta_value' => array(
			array( 'constraint' => array( 'meta_key' => array( '_from_name', '_from_email' ) ) ),
			array( 'constraint' => array( 'meta_key' => array( 'regex' => '/^_field_/' ) ) ),
		),
	),
);
