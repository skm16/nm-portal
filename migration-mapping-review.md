# NMDA Portal Migration - Field Mapping Review

## Review Date: 2025-11-07

## Overview
This document reviews the field mappings between the source MySQL database tables and the WordPress destination structures to ensure all data is correctly mapped.

---

## 1. USER TABLE MAPPING

### Source Table: `user`
| Field | Type | Source |
|-------|------|--------|
| UserId | varchar(50) | UUID Primary Key |
| CompanyId | varchar(50) | UUID Foreign Key to company |
| AccountManagerGUID | varchar(50) | UUID Reference |
| FirstName | varchar(45) | User's first name |
| LastName | varchar(45) | User's last name |
| Email | varchar(100) | User's email |

### Destination: WordPress `wp_users` + meta

#### Current Mapping in `map_values_to_fields()` (Line 1273-1282)
```php
'UserId' => $values[0],         // ✓ Correct
'CompanyId' => $values[1],      // ✓ Correct
'AccountManagerGUID' => $values[2], // ✓ Correct
'FirstName' => $values[3],      // ✓ Correct
'LastName' => $values[4],       // ✓ Correct
'Email' => $values[5],          // ✓ Correct
```

#### Destination Mapping in `migrate_single_user()` (Line 516-575)
| Source Field | Destination | Method | Status |
|-------------|-------------|---------|--------|
| UserId | ID Mapping Table | `nmda_store_id_mapping()` | ✓ CORRECT |
| Email | wp_users.user_email | `wp_insert_user()` | ✓ CORRECT |
| FirstName | wp_users.first_name | `wp_insert_user()` | ✓ CORRECT |
| LastName | wp_users.last_name | `wp_insert_user()` | ✓ CORRECT |
| CompanyId | user_meta._migration_company_id | `update_user_meta()` | ✓ CORRECT |
| AccountManagerGUID | NOT STORED | ❌ MISSING | ⚠️ ISSUE |

**Issue Found:**
- `AccountManagerGUID` is parsed but never stored in user meta
- **Recommendation**: Add `update_user_meta( $wp_user_id, 'account_manager_guid', $user_data['AccountManagerGUID'] );` at line ~571

---

## 2. COMPANY/BUSINESS TABLE MAPPING

### Source Table: `company`
| Field | Type | Notes |
|-------|------|-------|
| CompanyId | varchar(50) | UUID Primary Key |
| CompanyName | varchar(100) | Business name |
| FirstName | varchar(45) | Owner's first name |
| LastName | varchar(45) | Owner's last name |
| Address1 | varchar(100) | Primary address line 1 |
| Address2 | varchar(100) | Primary address line 2 |
| City | varchar(45) | City |
| State | varchar(45) | State |
| Zip | varchar(10) | Zip code |
| Phone | varchar(45) | Phone number |
| Email | varchar(45) | Business email |
| Website | varchar(100) | Website URL |
| Logo | varchar(200) | Logo file reference |
| GroupTypeOther | varchar(100) | Group type info |
| ProductType | varchar(200) | CSV list of product types |

### Destination: WordPress Post Type `nmda_business` + post_meta

#### Current Mapping in `map_values_to_fields()` (Line 1285-1307)
```php
'CompanyId' => $values[0],       // ✓ Index 0
'CompanyName' => $values[1],     // ✓ Index 1
'Category' => $values[2] ?? '',  // ⚠️ Does 'Category' exist in company table?
'DBA' => $values[3] ?? '',       // ⚠️ Does 'DBA' exist in company table?
'Address' => $values[6] ?? '',   // ❌ WRONG - Should be $values[4] for Address1
'Address2' => $values[7] ?? '',  // ❌ WRONG - Should be $values[5]
'City' => $values[8] ?? '',      // ❌ WRONG - Should be $values[6]
'State' => $values[9] ?? '',     // ❌ WRONG - Should be $values[7]
'Zip' => $values[10] ?? '',      // ❌ WRONG - Should be $values[8]
'Phone' => $values[11] ?? '',    // ❌ WRONG - Should be $values[9]
'Email' => $values[12] ?? '',    // ❌ WRONG - Should be $values[10]
'Website' => $values[13] ?? '',  // ❌ WRONG - Should be $values[11]
'FirstName' => $values[16] ?? '', // ❌ WRONG - Should be $values[2]
'LastName' => $values[17] ?? '',  // ❌ WRONG - Should be $values[3]
```

