<?php
/**
 * Admin UI Class
 * Handles admin bar integration, visual indicators, and user interface
 *
 * @package NMDA_User_Switcher
 */

class NMDA_User_Switcher_Admin_UI {

    /**
     * Initialize the admin UI
     */
    public function init() {
        // Add admin bar menu
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );

        // Enqueue assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Add visual banner
        add_action( 'wp_footer', array( $this, 'render_switch_banner' ), 999 );
        add_action( 'admin_footer', array( $this, 'render_switch_banner' ), 999 );

        // Add AJAX handlers
        add_action( 'wp_ajax_nmda_search_users', array( $this, 'ajax_search_users' ) );
        add_action( 'wp_ajax_nmda_get_user_info', array( $this, 'ajax_get_user_info' ) );

        // Add switch links to user list table
        add_filter( 'user_row_actions', array( $this, 'add_user_row_actions' ), 10, 2 );

        // Add dashboard widget
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
    }

    /**
     * Enqueue CSS and JavaScript
     */
    public function enqueue_assets() {
        // Only for admins or when in a switched session
        if ( ! current_user_can( 'manage_options' ) && ! NMDA_User_Switcher_Session::get_original_user() ) {
            return;
        }

        wp_enqueue_style(
            'nmda-user-switcher',
            NMDA_USER_SWITCHER_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            NMDA_USER_SWITCHER_VERSION
        );

        wp_enqueue_script(
            'nmda-user-switcher',
            NMDA_USER_SWITCHER_PLUGIN_URL . 'assets/js/user-switcher.js',
            array( 'jquery', 'jquery-ui-autocomplete' ),
            NMDA_USER_SWITCHER_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'nmda-user-switcher', 'nmdaUserSwitcher', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'nmda_user_switcher_ajax' ),
            'isSwitched' => (bool) NMDA_User_Switcher_Session::get_original_user(),
            'strings' => array(
                'searchPlaceholder' => __( 'Search users...', 'nmda-user-switcher' ),
                'noResults' => __( 'No users found', 'nmda-user-switcher' ),
                'loading' => __( 'Loading...', 'nmda-user-switcher' ),
            ),
        ) );
    }

    /**
     * Add admin bar menu items
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function add_admin_bar_menu( $wp_admin_bar ) {
        // Check if user can switch
        $original_user_id = NMDA_User_Switcher_Session::get_original_user();

        if ( ! current_user_can( 'manage_options' ) && ! $original_user_id ) {
            return;
        }

        // If currently switched, show switch back option
        if ( $original_user_id ) {
            $session_info = NMDA_User_Switcher_Session::get_session_info( $original_user_id );
            $switched_user = $session_info['switched_user'];

            $wp_admin_bar->add_node( array(
                'id' => 'nmda-user-switcher',
                'title' => sprintf(
                    '<span class="nmda-switcher-indicator">%s <strong>%s</strong></span>',
                    __( 'Viewing as:', 'nmda-user-switcher' ),
                    esc_html( $switched_user->display_name )
                ),
                'href' => false,
                'meta' => array(
                    'class' => 'nmda-user-switcher-active',
                ),
            ) );

            $wp_admin_bar->add_node( array(
                'id' => 'nmda-switch-back',
                'parent' => 'nmda-user-switcher',
                'title' => __( 'Switch Back to Admin', 'nmda-user-switcher' ),
                'href' => NMDA_User_Switcher::get_switch_back_url( admin_url() ),
            ) );

            $wp_admin_bar->add_node( array(
                'id' => 'nmda-switch-other',
                'parent' => 'nmda-user-switcher',
                'title' => __( 'Switch to Another User', 'nmda-user-switcher' ),
                'href' => '#',
                'meta' => array(
                    'class' => 'nmda-switch-to-other',
                ),
            ) );

        } else {
            // Not currently switched, show switcher menu
            $wp_admin_bar->add_node( array(
                'id' => 'nmda-user-switcher',
                'title' => __( 'Switch User', 'nmda-user-switcher' ),
                'href' => false,
            ) );

            $wp_admin_bar->add_node( array(
                'id' => 'nmda-user-search',
                'parent' => 'nmda-user-switcher',
                'title' => $this->get_user_search_html(),
                'href' => false,
                'meta' => array(
                    'html' => true,
                    'class' => 'nmda-user-search-container',
                ),
            ) );

            // Add recent switches
            $this->add_recent_switches_menu( $wp_admin_bar );
        }

        // Add link to activity log
        if ( current_user_can( 'manage_options' ) ) {
            $wp_admin_bar->add_node( array(
                'id' => 'nmda-switcher-log',
                'parent' => 'nmda-user-switcher',
                'title' => __( 'View Activity Log', 'nmda-user-switcher' ),
                'href' => admin_url( 'users.php?page=nmda-switcher-log' ),
            ) );
        }
    }

    /**
     * Add recent switches to admin bar menu
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    private function add_recent_switches_menu( $wp_admin_bar ) {
        $current_user_id = get_current_user_id();
        $recent = NMDA_User_Switcher_Logger::get_admin_history( $current_user_id, 5 );

        if ( empty( $recent ) ) {
            return;
        }

        $wp_admin_bar->add_node( array(
            'id' => 'nmda-recent-switches',
            'parent' => 'nmda-user-switcher',
            'title' => __( 'Recent', 'nmda-user-switcher' ),
            'href' => false,
        ) );

        $seen_users = array();

        foreach ( $recent as $log ) {
            // Skip if we've already added this user
            if ( in_array( $log->switched_to_user_id, $seen_users ) ) {
                continue;
            }

            $seen_users[] = $log->switched_to_user_id;

            $user = get_userdata( $log->switched_to_user_id );

            if ( ! $user ) {
                continue;
            }

            $wp_admin_bar->add_node( array(
                'id' => 'nmda-recent-' . $user->ID,
                'parent' => 'nmda-recent-switches',
                'title' => sprintf(
                    '%s (%s)',
                    esc_html( $user->display_name ),
                    esc_html( $user->user_email )
                ),
                'href' => NMDA_User_Switcher::get_switch_url( $user->ID ),
            ) );
        }
    }

    /**
     * Get user search HTML for admin bar
     *
     * @return string
     */
    private function get_user_search_html() {
        ob_start();
        ?>
        <div class="nmda-user-search-wrapper">
            <input type="text"
                   id="nmda-user-search-input"
                   class="nmda-user-search-input"
                   placeholder="<?php esc_attr_e( 'Search users...', 'nmda-user-switcher' ); ?>"
                   autocomplete="off">
            <div class="nmda-user-search-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the visual banner when switched
     */
    public function render_switch_banner() {
        $session_info = NMDA_User_Switcher::get_current_session_info();

        if ( ! $session_info ) {
            return;
        }

        $switched_user = $session_info['switched_user'];
        $admin_user = get_userdata( $session_info['admin_id'] );

        // Get user's businesses for display
        $businesses = array();
        if ( function_exists( 'nmda_get_user_businesses' ) ) {
            $businesses = nmda_get_user_businesses( $switched_user->ID );
        }

        ?>
        <div id="nmda-switch-banner" class="nmda-switch-banner">
            <div class="nmda-switch-banner-content">
                <div class="nmda-switch-banner-info">
                    <span class="nmda-switch-icon">👁️</span>
                    <strong><?php _e( 'Viewing as:', 'nmda-user-switcher' ); ?></strong>
                    <span class="nmda-switched-user-name"><?php echo esc_html( $switched_user->display_name ); ?></span>
                    <span class="nmda-switched-user-email">(<?php echo esc_html( $switched_user->user_email ); ?>)</span>

                    <?php if ( ! empty( $businesses ) ) : ?>
                        <span class="nmda-switched-user-businesses">
                            | <?php echo count( $businesses ); ?> <?php echo _n( 'Business', 'Businesses', count( $businesses ), 'nmda-user-switcher' ); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="nmda-switch-banner-actions">
                    <a href="<?php echo esc_url( NMDA_User_Switcher::get_switch_back_url() ); ?>"
                       class="nmda-switch-back-button">
                        <?php _e( 'Switch Back to Admin', 'nmda-user-switcher' ); ?>
                    </a>
                </div>
            </div>

            <?php if ( ! empty( $businesses ) ) : ?>
                <div class="nmda-switch-banner-businesses">
                    <strong><?php _e( 'Associated Businesses:', 'nmda-user-switcher' ); ?></strong>
                    <?php foreach ( $businesses as $business ) : ?>
                        <?php
                        // nmda_get_user_businesses returns arrays, not objects
                        $business_id = is_array( $business ) ? $business['business_id'] : $business->ID;
                        $business_post = get_post( $business_id );

                        if ( ! $business_post ) {
                            continue;
                        }

                        // Get role from the business array or function
                        $role = '';
                        if ( is_array( $business ) && isset( $business['role'] ) ) {
                            $role = $business['role'];
                        } elseif ( function_exists( 'nmda_get_user_business_role' ) ) {
                            $role = nmda_get_user_business_role( $switched_user->ID, $business_id );
                        }
                        ?>
                        <span class="nmda-business-badge">
                            <?php echo esc_html( $business_post->post_title ); ?>
                            <?php if ( $role ) : ?>
                                <span class="nmda-role-badge nmda-role-<?php echo esc_attr( $role ); ?>">
                                    <?php echo esc_html( ucfirst( $role ) ); ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX handler for user search
     */
    public function ajax_search_users() {
        check_ajax_referer( 'nmda_user_switcher_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'nmda-user-switcher' ) ) );
        }

        $search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

        if ( empty( $search ) ) {
            wp_send_json_success( array( 'users' => array() ) );
        }

        // Search users
        $user_query = new WP_User_Query( array(
            'search' => '*' . $search . '*',
            'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
            'number' => 20,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ) );

        $users = array();
        $prevent_admin_switch = get_option( 'nmda_user_switcher_prevent_admin_switch', true );

        foreach ( $user_query->get_results() as $user ) {
            // Skip admins if option is set
            if ( $prevent_admin_switch && user_can( $user->ID, 'manage_options' ) ) {
                continue;
            }

            // Get business count if function exists
            $business_count = 0;
            if ( function_exists( 'nmda_get_user_businesses' ) ) {
                $businesses = nmda_get_user_businesses( $user->ID );
                $business_count = count( $businesses );
            }

            $users[] = array(
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email,
                'user_login' => $user->user_login,
                'roles' => implode( ', ', $user->roles ),
                'business_count' => $business_count,
                'switch_url' => NMDA_User_Switcher::get_switch_url( $user->ID ),
            );
        }

        wp_send_json_success( array( 'users' => $users ) );
    }

    /**
     * AJAX handler to get detailed user info
     */
    public function ajax_get_user_info() {
        check_ajax_referer( 'nmda_user_switcher_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'nmda-user-switcher' ) ) );
        }

        $user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
        $user = get_userdata( $user_id );

        if ( ! $user ) {
            wp_send_json_error( array( 'message' => __( 'User not found', 'nmda-user-switcher' ) ) );
        }

        $businesses = array();
        if ( function_exists( 'nmda_get_user_businesses' ) ) {
            $user_businesses = nmda_get_user_businesses( $user_id );
            foreach ( $user_businesses as $business ) {
                // nmda_get_user_businesses returns arrays, not objects
                $business_id = is_array( $business ) ? $business['business_id'] : $business->ID;
                $business_post = get_post( $business_id );

                if ( ! $business_post ) {
                    continue;
                }

                // Get role from array or function
                $role = '';
                if ( is_array( $business ) && isset( $business['role'] ) ) {
                    $role = $business['role'];
                } elseif ( function_exists( 'nmda_get_user_business_role' ) ) {
                    $role = nmda_get_user_business_role( $user_id, $business_id );
                }

                $businesses[] = array(
                    'id' => $business_id,
                    'title' => $business_post->post_title,
                    'role' => $role,
                );
            }
        }

        wp_send_json_success( array(
            'user' => array(
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email,
                'roles' => $user->roles,
                'businesses' => $businesses,
            ),
        ) );
    }

    /**
     * Add "Switch To" link in users list table
     *
     * @param array $actions
     * @param WP_User $user
     * @return array
     */
    public function add_user_row_actions( $actions, $user ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $actions;
        }

        $current_user_id = get_current_user_id();

        // Don't show for current user
        if ( $user->ID === $current_user_id ) {
            return $actions;
        }

        // Don't show for other admins if option is set
        if ( get_option( 'nmda_user_switcher_prevent_admin_switch', true ) && user_can( $user->ID, 'manage_options' ) ) {
            return $actions;
        }

        $switch_url = NMDA_User_Switcher::get_switch_url( $user->ID, admin_url( 'users.php' ) );

        $actions['nmda_switch_to'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $switch_url ),
            __( 'Switch To', 'nmda-user-switcher' )
        );

        return $actions;
    }

    /**
     * Add dashboard widget showing switching activity
     */
    public function add_dashboard_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'nmda_user_switcher_stats',
            __( 'User Switcher Activity', 'nmda-user-switcher' ),
            array( $this, 'render_dashboard_widget' )
        );
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        $stats = NMDA_User_Switcher_Logger::get_statistics();
        $recent = NMDA_User_Switcher_Logger::get_all_activity( array( 'limit' => 5 ) );

        ?>
        <div class="nmda-switcher-stats">
            <div class="nmda-stats-row">
                <div class="nmda-stat-box">
                    <span class="nmda-stat-number"><?php echo esc_html( $stats['total_switches'] ); ?></span>
                    <span class="nmda-stat-label"><?php _e( 'Total Switches', 'nmda-user-switcher' ); ?></span>
                </div>
                <div class="nmda-stat-box">
                    <span class="nmda-stat-number"><?php echo esc_html( $stats['today_switches'] ); ?></span>
                    <span class="nmda-stat-label"><?php _e( 'Today', 'nmda-user-switcher' ); ?></span>
                </div>
                <div class="nmda-stat-box">
                    <span class="nmda-stat-number"><?php echo esc_html( $stats['week_switches'] ); ?></span>
                    <span class="nmda-stat-label"><?php _e( 'This Week', 'nmda-user-switcher' ); ?></span>
                </div>
                <div class="nmda-stat-box">
                    <span class="nmda-stat-number"><?php echo esc_html( $stats['active_sessions'] ); ?></span>
                    <span class="nmda-stat-label"><?php _e( 'Active Sessions', 'nmda-user-switcher' ); ?></span>
                </div>
            </div>

            <?php if ( ! empty( $recent ) ) : ?>
                <h4><?php _e( 'Recent Activity', 'nmda-user-switcher' ); ?></h4>
                <ul class="nmda-recent-activity">
                    <?php foreach ( $recent as $log ) : ?>
                        <?php
                        $admin = get_userdata( $log->admin_id );
                        $switched_to = get_userdata( $log->switched_to_user_id );
                        ?>
                        <li>
                            <strong><?php echo esc_html( $admin ? $admin->display_name : 'Unknown' ); ?></strong>
                            <?php _e( 'switched to', 'nmda-user-switcher' ); ?>
                            <strong><?php echo esc_html( $switched_to ? $switched_to->display_name : 'Unknown' ); ?></strong>
                            <span class="nmda-log-time"><?php echo human_time_diff( strtotime( $log->switch_time ), current_time( 'timestamp' ) ); ?> <?php _e( 'ago', 'nmda-user-switcher' ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <p>
                <a href="<?php echo esc_url( admin_url( 'users.php?page=nmda-switcher-log' ) ); ?>" class="button">
                    <?php _e( 'View Full Activity Log', 'nmda-user-switcher' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
