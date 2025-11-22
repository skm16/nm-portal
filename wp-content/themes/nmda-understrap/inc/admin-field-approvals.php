<?php
/**
 * Admin Field Approval Workflow
 *
 * Handles admin interface for approving/rejecting field changes
 * submitted by business users.
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register meta box for pending field changes
 */
function nmda_register_field_approval_meta_box() {
    add_meta_box(
        'nmda_pending_changes',
        '<i class="dashicons dashicons-clock"></i> Pending Field Changes',
        'nmda_render_pending_changes_meta_box',
        'nmda_business',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'nmda_register_field_approval_meta_box' );

/**
 * Render pending changes meta box
 *
 * @param WP_Post $post The business post object
 */
function nmda_render_pending_changes_meta_box( $post ) {
    // Get pending changes from post meta
    $pending_changes = get_post_meta( $post->ID, '_pending_changes', true );

    if ( empty( $pending_changes ) || ! is_array( $pending_changes ) ) {
        echo '<p class="nmda-no-pending">No pending changes at this time.</p>';
        return;
    }

    // Security nonce
    wp_nonce_field( 'nmda_field_approval', 'nmda_field_approval_nonce' );

    echo '<div id="nmda-pending-changes-container">';

    foreach ( $pending_changes as $field_name => $change_data ) {
        $current_value = nmda_get_current_field_value( $post->ID, $field_name );
        $proposed_value = $change_data['value'] ?? '';
        $submitted_by = $change_data['user_id'] ?? 0;
        $submitted_date = $change_data['requested'] ?? current_time( 'mysql' );
        $user_info = get_userdata( $submitted_by );

        // Format field name for display
        $field_label = nmda_get_field_label( $field_name );

        ?>
        <div class="nmda-pending-change" data-field="<?php echo esc_attr( $field_name ); ?>">
            <div class="nmda-change-header">
                <h4><?php echo esc_html( $field_label ); ?></h4>
                <span class="nmda-change-badge">Pending Approval</span>
            </div>

            <div class="nmda-change-meta">
                <span class="nmda-submitted-by">
                    <i class="dashicons dashicons-admin-users"></i>
                    Submitted by: <strong><?php echo esc_html( $user_info->display_name ); ?></strong>
                    (<?php echo esc_html( $user_info->user_email ); ?>)
                </span>
                <span class="nmda-submitted-date">
                    <i class="dashicons dashicons-calendar"></i>
                    <?php echo esc_html( date( 'F j, Y \a\t g:i a', strtotime( $submitted_date ) ) ); ?>
                </span>
            </div>

            <div class="nmda-change-comparison">
                <div class="nmda-comparison-side nmda-current">
                    <div class="nmda-comparison-label">Current Value</div>
                    <div class="nmda-comparison-value">
                        <?php echo nmda_format_field_value( $current_value, $field_name ); ?>
                    </div>
                </div>

                <div class="nmda-comparison-arrow">
                    <i class="dashicons dashicons-arrow-right-alt"></i>
                </div>

                <div class="nmda-comparison-side nmda-proposed">
                    <div class="nmda-comparison-label">Proposed Value</div>
                    <div class="nmda-comparison-value">
                        <?php echo nmda_format_field_value( $proposed_value, $field_name ); ?>
                    </div>
                </div>
            </div>

            <div class="nmda-change-actions">
                <button type="button"
                        class="button button-primary nmda-approve-change"
                        data-business-id="<?php echo esc_attr( $post->ID ); ?>"
                        data-field="<?php echo esc_attr( $field_name ); ?>">
                    <i class="dashicons dashicons-yes"></i> Approve Change
                </button>

                <button type="button"
                        class="button nmda-reject-change"
                        data-business-id="<?php echo esc_attr( $post->ID ); ?>"
                        data-field="<?php echo esc_attr( $field_name ); ?>">
                    <i class="dashicons dashicons-no"></i> Reject Change
                </button>
            </div>

            <div class="nmda-rejection-reason" style="display: none;">
                <label for="rejection-reason-<?php echo esc_attr( $field_name ); ?>">
                    <strong>Rejection Reason (will be sent to user):</strong>
                </label>
                <textarea
                    id="rejection-reason-<?php echo esc_attr( $field_name ); ?>"
                    class="nmda-rejection-textarea"
                    rows="3"
                    placeholder="Please explain why this change cannot be approved..."></textarea>
                <div class="nmda-rejection-buttons">
                    <button type="button" class="button button-primary nmda-confirm-rejection">
                        <i class="dashicons dashicons-yes"></i> Confirm Rejection
                    </button>
                    <button type="button" class="button nmda-cancel-rejection">
                        Cancel
                    </button>
                </div>
            </div>

            <div class="nmda-approval-message" style="display: none;"></div>
        </div>
        <?php
    }

    echo '</div>';
}

/**
 * Get current field value from business post
 *
 * @param int    $business_id Business post ID
 * @param string $field_name  Field name
 * @return mixed Current field value
 */
function nmda_get_current_field_value( $business_id, $field_name ) {
    // Try post meta first
    $value = get_post_meta( $business_id, $field_name, true );

    // If empty, try custom table lookup (for addresses, etc.)
    if ( empty( $value ) ) {
        global $wpdb;

        // Check if it's an address field
        if ( strpos( $field_name, 'primary_' ) === 0 ) {
            $address_field = str_replace( 'primary_', '', $field_name );
            $value = $wpdb->get_var( $wpdb->prepare(
                "SELECT {$address_field} FROM {$wpdb->prefix}nmda_business_addresses
                WHERE business_id = %d AND is_primary = 1 LIMIT 1",
                $business_id
            ) );
        }
    }

    return $value ?: '(empty)';
}

/**
 * Get human-readable field label
 *
 * @param string $field_name Field name
 * @return string Formatted field label
 */
function nmda_get_field_label( $field_name ) {
    $labels = array(
        'business_name' => 'Business Name',
        'dba' => 'DBA (Doing Business As)',
        'dba_name' => 'DBA (Doing Business As)', // Form field name
        'business_phone' => 'Business Phone',
        'business_email' => 'Business Email',
        'website' => 'Website',
        'primary_address' => 'Primary Address',
        'primary_city' => 'City',
        'primary_state' => 'State',
        'primary_zip' => 'ZIP Code',
        'primary_county' => 'County',
        'business_description' => 'Business Description',
        'business_profile' => 'Business Description', // ACF field name
        'year_established' => 'Year Established',
        'number_of_employees' => 'Number of Employees', // Form field name
        'num_employees' => 'Number of Employees', // ACF field name
        'sales_additional_info' => 'Additional Sales Information', // Form field name
        'additional_info' => 'Additional Sales Information', // ACF field name
        'social_facebook' => 'Facebook URL',
        'social_instagram' => 'Instagram URL',
        'social_twitter' => 'Twitter URL',
    );

    return isset( $labels[ $field_name ] ) ? $labels[ $field_name ] : ucwords( str_replace( '_', ' ', $field_name ) );
}

/**
 * Format field value for display
 *
 * @param mixed  $value      Field value
 * @param string $field_name Field name for context
 * @return string Formatted value HTML
 */
function nmda_format_field_value( $value, $field_name ) {
    if ( empty( $value ) || $value === '(empty)' ) {
        return '<span class="nmda-empty-value">(empty)</span>';
    }

    // Handle URLs
    if ( strpos( $field_name, 'website' ) !== false ||
         strpos( $field_name, 'social_' ) !== false ||
         filter_var( $value, FILTER_VALIDATE_URL ) ) {
        return '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html( $value ) . '</a>';
    }

    // Handle emails
    if ( strpos( $field_name, 'email' ) !== false || filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
        return '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
    }

    // Handle phone numbers
    if ( strpos( $field_name, 'phone' ) !== false ) {
        $formatted = preg_replace( '/[^0-9]/', '', $value );
        if ( strlen( $formatted ) === 10 ) {
            $value = '(' . substr( $formatted, 0, 3 ) . ') ' . substr( $formatted, 3, 3 ) . '-' . substr( $formatted, 6 );
        }
    }

    // Handle long text
    if ( strlen( $value ) > 200 ) {
        return '<div class="nmda-long-text">' . nl2br( esc_html( $value ) ) . '</div>';
    }

    return esc_html( $value );
}

/**
 * AJAX Handler: Approve field change
 */
function nmda_ajax_approve_field_change() {
    // Verify nonce
    check_ajax_referer( 'nmda_field_approval', 'nonce' );

    // Check user capabilities
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to approve changes.' ) );
    }

    $business_id = intval( $_POST['business_id'] );
    $field_name = sanitize_text_field( $_POST['field'] );

    // Get pending changes
    $pending_changes = get_post_meta( $business_id, '_pending_changes', true );

    if ( empty( $pending_changes ) || ! isset( $pending_changes[ $field_name ] ) ) {
        wp_send_json_error( array( 'message' => 'No pending change found for this field.' ) );
    }

    $change_data = $pending_changes[ $field_name ];
    $new_value = $change_data['value'];
    $user_id = $change_data['user_id'];

    // Apply the change
    $success = nmda_apply_field_change( $business_id, $field_name, $new_value );

    if ( ! $success ) {
        wp_send_json_error( array( 'message' => 'Failed to apply field change.' ) );
    }

    // Remove from pending changes
    unset( $pending_changes[ $field_name ] );
    update_post_meta( $business_id, '_pending_changes', $pending_changes );

    // Log the approval
    nmda_log_field_change( $business_id, $field_name, $new_value, get_current_user_id(), 'approved' );

    // Send notification to user
    nmda_notify_user_change_approved( $business_id, $user_id, $field_name, $new_value );

    wp_send_json_success( array(
        'message' => 'Change approved successfully!',
        'field' => $field_name
    ) );
}
add_action( 'wp_ajax_nmda_approve_field_change', 'nmda_ajax_approve_field_change' );

