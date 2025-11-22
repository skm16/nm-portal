<?php
/**
 * NMDA Resource Center Functions
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get all resources, optionally filtered by category
 *
 * @param array $args Query arguments
 * @return array Array of resource posts
 */
function nmda_get_resources( $args = array() ) {
	$defaults = array(
		'post_type'      => 'nmda_resource',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	);

	$args = wp_parse_args( $args, $defaults );

	// If search term is provided, also search in ACF fields
	if ( ! empty( $args['s'] ) ) {
		$search_term = $args['s'];

		// Add meta query to search in resource description
		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'     => 'resource_description',
				'value'   => $search_term,
				'compare' => 'LIKE',
			),
		);

		// If there's already a meta_query, merge it
		if ( isset( $args['meta_query'] ) ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				$args['meta_query'],
				$meta_query,
			);
		} else {
			$args['meta_query'] = $meta_query;
		}
	}

	$query = new WP_Query( $args );

	return $query->posts;
}

/**
 * Get resources grouped by category
 *
 * @return array Associative array of categories with their resources
 */
function nmda_get_resources_by_category() {
	$categories = get_terms( array(
		'taxonomy'   => 'resource_category',
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	$grouped = array();

	foreach ( $categories as $category ) {
		$resources = nmda_get_resources( array(
			'tax_query' => array(
				array(
					'taxonomy' => 'resource_category',
					'field'    => 'term_id',
					'terms'    => $category->term_id,
				),
			),
		) );

		if ( ! empty( $resources ) ) {
			$grouped[ $category->term_id ] = array(
				'category'  => $category,
				'resources' => $resources,
			);
		}
	}

	return $grouped;
}

/**
 * Track resource download
 *
 * @param int $resource_id Resource post ID
 * @param int $user_id User ID (optional, defaults to current user)
 * @return bool Success
 */
function nmda_track_resource_download( $resource_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'nmda_resource_downloads';

	// Check if table exists, if not create it
	nmda_create_resource_downloads_table();

	// Insert download record
	$result = $wpdb->insert(
		$table_name,
		array(
			'resource_id'   => $resource_id,
			'user_id'       => $user_id,
			'download_date' => current_time( 'mysql' ),
			'ip_address'    => nmda_get_user_ip(),
		),
		array( '%d', '%d', '%s', '%s' )
	);

	// Increment total download count
	if ( $result ) {
		$current_count = get_post_meta( $resource_id, '_download_count', true );
		$new_count = $current_count ? intval( $current_count ) + 1 : 1;
		update_post_meta( $resource_id, '_download_count', $new_count );
	}

	return $result !== false;
}

/**
 * Get user's IP address
 *
 * @return string IP address
 */
function nmda_get_user_ip() {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return sanitize_text_field( $ip );
}

/**
 * Create resource downloads tracking table
 */
function nmda_create_resource_downloads_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'nmda_resource_downloads';
	$charset_collate = $wpdb->get_charset_collate();

	// Check if table already exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
		return;
	}

	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		resource_id bigint(20) NOT NULL,
		user_id bigint(20) NOT NULL,
		download_date datetime NOT NULL,
		ip_address varchar(45) DEFAULT '',
		PRIMARY KEY  (id),
		KEY resource_id (resource_id),
		KEY user_id (user_id),
		KEY download_date (download_date)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Get download count for a resource
 *
 * @param int $resource_id Resource post ID
 * @return int Download count
 */
function nmda_get_resource_download_count( $resource_id ) {
	$count = get_post_meta( $resource_id, '_download_count', true );
	return $count ? intval( $count ) : 0;
}

/**
 * Get download statistics for a resource
 *
 * @param int $resource_id Resource post ID
 * @return array Download statistics
 */
function nmda_get_resource_download_stats( $resource_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'nmda_resource_downloads';

	$total_downloads = nmda_get_resource_download_count( $resource_id );

	$unique_users = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE resource_id = %d",
		$resource_id
	) );

	$downloads_30_days = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM $table_name
		WHERE resource_id = %d
		AND download_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
		$resource_id
	) );

	return array(
		'total_downloads'   => $total_downloads,
		'unique_users'      => $unique_users ? intval( $unique_users ) : 0,
		'downloads_30_days' => $downloads_30_days ? intval( $downloads_30_days ) : 0,
	);
}

