<?php
/**
 * NMDA ACF Field Groups
 * Programmatically register ACF field groups for business post type
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register ACF field groups on init
 */
function nmda_register_acf_field_groups() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    /**
     * Field Group: Business Information
     */
    acf_add_local_field_group( array(
        'key'      => 'group_business_info',
        'title'    => 'Business Information',
        'fields'   => array(
            array(
                'key'   => 'field_dba',
                'label' => 'DBA (Doing Business As)',
                'name'  => 'dba',
                'type'  => 'text',
            ),
            array(
                'key'           => 'field_business_phone',
                'label'         => 'Business Phone',
                'name'          => 'business_phone',
                'type'          => 'text',
                'required'      => 1,
                'placeholder'   => '505-555-5555',
            ),
            array(
                'key'      => 'field_business_email',
                'label'    => 'Business Email',
                'name'     => 'business_email',
                'type'     => 'email',
                'required' => 1,
            ),
            array(
                'key'         => 'field_website',
                'label'       => 'Website',
                'name'        => 'website',
                'type'        => 'url',
                'placeholder' => 'https://example.com',
            ),
            array(
                'key'          => 'field_business_profile',
                'label'        => 'Business Profile',
                'name'         => 'business_profile',
                'type'         => 'wysiwyg',
                'instructions' => 'Provide a brief description of your business (will be displayed publicly)',
                'required'     => 1,
                'tabs'         => 'visual',
                'toolbar'      => 'basic',
                'media_upload' => 0,
            ),
            array(
                'key'          => 'field_business_hours',
                'label'        => 'Business Hours',
                'name'         => 'business_hours',
                'type'         => 'textarea',
                'instructions' => 'If by appointment only, include contact info',
                'rows'         => 5,
                'placeholder'  => "Monday-Friday: 8:00 AM - 5:00 PM\nSaturday: 9:00 AM - 4:00 PM\nSunday: Closed",
            ),
            array(
                'key'          => 'field_num_employees',
                'label'        => 'Number of Employees',
                'name'         => 'num_employees',
                'type'         => 'number',
                'instructions' => 'Approximate number (for NMDA records)',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'nmda_business',
                ),
            ),
        ),
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
    ) );

    /**
     * Field Group: Business Classification
     */
    acf_add_local_field_group( array(
        'key'      => 'group_classification',
        'title'    => 'Logo Program Classification',
        'fields'   => array(
            array(
                'key'          => 'field_classification',
                'label'        => 'Select Classification(s)',
                'name'         => 'classification',
                'type'         => 'checkbox',
                'required'     => 1,
                'instructions' => 'Select all that apply to your business',
                'choices'      => array(
                    'grown'     => 'NEW MEXICO – Grown with Tradition® (Farmers/Ranchers)',
                    'taste'     => 'NEW MEXICO – Taste the Tradition® (Food/Beverage Manufacturers)',
                    'associate' => 'NEW MEXICO – Associate Member (Retailers, Restaurants, Agritourism)',
                ),
                'layout'       => 'vertical',
            ),
            array(
                'key'               => 'field_associate_type',
                'label'             => 'Associate Member Type',
                'name'              => 'associate_type',
                'type'              => 'checkbox',
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'field_classification',
                            'operator' => '==',
                            'value'    => 'associate',
                        ),
                    ),
                ),
                'choices'           => array(
                    'in_person'    => 'In-Person Retail (sells 3+ Taste/Grown products)',
                    'online'       => 'Online Retail',
                    'restaurant'   => 'Restaurant (serves NM ingredients)',
                    'tourism'      => 'Agritourism Operation',
                    'artisan'      => 'Artisan/Crafted Products',
                    'pet'          => 'Pet Food Manufacturer',
                    'educational'  => 'Educational Organization',
                    'non_profit'   => 'Non-Profit Organization',
                    'other'        => 'Other',
                ),
                'layout'            => 'vertical',
            ),
            array(
                'key'               => 'field_associate_other_text',
                'label'             => 'Other Associate Type (please describe)',
                'name'              => 'associate_other_text',
                'type'              => 'text',
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'field_associate_type',
                            'operator' => '==',
                            'value'    => 'other',
                        ),
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'nmda_business',
                ),
            ),
        ),
        'position' => 'normal',
    ) );

    /**
     * Field Group: Social Media
     */
    acf_add_local_field_group( array(
        'key'      => 'group_social_media',
        'title'    => 'Social Media',
        'fields'   => array(
            array(
                'key'         => 'field_facebook',
                'label'       => 'Facebook Handle',
                'name'        => 'facebook',
                'type'        => 'text',
                'placeholder' => '@nmtastethetradition',
                'prepend'     => '@',
            ),
            array(
                'key'         => 'field_instagram',
                'label'       => 'Instagram Handle',
                'name'        => 'instagram',
                'type'        => 'text',
                'placeholder' => '@nmtastethetradition',
                'prepend'     => '@',
            ),
            array(
                'key'         => 'field_twitter',
                'label'       => 'Twitter/X Handle',
                'name'        => 'twitter',
                'type'        => 'text',
                'placeholder' => '@TasteNewMexico',
                'prepend'     => '@',
            ),
            array(
                'key'         => 'field_pinterest',
                'label'       => 'Pinterest Handle',
                'name'        => 'pinterest',
                'type'        => 'text',
                'placeholder' => '@nmtastethetradition',
                'prepend'     => '@',
            ),
            array(
                'key'   => 'field_social_other',
                'label' => 'Other Social Media',
                'name'  => 'social_other',
                'type'  => 'text',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'nmda_business',
                ),
            ),
        ),
        'position' => 'side',
    ) );

    /**
     * Field Group: Sales & Distribution
     */
    acf_add_local_field_group( array(
        'key'      => 'group_sales',
        'title'    => 'Sales & Distribution',
        'fields'   => array(
            array(
                'key'          => 'field_sales_type',
                'label'        => 'Type of Sales',
                'name'         => 'sales_type',
                'type'         => 'checkbox',
                'instructions' => 'Select all that apply',
                'choices'      => array(
                    'local'        => 'Local',
                    'regional'     => 'Regional',
                    'in_state'     => 'In-State',
                    'national'     => 'National',
                    'international' => 'International',
                ),
                'layout'       => 'vertical',
            ),
            array(
                'key'          => 'field_additional_info',
                'label'        => 'Additional Information',
                'name'         => 'additional_info',
                'type'         => 'textarea',
                'instructions' => 'Additional details about your operation (for NMDA records only)',
                'rows'         => 4,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'nmda_business',
                ),
            ),
        ),
        'position' => 'normal',
    ) );

    /**
     * Field Group: Owner/Primary Contact (Private)
     */
    acf_add_local_field_group( array(
        'key'      => 'group_owner_contact',
        'title'    => 'Owner/Primary Contact (Private)',
        'fields'   => array(
            array(
                'key'   => 'field_owner_first_name',
                'label' => 'Owner/Officer First Name',
                'name'  => 'owner_first_name',
                'type'  => 'text',
            ),
            array(
                'key'   => 'field_owner_last_name',
                'label' => 'Owner/Officer Last Name',
                'name'  => 'owner_last_name',
                'type'  => 'text',
            ),
            array(
                'key'               => 'field_is_primary_contact',
                'label'             => 'Is this person the primary contact?',
                'name'              => 'is_primary_contact',
                'type'              => 'true_false',
                'message'           => 'Yes, this person is the primary contact',
                'default_value'     => 1,
            ),
            array(
                'key'               => 'field_contact_first_name',
                'label'             => 'Primary Contact First Name',
                'name'              => 'contact_first_name',
                'type'              => 'text',
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'field_is_primary_contact',
                            'operator' => '!=',
                            'value'    => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key'               => 'field_contact_last_name',
                'label'             => 'Primary Contact Last Name',
                'name'              => 'contact_last_name',
                'type'              => 'text',
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'field_is_primary_contact',
                            'operator' => '!=',
                            'value'    => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key'         => 'field_contact_phone',
                'label'       => 'Contact Phone',
                'name'        => 'contact_phone',
                'type'        => 'text',
                'placeholder' => '505-555-5555',
            ),
            array(
                'key'   => 'field_contact_email',
                'label' => 'Contact Email',
                'name'  => 'contact_email',
                'type'  => 'email',
            ),
            array(
                'key'   => 'field_contact_address',
                'label' => 'Mailing Address',
                'name'  => 'contact_address',
                'type'  => 'text',
            ),
            array(
                'key'   => 'field_contact_address_2',
                'label' => 'Mailing Address Line 2',
                'name'  => 'contact_address_2',
                'type'  => 'text',
            ),
            array(
                'key'   => 'field_contact_city',
                'label' => 'City',
                'name'  => 'contact_city',
                'type'  => 'text',
            ),
            array(
                'key'   => 'field_contact_state',
                'label' => 'State',
                'name'  => 'contact_state',
                'type'  => 'text',
            ),
            array(
                'key'   => 'field_contact_zip',
                'label' => 'Zip Code',
                'name'  => 'contact_zip',
                'type'  => 'text',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'nmda_business',
                ),
            ),
        ),
        'position' => 'normal',
    ) );

    /**
     * Field Group: Primary Business Address
     */
    acf_add_local_field_group( array(
        'key'      => 'group_primary_address',
        'title'    => 'Primary Business Address',
        'fields'   => array(
            array(
                'key'          => 'field_primary_address',
                'label'        => 'Business Physical Address',
                'name'         => 'primary_address',
                'type'         => 'text',
                'required'     => 1,
                'instructions' => 'This will be the primary address displayed on the website if open to the public',
            ),
            array(
                'key'   => 'field_primary_address_2',
                'label' => 'Address Line 2',
                'name'  => 'primary_address_2',
                'type'  => 'text',
            ),
            array(
                'key'      => 'field_primary_city',
                'label'    => 'City',
                'name'     => 'primary_city',
                'type'     => 'text',
                'required' => 1,
            ),
            array(
                'key'           => 'field_primary_state',
                'label'         => 'State',
                'name'          => 'primary_state',
                'type'          => 'text',
                'required'      => 1,
                'default_value' => 'NM',
            ),
            array(
                'key'      => 'field_primary_zip',
                'label'    => 'Zip Code',
                'name'     => 'primary_zip',
                'type'     => 'text',
                'required' => 1,
            ),
            array(
                'key'           => 'field_primary_address_type',
                'label'         => 'Address Type',
                'name'          => 'primary_address_type',
                'type'          => 'select',
                'required'      => 1,
                'choices'       => array(
                    'public_hours'      => 'Open to the public during regular business hours',
                    'public_reservation' => 'Open to the public with a reservation',
                    'not_public'        => 'Not open to the public (doesn\'t appear on website)',
                    'other'             => 'Other',
                ),
                'default_value' => 'public_hours',
            ),
            array(
                'key'               => 'field_reservation_instructions',
                'label'             => 'Reservation Instructions',
                'name'              => 'reservation_instructions',
                'type'              => 'textarea',
                'rows'              => 3,
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'field_primary_address_type',
                            'operator' => '==',
                            'value'    => 'public_reservation',
                        ),
                    ),
                ),
                'placeholder'       => 'Call 505-555-5555 or email info@business.com',
            ),
            array(
                'key'               => 'field_other_instructions',
                'label'             => 'Other Instructions',
                'name'              => 'other_instructions',
                'type'              => 'textarea',
                'rows'              => 3,
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'field_primary_address_type',
                            'operator' => '==',
                            'value'    => 'other',
                        ),
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'nmda_business',
                ),
            ),
        ),
        'position' => 'normal',
    ) );

    /**
     * Field Group: Administrative
     */
    acf_add_local_field_group( array(
        'key'      => 'group_admin',
        'title'    => 'Administrative (NMDA Only)',
        'fields'   => array(
            array(
                'key'          => 'field_approval_status',
                'label'        => 'Approval Status',
                'name'         => 'approval_status',
                'type'         => 'select',
                'choices'      => array(
                    'pending'  => 'Pending Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'changes_requested' => 'Changes Requested',
                ),
                'default_value' => 'pending',
            ),
            array(
                'key'   => 'field_admin_notes',
                'label' => 'Admin Notes',
                'name'  => 'admin_notes',
                'type'  => 'textarea',
                'rows'  => 5,
            ),
            array(
                'key'   => 'field_approval_date',
                'label' => 'Approval Date',
                'name'  => 'approval_date',
                'type'  => 'date_picker',
                'display_format' => 'm/d/Y',
                'return_format'  => 'Y-m-d',
            ),
            array(
                'key'   => 'field_approved_by',
                'label' => 'Approved By',
                'name'  => 'approved_by',
                'type'  => 'user',
                'role'  => array( 'administrator', 'editor' ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'nmda_business',
                ),
            ),
        ),
        'position' => 'side',
    ) );
}
add_action( 'acf/init', 'nmda_register_acf_field_groups' );
