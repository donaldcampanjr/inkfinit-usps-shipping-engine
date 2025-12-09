<?php
/**
 * Product Scanning & Metadata Reader
 * Handles preset assignments and product attribute reading
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get product preset assignment
 *
 * @param int $product_id WooCommerce product ID.
 * @return string|null Preset key (tee, hoodie, etc.) or null
 */
function wtcc_shipping_get_product_preset( $product_id ) {
	return get_post_meta( $product_id, '_wtc_preset', true );
}

/**
 * Set product preset assignment
 *
 * @param int    $product_id WooCommerce product ID.
 * @param string $preset Preset key.
 * @return bool Success
 */
function wtcc_shipping_set_product_preset( $product_id, $preset ) {
	return update_post_meta( $product_id, '_wtc_preset', sanitize_text_field( $preset ) );
}

/**
 * Get product weight in ounces (normalized)
 *
 * @param int|WC_Product $product Product ID or WC_Product object.
 * @return float Weight in ounces
 */
function wtcc_shipping_get_product_weight_oz( $product ) {
	if ( is_int( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product ) {
		return 0;
	}

	$weight = wtcc_shipping_validate_numeric( $product->get_weight(), 'product_weight' );
	if ( null === $weight ) {
		$weight = 0;
	}

	$unit = sanitize_text_field( get_option( 'woocommerce_weight_unit', 'kg' ) );

	// Convert to ounces with sanitized values
	switch ( $unit ) {
		case 'oz':
			return max( 0, (float) $weight );
		case 'lb':
			return max( 0, (float) $weight * 16 );
		case 'g':
			return max( 0, (float) $weight / 28.3495 );
		case 'kg':
			return max( 0, (float) $weight * 35.274 );
		default:
			return max( 0, (float) $weight );
	}
}

/**
 * Get product dimensions in inches
 * Converts from WooCommerce dimension unit to inches for USPS API
 *
 * @param int|WC_Product $product Product ID or WC_Product object.
 * @return array Array with 'length', 'width', 'height' in inches
 */
function wtcc_shipping_get_product_dimensions_in( $product ) {
	if ( is_int( $product ) ) {
		$product = wc_get_product( $product );
	}

	$defaults = array( 'length' => 6, 'width' => 4, 'height' => 2 );

	if ( ! $product ) {
		return $defaults;
	}

	$length = (float) $product->get_length();
	$width  = (float) $product->get_width();
	$height = (float) $product->get_height();

	// If no dimensions set, return defaults
	if ( $length <= 0 && $width <= 0 && $height <= 0 ) {
		return $defaults;
	}

	$unit = get_option( 'woocommerce_dimension_unit', 'in' );

	// Convert to inches based on WooCommerce setting
	$conversion = 1;
	switch ( $unit ) {
		case 'mm':
			$conversion = 0.0393701;
			break;
		case 'cm':
			$conversion = 0.393701;
			break;
		case 'm':
			$conversion = 39.3701;
			break;
		case 'in':
			$conversion = 1;
			break;
		case 'yd':
			$conversion = 36;
			break;
	}

	return array(
		'length' => max( 1, round( $length * $conversion, 1 ) ),
		'width'  => max( 1, round( $width * $conversion, 1 ) ),
		'height' => max( 1, round( $height * $conversion, 1 ) ),
	);
}

/**
 * Calculate package dimensions from cart items
 * Uses smart box-packing algorithm to optimize package sizes
 *
 * @param array $package WooCommerce package with contents.
 * @return array Package dimensions in inches (for primary package)
 */
function wtcc_shipping_calculate_package_dimensions( $package ) {
	// Default dimensions if no contents
	$defaults = array( 'length' => 6, 'width' => 4, 'height' => 2 );

	if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
		return $defaults;
	}

	// Use smart box packing if available
	if ( function_exists( 'wtcc_shipping_pack_items' ) ) {
		$packed = wtcc_shipping_smart_pack_package( $package );
		if ( ! empty( $packed ) ) {
			// Return dimensions of first/primary package
			return $packed[0]['box_dimensions'];
		}
	}

	// Fallback to legacy stacking method
	return wtcc_shipping_calculate_package_dimensions_legacy( $package );
}

