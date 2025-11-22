<?php
/**
 * NMDA Communications/Messaging Functions
 *
 * Handles in-portal messaging between members and admins
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Send a message
 *
 * @param int    $from_user_id  User ID of sender.
 * @param int    $to_user_id    User ID of recipient.
 * @param int    $business_id   Related business ID (optional).
 * @param string $subject       Message subject.
 * @param string $message       Message content.
 * @param string $type          Message type (general, application, reimbursement, technical).
 * @param int    $parent_id     Parent message ID for threading (optional).
 * @return int|WP_Error Message ID on success, WP_Error on failure.
 */
function nmda_send_message( $from_user_id, $to_user_id, $business_id = null, $subject = '', $message = '', $type = 'general', $parent_id = null ) {
    global $wpdb;
    $table = nmda_get_communications_table();

    // Validate required fields
    if ( empty( $from_user_id ) || empty( $to_user_id ) ) {
        return new WP_Error( 'missing_users', __( 'Sender and recipient are required.', 'nmda-understrap' ) );
    }

    if ( empty( $message ) ) {
        return new WP_Error( 'empty_message', __( 'Message content is required.', 'nmda-understrap' ) );
    }

    // Determine if sender is admin or member
    $sender = get_userdata( $from_user_id );
    $recipient = get_userdata( $to_user_id );

    if ( ! $sender || ! $recipient ) {
        return new WP_Error( 'invalid_users', __( 'Invalid sender or recipient.', 'nmda-understrap' ) );
    }

    $sender_is_admin = user_can( $from_user_id, 'manage_options' );

    // Build insert data based on who's sending
    $data = array(
        'business_id' => $business_id,
        'type'        => $type,
        'subject'     => sanitize_text_field( $subject ),
        'message'     => wp_kses_post( $message ),
        'parent_id'   => $parent_id,
        'read_status' => 0,
        'sender_id'   => $from_user_id,
        'created_at'  => current_time( 'mysql' ),
    );

    if ( $sender_is_admin ) {
        // Admin sending to member
        $data['admin_id'] = $from_user_id;
        $data['user_id']  = $to_user_id;
    } else {
        // Member sending to admin
        $data['user_id']  = $from_user_id;
        $data['admin_id'] = $to_user_id;
    }

    $result = $wpdb->insert(
        $table,
        $data,
        array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%d' )
    );

    if ( ! $result ) {
        return new WP_Error( 'db_insert_error', __( 'Failed to send message.', 'nmda-understrap' ) );
    }

    $message_id = $wpdb->insert_id;

    // Send email notification
    nmda_send_message_notification( $message_id, $to_user_id );

    return $message_id;
}

/**
 * Get messages for a user
 *
 * @param int    $user_id User ID.
 * @param string $filter  Filter type: 'inbox', 'sent', or 'all'. Default 'all'.
 * @param int    $limit   Number of messages to retrieve. Default 50.
 * @param int    $offset  Offset for pagination. Default 0.
 * @return array Array of message objects.
 */
function nmda_get_messages( $user_id, $filter = 'all', $limit = 50, $offset = 0 ) {
    global $wpdb;
    $table = nmda_get_communications_table();

    $is_admin = user_can( $user_id, 'manage_options' );

    // Build WHERE clause based on filter
    $where_clauses = array();

    if ( $filter === 'inbox' ) {
        // Messages received by this user (where they are NOT the sender)
        if ( $is_admin ) {
            $where_clauses[] = $wpdb->prepare( 'admin_id = %d AND sender_id != %d', $user_id, $user_id );
        } else {
            $where_clauses[] = $wpdb->prepare( 'user_id = %d AND sender_id != %d', $user_id, $user_id );
        }
    } elseif ( $filter === 'sent' ) {
        // Messages sent by this user
        $where_clauses[] = $wpdb->prepare( 'sender_id = %d', $user_id );
    } else {
        // All messages involving this user
        if ( $is_admin ) {
            $where_clauses[] = $wpdb->prepare( 'admin_id = %d OR user_id = %d', $user_id, $user_id );
        } else {
            $where_clauses[] = $wpdb->prepare( 'user_id = %d', $user_id );
        }
    }

    $where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );

    // Get messages
    $sql = "SELECT * FROM $table
            $where_sql
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d";

    $messages = $wpdb->get_results( $wpdb->prepare( $sql, $limit, $offset ) );

    // Enrich messages with sender/recipient info
    foreach ( $messages as &$message ) {
        $message->sender = nmda_get_message_sender( $message, $user_id );
        $message->recipient = nmda_get_message_recipient( $message, $user_id );
        $message->is_unread = ! $message->read_status;
        $message->business_name = $message->business_id ? get_the_title( $message->business_id ) : '';
    }

    return $messages;
}

