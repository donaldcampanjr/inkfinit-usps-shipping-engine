<?php
/**
 * Inkfinit Shipping - Split Shipments UI
 * Advanced order splitting with checkbox-based line item selection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add split shipments metabox
 */
add_action( 'add_meta_boxes', 'wtcc_add_split_shipments_metabox' );
function wtcc_add_split_shipments_metabox() {
	$screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';

	add_meta_box(
		'wtcc_split_shipments',
		__( 'Split Into Multiple Shipments', 'wtc-shipping' ),
		'wtcc_split_shipments_metabox_content',
		$screen,
		'normal',
		'default'
	);
}

/**
 * Render split shipments metabox content
 */
function wtcc_split_shipments_metabox_content( $post_or_order ) {
	$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;
	
	if ( ! $order ) {
		echo '<p>' . esc_html__( 'No order found.', 'wtc-shipping' ) . '</p>';
		return;
	}
	
	wp_nonce_field( 'wtcc_split_shipments', 'wtcc_split_shipments_nonce' );
	
	echo '<div id="wtcc-split-shipments-wrapper" data-order-id="' . esc_attr( $order->get_id() ) . '">';
	echo wtcc_render_split_shipments_inner_content( $order );
	echo '</div>';

	// Hidden template for new shipments
	?>
	<template id="wtcc-shipment-template">
		<div class="postbox wtcc-shipment-group" data-shipment-id="">
			<div class="postbox-header">
				<h2 class="hndle">
					<span class="wtcc-shipment-title"><?php esc_html_e( 'Shipment #', 'wtc-shipping' ); ?><span></span></span>
				</h2>
				<div class="handle-actions">
					<button type="button" class="button-link wtcc-remove-shipment"><?php esc_html_e( 'Remove', 'wtc-shipping' ); ?></button>
				</div>
			</div>
			<div class="inside">
				<div class="wtcc-shipment-items">
					<?php
					if ( $order ) {
						foreach ( $order->get_items() as $item_id => $item ) {
							wtcc_render_shipment_item_checkbox( $item_id, $item );
						}
					}
					?>
				</div>
			</div>
		</div>
	</template>
	<?php
}

/**
 * Renders a single item checkbox for the shipment interface.
 */
function wtcc_render_shipment_item_checkbox( $item_id, $item, $shipment_id = 0, $checked = false ) {
	?>
	<label class="wtcc-item-checkbox">
		<input type="checkbox" 
			   name="wtcc_shipment_<?php echo esc_attr( $shipment_id ); ?>[]" 
			   value="<?php echo esc_attr( $item_id ); ?>"
			   class="wtcc-item-select"
			   data-item-id="<?php echo esc_attr( $item_id ); ?>"
			   <?php checked( $checked ); ?>>
		<span class="wtcc-item-details">
			<strong class="wtcc-item-name"><?php echo esc_html( $item->get_name() ); ?></strong>
			<span class="wtcc-item-meta">
				<?php esc_html_e( 'Qty:', 'wtc-shipping' ); ?> <?php echo esc_html( $item->get_quantity() ); ?> | 
				<?php if ( $item->get_meta( '_wtcc_weight' ) ) : ?>
					<?php esc_html_e( 'Weight:', 'wtc-shipping' ); ?> <?php echo esc_html( $item->get_meta( '_wtcc_weight' ) ); ?> lbs
				<?php endif; ?>
			</span>
		</span>
	</label>
	<?php
}

/**
 * Renders the inner content for the split shipment metabox.
 * Can be called via AJAX to refresh the metabox.
 */
