<?php
$post_rule = array( 'constraint' => array( 'post_type' => 'feedback' ) );

return array(
	'posts'    => array(
		'post_title'   => $post_rule,
		'post_content' => $post_rule,
		'post_excerpt' => $post_rule,
	),
	'postmeta' => array(
		'meta_value' => array(
			'constraint' => array( 'meta_key' => array( 'regex' => '/^_feedback_/' ) ),
		),
	),
);
