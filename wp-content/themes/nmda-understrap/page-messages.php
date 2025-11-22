<?php
/**
 * Template Name: Messages
 * Template for member and admin messaging
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Require login
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$current_user = wp_get_current_user();
$is_admin = current_user_can( 'manage_options' );

// Get initial messages - default to all conversations
$filter = isset( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all';
$messages = nmda_get_messages( $current_user->ID, $filter, 50, 0 );

// Get message detail if viewing a specific message
$message_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$current_message = null;
if ( $message_id ) {
    $current_message = nmda_get_message_detail( $message_id, $current_user->ID );
    if ( is_wp_error( $current_message ) ) {
        $current_message = null;
    } else {
        // Mark as read when viewing
        nmda_mark_message_read( $message_id, $current_user->ID );
    }
}

// Get unread count
$unread_count = nmda_get_unread_count( $current_user->ID );

// Get user's businesses for message composer
$user_businesses = array();
if ( ! $is_admin ) {
    global $wpdb;
    $table = nmda_get_user_business_table();
    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT business_id FROM $table WHERE user_id = %d AND status = 'active'",
        $current_user->ID
    ) );
    foreach ( $results as $row ) {
        $business = get_post( $row->business_id );
        if ( $business ) {
            $user_businesses[] = array(
                'id'   => $business->ID,
                'name' => $business->post_title,
            );
        }
    }
}

// Get admin users for message composer (for admin sending to specific admin)
$admin_users = array();
if ( $is_admin ) {
    $admin_users = get_users( array( 'role' => 'administrator' ) );
}

get_header();
?>

<div class="wrapper" id="page-wrapper">
    <div class="container" id="content">

        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="page-title">
                            <i class="fa fa-envelope"></i> Messages
                            <?php if ( $unread_count > 0 ) : ?>
                                <span class="badge badge-danger ml-2"><?php echo $unread_count; ?> Unread</span>
                            <?php endif; ?>
                        </h1>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-outline-light">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#newMessageModal">
                            <i class="fa fa-plus"></i> New Message
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Messages List -->
            <div class="col-md-4">
                <div class="card messages-list-card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>"
                                   href="?filter=all">
                                    <i class="fa fa-comments"></i> All Conversations
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'inbox' ? 'active' : ''; ?>"
                                   href="?filter=inbox">
                                    <i class="fa fa-inbox"></i> Unread
                                    <?php if ( $unread_count > 0 ) : ?>
                                        <span class="badge badge-danger"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0" id="messages-list-container">
                        <?php if ( empty( $messages ) ) : ?>
                            <div class="text-center py-5 empty-state">
                                <i class="fa fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-3">No messages yet.</p>
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newMessageModal">
                                    <i class="fa fa-plus"></i> Send your first message
                                </button>
                            </div>
                        <?php else : ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ( $messages as $message ) : ?>
                                    <a href="?id=<?php echo $message->id; ?>&filter=<?php echo esc_attr( $filter ); ?>"
                                       class="list-group-item list-group-item-action message-item <?php echo $message_id == $message->id ? 'active' : ''; ?> <?php echo $message->is_unread && !$message->sender['is_me'] ? 'unread' : ''; ?>"
                                       data-message-id="<?php echo $message->id; ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <?php if ( $message->is_unread && !$message->sender['is_me'] ) : ?>
                                                        <span class="badge badge-primary badge-sm mr-1">New</span>
                                                    <?php endif; ?>
                                                    <?php if ( $message->sender['is_me'] ) : ?>
                                                        <i class="fa fa-reply text-muted"></i> You
                                                        <span class="text-muted">→</span> <?php echo esc_html( $message->recipient['name'] ); ?>
                                                    <?php else : ?>
                                                        <i class="fa fa-envelope text-muted"></i> <?php echo esc_html( $message->sender['name'] ); ?>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="mb-1 message-subject"><?php echo esc_html( $message->subject ?: '(No subject)' ); ?></p>
                                                <?php if ( $message->business_name ) : ?>
                                                    <small class="text-muted">
                                                        <i class="fa fa-building"></i> <?php echo esc_html( $message->business_name ); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted text-nowrap ml-2">
                                                <?php echo human_time_diff( strtotime( $message->created_at ), current_time( 'timestamp' ) ); ?> ago
                                            </small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Message Detail -->
            <div class="col-md-8">
                <div class="card message-detail-card">
                    <?php if ( $current_message ) : ?>
                        <!-- Message Header -->
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1"><?php echo esc_html( $current_message->subject ?: '(No subject)' ); ?></h5>
                                    <div class="message-meta">
                                        <span class="badge badge-info"><?php echo esc_html( ucfirst( $current_message->type ) ); ?></span>
                                        <?php if ( $current_message->business_name ) : ?>
                                            <span class="ml-2">
                                                <i class="fa fa-building"></i> <?php echo esc_html( $current_message->business_name ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary reply-btn" data-message-id="<?php echo $current_message->id; ?>">
                                        <i class="fa fa-reply"></i> Reply
                                    </button>
                                    <?php if ( $is_admin || $current_message->sender['is_me'] ) : ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-message-btn" data-message-id="<?php echo $current_message->id; ?>">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Message Body -->
                        <div class="card-body message-body">
                            <!-- Original Message -->
                            <div class="message-thread-item">
                                <div class="message-sender-info">
                                    <strong><?php echo esc_html( $current_message->sender['name'] ); ?></strong>
                                    <span class="text-muted ml-2">
                                        <?php echo date( 'F j, Y g:i A', strtotime( $current_message->created_at ) ); ?>
                                    </span>
                                </div>
                                <div class="message-content mt-2">
                                    <?php echo wpautop( wp_kses_post( $current_message->message ) ); ?>
                                </div>
                            </div>

                            <!-- Thread (Previous Messages) -->
                            <?php if ( ! empty( $current_message->thread ) ) : ?>
                                <hr>
                                <div class="message-thread">
                                    <h6 class="text-muted mb-3">Previous Messages</h6>
                                    <?php foreach ( array_reverse( $current_message->thread ) as $thread_msg ) : ?>
                                        <div class="message-thread-item">
                                            <div class="message-sender-info">
                                                <strong><?php echo esc_html( $thread_msg->sender['name'] ); ?></strong>
                                                <span class="text-muted ml-2">
                                                    <?php echo date( 'F j, Y g:i A', strtotime( $thread_msg->created_at ) ); ?>
                                                </span>
                                            </div>
                                            <div class="message-content mt-2">
                                                <?php echo wpautop( wp_kses_post( $thread_msg->message ) ); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Replies -->
                            <?php if ( ! empty( $current_message->replies ) ) : ?>
                                <hr>
                                <div class="message-replies">
                                    <h6 class="text-muted mb-3">Replies</h6>
                                    <?php foreach ( $current_message->replies as $reply ) : ?>
                                        <div class="message-thread-item">
                                            <div class="message-sender-info">
                                                <strong><?php echo esc_html( $reply->sender['name'] ); ?></strong>
                                                <span class="text-muted ml-2">
                                                    <?php echo date( 'F j, Y g:i A', strtotime( $reply->created_at ) ); ?>
                                                </span>
                                            </div>
                                            <div class="message-content mt-2">
                                                <?php echo wpautop( wp_kses_post( $reply->message ) ); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Reply Form -->
                            <div id="reply-form-container" style="display: none;">
                                <hr>
                                <h6>Reply</h6>
                                <form id="reply-form">
                                    <input type="hidden" name="parent_id" value="<?php echo $current_message->id; ?>">
                                    <input type="hidden" name="to_user_id" value="<?php echo $current_message->sender['id']; ?>">
                                    <input type="hidden" name="business_id" value="<?php echo $current_message->business_id; ?>">
                                    <input type="hidden" name="type" value="<?php echo esc_attr( $current_message->type ); ?>">

                                    <div class="form-group">
                                        <textarea class="form-control" name="message" rows="4" placeholder="Type your reply..." required></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-secondary cancel-reply-btn">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-paper-plane"></i> Send Reply
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php else : ?>
                        <!-- No Message Selected -->
                        <div class="card-body text-center py-5 empty-state">
                            <i class="fa fa-envelope-open-o" style="font-size: 64px; color: #ccc;"></i>
                            <p class="text-muted mt-3 lead">Select a message to view</p>
                            <p class="text-muted">Or start a new conversation</p>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#newMessageModal">
                                <i class="fa fa-plus"></i> New Message
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-envelope"></i> New Message
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="new-message-form">
                <div class="modal-body">

                    <?php if ( ! $is_admin ) : ?>
                        <!-- Member sending to admin -->
                        <input type="hidden" name="to_user_id" value="1">

                        <?php if ( ! empty( $user_businesses ) ) : ?>
                            <div class="form-group">
                                <label for="message-business">Related Business</label>
                                <select class="form-control" name="business_id" id="message-business">
                                    <option value="">Select a business (optional)</option>
                                    <?php foreach ( $user_businesses as $business ) : ?>
                                        <option value="<?php echo $business['id']; ?>">
                                            <?php echo esc_html( $business['name'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                    <?php else : ?>
                        <!-- Admin sending - can select recipient -->
                        <div class="form-group">
                            <label for="message-recipient">To <span class="text-danger">*</span></label>
                            <select class="form-control" name="to_user_id" id="message-recipient" required>
                                <option value="">Select recipient...</option>
                                <?php
                                // Get all active members
                                $members = get_users( array( 'role__not_in' => array( 'administrator' ) ) );
                                foreach ( $members as $member ) :
                                ?>
                                    <option value="<?php echo $member->ID; ?>">
                                        <?php echo esc_html( $member->display_name . ' (' . $member->user_email . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message-business-admin">Related Business</label>
                            <input type="number" class="form-control" name="business_id" id="message-business-admin"
                                   placeholder="Business ID (optional)">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="message-type">Message Type</label>
                        <select class="form-control" name="type" id="message-type">
                            <option value="general">General Inquiry</option>
                            <option value="application">Business Application</option>
                            <option value="reimbursement">Reimbursement</option>
                            <option value="technical">Technical Support</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message-subject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="subject" id="message-subject"
                               placeholder="Enter subject" required>
                    </div>

                    <div class="form-group">
                        <label for="message-content">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="message" id="message-content" rows="6"
                                  placeholder="Type your message..." required></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-paper-plane"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
get_footer();
