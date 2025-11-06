# Phase 2 Analysis: Existing Portal Review

## Executive Summary

Analysis of the existing Node.js/Express portal application to inform WordPress Phase 2 development. This document provides detailed insights into the current system's data structures, forms, workflows, and features that need to be replicated in WordPress.

---

## 1. Registration & Application Form Analysis

### Registration Form Overview
**File**: `register.html` (1,077 lines)

The application form is extensive and divided into multiple sections:

#### Section 1: Personal Contact Information (Private)
Fields marked as **NOT** posted on website:

**Responsible Officer/Owner:**
- First Name / Last Name
- Primary Contact checkbox

**Primary Contact Person:**
- First Name / Last Name
- Best Contact Phone (format: XXX-XXX-XXXX)
- Mailing Address (line 1 & 2)
- City / State / Zip
- Personal Email Address

#### Section 2: Business Contact Information (Public)
Fields marked as **WILL** be posted on website:

**Business Identity:**
- Licensee (Business Legal Name) - Required
- DBA (Doing Business As) - Optional

**Registered Business Address:**
- Business Physical Address (with note about public display)
- Address Line 2
- City / State / Zip
- Address Type:
  - Open to the public during regular business hours
  - Open to the public with a reservation
  - Not open to the public (doesn't appear on website)
  - Other
- Business Phone (format: XXX-XXX-XXXX)
- Business Email
- Website (if applicable)

**Social Media:**
- Facebook (checkbox + handle field)
- Instagram (checkbox + handle field)
- Twitter (checkbox + handle field)
- Pinterest (checkbox + handle field)
- Other (checkbox + handle field)

#### Section 3: Additional Business Information

**For Public Display:**
- Business Logo Upload (currently hidden in HTML)
- Profile/Description (textarea)
- Business Hours (textarea with placeholder example)
- Products Found (currently hidden)

**Additional Addresses System:**
- Dynamic address table with Add/Remove functionality
- Fields per additional address:
  - Address Name
  - Full address (line 1, line 2, city, state, zip)
  - Address Type (same options as primary)
  - Reservation Instructions (conditional)
  - Other Instructions (conditional)

**For NMDA Records Only:**
- Additional Info About Location (textarea)
- Number of Employees
- Type of Sales (multiple checkboxes):
  - Local
  - Regional
  - In-State
  - National
  - International

#### Section 4: Logo Classification (Primary Categories)

Three main classifications:

1. **NEW MEXICO – Grown with Tradition®**
   - For farmers/ranchers: produce, nuts, livestock, poultry, meat products, horticultural products

2. **NEW MEXICO – Taste the Tradition®**
   - For manufacturers: 51% processed/manufactured in NM
   - Includes: sauces, seasonings, dried foods, beverages, baked goods, sweets

3. **NEW MEXICO – Grown/Taste Associate Member**
   - Retailers with 3+ Taste/Grown products
   - Restaurants with NM ingredients
   - Agritourism operations
   - Artisan/crafted products (51% agriculture origin by weight, NM grown)
   - Pet food manufacturers
   - Supporting organizations

#### Section 5: Product Ingredient Indicators

**Contains (Chile Types):**
- Green Chile
- Red Chile
- Bell Pepper (red, yellow, orange, green)
- Jalapeno
- Chile de Arbol
- Cayenne
- Paprika
- Chimayo
- Ancho
- Poblano
- Pasilla Chile
- Not Applicable

**Operation Types:**
- Apiary
- Dairy
- Nursery

#### Section 6: Grown with Tradition® Products (Extensive!)

**Produce** (with Organic sub-option for each):
- Apples
- Cabbages
- Chiles or Peppers
- Lettuces
- Onions
- Potatoes
- Pumpkins
- Watermelons
- Peanuts
- Other Legumes and Beans (soy, lentils, mesquite, peas)
- Other (with text description field)

**Nuts** (with Organic sub-option):
- Pecans
- Pistachios
- Pinon (Pine Nuts)
- Other (with text description)

**Livestock and Poultry** (various sub-options):
- Cattle (Organic, Beef, Dairy)
- Pigs (Organic)
- Sheep/Lambs (Conventional, Organic, Meat, Dairy, Fiber)
- Goats (Conventional, Organic, Meat, Dairy, Fiber)
- Chickens (Organic, Free Range, Meat, Layer)
- Turkeys (similar sub-options)
- Ducks, Geese, Quail, Other Poultry

**Meat Products:**
- Fresh, Frozen, Smoked, Cured options for:
  - Beef, Pork, Lamb, Goat, Poultry, Game, Other

**Dairy Products:**
- Raw Milk, Pasteurized Milk, Cheese, Yogurt, Ice Cream, Other

**Other Products:**
- Eggs, Honey, Fiber/Wool, Pet Food, Other

#### Section 7: Taste the Tradition® Products

**Beverages:**
- Beer, Wine, Spirits
- Coffee, Tea
- Juice, Cider
- Soft Drinks, Energy Drinks
- Other

**Prepared Foods:**
- Sauces & Salsas
- Seasonings & Spices
- Baked Goods
- Confections
- Frozen Foods
- Dried Foods
- Canned Foods
- Snack Foods
- Other

**Associate Member Details:**
(Various sub-categories based on classification)

---

## 2. Database Schema Analysis

### Current Database Structure

#### Business Table (`business`)
**Total Fields**: ~250+ fields
**Boolean Fields**: 202 tinyint fields

**Core Fields:**
```sql
BusinessId (varchar(50) - UUID)
BusinessName (varchar(100))
Category (varchar(50))
DBA (varchar(100))
```

**Address Fields (Primary):**
```sql
AddressName (varchar(250))
AddressType (varchar(100))
Address, Address2 (varchar(100))
City, State, Zip (varchar(100))
Phone, Email, Website (varchar(100))
```

**Contact Information:**
```sql
OwnerFirstName, OwnerLastName
ContactFirstName, ContactLastName
ContactPhone, ContactEmail
ContactAddress, ContactAddress2
ContactCity, ContactState, ContactZip
```

**Social Media:**
```sql
Facebook (tinyint)
FacebookHandle (varchar(100))
Instagram (tinyint)
InstagramHandle (varchar(100))
Twitter (tinyint)
TwitterHandle (varchar(100))
Pinterest (tinyint)
PinterestHandle (varchar(100))
SocialOther (tinyint)
SocialOtherHandle (varchar(100))
```

**Classifications:**
```sql
ClassGrown (tinyint)
ClassTaste (tinyint)
ClassAssociate (tinyint)
```

**Product Type Booleans (Examples of 202 total):**
```sql
GrownApples (tinyint)
GrownApplesOrganic (tinyint)
GrownChiles (tinyint)
GrownChilesOrganic (tinyint)
GrownCattle (tinyint)
GrownCattleOrganic (tinyint)
GrownCattleBeef (tinyint)
GrownCattleDairy (tinyint)
TasteBeer (tinyint)
TasteWine (tinyint)
TasteSauces (tinyint)
... (and 190+ more)
```

#### Business Address Table (`business_address`)
Separate table for additional addresses beyond the primary business address.

**Key Fields:**
```sql
AddressId (varchar(50))
BusinessId (varchar(50))
AddressName (varchar(250))
AddressType (varchar(100))
Address, Address2, City, State, Zip
ReservationInstructions (text)
OtherInstructions (text)
```

#### Reimbursement Tables

##### Lead Generation (`csr_lead`)
```sql
LeadId (varchar(50) - UUID)
BusinessId (varchar(50))
FirstName, LastName, Phone, Email
MailingAddress, MailingCity, MailingState, MailingZip
PhysicalAddress, PhysicalCity, PhysicalState, PhysicalZip
EventType (varchar(1000))
EventLocation (text)
EventDescription (text)
EventWebsite (varchar(500))
EventDates (text)
EventCosts (text)
FundingExplanation (text)
CollectingMethod (text)
PreviousReimbursement (tinyint)
Commitment (tinyint)
Approved (tinyint)
Rejected (tinyint)
AdminInitials (varchar(5))
DateSubmitted (datetime)
DateApproved (datetime)
DateRejected (datetime)
UserId (varchar(50))
```

##### Advertising Reimbursement (`csr_advertising`)
```sql
AdvertisingId (varchar(50))
BusinessId (varchar(50))
CompanyName (varchar(100))
FirstName, LastName, Phone, Email
MailingAddress (full address fields)
PhysicalAddress (full address fields)
MediaType (varchar(45))
MediaTypeOther (text)
CollaboratingCompanies (text)
FundingExplanation (text)
Differentiation (text)
TargetMarkets (text)
AdvertisingDates (text)
ExpectedReach (text)
CostBreakdown (text)
Approved, Rejected (tinyint)
AdminInitials (varchar(5))
DateSubmitted, DateApproved, DateRejected (datetime)
```

##### Labels Reimbursement (`csr_labels`)
Similar structure to advertising, with label-specific fields.

---

## 3. API Endpoints Analysis

### Authentication Endpoints
- `POST /Authenticate` - User login
- `POST /RecoverPwd` - Password recovery
- `GET /UpdateAuthToken` - Refresh auth token
- `POST /AddUser` - Create new user
- `POST /DeleteUser` - Remove user

### Business/Company Endpoints
- `GET /GetBusinesses` - List all businesses
- `GET /GetAllBusinesses` - API-authenticated list (with rate limiting)
- `GET /GetBusinessById` - Single business details
- `GET /GetBusinessUsers` - Users associated with business
- `GET /GetBusinessAddresses` - All addresses for business
- `GET /GetBusinessApprovals` - Pending approval queue
- `POST /AddBusiness` - Create new business
- `POST /UpdateBusiness` - Update business details
- `POST /DeleteBusiness` - Remove business
- `POST /DeleteBusinessAddress` - Remove address
- `POST /UpdateBusinessCategory` - Update classification

### Reimbursement Endpoints

**Lead Generation:**
- `GET /GetReimburseLead` - List lead reimbursements
- `GET /GetReimburseLeadApprovals` - Pending approvals
- `GET /GetReimburseLeadById` - Single request
- `GET /GetReimburseLeadByBusinessId` - All requests for business
- `POST /AddReimburseLead` - Submit new request
- `POST /UpdateReimburseLead` - Update request (approve/reject)

**Advertising:**
- Same pattern as Lead with `/GetReimburseAdvertising*` endpoints
- `POST /AddReimburseAdvertising`
- `POST /UpdateReimburseAdvertising`

**Labels:**
- Same pattern with `/GetReimburseLabels*` endpoints
- `POST /AddReimburseLabels`
- `POST /UpdateReimburseLabels`

### Other Endpoints
- `GET /GetGroupTypes` - List business categories
- `GET /GetCompanyGroups` - Company category associations

---

## 4. Key Features & Workflows

### Business Registration Workflow
1. User completes extensive registration form
2. Selects primary classification (Grown/Taste/Associate)
3. Conditional sections display based on classification
4. Multiple product types selectable (202 boolean fields)
5. Can add multiple additional addresses
6. Form submission creates business record
7. Admin review and approval process
8. Business appears on public directory once approved

### Address Management
- Primary business address (required)
- Unlimited additional addresses
- Each address has type (public hours, by reservation, not public, other)
- Conditional fields based on address type
- Addresses can be edited/deleted

### Reimbursement Submission Workflow
1. User selects reimbursement type (Lead/Advertising/Labels)
2. Fills type-specific form
3. Provides cost breakdown and funding explanation
4. Describes lead collection method
5. Commits to providing lead data
6. Submits with timestamp
7. Admin reviews and approves/rejects
8. Status tracked with dates and admin initials

### User-Business Relationships
- UUID-based user and business IDs
- Users can be associated with multiple businesses
- One user record can manage multiple business profiles
- AuthToken system for session management

---

## 5. WordPress Migration Strategy

### Custom Post Type Mapping

**`nmda_business` Post Type:**
- **Post Title**: Business Name
- **Post Content**: Business Profile/Description
- **Featured Image**: Business Logo
- **Custom Taxonomies**:
  - `business_category`: Three main classifications
  - `product_type`: Hierarchical taxonomy for products

**Post Meta Fields:**
- Core business info (DBA, Phone, Email, Website)
- Contact information
- Social media handles
- Business hours
- Employee count
- Sales types
- All boolean product flags

### Handling 202 Boolean Product Fields

**Option 1: Taxonomy-Based (Recommended)**
- Create hierarchical `product_type` taxonomy
- Parent terms: Produce, Nuts, Livestock, Dairy, Beverages, etc.
- Child terms: Apples, Pecans, Cattle, etc.
- Use term meta for attributes (Organic, Free Range, etc.)
- Benefits: Filterable, searchable, scalable

**Option 2: JSON Meta Field**
- Store as JSON in single post meta field
- Indexed for search
- Benefits: Flexible, reduces database rows

**Option 3: ACF Flexible Content**
- Use ACF Pro flexible content fields
- Grouped by product category
- Benefits: User-friendly admin interface

**Recommended: Hybrid Approach**
- Use taxonomies for main product categories
- Use term meta for product attributes
- Store in custom table for complex filtering

### Custom Tables Usage

Our Phase 1 custom tables map perfectly:

**`wp_nmda_user_business`**:
- Replaces user-business associations
- Adds role-based access (owner, manager, viewer)
- Tracks invitation workflow

**`wp_nmda_business_addresses`**:
- Replaces separate address table
- Stores multiple addresses per business
- Includes address type and instructions

**`wp_nmda_reimbursements`**:
- Consolidates all three reimbursement types
- Stores form data as JSON
- Tracks approval workflow and fiscal year

**`wp_nmda_communications`**:
- New feature: in-portal messaging
- Replaces email-only communication
- Provides audit trail

**`wp_nmda_field_permissions`**:
- New feature: granular edit permissions
- Controls which fields require approval
- Manages admin-only fields

---

## 6. Form Design Requirements

### Multi-Step Form Implementation

**Step 1: Personal Contact**
- All private contact fields
- Validation: email format, phone format

**Step 2: Business Information**
- Business name, DBA, address
- Social media handles
- Validation: required business name

**Step 3: Classifications & Products**
- Select classification (shows/hides sections)
- Product selection (conditional based on classification)
- Progress indicator

**Step 4: Additional Details**
- Profile description
- Business hours
- Additional addresses (dynamic add/remove)
- Number of employees

**Step 5: Review & Submit**
- Summary of all entered data
- Edit capability for any section
- Final submission

### Product Selection UI

**Recommended Interface:**
- Accordion-style sections
- Checkboxes with nested sub-options
- Search/filter capability for large lists
- "Select All" options for categories
- Visual indication of selected items
- Mobile-responsive design

---

## 7. Admin Interface Requirements

### Business Approval Dashboard
- Queue of pending business applications
- Filterable by:
  - Classification type
  - Submission date
  - Product categories
- Quick actions: Approve, Reject, Request Changes
- Bulk approval capability
- Detailed view with all form data
- Side-by-side comparison for edits

### Reimbursement Management
- Separate tabs for Lead, Advertising, Labels
- Filter by:
  - Status (submitted, approved, rejected)
  - Fiscal year
  - Business
  - Date range
- Status indicators with color coding
- Admin notes field
- Approval workflow with initials and dates
- Export to CSV for accounting

### Document Management
- Upload interface for required documents
- Document status indicators
- Non-blocking submission (can submit without all docs)
- Flag missing documents for follow-up
- Document versioning

---

## 8. Phase 2 Development Priorities

### Must-Have for MVP

1. **Business Application Form**
   - Multi-step form implementation
   - Product selection interface
   - Address management
   - Draft saving capability

2. **Admin Approval Interface**
   - Business application review
   - Approve/reject workflow
   - Pending queue management

3. **Member Dashboard**
   - Business profile overview
   - Quick edit capability
   - Reimbursement status tracker

4. **Reimbursement Forms**
   - Three form types (Lead, Advertising, Labels)
   - Cost documentation
   - Submission workflow

5. **Public Directory**
   - Filterable business listing
   - Category/product filtering
   - Map integration
   - Business detail pages

### Nice-to-Have for V1.1

1. **Advanced Search**
   - Multi-criteria filtering
   - Location-based search
   - Product-based search

2. **User Invitation System**
   - Email invitations
   - Role assignment
   - Acceptance workflow

3. **Communication Module**
   - In-portal messaging
   - Email notifications
   - Message threads

4. **Analytics Dashboard**
   - User activity tracking
   - Application metrics
   - Reimbursement statistics

---

## 9. Technical Specifications

### Form Validation Rules

**Email Fields:**
- Format: standard email validation
- Unique for user registration

**Phone Fields:**
- Format: XXX-XXX-XXXX
- jQuery mask plugin for formatting
- Optional for most fields

**Address Fields:**
- State: 2-letter code or full name
- Zip: 5-digit or 9-digit format

**Business Classification:**
- At least one classification must be selected
- Conditional validation based on selection

**Product Selection:**
- No minimum required
- Warning if no products selected

### File Upload Specifications

**Logo Upload:**
- Formats: JPG, PNG, SVG
- Max size: 2MB
- Dimensions: Min 200x200, Max 1000x1000
- Auto-resize and optimization

**Document Uploads:**
- Formats: PDF, JPG, PNG, DOCX
- Max size: 10MB per file
- Multiple files allowed
- Drag-and-drop interface

### Performance Considerations

**Large Form Handling:**
- AJAX-based submission
- Progress indicators
- Auto-save draft functionality
- Session timeout warnings

**Product Taxonomy:**
- Efficient querying with proper indexes
- Caching of taxonomy terms
- Lazy loading for large lists

**Search & Filtering:**
- Implement search index
- Use WP_Query optimization
- Consider ElasticSearch for advanced search

---

## 10. Migration Checklist

### Data Migration Tasks

- [ ] Export existing business data from MySQL
- [ ] Map UUID to WordPress post IDs
- [ ] Import business records as `nmda_business` posts
- [ ] Create product taxonomy terms
- [ ] Assign products to businesses
- [ ] Import additional addresses to custom table
- [ ] Import reimbursement records
- [ ] Create user accounts from existing user table
- [ ] Map user-business relationships
- [ ] Verify data integrity

### Content Migration

- [ ] Business logos/images
- [ ] Profile descriptions
- [ ] Social media handles
- [ ] Contact information
- [ ] Historical reimbursement data

### Validation Steps

- [ ] Count verification (old vs new)
- [ ] Relationship verification
- [ ] Data sampling and spot checks
- [ ] User login testing
- [ ] Business display testing

---

## 11. Recommendations for Phase 2

### Simplification Opportunities

1. **Product Selection**
   - Consider grouping rarely-used options
   - Implement "Other" with text description for edge cases
   - Reduce from 202 to ~50-75 core categories with attributes

2. **Form Length**
   - Break into smaller, more manageable steps
   - Allow saving and returning later
   - Mark truly optional vs required clearly

3. **User Experience**
   - Add progress bars
   - Show estimated time to complete
   - Provide contextual help text
   - Include example data

### Enhancement Opportunities

1. **Application Pre-fill**
   - Allow importing data from previous applications
   - Pre-populate from business registration data
   - Remember common selections

2. **Collaborative Editing**
   - Multiple users can edit different sections
   - Lock mechanism for simultaneous editing
   - Change tracking and audit log

3. **Mobile Optimization**
   - Simplified mobile forms
   - Touch-friendly controls
   - Responsive design throughout

4. **Automation**
   - Auto-approval for trusted members
   - Automated email sequences
   - Reminder system for incomplete applications

---

## Conclusion

The existing portal provides a solid foundation for the WordPress migration. The extensive product taxonomy (202 boolean fields) will be the most challenging aspect to migrate, but using WordPress taxonomies with term meta provides a scalable solution.

Key success factors for Phase 2:
- Progressive enhancement of the form UX
- Efficient product selection interface
- Robust admin approval workflows
- Clear data migration strategy
- Comprehensive testing plan

**Next Steps:**
1. Review this analysis with stakeholders
2. Confirm product taxonomy simplification approach
3. Create detailed wireframes for forms
4. Begin template development for Phase 2
