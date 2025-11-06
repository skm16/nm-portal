<?php
/**
 * NMDA Admin Reimbursements Interface
 * Handles reimbursement request review and approval workflow
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register admin menu page
 */
function nmda_register_reimbursements_admin_page() {
	add_menu_page(
		'Reimbursement Requests',
		'Reimbursements',
		'manage_options',
		'nmda-reimbursements',
		'nmda_render_reimbursements_page',
		'dashicons-money-alt',
		27
	);

	// Add submenu pages
	add_submenu_page(
		'nmda-reimbursements',
		'Pending Reimbursements',
		'Pending',
		'manage_options',
		'nmda-reimbursements&status=submitted',
		'nmda_render_reimbursements_page'
	);

	add_submenu_page(
		'nmda-reimbursements',
		'Approved Reimbursements',
		'Approved',
		'manage_options',
		'nmda-reimbursements&status=approved',
		'nmda_render_reimbursements_page'
	);

	add_submenu_page(
		'nmda-reimbursements',
		'Rejected Reimbursements',
		'Rejected',
		'manage_options',
		'nmda-reimbursements&status=rejected',
		'nmda_render_reimbursements_page'
	);
}
add_action( 'admin_menu', 'nmda_register_reimbursements_admin_page' );

/**
 * Enqueue admin styles and scripts
 */
