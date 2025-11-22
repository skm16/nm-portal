<?php
/**
 * Plugin Name: NMDA User Switcher
 * Plugin URI: https://nmda.com
 * Description: Allows administrators to view the site from any user's perspective while maintaining admin privileges. Built specifically for NMDA Portal user-business relationship system.
 * Version: 1.0.0
 * Author: NMDA Development Team
 * Author URI: https://nmda.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: nmda-user-switcher
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Plugin version.
 */
define( 'NMDA_USER_SWITCHER_VERSION', '1.0.0' );
define( 'NMDA_USER_SWITCHER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NMDA_USER_SWITCHER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_nmda_user_switcher() {
    require_once NMDA_USER_SWITCHER_PLUGIN_DIR . 'includes/class-activator.php';
    NMDA_User_Switcher_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_nmda_user_switcher() {
    require_once NMDA_USER_SWITCHER_PLUGIN_DIR . 'includes/class-activator.php';
    NMDA_User_Switcher_Activator::deactivate();
}

register_activation_hook( __FILE__, 'activate_nmda_user_switcher' );
register_deactivation_hook( __FILE__, 'deactivate_nmda_user_switcher' );

/**
 * Include required files
 */
require_once NMDA_USER_SWITCHER_PLUGIN_DIR . 'includes/class-session.php';
require_once NMDA_USER_SWITCHER_PLUGIN_DIR . 'includes/class-logger.php';
require_once NMDA_USER_SWITCHER_PLUGIN_DIR . 'includes/class-switcher.php';
require_once NMDA_USER_SWITCHER_PLUGIN_DIR . 'includes/class-admin-ui.php';

/**
 * Initialize the plugin
 */
function run_nmda_user_switcher() {
    $switcher = new NMDA_User_Switcher();
    $switcher->init();

    if ( is_admin() || current_user_can( 'manage_options' ) ) {
        $admin_ui = new NMDA_User_Switcher_Admin_UI();
        $admin_ui->init();
    }
}

add_action( 'plugins_loaded', 'run_nmda_user_switcher' );
