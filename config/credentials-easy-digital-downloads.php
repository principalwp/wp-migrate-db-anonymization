<?php
/**
 * Easy Digital Downloads API keys, tokens and license keys.
 *
 * As in credentials.php, each becomes a numbered placeholder.
 *
 * Kept apart from credentials.php so its rules only load on sites with EDD
 * data (see Loader::requirements()). To keep the original credentials, turn
 * this file and credentials.php off with the
 * `wpmdb_anonymization_load_config_file` filter (see the README).
 */

// The usermeta names EDD gives a user's API keys: the meta key before EDD 2.4,
// the meta value from 2.4.
$edd_api_user_meta_names = array( 'edd_user_public_key', 'edd_user_secret_key' );

return array(
	'usermeta'              => array(
		'meta_value' => array(
			array(
				// API keys stored by EDD before 2.4, with the key as the value (see
				// meta_key below for later versions).
				'constraint' => array( 'meta_key' => array( 'in' => $edd_api_user_meta_names ) ),
			),
		),
		'meta_key'   => array(
			array(
				// EDD 2.4+ stores a user's API public key and secret key as the meta
				// key, with 'edd_user_public_key' or 'edd_user_secret_key' as the
				// value. Both keys are MD5 hashes.
				'constraint' => array( 'meta_value' => array( 'in' => $edd_api_user_meta_names ) ),
			),
		),
	),
	'options'               => array(
		'option_value' => array(
			array(
				// EDD caches each user's API public key and secret key for an hour
				// in transients named md5( 'edd_api_user_public_key' . $user_id ) and
				// md5( 'edd_api_user_secret_key' . $user_id ), after an API request or
				// when the keys are shown in the admin (EDD_API::get_user_public_key()
				// and get_user_secret_key()). Expired transients stay in the table
				// until WordPress deletes them. The names can't be listed without the
				// user IDs, so this matches any transient with an MD5 name and an MD5
				// value: a different cache stored that way gets a placeholder too.
				'constraint' => array(
					'option_name'  => array( 'regex' => '/^_transient_[a-f0-9]{32}$/' ),
					'option_value' => array( 'regex' => '/^[a-f0-9]{32}$/' ),
				),
			),
		),
	),
	'postmeta'              => array(
		'meta_value' => array(
			array(
				// EDD 2.x API request logs (stored as posts): the API key and token
				// used. 'public' marks a request made without a key and is kept, as
				// in edd_logs_api_requests, to which EDD 3 migrates these logs.
				'constraint' => array(
					'meta_key'   => array( 'in' => array( '_edd_log_key', '_edd_log_token' ) ),
					'meta_value' => array( 'regex' => '/^(?!public$)/' ),
				),
			),
			array(
				// EDD Software Licensing before 3.6 (licenses stored as posts): the
				// license key, and on renewal payments the key that was renewed.
				'constraint' => array( 'meta_key' => array( 'in' => array( '_edd_sl_key', '_edd_sl_renewal_key' ) ) ),
			),
		),
	),
	'posts'                 => array(
		// EDD 2.x logs are edd_log posts. An API request log stores the request's
		// query string in the excerpt, including the key, token and any customer
		// email address or purchase key (EDD 3 migrates it to
		// edd_logs_api_requests.request). EDD sets no excerpt on other log types.
		'post_excerpt' => array(
			'constraint' => array( 'post_type' => 'edd_log' ),
		),
	),
	'edd_logs_api_requests' => array(
		// The API key and token used, or 'public' for requests made without a
		// key, which is kept.
		'api_key' => array(
			'constraint' => array( 'api_key' => array( 'regex' => '/^(?!public$)/' ) ),
		),
		'token'   => array(
			'constraint' => array( 'token' => array( 'regex' => '/^(?!public$)/' ) ),
		),
		// Query string of the request, including the key, token and any customer
		// email address or purchase key.
		'request' => array(),
	),
	'edd_licenses'          => array(
		// EDD Software Licensing 3.6+. An MD5 hash by default.
		'license_key' => array(),
	),
);
