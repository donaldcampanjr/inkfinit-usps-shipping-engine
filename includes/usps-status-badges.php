<?php
/**
 * USPS Status Badges & Customer Notifications
 * Shows real-time USPS API status to admins and customers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get USPS status badge HTML for admin pages
 * 
 * @return string HTML badge showing API connection status
 */
function wtcc_get_usps_status_badge() {
	$consumer_key = get_option( 'wtcc_usps_consumer_key', '' );
	$consumer_secret = get_option( 'wtcc_usps_consumer_secret', '' );
	$last_success = get_option( 'wtcc_last_usps_success', 0 );
	$last_failure = get_option( 'wtcc_last_usps_failure', 0 );
	
	// Not configured
	if ( empty( $consumer_key ) || empty( $consumer_secret ) ) {
		return '<span class="wtcc-usps-status-badge not-configured">Not Configured</span>';
	}
	
	$now = time();
	$success_age = $last_success > 0 ? $now - $last_success : PHP_INT_MAX;
	$failure_age = $last_failure > 0 ? $now - $last_failure : PHP_INT_MAX;
	
	// Connected - success within last 10 minutes and more recent than failure
	if ( $success_age < 600 && $success_age < $failure_age ) {
		return '<span class="wtcc-usps-status-badge connected">Connected</span>';
	}
	
	// Recent failure
	if ( $failure_age < $success_age ) {
		return '<span class="wtcc-usps-status-badge error">Connection Error</span>';
	}
	
	// Stale - no recent check
	return '<span class="wtcc-usps-status-badge unknown">Unknown</span>';
}

/**
 * Display USPS badge on checkout page for customers
 * Shows real-time rate accuracy messaging with red/white/blue USPS theme
 */
add_action( 'woocommerce_review_order_after_shipping', 'wtcc_checkout_usps_badge' );
function wtcc_checkout_usps_badge() {
	$consumer_key = get_option( 'wtcc_usps_consumer_key', '' );
	$consumer_secret = get_option( 'wtcc_usps_consumer_secret', '' );
	$last_success = get_option( 'wtcc_last_usps_success', 0 );
	$last_failure = get_option( 'wtcc_last_usps_failure', 0 );
	
	// Get logo URL
	$logo_url = WTCC_SHIPPING_PLUGIN_URL . 'assets/images/wtc-logo.svg';
	$logo_png = WTCC_SHIPPING_PLUGIN_URL . 'assets/images/wtc-logo.png';
	
	// Only show branded message if USPS is configured
	if ( empty( $consumer_key ) || empty( $consumer_secret ) ) {
		return;
	}
	
	$now = time();
	$success_age = $now - $last_success;
	$failure_age = $now - $last_failure;
	
	// Show professional badge if API is working (success within last 5 minutes)
	if ( $success_age < 300 && ( $last_failure === 0 || $success_age < $failure_age ) ) {
		?>
		<tr class="wtc-shipping-accuracy-notice">
			<td colspan="2">
				<div class="notice-inner">
					<!-- Red/White/Blue accent bar -->
					<div class="accent-bar"></div>
					
					<div class="notice-content">
						<!-- Logo -->
						<?php if ( file_exists( WTCC_SHIPPING_PLUGIN_DIR . 'assets/images/wtc-logo.svg' ) || file_exists( WTCC_SHIPPING_PLUGIN_DIR . 'assets/images/wtc-logo.png' ) ) : ?>
						<div class="logo-container">
							<img src="<?php echo esc_url( file_exists( WTCC_SHIPPING_PLUGIN_DIR . 'assets/images/wtc-logo.svg' ) ? $logo_url : $logo_png ); ?>" 
								 alt="Inkfinit Shipping" 
								 class="logo"
								 onerror="this.onerror=null; this.src='<?php echo esc_url( $logo_png ); ?>';">
						</div>
						<?php endif; ?>
						
						<!-- Content -->
						<div class="text-content">
							<div class="api-status">
								<span class="api-status-badge">
									<span class="ok">[OK]</span> Official USPS API
								</span>
							</div>
							<p class="notice-text">
								<strong>Real-time rates for your best price.</strong><br>
								<span class="subtext">Exact USPS pricing — domestic &amp; international. No estimates, no overpaying.</span>
							</p>
						</div>
						
						<!-- Live indicator -->
						<div class="live-indicator">
							<div class="live-indicator-inner">
								<span class="live-dot"></span>
								<span class="live-text">Live</span>
							</div>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}
	// Show fallback notice if API failed
	elseif ( $failure_age < 300 ) {
		?>
		<tr class="wtc-shipping-fallback-notice">
			<td colspan="2">
				<div class="notice-inner">
					<div class="notice-content">
						<span class="icon"></span>
						<p class="notice-text">
							<strong>Using verified standard rates</strong> — Real-time USPS rates will resume shortly.
						</p>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}
}

