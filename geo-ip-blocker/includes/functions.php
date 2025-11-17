<?php
/**
 * Helper functions.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get database instance.
 *
 * @return Geo_IP_Blocker_Database
 */
function geo_ip_blocker_get_database() {
	return new Geo_IP_Blocker_Database();
}

/**
 * Get all countries for selection.
 *
 * @return array
 */
function geo_ip_blocker_get_countries() {
	return array(
		'AF' => __( 'Afghanistan', 'geo-ip-blocker' ),
		'AL' => __( 'Albania', 'geo-ip-blocker' ),
		'DZ' => __( 'Algeria', 'geo-ip-blocker' ),
		'AD' => __( 'Andorra', 'geo-ip-blocker' ),
		'AO' => __( 'Angola', 'geo-ip-blocker' ),
		'AG' => __( 'Antigua and Barbuda', 'geo-ip-blocker' ),
		'AR' => __( 'Argentina', 'geo-ip-blocker' ),
		'AM' => __( 'Armenia', 'geo-ip-blocker' ),
		'AU' => __( 'Australia', 'geo-ip-blocker' ),
		'AT' => __( 'Austria', 'geo-ip-blocker' ),
		'AZ' => __( 'Azerbaijan', 'geo-ip-blocker' ),
		'BS' => __( 'Bahamas', 'geo-ip-blocker' ),
		'BH' => __( 'Bahrain', 'geo-ip-blocker' ),
		'BD' => __( 'Bangladesh', 'geo-ip-blocker' ),
		'BB' => __( 'Barbados', 'geo-ip-blocker' ),
		'BY' => __( 'Belarus', 'geo-ip-blocker' ),
		'BE' => __( 'Belgium', 'geo-ip-blocker' ),
		'BZ' => __( 'Belize', 'geo-ip-blocker' ),
		'BJ' => __( 'Benin', 'geo-ip-blocker' ),
		'BT' => __( 'Bhutan', 'geo-ip-blocker' ),
		'BO' => __( 'Bolivia', 'geo-ip-blocker' ),
		'BA' => __( 'Bosnia and Herzegovina', 'geo-ip-blocker' ),
		'BW' => __( 'Botswana', 'geo-ip-blocker' ),
		'BR' => __( 'Brazil', 'geo-ip-blocker' ),
		'BN' => __( 'Brunei', 'geo-ip-blocker' ),
		'BG' => __( 'Bulgaria', 'geo-ip-blocker' ),
		'BF' => __( 'Burkina Faso', 'geo-ip-blocker' ),
		'BI' => __( 'Burundi', 'geo-ip-blocker' ),
		'KH' => __( 'Cambodia', 'geo-ip-blocker' ),
		'CM' => __( 'Cameroon', 'geo-ip-blocker' ),
		'CA' => __( 'Canada', 'geo-ip-blocker' ),
		'CV' => __( 'Cape Verde', 'geo-ip-blocker' ),
		'CF' => __( 'Central African Republic', 'geo-ip-blocker' ),
		'TD' => __( 'Chad', 'geo-ip-blocker' ),
		'CL' => __( 'Chile', 'geo-ip-blocker' ),
		'CN' => __( 'China', 'geo-ip-blocker' ),
		'CO' => __( 'Colombia', 'geo-ip-blocker' ),
		'KM' => __( 'Comoros', 'geo-ip-blocker' ),
		'CG' => __( 'Congo', 'geo-ip-blocker' ),
		'CR' => __( 'Costa Rica', 'geo-ip-blocker' ),
		'HR' => __( 'Croatia', 'geo-ip-blocker' ),
		'CU' => __( 'Cuba', 'geo-ip-blocker' ),
		'CY' => __( 'Cyprus', 'geo-ip-blocker' ),
		'CZ' => __( 'Czech Republic', 'geo-ip-blocker' ),
		'DK' => __( 'Denmark', 'geo-ip-blocker' ),
		'DJ' => __( 'Djibouti', 'geo-ip-blocker' ),
		'DM' => __( 'Dominica', 'geo-ip-blocker' ),
		'DO' => __( 'Dominican Republic', 'geo-ip-blocker' ),
		'EC' => __( 'Ecuador', 'geo-ip-blocker' ),
		'EG' => __( 'Egypt', 'geo-ip-blocker' ),
		'SV' => __( 'El Salvador', 'geo-ip-blocker' ),
		'GQ' => __( 'Equatorial Guinea', 'geo-ip-blocker' ),
		'ER' => __( 'Eritrea', 'geo-ip-blocker' ),
		'EE' => __( 'Estonia', 'geo-ip-blocker' ),
		'ET' => __( 'Ethiopia', 'geo-ip-blocker' ),
		'FJ' => __( 'Fiji', 'geo-ip-blocker' ),
		'FI' => __( 'Finland', 'geo-ip-blocker' ),
		'FR' => __( 'France', 'geo-ip-blocker' ),
		'GA' => __( 'Gabon', 'geo-ip-blocker' ),
		'GM' => __( 'Gambia', 'geo-ip-blocker' ),
		'GE' => __( 'Georgia', 'geo-ip-blocker' ),
		'DE' => __( 'Germany', 'geo-ip-blocker' ),
		'GH' => __( 'Ghana', 'geo-ip-blocker' ),
		'GR' => __( 'Greece', 'geo-ip-blocker' ),
		'GD' => __( 'Grenada', 'geo-ip-blocker' ),
		'GT' => __( 'Guatemala', 'geo-ip-blocker' ),
		'GN' => __( 'Guinea', 'geo-ip-blocker' ),
		'GW' => __( 'Guinea-Bissau', 'geo-ip-blocker' ),
		'GY' => __( 'Guyana', 'geo-ip-blocker' ),
		'HT' => __( 'Haiti', 'geo-ip-blocker' ),
		'HN' => __( 'Honduras', 'geo-ip-blocker' ),
		'HU' => __( 'Hungary', 'geo-ip-blocker' ),
		'IS' => __( 'Iceland', 'geo-ip-blocker' ),
		'IN' => __( 'India', 'geo-ip-blocker' ),
		'ID' => __( 'Indonesia', 'geo-ip-blocker' ),
		'IR' => __( 'Iran', 'geo-ip-blocker' ),
		'IQ' => __( 'Iraq', 'geo-ip-blocker' ),
		'IE' => __( 'Ireland', 'geo-ip-blocker' ),
		'IL' => __( 'Israel', 'geo-ip-blocker' ),
		'IT' => __( 'Italy', 'geo-ip-blocker' ),
		'JM' => __( 'Jamaica', 'geo-ip-blocker' ),
		'JP' => __( 'Japan', 'geo-ip-blocker' ),
		'JO' => __( 'Jordan', 'geo-ip-blocker' ),
		'KZ' => __( 'Kazakhstan', 'geo-ip-blocker' ),
		'KE' => __( 'Kenya', 'geo-ip-blocker' ),
		'KI' => __( 'Kiribati', 'geo-ip-blocker' ),
		'KP' => __( 'North Korea', 'geo-ip-blocker' ),
		'KR' => __( 'South Korea', 'geo-ip-blocker' ),
		'KW' => __( 'Kuwait', 'geo-ip-blocker' ),
		'KG' => __( 'Kyrgyzstan', 'geo-ip-blocker' ),
		'LA' => __( 'Laos', 'geo-ip-blocker' ),
		'LV' => __( 'Latvia', 'geo-ip-blocker' ),
		'LB' => __( 'Lebanon', 'geo-ip-blocker' ),
		'LS' => __( 'Lesotho', 'geo-ip-blocker' ),
		'LR' => __( 'Liberia', 'geo-ip-blocker' ),
		'LY' => __( 'Libya', 'geo-ip-blocker' ),
		'LI' => __( 'Liechtenstein', 'geo-ip-blocker' ),
		'LT' => __( 'Lithuania', 'geo-ip-blocker' ),
		'LU' => __( 'Luxembourg', 'geo-ip-blocker' ),
		'MG' => __( 'Madagascar', 'geo-ip-blocker' ),
		'MW' => __( 'Malawi', 'geo-ip-blocker' ),
		'MY' => __( 'Malaysia', 'geo-ip-blocker' ),
		'MV' => __( 'Maldives', 'geo-ip-blocker' ),
		'ML' => __( 'Mali', 'geo-ip-blocker' ),
		'MT' => __( 'Malta', 'geo-ip-blocker' ),
		'MH' => __( 'Marshall Islands', 'geo-ip-blocker' ),
		'MR' => __( 'Mauritania', 'geo-ip-blocker' ),
		'MU' => __( 'Mauritius', 'geo-ip-blocker' ),
		'MX' => __( 'Mexico', 'geo-ip-blocker' ),
		'FM' => __( 'Micronesia', 'geo-ip-blocker' ),
		'MD' => __( 'Moldova', 'geo-ip-blocker' ),
		'MC' => __( 'Monaco', 'geo-ip-blocker' ),
		'MN' => __( 'Mongolia', 'geo-ip-blocker' ),
		'ME' => __( 'Montenegro', 'geo-ip-blocker' ),
		'MA' => __( 'Morocco', 'geo-ip-blocker' ),
		'MZ' => __( 'Mozambique', 'geo-ip-blocker' ),
		'MM' => __( 'Myanmar', 'geo-ip-blocker' ),
		'NA' => __( 'Namibia', 'geo-ip-blocker' ),
		'NR' => __( 'Nauru', 'geo-ip-blocker' ),
		'NP' => __( 'Nepal', 'geo-ip-blocker' ),
		'NL' => __( 'Netherlands', 'geo-ip-blocker' ),
		'NZ' => __( 'New Zealand', 'geo-ip-blocker' ),
		'NI' => __( 'Nicaragua', 'geo-ip-blocker' ),
		'NE' => __( 'Niger', 'geo-ip-blocker' ),
		'NG' => __( 'Nigeria', 'geo-ip-blocker' ),
		'NO' => __( 'Norway', 'geo-ip-blocker' ),
		'OM' => __( 'Oman', 'geo-ip-blocker' ),
		'PK' => __( 'Pakistan', 'geo-ip-blocker' ),
		'PW' => __( 'Palau', 'geo-ip-blocker' ),
		'PA' => __( 'Panama', 'geo-ip-blocker' ),
		'PG' => __( 'Papua New Guinea', 'geo-ip-blocker' ),
		'PY' => __( 'Paraguay', 'geo-ip-blocker' ),
		'PE' => __( 'Peru', 'geo-ip-blocker' ),
		'PH' => __( 'Philippines', 'geo-ip-blocker' ),
		'PL' => __( 'Poland', 'geo-ip-blocker' ),
		'PT' => __( 'Portugal', 'geo-ip-blocker' ),
		'QA' => __( 'Qatar', 'geo-ip-blocker' ),
		'RO' => __( 'Romania', 'geo-ip-blocker' ),
		'RU' => __( 'Russia', 'geo-ip-blocker' ),
		'RW' => __( 'Rwanda', 'geo-ip-blocker' ),
		'KN' => __( 'Saint Kitts and Nevis', 'geo-ip-blocker' ),
		'LC' => __( 'Saint Lucia', 'geo-ip-blocker' ),
		'VC' => __( 'Saint Vincent and the Grenadines', 'geo-ip-blocker' ),
		'WS' => __( 'Samoa', 'geo-ip-blocker' ),
		'SM' => __( 'San Marino', 'geo-ip-blocker' ),
		'ST' => __( 'Sao Tome and Principe', 'geo-ip-blocker' ),
		'SA' => __( 'Saudi Arabia', 'geo-ip-blocker' ),
		'SN' => __( 'Senegal', 'geo-ip-blocker' ),
		'RS' => __( 'Serbia', 'geo-ip-blocker' ),
		'SC' => __( 'Seychelles', 'geo-ip-blocker' ),
		'SL' => __( 'Sierra Leone', 'geo-ip-blocker' ),
		'SG' => __( 'Singapore', 'geo-ip-blocker' ),
		'SK' => __( 'Slovakia', 'geo-ip-blocker' ),
		'SI' => __( 'Slovenia', 'geo-ip-blocker' ),
		'SB' => __( 'Solomon Islands', 'geo-ip-blocker' ),
		'SO' => __( 'Somalia', 'geo-ip-blocker' ),
		'ZA' => __( 'South Africa', 'geo-ip-blocker' ),
		'SS' => __( 'South Sudan', 'geo-ip-blocker' ),
		'ES' => __( 'Spain', 'geo-ip-blocker' ),
		'LK' => __( 'Sri Lanka', 'geo-ip-blocker' ),
		'SD' => __( 'Sudan', 'geo-ip-blocker' ),
		'SR' => __( 'Suriname', 'geo-ip-blocker' ),
		'SZ' => __( 'Swaziland', 'geo-ip-blocker' ),
		'SE' => __( 'Sweden', 'geo-ip-blocker' ),
		'CH' => __( 'Switzerland', 'geo-ip-blocker' ),
		'SY' => __( 'Syria', 'geo-ip-blocker' ),
		'TW' => __( 'Taiwan', 'geo-ip-blocker' ),
		'TJ' => __( 'Tajikistan', 'geo-ip-blocker' ),
		'TZ' => __( 'Tanzania', 'geo-ip-blocker' ),
		'TH' => __( 'Thailand', 'geo-ip-blocker' ),
		'TL' => __( 'Timor-Leste', 'geo-ip-blocker' ),
		'TG' => __( 'Togo', 'geo-ip-blocker' ),
		'TO' => __( 'Tonga', 'geo-ip-blocker' ),
		'TT' => __( 'Trinidad and Tobago', 'geo-ip-blocker' ),
		'TN' => __( 'Tunisia', 'geo-ip-blocker' ),
		'TR' => __( 'Turkey', 'geo-ip-blocker' ),
		'TM' => __( 'Turkmenistan', 'geo-ip-blocker' ),
		'TV' => __( 'Tuvalu', 'geo-ip-blocker' ),
		'UG' => __( 'Uganda', 'geo-ip-blocker' ),
		'UA' => __( 'Ukraine', 'geo-ip-blocker' ),
		'AE' => __( 'United Arab Emirates', 'geo-ip-blocker' ),
		'GB' => __( 'United Kingdom', 'geo-ip-blocker' ),
		'US' => __( 'United States', 'geo-ip-blocker' ),
		'UY' => __( 'Uruguay', 'geo-ip-blocker' ),
		'UZ' => __( 'Uzbekistan', 'geo-ip-blocker' ),
		'VU' => __( 'Vanuatu', 'geo-ip-blocker' ),
		'VA' => __( 'Vatican City', 'geo-ip-blocker' ),
		'VE' => __( 'Venezuela', 'geo-ip-blocker' ),
		'VN' => __( 'Vietnam', 'geo-ip-blocker' ),
		'YE' => __( 'Yemen', 'geo-ip-blocker' ),
		'ZM' => __( 'Zambia', 'geo-ip-blocker' ),
		'ZW' => __( 'Zimbabwe', 'geo-ip-blocker' ),
	);
}

