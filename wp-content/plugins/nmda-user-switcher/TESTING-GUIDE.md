# NMDA User Switcher - Testing Guide

This guide will walk you through testing the plugin to ensure it works correctly with your NMDA Portal.

## Prerequisites

Before testing, ensure:
- [ ] Local by Flywheel site is **running**
- [ ] You're logged in as an **administrator**
- [ ] You have at least **2-3 test users** with different roles
- [ ] Some test users have **associated businesses** in the portal
- [ ] NMDA Understrap theme is active

## Installation & Activation

### Step 1: Activate the Plugin

**Using WP-CLI:**
```bash
"C:\wp-cli\wp.bat" plugin activate nmda-user-switcher
```

**Or via WordPress Admin:**
1. Go to **Plugins → Installed Plugins**
2. Find "NMDA User Switcher"
3. Click **Activate**

### Step 2: Verify Database Table Created

Check that the logging table was created:

```bash
"C:\wp-cli\wp.bat" db query "SHOW TABLES LIKE 'wp_nmda_user_switches';"
```

You should see: `wp_nmda_user_switches`

### Step 3: Verify Table Structure

```bash
"C:\wp-cli\wp.bat" db query "DESCRIBE wp_nmda_user_switches;"
```

Should show columns: `id`, `admin_id`, `switched_to_user_id`, `switch_time`, `switch_back_time`, `ip_address`, `user_agent`

## Test Cases

### Test 1: Admin Bar Integration

**Expected:** "Switch User" menu appears in admin bar

1. Log in as administrator
2. Look at the WordPress admin bar (top of screen)
3. You should see a "Switch User" menu item

**✓ Pass if:** Menu appears in admin bar
**✗ Fail if:** Menu is missing or not clickable

---

### Test 2: User Search Functionality

**Expected:** Search box allows finding users

1. Click "Switch User" in admin bar
2. You should see a search input box
3. Type a few letters of a test user's name
4. Wait for autocomplete results

**✓ Pass if:**
- Search box appears
- Results load within 1-2 seconds
- User details display (name, email, business count)

**✗ Fail if:**
- No search box
- No results appear
- JavaScript errors in console

---

### Test 3: Switch to User (Business Owner)

**Expected:** Switch works and shows user context

**Setup:** Find a user who owns at least one business

1. Search for and click on a business owner user
2. You should be redirected to the member dashboard

**Verify:**
- [ ] A **red/brown banner** appears at top of page showing:
  - "Viewing as: [User Name]"
  - User's email address
  - Number of businesses
  - List of associated businesses with roles
- [ ] Admin bar is still visible
- [ ] Dashboard shows **only that user's businesses**
- [ ] URL changed (may include switch parameters)

**✓ Pass if:** All items above are correct
**✗ Fail if:** Banner missing, wrong user shown, or admin bar hidden

---

### Test 4: Business Relationship Display

**Expected:** Banner shows correct business associations

While switched to a user with businesses:

1. Look at the banner at top of page
2. Click to expand business list (if collapsed)

**Verify:**
- [ ] All user's businesses are listed
- [ ] Each business shows the user's role:
  - Owner (gold badge)
  - Manager (light badge)
  - Viewer (gray badge)
- [ ] Business count is accurate

**✓ Pass if:** All businesses and roles match database
**✗ Fail if:** Missing businesses or wrong roles

---

### Test 5: User Dashboard View

**Expected:** Dashboard reflects switched user's perspective

While switched to a user:

1. Navigate to the Member Dashboard page
2. Check what's displayed

**Verify:**
- [ ] Only shows businesses **that user** has access to
- [ ] Reimbursements shown are **that user's**
- [ ] "Edit Profile" links work for businesses where user is Owner/Manager
- [ ] Resources accessible if user is approved member

**✓ Pass if:** Everything shows from user's perspective
**✗ Fail if:** Seeing other users' data or admin-only content

---

### Test 6: Profile Editing Permissions

**Expected:** Edit permissions match user's role

While switched to a business **Owner**:

1. Go to Edit Profile for one of their businesses
2. Try to make changes

