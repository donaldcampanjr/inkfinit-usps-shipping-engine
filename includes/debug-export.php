<?php
/**
 * Debug Info Export for Inkfinit Shipping Engine.
 *
 * One-click export of system info, settings, and error logs for support.
 * Uses WordPress Site Health standards and accessible UI.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register AJAX handler for debug export.
 */
add_action( 'wp_ajax_wtcc_export_debug_info', 'wtcc_ajax_export_debug_info' );

/**
 * AJAX handler to generate debug info export.
 */
function wtcc_ajax_export_debug_info() {
	check_ajax_referer( 'wtcc_debug_export_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Permission denied' );
	}

	$debug_info = wtcc_gather_debug_info();

	// Return as downloadable text.
	wp_send_json_success( array(
		'content'  => $debug_info,
		'filename' => 'inkfinit-shipping-debug-' . gmdate( 'Y-m-d-His' ) . '.txt',
	) );
}

/**
 * Gather all debug information.
 *
 * @return string Formatted debug info text.
 */
function wtcc_gather_debug_info() {
	$output = array();

	// Header.
	$output[] = '=== INKFINIT USPS SHIPPING ENGINE DEBUG REPORT ===';
	$output[] = 'Generated: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC';
	$output[] = 'Site URL: ' . home_url();
	$output[] = '';

	// Plugin Info.
	$output[] = '=== PLUGIN INFO ===';
	$output[] = 'Plugin Version: ' . ( defined( 'WTCC_SHIPPING_VERSION' ) ? WTCC_SHIPPING_VERSION : 'Unknown' );
	$output[] = 'Edition: ' . wtcc_get_edition();
	$output[] = 'License Status: ' . wtcc_get_license_status();
	$expiry = wtcc_get_license_expiry_info();
	if ( $expiry ) {
		$output[] = 'License Expires: ' . $expiry['expires_at'] . ' (' . $expiry['days_left'] . ' days left)';
	}
	$output[] = '';

	// Environment.
	$output[] = '=== ENVIRONMENT ===';
	$output[] = 'WordPress Version: ' . get_bloginfo( 'version' );
	$output[] = 'WooCommerce Version: ' . ( defined( 'WC_VERSION' ) ? WC_VERSION : 'Not Active' );
	$output[] = 'PHP Version: ' . phpversion();
	$output[] = 'MySQL Version: ' . ( function_exists( 'mysqli_get_client_version' ) ? mysqli_get_client_version() : 'Unknown' );
	$output[] = 'Server Software: ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown' );
	$output[] = 'Memory Limit: ' . WP_MEMORY_LIMIT;
	$output[] = 'Max Execution Time: ' . ini_get( 'max_execution_time' ) . 's';
	$output[] = 'cURL Enabled: ' . ( function_exists( 'curl_version' ) ? 'Yes' : 'No' );
	$output[] = 'SSL Enabled: ' . ( is_ssl() ? 'Yes' : 'No' );
	$output[] = 'Debug Mode: ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled' );
	$output[] = '';

	// USPS API Settings (sanitized).
	$output[] = '=== USPS API CONFIGURATION ===';
	$consumer_key = get_option( 'wtcc_usps_consumer_key', '' );
	$output[] = 'Consumer Key: ' . ( ! empty( $consumer_key ) ? '****' . substr( $consumer_key, -4 ) : 'Not Set' );
	$output[] = 'Consumer Secret: ' . ( ! empty( get_option( 'wtcc_usps_consumer_secret', '' ) ) ? 'Set (hidden)' : 'Not Set' );
	$output[] = 'Origin ZIP: ' . get_option( 'wtcc_origin_zip', 'Not Set' );
	$output[] = 'Origin Address: ' . get_option( 'wtcc_origin_address', 'Not Set' );
	$output[] = 'Origin City: ' . get_option( 'wtcc_origin_city', 'Not Set' );
	$output[] = 'Origin State: ' . get_option( 'wtcc_origin_state', 'Not Set' );
	$output[] = 'API Mode: ' . get_option( 'wtcc_usps_api_mode', 'production' );
	$output[] = '';

	// OAuth Status.
	$output[] = '=== OAUTH STATUS ===';
	$oauth_token = get_transient( 'wtcc_usps_oauth_token' );
	$output[] = 'OAuth Token Cached: ' . ( $oauth_token ? 'Yes' : 'No' );
	if ( $oauth_token ) {
		$expires = get_option( '_transient_timeout_wtcc_usps_oauth_token', 0 );
		if ( $expires ) {
			$output[] = 'Token Expires In: ' . human_time_diff( time(), $expires );
		}
	}
	$output[] = '';

	// Shipping Settings.
	$output[] = '=== SHIPPING SETTINGS ===';
	$output[] = 'Default Method: ' . get_option( 'wtcc_default_shipping_method', 'None' );
	$output[] = 'Enabled Methods: ' . implode( ', ', wtcc_get_enabled_shipping_methods() );
	$output[] = '';

	// Recent Errors from debug.log.
	$output[] = '=== RECENT ERRORS (Last 50 lines) ===';
	$errors = wtcc_get_recent_errors();
	if ( ! empty( $errors ) ) {
		$output = array_merge( $output, $errors );
	} else {
		$output[] = 'No recent errors found in debug.log';
	}
	$output[] = '';

	// Active Plugins.
	$output[] = '=== ACTIVE PLUGINS ===';
	$plugins = get_option( 'active_plugins', array() );
	foreach ( $plugins as $plugin ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
		$output[] = '- ' . $plugin_data['Name'] . ' v' . $plugin_data['Version'];
	}
	$output[] = '';

	// Theme Info.
	$output[] = '=== THEME INFO ===';
	$theme = wp_get_theme();
	$output[] = 'Active Theme: ' . $theme->get( 'Name' ) . ' v' . $theme->get( 'Version' );
	$output[] = 'Theme Author: ' . $theme->get( 'Author' );
	if ( is_child_theme() ) {
		$parent = wp_get_theme( $theme->get( 'Template' ) );
		$output[] = 'Parent Theme: ' . $parent->get( 'Name' ) . ' v' . $parent->get( 'Version' );
	}
	$output[] = '';

	// WooCommerce Settings.
	$output[] = '=== WOOCOMMERCE SETTINGS ===';
	$output[] = 'Currency: ' . get_woocommerce_currency();
	$output[] = 'Weight Unit: ' . get_option( 'woocommerce_weight_unit', 'oz' );
	$output[] = 'Dimension Unit: ' . get_option( 'woocommerce_dimension_unit', 'in' );
	$output[] = 'Store Country: ' . WC()->countries->get_base_country();
	$output[] = 'Store State: ' . WC()->countries->get_base_state();
	$output[] = 'Store Postcode: ' . WC()->countries->get_base_postcode();
	$output[] = '';

	$output[] = '=== END OF DEBUG REPORT ===';

	return implode( "\n", $output );
}

