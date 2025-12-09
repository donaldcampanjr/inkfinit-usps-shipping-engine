<?php
/**
 * Inkfinit Shipping - USPS API Settings Page
 * Clean WordPress native UI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whitelist our options for the Settings API.
 */
add_filter( 'allowed_options', 'wtcc_allowed_usps_api_options' );
function wtcc_allowed_usps_api_options( $allowed_options ) {
    $allowed_options['wtc_usps_api_settings'] = array( 'wtcc_usps_api_options' );
    return $allowed_options;
}

/**
 * Register settings and sections for the USPS API page.
 */
function wtcc_register_usps_api_settings() {
	$option_group = 'wtc_usps_api_settings';

	// Register the setting for all options
	register_setting( $option_group, 'wtcc_usps_api_options', array(
		'type' => 'array',
		'sanitize_callback' => 'wtcc_usps_api_options_sanitize',
	) );

	// API Credentials Section
	add_settings_section(
		'wtcc_usps_api_credentials_section',
		null, // Title rendered in postbox
		'__return_false',
		$option_group
	);

	add_settings_field( 'consumer_key', __( 'Consumer Key', 'wtc-shipping' ), 'wtcc_usps_api_field_text', $option_group, 'wtcc_usps_api_credentials_section', [ 'id' => 'consumer_key', 'type' => 'text', 'desc' => __( 'Your USPS API Consumer Key.', 'wtc-shipping' ) ] );
	add_settings_field( 'consumer_secret', __( 'Consumer Secret', 'wtc-shipping' ), 'wtcc_usps_api_field_text', $option_group, 'wtcc_usps_api_credentials_section', [ 'id' => 'consumer_secret', 'type' => 'password', 'desc' => __( 'Your USPS API Consumer Secret.', 'wtc-shipping' ) ] );
	add_settings_field( 'api_mode', __( 'API Mode', 'wtc-shipping' ), 'wtcc_usps_api_field_select', $option_group, 'wtcc_usps_api_credentials_section', [ 'id' => 'api_mode', 'options' => [ 'production' => 'Production (Live)', 'development' => 'Development (Test)' ], 'desc' => __( 'Use Production for live rates. Development mode uses test credentials.', 'wtc-shipping' ) ] );

	// Origin Address Section
	add_settings_section(
		'wtcc_usps_origin_address_section',
		null, // Title rendered in postbox
		'__return_false',
		$option_group
	);

	add_settings_field( 'company_name', __( 'Company Name', 'wtc-shipping' ), 'wtcc_usps_api_field_text', $option_group, 'wtcc_usps_origin_address_section', [ 'id' => 'company_name', 'desc' => __( 'Your company or sender name.', 'wtc-shipping' ) ] );
	add_settings_field( 'origin_address', __( 'Origin Address', 'wtc-shipping' ), 'wtcc_usps_api_field_text', $option_group, 'wtcc_usps_origin_address_section', [ 'id' => 'origin_address', 'desc' => __( 'The street address you ship from.', 'wtc-shipping' ) ] );
	add_settings_field( 'origin_city', __( 'Origin City', 'wtc-shipping' ), 'wtcc_usps_api_field_text', $option_group, 'wtcc_usps_origin_address_section', [ 'id' => 'origin_city' ] );
	add_settings_field( 'origin_state', __( 'Origin State', 'wtc-shipping' ), 'wtcc_usps_api_field_text', $option_group, 'wtcc_usps_origin_address_section', [ 'id' => 'origin_state' ] );
	add_settings_field( 'origin_zip', __( 'Origin ZIP Code', 'wtc-shipping' ), 'wtcc_usps_api_field_text', $option_group, 'wtcc_usps_origin_address_section', [ 'id' => 'origin_zip', 'desc' => __( 'The 5-digit ZIP code where you ship from.', 'wtc-shipping' ) ] );
	add_settings_field( 'origin_phone', __( 'Origin Phone', 'wtc-shipping' ), 'wtcc_usps_api_field_text', $option_group, 'wtcc_usps_origin_address_section', [ 'id' => 'origin_phone', 'type' => 'tel' ] );
}
add_action( 'admin_init', 'wtcc_register_usps_api_settings' );

/**
 * Sanitize the options array.
 */