/**
 * Smart pack a WooCommerce package using box-packing algorithm
 *
 * @param array $package WooCommerce package with contents.
 * @return array Packed boxes result
 */
function wtcc_shipping_smart_pack_package( $package ) {
	if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
		return array();
	}

	// Convert WooCommerce cart items to packing format
	$items = array();
	
	foreach ( $package['contents'] as $cart_item_key => $item ) {
		if ( ! isset( $item['data'] ) ) {
			continue;
		}

		$product  = $item['data'];
		$quantity = isset( $item['quantity'] ) ? intval( $item['quantity'] ) : 1;
		$dims     = wtcc_shipping_get_product_dimensions_in( $product );
		$weight   = wtcc_shipping_get_product_weight_oz( $product );

		// Get product name for debugging/display
		$name = $product->get_name();

		$items[] = array(
			'name'         => $name,
			'product_id'   => $product->get_id(),
			'length'       => $dims['length'],
			'width'        => $dims['width'],
			'height'       => $dims['height'],
			'weight'       => $weight > 0 ? $weight : 4, // Default 4oz if no weight
			'quantity'     => $quantity,
			'cart_item_key'=> $cart_item_key,
		);
	}

	if ( empty( $items ) ) {
		return array();
	}

	// Run the packing algorithm
	return wtcc_shipping_pack_items( $items );
}

/**
 * Get all packages for shipping calculation (supports multi-package orders)
 *
 * @param array $package WooCommerce package with contents.
 * @return array Array of packages with dimensions and weights
 */
function wtcc_shipping_get_shipping_packages( $package ) {
	// Use smart packing
	$packed_boxes = wtcc_shipping_smart_pack_package( $package );
	
	if ( empty( $packed_boxes ) ) {
		// Fallback to single package with legacy dimensions
		$dims = wtcc_shipping_calculate_package_dimensions_legacy( $package );
		$weight = wtcc_shipping_calculate_package_weight( $package );
		
		return array(
			array(
				'length'    => $dims['length'],
				'width'     => $dims['width'],
				'height'    => $dims['height'],
				'weight_oz' => $weight,
				'weight_lbs'=> round( $weight / 16, 2 ),
				'box_name'  => 'Standard Package',
			),
		);
	}

	// Convert packed boxes to shipping packages format
	return wtcc_shipping_get_packages_for_usps( $packed_boxes );
}

/**
 * Legacy package dimension calculation (simple stacking)
 *
 * @param array $package WooCommerce package with contents.
 * @return array Package dimensions in inches
 */
function wtcc_shipping_calculate_package_dimensions_legacy( $package ) {
	$max_length = 6;
	$max_width  = 4;
	$total_height = 0;

	if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
		return array( 'length' => $max_length, 'width' => $max_width, 'height' => 2 );
	}

	foreach ( $package['contents'] as $item ) {
		if ( ! isset( $item['data'] ) ) {
			continue;
		}

		$product = $item['data'];
		$quantity = $item['quantity'] ?? 1;
		$dims = wtcc_shipping_get_product_dimensions_in( $product );

		// Track largest footprint (length x width)
		$max_length = max( $max_length, $dims['length'] );
		$max_width  = max( $max_width, $dims['width'] );

		// Stack items (add heights)
		$total_height += ( $dims['height'] * $quantity );
	}

	// Cap dimensions at reasonable maximums (USPS limits)
	return array(
		'length' => min( 108, $max_length ),
		'width'  => min( 108, $max_width ),
		'height' => min( 108, max( 1, $total_height ) ),
	);
}

