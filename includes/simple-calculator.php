<?php
/**
 * Simple Shipping Rate Calculator
 * 
 * FREE FEATURE - Available to all users regardless of license.
 * Shows estimated shipping rates based on weight and destination.
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
	
	// Enqueue the calculator script
	wp_enqueue_script( 
		'wtcc-simple-calculator', 
		WTCC_SHIPPING_PLUGIN_URL . 'assets/simple-calculator.js', 
		array( 'jquery' ), 
		WTCC_SHIPPING_VERSION, 
		true 
	);
	
	// Pass data to JavaScript
	wp_localize_script( 'wtcc-simple-calculator', 'wtcc_calc', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'wtcc_simple_calc' ),
		'is_pro'   => wtcc_is_pro(),
	) );
	
	$is_pro = wtcc_is_pro();
	?>
	<div class="wrap">
		<?php wtcc_admin_header( __( 'Shipping Rate Calculator', 'wtc-shipping' ) ); ?>
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<!-- Main Content -->
				<div id="post-body-content">
					<div class="postbox">
						<h2 class="hndle">
							<span class="dashicons dashicons-calculator" style="margin-right: 8px;"></span>
							<?php esc_html_e( 'Calculate Shipping Rates', 'wtc-shipping' ); ?>
						</h2>
						<div class="inside">
							<?php if ( ! $is_pro ) : ?>
								<div class="notice notice-info inline" style="margin: 0 0 20px 0;">
									<p>
										<strong><?php esc_html_e( 'Free Calculator Mode', 'wtc-shipping' ); ?></strong><br>
										<?php esc_html_e( 'Enter package details to estimate shipping costs. Upgrade to Pro for live USPS rates at checkout.', 'wtc-shipping' ); ?>
									</p>
								</div>
							<?php endif; ?>
							
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row">
										<label for="wtcc_calc_weight"><?php esc_html_e( 'Package Weight', 'wtc-shipping' ); ?></label>
									</th>
									<td>
										<input type="number" 
											   id="wtcc_calc_weight" 
											   class="regular-text" 
											   min="0.1" 
											   step="0.1" 
											   placeholder="<?php esc_attr_e( 'Enter weight', 'wtc-shipping' ); ?>"
											   style="width: 150px;">
										<select id="wtcc_calc_weight_unit" style="margin-left: 5px;">
											<option value="oz"><?php esc_html_e( 'Ounces (oz)', 'wtc-shipping' ); ?></option>
											<option value="lb"><?php esc_html_e( 'Pounds (lb)', 'wtc-shipping' ); ?></option>
										</select>
										<p class="description"><?php esc_html_e( 'Total weight of the package.', 'wtc-shipping' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="wtcc_calc_origin_zip"><?php esc_html_e( 'Origin ZIP Code', 'wtc-shipping' ); ?></label>
									</th>
									<td>
										<?php 
										$saved_origin = get_option( 'wtcc_origin_zip', '' );
										if ( empty( $saved_origin ) ) {
											$options = wtcc_get_usps_api_options();
											$saved_origin = $options['origin_zip'] ?? '';
										}
										?>
										<input type="text" 
											   id="wtcc_calc_origin_zip" 
											   class="regular-text" 
											   maxlength="5" 
											   pattern="[0-9]{5}"
											   placeholder="<?php esc_attr_e( '12345', 'wtc-shipping' ); ?>"
											   value="<?php echo esc_attr( $saved_origin ); ?>"
											   style="width: 100px;">
										<p class="description"><?php esc_html_e( 'Where you ship from (5-digit ZIP).', 'wtc-shipping' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="wtcc_calc_dest_zip"><?php esc_html_e( 'Destination ZIP Code', 'wtc-shipping' ); ?></label>
									</th>
									<td>
										<input type="text" 
											   id="wtcc_calc_dest_zip" 
											   class="regular-text" 
											   maxlength="5" 
											   pattern="[0-9]{5}"
											   placeholder="<?php esc_attr_e( '90210', 'wtc-shipping' ); ?>"
											   style="width: 100px;">
										<p class="description"><?php esc_html_e( 'Where you are shipping to (5-digit ZIP).', 'wtc-shipping' ); ?></p>
									</td>
								</tr>
							</table>
							
							<p class="submit">
								<button type="button" id="wtcc_calc_button" class="button button-primary button-large">
									<span class="dashicons dashicons-calculator" style="margin-top: 4px;"></span>
									<?php esc_html_e( 'Calculate All Rates', 'wtc-shipping' ); ?>
								</button>
								<span id="wtcc_calc_spinner" class="spinner" style="float: none; margin-left: 10px;"></span>
							</p>
						</div>
					</div>
					
					<!-- Results Section -->
					<div id="wtcc_calc_results_box" class="postbox" style="display: none;">
						<h2 class="hndle">
							<span class="dashicons dashicons-list-view" style="margin-right: 8px;"></span>
							<?php esc_html_e( 'Estimated Shipping Rates', 'wtc-shipping' ); ?>
						</h2>
						<div class="inside">
							<div id="wtcc_calc_results"></div>
							<p class="description" style="margin-top: 15px; font-style: italic;">
								<?php esc_html_e( 'Note: These are estimates based on USPS published rates. Actual rates may vary.', 'wtc-shipping' ); ?>
							</p>
						</div>
					</div>
					
					<!-- Error Section -->
					<div id="wtcc_calc_error_box" class="postbox" style="display: none;">
						<div class="inside">
							<div class="notice notice-error inline" style="margin: 0;">
								<p id="wtcc_calc_error_msg"></p>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">
					<?php if ( ! $is_pro ) : ?>
						<div class="postbox">
							<h2 class="hndle">
								<span class="dashicons dashicons-star-filled" style="color: #f0b849; margin-right: 8px;"></span>
								<?php esc_html_e( 'Upgrade to Pro', 'wtc-shipping' ); ?>
							</h2>
							<div class="inside">
								<p><?php esc_html_e( 'Get live USPS rates at checkout, label printing, tracking, and more!', 'wtc-shipping' ); ?></p>
								<ul style="list-style: disc; margin-left: 20px;">
									<li><?php esc_html_e( 'Real-time USPS rates', 'wtc-shipping' ); ?></li>
									<li><?php esc_html_e( 'Print shipping labels', 'wtc-shipping' ); ?></li>
									<li><?php esc_html_e( 'Package tracking', 'wtc-shipping' ); ?></li>
									<li><?php esc_html_e( 'Shipping presets', 'wtc-shipping' ); ?></li>
									<li><?php esc_html_e( 'Bulk tools', 'wtc-shipping' ); ?></li>
								</ul>
								<p>
									<a href="https://inkfinit.pro/pricing" target="_blank" rel="noopener" class="button button-primary">
										<?php esc_html_e( 'View Pricing', 'wtc-shipping' ); ?>
									</a>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) ); ?>" class="button">
										<?php esc_html_e( 'Enter License Key', 'wtc-shipping' ); ?>
									</a>
								</p>
							</div>
						</div>
					<?php endif; ?>
					
					<div class="postbox">
						<h2 class="hndle">
							<span class="dashicons dashicons-info" style="margin-right: 8px;"></span>
							<?php esc_html_e( 'About This Calculator', 'wtc-shipping' ); ?>
						</h2>
						<div class="inside">
							<p><?php esc_html_e( 'This calculator provides estimated USPS shipping rates based on:', 'wtc-shipping' ); ?></p>
							<ul style="list-style: disc; margin-left: 20px;">
								<li><?php esc_html_e( 'Package weight', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Origin and destination ZIP codes', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Current USPS rate tables', 'wtc-shipping' ); ?></li>
							</ul>
							<p class="description">
								<?php esc_html_e( 'Rates shown are for domestic US shipments. International rates require Pro.', 'wtc-shipping' ); ?>
							</p>
						</div>
					</div>
					
					<div class="postbox">
						<h2 class="hndle">
							<span class="dashicons dashicons-book" style="margin-right: 8px;"></span>
							<?php esc_html_e( 'USPS Services', 'wtc-shipping' ); ?>
						</h2>
						<div class="inside">
							<p><strong><?php esc_html_e( 'Ground Advantage', 'wtc-shipping' ); ?></strong><br>
							<?php esc_html_e( '2-5 business days, most economical', 'wtc-shipping' ); ?></p>
							
							<p><strong><?php esc_html_e( 'Priority Mail', 'wtc-shipping' ); ?></strong><br>
							<?php esc_html_e( '1-3 business days, includes tracking', 'wtc-shipping' ); ?></p>
							
							<p><strong><?php esc_html_e( 'Priority Mail Express', 'wtc-shipping' ); ?></strong><br>
							<?php esc_html_e( 'Overnight to 2-day, money-back guarantee', 'wtc-shipping' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			<br class="clear">
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
	
	$weight      = floatval( $_POST['weight'] ?? 0 );
	$weight_unit = sanitize_text_field( $_POST['weight_unit'] ?? 'oz' );
	$origin_zip  = sanitize_text_field( $_POST['origin_zip'] ?? '' );
	$dest_zip    = sanitize_text_field( $_POST['dest_zip'] ?? '' );
	
	// Validate inputs
	if ( $weight <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid weight.', 'wtc-shipping' ) ) );
	}
	
	if ( ! preg_match( '/^\d{5}$/', $origin_zip ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid 5-digit origin ZIP code.', 'wtc-shipping' ) ) );
	}
	
	if ( ! preg_match( '/^\d{5}$/', $dest_zip ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid 5-digit destination ZIP code.', 'wtc-shipping' ) ) );
	}
	
	// Convert weight to ounces
	$weight_oz = ( $weight_unit === 'lb' ) ? $weight * 16 : $weight;
	
	// Calculate rates using the rule engine or USPS API
	$rates = wtcc_calculate_all_rates( $weight_oz, $origin_zip, $dest_zip );
	
	if ( empty( $rates ) ) {
		wp_send_json_error( array( 'message' => __( 'No rates available for this route. Please check your ZIP codes.', 'wtc-shipping' ) ) );
	}
	
	wp_send_json_success( array( 'rates' => $rates ) );
}

/**
 * Calculate all available shipping rates.
 *
 * @param float  $weight_oz Weight in ounces.
 * @param string $origin_zip Origin ZIP code.
 * @param string $dest_zip Destination ZIP code.
 * @return array Array of rate objects.
 */
