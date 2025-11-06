<?php
/**
 * Template Name: My Reimbursements
 * Template for viewing member's reimbursement history
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

// Get filter parameters
$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$filter_type   = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
$filter_year   = isset( $_GET['year'] ) ? sanitize_text_field( $_GET['year'] ) : '';
$filter_business = isset( $_GET['business'] ) ? intval( $_GET['business'] ) : 0;

// Get user's reimbursements
$reimbursements = nmda_get_user_reimbursements( $user_id, array(
	'status'      => $filter_status,
	'type'        => $filter_type,
	'fiscal_year' => $filter_year,
	'business_id' => $filter_business,
) );

// Get user's businesses for filter
$user_businesses = nmda_get_user_businesses( $user_id );
$approved_businesses = array_filter( $user_businesses, function( $business ) {
	$post = get_post( $business['business_id'] );
	return $post && $post->post_status === 'publish';
} );

// Get available fiscal years
global $wpdb;
$reimbursement_table = $wpdb->prefix . 'nmda_reimbursements';
$fiscal_years = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT DISTINCT fiscal_year FROM {$reimbursement_table} WHERE user_id = %d ORDER BY fiscal_year DESC",
		$user_id
	)
);
?>

<div class="wrapper" id="page-wrapper">
	<div class="container" id="content">

		<!-- Page Header -->
		<div class="dashboard-header">
			<div class="row align-items-center">
				<div class="col-md-8">
					<h1 class="dashboard-title"><i class="fa fa-dollar"></i> My Reimbursements</h1>
					<p class="dashboard-subtitle">View and track your reimbursement requests</p>
				</div>
				<div class="col-md-4 text-right">
					<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-secondary">
						<i class="fa fa-arrow-left"></i> Back to Dashboard
					</a>
				</div>
			</div>
		</div>

		<!-- Filters -->
		<div class="card mt-4">
			<div class="card-body">
				<form method="get" class="row align-items-end">
					<div class="col-md-3 form-group">
						<label for="status">Status</label>
						<select name="status" id="status" class="form-control">
							<option value="">All Statuses</option>
							<option value="submitted" <?php selected( $filter_status, 'submitted' ); ?>>Submitted</option>
							<option value="approved" <?php selected( $filter_status, 'approved' ); ?>>Approved</option>
							<option value="rejected" <?php selected( $filter_status, 'rejected' ); ?>>Rejected</option>
						</select>
					</div>
					<div class="col-md-3 form-group">
						<label for="type">Type</label>
						<select name="type" id="type" class="form-control">
							<option value="">All Types</option>
							<option value="lead" <?php selected( $filter_type, 'lead' ); ?>>Lead Generation</option>
							<option value="advertising" <?php selected( $filter_type, 'advertising' ); ?>>Advertising</option>
							<option value="labels" <?php selected( $filter_type, 'labels' ); ?>>Product Labels</option>
						</select>
					</div>
					<div class="col-md-2 form-group">
						<label for="year">Fiscal Year</label>
						<select name="year" id="year" class="form-control">
							<option value="">All Years</option>
							<?php foreach ( $fiscal_years as $year ) : ?>
								<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $filter_year, $year ); ?>>
									<?php echo esc_html( $year ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-3 form-group">
						<label for="business">Business</label>
						<select name="business" id="business" class="form-control">
							<option value="">All Businesses</option>
							<?php foreach ( $approved_businesses as $business ) : ?>
								<?php $business_post = get_post( $business['business_id'] ); ?>
								<option value="<?php echo esc_attr( $business['business_id'] ); ?>" <?php selected( $filter_business, $business['business_id'] ); ?>>
									<?php echo esc_html( $business_post->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-1 form-group">
						<button type="submit" class="btn btn-primary btn-block">
							<i class="fa fa-filter"></i>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Summary Stats -->
		<div class="row mt-4 dashboard-stats">
			<?php
			$stats = nmda_get_reimbursement_stats_for_user( $user_id, $filter_year ?: date( 'Y' ) );
			?>
			<div class="col-md-3">
				<div class="stat-card">
					<div class="stat-icon">
						<i class="fa fa-file-text"></i>
					</div>
					<div class="stat-content">
						<h3><?php echo count( $reimbursements ); ?></h3>
						<p>Total Requests</p>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="stat-card">
					<div class="stat-icon">
						<i class="fa fa-clock-o"></i>
					</div>
					<div class="stat-content">
						<h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
						<p>Pending</p>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="stat-card">
					<div class="stat-icon">
						<i class="fa fa-check-circle"></i>
					</div>
					<div class="stat-content">
						<h3><?php echo $stats['approved_count'] ?? 0; ?></h3>
						<p>Approved</p>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="stat-card">
					<div class="stat-icon">
						<i class="fa fa-dollar"></i>
					</div>
					<div class="stat-content">
						<h3>$<?php echo number_format( $stats['approved_amount'] ?? 0, 0 ); ?></h3>
						<p>Total Approved</p>
					</div>
				</div>
			</div>
		</div>

		<!-- Reimbursements List -->
		<div class="card mt-4">
			<div class="card-header">
				<h3><i class="fa fa-list"></i> Reimbursement Requests</h3>
			</div>
			<div class="card-body">
				<?php if ( ! empty( $reimbursements ) ) : ?>
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>ID</th>
									<th>Business</th>
									<th>Type</th>
									<th>Fiscal Year</th>
									<th>Amount</th>
									<th>Status</th>
									<th>Submitted</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $reimbursements as $reimbursement ) : ?>
									<?php
									$business = get_post( $reimbursement->business_id );
									$type_labels = array(
										'lead'        => 'Lead Generation',
										'advertising' => 'Advertising',
										'labels'      => 'Product Labels',
									);
									$type_label = $type_labels[ $reimbursement->type ] ?? ucfirst( $reimbursement->type );
									?>
									<tr>
										<td><strong>#<?php echo $reimbursement->id; ?></strong></td>
										<td><?php echo esc_html( $business ? $business->post_title : 'Unknown' ); ?></td>
										<td>
											<span class="badge badge-secondary">
												<?php echo esc_html( $type_label ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $reimbursement->fiscal_year ); ?></td>
										<td>
											<strong>$<?php echo number_format( $reimbursement->amount_requested, 2 ); ?></strong>
											<?php if ( $reimbursement->status === 'approved' && $reimbursement->amount_approved ) : ?>
												<br><small class="text-success">Approved: $<?php echo number_format( $reimbursement->amount_approved, 2 ); ?></small>
											<?php endif; ?>
										</td>
										<td>
											<span class="status-badge status-<?php echo esc_attr( $reimbursement->status ); ?>">
												<?php echo ucfirst( $reimbursement->status ); ?>
											</span>
										</td>
										<td>
											<?php echo date( 'M j, Y', strtotime( $reimbursement->created_at ) ); ?><br>
											<small class="text-muted"><?php echo human_time_diff( strtotime( $reimbursement->created_at ), current_time( 'timestamp' ) ); ?> ago</small>
										</td>
										<td>
											<a href="<?php echo home_url( '/dashboard/reimbursements/' . $reimbursement->id ); ?>" class="btn btn-sm btn-primary">
												<i class="fa fa-eye"></i> View
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="alert alert-info">
						<i class="fa fa-info-circle"></i> No reimbursement requests found matching your filters.
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Submit New Request -->
		<div class="text-center mt-4 mb-5">
			<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-lg btn-outline-primary mr-2">
				<i class="fa fa-arrow-left"></i> Return to Dashboard
			</a>
		</div>

	</div>
</div>

<?php
get_footer();
