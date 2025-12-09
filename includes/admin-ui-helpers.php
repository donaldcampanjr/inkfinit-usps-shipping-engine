<?php
/**
 * Admin UI Helpers
 * This file contains helper functions for rendering consistent UI elements across the plugin's admin pages.
 * It promotes the use of native WordPress UI components and provides utility functions for icons and headings.
 *
 * @package WTC_Shipping_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the admin helper stylesheet.
 *
 * Note: admin-helpers.css removed - file doesn't exist
 * Using WordPress native admin styles instead
 */
// Removed CSS enqueue for non-existent file

/**
 * Gets a relevant Dashicon class based on keywords in a title.
 *
 * Provides a simple way to apply consistent iconography throughout the plugin's UI
 * without needing to manually specify an icon for every element.
 *
 * @param string $title The title to search for keywords.
 * @return string The Dashicon class name.
 */
function wtcc_get_section_icon( $title ) {
	$icons = array(
		'box'        => 'dashicons-archive',
		'package'    => 'dashicons-archive',
		'inventory'  => 'dashicons-archive',
		'shipping'   => 'dashicons-car',
		'delivery'   => 'dashicons-car',
		'price'      => 'dashicons-money-alt',
		'cost'       => 'dashicons-money-alt',
		'rate'       => 'dashicons-money-alt',
		'security'   => 'dashicons-lock',
		'secure'     => 'dashicons-lock',
		'settings'   => 'dashicons-admin-generic',
		'config'     => 'dashicons-admin-generic',
		'label'      => 'dashicons-tag',
		'tag'        => 'dashicons-tag',
		'location'   => 'dashicons-location',
		'address'    => 'dashicons-location',
		'tracking'   => 'dashicons-location-alt',
		'search'     => 'dashicons-search',
		'find'       => 'dashicons-search',
		'stats'      => 'dashicons-chart-bar',
		'analytics'  => 'dashicons-chart-bar',
		'info'       => 'dashicons-info',
		'help'       => 'dashicons-editor-help',
		'guide'      => 'dashicons-book',
		'resource'   => 'dashicons-sos',
		'product'    => 'dashicons-products',
		'order'      => 'dashicons-clipboard',
		'api'        => 'dashicons-rest-api',
		'usps'       => 'dashicons-email-alt',
		'mail'       => 'dashicons-email-alt',
		'pickup'     => 'dashicons-calendar-alt',
		'schedule'   => 'dashicons-calendar-alt',
		'print'      => 'dashicons-printer',
		'weight'     => 'dashicons-image-crop',
		'dimension'  => 'dashicons-image-crop',
		'flat'       => 'dashicons-migrate',
		'zone'       => 'dashicons-admin-site',
		'health'     => 'dashicons-heart',
		'status'     => 'dashicons-yes-alt',
		'warning'    => 'dashicons-warning',
		'error'      => 'dashicons-dismiss',
		'success'    => 'dashicons-yes',
		'dashboard'  => 'dashicons-dashboard',
		'feature'    => 'dashicons-admin-tools',
		'tool'       => 'dashicons-admin-tools',
		'preset'     => 'dashicons-screenoptions',
		'rule'       => 'dashicons-filter',
		'quick'      => 'dashicons-performance',
		'action'     => 'dashicons-performance',
		'recent'     => 'dashicons-clock',
		'external'   => 'dashicons-external',
	);

	$title_lower = strtolower( $title );
	foreach ( $icons as $keyword => $icon ) {
		if ( strpos( $title_lower, $keyword ) !== false ) {
			return $icon;
		}
	}

	return 'dashicons-admin-post'; // Default icon
}

/**
 * Generates a formatted heading for a postbox handle (h2).
 *
 * This function standardizes the appearance of postbox titles by automatically
 * adding a relevant Dashicon before the title text. It uses wtcc_get_section_icon
 * to determine the best icon based on the title's content.
 *
 * The output is escaped and ready for display.
 *
 * @param string $title The raw title text.
 * @return string The formatted HTML for the heading.
 */
function wtcc_section_heading( $title ) {
	$icon_class = wtcc_get_section_icon( $title );
	return sprintf(
		'<span class="dashicons %s"></span> %s',
		esc_attr( $icon_class ),
		esc_html( $title )
	);
}

/**
 * Generates a standard admin page header with optional action button.
 *
 * @param string $title       The title to display in the h1 tag.
 * @param string $button_text Optional button text (e.g., 'Add New').
 * @param string $button_url  Optional button URL.
 */
function wtcc_admin_header( $title, $button_text = '', $button_url = '' ) {
	$icon_class = wtcc_get_section_icon( $title );
	echo '<h1><span class="dashicons ' . esc_attr( $icon_class ) . '"></span> ' . esc_html( $title );
	if ( ! empty( $button_text ) && ! empty( $button_url ) ) {
		echo ' <a href="' . esc_url( $button_url ) . '" class="page-title-action">' . esc_html( $button_text ) . '</a>';
	}
	echo '</h1>';
}

/**
 * Check if a feature requires Pro+ and render upgrade notice if on Free tier.
 *
 * Call this at the top of any Pro+ feature page. Returns true if access is blocked.
 *
 * @param string $feature_name Human-readable feature name.
 * @param string $required_tier 'pro', 'premium', or 'enterprise'.
 * @return bool True if access blocked (Free tier), false if access allowed.
 */