function wtcc_calculate_all_rates( $weight_oz, $origin_zip, $dest_zip ) {
	$rates = array();
	
	// Get rate configuration
	$config = wtcc_shipping_get_rates_config();
	
	// Determine zone (simplified - just use distance factor)
	$zone_multiplier = wtcc_get_zone_multiplier( $origin_zip, $dest_zip );
	
	// Calculate each service rate
	$services = array(
		'ground' => array(
			'name'     => __( 'USPS Ground Advantage', 'wtc-shipping' ),
			'delivery' => '2-5 business days',
			'icon'     => '📦',
		),
		'priority' => array(
			'name'     => __( 'USPS Priority Mail', 'wtc-shipping' ),
			'delivery' => '1-3 business days',
			'icon'     => '📫',
		),
		'express' => array(
			'name'     => __( 'USPS Priority Mail Express', 'wtc-shipping' ),
			'delivery' => '1-2 business days',
			'icon'     => '🚀',
		),
	);
	
	foreach ( $services as $key => $service ) {
		if ( ! isset( $config[ $key ] ) ) {
			continue;
		}
		
		$rate_config = $config[ $key ];
		$max_weight_oz = ( $rate_config['max_weight'] ?? 70 ) * 16;
		
		// Skip if over max weight
		if ( $weight_oz > $max_weight_oz ) {
			continue;
		}
		
		// Calculate base cost
		$base_cost = floatval( $rate_config['base_cost'] ?? 0 );
		$per_oz    = floatval( $rate_config['per_oz'] ?? 0 );
		
		$cost = $base_cost + ( $per_oz * $weight_oz );
		$cost = $cost * $zone_multiplier;
		$cost = round( $cost, 2 );
		
		// Minimum cost check
		if ( $cost < 0.01 ) {
			$cost = $base_cost > 0 ? $base_cost : 5.00; // Fallback minimum
		}
		
		$rates[] = array(
			'service'  => $key,
			'name'     => $service['name'],
			'cost'     => $cost,
			'delivery' => $service['delivery'],
			'icon'     => $service['icon'],
		);
	}
	
	// Sort by cost (cheapest first)
	usort( $rates, function( $a, $b ) {
		return $a['cost'] <=> $b['cost'];
	} );
	
	return $rates;
}

