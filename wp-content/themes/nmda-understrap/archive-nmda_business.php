<?php
/**
 * Template for Business Directory Archive
 *
 * Displays searchable, filterable directory of NMDA businesses
 *
 * @package NMDA_Understrap
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Get filter parameters
$search_query    = isset( $_GET['search_query'] ) ? sanitize_text_field( $_GET['search_query'] ) : '';
$filter_category = isset( $_GET['category'] ) ? intval( $_GET['category'] ) : 0;
$filter_product  = isset( $_GET['product'] ) ? intval( $_GET['product'] ) : 0;
$filter_classification = isset( $_GET['classification'] ) ? sanitize_text_field( $_GET['classification'] ) : '';

// Build query args
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$args  = array(
	'post_type'      => 'nmda_business',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'orderby'        => 'title',
	'order'          => 'ASC',
);

// Handle search query (using custom parameter to avoid WordPress search template)
if ( $search_query ) {
	global $wpdb;
	$search_term = '%' . $wpdb->esc_like( $search_query ) . '%';

	// Search in post title, content, and excerpt
	$search_results = $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts}
		WHERE post_type = 'nmda_business'
		AND post_status = 'publish'
		AND (post_title LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s)",
		$search_term,
		$search_term,
		$search_term
	) );

	if ( ! empty( $search_results ) ) {
		$args['post__in'] = $search_results;
	} else {
		// No results found - set to empty array to force no results
		$args['post__in'] = array( 0 );
	}
}

// Build tax_query array
$tax_query = array( 'relation' => 'AND' );

if ( $filter_category ) {
	$tax_query[] = array(
		'taxonomy' => 'business_category',
		'field'    => 'term_id',
		'terms'    => $filter_category,
	);
}

if ( $filter_product ) {
	$tax_query[] = array(
		'taxonomy' => 'product_type',
		'field'    => 'term_id',
		'terms'    => $filter_product,
	);
}

if ( count( $tax_query ) > 1 ) {
	$args['tax_query'] = $tax_query;
}

// Add meta query for classification filter
if ( $filter_classification ) {
	$args['meta_query'] = array(
		array(
			'key'     => 'classification',
			'value'   => serialize( strval( $filter_classification ) ),
			'compare' => 'LIKE',
		),
	);
}

// Execute query
$businesses = new WP_Query( $args );

// Get taxonomies for filters
$categories = get_terms( array(
	'taxonomy'   => 'business_category',
	'hide_empty' => true,
) );

$products = get_terms( array(
	'taxonomy'   => 'product_type',
	'hide_empty' => true,
) );

// Check if filters are active
$has_filters = $search_query || $filter_category || $filter_product || $filter_classification;
?>

<div class="wrapper" id="archive-wrapper">
	<div class="container" id="content">
		<div class="row">
			<div class="col-12">

				<!-- Page Header -->
				<div class="dashboard-header mb-4">
					<div class="row align-items-center">
						<div class="col-md-8">
							<h1 class="dashboard-title">
								<i class="fa fa-building"></i> Business Directory
							</h1>
							<p class="dashboard-subtitle">Discover New Mexico agricultural businesses</p>
						</div>
						<div class="col-md-4 text-right">
							<?php if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=nmda_business' ) ); ?>" class="btn btn-primary">
									<i class="fa fa-plus"></i> Add Business
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Search and Filter Form -->
				<div class="card mb-4">
					<div class="card-body">
						<form method="get" class="directory-filter-form">
							<div class="row">
								<div class="col-md-4 mb-3 mb-md-0">
									<div class="form-group mb-0">
										<label for="search-input" class="sr-only">Search Businesses</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fa fa-search"></i></span>
											</div>
											<input
												type="text"
												id="search-input"
												name="search_query"
												class="form-control"
												placeholder="Search by name or keyword..."
												value="<?php echo esc_attr( $search_query ); ?>"
											>
										</div>
									</div>
								</div>

								<div class="col-md-3 mb-3 mb-md-0">
									<select name="category" id="category-filter" class="form-control">
										<option value="">All Categories</option>
										<?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
											<?php foreach ( $categories as $category ) : ?>
												<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $filter_category, $category->term_id ); ?>>
													<?php echo esc_html( $category->name ); ?> (<?php echo esc_html( $category->count ); ?>)
												</option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</div>

								<div class="col-md-3 mb-3 mb-md-0">
									<select name="product" id="product-filter" class="form-control">
										<option value="">All Products</option>
										<?php if ( ! is_wp_error( $products ) && ! empty( $products ) ) : ?>
											<?php foreach ( $products as $product ) : ?>
												<option value="<?php echo esc_attr( $product->term_id ); ?>" <?php selected( $filter_product, $product->term_id ); ?>>
													<?php echo esc_html( $product->name ); ?> (<?php echo esc_html( $product->count ); ?>)
												</option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</div>

								<div class="col-md-2">
									<button type="submit" class="btn btn-primary btn-block">
										<i class="fa fa-filter"></i> Filter
									</button>
								</div>
							</div>

							<!-- Classification Filter Row -->
							<div class="row mt-3">
								<div class="col-md-12">
									<label class="mr-3"><strong>Classification:</strong></label>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="radio" name="classification" id="class-all" value="" <?php checked( $filter_classification, '' ); ?>>
										<label class="form-check-label" for="class-all">All</label>
									</div>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="radio" name="classification" id="class-grown" value="grown" <?php checked( $filter_classification, 'grown' ); ?>>
										<label class="form-check-label" for="class-grown">Grown</label>
									</div>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="radio" name="classification" id="class-taste" value="taste" <?php checked( $filter_classification, 'taste' ); ?>>
										<label class="form-check-label" for="class-taste">Taste</label>
									</div>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="radio" name="classification" id="class-associate" value="associate" <?php checked( $filter_classification, 'associate' ); ?>>
										<label class="form-check-label" for="class-associate">Associate</label>
									</div>
								</div>
							</div>
						</form>

						<?php if ( $has_filters ) : ?>
							<div class="row mt-3">
								<div class="col-12 text-right">
									<a href="<?php echo esc_url( get_post_type_archive_link( 'nmda_business' ) ); ?>" class="btn btn-sm btn-outline-secondary">
										<i class="fa fa-times"></i> Clear Filters
									</a>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Results Count -->
				<?php if ( $businesses->have_posts() ) : ?>
					<div class="results-summary mb-3">
						<p class="text-muted">
							<strong><?php echo number_format_i18n( $businesses->found_posts ); ?></strong>
							<?php echo _n( 'business found', 'businesses found', $businesses->found_posts, 'nmda-understrap' ); ?>
						</p>
					</div>
				<?php endif; ?>

				<!-- Business Grid -->
				<div class="row business-grid">
					<?php if ( $businesses->have_posts() ) : ?>
						<?php while ( $businesses->have_posts() ) : $businesses->the_post(); ?>
							<?php
							$business_id     = get_the_ID();
							$dba             = get_field( 'dba', $business_id );
							$primary_city    = get_field( 'primary_city', $business_id );
							$primary_state   = get_field( 'primary_state', $business_id );
							$business_phone  = get_field( 'business_phone', $business_id );
							$classification  = get_field( 'classification', $business_id );
							$logo_url        = get_the_post_thumbnail_url( $business_id, 'medium' );
							$product_terms   = wp_get_post_terms( $business_id, 'product_type', array( 'number' => 5 ) );
							$approval_status = get_field( 'approval_status', $business_id );

							// Only show approved businesses in public directory
							if ( ! is_user_logged_in() || ! current_user_can( 'administrator' ) ) {
								if ( $approval_status !== 'approved' ) {
									continue;
								}
							}
							?>

							<div class="col-md-6 col-lg-4 mb-4">
								<div class="business-card">
									<div class="business-card-image">
										<?php if ( $logo_url ) : ?>
											<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?> logo">
										<?php else : ?>
											<div class="business-card-placeholder">
												<i class="fa fa-building"></i>
											</div>
										<?php endif; ?>

										<?php if ( is_user_logged_in() && current_user_can( 'administrator' ) && $approval_status !== 'approved' ) : ?>
											<span class="nmda-status-badge <?php echo esc_attr( $approval_status ); ?>">
												<?php echo esc_html( ucfirst( $approval_status ) ); ?>
											</span>
										<?php endif; ?>
									</div>

									<div class="business-card-content">
										<h3 class="business-card-title">
											<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
										</h3>

										<?php if ( $dba && $dba !== get_the_title() ) : ?>
											<p class="business-card-dba">DBA: <?php echo esc_html( $dba ); ?></p>
										<?php endif; ?>

										<?php if ( $classification && is_array( $classification ) ) : ?>
											<div class="business-card-classification mb-2">
												<?php foreach ( $classification as $class ) : ?>
													<span class="badge badge-primary"><?php echo esc_html( ucfirst( $class ) ); ?></span>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>

										<?php if ( $primary_city || $primary_state ) : ?>
											<p class="business-card-location">
												<i class="fa fa-map-marker-alt"></i>
												<?php
												echo esc_html( trim( $primary_city . ', ' . $primary_state, ', ' ) );
												?>
											</p>
										<?php endif; ?>

										<?php if ( ! is_wp_error( $product_terms ) && ! empty( $product_terms ) ) : ?>
											<div class="business-card-products">
												<?php foreach ( array_slice( $product_terms, 0, 3 ) as $product ) : ?>
													<span class="badge badge-secondary"><?php echo esc_html( $product->name ); ?></span>
												<?php endforeach; ?>
												<?php if ( count( $product_terms ) > 3 ) : ?>
													<span class="badge badge-light">+<?php echo count( $product_terms ) - 3; ?> more</span>
												<?php endif; ?>
											</div>
										<?php endif; ?>

										<a href="<?php the_permalink(); ?>" class="btn btn-primary btn-block mt-3">
											View Profile <i class="fa fa-arrow-right"></i>
										</a>
									</div>
								</div>
							</div>

						<?php endwhile; ?>
					<?php else : ?>
						<div class="col-12">
							<div class="alert alert-info text-center">
								<i class="fa fa-search fa-3x mb-3"></i>
								<h4>No businesses found</h4>
								<p>Try adjusting your search or filter criteria.</p>
								<?php if ( $has_filters ) : ?>
									<a href="<?php echo esc_url( get_post_type_archive_link( 'nmda_business' ) ); ?>" class="btn btn-primary">
										<i class="fa fa-times"></i> Clear Filters
									</a>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- Pagination -->
				<?php if ( $businesses->max_num_pages > 1 ) : ?>
					<div class="row mt-4">
						<div class="col-12">
							<nav aria-label="Business directory pagination">
								<?php
								$big = 999999999;
								echo paginate_links( array(
									'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
									'format'    => '?paged=%#%',
									'current'   => max( 1, $paged ),
									'total'     => $businesses->max_num_pages,
									'prev_text' => '<i class="fa fa-chevron-left"></i> Previous',
									'next_text' => 'Next <i class="fa fa-chevron-right"></i>',
									'type'      => 'list',
								) );
								?>
							</nav>
						</div>
					</div>
				<?php endif; ?>

				<?php wp_reset_postdata(); ?>

			</div>
		</div>
	</div>
</div>

<?php
get_footer();
