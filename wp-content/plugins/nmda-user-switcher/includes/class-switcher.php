<?php
/**
 * Core Switcher Class
 * Handles the actual user switching logic and user context override
 *
 * @package NMDA_User_Switcher
 */

class NMDA_User_Switcher {

    /**
     * The current real admin user ID
     *
     * @var int
     */
    private $original_user_id = null;

    /**
     * The user ID we're currently switched to
     *
     * @var int
     */
    private $switched_user_id = null;

    /**
     * Initialize the switcher
     */
    public function init() {
        // Hook into user determination early
        add_filter( 'determine_current_user', array( $this, 'determine_current_user' ), 1 );

        // Also hook into authentication to override the user
        add_filter( 'authenticate', array( $this, 'authenticate_user' ), 999, 3 );

        // Override wp_get_current_user to ensure switched user is used
        add_action( 'set_current_user', array( $this, 'set_current_user' ) );

        // Clear user cache when switching
        add_action( 'init', array( $this, 'maybe_override_current_user' ), 1 );

        // Handle switch requests
        add_action( 'init', array( $this, 'handle_switch_request' ), 0 );

        // Always show admin bar for admins even when switched
        add_filter( 'show_admin_bar', array( $this, 'show_admin_bar' ), 999 );

        // Add body class when switching
        add_filter( 'body_class', array( $this, 'add_body_class' ) );
        add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );

        // Cleanup expired sessions (daily)
        if ( ! wp_next_scheduled( 'nmda_user_switcher_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'nmda_user_switcher_cleanup' );
        }
        add_action( 'nmda_user_switcher_cleanup', array( $this, 'cleanup_sessions' ) );
    }

    /**
     * Determine which user we should be in the context of
     *
     * @param int $user_id Current user ID
     * @return int The user ID to use
     */
    public function determine_current_user( $user_id ) {
        // Don't interfere if no user is logged in
        if ( ! $user_id ) {
            return $user_id;
        }

        // Store the real user ID
        if ( null === $this->original_user_id ) {
            $this->original_user_id = $user_id;
        }

        // Check if this user has an active switch session
        $switched_to = NMDA_User_Switcher_Session::get_switched_user( $user_id );

        if ( $switched_to ) {
            // Verify the switched user still exists
            $switched_user = get_userdata( $switched_to );

            if ( $switched_user ) {
                $this->switched_user_id = $switched_to;
                return $switched_to;
            } else {
                // User doesn't exist anymore, end the session
                NMDA_User_Switcher_Session::end_session( $user_id );
            }
        }

        return $user_id;
    }

    /**
     * Maybe override the current user early in the request
     * This ensures wp_get_current_user() uses the switched user
     */
    public function maybe_override_current_user() {
        // Get the original logged-in user
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        // Check for switched session
        $switched_to = NMDA_User_Switcher_Session::get_switched_user( $user_id );

        if ( $switched_to && $switched_to !== $user_id ) {
            // Clear the current user cache
            wp_cache_delete( $user_id, 'users' );
            wp_cache_delete( $switched_to, 'users' );

            // Set the switched user as current
            global $current_user;
            $current_user = get_userdata( $switched_to );

            // Also update the global
            wp_set_current_user( $switched_to );
        }
    }

    /**
     * Override the user on set_current_user action
     *
     * @param int $user_id The user ID being set
     */
    public function set_current_user( $user_id ) {
        // If we have a switched session, override the user being set
        if ( $this->switched_user_id && $this->switched_user_id !== $user_id ) {
            global $current_user;
            $current_user = get_userdata( $this->switched_user_id );
        }
    }

    /**
     * Filter authenticate to ensure switched user is maintained
     *
     * @param WP_User|WP_Error|null $user
     * @param string $username
     * @param string $password
     * @return WP_User|WP_Error|null
     */
    public function authenticate_user( $user, $username, $password ) {
        // Don't interfere with actual authentication attempts
        if ( ! empty( $username ) || ! empty( $password ) ) {
            return $user;
        }

        return $user;
    }