/**
 * Add USPS status to shipping method label (admin only)
 */
add_filter( 'woocommerce_cart_shipping_method_full_label', 'wtcc_add_status_to_shipping_label', 10, 2 );
function wtcc_add_status_to_shipping_label( $label, $method ) {
	// Only show to admins
	if ( ! current_user_can( 'manage_options' ) ) {
		return $label;
	}
	
	// Check if this is one of our methods
	$our_methods = array( 'wtc_ground', 'wtc_priority', 'wtc_express' );
	if ( ! in_array( $method->get_method_id(), $our_methods, true ) ) {
		return $label;
	}
	
	// Check USPS status
	$last_success = get_option( 'wtcc_last_usps_success', 0 );
	$success_age = time() - $last_success;
	
	if ( $success_age < 300 ) {
		$label .= ' <span class="wtcc-status-label live">USPS LIVE</span>';
	} else {
		$label .= ' <span class="wtcc-status-label estimate">ESTIMATE</span>';
	}
	
	return $label;
}

/**
 * Admin notice if USPS API is failing
 */
add_action( 'admin_notices', 'wtcc_usps_failure_notice' );
function wtcc_usps_failure_notice() {
	// Only on relevant admin pages
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'woocommerce_page_wtc-core-shipping-presets', 'woocommerce_page_wtc-core-shipping-diagnostics', 'woocommerce_page_wtc-core-shipping-usps-api' ), true ) ) {
		return;
	}
	
	$last_failure = get_option( 'wtcc_last_usps_failure', 0 );
	$last_success = get_option( 'wtcc_last_usps_success', 0 );
	
	// Show notice if last failure is more recent than last success
	if ( $last_failure > 0 && $last_failure > $last_success ) {
		$failure_age = human_time_diff( $last_failure, time() );
		echo '<div class="notice notice-error">';
		echo '<p><strong>[!] USPS API Issue:</strong> Last API call failed ' . esc_html( $failure_age ) . ' ago. Using fallback rates. ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=wtc-core-shipping-diagnostics' ) ) . '">View Diagnostics</a></p>';
		echo '</div>';
	}
}

/**
 * Enqueue styles for status badges
 */
add_action( 'wp_enqueue_scripts', 'wtcc_enqueue_status_badge_styles' );
function wtcc_enqueue_status_badge_styles() {
	if ( is_checkout() ) {
		wp_enqueue_style(
			'wtcc-usps-status-badges',
			WTCC_SHIPPING_PLUGIN_URL . 'assets/usps-status-badges.css',
			array(),
			WTCC_SHIPPING_VERSION
		);
	}
}

add_action( 'admin_enqueue_scripts', 'wtcc_enqueue_admin_status_badge_styles' );
function wtcc_enqueue_admin_status_badge_styles( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'woocommerce_page_wtc-core-shipping-presets', 'woocommerce_page_wtc-core-shipping-diagnostics', 'woocommerce_page_wtc-core-shipping-usps-api' ), true ) ) {
		return;
	}
	wp_enqueue_style(
		'wtcc-usps-status-badges',
		WTCC_SHIPPING_PLUGIN_URL . 'assets/usps-status-badges.css',
		array(),
		WTCC_SHIPPING_VERSION
	);
}
