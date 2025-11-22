# User Switcher Dashboard Fix

## Issue
When switching to a user, the dashboard was not showing the switched user's data. The dashboard was still showing the admin's view instead of the user's perspective.

## Root Cause
WordPress caches the current user object and only determines the user once during authentication. After that, `get_current_user_id()` and `wp_get_current_user()` return cached values. Our `determine_current_user` filter was running during authentication, but subsequent calls to get the current user were using cached data.

## Solution Implemented

### 1. Added Multiple User Override Hooks
- **`determine_current_user`** (priority 1) - Overrides user during authentication
- **`set_current_user`** (action) - Ensures switched user is maintained when WordPress sets the current user
- **`init`** (priority 1) - Forcefully overrides the current user early in the request with `maybe_override_current_user()`
- **`authenticate`** (filter, priority 999) - Maintains switched user during auth checks

### 2. Clear WordPress User Caches
When switching users (both to and back), we now:
- Delete user object cache: `wp_cache_delete($user_id, 'users')`
- Delete user meta cache: `wp_cache_delete($user_id, 'user_meta')`
- Clear the global `$current_user` variable
- Call `wp_set_current_user()` to force WordPress to use the switched user

### 3. New Method: `maybe_override_current_user()`
This method runs early in the `init` hook (priority 1) and:
1. Gets the currently logged-in admin's user ID
2. Checks if they have an active switch session
3. If yes, clears all user caches
4. Sets the global `$current_user` to the switched user
5. Calls `wp_set_current_user()` with the switched user ID

This ensures that all subsequent calls to `get_current_user_id()`, `wp_get_current_user()`, and related functions return the switched user throughout the entire request.

## Testing the Fix

### Prerequisites
1. Start your Local by Flywheel site
2. Activate the plugin:
   ```bash
   "C:\wp-cli\wp.bat" plugin activate nmda-user-switcher
   ```

### Test Steps

1. **Create Test Users** (if needed):
   ```bash
   # Create a test user with a business
   "C:\wp-cli\wp.bat" user create testuser1 test1@example.com --role=subscriber --display_name="Test User One"

   # Associate them with a business (do this via WordPress admin)
   ```

2. **Log in as Administrator**
   - Go to your WordPress admin dashboard

3. **Switch to Test User**
   - Click "Switch User" in the admin bar
   - Search for "Test User"
   - Click on the test user

4. **Verify Dashboard Shows User's View**
   - Navigate to `/member-dashboard` or click Dashboard in menu
   - **Expected:** Dashboard shows ONLY the test user's businesses
   - **Expected:** User's name appears in the banner
   - **Expected:** Reimbursements shown are the test user's

5. **Verify User Context Throughout**
   - Check profile editing - should show user's editable businesses
   - Check resource center - should reflect user's approval status
   - Any page using `get_current_user_id()` should return the switched user's ID

6. **Verify in Code**
   Add this temporary code to your theme's `functions.php`:
   ```php
   add_action('wp_footer', function() {
       if (is_user_logged_in()) {
           $user_id = get_current_user_id();
           $user = wp_get_current_user();
           echo '<!-- DEBUG: User ID: ' . $user_id . ', Display Name: ' . $user->display_name . ' -->';
       }
   });
   ```

   Then view the page source - the comment should show the **switched user's** ID and name, not the admin's.

7. **Test Switch Back**
   - Click "Switch Back to Admin" in the banner
   - Dashboard should now show all users/businesses again

8. **Test Database Logging**
   ```bash
   "C:\wp-cli\wp.bat" db query "SELECT * FROM wp_nmda_user_switches ORDER BY id DESC LIMIT 1;"
   ```
   Should show your most recent switch with admin_id and switched_to_user_id.

## Expected Behavior After Fix

### ✅ What Should Work Now

1. **Dashboard reflects switched user's data**
   - Only shows businesses the switched user has access to
   - Shows correct user name in welcome message
   - Displays user's reimbursement history

2. **All NMDA functions return correct data**
   - `nmda_get_user_businesses(get_current_user_id())` returns switched user's businesses
   - `nmda_user_can_access_business()` checks switched user's permissions
   - `nmda_get_user_business_role()` returns switched user's role

