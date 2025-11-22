<?php
/**
 * Template Name: User Directory
 * Description: Admin-only directory of all users with business associations
 *
 * @package NMDA_Understrap
 */

defined( 'ABSPATH' ) || exit;

// Security check - Admin only
if ( ! is_user_logged_in() || ! current_user_can( 'administrator' ) ) {
	wp_redirect( home_url() );
	exit;
}

get_header();

// Get filter parameters
$search_query  = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$filter_role   = isset( $_GET['role'] ) ? sanitize_text_field( $_GET['role'] ) : '';
$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$orderby       = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'display_name';
$order         = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';

// Pagination
$paged         = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
$per_page      = 25;
$offset        = ( $paged - 1 ) * $per_page;

// Build user query args
$user_args = array(
	'orderby' => $orderby,
	'order'   => $order,
	'number'  => $per_page,
	'offset'  => $offset,
);

// Add search
if ( $search_query ) {
	$user_args['search']         = '*' . $search_query . '*';
	$user_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
}

// Get users
$user_query = new WP_User_Query( $user_args );
$users      = $user_query->get_results();
$total      = $user_query->get_total();

// Get all users with business data
global $wpdb;
$user_business_table = $wpdb->prefix . 'nmda_user_business';

$users_data = array();
foreach ( $users as $user ) {
	$user_id = $user->ID;

	// Get user's businesses
	$businesses = nmda_get_user_businesses( $user_id );

	// Filter by role if specified
	if ( $filter_role ) {
		$businesses = array_filter( $businesses, function( $business ) use ( $filter_role ) {
			return $business['role'] === $filter_role;
		} );
		if ( empty( $businesses ) ) {
			continue;
		}
	}

	// Filter by status if specified
	if ( $filter_status ) {
		$businesses = array_filter( $businesses, function( $business ) use ( $filter_status ) {
			return $business['status'] === $filter_status;
		} );
		if ( empty( $businesses ) ) {
			continue;
		}
	}

	// Get last login
	$last_login = get_user_meta( $user_id, 'last_login', true );

	$users_data[] = array(
		'user'       => $user,
		'businesses' => $businesses,
		'last_login' => $last_login,
	);
}

// Check if filters are active
$has_filters = $search_query || $filter_role || $filter_status;
?>

