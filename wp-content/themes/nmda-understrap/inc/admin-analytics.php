<?php
/**
 * Analytics Dashboard - Admin Page
 *
 * @package NMDA_Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include query functions
require_once get_stylesheet_directory() . '/inc/admin-analytics-queries.php';

/**
 * Register Analytics admin menu
 */
function nmda_register_analytics_menu() {
	add_menu_page(
		__( 'Analytics', 'nmda-understrap' ),
		__( 'Analytics', 'nmda-understrap' ),
		'manage_options',
		'nmda-analytics',
		'nmda_render_analytics_dashboard',
		'dashicons-chart-bar',
		26
	);
}
add_action( 'admin_menu', 'nmda_register_analytics_menu' );

/**
 * Enqueue analytics scripts and styles
 */
function nmda_enqueue_analytics_assets( $hook ) {
	// Debug: Log the hook to see what page we're on
	error_log( 'NMDA Analytics Hook: ' . $hook );

	// Check if we're on the analytics page
	if ( $hook !== 'toplevel_page_nmda-analytics' ) {
		error_log( 'NMDA Analytics: Not on analytics page, skipping enqueue' );
		return;
	}

	error_log( 'NMDA Analytics: Enqueueing scripts and styles' );

	// Chart.js
	wp_enqueue_script(
		'chartjs',
		'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
		array(),
		'4.4.0',
		true
	);

	// flatpickr for date range picker
	wp_enqueue_style(
		'flatpickr',
		'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
		array(),
		'4.6.13'
	);

	wp_enqueue_script(
		'flatpickr',
		'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js',
		array(),
		'4.6.13',
		true
	);

	// Get file paths
	$css_path = get_stylesheet_directory() . '/assets/css/admin-analytics.css';
	$js_path = get_stylesheet_directory() . '/assets/js/admin-analytics.js';

	// Check if files exist and get version
	$css_version = file_exists( $css_path ) ? filemtime( $css_path ) : '1.0.0';
	$js_version = file_exists( $js_path ) ? filemtime( $js_path ) : '1.0.0';

	error_log( 'NMDA Analytics CSS exists: ' . ( file_exists( $css_path ) ? 'Yes' : 'No' ) );
	error_log( 'NMDA Analytics JS exists: ' . ( file_exists( $js_path ) ? 'Yes' : 'No' ) );

	// Custom analytics styles
	wp_enqueue_style(
		'nmda-analytics',
		get_stylesheet_directory_uri() . '/assets/css/admin-analytics.css',
		array(),
		$css_version
	);

	// Custom analytics scripts
	wp_enqueue_script(
		'nmda-analytics',
		get_stylesheet_directory_uri() . '/assets/js/admin-analytics.js',
		array( 'jquery', 'chartjs', 'flatpickr' ),
		$js_version,
		true
	);

	// Localize script
	wp_localize_script( 'nmda-analytics', 'nmdaAnalytics', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'nmda_analytics_nonce' ),
	) );

	error_log( 'NMDA Analytics: All scripts and styles enqueued' );
}
add_action( 'admin_enqueue_scripts', 'nmda_enqueue_analytics_assets' );

/**
 * Render Analytics Dashboard
 */
