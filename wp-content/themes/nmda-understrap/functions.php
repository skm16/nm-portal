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
define( 'NMDA_THEME_VERSION', '1.0.0' );
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
    // Custom JavaScript
    wp_enqueue_script( 'nmda-custom-js', NMDA_THEME_URI . '/assets/js/custom.js', array( 'jquery' ), NMDA_THEME_VERSION, true );

    // Localize script with AJAX URL and nonce
    wp_localize_script( 'nmda-custom-js', 'nmdaAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'nmda-ajax-nonce' ),
    ) );

    // Dashboard styles
    if ( is_page_template( 'page-member-dashboard.php' ) ) {
        wp_enqueue_style( 'nmda-dashboard-styles', NMDA_THEME_URI . '/assets/css/dashboard.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
    }

    // Resource Center styles
    if ( is_page_template( 'page-resource-center.php' ) ) {
        wp_enqueue_style( 'nmda-dashboard-styles', NMDA_THEME_URI . '/assets/css/dashboard.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_style( 'nmda-resource-center-styles', NMDA_THEME_URI . '/assets/css/resource-center.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-resource-center', NMDA_THEME_URI . '/assets/js/resource-center.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
    }

    // Edit Profile styles
    if ( is_page_template( 'page-edit-profile.php' ) ) {
        wp_enqueue_style( 'nmda-dashboard-styles', NMDA_THEME_URI . '/assets/css/dashboard.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_style( 'nmda-edit-profile-styles', NMDA_THEME_URI . '/assets/css/edit-profile.css', array( 'nmda-dashboard-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-edit-profile', NMDA_THEME_URI . '/assets/js/edit-profile.js', array( 'jquery' ), NMDA_THEME_VERSION, true );
    }

    // Reimbursement forms scripts and styles
    if ( is_page_template( array( 'page-reimbursement-lead.php', 'page-reimbursement-advertising.php', 'page-reimbursement-labels.php' ) ) ) {
        wp_enqueue_style( 'nmda-reimbursement-forms-styles', NMDA_THEME_URI . '/assets/css/reimbursement-forms.css', array( 'nmda-custom-styles' ), NMDA_THEME_VERSION );
        wp_enqueue_script( 'nmda-reimbursement-forms', NMDA_THEME_URI . '/assets/js/reimbursement-forms.js', array( 'jquery' ), NMDA_THEME_VERSION, true );

        // Localize script with AJAX URL and dashboard URL
        wp_localize_script( 'nmda-reimbursement-forms', 'nmdaData', array(
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'dashboardUrl' => home_url( '/dashboard' ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'nmda_enqueue_scripts' );

/**
 * Load core modules
 */
require_once NMDA_THEME_DIR . '/inc/setup.php';
require_once NMDA_THEME_DIR . '/inc/custom-post-types.php';
require_once NMDA_THEME_DIR . '/inc/user-management.php';
require_once NMDA_THEME_DIR . '/inc/business-management.php';
require_once NMDA_THEME_DIR . '/inc/reimbursements.php';
require_once NMDA_THEME_DIR . '/inc/resources.php';
require_once NMDA_THEME_DIR . '/inc/api-integrations.php';
require_once NMDA_THEME_DIR . '/inc/database-schema.php';
require_once NMDA_THEME_DIR . '/inc/acf-field-groups.php';
require_once NMDA_THEME_DIR . '/inc/product-taxonomy.php';
require_once NMDA_THEME_DIR . '/inc/application-forms.php';
require_once NMDA_THEME_DIR . '/inc/admin-approval.php';
require_once NMDA_THEME_DIR . '/inc/admin-reimbursements.php';

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
 * Redirect logged-in users from the homepage to the /dashboard/ page.
 *
 * This function checks if a user is logged in and if they are currently
 * viewing the front page. If both conditions are true, it redirects
 * them to the site's /dashboard/ URL.
 */
function nmda_redirect_logged_in_users_from_home() {
    
    // Check if the user is logged in AND is on the front page
    if ( is_user_logged_in() && is_front_page() ) {
        
        // Redirect to the /dashboard/ page
        wp_redirect( site_url( '/dashboard/' ) );
        
        // Always exit after a wp_redirect() to prevent further script execution
        exit;
    }
}
// Add the function to the 'template_redirect' hook
add_action( 'template_redirect', 'nmda_redirect_logged_in_users_from_home' );
