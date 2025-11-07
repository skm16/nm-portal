<?php
/**
 * Plugin Name: NMDA Importer (WP-CLI)
 * Description: Idempotent importer for NMDA SQL dumps into WordPress CPTs/Taxonomies + custom tables.
 * Version:     0.1.0
 */

defined('ABSPATH') || exit;

if (defined('WP_CLI') && WP_CLI) {

	class NMDA_Importer_CLI {

		private $portal_dir;
		private $dry_run = true;

		public function __construct() {
			$this->portal_dir = trailingslashit(ABSPATH) . 'portal-files/';
		}

		/* =========================
		 * Entry points / Commands
		 * ========================= */

		/**
		 * Import all datasets in the correct order.
		 *
		 * ## OPTIONS
		 * [--dry-run] : Default behavior (no writes).
		 * [--execute] : Perform writes.
		 * [--limit=<n>] : Limit rows per step.
		 * [--offset=<n>] : Offset rows per step.
		 */
		public function import_all($args, $assoc_args) {
			$this->dry_run = ! isset($assoc_args['execute']);
			$limit  = isset($assoc_args['limit'])  ? (int)$assoc_args['limit']  : 0;
			$offset = isset($assoc_args['offset']) ? (int)$assoc_args['offset'] : 0;

			$this->ensure_idmap_table();

			// 1) group types
			$this->import_group_types($args, $assoc_args);
			// 2) companies
			$this->import_companies($args, $assoc_args);
			// 3) company ↔ group terms
			$this->import_company_terms($args, $assoc_args);
			// 4) businesses
			$this->import_businesses($args, $assoc_args);
			// 5) addresses
			$this->import_addresses($args, $assoc_args);
			// 6) users
			$this->import_users($args, $assoc_args);

			WP_CLI::success(($this->dry_run ? 'Dry-run ' : 'Execute ') . 'completed for ALL steps.');
		}

		/**
		 * Seed nmda_group_type taxonomy terms from nmda_group_type.sql
		 *
		 * ## OPTIONS
		 * [--dry-run] [--execute] [--limit=<n>] [--offset=<n>]
		 */
		public function import_group_types($args, $assoc_args) {
			$this->dry_run = ! isset($assoc_args['execute']);
			$this->ensure_idmap_table();

			$file = $this->portal_dir . 'nmda_group_type.sql';
			$rows = $this->parse_sql_inserts($file, 'group_type');
			$rows = $this->apply_limit_offset($rows, $assoc_args);

			if (!taxonomy_exists('nmda_group_type')) {
				WP_CLI::error('Taxonomy nmda_group_type is not registered. Load your theme/plugin that registers it first.');
			}

			WP_CLI::log('Seeding nmda_group_type terms...');
			$progress = \WP_CLI\Utils\make_progress_bar('Terms', count($rows));
			$created = 0; $skipped = 0;

			foreach ($rows as $row) {
				$progress->tick();
				$legacy_id = $row['GroupTypeId'] ?? $row['groupTypeId'] ?? null;
				$name      = trim($row['GroupType'] ?? $row['groupType'] ?? '');

				if (!$legacy_id || $name === '') {
					$skipped++; continue;
				}

				$term_id = $this->get_mapped_id($legacy_id, 'group_type_term');
				if ($term_id) { $skipped++; continue; }

				if ($this->dry_run) { $created++; continue; }

				$term = term_exists($name, 'nmda_group_type');
				if (!$term) {
					$term = wp_insert_term($name, 'nmda_group_type', array(
						'slug' => sanitize_title($name)
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
		 * Import Companies into CPT nmda_company
		 *
		 * ## OPTIONS
		 * [--dry-run] [--execute] [--limit=<n>] [--offset=<n>]
		 */
		public function import_companies($args, $assoc_args) {
			$this->dry_run = ! isset($assoc_args['execute']);
			$this->ensure_idmap_table();

			$file = $this->portal_dir . 'nmda_company.sql';
			$rows = $this->parse_sql_inserts($file, 'company');
			$rows = $this->apply_limit_offset($rows, $assoc_args);

			$this->assert_cpt('nmda_company');

			$progress = \WP_CLI\Utils\make_progress_bar('Companies', count($rows));
			$created = 0; $skipped = 0; $updated = 0;

			foreach ($rows as $row) {
				$progress->tick();
				$legacy_id = $row['CompanyId'] ?? $row['companyId'] ?? null;
				$name      = trim($row['CompanyName'] ?? $row['companyName'] ?? '');
				if (!$legacy_id || $name === '') { $skipped++; continue; }

				$post_id = $this->get_mapped_id($legacy_id, 'company');
				if ($post_id && get_post($post_id)) {
					// Optional: update existing meta if you want
					$updated++; continue;
				}

				if ($this->dry_run) { $created++; continue; }

				$post_id = wp_insert_post(array(
					'post_type'   => 'nmda_company',
					'post_status' => 'publish',
					'post_title'  => $name,
				), true);

				if (is_wp_error($post_id)) {
					WP_CLI::warning('Company insert error: ' . $post_id->get_error_message());
					continue;
				}

				// Meta mapping
				$this->update_meta($post_id, array(
					'_legacy_company_id' => $legacy_id,
					'company_first_name' => $row['FirstName'] ?? '',
					'company_last_name'  => $row['LastName'] ?? '',
					'company_addr1'      => $row['Address1'] ?? '',
					'company_addr2'      => $row['Address2'] ?? '',
					'company_city'       => $row['City'] ?? '',
					'company_state'      => $row['State'] ?? '',
					'company_zip'        => $row['Zip'] ?? '',
					'company_phone'      => $row['Phone'] ?? '',
					'company_email'      => strtolower(trim($row['Email'] ?? '')),
					'company_website'    => $row['Website'] ?? '',
					'company_logo'       => $row['Logo'] ?? '',
					'group_type_other'   => $row['GroupTypeOther'] ?? '',
					'product_type_raw'   => $row['ProductType'] ?? '',
				));

				$this->set_mapping($legacy_id, 'company', $post_id);
				$created++;
			}

			$progress->finish();
			WP_CLI::success(sprintf('%s: created=%d, updated=%d, skipped=%d',
				$this->dry_run ? 'Dry-run' : 'Done', $created, $updated, $skipped
			));
		}

		/**
		 * Assign nmda_group_type terms to companies from nmda_company_groups.sql
		 *
		 * ## OPTIONS
		 * [--dry-run] [--execute] [--limit=<n>] [--offset=<n>]
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
				$company_id   = $row['CompanyId'] ?? null;
				$group_type_id= $row['GroupTypeId'] ?? null;
				if (!$company_id || !$group_type_id) { $skipped++; continue; }

				$post_id = $this->get_mapped_id($company_id, 'company');
				$term_id = $this->get_mapped_id($group_type_id, 'group_type_term');

				if (!$post_id || !$term_id) { $skipped++; continue; }
				if ($this->dry_run) { $assigned++; continue; }

				$term = get_term($term_id, 'nmda_group_type');
				if (!$term || is_wp_error($term)) { $skipped++; continue; }

				wp_set_post_terms($post_id, array($term_id), 'nmda_group_type', true);
				$assigned++;
			}

			$progress->finish();
			WP_CLI::success(sprintf('%s: assigned=%d, skipped=%d',
				$this->dry_run ? 'Dry-run' : 'Done', $assigned, $skipped
			));
		}

		/**
		 * Import Businesses into CPT nmda_business
		 *
		 * ## OPTIONS
		 * [--dry-run] [--execute] [--limit=<n>] [--offset=<n>]
		 */
		public function import_businesses($args, $assoc_args) {
			$this->dry_run = ! isset($assoc_args['execute']);
			$this->ensure_idmap_table();

			$file = $this->portal_dir . 'nmda_business.sql';
			$rows = $this->parse_sql_inserts($file, 'business');
			$rows = $this->apply_limit_offset($rows, $assoc_args);

			$this->assert_cpt('nmda_business');

			$progress = \WP_CLI\Utils\make_progress_bar('Businesses', count($rows));
			$created = 0; $skipped = 0; $updated = 0;

			foreach ($rows as $row) {
				$progress->tick();
				$legacy_id = $row['BusinessId'] ?? null;
				$name      = trim($row['BusinessName'] ?? '');
				if (!$legacy_id || $name === '') { $skipped++; continue; }

				$post_id = $this->get_mapped_id($legacy_id, 'business');
				if ($post_id && get_post($post_id)) {
					// Optional: update meta
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

				// Meta mapping (trimmed set; add more as needed)
				$this->update_meta($post_id, array(
					'_legacy_business_id' => $legacy_id,
					'dba'                 => $row['DBA'] ?? '',
					'category'            => $row['Category'] ?? '',
					'website'             => $row['Website'] ?? '',
					'email'               => strtolower(trim($row['Email'] ?? '')),
					'phone'               => $row['Phone'] ?? '',
					'business_hours'      => $row['BusinessHours'] ?? '',
					'owner_first'         => $row['OwnerFirstName'] ?? '',
					'owner_last'          => $row['OwnerLastName'] ?? '',
					'contact_first'       => $row['ContactFirstName'] ?? '',
					'contact_last'        => $row['ContactLastName'] ?? '',
					'contact_phone'       => $row['ContactPhone'] ?? '',
					'contact_email'       => strtolower(trim($row['ContactEmail'] ?? '')),
					'facebook_enabled'    => $this->yn_to_bool($row['Facebook'] ?? null),
					'facebook_handle'     => $row['FacebookHandle'] ?? '',
					'instagram_enabled'   => $this->yn_to_bool($row['Instagram'] ?? null),
					'instagram_handle'    => $row['InstagramHandle'] ?? '',
					'twitter_enabled'     => $this->yn_to_bool($row['Twitter'] ?? null),
					'twitter_handle'      => $row['TwitterHandle'] ?? '',
					'has_organic'         => $this->yn_to_bool($row['HasOrganic'] ?? null),
					'has_grass_fed'       => $this->yn_to_bool($row['HasGrassFed'] ?? null),
					'has_free_range'      => $this->yn_to_bool($row['HasFreeRange'] ?? null),
					'_legacy_admin_initials' => $row['AdminInitials'] ?? '',
				));

				$this->set_mapping($legacy_id, 'business', $post_id);
				$created++;
			}

			$progress->finish();
			WP_CLI::success(sprintf('%s: created=%d, updated=%d, skipped=%d',
				$this->dry_run ? 'Dry-run' : 'Done', $created, $updated, $skipped
			));
		}

		/**
		 * Import Business Addresses into custom table wp_nmda_business_addresses
		 *
		 * ## OPTIONS
		 * [--dry-run] [--execute] [--limit=<n>] [--offset=<n>]
		 */
		public function import_addresses($args, $assoc_args) {
			global $wpdb;
			$this->dry_run = ! isset($assoc_args['execute']);
			$this->ensure_idmap_table();

			$table = $wpdb->prefix . 'nmda_business_addresses';
			// sanity
			$exists = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s",
				$table
			));
			if (!$exists) {
				WP_CLI::error("Custom table {$table} does not exist.");
			}

			$file = $this->portal_dir . 'nmda_business_address.sql';
			$rows = $this->parse_sql_inserts($file, 'business_address');
			$rows = $this->apply_limit_offset($rows, $assoc_args);

			$progress = \WP_CLI\Utils\make_progress_bar('Business Addresses', count($rows));
			$inserted = 0; $skipped = 0; $missing_parent = 0;

			foreach ($rows as $row) {
				$progress->tick();
				$legacy_bid = $row['BusinessId'] ?? null;
				if (!$legacy_bid) { $skipped++; continue; }

				$parent_post_id = $this->get_mapped_id($legacy_bid, 'business');
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
			WP_CLI::success(sprintf('%s: inserted=%d, missing_parent=%d, skipped=%d',
				$this->dry_run ? 'Dry-run' : 'Done', $inserted, $missing_parent, $skipped
			));
		}

		/**
		 * Import Users from nmda_user.sql
		 *
		 * ## OPTIONS
		 * [--dry-run] [--execute] [--limit=<n>] [--offset=<n>]
		 */
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
				$legacy_uid = $row['UserId'] ?? $row['userId'] ?? null;
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
					'role'         => 'subscriber', // or your custom role (nmda_company_contact)
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

		/* =========================
		 * Utilities / Infra
		 * ========================= */

		private function assert_cpt($cpt) {
			if (!post_type_exists($cpt)) {
				WP_CLI::error("CPT {$cpt} is not registered. Load your theme/plugin that registers it first.");
			}
		}

		private function apply_limit_offset(array $rows, array $assoc_args) {
			$limit  = isset($assoc_args['limit'])  ? (int)$assoc_args['limit']  : 0;
			$offset = isset($assoc_args['offset']) ? (int)$assoc_args['offset'] : 0;
			if ($offset > 0) {
				$rows = array_slice($rows, $offset);
			}
			if ($limit > 0) {
				$rows = array_slice($rows, 0, $limit);
			}
			return $rows;
		}

		private function update_meta($post_id, array $pairs) {
			foreach ($pairs as $k => $v) {
				update_post_meta($post_id, $k, $v);
			}
		}

		private function yn_to_bool($val) {
			$val = is_string($val) ? trim($val) : $val;
			if ($val === null || $val === '') return 0;
			return in_array(strtolower((string)$val), array('y','yes','true','1'), true) ? 1 : 0;
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

		/* ===== Id Mapping ===== */

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

		/* ===== SQL parsing =====
		   minimal INSERT INTO (...) VALUES (...),(...); parser
		*/
		private function parse_sql_inserts($file, $label = '') {
			if (!file_exists($file)) {
				WP_CLI::warning("Missing SQL file: {$file}");
				return array();
			}
			$contents = file_get_contents($file);
			if ($contents === false || $contents === '') {
				WP_CLI::warning("Empty SQL file: {$file}");
				return array();
			}
			$sql = preg_replace('/\s+/', ' ', $contents);
			$rows = array();
			$pattern = '/INSERT\s+INTO\s+`?[\w\-]+`?\s*\((.*?)\)\s*VALUES\s*(.*?);/i';
			if (preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
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
			WP_CLI::log(sprintf('Parsed %d %s rows from %s', count($rows), $label ?: 'insert', basename($file)));
			return $rows;
		}

		private function split_value_tuples($vals_raw) {
			$out = array(); $level=0; $buf=''; $len=strlen($vals_raw);
			for ($i=0; $i<$len; $i++) {
				$ch = $vals_raw[$i];
				if ($ch === '(') { if ($level===0) { $buf=''; } $level++; }
				$buf .= $ch;
				if ($ch === ')') { $level--; if ($level===0) { $out[] = $buf; $buf=''; } }
			}
			return $out;
		}

		private function split_values_respecting_quotes($tuple) {
			$tuple = trim($tuple);
			if (substr($tuple,0,1)==='(' && substr($tuple,-1)===')') {
				$tuple = substr($tuple, 1, -1);
			}
			$out=array(); $buf=''; $in=false; $q=''; $len=strlen($tuple);
			for ($i=0; $i<$len; $i++) {
				$ch = $tuple[$i];
				if ($in) {
					$buf .= $ch;
					if ($ch === '\\' && $i+1 < $len) { $buf .= $tuple[++$i]; continue; }
					if ($ch === $q) { $in = false; }
				} else {
					if ($ch === '\'' || $ch === '"') { $in=true; $q=$ch; $buf.=$ch; }
					elseif ($ch === ',') { $out[] = trim($buf); $buf=''; }
					else { $buf.=$ch; }
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
			// numeric?
			if (is_numeric($v)) {
				return (strpos($v, '.') !== false) ? (float)$v : (int)$v;
			}
			return $v;
		}
	}

	/* =========================
	 * WP-CLI command bindings
	 * ========================= */
	WP_CLI::add_command('nmda import all',            [new NMDA_Importer_CLI(), 'import_all']);
	WP_CLI::add_command('nmda import group-types',    [new NMDA_Importer_CLI(), 'import_group_types']);
	WP_CLI::add_command('nmda import companies',      [new NMDA_Importer_CLI(), 'import_companies']);
	WP_CLI::add_command('nmda import company-terms',  [new NMDA_Importer_CLI(), 'import_company_terms']);
	WP_CLI::add_command('nmda import businesses',     [new NMDA_Importer_CLI(), 'import_businesses']);
	WP_CLI::add_command('nmda import addresses',      [new NMDA_Importer_CLI(), 'import_addresses']);
	WP_CLI::add_command('nmda import users',          [new NMDA_Importer_CLI(), 'import_users']);
}
