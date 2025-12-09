<?php
/**
 * Security Hardening & Validation
 * 
 * Enterprise-grade security measures for Inkfinit Shipping
 * - Input validation & sanitization
 * - Output escaping
 * - Nonce verification
 * - Capability checks
 * - Rate limiting
 * - SQL injection prevention
 * - XSS prevention
 * 
 * @package WTC_Shipping
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ==========================================================================
 * INPUT VALIDATION FUNCTIONS
 * ==========================================================================
 */

/**
 * Validate and sanitize ZIP code
 * 
 * @param string $zip ZIP code to validate.
 * @return string|false Sanitized ZIP or false if invalid.
 */
function wtcc_validate_zip_code( $zip ) {
	// Remove all non-alphanumeric characters
	$zip = preg_replace( '/[^a-zA-Z0-9]/', '', $zip );
	
	// US ZIP: 5 digits or 9 digits (ZIP+4)
	if ( preg_match( '/^\d{5}(\d{4})?$/', $zip ) ) {
		return substr( $zip, 0, 5 ); // Return just 5-digit
	}
	
	// Canadian postal code
	if ( preg_match( '/^[A-Za-z]\d[A-Za-z]\d[A-Za-z]\d$/', $zip ) ) {
		return strtoupper( $zip );
	}
	
	// Allow other international formats (basic alphanumeric)
	if ( preg_match( '/^[a-zA-Z0-9]{3,10}$/', $zip ) ) {
		return strtoupper( $zip );
	}
	
	return false;
}

/**
 * Validate country code (ISO 3166-1 alpha-2)
 * 
 * @param string $country Country code to validate.
 * @return string|false Valid country code or false.
 */
function wtcc_validate_country_code( $country ) {
	$country = strtoupper( sanitize_text_field( $country ) );
	
	// Must be exactly 2 letters
	if ( ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
		return false;
	}
	
	// Validate against WooCommerce countries if available
	if ( function_exists( 'WC' ) && WC()->countries ) {
		$valid_countries = array_keys( WC()->countries->get_countries() );
		if ( ! in_array( $country, $valid_countries, true ) ) {
			return false;
		}
	}
	
	return $country;
}

/**
 * Validate tracking number format
 * 
 * @param string $tracking Tracking number.
 * @return string|false Sanitized tracking or false if invalid.
 */
function wtcc_validate_tracking_number( $tracking ) {
	// Remove spaces and dashes
	$tracking = preg_replace( '/[\s\-]/', '', $tracking );
	
	// USPS tracking: 20-34 alphanumeric characters
	if ( preg_match( '/^[A-Z0-9]{20,34}$/i', $tracking ) ) {
		return strtoupper( $tracking );
	}
	
	// Also accept shorter formats for international
	if ( preg_match( '/^[A-Z]{2}\d{9}[A-Z]{2}$/i', $tracking ) ) {
		return strtoupper( $tracking );
	}
	
	return false;
}

/**
 * Validate weight value
 * 
 * @param mixed $weight Weight to validate.
 * @param float $min    Minimum allowed.
 * @param float $max    Maximum allowed.
 * @return float|false Valid weight or false.
 */
function wtcc_validate_weight( $weight, $min = 0, $max = 70 ) {
	if ( ! is_numeric( $weight ) ) {
		return false;
	}
	
	$weight = (float) $weight;
	
	if ( $weight < $min || $weight > $max ) {
		return false;
	}
	
	return round( $weight, 4 );
}

/**
 * Validate dimension value
 * 
 * @param mixed $dim Dimension to validate.
 * @param float $max Maximum allowed (USPS limit is 108").
 * @return float|false Valid dimension or false.
 */
function wtcc_validate_dimension( $dim, $max = 108 ) {
	if ( ! is_numeric( $dim ) ) {
		return false;
	}
	
	$dim = (float) $dim;
	
	if ( $dim < 0 || $dim > $max ) {
		return false;
	}
	
	return round( $dim, 2 );
}

/**
 * Validate monetary amount
 * 
 * @param mixed $amount Amount to validate.
 * @param float $max    Maximum allowed.
 * @return float|false Valid amount or false.
 */
function wtcc_validate_monetary_amount( $amount, $max = 10000 ) {
	if ( ! is_numeric( $amount ) ) {
		return false;
	}
	
	$amount = (float) $amount;
	
	if ( $amount < 0 || $amount > $max ) {
		return false;
	}
	
	return round( $amount, 2 );
}

