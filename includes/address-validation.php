<?php
/**
 * Inkfinit Shipping - USPS Address Validation at Checkout
 * Validates shipping addresses using USPS API before order placement
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * USPS Address Validation API call
 */
function wtcc_validate_usps_address( $address1, $address2, $city, $state, $zip ) {
	// Get USPS credentials
	$consumer_key = get_option( 'wtcc_usps_consumer_key', '' );
	$consumer_secret = get_option( 'wtcc_usps_consumer_secret', '' );
	
	if ( empty( $consumer_key ) || empty( $consumer_secret ) ) {
		return new WP_Error( 'no_credentials', 'USPS API credentials not configured' );
	}
	
	// Get OAuth token
	$credentials = array(
		'consumer_key' => $consumer_key,
		'consumer_secret' => $consumer_secret,
	);
	$token = wtcc_shipping_get_oauth_token( $credentials );
	
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	
	// USPS Address Validation API endpoint
	$api_mode = get_option( 'wtcc_usps_api_mode', 'production' );
	$base_url = $api_mode === 'sandbox' 
		? 'https://api-cat.usps.com' 
		: 'https://api.usps.com';
	
	$url = $base_url . '/addresses/v3/address';
	
	// Build request
	$request_body = array(
		'streetAddress' => trim( $address1 . ' ' . $address2 ),
		'city' => $city,
		'state' => $state,
		'ZIPCode' => $zip,
	);
	
	$args = array(
		'method' => 'GET',
		'headers' => array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type' => 'application/json',
		),
		'body' => json_encode( $request_body ),
		'timeout' => 15,
	);
	
	$response = wp_remote_get( add_query_arg( $request_body, $url ), $args );
	
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'api_error', 'Address validation request failed: ' . $response->get_error_message() );
	}
	
	$status = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( $status !== 200 ) {
		$error_message = $body['error']['message'] ?? 'Unknown error';
		return new WP_Error( 'validation_failed', $error_message );
	}
	
	// Check if address is valid
	if ( ! empty( $body['address'] ) ) {
		return array(
			'valid' => true,
			'original' => array(
				'address1' => $address1,
				'address2' => $address2,
				'city' => $city,
				'state' => $state,
				'zip' => $zip,
			),
			'standardized' => array(
				'address1' => $body['address']['streetAddress'] ?? $address1,
				'address2' => $body['address']['secondaryAddress'] ?? $address2,
				'city' => $body['address']['city'] ?? $city,
				'state' => $body['address']['state'] ?? $state,
				'zip' => $body['address']['ZIPCode'] ?? $zip,
				'zip4' => $body['address']['ZIPPlus4'] ?? '',
			),
			'deliverable' => $body['address']['deliveryPoint'] ?? 'UNKNOWN',
		);
	}
	
	return new WP_Error( 'invalid_address', 'Address could not be validated' );
}

/**
 * Add address validation to checkout
 */
add_action( 'woocommerce_after_checkout_validation', 'wtcc_validate_checkout_address', 10, 2 );
function wtcc_validate_checkout_address( $data, $errors ) {
	// Check if address validation is enabled
	$validation_enabled = get_option( 'wtcc_address_validation_enabled', 'no' ) === 'yes';
	
	if ( ! $validation_enabled ) {
		return;
	}
	
	// Skip validation if shipping to different address is not checked
	if ( empty( $data['ship_to_different_address'] ) && ! empty( $data['billing_address_1'] ) ) {
		// Use billing address for shipping
		$address1 = $data['billing_address_1'];
		$address2 = $data['billing_address_2'] ?? '';
		$city = $data['billing_city'];
		$state = $data['billing_state'];
		$zip = $data['billing_postcode'];
	} else {
		// Use shipping address
		$address1 = $data['shipping_address_1'] ?? '';
		$address2 = $data['shipping_address_2'] ?? '';
		$city = $data['shipping_city'] ?? '';
		$state = $data['shipping_state'] ?? '';
		$zip = $data['shipping_postcode'] ?? '';
	}
	
	// Only validate US addresses
	$country = $data['shipping_country'] ?? $data['billing_country'] ?? '';
	if ( $country !== 'US' ) {
		return;
	}
	
	// Skip if address is empty
	if ( empty( $address1 ) || empty( $city ) || empty( $state ) || empty( $zip ) ) {
		return;
	}
	
	// Validate address
	$validation = wtcc_validate_usps_address( $address1, $address2, $city, $state, $zip );
	
	if ( is_wp_error( $validation ) ) {
		// Log error but don't block checkout (fail gracefully)
		error_log( 'Inkfinit Shipping: Address validation error - ' . $validation->get_error_message() );
		return;
	}
	
	if ( ! $validation['valid'] ) {
		$errors->add( 'shipping', __( 'The shipping address could not be verified. Please check your address and try again.', 'wtc-shipping' ) );
		return;
	}
	
	// Check if address was standardized
	$original = $validation['original'];
	$standardized = $validation['standardized'];
	
	$address_changed = (
		strtolower( trim( $original['address1'] ) ) !== strtolower( trim( $standardized['address1'] ) ) ||
		strtolower( trim( $original['city'] ) ) !== strtolower( trim( $standardized['city'] ) ) ||
		strtolower( trim( $original['state'] ) ) !== strtolower( trim( $standardized['state'] ) ) ||
		substr( $original['zip'], 0, 5 ) !== substr( $standardized['zip'], 0, 5 )
	);
	
	if ( $address_changed ) {
		// Store suggestion in session for display
		WC()->session->set( 'wtcc_address_suggestion', $standardized );
		
		// Add notice with suggestion
		$suggestion_text = sprintf(
			__( 'Address suggestion: %s, %s, %s %s', 'wtc-shipping' ),
			$standardized['address1'],
			$standardized['city'],
			$standardized['state'],
			$standardized['zip']
		);
		
		wc_add_notice( $suggestion_text, 'notice' );
	}
}

