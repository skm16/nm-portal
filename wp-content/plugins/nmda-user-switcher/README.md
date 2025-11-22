# NMDA User Switcher

A custom WordPress plugin built specifically for the NMDA Portal that allows administrators to view the site from any user's perspective while maintaining admin privileges.

## Features

- **Seamless User Switching**: Switch to any user account with a single click
- **Admin Bar Integration**: Quick access switcher in the WordPress admin bar
- **Visual Indicators**: Prominent banner showing who you're viewing as
- **Business Relationship Display**: Shows user's associated businesses and roles
- **User Search**: Autocomplete search to find users quickly
- **Recent Switches**: Quick access to recently viewed users
- **Activity Logging**: Complete audit trail of all switching activity
- **Dashboard Widget**: Statistics and recent activity at a glance
- **Security Features**:
  - Prevents switching to other administrators
  - Nonce verification for all actions
  - IP address and user agent logging
  - Session timeout (12 hours default)

## Installation

1. Upload the `nmda-user-switcher` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create a database table for activity logging

## Usage

### Switching to a User

**Method 1: Admin Bar Menu**
1. Click "Switch User" in the admin bar
2. Type the user's name or email in the search box
3. Click on the user from the search results

**Method 2: Users List Table**
1. Go to Users → All Users
2. Hover over any user row
3. Click "Switch To" in the row actions

**Method 3: Recent Switches**
1. Click "Switch User" in the admin bar
2. Hover over "Recent"
3. Click on a recently viewed user

### Switching Back

**Method 1: Visual Banner**
- Click the "Switch Back to Admin" button in the gold banner at the top of the page

**Method 2: Admin Bar**
- Click on the "Viewing as: [User Name]" in the admin bar
- Select "Switch Back to Admin"

### View Activity Log

1. Click "Switch User" in the admin bar
2. Select "View Activity Log"

Or from the dashboard:
1. View the "User Switcher Activity" widget on the admin dashboard
2. Click "View Full Activity Log"

## What You'll See When Switched

### Visual Banner
A prominent banner appears at the top of every page showing:
- The user's display name and email
- Number of associated businesses
- List of all businesses and the user's role (Owner, Manager, Viewer)
- Quick "Switch Back" button

### User Context
All page views and functionality will work exactly as if you were that user:
- Dashboard shows their businesses only
- Profile editing permissions match their role
- Resource center access reflects their approval status
- Reimbursement forms show their data
- Business directory shows what they can see

### Admin Privileges Maintained
Even when switched:
- Admin bar remains visible
- You can access wp-admin
- Full admin capabilities are preserved
- You can switch to another user without switching back first

## NMDA Portal Integration

This plugin is specifically designed to work with NMDA Portal's custom features:

### Business Relationships
The switcher respects the `wp_nmda_user_business` relationship table:
- Shows all businesses the user has access to
- Displays the user's role for each business (Owner, Manager, Viewer)
- Reflects proper permissions for profile editing

### Custom User Functions
Compatible with all NMDA custom functions:
- `nmda_get_user_businesses($user_id)`
- `nmda_user_can_access_business($user_id, $business_id)`
- `nmda_get_user_business_role($user_id, $business_id)`
- `nmda_is_approved_member($user_id)`

### Field-Level Permissions
When editing business profiles as a switched user:
- Fields requiring approval show the correct workflow
- User-editable restrictions are enforced
- Admin-only fields are properly restricted

## Dashboard Widget

The admin dashboard includes a "User Switcher Activity" widget showing:
- Total switches (all time)
- Switches today
- Switches this week
- Active sessions count
- Recent activity with timestamps
- Link to full activity log

## Security Features

### Restrictions
- Only users with `manage_options` capability can switch users
- Cannot switch to other administrators (configurable)
- Each switch action requires a nonce verification
- Sessions automatically expire after 12 hours

