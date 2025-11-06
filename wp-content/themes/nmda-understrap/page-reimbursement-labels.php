<?php
/**
 * Template Name: Labels Reimbursement
 * Template for submitting product labels reimbursement requests
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

// Check if user has approved businesses
$user_id    = get_current_user_id();
$businesses = nmda_get_user_businesses( $user_id );

// Filter to only approved businesses
$approved_businesses = array();
foreach ( $businesses as $business ) {
	$business_post = get_post( $business['business_id'] );
	if ( $business_post && $business_post->post_status === 'publish' ) {
		$approved_businesses[] = $business;
	}
}

get_header();
?>

<div class="wrapper" id="page-wrapper">
	<div class="container" id="content">
		<div class="row">
			<div class="col-md-12">

				<div class="reimbursement-header">
					<h1><i class="fa fa-dollar"></i> Labels Reimbursement</h1>
					<p class="lead">Request reimbursement for product label design and printing costs</p>
				</div>

				<?php if ( empty( $approved_businesses ) ) : ?>
					<div class="alert alert-warning mt-4">
						<h4><i class="fa fa-exclamation-triangle"></i> No Approved Business</h4>
						<p>You must have an approved business to submit reimbursement requests.</p>
						<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-primary">Return to Dashboard</a>
					</div>
				<?php else : ?>
					<?php echo nmda_render_labels_reimbursement_form( $approved_businesses ); ?>
				<?php endif; ?>

			</div>
		</div>
	</div>
</div>

<?php
get_footer();
