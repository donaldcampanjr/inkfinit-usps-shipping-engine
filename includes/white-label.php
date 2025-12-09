<?php
/**
 * White-Label Mode for Premium and Enterprise Tiers.
 *
 * Allows resellers and agencies to hide Inkfinit branding
 * and customize the plugin appearance for their clients.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if white-label mode is enabled.
 *
 * @return bool True if white-label is active.
 */
function wtcc_is_white_label_enabled() {
	// Only Premium+ can use white-label.
	if ( ! wtcc_is_premium() ) {
		return false;
	}

	return (bool) get_option( 'wtcc_white_label_enabled', false );
}

/**
 * Get white-label settings.
 *
 * @return array White-label configuration.
 */
function wtcc_get_white_label_settings() {
	$defaults = array(
		'enabled'      => false,
		'plugin_name'  => __( 'USPS Shipping', 'wtc-shipping' ),
		'company_name' => '',
		'company_url'  => '',
		'support_url'  => '',
		'logo_url'     => '',
		'primary_color' => '#2271b1',
	);

	$settings = get_option( 'wtcc_white_label_settings', array() );

	return wp_parse_args( $settings, $defaults );
}

/**
 * Get the display name for the plugin (white-labeled or default).
 *
 * @return string Plugin name.
 */
function wtcc_get_plugin_display_name() {
	if ( wtcc_is_white_label_enabled() ) {
		$settings = wtcc_get_white_label_settings();
		// Provide sensible default if empty.
		$name = trim( $settings['plugin_name'] ?? '' );
		return ! empty( $name ) ? $name : __( 'USPS Shipping Pro', 'wtc-shipping' );
	}

	return __( 'Inkfinit USPS Shipping Engine', 'wtc-shipping' );
}

/**
 * Get company name for display.
 *
 * @return string Company name.
 */
function wtcc_get_company_name() {
	if ( wtcc_is_white_label_enabled() ) {
		$settings = wtcc_get_white_label_settings();
		// Provide sensible default if empty - use site name.
		$name = trim( $settings['company_name'] ?? '' );
		return ! empty( $name ) ? $name : get_bloginfo( 'name' );
	}

	return 'Inkfinit LLC';
}

/**
 * Filter plugin header data for white-labeling.
 */
add_filter( 'all_plugins', 'wtcc_filter_plugin_header' );

/**
 * Filter plugin header in plugins list.
 *
 * @param array $plugins All plugins.
 * @return array Filtered plugins.
 */
function wtcc_filter_plugin_header( $plugins ) {
	if ( ! wtcc_is_white_label_enabled() ) {
		return $plugins;
	}

	$plugin_file = plugin_basename( WTCC_SHIPPING_PLUGIN_DIR . 'plugin.php' );

	if ( isset( $plugins[ $plugin_file ] ) ) {
		$settings = wtcc_get_white_label_settings();

		$plugins[ $plugin_file ]['Name']        = $settings['plugin_name'] ?: 'USPS Shipping';
		$plugins[ $plugin_file ]['PluginURI']   = $settings['company_url'] ?: '';
		$plugins[ $plugin_file ]['Author']      = $settings['company_name'] ?: '';
		$plugins[ $plugin_file ]['AuthorURI']   = $settings['company_url'] ?: '';
		$plugins[ $plugin_file ]['Description'] = __( 'Professional USPS shipping rates and label printing for WooCommerce.', 'wtc-shipping' );
	}

	return $plugins;
}

/**
 * Register settings for white-label.
 */
add_action( 'admin_init', 'wtcc_register_white_label_settings' );

/**
 * Register white-label settings.
 */
