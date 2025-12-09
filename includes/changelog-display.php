<?php
/**
 * In-Plugin Changelog and Release Notes Display.
 *
 * Shows version history and release notes within the admin interface.
 * Displays notification after plugin updates with what's new.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store the previously installed version for update detection.
 */
add_action( 'admin_init', 'wtcc_check_plugin_update' );

/**
 * Check if plugin was updated and show notification.
 */
function wtcc_check_plugin_update() {
	$current_version = WTCC_SHIPPING_VERSION;
	$stored_version  = get_option( 'wtcc_last_known_version', '' );

	if ( empty( $stored_version ) ) {
		// First install or upgrade from very old version.
		update_option( 'wtcc_last_known_version', $current_version );
		return;
	}

	if ( version_compare( $stored_version, $current_version, '<' ) ) {
		// Plugin was updated - flag for notification.
		update_option( 'wtcc_show_update_notice', $current_version );
		update_option( 'wtcc_last_known_version', $current_version );
	}
}

/**
 * Display update notification on admin pages.
 */
add_action( 'admin_notices', 'wtcc_display_update_notification' );

/**
 * Show "What's New" notice after plugin update.
 * Only displays on the plugin Dashboard page and requires manual dismissal.
 */
function wtcc_display_update_notification() {
	$new_version = get_option( 'wtcc_show_update_notice', '' );

	if ( empty( $new_version ) ) {
		return;
	}

	// Only show on the plugin Dashboard page (toplevel_page_wtc-core-shipping)
	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'toplevel_page_wtc-core-shipping' ) {
		return;
	}

	$changelog_url = admin_url( 'admin.php?page=wtc-changelog' );
	$dismiss_url   = wp_nonce_url( add_query_arg( 'wtcc_dismiss_update_notice', '1' ), 'wtcc_dismiss_update' );

	?>
	<div class="notice notice-success" style="padding:15px;border-left-color:#28a745;">
		<p style="font-size:14px;margin:0 0 10px;">
			<span class="dashicons dashicons-megaphone" style="color:#28a745;margin-right:8px;" aria-hidden="true"></span>
			<strong><?php printf( esc_html__( 'Inkfinit Shipping Engine updated to version %s!', 'wtc-shipping' ), esc_html( $new_version ) ); ?></strong>
		</p>
		<p style="margin:0;">
			<a href="<?php echo esc_url( $changelog_url ); ?>" class="button button-primary">
				<?php esc_html_e( "See What's New", 'wtc-shipping' ); ?>
			</a>
			<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button" style="margin-left:5px;">
				<?php esc_html_e( 'Dismiss', 'wtc-shipping' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Handle dismissal of update notice.
 */
add_action( 'admin_init', 'wtcc_handle_update_notice_dismiss' );

/**
 * Dismiss the update notice.
 */
function wtcc_handle_update_notice_dismiss() {
	if ( isset( $_GET['wtcc_dismiss_update_notice'] ) && isset( $_GET['_wpnonce'] ) ) {
		if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wtcc_dismiss_update' ) ) {
			delete_option( 'wtcc_show_update_notice' );
			wp_safe_redirect( remove_query_arg( array( 'wtcc_dismiss_update_notice', '_wpnonce' ) ) );
			exit;
		}
	}
}

/**
 * Get changelog data.
 *
 * @return array Changelog entries.
 */
