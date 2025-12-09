<?php
/**
 * USPS Label API Integration
 * Complete implementation for domestic and international label generation
 * Supports PDF (8.5x11, 4x6) and ZPL (thermal printer) formats
 * 
 * API Endpoints:
 * - Domestic: /labels/v3/label
 * - International: /international-labels/v3/label
 * 
 * @package WTC_Shipping
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create USPS shipping label (domestic)
 * 
 * @param array $shipment Shipment data including from, to, package details
 * @param array $options Label options (format, size, etc)
 * @return array|WP_Error Label data with tracking, image, postage or error
 */
function wtcc_usps_create_domestic_label( $shipment, $options = array() ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	
	if ( ! $access_token ) {
		return new WP_Error( 'oauth_failed', 'Failed to obtain USPS OAuth token' );
	}
	
	// Default options
	$options = wp_parse_args( $options, array(
		'label_format'       => 'PDF',           // PDF, ZPL, PNG
		'label_size'         => '4X6',           // 4X6, 6X4, 8.5X11
		'receipt'            => true,            // Include postage receipt
		'separate_receipt'   => false,           // Separate receipt page
		'print_customs_form' => false,           // Not needed for domestic
	) );
	
	// Validate required fields
	$validation = wtcc_validate_shipment_data( $shipment, 'domestic' );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}
	
	// Map mail class to USPS service code
	$service_map = array(
		'USPS_GROUND_ADVANTAGE' => 'USPS_GROUND_ADVANTAGE',
		'PRIORITY_MAIL'         => 'PRIORITY_MAIL',
		'PRIORITY_MAIL_EXPRESS' => 'PRIORITY_MAIL_EXPRESS',
		'FIRST_CLASS_MAIL'      => 'FIRST_CLASS_MAIL',
	);
	
	$mail_class = isset( $service_map[ $shipment['mail_class'] ] ) 
		? $service_map[ $shipment['mail_class'] ] 
		: 'USPS_GROUND_ADVANTAGE';
	
	// Build API request body per USPS Label API v3 spec
	$request_body = array(
		'fromAddress' => array(
			'streetAddress'    => $shipment['from']['address_1'],
			'secondaryAddress' => $shipment['from']['address_2'] ?? '',
			'city'             => $shipment['from']['city'],
			'state'            => $shipment['from']['state'],
			'ZIPCode'          => substr( $shipment['from']['postcode'], 0, 5 ),
			'ZIPPlus4'         => '',
		),
		'toAddress' => array(
			'streetAddress'    => $shipment['to']['address_1'],
			'secondaryAddress' => $shipment['to']['address_2'] ?? '',
			'city'             => $shipment['to']['city'],
			'state'            => $shipment['to']['state'],
			'ZIPCode'          => substr( $shipment['to']['postcode'], 0, 5 ),
			'ZIPPlus4'         => '',
		),
		'weight'      => round( $shipment['weight'] * 16, 2 ), // Convert lbs to oz
		'length'      => round( $shipment['length'], 2 ),
		'width'       => round( $shipment['width'], 2 ),
		'height'      => round( $shipment['height'], 2 ),
		'mailClass'   => $mail_class,
		'processingCategory' => 'MACHINABLE',
		'rateIndicator'      => 'SP', // Single piece
		'destinationEntryFacilityType' => 'NONE',
		'labelFormat' => $options['label_format'],
		'labelSize'   => $options['label_size'],
	);
	
	// Add optional fields
	if ( ! empty( $shipment['from']['company'] ) ) {
		$request_body['fromAddress']['firmName'] = substr( $shipment['from']['company'], 0, 50 );
	}
	if ( ! empty( $shipment['to']['company'] ) ) {
		$request_body['toAddress']['firmName'] = substr( $shipment['to']['company'], 0, 50 );
	}
	
	// Add insurance if specified
	if ( ! empty( $shipment['insured_value'] ) && $shipment['insured_value'] > 0 ) {
		$request_body['extraServices'] = array( '930' ); // Insurance extra service code
		$request_body['insuredValue'] = round( $shipment['insured_value'], 2 );
	}
	
	// Add delivery confirmation if available
	if ( in_array( $mail_class, array( 'PRIORITY_MAIL', 'PRIORITY_MAIL_EXPRESS' ), true ) ) {
		if ( ! isset( $request_body['extraServices'] ) ) {
			$request_body['extraServices'] = array();
		}
		// Delivery confirmation included free with Priority/Express
	}
	
	$endpoint = $credentials['api_endpoint'] . '/labels/v3/label';
	
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
		return new WP_Error( 'api_error', 'Label API request failed: ' . $response->get_error_message() );
	}
	
	$status_code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	// Log for debugging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'USPS Label API - Status: ' . $status_code );
		error_log( 'USPS Label API - Request: ' . wp_json_encode( $request_body ) );
		error_log( 'USPS Label API - Response: ' . substr( $body, 0, 1000 ) );
	}
	
	if ( $status_code >= 200 && $status_code < 300 ) {
		// Success - extract label data
		return array(
			'tracking_number' => $data['trackingNumber'] ?? '',
			'label_image'     => $data['labelImage'] ?? '',          // Base64 encoded
			'receipt_image'   => $data['receiptImage'] ?? '',        // Base64 encoded receipt
			'postage'         => $data['postage'] ?? 0,
			'label_format'    => $options['label_format'],
			'label_size'      => $options['label_size'],
			'mail_class'      => $mail_class,
			'delivery_date'   => $data['deliveryDate'] ?? '',
			'raw_response'    => $data,
		);
	}
	
	// Error handling
	$error_message = 'Label creation failed';
	if ( isset( $data['error']['message'] ) ) {
		$error_message = $data['error']['message'];
	} elseif ( isset( $data['errors'][0]['message'] ) ) {
		$error_message = $data['errors'][0]['message'];
	}
	
	return new WP_Error( 'label_failed', $error_message, array( 'response' => $data ) );
}

