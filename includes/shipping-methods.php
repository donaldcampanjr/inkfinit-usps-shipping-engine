<?php
/**
 * WooCommerce Shipping Methods Registration
 * Creates shipping method classes that tie into the rule engine
 * 
 * IMPORTANT: These methods only register for Pro+ tier.
 * Free tier does NOT get checkout shipping functionality.
 * 
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize shipping classes when WooCommerce is ready.
 * 
 * PRO+ ONLY: Free tier users do not get WooCommerce checkout integration.
 * The Free tier is for admin research calculator only.
 */
add_action( 'woocommerce_shipping_init', 'wtcc_init_shipping_classes' );
function wtcc_init_shipping_classes() {
	// PRO+ TIER GATE: Free tier does not get shipping methods at checkout.
	if ( function_exists( 'wtcc_is_pro' ) && ! wtcc_is_pro() ) {
		return; // Exit - no shipping methods for Free tier.
	}
	
	// Load base class
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/class-shipping-method.php';
	
	// Bail if base class didn't load (WooCommerce not ready)
	if ( ! class_exists( 'WTC_Shipping_Method' ) ) {
		return;
	}

	/**
	 * Ground - USPS Ground Advantage
	 */
	if ( ! class_exists( 'WTC_Shipping_Ground' ) ) {
		class WTC_Shipping_Ground extends WTC_Shipping_Method {
			public function __construct( $instance_id = 0 ) {
				$this->id                 = 'wtc_ground';
				$this->method_title       = __( 'USPS Ground Advantage', 'wtc-shipping' );
				$this->method_description = __( 'Economical ground shipping', 'wtc-shipping' );
				$this->group              = 'ground';
				parent::__construct( $instance_id );
			}
		}
	}

	/**
	 * Priority - USPS Priority Mail
	 */
	if ( ! class_exists( 'WTC_Shipping_Priority' ) ) {
		class WTC_Shipping_Priority extends WTC_Shipping_Method {
			public function __construct( $instance_id = 0 ) {
				$this->id                 = 'wtc_priority';
				$this->method_title       = __( 'USPS Priority Mail', 'wtc-shipping' );
				$this->method_description = __( '1-3 day delivery', 'wtc-shipping' );
				$this->group              = 'priority';
				parent::__construct( $instance_id );
			}
		}
	}

	/**
	 * Express - USPS Priority Mail Express
	 */
	if ( ! class_exists( 'WTC_Shipping_Express' ) ) {
		class WTC_Shipping_Express extends WTC_Shipping_Method {
			public function __construct( $instance_id = 0 ) {
				$this->id                 = 'wtc_express';
				$this->method_title       = __( 'USPS Priority Mail Express', 'wtc-shipping' );
				$this->method_description = __( 'Overnight to 2-day guaranteed', 'wtc-shipping' );
				$this->group              = 'express';
				parent::__construct( $instance_id );
			}
		}
	}
}

/**
 * Register our shipping methods with WooCommerce.
 * 
 * PRO+ ONLY: Free tier does not register shipping methods.
 */
add_filter( 'woocommerce_shipping_methods', 'wtcc_shipping_register_methods' );
function wtcc_shipping_register_methods( $methods ) {
	// PRO+ TIER GATE: Free tier does not get shipping methods.
	if ( function_exists( 'wtcc_is_pro' ) && ! wtcc_is_pro() ) {
		return $methods; // Return unchanged - no WTC methods for Free tier.
	}
	
	$methods['wtc_ground']      = 'WTC_Shipping_Ground';
	$methods['wtc_priority']    = 'WTC_Shipping_Priority';
	$methods['wtc_express']     = 'WTC_Shipping_Express';
	return $methods;
}

/**
 * Remove duplicate shipping rates at checkout
 * 
 * WooCommerce can show duplicates if the same method is in multiple zones.
 * This keeps only the cheapest rate for each shipping method type.
 */
