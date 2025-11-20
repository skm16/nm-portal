#!/bin/bash

################################################################################
# NMDA WordPress Import Manager
# Convenient wrapper for WP-CLI import operations
################################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
IMPORT_SCRIPT="${SCRIPT_DIR}/wp-import-orchestrator.php"
MAPPER_SCRIPT="${SCRIPT_DIR}/nmda-data-mapper.php"
SQL_DIR="${SCRIPT_DIR}/sqls-for-import"
ACF_DIR="${SCRIPT_DIR}/acf-json"
BACKUP_DIR="${SCRIPT_DIR}/backups"

# Function to display header
show_header() {
    echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║        NMDA WordPress Import Manager for Non-Profits        ║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo
}

# Function to check prerequisites
check_prerequisites() {
    echo -e "${YELLOW}Checking prerequisites...${NC}"
    
    # Check if WP-CLI is installed
    if ! command -v wp &> /dev/null; then
        echo -e "${RED}✗ WP-CLI is not installed${NC}"
        echo "Please install WP-CLI: https://wp-cli.org/#installing"
        exit 1
    fi
    
    # Check if we're in a WordPress directory
    if ! wp core is-installed &> /dev/null; then
        echo -e "${RED}✗ Not in a WordPress installation directory${NC}"
        echo "Please run this script from your WordPress root directory"
        exit 1
    fi
    
    # Check if required directories exist
    if [ ! -d "$SQL_DIR" ]; then
        echo -e "${YELLOW}Creating SQL import directory...${NC}"
        mkdir -p "$SQL_DIR"
    fi
    
    if [ ! -d "$ACF_DIR" ]; then
        echo -e "${YELLOW}Creating ACF JSON directory...${NC}"
        mkdir -p "$ACF_DIR"
    fi
    
    if [ ! -d "$BACKUP_DIR" ]; then
        echo -e "${YELLOW}Creating backup directory...${NC}"
        mkdir -p "$BACKUP_DIR"
    fi
    
    echo -e "${GREEN}✓ All prerequisites met${NC}"
    echo
}

# Function to display menu
show_menu() {
    echo -e "${YELLOW}Select an operation:${NC}"
    echo
    echo "  1) Audit - Analyze SQL files and check configuration"
    echo "  2) Import - Execute full import with backup"
    echo "  3) Rollback - Restore from latest backup"
    echo "  4) Quick Test - Run audit and dry-run import"
    echo "  5) List Backups - Show available backups"
    echo "  6) Clean Logs - Remove old log files"
    echo "  7) Validate ACF - Check ACF field configuration"
    echo "  8) Export Current - Export current database"
    echo "  9) Help - Show detailed help"
    echo "  0) Exit"
    echo
    read -p "Enter your choice [0-9]: " choice
    echo
}

# Function to run audit
run_audit() {
    echo -e "${YELLOW}Running audit...${NC}"
    wp eval-file "$IMPORT_SCRIPT" --mode=audit
}

# Function to run import
run_import() {
    echo -e "${YELLOW}Starting import process...${NC}"
    
    # Check if there are SQL files to import
    if [ ! "$(ls -A $SQL_DIR/*.sql 2>/dev/null)" ]; then
        echo -e "${RED}No SQL files found in $SQL_DIR${NC}"
        echo "Please place your SQL files in the sqls-for-import directory"
        return 1
    fi
    
    # Confirm with user
    echo -e "${RED}WARNING: This will modify your database!${NC}"
    read -p "Are you sure you want to continue? (yes/no): " confirm
    
    if [ "$confirm" == "yes" ]; then
        wp eval-file "$IMPORT_SCRIPT" --mode=import --force
    else
        echo "Import cancelled"
    fi
}

