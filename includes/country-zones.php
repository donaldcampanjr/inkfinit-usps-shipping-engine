<?php
/**
 * Country Zones Configuration
 * Auto-maps country codes to zones. Admin never touches this.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get country zone mapping
 *
 * @return array Map of country codes to zones
 */
function wtcc_shipping_get_country_zones() {
	return array(
		// North America
		'US' => 'usa',
		'CA' => 'canada',
		'MX' => 'mexico',

		// Europe
		'GB' => 'uk',
		'FR' => 'eu1',
		'DE' => 'eu1',
		'ES' => 'eu1',
		'IT' => 'eu1',
		'SE' => 'eu2',
		'NO' => 'eu2',
		'DK' => 'eu2',
		'PL' => 'eu2',
		'CZ' => 'eu2',
		'RO' => 'eu2',
		'UA' => 'eu2',
		'RU' => 'eu2',

		// Asia Pacific
		'AU' => 'apac',
		'NZ' => 'apac',
		'JP' => 'asia',
		'KR' => 'asia',
		'CN' => 'asia',
		'IN' => 'asia',
		'SG' => 'asia',
		'TH' => 'asia',
		'VN' => 'asia',

		// South America
		'BR' => 'south-america',
		'AR' => 'south-america',
		'CL' => 'south-america',
		'CO' => 'south-america',
		'PE' => 'south-america',

		// Middle East / Africa
		'AE' => 'middle-east',
		'SA' => 'middle-east',
		'ZA' => 'africa',
		'NG' => 'africa',
		'EG' => 'africa',
	);
}

/**
 * Get zone for a country code
 * Auto-detection — Admin never does this
 *
 * @param string $country_code 2-letter country code.
 * @return string Zone identifier
 */
function wtcc_shipping_get_zone_for_country( $country_code ) {
	$zones = wtcc_shipping_get_country_zones();
	return $zones[ strtoupper( $country_code ) ] ?? 'rest-of-world';
}

/**
 * Get zone label by code
 *
 * @param string $zone_code Zone identifier.
 * @return string Human-readable zone name
 */
function wtcc_shipping_get_zone_label( $zone_code ) {
	$zones = array(
		'usa'            => 'United States',
		'canada'         => 'Canada',
		'mexico'         => 'Mexico',
		'uk'             => 'United Kingdom',
		'eu1'            => 'Western Europe (FR, DE, ES, IT)',
		'eu2'            => 'Northern & Eastern Europe',
		'apac'           => 'Australia & New Zealand',
		'asia'           => 'Asia (JP, KR, CN, etc)',
		'south-america'  => 'South America',
		'middle-east'    => 'Middle East',
		'africa'         => 'Africa',
		'rest-of-world'  => 'Rest of World',
	);

	return $zones[ $zone_code ] ?? $zone_code;
}

/**
 * Get country code by zone
 *
 * @param string $zone_code Zone identifier.
 * @return array Array of country codes in zone
 */
function wtcc_shipping_get_countries_by_zone( $zone_code ) {
	$zones = wtcc_shipping_get_country_zones();
	$result = array();

	foreach ( $zones as $country => $zone ) {
		if ( $zone === $zone_code ) {
			$result[] = $country;
		}
	}

	return $result;
}
