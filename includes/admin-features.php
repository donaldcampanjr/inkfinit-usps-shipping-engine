<?php
/**
 * Advanced Admin Features with Enhanced Security
 * - Bulk preset assignment
 * - Custom shipping labels
 * - Shipping history logging
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: Core validation functions are loaded from core-functions.php
// This prevents redeclaration errors

// ===== BULK PRESET ASSIGNMENT =====

add_filter( 'bulk_actions-edit-product', 'wtc_add_bulk_preset_action' );
function wtc_add_bulk_preset_action( $bulk_actions ) {
	$bulk_actions['wtc_bulk_assign_preset'] = 'Assign WTC Preset';
	return $bulk_actions;
}

add_filter( 'handle_bulk_actions-edit-product', 'wtc_handle_bulk_preset', 10, 3 );
function wtc_handle_bulk_preset( $redirect, $doaction, $post_ids ) {
	if ( 'wtc_bulk_assign_preset' !== $doaction ) {
		return $redirect;
	}

	if ( ! isset( $_POST['wtc_bulk_preset'] ) || empty( $_POST['wtc_bulk_preset'] ) ) {
		return $redirect;
	}

	$preset = sanitize_text_field( $_POST['wtc_bulk_preset'] );
	$count = 0;

	foreach ( (array) $post_ids as $product_id ) {
		wtcc_shipping_set_product_preset( $product_id, $preset );
		$count++;
	}

	return add_query_arg( array( 'wtc_bulk_preset_assigned' => $count ), $redirect );
}

add_action( 'admin_notices', 'wtc_bulk_preset_notice' );
function wtc_bulk_preset_notice() {
	if ( ! isset( $_REQUEST['wtc_bulk_preset_assigned'] ) || ! is_admin() ) {
		return;
	}

	$count = intval( $_REQUEST['wtc_bulk_preset_assigned'] );
	?>
	<div class="notice notice-success is-dismissible">
		<p><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> WTC Preset assigned to <?php echo esc_html( $count ); ?> products.</p>
	</div>
	<?php
}

add_action( 'admin_footer', 'wtc_bulk_preset_form' );
function wtc_bulk_preset_form() {
	global $current_screen;
	
	if ( ! $current_screen || $current_screen->id !== 'edit-product' ) {
		return;
	}

	$presets = wtcc_shipping_get_presets();
	?>
	<script>
	(function() {
		const bulkSelect = document.querySelector('select[name="action"]');
		if (!bulkSelect) return;

		// Add preset select to bulk actions
		const presetDiv = document.createElement('div');
		presetDiv.id = 'wtc_bulk_preset_div';
		presetDiv.style.display = 'none';
		presetDiv.style.marginLeft = '10px';
		presetDiv.style.marginRight = '10px';
		presetDiv.innerHTML = `
			<select name="wtc_bulk_preset" id="wtc_bulk_preset">
				<option value="">-- Select Preset --</option>
				<?php foreach ( $presets as $key => $preset ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $preset['label'] ?? ucfirst( $key ) ); ?></option>
				<?php endforeach; ?>
			</select>
		`;
		bulkSelect.parentNode.insertBefore(presetDiv, bulkSelect.nextSibling);

		// Show/hide preset select when bulk action changes
		bulkSelect.addEventListener('change', function() {
			presetDiv.style.display = this.value === 'wtc_bulk_assign_preset' ? 'inline-block' : 'none';
		});
	})();
	</script>
	<?php
}

// ===== CUSTOM SHIPPING LABELS =====

function wtcc_shipping_get_custom_labels() {
	return get_option( 'wtc_label_overrides', array() );
}

function wtcc_shipping_get_label_for_group( $group_key ) {
	$overrides = wtcc_shipping_get_custom_labels();
	return $overrides[ $group_key ] ?? '';
}

// ===== SHIPPING HISTORY/LOGGING =====

function wtcc_shipping_log_calculation( $order_id, $group, $cost, $weight_oz, $zone ) {
	$history = get_option( 'wtcc_shipping_history', array() );

	$entry = array(
		'order_id'   => $order_id,
		'group'      => $group,
		'cost'       => $cost,
		'weight_oz'  => $weight_oz,
		'zone'       => $zone,
		'timestamp'  => current_time( 'timestamp' ),
		'user_id'    => get_current_user_id(),
	);

	$history[] = $entry;

	// Keep only last 1000 records
	if ( count( $history ) > 1000 ) {
		$history = array_slice( $history, -1000 );
	}

	update_option( 'wtcc_shipping_history', $history );
}

function wtcc_shipping_get_history( $limit = 100 ) {
	$history = get_option( 'wtcc_shipping_history', array() );
	return array_slice( array_reverse( $history ), 0, $limit );
}

// Log on order completion
add_action( 'woocommerce_order_status_completed', 'wtc_log_order_shipping' );
function wtc_log_order_shipping( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	foreach ( $order->get_shipping_methods() as $method ) {
		$method_title = $method->get_method_title() ? (string) $method->get_method_title() : '';
		$cost = $method->get_total();

		// Try to extract group from method name
		$group = 'unknown';
		if ( $method_title && strpos( $method_title, 'First Class' ) !== false ) {
			$group = 'first_class';
		} elseif ( strpos( $method_title, 'Ground' ) !== false ) {
			$group = 'ground';
		} elseif ( strpos( $method_title, 'Priority' ) !== false ) {
			$group = 'priority';
		} elseif ( strpos( $method_title, 'Express' ) !== false ) {
			$group = 'express';
		}

		$zone = wtcc_shipping_get_zone_for_country( $order->get_shipping_country() ?? 'US' );
		$total_weight = 0;

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$weight = wtcc_shipping_get_product_weight_oz( $product );
				$qty = $item->get_quantity();
				$total_weight += ( $weight * $qty );
			}
		}

		wtcc_shipping_log_calculation( $order_id, $group, $cost, $total_weight, $zone );
	}
}

// Display history in admin
function wtcc_shipping_admin_history_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized' );
	}

	add_submenu_page(
		'wtc-shipping',
		'Shipping History',
		'Shipping History',
		'manage_options',
		'wtc-shipping-history',
		function() {
			?>
                        <div class="wrap">
                                <?php wtcc_admin_header( "Shipping History" ); ?>
                                <p class="description">Recent shipping calculations from completed orders</p>

				<table class="widefat fixed">
					<thead>
						<tr>
							<th>Order ID</th>
							<th>Method</th>
							<th>Weight (oz)</th>
							<th>Zone</th>
							<th>Cost</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$history = wtcc_shipping_get_history( 200 );
						if ( empty( $history ) ) {
							echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">No shipping history yet. Complete an order to see data.</td></tr>';
						} else {
							foreach ( $history as $entry ) {
								echo '<tr>';
								echo '<td><a href="' . esc_url( admin_url( 'post.php?post=' . $entry['order_id'] . '&action=edit' ) ) . '">#' . esc_html( $entry['order_id'] ) . '</a></td>';
								echo '<td>' . esc_html( ucfirst( $entry['group'] ) ) . '</td>';
								echo '<td>' . esc_html( number_format( $entry['weight_oz'], 1 ) ) . '</td>';
								echo '<td>' . esc_html( strtoupper( $entry['zone'] ) ) . '</td>';
								echo '<td>$' . esc_html( number_format( $entry['cost'], 2 ) ) . '</td>';
								echo '<td>' . esc_html( wp_date( 'M d, Y H:i', $entry['timestamp'] ) ) . '</td>';
								echo '</tr>';
							}
						}
						?>
					</tbody>
				</table>
			</div>
			<?php
		}
	);
}
// Menu registration moved to plugin.php for consistency