/**
 * Create USPS international shipping label with customs forms
 * 
 * @param array $shipment Shipment data including customs info
 * @param array $options Label options
 * @return array|WP_Error Label data or error
 */
function wtcc_usps_create_international_label( $shipment, $options = array() ) {
	$credentials = wtcc_shipping_get_usps_credentials();
	$access_token = wtcc_shipping_get_oauth_token( $credentials );
	
	if ( ! $access_token ) {
		return new WP_Error( 'oauth_failed', 'Failed to obtain USPS OAuth token' );
	}
	
	// Default options
	$options = wp_parse_args( $options, array(
		'label_format'       => 'PDF',
		'label_size'         => '4X6',
		'receipt'            => true,
		'separate_receipt'   => false,
		'print_customs_form' => true,
	) );
	
	// Validate required fields
	$validation = wtcc_validate_shipment_data( $shipment, 'international' );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}
	
	// International mail class
	$mail_class = $shipment['mail_class'] ?? 'PRIORITY_MAIL_INTERNATIONAL';
	
	// Build request body per USPS International Labels API v3 spec
	$request_body = array(
		'imageInfo' => array(
			'imageType' => $options['label_format'], // PDF, ZPL, PNG
			'labelType' => $options['label_size'],    // 4X6LABEL, 6X4LABEL, etc.
			'receiptOption' => $options['receipt'] ? 'SEPARATE_PAGE' : 'NONE',
		),
		'fromAddress' => array(
			'firstName'        => $shipment['from']['first_name'] ?? '',
			'lastName'         => $shipment['from']['last_name'] ?? '',
			'firm'             => $shipment['from']['company'] ?? '',
			'address1'         => $shipment['from']['address_1'],
			'address2'         => $shipment['from']['address_2'] ?? '',
			'city'             => $shipment['from']['city'],
			'state'            => $shipment['from']['state'],
			'zip5'             => substr( $shipment['from']['postcode'], 0, 5 ),
			'zip4'             => '',
			'phone'            => $shipment['from']['phone'] ?? get_option( 'wtcc_origin_phone', '' ),
		),
		'toAddress' => array(
			'firstName'        => $shipment['to']['first_name'] ?? '',
			'lastName'         => $shipment['to']['last_name'] ?? '',
			'firm'             => $shipment['to']['company'] ?? '',
			'address1'         => $shipment['to']['address_1'],
			'address2'         => $shipment['to']['address_2'] ?? '',
			'city'             => $shipment['to']['city'],
			'province'         => $shipment['to']['state'] ?? '',
			'postalCode'       => $shipment['to']['postcode'],
			'countryCode'      => $shipment['to']['country'],
			'phone'            => $shipment['to']['phone'] ?? '',
			'email'            => $shipment['to']['email'] ?? '',
		),
		'packageDescription' => array(
			'weight'           => round( $shipment['weight'], 4 ),
			'length'           => round( $shipment['length'], 2 ),
			'width'            => round( $shipment['width'], 2 ),
			'height'           => round( $shipment['height'], 2 ),
			'girth'            => 0,
			'mailClass'        => $mail_class,
			'processingCategory' => 'NON_MACHINABLE',
			'rateIndicator'    => 'SP',
			'destinationEntryFacilityType' => 'NONE',
		),
	);
	
	// Add customs information (required for international)
	if ( ! empty( $shipment['customs_items'] ) ) {
		$customs_items = array();
		$total_value = 0;
		
		foreach ( $shipment['customs_items'] as $item ) {
			$item_value = round( floatval( $item['value'] ), 2 );
			$item_weight = round( floatval( $item['weight'] ), 4 );
			$total_value += $item_value * intval( $item['quantity'] );
			
			$customs_items[] = array(
				'description'      => substr( $item['description'], 0, 50 ),
				'quantity'         => intval( $item['quantity'] ),
				'value'            => $item_value,
				'weight'           => $item_weight,
				'tariffNumber'     => $item['hs_code'] ?? '',
				'countryOfOrigin'  => $item['origin_country'] ?? 'US',
			);
		}
		
		$request_body['customsForm'] = array(
			'customsContentType' => $shipment['content_type'] ?? 'MERCHANDISE',
			'contentsExplanation' => $shipment['content_description'] ?? 'Merchandise',
			'restriction'        => $shipment['restriction_type'] ?? 'NONE',
			'customsSigner'      => get_option( 'wtcc_company_name', get_bloginfo( 'name' ) ),
			'customsCertify'     => true,
			'customsItems'       => $customs_items,
			'insuredValue'       => $shipment['insured_value'] ?? 0,
			'nonDeliveryOption'  => $shipment['non_delivery_option'] ?? 'RETURN',
		);
		
		// Add invoice number if available
		if ( ! empty( $shipment['invoice_number'] ) ) {
			$request_body['customsForm']['invoiceNumber'] = $shipment['invoice_number'];
		}
		
		// Add license number if available
		if ( ! empty( $shipment['license_number'] ) ) {
			$request_body['customsForm']['licenseNumber'] = $shipment['license_number'];
		}
		
		// Add certificate number if available
		if ( ! empty( $shipment['certificate_number'] ) ) {
			$request_body['customsForm']['certificateNumber'] = $shipment['certificate_number'];
		}
	}
	
	// Use dedicated international labels endpoint per USPS API v3 spec
	$api_mode = get_option( 'wtcc_usps_api_mode', 'production' );
	$base_url = $api_mode === 'sandbox' 
		? 'https://api-cat.usps.com' 
		: 'https://api.usps.com';
	
	$endpoint = $base_url . '/international-labels/v3/label';
	
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
		return new WP_Error( 'api_error', 'International label API request failed: ' . $response->get_error_message() );
	}
	
	$status_code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	// Log for debugging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'USPS Intl Label API - Status: ' . $status_code );
		error_log( 'USPS Intl Label API - Response: ' . substr( $body, 0, 1000 ) );
	}
	
	if ( $status_code >= 200 && $status_code < 300 ) {
		return array(
			'tracking_number'     => $data['trackingNumber'] ?? '',
			'label_image'         => $data['labelImage'] ?? '',
			'customs_form_image'  => $data['customsFormImage'] ?? '',      // CN22/CN23 form image
			'customs_form_number' => $data['customsFormNumber'] ?? '',     // Customs form number
			'receipt_image'       => $data['receiptImage'] ?? '',
			'postage'             => $data['postage'] ?? 0,
			'zone'                => $data['zone'] ?? '',
			'carrier_route'       => $data['carrierRoute'] ?? '',
			'post_office'         => $data['postOffice'] ?? '',
			'label_format'        => $options['label_format'],
			'label_size'          => $options['label_size'],
			'mail_class'          => $mail_class,
			'delivery_date'       => $data['commitmentDate'] ?? '',        // Estimated delivery
			'insurance_fee'       => $data['insuranceFee'] ?? 0,
			'extra_services'      => $data['extraServices'] ?? array(),
			'raw_response'        => $data,
		);
	}
	
	// Error handling
	$error_message = 'International label creation failed';
	if ( isset( $data['error']['message'] ) ) {
		$error_message = $data['error']['message'];
	} elseif ( isset( $data['errors'][0]['message'] ) ) {
		$error_message = $data['errors'][0]['message'];
	}
	
	return new WP_Error( 'label_failed', $error_message, array( 'response' => $data ) );
}

