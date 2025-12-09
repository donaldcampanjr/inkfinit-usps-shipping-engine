<?php
/**
 * Zero-Knowledge Shipping Calculator
 * 
 * Automatically calculates shipping costs based on:
 * - Cart weight
 * - Destination zone
 * - Shipping group (First Class, Ground, Priority, Express)
 * 
 * Admin only sets BASE COSTS and ZONE MULTIPLIERS.
 * Plugin handles all logic automatically.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wtcc_get_flat_rate_boxes' ) ) {
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/flat-rate-boxes.php';
}

/**
 * Get default shipping rates configuration
 * 
 * These are the ONLY settings admin needs to configure:
 * - Base cost per group
 * - Zone multipliers (markup for international)
 * 
 * ALWAYS returns a config, even if empty. Never returns false/null.
 * 
 * @return array Shipping rates config
 */
function wtcc_shipping_get_rates_config() {
	$saved = get_option( 'wtcc_shipping_rates_config' );
	
	// If saved config exists and is valid, use it
	if ( is_array( $saved ) && ! empty( $saved ) ) {
		return $saved;
	}
	
	// Otherwise return hardcoded defaults (ensures shipping always works)
	// Note: First Class was merged into Ground Advantage by USPS in 2023
	return array(
		'ground'      => array(
			'base_cost'   => 5.50,
			'per_oz'      => 0.15,
			'max_weight'  => 70,
		),
		'priority'    => array(
			'base_cost'   => 10.50,
			'per_oz'      => 0.22,
			'max_weight'  => 70,
		),
		'express'     => array(
			'base_cost'   => 26.99,
			'per_oz'      => 0.35,
			'max_weight'  => 70,
		),
		'zone_multipliers' => array(
			'usa'            => 1.0,
			'canada'         => 1.5,
			'uk'             => 2.0,
			'eu1'            => 2.2,
			'eu2'            => 2.5,
			'apac'           => 3.0,
			'asia'           => 3.2,
			'south-america'  => 2.8,
			'middle-east'    => 3.0,
			'africa'         => 3.5,
			'rest-of-world'  => 3.0,
		),
	);
}

/**
 * Auto-calculate shipping cost
 * 
 * Takes weight + zone + group and returns cost.
 * Admin never creates rules — plugin calculates automatically.
 * 
 * ALWAYS returns a valid cost. Returns 0 + zone markup as fallback.
 *
 * @param string $group Shipping group (first_class, ground, priority, express).
 * @param float  $total_weight_oz Total weight in ounces.
 * @param string $zone Shipping zone (usa, canada, eu1, etc).
 * @return float Calculated cost (never null)
 */
function wtcc_shipping_calculate_cost_auto( $group, $total_weight_oz, $zone = 'usa' ) {
	try {
		// Sanitize inputs
		$group = sanitize_text_field( $group );
		$zone  = wtcc_shipping_validate_zone( $zone );
		$total_weight_oz = wtcc_shipping_validate_numeric( $total_weight_oz, 'total_weight_oz' );

		if ( null === $total_weight_oz || $total_weight_oz < 0 ) {
			$total_weight_oz = 0;
		}

		$config = wtcc_shipping_get_rates_config();

		// Config should never be empty due to fallback in wtcc_shipping_get_rates_config
		if ( ! is_array( $config ) || empty( $config ) ) {
			// Absolute fallback if something goes very wrong
			return 9.99;
		}

		// Check if group exists in config
		if ( ! isset( $config[ $group ] ) ) {
			// Group not found - return a reasonable default
			error_log( "Inkfinit Shipping: Group '{$group}' not found in config. Using ground rate." );
			$group = 'ground';
			
			if ( ! isset( $config[ $group ] ) ) {
				return 9.99;
			}
		}

		$group_config = $config[ $group ];
		
		// Validate and sanitize config values
		$base_cost = wtcc_shipping_sanitize_amount( $group_config['base_cost'] ?? 9.99 );
		$per_oz    = wtcc_shipping_sanitize_amount( $group_config['per_oz'] ?? 0.15 );
		$max_weight = wtcc_shipping_sanitize_amount( $group_config['max_weight'] ?? 70 );

		// Check if weight exceeds max
		if ( $total_weight_oz > ( $max_weight * 16 ) ) {
			// Weight too heavy for this method - DON'T return null, use base cost only
			error_log( "Inkfinit Shipping: Package too heavy ({$total_weight_oz} oz) for {$group}, using base cost only" );
			$cost = $base_cost;
		} else {
			// Calculate base + weight cost
			$cost = $base_cost + ( $total_weight_oz * $per_oz );
		}

		// Apply zone multiplier
		$multipliers = $config['zone_multipliers'] ?? array();
		$multiplier  = $multipliers[ $zone ] ?? 1.0;
		$cost        = $cost * $multiplier;

		// Ensure cost is always positive
		return max( 0.5, round( (float) $cost, 2 ) );
	} catch ( Exception $e ) {
		error_log( 'Inkfinit Shipping Calculate Error: ' . $e->getMessage() );
		// Return minimum valid cost on error
		return 9.99;
	}
}

