<?php
/**
 * Inkfinit Shipping - License Settings Page
 *
 * Features:
 * - License key entry and validation
 * - Expiry date and days-left display
 * - Tier information (Free/Pro/Premium/Enterprise)
 * - Test key generation for development
 * - Clear, accessible UI using WordPress native styles
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
			// Clear cached license data to force revalidation.
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
			// Toggle developer mode (test keys enabled).
			$current = get_option( 'wtcc_dev_mode_enabled', false );
			update_option( 'wtcc_dev_mode_enabled', ! $current );
			if ( ! $current ) {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Developer Mode ENABLED', 'wtc-shipping' ) . '</strong> - Test keys now work for all admins.</p></div>';
			} else {
				echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Developer Mode disabled.', 'wtc-shipping' ) . '</p></div>';
			}
		}

		// Refresh edition after changes.
		$edition = wtcc_get_edition();
	}

	$license_status = wtcc_get_license_status();
	$license_data   = wtcc_get_license_data();
	$expiry_info    = wtcc_get_license_expiry_info();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Inkfinit Shipping - License Settings', 'wtc-shipping' ); ?></h1>

		<!-- Current Edition Card -->
		<div class="card" style="max-width:800px;margin-top:20px;">
			<h2 class="title"><?php esc_html_e( 'Current Edition', 'wtc-shipping' ); ?></h2>
			<?php wtcc_render_edition_status( $edition, $license_status, $expiry_info ); ?>
		</div>

		<!-- License Key Management Card -->
		<div class="card" style="max-width:800px;margin-top:20px;">
			<h2 class="title"><?php esc_html_e( 'License Key', 'wtc-shipping' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'wtcc_license_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="wtcc_license_key"><?php esc_html_e( 'License Key', 'wtc-shipping' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								name="wtcc_license_key"
								id="wtcc_license_key"
								value="<?php echo esc_attr( $license_key ); ?>"
								class="large-text code"
								placeholder="<?php esc_attr_e( 'e.g., INK-PRO-XXXXXXXX', 'wtc-shipping' ); ?>"
								autocomplete="off"
								aria-describedby="license-key-description"
							/>
							<p class="description" id="license-key-description">
								<?php
								printf(
									/* translators: %s: URL to pricing page */
									esc_html__( 'Enter your license key from %s or generate a test key for development.', 'wtc-shipping' ),
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
							<?php wtcc_render_license_status_badge( $license_status ); ?>
						</td>
					</tr>

					<?php if ( $expiry_info ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Expires', 'wtc-shipping' ); ?></th>
						<td>
							<?php wtcc_render_expiry_display( $expiry_info ); ?>
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
					<?php if ( function_exists( 'wtcc_can_use_test_keys' ) && wtcc_can_use_test_keys() ) : ?>
					<button type="submit" name="wtcc_license_action" value="generate_test_key" class="button">
						<?php esc_html_e( 'Generate Test Key', 'wtc-shipping' ); ?>
					</button>
					<?php endif; ?>
					<?php if ( ! empty( $license_key ) ) : ?>
					<button type="submit" name="wtcc_license_action" value="clear_key" class="button" onclick="return confirm('<?php esc_attr_e( 'Clear license key and revert to Free mode?', 'wtc-shipping' ); ?>');">
						<?php esc_html_e( 'Clear License', 'wtc-shipping' ); ?>
					</button>
					<?php endif; ?>
				</p>
			</form>
		</div>

		<!-- Developer Mode Toggle (Admin Only) -->
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<?php $dev_mode_enabled = get_option( 'wtcc_dev_mode_enabled', false ); ?>
		<div class="card" style="max-width:800px;margin-top:20px;<?php echo $dev_mode_enabled ? 'border-left:4px solid #d63638;' : ''; ?>">
			<h2 class="title">
				<span class="dashicons dashicons-admin-tools"></span>
				<?php esc_html_e( 'Developer Mode', 'wtc-shipping' ); ?>
				<?php if ( $dev_mode_enabled ) : ?>
					<span style="background:#d63638;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;margin-left:10px;">ON</span>
				<?php endif; ?>
			</h2>
			<p style="color:#666;">
				<?php esc_html_e( 'When enabled, test license keys (INKDEV-*) work for all administrators. Use this for development/testing only.', 'wtc-shipping' ); ?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'wtcc_license_nonce' ); ?>
				<button type="submit" name="wtcc_license_action" value="toggle_dev_mode" class="button <?php echo $dev_mode_enabled ? 'button-secondary' : 'button-primary'; ?>">
					<?php if ( $dev_mode_enabled ) : ?>
						<span class="dashicons dashicons-no" style="vertical-align:middle;"></span>
						<?php esc_html_e( 'Disable Developer Mode', 'wtc-shipping' ); ?>
					<?php else : ?>
						<span class="dashicons dashicons-yes" style="vertical-align:middle;"></span>
						<?php esc_html_e( 'Enable Developer Mode', 'wtc-shipping' ); ?>
					<?php endif; ?>
				</button>
			</form>
			<?php if ( $dev_mode_enabled ) : ?>
			<p style="margin-top:15px;padding:10px;background:#fff3cd;border-radius:4px;">
				<strong>⚠️ <?php esc_html_e( 'Developer Mode is ON', 'wtc-shipping' ); ?></strong><br>
				<?php esc_html_e( 'Test keys are active. Remember to disable before going live!', 'wtc-shipping' ); ?>
			</p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<!-- Tier Comparison Card -->
		<div class="card" style="max-width:800px;margin-top:20px;">
			<h2 class="title"><?php esc_html_e( 'Available Tiers', 'wtc-shipping' ); ?></h2>
			<?php wtcc_render_tier_comparison(); ?>
		</div>

		<!-- Quick Links Card -->
		<div class="card" style="max-width:800px;margin-top:20px;">
			<h2 class="title"><?php esc_html_e( 'Quick Links', 'wtc-shipping' ); ?></h2>
			<p>
				<a href="https://inkfinit.pro" target="_blank" rel="noopener" class="button">
					<?php esc_html_e( 'Get Pro License', 'wtc-shipping' ); ?>
				</a>
				<a href="https://inkfinit.pro/docs" target="_blank" rel="noopener" class="button">
					<?php esc_html_e( 'Documentation', 'wtc-shipping' ); ?>
				</a>
				<a href="mailto:support@inkfinit.pro" class="button">
					<?php esc_html_e( 'Contact Support', 'wtc-shipping' ); ?>
				</a>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Render the edition status display.
 *
 * @param string     $edition        Current edition.
 * @param string     $license_status License status.
 * @param array|null $expiry_info    Expiry info array.
 */