/**
 * ==========================================================================
 * RATE LIMITING
 * ==========================================================================
 */

/**
 * Check rate limit for API calls
 * 
 * @param string $action     Action identifier.
 * @param int    $max_calls  Maximum calls allowed.
 * @param int    $period     Period in seconds.
 * @return bool True if within limit, false if exceeded.
 */
function wtcc_check_rate_limit( $action, $max_calls = 60, $period = 60 ) {
	$key = 'wtcc_rate_' . md5( $action . '_' . wtcc_get_client_ip() );
	$current = get_transient( $key );
	
	if ( false === $current ) {
		set_transient( $key, 1, $period );
		return true;
	}
	
	if ( $current >= $max_calls ) {
		return false;
	}
	
	set_transient( $key, $current + 1, $period );
	return true;
}

/**
 * Get client IP address securely
 * 
 * @return string IP address.
 */
function wtcc_get_client_ip() {
	$ip = '';
	
	// Check for proxy headers (but don't trust completely)
	$headers = array(
		'HTTP_CF_CONNECTING_IP', // Cloudflare
		'HTTP_X_REAL_IP',
		'REMOTE_ADDR',
	);
	
	foreach ( $headers as $header ) {
		if ( ! empty( $_SERVER[ $header ] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			break;
		}
	}
	
	// Validate IP format
	if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return $ip;
	}
	
	return '0.0.0.0';
}

/**
 * ==========================================================================
 * AJAX SECURITY WRAPPERS
 * ==========================================================================
 */

/**
 * Secure AJAX handler wrapper
 * 
 * @param string   $action      AJAX action name.
 * @param callable $callback    Handler function.
 * @param string   $capability  Required capability (default: manage_options).
 * @param bool     $public      Allow non-logged-in users.
 */
function wtcc_register_secure_ajax( $action, $callback, $capability = 'manage_options', $public = false ) {
	$wrapper = function() use ( $callback, $capability, $action ) {
		// Verify nonce
		if ( ! check_ajax_referer( 'wtcc_' . $action, 'nonce', false ) ) {
			wp_send_json_error( array(
				'message' => 'Security check failed',
				'code'    => 'invalid_nonce',
			), 403 );
		}
		
		// Check capability if not public
		if ( $capability && ! current_user_can( $capability ) ) {
			wp_send_json_error( array(
				'message' => 'Permission denied',
				'code'    => 'unauthorized',
			), 403 );
		}
		
		// Rate limiting
		if ( ! wtcc_check_rate_limit( $action ) ) {
			wp_send_json_error( array(
				'message' => 'Too many requests. Please wait.',
				'code'    => 'rate_limited',
			), 429 );
		}
		
		// Call actual handler
		call_user_func( $callback );
	};
	
	add_action( 'wp_ajax_' . $action, $wrapper );
	
	if ( $public ) {
		add_action( 'wp_ajax_nopriv_' . $action, $wrapper );
	}
}

/**
 * ==========================================================================
 * SECURITY HEADERS
 * ==========================================================================
 */

/**
 * Add security headers to admin pages
 * Note: Disabled to prevent early header warnings in PHP 8+
 */
// add_action( 'send_headers', 'wtcc_add_security_headers' );
function wtcc_add_security_headers() {
	// Only in admin area
	if ( ! is_admin() ) {
		return;
	}
	
	// Only on our pages
	if ( ! isset( $_GET['page'] ) ) {
		return;
	}
	
	$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
	if ( ! $page || strpos( $page, 'wtc-core-shipping' ) === false ) {
		return;
	}
	
	// Prevent clickjacking
	header( 'X-Frame-Options: SAMEORIGIN' );
	
	// Prevent MIME sniffing
	header( 'X-Content-Type-Options: nosniff' );
	
	// XSS Protection
	header( 'X-XSS-Protection: 1; mode=block' );
}

/**
 * ==========================================================================
 * DATA ENCRYPTION
 * ==========================================================================
 */

/**
 * Encrypt sensitive data before storing
 * 
 * @param string $data Data to encrypt.
 * @return string Encrypted data.
 */
