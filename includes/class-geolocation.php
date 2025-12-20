<?php
/**
 * Geolocation handler class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_Blocker_Geolocation
 *
 * Handles IP detection and geolocation with multiple API providers and caching.
 */
class Geo_Blocker_Geolocation {

	/**
	 * Cache expiration time in seconds (30 minutes default).
	 *
	 * @var int
	 */
	private $cache_expiration = 1800;

	/**
	 * API providers priority list.
	 *
	 * @var array
	 */
	private $api_providers = array(
		'maxmind',
		'ip2location',
		'ipapi',
	);

	/**
	 * Rate limit tracking.
	 *
	 * @var array
	 */
	private $rate_limits = array(
		'ipapi' => array(
			'max_requests' => 45,
			'period'       => 60, // seconds
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->cache_expiration = apply_filters( 'geo_ip_blocker_cache_expiration', $this->cache_expiration );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Schedule cron for database updates.
		add_action( 'geo_ip_blocker_update_database', array( $this, 'update_local_database' ) );

		// Register activation hook for cron.
		if ( ! wp_next_scheduled( 'geo_ip_blocker_update_database' ) ) {
			wp_schedule_event( time(), 'weekly', 'geo_ip_blocker_update_database' );
		}
	}

	/**
	 * Get visitor IP address.
	 *
	 * Supports proxies, CDNs, and various server configurations.
	 *
	 * @return string|false IP address or false if unable to detect.
	 */
	public function get_visitor_ip() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',  // Cloudflare.
			'HTTP_X_REAL_IP',         // Nginx proxy.
			'HTTP_X_FORWARDED_FOR',   // General proxy.
			'HTTP_X_FORWARDED',       // General proxy.
			'HTTP_FORWARDED_FOR',     // RFC 7239.
			'HTTP_FORWARDED',         // RFC 7239.
			'HTTP_CLIENT_IP',         // Client IP.
			'REMOTE_ADDR',            // Direct connection.
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip_list = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

				// Handle multiple IPs (comma-separated).
				if ( strpos( $ip_list, ',' ) !== false ) {
					$ips = array_map( 'trim', explode( ',', $ip_list ) );
					foreach ( $ips as $ip ) {
						if ( $this->validate_ip( $ip ) ) {
							return $ip;
						}
					}
				} else {
					if ( $this->validate_ip( $ip_list ) ) {
						return $ip_list;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Validate IP address.
	 *
	 * @param string $ip IP address to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_ip( $ip ) {
		// Validate IPv4 or IPv6.
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) === false ) {
			return false;
		}

		// Exclude private and reserved ranges for production.
		// Allow them for local development.
		$is_local = defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV;

		if ( ! $is_local ) {
			// Exclude private and reserved IPs.
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get complete location data for an IP address.
	 *
	 * @param string $ip IP address.
	 * @return array|false Location data or false on failure.
	 */
	public function get_location_data( $ip ) {
		if ( ! $this->validate_ip( $ip ) ) {
			return false;
		}

		// Check cache first.
		$cached_data = $this->get_cached_location( $ip );
		if ( $cached_data !== false ) {
			return $cached_data;
		}

		$location_data = false;

		// Try local database first if available.
		$use_local_db = geo_ip_blocker_get_setting( 'use_local_database', false );
		if ( $use_local_db ) {
			$location_data = $this->query_local_database( $ip );
		}

		// Fallback to API providers.
		if ( ! $location_data ) {
			$enabled_providers = geo_ip_blocker_get_setting( 'enabled_providers', $this->api_providers );

			foreach ( $enabled_providers as $provider ) {
				$location_data = $this->query_api( $ip, $provider );

				if ( $location_data ) {
					break;
				}

				// Small delay between API calls to respect rate limits.
				usleep( 100000 ); // 0.1 second.
			}
		}

		// If all methods failed, return default data.
		if ( ! $location_data ) {
			$location_data = array(
				'ip'           => $ip,
				'country_code' => 'UNKNOWN',
				'country_name' => 'Unknown',
				'region'       => '',
				'city'         => '',
				'latitude'     => 0,
				'longitude'    => 0,
			);
		}

		// Cache the result.
		$this->cache_location( $ip, $location_data );

		return $location_data;
	}

	/**
	 * Get country code for an IP address.
	 *
	 * @param string $ip IP address.
	 * @return string Country code (2 letters) or 'UNKNOWN'.
	 */
	public function get_country_code( $ip ) {
		$location_data = $this->get_location_data( $ip );
		return $location_data ? $location_data['country_code'] : 'UNKNOWN';
	}

	/**
	 * Get region for an IP address.
	 *
	 * @param string $ip IP address.
	 * @return string Region name.
	 */
	public function get_region( $ip ) {
		$location_data = $this->get_location_data( $ip );
		return $location_data ? $location_data['region'] : '';
	}

	/**
	 * Get city for an IP address.
	 *
	 * @param string $ip IP address.
	 * @return string City name.
	 */
	public function get_city( $ip ) {
		$location_data = $this->get_location_data( $ip );
		return $location_data ? $location_data['city'] : '';
	}

	/**
	 * Query API provider for location data.
	 *
	 * @param string $ip       IP address.
	 * @param string $provider Provider name.
	 * @return array|false Location data or false on failure.
	 */
	private function query_api( $ip, $provider ) {
		// Check rate limits.
		if ( ! $this->check_rate_limit( $provider ) ) {
			error_log( sprintf( 'Geo IP Blocker: Rate limit exceeded for provider %s', $provider ) );
			return false;
		}

		$location_data = false;

		switch ( $provider ) {
			case 'maxmind':
				$location_data = $this->query_maxmind( $ip );
				break;

			case 'ip2location':
				$location_data = $this->query_ip2location( $ip );
				break;

			case 'ipapi':
				$location_data = $this->query_ipapi( $ip );
				break;

			default:
				$location_data = apply_filters( 'geo_ip_blocker_query_custom_provider', false, $ip, $provider );
				break;
		}

		// Track API request for rate limiting.
		if ( $location_data !== false ) {
			$this->track_api_request( $provider );
		}

		return $location_data;
	}

	/**
	 * Query MaxMind GeoIP2 API.
	 *
	 * @param string $ip IP address.
	 * @return array|false Location data or false on failure.
	 */
	private function query_maxmind( $ip ) {
		$account_id  = geo_ip_blocker_get_setting( 'maxmind_account_id', '' );
		$license_key = geo_ip_blocker_get_setting( 'maxmind_license_key', '' );

		if ( empty( $account_id ) || empty( $license_key ) ) {
			return false;
		}

		$url = sprintf( 'https://geoip.maxmind.com/geoip/v2.1/city/%s', $ip );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 5,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $account_id . ':' . $license_key ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Geo IP Blocker MaxMind Error: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || ! isset( $data['country']['iso_code'] ) ) {
			return false;
		}

		return array(
			'ip'           => $ip,
			'country_code' => sanitize_text_field( $data['country']['iso_code'] ),
			'country_name' => sanitize_text_field( $data['country']['names']['en'] ?? '' ),
			'region'       => sanitize_text_field( $data['subdivisions'][0]['names']['en'] ?? '' ),
			'city'         => sanitize_text_field( $data['city']['names']['en'] ?? '' ),
			'latitude'     => floatval( $data['location']['latitude'] ?? 0 ),
			'longitude'    => floatval( $data['location']['longitude'] ?? 0 ),
		);
	}

	/**
	 * Query IP2Location API.
	 *
	 * @param string $ip IP address.
	 * @return array|false Location data or false on failure.
	 */
	private function query_ip2location( $ip ) {
		$api_key = geo_ip_blocker_get_setting( 'ip2location_api_key', '' );

		if ( empty( $api_key ) ) {
			return false;
		}

		$url = add_query_arg(
			array(
				'key'     => $api_key,
				'ip'      => $ip,
				'package' => 'WS10',
				'format'  => 'json',
			),
			'https://api.ip2location.com/v2/'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Geo IP Blocker IP2Location Error: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || ! isset( $data['country_code'] ) ) {
			return false;
		}

		return array(
			'ip'           => $ip,
			'country_code' => sanitize_text_field( $data['country_code'] ),
			'country_name' => sanitize_text_field( $data['country_name'] ?? '' ),
			'region'       => sanitize_text_field( $data['region_name'] ?? '' ),
			'city'         => sanitize_text_field( $data['city_name'] ?? '' ),
			'latitude'     => floatval( $data['latitude'] ?? 0 ),
			'longitude'    => floatval( $data['longitude'] ?? 0 ),
		);
	}

	/**
	 * Query IP-API.com (free service with rate limit).
	 *
	 * @param string $ip IP address.
	 * @return array|false Location data or false on failure.
	 */
	private function query_ipapi( $ip ) {
		$url = sprintf( 'http://ip-api.com/json/%s?fields=status,message,country,countryCode,region,regionName,city,lat,lon', $ip );

		$response = wp_remote_get( $url, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Geo IP Blocker IP-API Error: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || $data['status'] !== 'success' ) {
			if ( isset( $data['message'] ) ) {
				error_log( 'Geo IP Blocker IP-API Error: ' . $data['message'] );
			}
			return false;
		}

		return array(
			'ip'           => $ip,
			'country_code' => sanitize_text_field( $data['countryCode'] ),
			'country_name' => sanitize_text_field( $data['country'] ?? '' ),
			'region'       => sanitize_text_field( $data['regionName'] ?? '' ),
			'city'         => sanitize_text_field( $data['city'] ?? '' ),
			'latitude'     => floatval( $data['lat'] ?? 0 ),
			'longitude'    => floatval( $data['lon'] ?? 0 ),
		);
	}

	/**
	 * Query local GeoIP2 database.
	 *
	 * @param string $ip IP address.
	 * @return array|false Location data or false on failure.
	 */
	private function query_local_database( $ip ) {
		$upload_dir = wp_upload_dir();
		$db_path    = $upload_dir['basedir'] . '/geo-ip-blocker/GeoLite2-City.mmdb';

		if ( ! file_exists( $db_path ) ) {
			return false;
		}

		// Check if MaxMind Reader class exists.
		if ( ! class_exists( 'GeoIp2\Database\Reader' ) ) {
			// Try to load from composer if available.
			$composer_autoload = GEO_IP_BLOCKER_PLUGIN_DIR . 'vendor/autoload.php';
			if ( file_exists( $composer_autoload ) ) {
				require_once $composer_autoload;
			} else {
				return false;
			}
		}

		try {
			$reader = new \GeoIp2\Database\Reader( $db_path );
			$record = $reader->city( $ip );

			return array(
				'ip'           => $ip,
				'country_code' => $record->country->isoCode ?? 'UNKNOWN',
				'country_name' => $record->country->name ?? '',
				'region'       => $record->mostSpecificSubdivision->name ?? '',
				'city'         => $record->city->name ?? '',
				'latitude'     => $record->location->latitude ?? 0,
				'longitude'    => $record->location->longitude ?? 0,
			);
		} catch ( Exception $e ) {
			error_log( 'Geo IP Blocker Local DB Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Cache location data.
	 *
	 * @param string $ip   IP address.
	 * @param array  $data Location data.
	 * @return bool True on success, false on failure.
	 */
	private function cache_location( $ip, $data ) {
		$cache_key = $this->get_cache_key( $ip );
		return set_transient( $cache_key, $data, $this->cache_expiration );
	}

	/**
	 * Get cached location data.
	 *
	 * @param string $ip IP address.
	 * @return array|false Cached data or false if not found.
	 */
	private function get_cached_location( $ip ) {
		$cache_key = $this->get_cache_key( $ip );
		return get_transient( $cache_key );
	}

	/**
	 * Get cache key for an IP address.
	 *
	 * @param string $ip IP address.
	 * @return string Cache key.
	 */
	private function get_cache_key( $ip ) {
		return 'geo_blocker_ip_' . md5( $ip );
	}

	/**
	 * Check rate limit for API provider.
	 *
	 * @param string $provider Provider name.
	 * @return bool True if within limits, false if exceeded.
	 */
	private function check_rate_limit( $provider ) {
		if ( ! isset( $this->rate_limits[ $provider ] ) ) {
			return true;
		}

		$limit        = $this->rate_limits[ $provider ];
		$cache_key    = 'geo_blocker_rate_' . $provider;
		$request_data = get_transient( $cache_key );

		if ( ! $request_data ) {
			return true;
		}

		return $request_data['count'] < $limit['max_requests'];
	}

	/**
	 * Track API request for rate limiting.
	 *
	 * @param string $provider Provider name.
	 */
	private function track_api_request( $provider ) {
		if ( ! isset( $this->rate_limits[ $provider ] ) ) {
			return;
		}

		$limit     = $this->rate_limits[ $provider ];
		$cache_key = 'geo_blocker_rate_' . $provider;

		$request_data = get_transient( $cache_key );

		if ( ! $request_data ) {
			$request_data = array(
				'count'      => 0,
				'start_time' => time(),
			);
		}

		$request_data['count']++;

		set_transient( $cache_key, $request_data, $limit['period'] );
	}

	/**
	 * Update local GeoIP2 database.
	 *
	 * Downloads the latest GeoLite2 database from MaxMind.
	 */
	public function update_local_database() {
		$license_key = geo_ip_blocker_get_setting( 'maxmind_license_key', '' );

		if ( empty( $license_key ) ) {
			error_log( 'Geo IP Blocker: MaxMind license key required for database updates' );
			return;
		}

		$upload_dir = wp_upload_dir();
		$target_dir = $upload_dir['basedir'] . '/geo-ip-blocker';

		// Create directory if it doesn't exist.
		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
		}

		// Download URL for GeoLite2 City database.
		$download_url = add_query_arg(
			array(
				'edition_id'  => 'GeoLite2-City',
				'license_key' => $license_key,
				'suffix'      => 'tar.gz',
			),
			'https://download.maxmind.com/app/geoip_download'
		);

		$temp_file = download_url( $download_url );

		if ( is_wp_error( $temp_file ) ) {
			error_log( 'Geo IP Blocker: Failed to download database - ' . $temp_file->get_error_message() );
			return;
		}

		// Extract the .mmdb file from the archive.
		try {
			$phar = new PharData( $temp_file );
			$phar->extractTo( $target_dir, null, true );

			// Find and move the .mmdb file.
			$files = glob( $target_dir . '/GeoLite2-City_*/GeoLite2-City.mmdb' );
			if ( ! empty( $files ) ) {
				rename( $files[0], $target_dir . '/GeoLite2-City.mmdb' );

				// Clean up extracted directory.
				$extract_dir = dirname( $files[0] );
				$this->delete_directory( $extract_dir );
			}

			// Clean up temp file.
			unlink( $temp_file );

			update_option( 'geo_ip_blocker_db_last_update', time() );

			error_log( 'Geo IP Blocker: Database updated successfully' );
		} catch ( Exception $e ) {
			error_log( 'Geo IP Blocker: Failed to extract database - ' . $e->getMessage() );
			unlink( $temp_file );
		}
	}

	/**
	 * Delete directory recursively.
	 *
	 * @param string $dir Directory path.
	 */
	private function delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );

		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			is_dir( $path ) ? $this->delete_directory( $path ) : unlink( $path );
		}

		rmdir( $dir );
	}

	/**
	 * Clear location cache.
	 *
	 * @param string $ip Optional. Specific IP to clear. If empty, clears all.
	 */
	public function clear_cache( $ip = '' ) {
		global $wpdb;

		if ( ! empty( $ip ) ) {
			$cache_key = $this->get_cache_key( $ip );
			delete_transient( $cache_key );
		} else {
			// Clear all geo blocker transients.
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_geo_blocker_ip_%'" );
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_geo_blocker_ip_%'" );
		}
	}
}
