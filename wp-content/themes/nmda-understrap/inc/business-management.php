<?php
/**
 * NMDA Business Management Functions
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Send user invitation to join business
 *
 * @param int $business_id Business post ID.
 * @param string $email Email address to invite.
 * @param string $role Role for the invited user.
 * @param int $invited_by User ID of inviter.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function nmda_invite_user_to_business( $business_id, $email, $role = 'viewer', $invited_by = null ) {
    // Validate email
    if ( ! is_email( $email ) ) {
        return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'nmda-understrap' ) );
    }

    // Validate business exists
    if ( get_post_type( $business_id ) !== 'nmda_business' ) {
        return new WP_Error( 'invalid_business', __( 'Invalid business ID.', 'nmda-understrap' ) );
    }

    // Check if user already exists
    $user = get_user_by( 'email', $email );
    $user_id = $user ? $user->ID : null;

    global $wpdb;
    $table = nmda_get_user_business_table();

    // Check if invitation already exists
    if ( $user_id ) {
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table
            WHERE user_id = %d AND business_id = %d",
            $user_id,
            $business_id
        ) );

        if ( $existing > 0 ) {
            return new WP_Error( 'already_invited', __( 'User already has access to this business.', 'nmda-understrap' ) );
        }
    }

    // Generate unique invitation token
    $token = wp_generate_password( 32, false );

    // Store invitation
    if ( $user_id ) {
        // Existing user - create pending invitation
        $result = $wpdb->insert(
            $table,
            array(
                'user_id'       => $user_id,
                'business_id'   => $business_id,
                'role'          => $role,
                'status'        => 'pending',
                'invited_by'    => $invited_by,
                'invited_date'  => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%d', '%s' )
        );

        if ( $result ) {
            $invitation_id = $wpdb->insert_id;
            update_user_meta( $user_id, '_nmda_invitation_token_' . $invitation_id, $token );
        }
    } else {
        // New user - store invitation data temporarily
        set_transient( 'nmda_invitation_' . $token, array(
            'email'       => $email,
            'business_id' => $business_id,
            'role'        => $role,
            'invited_by'  => $invited_by,
        ), WEEK_IN_SECONDS );
    }

    // Send invitation email
    $business = get_post( $business_id );
    $invitation_url = add_query_arg( array(
        'action' => 'accept_invitation',
        'token'  => $token,
    ), home_url( '/invitation' ) );

    $subject = sprintf( __( 'Invitation to join %s on NMDA Portal', 'nmda-understrap' ), $business->post_title );

    $message = sprintf(
        __( "You've been invited to join %s on the New Mexico Department of Agriculture Portal.\n\n", 'nmda-understrap' ),
        $business->post_title
    );

    $message .= sprintf( __( "Role: %s\n\n", 'nmda-understrap' ), ucfirst( $role ) );
    $message .= sprintf( __( "Click here to accept the invitation: %s\n\n", 'nmda-understrap' ), $invitation_url );
    $message .= __( "This invitation will expire in 7 days.\n", 'nmda-understrap' );

    $sent = wp_mail( $email, $subject, $message );

    // Log invitation in communications
    if ( $sent ) {
        nmda_log_communication( $business_id, $user_id, $invited_by, 'invitation', $message );
    }

    return $sent;
}

/**
 * Accept invitation to join business
 *
 * @param string $token Invitation token.
 * @param int|null $user_id User ID (for existing users).
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function nmda_accept_invitation( $token, $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    global $wpdb;
    $table = nmda_get_user_business_table();

    // For existing users, check user meta for token
    if ( $user_id ) {
        $invitations = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, business_id, role FROM $table
            WHERE user_id = %d AND status = 'pending'",
            $user_id
        ) );

        foreach ( $invitations as $invitation ) {
            $stored_token = get_user_meta( $user_id, '_nmda_invitation_token_' . $invitation->id, true );
            if ( $stored_token === $token ) {
                // Update status to active
                $wpdb->update(
                    $table,
                    array(
                        'status'        => 'active',
                        'accepted_date' => current_time( 'mysql' ),
                    ),
                    array( 'id' => $invitation->id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );

                // Clean up token
                delete_user_meta( $user_id, '_nmda_invitation_token_' . $invitation->id );

                return true;
            }
        }
    }

    // Check transient for new user invitations
    $invitation_data = get_transient( 'nmda_invitation_' . $token );
    if ( $invitation_data && $user_id ) {
        // Create user-business association
        $result = nmda_add_user_to_business(
            $user_id,
            $invitation_data['business_id'],
            $invitation_data['role'],
            $invitation_data['invited_by']
        );

        if ( $result ) {
            delete_transient( 'nmda_invitation_' . $token );
            return true;
        }
    }

    return new WP_Error( 'invalid_token', __( 'Invalid or expired invitation token.', 'nmda-understrap' ) );
}

/**
 * Get business addresses
 *
 * @param int $business_id Business post ID.
 * @param string|null $address_type Filter by address type.
 * @return array Array of address objects.
 */
