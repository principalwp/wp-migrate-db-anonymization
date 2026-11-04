WP Migrate DB Anonymization Addon
==========================================

## Requirements

- PHP 7.4+
- WordPress 5.9+
- [WP Migrate](https://deliciousbrains.com/wp-migrate-db-pro/) or [WP Migrate Lite](https://wordpress.org/plugins/wp-migrate-db/)

## Installation

If you clone from this repository you will need to run `composer install` to complete the install of the plugin. The `vendor` directory is packaged with the plugin on the WordPress plugin repository:
https://wordpress.org/plugins/wp-migrate-db-anonymization/#developers 

The addon should be installed on the live site. This ensures any time the site's database is exported, pushed or pulled, the user data is anonymized.

When the plugin is activated it will anonymize by default. You will need to deactivate the plugin to turn it off.

## Configuration

To preserve specific rows in the users table, use the `WPMDB_ANONYMIZATION_USER_LOGIN_WHITELIST` constant to set a whitelist of comma separated user logins.

Passwords are emptied. Define the whitelist constant in the site's wp-config.php file:

    define( 'WPMDB_ANONYMIZATION_USER_LOGIN_WHITELIST', 'somelogin, anotherloginname' );
    
You can also programmatically control which users are whitelisted with the following filter, added to a plugin or mu-plugin file:

    /**
     * @param bool     $whitelisted
     * @param \WP_User $user
     *
     * @return bool
     */
    function my_wpmdb_anonymization_user_whitelisted( $whitelisted, $user )  {
        if ( false !== strrpos( $user->user_email, '@somedomain.com' ) ) {
            // All users with the login containing an email address of somedomain.com are whitelisted
            return true;
        }
        
        return $whitelisted;
    }
    add_filter( 'wpmdb_anonymization_user_whitelisted', 'my_wpmdb_anonymization_user_whitelisted', 10, 2 );
    
## Covered data

The anonymization rules live in the `config` directory, one file per plugin. Order totals, order dates and statuses, product data and numeric record IDs are kept, so order reports on an anonymized copy still add up. WooCommerce webhook statuses are also replaced (see [Credentials](#credentials)).

- `wordpress.php` (always loaded)
    - `users`: login, password, nicename, email, URL and display name
    - `usermeta`: first name, last name, nickname, description, `session_tokens` and `community-events-location` (both store IP addresses), `_new_email` (pending email change) and `signup_ip` (registration IP written by membership and registration plugins)
    - `comments`: author, email, URL, IP address and user agent
- `woocommerce.php`, including WooCommerce Subscriptions, which stores subscriptions as orders
    - `usermeta`: customer billing and shipping details, VAT number, order attribution user agent, Stripe, WooPayments and PayPal Express customer IDs and PayPal billing agreement
    - `postmeta` and `wc_orders_meta`: order billing and shipping details, address search index, customer IP address and user agent (including order attribution), VAT number and VAT location evidence (YITH WooCommerce EU VAT), Stripe customer and card IDs, PayPal Express customer and payer IDs, PayPal Standard payer details
    - `postmeta`: coupon email restrictions, replaced with a single placeholder address so the coupon stays restricted
    - `wc_orders`: billing email, IP address, user agent and customer note
    - `wc_order_addresses`: name, company, address, email and phone
    - `wc_customer_lookup`: username, name, email, location and phone numbers
    - `woocommerce_payment_tokens` and `woocommerce_payment_tokenmeta`: gateway token, card last 4 digits and expiry, PayPal email
    - `woocommerce_downloadable_product_permissions`: customer email
    - `wc_download_log`: IP address
    - `woocommerce_sessions`: session data
    - `wc_stock_notifications`: back in stock sign-up email
    - `wc_email_unsubscribes`: email hash (WooCommerce 11.0 and 11.1)
    - `comments`: content of order notes
- `easy-digital-downloads.php` (EDD 3.x, plus EDD 2.x data left after upgrading)
    - `edd_customers`, `edd_customer_email_addresses` and `edd_customer_addresses`: name, email, address and EDD 2.x customer notes
    - `edd_orders` and `edd_order_addresses`: email, IP address, name and address
    - `edd_customermeta` and `edd_ordermeta`: company, phone, last login IP, additional emails, Stripe and PayPal customer IDs, PayPal payer email, PayPal Express payer ID, EDD Invoices company and VAT number
    - `edd_notes`: note content
    - `edd_payment_tokens`: gateway token and customer ID, label (e.g. PayPal email or card last 4 digits), and payment source details
    - `edd_logs_api_requests`, `edd_logs_file_downloads` and `edd_logs_emails`: IP address, user agent and email
    - `edd_subscriptions` (Recurring Payments): notes
    - `edd_license_activations` (Software Licensing): site URL
    - `usermeta` and `postmeta`: Stripe customer IDs, PayPal Express payer ID, EDD 2.x customer address, payment email and IP address, and file download and API request log IP addresses
- `gravity-forms.php` (Gravity Forms 2.x, plus 1.x tables left after upgrading; loaded if a `gf_entry`, `gf_form` or `rg_lead` table exists)
    - `gf_entry` and `rg_lead`: IP address, user agent and source URL
    - `gf_entry_meta`, `rg_lead_detail` and `rg_lead_detail_long`: every field value, replaced with a numbered placeholder (see [Gravity Forms field values](#gravity-forms-field-values)). Entry meta that isn't a field value, such as add-on feed status, is kept
    - `gf_entry_notes` and `rg_lead_notes`: note content and author name
    - `gf_draft_submissions` and `rg_incomplete_submissions` (Save and Continue): email, IP address and source URL; the saved field values and resume token are replaced in exports, pushes and pulls (see [Gravity Forms drafts](#gravity-forms-drafts))
    - `gf_form_view` and `rg_form_view`: IP address
- `contact-form-7.php` (Flamingo 2.6.4)
    - `posts`: titles, content, excerpts and email/subject-derived slugs for `flamingo_contact` and `flamingo_inbound`
    - `postmeta`: contact email/name/properties; message subject/sender, field values, request metadata, spam-service payloads, consent and submitted-data hash. Arrays become empty serialized arrays; scalar fields receive placeholders. Contact dates and submission statuses are kept
- `elementor-pro.php` (Elementor Pro 4.3.2, classic and Atomic Forms)
    - `e_submissions`: IP address, user agent, referring URL/title and JSON metadata
    - `e_submissions_values`: every submitted field value; `e_submissions_actions_log`: action log text
- `simple-history.php` (Simple History 5.36.0)
    - `simple_history`: message and the grouping fingerprint derived from logged values; `simple_history_contexts`: every context value, including names, profile changes, comment text, notes and request details. JSON lists/objects become empty containers. Event detail and context classification values are removed too
- `akismet.php` (Akismet 5.7.2)
    - `commentmeta`: submitted-comment copies and moderation history become empty serialized arrays; moderator logins receive placeholders. Result/status metadata and original public comment bodies are kept
- `jetpack-forms.php` (Jetpack 16.4-a.7 Forms)
    - `posts`: feedback titles, excerpts and author-derived slugs; feedback content becomes `{"fields":[]}`, retaining the list Jetpack's JSON reader requires
    - `postmeta`: extra answers, spam-check payloads and notification emails become empty serialized arrays; webhook responses become empty JSON objects and webhook errors receive placeholders. Source-page IDs and test flags are kept
    - `options`: geolocation cache transient names, which contain the submitter IP, receive row-numbered placeholders; country values and timeout values are kept
- `redirection.php` (Redirection; loaded if a `redirection_404` or `redirection_logs` table exists)
    - `redirection_404` and `redirection_logs`: IP address, user agent, referrer and request data (logged HTTP headers). The requested URL and domain are kept so the logs stay useful, so personal data in a logged URL's query string is not removed
- `credentials.php` (always loaded; see [Credentials](#credentials))
    - `usermeta`: application passwords, Two Factor TOTP secrets, backup codes and email tokens, Metorik cart tokens
    - `postmeta` and `wc_orders_meta`: WooCommerce Print Invoice & Delivery Note guest access and PDF tokens, Metorik cart tokens
    - `woocommerce_api_keys`: consumer key and secret, truncated key and nonces
    - `wc_webhooks`: secret, delivery URL and status
- `credentials-easy-digital-downloads.php` (loaded with `easy-digital-downloads.php`; see [Credentials](#credentials))
    - `usermeta`: user API keys
    - `options`: user API keys cached in transients
    - `postmeta`: EDD 2.x API log keys and tokens, EDD Software Licensing 2.x license keys
    - `posts`: query string of EDD 2.x API request logs (the excerpt of `edd_log` posts)
    - `edd_logs_api_requests`: API key, token and request query string. In both API request logs, `public`, which EDD records as the key and token of a request made without an API key, is kept
    - `edd_licenses`: license key

Table names are given without the table prefix. On multisite, subsite tables such as `wp_2_postmeta` use the same rules as `wp_postmeta`.

A plugin's rules are only loaded if its data is in the database, whether or not the plugin is active: WooCommerce if a `woocommerce_order_items`, `wc_orders` or `wc_customer_lookup` table exists for any site, or customer billing details remain in `usermeta`; Easy Digital Downloads (both `easy-digital-downloads.php` and `credentials-easy-digital-downloads.php`) if an `edd_customers` or `edd_orders` table exists. Sites without these plugins don't spend time checking their rules during a migration. The rules are loaded, and the `wpmdb_anonymization_config` filter applied, the first time they are needed during a migration rather than on every request.

To force a plugin's rules on or off, use the `wpmdb_anonymization_load_config_file` filter:

    /**
     * @param bool   $load Whether the plugin's data was found in the database.
     * @param string $name Config file name without extension, e.g. 'woocommerce'.
     *
     * @return bool
     */
    function my_wpmdb_anonymization_load_config_file( $load, $name ) {
        if ( 'easy-digital-downloads' === $name ) {
            return true;
        }

        return $load;
    }
    add_filter( 'wpmdb_anonymization_load_config_file', 'my_wpmdb_anonymization_load_config_file', 10, 2 );

### Credentials

API keys, tokens and secrets give access to the live site, its customers' orders and downloads, or external services. The credential rules replace scalar values with numbered placeholders, except TOTP secrets are emptied. Application passwords, Two Factor backup codes and OAuth nonces become empty serialized arrays. Create new keys on the copy if you need working credentials; placeholders do not preserve credential formats.

WooCommerce webhook delivery URLs become `https://example.com/<ID>` and statuses become `status <ID>`. Affected users need to set up Two Factor again on the copy, or deactivate that plugin there.

Easy Digital Downloads credentials are in `credentials-easy-digital-downloads.php`, loaded on sites with EDD data. API keys stored in `meta_key` become `meta key <user ID>`, so their original values do not appear in labels. Cached API keys are replaced when a transient has both an MD5-style name and value, so a cache another plugin stores in that form is replaced too. Sites with a persistent object cache keep transients there instead of in the database.

To keep the original credentials, turn both files off:

    add_filter( 'wpmdb_anonymization_load_config_file', function ( $load, $name ) {
        return 0 === strpos( $name, 'credentials' ) ? false : $load;
    }, 10, 2 );

### Gravity Forms field values

Gravity Forms stores each field value in `gf_entry_meta` under the field ID, such as `1`, or the field and input ID, such as `2.3` for the first name of a name field. Knowing which field is an email address, a phone number or a total would mean parsing every form, so every field value is replaced with a numbered placeholder. That includes product, quantity and total fields; the payment amount and status on the entry itself are kept.

The rules for a column are tried in order and the first that matches the row is used, so to treat a form's fields differently, add rules to the start of the list. A constraint can check `form_id` as well as `meta_key`. Values are compared as strings:

    add_filter( 'wpmdb_anonymization_config', function ( $config ) {
        if ( ! isset( $config['gf_entry_meta'] ) ) {
            return $config;
        }

        array_unshift(
            $config['gf_entry_meta']['meta_value'],
            // Field 2 of form 3 is an email address.
            array(
                'constraint'     => array( 'form_id' => '3', 'meta_key' => '2' ),
                'fake_data_type' => 'email',
            )
        );

        return $config;
    } );

To keep a field's values as they are, skip the rule for those rows with the `wpmdb_anonymization_pre_rule_valid` filter:

    add_filter( 'wpmdb_anonymization_pre_rule_valid', function ( $valid, $rule, $row ) {
        // Keep field 7 (a product drop-down) of form 3.
        if ( isset( $row->entry_id, $row->form_id, $row->meta_key ) && '3' === (string) $row->form_id && '7' === $row->meta_key ) {
            return false;
        }

        return $valid;
    }, 10, 3 );

### Gravity Forms drafts

Save and Continue drafts are stored in `gf_draft_submissions`, or `rg_incomplete_submissions` before Gravity Forms 2.3. Each draft's `uuid` is also its resume token: the `gf_token` in the link sent to the user, which opens the saved entry. Drafts are short-lived resume links; Gravity Forms deletes them after 30 days by default (`gform_incomplete_submissions_expiration_days`).

Exports, pushes and pulls derive a decimal number from the first 52 bits of the token's SHA-256 hash. The token becomes `uuid <number>` and saved field values become an empty JSON object (`{}`). The replacement UUID fits its 32-character column. A hash collision could give two drafts the same replacement UUID; collisions are not handled.

### Column constraints

A rule's `constraint` can limit it to rows where another column has a particular value. Besides an exact value, it accepts a list of values or a regular expression (including delimiters):

    // Exact value
    'constraint' => array( 'meta_key' => 'billing_phone' ),

    // Any of these values
    'constraint' => array( 'meta_key' => array( 'in' => array( 'billing_phone', 'shipping_phone' ) ) ),

    // Shorthand for 'in'
    'constraint' => array( 'meta_key' => array( 'billing_phone', 'shipping_phone' ) ),

    // Regular expression, e.g. tel-1, tel-110, tel-211
    'constraint' => array( 'field_name' => array( 'regex' => '/^tel-\d+$/' ) ),

If a constraint lists several columns, all of them must match.

## Numbered placeholders

Matching scalar fields become `<field label> <N>`, for example `billing first name 7`. The label is the meta key for metadata, otherwise the column name; leading underscores are dropped and other underscores become spaces. A rule replacing `meta_key` itself uses the label `meta key`. Metadata uses the owning user, post, comment, order, entry, customer or payment token ID for `N`; other rows use their first column, the row ID. Gravity Forms drafts use a token hash as described above.

Email fields become `email<N>@example.com`, URLs become `https://example.com/<N>`, and passwords and TOTP secrets become empty strings. Existing empty values stay empty. A rule's `post_process_function` receives the placeholder, except for fields using the password format. Coupon email restrictions use it to store a serialized list containing one placeholder address.

Fields previously cleared to empty serialized arrays or JSON objects keep those empty containers. This covers WordPress sessions, events location and pending email changes; application passwords, backup codes and OAuth nonces; WooCommerce sessions; EDD addresses and payment sources; Gravity Forms drafts; and Redirection request data.

Placeholders depend on row IDs rather than original contents, so the same row gets the same placeholder on each download. They do not preserve joins based on original personal values across unrelated records. Hashes and fixed scalar values also receive placeholders.

Shared metadata rules match keys without looking up the owning post type. A key such as Flamingo's `_name` is therefore replaced on any post using that key. Site-specific filters can override these rules.

The form and log rules cover the named versions' database submission/log storage. They do not scrub form definitions, public comment bodies, uploaded files, external service copies or arbitrary third-party storage. Elementor form names and field identifiers remain configuration; Simple History keeps context key names.

A column shorter than its placeholder can truncate the value and produce an import warning under non-strict SQL mode. Numeric and format-specific fields no longer receive values in their original format.

## Extending

The rules for anonymization can be extended using the `wpmdb_anonymization_config` filter, added to a plugin or mu-plugin file:

    /**
     * Anonymizes a users date of birth.
     *
     * @param array $config
     *
     * @return array
     */
    function my_wpmdb_anonymization_rules( $config ) {
        $config['usermeta']['meta_value'][] = array(
            'constraint' => array( 'meta_key' => 'dob' ),
        );

        return $config;
    }
    
    add_filter( 'wpmdb_anonymization_config', 'my_wpmdb_anonymization_rules' );

The legacy `fake_data_type` key selects special placeholder formatting for `email`, `url`, `domainName` (also a URL) or `password`. Other fields use their label and number. Field names ending in `email` or `url`, and referrer columns, select the corresponding format automatically.

### Private rule files

Rules you'd rather not put in a plugin, for example for a custom table, can live in a plain PHP file that returns an array in the same format as the bundled `config/*.php` files:

    <?php
    // private-rules.php
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
                    'constraint' => array( 'meta_key' => 'private_phone' ),
                ),
            ),
        ),
    );

Add it to every export, push and pull with the `wpmdb_anonymization_config_files` filter, which receives the absolute paths of the rule files to load, bundled files first:

    add_filter( 'wpmdb_anonymization_config_files', function ( $files ) {
        $files[] = WP_CONTENT_DIR . '/private-rules.php';

        return $files;
    } );

- Table names are written without the table prefix, as in the bundled files.
- Extra files are merged with the bundled rules. If both define the same table and column, both sets of rules are kept, bundled rules first, and each value uses the first rule whose constraint matches. Rules with different constraints, such as different `meta_key` values, therefore all apply. To replace a bundled rule for the same rows, change it with the `wpmdb_anonymization_config` filter instead.
- Extra files have no requirements, so they are always loaded. The `wpmdb_anonymization_load_config_file` filter receives their file name without `.php` (e.g. `private-rules`) and can turn them off. Don't name a file after a bundled one (such as `wordpress.php`, `woocommerce.php` or `easy-digital-downloads.php`), or that file's requirements apply to it.
- The file returns an array. Paths that aren't files are skipped by the loader.

## Development

Install the development dependencies with `composer install`.

### Running tests

The unit tests use PHPUnit and run without a WordPress install. The small set of WordPress functions and classes the plugin uses (`apply_filters()`, `WP_User`, `wpdb` and so on) are stubbed in `tests/stubs/`.

    composer test

To run against a specific PHP version, call PHPUnit with that binary, e.g. `/path/to/php7.4 vendor/bin/phpunit`. GitHub Actions runs the suite on PHP 7.4, 8.1, 8.2 and 8.3 for every push and pull request.

### Building the plugin zip

    composer build

This runs `bin/build.sh`, which copies the plugin to a temporary directory, installs production dependencies only (`composer install --no-dev --optimize-autoloader`), strips everything listed in `.distignore` (tests, CI config, `.gitignore` and other development files) and writes `build/wp-migrate-db-anonymization.zip`, ready for the WordPress.org plugin repository. The script needs `bash`, `rsync`, `zip` and `composer`. Set `PHP_BIN=/path/to/php` to run Composer with a specific PHP version.
