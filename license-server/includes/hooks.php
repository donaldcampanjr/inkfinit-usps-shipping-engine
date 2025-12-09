<?php
/**
 * WooCommerce Integration Hooks
 *
 * Automatically generates license keys when customers purchase license products.
 *
 * PRODUCT SKU FORMAT:
 *   - INK-PRO-1Y      = Pro license, 1 year
 *   - INK-PRO-LT      = Pro license, lifetime
 *   - INK-ENT-1Y      = Enterprise license, 1 year
 *   - INK-ENT-LT      = Enterprise license, lifetime
 *
 * @package InkfinitLicenseServer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Process order and generate license key on completion.
 *
 * @param int $order_id WooCommerce order ID.
 */
function bils_process_order_for_license( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Check if we already generated a key for this order.
	global $wpdb;
	$table    = $wpdb->prefix . 'bils_license_keys';
	$existing = $wpdb->get_var( $wpdb->prepare(
		"SELECT license_key FROM $table WHERE order_id = %d LIMIT 1",
		$order_id
	) );

	if ( $existing ) {
		return; // Already processed.
	}

	// Find license product in order and determine tier.
	$tier = bils_get_license_tier_from_order( $order );
	if ( ! $tier ) {
		return; // No license product in this order.
	}

	// Create the license.
	$license_data = BILS_License_Manager::create_for_order( $order_id, $tier );
	if ( ! $license_data ) {
		$order->add_order_note( 'ERROR: Failed to generate license key.' );
		return;
	}

	// Send email to customer.
	bils_send_license_email( $order, $license_data );

	// Add order note.
	$tier_label = ucfirst( $tier );
	$order->add_order_note( sprintf(
		'✅ License key generated (%s): %s',
		$tier_label,
		$license_data['license_key']
	) );
}

// Hook into both completed and processing statuses.
add_action( 'woocommerce_order_status_completed', 'bils_process_order_for_license', 10, 1 );
add_action( 'woocommerce_order_status_processing', 'bils_process_order_for_license', 10, 1 );

/**
 * Determine license tier from order items by checking product SKUs.
 *
 * SKU Patterns:
 *   - INK-PRO-*  = Pro tier
 *   - INK-ENT-*  = Enterprise tier
 *
 * @param WC_Order $order WooCommerce order object.
 * @return string|null Tier ('pro', 'enterprise') or null if no license product.
 */
function bils_get_license_tier_from_order( $order ) {
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}

		$sku = strtoupper( $product->get_sku() );
		if ( empty( $sku ) ) {
			continue;
		}

		// Check for license product SKU patterns.
		if ( strpos( $sku, 'INK-ENT-' ) === 0 ) {
			return 'enterprise';
		}
		if ( strpos( $sku, 'INK-PRO-' ) === 0 ) {
			return 'pro';
		}
	}

	return null;
}

/**
 * Send license key email to customer.
 *
 * @param WC_Order $order        WooCommerce order object.
 * @param array    $license_data License data array.
 */
function bils_send_license_email( $order, $license_data ) {
	$to      = $license_data['customer_email'];
	$tier    = isset( $license_data['tier'] ) ? ucfirst( $license_data['tier'] ) : 'Pro';
	$subject = "Your Inkfinit USPS Shipping Engine {$tier} License Key";

	$message  = "Hi " . $license_data['customer_name'] . ",\n\n";
	$message .= "Thank you for purchasing Inkfinit USPS Shipping Engine {$tier}!\n\n";
	$message .= "Here is your license key:\n\n";
	$message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
	$message .= $license_data['license_key'] . "\n";
	$message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
	$message .= "HOW TO ACTIVATE:\n";
	$message .= "1. Install the Inkfinit USPS Shipping Engine plugin on your WordPress site\n";
	$message .= "2. Go to: Inkfinit Shipping → License\n";
	$message .= "3. Paste your license key and click Activate\n\n";
	$message .= "NEED HELP?\n";
	$message .= "• Documentation: https://inkfinit.pro/docs/usps-shipping/\n";
	$message .= "• Support: support@inkfinit.pro\n\n";
	$message .= "Thanks for choosing Inkfinit!\n";
	$message .= "— The Inkfinit Team";

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	wp_mail( $to, $subject, $message, $headers );
}
