# NMDA Portal Testing Checklist

**Project**: NMDA WordPress Portal
**Phase**: Quality Assurance & Testing
**Date**: November 21, 2025

---

## Testing Overview

This checklist covers all aspects of the NMDA WordPress Portal that require testing before production deployment. Each section includes specific test cases with expected results.

**Testing Environments**:
- Local Development
- Staging Server (recommended)
- Cross-Browser Testing
- Mobile Device Testing

**Test Data Requirements**:
- Multiple test user accounts (admin, business owner, business manager)
- Multiple test businesses (pending, approved, rejected)
- Sample reimbursement submissions
- Test documents (PDF, JPG, PNG, DOC)

---

## 1. User Registration & Authentication

### Registration Flow
- [ ] **TC-001**: New user can access registration page
- [ ] **TC-002**: Registration form validates all required fields
- [ ] **TC-003**: Email validation works (format and uniqueness)
- [ ] **TC-004**: Password strength requirements enforced
- [ ] **TC-005**: Successful registration creates WordPress user account
- [ ] **TC-006**: Welcome email sent to new user
- [ ] **TC-007**: User redirected to business application form after registration
- [ ] **TC-008**: Duplicate email addresses rejected with clear error message

### Login Flow
- [ ] **TC-009**: User can login with email and password
- [ ] **TC-010**: "Remember Me" checkbox works correctly
- [ ] **TC-011**: Failed login shows appropriate error message
- [ ] **TC-012**: Password reset link functional
- [ ] **TC-013**: Password reset email delivered
- [ ] **TC-014**: New password successfully updates account
- [ ] **TC-015**: Last login timestamp updated on successful login
- [ ] **TC-016**: User redirected to dashboard after login

---

## 2. Business Application Form

### Form Display
- [ ] **TC-017**: Multi-step form displays correctly
- [ ] **TC-018**: Progress indicator shows correct step
- [ ] **TC-019**: All five steps accessible via navigation
- [ ] **TC-020**: Required fields marked with asterisks
- [ ] **TC-021**: Placeholder text helpful and accurate
- [ ] **TC-022**: Form responsive on mobile devices

### Step 1: Personal Contact Information
- [ ] **TC-023**: All fields render correctly
- [ ] **TC-024**: Primary contact checkbox toggles additional fields
- [ ] **TC-025**: Phone number format validation works
- [ ] **TC-026**: Email validation works
- [ ] **TC-027**: Required fields prevent progression
- [ ] **TC-028**: "Next" button advances to Step 2

### Step 2: Business Information
- [ ] **TC-029**: Business name required field validation
- [ ] **TC-030**: DBA field optional
- [ ] **TC-031**: Address type selection shows/hides conditional fields
- [ ] **TC-032**: Reservation instructions appear when "by reservation" selected
- [ ] **TC-033**: Other instructions appear when "other" selected
- [ ] **TC-034**: Social media checkboxes enable/disable handle fields
- [ ] **TC-035**: Website URL validation works
- [ ] **TC-036**: Business profile textarea accepts formatted text
- [ ] **TC-037**: Business hours example helpful

### Step 3: Classification
- [ ] **TC-038**: At least one classification must be selected
- [ ] **TC-039**: Multiple classifications can be selected
- [ ] **TC-040**: Associate member type fields appear when "Associate" checked
- [ ] **TC-041**: Number of employees accepts numeric input
- [ ] **TC-042**: Sales type checkboxes work correctly
- [ ] **TC-043**: Form prevents progression without classification

### Step 4: Products
- [ ] **TC-044**: Product categories display in accordion
- [ ] **TC-045**: Accordion expands/collapses smoothly
- [ ] **TC-046**: Product checkboxes selectable
- [ ] **TC-047**: Product counter updates as selections change
- [ ] **TC-048**: All 70 products accessible
- [ ] **TC-049**: Product categories organized correctly (8 parents)
- [ ] **TC-050**: "Select All" works for category (if implemented)

### Step 5: Review & Submit
- [ ] **TC-051**: Application summary displays all entered data
- [ ] **TC-052**: Personal info section complete
- [ ] **TC-053**: Business info section complete
- [ ] **TC-054**: Classification summary correct
- [ ] **TC-055**: Product count and list accurate
- [ ] **TC-056**: Terms agreement checkbox required
- [ ] **TC-057**: "Edit" links return to specific steps (if implemented)

### Draft Functionality
- [ ] **TC-058**: "Save Draft" button triggers save
- [ ] **TC-059**: Draft save success message appears
- [ ] **TC-060**: Draft data stored in user meta
- [ ] **TC-061**: Draft notification appears on return
- [ ] **TC-062**: "Restore Draft" populates all fields correctly
- [ ] **TC-063**: Checkbox and array fields restored properly
- [ ] **TC-064**: Draft timestamp shows relative time ("5 hours ago")
- [ ] **TC-065**: "Clear Draft" removes saved data
- [ ] **TC-066**: Draft automatically cleared after submission

