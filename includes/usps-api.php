<?php
/**
 * USPS WebTools API Integration
 * Real-time shipping rate calculation using USPS API
 * Requires USPS Web Tools account (Developer account)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get USPS API credentials from options
 */
function wtcc_shipping_get_usps_credentials() {
	$options = get_option( 'wtcc_usps_api_options', array() );
	$api_mode = $options['api_mode'] ?? 'production';
	
	// Get credentials with fallback to legacy option names (use !empty to catch empty strings)
	$consumer_key = ! empty( $options['consumer_key'] ) ? $options['consumer_key'] : get_option( 'wtcc_usps_consumer_key', '' );
	$consumer_secret = ! empty( $options['consumer_secret'] ) ? $options['consumer_secret'] : get_option( 'wtcc_usps_consumer_secret', '' );
	
	return array(
		'consumer_key'    => $consumer_key,
		'consumer_secret' => $consumer_secret,
		// Production: https://apis.usps.com (note the 's')
		// Testing: https://apis-tem.usps.com
		'api_endpoint'    => ( 'development' === $api_mode )
			? 'https://apis-tem.usps.com'
			: 'https://apis.usps.com',
		'oauth_endpoint'  => ( 'development' === $api_mode )
			? 'https://apis-tem.usps.com/oauth2/v3/token'
			: 'https://apis.usps.com/oauth2/v3/token',
	);
}

/**
 * Validate USPS credentials
 */
function wtcc_shipping_validate_usps_credentials( $consumer_key, $consumer_secret ) {
	if ( empty( $consumer_key ) || empty( $consumer_secret ) ) {
		return false;
	}

	// Min length check
	if ( strlen( $consumer_key ) < 10 || strlen( $consumer_secret ) < 10 ) {
		return false;
	}

	return true;
}

/**
 * Get OAuth access token from USPS API
 * Tokens are cached for 7 hours (expires at 8 hours per USPS docs)
 * 
 * @param array $credentials USPS API credentials
 * @param bool  $force_refresh Force new token (bypass cache)
 * @return string|false Access token or false on failure
 */
function wtcc_shipping_get_oauth_token( $credentials, $force_refresh = false ) {
	// Sanitize credentials - remove all whitespace
	$credentials['consumer_key'] = preg_replace( '/\s+/', '', $credentials['consumer_key'] );
	$credentials['consumer_secret'] = preg_replace( '/\s+/', '', $credentials['consumer_secret'] );
	
	// Check cache first (unless forcing refresh)
	if ( ! $force_refresh ) {
		$cached_token = get_transient( 'wtcc_usps_oauth_token' );
		if ( false !== $cached_token && ! empty( $cached_token ) ) {
			return $cached_token;
		}
	}

	// Request new token using client_credentials grant type
	// Per USPS OAuth docs: client_id and client_secret go in the body
	// IMPORTANT: scope parameter is REQUIRED as of 2024 USPS API changes
	$response = wp_remote_post(
		$credentials['oauth_endpoint'],
		array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept'       => 'application/json',
			),
			'body' => array(
				'grant_type'    => 'client_credentials',
				'client_id'     => $credentials['consumer_key'],
				'client_secret' => $credentials['consumer_secret'],
				'scope'         => 'prices international-prices',
			),
			'timeout'   => 15,
			'sslverify' => true,
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'USPS OAuth Error: ' . $response->get_error_message() );
		return false;
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$body_raw = wp_remote_retrieve_body( $response );
	
	// Log for debugging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'USPS OAuth Response Code: ' . $status_code );
		error_log( 'USPS OAuth Response: ' . substr( $body_raw, 0, 500 ) );
	}

	$body = json_decode( $body_raw, true );

	if ( isset( $body['access_token'] ) ) {
		// Cache for 7 hours (tokens valid for 8 hours per USPS docs)
		$expires_in = isset( $body['expires_in'] ) ? intval( $body['expires_in'] ) : 28800;
		$cache_time = max( 3600, $expires_in - 3600 ); // Cache for 1 hour less than expiry
		set_transient( 'wtcc_usps_oauth_token', $body['access_token'], $cache_time );
		return $body['access_token'];
	}

	// Log error details
	if ( isset( $body['error'] ) ) {
		error_log( 'USPS OAuth Error: ' . $body['error'] . ' - ' . ( isset( $body['error_description'] ) ? $body['error_description'] : '' ) );
	} else {
		error_log( 'USPS OAuth: No access_token in response. Status: ' . $status_code );
	}

	error_log( 'USPS OAuth: Failed to get access token' );
	return false;
}