function wtcc_usps_api_options_sanitize( $input ) {
	$sanitized = [];

	$sanitized['consumer_key']    = sanitize_text_field( $input['consumer_key'] ?? '' );
	$sanitized['consumer_secret'] = sanitize_text_field( $input['consumer_secret'] ?? '' );
	$sanitized['api_mode']        = in_array( $input['api_mode'] ?? '', [ 'production', 'development' ], true ) ? $input['api_mode'] : 'production';
	$sanitized['company_name']    = sanitize_text_field( $input['company_name'] ?? '' );
	$sanitized['origin_address']  = sanitize_text_field( $input['origin_address'] ?? '' );
	$sanitized['origin_city']     = sanitize_text_field( $input['origin_city'] ?? '' );
	$sanitized['origin_state']    = sanitize_text_field( $input['origin_state'] ?? '' );
	$sanitized['origin_zip']      = sanitize_text_field( $input['origin_zip'] ?? '' );
	$sanitized['origin_phone']    = sanitize_text_field( $input['origin_phone'] ?? '' );

	return $sanitized;
}

/**
 * Get USPS API options with defaults.
 * Also migrates from old individual options if needed.
 */
function wtcc_get_usps_api_options() {
	static $is_migrating = false;
	
	$defaults = [
		'consumer_key'    => '',
		'consumer_secret' => '',
		'api_mode'        => 'production',
		'company_name'    => get_bloginfo( 'name' ),
		'origin_address'  => '',
		'origin_city'     => '',
		'origin_state'    => '',
		'origin_zip'      => '',
		'origin_phone'    => '',
	];
	
	$options = get_option( 'wtcc_usps_api_options', [] );
	
	// If no array options exist, try to migrate from old individual options
	// Use static flag to prevent recursion
	if ( ! $is_migrating && ( empty( $options ) || empty( $options['consumer_key'] ) ) ) {
		$is_migrating = true;
		
		$old_key = get_option( 'wtcc_usps_consumer_key', '' );
		$old_secret = get_option( 'wtcc_usps_consumer_secret', '' );
		
		// Also check the old wtc_ prefix options
		if ( empty( $old_key ) ) {
			$old_key = get_option( 'wtc_usps_consumer_key', '' );
		}
		if ( empty( $old_secret ) ) {
			$old_secret = get_option( 'wtc_usps_consumer_secret', '' );
		}
		
		if ( ! empty( $old_key ) || ! empty( $old_secret ) ) {
			$options = [
				'consumer_key'    => $old_key,
				'consumer_secret' => $old_secret,
				'api_mode'        => get_option( 'wtcc_usps_api_mode', get_option( 'wtc_usps_api_mode', 'production' ) ),
				'company_name'    => get_option( 'wtcc_usps_company_name', get_option( 'wtc_usps_company_name', get_bloginfo( 'name' ) ) ),
				'origin_address'  => get_option( 'wtcc_usps_origin_address', get_option( 'wtc_usps_origin_address', '' ) ),
				'origin_city'     => get_option( 'wtcc_usps_origin_city', get_option( 'wtc_usps_origin_city', '' ) ),
				'origin_state'    => get_option( 'wtcc_usps_origin_state', get_option( 'wtc_usps_origin_state', '' ) ),
				'origin_zip'      => get_option( 'wtcc_usps_origin_zip', get_option( 'wtc_usps_origin_zip', '' ) ),
				'origin_phone'    => get_option( 'wtcc_usps_origin_phone', get_option( 'wtc_usps_origin_phone', '' ) ),
			];
			
			// Save migrated options
			update_option( 'wtcc_usps_api_options', $options );
		}
		
		$is_migrating = false;
	}
	
	return wp_parse_args( is_array( $options ) ? $options : [], $defaults );
}

/**
 * Generic text field renderer.
 */
