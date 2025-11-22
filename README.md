# NMDA WordPress Portal

**Status**: ✅ Phase 2 Complete - Production Ready for Testing
**Completion**: 90%
**Last Updated**: November 21, 2025

A comprehensive WordPress-based portal for the New Mexico Department of Agriculture (NMDA) Logo Program, featuring business directory, application workflow, reimbursement management, and member services.

## 🎯 Overview

The NMDA WordPress Portal is a complete web application built on WordPress that manages the New Mexico Logo Program. The system replaces a legacy Node.js/Express application with a modern, maintainable WordPress solution.

### What's Complete ✅

- **Full data migration** from legacy MySQL database (companies, businesses, users, reimbursements)
- **Member features**: Registration, business application (multi-step form with draft save), dashboard, profile editing with auto-save
- **Reimbursement system**: Three form types (Lead Generation, Advertising, Labels) with file uploads and fiscal year limits
- **Admin interfaces**: Business approval workflow, reimbursement management, user directory
- **Public features**: Business directory with search/filter, individual business profiles
- **Document management**: File upload system integrated with WordPress Media Library
- **13 page templates**, 11 CSS files, 7 JavaScript files
- **Comprehensive security**: Nonces, input sanitization, output escaping, file upload validation

### What's Remaining (~5%)

- Additional address management UI (dynamic add/remove)
- Email integration (Mailchimp/ActiveCampaign API)
- Comprehensive testing & QA
- User documentation

**See [PHASE2_COMPLETE.md](PHASE2_COMPLETE.md) for full status report**

## 🚀 Quick Start

### For Developers

1. **Clone/Pull** the latest code from the repository
2. **Review Documentation**:
   - [PHASE2_COMPLETE.md](PHASE2_COMPLETE.md) - Complete feature status
   - [TESTING_CHECKLIST.md](TESTING_CHECKLIST.md) - 608 test cases
   - [OUTSTANDING_TASKS.md](OUTSTANDING_TASKS.md) - Remaining work
   - [MIGRATION-USAGE.md](MIGRATION-USAGE.md) - Data migration guide
   - [CHANGELOG.md](CHANGELOG.md) - Development history
3. **Run Local Environment**: Start your local WordPress installation
4. **Create WordPress Pages**: Create pages for all templates (see PHASE2_COMPLETE.md section 11)
5. **Test Features**: Follow TESTING_CHECKLIST.md

### For Testers

1. Access the portal at your staging/local URL
2. Register a new user account
3. Complete a business application
4. Test the dashboard and profile editing
5. Submit a test reimbursement
6. Report bugs using the template in TESTING_CHECKLIST.md

### For NMDA Staff (Admin)

1. Log in to WordPress admin
2. Navigate to Applications → Pending to approve businesses
3. Navigate to admin Reimbursements to manage requests
4. Use User Directory to manage members
5. Add resources in Posts → Resources

## 🌟 Key Features

### Member Portal
- **Business Application**: Multi-step form with 5 sections, draft save/restore, product selection accordion
- **Member Dashboard**: Multi-business support, quick stats, recent activity, accordion interface
- **Profile Editing**: Auto-save, field-level permissions, tabbed interface (Info, Contact, Social, Products)
- **Reimbursements**: Three form types with file uploads, auto-calculation, fiscal year limits ($5k, $10k, $3k)
- **Resource Center**: Download tracking, search/filter, member-only access

### Admin Tools
- **Business Approval**: AJAX interface, detail modal, bulk actions, email notifications
- **Reimbursement Management**: Filter by status/type/year, approve/reject workflow, admin notes
- **User Directory**: Search/filter, bulk actions, CSV export, statistics dashboard
- **Custom WP-CLI Commands**: Complete data migration system

### Public Features
- **Business Directory**: Search/filter, classification badges, product tags, responsive cards
- **Business Profiles**: Public profiles with contact info, products, social media, location

### Technical Features
- **Data Migration**: Complete WP-CLI migration system with UUID→ID mapping
- **Security**: Nonces, input sanitization, output escaping, role-based access control
- **File Uploads**: WordPress Media Library integration, validation (5MB, PDF/JPG/PNG/DOC)
- **Performance**: AJAX operations, pagination, caching-ready
- **Accessibility**: WCAG AA compliance, keyboard navigation, screen reader friendly

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

## 📚 Documentation

Comprehensive documentation is available for all aspects of the portal:

### For Developers & Technical Staff