/**
 * Validate shipment data before API call
 * 
 * @param array  $shipment Shipment data
 * @param string $type 'domestic' or 'international'
 * @return true|WP_Error True if valid, error otherwise
 */
function wtcc_validate_shipment_data( $shipment, $type = 'domestic' ) {
	$required = array( 'from', 'to', 'weight' );
	
	foreach ( $required as $field ) {
		if ( empty( $shipment[ $field ] ) ) {
			return new WP_Error( 'missing_field', sprintf( 'Missing required field: %s', $field ) );
		}
	}
	
	// Validate addresses
	$address_fields = array( 'address_1', 'city', 'state', 'postcode' );
	foreach ( array( 'from', 'to' ) as $addr_type ) {
		foreach ( $address_fields as $field ) {
			if ( empty( $shipment[ $addr_type ][ $field ] ) ) {
				return new WP_Error( 'missing_address', sprintf( 'Missing %s address field: %s', $addr_type, $field ) );
			}
		}
	}
	
	// International-specific validation
	if ( 'international' === $type ) {
		if ( empty( $shipment['to']['country'] ) ) {
			return new WP_Error( 'missing_country', 'Country is required for international shipments' );
		}
		
		if ( empty( $shipment['customs_items'] ) ) {
			return new WP_Error( 'missing_customs', 'Customs items are required for international shipments' );
		}
	}
	
	// Validate weight (max 70 lbs for most services)
	if ( $shipment['weight'] > 70 ) {
		return new WP_Error( 'weight_exceeded', 'Weight exceeds maximum of 70 lbs' );
	}
	
	return true;
}