**Verify:**
- [ ] Can access edit form
- [ ] Can see all editable fields
- [ ] Save button is enabled
- [ ] Changes save successfully (test with a non-critical field)

While switched to a business **Viewer**:

1. Try to access Edit Profile for a business they can only view
2. Should be blocked or see read-only view

**✓ Pass if:** Permissions match role exactly
**✗ Fail if:** Wrong permissions or can edit when shouldn't

---

### Test 7: Switch Back to Admin

**Expected:** Can easily return to admin view

While switched to any user:

**Method A - Banner Button:**
1. Click **"Switch Back to Admin"** button in the banner
2. Should redirect to wp-admin

**Method B - Admin Bar:**
1. Click on "Viewing as: [User]" in admin bar
2. Select "Switch Back to Admin"

**Verify:**
- [ ] Returns to admin user account
- [ ] Banner disappears
- [ ] Admin bar still visible
- [ ] Can see all users/businesses again

**✓ Pass if:** Switch back works via both methods
**✗ Fail if:** Stuck in switched view or errors occur

---

### Test 8: Switch to Another User Without Switching Back

**Expected:** Can switch directly from one user to another

1. Switch to User A
2. In admin bar, click "Switch to Another User"
3. Search for and select User B

**Verify:**
- [ ] Switches directly to User B
- [ ] Banner updates to show User B's name
- [ ] Dashboard shows User B's businesses
- [ ] No errors or stuck sessions

**✓ Pass if:** Direct switching works smoothly
**✗ Fail if:** Must switch back first or errors occur

---

### Test 9: Recent Switches Menu

**Expected:** Quick access to recently viewed users

1. Switch to 2-3 different users
2. Switch back to admin
3. Click "Switch User" → "Recent"

**Verify:**
- [ ] Shows list of recently viewed users (up to 5)
- [ ] Each user shows name and email
- [ ] Clicking a user switches to them immediately
- [ ] No duplicate entries

**✓ Pass if:** Recent list is accurate and functional
**✗ Fail if:** Empty, duplicates, or wrong users

---

### Test 10: Users List Table Integration

**Expected:** "Switch To" link in users table

1. Go to **Users → All Users**
2. Hover over any non-admin user row
3. Look at row actions (Edit | Delete | etc.)

**Verify:**
- [ ] "Switch To" link appears
- [ ] Link is styled in red (NMDA brand color)
- [ ] Clicking it switches to that user
- [ ] After switching, returns to users list page

**✓ Pass if:** Link works from users table
**✗ Fail if:** Link missing or doesn't work

---

### Test 11: Dashboard Widget

**Expected:** Activity widget shows stats

1. Switch back to admin
2. Go to **Dashboard**
3. Look for "User Switcher Activity" widget

**Verify:**
- [ ] Widget is visible on dashboard
- [ ] Shows statistics:
  - Total switches
  - Switches today
  - Switches this week
  - Active sessions
- [ ] Shows recent activity list
- [ ] "View Full Activity Log" link works

**✓ Pass if:** Widget displays and stats are accurate
**✗ Fail if:** Widget missing or shows wrong data

---

### Test 12: Activity Logging

**Expected:** All switches are logged to database

After performing several switches:

```bash
"C:\wp-cli\wp.bat" db query "SELECT * FROM wp_nmda_user_switches ORDER BY switch_time DESC LIMIT 5;"
```

**Verify:**
- [ ] Each switch has a record
- [ ] `admin_id` is your admin user ID
- [ ] `switched_to_user_id` matches who you switched to
- [ ] `switch_time` is accurate
- [ ] `switch_back_time` is set when you switched back
- [ ] `ip_address` is recorded
- [ ] `user_agent` is recorded

**✓ Pass if:** All switches logged correctly
**✗ Fail if:** Missing logs or incorrect data

---

### Test 13: Security - Prevent Admin Switch

**Expected:** Cannot switch to other administrators

**Setup:** Create a second admin user for testing

1. Try to search for another admin user
2. Try to switch to them

**Verify:**
- [ ] Other admins don't appear in search results
- [ ] No "Switch To" link on admin users in users table
- [ ] Direct URL switch attempt fails with error message

