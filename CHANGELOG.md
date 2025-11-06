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

---

#### Analysis - Phase 2 Planning: Existing Portal Review ✓

**Documentation Created**
- `PHASE2_ANALYSIS.md` - Comprehensive analysis document (94KB, 11 sections)

**Analysis Completed:**

1. **Registration & Application Form** (1,077 lines analyzed)
   - 7 major sections identified
   - Public vs private field designation
   - Social media integration fields
   - 3 main business classifications
   - Dynamic additional address system
   - Conditional form logic documented

2. **Database Schema Review**
   - Business table: 250+ fields analyzed
   - **202 boolean product type fields** identified
   - 3 reimbursement table structures documented
   - UUID to WordPress ID mapping strategy defined
   - Relationship tables analyzed

3. **API Endpoints** (27 endpoints documented)
   - Authentication endpoints (5)
   - Business/Company endpoints (10)
   - Reimbursement endpoints (12)
   - JWT authentication with RSA keys
   - Rate limiting implementation

4. **Product Taxonomy Strategy**
   - Hierarchical product categories defined
   - Organic/conventional sub-classifications
   - Meat product types and preparations
   - Beverage and prepared food categories
   - Taxonomy vs meta field approach evaluated

5. **Form Design Requirements**
   - Multi-step form architecture (5 steps recommended)
   - Progressive disclosure patterns
   - Mobile-responsive considerations
   - Auto-save draft functionality
   - Validation rules documented

6. **Admin Interface Specifications**
   - Business approval dashboard requirements
   - Reimbursement management interface
   - Document upload and tracking
   - Bulk action capabilities
   - Filtering and search requirements

7. **Migration Strategy**
   - UUID to WordPress post ID mapping
   - Product taxonomy import approach
   - Address data migration plan
   - User-business relationship preservation
   - Data validation checkpoints

8. **WordPress Implementation Recommendations**
   - **Hybrid taxonomy approach** for 202 product fields
   - Custom table usage strategy
   - ACF Pro field groups suggested
   - Performance optimization techniques
   - Caching strategy outlined

9. **Key Findings**
   - Existing form is comprehensive but lengthy (needs UX improvement)
   - Product selection is main complexity (202 boolean fields)
   - Three distinct reimbursement workflows to implement
   - Multiple address support is critical feature
   - Admin approval workflow is core functionality

10. **Phase 2 Priorities Identified**
    - **Must-Have (MVP)**:
      - Business application form with product selection
      - Admin approval interface
      - Member dashboard
      - Three reimbursement form types
      - Public business directory
    - **Nice-to-Have (V1.1)**:
      - Advanced search and filtering
      - User invitation system
      - Communication module
      - Analytics dashboard

11. **Technical Specifications**
    - File upload limits and formats
    - Validation rules for all field types
    - Performance considerations for large forms
    - Search optimization strategies

---

#### Added - Phase 2: Templates & UI Development (In Progress)

**ACF Integration** (`/inc/acf-field-groups.php`)
- 7 field groups programmatically registered:
  - Business Information (DBA, phone, email, website, profile, hours, employees)
  - Business Classification (Grown/Taste/Associate with conditional logic)
  - Social Media (Facebook, Instagram, Twitter, Pinterest handles)
  - Sales & Distribution (sales types, additional info)
  - Owner/Primary Contact (private information fields)
  - Primary Business Address (with address type and conditional instructions)
  - Administrative (approval status, admin notes, approval date, approved by)
- Conditional logic for associate member types
- Field validation and requirements
- Admin-only fields for approval workflow

**Simplified Product Taxonomy** (`/inc/product-taxonomy.php`)
- **Reduced from 202 boolean fields to 8 parent categories + 70 children**
- Parent categories:
  - Produce (11 types)
  - Nuts (4 types)
  - Livestock & Poultry (11 types)
  - Meat Products (7 types)
  - Dairy Products (7 types)
  - Beverages (9 types)
  - Prepared Foods (9 types)
  - Other Products (7 types)
- ACF fields for product attributes:
  - Organic certification
  - Free Range (poultry)
  - Grass Fed (beef, lamb)
  - Preparation types (fresh, frozen, smoked, cured, dried)
  - Product use (meat, dairy, fiber, eggs)
- Helper functions:
  - `nmda_get_business_products()` - Get products with attributes
  - `nmda_get_product_categories()` - Get hierarchical categories
  - `nmda_get_products_by_category()` - Get products by parent
- Auto-populates on theme activation

**Business Application Form** (`page-business-application.php` + `/inc/application-forms.php`)
- Multi-step form template (5 steps):
  1. Personal Contact Information (private)
  2. Business Information (public display)
  3. Logo Program Classification
  4. Product Selection
  5. Review & Submit
- Progress indicator navigation
- Conditional field display based on selections
- jQuery validation and field toggles
- AJAX form submission
- Draft save functionality
- Features:
  - Address type with conditional reservation/other instructions
  - Primary contact toggle
  - Social media field enable/disable
  - Associate member type conditional display
  - Product category accordion interface
  - Terms agreement checkbox
