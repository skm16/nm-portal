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
					$updated++; continue;
				}

				if ($this->dry_run) { $created++; continue; }

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

				$this->set_mapping($legacy_uid, 'user', $user_id);
				$created++;
			}

			$progress->finish();
			WP_CLI::success(sprintf('%s: created=%d, updated=%d, skipped=%d',
				$this->dry_run ? 'Dry-run' : 'Done', $created, $updated, $skipped
			));
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

				$post_id = $this->get_mapped_id($legacy_id, 'business');
				if ($post_id && get_post($post_id)) {
					$updated++; continue;
				}

				if ($this->dry_run) { $created++; continue; }

				$post_id = wp_insert_post(array(
					'post_type'   => 'nmda_business',
					'post_status' => 'publish',
					'post_title'  => $name,
					'post_content'=> $row['AdditionalInfo'] ?? '',
				), true);

				if (is_wp_error($post_id)) {
					WP_CLI::warning('Business insert error: ' . $post_id->get_error_message());
					continue;
				}

				// Relationships
				// IMPORTANT: business table does NOT have CompanyId/UserId fields!
				// Instead, user.CompanyId references business.BusinessId
				// So we need to find users where their CompanyId matches this business's BusinessId

				$owner_user_id = 0;
				$company_post_id = 0;

				// Find user(s) associated with this business
				// user.CompanyId -> business.BusinessId
				$user_mapping = $this->find_user_by_company_id($legacy_id);
				if ($user_mapping) {
					$owner_user_id = $user_mapping['user_id'];
					// If the user has a company, link it
					if (!empty($user_mapping['company_id'])) {
						$company_post_id = $this->get_mapped_id($user_mapping['company_id'], 'company') ?: 0;
					}
				}

				// Meta mapping to ACF
				$this->update_meta($post_id, array(
					// External keys + relationships
					'external_business_id' => $legacy_id,
					'related_company'      => $company_post_id,
					'owner_wp_user'        => $owner_user_id,

					// Business Information
					'dba'                  => $row['DBA'] ?? '',
					'business_phone'       => $row['Phone'] ?? '',
					'business_email'       => strtolower(trim($row['Email'] ?? '')),
					'website'              => $row['Website'] ?? '',
					'business_profile'     => $row['BusinessProfile'] ?? '',
					'business_hours'       => $row['BusinessHours'] ?? '',
					'num_employees'        => isset($row['NumEmployees']) ? (int)$row['NumEmployees'] : '',

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
					'contact_address'      => $row['MailingAddress']   ?? '',
					'contact_address_2'    => $row['MailingAddress2']  ?? '',
					'contact_city'         => $row['MailingCity']      ?? '',
					'contact_state'        => $row['MailingState']     ?? '',
					'contact_zip'          => $row['MailingZip']       ?? '',

					// Primary Address
					'primary_address'      => $row['PhysicalAddress']  ?? '',
					'primary_address_2'    => $row['PhysicalAddress2'] ?? '',
					'primary_city'         => $row['PhysicalCity']     ?? '',
					'primary_state'        => $row['PhysicalState']    ?? 'NM',
					'primary_zip'          => $row['PhysicalZip']      ?? '',
					'primary_address_type' => 'public_hours',

					// Admin
					'approval_status'      => ($row['Approved'] ?? 0) ? 'approved' : 'pending',
					'admin_notes'          => $row['AdminNotes'] ?? '',
					'_legacy_admin_initials' => $row['AdminInitials'] ?? '',
					'approval_date'        => !empty($row['DateApproved']) ? substr($row['DateApproved'],0,10) : '',
					'approved_by'          => '',
				));

				// Assign product types based on boolean fields
				$this->assign_product_types($post_id, $row);

				$this->set_mapping($legacy_id, 'business', $post_id);
				$created++;
			}

			$progress->finish();
			WP_CLI::success(sprintf('%s: created=%d, updated=%d, skipped=%d',
				$this->dry_run ? 'Dry-run' : 'Done', $created, $updated, $skipped
			));
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
					'is_primary'     => 0,
					'created_at'     => current_time('mysql'),
					'updated_at'     => current_time('mysql'),
				);
				$wpdb->insert($table, $data);
				$inserted++;
			}

			$progress->finish();
			WP_CLI::success(sprintf('%s: inserted=%d, backfilled_businesses=%d, missing_parent=%d, skipped=%d',
				$this->dry_run ? 'Dry-run' : 'Done', $inserted, $backfilled, $missing_parent, $skipped
			));
		}

		/* =========================================
		 * CSR Applications (CPT: nmda-applications)
		 * ========================================= */

		/**
		 * Import CSR tables into nmda-applications CPT.
		 *
		 * @param string $type 'advertising' | 'labels' | 'lead'
		 */
		public function import_csr($type, $args, $assoc_args) {
			$this->dry_run = ! isset($assoc_args['execute']);
			$this->ensure_idmap_table();

			$this->assert_cpt('nmda-applications');

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

			$progress = \WP_CLI\Utils\make_progress_bar(strtoupper($type) . ' Applications', count($rows));
			$created = 0; $skipped = 0; $updated = 0;

			foreach ($rows as $row) {
				$progress->tick();

				$legacy_app_id = $row[$id_key] ?? null;
				$legacy_bid    = $row['BusinessId'] ?? null;
				$title_bits    = array_filter([
					ucfirst($type),
					trim($row['CompanyName'] ?? ''),
					!empty($row['DateSubmitted']) ? substr($row['DateSubmitted'], 0, 10) : ''
				]);
				$post_title    = implode(' – ', $title_bits) ?: strtoupper($type) . ' Application';

				if (!$legacy_app_id) { $skipped++; continue; }

				$post_id = $this->get_mapped_id($legacy_app_id, 'csr_' . $type);
				if ($post_id && get_post($post_id)) {
					$updated++; continue;
				}

				if ($this->dry_run) { $created++; continue; }

				$post_id = wp_insert_post(array(
					'post_type'   => 'nmda-applications',
					'post_status' => 'publish',
					'post_title'  => $post_title,
					'post_content'=> $row['FundingExplanation'] ?? $row['EventDescription'] ?? ($row['NoLabels'] ?? ''),
				), true);

				if (is_wp_error($post_id)) {
					WP_CLI::warning('Application insert error: ' . $post_id->get_error_message());
					continue;
				}

				// Link to business if possible
				$biz_post_id = $legacy_bid ? $this->get_mapped_id($legacy_bid, 'business') : 0;

				// Common fields
				$this->update_meta($post_id, array(
					'application_type'        => $type,
					'external_application_id' => $legacy_app_id,
					'related_business'        => $biz_post_id,
					'approved'                => !empty($row['Approved']) ? 1 : 0,
					'rejected'                => !empty($row['Rejected']) ? 1 : 0,
					'admin_initials'          => $row['AdminInitials'] ?? '',
					'date_submitted'          => $row['DateSubmitted'] ?? '',
					'date_approved'           => $row['DateApproved']  ?? '',
					'date_rejected'           => $row['DateRejected']  ?? '',
					'auth_token_guid'         => $row['authTokenGUID'] ?? '',
				));

				// Type-specific extras
				if ($type === 'advertising') {
					$this->update_meta($post_id, array(
						'media_type'         => $row['MediaType'] ?? '',
						'media_type_other'   => $row['MediaTypeOther'] ?? '',
						'funding_explanation'=> $row['FundingExplanation'] ?? '',
						'expected_reach'     => $row['ExpectedReach'] ?? '',
						'cost_breakdown'     => $row['AdvertisingCosts'] ?? '',
					));
				} elseif ($type === 'labels') {
					$this->update_meta($post_id, array(
						'logo_program'    => $row['LogoProgram'] ?? '',
						'physical_labels' => $row['PhysicalLabels'] ?? '',
						'plate_charges'   => $row['PlateCharges'] ?? '',
						'graphic_design_fee' => $row['GraphicDesignFee'] ?? '',
						'compliance_laws' => isset($row['ComplianceLaws']) ? (int)$row['ComplianceLaws'] : 0,
						'compliance_licensing' => isset($row['ComplianceLicensing']) ? (int)$row['ComplianceLicensing'] : 0,
						'label_proofs'    => isset($row['LabelProofs']) ? (int)$row['LabelProofs'] : 0,
						'notes_blob'      => $row['NoLabels'] ?? '',
					));
				} elseif ($type === 'lead') {
					$this->update_meta($post_id, array(
						'event_type'        => $row['EventType'] ?? '',
						'event_location'    => $row['EventLocation'] ?? '',
						'event_description' => $row['EventDescription'] ?? '',
						'event_website'     => $row['EventWebsite'] ?? '',
						'event_dates'       => $row['EventDates'] ?? '',
						'event_costs'       => $row['EventCosts'] ?? '',
						'collecting_method' => $row['CollectingMethod'] ?? '',
						'previous_reimbursement' => isset($row['PreviousReimbursement']) ? (int)$row['PreviousReimbursement'] : 0,
						'commitment'             => isset($row['Commitment']) ? (int)$row['Commitment'] : 0,
					));
				}

				$this->set_mapping($legacy_app_id, 'csr_' . $type, $post_id);
				$created++;
			}

			$progress->finish();
			WP_CLI::success(sprintf('[%s] %s: created=%d, updated=%d, skipped=%d',
				$type,
				$this->dry_run ? 'Dry-run' : 'Done',
				$created, $updated, $skipped
			));
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
			$table = $wpdb->prefix . 'nmda_id_mapping';

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
						$values = $this->split_values_respecting_quotes($tuple);
						$columns = $default_columns ?: array_map(function($i){ return 'col_' . ($i+1); }, array_keys($values));
						$assoc  = array();
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

			$mapping = $this->get_product_type_mapping();
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
						$term_id = is_array($term) ? $term['term_id'] : $term;
						$term_ids[] = (int)$term_id;
					}
				}
			}

			if (!empty($term_ids)) {
				wp_set_post_terms($post_id, $term_ids, 'product_type', false);
			}
		}
	}

	/* =========================================
	 * WP-CLI command bindings
	 * ========================================= */
	$cmd = new NMDA_Importer_CLI_Final();
	WP_CLI::add_command('nmda import all',           [$cmd, 'import_all']);
	WP_CLI::add_command('nmda import group-types',   [$cmd, 'import_group_types']);
	WP_CLI::add_command('nmda import companies',     [$cmd, 'import_companies']);
	WP_CLI::add_command('nmda import company-terms', [$cmd, 'import_company_terms']);
	WP_CLI::add_command('nmda import users',         [$cmd, 'import_users']);
	WP_CLI::add_command('nmda import businesses',    [$cmd, 'import_businesses']);
	WP_CLI::add_command('nmda import addresses',     [$cmd, 'import_addresses']);
	WP_CLI::add_command('nmda import csr',           [$cmd, 'import_csr']); // usage: --type=advertising|labels|lead
}