/**
 * Add address suggestion display to checkout
 */
add_action( 'woocommerce_review_order_before_payment', 'wtcc_display_address_suggestion' );
function wtcc_display_address_suggestion() {
	$validation_enabled = get_option( 'wtcc_address_validation_enabled', 'no' ) === 'yes';
	
	if ( ! $validation_enabled ) {
		return;
	}
	
	$suggestion = WC()->session->get( 'wtcc_address_suggestion' );
	
	if ( empty( $suggestion ) ) {
		return;
	}
	
	?>
	<div class="woocommerce-info wtcc-address-suggestion">
		<strong><?php _e( 'Address Suggestion:', 'wtc-shipping' ); ?></strong>
		<p>
			<?php echo esc_html( $suggestion['address1'] ); ?><br>
			<?php if ( ! empty( $suggestion['address2'] ) ) : ?>
				<?php echo esc_html( $suggestion['address2'] ); ?><br>
			<?php endif; ?>
			<?php echo esc_html( $suggestion['city'] ); ?>, 
			<?php echo esc_html( $suggestion['state'] ); ?> 
			<?php echo esc_html( $suggestion['zip'] ); ?>
			<?php if ( ! empty( $suggestion['zip4'] ) ) : ?>
				-<?php echo esc_html( $suggestion['zip4'] ); ?>
			<?php endif; ?>
		</p>
		<button type="button" class="button" id="wtcc-use-suggested-address">
			<?php _e( 'Use This Address', 'wtc-shipping' ); ?>
		</button>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		$('#wtcc-use-suggested-address').on('click', function() {
			var suggestion = <?php echo json_encode( $suggestion ); ?>;
			
			// Update shipping fields
			$('#shipping_address_1').val(suggestion.address1).trigger('change');
			$('#shipping_address_2').val(suggestion.address2 || '').trigger('change');
			$('#shipping_city').val(suggestion.city).trigger('change');
			$('#shipping_state').val(suggestion.state).trigger('change');
			$('#shipping_postcode').val(suggestion.zip).trigger('change');
			
			// Hide suggestion box
			$('.wtcc-address-suggestion').fadeOut();
			
			// Update checkout
			$('body').trigger('update_checkout');
		});
	});
	</script>
	<?php
}

/**
 * Clear address suggestion after order placement
 */
add_action( 'woocommerce_thankyou', 'wtcc_clear_address_suggestion' );
function wtcc_clear_address_suggestion() {
	WC()->session->__unset( 'wtcc_address_suggestion' );
}

/**
 * Add real-time address validation (AJAX)
 */
add_action( 'wp_ajax_wtcc_validate_address', 'wtcc_ajax_validate_address' );
add_action( 'wp_ajax_nopriv_wtcc_validate_address', 'wtcc_ajax_validate_address' );
function wtcc_ajax_validate_address() {
	check_ajax_referer( 'wtcc_validate_address', 'nonce' );
	
	$address1 = sanitize_text_field( $_POST['address1'] ?? '' );
	$address2 = sanitize_text_field( $_POST['address2'] ?? '' );
	$city = sanitize_text_field( $_POST['city'] ?? '' );
	$state = sanitize_text_field( $_POST['state'] ?? '' );
	$zip = sanitize_text_field( $_POST['zip'] ?? '' );
	
	if ( empty( $address1 ) || empty( $city ) || empty( $state ) || empty( $zip ) ) {
		wp_send_json_error( array( 'message' => 'Incomplete address' ) );
	}
	
	$validation = wtcc_validate_usps_address( $address1, $address2, $city, $state, $zip );
	
	if ( is_wp_error( $validation ) ) {
		wp_send_json_error( array( 
			'message' => $validation->get_error_message() 
		) );
	}
	
	wp_send_json_success( $validation );
}

/**
 * Add address validation button to checkout
 */
