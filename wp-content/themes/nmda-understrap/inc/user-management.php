<?php
/**
 * NMDA User Management Functions
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Custom user registration
 *
 * @param array $user_data User data array.
 * @return int|WP_Error User ID on success, WP_Error on failure.
 */
function nmda_custom_registration( $user_data ) {
    // Validate required fields
    if ( empty( $user_data['user_email'] ) || empty( $user_data['user_login'] ) ) {
        return new WP_Error( 'missing_fields', __( 'Required fields are missing.', 'nmda-understrap' ) );
    }

    // Create WordPress user account
    $user_id = wp_insert_user( array(
        'user_login' => sanitize_user( $user_data['user_login'] ),
        'user_email' => sanitize_email( $user_data['user_email'] ),
        'user_pass'  => wp_generate_password(),
        'first_name' => sanitize_text_field( $user_data['first_name'] ?? '' ),
        'last_name'  => sanitize_text_field( $user_data['last_name'] ?? '' ),
        'role'       => 'subscriber', // Default role for new members
    ) );

    if ( is_wp_error( $user_id ) ) {
        return $user_id;
    }

    // Store additional user meta
    if ( ! empty( $user_data['phone'] ) ) {
        update_user_meta( $user_id, 'phone', sanitize_text_field( $user_data['phone'] ) );
    }

    // Send welcome email with password reset link
    wp_new_user_notification( $user_id, null, 'user' );

    return $user_id;
}

/**
 * Check if user can access a specific business
 *
 * @param int $user_id User ID.
 * @param int $business_id Business post ID.
 * @return bool True if user has access, false otherwise.
 */
function nmda_user_can_access_business( $user_id, $business_id ) {
    global $wpdb;

    // Admins can access all businesses
    if ( user_can( $user_id, 'administrator' ) ) {
        return true;
    }

    $table = nmda_get_user_business_table();

    $result = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table
        WHERE user_id = %d
        AND business_id = %d
        AND status = 'active'",
        $user_id,
        $business_id
    ) );

    return $result > 0;
}

/**
 * Get user's role for a specific business
 *
 * @param int $user_id User ID.
 * @param int $business_id Business post ID.
 * @return string|null User's role or null if no access.
 */
function nmda_get_user_business_role( $user_id, $business_id ) {
    global $wpdb;

    if ( user_can( $user_id, 'administrator' ) ) {
        return 'administrator';
    }

    $table = nmda_get_user_business_table();

    $role = $wpdb->get_var( $wpdb->prepare(
        "SELECT role FROM $table
        WHERE user_id = %d
        AND business_id = %d
        AND status = 'active'",
        $user_id,
        $business_id
    ) );

    return $role;
}

/**
 * Get all businesses associated with a user
 *
 * @param int $user_id User ID.
 * @param string $status Filter by status (optional).
 * @return array Array of business IDs and roles.
 */
function nmda_get_user_businesses( $user_id, $status = 'active' ) {
    global $wpdb;

    $table = nmda_get_user_business_table();

    $query = $wpdb->prepare(
        "SELECT business_id, role, status, accepted_date
        FROM $table
        WHERE user_id = %d",
        $user_id
    );

    if ( $status ) {
        $query .= $wpdb->prepare( " AND status = %s", $status );
    }

    $query .= " ORDER BY accepted_date DESC";

    return $wpdb->get_results( $query, ARRAY_A );
}

/**
 * Get all users associated with a business
 *
 * @param int $business_id Business post ID.
 * @param string $status Filter by status (optional).
 * @return array Array of user IDs and roles.
 */
function nmda_get_business_users( $business_id, $status = 'active' ) {
    global $wpdb;

    $table = nmda_get_user_business_table();

    $query = $wpdb->prepare(
        "SELECT user_id, role, status, invited_date, accepted_date
        FROM $table
        WHERE business_id = %d",
        $business_id
    );

    if ( $status ) {
        $query .= $wpdb->prepare( " AND status = %s", $status );
    }

    $query .= " ORDER BY accepted_date DESC";

    return $wpdb->get_results( $query, ARRAY_A );
}

