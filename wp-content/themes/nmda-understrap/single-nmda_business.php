<?php
/**
 * Template for Single Business Profile
 *
 * Displays detailed information about a single NMDA business
 *
 * @package NMDA_Understrap
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$business_id = get_the_ID();

	// Get ACF fields
	$dba                = get_field( 'dba', $business_id );
	$business_phone     = get_field( 'business_phone', $business_id );
	$business_email     = get_field( 'business_email', $business_id );
	$website            = get_field( 'website', $business_id );
	$business_profile   = get_field( 'business_profile', $business_id );
	$business_hours     = get_field( 'business_hours', $business_id );
	$num_employees      = get_field( 'num_employees', $business_id );
	$classification     = get_field( 'classification', $business_id );
	$associate_type     = get_field( 'associate_type', $business_id );

	// Address fields
	$primary_address    = get_field( 'primary_address', $business_id );
	$primary_city       = get_field( 'primary_city', $business_id );
	$primary_state      = get_field( 'primary_state', $business_id );
	$primary_zip        = get_field( 'primary_zip', $business_id );
	$primary_county     = get_field( 'primary_county', $business_id );

	// Get taxonomies
	$business_categories = wp_get_post_terms( $business_id, 'business_category' );
	$product_types       = wp_get_post_terms( $business_id, 'product_type' );

	// Get logo
	$logo_url = get_the_post_thumbnail_url( $business_id, 'large' );

	// Check approval status
	$approval_status = get_field( 'approval_status', $business_id );
	?>

	<div class="wrapper" id="single-wrapper">

		<!-- Hero Section -->
		<div class="business-profile-hero">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-md-3">
						<div class="business-profile-logo">
							<?php if ( $logo_url ) : ?>
								<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?> logo">
							<?php else : ?>
								<div class="business-profile-logo-placeholder">
									<i class="fa fa-building"></i>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<div class="col-md-9">
						<div class="business-profile-header-content">
							<?php if ( is_user_logged_in() && current_user_can( 'administrator' ) && $approval_status !== 'approved' ) : ?>
								<span class="nmda-status-badge <?php echo esc_attr( $approval_status ); ?> mb-2">
									<?php echo esc_html( ucfirst( $approval_status ) ); ?>
								</span>
							<?php endif; ?>

							<h1 class="business-profile-title"><?php the_title(); ?></h1>

							<?php if ( $dba && $dba !== get_the_title() ) : ?>
								<p class="business-profile-dba">DBA: <?php echo esc_html( $dba ); ?></p>
							<?php endif; ?>

							<?php if ( $classification && is_array( $classification ) ) : ?>
								<div class="business-profile-classification mt-3">
									<?php foreach ( $classification as $class ) : ?>
										<span class="badge badge-primary badge-lg">
											<i class="fa fa-check-circle"></i> <?php echo esc_html( ucfirst( $class ) ); ?>
										</span>
									<?php endforeach; ?>
									<?php if ( in_array( 'associate', $classification ) && $associate_type ) : ?>
										<?php
										// Handle if associate_type is an array (checkbox field)
										$associate_types = is_array( $associate_type ) ? $associate_type : array( $associate_type );
										foreach ( $associate_types as $type ) :
											if ( ! empty( $type ) ) :
										?>
											<span class="badge badge-secondary badge-lg">
												<?php echo esc_html( ucfirst( str_replace( '_', ' ', $type ) ) ); ?>
											</span>
										<?php
											endif;
										endforeach;
										?>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<div class="business-profile-actions mt-3">
								<a href="<?php echo esc_url( get_post_type_archive_link( 'nmda_business' ) ); ?>" class="btn btn-outline-light">
									<i class="fa fa-arrow-left"></i> Back to Directory
								</a>
								<?php if ( is_user_logged_in() && current_user_can( 'edit_post', $business_id ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $business_id ) ); ?>" class="btn btn-outline-light">
										<i class="fa fa-edit"></i> Edit
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Main Content -->
		<div class="container" id="content">
			<div class="row mt-4">

				<!-- Main Column -->
				<div class="col-lg-8">

					<!-- About Section -->
					<?php if ( $business_profile ) : ?>
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-info-circle"></i> About</h3>
							</div>
							<div class="card-body">
								<?php echo wp_kses_post( $business_profile ); ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Products Section -->
					<?php if ( ! is_wp_error( $product_types ) && ! empty( $product_types ) ) : ?>
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-shopping-basket"></i> Products</h3>
							</div>
							<div class="card-body">
								<?php
								// Group products by parent category if available
								$products_by_parent = array();
								foreach ( $product_types as $product ) {
									if ( $product->parent ) {
										$parent = get_term( $product->parent, 'product_type' );
										if ( ! isset( $products_by_parent[ $parent->name ] ) ) {
											$products_by_parent[ $parent->name ] = array();
										}
										$products_by_parent[ $parent->name ][] = $product;
									} else {
										if ( ! isset( $products_by_parent['Other'] ) ) {
											$products_by_parent['Other'] = array();
										}
										$products_by_parent['Other'][] = $product;
									}
								}

								// Display grouped products
								if ( ! empty( $products_by_parent ) ) :
									foreach ( $products_by_parent as $category => $products ) :
										if ( $category !== 'Other' ) :
											?>
											<h5 class="product-category-heading"><?php echo esc_html( $category ); ?></h5>
										<?php endif; ?>
										<div class="product-list mb-3">
											<?php foreach ( $products as $product ) : ?>
												<span class="badge badge-product"><?php echo esc_html( $product->name ); ?></span>
											<?php endforeach; ?>
										</div>
									<?php
									endforeach;
								else :
									?>
									<div class="product-list">
										<?php foreach ( $product_types as $product ) : ?>
											<span class="badge badge-product"><?php echo esc_html( $product->name ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Business Hours -->
					<?php if ( $business_hours ) : ?>
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-clock"></i> Business Hours</h3>
							</div>
							<div class="card-body">
								<?php echo nl2br( esc_html( $business_hours ) ); ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Categories -->
					<?php if ( ! is_wp_error( $business_categories ) && ! empty( $business_categories ) ) : ?>
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-tags"></i> Categories</h3>
							</div>
							<div class="card-body">
								<?php foreach ( $business_categories as $category ) : ?>
									<span class="badge badge-category"><?php echo esc_html( $category->name ); ?></span>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

				</div>

				<!-- Sidebar Column -->
				<div class="col-lg-4">

					<!-- Contact Information -->
					<div class="card mb-4 business-contact-card">
						<div class="card-header">
							<h3><i class="fa fa-address-card"></i> Contact Information</h3>
						</div>
						<div class="card-body">
							<?php if ( $business_phone ) : ?>
								<div class="contact-item">
									<i class="fa fa-phone"></i>
									<div class="contact-item-content">
										<strong>Phone:</strong><br>
										<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $business_phone ) ); ?>">
											<?php echo esc_html( $business_phone ); ?>
										</a>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( $business_email ) : ?>
								<div class="contact-item">
									<i class="fa fa-envelope"></i>
									<div class="contact-item-content">
										<strong>Email:</strong><br>
										<a href="mailto:<?php echo esc_attr( $business_email ); ?>">
											<?php echo esc_html( $business_email ); ?>
										</a>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( $website ) : ?>
								<div class="contact-item">
									<i class="fa fa-globe"></i>
									<div class="contact-item-content">
										<strong>Website:</strong><br>
										<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( parse_url( $website, PHP_URL_HOST ) ?: $website ); ?>
											<i class="fa fa-external-link-alt fa-sm"></i>
										</a>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Addresses -->
					<?php
					// Get all addresses for this business
					$all_addresses = nmda_get_business_addresses( $business_id );
					if ( ! empty( $all_addresses ) ) :
					?>
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-map-marker-alt"></i> Location<?php echo count( $all_addresses ) > 1 ? 's' : ''; ?></h3>
							</div>
							<div class="card-body">
								<?php foreach ( $all_addresses as $index => $address ) : ?>
									<?php if ( $index > 0 ) : ?>
										<hr class="my-3">
									<?php endif; ?>

									<div class="business-address-item">
										<!-- Address Name/Type with Primary Badge -->
										<h5 class="mb-2">
											<?php
											$address_display_name = ! empty( $address->address_name ) ? $address->address_name : ucwords( str_replace( '_', ' ', $address->address_type ) );
											echo esc_html( $address_display_name );
											?>
											<?php if ( $address->is_primary ) : ?>
												<span class="badge badge-success ml-2">Primary</span>
											<?php endif; ?>
										</h5>

										<!-- Address Type (if different from name) -->
										<?php if ( ! empty( $address->address_name ) && $address->address_type ) : ?>
											<p class="text-muted mb-2">
												<small><i class="fa fa-tag"></i> <?php echo esc_html( ucwords( str_replace( '_', ' ', $address->address_type ) ) ); ?></small>
											</p>
										<?php endif; ?>

										<!-- Street Address -->
										<?php if ( $address->address_line_1 ) : ?>
											<p class="mb-1">
												<i class="fa fa-map-marker"></i>
												<?php echo esc_html( $address->address_line_1 ); ?>
												<?php if ( $address->address_line_2 ) : ?>
													<br><span class="ml-3"><?php echo esc_html( $address->address_line_2 ); ?></span>
												<?php endif; ?>
											</p>
										<?php endif; ?>

										<!-- City, State, ZIP -->
										<?php if ( $address->city || $address->state ) : ?>
											<p class="mb-1">
												<span class="ml-3">
													<?php
													$location_parts = array_filter( array( $address->city, $address->state, $address->zip_code ) );
													echo esc_html( implode( ', ', $location_parts ) );
													?>
												</span>
											</p>
										<?php endif; ?>

										<!-- County -->
										<?php if ( $address->county ) : ?>
											<p class="mb-2 text-muted">
												<small class="ml-3"><?php echo esc_html( $address->county ); ?> County</small>
											</p>
										<?php endif; ?>

										<!-- Phone -->
										<?php if ( $address->phone ) : ?>
											<p class="mb-1">
												<i class="fa fa-phone"></i>
												<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $address->phone ) ); ?>">
													<?php echo esc_html( $address->phone ); ?>
												</a>
											</p>
										<?php endif; ?>

										<!-- Email -->
										<?php if ( $address->email ) : ?>
											<p class="mb-2">
												<i class="fa fa-envelope"></i>
												<a href="mailto:<?php echo esc_attr( $address->email ); ?>">
													<?php echo esc_html( $address->email ); ?>
												</a>
											</p>
										<?php endif; ?>

										<!-- View on Map Button -->
										<?php if ( $address->address_line_1 && $address->city && $address->state ) : ?>
											<a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode( $address->address_line_1 . ', ' . $address->city . ', ' . $address->state . ' ' . $address->zip_code ); ?>"
											   target="_blank"
											   rel="noopener noreferrer"
											   class="btn btn-sm btn-outline-primary mt-2">
												<i class="fa fa-map"></i> View on Map
											</a>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Quick Facts -->
					<?php if ( $num_employees ) : ?>
						<div class="card mb-4">
							<div class="card-header">
								<h3><i class="fa fa-info"></i> Quick Facts</h3>
							</div>
							<div class="card-body">
								<div class="quick-fact">
									<i class="fa fa-users"></i>
									<div>
										<strong>Employees:</strong><br>
										<?php echo esc_html( $num_employees ); ?>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>

				</div>

			</div>
		</div>

	</div>

<?php endwhile; ?>

<?php
get_footer();