**✓ Pass if:** Completely blocked from switching to admins
**✗ Fail if:** Can switch to other admins

---

### Test 14: Session Timeout

**Expected:** Sessions expire after 12 hours (configurable)

**Note:** This is a long test. To speed up:

1. Temporarily change timeout to 60 seconds:
```bash
"C:\wp-cli\wp.bat" option update nmda_user_switcher_session_timeout 60
```

2. Switch to a user
3. Wait 60+ seconds
4. Refresh the page

**Verify:**
- [ ] Session expires
- [ ] Automatically switched back to admin
- [ ] Or shows message about expired session

5. Reset timeout back to 12 hours:
```bash
"C:\wp-cli\wp.bat" option update nmda_user_switcher_session_timeout 43200
```

**✓ Pass if:** Session expires as configured
**✗ Fail if:** Session never expires

---

### Test 15: NMDA Function Integration

**Expected:** All NMDA custom functions work with switched user

While switched to a test user:

Check these functions return correct data:

1. **User Businesses:**
```php
// Add to functions.php temporarily:
add_action('wp_footer', function() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $businesses = nmda_get_user_businesses($user_id);
        echo '<!-- User ID: ' . $user_id . ', Businesses: ' . count($businesses) . ' -->';
    }
});
```

View page source and check comment shows:
- Correct switched user ID
- Correct business count for that user

2. **Business Access:**
Test `nmda_user_can_access_business()` returns true for user's businesses and false for others

3. **User Role:**
Test `nmda_get_user_business_role()` returns correct role (owner/manager/viewer)

**✓ Pass if:** All functions use switched user ID correctly
**✗ Fail if:** Functions return data for admin instead of switched user

---

### Test 16: Visual Styling

**Expected:** Branded styling matches NMDA design