function nmda_render_analytics_dashboard() {
	// Get all statistics with caching
	$user_stats = nmda_get_cached_analytics( 'user_stats', 'nmda_get_user_stats' );
	$business_stats = nmda_get_cached_analytics( 'business_stats', 'nmda_get_business_stats' );
	$reimbursement_stats = nmda_get_cached_analytics( 'reimbursement_stats', function() {
		return nmda_get_analytics_reimbursement_stats( 'current' );
	} );
	$messaging_stats = nmda_get_cached_analytics( 'messaging_stats', 'nmda_get_messaging_stats' );

	// Get chart data
	$user_registrations = nmda_get_cached_analytics( 'user_registrations', 'nmda_get_user_registrations_by_month' );
	$applications_by_month = nmda_get_cached_analytics( 'applications_by_month', function() {
		return nmda_get_applications_by_month( 12 );
	} );
	?>
	<div class="wrap nmda-analytics-dashboard">
		<h1>
			<span class="dashicons dashicons-chart-bar"></span>
			<?php esc_html_e( 'NMDA Portal Analytics', 'nmda-understrap' ); ?>
		</h1>

		<!-- JavaScript Loading Indicator -->
		<div id="nmda-js-check" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 20px 0;">
			<strong>⚠️ JavaScript Status:</strong> <span id="js-status">Checking...</span>
			<div id="js-details" style="margin-top: 8px; font-size: 12px; color: #666;"></div>
		</div>

		<!-- Dashboard Header with Refresh Button -->
		<div class="nmda-analytics-header">
			<div class="nmda-analytics-filters">
				<label for="analytics-date-range"><?php esc_html_e( 'Date Range:', 'nmda-understrap' ); ?></label>
				<input type="text" id="analytics-date-range" class="analytics-date-picker" placeholder="Select date range">
				<button type="button" class="button" id="apply-date-filter">
					<?php esc_html_e( 'Apply', 'nmda-understrap' ); ?>
				</button>
			</div>
			<div class="nmda-analytics-actions">
				<button type="button" class="button" id="refresh-analytics">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Refresh Data', 'nmda-understrap' ); ?>
				</button>
				<button type="button" class="button button-primary" id="export-analytics">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Report', 'nmda-understrap' ); ?>
				</button>
			</div>
		</div>

		<!-- Key Metrics Cards -->
		<div class="nmda-metrics-grid">
			<!-- User Metrics -->
			<div class="nmda-metric-card">
				<div class="metric-icon">
					<span class="dashicons dashicons-groups"></span>
				</div>
				<div class="metric-content">
					<div class="metric-value"><?php echo number_format( $user_stats['total_users'] ); ?></div>
					<div class="metric-label"><?php esc_html_e( 'Total Users', 'nmda-understrap' ); ?></div>
				</div>
			</div>

			<div class="nmda-metric-card">
				<div class="metric-icon metric-success">
					<span class="dashicons dashicons-admin-users"></span>
				</div>
				<div class="metric-content">
					<div class="metric-value"><?php echo number_format( $user_stats['new_this_month'] ); ?></div>
					<div class="metric-label"><?php esc_html_e( 'New This Month', 'nmda-understrap' ); ?></div>
					<?php if ( $user_stats['growth_rate'] != 0 ) : ?>
						<div class="metric-change <?php echo $user_stats['growth_rate'] > 0 ? 'positive' : 'negative'; ?>">
							<?php echo $user_stats['growth_rate'] > 0 ? '+' : ''; ?><?php echo $user_stats['growth_rate']; ?>%
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="nmda-metric-card">
				<div class="metric-icon metric-info">
					<span class="dashicons dashicons-businessman"></span>
				</div>
				<div class="metric-content">
					<div class="metric-value"><?php echo number_format( $user_stats['active_users'] ); ?></div>
					<div class="metric-label"><?php esc_html_e( 'Active Users (30d)', 'nmda-understrap' ); ?></div>
				</div>
			</div>

			<!-- Business Metrics -->
			<div class="nmda-metric-card">
				<div class="metric-icon metric-primary">
					<span class="dashicons dashicons-store"></span>
				</div>
				<div class="metric-content">
					<div class="metric-value"><?php echo number_format( $business_stats['total'] ); ?></div>
					<div class="metric-label"><?php esc_html_e( 'Total Businesses', 'nmda-understrap' ); ?></div>
				</div>
			</div>

			<div class="nmda-metric-card">
				<div class="metric-icon metric-warning">
					<span class="dashicons dashicons-clock"></span>
				</div>
				<div class="metric-content">
					<div class="metric-value"><?php echo number_format( $business_stats['pending'] ); ?></div>
					<div class="metric-label"><?php esc_html_e( 'Pending Applications', 'nmda-understrap' ); ?></div>
				</div>
			</div>

			<!-- Reimbursement Metrics -->
			<div class="nmda-metric-card">
				<div class="metric-icon metric-success">
					<span class="dashicons dashicons-money-alt"></span>
				</div>
				<div class="metric-content">
					<div class="metric-value">$<?php echo number_format( $reimbursement_stats['total_approved_amount'], 2 ); ?></div>
					<div class="metric-label"><?php esc_html_e( 'Total Approved (FY)', 'nmda-understrap' ); ?></div>
					<div class="metric-subtext">
						<?php echo number_format( $reimbursement_stats['total_approved'] ); ?> <?php esc_html_e( 'reimbursements', 'nmda-understrap' ); ?>
					</div>
				</div>
			</div>

			<div class="nmda-metric-card">
				<div class="metric-icon metric-info">
					<span class="dashicons dashicons-yes-alt"></span>
				</div>
				<div class="metric-content">
					<div class="metric-value"><?php echo $reimbursement_stats['approval_rate']; ?>%</div>
					<div class="metric-label"><?php esc_html_e( 'Approval Rate', 'nmda-understrap' ); ?></div>
				</div>
			</div>

			<!-- Messaging Metrics -->
			<div class="nmda-metric-card">
				<div class="metric-icon metric-primary">
					<span class="dashicons dashicons-email"></span>
				</div>
				<div class="metric-content">
					<div class="metric-value"><?php echo number_format( $messaging_stats['total_messages'] ); ?></div>
					<div class="metric-label"><?php esc_html_e( 'Total Messages', 'nmda-understrap' ); ?></div>
					<?php if ( $messaging_stats['unread_messages'] > 0 ) : ?>
						<div class="metric-subtext metric-warning">
							<?php echo number_format( $messaging_stats['unread_messages'] ); ?> <?php esc_html_e( 'unread', 'nmda-understrap' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Charts Row -->
		<div class="nmda-charts-row">
			<!-- User Growth Chart -->
			<div class="nmda-chart-card">
				<div class="chart-card-header">
					<h2><?php esc_html_e( 'User Registrations (Last 12 Months)', 'nmda-understrap' ); ?></h2>
					<button type="button" class="button button-small" data-export="user-growth">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'nmda-understrap' ); ?>
					</button>
				</div>
				<div class="chart-container">
					<canvas id="user-growth-chart"></canvas>
				</div>
			</div>

			<!-- Business Applications Chart -->
			<div class="nmda-chart-card">
				<div class="chart-card-header">
					<h2><?php esc_html_e( 'Business Applications by Month', 'nmda-understrap' ); ?></h2>
					<button type="button" class="button button-small" data-export="applications">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'nmda-understrap' ); ?>
					</button>
				</div>
				<div class="chart-container">
					<canvas id="applications-chart"></canvas>
				</div>
			</div>

			<!-- Business Classifications Chart -->
			<div class="nmda-chart-card">
				<div class="chart-card-header">
					<h2><?php esc_html_e( 'Business Classifications', 'nmda-understrap' ); ?></h2>
					<button type="button" class="button button-small" data-export="classifications">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'nmda-understrap' ); ?>
					</button>
				</div>
				<div class="chart-container">
					<canvas id="classifications-chart"></canvas>
				</div>
			</div>
		</div>

		<!-- Data Tables Section -->
		<div class="nmda-analytics-tabs">
			<div class="nav-tab-wrapper">
				<a href="#tab-users" class="nav-tab nav-tab-active"><?php esc_html_e( 'Users', 'nmda-understrap' ); ?></a>
				<a href="#tab-businesses" class="nav-tab"><?php esc_html_e( 'Businesses', 'nmda-understrap' ); ?></a>
				<a href="#tab-reimbursements" class="nav-tab"><?php esc_html_e( 'Reimbursements', 'nmda-understrap' ); ?></a>
				<a href="#tab-activity" class="nav-tab"><?php esc_html_e( 'Activity', 'nmda-understrap' ); ?></a>
			</div>

			<!-- Users Tab -->
			<div id="tab-users" class="tab-content active">
				<?php nmda_render_users_table(); ?>
			</div>

			<!-- Businesses Tab -->
			<div id="tab-businesses" class="tab-content">
				<?php nmda_render_businesses_table(); ?>
			</div>

			<!-- Reimbursements Tab -->
			<div id="tab-reimbursements" class="tab-content">
				<?php nmda_render_reimbursements_table( $reimbursement_stats['fiscal_year'] ); ?>
			</div>

			<!-- Activity Tab -->
			<div id="tab-activity" class="tab-content">
				<?php nmda_render_activity_feed(); ?>
			</div>
		</div>

		<!-- Hidden data for charts -->
		<script type="application/json" id="user-registrations-data">
			<?php echo wp_json_encode( $user_registrations ); ?>
		</script>
		<script type="application/json" id="applications-data">
			<?php echo wp_json_encode( $applications_by_month ); ?>
		</script>
		<script type="application/json" id="business-classifications-data">
			<?php echo wp_json_encode( $business_stats['classifications'] ); ?>
		</script>
	</div>
	<?php
}

