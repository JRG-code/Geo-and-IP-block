=== Geo & IP Blocker for WooCommerce ===
Contributors: jrgcode
Tags: geolocation, ip-blocker, woocommerce, security, country-blocker, geo-blocking, access-control
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block or allow access to your WooCommerce store based on visitor's country or IP address. Complete geo-blocking solution with advanced features.

== Description ==

**Geo & IP Blocker for WooCommerce** is a comprehensive WordPress plugin that allows you to control access to your site based on geographical location (country/region) or IP addresses.

= Key Features =

**Geographic Blocking**
* Block or allow access by country (250+ countries supported)
* Whitelist mode (allow only selected countries) or Blacklist mode (block selected countries)
* Accurate geolocation using MaxMind GeoIP2 or IP-API
* Geolocation query caching for better performance

**IP Address Blocking**
* Block individual IP addresses
* CIDR notation support (`192.168.1.0/24`)
* IP range support (`192.168.1.1-192.168.1.50`)
* Full IPv4 and IPv6 support
* IP whitelist and blacklist

**WooCommerce Integration**
* Site-wide or shop-only blocking
* Cart/checkout specific blocking
* Per-product geographic restrictions
* Per-category restrictions
* Custom messages for blocked products
* Automatic removal of blocked products from cart

**Logs & Statistics**
* Complete logging of access attempts
* Advanced filtering (date, country, IP, reason)
* Real-time statistics
* CSV export (up to 50,000 records)
* Charts with Chart.js (timeline, countries, reasons)
* Automatic cleanup of old logs

**Customizable Templates**
* 3 ready-to-use templates (default, minimal, dark)
* Theme override support
* 7 shortcodes for dynamic content
* Fully responsive
* WCAG 2.1 accessibility compliant

**Developer Friendly**
* 15+ filters for customization
* 10+ action hooks
* Complete API for programmatic access
* Well-documented code
* PHPUnit tests included

**Performance & Security**
* Multi-layer caching (object cache + transients)
* Compatible with major caching plugins
* Optimized database indexes
* API rate limiting
* Input validation and sanitization
* CSRF and XSS protection

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "Geo & IP Blocker"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Go to Plugins > Add New > Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin

= After Activation =

1. Go to "Geo & IP Blocker" > "Settings"
2. Configure your geolocation API (MaxMind recommended)
3. Set your blocking mode (whitelist or blacklist)
4. Add countries to block or allow
5. Save settings

== Frequently Asked Questions ==

= Does this plugin work without WooCommerce? =

While the plugin is designed for WooCommerce integration, it can work on regular WordPress sites for basic geo-blocking functionality. However, WooCommerce is required for product-level restrictions.

= Which geolocation service should I use? =

We recommend MaxMind GeoIP2 for accuracy and reliability. IP-API is a free alternative but has a limit of 45 requests per minute, which may not be suitable for high-traffic sites.

= Will this slow down my site? =

No. The plugin uses multi-layer caching and optimized database queries to ensure minimal performance impact. Geolocation results are cached for 30 minutes, and the plugin is compatible with popular caching plugins.

= Can I customize the blocked message? =

Yes! You can customize the message in the settings, use one of the three built-in templates (default, minimal, dark), or create your own template by copying it to your theme directory.

= Does it support IPv6? =

Yes, the plugin fully supports both IPv4 and IPv6 addresses, including CIDR notation for both.

= Can I whitelist specific IPs from blocked countries? =

Yes, IP whitelists take priority over country blocking. This allows you to whitelist specific IPs even if their country is blocked.

= How do I test if blocking is working? =

Go to Tools > IP Location Test in the plugin admin. You can test your own IP or any other IP address to see if it would be blocked.

= Can I import/export settings? =

Yes, go to Tools > Settings Import/Export. You can export your configuration as JSON and import it on another site.

= Does it log all blocked attempts? =

Yes, if logging is enabled. You can view all logged attempts in the Logs page with advanced filtering and export capabilities.

= Can I block access to specific WooCommerce products? =

Yes, edit any product and you'll find a "Geo Restrictions" metabox where you can enable country-based restrictions for that specific product.

== Screenshots ==

1. General Settings - Configure blocking mode, action, and exemptions
2. Country Selection - Easy-to-use country selector with search
3. IP Management - Add IPs, CIDR ranges, and IP ranges to whitelist/blacklist
4. WooCommerce Integration - Site-level and product-level blocking options
5. Logs & Statistics - View blocked attempts with advanced filtering
6. Blocked Message Template - Default template shown to blocked visitors
7. Tools - IP testing, database management, import/export
8. Product Metabox - Per-product geographic restrictions

== Changelog ==