**CRITICAL ISSUES FOUND:**
1. **Field indices are completely wrong** - All offsets are incorrect
2. Missing fields: `Logo`, `GroupTypeOther`, `ProductType`
3. Unknown fields being mapped: `Category`, `DBA` (not in CREATE TABLE statement)

#### Correct Mapping Should Be:
```php
case 'company':
case 'business':
    if ( count( $values ) >= 15 ) {
        $mapped = array(
            'CompanyId' => $values[0],      // varchar(50)
            'CompanyName' => $values[1],    // varchar(100)
            'FirstName' => $values[2],      // varchar(45)
            'LastName' => $values[3],       // varchar(45)
            'Address1' => $values[4],       // varchar(100)
            'Address2' => $values[5],       // varchar(100)
            'City' => $values[6],           // varchar(45)
            'State' => $values[7],          // varchar(45)
            'Zip' => $values[8],            // varchar(10)
            'Phone' => $values[9],          // varchar(45)
            'Email' => $values[10],         // varchar(45)
            'Website' => $values[11],       // varchar(100)
            'Logo' => $values[12],          // varchar(200)
            'GroupTypeOther' => $values[13], // varchar(100)
            'ProductType' => $values[14],   // varchar(200)
        );
    }
    break;
```

#### Destination Mapping in `migrate_single_business()` (Line 621-688)
| Source Field | Destination | Status |
|-------------|-------------|---------|
| CompanyId | ID Mapping Table | ✓ CORRECT |
| CompanyName | wp_posts.post_title | ✓ CORRECT |
| Email | post_meta.business_email | ✓ CORRECT |
| Phone | post_meta.business_phone | ✓ CORRECT |
| Website | post_meta.business_website | ✓ CORRECT |
| Address (Address1) | post_meta.business_address | ✓ CORRECT (if fixed) |
| City | post_meta.business_city | ✓ CORRECT (if fixed) |
| State | post_meta.business_state | ✓ CORRECT (if fixed) |
| Zip | post_meta.business_zip | ✓ CORRECT (if fixed) |
| FirstName+LastName | post_meta._owner_name | ❌ NOT IMPLEMENTED |
| Logo | NOT STORED | ❌ MISSING |
| GroupTypeOther | NOT STORED | ❌ MISSING |
| ProductType | Taxonomy business_category | ⚠️ WRONG FIELD NAME |

**Issues:**
1. `_owner_name` meta is referenced but never set in migration
2. `Logo` field is completely ignored
3. `GroupTypeOther` is ignored
4. Line 681 uses non-existent `$category` variable - should be `ProductType`

---

## 3. BUSINESS ADDRESS TABLE MAPPING

### Source Table: `business_address`
| Field | Type |
|-------|------|
| BusinessAddressId | varchar(50) PK |
| BusinessId | varchar(50) FK |
| AddressName | varchar(100) |
| AddressType | varchar(100) |
| Address | varchar(100) |
| Address2 | varchar(100) |
| City | varchar(100) |
| State | varchar(100) |
| Zip | varchar(100) |
| Other | text |
| Reservation | text |
| Category | varchar(200) |

### Destination: `wp_nmda_business_addresses`

#### Current Mapping in `map_values_to_fields()` (Line 1309-1324)
```php
'AddressId' => $values[0],       // ✓ Correct
'BusinessId' => $values[1],      // ✓ Correct
'AddressType' => $values[2],     // ✓ Correct
'AddressName' => $values[3],     // ✓ Correct
'Address' => $values[4],         // ✓ Correct
'Address2' => $values[5],        // ✓ Correct
'City' => $values[6],            // ✓ Correct
'State' => $values[7],           // ✓ Correct
'Zip' => $values[8],             // ✓ Correct
```

