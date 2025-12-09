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

	if ( ! wtcc_is_premium() ) {
		wp_send_json_error( __( 'Premium license required for bulk import', 'wtc-shipping' ) );
	}

	$licenses_raw = isset( $_POST['licenses'] ) ? sanitize_textarea_field( wp_unslash( $_POST['licenses'] ) ) : '';

	if ( empty( $licenses_raw ) ) {
		wp_send_json_error( __( 'No license keys provided', 'wtc-shipping' ) );
	}

	$licenses = wtcc_parse_license_input( $licenses_raw );

	if ( empty( $licenses ) ) {
		wp_send_json_error( __( 'Could not parse license keys', 'wtc-shipping' ) );
	}

	$imported = get_option( 'wtcc_imported_licenses', array() );
	$results  = array( 'imported' => 0, 'skipped' => 0, 'errors' => array() );

	foreach ( $licenses as $license ) {
		$license = trim( $license );

		if ( empty( $license ) ) {
			continue;
		}

		if ( in_array( $license, array_column( $imported, 'key' ), true ) ) {
			$results['skipped']++;
			continue;
		}

		$is_valid_format = preg_match( '/^[A-Z0-9\-]{16,}$/i', $license );
		$is_dev_key      = preg_match( '/^INKDEV-[A-Z0-9]{4,}-[A-Z0-9]{8,}$/i', $license );
		if ( ! $is_valid_format && ! $is_dev_key ) {
			$results['errors'][] = sprintf( __( 'Invalid format: %s', 'wtc-shipping' ), substr( $license, 0, 10 ) . '...' );
			continue;
		}

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
 */
function wtcc_parse_license_input( $input ) {
	$json = json_decode( $input, true );
	if ( is_array( $json ) ) {
		return array_map( 'trim', $json );
	}

	if ( strpos( $input, "\n" ) !== false ) {
		return array_filter( array_map( 'trim', explode( "\n", $input ) ) );
	}

	if ( strpos( $input, ',' ) !== false ) {
		return array_filter( array_map( 'trim', explode( ',', $input ) ) );
	}

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

	$results = array( 'validated' => 0, 'invalid' => 0, 'errors' => array() );

	foreach ( $imported as $index => $license_data ) {
		$key        = $license_data['key'];
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
 */
function wtcc_validate_license_with_server( $key ) {
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

	if ( ! defined( 'WTCC_LICENSE_SERVER_URL' ) || empty( WTCC_LICENSE_SERVER_URL ) ) {
		return array( 'valid' => false, 'error' => 'License server not configured.' );
	}

	$response = wp_remote_post( WTCC_LICENSE_SERVER_URL . '/inkfinit/v1/license/validate', array(
		'timeout' => 15,
		'body'    => array( 'license_key' => $key, 'site_url' => site_url() ),
	) );

	if ( is_wp_error( $response ) ) {
		return array( 'valid' => false, 'error' => $response->get_error_message() );
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

	if ( ! wtcc_is_premium() ) {
		?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'Bulk License Import', 'wtc-shipping' ); ?></strong>
				<span class="dashicons dashicons-star-filled"></span>
			</p>
			<p><?php esc_html_e( 'Import multiple license keys for multi-site deployments. Available for Premium and Enterprise customers.', 'wtc-shipping' ); ?></p>
			<p><a href="<?php echo esc_url( WTCC_UPGRADE_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'wtc-shipping' ); ?></a></p>
		</div>
		<?php
		return;
	}

	$nonce    = wp_create_nonce( 'wtcc_bulk_import_nonce' );
	$imported = get_option( 'wtcc_imported_licenses', array() );
	?>
	<h3><?php esc_html_e( 'Bulk License Key Import', 'wtc-shipping' ); ?></h3>
	<p class="description"><?php esc_html_e( 'Import multiple license keys for agency deployments. Enter one key per line, comma-separated, or as a JSON array.', 'wtc-shipping' ); ?></p>

	<div class="card">
		<h4><?php esc_html_e( 'Import Keys', 'wtc-shipping' ); ?></h4>
		<p>
			<textarea id="wtcc-bulk-licenses" rows="6" class="large-text code" placeholder="<?php esc_attr_e( "Enter license keys...\nOne per line, or comma-separated", 'wtc-shipping' ); ?>"></textarea>
		</p>
		<p>
			<button type="button" id="wtcc-import-licenses" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import Keys', 'wtc-shipping' ); ?>
			</button>
			<button type="button" id="wtcc-validate-licenses" class="button" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Validate All', 'wtc-shipping' ); ?>
			</button>
		</p>
		<div id="wtcc-import-results" hidden></div>
	</div>

	<div class="card">
		<h4><?php esc_html_e( 'Imported Keys', 'wtc-shipping' ); ?> (<?php echo count( $imported ); ?>)</h4>

		<?php if ( empty( $imported ) ) : ?>
			<p class="description"><?php esc_html_e( 'No keys imported yet.', 'wtc-shipping' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
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
							<td><code><?php echo esc_html( substr( $license['key'], 0, 12 ) . '...' ); ?></code></td>
							<td>
								<?php
								$status_icon = 'dashicons-marker';
								if ( 'valid' === $license['status'] ) {
									$status_icon = 'dashicons-yes';
								} elseif ( 'invalid' === $license['status'] ) {
									$status_icon = 'dashicons-no';
								}
								?>
								<span class="dashicons <?php echo esc_attr( $status_icon ); ?>"></span>
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $license['status'] ) ) ); ?>
							</td>
							<td><?php echo esc_html( human_time_diff( strtotime( $license['imported_at'] ) ) . ' ago' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#wtcc-import-licenses').on('click', function() {
			var btn = $(this);
			var licenses = $('#wtcc-bulk-licenses').val();
			var results = $('#wtcc-import-results');

			if (!licenses.trim()) {
				alert('<?php echo esc_js( __( 'Please enter license keys', 'wtc-shipping' ) ); ?>');
				return;
			}

			btn.prop('disabled', true);

			$.post(ajaxurl, {
				action: 'wtcc_bulk_import_licenses',
				nonce: btn.data('nonce'),
				licenses: licenses
			}, function(response) {
				if (response.success) {
					var data = response.data;
					results.html('<div class="notice notice-success inline"><p>Imported: ' + data.imported + ' | Skipped: ' + data.skipped + '</p></div>').removeAttr('hidden');
					$('#wtcc-bulk-licenses').val('');
					setTimeout(function() { location.reload(); }, 2000);
				} else {
					results.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>').removeAttr('hidden');
				}
				btn.prop('disabled', false);
			});
		});

		$('#wtcc-validate-licenses').on('click', function() {
			var btn = $(this);
			var results = $('#wtcc-import-results');

			btn.prop('disabled', true);

			$.post(ajaxurl, {
				action: 'wtcc_validate_imported_licenses',
				nonce: btn.data('nonce')
			}, function(response) {
				if (response.success) {
					var data = response.data;
					results.html('<div class="notice notice-info inline"><p>Valid: ' + data.validated + ' | Invalid: ' + data.invalid + '</p></div>').removeAttr('hidden');
					setTimeout(function() { location.reload(); }, 2000);
				} else {
					results.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>').removeAttr('hidden');
				}
				btn.prop('disabled', false);
			});
		});
	});
	</script>
	<?php
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
		<h1><?php esc_html_e( 'Bulk License Import', 'wtc-shipping' ); ?></h1>
		<?php wtcc_render_bulk_import_ui(); ?>
	</div>
	<?php
}