function wtcc_register_white_label_settings() {
	register_setting( 'wtcc_white_label_group', 'wtcc_white_label_enabled', array(
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );

	register_setting( 'wtcc_white_label_group', 'wtcc_white_label_settings', array(
		'type'              => 'array',
		'sanitize_callback' => 'wtcc_sanitize_white_label_settings',
	) );
}

/**
 * Sanitize white-label settings.
 *
 * @param array $input Raw input.
 * @return array Sanitized settings.
 */
function wtcc_sanitize_white_label_settings( $input ) {
	return array(
		'plugin_name'   => sanitize_text_field( $input['plugin_name'] ?? '' ),
		'company_name'  => sanitize_text_field( $input['company_name'] ?? '' ),
		'company_url'   => esc_url_raw( $input['company_url'] ?? '' ),
		'support_url'   => esc_url_raw( $input['support_url'] ?? '' ),
		'logo_url'      => esc_url_raw( $input['logo_url'] ?? '' ),
		'primary_color' => sanitize_hex_color( $input['primary_color'] ?? '#2271b1' ),
	);
}

/**
 * Render white-label settings UI.
 */
function wtcc_render_white_label_settings() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	// Check tier.
	if ( ! wtcc_is_premium() ) {
		?>
		<div class="wtcc-white-label-locked">
			<h3>
				<?php esc_html_e( 'White-Label Mode', 'wtc-shipping' ); ?>
				<?php wtcc_render_premium_badge(); ?>
			</h3>
			<p><?php esc_html_e( 'Remove Inkfinit branding and customize the plugin for your agency clients. Available for Premium and Enterprise customers.', 'wtc-shipping' ); ?></p>
			<a href="<?php echo esc_url( WTCC_UPGRADE_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
				<?php esc_html_e( 'Upgrade to Premium', 'wtc-shipping' ); ?>
			</a>
		</div>
		<?php
		return;
	}

	$settings = wtcc_get_white_label_settings();
	$enabled  = get_option( 'wtcc_white_label_enabled', false );

	?>
	<div class="wtcc-white-label-settings">
		<h3>
			<?php esc_html_e( 'White-Label Mode', 'wtc-shipping' ); ?>
			<?php if ( $enabled ) : ?>
				<span class="update-plugins count-1"><span class="plugin-count"><?php esc_html_e( 'Active', 'wtc-shipping' ); ?></span></span>
			<?php endif; ?>
		</h3>
		<p class="description"><?php esc_html_e( 'Customize the plugin branding for your clients. When enabled, Inkfinit branding will be hidden throughout the admin interface.', 'wtc-shipping' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'wtcc_white_label_group' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable White-Label', 'wtc-shipping' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wtcc_white_label_enabled" value="1" <?php checked( $enabled ); ?> />
							<?php esc_html_e( 'Hide Inkfinit branding throughout the plugin', 'wtc-shipping' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wtcc_plugin_name"><?php esc_html_e( 'Plugin Name', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="text" id="wtcc_plugin_name" name="wtcc_white_label_settings[plugin_name]" value="<?php echo esc_attr( $settings['plugin_name'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'USPS Shipping', 'wtc-shipping' ); ?>" />
						<p class="description"><?php esc_html_e( 'The name displayed in the admin menu and plugin list.', 'wtc-shipping' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wtcc_company_name"><?php esc_html_e( 'Company Name', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="text" id="wtcc_company_name" name="wtcc_white_label_settings[company_name]" value="<?php echo esc_attr( $settings['company_name'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Your Agency Name', 'wtc-shipping' ); ?>" />
						<p class="description"><?php esc_html_e( 'Your company name for attribution.', 'wtc-shipping' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wtcc_company_url"><?php esc_html_e( 'Company URL', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="url" id="wtcc_company_url" name="wtcc_white_label_settings[company_url]" value="<?php echo esc_url( $settings['company_url'] ); ?>" class="regular-text" placeholder="https://youragency.com" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wtcc_support_url"><?php esc_html_e( 'Support URL', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="url" id="wtcc_support_url" name="wtcc_white_label_settings[support_url]" value="<?php echo esc_url( $settings['support_url'] ); ?>" class="regular-text" placeholder="https://youragency.com/support" />
						<p class="description"><?php esc_html_e( 'Where clients should go for support.', 'wtc-shipping' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wtcc_logo_url"><?php esc_html_e( 'Logo URL', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="url" id="wtcc_logo_url" name="wtcc_white_label_settings[logo_url]" value="<?php echo esc_url( $settings['logo_url'] ); ?>" class="regular-text" placeholder="https://youragency.com/logo.png" />
						<p class="description"><?php esc_html_e( 'URL to your logo (recommended: 200x50px).', 'wtc-shipping' ); ?></p>
						<?php if ( ! empty( $settings['logo_url'] ) ) : ?>
							<div class="card">
								<img src="<?php echo esc_url( $settings['logo_url'] ); ?>" alt="<?php esc_attr_e( 'Logo preview', 'wtc-shipping' ); ?>" />
							</div>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wtcc_primary_color"><?php esc_html_e( 'Primary Color', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="color" id="wtcc_primary_color" name="wtcc_white_label_settings[primary_color]" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" />
						<code><?php echo esc_html( $settings['primary_color'] ); ?></code>
						<p class="description"><?php esc_html_e( 'Accent color for buttons and highlights.', 'wtc-shipping' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save White-Label Settings', 'wtc-shipping' ) ); ?>
		</form>

		<?php if ( $enabled ) : ?>
			<div class="card">
				<h4><?php esc_html_e( 'Preview', 'wtc-shipping' ); ?></h4>
				<p><strong><?php esc_html_e( 'Plugin Name:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( wtcc_get_plugin_display_name() ); ?></p>
				<p><strong><?php esc_html_e( 'Company:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( wtcc_get_company_name() ); ?></p>
				<?php if ( ! empty( $settings['logo_url'] ) ) : ?>
					<p><strong><?php esc_html_e( 'Logo:', 'wtc-shipping' ); ?></strong></p>
					<img src="<?php echo esc_url( $settings['logo_url'] ); ?>" alt="" />
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Apply custom CSS for white-label primary color.
 */
add_action( 'admin_head', 'wtcc_white_label_custom_css' );

/**
 * Output custom CSS for white-label styling.
 */
function wtcc_white_label_custom_css() {
	if ( ! wtcc_is_white_label_enabled() ) {
		return;
	}

	$settings = wtcc_get_white_label_settings();
	$color    = $settings['primary_color'] ?: '#2271b1';

	// Only apply on our pages.
	$screen = get_current_screen();
	if ( ! $screen || strpos( $screen->id, 'inkfinit-shipping' ) === false ) {
		return;
	}

	?>
	<style>
	.wtcc-white-label-brand .button-primary,
	.wtcc-white-label-brand a.button-primary {
		background: <?php echo esc_attr( $color ); ?>;
		border-color: <?php echo esc_attr( $color ); ?>;
	}
	.wtcc-white-label-brand .button-primary:hover,
	.wtcc-white-label-brand a.button-primary:hover {
		background: <?php echo esc_attr( wtcc_adjust_color_brightness( $color, -20 ) ); ?>;
		border-color: <?php echo esc_attr( wtcc_adjust_color_brightness( $color, -20 ) ); ?>;
	}
	</style>
	<?php
}

/**
 * Adjust color brightness.
 *
 * @param string $hex   Hex color.
 * @param int    $steps Steps to adjust (-255 to 255).
 * @return string Adjusted hex color.
 */
function wtcc_adjust_color_brightness( $hex, $steps ) {
	$hex = str_replace( '#', '', $hex );

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	$r = max( 0, min( 255, $r + $steps ) );
	$g = max( 0, min( 255, $g + $steps ) );
	$b = max( 0, min( 255, $b + $steps ) );

	return '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT ) .
	       str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT ) .
	       str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );
}

/**
 * Filter admin menu labels for white-label mode.
 */
add_action( 'admin_menu', 'wtcc_white_label_admin_menu', 999 );

/**
 * Update admin menu labels when white-label is enabled.
 */
function wtcc_white_label_admin_menu() {
	if ( ! wtcc_is_white_label_enabled() ) {
		return;
	}

	global $submenu;

	// Update WooCommerce submenu items.
	if ( isset( $submenu['woocommerce'] ) ) {
		$settings    = wtcc_get_white_label_settings();
		$plugin_name = $settings['plugin_name'] ?: __( 'USPS Shipping', 'wtc-shipping' );

		foreach ( $submenu['woocommerce'] as $key => $item ) {
			// Replace "Inkfinit" with custom plugin name.
			if ( strpos( $item[0], 'Inkfinit' ) !== false ) {
				$submenu['woocommerce'][ $key ][0] = str_replace( 'Inkfinit Shipping', $plugin_name, $item[0] );
			}
		}
	}
}

/**
 * Render the White-Label settings page.
 */
function wtcc_render_white_label_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'wtc-shipping' ) );
	}
	?>
	<div class="wrap">
		<?php if ( function_exists( 'wtcc_admin_header' ) ) : ?>
			<?php wtcc_admin_header( __( 'White-Label Settings', 'wtc-shipping' ) ); ?>
		<?php else : ?>
			<h1><?php esc_html_e( 'White-Label Settings', 'wtc-shipping' ); ?></h1>
		<?php endif; ?>
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<div class="postbox">
						<h2 class="hndle">
							<span class="dashicons dashicons-art"></span>
							<?php esc_html_e( 'White-Label Branding', 'wtc-shipping' ); ?>
						</h2>
						<div class="inside">
							<?php wtcc_render_white_label_settings(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
