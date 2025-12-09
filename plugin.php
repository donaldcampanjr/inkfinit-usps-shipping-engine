<?php
/**
 * Plugin Name: Inkfinit USPS Shipping Engine
 * Plugin URI: https://github.com/donaldcampanjr/wtc-shipping-core-design
 * Description: Professional real-time USPS shipping rates, presets, and delivery estimates.
 * Version: 1.3.2
 * Author: Inkfinit LLC
 * Author URI: https://inkfinit.pro
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0-or-later.html
 * Text Domain: wtc-shipping
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 *
 * @package Inkfinit_Shipping_Engine
 */

// Last Updated: 2025-12-08 19:30:00


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Suppress PHP 8.1+ deprecation warnings from WordPress core and other plugins
// This is a workaround for third-party code passing null to strpos/str_replace
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
	// Only suppress deprecation warnings in wp-includes/functions.php
	if ( $errno === E_DEPRECATED && strpos( $errfile, 'wp-includes/functions.php' ) !== false ) {
		// Log to debug.log if WP_DEBUG_LOG is enabled
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf(
				'[WTC Shipping suppressed] %s in %s on line %d',
				$errstr,
				$errfile,
				$errline
			) );
		}
		return true; // Suppress the warning
	}
	return false; // Let PHP handle all other errors normally
}, E_DEPRECATED );

// Verify WordPress environment is properly configured before loading
add_action( 'admin_notices', function() {
	$site_url = get_option( 'siteurl' );
	$home_url = get_option( 'home' );
	
	if ( empty( $site_url ) || empty( $home_url ) ) {
		echo '<div class="notice notice-error"><p><strong>Inkfinit Shipping:</strong> WordPress site URL or home URL is not configured. Please check Settings → General or wp-config.php.</p></div>';
	}
} );

define( 'WTCC_SHIPPING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WTCC_SHIPPING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WTCC_SHIPPING_VERSION', '1.3.2' );

// License server URL - can be overridden in wp-config.php.
if ( ! defined( 'WTCC_LICENSE_SERVER_URL' ) ) {
	// Default to inkfinit.pro license API.
	define( 'WTCC_LICENSE_SERVER_URL', 'https://inkfinit.pro/wp-json' );
}

// Edition constants for easy checks across the codebase.
if ( ! defined( 'WTCC_EDITION_FREE' ) ) {
	define( 'WTCC_EDITION_FREE', 'free' );
}
if ( ! defined( 'WTCC_EDITION_PRO' ) ) {
	define( 'WTCC_EDITION_PRO', 'pro' );
}

// Load licensing helpers early, then core functions.
require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/license.php';

// Load core functions first to ensure they are available globally
require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/core-functions.php';

// Load presets and other non-WooCommerce files immediately
require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/presets.php';
require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/country-zones.php';
require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/box-packing.php';
require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/rule-engine.php';

// Security hardening (load early)
require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/security-hardening.php';

/**
 * Load WooCommerce-dependent files after init to prevent early textdomain loading.
 * WordPress 6.7+ requires translations to be loaded at init or later.
 */
add_action( 'init', 'wtcc_load_woocommerce_dependent_files', 1 );
function wtcc_load_woocommerce_dependent_files() {
	// Skip if already loaded
	static $loaded = false;
	if ( $loaded ) {
		return;
	}
	$loaded = true;

	// WooCommerce-dependent files (order matters!)
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/product-scan.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/usps-api.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/usps-enhanced-features.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/shipping-methods.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/label-printing.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/usps-label-api.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/label-printer-settings.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/usps-pickup-api.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/address-validation.php';

	// Customer tracking display (frontend + AJAX)
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/customer-tracking-display.php';

	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/delivery-estimates.php';

	// Additional mail classes (Media Mail, Cubic, Library Mail)
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/additional-mail-classes.php';

	// Default shipping method selection
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/default-shipping-method.php';

	// Order auto-complete on label print
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/order-auto-complete.php';

	// USPS Flat Rate boxes (frontend + admin)
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/flat-rate-boxes.php';

	// Product purchase limits (frontend + admin)
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/product-purchase-limits.php';

	// Frontend status badges (checkout page)
	if ( ! is_admin() ) {
		require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/usps-status-badges.php';
	}
}

