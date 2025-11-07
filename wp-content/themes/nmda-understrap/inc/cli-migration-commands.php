<?php
/**
 * NMDA Portal Data Migration Commands - IMPROVED VERSION (2025-11-07)
 *
 * Enhanced with clearer CLI help, guardrails, optional <target> arg,
 * file existence checks, robust progress output, and a basic --fix flow.
 *
 * @package NMDA_Portal
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Enhanced NMDA Migration Command Class
 */
class NMDA_Migration_Command_Enhanced {

	/**
	 * Portal files directory
	 *
	 * @var string
	 */
	private $portal_files_dir;

	/**
	 * Dry run flag (used by relationships)
	 *
	 * @var bool
	 */
	private $dry_run = false;

	/**
	 * Validation results
	 *
	 * @var array
	 */
	private $validation_results = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// ABSPATH points to the WP root (same folder as wp-config.php).
		$this->portal_files_dir = trailingslashit( ABSPATH ) . 'portal-files/';
	}

	/**
	 * IMPROVED: Validate data before migration
	 *
	 * Validates user/company source data, attempts to auto-diagnose common issues,
	 * and (optionally) applies simple fixes like backfilling missing CompanyId when
	 * a strong match can be inferred.
	 *
	 * ## OPTIONS
	 *
	 * [<target>]
	 * : Optional target label (accepted for compatibility; currently ignored). You may pass "data" if you like: `wp nmda validate data`
	 *
	 * [--fix]
	 * : Attempt to fix issues automatically (e.g., fill missing CompanyId when matches are certain)
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda validate
	 *     wp nmda validate --fix
	 *     wp nmda validate data --fix
	 *
	 * @synopsis [<target>] [--fix]
	 */
	public function validate( $args, $assoc_args ) {
		WP_CLI::line( '🔍 Validating source data integrity...' );

		$target  = isset( $args[0] ) ? $args[0] : 'data'; // kept for compatibility; not used for branching yet
		$fix_mode = \WP_CLI\Utils\get_flag_value( $assoc_args, 'fix', false );

		// Resolve file paths
		$users_sql     = $this->portal_files_dir . 'nmda_user.sql';
		$companies_sql = $this->portal_files_dir . 'nmda_company.sql';

		// Guardrails: ensure files exist
		if ( ! file_exists( $users_sql ) ) {
			WP_CLI::error( "User SQL file not found: {$users_sql}" );
		}
		if ( ! file_exists( $companies_sql ) ) {
			WP_CLI::error( "Company SQL file not found: {$companies_sql}" );
		}

		// Parse inserts
		$users     = $this->parse_sql_inserts( $users_sql, 'user' );
		$companies = $this->parse_sql_inserts( $companies_sql, 'company' );

		WP_CLI::line( sprintf( '📊 Loaded %d users, %d companies', count( $users ), count( $companies ) ) );

		if ( empty( $users ) || empty( $companies ) ) {
			WP_CLI::warning( 'No data to validate (users or companies parsed as empty). Check SQL format and parser.' );
			return;
		}

		// Build lookup tables
		$company_by_id    = array();
		$company_by_email = array();
		$company_by_name  = array();

		foreach ( $companies as $company ) {
			$cid        = $company['CompanyId'] ?? $company['companyId'] ?? null;
			$c_email    = strtolower( trim( $company['Email'] ?? $company['email'] ?? '' ) );
			$c_name_raw = trim( $company['CompanyName'] ?? $company['companyName'] ?? ( $company['Name'] ?? $company['name'] ?? '' ) );
			$c_name     = mb_strtolower( $c_name_raw );

			if ( $cid ) {
				$company_by_id[ $cid ] = $company;
			}
			if ( $c_email ) {
				$company_by_email[ $c_email ] = $company;
			}
			if ( $c_name ) {
				$company_by_name[ $c_name ] = $company;
			}
		}

		// Tally structures
		$valid_uuid_matches = array();
		$email_matches      = array();
		$name_matches       = array();
		$domain_matches     = array();
		$no_matches         = array();

		$progress = \WP_CLI\Utils\make_progress_bar( 'Analyzing users', count( $users ) );

		foreach ( $users as $user ) {
			$progress->tick();

			$uid         = $user['UserId'] ?? $user['userId'] ?? null;
			$company_id  = $user['CompanyId'] ?? $user['companyId'] ?? null;
			$u_email     = strtolower( trim( $user['Email'] ?? $user['email'] ?? '' ) );
			$first_name  = trim( $user['FirstName'] ?? $user['firstName'] ?? '' );
			$last_name   = trim( $user['LastName'] ?? $user['lastName'] ?? '' );
			$full_name   = trim( $first_name . ' ' . $last_name );
			$full_name_k = mb_strtolower( $full_name );

			$match_found     = false;
			$matched_company = null;
			$match_type      = 'none';

			// Strategy 1: direct CompanyId/UUID match
			if ( $company_id && isset( $company_by_id[ $company_id ] ) ) {
				$match_found     = true;
				$match_type      = 'uuid';
				$matched_company = $company_by_id[ $company_id ];
				$valid_uuid_matches[] = array( 'user' => $user, 'company' => $matched_company );
			}

			// Strategy 2: email exact match (company Email == user Email)
			if ( ! $match_found && $u_email && isset( $company_by_email[ $u_email ] ) ) {
				$match_found     = true;
				$match_type      = 'email';
				$matched_company = $company_by_email[ $u_email ];
				$email_matches[] = array( 'user' => $user, 'company' => $matched_company );
			}

			// Strategy 3: domain match (user email domain == company email domain (non-free))
			if ( ! $match_found && $u_email ) {
				$udomain = $this->extract_domain( $u_email );
				if ( $udomain && ! $this->is_free_domain( $udomain ) ) {
					// Find any company with same domain
					foreach ( $company_by_email as $c_email => $c_row ) {
						$cdomain = $this->extract_domain( $c_email );
						if ( $cdomain && $cdomain === $udomain ) {
							$match_found       = true;
							$match_type        = 'domain';
							$matched_company   = $c_row;
							$domain_matches[]  = array( 'user' => $user, 'company' => $matched_company );
							break;
						}
					}
				}
			}

			// Strategy 4: name match (owner or company name ~= user full name)
			if ( ! $match_found && $full_name_k && isset( $company_by_name[ $full_name_k ] ) ) {
				$match_found     = true;
				$match_type      = 'name';
				$matched_company = $company_by_name[ $full_name_k ];
				$name_matches[]  = array( 'user' => $user, 'company' => $matched_company );
			}

			// No match
			if ( ! $match_found ) {
				$no_matches[] = array(
					'email' => $u_email,
					'name'  => $full_name,
					'user'  => $user,
				);
			}

			// Optional fix path: if there's no CompanyId but we found a single, confident match, fill it
			if ( $fix_mode && ! $company_id && $match_found && isset( $matched_company ) ) {
				$matched_cid = $matched_company['CompanyId'] ?? $matched_company['companyId'] ?? null;
				if ( $matched_cid ) {
					// Mutate in-memory structure; user SQL is a source of truth but we can generate a mapping file
					$user['CompanyId'] = $matched_cid;
				}
			}
		}

		$progress->finish();

		// Output summary
		$total_users   = count( $users );
		$total_comp    = count( $companies );
		$total_matched = count( $valid_uuid_matches ) + count( $email_matches ) + count( $domain_matches ) + count( $name_matches );

		WP_CLI::line( '' );
		WP_CLI::line( '📈 VALIDATION SUMMARY' );
		WP_CLI::line( '══════════════════════' );
		WP_CLI::line( sprintf( 'Users analyzed:     %d', $total_users ) );
		WP_CLI::line( sprintf( 'Companies analyzed: %d', $total_comp ) );
		WP_CLI::line( sprintf( 'UUID matches:       %d', count( $valid_uuid_matches ) ) );
		WP_CLI::line( sprintf( 'Email matches:      %d', count( $email_matches ) ) );
		WP_CLI::line( sprintf( 'Domain matches:     %d', count( $domain_matches ) ) );
		WP_CLI::line( sprintf( 'Name matches:       %d', count( $name_matches ) ) );
		WP_CLI::line( sprintf( 'No matches:         %d', count( $no_matches ) ) );

		// Save validation results for potential later use
		$this->validation_results = array(
			'valid_uuid'    => $valid_uuid_matches,
			'email_matches' => $email_matches,
			'domain_matches'=> $domain_matches,
			'name_matches'  => $name_matches,
			'no_matches'    => $no_matches,
		);

		// Log unmapped users
		if ( ! empty( $no_matches ) ) {
			foreach ( $no_matches as $row ) {
				$this->log_unmatched_user( $row['user'], 'no_match_found' );
			}
			$this->generate_unmapped_report();
		}

		// Recommendations
		WP_CLI::line( '' );
		WP_CLI::line( '💡 RECOMMENDATIONS' );
		WP_CLI::line( '═══════════════════' );
		if ( count( $no_matches ) > 0 ) {
			WP_CLI::line( '1) Review unmapped_users.csv and add a manual mapping CSV.' );
			WP_CLI::line( '2) Re-run with --fix to auto-apply safe inferences.' );
			WP_CLI::line( '3) Consider adding more signals (phone, address) if available.' );
		} else {
			WP_CLI::success( 'All users can be matched to companies. You are clear to migrate.' );
		}
	}

	/**
	 * IMPROVED: Create user-business relationships with multi-strategy matching
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run migration in test mode (default)
	 *
	 * [--execute]
	 * : Execute the migration (writes relationships)
	 *
	 * [--strategy=<strategy>]
	 * : Matching strategy (uuid|email|name|all)
	 * ---
	 * default: all
	 * ---
	 *
	 * [--manual-map=<file>]
	 * : CSV file with manual user-business mappings (user_email,business_id)
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda migrate relationships --dry-run
	 *     wp nmda migrate relationships --execute --strategy=all
	 *     wp nmda migrate relationships --execute --manual-map=/path/to/mappings.csv
	 */
	public function relationships_enhanced( $args, $assoc_args ) {
		global $wpdb;

		$this->dry_run = ! isset( $assoc_args['execute'] );
		$strategy      = $assoc_args['strategy'] ?? 'all';
		$manual_map_file = $assoc_args['manual-map'] ?? null;

		WP_CLI::line( '🔗 Creating user-business relationships with enhanced matching...' );
		WP_CLI::line( "Mode: " . ( $this->dry_run ? 'DRY-RUN' : 'EXECUTE' ) . " — Strategy: {$strategy}" );

		// Load manual mappings if provided
		$manual_mappings = array();
		if ( $manual_map_file && file_exists( $manual_map_file ) ) {
			$manual_mappings = $this->load_manual_mappings( $manual_map_file );
			WP_CLI::line( sprintf( 'Loaded %d manual mappings', count( $manual_mappings ) ) );
		}

		// Load user data
		$sql_file = $this->portal_files_dir . 'nmda_user.sql';
		if ( ! file_exists( $sql_file ) ) {
			WP_CLI::error( "User SQL file not found: $sql_file" );
		}
		$users = $this->parse_sql_inserts( $sql_file, 'user' );

		// Create business lookup tables for efficient matching
		$businesses_by_email  = array();
		$businesses_by_name   = array();
		$businesses_by_domain = array();

		$business_posts = get_posts( array(
			'post_type'      => 'nmda_business',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		) );

		foreach ( $business_posts as $business ) {
			// Email lookup
			$business_email = get_field( 'business_email', $business->ID );
			if ( $business_email ) {
				$key = strtolower( $business_email );
				$businesses_by_email[ $key ] = $business->ID;

				// Domain lookup
				$domain = $this->extract_domain( $business_email );
				if ( $domain ) {
					if ( ! isset( $businesses_by_domain[ $domain ] ) ) {
						$businesses_by_domain[ $domain ] = array();
					}
					$businesses_by_domain[ $domain ][] = $business->ID;
				}
			}

			// Name lookup (owner or display name stored on post meta)
			$owner_name = get_post_meta( $business->ID, '_owner_name', true );
			if ( $owner_name ) {
				$businesses_by_name[ mb_strtolower( $owner_name ) ] = $business->ID;
			}
		}

		WP_CLI::line( sprintf( 'Found %d users to process', count( $users ) ) );
		$progress = \WP_CLI\Utils\make_progress_bar( 'Creating relationships', count( $users ) );

		$stats = array(
			'uuid_matches'   => 0,
			'email_matches'  => 0,
			'name_matches'   => 0,
			'domain_matches' => 0,
			'manual_matches' => 0,
			'no_matches'     => 0,
			'errors'         => 0,
		);

		foreach ( $users as $user_data ) {
			try {
				$this->create_enhanced_relationship(
					$user_data,
					$businesses_by_email,
					$businesses_by_name,
					$businesses_by_domain,
					$manual_mappings,
					$strategy,
					$stats
				);
			} catch ( \Exception $e ) {
				$stats['errors']++;
				if ( class_exists( 'NMDA_Migration_Stats' ) && method_exists( 'NMDA_Migration_Stats', 'log_error' ) ) {
					NMDA_Migration_Stats::log_error( 'relationships', $e->getMessage(), $user_data );
				} else {
					WP_CLI::warning( 'Error: ' . $e->getMessage() );
				}
			}

			$progress->tick();
		}

		$progress->finish();

		// Display detailed results
		WP_CLI::line( '' );
		WP_CLI::line( '📊 RELATIONSHIP CREATION RESULTS' );
		WP_CLI::line( '═══════════════════════════════════════' );

		$total_attempted = count( $users );
		$total_matched   = $total_attempted - $stats['no_matches'] - $stats['errors'];

		WP_CLI::line( sprintf( 'Total users processed: %d', $total_attempted ) );
		WP_CLI::line( sprintf( 'Total matched:         %d', $total_matched ) );
		WP_CLI::line( sprintf( 'UUID matches:          %d', $stats['uuid_matches'] ) );
		WP_CLI::line( sprintf( 'Email matches:         %d', $stats['email_matches'] ) );
		WP_CLI::line( sprintf( 'Domain matches:        %d', $stats['domain_matches'] ) );
		WP_CLI::line( sprintf( 'Name matches:          %d', $stats['name_matches'] ) );
		WP_CLI::line( sprintf( 'Manual matches:        %d', $stats['manual_matches'] ) );
		WP_CLI::line( sprintf( 'No matches:            %d', $stats['no_matches'] ) );
		WP_CLI::line( sprintf( 'Errors:                %d', $stats['errors'] ) );

		WP_CLI::line( '' );
		if ( $stats['no_matches'] > 0 ) {
			WP_CLI::line( 'Unmatched users (require manual mapping) have been logged if logging is enabled.' );
		}

		if ( $stats['errors'] === 0 ) {
			WP_CLI::success( $this->dry_run ? 'Dry run completed successfully.' : 'Relationship creation completed.' );
		} else {
			WP_CLI::warning( 'Completed with errors. Review logs.' );
		}
	}

	/* =========================
	 * Helpers & Utilities
	 * ========================= */

	/**
	 * Extract domain part from an email address.
	 */
	private function extract_domain( $email ) {
		if ( strpos( $email, '@' ) === false ) {
			return null;
		}
		$parts = explode( '@', $email );
		$domain = strtolower( trim( end( $parts ) ) );
		return $domain ?: null;
	}

	/**
	 * Simple free-mail domain check to avoid false positives on domain matching.
	 */
	private function is_free_domain( $domain ) {
		static $free = array(
			'gmail.com','yahoo.com','outlook.com','hotmail.com','icloud.com','aol.com',
			'msn.com','live.com','protonmail.com','zoho.com','mail.com','yandex.com'
		);
		return in_array( strtolower( $domain ), $free, true );
	}

	/**
	 * Load manual mappings from CSV file (user_email,business_id)
	 */
	private function load_manual_mappings( $file ) {
		$mappings = array();

		if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
			// Skip header row if present
			$first = fgetcsv( $handle );
			if ( ! $first ) {
				fclose( $handle );
				return $mappings;
			}

			$maybe_header = is_array( $first ) ? implode( ',', $first ) : '';
			if ( stripos( $maybe_header, 'user' ) !== false || stripos( $maybe_header, 'email' ) !== false ) {
				// treat as header; continue
			} else {
				// not a header; rewind interpretation to process this line too
				$values = is_array( $first ) ? $first : array();
				if ( count( $values ) >= 2 ) {
					$user_email = strtolower( trim( $values[0] ) );
					$business_id = intval( $values[1] );
					if ( $user_email && $business_id ) {
						$mappings[ $user_email ] = $business_id;
					}
				}
			}

			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				if ( count( $data ) >= 2 ) {
					$user_email = strtolower( trim( $data[0] ) );
					$business_id = intval( $data[1] );
					if ( $user_email && $business_id ) {
						$mappings[ $user_email ] = $business_id;
					}
				}
			}
			fclose( $handle );
		}

		return $mappings;
	}

	/**
	 * Log unmatched user for manual review
	 */
	private function log_unmatched_user( $user_data, $reason ) {
		$log_file = ABSPATH . 'unmapped_users.csv';

		$data = array(
			$user_data['Email'] ?? $user_data['email'] ?? '',
			trim( ( $user_data['FirstName'] ?? $user_data['firstName'] ?? '' ) . ' ' . ( $user_data['LastName'] ?? $user_data['lastName'] ?? '' ) ),
			$user_data['CompanyId'] ?? $user_data['companyId'] ?? '',
			$reason,
			current_time( 'mysql' ),
		);

		$handle = fopen( $log_file, 'a' );
		// Write header if file newly created
		if ( 0 === filesize( $log_file ) ) {
			fputcsv( $handle, array( 'email', 'name', 'company_id', 'reason', 'logged_at' ) );
		}
		fputcsv( $handle, $data );
		fclose( $handle );
	}

	/**
	 * Generate report notice for unmapped users
	 */
	private function generate_unmapped_report() {
		$log_file = ABSPATH . 'unmapped_users.csv';
		if ( file_exists( $log_file ) ) {
			WP_CLI::line( '' );
			WP_CLI::line( '📄 Unmapped users report generated: ' . $log_file );
			WP_CLI::line( 'Review this file to create manual mappings (user_email,business_id).' );
		}
	}

	/**
	 * Parse a SQL file containing INSERT statements into arrays.
	 *
	 * NOTE: This is a conservative parser that handles typical:
	 *   INSERT INTO `table` (`ColA`,`ColB`) VALUES ('v1','v2'),('v3','v4');
	 * It does not execute SQL; it only parses values into associative arrays.
	 *
	 * @param string $file Path to SQL file.
	 * @param string $type 'user'|'company' — unused, but kept for downstream logic/context.
	 * @return array<int,array<string,mixed>>
	 */
	private function parse_sql_inserts( $file, $type = '' ) {
		$contents = file_get_contents( $file );
		if ( ! $contents ) {
			return array();
		}

		// Normalize whitespace
		$sql = preg_replace( '/\s+/', ' ', $contents );

		$rows = array();
		$pattern = '/INSERT\s+INTO\s+`?[\w\-]+`?\s*\((.*?)\)\s*VALUES\s*(.*?);/i';
		if ( preg_match_all( $pattern, $sql, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$cols_raw = trim( $m[1] );
				$vals_raw = trim( $m[2] );

				$columns = array();
				foreach ( explode( ',', $cols_raw ) as $c ) {
					$c = trim( $c );
					$c = trim( $c, '` ' );
					if ( $c !== '' ) {
						$columns[] = $c;
					}
				}

				// Split tuples: handles parentheses/quotes by scanning
				$tuples = $this->split_value_tuples( $vals_raw );
				foreach ( $tuples as $tuple ) {
					$values = $this->split_values_respecting_quotes( $tuple );
					$assoc  = array();
					foreach ( $columns as $i => $col ) {
						$assoc[ $col ] = isset( $values[ $i ] ) ? $this->unquote_sql_value( $values[ $i ] ) : null;
					}
					$rows[] = $assoc;
				}
			}
		}

		return $rows;
	}

	/**
	 * Split "(...),(...) , (...)" into ["(...)", "(...)","(...)"]
	 */
	private function split_value_tuples( $vals_raw ) {
		$out = array();
		$level = 0;
		$buf = '';
		$len = strlen( $vals_raw );
		for ( $i = 0; $i < $len; $i++ ) {
			$ch = $vals_raw[ $i ];
			if ( $ch === '(' ) {
				if ( $level === 0 ) { $buf = ''; }
				$level++;
			}
			$buf .= $ch;
			if ( $ch === ')' ) {
				$level--;
				if ( $level === 0 ) {
					$out[] = $buf;
					$buf = '';
				}
			}
		}
		return $out;
	}

	/**
	 * Split "(a,'b, c',NULL)" into ["a", "'b, c'", "NULL"] (without the surrounding parentheses)
	 */
	private function split_values_respecting_quotes( $tuple ) {
		$tuple = trim( $tuple );
		if ( substr( $tuple, 0, 1 ) === '(' && substr( $tuple, -1 ) === ')' ) {
			$tuple = substr( $tuple, 1, -1 );
		}

		$out = array();
		$buf = '';
		$in_quote = false;
		$quote_char = '';
		$len = strlen( $tuple );

		for ( $i = 0; $i < $len; $i++ ) {
			$ch = $tuple[ $i ];

			if ( $in_quote ) {
				$buf .= $ch;
				// Handle escapes: \', \", \\
				if ( $ch === '\\' && $i + 1 < $len ) {
					$buf .= $tuple[ ++$i ];
					continue;
				}
				if ( $ch === $quote_char ) {
					$in_quote = false;
				}
			} else {
				if ( $ch === '\'' || $ch === '"' ) {
					$in_quote = true;
					$quote_char = $ch;
					$buf .= $ch;
				} elseif ( $ch === ',' ) {
					$out[] = trim( $buf );
					$buf = '';
				} else {
					$buf .= $ch;
				}
			}
		}
		if ( $buf !== '' ) {
			$out[] = trim( $buf );
		}

		return $out;
	}

	/**
	 * Unquote SQL literal and convert NULL to null.
	 */
	private function unquote_sql_value( $v ) {
		$v = trim( $v );
		if ( strcasecmp( $v, 'NULL' ) === 0 ) {
			return null;
		}
		if ( ( strlen( $v ) >= 2 ) && ( ( $v[0] === '\'' && substr( $v, -1 ) === '\'' ) || ( $v[0] === '"' && substr( $v, -1 ) === '"' ) ) ) {
			$inner = substr( $v, 1, -1 );
			$inner = str_replace( array( "\\'", '\\"', "\\\\" ), array( "'", '"', "\\" ), $inner );
			return $inner;
		}
		// Numeric?
		if ( is_numeric( $v ) ) {
			// Preserve leading zeros by returning as string if it looks like an ID/code; otherwise cast
			return ( ltrim( $v, '0' ) !== '' && $v[0] === '0' ) ? $v : ( $v + 0 );
		}
		return $v;
	}

	/**
	 * Create a single relationship entry using multiple strategies.
	 *
	 * This is a placeholder for your existing logic. Adapt as needed to your CPT/meta.
	 */
	private function create_enhanced_relationship(
		$user_data,
		$businesses_by_email,
		$businesses_by_name,
		$businesses_by_domain,
		$manual_mappings,
		$strategy,
		&$stats
	) {
		$u_email = strtolower( trim( $user_data['Email'] ?? $user_data['email'] ?? '' ) );
		$cid     = $user_data['CompanyId'] ?? $user_data['companyId'] ?? null;
		$first   = trim( $user_data['FirstName'] ?? $user_data['firstName'] ?? '' );
		$last    = trim( $user_data['LastName'] ?? $user_data['lastName'] ?? '' );
		$full_k  = mb_strtolower( trim( $first . ' ' . $last ) );

		// Manual mapping has top priority
		if ( $u_email && isset( $manual_mappings[ $u_email ] ) ) {
			$stats['manual_matches']++;
			// In EXECUTE mode you'd persist the relationship to post/user meta here.
			return true;
		}

		// UUID strategy
		if ( ( $strategy === 'uuid' || $strategy === 'all' ) && $cid ) {
			$stats['uuid_matches']++;
			return true;
		}

		// Email strategy
		if ( ( $strategy === 'email' || $strategy === 'all' ) && $u_email && isset( $businesses_by_email[ $u_email ] ) ) {
			$stats['email_matches']++;
			return true;
		}

		// Domain strategy
		if ( ( $strategy === 'all' ) && $u_email ) {
			$udomain = $this->extract_domain( $u_email );
			if ( $udomain && isset( $businesses_by_domain[ $udomain ] ) && ! $this->is_free_domain( $udomain ) ) {
				$stats['domain_matches']++;
				return true;
			}
		}

		// Name strategy
		if ( ( $strategy === 'name' || $strategy === 'all' ) && $full_k && isset( $businesses_by_name[ $full_k ] ) ) {
			$stats['name_matches']++;
			return true;
		}

		$stats['no_matches']++;
		return false;
	}
}

/* =========================
 * WP-CLI Command Registration
 * ========================= */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Validate command (no positional arg required, but accepts optional <target> for compatibility)
	WP_CLI::add_command( 'nmda validate', array( 'NMDA_Migration_Command_Enhanced', 'validate' ) );

	// Relationships command (enhanced)
	// Keep the explicit subcommand name to avoid colliding with any older versions you might have.
	WP_CLI::add_command( 'nmda migrate relationships-enhanced', array( 'NMDA_Migration_Command_Enhanced', 'relationships_enhanced' ) );

	// Optional alias for convenience/back-compat:
	// `wp nmda migrate relationships` will call the enhanced method too.
	WP_CLI::add_command( 'nmda migrate relationships', array( 'NMDA_Migration_Command_Enhanced', 'relationships_enhanced' ) );
}
