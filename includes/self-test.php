<?php
/**
 * Automated Self-Test for Inkfinit Shipping Engine.
 *
 * Runs health checks on configuration and API connectivity.
 * Uses WordPress Site Health standards for consistent UI.
 * Available for Pro tier and above.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register AJAX handler for self-test.
 */
add_action( 'wp_ajax_wtcc_run_self_test', 'wtcc_ajax_run_self_test' );

/**
 * AJAX handler to run self-test.
 */
function wtcc_ajax_run_self_test() {
	check_ajax_referer( 'wtcc_self_test_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Permission denied' );
	}

	// Only Pro+ can run self-test.
	if ( ! wtcc_is_pro() ) {
		wp_send_json_error( 'Pro license required' );
	}

	$results = wtcc_run_all_health_checks();

	wp_send_json_success( $results );
}

/**
 * Run all health checks and return results.
 *
 * @return array Array of test results.
 */
function wtcc_run_all_health_checks() {
	$results = array(
		'timestamp' => current_time( 'mysql' ),
		'tests'     => array(),
		'summary'   => array(
			'passed'  => 0,
			'warning' => 0,
			'failed'  => 0,
		),
	);

	// Run each test.
	$tests = array(
		'php_version'     => wtcc_test_php_version(),
		'wordpress'       => wtcc_test_wordpress_version(),
		'woocommerce'     => wtcc_test_woocommerce(),
		'ssl'             => wtcc_test_ssl(),
		'curl'            => wtcc_test_curl(),
		'api_credentials' => wtcc_test_api_credentials(),
		'oauth_token'     => wtcc_test_oauth_token(),
		'origin_address'  => wtcc_test_origin_address(),
		'shipping_zones'  => wtcc_test_shipping_zones(),
		'license'         => wtcc_test_license(),
	);

	foreach ( $tests as $key => $test ) {
		$results['tests'][ $key ] = $test;
		$results['summary'][ $test['status'] ]++;
	}

	return $results;
}

/**
 * Test PHP version.
 *
 * @return array Test result.
 */
function wtcc_test_php_version() {
	$required = '8.0';
	$current  = phpversion();
	$passed   = version_compare( $current, $required, '>=' );

	return array(
		'name'        => __( 'PHP Version', 'wtc-shipping' ),
		'status'      => $passed ? 'passed' : 'failed',
		'message'     => $passed
			? sprintf( __( 'PHP %s meets requirement (>= %s)', 'wtc-shipping' ), $current, $required )
			: sprintf( __( 'PHP %s is outdated. Please upgrade to PHP %s or higher.', 'wtc-shipping' ), $current, $required ),
		'value'       => $current,
	);
}

/**
 * Test WordPress version.
 *
 * @return array Test result.
 */
function wtcc_test_wordpress_version() {
	$required = '5.8';
	$current  = get_bloginfo( 'version' );
	$passed   = version_compare( $current, $required, '>=' );

	return array(
		'name'    => __( 'WordPress Version', 'wtc-shipping' ),
		'status'  => $passed ? 'passed' : 'warning',
		'message' => $passed
			? sprintf( __( 'WordPress %s meets requirement (>= %s)', 'wtc-shipping' ), $current, $required )
			: sprintf( __( 'WordPress %s may cause compatibility issues. Recommended: %s+', 'wtc-shipping' ), $current, $required ),
		'value'   => $current,
	);
}

/**
 * Test WooCommerce.
 *
 * @return array Test result.
 */
function wtcc_test_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array(
			'name'    => __( 'WooCommerce', 'wtc-shipping' ),
			'status'  => 'failed',
			'message' => __( 'WooCommerce is not active. This plugin requires WooCommerce.', 'wtc-shipping' ),
			'value'   => 'Not Active',
		);
	}

	$required = '8.0';
	$current  = WC_VERSION;
	$passed   = version_compare( $current, $required, '>=' );

	return array(
		'name'    => __( 'WooCommerce', 'wtc-shipping' ),
		'status'  => $passed ? 'passed' : 'warning',
		'message' => $passed
			? sprintf( __( 'WooCommerce %s is active and compatible', 'wtc-shipping' ), $current )
			: sprintf( __( 'WooCommerce %s may have compatibility issues. Recommended: %s+', 'wtc-shipping' ), $current, $required ),
		'value'   => $current,
	);
}