// Admin-only files - also load after init
add_action( 'init', 'wtcc_load_admin_files', 1 );
function wtcc_load_admin_files() {
	if ( ! is_admin() ) {
		return;
	}
	
	// Skip if already loaded
	static $loaded = false;
	if ( $loaded ) {
		return;
	}
	$loaded = true;

	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-ui-helpers.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-features.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/usps-status-badges.php'; // Load early - needed by diagnostics
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-page-presets.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-page-presets-editor.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-page-features.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-page-user-guide.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-page-rates.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-page-boxes.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-usps-api.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-page-license.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-diagnostics.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/product-preset-picker.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/preset-product-sync.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/product-dimension-recommender.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/debug-overlay.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-security-dashboard.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/product-dimension-alerts.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/bulk-variation-manager.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-pickup-scheduling.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-split-shipments.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/packing-slips.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-page-flat-rate.php';
	// New SaaS enhancement files (v1.3.0).
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-notices.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/debug-export.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/changelog-display.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/bulk-license-import.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/white-label.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/self-test.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/quick-links.php';
	require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/simple-calculator.php';
	// require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/admin-presets-wc-integration.php';
}

/**
 * Create shipping classes for presets (optional, for admin UI organization)
 */
if ( ! function_exists( 'wtcc_shipping_ensure_classes' ) ) {
function wtcc_shipping_ensure_classes() {
	if ( ! taxonomy_exists( 'product_shipping_class' ) ) {
		return;
	}

	$presets = wtcc_shipping_get_presets();
	foreach ( $presets as $preset_key => $preset ) {
		$slug = $preset['class'] ?? $preset_key;
		$name = $preset['class_label'] ?? ucfirst( $preset_key );
		
		if ( ! term_exists( $slug, 'product_shipping_class' ) ) {
			wp_insert_term( $name, 'product_shipping_class', array( 'slug' => $slug ) );
		}
	}
}
}

/**
 * Plugin activation - initialize default config
 * Zone methods are added by woocommerce_shipping_init hook
 */
if ( ! function_exists( 'wtcc_shipping_activate' ) ) {
function wtcc_shipping_activate() {
	// Set default rates if not configured
	if ( ! get_option( 'wtcc_shipping_rates_config' ) ) {
		update_option( 'wtcc_shipping_rates_config', array(
			'ground'      => array( 'base_cost' => 8.50, 'per_oz' => 0.18, 'max_weight' => 70 ),
			'priority'    => array( 'base_cost' => 10.50, 'per_oz' => 0.22, 'max_weight' => 70 ),
			'express'     => array( 'base_cost' => 26.99, 'per_oz' => 0.35, 'max_weight' => 70 ),
			'zone_multipliers' => array(
				'usa' => 1.0, 'canada' => 1.5, 'uk' => 2.0, 'eu1' => 2.2, 'eu2' => 2.5,
				'apac' => 3.0, 'asia' => 3.2, 'south-america' => 2.8, 'middle-east' => 3.0,
				'africa' => 3.5, 'rest-of-world' => 3.0
			),
		) );
	}

	// Auto-create shipping zones with methods
	if ( class_exists( 'WC_Shipping_Zones' ) && class_exists( 'WC_Shipping_Zone' ) ) {
		$zones = WC_Shipping_Zones::get_zones();
		$our_methods = array( 'wtc_ground', 'wtc_priority', 'wtc_express' );
		
		// Check if USA zone exists
		$usa_zone_exists = false;
	foreach ( $zones as $zone_data ) {
		$zone = new WC_Shipping_Zone( $zone_data['zone_id'] ?? $zone_data['id'] );
		$zone_name = $zone->get_zone_name() ? strtolower( (string) $zone->get_zone_name() ) : '';
		if ( $zone_name && ( strpos( $zone_name, 'usa' ) !== false || strpos( $zone_name, 'united states' ) !== false ) ) {
				$usa_zone_exists = true;
				break;
			}
		}
		
		// Create USA zone if doesn't exist
		if ( ! $usa_zone_exists ) {
			$usa_zone = new WC_Shipping_Zone( 0 );
			$usa_zone->set_zone_name( 'USA' );
			$usa_zone->set_zone_order( 1 );
			$usa_zone->add_location( 'US', 'country' );
			$usa_zone->save();
			
			// Add all WTC methods to USA zone (only once)
			$usa_zone_id = $usa_zone->get_id();
			$usa_zone = new WC_Shipping_Zone( $usa_zone_id );
			$existing_methods = $usa_zone->get_shipping_methods( false );
			$existing_ids = array_map( function( $m ) { return $m->id; }, $existing_methods );
			
			foreach ( $our_methods as $method_id ) {
				if ( ! in_array( $method_id, $existing_ids, true ) ) {
					$usa_zone->add_shipping_method( $method_id );
				}
			}
		}
		
		// Add methods to "Rest of World" zone (zone 0)
		$rest_zone = new WC_Shipping_Zone( 0 );
		$existing_methods = $rest_zone->get_shipping_methods( false );
		$existing_ids = array_map( function( $m ) { return $m->id; }, $existing_methods );
		
		foreach ( $our_methods as $method_id ) {
			if ( ! in_array( $method_id, $existing_ids, true ) ) {
				$rest_zone->add_shipping_method( $method_id );
			}
		}
	}

	// Force zone method setup on next page load
	delete_option( 'wtcc_shipping_needs_zone_setup' );
	update_option( 'wtcc_shipping_needs_zone_setup', time() );
	flush_rewrite_rules();
}
}

