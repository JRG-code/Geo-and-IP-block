# Settings Save Diagnostic Instructions

## Problem
Settings appear to save successfully but don't persist when switching tabs or reloading the page.

## Diagnostic Steps

### Step 1: Run the Diagnostic Script

1. Access this URL in your browser (logged in as admin):
   ```
   https://yoursite.com/wp-content/plugins/geo-ip-blocker/debug-settings.php
   ```

2. Review the output and check for:
   - ❌ Settings are EMPTY
   - ❌ admin.js regex pattern missing
   - ⚠️ Update/Manager plugins detected
   - ⚠️ Cache plugins detected

### Step 2: Check Debug Log

1. Try saving settings from the admin panel (General Settings tab)
2. Check the debug log file:
   ```
   /wp-content/plugins/geo-ip-blocker/debug-save.log
   ```

3. Look for:
   - `settings_received: NO` = JavaScript is not extracting field names correctly
   - `new_settings_count: 0` = No data being sent
   - `update_result: FAILED` = Database write issue

### Step 3: Browser Console Check

1. Open browser Developer Tools (F12)
2. Go to Console tab
3. Try saving settings
4. Check for JavaScript errors (red text)
5. Go to Network tab
6. Look for the `admin-ajax.php` request
7. Click on it and check:
   - **Request payload**: Should have `settings` object with field names
   - **Response**: Should be JSON with `success: true`

### Step 4: Cache Clear

Clear ALL caches:
- Browser cache (Ctrl+Shift+Delete or Cmd+Shift+Delete)
- WordPress cache plugin (if any)
- Server cache (ask hosting provider)
- CDN cache (if using Cloudflare/similar)

### Step 5: Plugin Conflict Test

Temporarily deactivate these types of plugins:
- Plugin update managers
- Cache plugins
- Code optimization plugins
- JavaScript minification plugins

Then test settings save again.

## Common Issues & Solutions

### Issue 1: Update Manager Plugin Conflict
**Symptom**: Plugin keeps reverting to old version
**Solution**:
1. Temporarily deactivate update manager plugins
2. Re-upload the plugin files manually
3. Clear all caches
4. Test again

### Issue 2: JavaScript Not Loading
**Symptom**: Browser console shows 404 for admin.js
**Solution**:
1. Check file permissions: `chmod 644 assets/js/admin.js`
2. Clear server cache
3. Hard refresh browser (Ctrl+F5)

### Issue 3: Field Names Not Extracted
**Symptom**: `debug-save.log` shows empty settings keys
**Solution**: Check that admin.js contains this pattern:
```javascript
const settingsMatch = key.match(/^settings\[([^\]]+)\](\[\])?$/);
```

### Issue 4: Database Not Updating
**Symptom**: `update_result: FAILED` in debug log
**Solution**:
1. Check database permissions
2. Check if option already exists: `wp_options` table, look for `geo_ip_blocker_settings`
3. Try deleting the option and saving again

## Files to Check

After any changes, verify these files match the latest version:

1. **admin.js** - Modified date should be recent
   ```
   /wp-content/plugins/geo-ip-blocker/assets/js/admin.js
   ```
   Size should be approximately 36-38 KB

2. **class-settings-page.php** - Should have debug logging
   ```
   /wp-content/plugins/geo-ip-blocker/admin/class-settings-page.php
   ```
   Should contain "TEMPORARY DEBUG LOGGING" comment

3. **geo-ip-blocker.php** - Version should be 1.0.1
   ```
   Version: 1.0.1
   ```

## Contact Support

If none of these steps work, provide:
1. Output from `debug-settings.php`
2. Last few entries from `debug-save.log`
3. Browser console errors (screenshot)
4. Network tab screenshot of the AJAX request

## Cleanup After Diagnosis

Once the issue is resolved, delete these files:
```bash
rm /wp-content/plugins/geo-ip-blocker/debug-settings.php
rm /wp-content/plugins/geo-ip-blocker/debug-save.log
rm /wp-content/plugins/geo-ip-blocker/DIAGNOSTIC-INSTRUCTIONS.md
```

And remove the debug logging from `class-settings-page.php` (search for "TEMPORARY DEBUG LOGGING").
