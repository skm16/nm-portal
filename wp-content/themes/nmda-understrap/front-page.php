<?php
/**
 * Front Page Template - NMDA Portal Homepage
 *
 * Handles homepage logic:
 * - Logged-in users: Redirect to dashboard
 * - Non-logged-in users: Show landing page with login/register
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Redirect logged-in users to dashboard
if ( is_user_logged_in() ) {
	$dashboard_page = get_page_by_path( 'dashboard' );
	if ( $dashboard_page ) {
		wp_redirect( get_permalink( $dashboard_page->ID ) );
		exit;
	} else {
		// Fallback to home if dashboard doesn't exist
		wp_redirect( home_url( '/dashboard/' ) );
		exit;
	}
}

// Show landing page for non-logged-in users
get_header();
?>

<div class="nmda-landing-page">

	<!-- Hero Section -->
	<section class="hero-section">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-7">
					<h1 class="hero-title">Welcome to the New Mexico Logo Program</h1>
					<p class="hero-subtitle lead">
						Join New Mexico's premier agricultural branding initiative.
						Promote your locally grown products with the official New Mexico Grown with Tradition and Taste the Tradition logos.
					</p>

					<div class="cta-buttons mt-4">
						<a href="<?php echo home_url( '/register/' ); ?>" class="btn btn-primary btn-lg me-3">
							<i class="fa fa-user-plus"></i> Create Account
						</a>
						<a href="<?php echo home_url( '/login/' ); ?>" class="btn btn-outline-primary btn-lg">
							<i class="fa fa-sign-in"></i> Log In
						</a>
					</div>
				</div>
				<div class="col-lg-5 text-center">
					<div class="hero-image">
						<!-- Placeholder for NMDA Logo or program image -->
						<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/nmda-logo-placeholder.png"
						     alt="New Mexico Logo Program"
						     class="img-fluid"
						     style="max-width: 400px;"
						     onerror="this.style.display='none'">
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Program Benefits Section -->
	<section class="benefits-section py-5 bg-light">
		<div class="container">
			<h2 class="text-center mb-5">Program Benefits</h2>
			<div class="row">
				<div class="col-md-4 mb-4">
					<div class="benefit-card text-center">
						<div class="benefit-icon mb-3">
							<i class="fa fa-certificate fa-3x" style="color: var(--nmda-red, #8b0c12);"></i>
						</div>
						<h3 class="h5">Official Certification</h3>
						<p>
							Use the official New Mexico logos on your products, packaging, and marketing materials.
						</p>
					</div>
				</div>
				<div class="col-md-4 mb-4">
					<div class="benefit-card text-center">
						<div class="benefit-icon mb-3">
							<i class="fa fa-dollar-sign fa-3x" style="color: var(--nmda-gold, #f5be29);"></i>
						</div>
						<h3 class="h5">Cost-Share Reimbursements</h3>
						<p>
							Access funding for lead generation, advertising, and product labeling expenses.
						</p>
					</div>
				</div>
				<div class="col-md-4 mb-4">
					<div class="benefit-card text-center">
						<div class="benefit-icon mb-3">
							<i class="fa fa-users fa-3x" style="color: var(--nmda-brown-dark, #512c1d);"></i>
						</div>
						<h3 class="h5">Statewide Promotion</h3>
						<p>
							Be listed in our public business directory and reach customers across New Mexico.
						</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- How It Works Section -->
	<section class="how-it-works-section py-5">
		<div class="container">
			<h2 class="text-center mb-5">How It Works</h2>
			<div class="row">
				<div class="col-md-3 mb-4">
					<div class="step-card text-center">
						<div class="step-number mb-3">
							<span class="badge bg-primary" style="font-size: 2rem; padding: 1rem 1.5rem; border-radius: 50%;">1</span>
						</div>
						<h4 class="h6">Create Account</h4>
						<p class="small">Register for a free account to get started.</p>
					</div>
				</div>
				<div class="col-md-3 mb-4">
					<div class="step-card text-center">
						<div class="step-number mb-3">
							<span class="badge bg-primary" style="font-size: 2rem; padding: 1rem 1.5rem; border-radius: 50%;">2</span>
						</div>
						<h4 class="h6">Submit Application</h4>
						<p class="small">Complete your business application with product details.</p>
					</div>
				</div>
				<div class="col-md-3 mb-4">
					<div class="step-card text-center">
						<div class="step-number mb-3">
							<span class="badge bg-primary" style="font-size: 2rem; padding: 1rem 1.5rem; border-radius: 50%;">3</span>
						</div>
						<h4 class="h6">Get Approved</h4>
						<p class="small">NMDA staff review and approve your application.</p>
					</div>
				</div>
				<div class="col-md-3 mb-4">
					<div class="step-card text-center">
						<div class="step-number mb-3">
							<span class="badge bg-primary" style="font-size: 2rem; padding: 1rem 1.5rem; border-radius: 50%;">4</span>
						</div>
						<h4 class="h6">Start Using Logos</h4>
						<p class="small">Download logos and access program resources.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Call to Action Section -->
	<section class="cta-section py-5" style="background-color: var(--nmda-brown-dark, #512c1d); color: white;">
		<div class="container text-center">
			<h2 class="mb-4">Ready to Join?</h2>
			<p class="lead mb-4">
				Start your application today and become part of New Mexico's agricultural legacy.
			</p>
			<a href="<?php echo home_url( '/register/' ); ?>" class="btn btn-light btn-lg">
				<i class="fa fa-rocket"></i> Get Started Now
			</a>
		</div>
	</section>

	<!-- Contact Information Section -->
	<section class="contact-section py-5 bg-light">
		<div class="container">
			<div class="row">
				<div class="col-md-6">
					<h3>Questions?</h3>
					<p>
						Contact the New Mexico Department of Agriculture for more information about the Logo Program.
					</p>
					<ul class="list-unstyled">
						<li class="mb-2">
							<i class="fa fa-phone"></i>
							<strong>Phone:</strong> (505) 646-3007
						</li>
						<li class="mb-2">
							<i class="fa fa-envelope"></i>
							<strong>Email:</strong> <a href="mailto:info@nmda.gov">info@nmda.gov</a>
						</li>
						<li class="mb-2">
							<i class="fa fa-map-marker-alt"></i>
							<strong>Address:</strong> 3190 S. Espina, Las Cruces, NM 88003
						</li>
					</ul>
				</div>
				<div class="col-md-6">
					<h3>Explore Our Directory</h3>
					<p>
						Browse businesses that are already part of the New Mexico Logo Program.
					</p>
					<a href="<?php echo get_post_type_archive_link( 'nmda_business' ); ?>" class="btn btn-primary">
						<i class="fa fa-search"></i> View Business Directory
					</a>
				</div>
			</div>
		</div>
	</section>

</div>

<?php
get_footer();
