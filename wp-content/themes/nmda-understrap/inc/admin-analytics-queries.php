<?php
/**
 * Analytics Dashboard - Database Query Functions
 *
 * @package NMDA_Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Verify that custom analytics tables exist
 *
 * @return array Table existence status
 */
function nmda_verify_analytics_tables() {
	global $wpdb;

	$tables = array(
		'user_business'      => $wpdb->prefix . 'nmda_user_business',
		'business_addresses' => $wpdb->prefix . 'nmda_business_addresses',
		'reimbursements'     => $wpdb->prefix . 'nmda_reimbursements',
		'communications'     => $wpdb->prefix . 'nmda_communications',
	);

	$status = array();
	foreach ( $tables as $key => $table_name ) {
		$status[ $key ] = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) ) === $table_name;
	}

	return $status;
}

/**
 * Check if a specific analytics table exists
 *
 * @param string $table_key Table key (user_business, business_addresses, reimbursements, communications)
 * @return bool True if table exists
 */
function nmda_analytics_table_exists( $table_key ) {
	static $cache = null;

	if ( $cache === null ) {
		$cache = nmda_verify_analytics_tables();
	}

	return $cache[ $table_key ] ?? false;
}

/**
 * Get user statistics
 *
 * @param string $date_range Date range filter ('all', 'this_month', 'this_year', 'last_30_days', 'last_12_months')
 * @return array User statistics
 */