### Form Submission
- [ ] **TC-067**: Submit button triggers AJAX submission
- [ ] **TC-068**: Loading spinner appears during submission
- [ ] **TC-069**: Button disabled during submission
- [ ] **TC-070**: Success message displays application ID
- [ ] **TC-071**: Confirmation email sent to applicant
- [ ] **TC-072**: Admin notification email sent
- [ ] **TC-073**: Business post created with pending status
- [ ] **TC-074**: ACF fields populated correctly
- [ ] **TC-075**: Product taxonomy terms assigned
- [ ] **TC-076**: User-business relationship created
- [ ] **TC-077**: Redirect to dashboard works (if enabled)
- [ ] **TC-078**: Error handling displays specific messages

---

## 3. Member Dashboard

### Dashboard Access
- [ ] **TC-079**: Dashboard requires login
- [ ] **TC-080**: Non-logged-in users redirected to login page
- [ ] **TC-081**: Dashboard loads within 3 seconds

### No Application State
- [ ] **TC-082**: Welcome message displays for users without businesses
- [ ] **TC-083**: "Get Started" section visible
- [ ] **TC-084**: "Apply Now" button links to application form

### Pending Application State
- [ ] **TC-085**: Alert displays for pending applications
- [ ] **TC-086**: Application ID shown
- [ ] **TC-087**: Submission date accurate
- [ ] **TC-088**: Status message clear and helpful

### Rejected Application State
- [ ] **TC-089**: Alert displays for rejected applications
- [ ] **TC-090**: Admin feedback/notes visible (if provided)
- [ ] **TC-091**: Contact information for questions displayed

### Active Member State (Approved Business)
- [ ] **TC-092**: Welcome header shows user name
- [ ] **TC-093**: Last login timestamp accurate and human-readable
- [ ] **TC-094**: Quick stats cards display correctly:
  - [ ] Membership status "Active"
  - [ ] Product count accurate
  - [ ] Reimbursement count for current fiscal year accurate
  - [ ] "Member since" calculated from approval date (not post date)

### Multi-Business Accordion
- [ ] **TC-095**: Multiple businesses display in accordion
- [ ] **TC-096**: First business expanded by default
- [ ] **TC-097**: Other businesses collapsed initially
- [ ] **TC-098**: Accordion expands/collapses smoothly
- [ ] **TC-099**: Chevron icon rotates on expand/collapse
- [ ] **TC-100**: Each business shows role badge (Owner, Manager, Viewer)

### Business Profile Display
- [ ] **TC-101**: Business logo displays (if uploaded)
- [ ] **TC-102**: Business name correct
- [ ] **TC-103**: DBA displays (if present)
- [ ] **TC-104**: Classification badges shown
- [ ] **TC-105**: Contact information accurate (phone, email, website)
- [ ] **TC-106**: Website link opens in new tab
- [ ] **TC-107**: Location address formatted correctly
- [ ] **TC-108**: Business profile description displays
- [ ] **TC-109**: Line breaks preserved in description
- [ ] **TC-110**: Products display as badges
- [ ] **TC-111**: Product tags responsive layout

### Quick Actions
- [ ] **TC-112**: "Edit Profile" link works
- [ ] **TC-113**: "View Public Profile" link works and opens in new tab

### Recent Activity
- [ ] **TC-114**: Last 5 reimbursements display
- [ ] **TC-115**: Reimbursement type shown (Lead/Advertising/Labels)
- [ ] **TC-116**: Status badges color-coded
- [ ] **TC-117**: Relative timestamps correct ("2 days ago")
- [ ] **TC-118**: "No recent activity" message if empty

### Sidebar Widgets
- [ ] **TC-119**: Reimbursement stats show pending/approved/rejected counts
- [ ] **TC-120**: "View All Reimbursements" link works
- [ ] **TC-121**: Reimbursement form links work (all three types)
- [ ] **TC-122**: Resources section displays last 5 resources
- [ ] **TC-123**: Resource links work
- [ ] **TC-124**: "View All Resources" link works
- [ ] **TC-125**: Support contact information accurate

---

## 4. Edit Profile

### Profile Access
- [ ] **TC-126**: Edit profile requires login
- [ ] **TC-127**: Business selector appears for multi-business users
- [ ] **TC-128**: Selected business data loads correctly
- [ ] **TC-129**: Unsaved changes warning when switching businesses

### Tab Interface
- [ ] **TC-130**: All four tabs display (Info, Contact, Social, Products)
- [ ] **TC-131**: Tab switching works smoothly
- [ ] **TC-132**: Tab content loads correctly
- [ ] **TC-133**: Current tab highlighted

### Tab 1: Business Information
- [ ] **TC-134**: All fields populated with current data
- [ ] **TC-135**: Business name (post title) editable
- [ ] **TC-136**: DBA field editable
- [ ] **TC-137**: Phone and email fields editable
- [ ] **TC-138**: Website URL validation works
- [ ] **TC-139**: Business profile WYSIWYG editor functional
- [ ] **TC-140**: Business hours textarea editable
- [ ] **TC-141**: Number of employees accepts numeric input

### Tab 2: Contact & Address
- [ ] **TC-142**: Address fields populated correctly
- [ ] **TC-143**: All address components editable
- [ ] **TC-144**: State dropdown has all 50 states
- [ ] **TC-145**: ZIP code validation works
- [ ] **TC-146**: County field editable

### Tab 3: Social Media
- [ ] **TC-147**: Social media checkboxes enable/disable fields
- [ ] **TC-148**: Facebook handle editable when enabled
- [ ] **TC-149**: Instagram handle editable when enabled
- [ ] **TC-150**: Twitter handle editable when enabled
- [ ] **TC-151**: Pinterest handle editable when enabled

