<?php
/**
 * USPS Enhanced Features
 * Additional USPS API integrations for band/shop owners
 * 
 * Features:
 * - Address Validation & Standardization
 * - Service Standards (Delivery Estimates)
 * - Carrier Pickup Scheduling
 * - Package Tracking Lookup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ==========================================================================
 * ADDRESS VALIDATION
 * Validates and standardizes addresses before shipping
 * ==========================================================================
 */

/**
 * Validate and standardize a US address via USPS API
 * 
 * @param array $address Array with address1, address2, city, state, zip
 * @return array|false Standardized address or false on error
 */
function wtcc_usps_validate_address( $address ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return false;
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return false;
	}
	
	// Build request
	$request_body = array(
		'streetAddress'     => $address['address1'] ?? '',
		'secondaryAddress'  => $address['address2'] ?? '',
		'city'              => $address['city'] ?? '',
		'state'             => $address['state'] ?? '',
		'ZIPCode'           => substr( $address['zip'] ?? '', 0, 5 ),
	);
	
	$response = wp_safe_remote_post(
		$credentials['api_endpoint'] . '/addresses/v3/address',
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $request_body ),
			'timeout' => 10,
		)
	);
	
	if ( is_wp_error( $response ) ) {
		return false;
	}
	
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( isset( $data['address'] ) ) {
		return array(
			'address1'  => $data['address']['streetAddress'] ?? '',
			'address2'  => $data['address']['secondaryAddress'] ?? '',
			'city'      => $data['address']['city'] ?? '',
			'state'     => $data['address']['state'] ?? '',
			'zip'       => $data['address']['ZIPCode'] ?? '',
			'zip4'      => $data['address']['ZIPPlus4'] ?? '',
			'validated' => true,
		);
	}
	
	return false;
}

/**
 * Validate address on checkout
 */
add_action( 'woocommerce_after_checkout_validation', 'wtcc_validate_shipping_address', 10, 2 );
function wtcc_validate_shipping_address( $data, $errors ) {
	// Only validate US addresses
	if ( $data['shipping_country'] !== 'US' && $data['billing_country'] !== 'US' ) {
		return;
	}
	
	// Skip if USPS API not configured
	$credentials = wtcc_shipping_get_usps_credentials();
	if ( empty( $credentials['consumer_key'] ) ) {
		return;
	}
	
	// Check if address validation is enabled
	if ( ! wtcc_get_features_option( 'enable_address_validation', false ) ) {
		return;
	}
	
	$address = array(
		'address1' => $data['shipping_address_1'] ?: $data['billing_address_1'],
		'address2' => $data['shipping_address_2'] ?: $data['billing_address_2'],
		'city'     => $data['shipping_city'] ?: $data['billing_city'],
		'state'    => $data['shipping_state'] ?: $data['billing_state'],
		'zip'      => $data['shipping_postcode'] ?: $data['billing_postcode'],
	);
	
	$validated = wtcc_usps_validate_address( $address );
	
	if ( $validated === false ) {
		// Could not validate - add warning but don't block
		wc_add_notice( __( 'We could not verify your shipping address with USPS. Please double-check it before submitting.', 'wtc-shipping' ), 'notice' );
	}
}


/**
 * ==========================================================================
 * SERVICE STANDARDS (Delivery Estimates)
 * Shows customers expected delivery dates
 * ==========================================================================
 */

/**
 * Get service standards (delivery time) for a shipment
 * 
 * @param string $from_zip Origin ZIP
 * @param string $to_zip Destination ZIP
 * @param string $mail_class USPS mail class
 * @return array|false Delivery estimate or false
 */
function wtcc_usps_get_service_standards( $from_zip, $to_zip, $mail_class = 'PRIORITY_MAIL' ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return false;
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return false;
	}
	
	// Cache key
	$cache_key = 'wtcc_standards_' . md5( "$from_zip|$to_zip|$mail_class" );
	$cached = get_transient( $cache_key );
	if ( $cached !== false ) {
		return $cached;
	}
	
	$endpoint = $credentials['api_endpoint'] . '/service-standards/v3/estimates';
	$params = array(
		'originZIPCode'      => substr( $from_zip, 0, 5 ),
		'destinationZIPCode' => substr( $to_zip, 0, 5 ),
		'mailClass'          => $mail_class,
		'acceptanceDate'     => gmdate( 'Y-m-d' ),
	);
	
	$response = wp_safe_remote_get(
		add_query_arg( $params, $endpoint ),
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout' => 10,
		)
	);
	
	if ( is_wp_error( $response ) ) {
		return false;
	}
	
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( isset( $data['serviceStandard'] ) || isset( $data['deliveryDate'] ) ) {
		$result = array(
			'delivery_days'   => $data['serviceStandard'] ?? null,
			'delivery_date'   => $data['deliveryDate'] ?? null,
			'commitment_name' => $data['commitmentName'] ?? null,
		);
		
		set_transient( $cache_key, $result, 12 * HOUR_IN_SECONDS );
		return $result;
	}
	
	return false;
}