/**
 * Calculate shipping cost using USPS API
 * 
 * @param string $service USPS service (e.g., 'Priority Mail', 'Ground Advantage')
 * @param float  $weight Weight in ounces
 * @param string $from_zip Origin ZIP code (your location)
 * @param string $to_zip Destination ZIP code
 * @param string $to_country Destination country (US only for domestic)
 * @param array  $dimensions Package dimensions (length, width, height in inches)
 * 
 * @return float|false Rate in dollars, or false on error
 */
function wtcc_shipping_usps_api_rate( $service, $weight, $from_zip, $to_zip, $to_country = 'US', $dimensions = array() ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return false; // No API configured, fall back to manual rates
	}

	// Get OAuth token
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		error_log( 'USPS API: OAuth token failed - falling back to manual rates' );
		return false; // OAuth failed, fall back to manual rates
	}

	// Cache rates for 4 hours to reduce API calls
	$cache_key = 'wtcc_usps_rate_' . md5( "$service|$weight|$from_zip|$to_zip|$to_country" );
	$cache_time_key = 'wtcc_usps_rate_time_' . md5( "$service|$weight|$from_zip|$to_zip|$to_country" );
	$cached_rate = get_transient( $cache_key );
	
	if ( false !== $cached_rate ) {
		return $cached_rate;
	}

	// Ensure weight is in valid format
	if ( $weight < 0.1 ) {
		$weight = 0.1; // USPS minimum
	}

	// Default dimensions if not provided
	if ( empty( $dimensions ) ) {
		$dimensions = array( 'length' => 12, 'width' => 12, 'height' => 6 );
	}

	try {
		// Build USPS API request based on service type
		if ( $to_country === 'US' ) {
			$rate = wtcc_shipping_usps_domestic_rate( $service, $weight, $from_zip, $to_zip, $credentials, $access_token, $dimensions );
		} else {
			$rate = wtcc_shipping_usps_international_rate( $service, $weight, $from_zip, $to_zip, $to_country, $credentials, $access_token, $dimensions );
		}

		if ( is_numeric( $rate ) && $rate >= 0 ) {
			set_transient( $cache_key, $rate, 4 * HOUR_IN_SECONDS );
			// Store timestamp of when rate was fetched
			set_transient( $cache_time_key, current_time( 'timestamp' ), 4 * HOUR_IN_SECONDS );
			return (float) $rate;
		}
	} catch ( Exception $e ) {
		error_log( 'USPS API Error: ' . $e->getMessage() );
	}

	return false; // Fall back to manual rates on error
}

/**
 * Get domestic USPS rate via API
 *
 * @param string $service Service group (first_class, ground, priority, express).
 * @param float  $weight Weight in ounces.
 * @param string $from_zip Origin ZIP.
 * @param string $to_zip Destination ZIP.
 * @param array  $credentials API credentials.
 * @param string $access_token OAuth token.
 * @param array  $dimensions Package dimensions in inches.
 * @return float|false Rate or false on error.
 */
