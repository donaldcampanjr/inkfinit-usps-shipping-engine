<?php
/**
 * Core Validation & Sanitization Functions
 * Loaded on all contexts (frontend + admin)
 * Required by shipping calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize monetary amount
 *
 * @param mixed $amount Amount to sanitize.
 * @return float Sanitized amount
 */
function wtcc_shipping_sanitize_amount( $amount ) {
	if ( is_numeric( $amount ) ) {
		return (float) $amount;
	}
	return 0.0;
}

/**
 * Sanitize percentage value
 *
 * @param mixed $percent Percentage to sanitize.
 * @return float Sanitized percentage (0-100)
 */
function wtcc_shipping_sanitize_percent( $percent ) {
	$percent = (float) $percent;
	return max( 0, min( 100, $percent ) );
}

/**
 * Validate numeric value
 *
 * @param mixed  $value Value to validate.
 * @param string $field Field name for logging.
 * @return float|null Validated value or null if invalid
 */
if ( ! function_exists( 'wtcc_shipping_validate_numeric' ) ) {
function wtcc_shipping_validate_numeric( $value, $field = 'value' ) {
	if ( is_numeric( $value ) ) {
		return (float) $value;
	}
	error_log( "Inkfinit Shipping: Invalid numeric value for '{$field}': " . wp_json_encode( $value ) );
	return null;
}
} // end function_exists

/**
 * Validate and sanitize zone
 *
 * @param string $zone Zone code.
 * @param array  $allowed_zones Allowed zones (if empty, checks against default list).
 * @return string Validated zone code
 */
if ( ! function_exists( 'wtcc_shipping_validate_zone' ) ) {
function wtcc_shipping_validate_zone( $zone, $allowed_zones = array() ) {
	if ( empty( $allowed_zones ) ) {
		$allowed_zones = array(
			'usa',
			'canada',
			'mexico',
			'uk',
			'eu1',
			'eu2',
			'apac',
			'asia',
			'south-america',
			'middle-east',
			'africa',
			'rest-of-world',
		);
	}

	$zone = sanitize_text_field( $zone );

	if ( in_array( $zone, $allowed_zones, true ) ) {
		return $zone;
	}

	error_log( "Inkfinit Shipping: Invalid zone '{$zone}'. Defaulting to 'rest-of-world'." );
	return 'rest-of-world';
}
} // end function_exists

/**
 * Sanitize preset name
 *
 * @param string $preset Preset name.
 * @return string Sanitized preset name
 */
function wtcc_shipping_sanitize_preset( $preset ) {
	$preset = sanitize_text_field( $preset );

	// Block dangerous patterns
	if ( preg_match( '/[;\'"|`\\\\]/', $preset ) ) {
		error_log( "Inkfinit Shipping: Blocked dangerous characters in preset: {$preset}" );
		return 'default';
	}

	return $preset;
}

/**
 * Sanitize calculation data for storage
 *
 * @param array $calc_data Calculation data.
 * @return array Sanitized data
 */
function wtcc_shipping_sanitize_calc_data( $calc_data ) {
	$sanitized = array();

	foreach ( $calc_data as $key => $value ) {
		if ( is_numeric( $value ) ) {
			$sanitized[ $key ] = (float) $value;
		} else {
			$sanitized[ $key ] = sanitize_text_field( $value );
		}
	}

	return $sanitized;
}

/**
 * AJAX handler to test USPS API connection
 */
add_action( 'wp_ajax_wtcc_test_api_connection', 'wtcc_ajax_test_api_connection' );
function wtcc_ajax_test_api_connection() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wtcc_test_api' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}

	// Check capabilities - allow both manage_woocommerce and manage_options
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
	}

	// Get API credentials from new option structure, with fallback to legacy options
	$options = get_option( 'wtcc_usps_api_options', array() );
	$consumer_key = ! empty( $options['consumer_key'] ) ? $options['consumer_key'] : get_option( 'wtcc_usps_consumer_key', '' );
	$consumer_secret = ! empty( $options['consumer_secret'] ) ? $options['consumer_secret'] : get_option( 'wtcc_usps_consumer_secret', '' );
	$origin_zip = ! empty( $options['origin_zip'] ) ? $options['origin_zip'] : get_option( 'wtcc_origin_zip', '' );

	// Validate credentials exist
	if ( empty( $consumer_key ) || empty( $consumer_secret ) ) {
		wp_send_json_error( array( 'message' => 'API credentials not configured. Get them at developer.usps.com' ) );
	}

	if ( empty( $origin_zip ) ) {
		wp_send_json_error( array( 'message' => 'Origin ZIP code not set' ) );
	}

	// Test the API connection
	if ( function_exists( 'wtcc_usps_test_connection' ) ) {
		$result = wtcc_usps_test_connection();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		} elseif ( is_array( $result ) && isset( $result['message'] ) ) {
			// Enhanced response with test rate info
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_success( array( 'message' => 'API connection successful! Live rates enabled.' ) );
		}
	} else {
		wp_send_json_error( array( 'message' => 'Test function not available. Please check plugin files.' ) );
	}
}

/**
 * Convert weight to ounces
 *
 * @param float  $weight Weight value.
 * @param string $unit   Weight unit (lbs, oz, kg, g).
 * @return float Weight converted to ounces
 */
if ( ! function_exists( 'wtcc_shipping_convert_to_oz' ) ) {
function wtcc_shipping_convert_to_oz( $weight, $unit ) {
	$conversions = array(
		'lbs' => 16,
		'lb'  => 16,
		'oz'  => 1,
		'kg'  => 35.274,
		'g'   => 0.035274,
	);
	return $weight * ( $conversions[ strtolower( $unit ) ] ?? 1 );
}
} // end function_exists

/**
 * Generate and save a test license key for development.
 * This is for testing Pro features without a remote license server.
 *
 * Usage: wp eval 'wtcc_setup_test_license();'
 *
 * @return string Generated test key
 */
function wtcc_setup_test_license() {
	// Generate the test key using INKDEV format.
	// Format: INKDEV-[TIER]-[8+ alphanumeric]
	// TIER options: ENT (Enterprise), PREM (Premium), PRO (Pro)
	$random_part = strtoupper( substr( md5( microtime() . wp_rand( 1, 999999 ) ), 0, 10 ) );
	$key         = "INKDEV-ENT-{$random_part}";

	// Save to options and clear any cached validation.
	update_option( 'wtcc_license_key', $key );
	delete_transient( 'wtcc_license_status' );

	// Return the generated key.
	return $key;
}