/**
 * Add WTC shipping methods to ALL zones automatically
 */
if ( ! function_exists( 'wtcc_shipping_ensure_zone_methods' ) ) {
function wtcc_shipping_ensure_zone_methods() {
	// Only run once per request
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	// Ensure WooCommerce classes are loaded
	if ( ! class_exists( 'WC_Shipping_Zones' ) || ! class_exists( 'WC_Shipping_Zone' ) ) {
		return;
	}

	$our_methods = array( 'wtc_ground', 'wtc_priority', 'wtc_express' );
	
	try {
		// CORRECT: WC_Shipping_Zones is the class, get_zones() is the static method
	$all_zones = WC_Shipping_Zones::get_zones();
		$all_zones[] = array( 'id' => 0 ); // Add "Rest of World" zone

		foreach ( $all_zones as $zone_data ) {
			// Handle both 'id' and 'zone_id' keys
			$zone_id = isset( $zone_data['id'] ) ? (int) $zone_data['id'] : 0;
			if ( $zone_id === 0 && isset( $zone_data['zone_id'] ) ) {
				$zone_id = (int) $zone_data['zone_id'];
			}
			
			$zone = new WC_Shipping_Zone( $zone_id );
			
			// Get existing shipping methods
			$existing_methods = $zone->get_shipping_methods( false );
			$existing_ids = array();
			
			foreach ( $existing_methods as $method ) {
				if ( is_object( $method ) && ! empty( $method->id ) ) {
					$existing_ids[] = $method->id;
				}
			}

			// Add our methods if they don't exist
			foreach ( $our_methods as $method_id ) {
				if ( ! in_array( $method_id, $existing_ids, true ) ) {
					$zone->add_shipping_method( $method_id );
				}
			}
		}

		// Clear the flag if it was set
		delete_option( 'wtcc_shipping_needs_zone_setup' );
	} catch ( Exception $e ) {
		// Log error but don't break the site
		error_log( 'WTC Shipping: Zone setup error - ' . $e->getMessage() );
	}
}
}

/**
 * Plugin deactivation
 */
function wtcc_shipping_deactivate() {
	flush_rewrite_rules();
}

/**
 * Admin menu with strict license gating
 * 
 * FREE EDITION: Only shows Dashboard, Calculator, License, User Guide
 * PRO/PREMIUM/ENTERPRISE: All features unlocked
 */