### Tab 4: Products & Categories
- [ ] **TC-152**: Classification checkboxes reflect current selections
- [ ] **TC-153**: Product selection accordion displays
- [ ] **TC-154**: Product checkboxes reflect current selections
- [ ] **TC-155**: Product counter updates on changes
- [ ] **TC-156**: Sales type checkboxes reflect current selections

### Field-Level Permissions
- [ ] **TC-157**: Fields requiring approval show warning badge
- [ ] **TC-158**: Normal fields have no badge
- [ ] **TC-159**: Permission checking function works correctly

### Auto-Save
- [ ] **TC-160**: Form detects changes
- [ ] **TC-161**: Auto-save triggers 2 seconds after last change
- [ ] **TC-162**: "Saving..." indicator appears
- [ ] **TC-163**: "Saved ✓" indicator appears on success
- [ ] **TC-164**: Background AJAX saves without page reload
- [ ] **TC-165**: Auto-save handles errors gracefully

### Manual Save
- [ ] **TC-166**: "Save Changes" button triggers save
- [ ] **TC-167**: Form validation runs before save
- [ ] **TC-168**: Loading spinner appears
- [ ] **TC-169**: Button disabled during save
- [ ] **TC-170**: Success message details changes made
- [ ] **TC-171**: Updated fields list accurate
- [ ] **TC-172**: Pending approval fields list accurate (if applicable)
- [ ] **TC-173**: Page reloads to show updated data
- [ ] **TC-174**: Success message scrolls into view

### Unsaved Changes Warning
- [ ] **TC-175**: Browser warns before navigation if changes unsaved
- [ ] **TC-176**: Warning message clear
- [ ] **TC-177**: Warning disabled after successful save
- [ ] **TC-178**: "Cancel" button shows confirmation if changes exist

### Backend Processing
- [ ] **TC-179**: ACF fields update correctly
- [ ] **TC-180**: Post title updates
- [ ] **TC-181**: Product taxonomy terms update
- [ ] **TC-182**: Sales type updates
- [ ] **TC-183**: Address components update
- [ ] **TC-184**: Social media handles update
- [ ] **TC-185**: Change detection accurate (only changed fields processed)
- [ ] **TC-186**: Permission-based processing works (direct update vs pending approval)

---

## 5. Reimbursement Forms

### All Three Forms (Lead, Advertising, Labels)

#### Form Access
- [ ] **TC-187**: Forms require login
- [ ] **TC-188**: Forms load within 3 seconds
- [ ] **TC-189**: Business selector populated with user's businesses
- [ ] **TC-190**: Fiscal year dropdown shows current + next year

#### Form Fields (Test for each form type)
- [ ] **TC-191**: All required fields marked
- [ ] **TC-192**: All fields render correctly
- [ ] **TC-193**: Placeholder text helpful
- [ ] **TC-194**: Help text accurate

#### Lead Generation Specific
- [ ] **TC-195**: Event information fields present
- [ ] **TC-196**: Lead collection method textarea present
- [ ] **TC-197**: Cost breakdown fields (booth, materials, travel, other)
- [ ] **TC-198**: Commitment checkbox required

#### Advertising Specific
- [ ] **TC-199**: Campaign information fields present
- [ ] **TC-200**: Media type options available
- [ ] **TC-201**: Cost breakdown fields (ad space, design, other)
- [ ] **TC-202**: Target audience field present

#### Labels Specific
- [ ] **TC-203**: Product information fields present
- [ ] **TC-204**: Label type selection (new, redesign, reprint)
- [ ] **TC-205**: Vendor information fields
- [ ] **TC-206**: Label specifications (quantity, size)
- [ ] **TC-207**: Cost breakdown (design, printing, setup, shipping, other)

#### Cost Calculation
- [ ] **TC-208**: Total amount auto-calculates
- [ ] **TC-209**: Calculation updates as fields change
- [ ] **TC-210**: Calculation accurate for all cost fields
- [ ] **TC-211**: Currency formatting correct

#### File Upload
- [ ] **TC-212**: File upload field visible
- [ ] **TC-213**: Multiple file selection works
- [ ] **TC-214**: File type restriction enforced (PDF, JPG, PNG, DOC, DOCX)
- [ ] **TC-215**: File size validation (5MB per file) works client-side
- [ ] **TC-216**: Error message clear for invalid file type
- [ ] **TC-217**: Error message clear for file size exceeded
- [ ] **TC-218**: Input clears on validation failure
- [ ] **TC-219**: Valid files queue for upload

#### Fiscal Year Limit Checking
- [ ] **TC-220**: Limit enforcement works (Lead: $5k, Advertising: $10k, Labels: $3k)
- [ ] **TC-221**: Error message if limit exceeded
- [ ] **TC-222**: Remaining amount displayed
- [ ] **TC-223**: Limit calculated per business per fiscal year

#### Form Submission
- [ ] **TC-224**: Submit button triggers AJAX submission
- [ ] **TC-225**: FormData API used (supports file upload)
- [ ] **TC-226**: Loading state on button ("Submitting...")
- [ ] **TC-227**: Duplicate submission prevented
- [ ] **TC-228**: Success message displays
- [ ] **TC-229**: Reimbursement ID shown
- [ ] **TC-230**: Action buttons appear (View reimbursements, Submit another)
- [ ] **TC-231**: Error messages specific and helpful
- [ ] **TC-232**: Smooth scroll to message area

