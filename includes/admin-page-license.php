<?php
/**
 * Inkfinit Shipping - License Settings Page
 *
 * Uses native WordPress admin UI classes only.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the license settings page.
 */
function wtcc_render_license_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'wtc-shipping' ) );
	}

	$license_key = get_option( 'wtcc_license_key', '' );
	$edition     = wtcc_get_edition();

	// Handle form submission.
	if ( isset( $_POST['wtcc_license_action'] ) && check_admin_referer( 'wtcc_license_nonce' ) ) {
		$action = sanitize_key( $_POST['wtcc_license_action'] );

		if ( 'save_key' === $action && isset( $_POST['wtcc_license_key'] ) ) {
			$new_key = sanitize_text_field( wp_unslash( $_POST['wtcc_license_key'] ) );
			update_option( 'wtcc_license_key', $new_key );
			delete_transient( 'wtcc_license_data_' . md5( $new_key . '|' . get_option( 'wtcc_license_server_url', '' ) ) );
			$license_key = $new_key;
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'License key saved successfully!', 'wtc-shipping' ) . '</p></div>';
		} elseif ( 'generate_test_key' === $action ) {
			$test_key = wtcc_setup_test_license();
			$license_key = $test_key;
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Test license key generated:', 'wtc-shipping' ) . '</strong> <code>' . esc_html( $test_key ) . '</code></p></div>';
		} elseif ( 'clear_key' === $action ) {
			delete_option( 'wtcc_license_key' );
			delete_option( 'wtcc_edition' );
			$license_key = '';
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'License key cleared. Plugin reverted to Free mode.', 'wtc-shipping' ) . '</p></div>';
		} elseif ( 'toggle_dev_mode' === $action ) {
			$current = get_option( 'wtcc_dev_mode_enabled', false );
			update_option( 'wtcc_dev_mode_enabled', ! $current );
			if ( ! $current ) {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Developer Mode ENABLED', 'wtc-shipping' ) . '</strong> - Test keys now work for all admins.</p></div>';
			} else {
				echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Developer Mode disabled.', 'wtc-shipping' ) . '</p></div>';
			}
		}

		$edition = wtcc_get_edition();
	}

	$license_status = wtcc_get_license_status();
	$license_data   = wtcc_get_license_data();
	$expiry_info    = wtcc_get_license_expiry_info();
	$dev_mode_enabled = get_option( 'wtcc_dev_mode_enabled', false );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Inkfinit Shipping - License Settings', 'wtc-shipping' ); ?></h1>

		<div class="card">
			<h2><?php esc_html_e( 'Current Edition', 'wtc-shipping' ); ?></h2>
			<p>
				<strong>
					<?php
					$edition_labels = array(
						'free'       => __( 'Free Edition', 'wtc-shipping' ),
						'pro'        => __( 'Pro Edition', 'wtc-shipping' ),
						'premium'    => __( 'Premium Edition', 'wtc-shipping' ),
						'enterprise' => __( 'Enterprise Edition', 'wtc-shipping' ),
					);
					echo esc_html( $edition_labels[ $edition ] ?? ucfirst( $edition ) );
					?>
				</strong>
			</p>
			<p class="description">
				<?php
				if ( 'free' === $edition ) {
					esc_html_e( 'Free edition. Upgrade to Pro for full features.', 'wtc-shipping' );
				} else {
					esc_html_e( 'All features unlocked.', 'wtc-shipping' );
				}
				?>
			</p>
		</div>

		<div class="card">
			<h2><?php esc_html_e( 'License Key', 'wtc-shipping' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'wtcc_license_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="wtcc_license_key"><?php esc_html_e( 'License Key', 'wtc-shipping' ); ?></label>
						</th>
						<td>
							<input type="text" name="wtcc_license_key" id="wtcc_license_key" value="<?php echo esc_attr( $license_key ); ?>" class="large-text code" placeholder="<?php esc_attr_e( 'e.g., IUSE-PRO-XXXXXXXX', 'wtc-shipping' ); ?>" autocomplete="off" />
							<p class="description">
								<?php
								printf(
									esc_html__( 'Enter your license key from %s', 'wtc-shipping' ),
									'<a href="https://inkfinit.pro" target="_blank" rel="noopener">inkfinit.pro</a>'
								);
								?>
							</p>
						</td>
					</tr>

					<?php if ( ! empty( $license_key ) ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'wtc-shipping' ); ?></th>
						<td>
							<?php
							$status_labels = array(
								'none'          => __( 'No License', 'wtc-shipping' ),
								'valid'         => __( 'Valid', 'wtc-shipping' ),
								'invalid'       => __( 'Invalid', 'wtc-shipping' ),
								'expired'       => __( 'Expired', 'wtc-shipping' ),
								'expiring_soon' => __( 'Expiring Soon', 'wtc-shipping' ),
								'unknown'       => __( 'Unknown (server unreachable)', 'wtc-shipping' ),
							);
							$status_classes = array(
								'valid'         => 'notice-success',
								'invalid'       => 'notice-error',
								'expired'       => 'notice-error',
								'expiring_soon' => 'notice-warning',
								'unknown'       => 'notice-warning',
							);
							$status_class = $status_classes[ $license_status ] ?? '';
							?>
							<span class="<?php echo esc_attr( $status_class ); ?>">
								<strong><?php echo esc_html( $status_labels[ $license_status ] ?? $license_status ); ?></strong>
							</span>
						</td>
					</tr>

					<?php if ( $expiry_info ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Expires', 'wtc-shipping' ); ?></th>
						<td>
							<?php
							$date_formatted = date_i18n( get_option( 'date_format' ), strtotime( $expiry_info['expires_at'] ) );
							echo esc_html( $date_formatted );
							echo ' (' . esc_html( sprintf( _n( '%d day left', '%d days left', $expiry_info['days_left'], 'wtc-shipping' ), $expiry_info['days_left'] ) ) . ')';
							?>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( $license_data && isset( $license_data['customer'] ) ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Licensed To', 'wtc-shipping' ); ?></th>
						<td>
							<?php echo esc_html( $license_data['customer'] ); ?>
							<?php if ( isset( $license_data['is_test_key'] ) && $license_data['is_test_key'] ) : ?>
								<span class="description">(<?php esc_html_e( 'Test Key', 'wtc-shipping' ); ?>)</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endif; ?>
					<?php endif; ?>
				</table>

				<p class="submit">
					<button type="submit" name="wtcc_license_action" value="save_key" class="button button-primary">
						<?php esc_html_e( 'Save License Key', 'wtc-shipping' ); ?>
					</button>
					<?php if ( ! empty( $license_key ) ) : ?>
					<button type="submit" name="wtcc_license_action" value="clear_key" class="button" onclick="return confirm('<?php esc_attr_e( 'Clear license key?', 'wtc-shipping' ); ?>');">
						<?php esc_html_e( 'Clear License', 'wtc-shipping' ); ?>
					</button>
					<?php endif; ?>
				</p>
			</form>
		</div>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<div class="card">
			<h2>
				<span class="dashicons dashicons-admin-tools"></span>
				<?php esc_html_e( 'Developer Mode', 'wtc-shipping' ); ?>
				<?php if ( $dev_mode_enabled ) : ?>
					<span class="update-plugins count-1"><span class="plugin-count">ON</span></span>
				<?php endif; ?>
			</h2>
			
			<?php if ( $dev_mode_enabled ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Developer Mode is ON', 'wtc-shipping' ); ?></strong> — <?php esc_html_e( 'Test keys are active. Disable before going live!', 'wtc-shipping' ); ?></p>
			</div>
			<?php endif; ?>
			
			<p class="description"><?php esc_html_e( 'When enabled, test license keys (INKDEV-*) work for all administrators.', 'wtc-shipping' ); ?></p>
			
			<form method="post">
				<?php wp_nonce_field( 'wtcc_license_nonce' ); ?>
				<p class="submit">
					<button type="submit" name="wtcc_license_action" value="toggle_dev_mode" class="button <?php echo $dev_mode_enabled ? '' : 'button-primary'; ?>">
						<?php echo $dev_mode_enabled ? esc_html__( 'Disable Developer Mode', 'wtc-shipping' ) : esc_html__( 'Enable Developer Mode', 'wtc-shipping' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php endif; ?>

		<div class="card">
			<h2><?php esc_html_e( 'Feature Comparison', 'wtc-shipping' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Feature', 'wtc-shipping' ); ?></th>
						<th><?php esc_html_e( 'Free', 'wtc-shipping' ); ?></th>
						<th><?php esc_html_e( 'Pro', 'wtc-shipping' ); ?></th>
						<th><?php esc_html_e( 'Enterprise', 'wtc-shipping' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Rate Calculator', 'wtc-shipping' ); ?></td>
						<td><span class="dashicons dashicons-yes"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Live USPS Rates', 'wtc-shipping' ); ?></td>
						<td><span class="dashicons dashicons-minus"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Label Printing', 'wtc-shipping' ); ?></td>
						<td><span class="dashicons dashicons-minus"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Order Tracking', 'wtc-shipping' ); ?></td>
						<td><span class="dashicons dashicons-minus"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Shipping Presets', 'wtc-shipping' ); ?></td>
						<td><span class="dashicons dashicons-minus"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'White-Label Mode', 'wtc-shipping' ); ?></td>
						<td><span class="dashicons dashicons-minus"></span></td>
						<td><span class="dashicons dashicons-minus"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Priority Support', 'wtc-shipping' ); ?></td>
						<td><span class="dashicons dashicons-minus"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
						<td><span class="dashicons dashicons-yes"></span></td>
					</tr>
				</tbody>
			</table>
			<p>
				<a href="https://inkfinit.pro" target="_blank" rel="noopener" class="button button-primary">
					<?php esc_html_e( 'Get Pro License', 'wtc-shipping' ); ?>
				</a>
				<a href="https://inkfinit.pro/docs" target="_blank" rel="noopener" class="button">
					<?php esc_html_e( 'Documentation', 'wtc-shipping' ); ?>
				</a>
			</p>
		</div>

	</div>
	<?php
}