- **[PHASE2_COMPLETE.md](PHASE2_COMPLETE.md)** - Complete status report showing 90% completion
  - Feature inventory (what's built)
  - Implementation details
  - Database schema
  - Page templates list
  - Security measures
  - Next steps

- **[CHANGELOG.md](CHANGELOG.md)** - Complete development history (23,000+ lines)
  - Phase 1: Database & Architecture Setup
  - Phase 2: Application form, Dashboard, Admin interfaces
  - All bug fixes and UX improvements
  - Reimbursement forms implementation
  - Edit profile system
  - Document upload system

- **[MIGRATION-USAGE.md](MIGRATION-USAGE.md)** - Data migration guide
  - WP-CLI commands for import
  - UUID → WordPress ID mapping
  - Step-by-step import process
  - Verification procedures
  - Troubleshooting

- **[TESTING_CHECKLIST.md](TESTING_CHECKLIST.md)** - QA testing guide
  - 608 test cases across 20 categories
  - User flows, forms, admin workflows
  - Security, performance, mobile, cross-browser
  - Accessibility testing
  - Bug reporting template

- **[OUTSTANDING_TASKS.md](OUTSTANDING_TASKS.md)** - Remaining work (~10%)
  - Additional address management UI
  - Email integration (Mailchimp/ActiveCampaign)
  - In-portal messaging (optional)
  - Field-level approval workflow (optional)
  - User invitation system (optional)
  - Analytics dashboard (future)
  - Comprehensive testing requirements

- **[CLAUDE.md](CLAUDE.md)** - Original project instructions and specifications

### Project Structure

```
wp-content/themes/nmda-understrap/
├── functions.php           # Main theme controller
├── inc/                    # PHP modules
│   ├── setup.php          # Theme configuration
│   ├── custom-post-types.php  # CPTs and taxonomies
│   ├── database-schema.php    # Custom tables
│   ├── user-management.php    # User functions
│   ├── business-management.php # Business functions
│   ├── reimbursements.php     # Reimbursement system
│   ├── application-forms.php  # Application form
│   ├── acf-field-groups.php   # ACF registration
│   ├── product-taxonomy.php   # Product taxonomy
│   ├── admin-approval.php     # Approval interface
│   ├── admin-reimbursements.php # Reimbursement admin
│   ├── api-integrations.php   # Email service APIs
│   └── cli-migration-commands.php # WP-CLI migration
├── assets/
│   ├── css/              # 11 CSS files
│   └── js/               # 7 JavaScript files
├── page-*.php            # 13 page templates
├── single-nmda_business.php
└── archive-nmda_business.php
```

## 📞 Support & Next Steps

### Immediate Next Steps

1. **Execute Testing** - Follow [TESTING_CHECKLIST.md](TESTING_CHECKLIST.md)
2. **Fix Bugs** - Address issues found during testing
3. **Complete Remaining Features** - See [OUTSTANDING_TASKS.md](OUTSTANDING_TASKS.md)
4. **Create User Documentation** - Write guides for members and admins
5. **Set Up Staging** - Deploy to staging environment
6. **Launch** - Deploy to production when ready

### Getting Help

For issues or questions:

1. **Review Documentation** - Check the docs listed above
2. **Check CHANGELOG** - See if issue already addressed
3. **Testing Checklist** - Verify expected behavior
4. **Outstanding Tasks** - Check if feature is pending implementation

### Reporting Bugs

Use the bug reporting template in [TESTING_CHECKLIST.md](TESTING_CHECKLIST.md) (bottom of file).

## 📄 License

This portal system is provided as-is for use by the New Mexico Department of Agriculture. Modify as needed for your specific requirements.

## 🔄 Version History

### Phase 2 (Current - November 2025)
- **2.0** - Complete member portal with all core features
  - Business application form (multi-step with draft save)
  - Member dashboard (multi-business support)
  - Profile editing (auto-save, permissions)
  - Three reimbursement forms (file uploads, limits)
  - Admin approval interface
  - Admin reimbursement management
  - User directory (admin)
  - Business directory (public)
  - Document upload system
  - 13 page templates
  - Comprehensive security implementation

### Phase 1 (October 2025)
- **1.0** - Database architecture and foundation
  - Custom database tables
  - Custom post types and taxonomies
  - ACF field groups
  - User management system
  - Role-based access control
  - Product taxonomy (simplified from 202 to 70)
  - Data migration system (WP-CLI)
  - Complete migration from Node.js legacy system

---

**NMDA WordPress Portal** - Built for the New Mexico Department of Agriculture Logo Program
**Status**: Phase 2 Complete - 90% - Ready for Testing
**Next Phase**: Testing, bug fixes, and production deployment