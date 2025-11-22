<?php
/**
 * NMDA Custom Database Tables Schema
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Create all custom database tables
 */
function nmda_create_custom_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Table names with WordPress prefix
    $table_user_business    = $wpdb->prefix . 'nmda_user_business';
    $table_business_address = $wpdb->prefix . 'nmda_business_addresses';
    $table_reimbursements   = $wpdb->prefix . 'nmda_reimbursements';
    $table_communications   = $wpdb->prefix . 'nmda_communications';
    $table_field_permissions = $wpdb->prefix . 'nmda_field_permissions';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // User-Business Relationship Table
    $sql_user_business = "CREATE TABLE IF NOT EXISTS $table_user_business (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        business_id bigint(20) NOT NULL,
        role varchar(50) NOT NULL DEFAULT 'viewer',
        status varchar(50) NOT NULL DEFAULT 'pending',
        invitation_token varchar(64) DEFAULT NULL,
        expires_at datetime DEFAULT NULL,
        invited_by bigint(20) DEFAULT NULL,
        invited_date datetime DEFAULT NULL,
        accepted_date datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY business_id (business_id),
        KEY status (status),
        KEY invitation_token (invitation_token),
        KEY expires_at (expires_at),
        UNIQUE KEY unique_user_business (user_id, business_id)
    ) $charset_collate;";

    // Business Addresses Table (multiple addresses per business)
    // @deprecated This table is no longer used. Address data is now stored in ACF repeater field 'extra_locations'
    // @see group_business_consolidated.json field 'extra_locations'
    // Table kept for reference only - address management now uses ACF instead of custom DB table
    $sql_business_address = "CREATE TABLE IF NOT EXISTS $table_business_address (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) NOT NULL,
        address_type varchar(100) NOT NULL,
        address_name varchar(250) DEFAULT NULL,
        address_line_1 varchar(255) DEFAULT NULL,
        address_line_2 varchar(255) DEFAULT NULL,
        city varchar(100) DEFAULT NULL,
        state varchar(50) DEFAULT NULL,
        zip_code varchar(20) DEFAULT NULL,
        county varchar(100) DEFAULT NULL,
        country varchar(100) DEFAULT 'USA',
        phone varchar(50) DEFAULT NULL,
        email varchar(100) DEFAULT NULL,
        is_primary tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY business_id (business_id),
        KEY address_type (address_type),
        KEY is_primary (is_primary)
    ) $charset_collate;";

    // Reimbursements Table (all types: lead, advertising, labels)
    $sql_reimbursements = "CREATE TABLE IF NOT EXISTS $table_reimbursements (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        type varchar(50) NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'submitted',
        fiscal_year varchar(10) NOT NULL,
        amount_requested decimal(10,2) DEFAULT NULL,
        amount_approved decimal(10,2) DEFAULT NULL,
        data longtext,
        documents text,
        admin_notes text,
        reviewed_by bigint(20) DEFAULT NULL,
        reviewed_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY business_id (business_id),
        KEY user_id (user_id),
        KEY type (type),
        KEY status (status),
        KEY fiscal_year (fiscal_year),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Communications/Messaging Table
    $sql_communications = "CREATE TABLE IF NOT EXISTS $table_communications (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        business_id bigint(20) DEFAULT NULL,
        user_id bigint(20) DEFAULT NULL,
        admin_id bigint(20) DEFAULT NULL,
        sender_id bigint(20) DEFAULT NULL,
        type varchar(50) NOT NULL DEFAULT 'general',
        subject varchar(255) DEFAULT NULL,
        message text,
        attachments text,
        read_status tinyint(1) DEFAULT 0,
        parent_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY business_id (business_id),
        KEY user_id (user_id),
        KEY admin_id (admin_id),
        KEY sender_id (sender_id),
        KEY type (type),
        KEY read_status (read_status),
        KEY parent_id (parent_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Field Permissions Table
    $sql_field_permissions = "CREATE TABLE IF NOT EXISTS $table_field_permissions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        field_name varchar(100) NOT NULL,
        requires_approval tinyint(1) DEFAULT 0,
        user_editable tinyint(1) DEFAULT 1,
        admin_only tinyint(1) DEFAULT 0,
        description varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY field_name (field_name)
    ) $charset_collate;";

    // Execute table creation
    dbDelta( $sql_user_business );
    dbDelta( $sql_business_address );
    dbDelta( $sql_reimbursements );
    dbDelta( $sql_communications );
    dbDelta( $sql_field_permissions );

    // Insert default field permissions
    nmda_insert_default_field_permissions();

    // Run database upgrades
    nmda_upgrade_database();

    // Store database version
    update_option( 'nmda_db_version', '1.2' );
}