/**
 * Format date for display.
 *
 * @param string $date Date string.
 * @return string
 */
function geo_ip_blocker_format_date( $date ) {
	return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) );
}

/**
 * Get plugin settings.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function geo_ip_blocker_get_setting( $key, $default = '' ) {
	$settings = get_option( 'geo_ip_blocker_settings', array() );
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Update plugin setting.
 *
 * @param string $key   Setting key.
 * @param mixed  $value Setting value.
 * @return bool
 */
function geo_ip_blocker_update_setting( $key, $value ) {
	$settings         = get_option( 'geo_ip_blocker_settings', array() );
	$settings[ $key ] = $value;
	return update_option( 'geo_ip_blocker_settings', $settings );
}

/**
 * Get geolocation instance.
 *
 * @return Geo_Blocker_Geolocation
 */
function geo_ip_blocker_get_geolocation() {
	return new Geo_Blocker_Geolocation();
}

/**
 * Get visitor's current IP address.
 *
 * @return string|false IP address or false.
 */
function geo_ip_blocker_get_current_ip() {
	$geolocation = geo_ip_blocker_get_geolocation();
	return $geolocation->get_visitor_ip();
}

/**
 * Get location data for current visitor.
 *
 * @return array|false Location data or false.
 */
function geo_ip_blocker_get_current_location() {
	$ip = geo_ip_blocker_get_current_ip();
	if ( ! $ip ) {
		return false;
	}

	$geolocation = geo_ip_blocker_get_geolocation();
	return $geolocation->get_location_data( $ip );
}

