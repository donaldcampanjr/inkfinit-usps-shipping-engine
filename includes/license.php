<?php
/**
 * Licensing helpers for gating features by tier.
 *
 * Supports: free, pro, premium, enterprise
 * Includes expiry tracking, tier detection, and remote validation.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Edition constants.
define( 'WTCC_TIER_FREE', 'free' );
define( 'WTCC_TIER_PRO', 'pro' );
define( 'WTCC_TIER_PREMIUM', 'premium' );
define( 'WTCC_TIER_ENTERPRISE', 'enterprise' );

/**
 * Get current edition/tier.
 *
 * - 'enterprise' => full feature set + white-label + bulk import
 * - 'premium'    => full feature set + white-label + bulk import
 * - 'pro'        => full feature set (unlocked by license)
 * - 'free'       => calculator/demo only, core automation gated
 *
 * @return string
 */
function wtcc_get_edition() {
	// 1) Allow hard override via constant for alternate builds (e.g., staging).
	if ( defined( 'WTCC_FORCE_EDITION' ) ) {
		$forced = strtolower( (string) WTCC_FORCE_EDITION );
		if ( in_array( $forced, array( 'free', 'pro', 'premium', 'enterprise' ), true ) ) {
			return $forced;
		}
	}

	// 2) If a license key is present and remote validation says it's valid, use tier from license data.
	$license_key = wtcc_get_license_key();
	if ( ! empty( $license_key ) ) {
		$license_data = wtcc_get_license_data();
		if ( $license_data && isset( $license_data['valid'] ) && $license_data['valid'] ) {
			// Tier from license server, default to 'pro'.
			return isset( $license_data['tier'] ) ? strtolower( $license_data['tier'] ) : 'pro';
		}
		// Status unknown (server unreachable) - still allow Pro.
		$status = wtcc_get_license_status();
		if ( $status === 'unknown' ) {
			return 'pro';
		}
	}

	// 3) Fallback to stored edition option, defaulting to free for new installs.
	$edition = get_option( 'wtcc_edition', 'free' );
	$edition = is_string( $edition ) ? strtolower( $edition ) : 'free';

	if ( ! in_array( $edition, array( 'free', 'pro', 'premium', 'enterprise' ), true ) ) {
		$edition = 'free';
	}

	return $edition;
}

/**
 * Determine if this install should behave as Pro or higher.
 * Result is cached per request for performance.
 *
 * @return bool
 */
function wtcc_is_pro() {
	static $is_pro = null;
	if ( $is_pro === null ) {
		$is_pro = in_array( wtcc_get_edition(), array( 'pro', 'premium', 'enterprise' ), true );
	}
	return $is_pro;
}

/**
 * Determine if this install is Premium or higher.
 * Result is cached per request for performance.
 *
 * @return bool
 */
function wtcc_is_premium() {
	static $is_premium = null;
	if ( $is_premium === null ) {
		$is_premium = in_array( wtcc_get_edition(), array( 'premium', 'enterprise' ), true );
	}
	return $is_premium;
}

/**
 * Determine if this install is Enterprise.
 * Result is cached per request for performance.
 *
 * @return bool
 */
function wtcc_is_enterprise() {
	static $is_enterprise = null;
	if ( $is_enterprise === null ) {
		$is_enterprise = 'enterprise' === wtcc_get_edition();
	}
	return $is_enterprise;
}

/**
 * Retrieve stored license key (if any).
 *
 * @return string
 */
function wtcc_get_license_key() {
	$key = get_option( 'wtcc_license_key', '' );
	return is_string( $key ) ? trim( $key ) : '';
}

/**
 * Get full license data from server (cached).
 *
 * Returns array with keys: valid, tier, expires_at, customer, etc.
 * Returns null if no key or validation failed.
 *
 * @return array|null
 */
