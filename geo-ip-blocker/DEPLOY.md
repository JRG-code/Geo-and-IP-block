# Deployment Guide - Geo & IP Blocker for WooCommerce

## Table of Contents

- [Pre-Deployment Checklist](#pre-deployment-checklist)
- [Distribution Package](#distribution-package)
- [Installation Methods](#installation-methods)
- [Hostinger Deployment](#hostinger-deployment)
- [Quick Setup (5 Minutes)](#quick-setup-5-minutes)
- [Hostinger-Specific Configuration](#hostinger-specific-configuration)
- [Rollback Plan](#rollback-plan)
- [Post-Deployment Verification](#post-deployment-verification)

## Pre-Deployment Checklist

Before deploying, ensure all these items are checked:

### Code Quality

```bash
# Check PHP syntax
find . -name "*.php" -exec php -l {} \;

# Run WordPress Coding Standards (if available)
phpcs --standard=WordPress geo-ip-blocker/

# Auto-fix coding standards
phpcbf --standard=WordPress geo-ip-blocker/
```

### Testing

- [ ] All PHPUnit tests passing
- [ ] Manual testing checklist completed (see [TESTING.md](TESTING.md))
- [ ] Tested on WordPress 5.8+
- [ ] Tested on WordPress 6.4+
- [ ] Tested on WooCommerce 6.0+
- [ ] Tested on WooCommerce 8.0+
- [ ] Tested on PHP 7.4
- [ ] Tested on PHP 8.0+
- [ ] Tested on different hosting environments

### Documentation

- [ ] README.md complete and up-to-date
- [ ] readme.txt formatted for WordPress.org
- [ ] TESTING.md checklist complete
- [ ] All code comments in place
- [ ] Changelog updated
- [ ] Screenshots prepared (if needed)

### Version Control

- [ ] All changes committed
- [ ] Version number updated in:
  - geo-ip-blocker.php (Plugin header)
  - geo-ip-blocker.php (GEO_IP_BLOCKER_VERSION constant)
  - readme.txt (Stable tag)
- [ ] Git tag created for release
- [ ] Branch pushed to remote

### Files

- [ ] uninstall.php present and tested
- [ ] License file included
- [ ] No development files in package (.git, node_modules, tests, etc.)

## Distribution Package

### File Structure

Your distribution package should have this structure:

```
geo-ip-blocker/
├── geo-ip-blocker.php          # Main plugin file
├── uninstall.php               # Uninstall handler
├── readme.txt                  # WordPress.org readme
├── README.md                   # Technical documentation
├── LICENSE                     # GPL v2 License
├── TESTING.md                  # Testing checklist
├── DEPLOY.md                   # This file
├── /admin/                     # Admin interface
│   ├── class-admin.php
│   ├── class-settings-page.php
│   ├── class-logs-page.php
│   └── /views/
├── /assets/                    # CSS, JS, images
│   ├── /css/
│   ├── /js/
│   └── /images/
├── /includes/                  # Core functionality
│   ├── class-blocker.php
│   ├── class-cache.php
│   ├── class-database.php
│   ├── class-geo-blocker.php
│   ├── class-geolocation.php
│   ├── class-ip-manager.php
│   ├── class-logger.php
│   ├── class-rate-limiter.php
│   ├── class-security.php
│   ├── class-woocommerce.php
│   ├── class-blocked-page.php
│   └── functions.php
├── /languages/                 # Translation files
│   └── geo-ip-blocker.pot
└── /templates/                 # Frontend templates
    └── blocked-message.php
```

### Creating Distribution ZIP

#### Manual Method

```bash
# Navigate to plugins directory
cd wp-content/plugins/

# Create ZIP excluding development files
zip -r geo-ip-blocker-1.0.0.zip geo-ip-blocker/ \
    -x "*.git*" \
    -x "*node_modules/*" \
    -x "*tests/*" \
    -x "*.DS_Store" \
    -x "*phpunit.xml*" \
    -x "*composer.json" \
    -x "*composer.lock" \
    -x "*package.json" \
    -x "*package-lock.json" \
    -x "*.gitignore"
```

#### Using Script

Create a `build.sh` script:

```bash
#!/bin/bash

VERSION="1.0.0"
PLUGIN_SLUG="geo-ip-blocker"
BUILD_DIR="build"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

# Create build directory
mkdir -p ${BUILD_DIR}

# Copy plugin files
rsync -av \
    --exclude='.git*' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='.DS_Store' \
    --exclude='phpunit.xml*' \
    --exclude='composer.*' \
    --exclude='package*.json' \
    --exclude='build' \
    --exclude='*.zip' \
    ./ ${BUILD_DIR}/${PLUGIN_SLUG}/

# Create ZIP
cd ${BUILD_DIR}
zip -r ../${ZIP_NAME} ${PLUGIN_SLUG}/
cd ..

# Cleanup
rm -rf ${BUILD_DIR}

echo "✅ Build complete: ${ZIP_NAME}"
```

Make executable and run:

```bash
chmod +x build.sh
./build.sh
```

## Installation Methods

### Method 1: WordPress Admin Upload

**Easiest for non-technical users**

1. Log in to WordPress admin
2. Go to **Plugins > Add New**
3. Click **Upload Plugin**
4. Choose the ZIP file
5. Click **Install Now**
6. Click **Activate Plugin**

### Method 2: FTP Upload

**Best for when WP admin is inaccessible**

1. Unzip the plugin locally
2. Connect to server via FTP (FileZilla, Cyberduck, etc.)
3. Navigate to `wp-content/plugins/`
4. Upload the `geo-ip-blocker` folder
5. Go to WordPress admin
6. Navigate to **Plugins**
7. Activate "Geo & IP Blocker for WooCommerce"

### Method 3: SSH/Command Line

**Fastest for developers**

```bash
# Via SCP
scp geo-ip-blocker-1.0.0.zip user@server:/path/to/wp-content/plugins/
ssh user@server
cd /path/to/wp-content/plugins/
unzip geo-ip-blocker-1.0.0.zip

# Via WP-CLI (if available)
wp plugin install geo-ip-blocker-1.0.0.zip --activate
```

### Method 4: Hostinger File Manager

**Recommended for Hostinger users**

1. Log in to Hostinger panel
2. Go to **File Manager**
3. Navigate to `public_html/wp-content/plugins/`
4. Click **Upload**
5. Choose ZIP file and upload
6. Right-click ZIP and select **Extract**
7. Delete ZIP file
8. Go to WordPress admin and activate

## Hostinger Deployment

### Prerequisites

- Hostinger hosting account
- WordPress installed
- WooCommerce installed and activated
- PHP 7.4+ configured
- Database access credentials

### Step-by-Step Deployment

#### 1. Prepare Hostinger Environment

```bash
# SSH into Hostinger (if available)
ssh u123456789@123.456.789.123

# Navigate to WordPress root
cd domains/yourdomain.com/public_html

# Check PHP version
php -v

# Should be 7.4 or higher
```

#### 2. Upload Plugin

**Option A: File Manager (Recommended)**

1. Hostinger Panel > File Manager
2. Navigate to `public_html/wp-content/plugins/`
3. Upload `geo-ip-blocker-1.0.0.zip`
4. Extract archive
5. Delete ZIP file

**Option B: FTP**

1. Use Hostinger FTP credentials
2. Connect via FileZilla
3. Upload to `public_html/wp-content/plugins/`

#### 3. Activate Plugin

1. WordPress Admin > Plugins
2. Find "Geo & IP Blocker for WooCommerce"
3. Click **Activate**

#### 4. Verify Installation

Check that database tables were created:

```sql
-- Access phpMyAdmin from Hostinger panel
SHOW TABLES LIKE 'wp_geo_ip_%';

-- Should show:
-- wp_geo_ip_rules
-- wp_geo_ip_logs
```

## Quick Setup (5 Minutes)

After activation, follow these steps for basic configuration:

### 1. Configure Geolocation API

**Option A: MaxMind GeoIP2 (Recommended)**

1. Register at: https://www.maxmind.com/en/geolite2/signup
2. Generate license key
3. WordPress Admin > Geo & IP Blocker > Settings > API
4. Select "MaxMind GeoIP2"
5. Paste license key
6. Click **Test Connection**
7. Save Settings

**Option B: IP-API (Free, Limited)**

1. WordPress Admin > Geo & IP Blocker > Settings > API
2. Select "IP-API"
3. Save Settings

Note: IP-API has 45 requests/minute limit.

### 2. Basic Configuration

1. Go to **Settings > General**
2. **Enable Plugin**: Check
3. **Blocking Mode**: Select "Blacklist"
4. **Block Action**: Select "Show Message"
5. **Block Message**: Customize your message
6. Save Settings

### 3. Add Countries to Block

1. Still in **Settings > General**
2. Scroll to **Blocked Countries**
3. Start typing country name
4. Select countries to block
5. Save Settings

### 4. Test Blocking

1. Go to **Tools > IP Location Test**
2. Click **Detect My Location**
3. Verify your country is detected
4. Check if you would be blocked

### 5. Monitor Logs

1. Go to **Logs**
2. View blocked access attempts
3. Configure log retention as needed

## Hostinger-Specific Configuration

### PHP Settings

Recommended PHP configuration for Hostinger:

```ini
; Minimum values
memory_limit = 256M
max_execution_time = 300
max_input_vars = 3000
post_max_size = 32M
upload_max_filesize = 32M

; For better performance
opcache.enable = 1
opcache.memory_consumption = 128
```

Configure via:
- Hostinger Panel > Advanced > PHP Configuration
- Or add to `.user.ini` file in public_html

### LiteSpeed Cache Integration

Hostinger uses LiteSpeed. Configure caching:

1. Install LiteSpeed Cache plugin (if not installed)
2. **Settings > LiteSpeed Cache > Cache**
   - Enable Cache: ON
3. **Excludes > Do Not Cache URIs**
   - Add: `/checkout/`
   - Add: `/cart/`
   - Add: `/my-account/`
4. Save Settings

The plugin automatically integrates with LiteSpeed cache.

### Database Optimization

For Hostinger MySQL:

```sql
-- Check if indexes exist
SHOW INDEX FROM wp_geo_ip_logs;
SHOW INDEX FROM wp_geo_ip_rules;

-- Optimize tables monthly
OPTIMIZE TABLE wp_geo_ip_logs;
OPTIMIZE TABLE wp_geo_ip_rules;

-- Analyze for query optimization
ANALYZE TABLE wp_geo_ip_logs;
ANALYZE TABLE wp_geo_ip_rules;
```

### Cron Jobs

For better performance, use real cron instead of WP-Cron:

1. Add to `wp-config.php`:
   ```php
   define('DISABLE_WP_CRON', true);
   ```

2. Hostinger Panel > Advanced > Cron Jobs

3. Add cron job:
   ```
   */15 * * * * wget -q -O - https://yourdomain.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
   ```

### Memory Optimization

If experiencing memory issues:

1. Enable object caching (Redis if available)
2. Reduce log retention period
3. Lower max logs setting
4. Enable auto-cleanup

## Rollback Plan

If deployment fails or issues occur:

### Quick Rollback via Admin

1. WordPress Admin > Plugins
2. Deactivate "Geo & IP Blocker"
3. Delete plugin
4. Reinstall previous version

### Manual Rollback via FTP

1. Connect via FTP
2. Rename `wp-content/plugins/geo-ip-blocker` to `geo-ip-blocker-backup`
3. Upload previous version
4. Reactivate via WordPress admin

### Database Rollback

If database issues occur:

```sql
-- Restore from backup
-- Or drop tables and reinstall
DROP TABLE IF EXISTS wp_geo_ip_rules;
DROP TABLE IF EXISTS wp_geo_ip_logs;

-- Delete options
DELETE FROM wp_options WHERE option_name LIKE 'geo_ip_blocker_%';
DELETE FROM wp_options WHERE option_name LIKE 'geo_blocker_%';
```

Then reinstall plugin.

## Post-Deployment Verification

### Functionality Tests

- [ ] Plugin activates without errors
- [ ] Settings page loads
- [ ] Can add/remove countries
- [ ] Can add/remove IPs
- [ ] Geolocation API works (test in Tools)
- [ ] Logs are recorded
- [ ] Blocked message displays
- [ ] WooCommerce integration works
- [ ] Product restrictions work

### Performance Tests

- [ ] Page load time not significantly impacted
- [ ] Database queries optimized (use Query Monitor plugin)
- [ ] Cache is working
- [ ] No PHP errors in error log

### Check Error Logs

```bash
# Hostinger error log location
tail -f ~/logs/error.log

# Or via File Manager
domains/yourdomain.com/logs/error.log
```

### Monitor First 24 Hours

- Watch for any PHP errors
- Monitor blocked attempts
- Check for false positives
- Verify no legitimate users blocked

### Backup Verification

Ensure backup was taken before deployment:

- Database backup
- Files backup
- Can restore if needed

## Troubleshooting

### Plugin Won't Activate

- Check PHP version (must be 7.4+)
- Check WooCommerce is active
- Check error logs
- Increase PHP memory limit

### Database Tables Not Created

```php
// Add to wp-config.php temporarily
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check debug.log for errors
// wp-content/debug.log
```

Then:
1. Deactivate plugin
2. Delete tables manually if needed
3. Reactivate plugin

### Geolocation Not Working

- Verify API key is correct
- Test connection in Settings > API
- Check if curl is enabled: `php -m | grep curl`
- Try alternative API (IP-API)

### Performance Issues

- Enable caching in Settings
- Reduce log retention
- Use object cache (Redis/Memcached)
- Optimize database tables
- Clear cache

### Users Getting Blocked Incorrectly

- Check whitelist for admin IPs
- Verify blocking mode (whitelist vs blacklist)
- Enable "Exempt Administrators"
- Check geolocation accuracy
- Add specific IPs to whitelist

## Support

For deployment support:

- GitHub Issues: https://github.com/JRG-code/Geo-and-IP-block/issues
- Documentation: See README.md
- Email: support@exemplo.com

## Version History

### 1.0.0 - Initial Release
- First production-ready version
- All core features implemented
- Full Hostinger compatibility