/**
 * Convert label format for different printer types
 * 
 * @param string $label_image Base64 encoded label
 * @param string $from_format Current format (PDF, ZPL, PNG)
 * @param string $to_format Desired format
 * @return string|WP_Error Converted label or error
 */
function wtcc_convert_label_format( $label_image, $from_format, $to_format ) {
	// If formats match, no conversion needed
	if ( strtoupper( $from_format ) === strtoupper( $to_format ) ) {
		return $label_image;
	}
	
	// Note: Format conversion typically requires external library or service
	// For now, return error - integrate with ImageMagick or similar if needed
	return new WP_Error( 
		'conversion_unsupported', 
		'Label format conversion not yet implemented. Request original format from USPS API.' 
	);
}

/**
 * Get available label formats and sizes
 * 
 * @return array Available formats
 */
function wtcc_get_available_label_formats() {
	return array(
		'formats' => array(
			'PDF' => array(
				'name'        => 'PDF',
				'description' => 'Standard PDF - Works with any printer',
				'printer_type' => 'regular',
			),
			'ZPL' => array(
				'name'        => 'ZPL',
				'description' => 'Zebra Programming Language - Thermal printers',
				'printer_type' => 'thermal',
			),
			'PNG' => array(
				'name'        => 'PNG',
				'description' => 'Image format - Universal compatibility',
				'printer_type' => 'regular',
			),
		),
		'sizes' => array(
			'4X6' => array(
				'name'        => '4" x 6"',
				'description' => 'Standard thermal label size',
				'recommended_for' => array( 'thermal' ),
			),
			'6X4' => array(
				'name'        => '6" x 4"',
				'description' => 'Landscape thermal label',
				'recommended_for' => array( 'thermal' ),
			),
			'8.5X11' => array(
				'name'        => '8.5" x 11"',
				'description' => 'Standard letter size',
				'recommended_for' => array( 'regular' ),
			),
		),
	);
}