/**
 * Calculate shipping cost for a package
 * Plugin automatically determines weight, zone, and returns costs for all groups
 * Now supports multi-package orders via smart box-packing
 *
 * @param string $group Shipping group.
 * @param array  $package WooCommerce package.
 * @return float Cost (NEVER null - always returns valid cost or fallback)
 */
function wtcc_shipping_calculate_cost( $group, $package ) {
	try {
		// Check for a matching rule first
		$rules = get_option( 'wtcc_shipping_rules', array() );
		if ( ! empty( $rules ) ) {
			foreach ( $rules as $rule ) {
				if ( wtcc_shipping_rule_matches( $rule, $package, $group ) ) {
					// Rule matched, apply the action
					$action = $rule['action'] ?? 'fixed_cost';
					switch ( $action ) {
						case 'fixed_cost':
							return (float) ( $rule['cost'] ?? 0 );
						case 'flat_rate_box':
							$box_key = $rule['flat_rate_box'] ?? '';
							$all_boxes = wtcc_get_flat_rate_boxes();
							if ( isset( $all_boxes[ $box_key ] ) ) {
								$pricing_type = get_option( 'wtcc_flat_rate_options', array() )['pricing_type'] ?? 'commercial';
								$price_key = 'price_' . $pricing_type;
								return (float) ( $all_boxes[ $box_key ][ $price_key ] ?? 99.99 );
							}
							break; // Fall through if box not found
						case 'disable_method':
							return false; // Explicitly disable this method
					}
				}
			}
		}

		// Get destination info
		$destination = $package['destination'] ?? array();
		$country     = $destination['country'] ?? 'US';
		$postcode    = $destination['postcode'] ?? '';
		
		// Get zone safely
		$zones = wtcc_shipping_get_country_zones();
		$zone = $zones[ strtoupper( $country ) ] ?? 'rest-of-world';

		// USPS API credentials check
		$usps_consumer_key = get_option( 'wtcc_usps_consumer_key', '' );
		$usps_consumer_secret = get_option( 'wtcc_usps_consumer_secret', '' );
		$origin_zip = get_option( 'wtcc_origin_zip', '' );
		$has_api = ! empty( $usps_consumer_key ) && ! empty( $usps_consumer_secret ) && ! empty( $postcode ) && ! empty( $origin_zip );

		// Try multi-package calculation with smart box packing
		if ( function_exists( 'wtcc_shipping_get_shipping_packages' ) ) {
			$shipping_packages = wtcc_shipping_get_shipping_packages( $package );
			
			if ( ! empty( $shipping_packages ) && count( $shipping_packages ) > 0 ) {
				$total_cost = 0;
				$all_api_success = true;
				
				// Calculate rate for each package
				foreach ( $shipping_packages as $pkg_index => $pkg ) {
					$pkg_weight = $pkg['weight_oz'] ?? 0;
					$pkg_dims = array(
						'length' => $pkg['length'] ?? 12,
						'width'  => $pkg['width'] ?? 12,
						'height' => $pkg['height'] ?? 6,
					);
					
					// Try USPS API first
					if ( $has_api ) {
						$api_rate = wtcc_shipping_calculate_cost_via_api( 
							$group, 
							$pkg_weight, 
							$postcode, 
							$country, 
							$pkg_dims 
						);
						
						if ( is_numeric( $api_rate ) && $api_rate > 0 ) {
							$total_cost += $api_rate;
							continue;
						}
						$all_api_success = false;
					}
					
					// Fallback to manual calculation for this package
					$pkg_cost = wtcc_shipping_calculate_cost_auto( $group, $pkg_weight, $zone );
					$total_cost += $pkg_cost;
				}
				
				// Update API status indicators
				if ( $has_api ) {
					if ( $all_api_success ) {
						set_transient( 'wtcc_usps_status_' . get_current_user_id(), 'live', 5 * MINUTE_IN_SECONDS );
						update_option( 'wtcc_last_usps_success', time() );
					} else {
						set_transient( 'wtcc_usps_status_' . get_current_user_id(), 'fallback', 5 * MINUTE_IN_SECONDS );
						update_option( 'wtcc_last_usps_failure', time() );
					}
				}
				
				// Store packing info for debug display
				set_transient( 'wtcc_last_packing_' . md5( serialize( $package ) ), array(
					'package_count' => count( $shipping_packages ),
					'packages'      => $shipping_packages,
					'total_cost'    => $total_cost,
					'group'         => $group,
				), HOUR_IN_SECONDS );
				
				return max( 0.5, round( (float) $total_cost, 2 ) );
			}
		}

		// Legacy fallback - single package calculation
		return wtcc_shipping_calculate_cost_legacy( $group, $package );
	} catch ( Exception $e ) {
		error_log( 'Inkfinit Shipping Package Error: ' . $e->getMessage() );
		// On any error, return fallback cost
		return 9.99;
	}
}

