<?php
/**
 * USPS Priority Mail Flat Rate Box System
 * 
 * Complete integration for USPS Flat Rate shipping:
 * - Box definitions with exact USPS dimensions
 * - Flat rate pricing (retail and commercial)
 * - Admin override capabilities
 * - Checkout integration
 * - Label system integration
 * 
 * @package WTC_Shipping_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all USPS Priority Mail Flat Rate box definitions
 * Dimensions are exact USPS specifications in inches
 * Prices as of 2024/2025 (update periodically)
 * 
 * @return array Flat rate box definitions
 */
function wtcc_get_flat_rate_boxes() {
	$boxes = array(
		// ============================================
		// ENVELOPES
		// ============================================
		'flat_rate_envelope' => array(
			'name'              => 'Priority Mail Flat Rate Envelope',
			'usps_code'         => 'FLAT_RATE_ENVELOPE',
			'type'              => 'envelope',
			'length'            => 12.5,
			'width'             => 9.5,
			'height'            => 0.75, // Max thickness
			'max_weight_oz'     => 1120, // 70 lbs
			'price_retail'      => 10.45,
			'price_commercial'  => 8.45,
			'price_business'    => 7.89,
			'free_supplies'     => true,
			'usps_product_id'   => 'EP14F',
			'description'       => 'Standard flat rate envelope for documents and thin items',
		),
		'flat_rate_envelope_padded' => array(
			'name'              => 'Priority Mail Padded Flat Rate Envelope',
			'usps_code'         => 'PADDED_FLAT_RATE_ENVELOPE',
			'type'              => 'envelope',
			'length'            => 12.5,
			'width'             => 9.5,
			'height'            => 1.0,
			'max_weight_oz'     => 1120,
			'price_retail'      => 10.90,
			'price_commercial'  => 8.85,
			'price_business'    => 8.25,
			'free_supplies'     => true,
			'usps_product_id'   => 'EP14PF',
			'description'       => 'Padded envelope for fragile flat items',
		),
		'flat_rate_envelope_legal' => array(
			'name'              => 'Priority Mail Legal Flat Rate Envelope',
			'usps_code'         => 'LEGAL_FLAT_RATE_ENVELOPE',
			'type'              => 'envelope',
			'length'            => 15,
			'width'             => 9.5,
			'height'            => 0.75,
			'max_weight_oz'     => 1120,
			'price_retail'      => 10.75,
			'price_commercial'  => 8.70,
			'price_business'    => 8.10,
			'free_supplies'     => true,
			'usps_product_id'   => 'EP15F',
			'description'       => 'Legal-size envelope for larger documents',
		),
		'flat_rate_envelope_small' => array(
			'name'              => 'Priority Mail Small Flat Rate Envelope',
			'usps_code'         => 'SMALL_FLAT_RATE_ENVELOPE',
			'type'              => 'envelope',
			'length'            => 10,
			'width'             => 6,
			'height'            => 0.5,
			'max_weight_oz'     => 64, // 4 lbs for small envelope
			'price_retail'      => 9.35,
			'price_commercial'  => 7.55,
			'price_business'    => 7.05,
			'free_supplies'     => true,
			'usps_product_id'   => 'EP10F',
			'description'       => 'Smaller envelope for compact items',
		),
		'flat_rate_envelope_window' => array(
			'name'              => 'Priority Mail Window Flat Rate Envelope',
			'usps_code'         => 'WINDOW_FLAT_RATE_ENVELOPE',
			'type'              => 'envelope',
			'length'            => 10,
			'width'             => 5,
			'height'            => 0.5,
			'max_weight_oz'     => 64,
			'price_retail'      => 9.35,
			'price_commercial'  => 7.55,
			'price_business'    => 7.05,
			'free_supplies'     => true,
			'usps_product_id'   => 'EP10WF',
			'description'       => 'Window envelope with address visibility',
		),
		'flat_rate_envelope_gift_card' => array(
			'name'              => 'Priority Mail Gift Card Flat Rate Envelope',
			'usps_code'         => 'GIFT_CARD_FLAT_RATE_ENVELOPE',
			'type'              => 'envelope',
			'length'            => 10,
			'width'             => 7,
			'height'            => 0.5,
			'max_weight_oz'     => 64,
			'price_retail'      => 9.35,
			'price_commercial'  => 7.55,
			'price_business'    => 7.05,
			'free_supplies'     => true,
			'usps_product_id'   => 'EP10GF',
			'description'       => 'Decorative envelope for gift cards',
		),

		// ============================================
		// BOXES
		// ============================================
		'flat_rate_box_small' => array(
			'name'              => 'Priority Mail Small Flat Rate Box',
			'usps_code'         => 'SMALL_FLAT_RATE_BOX',
			'type'              => 'box',
			'length'            => 8.6875, // 8-11/16"
			'width'             => 5.4375, // 5-7/16"
			'height'            => 1.75,
			'max_weight_oz'     => 1120, // 70 lbs
			'price_retail'      => 10.85,
			'price_commercial'  => 8.70,
			'price_business'    => 8.10,
			'free_supplies'     => true,
			'usps_product_id'   => 'O-FRB1',
			'description'       => 'Small box ideal for jewelry, small electronics, CDs',
		),
		'flat_rate_box_medium_1' => array(
			'name'              => 'Priority Mail Medium Flat Rate Box (Top Load)',
			'usps_code'         => 'MEDIUM_FLAT_RATE_BOX',
			'type'              => 'box',
			'length'            => 11.25,
			'width'             => 8.75,
			'height'            => 6,
			'max_weight_oz'     => 1120,
			'price_retail'      => 17.10,
			'price_commercial'  => 14.35,
			'price_business'    => 13.40,
			'free_supplies'     => true,
			'usps_product_id'   => 'O-FRB2',
			'description'       => 'Medium box (top load) - most popular for merchandise',
			'alternative_name'  => 'Medium Box 1',
		),
		'flat_rate_box_medium_2' => array(
			'name'              => 'Priority Mail Medium Flat Rate Box (Side Load)',
			'usps_code'         => 'MEDIUM_FLAT_RATE_BOX',
			'type'              => 'box',
			'length'            => 14,
			'width'             => 12,
			'height'            => 3.5,
			'max_weight_oz'     => 1120,
			'price_retail'      => 17.10,
			'price_commercial'  => 14.35,
			'price_business'    => 13.40,
			'free_supplies'     => true,
			'usps_product_id'   => 'O-FRB2',
			'description'       => 'Medium box (side load) - better for flat items like shirts',
			'alternative_name'  => 'Medium Box 2',
		),
		'flat_rate_box_large' => array(
			'name'              => 'Priority Mail Large Flat Rate Box',
			'usps_code'         => 'LARGE_FLAT_RATE_BOX',
			'type'              => 'box',
			'length'            => 12.25,
			'width'             => 12.25,
			'height'            => 6,
			'max_weight_oz'     => 1120,
			'price_retail'      => 23.00,
			'price_commercial'  => 19.60,
			'price_business'    => 18.30,
			'free_supplies'     => true,
			'usps_product_id'   => 'O-FRB3',
			'description'       => 'Large box - ideal for heavy items, vinyl records, shoes',
		),
		'flat_rate_box_large_apofpo' => array(
			'name'              => 'Priority Mail Large Flat Rate Box (APO/FPO/DPO)',
			'usps_code'         => 'LARGE_FLAT_RATE_BOX_APO',
			'type'              => 'box',
			'length'            => 12.25,
			'width'             => 12.25,
			'height'            => 6,
			'max_weight_oz'     => 1120,
			'price_retail'      => 22.25,
			'price_commercial'  => 18.95,
			'price_business'    => 17.65,
			'free_supplies'     => true,
			'usps_product_id'   => 'O-FRB3APO',
			'description'       => 'Large box for military addresses only',
			'military_only'     => true,
		),
		'flat_rate_box_board_game' => array(
			'name'              => 'Priority Mail Board Game Flat Rate Box',
			'usps_code'         => 'BOARD_GAME_FLAT_RATE_BOX',
			'type'              => 'box',
			'length'            => 24.0625, // 24-1/16"
			'width'             => 11.875,  // 11-7/8"
			'height'            => 3.125,   // 3-1/8"
			'max_weight_oz'     => 1120,
			'price_retail'      => 23.00,
			'price_commercial'  => 19.60,
			'price_business'    => 18.30,
			'free_supplies'     => true,
			'usps_product_id'   => 'O-FRB4',
			'description'       => 'Specialty box for board games and flat large items',
		),

		// ============================================
		// REGIONAL RATE BOXES (Zone-based but fixed per zone)
		// ============================================
		'regional_rate_box_a1' => array(
			'name'              => 'Priority Mail Regional Rate Box A (Top Load)',
			'usps_code'         => 'REGIONAL_RATE_BOX_A',
			'type'              => 'regional',
			'length'            => 10.125,
			'width'             => 7.125,
			'height'            => 5,
			'max_weight_oz'     => 240, // 15 lbs
			'price_retail'      => null, // Zone-based pricing
			'price_commercial'  => null,
			'free_supplies'     => true,
			'usps_product_id'   => 'RRB-A1',
			'description'       => 'Regional A box (top load) - zone pricing, up to 15 lbs',
			'zone_based'        => true,
		),
		'regional_rate_box_a2' => array(
			'name'              => 'Priority Mail Regional Rate Box A (Side Load)',
			'usps_code'         => 'REGIONAL_RATE_BOX_A',
			'type'              => 'regional',
			'length'            => 13.0625, // 13-1/16"
			'width'             => 11.0625, // 11-1/16"
			'height'            => 2.5,
			'max_weight_oz'     => 240,
			'price_retail'      => null,
			'price_commercial'  => null,
			'free_supplies'     => true,
			'usps_product_id'   => 'RRB-A2',
			'description'       => 'Regional A box (side load) - zone pricing, up to 15 lbs',
			'zone_based'        => true,
		),
		'regional_rate_box_b1' => array(
			'name'              => 'Priority Mail Regional Rate Box B (Top Load)',
			'usps_code'         => 'REGIONAL_RATE_BOX_B',
			'type'              => 'regional',
			'length'            => 12.25,
			'width'             => 10.5,
			'height'            => 5.5,
			'max_weight_oz'     => 320, // 20 lbs
			'price_retail'      => null,
			'price_commercial'  => null,
			'free_supplies'     => true,
			'usps_product_id'   => 'RRB-B1',
			'description'       => 'Regional B box (top load) - zone pricing, up to 20 lbs',
			'zone_based'        => true,
		),
		'regional_rate_box_b2' => array(
			'name'              => 'Priority Mail Regional Rate Box B (Side Load)',
			'usps_code'         => 'REGIONAL_RATE_BOX_B',
			'type'              => 'regional',
			'length'            => 16.25,
			'width'             => 14.5,
			'height'            => 3,
			'max_weight_oz'     => 320,
			'price_retail'      => null,
			'price_commercial'  => null,
			'free_supplies'     => true,
			'usps_product_id'   => 'RRB-B2',
			'description'       => 'Regional B box (side load) - zone pricing, up to 20 lbs',
			'zone_based'        => true,
		),
	);

	// Apply any saved price overrides from admin
	$price_overrides = get_option( 'wtcc_flat_rate_price_overrides', array() );
	foreach ( $price_overrides as $box_key => $prices ) {
		if ( isset( $boxes[ $box_key ] ) ) {
			if ( ! empty( $prices['retail'] ) ) {
				$boxes[ $box_key ]['price_retail'] = floatval( $prices['retail'] );
			}
			if ( ! empty( $prices['commercial'] ) ) {
				$boxes[ $box_key ]['price_commercial'] = floatval( $prices['commercial'] );
			}
		}
	}

	return apply_filters( 'wtcc_flat_rate_boxes', $boxes );
}

