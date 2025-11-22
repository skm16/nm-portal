# Outstanding Tasks - NMDA Portal

**Project**: NMDA WordPress Portal
**Phase**: Post Phase 2 - Enhancements & Testing
**Date**: November 21, 2025

---

## Overview

Phase 2 development is 90% complete with all core functionality implemented. This document outlines the remaining 10% of work consisting of enhancements, optional features, and comprehensive testing.

**Current Status**: Production-ready for testing
**Remaining Work**: ~40-60 hours of development + 60-80 hours of testing

---

## Recent Updates (November 21, 2025)

### Business Application Form Enhancements
**Status**: ✅ COMPLETE

#### Styling & UX Improvements
- ✅ Added professional dashboard header with brown-red gradient to business application page
- ✅ Wrapped form in Bootstrap card for consistent layout with other portal pages
- ✅ Enhanced progress indicator with:
  - Circular step numbers (48px diameter)
  - Three-state visual system (pending/active/completed)
  - Bootstrap Icons checkmark for completed steps (`bi-check-circle-fill`)
  - Smart connecting lines between circles (gold for completed segments)
  - Brown-to-red gradient for completed steps matching brand colors
  - Gold border with glow effect for active step
  - Responsive vertical timeline for mobile devices
  - Smooth CSS transitions between states
- ✅ Fixed JavaScript state management for progress indicator
- ✅ Enqueued `dashboard.css` for business application page
- ✅ Added Bootstrap Icons CDN (v1.13.1)

#### Files Modified
- `functions.php` - Added dashboard.css and Bootstrap Icons enqueue
- `page-business-application.php` - Added dashboard header and card wrapper
- `inc/application-forms.php` - Changed initial step state from "completed" to "active"
- `assets/css/custom.css` - Complete progress indicator styling overhaul
- `assets/js/custom.js` - Fixed zero-indexed step logic for proper state management

---

## 1. Custom Login & Registration Pages

**Priority**: 🔴 HIGH (Pre-Launch Critical)
**Estimated Time**: 6-8 hours
**Status**: ✅ COMPLETE (November 21, 2025)

### Completed Features
- ✅ Custom `page-login.php` template with branded interface
- ✅ Custom `page-register.php` template with all required fields
- ✅ Custom `page-forgot-password.php` template
- ✅ Custom `page-reset-password.php` template with token validation
- ✅ All WordPress URL redirects configured (wp-login.php → /login/)
- ✅ Password visibility toggle with visual feedback
- ✅ Password strength indicator with requirements
- ✅ Password match validation
- ✅ Welcome email with login instructions
- ✅ Redirect to login page after registration with success message
- ✅ All form validation (client and server-side)
- ✅ WordPress nonce verification on all forms
- ✅ Proper error message display
- ✅ Login/Register/Forgot Password/Reset Password CSS styling (`assets/css/login-register.css`)
- ✅ Form enhancements JavaScript (`assets/js/login-register.js`)
- ✅ Redirect after logout to homepage
- ✅ Admin bar disabled for non-admin users

### Files to Create/Modify
- `page-login.php` - Custom login page template (new)
- `page-register.php` - Custom registration page template (new)
- `page-forgot-password.php` - Forgot password page (new)
- `page-reset-password.php` - Reset password page (new)
- `inc/user-management.php` - Add login/register handlers (modify)
- `assets/css/login-register.css` - Styling for auth pages (new)
- `assets/js/login-register.js` - Form validation and AJAX (new)
- `functions.php` - Add URL redirect filters (modify)

### Code Examples

**Redirect wp-login.php to custom page** (add to functions.php):
```php
// Redirect wp-login.php to custom login page
add_action('init', 'nmda_redirect_login_page');
function nmda_redirect_login_page() {
    $page_viewed = basename($_SERVER['REQUEST_URI']);

    if ($page_viewed == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
        if (!isset($_GET['action']) || $_GET['action'] == 'login') {
            wp_redirect(home_url('/login/'));
            exit;
        }
    }
}

// Filter login URL
add_filter('login_url', 'nmda_custom_login_url', 10, 3);
function nmda_custom_login_url($login_url, $redirect, $force_reauth) {
    $login_page = home_url('/login/');
    if (!empty($redirect)) {
        $login_page = add_query_arg('redirect_to', urlencode($redirect), $login_page);
    }
    return $login_page;
}

// Filter registration URL
add_filter('register_url', 'nmda_custom_register_url');
function nmda_custom_register_url($register_url) {
    return home_url('/register/');
}

// Filter lost password URL
add_filter('lostpassword_url', 'nmda_custom_lostpassword_url', 10, 2);
function nmda_custom_lostpassword_url($lostpassword_url, $redirect) {
    $lostpassword_page = home_url('/forgot-password/');
    if (!empty($redirect)) {
        $lostpassword_page = add_query_arg('redirect_to', urlencode($redirect), $lostpassword_page);
    }
    return $lostpassword_page;
}
```