    /**
     * Handle switch/switch-back requests
     */
    public function handle_switch_request() {
        // Check for switch request
        if ( isset( $_GET['nmda_switch_to'] ) && isset( $_GET['_wpnonce'] ) ) {
            $this->process_switch_to();
        }

        // Check for switch back request
        if ( isset( $_GET['nmda_switch_back'] ) && isset( $_GET['_wpnonce'] ) ) {
            $this->process_switch_back();
        }
    }

    /**
     * Process a switch-to request
     */
    private function process_switch_to() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'nmda_switch_to_' . $_GET['nmda_switch_to'] ) ) {
            wp_die( __( 'Security check failed. Please try again.', 'nmda-user-switcher' ) );
        }

        $current_user_id = get_current_user_id();

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to switch users.', 'nmda-user-switcher' ) );
        }

        $switch_to_id = absint( $_GET['nmda_switch_to'] );
        $switch_to_user = get_userdata( $switch_to_id );

        // Verify target user exists
        if ( ! $switch_to_user ) {
            wp_die( __( 'The user you are trying to switch to does not exist.', 'nmda-user-switcher' ) );
        }

        // Prevent switching to other administrators if option is set
        if ( get_option( 'nmda_user_switcher_prevent_admin_switch', true ) ) {
            if ( user_can( $switch_to_id, 'manage_options' ) ) {
                wp_die( __( 'You cannot switch to other administrators for security reasons.', 'nmda-user-switcher' ) );
            }
        }

        // Start the switching session
        NMDA_User_Switcher_Session::start_session( $current_user_id, $switch_to_id );

        // Log the switch
        NMDA_User_Switcher_Logger::log_switch( $current_user_id, $switch_to_id );

        // Clear WordPress user caches to force fresh user data
        wp_cache_delete( $current_user_id, 'users' );
        wp_cache_delete( $current_user_id, 'user_meta' );
        wp_cache_delete( $switch_to_id, 'users' );
        wp_cache_delete( $switch_to_id, 'user_meta' );

        // Clear current user global
        global $current_user;
        $current_user = null;

        // Determine redirect URL
        $redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : home_url();

        // If no specific redirect, go to member dashboard if it exists
        if ( $redirect_to === home_url() ) {
            $dashboard_page = get_page_by_path( 'member-dashboard' );
            if ( $dashboard_page ) {
                $redirect_to = get_permalink( $dashboard_page->ID );
            }
        }

        // Redirect
        wp_safe_redirect( $redirect_to );
        exit;
    }

    /**
     * Process a switch-back request
     */
    private function process_switch_back() {
        // Get the real admin user ID first (before nonce verification)
        $admin_id = NMDA_User_Switcher_Session::get_original_user();

        if ( ! $admin_id ) {
            wp_die( __( 'No active switching session found.', 'nmda-user-switcher' ) );
        }

        // Verify nonce - it could be created in either user's context
        $nonce_valid = wp_verify_nonce( $_GET['_wpnonce'], 'nmda_switch_back' );

        // If nonce fails with current user, try verifying with the original admin's context
        if ( ! $nonce_valid ) {
            // Temporarily set the admin as current user for nonce verification
            $original_current_user = wp_get_current_user();
            wp_set_current_user( $admin_id );

            $nonce_valid = wp_verify_nonce( $_GET['_wpnonce'], 'nmda_switch_back' );

            // Restore the switched user context
            if ( $original_current_user && $original_current_user->ID ) {
                wp_set_current_user( $original_current_user->ID );
            }
        }

        if ( ! $nonce_valid ) {
            wp_die( __( 'Security check failed. Please try again.', 'nmda-user-switcher' ) );
        }

        // Get the switched user ID before ending session
        $switched_user_id = NMDA_User_Switcher_Session::get_switched_user( $admin_id );

        // End the session
        NMDA_User_Switcher_Session::end_session( $admin_id );

        // Log the switch back
        NMDA_User_Switcher_Logger::log_switch_back( $admin_id );

        // Clear WordPress user caches
        if ( $switched_user_id ) {
            wp_cache_delete( $switched_user_id, 'users' );
            wp_cache_delete( $switched_user_id, 'user_meta' );
        }
        wp_cache_delete( $admin_id, 'users' );
        wp_cache_delete( $admin_id, 'user_meta' );

        // Clear current user global
        global $current_user;
        $current_user = null;

        // Redirect to admin or specified location
        $redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : admin_url();

        wp_safe_redirect( $redirect_to );
        exit;
    }

    /**
     * Ensure admin bar is shown when switching
     *
     * @param bool $show_admin_bar
     * @return bool
     */
    public function show_admin_bar( $show_admin_bar ) {
        $original_user_id = NMDA_User_Switcher_Session::get_original_user();

        if ( $original_user_id && user_can( $original_user_id, 'manage_options' ) ) {
            return true;
        }

        return $show_admin_bar;
    }

    /**
     * Add body class when switching
     *
     * @param array $classes
     * @return array
     */
    public function add_body_class( $classes ) {
        if ( $this->is_switched() ) {
            $classes[] = 'nmda-user-switched';
        }

        return $classes;
    }

    /**
     * Add admin body class when switching
     *
     * @param string $classes
     * @return string
     */
    public function add_admin_body_class( $classes ) {
        if ( $this->is_switched() ) {
            $classes .= ' nmda-user-switched';
        }

        return $classes;
    }

    /**
     * Check if currently in a switched session
     *
     * @return bool
     */
    public function is_switched() {
        $current_user_id = get_current_user_id();
        return NMDA_User_Switcher_Session::is_switching( $current_user_id ) ||
               NMDA_User_Switcher_Session::get_original_user() !== false;
    }

    /**
     * Get switch URL for a specific user
     *
     * @param int $user_id The user ID to switch to
     * @param string $redirect_to Optional redirect URL after switching
     * @return string The switch URL
     */
    public static function get_switch_url( $user_id, $redirect_to = '' ) {
        $url = add_query_arg(
            array(
                'nmda_switch_to' => $user_id,
                '_wpnonce' => wp_create_nonce( 'nmda_switch_to_' . $user_id ),
            ),
            home_url()
        );

        if ( $redirect_to ) {
            $url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $url );
        }

        return $url;
    }

    /**
     * Get switch back URL
     *
     * @param string $redirect_to Optional redirect URL after switching back
     * @return string The switch back URL
     */
    public static function get_switch_back_url( $redirect_to = '' ) {
        // Get the original admin user ID if we're in a switched session
        $admin_id = NMDA_User_Switcher_Session::get_original_user();

        // If we're switched, create nonce in the admin's context
        // Otherwise use current user context
        $nonce = '';
        if ( $admin_id ) {
            // Temporarily switch to admin context for nonce creation
            $current_user_backup = wp_get_current_user();
            wp_set_current_user( $admin_id );
            $nonce = wp_create_nonce( 'nmda_switch_back' );
            // Restore previous context
            if ( $current_user_backup && $current_user_backup->ID ) {
                wp_set_current_user( $current_user_backup->ID );
            }
        } else {
            $nonce = wp_create_nonce( 'nmda_switch_back' );
        }

        $url = add_query_arg(
            array(
                'nmda_switch_back' => '1',
                '_wpnonce' => $nonce,
            ),
            home_url()
        );

        if ( $redirect_to ) {
            $url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $url );
        }

        return $url;
    }

    /**
     * Cleanup expired sessions (called daily via cron)
     */
    public function cleanup_sessions() {
        NMDA_User_Switcher_Session::cleanup_expired_sessions();

        // Also cleanup old logs (keep 90 days)
        NMDA_User_Switcher_Logger::cleanup_old_logs( 90 );
    }

    /**
     * Get current session info for display
     *
     * @return array|false Session info or false
     */
    public static function get_current_session_info() {
        $admin_id = NMDA_User_Switcher_Session::get_original_user();

        if ( ! $admin_id ) {
            return false;
        }

        return NMDA_User_Switcher_Session::get_session_info( $admin_id );
    }
}
