<?php
/**
 * Auto-Calculated Shipping Display & Debug Tools
 * 
 * Customer-facing:
 * - Simple labels (Standard, Economy, Express)
 * - Just price and speed
 * 
 * Admin-facing:
 * - Full calculation breakdown
 * - Weight detection
 * - Zone identification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display shipping methods with simple human-friendly labels
 * Called during checkout shipping method selection
 */
add_filter( 'woocommerce_shipping_package_rates', 'wtcc_shipping_simplify_checkout_labels', 10, 2 );
function wtcc_shipping_simplify_checkout_labels( $rates, $package ) {
	$new_rates = array();
	$costs     = array();

	// Collect all available methods and costs
	foreach ( $rates as $rate_key => $rate ) {
		if ( $rate_key && strpos( (string) $rate_key, 'wtc_' ) === 0 ) {
			$costs[] = (float) $rate->cost;
			$new_rates[ $rate_key ] = $rate;
		}
	}

	if ( empty( $costs ) ) {
		return $rates;
	}

	// Determine cheapest, fastest
	$cheapest_cost = min( $costs );
	$most_expensive = max( $costs );

	// Update labels with simple, friendly UX
	foreach ( $new_rates as $rate_key => $rate ) {
		$base_label = $rate->label;
		$cost       = (float) $rate->cost;

		// Add simple label based on cost
		if ( $cost === $cheapest_cost ) {
			$rate->label = __( 'Standard (Cheapest)', 'wtc-shipping' ) . ' – $' . number_format( $cost, 2 );
		} elseif ( $cost === $most_expensive ) {
			$rate->label = __( 'Express (Fastest)', 'wtc-shipping' ) . ' – $' . number_format( $cost, 2 );
		} else {
			$rate->label = __( 'Standard', 'wtc-shipping' ) . ' – $' . number_format( $cost, 2 );
		}

		$new_rates[ $rate_key ] = $rate;
	}

	return $new_rates;
}

/**
 * Add admin-only debug info on cart/checkout pages
 */
add_action( 'woocommerce_after_cart_totals', 'wtcc_shipping_admin_debug_display' );
add_action( 'woocommerce_after_checkout_form', 'wtcc_shipping_admin_debug_display' );
function wtcc_shipping_admin_debug_display() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$cart = WC()->cart;

	if ( ! $cart ) {
		return;
	}

	// Check for zero-weight products and show warnings
	$warnings = array();
	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['data'] ) ) {
			continue;
		}

		$product = $item['data'];
		$weight = wtcc_shipping_get_product_weight_oz( $product );
		$preset = wtcc_shipping_get_product_preset( $product->get_id() );

		if ( $weight <= 0 && empty( $preset ) ) {
			$warnings[] = $product->get_name() . ' has NO weight or preset assigned!';
		} elseif ( $weight <= 0 ) {
			$warnings[] = $product->get_name() . ' has weight 0 oz (preset set but weight not auto-filled?)';
		} elseif ( empty( $preset ) ) {
			$warnings[] = $product->get_name() . ' has no preset assigned (but has manual weight)';
		}
	}

	if ( ! empty( $warnings ) ) {
		echo '<div class="wtcc-debug-warning">';
		echo '<strong>Inkfinit Shipping Warning:</strong> Some products have missing data that may cause inaccurate rates.';
		echo '<ul class="wtcc-debug-warning-list">';
		foreach ( $warnings as $warning ) {
			echo '<li>' . esc_html( $warning ) . '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}

	// Get total weight
	$total_weight_oz = wtcc_shipping_get_cart_weight_oz( $cart );
	$total_weight_lb = $total_weight_oz / 16;

	// Get destination info
	$destination = WC()->customer->get_shipping_country();
	if ( WC()->customer->has_shipping_address() ) {
		$destination .= ', ' . WC()->customer->get_shipping_state();
		$destination .= ', ' . WC()->customer->get_shipping_postcode();
	}

	// Get shipping zone
	$shipping_package = WC()->shipping->get_packages()[0] ?? null;
	$zone = $shipping_package ? WC_Shipping_Zones::get_zone_matching_package( $shipping_package ) : null;
	$zone_name = $zone ? $zone->get_zone_name() : 'N/A';

	// Display debug info in a fixed overlay
	?>
	<div class="wtcc-debug-overlay">
		<div class="wtcc-debug-overlay-header">Inkfinit Shipping Debug</div>
		<strong>Total Weight:</strong> <?php echo esc_html( round( $total_weight_oz, 2 ) ); ?> oz / <?php echo esc_html( round( $total_weight_lb, 2 ) ); ?> lbs<br>
		<strong>Destination:</strong> <?php echo esc_html( $destination ); ?><br>
		<strong>Shipping Zone:</strong> <?php echo esc_html( $zone_name ); ?>
		<hr>
		<div class="wtcc-debug-overlay-footer">This panel is visible to admins only.</div>
	</div>
	<?php
}

/**
 * Debug overlay removed - configuration visible in Dashboard and Diagnostics pages
 */