### Testing Requirements
- [ ] Login with valid credentials
- [ ] Login with invalid credentials (error message)
- [ ] "Remember Me" functionality
- [ ] Registration with all fields
- [ ] Registration with duplicate email (error)
- [ ] Password reset request
- [ ] Password reset with valid token
- [ ] Password reset with expired/invalid token
- [ ] Redirect to original page after login
- [ ] Already logged in users redirected from login page
- [ ] wp-login.php properly redirects
- [ ] wp-admin access for non-logged-in users redirects to custom login

---

## 2. Homepage Redirect Logic

**Priority**: 🔴 HIGH (Pre-Launch Critical)
**Estimated Time**: 2-3 hours
**Status**: ✅ COMPLETE (November 21, 2025)

### Completed Features
- ✅ Custom `front-page.php` template with redirect logic
- ✅ Logged-in users automatically redirect to `/dashboard/`
- ✅ Non-logged-in users see branded landing page with login/register options
- ✅ Homepage CSS styling (`assets/css/homepage.css`)
- ✅ Proper conditional rendering based on user login status

### Required Work

#### Homepage Template
- [ ] Create `front-page.php` or modify existing homepage
- [ ] Implement redirect logic:
  - **If user is logged in**: Redirect to `/dashboard/`
  - **If user is NOT logged in**: Show login/register landing page
- [ ] Alternative approach: Show content on homepage with conditional sections

#### Non-Logged-In Homepage (Option A - Redirect)
- [ ] Check `is_user_logged_in()`
- [ ] If false, show landing page content
- [ ] If true, `wp_redirect()` to dashboard

#### Non-Logged-In Homepage (Option B - Landing Page)
- [ ] Hero section with NMDA branding
- [ ] Brief program description
- [ ] Login form (inline or modal)
- [ ] "Create Account" button (prominent)
- [ ] "Forgot Password?" link
- [ ] Program benefits/features
- [ ] Contact information

### Files to Create/Modify
- `front-page.php` - Homepage template with redirect logic (new)
- `page-home.php` - Alternative landing page template (new)
- `assets/css/homepage.css` - Homepage styling (new)

### Code Example

**front-page.php with redirect**:
```php
<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

// Redirect logged-in users to dashboard
if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

// Show landing page for non-logged-in users
get_header();
?>

<div class="nmda-landing-page">
    <section class="hero-section">
        <div class="container">
            <h1>Welcome to the New Mexico Logo Program</h1>
            <p class="lead">Join New Mexico's premier agricultural branding initiative</p>

            <div class="cta-buttons">
                <a href="<?php echo home_url('/register/'); ?>" class="btn btn-primary btn-lg">
                    Create Account
                </a>
                <a href="<?php echo home_url('/login/'); ?>" class="btn btn-outline-primary btn-lg">
                    Log In
                </a>
            </div>
        </div>
    </section>

    <!-- Additional landing page content -->
</div>

<?php get_footer(); ?>
```

### Testing Requirements
- [ ] Logged-in user visiting homepage redirects to dashboard
- [ ] Non-logged-in user sees landing page with login/register options
- [ ] No redirect loop issues
- [ ] Dashboard link preserved in navigation
- [ ] Direct dashboard access still requires login

---

## 3. Multiple Address Management UI

**Priority**: 🟢 NICE-TO-HAVE (Application form only)
**Estimated Time**: 0 hours (COMPLETE) or 3-4 hours (if adding to application form)
**Status**: ✅ SUBSTANTIALLY COMPLETE (75-80%)
**Last Updated**: November 21, 2025

### Implementation Status

#### ✅ FULLY IMPLEMENTED
- ✅ Database table `wp_nmda_business_addresses` with proper schema
- ✅ Backend CRUD functions (`nmda_get_business_addresses()`, `nmda_add_business_address()`, `nmda_update_business_address()`, `nmda_delete_business_address()`)
- ✅ Four AJAX handlers with nonce verification and permission checks
- ✅ **Edit Profile Page** - Complete address management interface
  - "Manage Addresses" section in Contact & Location tab
  - List all existing addresses with edit/delete buttons
  - "Add New Address" button
  - Bootstrap modal form for adding/editing addresses
  - Address type dropdown (8 options: Physical, Mailing, Shipping, Billing, Warehouse, Retail, Office, Other)
  - AJAX save for address changes
  - Delete confirmation for address removal
  - Set as Primary functionality
- ✅ **Frontend JavaScript** - Complete (`assets/js/address-management.js`, 369 lines)
  - Add/edit/delete/set primary handlers
  - Real-time list updates
  - Form state management
  - Success/error messaging
- ✅ **Business Profile Display** - Shows all addresses with proper formatting
  - Address name/type labels
  - Primary badge indicator
  - Full address details with Google Maps links
  - Mobile-responsive cards

#### ⚠️ OPTIONAL ENHANCEMENT (Not Required for Launch)
**Business Application Form** - Currently only supports primary address entry
- Application form keeps initial submission simple (one primary address)
- Users can add additional addresses post-approval via Edit Profile page
- If desired, can add dynamic address fields to application form (3-4 hours)

