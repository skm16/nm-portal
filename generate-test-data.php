#!/usr/bin/env php
<?php
/**
 * NMDA Test Data Generator
 * Creates sample SQL files for testing the import process
 */

if (!defined('WP_CLI') || !WP_CLI) {
    echo "This script must be run through WP-CLI\n";
    exit(1);
}

class NMDA_Test_Data_Generator {
    
    private $output_dir = 'sqls-for-import';
    private $sample_data = [];
    
    public function __construct() {
        if (!is_dir($this->output_dir)) {
            mkdir($this->output_dir, 0755, true);
        }
    }
    
    /**
     * Generate all test data
     */
    public function generate() {
        WP_CLI::line("Generating test data...");
        
        $this->generate_companies();
        $this->generate_businesses();
        $this->generate_applications();
        $this->generate_reimbursements();
        
        WP_CLI::success("Test data generated in {$this->output_dir}/");
    }
    
    /**
     * Generate company data
     */
    private function generate_companies() {
        $sql = "-- NMDA Companies Test Data\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "CREATE TABLE IF NOT EXISTS `nmda_companies_import` (\n";
        $sql .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        $sql .= "  `company_name` varchar(255) NOT NULL,\n";
        $sql .= "  `company_legal_name` varchar(255),\n";
        $sql .= "  `company_ein` varchar(20),\n";
        $sql .= "  `company_type` varchar(50),\n";
        $sql .= "  `company_industry` varchar(100),\n";
        $sql .= "  `company_founded` date,\n";
        $sql .= "  `company_employees` int(11),\n";
        $sql .= "  `company_revenue` decimal(15,2),\n";
        $sql .= "  `company_description` text,\n";
        $sql .= "  `primary_contact_name` varchar(255),\n";
        $sql .= "  `primary_contact_email` varchar(255),\n";
        $sql .= "  `primary_contact_phone` varchar(20),\n";
        $sql .= "  `company_address` varchar(255),\n";
        $sql .= "  `company_city` varchar(100),\n";
        $sql .= "  `company_state` varchar(2),\n";
        $sql .= "  `company_zip` varchar(10),\n";
        $sql .= "  `company_country` varchar(2) DEFAULT 'US',\n";
        $sql .= "  `status` varchar(20) DEFAULT 'active',\n";
        $sql .= "  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "  PRIMARY KEY (`id`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
        
        $companies = [
            [
                'RareDisease Foundation',
                'RareDisease Foundation Inc',
                '12-3456789',
                'nonprofit',
                'Healthcare',
                '2010-01-15',
                25,
                1500000.00,
                'Leading foundation supporting rare disease research and patient advocacy',
                'John Smith',
                'john@raredisease.org',
                '555-0101',
                '123 Medical Center Dr',
                'Boston',
                'MA',
                '02108'
            ],
            [
                'Hope Therapeutics',
                'Hope Therapeutics LLC',
                '98-7654321',
                'forprofit',
                'Biotechnology',
                '2015-06-20',
                150,
                25000000.00,
                'Developing innovative therapies for rare genetic disorders',
                'Sarah Johnson',
                'sarah@hopetherapeutics.com',
                '555-0102',
                '456 Biotech Way',
                'San Francisco',
                'CA',
                '94107'
            ],
            [
                'Patient Advocates United',
                'Patient Advocates United',
                '45-6789012',
                'nonprofit',
                'Patient Advocacy',
                '2008-03-10',
                10,
                500000.00,
                'Connecting rare disease patients with resources and support',
                'Maria Garcia',
                'maria@patientadvocates.org',
                '555-0103',
                '789 Community Blvd',
                'Chicago',
                'IL',
                '60601'
            ]
        ];
        
        $sql .= "INSERT INTO `nmda_companies_import` ";
        $sql .= "(`company_name`, `company_legal_name`, `company_ein`, `company_type`, `company_industry`, ";
        $sql .= "`company_founded`, `company_employees`, `company_revenue`, `company_description`, ";
        $sql .= "`primary_contact_name`, `primary_contact_email`, `primary_contact_phone`, ";
        $sql .= "`company_address`, `company_city`, `company_state`, `company_zip`) VALUES\n";
        
        $values = [];
        foreach ($companies as $company) {
            $values[] = "('" . implode("', '", array_map([$this, 'escape_string'], $company)) . "')";
        }
        
        $sql .= implode(",\n", $values) . ";\n";
        
        file_put_contents($this->output_dir . '/01-companies.sql', $sql);
        WP_CLI::line("  Generated: 01-companies.sql");
    }
    
    /**
     * Generate business profile data
     */
    private function generate_businesses() {
        $sql = "-- NMDA Business Profiles Test Data\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "CREATE TABLE IF NOT EXISTS `nmda_businesses_import` (\n";
        $sql .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        $sql .= "  `business_name` varchar(255) NOT NULL,\n";
        $sql .= "  `business_description` text,\n";
        $sql .= "  `business_email` varchar(255),\n";
        $sql .= "  `business_phone` varchar(20),\n";
        $sql .= "  `business_website` varchar(255),\n";
        $sql .= "  `business_address` varchar(255),\n";
        $sql .= "  `business_city` varchar(100),\n";
        $sql .= "  `business_state` varchar(2),\n";
        $sql .= "  `business_zip` varchar(10),\n";
        $sql .= "  `business_country` varchar(2) DEFAULT 'US',\n";
        $sql .= "  `business_type` varchar(50),\n";
        $sql .= "  `business_status` varchar(20) DEFAULT 'active',\n";
        $sql .= "  `company_id` int(11),\n";
        $sql .= "  `member_since` date,\n";
        $sql .= "  `membership_level` varchar(50),\n";
        $sql .= "  `business_category` varchar(255),\n";
        $sql .= "  `business_tags` varchar(500),\n";
        $sql .= "  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "  PRIMARY KEY (`id`),\n";
        $sql .= "  KEY `company_id` (`company_id`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
        
        $businesses = [
            [
                'RareDisease Research Center',
                'Premier research facility dedicated to understanding and treating rare diseases',
                'research@raredisease.org',
                '555-0201',
                'https://research.raredisease.org',
                '123 Medical Center Dr',
                'Boston',
                'MA',
                '02108',
                'research_center',
                'active',
                1,
                '2010-02-01',
                'platinum',
                'Research',
                'rare diseases, research, clinical trials, genetics'
            ],
            [
                'Hope Clinical Services',
                'Specialized clinical services for rare disease patients',
                'clinic@hopetherapeutics.com',
                '555-0202',
                'https://clinic.hopetherapeutics.com',
                '456 Biotech Way',
                'San Francisco',
                'CA',
                '94107',
                'clinical_services',
                'active',
                2,
                '2015-07-01',
                'gold',
                'Clinical Services',
                'treatment, therapy, patient care, clinical'
            ],
            [
                'Patient Support Network',
                'Comprehensive support services for patients and families',
                'support@patientadvocates.org',
                '555-0203',
                'https://support.patientadvocates.org',
                '789 Community Blvd',
                'Chicago',
                'IL',
                '60601',
                'support_services',
                'active',
                3,
                '2008-04-15',
                'silver',
                'Support Services',
                'patient support, advocacy, resources, community'
            ]
        ];
        
        $sql .= "INSERT INTO `nmda_businesses_import` ";
        $sql .= "(`business_name`, `business_description`, `business_email`, `business_phone`, ";
        $sql .= "`business_website`, `business_address`, `business_city`, `business_state`, ";
        $sql .= "`business_zip`, `business_type`, `business_status`, `company_id`, ";
        $sql .= "`member_since`, `membership_level`, `business_category`, `business_tags`) VALUES\n";
        
        $values = [];
        foreach ($businesses as $business) {
            $values[] = "('" . implode("', '", array_map([$this, 'escape_string'], $business)) . "')";
        }
        
        $sql .= implode(",\n", $values) . ";\n";
        
        file_put_contents($this->output_dir . '/02-businesses.sql', $sql);
        WP_CLI::line("  Generated: 02-businesses.sql");
    }
    
    /**
     * Generate application data
     */
    private function generate_applications() {
        $sql = "-- NMDA Membership Applications Test Data\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "CREATE TABLE IF NOT EXISTS `nmda_applications_import` (\n";
        $sql .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        $sql .= "  `applicant_name` varchar(255) NOT NULL,\n";
        $sql .= "  `applicant_email` varchar(255),\n";
        $sql .= "  `applicant_phone` varchar(20),\n";
        $sql .= "  `application_type` varchar(50),\n";
        $sql .= "  `application_status` varchar(20),\n";
        $sql .= "  `application_date` date,\n";
        $sql .= "  `review_date` date,\n";
        $sql .= "  `reviewer_id` int(11),\n";
        $sql .= "  `review_notes` text,\n";
        $sql .= "  `organization_name` varchar(255),\n";
        $sql .= "  `organization_type` varchar(50),\n";
        $sql .= "  `organization_description` text,\n";
        $sql .= "  `rare_disease_focus` varchar(500),\n";
        $sql .= "  `services_provided` text,\n";
        $sql .= "  `target_population` varchar(500),\n";
        $sql .= "  `geographic_reach` varchar(100),\n";
        $sql .= "  `annual_budget` decimal(12,2),\n";
        $sql .= "  `tax_exempt_status` varchar(20),\n";
        $sql .= "  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "  PRIMARY KEY (`id`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
        
        $applications = [
            [
                'Dr. Emily Chen',
                'emily.chen@geneticslab.org',
                '555-0301',
                'organization',
                'approved',
                '2024-01-15',
                '2024-01-22',
                1,
                'Strong application with clear focus on rare genetic disorders',
                'Genetics Research Lab',
                'research_institution',
                'Academic research laboratory specializing in rare genetic conditions',
                'Mitochondrial diseases, Lysosomal storage disorders',
                'Research, Genetic testing, Clinical consultation',
                'Pediatric and adult patients',
                'National',
                2500000.00,
                '501c3'
            ],
            [
                'Michael Thompson',
                'michael@caregiversupport.org',
                '555-0302',
                'individual',
                'pending',
                '2024-02-10',
                null,
                null,
                null,
                'Caregiver Support Alliance',
                'nonprofit',
                'Support organization for caregivers of rare disease patients',
                'Multiple rare diseases',
                'Support groups, Educational resources, Respite care coordination',
                'Caregivers and families',
                'Regional',
                750000.00,
                '501c3'
            ]
        ];
        
        $sql .= "INSERT INTO `nmda_applications_import` ";
        $sql .= "(`applicant_name`, `applicant_email`, `applicant_phone`, `application_type`, ";
        $sql .= "`application_status`, `application_date`, `review_date`, `reviewer_id`, ";
        $sql .= "`review_notes`, `organization_name`, `organization_type`, `organization_description`, ";
        $sql .= "`rare_disease_focus`, `services_provided`, `target_population`, ";
        $sql .= "`geographic_reach`, `annual_budget`, `tax_exempt_status`) VALUES\n";
        
        $values = [];
        foreach ($applications as $app) {
            $formatted = array_map(function($value) {
                if ($value === null) return 'NULL';
                return "'" . $this->escape_string($value) . "'";
            }, $app);
            $values[] = "(" . implode(", ", $formatted) . ")";
        }
        
        $sql .= implode(",\n", $values) . ";\n";
        
        file_put_contents($this->output_dir . '/03-applications.sql', $sql);
        WP_CLI::line("  Generated: 03-applications.sql");
    }
    
    /**
     * Generate reimbursement data
     */
    private function generate_reimbursements() {
        $sql = "-- NMDA Reimbursement Requests Test Data\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "CREATE TABLE IF NOT EXISTS `nmda_reimbursements_import` (\n";
        $sql .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        $sql .= "  `request_number` varchar(50) NOT NULL,\n";
        $sql .= "  `requester_name` varchar(255),\n";
        $sql .= "  `requester_email` varchar(255),\n";
        $sql .= "  `requester_phone` varchar(20),\n";
        $sql .= "  `organization_id` int(11),\n";
        $sql .= "  `expense_type` varchar(50),\n";
        $sql .= "  `expense_date` date,\n";
        $sql .= "  `expense_amount` decimal(10,2),\n";
        $sql .= "  `expense_currency` varchar(3) DEFAULT 'USD',\n";
        $sql .= "  `expense_description` text,\n";
        $sql .= "  `payment_method` varchar(50),\n";
        $sql .= "  `request_status` varchar(20),\n";
        $sql .= "  `submission_date` date,\n";
        $sql .= "  `approval_date` date,\n";
        $sql .= "  `payment_date` date,\n";
        $sql .= "  `approver_id` int(11),\n";
        $sql .= "  `approval_notes` text,\n";
        $sql .= "  `payment_reference` varchar(100),\n";
        $sql .= "  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "  PRIMARY KEY (`id`),\n";
        $sql .= "  UNIQUE KEY `request_number` (`request_number`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
        
        $reimbursements = [
            [
                'REIMB-2024-001',
                'John Smith',
                'john@raredisease.org',
                '555-0101',
                1,
                'conference',
                '2024-01-05',
                2500.00,
                'USD',
                'Registration and travel for Rare Disease Summit 2024',
                'bank_transfer',
                'approved',
                '2024-01-10',
                '2024-01-12',
                '2024-01-15',
                1,
                'Approved for conference attendance',
                'TXN-2024-0001'
            ],
            [
                'REIMB-2024-002',
                'Sarah Johnson',
                'sarah@hopetherapeutics.com',
                '555-0102',
                2,
                'equipment',
                '2024-01-20',
                5750.00,
                'USD',
                'Laboratory equipment for rare disease research',
                'check',
                'pending',
                '2024-01-25',
                null,
                null,
                null,
                null,
                null
            ]
        ];
        
        $sql .= "INSERT INTO `nmda_reimbursements_import` ";
        $sql .= "(`request_number`, `requester_name`, `requester_email`, `requester_phone`, ";
        $sql .= "`organization_id`, `expense_type`, `expense_date`, `expense_amount`, ";
        $sql .= "`expense_currency`, `expense_description`, `payment_method`, `request_status`, ";
        $sql .= "`submission_date`, `approval_date`, `payment_date`, `approver_id`, ";
        $sql .= "`approval_notes`, `payment_reference`) VALUES\n";
        
        $values = [];
        foreach ($reimbursements as $reimb) {
            $formatted = array_map(function($value) {
                if ($value === null) return 'NULL';
                return "'" . $this->escape_string($value) . "'";
            }, $reimb);
            $values[] = "(" . implode(", ", $formatted) . ")";
        }
        
        $sql .= implode(",\n", $values) . ";\n";
        
        file_put_contents($this->output_dir . '/04-reimbursements.sql', $sql);
        WP_CLI::line("  Generated: 04-reimbursements.sql");
    }
    
    /**
     * Escape string for SQL
     */
    private function escape_string($string) {
        if (is_numeric($string)) {
            return $string;
        }
        return str_replace("'", "''", $string);
    }
}

// Run the generator
$generator = new NMDA_Test_Data_Generator();
$generator->generate();