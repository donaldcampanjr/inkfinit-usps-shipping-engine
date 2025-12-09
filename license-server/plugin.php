<?php
/**
 * Plugin Name: Inkfinit License Server
 * Plugin URI: https://inkfinit.pro
 * Description: Automatic license key generation and validation for Inkfinit USPS Shipping Engine Pro.
 * Version: 1.0.0
 * Author: Inkfinit LLC
 * Author URI: https://inkfinit.pro
 * License: GPL-3.0-or-later
 * Text Domain: inkfinit-license-server
 * Domain Path: /languages
 *
 * @package InkfinitLicenseServer
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'BILS_PLUGIN_FILE', __FILE__ );
define( 'BILS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BILS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BILS_VERSION', '1.0.0' );

// Include core files.
require_once BILS_PLUGIN_DIR . 'includes/class-database.php';
require_once BILS_PLUGIN_DIR . 'includes/class-key-generator.php';
require_once BILS_PLUGIN_DIR . 'includes/class-license-manager.php';
require_once BILS_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once BILS_PLUGIN_DIR . 'includes/class-admin.php';
require_once BILS_PLUGIN_DIR . 'includes/hooks.php';

// On activation, create the license keys table.
register_activation_hook( __FILE__, function() {
	$database = new BILS_Database();
	$database->create_table();
} );

// Load the plugin.
add_action( 'plugins_loaded', function() {
	BILS_REST_API::register_routes();
	BILS_Admin::init();
} );
