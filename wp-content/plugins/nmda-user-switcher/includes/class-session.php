<?php
/**
 * Session Management Class
 * Handles storing and retrieving user switching state
 *
 * @package NMDA_User_Switcher
 */

class NMDA_User_Switcher_Session {

    /**
     * Meta key for storing switched user ID
     */
    const SWITCHED_USER_META = '_nmda_switched_user';

    /**
     * Meta key for storing original admin ID
     */
    const ORIGINAL_USER_META = '_nmda_original_user';

    /**
     * Transient prefix
     */
    const TRANSIENT_PREFIX = 'nmda_user_switch_';

    /**
     * Cookie name
     */
    const COOKIE_NAME = 'nmda_user_switcher';

    /**
     * Start a switching session
     *
     * @param int $admin_id The administrator's user ID
     * @param int $switch_to_id The user ID to switch to
     * @return bool True on success, false on failure
     */
    public static function start_session( $admin_id, $switch_to_id ) {
        // Store in user meta
        update_user_meta( $admin_id, self::SWITCHED_USER_META, $switch_to_id );
        update_user_meta( $admin_id, self::ORIGINAL_USER_META, $admin_id );

        // Store in transient for quick access
        $transient_key = self::TRANSIENT_PREFIX . $admin_id;
        $timeout = get_option( 'nmda_user_switcher_session_timeout', 43200 ); // Default 12 hours

        $session_data = array(
            'admin_id' => $admin_id,
            'switched_to' => $switch_to_id,
            'start_time' => current_time( 'timestamp' ),
        );

        set_transient( $transient_key, $session_data, $timeout );

        // Set cookie for persistence across requests
        self::set_cookie( $admin_id, $switch_to_id );

        return true;
    }

    /**
     * End a switching session
     *
     * @param int $admin_id The administrator's user ID
     * @return bool True on success
     */
    public static function end_session( $admin_id ) {
        // Remove user meta
        delete_user_meta( $admin_id, self::SWITCHED_USER_META );
        delete_user_meta( $admin_id, self::ORIGINAL_USER_META );

        // Remove transient
        delete_transient( self::TRANSIENT_PREFIX . $admin_id );

        // Remove cookie
        self::delete_cookie();

        return true;
    }

    /**
     * Get the switched user ID for an admin
     *
     * @param int $admin_id The administrator's user ID
     * @return int|false The switched user ID or false if not switching
     */
    public static function get_switched_user( $admin_id ) {
        // Try transient first (faster)
        $session_data = get_transient( self::TRANSIENT_PREFIX . $admin_id );

        if ( $session_data && isset( $session_data['switched_to'] ) ) {
            return absint( $session_data['switched_to'] );
        }

        // Fallback to user meta
        $switched_user = get_user_meta( $admin_id, self::SWITCHED_USER_META, true );

        if ( $switched_user ) {
            return absint( $switched_user );
        }

        return false;
    }

    /**
     * Get the original admin ID from current session
     *
     * @return int|false The original admin user ID or false if not switching
     */
    public static function get_original_user() {
        $current_user_id = get_current_user_id();

        // Check if current user has switched user meta
        $original_user = get_user_meta( $current_user_id, self::ORIGINAL_USER_META, true );

        if ( $original_user ) {
            return absint( $original_user );
        }

        // Check all active sessions (in case of cookie-based detection)
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
            WHERE meta_key = %s AND meta_value = %d
            LIMIT 1",
            self::SWITCHED_USER_META,
            $current_user_id
        ) );

        return $result ? absint( $result ) : false;
    }

    /**
     * Check if user is currently in a switched session
     *
     * @param int $user_id The user ID to check
     * @return bool True if switching, false otherwise
     */
    public static function is_switching( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        $switched_user = get_user_meta( $user_id, self::SWITCHED_USER_META, true );

        return ! empty( $switched_user );
    }

    /**
     * Set cookie for session persistence
     *
     * @param int $admin_id Original admin ID
     * @param int $switched_to Switched user ID
     */
    private static function set_cookie( $admin_id, $switched_to ) {
        $timeout = get_option( 'nmda_user_switcher_session_timeout', 43200 );
        $expiry = time() + $timeout;

        $cookie_value = json_encode( array(
            'admin' => $admin_id,
            'switched' => $switched_to,
            'time' => time(),
        ) );

        setcookie(
            self::COOKIE_NAME,
            $cookie_value,
            $expiry,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true // HTTP only
        );
    }

    /**
     * Delete the session cookie
     */
    private static function delete_cookie() {
        setcookie(
            self::COOKIE_NAME,
            '',
            time() - 3600,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }

    /**
     * Clean up expired sessions
     * Should be called periodically via cron
     */
    public static function cleanup_expired_sessions() {
        global $wpdb;

        // Find all users with switched user meta
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
            self::SWITCHED_USER_META
        ) );

        foreach ( $results as $row ) {
            $admin_id = $row->user_id;
            $transient = get_transient( self::TRANSIENT_PREFIX . $admin_id );

            // If transient expired, clean up the meta
            if ( false === $transient ) {
                delete_user_meta( $admin_id, self::SWITCHED_USER_META );
                delete_user_meta( $admin_id, self::ORIGINAL_USER_META );
            }
        }
    }

    /**
     * Get session info for display
     *
     * @param int $admin_id The administrator's user ID
     * @return array|false Session information or false
     */
    public static function get_session_info( $admin_id ) {
        $switched_user_id = self::get_switched_user( $admin_id );

        if ( ! $switched_user_id ) {
            return false;
        }

        $switched_user = get_userdata( $switched_user_id );
        $session_data = get_transient( self::TRANSIENT_PREFIX . $admin_id );

        return array(
            'switched_user' => $switched_user,
            'switched_user_id' => $switched_user_id,
            'start_time' => isset( $session_data['start_time'] ) ? $session_data['start_time'] : null,
            'admin_id' => $admin_id,
        );
    }
}