/**
 * Get country code for current visitor.
 *
 * @return string Country code or 'UNKNOWN'.
 */
function geo_ip_blocker_get_current_country() {
	$ip = geo_ip_blocker_get_current_ip();
	if ( ! $ip ) {
		return 'UNKNOWN';
	}

	$geolocation = geo_ip_blocker_get_geolocation();
	return $geolocation->get_country_code( $ip );
}

/**
 * Get location data for a specific IP address.
 *
 * @param string $ip IP address.
 * @return array|false Location data or false.
 */
function geo_ip_blocker_get_ip_location( $ip ) {
	$geolocation = geo_ip_blocker_get_geolocation();
	return $geolocation->get_location_data( $ip );
}

/**
 * Clear geolocation cache.
 *
 * @param string $ip Optional. Specific IP to clear.
 */
function geo_ip_blocker_clear_geo_cache( $ip = '' ) {
	$geolocation = geo_ip_blocker_get_geolocation();
	$geolocation->clear_cache( $ip );
}

/**
 * Get available geolocation providers.
 *
 * @return array
 */
function geo_ip_blocker_get_providers() {
	return array(
		'maxmind'     => __( 'MaxMind GeoIP2', 'geo-ip-blocker' ),
		'ip2location' => __( 'IP2Location', 'geo-ip-blocker' ),
		'ipapi'       => __( 'IP-API.com (Free)', 'geo-ip-blocker' ),
	);
}

