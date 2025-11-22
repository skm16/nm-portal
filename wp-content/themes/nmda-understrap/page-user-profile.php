<?php
/**
 * Template Name: User Profile
 * Description: Admin-only view of a single user's profile and activity
 *
 * @package NMDA_Understrap
 */

defined( 'ABSPATH' ) || exit;

// Security check - Admin only
if ( ! is_user_logged_in() || ! current_user_can( 'administrator' ) ) {
	wp_redirect( home_url() );
	exit;
}

// Get user ID from query parameter
$user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;

if ( ! $user_id ) {
	wp_redirect( get_permalink() );
	exit;
}

// Get user data
$user = get_userdata( $user_id );

if ( ! $user ) {
	wp_redirect( get_permalink() );
	exit;
}

// Get user's businesses
$businesses = nmda_get_user_businesses( $user_id );

// Get reimbursement stats
global $wpdb;
$reimbursement_table = $wpdb->prefix . 'nmda_reimbursements';
$reimbursements = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM $reimbursement_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 10",
	$user_id
) );

// Get communication log
$communications_table = $wpdb->prefix . 'nmda_communications';
$communications = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM $communications_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 10",
	$user_id
) );

// Get user meta
$last_login = get_user_meta( $user_id, 'last_login', true );

get_header();
?>

