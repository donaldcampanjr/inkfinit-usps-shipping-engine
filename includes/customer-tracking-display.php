<?php
/**
 * Customer Tracking Display
 * 
 * Shows USPS tracking information in customer My Account area
 * Styled nicely to encourage return visits
 * 
 * @package WTC_Shipping
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add tracking section to order details in My Account
 */
add_action( 'woocommerce_order_details_after_order_table', 'wtcc_display_order_tracking', 10, 1 );
function wtcc_display_order_tracking( $order ) {
	if ( ! $order ) {
		return;
	}
	
	$tracking_number = $order->get_meta( '_wtcc_tracking_number' );
	$tracking_carrier = $order->get_meta( '_wtcc_tracking_carrier' );
	
	if ( empty( $tracking_number ) ) {
		return;
	}
	
	// Get tracking info from USPS if available
	$tracking_info = null;
	if ( function_exists( 'wtcc_usps_get_tracking' ) && 'usps' === strtolower( $tracking_carrier ) ) {
		$tracking_info = wtcc_usps_get_tracking( $tracking_number );
	}
	
	?>
	<section class="wtcc-tracking-section">
		<h2>
			<span class="dashicons dashicons-location"></span>
			Track Your Package
		</h2>
		
		<div class="wtcc-tracking-card">
			<!-- Accent stripe -->
			<div class="wtcc-tracking-card-accent"></div>
			
			<!-- Tracking Number -->
			<div class="wtcc-tracking-number-container">
				<div>
					<span class="wtcc-tracking-number-label">
						<?php echo esc_html( strtoupper( $tracking_carrier ?: 'USPS' ) ); ?> Tracking Number
					</span>
					<div class="wtcc-tracking-number">
						<?php echo esc_html( $tracking_number ); ?>
					</div>
				</div>
				
				<a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=<?php echo esc_attr( $tracking_number ); ?>" 
				   target="_blank" 
				   rel="noopener noreferrer"
				   class="wtcc-tracking-link">
					   Track on USPS.com
					   <span class="dashicons dashicons-external"></span>
				</a>
			</div>
			
			<?php if ( $tracking_info && ! is_wp_error( $tracking_info ) ) : ?>
				<!-- Status Summary -->
				<div class="wtcc-tracking-status-container">
					<div class="wtcc-tracking-status-summary">
						<span class="dashicons dashicons-star-filled wtcc-status-icon"></span>
						<div>
							<div class="wtcc-status-text-label">Current Status</div>
							<div class="wtcc-status-text-summary">
								<?php echo esc_html( $tracking_info['summary'] ); ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Tracking History -->
				<?php if ( ! empty( $tracking_info['history'] ) ) : ?>
					<ol class="wtcc-tracking-history">
						<?php foreach ( $tracking_info['history'] as $index => $event ) : ?>
							<li class="wtcc-tracking-event <?php echo ( 0 === $index ) ? 'wtcc-current-event' : ''; ?>">
								<div class="wtcc-tracking-event-dot"></div>
								<div class="wtcc-tracking-event-description">
									<?php echo esc_html( $event['description'] ); ?>
								</div>
								<div class="wtcc-tracking-event-location">
									<?php echo esc_html( $event['location'] ); ?>
								</div>
								<div class="wtcc-tracking-event-date">
									<?php echo esc_html( $event['date'] ); ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>

			<?php elseif ( is_wp_error( $tracking_info ) ) : ?>
				<div class="wtcc-tracking-error">
					<p>Could not retrieve tracking details at this time. Please try again later or use the link above.</p>
					<!-- <p>Error: <?php echo esc_html( $tracking_info->get_error_message() ); ?></p> -->
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Add tracking to order emails
 */
add_action( 'woocommerce_email_order_meta', 'wtcc_add_tracking_to_emails', 20, 3 );
function wtcc_add_tracking_to_emails( $order, $sent_to_admin, $plain_text ) {
	if ( $sent_to_admin ) {
		return; // Don't add to admin emails
	}
	
	$tracking_number = $order->get_meta( '_wtcc_tracking_number' );
	$tracking_carrier = $order->get_meta( '_wtcc_tracking_carrier' );
	
	if ( empty( $tracking_number ) ) {
		return;
	}
	
	if ( $plain_text ) {
		echo "\n\n";
		echo "TRACKING INFORMATION\n";
		echo "====================\n";
		echo "Carrier: " . strtoupper( $tracking_carrier ?: 'USPS' ) . "\n";
		echo "Tracking Number: " . $tracking_number . "\n";
		echo "Track at: https://tools.usps.com/go/TrackConfirmAction?tLabels=" . $tracking_number . "\n";
	} else {
		?>
		<div style="margin: 20px 0; padding: 20px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #0fb47e;">
			<h3 style="margin: 0 0 10px 0; color: #002868;"> Track Your Package</h3>
			<p style="margin: 0 0 10px 0;">
				<strong>Carrier:</strong> <?php echo esc_html( strtoupper( $tracking_carrier ?: 'USPS' ) ); ?><br>
				<strong>Tracking Number:</strong> <?php echo esc_html( $tracking_number ); ?>
			</p>
			<a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=<?php echo esc_attr( $tracking_number ); ?>" 
			   style="display: inline-block; background: #002868; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold;">
				Track Package →
			</a>
		</div>
		<?php
	}
}

/**
 * Add tracking number field to order edit page (admin)
 */
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'wtcc_admin_tracking_field', 10, 1 );
function wtcc_admin_tracking_field( $order ) {
	$tracking_number = $order->get_meta( '_wtcc_tracking_number' );
	$tracking_carrier = $order->get_meta( '_wtcc_tracking_carrier' );
	?>
	<div class="address" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
		<h3 style="display: flex; align-items: center; gap: 8px;">
			<span class="dashicons dashicons-location" style="color: #0fb47e;"></span>
			Shipment Tracking
		</h3>
		<p>
			<label for="wtcc_tracking_carrier"><strong>Carrier:</strong></label><br>
			<select name="wtcc_tracking_carrier" id="wtcc_tracking_carrier" style="width: 100%; margin-top: 4px;">
				<option value="usps" <?php selected( $tracking_carrier, 'usps' ); ?>>USPS</option>
				<option value="ups" <?php selected( $tracking_carrier, 'ups' ); ?>>UPS</option>
				<option value="fedex" <?php selected( $tracking_carrier, 'fedex' ); ?>>FedEx</option>
				<option value="dhl" <?php selected( $tracking_carrier, 'dhl' ); ?>>DHL</option>
			</select>
		</p>
		<p>
			<label for="wtcc_tracking_number"><strong>Tracking Number:</strong></label><br>
			<input type="text" 
				   name="wtcc_tracking_number" 
				   id="wtcc_tracking_number" 
				   value="<?php echo esc_attr( $tracking_number ); ?>"
				   style="width: 100%; margin-top: 4px;"
				   placeholder="Enter tracking number">
		</p>
		<?php if ( $tracking_number ) : ?>
			<p>
				<a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=<?php echo esc_attr( $tracking_number ); ?>" 
				   target="_blank"
				   class="button">
					View on USPS.com
				</a>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Save tracking number from order edit
 */
add_action( 'woocommerce_process_shop_order_meta', 'wtcc_save_admin_tracking_field', 10, 1 );
function wtcc_save_admin_tracking_field( $order_id ) {
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		return;
	}
	
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	
	if ( isset( $_POST['wtcc_tracking_number'] ) ) {
		$tracking = sanitize_text_field( $_POST['wtcc_tracking_number'] );
		$order->update_meta_data( '_wtcc_tracking_number', $tracking );
	}
	
	if ( isset( $_POST['wtcc_tracking_carrier'] ) ) {
		$carrier = sanitize_text_field( $_POST['wtcc_tracking_carrier'] );
		$order->update_meta_data( '_wtcc_tracking_carrier', $carrier );
	}
	
	$order->save();
}

