<?php
/**
 * Security Handler
 *
 * Handles security validations, sanitization, and permissions
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_Security
 *
 * Provides security utilities for the plugin
 */
class Geo_IP_Blocker_Security {

	/**
	 * Singleton instance.
	 *
	 * @var Geo_IP_Blocker_Security
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Geo_IP_Blocker_Security
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
		// Private to enforce singleton.
	}

	/**
	 * Verify nonce for AJAX requests
	 *
	 * @param string $nonce_field Nonce field name.
	 * @param string $action      Nonce action.
	 * @return bool True if valid, dies otherwise.
	 */
	public function verify_ajax_nonce( $nonce_field = 'nonce', $action = 'geo_ip_blocker_settings_nonce' ) {
		check_ajax_referer( $action, $nonce_field );
		return true;
	}

	/**
	 * Verify nonce for regular form submissions
	 *
	 * @param string $nonce_field Nonce field name.
	 * @param string $action      Nonce action.
	 * @return bool True if valid, false otherwise.
	 */
	public function verify_nonce( $nonce_field = '_wpnonce', $action = 'geo_ip_blocker_action' ) {
		if ( ! isset( $_POST[ $nonce_field ] ) ) {
			return false;
		}

		return wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), $action );
	}

	/**
	 * Check if current user has required capability
	 *
	 * @param string $capability Required capability (default: manage_options).
	 * @param bool   $die        Whether to die if permission denied.
	 * @return bool True if user has capability.
	 */
	public function check_permission( $capability = 'manage_options', $die = false ) {
		if ( ! current_user_can( $capability ) ) {
			if ( $die ) {
				wp_die(
					esc_html__( 'Você não tem permissão para acessar esta página.', 'geo-ip-blocker' ),
					esc_html__( 'Permissão Negada', 'geo-ip-blocker' ),
					array( 'response' => 403 )
				);
			}
			return false;
		}
		return true;
	}

	/**
	 * Validate and sanitize IP address
	 *
	 * Supports IPv4, IPv6, CIDR notation, and ranges
	 *
	 * @param string $ip IP address to validate.
	 * @return string|false Sanitized IP or false if invalid.
	 */
	public function validate_ip( $ip ) {
		$ip = trim( $ip );

		// Check for CIDR notation (e.g., 192.168.1.0/24).
		if ( strpos( $ip, '/' ) !== false ) {
			list( $ip_part, $cidr ) = explode( '/', $ip, 2 );

			// Validate IP part.
			if ( ! filter_var( $ip_part, FILTER_VALIDATE_IP ) ) {
				return false;
			}

			// Validate CIDR.
			$cidr = (int) $cidr;
			if ( $cidr < 0 || $cidr > 128 ) {
				return false;
			}

			return sanitize_text_field( $ip_part . '/' . $cidr );
		}

		// Check for range (e.g., 192.168.1.1-192.168.1.50).
		if ( strpos( $ip, '-' ) !== false ) {
			$parts = explode( '-', $ip, 2 );
			if ( count( $parts ) !== 2 ) {
				return false;
			}

			// Validate both IPs.
			if ( ! filter_var( trim( $parts[0] ), FILTER_VALIDATE_IP ) ||
				 ! filter_var( trim( $parts[1] ), FILTER_VALIDATE_IP ) ) {
				return false;
			}

			return sanitize_text_field( trim( $parts[0] ) . '-' . trim( $parts[1] ) );
		}

		// Regular IP validation.
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return sanitize_text_field( $ip );
		}

		return false;
	}

	/**
	 * Validate and sanitize list of IPs
	 *
	 * @param array $ip_list Array of IP addresses.
	 * @return array Array of validated IPs.
	 */
	public function validate_ip_list( $ip_list ) {
		if ( ! is_array( $ip_list ) ) {
			return array();
		}

		$validated = array();

		foreach ( $ip_list as $ip ) {
			$validated_ip = $this->validate_ip( $ip );
			if ( $validated_ip ) {
				$validated[] = $validated_ip;
			}
		}

		return array_unique( $validated );
	}

	/**
	 * Validate country code
	 *
	 * @param string $code Country code.
	 * @return string|false Validated code or false.
	 */
	public function validate_country_code( $code ) {
		$code = strtoupper( trim( $code ) );

		// ISO 3166-1 alpha-2 country codes are exactly 2 characters.
		if ( strlen( $code ) !== 2 ) {
			return false;
		}

		// Only allow A-Z.
		if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) {
			return false;
		}

		return $code;
	}

	/**
	 * Validate list of country codes
	 *
	 * @param array $codes Array of country codes.
	 * @return array Array of validated country codes.
	 */
	public function validate_country_codes( $codes ) {
		if ( ! is_array( $codes ) ) {
			return array();
		}

		$validated = array();

		foreach ( $codes as $code ) {
			$validated_code = $this->validate_country_code( $code );
			if ( $validated_code ) {
				$validated[] = $validated_code;
			}
		}

		return array_unique( $validated );
	}

	/**
	 * Sanitize URL
	 *
	 * @param string $url URL to sanitize.
	 * @return string Sanitized URL.
	 */
	public function sanitize_url( $url ) {
		return esc_url_raw( $url );
	}

	/**
	 * Sanitize text field
	 *
	 * @param string $text Text to sanitize.
	 * @return string Sanitized text.
	 */
	public function sanitize_text( $text ) {
		return sanitize_text_field( $text );
	}

	/**
	 * Sanitize HTML content
	 *
	 * Allows only safe HTML tags
	 *
	 * @param string $html HTML content.
	 * @return string Sanitized HTML.
	 */
	public function sanitize_html( $html ) {
		return wp_kses_post( $html );
	}

	/**
	 * Sanitize boolean value
	 *
	 * @param mixed $value Value to sanitize.
	 * @return bool Boolean value.
	 */
	public function sanitize_boolean( $value ) {
		return (bool) $value;
	}

	/**
	 * Sanitize integer value
	 *
	 * @param mixed $value Value to sanitize.
	 * @param int   $min   Minimum allowed value.
	 * @param int   $max   Maximum allowed value.
	 * @return int Sanitized integer.
	 */
	public function sanitize_integer( $value, $min = 0, $max = PHP_INT_MAX ) {
		$value = absint( $value );

		if ( $value < $min ) {
			$value = $min;
		}

		if ( $value > $max ) {
			$value = $max;
		}

		return $value;
	}

	/**
	 * Validate settings array
	 *
	 * @param array $input Raw input settings.
	 * @return array Validated settings.
	 */
	public function validate_settings( $input ) {
		$output = array();

		// General Settings.
		$output['enabled']              = $this->sanitize_boolean( isset( $input['enabled'] ) ? $input['enabled'] : false );
		$output['blocking_mode']        = in_array( $input['blocking_mode'], array( 'whitelist', 'blacklist' ), true ) ? $input['blocking_mode'] : 'blacklist';
		$output['block_action']         = in_array( $input['block_action'], array( 'message', 'redirect', 'page', '403' ), true ) ? $input['block_action'] : 'message';
		$output['block_message']        = $this->sanitize_html( $input['block_message'] );
		$output['redirect_url']         = $this->sanitize_url( $input['redirect_url'] );
		$output['block_page_id']        = $this->sanitize_integer( $input['block_page_id'] );
		$output['exempt_administrators'] = $this->sanitize_boolean( isset( $input['exempt_administrators'] ) ? $input['exempt_administrators'] : false );
		$output['exempt_logged_in']     = $this->sanitize_boolean( isset( $input['exempt_logged_in'] ) ? $input['exempt_logged_in'] : false );

		// Country Blocking.
		$output['blocked_countries'] = $this->validate_country_codes( isset( $input['blocked_countries'] ) ? $input['blocked_countries'] : array() );
		$output['allowed_countries'] = $this->validate_country_codes( isset( $input['allowed_countries'] ) ? $input['allowed_countries'] : array() );

		// Logging.
		$output['enable_logging']     = $this->sanitize_boolean( isset( $input['enable_logging'] ) ? $input['enable_logging'] : false );
		$output['max_logs']           = $this->sanitize_integer( isset( $input['max_logs'] ) ? $input['max_logs'] : 10000, 100, 100000 );
		$output['log_retention_days'] = $this->sanitize_integer( isset( $input['log_retention_days'] ) ? $input['log_retention_days'] : 90, 1, 365 );

		return $output;
	}

	/**
	 * Check if IP is from localhost/private network
	 *
	 * @param string $ip IP address.
	 * @return bool True if local/private IP.
	 */
	public function is_local_ip( $ip ) {
		// Check if it's a valid IP.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		// Check if it's a private IP.
		if ( ! filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		) ) {
			return true;
		}

		return false;
	}

	/**
	 * Escape data for JavaScript
	 *
	 * @param mixed $data Data to escape.
	 * @return string Escaped data.
	 */
	public function esc_js( $data ) {
		return esc_js( wp_json_encode( $data ) );
	}

	/**
	 * Generate secure random token
	 *
	 * @param int $length Token length.
	 * @return string Random token.
	 */
	public function generate_token( $length = 32 ) {
		return bin2hex( random_bytes( $length / 2 ) );
	}

	/**
	 * Hash data securely
	 *
	 * @param string $data Data to hash.
	 * @return string Hashed data.
	 */
	public function hash_data( $data ) {
		return wp_hash( $data );
	}

	/**
	 * Prevent directory traversal
	 *
	 * @param string $path Path to validate.
	 * @return string|false Validated path or false.
	 */
	public function validate_path( $path ) {
		// Remove any directory traversal attempts.
		$path = str_replace( array( '../', '..\\' ), '', $path );

		// Check if path is within allowed directory.
		$real_path = realpath( $path );

		if ( false === $real_path ) {
			return false;
		}

		// Ensure it's within the WordPress installation.
		if ( strpos( $real_path, ABSPATH ) !== 0 ) {
			return false;
		}

		return $real_path;
	}

	/**
	 * Log security event
	 *
	 * @param string $event_type Event type.
	 * @param array  $data       Event data.
	 */
	public function log_security_event( $event_type, $data = array() ) {
		if ( ! defined( 'GEO_IP_BLOCKER_SECURITY_LOG' ) || ! GEO_IP_BLOCKER_SECURITY_LOG ) {
			return;
		}

		$log_entry = array(
			'timestamp'  => current_time( 'mysql' ),
			'event_type' => $event_type,
			'user_id'    => get_current_user_id(),
			'ip_address' => $this->get_client_ip(),
			'data'       => $data,
		);

		error_log( 'GEO_IP_BLOCKER_SECURITY: ' . wp_json_encode( $log_entry ) );
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address.
	 */
	private function get_client_ip() {
		$geolocation = geo_ip_blocker_get_geolocation();
		return $geolocation ? $geolocation->get_visitor_ip() : '';
	}
}

/**
 * Get security instance
 *
 * @return Geo_IP_Blocker_Security
 */
function geo_ip_blocker_get_security() {
	return Geo_IP_Blocker_Security::instance();
}
