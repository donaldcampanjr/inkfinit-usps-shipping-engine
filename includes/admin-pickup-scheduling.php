<?php
/**
 * USPS Carrier Pickup - Admin UI
 * Interface for scheduling USPS package pickups
 * 
 * @package WTC_Shipping
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add pickup metabox to order page
 */
add_action( 'add_meta_boxes', 'wtcc_add_pickup_metabox', 30 );
function wtcc_add_pickup_metabox() {
	if ( function_exists( 'wtcc_is_pro' ) && ! wtcc_is_pro() ) {
		return;
	}
	$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) &&
	          wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';
	
	add_meta_box(
		'wtcc_pickup_scheduling',
		__( 'USPS Carrier Pickup', 'wtc-shipping' ),
		'wtcc_pickup_metabox_content',
		$screen,
		'side',
		'default'
	);
}

/**
 * Render pickup metabox content
 */
function wtcc_pickup_metabox_content( $post_or_order ) {
	$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;
	
	if ( ! $order ) {
		return;
	}
	
	$order_id = $order->get_id();
	wp_nonce_field( 'wtcc_schedule_pickup', 'wtcc_pickup_nonce' );

	echo '<div id="wtcc-pickup-metabox-content-wrapper" data-order-id="' . esc_attr( $order_id ) . '">';
	echo wtcc_render_pickup_metabox_inner_content( $order );
	echo '</div>';
}

/**
 * Renders the actual content of the metabox. Can be called directly or via AJAX.
 *
 * @param WC_Order $order The order object.
 * @return string The HTML content.
 */
