# NMDA WordPress Import System

A comprehensive WP-CLI based solution for importing SQL data into WordPress with ACF field mapping, designed specifically for non-profit organizations in the rare disease space.

## 🎯 Overview

This import system provides a robust, production-ready solution for migrating data into WordPress while maintaining data integrity, relationships, and ACF field mappings. It includes automated backup, rollback capabilities, and comprehensive auditing features.

## 🚀 Features

- **Comprehensive Auditing**: Pre-import analysis of SQL files and system configuration
- **Automatic Backup**: Creates database backups before any import operation
- **ACF Integration**: Automatic mapping of SQL data to ACF fields
- **Relationship Management**: Handles complex post relationships and foreign keys
- **Rollback Capability**: One-command rollback to previous state
- **Progress Tracking**: Real-time progress bars and detailed logging
- **Test Data Generation**: Built-in test data generator for development
- **Interactive CLI**: User-friendly command-line interface

## 📋 Prerequisites

- WordPress installation (5.0+)
- WP-CLI installed and configured
- PHP 7.4+ with MySQL support
- Advanced Custom Fields (ACF) plugin
- Write permissions for backup directory
- Sufficient disk space for backups

## 🛠️ Installation

1. **Download the scripts** to your WordPress root directory:

```bash
# Clone or download these files:
- wp-import-orchestrator.php
- nmda-data-mapper.php
- wp-import-manager.sh
- generate-test-data.php
```

2. **Set execute permissions**:

```bash
chmod +x wp-import-manager.sh
```

3. **Create required directories**:

```bash
mkdir -p sqls-for-import
mkdir -p acf-json
mkdir -p backups
```

## 📁 Directory Structure

```
wordpress-root/
├── wp-import-orchestrator.php   # Main import orchestrator
├── nmda-data-mapper.php         # Data mapping utilities
├── wp-import-manager.sh         # Interactive CLI wrapper
├── generate-test-data.php       # Test data generator
├── sqls-for-import/             # Place SQL files here
│   ├── 01-companies.sql
│   ├── 02-businesses.sql
│   ├── 03-applications.sql
│   └── 04-reimbursements.sql
├── acf-json/                    # ACF field group JSON files
│   └── *.json
└── backups/                     # Automated backups and logs
    ├── *.sql                    # Database backups
    └── *.log                    # Import logs
```

## 🎮 Usage

### Interactive Mode (Recommended)

Run the interactive manager:

```bash
./wp-import-manager.sh
```

This provides a menu-driven interface with options for:
- Audit
- Import
- Rollback
- Quick Test
- Backup Management
- ACF Validation

### Direct WP-CLI Commands

#### Audit Mode

Analyze SQL files without making changes:

```bash
wp eval-file wp-import-orchestrator.php --mode=audit
```

#### Import Mode

Execute the full import with automatic backup:

```bash
wp eval-file wp-import-orchestrator.php --mode=import --force
```

#### Rollback Mode

Restore from the latest backup:

```bash
wp eval-file wp-import-orchestrator.php --mode=rollback --force
```

### Generate Test Data

Create sample SQL files for testing:

```bash
wp eval-file generate-test-data.php
```

## 🗂️ Supported Post Types

The system handles these custom post types:

| Post Type | Description | Key Fields |
|-----------|-------------|------------|
| `nmda_business` | Business Profiles | Name, Description, Contact Info, Company Relationship |
| `company` | Companies | Legal Name, EIN, Industry, Revenue |
| `nmda-applications` | Membership Applications | Applicant Info, Status, Organization Details |
| `nmda-reimbursements` | Reimbursement Requests | Amount, Status, Payment Details |

## 🔄 ACF Field Mapping

The system automatically maps SQL columns to ACF fields. Example mapping for business profiles:

```php
'business_name' => 'field_business_name'
'business_email' => 'field_business_email'
'company_id' => 'field_company_relationship'
'member_since' => 'field_member_since'
```

### Supported Field Types

- **Text/Textarea**: Direct mapping
- **Date/DateTime**: Automatic format conversion
- **Number**: Type casting (int/float)
- **Relationship**: Post ID resolution
- **File/Image**: Attachment ID mapping
- **Repeater**: JSON array handling
- **Select/Checkbox**: Value normalization

## 📊 Import Process Flow

1. **Pre-Import Audit**
   - Verify SQL file structure
   - Check ACF field groups
   - Validate post types
   - Count records and relationships

2. **Backup Creation**
   - Full database export
   - Generate rollback script
   - Log file initialization

3. **ACF Synchronization**
   - Import field groups from JSON
   - Verify field configurations

4. **Data Import**
   - Process SQL files sequentially
   - Map data to WordPress structure
   - Create/update posts
   - Apply ACF field values

5. **Post-Processing**
   - Fix relationship references
   - Clear caches
   - Update rewrite rules
   - Verify import results

## 🔧 Configuration

### SQL File Naming Convention

Name SQL files with numeric prefixes to control import order:

```
01-companies.sql          # Import first
02-businesses.sql         # Import second
03-applications.sql       # Import third
04-reimbursements.sql     # Import last
```

### ACF JSON Files

Export ACF field groups to JSON and place in `acf-json/`:

1. In WordPress admin, go to Custom Fields
2. Select field group
3. Export to JSON
4. Save in `acf-json/` directory

## 🚨 Error Handling

