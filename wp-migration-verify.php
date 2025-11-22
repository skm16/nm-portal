#!/usr/bin/env php
<?php
/**
 * NMDA Migration Verification Utility
 *
 * Verifies the integrity of migrated data and displays detailed statistics
 *
 * Usage: wp eval-file wp-migration-verify.php
 *        OR
 *        wp nmda verify
 */

if (!defined('WP_CLI') || !WP_CLI) {
    echo "This script must be run through WP-CLI\n";
    exit(1);
}

class NMDA_Migration_Verifier {

    private $stats = [];
    private $issues = [];
    private $warnings = [];

    public function run() {
        WP_CLI::line(WP_CLI::colorize("%G=== NMDA Migration Verification ===%n\n"));

        // Check ID mapping table
        $this->verify_mapping_table();

        // Verify users
        $this->verify_users();

        // Verify companies
        $this->verify_companies();

        // Verify businesses
        $this->verify_businesses();

        // Verify reimbursements
        $this->verify_reimbursements();

        // Verify relationships
        $this->verify_relationships();

        // Display summary
        $this->display_summary();
    }

    /**
     * Verify mapping table exists and has data
     */
    private function verify_mapping_table() {
        global $wpdb;

        WP_CLI::line("Checking ID mapping table...");

        $table = $wpdb->prefix . 'nmda_id_mapping';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            $this->issues[] = "ID mapping table does not exist";
            WP_CLI::error("ID mapping table not found", false);
            return;
        }

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $this->stats['mappings_total'] = $count;

        if ($count == 0) {
            $this->warnings[] = "ID mapping table is empty";
            WP_CLI::warning("No ID mappings found");
        } else {
            WP_CLI::success("Found {$count} ID mappings");

            // Count by type
            $by_type = $wpdb->get_results("
                SELECT entity_type, COUNT(*) as count
                FROM {$table}
                GROUP BY entity_type
            ");

            foreach ($by_type as $row) {
                $this->stats['mappings_' . $row->entity_type] = $row->count;
                WP_CLI::line("  {$row->entity_type}: {$row->count}");
            }
        }
    }

    /**
     * Verify migrated users
     */
    private function verify_users() {
        WP_CLI::line("\nVerifying users...");

        // Count users with migration metadata
        $users = get_users([
            'meta_key' => 'nmda_original_uuid',
            'fields' => 'all'
        ]);

        $this->stats['users_migrated'] = count($users);

        if (empty($users)) {
            $this->warnings[] = "No migrated users found";
            WP_CLI::warning("No users with migration metadata found");
            return;
        }

        WP_CLI::success("Found {$this->stats['users_migrated']} migrated users");

        // Verify user data integrity
        $complete = 0;
        $incomplete = 0;

        foreach ($users as $user) {
            $has_company_id = !empty(get_user_meta($user->ID, 'nmda_company_id', true));
            $has_name = !empty($user->first_name) && !empty($user->last_name);

            if ($has_company_id && $has_name) {
                $complete++;
            } else {
                $incomplete++;
            }
        }

        WP_CLI::line("  Complete profiles: {$complete}");
        if ($incomplete > 0) {
            WP_CLI::line("  Incomplete profiles: {$incomplete}");
            $this->warnings[] = "{$incomplete} users have incomplete profiles";
        }
    }