/**
 * Get enabled shipping methods.
 *
 * @return array List of enabled method IDs.
 */
function wtcc_get_enabled_shipping_methods() {
	$methods = array();

	if ( get_option( 'wtcc_enable_first_class', '1' ) === '1' ) {
		$methods[] = 'First Class';
	}
	if ( get_option( 'wtcc_enable_ground', '1' ) === '1' ) {
		$methods[] = 'Ground Advantage';
	}
	if ( get_option( 'wtcc_enable_priority', '1' ) === '1' ) {
		$methods[] = 'Priority Mail';
	}
	if ( get_option( 'wtcc_enable_express', '1' ) === '1' ) {
		$methods[] = 'Priority Express';
	}
	if ( get_option( 'wtcc_enable_media_mail', '0' ) === '1' ) {
		$methods[] = 'Media Mail';
	}

	return ! empty( $methods ) ? $methods : array( 'None configured' );
}

/**
 * Get recent errors from debug.log.
 *
 * @return array Array of recent error lines.
 */
function wtcc_get_recent_errors() {
	$errors = array();

	// Check debug.log.
	$debug_log = WP_CONTENT_DIR . '/debug.log';
	if ( file_exists( $debug_log ) && is_readable( $debug_log ) ) {
		$lines = array();
		$file  = new SplFileObject( $debug_log );
		$file->seek( PHP_INT_MAX );
		$total_lines = $file->key();

		// Get last 50 lines.
		$start = max( 0, $total_lines - 50 );
		$file->seek( $start );

		while ( ! $file->eof() ) {
			$line = $file->fgets();
			// Only include Inkfinit-related or PHP errors.
			if ( stripos( $line, 'inkfinit' ) !== false || 
			     stripos( $line, 'wtc' ) !== false ||
			     stripos( $line, 'fatal' ) !== false ||
			     stripos( $line, 'error' ) !== false ) {
				$lines[] = trim( $line );
			}
		}

		$errors = array_slice( $lines, -50 );
	}

	return $errors;
}

/**
 * Render debug export button (for use in diagnostics page).
 */
function wtcc_render_debug_export_button() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$nonce = wp_create_nonce( 'wtcc_debug_export_nonce' );
	?>
	<div class="wtcc-debug-export">
		<button type="button" id="wtcc-export-debug" class="button button-secondary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<span class="dashicons dashicons-download" aria-hidden="true"></span>
			<?php esc_html_e( 'Export Debug Info', 'wtc-shipping' ); ?>
		</button>
		<p class="description"><?php esc_html_e( 'Download system info for support troubleshooting.', 'wtc-shipping' ); ?></p>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#wtcc-export-debug').on('click', function() {
			var btn = $(this);
			btn.prop('disabled', true).text('<?php echo esc_js( __( 'Generating...', 'wtc-shipping' ) ); ?>');

			$.post(ajaxurl, {
				action: 'wtcc_export_debug_info',
				nonce: btn.data('nonce')
			}, function(response) {
				if (response.success) {
					var blob = new Blob([response.data.content], {type: 'text/plain'});
					var link = document.createElement('a');
					link.href = window.URL.createObjectURL(blob);
					link.download = response.data.filename;
					link.click();
				} else {
					alert('<?php echo esc_js( __( 'Failed to generate debug info.', 'wtc-shipping' ) ); ?>');
				}
				btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span><?php echo esc_js( __( 'Export Debug Info', 'wtc-shipping' ) ); ?>');
			});
		});
	});
	</script>
	<?php
}