/**
 * Get only flat rate boxes (not envelopes or regional)
 * 
 * @return array Flat rate boxes only
 */
function wtcc_get_flat_rate_boxes_only() {
	$all = wtcc_get_flat_rate_boxes();
	return array_filter( $all, function( $box ) {
		return $box['type'] === 'box' && empty( $box['zone_based'] );
	} );
}

/**
 * Get only flat rate envelopes
 * 
 * @return array Flat rate envelopes only
 */
function wtcc_get_flat_rate_envelopes() {
	$all = wtcc_get_flat_rate_boxes();
	return array_filter( $all, function( $box ) {
		return $box['type'] === 'envelope';
	} );
}

/**
 * Get enabled flat rate boxes for shipping calculations
 * 
 * @return array Enabled flat rate boxes
 */
function wtcc_get_enabled_flat_rate_boxes() {
	$all_boxes = wtcc_get_flat_rate_boxes();
	$enabled = get_option( 'wtcc_enabled_flat_rate_boxes', array_keys( $all_boxes ) );
	
	if ( empty( $enabled ) ) {
		return $all_boxes;
	}
	
	return array_intersect_key( $all_boxes, array_flip( $enabled ) );
}

/**
 * Check if an item fits in a specific flat rate box
 * 
 * @param array $item Item dimensions (length, width, height in inches, weight in oz)
 * @param array $box  Box definition
 * @return bool True if item fits
 */