// Note: Delivery estimate display is now handled in delivery-estimates.php


/**
 * ==========================================================================
 * CARRIER PICKUP SCHEDULING
 * Schedule USPS to pick up packages from your location
 * NOTE: Full implementation is in includes/usps-pickup-api.php
 * ==========================================================================
 */

/**
 * ==========================================================================
 * CARRIER PICKUP API v3
 * Schedule pickups for packages
 * NOTE: Full implementation is in includes/usps-pickup-api.php
 * ==========================================================================
 */


/**
 * ==========================================================================
 * PACKAGE TRACKING LOOKUP
 * Real-time tracking status from USPS
 * ==========================================================================
 */

/**
 * Get tracking information for a package
 * 
 * @param string $tracking_number USPS tracking number
 * @return array|false Tracking info or false
 */
function wtcc_usps_get_tracking( $tracking_number ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return false;
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return false;
	}
	
	// Use v3.2 tracking API
	$response = wp_safe_remote_get(
		$credentials['api_endpoint'] . '/tracking/v3/tracking/' . urlencode( $tracking_number ),
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout' => 10,
		)
	);
	
	if ( is_wp_error( $response ) ) {
		return false;
	}
	
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( isset( $data['trackingNumber'] ) ) {
		return array(
			'tracking_number' => $data['trackingNumber'],
			'status'          => $data['statusCategory'] ?? 'Unknown',
			'status_detail'   => $data['status'] ?? '',
			'delivery_date'   => $data['actualDeliveryDate'] ?? $data['expectedDeliveryDate'] ?? null,
			'location'        => isset( $data['currentLocation'] ) ? 
				( $data['currentLocation']['city'] . ', ' . $data['currentLocation']['state'] ) : '',
			'events'          => $data['trackingEvents'] ?? array(),
		);
	}
	
	return false;
}

/**
 * AJAX handler for tracking lookup
 */
add_action( 'wp_ajax_wtcc_lookup_tracking', 'wtcc_ajax_lookup_tracking' );
add_action( 'wp_ajax_nopriv_wtcc_lookup_tracking', 'wtcc_ajax_lookup_tracking' );
function wtcc_ajax_lookup_tracking() {
	$tracking = sanitize_text_field( $_POST['tracking_number'] ?? '' );
	
	if ( ! $tracking ) {
		wp_send_json_error( 'No tracking number provided' );
	}
	
	$result = wtcc_usps_get_tracking( $tracking );
	
	if ( $result ) {
		wp_send_json_success( $result );
	} else {
		wp_send_json_error( 'Could not retrieve tracking information' );
	}
}


function wtcc_get_features_option( $key, $default = false ) {
    $options = get_option( 'wtcc_features_options', array(
        'show_delivery_estimates' => true,
        'enable_address_validation' => false,
    ) );
    return $options[ $key ] ?? $default;
}

/**
 * ==========================================================================
 * SETTINGS FOR ENHANCED FEATURES
 * ==========================================================================
 */

// The settings are now handled in includes/admin-page-features.php via the Settings API.
// The functions wtcc_enhanced_features_settings() and wtcc_save_enhanced_features_settings() have been removed.


/**
 * ==========================================================================
 * DOMESTIC LABELS API v3
 * Create actual shipping labels with barcodes
 * NOTE: Full implementation is in includes/usps-label-api.php
 * ==========================================================================
 */

/**
 * ==========================================================================
 * INTERNATIONAL LABELS API v3
 * Create international shipping labels with customs forms
 * NOTE: Full implementation is in includes/usps-label-api.php
 * ==========================================================================
 */

/**
 * ==========================================================================
 * USPS LOCATIONS API v3
 * Find nearest USPS post offices and drop-off locations
 * ==========================================================================
 */

/**
 * Find nearest USPS locations for drop-off
 * 
 * @param string $zip ZIP code to search near
 * @param string $type Location type: PO (post office), COLLECTION, APO, etc.
 * @return array List of nearby locations
 */