#### Backend Processing
- [ ] **TC-233**: Nonce verified
- [ ] **TC-234**: User authenticated
- [ ] **TC-235**: Business ownership verified
- [ ] **TC-236**: Fiscal year limit checked before processing
- [ ] **TC-237**: Files uploaded to WordPress media library
- [ ] **TC-238**: File size validated server-side (5MB)
- [ ] **TC-239**: Attachments created correctly
- [ ] **TC-240**: Attachment metadata generated
- [ ] **TC-241**: Document IDs stored in JSON format
- [ ] **TC-242**: Form data sanitized
- [ ] **TC-243**: Record inserted in `wp_nmda_reimbursements` table
- [ ] **TC-244**: Submission email sent to user
- [ ] **TC-245**: Admin notification email sent

---

## 6. My Reimbursements

### Page Access
- [ ] **TC-246**: Page requires login
- [ ] **TC-247**: Page loads within 3 seconds

### Summary Statistics
- [ ] **TC-248**: Total submitted count accurate
- [ ] **TC-249**: Total approved count accurate
- [ ] **TC-250**: Total approved amount formatted as currency
- [ ] **TC-251**: Pending count accurate

### Filtering
- [ ] **TC-252**: Status filter dropdown works (All, Submitted, Pending, Approved, Rejected)
- [ ] **TC-253**: Type filter dropdown works (All, Lead, Advertising, Labels)
- [ ] **TC-254**: Fiscal year filter dropdown populated
- [ ] **TC-255**: Business filter dropdown works (multi-business users)
- [ ] **TC-256**: "Apply Filters" button updates table
- [ ] **TC-257**: "Reset Filters" clears all filters
- [ ] **TC-258**: Filtered results accurate

### Data Table
- [ ] **TC-259**: All columns display correctly
- [ ] **TC-260**: Status badges color-coded
- [ ] **TC-261**: Amount requested formatted as currency
- [ ] **TC-262**: Amount approved formatted as currency (if applicable)
- [ ] **TC-263**: Relative dates display ("2 weeks ago")
- [ ] **TC-264**: Sortable columns work (if implemented)
- [ ] **TC-265**: Table responsive on mobile

### Pagination
- [ ] **TC-266**: 20 results per page
- [ ] **TC-267**: Page numbers display
- [ ] **TC-268**: Prev/Next buttons work
- [ ] **TC-269**: Jump to page works (if implemented)
- [ ] **TC-270**: Total results count accurate

### Actions
- [ ] **TC-271**: "View Detail" link navigates to detail page
- [ ] **TC-272**: "Download Documents" link works (if files attached)
- [ ] **TC-273**: Conditional actions based on status work

### Empty State
- [ ] **TC-274**: Helpful message displays if no reimbursements
- [ ] **TC-275**: "Submit Reimbursement" CTA visible
- [ ] **TC-276**: Links to all three form types work

---

## 7. Resource Center

### Access Control
- [ ] **TC-277**: Resource center requires login
- [ ] **TC-278**: Member-only restriction enforced
- [ ] **TC-279**: Non-members redirected with message

### Search & Filter
- [ ] **TC-280**: Search by title/description works
- [ ] **TC-281**: Category filter dropdown populated
- [ ] **TC-282**: Sort options work (Newest, Oldest, Title A-Z, Most Downloaded)
- [ ] **TC-283**: "Search" button triggers AJAX search
- [ ] **TC-284**: "Clear" button resets search/filters

### Resource Display
- [ ] **TC-285**: Grid layout responsive (3 columns desktop, 1 mobile)
- [ ] **TC-286**: Resource cards display correctly
- [ ] **TC-287**: Thumbnail images display (if available)
- [ ] **TC-288**: Title and description excerpt visible
- [ ] **TC-289**: File type icon correct (PDF, JPG, DOC, etc.)
- [ ] **TC-290**: File size displays formatted ("2.5 MB")
- [ ] **TC-291**: Download button works
- [ ] **TC-292**: Download count displays
- [ ] **TC-293**: Category tags visible

### Resource Types
- [ ] **TC-294**: File uploads (Media Library) work
- [ ] **TC-295**: External URLs work (open in new tab)
- [ ] **TC-296**: File type detection accurate

### Download Tracking
- [ ] **TC-297**: Custom download URL generated
- [ ] **TC-298**: Download count increments
- [ ] **TC-299**: User download history logged (if implemented)

### Pagination
- [ ] **TC-300**: 12 resources per page
- [ ] **TC-301**: Pagination works correctly
- [ ] **TC-302**: Total results count accurate

---

## 8. Admin Approval Interface

### Access Control
- [ ] **TC-303**: Interface requires admin capability (`manage_options`)
- [ ] **TC-304**: Non-admins redirected

### Applications List
- [ ] **TC-305**: Custom admin menu page accessible
- [ ] **TC-306**: Tabbed interface displays (All, Pending, Approved, Rejected)
- [ ] **TC-307**: Real-time application counts accurate
- [ ] **TC-308**: Tab switching works
- [ ] **TC-309**: Sortable table works
- [ ] **TC-310**: All columns display correctly