/**
 * Upgrade database schema for existing installations
 */
function nmda_upgrade_database() {
    global $wpdb;
    $table_user_business = $wpdb->prefix . 'nmda_user_business';
    $table_communications = $wpdb->prefix . 'nmda_communications';
    $current_version = get_option( 'nmda_db_version', '1.0' );

    // Upgrade to 1.1: Add invitation_token and expires_at columns
    if ( version_compare( $current_version, '1.1', '<' ) ) {
        // Check if invitation_token column exists
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM $table_user_business LIKE 'invitation_token'"
        );

        if ( empty( $column_exists ) ) {
            $wpdb->query(
                "ALTER TABLE $table_user_business
                ADD COLUMN invitation_token varchar(64) DEFAULT NULL AFTER status,
                ADD INDEX idx_invitation_token (invitation_token)"
            );
        }

        // Check if expires_at column exists
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM $table_user_business LIKE 'expires_at'"
        );

        if ( empty( $column_exists ) ) {
            $wpdb->query(
                "ALTER TABLE $table_user_business
                ADD COLUMN expires_at datetime DEFAULT NULL AFTER invitation_token,
                ADD INDEX idx_expires_at (expires_at)"
            );
        }
    }

    // Upgrade to 1.2: Add sender_id column to communications table
    if ( version_compare( $current_version, '1.2', '<' ) ) {
        // Check if sender_id column exists
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM $table_communications LIKE 'sender_id'"
        );

        if ( empty( $column_exists ) ) {
            $wpdb->query(
                "ALTER TABLE $table_communications
                ADD COLUMN sender_id bigint(20) DEFAULT NULL AFTER admin_id,
                ADD INDEX idx_sender_id (sender_id)"
            );

            // Populate sender_id for existing messages based on logic:
            // If both user_id and admin_id exist, check if admin_id is actually an admin
            // This is a best-effort migration
            $wpdb->query(
                "UPDATE $table_communications c
                INNER JOIN {$wpdb->prefix}users u ON u.ID = c.admin_id
                INNER JOIN {$wpdb->prefix}usermeta um ON um.user_id = u.ID AND um.meta_key = 'wp_capabilities'
                SET c.sender_id = c.admin_id
                WHERE c.admin_id IS NOT NULL
                AND um.meta_value LIKE '%administrator%'"
            );

            // For remaining messages (member sent), set sender_id to user_id
            $wpdb->query(
                "UPDATE $table_communications
                SET sender_id = user_id
                WHERE sender_id IS NULL AND user_id IS NOT NULL"
            );
        }
    }
}

/**
 * Insert default field permission settings
 */
function nmda_insert_default_field_permissions() {
    global $wpdb;
    $table = $wpdb->prefix . 'nmda_field_permissions';

    // Check if permissions already exist
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    if ( $count > 0 ) {
        return; // Already initialized
    }

    // Default permissions for common business fields
    $default_permissions = array(
        array( 'business_name', 1, 1, 0, 'Business legal name - requires approval' ),
        array( 'dba_name', 1, 1, 0, 'Doing Business As name - requires approval' ),
        array( 'business_phone', 0, 1, 0, 'Primary business phone number' ),
        array( 'business_email', 0, 1, 0, 'Primary business email' ),
        array( 'website', 0, 1, 0, 'Business website URL' ),
        array( 'description', 0, 1, 0, 'Business description' ),
        array( 'logo', 0, 1, 0, 'Business logo image' ),
        array( 'tax_id', 1, 0, 1, 'Tax ID - admin only' ),
        array( 'status', 1, 0, 1, 'Business status - admin only' ),
    );

    foreach ( $default_permissions as $permission ) {
        $wpdb->insert(
            $table,
            array(
                'field_name'        => $permission[0],
                'requires_approval' => $permission[1],
                'user_editable'     => $permission[2],
                'admin_only'        => $permission[3],
                'description'       => $permission[4],
            ),
            array( '%s', '%d', '%d', '%d', '%s' )
        );
    }
}

/**
 * Get table name helper functions
 */
function nmda_get_user_business_table() {
    global $wpdb;
    return $wpdb->prefix . 'nmda_user_business';
}

function nmda_get_business_address_table() {
    global $wpdb;
    return $wpdb->prefix . 'nmda_business_addresses';
}

function nmda_get_reimbursements_table() {
    global $wpdb;
    return $wpdb->prefix . 'nmda_reimbursements';
}

function nmda_get_communications_table() {
    global $wpdb;
    return $wpdb->prefix . 'nmda_communications';
}

function nmda_get_field_permissions_table() {
    global $wpdb;
    return $wpdb->prefix . 'nmda_field_permissions';
}
