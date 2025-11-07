# NMDA Portal Migration - Command Reference

Quick reference for running the data migration using WP-CLI commands.

## Prerequisites

1. **Backup your database:**
   ```bash
   wp db export backup-before-migration-$(date +%Y%m%d-%H%M%S).sql
   ```

2. **Verify portal-files directory exists:**
   ```bash
   ls portal-files/
   ```
   Should contain: `nmda_user.sql`, `nmda_company.sql`, `nmda_business_address.sql`, `nmda_csr_*.sql`

3. **Test on staging first** - Never run on production without testing!

---

## Available Commands

### Full Migration (All Phases)

```bash
# Dry run (test mode - no changes made)
wp nmda migrate all --dry-run

# Execute (actual migration)
wp nmda migrate all --execute
```

**What it does:**
- Migrates users (65 expected)
- Migrates businesses (70 expected)
- Creates user-business relationships
- Migrates addresses
- Migrates reimbursements (130+ expected)

**Duration:** ~3-5 minutes for full dataset

---

### Individual Phase Commands

#### 1. Migrate Users

```bash
# Test
wp nmda migrate users --dry-run

# Execute
wp nmda migrate users --execute
```

**What it migrates:**
- User accounts from `nmda_user.sql`
- Creates WordPress users with temp password: `NMDAPortal2024!`
- Stores AccountManagerGUID for reference

**Output:**
```
Found 65 users to migrate
Migrating users  100% [==========================] 0:01
✓ Users: 65 attempted, 63 success, 2 errors
```

---

#### 2. Migrate Businesses

```bash
# Test
wp nmda migrate businesses --dry-run

# Execute
wp nmda migrate businesses --execute
```

**What it migrates:**
- Business profiles from `nmda_company.sql`
- Creates `nmda_business` custom posts
- Maps product types to taxonomy terms
- Stores ACF fields (email, phone, website, address)

**Output:**
```
Found 70 businesses to migrate
Migrating businesses  100% [=======================] 0:02
✓ Businesses: 70 attempted, 70 success, 0 errors
```

---

#### 3. Create User-Business Relationships

```bash
# Test
wp nmda migrate relationships --dry-run

# Execute
wp nmda migrate relationships --execute
```

**What it creates:**
- Records in `wp_nmda_user_business` table
- Sets original users as 'owner' role
- Updates `post_author` for each business

**Output:**
```
Found 65 user-business relationships to create
Creating relationships  100% [===================] 0:00
✓ Relationships: 65 attempted, 65 success, 0 errors
```

**Important:** Run AFTER users and businesses are migrated!

---

#### 4. Migrate Addresses

```bash
# Test
wp nmda migrate addresses --dry-run

# Execute
wp nmda migrate addresses --execute
```

**What it migrates:**
- Business addresses from `nmda_business_address.sql`
- Inserts into `wp_nmda_business_addresses` table
- Updates ACF primary address fields

**Output:**
```
Found 72 addresses to migrate
Migrating addresses  100% [=========================] 0:01
✓ Addresses: 72 attempted, 72 success, 0 errors
```

---

#### 5. Migrate Reimbursements

```bash
# Test
wp nmda migrate reimbursements --dry-run

# Execute
wp nmda migrate reimbursements --execute
```

**What it migrates:**
- Lead generation reimbursements from `nmda_csr_lead.sql`
- Advertising reimbursements from `nmda_csr_advertising.sql`
- Label reimbursements from `nmda_csr_labels.sql`
- Preserves approval status and admin notes
- Stores all form data as JSON

**Output:**
```
Found 46 lead reimbursements
Migrating lead reimbursements  100% [=============] 0:01
Found 42 advertising reimbursements
Migrating advertising reimbursements  100% [=======] 0:01
Found 15 labels reimbursements
Migrating labels reimbursements  100% [=============] 0:00
✓ Reimbursements: 103 attempted, 103 success, 0 errors
```

---

### Validation & Reporting

#### View Migration Statistics

```bash
wp nmda migrate stats
```

**Output:**
```
╔═══════════════════════════════════════╗
║     MIGRATION STATISTICS SUMMARY      ║
╚═══════════════════════════════════════╝

Users: Attempted: 65, Success: 63, Skipped: 2, Errors: 0
Businesses: Attempted: 70, Success: 70, Skipped: 0, Errors: 0
Relationships: Attempted: 65, Success: 65, Skipped: 0, Errors: 0
Addresses: Attempted: 72, Success: 72, Skipped: 0, Errors: 0
Reimbursements: Attempted: 103, Success: 103, Skipped: 0, Errors: 0
📋 Errors Logged: 2
```