function wtcc_item_fits_flat_rate_box( $item, $box ) {
	// Check weight first
	$item_weight = floatval( $item['weight_oz'] ?? $item['weight'] ?? 0 );
	if ( $item_weight > $box['max_weight_oz'] ) {
		return false;
	}
	
	// Get item dimensions
	$item_dims = array(
		floatval( $item['length'] ?? 0 ),
		floatval( $item['width'] ?? 0 ),
		floatval( $item['height'] ?? 0 ),
	);
	rsort( $item_dims ); // Largest first
	
	// Get box dimensions
	$box_dims = array(
		floatval( $box['length'] ),
		floatval( $box['width'] ),
		floatval( $box['height'] ),
	);
	rsort( $box_dims ); // Largest first
	
	// Item must fit in each dimension (with small tolerance)
	$tolerance = 0.1; // 0.1 inch tolerance
	for ( $i = 0; $i < 3; $i++ ) {
		if ( $item_dims[ $i ] > ( $box_dims[ $i ] + $tolerance ) ) {
			return false;
		}
	}
	
	return true;
}

/**
 * Find the best flat rate box for given items
 * Returns the smallest/cheapest box that fits all items
 * 
 * @param array $items Array of items with dimensions and weight
 * @return array|false Best box or false if none fit
 */
