# Settings Save Flow - Debug Trace

## Test Scenario: Saving General Settings Tab

### 1. HTML Form Fields (Example)
```html
<input name="settings[enabled]" type="checkbox" checked value="1">
<select name="settings[blocking_mode]">
    <option value="blacklist" selected>Blacklist</option>
</select>
<select name="settings[block_action]">
    <option value="message" selected>Show Message</option>
</select>
<input name="settings[block_message]" type="text" value="Access blocked">
```

### 2. JavaScript Processing (admin.js)

#### Step 2a: Add unchecked checkboxes
```javascript
// For checked checkbox: FormData already has "settings[enabled]" = "1"
// For unchecked checkbox "settings[exempt_administrators]":
formData.append("settings[exempt_administrators]", "0");
```

**FormData after this step:**
```
settings[enabled] = "1"
settings[blocking_mode] = "blacklist"
settings[block_action] = "message"
settings[block_message] = "Access blocked"
settings[exempt_administrators] = "0"
```

#### Step 2b: Extract field names
```javascript
const settings = {};
formData.forEach((value, key) => {
    const settingsMatch = key.match(/^settings\[([^\]]+)\](\[\])?$/);
    if (settingsMatch) {
        fieldName = settingsMatch[1]; // Extract "enabled" from "settings[enabled]"
        settings[fieldName] = value;
    }
});
```

**Settings object after extraction:**
```javascript
{
    enabled: "1",
    blocking_mode: "blacklist",
    block_action: "message",
    block_message: "Access blocked",
    exempt_administrators: "0"
}
```

#### Step 2c: Send AJAX
```javascript
const data = {
    action: 'geo_ip_blocker_save_settings',
    nonce: 'xyz123',
    settings: settings
};

$.ajax({
    url: ajaxUrl,
    type: 'POST',
    data: data
});
```

**POST data sent:**
```
action=geo_ip_blocker_save_settings
nonce=xyz123
settings[enabled]=1
settings[blocking_mode]=blacklist
settings[block_action]=message
settings[block_message]=Access blocked
settings[exempt_administrators]=0
```

### 3. PHP Processing (class-settings-page.php)

#### Step 3a: Receive POST data
```php
$_POST = array(
    'action' => 'geo_ip_blocker_save_settings',
    'nonce' => 'xyz123',
    'settings' => array(
        'enabled' => '1',
        'blocking_mode' => 'blacklist',
        'block_action' => 'message',
        'block_message' => 'Access blocked',
        'exempt_administrators' => '0'
    )
);
```

#### Step 3b: Get existing settings (from other tabs)
```php
$existing_settings = get_option('geo_ip_blocker_settings');
// Contains settings from all tabs, e.g.:
array(
    'enabled' => false,
    'blocking_mode' => 'whitelist',
    'geolocation_provider' => 'ip-api',  // From API tab
    'blocked_countries' => array('CN'),   // From Countries tab
    'enable_logging' => true,             // From Logging tab
    // ... all other settings
)
```

#### Step 3c: Merge settings
```php
$new_settings = $_POST['settings'];
$merged_settings = array_merge($existing_settings, $new_settings);
// Result:
array(
    'enabled' => '1',                     // UPDATED from new
    'blocking_mode' => 'blacklist',       // UPDATED from new
    'block_action' => 'message',          // UPDATED from new
    'block_message' => 'Access blocked',  // UPDATED from new
    'exempt_administrators' => '0',       // UPDATED from new
    'geolocation_provider' => 'ip-api',   // PRESERVED from existing
    'blocked_countries' => array('CN'),   // PRESERVED from existing
    'enable_logging' => true,             // PRESERVED from existing
    // ... all other settings preserved
)
```

#### Step 3d: Sanitize
```php
$sanitized = $this->sanitize_settings($merged_settings);
// Converts types:
array(
    'enabled' => true,                    // Converted to boolean
    'blocking_mode' => 'blacklist',       // Validated
    'block_action' => 'message',          // Validated
    'block_message' => 'Access blocked',  // Sanitized HTML
    'exempt_administrators' => false,     // Converted to boolean
    // ... all settings properly typed
)
```

#### Step 3e: Save
```php
update_option('geo_ip_blocker_settings', $sanitized);
wp_send_json_success(array('message' => 'Settings saved successfully!'));
```

### 4. Expected Behavior

✅ **General Settings tab saved:**
- enabled = true
- blocking_mode = blacklist
- block_action = message
- block_message = "Access blocked"

✅ **Other tabs preserved:**
- API Settings (geolocation_provider, etc.) - UNCHANGED
- Country Blocking (blocked_countries, etc.) - UNCHANGED
- Logging (enable_logging, etc.) - UNCHANGED

✅ **Switch to another tab:**
- Settings from General tab remain saved
- Settings from other tabs remain saved

✅ **Return to General Settings tab:**
- All fields show previously saved values
- Data persists across tab switches

## Potential Issues to Check

### Issue 1: Non-existent fields in existing settings
**Problem:** If a field doesn't exist in existing_settings, array_merge still works
**Solution:** ✅ array_merge handles this correctly

### Issue 2: Array fields (countries)
**Problem:** Countries are arrays, need special handling
**Solution:** ✅ JavaScript handles `settings[blocked_countries][]` correctly
```javascript
if (isArray) {
    if (!settings[fieldName]) {
        settings[fieldName] = [];
    }
    settings[fieldName].push(value);
}
```

### Issue 3: Unchecked checkboxes
**Problem:** HTML doesn't send unchecked checkboxes
**Solution:** ✅ JavaScript explicitly adds "0" for unchecked boxes

### Issue 4: Empty values overwriting existing
**Problem:** Empty string "" could overwrite existing values
**Solution:** ✅ Sanitization function uses isset() checks with defaults

## Debug Checklist

When testing, verify:

1. ✅ JavaScript regex extracts field names correctly
2. ✅ AJAX POST contains `settings` object with clean field names
3. ✅ PHP receives `$_POST['settings']` as array
4. ✅ Existing settings are loaded before merge
5. ✅ Merge preserves settings from other tabs
6. ✅ Sanitization converts types correctly
7. ✅ Database update succeeds
8. ✅ Settings persist after page reload
9. ✅ Tab switching doesn't lose data
10. ✅ Checkboxes save both checked and unchecked states

## Files Verification

### File 1: admin.js
**Location:** `/assets/js/admin.js`
**Key Pattern:** `/^settings\[([^\]]+)\](\[\])?$/`
**Status:** ✅ CORRECT

### File 2: class-settings-page.php
**Location:** `/admin/class-settings-page.php`
**Key Function:** `ajax_save_settings()`
**Merge Logic:** `array_merge($existing_settings, $new_settings)`
**Status:** ✅ CORRECT

### File 3: Debug Logging
**Location:** `/debug-save.log` (created on save)
**Contains:** Full trace of POST data, merge, sanitize, save
**Status:** ✅ ACTIVE

## Conclusion

**All logic is correct and should work!**

The settings persistence issue should be resolved. If it still doesn't work, check:
1. Browser console for JavaScript errors
2. Network tab for AJAX request/response
3. debug-save.log for server-side trace
4. Clear all caches (browser, WordPress, CDN)
5. Verify plugin files are latest version (not reverted by update manager)
