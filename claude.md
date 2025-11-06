# NMDA Portal WordPress Development Instructions

## Project Overview

You are building a WordPress-based portal for the New Mexico Department of Agriculture (NMDA) to manage agricultural business directories, user accounts, and reimbursement programs. This replaces a Node.js/Express application with a custom WordPress solution.

## Technology Stack

- **CMS**: WordPress (latest stable version)
- **Theme Framework**: Understrap (https://understrap.com/) - Create a child theme nmda-understrap
- **Database**: MySQL with custom tables for complex relationships
- **Languages**: PHP 7.4+, JavaScript (ES6+), HTML5, CSS3/SASS
- **APIs**: WordPress REST API, third-party email service API (Mailchimp/ActiveCampaign)

## Brand Guidelines

```css
/* Primary Brand Colors */
--nmda-brown-dark: #512c1d;
--nmda-red: #8b0c12;
--nmda-brown-darker: #330d0f;
--nmda-gold: #f5be29;
```

## Original Portal Reference Files

In WordPress root directory in portal-files directory

## Project Structure

```
/wp-content/
├── themes/
│   └── nmda-understrap/
│       ├── functions.php
│       ├── inc/
│       │   ├── setup.php
│       │   ├── custom-post-types.php
│       │   ├── user-management.php
│       │   ├── business-management.php
│       │   ├── reimbursements.php
│       │   └── api-integrations.php
│       └── template-parts/
│           ├── dashboard/
│           ├── forms/
│           └── listings/
└── plugins/
    └── nmda-portal/
        ├── nmda-portal.php
        ├── includes/
        ├── admin/
        └── public/
```

## Core Development Tasks

### Phase 1: Database & Architecture Setup

#### Task 1.1: Create Custom Database Tables

Create the following tables with proper relationships:

```sql
-- User-Business relationship table
wp_nmda_user_business (
    id INT AUTO_INCREMENT,
    user_id BIGINT(20),
    business_id BIGINT(20),
    role VARCHAR(50), -- 'owner', 'manager', 'viewer'
    status VARCHAR(50), -- 'active', 'pending', 'disabled'
    invited_by BIGINT(20),
    invited_date DATETIME,
    accepted_date DATETIME
)

-- Business addresses (multiple per business)
wp_nmda_business_addresses (
    id INT AUTO_INCREMENT,
    business_id BIGINT(20),
    address_type VARCHAR(100),
    address_name VARCHAR(250),
    [address fields...]
)

-- Reimbursement tables (lead, advertising, labels)
wp_nmda_reimbursements (
    id INT AUTO_INCREMENT,
    business_id BIGINT(20),
    user_id BIGINT(20),
    type VARCHAR(50), -- 'lead', 'advertising', 'labels'
    status VARCHAR(50), -- 'submitted', 'pending', 'approved', 'rejected'
    fiscal_year VARCHAR(10),
    data LONGTEXT, -- JSON encoded form data
    documents TEXT, -- JSON array of document IDs
    admin_notes TEXT,
    created_at DATETIME,
    updated_at DATETIME
)

-- Communication log
wp_nmda_communications (
    id INT AUTO_INCREMENT,
    business_id BIGINT(20),
    user_id BIGINT(20),
    admin_id BIGINT(20),
    type VARCHAR(50), -- 'application', 'reimbursement', 'general'
    message TEXT,
    attachments TEXT,
    read_status BOOLEAN,
    created_at DATETIME
)

-- Field permissions
wp_nmda_field_permissions (
    field_name VARCHAR(100),
    requires_approval BOOLEAN,
    user_editable BOOLEAN
)
```

#### Task 1.2: Create Migration Script

Build a migration script that:

1. Connects to the old MySQL database (AWS RDS)
2. Maps UUID primary keys to WordPress integer IDs
3. Preserves all relationships and historical data
4. Handles the 100+ boolean product type fields efficiently

### Phase 2: User & Business Account Management

#### Task 2.1: User Registration & Login

```php
// Custom registration flow
function nmda_custom_registration() {
    // 1. Create WordPress user account
    // 2. Send welcome email
    // 3. Redirect to business application form
    // 4. Store user meta for NMDA-specific data
}

// Handle multiple business associations
function nmda_user_can_access_business($user_id, $business_id) {
    // Check wp_nmda_user_business table
    // Verify role and status
    // Return boolean
}
```

#### Task 2.2: Business Profile Management

Implement Custom Post Type 'nmda_business' with:

- Multiple users per business capability
- User invitation system
- Role-based permissions (owner, manager, viewer)
- Status tracking for invited users

#### Task 2.3: User Invitation System

```php
// Send invitation email
function nmda_invite_user_to_business($business_id, $email, $role) {
    // 1. Check if user exists
    // 2. Create pending invitation record
    // 3. Send invitation email with unique link
    // 4. Log invitation in communications table
}

// Accept invitation
function nmda_accept_invitation($invitation_token) {
    // 1. Verify token
    // 2. Create/update user account
    // 3. Associate user with business
    // 4. Update status to 'active'
}
```

### Phase 3: Application & Onboarding Workflow

#### Task 3.1: Application Form

Create multi-step form with:

- Business information fields (client to provide final list)
- Document upload capability (non-blocking)
- Save draft functionality
- Progress indicator

```javascript
// Form handler with document management
class NMDAApplicationForm {
  constructor() {
    this.documents = [];
    this.requiredDocs = ["business_license", "tax_certificate"];
    this.optionalDocs = ["product_labels", "invoices"];
  }

  submitApplication() {
    // Allow submission even without all documents
    // Flag missing documents for admin view
    // Send confirmation emails
  }
}
```

#### Task 3.2: Admin Review Dashboard

Build admin interface with:

- Pending applications queue
- Document status indicators
- In-portal messaging system
- Approval/rejection workflow
- Bulk actions support

#### Task 3.3: Communication Module

```php
// In-portal messaging
function nmda_send_message($from_id, $to_id, $business_id, $message, $type) {
    // Store in wp_nmda_communications
    // Send email notification
    // Create dashboard notification
    // Return message ID
}

// Message thread view
function nmda_get_communication_thread($business_id, $user_id) {
    // Retrieve all messages for business/user combination
    // Mark as read
    // Return formatted thread
}
```

### Phase 4: Member Dashboard & Profile Management

#### Task 4.1: Dashboard Redesign

Create intuitive dashboard with:

- Business profile overview
- Quick actions menu
- Notification center
- Document library
- Reimbursement status tracker

#### Task 4.2: Profile Editing System

```php
// Field-level permission checking
function nmda_can_edit_field($field_name, $user_role) {
    // Check wp_nmda_field_permissions table
    // Return edit capability
}

// Handle field updates
function nmda_update_business_field($business_id, $field_name, $value) {
    // Check if field requires approval
    // If yes, create pending change record
    // If no, update immediately
    // Log change in audit trail
}
```

#### Task 4.3: Change Approval System

For fields requiring approval:

1. Store pending changes separately
2. Notify admins of pending changes
3. Show comparison view (current vs proposed)
4. Apply changes upon approval

### Phase 5: Cost-Share Reimbursement Module

#### Task 5.1: Reimbursement Forms

Create three standardized forms:

- Lead Generation Reimbursement
- Advertising Reimbursement
- Labels Reimbursement

```php
// Reimbursement submission
function nmda_submit_reimbursement($type, $data) {
    // Validate business eligibility
    // Check fiscal year limits
    // Store in database
    // Send notifications
    // Return confirmation
}
```

#### Task 5.2: Admin Management Interface

Build interface with:

- Filterable lists by status and fiscal year
- Bulk approval/rejection
- Export capabilities
- Archived view for rejected requests
- Fiscal year organization

### Phase 6: Resource Center

#### Task 6.1: Member-Only Resources

```php
// Resource access control
function nmda_resource_center_shortcode() {
    if (!nmda_is_approved_member()) {
        return 'Please log in to access resources.';
    }

    // Display categorized resources
    // Track downloads
    // Show usage analytics
}
```

Resource types to support:

- Logo files (multiple formats)
- Program guidelines (PDFs)
- Forms and templates
- Important links
- Training materials

### Phase 7: Email Integration

#### Task 7.1: Third-Party Email Service Integration

```php
// Mailchimp/ActiveCampaign API integration
class NMDA_Email_Integration {
    private $api_key;
    private $list_id;

    function sync_member($user_id, $business_id, $action) {
        // Actions: 'add', 'update', 'remove'
        // Map WordPress data to email service fields
        // Update tags/segments based on business category
        // Handle API errors gracefully
    }

    function bulk_sync() {
        // Sync all approved members
        // Update segments
        // Clean removed members
    }
}
```

### Phase 8: Analytics & Tracking

#### Task 8.1: Analytics Implementation

Add tracking for:

- User login frequency
- Profile update patterns
- Document downloads
- Resource usage
- Reimbursement submissions
- Communication metrics

```javascript
// Google Analytics 4 integration
gtag("event", "nmda_action", {
  category: "member_activity",
  action: action_type,
  label: business_id,
  value: value,
});
```

### Phase 9: Data Migration

#### Task 9.1: Migration Script Development

```php
// Main migration controller
class NMDA_Data_Migration {
    function migrate_users() {
        // Map UUID to WordPress user IDs
        // Preserve all user metadata
        // Maintain relationships
    }

    function migrate_businesses() {
        // Convert to Custom Post Types
        // Handle 100+ boolean fields efficiently
        // Preserve addresses and categories
    }

    function migrate_reimbursements() {
        // Import historical reimbursement data
        // Maintain approval status
        // Preserve fiscal year data
    }

    function validate_migration() {
        // Count comparisons
        // Relationship verification
        // Data integrity checks
    }
}
```

## Development Guidelines

### Code Standards

- Follow WordPress Coding Standards
- Use proper sanitization and validation
- Implement nonce verification for all forms
- Escape all output
- Use prepared statements for database queries

### Performance Optimization

- Implement caching for complex queries
- Use AJAX for form submissions
- Lazy load dashboard components
- Optimize database indexes
- Minimize external API calls

### Security Requirements

- Implement role-based access control
- Sanitize all user inputs
- Use WordPress nonce for CSRF protection
- Secure file upload handling
- Regular security audits

### Testing Requirements

- Unit tests for critical functions
- Integration tests for workflows
- User acceptance testing scripts
- Performance benchmarking
- Cross-browser compatibility

## Deployment Checklist

### Pre-Launch

- [ ] Complete data migration
- [ ] Verify all user accounts
- [ ] Test email notifications
- [ ] Validate business profiles
- [ ] Check reimbursement workflows
- [ ] Test document uploads
- [ ] Verify API integrations
- [ ] Complete security audit

### Launch Day

- [ ] Final database backup
- [ ] DNS configuration
- [ ] SSL certificate installation
- [ ] Monitor error logs
- [ ] Test critical paths
- [ ] Verify email delivery

### Post-Launch

- [ ] Monitor performance metrics
- [ ] Gather user feedback
- [ ] Address immediate issues
- [ ] Schedule training sessions
- [ ] Document known issues

## Common Issues & Solutions

### Issue: Multiple users editing same business

**Solution**: Implement optimistic locking with timestamp checking

### Issue: Large file uploads timing out

**Solution**: Implement chunked upload with progress indicator

### Issue: Email delivery failures

**Solution**: Implement queue system with retry logic

### Issue: Slow dashboard loading

**Solution**: Implement pagination and lazy loading

## Additional Resources

- WordPress Codex: https://codex.wordpress.org/
- Understrap Documentation: https://understrap.com/docs/
- WordPress REST API: https://developer.wordpress.org/rest-api/
- Mailchimp API: https://mailchimp.com/developer/
- ActiveCampaign API: https://developers.activecampaign.com/

## Support & Maintenance

### Regular Maintenance Tasks

- Database optimization (monthly)
- Security updates (as released)
- Backup verification (weekly)
- Performance monitoring (continuous)
- User activity reports (monthly)

### Emergency Procedures

1. Database corruption: Restore from backup
2. Security breach: Immediate lockdown and audit
3. API failures: Fallback to manual processes
4. Performance issues: Scale resources temporarily

## Project Timeline Estimates

- **Phase 1-2**: Database & User Management (2 weeks)
- **Phase 3**: Application Workflow (1.5 weeks)
- **Phase 4**: Member Dashboard (1.5 weeks)
- **Phase 5**: Reimbursement Module (1 week)
- **Phase 6-7**: Resources & Email (1 week)
- **Phase 8**: Analytics (3 days)
- **Phase 9**: Data Migration (1 week)
- **Testing & Launch**: (1 week)

**Total Estimated Timeline**: 9-10 weeks

## Questions for Client

1. Final list of application form fields?
2. Specific email service preference (Mailchimp vs ActiveCampaign)?
3. Google Analytics account details?
4. Preferred file size limits for uploads?
5. Specific fiscal year cutoff dates?
6. Admin user list for initial setup?
7. Training requirements for staff?
