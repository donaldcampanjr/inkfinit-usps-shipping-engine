<?php
/**
 * Simple Shipping Rate Calculator
 * 
 * FREE FEATURE - Available to all users regardless of license.
 * Uses native WordPress admin UI only.
 * 
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Simple Calculator admin page.
 */
function wtcc_render_simple_calculator_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wtc-shipping' ) );
	}
	
	wp_enqueue_script( 
		'wtcc-simple-calculator', 
		WTCC_SHIPPING_PLUGIN_URL . 'assets/simple-calculator.js', 
		array( 'jquery' ), 
		WTCC_SHIPPING_VERSION, 
		true 
	);
	
	wp_localize_script( 'wtcc-simple-calculator', 'wtcc_calc', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'wtcc_simple_calc' ),
		'is_pro'   => wtcc_is_pro(),
	) );
	
	$is_pro = wtcc_is_pro();
	$saved_origin = get_option( 'wtcc_origin_zip', '' );
	if ( empty( $saved_origin ) ) {
		$options = function_exists( 'wtcc_get_usps_api_options' ) ? wtcc_get_usps_api_options() : array();
		$saved_origin = $options['origin_zip'] ?? '';
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Shipping Rate Calculator', 'wtc-shipping' ); ?></h1>
		
		<?php if ( ! $is_pro ) : ?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Free Calculator Mode', 'wtc-shipping' ); ?></strong> — 
				<?php esc_html_e( 'Enter package details to estimate shipping costs. Upgrade to Pro for live USPS rates at checkout.', 'wtc-shipping' ); ?>
			</p>
		</div>
		<?php endif; ?>

		<div class="card">
			<h2><?php esc_html_e( 'Calculate Shipping Rates', 'wtc-shipping' ); ?></h2>
			
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="wtcc_calc_weight"><?php esc_html_e( 'Package Weight', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="number" id="wtcc_calc_weight" class="small-text" min="0.1" step="0.1" placeholder="0">
						<select id="wtcc_calc_weight_unit">
							<option value="oz"><?php esc_html_e( 'Ounces', 'wtc-shipping' ); ?></option>
							<option value="lb"><?php esc_html_e( 'Pounds', 'wtc-shipping' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Total weight of the package.', 'wtc-shipping' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wtcc_calc_origin_zip"><?php esc_html_e( 'Origin ZIP Code', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="text" id="wtcc_calc_origin_zip" class="small-text" maxlength="5" pattern="[0-9]{5}" placeholder="12345" value="<?php echo esc_attr( $saved_origin ); ?>">
						<p class="description"><?php esc_html_e( 'Where you ship from (5-digit ZIP).', 'wtc-shipping' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wtcc_calc_dest_zip"><?php esc_html_e( 'Destination ZIP Code', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<input type="text" id="wtcc_calc_dest_zip" class="small-text" maxlength="5" pattern="[0-9]{5}" placeholder="90210">
						<p class="description"><?php esc_html_e( 'Where you are shipping to (5-digit ZIP).', 'wtc-shipping' ); ?></p>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<button type="button" id="wtcc_calc_button" class="button button-primary">
					<?php esc_html_e( 'Calculate Rates', 'wtc-shipping' ); ?>
				</button>
				<span id="wtcc_calc_spinner" class="spinner"></span>
			</p>
		</div>

		<div id="wtcc_calc_results_box" class="card" hidden>
			<h2><?php esc_html_e( 'Estimated Shipping Rates', 'wtc-shipping' ); ?></h2>
			<div id="wtcc_calc_results"></div>
			<p class="description"><?php esc_html_e( 'Note: These are estimates based on USPS published rates. Actual rates may vary.', 'wtc-shipping' ); ?></p>
		</div>

		<div id="wtcc_calc_error_box" class="notice notice-error" hidden>
			<p id="wtcc_calc_error_msg"></p>
		</div>

		<?php if ( ! $is_pro ) : ?>
		<div class="card">
			<h2><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Upgrade to Pro', 'wtc-shipping' ); ?></h2>
			<p><?php esc_html_e( 'Get live USPS rates at checkout, label printing, tracking, and more!', 'wtc-shipping' ); ?></p>
			<ul class="ul-disc">
				<li><?php esc_html_e( 'Real-time USPS rates', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Print shipping labels', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Package tracking', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Shipping presets', 'wtc-shipping' ); ?></li>
			</ul>
			<p>
				<a href="https://inkfinit.pro" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Get Pro', 'wtc-shipping' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) ); ?>" class="button"><?php esc_html_e( 'Enter License', 'wtc-shipping' ); ?></a>
			</p>
		</div>
		<?php endif; ?>

		<div class="card">
			<h2><?php esc_html_e( 'USPS Services', 'wtc-shipping' ); ?></h2>
			<p><strong><?php esc_html_e( 'Ground Advantage', 'wtc-shipping' ); ?></strong> — <?php esc_html_e( '2-5 business days, most economical', 'wtc-shipping' ); ?></p>
			<p><strong><?php esc_html_e( 'Priority Mail', 'wtc-shipping' ); ?></strong> — <?php esc_html_e( '1-3 business days, includes tracking', 'wtc-shipping' ); ?></p>
			<p><strong><?php esc_html_e( 'Priority Mail Express', 'wtc-shipping' ); ?></strong> — <?php esc_html_e( 'Overnight to 2-day, money-back guarantee', 'wtc-shipping' ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * AJAX handler for rate calculation.
 */
add_action( 'wp_ajax_wtcc_calculate_simple_rates', 'wtcc_ajax_calculate_simple_rates' );
function wtcc_ajax_calculate_simple_rates() {
	check_ajax_referer( 'wtcc_simple_calc', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wtc-shipping' ) ) );
	}
	
	$weight      = floatval( wp_unslash( $_POST['weight'] ?? 0 ) );
	$weight_unit = sanitize_text_field( wp_unslash( $_POST['weight_unit'] ?? 'oz' ) );
	$origin_zip  = sanitize_text_field( wp_unslash( $_POST['origin_zip'] ?? '' ) );
	$dest_zip    = sanitize_text_field( wp_unslash( $_POST['dest_zip'] ?? '' ) );
	
	if ( $weight <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid weight.', 'wtc-shipping' ) ) );
	}
	
	if ( ! preg_match( '/^\d{5}$/', $origin_zip ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid 5-digit origin ZIP code.', 'wtc-shipping' ) ) );
	}
	
	if ( ! preg_match( '/^\d{5}$/', $dest_zip ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid 5-digit destination ZIP code.', 'wtc-shipping' ) ) );
	}
	
	$weight_oz = ( $weight_unit === 'lb' ) ? $weight * 16 : $weight;
	
	$rates = wtcc_calculate_all_rates( $weight_oz, $origin_zip, $dest_zip );
	
	if ( empty( $rates ) ) {
		wp_send_json_error( array( 'message' => __( 'No rates available for this route.', 'wtc-shipping' ) ) );
	}
	
	wp_send_json_success( array( 'rates' => $rates ) );
}

/**
 * Calculate rates for all available services.
 */
function wtcc_calculate_all_rates( $weight_oz, $origin_zip, $dest_zip ) {
	$rates = array();
	
	if ( function_exists( 'wtcc_shipping_calculate_cost_auto' ) ) {
		$services = array(
			'ground'   => __( 'USPS Ground Advantage', 'wtc-shipping' ),
			'priority' => __( 'USPS Priority Mail', 'wtc-shipping' ),
			'express'  => __( 'USPS Priority Mail Express', 'wtc-shipping' ),
		);
		
		foreach ( $services as $key => $label ) {
			$cost = wtcc_shipping_calculate_cost_auto( $key, $weight_oz, 'usa' );
			if ( $cost && $cost > 0 ) {
				$rates[] = array(
					'service' => $label,
					'cost'    => number_format( $cost, 2 ),
				);
			}
		}
	}
	
	return $rates;
}
