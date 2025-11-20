#!/usr/bin/env php
<?php
/**
 * Data Mapping Helper for NMDA WordPress Import
 * Handles complex ACF field mappings and post relationships
 */

if (!defined('WP_CLI') || !WP_CLI) {
    echo "This script must be run through WP-CLI\n";
    exit(1);
}

class NMDA_Data_Mapper {
    
    private $field_map = [];
    private $relationship_map = [];
    private $processed_posts = [];
    
    /**
     * Map SQL data to ACF fields
     */
    public function map_to_acf($post_type, $sql_data) {
        $acf_data = [];
        
        // Get field mapping for this post type
        $mapping = $this->get_field_mapping($post_type);
        
        foreach ($mapping as $sql_field => $acf_field) {
            if (isset($sql_data[$sql_field])) {
                $value = $sql_data[$sql_field];
                
                // Handle special field types
                $value = $this->process_field_value($acf_field, $value, $sql_data);
                
                $acf_data[$acf_field] = $value;
            }
        }
        
        return $acf_data;
    }
    
    /**
     * Get field mapping configuration
     */
    private function get_field_mapping($post_type) {
        $mappings = [
            'nmda_business' => [
                'business_name' => 'field_business_name',
                'business_description' => 'field_business_description',
                'business_email' => 'field_business_email',
                'business_phone' => 'field_business_phone',
                'business_website' => 'field_business_website',
                'business_address' => 'field_business_address',
                'business_city' => 'field_business_city',
                'business_state' => 'field_business_state',
                'business_zip' => 'field_business_zip',
                'business_country' => 'field_business_country',
                'business_logo_id' => 'field_business_logo',
                'business_type' => 'field_business_type',
                'business_status' => 'field_business_status',
                'company_id' => 'field_company_relationship',
                'member_since' => 'field_member_since',
                'membership_level' => 'field_membership_level'
            ],
            'company' => [
                'company_name' => 'field_company_name',
                'company_legal_name' => 'field_company_legal_name',
                'company_ein' => 'field_company_ein',
                'company_type' => 'field_company_type',
                'company_industry' => 'field_company_industry',
                'company_founded' => 'field_company_founded',
                'company_employees' => 'field_company_employees',
                'company_revenue' => 'field_company_revenue',
                'company_description' => 'field_company_description',
                'primary_contact_name' => 'field_primary_contact_name',
                'primary_contact_email' => 'field_primary_contact_email',
                'primary_contact_phone' => 'field_primary_contact_phone',
                'company_address' => 'field_company_address',
                'company_city' => 'field_company_city',
                'company_state' => 'field_company_state',
                'company_zip' => 'field_company_zip',
                'company_country' => 'field_company_country'
            ],
            'nmda-applications' => [
                'applicant_name' => 'field_applicant_name',
                'applicant_email' => 'field_applicant_email',
                'applicant_phone' => 'field_applicant_phone',
                'application_type' => 'field_application_type',
                'application_status' => 'field_application_status',
                'application_date' => 'field_application_date',
                'review_date' => 'field_review_date',
                'reviewer_id' => 'field_reviewer',
                'review_notes' => 'field_review_notes',
                'organization_name' => 'field_organization_name',
                'organization_type' => 'field_organization_type',
                'organization_description' => 'field_organization_description',
                'rare_disease_focus' => 'field_rare_disease_focus',
                'services_provided' => 'field_services_provided',
                'target_population' => 'field_target_population',
                'geographic_reach' => 'field_geographic_reach',
                'annual_budget' => 'field_annual_budget',
                'tax_exempt_status' => 'field_tax_exempt_status',
                'supporting_documents' => 'field_supporting_documents'
            ],
            'nmda-reimbursements' => [
                'request_number' => 'field_request_number',
                'requester_name' => 'field_requester_name',
                'requester_email' => 'field_requester_email',
                'requester_phone' => 'field_requester_phone',
                'organization_id' => 'field_organization',
                'expense_type' => 'field_expense_type',
                'expense_date' => 'field_expense_date',
                'expense_amount' => 'field_expense_amount',
                'expense_currency' => 'field_expense_currency',
                'expense_description' => 'field_expense_description',
                'supporting_receipts' => 'field_supporting_receipts',
                'payment_method' => 'field_payment_method',
                'bank_account' => 'field_bank_account',
                'routing_number' => 'field_routing_number',
                'request_status' => 'field_request_status',
                'submission_date' => 'field_submission_date',
                'approval_date' => 'field_approval_date',
                'payment_date' => 'field_payment_date',
                'approver_id' => 'field_approver',
                'approval_notes' => 'field_approval_notes',
                'payment_reference' => 'field_payment_reference'
            ]
        ];
        
        return isset($mappings[$post_type]) ? $mappings[$post_type] : [];
    }
    
