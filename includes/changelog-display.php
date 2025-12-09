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
		update_option( 'wtcc_last_known_version', $current_version );
		return;
	}

	if ( version_compare( $stored_version, $current_version, '<' ) ) {
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
 */
function wtcc_display_update_notification() {
	$new_version = get_option( 'wtcc_show_update_notice', '' );

	if ( empty( $new_version ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'toplevel_page_wtc-core-shipping' ) {
		return;
	}

	$changelog_url = admin_url( 'admin.php?page=wtc-changelog' );
	$dismiss_url   = wp_nonce_url( add_query_arg( 'wtcc_dismiss_update_notice', '1' ), 'wtcc_dismiss_update' );
	?>
	<div class="notice notice-success">
		<p>
			<span class="dashicons dashicons-megaphone"></span>
			<strong><?php printf( esc_html__( 'Inkfinit Shipping Engine updated to version %s!', 'wtc-shipping' ), esc_html( $new_version ) ); ?></strong>
		</p>
		<p>
			<a href="<?php echo esc_url( $changelog_url ); ?>" class="button button-primary"><?php esc_html_e( "See What's New", 'wtc-shipping' ); ?></a>
			<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button"><?php esc_html_e( 'Dismiss', 'wtc-shipping' ); ?></a>
		</p>
	</div>
	<?php
}

/**
 * Handle dismissal of update notice.
 */
add_action( 'admin_init', 'wtcc_handle_update_notice_dismiss' );

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
 */
function wtcc_get_changelog_data() {
	return array(
		array(
			'version'      => '1.3.0',
			'date'         => '2025-06-15',
			'title'        => 'SaaS Enhancement Release',
			'type'         => 'major',
			'features'     => array(
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
			'fixes'        => array(),
		),
		array(
			'version'      => '1.2.0',
			'date'         => '2025-04-01',
			'title'        => 'OAuth v3 API Update',
			'type'         => 'major',
			'features'     => array(
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
			'fixes'        => array(
				__( 'Fixed PHP 8.1 deprecation warnings', 'wtc-shipping' ),
				__( 'Fixed intermittent OAuth token failures', 'wtc-shipping' ),
			),
		),
		array(
			'version'      => '1.1.0',
			'date'         => '2025-01-15',
			'title'        => 'Label Printing Release',
			'type'         => 'major',
			'features'     => array(
				__( 'USPS shipping label printing', 'wtc-shipping' ),
				__( 'Carrier pickup scheduling', 'wtc-shipping' ),
				__( 'Bulk variation dimension manager', 'wtc-shipping' ),
				__( 'Delivery estimates display', 'wtc-shipping' ),
			),
			'improvements' => array(
				__( 'Enhanced preset system', 'wtc-shipping' ),
				__( 'Improved admin UI', 'wtc-shipping' ),
			),
			'fixes'        => array(),
		),
		array(
			'version'      => '1.0.0',
			'date'         => '2024-10-01',
			'title'        => 'Initial Release',
			'type'         => 'major',
			'features'     => array(
				__( 'Real-time USPS shipping rate calculation', 'wtc-shipping' ),
				__( 'Product shipping presets', 'wtc-shipping' ),
				__( 'Rule-based rate adjustments', 'wtc-shipping' ),
				__( 'Multiple mail class support', 'wtc-shipping' ),
				__( 'Box packing algorithm', 'wtc-shipping' ),
			),
			'improvements' => array(),
			'fixes'        => array(),
		),
	);
}

/**
 * Render the changelog page content.
 */
function wtcc_render_changelog_page() {
	$changelog = wtcc_get_changelog_data();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Changelog & Release Notes', 'wtc-shipping' ); ?></h1>

		<div class="card">
			<h2><?php esc_html_e( 'Version History', 'wtc-shipping' ); ?></h2>
			<p><?php esc_html_e( 'See what has changed in each version of Inkfinit Shipping Engine.', 'wtc-shipping' ); ?></p>
		</div>

		<?php foreach ( $changelog as $release ) : ?>
			<div class="card">
				<h2>
					<?php echo esc_html( $release['title'] ); ?>
					<span class="update-plugins count-1"><span class="plugin-count">v<?php echo esc_html( $release['version'] ); ?></span></span>
				</h2>
				<p class="description"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $release['date'] ) ) ); ?></p>

				<?php if ( ! empty( $release['features'] ) ) : ?>
					<h3><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'New Features', 'wtc-shipping' ); ?></h3>
					<ul class="ul-disc">
						<?php foreach ( $release['features'] as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! empty( $release['improvements'] ) ) : ?>
					<h3><span class="dashicons dashicons-arrow-up-alt"></span> <?php esc_html_e( 'Improvements', 'wtc-shipping' ); ?></h3>
					<ul class="ul-disc">
						<?php foreach ( $release['improvements'] as $improvement ) : ?>
							<li><?php echo esc_html( $improvement ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! empty( $release['fixes'] ) ) : ?>
					<h3><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Bug Fixes', 'wtc-shipping' ); ?></h3>
					<ul class="ul-disc">
						<?php foreach ( $release['fixes'] as $fix ) : ?>
							<li><?php echo esc_html( $fix ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<p class="description">
			<?php esc_html_e( 'For the full changelog, visit', 'wtc-shipping' ); ?>
			<a href="<?php echo esc_url( WTCC_CHANGELOG_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'GitHub', 'wtc-shipping' ); ?></a>
		</p>
	</div>
	<?php
}

/**
 * Render a compact changelog widget for embedding.
 */
function wtcc_render_changelog_widget( $limit = 2 ) {
	$changelog = array_slice( wtcc_get_changelog_data(), 0, $limit );
	?>
	<div class="card">
		<h3><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Recent Updates', 'wtc-shipping' ); ?></h3>
		<?php foreach ( $changelog as $release ) : ?>
			<p>
				<strong>v<?php echo esc_html( $release['version'] ); ?></strong>
				<span class="description"><?php echo esc_html( $release['date'] ); ?></span><br>
				<?php echo esc_html( $release['title'] ); ?>
			</p>
		<?php endforeach; ?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-changelog' ) ); ?>" class="button"><?php esc_html_e( 'View Full Changelog', 'wtc-shipping' ); ?></a></p>
	</div>
	<?php
}
