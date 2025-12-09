<?php
/**
 * Bulk License Key Import for Agencies.
 *
 * Allows Premium and Enterprise customers to import multiple
 * license keys for multi-site deployments.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register AJAX handlers for bulk import.
 */
add_action( 'wp_ajax_wtcc_bulk_import_licenses', 'wtcc_ajax_bulk_import_licenses' );
add_action( 'wp_ajax_wtcc_validate_imported_licenses', 'wtcc_ajax_validate_imported_licenses' );

/**
 * AJAX handler to import bulk licenses.
 */
function wtcc_ajax_bulk_import_licenses() {
	check_ajax_referer( 'wtcc_bulk_import_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( __( 'Permission denied', 'wtc-shipping' ) );
	}

	// Only Premium+ can use bulk import.
	if ( ! wtcc_is_premium() ) {
		wp_send_json_error( __( 'Premium license required for bulk import', 'wtc-shipping' ) );
	}

	$licenses_raw = isset( $_POST['licenses'] ) ? sanitize_textarea_field( wp_unslash( $_POST['licenses'] ) ) : '';

	if ( empty( $licenses_raw ) ) {
		wp_send_json_error( __( 'No license keys provided', 'wtc-shipping' ) );
	}

	// Parse licenses (one per line, comma separated, or JSON array).
	$licenses = wtcc_parse_license_input( $licenses_raw );

	if ( empty( $licenses ) ) {
		wp_send_json_error( __( 'Could not parse license keys', 'wtc-shipping' ) );
	}

	// Store imported licenses.
	$imported = get_option( 'wtcc_imported_licenses', array() );

	$results = array(
		'imported' => 0,
		'skipped'  => 0,
		'errors'   => array(),
	);

	foreach ( $licenses as $license ) {
		$license = trim( $license );

		if ( empty( $license ) ) {
			continue;
		}

		// Check if already imported.
		if ( in_array( $license, array_column( $imported, 'key' ), true ) ) {
			$results['skipped']++;
			continue;
		}

		// Validate format (basic check).
		$is_valid_format = preg_match( '/^[A-Z0-9\-]{16,}$/i', $license );
		$is_dev_key      = preg_match( '/^INKDEV-[A-Z0-9]{4,}-[A-Z0-9]{8,}$/i', $license );
		if ( ! $is_valid_format && ! $is_dev_key ) {
			$results['errors'][] = sprintf( __( 'Invalid format: %s', 'wtc-shipping' ), substr( $license, 0, 10 ) . '...' );
			continue;
		}

		// Add to imported list.
		$imported[] = array(
			'key'         => $license,
			'imported_at' => current_time( 'mysql' ),
			'status'      => 'pending_validation',
			'site'        => '',
		);

		$results['imported']++;
	}

	update_option( 'wtcc_imported_licenses', $imported );

	wp_send_json_success( $results );
}

/**
 * Parse license input from various formats.
 *
 * @param string $input Raw input string.
 * @return array Array of license keys.
 */
function wtcc_parse_license_input( $input ) {
	// Try JSON array first.
	$json = json_decode( $input, true );
	if ( is_array( $json ) ) {
		return array_map( 'trim', $json );
	}

	// Try newline-separated.
	if ( strpos( $input, "\n" ) !== false ) {
		return array_filter( array_map( 'trim', explode( "\n", $input ) ) );
	}

	// Try comma-separated.
	if ( strpos( $input, ',' ) !== false ) {
		return array_filter( array_map( 'trim', explode( ',', $input ) ) );
	}

	// Single license.
	return array( trim( $input ) );
}

/**
 * AJAX handler to validate imported licenses.
 */
function wtcc_ajax_validate_imported_licenses() {
	check_ajax_referer( 'wtcc_bulk_import_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( __( 'Permission denied', 'wtc-shipping' ) );
	}

	if ( ! wtcc_is_premium() ) {
		wp_send_json_error( __( 'Premium license required', 'wtc-shipping' ) );
	}

	$imported = get_option( 'wtcc_imported_licenses', array() );

	if ( empty( $imported ) ) {
		wp_send_json_error( __( 'No imported licenses to validate', 'wtc-shipping' ) );
	}

	$results = array(
		'validated' => 0,
		'invalid'   => 0,
		'errors'    => array(),
	);

	// Validate each license.
	foreach ( $imported as $index => $license_data ) {
		$key = $license_data['key'];

		// Call license server.
		$validation = wtcc_validate_license_with_server( $key );

		if ( $validation['valid'] ) {
			$imported[ $index ]['status']       = 'valid';
			$imported[ $index ]['tier']         = $validation['tier'] ?? 'pro';
			$imported[ $index ]['expires']      = $validation['expires_at'] ?? '';
			$imported[ $index ]['validated_at'] = current_time( 'mysql' );
			$results['validated']++;
		} else {
			$imported[ $index ]['status'] = 'invalid';
			$results['invalid']++;
			$results['errors'][] = sprintf( __( 'Key %s is invalid', 'wtc-shipping' ), substr( $key, 0, 8 ) . '...' );
		}
	}

	update_option( 'wtcc_imported_licenses', $imported );

	wp_send_json_success( $results );
}