/**
 * Check if local database is available.
 *
 * @return bool
 */
function geo_ip_blocker_has_local_database() {
	$upload_dir = wp_upload_dir();
	$db_path    = $upload_dir['basedir'] . '/geo-ip-blocker/GeoLite2-City.mmdb';
	return file_exists( $db_path );
}

/**
 * Get local database last update time.
 *
 * @return int|false Timestamp or false.
 */
function geo_ip_blocker_get_db_last_update() {
	return get_option( 'geo_ip_blocker_db_last_update', false );
}

/**
 * Get IP Manager instance.
 *
 * @return Geo_Blocker_IP_Manager
 */
function geo_ip_blocker_get_ip_manager() {
	return new Geo_Blocker_IP_Manager();
}

/**
 * Validate IP address.
 *
 * @param string $ip            IP address.
 * @param bool   $allow_private Whether to allow private IPs.
 * @return bool True if valid, false otherwise.
 */
function geo_ip_blocker_validate_ip( $ip, $allow_private = true ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->validate_ip( $ip, $allow_private );
}

/**
 * Validate IP range (CIDR or hyphen format).
 *
 * @param string $range Range to validate.
 * @return bool|string False if invalid, normalized format if valid.
 */
function geo_ip_blocker_validate_range( $range ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->validate_range( $range );
}

