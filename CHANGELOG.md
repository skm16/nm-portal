# NMDA Portal Development Changelog

All notable changes to the NMDA Portal WordPress project will be documented in this file.

## [Unreleased]

### 2025-11-06

#### Added - Phase 1: Database & Architecture Setup ✓

**Child Theme Structure**
- Created Understrap child theme (nmda-understrap) with complete directory structure
- Added `style.css` with NMDA brand colors and base styling
- Created `functions.php` as main theme controller with enqueue functions
- Set up `assets/css/` and `assets/js/` directories for custom resources

**Core PHP Modules** (`/inc/` directory)
- `setup.php` - Theme configuration, custom image sizes, widget areas, login customization
- `database-schema.php` - Custom database table creation and management functions:
  - `wp_nmda_user_business` - User-business relationships with roles
  - `wp_nmda_business_addresses` - Multiple addresses per business
  - `wp_nmda_reimbursements` - Cost-share reimbursement tracking
  - `wp_nmda_communications` - In-portal messaging system
  - `wp_nmda_field_permissions` - Field-level edit permissions
- `custom-post-types.php` - Custom post types and taxonomies:
  - `nmda_business` post type with business_category and product_type taxonomies
  - `nmda_resource` post type with resource_category taxonomy
- `user-management.php` - User and permission functions:
  - Custom registration workflow
  - Multi-business user associations
  - Role-based access control (owner, manager, viewer)
  - User invitation system
- `business-management.php` - Business profile management:
  - User invitation and acceptance workflow
  - Address management (multiple addresses per business)
  - Field-level edit permissions and approval workflow
  - Communication logging
- `reimbursements.php` - Cost-share reimbursement system:
  - Three reimbursement types (lead, advertising, labels)
  - Submission, approval, and rejection workflows
  - Fiscal year tracking and limits
  - Email notifications
- `api-integrations.php` - Third-party API integrations:
  - Mailchimp integration class
  - ActiveCampaign integration class
  - Automatic sync on business publish and user addition
  - Bulk sync functionality

**Custom Assets**
- `assets/css/custom.css` - Comprehensive styling for:
  - Dashboard cards and quick actions
  - Business profile pages
  - Multi-step reimbursement forms with progress indicators
  - Document upload areas with drag-and-drop
  - Status badges and notifications
  - Responsive design for mobile devices
- `assets/js/custom.js` - Interactive functionality:
  - Multi-step form handler class
  - Document upload handler with drag-and-drop
  - AJAX form submission
  - Editable field functionality
  - Notification center with unread badges

**Database Schema**
- Automated table creation on theme activation
- Default field permissions populated on first run
- Helper functions for table name retrieval
- Proper indexes for query optimization

**Features Implemented**
- Role-based access control system
- Multi-user business management
- Field-level permissions with approval workflow
- Communication/messaging infrastructure
- Document management foundation
- Email service integration framework
- AJAX-powered user interface
- Responsive design system

**WordPress Integration**
- Custom user roles (Business Owner, Business Manager)
- Custom navigation menus (Member Dashboard, Member Footer)
- Featured image support for business logos
- Custom login page branding
- Admin bar customization for non-admins