/**
 * AJAX Handler: Reject field change
 */
function nmda_ajax_reject_field_change() {
    // Verify nonce
    check_ajax_referer( 'nmda_field_approval', 'nonce' );

    // Check user capabilities
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to reject changes.' ) );
    }

    $business_id = intval( $_POST['business_id'] );
    $field_name = sanitize_text_field( $_POST['field'] );
    $rejection_reason = sanitize_textarea_field( $_POST['reason'] );

    if ( empty( $rejection_reason ) ) {
        wp_send_json_error( array( 'message' => 'Please provide a rejection reason.' ) );
    }

    // Get pending changes
    $pending_changes = get_post_meta( $business_id, '_pending_changes', true );

    if ( empty( $pending_changes ) || ! isset( $pending_changes[ $field_name ] ) ) {
        wp_send_json_error( array( 'message' => 'No pending change found for this field.' ) );
    }

    $change_data = $pending_changes[ $field_name ];
    $user_id = $change_data['user_id'];
    $proposed_value = $change_data['value'];

    // Remove from pending changes
    unset( $pending_changes[ $field_name ] );
    update_post_meta( $business_id, '_pending_changes', $pending_changes );

    // Log the rejection
    nmda_log_field_change( $business_id, $field_name, $proposed_value, get_current_user_id(), 'rejected', $rejection_reason );

    // Send notification to user
    nmda_notify_user_change_rejected( $business_id, $user_id, $field_name, $proposed_value, $rejection_reason );

    wp_send_json_success( array(
        'message' => 'Change rejected.',
        'field' => $field_name
    ) );
}
add_action( 'wp_ajax_nmda_reject_field_change', 'nmda_ajax_reject_field_change' );

