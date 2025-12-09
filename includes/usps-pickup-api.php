<?php
/**
 * USPS Carrier Pickup API Integration
 * Schedule package pickups with USPS carriers
 * 
 * API Endpoint: /pickup/v3/carrier-pickup
 * Supports: Schedule, Check availability, Modify, Cancel
 * 
 * @package WTC_Shipping
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule USPS carrier pickup
 * 
 * @param array $pickup_data Pickup details
 * @return array|WP_Error Pickup confirmation or error
 */
function wtcc_usps_schedule_pickup( $pickup_data ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	
	if ( ! $access_token ) {
		return new WP_Error( 'oauth_failed', 'Failed to obtain USPS OAuth token' );
	}
	
	// Validate required fields
	$validation = wtcc_validate_pickup_data( $pickup_data );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}
	
	// Build API request body per USPS Carrier Pickup API v3 spec
	$request_body = array(
		'pickupAddress' => array(
			'firstName'        => $pickup_data['firstName'],
			'lastName'         => $pickup_data['lastName'],
			'firm'             => $pickup_data['company'] ?? '',
			'address' => array(
				'streetAddress'    => $pickup_data['address']['streetAddress'],
				'secondaryAddress' => $pickup_data['address']['secondaryAddress'] ?? '',
				'city'             => $pickup_data['address']['city'],
				'state'            => $pickup_data['address']['state'],
				'ZIPCode'          => substr( $pickup_data['address']['zipCode'], 0, 5 ),
				'ZIPPlus4'         => '',
			),
		),
		'packages' => array(
			array(
				'packageType'  => $pickup_data['packageType'] ?? 'PACKAGE',  // PACKAGE, FLAT_RATE_ENVELOPE, etc
				'packageCount' => intval( $pickup_data['packageCount'] ?? 1 ),
			),
		),
		'estimatedWeight' => round( $pickup_data['totalWeight'] ?? 1, 2 ),  // Total weight in pounds
		'pickupDate' => $pickup_data['pickupDate'],  // Format: YYYY-MM-DD
		'pickupLocation' => array(
			'packageLocation'      => $pickup_data['packageLocation'] ?? 'FRONT_DOOR',  // FRONT_DOOR, BACK_DOOR, SIDE_DOOR, KNOCK_ON_DOOR, MAIL_ROOM, OFFICE, RECEPTION, IN_MAILBOX, OTHER
			'specialInstructions'  => $pickup_data['specialInstructions'] ?? '',
		),
	);
	
	// Add contact info
	if ( ! empty( $pickup_data['phone'] ) ) {
		$request_body['pickupAddress']['contact'] = array(
			'email' => $pickup_data['email'] ?? '',
			'phone' => preg_replace( '/[^0-9]/', '', $pickup_data['phone'] ),
		);
	}
	
	$endpoint = $credentials['api_endpoint'] . '/pickup/v3/carrier-pickup';
	
	$response = wp_remote_post(
		$endpoint,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'      => wp_json_encode( $request_body ),
			'timeout'   => 30,
			'sslverify' => true,
		)
	);
	
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'api_error', 'Pickup API request failed: ' . $response->get_error_message() );
	}
	
	$status_code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	// Log for debugging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'USPS Pickup API - Status: ' . $status_code );
		error_log( 'USPS Pickup API - Request: ' . wp_json_encode( $request_body ) );
		error_log( 'USPS Pickup API - Response: ' . substr( $body, 0, 1000 ) );
	}
	
	if ( $status_code >= 200 && $status_code < 300 ) {
		// Success - extract confirmation data
		return array(
			'confirmation_number' => $data['confirmationNumber'] ?? '',
			'pickup_date'         => $data['pickupDate'] ?? '',
			'pickup_address'      => $data['pickupAddress'] ?? array(),
			'status'              => 'scheduled',
			'raw_response'        => $data,
		);
	}
	
	// Error handling
	$error_message = 'Pickup scheduling failed';
	if ( isset( $data['error']['message'] ) ) {
		$error_message = $data['error']['message'];
	} elseif ( isset( $data['errors'][0]['message'] ) ) {
		$error_message = $data['errors'][0]['message'];
	}
	
	return new WP_Error( 'pickup_failed', $error_message, array( 'response' => $data ) );
}

/**
 * Check carrier pickup availability for a date
 * 
 * @param string $zip_code ZIP code
 * @param string $pickup_date Date in YYYY-MM-DD format
 * @return array|WP_Error Availability data or error
 */
function wtcc_usps_check_pickup_availability( $zip_code, $pickup_date ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	
	if ( ! $access_token ) {
		return new WP_Error( 'oauth_failed', 'Failed to obtain USPS OAuth token' );
	}
	
	// Clean ZIP code
	$zip_code = substr( preg_replace( '/[^0-9]/', '', $zip_code ), 0, 5 );
	
	if ( strlen( $zip_code ) !== 5 ) {
		return new WP_Error( 'invalid_zip', 'Invalid ZIP code provided' );
	}
	
	$endpoint = $credentials['api_endpoint'] . '/pickup/v3/carrier-pickup/availability';
	$url = add_query_arg(
		array(
			'ZIPCode'    => $zip_code,
			'pickupDate' => $pickup_date,
		),
		$endpoint
	);
	
	$response = wp_remote_get(
		$url,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Accept'        => 'application/json',
			),
			'timeout'   => 15,
			'sslverify' => true,
		)
	);
	
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'api_error', 'Availability check failed: ' . $response->get_error_message() );
	}
	
	$status_code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	if ( $status_code === 200 && isset( $data['available'] ) ) {
		return array(
			'available'    => (bool) $data['available'],
			'pickup_date'  => $pickup_date,
			'carrier_route' => $data['carrierRoute'] ?? '',
			'message'      => $data['message'] ?? '',
		);
	}
	
	$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unable to check availability';
	return new WP_Error( 'check_failed', $error_message );
}

