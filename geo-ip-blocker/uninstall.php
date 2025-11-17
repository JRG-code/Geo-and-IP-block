<?php
/**
 * Uninstall Handler
 *
 * Fired when the plugin is uninstalled.
 *
 * @package GeoIPBlocker
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * Delete plugin data based on user preference.
 */
$delete_data = get_option( 'geo_ip_blocker_delete_data_on_uninstall', true );

if ( $delete_data ) {
	// Delete database tables.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}geo_ip_rules" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}geo_ip_logs" );

	// Delete all plugin options.
	delete_option( 'geo_ip_blocker_settings' );
	delete_option( 'geo_ip_blocker_version' );
	delete_option( 'geo_ip_blocker_db_version' );
	delete_option( 'geo_ip_blocker_activated' );
	delete_option( 'geo_ip_blocker_delete_data_on_uninstall' );

	// Delete IP lists.
	delete_option( 'geo_blocker_ip_whitelist' );
	delete_option( 'geo_blocker_ip_blacklist' );

	// Delete transients.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE %s
			OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_geo_ip_blocker_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_geo_ip_blocker_' ) . '%'
		)
	);

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE %s
			OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_geo_blocker_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_geo_blocker_' ) . '%'
		)
	);

	// Delete rate limiter transients.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE %s
			OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_geo_ip_blocker_rate_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_geo_ip_blocker_rate_' ) . '%'
		)
	);

	// Delete site-wide transients (multisite).
	if ( is_multisite() ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta}
				WHERE meta_key LIKE %s
				OR meta_key LIKE %s",
				$wpdb->esc_like( '_site_transient_geo_ip_blocker_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_geo_ip_blocker_' ) . '%'
			)
		);
	}

	// Clear object cache.
	wp_cache_flush();

	// Delete WooCommerce product meta.
	if ( class_exists( 'WooCommerce' ) ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta}
				WHERE meta_key IN (%s, %s)",
				'_geo_blocker_enabled',
				'_geo_blocker_countries'
			)
		);
	}

	// Delete user meta (if any).
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta}
			WHERE meta_key LIKE %s",
			$wpdb->esc_like( 'geo_ip_blocker_' ) . '%'
		)
	);
}

// Clear scheduled events.
wp_clear_scheduled_hook( 'geo_ip_blocker_cleanup_logs' );
wp_clear_scheduled_hook( 'geo_ip_blocker_update_database' );
wp_clear_scheduled_hook( 'geo_ip_blocker_update_geoip_db' );

// Flush rewrite rules.
flush_rewrite_rules();