/**
 * Test SSL.
 *
 * @return array Test result.
 */
function wtcc_test_ssl() {
	$is_ssl = is_ssl();

	return array(
		'name'    => __( 'SSL Certificate', 'wtc-shipping' ),
		'status'  => $is_ssl ? 'passed' : 'warning',
		'message' => $is_ssl
			? __( 'Site is using HTTPS (required for USPS API)', 'wtc-shipping' )
			: __( 'Site is not using HTTPS. USPS API requires SSL.', 'wtc-shipping' ),
		'value'   => $is_ssl ? 'Enabled' : 'Disabled',
	);
}

/**
 * Test cURL.
 *
 * @return array Test result.
 */
function wtcc_test_curl() {
	$has_curl = function_exists( 'curl_version' );

	return array(
		'name'    => __( 'cURL Extension', 'wtc-shipping' ),
		'status'  => $has_curl ? 'passed' : 'failed',
		'message' => $has_curl
			? __( 'cURL is available for API requests', 'wtc-shipping' )
			: __( 'cURL is not available. USPS API requires cURL.', 'wtc-shipping' ),
		'value'   => $has_curl ? 'Available' : 'Not Available',
	);
}

/**
 * Test API credentials.
 *
 * @return array Test result.
 */
function wtcc_test_api_credentials() {
	$consumer_key    = get_option( 'wtcc_usps_consumer_key', '' );
	$consumer_secret = get_option( 'wtcc_usps_consumer_secret', '' );

	$has_key    = ! empty( $consumer_key );
	$has_secret = ! empty( $consumer_secret );

	if ( $has_key && $has_secret ) {
		return array(
			'name'    => __( 'USPS API Credentials', 'wtc-shipping' ),
			'status'  => 'passed',
			'message' => __( 'Consumer Key and Secret are configured', 'wtc-shipping' ),
			'value'   => 'Configured',
		);
	}

	$missing = array();
	if ( ! $has_key ) {
		$missing[] = 'Consumer Key';
	}
	if ( ! $has_secret ) {
		$missing[] = 'Consumer Secret';
	}

	return array(
		'name'    => __( 'USPS API Credentials', 'wtc-shipping' ),
		'status'  => 'failed',
		'message' => sprintf( __( 'Missing: %s', 'wtc-shipping' ), implode( ', ', $missing ) ),
		'value'   => 'Incomplete',
	);
}

/**
 * Test OAuth token.
 *
 * @return array Test result.
 */
function wtcc_test_oauth_token() {
	// Skip if no credentials.
	$consumer_key = get_option( 'wtcc_usps_consumer_key', '' );
	if ( empty( $consumer_key ) ) {
		return array(
			'name'    => __( 'USPS OAuth Token', 'wtc-shipping' ),
			'status'  => 'warning',
			'message' => __( 'Cannot test - API credentials not configured', 'wtc-shipping' ),
			'value'   => 'Skipped',
		);
	}

	$token = get_transient( 'wtcc_usps_oauth_token' );

	if ( $token ) {
		return array(
			'name'    => __( 'USPS OAuth Token', 'wtc-shipping' ),
			'status'  => 'passed',
			'message' => __( 'OAuth token is cached and valid', 'wtc-shipping' ),
			'value'   => 'Active',
		);
	}

	// Try to get a new token.
	if ( function_exists( 'wtcc_usps_get_oauth_token' ) ) {
		$new_token = wtcc_usps_get_oauth_token();
		if ( $new_token ) {
			return array(
				'name'    => __( 'USPS OAuth Token', 'wtc-shipping' ),
				'status'  => 'passed',
				'message' => __( 'Successfully obtained new OAuth token from USPS', 'wtc-shipping' ),
				'value'   => 'Refreshed',
			);
		}
	}

	return array(
		'name'    => __( 'USPS OAuth Token', 'wtc-shipping' ),
		'status'  => 'failed',
		'message' => __( 'Could not obtain OAuth token. Check API credentials.', 'wtc-shipping' ),
		'value'   => 'Failed',
	);
}

/**
 * Test origin address.
 *
 * @return array Test result.
 */