/**
 * Cancel a scheduled pickup
 * 
 * @param string $confirmation_number Pickup confirmation number
 * @return bool|WP_Error True on success, error on failure
 */
function wtcc_usps_cancel_pickup( $confirmation_number ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	
	if ( ! $access_token ) {
		return new WP_Error( 'oauth_failed', 'Failed to obtain USPS OAuth token' );
	}
	
	$endpoint = $credentials['api_endpoint'] . '/pickup/v3/carrier-pickup/' . $confirmation_number;
	
	$response = wp_remote_request(
		$endpoint,
		array(
			'method'  => 'DELETE',
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Accept'        => 'application/json',
			),
			'timeout'   => 15,
			'sslverify' => true,
		)
	);
	
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'api_error', 'Cancellation request failed: ' . $response->get_error_message() );
	}
	
	$status_code = wp_remote_retrieve_response_code( $response );
	
	if ( $status_code === 200 || $status_code === 204 ) {
		return true;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Cancellation failed';
	
	return new WP_Error( 'cancel_failed', $error_message );
}

/**
 * Validate pickup data before API call
 * 
 * @param array $pickup_data Pickup data
 * @return true|WP_Error True if valid, error otherwise
 */
function wtcc_validate_pickup_data( $pickup_data ) {
	$errors = array();
	
	// Required fields
	$required = array(
		'firstName'   => 'First name',
		'lastName'    => 'Last name',
		'address'     => 'Address',
		'pickupDate'  => 'Pickup date',
	);
	
	foreach ( $required as $field => $label ) {
		if ( empty( $pickup_data[ $field ] ) ) {
			$errors[] = $label . ' is required';
		}
	}
	
	// Validate address subfields
	if ( ! empty( $pickup_data['address'] ) ) {
		$address_required = array( 'streetAddress', 'city', 'state', 'zipCode' );
		foreach ( $address_required as $field ) {
			if ( empty( $pickup_data['address'][ $field ] ) ) {
				$errors[] = 'Address ' . $field . ' is required';
			}
		}
	}
	
	// Validate pickup date format
	if ( ! empty( $pickup_data['pickupDate'] ) ) {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $pickup_data['pickupDate'] ) ) {
			$errors[] = 'Pickup date must be in YYYY-MM-DD format';
		}
		
		// Must be future date
		$pickup_timestamp = strtotime( $pickup_data['pickupDate'] );
		if ( $pickup_timestamp < strtotime( 'today' ) ) {
			$errors[] = 'Pickup date must be today or in the future';
		}
	}
	
	if ( ! empty( $errors ) ) {
		return new WP_Error( 'validation_failed', implode( '; ', $errors ) );
	}
	
	return true;
}

/**
 * Save pickup confirmation to order meta
 * 
 * @param WC_Order|int $order Order object or ID
 * @param array $pickup_data Pickup confirmation data
 * @return bool Success status
 */
function wtcc_save_pickup_to_order( $order, $pickup_data ) {
	$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;
	
	if ( ! $order ) {
		return false;
	}
	
	// Save pickup data
	$order->update_meta_data( '_wtcc_pickup_confirmation', $pickup_data['confirmation_number'] );
	$order->update_meta_data( '_wtcc_pickup_date', $pickup_data['pickup_date'] );
	$order->update_meta_data( '_wtcc_pickup_status', $pickup_data['status'] );
	$order->update_meta_data( '_wtcc_pickup_scheduled_at', current_time( 'mysql' ) );
	$order->save();
	
	// Add order note
	$order->add_order_note(
		sprintf(
			__( 'USPS pickup scheduled for %s. Confirmation: %s', 'wtc-shipping' ),
			$pickup_data['pickup_date'],
			$pickup_data['confirmation_number']
		)
	);
	
	// Trigger action for extensions
	do_action( 'wtcc_pickup_scheduled', $order->get_id(), $pickup_data );
	
	return true;
}

/**
 * Get package types for pickup
 * 
 * @return array Package types
 */
function wtcc_get_pickup_package_types() {
	return array(
		'PACKAGE'              => __( 'Package', 'wtc-shipping' ),
		'FLAT_RATE_ENVELOPE'   => __( 'Flat Rate Envelope', 'wtc-shipping' ),
		'FLAT_RATE_BOX'        => __( 'Flat Rate Box', 'wtc-shipping' ),
		'EXPRESS_MAIL'         => __( 'Express Mail', 'wtc-shipping' ),
		'PRIORITY_MAIL'        => __( 'Priority Mail', 'wtc-shipping' ),
		'INTERNATIONAL'        => __( 'International', 'wtc-shipping' ),
		'OTHER'                => __( 'Other', 'wtc-shipping' ),
	);
}

/**
 * Get package locations for pickup
 * 
 * @return array Package locations
 */
function wtcc_get_pickup_locations() {
	return array(
		'FRONT_DOOR'   => __( 'Front Door', 'wtc-shipping' ),
		'BACK_DOOR'    => __( 'Back Door', 'wtc-shipping' ),
		'SIDE_DOOR'    => __( 'Side Door', 'wtc-shipping' ),
		'KNOCK_ON_DOOR' => __( 'Knock on Door', 'wtc-shipping' ),
		'MAIL_ROOM'    => __( 'Mail Room', 'wtc-shipping' ),
		'OFFICE'       => __( 'Office', 'wtc-shipping' ),
		'RECEPTION'    => __( 'Reception', 'wtc-shipping' ),
		'IN_MAILBOX'   => __( 'In Mailbox', 'wtc-shipping' ),
		'OTHER'        => __( 'Other Location', 'wtc-shipping' ),
	);
}
