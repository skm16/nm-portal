<?php
/**
 * Template Name: Edit Business Profile
 * Template for editing business profile information
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

global $wpdb;
$user_id = get_current_user_id();

// Get all businesses for this user
$user_business_relationships = nmda_get_user_businesses( $user_id );

// Convert business_ids to post objects
$user_businesses = array();
if ( ! empty( $user_business_relationships ) ) {
	foreach ( $user_business_relationships as $rel ) {
		$business = get_post( $rel['business_id'] );
		if ( $business && $business->post_type === 'nmda_business' && $business->post_status === 'publish' ) {
			$user_businesses[] = $business;
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

// Get selected business (from URL param or first business)
$selected_business_id = isset( $_GET['business_id'] ) ? intval( $_GET['business_id'] ) : $user_businesses[0]->ID;

// Verify user has access to this business
if ( ! nmda_user_can_access_business( $user_id, $selected_business_id ) ) {
	$selected_business_id = $user_businesses[0]->ID;
}

$business = get_post( $selected_business_id );
$user_role = nmda_get_user_business_role( $user_id, $selected_business_id );

// DEBUG: Log permission check values
error_log( '=== EDIT PROFILE PERMISSION CHECK ===' );
error_log( 'User ID: ' . $user_id . ' (type: ' . gettype( $user_id ) . ')' );
error_log( 'Business ID: ' . $selected_business_id );
error_log( 'Business post_author: ' . $business->post_author . ' (type: ' . gettype( $business->post_author ) . ')' );
error_log( 'User role from function: ' . var_export( $user_role, true ) );

// Fallback: if no role found but user is post author, treat as owner
if ( ! $user_role && (int) $business->post_author === (int) $user_id ) {
	$user_role = 'owner';
	error_log( 'FALLBACK TRIGGERED: Setting user_role to owner' );
	// Create the relationship in the database
	$relationship_id = nmda_add_user_to_business( $user_id, $selected_business_id, 'owner', null );
	if ( $relationship_id ) {
		error_log( 'SUCCESS: User-business relationship created with ID: ' . $relationship_id );
	} else {
		error_log( 'ERROR: Failed to create user-business relationship. User ID: ' . $user_id . ', Business ID: ' . $selected_business_id );
		error_log( 'Database error (if any): ' . ( ! empty( $wpdb->last_error ) ? $wpdb->last_error : 'No error reported' ) );
	}
}

// Check if user has edit permissions
$can_edit = in_array( $user_role, array( 'owner', 'manager' ) ) || user_can( $user_id, 'administrator' );
error_log( 'Final user_role: ' . var_export( $user_role, true ) );
error_log( 'Can edit: ' . var_export( $can_edit, true ) );
error_log( '======================================' );

if ( ! $can_edit ) {
	error_log( 'ERROR BLOCK EXECUTED - This should not happen if can_edit is true!' );
	get_header();
	?>
	<!-- DEBUG: ERROR BLOCK IS BEING RENDERED - THIS SHOULD NOT APPEAR -->
	<div class="wrapper" id="page-wrapper">
		<div class="container" id="content">
			<div class="row">
				<div class="col-md-12">
					<div class="alert alert-danger mt-5">
						<h4><i class="fa fa-ban"></i> Access Denied</h4>
						<p>You don't have permission to edit this business profile. Only owners and managers can make changes.</p>
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

// Get pending changes
$pending_changes = get_post_meta( $selected_business_id, '_pending_changes', true );
if ( ! is_array( $pending_changes ) ) {
	$pending_changes = array();
}

// Get all product types
$product_types = get_terms( array(
	'taxonomy'   => 'product_type',
	'hide_empty' => false,
) );

get_header();
?>

<div class="wrapper" id="page-wrapper">
	<div class="container" id="content">

		<!-- Page Header -->
		<div class="dashboard-header">
			<div class="row align-items-center">
				<div class="col-md-8">
					<h1 class="dashboard-title"><i class="fa fa-edit"></i> Edit Business Profile</h1>
					<p class="dashboard-subtitle">Update your business information</p>
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

		<?php if ( ! empty( $pending_changes ) ) : ?>
			<!-- Pending Changes Alert -->
			<div class="alert alert-info mt-4">
				<h5><i class="fa fa-clock-o"></i> Pending Changes</h5>
				<p>You have <strong><?php echo count( $pending_changes ); ?></strong> change(s) awaiting admin approval:</p>
				<ul class="mb-0">
					<?php foreach ( $pending_changes as $field => $change ) : ?>
						<li>
							<strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $field ) ) ); ?>:</strong>
							<?php echo esc_html( $change['new_value'] ); ?>
							<span class="text-muted">(Submitted: <?php echo date( 'M j, Y', $change['timestamp'] ); ?>)</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<!-- Edit Form -->
		<form id="profile-edit-form" class="mt-4">
			<input type="hidden" name="business_id" value="<?php echo esc_attr( $selected_business_id ); ?>">
			<input type="hidden" name="action" value="nmda_update_business_profile">
			<?php wp_nonce_field( 'nmda_update_profile_' . $selected_business_id, 'profile_nonce' ); ?>

			<!-- Nav Tabs -->
			<ul class="nav nav-tabs" id="profileTabs" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab">
						<i class="fa fa-info-circle"></i> Business Info
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab">
						<i class="fa fa-map-marker"></i> Contact & Location
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="social-tab" data-toggle="tab" href="#social" role="tab">
						<i class="fa fa-share-alt"></i> Social Media
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="products-tab" data-toggle="tab" href="#products" role="tab">
						<i class="fa fa-shopping-cart"></i> Products
					</a>
				</li>
			</ul>

			<!-- Tab Content -->
			<div class="tab-content" id="profileTabContent">

				<!-- Business Info Tab -->
				<div class="tab-pane fade show active" id="info" role="tabpanel">
					<div class="card">
						<div class="card-body">
							<h4 class="card-title"><i class="fa fa-info-circle"></i> Business Information</h4>

							<div class="row">
								<div class="col-md-6 form-group">
									<label for="business_name">
										Business Name <span class="text-danger">*</span>
										<?php if ( nmda_field_requires_approval( 'business_name' ) ) : ?>
											<span class="badge badge-warning">Requires Approval</span>
										<?php endif; ?>
									</label>
									<input type="text" class="form-control" id="business_name" name="business_name"
										value="<?php echo esc_attr( $business->post_title ); ?>" required>
									<small class="form-text text-muted">Legal business name</small>
								</div>

								<div class="col-md-6 form-group">
									<label for="dba_name">
										DBA Name
										<?php if ( nmda_field_requires_approval( 'dba_name' ) ) : ?>
											<span class="badge badge-warning">Requires Approval</span>
										<?php endif; ?>
									</label>
									<input type="text" class="form-control" id="dba_name" name="dba_name"
										value="<?php echo esc_attr( get_field( 'dba_name', $selected_business_id ) ); ?>">
									<small class="form-text text-muted">Doing Business As</small>
								</div>
							</div>

							<div class="row">
								<div class="col-md-6 form-group">
									<label for="business_phone">Business Phone <span class="text-danger">*</span></label>
									<input type="tel" class="form-control" id="business_phone" name="business_phone"
										value="<?php echo esc_attr( get_field( 'business_phone', $selected_business_id ) ); ?>" required>
								</div>

								<div class="col-md-6 form-group">
									<label for="business_email">Business Email <span class="text-danger">*</span></label>
									<input type="email" class="form-control" id="business_email" name="business_email"
										value="<?php echo esc_attr( get_field( 'business_email', $selected_business_id ) ); ?>" required>
								</div>
							</div>

							<div class="form-group">
								<label for="website">Website</label>
								<input type="url" class="form-control" id="website" name="website"
									value="<?php echo esc_attr( get_field( 'website', $selected_business_id ) ); ?>"
									placeholder="https://">
							</div>

							<div class="form-group">
								<label for="business_description">Business Description</label>
								<textarea class="form-control" id="business_description" name="business_description" rows="4"
									placeholder="Tell us about your business..."><?php echo esc_textarea( $business->post_content ); ?></textarea>
								<small class="form-text text-muted">This will be displayed on your public profile</small>
							</div>

							<div class="form-group">
								<label for="business_hours">Business Hours</label>
								<textarea class="form-control" id="business_hours" name="business_hours" rows="3"
									placeholder="Monday-Friday: 9am-5pm..."><?php echo esc_textarea( get_field( 'business_hours', $selected_business_id ) ); ?></textarea>
							</div>

							<div class="row">
								<div class="col-md-6 form-group">
									<label for="number_of_employees">Number of Employees</label>
									<select class="form-control" id="number_of_employees" name="number_of_employees">
										<option value="">Select...</option>
										<option value="1-5" <?php selected( get_field( 'number_of_employees', $selected_business_id ), '1-5' ); ?>>1-5</option>
										<option value="6-10" <?php selected( get_field( 'number_of_employees', $selected_business_id ), '6-10' ); ?>>6-10</option>
										<option value="11-25" <?php selected( get_field( 'number_of_employees', $selected_business_id ), '11-25' ); ?>>11-25</option>
										<option value="26-50" <?php selected( get_field( 'number_of_employees', $selected_business_id ), '26-50' ); ?>>26-50</option>
										<option value="51+" <?php selected( get_field( 'number_of_employees', $selected_business_id ), '51+' ); ?>>51+</option>
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Contact & Location Tab -->
				<div class="tab-pane fade" id="contact" role="tabpanel">
					<div class="card">
						<div class="card-body">
							<h4 class="card-title"><i class="fa fa-map-marker"></i> Contact & Location</h4>

							<?php
							$primary_address = nmda_get_business_primary_address( $selected_business_id );
							?>

							<h5 class="mt-3">Primary Business Address</h5>

							<div class="form-group">
								<label for="address_street">Street Address <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="address_street" name="address_street"
									value="<?php echo esc_attr( $primary_address['street_address'] ?? '' ); ?>" required>
							</div>

							<div class="form-group">
								<label for="address_street_2">Address Line 2</label>
								<input type="text" class="form-control" id="address_street_2" name="address_street_2"
									value="<?php echo esc_attr( $primary_address['street_address_2'] ?? '' ); ?>">
							</div>

							<div class="row">
								<div class="col-md-6 form-group">
									<label for="address_city">City <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="address_city" name="address_city"
										value="<?php echo esc_attr( $primary_address['city'] ?? '' ); ?>" required>
								</div>

								<div class="col-md-3 form-group">
									<label for="address_state">State <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="address_state" name="address_state"
										value="<?php echo esc_attr( $primary_address['state'] ?? 'NM' ); ?>" required maxlength="2">
								</div>

								<div class="col-md-3 form-group">
									<label for="address_zip">ZIP Code <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="address_zip" name="address_zip"
										value="<?php echo esc_attr( $primary_address['zip_code'] ?? '' ); ?>" required>
								</div>
							</div>

							<div class="row">
								<div class="col-md-6 form-group">
									<label for="address_county">County</label>
									<input type="text" class="form-control" id="address_county" name="address_county"
										value="<?php echo esc_attr( $primary_address['county'] ?? '' ); ?>">
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Social Media Tab -->
				<div class="tab-pane fade" id="social" role="tabpanel">
					<div class="card">
						<div class="card-body">
							<h4 class="card-title"><i class="fa fa-share-alt"></i> Social Media</h4>
							<p class="text-muted">Connect your social media accounts to show on your public profile</p>

							<div class="form-group">
								<label for="facebook"><i class="fa fa-facebook-square"></i> Facebook</label>
								<input type="url" class="form-control" id="facebook" name="facebook"
									value="<?php echo esc_attr( get_field( 'facebook', $selected_business_id ) ); ?>"
									placeholder="https://facebook.com/yourpage">
							</div>

							<div class="form-group">
								<label for="instagram"><i class="fa fa-instagram"></i> Instagram</label>
								<input type="url" class="form-control" id="instagram" name="instagram"
									value="<?php echo esc_attr( get_field( 'instagram', $selected_business_id ) ); ?>"
									placeholder="https://instagram.com/yourprofile">
							</div>

							<div class="form-group">
								<label for="twitter"><i class="fa fa-twitter-square"></i> Twitter/X</label>
								<input type="url" class="form-control" id="twitter" name="twitter"
									value="<?php echo esc_attr( get_field( 'twitter', $selected_business_id ) ); ?>"
									placeholder="https://twitter.com/yourhandle">
							</div>

							<div class="form-group">
								<label for="pinterest"><i class="fa fa-pinterest-square"></i> Pinterest</label>
								<input type="url" class="form-control" id="pinterest" name="pinterest"
									value="<?php echo esc_attr( get_field( 'pinterest', $selected_business_id ) ); ?>"
									placeholder="https://pinterest.com/yourprofile">
							</div>
						</div>
					</div>
				</div>

				<!-- Products Tab -->
				<div class="tab-pane fade" id="products" role="tabpanel">
					<div class="card">
						<div class="card-body">
							<h4 class="card-title"><i class="fa fa-shopping-cart"></i> Products & Distribution</h4>

							<?php if ( ! empty( $product_types ) && ! is_wp_error( $product_types ) ) : ?>
								<h5 class="mt-3">Product Types</h5>
								<p class="text-muted">Select all product types that apply to your business</p>

								<?php
								$selected_products = wp_get_post_terms( $selected_business_id, 'product_type', array( 'fields' => 'ids' ) );
								?>

								<div class="row">
									<?php foreach ( $product_types as $product ) : ?>
										<div class="col-md-6 col-lg-4 mb-2">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input"
													id="product_<?php echo esc_attr( $product->term_id ); ?>"
													name="product_types[]"
													value="<?php echo esc_attr( $product->term_id ); ?>"
													<?php checked( in_array( $product->term_id, $selected_products ) ); ?>>
												<label class="custom-control-label" for="product_<?php echo esc_attr( $product->term_id ); ?>">
													<?php echo esc_html( $product->name ); ?>
												</label>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<h5 class="mt-4">Sales & Distribution</h5>

							<?php
							$sales_types = get_field( 'sales_type', $selected_business_id );
							if ( ! is_array( $sales_types ) ) {
								$sales_types = array();
							}
							?>

							<div class="form-group">
								<div class="custom-control custom-checkbox">
									<input type="checkbox" class="custom-control-input" id="sales_retail"
										name="sales_types[]" value="retail" <?php checked( in_array( 'retail', $sales_types ) ); ?>>
									<label class="custom-control-label" for="sales_retail">Retail Sales</label>
								</div>
								<div class="custom-control custom-checkbox">
									<input type="checkbox" class="custom-control-input" id="sales_wholesale"
										name="sales_types[]" value="wholesale" <?php checked( in_array( 'wholesale', $sales_types ) ); ?>>
									<label class="custom-control-label" for="sales_wholesale">Wholesale</label>
								</div>
								<div class="custom-control custom-checkbox">
									<input type="checkbox" class="custom-control-input" id="sales_farmers_market"
										name="sales_types[]" value="farmers_market" <?php checked( in_array( 'farmers_market', $sales_types ) ); ?>>
									<label class="custom-control-label" for="sales_farmers_market">Farmers Markets</label>
								</div>
								<div class="custom-control custom-checkbox">
									<input type="checkbox" class="custom-control-input" id="sales_online"
										name="sales_types[]" value="online" <?php checked( in_array( 'online', $sales_types ) ); ?>>
									<label class="custom-control-label" for="sales_online">Online Sales</label>
								</div>
								<div class="custom-control custom-checkbox">
									<input type="checkbox" class="custom-control-input" id="sales_direct"
										name="sales_types[]" value="direct" <?php checked( in_array( 'direct', $sales_types ) ); ?>>
									<label class="custom-control-label" for="sales_direct">Direct to Consumer</label>
								</div>
							</div>

							<div class="form-group">
								<label for="sales_additional_info">Additional Sales Information</label>
								<textarea class="form-control" id="sales_additional_info" name="sales_additional_info" rows="3"
									placeholder="Provide any additional details about your sales channels..."><?php echo esc_textarea( get_field( 'sales_additional_info', $selected_business_id ) ); ?></textarea>
							</div>
						</div>
					</div>
				</div>

			</div>

			<!-- Save Buttons -->
			<div class="card mt-4 mb-5">
				<div class="card-body">
					<div class="row">
						<div class="col-md-6">
							<button type="submit" class="btn btn-primary btn-lg">
								<i class="fa fa-save"></i> Save Changes
							</button>
							<button type="button" class="btn btn-outline-secondary btn-lg" id="cancel-btn">
								<i class="fa fa-times"></i> Cancel
							</button>
						</div>
						<div class="col-md-6 text-right">
							<p class="mb-0 text-muted">
								<i class="fa fa-info-circle"></i> Changes to certain fields may require admin approval
							</p>
						</div>
					</div>
				</div>
			</div>

		</form>

	</div>
</div>

<?php
get_footer();
