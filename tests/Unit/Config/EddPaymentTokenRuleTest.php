<?php

namespace WPMDB\Anonymization\Tests\Unit\Config;

use WPMDB\Anonymization\Config\Rule;
use WPMDB\Anonymization\Tests\TestCase;

class EddPaymentTokenRuleTest extends TestCase {

	/**
	 * @param string $column
	 *
	 * @return Rule
	 */
	protected function payment_token_rule( $column ) {
		$config = include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/easy-digital-downloads.php';
		$this->assertArrayHasKey( $column, $config['edd_payment_tokens'], "edd_payment_tokens.{$column} has a rule." );

		$rule = new Rule( 'edd_payment_tokens', $column );
		$rule->load( $config['edd_payment_tokens'][ $column ] );

		return $rule;
	}

	/**
	 * @return object
	 */
	protected function paypal_token_row() {
		$payment_source = array(
			'paypal' => array(
				'email_address' => 'buyer@example.com',
				'name'          => array(
					'given_name' => 'Jane',
					'surname'    => 'Buyer',
				),
			),
		);

		return (object) array(
			'id'             => 7,
			'gateway'        => 'paypal',
			'type'           => 'paypal',
			'label'          => 'PayPal - buyer@example.com',
			'payment_source' => json_encode( $payment_source ),
		);
	}

	public function test_label_is_replaced_with_a_numbered_label() {
		$rule = $this->payment_token_rule( 'label' );
		$this->assertTrue( $rule->valid( $this->paypal_token_row() ) );

		$value = $rule->anonymize( $this->paypal_token_row() );

		$this->assertSame( 'label 7', $value );
	}

	public function test_payment_source_is_replaced_with_an_empty_json_object() {
		$rule = $this->payment_token_rule( 'payment_source' );
		$this->assertTrue( $rule->valid( $this->paypal_token_row() ) );

		$value = $rule->anonymize( $this->paypal_token_row() );

		$this->assertSame( '{}', $value );
		$this->assertSame( array(), json_decode( $value, true ) );
	}

	public function test_paypal_payer_email_order_meta_is_anonymized() {
		$config = include WPMDB_ANONYMIZATION_PLUGIN_DIR . '/config/easy-digital-downloads.php';
		$row    = (object) array(
			'meta_id'    => 90,
			'edd_order_id' => 7,
			'meta_key'   => '_edd_paypal_payer_email',
			'meta_value' => 'buyer@example.com',
		);

		foreach ( $config['edd_ordermeta']['meta_value'] as $data ) {
			$rule = new Rule( 'edd_ordermeta', 'meta_value' );
			$rule->load( $data );
			if ( ! $rule->valid( $row ) ) {
				continue;
			}

			$value = $rule->anonymize( $row );
			$this->assertSame( 'email7@example.com', $value );

			return;
		}

		$this->fail( 'No edd_ordermeta rule matches _edd_paypal_payer_email.' );
	}
}
