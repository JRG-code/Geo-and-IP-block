# Plugin Update Checker - Setup Guide

## ⚠️ IMPORTANT: Update Checker Disabled

**The built-in update checker has been disabled to avoid conflicts with external plugin management systems.**

If you're using an external plugin update manager (like WP Pusher, GitHub Updater, etc.), the plugin will work with that system automatically using the standard WordPress plugin headers.

## Managed Externally

This plugin's updates should be managed by your external update management system. The plugin includes all necessary WordPress standard headers:

- Plugin Name
- Version
- Author
- Plugin URI
- Requires at least
- Requires PHP

Your external update manager will use these headers to manage updates.

## Re-enabling Built-in Update Checker (Optional)

If you want to use the built-in GitHub update checker instead of an external system:

1. **Ensure plugin-update-checker library is present**:
   ```bash
   # The library should be in:
   geo-ip-blocker/plugin-update-checker/
   ```

2. **Uncomment the code in geo-ip-blocker.php**:

   Find these lines (around line 47):
   ```php
   /**
    * if ( file_exists( GEO_IP_BLOCKER_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php' ) ) {
    *     require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
    *     $geo_ip_blocker_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    *         'https://github.com/JRG-code/Geo-and-IP-block',
    *         __FILE__,
    *         'geo-ip-blocker'
    *     );
    *     $geo_ip_blocker_update_checker->setBranch( 'main' );
    * }
    */
   ```

   Uncomment by removing the `*` characters:
   ```php
   if ( file_exists( GEO_IP_BLOCKER_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php' ) ) {
       require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
       $geo_ip_blocker_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
           'https://github.com/JRG-code/Geo-and-IP-block',
           __FILE__,
           'geo-ip-blocker'
       );
       $geo_ip_blocker_update_checker->setBranch( 'main' );
   }
   ```

3. **Test the update checker**:
   - Go to WordPress Dashboard → Updates
   - Click "Check Again"
   - Updates should now check from GitHub

## For Plugin Users (Installing from ZIP)

### Prerequisites

You need to include the vendor folder in your distribution ZIP. This requires running Composer before creating the distribution package.

### Building Distribution Package with Updates

```bash
# 1. Navigate to plugin directory
cd geo-ip-blocker/

# 2. Install dependencies (production only)
composer install --no-dev --optimize-autoloader

# 3. Create distribution ZIP (from parent directory)
cd ..
zip -r geo-ip-blocker-1.0.0.zip geo-ip-blocker/ \
    -x "geo-ip-blocker/.git/*" \
    -x "geo-ip-blocker/.gitignore" \
    -x "geo-ip-blocker/tests/*" \
    -x "geo-ip-blocker/phpunit.xml.dist" \
    -x "geo-ip-blocker/composer.json" \
    -x "geo-ip-blocker/composer.lock" \
    -x "*.DS_Store"

# Note: vendor/ folder IS included in the ZIP
```

### What Gets Installed

When users install your plugin:
- `vendor/` folder with Plugin Update Checker library
- Plugin will check GitHub for updates automatically
- No configuration needed by the user

## For Developers (Publishing Updates)

### Publishing a New Version

1. **Update version numbers** in these locations:
   ```php
   // geo-ip-blocker.php header
   * Version: 1.0.1

   // geo-ip-blocker.php constant
   define( 'GEO_IP_BLOCKER_VERSION', '1.0.1' );
   ```

2. **Update changelog** in `readme.txt`:
   ```
   == Changelog ==

   = 1.0.1 - 2024-01-20 =
   * Fixed: Bug description
   * Added: New feature
   ```

3. **Commit and tag the release**:
   ```bash
   git add -A
   git commit -m "Release v1.0.1"
   git tag v1.0.1
   git push origin main
   git push origin v1.0.1
   ```

4. **Create GitHub Release** (recommended):
   - Go to GitHub repository
   - Releases > Create new release
   - Tag: v1.0.1
   - Title: Version 1.0.1
   - Description: Copy from changelog
   - Attach distribution ZIP

### Update Check Flow