function wtcc_find_best_flat_rate_box( $items ) {
	$boxes = wtcc_get_enabled_flat_rate_boxes();
	
	// Calculate total dimensions needed (simple approach - sum volumes)
	$total_weight = 0;
	$max_length = 0;
	$max_width = 0;
	$max_height = 0;
	$total_volume = 0;
	
	foreach ( $items as $item ) {
		$qty = intval( $item['quantity'] ?? 1 );
		$weight = floatval( $item['weight_oz'] ?? $item['weight'] ?? 0 ) * $qty;
		$total_weight += $weight;
		
		$l = floatval( $item['length'] ?? 0 );
		$w = floatval( $item['width'] ?? 0 );
		$h = floatval( $item['height'] ?? 0 );
		
		$max_length = max( $max_length, $l );
		$max_width = max( $max_width, $w );
		$max_height = max( $max_height, $h );
		$total_volume += ( $l * $w * $h ) * $qty;
	}
	
	// Check weight limit first
	if ( $total_weight > 1120 ) { // 70 lbs max
		return false;
	}
	
	// Sort boxes by price (cheapest first)
	$pricing_type = wtcc_get_flat_rate_pricing_type();
	uasort( $boxes, function( $a, $b ) use ( $pricing_type ) {
		$price_a = $a[ 'price_' . $pricing_type ] ?? $a['price_retail'] ?? 999;
		$price_b = $b[ 'price_' . $pricing_type ] ?? $b['price_retail'] ?? 999;
		return $price_a <=> $price_b;
	} );
	
	// Find smallest box that fits
	foreach ( $boxes as $box_key => $box ) {
		// Skip zone-based boxes
		if ( ! empty( $box['zone_based'] ) ) {
			continue;
		}
		
		// Check if largest item dimension fits
		$box_dims = array( $box['length'], $box['width'], $box['height'] );
		rsort( $box_dims );
		
		$item_max_dims = array( $max_length, $max_width, $max_height );
		rsort( $item_max_dims );
		
		$fits_dimensions = true;
		for ( $i = 0; $i < 3; $i++ ) {
			if ( $item_max_dims[ $i ] > $box_dims[ $i ] + 0.1 ) {
				$fits_dimensions = false;
				break;
			}
		}
		
		if ( ! $fits_dimensions ) {
			continue;
		}
		
		// Check box volume vs total volume
		$box_volume = $box['length'] * $box['width'] * $box['height'];
		if ( $total_volume > $box_volume ) {
			continue;
		}
		
		// Check weight
		if ( $total_weight > $box['max_weight_oz'] ) {
			continue;
		}
		
		// This box works!
		return array_merge( $box, array( 'key' => $box_key ) );
	}
	
	return false;
}

/**
 * Get the pricing type to use (retail, commercial, or business)
 * 
 * @return string Pricing type key
 */
function wtcc_get_flat_rate_pricing_type() {
	$type = get_option( 'wtcc_flat_rate_pricing_type', 'commercial' );
	
	if ( ! in_array( $type, array( 'retail', 'commercial', 'business' ), true ) ) {
		$type = 'commercial';
	}
	
	return $type;
}

/**
 * Get the price for a specific flat rate box
 * 
 * @param string $box_key Box key
 * @return float|false Price or false if not found
 */
function wtcc_get_flat_rate_box_price( $box_key ) {
	$boxes = wtcc_get_flat_rate_boxes();
	
	if ( ! isset( $boxes[ $box_key ] ) ) {
		return false;
	}
	
	$box = $boxes[ $box_key ];
	$pricing_type = wtcc_get_flat_rate_pricing_type();
	
	$price = $box[ 'price_' . $pricing_type ] ?? $box['price_retail'] ?? false;
	
	// Apply any markup/discount
	$markup = floatval( get_option( 'wtcc_flat_rate_markup', 0 ) );
	if ( $markup !== 0 && $price !== false ) {
		$price += $markup;
	}
	
	return $price;
}

