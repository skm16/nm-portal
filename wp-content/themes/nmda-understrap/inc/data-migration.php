<?php
/**
 * Data Migration Functions
 *
 * Handles migration from old Node.js portal to WordPress
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the ID mapping table name
 *
 * @return string Table name with WordPress prefix
 */
function nmda_get_id_mapping_table() {
	global $wpdb;
	return $wpdb->prefix . 'nmda_id_mapping';
}

/**
 * Create the UUID to WordPress ID mapping table
 *
 * @return void
 */
function nmda_create_id_mapping_table() {
	global $wpdb;
	$table_name      = nmda_get_id_mapping_table();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		old_id varchar(50) NOT NULL,
		new_id bigint(20) NOT NULL,
		entity_type varchar(50) NOT NULL,
		migrated_date datetime NOT NULL,
		notes text,
		PRIMARY KEY  (id),
		UNIQUE KEY old_id_type (old_id, entity_type),
		KEY entity_type (entity_type),
		KEY new_id (new_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	error_log( 'NMDA Migration: ID mapping table created/verified' );
}

/**
 * Store UUID to WordPress ID mapping
 *
 * @param string $old_id UUID from old system
 * @param int    $new_id WordPress ID
 * @param string $entity_type Type of entity (user, business, address, reimbursement)
 * @param string $notes Optional notes
 * @return bool True on success, false on failure
 */
function nmda_store_id_mapping( $old_id, $new_id, $entity_type, $notes = '' ) {
	global $wpdb;
	$table = nmda_get_id_mapping_table();

	$result = $wpdb->insert(
		$table,
		array(
			'old_id'         => $old_id,
			'new_id'         => $new_id,
			'entity_type'    => $entity_type,
			'migrated_date'  => current_time( 'mysql' ),
			'notes'          => $notes,
		),
		array( '%s', '%d', '%s', '%s', '%s' )
	);

	if ( $result === false ) {
		error_log( "Failed to store ID mapping: $old_id -> $new_id ($entity_type)" );
		error_log( 'DB Error: ' . $wpdb->last_error );
	}

	return $result !== false;
}

/**
 * Get WordPress ID from old UUID
 *
 * @param string $old_id UUID from old system
 * @param string $entity_type Type of entity
 * @return int|null WordPress ID or null if not found
 */
function nmda_get_new_id_from_uuid( $old_id, $entity_type ) {
	global $wpdb;
	$table = nmda_get_id_mapping_table();

	$new_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT new_id FROM $table WHERE old_id = %s AND entity_type = %s",
			$old_id,
			$entity_type
		)
	);

	return $new_id ? (int) $new_id : null;
}

/**
 * Get old UUID from WordPress ID
 *
 * @param int    $new_id WordPress ID
 * @param string $entity_type Type of entity
 * @return string|null UUID or null if not found
 */
function nmda_get_uuid_from_new_id( $new_id, $entity_type ) {
	global $wpdb;
	$table = nmda_get_id_mapping_table();

	$old_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT old_id FROM $table WHERE new_id = %d AND entity_type = %s",
			$new_id,
			$entity_type
		)
	);

	return $old_id ? $old_id : null;
}

/**
 * Migration utility: Clean phone number
 *
 * @param string $phone Raw phone number
 * @return string Cleaned phone number
 */
function nmda_clean_phone( $phone ) {
	if ( empty( $phone ) || $phone === 'undefined' || $phone === 'null' ) {
		return '';
	}
	// Remove all non-numeric characters
	return preg_replace( '/[^0-9]/', '', $phone );
}

/**
 * Migration utility: Clean email
 *
 * @param string $email Raw email
 * @return string Cleaned email
 */
function nmda_clean_email( $email ) {
	if ( empty( $email ) || $email === 'undefined' || $email === 'null' ) {
		return '';
	}
	return sanitize_email( trim( $email ) );
}