function wtcc_encrypt_data( $data ) {
	if ( empty( $data ) ) {
		return '';
	}
	
	$key = wp_salt( 'auth' );
	$iv = substr( md5( wp_salt( 'secure_auth' ) ), 0, 16 );
	
	if ( function_exists( 'openssl_encrypt' ) ) {
		$encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
		return base64_encode( $encrypted );
	}
	
	// Fallback: basic obfuscation (not true encryption)
	return base64_encode( $data );
}

/**
 * Decrypt sensitive data
 * 
 * @param string $data Encrypted data.
 * @return string Decrypted data.
 */
function wtcc_decrypt_data( $data ) {
	if ( empty( $data ) ) {
		return '';
	}
	
	$key = wp_salt( 'auth' );
	$iv = substr( md5( wp_salt( 'secure_auth' ) ), 0, 16 );
	
	if ( function_exists( 'openssl_encrypt' ) ) {
		$decoded = base64_decode( $data );
		return openssl_decrypt( $decoded, 'AES-256-CBC', $key, 0, $iv );
	}
	
	// Fallback
	return base64_decode( $data );
}

/**
 * ==========================================================================
 * LOGGING & AUDIT
 * ==========================================================================
 */

/**
 * Log security events
 * 
 * @param string $event   Event type.
 * @param string $message Event message.
 * @param array  $context Additional context.
 */
function wtcc_security_log( $event, $message, $context = array() ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}
	
	$log_entry = array(
		'time'    => current_time( 'mysql' ),
		'event'   => sanitize_key( $event ),
		'message' => sanitize_text_field( $message ),
		'ip'      => wtcc_get_client_ip(),
		'user'    => get_current_user_id(),
		'context' => array_map( 'sanitize_text_field', $context ),
	);
	
	error_log( 'WTC Security: ' . wp_json_encode( $log_entry ) );
}

/**
 * ==========================================================================
 * CLEANUP & MAINTENANCE
 * ==========================================================================
 */

/**
 * Clean up old transients
 */
add_action( 'wtcc_daily_cleanup', 'wtcc_cleanup_old_data' );
function wtcc_cleanup_old_data() {
	global $wpdb;
	
	// Clean rate limit transients older than 1 hour
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE %s 
			AND option_value < %d",
			'%_transient_wtcc_rate_%',
			time() - HOUR_IN_SECONDS
		)
	);
}

// Schedule cleanup if not already scheduled
if ( ! wp_next_scheduled( 'wtcc_daily_cleanup' ) ) {
	wp_schedule_event( time(), 'daily', 'wtcc_daily_cleanup' );
}

/**
 * ==========================================================================
 * CONTENT SECURITY POLICY NONCES
 * ==========================================================================
 */

/**
 * Generate CSP nonce for inline scripts
 * 
 * @return string CSP nonce.
 */
function wtcc_get_csp_nonce() {
	static $nonce = null;
	
	if ( null === $nonce ) {
		$nonce = wp_create_nonce( 'wtcc_csp_' . get_current_user_id() );
	}
	
	return $nonce;
}

/**
 * ==========================================================================
 * SECURE OPTION HANDLING
 * ==========================================================================
 */

/**
 * Get option with validation
 * 
 * @param string $option   Option name.
 * @param mixed  $default  Default value.
 * @param string $type     Expected type (string, int, float, array, bool).
 * @return mixed Validated option value.
 */
function wtcc_get_option( $option, $default = '', $type = 'string' ) {
	$value = get_option( $option, $default );
	
	switch ( $type ) {
		case 'int':
			return intval( $value );
		case 'float':
			return floatval( $value );
		case 'bool':
			return (bool) $value;
		case 'array':
			return is_array( $value ) ? $value : $default;
		case 'string':
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Update option with validation
 * 
 * @param string $option Option name.
 * @param mixed  $value  Value to save.
 * @param string $type   Value type.
 * @return bool Success.
 */
function wtcc_update_option( $option, $value, $type = 'string' ) {
	// Validate option name
	if ( ! preg_match( '/^wtcc?_[a-z_]+$/', $option ) ) {
		return false;
	}
	
	switch ( $type ) {
		case 'int':
			$value = intval( $value );
			break;
		case 'float':
			$value = floatval( $value );
			break;
		case 'bool':
			$value = (bool) $value;
			break;
		case 'array':
			if ( ! is_array( $value ) ) {
				return false;
			}
			$value = array_map( 'sanitize_text_field', $value );
			break;
		case 'string':
		default:
			$value = sanitize_text_field( $value );
	}
	
	return update_option( $option, $value );
}
