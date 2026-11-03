<?php
/**
 * Easy Digital Downloads 3.x custom tables, plus legacy EDD 2.x payment meta
 * and customer meta that may remain after the 3.0 migration.
 *
 * Only personal data is anonymized. Order totals, dates, statuses, product
 * data and IDs are left untouched.
 */

$address_rules = array(
	'name'        => array(),
	'address'     => array(),
	'address2'    => array(),
	'city'        => array(),
	'region'      => array(),
	'postal_code' => array(),
	'country'     => array(),
);

$gateway_customer_id_rule = array(
	// Stripe and PayPal customer IDs, live and test mode, and PayPal Express payer ID.
	'constraint' => array( 'meta_key' => array( 'in' => array( '_edd_stripe_customer_id', '_edd_stripe_customer_id_test', '_edd_paypal_vault_customer_id', '_edd_paypal_vault_customer_id_test', '_edd_eppe_payer_id' ) ) ),
);

return array(
	'edd_customers'                => array(
		'email' => array( 'fake_data_type' => 'email' ),
		'name'  => array(),
		// EDD 2.x customer notes, dropped once EDD 3 has moved them to edd_notes.
		'notes' => array(),
	),
	'edd_customer_email_addresses' => array(
		'email' => array( 'fake_data_type' => 'email' ),
	),
	'edd_customer_addresses'       => $address_rules,
	'edd_customermeta'             => array(
		'meta_value' => array(
			array(
				'constraint' => array( 'meta_key' => 'company_name' ),
			),
			array(
				'constraint' => array( 'meta_key' => 'phone' ),
			),
			array(
				'constraint' => array( 'meta_key' => 'last_login_ip' ),
			),
			array(
				// EDD 2.x, moved to edd_customer_email_addresses in 3.0.
				'constraint' => array( 'meta_key' => 'additional_email' ),
				'fake_data_type' => 'email',
			),
			$gateway_customer_id_rule,
		),
	),
	'edd_orders'                   => array(
		'email' => array( 'fake_data_type' => 'email' ),
		'ip'    => array(),
	),
	'edd_order_addresses'          => $address_rules,
	'edd_ordermeta'                => array(
		'meta_value' => array(
			array(
				'constraint' => array( 'meta_key' => 'company_name' ),
			),
			array(
				'constraint' => array( 'meta_key' => '_edd_phone' ),
			),
			array(
				// PayPal account email, shown on the receipt when the buyer paid with PayPal.
				'constraint' => array( 'meta_key' => '_edd_paypal_payer_email' ),
				'fake_data_type' => 'email',
			),
			array(
				// EDD Invoices.
				'constraint' => array( 'meta_key' => 'invoices_company' ),
			),
			array(
				// EDD Invoices VAT number, which can identify a sole trader.
				'constraint' => array( 'meta_key' => 'invoices_vat' ),
			),
			$gateway_customer_id_rule,
		),
	),
	'edd_notes'                    => array(
		// Order and customer notes can contain customer details, e.g. email addresses.
		'content' => array(),
	),
	'edd_payment_tokens'           => array(
		'token_id'            => array(),
		'gateway_customer_id' => array(),
		// The PayPal vault builds the label from the payment source, e.g.
		// "PayPal - buyer@example.com" or "Visa ending in 4242".
		'label'               => array(),
		// PayPal's full payment source response as JSON, which can include the
		// payer's email, name and address, or the cardholder's name and card
		// details. Replace with an empty JSON object.
		'payment_source'      => array( 'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_json_object' ),
	),
	'edd_logs_api_requests'        => array(
		'ip' => array(),
	),
	'edd_logs_file_downloads'      => array(
		'ip'         => array(),
		'user_agent' => array(),
	),
	'edd_logs_emails'              => array(
		'email' => array( 'fake_data_type' => 'email' ),
	),
	'edd_subscriptions'            => array(
		// EDD Recurring Payments subscription notes.
		'notes' => array(),
	),
	'edd_license_activations'      => array(
		// EDD Software Licensing stores the customer's site URL without the protocol
		// or www. Its replacement uses the same URL placeholder as URL fields.
		'site_name' => array( 'fake_data_type' => 'domainName' ),
	),
	'usermeta'                     => array(
		'meta_value' => array(
			array(
				// EDD 2.x customer address. Keep an empty serialized array.
				'constraint'            => array( 'meta_key' => '_edd_user_address' ),
				'post_process_function' => 'WPMDB\\Anonymization\\Config\\Replacement::empty_serialized_array',
			),
			$gateway_customer_id_rule,
		),
	),
	'postmeta'                     => array(
		'meta_value' => array(
			array(
				// EDD 2.x payments, stored as posts.
				'constraint' => array( 'meta_key' => '_edd_payment_user_email' ),
				'fake_data_type' => 'email',
			),
			array(
				// EDD 2.x payment IP and file download and API request log IPs.
				'constraint' => array( 'meta_key' => array( 'in' => array( '_edd_payment_user_ip', '_edd_log_ip', '_edd_log_request_ip' ) ) ),
			),
			$gateway_customer_id_rule,
		),
	),
);