/**
 * Map form field names to ACF field names
 *
 * The edit profile form uses different field names than ACF registration.
 * This function translates form field names to their corresponding ACF field names.
 *
 * @param string $form_field_name Field name from the edit form
 * @return string ACF field name
 */
function nmda_map_form_field_to_acf( $form_field_name ) {
    $mapping = array(
        'dba_name'               => 'dba',
        'business_description'   => 'business_profile',
        'number_of_employees'    => 'num_employees',
        'sales_additional_info'  => 'additional_info',
    );

    return isset( $mapping[ $form_field_name ] ) ? $mapping[ $form_field_name ] : $form_field_name;
}

/**
 * Apply approved field change to business
 *
 * @param int    $business_id Business post ID
 * @param string $field_name  Field name
 * @param mixed  $new_value   New value to apply
 * @return bool Success status
 */
function nmda_apply_field_change( $business_id, $field_name, $new_value ) {
    global $wpdb;

    // Map form field names to ACF field names
    $original_field_name = $field_name;
    $field_name = nmda_map_form_field_to_acf( $field_name );

    // Debug logging
    error_log( sprintf(
        'NMDA Field Approval: Processing field "%s"%s for business ID %d',
        $field_name,
        ( $original_field_name !== $field_name ? " (mapped from \"{$original_field_name}\")" : '' ),
        $business_id
    ) );
    error_log( 'NMDA Field Approval: New value = ' . print_r( $new_value, true ) );

    // Check if this is an address field
    if ( strpos( $field_name, 'primary_' ) === 0 || strpos( $field_name, 'address_' ) === 0 ) {
        // Map form field names to database column names
        $field_mapping = array(
            'primary_address' => 'address_line_1',
            'address_street' => 'address_line_1',
            'address_street_2' => 'address_line_2',
            'address_city' => 'city',
            'address_state' => 'state',
            'address_zip' => 'zip_code',
            'address_county' => 'county',
            'primary_city' => 'city',
            'primary_state' => 'state',
            'primary_zip' => 'zip_code',
            'primary_county' => 'county',
        );

        // Get the actual database column name
        $db_column = isset( $field_mapping[ $field_name ] ) ? $field_mapping[ $field_name ] : str_replace( array( 'primary_', 'address_' ), '', $field_name );

        // Update in addresses table
        $result = $wpdb->update(
            $wpdb->prefix . 'nmda_business_addresses',
            array( $db_column => $new_value ),
            array(
                'business_id' => $business_id,
                'is_primary' => 1
            ),
            array( '%s' ),
            array( '%d', '%d' )
        );

        return $result !== false;
    }

    // Check if this is the business title
    if ( $field_name === 'business_name' ) {
        $result = wp_update_post( array(
            'ID' => $business_id,
            'post_title' => $new_value
        ), true );

        return ! is_wp_error( $result );
    }

    // Check if this is an ACF field
    if ( function_exists( 'get_field_object' ) ) {
        $field_object = get_field_object( $field_name, $business_id );

        // Debug: Log whether ACF field was found
        if ( $field_object !== false && isset( $field_object['key'] ) ) {
            error_log( sprintf(
                'NMDA Field Approval: Found ACF field with key "%s" (type: %s)',
                $field_object['key'],
                $field_object['type'] ?? 'unknown'
            ) );
        } else {
            error_log( sprintf(
                'NMDA Field Approval: ACF field "%s" NOT FOUND (will use post_meta fallback)',
                $field_name
            ) );
        }

        // If it's an ACF field, use update_field() with field KEY for reliable cache handling
        if ( $field_object !== false && isset( $field_object['key'] ) ) {
            // Use field KEY instead of field name for more reliable admin updates
            $result = update_field( $field_object['key'], $new_value, $business_id );

            // Debug: Log update result
            error_log( sprintf(
                'NMDA Field Approval: update_field() result = %s',
                $result ? 'SUCCESS' : 'FAILED'
            ) );

            // Verify the value was actually updated
            $verify_value = get_field( $field_object['key'], $business_id );
            error_log( sprintf(
                'NMDA Field Approval: Verification - Field now contains: %s',
                print_r( $verify_value, true )
            ) );

            // Clear ACF cache to ensure admin interface shows updated value immediately
            if ( function_exists( 'acf_get_store' ) ) {
                acf_get_store( 'values' )->remove( $business_id );
                error_log( 'NMDA Field Approval: ACF cache cleared' );
            }

            // Clear WordPress object cache for this post
            wp_cache_delete( $business_id, 'posts' );
            wp_cache_delete( $business_id, 'post_meta' );
            clean_post_cache( $business_id );
            error_log( 'NMDA Field Approval: WordPress cache cleared' );

            return $result;
        }
    }

    // Otherwise, update post meta
    return update_post_meta( $business_id, $field_name, $new_value );
}

