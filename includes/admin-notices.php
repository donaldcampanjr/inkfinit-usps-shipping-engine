<?php
/**
 * Admin Notices for Inkfinit Shipping Engine.
 *
 * Displays dismissible notices for:
 * - License expiring soon / expired / invalid
 * - Missing USPS API credentials
 * - System health issues
 *
 * All notices use WordPress native styles and are accessible.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register admin notices hook.
 */
add_action( 'admin_notices', 'wtcc_display_admin_notices' );

/**
 * Display all relevant admin notices.
 */
function wtcc_display_admin_notices() {
	// Only show to admins on relevant pages.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	// Check if we're on a WTC Shipping page or dashboard.
	$screen = get_current_screen();
	$is_wtc_page = $screen && strpos( $screen->id, 'wtc' ) !== false;
	$is_dashboard = $screen && 'dashboard' === $screen->id;

	// License notices (show on all admin pages).
	wtcc_maybe_show_license_notice();

	// API notices (show only on WTC pages or dashboard).
	if ( $is_wtc_page || $is_dashboard ) {
		wtcc_maybe_show_api_notice();
	}
}

/**
 * Show license-related notices.
 */
function wtcc_maybe_show_license_notice() {
	// Check if user dismissed this notice.
	$dismissed = get_user_meta( get_current_user_id(), 'wtcc_license_notice_dismissed', true );
	$dismiss_until = get_user_meta( get_current_user_id(), 'wtcc_license_notice_dismiss_until', true );

	if ( $dismissed && $dismiss_until && time() < (int) $dismiss_until ) {
		return;
	}

	$status = wtcc_get_license_status();
	$expiry = wtcc_get_license_expiry_info();

	// No license key - gentle prompt on WTC pages only.
	if ( 'none' === $status ) {
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->id, 'wtc' ) !== false ) {
			wtcc_render_notice(
				'info',
				sprintf(
					/* translators: %s: License settings URL */
					__( '<strong>Inkfinit Shipping:</strong> You\'re using the Free edition. <a href="%s">Enter a license key</a> or <a href="https://inkfinit.pro" target="_blank" rel="noopener">upgrade to Pro</a> for full features.', 'wtc-shipping' ),
					esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) )
				),
				true,
				'wtcc_license_notice'
			);
		}
		return;
	}

	// License expired.
	if ( 'expired' === $status ) {
		wtcc_render_notice(
			'error',
			sprintf(
				/* translators: %s: License renewal URL */
				__( '<strong>Inkfinit Shipping:</strong> Your license has expired. Pro features are disabled. <a href="%s" target="_blank" rel="noopener">Renew your license</a> to restore full functionality.', 'wtc-shipping' ),
				'https://inkfinit.pro/renew'
			),
			true,
			'wtcc_license_notice'
		);
		return;
	}

	// License expiring soon.
	if ( 'expiring_soon' === $status && $expiry ) {
		$days_left = $expiry['days_left'];
		wtcc_render_notice(
			'warning',
			sprintf(
				/* translators: 1: Days remaining, 2: Expiry date, 3: Renewal URL */
				__( '<strong>Inkfinit Shipping:</strong> Your license expires in %1$d days (on %2$s). <a href="%3$s" target="_blank" rel="noopener">Renew now</a> to avoid interruption.', 'wtc-shipping' ),
				$days_left,
				date_i18n( get_option( 'date_format' ), strtotime( $expiry['expires_at'] ) ),
				'https://inkfinit.pro/renew'
			),
			true,
			'wtcc_license_notice'
		);
		return;
	}

	// License invalid.
	if ( 'invalid' === $status ) {
		wtcc_render_notice(
			'error',
			sprintf(
				/* translators: %s: License settings URL */
				__( '<strong>Inkfinit Shipping:</strong> Your license key is invalid or has been revoked. <a href="%s">Check your license settings</a> or contact support.', 'wtc-shipping' ),
				esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) )
			),
			true,
			'wtcc_license_notice'
		);
	}
}

/**
 * Show USPS API credential notices.
 */
