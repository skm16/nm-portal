<?php
/**
 * Template Name: Resource Center
 * Template for member resource downloads
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

// Check if user is approved member
if ( ! nmda_is_approved_member() ) {
	get_header();
	?>
	<div class="wrapper" id="page-wrapper">
		<div class="container" id="content">
			<div class="row">
				<div class="col-md-12">
					<div class="alert alert-warning mt-5">
						<h4><i class="fa fa-exclamation-triangle"></i> Access Restricted</h4>
						<p>The Resource Center is available only to approved NMDA members. Your business application must be approved before you can access member resources.</p>
						<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-primary">
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

get_header();

// Get filter parameters
$filter_category = isset( $_GET['category'] ) ? intval( $_GET['category'] ) : 0;
$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

// Build query args
$query_args = array();
if ( $filter_category ) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'resource_category',
			'field'    => 'term_id',
			'terms'    => $filter_category,
		),
	);
}
if ( $search_query ) {
	$query_args['s'] = $search_query;
}

// Get resources - always get all resources in one list
$resources = nmda_get_resources( $query_args );

// Get all categories for filter
$all_categories = get_terms( array(
	'taxonomy'   => 'resource_category',
	'hide_empty' => true,
	'orderby'    => 'name',
	'order'      => 'ASC',
) );
?>

<div class="wrapper" id="page-wrapper">
	<div class="container" id="content">

		<!-- Page Header -->
		<div class="dashboard-header">
			<div class="row align-items-center">
				<div class="col-md-8">
					<h1 class="dashboard-title"><i class="fa fa-download"></i> Resource Center</h1>
					<p class="dashboard-subtitle">Access member-only resources, downloads, and tools</p>
				</div>
				<div class="col-md-4 text-right">
					<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-secondary">
						<i class="fa fa-arrow-left"></i> Back to Dashboard
					</a>
				</div>
			</div>
		</div>

		<!-- Search and Filter -->
		<div class="card mt-4">
			<div class="card-body">
				<form method="get" action="<?php echo esc_url( get_permalink() ); ?>" class="row align-items-end">
					<div class="col-md-6 form-group">
						<label for="search"><i class="fa fa-search"></i> Search Resources</label>
						<input type="text" name="s" id="search" class="form-control" value="<?php echo esc_attr( $search_query ); ?>" placeholder="Search by title or description...">
					</div>
					<div class="col-md-4 form-group">
						<label for="category"><i class="fa fa-filter"></i> Filter by Category</label>
						<select name="category" id="category" class="form-control">
							<option value="">All Categories</option>
							<?php foreach ( $all_categories as $category ) : ?>
								<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $filter_category, $category->term_id ); ?>>
									<?php echo esc_html( $category->name ); ?> (<?php echo $category->count; ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-2 form-group">
						<button type="submit" class="btn btn-primary btn-block">
							<i class="fa fa-search"></i> Filter
						</button>
					</div>
				</form>
				<?php if ( $filter_category || $search_query ) : ?>
					<div class="mt-2">
						<a href="<?php echo get_permalink(); ?>" class="btn btn-sm btn-outline-secondary">
							<i class="fa fa-times"></i> Clear Filters
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Resources Display -->
		<div class="card mt-4">
			<div class="card-header">
				<h3><i class="fa fa-folder-open"></i> All Resources</h3>
			</div>
			<div class="card-body">
				<?php if ( ! empty( $resources ) ) : ?>
					<!-- Resources Grid -->
					<div class="row">
						<?php foreach ( $resources as $resource ) : ?>
							<?php
							$file_info = nmda_get_resource_file_info( $resource->ID );
							$download_count = nmda_get_resource_download_count( $resource->ID );
							$thumbnail = get_the_post_thumbnail_url( $resource->ID, 'medium' );
							$description = get_field( 'resource_description', $resource->ID );
							$resource_type = get_field( 'resource_type', $resource->ID );
							$is_external = $resource_type === 'URL';

							// Get resource categories
							$categories = get_the_terms( $resource->ID, 'resource_category' );
							?>
							<div class="col-md-6 col-lg-4 mb-4">
								<div class="resource-card">
									<?php if ( $thumbnail ) : ?>
										<div class="resource-thumbnail">
											<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $resource->post_title ); ?>">
										</div>
									<?php endif; ?>
									<div class="resource-content">
										<h4 class="resource-title">
											<?php echo esc_html( $resource->post_title ); ?>
											<?php if ( $is_external ) : ?>
												<i class="fa fa-external-link" title="External Link"></i>
											<?php endif; ?>
										</h4>

										<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
											<div class="resource-categories mb-2">
												<?php foreach ( $categories as $category ) : ?>
													<span class="badge badge-primary">
														<i class="fa fa-folder"></i> <?php echo esc_html( $category->name ); ?>
													</span>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>

										<?php if ( $description ) : ?>
											<div class="resource-description">
												<?php echo wp_trim_words( wp_strip_all_tags( $description ), 20 ); ?>
											</div>
										<?php endif; ?>

										<?php if ( $file_info ) : ?>
											<div class="resource-meta">
												<span class="badge badge-secondary">
													<i class="fa fa-file-<?php echo esc_attr( $file_info['ext'] ); ?>-o"></i>
													<?php echo strtoupper( $file_info['ext'] ); ?>
												</span>
												<span class="badge badge-info">
													<i class="fa fa-hdd-o"></i>
													<?php echo esc_html( $file_info['size_formatted'] ); ?>
												</span>
												<?php if ( ! $is_external ) : ?>
													<span class="badge badge-light">
														<i class="fa fa-download"></i>
														<?php echo $download_count; ?> downloads
													</span>
												<?php endif; ?>
											</div>

											<?php if ( $is_external ) : ?>
												<a href="<?php echo esc_url( $file_info['url'] ); ?>" class="btn btn-primary btn-block mt-3" target="_blank" rel="noopener">
													<i class="fa fa-external-link"></i> Visit Link
												</a>
											<?php else : ?>
												<a href="<?php echo esc_url( add_query_arg( 'nmda_download_resource', $resource->ID, home_url() ) ); ?>" class="btn btn-primary btn-block mt-3">
													<i class="fa fa-download"></i> Download
												</a>
											<?php endif; ?>
										<?php else : ?>
											<div class="alert alert-warning mt-3 mb-0">
												<small><i class="fa fa-exclamation-triangle"></i> No file available</small>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="alert alert-info">
						<i class="fa fa-info-circle"></i> No resources found matching your search criteria.
						<?php if ( $filter_category || $search_query ) : ?>
							<a href="<?php echo get_permalink(); ?>" class="alert-link">Clear filters</a> to view all resources.
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Help Section -->
		<div class="card mt-4 mb-5">
			<div class="card-body bg-light">
				<div class="row">
					<div class="col-md-6">
						<h4><i class="fa fa-question-circle"></i> Need Help?</h4>
						<p>If you can't find the resource you're looking for or need assistance, please contact NMDA staff.</p>
					</div>
					<div class="col-md-6 text-right">
						<a href="mailto:info@nmda.com" class="btn btn-outline-primary">
							<i class="fa fa-envelope"></i> Contact Support
						</a>
						<a href="<?php echo home_url( '/dashboard' ); ?>" class="btn btn-outline-secondary">
							<i class="fa fa-home"></i> Return to Dashboard
						</a>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

<?php
get_footer();