/**
 * Validate a license key with the license server.
 *
 * @param string $key License key.
 * @return array Validation result.
 */
function wtcc_validate_license_with_server( $key ) {
	// Dev keys validate locally (format: INKDEV-TIER-XXXXXXXX).
	if ( preg_match( '/^INKDEV-[A-Z0-9]{4,}-[A-Z0-9]{8,}$/i', $key ) ) {
		$tier = 'pro';
		if ( preg_match( '/INKDEV-ENT/i', $key ) ) {
			$tier = 'enterprise';
		} elseif ( preg_match( '/INKDEV-PREM/i', $key ) ) {
			$tier = 'premium';
		}

		return array(
			'valid'      => true,
			'tier'       => $tier,
			'expires_at' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
		);
	}

	// Check if license server URL is configured.
	if ( ! defined( 'WTCC_LICENSE_SERVER_URL' ) || empty( WTCC_LICENSE_SERVER_URL ) ) {
		return array(
			'valid' => false,
			'error' => 'License server not configured.',
		);
	}

	// Call actual license server.
	$response = wp_remote_post( WTCC_LICENSE_SERVER_URL . '/inkfinit/v1/license/validate', array(
		'timeout' => 15,
		'body'    => array(
			'license_key' => $key,
			'site_url'    => site_url(),
		),
	) );

	if ( is_wp_error( $response ) ) {
		return array(
			'valid' => false,
			'error' => $response->get_error_message(),
		);
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	return array(
		'valid'      => ! empty( $body['valid'] ),
		'tier'       => $body['tier'] ?? 'pro',
		'expires_at' => $body['expires_at'] ?? '',
	);
}

/**
 * Render the bulk import UI.
 */
function wtcc_render_bulk_import_ui() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	// Check tier.
	if ( ! wtcc_is_premium() ) {
		?>
		<div class="wtcc-bulk-import-locked" style="padding:20px;background:#f0f0f1;border-left:4px solid #d6336c;margin:20px 0;">
			<h3 style="margin:0 0 10px;">
				<?php esc_html_e( 'Bulk License Import', 'wtc-shipping' ); ?>
				<?php wtcc_render_premium_badge(); ?>
			</h3>
			<p><?php esc_html_e( 'Import multiple license keys for multi-site deployments. Available for Premium and Enterprise customers.', 'wtc-shipping' ); ?></p>
			<a href="<?php echo esc_url( WTCC_UPGRADE_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
				<?php esc_html_e( 'Upgrade to Premium', 'wtc-shipping' ); ?>
			</a>
		</div>
		<?php
		return;
	}

	$nonce    = wp_create_nonce( 'wtcc_bulk_import_nonce' );
	$imported = get_option( 'wtcc_imported_licenses', array() );

	?>
	<div class="wtcc-bulk-import" style="margin:20px 0;">
		<h3><?php esc_html_e( 'Bulk License Key Import', 'wtc-shipping' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Import multiple license keys for agency deployments. Enter one key per line, comma-separated, or as a JSON array.', 'wtc-shipping' ); ?></p>

		<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:15px;">
			<!-- Import Form -->
			<div style="background:#fff;padding:20px;border:1px solid #c3c4c7;border-radius:4px;">
				<h4 style="margin:0 0 10px;"><?php esc_html_e( 'Import Keys', 'wtc-shipping' ); ?></h4>
				<textarea id="wtcc-bulk-licenses" rows="8" style="width:100%;font-family:monospace;" placeholder="<?php esc_attr_e( "Enter license keys...\nOne per line, or comma-separated", 'wtc-shipping' ); ?>"></textarea>
				<div style="margin-top:10px;display:flex;gap:10px;">
					<button type="button" id="wtcc-import-licenses" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<span class="dashicons dashicons-upload" style="vertical-align:middle;margin-right:5px;" aria-hidden="true"></span>
						<?php esc_html_e( 'Import Keys', 'wtc-shipping' ); ?>
					</button>
					<button type="button" id="wtcc-validate-licenses" class="button" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<span class="dashicons dashicons-yes-alt" style="vertical-align:middle;margin-right:5px;" aria-hidden="true"></span>
						<?php esc_html_e( 'Validate All', 'wtc-shipping' ); ?>
					</button>
				</div>
				<div id="wtcc-import-results" style="margin-top:15px;display:none;"></div>
			</div>

			<!-- Imported Keys List -->
			<div style="background:#fff;padding:20px;border:1px solid #c3c4c7;border-radius:4px;">
				<h4 style="margin:0 0 10px;">
					<?php esc_html_e( 'Imported Keys', 'wtc-shipping' ); ?>
					<span class="wtcc-key-count" style="background:#2271b1;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:5px;">
						<?php echo count( $imported ); ?>
					</span>
				</h4>

				<?php if ( empty( $imported ) ) : ?>
					<p style="color:#666;font-style:italic;"><?php esc_html_e( 'No keys imported yet.', 'wtc-shipping' ); ?></p>
				<?php else : ?>
					<div style="max-height:250px;overflow-y:auto;">
						<table class="widefat striped" style="font-size:12px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Key', 'wtc-shipping' ); ?></th>
									<th><?php esc_html_e( 'Status', 'wtc-shipping' ); ?></th>
									<th><?php esc_html_e( 'Imported', 'wtc-shipping' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $imported as $license ) : ?>
									<tr>
										<td>
											<code style="font-size:11px;"><?php echo esc_html( substr( $license['key'], 0, 12 ) . '...' ); ?></code>
										</td>
										<td>
											<?php
											$status_colors = array(
												'valid'              => '#28a745',
												'invalid'            => '#d32f2f',
												'pending_validation' => '#f0ad4e',
											);
											$color = $status_colors[ $license['status'] ] ?? '#666';
											?>
											<span style="color:<?php echo esc_attr( $color ); ?>;font-weight:600;">
												<?php echo esc_html( ucfirst( str_replace( '_', ' ', $license['status'] ) ) ); ?>
											</span>
										</td>
										<td style="color:#666;">
											<?php echo esc_html( human_time_diff( strtotime( $license['imported_at'] ) ) . ' ago' ); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		// Import licenses.
		$('#wtcc-import-licenses').on('click', function() {
			var btn = $(this);
			var licenses = $('#wtcc-bulk-licenses').val();
			var results = $('#wtcc-import-results');

			if (!licenses.trim()) {
				alert('<?php echo esc_js( __( 'Please enter license keys', 'wtc-shipping' ) ); ?>');
				return;
			}

			btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="vertical-align:middle;margin-right:5px;"></span><?php echo esc_js( __( 'Importing...', 'wtc-shipping' ) ); ?>');

			$.post(ajaxurl, {
				action: 'wtcc_bulk_import_licenses',
				nonce: btn.data('nonce'),
				licenses: licenses
			}, function(response) {
				if (response.success) {
					var data = response.data;
					results.html(
						'<div class="notice notice-success inline"><p>' +
						'<?php echo esc_js( __( 'Imported:', 'wtc-shipping' ) ); ?> ' + data.imported +
						' | <?php echo esc_js( __( 'Skipped:', 'wtc-shipping' ) ); ?> ' + data.skipped +
						(data.errors.length ? '<br><?php echo esc_js( __( 'Errors:', 'wtc-shipping' ) ); ?> ' + data.errors.join(', ') : '') +
						'</p></div>'
					).show();
					$('#wtcc-bulk-licenses').val('');
					setTimeout(function() { location.reload(); }, 2000);
				} else {
					results.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>').show();
				}
				btn.prop('disabled', false).html('<span class="dashicons dashicons-upload" style="vertical-align:middle;margin-right:5px;"></span><?php echo esc_js( __( 'Import Keys', 'wtc-shipping' ) ); ?>');
			});
		});

		// Validate all licenses.
		$('#wtcc-validate-licenses').on('click', function() {
			var btn = $(this);
			var results = $('#wtcc-import-results');

			btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="vertical-align:middle;margin-right:5px;"></span><?php echo esc_js( __( 'Validating...', 'wtc-shipping' ) ); ?>');

			$.post(ajaxurl, {
				action: 'wtcc_validate_imported_licenses',
				nonce: btn.data('nonce')
			}, function(response) {
				if (response.success) {
					var data = response.data;
					results.html(
						'<div class="notice notice-info inline"><p>' +
						'<?php echo esc_js( __( 'Valid:', 'wtc-shipping' ) ); ?> ' + data.validated +
						' | <?php echo esc_js( __( 'Invalid:', 'wtc-shipping' ) ); ?> ' + data.invalid +
						'</p></div>'
					).show();
					setTimeout(function() { location.reload(); }, 2000);
				} else {
					results.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>').show();
				}
				btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt" style="vertical-align:middle;margin-right:5px;"></span><?php echo esc_js( __( 'Validate All', 'wtc-shipping' ) ); ?>');
			});
		});
	});
	</script>
	<style>
	.dashicons.spin { animation: spin 1s linear infinite; }
	@keyframes spin { 100% { transform: rotate(360deg); } }
	</style>
	<?php
}

/**
 * Helper to render premium badge.
 */
function wtcc_render_premium_badge() {
	echo '<span style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:600;margin-left:5px;text-transform:uppercase;">' . esc_html__( 'Premium', 'wtc-shipping' ) . '</span>';
}

/**
 * Render the Bulk License Import page.
 */
function wtcc_render_bulk_import_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'wtc-shipping' ) );
	}
	?>
	<div class="wrap">
		<?php if ( function_exists( 'wtcc_admin_header' ) ) : ?>
			<?php wtcc_admin_header( __( 'Bulk License Import', 'wtc-shipping' ) ); ?>
		<?php else : ?>
			<h1><?php esc_html_e( 'Bulk License Import', 'wtc-shipping' ); ?></h1>
		<?php endif; ?>
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<div class="postbox">
						<h2 class="hndle">
							<span class="dashicons dashicons-upload" style="margin-right:8px;"></span>
							<?php esc_html_e( 'Import License Keys', 'wtc-shipping' ); ?>
						</h2>
						<div class="inside">
							<?php wtcc_render_bulk_import_ui(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