function wtcc_render_edition_status( $edition, $license_status, $expiry_info ) {
	$edition_labels = array(
		'free'       => __( 'Free', 'wtc-shipping' ),
		'pro'        => __( 'Pro', 'wtc-shipping' ),
		'premium'    => __( 'Premium', 'wtc-shipping' ),
		'enterprise' => __( 'Enterprise', 'wtc-shipping' ),
	);

	$edition_colors = array(
		'free'       => '#666',
		'pro'        => '#28a745',
		'premium'    => '#0073aa',
		'enterprise' => '#5c6bc0',
	);

	$edition_label = $edition_labels[ $edition ] ?? ucfirst( $edition );
	$edition_color = $edition_colors[ $edition ] ?? '#666';

	$icon = 'free' === $edition ? '⊘' : '✓';
	?>
	<div style="display:flex;align-items:center;gap:12px;padding:15px 0;">
		<span style="font-size:32px;color:<?php echo esc_attr( $edition_color ); ?>;" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
		<div>
			<strong style="font-size:18px;color:<?php echo esc_attr( $edition_color ); ?>;">
				<?php echo esc_html( $edition_label ); ?> <?php esc_html_e( 'Edition', 'wtc-shipping' ); ?>
			</strong>
			<p style="margin:5px 0 0;color:#666;">
				<?php
				if ( 'free' === $edition ) {
					esc_html_e( 'Free edition. Upgrade to Pro for full features.', 'wtc-shipping' );
				} elseif ( 'enterprise' === $edition ) {
					esc_html_e( 'All features unlocked including white-label and priority support.', 'wtc-shipping' );
				} elseif ( 'premium' === $edition ) {
					esc_html_e( 'All features unlocked including white-label and bulk tools.', 'wtc-shipping' );
				} else {
					esc_html_e( 'All Pro features unlocked. Full USPS shipping engine active.', 'wtc-shipping' );
				}
				?>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Render license status badge.
 *
 * @param string $status License status.
 */
function wtcc_render_license_status_badge( $status ) {
	$badges = array(
		'none'          => array( 'label' => __( 'No License', 'wtc-shipping' ), 'color' => '#999', 'icon' => '—' ),
		'valid'         => array( 'label' => __( 'Valid', 'wtc-shipping' ), 'color' => '#28a745', 'icon' => '✓' ),
		'invalid'       => array( 'label' => __( 'Invalid', 'wtc-shipping' ), 'color' => '#d32f2f', 'icon' => '✗' ),
		'expired'       => array( 'label' => __( 'Expired', 'wtc-shipping' ), 'color' => '#d32f2f', 'icon' => '✗' ),
		'expiring_soon' => array( 'label' => __( 'Expiring Soon', 'wtc-shipping' ), 'color' => '#f0ad4e', 'icon' => '⚠' ),
		'unknown'       => array( 'label' => __( 'Unknown', 'wtc-shipping' ), 'color' => '#f0ad4e', 'icon' => '?' ),
	);

	$badge = $badges[ $status ] ?? $badges['unknown'];
	?>
	<span style="color:<?php echo esc_attr( $badge['color'] ); ?>;font-weight:600;" role="status">
		<?php echo esc_html( $badge['icon'] . ' ' . $badge['label'] ); ?>
	</span>
	<?php if ( 'unknown' === $status ) : ?>
		<span class="description" style="display:block;margin-top:5px;">
			<?php esc_html_e( 'Could not reach license server. Pro features remain active.', 'wtc-shipping' ); ?>
		</span>
	<?php endif; ?>
	<?php
}

/**
 * Render expiry date display.
 *
 * @param array $expiry_info Expiry info from wtcc_get_license_expiry_info().
 */
function wtcc_render_expiry_display( $expiry_info ) {
	$date_formatted = date_i18n( get_option( 'date_format' ), strtotime( $expiry_info['expires_at'] ) );
	$days_left      = $expiry_info['days_left'];
	$is_expired     = $expiry_info['is_expired'];

	if ( $is_expired ) {
		$color = '#d32f2f';
		$text  = __( 'Expired', 'wtc-shipping' );
	} elseif ( $days_left <= 7 ) {
		$color = '#d32f2f';
		/* translators: %d: Number of days */
		$text = sprintf( _n( '%d day left', '%d days left', $days_left, 'wtc-shipping' ), $days_left );
	} elseif ( $days_left <= 30 ) {
		$color = '#f0ad4e';
		/* translators: %d: Number of days */
		$text = sprintf( _n( '%d day left', '%d days left', $days_left, 'wtc-shipping' ), $days_left );
	} else {
		$color = '#28a745';
		/* translators: %d: Number of days */
		$text = sprintf( _n( '%d day left', '%d days left', $days_left, 'wtc-shipping' ), $days_left );
	}
	?>
	<span style="color:<?php echo esc_attr( $color ); ?>;font-weight:600;">
		<?php echo esc_html( $date_formatted ); ?>
	</span>
	<span style="color:<?php echo esc_attr( $color ); ?>;margin-left:8px;">
		(<?php echo esc_html( $text ); ?>)
	</span>
	<?php if ( $days_left <= 30 && ! $is_expired ) : ?>
		<a href="https://inkfinit.pro/renew" target="_blank" rel="noopener" class="button button-small" style="margin-left:10px;">
			<?php esc_html_e( 'Renew Now', 'wtc-shipping' ); ?>
		</a>
	<?php endif; ?>
	<?php
}

/**
 * Render tier comparison table.
 */
function wtcc_render_tier_comparison() {
	?>
	<table class="widefat striped" role="presentation">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Feature', 'wtc-shipping' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Free', 'wtc-shipping' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Pro ($149/yr)', 'wtc-shipping' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Enterprise', 'wtc-shipping' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Live USPS Rates at Checkout', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#28a745;">✓</span></td>
				<td><span style="color:#28a745;">✓</span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Label Printing', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#28a745;">✓</span></td>
				<td><span style="color:#28a745;">✓</span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Order Tracking', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#28a745;">✓</span></td>
				<td><span style="color:#28a745;">✓</span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Shipping Presets', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#28a745;">✓</span></td>
				<td><span style="color:#28a745;">✓</span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Bulk Variation Manager', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#28a745;">✓</span></td>
				<td><span style="color:#28a745;">✓</span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Diagnostics & Self-Test', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#28a745;">✓</span></td>
				<td><span style="color:#28a745;">✓</span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'White-Label Mode', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#28a745;">✓</span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Bulk License Import', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#999;">—</span></td>
				<td><span style="color:#28a745;">✓</span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Priority Support', 'wtc-shipping' ); ?></td>
				<td><span style="color:#999;">Community</span></td>
				<td><span style="color:#28a745;">Email (24-48h)</span></td>
				<td><span style="color:#28a745;">Phone + Email + SLA</span></td>
			</tr>
		</tbody>
	</table>
	<p style="margin-top:15px;">
		<a href="https://inkfinit.pro" target="_blank" rel="noopener" class="button button-primary">
			<?php esc_html_e( 'Compare Plans & Pricing', 'wtc-shipping' ); ?>
		</a>
	</p>
	<?php
}
