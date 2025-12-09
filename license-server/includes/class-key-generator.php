<?php
/**
 * License key generation utility.
 *
 * Key Format: IUSE-{TIER}-{TIMESTAMP_HEX}-{RANDOM_HEX}-{ORDER_HEX}
 * Examples:
 *   - IUSE-PRO-675789AB-A1B2C3-F1
 *   - IUSE-ENT-675789AB-D4E5F6-2A
 *
 * @package InkfinitLicenseServer
 */

defined( 'ABSPATH' ) || exit;

class BILS_Key_Generator {

	/**
	 * Valid tier codes and their full names.
	 *
	 * @var array
	 */
	private static $tier_map = array(
		'PRO' => 'pro',
		'ENT' => 'enterprise',
	);

	/**
	 * Generate a unique license key.
	 *
	 * @param int    $order_id WooCommerce order ID.
	 * @param string $tier     License tier: 'pro' or 'enterprise'.
	 * @return string Generated license key.
	 */
	public static function generate( $order_id, $tier = 'pro' ) {
		$prefix    = 'IUSE';
		$tier_code = self::get_tier_code( $tier );
		$timestamp = strtoupper( dechex( time() ) );
		$random    = strtoupper( substr( bin2hex( random_bytes( 3 ) ), 0, 6 ) );
		$order_hex = strtoupper( dechex( $order_id ) );

		return sprintf( '%s-%s-%s-%s-%s', $prefix, $tier_code, $timestamp, $random, $order_hex );
	}

	/**
	 * Convert tier name to 3-letter code.
	 *
	 * @param string $tier Tier name (pro, enterprise).
	 * @return string 3-letter tier code.
	 */
	public static function get_tier_code( $tier ) {
		$tier = strtolower( $tier );
		switch ( $tier ) {
			case 'enterprise':
				return 'ENT';
			case 'pro':
			default:
				return 'PRO';
		}
	}

	/**
	 * Extract tier from license key.
	 *
	 * @param string $key License key.
	 * @return string Tier name (pro, enterprise) or 'pro' as default.
	 */
	public static function get_tier_from_key( $key ) {
		if ( preg_match( '/^IUSE-([A-Z]{3})-/', $key, $matches ) ) {
			$code = $matches[1];
			return isset( self::$tier_map[ $code ] ) ? self::$tier_map[ $code ] : 'pro';
		}
		return 'pro';
	}

	/**
	 * Validate key format.
	 *
	 * Valid formats:
	 *   - IUSE-PRO-{HEX}-{HEX}-{HEX}
	 *   - IUSE-ENT-{HEX}-{HEX}-{HEX}
	 *   - Legacy: IUSE-{HEX}-{HEX}-{HEX} (treated as PRO)
	 *
	 * @param string $key License key.
	 * @return bool True if valid format.
	 */
	public static function is_valid_format( $key ) {
		// New format with tier: IUSE-PRO-675789AB-A1B2C3-F1
		if ( preg_match( '/^IUSE-(PRO|ENT)-[A-F0-9]+-[A-F0-9]+-[A-F0-9]+$/i', $key ) ) {
			return true;
		}
		// Legacy format without tier: IUSE-675789AB-A1B2C3-F1
		if ( preg_match( '/^IUSE-[A-F0-9]+-[A-F0-9]+-[A-F0-9]+$/i', $key ) ) {
			return true;
		}
		return false;
	}
}
