<?php
/**
 * Plugin Uninstall
 * 
 * Cleans up all database options and metadata when plugin is deleted
 * UNLESS user has chosen to preserve data for testing.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user wants to preserve data (for testing/reinstall)
$preserve_data = get_option( 'wtcc_preserve_data_on_uninstall', 'yes' );

if ( 'yes' === $preserve_data ) {
	// User wants to keep their data - only remove transients/cache
	delete_transient( 'wtc_config_errors' );
	delete_transient( 'wtcc_shipping_history' );
	delete_transient( 'wtcc_usps_oauth_token' );
	
	// Clean up calculation cache transients only
	global $wpdb;
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%wtcc_shipping_calc_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_wtcc_usps_rate_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_wtcc_usps_rate_%'" );
	
	// Exit early - preserve all settings
	return;
}

// User wants full cleanup - remove everything

// Remove main configuration option
delete_option( 'wtcc_shipping_rates_config' );

// Remove method enable/disable settings
delete_option( 'wtc_enabled_methods' );

// Remove test mode setting
delete_option( 'wtc_test_mode' );

// Remove label overrides
delete_option( 'wtc_label_overrides' );

// Remove shipping history
delete_option( 'wtcc_shipping_history' );

// Clean up all product meta (presets)
$product_ids = get_posts( array(
	'post_type'      => 'product',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_key'       => '_wtc_preset',
) );

if ( ! empty( $product_ids ) ) {
	foreach ( $product_ids as $product_id ) {
		delete_post_meta( $product_id, '_wtc_preset' );
	}
}

// Clean up transients
delete_transient( 'wtc_config_errors' );
delete_transient( 'wtcc_shipping_history' );

// Clean up all calculation transients
global $wpdb;
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%wtcc_shipping_calc_%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_wtcc_usps_%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_wtcc_usps_%'" );

// Remove USPS API credentials
delete_option( 'wtcc_usps_consumer_key' );
delete_option( 'wtcc_usps_consumer_secret' );
delete_option( 'wtcc_origin_zip' );
delete_option( 'wtcc_usps_api_mode' );
delete_option( 'wtcc_preserve_data_on_uninstall' );
delete_option( 'wtcc_last_usps_success' );
delete_option( 'wtcc_last_usps_failure' );
