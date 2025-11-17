<?php
/**
 * Rate Limiter Handler
 *
 * Handles rate limiting for API calls and actions
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_Rate_Limiter
 *
 * Provides rate limiting utilities for the plugin
 */
class Geo_IP_Blocker_Rate_Limiter {

	/**
	 * Singleton instance.
	 *
	 * @var Geo_IP_Blocker_Rate_Limiter
	 */
	private static $instance = null;

	/**
	 * Rate limit prefix.
	 *
	 * @var string
	 */
	private $prefix = 'geo_ip_blocker_rate_';

	/**
	 * Default rate limits.
	 *
	 * @var array
	 */
	private $limits = array(
		'api_calls'       => array(
			'limit' => 100,
			'period' => HOUR_IN_SECONDS,
		),
		'geo_lookups'     => array(
			'limit' => 500,
			'period' => HOUR_IN_SECONDS,
		),
		'settings_update' => array(
			'limit' => 10,
			'period' => MINUTE_IN_SECONDS,
		),
		'export'          => array(
			'limit' => 5,
			'period' => HOUR_IN_SECONDS,
		),
		'import'          => array(
			'limit' => 5,
			'period' => HOUR_IN_SECONDS,
		),
	);

	/**
	 * Get singleton instance.
	 *
	 * @return Geo_IP_Blocker_Rate_Limiter
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
		// Allow customization of rate limits.
		$this->limits = apply_filters( 'geo_ip_blocker_rate_limits', $this->limits );
	}

	/**
	 * Check if action is rate limited
	 *
	 * @param string $action Action identifier.
	 * @param string $identifier Unique identifier (IP, user ID, etc.).
	 * @return bool True if allowed, false if rate limited.
	 */
	public function check_rate_limit( $action, $identifier = null ) {
		// Get identifier.
		if ( null === $identifier ) {
			$identifier = $this->get_identifier();
		}

		// Get limit configuration.
		if ( ! isset( $this->limits[ $action ] ) ) {
			return true; // No limit configured, allow.
		}

		$limit  = $this->limits[ $action ]['limit'];
		$period = $this->limits[ $action ]['period'];

		// Get transient key.
		$transient_key = $this->get_transient_key( $action, $identifier );

		// Get current attempts.
		$attempts = get_transient( $transient_key );

		if ( false === $attempts ) {
			// First attempt.
			set_transient( $transient_key, 1, $period );
			return true;
		}

		// Check if limit exceeded.
		if ( $attempts >= $limit ) {
			do_action( 'geo_ip_blocker_rate_limit_exceeded', $action, $identifier, $attempts );
			return false;
		}

		// Increment attempts.
		set_transient( $transient_key, $attempts + 1, $period );

		return true;
	}

	/**
	 * Get current attempt count
	 *
	 * @param string $action Action identifier.
	 * @param string $identifier Unique identifier.
	 * @return int Number of attempts.
	 */
	public function get_attempts( $action, $identifier = null ) {
		if ( null === $identifier ) {
			$identifier = $this->get_identifier();
		}

		$transient_key = $this->get_transient_key( $action, $identifier );
		$attempts      = get_transient( $transient_key );

		return $attempts ? (int) $attempts : 0;
	}

	/**
	 * Get remaining attempts
	 *
	 * @param string $action Action identifier.
	 * @param string $identifier Unique identifier.
	 * @return int Remaining attempts.
	 */
	public function get_remaining( $action, $identifier = null ) {
		if ( ! isset( $this->limits[ $action ] ) ) {
			return -1; // Unlimited.
		}

		$limit    = $this->limits[ $action ]['limit'];
		$attempts = $this->get_attempts( $action, $identifier );

		return max( 0, $limit - $attempts );
	}

	/**
	 * Check if identifier is currently rate limited
	 *
	 * @param string $action Action identifier.
	 * @param string $identifier Unique identifier.
	 * @return bool True if rate limited.
	 */
	public function is_rate_limited( $action, $identifier = null ) {
		if ( ! isset( $this->limits[ $action ] ) ) {
			return false;
		}

		if ( null === $identifier ) {
			$identifier = $this->get_identifier();
		}

		$limit    = $this->limits[ $action ]['limit'];
		$attempts = $this->get_attempts( $action, $identifier );

		return $attempts >= $limit;
	}

	/**
	 * Reset rate limit for identifier
	 *
	 * @param string $action Action identifier.
	 * @param string $identifier Unique identifier.
	 * @return bool True on success.
	 */
	public function reset_limit( $action, $identifier = null ) {
		if ( null === $identifier ) {
			$identifier = $this->get_identifier();
		}

		$transient_key = $this->get_transient_key( $action, $identifier );

		return delete_transient( $transient_key );
	}

	/**
	 * Reset all rate limits
	 *
	 * @return int Number of transients deleted.
	 */
	public function reset_all() {
		global $wpdb;

		$sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE %s
			OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_' . $this->prefix ) . '%',
			$wpdb->esc_like( '_transient_timeout_' . $this->prefix ) . '%'
		);