### Files Implemented
- ✅ `inc/business-management.php` - Lines 219-327, 870-1092 (CRUD + AJAX)
- ✅ `page-edit-profile.php` - Lines 312-704 (UI + Modal)
- ✅ `assets/js/address-management.js` - Complete (369 lines)
- ✅ `single-nmda_business.php` - Lines 265-363 (Display)

### Testing Status
- [x] Add new address via modal
- [x] Edit existing address
- [x] Delete non-primary address
- [x] Set address as primary
- [x] Primary address displayed first
- [x] Google Maps integration
- [x] Mobile responsive
- [x] Security (nonce, permissions, sanitization)
- Address deletion confirmed and executed
- All addresses display on profile
- Mobile responsive

---

## 4. Email Integration (Third-Party Service)

**Priority**: 🟡 MEDIUM
**Estimated Time**: 4-6 hours
**Status**: Infrastructure exists, API integration incomplete

### Current State
- ✅ `inc/api-integrations.php` file exists with class structure
- ✅ Hooks in place for business approval and user addition
- ⚠️ Mailchimp/ActiveCampaign API credentials not configured
- ⚠️ API methods incomplete

### Required Work

#### Configuration
- [ ] Choose email service (Mailchimp vs ActiveCampaign)
- [ ] Create account and obtain API key
- [ ] Define list/audience ID for members
- [ ] Create segments/tags for business categories

#### Code Implementation
- [ ] Complete `NMDA_Mailchimp_Integration` or `NMDA_ActiveCampaign_Integration` class
- [ ] Implement `add_member()` method - Add approved business to list
- [ ] Implement `update_member()` method - Update member info on profile changes
- [ ] Implement `remove_member()` method - Remove if business rejected/deleted
- [ ] Implement `sync_tags()` method - Apply tags based on:
  - Business classification (Grown, Taste, Associate)
  - Product types
  - Location (county/region)
- [ ] Implement `bulk_sync()` method - Sync all approved businesses
- [ ] Error handling and logging
- [ ] Rate limiting to respect API limits

#### WordPress Integration
- [ ] Hook into business approval (on status change to 'approved')
- [ ] Hook into profile updates (on ACF field save)
- [ ] Hook into business deletion/deactivation
- [ ] Admin settings page for API configuration
- [ ] Manual sync button in admin interface
- [ ] Sync status dashboard showing last sync time, errors

#### Testing
- [ ] Test member addition on approval
- [ ] Test member update on profile edit
- [ ] Test member removal on rejection
- [ ] Test tag application
- [ ] Test bulk sync
- [ ] Test error handling (invalid API key, network issues)
- [ ] Verify data mapping correct

### Files to Modify/Complete
- `inc/api-integrations.php` - Complete API class methods
- `inc/admin-settings.php` (create new) - Admin settings page
- `inc/business-management.php` - Add sync triggers

### API Documentation References
- **Mailchimp**: https://mailchimp.com/developer/marketing/api/
- **ActiveCampaign**: https://developers.activecampaign.com/

---

## 5. In-Portal Messaging System

**Priority**: ✅ COMPLETE
**Time Spent**: ~10 hours
**Status**: Fully implemented and tested

### Implementation Summary
- ✅ Complete bidirectional messaging system between members and admins
- ✅ Conversation/thread-based interface
- ✅ Real-time unread count tracking
- ✅ AJAX-powered message sending and reply
- ✅ Email notifications on new messages
- ✅ Mobile responsive design
- ✅ NMDA-branded UI with gradient headers and gold accents

### Completed Work

#### Message Composer Interface
- [x] "New Message" button in Messages page header
- [x] Bootstrap modal for message composition
- [x] To: field (auto-populated based on user role)
- [x] Subject field with validation
- [x] Message body textarea
- [x] Send button with AJAX submission and loading states

#### Message Thread View
- [x] "Messages" page template (page-messages.php)
- [x] Two-tab interface: "All Conversations" and "Unread"
- [x] Message list with:
  - Sender/recipient with directional icons
  - Subject line
  - Business association
  - Relative timestamps
  - Read/unread visual indicators
- [x] Split view: message list + detail view
- [x] Click to view full message with thread
- [x] Reply functionality with "Re:" subject handling
- [x] Delete message capability

#### Notification System
- [x] Unread message count function
- [x] Visual "New" badges on unread messages
- [x] Email notification on new message
- [x] Auto-mark as read when viewing
- [x] Real-time badge updates (60-second polling)

#### Backend Functions (inc/communications.php)
- [x] `nmda_send_message()` - Send message with proper sender tracking
- [x] `nmda_get_messages()` - Get messages with filter support (all/inbox/sent)
- [x] `nmda_get_message_detail()` - Get full message with sender/recipient info
- [x] `nmda_mark_message_read()` - Mark message as read with permission checks
- [x] `nmda_get_unread_count()` - Get unread count for badge
- [x] `nmda_delete_message()` - Delete message with permission checks
- [x] AJAX handlers for all operations with nonce verification