```
User's WordPress (every 12 hours)
  ↓
Checks GitHub Repository
  ↓
Compares versions (local vs GitHub)
  ↓
If newer version exists
  ↓
Shows update notification
  ↓
User clicks "Update Now"
  ↓
Downloads latest version from GitHub
  ↓
Installs update automatically
```

## Configuration Options

### Change Update Branch

By default, updates are checked from the `main` branch. To change:

```php
// In geo-ip-blocker.php, modify:
$geo_ip_blocker_update_checker->setBranch( 'develop' );  // or any branch
```

### Private Repository Support

If your repository is private:

1. Generate GitHub Personal Access Token:
   - GitHub Settings > Developer settings > Personal access tokens
   - Generate new token (classic)
   - Select `repo` scope
   - Copy the token

2. Add authentication in `geo-ip-blocker.php`:
   ```php
   // Uncomment and add your token:
   $geo_ip_blocker_update_checker->setAuthentication( 'your-github-token-here' );
   ```

### Custom Update Check Interval

```php
// Add after buildUpdateChecker():
$geo_ip_blocker_update_checker->checkPeriod = 6; // Check every 6 hours
```

## Testing Updates

### Test Update Detection

1. **Lower version number** temporarily:
   ```php
   define( 'GEO_IP_BLOCKER_VERSION', '0.9.0' );
   ```

2. **Force update check** in WordPress:
   - Dashboard > Updates
   - Click "Check Again"
   - Should see update available

3. **Restore version number** after testing

### View Update Checker Info

The plugin adds a "View Details" link on the update notification. This shows:
- Latest version number
- Release date
- Changelog
- Download link

## Troubleshooting

### Updates Not Showing

**Check 1: Verify vendor folder exists**
```bash
ls -la geo-ip-blocker/vendor/yahnis-elsts/plugin-update-checker/
```

**Check 2: Check for PHP errors**
```bash
# Enable WP_DEBUG in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# Check wp-content/debug.log for errors
```

**Check 3: Manually trigger update check**
```php
// Add temporarily to functions.php
delete_site_transient('update_plugins');
wp_update_plugins();
```

### Version Not Updating

Make sure you updated BOTH:
- Plugin header: `Version: 1.0.1`
- PHP constant: `GEO_IP_BLOCKER_VERSION`

### GitHub Rate Limiting

GitHub has API rate limits:
- Authenticated: 5,000 requests/hour
- Unauthenticated: 60 requests/hour

For high-traffic sites, use authentication:
```php
$geo_ip_blocker_update_checker->setAuthentication( 'your-token' );
```

## Distribution Checklist

Before creating distribution ZIP:

- [ ] Run `composer install --no-dev`
- [ ] Verify `vendor/` folder exists
- [ ] Verify update checker file exists
- [ ] Test plugin activation
- [ ] Test update detection (lower version)
- [ ] Remove test changes
- [ ] Create final ZIP with vendor folder

## Security Notes

1. **Never commit GitHub tokens** to the repository
2. **Use environment variables** for tokens if needed
3. **Vendor folder** should be in distribution ZIP only, not in git
4. **Regular updates** ensure users get security patches

## Advanced: Custom Update Server

If you want to host updates on your own server instead of GitHub:

```php
$geo_ip_blocker_update_checker = PucFactory::buildUpdateChecker(
    'https://your-server.com/updates/geo-ip-blocker.json',
    __FILE__,
    'geo-ip-blocker'
);
```

Create `geo-ip-blocker.json` on your server:
```json
{
    "version": "1.0.1",
    "download_url": "https://your-server.com/downloads/geo-ip-blocker-1.0.1.zip",
    "requires": "5.8",
    "tested": "6.4",
    "requires_php": "7.4",
    "sections": {
        "description": "Plugin description",
        "changelog": "Version 1.0.1 changes..."
    }
}
```

## Resources

- Plugin Update Checker Documentation: https://github.com/YahnisElsts/plugin-update-checker
- GitHub Releases: https://docs.github.com/en/repositories/releasing-projects-on-github
- WordPress Plugin API: https://developer.wordpress.org/plugins/plugin-basics/
