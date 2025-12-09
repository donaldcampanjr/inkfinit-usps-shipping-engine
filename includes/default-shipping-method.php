<?php
/**
 * Default Shipping Method Selection
 * 
 * Allows admins to set a default shipping method that's pre-selected at checkout
 * Feature Request: 5 votes on WooCommerce
 * 
 * @package WTC_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set default shipping method at checkout
 */
add_filter( 'woocommerce_shipping_chosen_method', 'wtcc_set_default_shipping_method', 10, 3 );
function wtcc_set_default_shipping_method( $chosen_method, $available_methods, $package_index ) {
	// Only apply on first load (when no method chosen yet)
	if ( ! empty( $chosen_method ) && isset( $available_methods[ $chosen_method ] ) ) {
		return $chosen_method;
	}

	// Default to USPS Ground Advantage if available
	foreach ( $available_methods as $key => $method ) {
		if ( strpos( $key, 'wtc_ground' ) === 0 ) {
			return $key;
		}
	}
	
	$default_method = get_option( 'wtcc_default_shipping_method', '' );
	
	if ( empty( $default_method ) ) {
		// No default set - fall back to cheapest
		$default_method = get_option( 'wtcc_default_shipping_logic', 'cheapest' );
	}

	// If specific method is set
	if ( strpos( $default_method, 'wtc_' ) === 0 ) {
		// Find this method in available methods
		foreach ( $available_methods as $key => $method ) {
			if ( strpos( $key, $default_method ) === 0 ) {
				return $key;
			}
		}
	}
	
	// Logic-based defaults
	switch ( $default_method ) {
		case 'cheapest':
			return wtcc_get_cheapest_method( $available_methods );
			
		case 'fastest':
			return wtcc_get_fastest_method( $available_methods );
			
		case 'priority':
			// Find Priority Mail
			foreach ( $available_methods as $key => $method ) {
				if ( strpos( $key, 'wtc_priority' ) === 0 ) {
					return $key;
				}
			}
			break;
			
		case 'ground':
			// Find Ground Advantage
			foreach ( $available_methods as $key => $method ) {
				if ( strpos( $key, 'wtc_ground' ) === 0 ) {
					return $key;
				}
			}
			break;
	}
	
	// Fall back to first available
	return $chosen_method;
}

/**
 * Get cheapest available shipping method
 */
function wtcc_get_cheapest_method( $available_methods ) {
	$cheapest_key = '';
	$cheapest_cost = PHP_FLOAT_MAX;
	
	foreach ( $available_methods as $key => $method ) {
		$cost = (float) $method->cost;
		if ( $cost < $cheapest_cost ) {
			$cheapest_cost = $cost;
			$cheapest_key = $key;
		}
	}
	
	return $cheapest_key;
}

/**
 * Get fastest available shipping method
 */
function wtcc_get_fastest_method( $available_methods ) {
	// Priority order: express > priority > ground
	$priority_order = array(
		'wtc_express'     => 1,
		'wtc_priority'    => 2,
		'wtc_ground'      => 3,
		'wtc_media_mail'  => 4,
	);
	
	$fastest_key = '';
	$fastest_priority = 999;
	
	foreach ( $available_methods as $key => $method ) {
		foreach ( $priority_order as $method_prefix => $priority ) {
			if ( strpos( $key, $method_prefix ) === 0 && $priority < $fastest_priority ) {
				$fastest_priority = $priority;
				$fastest_key = $key;
			}
		}
	}
	
	return $fastest_key;
}

/**
 * Add default shipping method setting to admin
 */
add_action( 'admin_init', 'wtcc_register_default_method_settings' );
function wtcc_register_default_method_settings() {
	register_setting( 'wtcc_shipping_settings', 'wtcc_default_shipping_method' );
	register_setting( 'wtcc_shipping_settings', 'wtcc_default_shipping_logic' );
}

/**
 * Render default shipping method setting field
 */
function wtcc_render_default_method_setting() {
	$current = get_option( 'wtcc_default_shipping_method', '' );
	$logic = get_option( 'wtcc_default_shipping_logic', 'cheapest' );
	
	$methods = array(
		''                => __( '— Use Logic Below —', 'wtc-shipping' ),
		'wtc_ground'      => __( 'USPS Ground Advantage', 'wtc-shipping' ),
		'wtc_priority'    => __( 'USPS Priority Mail', 'wtc-shipping' ),
		'wtc_express'     => __( 'USPS Express Mail', 'wtc-shipping' ),
	);
	
	$logic_options = array(
		'cheapest' => __( 'Always select cheapest option', 'wtc-shipping' ),
		'fastest'  => __( 'Always select fastest option', 'wtc-shipping' ),
		'priority' => __( 'Default to Priority Mail', 'wtc-shipping' ),
		'ground'   => __( 'Default to Ground Advantage', 'wtc-shipping' ),
	);
	
	?>
	<div class="wtcc-setting-group">
		<h4><?php esc_html_e( 'Default Shipping Method', 'wtc-shipping' ); ?></h4>
		<p class="description"><?php esc_html_e( 'Pre-select a shipping method at checkout to speed up the purchase process.', 'wtc-shipping' ); ?></p>
		
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Specific Method', 'wtc-shipping' ); ?></th>
				<td>
					<select name="wtcc_default_shipping_method">
						<?php foreach ( $methods as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Logic', 'wtc-shipping' ); ?></th>
				<td>
					<select name="wtcc_default_shipping_logic">
						<?php foreach ( $logic_options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $logic, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Used when no specific method is selected above, or if selected method is unavailable.', 'wtc-shipping' ); ?></p>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

/**
 * Add "Remember customer's last choice" feature
 */
add_action( 'woocommerce_checkout_update_order_meta', 'wtcc_save_customer_shipping_preference' );
function wtcc_save_customer_shipping_preference( $order_id ) {
	$order = wc_get_order( $order_id );
	$user_id = $order->get_user_id();
	
	if ( ! $user_id ) {
		return;
	}
	
	// Get the shipping method used
	$shipping_methods = $order->get_shipping_methods();
	foreach ( $shipping_methods as $shipping_method ) {
		$method_id = $shipping_method->get_method_id();
		
		if ( strpos( $method_id, 'wtc_' ) === 0 ) {
			update_user_meta( $user_id, '_wtcc_preferred_shipping_method', $method_id );
			break;
		}
	}
}

/**
 * Use customer's last shipping preference if available
 */
add_filter( 'woocommerce_shipping_chosen_method', 'wtcc_use_customer_preference', 5, 3 );
function wtcc_use_customer_preference( $chosen_method, $available_methods, $package_index ) {
	// Check if "remember preference" is enabled
	if ( get_option( 'wtcc_remember_shipping_preference', 'yes' ) !== 'yes' ) {
		return $chosen_method;
	}
	
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return $chosen_method;
	}
	
	$preferred = get_user_meta( $user_id, '_wtcc_preferred_shipping_method', true );
	
	if ( $preferred ) {
		// Check if preferred method is available
		foreach ( $available_methods as $key => $method ) {
			if ( strpos( $key, $preferred ) === 0 ) {
				return $key;
			}
		}
	}
	
	return $chosen_method;
}

/**
 * Register remember preference setting
 */
add_action( 'admin_init', 'wtcc_register_preference_settings' );
function wtcc_register_preference_settings() {
	register_setting( 'wtcc_shipping_settings', 'wtcc_remember_shipping_preference' );
}
