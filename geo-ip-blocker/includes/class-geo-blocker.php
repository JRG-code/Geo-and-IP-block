<?php
/**
 * Geo Blocker handler class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_Handler
 *
 * Handles the blocking logic based on geo-location and IP.
 */
class Geo_IP_Blocker_Handler {

	/**
	 * Database instance.
	 *
	 * @var Geo_IP_Blocker_Database
	 */
	private $database;

	/**
	 * Geolocation instance.
	 *
	 * @var Geo_Blocker_Geolocation
	 */
	private $geolocation;

	/**
	 * IP Manager instance.
	 *
	 * @var Geo_Blocker_IP_Manager
	 */
	private $ip_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->database    = new Geo_IP_Blocker_Database();
		$this->geolocation = new Geo_Blocker_Geolocation();
		$this->ip_manager  = new Geo_Blocker_IP_Manager();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'check_access' ), 1 );
	}

	/**
	 * Check if access should be blocked.
	 */
	public function check_access() {
		// Don't block admin or AJAX requests.
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Get client IP using geolocation class.
		$ip_address = $this->geolocation->get_visitor_ip();

		if ( empty( $ip_address ) ) {
			return;
		}

		// Check IP whitelist first (whitelist overrides everything).
		if ( $this->ip_manager->is_ip_allowed( $ip_address ) ) {
			return;
		}

		// Check IP blacklist.
		if ( $this->ip_manager->is_ip_blocked( $ip_address ) ) {
			$this->block_access( $ip_address, array(), 'IP blacklist' );
			return;
		}

		// Get geo data using geolocation class.
		$geo_data = $this->geolocation->get_location_data( $ip_address );

		// Check geo blocking rules.
		if ( $this->should_block( $ip_address, $geo_data ) ) {
			$this->block_access( $ip_address, $geo_data );
		}
	}

	/**
	 * Check if access should be blocked based on rules.
	 *
	 * @param string $ip_address IP address.
	 * @param array  $geo_data Geo data.
	 * @return bool
	 */
	public function should_block( $ip_address, $geo_data ) {
		$rules = $this->database->get_rules(
			array(
				'orderby' => 'priority',
				'order'   => 'ASC',
			)
		);

		if ( empty( $rules ) ) {
			return false;
		}

		$should_block = false;

		foreach ( $rules as $rule ) {
			$match = false;

			switch ( $rule->rule_type ) {
				case 'ip':
					$match = $this->match_ip( $ip_address, $rule->value );
					break;

				case 'country':
					if ( ! empty( $geo_data['country_code'] ) ) {
						$match = ( strtoupper( $geo_data['country_code'] ) === strtoupper( $rule->value ) );
					}
					break;

				case 'region':
					if ( ! empty( $geo_data['region'] ) ) {
						$match = ( strtolower( $geo_data['region'] ) === strtolower( $rule->value ) );
					}
					break;
			}

			if ( $match ) {
				// If action is 'allow', override previous blocks.
				if ( 'allow' === $rule->action ) {
					return false;
				}

				// If action is 'block', mark for blocking.
				if ( 'block' === $rule->action ) {
					$should_block = true;
				}
			}
		}

		return $should_block;
	}

	/**
	 * Match IP address against rule value.
	 *
	 * @param string $ip_address IP address.
	 * @param string $rule_value Rule value (single IP or CIDR range).
	 * @return bool
	 */
	private function match_ip( $ip_address, $rule_value ) {
		// Check for exact match.
		if ( $ip_address === $rule_value ) {
			return true;
		}

		// Check for CIDR notation.
		if ( strpos( $rule_value, '/' ) !== false ) {
			return $this->ip_in_range( $ip_address, $rule_value );
		}

		return false;
	}

	/**
	 * Check if IP is in CIDR range.
	 *
	 * @param string $ip IP address.
	 * @param string $range CIDR range (e.g., 192.168.1.0/24).
	 * @return bool
	 */
	private function ip_in_range( $ip, $range ) {
		list( $subnet, $mask ) = explode( '/', $range );

		$ip_decimal     = ip2long( $ip );
		$subnet_decimal = ip2long( $subnet );
		$mask_decimal   = ~( ( 1 << ( 32 - $mask ) ) - 1 );

		return ( $ip_decimal & $mask_decimal ) === ( $subnet_decimal & $mask_decimal );
	}

	/**
	 * Block access and log the attempt.
	 *
	 * @param string $ip_address   IP address.
	 * @param array  $geo_data     Geo data.
	 * @param string $block_reason Optional. Reason for blocking.
	 */
	private function block_access( $ip_address, $geo_data = array(), $block_reason = '' ) {
		if ( empty( $block_reason ) ) {
			$block_reason = 'Geo/IP blocking rule matched';
		}

		// Log the block attempt.
		$this->database->add_log(
			array(
				'ip_address'   => $ip_address,
				'country_code' => isset( $geo_data['country_code'] ) ? $geo_data['country_code'] : '',
				'region'       => isset( $geo_data['region'] ) ? $geo_data['region'] : '',
				'city'         => isset( $geo_data['city'] ) ? $geo_data['city'] : '',
				'blocked_url'  => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
				'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'block_reason' => $block_reason,
			)
		);

		// Send 403 Forbidden response.
		$this->send_blocked_response();
	}

	/**
	 * Send blocked response.
	 */
	private function send_blocked_response() {
		// Allow customization via filter.
		$message = apply_filters(
			'geo_ip_blocker_blocked_message',
			__( 'Access denied. Your location or IP address is not allowed to access this site.', 'geo-ip-blocker' )
		);

		// Allow custom template.
		$template = apply_filters( 'geo_ip_blocker_blocked_template', '' );

		if ( ! empty( $template ) && file_exists( $template ) ) {
			include $template;
			exit;
		}

		// Default response.
		wp_die(
			esc_html( $message ),
			esc_html__( 'Access Denied', 'geo-ip-blocker' ),
			array( 'response' => 403 )
		);
	}
}
