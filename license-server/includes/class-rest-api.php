<?php
/**
 * REST API for license validation.
 *
 * @package InkfinitLicenseServer
 */

defined( 'ABSPATH' ) || exit;

class BILS_REST_API {

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'inkfinit/v1',
			'/license/validate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'validate_license' ),
				'permission_callback' => '__return_true', // Public endpoint.
			)
		);
	}

	/**
	 * Validate a license key via REST API.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response Response.
	 */
	public static function validate_license( $request ) {
		$params = $request->get_json_params();

		if ( ! isset( $params['license_key'] ) ) {
			return new WP_REST_Response(
				array(
					'valid'  => false,
					'reason' => 'missing_license_key',
				),
				400
			);
		}

		$license_key = sanitize_text_field( $params['license_key'] );
		$result = BILS_License_Manager::validate( $license_key );

		$status_code = $result['valid'] ? 200 : 400;

		return new WP_REST_Response( $result, $status_code );
	}
}