function wtcc_usps_find_locations( $zip, $type = 'PO' ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return array();
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return array();
	}
	
	$response = wp_remote_get( 'https://api.usps.com/locations/v3/dropoff-locations?' . http_build_query( array(
		'mailClass' => 'USPS_GROUND_ADVANTAGE',
		'destinationZIPCode' => substr( $zip, 0, 5 ),
	) ), array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $access_token,
			'Accept'        => 'application/json',
		),
		'timeout' => 15,
	) );
	
	if ( is_wp_error( $response ) ) {
		return array();
	}
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( empty( $body['locations'] ) ) {
		return array();
	}
	
	$locations = array();
	foreach ( $body['locations'] as $loc ) {
		$locations[] = array(
			'name'          => $loc['facilityName'] ?? '',
			'address'       => sprintf(
				'%s, %s, %s %s',
				$loc['streetAddress'] ?? '',
				$loc['city'] ?? '',
				$loc['state'] ?? '',
				$loc['ZIPCode'] ?? ''
			),
			'phone'         => $loc['phone'] ?? '',
			'hours'         => $loc['hours'] ?? array(),
			'services'      => $loc['services'] ?? array(),
			'has_self_service_kiosk' => $loc['hasSelfServiceKiosk'] ?? false,
			'distance'      => $loc['distance'] ?? '',
		);
	}
	
	return $locations;
}

/**
 * REST API endpoint for finding locations
 */
add_action( 'rest_api_init', 'wtcc_register_locations_endpoint' );
function wtcc_register_locations_endpoint() {
	register_rest_route( 'wtc-shipping/v1', '/locations/(?P<zip>\d{5})', array(
		'methods'             => 'GET',
		'callback'            => 'wtcc_rest_find_locations',
		'permission_callback' => '__return_true',
		'args'                => array(
			'zip' => array(
				'required' => true,
				'validate_callback' => function( $param ) {
					return preg_match( '/^\d{5}$/', $param );
				},
			),
		),
	) );
}

function wtcc_rest_find_locations( $request ) {
	$locations = wtcc_usps_find_locations( $request['zip'] );
	return rest_ensure_response( $locations );
}

/**
 * ==========================================================================
 * SCAN FORMS API v3
 * Create manifest for multiple shipments (bulk shipping)
 * ==========================================================================
 */

/**
 * Create a SCAN form to manifest multiple shipments
 * 
 * @param array $tracking_numbers Array of tracking numbers to include
 * @param array $from_address Shipper address
 * @return array|WP_Error SCAN form data or error
 */
function wtcc_usps_create_scan_form( $tracking_numbers, $from_address ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return new WP_Error( 'not_connected', __( 'USPS API not connected', 'wtc-shipping' ) );
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return new WP_Error( 'auth_failed', __( 'Failed to authenticate with USPS', 'wtc-shipping' ) );
	}
	
	$request_body = array(
		'form' => 'SCAN',
		'imageType' => 'PDF',
		'mailingDate' => gmdate( 'Y-m-d' ),
		'overwriteMailingDate' => false,
		'entryFacilityZIPCode' => substr( $from_address['postcode'] ?? '', 0, 5 ),
		'destinationEntryFacilityType' => 'NONE',
		'shipmentAddress' => array(
			'firstName'       => $from_address['first_name'] ?? '',
			'lastName'        => $from_address['last_name'] ?? '',
			'firm'            => $from_address['company'] ?? '',
			'streetAddress'   => $from_address['address_1'] ?? '',
			'city'            => $from_address['city'] ?? '',
			'state'           => $from_address['state'] ?? '',
			'ZIPCode'         => substr( $from_address['postcode'] ?? '', 0, 5 ),
		),
		'trackingNumbers' => $tracking_numbers,
	);
	
	$response = wp_remote_post( 'https://api.usps.com/scan-forms/v3/scan-form', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $access_token,
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
		),
		'body'    => wp_json_encode( $request_body ),
		'timeout' => 30,
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$code = wp_remote_retrieve_response_code( $response );
	
	if ( $code !== 200 && $code !== 201 ) {
		return new WP_Error( 'scan_error', $body['message'] ?? __( 'Failed to create SCAN form', 'wtc-shipping' ) );
	}
	
	return array(
		'scan_form_image' => $body['SCANFormImage'] ?? '',
		'scan_form_number' => $body['SCANFormNumber'] ?? '',
		'manifested_count' => count( $tracking_numbers ),
	);
}

/**
 * ==========================================================================
 * SHIPPING OPTIONS API v3
 * Get ALL shipping options with prices in ONE call (most efficient!)
 * ==========================================================================
 */

/**
 * Get all shipping options with pricing in a single call
 * This is the MOST EFFICIENT way to get rates - replaces multiple API calls
 * 
 * @param array $package Package details
 * @return array All available shipping options with prices
 */
