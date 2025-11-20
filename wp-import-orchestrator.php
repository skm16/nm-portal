#!/usr/bin/env php
<?php
/**
 * WordPress SQL Import Orchestrator
 * Comprehensive WP-CLI script for importing SQL data with ACF field mapping
 * 
 * Usage: wp eval-file wp-import-orchestrator.php [--mode=audit|import|rollback] [--force]
 */

// Check if WP-CLI is running
if (!defined('WP_CLI') || !WP_CLI) {
    echo "This script must be run through WP-CLI\n";
    exit(1);
}

class NMDA_Import_Orchestrator {
    
    private $sql_dir = 'sqls-for-import';
    private $acf_dir = 'acf-json';
    private $backup_dir = 'backups';
    private $log_file;
    private $backup_file;
    private $import_stats = [];
    private $post_type_map = [
        'nmda_business' => 'Business Profiles',
        'company' => 'Companies',
        'nmda-applications' => 'Applications to become a member',
        'nmda-reimbursements' => 'Reimbursement requests'
    ];
    
    public function __construct() {
        $this->log_file = date('Y-m-d-H-i-s') . '-import.log';
        $this->backup_file = date('Y-m-d-H-i-s') . '-backup.sql';
        
        // Create necessary directories
        $this->ensure_directories();
        
        // Initialize logging
        $this->log("=== NMDA WordPress Import Orchestrator Started ===");
        $this->log("Time: " . date('Y-m-d H:i:s'));
        $this->log("User: " . wp_get_current_user()->user_login);
    }
    
    /**
     * Main execution method
     */
    public function run($mode = 'audit', $force = false) {
        WP_CLI::line(WP_CLI::colorize("%G=== NMDA WordPress Import Orchestrator ===%n"));
        
        switch($mode) {
            case 'audit':
                $this->run_audit();
                break;
                
            case 'import':
                if (!$force) {
                    WP_CLI::confirm("Are you sure you want to proceed with import? This will modify your database.");
                }
                $this->run_import();
                break;
                
            case 'rollback':
                if (!$force) {
                    WP_CLI::confirm("Are you sure you want to rollback to the latest backup?");
                }
                $this->run_rollback();
                break;
                
            default:
                WP_CLI::error("Invalid mode. Use: audit, import, or rollback");
        }
    }
    
    /**
     * Run audit mode - analyze SQL files without importing
     */
    private function run_audit() {
        WP_CLI::line(WP_CLI::colorize("%Y[AUDIT MODE]%n Analyzing SQL files..."));
        
        // Check ACF configuration
        $this->audit_acf_fields();
        
        // Check post types
        $this->audit_post_types();
        
        // Analyze SQL files
        $sql_files = $this->get_sql_files();
        
        if (empty($sql_files)) {
            WP_CLI::warning("No SQL files found in {$this->sql_dir}");
            return;
        }
        
        $total_stats = [
            'files' => count($sql_files),
            'total_records' => 0,
            'tables' => [],
            'relationships' => []
        ];
        
        foreach ($sql_files as $file) {
            WP_CLI::line("\nAnalyzing: " . basename($file));
            $stats = $this->analyze_sql_file($file);
            $total_stats['total_records'] += $stats['record_count'];
            
            foreach ($stats['tables'] as $table => $count) {
                if (!isset($total_stats['tables'][$table])) {
                    $total_stats['tables'][$table] = 0;
                }
                $total_stats['tables'][$table] += $count;
            }
            
            if (!empty($stats['relationships'])) {
                $total_stats['relationships'] = array_merge($total_stats['relationships'], $stats['relationships']);
            }
        }
        
        // Display audit summary
        $this->display_audit_summary($total_stats);
    }
    
    /**
     * Analyze a single SQL file
     */
    private function analyze_sql_file($file) {
        $content = file_get_contents($file);
        $stats = [
            'file' => basename($file),
            'size' => filesize($file),
            'record_count' => 0,
            'tables' => [],
            'relationships' => [],
            'warnings' => []
        ];
        
        // Count INSERT statements
        preg_match_all('/INSERT\s+INTO\s+`?(\w+)`?\s+/i', $content, $inserts);
        if (!empty($inserts[1])) {
            foreach ($inserts[1] as $table) {
                if (!isset($stats['tables'][$table])) {
                    $stats['tables'][$table] = 0;
                }
                $stats['tables'][$table]++;
            }
        }
        
        // Count records (rough estimate based on VALUES occurrences)
        $stats['record_count'] = substr_count($content, 'VALUES');
        
        // Detect foreign key relationships
        preg_match_all('/FOREIGN\s+KEY.*?REFERENCES\s+`?(\w+)`?\s*\(`?(\w+)`?\)/i', $content, $fks);
        if (!empty($fks[1])) {
            foreach ($fks[1] as $i => $ref_table) {
                $stats['relationships'][] = [
                    'table' => $ref_table,
                    'column' => $fks[2][$i]
                ];
            }
        }
        
        // Check for potential issues
        if (stripos($content, 'DROP TABLE') !== false) {
            $stats['warnings'][] = "Contains DROP TABLE statements";
        }
        if (stripos($content, 'TRUNCATE') !== false) {
            $stats['warnings'][] = "Contains TRUNCATE statements";
        }
        
        return $stats;
    }
    