function wtcc_maybe_show_api_notice() {
	// Only show if Pro is active.
	if ( ! wtcc_is_pro() ) {
		return;
	}

	// Check if user dismissed this notice.
	$dismissed = get_user_meta( get_current_user_id(), 'wtcc_api_notice_dismissed', true );
	$dismiss_until = get_user_meta( get_current_user_id(), 'wtcc_api_notice_dismiss_until', true );

	if ( $dismissed && $dismiss_until && time() < (int) $dismiss_until ) {
		return;
	}

	$consumer_key    = get_option( 'wtcc_usps_consumer_key', '' );
	$consumer_secret = get_option( 'wtcc_usps_consumer_secret', '' );
	$origin_zip      = get_option( 'wtcc_origin_zip', '' );

	$missing = array();

	if ( empty( $consumer_key ) ) {
		$missing[] = __( 'Consumer Key', 'wtc-shipping' );
	}
	if ( empty( $consumer_secret ) ) {
		$missing[] = __( 'Consumer Secret', 'wtc-shipping' );
	}
	if ( empty( $origin_zip ) ) {
		$missing[] = __( 'Origin ZIP Code', 'wtc-shipping' );
	}

	if ( ! empty( $missing ) ) {
		wtcc_render_notice(
			'warning',
			sprintf(
				/* translators: 1: Missing fields list, 2: Settings URL */
				__( '<strong>Inkfinit Shipping:</strong> USPS API not fully configured. Missing: %1$s. <a href="%2$s">Configure now</a> to enable live shipping rates.', 'wtc-shipping' ),
				implode( ', ', $missing ),
				esc_url( admin_url( 'admin.php?page=wtc-core-shipping-usps-api' ) )
			),
			true,
			'wtcc_api_notice'
		);
	}
}

/**
 * Render an admin notice with proper accessibility.
 *
 * @param string $type        Notice type: 'info', 'success', 'warning', 'error'.
 * @param string $message     HTML message content.
 * @param bool   $dismissible Whether the notice can be dismissed.
 * @param string $notice_id   Unique ID for dismiss tracking.
 */
function wtcc_render_notice( $type, $message, $dismissible = true, $notice_id = '' ) {
	$classes = array( 'notice', "notice-{$type}" );

	if ( $dismissible ) {
		$classes[] = 'is-dismissible';
	}

	$data_attr = '';
	if ( $notice_id ) {
		$data_attr = sprintf( ' data-notice-id="%s"', esc_attr( $notice_id ) );
	}

	printf(
		'<div class="%s"%s role="alert"><p>%s</p></div>',
		esc_attr( implode( ' ', $classes ) ),
		$data_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		$message // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Contains HTML.
	);
}

/**
 * Handle AJAX dismiss of notices.
 */
add_action( 'wp_ajax_wtcc_dismiss_notice', 'wtcc_ajax_dismiss_notice' );

/**
 * AJAX handler for dismissing notices.
 */
function wtcc_ajax_dismiss_notice() {
	check_ajax_referer( 'wtcc_admin_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Permission denied' );
	}

	$notice_id = isset( $_POST['notice_id'] ) ? sanitize_key( $_POST['notice_id'] ) : '';

	if ( ! $notice_id ) {
		wp_send_json_error( 'Invalid notice ID' );
	}

	// Dismiss for 7 days (except license expired, which is 1 day).
	$dismiss_duration = 'wtcc_license_notice' === $notice_id ? DAY_IN_SECONDS : 7 * DAY_IN_SECONDS;

	update_user_meta( get_current_user_id(), $notice_id . '_dismissed', true );
	update_user_meta( get_current_user_id(), $notice_id . '_dismiss_until', time() + $dismiss_duration );

	wp_send_json_success();
}

/**
 * Enqueue admin notice dismiss script.
 */
add_action( 'admin_enqueue_scripts', 'wtcc_enqueue_notice_scripts' );

/**
 * Enqueue inline script for notice dismissal.
 */
function wtcc_enqueue_notice_scripts() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	// Add nonce for AJAX.
	wp_localize_script( 'jquery', 'wtccNotices', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'wtcc_admin_nonce' ),
	) );

	// Inline script for dismiss handling.
	$script = "
		jQuery(document).on('click', '.notice[data-notice-id] .notice-dismiss', function() {
			var noticeId = jQuery(this).closest('.notice').data('notice-id');
			if (noticeId && typeof wtccNotices !== 'undefined') {
				jQuery.post(wtccNotices.ajaxUrl, {
					action: 'wtcc_dismiss_notice',
					notice_id: noticeId,
					nonce: wtccNotices.nonce
				});
			}
		});
	";

	wp_add_inline_script( 'jquery', $script );
}