function wtcc_usps_get_all_shipping_options( $package ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return array();
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return array();
	}
	
	// Build request per Shipping Options v3 API spec
	$request_body = array(
		'originZIPCode'      => substr( $package['origin_zip'] ?? '', 0, 5 ),
		'destinationZIPCode' => substr( $package['destination_zip'] ?? '', 0, 5 ),
		'destinationEntryFacilityType' => 'NONE',
		'packageDescription' => array(
			'weight'         => floatval( $package['weight'] ?? 1 ),
			'length'         => floatval( $package['length'] ?? 6 ),
			'width'          => floatval( $package['width'] ?? 4 ),
			'height'         => floatval( $package['height'] ?? 2 ),
			'mailClass'      => 'ALL', // Get all available classes
			'rateIndicator'  => 'SP',
			'processingCategory' => 'MACHINABLE',
		),
		'pricingOptions' => array(
			array(
				'priceType'   => 'RETAIL',
				'mailingDate' => gmdate( 'Y-m-d' ),
			),
		),
	);
	
	// Correct endpoint per USPS Shipping Options v3 API spec
	$response = wp_remote_post( $credentials['api_endpoint'] . '/options/search', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $access_token,
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
		),
		'body'    => wp_json_encode( $request_body ),
		'timeout' => 15,
	) );
	
	if ( is_wp_error( $response ) ) {
		return array();
	}
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$code = wp_remote_retrieve_response_code( $response );
	
	// Check for API errors
	if ( $code !== 200 ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'USPS Shipping Options v3 API Error: ' . wp_json_encode( $body ) );
		}
		return array();
	}
	
	// Per v3 spec: response is { originZIPCode, destinationZIPCode, pricingOptions[] }
	if ( empty( $body['pricingOptions'] ) ) {
		return array();
	}
	
	$options = array();
	foreach ( $body['pricingOptions'] as $pricing_option ) {
		// Each pricingOption contains rates array
		if ( empty( $pricing_option['rates'] ) ) {
			continue;
		}
		
		foreach ( $pricing_option['rates'] as $rate ) {
			$options[] = array(
				'mail_class'       => $rate['mailClass'] ?? '',
				'mail_class_name'  => wtcc_get_mail_class_name( $rate['mailClass'] ?? '' ),
				'price'            => floatval( $rate['price'] ?? 0 ),
				'zone'             => $rate['zone'] ?? '',
				'commitment'       => array(
					'name'           => $rate['serviceStandardMessage'] ?? '',
					'delivery_date'  => $rate['commitmentDate'] ?? '',
				),
				'extra_services'   => $rate['extraServices'] ?? array(),
				'raw_rate_data'    => $rate,
			);
		}
	}
	
	return $options;
}

/**
 * ==========================================================================
 * TRACKING SUBSCRIPTIONS API v3.2
 * Subscribe to real-time tracking updates via webhooks
 * ==========================================================================
 */

/**
 * Subscribe to tracking updates for a package
 * USPS will POST updates to your webhook URL when package status changes
 * 
 * @param string $tracking_number Tracking number to subscribe to
 * @param string $webhook_url URL to receive tracking notifications
 * @return array|WP_Error Subscription data or error
 */
function wtcc_usps_subscribe_tracking( $tracking_number, $webhook_url = null ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return new WP_Error( 'not_connected', __( 'USPS API not connected', 'wtc-shipping' ) );
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return new WP_Error( 'auth_failed', __( 'Failed to authenticate with USPS', 'wtc-shipping' ) );
	}
	
	// Default webhook URL is our REST endpoint
	if ( ! $webhook_url ) {
		$webhook_url = rest_url( 'wtc-shipping/v1/tracking-webhook' );
	}
	
	$request_body = array(
		'trackingNumber' => $tracking_number,
		'callbackURL'    => $webhook_url,
	);
	
	$response = wp_remote_post( 'https://api.usps.com/subscriptions-tracking/v3/subscriptions', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $access_token,
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
		),
		'body'    => wp_json_encode( $request_body ),
		'timeout' => 15,
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$code = wp_remote_retrieve_response_code( $response );
	
	if ( $code !== 200 && $code !== 201 ) {
		return new WP_Error( 'subscription_error', $body['message'] ?? __( 'Failed to subscribe to tracking', 'wtc-shipping' ) );
	}
	
	return array(
		'subscription_id' => $body['subscriptionId'] ?? '',
		'tracking_number' => $tracking_number,
		'status'          => 'active',
	);
}

/**
 * Webhook endpoint to receive tracking updates from USPS
 */
add_action( 'rest_api_init', 'wtcc_register_tracking_webhook' );
function wtcc_register_tracking_webhook() {
	register_rest_route( 'wtc-shipping/v1', '/tracking-webhook', array(
		'methods'             => 'POST',
		'callback'            => 'wtcc_handle_tracking_webhook',
		'permission_callback' => '__return_true', // USPS needs public access
	) );
}