/**
 * Calculate flat rate shipping for cart items
 * Returns all valid flat rate options
 * 
 * @param array $items Cart items with dimensions
 * @return array Flat rate options with prices
 */
function wtcc_calculate_flat_rate_options( $items ) {
	$options = array();
	$best_box = wtcc_find_best_flat_rate_box( $items );
	
	if ( $best_box ) {
		$price = wtcc_get_flat_rate_box_price( $best_box['key'] );
		
		if ( $price !== false ) {
			$options[] = array(
				'box_key'     => $best_box['key'],
				'box_name'    => $best_box['name'],
				'box_type'    => $best_box['type'],
				'price'       => $price,
				'usps_code'   => $best_box['usps_code'],
				'description' => $best_box['description'] ?? '',
			);
		}
	}
	
	return apply_filters( 'wtcc_flat_rate_options', $options, $items );
}

/**
 * Check if flat rate shipping should be offered
 * Based on admin settings and item compatibility
 * 
 * @return bool True if flat rate should be offered
 */
function wtcc_should_offer_flat_rate() {
	// Check if globally enabled
	if ( get_option( 'wtcc_flat_rate_enabled', 'yes' ) !== 'yes' ) {
		return false;
	}
	
	// Check if any flat rate boxes are enabled
	$enabled_boxes = wtcc_get_enabled_flat_rate_boxes();
	if ( empty( $enabled_boxes ) ) {
		return false;
	}
	
	return true;
}

/**
 * Get order's assigned flat rate box (if any)
 * 
 * @param int $order_id Order ID
 * @return array|false Box info or false
 */
function wtcc_get_order_flat_rate_box( $order_id ) {
	$box_key = get_post_meta( $order_id, '_wtcc_flat_rate_box', true );
	
	if ( empty( $box_key ) ) {
		return false;
	}
	
	$boxes = wtcc_get_flat_rate_boxes();
	
	if ( ! isset( $boxes[ $box_key ] ) ) {
		return false;
	}
	
	return array_merge( $boxes[ $box_key ], array( 'key' => $box_key ) );
}

/**
 * Set order's flat rate box
 * 
 * @param int    $order_id Order ID
 * @param string $box_key  Box key
 * @return bool Success
 */
function wtcc_set_order_flat_rate_box( $order_id, $box_key ) {
	$boxes = wtcc_get_flat_rate_boxes();
	
	if ( ! isset( $boxes[ $box_key ] ) ) {
		return false;
	}
	
	update_post_meta( $order_id, '_wtcc_flat_rate_box', $box_key );
	update_post_meta( $order_id, '_wtcc_flat_rate_box_name', $boxes[ $box_key ]['name'] );
	update_post_meta( $order_id, '_wtcc_flat_rate_usps_code', $boxes[ $box_key ]['usps_code'] );
	
	return true;
}

/**
 * Compare flat rate vs calculated rate
 * Returns the cheaper option
 * 
 * @param float $calculated_rate The calculated weight/zone rate
 * @param array $items           Cart items
 * @return array Best option info
 */
function wtcc_compare_flat_rate_vs_calculated( $calculated_rate, $items ) {
	$flat_rate_options = wtcc_calculate_flat_rate_options( $items );
	
	if ( empty( $flat_rate_options ) ) {
		return array(
			'use_flat_rate' => false,
			'rate'          => $calculated_rate,
			'reason'        => 'No flat rate boxes fit items',
		);
	}
	
	$best_flat_rate = $flat_rate_options[0];
	
	// Check admin preference
	$preference = get_option( 'wtcc_flat_rate_preference', 'cheaper' );
	
	switch ( $preference ) {
		case 'always':
			// Always use flat rate if available
			return array(
				'use_flat_rate' => true,
				'rate'          => $best_flat_rate['price'],
				'box'           => $best_flat_rate,
				'reason'        => 'Admin preference: always use flat rate',
			);
			
		case 'never':
			// Never use flat rate
			return array(
				'use_flat_rate' => false,
				'rate'          => $calculated_rate,
				'reason'        => 'Admin preference: never use flat rate',
			);
			
		case 'cheaper':
		default:
			// Use whichever is cheaper
			if ( $best_flat_rate['price'] < $calculated_rate ) {
				return array(
					'use_flat_rate' => true,
					'rate'          => $best_flat_rate['price'],
					'box'           => $best_flat_rate,
					'reason'        => 'Flat rate is cheaper',
					'savings'       => $calculated_rate - $best_flat_rate['price'],
				);
			} else {
				return array(
					'use_flat_rate' => false,
					'rate'          => $calculated_rate,
					'reason'        => 'Calculated rate is cheaper',
					'difference'    => $best_flat_rate['price'] - $calculated_rate,
				);
			}
	}
}

