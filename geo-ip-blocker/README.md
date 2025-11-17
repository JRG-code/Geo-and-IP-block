# Geo & IP Blocker for WooCommerce

A powerful WordPress plugin to block or allow access to your WooCommerce store based on geographic location and IP addresses.

## Features

- **Multi-level Blocking**: Block by country, region, or specific IP addresses
- **CIDR Support**: Block entire IP ranges using CIDR notation
- **Multiple Geolocation Providers**: Support for MaxMind, IP2Location, and IP-API.com
- **Smart Caching**: Reduces API calls with WordPress Transients (30-minute cache)
- **Local Database Support**: Optional offline geolocation using MaxMind GeoLite2
- **Priority System**: Set rule priorities for fine-grained control
- **Detailed Logging**: Track all blocked access attempts
- **CDN Compatible**: Detects real IP behind Cloudflare, proxies, and load balancers

## Requirements

- PHP >= 7.4
- WordPress >= 5.8
- WooCommerce >= 6.0

## Installation

### Basic Installation

1. Upload the `geo-ip-blocker` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your API keys in the settings
4. Add blocking rules as needed

### Installation with Local Database (Recommended)

For better performance and unlimited lookups, install the MaxMind GeoIP2 PHP library:

```bash
cd wp-content/plugins/geo-ip-blocker
composer install --no-dev
```

Then configure your MaxMind license key in the settings to enable automatic database updates.

## Geolocation Providers

### 1. MaxMind GeoIP2 (Recommended)

**Pros:**
- High accuracy
- Local database option (unlimited lookups)
- Commercial support available

**Setup:**
1. Sign up at [MaxMind](https://www.maxmind.com/)
2. Generate a license key
3. Enter your Account ID and License Key in plugin settings
4. (Optional) Enable local database for offline lookups

**API Pricing:** Free tier: 1,000 requests/day

### 2. IP2Location

**Pros:**
- Good accuracy
- Multiple data points
- Commercial options

**Setup:**
1. Sign up at [IP2Location](https://www.ip2location.com/)
2. Get your API key
3. Enter the key in plugin settings

**API Pricing:** Free tier: 500 queries/day

### 3. IP-API.com (Fallback)

**Pros:**
- Completely free
- No API key required
- Good for testing

**Cons:**
- Rate limited to 45 requests/minute
- Less accurate than paid services

**Setup:** Enable in plugin settings (no API key required)

## Configuration

### API Provider Priority

The plugin tries providers in this order:
1. Local MaxMind database (if available)
2. MaxMind API
3. IP2Location API
4. IP-API.com (free fallback)

### Cache Settings

Location data is cached for 30 minutes by default. You can modify this:

```php
add_filter( 'geo_ip_blocker_cache_expiration', function() {
    return 3600; // 1 hour in seconds
});
```

### Local Database Updates

If using the local MaxMind database, it updates automatically every week. Manual update:

```php
$geolocation = geo_ip_blocker_get_geolocation();
$geolocation->update_local_database();
```

## Usage Examples

### Get Current Visitor's Location

```php
// Get full location data
$location = geo_ip_blocker_get_current_location();
echo $location['country_code']; // e.g., "US"
echo $location['country_name']; // e.g., "United States"
echo $location['region'];       // e.g., "California"
echo $location['city'];         // e.g., "San Francisco"

// Get just the country code
$country = geo_ip_blocker_get_current_country(); // "US"
```

### Get Location for Specific IP

```php
$ip_location = geo_ip_blocker_get_ip_location( '8.8.8.8' );
print_r( $ip_location );
```

### Clear Cache

```php
// Clear cache for specific IP
geo_ip_blocker_clear_geo_cache( '8.8.8.8' );

// Clear all location cache
geo_ip_blocker_clear_geo_cache();
```

## IP Detection

The plugin detects visitor IP addresses with support for:

- **Cloudflare**: `CF-Connecting-IP` header
- **Nginx Proxy**: `X-Real-IP` header
- **Load Balancers**: `X-Forwarded-For` header
- **Standard**: `REMOTE_ADDR`

### Custom IP Detection

```php
add_filter( 'geo_ip_blocker_visitor_ip', function( $ip ) {
    // Your custom IP detection logic
    return $custom_ip;
});
```

## Blocking Rules

### Block by Country

```php
// Add rule via database
$database = geo_ip_blocker_get_database();
$database->add_rule([
    'rule_type' => 'country',
    'value'     => 'CN',  // China
    'action'    => 'block',
    'priority'  => 10
]);
```

### Block by IP Range (CIDR)

```php
$database->add_rule([
    'rule_type' => 'ip',
    'value'     => '192.168.1.0/24',
    'action'    => 'block',
    'priority'  => 5
]);
```

### Allow Specific IPs (Whitelist)

```php
$database->add_rule([
    'rule_type' => 'ip',
    'value'     => '1.2.3.4',
    'action'    => 'allow',  // Overrides blocks
    'priority'  => 1         // Higher priority
]);
```

## Hooks & Filters

### Filters

```php
// Modify cache expiration (default: 1800 seconds)
add_filter( 'geo_ip_blocker_cache_expiration', function( $seconds ) {
    return 3600; // 1 hour
});

// Modify blocked message
add_filter( 'geo_ip_blocker_blocked_message', function( $message ) {
    return 'Custom blocked message';
});

// Custom blocked template
add_filter( 'geo_ip_blocker_blocked_template', function( $template ) {
    return get_template_directory() . '/blocked-page.php';
});

// Add custom geolocation provider
add_filter( 'geo_ip_blocker_query_custom_provider', function( $data, $ip, $provider ) {
    if ( $provider === 'my_custom_provider' ) {
        // Your API call logic
        return $location_data;
    }
    return $data;
}, 10, 3 );
```

### Actions

```php
// Runs weekly to update local database
add_action( 'geo_ip_blocker_update_database', function() {
    // Custom database update logic
});
```

## Performance Optimization

### Enable Local Database

1. Install composer dependencies
2. Configure MaxMind license key
3. Enable "Use Local Database" in settings
4. Plugin will download GeoLite2-City database automatically

**Benefits:**
- No API rate limits
- Faster lookups
- Works offline
- Free unlimited queries

### Cache Strategy

- Location data cached for 30 minutes
- Rate limiting prevents API abuse
- Automatic fallback between providers

## Troubleshooting

### Location Not Detected

1. Check if visitor IP is public (not local/private)
2. Verify API keys are correct
3. Check provider rate limits
4. Review error logs

### Enable Debug Logging

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Check `/wp-content/debug.log` for geolocation errors.

### Clear All Cache

```php
geo_ip_blocker_clear_geo_cache();
```

## Security

- All IP addresses validated with `filter_var()`
- API responses sanitized
- Rate limiting prevents abuse
- CSRF protection on admin forms

## Development

### Run Tests

```bash
composer install
./vendor/bin/phpunit
```

### Code Standards

Follows WordPress Coding Standards:

```bash
./vendor/bin/phpcs --standard=WordPress geo-ip-blocker/
```

## License

GPL v2 or later

## Support

- **Issues**: [GitHub Issues](https://github.com/JRG-code/Geo-and-IP-block/issues)
- **Documentation**: [Plugin Wiki](#)

## Changelog

### 1.0.0
- Initial release
- Multiple geolocation provider support
- Local database option
- Smart caching system
- IP detection with CDN support