function wtcc_handle_tracking_webhook( $request ) {
	$data = $request->get_json_params();
	
	if ( empty( $data['trackingNumber'] ) ) {
		return new WP_Error( 'invalid_data', 'Missing tracking number', array( 'status' => 400 ) );
	}
	
	$tracking_number = sanitize_text_field( $data['trackingNumber'] );
	$event_type      = sanitize_text_field( $data['eventType'] ?? '' );
	$event_time      = sanitize_text_field( $data['eventTimestamp'] ?? '' );
	$event_city      = sanitize_text_field( $data['eventCity'] ?? '' );
	$event_state     = sanitize_text_field( $data['eventState'] ?? '' );
	$status_summary  = sanitize_text_field( $data['statusSummary'] ?? '' );
	
	// Find order with this tracking number
	$orders = wc_get_orders( array(
		'meta_key'   => '_wtcc_tracking_number',
		'meta_value' => $tracking_number,
		'limit'      => 1,
	) );
	
	if ( ! empty( $orders ) ) {
		$order = $orders[0];
		
		// Update order meta with latest tracking info
		$order->update_meta_data( '_wtcc_tracking_status', $status_summary );
		$order->update_meta_data( '_wtcc_tracking_last_update', current_time( 'mysql' ) );
		$order->update_meta_data( '_wtcc_tracking_location', $event_city . ', ' . $event_state );
		$order->save();
		
		// Add order note
		$order->add_order_note( sprintf(
			/* translators: 1: status, 2: location, 3: time */
			__( 'USPS Tracking Update: %1$s at %2$s (%3$s)', 'wtc-shipping' ),
			$status_summary,
			$event_city . ', ' . $event_state,
			$event_time
		) );
		
		// Check if delivered
		if ( stripos( $event_type, 'DELIVERED' ) !== false ) {
			$order->update_status( 'completed', __( 'Package delivered per USPS tracking.', 'wtc-shipping' ) );
		}
		
		// Fire action for other plugins to hook into
		do_action( 'wtcc_tracking_update_received', $order, $data );
	}
	
	// Log the webhook
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Inkfinit Shipping - Tracking webhook received: ' . wp_json_encode( $data ) );
	}
	
	return rest_ensure_response( array( 'received' => true ) );
}

/**
 * ==========================================================================
 * INTERNATIONAL SERVICE STANDARDS API v3
 * Get delivery estimates for international shipments
 * ==========================================================================
 */

/**
 * Get service standards for international shipments
 * 
 * @param string $destination_country Two-letter country code
 * @param string $mail_class Mail class (PRIORITY_MAIL_INTERNATIONAL, etc.)
 * @return array Service standard information
 */
function wtcc_usps_get_international_service_standards( $destination_country, $mail_class = 'PRIORITY_MAIL_INTERNATIONAL' ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return array();
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return array();
	}
	
	$response = wp_remote_get( 'https://api.usps.com/international-service-standards/v3/estimates?' . http_build_query( array(
		'destinationCountryCode' => strtoupper( $destination_country ),
		'mailClass'              => $mail_class,
	) ), array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $access_token,
			'Accept'        => 'application/json',
		),
		'timeout' => 15,
	) );
	
	if ( is_wp_error( $response ) ) {
		return array();
	}
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	
	return array(
		'destination_country' => $destination_country,
		'mail_class'          => $mail_class,
		'delivery_message'    => $body['serviceStandardMessage'] ?? '',
		'min_days'            => $body['serviceStandardDaysMin'] ?? '',
		'max_days'            => $body['serviceStandardDaysMax'] ?? '',
	);
}

/**
 * ==========================================================================
 * PRICE ADJUSTMENTS API v3
 * Track pricing adjustments/discrepancies for shipped packages
 * ==========================================================================
 */

/**
 * Get pricing adjustments for a tracking number
 * Useful for understanding why you were charged more/less than expected
 * 
 * @param string $tracking_number Package tracking number
 * @return array|WP_Error Adjustment data or error
 */