#### Database Schema
- [x] Enhanced `wp_nmda_communications` table with `sender_id` column
- [x] Database upgrade function for existing installations
- [x] Proper indexes for performance

### Files Created/Modified
- ✅ `page-messages.php` - Member message interface (620+ lines)
- ✅ `assets/css/messages.css` - Complete NMDA-branded messaging styles (475+ lines)
- ✅ `assets/js/messages.js` - Message interactions with AJAX (264 lines)
- ✅ `inc/communications.php` - Backend functions and AJAX handlers (620+ lines)
- ✅ `inc/database-schema.php` - Added sender_id column and upgrade logic
- ✅ `functions.php` - Script/style enqueuing for messages page

### Testing Completed
- ✅ Member can send message to admin
- ✅ Admin can send message to member
- ✅ Reply threading works correctly with "Re:" prefixing
- ✅ Message thread displays chronologically
- ✅ Unread count accurate and updates in real-time
- ✅ Email notifications sent successfully
- ✅ Sender's own messages don't appear in their "Unread" tab
- ✅ Mobile responsive on all screen sizes
- ✅ Security: nonce verification, permission checks, input sanitization

### Remaining Optional Enhancements
- Navigation badge integration (next task)
- Dashboard widget showing recent messages (next task)
- Attachment support (future enhancement)
- Admin-side dedicated messages interface (future enhancement)

---

## 6. Field-Level Edit Approval Workflow (Admin Interface)

**Priority**: 🟠 NICE-TO-HAVE
**Estimated Time**: 8-10 hours (for remaining components)
**Status**: ✅ SUBSTANTIALLY COMPLETE (60%)
**Last Updated**: November 21, 2025

### Implementation Status

#### ✅ FULLY IMPLEMENTED
- ✅ **Database Schema**
  - `wp_nmda_field_permissions` table with proper structure
  - Default field permissions inserted on activation
  - Pending changes stored in `_pending_changes` post meta
  - Change history logged in `_field_change_log` post meta
- ✅ **Backend Functions** (`inc/business-management.php`)
  - `nmda_can_edit_field()` - Role-based edit permission checking
  - `nmda_field_requires_approval()` - Approval requirement checking
  - `nmda_update_business_field()` - Core workflow orchestrator
  - `nmda_log_field_change()` - Change history logging
  - `nmda_notify_admins_pending_change()` - Email notifications
  - `nmda_map_form_field_to_acf()` - Field name mapping
  - `nmda_apply_field_change()` - Apply approved changes
  - Email notifications for approval/rejection
- ✅ **Admin Metabox** (`inc/admin-field-approvals.php`, 615 lines)
  - Registered on `nmda_business` edit pages
  - "Pending Field Changes" metabox with professional UI
  - Side-by-side comparison (current vs proposed)
  - Approve/Reject buttons with AJAX
  - Rejection reason textarea
  - Field value formatting (URLs, emails, phones, text)
- ✅ **Dashboard Widget**
  - Shows all businesses with pending changes
  - Quick link to business edit page for review
  - Capability-based display (edit_others_posts)
- ✅ **AJAX Handlers**
  - `nmda_ajax_approve_field_change()` - Approve endpoint
  - `nmda_ajax_reject_field_change()` - Reject endpoint with reason
  - Nonce verification and capability checks
- ✅ **Frontend JavaScript** (`assets/js/field-approvals.js`, 257 lines)
  - Approve/reject button handlers
  - Confirmation dialogs
  - Success/error messaging
  - Auto-removal of approved/rejected changes
  - Rejection reason validation
- ✅ **CSS Styling** (`assets/css/field-approvals.css`, 362 lines)
  - NMDA-branded design
  - Responsive layout
  - Mobile-optimized grid to single column
  - Processing states with animations

#### ❌ NOT IMPLEMENTED (Remaining Work: 8-10 hours)

**Admin Settings Page for Field Permissions** (4-6 hours)
- [ ] Admin menu page "Field Permissions"
- [ ] UI to configure field permission levels
  - [ ] Freely editable by members
  - [ ] Requires admin approval
  - [ ] Admin only (not editable by members)
- [ ] Save permissions to database
- [ ] Currently using hardcoded defaults

**Dedicated Pending Changes Admin Page** (3-4 hours)
- [ ] Admin menu item "Pending Changes"
- [ ] Table showing ALL pending changes across all businesses
- [ ] Filters: Business, field type, date range
- [ ] Sortable columns
- [ ] Bulk approve/reject functionality

**Enhanced Audit Trail View** (1-2 hours)
- [ ] Display change history on business edit page
- [ ] Show approved/rejected changes with dates and admins
- [ ] Currently logged but not displayed in admin UI