/**
 * Check if IP is blocked.
 *
 * @param string $ip IP address.
 * @return bool True if blocked, false otherwise.
 */
function geo_ip_blocker_is_ip_blocked( $ip ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->is_ip_blocked( $ip );
}

/**
 * Check if IP is in whitelist.
 *
 * @param string $ip IP address.
 * @return bool True if whitelisted, false otherwise.
 */
function geo_ip_blocker_is_ip_whitelisted( $ip ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->is_ip_allowed( $ip );
}

/**
 * Add IP to whitelist or blacklist.
 *
 * @param string $ip        IP address or range.
 * @param string $list_type List type ('whitelist' or 'blacklist').
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function geo_ip_blocker_add_ip( $ip, $list_type = 'blacklist' ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->add_ip_to_list( $ip, $list_type );
}

/**
 * Remove IP from whitelist or blacklist.
 *
 * @param string $ip        IP address or range.
 * @param string $list_type List type ('whitelist' or 'blacklist').
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function geo_ip_blocker_remove_ip( $ip, $list_type = 'blacklist' ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->remove_ip_from_list( $ip, $list_type );
}

/**
 * Get IP list.
 *
 * @param string $list_type List type ('whitelist' or 'blacklist').
 * @return array List of IPs.
 */
function geo_ip_blocker_get_ip_list( $list_type = 'blacklist' ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->get_list( $list_type );
}

