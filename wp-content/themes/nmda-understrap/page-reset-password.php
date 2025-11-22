<?php
/**
 * Template Name: Reset Password Page
 * Custom branded password reset page for NMDA Portal
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Redirect if already logged in
if ( is_user_logged_in() ) {
	$dashboard_page = get_page_by_path( 'dashboard' );
	if ( $dashboard_page ) {
		wp_redirect( get_permalink( $dashboard_page->ID ) );
	} else {
		wp_redirect( home_url( '/dashboard/' ) );
	}
	exit;
}

// Get key and login from URL
$reset_key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
$user_login = isset( $_GET['login'] ) ? sanitize_text_field( $_GET['login'] ) : '';

$reset_errors = array();
$success_message = '';
$valid_key = false;
$user = null;

// Validate reset key and user
if ( ! empty( $reset_key ) && ! empty( $user_login ) ) {
	$user = check_password_reset_key( $reset_key, $user_login );

	if ( is_wp_error( $user ) ) {
		if ( $user->get_error_code() === 'expired_key' ) {
			$reset_errors[] = 'This password reset link has expired. Please request a new one.';
		} else {
			$reset_errors[] = 'This password reset link is invalid. Please request a new one.';
		}
	} else {
		$valid_key = true;
	}
} else {
	$reset_errors[] = 'Missing required information. Please use the link from your email.';
}

// Handle password reset form submission
if ( isset( $_POST['nmda_reset_password_submit'] ) && $valid_key ) {
	// Verify nonce
	if ( ! isset( $_POST['nmda_reset_password_nonce'] ) || ! wp_verify_nonce( $_POST['nmda_reset_password_nonce'], 'nmda_reset_password_action' ) ) {
		$reset_errors[] = 'Security verification failed. Please try again.';
	} else {
		$new_password = $_POST['nmda_new_password'];
		$confirm_password = $_POST['nmda_confirm_password'];

		// Validation
		if ( empty( $new_password ) ) {
			$reset_errors[] = 'Password is required.';
		} elseif ( strlen( $new_password ) < 8 ) {
			$reset_errors[] = 'Password must be at least 8 characters long.';
		}

		if ( $new_password !== $confirm_password ) {
			$reset_errors[] = 'Passwords do not match.';
		}

		// If no errors, reset the password
		if ( empty( $reset_errors ) ) {
			reset_password( $user, $new_password );

			// Send confirmation email
			$to = $user->user_email;
			$subject = 'Password Changed - NMDA Portal';
			$message = "Hello " . $user->first_name . ",\n\n";
			$message .= "Your password has been successfully changed.\n\n";
			$message .= "If you did not make this change, please contact us immediately at info@nmda.gov or call (505) 646-3007.\n\n";
			$message .= "You can now log in with your new password:\n";
			$message .= home_url( '/login/' ) . "\n\n";
			$message .= "Best regards,\n";
			$message .= "New Mexico Department of Agriculture";

			$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
			wp_mail( $to, $subject, $message, $headers );

			// Redirect to login with success message
			wp_redirect( home_url( '/login/?success=password_reset' ) );
			exit;
		}
	}
}

get_header();
?>

<div class="nmda-reset-password-page">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-6 col-lg-5">

				<div class="reset-password-card">
					<!-- Header -->
					<div class="reset-password-header text-center mb-4">
						<h1 class="reset-password-title">Reset Password</h1>
						<?php if ( $valid_key ) : ?>
							<p class="reset-password-subtitle">Enter your new password</p>
						<?php else : ?>
							<p class="reset-password-subtitle">Invalid or expired reset link</p>
						<?php endif; ?>
					</div>

					<!-- Error Messages -->
					<?php if ( ! empty( $reset_errors ) ) : ?>
						<div class="alert alert-danger" role="alert">
							<strong>Error</strong>
							<ul class="mb-0 mt-2">
								<?php foreach ( $reset_errors as $error ) : ?>
									<li><?php echo esc_html( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>

						<!-- Show link to request new reset -->
						<?php if ( ! $valid_key ) : ?>
							<div class="text-center mt-4">
								<a href="<?php echo home_url( '/forgot-password/' ); ?>" class="btn btn-primary btn-lg">
									<i class="fa fa-envelope"></i> Request New Reset Link
								</a>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<!-- Password Reset Form -->
					<?php if ( $valid_key ) : ?>
						<form method="post" action="" class="reset-password-form" id="nmda-reset-password-form">
							<?php wp_nonce_field( 'nmda_reset_password_action', 'nmda_reset_password_nonce' ); ?>

							<!-- User Information Display -->
							<div class="alert alert-info mb-4">
								<i class="fa fa-user"></i>
								<strong>Account:</strong> <?php echo esc_html( $user->user_login ); ?>
								<br>
								<small><?php echo esc_html( $user->user_email ); ?></small>
							</div>

							<!-- New Password Field -->
							<div class="form-group mb-3">
								<label for="nmda_new_password" class="form-label">
									New Password <span class="text-danger">*</span>
								</label>
								<div class="password-field-wrapper position-relative">
									<input
										type="password"
										class="form-control form-control-lg"
										id="nmda_new_password"
										name="nmda_new_password"
										required
										minlength="8"
										autofocus
										placeholder="Enter new password"
									>
									<button
										type="button"
										class="btn btn-sm btn-link password-toggle"
										aria-label="Toggle password visibility"
										data-target="nmda_new_password"
										style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);"
									>
										<i class="fa fa-eye"></i>
									</button>
								</div>
								<small class="form-text text-muted">At least 8 characters. Mix of letters, numbers, and symbols recommended.</small>
							</div>

							<!-- Confirm Password Field -->
							<div class="form-group mb-4">
								<label for="nmda_confirm_password" class="form-label">
									Confirm New Password <span class="text-danger">*</span>
								</label>
								<div class="password-field-wrapper position-relative">
									<input
										type="password"
										class="form-control form-control-lg"
										id="nmda_confirm_password"
										name="nmda_confirm_password"
										required
										minlength="8"
										placeholder="Re-enter new password"
									>
									<button
										type="button"
										class="btn btn-sm btn-link password-toggle"
										aria-label="Toggle password visibility"
										data-target="nmda_confirm_password"
										style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);"
									>
										<i class="fa fa-eye"></i>
									</button>
								</div>
							</div>

							<!-- Submit Button -->
							<div class="form-group mb-3">
								<button type="submit" name="nmda_reset_password_submit" class="btn btn-primary btn-lg w-100">
									<i class="fa fa-key"></i> Reset Password
								</button>
							</div>
						</form>
					<?php endif; ?>

					<!-- Back to Login -->
					<div class="text-center mt-4">
						<p class="mb-0">
							<a href="<?php echo home_url( '/login/' ); ?>" class="login-link">
								<i class="fa fa-arrow-left"></i> Back to Login
							</a>
						</p>
					</div>

					<!-- Back to Home -->
					<div class="text-center mt-4 pt-3 border-top">
						<a href="<?php echo home_url( '/' ); ?>" class="btn btn-link">
							<i class="fa fa-home"></i> Back to Home
						</a>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<?php
get_footer();
