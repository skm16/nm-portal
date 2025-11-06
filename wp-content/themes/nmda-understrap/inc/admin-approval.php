<?php
/**
 * NMDA Admin Approval Interface
 * Handles business application review and approval workflow
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register admin menu page
 */
function nmda_register_approval_admin_page() {
	add_menu_page(
		'Application Reviews',
		'Applications',
		'manage_options',
		'nmda-applications',
		'nmda_render_applications_page',
		'dashicons-yes-alt',
		26
	);

	// Add submenu pages
	add_submenu_page(
		'nmda-applications',
		'Pending Applications',
		'Pending',
		'manage_options',
		'nmda-applications&status=pending',
		'nmda_render_applications_page'
	);

	add_submenu_page(
		'nmda-applications',
		'Approved Applications',
		'Approved',
		'manage_options',
		'nmda-applications&status=approved',
		'nmda_render_applications_page'
	);

	add_submenu_page(
		'nmda-applications',
		'Rejected Applications',
		'Rejected',
		'manage_options',
		'nmda-applications&status=rejected',
		'nmda_render_applications_page'
	);
}
add_action( 'admin_menu', 'nmda_register_approval_admin_page' );

/**
 * Enqueue admin styles and scripts
 */
function nmda_enqueue_admin_approval_assets( $hook ) {
	if ( strpos( $hook, 'nmda-applications' ) === false ) {
		return;
	}

	// Enqueue admin CSS
	wp_enqueue_style(
		'nmda-admin-approval',
		NMDA_THEME_URI . '/assets/css/admin-approval.css',
		array(),
		NMDA_THEME_VERSION
	);

	// Enqueue admin JS
	wp_enqueue_script(
		'nmda-admin-approval',
		NMDA_THEME_URI . '/assets/js/admin-approval.js',
		array( 'jquery' ),
		NMDA_THEME_VERSION,
		true
	);

	// Localize script
	wp_localize_script(
		'nmda-admin-approval',
		'nmdaAdmin',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nmda-admin-nonce' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'nmda_enqueue_admin_approval_assets' );

/**
 * Render applications management page
 */
function nmda_render_applications_page() {
	// Get current status filter
	$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';

	// Get applications
	$args = array(
		'post_type'      => 'nmda_business',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	// Add status meta query if not 'all'
	if ( $status !== 'all' ) {
		$args['meta_query'] = array(
			array(
				'key'   => 'approval_status',
				'value' => $status,
			),
		);
	}

	$applications = new WP_Query( $args );

	// Get counts for each status
	$counts = nmda_get_application_counts();

	?>
	<div class="wrap nmda-admin-wrap">
		<h1 class="wp-heading-inline">Business Applications</h1>

		<hr class="wp-header-end">

		<!-- Status Filter Tabs -->
		<ul class="subsubsub">
			<li class="all">
				<a href="<?php echo admin_url( 'admin.php?page=nmda-applications' ); ?>" class="<?php echo $status === 'all' ? 'current' : ''; ?>">
					All <span class="count">(<?php echo $counts['all']; ?>)</span>
				</a> |
			</li>
			<li class="pending">
				<a href="<?php echo admin_url( 'admin.php?page=nmda-applications&status=pending' ); ?>" class="<?php echo $status === 'pending' ? 'current' : ''; ?>">
					Pending <span class="count">(<?php echo $counts['pending']; ?>)</span>
				</a> |
			</li>
			<li class="approved">
				<a href="<?php echo admin_url( 'admin.php?page=nmda-applications&status=approved' ); ?>" class="<?php echo $status === 'approved' ? 'current' : ''; ?>">
					Approved <span class="count">(<?php echo $counts['approved']; ?>)</span>
				</a> |
			</li>
			<li class="rejected">
				<a href="<?php echo admin_url( 'admin.php?page=nmda-applications&status=rejected' ); ?>" class="<?php echo $status === 'rejected' ? 'current' : ''; ?>">
					Rejected <span class="count">(<?php echo $counts['rejected']; ?>)</span>
				</a>
			</li>
		</ul>

		<!-- Bulk Actions -->
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<select name="bulk-action" id="bulk-action-selector-top">
					<option value="-1">Bulk Actions</option>
					<option value="approve">Approve</option>
					<option value="reject">Reject</option>
				</select>
				<button type="button" id="doaction" class="button action">Apply</button>
			</div>
		</div>

		<!-- Applications Table -->
		<form id="nmda-applications-form" method="post">
			<table class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<input id="cb-select-all-1" type="checkbox">
						</td>
						<th scope="col" class="manage-column column-title column-primary">Business Name</th>
						<th scope="col" class="manage-column">Applicant</th>
						<th scope="col" class="manage-column">Classification</th>
						<th scope="col" class="manage-column">Products</th>
						<th scope="col" class="manage-column">Submitted</th>
						<th scope="col" class="manage-column">Status</th>
						<th scope="col" class="manage-column">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $applications->have_posts() ) : ?>
						<?php while ( $applications->have_posts() ) : $applications->the_post(); ?>
							<?php
							$business_id      = get_the_ID();
							$approval_status  = get_field( 'approval_status' );
							$owner_name       = get_field( 'owner_first_name' ) . ' ' . get_field( 'owner_last_name' );
							$classification   = get_field( 'classification' );
							$product_terms    = wp_get_post_terms( $business_id, 'product_type' );
							?>
							<tr data-business-id="<?php echo $business_id; ?>">
								<th scope="row" class="check-column">
									<input type="checkbox" name="business[]" value="<?php echo $business_id; ?>">
								</th>
								<td class="column-title column-primary" data-colname="Business Name">
									<strong><?php the_title(); ?></strong>
									<button type="button" class="toggle-row">
										<span class="screen-reader-text">Show more details</span>
									</button>
								</td>
								<td data-colname="Applicant">
									<?php echo esc_html( $owner_name ); ?><br>
									<small><?php echo esc_html( get_field( 'contact_email' ) ); ?></small>
								</td>
								<td data-colname="Classification">
									<?php
									if ( is_array( $classification ) ) {
										echo implode( ', ', array_map( 'ucfirst', $classification ) );
									}
									?>
								</td>
								<td data-colname="Products">
									<span class="product-count"><?php echo count( $product_terms ); ?> products</span>
								</td>
								<td data-colname="Submitted">
									<?php echo human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ); ?> ago
								</td>
								<td data-colname="Status">
									<span class="status-badge status-<?php echo esc_attr( $approval_status ); ?>">
										<?php echo ucfirst( $approval_status ); ?>
									</span>
								</td>
								<td data-colname="Actions">
									<button type="button" class="button button-small nmda-view-application" data-business-id="<?php echo $business_id; ?>">
										View Details
									</button>
								</td>
							</tr>
						<?php endwhile; ?>
						<?php wp_reset_postdata(); ?>
					<?php else : ?>
						<tr>
							<td colspan="8" class="no-items">No applications found.</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</form>

		<!-- Application Detail Modal -->
		<div id="nmda-application-modal" class="nmda-modal" style="display: none;">
			<div class="nmda-modal-content">
				<span class="nmda-modal-close">&times;</span>
				<div id="nmda-application-detail">
					<!-- Content loaded via AJAX -->
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Get application counts by status
 */
function nmda_get_application_counts() {
	$counts = array(
		'all'      => 0,
		'pending'  => 0,
		'approved' => 0,
		'rejected' => 0,
	);

	// Get all applications
	$all_args = array(
		'post_type'      => 'nmda_business',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);
	$all_query    = new WP_Query( $all_args );
	$counts['all'] = $all_query->found_posts;

	// Count by status
	foreach ( array( 'pending', 'approved', 'rejected' ) as $status ) {
		$status_args = array(
			'post_type'      => 'nmda_business',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'approval_status',
					'value' => $status,
				),
			),
		);
		$status_query       = new WP_Query( $status_args );
		$counts[ $status ] = $status_query->found_posts;
	}

	return $counts;
}