function wtcc_render_split_shipments_inner_content( $order ) {
	ob_start();
	
	$items = $order->get_items();
	
	if ( empty( $items ) ) {
		echo '<div class="inside"><p>' . esc_html__( 'There are no items in this order to ship.', 'wtc-shipping' ) . '</p></div>';
		return ob_get_clean();
	}
	
	$shipments = $order->get_meta( '_wtcc_shipments', true ) ?: array();
	$has_shipments = ! empty( $shipments );

	?>
	<div id="wtcc-shipment-message" class="hidden"></div>

	<?php if ( $has_shipments ) : ?>
		<!-- Display existing shipments -->
		<div class="wtcc-existing-shipments">
			<p class="description">
				<?php printf( esc_html__( 'This order has been split into %d shipment(s).', 'wtc-shipping' ), count( $shipments ) ); ?>
			</p>
			
			<?php foreach ( $shipments as $index => $shipment ) : ?>
				<div class="postbox closed">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php printf( esc_html__( 'Shipment #%d', 'wtc-shipping' ), $index + 1 ); ?></span>
							<span class="notice notice-<?php echo ($shipment['status'] ?? 'pending') === 'labeled' ? 'success' : 'warning'; ?> inline wtcc-shipment-status-badge">
								<?php echo esc_html( ucfirst( $shipment['status'] ?? 'Pending' ) ); ?>
							</span>
						</h2>
						<div class="handle-actions hide-if-no-js">
							<button type="button" class="handlediv" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', 'wtc-shipping' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
						</div>
					</div>
					<div class="inside">
						<ul class="wtcc-ul-disc">
							<?php foreach ( $shipment['items'] as $item_id ) : ?>
								<?php 
								$item = $order->get_item( $item_id );
								if ( $item ) :
								?>
									<li>
										<?php echo esc_html( $item->get_name() ); ?> 
										<span class="description">× <?php echo esc_html( $item->get_quantity() ); ?></span>
									</li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
						
						<?php if ( ! empty( $shipment['tracking'] ) ) : ?>
							<p><strong><?php esc_html_e( 'Tracking:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( $shipment['tracking'] ); ?></p>
						<?php endif; ?>
						
						<div class="actions">
							<?php if ( ($shipment['status'] ?? 'pending') !== 'labeled' ) : ?>
								<button type="button" class="button wtcc-print-shipment-label" 
										data-shipment-index="<?php echo esc_attr( $index ); ?>">
									<?php esc_html_e( 'Print Label', 'wtc-shipping' ); ?>
								</button>
							<?php endif; ?>
							<button type="button" class="button-link button-link-delete wtcc-delete-shipment" 
									data-shipment-index="<?php echo esc_attr( $index ); ?>">
								<?php esc_html_e( 'Delete Shipment', 'wtc-shipping' ); ?>
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			
			<p>
				<button type="button" class="button button-secondary wtcc-reset-shipments">
					<?php esc_html_e( 'Reset All Shipments', 'wtc-shipping' ); ?>
				</button>
				<span class="spinner"></span>
			</p>
		</div>
	<?php else : ?>
		<!-- Create new shipments interface -->
		<div id="wtcc-shipments-container" class="meta-box-sortables">
			<p class="description">
				<?php esc_html_e( 'Split this order into multiple shipments. Select which items go in each package.', 'wtc-shipping' ); ?>
			</p>
			
			<div class="postbox wtcc-shipment-group" data-shipment-id="1">
				<div class="postbox-header">
					<h2 class="hndle">
						<span class="wtcc-shipment-title"><?php esc_html_e( 'Shipment #1', 'wtc-shipping' ); ?></span>
					</h2>
					<div class="handle-actions">
						<button type="button" class="button-link wtcc-remove-shipment"><?php esc_html_e( 'Remove', 'wtc-shipping' ); ?></button>
					</div>
				</div>
				<div class="inside">
					<div class="wtcc-shipment-items">
						<?php foreach ( $items as $item_id => $item ) {
							wtcc_render_shipment_item_checkbox( $item_id, $item, 1 );
						} ?>
					</div>
				</div>
			</div>
		</div>
		
		<p>
			<button type="button" class="button button-secondary" id="wtcc-add-shipment">
				<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Another Shipment', 'wtc-shipping' ); ?>
			</button>
		</p>
		
		<div class="actions">
			<button type="button" class="button button-primary" id="wtcc-save-shipments">
				<?php esc_html_e( 'Save Shipments', 'wtc-shipping' ); ?>
			</button>
			<span class="spinner"></span>
		</div>
	<?php endif; ?>
	<?php
	return ob_get_clean();
}

/**
 * AJAX handler - Save shipments
 */
add_action( 'wp_ajax_wtcc_save_shipments', 'wtcc_ajax_save_shipments' );
function wtcc_ajax_save_shipments() {
	check_ajax_referer( 'wtcc_split_shipments', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wtc-shipping' ) ) );
	}
	
	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$shipments_data = isset( $_POST['shipments'] ) ? (array) $_POST['shipments'] : array();
	
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => __( 'Order not found.', 'wtc-shipping' ) ) );
	}
	
	if ( empty( $shipments_data ) ) {
		wp_send_json_error( array( 'message' => __( 'No shipment data provided.', 'wtc-shipping' ) ) );
	}
	
	// Validate that all items are assigned
	$all_order_items = array_keys( $order->get_items() );
	$assigned_items = array();
	
	foreach ( $shipments_data as $shipment ) {
		if ( ! empty( $shipment['items'] ) ) {
			$assigned_items = array_merge( $assigned_items, $shipment['items'] );
		}
	}
	$assigned_items = array_map( 'absint', $assigned_items );
	
	$unassigned_items = array_diff( $all_order_items, $assigned_items );
	
	if ( ! empty( $unassigned_items ) ) {
		wp_send_json_error( array( 
			'message' => sprintf(
				/* translators: %d: number of unassigned items */
				_n(
					'All items must be assigned to a shipment. %d item is not assigned.',
					'All items must be assigned to a shipment. %d items are not assigned.',
					count( $unassigned_items ),
					'wtc-shipping'
				),
				count( $unassigned_items )
			)
		) );
	}
	
	// Format shipments
	$shipments = array();
	foreach ( $shipments_data as $shipment ) {
		if ( ! empty( $shipment['items'] ) ) {
			$shipments[] = array(
				'items'      => array_map( 'absint', $shipment['items'] ),
				'status'     => 'pending',
				'tracking'   => '',
				'created_at' => current_time( 'mysql' ),
			);
		}
	}
	
	if ( empty( $shipments ) ) {
		wp_send_json_error( array( 'message' => __( 'Cannot save empty shipments.', 'wtc-shipping' ) ) );
	}
	
	// Save to order meta
	$order->update_meta_data( '_wtcc_shipments', $shipments );
	$order->save();
	
	$order->add_order_note( sprintf(
		/* translators: %d: number of shipments */
		_n(
			'Order split into %d shipment.',
			'Order split into %d shipments.',
			count( $shipments ),
			'wtc-shipping'
		),
		count( $shipments )
	) );
	
	wp_send_json_success( array(
		'html' => wtcc_render_split_shipments_inner_content( $order ),
	) );
}