- Form submission handler:
  - Creates `nmda_business` custom post (pending status)
  - Saves all ACF field data
  - Assigns product taxonomy terms
  - Associates user with business (owner role)
  - Sends confirmation email to applicant
  - Sends notification to admin
  - Returns success with redirect to dashboard

**Template Structure**
- `page-business-application.php` - Page template for application form
- Integrated with existing multi-step form JavaScript from Phase 1
- Bootstrap 4 styling with NMDA brand colors
- Mobile-responsive design
- Accessible form controls

**User Experience Improvements**
- Form sections collapsible/expandable
- Real-time validation feedback
- Auto-save draft functionality
- Progress saving between sessions
- Clear field instructions and help text
- Format examples for phone, address fields

**Integration Points**
- Uses Phase 1 custom tables for user-business relationships
- Leverages Phase 1 email notification system
- Connects to Phase 1 AJAX handlers
- Utilizes Phase 1 approval workflow infrastructure

**Bug Fixes & UX Improvements (Round 1)**
- Enhanced product selection display with collapsible accordion cards
- Added product selection counter showing number of products selected
- Added helpful message if product taxonomy not initialized
- Fixed form submission to show success message before redirect
- Added spinner animation during submission
- Displays application ID and confirmation message
- Auto-redirect to dashboard after 3 seconds
- Proper error handling with user-friendly messages
- Button states properly managed during submission

**Bug Fixes & UX Improvements (Round 2)**
- **Fixed product taxonomy hierarchical structure** - Changed `product_type` taxonomy from `hierarchical => false` to `hierarchical => true` in custom-post-types.php to enable parent-child product categories
- **Implemented complete draft save/restore system** - Full workflow for saving and restoring application progress:
  - Added `nmda_handle_draft_save()` AJAX handler to save form progress as user meta, fixing 400 error
  - Added `nmda_handle_draft_clear()` AJAX handler to delete saved drafts
  - Draft detection notification shows when user returns to form with saved draft
  - "Restore Draft" button populates all form fields including checkboxes, radios, and arrays
  - "Clear Draft & Start Fresh" button with confirmation dialog
  - Draft automatically cleared after successful submission
  - Human-readable timestamp showing draft age (e.g., "5 hours ago")
  - Draft data stored as `nmda_application_draft` user meta with `nmda_application_draft_updated` timestamp
- **Added application summary population** - Created `populateApplicationSummary()` JavaScript function to display comprehensive review of all form data in Step 5
- **Removed auto-redirect after submission** - Eliminated 3-second setTimeout redirect, keeping success message and manual dashboard link per user preference
- Application summary displays personal info, business info, classifications, and selected products with visual formatting
- Summary updates dynamically when user navigates to Step 5

---

#### Added - Phase 2: Admin Approval Interface ✓

**Admin Dashboard** (`/inc/admin-approval.php`)
- Custom admin menu page "Applications" with dashicons-yes-alt icon
- Submenu pages for Pending, Approved, and Rejected applications
- Dedicated admin assets (admin-approval.css and admin-approval.js)
- Full AJAX-powered interface with no page reloads

**Applications List View**
- Tabbed interface with status filters (All, Pending, Approved, Rejected)
- Real-time application counts in each tab
- Sortable table with columns:
  - Business Name
  - Applicant (name and email)
  - Classification (Logo Program type)
  - Products count
  - Submission time (relative, e.g., "5 hours ago")
  - Status badge with color coding
  - Quick actions
- Checkbox column for bulk selection
- "Select All" functionality

**Application Detail Modal**
- Triggered by "View Details" button
- Full-screen modal overlay with close button
- Comprehensive application review sections:
  - Personal Contact Information (private - name, phone, email, mailing address)
  - Business Information (public - legal name, DBA, phone, email, website, physical address, address type, business profile, hours)
  - Logo Program Classification (classifications, associate types, number of employees)
  - Products list (all selected products with checkmarks)
  - Admin Notes (editable textarea with save button)
- Status bar showing current approval status and submission date
- Action buttons based on current status:
  - Pending: "Approve Application" and "Reject Application"
  - Approved: "Revoke Approval"
  - Rejected: "Approve Application"

**Approval/Rejection Workflow**
- **Approve Action**:
  - Updates `approval_status` ACF field to 'approved'
  - Records `approval_date` timestamp
  - Records `approved_by` user ID
  - Changes post status from 'pending' to 'publish'
  - Sends approval email to applicant
  - Shows success notification
  - Refreshes page to update status
- **Reject Action**:
  - Updates `approval_status` ACF field to 'rejected'
  - Records rejection timestamp and admin
  - Keeps post status as 'pending'
  - Sends rejection email with admin notes
  - Shows success notification
  - Refreshes page to update status

