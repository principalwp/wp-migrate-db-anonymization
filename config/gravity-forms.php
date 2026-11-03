<?php
/**
 * Gravity Forms 2.x entry tables, plus the legacy Gravity Forms 1.x tables
 * that may remain after upgrading.
 *
 * Entry field values can hold anything a form collects, so every field value
 * is replaced with a numbered placeholder. Form definitions would have to be
 * parsed to know which field is an email address, a phone number and so on. To use
 * a more specific rule for a field, or to keep a field's values, see the
 * README. Entry IDs, form IDs, dates, statuses and payment details are left
 * untouched.
 */

$url_rule = array(
	// The page a form was submitted from. Query strings can carry personal data.
	'fake_data_type' => 'url',
);

// Field values, stored under the field ID, e.g. `1`, or the field and input ID
// for fields with several inputs, e.g. `2.3` for a name field's first name.
$field_value_rule = array(
	'constraint' => array( 'meta_key' => array( 'regex' => '/^\d+(\.\d+)?$/' ) ),
);

$draft_rules = array(
	// Replace the resume token in Save and Continue links (`gf_token`).
	'uuid'       => array(),
	'email'      => array( 'fake_data_type' => 'email' ),
	'ip'         => array(),
	'source_url' => $url_rule,
	// JSON with the submitted field values. Replace the whole value.
	'submission' => array(),
);

$notes_rules = array(
	// Notes added by admins and add-ons can quote entry details.
	'value'     => array(),
	'user_name' => array(),
);

return array(
	'gf_entry'                  => array(
		'ip'         => array(),
		'user_agent' => array(),
		'source_url' => $url_rule,
	),
	'gf_entry_meta'             => array(
		// Field values and Save and Continue credentials; other add-on meta stays.
		'meta_value' => array(
			$field_value_rule,
			array( 'constraint' => array( 'meta_key' => array( 'resume_url', 'resume_token' ) ) ),
		),
	),
	'gf_entry_notes'            => $notes_rules,
	'gf_draft_submissions'      => $draft_rules,
	'gf_form_view'              => array(
		'ip' => array(),
	),

	// Gravity Forms 1.x.
	'rg_lead'                   => array(
		'ip'         => array(),
		'user_agent' => array(),
		'source_url' => $url_rule,
	),
	'rg_lead_detail'            => array(
		'value' => array(),
	),
	// Field values longer than 200 characters.
	'rg_lead_detail_long'       => array(
		'value' => array(),
	),
	'rg_lead_notes'             => $notes_rules,
	'rg_incomplete_submissions' => $draft_rules,
	'rg_form_view'              => array(
		'ip' => array(),
	),
);