/**
 * AJAX handler - Reset shipments
 */
add_action( 'wp_ajax_wtcc_reset_shipments', 'wtcc_ajax_reset_shipments' );
function wtcc_ajax_reset_shipments() {
	check_ajax_referer( 'wtcc_split_shipments', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wtc-shipping' ) ) );
	}
	
	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => __( 'Order not found.', 'wtc-shipping' ) ) );
	}
	
	$order->delete_meta_data( '_wtcc_shipments' );
	$order->save();
	
	$order->add_order_note( __( 'Shipments reset. Order restored to a single shipment.', 'wtc-shipping' ) );
	
	wp_send_json_success( array(
		'html' => wtcc_render_split_shipments_inner_content( $order ),
	) );
}

/**
 * AJAX handler - Delete single shipment
 */
add_action( 'wp_ajax_wtcc_delete_shipment', 'wtcc_ajax_delete_shipment' );
function wtcc_ajax_delete_shipment() {
	check_ajax_referer( 'wtcc_split_shipments', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wtc-shipping' ) ) );
	}
	
	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$shipment_index = isset( $_POST['shipment_index'] ) ? absint( $_POST['shipment_index'] ) : -1;
	
	$order = wc_get_order( $order_id );
	
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => __( 'Order not found.', 'wtc-shipping' ) ) );
	}
	
	$shipments = $order->get_meta( '_wtcc_shipments', true ) ?: array();
	
	if ( $shipment_index < 0 || ! isset( $shipments[ $shipment_index ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Shipment not found.', 'wtc-shipping' ) ) );
	}
	
	// Remove shipment
	unset( $shipments[ $shipment_index ] );
	$shipments = array_values( $shipments ); // Re-index array
	
	if ( empty( $shipments ) ) {
		$order->delete_meta_data( '_wtcc_shipments' );
		$order->add_order_note( __( 'All shipments deleted. Order restored to a single shipment.', 'wtc-shipping' ) );
	} else {
		$order->update_meta_data( '_wtcc_shipments', $shipments );
		$order->add_order_note( sprintf( __( 'Shipment #%d deleted.', 'wtc-shipping' ), $shipment_index + 1 ) );
	}
	
	$order->save();
	
	wp_send_json_success( array(
		'html' => wtcc_render_split_shipments_inner_content( $order ),
	) );
}

/**
 * Enqueue split shipments scripts
 */
add_action( 'admin_enqueue_scripts', 'wtcc_split_shipments_admin_scripts' );
function wtcc_split_shipments_admin_scripts( $hook ) {
	global $post;
	
	$screen = get_current_screen();
	$is_order_screen = ( $screen && ( $screen->id === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders' ) );

	if ( ! $is_order_screen ) {
		return;
	}

    wp_enqueue_style(
        'wtc-admin-split-shipments-style',
        plugin_dir_url( __FILE__ ) . '../assets/admin-split-shipments.css',
        array(),
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/admin-split-shipments.css' )
    );

	wp_enqueue_script(
        'wtc-admin-split-shipments-script',
        plugin_dir_url( __FILE__ ) . '../assets/admin-split-shipments.js',
        array( 'jquery', 'wp-util' ),
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/admin-split-shipments.js' ),
        true
    );

	wp_localize_script( 'wtc-admin-split-shipments-script', 'wtcc_split_shipments_params', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'wtcc_split_shipments' ),
		'i18n'     => array(
			'error'                 => __( 'An unexpected error occurred. Please try again.', 'wtc-shipping' ),
			'saving'                => __( 'Saving...', 'wtc-shipping' ),
			'save_shipments'        => __( 'Save Shipments', 'wtc-shipping' ),
			'confirm_reset'         => __( 'Are you sure you want to reset all shipments? This cannot be undone.', 'wtc-shipping' ),
			'confirm_delete'        => __( 'Are you sure you want to delete this shipment?', 'wtc-shipping' ),
			'at_least_one_shipment' => __( 'You must have at least one shipment.', 'wtc-shipping' ),
			'no_items_in_shipment'  => __( 'Please add at least one item to a shipment before saving.', 'wtc-shipping' ),
			'shipment_title'        => __( 'Shipment #', 'wtc-shipping' ),
		),
	) );
}