function wtcc_render_pickup_metabox_inner_content( $order ) {
	ob_start();

	$order_id = $order->get_id();
	$pickup_confirmation = $order->get_meta( '_wtcc_pickup_confirmation' );
	$pickup_date = $order->get_meta( '_wtcc_pickup_date' );
	$pickup_status = $order->get_meta( '_wtcc_pickup_status' );
	$has_label = $order->get_meta( '_wtcc_tracking_number' );

	if ( $pickup_confirmation && $pickup_status !== 'cancelled' ) {
		// Pickup already scheduled
		?>
		<div class="notice notice-success notice-alt inline wtcc-pickup-scheduled">
			<p>
				<strong><?php esc_html_e( '✓ Pickup Scheduled', 'wtc-shipping' ); ?></strong>
			</p>
			<ul>
				<li>
					<strong><?php esc_html_e( 'Date:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( wp_date( 'F j, Y', strtotime( $pickup_date ) ) ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Confirmation:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( $pickup_confirmation ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Status:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( ucfirst( $pickup_status ) ); ?>
				</li>
			</ul>
		</div>
		
		<button type="button" class="button button-secondary wtcc-cancel-pickup" 
		        data-confirmation="<?php echo esc_attr( $pickup_confirmation ); ?>">
			<?php esc_html_e( 'Cancel Pickup', 'wtc-shipping' ); ?>
		</button>
		<?php
	} else {
		// No pickup scheduled or cancelled
		if ( ! $has_label ) {
			?>
			<div class="notice notice-warning notice-alt inline">
				<p>
					<?php esc_html_e( '⚠️ Create a shipping label first before scheduling pickup.', 'wtc-shipping' ); ?>
				</p>
			</div>
			<?php
			return ob_get_clean();
		}
		
		if ( $pickup_status === 'cancelled' ) {
			?>
			<div class="notice notice-info notice-alt inline">
				<p><?php esc_html_e( 'A previous pickup request for this order was cancelled.', 'wtc-shipping' ); ?></p>
			</div>
			<?php
		}

		// Get origin address
		$origin_zip = get_option( 'wtcc_origin_zip', '' );
		$origin_address = get_option( 'wtcc_origin_address', '' );
		$origin_city = get_option( 'wtcc_origin_city', '' );
		$origin_state = get_option( 'wtcc_origin_state', '' );
		$origin_phone = get_option( 'wtcc_origin_phone', '' );
		
		// Default pickup date (tomorrow)
		$default_date = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		?>
		<div class="wtcc-pickup-form">
			<p class="description">
				<?php esc_html_e( 'Schedule USPS to pick up this package from your location.', 'wtc-shipping' ); ?>
			</p>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th><label for="wtcc_pickup_date"><?php esc_html_e( 'Pickup Date', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
						<td>
							<input type="date" 
								   id="wtcc_pickup_date" 
								   name="wtcc_pickup_date" 
								   value="<?php echo esc_attr( $default_date ); ?>"
								   min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"
								   class="regular-text" />
						</td>
					</tr>
					
					<tr>
						<th><label for="wtcc_package_count"><?php esc_html_e( 'Number of Packages', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
						<td>
							<input type="number" 
								   id="wtcc_package_count" 
								   name="wtcc_package_count" 
								   value="1" 
								   min="1"
								   class="small-text" />
						</td>
					</tr>
					
					<tr>
						<th><label for="wtcc_total_weight"><?php esc_html_e( 'Total Weight (lbs)', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
						<td>
							<input type="number" 
								   id="wtcc_total_weight" 
								   name="wtcc_total_weight" 
								   value="<?php echo esc_attr( $order->get_meta( '_wtcc_total_weight' ) ?: '1' ); ?>" 
								   min="0.1"
								   step="0.1"
								   class="small-text" />
						</td>
					</tr>
					
					<tr>
						<th><label for="wtcc_package_location"><?php esc_html_e( 'Package Location', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
						<td>
							<select id="wtcc_package_location" name="wtcc_package_location" class="regular-text">
								<?php foreach ( wtcc_get_pickup_locations() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					
					<tr>
						<th><label for="wtcc_special_instructions"><?php esc_html_e( 'Special Instructions', 'wtc-shipping' ); ?></label></th>
						<td>
							<textarea id="wtcc_special_instructions" 
									  name="wtcc_special_instructions" 
									  rows="3"
									  class="widefat"
									  placeholder="<?php esc_attr_e( 'Optional pickup instructions...', 'wtc-shipping' ); ?>"></textarea>
						</td>
					</tr>
					
					<tr>
						<th><?php esc_html_e( 'Pickup Address', 'wtc-shipping' ); ?></th>
						<td>
							<fieldset>
								<label for="wtcc_use_custom_address">
									<input type="checkbox" id="wtcc_use_custom_address" name="wtcc_use_custom_address" value="1" />
									<?php esc_html_e( 'Use different pickup address', 'wtc-shipping' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Default:', 'wtc-shipping' ); ?> <?php echo esc_html( $origin_address ?: 'Not configured' ); ?>, 
									<?php echo esc_html( $origin_city ); ?> <?php echo esc_html( $origin_state ); ?> 
									<?php echo esc_html( $origin_zip ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			
			<div id="wtcc_custom_address_fields" class="hidden">
				<table class="form-table">
					<tbody>
						<tr>
							<th><label for="wtcc_custom_address"><?php esc_html_e( 'Street Address', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
							<td>
								<input type="text" 
									   id="wtcc_custom_address" 
									   name="wtcc_custom_address" 
									   value="<?php echo esc_attr( $origin_address ); ?>"
									   class="regular-text" />
							</td>
						</tr>
						
						<tr>
							<th><label for="wtcc_custom_city"><?php esc_html_e( 'City', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
							<td>
								<input type="text" 
									   id="wtcc_custom_city" 
									   name="wtcc_custom_city" 
									   value="<?php echo esc_attr( $origin_city ); ?>"
									   class="regular-text" />
							</td>
						</tr>
						
						<tr>
							<th><label for="wtcc_custom_state"><?php esc_html_e( 'State', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
							<td>
								<input type="text" 
									   id="wtcc_custom_state" 
									   name="wtcc_custom_state" 
									   value="<?php echo esc_attr( $origin_state ); ?>"
									   maxlength="2"
									   class="small-text" />
							</td>
						</tr>
						
						<tr>
							<th><label for="wtcc_custom_zip"><?php esc_html_e( 'ZIP Code', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
							<td>
								<input type="text" 
									   id="wtcc_custom_zip" 
									   name="wtcc_custom_zip" 
									   value="<?php echo esc_attr( $origin_zip ); ?>"
									   class="small-text" />
							</td>
						</tr>
						
						<tr>
							<th><label for="wtcc_custom_phone"><?php esc_html_e( 'Phone', 'wtc-shipping' ); ?> <span class="required">*</span></label></th>
							<td>
								<input type="tel" 
									   id="wtcc_custom_phone" 
									   name="wtcc_custom_phone" 
									   value="<?php echo esc_attr( $origin_phone ); ?>"
									   class="regular-text" />
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			
			<p class="submit">
				<button type="button" class="button button-primary wtcc-schedule-pickup">
					<?php esc_html_e( 'Schedule Pickup', 'wtc-shipping' ); ?>
				</button>
				<span class="spinner"></span>
			</p>
			
			<p class="description">
				<strong><?php esc_html_e( 'Note:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'USPS pickups are free when scheduled with regular mail delivery. Pickup availability varies by location.', 'wtc-shipping' ); ?>
			</p>
		</div>
		<?php
	}
	return ob_get_clean();
}

/**
 * AJAX handler - Schedule pickup
 */
add_action( 'wp_ajax_wtcc_schedule_pickup', 'wtcc_ajax_schedule_pickup' );
function wtcc_ajax_schedule_pickup() {
	check_ajax_referer( 'wtcc_schedule_pickup', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied', 'wtc-shipping' ) ) );
	}
	
	$order_id = absint( $_POST['order_id'] ?? 0 );
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => __( 'Order not found', 'wtc-shipping' ) ) );
	}
	
	// Get origin address (use custom if provided, otherwise default)
	$use_custom = ! empty( $_POST['use_custom_address'] );
	
	if ( $use_custom ) {
		$origin_address = sanitize_text_field( $_POST['custom_address'] ?? '' );
		$origin_city = sanitize_text_field( $_POST['custom_city'] ?? '' );
		$origin_state = sanitize_text_field( $_POST['custom_state'] ?? '' );
		$origin_zip = sanitize_text_field( $_POST['custom_zip'] ?? '' );
		$origin_phone = sanitize_text_field( $_POST['custom_phone'] ?? '' );
		$company_name = get_option( 'wtcc_company_name', get_bloginfo( 'name' ) );
		
		// Basic validation
		if ( empty($origin_address) || empty($origin_city) || empty($origin_state) || empty($origin_zip) || empty($origin_phone) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill all required custom address fields.', 'wtc-shipping' ) ) );
		}

		// Save custom address to order meta for reference
		$order->update_meta_data( '_wtcc_pickup_custom_address', $origin_address );
		$order->update_meta_data( '_wtcc_pickup_custom_city', $origin_city );
		$order->update_meta_data( '_wtcc_pickup_custom_state', $origin_state );
		$order->update_meta_data( '_wtcc_pickup_custom_zip', $origin_zip );
		$order->update_meta_data( '_wtcc_pickup_custom_phone', $origin_phone );
		$order->save();
	} else {
		$origin_zip = get_option( 'wtcc_origin_zip', '' );
		$origin_address = get_option( 'wtcc_origin_address', '' );
		$origin_city = get_option( 'wtcc_origin_city', '' );
		$origin_state = get_option( 'wtcc_origin_state', '' );
		$origin_phone = get_option( 'wtcc_origin_phone', '' );
		$company_name = get_option( 'wtcc_company_name', get_bloginfo( 'name' ) );

		if ( empty($origin_address) || empty($origin_city) || empty($origin_state) || empty($origin_zip) ) {
			wp_send_json_error( array( 'message' => __( 'Please configure your default pickup address in the plugin settings.', 'wtc-shipping' ) ) );
		}
	}
	
	// Get user input
	$pickup_date = sanitize_text_field( $_POST['pickup_date'] ?? '' );
	$package_count = max( 1, absint( $_POST['package_count'] ?? 1 ) );
	$total_weight = max( 0.1, floatval( $_POST['total_weight'] ?? 1 ) );
	$package_location = strtoupper( sanitize_text_field( $_POST['package_location'] ?? 'FRONT_DOOR' ) );
	$special_instructions = sanitize_textarea_field( $_POST['special_instructions'] ?? '' );
	$valid_locations = array_keys( wtcc_get_pickup_locations() );

	if ( empty( $pickup_date ) ) {
		wp_send_json_error( array( 'message' => __( 'Pickup date is required.', 'wtc-shipping' ) ) );
	}

	if ( ! in_array( $package_location, $valid_locations, true ) ) {
		$package_location = 'FRONT_DOOR';
	}

	$first_name = $order->get_shipping_first_name() ?: $order->get_billing_first_name() ?: $company_name;
	$last_name = $order->get_shipping_last_name() ?: $order->get_billing_last_name() ?: __( 'Team', 'wtc-shipping' );
	$email = $order->get_billing_email();

	$pickup_data = array(
		'firstName'           => $first_name ?: __( 'Store', 'wtc-shipping' ),
		'lastName'            => $last_name,
		'company'             => $company_name,
		'address'             => array(
			'streetAddress' => $origin_address,
			'city'          => $origin_city,
			'state'         => $origin_state,
			'zipCode'       => $origin_zip,
		),
		'packageType'         => 'PACKAGE',
		'packageCount'        => $package_count,
		'totalWeight'         => $total_weight,
		'pickupDate'          => $pickup_date,
		'packageLocation'     => $package_location,
		'specialInstructions' => $special_instructions,
		'phone'               => $origin_phone,
		'email'               => $email,
	);

	$result = wtcc_usps_schedule_pickup( $pickup_data );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wtcc_save_pickup_to_order( $order, $result );

	$updated_order = wc_get_order( $order_id );
	$html = wtcc_render_pickup_metabox_inner_content( $updated_order );

	wp_send_json_success(
		array(
			'message' => __( 'Pickup scheduled successfully.', 'wtc-shipping' ),
			'html'    => $html,
		)
	);
}
