<?php
/**
 * Database management for license keys.
 *
 * @package InkfinitLicenseServer
 */

defined( 'ABSPATH' ) || exit;

class BILS_Database {

	/**
	 * Create the license keys table.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'bils_license_keys';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			license_key VARCHAR(100) NOT NULL UNIQUE,
			order_id BIGINT(20) UNSIGNED NOT NULL,
			customer_email VARCHAR(255) NOT NULL,
			customer_name VARCHAR(255) NOT NULL,
			tier VARCHAR(20) DEFAULT 'pro',
			status VARCHAR(20) DEFAULT 'active',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME NULL,
			last_validated_at DATETIME NULL,
			validation_count INT DEFAULT 0,
			KEY order_id (order_id),
			KEY license_key (license_key),
			KEY status (status),
			KEY tier (tier)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a new license key.
	 *
	 * @param array $data License key data.
	 * @return int|false Insert ID or false on failure.
	 */
	public static function insert( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bils_license_keys';

		$result = $wpdb->insert(
			$table_name,
			array(
				'license_key'    => $data['license_key'],
				'order_id'       => $data['order_id'],
				'customer_email' => $data['customer_email'],
				'customer_name'  => $data['customer_name'],
				'tier'           => isset( $data['tier'] ) ? $data['tier'] : 'pro',
				'status'         => 'active',
				'expires_at'     => isset( $data['expires_at'] ) ? $data['expires_at'] : null,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a license key by key string.
	 *
	 * @param string $license_key License key.
	 * @return object|null License record or null.
	 */
	public static function get_by_key( $license_key ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bils_license_keys';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE license_key = %s LIMIT 1",
				$license_key
			)
		);
	}

	/**
	 * Get license by order ID.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return object|null License record or null.
	 */
	public static function get_by_order( $order_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bils_license_keys';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE order_id = %d LIMIT 1",
				$order_id
			)
		);
	}

	/**
	 * Update last validated timestamp and increment validation count.
	 *
	 * @param int $id License ID.
	 */
	public static function record_validation( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bils_license_keys';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_name SET last_validated_at = %s, validation_count = validation_count + 1 WHERE id = %d",
				current_time( 'mysql' ),
				$id
			)
		);
	}

	/**
	 * Update license status.
	 *
	 * @param int    $id     License ID.
	 * @param string $status New status (active/revoked/expired).
	 */
	public static function update_status( $id, $status ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bils_license_keys';

		$wpdb->update(
			$table_name,
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get all licenses (for admin listing).
	 *
	 * @param int $limit  Results per page.
	 * @param int $offset Pagination offset.
	 * @return array License records.
	 */
	public static function get_all( $limit = 20, $offset = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bils_license_keys';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);
	}

	/**
	 * Get total license count.
	 *
	 * @return int Total count.
	 */
	public static function count_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bils_license_keys';

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	}
}