3. **Global user context is correct**
   - `get_current_user_id()` returns switched user ID
   - `wp_get_current_user()` returns switched user object
   - `$current_user` global is the switched user

4. **Caches are properly cleared**
   - Switching users clears all WordPress user caches
   - Fresh data is loaded on next page load
   - No stale data from previous user

### ⚠️ Important Notes

- **Page Cache**: If you're using a caching plugin (WP Super Cache, W3 Total Cache, etc.), you may need to clear it or ensure logged-in users aren't cached
- **Object Cache**: If using Redis or Memcached, those caches are also cleared by `wp_cache_delete()`
- **Browser Cache**: Hard refresh (Ctrl+Shift+R) if you see old data

## Debugging If Still Not Working

### Check if plugin is active:
```bash
"C:\wp-cli\wp.bat" plugin list | findstr nmda-user-switcher
```

### Check if hooks are registered:
```bash
"C:\wp-cli\wp.bat" eval "
global \$wp_filter;
if (isset(\$wp_filter['determine_current_user'])) {
    echo 'determine_current_user filter is registered';
} else {
    echo 'WARNING: Filter not registered';
}
"
```

### Check session data:
```bash
"C:\wp-cli\wp.bat" user meta list 1 --fields=meta_key,meta_value | findstr nmda
```
(Replace `1` with your admin user ID)

Should show `_nmda_switched_user` if you have an active session.

### Enable WordPress Debug Mode:
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then add debug logging to the plugin:
```php
// Add to maybe_override_current_user() method
error_log("NMDA Switcher: User ID: $user_id, Switched to: $switched_to");
```

Check `wp-content/debug.log` for output.

## Technical Details

### Hook Execution Order
1. `init` (priority 0) - `handle_switch_request()` processes switch URLs
2. `init` (priority 1) - `maybe_override_current_user()` forces user override
3. `determine_current_user` - Returns switched user ID during auth
4. `set_current_user` - Maintains switched user when WordPress sets current user
5. Rest of WordPress loads with switched user context

### Cache Clearing Strategy
We clear caches at two points:
1. **During switch** - Right after `start_session()` in `process_switch_to()`
2. **On every request** - In `maybe_override_current_user()` when session is active

This ensures both immediate and persistent user context switching.

### Session Storage
- **User Meta**: `_nmda_switched_user` stores the target user ID on the admin user
- **User Meta**: `_nmda_original_user` stores the original admin ID
- **Transient**: `nmda_user_switch_{admin_id}` for quick access
- **Cookie**: `nmda_user_switcher` for cross-request persistence

All three must be in sync for switching to work properly.

## Files Modified

1. **`includes/class-switcher.php`**
   - Added `maybe_override_current_user()` method
   - Added `set_current_user()` hook handler
   - Added `authenticate_user()` filter
   - Modified `init()` to add new hooks
   - Modified `process_switch_to()` to clear caches
   - Modified `process_switch_back()` to clear caches

## Rollback Instructions

If you need to rollback to the previous version:

1. Deactivate the plugin:
   ```bash
   "C:\wp-cli\wp.bat" plugin deactivate nmda-user-switcher
   ```

2. Restore from git (if committed before):
   ```bash
   cd wp-content/plugins/nmda-user-switcher
   git checkout HEAD~1 includes/class-switcher.php
   ```

3. Reactivate:
   ```bash
   "C:\wp-cli\wp.bat" plugin activate nmda-user-switcher
   ```

## Next Steps

Once you confirm the dashboard is working:

1. ✅ Test all member-facing pages (profile, reimbursements, resources)
2. ✅ Test with users who have multiple businesses
3. ✅ Test with users who have different roles (owner, manager, viewer)
4. ✅ Test switching between multiple users without switching back
5. ✅ Verify admin privileges are maintained when switched
6. ✅ Check performance (should add <50ms to page load)
7. ✅ Review full TESTING-GUIDE.md for comprehensive test cases

## Support

If the dashboard still doesn't show the switched user's view after these fixes:

1. Check for theme-specific user queries that bypass WordPress user functions
2. Look for custom AJAX calls that might not respect the switched user
3. Check if the dashboard page is using a custom query instead of `get_current_user_id()`
4. Verify no other plugins are interfering with user context

Share the specific code from `page-member-dashboard.php` (lines that fetch user data) for further debugging.
