<?php
/**
 * Template Name: Manage Business Users
 * Template for managing users associated with a business
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

$user_id = get_current_user_id();

// Get all businesses for this user
$user_business_relationships = nmda_get_user_businesses( $user_id );

// Convert business_ids to post objects and track owner businesses
$user_businesses = array();
$owner_businesses = array();
if ( ! empty( $user_business_relationships ) ) {
	foreach ( $user_business_relationships as $rel ) {
		$business = get_post( $rel['business_id'] );
		if ( $business && $business->post_type === 'nmda_business' && $business->post_status === 'publish' ) {
			// Attach role to business object for later reference
			$business->user_role = $rel['role'];
			$user_businesses[] = $business;

			// Track businesses where user is owner (for default selection)
			if ( $rel['role'] === 'owner' ) {
				$owner_businesses[] = $business;
			}
		}
	}
}

if ( empty( $user_businesses ) ) {
	get_header();
	?>
	<div class="wrapper" id="page-wrapper">
		<div class="container" id="content">
			<div class="row">
				<div class="col-md-12">
					<div class="alert alert-warning mt-5">
						<h4><i class="fa fa-exclamation-triangle"></i> No Business Profile</h4>
						<p>You don't have a business profile yet. Please submit an application first.</p>
						<a href="<?php echo home_url( '/business-application' ); ?>" class="btn btn-primary">
							<i class="fa fa-plus"></i> Submit Application
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	get_footer();
	exit;
}

// Get selected business (from URL param or first owner business, fallback to first business)
$default_business_id = ! empty( $owner_businesses ) ? $owner_businesses[0]->ID : ( ! empty( $user_businesses ) ? $user_businesses[0]->ID : null );
$selected_business_id = isset( $_GET['business_id'] ) ? intval( $_GET['business_id'] ) : $default_business_id;

// Verify user has access to this business
if ( ! nmda_user_can_access_business( $user_id, $selected_business_id ) ) {
	$selected_business_id = $user_businesses[0]->ID;
}

$business = get_post( $selected_business_id );
$user_role = nmda_get_user_business_role( $user_id, $selected_business_id );

// Check if user has permission to manage users (owner only)
$can_manage = ( $user_role === 'owner' ) || current_user_can( 'administrator' );

if ( ! $can_manage ) {
	get_header();
	?>
	<div class="wrapper" id="page-wrapper">
		<div class="container" id="content">
			<div class="row">
				<div class="col-md-12">
					<div class="alert alert-danger mt-5">
						<h4><i class="fa fa-ban"></i> Access Denied</h4>
						<?php
						$is_owner_anywhere = ! empty( $owner_businesses );

						if ( $is_owner_anywhere ) {
							// User is owner of other businesses - show helpful links
							?>
							<p>You cannot manage users for <strong><?php echo esc_html( $business->post_title ); ?></strong>.</p>
							<p>Your role: <strong><?php echo esc_html( ucfirst( $user_role ) ); ?></strong></p>
							<hr>
							<p><strong><i class="fa fa-check-circle"></i> You can manage users for:</strong></p>
							<ul class="mb-3">
								<?php foreach ( $owner_businesses as $owner_biz ) : ?>
									<?php
									$manage_url = add_query_arg( 'business_id', $owner_biz->ID, get_permalink() );
									?>
									<li>
										<a href="<?php echo esc_url( $manage_url ); ?>" class="alert-link">
											<?php echo esc_html( $owner_biz->post_title ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php
						} else {
							// User is not owner of any business
							?>
							<p>Only business owners can manage users.</p>
							<p>Your role for <strong><?php echo esc_html( $business->post_title ); ?></strong>: <strong><?php echo esc_html( ucfirst( $user_role ) ); ?></strong></p>
							<p>Contact an owner of this business if you need to manage users.</p>
							<?php
						}
						?>
						<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-secondary">
							<i class="fa fa-arrow-left"></i> Return to Dashboard
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	get_footer();
	exit;
}

// Get all users for this business
$business_users = nmda_get_business_users( $selected_business_id );

get_header();
?>

<div class="wrapper" id="page-wrapper">
	<div class="container" id="content">

		<!-- Page Header -->
		<div class="dashboard-header">
			<div class="row align-items-center">
				<div class="col-md-8">
					<h1 class="dashboard-title"><i class="fa fa-users"></i> Manage Business Users</h1>
					<p class="dashboard-subtitle">Invite team members and manage access to <?php echo esc_html( $business->post_title ); ?></p>
				</div>
				<div class="col-md-4 text-right">
					<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-secondary">
						<i class="fa fa-arrow-left"></i> Back to Dashboard
					</a>
				</div>
			</div>
		</div>

		<?php if ( count( $user_businesses ) > 1 ) : ?>
			<!-- Business Selector -->
			<div class="card mt-4">
				<div class="card-body">
					<div class="row align-items-center">
						<div class="col-md-3">
							<label for="business-selector" class="mb-0">
								<strong><i class="fa fa-building"></i> Select Business:</strong>
							</label>
						</div>
						<div class="col-md-9">
							<select id="business-selector" class="form-control">
								<?php foreach ( $user_businesses as $biz ) : ?>
									<option value="<?php echo esc_attr( $biz->ID ); ?>" <?php selected( $selected_business_id, $biz->ID ); ?>>
										<?php echo esc_html( $biz->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Invite User Button -->
		<div class="card mt-4">
			<div class="card-body">
				<div class="d-flex justify-content-between align-items-center">
					<div>
						<h5 class="mb-1"><i class="fa fa-user-plus"></i> Invite New User</h5>
						<p class="mb-0 text-muted">Send an invitation to give someone access to this business profile</p>
					</div>
					<button type="button" class="btn btn-primary" id="invite-user-btn">
						<i class="fa fa-envelope"></i> Send Invitation
					</button>
				</div>
			</div>
		</div>

		<!-- Current Users List -->
		<div class="card mt-4">
			<div class="card-header">
				<h5 class="mb-0"><i class="fa fa-users"></i> Current Users (<?php echo count( $business_users ); ?>)</h5>
			</div>
			<div class="card-body">
				<div id="users-list">
					<?php if ( ! empty( $business_users ) ) : ?>
						<div class="table-responsive">
							<table class="table table-hover">
								<thead>
									<tr>
										<th>Name</th>
										<th>Email</th>
										<th>Role</th>
										<th>Status</th>
										<th>Joined</th>
										<th class="text-right">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $business_users as $user_data ) : ?>
										<?php
										$user_obj = get_userdata( $user_data['user_id'] );
										if ( ! $user_obj ) {
											continue;
										}
										$is_current_user = $user_data['user_id'] === $user_id;
										$status = $user_data['status'];
										$role = $user_data['role'];
										?>
										<tr data-user-id="<?php echo esc_attr( $user_data['user_id'] ); ?>" data-relationship-id="<?php echo esc_attr( $user_data['id'] ); ?>">
											<td>
												<strong><?php echo esc_html( $user_obj->display_name ); ?></strong>
												<?php if ( $is_current_user ) : ?>
													<span class="badge badge-info ml-2">You</span>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $user_obj->user_email ); ?></td>
											<td>
												<?php if ( $role === 'owner' && ! $is_current_user ) : ?>
													<select class="form-control form-control-sm role-select" data-user-id="<?php echo esc_attr( $user_data['user_id'] ); ?>">
														<option value="owner" <?php selected( $role, 'owner' ); ?>>Owner</option>
														<option value="manager" <?php selected( $role, 'manager' ); ?>>Manager</option>
														<option value="viewer" <?php selected( $role, 'viewer' ); ?>>Viewer</option>
													</select>
												<?php elseif ( $role !== 'owner' ) : ?>
													<select class="form-control form-control-sm role-select" data-user-id="<?php echo esc_attr( $user_data['user_id'] ); ?>">
														<option value="owner" <?php selected( $role, 'owner' ); ?>>Owner</option>
														<option value="manager" <?php selected( $role, 'manager' ); ?>>Manager</option>
														<option value="viewer" <?php selected( $role, 'viewer' ); ?>>Viewer</option>
													</select>
												<?php else : ?>
													<span class="badge badge-success">Owner</span>
												<?php endif; ?>
											</td>
											<td>
												<?php
												$status_badge = 'secondary';
												$status_text = ucfirst( $status );
												if ( $status === 'active' ) {
													$status_badge = 'success';
												} elseif ( $status === 'pending' ) {
													$status_badge = 'warning';
												} elseif ( $status === 'inactive' ) {
													$status_badge = 'danger';
												}
												?>
												<span class="badge badge-<?php echo esc_attr( $status_badge ); ?>"><?php echo esc_html( $status_text ); ?></span>
											</td>
											<td>
												<?php
												if ( $status === 'pending' && ! empty( $user_data['invited_date'] ) ) {
													echo 'Invited ' . esc_html( date( 'M j, Y', strtotime( $user_data['invited_date'] ) ) );
												} elseif ( ! empty( $user_data['accepted_date'] ) ) {
													echo esc_html( date( 'M j, Y', strtotime( $user_data['accepted_date'] ) ) );
												} else {
													echo '—';
												}
												?>
											</td>
											<td class="text-right">
												<?php if ( $status === 'pending' ) : ?>
													<button type="button" class="btn btn-sm btn-outline-primary resend-invite-btn"
															data-user-id="<?php echo esc_attr( $user_data['user_id'] ); ?>"
															data-email="<?php echo esc_attr( $user_obj->user_email ); ?>">
														<i class="fa fa-envelope"></i> Resend
													</button>
												<?php endif; ?>
												<?php if ( ! $is_current_user ) : ?>
													<button type="button" class="btn btn-sm btn-outline-danger remove-user-btn"
															data-user-id="<?php echo esc_attr( $user_data['user_id'] ); ?>"
															data-name="<?php echo esc_attr( $user_obj->display_name ); ?>">
														<i class="fa fa-trash"></i> Remove
													</button>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<div class="alert alert-info">
							<i class="fa fa-info-circle"></i> No users found. Invite someone to get started!
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Help Info -->
		<div class="card mt-4 mb-5">
			<div class="card-body">
				<h6><i class="fa fa-question-circle"></i> User Roles Explained</h6>
				<ul class="mb-0">
					<li><strong>Owner:</strong> Full access - can manage users, edit profile, and submit reimbursements</li>
					<li><strong>Manager:</strong> Can edit profile and submit reimbursements, but cannot manage users</li>
					<li><strong>Viewer:</strong> Read-only access to business profile and reimbursement history</li>
				</ul>
			</div>
		</div>

	</div>
</div>

<!-- Invite User Modal -->
<div class="modal fade" id="inviteUserModal" tabindex="-1" role="dialog" aria-labelledby="inviteUserModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="inviteUserModalLabel">
					<i class="fa fa-user-plus"></i> Invite User to Business
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form id="invite-user-form">
				<div class="modal-body">
					<input type="hidden" id="business-id-invite" name="business_id" value="<?php echo esc_attr( $selected_business_id ); ?>">

					<div class="form-group">
						<label for="invite-email">Email Address <span class="text-danger">*</span></label>
						<input type="email" class="form-control" id="invite-email" name="email"
							   placeholder="user@example.com" required>
						<small class="form-text text-muted">
							An invitation email will be sent to this address
						</small>
					</div>

					<div class="form-group">
						<label for="invite-role">User Role <span class="text-danger">*</span></label>
						<select class="form-control" id="invite-role" name="role" required>
							<option value="manager">Manager - Can edit profile and submit reimbursements</option>
							<option value="viewer">Viewer - Read-only access</option>
							<option value="owner">Owner - Full access including user management</option>
						</select>
					</div>

					<div class="form-group">
						<label for="invite-message">Personal Message (Optional)</label>
						<textarea class="form-control" id="invite-message" name="message" rows="3"
								  placeholder="Add a personal message to the invitation email..."></textarea>
					</div>

					<div class="alert alert-info mb-0">
						<small>
							<i class="fa fa-info-circle"></i>
							The user will receive an email with a link to accept the invitation and access this business profile.
						</small>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">
						<i class="fa fa-times"></i> Cancel
					</button>
					<button type="submit" class="btn btn-primary" id="send-invite-btn">
						<i class="fa fa-envelope"></i> Send Invitation
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
get_footer();
