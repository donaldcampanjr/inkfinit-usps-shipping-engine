<?php
/**
 * License manager for creating, validating, and managing licenses.
 *
 * @package InkfinitLicenseServer
 */

defined( 'ABSPATH' ) || exit;

class BILS_License_Manager {

	/**
	 * Create a new license for an order.
	 *
	 * @param int    $order_id WooCommerce order ID.
	 * @param string $tier     License tier: 'pro' or 'enterprise'.
	 * @return array|false License data on success, false on failure.
	 */
	public static function create_for_order( $order_id, $tier = 'pro' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		// Generate key with tier embedded.
		$license_key = BILS_Key_Generator::generate( $order_id, $tier );

		$data = array(
			'license_key'    => $license_key,
			'order_id'       => $order_id,
			'customer_email' => $order->get_billing_email(),
			'customer_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'tier'           => $tier,
			'expires_at'     => gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) ),
		);

		if ( BILS_Database::insert( $data ) ) {
			return $data;
		}

		return false;
	}

	/**
	 * Validate a license key.
	 *
	 * @param string $license_key License key.
	 * @return array Validation result.
	 */
	public static function validate( $license_key ) {
		if ( ! BILS_Key_Generator::is_valid_format( $license_key ) ) {
			return array(
				'valid'  => false,
				'reason' => 'invalid_format',
			);
		}

		$license = BILS_Database::get_by_key( $license_key );

		if ( ! $license ) {
			return array(
				'valid'  => false,
				'reason' => 'not_found',
			);
		}

		if ( 'active' !== $license->status ) {
			return array(
				'valid'  => false,
				'reason' => $license->status,
			);
		}

		if ( $license->expires_at && current_time( 'mysql' ) > $license->expires_at ) {
			BILS_Database::update_status( $license->id, 'expired' );
			return array(
				'valid'  => false,
				'reason' => 'expired',
			);
		}

		BILS_Database::record_validation( $license->id );

		// Get tier from database or extract from key.
		$tier = ! empty( $license->tier ) 
			? $license->tier 
			: BILS_Key_Generator::get_tier_from_key( $license_key );

		return array(
			'valid'        => true,
			'license_key'  => $license_key,
			'tier'         => $tier,
			'order_id'     => (int) $license->order_id,
			'customer'     => $license->customer_name,
			'expires_at'   => $license->expires_at,
			'validations'  => (int) $license->validation_count + 1,
		);
	}

	/**
	 * Revoke a license.
	 *
	 * @param int $license_id License database ID.
	 * @return bool True on success.
	 */
	public static function revoke( $license_id ) {
		BILS_Database::update_status( $license_id, 'revoked' );
		return true;
	}

	/**
	 * Activate a revoked license.
	 *
	 * @param int $license_id License database ID.
	 * @return bool True on success.
	 */
	public static function activate( $license_id ) {
		BILS_Database::update_status( $license_id, 'active' );
		return true;
	}
}