function nmda_enqueue_admin_reimbursements_assets( $hook ) {
	if ( strpos( $hook, 'nmda-reimbursements' ) === false ) {
		return;
	}

	// Enqueue admin CSS
	wp_enqueue_style(
		'nmda-admin-reimbursements',
		NMDA_THEME_URI . '/assets/css/admin-reimbursements.css',
		array(),
		NMDA_THEME_VERSION
	);

	// Enqueue admin JS
	wp_enqueue_script(
		'nmda-admin-reimbursements',
		NMDA_THEME_URI . '/assets/js/admin-reimbursements.js',
		array( 'jquery' ),
		NMDA_THEME_VERSION,
		true
	);

	// Localize script
	wp_localize_script(
		'nmda-admin-reimbursements',
		'nmdaReimbursements',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nmda-reimbursements-nonce' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'nmda_enqueue_admin_reimbursements_assets' );

/**
 * Render reimbursements management page
 */
function nmda_render_reimbursements_page() {
	global $wpdb;

	// Get current status filter
	$status       = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
	$type_filter  = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'all';
	$fiscal_year  = isset( $_GET['fiscal_year'] ) ? sanitize_text_field( $_GET['fiscal_year'] ) : 'all';
	$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
	$per_page     = 20;
	$offset       = ( $current_page - 1 ) * $per_page;

	// Build query
	$table = $wpdb->prefix . 'nmda_reimbursements';
	$where = array( '1=1' );

	if ( $status !== 'all' ) {
		$where[] = $wpdb->prepare( 'status = %s', $status );
	}

	if ( $type_filter !== 'all' ) {
		$where[] = $wpdb->prepare( 'type = %s', $type_filter );
	}

	if ( $fiscal_year !== 'all' ) {
		$where[] = $wpdb->prepare( 'fiscal_year = %s', $fiscal_year );
	}

	$where_clause = implode( ' AND ', $where );

	// Get total count
	$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where_clause" );
	$total_pages = ceil( $total_count / $per_page );

	// Get reimbursements
	$reimbursements = $wpdb->get_results(
		"SELECT * FROM $table
		WHERE $where_clause
		ORDER BY created_at DESC
		LIMIT $per_page OFFSET $offset",
		ARRAY_A
	);

	// Get statistics
	$stats = array(
		'all'       => $wpdb->get_var( "SELECT COUNT(*) FROM $table" ),
		'submitted' => $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'submitted'" ),
		'approved'  => $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'approved'" ),
		'rejected'  => $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'rejected'" ),
	);

	// Get available fiscal years
	$fiscal_years = $wpdb->get_col( "SELECT DISTINCT fiscal_year FROM $table ORDER BY fiscal_year DESC" );
	?>

	<div class="wrap nmda-reimbursements-page">
		<h1>
			<span class="dashicons dashicons-money-alt"></span>
			Reimbursement Requests
		</h1>

		<!-- Status Filters -->
		<ul class="subsubsub">
			<li class="all">
				<a href="?page=nmda-reimbursements" class="<?php echo ( $status === 'all' ) ? 'current' : ''; ?>">
					All <span class="count">(<?php echo $stats['all']; ?>)</span>
				</a> |
			</li>
			<li class="submitted">
				<a href="?page=nmda-reimbursements&status=submitted" class="<?php echo ( $status === 'submitted' ) ? 'current' : ''; ?>">
					Pending <span class="count">(<?php echo $stats['submitted']; ?>)</span>
				</a> |
			</li>
			<li class="approved">
				<a href="?page=nmda-reimbursements&status=approved" class="<?php echo ( $status === 'approved' ) ? 'current' : ''; ?>">
					Approved <span class="count">(<?php echo $stats['approved']; ?>)</span>
				</a> |
			</li>
			<li class="rejected">
				<a href="?page=nmda-reimbursements&status=rejected" class="<?php echo ( $status === 'rejected' ) ? 'current' : ''; ?>">
					Rejected <span class="count">(<?php echo $stats['rejected']; ?>)</span>
				</a>
			</li>
		</ul>

		<!-- Additional Filters -->
		<div class="tablenav top">
			<div class="alignleft actions">
				<select name="type_filter" id="type-filter">
					<option value="all" <?php selected( $type_filter, 'all' ); ?>>All Types</option>
					<option value="lead" <?php selected( $type_filter, 'lead' ); ?>>Lead Generation</option>
					<option value="advertising" <?php selected( $type_filter, 'advertising' ); ?>>Advertising</option>
					<option value="labels" <?php selected( $type_filter, 'labels' ); ?>>Labels</option>
				</select>

				<select name="fiscal_year" id="fiscal-year-filter">
					<option value="all" <?php selected( $fiscal_year, 'all' ); ?>>All Fiscal Years</option>
					<?php foreach ( $fiscal_years as $year ) : ?>
						<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $fiscal_year, $year ); ?>>
							<?php echo esc_html( $year ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<input type="button" id="filter-button" class="button" value="Filter">
			</div>

			<div class="tablenav-pages">
				<?php
				echo paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total'     => $total_pages,
						'current'   => $current_page,
					)
				);
				?>
			</div>
		</div>

		<!-- Reimbursements Table -->
		<table class="wp-list-table widefat fixed striped reimbursements-table">
			<thead>
				<tr>
					<th>ID</th>
					<th>Business</th>
					<th>Type</th>
					<th>Fiscal Year</th>
					<th>Amount Requested</th>
					<th>Amount Approved</th>
					<th>Status</th>
					<th>Submitted</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $reimbursements ) ) : ?>
					<tr>
						<td colspan="9" class="no-items">No reimbursement requests found.</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $reimbursements as $reimbursement ) : ?>
						<?php
						$business      = get_post( $reimbursement['business_id'] );
						$business_name = $business ? $business->post_title : 'Unknown';
						$status_class  = 'status-' . $reimbursement['status'];
						$type_labels   = array(
							'lead'        => 'Lead Generation',
							'advertising' => 'Advertising',
							'labels'      => 'Product Labels',
						);
						$type_label = isset( $type_labels[ $reimbursement['type'] ] ) ? $type_labels[ $reimbursement['type'] ] : $reimbursement['type'];
						?>
						<tr>
							<td><strong>#<?php echo $reimbursement['id']; ?></strong></td>
							<td>
								<a href="<?php echo get_edit_post_link( $reimbursement['business_id'] ); ?>" target="_blank">
									<?php echo esc_html( $business_name ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $type_label ); ?></td>
							<td><?php echo esc_html( $reimbursement['fiscal_year'] ); ?></td>
							<td>$<?php echo number_format( $reimbursement['amount_requested'], 2 ); ?></td>
							<td>
								<?php if ( $reimbursement['amount_approved'] ) : ?>
									$<?php echo number_format( $reimbursement['amount_approved'], 2 ); ?>
								<?php else : ?>
									<span class="na">—</span>
								<?php endif; ?>
							</td>
							<td>
								<span class="status-badge <?php echo $status_class; ?>">
									<?php echo ucfirst( $reimbursement['status'] ); ?>
								</span>
							</td>
							<td><?php echo date( 'M j, Y g:i A', strtotime( $reimbursement['created_at'] ) ); ?></td>
							<td>
								<button type="button" class="button button-small nmda-view-reimbursement" data-reimbursement-id="<?php echo $reimbursement['id']; ?>">
									View Details
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				echo paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total'     => $total_pages,
						'current'   => $current_page,
					)
				);
				?>
			</div>
		</div>
	</div>

	<!-- Reimbursement Detail Modal -->
	<div id="nmda-reimbursement-modal" class="nmda-modal">
		<div class="nmda-modal-content">
			<span class="nmda-modal-close">&times;</span>
			<div id="nmda-reimbursement-detail"></div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#filter-button').on('click', function() {
			var type = $('#type-filter').val();
			var fiscal = $('#fiscal-year-filter').val();
			var url = '?page=nmda-reimbursements';

			<?php if ( $status !== 'all' ) : ?>
			url += '&status=<?php echo $status; ?>';
			<?php endif; ?>

			if (type !== 'all') url += '&type=' + type;
			if (fiscal !== 'all') url += '&fiscal_year=' + fiscal;

			window.location.href = url;
		});
	});
	</script>

	<?php
}