/**
 * Add tracking column to orders list
 */
add_filter( 'manage_edit-shop_order_columns', 'wtcc_add_tracking_column', 20 );
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'wtcc_add_tracking_column', 20 );
function wtcc_add_tracking_column( $columns ) {
	$new_columns = array();
	
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		
		if ( 'order_status' === $key ) {
			$new_columns['wtcc_tracking'] = '<span class="dashicons dashicons-location" title="Tracking"></span>';
		}
	}
	
	return $new_columns;
}

/**
 * Render tracking column
 */
add_action( 'manage_shop_order_posts_custom_column', 'wtcc_render_tracking_column', 10, 2 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'wtcc_render_tracking_column_hpos', 10, 2 );

function wtcc_render_tracking_column( $column, $post_id ) {
	if ( 'wtcc_tracking' !== $column ) {
		return;
	}
	
	$order = wc_get_order( $post_id );
	wtcc_output_tracking_column_content( $order );
}

function wtcc_render_tracking_column_hpos( $column, $order ) {
	if ( 'wtcc_tracking' !== $column ) {
		return;
	}
	
	wtcc_output_tracking_column_content( $order );
}

function wtcc_output_tracking_column_content( $order ) {
	if ( ! $order ) {
		return;
	}
	
	$tracking = $order->get_meta( '_wtcc_tracking_number' );
	
	if ( $tracking ) {
		echo '<span class="dashicons dashicons-yes-alt" style="color: #10b981;" title="' . esc_attr( $tracking ) . '"></span>';
	} else {
		echo '<span class="dashicons dashicons-minus" style="color: #cbd5e1;" title="No tracking"></span>';
	}
}