/**
 * Associate user with business
 *
 * @param int $user_id User ID.
 * @param int $business_id Business post ID.
 * @param string $role User role for this business.
 * @param int|null $invited_by User ID of inviter.
 * @return int|false Insert ID on success, false on failure.
 */
function nmda_add_user_to_business( $user_id, $business_id, $role = 'viewer', $invited_by = null ) {
    global $wpdb;

    $table = nmda_get_user_business_table();

    $result = $wpdb->insert(
        $table,
        array(
            'user_id'       => $user_id,
            'business_id'   => $business_id,
            'role'          => $role,
            'status'        => 'active',
            'invited_by'    => $invited_by,
            'invited_date'  => current_time( 'mysql' ),
            'accepted_date' => current_time( 'mysql' ),
        ),
        array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
    );

    return $result ? $wpdb->insert_id : false;
}

/**
 * Remove user from business
 *
 * @param int $user_id User ID.
 * @param int $business_id Business post ID.
 * @return bool True on success, false on failure.
 */
function nmda_remove_user_from_business( $user_id, $business_id ) {
    global $wpdb;

    $table = nmda_get_user_business_table();

    $result = $wpdb->delete(
        $table,
        array(
            'user_id'     => $user_id,
            'business_id' => $business_id,
        ),
        array( '%d', '%d' )
    );

    return $result !== false;
}

/**
 * Update user's role for a business
 *
 * @param int $user_id User ID.
 * @param int $business_id Business post ID.
 * @param string $new_role New role.
 * @return bool True on success, false on failure.
 */
function nmda_update_user_business_role( $user_id, $business_id, $new_role ) {
    global $wpdb;

    $table = nmda_get_user_business_table();

    $result = $wpdb->update(
        $table,
        array( 'role' => $new_role ),
        array(
            'user_id'     => $user_id,
            'business_id' => $business_id,
        ),
        array( '%s' ),
        array( '%d', '%d' )
    );

    return $result !== false;
}

/**
 * Check if user is approved member
 *
 * @param int|null $user_id User ID (defaults to current user).
 * @return bool True if approved member, false otherwise.
 */
function nmda_is_approved_member( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    if ( ! $user_id ) {
        return false;
    }

    // Get user's businesses
    $businesses = nmda_get_user_businesses( $user_id, 'active' );

    // Check if any associated business is published/approved
    foreach ( $businesses as $business ) {
        $post_status = get_post_status( $business->business_id );
        if ( $post_status === 'publish' ) {
            return true;
        }
    }

    return false;
}

/**
 * Redirect non-logged-in users
 */
function nmda_redirect_non_logged_in_users() {
    if ( ! is_user_logged_in() && is_page( array( 'dashboard', 'my-account', 'resources' ) ) ) {
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
}
add_action( 'template_redirect', 'nmda_redirect_non_logged_in_users' );

/**
 * Add custom user roles
 */
function nmda_add_custom_roles() {
    // Business Owner role
    add_role(
        'business_owner',
        __( 'Business Owner', 'nmda-understrap' ),
        array(
            'read'         => true,
            'edit_posts'   => false,
            'delete_posts' => false,
        )
    );

    // Business Manager role
    add_role(
        'business_manager',
        __( 'Business Manager', 'nmda-understrap' ),
        array(
            'read'         => true,
            'edit_posts'   => false,
            'delete_posts' => false,
        )
    );
}
add_action( 'init', 'nmda_add_custom_roles' );

/**
 * Track user last login time
 */
function nmda_track_last_login( $user_login, $user ) {
	update_user_meta( $user->ID, 'last_login', current_time( 'mysql' ) );
}
add_action( 'wp_login', 'nmda_track_last_login', 10, 2 );