function wtcc_shipping_usps_domestic_rate( $service, $weight, $from_zip, $to_zip, $credentials, $access_token, $dimensions = array() ) {
	// Service mapping for new API v3
	// FIRST-CLASS_PACKAGE_SERVICE is deprecated - use USPS_GROUND_ADVANTAGE
	$service_map = array(
		'first_class'     => 'USPS_GROUND_ADVANTAGE',
		'ground'          => 'USPS_GROUND_ADVANTAGE',
		'priority'        => 'PRIORITY_MAIL',
		'express'         => 'PRIORITY_MAIL_EXPRESS',
	);

	$mail_class = isset( $service_map[ $service ] ) ? $service_map[ $service ] : 'PRIORITY_MAIL';

	// Convert weight from ounces to pounds (API requires pounds)
	$total_weight = $weight / 16;
	if ( $total_weight < 0.0625 ) { // 1 oz minimum
		$total_weight = 0.0625;
	}

	// Use actual dimensions or defaults
	$length = isset( $dimensions['length'] ) ? (float) $dimensions['length'] : 12;
	$width  = isset( $dimensions['width'] ) ? (float) $dimensions['width'] : 12;
	$height = isset( $dimensions['height'] ) ? (float) $dimensions['height'] : 6;

	// Ensure minimum dimensions (1 inch each per USPS requirements)
	$length = max( 1, $length );
	$width  = max( 1, $width );
	$height = max( 1, $height );

	// Build JSON request for Domestic Prices API v3
	// Using SP (Single-Piece) rate indicator for retail pricing
	$request_body = array(
		'originZIPCode'               => substr( $from_zip, 0, 5 ),
		'destinationZIPCode'          => substr( $to_zip, 0, 5 ),
		'weight'                      => round( $total_weight, 4 ),
		'length'                      => round( $length, 1 ),
		'width'                       => round( $width, 1 ),
		'height'                      => round( $height, 1 ),
		'mailClass'                   => $mail_class,
		'processingCategory'          => 'MACHINABLE',
		'destinationEntryFacilityType' => 'NONE',
		'rateIndicator'               => 'SP', // Single-Piece for retail
		'priceType'                   => 'RETAIL',
	);

	return wtcc_shipping_usps_api_request( $request_body, 'domestic', $credentials, $access_token );
}

/**
 * Get international USPS rate via API
 *
 * @param string $service Service group.
 * @param float  $weight Weight in ounces.
 * @param string $from_zip Origin ZIP.
 * @param string $to_zip Destination postal code.
 * @param string $to_country Destination country code.
 * @param array  $credentials API credentials.
 * @param string $access_token OAuth token.
 * @param array  $dimensions Package dimensions in inches.
 * @return float|false Rate or false on error.
 */
function wtcc_shipping_usps_international_rate( $service, $weight, $from_zip, $to_zip, $to_country, $credentials, $access_token, $dimensions = array() ) {
	// Service mapping for international - per USPS API v3 spec
	$service_map = array(
		'first_class'     => 'FIRST-CLASS_PACKAGE_INTERNATIONAL_SERVICE',
		'ground'          => 'PRIORITY_MAIL_INTERNATIONAL',
		'priority'        => 'PRIORITY_MAIL_INTERNATIONAL',
		'express'         => 'PRIORITY_MAIL_EXPRESS_INTERNATIONAL',
	);

	$mail_class = isset( $service_map[ $service ] ) ? $service_map[ $service ] : 'PRIORITY_MAIL_INTERNATIONAL';

	// Convert weight from ounces to pounds (API requires pounds)
	$total_weight = $weight / 16;
	if ( $total_weight < 0.0625 ) { // 1 oz minimum
		$total_weight = 0.0625;
	}

	// Use actual dimensions or defaults
	$length = isset( $dimensions['length'] ) ? (float) $dimensions['length'] : 12;
	$width  = isset( $dimensions['width'] ) ? (float) $dimensions['width'] : 12;
	$height = isset( $dimensions['height'] ) ? (float) $dimensions['height'] : 6;

	// Ensure minimum dimensions
	$length = max( 1, $length );
	$width  = max( 1, $width );
	$height = max( 1, $height );

	// Build JSON request for International Prices API v3
	// Required fields per spec: originZIPCode, weight, length, width, height, 
	// mailClass, processingCategory, rateIndicator, destinationEntryFacilityType, priceType, destinationCountryCode
	$request_body = array(
		'originZIPCode'               => substr( $from_zip, 0, 5 ),
		'foreignPostalCode'           => ! empty( $to_zip ) ? $to_zip : '',
		'destinationCountryCode'      => strtoupper( substr( $to_country, 0, 2 ) ),
		'weight'                      => round( $total_weight, 4 ),
		'length'                      => round( $length, 1 ),
		'width'                       => round( $width, 1 ),
		'height'                      => round( $height, 1 ),
		'mailClass'                   => $mail_class,
		'processingCategory'          => 'MACHINABLE',
		'rateIndicator'               => 'SP', // Single Piece
		'destinationEntryFacilityType' => 'NONE',
		'priceType'                   => 'RETAIL',
	);

	return wtcc_shipping_usps_api_request( $request_body, 'international', $credentials, $access_token );
}