function wtcc_get_license_data() {
	$key = wtcc_get_license_key();
	if ( '' === $key ) {
		return null;
	}

	// Test keys: simulate full license data locally.
	// SECURITY: Controlled access to test keys with obscure prefix.
	// Test key format: INKDEV-[TIER]-[random] (not obvious like "TESTKEY")
	$is_test_key = preg_match( '/^INKDEV-[A-Z0-9]{2,}-[A-Z0-9]{8,}$/i', $key );
	
	if ( $is_test_key ) {
		$allow_test_keys = false;

		/*
		 * ============================================================
		 * PRODUCTION BUILD FLAG - TEST KEY KILL SWITCH
		 * ============================================================
		 * 
		 * IMPORTANT: For production/public release builds, set this to TRUE.
		 * 
		 * When $disable_test_keys_permanently = true:
		 * - ALL test keys (INKDEV-*) will be rejected
		 * - Even User ID 1 / Super Admins cannot use test keys
		 * - This is the production-secure configuration
		 * 
		 * When $disable_test_keys_permanently = false (DEVELOPMENT MODE):
		 * - Test keys work for User ID 1 (site owner)
		 * - Test keys work for Super Admins on multisite
		 * - Test keys work if WTCC_ALLOW_TEST_KEYS constant is true
		 * - Test keys work on localhost/staging with WP_DEBUG
		 * 
		 * BEFORE PUBLIC RELEASE: Change this to TRUE and rebuild ZIP.
		 * ============================================================
		 */
		$disable_test_keys_permanently = true; // PRODUCTION MODE - Test keys disabled

		if ( $disable_test_keys_permanently ) {
			return array(
				'valid'   => false,
				'tier'    => 'free',
				'message' => 'Invalid license key.',
			);
		}

		// Option 1: Explicit constant in wp-config.php.
		if ( defined( 'WTCC_ALLOW_TEST_KEYS' ) && WTCC_ALLOW_TEST_KEYS === true ) {
			$allow_test_keys = true;
		}

		// Option 2: Developer Mode toggle in admin (stored in database).
		// This is the easiest way - just toggle it in License settings.
		if ( get_option( 'wtcc_dev_mode_enabled', false ) && current_user_can( 'manage_options' ) ) {
			$allow_test_keys = true;
		}

		// Option 3: User ID 1 (site owner) on single site.
		// This lets the primary admin test without any config changes.
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id === 1 ) {
				// User ID 1 is typically the original site owner.
				$allow_test_keys = true;
			}
		}

		// Option 4: WP_DEBUG + local/staging environment.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$site_url = home_url();
			$is_local = (
				strpos( $site_url, 'localhost' ) !== false ||
				strpos( $site_url, '127.0.0.1' ) !== false ||
				strpos( $site_url, '.local' ) !== false ||
				strpos( $site_url, '.test' ) !== false ||
				strpos( $site_url, 'staging.' ) !== false ||
				strpos( $site_url, '.staging' ) !== false ||
				strpos( $site_url, 'dev.' ) !== false ||
				strpos( $site_url, '.dev' ) !== false
			);
			if ( $is_local ) {
				$allow_test_keys = true;
			}
		}

		// If test keys not allowed, reject them (generic message - don't reveal it's a test key).
		if ( ! $allow_test_keys ) {
			return array(
				'valid'   => false,
				'tier'    => 'free',
				'message' => 'License validation failed. Please contact support.',
			);
		}

		// Determine tier from test key segment.
		// Format: INKDEV-PRO-XXXXXXXX, INKDEV-PREM-XXXXXXXX, INKDEV-ENT-XXXXXXXX
		$tier = 'pro';
		if ( preg_match( '/INKDEV-ENT/i', $key ) ) {
			$tier = 'enterprise';
		} elseif ( preg_match( '/INKDEV-PREM/i', $key ) ) {
			$tier = 'premium';
		}

		$test_expiry = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
		return array(
			'valid'       => true,
			'tier'        => $tier,
			'license_key' => $key,
			'expires_at'  => $test_expiry,
			'customer'    => 'Development License',
			'is_test_key' => true,
		);
	}

	// Get server URL from option or fall back to constant.
	$server_url = get_option( 'wtcc_license_server_url', '' );
	if ( empty( $server_url ) && defined( 'WTCC_LICENSE_SERVER_URL' ) ) {
		$server_url = WTCC_LICENSE_SERVER_URL;
	}
	if ( empty( $server_url ) ) {
		return null;
	}

	// Cache result for 12 hours.
	$cache_key = 'wtcc_license_data_' . md5( $key . '|' . $server_url );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	// Build request payload.
	$body = array(
		'license_key' => $key,
		'site_url'    => home_url(),
		'plugin'      => 'inkfinit-shipping-engine',
		'version'     => defined( 'WTCC_SHIPPING_VERSION' ) ? WTCC_SHIPPING_VERSION : '',
	);

	$args = array(
		'timeout' => 10,
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( $body ),
	);

	$response = wp_remote_post( $server_url, $args );

	if ( is_wp_error( $response ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Inkfinit Shipping: License check failed - ' . $response->get_error_message() );
		}
		return null;
	}

	$code     = wp_remote_retrieve_response_code( $response );
	$body_raw = wp_remote_retrieve_body( $response );
	$data     = json_decode( $body_raw, true );

	if ( 200 !== $code || ! is_array( $data ) ) {
		return null;
	}

	set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

	return $data;
}

