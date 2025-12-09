<?php
/**
 * Additional USPS Mail Classes
 * 
 * Adds support for:
 * - Commercial Plus / Cubic Rates (14 votes on WooCommerce feature requests)
 * - Media Mail (3 votes)
 * - Library Mail
 * 
 * @package WTC_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register additional shipping methods
 */
add_filter( 'woocommerce_shipping_methods', 'wtcc_register_additional_methods' );
function wtcc_register_additional_methods( $methods ) {
	// Only add if enabled in settings
	if ( get_option( 'wtcc_enable_media_mail', 'no' ) === 'yes' ) {
		$methods['wtc_media_mail'] = 'WTC_Shipping_Media_Mail';
	}
	
	if ( get_option( 'wtcc_enable_cubic_pricing', 'no' ) === 'yes' ) {
		$methods['wtc_priority_cubic'] = 'WTC_Shipping_Priority_Cubic';
	}
	
	return $methods;
}

/**
 * Media Mail Shipping Method
 * For books, CDs, DVDs, and educational materials ONLY
 * 
 * Class is declared inside a hook to ensure WTC_Shipping_Method exists
 */
add_action( 'woocommerce_shipping_init', 'wtcc_init_media_mail_class' );
function wtcc_init_media_mail_class() {
	if ( class_exists( 'WTC_Shipping_Method' ) && ! class_exists( 'WTC_Shipping_Media_Mail' ) ) {
		class WTC_Shipping_Media_Mail extends WTC_Shipping_Method {
			public function __construct( $instance_id = 0 ) {
				$this->id                 = 'wtc_media_mail';
				$this->method_title       = __( 'USPS Media Mail', 'wtc-shipping' );
				$this->method_description = __( 'Economical shipping for books, CDs, DVDs, and educational materials only. Cannot contain advertising.', 'wtc-shipping' );
				$this->group              = 'media_mail';
				parent::__construct( $instance_id );
			}
		}
	}
}

/**
 * Priority Mail Cubic Pricing
 * Commercial pricing for small, heavy packages
 * 
 * Class is declared inside a hook to ensure WTC_Shipping_Method exists
 */
add_action( 'woocommerce_shipping_init', 'wtcc_init_priority_cubic_class' );
function wtcc_init_priority_cubic_class() {
	if ( class_exists( 'WTC_Shipping_Method' ) && ! class_exists( 'WTC_Shipping_Priority_Cubic' ) ) {
		class WTC_Shipping_Priority_Cubic extends WTC_Shipping_Method {
			public function __construct( $instance_id = 0 ) {
				$this->id                 = 'wtc_priority_cubic';
				$this->method_title       = __( 'USPS Priority Mail Cubic', 'wtc-shipping' );
				$this->method_description = __( 'Commercial cubic pricing for Priority Mail. Best for small, heavy packages under 0.5 cubic feet.', 'wtc-shipping' );
				$this->group              = 'priority_cubic';
				parent::__construct( $instance_id );
			}
		}
	}
}

/**
 * Add media mail and cubic to service mapping
 */
add_filter( 'wtcc_usps_service_map', 'wtcc_add_additional_service_mapping' );
function wtcc_add_additional_service_mapping( $service_map ) {
	$service_map['media_mail']     = 'MEDIA_MAIL';
	$service_map['library_mail']   = 'LIBRARY_MAIL';
	$service_map['priority_cubic'] = 'PRIORITY_MAIL_CUBIC';
	
	return $service_map;
}

/**
 * Add additional services to rates config
 */
add_filter( 'wtcc_shipping_rates_config', 'wtcc_add_additional_rates_config' );
function wtcc_add_additional_rates_config( $config ) {
	// Media Mail - very cheap but slow
	if ( ! isset( $config['media_mail'] ) ) {
		$config['media_mail'] = array(
			'base_cost'   => 3.65,
			'per_oz'      => 0.08,
			'max_weight'  => 70,
		);
	}
	
	// Priority Cubic - commercial pricing
	if ( ! isset( $config['priority_cubic'] ) ) {
		$config['priority_cubic'] = array(
			'base_cost'   => 8.25,
			'per_oz'      => 0.15,
			'max_weight'  => 20, // Cubic has weight limit
		);
	}
	
	return $config;
}

/**
 * Calculate cubic tier for Priority Mail Cubic
 * Cubic pricing is based on package volume, not weight
 * 
 * @param float $length Length in inches.
 * @param float $width Width in inches.
 * @param float $height Height in inches.
 * @return float|false Cubic feet or false if too large.
 */
function wtcc_calculate_cubic_tier( $length, $width, $height ) {
	$cubic_inches = $length * $width * $height;
	$cubic_feet = $cubic_inches / 1728; // 12^3 = 1728 cubic inches per cubic foot
	
	// Cubic pricing only available for packages under 0.5 cubic feet
	if ( $cubic_feet > 0.5 ) {
		return false;
	}
	
	// Return the cubic tier (0.1 to 0.5)
	if ( $cubic_feet <= 0.1 ) {
		return 0.1;
	} elseif ( $cubic_feet <= 0.2 ) {
		return 0.2;
	} elseif ( $cubic_feet <= 0.3 ) {
		return 0.3;
	} elseif ( $cubic_feet <= 0.4 ) {
		return 0.4;
	} else {
		return 0.5;
	}
}

/**
 * Check if cart is eligible for Media Mail
 * Only books, CDs, DVDs, and educational materials qualify
 * 
 * @return bool|string True if eligible, error message if not.
 */
