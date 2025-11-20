# NMDA Portal Migration - Improved Execution Guide

## Overview

This guide provides an improved migration process that addresses the user-business connection issues. The enhanced approach uses multiple matching strategies to ensure maximum successful relationships.

## Pre-Migration Requirements

### 1. Backup Everything
```bash
# Database backup
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# File system backup
tar -czf portal-files-backup.tar.gz portal-files/
```

### 2. Verify Source Files
```bash
# Check all required files exist
ls -la portal-files/*.sql

# Expected files:
# - nmda_user.sql (301 users)
# - nmda_company.sql (134 businesses)
# - nmda_business_address.sql
# - nmda_csr_*.sql (reimbursements)
```

## Phase 1: Data Validation & Analysis

### Step 1.1: Run Validation Check
```bash
# Analyze data integrity and matching potential
wp nmda validate data

# Review the output for:
# - UUID match rate
# - Email match potential
# - Name match candidates
# - Unmatched records
```

### Step 1.2: Generate Match Report
```bash
# Create detailed analysis with fix suggestions
wp nmda validate data --fix > validation-report.txt
```

### Step 1.3: Review Unmatched Users
If validation shows unmatched users, review the list and prepare manual mappings:

1. Export unmatched users:
```bash
wp nmda export unmatched-users --format=csv > unmatched-users.csv
```

2. Create manual mapping file (`manual-mappings.csv`):
```csv
user_email,business_id
john.doe@farm.com,123
jane.smith@ranch.org,456
```

## Phase 2: Clean Migration Execution

### Step 2.1: Reset Previous Migration (If Needed)
```bash
# Clear existing data from failed migration
wp db query "DELETE FROM wp_posts WHERE post_type = 'nmda_business';"
wp db query "DELETE FROM wp_users WHERE ID > 1 AND user_login LIKE 'nmda_%';"
wp db query "TRUNCATE TABLE wp_nmda_user_business;"
wp db query "TRUNCATE TABLE wp_nmda_id_mapping;"
```

### Step 2.2: Migrate Users
```bash
# Dry run first
wp nmda migrate users --dry-run

# Review output, then execute
wp nmda migrate users --execute

# Verify
wp user list --role=business_owner --format=count
# Expected: ~301 users
```

### Step 2.3: Migrate Businesses
```bash
# Dry run first
wp nmda migrate businesses --dry-run

# Execute
wp nmda migrate businesses --execute

# Verify
wp post list --post_type=nmda_business --format=count
# Expected: 134 businesses
```

### Step 2.4: Create Relationships (IMPROVED)
```bash
# First, dry run with enhanced matching
wp nmda migrate relationships-enhanced --dry-run --strategy=all

# Review matching statistics
# If satisfied with match rate, execute

# Execute with all strategies
wp nmda migrate relationships-enhanced --execute --strategy=all

# Or with manual mappings if needed
wp nmda migrate relationships-enhanced --execute --strategy=all --manual-map=manual-mappings.csv
```

## Phase 3: Verification & Quality Assurance

### Step 3.1: Verify Relationship Success Rate
```bash
# Check relationship statistics
wp db query "
SELECT 
    COUNT(DISTINCT user_id) as connected_users,
    COUNT(DISTINCT business_id) as connected_businesses,
    COUNT(*) as total_relationships
FROM wp_nmda_user_business;
"

# Expected:
# - connected_users: ~301
# - connected_businesses: ~134
# - total_relationships: 300+
```

### Step 3.2: Identify Orphaned Records
```bash
# Find businesses without owners
wp db query "
SELECT p.ID, p.post_title 
FROM wp_posts p
LEFT JOIN wp_nmda_user_business ub ON p.ID = ub.business_id
WHERE p.post_type = 'nmda_business' 
AND ub.id IS NULL;
"

# Find users without businesses
wp db query "
SELECT u.ID, u.user_email 
FROM wp_users u
LEFT JOIN wp_nmda_user_business ub ON u.ID = ub.user_id
WHERE u.ID > 1 
AND ub.id IS NULL;
"
```

