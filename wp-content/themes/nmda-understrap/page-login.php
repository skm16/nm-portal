<?php
/**
 * Template Name: Login Page
 * Custom branded login page for NMDA Portal
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

// Handle login form submission
$login_errors = array();
$success_message = '';

if ( isset( $_POST['nmda_login_submit'] ) ) {
	// Verify nonce
	if ( ! isset( $_POST['nmda_login_nonce'] ) || ! wp_verify_nonce( $_POST['nmda_login_nonce'], 'nmda_login_action' ) ) {
		$login_errors[] = 'Security verification failed. Please try again.';
	} else {
		$username = sanitize_user( $_POST['nmda_username'] );
		$password = $_POST['nmda_password'];
		$remember = isset( $_POST['nmda_remember'] ) ? true : false;

		// Attempt login
		$creds = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$user = wp_signon( $creds, false );

		if ( is_wp_error( $user ) ) {
			$login_errors[] = 'Invalid username or password.';
		} else {
			// Success - redirect
			$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : home_url( '/dashboard/' );
			wp_redirect( $redirect_to );
			exit;
		}
	}
}

get_header();
?>

<div class="nmda-login-page">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-6 col-lg-5">

				<div class="login-card">
					<!-- Logo/Branding -->
					<div class="login-header text-center mb-4">
						<h1 class="login-title">Log In</h1>
						<p class="login-subtitle">Access your NMDA Portal account</p>
					</div>

					<!-- Error Messages -->
					<?php if ( ! empty( $login_errors ) ) : ?>
						<div class="alert alert-danger" role="alert">
							<strong>Login Failed</strong>
							<ul class="mb-0 mt-2">
								<?php foreach ( $login_errors as $error ) : ?>
									<li><?php echo esc_html( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<!-- Success Message (for password reset, etc.) -->
					<?php if ( isset( $_GET['success'] ) ) : ?>
						<div class="alert alert-success" role="alert">
							<?php
							switch ( $_GET['success'] ) {
								case 'password_reset':
									echo 'Your password has been reset successfully. Please log in.';
									break;
								case 'registered':
									echo '<strong>Registration successful!</strong><br>';
									echo 'Your account has been created. After logging in, complete your business application.<br>';
									echo '<small class="text-muted">Note: Your application will require NMDA staff approval before accessing program benefits.</small>';
									break;
								default:
									echo 'Success! Please log in to continue.';
							}
							?>
						</div>
					<?php endif; ?>

					<!-- Login Form -->
					<form method="post" action="" class="login-form" id="nmda-login-form">
						<?php wp_nonce_field( 'nmda_login_action', 'nmda_login_nonce' ); ?>

						<!-- Username/Email Field -->
						<div class="form-group mb-3">
							<label for="nmda_username" class="form-label">
								Username or Email Address <span class="text-danger">*</span>
							</label>
							<input
								type="text"
								class="form-control form-control-lg"
								id="nmda_username"
								name="nmda_username"
								required
								autofocus
								placeholder="Enter your username or email"
								value="<?php echo isset( $_POST['nmda_username'] ) ? esc_attr( $_POST['nmda_username'] ) : ''; ?>"
							>
						</div>

						<!-- Password Field -->
						<div class="form-group mb-3">
							<label for="nmda_password" class="form-label">
								Password <span class="text-danger">*</span>
							</label>
							<div class="password-field-wrapper position-relative">
								<input
									type="password"
									class="form-control form-control-lg"
									id="nmda_password"
									name="nmda_password"
									required
									placeholder="Enter your password"
								>
								<button
									type="button"
									class="btn btn-sm btn-link password-toggle"
									aria-label="Toggle password visibility"
									style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);"
								>
									<i class="fa fa-eye" id="password-toggle-icon"></i>
								</button>
							</div>
						</div>

						<!-- Remember Me & Forgot Password -->
						<div class="form-group d-flex justify-content-between align-items-center mb-4">
							<div class="form-check">
								<input
									type="checkbox"
									class="form-check-input"
									id="nmda_remember"
									name="nmda_remember"
									value="1"
								>
								<label class="form-check-label" for="nmda_remember">
									Remember Me
								</label>
							</div>
							<a href="<?php echo home_url( '/forgot-password/' ); ?>" class="forgot-password-link">
								Forgot Password?
							</a>
						</div>

						<!-- Submit Button -->
						<div class="form-group mb-3">
							<button type="submit" name="nmda_login_submit" class="btn btn-primary btn-lg w-100">
								<i class="fa fa-sign-in-alt"></i> Log In
							</button>
						</div>

						<!-- Register Link -->
						<div class="text-center mt-4">
							<p class="mb-0">
								Don't have an account?
								<a href="<?php echo home_url( '/register/' ); ?>" class="register-link">
									<strong>Create Account</strong>
								</a>
							</p>
						</div>
					</form>

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
?>