    /**
     * Verify migrated companies
     */
    private function verify_companies() {
        WP_CLI::line("\nVerifying companies...");

        $companies = get_posts([
            'post_type' => 'company',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'nmda_original_uuid',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);

        $this->stats['companies_migrated'] = count($companies);

        if (empty($companies)) {
            $this->warnings[] = "No migrated companies found";
            WP_CLI::warning("No companies with migration metadata found");
            return;
        }

        WP_CLI::success("Found {$this->stats['companies_migrated']} migrated companies");

        // Check data completeness
        $with_contact = 0;
        $with_address = 0;
        $with_products = 0;

        foreach ($companies as $company) {
            $email = get_post_meta($company->ID, 'company_email', true);
            $phone = get_post_meta($company->ID, 'company_phone', true);

            if (!empty($email) || !empty($phone)) {
                $with_contact++;
            }

            $address = get_post_meta($company->ID, 'company_address', true);
            $city = get_post_meta($company->ID, 'company_city', true);

            if (!empty($address) || !empty($city)) {
                $with_address++;
            }

            $product_type = get_post_meta($company->ID, 'product_type_text', true);
            if (!empty($product_type)) {
                $with_products++;
            }
        }

        WP_CLI::line("  With contact info: {$with_contact}");
        WP_CLI::line("  With address info: {$with_address}");
        WP_CLI::line("  With product types: {$with_products}");
    }

    /**
     * Verify migrated businesses
     */
    private function verify_businesses() {
        WP_CLI::line("\nVerifying businesses...");

        $businesses = get_posts([
            'post_type' => 'nmda_business',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'nmda_original_uuid',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);

        $this->stats['businesses_migrated'] = count($businesses);

        if (empty($businesses)) {
            $this->warnings[] = "No migrated businesses found";
            WP_CLI::warning("No businesses with migration metadata found");
            return;
        }

        WP_CLI::success("Found {$this->stats['businesses_migrated']} migrated businesses");

        // Check data completeness
        $with_contact = 0;
        $with_address = 0;
        $with_hours = 0;
        $with_social = 0;
        $with_product_fields = 0;

        foreach ($businesses as $business) {
            // Contact info
            $email = get_post_meta($business->ID, 'business_email', true);
            $phone = get_post_meta($business->ID, 'business_phone', true);
            if (!empty($email) || !empty($phone)) {
                $with_contact++;
            }

            // Address
            $address = get_post_meta($business->ID, 'business_address', true);
            $city = get_post_meta($business->ID, 'business_city', true);
            if (!empty($address) || !empty($city)) {
                $with_address++;
            }

            // Hours
            $hours = get_post_meta($business->ID, 'business_hours', true);
            if (!empty($hours)) {
                $with_hours++;
            }

            // Social media
            $facebook = get_post_meta($business->ID, 'facebook_handle', true);
            $instagram = get_post_meta($business->ID, 'instagram_handle', true);
            if (!empty($facebook) || !empty($instagram)) {
                $with_social++;
            }

            // Product type fields
            $product_fields = get_post_meta($business->ID, 'product_type_fields', true);
            if (!empty($product_fields)) {
                $with_product_fields++;
            }
        }

        WP_CLI::line("  With contact info: {$with_contact}");
        WP_CLI::line("  With address info: {$with_address}");
        WP_CLI::line("  With business hours: {$with_hours}");
        WP_CLI::line("  With social media: {$with_social}");
        WP_CLI::line("  With product type fields: {$with_product_fields}");

        // Check addresses
        $with_additional_addresses = 0;
        foreach ($businesses as $business) {
            $addresses = get_post_meta($business->ID, 'business_addresses', true);
            if (is_array($addresses) && count($addresses) > 0) {
                $with_additional_addresses++;
            }
        }

        WP_CLI::line("  With additional addresses: {$with_additional_addresses}");
    }

    /**
     * Verify migrated reimbursements
     */
    private function verify_reimbursements() {
        global $wpdb;
        WP_CLI::line("\nVerifying reimbursements...");

        $table = $wpdb->prefix . 'nmda_reimbursements';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            $this->warnings[] = "Reimbursements table does not exist";
            WP_CLI::warning("Reimbursements table not found");
            return;
        }

        // Count total reimbursements
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $this->stats['reimbursements_total'] = $total;

        if ($total == 0) {
            $this->warnings[] = "No reimbursements found";
            WP_CLI::warning("Reimbursements table is empty");
            return;
        }

        WP_CLI::success("Found {$total} total reimbursements");

        // Count by type
        $by_type = $wpdb->get_results("
            SELECT type, COUNT(*) as count
            FROM {$table}
            GROUP BY type
        ");

        foreach ($by_type as $row) {
            $this->stats['reimbursements_' . $row->type] = $row->count;
            WP_CLI::line("  {$row->type}: {$row->count}");
        }

        // Count by status
        $by_status = $wpdb->get_results("
            SELECT status, COUNT(*) as count
            FROM {$table}
            GROUP BY status
        ");

        WP_CLI::line("\nBy Status:");
        foreach ($by_status as $row) {
            $this->stats['reimbursements_status_' . $row->status] = $row->count;
            WP_CLI::line("  {$row->status}: {$row->count}");
        }

        // Verify business relationships
        $with_valid_business = $wpdb->get_var("
            SELECT COUNT(DISTINCT r.id)
            FROM {$table} r
            INNER JOIN {$wpdb->posts} p ON r.business_id = p.ID
            WHERE p.post_type = 'nmda_business'
        ");

        $with_invalid_business = $total - $with_valid_business;

        WP_CLI::line("\nRelationships:");
        WP_CLI::line("  Valid business links: {$with_valid_business}");
        if ($with_invalid_business > 0) {
            WP_CLI::warning("  Invalid business links: {$with_invalid_business}");
            $this->warnings[] = "{$with_invalid_business} reimbursements have invalid business references";
        }

        // Check data completeness
        $with_amount = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE amount_requested IS NOT NULL AND amount_requested > 0");
        $with_fiscal_year = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE fiscal_year IS NOT NULL AND fiscal_year != ''");

        WP_CLI::line("\nData Completeness:");
        WP_CLI::line("  With amount requested: {$with_amount}");
        WP_CLI::line("  With fiscal year: {$with_fiscal_year}");

        $this->stats['reimbursements_with_amount'] = $with_amount;
        $this->stats['reimbursements_with_fiscal_year'] = $with_fiscal_year;
    }

    /**
     * Verify relationships between entities
     */
    private function verify_relationships() {
        WP_CLI::line("\nVerifying relationships...");

        // User to company relationships
        $users = get_users([
            'meta_key' => 'nmda_company_post_id',
            'fields' => 'all'
        ]);

        $valid_relationships = 0;
        $broken_relationships = 0;

        foreach ($users as $user) {
            $company_id = get_user_meta($user->ID, 'nmda_company_post_id', true);

            if ($company_id) {
                $company = get_post($company_id);
                if ($company && $company->post_type === 'company') {
                    $valid_relationships++;
                } else {
                    $broken_relationships++;
                    $this->issues[] = "User {$user->ID} has invalid company reference: {$company_id}";
                }
            }
        }

        WP_CLI::line("  User-Company links: {$valid_relationships} valid");
        if ($broken_relationships > 0) {
            WP_CLI::warning("  {$broken_relationships} broken relationships found");
        }

        $this->stats['relationships_valid'] = $valid_relationships;
        $this->stats['relationships_broken'] = $broken_relationships;
    }

    /**
     * Display verification summary
     */
    private function display_summary() {
        WP_CLI::line("\n" . WP_CLI::colorize("%G=== VERIFICATION SUMMARY ===%n"));

        // Display stats
        WP_CLI::line("\nMigrated Data:");
        WP_CLI::line("  Users: " . ($this->stats['users_migrated'] ?? 0));
        WP_CLI::line("  Companies: " . ($this->stats['companies_migrated'] ?? 0));
        WP_CLI::line("  Businesses: " . ($this->stats['businesses_migrated'] ?? 0));
        WP_CLI::line("  Reimbursements: " . ($this->stats['reimbursements_total'] ?? 0));
        if (isset($this->stats['reimbursements_advertising'])) {
            WP_CLI::line("    - Advertising: " . ($this->stats['reimbursements_advertising'] ?? 0));
        }
        if (isset($this->stats['reimbursements_labels'])) {
            WP_CLI::line("    - Labels: " . ($this->stats['reimbursements_labels'] ?? 0));
        }
        if (isset($this->stats['reimbursements_lead'])) {
            WP_CLI::line("    - Lead Generation: " . ($this->stats['reimbursements_lead'] ?? 0));
        }
        WP_CLI::line("  ID Mappings: " . ($this->stats['mappings_total'] ?? 0));
        WP_CLI::line("  Valid Relationships: " . ($this->stats['relationships_valid'] ?? 0));

        // Display warnings
        if (!empty($this->warnings)) {
            WP_CLI::line("\n" . WP_CLI::colorize("%Y=== WARNINGS ===%n"));
            foreach ($this->warnings as $warning) {
                WP_CLI::warning($warning);
            }
        }

        // Display issues
        if (!empty($this->issues)) {
            WP_CLI::line("\n" . WP_CLI::colorize("%R=== ISSUES ===%n"));
            foreach ($this->issues as $issue) {
                WP_CLI::error($issue, false);
            }
        }

        // Final status
        WP_CLI::line("");
        if (empty($this->issues)) {
            WP_CLI::success("Migration verification completed with no critical issues!");
        } else {
            WP_CLI::error("Migration verification found " . count($this->issues) . " issue(s)");
        }
    }
}

// If run directly (not via WP-CLI command)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $verifier = new NMDA_Migration_Verifier();
    $verifier->run();
}