### Step 3.3: Audit Match Quality
```bash
# Review low-confidence matches
wp db query "
SELECT pm1.post_id, pm1.meta_value as match_type, pm2.meta_value as confidence
FROM wp_postmeta pm1
JOIN wp_postmeta pm2 ON pm1.post_id = pm2.post_id
WHERE pm1.meta_key = '_migration_match_type'
AND pm2.meta_key = '_migration_match_confidence'
AND CAST(pm2.meta_value AS UNSIGNED) < 70
ORDER BY CAST(pm2.meta_value AS UNSIGNED) ASC;
"
```

## Phase 4: Manual Remediation

### Step 4.1: Handle Unmatched Users
For users that couldn't be automatically matched:

1. Review the `unmapped_users.csv` file
2. Manually identify correct businesses
3. Create relationships manually:

```php
// Via WP-CLI custom command or PHP script
$user_id = 123; // WordPress user ID
$business_id = 456; // WordPress post ID

$wpdb->insert(
    $wpdb->prefix . 'nmda_user_business',
    array(
        'user_id' => $user_id,
        'business_id' => $business_id,
        'role' => 'owner',
        'status' => 'active',
        'invited_date' => current_time('mysql'),
        'accepted_date' => current_time('mysql'),
    )
);
```

### Step 4.2: Fix Incorrect Matches
If any matches are incorrect:

```bash
# Remove incorrect relationship
wp db query "
DELETE FROM wp_nmda_user_business 
WHERE user_id = X AND business_id = Y;
"

# Create correct relationship
wp db query "
INSERT INTO wp_nmda_user_business 
(user_id, business_id, role, status, invited_date, accepted_date)
VALUES (X, Z, 'owner', 'active', NOW(), NOW());
"
```

## Phase 5: Complete Migration

### Step 5.1: Migrate Addresses
```bash
wp nmda migrate addresses --execute
```

### Step 5.2: Migrate Reimbursements
```bash
wp nmda migrate reimbursements --execute
```

### Step 5.3: Final Validation
```bash
wp nmda validate migration
```

## Success Metrics

### Target Goals:
- ✅ 95%+ users connected to businesses
- ✅ All businesses have at least one owner
- ✅ No duplicate relationships
- ✅ All data integrity constraints met

### Actual Results (Track):
- [ ] Total users migrated: ___/301
- [ ] Total businesses migrated: ___/134
- [ ] Successful relationships: ___
- [ ] Connection rate: ___%
- [ ] Manual interventions required: ___

## Troubleshooting Common Issues

### Issue: "Could not find business for user"
**Solution**: User's CompanyId doesn't match any business. Use email or name matching strategy.

### Issue: Multiple businesses with same email
**Solution**: Manual review required. Check business names and owner names for correct match.

### Issue: User already exists
**Solution**: Either skip (if correct) or merge accounts if duplicate.

### Issue: Low match confidence
**Solution**: Review matches with confidence <70% manually to ensure accuracy.

## Post-Migration Tasks

1. **Send welcome emails** to all migrated users with:
   - New login credentials
   - Password reset link
   - Portal access instructions

2. **Quality check** random sample:
   - Test 10-20 user logins
   - Verify business profile access
   - Check permission levels

3. **Monitor logs** for 48 hours:
   ```bash
   tail -f wp-content/debug.log
   ```

4. **Generate migration report**:
   ```bash
   wp nmda generate migration-report --output=final-report.html
   ```

## Rollback Procedure (If Needed)

```bash
# Restore database
wp db import backup-[timestamp].sql

# Clear migration artifacts
rm unmapped_users.csv
rm manual-mappings.csv

# Reset ID mapping table
wp db query "TRUNCATE TABLE wp_nmda_id_mapping;"
```

## Support Contacts

- Technical issues: [Development team]
- Data questions: [Database administrator]
- User concerns: [Customer support]
- Emergency: [On-call developer]