function wtcc_usps_api_field_text( $args ) {
	$options = wtcc_get_usps_api_options();
	$id = $args['id'];
	$type = $args['type'] ?? 'text';
	$value = $options[$id] ?? '';
	?>
	<input type="<?php echo esc_attr($type); ?>" id="wtcc_<?php echo esc_attr($id); ?>" name="wtcc_usps_api_options[<?php echo esc_attr($id); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
	<?php if ( ! empty( $args['desc'] ) ) : ?>
		<p class="description"><?php echo esc_html( $args['desc'] ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Generic select field renderer.
 */
function wtcc_usps_api_field_select( $args ) {
	$options = wtcc_get_usps_api_options();
	$id = $args['id'];
	$value = $options[$id] ?? '';
	?>
	<select id="wtcc_<?php echo esc_attr($id); ?>" name="wtcc_usps_api_options[<?php echo esc_attr($id); ?>]">
		<?php foreach ( $args['options'] as $val => $label ) : ?>
			<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $value, $val ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php if ( ! empty( $args['desc'] ) ) : ?>
		<p class="description"><?php echo esc_html( $args['desc'] ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Enqueue scripts and styles for the USPS API page.
 */
function wtcc_enqueue_usps_api_scripts( $hook ) {
	// Match any hook containing our page slug
	if ( strpos( $hook, 'wtc-core-shipping-usps-api' ) === false ) {
		return;
	}
	wp_enqueue_script( 'wtc-usps-api-admin', plugin_dir_url( __FILE__ ) . '../assets/admin-usps-api.js', [ 'jquery', 'wp-util' ], WTCC_SHIPPING_VERSION, true );
	wp_localize_script( 'wtc-usps-api-admin', 'wtc_usps_api', [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'wtcc_test_api' ),
		'i18n'     => [
			'testing'   => esc_html__( 'Testing...', 'wtc-shipping' ),
			'connError' => esc_html__( 'Connection error.', 'wtc-shipping' ),
		],
	] );
}
add_action( 'admin_enqueue_scripts', 'wtcc_enqueue_usps_api_scripts' );

/**
 * Render USPS API settings page
 * 
 * IMPORTANT: This page requires Pro/Premium/Enterprise license.
 * Free users are blocked from accessing this page entirely.
 */
function wtcc_shipping_usps_api_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wtc-shipping' ) );
	}

	// STRICT LICENSE CHECK - Block free users entirely
	$is_licensed = function_exists( 'wtcc_is_pro' ) && wtcc_is_pro();
	if ( ! $is_licensed ) {
		?>
		<div class="wrap">
			<?php wtcc_admin_header(__( 'USPS API Settings', 'wtc-shipping' )); ?>
			<div class="notice notice-error">
				<h3 style="margin-top: 0.5em;">🔒 <?php esc_html_e( 'Pro License Required', 'wtc-shipping' ); ?></h3>
				<p><?php esc_html_e( 'USPS API credentials and live rate calculations require a Pro, Premium, or Enterprise license.', 'wtc-shipping' ); ?></p>
				<p><?php esc_html_e( 'With a Pro license, you can:', 'wtc-shipping' ); ?></p>
				<ul style="list-style: disc; margin-left: 2em;">
					<li><?php esc_html_e( 'Connect directly to USPS Web Tools API', 'wtc-shipping' ); ?></li>
					<li><?php esc_html_e( 'Get real-time shipping rates at checkout', 'wtc-shipping' ); ?></li>
					<li><?php esc_html_e( 'Print shipping labels directly from WooCommerce', 'wtc-shipping' ); ?></li>
					<li><?php esc_html_e( 'Track packages and provide tracking to customers', 'wtc-shipping' ); ?></li>
					<li><?php esc_html_e( 'Access presets, box packing, and all shipping tools', 'wtc-shipping' ); ?></li>
				</ul>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Activate License', 'wtc-shipping' ); ?>
					</a>
					<a href="https://inkfinit.pro/plugins/usps-shipping/" target="_blank" class="button button-secondary" style="margin-left: 10px;">
						<?php esc_html_e( 'Get Pro License', 'wtc-shipping' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
		return;
	}

	$options = wtcc_get_usps_api_options();
	$is_configured = ! empty( $options['consumer_key'] ) && ! empty( $options['consumer_secret'] ) && ! empty( $options['origin_zip'] );
	$last_success = get_option( 'wtcc_last_usps_success' );
	$last_failure = get_option( 'wtcc_last_usps_failure' );
	?>
	<div class="wrap">
		<?php wtcc_admin_header(__( 'USPS API Settings', 'wtc-shipping' )); ?>

		<?php settings_errors(); ?>

		<?php if ( $is_configured && $last_success && ( ! $last_failure || $last_success > $last_failure ) ) : ?>
			<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible">
				<p><strong><?php esc_html_e( 'API Connected', 'wtc-shipping' ); ?></strong> &mdash; <?php printf( esc_html__( 'Your store is using live USPS rates. Last verified: %s ago.', 'wtc-shipping' ), esc_html( human_time_diff( $last_success ) ) ); ?></p>
			</div>
		<?php elseif ( $is_configured && $last_failure ) : ?>
			<div id="setting-error-settings_updated" class="notice notice-warning settings-error is-dismissible">
				<p><strong><?php esc_html_e( 'Connection Issue', 'wtc-shipping' ); ?></strong> &mdash; <?php esc_html_e( 'Credentials saved but the last test failed. Please verify below.', 'wtc-shipping' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'wtc_usps_api_settings' ); ?>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<!-- Main Content -->
					<div id="post-body-content">
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading(__( 'API Credentials', 'wtc-shipping' )); ?></h2>
							<div class="inside">
								<table class="form-table">
									<?php do_settings_fields( 'wtc_usps_api_settings', 'wtcc_usps_api_credentials_section' ); ?>
								</table>
							</div>
						</div>
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading(__( 'Origin Address', 'wtc-shipping' )); ?></h2>
							<div class="inside">
								<p><?php esc_html_e( 'This is the address where you ship your packages from. It is required for accurate rate calculations.', 'wtc-shipping' ); ?></p>
								<table class="form-table">
									<?php do_settings_fields( 'wtc_usps_api_settings', 'wtcc_usps_origin_address_section' ); ?>
								</table>
							</div>
						</div>
						<p class="submit">
							<?php submit_button( __( 'Save Settings', 'wtc-shipping' ), 'primary', 'submit', false ); ?>
							<?php if ( $is_configured ) : ?>
								<button type="button" id="wtcc-test-api-btn" class="button button-secondary">
									<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Test Connection', 'wtc-shipping' ); ?>
								</button>
								<span id="wtcc-test-api-status" class="spinner"></span>
							<?php endif; ?>
						</p>
					</div>

					<!-- Sidebar -->
					<div id="postbox-container-1" class="postbox-container">
						<?php wtcc_usps_api_sidebar_setup_guide(); ?>
						<?php wtcc_usps_api_sidebar_troubleshooting(); ?>
					</div>
				</div>
				<br class="clear">
			</div>
		</form>
	</div>
	<?php
}

/**
 * Renders the sidebar setup guide widget.
 */
function wtcc_usps_api_sidebar_setup_guide() {
	?>
	<div class="postbox">
		<h2 class="hndle"><?php echo wtcc_section_heading( __( 'Setup Guide', 'wtc-shipping' ) ); ?></h2>
		<div class="inside">
			<p><?php esc_html_e( 'Get your free USPS API credentials in 3 steps:', 'wtc-shipping' ); ?></p>
			<ol>
				<li>
					<strong><?php esc_html_e( 'Create a USPS Developer Account', 'wtc-shipping' ); ?></strong><br>
					<a href="https://developer.usps.com/apis" target="_blank" rel="noopener"><?php esc_html_e( 'Register at the USPS Developer Portal', 'wtc-shipping' ); ?> <span class="dashicons dashicons-external"></span></a>
				</li>
				<li>
					<strong><?php esc_html_e( 'Create an Application', 'wtc-shipping' ); ?></strong><br>
					<span class="description"><?php esc_html_e( 'In your dashboard, click "Apps" &rarr; "Create App". Enable the "Domestic Prices" API.', 'wtc-shipping' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'Copy Your Credentials', 'wtc-shipping' ); ?></strong><br>
					<span class="description"><?php esc_html_e( 'Copy your Consumer Key and Consumer Secret, then paste them in the form on this page.', 'wtc-shipping' ); ?></span>
				</li>
			</ol>
			<div class="notice notice-info inline">
				<p><strong><?php esc_html_e( 'Note:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'USPS uses OAuth v3 (2024). You need a Consumer Key and Consumer Secret. Old "User ID" credentials will not work.', 'wtc-shipping' ); ?></p>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Renders the sidebar troubleshooting widget.
 */
function wtcc_usps_api_sidebar_troubleshooting() {
	?>
	<div class="postbox">
		<h2 class="hndle"><?php echo wtcc_section_heading( __( 'Troubleshooting', 'wtc-shipping' ) ); ?></h2>
		<div class="inside">
			<p><strong><?php esc_html_e( 'Common issues:', 'wtc-shipping' ); ?></strong></p>
			<ul>
				<li><strong>"<?php esc_html_e( 'OAuth failed', 'wtc-shipping' ); ?>"</strong> &mdash; <?php esc_html_e( 'Double-check your Consumer Key and Secret.', 'wtc-shipping' ); ?></li>
				<li><strong>"<?php esc_html_e( 'Connection refused', 'wtc-shipping' ); ?>"</strong> &mdash; <?php esc_html_e( 'Your server firewall may be blocking outbound requests.', 'wtc-shipping' ); ?></li>
				<li><strong><?php esc_html_e( 'No rates at checkout', 'wtc-shipping' ); ?></strong> &mdash; <?php esc_html_e( 'Use the Diagnostics tool to check for errors.', 'wtc-shipping' ); ?></li>
			</ul>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-diagnostics' ) ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Run Diagnostics', 'wtc-shipping' ); ?>
			</a>
		</div>
	</div>
	<?php
}