function wtcc_test_origin_address() {
	$origin_zip = get_option( 'wtcc_origin_zip', '' );

	if ( empty( $origin_zip ) ) {
		return array(
			'name'    => __( 'Origin Address', 'wtc-shipping' ),
			'status'  => 'failed',
			'message' => __( 'Origin ZIP code is required for shipping calculations', 'wtc-shipping' ),
			'value'   => 'Not Set',
		);
	}

	// Validate ZIP format.
	if ( ! preg_match( '/^\d{5}(-\d{4})?$/', $origin_zip ) ) {
		return array(
			'name'    => __( 'Origin Address', 'wtc-shipping' ),
			'status'  => 'warning',
			'message' => __( 'Origin ZIP code format may be invalid', 'wtc-shipping' ),
			'value'   => $origin_zip,
		);
	}

	return array(
		'name'    => __( 'Origin Address', 'wtc-shipping' ),
		'status'  => 'passed',
		'message' => sprintf( __( 'Origin ZIP %s is configured', 'wtc-shipping' ), $origin_zip ),
		'value'   => $origin_zip,
	);
}

/**
 * Test shipping zones.
 *
 * @return array Test result.
 */
function wtcc_test_shipping_zones() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return array(
			'name'    => __( 'Shipping Zones', 'wtc-shipping' ),
			'status'  => 'warning',
			'message' => __( 'WooCommerce Shipping Zones not available', 'wtc-shipping' ),
			'value'   => 'Unknown',
		);
	}

	$zones = WC_Shipping_Zones::get_zones();
	$count = count( $zones );

	if ( $count === 0 ) {
		return array(
			'name'    => __( 'Shipping Zones', 'wtc-shipping' ),
			'status'  => 'warning',
			'message' => __( 'No shipping zones configured. Consider adding zones for targeted rates.', 'wtc-shipping' ),
			'value'   => '0 zones',
		);
	}

	return array(
		'name'    => __( 'Shipping Zones', 'wtc-shipping' ),
		'status'  => 'passed',
		'message' => sprintf( _n( '%d shipping zone configured', '%d shipping zones configured', $count, 'wtc-shipping' ), $count ),
		'value'   => $count . ' zones',
	);
}

/**
 * Test license status.
 *
 * @return array Test result.
 */
function wtcc_test_license() {
	$status = wtcc_get_license_status();
	$expiry = wtcc_get_license_expiry_info();

	switch ( $status ) {
		case 'valid':
			$days_msg = $expiry ? sprintf( ' (%d days remaining)', $expiry['days_left'] ) : '';
			return array(
				'name'    => __( 'License', 'wtc-shipping' ),
				'status'  => 'passed',
				'message' => __( 'License is valid and active', 'wtc-shipping' ) . $days_msg,
				'value'   => 'Valid',
			);

		case 'expiring_soon':
			return array(
				'name'    => __( 'License', 'wtc-shipping' ),
				'status'  => 'warning',
				'message' => sprintf( __( 'License expires in %d days. Consider renewing.', 'wtc-shipping' ), $expiry['days_left'] ),
				'value'   => 'Expiring Soon',
			);

		case 'expired':
			return array(
				'name'    => __( 'License', 'wtc-shipping' ),
				'status'  => 'failed',
				'message' => __( 'License has expired. Pro features are disabled.', 'wtc-shipping' ),
				'value'   => 'Expired',
			);

		case 'invalid':
			return array(
				'name'    => __( 'License', 'wtc-shipping' ),
				'status'  => 'failed',
				'message' => __( 'License key is invalid or revoked.', 'wtc-shipping' ),
				'value'   => 'Invalid',
			);

		default:
			return array(
				'name'    => __( 'License', 'wtc-shipping' ),
				'status'  => 'warning',
				'message' => __( 'License status unknown. Server may be unreachable.', 'wtc-shipping' ),
				'value'   => 'Unknown',
			);
	}
}

/**
 * Render self-test button and results area.
 */