### Application Detail Modal
- [ ] **TC-311**: "View Details" button opens modal
- [ ] **TC-312**: Modal displays full application data
- [ ] **TC-313**: Personal contact information section complete
- [ ] **TC-314**: Business information section complete
- [ ] **TC-315**: Classification section accurate
- [ ] **TC-316**: Products list complete
- [ ] **TC-317**: Admin notes editable
- [ ] **TC-318**: "Save Notes" button works
- [ ] **TC-319**: Current status displayed
- [ ] **TC-320**: Submission date accurate
- [ ] **TC-321**: Action buttons appropriate for status
- [ ] **TC-322**: Modal close button works
- [ ] **TC-323**: Escape key closes modal

### Approval Workflow
- [ ] **TC-324**: "Approve Application" button triggers approval
- [ ] **TC-325**: Confirmation dialog appears (if implemented)
- [ ] **TC-326**: Approval updates `approval_status` to 'approved'
- [ ] **TC-327**: Approval date recorded
- [ ] **TC-328**: Approved by admin ID recorded
- [ ] **TC-329**: Post status changed to 'publish'
- [ ] **TC-330**: Approval email sent to applicant
- [ ] **TC-331**: Success notification displays
- [ ] **TC-332**: Page refreshes to show updated status

### Rejection Workflow
- [ ] **TC-333**: "Reject Application" button triggers rejection
- [ ] **TC-334**: Confirmation dialog appears (if implemented)
- [ ] **TC-335**: Rejection updates `approval_status` to 'rejected'
- [ ] **TC-336**: Rejection date recorded
- [ ] **TC-337**: Post status remains 'pending'
- [ ] **TC-338**: Rejection email sent with admin notes
- [ ] **TC-339**: Success notification displays
- [ ] **TC-340**: Page refreshes

### Bulk Actions
- [ ] **TC-341**: Checkbox column selectable
- [ ] **TC-342**: "Select All" checkbox works
- [ ] **TC-343**: Bulk action dropdown populated (Approve, Reject)
- [ ] **TC-344**: "Apply" button triggers confirmation
- [ ] **TC-345**: Bulk processing works correctly
- [ ] **TC-346**: Count of processed applications displayed
- [ ] **TC-347**: Success notification appears
- [ ] **TC-348**: Page refreshes after bulk action

---

## 9. Admin Reimbursements Interface

### Access Control
- [ ] **TC-349**: Interface requires admin capability
- [ ] **TC-350**: Non-admins redirected

### Reimbursement List
- [ ] **TC-351**: Filter by status works
- [ ] **TC-352**: Filter by type works (Lead, Advertising, Labels)
- [ ] **TC-353**: Filter by fiscal year works
- [ ] **TC-354**: Filter by business works
- [ ] **TC-355**: Sortable table works
- [ ] **TC-356**: All columns display correctly

### Reimbursement Detail View
- [ ] **TC-357**: Detail view accessible
- [ ] **TC-358**: All form data displayed
- [ ] **TC-359**: Submitted documents visible and downloadable
- [ ] **TC-360**: Cost breakdown clear
- [ ] **TC-361**: Admin notes editable
- [ ] **TC-362**: Approval/rejection actions available

### Approval Workflow
- [ ] **TC-363**: Approve button triggers approval
- [ ] **TC-364**: Amount approved field editable
- [ ] **TC-365**: Approval updates status to 'approved'
- [ ] **TC-366**: Approval date recorded
- [ ] **TC-367**: Approved by admin ID recorded
- [ ] **TC-368**: Approval email sent to user
- [ ] **TC-369**: Success notification displays

### Rejection Workflow
- [ ] **TC-370**: Reject button triggers rejection
- [ ] **TC-371**: Admin notes/reason required
- [ ] **TC-372**: Rejection updates status to 'rejected'
- [ ] **TC-373**: Rejection date recorded
- [ ] **TC-374**: Rejection email sent with feedback
- [ ] **TC-375**: Success notification displays

---

## 10. User Directory (Admin)

### Access Control
- [ ] **TC-376**: Directory requires admin capability
- [ ] **TC-377**: Non-admins redirected

### Search & Filter
- [ ] **TC-378**: Search by name works
- [ ] **TC-379**: Search by email works
- [ ] **TC-380**: Search by business works
- [ ] **TC-381**: Filter by role works
- [ ] **TC-382**: Filter by status works
- [ ] **TC-383**: Date range filter works
- [ ] **TC-384**: "Search" button applies filters
- [ ] **TC-385**: "Reset" button clears filters

### User Table
- [ ] **TC-386**: All columns display correctly
- [ ] **TC-387**: Gravatar images display
- [ ] **TC-388**: Email mailto link works
- [ ] **TC-389**: Associated businesses linked correctly
- [ ] **TC-390**: Last login accurate and formatted
- [ ] **TC-391**: Registration date accurate
- [ ] **TC-392**: Status badge color-coded

### Sortable Columns
- [ ] **TC-393**: Column headers clickable
- [ ] **TC-394**: Sort ASC/DESC toggle works
- [ ] **TC-395**: Sort indicators display (arrows)
- [ ] **TC-396**: URL parameters persist sort state

### Pagination
- [ ] **TC-397**: 25 users per page
- [ ] **TC-398**: Pagination controls work
- [ ] **TC-399**: "Showing X-Y of Z users" accurate
- [ ] **TC-400**: Total user count accurate