/**
 * Migration utility: Clean URL
 *
 * @param string $url Raw URL
 * @return string Cleaned URL
 */
function nmda_clean_url( $url ) {
	if ( empty( $url ) || $url === 'undefined' || $url === 'null' ) {
		return '';
	}
	// Add http:// if no protocol specified
	if ( ! preg_match( '~^(?:f|ht)tps?://~i', $url ) ) {
		$url = 'http://' . $url;
	}
	return esc_url_raw( trim( $url ) );
}

/**
 * Migration utility: Normalize empty value
 *
 * @param mixed $value Value to normalize
 * @return mixed Normalized value (null if empty)
 */
function nmda_normalize_empty( $value ) {
	if ( $value === '' || $value === 'undefined' || $value === 'null' || $value === false || is_null( $value ) ) {
		return null;
	}
	return $value;
}

/**
 * Migration utility: Generate username from email
 *
 * @param string $email User email
 * @param string $first_name First name (fallback)
 * @param string $last_name Last name (fallback)
 * @return string Unique username
 */
function nmda_generate_username( $email, $first_name = '', $last_name = '' ) {
	// Try email prefix first
	$username = strstr( $email, '@', true );
	$username = sanitize_user( $username, true );

	// If that's taken, try firstname.lastname
	if ( username_exists( $username ) && $first_name && $last_name ) {
		$username = sanitize_user( strtolower( $first_name . '.' . $last_name ), true );
	}

	// If still taken, append numbers
	if ( username_exists( $username ) ) {
		$base_username = $username;
		$counter       = 1;
		while ( username_exists( $username ) ) {
			$username = $base_username . $counter;
			$counter++;
		}
	}

	return $username;
}

/**
 * Migration statistics tracker
 */
class NMDA_Migration_Stats {
	private static $stats = array(
		'users'          => array(
			'attempted' => 0,
			'success'   => 0,
			'skipped'   => 0,
			'errors'    => 0,
		),
		'businesses'     => array(
			'attempted' => 0,
			'success'   => 0,
			'skipped'   => 0,
			'errors'    => 0,
		),
		'relationships'  => array(
			'attempted' => 0,
			'success'   => 0,
			'skipped'   => 0,
			'errors'    => 0,
		),
		'addresses'      => array(
			'attempted' => 0,
			'success'   => 0,
			'skipped'   => 0,
			'errors'    => 0,
		),
		'reimbursements' => array(
			'attempted' => 0,
			'success'   => 0,
			'skipped'   => 0,
			'errors'    => 0,
		),
		'error_log'      => array(),
	);

	public static function increment( $category, $type ) {
		if ( isset( self::$stats[ $category ][ $type ] ) ) {
			self::$stats[ $category ][ $type ]++;
		}
	}

	public static function log_error( $category, $message, $data = array() ) {
		self::$stats['error_log'][] = array(
			'category' => $category,
			'message'  => $message,
			'data'     => $data,
			'time'     => current_time( 'mysql' ),
		);
		error_log( "NMDA Migration Error [$category]: $message" );
	}

	public static function get_stats() {
		return self::$stats;
	}

	public static function get_summary() {
		$summary = array();
		foreach ( self::$stats as $category => $data ) {
			if ( $category === 'error_log' ) {
				$summary[ $category ] = count( $data );
			} else {
				$summary[ $category ] = $data;
			}
		}
		return $summary;
	}
}

/**
 * Initialize migration tables
 *
 * @return void
 */
function nmda_init_migration_tables() {
	nmda_create_id_mapping_table();
	error_log( 'NMDA Migration: Initialization complete' );
}

// Hook to create tables when needed
add_action( 'after_setup_theme', function() {
	// Only create tables if we're about to run migration
	// This prevents unnecessary table creation on every page load
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		nmda_init_migration_tables();

		// Load WP-CLI commands
		require_once __DIR__ . '/cli-migration-commands.php';
	}
}, 20 );
