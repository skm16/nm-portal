# NMDA Portal Data Migration Guide

## Overview

This document provides comprehensive instructions for migrating data from the old Node.js/Express portal to the new WordPress-based NMDA Portal.

**Migration Scope:**
- 70 businesses with 210+ product type fields
- 65 user accounts
- ~130 historical reimbursements (lead generation, advertising, labels)
- Multiple addresses per business
- User-business relationships

**Source Data Location:** `/portal-files/` directory in WordPress root

---

## Table of Contents

1. [Pre-Migration Checklist](#pre-migration-checklist)
2. [Data Structure Overview](#data-structure-overview)
3. [Migration Architecture](#migration-architecture)
4. [Step-by-Step Migration Process](#step-by-step-migration-process)
5. [Validation & Testing](#validation--testing)
6. [Rollback Procedures](#rollback-procedures)
7. [Post-Migration Tasks](#post-migration-tasks)
8. [Troubleshooting](#troubleshooting)

---

## Pre-Migration Checklist

### Before You Begin

- [ ] **Backup WordPress Database**
  ```bash
  wp db export backup-before-migration-$(date +%Y%m%d).sql
  ```

- [ ] **Verify portal-files Directory**
  - Location: `/portal-files/`
  - Contains: SQL dumps for all data tables
  - Key files: `nmda_company.sql`, `nmda_user.sql`, `nmda_csr_*.sql`

- [ ] **Test on Staging Environment First**
  - Never run migration on production without testing
  - Verify all data imports correctly on staging
  - Check for any errors or data quality issues

- [ ] **Review Current Data Counts**
  ```sql
  -- Count existing WordPress data (should be minimal/zero)
  SELECT COUNT(*) FROM wp_posts WHERE post_type = 'nmda_business';
  SELECT COUNT(*) FROM wp_users WHERE ID > 1;
  SELECT COUNT(*) FROM wp_nmda_reimbursements;
  ```

- [ ] **Prepare User Communication**
  - Draft email template for password reset notification
  - Prepare user guide for new system
  - Set up support channel for migration questions

---

## Data Structure Overview

### Old Database Schema (Node.js Portal)

#### Primary Key System
- **Type:** UUID (VARCHAR 50)
- **Format:** '10fcf1e1-7237-4c01-acec-051a84421737'
- **Challenge:** Must convert to WordPress auto-increment integers

####  Tables & Record Counts

| Table | Records | Description |
|-------|---------|-------------|
| `company` | 70 | Simplified business records |
| `user` | 65 | User accounts (passwords in external AccountManager) |
| `business_address` | 70+ | Multiple addresses per business |
| `csr_lead` | 46 | Lead generation reimbursements |
| `csr_advertising` | 42 | Advertising reimbursements |
| `csr_labels` | Variable | Label printing reimbursements |

#### Key Fields

**Company Table:**
- `CompanyId` (UUID) - Primary key
- `CompanyName` - Business name
- `FirstName`, `LastName` - Owner name
- `Email`, `Phone` - Contact info
- `Address`, `City`, `State`, `Zip` - Location
- `Logo` - File path (optional)
- `ProductType` - TEXT field with comma-separated products

**User Table:**
- `UserId` (UUID) - Primary key
- `CompanyId` (UUID) - Foreign key to company
- `AccountManagerGUID` - External authentication system reference
- `Email`, `FirstName`, `LastName` - User info

### New WordPress Structure

#### Custom Post Types
- **`nmda_business`** - Business profiles with ACF fields

#### Custom Tables
- **`wp_nmda_user_business`** - Many-to-many user-business relationships
- **`wp_nmda_business_addresses`** - Multiple addresses per business
- **`wp_nmda_reimbursements`** - Unified reimbursement storage
- **`wp_nmda_id_mapping`** - UUID to WordPress ID translation

#### Taxonomies
- **`product_type`** - Hierarchical product categories
- **`business_category`** - Business classifications

---

## Migration Architecture

### UUID to WordPress ID Mapping

Every old UUID is mapped to a new WordPress integer ID:

```php
// Mapping table structure
wp_nmda_id_mapping:
  - old_id (VARCHAR 50) - Original UUID
  - new_id (BIGINT 20) - New WordPress ID
  - entity_type (VARCHAR 50) - 'user', 'business', 'address', 'reimbursement'
  - migrated_date (DATETIME) - When migration occurred
  - notes (TEXT) - Optional migration notes
```

### Data Transformation Flow

```
OLD System                    NEW WordPress
─────────────────────────────────────────────────
company.CompanyId (UUID)  →  nmda_business.ID (int)
company.CompanyName       →  post_title
company.ProductType       →  product_type taxonomy terms
company.Email             →  business_email (ACF)
company.Phone             →  business_phone (ACF)

user.UserId (UUID)        →  wp_users.ID (int)
user.Email                →  user_email
user.CompanyId            →  wp_nmda_user_business relationship

csr_lead.LeadId (UUID)    →  wp_nmda_reimbursements.id
csr_lead.* (all fields)   →  data column (JSON)
```

###  Password Strategy

**Chosen Approach:** Common Temporary Password with Forced Reset

- **Temporary Password:** `NMDAPortal2024!`
- **Implementation:**
  - All imported users get this password
  - `show_password_fields` user meta set to `false`
  - Users forced to reset on first login
  - Password reset email sent to all users post-migration

---

## Step-by-Step Migration Process

### Phase 1: Foundation Setup

#### Create ID Mapping Table

The mapping table is automatically created when the theme loads (if in WP-CLI context).

```php
// Manually trigger if needed:
nmda_init_migration_tables();
```

**Table Structure:**
```sql
CREATE TABLE wp_nmda_id_mapping (
  id bigint(20) AUTO_INCREMENT PRIMARY KEY,
  old_id varchar(50) NOT NULL,
  new_id bigint(20) NOT NULL,
  entity_type varchar(50) NOT NULL,
  migrated_date datetime NOT NULL,
  notes text,
  UNIQUE KEY old_id_type (old_id, entity_type)
);
```

#### Key Functions Available

```php
// Store mapping
nmda_store_id_mapping($uuid, $wp_id, 'business', 'Migrated from nmda_company');

// Retrieve WordPress ID from UUID
$wp_id = nmda_get_new_id_from_uuid($uuid, 'business');

// Retrieve UUID from WordPress ID
$uuid = nmda_get_uuid_from_new_id($wp_id, 'business');

// Data cleaning utilities
$clean_phone = nmda_clean_phone($raw_phone);
$clean_email = nmda_clean_email($raw_email);
$clean_url = nmda_clean_url($raw_url);

// Generate unique username
$username = nmda_generate_username($email, $first_name, $last_name);
```

---

### Phase 2: User Migration

**Source File:** `portal-files/nmda_user.sql`
**Target:** `wp_users` + `wp_usermeta`

#### Migration Script (WP-CLI)

```bash
wp nmda migrate users --dry-run  # Test first
wp nmda migrate users --execute  # Run migration
```

#### What Happens:

1. **Parse nmda_user.sql**
   - Read all 65 user records
   - Extract: UserId (UUID), Email, FirstName, LastName, CompanyId, AccountManagerGUID

2. **For Each User:**
   - Check if email already exists (skip if duplicate)
   - Generate unique username from email
   - Create WordPress user:
     ```php
     wp_insert_user([
       'user_login' => $username,
       'user_email' => $email,
       'user_pass' => 'NMDAPortal2024!',
       'first_name' => $first_name,
       'last_name' => $last_name,
       'role' => 'business_owner'
     ]);
     ```
   - Store AccountManagerGUID in user meta (for reference)
   - Set password reset flag: `update_user_meta($user_id, 'show_password_fields', false);`
   - Map old UserId → new WordPress ID in mapping table

3. **Generate User Report:**
   - List of created users with usernames
   - List of skipped users (duplicates/errors)
   - Temporary password (NMDAPortal2024!)

#### Expected Results

```
✓ 65 users attempted
✓ 63 users created successfully
⚠ 2 users skipped (duplicate emails)
✗ 0 errors
```

---

### Phase 3: Business Migration

**Source File:** `portal-files/nmda_company.sql`
**Target:** `nmda_business` Custom Post Type

#### Migration Script

```bash
wp nmda migrate businesses --dry-run
wp nmda migrate businesses --execute
```

#### What Happens:

1. **Parse nmda_company.sql**
   - Read all 70 company records
   - Extract all fields including ProductType

2. **For Each Business:**
   ```php
   // Create custom post
   $post_id = wp_insert_post([
     'post_type' => 'nmda_business',
     'post_title' => $company_name,
     'post_content' => $profile_description,
     'post_status' => 'publish', // All are approved
     'post_author' => 1 // Temporary, will be updated in Phase 4
   ]);

   // Map core ACF fields
   update_field('business_email', $email, $post_id);
   update_field('business_phone', $phone, $post_id);
   update_field('business_website', $website, $post_id);
   update_field('dba_name', $dba, $post_id);

   // Parse ProductType and assign taxonomy terms
   $products = explode(',', $product_type);
   foreach ($products as $product) {
     wp_set_object_terms($post_id, trim($product), 'product_type', true);
   }

   // Store CompanyId mapping
   nmda_store_id_mapping($company_id, $post_id, 'business');
   ```

3. **Product Type Handling:**
   - Old system stored as comma-separated TEXT: "Apples, Chiles, Lettuce"
   - New system uses hierarchical taxonomy
   - Migration creates/assigns appropriate terms

#### Expected Results

```
✓ 70 businesses attempted
✓ 70 businesses created successfully
✓ 150+ product terms assigned
✗ 0 errors
```

---

### Phase 4: User-Business Relationships

**Source:** User table `CompanyId` foreign keys
**Target:** `wp_nmda_user_business` junction table

#### Migration Script

```bash
wp nmda migrate relationships --dry-run
wp nmda migrate relationships --execute
```

#### What Happens:

1. **For Each User Record:**
   ```php
   // Get mapped IDs
   $user_id = nmda_get_new_id_from_uuid($user_uuid, 'user');
   $business_id = nmda_get_new_id_from_uuid($company_uuid, 'business');

   // Create relationship
   $wpdb->insert('wp_nmda_user_business', [
     'user_id' => $user_id,
     'business_id' => $business_id,
     'role' => 'owner', // Original users are owners
     'status' => 'active',
     'invited_date' => $created_date,
     'accepted_date' => $created_date
   ]);

   // Update post_author
   wp_update_post([
     'ID' => $business_id,
     'post_author' => $user_id
   ]);
   ```

#### Expected Results

```
✓ 65 relationships created
✓ 65 post_author fields updated
✗ 0 orphaned users
✗ 0 orphaned businesses
```

---

### Phase 5: Address Migration

**Source File:** `portal-files/nmda_business_address.sql`
**Target:** `wp_nmda_business_addresses` + ACF fields

#### Migration Script

```bash
wp nmda migrate addresses --dry-run
wp nmda migrate addresses --execute
```

#### What Happens:

1. **For Each Address Record:**
   ```php
   // Get mapped business ID
   $business_id = nmda_get_new_id_from_uuid($old_business_id, 'business');

   // Insert into custom table
   $wpdb->insert('wp_nmda_business_addresses', [
     'business_id' => $business_id,
     'address_type' => $address_type,
     'address_name' => $address_name,
     'street_address' => $street,
     'street_address_2' => $street2,
     'city' => $city,
     'state' => $state,
     'zip_code' => $zip,
     'county' => $county,
     'is_primary' => $is_primary
   ]);

   // If primary address, also populate ACF fields
   if ($is_primary) {
     update_field('primary_address', $street, $business_id);
     update_field('primary_city', $city, $business_id);
     update_field('primary_state', $state, $business_id);
     update_field('primary_zip', $zip, $business_id);
   }
   ```

#### Expected Results

```
✓ 70+ addresses migrated
✓ 70 primary addresses set in ACF
✗ 0 orphaned addresses
```

---

### Phase 6: Reimbursement Migration

**Source Files:**
- `portal-files/nmda_csr_lead.sql` (46 records)
- `portal-files/nmda_csr_advertising.sql` (42 records)
- `portal-files/nmda_csr_labels.sql` (variable records)

**Target:** `wp_nmda_reimbursements` unified table

#### Migration Script

```bash
wp nmda migrate reimbursements --dry-run
wp nmda migrate reimbursements --execute
```

#### What Happens:

1. **Parse All Three CSR Tables**

2. **For Each Reimbursement:**
   ```php
   // Map IDs
   $business_id = nmda_get_new_id_from_uuid($old_business_id, 'business');
   $user_id = nmda_get_new_id_from_uuid($old_user_id, 'user');

   // Determine status
   if ($approved == 1) {
     $status = 'approved';
   } elseif ($rejected == 1) {
     $status = 'rejected';
   } else {
     $status = 'submitted';
   }

   // Encode all form fields as JSON
   $form_data = json_encode([
     'event_type' => $event_type,
     'location' => $location,
     'description' => $description,
     'cost_breakdown' => [
       'booth_fee' => $booth_fee,
       'materials' => $materials,
       // ... all other fields
     ]
   ]);

   // Insert into reimbursements table
   $wpdb->insert('wp_nmda_reimbursements', [
     'business_id' => $business_id,
     'user_id' => $user_id,
     'type' => 'lead', // or 'advertising', 'labels'
     'status' => $status,
     'fiscal_year' => $fiscal_year,
     'data' => $form_data,
     'admin_notes' => $admin_notes,
     'created_at' => $date_submitted,
     'updated_at' => $date_approved ?? $date_rejected
   ]);

   // Store reimbursement ID mapping
   nmda_store_id_mapping($old_reimb_id, $new_reimb_id, 'reimbursement');
   ```

3. **Preserve Approval History:**
   ```php
   // Log in communications table
   nmda_log_communication($business_id, $user_id, 'reimbursement', [
     'type' => 'submission',
     'date' => $date_submitted
   ]);

   if ($status !== 'submitted') {
     nmda_log_communication($business_id, $user_id, 'reimbursement', [
       'type' => $status,
       'date' => $date_approved ?? $date_rejected,
       'admin' => $admin_initials,
       'notes' => $admin_notes
     ]);
   }
   ```

#### Expected Results

```
✓ 130+ reimbursements migrated
  - 46 lead generation
  - 42 advertising
  - Variable labels
✓ All approval history preserved
✓ All form data preserved in JSON
✗ 0 data loss
```

---

## Validation & Testing

### Automated Validation Checks

Run after migration completes:

```bash
wp nmda validate migration
```

#### Count Comparisons

```sql
-- Users
SELECT 'Old Users', COUNT(*) FROM old_db.user;
SELECT 'New Users', COUNT(*) - 1 FROM wp_users; -- Subtract admin user

-- Businesses
SELECT 'Old Companies', COUNT(*) FROM old_db.company;
SELECT 'New Businesses', COUNT(*) FROM wp_posts WHERE post_type = 'nmda_business';

-- Reimbursements
SELECT 'Old Lead Reimbursements', COUNT(*) FROM old_db.csr_lead;
SELECT 'Old Advertising Reimbursements', COUNT(*) FROM old_db.csr_advertising;
SELECT 'New Total Reimbursements', COUNT(*) FROM wp_nmda_reimbursements;
```

#### Relationship Integrity

```sql
-- Check for orphaned relationships
SELECT * FROM wp_nmda_user_business ub
LEFT JOIN wp_users u ON ub.user_id = u.ID
WHERE u.ID IS NULL;

SELECT * FROM wp_nmda_user_business ub
LEFT JOIN wp_posts p ON ub.business_id = p.ID
WHERE p.ID IS NULL;

-- Check for orphaned addresses
SELECT * FROM wp_nmda_business_addresses ba
LEFT JOIN wp_posts p ON ba.business_id = p.ID
WHERE p.ID IS NULL;

-- Check for orphaned reimbursements
SELECT * FROM wp_nmda_reimbursements r
LEFT JOIN wp_posts p ON r.business_id = p.ID
WHERE p.ID IS NULL;
```

#### Required Field Validation

```sql
-- Businesses without names
SELECT ID, post_title FROM wp_posts
WHERE post_type = 'nmda_business'
AND (post_title IS NULL OR post_title = '');

-- Users without email
SELECT ID, user_email FROM wp_users
WHERE user_email IS NULL OR user_email = '';

-- Reimbursements without type
SELECT id, type FROM wp_nmda_reimbursements
WHERE type IS NULL OR type = '';
```

### Manual Spot Checks

1. **Login as migrated user:**
   - Username: [from migration report]
   - Password: `NMDAPortal2024!`
   - Should be forced to reset password

2. **View business profile:**
   - Navigate to Dashboard
   - Check business information displays correctly
   - Verify products/categories are correct

3. **Check reimbursement history:**
   - Navigate to My Reimbursements
   - Verify historical submissions show correct status
   - Check approval dates and notes preserved

4. **Test edit profile:**
   - Make a change and save
   - Verify change persists after reload

---

## Rollback Procedures

### If Migration Fails

1. **Stop immediately** - Don't continue if errors occur

2. **Restore from backup:**
   ```bash
   wp db import backup-before-migration-YYYYMMDD.sql
   ```

3. **Clear migration tables:**
   ```sql
   TRUNCATE TABLE wp_nmda_id_mapping;
   ```

4. **Review error logs:**
   ```bash
   tail -f wp-content/debug.log
   ```

5. **Fix issues** and re-run on staging

### Partial Rollback

To rollback specific entities:

```sql
-- Get IDs from mapping table
SELECT new_id FROM wp_nmda_id_mapping
WHERE entity_type = 'business'
AND migrated_date > '2024-11-06 00:00:00';

-- Delete businesses
DELETE FROM wp_posts WHERE ID IN (...);
DELETE FROM wp_postmeta WHERE post_id IN (...);

-- Delete mapping records
DELETE FROM wp_nmda_id_mapping
WHERE entity_type = 'business'
AND migrated_date > '2024-11-06 00:00:00';
```

---

## Post-Migration Tasks

### 1. Send User Notifications

Email template saved at: `portal-files/email-templates/migration-notification.html`

**Email Content:**
```
Subject: Welcome to the New NMDA Portal

Dear [FirstName],

We've migrated your account to our new portal system. Your business information
and reimbursement history have been preserved.

Login Details:
- URL: https://portal.nmda.nm.gov
- Username: [username]
- Temporary Password: NMDAPortal2024!

You will be required to create a new password when you first log in.

Questions? Contact support@nmda.nm.gov

Thank you,
NMDA Team
```

### 2. Monitor for Issues

**First 48 hours:**
- Check debug log frequently: `tail -f wp-content/debug.log`
- Monitor support email for user questions
- Watch for login issues or data discrepancies

**First week:**
- Review user login statistics
- Track password reset completions
- Monitor for any data accuracy reports

### 3. Update Documentation

- [ ] Update user guide with new screenshots
- [ ] Record any migration issues encountered
- [ ] Document any data transformations made
- [ ] Update this guide with lessons learned

### 4. Performance Optimization

After migration, optimize database:

```sql
OPTIMIZE TABLE wp_posts;
OPTIMIZE TABLE wp_postmeta;
OPTIMIZE TABLE wp_users;
OPTIMIZE TABLE wp_usermeta;
OPTIMIZE TABLE wp_nmda_user_business;
OPTIMIZE TABLE wp_nmda_reimbursements;
```

---

## Troubleshooting

### Common Issues

#### Issue: Duplicate Email Addresses

**Symptom:** Error creating user: "Email already exists"

**Solution:**
- Check if user already exists: `wp user get user@example.com`
- If legitimate duplicate, append number to email: `user+1@example.com`
- Or skip and manually resolve later

#### Issue: Invalid UUID Format

**Symptom:** Cannot find mapped ID for UUID

**Solution:**
```php
// Verify UUID format
if (preg_match('/^[a-f0-9-]{36}$/', $uuid)) {
  // Valid UUID
} else {
  // Log error and skip
  error_log("Invalid UUID format: $uuid");
}
```

#### Issue: Missing Company for User

**Symptom:** User has CompanyId but company doesn't exist

**Solution:**
- Log users with missing companies
- Create placeholder businesses
- Or assign to generic "Pending Migration" business
- Review manually after migration

#### Issue: Special Characters in Names

**Symptom:** Mojibake or encoding issues

**Solution:**
```php
// Ensure UTF-8 encoding
$name = mb_convert_encoding($name, 'UTF-8', 'auto');
$name = sanitize_text_field($name);
```

### Debug Mode

Enable detailed logging during migration:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('NMDA_MIGRATION_DEBUG', true);
```

### Getting Help

- **Migration Logs:** `wp-content/debug.log`
- **Migration Stats:** Run `wp nmda migration stats`
- **ID Mappings:** Query `wp_nmda_id_mapping` table
- **Error Reports:** Email to developer with debug.log attached

---

## Migration Statistics & Reporting

### Generate Final Report

```bash
wp nmda migration report --output=migration-report-YYYYMMDD.txt
```

**Report includes:**
- Total counts per entity type
- Success/failure rates
- List of errors encountered
- Data integrity validation results
- Execution time
- Next steps

### Sample Report Output

```
NMDA Portal Data Migration Report
Generated: 2024-11-06 14:30:00
─────────────────────────────────────────

USERS
✓ Attempted: 65
✓ Success: 63
⚠ Skipped: 2 (duplicate emails)
✗ Errors: 0

BUSINESSES
✓ Attempted: 70
✓ Success: 70
✗ Errors: 0

RELATIONSHIPS
✓ Created: 63
✗ Orphaned: 0

ADDRESSES
✓ Migrated: 72
✓ Primary addresses: 70

REIMBURSEMENTS
✓ Lead: 46
✓ Advertising: 42
✓ Labels: 15
✓ Total: 103

DATA INTEGRITY
✓ No orphaned records
✓ All relationships valid
✓ All required fields populated

EXECUTION TIME
Total: 3 minutes 47 seconds

NEXT STEPS
1. Send password reset emails to 63 users
2. Manually review 2 skipped users
3. Update DNS to point to new portal
4. Monitor logs for 48 hours
```

---

## Conclusion

This migration guide provides a complete roadmap for transferring data from the old Node.js portal to the new WordPress system. Follow each phase carefully, validate thoroughly, and monitor closely after completion.

**Remember:**
- Always test on staging first
- Keep backups
- Document everything
- Communicate with users
- Monitor post-migration

For questions or issues, contact the development team.

---

**Document Version:** 1.0
**Last Updated:** 2024-11-06
**Author:** NMDA Portal Development Team