    /**
     * Process field value based on type
     */
    private function process_field_value($acf_field, $value, $sql_data) {
        // Handle relationship fields
        if (strpos($acf_field, '_relationship') !== false || strpos($acf_field, '_id') !== false) {
            return $this->process_relationship_field($value, $acf_field);
        }
        
        // Handle date fields
        if (strpos($acf_field, '_date') !== false || strpos($acf_field, '_since') !== false) {
            return $this->process_date_field($value);
        }
        
        // Handle file/image fields
        if (strpos($acf_field, '_logo') !== false || strpos($acf_field, '_documents') !== false || strpos($acf_field, '_receipts') !== false) {
            return $this->process_file_field($value);
        }
        
        // Handle repeater fields
        if (strpos($acf_field, 'services_provided') !== false || strpos($acf_field, 'target_population') !== false) {
            return $this->process_repeater_field($value);
        }
        
        // Handle select/checkbox fields
        if (in_array($acf_field, ['field_business_type', 'field_company_type', 'field_application_type', 'field_expense_type'])) {
            return $this->process_select_field($value);
        }
        
        // Handle number fields
        if (in_array($acf_field, ['field_company_employees', 'field_company_revenue', 'field_annual_budget', 'field_expense_amount'])) {
            return $this->process_number_field($value);
        }
        
        // Default: return as-is
        return $value;
    }
    
    /**
     * Process relationship fields
     */
    private function process_relationship_field($value, $field_name) {
        if (empty($value)) return null;
        
        // Check if we have a mapping for this ID
        if (isset($this->relationship_map[$value])) {
            return $this->relationship_map[$value];
        }
        
        // Try to find the related post
        $related_post = null;
        
        // Determine the post type based on field name
        if (strpos($field_name, 'company') !== false) {
            $related_post = $this->find_post_by_import_id('company', $value);
        } elseif (strpos($field_name, 'organization') !== false) {
            $related_post = $this->find_post_by_import_id('nmda_business', $value);
        } elseif (strpos($field_name, 'reviewer') !== false || strpos($field_name, 'approver') !== false) {
            // Handle user relationships
            $related_post = $this->find_user_by_import_id($value);
        }
        
        if ($related_post) {
            $this->relationship_map[$value] = $related_post;
            return $related_post;
        }
        
        return null;
    }
    
    /**
     * Find post by import ID
     */
    private function find_post_by_import_id($post_type, $import_id) {
        $args = [
            'post_type' => $post_type,
            'meta_key' => '_import_id',
            'meta_value' => $import_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ];
        
        $posts = get_posts($args);
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Find user by import ID
     */
    private function find_user_by_import_id($import_id) {
        $users = get_users([
            'meta_key' => '_import_id',
            'meta_value' => $import_id,
            'number' => 1,
            'fields' => 'ID'
        ]);
        
        return !empty($users) ? $users[0] : null;
    }
    
    /**
     * Process date fields
     */
    private function process_date_field($value) {
        if (empty($value)) return '';
        
        // Convert various date formats to ACF format (Ymd or Y-m-d H:i:s)
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            // Check if it includes time
            if (preg_match('/\d{2}:\d{2}/', $value)) {
                return date('Y-m-d H:i:s', $timestamp);
            } else {
                return date('Ymd', $timestamp);
            }
        }
        
        return $value;
    }
    
