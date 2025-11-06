<?php
/**
 * NMDA Reimbursement Management Functions
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Submit a reimbursement request
 *
 * @param string $type Reimbursement type (lead, advertising, labels).
 * @param array $data Form data.
 * @return int|WP_Error Reimbursement ID on success, WP_Error on failure.
 */
function nmda_submit_reimbursement( $type, $data ) {
    // Validate required fields
    if ( empty( $data['business_id'] ) || empty( $data['user_id'] ) || empty( $data['fiscal_year'] ) ) {
        return new WP_Error( 'missing_fields', __( 'Required fields are missing.', 'nmda-understrap' ) );
    }

    // Validate business eligibility
    $is_eligible = nmda_check_business_eligibility( $data['business_id'] );
    if ( is_wp_error( $is_eligible ) ) {
        return $is_eligible;
    }

    // Check fiscal year limits
    $limit_check = nmda_check_fiscal_year_limit( $data['business_id'], $type, $data['fiscal_year'] );
    if ( is_wp_error( $limit_check ) ) {
        return $limit_check;
    }

    global $wpdb;
    $table = nmda_get_reimbursements_table();

    // Insert reimbursement
    $result = $wpdb->insert(
        $table,
        array(
            'business_id'      => $data['business_id'],
            'user_id'          => $data['user_id'],
            'type'             => $type,
            'status'           => 'submitted',
            'fiscal_year'      => $data['fiscal_year'],
            'amount_requested' => $data['amount_requested'] ?? null,
            'data'             => json_encode( $data ),
            'documents'        => json_encode( $data['documents'] ?? array() ),
        ),
        array( '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s' )
    );

    if ( ! $result ) {
        return new WP_Error( 'insert_failed', __( 'Failed to submit reimbursement.', 'nmda-understrap' ) );
    }

    $reimbursement_id = $wpdb->insert_id;

    // Send notification emails
    nmda_send_reimbursement_notification( $reimbursement_id, 'submitted' );

    // Log communication
    nmda_log_communication(
        $data['business_id'],
        $data['user_id'],
        null,
        'reimbursement',
        sprintf( __( 'Reimbursement request #%d submitted for %s', 'nmda-understrap' ), $reimbursement_id, $type )
    );

    return $reimbursement_id;
}

/**
 * Get reimbursement by ID
 *
 * @param int $reimbursement_id Reimbursement ID.
 * @return object|null Reimbursement object or null.
 */
function nmda_get_reimbursement( $reimbursement_id ) {
    global $wpdb;
    $table = nmda_get_reimbursements_table();

    $reimbursement = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $reimbursement_id
    ) );

    if ( $reimbursement && $reimbursement->data ) {
        $reimbursement->data = json_decode( $reimbursement->data, true );
    }

    if ( $reimbursement && $reimbursement->documents ) {
        $reimbursement->documents = json_decode( $reimbursement->documents, true );
    }

    return $reimbursement;
}

/**
 * Get reimbursements for a business
 *
 * @param int $business_id Business post ID.
 * @param array $args Query arguments.
 * @return array Array of reimbursement objects.
 */
function nmda_get_business_reimbursements( $business_id, $args = array() ) {
    global $wpdb;
    $table = nmda_get_reimbursements_table();

    $defaults = array(
        'type'        => null,
        'status'      => null,
        'fiscal_year' => null,
        'orderby'     => 'created_at',
        'order'       => 'DESC',
        'limit'       => 100,
    );

    $args = wp_parse_args( $args, $defaults );

    $query = $wpdb->prepare( "SELECT * FROM $table WHERE business_id = %d", $business_id );

    if ( $args['type'] ) {
        $query .= $wpdb->prepare( " AND type = %s", $args['type'] );
    }

    if ( $args['status'] ) {
        $query .= $wpdb->prepare( " AND status = %s", $args['status'] );
    }

    if ( $args['fiscal_year'] ) {
        $query .= $wpdb->prepare( " AND fiscal_year = %s", $args['fiscal_year'] );
    }

    $query .= " ORDER BY {$args['orderby']} {$args['order']}";
    $query .= $wpdb->prepare( " LIMIT %d", $args['limit'] );

    return $wpdb->get_results( $query );
}

