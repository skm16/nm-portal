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

/**
 * Render Lead Generation reimbursement form
 *
 * @param array $approved_businesses Array of approved businesses.
 * @return string Form HTML.
 */
function nmda_render_lead_reimbursement_form( $approved_businesses ) {
	ob_start();
	?>

	<form id="nmda-reimbursement-form-lead" class="nmda-reimbursement-form nmda-ajax-form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'nmda_reimbursement_lead', 'nmda_reimbursement_nonce' ); ?>
		<input type="hidden" name="action" value="nmda_submit_reimbursement_lead" />
		<input type="hidden" name="reimbursement_type" value="lead" />

		<div class="card mb-4">
			<div class="card-body">
				<h3>Business Selection</h3>
				<div class="form-group">
					<label for="business_id">Select Business <span class="text-danger">*</span></label>
					<select name="business_id" id="business_id" class="form-control" required>
						<option value="">-- Select Business --</option>
						<?php foreach ( $approved_businesses as $business ) : ?>
							<?php $business_post = get_post( $business['business_id'] ); ?>
							<option value="<?php echo esc_attr( $business['business_id'] ); ?>">
								<?php echo esc_html( $business_post->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="fiscal_year">Fiscal Year <span class="text-danger">*</span></label>
					<select name="fiscal_year" id="fiscal_year" class="form-control" required>
						<option value="<?php echo date( 'Y' ); ?>"><?php echo date( 'Y' ); ?></option>
						<option value="<?php echo date( 'Y' ) + 1; ?>"><?php echo date( 'Y' ) + 1; ?></option>
					</select>
				</div>
			</div>
		</div>

		<div class="card mb-4">
			<div class="card-body">
				<h3>Event/Activity Information</h3>

				<div class="form-group">
					<label for="event_name">Event/Activity Name <span class="text-danger">*</span></label>
					<input type="text" name="event_name" id="event_name" class="form-control" required>
				</div>

				<div class="form-group">
					<label for="event_type">Event Type <span class="text-danger">*</span></label>
					<select name="event_type" id="event_type" class="form-control" required>
						<option value="">-- Select Type --</option>
						<option value="trade_show">Trade Show</option>
						<option value="farmers_market">Farmers Market</option>
						<option value="festival">Festival/Fair</option>
						<option value="promotional_event">Promotional Event</option>
						<option value="other">Other</option>
					</select>
				</div>

				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="event_date">Event Date <span class="text-danger">*</span></label>
							<input type="date" name="event_date" id="event_date" class="form-control" required>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="event_location">Event Location <span class="text-danger">*</span></label>
							<input type="text" name="event_location" id="event_location" class="form-control" required placeholder="City, State">
						</div>
					</div>
				</div>

				<div class="form-group">
					<label for="event_description">Event Description <span class="text-danger">*</span></label>
					<textarea name="event_description" id="event_description" class="form-control" rows="4" required placeholder="Describe the event and your participation"></textarea>
				</div>
			</div>
		</div>

		<div class="card mb-4">
			<div class="card-body">
				<h3>Cost Information</h3>

				<div class="form-group">
					<label for="booth_fee">Booth/Space Fee</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="booth_fee" id="booth_fee" class="form-control" step="0.01" min="0">
					</div>
				</div>

				<div class="form-group">
					<label for="promotional_materials">Promotional Materials Cost</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="promotional_materials" id="promotional_materials" class="form-control" step="0.01" min="0">
					</div>
				</div>

				<div class="form-group">
					<label for="travel_costs">Travel/Lodging Costs</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="travel_costs" id="travel_costs" class="form-control" step="0.01" min="0">
					</div>
				</div>

				<div class="form-group">
					<label for="other_costs">Other Costs</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="other_costs" id="other_costs" class="form-control" step="0.01" min="0">
					</div>
					<small class="form-text text-muted">Please describe in notes below</small>
				</div>

				<div class="form-group">
					<label for="amount_requested">Total Amount Requested <span class="text-danger">*</span></label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="amount_requested" id="amount_requested" class="form-control" step="0.01" value="0.00" readonly>
					</div>
					<small class="form-text text-muted">Auto-calculated from costs above (must be greater than $0)</small>
				</div>

				<div class="form-group">
					<label for="additional_notes">Additional Notes</label>
					<textarea name="additional_notes" id="additional_notes" class="form-control" rows="3"></textarea>
				</div>
			</div>
		</div>

		<div class="card mb-4">
			<div class="card-body">
				<h3>Supporting Documents</h3>
				<p>Please upload receipts, invoices, event flyers, or other proof of participation.</p>

				<div class="form-group">
					<label for="documents">Upload Documents</label>
					<input type="file" name="documents[]" id="documents" class="form-control-file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
					<small class="form-text text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB per file)</small>
				</div>
			</div>
		</div>

		<div class="form-actions">
			<button type="submit" class="btn btn-primary btn-lg">
				<i class="fa fa-check"></i> Submit Reimbursement Request
			</button>
			<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-secondary btn-lg">
				<i class="fa fa-times"></i> Cancel
			</a>
		</div>

		<div id="form-messages" class="mt-3"></div>
	</form>

	<script>
	jQuery(document).ready(function($) {
		// Auto-calculate total amount
		function calculateTotal() {
			var booth = parseFloat($('#booth_fee').val()) || 0;
			var promo = parseFloat($('#promotional_materials').val()) || 0;
			var travel = parseFloat($('#travel_costs').val()) || 0;
			var other = parseFloat($('#other_costs').val()) || 0;
			var total = booth + promo + travel + other;
			$('#amount_requested').val(total.toFixed(2));
		}

		$('#booth_fee, #promotional_materials, #travel_costs, #other_costs').on('input', calculateTotal);
	});
	</script>

	<?php
	return ob_get_clean();
}

/**
 * Render advertising reimbursement form
 *
 * @param array $approved_businesses Array of approved businesses.
 * @return string Form HTML.
 */
function nmda_render_advertising_reimbursement_form( $approved_businesses ) {
	ob_start();
	?>

	<form id="nmda-reimbursement-form-advertising" class="nmda-reimbursement-form nmda-ajax-form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'nmda_reimbursement_advertising', 'nmda_reimbursement_nonce' ); ?>
		<input type="hidden" name="action" value="nmda_submit_reimbursement_advertising" />
		<input type="hidden" name="reimbursement_type" value="advertising" />

		<!-- Business Selection -->
		<div class="card mb-4">
			<div class="card-body">
				<h3><i class="fa fa-building"></i> Business Selection</h3>
				<div class="form-group">
					<label for="business_id">Select Business <span class="text-danger">*</span></label>
					<select name="business_id" id="business_id" class="form-control" required>
						<option value="">-- Select Business --</option>
						<?php foreach ( $approved_businesses as $business ) : ?>
							<?php $business_post = get_post( $business['business_id'] ); ?>
							<option value="<?php echo esc_attr( $business['business_id'] ); ?>">
								<?php echo esc_html( $business_post->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="fiscal_year">Fiscal Year <span class="text-danger">*</span></label>
					<select name="fiscal_year" id="fiscal_year" class="form-control" required>
						<option value="">-- Select Fiscal Year --</option>
						<?php
						$current_year = (int) date( 'Y' );
						for ( $year = $current_year; $year >= $current_year - 2; $year-- ) {
							echo '<option value="' . esc_attr( $year ) . '">' . esc_html( $year ) . '</option>';
						}
						?>
					</select>
					<small class="form-text text-muted">Maximum $10,000 per fiscal year for advertising reimbursements</small>
				</div>
			</div>
		</div>

		<!-- Advertising Campaign Information -->
		<div class="card mb-4">
			<div class="card-body">
				<h3><i class="fa fa-bullhorn"></i> Advertising Campaign Information</h3>

				<div class="form-group">
					<label for="campaign_name">Campaign Name <span class="text-danger">*</span></label>
					<input type="text" name="campaign_name" id="campaign_name" class="form-control" required>
				</div>

				<div class="form-group">
					<label for="ad_type">Advertising Type <span class="text-danger">*</span></label>
					<select name="ad_type" id="ad_type" class="form-control" required>
						<option value="">-- Select Type --</option>
						<option value="print">Print (Newspaper, Magazine)</option>
						<option value="digital">Digital (Website, Social Media)</option>
						<option value="billboard">Billboard / Outdoor</option>
						<option value="radio">Radio</option>
						<option value="tv">Television</option>
						<option value="other">Other</option>
					</select>
				</div>

				<div class="form-group">
					<label for="publication_platform">Publication/Platform Name <span class="text-danger">*</span></label>
					<input type="text" name="publication_platform" id="publication_platform" class="form-control" required placeholder="e.g., Albuquerque Journal, Facebook Ads, etc.">
				</div>

				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="start_date">Campaign Start Date <span class="text-danger">*</span></label>
							<input type="date" name="start_date" id="start_date" class="form-control" required>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="end_date">Campaign End Date</label>
							<input type="date" name="end_date" id="end_date" class="form-control">
						</div>
					</div>
				</div>

				<div class="form-group">
					<label for="circulation_reach">Estimated Circulation/Reach</label>
					<input type="text" name="circulation_reach" id="circulation_reach" class="form-control" placeholder="e.g., 50,000 readers, 10,000 impressions">
				</div>

				<div class="form-group">
					<label for="campaign_description">Campaign Description</label>
					<textarea name="campaign_description" id="campaign_description" class="form-control" rows="4" placeholder="Describe the advertising campaign and its objectives"></textarea>
				</div>
			</div>
		</div>

		<!-- Cost Information -->
		<div class="card mb-4">
			<div class="card-body">
				<h3><i class="fa fa-dollar"></i> Cost Information</h3>

				<div class="form-group">
					<label for="ad_cost">Advertising Space/Time Cost</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="ad_cost" id="ad_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
					</div>
					<small class="form-text text-muted">Cost of ad placement/airtime</small>
				</div>

				<div class="form-group">
					<label for="design_cost">Design/Production Cost</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="design_cost" id="design_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
					</div>
					<small class="form-text text-muted">Cost of creative design and production</small>
				</div>

				<div class="form-group">
					<label for="other_costs">Other Costs</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="other_costs" id="other_costs" class="form-control" step="0.01" min="0" placeholder="0.00">
					</div>
					<small class="form-text text-muted">Any other related costs (specify in description)</small>
				</div>

				<hr>

				<div class="form-group">
					<label for="amount_requested">Total Amount Requested <span class="text-danger">*</span></label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="amount_requested" id="amount_requested" class="form-control" step="0.01" value="0.00" readonly>
					</div>
					<small class="form-text text-muted">Auto-calculated from costs above (must be greater than $0)</small>
				</div>
			</div>
		</div>

		<!-- Supporting Documents -->
		<div class="card mb-4">
			<div class="card-body">
				<h3><i class="fa fa-paperclip"></i> Supporting Documents</h3>
				<p>Please upload invoices, receipts, ad samples, and any other supporting documentation.</p>
				<div class="form-group">
					<input type="file" name="documents[]" id="documents" class="form-control-file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
					<small class="form-text text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB per file)</small>
				</div>
			</div>
		</div>

		<!-- Form Actions -->
		<div class="form-actions">
			<button type="submit" class="btn btn-primary btn-lg">
				<i class="fa fa-check"></i> Submit Reimbursement Request
			</button>
			<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-secondary btn-lg">
				<i class="fa fa-times"></i> Cancel
			</a>
		</div>

		<div id="form-messages" class="mt-3"></div>
	</form>

	<script>
	jQuery(document).ready(function($) {
		// Auto-calculate total amount
		function calculateTotal() {
			var adCost = parseFloat($('#ad_cost').val()) || 0;
			var designCost = parseFloat($('#design_cost').val()) || 0;
			var otherCosts = parseFloat($('#other_costs').val()) || 0;
			var total = adCost + designCost + otherCosts;
			$('#amount_requested').val(total.toFixed(2));
		}

		$('#ad_cost, #design_cost, #other_costs').on('input', calculateTotal);
	});
	</script>

	<?php
	return ob_get_clean();
}

/**
 * Render labels reimbursement form
 *
 * @param array $approved_businesses Array of approved businesses.
 * @return string Form HTML.
 */
function nmda_render_labels_reimbursement_form( $approved_businesses ) {
	ob_start();
	?>

	<form id="nmda-reimbursement-form-labels" class="nmda-reimbursement-form nmda-ajax-form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'nmda_reimbursement_labels', 'nmda_reimbursement_nonce' ); ?>
		<input type="hidden" name="action" value="nmda_submit_reimbursement_labels" />
		<input type="hidden" name="reimbursement_type" value="labels" />

		<!-- Business Selection -->
		<div class="card mb-4">
			<div class="card-body">
				<h3><i class="fa fa-building"></i> Business Selection</h3>
				<div class="form-group">
					<label for="business_id">Select Business <span class="text-danger">*</span></label>
					<select name="business_id" id="business_id" class="form-control" required>
						<option value="">-- Select Business --</option>
						<?php foreach ( $approved_businesses as $business ) : ?>
							<?php $business_post = get_post( $business['business_id'] ); ?>
							<option value="<?php echo esc_attr( $business['business_id'] ); ?>">
								<?php echo esc_html( $business_post->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="fiscal_year">Fiscal Year <span class="text-danger">*</span></label>
					<select name="fiscal_year" id="fiscal_year" class="form-control" required>
						<option value="">-- Select Fiscal Year --</option>
						<?php
						$current_year = (int) date( 'Y' );
						for ( $year = $current_year; $year >= $current_year - 2; $year-- ) {
							echo '<option value="' . esc_attr( $year ) . '">' . esc_html( $year ) . '</option>';
						}
						?>
					</select>
					<small class="form-text text-muted">Maximum $3,000 per fiscal year for label reimbursements</small>
				</div>
			</div>
		</div>

		<!-- Product Label Information -->
		<div class="card mb-4">
			<div class="card-body">
				<h3><i class="fa fa-tag"></i> Product Label Information</h3>

				<div class="form-group">
					<label for="product_name">Product Name <span class="text-danger">*</span></label>
					<input type="text" name="product_name" id="product_name" class="form-control" required>
				</div>

				<div class="form-group">
					<label for="label_type">Label Type <span class="text-danger">*</span></label>
					<select name="label_type" id="label_type" class="form-control" required>
						<option value="">-- Select Type --</option>
						<option value="new">New Label Design</option>
						<option value="redesign">Label Redesign</option>
						<option value="reprint">Label Reprint</option>
					</select>
				</div>

				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="quantity">Quantity Printed <span class="text-danger">*</span></label>
							<input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="label_size">Label Size</label>
							<input type="text" name="label_size" id="label_size" class="form-control" placeholder="e.g., 3x5 inches">
						</div>
					</div>
				</div>

				<div class="form-group">
					<label for="vendor_name">Vendor/Printer Name <span class="text-danger">*</span></label>
					<input type="text" name="vendor_name" id="vendor_name" class="form-control" required>
				</div>

				<div class="form-group">
					<label for="label_description">Label Description</label>
					<textarea name="label_description" id="label_description" class="form-control" rows="3" placeholder="Describe the label design and purpose"></textarea>
				</div>
			</div>
		</div>

		<!-- Cost Information -->
		<div class="card mb-4">
			<div class="card-body">
				<h3><i class="fa fa-dollar"></i> Cost Information</h3>

				<div class="form-group">
					<label for="design_cost">Design Cost</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="design_cost" id="design_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
					</div>
					<small class="form-text text-muted">Cost of label design services</small>
				</div>

				<div class="form-group">
					<label for="printing_cost">Printing Cost</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="printing_cost" id="printing_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
					</div>
					<small class="form-text text-muted">Cost of label printing</small>
				</div>

				<div class="form-group">
					<label for="setup_cost">Setup/Plate Cost</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="setup_cost" id="setup_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
					</div>
					<small class="form-text text-muted">One-time setup or plate fees</small>
				</div>

				<div class="form-group">
					<label for="shipping_cost">Shipping Cost</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="shipping_cost" id="shipping_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
					</div>
					<small class="form-text text-muted">Shipping and handling fees</small>
				</div>

				<div class="form-group">
					<label for="other_costs">Other Costs</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="other_costs" id="other_costs" class="form-control" step="0.01" min="0" placeholder="0.00">
					</div>
					<small class="form-text text-muted">Any other related costs (specify in description)</small>
				</div>

				<hr>

				<div class="form-group">
					<label for="amount_requested">Total Amount Requested <span class="text-danger">*</span></label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">$</span>
						</div>
						<input type="number" name="amount_requested" id="amount_requested" class="form-control" step="0.01" value="0.00" readonly>
					</div>
					<small class="form-text text-muted">Auto-calculated from costs above (must be greater than $0)</small>
				</div>
			</div>
		</div>

		<!-- Supporting Documents -->
		<div class="card mb-4">
			<div class="card-body">
				<h3><i class="fa fa-paperclip"></i> Supporting Documents</h3>
				<p>Please upload invoices, receipts, label samples, and any other supporting documentation.</p>
				<div class="form-group">
					<input type="file" name="documents[]" id="documents" class="form-control-file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
					<small class="form-text text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB per file)</small>
				</div>
			</div>
		</div>

		<!-- Form Actions -->
		<div class="form-actions">
			<button type="submit" class="btn btn-primary btn-lg">
				<i class="fa fa-check"></i> Submit Reimbursement Request
			</button>
			<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-secondary btn-lg">
				<i class="fa fa-times"></i> Cancel
			</a>
		</div>

		<div id="form-messages" class="mt-3"></div>
	</form>

	<script>
	jQuery(document).ready(function($) {
		// Auto-calculate total amount
		function calculateTotal() {
			var designCost = parseFloat($('#design_cost').val()) || 0;
			var printingCost = parseFloat($('#printing_cost').val()) || 0;
			var setupCost = parseFloat($('#setup_cost').val()) || 0;
			var shippingCost = parseFloat($('#shipping_cost').val()) || 0;
			var otherCosts = parseFloat($('#other_costs').val()) || 0;
			var total = designCost + printingCost + setupCost + shippingCost + otherCosts;
			$('#amount_requested').val(total.toFixed(2));
		}

		$('#design_cost, #printing_cost, #setup_cost, #shipping_cost, #other_costs').on('input', calculateTotal);
	});
	</script>

	<?php
	return ob_get_clean();
}

/**
 * Handle lead reimbursement AJAX submission
 */
function nmda_handle_lead_reimbursement_submission() {
	// Verify nonce
	if ( ! isset( $_POST['nmda_reimbursement_nonce'] ) || ! wp_verify_nonce( $_POST['nmda_reimbursement_nonce'], 'nmda_reimbursement_lead' ) ) {
		wp_send_json_error( array( 'message' => 'Security verification failed. Please refresh and try again.' ) );
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in to submit a reimbursement request.' ) );
	}

	$user_id     = get_current_user_id();
	$business_id = isset( $_POST['business_id'] ) ? intval( $_POST['business_id'] ) : 0;

	// Verify user has access to this business
	if ( ! nmda_user_can_access_business( $user_id, $business_id ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to submit reimbursements for this business.' ) );
	}

	// Verify business is approved
	$business_post = get_post( $business_id );
	if ( ! $business_post || $business_post->post_status !== 'publish' ) {
		wp_send_json_error( array( 'message' => 'Only approved businesses can submit reimbursement requests.' ) );
	}

	// Handle file uploads
	$document_ids = array();
	if ( ! empty( $_FILES['documents']['name'][0] ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$files = $_FILES['documents'];
		foreach ( $files['name'] as $key => $value ) {
			if ( $files['name'][ $key ] ) {
				$file = array(
					'name'     => $files['name'][ $key ],
					'type'     => $files['type'][ $key ],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error'    => $files['error'][ $key ],
					'size'     => $files['size'][ $key ],
				);

				// Check file size (5MB max)
				if ( $file['size'] > 5242880 ) {
					wp_send_json_error( array( 'message' => 'File ' . $file['name'] . ' exceeds 5MB limit.' ) );
				}

				$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

				if ( isset( $upload['error'] ) ) {
					wp_send_json_error( array( 'message' => 'Upload error: ' . $upload['error'] ) );
				}

				// Create attachment
				$attachment = array(
					'post_mime_type' => $upload['type'],
					'post_title'     => sanitize_file_name( $file['name'] ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);

				$attach_id      = wp_insert_attachment( $attachment, $upload['file'] );
				$document_ids[] = $attach_id;

				// Generate attachment metadata
				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
			}
		}
	}

	// Collect form data
	$form_data = array(
		'event_name'            => sanitize_text_field( $_POST['event_name'] ?? '' ),
		'event_type'            => sanitize_text_field( $_POST['event_type'] ?? '' ),
		'event_date'            => sanitize_text_field( $_POST['event_date'] ?? '' ),
		'event_location'        => sanitize_text_field( $_POST['event_location'] ?? '' ),
		'event_description'     => sanitize_textarea_field( $_POST['event_description'] ?? '' ),
		'booth_fee'             => floatval( $_POST['booth_fee'] ?? 0 ),
		'promotional_materials' => floatval( $_POST['promotional_materials'] ?? 0 ),
		'travel_costs'          => floatval( $_POST['travel_costs'] ?? 0 ),
		'other_costs'           => floatval( $_POST['other_costs'] ?? 0 ),
	);

	$fiscal_year      = sanitize_text_field( $_POST['fiscal_year'] ?? '' );
	$amount_requested = floatval( $_POST['amount_requested'] ?? 0 );

	// Prepare data array for submission
	$submission_data = array_merge( $form_data, array(
		'business_id'      => $business_id,
		'user_id'          => $user_id,
		'fiscal_year'      => $fiscal_year,
		'amount_requested' => $amount_requested,
		'documents'        => $document_ids,
	) );

	// Submit reimbursement
	$result = nmda_submit_reimbursement( 'lead', $submission_data );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message'          => 'Your lead generation reimbursement request has been submitted successfully!',
			'reimbursement_id' => $result,
		)
	);
}
add_action( 'wp_ajax_nmda_submit_reimbursement_lead', 'nmda_handle_lead_reimbursement_submission' );

/**
 * Handle advertising reimbursement AJAX submission
 */
function nmda_handle_advertising_reimbursement_submission() {
	// Verify nonce
	if ( ! isset( $_POST['nmda_reimbursement_nonce'] ) || ! wp_verify_nonce( $_POST['nmda_reimbursement_nonce'], 'nmda_reimbursement_advertising' ) ) {
		wp_send_json_error( array( 'message' => 'Security verification failed. Please refresh and try again.' ) );
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in to submit a reimbursement request.' ) );
	}

	$user_id     = get_current_user_id();
	$business_id = isset( $_POST['business_id'] ) ? intval( $_POST['business_id'] ) : 0;

	// Verify user has access to this business
	if ( ! nmda_user_can_access_business( $user_id, $business_id ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to submit reimbursements for this business.' ) );
	}

	// Verify business is approved
	$business_post = get_post( $business_id );
	if ( ! $business_post || $business_post->post_status !== 'publish' ) {
		wp_send_json_error( array( 'message' => 'Only approved businesses can submit reimbursement requests.' ) );
	}

	// Handle file uploads
	$document_ids = array();
	if ( ! empty( $_FILES['documents']['name'][0] ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$files = $_FILES['documents'];
		foreach ( $files['name'] as $key => $value ) {
			if ( $files['name'][ $key ] ) {
				$file = array(
					'name'     => $files['name'][ $key ],
					'type'     => $files['type'][ $key ],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error'    => $files['error'][ $key ],
					'size'     => $files['size'][ $key ],
				);

				// Check file size (5MB max)
				if ( $file['size'] > 5242880 ) {
					wp_send_json_error( array( 'message' => 'File ' . $file['name'] . ' exceeds 5MB limit.' ) );
				}

				$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

				if ( isset( $upload['error'] ) ) {
					wp_send_json_error( array( 'message' => 'Upload error: ' . $upload['error'] ) );
				}

				// Create attachment
				$attachment = array(
					'post_mime_type' => $upload['type'],
					'post_title'     => sanitize_file_name( $file['name'] ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);

				$attach_id      = wp_insert_attachment( $attachment, $upload['file'] );
				$document_ids[] = $attach_id;

				// Generate attachment metadata
				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
			}
		}
	}

	// Collect form data
	$form_data = array(
		'campaign_name'        => sanitize_text_field( $_POST['campaign_name'] ?? '' ),
		'ad_type'              => sanitize_text_field( $_POST['ad_type'] ?? '' ),
		'publication_platform' => sanitize_text_field( $_POST['publication_platform'] ?? '' ),
		'start_date'           => sanitize_text_field( $_POST['start_date'] ?? '' ),
		'end_date'             => sanitize_text_field( $_POST['end_date'] ?? '' ),
		'circulation_reach'    => sanitize_text_field( $_POST['circulation_reach'] ?? '' ),
		'campaign_description' => sanitize_textarea_field( $_POST['campaign_description'] ?? '' ),
		'ad_cost'              => floatval( $_POST['ad_cost'] ?? 0 ),
		'design_cost'          => floatval( $_POST['design_cost'] ?? 0 ),
		'other_costs'          => floatval( $_POST['other_costs'] ?? 0 ),
	);

	$fiscal_year      = sanitize_text_field( $_POST['fiscal_year'] ?? '' );
	$amount_requested = floatval( $_POST['amount_requested'] ?? 0 );

	// Prepare data array for submission
	$submission_data = array_merge( $form_data, array(
		'business_id'      => $business_id,
		'user_id'          => $user_id,
		'fiscal_year'      => $fiscal_year,
		'amount_requested' => $amount_requested,
		'documents'        => $document_ids,
	) );

	// Submit reimbursement
	$result = nmda_submit_reimbursement( 'advertising', $submission_data );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message'          => 'Your advertising reimbursement request has been submitted successfully!',
			'reimbursement_id' => $result,
		)
	);
}
add_action( 'wp_ajax_nmda_submit_reimbursement_advertising', 'nmda_handle_advertising_reimbursement_submission' );

/**
 * Handle labels reimbursement AJAX submission
 */
function nmda_handle_labels_reimbursement_submission() {
	// Verify nonce
	if ( ! isset( $_POST['nmda_reimbursement_nonce'] ) || ! wp_verify_nonce( $_POST['nmda_reimbursement_nonce'], 'nmda_reimbursement_labels' ) ) {
		wp_send_json_error( array( 'message' => 'Security verification failed. Please refresh and try again.' ) );
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in to submit a reimbursement request.' ) );
	}

	$user_id     = get_current_user_id();
	$business_id = isset( $_POST['business_id'] ) ? intval( $_POST['business_id'] ) : 0;

	// Verify user has access to this business
	if ( ! nmda_user_can_access_business( $user_id, $business_id ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to submit reimbursements for this business.' ) );
	}

	// Verify business is approved
	$business_post = get_post( $business_id );
	if ( ! $business_post || $business_post->post_status !== 'publish' ) {
		wp_send_json_error( array( 'message' => 'Only approved businesses can submit reimbursement requests.' ) );
	}

	// Handle file uploads
	$document_ids = array();
	if ( ! empty( $_FILES['documents']['name'][0] ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$files = $_FILES['documents'];
		foreach ( $files['name'] as $key => $value ) {
			if ( $files['name'][ $key ] ) {
				$file = array(
					'name'     => $files['name'][ $key ],
					'type'     => $files['type'][ $key ],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error'    => $files['error'][ $key ],
					'size'     => $files['size'][ $key ],
				);

				// Check file size (5MB max)
				if ( $file['size'] > 5242880 ) {
					wp_send_json_error( array( 'message' => 'File ' . $file['name'] . ' exceeds 5MB limit.' ) );
				}

				$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

				if ( isset( $upload['error'] ) ) {
					wp_send_json_error( array( 'message' => 'Upload error: ' . $upload['error'] ) );
				}

				// Create attachment
				$attachment = array(
					'post_mime_type' => $upload['type'],
					'post_title'     => sanitize_file_name( $file['name'] ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);

				$attach_id      = wp_insert_attachment( $attachment, $upload['file'] );
				$document_ids[] = $attach_id;

				// Generate attachment metadata
				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
			}
		}
	}

	// Collect form data
	$form_data = array(
		'product_name'       => sanitize_text_field( $_POST['product_name'] ?? '' ),
		'label_type'         => sanitize_text_field( $_POST['label_type'] ?? '' ),
		'quantity'           => intval( $_POST['quantity'] ?? 0 ),
		'label_size'         => sanitize_text_field( $_POST['label_size'] ?? '' ),
		'vendor_name'        => sanitize_text_field( $_POST['vendor_name'] ?? '' ),
		'label_description'  => sanitize_textarea_field( $_POST['label_description'] ?? '' ),
		'design_cost'        => floatval( $_POST['design_cost'] ?? 0 ),
		'printing_cost'      => floatval( $_POST['printing_cost'] ?? 0 ),
		'setup_cost'         => floatval( $_POST['setup_cost'] ?? 0 ),
		'shipping_cost'      => floatval( $_POST['shipping_cost'] ?? 0 ),
		'other_costs'        => floatval( $_POST['other_costs'] ?? 0 ),
	);

	$fiscal_year      = sanitize_text_field( $_POST['fiscal_year'] ?? '' );
	$amount_requested = floatval( $_POST['amount_requested'] ?? 0 );

	// Prepare data array for submission
	$submission_data = array_merge( $form_data, array(
		'business_id'      => $business_id,
		'user_id'          => $user_id,
		'fiscal_year'      => $fiscal_year,
		'amount_requested' => $amount_requested,
		'documents'        => $document_ids,
	) );

	// Submit reimbursement
	$result = nmda_submit_reimbursement( 'labels', $submission_data );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message'          => 'Your labels reimbursement request has been submitted successfully!',
			'reimbursement_id' => $result,
		)
	);
}
add_action( 'wp_ajax_nmda_submit_reimbursement_labels', 'nmda_handle_labels_reimbursement_submission' );