= 1.0.6 - 2025-01-19 =
* Fixed: Critical setting name mismatches that prevented blocking functionality from working
* Fixed: Whitelist mode now properly allows only selected countries
* Fixed: Blacklist mode now properly blocks selected countries
* Fixed: User/role/page exemptions now work correctly
* Fixed: WooCommerce integration now uses correct setting names
* Added: WooCommerce Cart and Checkout Blocks compatibility declaration
* Improved: Enhanced WooCommerce feature compatibility declarations

= 1.0.1 - 2025-01-17 =
* Fixed: Settings persistence issue - settings now properly saved across all tabs
* Fixed: Settings data structure - JavaScript now sends data in correct format expected by PHP
* Fixed: Multi-tab form handling - settings from inactive tabs are now preserved when saving
* Fixed: Checkbox handling - unchecked checkboxes now properly saved instead of reverting
* Fixed: PHP warnings for missing field indices with proper isset() checks
* Fixed: WooCommerce HPOS compatibility - declared support for High-Performance Order Storage
* Improved: Settings merge logic to prevent data loss between tabs

= 1.0.0 - 2024-01-15 =
* Initial release
* Geographic blocking by country
* IP address blocking (individual, CIDR, ranges)
* WooCommerce integration
* Logs and statistics
* 3 customizable templates
* Tools and utilities
* Multi-layer caching
* Security hardening
* Rate limiting
* PHPUnit tests
* Complete documentation

== Upgrade Notice ==

= 1.0.6 =
CRITICAL UPDATE: Fixes blocking functionality that was completely broken. Whitelist/blacklist now work properly. Update immediately!

= 1.0.1 =
Critical bug fix for settings persistence. Update recommended for all users.

= 1.0.0 =
Initial release of Geo & IP Blocker for WooCommerce.

== Third Party Services ==

This plugin may connect to third-party services for geolocation:

**MaxMind GeoIP2**
* Service URL: https://www.maxmind.com/
* Privacy Policy: https://www.maxmind.com/en/privacy-policy
* Terms of Service: https://www.maxmind.com/en/terms-of-service
* Used for: IP geolocation when MaxMind is selected as provider
* Data sent: IP addresses for geolocation lookup

**IP-API**
* Service URL: https://ip-api.com/
* Privacy Policy: https://ip-api.com/docs/legal
* Used for: IP geolocation when IP-API is selected as provider
* Data sent: IP addresses for geolocation lookup
* Note: Free tier has 45 requests/minute limit

You can choose which service to use (or none) in the plugin settings.

== Developer Documentation ==

= Filters =

`geo_blocker_should_block` - Modify blocking decision
`geo_blocker_message` - Customize blocked message
`geo_blocker_reason` - Customize block reason
`geo_blocker_show_details` - Control detail visibility
`geo_blocker_template_path` - Override template path

= Actions =

`geo_blocker_access_blocked` - Fired when access is blocked
`geo_blocker_before_message` - Before blocked message
`geo_blocker_after_message` - After blocked message
`geo_blocker_settings_updated` - When settings are saved

= Example Usage =

Block all users from country XX:
`
add_filter( 'geo_blocker_should_block', function( $should_block, $ip, $country_code ) {
    if ( $country_code === 'XX' ) {
        return true;
    }
    return $should_block;
}, 10, 3 );
`

Custom blocked message:
`
add_filter( 'geo_blocker_message', function( $message, $ip, $country_code ) {
    return 'Access denied. Contact: support@example.com';
}, 10, 3 );
`

Log to external service when blocked:
`
add_action( 'geo_blocker_access_blocked', function( $ip, $country_code, $reason ) {
    // Send to external logging service
    external_log( $ip, $country_code, $reason );
}, 10, 3 );
`

For complete developer documentation, visit: https://github.com/JRG-code/Geo-and-IP-block

== Privacy Policy ==

This plugin stores the following data locally in your WordPress database:

* **Blocked access logs** (if logging is enabled): IP address, country code, region, city, blocked URL, user agent, block reason, timestamp
* **Plugin settings**: Your configuration choices including country lists, IP lists, blocking mode, etc.

The plugin does NOT:
* Track users across sites
* Store personal data beyond what's necessary for blocking functionality
* Send data to third parties (except geolocation APIs if configured)
* Use cookies

**Geolocation Services**:
If you choose to use MaxMind GeoIP2 or IP-API, visitor IP addresses will be sent to these services for geolocation. Please review their privacy policies (linked above in "Third Party Services" section).

**Data Retention**:
* Logs can be configured to auto-delete after a specified number of days (default: 90 days)
* You can manually delete all logs at any time
* On plugin uninstall, you can choose to remove all data

== Support ==

For support, feature requests, or bug reports:

* GitHub: https://github.com/JRG-code/Geo-and-IP-block/issues
* Email: support@exemplo.com

== Credits ==

* Developed by JRG Code
* Uses MaxMind GeoIP2 for geolocation
* Uses IP-API as free alternative
* Icons by Font Awesome
