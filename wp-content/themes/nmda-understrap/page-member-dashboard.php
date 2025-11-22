<?php
/**
 * Template Name: Member Dashboard
 * Template for member dashboard homepage
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

$user_id    = get_current_user_id();
$user       = wp_get_current_user();
$businesses = nmda_get_user_businesses( $user_id );

// Get last login time from user meta, or use registration date as fallback
$last_login = get_user_meta( $user_id, 'last_login', true );
if ( ! $last_login ) {
	$last_login = $user->user_registered;
}
?>

<div class="wrapper" id="page-wrapper">
	<div class="container" id="content">
		<div class="row">
			<div class="col-12">
				<!-- Dashboard Header -->
				<div class="dashboard-header">
					<div class="row align-items-center">
						<div class="col-md-8">
							<h1 class="dashboard-title">Welcome, <?php echo esc_html( $user->display_name ); ?>!</h1>
							<p class="dashboard-subtitle">Manage your New Mexico Logo Program membership</p>
						</div>
						<div class="col-md-4 text-right">
							<span class="last-login">Last login: <?php echo human_time_diff( strtotime( $last_login ), current_time( 'timestamp' ) ); ?> ago</span>
						</div>
					</div>
				</div>

				<?php if ( empty( $businesses ) ) : ?>
					<!-- No Business Application -->
					<div class="row mt-4">
						<div class="col-md-12">
							<div class="alert alert-info">
								<h4><i class="fa fa-info-circle"></i> Get Started</h4>
								<p>You haven't submitted a business application yet. Apply now to join the New Mexico Logo Program!</p>
								<a href="<?php echo get_permalink( get_page_by_path( 'business-application' ) ); ?>" class="btn btn-primary">
									<i class="fa fa-file-text"></i> Apply Now
								</a>
							</div>
						</div>
					</div>
				<?php else : ?>

					<?php
					// Separate businesses by approval status
					$approved_businesses = array();
					$pending_businesses  = array();
					$rejected_businesses = array();

					foreach ( $businesses as $business ) {
						$business_id   = $business['business_id'];
						$business_post = get_post( $business_id );

						// Skip if post doesn't exist or is trashed
						if ( ! $business_post || $business_post->post_status === 'trash' ) {
							continue;
						}

						$approval_status = get_field( 'approval_status', $business_id );

						// Check post status - published posts should be approved
						if ( $business_post->post_status === 'publish' || $approval_status === 'approved' ) {
							$approved_businesses[] = $business;
						} elseif ( $approval_status === 'rejected' ) {
							$rejected_businesses[] = $business;
						} elseif ( $approval_status === 'pending' || $business_post->post_status === 'pending' || $business_post->post_status === 'draft' ) {
							$pending_businesses[] = $business;
						}
						// If none of the above, skip it (invalid state)
					}
					?>

					<!-- Pending Applications -->
					<?php foreach ( $pending_businesses as $business ) : ?>
						<?php
						$business_id   = $business['business_id'];
						$business_post = get_post( $business_id );
						?>
						<div class="row mt-4">
							<div class="col-md-12">
								<div class="alert alert-warning">
									<h4><i class="fa fa-clock-o"></i> Application Under Review</h4>
									<p>Your application for <strong><?php echo esc_html( $business_post->post_title ); ?></strong> is currently being reviewed by NMDA staff. You will receive an email notification once your application has been processed.</p>
									<p><strong>Application ID:</strong> #<?php echo $business_id; ?><br>
									<strong>Submitted:</strong> <?php echo get_the_date( 'F j, Y g:i A', $business_id ); ?></p>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

					<!-- Rejected Applications -->
					<?php foreach ( $rejected_businesses as $business ) : ?>
						<?php
						$business_id   = $business['business_id'];
						$business_post = get_post( $business_id );
						$admin_notes   = get_field( 'admin_notes', $business_id );
						?>
						<div class="row mt-4">
							<div class="col-md-12">
								<div class="alert alert-danger">
									<h4><i class="fa fa-exclamation-triangle"></i> Application Not Approved</h4>
									<p>Your application for <strong><?php echo esc_html( $business_post->post_title ); ?></strong> was not approved. Please contact NMDA staff for more information.</p>
									<?php if ( $admin_notes ) : ?>
										<p><strong>Feedback:</strong> <?php echo nl2br( esc_html( $admin_notes ) ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

					<!-- Approved Businesses -->
					<?php if ( ! empty( $approved_businesses ) ) : ?>
						<div class="row mt-4">
							<div class="col-md-8">
								<!-- Business Accordion -->
								<div class="accordion" id="business-accordion">
									<?php foreach ( $approved_businesses as $index => $business ) : ?>
										<?php
										$business_id     = $business['business_id'];
										$business_post   = get_post( $business_id );
										$role            = $business['role'];
										$approval_date   = get_field( 'approval_date', $business_id );
										$accordion_id    = 'business-' . $business_id;
										$is_first        = ( $index === 0 );
										?>

										<div class="card business-accordion-card">
											<div class="card-header" id="heading-<?php echo $accordion_id; ?>">
												<h2 class="mb-0">
													<button class="btn btn-link btn-block text-left <?php echo $is_first ? '' : 'collapsed'; ?>" type="button" data-toggle="collapse" data-target="#collapse-<?php echo $accordion_id; ?>" aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo $accordion_id; ?>">
														<i class="fa fa-building"></i> <?php echo esc_html( $business_post->post_title ); ?>
														<span class="badge badge-success ml-2"><?php echo ucfirst( $role ); ?></span>
														<i class="fa fa-chevron-down float-right"></i>
													</button>
												</h2>
											</div>

											<div id="collapse-<?php echo $accordion_id; ?>" class="collapse <?php echo $is_first ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo $accordion_id; ?>" data-parent="#business-accordion">
												<div class="card-body">

													<!-- Quick Stats Cards -->
													<div class="row mb-4 dashboard-stats">
														<div class="col-md-6 mb-4">
															<div class="stat-card">
																<div class="stat-icon">
																	<i class="fa fa-check-circle"></i>
																</div>
																<div class="stat-content">
																	<h3>Active</h3>
																	<p>Status</p>
																</div>
															</div>
														</div>
														<div class="col-md-6 mb-4">
															<div class="stat-card">
																<div class="stat-icon">
																	<i class="fa fa-shopping-bag"></i>
																</div>
																<div class="stat-content">
																	<?php
																	$product_count = wp_count_terms(
																		array(
																			'taxonomy'   => 'product_type',
																			'object_ids' => array( $business_id ),
																		)
																	);
																	?>
																	<h3><?php echo $product_count; ?></h3>
																	<p>Products</p>
																</div>
															</div>
														</div>
														<div class="col-md-6">
															<div class="stat-card">
																<div class="stat-icon">
																	<i class="fa fa-dollar"></i>
																</div>
																<div class="stat-content">
																	<?php
																	global $wpdb;
																	$reimbursement_table = $wpdb->prefix . 'nmda_reimbursements';
																	$current_fiscal_year = date( 'Y' );
																	$reimbursement_count = $wpdb->get_var(
																		$wpdb->prepare(
																			"SELECT COUNT(*) FROM {$reimbursement_table} WHERE business_id = %d AND fiscal_year = %s",
																			$business_id,
																			$current_fiscal_year
																		)
																	);
																	?>
																	<h3><?php echo $reimbursement_count; ?></h3>
																	<p>This Year</p>
																</div>
															</div>
														</div>
														<div class="col-md-6">
															<div class="stat-card">
																<div class="stat-icon">
																	<i class="fa fa-calendar"></i>
																</div>
																<div class="stat-content">
																	<?php
																	// Use approval date if available, otherwise use post date
																	$member_since_date = $approval_date ? strtotime( $approval_date ) : get_the_time( 'U', $business_id );
																	?>
																	<h3><?php echo human_time_diff( $member_since_date, current_time( 'timestamp' ) ); ?></h3>
																	<p>Member</p>
																</div>
															</div>
														</div>
													</div>

													<!-- Quick Actions -->
													<div class="card quick-actions-card mb-4">
														<div class="card-body">
															<h4 class="card-title"><i class="fa fa-flash"></i> Quick Actions</h4>
															<div class="row">
																<div class="col-md-6">
																	<a href="<?php echo home_url( '/edit-profile/?business_id=' . $business_id ); ?>" class="quick-action-btn">
																		<i class="fa fa-edit"></i>
																		<span>Edit Profile</span>
																	</a>
																</div>
																<div class="col-md-6">
																	<a href="<?php echo get_permalink( $business_id ); ?>" class="quick-action-btn" target="_blank">
																		<i class="fa fa-external-link"></i>
																		<span>View Public Profile</span>
																	</a>
																</div>
															</div>
														</div>
													</div>

													<!-- Business Profile Details -->
													<div class="card business-profile-card">
														<div class="card-header">
															<h4><i class="fa fa-building"></i> Business Details</h4>
														</div>
														<div class="card-body">
															<div class="business-header">
																<?php if ( has_post_thumbnail( $business_id ) ) : ?>
																	<div class="business-logo">
																		<?php echo get_the_post_thumbnail( $business_id, 'medium' ); ?>
																	</div>
																<?php endif; ?>
																<div class="business-info">
																	<h3><?php echo esc_html( $business_post->post_title ); ?></h3>
																	<?php
																	$dba = get_field( 'dba', $business_id );
																	if ( $dba ) :
																		?>
																		<p class="dba">DBA: <?php echo esc_html( $dba ); ?></p>
																	<?php endif; ?>

																	<?php
																	$classification = get_field( 'classification', $business_id );
																	if ( is_array( $classification ) ) :
																		?>
																		<div class="classifications">
																			<?php foreach ( $classification as $class ) : ?>
																				<span class="badge badge-primary"><?php echo ucfirst( $class ); ?></span>
																			<?php endforeach; ?>
																		</div>
																	<?php endif; ?>
																</div>
															</div>

															<hr>

															<div class="business-details">
																<div class="row">
																	<div class="col-md-6">
																		<h5>Contact Information</h5>
																		<p>
																			<i class="fa fa-phone"></i> <?php echo esc_html( get_field( 'business_phone', $business_id ) ); ?><br>
																			<i class="fa fa-envelope"></i> <?php echo esc_html( get_field( 'business_email', $business_id ) ); ?>
																			<?php
																			$website = get_field( 'website', $business_id );
																			if ( $website ) :
																				?>
																				<br><i class="fa fa-globe"></i> <a href="<?php echo esc_url( $website ); ?>" target="_blank"><?php echo esc_html( $website ); ?></a>
																			<?php endif; ?>
																		</p>
																	</div>
																	<div class="col-md-6">
																		<h5>Location</h5>
																		<p>
																			<?php echo esc_html( get_field( 'primary_address', $business_id ) ); ?><br>
																			<?php echo esc_html( get_field( 'primary_city', $business_id ) . ', ' . get_field( 'primary_state', $business_id ) . ' ' . get_field( 'primary_zip', $business_id ) ); ?>
																		</p>
																	</div>
																</div>

																<?php
																$profile = get_field( 'business_profile', $business_id );
																if ( $profile ) :
																	?>
																	<h5 class="mt-3">About</h5>
																	<p><?php echo nl2br( esc_html( $profile ) ); ?></p>
																<?php endif; ?>

																<?php
																$products = wp_get_post_terms( $business_id, 'product_type' );
																if ( ! empty( $products ) ) :
																	?>
																	<h5 class="mt-3">Products</h5>
																	<div class="product-tags">
																		<?php foreach ( $products as $product ) : ?>
																			<span class="badge badge-secondary"><?php echo esc_html( $product->name ); ?></span>
																		<?php endforeach; ?>
																	</div>
																<?php endif; ?>
															</div>

															<div class="text-right mt-3">
																<a href="<?php echo home_url( '/edit-profile/?business_id=' . $business_id ); ?>" class="btn btn-primary">
																	<i class="fa fa-edit"></i> Edit Profile
																</a>
															</div>
														</div>
													</div>

													<!-- Recent Activity for this Business -->
													<div class="card mt-3">
														<div class="card-header">
															<h4><i class="fa fa-clock-o"></i> Recent Activity</h4>
														</div>
														<div class="card-body">
															<?php
															global $wpdb;
															$reimbursement_table = $wpdb->prefix . 'nmda_reimbursements';
															$recent_reimbursements = $wpdb->get_results(
																$wpdb->prepare(
																	"SELECT * FROM {$reimbursement_table} WHERE business_id = %d ORDER BY created_at DESC LIMIT 5",
																	$business_id
																)
															);
															?>
															<?php if ( ! empty( $recent_reimbursements ) ) : ?>
																<ul class="activity-list">
																	<?php foreach ( $recent_reimbursements as $reimbursement ) : ?>
																		<li>
																			<strong><?php echo ucfirst( $reimbursement->type ); ?> Reimbursement</strong><br>
																			<small>
																				<span class="status-badge status-<?php echo esc_attr( $reimbursement->status ); ?>">
																					<?php echo ucfirst( $reimbursement->status ); ?>
																				</span>
																				<?php echo human_time_diff( strtotime( $reimbursement->created_at ), current_time( 'timestamp' ) ); ?> ago
																			</small>
																		</li>
																	<?php endforeach; ?>
																</ul>
															<?php else : ?>
																<p class="text-muted">No recent activity</p>
															<?php endif; ?>
														</div>
													</div>

												</div>
											</div>
										</div>

									<?php endforeach; ?>
								</div>
							</div>

							<!-- Sidebar (shared across all businesses) -->
							<div class="col-md-4">
								<!-- My Reimbursements -->
								<div class="card">
									<div class="card-header">
										<h3><i class="fa fa-list-alt"></i> My Reimbursements</h3>
									</div>
									<div class="card-body">
										<?php
										// Get user's reimbursement stats
										$user_reimbursement_stats = nmda_get_reimbursement_stats_for_user( $user_id, date( 'Y' ) );
										?>
										<p>View and track all your reimbursement requests.</p>
										<div class="row text-center mb-3">
											<div class="col-4">
												<h4 class="mb-0"><?php echo $user_reimbursement_stats['pending_count'] ?? 0; ?></h4>
												<small class="text-muted">Pending</small>
											</div>
											<div class="col-4">
												<h4 class="mb-0 text-success"><?php echo $user_reimbursement_stats['approved_count'] ?? 0; ?></h4>
												<small class="text-muted">Approved</small>
											</div>
											<div class="col-4">
												<h4 class="mb-0 text-danger"><?php echo $user_reimbursement_stats['rejected_count'] ?? 0; ?></h4>
												<small class="text-muted">Rejected</small>
											</div>
										</div>
										<a href="<?php echo home_url( '/my-reimbursements' ); ?>" class="btn btn-primary btn-block">
											<i class="fa fa-list"></i> View All Reimbursements
										</a>
									</div>
								</div>

								<!-- Messages Widget -->
								<div class="card mt-3">
									<div class="card-header">
										<div class="d-flex justify-content-between align-items-center">
											<h3 class="mb-0"><i class="fa fa-envelope"></i> Messages</h3>
											<?php
											$unread_count = nmda_get_unread_count( $user_id );
											if ( $unread_count > 0 ) :
												?>
												<span class="badge badge-danger"><?php echo $unread_count; ?> New</span>
											<?php endif; ?>
										</div>
									</div>
									<div class="card-body">
										<?php
										// Get recent messages (last 5)
										$recent_messages = nmda_get_messages( $user_id, 'all', 5, 0 );
										?>
										<?php if ( ! empty( $recent_messages ) ) : ?>
											<div class="recent-messages-list">
												<?php foreach ( $recent_messages as $msg ) : ?>
													<div class="recent-message-item <?php echo $msg->is_unread ? 'unread' : ''; ?>">
														<a href="<?php echo home_url( '/messages/?id=' . $msg->id ); ?>" class="message-link">
															<div class="message-header">
																<?php if ( $msg->is_unread && ! $msg->sender['is_me'] ) : ?>
																	<span class="badge badge-danger badge-sm mr-1">New</span>
																<?php endif; ?>
																<strong><?php echo $msg->sender['is_me'] ? 'You' : esc_html( $msg->sender['name'] ); ?></strong>
																<?php if ( $msg->sender['is_me'] ) : ?>
																	<span class="text-muted">→ <?php echo esc_html( $msg->recipient['name'] ); ?></span>
																<?php endif; ?>
															</div>
															<div class="message-subject"><?php echo esc_html( $msg->subject ?: '(No subject)' ); ?></div>
															<small class="text-muted">
																<?php echo human_time_diff( strtotime( $msg->created_at ), current_time( 'timestamp' ) ); ?> ago
															</small>
														</a>
													</div>
												<?php endforeach; ?>
											</div>
										<?php else : ?>
											<p class="text-muted mb-0">No messages yet.</p>
										<?php endif; ?>
										<div class="mt-3">
											<a href="<?php echo home_url( '/messages' ); ?>" class="btn btn-primary btn-block">
												<i class="fa fa-envelope"></i> View All Messages
											</a>
										</div>
									</div>
								</div>

								<!-- Submit Reimbursement -->
								<div class="card mt-3">
									<div class="card-header">
										<h3><i class="fa fa-dollar"></i> Submit Reimbursement</h3>
									</div>
									<div class="card-body">
										<p>Request cost-share reimbursements for your marketing activities.</p>
										<div class="reimbursement-links">
											<a href="<?php echo home_url( '/reimbursement-lead' ); ?>" class="btn btn-outline-primary btn-block mb-2">
												<i class="fa fa-users"></i> Lead Generation
												<small class="d-block text-muted">Max $5,000/year</small>
											</a>
											<a href="<?php echo home_url( '/reimbursement-advertising' ); ?>" class="btn btn-outline-primary btn-block mb-2">
												<i class="fa fa-bullhorn"></i> Advertising
												<small class="d-block text-muted">Max $10,000/year</small>
											</a>
											<a href="<?php echo home_url( '/reimbursement-labels' ); ?>" class="btn btn-outline-primary btn-block">
												<i class="fa fa-tag"></i> Product Labels
												<small class="d-block text-muted">Max $3,000/year</small>
											</a>
										</div>
									</div>
								</div>

								<!-- Resources -->
								<div class="card mt-3">
									<div class="card-header">
										<h3><i class="fa fa-download"></i> Resources</h3>
									</div>
									<div class="card-body">
										<?php
										$resources = nmda_get_resources( array(
											'posts_per_page' => 5,
											'orderby'        => 'date',
											'order'          => 'DESC',
										) );
										?>
										<?php if ( ! empty( $resources ) ) : ?>
											<p>Access member-only downloads, logos, and program guidelines.</p>
											<ul class="resource-list">
												<?php foreach ( $resources as $resource ) : ?>
													<?php
													$file_info = nmda_get_resource_file_info( $resource->ID );
													$resource_type = get_field( 'resource_type', $resource->ID );
													$is_external = $resource_type === 'URL';
													$file_icon = 'fa-file-o';

													if ( $file_info ) {
														if ( $is_external ) {
															$file_icon = 'fa-external-link';
														} else {
															switch ( $file_info['ext'] ) {
																case 'pdf':
																	$file_icon = 'fa-file-pdf-o';
																	break;
																case 'doc':
																case 'docx':
																	$file_icon = 'fa-file-word-o';
																	break;
																case 'xls':
																case 'xlsx':
																	$file_icon = 'fa-file-excel-o';
																	break;
																case 'jpg':
																case 'jpeg':
																case 'png':
																case 'gif':
																	$file_icon = 'fa-file-image-o';
																	break;
																case 'zip':
																	$file_icon = 'fa-file-archive-o';
																	break;
															}
														}
													}

													// Determine link
													if ( $is_external && $file_info ) {
														$link = $file_info['url'];
														$target = ' target="_blank" rel="noopener"';
													} else {
														$link = add_query_arg( 'nmda_download_resource', $resource->ID, home_url() );
														$target = '';
													}
													?>
													<li>
														<a href="<?php echo esc_url( $link ); ?>"<?php echo $target; ?>>
															<i class="fa <?php echo esc_attr( $file_icon ); ?>"></i>
															<?php echo esc_html( $resource->post_title ); ?>
															<?php if ( $file_info ) : ?>
																<small class="text-muted">(<?php echo strtoupper( $file_info['ext'] ); ?>)</small>
															<?php endif; ?>
														</a>
													</li>
												<?php endforeach; ?>
											</ul>
											<a href="<?php echo home_url( '/resource-center' ); ?>" class="btn btn-primary btn-block mt-2">
												<i class="fa fa-folder-open"></i> View All Resources
											</a>
										<?php else : ?>
											<p class="text-muted">No resources available yet. Check back soon!</p>
											<a href="<?php echo home_url( '/resource-center' ); ?>" class="btn btn-outline-primary btn-block">
												<i class="fa fa-folder-open"></i> Go to Resource Center
											</a>
										<?php endif; ?>
									</div>
								</div>

								<!-- Support -->
								<div class="card mt-3">
									<div class="card-header">
										<h3><i class="fa fa-question-circle"></i> Need Help?</h3>
									</div>
									<div class="card-body">
										<p>Contact NMDA Logo Program staff for assistance.</p>
										<p>
											<strong>Email:</strong> logoprogram@nmda.gov<br>
											<strong>Phone:</strong> (505) 555-1234
										</p>
										<a href="#" class="btn btn-sm btn-primary btn-block">
											<i class="fa fa-envelope"></i> Send Message
										</a>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>

				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php
get_footer();