/**
 * AJAX: Get reimbursement details
 */
function nmda_ajax_get_reimbursement_details() {
	check_ajax_referer( 'nmda-reimbursements-nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
	}

	$reimbursement_id = isset( $_POST['reimbursement_id'] ) ? intval( $_POST['reimbursement_id'] ) : 0;

	if ( ! $reimbursement_id ) {
		wp_send_json_error( array( 'message' => 'Invalid reimbursement ID.' ) );
	}

	$reimbursement = nmda_get_reimbursement( $reimbursement_id );

	if ( ! $reimbursement ) {
		wp_send_json_error( array( 'message' => 'Reimbursement not found.' ) );
	}

	// Get business and user info
	$business      = get_post( $reimbursement->business_id );
	$user          = get_userdata( $reimbursement->user_id );
	$business_name = $business ? $business->post_title : 'Unknown';
	$user_name     = $user ? $user->display_name : 'Unknown';
	$user_email    = $user ? $user->user_email : '';

	// Decode JSON data (already decoded by nmda_get_reimbursement)
	$form_data = $reimbursement->data;
	$documents = $reimbursement->documents;

	// Type labels
	$type_labels = array(
		'lead'        => 'Lead Generation',
		'advertising' => 'Advertising',
		'labels'      => 'Product Labels',
	);
	$type_label = isset( $type_labels[ $reimbursement->type ] ) ? $type_labels[ $reimbursement->type ] : $reimbursement->type;

	ob_start();
	?>

	<div class="reimbursement-detail-content">
		<h2>Reimbursement Request #<?php echo $reimbursement_id; ?></h2>

		<div class="detail-section">
			<h3>Request Information</h3>
			<table class="form-table">
				<tr>
					<th>Type:</th>
					<td><strong><?php echo esc_html( $type_label ); ?></strong></td>
				</tr>
				<tr>
					<th>Business:</th>
					<td><?php echo esc_html( $business_name ); ?></td>
				</tr>
				<tr>
					<th>Submitted By:</th>
					<td><?php echo esc_html( $user_name ); ?> (<?php echo esc_html( $user_email ); ?>)</td>
				</tr>
				<tr>
					<th>Fiscal Year:</th>
					<td><?php echo esc_html( $reimbursement->fiscal_year ); ?></td>
				</tr>
				<tr>
					<th>Amount Requested:</th>
					<td><strong>$<?php echo number_format( $reimbursement->amount_requested, 2 ); ?></strong></td>
				</tr>
				<tr>
					<th>Status:</th>
					<td>
						<span class="status-badge status-<?php echo $reimbursement->status; ?>">
							<?php echo ucfirst( $reimbursement->status ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th>Submitted:</th>
					<td><?php echo date( 'F j, Y g:i A', strtotime( $reimbursement->created_at ) ); ?></td>
				</tr>
			</table>
		</div>

		<div class="detail-section">
			<h3>Request Details</h3>
			<table class="form-table">
				<?php foreach ( $form_data as $key => $value ) : ?>
					<?php if ( ! empty( $value ) ) : ?>
						<tr>
							<th><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?>:</th>
							<td><?php echo esc_html( is_array( $value ) ? implode( ', ', $value ) : $value ); ?></td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</table>
		</div>

		<?php if ( ! empty( $documents ) ) : ?>
			<div class="detail-section">
				<h3>Supporting Documents</h3>
				<ul class="document-list">
					<?php foreach ( $documents as $doc_id ) : ?>
						<?php $file_url = wp_get_attachment_url( $doc_id ); ?>
						<?php if ( $file_url ) : ?>
							<li>
								<a href="<?php echo esc_url( $file_url ); ?>" target="_blank">
									<span class="dashicons dashicons-media-document"></span>
									<?php echo esc_html( basename( $file_url ) ); ?>
								</a>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( $reimbursement['status'] === 'submitted' ) : ?>
			<div class="detail-section">
				<h3>Review Actions</h3>
				<div class="review-actions">
					<div class="approve-section">
						<label for="approved-amount">Approved Amount:</label>
						<input type="number" id="approved-amount" step="0.01" min="0" max="<?php echo $reimbursement['amount_requested']; ?>" value="<?php echo $reimbursement['amount_requested']; ?>" class="regular-text">
						<button type="button" class="button button-primary nmda-approve-reimbursement" data-reimbursement-id="<?php echo $reimbursement_id; ?>">
							<span class="dashicons dashicons-yes"></span> Approve Request
						</button>
					</div>

					<div class="reject-section">
						<label for="rejection-reason">Rejection Reason:</label>
						<textarea id="rejection-reason" rows="3" class="large-text"></textarea>
						<button type="button" class="button button-secondary nmda-reject-reimbursement" data-reimbursement-id="<?php echo $reimbursement_id; ?>">
							<span class="dashicons dashicons-no"></span> Reject Request
						</button>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $reimbursement['status'] === 'approved' ) : ?>
			<div class="detail-section">
				<h3>Approval Information</h3>
				<table class="form-table">
					<tr>
						<th>Approved Amount:</th>
						<td><strong>$<?php echo number_format( $reimbursement['amount_approved'], 2 ); ?></strong></td>
					</tr>
					<tr>
						<th>Reviewed At:</th>
						<td><?php echo date( 'F j, Y g:i A', strtotime( $reimbursement['reviewed_at'] ) ); ?></td>
					</tr>
				</table>
			</div>
		<?php endif; ?>

		<?php if ( $reimbursement['status'] === 'rejected' && ! empty( $reimbursement['admin_notes'] ) ) : ?>
			<div class="detail-section">
				<h3>Rejection Reason</h3>
				<p><?php echo nl2br( esc_html( $reimbursement['admin_notes'] ) ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<?php
	$html = ob_get_clean();

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_nmda_get_reimbursement_details', 'nmda_ajax_get_reimbursement_details' );

/**
 * AJAX: Approve reimbursement
 */
function nmda_ajax_approve_reimbursement() {
	check_ajax_referer( 'nmda-reimbursements-nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
	}

	$reimbursement_id = isset( $_POST['reimbursement_id'] ) ? intval( $_POST['reimbursement_id'] ) : 0;
	$approved_amount  = isset( $_POST['approved_amount'] ) ? floatval( $_POST['approved_amount'] ) : 0;

	if ( ! $reimbursement_id || ! $approved_amount ) {
		wp_send_json_error( array( 'message' => 'Invalid parameters.' ) );
	}

	$result = nmda_approve_reimbursement( $reimbursement_id, $approved_amount );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array( 'message' => 'Reimbursement request approved successfully!' ) );
}
add_action( 'wp_ajax_nmda_approve_reimbursement', 'nmda_ajax_approve_reimbursement' );

/**
 * AJAX: Reject reimbursement
 */
function nmda_ajax_reject_reimbursement() {
	check_ajax_referer( 'nmda-reimbursements-nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
	}

	$reimbursement_id = isset( $_POST['reimbursement_id'] ) ? intval( $_POST['reimbursement_id'] ) : 0;
	$reason           = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';

	if ( ! $reimbursement_id ) {
		wp_send_json_error( array( 'message' => 'Invalid reimbursement ID.' ) );
	}

	$result = nmda_reject_reimbursement( $reimbursement_id, $reason );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array( 'message' => 'Reimbursement request rejected.' ) );
}
add_action( 'wp_ajax_nmda_reject_reimbursement', 'nmda_ajax_reject_reimbursement' );
