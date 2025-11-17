<?php
/**
 * Country Regions and Groups
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get country regions/groups.
 *
 * @return array Array of regions with their country codes.
 */
function geo_ip_blocker_get_country_regions() {
	return array(
		'EU-27'        => array(
			'name'      => __( 'European Union (27 members)', 'geo-ip-blocker' ),
			'countries' => array(
				'AT', // Austria
				'BE', // Belgium
				'BG', // Bulgaria
				'HR', // Croatia
				'CY', // Cyprus
				'CZ', // Czech Republic
				'DK', // Denmark
				'EE', // Estonia
				'FI', // Finland
				'FR', // France
				'DE', // Germany
				'GR', // Greece
				'HU', // Hungary
				'IE', // Ireland
				'IT', // Italy
				'LV', // Latvia
				'LT', // Lithuania
				'LU', // Luxembourg
				'MT', // Malta
				'NL', // Netherlands
				'PL', // Poland
				'PT', // Portugal
				'RO', // Romania
				'SK', // Slovakia
				'SI', // Slovenia
				'ES', // Spain
				'SE', // Sweden
			),
		),
		'SCHENGEN'     => array(
			'name'      => __( 'Schengen Area', 'geo-ip-blocker' ),
			'countries' => array(
				'AT', // Austria
				'BE', // Belgium
				'CZ', // Czech Republic
				'DK', // Denmark
				'EE', // Estonia
				'FI', // Finland
				'FR', // France
				'DE', // Germany
				'GR', // Greece
				'HU', // Hungary
				'IS', // Iceland
				'IT', // Italy
				'LV', // Latvia
				'LI', // Liechtenstein
				'LT', // Lithuania
				'LU', // Luxembourg
				'MT', // Malta
				'NL', // Netherlands
				'NO', // Norway
				'PL', // Poland
				'PT', // Portugal
				'SK', // Slovakia
				'SI', // Slovenia
				'ES', // Spain
				'SE', // Sweden
				'CH', // Switzerland
			),
		),
		'BRICS'        => array(
			'name'      => __( 'BRICS', 'geo-ip-blocker' ),
			'countries' => array(
				'BR', // Brazil
				'RU', // Russia
				'IN', // India
				'CN', // China
				'ZA', // South Africa
			),
		),
		'G7'           => array(
			'name'      => __( 'G7', 'geo-ip-blocker' ),
			'countries' => array(
				'CA', // Canada
				'FR', // France
				'DE', // Germany
				'IT', // Italy
				'JP', // Japan
				'GB', // United Kingdom
				'US', // United States
			),
		),
		'G20'          => array(
			'name'      => __( 'G20', 'geo-ip-blocker' ),
			'countries' => array(
				'AR', // Argentina
				'AU', // Australia
				'BR', // Brazil
				'CA', // Canada
				'CN', // China
				'FR', // France
				'DE', // Germany
				'IN', // India
				'ID', // Indonesia
				'IT', // Italy
				'JP', // Japan
				'MX', // Mexico
				'RU', // Russia
				'SA', // Saudi Arabia
				'ZA', // South Africa
				'KR', // South Korea
				'TR', // Turkey
				'GB', // United Kingdom
				'US', // United States
			),
		),
		'NATO'         => array(
			'name'      => __( 'NATO', 'geo-ip-blocker' ),
			'countries' => array(
				'AL', // Albania
				'BE', // Belgium
				'BG', // Bulgaria
				'CA', // Canada
				'HR', // Croatia
				'CZ', // Czech Republic
				'DK', // Denmark
				'EE', // Estonia
				'FI', // Finland
				'FR', // France
				'DE', // Germany
				'GR', // Greece
				'HU', // Hungary
				'IS', // Iceland
				'IT', // Italy
				'LV', // Latvia
				'LT', // Lithuania
				'LU', // Luxembourg
				'ME', // Montenegro
				'NL', // Netherlands
				'MK', // North Macedonia
				'NO', // Norway
				'PL', // Poland
				'PT', // Portugal
				'RO', // Romania
				'SK', // Slovakia
				'SI', // Slovenia
				'ES', // Spain
				'SE', // Sweden
				'TR', // Turkey
				'GB', // United Kingdom
				'US', // United States
			),
		),
		'ASEAN'        => array(
			'name'      => __( 'ASEAN', 'geo-ip-blocker' ),
			'countries' => array(
				'BN', // Brunei
				'KH', // Cambodia
				'ID', // Indonesia
				'LA', // Laos
				'MY', // Malaysia
				'MM', // Myanmar
				'PH', // Philippines
				'SG', // Singapore
				'TH', // Thailand
				'VN', // Vietnam
			),
		),
		'MERCOSUR'     => array(
			'name'      => __( 'Mercosur', 'geo-ip-blocker' ),
			'countries' => array(
				'AR', // Argentina
				'BR', // Brazil
				'PY', // Paraguay
				'UY', // Uruguay
			),
		),
		'AFRICAN_UNION' => array(
			'name'      => __( 'African Union', 'geo-ip-blocker' ),
			'countries' => array(
				'DZ', 'AO', 'BJ', 'BW', 'BF', 'BI', 'CM', 'CV', 'CF', 'TD',
				'KM', 'CG', 'CD', 'CI', 'DJ', 'EG', 'GQ', 'ER', 'SZ', 'ET',
				'GA', 'GM', 'GH', 'GN', 'GW', 'KE', 'LS', 'LR', 'LY', 'MG',
				'MW', 'ML', 'MR', 'MU', 'MA', 'MZ', 'NA', 'NE', 'NG', 'RW',
				'ST', 'SN', 'SC', 'SL', 'SO', 'ZA', 'SS', 'SD', 'TZ', 'TG',
				'TN', 'UG', 'ZM', 'ZW',
			),
		),
		'ARAB_LEAGUE'  => array(
			'name'      => __( 'Arab League', 'geo-ip-blocker' ),
			'countries' => array(
				'DZ', // Algeria
				'BH', // Bahrain
				'KM', // Comoros
				'DJ', // Djibouti
				'EG', // Egypt
				'IQ', // Iraq
				'JO', // Jordan
				'KW', // Kuwait
				'LB', // Lebanon
				'LY', // Libya
				'MR', // Mauritania
				'MA', // Morocco
				'OM', // Oman
				'PS', // Palestine
				'QA', // Qatar
				'SA', // Saudi Arabia
				'SO', // Somalia
				'SD', // Sudan
				'SY', // Syria
				'TN', // Tunisia
				'AE', // UAE
				'YE', // Yemen
			),
		),
		'CARIBBEAN'    => array(
			'name'      => __( 'Caribbean Community (CARICOM)', 'geo-ip-blocker' ),
			'countries' => array(
				'AG', // Antigua and Barbuda
				'BS', // Bahamas
				'BB', // Barbados
				'BZ', // Belize
				'DM', // Dominica
				'GD', // Grenada
				'GY', // Guyana
				'HT', // Haiti
				'JM', // Jamaica
				'KN', // Saint Kitts and Nevis
				'LC', // Saint Lucia
				'VC', // Saint Vincent and the Grenadines
				'SR', // Suriname
				'TT', // Trinidad and Tobago
			),
		),
		'NORTH_AMERICA' => array(
			'name'      => __( 'North America', 'geo-ip-blocker' ),
			'countries' => array(
				'CA', // Canada
				'MX', // Mexico
				'US', // United States
			),
		),
		'SOUTH_AMERICA' => array(
			'name'      => __( 'South America', 'geo-ip-blocker' ),
			'countries' => array(
				'AR', // Argentina
				'BO', // Bolivia
				'BR', // Brazil
				'CL', // Chile
				'CO', // Colombia
				'EC', // Ecuador
				'GY', // Guyana
				'PY', // Paraguay
				'PE', // Peru
				'SR', // Suriname
				'UY', // Uruguay
				'VE', // Venezuela
			),
		),
		'CENTRAL_AMERICA' => array(
			'name'      => __( 'Central America', 'geo-ip-blocker' ),
			'countries' => array(
				'BZ', // Belize
				'CR', // Costa Rica
				'SV', // El Salvador
				'GT', // Guatemala
				'HN', // Honduras
				'NI', // Nicaragua
				'PA', // Panama
			),
		),
		'NORDIC'       => array(
			'name'      => __( 'Nordic Countries', 'geo-ip-blocker' ),
			'countries' => array(
				'DK', // Denmark
				'FI', // Finland
				'IS', // Iceland
				'NO', // Norway
				'SE', // Sweden
			),
		),
		'BALTICS'      => array(
			'name'      => __( 'Baltic States', 'geo-ip-blocker' ),
			'countries' => array(
				'EE', // Estonia
				'LV', // Latvia
				'LT', // Lithuania
			),
		),
		'MIDDLE_EAST'  => array(
			'name'      => __( 'Middle East', 'geo-ip-blocker' ),
			'countries' => array(
				'BH', // Bahrain
				'IQ', // Iraq
				'IR', // Iran
				'IL', // Israel
				'JO', // Jordan
				'KW', // Kuwait
				'LB', // Lebanon
				'OM', // Oman
				'PS', // Palestine
				'QA', // Qatar
				'SA', // Saudi Arabia
				'SY', // Syria
				'TR', // Turkey
				'AE', // UAE
				'YE', // Yemen
			),
		),
	);
}

/**
 * Expand region codes to country codes.
 *
 * @param array $codes Array of country/region codes.
 * @return array Array of unique country codes.
 */
function geo_ip_blocker_expand_regions( $codes ) {
	$regions   = geo_ip_blocker_get_country_regions();
	$countries = array();

	foreach ( $codes as $code ) {
		if ( isset( $regions[ $code ] ) ) {
			// It's a region, expand it.
			$countries = array_merge( $countries, $regions[ $code ]['countries'] );
		} else {
			// It's a regular country code.
			$countries[] = $code;
		}
	}

	// Return unique country codes.
	return array_unique( $countries );
}

/**
 * Check if a code is a region.
 *
 * @param string $code Code to check.
 * @return bool True if it's a region, false otherwise.
 */
function geo_ip_blocker_is_region( $code ) {
	$regions = geo_ip_blocker_get_country_regions();
	return isset( $regions[ $code ] );
}
