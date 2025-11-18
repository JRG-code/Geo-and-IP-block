<?php
/**
 * Debug Settings Save
 *
 * Temporary diagnostic file to understand settings save issues
 *
 * To use: Access yoursite.com/wp-content/plugins/geo-ip-blocker/debug-settings.php
 */

// Load WordPress
require_once '../../../wp-load.php';

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
	die( 'Unauthorized access' );
}

header( 'Content-Type: text/plain; charset=utf-8' );

echo "=== GEO IP BLOCKER - SETTINGS DIAGNOSTIC ===\n\n";
echo "Date: " . date( 'Y-m-d H:i:s' ) . "\n";
echo "Plugin Version: " . GEO_IP_BLOCKER_VERSION . "\n\n";

// Check if settings option exists
$settings = get_option( 'geo_ip_blocker_settings', array() );

echo "=== CURRENT SETTINGS IN DATABASE ===\n";
if ( empty( $settings ) ) {
	echo "WARNING: Settings are EMPTY!\n\n";
} else {
	echo "Settings found: " . count( $settings ) . " fields\n\n";
	foreach ( $settings as $key => $value ) {
		if ( is_array( $value ) ) {
			echo "$key: [Array with " . count( $value ) . " items]\n";
		} elseif ( is_bool( $value ) ) {
			echo "$key: " . ( $value ? 'true' : 'false' ) . "\n";
		} else {
			$display_value = is_string( $value ) && strlen( $value ) > 50
				? substr( $value, 0, 50 ) . '...'
				: $value;
			echo "$key: $display_value\n";
		}
	}
}

echo "\n=== CRITICAL FIELDS CHECK ===\n";
$critical_fields = array( 'enabled', 'blocking_mode', 'block_action', 'geolocation_provider' );
foreach ( $critical_fields as $field ) {
	$exists = isset( $settings[ $field ] );
	$value = $exists ? $settings[ $field ] : 'NOT SET';
	echo "$field: " . ( $exists ? '✓' : '✗' ) . " - $value\n";
}

echo "\n=== FILE VERSIONS CHECK ===\n";
$admin_js_path = GEO_IP_BLOCKER_PLUGIN_DIR . 'assets/js/admin.js';
$settings_php_path = GEO_IP_BLOCKER_PLUGIN_DIR . 'admin/class-settings-page.php';

if ( file_exists( $admin_js_path ) ) {
	echo "admin.js exists: ✓\n";
	echo "admin.js size: " . filesize( $admin_js_path ) . " bytes\n";
	echo "admin.js modified: " . date( 'Y-m-d H:i:s', filemtime( $admin_js_path ) ) . "\n";

	// Check for the critical regex pattern
	$admin_js_content = file_get_contents( $admin_js_path );
	$has_regex = strpos( $admin_js_content, 'settings\\[([^\\]]+)\\]' ) !== false;
	echo "Has field extraction regex: " . ( $has_regex ? '✓ YES' : '✗ NO (PROBLEM!)' ) . "\n";
} else {
	echo "admin.js: ✗ FILE NOT FOUND!\n";
}

if ( file_exists( $settings_php_path ) ) {
	echo "\nclass-settings-page.php exists: ✓\n";
	echo "class-settings-page.php size: " . filesize( $settings_php_path ) . " bytes\n";
	echo "class-settings-page.php modified: " . date( 'Y-m-d H:i:s', filemtime( $settings_php_path ) ) . "\n";

	// Check for debug_mode in defaults
	$settings_php_content = file_get_contents( $settings_php_path );
	$has_debug_mode = strpos( $settings_php_content, "'debug_mode'" ) !== false;
	echo "Has debug_mode in defaults: " . ( $has_debug_mode ? '✓ YES' : '✗ NO' ) . "\n";
} else {
	echo "\nclass-settings-page.php: ✗ FILE NOT FOUND!\n";
}

echo "\n=== WORDPRESS ENVIRONMENT ===\n";
echo "WordPress Version: " . get_bloginfo( 'version' ) . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Active Plugins: " . count( get_option( 'active_plugins', array() ) ) . "\n";

// Check for plugin update manager plugins
$active_plugins = get_option( 'active_plugins', array() );
$update_plugins = array();
foreach ( $active_plugins as $plugin ) {
	if ( stripos( $plugin, 'update' ) !== false || stripos( $plugin, 'manager' ) !== false ) {
		$update_plugins[] = $plugin;
	}
}

if ( ! empty( $update_plugins ) ) {
	echo "\n⚠️  UPDATE/MANAGER PLUGINS DETECTED:\n";
	foreach ( $update_plugins as $plugin ) {
		echo "  - $plugin\n";
	}
}

echo "\n=== CACHE CHECK ===\n";
$cache_plugins = array();
foreach ( $active_plugins as $plugin ) {
	if ( stripos( $plugin, 'cache' ) !== false || stripos( $plugin, 'optimize' ) !== false ) {
		$cache_plugins[] = $plugin;
	}
}

if ( ! empty( $cache_plugins ) ) {
	echo "⚠️  CACHE PLUGINS DETECTED (may need clearing):\n";
	foreach ( $cache_plugins as $plugin ) {
		echo "  - $plugin\n";
	}
} else {
	echo "No cache plugins detected\n";
}

echo "\n=== RECOMMENDATIONS ===\n";
if ( empty( $settings ) ) {
	echo "1. Settings are empty - try saving from admin panel\n";
	echo "2. Check browser console for JavaScript errors\n";
	echo "3. Check if AJAX requests are reaching the server\n";
}

if ( ! empty( $cache_plugins ) ) {
	echo "4. Clear all caches (plugin cache, browser cache)\n";
}

if ( ! empty( $update_plugins ) ) {
	echo "5. Temporarily deactivate update manager plugins to test\n";
}

echo "\n=== END OF DIAGNOSTIC ===\n";
echo "\nTo delete this file after reviewing:\n";
echo "rm " . __FILE__ . "\n";
