<?php

defined('ABSPATH') || exit;

if (defined('WP_CLI') && WP_CLI) {

    class NMDA_Importer_CLI_Final {

        private $portal_dir;
        private $dry_run = true;

        public function __construct() {
            // Directory that contains the raw *.sql files exported from MySQL
            $this->portal_dir = trailingslashit(ABSPATH) . 'portal-files/';
        }

        /* =========================================
         * Entry points / Command Orchestration
         * ========================================= */

        /**
         * Import all datasets in the correct order.
         *
         * ## OPTIONS
         * [--dry-run] : Default (no writes)
         * [--execute] : Perform writes
         * [--limit=<n>] : Limit rows per step
         * [--offset=<n>] : Offset rows per step
         */
        public function import_all($args, $assoc_args) {
            $this->dry_run = ! isset($assoc_args['execute']);
            $this->ensure_idmap_table();

            // 1) taxonomy terms (group types)
            $this->import_group_types($args, $assoc_args);

            // 2) companies
            $this->import_companies($args, $assoc_args);

            // 3) company ↔ group type terms (many-to-many)
            $this->import_company_terms($args, $assoc_args);

            // 4) users FIRST (so businesses can attach owner by email/UserId)
            $this->import_users($args, $assoc_args);

            // 5) businesses
            $this->import_businesses($args, $assoc_args);

            // 6) addresses (custom table)
            $this->import_addresses($args, $assoc_args);

            // 7) CSR Applications -> nmda-applications (3 tables)
            $this->import_csr('advertising', $args, $assoc_args);
            $this->import_csr('labels',      $args, $assoc_args);
            $this->import_csr('lead',        $args, $assoc_args);

            WP_CLI::success(($this->dry_run ? 'Dry-run ' : 'Execute ') . 'completed for ALL steps.');
        }

        /* =========================================
         * Taxonomy: nmda_group_type
         * ========================================= */

        /**
         * Seed nmda_group_type taxonomy terms from nmda_group_type.sql
         */
        public function import_group_types($args, $assoc_args) {
            $this->dry_run = ! isset($assoc_args['execute']);
            $this->ensure_idmap_table();

            $file = $this->portal_dir . 'nmda_group_type.sql';
            $rows = $this->parse_sql_inserts($file, 'group_type');
            $rows = $this->apply_limit_offset($rows, $assoc_args);

            if (!taxonomy_exists('nmda_group_type')) {
                WP_CLI::error('Taxonomy nmda_group_type is not registered. Ensure your theme/plugin registers it first.');
            }

            WP_CLI::log('Seeding nmda_group_type terms...');
            $progress = \WP_CLI\Utils\make_progress_bar('Group Types', count($rows));
            $created = 0; $skipped = 0;

            foreach ($rows as $row) {
                $progress->tick();
                $legacy_id = $row['GroupTypeId'] ?? $row['groupTypeId'] ?? null;
                $name      = trim($row['GroupType'] ?? $row['groupType'] ?? '');

                if (!$legacy_id || $name === '') { $skipped++; continue; }

                $term_id = $this->get_mapped_id($legacy_id, 'group_type_term');
                if ($term_id) { $skipped++; continue; }

                if ($this->dry_run) { $created++; continue; }

                $term = term_exists($name, 'nmda_group_type');
                if (!$term) {
                    $term = wp_insert_term($name, 'nmda_group_type', array(
                        'slug' => sanitize_title($name),
                    ));
                }
                if (is_wp_error($term)) {
                    WP_CLI::warning('Term error: ' . $term->get_error_message());
                    continue;
                }
                $tid = (int)(is_array($term) ? $term['term_id'] : $term);
                update_term_meta($tid, '_legacy_group_type_id', $legacy_id);
                $this->set_mapping($legacy_id, 'group_type_term', $tid);
                $created++;
            }

            $progress->finish();
            WP_CLI::success(sprintf('%s: created=%d, skipped=%d',
                $this->dry_run ? 'Dry-run' : 'Done', $created, $skipped
            ));
        }

        /**
         * Assign nmda_group_type terms to companies from nmda_company_groups.sql
         */
        public function import_company_terms($args, $assoc_args) {
            $this->dry_run = ! isset($assoc_args['execute']);
            $this->ensure_idmap_table();

            $file = $this->portal_dir . 'nmda_company_groups.sql';
            $rows = $this->parse_sql_inserts($file, 'company_groups');
            $rows = $this->apply_limit_offset($rows, $assoc_args);

            $progress = \WP_CLI\Utils\make_progress_bar('Company↔GroupType', count($rows));
            $assigned = 0; $skipped = 0;

            foreach ($rows as $row) {
                $progress->tick();
                $company_id    = $row['CompanyId']   ?? null;
                $group_type_id = $row['GroupTypeId'] ?? null;
                if (!$company_id || !$group_type_id) { $skipped++; continue; }

                $post_id = $this->get_mapped_id($company_id, 'company');
                $term_id = $this->get_mapped_id($group_type_id, 'group_type_term');

                if (!$post_id || !$term_id) { $skipped++; continue; }
                if ($this->dry_run) { $assigned++; continue; }

                wp_set_post_terms($post_id, array($term_id), 'nmda_group_type', true);
                $assigned++;
            }

            $progress->finish();
            WP_CLI::success(sprintf('%s: assigned=%d, skipped=%d',
                $this->dry_run ? 'Dry-run' : 'Done', $assigned, $skipped
            ));
        }

        /* =========================================
         * Companies (CPT: company)
         * ========================================= */

        public function import_companies($args, $assoc_args) {
            $this->dry_run = ! isset($assoc_args['execute']);
            $this->ensure_idmap_table();

            $file = $this->portal_dir . 'nmda_company.sql';
            $rows = $this->parse_sql_inserts($file, 'company');
            $rows = $this->apply_limit_offset($rows, $assoc_args);

            $this->assert_cpt('company');

            $progress = \WP_CLI\Utils\make_progress_bar('Companies', count($rows));
            $created = 0; $skipped = 0; $updated = 0;

            foreach ($rows as $row) {
                $progress->tick();
                $legacy_id = $row['CompanyId'] ?? null;
                $name      = trim($row['CompanyName'] ?? '');
                if (!$legacy_id || $name === '') { $skipped++; continue; }

                $post_id = $this->get_mapped_id($legacy_id, 'company');
                if ($post_id && get_post($post_id)) {
                    $updated++; continue;
                }

                if ($this->dry_run) { $created++; continue; }

                $post_id = wp_insert_post(array(
                    'post_type'   => 'company',
                    'post_status' => 'publish',
                    'post_title'  => $name,
                ), true);

                if (is_wp_error($post_id)) {
                    WP_CLI::warning('Company insert error: ' . $post_id->get_error_message());
                    continue;
                }

                $this->update_meta($post_id, array(
                    'external_company_id' => $legacy_id,
                    'company_phone'       => $row['Phone']   ?? '',
                    'company_email'       => strtolower(trim($row['Email'] ?? '')),
                    'company_website'     => $row['Website'] ?? '',
                ));

                $this->set_mapping($legacy_id, 'company', $post_id);
                $created++;
            }

            $progress->finish();
            WP_CLI::success(sprintf('%s: created=%d, updated=%d, skipped=%d',
                $this->dry_run ? 'Dry-run' : 'Done', $created, $updated, $skipped
            ));
        }

        /* =========================================
         * Users (must run BEFORE businesses)
         * ========================================= */

        public function import_users($args, $assoc_args) {
            $this->dry_run = ! isset($assoc_args['execute']);
            $this->ensure_idmap_table();

            $file = $this->portal_dir . 'nmda_user.sql';
            $rows = $this->parse_sql_inserts($file, 'user');
            $rows = $this->apply_limit_offset($rows, $assoc_args);

            $this->ensure_user_business_table();

            $progress = \WP_CLI\Utils\make_progress_bar('Users', count($rows));
            $created = 0; $skipped = 0; $updated = 0;

            foreach ($rows as $row) {
                $progress->tick();
                $legacy_uid = $row['UserId'] ?? null;
                $email      = strtolower(trim($row['Email'] ?? ''));
                $first      = trim($row['FirstName'] ?? '');
                $last       = trim($row['LastName'] ?? '');

                if (!$legacy_uid || $email === '') { $skipped++; continue; }

                $existing_uid = $this->get_mapped_id($legacy_uid, 'user');
                if ($existing_uid && get_user_by('id', $existing_uid)) {
                    $updated++;
                    // For safety, persist the user↔business mapping in the bridge table
                    $this->upsert_user_business_link($existing_uid, $row);
                    continue;
                }

                if ($this->dry_run) {
                    $created++;
                    continue;
                }

                $login = sanitize_user($email, true);
                if (username_exists($login)) {
                    $login = $this->unique_login_from_email($email);
                }

                $user_id = wp_insert_user(array(
                    'user_login'   => $login,
                    'user_email'   => $email,
                    'display_name' => trim($first . ' ' . $last) ?: $email,
                    'first_name'   => $first,
                    'last_name'    => $last,
                    'user_pass'    => wp_generate_password(20),
                    'role'         => 'subscriber',
                ));

                if (is_wp_error($user_id)) {
                    WP_CLI::warning('User insert error: ' . $user_id->get_error_message());
                    continue;
                }

                update_user_meta($user_id, '_legacy_user_id', $legacy_uid);
                if (!empty($row['CompanyId'])) {
                    update_user_meta($user_id, '_legacy_company_id', $row['CompanyId']);
                }

                // Persist linking info to the bridge table so businesses / reimbursements can find owners
                $this->upsert_user_business_link($user_id, $row);

                $this->set_mapping($legacy_uid, 'user', $user_id);
                $created++;
            }

            $progress->finish();
            WP_CLI::success(sprintf('%s: created=%d, updated=%d, skipped=%d',
                $this->dry_run ? 'Dry-run' : 'Done', $created, $updated, $skipped
            ));
        }

        /**
         * Ensure the nmda_user_business bridge table exists.
         *
         * This table lets us attach multiple users to a given business and vice versa,
         * and gives CSR reimbursements a reliable way to resolve user_id from BusinessId.
         */
        private function ensure_user_business_table() {
            global $wpdb;
            $table            = $wpdb->prefix . 'nmda_user_business';
            $charset_collate  = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE IF NOT EXISTS {$table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                business_id bigint(20) unsigned NOT NULL,
                legacy_business_id varchar(191) NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY business_id (business_id),
                KEY legacy_business_id (legacy_business_id)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        /**
         * Upsert the user↔business relationship into the nmda_user_business table
         * based on legacy CompanyId (which actually references business.BusinessId).
         *
         * @param int   $user_id
         * @param array $legacy_user_row
         */
        private function upsert_user_business_link($user_id, array $legacy_user_row) {
            global $wpdb;

            if (empty($legacy_user_row['CompanyId'])) {
                return;
            }

            $legacy_business_id = $legacy_user_row['CompanyId'];

            // Attempt to resolve the related business WP post ID if it's already mapped
            $business_post_id = $this->get_mapped_id($legacy_business_id, 'business');
            if (!$business_post_id) {
                // Business may not have been imported yet; we'll still store the legacy ID
                $business_post_id = 0;
            }

            $table = $wpdb->prefix . 'nmda_user_business';

            // If a row already exists for this user + legacy business, update it; otherwise insert
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND legacy_business_id = %s LIMIT 1",
                $user_id,
                $legacy_business_id
            ));

            $data = array(
                'user_id'           => (int) $user_id,
                'business_id'       => (int) $business_post_id,
                'legacy_business_id'=> (string) $legacy_business_id,
                'status'            => 'active',
                'updated_at'        => current_time('mysql'),
            );

            if ($existing_id) {
                $wpdb->update($table, $data, array('id' => (int) $existing_id));
            } else {
                $data['created_at'] = current_time('mysql');
                $wpdb->insert($table, $data);
            }
        }

        /* =========================================
         * Businesses (CPT: nmda_business)
         * ========================================= */

        public function import_businesses($args, $assoc_args) {
            $this->dry_run = ! isset($assoc_args['execute']);
            $this->ensure_idmap_table();

            $file = $this->portal_dir . 'nmda_business.sql';
            $rows = $this->parse_sql_inserts($file, 'business'); // <-- table name

            // Validation: Expected ~427+ businesses
            if (count($rows) < 400) {
                WP_CLI::warning(sprintf('Expected ~427 businesses but only found %d. Data may be truncated.', count($rows)));
            }

            $rows = $this->apply_limit_offset($rows, $assoc_args);

            $this->assert_cpt('nmda_business');

            $progress = \WP_CLI\Utils\make_progress_bar('Businesses', count($rows));
            $created = 0; $skipped = 0; $updated = 0;

            foreach ($rows as $row) {
                $progress->tick();
                $legacy_id = $row['BusinessId'] ?? null;
                $name      = trim($row['BusinessName'] ?? $row['DBA'] ?? '');
                if (!$legacy_id || $name === '') { $skipped++; continue; }

                // Basic duplicate detection on the source data itself
                static $seen_business_ids = array();
                if (isset($seen_business_ids[$legacy_id])) {
                    WP_CLI::warning(sprintf(
                        'Duplicate BusinessId %s encountered (%s). Skipping duplicate row.',
                        $legacy_id,
                        $name
                    ));
                    $skipped++;
                    continue;
                }
                $seen_business_ids[$legacy_id] = true;

                // Resolve mapping if we already know about this legacy BusinessId
                $post_id = $this->get_mapped_id($legacy_id, 'business');
                if ($post_id && get_post($post_id)) {
                    // Business already exists in WP; refresh taxonomy/meta bits that are safe to re-derive
                    if (!$this->dry_run) {
                        $this->assign_product_types($post_id, $row);
                        $this->assign_business_classification($post_id, $row);

                        $associate_types = $this->derive_associate_types($row);
                        if (!empty($associate_types)) {
                            update_post_meta($post_id, 'associate_type', $associate_types);
                        } else {
                            // Optionally clear if you want a strict mirror:
                            // delete_post_meta($post_id, 'associate_type');
                        }
                    }

                    $updated++;
                    continue;
                }

                // If there's no mapped post yet, try to find an existing business by legacy meta.
                if (!$post_id) {
                    $existing = $this->find_business_by_legacy_id($legacy_id);
                    if ($existing) {
                        // Reuse existing post and just ensure the mapping exists.
                        $this->set_mapping($legacy_id, 'business', $existing);

                        if (!$this->dry_run) {
                            $this->assign_product_types($existing, $row);
                            $this->assign_business_classification($existing, $row);

                            $associate_types = $this->derive_associate_types($row);
                            if (!empty($associate_types)) {
                                update_post_meta($existing, 'associate_type', $associate_types);
                            }
                        }

                        $updated++;
                        continue;
                    }
                }

                // Dry-run: count, but don't create anything.
                if ($this->dry_run) {
                    $created++;
                    continue;
                }

                // Respect legacy creation date as closely as possible for more accurate history
                $create_dt     = !empty($row['CreateDt']) ? substr($row['CreateDt'], 0, 19) : current_time('mysql');
                $post_date_gmt = get_gmt_from_date($create_dt);

                $post_id = wp_insert_post(array(
                    'post_type'         => 'nmda_business',
                    'post_status'       => 'publish',
                    'post_title'        => $name,
                    'post_content'      => $row['AdditionalInfo'] ?? '',
                    'post_date'         => $create_dt,
                    'post_date_gmt'     => $post_date_gmt,
                    'post_modified'     => $create_dt,
                    'post_modified_gmt' => $post_date_gmt,
                ), true);

                if (is_wp_error($post_id)) {
                    WP_CLI::warning('Business insert error: ' . $post_id->get_error_message());
                    continue;
                }

                // Relationships
                // IMPORTANT LEGACY SCHEMA QUIRK:
                // In the legacy database, the 'user' table has a 'CompanyId' field.
                // Despite its name, this field REFERENCES 'business.BusinessId', NOT 'company.CompanyId'!
                //
                // Legacy Relationships:
                //   user.CompanyId → business.BusinessId (user belongs to business)
                //   company_groups.CompanyId → company.CompanyId (companies have group types)

                $owner_user_id   = 0;
                $company_post_id = 0;

                // Find user(s) associated with this business via the bridge table first
                $owner_user_id = $this->find_owner_user_for_business($legacy_id);

                // Fallback to older meta-based lookup if bridge data isn't present
                if (!$owner_user_id) {
                    $user_mapping = $this->find_user_by_company_id($legacy_id);
                    if ($user_mapping) {
                        $owner_user_id = $user_mapping['user_id'];
                        // If the user has a company_id value, try to link it
                        if (!empty($user_mapping['company_id'])) {
                            // Try business first (most common case - user belongs to another business)
                            $company_post_id = $this->get_mapped_id($user_mapping['company_id'], 'business') ?: 0;

                            // Fallback: try actual company entity (edge case)
                            if (!$company_post_id) {
                                $company_post_id = $this->get_mapped_id($user_mapping['company_id'], 'company') ?: 0;
                            }
                        }
                    }
                }

                // Try to associate a "company" post by fuzzy name matching when we still don't have one
                if (!$company_post_id) {
                    $company_post_id = $this->find_company_post_for_business_name($name);
                }

                // Meta mapping to ACF
                $this->update_meta($post_id, array(
                    // External keys + relationships
                    'external_business_id' => $legacy_id,
                    'related_company'      => $company_post_id,
                    'owner_wp_user'        => $owner_user_id,
                    'legacy_created_at'    => $row['CreateDt'] ?? '',
                    'legacy_updated_flag'  => isset($row['Updated']) ? (int) $row['Updated'] : 0,

                    // Business Information
                    'dba'                  => $row['DBA'] ?? '',
                    'business_phone'       => $row['Phone'] ?? '',
                    'business_email'       => strtolower(trim($row['Email'] ?? '')),
                    'website'              => $row['Website'] ?? '',
                    'business_profile'     => $row['BusinessProfile'] ?? '',
                    'business_hours'       => $row['BusinessHours'] ?? '',
                    'num_employees'        => isset($row['NumEmployees']) ? (int)$row['NumEmployees'] : '',
                    'legacy_logo'          => $row['Logo'] ?? '',

                    // Social Media
                    'facebook'             => $row['FacebookHandle']  ?? ($row['Facebook']  ?? ''),
                    'instagram'            => $row['InstagramHandle'] ?? ($row['Instagram'] ?? ''),
                    'twitter'              => $row['TwitterHandle']   ?? ($row['Twitter']   ?? ''),
                    'social_other'         => $row['SocialOther'] ?? '',

                    // Owner/Primary Contact
                    'owner_first_name'     => $row['OwnerFirstName']   ?? '',
                    'owner_last_name'      => $row['OwnerLastName']    ?? '',
                    'is_primary_contact'   => 1,
                    'contact_first_name'   => $row['ContactFirstName'] ?? '',
                    'contact_last_name'    => $row['ContactLastName']  ?? '',
                    'contact_phone'        => $row['ContactPhone']     ?? '',
                    'contact_email'        => strtolower(trim($row['ContactEmail'] ?? '')),
                    // Contact / mailing-ish address from business table
                    'contact_address'      => $row['Address']   ?? '',
                    'contact_address_2'    => $row['Address2']  ?? '',
                    'contact_city'         => $row['City']      ?? '',
                    'contact_state'        => $row['State']     ?? '',
                    'contact_zip'          => $row['Zip']       ?? '',

                    // Treat the same as the “Primary Business Location” unless you prefer to leave blank
                    'primary_address'      => $row['Address']   ?? '',
                    'primary_address_2'    => $row['Address2']  ?? '',
                    'primary_city'         => $row['City']      ?? '',
                    'primary_state'        => $row['State']     ?? 'NM',
                    'primary_zip'          => $row['Zip']       ?? '',
                    'primary_address_type' => $row['AddressType'] ?? 'public_hours',

                    // Admin
                    'approval_status'      => ($row['Approved'] ?? 0) ? 'approved' : 'pending',
                    'admin_notes'          => $row['AdminNotes'] ?? '',
                    '_legacy_admin_initials' => $row['AdminInitials'] ?? '',
                    'approval_date'        => !empty($row['DateApproved']) ? substr($row['DateApproved'],0,10) : '',
                    'approved_by'          => '',
                ));

                // Assign product types based on boolean fields
                $this->assign_product_types($post_id, $row);

                // Mirror nmda_group_type terms from the related company (if any)
                $this->sync_business_group_types_from_company($post_id, $company_post_id);

                // Business classification (grown/taste/associate)
                $this->assign_business_classification($post_id, $row);

                // Associate types (in_person, online, restaurant, etc.)
                $associate_types = $this->derive_associate_types($row);
                if (!empty($associate_types)) {
                    update_post_meta($post_id, 'associate_type', $associate_types);
                }

                // Map legacy → new ID once
                $this->set_mapping($legacy_id, 'business', $post_id);
                $created++;
            }

            $progress->finish();
            WP_CLI::success(sprintf('%s: created=%d, updated=%d, skipped=%d',
                $this->dry_run ? 'Dry-run' : 'Done', $created, $updated, $skipped
            ));
        }


         /**
         * Backfill nmda_group_type terms on existing nmda_business posts
         * based on their related_company meta.
         *
         * Usage:
         *   wp nmda sync           (dry run)
         *   wp nmda sync --execute (actually write terms)
         */
        public function sync_business_group_types($args, $assoc_args) {
            $this->dry_run = ! isset($assoc_args['execute']);

            if (!taxonomy_exists('nmda_group_type')) {
                WP_CLI::error('Taxonomy nmda_group_type is not registered.');
                return;
            }

            $query = new \WP_Query(array(
                'post_type'      => 'nmda_business',
                'posts_per_page' => -1,
                'post_status'    => array('publish', 'pending', 'draft'),
                'fields'         => 'ids',
            ));

            $total = (int) $query->found_posts;
            WP_CLI::log(sprintf(
                'Found %d nmda_business posts to check for nmda_group_type sync.',
                $total
            ));

            $progress  = \WP_CLI\Utils\make_progress_bar('Syncing business group types', $total);
            $updated   = 0;
            $skipped   = 0;
            $no_company = 0;

            foreach ($query->posts as $business_id) {
                $progress->tick();

                $company_post_id = (int) get_post_meta($business_id, 'related_company', true);
                if (! $company_post_id) {
                    $no_company++;
                    continue;
                }

                if ($this->dry_run) {
                    $updated++;
                    continue;
                }

                $this->sync_business_group_types_from_company($business_id, $company_post_id);
                $updated++;
            }

            $progress->finish();

            WP_CLI::success(sprintf(
                '%s: updated=%d, no_related_company=%d, skipped=%d',
                $this->dry_run ? 'Dry-run' : 'Done',
                $updated,
                $no_company,
                $skipped
            ));
        }


        /**
         * Resolve a likely "owner" user_id for a given legacy BusinessId.
         * Preference:
         *   1. nmda_user_business rows with active status
         *   2. First user where usermeta _legacy_company_id matches this legacy BusinessId
         *
         * @param string $legacy_business_id
         * @return int
         */
        private function find_owner_user_for_business($legacy_business_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'nmda_user_business';

            // First look in the bridge table
            $user_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$table}
                 WHERE legacy_business_id = %s
                   AND status = 'active'
                 ORDER BY id ASC
                 LIMIT 1",
                $legacy_business_id
            ));

            if ($user_id) {
                return (int) $user_id;
            }

            // Fallback to old-style meta key lookup
            $wp_user_id = $wpdb->get_var($wpdb->prepare(
                "SELECT u.ID FROM {$wpdb->users} u
                 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                 WHERE um.meta_key = '_legacy_company_id'
                   AND um.meta_value = %s
                 LIMIT 1",
                $legacy_business_id
            ));

            return $wp_user_id ? (int) $wp_user_id : 0;
        }

        /* =========================================
         * Addresses (custom table wp_nmda_business_addresses)
         * ========================================= */

        public function import_addresses($args, $assoc_args) {
            global $wpdb;
            $this->dry_run = ! isset($assoc_args['execute']);
            $this->ensure_idmap_table();

            $do_backfill = isset($assoc_args['backfill']); // opt-in backfill

            $table = $wpdb->prefix . 'nmda_business_addresses';
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s",
                $table
            ));
            if (!$exists) {
                WP_CLI::error("Custom table {$table} does not exist.");
            }

            $file = $this->portal_dir . 'nmda_business_address.sql';
            $rows = $this->parse_sql_inserts($file, 'business_address'); // <-- table name
            $rows = $this->apply_limit_offset($rows, $assoc_args);

            $progress = \WP_CLI\Utils\make_progress_bar('Business Addresses', count($rows));
            $inserted = 0; $skipped = 0; $missing_parent = 0; $backfilled = 0;

            // Track primary address selection per legacy business
            $primary_assigned = array();

            // Collect addresses per business so we can mirror them into ACF fields
            $addresses_by_business = array();
            $primary_fields        = array();

            // Discover available columns so we can safely map optional legacy fields
            $columns         = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
            $has_category    = in_array('category', $columns, true);
            $has_other       = in_array('other', $columns, true);
            $has_reservation = in_array('reservation', $columns, true);

            foreach ($rows as $row) {
                $progress->tick();
                $legacy_bid = $row['BusinessId'] ?? null;
                if (!$legacy_bid) { $skipped++; continue; }

                $parent_post_id = $this->get_mapped_id($legacy_bid, 'business');

                // Optionally backfill a stub business if parent is missing
                if (!$parent_post_id && $do_backfill) {
                    if ($this->dry_run) {
                        $backfilled++;
                        $parent_post_id = 999999; // accounting only
                    } else {
                        $title = trim($row['AddressName'] ?? ($row['City'] ?? 'Business ' . substr($legacy_bid, 0, 6)));
                        if ($title === '') $title = 'Business ' . substr($legacy_bid, 0, 6);
                        $post_id = wp_insert_post(array(
                            'post_type'   => 'nmda_business',
                            'post_status' => 'publish',
                            'post_title'  => $title,
                            'post_content'=> '',
                        ), true);
                        if (!is_wp_error($post_id)) {
                            update_post_meta($post_id, 'external_business_id', $legacy_bid);
                            $this->set_mapping($legacy_bid, 'business', $post_id);
                            $parent_post_id = $post_id;
                            $backfilled++;
                        } else {
                            WP_CLI::warning('Backfill business error: ' . $post_id->get_error_message());
                        }
                    }
                }

                if (!$parent_post_id) { $missing_parent++; continue; }

                // Heuristics for marking a primary address per business
                $is_primary   = 0;
                $address_type = $row['AddressType'] ?? '';
                $category     = $row['Category'] ?? '';

                if (!isset($primary_assigned[$legacy_bid])) {
                    // Prefer explicit primary/public/physical markers when present
                    if (!empty($category) && stripos($category, 'primary') !== false) {
                        $is_primary = 1;
                    } elseif (!empty($address_type) && stripos($address_type, 'physical') !== false) {
                        $is_primary = 1;
                    }

                    // If nothing obvious is marked, treat the first address as primary
                    if ($is_primary === 0) {
                        $is_primary = 1;
                    }

                    $primary_assigned[$legacy_bid] = true;
                }

                // Build ACF-friendly payloads
                if (!$this->dry_run) {
                    $acf_location = array(
                        'location_name' => $row['AddressName']   ?? '',
                        'address'       => $row['Address']       ?? '',
                        'address_2'     => $row['Address2']      ?? '',
                        'city'          => $row['City']          ?? '',
                        'state'         => $row['State']         ?? '',
                        'zip'           => $row['Zip']           ?? '',
                        'location_type' => $this->map_location_type($address_type, $category),
                        'phone'         => $row['Phone']         ?? '',
                        'email'         => strtolower(trim($row['Email'] ?? '')),
                        'county'        => $row['County']        ?? '',
                        'country'       => $row['Country']       ?? 'USA',
                    );

                    $addresses_by_business[$parent_post_id][] = $acf_location;

                    // Capture primary address fields for the business
                    if ($is_primary && !isset($primary_fields[$parent_post_id])) {
                        $primary_fields[$parent_post_id] = array(
                            'primary_address'          => $row['Address']   ?? '',
                            'primary_address_2'        => $row['Address2']  ?? '',
                            'primary_city'             => $row['City']      ?? '',
                            'primary_state'            => $row['State']     ?? 'NM',
                            'primary_zip'              => $row['Zip']       ?? '',
                            'primary_county'           => $row['County']    ?? '',
                            'primary_address_type'     => $this->map_primary_address_type($address_type, $category, $row),
                            'reservation_instructions' => $row['Reservation'] ?? '',
                            'other_instructions'       => $row['Other'] ?? '',
                        );
                    }
                }

                if ($this->dry_run) { $inserted++; continue; }

                $data = array(
                    'business_id'    => (int)$parent_post_id,
                    'address_type'   => $row['AddressType']   ?? '',
                    'address_name'   => $row['AddressName']   ?? '',
                    'address_line_1' => $row['Address']       ?? '',
                    'address_line_2' => $row['Address2']      ?? '',
                    'city'           => $row['City']          ?? '',
                    'state'          => $row['State']         ?? '',
                    'zip_code'       => $row['Zip']           ?? '',
                    'county'         => $row['County']        ?? '',
                    'country'        => $row['Country']       ?? 'USA',
                    'phone'          => $row['Phone']         ?? '',
                    'email'          => strtolower(trim($row['Email'] ?? '')),
                );

                // Conditionally map additional legacy fields into table columns when present
                if ($has_category) {
                    $data['category'] = $row['Category'] ?? '';
                }
                if ($has_other) {
                    $data['other'] = $row['Other'] ?? '';
                }
                if ($has_reservation) {
                    $data['reservation'] = $row['Reservation'] ?? '';
                }

                $data['is_primary'] = $is_primary;
                $data['created_at'] = current_time('mysql');
                $data['updated_at'] = current_time('mysql');

                $wpdb->insert($table, $data);
                $inserted++;
            }

            $progress->finish();

            // Mirror imported addresses into ACF fields
            if (!$this->dry_run) {
                foreach ($addresses_by_business as $business_post_id => $locations) {
                    if (empty($business_post_id)) { continue; }

                    // Primary address fields
                    if (!empty($primary_fields[$business_post_id])) {
                        foreach ($primary_fields[$business_post_id] as $meta_key => $meta_value) {
                            update_post_meta($business_post_id, $meta_key, $meta_value);
                        }
                    }

                    // Extra locations repeater
                    if (function_exists('update_field')) {
                        update_field('extra_locations', array_values($locations), $business_post_id);
                    } else {
                        update_post_meta($business_post_id, 'extra_locations', $locations);
                    }
                }
            }

            WP_CLI::success(sprintf('%s: inserted=%d, backfilled_businesses=%d, missing_parent=%d, skipped=%d',
                $this->dry_run ? 'Dry-run' : 'Done', $inserted, $backfilled, $missing_parent, $skipped
            ));
        }

        /**
         * Map legacy address types into the newer location_type options.
         */
        private function map_location_type($address_type, $category) {
            $source = strtolower(trim(($address_type ?: '') . ' ' . ($category ?: '')));

            $map = array(
                'mail'      => 'mailing',
                'shipping'  => 'shipping',
                'billing'   => 'billing',
                'warehouse' => 'warehouse',
                'retail'    => 'retail',
                'store'     => 'retail',
                'office'    => 'office',
            );

            foreach ($map as $needle => $value) {
                if (strpos($source, $needle) !== false) {
                    return $value;
                }
            }

            return 'physical';
        }

        /**
         * Map primary address visibility/type to ACF select values.
         */
        private function map_primary_address_type($address_type, $category, array $row) {
            $source = strtolower(trim(($address_type ?: '') . ' ' . ($category ?: '')));

            if (!empty($row['Reservation']) || strpos($source, 'reservation') !== false) {
                return 'public_reservation';
            }

            if (strpos($source, 'not open') !== false || strpos($source, 'private') !== false || strpos($source, 'office') !== false) {
                return 'not_public';
            }

            if (!empty($row['Other']) && strpos($source, 'other') !== false) {
                return 'other';
            }

            return 'public_hours';
        }

        /* =========================================
         * CSR Applications (CPT: nmda-applications)
         * ========================================= */

        /**
         * Import CSR tables into wp_nmda_reimbursements custom table.
         *
         * @param string $type 'advertising' | 'labels' | 'lead'
         */
        public function import_csr($type, $args, $assoc_args) {
            global $wpdb;
            $this->dry_run = ! isset($assoc_args['execute']);
            $this->ensure_idmap_table();

            // Get reimbursements table name
            $table = $wpdb->prefix . 'nmda_reimbursements';

            switch ($type) {
                case 'advertising':
                    $file = $this->portal_dir . 'nmda_csr_advertising.sql';
                    $table_label = 'csr_advertising';
                    $id_key = 'AdvertisingId';
                    break;
                case 'labels':
                    $file = $this->portal_dir . 'nmda_csr_labels.sql';
                    $table_label = 'csr_labels';
                    $id_key = 'LabelsId';
                    break;
                case 'lead':
                    $file = $this->portal_dir . 'nmda_csr_lead.sql';
                    $table_label = 'csr_lead';
                    $id_key = 'LeadId';
                    break;
                default:
                    WP_CLI::error('Unknown CSR type: ' . $type);
                    return;
            }

            $rows = $this->parse_sql_inserts($file, $table_label);
            $rows = $this->apply_limit_offset($rows, $assoc_args);

            $progress = \WP_CLI\Utils\make_progress_bar(strtoupper($type) . ' Reimbursements', count($rows));
            $created = 0; $skipped = 0; $updated = 0; $errors = 0;

            foreach ($rows as $row) {
                $progress->tick();

                $legacy_app_id = $row[$id_key] ?? null;
                $legacy_bid    = $row['BusinessId'] ?? null;

                if (!$legacy_app_id || !$legacy_bid) {
                    $skipped++;
                    continue;
                }

                // Check if already imported
                $existing_id = $this->get_mapped_id($legacy_app_id, 'csr_' . $type);
                if ($existing_id) {
                    // Verify it exists in the reimbursements table
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$table} WHERE id = %d",
                        $existing_id
                    ));
                    if ($exists) {
                        $updated++;
                        continue;
                    }
                }

                if ($this->dry_run) {
                    $created++;
                    continue;
                }

                // Get mapped business_id
                $business_id = $this->get_mapped_id($legacy_bid, 'business');
                if (!$business_id) {
                    WP_CLI::warning("Business not found for legacy ID: {$legacy_bid}");
                    $errors++;
                    continue;
                }

                // Determine status from legacy approval flags
                $status = 'submitted';
                if (!empty($row['Approved'])) {
                    $status = 'approved';
                } elseif (!empty($row['Rejected'])) {
                    $status = 'rejected';
                }

                // Extract fiscal year from submission date
                $fiscal_year = '';
                if (!empty($row['DateSubmitted'])) {
                    $date = substr($row['DateSubmitted'], 0, 10);
                    $year = substr($date, 0, 4);
                    if ($year) {
                        $fiscal_year = $year;
                    }
                }

                // Get user_id - try to find the business owner
                $user_id = 0;
                $owner_id = get_post_meta($business_id, 'owner_wp_user', true);
                if ($owner_id) {
                    $user_id = $owner_id;
                } else {
                    // Fallback: get any user associated with this business from bridge table
                    $users = $wpdb->get_results($wpdb->prepare(
                        "SELECT user_id FROM {$wpdb->prefix}nmda_user_business
                        WHERE business_id = %d AND status = 'active' LIMIT 1",
                        $business_id
                    ));
                    if (!empty($users)) {
                        $user_id = $users[0]->user_id;
                    }
                }

                // If still no user, use admin user ID 1 as fallback for historical data
                if (!$user_id) {
                    $user_id = 1;
                }

                // Build type-specific data array
                $data_array = array(
                    'legacy_id'      => $legacy_app_id,
                    'company_name'   => $row['CompanyName'] ?? '',
                    'admin_initials' => $row['AdminInitials'] ?? '',
                    'date_submitted' => $row['DateSubmitted'] ?? '',
                    'date_approved'  => $row['DateApproved'] ?? '',
                    'date_rejected'  => $row['DateRejected'] ?? '',
                );

                // Add type-specific fields
                if ($type === 'advertising') {
                    $data_array = array_merge($data_array, array(
                        'media_type'          => $row['MediaType'] ?? '',
                        'media_type_other'    => $row['MediaTypeOther'] ?? '',
                        'funding_explanation' => $row['FundingExplanation'] ?? '',
                        'expected_reach'      => $row['ExpectedReach'] ?? '',
                        'advertising_costs'   => $row['AdvertisingCosts'] ?? '',
                    ));
                } elseif ($type === 'labels') {
                    $data_array = array_merge($data_array, array(
                        // Logo Program + association flags
                        'logo_program'         => $row['LogoProgram'] ?? '',
                        'chile_projects'       => isset($row['ChileProjects']) ? (int) $row['ChileProjects'] : 0,
                        'nm_products'          => isset($row['NMProducts']) ? (int) $row['NMProducts'] : 0,
                        'nm_true'              => isset($row['NMTrue']) ? (int) $row['NMTrue'] : 0,
                        'nm_value_added'       => isset($row['NMValueAdded']) ? (int) $row['NMValueAdded'] : 0,

                        // Cost + compliance details
                        'physical_labels'      => $row['PhysicalLabels'] ?? '',
                        'plate_charges'        => $row['PlateCharges'] ?? '',
                        'graphic_design_fee'   => $row['GraphicDesignFee'] ?? '',
                        'compliance_laws'      => isset($row['ComplianceLaws']) ? (int)$row['ComplianceLaws'] : 0,
                        'compliance_licensing' => isset($row['ComplianceLicensing']) ? (int)$row['ComplianceLicensing'] : 0,
                        'label_proofs'         => isset($row['LabelProofs']) ? (int)$row['LabelProofs'] : 0,
                        'notes'                => $row['NoLabels'] ?? '',
                    ));
                } elseif ($type === 'lead') {
                    $data_array = array_merge($data_array, array(
                        'event_type'            => $row['EventType'] ?? '',
                        'event_location'        => $row['EventLocation'] ?? '',
                        'event_description'     => $row['EventDescription'] ?? '',
                        'event_website'         => $row['EventWebsite'] ?? '',
                        'event_dates'           => $row['EventDates'] ?? '',
                        'event_costs'           => $row['EventCosts'] ?? '',
                        'collecting_method'     => $row['CollectingMethod'] ?? '',
                        'previous_reimbursement'=> isset($row['PreviousReimbursement']) ? (int)$row['PreviousReimbursement'] : 0,
                        'commitment'            => isset($row['Commitment']) ? (int)$row['Commitment'] : 0,
                    ));
                }

                // Parse amount from cost fields (if available)
                $amount_requested = null;
                if ($type === 'advertising' && !empty($row['AdvertisingCosts'])) {
                    // Try to extract numeric value from cost string
                    $amount_requested = $this->extract_amount($row['AdvertisingCosts']);
                } elseif ($type === 'labels') {
                    // Sum up label-related costs
                    $plate  = $this->extract_amount($row['PlateCharges'] ?? '');
                    $design = $this->extract_amount($row['GraphicDesignFee'] ?? '');
                    $amount_requested = $plate + $design;
                } elseif ($type === 'lead' && !empty($row['EventCosts'])) {
                    $amount_requested = $this->extract_amount($row['EventCosts']);
                }

                // Insert into reimbursements table
                $insert_result = $wpdb->insert(
                    $table,
                    array(
                        'business_id'      => $business_id,
                        'user_id'          => $user_id,
                        'type'             => $type,
                        'status'           => $status,
                        'fiscal_year'      => $fiscal_year ?: date('Y'),
                        'amount_requested' => $amount_requested,
                        'amount_approved'  => ($status === 'approved') ? $amount_requested : null,
                        'data'             => json_encode($data_array),
                        'documents'        => json_encode(array()),
                        'admin_notes'      => '',
                        'reviewed_by'      => null,
                        'reviewed_at'      => !empty($row['DateApproved']) ? $row['DateApproved'] : (!empty($row['DateRejected']) ? $row['DateRejected'] : null),
                        'created_at'       => !empty($row['DateSubmitted']) ? $row['DateSubmitted'] : current_time('mysql'),
                    ),
                    array('%d', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s')
                );

                if (!$insert_result) {
                    WP_CLI::warning("Failed to insert reimbursement for legacy ID {$legacy_app_id}: " . $wpdb->last_error);
                    $errors++;
                    continue;
                }

                $reimbursement_id = $wpdb->insert_id;
                $this->set_mapping($legacy_app_id, 'csr_' . $type, $reimbursement_id);
                $created++;
            }

            $progress->finish();
            WP_CLI::success(sprintf('[%s] %s: created=%d, updated=%d, skipped=%d, errors=%d',
                $type,
                $this->dry_run ? 'Dry-run' : 'Done',
                $created, $updated, $skipped, $errors
            ));
        }

        /**
         * Extract numeric amount from string (e.g., "$1,234.56" => 1234.56)
         */
        private function extract_amount($str) {
            if (empty($str)) return null;
            // Remove currency symbols, commas, and non-numeric chars except decimal point
            $cleaned = preg_replace('/[^\d.]/', '', $str);
            $amount = floatval($cleaned);
            return $amount > 0 ? $amount : null;
        }

        /* =========================================
         * Utilities / Infra
         * ========================================= */

        private function assert_cpt($cpt) {
            if (!post_type_exists($cpt)) {
                WP_CLI::error("CPT {$cpt} is not registered. Ensure it is registered before running the import.");
            }
        }

        private function apply_limit_offset(array $rows, array $assoc_args) {
            $limit  = isset($assoc_args['limit'])  ? (int)$assoc_args['limit']  : 0;
            $offset = isset($assoc_args['offset']) ? (int)$assoc_args['offset'] : 0;
            if ($offset > 0) $rows = array_slice($rows, $offset);
            if ($limit  > 0) $rows = array_slice($rows, 0, $limit);
            return $rows;
        }

        private function update_meta($post_id, array $pairs) {
            foreach ($pairs as $k => $v) {
                update_post_meta($post_id, $k, $v);
            }
        }

        private function unique_login_from_email($email) {
            $base = sanitize_user(preg_replace('/@.*/', '', $email), true);
            $try  = $base;
            $i=1;
            while (username_exists($try)) {
                $try = $base . '_' . $i++;
            }
            return $try;
        }

        /* ===== Id Mapping Table ===== */

        private function ensure_idmap_table() {
            global $wpdb;
            $table = $wpdb->prefix . 'nmda_id_mapping';
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS {$table} (
                old_id varchar(191) NOT NULL,
                entity_type varchar(64) NOT NULL,
                new_id bigint(20) unsigned NOT NULL,
                PRIMARY KEY (old_id, entity_type),
                KEY entity_type (entity_type),
                KEY new_id (new_id)
            ) {$charset_collate};";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        private function get_mapped_id($old_id, $type) {
            global $wpdb;
            $table = $wpdb->prefix . 'nmda_id_mapping';
            return (int)$wpdb->get_var($wpdb->prepare(
                "SELECT new_id FROM {$table} WHERE old_id=%s AND entity_type=%s",
                (string)$old_id, (string)$type
            ));
        }

        private function set_mapping($old_id, $type, $new_id) {
            if ($this->dry_run) return;
            global $wpdb;
            $table = $wpdb->prefix . 'nmda_id_mapping';
            $wpdb->replace($table, array(
                'old_id'      => (string)$old_id,
                'entity_type' => (string)$type,
                'new_id'      => (int)$new_id,
            ));
        }

        /**
         * Find user associated with a business
         * In the old system, user.CompanyId references business.BusinessId
         *
         * @param string $business_id The business BusinessId (UUID)
         * @return array|null Array with user_id and company_id, or null if not found
         */
        private function find_user_by_company_id($business_id) {
            global $wpdb;

            // Look up users where their old CompanyId = this business_id
            // We stored user.CompanyId as '_legacy_company_id' meta during user import
            $wp_user_id = $wpdb->get_var($wpdb->prepare(
                "SELECT u.ID FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                WHERE um.meta_key = '_legacy_company_id'
                AND um.meta_value = %s
                LIMIT 1",
                $business_id
            ));

            if (!$wp_user_id) {
                return null;
            }

            // Get the user's legacy company ID (which might be a company or business UUID)
            $company_id = get_user_meta($wp_user_id, '_legacy_company_id', true);

            return array(
                'user_id' => (int)$wp_user_id,
                'company_id' => $company_id
            );
        }

        /* ===== SQL INSERT parser (quote-aware, per-table) ===== */

        /**
         * Parse INSERTs for a specific table. Supports both:
         *   INSERT INTO `table` (cols...) VALUES (...),(...);
         *   INSERT INTO `table` VALUES (...),(...);
         */
        private function parse_sql_inserts($file, $table_name) {
            if (!file_exists($file)) {
                WP_CLI::warning("Missing SQL file: {$file}");
                return array();
            }
            $contents = file_get_contents($file);
            if ($contents === false || $contents === '') {
                WP_CLI::warning("Empty SQL file: {$file}");
                return array();
            }

            // Detailed logging for troubleshooting
            WP_CLI::log(sprintf('Reading SQL file: %s', basename($file)));
            WP_CLI::log(sprintf('File size: %s bytes', number_format(strlen($contents))));
            WP_CLI::log(sprintf('Looking for table: %s', $table_name));

            // Lightly normalize whitespace (leave quotes untouched)
            $sql = preg_replace('/\s+/', ' ', $contents);

            $rows = array();

            // Get column order from this table's CREATE
            $default_columns = $this->extract_columns_from_create_table($sql, $table_name);

            // A) INSERT INTO `<table>` (cols...) VALUES ...
            $with_cols = '/INSERT\s+INTO\s+`?' . preg_quote($table_name, '/') . '`?\s*\((.*?)\)\s*VALUES\s*(.*?);(?=\s*(?:UNLOCK|SET|DROP|CREATE|ALTER|INSERT|\/\*|$))/is';
            if (preg_match_all($with_cols, $sql, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $cols_raw = trim($m[1]);
                    $vals_raw = trim($m[2]);

                    $columns = array();
                    foreach (explode(',', $cols_raw) as $c) {
                        $c = trim($c);
                        $c = trim($c, '` ');
                        if ($c !== '') $columns[] = $c;
                    }

                    $tuples = $this->split_value_tuples($vals_raw);
                    foreach ($tuples as $tuple) {
                        $values = $this->split_values_respecting_quotes($tuple);
                        $assoc  = array();
                        foreach ($columns as $i => $col) {
                            $assoc[$col] = isset($values[$i]) ? $this->unquote_sql_value($values[$i]) : null;
                        }
                        $rows[] = $assoc;
                    }
                }
            }

            // B) INSERT INTO `<table>` VALUES ...
            $no_cols = '/INSERT\s+INTO\s+`?' . preg_quote($table_name, '/') . '`?\s*VALUES\s*(.*?);(?=\s*(?:UNLOCK|SET|DROP|CREATE|ALTER|INSERT|\/\*|$))/is';
            if (preg_match_all($no_cols, $sql, $matches2, PREG_SET_ORDER)) {
                foreach ($matches2 as $m2) {
                    $vals_raw = trim($m2[1]);
                    $tuples = $this->split_value_tuples($vals_raw);
                    foreach ($tuples as $tuple) {
                        $values  = $this->split_values_respecting_quotes($tuple);
                        $columns = $default_columns ?: array_map(function($i){ return 'col_' . ($i+1); }, array_keys($values));
                        $assoc   = array();
                        foreach ($columns as $i => $col) {
                            $assoc[$col] = isset($values[$i]) ? $this->unquote_sql_value($values[$i]) : null;
                        }
                        $rows[] = $assoc;
                    }
                }
            }

            WP_CLI::log(sprintf('Parsed %d %s rows from %s', count($rows), $table_name, basename($file)));

            // Validation: Check for zero-row parsing failures
            if (count($rows) === 0) {
                WP_CLI::error(sprintf('No rows parsed from %s! Check SQL file format and table name.', basename($file)));
            }

            return $rows;
        }

        /**
         * Extract column order from CREATE TABLE `<table_name>` (...)
         */
        private function extract_columns_from_create_table($sql_text, $table_name) {
            $cols = array();
            $regex = '/CREATE\s+TABLE\s+`?' . preg_quote($table_name, '/') . '`?\s*\((.*?)\)\s*ENGINE/si';
            if (preg_match($regex, $sql_text, $m)) {
                $body = $m[1];
                // split on commas not inside parentheses
                $parts = preg_split('/,(?![^\(]*\))/s', $body);
                foreach ($parts as $line) {
                    $line = trim($line);
                    if (preg_match('/^`([^`]+)`\s+/s', $line, $cm)) {
                        $cols[] = $cm[1];
                    }
                }
            }
            return $cols;
        }

        /**
         * Quote-aware tuple splitter: returns ["(...)", "(...)", ...]
         */
        private function split_value_tuples($vals_raw) {
            $out = array();
            $buf = '';
            $level = 0;
            $in = false; $q = '';
            $len = strlen($vals_raw);
            for ($i = 0; $i < $len; $i++) {
                $ch = $vals_raw[$i];

                if ($in) {
                    $buf .= $ch;
                    if ($ch === '\\' && $i + 1 < $len) { $buf .= $vals_raw[++$i]; continue; }
                    if ($ch === $q) { $in = false; }
                    continue;
                }

                if ($ch === '\'' || $ch === '"') { $in = true; $q = $ch; $buf .= $ch; continue; }

                if ($ch === '(') {
                    if ($level === 0) { $buf = ''; }
                    $level++;
                    $buf .= $ch;
                    continue;
                }
                if ($ch === ')') {
                    $level--;
                    $buf .= $ch;
                    if ($level === 0) { $out[] = $buf; $buf = ''; }
                    continue;
                }

                $buf .= $ch;
            }
            return $out;
        }

        /**
         * Split values inside a single "(...)" tuple, respecting quotes/escapes.
         */
        private function split_values_respecting_quotes($tuple) {
            $tuple = trim($tuple);
            if (substr($tuple,0,1)==='(' && substr($tuple,-1)===')') {
                $tuple = substr($tuple, 1, -1);
            }
            $out=array(); $buf=''; $in=false; $q=''; $len=strlen($tuple);
            for ($i=0; $i<$len; $i++) {
                $ch = $tuple[$i];
                if ($in) {
                    if ($ch === '\\' && $i+1 < $len) { $buf .= $tuple[$i]; $buf .= $tuple[++$i]; continue; }
                    if ($ch === $q) { $in = false; $buf .= $ch; continue; }
                    $buf .= $ch; continue;
                } else {
                    if ($ch === '\'' || $ch === '"') { $in=true; $q=$ch; $buf.=$ch; continue; }
                    if ($ch === ',') { $out[] = trim($buf); $buf=''; continue; }
                    $buf.=$ch;
                }
            }
            if ($buf!=='') $out[] = trim($buf);
            return $out;
        }

        private function unquote_sql_value($v) {
            $v = trim($v);
            if (strcasecmp($v, 'NULL') === 0) return null;
            if (strlen($v) >= 2) {
                $first = substr($v,0,1); $last = substr($v,-1);
                if (($first==="'" && $last==="'") || ($first==='"' && $last==='"')) {
                    $inner = substr($v,1,-1);
                    $inner = str_replace(array("\\'",'\\"',"\\\\"), array("'",'"',"\\"), $inner);
                    return $inner;
                }
            }
            if (is_numeric($v)) {
                return (strpos($v, '.') !== false) ? (float)$v : (int)$v;
            }
            return $v;
        }

        /* ===== Product Type Taxonomy Assignment ===== */

        /**
         * Get comprehensive product type field mapping
         */
        private function get_product_type_mapping() {
            return array(
                'ClassGrown' => 'Farms + Ranches',
                'ClassTaste' => 'Local Food + Drink',
                'ClassAssociate' => 'Shop Local',
                'AssociateInPerson' => 'In-Person Retail',
                'AssociateOnline' => 'Online Retail',
                'AssociateRestaurant' => 'Restaurant',
                'AssociateTourism' => 'Tourism',
                'AssociateArtisan' => 'Artisan',
                'AssociatePet' => 'Pet Products',
                'AssociateEducational' => 'Educational',
                'AssociateNonProfit' => 'Non-Profit',
                'ContainsGreenChile' => 'Contains Green Chile',
                'ContainsRedChile' => 'Contains Red Chile',
                'ContainsBellPepper' => 'Contains Bell Pepper',
                'ContainsJalapeno' => 'Contains Jalapeno',
                'ContainsChiledeArbol' => 'Contains Chile de Arbol',
                'ContainsCayenne' => 'Contains Cayenne',
                'ContainsPaprika' => 'Contains Paprika',
                'ContainsChimayo' => 'Contains Chimayo',
                'ContainsAncho' => 'Contains Ancho',
                'ContainsPoblano' => 'Contains Poblano',
                'ContainsPasillaChile' => 'Contains Pasilla Chile',
                'GrownApples' => 'Apples',
                'GrownApplesOrganic' => 'Apples (Organic)',
                'GrownChiles' => 'Chiles or Peppers',
                'GrownChilesOrganic' => 'Chiles or Peppers (Organic)',
                'GrownLettuces' => 'Lettuces',
                'GrownLettucesOrganic' => 'Lettuces (Organic)',
                'GrownCabbages' => 'Cabbages',
                'GrownCabbagesOrganic' => 'Cabbages (Organic)',
                'GrownOnions' => 'Onions',
                'GrownOnionsOrganic' => 'Onions (Organic)',
                'GrownPotatoes' => 'Potatoes',
                'GrownPotatoesOrganic' => 'Potatoes (Organic)',
                'GrownPumpkins' => 'Pumpkins',
                'GrownPumpkinsOrganic' => 'Pumpkins (Organic)',
                'GrownWatermelons' => 'Watermelons',
                'GrownWatermelonsOrganic' => 'Watermelons (Organic)',
                'GrownPeanuts' => 'Peanuts',
                'GrownPeanutsOrganic' => 'Peanuts (Organic)',
                'GrownLegumes' => 'Other Legumes and Beans',
                'GrownLegumesOrganic' => 'Other Legumes and Beans (Organic)',
                'GrownPecans' => 'Pecans',
                'GrownPecansOrganic' => 'Pecans (Organic)',
                'GrownPistachios' => 'Pistachios',
                'GrownPistachiosOrganic' => 'Pistachios (Organic)',
                'GrownPinon' => 'Pinon',
                'GrownPinonOrganic' => 'Pinon (Organic)',
                'GrownCattle' => 'Cattle',
                'GrownCattleOrganic' => 'Cattle (Organic)',
                'GrownCattleBeef' => 'Cattle - Beef',
                'GrownCattleDairy' => 'Cattle - Dairy',
                'GrownPigs' => 'Pigs',
                'GrownPigsOrganic' => 'Pigs (Organic)',
                'GrownSheep' => 'Sheep',
                'GrownSheepConventional' => 'Sheep (Conventional)',
                'GrownSheepOrganic' => 'Sheep (Organic)',
                'GrownSheepMeat' => 'Sheep - Meat',
                'GrownSheepDairy' => 'Sheep - Dairy',
                'GrownSheepFiber' => 'Sheep - Fiber',
                'GrownGoats' => 'Goats',
                'GrownGoatsConventional' => 'Goats (Conventional)',
                'GrownGoatsOrganic' => 'Goats (Organic)',
                'GrownGoatsMeat' => 'Goats - Meat',
                'GrownGoatsDairy' => 'Goats - Dairy',
                'GrownGoatsFiber' => 'Goats - Fiber',
                'GrownChickens' => 'Chickens',
                'GrownChickensOrganic' => 'Chickens (Organic)',
                'GrownChickensFreeRange' => 'Chickens (Free Range)',
                'GrownChickensMeat' => 'Chickens - Meat',
                'GrownChickensLayer' => 'Chickens - Eggs/Layer',
                'GrownGame' => 'Game Birds',
                'GrownGameOrganic' => 'Game Birds (Organic)',
                'GrownGameFreeRange' => 'Game Birds (Free Range)',
                'GrownBees' => 'Apiary',
                'GrownBeesOrganic' => 'Apiary (Organic)',
                'GrownBeef' => 'Beef',
                'GrownBeefOrganic' => 'Beef (Organic)',
                'GrownBeefGrassFed' => 'Beef (Grass Fed)',
                'GrownPork' => 'Pork',
                'GrownPorkOrganic' => 'Pork (Organic)',
                'GrownLamb' => 'Lamb',
                'GrownLambOrganic' => 'Lamb (Organic)',
                'GrownPoultry' => 'Poultry',
                'GrownPoultryOrganic' => 'Poultry (Organic)',
                'GrownPoultryFreeRange' => 'Poultry (Free Range)',
                'GrownNursery' => 'Nursery',
                'GrownGreenhouse' => 'Greenhouse',
                'GrownGrains' => 'Grains',
                'GrownGrainsOrganic' => 'Grains (Organic)',
                'GrownFiber' => 'Fiber',
                'GrownFiberOrganic' => 'Fiber (Organic)',
                'GrownHemp' => 'Hemp',
                'GrownHempOrganic' => 'Hemp (Organic)',
                'GrownMushrooms' => 'Mushrooms',
                'GrownMushroomsOrganic' => 'Mushrooms (Organic)',
                'GrownFeed' => 'Feed',
                'GrownFeedOrganic' => 'Feed (Organic)',
                'GrownSeeds' => 'Seeds',
                'GrownSeedsOrganic' => 'Seeds (Organic)',
                'GrownActivityGrowing' => 'Growing',
                'GrownActivityPacking' => 'Packing',
                'GrownActivityProcessing' => 'Processing',
                'GrownActivityCoPacking' => 'Co-Packing',
                'GrownActivityDistributing' => 'Distributing',
                'GrownActivityInPerson' => 'In-Person Sales',
                'GrownActivityOnline' => 'Online Sales',
                'TasteSalsas' => 'Salsas',
                'TasteProcessedChiles' => 'Processed Chiles',
                'TasteHotSauces' => 'Hot Sauces',
                'TasteCondiments' => 'Condiments',
                'TasteSpices' => 'Spices & Rubs',
                'TasteBBQSauces' => 'BBQ Sauces',
                'TasteMarinades' => 'Marinades & Dressings',
                'TasteDriedMixes' => 'Dried Mixes',
                'TasteDriedFruits' => 'Dried Fruits',
                'TasteBlueCorn' => 'Blue Corn & Blue Corn Products',
                'TasteMeat' => 'Meat',
                'TasteDairy' => 'Dairy Products',
                'TasteEggs' => 'Eggs',
                'TasteHoney' => 'Honey',
                'TasteWines' => 'Wines',
                'TasteBeers' => 'Beers',
                'TasteSpirits' => 'Spirits',
                'TasteCiders' => 'Ciders',
                'TasteSodas' => 'Sodas',
                'TasteCoffees' => 'Coffees',
                'TasteKombuchas' => 'Kombuchas',
                'TasteBreads' => 'Breads & Pastries',
                'TasteBakedGoods' => 'Baked Goods',
                'TasteConfectionaries' => 'Confectionaries',
                'TasteJams' => 'Jams, Jellies, and Preserves',
                'TasteSyrups' => 'Syrups',
                'TasteNewMexicanFood' => 'New Mexican Food Entrees',
                'TasteSnackFoods' => 'Snack Foods',
                'TasteCannedGoods' => 'Canned Goods',
                'TasteFrozenGoods' => 'Frozen Goods',
                'TasteActivityManufacturing' => 'Manufacturing for Sale by Other Retailers',
                'TasteActivityRetailing' => 'Retailing/Direct-to-Consumer Sales',
                'TasteActivityOnline' => 'Online Retailing (No Third-Parties)',
                'TasteActivityRestaurant' => 'Tasting Room/Restaurant',
                'TasteGrownApiary' => 'Apiary',
                'TasteGrownDairy' => 'Dairy',
                'TasteGrownNursery' => 'Nursery',
                'SalesTypeLocal' => 'Sales - Local',
                'SalesTypeRegional' => 'Sales - Regional',
                'SalesTypeInState' => 'Sales - In-State',
                'SalesTypeNational' => 'Sales - National',
                'SalesTypeInternational' => 'Sales - International',
                'CurrentExportsAfrica' => 'Currently Exports - Africa',
                'CurrentExportsAsia' => 'Currently Exports - Asia',
                'CurrentExportsCaribbean' => 'Currently Exports - Caribbean',
                'CurrentExportsCentalAmerica' => 'Currently Exports - Central America',
                'CurrentExportsEurope' => 'Currently Exports - Europe',
                'CurrentExportsMiddleEast' => 'Currently Exports - Middle East',
                'CurrentExportsNorthAmerica' => 'Currently Exports - North America',
                'CurrentExportsPacificRim' => 'Currently Exports - Pacific Rim',
                'CurrentExportsSouthAmerica' => 'Currently Exports - South America',
                'CurrentExportsWorldwide' => 'Currently Exports - Worldwide',
                'InterestExportsAfrica' => 'Interested in Exporting - Africa',
                'InterestExportsAsia' => 'Interested in Exporting - Asia',
                'InterestExportsCaribbean' => 'Interested in Exporting - Caribbean',
                'InterestExportsCentalAmerica' => 'Interested in Exporting - Central America',
                'InterestExportsEurope' => 'Interested in Exporting - Europe',
                'InterestExportsMiddleEast' => 'Interested in Exporting - Middle East',
                'InterestExportsNorthAmerica' => 'Interested in Exporting - North America',
                'InterestExportsPacificRim' => 'Interested in Exporting - Pacific Rim',
                'InterestExportsSouthAmerica' => 'Interested in Exporting - South America',
                'InterestExportsWorldwide' => 'Interested in Exporting - Worldwide',
                'InterestInternationalTrade' => 'Interested in International Trade',
                'HasOrganic' => 'Has Organic Products',
                'HasGrassFed' => 'Has Grass Fed Products',
                'HasFreeRange' => 'Has Free Range Products',
            );
        }

        /**
         * Assign product types to a business based on boolean fields
         */
        private function assign_product_types($post_id, $row) {
            if ($this->dry_run) {
                return;
            }

            $mapping  = $this->get_product_type_mapping();
            $term_ids = array();

            foreach ($mapping as $field_name => $term_name) {
                if (!empty($row[$field_name]) && $row[$field_name] == 1) {
                    $term = term_exists($term_name, 'product_type');
                    if (!$term) {
                        $term = wp_insert_term($term_name, 'product_type', array(
                            'slug' => sanitize_title($term_name),
                        ));
                    }
                    if (!is_wp_error($term)) {
                        $term_id   = is_array($term) ? $term['term_id'] : $term;
                        $term_ids[] = (int)$term_id;
                    }
                }
            }

            if (!empty($term_ids)) {
                wp_set_post_terms($post_id, $term_ids, 'product_type', false);
            }
        }

        /**
         * Assign business classification (grown / taste / associate)
         * based on legacy boolean fields.
         *
         * ACF checkbox field expects values:
         *  - 'grown'
         *  - 'taste'
         *  - 'associate'
         *
         * If your field key is different, update the meta key below.
         */
        private function assign_business_classification($post_id, $row) {
            if ($this->dry_run) {
                return;
            }

            // Reuse the full product mapping so we don’t duplicate lists.
            $mapping = $this->get_product_type_mapping();

            $grown     = false;
            $taste     = false;
            $associate = false;

            foreach ($mapping as $field_name => $_label) {
                // Only consider fields that are actually "on" for this business
                if (empty($row[$field_name]) || (int) $row[$field_name] !== 1) {
                    continue;
                }

                // NEW MEXICO – Grown with Tradition® (Farmers/Ranchers)
                if (
                    $field_name === 'ClassGrown'
                    || strpos($field_name, 'Grown') === 0   // Grown*, GrownActivity*, etc.
                ) {
                    $grown = true;
                }

                // NEW MEXICO – Taste the Tradition® (Food/Beverage Manufacturers)
                if (
                    $field_name === 'ClassTaste'
                    || strpos($field_name, 'Taste') === 0   // Taste*, TasteActivity*, TasteGrown*
                ) {
                    $taste = true;
                }

                // NEW MEXICO – Associate Member (Retailers, Restaurants, Agritourism)
                if (
                    $field_name === 'ClassAssociate'
                    || strpos($field_name, 'Associate') === 0
                    || strpos($field_name, 'SalesType') === 0
                    || strpos($field_name, 'CurrentExports') === 0
                    || strpos($field_name, 'InterestExports') === 0
                    || $field_name === 'InterestInternationalTrade'
                ) {
                    $associate = true;
                }
            }

            $values = array();
            if ($grown) {
                $values[] = 'grown';
            }
            if ($taste) {
                $values[] = 'taste';
            }
            if ($associate) {
                $values[] = 'associate';
            }

            // Update the ACF checkbox field; adjust the meta key if needed.
            update_post_meta($post_id, 'classification', $values);
        }

        /**
         * Derive associate_type checkbox values from legacy nmda_business row.
         *
         * ACF checkbox slug: associate_type
         * Options:
         *  - in_person
         *  - online
         *  - restaurant
         *  - tourism
         *  - artisan
         *  - pet
         *  - educational
         *  - non_profit
         *  - other
         *
         * @param array $row
         * @return array List of selected option values
         */
        private function derive_associate_types(array $row) {
            $values = array();

            // Count how many Grown*/Taste* product flags are on
            $grown_taste_count = $this->count_on_flags($row, '/^(Grown|Taste)/');

            $has_in_person    = !empty($row['AssociateInPerson']);
            $has_online       = !empty($row['AssociateOnline']);
            $has_restaurant   = !empty($row['AssociateRestaurant']);
            $has_tourism      = !empty($row['AssociateTourism']);
            $has_artisan      = !empty($row['AssociateArtisan']);
            $has_pet          = !empty($row['AssociatePet']);
            $has_educational  = !empty($row['AssociateEducational']);
            $has_non_profit   = !empty($row['AssociateNonProfit']);
            $class_associate  = !empty($row['ClassAssociate']);

            // in_person : In-Person Retail (sells 3+ Taste/Grown products)
            if ($has_in_person && $grown_taste_count >= 3) {
                $values[] = 'in_person';
            }

            // online : Online Retail
            if ($has_online) {
                $values[] = 'online';
            }

            // restaurant : Restaurant (serves NM ingredients)
            // -> restaurant flag + at least one NM-grown/taste product flag
            if ($has_restaurant && $grown_taste_count >= 1) {
                $values[] = 'restaurant';
            }

            // tourism : Agritourism Operation
            if ($has_tourism) {
                $values[] = 'tourism';
            }

            // artisan : Artisan/Crafted Products
            if ($has_artisan) {
                $values[] = 'artisan';
            }

            // pet : Pet Food Manufacturer
            // For now, treat AssociatePet as enough signal; you can refine later
            if ($has_pet) {
                $values[] = 'pet';
            }

            // educational : Educational Organization
            if ($has_educational) {
                $values[] = 'educational';
            }

            // non_profit : Non-Profit Organization
            if ($has_non_profit) {
                $values[] = 'non_profit';
            }

            // other : Other (catch-all for associate businesses with no specific type)
            if ($class_associate) {
                $specific = array('in_person', 'online', 'restaurant', 'tourism', 'artisan', 'pet', 'educational', 'non_profit');
                if (empty(array_intersect($specific, $values))) {
                    $values[] = 'other';
                }
            }

            return $values;
        }


        /**
         * Count how many legacy boolean flags are "on" (==1) for keys
         * matching a given regex.
         *
         * @param array  $row   Legacy business row
         * @param string $regex PCRE regex to match field names
         * @return int
         */
        private function count_on_flags(array $row, $regex) {
            $count = 0;

            foreach ($row as $key => $value) {
                if (preg_match($regex, $key) && (int) $value === 1) {
                    $count++;
                }
            }

            return $count;
        }


        /**
         * Find an existing company post for a given business name.
         * Used as a fuzzy fallback to associate nmda_business records with company CPTs.
         *
         * @param string $business_name
         * @return int Post ID or 0 if nothing reasonable is found.
         */
        private function find_company_post_for_business_name($business_name) {
            if (empty($business_name)) {
                return 0;
            }

            // First attempt an exact match on title
            $query = new WP_Query(array(
                'post_type'      => 'company',
                'posts_per_page' => 1,
                'post_status'    => array('publish', 'pending', 'draft'),
                'title'          => $business_name,
                'fields'         => 'ids',
            ));

            if (!empty($query->posts)) {
                return (int) $query->posts[0];
            }

            // Fuzzy search by LIKE on post_title if exact title lookup fails
            global $wpdb;
            $like = '%' . $wpdb->esc_like($business_name) . '%';
            $post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_type = 'company'
                   AND post_status IN ('publish','pending','draft')
                   AND post_title LIKE %s
                 ORDER BY CHAR_LENGTH(post_title) ASC
                 LIMIT 1",
                $like
            ));

            return $post_id ? (int) $post_id : 0;
        }

        /**
         * Find an existing nmda_business post by its legacy BusinessId stored in postmeta.
         *
         * This lets us safely re-use previously imported businesses even if the mapping table
         * was cleared or the import is being re-run.
         *
         * @param string $legacy_id
         * @return int Post ID or 0 if not found.
         */
        private function find_business_by_legacy_id($legacy_id) {
            $query = new WP_Query(array(
                'post_type'      => 'nmda_business',
                'posts_per_page' => 1,
                'post_status'    => array('publish', 'pending', 'draft'),
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => 'external_business_id',
                        'value' => $legacy_id,
                    ),
                ),
            ));

            if (!empty($query->posts)) {
                return (int) $query->posts[0];
            }

            return 0;
        }

        /**
         * Validate legacy data relationships before migration
         *
         * @param array $args
         * @param array $assoc_args
         */
        public function validate_relationships($args, $assoc_args) {
            WP_CLI::line(WP_CLI::colorize("%G=== Validating Legacy Data Relationships ===%n\n"));

            $issues = array();
            $warnings = array();

            // Parse all SQL files first
            WP_CLI::log('Parsing SQL files...');
            $users = $this->parse_sql_inserts($this->portal_dir . 'nmda_user.sql', 'user');
            $businesses = $this->parse_sql_inserts($this->portal_dir . 'nmda_business.sql', 'business');
            $companies = $this->parse_sql_inserts($this->portal_dir . 'nmda_company.sql', 'company');
            $company_groups = $this->parse_sql_inserts($this->portal_dir . 'nmda_company_groups.sql', 'company_groups');
            $group_types = $this->parse_sql_inserts($this->portal_dir . 'nmda_group_type.sql', 'group_type');

            WP_CLI::log(sprintf('Found: %d users, %d businesses, %d companies', count($users), count($businesses), count($companies)));

            // Build lookup arrays for quick validation
            $business_ids = array_flip(array_column($businesses, 'BusinessId'));
            $company_ids = array_flip(array_column($companies, 'CompanyId'));
            $group_type_ids = array_flip(array_column($group_types, 'GroupTypeId'));

            // Check 1: Validate user.CompanyId references valid businesses
            WP_CLI::log("\n" . WP_CLI::colorize("%Y1. Validating User → Business relationships...%n"));
            $user_business_issues = 0;
            $users_without_company = 0;

            foreach ($users as $user) {
                $company_id = $user['CompanyId'] ?? null;
                if (!$company_id) {
                    $users_without_company++;
                    continue;
                }

                if (!isset($business_ids[$company_id])) {
                    $issues[] = "User {$user['UserId']} ({$user['Email']}) references non-existent business: {$company_id}";
                    $user_business_issues++;
                }
            }

            if ($user_business_issues > 0) {
                WP_CLI::error("{$user_business_issues} users reference invalid businesses", false);
            } else {
                WP_CLI::success("All user-business references are valid");
            }

            if ($users_without_company > 0) {
                WP_CLI::line("  {$users_without_company} users have no CompanyId (will create without business association)");
            }

            // Check 2: Validate company → group type relationships
            WP_CLI::log("\n" . WP_CLI::colorize("%Y2. Validating Company → Group Type relationships...%n"));
            $company_group_issues = 0;
            $missing_companies = 0;
            $missing_group_types = 0;

            foreach ($company_groups as $cg) {
                $company_id = $cg['CompanyId'] ?? null;
                $group_type_id = $cg['GroupTypeId'] ?? null;

                if ($company_id && !isset($company_ids[$company_id])) {
                    $warnings[] = "CompanyGroup references non-existent company: {$company_id}";
                    $missing_companies++;
                }

                if ($group_type_id && !isset($group_type_ids[$group_type_id])) {
                    $warnings[] = "CompanyGroup references non-existent group type: {$group_type_id}";
                    $missing_group_types++;
                }
            }

            if ($missing_companies > 0 || $missing_group_types > 0) {
                WP_CLI::warning("{$missing_companies} invalid company refs, {$missing_group_types} invalid group type refs");
            } else {
                WP_CLI::success("All company-group type references are valid");
            }

            // Check 3: Validate CSR business references
            WP_CLI::log("\n" . WP_CLI::colorize("%Y3. Validating CSR → Business relationships...%n"));
            $csr_types = array('advertising', 'labels', 'lead');
            $csr_business_issues = 0;

            foreach ($csr_types as $type) {
                $file = $this->portal_dir . "nmda_csr_{$type}.sql";
                if (!file_exists($file)) {
                    WP_CLI::warning("CSR file not found: {$file}");
                    continue;
                }

                $csr_rows = $this->parse_sql_inserts($file, "csr_{$type}");
                $type_issues = 0;

                foreach ($csr_rows as $csr) {
                    $business_id = $csr['BusinessId'] ?? null;
                    if ($business_id && !isset($business_ids[$business_id])) {
                        $type_issues++;
                        $csr_business_issues++;
                    }
                }

                if ($type_issues > 0) {
                    WP_CLI::line("  {$type}: {$type_issues} invalid business references");
                } else {
                    WP_CLI::line("  {$type}: All references valid");
                }
            }

            if ($csr_business_issues > 0) {
                WP_CLI::error("{$csr_business_issues} CSR records reference invalid businesses", false);
            }

            // Check 4: Validate address business references
            WP_CLI::log("\n" . WP_CLI::colorize("%Y4. Validating Address → Business relationships...%n"));
            $address_file = $this->portal_dir . 'nmda_business_address.sql';
            if (file_exists($address_file)) {
                $addresses = $this->parse_sql_inserts($address_file, 'business_address');
                $address_issues = 0;

                foreach ($addresses as $addr) {
                    $business_id = $addr['BusinessId'] ?? null;
                    if ($business_id && !isset($business_ids[$business_id])) {
                        $address_issues++;
                    }
                }

                if ($address_issues > 0) {
                    WP_CLI::error("{$address_issues} addresses reference invalid businesses", false);
                } else {
                    WP_CLI::success("All address-business references are valid");
                }
            } else {
                WP_CLI::warning("Address file not found");
            }

            // Summary
            WP_CLI::line("\n" . WP_CLI::colorize("%G=== VALIDATION SUMMARY ===%n"));
            WP_CLI::line("Total Issues: " . count($issues));
            WP_CLI::line("Total Warnings: " . count($warnings));

            if (count($issues) > 0) {
                WP_CLI::line("\n" . WP_CLI::colorize("%R=== CRITICAL ISSUES ===%n"));
                foreach ($issues as $issue) {
                    WP_CLI::error($issue, false);
                }
                WP_CLI::error("\nValidation FAILED. Fix these issues before migrating.");
            } else {
                WP_CLI::success("\nValidation PASSED. Data is ready for migration.");
            }

            if (count($warnings) > 0) {
                WP_CLI::line("\n" . WP_CLI::colorize("%Y=== WARNINGS ===%n"));
                foreach ($warnings as $warning) {
                    WP_CLI::warning($warning);
                }
            }
        }


        /**
         * Mirror nmda_group_type terms from a related company post onto a business post.
         *
         * @param int $business_post_id nmda_business post ID
         * @param int $company_post_id  related company post ID
         */
        private function sync_business_group_types_from_company($business_post_id, $company_post_id) {
            if ($this->dry_run) {
                return;
            }

            $business_post_id = (int) $business_post_id;
            $company_post_id  = (int) $company_post_id;

            if (!$business_post_id || !$company_post_id) {
                return;
            }

            if (!taxonomy_exists('nmda_group_type')) {
                // Taxonomy not registered yet – nothing to do.
                return;
            }

            $terms = wp_get_object_terms(
                $company_post_id,
                'nmda_group_type',
                array('fields' => 'ids')
            );

            if (is_wp_error($terms) || empty($terms)) {
                return;
            }

            // Overwrite, so the business stays in sync with the company’s group types.
            wp_set_post_terms($business_post_id, $terms, 'nmda_group_type', false);
        }

        /**
         * Validate migration results after completion
         *
         * @param array $args
         * @param array $assoc_args
         */
        public function validate_migration($args, $assoc_args) {
            global $wpdb;

            WP_CLI::line(WP_CLI::colorize("%G=== Post-Migration Validation ===%n\n"));

            // 1. Check ID mapping completeness
            WP_CLI::log('1. Checking ID mapping table...');
            $mapping_table = $wpdb->prefix . 'nmda_id_mapping';
            $stats = $wpdb->get_results("
                SELECT entity_type, COUNT(*) as count
                FROM {$mapping_table}
                GROUP BY entity_type
            ", ARRAY_A);

            WP_CLI::line('ID Mapping Counts:');
            foreach ($stats as $stat) {
                WP_CLI::line("  {$stat['entity_type']}: {$stat['count']}");
            }

            // 2. Check business-user relationships
            WP_CLI::log("\n2. Checking business-user relationships...");
            $businesses = $wpdb->get_results("
                SELECT p.ID, p.post_title, pm.meta_value as owner_id
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'owner_wp_user'
                WHERE p.post_type = 'nmda_business'
            ");

            $no_owner = 0;
            $with_owner = 0;
            $invalid_owner = 0;

            foreach ($businesses as $business) {
                if (empty($business->owner_id)) {
                    $no_owner++;
                } else {
                    $user = get_user_by('id', $business->owner_id);
                    if ($user) {
                        $with_owner++;
                    } else {
                        $invalid_owner++;
                    }
                }
            }

            WP_CLI::line("Business Ownership:");
            WP_CLI::line("  With valid owner: {$with_owner}");
            WP_CLI::line("  Without owner: {$no_owner}");
            if ($invalid_owner > 0) {
                WP_CLI::error("  With invalid owner: {$invalid_owner}", false);
            }

            // 3. Check reimbursement associations
            WP_CLI::log("\n3. Checking reimbursement associations...");
            $reimb_table = $wpdb->prefix . 'nmda_reimbursements';

            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$reimb_table}'") === $reimb_table;

            if ($table_exists) {
                $reimb_stats = $wpdb->get_results("
                    SELECT
                        type,
                        COUNT(*) as total,
                        SUM(CASE WHEN user_id > 0 THEN 1 ELSE 0 END) as with_user,
                        SUM(CASE WHEN user_id = 1 THEN 1 ELSE 0 END) as admin_fallback,
                        SUM(CASE WHEN business_id > 0 THEN 1 ELSE 0 END) as with_business
                    FROM {$reimb_table}
                    GROUP BY type
                ", ARRAY_A);

                WP_CLI::line("Reimbursement Associations:");
                foreach ($reimb_stats as $stat) {
                    WP_CLI::line("  {$stat['type']}:");
                    WP_CLI::line("    Total: {$stat['total']}");
                    WP_CLI::line("    With user: {$stat['with_user']}");
                    WP_CLI::line("    Admin fallback: {$stat['admin_fallback']}");
                    WP_CLI::line("    With business: {$stat['with_business']}");
                }

                // Check for broken relationships
                $broken_business = $wpdb->get_var("
                    SELECT COUNT(*)
                    FROM {$reimb_table} r
                    LEFT JOIN {$wpdb->posts} p ON r.business_id = p.ID AND p.post_type = 'nmda_business'
                    WHERE r.business_id > 0 AND p.ID IS NULL
                ");

                $broken_user = $wpdb->get_var("
                    SELECT COUNT(*)
                    FROM {$reimb_table} r
                    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                    WHERE r.user_id > 0 AND u.ID IS NULL
                ");

                if ($broken_business > 0) {
                    WP_CLI::error("  {$broken_business} reimbursements have broken business links", false);
                }
                if ($broken_user > 0) {
                    WP_CLI::error("  {$broken_user} reimbursements have broken user links", false);
                }
            } else {
                WP_CLI::warning("Reimbursements table does not exist");
            }

            // 4. Check related_company mappings
            WP_CLI::log("\n4. Checking related_company mappings...");
            $with_related = $wpdb->get_var("
                SELECT COUNT(*)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'nmda_business'
                AND pm.meta_key = 'related_company'
                AND pm.meta_value > 0
            ");

            $total_businesses = $wpdb->get_var("
                SELECT COUNT(*)
                FROM {$wpdb->posts}
                WHERE post_type = 'nmda_business'
            ");

            WP_CLI::line("Related Company Links:");
            WP_CLI::line("  Businesses with related_company: {$with_related} / {$total_businesses}");
            WP_CLI::line("  Businesses without related_company: " . ($total_businesses - $with_related));

            // Final summary
            WP_CLI::line("\n" . WP_CLI::colorize("%G=== VALIDATION COMPLETE ===%n"));
            WP_CLI::success("Review the results above for any issues");
        }
    }

    /* =========================================
     * WP-CLI command bindings
     * ========================================= */
    if (defined('WP_CLI') && WP_CLI) {
        $cmd = new NMDA_Importer_CLI_Final();
        WP_CLI::add_command('nmda import all',           [$cmd, 'import_all']);
        WP_CLI::add_command('nmda import group-types',   [$cmd, 'import_group_types']);
        WP_CLI::add_command('nmda import companies',     [$cmd, 'import_companies']);
        WP_CLI::add_command('nmda import company-terms', [$cmd, 'import_company_terms']);
        WP_CLI::add_command('nmda import users',         [$cmd, 'import_users']);
        WP_CLI::add_command('nmda import businesses',    [$cmd, 'import_businesses']);
        WP_CLI::add_command('nmda import addresses',     [$cmd, 'import_addresses']);
        WP_CLI::add_command('nmda import csr',           [$cmd, 'import_csr']);
        WP_CLI::add_command('nmda validate relationships', [$cmd, 'validate_relationships']);
        WP_CLI::add_command('nmda validate migration',   [$cmd, 'validate_migration']);
        WP_CLI::add_command('nmda sync', [$cmd, 'sync_business_group_types']);
    }
}