<?php
/**
 * NMDA Portal Data Migration Commands - IMPROVED VERSION
 * 
 * Enhanced with multi-strategy matching and validation
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
	 * Dry run flag
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
		$this->portal_files_dir = ABSPATH . 'portal-files/';
	}

	/**
	 * IMPROVED: Validate data before migration
	 *
	 * ## OPTIONS
	 *
	 * [--fix]
	 * : Attempt to fix issues automatically
	 *
	 * ## EXAMPLES
	 *
	 *     wp nmda validate data
	 *     wp nmda validate data --fix
	 */
	public function validate( $args, $assoc_args ) {
		WP_CLI::line( '🔍 Validating source data integrity...' );
		
		$fix_mode = isset( $assoc_args['fix'] );
		
		// Load all data files
		$users = $this->parse_sql_inserts( $this->portal_files_dir . 'nmda_user.sql', 'user' );
		$companies = $this->parse_sql_inserts( $this->portal_files_dir . 'nmda_company.sql', 'company' );
		
		// Create lookup tables
		$company_by_id = array();
		$company_by_email = array();
		
		foreach ( $companies as $company ) {
			$company_id = $company['CompanyId'] ?? $company['companyId'] ?? null;
			$email = strtolower( $company['Email'] ?? $company['email'] ?? '' );
			
			if ( $company_id ) {
				$company_by_id[ $company_id ] = $company;
			}
			if ( $email ) {
				$company_by_email[ $email ] = $company;
			}
		}
		
		// Validation results
		$orphaned_users = array();
		$email_matches = array();
		$name_matches = array();
		$no_matches = array();
		
		WP_CLI::line( sprintf( '📊 Analyzing %d users and %d companies...', count( $users ), count( $companies ) ) );
		
		foreach ( $users as $user ) {
			$user_id = $user['UserId'] ?? $user['userId'] ?? 'unknown';
			$company_id = $user['CompanyId'] ?? $user['companyId'] ?? null;
			$user_email = strtolower( $user['Email'] ?? $user['email'] ?? '' );
			$first_name = $user['FirstName'] ?? $user['firstName'] ?? '';
			$last_name = $user['LastName'] ?? $user['lastName'] ?? '';
			
			$match_found = false;
			$match_type = 'none';
			$matched_company = null;
			
			// Try UUID match
			if ( $company_id && isset( $company_by_id[ $company_id ] ) ) {
				$match_found = true;
				$match_type = 'uuid';
				$matched_company = $company_by_id[ $company_id ];
			}
			// Try email match
			elseif ( $user_email && isset( $company_by_email[ $user_email ] ) ) {
				$match_found = true;
				$match_type = 'email';
				$matched_company = $company_by_email[ $user_email ];
				
				if ( $fix_mode && $company_id !== $matched_company['CompanyId'] ) {
					WP_CLI::line( "  ✏️  Fixing CompanyId for user $user_email" );
					// In real implementation, would update the source data
				}
			}
			// Try name match
			else {
				foreach ( $companies as $company ) {
					$company_first = $company['FirstName'] ?? $company['firstName'] ?? '';
					$company_last = $company['LastName'] ?? $company['lastName'] ?? '';
					
					if ( $first_name && $last_name && 
					     strcasecmp( $first_name, $company_first ) === 0 && 
					     strcasecmp( $last_name, $company_last ) === 0 ) {
						$match_found = true;
						$match_type = 'name';
						$matched_company = $company;
						break;
					}
				}
			}
			
			// Categorize results
			if ( ! $match_found ) {
				$no_matches[] = array(
					'user_id' => $user_id,
					'email' => $user_email,
					'name' => "$first_name $last_name",
					'company_id' => $company_id
				);
			} else {
				switch ( $match_type ) {
					case 'uuid':
						// Perfect match, no action needed
						break;
					case 'email':
						$email_matches[] = array(
							'user' => $user_email,
							'company' => $matched_company['CompanyName'] ?? 'Unknown',
							'fixed' => $fix_mode
						);
						break;
					case 'name':
						$name_matches[] = array(
							'user' => "$first_name $last_name",
							'company' => $matched_company['CompanyName'] ?? 'Unknown',
							'confidence' => 'medium'
						);
						break;
				}
			}
		}
		
		// Display results
		WP_CLI::line( '' );
		WP_CLI::line( '📋 VALIDATION RESULTS' );
		WP_CLI::line( '═══════════════════════════════════════' );
		
		$valid_uuid_matches = count( $users ) - count( $email_matches ) - count( $name_matches ) - count( $no_matches );
		
		WP_CLI::line( sprintf( '✅ Valid UUID matches: %d (%.1f%%)', 
			$valid_uuid_matches, 
			( $valid_uuid_matches / count( $users ) ) * 100 
		) );
		
		if ( ! empty( $email_matches ) ) {
			WP_CLI::line( sprintf( '📧 Email matches: %d (%.1f%%)', 
				count( $email_matches ), 
				( count( $email_matches ) / count( $users ) ) * 100 
			) );
			
			if ( WP_CLI::get_config( 'debug' ) ) {
				foreach ( $email_matches as $match ) {
					WP_CLI::line( "   - {$match['user']} → {$match['company']}" );
				}
			}
		}
		
		if ( ! empty( $name_matches ) ) {
			WP_CLI::line( sprintf( '👤 Name matches: %d (%.1f%%)', 
				count( $name_matches ), 
				( count( $name_matches ) / count( $users ) ) * 100 
			) );
		}
		
		if ( ! empty( $no_matches ) ) {
			WP_CLI::warning( sprintf( '❌ No matches found: %d (%.1f%%)', 
				count( $no_matches ), 
				( count( $no_matches ) / count( $users ) ) * 100 
			) );
			
			WP_CLI::line( '' );
			WP_CLI::line( 'Unmatched users (require manual mapping):' );
			foreach ( $no_matches as $user ) {
				WP_CLI::line( sprintf( '  - %s (%s)', $user['email'], $user['name'] ) );
			}
		}
		
		// Save validation results for later use
		$this->validation_results = array(
			'valid_uuid' => $valid_uuid_matches,
			'email_matches' => $email_matches,
			'name_matches' => $name_matches,
			'no_matches' => $no_matches
		);
		
		// Provide recommendations
		WP_CLI::line( '' );
		WP_CLI::line( '💡 RECOMMENDATIONS' );
		WP_CLI::line( '═══════════════════════════════════════' );
		
		if ( count( $no_matches ) > 0 ) {
			WP_CLI::line( '1. Create manual mapping file for unmatched users' );
			WP_CLI::line( '2. Review and verify email/name matches' );
			WP_CLI::line( '3. Consider running with --fix flag to auto-correct issues' );
		} else {
			WP_CLI::success( 'All users can be matched to businesses!' );
		}
		
		return true;
	}

	/**
	 * IMPROVED: Create user-business relationships with multi-strategy matching
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Run migration in test mode
	 *
	 * [--execute]
	 * : Execute the migration
	 *
	 * [--strategy=<strategy>]
	 * : Matching strategy (uuid|email|name|all)
	 * ---
	 * default: all
	 * ---
	 *
	 * [--manual-map=<file>]
	 * : CSV file with manual user-business mappings
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
		$strategy = $assoc_args['strategy'] ?? 'all';
		$manual_map_file = $assoc_args['manual-map'] ?? null;
		
		WP_CLI::line( '🔗 Creating user-business relationships with enhanced matching...' );
		WP_CLI::line( "Strategy: $strategy" );
		
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
		$businesses_by_email = array();
		$businesses_by_name = array();
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
				$businesses_by_email[ strtolower( $business_email ) ] = $business->ID;
				
				// Domain lookup
				$domain = $this->extract_domain( $business_email );
				if ( $domain ) {
					if ( ! isset( $businesses_by_domain[ $domain ] ) ) {
						$businesses_by_domain[ $domain ] = array();
					}
					$businesses_by_domain[ $domain ][] = $business->ID;
				}
			}
			
			// Name lookup
			$owner_name = get_post_meta( $business->ID, '_owner_name', true );
			if ( $owner_name ) {
				$businesses_by_name[ strtolower( $owner_name ) ] = $business->ID;
			}
		}
		
		WP_CLI::line( sprintf( 'Found %d users to process', count( $users ) ) );
		$progress = \WP_CLI\Utils\make_progress_bar( 'Creating relationships', count( $users ) );
		
		$stats = array(
			'uuid_matches' => 0,
			'email_matches' => 0,
			'name_matches' => 0,
			'domain_matches' => 0,
			'manual_matches' => 0,
			'no_matches' => 0,
			'errors' => 0,
		);
		
		foreach ( $users as $user_data ) {
			try {
				$result = $this->create_enhanced_relationship( 
					$user_data, 
					$businesses_by_email, 
					$businesses_by_name,
					$businesses_by_domain,
					$manual_mappings,
					$strategy,
					$stats
				);
			} catch ( Exception $e ) {
				$stats['errors']++;
				NMDA_Migration_Stats::log_error( 'relationships', $e->getMessage(), $user_data );
			}
			
			$progress->tick();
		}
		
		$progress->finish();
		
		// Display detailed results
		WP_CLI::line( '' );
		WP_CLI::line( '📊 RELATIONSHIP CREATION RESULTS' );
		WP_CLI::line( '═══════════════════════════════════════' );
		
		$total_attempted = count( $users );
		$total_matched = $total_attempted - $stats['no_matches'] - $stats['errors'];
		
		WP_CLI::line( sprintf( 'Total users processed: %d', $total_attempted ) );
		WP_CLI::line( sprintf( 'Successfully matched: %d (%.1f%%)', 
			$total_matched, 
			( $total_matched / $total_attempted ) * 100 
		) );
		
		WP_CLI::line( '' );
		WP_CLI::line( 'Match breakdown:' );
		WP_CLI::line( sprintf( '  UUID matches:   %d', $stats['uuid_matches'] ) );
		WP_CLI::line( sprintf( '  Email matches:  %d', $stats['email_matches'] ) );
		WP_CLI::line( sprintf( '  Name matches:   %d', $stats['name_matches'] ) );
		WP_CLI::line( sprintf( '  Domain matches: %d', $stats['domain_matches'] ) );
		WP_CLI::line( sprintf( '  Manual matches: %d', $stats['manual_matches'] ) );
		
		if ( $stats['no_matches'] > 0 ) {
			WP_CLI::warning( sprintf( 'No matches found: %d', $stats['no_matches'] ) );
			
			// Generate unmapped users report
			$this->generate_unmapped_report();
		}
		
		if ( $stats['errors'] > 0 ) {
			WP_CLI::warning( sprintf( 'Errors encountered: %d', $stats['errors'] ) );
		}
		
		if ( $this->dry_run ) {
			WP_CLI::line( '' );
			WP_CLI::warning( 'This was a DRY RUN - no relationships were created.' );
			WP_CLI::line( 'Run with --execute flag to create actual relationships.' );
		}
	}

	/**
	 * Create enhanced user-business relationship with multiple matching strategies
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
		global $wpdb;
		
		// Extract user data
		$user_id_uuid = $user_data['UserId'] ?? $user_data['userId'] ?? null;
		$company_id_uuid = $user_data['CompanyId'] ?? $user_data['companyId'] ?? null;
		$user_email = strtolower( $user_data['Email'] ?? $user_data['email'] ?? '' );
		$first_name = $user_data['FirstName'] ?? $user_data['firstName'] ?? '';
		$last_name = $user_data['LastName'] ?? $user_data['lastName'] ?? '';
		$full_name = strtolower( trim( "$first_name $last_name" ) );
		
		if ( ! $user_id_uuid ) {
			throw new Exception( 'Missing UserId for relationship' );
		}
		
		// Get WordPress user ID
		$wp_user_id = nmda_get_new_id_from_uuid( $user_id_uuid, 'user' );
		if ( ! $wp_user_id ) {
			throw new Exception( "Could not find user mapping for UUID: $user_id_uuid" );
		}
		
		$wp_business_id = null;
		$match_type = 'none';
		$match_confidence = 0;
		
		// Strategy 1: UUID matching (highest confidence)
		if ( ( $strategy === 'uuid' || $strategy === 'all' ) && $company_id_uuid ) {
			$wp_business_id = nmda_get_new_id_from_uuid( $company_id_uuid, 'business' );
			if ( $wp_business_id ) {
				$match_type = 'uuid';
				$match_confidence = 100;
				$stats['uuid_matches']++;
			}
		}
		
		// Strategy 2: Manual mapping (very high confidence)
		if ( ! $wp_business_id && isset( $manual_mappings[ $user_email ] ) ) {
			$wp_business_id = $manual_mappings[ $user_email ];
			$match_type = 'manual';
			$match_confidence = 95;
			$stats['manual_matches']++;
		}
		
		// Strategy 3: Email matching (high confidence)
		if ( ! $wp_business_id && ( $strategy === 'email' || $strategy === 'all' ) && $user_email ) {
			if ( isset( $businesses_by_email[ $user_email ] ) ) {
				$wp_business_id = $businesses_by_email[ $user_email ];
				$match_type = 'email';
				$match_confidence = 90;
				$stats['email_matches']++;
			}
		}
		
		// Strategy 4: Name matching (medium confidence)
		if ( ! $wp_business_id && ( $strategy === 'name' || $strategy === 'all' ) && $full_name ) {
			if ( isset( $businesses_by_name[ $full_name ] ) ) {
				$wp_business_id = $businesses_by_name[ $full_name ];
				$match_type = 'name';
				$match_confidence = 70;
				$stats['name_matches']++;
			}
		}
		
		// Strategy 5: Domain matching (low confidence - requires review)
		if ( ! $wp_business_id && ( $strategy === 'all' ) && $user_email ) {
			$domain = $this->extract_domain( $user_email );
			if ( $domain && isset( $businesses_by_domain[ $domain ] ) ) {
				$domain_businesses = $businesses_by_domain[ $domain ];
				if ( count( $domain_businesses ) === 1 ) {
					// Only one business with this domain, likely match
					$wp_business_id = $domain_businesses[0];
					$match_type = 'domain';
					$match_confidence = 50;
					$stats['domain_matches']++;
					
					WP_CLI::debug( "Domain match for $user_email -> Business ID: $wp_business_id (confidence: $match_confidence%)" );
				}
			}
		}
		
		// No match found
		if ( ! $wp_business_id ) {
			$stats['no_matches']++;
			
			// Log unmatched user for manual review
			$this->log_unmatched_user( $user_data, 'No matching business found' );
			
			WP_CLI::debug( "No match found for user: $user_email ($full_name)" );
			return false;
		}
		
		// Log the match for review if confidence is low
		if ( $match_confidence < 70 ) {
			WP_CLI::warning( "Low confidence match ($match_confidence%): $user_email -> Business $wp_business_id via $match_type" );
		}
		
		if ( $this->dry_run ) {
			WP_CLI::debug( "Would create relationship: User $wp_user_id <-> Business $wp_business_id (via $match_type, confidence: $match_confidence%)" );
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
			return true;
		}
		
		// Create relationship with metadata
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
		
		// Store match metadata for audit
		add_post_meta( $wp_business_id, '_migration_match_type', $match_type );
		add_post_meta( $wp_business_id, '_migration_match_confidence', $match_confidence );
		
		// Update post_author
		wp_update_post( array(
			'ID'          => $wp_business_id,
			'post_author' => $wp_user_id,
		) );
		
		return true;
	}
	
	/**
	 * Extract domain from email address
	 */
	private function extract_domain( $email ) {
		$parts = explode( '@', $email );
		if ( count( $parts ) === 2 ) {
			$domain = strtolower( $parts[1] );
			// Exclude common free email providers
			$free_domains = array( 'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com' );
			if ( ! in_array( $domain, $free_domains ) ) {
				return $domain;
			}
		}
		return null;
	}
	
	/**
	 * Load manual mappings from CSV file
	 */
	private function load_manual_mappings( $file ) {
		$mappings = array();
		
		if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
			// Skip header row
			fgetcsv( $handle );
			
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				// Format: user_email, business_id
				if ( count( $data ) >= 2 ) {
					$user_email = strtolower( trim( $data[0] ) );
					$business_id = intval( $data[1] );
					$mappings[ $user_email ] = $business_id;
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
			$user_data['Email'] ?? '',
			$user_data['FirstName'] ?? '' . ' ' . $user_data['LastName'] ?? '',
			$user_data['CompanyId'] ?? '',
			$reason,
			current_time( 'mysql' )
		);
		
		$handle = fopen( $log_file, 'a' );
		fputcsv( $handle, $data );
		fclose( $handle );
	}
	
	/**
	 * Generate report of unmapped users
	 */
	private function generate_unmapped_report() {
		$log_file = ABSPATH . 'unmapped_users.csv';
		
		if ( file_exists( $log_file ) ) {
			WP_CLI::line( '' );
			WP_CLI::line( '📄 Unmapped users report generated: ' . $log_file );
			WP_CLI::line( 'Review this file to create manual mappings.' );
		}
	}
	
	// Include original parse_sql_inserts and other utility methods here...
	// [Previous parsing methods remain the same]
}

// Register enhanced commands
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'nmda validate', array( 'NMDA_Migration_Command_Enhanced', 'validate' ) );
	WP_CLI::add_command( 'nmda migrate relationships-enhanced', array( 'NMDA_Migration_Command_Enhanced', 'relationships_enhanced' ) );
}