function wtcc_usps_get_adjustments( $tracking_number ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return new WP_Error( 'not_connected', __( 'USPS API not connected', 'wtc-shipping' ) );
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return new WP_Error( 'auth_failed', __( 'Failed to authenticate with USPS', 'wtc-shipping' ) );
	}
	
	$response = wp_remote_get( 'https://api.usps.com/adjustments/v3/adjustments?' . http_build_query( array(
		'trackingNumber' => $tracking_number,
	) ), array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $access_token,
			'Accept'        => 'application/json',
		),
		'timeout' => 15,
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$code = wp_remote_retrieve_response_code( $response );
	
	if ( $code !== 200 ) {
		return new WP_Error( 'adjustment_error', $body['message'] ?? __( 'Failed to get adjustments', 'wtc-shipping' ) );
	}
	
	if ( empty( $body['adjustments'] ) ) {
		return array( 'adjustments' => array(), 'message' => __( 'No adjustments found', 'wtc-shipping' ) );
	}
	
	$adjustments = array();
	foreach ( $body['adjustments'] as $adj ) {
		$adjustments[] = array(
			'reason'           => $adj['adjustmentReason'] ?? '',
			'original_postage' => floatval( $adj['originalPostage'] ?? 0 ),
			'adjusted_postage' => floatval( $adj['adjustedPostage'] ?? 0 ),
			'difference'       => floatval( $adj['postageDifference'] ?? 0 ),
			'date'             => $adj['adjustmentDate'] ?? '',
		);
	}
	
	return array( 'adjustments' => $adjustments );
}

/**
 * ==========================================================================
 * WooCommerce ORDER INTEGRATION
 * Auto-create labels, subscribe to tracking when order ships
 * ==========================================================================
 */

/**
 * Add "Create USPS Label" button to order actions
 */
add_filter( 'woocommerce_order_actions', 'wtcc_add_create_label_action' );
function wtcc_add_create_label_action( $actions ) {
	$actions['wtcc_create_usps_label'] = __( 'Create USPS Shipping Label', 'wtc-shipping' );
	$actions['wtcc_create_scan_form']  = __( 'Create SCAN Form (Manifest)', 'wtc-shipping' );
	return $actions;
}

/**
 * Handle create label action
 */
add_action( 'woocommerce_order_action_wtcc_create_usps_label', 'wtcc_handle_create_label_action' );
function wtcc_handle_create_label_action( $order ) {
	// Get store address
	$from = array(
		'first_name' => get_option( 'woocommerce_store_address', '' ) ? '' : 'Store',
		'last_name'  => '',
		'company'    => get_bloginfo( 'name' ),
		'address_1'  => get_option( 'woocommerce_store_address', '' ),
		'address_2'  => get_option( 'woocommerce_store_address_2', '' ),
		'city'       => get_option( 'woocommerce_store_city', '' ),
		'state'      => get_option( 'woocommerce_default_country', 'US:CA' ),
		'postcode'   => get_option( 'woocommerce_store_postcode', '' ),
		'phone'      => '',
		'email'      => get_option( 'admin_email' ),
	);
	
	// Parse state from country
	$location = explode( ':', $from['state'] );
	$from['state'] = $location[1] ?? $location[0];
	
	// Get shipping address
	$to = array(
		'first_name' => $order->get_shipping_first_name(),
		'last_name'  => $order->get_shipping_last_name(),
		'company'    => $order->get_shipping_company(),
		'address_1'  => $order->get_shipping_address_1(),
		'address_2'  => $order->get_shipping_address_2(),
		'city'       => $order->get_shipping_city(),
		'state'      => $order->get_shipping_state(),
		'postcode'   => $order->get_shipping_postcode(),
		'country'    => $order->get_shipping_country(),
		'phone'      => $order->get_billing_phone(),
		'email'      => $order->get_billing_email(),
	);
	
	// Calculate total weight
	$total_weight = 0;
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( $product && $product->get_weight() ) {
			$total_weight += floatval( $product->get_weight() ) * $item->get_quantity();
		}
	}
	$total_weight = max( $total_weight, 0.5 ); // Minimum 0.5 lb
	
	// Determine if domestic or international
	$is_international = $to['country'] !== 'US';
	
	$shipment = array(
		'from'       => $from,
		'to'         => $to,
		'weight'     => $total_weight,
		'length'     => 10,
		'width'      => 8,
		'height'     => 4,
		'mail_class' => $is_international ? 'PRIORITY_MAIL_INTERNATIONAL' : 'USPS_GROUND_ADVANTAGE',
	);
	
	// Add customs info for international
	if ( $is_international ) {
		$customs_items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$customs_items[] = array(
				'description'    => substr( $item->get_name(), 0, 50 ),
				'quantity'       => $item->get_quantity(),
				'value'          => $item->get_total() / $item->get_quantity(),
				'weight'         => $product ? floatval( $product->get_weight() ) : 0.5,
				'origin_country' => 'US',
			);
		}
		$shipment['customs_items'] = $customs_items;
		$shipment['content_type'] = 'MERCHANDISE';
		
		$result = wtcc_usps_create_international_label( $shipment );
	} else {
		$result = wtcc_usps_create_domestic_label( $shipment );
	}
	
	if ( is_wp_error( $result ) ) {
		$order->add_order_note( sprintf(
			/* translators: %s: error message */
			__( 'Failed to create USPS label: %s', 'wtc-shipping' ),
			$result->get_error_message()
		) );
		return;
	}
	
	// Save label data to order
	$order->update_meta_data( '_wtcc_tracking_number', $result['tracking_number'] );
	$order->update_meta_data( '_wtcc_label_image', $result['label_image'] );
	$order->update_meta_data( '_wtcc_postage_paid', $result['postage'] );
	$order->update_meta_data( '_wtcc_label_created', current_time( 'mysql' ) );
	$order->save();
	
	// Subscribe to tracking updates
	wtcc_usps_subscribe_tracking( $result['tracking_number'] );
	
	// Add success note
	$order->add_order_note( sprintf(
		/* translators: 1: tracking number, 2: postage amount */
		__( 'USPS Label created! Tracking: %1$s | Postage: $%2$s', 'wtc-shipping' ),
		$result['tracking_number'],
		number_format( $result['postage'], 2 )
	) );
	
	// Fire action for other plugins
	do_action( 'wtcc_label_created', $order, $result );
}