/**
 * Get zone multiplier based on distance between ZIP codes.
 *
 * @param string $origin_zip Origin ZIP code.
 * @param string $dest_zip Destination ZIP code.
 * @return float Zone multiplier.
 */
function wtcc_get_zone_multiplier( $origin_zip, $dest_zip ) {
	// Get first 3 digits of each ZIP (sectional center facility)
	$origin_prefix = substr( $origin_zip, 0, 3 );
	$dest_prefix   = substr( $dest_zip, 0, 3 );
	
	// Same SCF - local
	if ( $origin_prefix === $dest_prefix ) {
		return 0.85;
	}
	
	// Calculate rough distance based on ZIP prefix ranges
	$origin_region = intval( $origin_prefix[0] );
	$dest_region   = intval( $dest_prefix[0] );
	
	$region_diff = abs( $origin_region - $dest_region );
	
	// Zone multipliers based on region difference
	switch ( $region_diff ) {
		case 0:
			return 0.90; // Same region
		case 1:
			return 1.00; // Adjacent region
		case 2:
			return 1.10;
		case 3:
			return 1.20;
		case 4:
			return 1.30;
		default:
			return 1.40; // Cross-country
	}
}

/**
 * Get rates configuration with defaults.
 * Uses saved config or sensible defaults based on USPS rates.
 *
 * @return array Rate configuration.
 */
function wtcc_shipping_get_rates_config() {
	// Try to get saved configuration
	$saved = get_option( 'wtcc_shipping_rates_config', array() );
	
	// Default USPS-based rates (approximate 2024 rates)
	$defaults = array(
		'ground' => array(
			'base_cost'  => 4.50,
			'per_oz'     => 0.15,
			'max_weight' => 70, // pounds
		),
		'priority' => array(
			'base_cost'  => 8.50,
			'per_oz'     => 0.25,
			'max_weight' => 70,
		),
		'express' => array(
			'base_cost'  => 28.00,
			'per_oz'     => 0.50,
			'max_weight' => 70,
		),
	);
	
	return wp_parse_args( $saved, $defaults );
}