function nmda_get_business_addresses( $business_id, $address_type = null ) {
    global $wpdb;
    $table = nmda_get_business_address_table();

    $query = $wpdb->prepare(
        "SELECT * FROM $table WHERE business_id = %d",
        $business_id
    );

    if ( $address_type ) {
        $query .= $wpdb->prepare( " AND address_type = %s", $address_type );
    }

    $query .= " ORDER BY is_primary DESC, id ASC";

    return $wpdb->get_results( $query );
}

/**
 * Add business address
 *
 * @param int $business_id Business post ID.
 * @param array $address_data Address data array.
 * @return int|false Insert ID on success, false on failure.
 */
function nmda_add_business_address( $business_id, $address_data ) {
    global $wpdb;
    $table = nmda_get_business_address_table();

    // If this is set as primary, unset other primary addresses
    if ( ! empty( $address_data['is_primary'] ) ) {
        $wpdb->update(
            $table,
            array( 'is_primary' => 0 ),
            array( 'business_id' => $business_id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    $result = $wpdb->insert(
        $table,
        array_merge(
            array( 'business_id' => $business_id ),
            $address_data
        )
    );

    return $result ? $wpdb->insert_id : false;
}

/**
 * Update business address
 *
 * @param int $address_id Address ID.
 * @param array $address_data Address data array.
 * @return bool True on success, false on failure.
 */
function nmda_update_business_address( $address_id, $address_data ) {
    global $wpdb;
    $table = nmda_get_business_address_table();

    // If this is set as primary, unset other primary addresses for this business
    if ( ! empty( $address_data['is_primary'] ) ) {
        $business_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT business_id FROM $table WHERE id = %d",
            $address_id
        ) );

        if ( $business_id ) {
            $wpdb->update(
                $table,
                array( 'is_primary' => 0 ),
                array( 'business_id' => $business_id ),
                array( '%d' ),
                array( '%d' )
            );
        }
    }

    $result = $wpdb->update(
        $table,
        $address_data,
        array( 'id' => $address_id ),
        null,
        array( '%d' )
    );

    return $result !== false;
}

/**
 * Delete business address
 *
 * @param int $address_id Address ID.
 * @return bool True on success, false on failure.
 */
function nmda_delete_business_address( $address_id ) {
    global $wpdb;
    $table = nmda_get_business_address_table();

    $result = $wpdb->delete(
        $table,
        array( 'id' => $address_id ),
        array( '%d' )
    );

    return $result !== false;
}

/**
 * Check if field can be edited by user
 *
 * @param string $field_name Field name.
 * @param string $user_role User's role for the business.
 * @return bool True if editable, false otherwise.
 */
function nmda_can_edit_field( $field_name, $user_role = 'viewer' ) {
    global $wpdb;
    $table = nmda_get_field_permissions_table();

    $permission = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE field_name = %s",
        $field_name
    ) );

    if ( ! $permission ) {
        // Field not found in permissions table - allow edit by default for non-admins
        return in_array( $user_role, array( 'owner', 'manager' ) );
    }

    // Admin only fields
    if ( $permission->admin_only ) {
        return current_user_can( 'administrator' );
    }

    // User editable fields
    if ( ! $permission->user_editable ) {
        return false;
    }

    // Owner and manager can edit
    return in_array( $user_role, array( 'owner', 'manager' ) );
}