/**
 * Clear IP list.
 *
 * @param string $list_type List type ('whitelist' or 'blacklist').
 * @return bool True on success, false on failure.
 */
function geo_ip_blocker_clear_ip_list( $list_type ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->clear_list( $list_type );
}

/**
 * Get IP list count.
 *
 * @param string $list_type List type ('whitelist' or 'blacklist').
 * @return int Number of entries in list.
 */
function geo_ip_blocker_get_ip_count( $list_type = 'blacklist' ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->get_list_count( $list_type );
}

/**
 * Check if IP is in CIDR range.
 *
 * @param string $ip   IP address.
 * @param string $cidr CIDR notation.
 * @return bool True if in range, false otherwise.
 */
function geo_ip_blocker_ip_in_cidr( $ip, $cidr ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->is_ip_in_cidr( $ip, $cidr );
}

/**
 * Check if IP is in hyphen range.
 *
 * @param string $ip    IP address.
 * @param string $range Range in hyphen format.
 * @return bool True if in range, false otherwise.
 */
function geo_ip_blocker_ip_in_range( $ip, $range ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->is_ip_in_hyphen_range( $ip, $range );
}

/**
 * Parse CIDR notation.
 *
 * @param string $cidr CIDR notation.
 * @return array|false Range info or false.
 */
function geo_ip_blocker_parse_cidr( $cidr ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->parse_cidr( $cidr );
}

/**
 * Check if IP is private/reserved.
 *
 * @param string $ip IP address.
 * @return bool True if private, false otherwise.
 */
function geo_ip_blocker_is_private_ip( $ip ) {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->is_private_ip( $ip );
}

/**
 * Get IP management statistics.
 *
 * @return array Statistics array.
 */
function geo_ip_blocker_get_ip_stats() {
	$manager = geo_ip_blocker_get_ip_manager();
	return $manager->get_statistics();
}

/**
 * Get Blocker instance.
 *
 * @return Geo_Blocker_Blocker
 */
function geo_ip_blocker_get_blocker() {
	static $blocker = null;

	if ( null === $blocker ) {
		$blocker = new Geo_Blocker_Blocker();
	}

	return $blocker;
}

/**
 * Get available WordPress roles.
 *
 * @return array
 */
function geo_ip_blocker_get_roles() {
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	return $wp_roles->get_names();
}

/**
 * Get blocking statistics.
 *
 * @return array
 */
function geo_ip_blocker_get_blocking_stats() {
	$blocker = geo_ip_blocker_get_blocker();
	return $blocker->get_statistics();
}

/**
 * Get available block actions.
 *
 * @return array
 */
function geo_ip_blocker_get_block_actions() {
	return array(
		'message'  => __( 'Show Message', 'geo-ip-blocker' ),
		'redirect' => __( 'Redirect to URL', 'geo-ip-blocker' ),
		'page'     => __( 'Show WordPress Page', 'geo-ip-blocker' ),
		'403'      => __( 'Show 403 Error', 'geo-ip-blocker' ),
	);
}