### Files Implemented
- ✅ `inc/admin-field-approvals.php` - Metabox, AJAX handlers, approval logic (615 lines)
- ✅ `inc/business-management.php` - Core field update workflow
- ✅ `inc/database-schema.php` - Field permissions table
- ✅ `assets/js/field-approvals.js` - Frontend interactions (257 lines)
- ✅ `assets/css/field-approvals.css` - Admin styling (362 lines)
- ✅ `functions.php` - Script/style enqueuing (lines 153-178)

### Files to Create
- ❌ `inc/admin-field-permissions.php` - Settings page for configuring permissions
- ❌ `inc/admin-pending-changes-list.php` - Dedicated pending changes list page
- ❌ `assets/css/admin-field-permissions.css` - Settings page styling
- ❌ `assets/js/admin-field-permissions.js` - Settings page interactions

### Testing Status
- [x] Field permission checking enforced in edit form
- [x] Pending changes created when restricted field edited
- [x] Admin metabox displays pending changes correctly
- [x] Side-by-side comparison accurate
- [x] Approval applies change to business
- [x] Rejection discards change and sends reason email
- [x] AJAX operations secure (nonce, capabilities)
- [x] Change history logged
- [ ] Bulk operations (not yet implemented)
- [ ] Field permissions configurable via UI (not yet implemented)

---

## 7. User Invitation System

**Priority**: 🟠 NICE-TO-HAVE
**Estimated Time**: 2-3 hours (for polish and HTML email)
**Status**: ✅ SUBSTANTIALLY COMPLETE (85-90%)
**Last Updated**: November 21, 2025

### Implementation Status

#### ✅ FULLY IMPLEMENTED
- ✅ **Database Schema**
  - `wp_nmda_user_business` table with invitation fields
  - `invitation_token` (varchar 64) with index
  - `expires_at` (datetime) for 30-day expiration
  - `invited_by`, `invited_date`, `accepted_date` columns
  - Status tracking (pending, active, inactive)
  - Database upgrade function for v1.1 migration
- ✅ **Backend Functions** (`inc/business-management.php`)
  - `nmda_invite_user_to_business()` - Complete invitation flow (lines 20-129)
    - Email validation
    - Duplicate check
    - 32-character secure token generation
    - 30-day automatic expiration
    - Handles existing and new users
    - Transient storage for new user invitations
    - Email sending with rollback on failure
    - Communication logging
  - `nmda_accept_invitation()` - Token validation and acceptance (lines 138-210)
    - Database and transient token validation
    - Expiration checking
    - User email verification
    - Status update to 'active'
    - Token cleanup after acceptance
  - Supporting CRUD functions for user-business relationships
- ✅ **Manage Business Users Page** (`page-manage-business-users.php`, 385 lines)
  - Access control (owner-only with fallback list)
  - Multi-business selector for users with multiple businesses
  - Current users table with:
    - User name with "You" badge
    - Email address
    - Role dropdown (Owner, Manager, Viewer)
    - Status badges (Active, Pending, Inactive)
    - Join/Invited date display
    - Resend invitation button
    - Remove user button
  - "Send Invitation" button with Bootstrap modal
  - Invitation form with email, role, personal message fields
  - Role capability explanations
  - Permission enforcement (can't self-delete, can't change own role if owner)
- ✅ **Invitation Acceptance Page** (`page-accept-invitation.php`, 273 lines)
  - Token validation from URL parameter
  - Expiration checking with clear error messages
  - Business validation
  - Inviter display
  - Different flows for logged-in vs non-logged-in users
  - Email mismatch warning
  - Role capability display (Owner/Manager/Viewer)
  - Auto-redirect to dashboard on success
  - Professional NMDA-branded UI
- ✅ **AJAX Handlers** (`inc/user-management.php`, lines 528-689)
  - `nmda_ajax_invite_user` - Send invitation
  - `nmda_ajax_remove_user` - Remove user from business
  - `nmda_ajax_update_user_role` - Change user role
  - `nmda_ajax_resend_invitation` - Resend invitation email
  - Nonce verification and permission checks on all endpoints
- ✅ **Frontend JavaScript** (`assets/js/user-management.js`, 398 lines)
  - Business selector updates
  - Invite user form submission with validation
  - Role change with confirmation
  - User removal with confirmation
  - Invitation resend
  - Dynamic user list refresh
  - Success/error messaging
  - Form reset on modal close
- ✅ **CSS Styling** (`assets/css/user-management.css`)
  - NMDA-branded design
  - Responsive table layout
  - Modal styling
  - Badge and button styles
- ✅ **Email System**
  - Plain text invitation email (functional)
  - Subject: "Invitation to join {business_name} on NMDA Portal"
  - Includes business name, role, acceptance link
  - 30-day expiration notice
  - Uses WordPress `wp_mail()`

#### ❌ NOT IMPLEMENTED / NEEDS ENHANCEMENT (Remaining Work: 2-3 hours)

**HTML Email Template** (1-2 hours)
- [ ] Create professional HTML email template file
- [ ] NMDA branding with logo and colors
- [ ] Email client compatibility (inline CSS)
- [ ] Currently plain text only