/**
 * Save label to order and file system
 * 
 * @param WC_Order $order Order object
 * @param array    $label_data Label data from API
 * @return bool|WP_Error True on success, error otherwise
 */
function wtcc_save_label_to_order( $order, $label_data ) {
	if ( ! $order ) {
		return new WP_Error( 'invalid_order', 'Invalid order object' );
	}
	
	// Save to order meta
	$order->update_meta_data( '_wtcc_tracking_number', $label_data['tracking_number'] );
	$order->update_meta_data( '_wtcc_label_image', $label_data['label_image'] );
	$order->update_meta_data( '_wtcc_label_format', $label_data['label_format'] );
	$order->update_meta_data( '_wtcc_label_size', $label_data['label_size'] );
	$order->update_meta_data( '_wtcc_postage_paid', $label_data['postage'] );
	$order->update_meta_data( '_wtcc_label_created', current_time( 'mysql' ) );
	$order->update_meta_data( '_wtcc_mail_class', $label_data['mail_class'] );
	
	// Save receipt if available
	if ( ! empty( $label_data['receipt_image'] ) ) {
		$order->update_meta_data( '_wtcc_receipt_image', $label_data['receipt_image'] );
	}
	
	// Save customs form for international (new format from International Labels v3)
	if ( ! empty( $label_data['customs_form_image'] ) ) {
		$order->update_meta_data( '_wtcc_customs_form_image', $label_data['customs_form_image'] );
	}
	
	if ( ! empty( $label_data['customs_form_number'] ) ) {
		$order->update_meta_data( '_wtcc_customs_form_number', $label_data['customs_form_number'] );
	}
	
	// Legacy support for old 'customs_form' field
	if ( ! empty( $label_data['customs_form'] ) ) {
		$order->update_meta_data( '_wtcc_customs_form', $label_data['customs_form'] );
	}
	
	// Save additional international label data
	if ( ! empty( $label_data['insurance_fee'] ) ) {
		$order->update_meta_data( '_wtcc_insurance_fee', $label_data['insurance_fee'] );
	}
	
	if ( ! empty( $label_data['delivery_date'] ) ) {
		$order->update_meta_data( '_wtcc_delivery_date', $label_data['delivery_date'] );
	}
	
	$order->save();
	
	// Optional: Save to file system for backup
	$upload_dir = wp_upload_dir();
	
	// Validate upload directory is configured and writable
	if ( empty( $upload_dir['basedir'] ) || ! empty( $upload_dir['error'] ) ) {
		error_log( 'Inkfinit Shipping: wp_upload_dir() failed - ' . ( $upload_dir['error'] ?? 'basedir empty' ) );
		return true; // Label saved to order meta, file backup skipped
	}
	
	$label_dir = $upload_dir['basedir'] . '/wtc-shipping-labels';
	
	if ( ! file_exists( $label_dir ) ) {
		wp_mkdir_p( $label_dir );
	}
	
	$file_extension = strtolower( $label_data['label_format'] );
	$file_name = sprintf( 
		'label-%d-%s.%s', 
		$order->get_id(), 
		$label_data['tracking_number'], 
		$file_extension 
	);
	$file_path = $label_dir . '/' . $file_name;
	
	// Decode and save
	$label_binary = base64_decode( $label_data['label_image'] );
	if ( $label_binary && $file_path ) {
		file_put_contents( $file_path, $label_binary );
	}
	
	// Save file path to meta
	$order->update_meta_data( '_wtcc_label_file_path', $file_path );
	$order->save();
	
	return true;
}

/**
 * Generate label download URL
 * 
 * @param int $order_id Order ID
 * @return string|false Download URL or false if no label
 */
function wtcc_get_label_download_url( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return false;
	}
	
	$tracking = $order->get_meta( '_wtcc_tracking_number', true );
	if ( ! $tracking ) {
		return false;
	}
	
	return add_query_arg(
		array(
			'wtc_download_label' => $order_id,
			'nonce'              => wp_create_nonce( 'wtc_download_label_' . $order_id ),
		),
		admin_url( 'admin-ajax.php' )
	);
}