// ============================================
// ADMIN SETTINGS
// ============================================

/**
 * Register flat rate settings
 */
add_action( 'admin_init', 'wtcc_register_flat_rate_settings' );
function wtcc_register_flat_rate_settings() {
	register_setting( 'wtcc_flat_rate_settings', 'wtcc_flat_rate_enabled' );
	register_setting( 'wtcc_flat_rate_settings', 'wtcc_flat_rate_pricing_type' );
	register_setting( 'wtcc_flat_rate_settings', 'wtcc_flat_rate_preference' );
	register_setting( 'wtcc_flat_rate_settings', 'wtcc_flat_rate_markup' );
	register_setting( 'wtcc_flat_rate_settings', 'wtcc_enabled_flat_rate_boxes' );
	register_setting( 'wtcc_flat_rate_settings', 'wtcc_flat_rate_price_overrides' );
}

/**
 * Handle saving flat rate settings
 */
add_action( 'admin_init', 'wtcc_save_flat_rate_settings' );
function wtcc_save_flat_rate_settings() {
	if ( ! isset( $_POST['wtcc_save_flat_rate_settings'] ) ) {
		return;
	}
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'wtcc_flat_rate_settings' ) ) {
		return;
	}
	
	// Save enabled status
	update_option( 'wtcc_flat_rate_enabled', sanitize_key( $_POST['flat_rate_enabled'] ?? 'yes' ) );
	
	// Save pricing type
	$pricing_type = sanitize_key( $_POST['flat_rate_pricing_type'] ?? 'commercial' );
	if ( ! in_array( $pricing_type, array( 'retail', 'commercial', 'business' ), true ) ) {
		$pricing_type = 'commercial';
	}
	update_option( 'wtcc_flat_rate_pricing_type', $pricing_type );
	
	// Save preference
	$preference = sanitize_key( $_POST['flat_rate_preference'] ?? 'cheaper' );
	if ( ! in_array( $preference, array( 'cheaper', 'always', 'never' ), true ) ) {
		$preference = 'cheaper';
	}
	update_option( 'wtcc_flat_rate_preference', $preference );
	
	// Save markup
	update_option( 'wtcc_flat_rate_markup', floatval( $_POST['flat_rate_markup'] ?? 0 ) );
	
	// Save enabled boxes
	$enabled_boxes = isset( $_POST['enabled_boxes'] ) ? array_map( 'sanitize_key', $_POST['enabled_boxes'] ) : array();
	update_option( 'wtcc_enabled_flat_rate_boxes', $enabled_boxes );
	
	// Save price overrides
	$overrides = array();
	if ( isset( $_POST['price_override'] ) && is_array( $_POST['price_override'] ) ) {
		foreach ( $_POST['price_override'] as $box_key => $prices ) {
			$box_key = sanitize_key( $box_key );
			$overrides[ $box_key ] = array(
				'retail'     => ! empty( $prices['retail'] ) ? floatval( $prices['retail'] ) : null,
				'commercial' => ! empty( $prices['commercial'] ) ? floatval( $prices['commercial'] ) : null,
			);
		}
	}
	update_option( 'wtcc_flat_rate_price_overrides', $overrides );
	
	add_settings_error( 'wtcc_flat_rate', 'saved', 'Flat rate settings saved.', 'success' );
}

// ============================================
// SHIPPING METHOD INTEGRATION
// ============================================

/**
 * Add flat rate as a shipping option in WooCommerce
 */
add_filter( 'woocommerce_package_rates', 'wtcc_add_flat_rate_shipping_option', 15, 2 );
function wtcc_add_flat_rate_shipping_option( $rates, $package ) {
	if ( ! wtcc_should_offer_flat_rate() ) {
		return $rates;
	}
	
	// Get cart items with dimensions
	$items = array();
	foreach ( $package['contents'] as $item ) {
		$product = $item['data'];
		
		$items[] = array(
			'quantity'  => $item['quantity'],
			'weight_oz' => wtcc_shipping_convert_to_oz( 
				$product->get_weight() ?: 0, 
				get_option( 'woocommerce_weight_unit', 'lbs' ) 
			),
			'length'    => floatval( $product->get_length() ?: 0 ),
			'width'     => floatval( $product->get_width() ?: 0 ),
			'height'    => floatval( $product->get_height() ?: 0 ),
		);
	}
	
	// Calculate flat rate options
	$flat_rate_options = wtcc_calculate_flat_rate_options( $items );
	
	if ( empty( $flat_rate_options ) ) {
		return $rates;
	}
	
	$best_option = $flat_rate_options[0];
	$preference = get_option( 'wtcc_flat_rate_preference', 'cheaper' );
	
	// Add flat rate as an option
	$flat_rate = new WC_Shipping_Rate(
		'wtc_flat_rate_' . $best_option['box_key'],
		sprintf( 
			__( 'USPS Priority Mail Flat Rate (%s)', 'wtc-shipping' ), 
			$best_option['box_name'] 
		),
		$best_option['price'],
		array(),
		'wtc_flat_rate'
	);
	
	// Add meta for order processing
	$flat_rate->add_meta_data( 'flat_rate_box', $best_option['box_key'] );
	$flat_rate->add_meta_data( 'usps_code', $best_option['usps_code'] );
	
	$rates[ 'wtc_flat_rate_' . $best_option['box_key'] ] = $flat_rate;
	
	return $rates;
}

