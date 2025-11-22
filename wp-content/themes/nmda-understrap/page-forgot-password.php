<?php
/**
 * Template Name: Forgot Password Page
 * Custom branded password reset request page for NMDA Portal
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

// Handle password reset request
$reset_errors = array();
$success_message = '';

if ( isset( $_POST['nmda_reset_submit'] ) ) {
	// Verify nonce
	if ( ! isset( $_POST['nmda_reset_nonce'] ) || ! wp_verify_nonce( $_POST['nmda_reset_nonce'], 'nmda_reset_action' ) ) {
		$reset_errors[] = 'Security verification failed. Please try again.';
	} else {
		$user_login = sanitize_text_field( $_POST['nmda_user_login'] );

		if ( empty( $user_login ) ) {
			$reset_errors[] = 'Please enter your username or email address.';
		} else {
			// Check if user exists
			if ( strpos( $user_login, '@' ) ) {
				// Email provided
				$user = get_user_by( 'email', $user_login );
			} else {
				// Username provided
				$user = get_user_by( 'login', $user_login );
			}

			if ( ! $user ) {
				// Don't reveal if user exists or not for security
				$success_message = 'If an account exists with that username or email, you will receive a password reset link shortly.';
			} else {
				// Generate reset key
				$reset_key = get_password_reset_key( $user );

				if ( is_wp_error( $reset_key ) ) {
					$reset_errors[] = 'Unable to generate password reset link. Please try again.';
				} else {
					// Send reset email
					$reset_url = home_url( '/reset-password/?key=' . $reset_key . '&login=' . rawurlencode( $user->user_login ) );

					$to = $user->user_email;
					$subject = 'Password Reset Request - NMDA Portal';
					$message = "Hello,\n\n";
					$message .= "You have requested to reset your password for your NMDA Portal account.\n\n";
					$message .= "Username: " . $user->user_login . "\n";
					$message .= "Email: " . $user->user_email . "\n\n";
					$message .= "To reset your password, please click the link below:\n";
					$message .= $reset_url . "\n\n";
					$message .= "This link will expire in 24 hours.\n\n";
					$message .= "If you did not request this password reset, please ignore this email.\n\n";
					$message .= "Best regards,\n";
					$message .= "New Mexico Department of Agriculture";

					$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

					$sent = wp_mail( $to, $subject, $message, $headers );

					if ( $sent ) {
						$success_message = 'If an account exists with that username or email, you will receive a password reset link shortly.';
					} else {
						$reset_errors[] = 'Unable to send password reset email. Please contact support.';
					}
				}
			}
		}
	}
}

get_header();
?>

<div class="nmda-forgot-password-page">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-6 col-lg-5">

				<div class="forgot-password-card">
					<!-- Header -->
					<div class="forgot-password-header text-center mb-4">
						<h1 class="forgot-password-title">Reset Password</h1>
						<p class="forgot-password-subtitle">Enter your username or email to receive a reset link</p>
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
					<?php endif; ?>

					<!-- Success Message -->
					<?php if ( ! empty( $success_message ) ) : ?>
						<div class="alert alert-success" role="alert">
							<?php echo esc_html( $success_message ); ?>
						</div>
						<div class="text-center mt-4">
							<p>Didn't receive the email?</p>
							<ul class="list-unstyled small text-muted">
								<li>Check your spam/junk folder</li>
								<li>Make sure you entered the correct username or email</li>
								<li>Wait a few minutes for the email to arrive</li>
							</ul>
						</div>
					<?php endif; ?>

					<!-- Password Reset Form -->
					<?php if ( empty( $success_message ) ) : ?>
						<form method="post" action="" class="forgot-password-form" id="nmda-forgot-password-form">
							<?php wp_nonce_field( 'nmda_reset_action', 'nmda_reset_nonce' ); ?>

							<!-- Username/Email Field -->
							<div class="form-group mb-4">
								<label for="nmda_user_login" class="form-label">
									Username or Email Address <span class="text-danger">*</span>
								</label>
								<input
									type="text"
									class="form-control form-control-lg"
									id="nmda_user_login"
									name="nmda_user_login"
									required
									autofocus
									placeholder="Enter your username or email"
									value="<?php echo isset( $_POST['nmda_user_login'] ) ? esc_attr( $_POST['nmda_user_login'] ) : ''; ?>"
								>
								<small class="form-text text-muted">
									We'll send a password reset link to your email address.
								</small>
							</div>

							<!-- Submit Button -->
							<div class="form-group mb-3">
								<button type="submit" name="nmda_reset_submit" class="btn btn-primary btn-lg w-100">
									<i class="fa fa-envelope"></i> Send Reset Link
								</button>
							</div>

							<!-- Back to Login -->
							<div class="text-center mt-4">
								<p class="mb-0">
									Remember your password?
									<a href="<?php echo home_url( '/login/' ); ?>" class="login-link">
										<strong>Log In</strong>
									</a>
								</p>
							</div>
						</form>
					<?php else : ?>
						<!-- Show login link after success -->
						<div class="text-center mt-4">
							<a href="<?php echo home_url( '/login/' ); ?>" class="btn btn-primary btn-lg">
								<i class="fa fa-sign-in-alt"></i> Return to Login
							</a>
						</div>
					<?php endif; ?>

					<!-- Back to Home -->
					<div class="text-center mt-4 pt-3 border-top">
						<a href="<?php echo home_url( '/' ); ?>" class="btn btn-link">
							<i class="fa fa-arrow-left"></i> Back to Home
						</a>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<?php
get_footer();
