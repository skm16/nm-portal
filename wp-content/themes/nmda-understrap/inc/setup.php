<?php
/**
 * NMDA Theme Setup and Configuration
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Custom image sizes
 */
function nmda_custom_image_sizes() {
    add_image_size( 'business-logo', 300, 300, true );
    add_image_size( 'business-thumbnail', 150, 150, true );
    add_image_size( 'resource-preview', 400, 300, true );
}
add_action( 'after_setup_theme', 'nmda_custom_image_sizes' );

/**
 * Register widget areas
 */
function nmda_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Dashboard Sidebar', 'nmda-understrap' ),
        'id'            => 'dashboard-sidebar',
        'description'   => __( 'Appears on member dashboard pages', 'nmda-understrap' ),
        'before_widget' => '<aside id="%1$s" class="widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'nmda_widgets_init' );

/**
 * Custom login page styling
 */
function nmda_custom_login_styles() {
    ?>
    <style type="text/css">
        #login h1 a {
            background-image: url('<?php echo NMDA_THEME_URI; ?>/assets/images/nmda-logo.png');
            background-size: contain;
            width: 100%;
            height: 80px;
        }
        .login form {
            border: 1px solid var(--nmda-brown-dark);
        }
        .wp-core-ui .button-primary {
            background-color: var(--nmda-brown-dark);
            border-color: var(--nmda-brown-darker);
        }
        .wp-core-ui .button-primary:hover {
            background-color: var(--nmda-red);
        }
    </style>
    <?php
}
add_action( 'login_enqueue_scripts', 'nmda_custom_login_styles' );

/**
 * Customize login page logo URL
 */
function nmda_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'nmda_login_logo_url' );

/**
 * Customize login page logo title
 */
function nmda_login_logo_url_title() {
    return 'New Mexico Department of Agriculture Portal';
}
add_filter( 'login_headertext', 'nmda_login_logo_url_title' );

/**
 * Remove admin bar for non-administrators
 */
function nmda_remove_admin_bar() {
    if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'nmda_remove_admin_bar' );

/**
 * Custom excerpt length
 */
function nmda_custom_excerpt_length( $length ) {
    return 25;
}
add_filter( 'excerpt_length', 'nmda_custom_excerpt_length', 999 );

/**
 * Custom excerpt more text
 */
function nmda_custom_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'nmda_custom_excerpt_more' );