<div class="wrapper" id="page-wrapper">
	<div class="container-fluid" id="content">
		<div class="row">
			<div class="col-12">

				<!-- Breadcrumb -->
				<nav aria-label="breadcrumb" class="mb-3">
					<ol class="breadcrumb">
						<li class="breadcrumb-item">
							<a href="<?php echo esc_url( get_permalink() ); ?>">User Directory</a>
						</li>
						<li class="breadcrumb-item active"><?php echo esc_html( $user->display_name ); ?></li>
					</ol>
				</nav>

				<!-- Page Header -->
				<div class="dashboard-header mb-4">
					<div class="row align-items-center">
						<div class="col-md-8">
							<h1 class="dashboard-title">
								<i class="fa fa-user"></i> <?php echo esc_html( $user->display_name ); ?>
							</h1>
							<p class="dashboard-subtitle"><?php echo esc_html( $user->user_email ); ?></p>
						</div>
						<div class="col-md-4 text-right">
							<a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>" class="btn btn-secondary">
								<i class="fa fa-edit"></i> Edit User
							</a>
						</div>
					</div>
				</div>

				<div class="row">

					<!-- Left Column -->
					<div class="col-lg-8">

						<!-- Associated Businesses -->
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-building"></i> Associated Businesses (<?php echo count( $businesses ); ?>)</h3>
							</div>
							<div class="card-body">
								<?php if ( ! empty( $businesses ) ) : ?>
									<div class="table-responsive">
										<table class="table table-hover mb-0">
											<thead>
												<tr>
													<th>Business Name</th>
													<th>Role</th>
													<th>Status</th>
													<th>Invited Date</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $businesses as $business ) : ?>
													<?php
													$business_title = get_the_title( $business['business_id'] );
													$business_url   = get_permalink( $business['business_id'] );
													?>
													<tr>
														<td>
															<a href="<?php echo esc_url( $business_url ); ?>" target="_blank">
																<?php echo esc_html( $business_title ); ?>
																<i class="fa fa-external-link-alt fa-sm"></i>
															</a>
														</td>
														<td>
															<span class="badge badge-<?php echo $business['role'] === 'owner' ? 'success' : ( $business['role'] === 'manager' ? 'primary' : 'secondary' ); ?>">
																<?php echo esc_html( ucfirst( $business['role'] ) ); ?>
															</span>
														</td>
														<td>
															<span class="status-indicator status-<?php echo esc_attr( $business['status'] ); ?>"></span>
															<?php echo esc_html( ucfirst( $business['status'] ) ); ?>
														</td>
														<td>
															<?php
															if ( $business['invited_date'] && $business['invited_date'] !== '0000-00-00 00:00:00' ) {
																echo esc_html( date( 'M j, Y', strtotime( $business['invited_date'] ) ) );
															} else {
																echo '<span class="text-muted">N/A</span>';
															}
															?>
														</td>
														<td>
															<a href="<?php echo esc_url( get_edit_post_link( $business['business_id'] ) ); ?>" class="btn btn-sm btn-outline-secondary">
																<i class="fa fa-edit"></i>
															</a>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								<?php else : ?>
									<p class="text-muted text-center py-3">
										<i class="fa fa-info-circle"></i> This user is not associated with any businesses.
									</p>
								<?php endif; ?>
							</div>
						</div>

						<!-- Reimbursement History -->
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-dollar-sign"></i> Recent Reimbursements</h3>
							</div>
							<div class="card-body">
								<?php if ( ! empty( $reimbursements ) ) : ?>
									<div class="table-responsive">
										<table class="table table-hover mb-0">
											<thead>
												<tr>
													<th>Type</th>
													<th>Business</th>
													<th>Status</th>
													<th>Fiscal Year</th>
													<th>Submitted</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $reimbursements as $reimbursement ) : ?>
													<tr>
														<td>
															<span class="badge badge-info">
																<?php echo esc_html( ucfirst( $reimbursement->type ) ); ?>
															</span>
														</td>
														<td><?php echo esc_html( get_the_title( $reimbursement->business_id ) ); ?></td>
														<td>
															<span class="nmda-status-badge <?php echo esc_attr( $reimbursement->status ); ?>">
																<?php echo esc_html( ucfirst( $reimbursement->status ) ); ?>
															</span>
														</td>
														<td><?php echo esc_html( $reimbursement->fiscal_year ); ?></td>
														<td>
															<?php
															if ( $reimbursement->created_at ) {
																echo esc_html( date( 'M j, Y', strtotime( $reimbursement->created_at ) ) );
															}
															?>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								<?php else : ?>
									<p class="text-muted text-center py-3">
										<i class="fa fa-info-circle"></i> No reimbursement history found.
									</p>
								<?php endif; ?>
							</div>
						</div>

						<!-- Communication Log -->
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-comments"></i> Recent Communications</h3>
							</div>
							<div class="card-body">
								<?php if ( ! empty( $communications ) ) : ?>
									<div class="communication-timeline">
										<?php foreach ( $communications as $comm ) : ?>
											<div class="communication-item">
												<div class="communication-icon">
													<i class="fa fa-<?php echo esc_attr( $comm->type === 'application' ? 'file-alt' : ( $comm->type === 'reimbursement' ? 'dollar-sign' : 'comment' ) ); ?>"></i>
												</div>
												<div class="communication-content">
													<div class="communication-header">
														<strong><?php echo esc_html( ucfirst( $comm->type ) ); ?></strong>
														<span class="communication-date">
															<?php echo human_time_diff( strtotime( $comm->created_at ), current_time( 'timestamp' ) ); ?> ago
														</span>
													</div>
													<div class="communication-message">
														<?php echo esc_html( wp_trim_words( $comm->message, 20 ) ); ?>
													</div>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<p class="text-muted text-center py-3">
										<i class="fa fa-info-circle"></i> No communication history found.
									</p>
								<?php endif; ?>
							</div>
						</div>

					</div>

					<!-- Right Column (Sidebar) -->
					<div class="col-lg-4">

						<!-- User Information Card -->
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-info-circle"></i> User Information</h3>
							</div>
							<div class="card-body">
								<div class="user-info-item">
									<i class="fa fa-user"></i>
									<div>
										<strong>Username:</strong><br>
										<?php echo esc_html( $user->user_login ); ?>
									</div>
								</div>

								<div class="user-info-item">
									<i class="fa fa-envelope"></i>
									<div>
										<strong>Email:</strong><br>
										<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>">
											<?php echo esc_html( $user->user_email ); ?>
										</a>
									</div>
								</div>

								<div class="user-info-item">
									<i class="fa fa-calendar-plus"></i>
									<div>
										<strong>Registered:</strong><br>
										<?php echo esc_html( date( 'M j, Y', strtotime( $user->user_registered ) ) ); ?>
									</div>
								</div>

								<?php if ( $last_login ) : ?>
									<div class="user-info-item">
										<i class="fa fa-sign-in-alt"></i>
										<div>
											<strong>Last Login:</strong><br>
											<?php echo human_time_diff( $last_login, current_time( 'timestamp' ) ); ?> ago
										</div>
									</div>
								<?php endif; ?>

								<div class="user-info-item">
									<i class="fa fa-shield-alt"></i>
									<div>
										<strong>Role:</strong><br>
										<?php
										$roles = $user->roles;
										echo esc_html( ucfirst( implode( ', ', $roles ) ) );
										?>
									</div>
								</div>
							</div>
						</div>

						<!-- Quick Stats -->
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-chart-bar"></i> Quick Stats</h3>
							</div>
							<div class="card-body">
								<div class="stat-item">
									<div class="stat-number"><?php echo count( $businesses ); ?></div>
									<div class="stat-label">Associated Businesses</div>
								</div>

								<div class="stat-item">
									<div class="stat-number"><?php echo count( $reimbursements ); ?></div>
									<div class="stat-label">Total Reimbursements</div>
								</div>

								<div class="stat-item">
									<div class="stat-number"><?php echo count( $communications ); ?></div>
									<div class="stat-label">Communications</div>
								</div>
							</div>
						</div>

						<!-- Quick Actions -->
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-bolt"></i> Quick Actions</h3>
							</div>
							<div class="card-body">
								<a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>" class="btn btn-primary btn-block mb-2">
									<i class="fa fa-edit"></i> Edit User
								</a>
								<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>" class="btn btn-secondary btn-block mb-2">
									<i class="fa fa-envelope"></i> Send Email
								</a>
								<button type="button" class="btn btn-outline-primary btn-block" onclick="window.print();">
									<i class="fa fa-print"></i> Print Profile
								</button>
							</div>
						</div>

					</div>

				</div>

			</div>
		</div>
	</div>
</div>

<?php
get_footer();
