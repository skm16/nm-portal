<?php
/**
 * WP-CLI Migration Commands
 *
 * Commands for migrating data from old Node.js portal to WordPress
 *
 * @package NMDA_Understrap_Child
 */

// Exit if not WP-CLI context
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * NMDA Portal Data Migration Commands
 */
class NMDA_Migration_Commands extends WP_CLI_Command {

	/**
	 * Portal files directory
	 *
	 * @var string
	 */
	private $portal_files_dir;

	/**
	 * Dry run mode flag
	 *
	 * @var bool
	 */
	private $dry_run = true;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->portal_files_dir = ABSPATH . 'portal-files/';

		// Ensure migration tables exist
		nmda_init_migration_tables();
	}

	/**
	 * Migrate all data from old portal
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run migration in test mode without making changes
	 *
	 * [--execute]
	 * : Execute the migration for real
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda migrate all --dry-run
	 *     wp nmda migrate all --execute
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function all( $args, $assoc_args ) {
		$this->dry_run = ! isset( $assoc_args['execute'] );

		if ( $this->dry_run ) {
			WP_CLI::warning( '=== DRY RUN MODE - No changes will be made ===' );
		} else {
			WP_CLI::confirm( 'This will import data into the database. Have you backed up? Continue?' );
		}

		WP_CLI::line( '' );
		WP_CLI::line( '╔════════════════════════════════════════════╗' );
		WP_CLI::line( '║   NMDA Portal Data Migration - Full Run   ║' );
		WP_CLI::line( '╚════════════════════════════════════════════╝' );
		WP_CLI::line( '' );

		$start_time = microtime( true );

		// Phase 1: Users
		WP_CLI::line( '📋 Phase 1/5: Migrating Users...' );
		$this->users( array(), $assoc_args );

		// Phase 2: Businesses
		WP_CLI::line( '📋 Phase 2/5: Migrating Businesses...' );
		$this->businesses( array(), $assoc_args );

		// Phase 3: Relationships
		WP_CLI::line( '📋 Phase 3/5: Creating User-Business Relationships...' );
		$this->relationships( array(), $assoc_args );

		// Phase 4: Addresses
		WP_CLI::line( '📋 Phase 4/5: Migrating Addresses...' );
		$this->addresses( array(), $assoc_args );

		// Phase 5: Reimbursements
		WP_CLI::line( '📋 Phase 5/5: Migrating Reimbursements...' );
		$this->reimbursements( array(), $assoc_args );

		$end_time = microtime( true );
		$duration = round( $end_time - $start_time, 2 );

		WP_CLI::line( '' );
		WP_CLI::success( "Migration completed in {$duration} seconds!" );
		WP_CLI::line( '' );

		// Show summary
		$this->stats( array(), array() );

		if ( $this->dry_run ) {
			WP_CLI::line( '' );
			WP_CLI::warning( 'This was a DRY RUN - no data was actually migrated.' );
			WP_CLI::line( 'Run with --execute flag to perform actual migration.' );
		}
	}

	/**
	 * Migrate users from old portal
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run migration in test mode
	 *
	 * [--execute]
	 * : Execute the migration
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda migrate users --dry-run
	 *     wp nmda migrate users --execute
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function users( $args, $assoc_args ) {
		$this->dry_run = ! isset( $assoc_args['execute'] );

		WP_CLI::line( 'Reading user data from portal-files...' );

		$sql_file = $this->portal_files_dir . 'nmda_user.sql';

		if ( ! file_exists( $sql_file ) ) {
			WP_CLI::error( "User SQL file not found: $sql_file" );
		}

		$users = $this->parse_sql_inserts( $sql_file, 'user' );

		WP_CLI::line( sprintf( 'Found %d users to migrate', count( $users ) ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating users', count( $users ) );

		foreach ( $users as $user_data ) {
			NMDA_Migration_Stats::increment( 'users', 'attempted' );

			try {
				$this->migrate_single_user( $user_data );
				NMDA_Migration_Stats::increment( 'users', 'success' );
			} catch ( Exception $e ) {
				NMDA_Migration_Stats::increment( 'users', 'errors' );
				NMDA_Migration_Stats::log_error( 'users', $e->getMessage(), $user_data );
			}

			$progress->tick();
		}

		$progress->finish();

		$stats = NMDA_Migration_Stats::get_stats();
		WP_CLI::success( sprintf(
			'Users: %d attempted, %d success, %d errors',
			$stats['users']['attempted'],
			$stats['users']['success'],
			$stats['users']['errors']
		) );
	}

	/**
	 * Migrate businesses from old portal
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run migration in test mode
	 *
	 * [--execute]
	 * : Execute the migration
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda migrate businesses --dry-run
	 *     wp nmda migrate businesses --execute
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function businesses( $args, $assoc_args ) {
		$this->dry_run = ! isset( $assoc_args['execute'] );

		WP_CLI::line( 'Reading business data from portal-files...' );

		$sql_file = $this->portal_files_dir . 'nmda_company.sql';

		if ( ! file_exists( $sql_file ) ) {
			WP_CLI::error( "Company SQL file not found: $sql_file" );
		}

		$businesses = $this->parse_sql_inserts( $sql_file, 'company' );

		WP_CLI::line( sprintf( 'Found %d businesses to migrate', count( $businesses ) ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating businesses', count( $businesses ) );

		foreach ( $businesses as $business_data ) {
			NMDA_Migration_Stats::increment( 'businesses', 'attempted' );

			try {
				$this->migrate_single_business( $business_data );
				NMDA_Migration_Stats::increment( 'businesses', 'success' );
			} catch ( Exception $e ) {
				NMDA_Migration_Stats::increment( 'businesses', 'errors' );
				NMDA_Migration_Stats::log_error( 'businesses', $e->getMessage(), $business_data );
			}

			$progress->tick();
		}

		$progress->finish();

		$stats = NMDA_Migration_Stats::get_stats();
		WP_CLI::success( sprintf(
			'Businesses: %d attempted, %d success, %d errors',
			$stats['businesses']['attempted'],
			$stats['businesses']['success'],
			$stats['businesses']['errors']
		) );
	}

	/**
	 * Create user-business relationships
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run migration in test mode
	 *
	 * [--execute]
	 * : Execute the migration
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda migrate relationships --dry-run
	 *     wp nmda migrate relationships --execute
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function relationships( $args, $assoc_args ) {
		$this->dry_run = ! isset( $assoc_args['execute'] );

		WP_CLI::line( 'Reading user data to create relationships...' );

		$sql_file = $this->portal_files_dir . 'nmda_user.sql';

		if ( ! file_exists( $sql_file ) ) {
			WP_CLI::error( "User SQL file not found: $sql_file" );
		}

		$users = $this->parse_sql_inserts( $sql_file, 'user' );

		WP_CLI::line( sprintf( 'Found %d user-business relationships to create', count( $users ) ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Creating relationships', count( $users ) );

		foreach ( $users as $user_data ) {
			NMDA_Migration_Stats::increment( 'relationships', 'attempted' );

			try {
				$this->create_user_business_relationship( $user_data );
				NMDA_Migration_Stats::increment( 'relationships', 'success' );
			} catch ( Exception $e ) {
				NMDA_Migration_Stats::increment( 'relationships', 'errors' );
				NMDA_Migration_Stats::log_error( 'relationships', $e->getMessage(), $user_data );
			}

			$progress->tick();
		}

		$progress->finish();

		$stats = NMDA_Migration_Stats::get_stats();
		WP_CLI::success( sprintf(
			'Relationships: %d attempted, %d success, %d errors',
			$stats['relationships']['attempted'],
			$stats['relationships']['success'],
			$stats['relationships']['errors']
		) );
	}

	/**
	 * Migrate business addresses
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run migration in test mode
	 *
	 * [--execute]
	 * : Execute the migration
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda migrate addresses --dry-run
	 *     wp nmda migrate addresses --execute
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function addresses( $args, $assoc_args ) {
		$this->dry_run = ! isset( $assoc_args['execute'] );

		WP_CLI::line( 'Reading address data from portal-files...' );

		$sql_file = $this->portal_files_dir . 'nmda_business_address.sql';

		if ( ! file_exists( $sql_file ) ) {
			WP_CLI::error( "Address SQL file not found: $sql_file" );
		}

		$addresses = $this->parse_sql_inserts( $sql_file, 'business_address' );

		WP_CLI::line( sprintf( 'Found %d addresses to migrate', count( $addresses ) ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating addresses', count( $addresses ) );

		foreach ( $addresses as $address_data ) {
			NMDA_Migration_Stats::increment( 'addresses', 'attempted' );

			try {
				$this->migrate_single_address( $address_data );
				NMDA_Migration_Stats::increment( 'addresses', 'success' );
			} catch ( Exception $e ) {
				NMDA_Migration_Stats::increment( 'addresses', 'errors' );
				NMDA_Migration_Stats::log_error( 'addresses', $e->getMessage(), $address_data );
			}

			$progress->tick();
		}

		$progress->finish();

		$stats = NMDA_Migration_Stats::get_stats();
		WP_CLI::success( sprintf(
			'Addresses: %d attempted, %d success, %d errors',
			$stats['addresses']['attempted'],
			$stats['addresses']['success'],
			$stats['addresses']['errors']
		) );
	}

	/**
	 * Migrate reimbursements
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run migration in test mode
	 *
	 * [--execute]
	 * : Execute the migration
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda migrate reimbursements --dry-run
	 *     wp nmda migrate reimbursements --execute
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function reimbursements( $args, $assoc_args ) {
		$this->dry_run = ! isset( $assoc_args['execute'] );

		WP_CLI::line( 'Reading reimbursement data from portal-files...' );

		// Migrate all three types
		$types = array(
			'lead'        => 'nmda_csr_lead.sql',
			'advertising' => 'nmda_csr_advertising.sql',
			'labels'      => 'nmda_csr_labels.sql',
		);

		$total_count = 0;

		foreach ( $types as $type => $filename ) {
			$sql_file = $this->portal_files_dir . $filename;

			if ( ! file_exists( $sql_file ) ) {
				WP_CLI::warning( "Reimbursement file not found: $filename (skipping)" );
				continue;
			}

			$reimbursements = $this->parse_sql_inserts( $sql_file, 'csr_' . $type );
			$count          = count( $reimbursements );
			$total_count   += $count;

			WP_CLI::line( sprintf( 'Found %d %s reimbursements', $count, $type ) );

			$progress = \WP_CLI\Utils\make_progress_bar( "Migrating $type reimbursements", $count );

			foreach ( $reimbursements as $reimb_data ) {
				NMDA_Migration_Stats::increment( 'reimbursements', 'attempted' );

				try {
					$this->migrate_single_reimbursement( $reimb_data, $type );
					NMDA_Migration_Stats::increment( 'reimbursements', 'success' );
				} catch ( Exception $e ) {
					NMDA_Migration_Stats::increment( 'reimbursements', 'errors' );
					NMDA_Migration_Stats::log_error( 'reimbursements', $e->getMessage(), $reimb_data );
				}

				$progress->tick();
			}

			$progress->finish();
		}

		$stats = NMDA_Migration_Stats::get_stats();
		WP_CLI::success( sprintf(
			'Reimbursements: %d attempted, %d success, %d errors',
			$stats['reimbursements']['attempted'],
			$stats['reimbursements']['success'],
			$stats['reimbursements']['errors']
		) );
	}

	/**
	 * Show migration statistics
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda migrate stats
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function stats( $args, $assoc_args ) {
		$stats = NMDA_Migration_Stats::get_summary();

		WP_CLI::line( '' );
		WP_CLI::line( '╔═══════════════════════════════════════╗' );
		WP_CLI::line( '║     MIGRATION STATISTICS SUMMARY      ║' );
		WP_CLI::line( '╚═══════════════════════════════════════╝' );
		WP_CLI::line( '' );

		foreach ( $stats as $category => $data ) {
			if ( $category === 'error_log' ) {
				WP_CLI::line( sprintf( '📋 Errors Logged: %d', $data ) );
				continue;
			}

			if ( is_array( $data ) ) {
				WP_CLI::line( sprintf(
					'%s: Attempted: %d, Success: %d, Skipped: %d, Errors: %d',
					ucfirst( $category ),
					$data['attempted'],
					$data['success'],
					$data['skipped'],
					$data['errors']
				) );
			}
		}

		WP_CLI::line( '' );
	}

	/**
	 * Validate migrated data
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda validate migration
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function validate( $args, $assoc_args ) {
		global $wpdb;

		WP_CLI::line( '' );
		WP_CLI::line( '╔═══════════════════════════════════════╗' );
		WP_CLI::line( '║     MIGRATION DATA VALIDATION         ║' );
		WP_CLI::line( '╚═══════════════════════════════════════╝' );
		WP_CLI::line( '' );

		$issues = array();

		// Check orphaned relationships
		WP_CLI::line( '🔍 Checking for orphaned user-business relationships...' );
		$orphaned_users = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}nmda_user_business ub
			LEFT JOIN {$wpdb->users} u ON ub.user_id = u.ID
			WHERE u.ID IS NULL"
		);

		if ( $orphaned_users > 0 ) {
			$issues[] = "$orphaned_users orphaned user relationships found";
			WP_CLI::warning( "Found $orphaned_users orphaned user relationships" );
		} else {
			WP_CLI::success( 'No orphaned user relationships' );
		}

		$orphaned_businesses = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}nmda_user_business ub
			LEFT JOIN {$wpdb->posts} p ON ub.business_id = p.ID
			WHERE p.ID IS NULL"
		);

		if ( $orphaned_businesses > 0 ) {
			$issues[] = "$orphaned_businesses orphaned business relationships found";
			WP_CLI::warning( "Found $orphaned_businesses orphaned business relationships" );
		} else {
			WP_CLI::success( 'No orphaned business relationships' );
		}

		// Check required fields
		WP_CLI::line( '🔍 Checking for missing required fields...' );

		$businesses_without_names = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			WHERE post_type = 'nmda_business'
			AND (post_title IS NULL OR post_title = '')"
		);

		if ( $businesses_without_names > 0 ) {
			$issues[] = "$businesses_without_names businesses without names";
			WP_CLI::warning( "Found $businesses_without_names businesses without names" );
		} else {
			WP_CLI::success( 'All businesses have names' );
		}

		// Summary
		WP_CLI::line( '' );
		if ( empty( $issues ) ) {
			WP_CLI::success( '✅ All validation checks passed!' );
		} else {
			WP_CLI::warning( sprintf( '⚠️  Found %d validation issues:', count( $issues ) ) );
			foreach ( $issues as $issue ) {
				WP_CLI::line( "  - $issue" );
			}
		}
		WP_CLI::line( '' );
	}

	/**
	 * Parse SQL INSERT statements from dump file
	 *
	 * @param string $file_path Path to SQL file
	 * @param string $table_name Table name (for logging)
	 * @return array Array of parsed records
	 */
	private function parse_sql_inserts( $file_path, $table_name ) {
		$content = file_get_contents( $file_path );
		$records = array();

		// Debug
		if ( ! $content ) {
			WP_CLI::warning( "Could not read file: $file_path" );
			return array();
		}

		WP_CLI::debug( "File size: " . strlen( $content ) . " bytes" );

		// First, get column names from CREATE TABLE statement
		$columns = array();
		if ( preg_match( "/CREATE TABLE `?$table_name`?\s*\((.*?)\)\s*ENGINE=/si", $content, $create_match ) ) {
			$create_body = $create_match[1];

			// Split by newlines and extract column names from lines that start with a backtick
			$lines = explode( "\n", $create_body );
			foreach ( $lines as $line ) {
				$line = trim( $line );

				// Skip constraint lines (PRIMARY KEY, UNIQUE KEY, FOREIGN KEY, etc.)
				if ( preg_match( "/^(PRIMARY|UNIQUE|FOREIGN|KEY|INDEX|CONSTRAINT)/i", $line ) ) {
					continue;
				}

				// Match lines that start with backtick (column definitions)
				// Pattern: `columnname` datatype ...
				if ( preg_match( "/^`([a-zA-Z0-9_]+)`\s+\w+/", $line, $col_match ) ) {
					WP_CLI::debug( "Matched column line: " . substr( $line, 0, 80 ) );
					$columns[] = $col_match[1];
				}
			}

			WP_CLI::debug( "Found " . count( $columns ) . " columns: " . implode( ', ', $columns ) );
		} else {
			WP_CLI::warning( "Could not find CREATE TABLE statement for: $table_name" );
		}

		if ( empty( $columns ) ) {
			WP_CLI::warning( "Could not extract column names for table: $table_name" );
			return array();
		}

		// Find INSERT INTO statements (format: INSERT INTO `table` VALUES (...),(...),...)
		if ( preg_match( "/INSERT INTO `?$table_name`?\s+VALUES\s*(.+);/si", $content, $insert_match ) ) {
			WP_CLI::debug( "Found INSERT statement, parsing values..." );
			$values_string = $insert_match[1];

			// Split by ),( to get individual records
			// Use a more robust approach to handle nested parentheses
			$value_sets = $this->split_value_sets( $values_string );
			WP_CLI::debug( "Found " . count( $value_sets ) . " value sets" );

			foreach ( $value_sets as $value_set ) {
				$values = $this->parse_sql_values( $value_set );

				if ( count( $values ) === count( $columns ) ) {
					$record = array_combine( $columns, $values );
					$records[] = $record;
				} else {
					WP_CLI::debug( "Value count mismatch: " . count( $values ) . " values vs " . count( $columns ) . " columns" );
				}
			}
		} else {
			WP_CLI::warning( "Could not find INSERT INTO statement for table: $table_name" );
		}

		return $records;
	}

	/**
	 * Split value sets from INSERT VALUES
	 *
	 * @param string $values_string String containing all value sets
	 * @return array Array of value set strings
	 */
	private function split_value_sets( $values_string ) {
		$sets = array();
		$current_set = '';
		$paren_depth = 0;
		$in_quotes = false;
		$quote_char = '';

		for ( $i = 0; $i < strlen( $values_string ); $i++ ) {
			$char = $values_string[ $i ];
			$prev_char = $i > 0 ? $values_string[ $i - 1 ] : '';

			// Handle quotes
			if ( ( $char === "'" || $char === '"' ) && $prev_char !== '\\' ) {
				if ( ! $in_quotes ) {
					$in_quotes = true;
					$quote_char = $char;
				} elseif ( $char === $quote_char ) {
					$in_quotes = false;
				}
			}

			// Handle parentheses (only when not in quotes)
			if ( ! $in_quotes ) {
				if ( $char === '(' ) {
					$paren_depth++;
				} elseif ( $char === ')' ) {
					$paren_depth--;

					// If we've closed the outer parenthesis
					if ( $paren_depth === 0 ) {
						$sets[] = trim( $current_set );
						$current_set = '';
						// Skip the comma after closing paren
						if ( isset( $values_string[ $i + 1 ] ) && $values_string[ $i + 1 ] === ',' ) {
							$i++;
						}
						continue;
					}
				}
			}

			// Add character to current set (but skip outer parentheses)
			if ( $paren_depth > 0 ) {
				$current_set .= $char;
			}
		}

		// Add last set if exists
		if ( ! empty( trim( $current_set ) ) ) {
			$sets[] = trim( $current_set );
		}

		return $sets;
	}

	/**
	 * Parse SQL values from INSERT statement
	 *
	 * @param string $values_string String of values
	 * @return array Array of values
	 */
	private function parse_sql_values( $values_string ) {
		$values = array();
		$current_value = '';
		$in_quotes = false;
		$quote_char = '';
		$escaped = false;

		for ( $i = 0; $i < strlen( $values_string ); $i++ ) {
			$char = $values_string[ $i ];

			if ( $escaped ) {
				$current_value .= $char;
				$escaped = false;
				continue;
			}

			if ( $char === '\\' ) {
				$escaped = true;
				continue;
			}

			if ( ( $char === "'" || $char === '"' ) && ! $in_quotes ) {
				$in_quotes = true;
				$quote_char = $char;
				continue;
			}

			if ( $char === $quote_char && $in_quotes ) {
				$in_quotes = false;
				continue;
			}

			if ( $char === ',' && ! $in_quotes ) {
				$values[] = $this->clean_sql_value( $current_value );
				$current_value = '';
				continue;
			}

			$current_value .= $char;
		}

		// Add last value
		if ( $current_value !== '' ) {
			$values[] = $this->clean_sql_value( $current_value );
		}

		return $values;
	}

	/**
	 * Clean SQL value
	 *
	 * @param string $value Raw value
	 * @return mixed Cleaned value
	 */
	private function clean_sql_value( $value ) {
		$value = trim( $value );

		if ( $value === 'NULL' ) {
			return null;
		}

		// Remove quotes
		$value = trim( $value, "'" );
		$value = trim( $value, '"' );

		// Unescape
		$value = stripslashes( $value );

		return $value;
	}

	/**
	 * Migrate a single user
	 *
	 * @param array $user_data User data from SQL
	 * @return int|false User ID or false on failure
	 */
	private function migrate_single_user( $user_data ) {
		// Extract fields
		$user_id_uuid = $user_data['UserId'] ?? $user_data['userId'] ?? null;
		$email        = nmda_clean_email( $user_data['Email'] ?? $user_data['email'] ?? '' );
		$first_name   = sanitize_text_field( $user_data['FirstName'] ?? $user_data['firstName'] ?? '' );
		$last_name    = sanitize_text_field( $user_data['LastName'] ?? $user_data['lastName'] ?? '' );
		$company_id   = $user_data['CompanyId'] ?? $user_data['companyId'] ?? null;
		$acct_mgr_guid = $user_data['AccountManagerGUID'] ?? $user_data['accountManagerGUID'] ?? '';

		if ( ! $user_id_uuid || ! $email ) {
			throw new Exception( 'Missing required user fields: UserId or Email' );
		}

		// Check if user already exists
		$existing_user_id = email_exists( $email );
		if ( $existing_user_id ) {
			NMDA_Migration_Stats::increment( 'users', 'skipped' );

			// Still create ID mapping for existing users so relationships can be linked
			if ( ! $this->dry_run ) {
				nmda_store_id_mapping( $user_id_uuid, $existing_user_id, 'user', "Email: $email (existing user)" );
			}

			return $existing_user_id;
		}

		if ( $this->dry_run ) {
			// In dry-run, just validate and return
			return true;
		}

		// Generate username
		$username = nmda_generate_username( $email, $first_name, $last_name );

		// Create user
		$wp_user_id = wp_insert_user( array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => 'NMDAPortal2024!',
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'role'         => 'business_owner',
			'show_admin_bar_front' => false,
		) );

		if ( is_wp_error( $wp_user_id ) ) {
			throw new Exception( 'Failed to create user: ' . $wp_user_id->get_error_message() );
		}

		// Store AccountManagerGUID for reference
		if ( $acct_mgr_guid ) {
			update_user_meta( $wp_user_id, 'account_manager_guid', $acct_mgr_guid );
		}

		// Store company ID temporarily (will be used in relationship phase)
		if ( $company_id ) {
			update_user_meta( $wp_user_id, '_migration_company_id', $company_id );
		}

		// Force password reset on first login
		update_user_meta( $wp_user_id, 'show_password_fields', false );

		// Store ID mapping
		nmda_store_id_mapping( $user_id_uuid, $wp_user_id, 'user', "Email: $email (new user)" );

		return $wp_user_id;
	}

	/**
	 * Migrate a single business
	 *
	 * @param array $business_data Business data from SQL
	 * @return int|false Post ID or false on failure
	 */
	private function migrate_single_business( $business_data ) {
		// Extract fields
		$company_id_uuid = $business_data['CompanyId'] ?? $business_data['companyId'] ?? null;
		$company_name    = sanitize_text_field( $business_data['CompanyName'] ?? $business_data['companyName'] ?? '' );
		$first_name      = sanitize_text_field( $business_data['FirstName'] ?? $business_data['firstName'] ?? '' );
		$last_name       = sanitize_text_field( $business_data['LastName'] ?? $business_data['lastName'] ?? '' );
		$email           = nmda_clean_email( $business_data['Email'] ?? $business_data['email'] ?? '' );
		$phone           = nmda_clean_phone( $business_data['Phone'] ?? $business_data['phone'] ?? '' );
		$website         = nmda_clean_url( $business_data['Website'] ?? $business_data['website'] ?? '' );
		$address         = sanitize_text_field( $business_data['Address'] ?? $business_data['address'] ?? '' );
		$city            = sanitize_text_field( $business_data['City'] ?? $business_data['city'] ?? '' );
		$state           = sanitize_text_field( $business_data['State'] ?? $business_data['state'] ?? 'NM' );
		$zip             = sanitize_text_field( $business_data['Zip'] ?? $business_data['zip'] ?? '' );
		$product_type    = $business_data['ProductType'] ?? $business_data['productType'] ?? '';
		$logo            = $business_data['Logo'] ?? $business_data['logo'] ?? '';

		if ( ! $company_id_uuid || ! $company_name ) {
			throw new Exception( 'Missing required business fields: CompanyId or CompanyName' );
		}

		if ( $this->dry_run ) {
			return true;
		}

		// Create business post
		$post_id = wp_insert_post( array(
			'post_type'    => 'nmda_business',
			'post_title'   => $company_name,
			'post_status'  => 'publish',
			'post_author'  => 1, // Will be updated in relationship phase
		) );

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( 'Failed to create business: ' . $post_id->get_error_message() );
		}

		// Update ACF fields
		if ( $email ) {
			update_field( 'business_email', $email, $post_id );
		}
		if ( $phone ) {
			update_field( 'business_phone', $phone, $post_id );
		}
		if ( $website ) {
			update_field( 'business_website', $website, $post_id );
		}

		// Store owner name in meta
		if ( $first_name || $last_name ) {
			update_post_meta( $post_id, '_owner_name', trim( "$first_name $last_name" ) );
		}

		// Store address in ACF fields
		if ( $address ) {
			update_field( 'primary_address', $address, $post_id );
		}
		if ( $city ) {
			update_field( 'primary_city', $city, $post_id );
		}
		if ( $state ) {
			update_field( 'primary_state', $state, $post_id );
		}
		if ( $zip ) {
			update_field( 'primary_zip', $zip, $post_id );
		}

		// Parse and assign product types
		if ( $product_type ) {
			$products = array_map( 'trim', explode( ',', $product_type ) );
			foreach ( $products as $product ) {
				if ( $product ) {
					wp_set_object_terms( $post_id, $product, 'product_type', true );
				}
			}
		}

		// Store ID mapping
		nmda_store_id_mapping( $company_id_uuid, $post_id, 'business', "Name: $company_name" );

		return $post_id;
	}

	/**
	 * Create user-business relationship
	 *
	 * @param array $user_data User data from SQL
	 * @return bool Success
	 */
	private function create_user_business_relationship( $user_data ) {
		global $wpdb;

		$user_id_uuid    = $user_data['UserId'] ?? $user_data['userId'] ?? null;
		$company_id_uuid = $user_data['CompanyId'] ?? $user_data['companyId'] ?? null;
		$user_email      = $user_data['Email'] ?? $user_data['email'] ?? null;

		if ( ! $user_id_uuid ) {
			throw new Exception( 'Missing UserId for relationship' );
		}

		// Get user WordPress ID
		$wp_user_id = nmda_get_new_id_from_uuid( $user_id_uuid, 'user' );
		if ( ! $wp_user_id ) {
			throw new Exception( "Could not find user mapping for UUID: $user_id_uuid" );
		}

		// Try to get business by UUID first
		$wp_business_id = null;
		if ( $company_id_uuid ) {
			$wp_business_id = nmda_get_new_id_from_uuid( $company_id_uuid, 'business' );
		}

		// If UUID lookup failed, try matching by email
		if ( ! $wp_business_id && $user_email ) {
			// Get user object to confirm email
			$user = get_user_by( 'id', $wp_user_id );
			if ( $user && $user->user_email ) {
				// Find business post with matching email in ACF field
				$args = array(
					'post_type'      => 'nmda_business',
					'posts_per_page' => 1,
					'meta_query'     => array(
						array(
							'key'     => 'business_email',
							'value'   => $user->user_email,
							'compare' => '='
						),
					),
				);
				$businesses = get_posts( $args );
				if ( ! empty( $businesses ) ) {
					$wp_business_id = $businesses[0]->ID;
					WP_CLI::debug( "Matched business by email: {$user->user_email} -> Business ID: $wp_business_id" );
				}
			}
		}

		// If still no business found, throw error
		if ( ! $wp_business_id ) {
			throw new Exception( "Could not find business for user email: $user_email (CompanyId UUID: $company_id_uuid)" );
		}

		if ( $this->dry_run ) {
			return true;
		}

		// Check if relationship already exists
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}nmda_user_business WHERE user_id = %d AND business_id = %d",
				$wp_user_id,
				$wp_business_id
			)
		);

		if ( $existing ) {
			WP_CLI::debug( "Relationship already exists: User $wp_user_id <-> Business $wp_business_id" );
			NMDA_Migration_Stats::increment( 'relationships', 'skipped' );
			return true;
		}

		// Create relationship
		$result = $wpdb->insert(
			$wpdb->prefix . 'nmda_user_business',
			array(
				'user_id'       => $wp_user_id,
				'business_id'   => $wp_business_id,
				'role'          => 'owner',
				'status'        => 'active',
				'invited_date'  => current_time( 'mysql' ),
				'accepted_date' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( $result === false ) {
			throw new Exception( 'Failed to create user-business relationship: ' . $wpdb->last_error );
		}

		// Update post_author
		wp_update_post( array(
			'ID'          => $wp_business_id,
			'post_author' => $wp_user_id,
		) );

		return true;
	}

	/**
	 * Migrate a single address
	 *
	 * @param array $address_data Address data from SQL
	 * @return int|false Address ID or false
	 */
	private function migrate_single_address( $address_data ) {
		global $wpdb;

		$business_id_uuid = $address_data['BusinessId'] ?? $address_data['businessId'] ?? null;

		if ( ! $business_id_uuid ) {
			throw new Exception( 'Missing BusinessId for address' );
		}

		$wp_business_id = nmda_get_new_id_from_uuid( $business_id_uuid, 'business' );

		if ( ! $wp_business_id ) {
			throw new Exception( "Could not find business for address: $business_id_uuid" );
		}

		if ( $this->dry_run ) {
			return true;
		}

		// Extract address fields
		$address_type = $address_data['AddressType'] ?? $address_data['addressType'] ?? 'primary';
		$address_name = $address_data['AddressName'] ?? $address_data['addressName'] ?? '';
		$street       = $address_data['Address'] ?? $address_data['address'] ?? '';
		$city         = $address_data['City'] ?? $address_data['city'] ?? '';
		$state        = $address_data['State'] ?? $address_data['state'] ?? 'NM';
		$zip          = $address_data['Zip'] ?? $address_data['zip'] ?? '';

		// Insert into custom table
		$result = $wpdb->insert(
			$wpdb->prefix . 'nmda_business_addresses',
			array(
				'business_id'      => $wp_business_id,
				'address_type'     => $address_type,
				'address_name'     => $address_name,
				'street_address'   => $street,
				'city'             => $city,
				'state'            => $state,
				'zip_code'         => $zip,
				'is_primary'       => 1,
			)
		);

		if ( $result === false ) {
			throw new Exception( 'Failed to create address' );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Migrate a single reimbursement
	 *
	 * @param array  $reimb_data Reimbursement data from SQL
	 * @param string $type Reimbursement type (lead, advertising, labels)
	 * @return int|false Reimbursement ID or false
	 */
	private function migrate_single_reimbursement( $reimb_data, $type ) {
		global $wpdb;

		$business_id_uuid = $reimb_data['BusinessId'] ?? $reimb_data['businessId'] ?? null;
		$user_id_uuid     = $reimb_data['UserId'] ?? $reimb_data['userId'] ?? null;

		if ( ! $business_id_uuid || ! $user_id_uuid ) {
			throw new Exception( 'Missing BusinessId or UserId for reimbursement' );
		}

		$wp_business_id = nmda_get_new_id_from_uuid( $business_id_uuid, 'business' );
		$wp_user_id     = nmda_get_new_id_from_uuid( $user_id_uuid, 'user' );

		if ( ! $wp_business_id || ! $wp_user_id ) {
			throw new Exception( "Could not find mapped IDs for reimbursement" );
		}

		if ( $this->dry_run ) {
			return true;
		}

		// Determine status
		$approved = (int) ( $reimb_data['Approved'] ?? $reimb_data['approved'] ?? 0 );
		$rejected = (int) ( $reimb_data['Rejected'] ?? $reimb_data['rejected'] ?? 0 );

		if ( $approved ) {
			$status = 'approved';
		} elseif ( $rejected ) {
			$status = 'rejected';
		} else {
			$status = 'submitted';
		}

		// Encode all form data as JSON
		$form_data = json_encode( $reimb_data );

		// Extract dates
		$date_submitted = $reimb_data['DateSubmitted'] ?? $reimb_data['dateSubmitted'] ?? current_time( 'mysql' );
		$date_updated   = $reimb_data['DateApproved'] ?? $reimb_data['DateRejected'] ?? $reimb_data['dateApproved'] ?? $reimb_data['dateRejected'] ?? current_time( 'mysql' );

		// Extract fiscal year (from date or explicit field)
		$fiscal_year = $reimb_data['FiscalYear'] ?? date( 'Y', strtotime( $date_submitted ) );

		// Insert reimbursement
		$result = $wpdb->insert(
			$wpdb->prefix . 'nmda_reimbursements',
			array(
				'business_id'  => $wp_business_id,
				'user_id'      => $wp_user_id,
				'type'         => $type,
				'status'       => $status,
				'fiscal_year'  => $fiscal_year,
				'data'         => $form_data,
				'admin_notes'  => $reimb_data['AdminNotes'] ?? $reimb_data['adminNotes'] ?? '',
				'created_at'   => $date_submitted,
				'updated_at'   => $date_updated,
			)
		);

		if ( $result === false ) {
			throw new Exception( 'Failed to create reimbursement' );
		}

		return $wpdb->insert_id;
	}
}

// Register WP-CLI commands
WP_CLI::add_command( 'nmda migrate', 'NMDA_Migration_Commands' );
WP_CLI::add_command( 'nmda validate', array( 'NMDA_Migration_Commands', 'validate' ) );