/**
 * Get available blocking modes.
 *
 * @return array
 */
function geo_ip_blocker_get_blocking_modes() {
	return array(
		'blacklist' => __( 'Blacklist Mode (Block selected countries)', 'geo-ip-blocker' ),
		'whitelist' => __( 'Whitelist Mode (Allow only selected countries)', 'geo-ip-blocker' ),
	);
}

/**
 * Get WooCommerce blocking modes.
 *
 * @return array
 */
function geo_ip_blocker_get_woo_blocking_modes() {
	return array(
		'all'           => __( 'Block entire site', 'geo-ip-blocker' ),
		'woo_only'      => __( 'Block only WooCommerce pages', 'geo-ip-blocker' ),
		'checkout_only' => __( 'Block only checkout/cart', 'geo-ip-blocker' ),
		'none'          => __( 'Do not block WooCommerce', 'geo-ip-blocker' ),
	);
}

/**
 * Get Logger instance.
 *
 * @return Geo_Blocker_Logger
 */
function geo_ip_blocker_get_logger() {
	static $logger = null;

	if ( null === $logger ) {
		$logger = new Geo_Blocker_Logger();
	}

	return $logger;
}

/**
 * Log a blocked access attempt.
 *
 * @param array $data Log data.
 * @return int|false Log ID or false.
 */
function geo_ip_blocker_log_access( $data ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->log_blocked_access( $data );
}

/**
 * Get logs with filters and pagination.
 *
 * @param array $filters Filters.
 * @param int   $page    Page number.
 * @param int   $per_page Items per page.
 * @return array
 */
function geo_ip_blocker_get_logs( $filters = array(), $page = 1, $per_page = 20 ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->get_logs( $filters, $page, $per_page );
}

/**
 * Export logs.
 *
 * @param string $format  Format ('csv' or 'json').
 * @param array  $filters Filters.
 * @return string|false File path or false.
 */
function geo_ip_blocker_export_logs( $format = 'csv', $filters = array() ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->export_logs( $format, $filters );
}

/**
 * Delete logs older than specified days.
 *
 * @param int $days Number of days.
 * @return int|false Number of deleted rows or false.
 */
function geo_ip_blocker_delete_old_logs( $days = null ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->delete_logs( $days );
}

/**
 * Clear all logs.
 *
 * @return int|false Number of deleted rows or false.
 */
function geo_ip_blocker_clear_logs() {
	$logger = geo_ip_blocker_get_logger();
	return $logger->clear_all_logs();
}

/**
 * Get log statistics.
 *
 * @param string $period Period ('today', 'week', 'month', 'year', 'all').
 * @return array
 */
function geo_ip_blocker_get_log_stats( $period = 'all' ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->get_statistics( $period );
}

/**
 * Get recent logs.
 *
 * @param int $limit Limit.
 * @return array
 */
function geo_ip_blocker_get_recent_logs( $limit = 100 ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->get_recent_logs( $limit );
}

/**
 * Get logs by IP.
 *
 * @param string $ip_address IP address.
 * @param int    $limit      Limit.
 * @return array
 */
function geo_ip_blocker_get_ip_logs( $ip_address, $limit = 100 ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->get_logs_by_ip( $ip_address, $limit );
}

/**
 * Get logs by country.
 *
 * @param string $country_code Country code.
 * @param int    $limit        Limit.
 * @return array
 */
function geo_ip_blocker_get_country_logs( $country_code, $limit = 100 ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->get_logs_by_country( $country_code, $limit );
}

/**
 * Get chart data for blocks.
 *
 * @param int    $days   Number of days.
 * @param string $period Period.
 * @return array
 */
function geo_ip_blocker_get_chart_data( $days = 30, $period = 'month' ) {
	$logger = geo_ip_blocker_get_logger();
	return $logger->get_chart_data( $days, $period );
}

/**
 * Get summary statistics.
 *
 * @return array
 */
function geo_ip_blocker_get_summary_stats() {
	$logger = geo_ip_blocker_get_logger();
	return $logger->get_summary();
}