function nmda_get_user_stats( $date_range = 'all' ) {
	global $wpdb;

	// Total users
	$total_users = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );

	// New users this month
	$new_this_month = $wpdb->get_var( "
		SELECT COUNT(*)
		FROM {$wpdb->users}
		WHERE user_registered >= DATE_FORMAT(NOW(), '%Y-%m-01')
	" );

	// Active users (logged in last 30 days)
	$thirty_days_ago = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
	$active_users = $wpdb->get_var( $wpdb->prepare( "
		SELECT COUNT(DISTINCT user_id)
		FROM {$wpdb->usermeta}
		WHERE meta_key = 'nmda_last_login'
		AND meta_value >= %s
	", $thirty_days_ago ) );

	// Users with businesses
	$users_with_businesses = 0;
	if ( nmda_analytics_table_exists( 'user_business' ) ) {
		$users_with_businesses = $wpdb->get_var( "
			SELECT COUNT(DISTINCT user_id)
			FROM {$wpdb->prefix}nmda_user_business
			WHERE status = 'active'
		" );
	}

	// Previous month for growth calculation
	$new_last_month = $wpdb->get_var( "
		SELECT COUNT(*)
		FROM {$wpdb->users}
		WHERE user_registered >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
		AND user_registered < DATE_FORMAT(NOW(), '%Y-%m-01')
	" );

	// Calculate growth rate
	$growth_rate = 0;
	if ( $new_last_month > 0 ) {
		$growth_rate = ( ( $new_this_month - $new_last_month ) / $new_last_month ) * 100;
	} elseif ( $new_this_month > 0 ) {
		$growth_rate = 100; // 100% growth if no users last month
	}

	return array(
		'total_users'             => intval( $total_users ),
		'new_this_month'          => intval( $new_this_month ),
		'active_users'            => intval( $active_users ),
		'users_with_businesses'   => intval( $users_with_businesses ),
		'growth_rate'             => round( $growth_rate, 1 ),
	);
}

/**
 * Get user registrations by month (last 12 months)
 *
 * @return array Monthly registration data
 */
function nmda_get_user_registrations_by_month() {
	global $wpdb;

	return $wpdb->get_results( "
		SELECT
			DATE_FORMAT(user_registered, '%Y-%m') as month,
			DATE_FORMAT(user_registered, '%b %Y') as month_label,
			COUNT(*) as count
		FROM {$wpdb->users}
		WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
		GROUP BY DATE_FORMAT(user_registered, '%Y-%m')
		ORDER BY month ASC
	", ARRAY_A );
}

/**
 * Get most active users
 *
 * @param int $limit Number of users to return
 * @return array Most active users
 */
function nmda_get_most_active_users( $limit = 10 ) {
	global $wpdb;

	// Check if table exists
	if ( ! nmda_analytics_table_exists( 'user_business' ) ) {
		return array();
	}

	// Get users with business associations and login activity
	$users = $wpdb->get_results( $wpdb->prepare( "
		SELECT
			u.ID,
			u.display_name,
			u.user_email,
			u.user_registered,
			COUNT(DISTINCT ub.business_id) as business_count,
			(SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'nmda_last_login' LIMIT 1) as last_login
		FROM {$wpdb->users} u
		LEFT JOIN {$wpdb->prefix}nmda_user_business ub ON u.ID = ub.user_id AND ub.status = 'active'
		GROUP BY u.ID
		HAVING business_count > 0
		ORDER BY last_login DESC, business_count DESC
		LIMIT %d
	", $limit ), ARRAY_A );

	return $users ?? array();
}

/**
 * Get inactive users (no login in X days)
 *
 * @param int $days Number of days to consider inactive
 * @param int $limit Number of users to return
 * @return array Inactive users
 */
function nmda_get_inactive_users( $days = 90, $limit = 50 ) {
	global $wpdb;

	// Check if table exists
	if ( ! nmda_analytics_table_exists( 'user_business' ) ) {
		return array();
	}

	$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

	$users = $wpdb->get_results( $wpdb->prepare( "
		SELECT
			u.ID,
			u.display_name,
			u.user_email,
			u.user_registered,
			(SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'nmda_last_login' LIMIT 1) as last_login,
			COUNT(DISTINCT ub.business_id) as business_count
		FROM {$wpdb->users} u
		LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'nmda_last_login'
		LEFT JOIN {$wpdb->prefix}nmda_user_business ub ON u.ID = ub.user_id AND ub.status = 'active'
		WHERE (um.meta_value < %s OR um.meta_value IS NULL)
		AND u.user_registered < %s
		GROUP BY u.ID
		ORDER BY last_login ASC
		LIMIT %d
	", $cutoff_date, $cutoff_date, $limit ), ARRAY_A );

	return $users ?? array();
}

/**
 * Get business statistics
 *
 * @return array Business statistics
 */
function nmda_get_business_stats() {
	global $wpdb;

	// Get post counts by status
	$counts = wp_count_posts( 'nmda_business' );

	// Get classification breakdown
	$classifications = array();
	$terms = get_terms( array(
		'taxonomy'   => 'business_category',
		'hide_empty' => false,
	) );

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$classifications[ $term->name ] = $term->count;
		}
	}

	// Get county distribution
	$counties = array();
	if ( nmda_analytics_table_exists( 'business_addresses' ) ) {
		$counties = $wpdb->get_results( "
			SELECT
				county,
				COUNT(*) as count
			FROM {$wpdb->prefix}nmda_business_addresses
			WHERE county IS NOT NULL AND county != ''
			AND is_primary = 1
			GROUP BY county
			ORDER BY count DESC
			LIMIT 20
		", ARRAY_A );
	}

	// Average approval time (days)
	$avg_approval_time = $wpdb->get_var( "
		SELECT AVG(DATEDIFF(post_modified, post_date))
		FROM {$wpdb->posts}
		WHERE post_type = 'nmda_business'
		AND post_status = 'publish'
		AND post_modified > post_date
	" );

	return array(
		'total'                => intval( $counts->publish ?? 0 ),
		'pending'              => intval( $counts->pending ?? 0 ),
		'draft'                => intval( $counts->draft ?? 0 ),
		'rejected'             => intval( $counts->trash ?? 0 ),
		'classifications'      => $classifications,
		'counties'             => $counties,
		'avg_approval_time'    => round( floatval( $avg_approval_time ?? 0 ), 1 ),
	);
}

/**
 * Get business applications by month
 *
 * @param int $months Number of months to retrieve
 * @return array Monthly application data
 */
function nmda_get_applications_by_month( $months = 12 ) {
	global $wpdb;

	return $wpdb->get_results( $wpdb->prepare( "
		SELECT
			DATE_FORMAT(post_date, '%%Y-%%m') as month,
			DATE_FORMAT(post_date, '%%b %%Y') as month_label,
			COUNT(*) as total,
			SUM(CASE WHEN post_status = 'publish' THEN 1 ELSE 0 END) as approved,
			SUM(CASE WHEN post_status = 'pending' THEN 1 ELSE 0 END) as pending,
			SUM(CASE WHEN post_status = 'trash' THEN 1 ELSE 0 END) as rejected
		FROM {$wpdb->posts}
		WHERE post_type = 'nmda_business'
		AND post_date >= DATE_SUB(NOW(), INTERVAL %d MONTH)
		GROUP BY DATE_FORMAT(post_date, '%%Y-%%m')
		ORDER BY month ASC
	", $months ), ARRAY_A );
}

/**
 * Get reimbursement statistics for analytics dashboard
 *
 * @param string $fiscal_year Fiscal year (e.g., '2025') or 'all'
 * @return array Reimbursement statistics
 */
function nmda_get_analytics_reimbursement_stats( $fiscal_year = 'current' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'nmda_reimbursements';

	// Check if table exists
	if ( ! nmda_analytics_table_exists( 'reimbursements' ) ) {
		return array(
			'fiscal_year'           => $fiscal_year,
			'total_submitted'       => 0,
			'total_approved'        => 0,
			'total_rejected'        => 0,
			'total_pending'         => 0,
			'total_requested'       => 0,
			'total_approved_amount' => 0,
			'approval_rate'         => 0,
			'by_type'               => array(),
		);
	}

	// Determine fiscal year
	if ( $fiscal_year === 'current' ) {
		$current_month = intval( date( 'n' ) );
		// Fiscal year starts July 1
		if ( $current_month >= 7 ) {
			$fiscal_year = date( 'Y' );
		} else {
			$fiscal_year = date( 'Y', strtotime( '-1 year' ) );
		}
	}

	// Build query with proper prepared statement
	if ( $fiscal_year !== 'all' ) {
		$stats = $wpdb->get_row( $wpdb->prepare( "
			SELECT
				COUNT(*) as total_submitted,
				SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as total_approved,
				SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as total_rejected,
				SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending,
				SUM(amount_requested) as total_requested,
				SUM(CASE WHEN status = 'approved' THEN amount_approved ELSE 0 END) as total_approved_amount
			FROM {$table}
			WHERE fiscal_year = %s
		", $fiscal_year ), ARRAY_A );

		$by_type = $wpdb->get_results( $wpdb->prepare( "
			SELECT
				type,
				COUNT(*) as count,
				SUM(amount_requested) as requested,
				SUM(CASE WHEN status = 'approved' THEN amount_approved ELSE 0 END) as approved
			FROM {$table}
			WHERE fiscal_year = %s
			GROUP BY type
		", $fiscal_year ), ARRAY_A );
	} else {
		$stats = $wpdb->get_row( "
			SELECT
				COUNT(*) as total_submitted,
				SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as total_approved,
				SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as total_rejected,
				SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending,
				SUM(amount_requested) as total_requested,
				SUM(CASE WHEN status = 'approved' THEN amount_approved ELSE 0 END) as total_approved_amount
			FROM {$table}
		", ARRAY_A );

		$by_type = $wpdb->get_results( "
			SELECT
				type,
				COUNT(*) as count,
				SUM(amount_requested) as requested,
				SUM(CASE WHEN status = 'approved' THEN amount_approved ELSE 0 END) as approved
			FROM {$table}
			GROUP BY type
		", ARRAY_A );
	}

	// Add NULL safety
	if ( ! $stats ) {
		$stats = array(
			'total_submitted'       => 0,
			'total_approved'        => 0,
			'total_rejected'        => 0,
			'total_pending'         => 0,
			'total_requested'       => 0,
			'total_approved_amount' => 0,
		);
	}

	$total_submitted = intval( $stats['total_submitted'] ?? 0 );

	return array(
		'fiscal_year'           => $fiscal_year,
		'total_submitted'       => $total_submitted,
		'total_approved'        => intval( $stats['total_approved'] ?? 0 ),
		'total_rejected'        => intval( $stats['total_rejected'] ?? 0 ),
		'total_pending'         => intval( $stats['total_pending'] ?? 0 ),
		'total_requested'       => floatval( $stats['total_requested'] ?? 0 ),
		'total_approved_amount' => floatval( $stats['total_approved_amount'] ?? 0 ),
		'approval_rate'         => $total_submitted > 0 ? round( ( intval( $stats['total_approved'] ?? 0 ) / $total_submitted ) * 100, 1 ) : 0,
		'by_type'               => $by_type ?? array(),
	);
}

/**
 * Get reimbursements by fiscal year
 *
 * @param int $years Number of fiscal years to retrieve
 * @return array Fiscal year data
 */
function nmda_get_reimbursements_by_fiscal_year( $years = 3 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'nmda_reimbursements';

	// Check if table exists
	if ( ! nmda_analytics_table_exists( 'reimbursements' ) ) {
		return array();
	}

	$results = $wpdb->get_results( $wpdb->prepare( "
		SELECT
			fiscal_year,
			COUNT(*) as total,
			SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
			SUM(amount_requested) as requested,
			SUM(CASE WHEN status = 'approved' THEN amount_approved ELSE 0 END) as approved_amount
		FROM {$table}
		GROUP BY fiscal_year
		ORDER BY fiscal_year DESC
		LIMIT %d
	", $years ), ARRAY_A );

	return $results ?? array();
}

/**
 * Get detailed reimbursements table data
 *
 * @param string $fiscal_year Fiscal year or 'all'
 * @param string $status Filter by status (optional)
 * @param int $limit Number of results to return
 * @return array Reimbursement records
 */
function nmda_get_reimbursements_table_data( $fiscal_year = 'current', $status = '', $limit = 100 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'nmda_reimbursements';

	// Check if table exists
	if ( ! nmda_analytics_table_exists( 'reimbursements' ) ) {
		return array();
	}

	// Determine fiscal year
	if ( $fiscal_year === 'current' ) {
		$current_month = intval( date( 'n' ) );
		if ( $current_month >= 7 ) {
			$fiscal_year = date( 'Y' );
		} else {
			$fiscal_year = date( 'Y', strtotime( '-1 year' ) );
		}
	}

	// Build WHERE clause
	$where_conditions = array();
	$prepare_values = array();

	if ( $fiscal_year !== 'all' ) {
		$where_conditions[] = 'r.fiscal_year = %s';
		$prepare_values[] = $fiscal_year;
	}

	if ( ! empty( $status ) && in_array( $status, array( 'approved', 'pending', 'rejected' ), true ) ) {
		$where_conditions[] = 'r.status = %s';
		$prepare_values[] = $status;
	}

	$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

	// Add limit
	$prepare_values[] = $limit;

	// Build and execute query
	$query = "
		SELECT
			r.id,
			r.business_id,
			r.user_id,
			r.type,
			r.status,
			r.fiscal_year,
			r.amount_requested,
			r.amount_approved,
			r.created_at,
			r.updated_at,
			p.post_title as business_name,
			u.display_name as submitted_by
		FROM {$table} r
		LEFT JOIN {$wpdb->posts} p ON r.business_id = p.ID
		LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
		{$where_clause}
		ORDER BY r.created_at DESC
		LIMIT %d
	";

	if ( ! empty( $prepare_values ) ) {
		$results = $wpdb->get_results( $wpdb->prepare( $query, ...$prepare_values ), ARRAY_A );
	} else {
		$results = $wpdb->get_results( $query, ARRAY_A );
	}

	return $results ?? array();
}

/**
 * Get messaging statistics
 *
 * @return array Message statistics
 */
function nmda_get_messaging_stats() {
	global $wpdb;
	$table = $wpdb->prefix . 'nmda_communications';

	// Check if table exists
	if ( ! nmda_analytics_table_exists( 'communications' ) ) {
		return array(
			'total_messages'  => 0,
			'unread_messages' => 0,
			'active_admins'   => 0,
			'active_users'    => 0,
			'by_month'        => array(),
		);
	}

	$stats = $wpdb->get_row( "
		SELECT
			COUNT(*) as total_messages,
			SUM(CASE WHEN read_status = 0 THEN 1 ELSE 0 END) as unread_messages,
			COUNT(DISTINCT CASE WHEN admin_id IS NOT NULL THEN admin_id END) as active_admins,
			COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id END) as active_users
		FROM {$table}
	", ARRAY_A );

	// Get messages by month (last 12 months)
	$by_month = $wpdb->get_results( "
		SELECT
			DATE_FORMAT(created_at, '%Y-%m') as month,
			DATE_FORMAT(created_at, '%b %Y') as month_label,
			COUNT(*) as count
		FROM {$table}
		WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
		GROUP BY DATE_FORMAT(created_at, '%Y-%m')
		ORDER BY month ASC
	", ARRAY_A );

	return array(
		'total_messages'   => intval( $stats['total_messages'] ?? 0 ),
		'unread_messages'  => intval( $stats['unread_messages'] ?? 0 ),
		'active_admins'    => intval( $stats['active_admins'] ?? 0 ),
		'active_users'     => intval( $stats['active_users'] ?? 0 ),
		'by_month'         => $by_month ?? array(),
	);
}

/**
 * Get recent activity (last N events)
 *
 * @param int $limit Number of events to return
 * @return array Recent activity events
 */
function nmda_get_recent_activity( $limit = 50 ) {
	global $wpdb;

	$events = array();

	// Get recent user registrations
	$users = $wpdb->get_results( $wpdb->prepare( "
		SELECT
			ID,
			display_name,
			user_email,
			user_registered as event_date,
			'user_registered' as event_type
		FROM {$wpdb->users}
		ORDER BY user_registered DESC
		LIMIT %d
	", $limit / 2 ), ARRAY_A );

	// Get recent business applications
	$businesses = $wpdb->get_results( $wpdb->prepare( "
		SELECT
			ID,
			post_title as title,
			post_status as status,
			post_date as event_date,
			'business_application' as event_type
		FROM {$wpdb->posts}
		WHERE post_type = 'nmda_business'
		ORDER BY post_date DESC
		LIMIT %d
	", $limit / 2 ), ARRAY_A );

	// Merge and sort
	$events = array_merge( $users, $businesses );
	usort( $events, function( $a, $b ) {
		return strtotime( $b['event_date'] ) - strtotime( $a['event_date'] );
	});

	return array_slice( $events, 0, $limit );
}

/**
 * Get detailed business directory report data
 *
 * @param array $filters Optional filters (classification, status, county)
 * @return array Businesses matching filters
 */
function nmda_get_business_directory_report( $filters = array() ) {
	$args = array(
		'post_type'      => 'nmda_business',
		'post_status'    => array( 'publish', 'pending', 'draft' ),
		'posts_per_page' => 100, // Limit to 100 for performance
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	// Apply filters
	if ( ! empty( $filters['classification'] ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'business_category',
				'field'    => 'slug',
				'terms'    => $filters['classification'],
			),
		);
	}

	if ( ! empty( $filters['status'] ) ) {
		$args['post_status'] = $filters['status'];
	}

	$businesses = get_posts( $args );

	if ( empty( $businesses ) ) {
		return array();
	}

	// Prefetch all classifications to avoid N+1 queries
	$business_ids = wp_list_pluck( $businesses, 'ID' );
	if ( function_exists( 'update_post_term_cache' ) ) {
		update_post_term_cache( $business_ids, 'nmda_business' );
	}

	// Prefetch all addresses at once
	$addresses_map = array();
	if ( nmda_analytics_table_exists( 'business_addresses' ) ) {
		global $wpdb;
		$ids_placeholder = implode( ',', array_fill( 0, count( $business_ids ), '%d' ) );
		$addresses = $wpdb->get_results( $wpdb->prepare( "
			SELECT business_id, address_type, county, city, state, zip_code
			FROM {$wpdb->prefix}nmda_business_addresses
			WHERE business_id IN ($ids_placeholder)
			AND is_primary = 1
		", ...$business_ids ), ARRAY_A );

		foreach ( $addresses as $address ) {
			$addresses_map[ $address['business_id'] ] = $address;
		}
	}

	// If addresses table doesn't exist or is empty, try ACF fields
	$use_acf = empty( $addresses_map );

	$data = array();
	foreach ( $businesses as $business ) {
		// Get address data
		if ( $use_acf ) {
			$address = nmda_get_business_primary_address( $business->ID );
		} else {
			$address = $addresses_map[ $business->ID ] ?? array();
		}

		// Get classifications (already cached)
		$classifications = wp_get_post_terms( $business->ID, 'business_category', array( 'fields' => 'names' ) );

		// Apply county filter if needed
		if ( ! empty( $filters['county'] ) && ( empty( $address ) || ( $address['county'] ?? '' ) !== $filters['county'] ) ) {
			continue;
		}

		$data[] = array(
			'id'              => $business->ID,
			'name'            => $business->post_title,
			'classification'  => ! empty( $classifications ) && ! is_wp_error( $classifications ) ? implode( ', ', $classifications ) : '',
			'status'          => $business->post_status,
			'county'          => $address['county'] ?? '',
			'city'            => $address['city'] ?? '',
			'submitted'       => $business->post_date,
			'approved'        => ( $business->post_status === 'publish' ) ? $business->post_modified : '',
			'days_to_approve' => ( $business->post_status === 'publish' && $business->post_modified > $business->post_date )
				? round( ( strtotime( $business->post_modified ) - strtotime( $business->post_date ) ) / DAY_IN_SECONDS, 1 )
				: '',
		);
	}

	return $data;
}

/**
 * Cache expensive analytics queries
 *
 * @param string $key Cache key
 * @param callable $callback Function to execute if cache miss
 * @param int $expiration Cache expiration in seconds (default 1 hour)
 * @return mixed Cached or fresh data
 */
function nmda_get_cached_analytics( $key, $callback, $expiration = HOUR_IN_SECONDS ) {
	$cache_key = 'nmda_analytics_' . $key;
	$cached = get_transient( $cache_key );

	if ( $cached !== false ) {
		return $cached;
	}

	$data = call_user_func( $callback );
	set_transient( $cache_key, $data, $expiration );

	return $data;
}

/**
 * Clear all analytics caches
 */
function nmda_clear_analytics_cache() {
	global $wpdb;

	// Get all analytics transient keys
	$transient_keys = $wpdb->get_col( $wpdb->prepare( "
		SELECT REPLACE(option_name, '_transient_', '')
		FROM {$wpdb->options}
		WHERE option_name LIKE %s
		AND option_name NOT LIKE %s
		LIMIT 100
	", '_transient_nmda_analytics_%', '_transient_timeout_%' ) );

	// Delete each transient using WordPress function for safety
	if ( ! empty( $transient_keys ) ) {
		foreach ( $transient_keys as $key ) {
			delete_transient( $key );
		}
	}

	return count( $transient_keys );
}