add_filter( 'woocommerce_package_rates', 'wtcc_shipping_remove_duplicate_rates', 100, 2 );
function wtcc_shipping_remove_duplicate_rates( $rates, $package ) {
	if ( empty( $rates ) ) {
		return $rates;
	}

	// Group rates by method label (e.g., "USPS First Class Mail")
	$grouped = array();
	foreach ( $rates as $rate_id => $rate ) {
		$label = $rate->get_label();
		
		// Track the cheapest rate for each label
		if ( ! isset( $grouped[ $label ] ) || $rate->get_cost() < $grouped[ $label ]['cost'] ) {
			$grouped[ $label ] = array(
				'rate_id' => $rate_id,
				'cost'    => $rate->get_cost(),
			);
		}
	}

	// Build new rates array with only cheapest of each type
	$unique_rates = array();
	foreach ( $grouped as $label => $data ) {
		$unique_rates[ $data['rate_id'] ] = $rates[ $data['rate_id'] ];
	}

	return $unique_rates;
}

/**
 * Sort shipping rates by cost (cheapest first)
 */
add_filter( 'woocommerce_package_rates', 'wtcc_shipping_sort_rates_by_cost', 110, 2 );
function wtcc_shipping_sort_rates_by_cost( $rates, $package ) {
	if ( empty( $rates ) || count( $rates ) < 2 ) {
		return $rates;
	}

	// Convert to array for sorting
	$rates_array = array();
	foreach ( $rates as $rate_id => $rate ) {
		$rates_array[ $rate_id ] = $rate;
	}

	// Sort by cost (ascending - cheapest first)
	uasort( $rates_array, function( $a, $b ) {
		$cost_a = (float) $a->get_cost();
		$cost_b = (float) $b->get_cost();
		
		if ( $cost_a === $cost_b ) {
			return 0;
		}
		return ( $cost_a < $cost_b ) ? -1 : 1;
	});

	return $rates_array;
}

/**
 * Add rate update timestamp to shipping rate labels
 */
add_filter( 'woocommerce_cart_shipping_method_full_label', 'wtcc_shipping_add_rate_timestamp', 10, 2 );
function wtcc_shipping_add_rate_timestamp( $label, $method ) {
	// Only add to our WTC shipping methods
	if ( strpos( $method->get_id(), 'wtc_' ) !== 0 ) {
		return $label;
	}
	
	// Get the rate timestamp based on method
	$timestamp = wtcc_get_rate_update_timestamp( $method->get_id() );
	
	if ( $timestamp ) {
		$time_ago = human_time_diff( $timestamp, current_time( 'timestamp' ) );
		$label .= '<br><small class="wtcc-rate-timestamp">Rate updated ' . esc_html( $time_ago ) . ' ago via USPS API</small>';
	} else {
		// Check if using manual rates
		$label .= '<br><small class="wtcc-rate-timestamp">Estimated rate</small>';
	}
	
	return $label;
}

/**
 * Get the timestamp when a rate was last fetched from API
 */
function wtcc_get_rate_update_timestamp( $method_id ) {
	// Map method ID to service group
	$service_map = array(
		'wtc_ground'      => 'ground',
		'wtc_priority'    => 'priority',
		'wtc_express'     => 'express',
	);
	
	// Extract base method ID (remove instance suffix like :1)
	$base_method_id = explode( ':', $method_id )[0];
	
	if ( ! isset( $service_map[ $base_method_id ] ) ) {
		return false;
	}
	
	$service = $service_map[ $base_method_id ];
	
	// Get cart contents for weight calculation
	$weight_oz = 0;
	if ( WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( $product && function_exists( 'wtcc_shipping_get_product_weight_oz' ) ) {
				$weight_oz += wtcc_shipping_get_product_weight_oz( $product ) * $cart_item['quantity'];
			}
		}
	}
	
	// Get destination
	$dest_zip = '';
	$dest_country = 'US';
	if ( WC()->customer ) {
		$dest_zip = WC()->customer->get_shipping_postcode();
		$dest_country = WC()->customer->get_shipping_country();
	}
	
	// Get origin ZIP
	$from_zip = get_option( 'wtcc_origin_zip', '' );
	
	if ( empty( $from_zip ) || empty( $dest_zip ) ) {
		return false;
	}
	
	// Ensure minimum weight
	if ( $weight_oz < 0.1 ) {
		$weight_oz = 0.1;
	}
	
	// Build cache key matching the one in usps-api.php
	$cache_time_key = 'wtcc_usps_rate_time_' . md5( "$service|$weight_oz|$from_zip|$dest_zip|$dest_country" );
	
	return get_transient( $cache_time_key );
}
