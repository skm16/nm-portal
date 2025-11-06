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
require_once NMDA_THEME_DIR . '/inc/api-integrations.php';
require_once NMDA_THEME_DIR . '/inc/database-schema.php';
require_once NMDA_THEME_DIR . '/inc/acf-field-groups.php';
require_once NMDA_THEME_DIR . '/inc/product-taxonomy.php';
require_once NMDA_THEME_DIR . '/inc/application-forms.php';
require_once NMDA_THEME_DIR . '/inc/admin-approval.php';

/**
 * Theme activation hook - create custom database tables
 */
function nmda_theme_activation() {
    // Create custom database tables
    nmda_create_custom_tables();

    // Flush rewrite rules
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'nmda_theme_activation' );

/**
 * Add theme support features
 */
function nmda_theme_setup() {
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