/**
 * Get message detail and thread
 *
 * @param int $message_id Message ID.
 * @param int $user_id    User ID requesting the message.
 * @return object|WP_Error Message object with thread, or WP_Error.
 */
function nmda_get_message_detail( $message_id, $user_id ) {
    global $wpdb;
    $table = nmda_get_communications_table();

    // Get the message
    $message = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $message_id
    ) );

    if ( ! $message ) {
        return new WP_Error( 'not_found', __( 'Message not found.', 'nmda-understrap' ) );
    }

    // Verify user has permission to view this message
    $is_admin = user_can( $user_id, 'manage_options' );
    $can_view = false;

    if ( $is_admin ) {
        $can_view = ( $message->admin_id == $user_id || $message->user_id == $user_id );
    } else {
        $can_view = ( $message->user_id == $user_id );
    }

    if ( ! $can_view ) {
        return new WP_Error( 'forbidden', __( 'You do not have permission to view this message.', 'nmda-understrap' ) );
    }

    // Enrich message data
    $message->sender = nmda_get_message_sender( $message, $user_id );
    $message->recipient = nmda_get_message_recipient( $message, $user_id );
    $message->is_unread = ! $message->read_status;
    $message->business_name = $message->business_id ? get_the_title( $message->business_id ) : '';

    // Get thread if this is a reply
    $message->thread = array();
    if ( $message->parent_id ) {
        $parent = nmda_get_message_detail( $message->parent_id, $user_id );
        if ( ! is_wp_error( $parent ) ) {
            $message->thread[] = $parent;
            // Get parent's thread recursively
            if ( ! empty( $parent->thread ) ) {
                $message->thread = array_merge( $message->thread, $parent->thread );
            }
        }
    }

    // Get replies to this message
    $replies = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table WHERE parent_id = %d ORDER BY created_at ASC",
        $message_id
    ) );

    foreach ( $replies as &$reply ) {
        $reply->sender = nmda_get_message_sender( $reply, $user_id );
        $reply->recipient = nmda_get_message_recipient( $reply, $user_id );
        $reply->is_unread = ! $reply->read_status;
    }

    $message->replies = $replies;

    return $message;
}

/**
 * Mark message as read
 *
 * @param int $message_id Message ID.
 * @param int $user_id    User ID marking as read.
 * @return bool True on success, false on failure.
 */
function nmda_mark_message_read( $message_id, $user_id ) {
    global $wpdb;
    $table = nmda_get_communications_table();

    // Get the message to verify permissions
    $message = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $message_id
    ) );

    if ( ! $message ) {
        return false;
    }

    // Only mark as read if user is the recipient
    $is_admin = user_can( $user_id, 'manage_options' );
    $is_recipient = false;

    if ( $is_admin ) {
        $is_recipient = ( $message->admin_id == $user_id );
    } else {
        $is_recipient = ( $message->user_id == $user_id && $message->admin_id !== null );
    }

    if ( ! $is_recipient ) {
        return false;
    }

    // Update read status
    $result = $wpdb->update(
        $table,
        array( 'read_status' => 1 ),
        array( 'id' => $message_id ),
        array( '%d' ),
        array( '%d' )
    );

    return $result !== false;
}

/**
 * Get unread message count for user
 *
 * @param int $user_id User ID.
 * @return int Unread message count.
 */
function nmda_get_unread_count( $user_id ) {
    global $wpdb;
    $table = nmda_get_communications_table();

    $is_admin = user_can( $user_id, 'manage_options' );

    if ( $is_admin ) {
        // Admin: count messages where admin is recipient (not sender) and unread
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table
            WHERE admin_id = %d
            AND sender_id != %d
            AND read_status = 0",
            $user_id,
            $user_id
        ) );
    } else {
        // Member: count messages where member is recipient (not sender) and unread
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table
            WHERE user_id = %d
            AND sender_id != %d
            AND read_status = 0",
            $user_id,
            $user_id
        ) );
    }

    return intval( $count );
}

/**
 * Delete a message
 *
 * @param int $message_id Message ID.
 * @param int $user_id    User ID requesting deletion.
 * @return bool True on success, false on failure.
 */
function nmda_delete_message( $message_id, $user_id ) {
    global $wpdb;
    $table = nmda_get_communications_table();

    // Get the message to verify permissions
    $message = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $message_id
    ) );

    if ( ! $message ) {
        return false;
    }

    // Only allow deletion if user is sender or admin
    $is_admin = user_can( $user_id, 'manage_options' );
    $can_delete = false;

    if ( $is_admin ) {
        $can_delete = true; // Admins can delete any message
    } else {
        // Members can only delete messages they sent
        $can_delete = ( $message->user_id == $user_id && $message->admin_id === null );
    }

    if ( ! $can_delete ) {
        return false;
    }

    // Delete the message
    $result = $wpdb->delete(
        $table,
        array( 'id' => $message_id ),
        array( '%d' )
    );

    return $result !== false;
}

