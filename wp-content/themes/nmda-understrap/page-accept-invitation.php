<?php
/**
 * Template Name: Accept Business Invitation
 * Template for accepting invitations to join a business
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get token from URL
$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
$error_message = '';
$success_message = '';
$invitation_data = null;

if ( $token ) {
	// Verify and get invitation details
	global $wpdb;
	$table = nmda_get_user_business_table();

	// First, try to find invitation in database (for existing users)
	$invitation = $wpdb->get_row( $wpdb->prepare(
		"SELECT ub.*, u.user_email, u.display_name
		FROM $table ub
		LEFT JOIN {$wpdb->users} u ON ub.user_id = u.ID
		WHERE ub.invitation_token = %s
		AND ub.status = 'pending'",
		$token
	) );

	// If not found in database, check transient (for new users)
	if ( ! $invitation ) {
		$transient_data = get_transient( 'nmda_invitation_' . $token );
		if ( $transient_data ) {
			// Convert transient data to object format similar to database result
			$invitation = (object) array(
				'user_id'      => null,
				'business_id'  => $transient_data['business_id'],
				'role'         => $transient_data['role'],
				'invited_by'   => $transient_data['invited_by'],
				'user_email'   => $transient_data['email'],
				'expires_at'   => $transient_data['expires_at'],
			);
		}
	}

	if ( $invitation ) {
		// Check if invitation has expired using expires_at column
		$current_time = current_time( 'mysql' );

		if ( isset( $invitation->expires_at ) && $invitation->expires_at && $current_time > $invitation->expires_at ) {
			$error_message = 'This invitation has expired. Please contact the business owner for a new invitation.';
		} else {
			// Get business details
			$business = get_post( $invitation->business_id );

			if ( ! $business || $business->post_status !== 'publish' ) {
				$error_message = 'The business associated with this invitation no longer exists.';
			} else {
				$invitation_data = array(
					'business_name' => $business->post_title,
					'business_id'   => $business->ID,
					'role'          => $invitation->role,
					'email'         => $invitation->user_email,
					'invited_by_id' => $invitation->invited_by,
					'token'         => $token,
				);

				// Get inviter info
				if ( $invitation->invited_by ) {
					$inviter = get_userdata( $invitation->invited_by );
					if ( $inviter ) {
						$invitation_data['invited_by_name'] = $inviter->display_name;
					}
				}
			}
		}
	} else {
		$error_message = 'Invalid or already accepted invitation. If you believe this is an error, please contact the business owner.';
	}
}

// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['accept_invitation'] ) ) {
	// Verify nonce
	if ( ! isset( $_POST['invitation_nonce'] ) || ! wp_verify_nonce( $_POST['invitation_nonce'], 'accept_invitation_' . $token ) ) {
		$error_message = 'Security verification failed. Please try again.';
	} else {
		// Accept invitation
		$result = nmda_accept_invitation( $token );

		if ( is_wp_error( $result ) ) {
			// Handle error
			$error_message = $result->get_error_message();
		} else {
			// Handle success - $result is true
			$success_message = 'Invitation accepted successfully! You now have access to ' . esc_html( $invitation_data['business_name'] ) . '.';

			// Redirect to dashboard after 3 seconds
			header( 'refresh:3;url=' . home_url( '/dashboard' ) );
		}
	}
}

get_header();
?>

<div class="wrapper" id="page-wrapper">
	<div class="container" id="content">
		<div class="row justify-content-center mt-5 mb-5">
			<div class="col-md-8 col-lg-6">

				<?php if ( $success_message ) : ?>
					<!-- Success Message -->
					<div class="card">
						<div class="card-body text-center py-5">
							<i class="fa fa-check-circle text-success" style="font-size: 64px;"></i>
							<h2 class="mt-4">Invitation Accepted!</h2>
							<p class="lead"><?php echo esc_html( $success_message ); ?></p>
							<p class="text-muted">Redirecting to your dashboard...</p>
							<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-primary mt-3">
								<i class="fa fa-dashboard"></i> Go to Dashboard Now
							</a>
						</div>
					</div>

				<?php elseif ( $error_message ) : ?>
					<!-- Error Message -->
					<div class="card">
						<div class="card-body text-center py-5">
							<i class="fa fa-exclamation-triangle text-danger" style="font-size: 64px;"></i>
							<h2 class="mt-4">Invitation Error</h2>
							<p class="lead"><?php echo esc_html( $error_message ); ?></p>
							<a href="<?php echo home_url( '/' ); ?>" class="btn btn-secondary mt-3">
								<i class="fa fa-home"></i> Return to Home
							</a>
						</div>
					</div>

				<?php elseif ( ! $token ) : ?>
					<!-- No Token -->
					<div class="card">
						<div class="card-body text-center py-5">
							<i class="fa fa-envelope-open text-muted" style="font-size: 64px;"></i>
							<h2 class="mt-4">Business Invitation</h2>
							<p class="lead">Please use the invitation link from your email to accept a business invitation.</p>
							<a href="<?php echo home_url( '/' ); ?>" class="btn btn-secondary mt-3">
								<i class="fa fa-home"></i> Return to Home
							</a>
						</div>
					</div>

				<?php elseif ( $invitation_data ) : ?>
					<!-- Valid Invitation - Show Acceptance Form -->
					<div class="card">
						<div class="card-header text-center bg-primary text-white">
							<h3 class="mb-0"><i class="fa fa-envelope-open"></i> You're Invited!</h3>
						</div>
						<div class="card-body">
							<div class="invitation-details text-center mb-4">
								<h4 class="mb-3"><?php echo esc_html( $invitation_data['business_name'] ); ?></h4>

								<?php if ( isset( $invitation_data['invited_by_name'] ) ) : ?>
									<p class="mb-2">
										<i class="fa fa-user"></i>
										<strong><?php echo esc_html( $invitation_data['invited_by_name'] ); ?></strong> has invited you to join their business
									</p>
								<?php endif; ?>

								<p class="mb-2">
									<i class="fa fa-envelope"></i>
									Invitation sent to: <strong><?php echo esc_html( $invitation_data['email'] ); ?></strong>
								</p>

								<p class="mb-3">
									<i class="fa fa-id-badge"></i>
									Your role will be: <span class="badge badge-primary"><?php echo esc_html( ucfirst( $invitation_data['role'] ) ); ?></span>
								</p>

								<hr>

								<div class="role-permissions text-left">
									<h6><strong>As a <?php echo esc_html( ucfirst( $invitation_data['role'] ) ); ?>, you will be able to:</strong></h6>
									<ul class="mb-0">
										<?php if ( $invitation_data['role'] === 'owner' ) : ?>
											<li>View and edit business profile</li>
											<li>Submit and manage reimbursement applications</li>
											<li>Manage business users and send invitations</li>
											<li>Access all business documents and resources</li>
										<?php elseif ( $invitation_data['role'] === 'manager' ) : ?>
											<li>View and edit business profile</li>
											<li>Submit and manage reimbursement applications</li>
											<li>Access business documents and resources</li>
											<li><em>(Cannot manage users)</em></li>
										<?php else : ?>
											<li>View business profile (read-only)</li>
											<li>View reimbursement history</li>
											<li>Access business resources</li>
											<li><em>(Cannot edit or manage)</em></li>
										<?php endif; ?>
									</ul>
								</div>
							</div>

							<?php if ( is_user_logged_in() ) : ?>
								<!-- User is logged in -->
								<?php
								$current_user = wp_get_current_user();
								if ( $current_user->user_email === $invitation_data['email'] ) :
								?>
									<form method="post" action="">
										<?php wp_nonce_field( 'accept_invitation_' . $token, 'invitation_nonce' ); ?>
										<input type="hidden" name="accept_invitation" value="1">

										<div class="alert alert-success">
											<i class="fa fa-check-circle"></i>
											You're logged in as <strong><?php echo esc_html( $current_user->display_name ); ?></strong>
										</div>

										<div class="text-center">
											<button type="submit" class="btn btn-primary btn-lg">
												<i class="fa fa-check"></i> Accept Invitation
											</button>
											<a href="<?php echo home_url( '/' ); ?>" class="btn btn-outline-secondary btn-lg">
												<i class="fa fa-times"></i> Decline
											</a>
										</div>
									</form>
								<?php else : ?>
									<div class="alert alert-warning">
										<i class="fa fa-exclamation-triangle"></i>
										You're logged in as <strong><?php echo esc_html( $current_user->user_email ); ?></strong>,
										but this invitation was sent to <strong><?php echo esc_html( $invitation_data['email'] ); ?></strong>.
										<br><br>
										Please <a href="<?php echo wp_logout_url( get_permalink() ); ?>">log out</a> and log in with the invited email address.
									</div>
								<?php endif; ?>

							<?php else : ?>
								<!-- User is not logged in -->
								<div class="alert alert-info">
									<i class="fa fa-info-circle"></i>
									To accept this invitation, please either log in to your existing account or create a new account.
								</div>

								<div class="text-center">
									<a href="<?php echo wp_login_url( get_permalink() . '?token=' . urlencode( $token ) ); ?>" class="btn btn-primary btn-lg">
										<i class="fa fa-sign-in"></i> Log In
									</a>
									<a href="<?php echo home_url( '/register/?redirect=' . urlencode( get_permalink() . '?token=' . urlencode( $token ) ) ); ?>" class="btn btn-success btn-lg">
										<i class="fa fa-user-plus"></i> Create Account
									</a>
								</div>

								<p class="text-muted text-center mt-3">
									<small>After logging in or creating an account, you'll be able to accept this invitation.</small>
								</p>
							<?php endif; ?>
						</div>
					</div>

				<?php endif; ?>

			</div>
		</div>
	</div>
</div>

<?php
get_footer();
