<?php
/**
 * Cache Handler
 *
 * Handles caching for improved performance
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_Cache
 *
 * Provides caching utilities for the plugin
 */
class Geo_IP_Blocker_Cache {

	/**
	 * Singleton instance.
	 *
	 * @var Geo_IP_Blocker_Cache
	 */
	private static $instance = null;

	/**
	 * Cache group name.
	 *
	 * @var string
	 */
	private $cache_group = 'geo_ip_blocker';

	/**
	 * Default cache expiration (1 hour).
	 *
	 * @var int
	 */
	private $default_expiration = 3600;

	/**
	 * Get singleton instance.
	 *
	 * @return Geo_IP_Blocker_Cache
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Register cache group if using persistent object cache.
		if ( function_exists( 'wp_cache_add_global_groups' ) ) {
			wp_cache_add_global_groups( array( $this->cache_group ) );
		}
	}

	/**
	 * Get cached value from WordPress object cache
	 *
	 * @param string $key Cache key.
	 * @return mixed|false Cached value or false if not found.
	 */
	public function get( $key ) {
		return wp_cache_get( $key, $this->cache_group );
	}

	/**
	 * Set value in WordPress object cache
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Expiration time in seconds (default: 1 hour).
	 * @return bool True on success, false on failure.
	 */
	public function set( $key, $value, $expiration = null ) {
		if ( null === $expiration ) {
			$expiration = $this->default_expiration;
		}

		return wp_cache_set( $key, $value, $this->cache_group, $expiration );
	}

	/**
	 * Delete value from cache
	 *
	 * @param string $key Cache key.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $key ) {
		return wp_cache_delete( $key, $this->cache_group );
	}

	/**
	 * Flush entire cache group
	 *
	 * @return bool True on success.
	 */
	public function flush() {
		// Flush object cache group.
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( $this->cache_group );
		}

		// Also flush related transients.
		$this->flush_transients();