**Email Notifications**
- **Approval Email**:
  - Congratulations message
  - Instructions to access member dashboard
  - Dashboard login link
- **Rejection Email**:
  - Professional notification
  - Includes admin notes/feedback if provided
  - Contact information for questions

**Admin Notes System**
- Editable textarea for each application
- "Save Notes" button with AJAX save
- Notes included in rejection emails
- Persistent storage in ACF field

**Bulk Actions**
- Dropdown selector with "Approve" and "Reject" options
- "Apply" button with confirmation dialog
- Processes selected applications in batch
- Shows count of processed applications
- Success notification after completion
- Automatic page refresh

**Security & Permissions**
- All AJAX actions require `manage_options` capability (admin only)
- Nonce verification on all requests
- Permission checks before any data modification
- Sanitized input for admin notes

**User Experience**
- Color-coded status badges (orange/pending, green/approved, red/rejected)
- Loading states for all AJAX actions
- Confirmation dialogs for destructive actions
- Success/error notifications
- Button text updates during processing ("Approving...", "Rejecting...", "Saving...")
- Smooth modal animations
- Responsive design for mobile devices
- Accessible keyboard navigation

**AJAX Actions Registered**
- `nmda_get_application_details` - Load full application data
- `nmda_approve_application` - Approve single application
- `nmda_reject_application` - Reject single application
- `nmda_save_admin_notes` - Save admin notes

---

#### Added - Phase 2: Member Dashboard ✓

**Dashboard Page Template** (`page-member-dashboard.php`)
- Template Name: "Member Dashboard" for easy page assignment
- Login requirement with automatic redirect to login page
- Multi-business support for users associated with multiple businesses
- Role-aware interface (owner, manager, viewer)

**Dashboard States**
- **No Application State**: Welcome screen with "Apply Now" CTA button
- **Pending Approval State**: Status notification with application ID and submission date
- **Rejected State**: Alert with admin feedback/notes and contact information
- **Active Member State**: Full dashboard with all features

**Quick Stats Overview Cards**
- **Membership Status**: Visual confirmation of active membership
- **Products Listed**: Count of product types associated with business
- **Reimbursements This Year**: Count for current fiscal year
- **Member Since**: Human-readable time since approval (e.g., "6 months")
- Color-coded icons with gradient background
- Hover effects with elevation
- Fully responsive grid layout

**Quick Actions Menu**
- Three prominent action buttons:
  - Edit Business Profile (with edit icon)
  - Submit Reimbursement (with dollar icon)
  - Download Resources (with download icon)
- Large, clickable cards with icons
- Hover animations with lift effect
- Links ready for future form integration

**Business Profile Overview Card**
- **Header Section**:
  - Business logo (featured image) if available
  - Business legal name
  - DBA if specified
  - Classification badges (Grown/Taste/Associate)
- **Contact Information**:
  - Business phone with phone icon
  - Business email with envelope icon
  - Website link with globe icon (opens in new tab)
- **Location**:
  - Full primary address display
  - City, State, ZIP
- **About Section**:
  - Business profile description
  - Preserves line breaks
- **Products Section**:
  - All selected products displayed as badges
  - Responsive tag layout
- **Action Buttons**:
  - "Edit Profile" button (primary)
  - "View Public Profile" button (opens in new tab)

**Sidebar Widgets**

*Recent Activity Card:*
- Lists last 5 reimbursement submissions
- Shows reimbursement type (Lead/Advertising/Labels)
- Status badges with color coding
- Relative timestamps (e.g., "2 days ago")
- "No recent activity" message if empty

*Resources Card:*
- Lists last 5 published resources
- File icon with clickable links
- "View All Resources" button
- "No resources available" message if empty

*Support/Help Card:*
- Contact information for NMDA staff
- Email and phone display
- "Send Message" button for future communication module

**Dashboard Styles** (`/assets/css/dashboard.css`)
- NMDA brand colors (brown #512c1d, red #8b0c12)
- Gradient header background
- Card-based layout with consistent shadows
- Status badge color system (pending/submitted/approved/rejected)
- Icon integration with Font Awesome
- Smooth transitions and hover effects
- Professional typography hierarchy
- Fully responsive breakpoints for mobile/tablet

**Data Integration**
- Pulls data from ACF fields (approval_status, business_phone, business_email, etc.)
- Queries custom reimbursements table for activity
- Uses taxonomy terms for product display
- Leverages user-business relationship from Phase 1 custom tables
- Real-time counts from database

**User Experience Features**
- Branded color scheme with gradient effects
- Intuitive card-based information architecture
- Clear visual hierarchy
- Icon-driven navigation
- Status-based conditional content
- Loading states preparation
- Mobile-first responsive design
- Accessible navigation and focus states

**Security & Permissions**
- Requires user authentication
- Only shows data for businesses user is associated with
- Respects user role (owner/manager/viewer)
- Secure data queries with wpdb prepare