/**
 * Send request to USPS API and parse response
 */
function wtcc_shipping_usps_api_request( $request_body, $api_type, $credentials, $access_token ) {
	$endpoint = $credentials['api_endpoint'];
	
	// Build endpoint based on API type - using v3 Domestic/International Prices API
	if ( 'domestic' === $api_type ) {
		$endpoint .= '/prices/v3/base-rates/search';
	} elseif ( 'international' === $api_type ) {
		$endpoint .= '/international-prices/v3/base-rates/search';
	}

	// Log request for debugging (only in WP_DEBUG mode)
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'USPS API Request to: ' . $endpoint );
		error_log( 'USPS API Request body: ' . wp_json_encode( $request_body ) );
	}

	try {
		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body'      => wp_json_encode( $request_body ),
				'timeout'   => 15,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'USPS API Request Error: ' . $response->get_error_message() );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		// Log response for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'USPS API Response Code: ' . $status_code );
			error_log( 'USPS API Response: ' . substr( $body, 0, 500 ) );
		}

		$data = json_decode( $body, true );

		if ( ! $data ) {
			error_log( 'USPS API: Failed to parse JSON response' );
			return false;
		}

		// Check for API errors
		if ( isset( $data['error'] ) ) {
			$error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown error';
			error_log( 'USPS API Error: ' . $error_msg );
			return false;
		}

		// Check for errors array (different error format)
		if ( isset( $data['errors'] ) && is_array( $data['errors'] ) ) {
			foreach ( $data['errors'] as $err ) {
				error_log( 'USPS API Error: ' . ( isset( $err['message'] ) ? $err['message'] : wp_json_encode( $err ) ) );
			}
			return false;
		}

		// Handle HTTP error codes
		if ( $status_code >= 400 ) {
			error_log( 'USPS API HTTP Error: ' . $status_code . ' - ' . $body );
			return false;
		}

		// Extract rate from response - base-rates/search returns totalBasePrice
		if ( isset( $data['totalBasePrice'] ) ) {
			return (float) $data['totalBasePrice'];
		}
		
		// Handle rate-list response format
		if ( isset( $data['rateOptions'] ) && is_array( $data['rateOptions'] ) && ! empty( $data['rateOptions'] ) ) {
			// Return the first (lowest) rate option
			if ( isset( $data['rateOptions'][0]['totalBasePrice'] ) ) {
				return (float) $data['rateOptions'][0]['totalBasePrice'];
			}
		}

		// Handle rates array response
		if ( isset( $data['rates'] ) && is_array( $data['rates'] ) && ! empty( $data['rates'] ) ) {
			if ( isset( $data['rates'][0]['price'] ) ) {
				return (float) $data['rates'][0]['price'];
			}
		}

		error_log( 'USPS API: Could not extract price from response' );
		return false;
	} catch ( Exception $e ) {
		error_log( 'USPS API Exception: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Clear USPS rate cache (call on product save or settings update)
 */
function wtcc_shipping_clear_usps_cache() {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", '%wtcc_usps_rate_%' ) );
}

/**
 * Test USPS API connection
 * Validates credentials by obtaining OAuth token
 * 
 * @return true|WP_Error True on success, WP_Error on failure
 */
function wtcc_usps_test_connection() {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	// Check if credentials are configured
	if ( empty( $credentials['consumer_key'] ) || empty( $credentials['consumer_secret'] ) ) {
		return new WP_Error( 'missing_credentials', 'USPS API credentials not configured. Get them at https://developer.usps.com' );
	}

	// Clean credentials
	$credentials['consumer_key'] = preg_replace( '/\s+/', '', $credentials['consumer_key'] );
	$credentials['consumer_secret'] = preg_replace( '/\s+/', '', $credentials['consumer_secret'] );
	
	// Validate credential format
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return new WP_Error( 'invalid_credentials', 'USPS API credentials appear invalid (too short or incorrect format). Check they were copied correctly from USPS Developer Portal.' );
	}

	// Force a fresh OAuth token to test the connection
	delete_transient( 'wtcc_usps_oauth_token' ); // Clear any cached token
	$access_token = wtcc_shipping_get_oauth_token( $credentials, true );

	if ( ! $access_token ) {
		return new WP_Error( 'oauth_failed', 'Failed to obtain OAuth token from USPS. Verify your Consumer Key and Consumer Secret are correct. Check https://developer.usps.com > My Apps for your credentials.' );
	}

	// Test a simple API call to verify token works with Domestic Prices API
	$options = get_option( 'wtcc_usps_api_options', array() );
	$origin_zip = $options['origin_zip'] ?? '10001';
	$test_endpoint = $credentials['api_endpoint'] . '/prices/v3/base-rates/search';

	// Test request body following exact API spec
	$test_body = array(
		'originZIPCode'               => sanitize_text_field( substr( $origin_zip, 0, 5 ) ),
		'destinationZIPCode'          => '90210',
		'weight'                      => 1.0,
		'length'                      => 6.0,
		'width'                       => 4.0,
		'height'                      => 2.0,
		'mailClass'                   => 'USPS_GROUND_ADVANTAGE',
		'processingCategory'          => 'MACHINABLE',
		'rateIndicator'               => 'SP',
		'destinationEntryFacilityType' => 'NONE',
		'priceType'                   => 'RETAIL',
	);

	$response = wp_remote_post(
		$test_endpoint,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'      => wp_json_encode( $test_body ),
			'timeout'   => 15,
			'sslverify' => true,
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'api_error', 'API request failed: ' . $response->get_error_message() );
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	// Log for debugging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'USPS Test Connection - Status: ' . $status_code );
		error_log( 'USPS Test Connection - Response: ' . substr( $body, 0, 500 ) );
	}

	if ( $status_code >= 200 && $status_code < 300 ) {
		// Success - record it
		update_option( 'wtcc_last_usps_success', time() );
		delete_option( 'wtcc_last_usps_failure' );

		// Extract rate for feedback
		$rate = isset( $data['totalBasePrice'] ) ? $data['totalBasePrice'] : null;
		if ( $rate ) {
			return array(
				'success' => true,
				'message' => '✅ Connected! Test rate: $' . number_format( $rate, 2 ) . ' — Your credentials are valid and working.',
				'rate'    => $rate,
			);
		}
		return array(
			'success' => true,
			'message' => '✅ Connected! Your USPS API credentials are valid and working.',
		);
	} elseif ( $status_code === 401 ) {
		update_option( 'wtcc_last_usps_failure', time() );
		return new WP_Error( 'auth_error', '❌ Authentication failed (401). Your OAuth credentials were rejected by USPS. Double-check Consumer Key and Secret at https://developer.usps.com/apps' );
	} elseif ( $status_code === 403 ) {
		update_option( 'wtcc_last_usps_failure', time() );
		return new WP_Error( 'access_denied', '❌ Access denied (403). Your app may not have permission for the Domestic Prices API. Ensure your USPS app has the correct permissions enabled.' );
	} elseif ( $status_code === 400 ) {
		// Bad request - usually means API is connected but request format wrong
		$error_msg = '❌ Bad request (400)';
		if ( isset( $data['error']['message'] ) ) {
			$error_msg .= ': ' . $data['error']['message'];
		} elseif ( isset( $data['errors'][0]['message'] ) ) {
			$error_msg .= ': ' . $data['errors'][0]['message'];
		}
		update_option( 'wtcc_last_usps_failure', time() );
		return new WP_Error( 'bad_request', $error_msg );
	} else {
		$error_msg = "❌ HTTP $status_code";
		if ( isset( $data['error']['message'] ) ) {
			$error_msg .= ': ' . $data['error']['message'];
		}
		update_option( 'wtcc_last_usps_failure', time() );
		return new WP_Error( 'api_error', 'API returned error: ' . $error_msg );
	}
}