<div class="wrapper" id="page-wrapper">
	<div class="container-fluid" id="content">
		<div class="row">
			<div class="col-12">

				<!-- Page Header -->
				<div class="dashboard-header mb-4">
					<div class="row align-items-center">
						<div class="col-md-8">
							<h1 class="dashboard-title">
								<i class="fa fa-users"></i> User Directory
							</h1>
							<p class="dashboard-subtitle">Manage all portal users and their business associations</p>
						</div>
						<div class="col-md-4 text-right">
							<button type="button" class="btn btn-secondary" id="export-users-btn">
								<i class="fa fa-download"></i> Export to CSV
							</button>
							<a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>" class="btn btn-primary">
								<i class="fa fa-user-plus"></i> Add User
							</a>
						</div>
					</div>
				</div>

				<!-- Search and Filter Form -->
				<div class="card mb-4">
					<div class="card-body">
						<form method="get" action="" class="user-filter-form">
							<div class="row">
								<div class="col-md-5 mb-3 mb-md-0">
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text"><i class="fa fa-search"></i></span>
										</div>
										<input
											type="text"
											name="s"
											class="form-control"
											placeholder="Search by name or email..."
											value="<?php echo esc_attr( $search_query ); ?>"
										>
									</div>
								</div>

								<div class="col-md-2 mb-3 mb-md-0">
									<select name="role" class="form-control">
										<option value="">All Roles</option>
										<option value="owner" <?php selected( $filter_role, 'owner' ); ?>>Owner</option>
										<option value="manager" <?php selected( $filter_role, 'manager' ); ?>>Manager</option>
										<option value="viewer" <?php selected( $filter_role, 'viewer' ); ?>>Viewer</option>
									</select>
								</div>

								<div class="col-md-2 mb-3 mb-md-0">
									<select name="status" class="form-control">
										<option value="">All Statuses</option>
										<option value="active" <?php selected( $filter_status, 'active' ); ?>>Active</option>
										<option value="pending" <?php selected( $filter_status, 'pending' ); ?>>Pending</option>
										<option value="disabled" <?php selected( $filter_status, 'disabled' ); ?>>Disabled</option>
									</select>
								</div>

								<div class="col-md-2 mb-3 mb-md-0">
									<button type="submit" class="btn btn-primary btn-block">
										<i class="fa fa-filter"></i> Filter
									</button>
								</div>

								<?php if ( $has_filters ) : ?>
									<div class="col-md-1">
										<a href="<?php echo esc_url( get_permalink() ); ?>" class="btn btn-outline-secondary btn-block" title="Clear Filters">
											<i class="fa fa-times"></i>
										</a>
									</div>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>

				<!-- Results Summary -->
				<div class="results-summary mb-3">
					<p class="text-muted">
						<strong><?php echo number_format_i18n( count( $users_data ) ); ?></strong>
						<?php echo _n( 'user found', 'users found', count( $users_data ), 'nmda-understrap' ); ?>
					</p>
				</div>

				<!-- Users Table -->
				<div class="card">
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover user-directory-table mb-0">
								<thead>
									<tr>
										<th width="30">
											<input type="checkbox" id="select-all-users">
										</th>
										<th>
											<a href="?orderby=display_name&order=<?php echo $orderby === 'display_name' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>">
												Name
												<?php if ( $orderby === 'display_name' ) : ?>
													<i class="fa fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
												<?php endif; ?>
											</a>
										</th>
										<th>
											<a href="?orderby=user_email&order=<?php echo $orderby === 'user_email' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>">
												Email
												<?php if ( $orderby === 'user_email' ) : ?>
													<i class="fa fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
												<?php endif; ?>
											</a>
										</th>
										<th>Businesses</th>
										<th>Primary Role</th>
										<th>Status</th>
										<th>Last Login</th>
										<th class="text-center">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php if ( ! empty( $users_data ) ) : ?>
										<?php foreach ( $users_data as $user_data ) : ?>
											<?php
											$user        = $user_data['user'];
											$businesses  = $user_data['businesses'];
											$last_login  = $user_data['last_login'];
											$primary_role = ! empty( $businesses ) ? $businesses[0]['role'] : 'N/A';
											$primary_status = ! empty( $businesses ) ? $businesses[0]['status'] : 'none';
											?>
											<tr>
												<td>
													<input type="checkbox" class="user-checkbox" value="<?php echo esc_attr( $user->ID ); ?>">
												</td>
												<td>
													<a href="?page_id=<?php echo get_the_ID(); ?>&user_id=<?php echo esc_attr( $user->ID ); ?>" class="user-name-link">
														<?php echo esc_html( $user->display_name ); ?>
													</a>
												</td>
												<td><?php echo esc_html( $user->user_email ); ?></td>
												<td>
													<span class="badge badge-info">
														<?php echo count( $businesses ); ?>
													</span>
													<?php if ( ! empty( $businesses ) ) : ?>
														<div class="business-list-tooltip">
															<?php foreach ( array_slice( $businesses, 0, 3 ) as $business ) : ?>
																<small class="d-block text-muted">
																	<?php echo esc_html( get_the_title( $business['business_id'] ) ); ?>
																	<em>(<?php echo esc_html( $business['role'] ); ?>)</em>
																</small>
															<?php endforeach; ?>
															<?php if ( count( $businesses ) > 3 ) : ?>
																<small class="text-muted">+<?php echo count( $businesses ) - 3; ?> more</small>
															<?php endif; ?>
														</div>
													<?php endif; ?>
												</td>
												<td>
													<?php if ( $primary_role !== 'N/A' ) : ?>
														<span class="badge badge-<?php echo $primary_role === 'owner' ? 'success' : ( $primary_role === 'manager' ? 'primary' : 'secondary' ); ?>">
															<?php echo esc_html( ucfirst( $primary_role ) ); ?>
														</span>
													<?php else : ?>
														<span class="text-muted">N/A</span>
													<?php endif; ?>
												</td>
												<td>
													<span class="status-indicator status-<?php echo esc_attr( $primary_status ); ?>"></span>
													<?php echo esc_html( ucfirst( $primary_status ) ); ?>
												</td>
												<td>
													<?php if ( $last_login ) : ?>
														<span title="<?php echo esc_attr( date( 'Y-m-d H:i:s', $last_login ) ); ?>">
															<?php echo human_time_diff( $last_login, current_time( 'timestamp' ) ); ?> ago
														</span>
													<?php else : ?>
														<span class="text-muted">Never</span>
													<?php endif; ?>
												</td>
												<td class="text-center">
													<div class="btn-group btn-group-sm">
														<a href="<?php echo esc_url( add_query_arg( 'user_id', $user->ID, get_permalink() ) ); ?>" class="btn btn-sm btn-outline-primary" title="View Profile">
															<i class="fa fa-eye"></i>
														</a>
														<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>" class="btn btn-sm btn-outline-secondary" title="Edit User">
															<i class="fa fa-edit"></i>
														</a>
													</div>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="8" class="text-center py-5">
												<i class="fa fa-users fa-3x text-muted mb-3"></i>
												<p class="text-muted">No users found matching your criteria.</p>
												<?php if ( $has_filters ) : ?>
													<a href="<?php echo esc_url( get_permalink() ); ?>" class="btn btn-primary">
														<i class="fa fa-times"></i> Clear Filters
													</a>
												<?php endif; ?>
											</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- Bulk Actions Bar -->
				<?php if ( ! empty( $users_data ) ) : ?>
					<div class="bulk-actions-bar mt-3" style="display: none;">
						<div class="card">
							<div class="card-body">
								<div class="row align-items-center">
									<div class="col-md-6">
										<span class="selected-count">0</span> user(s) selected
									</div>
									<div class="col-md-6 text-right">
										<select class="form-control d-inline-block w-auto mr-2" id="bulk-action-select">
											<option value="">Bulk Actions</option>
											<option value="export">Export Selected</option>
											<option value="message">Send Message</option>
										</select>
										<button type="button" class="btn btn-primary" id="bulk-action-apply">Apply</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<!-- Pagination -->
				<?php
				$total_pages = ceil( $total / $per_page );
				if ( $total_pages > 1 ) :
					?>
					<div class="row mt-4">
						<div class="col-12">
							<nav aria-label="User directory pagination">
								<ul class="pagination justify-content-center">
									<?php if ( $paged > 1 ) : ?>
										<li class="page-item">
											<a class="page-link" href="?paged=<?php echo $paged - 1; ?>">
												<i class="fa fa-chevron-left"></i> Previous
											</a>
										</li>
									<?php endif; ?>

									<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
										<li class="page-item <?php echo $i === $paged ? 'active' : ''; ?>">
											<a class="page-link" href="?paged=<?php echo $i; ?>"><?php echo $i; ?></a>
										</li>
									<?php endfor; ?>

									<?php if ( $paged < $total_pages ) : ?>
										<li class="page-item">
											<a class="page-link" href="?paged=<?php echo $paged + 1; ?>">
												Next <i class="fa fa-chevron-right"></i>
											</a>
										</li>
									<?php endif; ?>
								</ul>
							</nav>
						</div>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</div>
</div>

<?php
get_footer();
