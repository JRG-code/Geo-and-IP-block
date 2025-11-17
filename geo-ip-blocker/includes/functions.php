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
 * Validate IP address.
 *
 * @param string $ip IP address.
 * @return bool
 */
function geo_ip_blocker_validate_ip( $ip ) {
	return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
}

/**
 * Validate CIDR notation.
 *
 * @param string $cidr CIDR notation.
 * @return bool
 */
function geo_ip_blocker_validate_cidr( $cidr ) {
	if ( strpos( $cidr, '/' ) === false ) {
		return false;
	}

	list( $ip, $mask ) = explode( '/', $cidr );

	if ( ! geo_ip_blocker_validate_ip( $ip ) ) {
		return false;
	}

	$mask = intval( $mask );
	return $mask >= 0 && $mask <= 32;
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