/**
 * Save flat rate box selection to order
 */
add_action( 'woocommerce_checkout_create_order', 'wtcc_save_flat_rate_to_order', 10, 2 );
function wtcc_save_flat_rate_to_order( $order, $data ) {
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	
	if ( empty( $chosen_methods ) ) {
		return;
	}
	
	foreach ( $chosen_methods as $method ) {
		if ( strpos( $method, 'wtc_flat_rate_' ) === 0 ) {
			$box_key = str_replace( 'wtc_flat_rate_', '', $method );
			wtcc_set_order_flat_rate_box( $order->get_id(), $box_key );
			break;
		}
	}
}

// ============================================
// ORDER ADMIN DISPLAY
// ============================================

/**
 * Display flat rate box info in order admin
 */
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'wtcc_display_flat_rate_box_admin', 15 );
function wtcc_display_flat_rate_box_admin( $order ) {
	$box = wtcc_get_order_flat_rate_box( $order->get_id() );
	
	if ( ! $box ) {
		return;
	}
	
	?>
	<div class="wtcc-flat-rate-box-info" style="margin-top: 15px; padding: 10px; background: #e7f5ff; border-left: 3px solid #0073aa;">
		<strong>📦 Flat Rate Box:</strong> <?php echo esc_html( $box['name'] ); ?><br>
		<small>
			USPS Code: <?php echo esc_html( $box['usps_code'] ); ?> | 
			Dimensions: <?php echo esc_html( $box['length'] . '" × ' . $box['width'] . '" × ' . $box['height'] . '"' ); ?>
		</small>
	</div>
	<?php
}

/**
 * Add flat rate box override dropdown to order admin
 */
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'wtcc_add_flat_rate_override_admin', 20 );
function wtcc_add_flat_rate_override_admin( $order ) {
	$current_box = get_post_meta( $order->get_id(), '_wtcc_flat_rate_box', true );
	$boxes = wtcc_get_flat_rate_boxes();
	
	?>
	<div class="wtcc-flat-rate-override" style="margin-top: 10px;">
		<label for="wtcc_flat_rate_override"><strong>📦 Override Flat Rate Box:</strong></label>
		<select name="wtcc_flat_rate_override" id="wtcc_flat_rate_override" style="width: 100%; margin-top: 5px;">
			<option value="">— Use calculated/no override —</option>
			<?php foreach ( $boxes as $key => $box ) : ?>
				<?php if ( empty( $box['zone_based'] ) ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_box, $key ); ?>>
						<?php echo esc_html( $box['name'] ); ?> 
						— $<?php echo esc_html( number_format( $box['price_retail'], 2 ) ); ?>
					</option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select>
		<p class="description" style="margin-top: 5px;">Select a flat rate box to override the calculated shipping for labels.</p>
	</div>
	<?php
}

/**
 * Save flat rate box override from order admin
 */
add_action( 'woocommerce_process_shop_order_meta', 'wtcc_save_flat_rate_override_admin', 10, 2 );
function wtcc_save_flat_rate_override_admin( $order_id, $post ) {
	if ( isset( $_POST['wtcc_flat_rate_override'] ) ) {
		$box_key = sanitize_key( $_POST['wtcc_flat_rate_override'] );
		
		if ( empty( $box_key ) ) {
			delete_post_meta( $order_id, '_wtcc_flat_rate_box' );
			delete_post_meta( $order_id, '_wtcc_flat_rate_box_name' );
			delete_post_meta( $order_id, '_wtcc_flat_rate_usps_code' );
		} else {
			wtcc_set_order_flat_rate_box( $order_id, $box_key );
		}
	}
}

// ============================================
// LABEL INTEGRATION
// ============================================