**Missing Fields:**
- `Other` (text)
- `Reservation` (text)
- `Category` (varchar 200)

#### Destination Mapping in `migrate_single_address()` (Line 940-976)
| Source Field | Destination Field | Status |
|-------------|------------------|---------|
| BusinessId | business_id | ✓ CORRECT |
| AddressType | address_type | ✓ CORRECT |
| AddressName | address_name | ✓ CORRECT |
| Address | address_line_1 | ✓ CORRECT |
| Address2 | address_line_2 | ✓ CORRECT |
| City | city | ✓ CORRECT |
| State | state | ✓ CORRECT |
| Zip | zip | ✓ CORRECT |
| Other | NOT MAPPED | ❌ MISSING |
| Reservation | NOT MAPPED | ❌ MISSING |
| Category | NOT MAPPED | ❌ MISSING |

---

## 4. REIMBURSEMENT TABLES MAPPING

### Source Tables: `csr_lead`, `csr_advertising`, `csr_labels`
(Exact structure needs verification, but migration code expects):
- Id
- BusinessId
- UserId
- FiscalYear
- DateSubmitted
- Approved
- Rejected
- AdminNotes

#### Current Mapping in `map_values_to_fields()` (Line 1326-1341)
```php
'Id' => $values[0],
'BusinessId' => $values[1],
'UserId' => $values[2],
'FiscalYear' => $values[3],
'DateSubmitted' => $values[4],
'Approved' => $values[5] ?? 0,
'Rejected' => $values[6] ?? 0,
'AdminNotes' => $values[7] ?? '',
```

**Status:** ⚠️ NEEDS VERIFICATION - Actual table structure from SQL dump files needed

---

## 5. ID MAPPING TABLE

### Destination: `wp_nmda_id_mapping`
Used correctly throughout migration to map UUIDs to WordPress integer IDs.

**Status:** ✓ CORRECT

---

## SUMMARY OF ISSUES

### Critical Issues (Must Fix)
1. **Company table field indices are completely wrong** (Lines 1285-1307)
   - All address and contact fields are mapped to wrong array indices
   - Owner name fields (FirstName, LastName) are pointing to wrong indices

2. **Missing AccountManagerGUID** storage for users
   - Parsed but never saved to user meta

3. **Missing company fields:**
   - Logo (varchar 200) - Should be stored
   - GroupTypeOther (varchar 100) - Should be stored
   - FirstName/LastName not being set as _owner_name meta

4. **Wrong ProductType handling:**
   - Line 681 references undefined `$category` variable
   - Should use `ProductType` field for taxonomy assignment

### Medium Priority Issues
1. **Business address missing fields:**
   - Other (text)
   - Reservation (text)
   - Category (varchar 200)

2. **Reimbursement structure needs verification** against actual SQL dumps

---

## RECOMMENDED FIXES

### Fix 1: Correct Company Table Mapping (Line 1285-1307)
Replace the entire company mapping case with:
```php
case 'company':
case 'business':
    if ( count( $values ) >= 15 ) {
        $mapped = array(
            'CompanyId' => $values[0],
            'CompanyName' => $values[1],
            'FirstName' => $values[2],
            'LastName' => $values[3],
            'Address1' => $values[4],
            'Address2' => $values[5],
            'City' => $values[6],
            'State' => $values[7],
            'Zip' => $values[8],
            'Phone' => $values[9],
            'Email' => $values[10],
            'Website' => $values[11],
            'Logo' => $values[12],
            'GroupTypeOther' => $values[13],
            'ProductType' => $values[14],
        );
    }
    break;
```

### Fix 2: Store AccountManagerGUID (Line ~571)
Add after line 568 in `migrate_single_user()`:
```php
// Store AccountManagerGUID
if ( ! empty( $user_data['AccountManagerGUID'] ) ) {
    update_user_meta( $wp_user_id, 'account_manager_guid', $user_data['AccountManagerGUID'] );
}
```