    /**
     * Process file/image fields
     */
    private function process_file_field($value) {
        if (empty($value)) return null;
        
        // Handle comma-separated file IDs
        if (strpos($value, ',') !== false) {
            $file_ids = explode(',', $value);
            $attachment_ids = [];
            
            foreach ($file_ids as $file_id) {
                $attachment_id = $this->process_single_file(trim($file_id));
                if ($attachment_id) {
                    $attachment_ids[] = $attachment_id;
                }
            }
            
            return $attachment_ids;
        }
        
        // Single file
        return $this->process_single_file($value);
    }
    
    /**
     * Process single file reference
     */
    private function process_single_file($file_reference) {
        // If it's already a WordPress attachment ID
        if (is_numeric($file_reference)) {
            $attachment = get_post($file_reference);
            if ($attachment && $attachment->post_type === 'attachment') {
                return (int)$file_reference;
            }
        }
        
        // Try to find attachment by import ID
        $attachments = get_posts([
            'post_type' => 'attachment',
            'meta_key' => '_import_file_id',
            'meta_value' => $file_reference,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        
        return !empty($attachments) ? $attachments[0] : null;
    }
    
    /**
     * Process repeater fields
     */
    private function process_repeater_field($value) {
        if (empty($value)) return [];
        
        // Handle JSON data
        $json_data = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json_data;
        }
        
        // Handle comma-separated values
        if (strpos($value, ',') !== false) {
            $items = explode(',', $value);
            $repeater_data = [];
            
            foreach ($items as $item) {
                $repeater_data[] = [
                    'value' => trim($item)
                ];
            }
            
            return $repeater_data;
        }
        
        // Single value
        return [['value' => $value]];
    }
    
    /**
     * Process select/checkbox fields
     */
    private function process_select_field($value) {
        if (empty($value)) return '';
        
        // Handle multiple values (checkbox)
        if (strpos($value, ',') !== false) {
            return array_map('trim', explode(',', $value));
        }
        
        // Map common variations to standard values
        $mappings = [
            'non-profit' => 'nonprofit',
            'non profit' => 'nonprofit',
            'for-profit' => 'forprofit',
            'for profit' => 'forprofit',
            'llc' => 'LLC',
            'inc' => 'Inc',
            'corp' => 'Corporation'
        ];
        
        $lower_value = strtolower($value);
        if (isset($mappings[$lower_value])) {
            return $mappings[$lower_value];
        }
        
        return $value;
    }
    
    /**
     * Process number fields
     */
    private function process_number_field($value) {
        if (empty($value)) return 0;
        
        // Remove currency symbols and formatting
        $value = preg_replace('/[^0-9.-]/', '', $value);
        
        // Convert to appropriate type
        if (strpos($value, '.') !== false) {
            return (float)$value;
        }
        
        return (int)$value;
    }
    
    /**
     * Create or update post with mapped data
     */
    public function import_post($post_type, $sql_data) {
        // Check if post already exists (by import ID)
        $existing_post_id = null;
        if (isset($sql_data['id'])) {
            $existing_post_id = $this->find_post_by_import_id($post_type, $sql_data['id']);
        }
        
        // Prepare post data
        $post_data = [
            'post_type' => $post_type,
            'post_status' => $this->determine_post_status($sql_data),
            'post_title' => $this->determine_post_title($post_type, $sql_data),
            'post_content' => $this->determine_post_content($post_type, $sql_data),
        ];
        
        if ($existing_post_id) {
            $post_data['ID'] = $existing_post_id;
            $post_id = wp_update_post($post_data);
            WP_CLI::log("Updated existing post: {$post_id}");
        } else {
            $post_id = wp_insert_post($post_data);
            WP_CLI::log("Created new post: {$post_id}");
        }
        
        if (!is_wp_error($post_id)) {
            // Store import ID for future reference
            if (isset($sql_data['id'])) {
                update_post_meta($post_id, '_import_id', $sql_data['id']);
            }
            
            // Map and save ACF fields
            $acf_data = $this->map_to_acf($post_type, $sql_data);
            foreach ($acf_data as $field_key => $field_value) {
                update_field($field_key, $field_value, $post_id);
            }
            
            // Handle taxonomies
            $this->process_taxonomies($post_id, $post_type, $sql_data);
            
            // Track processed post
            $this->processed_posts[] = $post_id;
            
            return $post_id;
        } else {
            WP_CLI::error("Failed to import post: " . $post_id->get_error_message(), false);
            return false;
        }
    }
    
    /**
     * Determine post status
     */
    private function determine_post_status($sql_data) {
        if (isset($sql_data['status'])) {
            $status_map = [
                'active' => 'publish',
                'inactive' => 'draft',
                'pending' => 'pending',
                'archived' => 'private',
                'approved' => 'publish',
                'rejected' => 'trash'
            ];
            
            $status = strtolower($sql_data['status']);
            return isset($status_map[$status]) ? $status_map[$status] : 'draft';
        }
        
        return 'publish';
    }
    
    /**
     * Determine post title
     */
    private function determine_post_title($post_type, $sql_data) {
        $title_fields = [
            'nmda_business' => 'business_name',
            'company' => 'company_name',
            'nmda-applications' => 'applicant_name',
            'nmda-reimbursements' => 'request_number'
        ];
        
        if (isset($title_fields[$post_type]) && isset($sql_data[$title_fields[$post_type]])) {
            return $sql_data[$title_fields[$post_type]];
        }
        
        return 'Imported ' . date('Y-m-d H:i:s');
    }
    
    /**
     * Determine post content
     */
    private function determine_post_content($post_type, $sql_data) {
        $content_fields = [
            'nmda_business' => 'business_description',
            'company' => 'company_description',
            'nmda-applications' => 'organization_description',
            'nmda-reimbursements' => 'expense_description'
        ];
        
        if (isset($content_fields[$post_type]) && isset($sql_data[$content_fields[$post_type]])) {
            return $sql_data[$content_fields[$post_type]];
        }
        
        return '';
    }
    
    /**
     * Process taxonomies
     */
    private function process_taxonomies($post_id, $post_type, $sql_data) {
        // Define taxonomy mappings
        $taxonomy_map = [
            'nmda_business' => [
                'business_category' => 'business_category',
                'business_tags' => 'business_tags',
                'membership_level' => 'membership_level'
            ],
            'company' => [
                'industry' => 'company_industry',
                'company_type' => 'company_type'
            ],
            'nmda-applications' => [
                'application_type' => 'application_type',
                'disease_focus' => 'rare_disease'
            ],
            'nmda-reimbursements' => [
                'expense_category' => 'expense_type'
            ]
        ];
        
        if (!isset($taxonomy_map[$post_type])) {
            return;
        }
        
        foreach ($taxonomy_map[$post_type] as $sql_field => $taxonomy) {
            if (isset($sql_data[$sql_field]) && !empty($sql_data[$sql_field])) {
                $terms = $sql_data[$sql_field];
                
                // Handle comma-separated terms
                if (strpos($terms, ',') !== false) {
                    $terms = array_map('trim', explode(',', $terms));
                } else {
                    $terms = [trim($terms)];
                }
                
                wp_set_object_terms($post_id, $terms, $taxonomy);
            }
        }
    }
    
    /**
     * Run post-import relationship fixes
     */
    public function fix_relationships() {
        WP_CLI::log("Fixing post relationships...");
        
        // Re-process all relationship fields now that all posts are imported
        foreach ($this->processed_posts as $post_id) {
            $post_type = get_post_type($post_id);
            
            // Get all ACF fields for this post
            $fields = get_field_objects($post_id);
            
            if ($fields) {
                foreach ($fields as $field) {
                    if ($field['type'] == 'relationship' || $field['type'] == 'post_object') {
                        // Re-process the relationship
                        $value = get_post_meta($post_id, '_import_' . $field['name'], true);
                        if ($value) {
                            $related_id = $this->process_relationship_field($value, $field['key']);
                            if ($related_id) {
                                update_field($field['key'], $related_id, $post_id);
                            }
                        }
                    }
                }
            }
        }
        
        WP_CLI::success("Fixed " . count($this->processed_posts) . " post relationships");
    }
    
    /**
     * Get import statistics
     */
    public function get_stats() {
        return [
            'processed_posts' => count($this->processed_posts),
            'relationships_mapped' => count($this->relationship_map)
        ];
    }
}

// Make the class available globally for use in other scripts
if (!class_exists('NMDA_Data_Mapper')) {
    global $nmda_data_mapper;
    $nmda_data_mapper = new NMDA_Data_Mapper();
}