		return $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get time until rate limit resets
	 *
	 * @param string $action Action identifier.
	 * @param string $identifier Unique identifier.
	 * @return int Seconds until reset, 0 if not limited.
	 */
	public function get_reset_time( $action, $identifier = null ) {
		if ( null === $identifier ) {
			$identifier = $this->get_identifier();
		}

		$transient_key = $this->get_transient_key( $action, $identifier );
		$timeout_key   = '_transient_timeout_' . $transient_key;

		$timeout = get_option( $timeout_key );

		if ( ! $timeout ) {
			return 0;
		}

		$remaining = $timeout - time();

		return max( 0, $remaining );
	}

	/**
	 * Set custom rate limit
	 *
	 * @param string $action Action identifier.
	 * @param int    $limit Maximum number of attempts.
	 * @param int    $period Time period in seconds.
	 * @return bool True on success.
	 */
	public function set_limit( $action, $limit, $period ) {
		$this->limits[ $action ] = array(
			'limit'  => absint( $limit ),
			'period' => absint( $period ),
		);

		return true;
	}

	/**
	 * Get rate limit configuration
	 *
	 * @param string $action Action identifier.
	 * @return array|false Limit configuration or false if not set.
	 */
	public function get_limit( $action ) {
		return isset( $this->limits[ $action ] ) ? $this->limits[ $action ] : false;
	}

	/**
	 * Block identifier temporarily
	 *
	 * @param string $identifier Unique identifier.
	 * @param int    $duration Block duration in seconds (default: 1 hour).
	 * @return bool True on success.
	 */
	public function block_identifier( $identifier, $duration = HOUR_IN_SECONDS ) {
		$block_key = $this->prefix . 'blocked_' . md5( $identifier );

		return set_transient( $block_key, true, $duration );
	}

	/**
	 * Check if identifier is blocked
	 *
	 * @param string $identifier Unique identifier.
	 * @return bool True if blocked.
	 */
	public function is_blocked( $identifier ) {
		$block_key = $this->prefix . 'blocked_' . md5( $identifier );

		return (bool) get_transient( $block_key );
	}

	/**
	 * Unblock identifier
	 *
	 * @param string $identifier Unique identifier.
	 * @return bool True on success.
	 */
	public function unblock_identifier( $identifier ) {
		$block_key = $this->prefix . 'blocked_' . md5( $identifier );

		return delete_transient( $block_key );
	}

	/**
	 * Get statistics for all rate limits
	 *
	 * @return array Statistics.
	 */
	public function get_statistics() {
		global $wpdb;

		// Count active rate limit transients.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options}
				WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $this->prefix ) . '%'
			)
		);

		return array(
			'active_limits' => (int) $count,
			'configured_actions' => count( $this->limits ),
			'actions' => array_keys( $this->limits ),
		);
	}

	/**
	 * Get transient key for action and identifier
	 *
	 * @param string $action Action identifier.
	 * @param string $identifier Unique identifier.
	 * @return string Transient key.
	 */
	private function get_transient_key( $action, $identifier ) {
		return $this->prefix . $action . '_' . md5( $identifier );
	}

	/**
	 * Get default identifier (IP or user ID)
	 *
	 * @return string Identifier.
	 */
	private function get_identifier() {
		// For logged-in users, use user ID.
		if ( is_user_logged_in() ) {
			return 'user_' . get_current_user_id();
		}

		// Otherwise, use IP address.
		$geolocation = geo_ip_blocker_get_geolocation();
		$ip          = $geolocation ? $geolocation->get_visitor_ip() : '';

		return 'ip_' . $ip;
	}

	/**
	 * Cleanup old rate limit transients
	 *
	 * This is called automatically by WordPress when transients expire,
	 * but can be called manually to force cleanup.
	 *
	 * @return int Number of transients deleted.
	 */
	public function cleanup() {
		global $wpdb;

		// Delete expired transients.
		$sql = "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
				AND b.option_value < %d";

		$result = $wpdb->query(
			$wpdb->prepare(
				$sql,
				$wpdb->esc_like( '_transient_' . $this->prefix ) . '%',
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);

		return $result;
	}

	/**
	 * Log rate limit event
	 *
	 * @param string $action Action identifier.
	 * @param string $identifier Unique identifier.
	 * @param string $status Status (allowed, limited, blocked).
	 */
	private function log_event( $action, $identifier, $status ) {
		if ( ! defined( 'GEO_IP_BLOCKER_RATE_LIMIT_LOG' ) || ! GEO_IP_BLOCKER_RATE_LIMIT_LOG ) {
			return;
		}

		$log_entry = array(
			'timestamp'  => current_time( 'mysql' ),
			'action'     => $action,
			'identifier' => $identifier,
			'status'     => $status,
			'attempts'   => $this->get_attempts( $action, $identifier ),
		);

		error_log( 'GEO_IP_BLOCKER_RATE_LIMIT: ' . wp_json_encode( $log_entry ) );
	}
}

/**
 * Get rate limiter instance
 *
 * @return Geo_IP_Blocker_Rate_Limiter
 */
function geo_ip_blocker_get_rate_limiter() {
	return Geo_IP_Blocker_Rate_Limiter::instance();
}
