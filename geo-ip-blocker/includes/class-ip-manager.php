<?php
/**
 * IP Manager class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_Blocker_IP_Manager
 *
 * Handles IP address management, validation, and list operations.
 */
class Geo_Blocker_IP_Manager {

	/**
	 * Maximum number of IPs per list.
	 *
	 * @var int
	 */
	private $max_ips_per_list = 10000;

	/**
	 * Whitelist option key.
	 *
	 * @var string
	 */
	private $whitelist_option = 'geo_blocker_ip_whitelist';

	/**
	 * Blacklist option key.
	 *
	 * @var string
	 */
	private $blacklist_option = 'geo_blocker_ip_blacklist';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->max_ips_per_list = apply_filters( 'geo_ip_blocker_max_ips_per_list', $this->max_ips_per_list );
	}

	/**
	 * Validate IP address (IPv4 or IPv6).
	 *
	 * @param string $ip              IP address to validate.
	 * @param bool   $allow_private   Whether to allow private IPs.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_ip( $ip, $allow_private = true ) {
		// Basic validation.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		// Check if private IP is allowed.
		if ( ! $allow_private ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate IP range (CIDR or hyphen format).
	 *
	 * @param string $range Range to validate.
	 * @return bool|string False if invalid, normalized format if valid.
	 */
	public function validate_range( $range ) {
		$range = trim( $range );

		// Check if it's a CIDR notation.
		if ( strpos( $range, '/' ) !== false ) {
			return $this->validate_cidr( $range );
		}

		// Check if it's a hyphen range.
		if ( strpos( $range, '-' ) !== false ) {
			return $this->validate_hyphen_range( $range );
		}

		// Check if it's a single IP.
		if ( $this->validate_ip( $range ) ) {
			return $range;
		}

		return false;
	}

	/**
	 * Validate CIDR notation.
	 *
	 * @param string $cidr CIDR notation to validate.
	 * @return bool|string False if invalid, CIDR if valid.
	 */
	public function validate_cidr( $cidr ) {
		$parts = explode( '/', $cidr );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		list( $ip, $mask ) = $parts;

		// Validate IP.
		if ( ! $this->validate_ip( $ip ) ) {
			return false;
		}

		// Determine if IPv4 or IPv6.
		$is_ipv6 = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;

		// Validate mask.
		$mask = intval( $mask );

		if ( $is_ipv6 ) {
			// IPv6: 0-128.
			if ( $mask < 0 || $mask > 128 ) {
				return false;
			}
		} else {
			// IPv4: 0-32.
			if ( $mask < 0 || $mask > 32 ) {
				return false;
			}
		}

		return $cidr;
	}

	/**
	 * Validate hyphen range format (e.g., 192.168.1.1-192.168.1.50).
	 *
	 * @param string $range Range in hyphen format.
	 * @return bool|string False if invalid, normalized range if valid.
	 */
	public function validate_hyphen_range( $range ) {
		$parts = array_map( 'trim', explode( '-', $range ) );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		list( $start_ip, $end_ip ) = $parts;

		// Both must be valid IPs.
		if ( ! $this->validate_ip( $start_ip ) || ! $this->validate_ip( $end_ip ) ) {
			return false;
		}

		// Both must be same type (IPv4 or IPv6).
		$start_is_ipv6 = filter_var( $start_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;
		$end_is_ipv6   = filter_var( $end_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;

		if ( $start_is_ipv6 !== $end_is_ipv6 ) {
			return false;
		}

		// Convert to long/binary for comparison.
		if ( $start_is_ipv6 ) {
			$start_binary = inet_pton( $start_ip );
			$end_binary   = inet_pton( $end_ip );

			if ( $start_binary >= $end_binary ) {
				return false;
			}
		} else {
			$start_long = ip2long( $start_ip );
			$end_long   = ip2long( $end_ip );

			if ( $start_long >= $end_long ) {
				return false;
			}

			// Limit range size (max 65536 IPs in a range).
			if ( ( $end_long - $start_long ) > 65536 ) {
				return false;
			}
		}

		return $start_ip . '-' . $end_ip;
	}

	/**
	 * Check if IP is blocked.
	 *
	 * @param string $ip IP address to check.
	 * @return bool True if blocked, false otherwise.
	 */
	public function is_ip_blocked( $ip ) {
		// First check whitelist (whitelist overrides blacklist).
		if ( $this->is_ip_in_list( $ip, 'whitelist' ) ) {
			return false;
		}

		// Then check blacklist.
		return $this->is_ip_in_list( $ip, 'blacklist' );
	}

	/**
	 * Check if IP is allowed (in whitelist).
	 *
	 * @param string $ip IP address to check.
	 * @return bool True if allowed, false otherwise.
	 */
	public function is_ip_allowed( $ip ) {
		return $this->is_ip_in_list( $ip, 'whitelist' );
	}

	/**
	 * Check if IP is in a specific list.
	 *
	 * @param string $ip        IP address to check.
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return bool True if IP is in list, false otherwise.
	 */
	public function is_ip_in_list( $ip, $list_type ) {
		if ( ! $this->validate_ip( $ip ) ) {
			return false;
		}

		$list = $this->get_list( $list_type );

		if ( empty( $list ) ) {
			return false;
		}

		foreach ( $list as $entry ) {
			$entry = trim( $entry );

			// Check if it's a single IP.
			if ( $entry === $ip ) {
				return true;
			}

			// Check if it's a CIDR range.
			if ( strpos( $entry, '/' ) !== false ) {
				if ( $this->is_ip_in_cidr( $ip, $entry ) ) {
					return true;
				}
			}

			// Check if it's a hyphen range.
			if ( strpos( $entry, '-' ) !== false ) {
				if ( $this->is_ip_in_hyphen_range( $ip, $entry ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if IP is in CIDR range.
	 *
	 * @param string $ip   IP address.
	 * @param string $cidr CIDR notation.
	 * @return bool True if IP is in range, false otherwise.
	 */
	public function is_ip_in_cidr( $ip, $cidr ) {
		if ( ! $this->validate_ip( $ip ) || ! $this->validate_cidr( $cidr ) ) {
			return false;
		}

		list( $subnet, $mask ) = explode( '/', $cidr );

		// Check if same IP version.
		$ip_is_ipv6     = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;
		$subnet_is_ipv6 = filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;

		if ( $ip_is_ipv6 !== $subnet_is_ipv6 ) {
			return false;
		}

		if ( $ip_is_ipv6 ) {
			return $this->is_ipv6_in_cidr( $ip, $subnet, intval( $mask ) );
		} else {
			return $this->is_ipv4_in_cidr( $ip, $subnet, intval( $mask ) );
		}
	}

	/**
	 * Check if IPv4 is in CIDR range.
	 *
	 * @param string $ip     IPv4 address.
	 * @param string $subnet Subnet.
	 * @param int    $mask   Mask bits.
	 * @return bool
	 */
	private function is_ipv4_in_cidr( $ip, $subnet, $mask ) {
		$ip_long     = ip2long( $ip );
		$subnet_long = ip2long( $subnet );
		$mask_long   = -1 << ( 32 - $mask );

		return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
	}

	/**
	 * Check if IPv6 is in CIDR range.
	 *
	 * @param string $ip     IPv6 address.
	 * @param string $subnet Subnet.
	 * @param int    $mask   Mask bits.
	 * @return bool
	 */
	private function is_ipv6_in_cidr( $ip, $subnet, $mask ) {
		$ip_binary     = inet_pton( $ip );
		$subnet_binary = inet_pton( $subnet );

		if ( ! $ip_binary || ! $subnet_binary ) {
			return false;
		}

		// Create mask.
		$bytes = str_repeat( chr( 0 ), 16 );
		for ( $i = 0; $i < $mask; $i++ ) {
			$bytes[ (int) floor( $i / 8 ) ] = chr( ord( $bytes[ (int) floor( $i / 8 ) ] ) | ( 1 << ( 7 - ( $i % 8 ) ) ) );
		}

		return ( $ip_binary & $bytes ) === ( $subnet_binary & $bytes );
	}

	/**
	 * Check if IP is in hyphen range.
	 *
	 * @param string $ip    IP address.
	 * @param string $range Range in hyphen format.
	 * @return bool True if IP is in range, false otherwise.
	 */
	public function is_ip_in_hyphen_range( $ip, $range ) {
		if ( ! $this->validate_ip( $ip ) || ! $this->validate_hyphen_range( $range ) ) {
			return false;
		}

		list( $start_ip, $end_ip ) = array_map( 'trim', explode( '-', $range ) );

		// Check if same IP version.
		$ip_is_ipv6    = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;
		$start_is_ipv6 = filter_var( $start_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;

		if ( $ip_is_ipv6 !== $start_is_ipv6 ) {
			return false;
		}

		if ( $ip_is_ipv6 ) {
			$ip_binary    = inet_pton( $ip );
			$start_binary = inet_pton( $start_ip );
			$end_binary   = inet_pton( $end_ip );

			return $ip_binary >= $start_binary && $ip_binary <= $end_binary;
		} else {
			$ip_long    = ip2long( $ip );
			$start_long = ip2long( $start_ip );
			$end_long   = ip2long( $end_ip );

			return $ip_long >= $start_long && $ip_long <= $end_long;
		}
	}

	/**
	 * Add IP to list.
	 *
	 * @param string $ip        IP address or range.
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function add_ip_to_list( $ip, $list_type ) {
		// Validate list type.
		if ( ! in_array( $list_type, array( 'whitelist', 'blacklist' ), true ) ) {
			return new WP_Error( 'invalid_list_type', __( 'Invalid list type.', 'geo-ip-blocker' ) );
		}

		// Validate IP/range.
		$validated = $this->validate_range( $ip );
		if ( ! $validated ) {
			return new WP_Error( 'invalid_ip', __( 'Invalid IP address or range.', 'geo-ip-blocker' ) );
		}

		// Get current list.
		$list = $this->get_list( $list_type );

		// Check if already exists.
		if ( in_array( $validated, $list, true ) ) {
			return new WP_Error( 'ip_exists', __( 'IP already exists in list.', 'geo-ip-blocker' ) );
		}

		// Check max limit.
		if ( count( $list ) >= $this->max_ips_per_list ) {
			return new WP_Error(
				'max_limit_reached',
				sprintf(
					/* translators: %d: Maximum number of IPs allowed */
					__( 'Maximum limit of %d IPs reached.', 'geo-ip-blocker' ),
					$this->max_ips_per_list
				)
			);
		}

		// Add to list.
		$list[] = $validated;

		// Save list.
		return $this->save_list( $list, $list_type );
	}

	/**
	 * Remove IP from list.
	 *
	 * @param string $ip        IP address or range.
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function remove_ip_from_list( $ip, $list_type ) {
		// Validate list type.
		if ( ! in_array( $list_type, array( 'whitelist', 'blacklist' ), true ) ) {
			return new WP_Error( 'invalid_list_type', __( 'Invalid list type.', 'geo-ip-blocker' ) );
		}

		// Get current list.
		$list = $this->get_list( $list_type );

		// Find and remove IP.
		$key = array_search( $ip, $list, true );
		if ( $key === false ) {
			return new WP_Error( 'ip_not_found', __( 'IP not found in list.', 'geo-ip-blocker' ) );
		}

		unset( $list[ $key ] );

		// Re-index array.
		$list = array_values( $list );

		// Save list.
		return $this->save_list( $list, $list_type );
	}

	/**
	 * Get IP list.
	 *
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return array List of IPs.
	 */
	public function get_list( $list_type ) {
		$option_key = $list_type === 'whitelist' ? $this->whitelist_option : $this->blacklist_option;
		$list       = get_option( $option_key, array() );

		// Ensure it's an array.
		if ( ! is_array( $list ) ) {
			$list = array();
		}

		return $list;
	}

	/**
	 * Save IP list.
	 *
	 * @param array  $list      List of IPs.
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return bool True on success, false on failure.
	 */
	private function save_list( $list, $list_type ) {
		$option_key = $list_type === 'whitelist' ? $this->whitelist_option : $this->blacklist_option;
		return update_option( $option_key, $list );
	}

	/**
	 * Clear entire list.
	 *
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return bool True on success, false on failure.
	 */
	public function clear_list( $list_type ) {
		if ( ! in_array( $list_type, array( 'whitelist', 'blacklist' ), true ) ) {
			return false;
		}

		return $this->save_list( array(), $list_type );
	}

	/**
	 * Get list count.
	 *
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return int Number of entries in list.
	 */
	public function get_list_count( $list_type ) {
		return count( $this->get_list( $list_type ) );
	}

	/**
	 * Import IPs from array.
	 *
	 * @param array  $ips       Array of IPs to import.
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @param bool   $replace   Whether to replace existing list.
	 * @return array Array with 'success' count and 'errors' array.
	 */
	public function import_ips( $ips, $list_type, $replace = false ) {
		if ( ! is_array( $ips ) ) {
			return array(
				'success' => 0,
				'errors'  => array( __( 'Invalid input format.', 'geo-ip-blocker' ) ),
			);
		}

		$success = 0;
		$errors  = array();

		// Replace list if requested.
		if ( $replace ) {
			$this->clear_list( $list_type );
		}

		foreach ( $ips as $ip ) {
			$result = $this->add_ip_to_list( $ip, $list_type );

			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf(
					/* translators: 1: IP address, 2: Error message */
					__( '%1$s: %2$s', 'geo-ip-blocker' ),
					$ip,
					$result->get_error_message()
				);
			} else {
				$success++;
			}
		}

		return array(
			'success' => $success,
			'errors'  => $errors,
		);
	}

	/**
	 * Export IPs to array.
	 *
	 * @param string $list_type List type ('whitelist' or 'blacklist').
	 * @return array List of IPs.
	 */
	public function export_ips( $list_type ) {
		return $this->get_list( $list_type );
	}

	/**
	 * Parse CIDR to get IP range.
	 *
	 * @param string $cidr CIDR notation.
	 * @return array|false Array with 'start' and 'end' IPs, or false on failure.
	 */
	public function parse_cidr( $cidr ) {
		if ( ! $this->validate_cidr( $cidr ) ) {
			return false;
		}

		list( $ip, $mask ) = explode( '/', $cidr );
		$mask = intval( $mask );

		$is_ipv6 = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;

		if ( $is_ipv6 ) {
			// IPv6 parsing is complex, return simplified info.
			return array(
				'network' => $ip,
				'mask'    => $mask,
				'type'    => 'ipv6',
			);
		} else {
			// IPv4 parsing.
			$ip_long   = ip2long( $ip );
			$mask_long = -1 << ( 32 - $mask );

			$network = $ip_long & $mask_long;
			$broadcast = $network | ( ~$mask_long & 0xFFFFFFFF );

			return array(
				'start'     => long2ip( $network ),
				'end'       => long2ip( $broadcast ),
				'network'   => long2ip( $network ),
				'broadcast' => long2ip( $broadcast ),
				'mask'      => $mask,
				'type'      => 'ipv4',
			);
		}
	}

	/**
	 * Parse hyphen range.
	 *
	 * @param string $range Range in hyphen format.
	 * @return array|false Array with 'start' and 'end' IPs, or false on failure.
	 */
	public function parse_range( $range ) {
		if ( ! $this->validate_hyphen_range( $range ) ) {
			return false;
		}

		list( $start_ip, $end_ip ) = array_map( 'trim', explode( '-', $range ) );

		return array(
			'start' => $start_ip,
			'end'   => $end_ip,
		);
	}

	/**
	 * Check if IP is private/reserved.
	 *
	 * @param string $ip IP address.
	 * @return bool True if private/reserved, false otherwise.
	 */
	public function is_private_ip( $ip ) {
		if ( ! $this->validate_ip( $ip, true ) ) {
			return false;
		}

		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false;
	}

	/**
	 * Get statistics about lists.
	 *
	 * @return array Statistics array.
	 */
	public function get_statistics() {
		return array(
			'whitelist_count' => $this->get_list_count( 'whitelist' ),
			'blacklist_count' => $this->get_list_count( 'blacklist' ),
			'max_per_list'    => $this->max_ips_per_list,
		);
	}
}