/**
 * ==========================================================================
 * ADMIN UI: LABEL PRINTING META BOX
 * ==========================================================================
 */

add_action( 'add_meta_boxes', 'wtcc_add_label_meta_box' );
function wtcc_add_label_meta_box() {
	$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) 
		&& wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';
	
	add_meta_box(
		'wtcc_shipping_label',
		__( 'USPS Shipping Label', 'wtc-shipping' ),
		'wtcc_render_label_meta_box',
		$screen,
		'side',
		'high'
	);
}

function wtcc_render_label_meta_box( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	
	if ( ! $order ) {
		return;
	}
	
	$tracking_number = $order->get_meta( '_wtcc_tracking_number' );
	$label_image     = $order->get_meta( '_wtcc_label_image' );
	$postage         = $order->get_meta( '_wtcc_postage_paid' );
	$tracking_status = $order->get_meta( '_wtcc_tracking_status' );
	
	if ( $tracking_number ) {
		?>
		<div class="wtcc-label-info">
			<p><strong><?php esc_html_e( 'Tracking:', 'wtc-shipping' ); ?></strong><br>
				<a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=<?php echo esc_attr( $tracking_number ); ?>" target="_blank">
					<?php echo esc_html( $tracking_number ); ?>
				</a>
			</p>
			
			<?php if ( $tracking_status ) : ?>
				<p><strong><?php esc_html_e( 'Status:', 'wtc-shipping' ); ?></strong><br>
					<?php echo esc_html( $tracking_status ); ?>
				</p>
			<?php endif; ?>
			
			<?php if ( $postage ) : ?>
				<p><strong><?php esc_html_e( 'Postage:', 'wtc-shipping' ); ?></strong> $<?php echo esc_html( number_format( $postage, 2 ) ); ?></p>
			<?php endif; ?>
			
			<?php if ( $label_image ) : ?>
				<p>
					<a href="data:application/pdf;base64,<?php echo esc_attr( $label_image ); ?>" 
					   download="label-<?php echo esc_attr( $order->get_id() ); ?>.pdf" 
					   class="button button-primary">
						<?php esc_html_e( 'Download Label', 'wtc-shipping' ); ?>
					</a>
				</p>
			<?php endif; ?>
			
			<p>
				<button type="button" class="button wtcc-refresh-tracking" data-order="<?php echo esc_attr( $order->get_id() ); ?>">
					<?php esc_html_e( 'Refresh Tracking', 'wtc-shipping' ); ?>
				</button>
			</p>
		</div>
		<?php
	} else {
		?>
		<p><?php esc_html_e( 'No label created yet.', 'wtc-shipping' ); ?></p>
		<p><?php esc_html_e( 'Use Order Actions → "Create USPS Shipping Label" to generate.', 'wtc-shipping' ); ?></p>
		<?php
	}
}

/**
 * ==========================================================================
 * CUSTOMER-FACING: FIND USPS LOCATIONS SHORTCODE
 * ==========================================================================
 */