function wtcc_render_self_test_ui() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	// Check if Pro.
	if ( ! wtcc_is_pro() ) {
		?>
		<div class="wtcc-self-test-locked" style="padding:15px;background:#f0f0f1;border-left:4px solid #d6336c;margin:20px 0;">
			<strong><?php esc_html_e( 'Self-Test', 'wtc-shipping' ); ?></strong>
			<?php wtcc_render_pro_badge(); ?>
			<p style="margin:10px 0 0;"><?php esc_html_e( 'Automated health checks require a Pro license.', 'wtc-shipping' ); ?></p>
		</div>
		<?php
		return;
	}

	$nonce = wp_create_nonce( 'wtcc_self_test_nonce' );
	?>
	<div class="wtcc-self-test" style="margin:20px 0;">
		<h3><?php esc_html_e( 'Automated Self-Test', 'wtc-shipping' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Run a health check on your configuration and API connectivity.', 'wtc-shipping' ); ?></p>

		<button type="button" id="wtcc-run-self-test" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>" style="margin:10px 0;">
			<span class="dashicons dashicons-yes-alt" style="vertical-align:middle;margin-right:5px;" aria-hidden="true"></span>
			<?php esc_html_e( 'Run Health Check', 'wtc-shipping' ); ?>
		</button>

		<div id="wtcc-self-test-results" style="display:none;margin-top:20px;"></div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#wtcc-run-self-test').on('click', function() {
			var btn = $(this);
			var results = $('#wtcc-self-test-results');

			btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="vertical-align:middle;margin-right:5px;"></span><?php echo esc_js( __( 'Running Tests...', 'wtc-shipping' ) ); ?>');
			results.hide();

			$.post(ajaxurl, {
				action: 'wtcc_run_self_test',
				nonce: btn.data('nonce')
			}, function(response) {
				if (response.success) {
					wtccRenderTestResults(response.data, results);
				} else {
					results.html('<div class="notice notice-error"><p>' + response.data + '</p></div>').show();
				}
				btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt" style="vertical-align:middle;margin-right:5px;"></span><?php echo esc_js( __( 'Run Health Check', 'wtc-shipping' ) ); ?>');
			});
		});

		function wtccRenderTestResults(data, container) {
			var html = '<div class="health-check-body">';

			// Summary
			html += '<div style="display:flex;gap:20px;margin-bottom:20px;padding:15px;background:#f9f9f9;border-radius:4px;">';
			html += '<div><strong style="color:#28a745;font-size:24px;">' + data.summary.passed + '</strong><br><span style="color:#666;"><?php echo esc_js( __( 'Passed', 'wtc-shipping' ) ); ?></span></div>';
			html += '<div><strong style="color:#f0ad4e;font-size:24px;">' + data.summary.warning + '</strong><br><span style="color:#666;"><?php echo esc_js( __( 'Warnings', 'wtc-shipping' ) ); ?></span></div>';
			html += '<div><strong style="color:#d32f2f;font-size:24px;">' + data.summary.failed + '</strong><br><span style="color:#666;"><?php echo esc_js( __( 'Failed', 'wtc-shipping' ) ); ?></span></div>';
			html += '</div>';

			// Tests table
			html += '<table class="widefat striped"><thead><tr><th><?php echo esc_js( __( 'Test', 'wtc-shipping' ) ); ?></th><th><?php echo esc_js( __( 'Status', 'wtc-shipping' ) ); ?></th><th><?php echo esc_js( __( 'Details', 'wtc-shipping' ) ); ?></th></tr></thead><tbody>';

			var statusIcons = {
				'passed': '<span style="color:#28a745;">✓</span>',
				'warning': '<span style="color:#f0ad4e;">⚠</span>',
				'failed': '<span style="color:#d32f2f;">✗</span>'
			};

			$.each(data.tests, function(key, test) {
				html += '<tr>';
				html += '<td><strong>' + test.name + '</strong></td>';
				html += '<td>' + statusIcons[test.status] + ' ' + test.value + '</td>';
				html += '<td>' + test.message + '</td>';
				html += '</tr>';
			});

			html += '</tbody></table>';
			html += '<p class="description" style="margin-top:10px;"><?php echo esc_js( __( 'Last run:', 'wtc-shipping' ) ); ?> ' + data.timestamp + '</p>';
			html += '</div>';

			container.html(html).show();
		}
	});
	</script>
	<style>
	.dashicons.spin { animation: spin 1s linear infinite; }
	@keyframes spin { 100% { transform: rotate(360deg); } }
	</style>
	<?php
}