    /**
     * Audit ACF fields configuration
     */
    private function audit_acf_fields() {
        WP_CLI::line(WP_CLI::colorize("%Y[ACF AUDIT]%n Checking ACF field groups..."));
        
        if (!is_dir($this->acf_dir)) {
            WP_CLI::warning("ACF JSON directory not found: {$this->acf_dir}");
            return;
        }
        
        $json_files = glob($this->acf_dir . '/*.json');
        $field_groups = [];
        
        foreach ($json_files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data['title'])) {
                $field_groups[] = [
                    'title' => $data['title'],
                    'key' => $data['key'],
                    'fields' => count($data['fields'] ?? []),
                    'location' => $this->parse_acf_location($data['location'] ?? [])
                ];
            }
        }
        
        if (!empty($field_groups)) {
            WP_CLI::line("Found " . count($field_groups) . " ACF field groups:");
            foreach ($field_groups as $group) {
                WP_CLI::line("  • {$group['title']} ({$group['fields']} fields) - {$group['location']}");
            }
        } else {
            WP_CLI::warning("No ACF field groups found");
        }
    }
    
    /**
     * Parse ACF location rules
     */
    private function parse_acf_location($location) {
        if (empty($location)) return 'No location rules';
        
        $rules = [];
        foreach ($location as $group) {
            foreach ($group as $rule) {
                if ($rule['param'] == 'post_type' && $rule['operator'] == '==') {
                    $rules[] = "Post Type: " . $rule['value'];
                }
            }
        }
        
        return !empty($rules) ? implode(', ', $rules) : 'Custom rules';
    }
    
    /**
     * Audit registered post types
     */
    private function audit_post_types() {
        WP_CLI::line(WP_CLI::colorize("%Y[POST TYPE AUDIT]%n Checking registered post types..."));
        
        $expected_types = array_keys($this->post_type_map);
        $registered_types = get_post_types(['public' => true], 'names');
        
        foreach ($expected_types as $type) {
            if (in_array($type, $registered_types)) {
                WP_CLI::success("✓ Post type registered: {$type} ({$this->post_type_map[$type]})");
                
                // Count existing posts
                $count = wp_count_posts($type);
                $total = isset($count->publish) ? $count->publish : 0;
                if (isset($count->draft)) $total += $count->draft;
                if (isset($count->private)) $total += $count->private;
                
                if ($total > 0) {
                    WP_CLI::line("  Existing posts: {$total}");
                }
            } else {
                WP_CLI::warning("✗ Post type NOT registered: {$type} ({$this->post_type_map[$type]})");
            }
        }
    }
    
    /**
     * Display audit summary
     */
    private function display_audit_summary($stats) {
        WP_CLI::line("\n" . WP_CLI::colorize("%G=== AUDIT SUMMARY ===%n"));
        WP_CLI::line("Total SQL files: " . $stats['files']);
        WP_CLI::line("Estimated records: " . $stats['total_records']);
        
        if (!empty($stats['tables'])) {
            WP_CLI::line("\nTables to be affected:");
            foreach ($stats['tables'] as $table => $count) {
                WP_CLI::line("  • {$table}: ~{$count} operations");
            }
        }
        
        if (!empty($stats['relationships'])) {
            WP_CLI::line("\nDetected relationships:");
            foreach ($stats['relationships'] as $rel) {
                WP_CLI::line("  • References {$rel['table']}.{$rel['column']}");
            }
        }
        
        // Recommendations
        WP_CLI::line("\n" . WP_CLI::colorize("%Y=== RECOMMENDATIONS ===%n"));
        WP_CLI::line("1. Ensure all custom post types are registered");
        WP_CLI::line("2. Verify ACF field groups are synchronized");
        WP_CLI::line("3. Create a full database backup before import");
        WP_CLI::line("4. Test import on staging environment first");
        
        $this->log("Audit completed: " . json_encode($stats));
    }
    
    /**
     * Run import mode - execute SQL imports with backup
     */
    private function run_import() {
        WP_CLI::line(WP_CLI::colorize("%Y[IMPORT MODE]%n Starting import process..."));
        
        // Step 1: Create backup
        WP_CLI::line("\n1. Creating database backup...");
        $backup_created = $this->create_backup();
        
        if (!$backup_created) {
            WP_CLI::error("Failed to create backup. Aborting import.");
            return;
        }
        
        // Step 2: Sync ACF fields
        WP_CLI::line("\n2. Synchronizing ACF fields...");
        $this->sync_acf_fields();
        
        // Step 3: Prepare import environment
        WP_CLI::line("\n3. Preparing import environment...");
        $this->prepare_import_environment();
        
        // Step 4: Execute SQL imports
        WP_CLI::line("\n4. Executing SQL imports...");
        $sql_files = $this->get_sql_files();
        $success_count = 0;
        $error_count = 0;
        
        foreach ($sql_files as $file) {
            WP_CLI::line("\nImporting: " . basename($file));
            
            if ($this->import_sql_file($file)) {
                $success_count++;
                WP_CLI::success("✓ Successfully imported: " . basename($file));
            } else {
                $error_count++;
                WP_CLI::error("✗ Failed to import: " . basename($file), false);
            }
        }
        
        // Step 5: Post-import processing
        WP_CLI::line("\n5. Running post-import processing...");
        $this->post_import_processing();
        
        // Step 6: Verify import
        WP_CLI::line("\n6. Verifying import...");
        $this->verify_import();
        
        // Display summary
        WP_CLI::line("\n" . WP_CLI::colorize("%G=== IMPORT COMPLETE ===%n"));
        WP_CLI::line("Files processed: " . ($success_count + $error_count));
        WP_CLI::line("Successful: " . $success_count);
        WP_CLI::line("Failed: " . $error_count);
        WP_CLI::line("Backup saved to: " . $this->backup_dir . '/' . $this->backup_file);
        
        $this->log("Import completed. Success: {$success_count}, Failed: {$error_count}");
    }
    
    /**
     * Create database backup
     */
    private function create_backup() {
        global $wpdb;
        
        $backup_path = $this->backup_dir . '/' . $this->backup_file;
        
        // Use WP-CLI's db export command
        $result = WP_CLI::runcommand(
            'db export ' . $backup_path,
            ['return' => 'return_code', 'exit_error' => false]
        );
        
        if ($result === 0 && file_exists($backup_path)) {
            WP_CLI::success("Backup created: {$backup_path}");
            
            // Create revert script
            $this->create_revert_script($backup_path);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Create revert script for easy rollback
     */
    private function create_revert_script($backup_path) {
        $revert_script = "#!/bin/bash\n\n";
        $revert_script .= "# NMDA WordPress Import Revert Script\n";
        $revert_script .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $revert_script .= "echo 'Starting database rollback...'\n";
        $revert_script .= "wp db import {$backup_path}\n";
        $revert_script .= "echo 'Rollback complete!'\n";
        
        $revert_file = $this->backup_dir . '/revert-' . $this->backup_file . '.sh';
        file_put_contents($revert_file, $revert_script);
        chmod($revert_file, 0755);
        
        WP_CLI::line("Revert script created: {$revert_file}");
    }
    
    /**
     * Sync ACF fields from JSON
     */
    private function sync_acf_fields() {
        if (!function_exists('acf_get_field_groups')) {
            WP_CLI::warning("ACF not detected. Skipping field sync.");
            return;
        }
        
        $json_files = glob($this->acf_dir . '/*.json');
        $synced = 0;
        
        foreach ($json_files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['key'])) {
                // Import field group
                acf_import_field_group($data);
                $synced++;
                WP_CLI::line("  Synced field group: {$data['title']}");
            }
        }
        
        if ($synced > 0) {
            WP_CLI::success("Synced {$synced} ACF field groups");
        }
    }
    
    /**
     * Prepare import environment
     */
    private function prepare_import_environment() {
        // Disable autocommit for better performance
        global $wpdb;
        $wpdb->query('SET autocommit = 0');
        
        // Increase memory limit
        ini_set('memory_limit', '512M');
        
        // Disable query logging to save memory
        $wpdb->queries = [];
        
        WP_CLI::line("  Environment prepared for import");
    }
    
    /**
     * Import a single SQL file
     */
    private function import_sql_file($file) {
        global $wpdb;
        
        try {
            $content = file_get_contents($file);
            
            // Split content into individual queries
            $queries = $this->split_sql_queries($content);
            $total = count($queries);
            $processed = 0;
            
            $progress = \WP_CLI\Utils\make_progress_bar('Processing queries', $total);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;
                
                // Process the query based on type
                if ($this->should_process_query($query)) {
                    $result = $wpdb->query($query);
                    if ($result === false) {
                        $this->log("Query error in {$file}: " . $wpdb->last_error);
                    }
                }
                
                $processed++;
                $progress->tick();
            }
            
            $progress->finish();
            
            // Commit the transaction
            $wpdb->query('COMMIT');
            
            $this->import_stats[$file] = [
                'queries' => $total,
                'processed' => $processed
            ];
            
            return true;
            
        } catch (Exception $e) {
            $this->log("Import error for {$file}: " . $e->getMessage());
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Split SQL content into individual queries
     */
    private function split_sql_queries($content) {
        // Remove comments
        $content = preg_replace('/^\s*--.*$/m', '', $content);
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        
        // Split by semicolon (simple approach - may need refinement for complex queries)
        $queries = explode(';', $content);
        
        return array_filter(array_map('trim', $queries));
    }
    
    /**
     * Check if query should be processed
     */
    private function should_process_query($query) {
        // Skip dangerous operations
        $dangerous = ['DROP DATABASE', 'CREATE DATABASE', 'USE '];
        
        foreach ($dangerous as $keyword) {
            if (stripos($query, $keyword) !== false) {
                $this->log("Skipped dangerous query: " . substr($query, 0, 50));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Post-import processing
     */
    private function post_import_processing() {
        global $wpdb;
        
        // Re-enable autocommit
        $wpdb->query('SET autocommit = 1');
        
        // Clear caches
        wp_cache_flush();
        
        // Update rewrite rules
        flush_rewrite_rules();
        
        // Trigger action for custom processing
        do_action('nmda_after_import', $this->import_stats);
        
        WP_CLI::line("  Post-import processing complete");
    }
    
    /**
     * Verify import results
     */
    private function verify_import() {
        foreach ($this->post_type_map as $post_type => $label) {
            $count = wp_count_posts($post_type);
            $total = 0;
            
            if (is_object($count)) {
                foreach ($count as $status => $num) {
                    $total += (int)$num;
                }
            }
            
            WP_CLI::line("  {$label}: {$total} posts");
        }
    }
    
    /**
     * Run rollback mode
     */
    private function run_rollback() {
        WP_CLI::line(WP_CLI::colorize("%Y[ROLLBACK MODE]%n Starting rollback..."));
        
        // Find most recent backup
        $backups = glob($this->backup_dir . '/*.sql');
        if (empty($backups)) {
            WP_CLI::error("No backups found");
            return;
        }
        
        // Sort by modification time
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latest_backup = $backups[0];
        WP_CLI::line("Using backup: " . basename($latest_backup));
        
        // Import the backup
        $result = WP_CLI::runcommand(
            'db import ' . $latest_backup,
            ['return' => 'return_code', 'exit_error' => false]
        );
        
        if ($result === 0) {
            WP_CLI::success("Database rolled back successfully");
            
            // Clear caches
            wp_cache_flush();
            flush_rewrite_rules();
            
            $this->log("Rollback completed using: " . basename($latest_backup));
        } else {
            WP_CLI::error("Rollback failed");
        }
    }
    
    /**
     * Get SQL files from import directory
     */
    private function get_sql_files() {
        if (!is_dir($this->sql_dir)) {
            WP_CLI::warning("SQL directory not found: {$this->sql_dir}");
            return [];
        }
        
        return glob($this->sql_dir . '/*.sql');
    }
    
    /**
     * Ensure necessary directories exist
     */
    private function ensure_directories() {
        $dirs = [$this->backup_dir, $this->sql_dir, $this->acf_dir];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Log messages to file
     */
    private function log($message) {
        $log_path = $this->backup_dir . '/' . $this->log_file;
        file_put_contents($log_path, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
    }
}

// Parse command line arguments
$args = WP_CLI::get_runner()->arguments;
$assoc_args = WP_CLI::get_runner()->assoc_args;

$mode = isset($assoc_args['mode']) ? $assoc_args['mode'] : 'audit';
$force = isset($assoc_args['force']);

// Run the orchestrator
$orchestrator = new NMDA_Import_Orchestrator();
$orchestrator->run($mode, $force);