### Bulk Actions
- [ ] **TC-401**: "Select All" checkbox works
- [ ] **TC-402**: Individual checkboxes work
- [ ] **TC-403**: Bulk action dropdown populated
- [ ] **TC-404**: "Apply" button triggers confirmation
- [ ] **TC-405**: Bulk activate works
- [ ] **TC-406**: Bulk deactivate works
- [ ] **TC-407**: Bulk delete works (with cascade options)
- [ ] **TC-408**: Bulk action results displayed

### Individual Actions
- [ ] **TC-409**: "View Profile" link works
- [ ] **TC-410**: "Edit User" link to WP admin works
- [ ] **TC-411**: "View Businesses" link works
- [ ] **TC-412**: "Send Email" link works
- [ ] **TC-413**: "Suspend Account" action works
- [ ] **TC-414**: "Delete User" confirmation appears

### Export Functionality
- [ ] **TC-415**: "Export to CSV" button visible
- [ ] **TC-416**: Export includes filtered results
- [ ] **TC-417**: CSV file downloads immediately
- [ ] **TC-418**: CSV contains all user data and business associations
- [ ] **TC-419**: CSV format correct

### Statistics Dashboard
- [ ] **TC-420**: Total users count accurate
- [ ] **TC-421**: Active users count accurate
- [ ] **TC-422**: Users with businesses count accurate
- [ ] **TC-423**: New users this month count accurate
- [ ] **TC-424**: Users by role breakdown accurate

---

## 11. Public Business Directory

### Directory Access
- [ ] **TC-425**: Directory publicly accessible (no login required)
- [ ] **TC-426**: Only published/approved businesses visible
- [ ] **TC-427**: Pending and rejected businesses not visible

### Search & Filter
- [ ] **TC-428**: Search by business name works
- [ ] **TC-429**: Search by keywords works
- [ ] **TC-430**: Filter by classification works
- [ ] **TC-431**: Filter by product type works
- [ ] **TC-432**: Filter by location works
- [ ] **TC-433**: Filter by sales type works
- [ ] **TC-434**: Filters combinable (multiple filters at once)

### Sorting
- [ ] **TC-435**: Sort alphabetically (A-Z) works
- [ ] **TC-436**: Sort alphabetically (Z-A) works
- [ ] **TC-437**: Sort by newest members works
- [ ] **TC-438**: Sort by most products works

### Display
- [ ] **TC-439**: Grid view toggle works
- [ ] **TC-440**: List view toggle works
- [ ] **TC-441**: Business cards display correctly
- [ ] **TC-442**: Logos display (if uploaded)
- [ ] **TC-443**: Business name visible
- [ ] **TC-444**: DBA visible (if applicable)
- [ ] **TC-445**: Location displayed
- [ ] **TC-446**: Product tags display (limited to 5 with "show more")
- [ ] **TC-447**: Classification badges visible
- [ ] **TC-448**: "View Profile" link works
- [ ] **TC-449**: Responsive on mobile

### Pagination
- [ ] **TC-450**: Pagination works correctly
- [ ] **TC-451**: Results count accurate
- [ ] **TC-452**: Page numbers display correctly

### Empty State
- [ ] **TC-453**: Message displays if no results
- [ ] **TC-454**: Helpful guidance provided

---

## 12. Single Business Profile (Public)

### Profile Access
- [ ] **TC-455**: Profile publicly accessible
- [ ] **TC-456**: Only published/approved businesses accessible
- [ ] **TC-457**: Pending/rejected businesses return 404 or redirect

### Header Section
- [ ] **TC-458**: Business logo displays large (if uploaded)
- [ ] **TC-459**: Business name prominent
- [ ] **TC-460**: DBA visible (if applicable)
- [ ] **TC-461**: Classification badges displayed
- [ ] **TC-462**: Location (city, state) visible

### Contact Information
- [ ] **TC-463**: Phone clickable (tel: link for mobile)
- [ ] **TC-464**: Email clickable (mailto: link)
- [ ] **TC-465**: Website link opens in new tab
- [ ] **TC-466**: Social media icons display
- [ ] **TC-467**: Social media links work
- [ ] **TC-468**: Social media handles visible

### About Section
- [ ] **TC-469**: Business profile description displays
- [ ] **TC-470**: Formatting preserved
- [ ] **TC-471**: Business hours display
- [ ] **TC-472**: Address visible (if public)
- [ ] **TC-473**: "Get Directions" link to Google Maps works

### Products Section
- [ ] **TC-474**: All products listed
- [ ] **TC-475**: Products organized by category
- [ ] **TC-476**: Organic indicators display
- [ ] **TC-477**: Product attributes visible

### Additional Information
- [ ] **TC-478**: Sales types display (Local, Regional, etc.)
- [ ] **TC-479**: Years as member calculated correctly
- [ ] **TC-480**: Number of employees visible (if public)

### Actions
- [ ] **TC-481**: Share buttons work (Facebook, Twitter)
- [ ] **TC-482**: Print profile functionality works
- [ ] **TC-483**: Report/flag business link works (if implemented)

### Related Businesses
- [ ] **TC-484**: Related businesses section displays
- [ ] **TC-485**: Similar businesses based on products/location
- [ ] **TC-486**: "You might also like" suggestions relevant
- [ ] **TC-487**: Related business links work