**Auto-Acceptance on Registration** (0.5 hours)
- [ ] Modify registration flow to auto-accept if email matches invitation
- [ ] Currently requires manual acceptance after registration

**Expired Invitation Cleanup** (0.5 hours)
- [ ] WordPress cron job to clean expired invitations
- [ ] Transient cleanup (handled automatically by WordPress)
- [ ] Database record cleanup for expired tokens

### Files Implemented
- ✅ `inc/business-management.php` - Lines 20-210 (invitation functions)
- ✅ `inc/user-management.php` - Lines 528-689 (AJAX handlers)
- ✅ `inc/database-schema.php` - Lines 164-190 (schema upgrade for invitations)
- ✅ `page-manage-business-users.php` - Complete user management UI (385 lines)
- ✅ `page-accept-invitation.php` - Acceptance page (273 lines)
- ✅ `assets/js/user-management.js` - Frontend interactions (398 lines)
- ✅ `assets/css/user-management.css` - Styling
- ✅ `functions.php` - Lines 132-140, 184-185 (enqueuing)

### Testing Status
- [x] Owner can invite user via modal form
- [x] Invitation email delivered
- [x] Invitation link includes valid token
- [x] Token validation works correctly
- [x] Expiration checking enforced
- [x] Existing user acceptance after login
- [x] User added with correct role
- [x] Role permissions enforced in manage users page
- [x] Resend invitation works
- [x] Remove user works with confirmation
- [x] Role change works with confirmation
- [x] Security (nonce, permissions, sanitization)
- [ ] HTML email template (plain text only currently)
- [ ] Auto-acceptance on new user registration
- [ ] Expired invitation cleanup cron job

---

## 8. Advanced Search & Directory Filtering

**Priority**: 🟢 ENHANCEMENT
**Estimated Time**: 6-8 hours
**Status**: Basic directory exists, advanced features missing

### Current State
- ✅ Business directory exists (archive-nmda_business.php)
- ✅ Basic search by name
- ✅ Filter by classification
- ⚠️ No multi-criteria filtering
- ⚠️ No AJAX filtering (page reloads)
- ⚠️ No location-based search
- ⚠️ No attribute filtering (organic, etc.)

### Required Work

#### Enhanced Filter Interface
- [ ] Filter sidebar or top panel
- [ ] Search box (business name or keywords)
- [ ] Classification checkboxes (Grown, Taste, Associate)
- [ ] Product type hierarchical checkboxes or multi-select
- [ ] Location filters:
  - County dropdown
  - City autocomplete
  - Radius search from ZIP (advanced)
- [ ] Sales type checkboxes (Local, Regional, National, International)
- [ ] Attribute filters:
  - Organic certified
  - Free range
  - Grass fed
- [ ] "Apply Filters" button
- [ ] "Clear Filters" button
- [ ] Active filter tags (closeable to remove filter)

#### AJAX Filtering
- [ ] Convert to AJAX-powered filtering (no page reload)
- [ ] Update URL parameters for bookmarkable searches
- [ ] Loading indicator during search
- [ ] Results count update
- [ ] Smooth transitions

#### Map Integration (Optional, Advanced)
- [ ] Google Maps or Leaflet integration
- [ ] Plot businesses on map
- [ ] Cluster markers for nearby businesses
- [ ] Click marker to view business card
- [ ] Map/list view toggle

#### Search Algorithm Enhancement
- [ ] Full-text search across multiple fields (name, description, products)
- [ ] Relevance scoring
- [ ] Typo tolerance (fuzzy search)
- [ ] Search suggestions/autocomplete

### Files to Modify
- `archive-nmda_business.php` - Enhanced filter interface
- `inc/business-query.php` (create new) - Custom query logic
- `assets/css/directory.css` - Filter styling
- `assets/js/directory-filter.js` (create new) - AJAX filtering
- Template part for business cards (for AJAX replacement)

### Testing
- Multi-criteria filtering works
- Filter combinations accurate
- AJAX filtering smooth and fast
- URL parameters correct for bookmarking
- Clear filters resets all
- Active filter tags display and remove correctly
- Map displays businesses correctly (if implemented)
- Search algorithm returns relevant results
- Mobile responsive

---

## 9. Analytics Dashboard

**Priority**: 🟢 ENHANCEMENT
**Estimated Time**: 8-10 hours
**Status**: Not implemented

### Current State
- ⚠️ No analytics tracking implemented
- ⚠️ No admin analytics dashboard
- ✅ Database structure supports activity tracking

### Required Work

#### Google Analytics 4 Integration
- [ ] Create GA4 property for portal
- [ ] Add GA4 tracking code to theme
- [ ] Configure goals/events:
  - User registration
  - Business application submission
  - Reimbursement submission
  - Resource downloads
  - Profile edits
  - Business directory searches
- [ ] E-commerce tracking for reimbursement amounts (if applicable)
- [ ] User properties (member status, business classification)

#### Resource Download Tracking
- [ ] Backend already has download tracking structure
- [ ] Enhance to track:
  - Which resources downloaded
  - By which users
  - Download count per resource
  - Download trends over time