/**
 * Send email notification when change is approved
 *
 * @param int    $business_id Business post ID
 * @param int    $user_id     User who submitted the change
 * @param string $field_name  Field name
 * @param mixed  $new_value   Approved value
 */
function nmda_notify_user_change_approved( $business_id, $user_id, $field_name, $new_value ) {
    $user = get_userdata( $user_id );
    $business_name = get_the_title( $business_id );
    $field_label = nmda_get_field_label( $field_name );

    $subject = 'Business Profile Change Approved - ' . $business_name;

    $message = sprintf(
        "Hello %s,\n\n" .
        "Good news! Your requested change to the \"%s\" field for %s has been approved.\n\n" .
        "New Value: %s\n\n" .
        "The change is now live on your business profile.\n\n" .
        "View your profile: %s\n\n" .
        "Thank you,\n" .
        "New Mexico Department of Agriculture",
        $user->display_name,
        $field_label,
        $business_name,
        strip_tags( nmda_format_field_value( $new_value, $field_name ) ),
        home_url( '/business-profile/' )
    );

    wp_mail( $user->user_email, $subject, $message );
}

/**
 * Send email notification when change is rejected
 *
 * @param int    $business_id      Business post ID
 * @param int    $user_id          User who submitted the change
 * @param string $field_name       Field name
 * @param mixed  $proposed_value   Proposed value that was rejected
 * @param string $rejection_reason Reason for rejection
 */