function wtcc_require_license_tier( $feature_name, $required_tier = 'pro' ) {
	$edition = wtcc_get_edition();
	
	$tier_order = array( 'free' => 0, 'pro' => 1, 'premium' => 2, 'enterprise' => 3 );
	$current_level  = $tier_order[ $edition ] ?? 0;
	$required_level = $tier_order[ $required_tier ] ?? 1;
	
	// Access granted.
	if ( $current_level >= $required_level ) {
		return false;
	}
	
	// Access denied - render upgrade notice.
	$tier_labels = array(
		'pro'        => 'Pro',
		'premium'    => 'Premium',
		'enterprise' => 'Enterprise',
	);
	$tier_label = $tier_labels[ $required_tier ] ?? 'Pro';
	
	?>
	<div class="wrap">
		<h1><?php echo esc_html( $feature_name ); ?></h1>
		
		<div class="notice notice-warning" style="padding: 20px; margin-top: 20px;">
			<h2 style="margin-top: 0;">
				<span class="dashicons dashicons-lock" style="color: #d63638;"></span>
				<?php esc_html_e( 'License Required', 'wtc-shipping' ); ?>
			</h2>
			<p style="font-size: 14px;">
				<?php
				printf(
					/* translators: 1: feature name, 2: tier name */
					esc_html__( '%1$s requires an active %2$s license or higher.', 'wtc-shipping' ),
					'<strong>' . esc_html( $feature_name ) . '</strong>',
					'<strong>' . esc_html( $tier_label ) . '</strong>'
				);
				?>
			</p>
			<p style="font-size: 14px;">
				<?php esc_html_e( 'You are currently on the Free tier which includes:', 'wtc-shipping' ); ?>
			</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Basic preset configuration', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Plugin exploration and configuration', 'wtc-shipping' ); ?></li>
			</ul>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) ); ?>" class="button button-primary button-large">
					<?php esc_html_e( 'Enter License Key', 'wtc-shipping' ); ?>
				</a>
				<a href="https://inkfinit.pro/pricing" target="_blank" rel="noopener" class="button button-secondary button-large" style="margin-left: 10px;">
					<?php esc_html_e( 'View Pricing', 'wtc-shipping' ); ?>
				</a>
			</p>
		</div>
		
		<!-- Blurred preview of the feature -->
		<div style="margin-top: 30px; filter: blur(3px); opacity: 0.5; pointer-events: none; user-select: none;">
			<div class="card" style="max-width: 800px;">
				<h2><?php echo esc_html( $feature_name ); ?></h2>
				<p><?php esc_html_e( 'This feature is available with an active license. Upgrade to unlock full functionality.', 'wtc-shipping' ); ?></p>
				<table class="form-table">
					<tr><th><?php esc_html_e( 'Setting 1', 'wtc-shipping' ); ?></th><td><input type="text" disabled class="regular-text"></td></tr>
					<tr><th><?php esc_html_e( 'Setting 2', 'wtc-shipping' ); ?></th><td><input type="text" disabled class="regular-text"></td></tr>
					<tr><th><?php esc_html_e( 'Setting 3', 'wtc-shipping' ); ?></th><td><select disabled><option><?php esc_html_e( 'Select option...', 'wtc-shipping' ); ?></option></select></td></tr>
				</table>
			</div>
		</div>
	</div>
	<?php
	
	return true; // Access blocked.
}

/**
 * Render a "Pro Required" inline badge for features in mixed pages.
 *
 * @param string $required_tier 'pro', 'premium', or 'enterprise'.
 */
function wtcc_pro_required_badge( $required_tier = 'pro' ) {
	$colors = array(
		'pro'        => array( 'bg' => '#d63638', 'text' => '#fff' ),
		'premium'    => array( 'bg' => '#d63638', 'text' => '#fff' ),
		'enterprise' => array( 'bg' => '#5c6bc0', 'text' => '#fff' ),
	);
	$color = $colors[ $required_tier ] ?? $colors['pro'];
	$label = ucfirst( $required_tier );
	
	printf(
		'<span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:3px;background:%s;color:%s;font-size:11px;font-weight:600;text-transform:uppercase;">%s</span>',
		esc_attr( $color['bg'] ),
		esc_attr( $color['text'] ),
		esc_html( $label )
	);
}

/**
 * Check if current user can use test license keys.
 * Used for UI display (e.g., showing/hiding Generate Test Key button).
 *
 * @return bool
 */
function wtcc_can_use_test_keys() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	
	// Kill switch check.
	// NOTE: For production builds, this should be set to true.
	$disable_test_keys_permanently = false;
	if ( $disable_test_keys_permanently ) {
		return false;
	}
	
	// Check user permissions.
	$current_user_id = get_current_user_id();
	
	// User ID 1 (original site owner) can always use test keys.
	if ( $current_user_id === 1 ) {
		return true;
	}
	
	// Developer Mode toggle in admin settings.
	if ( get_option( 'wtcc_dev_mode_enabled', false ) && current_user_can( 'manage_options' ) ) {
		return true;
	}
	
	// Constant in wp-config.php.
	if ( defined( 'WTCC_ALLOW_TEST_KEYS' ) && WTCC_ALLOW_TEST_KEYS === true ) {
		return true;
	}
	
	return false;
}