### Fix 3: Store Owner Name Meta (Line ~677)
Add in `migrate_single_business()` after line 677:
```php
// Store owner name in meta for matching
$first_name = $business_data['FirstName'] ?? '';
$last_name = $business_data['LastName'] ?? '';
if ( $first_name || $last_name ) {
    update_post_meta( $post_id, '_owner_name', trim( "$first_name $last_name" ) );
}
```

### Fix 4: Fix Product Type Taxonomy Assignment (Line 680-682)
Replace:
```php
// Set business category taxonomy
if ( ! empty( $category ) ) {
    wp_set_object_terms( $post_id, $category, 'business_category' );
}
```

With:
```php
// Set product type taxonomy
$product_type = $business_data['ProductType'] ?? '';
if ( ! empty( $product_type ) ) {
    // ProductType is CSV list, split and assign
    $products = array_map( 'trim', explode( ',', $product_type ) );
    wp_set_object_terms( $post_id, $products, 'product_type', false );
}
```

### Fix 5: Store Logo and GroupTypeOther (Line ~677)
Add after setting other meta fields:
```php
// Store logo reference
if ( ! empty( $business_data['Logo'] ) ) {
    update_post_meta( $post_id, 'business_logo', $business_data['Logo'] );
}

// Store group type
if ( ! empty( $business_data['GroupTypeOther'] ) ) {
    update_post_meta( $post_id, 'group_type_other', $business_data['GroupTypeOther'] );
}
```

### Fix 6: Enhanced Address Mapping (Line 959-973)
Add the missing fields:
```php
$result = $wpdb->insert(
    $wpdb->prefix . 'nmda_business_addresses',
    array(
        'business_id' => $wp_business_id,
        'address_type' => $address_data['AddressType'] ?? 'physical',
        'address_name' => $address_data['AddressName'] ?? '',
        'address_line_1' => $address_data['Address'] ?? '',
        'address_line_2' => $address_data['Address2'] ?? '',
        'city' => $address_data['City'] ?? '',
        'state' => $address_data['State'] ?? '',
        'zip' => $address_data['Zip'] ?? '',
        'other' => $address_data['Other'] ?? '',           // ADD THIS
        'reservation' => $address_data['Reservation'] ?? '', // ADD THIS
        'category' => $address_data['Category'] ?? '',      // ADD THIS
        'country' => 'US',
        'is_primary' => 1,
        'created_at' => current_time( 'mysql' ),
    )
);
```

---

## TESTING RECOMMENDATIONS

After implementing fixes:

1. **Dry Run Test:**
   ```bash
   wp nmda validate
   wp nmda migrate all --dry-run
   ```

2. **Sample Migration:**
   ```bash
   # Backup first
   wp db export backup-before-fix.sql

   # Test with first 10 records
   wp nmda migrate users --execute
   wp nmda migrate businesses --execute
   wp nmda migrate relationships --execute
   ```

3. **Verify Data:**
   ```sql
   -- Check user meta
   SELECT * FROM wp_usermeta WHERE meta_key = 'account_manager_guid' LIMIT 5;

   -- Check business meta
   SELECT * FROM wp_postmeta WHERE meta_key IN ('_owner_name', 'business_logo', 'group_type_other') LIMIT 10;

   -- Check product type taxonomy
   SELECT * FROM wp_term_relationships WHERE term_taxonomy_id IN (
       SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE taxonomy = 'product_type'
   ) LIMIT 10;
   ```

---

## CONCLUSION

The migration script has **significant field mapping issues** in the company/business table parsing that will cause data loss or incorrect data storage. The array indices are systematically off, causing fields to be mapped to the wrong source data.

**Priority Actions:**
1. Fix company table field mapping immediately (Critical)
2. Add missing field storage for Logo, GroupTypeOther, AccountManagerGUID
3. Fix ProductType taxonomy assignment
4. Add missing address table fields
5. Test thoroughly with dry-run before executing

**Estimated Impact:** Without these fixes, ~70% of business data fields will be mapped incorrectly or lost.
