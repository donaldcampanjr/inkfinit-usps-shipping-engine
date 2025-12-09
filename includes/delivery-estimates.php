<?php
/**
 * USPS Delivery Time Estimates
 * 
 * Fetches and displays estimated delivery dates from USPS API
 * This is a TOP REQUESTED FEATURE (13 votes on WooCommerce feature requests)
 * 
 * @package WTC_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get delivery estimate from USPS Service Standards API
 * 
 * @param string $from_zip Origin ZIP code.
 * @param string $to_zip Destination ZIP code.
 * @param string $mail_class USPS mail class.
 * @return array|false Delivery estimate data or false on error.
 */
function wtcc_get_delivery_estimate( $from_zip, $to_zip, $mail_class = 'USPS_GROUND_ADVANTAGE' ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( empty( $credentials['consumer_key'] ) || empty( $credentials['consumer_secret'] ) ) {
		return false;
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return false;
	}
	
	// Cache key for delivery estimates
	$cache_key = 'wtcc_delivery_est_' . md5( "$from_zip|$to_zip|$mail_class" );
	$cached = get_transient( $cache_key );
	
	if ( false !== $cached ) {
		return $cached;
	}
	
	// USPS Service Standards API endpoint
	$endpoint = $credentials['api_endpoint'] . '/service-standards/v3/estimates';
	
	$request_params = array(
		'originZIPCode'      => substr( $from_zip, 0, 5 ),
		'destinationZIPCode' => substr( $to_zip, 0, 5 ),
		'mailClass'          => $mail_class,
		'acceptanceDate'     => gmdate( 'Y-m-d' ), // Today
	);
	
	$url = add_query_arg( $request_params, $endpoint );
	
	$response = wp_remote_get(
		$url,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Accept'        => 'application/json',
			),
			'timeout'   => 10,
			'sslverify' => true,
		)
	);
	
	if ( is_wp_error( $response ) ) {
		error_log( 'USPS Delivery Estimate Error: ' . $response->get_error_message() );
		return false;
	}
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( ! $body || isset( $body['error'] ) ) {
		return false;
	}
	
	// Parse the response
	$estimate = array(
		'mail_class'           => $mail_class,
		'scheduled_delivery'   => isset( $body['scheduledDeliveryDate'] ) ? $body['scheduledDeliveryDate'] : null,
		'delivery_days'        => isset( $body['deliveryDays'] ) ? (int) $body['deliveryDays'] : null,
		'delivery_days_range'  => array(
			'min' => isset( $body['deliveryDaysMin'] ) ? (int) $body['deliveryDaysMin'] : null,
			'max' => isset( $body['deliveryDaysMax'] ) ? (int) $body['deliveryDaysMax'] : null,
		),
		'effective_acceptance' => isset( $body['effectiveAcceptanceDate'] ) ? $body['effectiveAcceptanceDate'] : gmdate( 'Y-m-d' ),
	);
	
	// Cache for 6 hours (estimates don't change frequently)
	set_transient( $cache_key, $estimate, 6 * HOUR_IN_SECONDS );
	
	return $estimate;
}

/**
 * Get delivery estimates for all shipping methods
 * 
 * @param string $to_zip Destination ZIP.
 * @return array Array of estimates by service.
 */
function wtcc_get_all_delivery_estimates( $to_zip ) {
	$from_zip = get_option( 'wtcc_origin_zip', '' );
	
	if ( empty( $from_zip ) || empty( $to_zip ) ) {
		return array();
	}
	
	$services = array(
		'ground'      => 'USPS_GROUND_ADVANTAGE',
		'priority'    => 'PRIORITY_MAIL',
		'express'     => 'PRIORITY_MAIL_EXPRESS',
	);
	
	$estimates = array();
	
	foreach ( $services as $key => $mail_class ) {
		$estimate = wtcc_get_delivery_estimate( $from_zip, $to_zip, $mail_class );
		if ( $estimate ) {
			$estimates[ $key ] = $estimate;
		}
	}
	
	return $estimates;
}

/**
 * Format delivery date for display
 * 
 * @param string $date Date string (Y-m-d format).
 * @return string Formatted date.
 */
function wtcc_format_delivery_date( $date ) {
	if ( empty( $date ) ) {
		return '';
	}
	
	$timestamp = strtotime( $date );
	if ( ! $timestamp ) {
		return '';
	}
	
	// Check if it's today, tomorrow, or later
	$today = strtotime( 'today' );
	$tomorrow = strtotime( 'tomorrow' );
	
	if ( $timestamp === $today ) {
		return __( 'Today', 'wtc-shipping' );
	} elseif ( $timestamp === $tomorrow ) {
		return __( 'Tomorrow', 'wtc-shipping' );
	} else {
		// Show day name for within a week, otherwise show date
		$days_away = ( $timestamp - $today ) / DAY_IN_SECONDS;
		
		if ( $days_away <= 7 ) {
			return wp_date( 'l, M j', $timestamp ); // "Monday, Dec 2"
		} else {
			return wp_date( 'M j', $timestamp ); // "Dec 2"
		}
	}
}

/**
 * Get delivery estimate text for display
 * 
 * @param array $estimate Estimate data from wtcc_get_delivery_estimate().
 * @return string Human-readable delivery estimate.
 */
