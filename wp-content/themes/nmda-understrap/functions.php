<?php
/**
 * NMDA Understrap Child Theme Functions
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Theme Constants
 */
define( 'NMDA_THEME_VERSION', '1.0.2' );
define( 'NMDA_THEME_DIR', get_stylesheet_directory() );
define( 'NMDA_THEME_URI', get_stylesheet_directory_uri() );

/**
 * Enqueue parent and child theme styles
 */
function nmda_enqueue_styles() {
    // Parent theme styles
    wp_enqueue_style( 'parent-understrap-styles', get_template_directory_uri() . '/css/theme.min.css', array(), NMDA_THEME_VERSION );

    // Child theme styles
    wp_enqueue_style( 'nmda-child-styles', NMDA_THEME_URI . '/style.css', array( 'parent-understrap-styles' ), NMDA_THEME_VERSION );

    // Custom CSS
    wp_enqueue_style( 'nmda-custom-styles', NMDA_THEME_URI . '/assets/css/custom.css', array( 'nmda-child-styles' ), NMDA_THEME_VERSION );
}
add_action( 'wp_enqueue_scripts', 'nmda_enqueue_styles' );

/**
 * Enqueue child theme scripts
 */
function nmda_enqueue_scripts() {

    wp_enqueue_style('bootstrap-icons-latest', '//cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css');
    wp_enqueue_style( 'nmda-dashboard-styles', NMDA_THEME_URI . '/assets/css/dashboard.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
    
    // Homepage styles (front-page.php)
    if ( is_front_page() && ! is_user_logged_in() ) {
        wp_enqueue_style( 'nmda-homepage-styles', NMDA_THEME_URI . '/assets/css/homepage.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
    }

    // Login/Register/Authentication pages styles
    if ( is_page_template( array( 'page-login.php', 'page-register.php', 'page-forgot-password.php', 'page-reset-password.php' ) ) ) {
        wp_enqueue_style( 'nmda-login-register-styles', NMDA_THEME_URI . '/assets/css/login-register.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-login-register-js', NMDA_THEME_URI . '/assets/js/login-register.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
    }

    // Business Application page
    if ( is_page_template( 'page-business-application.php' ) ) {
        wp_enqueue_style( 'nmda-dashboard-styles', NMDA_THEME_URI . '/assets/css/dashboard.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_style( 'nmda-reimbursement-forms-styles', NMDA_THEME_URI . '/assets/css/reimbursement-forms.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-business-application-js', NMDA_THEME_URI . '/assets/js/business-application.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
    }

    // Custom JavaScript
    wp_enqueue_script( 'nmda-custom-js', NMDA_THEME_URI . '/assets/js/custom.js', array( 'jquery' ), NMDA_THEME_VERSION, true );

    // Localize script with AJAX URL and nonce
    wp_localize_script( 'nmda-custom-js', 'nmdaAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'nmda-ajax-nonce' ),
    ) );

    // Messages CSS for navigation badge (load for all logged-in users)
    if ( is_user_logged_in() ) {
        wp_enqueue_style( 'nmda-messages-nav-badge', NMDA_THEME_URI . '/assets/css/messages.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
    }

    // Resource Center styles
    if ( is_page_template( 'page-resource-center.php' ) ) {
        //wp_enqueue_style( 'nmda-dashboard-styles', NMDA_THEME_URI . '/assets/css/dashboard.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_style( 'nmda-resource-center-styles', NMDA_THEME_URI . '/assets/css/resource-center.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-resource-center', NMDA_THEME_URI . '/assets/js/resource-center.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
    }

    // Edit Profile styles and scripts
    if ( is_page_template( 'page-edit-profile.php' ) ) {
        wp_enqueue_style( 'nmda-reimbursement-forms-styles', NMDA_THEME_URI . '/assets/css/reimbursement-forms.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_style( 'nmda-edit-profile-styles', NMDA_THEME_URI . '/assets/css/edit-profile.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_style( 'nmda-address-management-styles', NMDA_THEME_URI . '/assets/css/address-management.css', array( 'nmda-edit-profile-styles' ), NMDA_THEME_VERSION );

        wp_enqueue_script( 'nmda-edit-profile', NMDA_THEME_URI . '/assets/js/edit-profile.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
        wp_enqueue_script( 'nmda-address-management', NMDA_THEME_URI . '/assets/js/address-management.js', array( 'jquery' ), NMDA_THEME_VERSION, true );

        // FIX: Localize the edit-profile script with AJAX URL and dashboard URL
        wp_localize_script( 'nmda-edit-profile', 'nmdaAjax', array(
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'dashboardUrl' => home_url( '/dashboard' ),
            'nonce'        => wp_create_nonce( 'nmda-ajax-nonce' ),
        ) );
    }

    // Reimbursement forms scripts and styles
    if ( is_page_template( array( 'page-reimbursement-lead.php', 'page-reimbursement-advertising.php', 'page-reimbursement-labels.php', 'page-my-reimbursements.php' ) ) ) {
        //wp_enqueue_style( 'nmda-dashboard-styles', NMDA_THEME_URI . '/assets/css/dashboard.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_style( 'nmda-reimbursement-forms-styles', NMDA_THEME_URI . '/assets/css/reimbursement-forms.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-reimbursement-forms', NMDA_THEME_URI . '/assets/js/reimbursement-forms.js', array( 'jquery' ), NMDA_THEME_VERSION, true );

        // Localize script with AJAX URL and dashboard URL
        wp_localize_script( 'nmda-reimbursement-forms', 'nmdaData', array(
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'dashboardUrl' => home_url( '/dashboard' ),
        ) );
    }

    // Business Directory styles
    if ( is_post_type_archive( 'nmda_business' ) ) {
        wp_enqueue_style( 'nmda-directory-styles', NMDA_THEME_URI . '/assets/css/directory.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
    }

    // Single Business Profile styles
    if ( is_singular( 'nmda_business' ) ) {
        wp_enqueue_style( 'nmda-business-profile-styles', NMDA_THEME_URI . '/assets/css/business-profile.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
    }

    // User Directory styles and scripts (admin only)
    if ( is_page_template( 'page-user-directory.php' ) ) {
        wp_enqueue_style( 'nmda-user-directory-styles', NMDA_THEME_URI . '/assets/css/user-directory.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-user-directory-js', NMDA_THEME_URI . '/assets/js/user-directory.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
    }

    // User Profile styles (admin only)
    if ( is_page_template( 'page-user-profile.php' ) || ( is_page() && isset( $_GET['user_id'] ) ) ) {
        wp_enqueue_style( 'nmda-user-profile-styles', NMDA_THEME_URI . '/assets/css/user-profile.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
    }

    // Manage Business Users page
    if ( is_page_template( 'page-manage-business-users.php' ) ) {
        wp_enqueue_style( 'nmda-user-management-styles', NMDA_THEME_URI . '/assets/css/user-management.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-user-management-js', NMDA_THEME_URI . '/assets/js/user-management.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
    }

    // Accept Invitation page
    if ( is_page_template( 'page-accept-invitation.php' ) ) {
        wp_enqueue_style( 'nmda-user-management-styles', NMDA_THEME_URI . '/assets/css/user-management.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
    }

    // Messages page
    if ( is_page_template( 'page-messages.php' ) ) {
        wp_enqueue_style( 'nmda-messages-styles', NMDA_THEME_URI . '/assets/css/messages.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-messages-js', NMDA_THEME_URI . '/assets/js/messages.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
    }
}
add_action( 'wp_enqueue_scripts', 'nmda_enqueue_scripts' );

/**
 * Enqueue admin styles and scripts
 */
function nmda_enqueue_admin_scripts( $hook ) {
    global $post_type;

    // Only load on business edit screen and dashboard
    if ( ( $hook === 'post.php' || $hook === 'post-new.php' || $hook === 'index.php' ) &&
         ( $post_type === 'nmda_business' || $hook === 'index.php' ) ) {

        // Field approvals CSS
        wp_enqueue_style(
            'nmda-field-approvals',
            NMDA_THEME_URI . '/assets/css/field-approvals.css',
            array(),
            NMDA_THEME_VERSION
        );

        // Field approvals JavaScript
        wp_enqueue_script(
            'nmda-field-approvals',
            NMDA_THEME_URI . '/assets/js/field-approvals.js',
            array( 'jquery' ),
            NMDA_THEME_VERSION,
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'nmda_enqueue_admin_scripts' );

/**
 * Load core modules
 */
require_once NMDA_THEME_DIR . '/inc/setup.php';
//require_once NMDA_THEME_DIR . '/inc/custom-post-types.php';
require_once NMDA_THEME_DIR . '/inc/user-management.php';
require_once NMDA_THEME_DIR . '/inc/business-management.php';
require_once NMDA_THEME_DIR . '/inc/reimbursements.php';
require_once NMDA_THEME_DIR . '/inc/resources.php';
require_once NMDA_THEME_DIR . '/inc/api-integrations.php';
require_once NMDA_THEME_DIR . '/inc/database-schema.php';
//require_once NMDA_THEME_DIR . '/inc/acf-field-groups.php';
require_once NMDA_THEME_DIR . '/inc/product-taxonomy.php';
require_once NMDA_THEME_DIR . '/inc/application-forms.php';
require_once NMDA_THEME_DIR . '/inc/admin-approval.php';
require_once NMDA_THEME_DIR . '/inc/admin-reimbursements.php';
require_once NMDA_THEME_DIR . '/inc/admin-field-approvals.php';
require_once NMDA_THEME_DIR . '/inc/admin-analytics.php';
require_once NMDA_THEME_DIR . '/inc/communications.php';
/**
 * Add custom rewrite rules for reimbursement detail pages
 */
function nmda_add_reimbursement_rewrite_rules() {
    add_rewrite_rule(
        '^dashboard/reimbursements/([0-9]+)/?$',
        'index.php?nmda_reimbursement_detail=1&reimbursement_id=$matches[1]',
        'top'
    );
}
add_action( 'init', 'nmda_add_reimbursement_rewrite_rules' );

/**
 * Add custom query vars
 */
function nmda_add_query_vars( $vars ) {
    $vars[] = 'reimbursement_id';
    $vars[] = 'nmda_reimbursement_detail';
    return $vars;
}
add_filter( 'query_vars', 'nmda_add_query_vars', 10, 1 );

/**
 * Alternative method: Add to public query vars directly
 */
function nmda_add_public_query_vars() {
    global $wp;
    $wp->add_query_var('reimbursement_id');
    $wp->add_query_var('nmda_reimbursement_detail');
}
add_action( 'init', 'nmda_add_public_query_vars', 10 );

/**
 * Handle custom reimbursement detail template
 */
function nmda_reimbursement_detail_template( $template ) {
    if ( get_query_var( 'nmda_reimbursement_detail' ) ) {
        $new_template = locate_template( array( 'page-reimbursement-detail.php' ) );
        if ( ! empty( $new_template ) ) {
            return $new_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'nmda_reimbursement_detail_template' );

/**
 * Theme activation hook - create custom database tables
 */
function nmda_theme_activation() {
    // Create custom database tables
    nmda_create_custom_tables();

    // Flush rewrite rules to register custom URLs
    nmda_add_reimbursement_rewrite_rules();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'nmda_theme_activation' );

/**
 * Temporary: Force database upgrade to v1.2 (adds sender_id column)
 * This will run once on next admin page load and can be removed after
 */
function nmda_force_db_upgrade_1_2() {
    global $wpdb;

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Check if sender_id column already exists
    $table = $wpdb->prefix . 'nmda_communications';
    $column_exists = $wpdb->get_results(
        "SHOW COLUMNS FROM $table LIKE 'sender_id'"
    );

    if ( ! empty( $column_exists ) ) {
        return; // Column already exists, nothing to do
    }

    // Add sender_id column (step 1)
    $wpdb->query(
        "ALTER TABLE $table ADD COLUMN sender_id bigint(20) DEFAULT NULL AFTER admin_id"
    );

    // Add index (step 2)
    $wpdb->query(
        "ALTER TABLE $table ADD INDEX idx_sender_id (sender_id)"
    );

    // Populate sender_id for existing messages
    // Admin messages: where admin_id is actually an admin role
    $wpdb->query(
        "UPDATE $table c
        INNER JOIN {$wpdb->prefix}users u ON u.ID = c.admin_id
        INNER JOIN {$wpdb->prefix}usermeta um ON um.user_id = u.ID AND um.meta_key = 'wp_capabilities'
        SET c.sender_id = c.admin_id
        WHERE c.admin_id IS NOT NULL
        AND um.meta_value LIKE '%administrator%'"
    );

    // Member messages: remaining ones
    $wpdb->query(
        "UPDATE $table
        SET sender_id = user_id
        WHERE sender_id IS NULL AND user_id IS NOT NULL"
    );

    // Update database version
    update_option( 'nmda_db_version', '1.2' );

    // Add admin notice
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>NMDA Database Upgraded:</strong> Messaging system updated to v1.2 (sender_id column added successfully).</p>';
        echo '</div>';
    } );
}
add_action( 'admin_init', 'nmda_force_db_upgrade_1_2', 1 );

/**
 * Add theme support features
 */
function nmda_theme_setup() {

    add_theme_support( 'title-tag' );

    // Add support for custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Add support for featured images
    add_theme_support( 'post-thumbnails' );

    // Register navigation menus
    register_nav_menus( array(
        'member-dashboard' => __( 'Member Dashboard Menu', 'nmda-understrap' ),
        'member-footer'    => __( 'Member Footer Menu', 'nmda-understrap' ),
    ) );
}
add_action( 'after_setup_theme', 'nmda_theme_setup' );


/**
 * Changes the custom logo link URL for logged-in users.
 *
 * @param string $html The HTML for the custom logo.
 * @return string The modified HTML.
 */
function nmda_custom_logo_link_for_logged_in_users( $html ) {
    
    // Check if the user is logged in
    if ( is_user_logged_in() ) {
        
        // Get the default home URL (what WordPress would normally use)
        // We use esc_url() just as WordPress core does.
        $default_url = esc_url( home_url( '/' ) );
        
        // Define your new dashboard URL
        $dashboard_url = esc_url( home_url( '/dashboard/' ) );
        
        // Find the default URL in the href attribute and replace it
        // This is safer than a simple str_replace on just the URL.
        $find_string = 'href="' . $default_url . '"';
        $replace_string = 'href="' . $dashboard_url . '"';
        
        $html = str_replace( $find_string, $replace_string, $html );
    }
    
    // Return the modified (or original) HTML
    return $html;
}
add_filter( 'get_custom_logo', 'nmda_custom_logo_link_for_logged_in_users' );

/**
 * ========================================
 * Custom Login/Registration URL Redirects
 * ========================================
 */

/**
 * Redirect wp-login.php to custom login page
 *
 * BYPASS: Add ?force_wp_login=1 to access wp-login.php directly
 * Example: /wp-login.php?force_wp_login=1
 */
function nmda_redirect_login_page() {
	// Allow bypass with query parameter for troubleshooting
	if ( isset( $_GET['force_wp_login'] ) ) {
		return;
	}

	$login_page = home_url( '/login/' );
	$page_viewed = basename( $_SERVER['REQUEST_URI'] );

	// Only redirect if accessing wp-login.php directly
	if ( $page_viewed == 'wp-login.php' && $_SERVER['REQUEST_METHOD'] == 'GET' ) {
		// Preserve redirect_to parameter if present
		$redirect_to = isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : '';

		// Handle different actions
		if ( isset( $_GET['action'] ) ) {
			$action = $_GET['action'];

			switch ( $action ) {
				case 'register':
					wp_redirect( home_url( '/register/' ) );
					exit;

				case 'lostpassword':
				case 'retrievepassword':
					wp_redirect( home_url( '/forgot-password/' ) );
					exit;

				case 'rp':
				case 'resetpass':
					// Preserve reset key and login
					$key = isset( $_GET['key'] ) ? $_GET['key'] : '';
					$login = isset( $_GET['login'] ) ? $_GET['login'] : '';
					wp_redirect( home_url( '/reset-password/?key=' . $key . '&login=' . rawurlencode( $login ) ) );
					exit;

				case 'logout':
					// Let WordPress handle logout normally
					return;

				default:
					// Redirect to custom login page
					if ( ! empty( $redirect_to ) ) {
						wp_redirect( $login_page . '?redirect_to=' . urlencode( $redirect_to ) );
					} else {
						wp_redirect( $login_page );
					}
					exit;
			}
		} else {
			// No action, just redirect to login with redirect_to preserved
			if ( ! empty( $redirect_to ) ) {
				wp_redirect( $login_page . '?redirect_to=' . urlencode( $redirect_to ) );
			} else {
				wp_redirect( $login_page );
			}
			exit;
		}
	}
}
add_action( 'init', 'nmda_redirect_login_page' );

/**
 * Customize the login URL throughout WordPress
 * Replaces wp-login.php with our custom login page
 */
function nmda_login_url( $login_url, $redirect, $force_reauth ) {
	$login_url = home_url( '/login/' );

	if ( ! empty( $redirect ) ) {
		$login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
	}

	if ( $force_reauth ) {
		$login_url = add_query_arg( 'reauth', '1', $login_url );
	}

	return $login_url;
}
add_filter( 'login_url', 'nmda_login_url', 10, 3 );

/**
 * Customize the registration URL
 */
function nmda_register_url( $register_url ) {
	return home_url( '/register/' );
}
add_filter( 'register_url', 'nmda_register_url' );

/**
 * Customize the lost password URL
 */
function nmda_lostpassword_url( $lostpassword_url ) {
	return home_url( '/forgot-password/' );
}
add_filter( 'lostpassword_url', 'nmda_lostpassword_url', 10, 0 );

/**
 * Redirect after logout
 * Sends users to homepage instead of wp-login.php
 */
function nmda_logout_redirect() {
	wp_redirect( home_url( '/' ) );
	exit();
}
add_action( 'wp_logout', 'nmda_logout_redirect' );

/**
 * Customize logout URL to redirect to homepage
 */
function nmda_logout_url( $logout_url, $redirect ) {
	// If no redirect specified, go to homepage
	if ( empty( $redirect ) ) {
		$redirect = home_url( '/' );
	}
	return add_query_arg( 'redirect_to', urlencode( $redirect ), $logout_url );
}
add_filter( 'logout_url', 'nmda_logout_url', 10, 2 );

/**
 * Disable admin bar for non-admin users
 */
function nmda_disable_admin_bar_for_non_admins() {
	if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
		show_admin_bar( false );
	}
}
add_action( 'after_setup_theme', 'nmda_disable_admin_bar_for_non_admins' );

/**
 * Redirect non-admin users away from the admin dashboard
 * Allow admins and AJAX requests
 */
function nmda_redirect_non_admin_from_admin() {
	if ( is_admin() && ! current_user_can( 'administrator' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		wp_redirect( home_url( '/dashboard/' ) );
		exit;
	}
}
add_action( 'admin_init', 'nmda_redirect_non_admin_from_admin' );

// Temporary: Force database upgrade on admin page load
add_action( 'admin_init', function() {
    if ( current_user_can( 'manage_options' ) && ! get_transient( 'nmda_db_upgraded_1_2' ) ) {
        require_once get_stylesheet_directory() . '/inc/database-schema.php';
        nmda_upgrade_database();
        set_transient( 'nmda_db_upgraded_1_2', true, HOUR_IN_SECONDS );
        update_option( 'nmda_db_version', '1.2' );
        wp_redirect( admin_url() );
        exit;
    }
}, 1 );