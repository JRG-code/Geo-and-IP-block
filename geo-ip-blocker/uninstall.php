<?php
/**
 * Uninstall script.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define plugin constants for uninstall.
define( 'GEO_IP_BLOCKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include database class.
require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-database.php';

/**
 * Remove plugin data on uninstall.
 */
function geo_ip_blocker_uninstall() {
	global $wpdb;

	// Get option to check if we should delete data.
	$delete_data = get_option( 'geo_ip_blocker_delete_data_on_uninstall', false );

	if ( ! $delete_data ) {
		// Don't delete data if option is not set.
		return;
	}

	// Delete database tables.
	$database = new Geo_IP_Blocker_Database();
	$database->drop_tables();

	// Delete options.
	delete_option( 'geo_ip_blocker_version' );
	delete_option( 'geo_ip_blocker_activated' );
	delete_option( 'geo_ip_blocker_db_version' );
	delete_option( 'geo_ip_blocker_db_last_update' );
	delete_option( 'geo_ip_blocker_settings' );
	delete_option( 'geo_ip_blocker_delete_data_on_uninstall' );

	// Delete IP lists.
	delete_option( 'geo_blocker_ip_whitelist' );
	delete_option( 'geo_blocker_ip_blacklist' );

	// Delete transients.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_geo_ip_blocker_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_geo_ip_blocker_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_geo_blocker_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_geo_blocker_%'" );
}

// Run uninstall.
geo_ip_blocker_uninstall();