/**
 * Render Users Data Table
 */
function nmda_render_users_table() {
	$active_users = nmda_get_most_active_users( 50 );
	?>
	<div class="nmda-table-header">
		<h3><?php esc_html_e( 'Most Active Users', 'nmda-understrap' ); ?></h3>
		<button type="button" class="button" data-export="users-table">
			<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'nmda-understrap' ); ?>
		</button>
	</div>
	<table class="wp-list-table widefat fixed striped nmda-analytics-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'User', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Email', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Businesses', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Last Login', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Registered', 'nmda-understrap' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $active_users ) ) : ?>
				<?php foreach ( $active_users as $user ) : ?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user['ID'] ) ); ?>">
									<?php echo esc_html( $user['display_name'] ); ?>
								</a>
							</strong>
						</td>
						<td><?php echo esc_html( $user['user_email'] ); ?></td>
						<td><?php echo intval( $user['business_count'] ); ?></td>
						<td>
							<?php
							if ( ! empty( $user['last_login'] ) ) {
								echo esc_html( human_time_diff( strtotime( $user['last_login'] ), current_time( 'timestamp' ) ) . ' ago' );
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo esc_html( date( 'M j, Y', strtotime( $user['user_registered'] ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No users found.', 'nmda-understrap' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render Businesses Data Table
 */
function nmda_render_businesses_table() {
	$businesses = nmda_get_business_directory_report();
	?>
	<div class="nmda-table-header">
		<h3><?php esc_html_e( 'Business Directory', 'nmda-understrap' ); ?></h3>
		<button type="button" class="button" data-export="businesses-table">
			<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'nmda-understrap' ); ?>
		</button>
	</div>
	<table class="wp-list-table widefat fixed striped nmda-analytics-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Business Name', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Classification', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Status', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'County', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Submitted', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Days to Approve', 'nmda-understrap' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $businesses ) ) : ?>
				<?php foreach ( $businesses as $business ) : ?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $business['id'] . '&action=edit' ) ); ?>">
									<?php echo esc_html( $business['name'] ); ?>
								</a>
							</strong>
						</td>
						<td><?php echo esc_html( $business['classification'] ); ?></td>
						<td>
							<span class="status-badge status-<?php echo esc_attr( $business['status'] ); ?>">
								<?php echo esc_html( ucfirst( $business['status'] ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $business['county'] ); ?></td>
						<td><?php echo esc_html( date( 'M j, Y', strtotime( $business['submitted'] ) ) ); ?></td>
						<td><?php echo $business['days_to_approve'] ? esc_html( $business['days_to_approve'] ) . ' days' : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No businesses found.', 'nmda-understrap' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render Reimbursements Data Table
 */
function nmda_render_reimbursements_table( $fiscal_year ) {
	$status_filter = isset( $_GET['reimbursement_status'] ) ? sanitize_text_field( $_GET['reimbursement_status'] ) : '';
	$reimbursements = nmda_get_reimbursements_table_data( $fiscal_year, $status_filter, 100 );
	?>
	<div class="nmda-table-header">
		<h3><?php echo esc_html( sprintf( __( 'Reimbursements - Fiscal Year %s', 'nmda-understrap' ), $fiscal_year ) ); ?></h3>
		<div class="nmda-table-actions">
			<select id="reimbursement-status-filter" class="nmda-filter-select">
				<option value=""><?php esc_html_e( 'All Statuses', 'nmda-understrap' ); ?></option>
				<option value="approved" <?php selected( $status_filter, 'approved' ); ?>><?php esc_html_e( 'Approved', 'nmda-understrap' ); ?></option>
				<option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'Pending', 'nmda-understrap' ); ?></option>
				<option value="rejected" <?php selected( $status_filter, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'nmda-understrap' ); ?></option>
			</select>
			<button type="button" class="button" data-export="reimbursements-table">
				<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'nmda-understrap' ); ?>
			</button>
		</div>
	</div>

	<table class="nmda-data-table" id="reimbursements-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Business', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Type', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Status', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Amount Requested', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Amount Approved', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Submitted By', 'nmda-understrap' ); ?></th>
				<th><?php esc_html_e( 'Date Submitted', 'nmda-understrap' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $reimbursements ) ) : ?>
				<?php foreach ( $reimbursements as $reimbursement ) : ?>
					<tr>
						<td><?php echo esc_html( $reimbursement['id'] ); ?></td>
						<td>
							<?php if ( ! empty( $reimbursement['business_name'] ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $reimbursement['business_id'] . '&action=edit' ) ); ?>">
									<?php echo esc_html( $reimbursement['business_name'] ); ?>
								</a>
							<?php else : ?>
								<?php esc_html_e( 'N/A', 'nmda-understrap' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( ucfirst( $reimbursement['type'] ?? 'N/A' ) ); ?></td>
						<td>
							<span class="status-badge status-<?php echo esc_attr( $reimbursement['status'] ?? 'unknown' ); ?>">
								<?php echo esc_html( ucfirst( $reimbursement['status'] ?? 'Unknown' ) ); ?>
							</span>
						</td>
						<td>
							<?php
							if ( ! empty( $reimbursement['amount_requested'] ) ) {
								echo esc_html( '$' . number_format( floatval( $reimbursement['amount_requested'] ), 2 ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<?php
							if ( ! empty( $reimbursement['amount_approved'] ) ) {
								echo esc_html( '$' . number_format( floatval( $reimbursement['amount_approved'] ), 2 ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo esc_html( $reimbursement['submitted_by'] ?? 'N/A' ); ?></td>
						<td>
							<?php
							if ( ! empty( $reimbursement['created_at'] ) ) {
								echo esc_html( date( 'M j, Y', strtotime( $reimbursement['created_at'] ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="8" class="no-data">
						<?php esc_html_e( 'No reimbursements found for this fiscal year.', 'nmda-understrap' ); ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render Activity Feed
 */
function nmda_render_activity_feed() {
	$recent_activity = nmda_get_recent_activity( 50 );
	?>
	<div class="nmda-table-header">
		<h3><?php esc_html_e( 'Recent Activity', 'nmda-understrap' ); ?></h3>
	</div>
	<div class="nmda-activity-feed">
		<?php if ( ! empty( $recent_activity ) ) : ?>
			<?php foreach ( $recent_activity as $event ) : ?>
				<div class="activity-item">
					<div class="activity-icon">
						<?php if ( $event['event_type'] === 'user_registered' ) : ?>
							<span class="dashicons dashicons-admin-users"></span>
						<?php elseif ( $event['event_type'] === 'business_application' ) : ?>
							<span class="dashicons dashicons-store"></span>
						<?php endif; ?>
					</div>
					<div class="activity-content">
						<?php if ( $event['event_type'] === 'user_registered' ) : ?>
							<strong><?php echo esc_html( $event['display_name'] ); ?></strong> <?php esc_html_e( 'registered', 'nmda-understrap' ); ?>
							<div class="activity-meta"><?php echo esc_html( $event['user_email'] ); ?></div>
						<?php elseif ( $event['event_type'] === 'business_application' ) : ?>
							<strong><?php echo esc_html( $event['title'] ); ?></strong> <?php esc_html_e( 'submitted', 'nmda-understrap' ); ?>
							<div class="activity-meta">
								<span class="status-badge status-<?php echo esc_attr( $event['status'] ); ?>">
									<?php echo esc_html( ucfirst( $event['status'] ) ); ?>
								</span>
							</div>
						<?php endif; ?>
					</div>
					<div class="activity-time">
						<?php echo esc_html( human_time_diff( strtotime( $event['event_date'] ), current_time( 'timestamp' ) ) . ' ago' ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No recent activity.', 'nmda-understrap' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * AJAX handler to refresh analytics cache
 */
function nmda_ajax_refresh_analytics() {
	check_ajax_referer( 'nmda_analytics_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
	}

	// Clear cache
	nmda_clear_analytics_cache();

	wp_send_json_success( array( 'message' => 'Analytics cache refreshed successfully.' ) );
}
add_action( 'wp_ajax_nmda_refresh_analytics', 'nmda_ajax_refresh_analytics' );