/**
 * Get resource file URL
 *
 * @param int $resource_id Resource post ID
 * @return string|false File URL or false if no file
 */
function nmda_get_resource_file_url( $resource_id ) {
	$resource_type = get_field( 'resource_type', $resource_id );

	if ( $resource_type === 'URL' ) {
		return get_field( 'resource_url', $resource_id );
	}

	// File type - get from ACF file field
	$file = get_field( 'resource_file', $resource_id );

	if ( $file && isset( $file['url'] ) ) {
		return $file['url'];
	}

	return false;
}

/**
 * Get resource file info
 *
 * @param int $resource_id Resource post ID
 * @return array|false File info array or false if no file
 */
function nmda_get_resource_file_info( $resource_id ) {
	$resource_type = get_field( 'resource_type', $resource_id );

	if ( $resource_type === 'URL' ) {
		$url = get_field( 'resource_url', $resource_id );
		if ( ! $url ) {
			return false;
		}

		// Parse URL to get extension
		$path = parse_url( $url, PHP_URL_PATH );
		$ext = '';
		$filename = 'External Link';

		// Only process if we have a valid path
		if ( ! empty( $path ) ) {
			$ext = pathinfo( $path, PATHINFO_EXTENSION );
			$filename = basename( $path );
		}

		return array(
			'id'             => 0,
			'url'            => $url,
			'filename'       => $filename,
			'type'           => 'url',
			'ext'            => $ext ? $ext : 'link',
			'size'           => 0,
			'size_formatted' => 'External',
			'is_external'    => true,
		);
	}

	// File type - get from ACF file field
	$file = get_field( 'resource_file', $resource_id );

	if ( ! $file ) {
		return false;
	}

	return array(
		'id'             => $file['id'],
		'url'            => $file['url'],
		'filename'       => $file['filename'],
		'type'           => $file['mime_type'],
		'ext'            => $file['subtype'],
		'size'           => $file['filesize'],
		'size_formatted' => size_format( $file['filesize'], 2 ),
		'is_external'    => false,
	);
}

/**
 * Handle resource download request
 */
function nmda_handle_resource_download() {
	// Check if this is a download request
	if ( ! isset( $_GET['nmda_download_resource'] ) ) {
		return;
	}

	$resource_id = intval( $_GET['nmda_download_resource'] );

	// Verify user is logged in
	if ( ! is_user_logged_in() ) {
		wp_redirect( wp_login_url( home_url( '/resource-center' ) ) );
		exit;
	}

	// Verify user is approved member
	if ( ! nmda_is_approved_member() ) {
		wp_die( 'You must be an approved member to download resources.', 'Access Denied', array( 'response' => 403 ) );
	}

	// Verify resource exists
	$resource = get_post( $resource_id );
	if ( ! $resource || $resource->post_type !== 'nmda_resource' ) {
		wp_die( 'Resource not found.', 'Not Found', array( 'response' => 404 ) );
	}

	// Get file info
	$file_info = nmda_get_resource_file_info( $resource_id );
	if ( ! $file_info ) {
		wp_die( 'Resource file not found.', 'Not Found', array( 'response' => 404 ) );
	}

	// Track download
	nmda_track_resource_download( $resource_id );

	// Redirect to file
	wp_redirect( $file_info['url'] );
	exit;
}
add_action( 'template_redirect', 'nmda_handle_resource_download' );

/**
 * Initialize resource downloads table on theme activation
 */
function nmda_init_resources() {
	nmda_create_resource_downloads_table();
}
add_action( 'after_setup_theme', 'nmda_init_resources' );