function wtcc_shipping_admin_menu() {
	// Custom SVG icon for menu - shipping box icon
	$icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M10 1L2 4.5v11L10 19l8-3.5v-11L10 1zm0 2.2l5.5 2.3L10 7.8 4.5 5.5 10 3.2zm-6 3.9l5 2.1v7.3l-5-2.2V7.1zm7 9.4V9.2l5-2.1v7.2l-5 2.2z"/></svg>');
	
	// Check license status - strict gating
	$is_licensed = function_exists( 'wtcc_is_pro' ) && wtcc_is_pro();
	$is_premium  = function_exists( 'wtcc_is_premium' ) && wtcc_is_premium();
	
	// Main menu
	add_menu_page(
		__( 'Inkfinit Shipping', 'wtc-shipping' ),
		__( 'Inkfinit Shipping', 'wtc-shipping' ),
		'manage_woocommerce',
		'wtc-core-shipping',
		'wtcc_shipping_dashboard',
		$icon_svg,
		58
	);
	
	// ===== FREE TIER PAGES (Always visible) =====
	
	// Dashboard (duplicate of main to rename first submenu)
	add_submenu_page(
		'wtc-core-shipping',
		__( 'Dashboard', 'wtc-shipping' ),
		__( 'Dashboard', 'wtc-shipping' ),
		'manage_woocommerce',
		'wtc-core-shipping',
		'wtcc_shipping_dashboard'
	);
	
	// Simple Calculator (FREE - available to all)
	add_submenu_page(
		'wtc-core-shipping',
		__( 'Rate Calculator', 'wtc-shipping' ),
		__( '📦 Rate Calculator', 'wtc-shipping' ),
		'manage_woocommerce',
		'wtc-simple-calculator',
		'wtcc_render_simple_calculator_page'
	);
	
	// License Settings (FREE - always visible for activation)
	add_submenu_page(
		'wtc-core-shipping',
		__( 'License Settings', 'wtc-shipping' ),
		__( '🔑 License', 'wtc-shipping' ),
		'manage_woocommerce',
		'wtc-core-shipping-license',
		'wtcc_render_license_settings_page'
	);
	
	// User Guide (FREE - helps users understand the plugin)
	add_submenu_page(
		'wtc-core-shipping',
		__( 'User Guide', 'wtc-shipping' ),
		__( '📖 User Guide', 'wtc-shipping' ),
		'manage_woocommerce',
		'wtc-user-guide',
		'wtcc_render_user_guide_page'
	);
	
	// ===== PRO TIER PAGES (Requires valid license) =====
	if ( $is_licensed ) {
		
		// Features page
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Features', 'wtc-shipping' ),
			__( 'Features', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-features',
			'wtcc_shipping_features_page'
		);
		
		// USPS API Settings (PRO+ only - requires license to enter credentials)
		add_submenu_page(
			'wtc-core-shipping',
			__( 'USPS API Settings', 'wtc-shipping' ),
			__( '🔌 USPS API', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-core-shipping-usps-api',
			'wtcc_shipping_usps_api_page'
		);
		
		// Setup & Configuration (rates, presets)
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Setup & Configuration', 'wtc-shipping' ),
			__( 'Setup & Config', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-core-shipping-presets',
			'wtcc_shipping_presets_page'
		);
		
		// Preset Editor
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Preset Editor', 'wtc-shipping' ),
			__( 'Preset Editor', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-core-shipping-presets-editor',
			'wtcc_render_preset_editor_page'
		);
		
		// Box Inventory
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Box Inventory', 'wtc-shipping' ),
			__( 'Box Inventory', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-shipping-boxes',
			'wtcc_shipping_render_box_inventory_page'
		);
		
		// USPS Flat Rate Boxes
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Flat Rate Boxes', 'wtc-shipping' ),
			__( 'Flat Rate Boxes', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-flat-rate',
			'wtcc_render_flat_rate_settings_page'
		);
		
		// Packing Slips
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Packing Slip Settings', 'wtc-shipping' ),
			__( 'Packing Slips', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-core-shipping-packing-slip-settings',
			'wtcc_render_packing_slip_settings_page'
		);
		
		// Variation Manager (for bulk variation edits)
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Bulk Variation Manager', 'wtc-shipping' ),
			__( 'Variation Manager', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-variation-manager',
			'wtcc_render_variation_manager_page'
		);
		
		// Shipping Rules (table rates)
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Shipping Rules', 'wtc-shipping' ),
			__( 'Shipping Rules', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-shipping-rules',
			'wtcc_shipping_rates_page'
		);
		
		// Diagnostics
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Diagnostics', 'wtc-shipping' ),
			__( 'Diagnostics', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-core-shipping-diagnostics',
			'wtcc_shipping_diagnostics_page'
		);
		
		// Changelog
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Changelog', 'wtc-shipping' ),
			__( 'Changelog', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-changelog',
			'wtcc_render_changelog_page'
		);
	}
	
	// ===== PREMIUM/ENTERPRISE TIER PAGES =====
	if ( $is_premium ) {
		
		// White-Label Settings
		add_submenu_page(
			'wtc-core-shipping',
			__( 'White-Label Settings', 'wtc-shipping' ),
			__( '🏷️ White-Label', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-white-label',
			'wtcc_render_white_label_page'
		);
		
		// Bulk License Import
		add_submenu_page(
			'wtc-core-shipping',
			__( 'Bulk License Import', 'wtc-shipping' ),
			__( '📥 Bulk Import', 'wtc-shipping' ),
			'manage_woocommerce',
			'wtc-bulk-import',
			'wtcc_render_bulk_import_page'
		);
	}
}

/**
 * Admin styles and scripts
 */
function wtcc_shipping_admin_styles( $hook ) {
	// Load on all WTC Shipping pages
	$hook = $hook ? (string) $hook : '';
	if ( $hook && ( strpos( $hook, 'wtc-') !== false || strpos( $hook, 'shop_order' ) !== false || strpos( $hook, 'woocommerce_page_wc-orders' ) !== false ) ) {
		
		// Load general admin styles
		wp_enqueue_style( 'wtc-shipping-admin-style', WTCC_SHIPPING_PLUGIN_URL . 'assets/admin-style.css', array(), WTCC_SHIPPING_VERSION );

		// Load admin JavaScript
		wp_enqueue_script( 'wtc-shipping-admin', WTCC_SHIPPING_PLUGIN_URL . 'assets/admin.js', array( 'jquery' ), WTCC_SHIPPING_VERSION, true );
	}

	// Specific styles for preset editor
	if ( $hook && strpos( $hook, 'wtc-core-shipping-presets-editor' ) !== false ) {
		wp_enqueue_style( 'wtc-shipping-presets-editor', WTCC_SHIPPING_PLUGIN_URL . 'assets/admin-presets-editor.css', array(), WTCC_SHIPPING_VERSION );
	}

	// Specific scripts for bulk variation manager (CSS removed - doesn't exist)
	if ( $hook && strpos( $hook, 'wtc-variation-manager' ) !== false ) {
		wp_enqueue_script( 'wtc-shipping-bulk-variation-manager', WTCC_SHIPPING_PLUGIN_URL . 'assets/admin-bulk-variation-manager.js', array( 'jquery' ), WTCC_SHIPPING_VERSION, true );

		wp_localize_script( 'wtc-shipping-bulk-variation-manager', 'wtcc_bulk_manager', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wtcc_variation_manager' ),
			'i18n' => array(
				'loading' => esc_html__( 'Loading...', 'wtc-shipping' ),
				'select_attribute_first' => esc_html__( 'Select attribute first', 'wtc-shipping' ),
				'select_value' => esc_html__( 'Select Value', 'wtc-shipping' ),
				'no_values_found' => esc_html__( 'No values found', 'wtc-shipping' ),
				'variations_found' => esc_html__( 'variations found.', 'wtc-shipping' ),
				'showing_preview' => esc_html__( 'Showing a preview of up to 50.', 'wtc-shipping' ),
				'no_variations_to_preview' => esc_html__( 'No variations to preview.', 'wtc-shipping' ),
				'confirm_apply' => esc_html__( 'Are you sure you want to apply this bulk update? This action cannot be undone.', 'wtc-shipping' ),
				'update_complete' => esc_html__( 'Update Complete!', 'wtc-shipping' ),
				'variations_updated' => esc_html__( 'variations updated.', 'wtc-shipping' ),
				'failed' => esc_html__( 'failed.', 'wtc-shipping' ),
				'error' => esc_html__( 'Error:', 'wtc-shipping' ),
			)
		) );
	}

	// Specific styles for packing slips
	if ( $hook && strpos( $hook, 'wtc-core-shipping-packing-slips' ) !== false ) {
		wp_enqueue_style( 'wtc-shipping-packing-slips', WTCC_SHIPPING_PLUGIN_URL . 'assets/packing-slips.css', array(), WTCC_SHIPPING_VERSION );
	}
}

/**
 * Determine whether the current admin request belongs to this plugin.
 *
 * @return bool
 */
function wtcc_shipping_is_core_admin_page() {
	if ( ! is_admin() || empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}
	$page = sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return strpos( $page, 'wtc' ) === 0;
}

/**
 * Load translations
 */
function wtcc_shipping_load_textdomain() {
	load_plugin_textdomain( 'wtc-core-shipping', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Admin notice to refresh shipping methods if they're not showing
 */
function wtcc_shipping_check_methods() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Only show on shipping settings page
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
	
	if ( 'wc-settings' !== $page || 'shipping' !== $tab ) {
		return;
	}

	// Check if our methods are registered
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		try {
			$zones = WC_Shipping_Zones::get_zones();
			$zones[] = array( 'id' => 0 );
			$found = false;

			foreach ( $zones as $zone_data ) {
				$zone_id = isset( $zone_data['id'] ) ? (int) $zone_data['id'] : 0;
				$zone = new WC_Shipping_Zone( $zone_id );
				$methods = $zone->get_shipping_methods( false );
				foreach ( $methods as $method ) {
					$method_id = isset( $method->id ) ? (string) $method->id : '';
					if ( $method_id && strpos( $method_id, 'wtc_' ) === 0 ) {
						$found = true;
						break 2;
					}
				}
			}

			if ( ! $found ) {
				$refresh_url = admin_url( 'admin.php?page=wtc-core-shipping-diagnostics&action=refresh_methods' );
				if ( $refresh_url ) {
					$nonce_url = wp_nonce_url( $refresh_url, 'wtc_refresh_methods' );
					if ( $nonce_url ) {
						echo '<div class="notice notice-warning"><p><strong>Inkfinit Shipping:</strong> Your shipping methods are not yet in the zones. <a href="' . esc_url( $nonce_url ) . '" class="button">Refresh Shipping Methods</a></p></div>';
					}
				}
			}
		} catch ( Exception $e ) {
			// Silent fail
		}
	}
}

/**
 * Admin notice if WooCommerce is missing
 */
function wtcc_shipping_admin_notice() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-error"><p>Inkfinit Shipping requires WooCommerce to be installed and active.</p></div>';
	}
}