function wtcc_check_media_mail_eligibility() {
	if ( ! WC()->cart ) {
		return false;
	}
	
	$media_categories = get_option( 'wtcc_media_mail_categories', array( 'books', 'music', 'dvd', 'educational' ) );
	
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = $cart_item['data'];
		
		// Check if product has media mail category
		$product_cats = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
		
		$is_media = false;
		foreach ( $media_categories as $media_cat ) {
			if ( in_array( $media_cat, $product_cats, true ) ) {
				$is_media = true;
				break;
			}
		}
		
		// Also check product meta for media mail eligibility
		if ( ! $is_media ) {
			$eligible = get_post_meta( $product->get_id(), '_wtc_media_mail_eligible', true );
			if ( $eligible === 'yes' ) {
				$is_media = true;
			}
		}
		
		if ( ! $is_media ) {
			return sprintf(
				__( 'Media Mail not available: "%s" is not eligible. Media Mail is only for books, CDs, DVDs, and educational materials.', 'wtc-shipping' ),
				$product->get_name()
			);
		}
	}
	
	return true;
}

/**
 * Filter Media Mail availability based on cart contents
 */
add_filter( 'woocommerce_package_rates', 'wtcc_filter_media_mail_availability', 20, 2 );
function wtcc_filter_media_mail_availability( $rates, $package ) {
	// Check if Media Mail is in the rates
	$media_mail_key = null;
	foreach ( $rates as $key => $rate ) {
		if ( strpos( $key, 'wtc_media_mail' ) !== false ) {
			$media_mail_key = $key;
			break;
		}
	}
	
	if ( $media_mail_key ) {
		$eligibility = wtcc_check_media_mail_eligibility();
		
		if ( $eligibility !== true ) {
			// Remove Media Mail if not eligible
			unset( $rates[ $media_mail_key ] );
		}
	}
	
	return $rates;
}

/**
 * Filter Cubic pricing availability based on package size
 */
add_filter( 'woocommerce_package_rates', 'wtcc_filter_cubic_availability', 20, 2 );
function wtcc_filter_cubic_availability( $rates, $package ) {
	// Check if Cubic is in the rates
	$cubic_key = null;
	foreach ( $rates as $key => $rate ) {
		if ( strpos( $key, 'wtc_priority_cubic' ) !== false ) {
			$cubic_key = $key;
			break;
		}
	}
	
	if ( $cubic_key ) {
		// Calculate package dimensions
		$dimensions = wtcc_shipping_calculate_package_dimensions( $package );
		$cubic_tier = wtcc_calculate_cubic_tier( 
			$dimensions['length'], 
			$dimensions['width'], 
			$dimensions['height'] 
		);
		
		if ( $cubic_tier === false ) {
			// Package too large for cubic pricing
			unset( $rates[ $cubic_key ] );
		}
	}
	
	return $rates;
}

/**
 * Add Media Mail eligibility checkbox to product edit page
 */
add_action( 'woocommerce_product_options_shipping', 'wtcc_add_media_mail_product_field' );
function wtcc_add_media_mail_product_field() {
	echo '<div class="options_group">';
	
	woocommerce_wp_checkbox( array(
		'id'          => '_wtc_media_mail_eligible',
		'label'       => __( 'Media Mail Eligible', 'wtc-shipping' ),
		'description' => __( 'Check if this product qualifies for Media Mail (books, CDs, DVDs, educational materials only).', 'wtc-shipping' ),
	) );
	
	echo '</div>';
}

/**
 * Save Media Mail eligibility
 */
add_action( 'woocommerce_process_product_meta', 'wtcc_save_media_mail_product_field' );
function wtcc_save_media_mail_product_field( $post_id ) {
	// Capability check (WooCommerce already verifies nonce)
	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}
	
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WooCommerce
	$eligible = isset( $_POST['_wtc_media_mail_eligible'] ) ? 'yes' : 'no';
	update_post_meta( $post_id, '_wtc_media_mail_eligible', sanitize_key( $eligible ) );
}

/**
 * Add settings for additional mail classes
 */
add_action( 'admin_init', 'wtcc_register_additional_mail_settings' );
function wtcc_register_additional_mail_settings() {
	register_setting( 'wtcc_usps_settings', 'wtcc_enable_media_mail' );
	register_setting( 'wtcc_usps_settings', 'wtcc_enable_cubic_pricing' );
	register_setting( 'wtcc_usps_settings', 'wtcc_media_mail_categories' );
}

/**
 * Get shipping groups including additional mail classes
 */
add_filter( 'wtcc_shipping_groups', 'wtcc_add_additional_shipping_groups' );
function wtcc_add_additional_shipping_groups( $groups ) {
	if ( get_option( 'wtcc_enable_media_mail', 'no' ) === 'yes' ) {
		$groups['media_mail'] = array(
			'label'       => 'Media Mail',
			'description' => 'USPS Media Mail — for books, CDs, DVDs only',
			'ux_label'    => 'Media Mail',
			'speed'       => 'slowest',
		);
	}
	
	if ( get_option( 'wtcc_enable_cubic_pricing', 'no' ) === 'yes' ) {
		$groups['priority_cubic'] = array(
			'label'       => 'Priority Mail Cubic',
			'description' => 'USPS Priority Cubic — commercial rates for small heavy packages',
			'ux_label'    => 'Priority Cubic',
			'speed'       => 'fast',
		);
	}
	
	return $groups;
}