/**
 * Calculate total package weight from cart items
 *
 * @param array $package WooCommerce package with contents.
 * @return float Weight in ounces
 */
function wtcc_shipping_calculate_package_weight( $package ) {
	$total_weight = 0;

	if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
		return 4; // Default 4oz
	}

	foreach ( $package['contents'] as $item ) {
		if ( ! isset( $item['data'] ) ) {
			continue;
		}

		$product  = $item['data'];
		$quantity = isset( $item['quantity'] ) ? intval( $item['quantity'] ) : 1;
		$weight   = wtcc_shipping_get_product_weight_oz( $product );

		$total_weight += ( $weight * $quantity );
	}

	// Return at least 4oz (reasonable minimum)
	return max( 4, $total_weight );
}

/**
 * Get product shipping class slug
 *
 * @param int|WC_Product $product Product ID or WC_Product object.
 * @return string Shipping class slug or empty
 */
function wtcc_shipping_get_product_shipping_class( $product ) {
	if ( is_int( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product ) {
		return '';
	}

	return $product->get_shipping_class();
}

/**
 * Get product categories (slugs)
 *
 * @param int|WC_Product $product Product ID or WC_Product object.
 * @return array Category slugs
 */
function wtcc_shipping_get_product_categories( $product ) {
	if ( is_int( $product ) ) {
		$product_id = $product;
	} else {
		$product_id = $product->get_id();
	}

	$terms = get_the_terms( $product_id, 'product_cat' );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}

	return wp_list_pluck( $terms, 'slug' );
}

/**
 * Get product tags (slugs)
 *
 * @param int|WC_Product $product Product ID or WC_Product object.
 * @return array Tag slugs
 */
function wtcc_shipping_get_product_tags( $product ) {
	if ( is_int( $product ) ) {
		$product_id = $product;
	} else {
		$product_id = $product->get_id();
	}

	$terms = get_the_terms( $product_id, 'product_tag' );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}

	return wp_list_pluck( $terms, 'slug' );
}

/**
 * Scan cart packages and build product metadata array
 *
 * @param array $packages WooCommerce packages.
 * @return array Array of product metadata
 */
function wtcc_shipping_scan_packages( $packages ) {
	$products_data = array();

	foreach ( $packages as $package ) {
		if ( empty( $package['contents'] ) ) {
			continue;
		}

		foreach ( $package['contents'] as $item_key => $item ) {
			if ( empty( $item['data'] ) ) {
				continue;
			}

			$product = $item['data'];
			$qty      = $item['quantity'] ?? 1;

			$products_data[] = array(
				'product_id'      => $product->get_id(),
				'qty'             => $qty,
				'weight_oz'       => wtcc_shipping_get_product_weight_oz( $product ),
				'shipping_class'  => wtcc_shipping_get_product_shipping_class( $product ),
				'categories'      => wtcc_shipping_get_product_categories( $product ),
				'tags'            => wtcc_shipping_get_product_tags( $product ),
				'preset'          => wtcc_shipping_get_product_preset( $product->get_id() ),
			);
		}
	}

	return $products_data;
}

/**
 * Calculate total cart weight in ounces
 *
 * @param array $packages WooCommerce packages.
 * @return float Total weight in ounces
 */
if ( ! function_exists( 'wtcc_shipping_calculate_total_weight_oz' ) ) {
function wtcc_shipping_calculate_total_weight_oz( $packages ) {
	$total_weight = 0;

	foreach ( $packages as $package ) {
		if ( empty( $package['contents'] ) ) {
			continue;
		}

		foreach ( $package['contents'] as $item ) {
			if ( empty( $item['data'] ) ) {
				continue;
			}

			$product = $item['data'];
			$qty      = $item['quantity'] ?? 1;
			$weight   = wtcc_shipping_get_product_weight_oz( $product );

			$total_weight += ( $weight * $qty );
		}
	}

	return $total_weight;
}
} // end function_exists
