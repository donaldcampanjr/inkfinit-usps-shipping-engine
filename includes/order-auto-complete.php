<?php
/**
 * Order Auto-Completion After Label Print
 * 
 * Automatically marks orders as complete and sends email when shipping label is printed
 * Feature Request: 4 votes on WooCommerce - "save steps in the long run"
 * 
 * @package WTC_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auto-complete order after label is printed
 */
add_action( 'wtcc_label_printed', 'wtcc_auto_complete_after_label', 10, 2 );
function wtcc_auto_complete_after_label( $order_id, $tracking_number ) {
	// Check if auto-complete is enabled
	if ( get_option( 'wtcc_auto_complete_on_label', 'no' ) !== 'yes' ) {
		return;
	}
	
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		return;
	}
	
	// Only process orders that are in processing status
	if ( $order->get_status() !== 'processing' ) {
		return;
	}
	
	// Add tracking number to order
	$order->update_meta_data( '_wtcc_tracking_number', $tracking_number );
	$order->update_meta_data( '_wtcc_label_printed_date', current_time( 'mysql' ) );
	
	// Check if we should auto-complete
	$auto_action = get_option( 'wtcc_auto_action_on_label', 'complete' );
	
	switch ( $auto_action ) {
		case 'complete':
			// Mark as completed
			$order->update_status( 'completed', __( 'Order auto-completed: Shipping label printed.', 'wtc-shipping' ) );
			break;
			
		case 'shipped':
			// Mark as shipped (custom status if exists, otherwise completed)
			if ( in_array( 'wc-shipped', array_keys( wc_get_order_statuses() ), true ) ) {
				$order->update_status( 'shipped', __( 'Shipping label printed.', 'wtc-shipping' ) );
			} else {
				$order->update_status( 'completed', __( 'Order auto-completed: Shipping label printed.', 'wtc-shipping' ) );
			}
			break;
			
		case 'notify_only':
			// Just send tracking notification, don't change status
			do_action( 'wtcc_send_tracking_email', $order_id, $tracking_number );
			break;
	}
	
	$order->save();
	
	// Trigger email if enabled
	if ( get_option( 'wtcc_auto_send_tracking_email', 'yes' ) === 'yes' ) {
		do_action( 'wtcc_send_tracking_email', $order_id, $tracking_number );
	}
}

/**
 * Register auto-complete settings
 */
add_action( 'admin_init', 'wtcc_register_auto_complete_settings' );
function wtcc_register_auto_complete_settings() {
	register_setting( 'wtcc_shipping_settings', 'wtcc_auto_complete_on_label' );
	register_setting( 'wtcc_shipping_settings', 'wtcc_auto_action_on_label' );
	register_setting( 'wtcc_shipping_settings', 'wtcc_auto_send_tracking_email' );
}

/**
 * Add settings section for auto-complete
 */