add_action( 'woocommerce_after_checkout_shipping_form', 'wtcc_add_address_validation_button' );
function wtcc_add_address_validation_button() {
	$validation_enabled = get_option( 'wtcc_address_validation_enabled', 'no' ) === 'yes';
	$realtime_enabled = get_option( 'wtcc_address_validation_realtime', 'no' ) === 'yes';
	
	if ( ! $validation_enabled || ! $realtime_enabled ) {
		return;
	}
	?>
	<p class="form-row">
		<button type="button" class="button" id="wtcc-validate-address-btn">
			<?php _e( 'Verify Address', 'wtc-shipping' ); ?>
		</button>
		<span id="wtcc-validation-result"></span>
	</p>
	
	<script>
	jQuery(document).ready(function($) {
		$('#wtcc-validate-address-btn').on('click', function() {
			var btn = $(this);
			var result = $('#wtcc-validation-result');
			
			btn.prop('disabled', true).text('<?php _e( 'Validating...', 'wtc-shipping' ); ?>');
			result.html('<span class="spinner is-active"></span>');
			
			$.ajax({
				url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
				type: 'POST',
				data: {
					action: 'wtcc_validate_address',
					nonce: '<?php echo wp_create_nonce( 'wtcc_validate_address' ); ?>',
					address1: $('#shipping_address_1').val(),
					address2: $('#shipping_address_2').val(),
					city: $('#shipping_city').val(),
					state: $('#shipping_state').val(),
					zip: $('#shipping_postcode').val()
				},
				success: function(response) {
					if (response.success) {
						if (response.data.valid) {
							result.html('<span>✓ Address verified</span>');
							
							// Show standardized address if different
							var std = response.data.standardized;
							if (std && response.data.deliverable === 'DELIVERABLE') {
								var msg = 'Suggested: ' + std.address1 + ', ' + std.city + ', ' + std.state + ' ' + std.zip;
								result.append('<br><small>' + msg + '</small>');
							}
						} else {
							result.html('<span>⚠ Address could not be verified</span>');
						}
					} else {
						result.html('<span>Error: ' + response.data.message + '</span>');
					}
				},
				error: function() {
					result.html('<span>Network error</span>');
				},
				complete: function() {
					btn.prop('disabled', false).text('<?php _e( 'Verify Address', 'wtc-shipping' ); ?>');
				}
			});
		});
	});
	</script>
	<?php
}

/**
 * Add address validation settings
 */
add_action( 'admin_init', 'wtcc_register_address_validation_settings' );
function wtcc_register_address_validation_settings() {
	register_setting( 'wtcc_address_validation', 'wtcc_address_validation_enabled' );
	register_setting( 'wtcc_address_validation', 'wtcc_address_validation_realtime' );
	register_setting( 'wtcc_address_validation', 'wtcc_address_validation_strict' );
}

/**
 * Add settings section to USPS API page
 */
add_action( 'wtcc_after_usps_api_settings', 'wtcc_render_address_validation_settings' );
function wtcc_render_address_validation_settings() {
	$enabled = get_option( 'wtcc_address_validation_enabled', 'no' ) === 'yes';
	$realtime = get_option( 'wtcc_address_validation_realtime', 'no' ) === 'yes';
	$strict = get_option( 'wtcc_address_validation_strict', 'no' ) === 'yes';
	?>
	<div class="postbox">
		<div class="postbox-header">
			<h2>Address Validation</h2>
		</div>
		<div class="inside">
			<table class="form-table">
				<tr>
					<th scope="row">Enable Address Validation</th>
					<td>
						<label>
							<input type="checkbox" name="wtcc_address_validation_enabled" value="yes" <?php checked( $enabled ); ?>>
							Validate shipping addresses at checkout using USPS
						</label>
						<p class="description">Ensures accurate delivery addresses before order placement</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Real-Time Validation</th>
					<td>
						<label>
							<input type="checkbox" name="wtcc_address_validation_realtime" value="yes" <?php checked( $realtime ); ?>>
							Show "Verify Address" button at checkout
						</label>
						<p class="description">Allows customers to validate their address before completing purchase</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">Strict Mode</th>
					<td>
						<label>
							<input type="checkbox" name="wtcc_address_validation_strict" value="yes" <?php checked( $strict ); ?>>
							Block checkout if address cannot be validated
						</label>
						<p class="description">⚠️ Warning: May prevent legitimate orders if USPS API is unavailable</p>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}

/**
 * Save address validation settings with USPS API settings
 */
add_action( 'admin_init', 'wtcc_save_address_validation_settings' );
function wtcc_save_address_validation_settings() {
	if ( ! isset( $_POST['wtc_save_usps_api'] ) ) {
		return;
	}
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wtc_usps_api_nonce' ) ) {
		return;
	}
	
	update_option( 'wtcc_address_validation_enabled', isset( $_POST['wtcc_address_validation_enabled'] ) ? 'yes' : 'no' );
	update_option( 'wtcc_address_validation_realtime', isset( $_POST['wtcc_address_validation_realtime'] ) ? 'yes' : 'no' );
	update_option( 'wtcc_address_validation_strict', isset( $_POST['wtcc_address_validation_strict'] ) ? 'yes' : 'no' );
}
