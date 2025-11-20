<?php
/**
 * Diagnostic script for relationship issues
 * Run with: wp eval-file diagnose-relationships.php
 */

global $wpdb;

echo "=== NMDA Relationship Diagnostics ===\n\n";

// 1. Check ID mapping table
echo "1. Checking ID Mapping Table:\n";
$mapping_table = $wpdb->prefix . 'nmda_id_mapping';
$counts = $wpdb->get_results("
    SELECT entity_type, COUNT(*) as count
    FROM $mapping_table
    GROUP BY entity_type
");
foreach ($counts as $row) {
    echo "   - {$row->entity_type}: {$row->count} mappings\n";
}

// 2. Sample mappings
echo "\n2. Sample ID Mappings:\n";
$samples = $wpdb->get_results("SELECT * FROM $mapping_table LIMIT 10");
foreach ($samples as $sample) {
    echo "   {$sample->entity_type}: {$sample->old_id} => {$sample->new_id}\n";
}

// 3. Check a sample business
echo "\n3. Sample Business Data:\n";
$business = $wpdb->get_row("
    SELECT p.ID, p.post_title
    FROM {$wpdb->posts} p
    WHERE p.post_type = 'nmda_business'
    LIMIT 1
");

if ($business) {
    echo "   Business ID: {$business->ID}\n";
    echo "   Business Name: {$business->post_title}\n\n";

    // Get meta
    $meta = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE post_id = %d
        AND meta_key IN ('external_business_id', 'related_company', 'owner_wp_user', 'external_user_id')
    ", $business->ID));

    echo "   Meta data:\n";
    foreach ($meta as $m) {
        echo "   - {$m->meta_key}: {$m->meta_value}\n";
    }

    // Get external IDs to look up
    $external_business_id = get_post_meta($business->ID, 'external_business_id', true);
    $external_user_id = get_post_meta($business->ID, 'external_user_id', true);

    if ($external_business_id) {
        echo "\n4. Looking up source data for business {$external_business_id}:\n";

        // Parse SQL file to find this business
        $file = ABSPATH . 'portal-files/nmda_business.sql';
        if (file_exists($file)) {
            $sql = file_get_contents($file);
            // Find the business by ID
            if (preg_match("/'$external_business_id'[^)]+/", $sql, $matches)) {
                $data = $matches[0];
                // Extract CompanyId and UserId from the row
                echo "   Found business data snippet:\n";
                echo "   " . substr($data, 0, 200) . "...\n";

                // Try to extract specific fields
                if (preg_match_all(/'([^']+)'|NULL/", $data, $fields)) {
                    echo "\n   First 10 fields:\n";
                    for ($i = 0; $i < min(10, count($fields[0])); $i++) {
                        echo "   [$i]: {$fields[0][$i]}\n";
                    }
                }
            }
        }
    }

    // Check if the related IDs exist in mapping
    $related_company_meta = get_post_meta($business->ID, 'related_company', true);
    $owner_user_meta = get_post_meta($business->ID, 'owner_wp_user', true);

    echo "\n5. Checking stored relationship values:\n";
    echo "   related_company meta value: " . ($related_company_meta ?: 'EMPTY') . "\n";
    echo "   owner_wp_user meta value: " . ($owner_user_meta ?: 'EMPTY') . "\n";

    if ($related_company_meta && $related_company_meta != '0') {
        $company = get_post($related_company_meta);
        if ($company) {
            echo "   ✓ Related company exists: {$company->post_title}\n";
        } else {
            echo "   ✗ Related company ID {$related_company_meta} NOT FOUND in database\n";
        }
    } else {
        echo "   ✗ No related company set (value is 0 or empty)\n";

        // Check if CompanyId exists in source data
        if ($external_user_id) {
            // Look up what company this user was associated with
            $user_company = $wpdb->get_var($wpdb->prepare("
                SELECT meta_value
                FROM {$wpdb->usermeta}
                WHERE meta_key = '_legacy_company_id'
                AND user_id = (
                    SELECT new_id
                    FROM $mapping_table
                    WHERE old_id = %s
                    AND entity_type = 'user'
                )
            ", $external_user_id));

            if ($user_company) {
                echo "   ℹ User's legacy company ID: {$user_company}\n";

                // Check if that company exists
                $company_post = $wpdb->get_var($wpdb->prepare("
                    SELECT new_id
                    FROM $mapping_table
                    WHERE old_id = %s
                    AND entity_type = 'company'
                ", $user_company));

                if ($company_post) {
                    echo "   ℹ Company post ID should be: {$company_post}\n";
                    echo "   ⚠ THIS IS THE PROBLEM: Company ID not being set correctly!\n";
                } else {
                    echo "   ✗ Company with legacy ID {$user_company} was not imported\n";
                }
            }
        }
    }

    if ($owner_user_meta && $owner_user_meta != '0') {
        $user = get_user_by('id', $owner_user_meta);
        if ($user) {
            echo "   ✓ Owner user exists: {$user->display_name}\n";
        } else {
            echo "   ✗ Owner user ID {$owner_user_meta} NOT FOUND in database\n";
        }
    } else {
        echo "   ✗ No owner user set (value is 0 or empty)\n";
    }
}

echo "\n6. Summary of Issues:\n";
echo "   - Check if CompanyId and UserId fields exist in SQL data\n";
echo "   - Check if mapping lookups are working correctly\n";
echo "   - Check if meta is being saved (0 means mapping failed)\n";

echo "\n=== End Diagnostics ===\n";