function nmda_notify_user_change_rejected( $business_id, $user_id, $field_name, $proposed_value, $rejection_reason ) {
    $user = get_userdata( $user_id );
    $business_name = get_the_title( $business_id );
    $field_label = nmda_get_field_label( $field_name );

    $subject = 'Business Profile Change Update - ' . $business_name;

    $message = sprintf(
        "Hello %s,\n\n" .
        "We've reviewed your requested change to the \"%s\" field for %s.\n\n" .
        "Unfortunately, we cannot approve this change at this time.\n\n" .
        "Proposed Value: %s\n\n" .
        "Reason:\n%s\n\n" .
        "If you have questions or would like to discuss this further, please contact us or submit a corrected change.\n\n" .
        "View your profile: %s\n\n" .
        "Thank you,\n" .
        "New Mexico Department of Agriculture",
        $user->display_name,
        $field_label,
        $business_name,
        strip_tags( nmda_format_field_value( $proposed_value, $field_name ) ),
        $rejection_reason,
        home_url( '/business-profile/' )
    );

    wp_mail( $user->user_email, $subject, $message );
}

/**
 * Register admin dashboard widget for pending changes overview
 */
function nmda_register_pending_changes_widget() {
    if ( current_user_can( 'edit_others_posts' ) ) {
        wp_add_dashboard_widget(
            'nmda_pending_changes_widget',
            '<i class="dashicons dashicons-clock"></i> Pending Business Profile Changes',
            'nmda_render_pending_changes_widget'
        );
    }
}
add_action( 'wp_dashboard_setup', 'nmda_register_pending_changes_widget' );

/**
 * Render dashboard widget for pending changes
 */
function nmda_render_pending_changes_widget() {
    global $wpdb;

    // Query all businesses with pending changes
    $businesses_with_changes = $wpdb->get_results(
        "SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_pending_changes'
        AND meta_value != 'a:0:{}'"
    );

    if ( empty( $businesses_with_changes ) ) {
        echo '<p>No pending changes at this time.</p>';
        return;
    }

    echo '<table class="widefat nmda-pending-changes-table">';
    echo '<thead><tr><th>Business</th><th>Pending Changes</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    foreach ( $businesses_with_changes as $row ) {
        $business_id = $row->post_id;
        $business_name = get_the_title( $business_id );
        $pending_changes = maybe_unserialize( $row->meta_value );
        $change_count = is_array( $pending_changes ) ? count( $pending_changes ) : 0;

        if ( $change_count === 0 ) {
            continue;
        }

        $edit_link = get_edit_post_link( $business_id );

        echo '<tr>';
        echo '<td><strong>' . esc_html( $business_name ) . '</strong></td>';
        echo '<td><span class="nmda-change-count">' . $change_count . ' field(s)</span></td>';
        echo '<td><a href="' . esc_url( $edit_link ) . '" class="button button-small">Review Changes</a></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