/**
 * Update business field with approval workflow
 *
 * @param int $business_id Business post ID.
 * @param string $field_name Field name.
 * @param mixed $value New value.
 * @param int $user_id User making the change.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function nmda_update_business_field( $business_id, $field_name, $value, $user_id ) {
    global $wpdb;
    $permissions_table = nmda_get_field_permissions_table();

    $permission = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $permissions_table WHERE field_name = %s",
        $field_name
    ) );

    // Check if field requires approval
    if ( $permission && $permission->requires_approval && ! current_user_can( 'administrator' ) ) {
        // Store as pending change
        $pending_changes = get_post_meta( $business_id, '_pending_changes', true ) ?: array();
        $pending_changes[ $field_name ] = array(
            'value'      => $value,
            'user_id'    => $user_id,
            'requested'  => current_time( 'mysql' ),
            'old_value'  => get_post_meta( $business_id, $field_name, true ),
        );
        update_post_meta( $business_id, '_pending_changes', $pending_changes );

        // Notify admins
        nmda_notify_admins_pending_change( $business_id, $field_name );

        return new WP_Error( 'pending_approval', __( 'Change submitted for approval.', 'nmda-understrap' ) );
    }

    // Update immediately
    update_post_meta( $business_id, $field_name, $value );

    // Log change
    nmda_log_field_change( $business_id, $field_name, $value, $user_id );

    return true;
}

/**
 * Log field change
 *
 * @param int $business_id Business post ID.
 * @param string $field_name Field name.
 * @param mixed $value New value.
 * @param int $user_id User ID.
 */
function nmda_log_field_change( $business_id, $field_name, $value, $user_id ) {
    $log = get_post_meta( $business_id, '_field_change_log', true ) ?: array();
    $log[] = array(
        'field'     => $field_name,
        'value'     => $value,
        'user_id'   => $user_id,
        'timestamp' => current_time( 'mysql' ),
    );
    update_post_meta( $business_id, '_field_change_log', $log );
}

/**
 * Notify admins of pending change
 *
 * @param int $business_id Business post ID.
 * @param string $field_name Field name.
 */
function nmda_notify_admins_pending_change( $business_id, $field_name ) {
    $business = get_post( $business_id );
    $admin_email = get_option( 'admin_email' );

    $subject = sprintf( __( 'Pending change for %s', 'nmda-understrap' ), $business->post_title );
    $message = sprintf(
        __( "A change to the field '%s' for business '%s' is pending your approval.\n\nView and approve: %s", 'nmda-understrap' ),
        $field_name,
        $business->post_title,
        admin_url( 'post.php?post=' . $business_id . '&action=edit' )
    );

    wp_mail( $admin_email, $subject, $message );
}

/**
 * Log communication
 *
 * @param int $business_id Business post ID.
 * @param int|null $user_id User ID.
 * @param int|null $admin_id Admin ID.
 * @param string $type Communication type.
 * @param string $message Message content.
 * @return int|false Insert ID on success, false on failure.
 */
function nmda_log_communication( $business_id, $user_id, $admin_id, $type, $message ) {
    global $wpdb;
    $table = nmda_get_communications_table();

    $result = $wpdb->insert(
        $table,
        array(
            'business_id' => $business_id,
            'user_id'     => $user_id,
            'admin_id'    => $admin_id,
            'type'        => $type,
            'message'     => $message,
        ),
        array( '%d', '%d', '%d', '%s', '%s' )
    );

    return $result ? $wpdb->insert_id : false;
}

/**
 * Check if a field requires approval
 *
 * @param string $field_name Field name to check.
 * @return bool True if field requires approval.
 */
function nmda_field_requires_approval( $field_name ) {
	global $wpdb;
	$table = nmda_get_field_permissions_table();

	$requires_approval = $wpdb->get_var( $wpdb->prepare(
		"SELECT requires_approval FROM $table WHERE field_name = %s",
		$field_name
	) );

	return (bool) $requires_approval;
}

/**
 * Handle business profile update AJAX request
 */
