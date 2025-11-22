<?php
/**
 * Activity Logger Class
 * Logs all user switching activity for security and audit purposes
 *
 * @package NMDA_User_Switcher
 */

class NMDA_User_Switcher_Logger {

    /**
     * Log a user switch event
     *
     * @param int $admin_id The administrator who is switching
     * @param int $switched_to_id The user ID being switched to
     * @return int|false The log entry ID or false on failure
     */
    public static function log_switch( $admin_id, $switched_to_id ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nmda_user_switches';

        $data = array(
            'admin_id' => absint( $admin_id ),
            'switched_to_user_id' => absint( $switched_to_id ),
            'switch_time' => current_time( 'mysql' ),
            'ip_address' => self::get_client_ip(),
            'user_agent' => self::get_user_agent(),
        );

        $result = $wpdb->insert( $table_name, $data );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Log a switch back event
     *
     * @param int $admin_id The administrator who is switching back
     * @return bool True on success, false on failure
     */
    public static function log_switch_back( $admin_id ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nmda_user_switches';

        // Find the most recent switch for this admin that hasn't been closed
        $log_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table_name}
            WHERE admin_id = %d AND switch_back_time IS NULL
            ORDER BY switch_time DESC
            LIMIT 1",
            $admin_id
        ) );

        if ( ! $log_id ) {
            return false;
        }

        $result = $wpdb->update(
            $table_name,
            array( 'switch_back_time' => current_time( 'mysql' ) ),
            array( 'id' => $log_id )
        );

        return $result !== false;
    }

    /**
     * Get switching history for an admin
     *
     * @param int $admin_id The administrator's user ID
     * @param int $limit Number of records to retrieve
     * @return array Array of log entries
     */
    public static function get_admin_history( $admin_id, $limit = 10 ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nmda_user_switches';

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name}
            WHERE admin_id = %d
            ORDER BY switch_time DESC
            LIMIT %d",
            $admin_id,
            $limit
        ) );

        return $results ? $results : array();
    }

    /**
     * Get all switching activity (for admin dashboard)
     *
     * @param array $args Query arguments
     * @return array Array of log entries
     */
    public static function get_all_activity( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'order' => 'DESC',
            'admin_id' => null,
            'switched_to_id' => null,
            'date_from' => null,
            'date_to' => null,
        );

        $args = wp_parse_args( $args, $defaults );
        $table_name = $wpdb->prefix . 'nmda_user_switches';

        $where = array();
        $where_values = array();

        if ( $args['admin_id'] ) {
            $where[] = 'admin_id = %d';
            $where_values[] = $args['admin_id'];
        }

        if ( $args['switched_to_id'] ) {
            $where[] = 'switched_to_user_id = %d';
            $where_values[] = $args['switched_to_id'];
        }

        if ( $args['date_from'] ) {
            $where[] = 'switch_time >= %s';
            $where_values[] = $args['date_from'];
        }

        if ( $args['date_to'] ) {
            $where[] = 'switch_time <= %s';
            $where_values[] = $args['date_to'];
        }

        $where_clause = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

        $where_values[] = absint( $args['limit'] );
        $where_values[] = absint( $args['offset'] );

        $sql = "SELECT * FROM {$table_name}
                {$where_clause}
                ORDER BY switch_time {$args['order']}
                LIMIT %d OFFSET %d";

        if ( ! empty( $where_values ) ) {
            $sql = $wpdb->prepare( $sql, $where_values );
        }

        $results = $wpdb->get_results( $sql );

        return $results ? $results : array();
    }

    /**
     * Get statistics for dashboard widget
     *
     * @return array Statistics data
     */
    public static function get_statistics() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nmda_user_switches';

        // Total switches
        $total_switches = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

        // Switches today
        $today_switches = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name}
            WHERE DATE(switch_time) = %s",
            current_time( 'Y-m-d' )
        ) );

        // Switches this week
        $week_start = date( 'Y-m-d', strtotime( 'monday this week' ) );
        $week_switches = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name}
            WHERE switch_time >= %s",
            $week_start
        ) );

        // Most viewed users (top 5)
        $most_viewed = $wpdb->get_results(
            "SELECT switched_to_user_id, COUNT(*) as view_count
            FROM {$table_name}
            GROUP BY switched_to_user_id
            ORDER BY view_count DESC
            LIMIT 5"
        );

        // Most active admins
        $most_active_admins = $wpdb->get_results(
            "SELECT admin_id, COUNT(*) as switch_count
            FROM {$table_name}
            GROUP BY admin_id
            ORDER BY switch_count DESC
            LIMIT 5"
        );

        // Active sessions (not switched back yet)
        $active_sessions = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name}
            WHERE switch_back_time IS NULL"
        );

        return array(
            'total_switches' => absint( $total_switches ),
            'today_switches' => absint( $today_switches ),
            'week_switches' => absint( $week_switches ),
            'most_viewed_users' => $most_viewed,
            'most_active_admins' => $most_active_admins,
            'active_sessions' => absint( $active_sessions ),
        );
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );

                    if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
                        return $ip;
                    }
                }
            }
        }

        return 'Unknown';
    }

    /**
     * Get user agent string
     *
     * @return string User agent
     */
    private static function get_user_agent() {
        return isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 255 ) : 'Unknown';
    }

    /**
     * Delete old log entries
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted rows
     */
    public static function cleanup_old_logs( $days = 90 ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nmda_user_switches';
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE switch_time < %s",
            $cutoff_date
        ) );

        return absint( $deleted );
    }
}
