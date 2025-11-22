<?php
/**
 * Template Name: Registration Page
 * Custom branded registration page for NMDA Portal
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

// Handle registration form submission
$registration_errors = array();
$success_message = '';

if ( isset( $_POST['nmda_register_submit'] ) ) {
	// Verify nonce
	if ( ! isset( $_POST['nmda_register_nonce'] ) || ! wp_verify_nonce( $_POST['nmda_register_nonce'], 'nmda_register_action' ) ) {
		$registration_errors[] = 'Security verification failed. Please try again.';
	} else {
		// Sanitize and validate inputs
		$username = sanitize_user( $_POST['nmda_username'] );
		$email = sanitize_email( $_POST['nmda_email'] );
		$password = $_POST['nmda_password'];
		$password_confirm = $_POST['nmda_password_confirm'];
		$first_name = sanitize_text_field( $_POST['nmda_first_name'] );
		$last_name = sanitize_text_field( $_POST['nmda_last_name'] );
		$phone = sanitize_text_field( $_POST['nmda_phone'] );
		$terms_accepted = isset( $_POST['nmda_terms'] ) ? true : false;

		// Validation
		if ( empty( $username ) ) {
			$registration_errors[] = 'Username is required.';
		} elseif ( strlen( $username ) < 4 ) {
			$registration_errors[] = 'Username must be at least 4 characters long.';
		} elseif ( username_exists( $username ) ) {
			$registration_errors[] = 'This username is already taken. Please choose another.';
		}

		if ( empty( $email ) ) {
			$registration_errors[] = 'Email address is required.';
		} elseif ( ! is_email( $email ) ) {
			$registration_errors[] = 'Please enter a valid email address.';
		} elseif ( email_exists( $email ) ) {
			$registration_errors[] = 'This email address is already registered. Please <a href="' . home_url( '/login/' ) . '">log in</a> instead.';
		}

		if ( empty( $first_name ) ) {
			$registration_errors[] = 'First name is required.';
		}

		if ( empty( $last_name ) ) {
			$registration_errors[] = 'Last name is required.';
		}

		if ( empty( $password ) ) {
			$registration_errors[] = 'Password is required.';
		} elseif ( strlen( $password ) < 8 ) {
			$registration_errors[] = 'Password must be at least 8 characters long.';
		}

		if ( $password !== $password_confirm ) {
			$registration_errors[] = 'Passwords do not match.';
		}

		if ( ! $terms_accepted ) {
			$registration_errors[] = 'You must accept the Terms of Service to register.';
		}

		// If no errors, create the user
		if ( empty( $registration_errors ) ) {
			$user_data = array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'role'         => 'subscriber', // Default role, can be changed after business application
			);

			$user_id = wp_insert_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				$registration_errors[] = 'Registration failed: ' . $user_id->get_error_message();
			} else {
				// Store phone number as user meta
				if ( ! empty( $phone ) ) {
					update_user_meta( $user_id, 'phone', $phone );
				}

				// Send welcome email
				$to = $email;
				$subject = 'Welcome to NMDA Portal - Account Created';
				$message = "Hello $first_name,\n\n";
				$message .= "Thank you for registering with the New Mexico Logo Program!\n\n";
				$message .= "Your account has been created successfully. You can now log in and start your business application.\n\n";
				$message .= "Username: $username\n";
				$message .= "Login URL: " . home_url( '/login/' ) . "\n\n";
				$message .= "IMPORTANT: Your business application will require approval by NMDA staff before you can access all program benefits.\n\n";
				$message .= "Next Steps:\n";
				$message .= "1. Log in to your account\n";
				$message .= "2. Complete your business application\n";
				$message .= "3. Submit required documents\n";
				$message .= "4. Wait for NMDA staff to review and approve your application\n";
				$message .= "5. Once approved, you'll receive access to logos, reimbursements, and other program resources\n\n";
				$message .= "If you have any questions, please contact us at info@nmda.gov or call (505) 646-3007.\n\n";
				$message .= "Best regards,\n";
				$message .= "New Mexico Department of Agriculture";

				$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

				wp_mail( $to, $subject, $message, $headers );

				// Redirect to login with success message
				wp_redirect( home_url( '/login/?success=registered' ) );
				exit;
			}
		}
	}
}

get_header();
?>

<div class="nmda-register-page">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-8 col-lg-7">

				<div class="register-card">
					<!-- Header ---->
					<div class="register-header text-center mb-4">
						<h1 class="register-title">Create Account</h1>
						<p class="register-subtitle">Join the New Mexico Logo Program</p>
					</div>

					<!-- Error Messages -->
					<?php if ( ! empty( $registration_errors ) ) : ?>
						<div class="alert alert-danger" role="alert">
							<strong>Registration Failed</strong>
							<ul class="mb-0 mt-2">
								<?php foreach ( $registration_errors as $error ) : ?>
									<li><?php echo wp_kses_post( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<!-- Registration Form -->
					<form method="post" action="" class="register-form" id="nmda-register-form">
						<?php wp_nonce_field( 'nmda_register_action', 'nmda_register_nonce' ); ?>

						<!-- Account Information Section -->
						<fieldset class="mb-4">
							<legend class="h5 mb-3">Account Information</legend>

							<!-- Username Field -->
							<div class="form-group mb-3">
								<label for="nmda_username" class="form-label">
									Username <span class="text-danger">*</span>
								</label>
								<input
									type="text"
									class="form-control form-control-lg"
									id="nmda_username"
									name="nmda_username"
									required
									autofocus
									minlength="4"
									placeholder="Choose a username"
									value="<?php echo isset( $_POST['nmda_username'] ) ? esc_attr( $_POST['nmda_username'] ) : ''; ?>"
								>
								<small class="form-text text-muted">At least 4 characters, letters, numbers, and underscores only.</small>
							</div>

							<!-- Email Field -->
							<div class="form-group mb-3">
								<label for="nmda_email" class="form-label">
									Email Address <span class="text-danger">*</span>
								</label>
								<input
									type="email"
									class="form-control form-control-lg"
									id="nmda_email"
									name="nmda_email"
									required
									placeholder="your.email@example.com"
									value="<?php echo isset( $_POST['nmda_email'] ) ? esc_attr( $_POST['nmda_email'] ) : ''; ?>"
								>
								<small class="form-text text-muted">We'll send login instructions to this address.</small>
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
										minlength="8"
										placeholder="Create a strong password"
									>
									<button
										type="button"
										class="btn btn-sm btn-link password-toggle"
										aria-label="Toggle password visibility"
										data-target="nmda_password"
										style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);"
									>
										<i class="fa fa-eye"></i>
									</button>
								</div>
								<small class="form-text text-muted">At least 8 characters. Mix of letters, numbers, and symbols recommended.</small>
							</div>

							<!-- Password Confirmation Field -->
							<div class="form-group mb-3">
								<label for="nmda_password_confirm" class="form-label">
									Confirm Password <span class="text-danger">*</span>
								</label>
								<div class="password-field-wrapper position-relative">
									<input
										type="password"
										class="form-control form-control-lg"
										id="nmda_password_confirm"
										name="nmda_password_confirm"
										required
										minlength="8"
										placeholder="Re-enter your password"
									>
									<button
										type="button"
										class="btn btn-sm btn-link password-toggle"
										aria-label="Toggle password visibility"
										data-target="nmda_password_confirm"
										style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);"
									>
										<i class="fa fa-eye"></i>
									</button>
								</div>
							</div>
						</fieldset>

						<!-- Personal Information Section -->
						<fieldset class="mb-4">
							<legend class="h5 mb-3">Personal Information</legend>

							<div class="row">
								<!-- First Name -->
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="nmda_first_name" class="form-label">
											First Name <span class="text-danger">*</span>
										</label>
										<input
											type="text"
											class="form-control form-control-lg"
											id="nmda_first_name"
											name="nmda_first_name"
											required
											placeholder="First name"
											value="<?php echo isset( $_POST['nmda_first_name'] ) ? esc_attr( $_POST['nmda_first_name'] ) : ''; ?>"
										>
									</div>
								</div>

								<!-- Last Name -->
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="nmda_last_name" class="form-label">
											Last Name <span class="text-danger">*</span>
										</label>
										<input
											type="text"
											class="form-control form-control-lg"
											id="nmda_last_name"
											name="nmda_last_name"
											required
											placeholder="Last name"
											value="<?php echo isset( $_POST['nmda_last_name'] ) ? esc_attr( $_POST['nmda_last_name'] ) : ''; ?>"
										>
									</div>
								</div>
							</div>

							<!-- Phone -->
							<div class="form-group mb-3">
								<label for="nmda_phone" class="form-label">
									Phone Number
								</label>
								<input
									type="tel"
									class="form-control form-control-lg"
									id="nmda_phone"
									name="nmda_phone"
									placeholder="(505) 123-4567"
									value="<?php echo isset( $_POST['nmda_phone'] ) ? esc_attr( $_POST['nmda_phone'] ) : ''; ?>"
								>
								<small class="form-text text-muted">Optional. We may contact you about your application.</small>
							</div>
						</fieldset>

						<!-- Terms and Conditions -->
						<div class="form-group mb-4">
							<div class="form-check">
								<input
									type="checkbox"
									class="form-check-input"
									id="nmda_terms"
									name="nmda_terms"
									required
									value="1"
								>
								<label class="form-check-label" for="nmda_terms">
									I agree to the <a href="<?php echo home_url( '/terms-of-service/' ); ?>" target="_blank">Terms of Service</a>
									and <a href="<?php echo home_url( '/privacy-policy/' ); ?>" target="_blank">Privacy Policy</a>
									<span class="text-danger">*</span>
								</label>
							</div>
						</div>

						<!-- Submit Button -->
						<div class="form-group mb-3">
							<button type="submit" name="nmda_register_submit" class="btn btn-primary btn-lg w-100">
								<i class="fa fa-user-plus"></i> Create Account
							</button>
						</div>

						<!-- Login Link -->
						<div class="text-center mt-4">
							<p class="mb-0">
								Already have an account?
								<a href="<?php echo home_url( '/login/' ); ?>" class="login-link">
									<strong>Log In</strong>
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