/**
 * Handle manual refresh of shipping methods
 */
function wtcc_shipping_handle_refresh_methods() {
	// Only process if we have the action parameter
	if ( ! isset( $_GET['action'] ) || 'refresh_methods' !== $_GET['action'] ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Verify nonce
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wtc_refresh_methods' ) ) {
		return;
	}

	// Force re-run the zone setup
	wtcc_shipping_ensure_zone_methods();

	// Clear transients
	wp_safe_remote_post( admin_url( 'admin-ajax.php?action=woocommerce_clear_transients' ), array( 'blocking' => false ) );

	// Redirect back to shipping settings
	wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) );
	exit;
}
add_action( 'admin_init', 'wtcc_shipping_handle_refresh_methods', 5 );

/**
 * Load frontend scripts and styles
 */
add_action( 'wp_enqueue_scripts', 'wtcc_shipping_frontend_scripts' );
function wtcc_shipping_frontend_scripts() {
	// Frontend stylesheet for all pages
	wp_enqueue_style(
		'wtcc-frontend-style',
		WTCC_SHIPPING_PLUGIN_URL . 'assets/frontend-style.css',
		array(),
		WTCC_SHIPPING_VERSION
	);

	// Customer tracking display styles (only on order details page)
	if ( is_wc_endpoint_url( 'view-order' ) ) {
		wp_enqueue_style(
			'wtcc-frontend-tracking',
			WTCC_SHIPPING_PLUGIN_URL . 'assets/frontend-tracking.css',
			array(),
			WTCC_SHIPPING_VERSION
		);
	}

	// Debug overlay styles (only for admins on frontend)
	if ( current_user_can( 'manage_options' ) && ! is_admin() ) {
		wp_enqueue_style(
			'wtcc-frontend-debug',
			WTCC_SHIPPING_PLUGIN_URL . 'assets/frontend-debug.css',
			array(),
			WTCC_SHIPPING_VERSION
		);
	}

	// Delivery estimate styles on cart/checkout
	if ( is_cart() || is_checkout() ) {
		wp_enqueue_style(
			'wtcc-frontend-delivery-estimates',
			WTCC_SHIPPING_PLUGIN_URL . 'assets/frontend-delivery-estimates.css',
			array(),
			WTCC_SHIPPING_VERSION
		);
	}
}