/**
 * Legacy single-package cost calculation
 * Used as fallback when smart packing is unavailable
 *
 * @param string $group Shipping group.
 * @param array  $package WooCommerce package.
 * @return float Cost
 */
function wtcc_shipping_calculate_cost_legacy( $group, $package ) {
	// Get total weight
	$total_weight = 0;
	if ( isset( $package['contents'] ) && is_array( $package['contents'] ) ) {
		foreach ( $package['contents'] as $item ) {
			if ( isset( $item['data'] ) ) {
				$product = $item['data'];
				$quantity = $item['quantity'] ?? 1;
				$item_weight_oz = wtcc_shipping_get_product_weight_oz( $product );
				$total_weight += ( $item_weight_oz * $quantity );
			}
		}
	}

	// Fallback: if weight is 0 or very small, use minimum
	if ( $total_weight <= 0 ) {
		$total_weight = 1; // 1 oz minimum
	}

	$destination = $package['destination'] ?? array();
	$country     = $destination['country'] ?? 'US';
	$postcode    = $destination['postcode'] ?? '';
	
	// Get zone safely
	$zones = wtcc_shipping_get_country_zones();
	$zone = $zones[ strtoupper( $country ) ] ?? 'rest-of-world';

	// PRIORITY: Try USPS API first if credentials are configured
	$usps_consumer_key = get_option( 'wtcc_usps_consumer_key', '' );
	$usps_consumer_secret = get_option( 'wtcc_usps_consumer_secret', '' );
	
	if ( ! empty( $usps_consumer_key ) && ! empty( $usps_consumer_secret ) && ! empty( $postcode ) ) {
		// Calculate package dimensions from actual products
		$dimensions = wtcc_shipping_calculate_package_dimensions( $package );
		
		$api_rate = wtcc_shipping_calculate_cost_via_api( $group, $total_weight, $postcode, $country, $dimensions );
		if ( is_numeric( $api_rate ) && $api_rate > 0 ) {
			// API returned valid rate - USE IT (overrides hardcoded rates)
			// Mark this calculation as USPS LIVE
			set_transient( 'wtcc_usps_status_' . get_current_user_id(), 'live', 5 * MINUTE_IN_SECONDS );
			update_option( 'wtcc_last_usps_success', time() );
			return $api_rate;
		}
		// API failed - mark as fallback
		set_transient( 'wtcc_usps_status_' . get_current_user_id(), 'fallback', 5 * MINUTE_IN_SECONDS );
		update_option( 'wtcc_last_usps_failure', time() );
	}

	// Fall back to manual calculation if API not configured or failed
	return wtcc_shipping_calculate_cost_auto( $group, $total_weight, $zone );
}