/**
 * Handle label download request
 */
add_action( 'admin_init', 'wtcc_handle_label_download' );
function wtcc_handle_label_download() {
	if ( ! isset( $_GET['wtc_download_label'] ) ) {
		return;
	}
	
	$order_id = absint( $_GET['wtc_download_label'] );
	$nonce = sanitize_text_field( $_GET['nonce'] ?? '' );
	
	if ( ! wp_verify_nonce( $nonce, 'wtc_download_label_' . $order_id ) ) {
		wp_die( 'Security check failed', 403 );
	}
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Permission denied', 403 );
	}
	
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_die( 'Order not found', 404 );
	}
	
	$label_image = $order->get_meta( '_wtcc_label_image', true );
	$label_format = $order->get_meta( '_wtcc_label_format', true ) ?: 'PDF';
	$tracking = $order->get_meta( '_wtcc_tracking_number', true );
	
	if ( ! $label_image ) {
		wp_die( 'No label found for this order', 404 );
	}
	
	// Set headers for download
	$extension = strtolower( $label_format );
	$mime_types = array(
		'pdf' => 'application/pdf',
		'zpl' => 'application/zpl',
		'png' => 'image/png',
	);
	
	$mime_type = $mime_types[ $extension ] ?? 'application/octet-stream';
	$filename = sprintf( 'label-%d-%s.%s', $order_id, $tracking, $extension );
	
	header( 'Content-Type: ' . $mime_type );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Accept-Ranges: bytes' );
	
	// Output label
	echo base64_decode( $label_image );
	exit;
}

/**
 * Handle customs form download request (international shipments)
 */
add_action( 'admin_init', 'wtcc_handle_customs_form_download' );
function wtcc_handle_customs_form_download() {
	if ( ! isset( $_GET['wtc_download_customs'] ) ) {
		return;
	}
	
	$order_id = absint( $_GET['wtc_download_customs'] );
	$nonce = sanitize_text_field( $_GET['nonce'] ?? '' );
	
	if ( ! wp_verify_nonce( $nonce, 'wtc_download_customs_' . $order_id ) ) {
		wp_die( 'Security check failed', 403 );
	}
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Permission denied', 403 );
	}
	
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_die( 'Order not found', 404 );
	}
	
	// Try new format first (International Labels v3)
	$customs_image = $order->get_meta( '_wtcc_customs_form_image', true );
	$customs_number = $order->get_meta( '_wtcc_customs_form_number', true );
	
	// Fallback to legacy format
	if ( ! $customs_image ) {
		$customs_image = $order->get_meta( '_wtcc_customs_form', true );
	}
	
	if ( ! $customs_image ) {
		wp_die( 'No customs form found for this order', 404 );
	}
	
	$tracking = $order->get_meta( '_wtcc_tracking_number', true );
	$label_format = $order->get_meta( '_wtcc_label_format', true ) ?: 'PDF';
	
	// Set headers for download
	$extension = strtolower( $label_format );
	$mime_types = array(
		'pdf' => 'application/pdf',
		'png' => 'image/png',
	);
	
	$mime_type = $mime_types[ $extension ] ?? 'application/pdf';
	$filename = sprintf( 'customs-form-%d-%s.%s', $order_id, $tracking, $extension );
	
	header( 'Content-Type: ' . $mime_type );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Accept-Ranges: bytes' );
	
	// Output customs form
	echo base64_decode( $customs_image );
	exit;
}

/**
 * Generate customs form download URL
 * 
 * @param int $order_id Order ID
 * @return string|false Download URL or false if no customs form
 */
function wtcc_get_customs_form_download_url( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return false;
	}
	
	// Check if customs form exists
	$customs_image = $order->get_meta( '_wtcc_customs_form_image', true );
	if ( ! $customs_image ) {
		$customs_image = $order->get_meta( '_wtcc_customs_form', true );
	}
	
	if ( ! $customs_image ) {
		return false;
	}
	
	return add_query_arg(
		array(
			'wtc_download_customs' => $order_id,
			'nonce'                => wp_create_nonce( 'wtc_download_customs_' . $order_id ),
		),
		admin_url( 'admin-ajax.php' )
	);
}