/**
 * Add footer to all WTC Shipping plugin pages
 */
function wtcc_shipping_admin_footer() {
	$screen = get_current_screen();
	
	// Check if this is a WTC Shipping page
	$wtc_pages = array(
		'toplevel_page_wtc-core-shipping',
		'shipping-engine_page_wtc-features',
		'shipping-engine_page_wtc-user-guide',
		'shipping-engine_page_wtc-core-shipping-presets',
		'shipping-engine_page_wtc-core-shipping-packing-slip-settings',
		'shipping-engine_page_wtc-pickup-scheduling',
		'shipping-engine_page_wtc-security-dashboard',
		'shipping-engine_page_wtc-variation-manager',
		'shipping-engine_page_wtc-shipping-rules',
		'shipping-engine_page_wtc-core-shipping-diagnostics',
		'shipping-engine_page_wtc-core-shipping-usps-api',
		'shipping-engine_page_wtc-shipping-boxes',
		'shipping-engine_page_wtc-flat-rate',
		'shipping-engine_page_wtc-core-shipping-presets-editor',
	);
	
	if ( ! $screen || ! in_array( $screen->id, $wtc_pages, true ) ) {
		return;
	}
	
	echo '<div class="wtc-admin-footer" style="margin-top:60px; padding:40px 20px; border-top:2px solid #e5e5e5; background:#f9f9f9; border-radius:4px; text-align: center;">';
	echo '<p style="color:#646970; font-size:15px; margin:0 0 10px 0; line-height: 1.6; font-weight:500;">';
	echo '<strong>Inkfinit LLC</strong> &copy; ' . esc_html( date( 'Y' ) );
	echo '</p>';
	echo '<p style="color:#666; font-size:13px; margin:0 0 8px 0; line-height: 1.5;">';
	echo 'Inkfinit Shipping v' . esc_html( WTCC_SHIPPING_VERSION ) . ' | Professional USPS Shipping for WooCommerce';
	echo '</p>';
	echo '<p style="color:#999; font-size:12px; margin:12px 0 0 0; border-top:1px solid #ddd; padding-top:12px;">';
	echo 'Built with care by <strong>Inkfinit LLC</strong>';
	echo '</p>';
	echo '</div>';
}
add_action( 'admin_footer', 'wtcc_shipping_admin_footer' );

// Register hooks
register_activation_hook( __FILE__, 'wtcc_shipping_activate' );
register_deactivation_hook( __FILE__, 'wtcc_shipping_deactivate' );

add_action( 'woocommerce_init', 'wtcc_shipping_ensure_classes', 20 );
// Ensure zone methods are added AFTER methods are registered (after woocommerce_shipping_methods filter)
add_action( 'woocommerce_shipping_init', 'wtcc_shipping_ensure_zone_methods', 100 );
// Also hook on admin_init to ensure zones get methods when saving in WC settings
add_action( 'admin_init', 'wtcc_shipping_ensure_zone_methods', 100 );
add_action( 'admin_menu', 'wtcc_shipping_admin_menu' );
add_action( 'admin_enqueue_scripts', 'wtcc_shipping_admin_styles' );
add_action( 'init', 'wtcc_shipping_load_textdomain' );
add_action( 'admin_notices', 'wtcc_shipping_check_methods', 15 );
add_action( 'admin_notices', 'wtcc_shipping_admin_notice' );