**Check Banner:**
- [ ] Background gradient: Red to Brown
- [ ] Text color: White
- [ ] "Switch Back" button: Gold (#f5be29)
- [ ] Button hover: Slightly lighter gold
- [ ] Role badges: Gold for owner, light for manager, gray for viewer

**Check Admin Bar:**
- [ ] "Switch User" menu integrates cleanly
- [ ] "Viewing as" indicator has gold background
- [ ] Search box is styled and functional

**Check Search Results:**
- [ ] Clean, readable list
- [ ] Hover effect on items
- [ ] No layout issues or overflow

**✓ Pass if:** All styling looks professional and branded
**✗ Fail if:** Broken layouts, wrong colors, or poor UX

---

### Test 17: Responsive Design

**Expected:** Works on mobile and tablet sizes

1. Switch to a user
2. Resize browser to mobile width (375px)
3. Resize to tablet width (768px)

**Verify:**
- [ ] Banner remains visible and readable
- [ ] Button doesn't overflow
- [ ] Business list wraps properly
- [ ] Admin bar integration works on mobile
- [ ] No horizontal scrolling

**✓ Pass if:** Responsive at all screen sizes
**✗ Fail if:** Broken layout or hidden elements

---

### Test 18: AJAX Errors Handling

**Expected:** Graceful error handling

**Test with network disabled:**
1. Open browser DevTools → Network tab
2. Set throttling to "Offline"
3. Try to search for users

**Verify:**
- [ ] Shows error message (not blank)
- [ ] Doesn't break the page
- [ ] Can retry when back online

**✓ Pass if:** Errors handled gracefully
**✗ Fail if:** Page breaks or no feedback

---

### Test 19: Multiple Browsers

**Expected:** Works in all major browsers

Test in:
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari (if on Mac)

**Verify:**
- [ ] Switching works in all browsers
- [ ] Banner displays correctly
- [ ] Cookies/sessions work
- [ ] No console errors

**✓ Pass if:** Consistent across browsers
**✗ Fail if:** Browser-specific issues

---

### Test 20: Deactivation Cleanup

**Expected:** Clean deactivation without orphaned data

1. Note your current active session
2. Deactivate the plugin:
```bash
"C:\wp-cli\wp.bat" plugin deactivate nmda-user-switcher
```

3. Check database:
```bash
"C:\wp-cli\wp.bat" db query "SELECT * FROM wp_usermeta WHERE meta_key LIKE '_nmda_%';"
```

**Verify:**
- [ ] User meta cleaned up
- [ ] Transients removed
- [ ] No PHP errors
- [ ] Table `wp_nmda_user_switches` still exists (logs preserved)

4. Reactivate:
```bash
"C:\wp-cli\wp.bat" plugin activate nmda-user-switcher
```

**✓ Pass if:** Clean deactivation and reactivation
**✗ Fail if:** Errors or orphaned data

---

## Performance Tests

### Load Time Impact

**Expected:** Minimal performance impact

1. Install Query Monitor plugin (for testing)
2. Measure page load time **without** switcher active
3. Measure page load time **with** user switched
4. Compare times

**✓ Pass if:** Less than 50ms difference
**✗ Fail if:** Significant slowdown (200ms+)

---

### Database Query Count

**Expected:** Minimal extra queries

Using Query Monitor:

1. Check query count on dashboard (not switched)
2. Switch to user and check dashboard query count
3. Compare

**✓ Pass if:** 1-3 additional queries only
**✗ Fail if:** Many extra queries (10+)

---

## Compatibility Tests

### Test with NMDA Portal Features

While switched to a user, test these NMDA features:

**Applications:**
- [ ] View application status
- [ ] Submit new application (if applicable)
- [ ] Upload documents

**Reimbursements:**
- [ ] View reimbursement list
- [ ] Submit lead reimbursement
- [ ] Submit advertising reimbursement
- [ ] Submit labels reimbursement

**Resource Center:**
- [ ] Access resources (if approved member)
- [ ] Download files
- [ ] View restricted content

**Directory:**
- [ ] View business directory
- [ ] Search businesses
- [ ] Filter by category

**Profile Editing:**
- [ ] Edit business info
- [ ] Update contact details
- [ ] Manage social media links
- [ ] Update product types

**✓ Pass if:** All features work from user's perspective
**✗ Fail if:** Features break or show wrong data

---

## Test Results Summary

After completing all tests, fill out this summary:

### Passed Tests: _____ / 20

### Critical Failures:
(List any test failures that prevent core functionality)

1.
2.
3.

### Minor Issues:
(List any cosmetic or non-critical issues)

1.
2.
3.

### Browser Compatibility:
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari

### Overall Assessment:
- [ ] **Ready for Production** - All critical tests pass
- [ ] **Needs Minor Fixes** - Some minor issues to address
- [ ] **Needs Major Fixes** - Critical functionality broken

---

## Debugging Tips

### Check Plugin is Loaded

```bash
"C:\wp-cli\wp.bat" plugin list | findstr nmda-user-switcher
```

Should show: `active`

### Check for PHP Errors

```bash
"C:\wp-cli\wp.bat" eval "error_log('NMDA User Switcher Test');"
```

Then check: `wp-content/debug.log`

### Check JavaScript Console

1. Open browser DevTools (F12)
2. Go to Console tab
3. Look for errors related to `nmda` or `switcher`

### Verify Hooks Are Registered

```bash
"C:\wp-cli\wp.bat" eval "do_action('init'); echo 'Hooks loaded';"
```

### Check Current User Context

```bash
"C:\wp-cli\wp.bat" eval "echo get_current_user_id();"
```

---

## Rollback Plan

If critical issues are found:

1. **Deactivate Plugin:**
```bash
"C:\wp-cli\wp.bat" plugin deactivate nmda-user-switcher
```

2. **Document Issue:**
- What test failed
- Error messages
- Steps to reproduce

3. **Keep Logs:**
Database table is preserved for debugging

4. **Report to Developer:**
Include test results and error logs

---

## Next Steps After Testing

Once all tests pass:

1. **Document any configuration changes**
2. **Train admin users on the feature**
3. **Set up monitoring** for switched sessions
4. **Schedule periodic log cleanup** (keeps 90 days by default)
5. **Add to backup routine** (include `wp_nmda_user_switches` table)

---

## Support

For issues or questions during testing:
- Check the main [README.md](README.md) documentation
- Review error logs in `wp-content/debug.log`
- Contact NMDA development team with test results