function wtcc_get_changelog_data() {
	return array(
		array(
			'version'  => '1.3.0',
			'date'     => '2025-06-15',
			'title'    => 'SaaS Enhancement Release',
			'type'     => 'major',
			'features' => array(
				__( 'Added 4-tier license system (Free, Pro, Premium, Enterprise)', 'wtc-shipping' ),
				__( 'License expiry display with renewal reminders', 'wtc-shipping' ),
				__( 'Admin notices for license and API issues', 'wtc-shipping' ),
				__( 'One-click debug info export for support', 'wtc-shipping' ),
				__( 'Quick support and documentation links throughout admin', 'wtc-shipping' ),
				__( 'Bulk license key import for agencies (Premium/Enterprise)', 'wtc-shipping' ),
				__( 'White-label mode for resellers (Premium/Enterprise)', 'wtc-shipping' ),
				__( 'In-plugin changelog display', 'wtc-shipping' ),
				__( 'Automated self-test health checks (Pro+)', 'wtc-shipping' ),
			),
			'improvements' => array(
				__( 'Enhanced tier comparison table on license page', 'wtc-shipping' ),
				__( 'Improved accessibility across all admin pages', 'wtc-shipping' ),
				__( 'Better error messages for API failures', 'wtc-shipping' ),
			),
			'fixes' => array(),
		),
		array(
			'version'  => '1.2.0',
			'date'     => '2025-04-01',
			'title'    => 'OAuth v3 API Update',
			'type'     => 'major',
			'features' => array(
				__( 'USPS OAuth v3 API integration', 'wtc-shipping' ),
				__( 'Automatic token caching and refresh', 'wtc-shipping' ),
				__( 'Split shipments for oversized orders', 'wtc-shipping' ),
				__( 'Packing slip generation', 'wtc-shipping' ),
				__( 'Security hardening module', 'wtc-shipping' ),
			),
			'improvements' => array(
				__( 'Faster rate calculations with caching', 'wtc-shipping' ),
				__( 'Better handling of dimensional weight', 'wtc-shipping' ),
			),
			'fixes' => array(
				__( 'Fixed PHP 8.1 deprecation warnings', 'wtc-shipping' ),
				__( 'Fixed intermittent OAuth token failures', 'wtc-shipping' ),
			),
		),
		array(
			'version'  => '1.1.0',
			'date'     => '2025-01-15',
			'title'    => 'Label Printing Release',
			'type'     => 'major',
			'features' => array(
				__( 'USPS shipping label printing', 'wtc-shipping' ),
				__( 'Carrier pickup scheduling', 'wtc-shipping' ),
				__( 'Bulk variation dimension manager', 'wtc-shipping' ),
				__( 'Delivery estimates display', 'wtc-shipping' ),
			),
			'improvements' => array(
				__( 'Enhanced preset system', 'wtc-shipping' ),
				__( 'Improved admin UI', 'wtc-shipping' ),
			),
			'fixes' => array(),
		),
		array(
			'version'  => '1.0.0',
			'date'     => '2024-10-01',
			'title'    => 'Initial Release',
			'type'     => 'major',
			'features' => array(
				__( 'Real-time USPS shipping rate calculation', 'wtc-shipping' ),
				__( 'Product shipping presets', 'wtc-shipping' ),
				__( 'Rule-based rate adjustments', 'wtc-shipping' ),
				__( 'Multiple mail class support', 'wtc-shipping' ),
				__( 'Box packing algorithm', 'wtc-shipping' ),
			),
			'improvements' => array(),
			'fixes' => array(),
		),
	);
}

/**
 * Render the changelog page content.
 */
