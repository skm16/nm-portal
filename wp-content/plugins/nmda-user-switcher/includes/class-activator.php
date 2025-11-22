<?php
/**
 * Fired during plugin activation and deactivation
 *
 * @package NMDA_User_Switcher
 */

class NMDA_User_Switcher_Activator {

    /**
     * Activate the plugin
     * Creates the user switches logging table
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'nmda_user_switches';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            admin_id bigint(20) NOT NULL,
            switched_to_user_id bigint(20) NOT NULL,
            switch_time datetime NOT NULL,
            switch_back_time datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY admin_id (admin_id),
            KEY switched_to_user_id (switched_to_user_id),
            KEY switch_time (switch_time)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Set plugin version
        update_option( 'nmda_user_switcher_version', NMDA_USER_SWITCHER_VERSION );

        // Set default options
        if ( ! get_option( 'nmda_user_switcher_session_timeout' ) ) {
            update_option( 'nmda_user_switcher_session_timeout', 43200 ); // 12 hours in seconds
        }

        if ( ! get_option( 'nmda_user_switcher_prevent_admin_switch' ) ) {
            update_option( 'nmda_user_switcher_prevent_admin_switch', true );
        }
    }

    /**
     * Deactivate the plugin
     * Cleans up any active switching sessions
     */
    public static function deactivate() {
        // Clean up any active switching sessions
        delete_metadata( 'user', 0, '_nmda_switched_user', '', true );
        delete_metadata( 'user', 0, '_nmda_original_user', '', true );

        // Clean up transients
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nmda_user_switch_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nmda_user_switch_%'" );
    }
}