/**
 * Get message sender information
 *
 * @param object $message  Message object.
 * @param int    $user_id  Current user ID.
 * @return array Sender info array.
 */
function nmda_get_message_sender( $message, $user_id ) {
    // Determine who sent the message
    if ( $message->admin_id && $message->user_id ) {
        // Both fields populated - admin sent to member
        $sender_id = $message->admin_id;
    } elseif ( $message->user_id ) {
        // Only user_id - member sent to admin
        $sender_id = $message->user_id;
    } else {
        return array( 'id' => 0, 'name' => 'Unknown', 'is_me' => false );
    }

    $sender = get_userdata( $sender_id );

    return array(
        'id'     => $sender_id,
        'name'   => $sender ? $sender->display_name : 'Unknown',
        'email'  => $sender ? $sender->user_email : '',
        'is_me'  => ( $sender_id == $user_id ),
    );
}

/**
 * Get message recipient information
 *
 * @param object $message  Message object.
 * @param int    $user_id  Current user ID.
 * @return array Recipient info array.
 */
function nmda_get_message_recipient( $message, $user_id ) {
    // Determine who receives the message
    if ( $message->admin_id && $message->user_id ) {
        // Both fields populated - admin sent to member
        $recipient_id = $message->user_id;
    } elseif ( $message->admin_id ) {
        // Only admin_id - member sent to admin
        $recipient_id = $message->admin_id;
    } else {
        return array( 'id' => 0, 'name' => 'Unknown', 'is_me' => false );
    }

    $recipient = get_userdata( $recipient_id );

    return array(
        'id'     => $recipient_id,
        'name'   => $recipient ? $recipient->display_name : 'Unknown',
        'email'  => $recipient ? $recipient->user_email : '',
        'is_me'  => ( $recipient_id == $user_id ),
    );
}

/**
 * Send email notification for new message
 *
 * @param int $message_id   Message ID.
 * @param int $recipient_id Recipient user ID.
 * @return bool True if email sent, false otherwise.
 */
function nmda_send_message_notification( $message_id, $recipient_id ) {
    global $wpdb;
    $table = nmda_get_communications_table();

    $message = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $message_id
    ) );

    if ( ! $message ) {
        return false;
    }

    $recipient = get_userdata( $recipient_id );
    if ( ! $recipient ) {
        return false;
    }

    $sender = nmda_get_message_sender( $message, $recipient_id );
    $business_name = $message->business_id ? get_the_title( $message->business_id ) : 'N/A';

    // Build email
    $to = $recipient->user_email;
    $subject = sprintf( __( 'New Message from %s', 'nmda-understrap' ), $sender['name'] );

    $message_body = sprintf(
        __( "You have received a new message in the NMDA Portal.\n\n", 'nmda-understrap' )
    );
    $message_body .= sprintf( __( "From: %s\n", 'nmda-understrap' ), $sender['name'] );
    $message_body .= sprintf( __( "Business: %s\n", 'nmda-understrap' ), $business_name );
    $message_body .= sprintf( __( "Subject: %s\n\n", 'nmda-understrap' ), $message->subject );
    $message_body .= sprintf( __( "Message:\n%s\n\n", 'nmda-understrap' ), wp_strip_all_tags( $message->message ) );
    $message_body .= sprintf( __( "View and reply: %s\n\n", 'nmda-understrap' ), home_url( '/messages/?id=' . $message_id ) );
    $message_body .= __( "New Mexico Department of Agriculture", 'nmda-understrap' );

    return wp_mail( $to, $subject, $message_body );
}

/**
 * AJAX: Send message
 */
function nmda_ajax_send_message() {
    check_ajax_referer( 'nmda-ajax-nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in to send messages.' ) );
    }

    $from_user_id = get_current_user_id();
    $to_user_id   = intval( $_POST['to_user_id'] ?? 0 );
    $business_id  = intval( $_POST['business_id'] ?? 0 );
    $subject      = sanitize_text_field( $_POST['subject'] ?? '' );
    $message      = wp_kses_post( $_POST['message'] ?? '' );
    $type         = sanitize_text_field( $_POST['type'] ?? 'general' );
    $parent_id    = intval( $_POST['parent_id'] ?? 0 ) ?: null;

    $result = nmda_send_message( $from_user_id, $to_user_id, $business_id, $subject, $message, $type, $parent_id );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    wp_send_json_success( array(
        'message'    => 'Message sent successfully.',
        'message_id' => $result,
    ) );
}
add_action( 'wp_ajax_nmda_send_message', 'nmda_ajax_send_message' );

/**
 * AJAX: Get messages
 */