---

## 13. Security Testing

### Authentication
- [ ] **TC-488**: Password strength enforcement works
- [ ] **TC-489**: Brute force protection active (if implemented)
- [ ] **TC-490**: Session timeout appropriate
- [ ] **TC-491**: Logout functionality works
- [ ] **TC-492**: "Remember Me" secure

### Authorization
- [ ] **TC-493**: Non-logged-in users cannot access protected pages
- [ ] **TC-494**: Users can only edit their own businesses
- [ ] **TC-495**: Business ownership verified on all actions
- [ ] **TC-496**: Admin-only pages restricted to admins
- [ ] **TC-497**: Role-based permissions enforced

### Input Validation
- [ ] **TC-498**: XSS attacks prevented (all user inputs)
- [ ] **TC-499**: SQL injection prevented (all database queries)
- [ ] **TC-500**: CSRF protection via nonces functional
- [ ] **TC-501**: File upload security enforced (type, size)
- [ ] **TC-502**: Path traversal attacks prevented
- [ ] **TC-503**: Email header injection prevented

### Data Protection
- [ ] **TC-504**: Private contact information not publicly visible
- [ ] **TC-505**: Admin notes not visible to members
- [ ] **TC-506**: Document access restricted to authorized users
- [ ] **TC-507**: Database queries use prepared statements
- [ ] **TC-508**: Output properly escaped (esc_html, esc_url, etc.)

---

## 14. Performance Testing

### Page Load Times
- [ ] **TC-509**: Homepage loads in < 3 seconds
- [ ] **TC-510**: Dashboard loads in < 3 seconds
- [ ] **TC-511**: Business directory loads in < 5 seconds
- [ ] **TC-512**: Forms load in < 2 seconds
- [ ] **TC-513**: Admin interfaces load in < 3 seconds

### AJAX Operations
- [ ] **TC-514**: Form submissions complete in < 5 seconds
- [ ] **TC-515**: Search/filter operations complete in < 2 seconds
- [ ] **TC-516**: Auto-save completes in < 1 second
- [ ] **TC-517**: File uploads progress indicator accurate

### Database Queries
- [ ] **TC-518**: No N+1 query problems
- [ ] **TC-519**: Pagination limits query results
- [ ] **TC-520**: Caching implemented for expensive queries
- [ ] **TC-521**: Database queries optimized

### File Handling
- [ ] **TC-522**: File uploads don't time out
- [ ] **TC-523**: Large files (up to 5MB) upload successfully
- [ ] **TC-524**: Multiple file uploads work
- [ ] **TC-525**: File downloads don't timeout

---

## 15. Mobile Testing

### Devices to Test
- [ ] **iOS (iPhone)**: Safari browser
- [ ] **Android**: Chrome browser
- [ ] **Tablets (iPad, Android)**: Default browsers

### Responsive Design
- [ ] **TC-526**: All pages responsive on mobile
- [ ] **TC-527**: Navigation menu mobile-friendly
- [ ] **TC-528**: Forms usable on mobile
- [ ] **TC-529**: Tables responsive (horizontal scroll or card view)
- [ ] **TC-530**: Images scale appropriately
- [ ] **TC-531**: Touch targets adequate size (44x44px minimum)

### Mobile-Specific Functionality
- [ ] **TC-532**: Phone numbers clickable (tel: links)
- [ ] **TC-533**: Email addresses clickable (mailto: links)
- [ ] **TC-534**: Zoom/pinch gestures work
- [ ] **TC-535**: Orientation changes handled gracefully
- [ ] **TC-536**: Virtual keyboard doesn't obscure inputs

### Performance on Mobile
- [ ] **TC-537**: Pages load reasonably on 3G/4G
- [ ] **TC-538**: Forms submit successfully on mobile
- [ ] **TC-539**: File uploads work on mobile
- [ ] **TC-540**: No significant layout shifts

---

## 16. Cross-Browser Testing

### Browsers to Test
- [ ] **Chrome** (latest version)
- [ ] **Firefox** (latest version)
- [ ] **Safari** (latest version)
- [ ] **Edge** (latest version)

### Compatibility Checks
- [ ] **TC-541**: Layout consistent across browsers
- [ ] **TC-542**: Forms work in all browsers
- [ ] **TC-543**: AJAX operations work in all browsers
- [ ] **TC-544**: File uploads work in all browsers
- [ ] **TC-545**: CSS styling consistent
- [ ] **TC-546**: JavaScript functionality consistent
- [ ] **TC-547**: No console errors in any browser

---

## 17. Accessibility Testing

### WCAG AA Compliance
- [ ] **TC-548**: Color contrast meets WCAG AA (4.5:1 text, 3:1 UI)
- [ ] **TC-549**: All images have alt text
- [ ] **TC-550**: Form labels properly associated with inputs
- [ ] **TC-551**: Error messages announced to screen readers
- [ ] **TC-552**: Focus indicators visible
- [ ] **TC-553**: Tab order logical
- [ ] **TC-554**: ARIA labels present on interactive elements
- [ ] **TC-555**: Headings hierarchical (H1, H2, H3, etc.)
- [ ] **TC-556**: Skip navigation link present