/**
 * AJAX: Get application details
 */
function nmda_ajax_get_application_details() {
	// Verify nonce
	check_ajax_referer( 'nmda-admin-nonce', 'nonce' );

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
	}

	$business_id = intval( $_POST['business_id'] );

	if ( ! $business_id ) {
		wp_send_json_error( array( 'message' => 'Invalid business ID.' ) );
	}

	// Get business details
	$business = get_post( $business_id );

	if ( ! $business ) {
		wp_send_json_error( array( 'message' => 'Business not found.' ) );
	}

	// Get all ACF fields
	$fields = get_fields( $business_id );

	// Get product terms
	$products = wp_get_post_terms( $business_id, 'product_type' );

	// Build HTML response
	ob_start();
	?>
	<div class="nmda-application-detail">
		<h2><?php echo esc_html( $business->post_title ); ?></h2>

		<div class="application-status-bar">
			<span class="status-badge status-<?php echo esc_attr( $fields['approval_status'] ); ?>">
				Status: <?php echo ucfirst( $fields['approval_status'] ); ?>
			</span>
			<span class="submitted-date">
				Submitted: <?php echo get_the_date( 'F j, Y g:i A', $business_id ); ?>
			</span>
		</div>

		<div class="application-sections">
			<!-- Personal Contact Information -->
			<div class="application-section">
				<h3>Personal Contact Information (Private)</h3>
				<div class="section-content">
					<div class="field-row">
						<label>Name:</label>
						<span><?php echo esc_html( $fields['owner_first_name'] . ' ' . $fields['owner_last_name'] ); ?></span>
					</div>
					<div class="field-row">
						<label>Phone:</label>
						<span><?php echo esc_html( $fields['contact_phone'] ); ?></span>
					</div>
					<div class="field-row">
						<label>Email:</label>
						<span><a href="mailto:<?php echo esc_attr( $fields['contact_email'] ); ?>"><?php echo esc_html( $fields['contact_email'] ); ?></a></span>
					</div>
					<div class="field-row">
						<label>Mailing Address:</label>
						<span>
							<?php echo esc_html( $fields['contact_address'] ); ?><br>
							<?php if ( $fields['contact_address_2'] ) : ?>
								<?php echo esc_html( $fields['contact_address_2'] ); ?><br>
							<?php endif; ?>
							<?php echo esc_html( $fields['contact_city'] . ', ' . $fields['contact_state'] . ' ' . $fields['contact_zip'] ); ?>
						</span>
					</div>
				</div>
			</div>

			<!-- Business Information -->
			<div class="application-section">
				<h3>Business Information (Public)</h3>
				<div class="section-content">
					<div class="field-row">
						<label>Legal Name:</label>
						<span><?php echo esc_html( $business->post_title ); ?></span>
					</div>
					<?php if ( $fields['dba'] ) : ?>
						<div class="field-row">
							<label>DBA:</label>
							<span><?php echo esc_html( $fields['dba'] ); ?></span>
						</div>
					<?php endif; ?>
					<div class="field-row">
						<label>Phone:</label>
						<span><?php echo esc_html( $fields['business_phone'] ); ?></span>
					</div>
					<div class="field-row">
						<label>Email:</label>
						<span><?php echo esc_html( $fields['business_email'] ); ?></span>
					</div>
					<?php if ( $fields['website'] ) : ?>
						<div class="field-row">
							<label>Website:</label>
							<span><a href="<?php echo esc_url( $fields['website'] ); ?>" target="_blank"><?php echo esc_html( $fields['website'] ); ?></a></span>
						</div>
					<?php endif; ?>
					<div class="field-row">
						<label>Business Address:</label>
						<span>
							<?php echo esc_html( $fields['primary_address'] ); ?><br>
							<?php if ( $fields['primary_address_2'] ) : ?>
								<?php echo esc_html( $fields['primary_address_2'] ); ?><br>
							<?php endif; ?>
							<?php echo esc_html( $fields['primary_city'] . ', ' . $fields['primary_state'] . ' ' . $fields['primary_zip'] ); ?>
						</span>
					</div>
					<div class="field-row">
						<label>Address Type:</label>
						<span><?php echo esc_html( $fields['primary_address_type'] ); ?></span>
					</div>
					<?php if ( $fields['business_profile'] ) : ?>
						<div class="field-row">
							<label>Business Profile:</label>
							<span><?php echo nl2br( esc_html( $fields['business_profile'] ) ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $fields['business_hours'] ) : ?>
						<div class="field-row">
							<label>Business Hours:</label>
							<span><?php echo nl2br( esc_html( $fields['business_hours'] ) ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Classification -->
			<div class="application-section">
				<h3>Logo Program Classification</h3>
				<div class="section-content">
					<div class="field-row">
						<label>Classification:</label>
						<span>
							<?php
							if ( is_array( $fields['classification'] ) ) {
								echo implode( ', ', array_map( 'ucfirst', $fields['classification'] ) );
							}
							?>
						</span>
					</div>
					<?php if ( is_array( $fields['associate_type'] ) && ! empty( $fields['associate_type'] ) ) : ?>
						<div class="field-row">
							<label>Associate Type:</label>
							<span><?php echo implode( ', ', array_map( 'ucfirst', $fields['associate_type'] ) ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $fields['num_employees'] ) : ?>
						<div class="field-row">
							<label>Number of Employees:</label>
							<span><?php echo esc_html( $fields['num_employees'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Products -->
			<div class="application-section">
				<h3>Products (<?php echo count( $products ); ?>)</h3>
				<div class="section-content">
					<?php if ( ! empty( $products ) ) : ?>
						<ul class="product-list">
							<?php foreach ( $products as $product ) : ?>
								<li><?php echo esc_html( $product->name ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p>No products selected.</p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Admin Notes -->
			<div class="application-section">
				<h3>Admin Notes</h3>
				<div class="section-content">
					<textarea id="admin-notes-<?php echo $business_id; ?>" class="large-text" rows="4"><?php echo esc_textarea( $fields['admin_notes'] ); ?></textarea>
					<button type="button" class="button nmda-save-notes" data-business-id="<?php echo $business_id; ?>">
						Save Notes
					</button>
				</div>
			</div>
		</div>

		<!-- Action Buttons -->
		<div class="application-actions">
			<?php if ( $fields['approval_status'] === 'pending' ) : ?>
				<button type="button" class="button button-primary button-large nmda-approve-application" data-business-id="<?php echo $business_id; ?>">
					<span class="dashicons dashicons-yes"></span> Approve Application
				</button>
				<button type="button" class="button button-secondary button-large nmda-reject-application" data-business-id="<?php echo $business_id; ?>">
					<span class="dashicons dashicons-no"></span> Reject Application
				</button>
			<?php elseif ( $fields['approval_status'] === 'approved' ) : ?>
				<button type="button" class="button button-secondary button-large nmda-reject-application" data-business-id="<?php echo $business_id; ?>">
					<span class="dashicons dashicons-no"></span> Revoke Approval
				</button>
			<?php elseif ( $fields['approval_status'] === 'rejected' ) : ?>
				<button type="button" class="button button-primary button-large nmda-approve-application" data-business-id="<?php echo $business_id; ?>">
					<span class="dashicons dashicons-yes"></span> Approve Application
				</button>
			<?php endif; ?>
		</div>
	</div>
	<?php
	$html = ob_get_clean();

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_nmda_get_application_details', 'nmda_ajax_get_application_details' );

/**
 * AJAX: Approve application
 */
function nmda_ajax_approve_application() {
	// Verify nonce
	check_ajax_referer( 'nmda-admin-nonce', 'nonce' );

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
	}

	$business_id = intval( $_POST['business_id'] );

	if ( ! $business_id ) {
		wp_send_json_error( array( 'message' => 'Invalid business ID.' ) );
	}

	// Update approval status
	update_field( 'approval_status', 'approved', $business_id );
	update_field( 'approval_date', current_time( 'mysql' ), $business_id );
	update_field( 'approved_by', get_current_user_id(), $business_id );

	// Change post status to publish
	wp_update_post(
		array(
			'ID'          => $business_id,
			'post_status' => 'publish',
		)
	);

	// Send approval email
	nmda_send_approval_email( $business_id );

	wp_send_json_success( array( 'message' => 'Application approved successfully!' ) );
}
add_action( 'wp_ajax_nmda_approve_application', 'nmda_ajax_approve_application' );

/**
 * AJAX: Reject application
 */
function nmda_ajax_reject_application() {
	// Verify nonce
	check_ajax_referer( 'nmda-admin-nonce', 'nonce' );

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
	}

	$business_id = intval( $_POST['business_id'] );

	if ( ! $business_id ) {
		wp_send_json_error( array( 'message' => 'Invalid business ID.' ) );
	}

	// Update approval status
	update_field( 'approval_status', 'rejected', $business_id );
	update_field( 'approval_date', current_time( 'mysql' ), $business_id );
	update_field( 'approved_by', get_current_user_id(), $business_id );

	// Keep post status as pending
	wp_update_post(
		array(
			'ID'          => $business_id,
			'post_status' => 'pending',
		)
	);

	// Send rejection email
	nmda_send_rejection_email( $business_id );

	wp_send_json_success( array( 'message' => 'Application rejected.' ) );
}
add_action( 'wp_ajax_nmda_reject_application', 'nmda_ajax_reject_application' );

/**
 * AJAX: Save admin notes
 */
function nmda_ajax_save_admin_notes() {
	// Verify nonce
	check_ajax_referer( 'nmda-admin-nonce', 'nonce' );

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
	}

	$business_id = intval( $_POST['business_id'] );
	$notes       = sanitize_textarea_field( $_POST['notes'] );

	if ( ! $business_id ) {
		wp_send_json_error( array( 'message' => 'Invalid business ID.' ) );
	}

	// Update admin notes
	update_field( 'admin_notes', $notes, $business_id );

	wp_send_json_success( array( 'message' => 'Notes saved successfully!' ) );
}
add_action( 'wp_ajax_nmda_save_admin_notes', 'nmda_ajax_save_admin_notes' );

/**
 * Send approval email to applicant
 */
function nmda_send_approval_email( $business_id ) {
	$business   = get_post( $business_id );
	$user_id    = $business->post_author;
	$user       = get_userdata( $user_id );
	$contact_email = get_field( 'contact_email', $business_id );

	$subject = 'Business Application Approved - NMDA Logo Program';
	$message = sprintf(
		"Congratulations!\n\nYour business application for %s has been approved for the New Mexico Logo Program.\n\nYou can now access your member dashboard to:\n- View and update your business profile\n- Submit reimbursement requests\n- Access program resources\n\nLogin to your dashboard: %s\n\nThank you for being part of the New Mexico Logo Program!\n\nNew Mexico Department of Agriculture",
		$business->post_title,
		home_url( '/dashboard' )
	);

	wp_mail( $contact_email, $subject, $message );
}

/**
 * Send rejection email to applicant
 */
function nmda_send_rejection_email( $business_id ) {
	$business   = get_post( $business_id );
	$user_id    = $business->post_author;
	$user       = get_userdata( $user_id );
	$contact_email = get_field( 'contact_email', $business_id );
	$admin_notes   = get_field( 'admin_notes', $business_id );

	$subject = 'Business Application Update - NMDA Logo Program';
	$message = sprintf(
		"Dear %s,\n\nThank you for your interest in the New Mexico Logo Program.\n\nAfter reviewing your application for %s, we are unable to approve it at this time.\n\n%s\n\nIf you have questions or would like to discuss your application, please contact us.\n\nThank you,\nNew Mexico Department of Agriculture",
		get_field( 'owner_first_name', $business_id ),
		$business->post_title,
		$admin_notes ? "Feedback: " . $admin_notes : ''
	);

	wp_mail( $contact_email, $subject, $message );
}
