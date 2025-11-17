# Hostinger Setup Guide - Geo & IP Blocker

Complete guide for deploying and configuring Geo & IP Blocker on Hostinger hosting.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation on Hostinger](#installation-on-hostinger)
- [Hostinger Optimizations](#hostinger-optimizations)
- [LiteSpeed Cache Configuration](#litespeed-cache-configuration)
- [PHP Configuration](#php-configuration)
- [Database Management](#database-management)
- [Cron Jobs Setup](#cron-jobs-setup)
- [Performance Tuning](#performance-tuning)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### Hostinger Plan Requirements

**Minimum Plan**: Business Hosting or higher

**Recommended Plan**: Cloud Hosting for:
- Better performance
- More resources
- Object caching (Redis)
- Advanced PHP options

### WordPress Requirements

- WordPress 5.8 or higher
- WooCommerce 6.0 or higher
- PHP 7.4 or higher (8.1 recommended)
- MySQL 5.7 or higher

### Check Your Environment

1. **Hostinger Panel** > Advanced > PHP Information
   - Verify PHP version
   - Check memory_limit (minimum 256M)
   - Verify curl extension loaded

2. **phpMyAdmin**
   - Check MySQL version
   - Ensure you have CREATE TABLE permissions

## Installation on Hostinger

### Method 1: Hostinger File Manager (Recommended)

1. **Log in to Hostinger Panel**
   - Go to hpanel.hostinger.com
   - Select your hosting account

2. **Access File Manager**
   - Click **File Manager** icon
   - Navigate to `public_html/wp-content/plugins/`

3. **Upload Plugin**
   - Click **Upload** button (top right)
   - Select `geo-ip-blocker-1.0.0.zip`
   - Wait for upload to complete

4. **Extract Archive**
   - Right-click the ZIP file
   - Select **Extract**
   - Confirm extraction
   - Delete the ZIP file

5. **Set Permissions**
   - Folders should be 755
   - Files should be 644
   - Right-click > **Permissions** if needed

6. **Activate in WordPress**
   - Go to WordPress Admin
   - Navigate to **Plugins**
   - Find "Geo & IP Blocker for WooCommerce"
   - Click **Activate**

### Method 2: FTP Upload

1. **Get FTP Credentials**
   - Hostinger Panel > Files > FTP Accounts
   - Note: hostname, username, password, port

2. **Connect via FTP Client**
   ```
   Host: ftp.yourdomain.com
   Username: your_ftp_user
   Password: your_ftp_password
   Port: 21
   ```

3. **Navigate to Plugins**
   - Remote directory: `/public_html/wp-content/plugins/`

4. **Upload Plugin Folder**
   - Upload unzipped `geo-ip-blocker` folder
   - Ensure all files transferred

5. **Activate in WordPress**
   - WordPress Admin > Plugins > Activate

### Method 3: SSH (Cloud/VPS Plans)

```bash
# Connect via SSH
ssh u123456789@your-server-ip

# Navigate to plugins directory
cd domains/yourdomain.com/public_html/wp-content/plugins/

# Upload via SCP or wget
# Then extract
unzip geo-ip-blocker-1.0.0.zip

# Set permissions
chmod -R 755 geo-ip-blocker
find geo-ip-blocker -type f -exec chmod 644 {} \;

# Activate via WP-CLI (if available)
wp plugin activate geo-ip-blocker
```

## Hostinger Optimizations

### LiteSpeed Cache Configuration

Hostinger uses LiteSpeed web server. Optimize for best performance:

#### 1. Install LiteSpeed Cache Plugin

If not already installed:

```bash
# Via WordPress Admin
Plugins > Add New > Search "LiteSpeed Cache" > Install > Activate

# Via WP-CLI
wp plugin install litespeed-cache --activate
```

#### 2. Configure LiteSpeed Cache

**Cache Settings** (LiteSpeed Cache > Cache):
```
Enable Cache: ON
Cache Logged-in Users: OFF
Cache Commenters: OFF
Cache REST API: OFF
Cache Login Page: OFF
```

**Exclude URLs** (LiteSpeed Cache > Excludes > Do Not Cache URIs):
```
/checkout/*
/cart/*
/my-account/*
/wc-api/*
```

**Exclude Cookies** (LiteSpeed Cache > Excludes > Do Not Cache Cookies):
```
woocommerce_*
wordpress_logged_in_*
wp-postpass_*
```

**Exclude User Agents** (LiteSpeed Cache > Excludes):
```
(leave default)
```

#### 3. Object Cache (Cloud Plans Only)

If you have Redis available:

```bash
# Hostinger Panel > Advanced > Redis
# Enable Redis

# Then in LiteSpeed Cache
LiteSpeed Cache > Cache > Object Cache
- Object Cache: Redis
- Host: localhost
- Port: 6379
- Save
```

#### 4. Geo & IP Blocker Integration

The plugin automatically integrates with LiteSpeed Cache using:

```php
// In class-cache.php
if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
    LiteSpeed_Cache_API::purge_all();
}
```

No additional configuration needed!

## PHP Configuration

### Recommended PHP Settings

**Hostinger Panel** > Advanced > PHP Configuration:

```ini
; Memory Settings
memory_limit = 256M
; For large sites: 512M

; Execution Time
max_execution_time = 300
max_input_time = 300

; Upload Limits
upload_max_filesize = 32M
post_max_size = 32M

; Input Variables
max_input_vars = 3000

; OPcache (Performance)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

### Change PHP Version

If you need to upgrade PHP:

1. **Hostinger Panel** > Advanced > PHP Configuration
2. Select PHP version (8.1 recommended)
3. Click **Update**
4. Test your site

### .user.ini Method

For granular control, create `.user.ini` in `public_html/`:

```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 32M
post_max_size = 32M
max_input_vars = 3000
```

Changes take effect in ~5 minutes.

## Database Management

### Check Database Status

**phpMyAdmin** (Hostinger Panel > Databases):

```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_geo_ip_%';

-- Check table sizes
SELECT
    table_name,
    table_rows,
    ROUND(data_length / 1024 / 1024, 2) AS 'Data Size (MB)',
    ROUND(index_length / 1024 / 1024, 2) AS 'Index Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'your_database_name'
AND table_name LIKE 'wp_geo_ip_%';

-- Check indexes
SHOW INDEX FROM wp_geo_ip_logs;
SHOW INDEX FROM wp_geo_ip_rules;
```

### Optimize Tables

Run monthly via phpMyAdmin:

```sql
OPTIMIZE TABLE wp_geo_ip_logs;
OPTIMIZE TABLE wp_geo_ip_rules;
ANALYZE TABLE wp_geo_ip_logs;
ANALYZE TABLE wp_geo_ip_rules;
```

Or use WordPress Tools:

1. Geo & IP Blocker > Tools > Database
2. Click **Optimize Tables**

### Automated Cleanup

Set up automatic log cleanup:

1. **Settings** > Logs
2. **Enable Auto Cleanup**: Check
3. **Log Retention**: 90 days
4. **Max Logs**: 100,000
5. Save Settings

This runs daily via WordPress cron.

## Cron Jobs Setup

### Disable WP-Cron (Recommended)

WP-Cron can cause performance issues. Use real cron:

1. **Add to wp-config.php**:
   ```php
   define('DISABLE_WP_CRON', true);
   ```

2. **Hostinger Panel** > Advanced > Cron Jobs

3. **Create New Cron Job**:
   ```bash
   # Run every 15 minutes
   Command: wget -q -O - https://yourdomain.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
   Minute: */15
   Hour: *
   Day: *
   Month: *
   Weekday: *
   ```

4. Save Cron Job

### Plugin-Specific Crons

The plugin registers these scheduled events:

- `geo_ip_blocker_cleanup_logs` - Daily log cleanup
- `geo_ip_blocker_update_geoip_db` - Weekly GeoIP database update

These run automatically via the cron above.

### Manual Cron Trigger

For testing:

```bash
# Via SSH
cd /home/u123456789/domains/yourdomain.com/public_html
wp cron event list
wp cron event run geo_ip_blocker_cleanup_logs

# Via URL
curl https://yourdomain.com/wp-cron.php?doing_wp_cron
```

## Performance Tuning

### 1. Enable All Caching

**Plugin Cache**:
```
Settings > Performance
- Enable Cache: ON
- Cache Duration: 1800 (30 minutes)
```

**LiteSpeed Cache**:
```
All settings from "LiteSpeed Cache Configuration" section above
```

**Browser Cache** (via .htaccess):
```apache
# Add to .htaccess
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
</IfModule>
```

### 2. Database Indexes

Verified automatically on activation:

```sql
-- Check indexes exist
SHOW INDEX FROM wp_geo_ip_logs;

-- Should show these composite indexes:
-- country_created (country_code, created_at)
-- ip_created (ip_address, created_at)
-- block_reason (block_reason)
```

### 3. Limit Log Size

For high-traffic sites:

```
Settings > Logs
- Enable Auto Cleanup: ON
- Retention Period: 30 days (or less)
- Max Logs: 50,000 (or less)
```

### 4. CDN Integration (Optional)

If using Cloudflare with Hostinger:

1. **Cloudflare Settings**:
   - Enable "Rocket Loader"
   - Enable "Mirage"
   - Enable "Polish"

2. **Get Real Visitor IP**:
   Add to `wp-config.php`:
   ```php
   if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
       $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
   }
   ```

3. **Update Geo Settings**:
   ```
   Settings > API > IP Header
   - Check "HTTP_CF_CONNECTING_IP"
   ```

### 5. Monitor Performance

Use WordPress plugins:

- **Query Monitor**: Check database queries
- **P3 Plugin Profiler**: Find slow plugins
- **New Relic** (if available on plan)

Target metrics:
- Page load time: < 2 seconds
- Database queries: < 50 per page
- Plugin overhead: < 100ms

## Troubleshooting

### Cannot Activate Plugin

**Error**: "Plugin requires WooCommerce"

**Solution**:
1. Verify WooCommerce is installed and activated
2. Check WooCommerce version (need 6.0+)
3. Try deactivating and reactivating WooCommerce

**Error**: "Plugin requires PHP 7.4"

**Solution**:
1. Hostinger Panel > PHP Configuration
2. Change to PHP 8.1
3. Test site first
4. Reactivate plugin

### Database Tables Not Created

**Check**:
```sql
SHOW TABLES LIKE 'wp_geo_ip_%';
```

**If empty**:
1. Check database permissions
2. Enable WP_DEBUG:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
3. Check `wp-content/debug.log`
4. Deactivate and reactivate plugin

### Geolocation Not Working

**MaxMind API fails**:
1. Verify license key is correct
2. Test connection in Settings > API
3. Check if curl is enabled:
   ```php
   php -m | grep curl
   ```
4. Try IP-API instead (Settings > API)

**IP-API rate limit**:
- Limit is 45 requests/minute
- Solution: Upgrade to MaxMind
- Or enable aggressive caching

### Memory Limit Errors

**Error**: "Allowed memory size exhausted"

**Solution**:
```ini
; .user.ini in public_html
memory_limit = 512M
```

Or contact Hostinger support to increase.

### Slow Admin Dashboard

1. **Reduce logs page size**:
   - Settings > Logs > Items per page: 20

2. **Enable pagination**:
   - Default is enabled

3. **Disable real-time stats** (if added in future):
   - Can increase load

4. **Clear old logs**:
   - Logs > Bulk Actions > Select All > Delete

### Cache Not Working

1. **Clear all caches**:
   ```bash
   # WordPress
   wp cache flush

   # LiteSpeed
   LiteSpeed Cache > Toolbox > Purge > Purge All

   # Geo Blocker
   Tools > Clear Cache
   ```

2. **Verify cache plugins**:
   ```php
   // Add to functions.php temporarily
   if ( function_exists('w3tc_flush_all') ) {
       echo 'W3 Total Cache active';
   }
   if ( class_exists('LiteSpeed_Cache_API') ) {
       echo 'LiteSpeed Cache active';
   }
   ```

3. **Check object cache**:
   ```bash
   wp cache type
   # Should show: redis or memcached
   ```

### False Positive Blocks

**Legitimate users getting blocked**:

1. **Add to whitelist**:
   - Settings > IPs > IP Whitelist
   - Add their IP or range

2. **Verify geolocation**:
   - Tools > Test IP
   - Enter their IP
   - Check country detected

3. **Exempt logged-in users**:
   - Settings > General
   - Enable "Exempt Logged In Users"

4. **Check mode**:
   - Whitelist mode: Only allows selected countries
   - Blacklist mode: Blocks selected countries
   - Ensure correct mode selected

## Hostinger Support Resources

### Official Support

- **Live Chat**: Available in Hostinger Panel
- **Email**: support@hostinger.com
- **Knowledge Base**: https://support.hostinger.com

### Performance

- **Hostinger Speed Test**: https://www.hostinger.com/tools/website-speed-test
- **PageSpeed Insights**: https://pagespeed.web.dev/

### Monitoring

- **Hostinger Panel** > Statistics:
  - Visitor stats
  - Resource usage
  - Bandwidth usage

### Backups

**Automatic Backups** (included in most plans):
- Hostinger Panel > Backups
- Weekly automatic backups
- Download or restore from here

**Manual Backup Before Installing**:
1. Hostinger Panel > Backups > Generate Backup
2. Wait for completion
3. Download backup file

**Database Backup**:
```bash
# Via phpMyAdmin
Export > Select tables > Go

# Via SSH (if available)
mysqldump -u username -p database_name > backup.sql
```

## Best Practices for Hostinger

1. **Always test after PHP upgrades**
2. **Monitor resource usage** (CPU, RAM)
3. **Keep backups** before major changes
4. **Use LiteSpeed Cache** for best performance
5. **Enable HTTPS** (free with Hostinger)
6. **Set up cron jobs** (don't rely on WP-Cron)
7. **Optimize database** monthly
8. **Clear logs** regularly
9. **Monitor error logs** in Hostinger Panel
10. **Keep WordPress, WooCommerce, and plugin updated**

## Quick Reference

### File Locations

```
Plugin: public_html/wp-content/plugins/geo-ip-blocker/
Logs: ~/logs/error.log
PHP Config: .user.ini in public_html/
WordPress: public_html/
WP Config: public_html/wp-config.php
```

### Common Commands

```bash
# Check PHP version
php -v

# List cron jobs
crontab -l

# Check disk space
df -h

# Check database tables
wp db tables --search=geo_ip
```

### Performance Targets

- **Page Load**: < 2 seconds
- **TTFB**: < 200ms
- **Database Queries**: < 50/page
- **Memory Usage**: < 50% of limit
- **CPU Usage**: < 70% average

## Need Help?

For Hostinger-specific issues:
1. Check Hostinger Knowledge Base
2. Contact Hostinger Support
3. Check WordPress debug log

For plugin issues:
- GitHub: https://github.com/JRG-code/Geo-and-IP-block/issues
- Email: support@exemplo.com