- [ ] Admin report for resource popularity

#### Admin Analytics Dashboard
- [ ] New admin page "Analytics" or "Reports"
- [ ] Dashboard sections:

  **User Metrics**:
  - [ ] Total users
  - [ ] New registrations this month/year
  - [ ] Active users (logged in within 30 days)
  - [ ] User growth chart (line graph)

  **Business Metrics**:
  - [ ] Total businesses
  - [ ] Pending applications
  - [ ] Approved businesses
  - [ ] Applications by month (bar chart)
  - [ ] Approval rate
  - [ ] Businesses by classification (pie chart)
  - [ ] Businesses by county/region (map or list)

  **Reimbursement Metrics**:
  - [ ] Total reimbursements submitted
  - [ ] By type (Lead, Advertising, Labels)
  - [ ] By fiscal year
  - [ ] Total amount requested
  - [ ] Total amount approved
  - [ ] Approval rate by type
  - [ ] Average reimbursement amount
  - [ ] Reimbursements by month (trend chart)

  **Resource Metrics**:
  - [ ] Total resources
  - [ ] Total downloads
  - [ ] Most downloaded resources
  - [ ] Downloads by category
  - [ ] Downloads by month (trend)

  **Engagement Metrics**:
  - [ ] Profile updates frequency
  - [ ] Average time between login
  - [ ] Most active members
  - [ ] Least active members (for outreach)

- [ ] Date range selector for all metrics
- [ ] Export reports to PDF or CSV
- [ ] Email scheduled reports (weekly/monthly digest)

#### Charts & Visualization
- [ ] Use Chart.js or similar library
- [ ] Line charts for trends
- [ ] Bar charts for comparisons
- [ ] Pie charts for distributions
- [ ] Tables for detailed data

### Files to Create
- `inc/admin-analytics.php` - Analytics page and query functions (new)
- `assets/css/admin-analytics.css` - Dashboard styles (new)
- `assets/js/admin-analytics.js` - Chart rendering and interactions (new)
- Include Chart.js library

### Testing
- GA4 tracking code fires correctly
- Events tracked accurately
- Admin dashboard loads performantly
- Charts render correctly
- Data accurate for all metrics
- Date range filtering works
- Export functionality works
- Scheduled reports delivered

---

## 10. Comprehensive Testing & QA

**Priority**: 🔴 CRITICAL
**Estimated Time**: 60-80 hours (comprehensive)
**Status**: Not started

### Testing Scope
See [TESTING_CHECKLIST.md](TESTING_CHECKLIST.md) for complete details (608 test cases).

### Critical Testing Areas
- [ ] **User Flows** (20 hours)
  - Registration → Application → Approval → Dashboard → Edit Profile → Reimbursement
  - Test with multiple user roles and scenarios
- [ ] **Form Testing** (15 hours)
  - All form submissions (application, all three reimbursements, profile edits)
  - Validation, file uploads, AJAX operations
- [ ] **Admin Workflows** (10 hours)
  - Application approval/rejection
  - Reimbursement approval/rejection
  - User management
- [ ] **Security Testing** (8 hours)
  - Authorization checks
  - Input validation
  - File upload security
  - SQL injection attempts
  - XSS attempts
- [ ] **Performance Testing** (5 hours)
  - Page load times
  - AJAX operation speed
  - Database query optimization
  - File upload performance
- [ ] **Mobile Testing** (10 hours)
  - iOS and Android devices
  - Responsive design verification
  - Touch interactions
- [ ] **Cross-Browser Testing** (8 hours)
  - Chrome, Firefox, Safari, Edge
  - Layout consistency
  - JavaScript functionality
- [ ] **Accessibility Testing** (4 hours)
  - WCAG AA compliance
  - Screen reader testing
  - Keyboard navigation
- [ ] **Email Testing** (5 hours)
  - All email types delivered
  - Links functional
  - Content accurate
- [ ] **Regression Testing** (as needed)
  - After every bug fix
  - Before deployment

### Bug Tracking
- [ ] Set up bug tracking system (GitHub Issues, Jira, etc.)
- [ ] Document all bugs with priority
- [ ] Create fix schedule
- [ ] Retest after fixes

### User Acceptance Testing (UAT)
- [ ] Identify UAT testers (NMDA staff, select members)
- [ ] Create UAT test scenarios
- [ ] Conduct UAT sessions
- [ ] Gather feedback
- [ ] Implement critical feedback

---

## 11. Documentation & Training

**Priority**: 🟡 MEDIUM
**Estimated Time**: 10-15 hours
**Status**: Technical documentation exists, user documentation needed

### User Documentation
- [ ] **Member User Guide**
  - How to register
  - How to complete business application
  - How to edit profile
  - How to submit reimbursements
  - How to access resources
  - FAQ section
- [ ] **Admin User Guide**
  - How to approve applications
  - How to manage reimbursements
  - How to manage users
  - How to upload resources
  - How to use analytics (if implemented)
  - FAQ section

