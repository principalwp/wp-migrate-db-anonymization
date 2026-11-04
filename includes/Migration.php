<?php

namespace WPMDB\Anonymization;

use WPMDB\Anonymization\Config\Config;

class Migration {

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var Anonymizer
	 */
	protected $anonymizer;

	/**
	 * Migration constructor.
	 *
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config     = $config;
		$this->anonymizer = new Anonymizer( $config );
	}

	/**
	 * Register hooks
	 */
	public function register() {
		add_filter( 'wpmdb_before_replace_custom_data', array( $this, 'hook_wpmdb_before_replace_custom_data' ), 10, 2 );
		add_filter( 'wpmdb_after_replace_custom_data', array( $this, 'hook_wpmdb_after_replace_custom_data' ), 10, 3 );
	}

	/**
	 * Let WP Migrate replace nested URLs, then transform the completed value once.
	 *
	 * @param array $data WP Migrate's value, before-fired and successive-filter tuple.
	 * @param object $wpmdb_replace
	 * @return array
	 */
	public function hook_wpmdb_before_replace_custom_data( $data, $wpmdb_replace ) {
		if ( $this->is_allowed_migration_type( $wpmdb_replace->get_intent() ) ) {
			$table = $this->config->clean_table( $wpmdb_replace->get_table() );
			$rule  = $this->anonymizer->matching_rule( $table, $wpmdb_replace->get_column(), $wpmdb_replace->get_row() );
			if ( null !== $rule && $rule->replaces_whole_value() ) {
				$data[2] = false;
			}
		}

		return $data;
	}

	/**
	 * @param array $data
	 * @param bool  $before_fired
	 * @param       $wpmdb_replace
	 *
	 * @return array
	 */
	public function hook_wpmdb_after_replace_custom_data( $data, $before_fired, $wpmdb_replace ) {
		if ( ! $this->is_allowed_migration_type( $wpmdb_replace->get_intent() ) ) {
			return $data;
		}

		if ( empty( $data ) ) {
			return $data;
		}

		$table = $this->config->clean_table( $wpmdb_replace->get_table() );

		if ( ! $this->config->has_table( $table ) ) {
			return $data;
		}

		$column = $wpmdb_replace->get_column();
		if ( ! $this->config->has_column( $table, $column ) ) {
			return $data;
		}

		return $this->anonymizer->anonymize_value( $table, $column, $wpmdb_replace->get_row(), $data );
	}

	/**
	 * @param $intent
	 *
	 * @return bool
	 */
	protected function is_allowed_migration_type( $intent ) {
		return in_array( $intent, array( 'savefile', 'pull', 'push' ) );
	}
}