/**
 * Update reimbursement status
 *
 * @param int $reimbursement_id Reimbursement ID.
 * @param string $status New status.
 * @param int|null $admin_id Admin user ID.
 * @param string|null $admin_notes Admin notes.
 * @return bool True on success, false on failure.
 */
function nmda_update_reimbursement_status( $reimbursement_id, $status, $admin_id = null, $admin_notes = null ) {
    global $wpdb;
    $table = nmda_get_reimbursements_table();

    $update_data = array(
        'status' => $status,
    );

    $format = array( '%s' );

    if ( $admin_id ) {
        $update_data['reviewed_by'] = $admin_id;
        $update_data['reviewed_at'] = current_time( 'mysql' );
        $format[] = '%d';
        $format[] = '%s';
    }

    if ( $admin_notes ) {
        $update_data['admin_notes'] = $admin_notes;
        $format[] = '%s';
    }

    $result = $wpdb->update(
        $table,
        $update_data,
        array( 'id' => $reimbursement_id ),
        $format,
        array( '%d' )
    );

    if ( $result !== false ) {
        // Send notification
        nmda_send_reimbursement_notification( $reimbursement_id, $status );

        // Log status change
        $reimbursement = nmda_get_reimbursement( $reimbursement_id );
        if ( $reimbursement ) {
            nmda_log_communication(
                $reimbursement->business_id,
                $reimbursement->user_id,
                $admin_id,
                'reimbursement',
                sprintf( __( 'Reimbursement #%d status changed to: %s', 'nmda-understrap' ), $reimbursement_id, $status )
            );
        }

        return true;
    }

    return false;
}

/**
 * Approve reimbursement
 *
 * @param int $reimbursement_id Reimbursement ID.
 * @param float $amount_approved Approved amount.
 * @param int $admin_id Admin user ID.
 * @param string|null $admin_notes Admin notes.
 * @return bool True on success, false on failure.
 */
function nmda_approve_reimbursement( $reimbursement_id, $amount_approved, $admin_id, $admin_notes = null ) {
    global $wpdb;
    $table = nmda_get_reimbursements_table();

    $result = $wpdb->update(
        $table,
        array(
            'status'          => 'approved',
            'amount_approved' => $amount_approved,
            'reviewed_by'     => $admin_id,
            'reviewed_at'     => current_time( 'mysql' ),
            'admin_notes'     => $admin_notes,
        ),
        array( 'id' => $reimbursement_id ),
        array( '%s', '%f', '%d', '%s', '%s' ),
        array( '%d' )
    );

    if ( $result !== false ) {
        nmda_send_reimbursement_notification( $reimbursement_id, 'approved' );
        return true;
    }

    return false;
}

/**
 * Reject reimbursement
 *
 * @param int $reimbursement_id Reimbursement ID.
 * @param int $admin_id Admin user ID.
 * @param string $reason Rejection reason.
 * @return bool True on success, false on failure.
 */
function nmda_reject_reimbursement( $reimbursement_id, $admin_id, $reason ) {
    return nmda_update_reimbursement_status( $reimbursement_id, 'rejected', $admin_id, $reason );
}

/**
 * Check business eligibility for reimbursements
 *
 * @param int $business_id Business post ID.
 * @return bool|WP_Error True if eligible, WP_Error otherwise.
 */
function nmda_check_business_eligibility( $business_id ) {
    // Check if business is published/approved
    $post_status = get_post_status( $business_id );
    if ( $post_status !== 'publish' ) {
        return new WP_Error( 'not_approved', __( 'Business must be approved to submit reimbursements.', 'nmda-understrap' ) );
    }

    // Check if business has required information
    // Add additional eligibility checks as needed

    return true;
}

/**
 * Check fiscal year reimbursement limits
 *
 * @param int $business_id Business post ID.
 * @param string $type Reimbursement type.
 * @param string $fiscal_year Fiscal year.
 * @return bool|WP_Error True if within limits, WP_Error otherwise.
 */