### Video Tutorials (Optional)
- [ ] Member registration walkthrough
- [ ] Business application tutorial
- [ ] Reimbursement submission tutorial
- [ ] Profile editing tutorial

### Admin Training
- [ ] Schedule training session with NMDA staff
- [ ] Demonstrate admin interfaces
- [ ] Practice approval workflows
- [ ] Q&A session

### Files to Create
- `USER_GUIDE.md` or PDF
- `ADMIN_GUIDE.md` or PDF
- Video files (if created)
- Training presentation slides

---

## 12. Deployment Preparation

**Priority**: 🔴 CRITICAL
**Estimated Time**: 5-8 hours
**Status**: Not started

### Staging Environment
- [ ] Set up staging server
- [ ] Deploy code to staging
- [ ] Import production database snapshot
- [ ] Configure staging environment variables
- [ ] Test email settings (use test accounts)
- [ ] Final testing on staging

### Production Preparation
- [ ] Database backup strategy finalized
- [ ] SSL certificate obtained and configured
- [ ] DNS records configured
- [ ] Email service configured (Mailchimp/ActiveCampaign)
- [ ] Google Analytics tracking ID configured
- [ ] Performance optimization:
  - [ ] Enable caching
  - [ ] Minify CSS/JS
  - [ ] Image optimization
  - [ ] CDN setup (if applicable)
- [ ] Security hardening:
  - [ ] Update all passwords
  - [ ] Disable debug mode
  - [ ] Configure firewall rules
  - [ ] Implement rate limiting
- [ ] Monitoring setup:
  - [ ] Error logging
  - [ ] Uptime monitoring
  - [ ] Performance monitoring

### Deployment Checklist
- [ ] Final code review
- [ ] Security audit
- [ ] Performance testing
- [ ] Create production database backup
- [ ] Deploy code to production
- [ ] Run database migrations (if any)
- [ ] Verify all configurations
- [ ] Test critical paths
- [ ] Monitor error logs for 24-48 hours

### Post-Launch
- [ ] User acceptance testing on production
- [ ] Monitor for issues
- [ ] Support users (respond to questions/issues)
- [ ] Gather user feedback
- [ ] Plan iteration cycle

---

## Summary

### By Priority

**🔴 CRITICAL (Must Do Before Launch)**
- Custom Login & Registration Pages (6-8 hours)
- Homepage Redirect Logic (2-3 hours)
- Comprehensive Testing & QA (60-80 hours)
- Deployment Preparation (5-8 hours)
- **Total: 73-99 hours**

**🟡 MEDIUM (Should Do Soon)**
- Multiple Address Management UI (3-4 hours)
- Email Integration (4-6 hours)
- Documentation & Training (10-15 hours)
- **Total: 17-25 hours**

**🟠 NICE-TO-HAVE (Can Do Later)**
- In-Portal Messaging System (8-10 hours)
- Field-Level Edit Approval Workflow (6-8 hours)
- User Invitation System (6-8 hours)
- **Total: 20-26 hours**

**🟢 ENHANCEMENT (Future Iterations)**
- Advanced Search & Directory Filtering (6-8 hours)
- Analytics Dashboard (8-10 hours)
- **Total: 14-18 hours**

### Grand Total Estimated Time
**124-168 hours** of work remaining (increased from 116-157 due to new critical auth pages)

### Recommended Phased Approach

**Phase 2.5 - Pre-Launch** (2-3 weeks)
- **CRITICAL**: Custom login, registration, and password reset pages
- **CRITICAL**: Homepage redirect logic (logged in → dashboard)
- Complete critical testing (all new auth flows)
- Fix all critical and high-priority bugs
- Multiple address management
- Email integration
- User documentation
- Deployment preparation
- **Launch to production**

**Phase 3 - Post-Launch Enhancements** (1-2 months after launch)
- In-portal messaging
- User invitation system
- Field approval workflow
- Analytics dashboard
- Advanced filtering

**Phase 4 - Future Enhancements** (3+ months after launch)
- Mobile app
- Advanced reporting
- API for third-party integrations
- Automated workflows
- Member portal enhancements based on usage data

---

## Next Immediate Steps

1. **THIS WEEK (CRITICAL)**: Create custom login, registration, and password reset pages
2. **THIS WEEK (CRITICAL)**: Implement homepage redirect logic
3. **THIS WEEK**: Test all authentication flows thoroughly
4. **Week 2**: Execute comprehensive testing (see TESTING_CHECKLIST.md)
5. **Week 2**: Fix critical bugs found during testing
6. **Week 2**: Implement multiple address management
7. **Week 3**: Configure email integration
8. **Week 3**: Write user guides and documentation
9. **Week 3**: Set up staging and test deployment
10. **Week 4**: Deploy to production

---

**Document Version**: 1.1
**Last Updated**: November 21, 2025
**Next Review**: After authentication pages complete
**Changes in v1.1**: Added custom login/registration pages and homepage redirect as CRITICAL pre-launch tasks