function nmda_ajax_update_business_profile() {
	// Verify nonce
	$business_id = isset( $_POST['business_id'] ) ? intval( $_POST['business_id'] ) : 0;

	if ( ! wp_verify_nonce( $_POST['profile_nonce'], 'nmda_update_profile_' . $business_id ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed.' ) );
	}

	// Check user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
	}

	$user_id = get_current_user_id();

	// Verify business exists
	if ( ! $business_id || get_post_type( $business_id ) !== 'nmda_business' ) {
		wp_send_json_error( array( 'message' => 'Invalid business ID.' ) );
	}

	// Check user has permission to edit
	$user_role = nmda_get_user_business_role( $user_id, $business_id );
	$can_edit  = in_array( $user_role, array( 'owner', 'manager', 'administrator' ) ) || current_user_can( 'administrator' );
	if ( ! $can_edit ) {
		wp_send_json_error( array( 'message' => 'You don\'t have permission to edit this business.' ) );
	}

	$is_autosave = isset( $_POST['autosave'] ) && $_POST['autosave'] === '1';

	// Process updates
	$updated_fields = array();
	$pending_fields = array();

	// Get current business data for comparison
	$current_business = get_post( $business_id );

	// Business Name (post title)
	if ( isset( $_POST['business_name'] ) ) {
		$business_name = sanitize_text_field( $_POST['business_name'] );
		$current_name  = $current_business->post_title;

		// Only process if value changed
		if ( $business_name !== $current_name ) {
			if ( nmda_field_requires_approval( 'business_name' ) ) {
				nmda_update_business_field( $business_id, 'business_name', $business_name, $user_id );
				$pending_fields[] = 'Business Name';
			} else {
				wp_update_post( array(
					'ID'         => $business_id,
					'post_title' => $business_name,
				) );
				$updated_fields[] = 'Business Name';
			}
		}
	}

	// Business Description (post content)
	if ( isset( $_POST['business_description'] ) ) {
		$business_description = wp_kses_post( $_POST['business_description'] );
		$current_description  = $current_business->post_content;

		// Only process if value changed
		if ( $business_description !== $current_description ) {
			wp_update_post( array(
				'ID'           => $business_id,
				'post_content' => $business_description,
			) );
			$updated_fields[] = 'Business Description';
		}
	}

	// ACF Fields
	$acf_fields = array(
		'dba_name',
		'business_phone',
		'business_email',
		'website',
		'business_hours',
		'number_of_employees',
		'facebook',
		'instagram',
		'twitter',
		'pinterest',
		'sales_additional_info',
	);

	foreach ( $acf_fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$value         = sanitize_text_field( $_POST[ $field ] );
			$current_value = get_field( $field, $business_id );

			// Normalize empty values for comparison
			$value         = ( $value === '' ) ? null : $value;
			$current_value = ( $current_value === '' || $current_value === false ) ? null : $current_value;

			// Only process if value changed
			if ( $value !== $current_value ) {
				if ( nmda_field_requires_approval( $field ) ) {
					nmda_update_business_field( $business_id, $field, $value, $user_id );
					$pending_fields[] = ucwords( str_replace( '_', ' ', $field ) );
				} else {
					update_field( $field, $value, $business_id );
					$updated_fields[] = ucwords( str_replace( '_', ' ', $field ) );
				}
			}
		}
	}

	// Sales types (array)
	if ( isset( $_POST['sales_types'] ) && is_array( $_POST['sales_types'] ) ) {
		$sales_types         = array_map( 'sanitize_text_field', $_POST['sales_types'] );
		$current_sales_types = get_field( 'sales_type', $business_id );

		// Normalize for comparison
		$current_sales_types = is_array( $current_sales_types ) ? $current_sales_types : array();
		sort( $sales_types );
		sort( $current_sales_types );

		// Only update if changed
		if ( $sales_types !== $current_sales_types ) {
			update_field( 'sales_type', $sales_types, $business_id );
			$updated_fields[] = 'Sales Types';
		}
	}

	// Product types (taxonomy)
	if ( isset( $_POST['product_types'] ) && is_array( $_POST['product_types'] ) ) {
		$product_types         = array_map( 'intval', $_POST['product_types'] );
		$current_product_terms = wp_get_post_terms( $business_id, 'product_type', array( 'fields' => 'ids' ) );
		$current_product_terms = is_array( $current_product_terms ) ? $current_product_terms : array();

		// Normalize for comparison
		sort( $product_types );
		sort( $current_product_terms );

		// Only update if changed
		if ( $product_types !== $current_product_terms ) {
			wp_set_post_terms( $business_id, $product_types, 'product_type' );
			$updated_fields[] = 'Product Types';
		}
	}

	// Primary address - stored in ACF fields
	$address_acf_mapping = array(
		'address_street'   => 'primary_address',
		'address_street_2' => 'primary_address_2',
		'address_city'     => 'primary_city',
		'address_state'    => 'primary_state',
		'address_zip'      => 'primary_zip',
		'address_county'   => 'primary_county',
	);

	$address_changed = false;

	foreach ( $address_acf_mapping as $post_key => $acf_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			$new_value     = sanitize_text_field( $_POST[ $post_key ] );
			$current_value = get_field( $acf_key, $business_id );

			// Normalize empty values for comparison
			$new_value     = ( $new_value === '' ) ? null : $new_value;
			$current_value = ( $current_value === '' || $current_value === false ) ? null : $current_value;

			// Check if value changed
			if ( $new_value !== $current_value ) {
				$address_changed = true;
				// Update the ACF field
				update_field( $acf_key, $_POST[ $post_key ], $business_id );
			}
		}
	}

	if ( $address_changed ) {
		$updated_fields[] = 'Business Address';
	}

	// Log the update
	nmda_log_field_change( $business_id, 'bulk_update', '', '', $user_id );

	// Build response message
	$message = '';
	$message_html = '';

	if ( $is_autosave ) {
		$message = 'Changes saved automatically.';
		$message_html = '<strong>Changes saved automatically.</strong>';
	} else {
		if ( ! empty( $updated_fields ) || ! empty( $pending_fields ) ) {
			$message_html = '<strong>Your business profile has been updated successfully!</strong>';
			$message = 'Your business profile has been updated successfully!';

			if ( ! empty( $updated_fields ) ) {
				$count = count( $updated_fields );
				$message_html .= '<div class="mt-2"><strong>' . $count . ' field' . ( $count > 1 ? 's' : '' ) . ' updated:</strong>';
				$message_html .= '<ul class="mb-0 mt-1">';
				foreach ( $updated_fields as $field ) {
					$message_html .= '<li>' . esc_html( $field ) . '</li>';
				}
				$message_html .= '</ul></div>';
			}

			if ( ! empty( $pending_fields ) ) {
				$count = count( $pending_fields );
				$message_html .= '<div class="mt-2"><strong>' . $count . ' field' . ( $count > 1 ? 's' : '' ) . ' pending approval:</strong>';
				$message_html .= '<ul class="mb-0 mt-1">';
				foreach ( $pending_fields as $field ) {
					$message_html .= '<li>' . esc_html( $field ) . '</li>';
				}
				$message_html .= '</ul>';
				$message_html .= '<small class="text-muted d-block mt-1">These changes will be reviewed by an administrator.</small>';
				$message_html .= '</div>';
			}
		} else {
			$message = 'No changes detected.';
			$message_html = '<strong>No changes were made to your profile.</strong>';
		}
	}

	wp_send_json_success( array(
		'message'        => trim( $message ),
		'message_html'   => $message_html,
		'updated_fields' => $updated_fields,
		'pending_fields' => $pending_fields,
	) );
}
add_action( 'wp_ajax_nmda_update_business_profile', 'nmda_ajax_update_business_profile' );

/**
 * Get business primary address
 *
 * @param int $business_id Business post ID.
 * @return array Primary address data or empty array.
 */
function nmda_get_business_primary_address( $business_id ) {
	// Get address fields from ACF using correct field names
	// Return with keys that match the form field expectations
	return array(
		'street_address'   => get_field( 'primary_address', $business_id ) ?: '',
		'street_address_2' => get_field( 'primary_address_2', $business_id ) ?: '',
		'city'             => get_field( 'primary_city', $business_id ) ?: '',
		'state'            => get_field( 'primary_state', $business_id ) ?: 'NM',
		'zip_code'         => get_field( 'primary_zip', $business_id ) ?: '',
		'county'           => get_field( 'primary_county', $business_id ) ?: '', // County field may not exist yet
	);
}
