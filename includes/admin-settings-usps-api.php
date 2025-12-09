<?php
/**
 * Inkfinit Shipping - USPS API Settings Fields
 *
 * @package WTC_Shipping_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add settings sections and fields for the USPS API page.
 */
function wtcc_add_usps_api_settings_sections() {
	// Section: API Credentials
	add_settings_section(
		'wtcc_usps_api_credentials_section',
		__( 'API Credentials', 'wtc-shipping' ),
		'wtcc_usps_api_credentials_section_callback',
		'wtc_usps_api_settings_page'
	);

	// Section: Advanced Settings
	add_settings_section(
		'wtcc_usps_advanced_settings_section',
		__( 'Advanced Settings', 'wtc-shipping' ),
		'__return_false', // No description needed for this section
		'wtc_usps_api_settings_page'
	);

	// Field: Consumer Key
	add_settings_field(
		'wtcc_usps_consumer_key',
		__( 'Consumer Key', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_usps_consumer_key',
			'name'        => 'wtcc_usps_consumer_key',
			'value'       => get_option( 'wtcc_usps_consumer_key', '' ),
			'placeholder' => __( 'Enter your Consumer Key', 'wtc-shipping' ),
			'description' => __( 'From your USPS Developer App.', 'wtc-shipping' ),
			'class'       => 'large-text',
			'type'        => 'text',
			'autocomplete' => 'off',
		)
	);

	// Field: Consumer Secret
	add_settings_field(
		'wtcc_usps_consumer_secret',
		__( 'Consumer Secret', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_usps_consumer_secret',
			'name'        => 'wtcc_usps_consumer_secret',
			'value'       => get_option( 'wtcc_usps_consumer_secret', '' ),
			'placeholder' => __( 'Enter your Consumer Secret', 'wtc-shipping' ),
			'description' => __( 'Keep this secret; never share it publicly.', 'wtc-shipping' ),
			'class'       => 'large-text',
			'type'        => 'password',
			'autocomplete' => 'off',
		)
	);

	// Field: Origin ZIP Code
	add_settings_field(
		'wtcc_origin_zip',
		__( 'Origin ZIP Code', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_origin_zip',
			'name'        => 'wtcc_origin_zip',
			'value'       => get_option( 'wtcc_origin_zip', '' ),
			'placeholder' => '12345',
			'description' => __( 'The ZIP code where your packages ship from.', 'wtc-shipping' ),
			'class'       => 'regular-text',
			'required'    => true,
			'pattern'     => '\d{5}(-\d{4})?',
			'autocomplete' => 'off',
		)
	);

	// Field: Origin Address
	add_settings_field(
		'wtcc_origin_address',
		__( 'Origin Address', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_origin_address',
			'name'        => 'wtcc_origin_address',
			'value'       => get_option( 'wtcc_origin_address', '' ),
			'placeholder' => '123 Main Street',
			'description' => __( 'Street address for carrier pickup.', 'wtc-shipping' ),
			'class'       => 'large-text',
			'autocomplete' => 'off',
		)
	);

	// Field: Origin City
	add_settings_field(
		'wtcc_origin_city',
		__( 'Origin City', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_origin_city',
			'name'        => 'wtcc_origin_city',
			'value'       => get_option( 'wtcc_origin_city', '' ),
			'placeholder' => 'New York',
			'description' => __( 'City for carrier pickup.', 'wtc-shipping' ),
			'class'       => 'regular-text',
			'autocomplete' => 'off',
		)
	);

	// Field: Origin State
	add_settings_field(
		'wtcc_origin_state',
		__( 'Origin State', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_origin_state',
			'name'        => 'wtcc_origin_state',
			'value'       => get_option( 'wtcc_origin_state', '' ),
			'placeholder' => 'NY',
			'description' => __( 'Two-letter state code (e.g., NY, CA).', 'wtc-shipping' ),
			'class'       => 'small-text',
			'maxlength'   => 2,
			'pattern'     => '[A-Za-z]{2}',
			'autocomplete' => 'off',
		)
	);

	// Field: Origin Phone
	add_settings_field(
		'wtcc_origin_phone',
		__( 'Origin Phone', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_origin_phone',
			'name'        => 'wtcc_origin_phone',
			'value'       => get_option( 'wtcc_origin_phone', '' ),
			'placeholder' => '555-123-4567',
			'description' => __( 'Contact phone number for pickup.', 'wtc-shipping' ),
			'class'       => 'regular-text',
			'type'        => 'tel',
			'autocomplete' => 'off',
		)
	);

	// Field: Company Name
	add_settings_field(
		'wtcc_company_name',
		__( 'Company Name', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_company_name',
			'name'        => 'wtcc_company_name',
			'value'       => get_option( 'wtcc_company_name', '' ),
			'placeholder' => __( 'Your Company Name', 'wtc-shipping' ),
			'description' => __( 'Business name for carrier pickup (optional).', 'wtc-shipping' ),
			'class'       => 'large-text',
			'autocomplete' => 'off',
		)
	);

	// Field: API Mode
	add_settings_field(
		'wtcc_usps_api_mode',
		__( 'API Mode', 'wtc-shipping' ),
		'wtcc_render_select_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_api_credentials_section',
		array(
			'id'          => 'wtcc_usps_api_mode',
			'name'        => 'wtcc_usps_api_mode',
			'value'       => get_option( 'wtcc_usps_api_mode', 'production' ),
			'options'     => array(
				'production' => __( 'Production (Live Rates)', 'wtc-shipping' ),
				'sandbox'    => __( 'Sandbox (Testing)', 'wtc-shipping' ),
			),
			'description' => __( 'Use Production for real transactions. Sandbox is for testing only.', 'wtc-shipping' ),
		)
	);

	// Field: Data Preservation
	add_settings_field(
		'wtcc_preserve_data_on_uninstall',
		__( 'Data Preservation', 'wtc-shipping' ),
		'wtcc_render_checkbox_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_advanced_settings_section',
		array(
			'id'          => 'wtcc_preserve_data_on_uninstall',
			'name'        => 'wtcc_preserve_data_on_uninstall',
			'value'       => get_option( 'wtcc_preserve_data_on_uninstall', 'yes' ),
			'label'       => __( 'Keep all settings when this plugin is deleted.', 'wtc-shipping' ),
			'description' => __( 'If checked, your credentials and settings will be preserved if you uninstall the plugin.', 'wtc-shipping' ),
		)
	);

	// Field: License Key (for Pro features and support)
	add_settings_field(
		'wtcc_license_key',
		__( 'License Key', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_advanced_settings_section',
		array(
			'id'          => 'wtcc_license_key',
			'name'        => 'wtcc_license_key',
			'value'       => wtcc_get_license_key(),
			'placeholder' => __( 'Enter your Inkfinit license key', 'wtc-shipping' ),
			'description' => __( 'Used for Pro features, updates, and support. You can safely leave this blank while evaluating the calculator.', 'wtc-shipping' ),
			'class'       => 'large-text',
			'autocomplete' => 'off',
		)
	);

	// Field: License Server URL (optional, for automatic validation)
	add_settings_field(
		'wtcc_license_server_url',
		__( 'License Server URL', 'wtc-shipping' ),
		'wtcc_render_text_field',
		'wtc_usps_api_settings_page',
		'wtcc_usps_advanced_settings_section',
		array(
			'id'          => 'wtcc_license_server_url',
			'name'        => 'wtcc_license_server_url',
			'value'       => get_option( 'wtcc_license_server_url', '' ),
			'placeholder' => __( 'https://yourstore.com/wp-json/inkfinit/v1/license/validate', 'wtc-shipping' ),
			'description' => __( 'Optional. If set, the plugin will contact this URL to validate license keys automatically. Leave blank to use local key-only checks.', 'wtc-shipping' ),
			'class'       => 'large-text',
			'autocomplete' => 'off',
		)
	);
}
add_action( 'admin_init', 'wtcc_add_usps_api_settings_sections' );

/**
 * Register stored options for USPS settings page.
 *
 * Keeping this simple: origin fields and license key are stored as individual options.
 */
function wtcc_register_usps_api_settings() {
	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_usps_consumer_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_usps_consumer_secret',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_origin_zip',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_origin_address',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_origin_city',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_origin_state',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_origin_phone',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_company_name',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_usps_api_mode',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'production',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_preserve_data_on_uninstall',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_license_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'wtcc_usps_api_settings_group',
		'wtcc_license_server_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'wtcc_register_usps_api_settings' );

/**
 * Renders the description for the API Credentials section.
 */
function wtcc_usps_api_credentials_section_callback() {
	echo '<p>' . esc_html__( 'Enter your USPS API credentials and origin address. These are required to fetch live shipping rates.', 'wtc-shipping' ) . '</p>';
}

/**
 * Generic renderer for text and password input fields.
 *
 * @param array $args Field arguments.
 */
function wtcc_render_text_field( $args ) {
	$defaults = array(
		'type'        => 'text',
		'class'       => 'regular-text',
		'placeholder' => '',
		'description' => '',
		'required'    => false,
		'pattern'     => '',
		'maxlength'   => '',
		'autocomplete' => 'on',
	);
	$args = wp_parse_args( $args, $defaults );

	printf(
		'<input type="%s" id="%s" name="%s" value="%s" class="%s" placeholder="%s" %s %s %s autocomplete="%s">',
		esc_attr( $args['type'] ),
		esc_attr( $args['id'] ),
		esc_attr( $args['name'] ),
		esc_attr( $args['value'] ),
		esc_attr( $args['class'] ),
		esc_attr( $args['placeholder'] ),
		$args['required'] ? 'required' : '',
		! empty( $args['pattern'] ) ? 'pattern="' . esc_attr( $args['pattern'] ) . '"' : '',
		! empty( $args['maxlength'] ) ? 'maxlength="' . esc_attr( $args['maxlength'] ) . '"' : '',
		esc_attr( $args['autocomplete'] )
	);

	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

/**
 * Generic renderer for select fields.
 *
 * @param array $args Field arguments.
 */
function wtcc_render_select_field( $args ) {
	printf( '<select id="%s" name="%s">', esc_attr( $args['id'] ), esc_attr( $args['name'] ) );
	foreach ( $args['options'] as $value => $label ) {
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $value ),
			selected( $args['value'], $value, false ),
			esc_html( $label )
		);
	}
	echo '</select>';

	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

/**
 * Generic renderer for checkbox fields.
 *
 * @param array $args Field arguments.
 */
function wtcc_render_checkbox_field( $args ) {
	echo '<label>';
	printf(
		'<input type="checkbox" id="%s" name="%s" value="yes" %s>',
		esc_attr( $args['id'] ),
		esc_attr( $args['name'] ),
		checked( $args['value'], 'yes', false )
	);
	printf( ' %s', esc_html( $args['label'] ) );
	echo '</label>';

	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}
