<?php
/**
 * Base Shipping Method Class
 * Extended by all four shipping method types
 * 
 * Must be loaded AFTER WooCommerce is initialized
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only declare class if WooCommerce is available
if ( ! class_exists( 'WC_Shipping_Method' ) ) {
	return;
}

class WTC_Shipping_Method extends WC_Shipping_Method {
	/**
	 * Shipping group ID
	 *
	 * @var string
	 */
	public $group = '';

	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );
		
		// Set title from method_title for WooCommerce display
		$this->title = $this->method_title;
		
		// Initialize settings
		$this->init_form_fields();
		$this->init_settings();
		
		// Get title from settings if available
		$this->title = $this->get_option( 'title', $this->method_title );
		
		$this->supports = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
	}

	public function calculate_shipping( $package = array() ) {
		try {
			// In free edition, do not provide live checkout rates.
			if ( function_exists( 'wtcc_is_pro' ) && ! wtcc_is_pro() ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Inkfinit Shipping: calculate_shipping skipped (free edition).' );
				}
				return;
			}

			$group = $this->group;
			$dest  = $package['destination'] ?? array();
			$debug_dest = sprintf(
				'%s-%s %s',
				$dest['country'] ?? '',
				$dest['state'] ?? '',
				$dest['postcode'] ?? ''
			);

			// Calculate cost using rule engine
			$cost = wtcc_shipping_calculate_cost( $group, $package );

			// Always add rate if we have a cost (should never be null now)
			if ( ! is_null( $cost ) && is_numeric( $cost ) && $cost >= 0 ) {
				$rate = array(
					'id'      => $this->get_rate_id(),
					'label'   => $this->method_title,
					'cost'    => (float) $cost,
					'package' => $package,
				);

				$this->add_rate( $rate );

				// Debug log for visibility in case of “no methods” reports
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( 'Inkfinit Shipping: added rate %s = %s for %s (dest %s)', $this->id, $cost, $group, $debug_dest ) );
				}
			} else {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( 'Inkfinit Shipping: skipped rate %s (invalid cost) for %s (dest %s)', $this->id, $group, $debug_dest ) );
				}
			}
		} catch ( Exception $e ) {
			error_log( 'Inkfinit Shipping Method Error: ' . $e->getMessage() );
		}
	}

	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __( 'Method Title', 'wtc-shipping' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wtc-shipping' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
		);
		
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'wtc-shipping' ),
				'label'       => __( 'Enable this shipping method', 'wtc-shipping' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes',
			),
		);
	}
}