add_shortcode( 'wtc_usps_locations', 'wtcc_usps_locations_shortcode' );
function wtcc_usps_locations_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'zip' => '',
	), $atts );
	
	ob_start();
	?>
	<div class="wtcc-usps-locations">
		<form class="wtcc-location-search" method="get">
			<label for="wtcc-location-zip"><?php esc_html_e( 'Find USPS locations near:', 'wtc-shipping' ); ?></label>
			<input type="text" id="wtcc-location-zip" name="zip" placeholder="<?php esc_attr_e( 'Enter ZIP code', 'wtc-shipping' ); ?>" 
			       value="<?php echo esc_attr( $atts['zip'] ); ?>" pattern="\d{5}" maxlength="5">
			<button type="submit" class="button"><?php esc_html_e( 'Search', 'wtc-shipping' ); ?></button>
		</form>
		
		<?php if ( ! empty( $atts['zip'] ) ) : 
			$locations = wtcc_usps_find_locations( $atts['zip'] );
			if ( ! empty( $locations ) ) : ?>
				<div class="wtcc-locations-list">
					<?php foreach ( $locations as $loc ) : ?>
						<div class="wtcc-location-card">
							<h4><?php echo esc_html( $loc['name'] ); ?></h4>
							<p class="address"><?php echo esc_html( $loc['address'] ); ?></p>
							<?php if ( $loc['phone'] ) : ?>
								<p class="phone"><a href="tel:<?php echo esc_attr( $loc['phone'] ); ?>"><?php echo esc_html( $loc['phone'] ); ?></a></p>
							<?php endif; ?>
							<?php if ( $loc['has_self_service_kiosk'] ) : ?>
								<span class="badge"><?php esc_html_e( 'Self-Service Kiosk', 'wtc-shipping' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="wtcc-no-results"><?php esc_html_e( 'No USPS locations found near this ZIP code.', 'wtc-shipping' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Helper function to get friendly mail class names
 */
function wtcc_get_mail_class_name( $mail_class ) {
	$names = array(
		'USPS_GROUND_ADVANTAGE'         => 'USPS Ground Advantage',
		'PRIORITY_MAIL'                 => 'Priority Mail',
		'PRIORITY_MAIL_EXPRESS'         => 'Priority Mail Express',
		'FIRST_CLASS_MAIL'              => 'First-Class Mail',
		'PARCEL_SELECT'                 => 'Parcel Select',
		'MEDIA_MAIL'                    => 'Media Mail',
		'LIBRARY_MAIL'                  => 'Library Mail',
		'PRIORITY_MAIL_INTERNATIONAL'   => 'Priority Mail International',
		'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => 'Priority Mail Express International',
		'FIRST_CLASS_PACKAGE_INTERNATIONAL'   => 'First-Class Package International',
	);
	
	if ( ! $mail_class ) {
		return '';
	}
	
	return $names[ $mail_class ] ?? str_replace( '_', ' ', ucwords( strtolower( (string) $mail_class ), '_' ) );
}

/**
 * ==========================================================================
 * USER INFO API v3
 * Get authenticated user account information
 * ==========================================================================
 */

/**
 * Get USPS user profile information
 * Returns account details for the authenticated user
 * 
 * @return array|WP_Error User profile data or error
 */
function wtcc_usps_get_user_profile() {
	$credentials = wtcc_shipping_get_usps_credentials();
	
	if ( ! wtcc_shipping_validate_usps_credentials( $credentials['consumer_key'], $credentials['consumer_secret'] ) ) {
		return new WP_Error( 'not_connected', __( 'USPS API not connected', 'wtc-shipping' ) );
	}
	
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	if ( ! $access_token ) {
		return new WP_Error( 'auth_failed', __( 'Failed to authenticate with USPS', 'wtc-shipping' ) );
	}
	
	// Cache for 24 hours
	$cache_key = 'wtcc_usps_user_profile';
	$cached = get_transient( $cache_key );
	if ( $cached !== false ) {
		return $cached;
	}
	
	$response = wp_remote_get( $credentials['api_endpoint'] . '/user/v1/profile', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $access_token,
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
		),
		'timeout' => 15,
	) );
	
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'api_error', $response->get_error_message() );
	}
	
	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( $code !== 200 ) {
		return new WP_Error( 'api_error', $body['message'] ?? __( 'Failed to retrieve user profile (optional - shipping rates still work)', 'wtc-shipping' ) );
	}
	
	$profile = array(
		'user_id'          => $body['userId'] ?? '',
		'username'         => $body['username'] ?? '',
		'email'            => $body['email'] ?? '',
		'first_name'       => $body['firstName'] ?? '',
		'last_name'        => $body['lastName'] ?? '',
		'company_name'     => $body['companyName'] ?? '',
		'phone'            => $body['phone'] ?? '',
		'account_type'     => $body['accountType'] ?? '',
		'account_status'   => $body['accountStatus'] ?? '',
		'permissions'      => $body['permissions'] ?? array(),
		'enabled_apis'     => $body['enabledApis'] ?? array(),
		'created_date'     => $body['createdDate'] ?? '',
		'last_login'       => $body['lastLogin'] ?? '',
	);
	
	// Cache for 24 hours
	set_transient( $cache_key, $profile, 24 * HOUR_IN_SECONDS );
	
	return $profile;
}