The system includes comprehensive error handling:

- **Transaction Support**: Rollback on failure
- **Query Validation**: Skip dangerous operations
- **Duplicate Prevention**: Check existing records
- **Relationship Integrity**: Validate foreign keys
- **Logging**: Detailed error messages

## 📝 Logs and Debugging

### Log Files

Logs are stored in `backups/` with timestamp:

```
backups/2024-01-15-10-30-45-import.log
```

### Viewing Logs

```bash
# View latest log
tail -f backups/*.log

# Search for errors
grep -i error backups/*.log
```

### Debug Mode

Enable verbose output:

```bash
wp eval-file wp-import-orchestrator.php --mode=import --debug
```

## 🔄 Rollback Procedures

### Automatic Rollback

Use the rollback command:

```bash
wp eval-file wp-import-orchestrator.php --mode=rollback
```

### Manual Rollback

Use the generated revert script:

```bash
./backups/revert-2024-01-15-10-30-45-backup.sql.sh
```

### Direct Database Restore

```bash
wp db import backups/2024-01-15-10-30-45-backup.sql
```

## ⚡ Performance Optimization

### Large Datasets

For datasets over 100MB:

1. Increase PHP memory limit:
```php
ini_set('memory_limit', '1024M');
```

2. Disable query logging:
```php
$wpdb->queries = [];
```

3. Use batch processing (built-in)

### Database Optimization

Run after import:

```bash
wp db optimize
```

## 🔒 Security Considerations

- **Backup Storage**: Store backups securely
- **SQL Injection**: All queries are sanitized
- **File Permissions**: Restrict access to import directories
- **Sensitive Data**: Exclude from logs
- **User Permissions**: Require admin privileges

## 🧪 Testing Workflow

1. **Generate test data**:
```bash
wp eval-file generate-test-data.php
```

2. **Run audit**:
```bash
./wp-import-manager.sh
# Select option 1 (Audit)
```

3. **Quick test**:
```bash
./wp-import-manager.sh
# Select option 4 (Quick Test)
```

4. **Verify results** in WordPress admin

5. **Rollback if needed**:
```bash
./wp-import-manager.sh
# Select option 3 (Rollback)
```

## 🤝 Integration Points

### Custom Hooks

The system triggers these actions:

```php
// After import completion
do_action('nmda_after_import', $import_stats);

// After relationship fixing
do_action('nmda_relationships_fixed', $relationship_map);
```

### Extending Functionality

Add custom processors:

```php
add_action('nmda_after_import', function($stats) {
    // Custom post-processing
});
```

## 📚 API Reference

### Main Classes

#### NMDA_Import_Orchestrator

Main orchestration class handling the import process.

**Methods:**
- `run($mode, $force)` - Execute import operation
- `run_audit()` - Analyze without importing
- `run_import()` - Execute full import
- `run_rollback()` - Restore from backup

#### NMDA_Data_Mapper

Handles field mapping and data transformation.

**Methods:**
- `map_to_acf($post_type, $sql_data)` - Map SQL to ACF
- `import_post($post_type, $sql_data)` - Create/update post
- `fix_relationships()` - Resolve post relationships

## 🐛 Troubleshooting

### Common Issues

**Issue: "WP-CLI not found"**
```bash
# Install WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

**Issue: "ACF fields not mapping"**
- Verify ACF is activated
- Check field keys in JSON files
- Run ACF validation: `./wp-import-manager.sh` → Option 7

**Issue: "Import fails with memory error"**
- Increase PHP memory limit
- Split large SQL files
- Use batch mode

**Issue: "Relationships not connecting"**
- Ensure related posts exist
- Check import order (companies before businesses)
- Review relationship map in logs

## 📈 Best Practices

1. **Always audit before importing**
2. **Test on staging environment first**
3. **Keep backups for at least 30 days**
4. **Monitor server resources during import**
5. **Document custom field mappings**
6. **Version control ACF JSON files**
7. **Schedule imports during low-traffic periods**

## 🚀 Production Deployment

1. **Pre-deployment Checklist**:
   - [ ] Test on staging
   - [ ] Verify all post types registered
   - [ ] ACF field groups synchronized
   - [ ] Backup strategy in place
   - [ ] Monitoring configured

2. **Deployment Steps**:
   ```bash
   # 1. Create production backup
   wp db export production-pre-import.sql
   
   # 2. Run audit
   wp eval-file wp-import-orchestrator.php --mode=audit
   
   # 3. Execute import
   wp eval-file wp-import-orchestrator.php --mode=import --force
   
   # 4. Verify results
   wp post list --post_type=nmda_business --format=count
   ```

3. **Post-deployment**:
   - Clear all caches
   - Test critical functionality
   - Monitor error logs
   - Keep rollback ready

## 📞 Support

For issues or questions about this import system:

1. Check the logs in `backups/` directory
2. Run audit mode for diagnostics
3. Review this documentation
4. Test with sample data first

## 📄 License

This import system is provided as-is for use with WordPress installations. Modify as needed for your specific requirements.

## 🔄 Version History

- **1.0.0** - Initial release with core functionality
- **1.1.0** - Added ACF field mapping
- **1.2.0** - Enhanced relationship handling
- **1.3.0** - Interactive CLI interface
- **1.4.0** - Test data generator

---

*Built specifically for non-profit organizations in the rare disease space, focusing on data integrity and ease of use.*