/**
 * Get license status string.
 *
 * @return string 'valid', 'invalid', 'none', 'unknown', 'expired', 'expiring_soon'
 */
function wtcc_get_license_status() {
	$key = wtcc_get_license_key();
	if ( '' === $key ) {
		return 'none';
	}

	// Check for valid INKDEV test key format (admin-only access checked in wtcc_get_license_data).
	$is_inkdev_key = preg_match( '/^INKDEV-[A-Z0-9]{2,}-[A-Z0-9]{8,}$/i', $key );
	if ( $is_inkdev_key ) {
		$data = wtcc_get_license_data();
		if ( $data && isset( $data['valid'] ) && $data['valid'] ) {
			return 'valid';
		}
		// If test key didn't validate (e.g., non-admin user), return invalid.
		return 'invalid';
	}

	$data = wtcc_get_license_data();
	if ( ! $data ) {
		return 'unknown';
	}

	if ( isset( $data['valid'] ) && $data['valid'] ) {
		// Check expiry.
		if ( isset( $data['expires_at'] ) && ! empty( $data['expires_at'] ) ) {
			$expires = strtotime( $data['expires_at'] );
			$now     = time();
			if ( $expires && $now > $expires ) {
				return 'expired';
			}
			// Warn if expiring within 30 days.
			if ( $expires && ( $expires - $now ) < ( 30 * DAY_IN_SECONDS ) ) {
				return 'expiring_soon';
			}
		}
		return 'valid';
	}

	return 'invalid';
}

/**
 * Get license expiration info.
 *
 * @return array|null Array with 'expires_at' (datetime string), 'days_left' (int), or null if unknown.
 */
function wtcc_get_license_expiry_info() {
	$data = wtcc_get_license_data();
	if ( ! $data || ! isset( $data['expires_at'] ) || empty( $data['expires_at'] ) ) {
		return null;
	}

	$expires   = strtotime( $data['expires_at'] );
	$now       = time();
	$days_left = max( 0, floor( ( $expires - $now ) / DAY_IN_SECONDS ) );

	return array(
		'expires_at' => $data['expires_at'],
		'days_left'  => (int) $days_left,
		'is_expired' => $now > $expires,
	);
}

/**
 * Render a small Pro badge using WordPress native admin styles.
 */
function wtcc_render_pro_badge() {
	echo '<span class="update-plugins count-1"><span class="plugin-count">Pro</span></span>';
}

/**
 * Render a small Enterprise badge using WordPress native admin styles.
 */
function wtcc_render_enterprise_badge() {
	echo '<span class="update-plugins count-1"><span class="plugin-count">Enterprise</span></span>';
}

/**
 * Generate a test license key for development/testing.
 *
 * Format: INKDEV-[TIER]-[RANDOM10]
 * Only works for User ID 1 / Super Admin (validated in wtcc_get_license_data)
 *
 * @param string $tier Optional tier: 'pro', 'prem', 'ent'. Default 'ent'.
 * @return string
 */
function wtcc_generate_test_license_key( $tier = 'ent' ) {
	$tier_code   = strtoupper( substr( $tier, 0, 4 ) ); // PRO, PREM, or ENT
	$random_part = strtoupper( substr( md5( microtime() . wp_rand( 1, 999999 ) ), 0, 10 ) );
	return "INKDEV-{$tier_code}-{$random_part}";
}
