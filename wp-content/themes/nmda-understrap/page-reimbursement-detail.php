<?php
/**
 * Template Name: Reimbursement Detail
 * Template for viewing single reimbursement details
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Redirect if not logged in
if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

$user_id = get_current_user_id();

// Get reimbursement ID from URL (e.g., /dashboard/reimbursements/123)
$reimbursement_id = get_query_var( 'reimbursement_id', 0 );

// If not in URL, try $_GET
if ( ! $reimbursement_id && isset( $_GET['id'] ) ) {
	$reimbursement_id = intval( $_GET['id'] );
}

// Convert to int to ensure it's not empty string
$reimbursement_id = intval( $reimbursement_id );

if ( ! $reimbursement_id ) {
	wp_redirect( home_url( '/my-reimbursements' ) );
	exit;
}

// Get reimbursement
$reimbursement = nmda_get_reimbursement( $reimbursement_id );

if ( ! $reimbursement ) {
	wp_redirect( home_url( '/my-reimbursements' ) );
	exit;
}

// Verify user has access to this reimbursement
if ( intval( $reimbursement->user_id ) !== $user_id ) {
	wp_redirect( home_url( '/my-reimbursements' ) );
	exit;
}

// Get business and user info
$business      = get_post( $reimbursement->business_id );
$business_name = $business ? $business->post_title : 'Unknown';

// Decode JSON data
$form_data = $reimbursement->data;
$documents = $reimbursement->documents;

// Type labels
$type_labels = array(
	'lead'        => 'Lead Generation',
	'advertising' => 'Advertising',
	'labels'      => 'Product Labels',
);
$type_label = isset( $type_labels[ $reimbursement->type ] ) ? $type_labels[ $reimbursement->type ] : $reimbursement->type;
?>

<div class="wrapper" id="page-wrapper">
	<div class="container" id="content">

		<!-- Page Header -->
		<div class="dashboard-header">
			<div class="row align-items-center">
				<div class="col-md-8">
					<h1 class="dashboard-title"><i class="fa fa-file-text"></i> Reimbursement #<?php echo $reimbursement_id; ?></h1>
					<p class="dashboard-subtitle"><?php echo esc_html( $type_label ); ?> Request</p>
				</div>
				<div class="col-md-4 text-right">
					<a href="<?php echo home_url( '/my-reimbursements' ); ?>" class="btn btn-secondary">
						<i class="fa fa-arrow-left"></i> Back to List
					</a>
				</div>
			</div>
		</div>

		<!-- Status Badge -->
		<div class="row mt-4">
			<div class="col-md-12">
				<div class="alert alert-<?php echo $reimbursement->status === 'approved' ? 'success' : ( $reimbursement->status === 'rejected' ? 'danger' : 'warning' ); ?>">
					<div class="row align-items-center">
						<div class="col-md-6">
							<h4>
								<i class="fa fa-<?php echo $reimbursement->status === 'approved' ? 'check-circle' : ( $reimbursement->status === 'rejected' ? 'times-circle' : 'clock-o' ); ?>"></i>
								Status: <span class="status-badge status-<?php echo $reimbursement->status; ?>"><?php echo ucfirst( $reimbursement->status ); ?></span>
							</h4>
						</div>
						<div class="col-md-6 text-right">
							<?php if ( $reimbursement->status === 'submitted' ) : ?>
								<p class="mb-0">Your request is being reviewed by NMDA staff. You will receive an email notification once it has been processed.</p>
							<?php elseif ( $reimbursement->status === 'approved' ) : ?>
								<p class="mb-0">Your request has been approved! You will receive payment according to program guidelines.</p>
							<?php elseif ( $reimbursement->status === 'rejected' ) : ?>
								<p class="mb-0">Your request was not approved. Please see notes below for more information.</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Overview Card -->
		<div class="card mt-4">
			<div class="card-header">
				<h3><i class="fa fa-info-circle"></i> Request Overview</h3>
			</div>
			<div class="card-body">
				<table class="table">
					<tr>
						<th width="200">Request ID:</th>
						<td>#<?php echo $reimbursement_id; ?></td>
					</tr>
					<tr>
						<th>Type:</th>
						<td><?php echo esc_html( $type_label ); ?></td>
					</tr>
					<tr>
						<th>Business:</th>
						<td>
							<?php echo esc_html( $business_name ); ?>
							<a href="<?php echo get_permalink( $reimbursement->business_id ); ?>" target="_blank" class="ml-2">
								<i class="fa fa-external-link"></i> View Profile
							</a>
						</td>
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
						<th>Submitted:</th>
						<td>
							<?php echo date( 'F j, Y g:i A', strtotime( $reimbursement->created_at ) ); ?>
							<small class="text-muted">(<?php echo human_time_diff( strtotime( $reimbursement->created_at ), current_time( 'timestamp' ) ); ?> ago)</small>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Request Details Card -->
		<div class="card mt-4">
			<div class="card-header">
				<h3><i class="fa fa-list"></i> Request Details</h3>
			</div>
			<div class="card-body">
				<table class="table">
					<?php foreach ( $form_data as $key => $value ) : ?>
						<?php if ( ! empty( $value ) && ! in_array( $key, array( 'amount_requested' ) ) ) : ?>
							<tr>
								<th width="250"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?>:</th>
								<td>
									<?php
									if ( is_numeric( $value ) && strpos( $key, 'cost' ) !== false ) {
										echo '$' . number_format( $value, 2 );
									} else {
										echo nl2br( esc_html( $value ) );
									}
									?>
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</table>
			</div>
		</div>

		<!-- Supporting Documents -->
		<?php if ( ! empty( $documents ) && is_array( $documents ) ) : ?>
			<div class="card mt-4">
				<div class="card-header">
					<h3><i class="fa fa-paperclip"></i> Supporting Documents</h3>
				</div>
				<div class="card-body">
					<ul class="list-unstyled">
						<?php foreach ( $documents as $doc_id ) : ?>
							<?php if ( $doc_id && is_numeric( $doc_id ) ) : ?>
								<?php
								$attachment = get_post( $doc_id );
								if ( $attachment ) :
									$file_url  = wp_get_attachment_url( $doc_id );
									$file_name = basename( $file_url );
									$file_type = wp_check_filetype( $file_url );
									?>
									<li class="mb-2">
										<i class="fa fa-file-<?php echo strpos( $file_type['type'], 'pdf' ) !== false ? 'pdf' : 'o'; ?>"></i>
										<a href="<?php echo esc_url( $file_url ); ?>" target="_blank">
											<?php echo esc_html( $attachment->post_title ?: $file_name ); ?>
										</a>
										<small class="text-muted">(<?php echo esc_html( $file_type['ext'] ); ?>)</small>
									</li>
								<?php endif; ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>

		<!-- Approval Information -->
		<?php if ( $reimbursement->status === 'approved' ) : ?>
			<div class="card mt-4">
				<div class="card-header bg-success text-white">
					<h3><i class="fa fa-check-circle"></i> Approval Information</h3>
				</div>
				<div class="card-body">
					<table class="table">
						<tr>
							<th width="200">Approved Amount:</th>
							<td><strong class="text-success">$<?php echo number_format( $reimbursement->amount_approved, 2 ); ?></strong></td>
						</tr>
						<tr>
							<th>Reviewed At:</th>
							<td><?php echo date( 'F j, Y g:i A', strtotime( $reimbursement->reviewed_at ) ); ?></td>
						</tr>
						<?php if ( ! empty( $reimbursement->admin_notes ) ) : ?>
							<tr>
								<th>Notes:</th>
								<td><?php echo nl2br( esc_html( $reimbursement->admin_notes ) ); ?></td>
							</tr>
						<?php endif; ?>
					</table>
				</div>
			</div>
		<?php endif; ?>

		<!-- Rejection Information -->
		<?php if ( $reimbursement->status === 'rejected' && ! empty( $reimbursement->admin_notes ) ) : ?>
			<div class="card mt-4">
				<div class="card-header bg-danger text-white">
					<h3><i class="fa fa-times-circle"></i> Rejection Reason</h3>
				</div>
				<div class="card-body">
					<p><?php echo nl2br( esc_html( $reimbursement->admin_notes ) ); ?></p>
					<?php if ( $reimbursement->reviewed_at ) : ?>
						<p class="text-muted mb-0">
							<small>Reviewed at: <?php echo date( 'F j, Y g:i A', strtotime( $reimbursement->reviewed_at ) ); ?></small>
						</p>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Actions -->
		<div class="text-center mt-4 mb-5">
			<a href="<?php echo home_url( '/my-reimbursements' ); ?>" class="btn btn-lg btn-outline-primary mr-2">
				<i class="fa fa-arrow-left"></i> Back to My Reimbursements
			</a>
			<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-lg btn-outline-secondary">
				<i class="fa fa-home"></i> Return to Dashboard
			</a>
		</div>

	</div>
</div>

<?php
get_footer();
