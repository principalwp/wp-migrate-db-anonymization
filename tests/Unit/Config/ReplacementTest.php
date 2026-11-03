<?php

namespace WPMDB\Anonymization\Tests\Unit\Config;

use WPMDB\Anonymization\Config\Replacement;
use WPMDB\Anonymization\Config\Rule;
use WPMDB\Anonymization\Tests\TestCase;

class ReplacementTest extends TestCase {

	public function test_serialized_list_wraps_placeholder() {
		$this->assertSame( serialize( array( 'email7@example.com' ) ), Replacement::serialized_list( 'email7@example.com' ) );
	}

	public function test_coupon_email_restrictions_are_a_serialized_list_of_one_placeholder_email() {
		$config = include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/woocommerce.php';
		$rule = new Rule( 'postmeta', 'meta_value' );
		foreach ( $config['postmeta']['meta_value'] as $data ) {
			if ( isset( $data['constraint']['meta_key'] ) && 'customer_email' === $data['constraint']['meta_key'] ) {
				$rule->load( $data );
			}
		}
		$row = $this->row( array( 'meta_id' => 90, 'post_id' => 7, 'meta_key' => 'customer_email', 'meta_value' => serialize( array( 'jane@example.org' ) ) ) );

		$this->assertSame( array( 'email7@example.com' ), unserialize( $rule->anonymize( $row ) ) );
	}
}