### Activity Logging
Every switch is logged with:
- Admin user ID (who switched)
- Target user ID (who they switched to)
- Timestamp of switch
- Timestamp of switch back
- IP address
- User agent string

### Automatic Cleanup
- Expired sessions are cleaned up daily
- Logs older than 90 days are automatically deleted
- Failed switch attempts are blocked

## Database Tables

### wp_nmda_user_switches
```sql
id                    - Primary key
admin_id              - Administrator who switched
switched_to_user_id   - User being switched to
switch_time           - When the switch occurred
switch_back_time      - When they switched back (NULL if still active)
ip_address            - IP address of the admin
user_agent            - Browser/device information
```

## Configuration Options

The plugin stores the following options in `wp_options`:

- `nmda_user_switcher_version`: Plugin version
- `nmda_user_switcher_session_timeout`: Session timeout in seconds (default: 43200 = 12 hours)
- `nmda_user_switcher_prevent_admin_switch`: Prevent switching to other admins (default: true)

## Hooks and Filters

### Filters
- `determine_current_user` - Overrides the current user context
- `show_admin_bar` - Ensures admin bar is shown when switched
- `body_class` - Adds `nmda-user-switched` class when active
- `admin_body_class` - Adds class to admin pages
- `user_row_actions` - Adds "Switch To" action in users list

### Actions
- `init` - Handles switch requests
- `admin_bar_menu` - Adds admin bar items
- `wp_footer` / `admin_footer` - Renders visual banner
- `wp_dashboard_setup` - Adds dashboard widget
- `nmda_user_switcher_cleanup` - Daily cron for cleanup

## Compatibility

### Required
- WordPress 5.0 or higher
- PHP 7.4 or higher
- NMDA Understrap Theme (for custom user functions)

### Tested With
- Advanced Custom Fields (ACF) Pro
- Gravity Forms
- WordPress multisite (not tested)

## Troubleshooting

### Can't switch to a user
- Verify you have administrator privileges
- Check if the user still exists
- If switching to another admin fails, check the `nmda_user_switcher_prevent_admin_switch` option

### Session expired unexpectedly
- Default timeout is 12 hours
- Browser cookies must be enabled
- Check `nmda_user_switcher_session_timeout` option

### Visual banner not showing
- Clear browser cache
- Verify the plugin is activated
- Check for JavaScript errors in browser console
- Ensure jQuery is loaded

### Functions not respecting switched user
- Verify functions use `get_current_user_id()`
- Check for hardcoded user IDs in custom code
- Some plugins may cache user data - try disabling caching plugins

## Development

### File Structure
```
nmda-user-switcher/
├── nmda-user-switcher.php       # Main plugin file
├── includes/
│   ├── class-activator.php      # Activation/deactivation
│   ├── class-session.php        # Session management
│   ├── class-logger.php         # Activity logging
│   ├── class-switcher.php       # Core switching logic
│   └── class-admin-ui.php       # Admin interface
├── assets/
│   ├── css/
│   │   └── admin-styles.css     # Styles (NMDA branded)
│   └── js/
│       └── user-switcher.js     # JavaScript functionality
└── README.md                     # This file
```

### CSS Variables
The plugin uses NMDA brand colors:
```css
--nmda-brown-dark: #512c1d;
--nmda-red: #8b0c12;
--nmda-brown-darker: #330d0f;
--nmda-gold: #f5be29;
```

### AJAX Endpoints
- `nmda_search_users` - Search for users by name/email
- `nmda_get_user_info` - Get detailed user information

## Support

For issues, questions, or feature requests:
1. Check the troubleshooting section above
2. Review the activity log for error details
3. Contact the NMDA development team

## Changelog

### Version 1.0.0
- Initial release
- Core switching functionality
- Admin bar integration
- Visual banner with business relationships
- User search with autocomplete
- Activity logging and statistics
- Dashboard widget
- Security features and session management

## Credits

Developed for the New Mexico Department of Agriculture (NMDA) Portal.

## License

GPL-2.0+