function wtcc_get_delivery_text( $estimate ) {
	if ( empty( $estimate ) ) {
		return '';
	}
	
	// If we have a scheduled delivery date, use that
	if ( ! empty( $estimate['scheduled_delivery'] ) ) {
		$formatted = wtcc_format_delivery_date( $estimate['scheduled_delivery'] );
		if ( $formatted ) {
			return sprintf( __( 'Get it by %s', 'wtc-shipping' ), $formatted );
		}
	}
	
	// Otherwise use delivery days
	if ( ! empty( $estimate['delivery_days'] ) ) {
		$days = $estimate['delivery_days'];
		
		if ( $days === 1 ) {
			return __( 'Next-day delivery', 'wtc-shipping' );
		} elseif ( $days === 2 ) {
			return __( '2-day delivery', 'wtc-shipping' );
		} else {
			return sprintf( __( '%d-day delivery', 'wtc-shipping' ), $days );
		}
	}
	
	// Use range if available
	if ( ! empty( $estimate['delivery_days_range']['min'] ) && ! empty( $estimate['delivery_days_range']['max'] ) ) {
		$min = $estimate['delivery_days_range']['min'];
		$max = $estimate['delivery_days_range']['max'];
		
		if ( $min === $max ) {
			return sprintf( __( '%d-day delivery', 'wtc-shipping' ), $min );
		} else {
			return sprintf( __( '%d-%d business days', 'wtc-shipping' ), $min, $max );
		}
	}
	
	return '';
}

/**
 * Add delivery estimate to shipping rate label
 */
add_filter( 'woocommerce_cart_shipping_method_full_label', 'wtcc_add_delivery_estimate_to_label', 15, 2 );
function wtcc_add_delivery_estimate_to_label( $label, $method ) {
	// Only process our shipping methods
	if ( strpos( $method->get_id(), 'wtc_' ) !== 0 ) {
		return $label;
	}
	
	// Check if delivery estimates are enabled
	if ( get_option( 'wtcc_show_delivery_estimates', 'yes' ) !== 'yes' ) {
		return $label;
	}
	
	// Get destination ZIP
	$to_zip = '';
	if ( WC()->customer ) {
		$to_zip = WC()->customer->get_shipping_postcode();
	}
	
	if ( empty( $to_zip ) ) {
		return $label;
	}
	
	// Map method ID to service key
	$service_map = array(
		'wtc_ground'      => 'ground',
		'wtc_priority'    => 'priority',
		'wtc_express'     => 'express',
	);
	
	$base_method_id = explode( ':', $method->get_id() )[0];
	
	if ( ! isset( $service_map[ $base_method_id ] ) ) {
		return $label;
	}
	
	$service_key = $service_map[ $base_method_id ];
	
	// Get cached estimates or fetch new ones
	$cache_key = 'wtcc_all_estimates_' . md5( $to_zip );
	$all_estimates = get_transient( $cache_key );
	
	if ( false === $all_estimates ) {
		$all_estimates = wtcc_get_all_delivery_estimates( $to_zip );
		set_transient( $cache_key, $all_estimates, 6 * HOUR_IN_SECONDS );
	}
	
	if ( isset( $all_estimates[ $service_key ] ) ) {
		$delivery_text = wtcc_get_delivery_text( $all_estimates[ $service_key ] );
		
		if ( $delivery_text ) {
			$label .= '<div class="wtcc-delivery-estimate">';
			$label .= '<span class="dashicons dashicons-clock"></span>';
			$label .= '<span class="wtcc-delivery-estimate-label">' . esc_html( $delivery_text ) . '</span>';
			$label .= '</div>';
		}
	}
	
	return $label;
}

/**
 * Add delivery estimate setting to USPS API settings
 */
add_filter( 'wtcc_usps_api_settings', 'wtcc_add_delivery_estimate_setting' );
function wtcc_add_delivery_estimate_setting( $settings ) {
	$settings['show_delivery_estimates'] = array(
		'title'       => __( 'Show Delivery Estimates', 'wtc-shipping' ),
		'type'        => 'checkbox',
		'label'       => __( 'Display estimated delivery dates at checkout', 'wtc-shipping' ),
		'description' => __( 'Shows "Get it by [date]" under each shipping option. Requires valid USPS API credentials.', 'wtc-shipping' ),
		'default'     => 'yes',
	);
	
	return $settings;
}

/**
 * AJAX handler for getting delivery estimates
 */
add_action( 'wp_ajax_wtcc_get_delivery_estimate', 'wtcc_ajax_get_delivery_estimate' );
add_action( 'wp_ajax_nopriv_wtcc_get_delivery_estimate', 'wtcc_ajax_get_delivery_estimate' );
function wtcc_ajax_get_delivery_estimate() {
	check_ajax_referer( 'wtcc_delivery_estimate', 'nonce' );
	
	$to_zip = isset( $_POST['zip'] ) ? sanitize_text_field( wp_unslash( $_POST['zip'] ) ) : '';
	
	if ( empty( $to_zip ) ) {
		wp_send_json_error( array( 'message' => 'ZIP code required' ) );
	}
	
	$estimates = wtcc_get_all_delivery_estimates( $to_zip );
	
	if ( empty( $estimates ) ) {
		wp_send_json_error( array( 'message' => 'Unable to get delivery estimates' ) );
	}
	
	$formatted = array();
	foreach ( $estimates as $service => $estimate ) {
		$formatted[ $service ] = array(
			'text' => wtcc_get_delivery_text( $estimate ),
			'date' => isset( $estimate['scheduled_delivery'] ) ? wtcc_format_delivery_date( $estimate['scheduled_delivery'] ) : '',
			'days' => isset( $estimate['delivery_days'] ) ? $estimate['delivery_days'] : null,
		);
	}
	
	wp_send_json_success( $formatted );
}

/**
 * Initialize default setting for delivery estimates
 */
add_action( 'init', 'wtcc_init_delivery_estimate_settings' );
function wtcc_init_delivery_estimate_settings() {
	if ( false === get_option( 'wtcc_show_delivery_estimates' ) ) {
		update_option( 'wtcc_show_delivery_estimates', 'yes' );
	}
}