function nmda_ajax_get_messages() {
    check_ajax_referer( 'nmda-ajax-nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
    }

    $user_id = get_current_user_id();
    $filter  = sanitize_text_field( $_POST['filter'] ?? 'all' );
    $limit   = intval( $_POST['limit'] ?? 50 );
    $offset  = intval( $_POST['offset'] ?? 0 );

    $messages = nmda_get_messages( $user_id, $filter, $limit, $offset );

    wp_send_json_success( array( 'messages' => $messages ) );
}
add_action( 'wp_ajax_nmda_get_messages', 'nmda_ajax_get_messages' );

/**
 * AJAX: Get message detail
 */
function nmda_ajax_get_message_detail() {
    check_ajax_referer( 'nmda-ajax-nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
    }

    $user_id    = get_current_user_id();
    $message_id = intval( $_POST['message_id'] ?? 0 );

    if ( ! $message_id ) {
        wp_send_json_error( array( 'message' => 'Message ID is required.' ) );
    }

    $message = nmda_get_message_detail( $message_id, $user_id );

    if ( is_wp_error( $message ) ) {
        wp_send_json_error( array( 'message' => $message->get_error_message() ) );
    }

    wp_send_json_success( array( 'message' => $message ) );
}
add_action( 'wp_ajax_nmda_get_message_detail', 'nmda_ajax_get_message_detail' );

/**
 * AJAX: Mark message as read
 */
function nmda_ajax_mark_message_read() {
    check_ajax_referer( 'nmda-ajax-nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
    }

    $user_id    = get_current_user_id();
    $message_id = intval( $_POST['message_id'] ?? 0 );

    if ( ! $message_id ) {
        wp_send_json_error( array( 'message' => 'Message ID is required.' ) );
    }

    $result = nmda_mark_message_read( $message_id, $user_id );

    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Failed to mark message as read.' ) );
    }

    // Get updated unread count
    $unread_count = nmda_get_unread_count( $user_id );

    wp_send_json_success( array(
        'message'       => 'Message marked as read.',
        'unread_count'  => $unread_count,
    ) );
}
add_action( 'wp_ajax_nmda_mark_message_read', 'nmda_ajax_mark_message_read' );

/**
 * AJAX: Delete message
 */
function nmda_ajax_delete_message() {
    check_ajax_referer( 'nmda-ajax-nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
    }

    $user_id    = get_current_user_id();
    $message_id = intval( $_POST['message_id'] ?? 0 );

    if ( ! $message_id ) {
        wp_send_json_error( array( 'message' => 'Message ID is required.' ) );
    }

    $result = nmda_delete_message( $message_id, $user_id );

    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Failed to delete message or you do not have permission.' ) );
    }

    wp_send_json_success( array( 'message' => 'Message deleted successfully.' ) );
}
add_action( 'wp_ajax_nmda_delete_message', 'nmda_ajax_delete_message' );

/**
 * AJAX: Get unread count
 */
function nmda_ajax_get_unread_count() {
    check_ajax_referer( 'nmda-ajax-nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
    }

    $user_id = get_current_user_id();
    $count = nmda_get_unread_count( $user_id );

    wp_send_json_success( array( 'unread_count' => $count ) );
}
add_action( 'wp_ajax_nmda_get_unread_count', 'nmda_ajax_get_unread_count' );

/**
 * Add Messages menu item with unread count badge to navigation
 *
 * @param string $items  The HTML list content for the menu items.
 * @param object $args   An object containing wp_nav_menu() arguments.
 * @return string Modified menu items
 */
function nmda_add_messages_to_nav( $items, $args ) {
    // Only add to primary menu and for logged-in users
    if ( $args->theme_location !== 'primary' || ! is_user_logged_in() ) {
        return $items;
    }

    $user_id = get_current_user_id();
    $unread_count = nmda_get_unread_count( $user_id );

    // Get the Messages page URL (assuming it exists)
    $messages_page = get_page_by_path( 'messages' );
    if ( ! $messages_page ) {
        return $items;
    }

    $messages_url = get_permalink( $messages_page->ID );

    // Build the badge HTML
    $badge_html = '';
    if ( $unread_count > 0 ) {
        $badge_html = sprintf(
            ' <span class="badge badge-danger nmda-messages-badge" id="nav-messages-badge">%d</span>',
            $unread_count
        );
    }

    // Build the menu item HTML
    $messages_item = sprintf(
        '<li class="menu-item nav-item"><a href="%s" class="nav-link"><i class="fa fa-envelope"></i> Messages%s</a></li>',
        esc_url( $messages_url ),
        $badge_html
    );

    // Add the item to the end of the menu
    $items .= $messages_item;

    return $items;
}
add_filter( 'wp_nav_menu_items', 'nmda_add_messages_to_nav', 10, 2 );