function wtcc_render_auto_complete_settings() {
	$auto_complete = get_option( 'wtcc_auto_complete_on_label', 'no' );
	$auto_action = get_option( 'wtcc_auto_action_on_label', 'complete' );
	$auto_email = get_option( 'wtcc_auto_send_tracking_email', 'yes' );
	
	?>
	<div class="wtcc-card">
		<h3 class="wtcc-card-title">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'Order Auto-Completion', 'wtc-shipping' ); ?>
		</h3>
		<div class="wtcc-card-content">
			<p class="description wtcc-mb-20">
				<?php esc_html_e( 'Save time by automatically updating orders when you print shipping labels.', 'wtc-shipping' ); ?>
			</p>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Auto-Actions', 'wtc-shipping' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wtcc_auto_complete_on_label" value="yes" <?php checked( $auto_complete, 'yes' ); ?>>
							<?php esc_html_e( 'Automatically process orders when label is printed', 'wtc-shipping' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Action on Label Print', 'wtc-shipping' ); ?></th>
					<td>
						<select name="wtcc_auto_action_on_label">
							<option value="complete" <?php selected( $auto_action, 'complete' ); ?>>
								<?php esc_html_e( 'Mark order as Completed', 'wtc-shipping' ); ?>
							</option>
							<option value="shipped" <?php selected( $auto_action, 'shipped' ); ?>>
								<?php esc_html_e( 'Mark order as Shipped (if status exists)', 'wtc-shipping' ); ?>
							</option>
							<option value="notify_only" <?php selected( $auto_action, 'notify_only' ); ?>>
								<?php esc_html_e( 'Send tracking email only (don\'t change status)', 'wtc-shipping' ); ?>
							</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Send Tracking Email', 'wtc-shipping' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wtcc_auto_send_tracking_email" value="yes" <?php checked( $auto_email, 'yes' ); ?>>
							<?php esc_html_e( 'Automatically send tracking number to customer', 'wtc-shipping' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Customer will receive an email with tracking number and link to track their package.', 'wtc-shipping' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}

/**
 * Bulk action: Print labels and complete orders
 */
add_filter( 'bulk_actions-edit-shop_order', 'wtcc_add_bulk_label_action' );
function wtcc_add_bulk_label_action( $bulk_actions ) {
	$bulk_actions['wtcc_print_labels_complete'] = __( 'Print Labels & Complete Orders', 'wtc-shipping' );
	return $bulk_actions;
}

/**
 * Handle bulk label print action
 */
add_filter( 'handle_bulk_actions-edit-shop_order', 'wtcc_handle_bulk_label_action', 10, 3 );
function wtcc_handle_bulk_label_action( $redirect_to, $action, $post_ids ) {
	if ( $action !== 'wtcc_print_labels_complete' ) {
		return $redirect_to;
	}
	
	// Capability check
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		return $redirect_to;
	}
	
	$processed = 0;
	$order_ids = array();
	
	foreach ( $post_ids as $post_id ) {
		$post_id = absint( $post_id );
		$order = wc_get_order( $post_id );
		
		if ( ! $order || $order->get_status() !== 'processing' ) {
			continue;
		}
		
		// Queue for label printing
		update_post_meta( $post_id, '_wtcc_pending_label', 'yes' );
		$order_ids[] = $post_id;
		$processed++;
	}
	
	// Redirect to label printing page with selected orders
	$redirect_to = add_query_arg( array(
		'wtcc_bulk_labels' => implode( ',', array_map( 'absint', $order_ids ) ),
		'wtcc_processed'   => absint( $processed ),
	), admin_url( 'admin.php?page=wtc-core-shipping-labels' ) );
	
	return $redirect_to;
}

/**
 * Add quick action button to order list
 */
add_filter( 'woocommerce_admin_order_actions', 'wtcc_add_quick_label_action', 10, 2 );
function wtcc_add_quick_label_action( $actions, $order ) {
	if ( $order->get_status() === 'processing' ) {
		$actions['wtcc_print_label'] = array(
			'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=wtcc_quick_print_label&order_id=' . $order->get_id() ), 'wtcc_quick_print' ),
			'name'   => __( 'Print Label', 'wtc-shipping' ),
			'action' => 'wtcc_print_label',
		);
	}
	
	return $actions;
}

/**
 * AJAX handler for quick print label
 */
add_action( 'wp_ajax_wtcc_quick_print_label', 'wtcc_ajax_quick_print_label' );
function wtcc_ajax_quick_print_label() {
	// Verify nonce
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wtcc_quick_print' ) ) {
		wp_die( esc_html__( 'Security check failed', 'wtc-shipping' ), 403 );
	}
	
	// Check capabilities
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_die( esc_html__( 'Permission denied', 'wtc-shipping' ), 403 );
	}
	
	$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	
	if ( ! $order_id ) {
		wp_die( esc_html__( 'Invalid order ID', 'wtc-shipping' ), 400 );
	}
	
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		wp_die( esc_html__( 'Order not found', 'wtc-shipping' ), 404 );
	}
	
	// Redirect to label printing page
	wp_safe_redirect( add_query_arg( array(
		'page'     => 'wtc-core-shipping-labels',
		'order_id' => $order_id,
	), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Add CSS for quick action button
 */
add_action( 'admin_head', 'wtcc_quick_action_css' );
function wtcc_quick_action_css() {
	?>
	<style>
		.wc-action-button-wtcc_print_label::after {
			font-family: dashicons !important;
			content: "\f497" !important;
		}
	</style>
	<?php
}

/**
 * One-click order completion from order page
 */
add_action( 'woocommerce_order_actions', 'wtcc_add_order_action' );
function wtcc_add_order_action( $actions ) {
	$actions['wtcc_print_and_complete'] = __( 'Print Label & Complete Order', 'wtc-shipping' );
	return $actions;
}

/**
 * Handle order action
 */
add_action( 'woocommerce_order_action_wtcc_print_and_complete', 'wtcc_handle_order_action' );
function wtcc_handle_order_action( $order ) {
	// Redirect to label printing with auto-complete flag
	wp_redirect( add_query_arg( array(
		'page'         => 'wtc-core-shipping-labels',
		'order_id'     => $order->get_id(),
		'auto_complete' => 'yes',
	), admin_url( 'admin.php' ) ) );
	exit;
}