# Function to run rollback
run_rollback() {
    echo -e "${YELLOW}Available backups:${NC}"
    ls -lh "$BACKUP_DIR"/*.sql 2>/dev/null | tail -5
    echo
    
    echo -e "${RED}WARNING: This will restore your database from backup!${NC}"
    read -p "Are you sure you want to rollback? (yes/no): " confirm
    
    if [ "$confirm" == "yes" ]; then
        wp eval-file "$IMPORT_SCRIPT" --mode=rollback --force
    else
        echo "Rollback cancelled"
    fi
}

# Function to run quick test
run_quick_test() {
    echo -e "${YELLOW}Running quick test...${NC}"
    
    # First run audit
    echo -e "\n${BLUE}Step 1: Audit${NC}"
    wp eval-file "$IMPORT_SCRIPT" --mode=audit
    
    # Create test backup
    echo -e "\n${BLUE}Step 2: Creating test backup${NC}"
    TEST_BACKUP="${BACKUP_DIR}/test-$(date +%Y%m%d-%H%M%S).sql"
    wp db export "$TEST_BACKUP"
    echo -e "${GREEN}Test backup created: $TEST_BACKUP${NC}"
    
    # Ask if user wants to proceed with test import
    echo
    read -p "Proceed with test import? (yes/no): " proceed
    
    if [ "$proceed" == "yes" ]; then
        echo -e "\n${BLUE}Step 3: Test Import${NC}"
        wp eval-file "$IMPORT_SCRIPT" --mode=import --force
        
        # Offer to rollback
        echo
        read -p "Rollback test import? (yes/no): " rollback
        
        if [ "$rollback" == "yes" ]; then
            echo -e "\n${BLUE}Step 4: Rolling back${NC}"
            wp db import "$TEST_BACKUP"
            echo -e "${GREEN}Rollback complete${NC}"
        fi
    fi
}

# Function to list backups
list_backups() {
    echo -e "${YELLOW}Available backups:${NC}"
    echo
    
    if [ -d "$BACKUP_DIR" ] && [ "$(ls -A $BACKUP_DIR/*.sql 2>/dev/null)" ]; then
        ls -lh "$BACKUP_DIR"/*.sql | awk '{print $9, $5, $6, $7, $8}'
        
        # Show total size
        echo
        total_size=$(du -sh "$BACKUP_DIR" | cut -f1)
        echo -e "${BLUE}Total backup size: $total_size${NC}"
    else
        echo "No backups found"
    fi
}

# Function to clean logs
clean_logs() {
    echo -e "${YELLOW}Cleaning old log files...${NC}"
    
    # Find log files older than 7 days
    old_logs=$(find "$BACKUP_DIR" -name "*.log" -mtime +7 2>/dev/null)
    
    if [ -n "$old_logs" ]; then
        echo "Found old log files:"
        echo "$old_logs"
        echo
        read -p "Delete these files? (yes/no): " confirm
        
        if [ "$confirm" == "yes" ]; then
            find "$BACKUP_DIR" -name "*.log" -mtime +7 -delete
            echo -e "${GREEN}Old logs deleted${NC}"
        fi
    else
        echo "No old log files found"
    fi
}

# Function to validate ACF
validate_acf() {
    echo -e "${YELLOW}Validating ACF configuration...${NC}"
    
    wp eval '
    if (function_exists("acf_get_field_groups")) {
        $groups = acf_get_field_groups();
        echo "Found " . count($groups) . " ACF field groups:\n\n";
        
        foreach ($groups as $group) {
            echo "• " . $group["title"] . " (Key: " . $group["key"] . ")\n";
            
            $fields = acf_get_fields($group["key"]);
            if ($fields) {
                echo "  Fields: " . count($fields) . "\n";
                foreach ($fields as $field) {
                    echo "    - " . $field["label"] . " (" . $field["type"] . ")\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "ACF not installed or activated\n";
    }
    '
}

# Function to export current database
export_current() {
    echo -e "${YELLOW}Exporting current database...${NC}"
    
    EXPORT_FILE="${BACKUP_DIR}/manual-export-$(date +%Y%m%d-%H%M%S).sql"
    
    wp db export "$EXPORT_FILE"
    
    if [ -f "$EXPORT_FILE" ]; then
        size=$(ls -lh "$EXPORT_FILE" | awk '{print $5}')
        echo -e "${GREEN}✓ Database exported successfully${NC}"
        echo "File: $EXPORT_FILE"
        echo "Size: $size"
    else
        echo -e "${RED}✗ Export failed${NC}"
    fi
}

# Function to show help
show_help() {
    echo -e "${YELLOW}NMDA WordPress Import Manager - Help${NC}"
    echo
    echo "This tool helps you import SQL data into WordPress with proper ACF field mapping."
    echo
    echo -e "${BLUE}Directory Structure:${NC}"
    echo "  sqls-for-import/  - Place your SQL files here"
    echo "  acf-json/         - ACF field group JSON files"
    echo "  backups/          - Database backups and logs"
    echo
    echo -e "${BLUE}Post Types Supported:${NC}"
    echo "  • nmda_business       - Business Profiles"
    echo "  • company            - Companies"
    echo "  • nmda-applications  - Membership Applications"
    echo "  • nmda-reimbursements - Reimbursement Requests"
    echo
    echo -e "${BLUE}Workflow:${NC}"
    echo "  1. Place SQL files in sqls-for-import/"
    echo "  2. Ensure ACF field groups are in acf-json/"
    echo "  3. Run Audit to verify configuration"
    echo "  4. Run Import to execute the import"
    echo "  5. Use Rollback if needed to restore"
    echo
    echo -e "${BLUE}Command Line Usage:${NC}"
    echo "  Direct WP-CLI: wp eval-file wp-import-orchestrator.php --mode=[audit|import|rollback]"
    echo "  Using wrapper: ./wp-import-manager.sh"
    echo
    echo -e "${BLUE}Tips:${NC}"
    echo "  • Always run audit before import"
    echo "  • Test on staging/dev environment first"
    echo "  • Keep backups for at least 30 days"
    echo "  • Monitor log files for detailed information"
}

# Function to display summary
show_summary() {
    echo
    echo -e "${BLUE}Current Status:${NC}"
    
    # Count SQL files
    sql_count=$(ls -1 "$SQL_DIR"/*.sql 2>/dev/null | wc -l)
    echo "  SQL files ready: $sql_count"
    
    # Count ACF JSON files
    acf_count=$(ls -1 "$ACF_DIR"/*.json 2>/dev/null | wc -l)
    echo "  ACF field groups: $acf_count"
    
    # Count backups
    backup_count=$(ls -1 "$BACKUP_DIR"/*.sql 2>/dev/null | wc -l)
    echo "  Backups available: $backup_count"
    
    # Check last import log
    latest_log=$(ls -t "$BACKUP_DIR"/*.log 2>/dev/null | head -1)
    if [ -n "$latest_log" ]; then
        echo "  Last import: $(basename $latest_log)"
    fi
    
    echo
}

# Main execution
main() {
    clear
    show_header
    check_prerequisites
    
    while true; do
        show_summary
        show_menu
        
        case $choice in
            1) run_audit ;;
            2) run_import ;;
            3) run_rollback ;;
            4) run_quick_test ;;
            5) list_backups ;;
            6) clean_logs ;;
            7) validate_acf ;;
            8) export_current ;;
            9) show_help ;;
            0) echo -e "${GREEN}Goodbye!${NC}"; exit 0 ;;
            *) echo -e "${RED}Invalid option${NC}" ;;
        esac
        
        echo
        read -p "Press Enter to continue..."
        clear
        show_header
    done
}

# Run main function
main