		return true;
	}

	/**
	 * Cache geolocation data for an IP
	 *
	 * @param string $ip   IP address.
	 * @param array  $data Geolocation data.
	 * @return bool True on success.
	 */
	public function cache_location( $ip, $data ) {
		$key = 'geo_' . md5( $ip );

		// Cache in object cache.
		$this->set( $key, $data, 30 * MINUTE_IN_SECONDS );

		// Also cache as transient for persistence.
		set_transient( $this->cache_group . '_' . $key, $data, 30 * MINUTE_IN_SECONDS );

		return true;
	}

	/**
	 * Get cached geolocation data for an IP
	 *
	 * @param string $ip IP address.
	 * @return array|false Geolocation data or false if not cached.
	 */
	public function get_location( $ip ) {
		$key = 'geo_' . md5( $ip );

		// Try object cache first.
		$data = $this->get( $key );

		// If not in object cache, try transient.
		if ( false === $data ) {
			$data = get_transient( $this->cache_group . '_' . $key );

			// If found in transient, restore to object cache.
			if ( false !== $data ) {
				$this->set( $key, $data, 30 * MINUTE_IN_SECONDS );
			}
		}

		return $data;
	}

	/**
	 * Cache country list
	 *
	 * @param array $countries Country list.
	 * @return bool True on success.
	 */
	public function cache_countries( $countries ) {
		$key = 'countries_list';

		// Cache for 24 hours.
		$this->set( $key, $countries, DAY_IN_SECONDS );
		set_transient( $this->cache_group . '_' . $key, $countries, DAY_IN_SECONDS );

		return true;
	}

	/**
	 * Get cached country list
	 *
	 * @return array|false Country list or false if not cached.
	 */
	public function get_countries() {
		$key  = 'countries_list';
		$data = $this->get( $key );

		if ( false === $data ) {
			$data = get_transient( $this->cache_group . '_' . $key );

			if ( false !== $data ) {
				$this->set( $key, $data, DAY_IN_SECONDS );
			}
		}

		return $data;
	}

	/**
	 * Cache settings
	 *
	 * @param array $settings Plugin settings.
	 * @return bool True on success.
	 */
	public function cache_settings( $settings ) {
		$key = 'plugin_settings';

		// Cache settings for 1 hour.
		return $this->set( $key, $settings, HOUR_IN_SECONDS );
	}

	/**
	 * Get cached settings
	 *
	 * @return array|false Settings or false if not cached.
	 */
	public function get_settings() {
		return $this->get( 'plugin_settings' );
	}

	/**
	 * Invalidate settings cache
	 *
	 * @return bool True on success.
	 */
	public function invalidate_settings() {
		return $this->delete( 'plugin_settings' );
	}

	/**
	 * Cache IP whitelist/blacklist
	 *
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @param array  $ips       IP addresses.
	 * @return bool True on success.
	 */
	public function cache_ip_list( $list_type, $ips ) {
		$key = 'ip_list_' . $list_type;

		// Cache for 1 hour.
		$this->set( $key, $ips, HOUR_IN_SECONDS );
		set_transient( $this->cache_group . '_' . $key, $ips, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Get cached IP list
	 *
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return array|false IP list or false if not cached.
	 */
	public function get_ip_list( $list_type ) {
		$key  = 'ip_list_' . $list_type;
		$data = $this->get( $key );

		if ( false === $data ) {
			$data = get_transient( $this->cache_group . '_' . $key );

			if ( false !== $data ) {
				$this->set( $key, $data, HOUR_IN_SECONDS );
			}
		}

		return $data;
	}

	/**
	 * Flush all plugin transients
	 *
	 * @return int Number of transients deleted.
	 */
	public function flush_transients() {
		global $wpdb;

		$prefix = $this->cache_group . '_';

		$sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE %s
			OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_' . $prefix ) . '%',
			$wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%'
		);

		return $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Check if cache plugin is active
	 *
	 * @return bool True if cache plugin detected.
	 */
	public function has_cache_plugin() {
		$cache_plugins = array(
			'wp-rocket/wp-rocket.php',
			'w3-total-cache/w3-total-cache.php',
			'wp-super-cache/wp-cache.php',
			'litespeed-cache/litespeed-cache.php',
			'wp-fastest-cache/wpFastestCache.php',
			'cache-enabler/cache-enabler.php',
			'comet-cache/comet-cache.php',
			'hyper-cache/plugin.php',
		);

		foreach ( $cache_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Clear all caches (plugin and third-party)
	 *
	 * @return bool True on success.
	 */
	public function clear_all_caches() {
		// Clear plugin cache.
		$this->flush();

		// Clear WordPress object cache.
		wp_cache_flush();

		// Clear WP Rocket cache.
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// Clear W3 Total Cache.
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		// Clear WP Super Cache.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		// Clear LiteSpeed Cache.
		if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
			LiteSpeed_Cache_API::purge_all();
		}

		// Clear WP Fastest Cache.
		if ( class_exists( 'WpFastestCache' ) ) {
			$wpfc = new WpFastestCache();
			if ( method_exists( $wpfc, 'deleteCache' ) ) {
				$wpfc->deleteCache( true );
			}
		}

		// Clear Cache Enabler.
		if ( class_exists( 'Cache_Enabler' ) && method_exists( 'Cache_Enabler', 'clear_complete_cache' ) ) {
			Cache_Enabler::clear_complete_cache();
		}

		// Clear Comet Cache.
		if ( class_exists( 'comet_cache' ) && method_exists( 'comet_cache', 'clear' ) ) {
			comet_cache::clear();
		}

		do_action( 'geo_ip_blocker_cache_cleared' );

		return true;
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache statistics.
	 */
	public function get_stats() {
		global $wpdb;

		$prefix = $this->cache_group . '_';

		// Count transients.
		$transient_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options}
				WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $prefix ) . '%'
			)
		);

		return array(
			'transient_count' => (int) $transient_count,
			'cache_group'     => $this->cache_group,
			'has_object_cache' => wp_using_ext_object_cache(),
			'cache_plugin'    => $this->has_cache_plugin(),
		);
	}

	/**
	 * Warm up cache with common data
	 *
	 * @return bool True on success.
	 */
	public function warm_up() {
		// Load and cache settings.
		$settings = get_option( 'geo_ip_blocker_settings', array() );
		$this->cache_settings( $settings );

		// Load and cache country list.
		$countries = geo_ip_blocker_get_countries();
		$this->cache_countries( $countries );

		// Load and cache IP lists.
		$ip_manager = geo_ip_blocker_get_ip_manager();
		if ( $ip_manager ) {
			$this->cache_ip_list( 'whitelist', $ip_manager->get_whitelist() );
			$this->cache_ip_list( 'blacklist', $ip_manager->get_blacklist() );
		}

		do_action( 'geo_ip_blocker_cache_warmed_up' );

		return true;
	}
}

/**
 * Get cache instance
 *
 * @return Geo_IP_Blocker_Cache
 */
function geo_ip_blocker_get_cache() {
	return Geo_IP_Blocker_Cache::instance();
}