### Keyboard Navigation
- [ ] **TC-557**: All interactive elements keyboard accessible
- [ ] **TC-558**: Tab key navigates in logical order
- [ ] **TC-559**: Enter/Space activates buttons and links
- [ ] **TC-560**: Escape closes modals
- [ ] **TC-561**: Arrow keys work in dropdowns and lists
- [ ] **TC-562**: No keyboard traps

### Screen Reader Testing
- [ ] **TC-563**: Content readable by screen reader (NVDA/JAWS)
- [ ] **TC-564**: Form fields announced correctly
- [ ] **TC-565**: Status messages announced
- [ ] **TC-566**: Navigation structure clear
- [ ] **TC-567**: Alternative text meaningful

---

## 18. Email Testing

### Email Delivery
- [ ] **TC-568**: All emails delivered successfully
- [ ] **TC-569**: Emails not marked as spam
- [ ] **TC-570**: Sender name and address correct
- [ ] **TC-571**: Reply-to address functional

### Email Templates
- [ ] **TC-572**: Welcome email formatting correct
- [ ] **TC-573**: Application confirmation email accurate
- [ ] **TC-574**: Approval email includes dashboard link
- [ ] **TC-575**: Rejection email includes feedback
- [ ] **TC-576**: Reimbursement submission confirmation correct
- [ ] **TC-577**: Reimbursement approval notification correct
- [ ] **TC-578**: Admin notification emails informative
- [ ] **TC-579**: Password reset email functional

### Email Content
- [ ] **TC-580**: All links in emails work
- [ ] **TC-581**: Personalization correct (user name, business name, etc.)
- [ ] **TC-582**: Email copy clear and professional
- [ ] **TC-583**: Branding consistent (NMDA colors/logo)

---

## 19. Error Handling & Edge Cases

### Form Validation
- [ ] **TC-584**: Missing required fields show specific errors
- [ ] **TC-585**: Invalid email format shows error
- [ ] **TC-586**: Invalid phone format shows error
- [ ] **TC-587**: Invalid URL format shows error
- [ ] **TC-588**: File too large shows error
- [ ] **TC-589**: Invalid file type shows error
- [ ] **TC-590**: Error messages clear and actionable

### Network Issues
- [ ] **TC-591**: AJAX failures handled gracefully
- [ ] **TC-592**: Timeout errors show retry option
- [ ] **TC-593**: Offline detection (if implemented)
- [ ] **TC-594**: File upload interruption handled

### Data Issues
- [ ] **TC-595**: Empty states handled (no businesses, no reimbursements, etc.)
- [ ] **TC-596**: Missing data doesn't break displays
- [ ] **TC-597**: NULL values handled correctly
- [ ] **TC-598**: Malformed data detected and handled

### User Experience Edge Cases
- [ ] **TC-599**: Very long text truncated appropriately
- [ ] **TC-600**: Special characters in names handled
- [ ] **TC-601**: Emoji in text fields handled
- [ ] **TC-602**: Multiple rapid form submissions prevented
- [ ] **TC-603**: Browser back button doesn't cause issues

---

## 20. Regression Testing

After any bug fixes or new features:

- [ ] **TC-604**: Re-run all critical path tests
- [ ] **TC-605**: Verify bug fix doesn't break existing functionality
- [ ] **TC-606**: Test related features that might be affected
- [ ] **TC-607**: Check for new console errors
- [ ] **TC-608**: Verify database integrity maintained

---

## Testing Summary

**Total Test Cases**: 608
**Critical Test Cases**: ~200 (marked with ⭐ in detailed testing)
**Estimated Testing Time**: 60-80 hours (comprehensive)
**Minimum Testing Time**: 20-30 hours (critical paths only)

### Testing Priorities

**Priority 1 - Critical (Must Test)**:
- User registration and login
- Business application submission
- Dashboard access and display
- All three reimbursement forms submission
- Admin approval workflow
- Security and authorization
- File upload functionality

**Priority 2 - Important (Should Test)**:
- Edit profile functionality
- My Reimbursements page
- Resource Center
- User Directory (admin)
- Email notifications
- Mobile responsiveness

**Priority 3 - Nice to Have (Can Test)**:
- Public business directory
- Single business profiles
- Advanced filtering
- Bulk actions
- Export functionality
- Analytics (if implemented)

---

## Bug Reporting Template

When reporting bugs, include:

**Bug ID**: BUG-###
**Title**: Brief descriptive title
**Priority**: Critical / High / Medium / Low
**Test Case**: TC-### (from this checklist)
**Environment**: Browser, OS, Device
**Steps to Reproduce**:
1. Step one
2. Step two
3. etc.

**Expected Result**: What should happen
**Actual Result**: What actually happened
**Screenshots**: Attach if applicable
**Console Errors**: Copy any JavaScript errors
**Additional Notes**: Any other relevant information

---

## Sign-Off

Once testing is complete:

**Tested By**: ___________________
**Date**: ___________________
**Pass Rate**: ___/608 tests passed
**Critical Bugs Found**: ___
**Total Bugs Found**: ___
**Ready for Production**: [ ] Yes [ ] No

---

**Next Steps After Testing**:
1. Fix all critical bugs
2. Fix high-priority bugs
3. Document known issues
4. Prepare staging environment
5. Conduct user acceptance testing (UAT)
6. Create production deployment plan
7. Schedule go-live date

---

**Document Version**: 1.0
**Last Updated**: November 21, 2025