function nmda_check_fiscal_year_limit( $business_id, $type, $fiscal_year ) {
    global $wpdb;
    $table = nmda_get_reimbursements_table();

    // Get total approved reimbursements for this fiscal year
    $total = $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount_approved) FROM $table
        WHERE business_id = %d
        AND type = %s
        AND fiscal_year = %s
        AND status = 'approved'",
        $business_id,
        $type,
        $fiscal_year
    ) );

    // Define limits per type (these should be configurable in admin)
    $limits = array(
        'lead'        => 5000,
        'advertising' => 10000,
        'labels'      => 3000,
    );

    $limit = $limits[ $type ] ?? 0;

    if ( $total >= $limit ) {
        return new WP_Error(
            'limit_exceeded',
            sprintf( __( 'Fiscal year limit of $%s reached for %s reimbursements.', 'nmda-understrap' ), number_format( $limit, 2 ), $type )
        );
    }

    return true;
}

/**
 * Send reimbursement notification
 *
 * @param int $reimbursement_id Reimbursement ID.
 * @param string $status Status.
 */
function nmda_send_reimbursement_notification( $reimbursement_id, $status ) {
    $reimbursement = nmda_get_reimbursement( $reimbursement_id );
    if ( ! $reimbursement ) {
        return;
    }

    $user = get_userdata( $reimbursement->user_id );
    if ( ! $user ) {
        return;
    }

    $business = get_post( $reimbursement->business_id );
    $subject = sprintf( __( 'Reimbursement #%d - %s', 'nmda-understrap' ), $reimbursement_id, ucfirst( $status ) );

    $message = sprintf( __( "Dear %s,\n\n", 'nmda-understrap' ), $user->display_name );
    $message .= sprintf( __( "Your %s reimbursement request (#%d) for %s has been %s.\n\n", 'nmda-understrap' ),
        $reimbursement->type,
        $reimbursement_id,
        $business->post_title,
        $status
    );

    if ( $status === 'approved' && $reimbursement->amount_approved ) {
        $message .= sprintf( __( "Approved amount: $%s\n\n", 'nmda-understrap' ), number_format( $reimbursement->amount_approved, 2 ) );
    }

    if ( $reimbursement->admin_notes ) {
        $message .= sprintf( __( "Notes: %s\n\n", 'nmda-understrap' ), $reimbursement->admin_notes );
    }

    $message .= sprintf( __( "View details: %s\n", 'nmda-understrap' ), home_url( '/dashboard/reimbursements/' . $reimbursement_id ) );

    wp_mail( $user->user_email, $subject, $message );

    // Also notify admins for new submissions
    if ( $status === 'submitted' ) {
        $admin_email = get_option( 'admin_email' );
        $admin_subject = sprintf( __( 'New Reimbursement Submission #%d', 'nmda-understrap' ), $reimbursement_id );
        $admin_message = sprintf(
            __( "A new %s reimbursement has been submitted.\n\nBusiness: %s\nAmount Requested: $%s\n\nReview: %s", 'nmda-understrap' ),
            $reimbursement->type,
            $business->post_title,
            number_format( $reimbursement->amount_requested, 2 ),
            admin_url( 'admin.php?page=nmda-reimbursements&id=' . $reimbursement_id )
        );
        wp_mail( $admin_email, $admin_subject, $admin_message );
    }
}

/**
 * Get reimbursement statistics for a business
 *
 * @param int $business_id Business post ID.
 * @param string|null $fiscal_year Fiscal year (optional).
 * @return array Statistics array.
 */
function nmda_get_reimbursement_stats( $business_id, $fiscal_year = null ) {
    global $wpdb;
    $table = nmda_get_reimbursements_table();

    $query = $wpdb->prepare( "SELECT type, status, COUNT(*) as count, SUM(amount_approved) as total FROM $table WHERE business_id = %d", $business_id );

    if ( $fiscal_year ) {
        $query .= $wpdb->prepare( " AND fiscal_year = %s", $fiscal_year );
    }

    $query .= " GROUP BY type, status";

    return $wpdb->get_results( $query );
}