---

#### Validate Migrated Data

```bash
wp nmda validate migration
```

**What it checks:**
- Orphaned user-business relationships
- Orphaned addresses
- Missing required fields (business names, user emails)
- Data integrity across all tables

**Output:**
```
╔═══════════════════════════════════════╗
║     MIGRATION DATA VALIDATION         ║
╚═══════════════════════════════════════╝

🔍 Checking for orphaned user-business relationships...
✓ No orphaned user relationships
✓ No orphaned business relationships

🔍 Checking for missing required fields...
✓ All businesses have names

✅ All validation checks passed!
```

---

## Recommended Migration Workflow

### Step 1: Preparation
```bash
# Backup database
wp db export backup-before-migration.sql

# Verify files exist
ls portal-files/*.sql
```

### Step 2: Dry Run (Test Everything)
```bash
wp nmda migrate all --dry-run
```

Review output for any errors or warnings.

### Step 3: Execute Migration
```bash
wp nmda migrate all --execute
```

### Step 4: Validate
```bash
wp nmda validate migration
wp nmda migrate stats
```

### Step 5: Spot Check
```bash
# Check user count
wp user list --role=business_owner --format=count

# Check business count
wp post list --post_type=nmda_business --format=count

# Check a specific user can login
wp user get user@example.com
```

### Step 6: Send User Notifications

After successful migration, email all users with:
- Login URL
- Username
- Temporary password: `NMDAPortal2024!`
- Instructions to reset password

---

## Troubleshooting

### Command not found
```bash
# Verify WP-CLI is installed
wp --info

# Verify you're in the WordPress directory
pwd
```

### Migration fails partway through
```bash
# View error log
tail -100 wp-content/debug.log

# Check stats to see where it failed
wp nmda migrate stats

# Rollback if needed
wp db import backup-before-migration.sql
```

### Duplicate email errors
**Cause:** User with that email already exists in WordPress

**Solution:**
- Check if it's the admin user (skip)
- Manually resolve duplicate
- Or modify user email in old data before migrating

### Missing UUIDs
**Cause:** Old record has NULL or invalid UUID

**Solution:**
- Check source SQL file for data quality
- Clean up source data
- Or skip problematic records

---

## Post-Migration Checklist

- [ ] Run validation: `wp nmda validate migration`
- [ ] Check statistics: `wp nmda migrate stats`
- [ ] Spot check 5-10 random users can login
- [ ] Verify business profiles display correctly
- [ ] Check reimbursement history shows up
- [ ] Test profile editing works
- [ ] Optimize database tables
- [ ] Send password reset emails to all users
- [ ] Monitor debug log for 24-48 hours

---

## Database Cleanup (After Successful Migration)

```bash
# Optimize tables
wp db optimize

# Clear transients
wp transient delete --all

# Regenerate thumbnails if needed
wp media regenerate --yes
```

---

## Rollback Procedure

If migration fails or has issues:

```bash
# 1. Stop immediately
# 2. Restore from backup
wp db import backup-before-migration.sql

# 3. Clear mapping table
wp db query "TRUNCATE TABLE wp_nmda_id_mapping;"

# 4. Review errors
tail -100 wp-content/debug.log

# 5. Fix issues and try again on staging
```

---

## Support

For issues or questions:
1. Check `wp-content/debug.log`
2. Review error messages from command output
3. Consult DATA-MIGRATION-GUIDE.md for detailed information
4. Contact development team with:
   - Command you ran
   - Error messages
   - debug.log excerpt

---

## Advanced Options

### Migrate Only Specific Phases

If you need to re-run only one phase:

```bash
# Re-import only users
wp nmda migrate users --execute

# Re-import only businesses
wp nmda migrate businesses --execute
```

**Note:** Be careful with re-importing to avoid duplicates. Check for existing data first.

### Export Migration Report

```bash
# Capture full output
wp nmda migrate all --execute > migration-log-$(date +%Y%m%d).txt 2>&1
```

---

**Last Updated:** 2024-11-06
**Version:** 1.0