/**
 * Calculate cost using USPS API
 * Falls back to manual calculation on error
 *
 * @param string $group Shipping group.
 * @param float  $weight_oz Weight in ounces.
 * @param string $to_postcode Destination postal code.
 * @param string $to_country Destination country code.
 * @param array  $dimensions Package dimensions (length, width, height in inches).
 * @return float|false Rate or false on failure.
 */
function wtcc_shipping_calculate_cost_via_api( $group, $weight_oz, $to_postcode, $to_country = 'US', $dimensions = array() ) {
	// Get origin ZIP from settings (admin's location)
	$origin_zip = get_option( 'wtcc_origin_zip', '' );
	
	if ( empty( $origin_zip ) ) {
		return false; // No origin ZIP configured, use manual calc
	}

	// Default dimensions if not provided
	if ( empty( $dimensions ) ) {
		$dimensions = array( 'length' => 12, 'width' => 12, 'height' => 6 );
	}

	// Call USPS API function with country and dimensions support
	$api_rate = wtcc_shipping_usps_api_rate( $group, $weight_oz, $origin_zip, $to_postcode, $to_country, $dimensions );
	
	if ( is_numeric( $api_rate ) && $api_rate > 0 ) {
		return $api_rate;
	}

	return false; // API failed, use manual calc
}

/**
 * Get all available groups with human-friendly labels
 * Note: First Class was merged into Ground Advantage by USPS in 2023
 * 
 * @return array Group definitions with UX labels
 */
function wtcc_shipping_get_groups() {
	return array(
		'ground'      => array(
			'label'       => 'Ground Advantage',
			'description' => 'USPS Ground Advantage — 2-5 day economical shipping',
			'ux_label'    => 'Economy',
			'speed'       => 'slow',
		),
		'priority'    => array(
			'label'       => 'Priority Mail',
			'description' => 'USPS Priority — 1-3 day delivery',
			'ux_label'    => 'Standard',
			'speed'       => 'fast',
		),
		'express'     => array(
			'label'       => 'Express Mail',
			'description' => 'USPS Express — overnight to 2-day delivery',
			'ux_label'    => 'Express',
			'speed'       => 'fastest',
		),
	);
}

/**
 * Get calculation breakdown for debug display
 * 
 * @param string $group Shipping group.
 * @return array|null Calculation details
 */
function wtcc_shipping_get_calculation_breakdown( $group ) {
	return get_transient( 'wtcc_shipping_calc_' . $group );
}

/**
 * Check if a shipping rule matches the current package
 *
 * @param array $rule The rule to check.
 * @param array $package The WooCommerce package.
 * @param string $group The current shipping group.
 * @return bool True if the rule matches, false otherwise.
 */
function wtcc_shipping_rule_matches( $rule, $package, $group ) {
	// Group check
	if ( ( $rule['group'] ?? '' ) !== $group ) {
		return false;
	}

	// Weight check
	$total_weight = 0;
	foreach ( $package['contents'] as $item ) {
		$product = $item['data'];
		$quantity = $item['quantity'] ?? 1;
		$item_weight_oz = wtcc_shipping_get_product_weight_oz( $product );
		$total_weight += ( $item_weight_oz * $quantity );
	}

	if ( ! empty( $rule['min_weight'] ) && $total_weight < $rule['min_weight'] ) {
		return false;
	}
	if ( ! empty( $rule['max_weight'] ) && $total_weight > $rule['max_weight'] ) {
		return false;
	}

	// Quantity check
	$total_qty = 0;
	foreach ( $package['contents'] as $item ) {
		$total_qty += $item['quantity'];
	}

	if ( ! empty( $rule['min_qty'] ) && $total_qty < $rule['min_qty'] ) {
		return false;
	}
	if ( ! empty( $rule['max_qty'] ) && $total_qty > $rule['max_qty'] ) {
		return false;
	}

	// Country check
	if ( ! empty( $rule['country'] ) && ( $package['destination']['country'] ?? '' ) !== $rule['country'] ) {
		return false;
	}

	// Category check
	if ( ! empty( $rule['category'] ) ) {
		$category_match = false;
		foreach ( $package['contents'] as $item ) {
			if ( has_term( $rule['category'], 'product_cat', $item['product_id'] ) ) {
				$category_match = true;
				break;
			}
		}
		if ( ! $category_match ) {
			return false;
		}
	}

	return true; // All conditions passed
}