/**
 * Get USPS mail class for flat rate box
 * Used when creating shipping labels
 * 
 * @param string $box_key Box key
 * @return string USPS mail class
 */
function wtcc_get_flat_rate_mail_class( $box_key ) {
	$boxes = wtcc_get_flat_rate_boxes();
	
	if ( ! isset( $boxes[ $box_key ] ) ) {
		return 'PRIORITY_MAIL';
	}
	
	$box = $boxes[ $box_key ];
	
	// Map box types to USPS mail classes
	$mail_classes = array(
		'FLAT_RATE_ENVELOPE'         => 'PRIORITY_MAIL_FLAT_RATE_ENVELOPE',
		'PADDED_FLAT_RATE_ENVELOPE'  => 'PRIORITY_MAIL_PADDED_FLAT_RATE_ENVELOPE',
		'LEGAL_FLAT_RATE_ENVELOPE'   => 'PRIORITY_MAIL_LEGAL_FLAT_RATE_ENVELOPE',
		'SMALL_FLAT_RATE_ENVELOPE'   => 'PRIORITY_MAIL_SMALL_FLAT_RATE_ENVELOPE',
		'WINDOW_FLAT_RATE_ENVELOPE'  => 'PRIORITY_MAIL_WINDOW_FLAT_RATE_ENVELOPE',
		'GIFT_CARD_FLAT_RATE_ENVELOPE' => 'PRIORITY_MAIL_GIFT_CARD_FLAT_RATE_ENVELOPE',
		'SMALL_FLAT_RATE_BOX'        => 'PRIORITY_MAIL_SMALL_FLAT_RATE_BOX',
		'MEDIUM_FLAT_RATE_BOX'       => 'PRIORITY_MAIL_MEDIUM_FLAT_RATE_BOX',
		'LARGE_FLAT_RATE_BOX'        => 'PRIORITY_MAIL_LARGE_FLAT_RATE_BOX',
		'LARGE_FLAT_RATE_BOX_APO'    => 'PRIORITY_MAIL_LARGE_FLAT_RATE_BOX_APO',
		'BOARD_GAME_FLAT_RATE_BOX'   => 'PRIORITY_MAIL_LARGE_FLAT_RATE_BOX',
		'REGIONAL_RATE_BOX_A'        => 'PRIORITY_MAIL_REGIONAL_RATE_BOX_A',
		'REGIONAL_RATE_BOX_B'        => 'PRIORITY_MAIL_REGIONAL_RATE_BOX_B',
	);
	
	return $mail_classes[ $box['usps_code'] ] ?? 'PRIORITY_MAIL';
}

/**
 * Get USPS container type for flat rate box
 * Used in USPS API requests
 * 
 * @param string $box_key Box key
 * @return string Container type
 */
function wtcc_get_flat_rate_container_type( $box_key ) {
	$boxes = wtcc_get_flat_rate_boxes();
	
	if ( ! isset( $boxes[ $box_key ] ) ) {
		return 'RECTANGULAR';
	}
	
	$box = $boxes[ $box_key ];
	
	// Map to USPS container types
	$containers = array(
		'FLAT_RATE_ENVELOPE'         => 'FLAT_RATE_ENVELOPE',
		'PADDED_FLAT_RATE_ENVELOPE'  => 'FLAT_RATE_PADDED_ENVELOPE',
		'LEGAL_FLAT_RATE_ENVELOPE'   => 'FLAT_RATE_LEGAL_ENVELOPE',
		'SMALL_FLAT_RATE_ENVELOPE'   => 'FLAT_RATE_ENVELOPE',
		'WINDOW_FLAT_RATE_ENVELOPE'  => 'FLAT_RATE_ENVELOPE',
		'GIFT_CARD_FLAT_RATE_ENVELOPE' => 'FLAT_RATE_ENVELOPE',
		'SMALL_FLAT_RATE_BOX'        => 'SM_FLAT_RATE_BOX',
		'MEDIUM_FLAT_RATE_BOX'       => 'MD_FLAT_RATE_BOX',
		'LARGE_FLAT_RATE_BOX'        => 'LG_FLAT_RATE_BOX',
		'LARGE_FLAT_RATE_BOX_APO'    => 'LG_FLAT_RATE_BOX',
		'BOARD_GAME_FLAT_RATE_BOX'   => 'LG_FLAT_RATE_BOX',
		'REGIONAL_RATE_BOX_A'        => 'REGIONALRATEBOXA',
		'REGIONAL_RATE_BOX_B'        => 'REGIONALRATEBOXB',
	);
	
	return $containers[ $box['usps_code'] ] ?? 'RECTANGULAR';
}
