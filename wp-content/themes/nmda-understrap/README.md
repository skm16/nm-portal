# NMDA Understrap Child Theme

Child theme for the New Mexico Department of Agriculture (NMDA) Portal, built on the Understrap framework.

## Overview

This custom WordPress theme provides a complete business directory and member portal solution for NMDA, including:

- Multi-user business profile management
- Cost-share reimbursement system (Lead Generation, Advertising, Labels)
- Role-based access control
- In-portal messaging system
- Member-only resource center
- Email service integration (Mailchimp/ActiveCampaign)

## Installation

1. Ensure parent theme **Understrap** is installed and activated
2. Upload this theme to `/wp-content/themes/nmda-understrap/`
3. Activate the theme in WordPress admin
4. Custom database tables will be created automatically on activation

## Directory Structure

```
nmda-understrap/
├── functions.php           # Main theme functions and module loader
├── style.css              # Theme stylesheet with NMDA branding
├── README.md              # This file
├── inc/                   # Core PHP modules
│   ├── setup.php          # Theme configuration and setup
│   ├── database-schema.php # Custom database tables
│   ├── custom-post-types.php # Business and Resource CPTs
│   ├── user-management.php # User roles and permissions
│   ├── business-management.php # Business profile functions
│   ├── reimbursements.php # Reimbursement system
│   └── api-integrations.php # Email service APIs
├── assets/
│   ├── css/
│   │   └── custom.css     # Custom styles
│   └── js/
│       └── custom.js      # Custom JavaScript
└── template-parts/        # Template components (to be developed)
    ├── dashboard/
    ├── forms/
    └── listings/
```

## Custom Database Tables

The theme creates five custom tables:

### wp_nmda_user_business
Manages many-to-many relationships between users and businesses with roles (owner, manager, viewer).

### wp_nmda_business_addresses
Stores multiple addresses per business (physical, mailing, billing, etc.).

### wp_nmda_reimbursements
Tracks cost-share reimbursement requests with status workflow and fiscal year limits.

### wp_nmda_communications
In-portal messaging system for business-admin communication.

### wp_nmda_field_permissions
Controls which fields require admin approval and user edit permissions.

## Custom Post Types

### nmda_business
Primary post type for member businesses with:
- Custom taxonomies: `business_category`, `product_type`
- Featured image support for business logos
- Public directory listing
- Member dashboard management

### nmda_resource
Member-only resources and downloads with:
- Custom taxonomy: `resource_category`
- Access control for approved members
- Download tracking capability

## User Roles

### Business Owner
- Full access to associated business profiles
- Can invite and manage other users
- Submit reimbursement requests
- Edit business information (subject to approval workflow)

### Business Manager
- Manage business information
- Submit reimbursement requests
- Cannot invite new users

### Viewer
- View business information only
- No edit permissions

## Key Features

### Multi-Step Forms
JavaScript class for handling complex multi-step forms with validation and draft saving.

### Document Upload
Drag-and-drop file upload with AJAX processing and validation.

### Field-Level Permissions
Granular control over which fields users can edit and which require admin approval.

### Email Integration
Automatic synchronization with Mailchimp or ActiveCampaign when:
- Business is published/approved
- User is added to business
- Manual bulk sync available

### Reimbursement System
Three reimbursement types with:
- Per-business fiscal year limits
- Multi-step submission forms
- Document attachment support
- Admin review and approval workflow
- Email notifications at each status change

## Configuration

### Email Service Setup
1. Go to Theme Settings (to be created in admin panel)
2. Select email service (Mailchimp or ActiveCampaign)
3. Enter API credentials
4. Enter list/audience ID
5. Test connection

### Field Permissions
Edit field permissions in the database or via admin interface (to be developed):
```php
// Example: Make business name require approval
UPDATE wp_nmda_field_permissions
SET requires_approval = 1
WHERE field_name = 'business_name';
```

## Important Functions

### User Management
```php
// Check if user can access business
nmda_user_can_access_business($user_id, $business_id);

// Get user's role for business
nmda_get_user_business_role($user_id, $business_id);

// Get all businesses for user
nmda_get_user_businesses($user_id, $status);

// Invite user to business
nmda_invite_user_to_business($business_id, $email, $role, $invited_by);
```

### Business Management
```php
// Get business addresses
nmda_get_business_addresses($business_id, $address_type);

// Add business address
nmda_add_business_address($business_id, $address_data);

// Update business field with approval workflow
nmda_update_business_field($business_id, $field_name, $value, $user_id);
```

### Reimbursements
```php
// Submit reimbursement
nmda_submit_reimbursement($type, $data);

// Approve reimbursement
nmda_approve_reimbursement($reimbursement_id, $amount_approved, $admin_id, $notes);

// Get business reimbursements
nmda_get_business_reimbursements($business_id, $args);
```

### Email Integration
```php
// Get integration instance
$integration = nmda_get_email_integration();

// Sync single member
$integration->sync_member($user_id, $business_id, 'add');

// Bulk sync all members
$results = $integration->bulk_sync();
```

## AJAX Actions

The following AJAX actions are handled by `custom.js`:

- `nmda_save_draft` - Save form draft
- `nmda_upload_document` - Upload document file
- `nmda_update_field` - Update editable field
- `nmda_mark_notification_read` - Mark notification as read

## Brand Colors

```css
--nmda-brown-dark: #512c1d;    /* Primary brand color */
--nmda-red: #8b0c12;           /* Secondary/accent color */
--nmda-brown-darker: #330d0f;  /* Dark text color */
--nmda-gold: #f5be29;          /* Accent/highlight color */
```

## Dependencies

- **Parent Theme**: Understrap
- **Plugins Required**:
  - Advanced Custom Fields PRO (for additional field management)
  - Gravity Forms (for complex forms, optional)

- **PHP**: 7.4+
- **WordPress**: 5.8+
- **jQuery**: Included with WordPress

## Development Roadmap

### Phase 2: Templates & UI (Next)
- [ ] Dashboard template
- [ ] Business profile page template
- [ ] Application form templates
- [ ] Reimbursement form templates
- [ ] Admin review interfaces

### Phase 3: Advanced Features
- [ ] Admin settings page
- [ ] Bulk user management
- [ ] Advanced reporting
- [ ] Export functionality
- [ ] Data migration tools

### Phase 4: Integration & Testing
- [ ] Complete email integration testing
- [ ] Security audit
- [ ] Performance optimization
- [ ] User acceptance testing
- [ ] Documentation completion

## Support

For issues, questions, or feature requests, please refer to the main project CLAUDE.md file or contact the development team.

## Version History

See [CHANGELOG.md](../../../../CHANGELOG.md) in the WordPress root directory.

## License

This theme is proprietary software developed for the New Mexico Department of Agriculture.