function wtcc_render_changelog_page() {
	$changelog = wtcc_get_changelog_data();

	?>
	<div class="wrap wtcc-changelog">
		<h1><?php esc_html_e( 'Changelog & Release Notes', 'wtc-shipping' ); ?></h1>

		<div class="wtcc-changelog-intro" style="background:#fff;padding:20px;border:1px solid #c3c4c7;border-radius:4px;margin:20px 0;">
			<h2 style="margin:0 0 10px;">
				<?php esc_html_e( 'Version History', 'wtc-shipping' ); ?>
			</h2>
			<p style="margin:0;color:#666;">
				<?php esc_html_e( 'See what has changed in each version of Inkfinit Shipping Engine.', 'wtc-shipping' ); ?>
			</p>
		</div>

		<?php foreach ( $changelog as $release ) : ?>
			<div class="wtcc-release" style="background:#fff;padding:20px;border:1px solid #c3c4c7;border-radius:4px;margin:20px 0;">
				<div style="display:flex;align-items:center;gap:15px;margin-bottom:15px;flex-wrap:wrap;">
					<h2 style="margin:0;font-size:1.3em;">
						<?php echo esc_html( $release['title'] ); ?>
					</h2>
					<span class="wtcc-version-badge" style="background:#2271b1;color:#fff;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;">
						v<?php echo esc_html( $release['version'] ); ?>
					</span>
					<span style="color:#666;font-size:13px;">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $release['date'] ) ) ); ?>
					</span>
				</div>

				<?php if ( ! empty( $release['features'] ) ) : ?>
					<div class="wtcc-release-section" style="margin:15px 0;">
						<h3 style="font-size:14px;margin:0 0 10px;color:#28a745;">
							<span class="dashicons dashicons-star-filled" style="margin-right:5px;" aria-hidden="true"></span>
							<?php esc_html_e( 'New Features', 'wtc-shipping' ); ?>
						</h3>
						<ul style="margin:0 0 0 30px;color:#333;">
							<?php foreach ( $release['features'] as $feature ) : ?>
								<li><?php echo esc_html( $feature ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $release['improvements'] ) ) : ?>
					<div class="wtcc-release-section" style="margin:15px 0;">
						<h3 style="font-size:14px;margin:0 0 10px;color:#2271b1;">
							<span class="dashicons dashicons-arrow-up-alt" style="margin-right:5px;" aria-hidden="true"></span>
							<?php esc_html_e( 'Improvements', 'wtc-shipping' ); ?>
						</h3>
						<ul style="margin:0 0 0 30px;color:#333;">
							<?php foreach ( $release['improvements'] as $improvement ) : ?>
								<li><?php echo esc_html( $improvement ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $release['fixes'] ) ) : ?>
					<div class="wtcc-release-section" style="margin:15px 0;">
						<h3 style="font-size:14px;margin:0 0 10px;color:#d63638;">
							<span class="dashicons dashicons-yes" style="margin-right:5px;" aria-hidden="true"></span>
							<?php esc_html_e( 'Bug Fixes', 'wtc-shipping' ); ?>
						</h3>
						<ul style="margin:0 0 0 30px;color:#333;">
							<?php foreach ( $release['fixes'] as $fix ) : ?>
								<li><?php echo esc_html( $fix ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="wtcc-changelog-footer" style="text-align:center;padding:20px;color:#666;">
			<p>
				<?php esc_html_e( 'For the full changelog, visit', 'wtc-shipping' ); ?>
				<a href="<?php echo esc_url( WTCC_CHANGELOG_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'inkfinit.com/changelog', 'wtc-shipping' ); ?>
				</a>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Render a compact changelog widget for embedding.
 *
 * @param int $limit Number of releases to show.
 */
function wtcc_render_changelog_widget( $limit = 2 ) {
	$changelog = wtcc_get_changelog_data();
	$changelog = array_slice( $changelog, 0, $limit );

	?>
	<div class="wtcc-changelog-widget" style="background:#fff;padding:15px;border:1px solid #c3c4c7;border-radius:4px;">
		<h3 style="margin:0 0 15px;display:flex;align-items:center;">
			<span class="dashicons dashicons-list-view" style="margin-right:10px;color:#2271b1;" aria-hidden="true"></span>
			<?php esc_html_e( 'Recent Updates', 'wtc-shipping' ); ?>
		</h3>

		<?php foreach ( $changelog as $release ) : ?>
			<div style="padding:10px 0;border-bottom:1px solid #eee;">
				<div style="display:flex;align-items:center;gap:10px;">
					<strong>v<?php echo esc_html( $release['version'] ); ?></strong>
					<span style="color:#666;font-size:12px;"><?php echo esc_html( $release['date'] ); ?></span>
				</div>
				<p style="margin:5px 0 0;font-size:13px;color:#333;">
					<?php echo esc_html( $release['title'] ); ?>
				</p>
			</div>
		<?php endforeach; ?>

		<p style="margin:15px 0 0;text-align:center;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-changelog' ) ); ?>" class="button">
				<?php esc_html_e( 'View Full Changelog', 'wtc-shipping' ); ?>
			</a>
		</p>
	</div>
	<?php
}
