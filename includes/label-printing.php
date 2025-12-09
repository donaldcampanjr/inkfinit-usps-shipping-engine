<?php
/**
 * Shipping Label Printing Integration
 *
 * Full integration with WooCommerce label printing plugins.
 * Exposes order data for USPS label generation via third-party services.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register WTC methods for label printing support.
 * Works with: WooCommerce Shipping, ShipStation, Shippo, EasyPost, etc.
 */
add_action( 'plugins_loaded', 'wtcc_shipping_register_label_provider', 20 );
function wtcc_shipping_register_label_provider() {
	if ( function_exists( 'wtcc_is_pro' ) && ! wtcc_is_pro() ) {
		return;
	}
	add_filter( 'woocommerce_shipping_methods_label_support', 'wtcc_shipping_add_label_support' );
	add_filter( 'woocommerce_shipping_method_supports_shipping_labels', 'wtcc_shipping_method_supports_labels', 10, 2 );
}

/**
 * Add WTC shipping methods to label support list.
 */
function wtcc_shipping_add_label_support( $methods ) {
	$wtc_methods = array( 'wtc_first_class', 'wtc_ground', 'wtc_priority', 'wtc_express' );
	foreach ( $wtc_methods as $method ) {
		if ( ! in_array( $method, $methods, true ) ) {
			$methods[] = $method;
		}
	}
	return $methods;
}

/**
 * Mark WTC methods as supporting labels.
 */
function wtcc_shipping_method_supports_labels( $supports, $method ) {
	if ( isset( $method->id ) && $method->id && strpos( $method->id, 'wtc_' ) === 0 ) {
		return true;
	}
	return $supports;
}

/**
 * Get complete shipment data for label generation.
 *
 * @param int $order_id WooCommerce order ID.
 * @return array Shipment data for label generation.
 */
function wtcc_shipping_get_shipment_data( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return array();
	}

	$origin = array(
		'name'     => get_bloginfo( 'name' ),
		'company'  => get_option( 'woocommerce_store_company', '' ),
		'address1' => get_option( 'woocommerce_store_address', '' ),
		'address2' => get_option( 'woocommerce_store_address_2', '' ),
		'city'     => get_option( 'woocommerce_store_city', '' ),
		'state'    => get_option( 'woocommerce_store_state', '' ),
		'zip'      => get_option( 'wtcc_origin_zip', get_option( 'woocommerce_store_postcode', '' ) ),
		'country'  => get_option( 'woocommerce_default_country', 'US' ),
		'phone'    => get_option( 'woocommerce_store_phone', '' ),
		'email'    => get_option( 'admin_email', '' ),
	);

	$country = isset( $origin['country'] ) && $origin['country'] ? (string) $origin['country'] : '';
	if ( $country && strpos( $country, ':' ) !== false ) {
		list( $origin['country'], $origin['state'] ) = explode( ':', $country );
	}

	$destination = array(
		'name'     => $order->get_formatted_shipping_full_name(),
		'company'  => $order->get_shipping_company(),
		'address1' => $order->get_shipping_address_1(),
		'address2' => $order->get_shipping_address_2(),
		'city'     => $order->get_shipping_city(),
		'state'    => $order->get_shipping_state(),
		'zip'      => $order->get_shipping_postcode(),
		'country'  => $order->get_shipping_country(),
		'phone'    => $order->get_billing_phone(),
		'email'    => $order->get_billing_email(),
	);

	$package    = wtcc_shipping_get_package_details( $order );
	$dimensions = array( 'length' => 10, 'width' => 8, 'height' => 4 );

	if ( function_exists( 'wtcc_shipping_pack_items' ) ) {
		$order_items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$order_items[] = array(
					'length' => floatval( $product->get_length() ?: 6 ),
					'width'  => floatval( $product->get_width() ?: 4 ),
					'height' => floatval( $product->get_height() ?: 2 ),
					'weight' => floatval( $product->get_weight() ?: 0.5 ),
					'qty'    => $item->get_quantity(),
				);
			}
		}

		if ( ! empty( $order_items ) ) {
			$packed = wtcc_shipping_pack_items( $order_items );
			if ( ! empty( $packed['packages'][0]['box'] ) ) {
				$box        = $packed['packages'][0]['box'];
				$dimensions = array(
					'length' => $box['length'],
					'width'  => $box['width'],
					'height' => $box['height'],
				);
			}
		}
	}

	$package['dimensions'] = $dimensions;

	$shipping_items  = $order->get_shipping_methods();
	$shipping_method = array();

	foreach ( $shipping_items as $item ) {
		$method_id = $item->get_method_id();
		if ( $method_id && strpos( $method_id, 'wtc_' ) === 0 ) {
			$shipping_method = array(
				'method_id'    => $item->get_method_id(),
				'method_title' => $item->get_method_title(),
				'cost'         => $item->get_total(),
				'usps_service' => wtcc_shipping_get_usps_service_name( $item->get_method_id() ),
				'mail_class'   => wtcc_map_method_to_mail_class( $item->get_method_id() ),
			);
			break;
		}
	}

	return array(
		'order_id'        => $order_id,
		'order_number'    => $order->get_order_number(),
		'order_date'      => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
		'carrier'         => 'USPS',
		'origin'          => $origin,
		'destination'     => $destination,
		'package'         => $package,
		'shipping_method' => $shipping_method,
		'items'           => wtcc_shipping_get_order_items_for_label( $order ),
		'customs'         => wtcc_shipping_get_customs_info( $order ),
	);
}

/**
 * Get package details from order.
 */
function wtcc_shipping_get_package_details( $order ) {
	$total_weight_oz = 0;
	$items_count     = 0;

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}

		$qty          = $item->get_quantity();
		$items_count += $qty;
		$weight       = $product->get_weight();

		if ( $weight ) {
			$weight_unit = get_option( 'woocommerce_weight_unit', 'lbs' );
			if ( 'lbs' === $weight_unit ) {
				$total_weight_oz += $weight * 16 * $qty;
			} elseif ( 'kg' === $weight_unit ) {
				$total_weight_oz += $weight * 35.274 * $qty;
			} elseif ( 'g' === $weight_unit ) {
				$total_weight_oz += $weight * 0.035274 * $qty;
			} else {
				$total_weight_oz += $weight * $qty;
			}
		}
	}

	return array(
		'weight_oz'      => round( $total_weight_oz, 2 ),
		'weight_lbs'     => round( $total_weight_oz / 16, 2 ),
		'items_count'    => $items_count,
		'declared_value' => $order->get_subtotal(),
	);
}

/**
 * Get order items formatted for label/customs.
 */
function wtcc_shipping_get_order_items_for_label( $order ) {
	$items = array();

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}

		$items[] = array(
			'name'           => $item->get_name(),
			'sku'            => $product->get_sku(),
			'quantity'       => $item->get_quantity(),
			'value'          => $item->get_subtotal() / $item->get_quantity(),
			'weight'         => $product->get_weight() ?: 0,
			'hs_code'        => $product->get_meta( '_hs_tariff_code', true ) ?: '',
			'origin_country' => $product->get_meta( '_country_of_origin', true ) ?: 'US',
		);
	}

	return $items;
}

/**
 * Get customs information for international shipments.
 */
function wtcc_shipping_get_customs_info( $order ) {
	$country = $order->get_shipping_country();

	if ( 'US' === $country ) {
		return null;
	}

	$items       = wtcc_shipping_get_order_items_for_label( $order );
	$total_value = array_sum( array_column( $items, 'value' ) );

	return array(
		'contents_type'        => 'MERCHANDISE',
		'contents_explanation' => 'Band Merchandise',
		'restriction_type'     => 'NONE',
		'non_delivery_option'  => 'RETURN',
		'customs_items'        => $items,
		'total_value'          => $total_value,
		'currency'             => $order->get_currency(),
	);
}

/**
 * Map WTC method ID to USPS service name.
 */
function wtcc_shipping_get_usps_service_name( $method_id ) {
	$services = array(
		'wtc_first_class' => 'USPS First Class Mail',
		'wtc_ground'      => 'USPS Ground Advantage',
		'wtc_priority'    => 'USPS Priority Mail',
		'wtc_express'     => 'USPS Priority Mail Express',
	);
	return isset( $services[ $method_id ] ) ? $services[ $method_id ] : 'USPS';
}

/**
 * Map WTC method ID to USPS API mail class code.
 */
function wtcc_map_method_to_mail_class( $method_id ) {
	$mail_classes = array(
		'wtc_first_class' => 'FIRST_CLASS_MAIL',
		'wtc_ground'      => 'USPS_GROUND_ADVANTAGE',
		'wtc_priority'    => 'PRIORITY_MAIL',
		'wtc_express'     => 'PRIORITY_MAIL_EXPRESS',
	);
	return isset( $mail_classes[ $method_id ] ) ? $mail_classes[ $method_id ] : 'USPS_GROUND_ADVANTAGE';
}

/**
 * Create shipping label for order.
 *
 * @param int   $order_id Order ID.
 * @param array $options  Label options (format, size, etc).
 * @return array|WP_Error Label data or error.
 */
function wtcc_create_shipping_label_for_order( $order_id, $options = array() ) {
	if ( ! function_exists( 'wtcc_usps_create_domestic_label' ) ) {
		require_once dirname( __FILE__ ) . '/usps-label-api.php';
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return new WP_Error( 'invalid_order', 'Order not found' );
	}

	$shipment_data = wtcc_shipping_get_shipment_data( $order_id );
	if ( empty( $shipment_data ) ) {
		return new WP_Error( 'invalid_shipment', 'Could not build shipment data' );
	}

	$shipment = array(
		'from' => array(
			'company'   => $shipment_data['origin']['company'],
			'address_1' => $shipment_data['origin']['address1'],
			'address_2' => $shipment_data['origin']['address2'],
			'city'      => $shipment_data['origin']['city'],
			'state'     => $shipment_data['origin']['state'],
			'postcode'  => $shipment_data['origin']['zip'],
			'country'   => $shipment_data['origin']['country'],
		),
		'to' => array(
			'first_name' => $order->get_shipping_first_name(),
			'last_name'  => $order->get_shipping_last_name(),
			'company'    => $shipment_data['destination']['company'],
			'address_1'  => $shipment_data['destination']['address1'],
			'address_2'  => $shipment_data['destination']['address2'],
			'city'       => $shipment_data['destination']['city'],
			'state'      => $shipment_data['destination']['state'],
			'postcode'   => $shipment_data['destination']['zip'],
			'country'    => $shipment_data['destination']['country'],
		),
		'weight'        => $shipment_data['package']['weight_lbs'],
		'length'        => $shipment_data['package']['dimensions']['length'] ?? 10,
		'width'         => $shipment_data['package']['dimensions']['width'] ?? 8,
		'height'        => $shipment_data['package']['dimensions']['height'] ?? 4,
		'mail_class'    => $shipment_data['shipping_method']['mail_class'] ?? 'USPS_GROUND_ADVANTAGE',
		'insured_value' => $shipment_data['package']['declared_value'],
	);

	$is_international = $shipment_data['destination']['country'] !== 'US';

	if ( $is_international && $shipment_data['customs'] ) {
		$shipment['customs_items'] = array();
		foreach ( $shipment_data['items'] as $item ) {
			$shipment['customs_items'][] = array(
				'description'    => $item['name'],
				'quantity'       => $item['quantity'],
				'value'          => $item['value'],
				'weight'         => $item['weight'],
				'hs_code'        => $item['hs_code'],
				'origin_country' => $item['origin_country'],
			);
		}
		$shipment['content_type'] = $shipment_data['customs']['contents_type'];
		$shipment['mail_class']   = 'PRIORITY_MAIL_INTERNATIONAL';
	}

	if ( $is_international ) {
		$result = wtcc_usps_create_international_label( $shipment, $options );
	} else {
		$result = wtcc_usps_create_domestic_label( $shipment, $options );
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	wtcc_save_label_to_order( $order, $result );

	$order->add_order_note( sprintf(
		__( 'USPS %s label created. Tracking: %s | Postage: $%s', 'wtc-shipping' ),
		$is_international ? 'International' : 'Domestic',
		$result['tracking_number'],
		number_format( $result['postage'], 2 )
	) );

	do_action( 'wtcc_label_printed', $order_id, $result['tracking_number'] );

	return $result;
}

/**
 * REST API endpoints for third-party integrations.
 */
add_action( 'rest_api_init', 'wtcc_shipping_register_rest_routes' );
function wtcc_shipping_register_rest_routes() {
	register_rest_route( 'wtc-shipping/v1', '/shipment/(?P<order_id>\d+)', array(
		'methods'             => 'GET',
		'callback'            => 'wtcc_shipping_rest_get_shipment',
		'permission_callback' => function() {
			return current_user_can( 'manage_woocommerce' );
		},
		'args' => array(
			'order_id' => array( 'required' => true, 'type' => 'integer' ),
		),
	) );

	register_rest_route( 'wtc-shipping/v1', '/tracking/(?P<order_id>\d+)', array(
		'methods'             => 'POST',
		'callback'            => 'wtcc_shipping_rest_update_tracking',
		'permission_callback' => function() {
			return current_user_can( 'manage_woocommerce' );
		},
		'args' => array(
			'order_id'        => array( 'required' => true, 'type' => 'integer' ),
			'tracking_number' => array( 'required' => true, 'type' => 'string' ),
		),
	) );
}

/**
 * REST: Get shipment data for an order.
 */
function wtcc_shipping_rest_get_shipment( $request ) {
	$order_id = $request->get_param( 'order_id' );
	$data     = wtcc_shipping_get_shipment_data( $order_id );

	if ( empty( $data ) ) {
		return new WP_Error( 'not_found', 'Order not found', array( 'status' => 404 ) );
	}

	return rest_ensure_response( $data );
}

/**
 * REST: Update tracking number for an order.
 */
function wtcc_shipping_rest_update_tracking( $request ) {
	$order_id = $request->get_param( 'order_id' );
	$tracking = sanitize_text_field( $request->get_param( 'tracking_number' ) );

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return new WP_Error( 'not_found', 'Order not found', array( 'status' => 404 ) );
	}

	$order->update_meta_data( '_wtcc_tracking_number', $tracking );
	$order->update_meta_data( '_wtcc_tracking_carrier', 'USPS' );
	$order->update_meta_data( '_wtcc_tracking_url', 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking );
	$order->save();

	$order->add_order_note( sprintf( __( 'USPS tracking number added: %s', 'wtc-shipping' ), $tracking ) );
	do_action( 'wtcc_shipping_tracking_updated', $order_id, $tracking, 'USPS' );

	return rest_ensure_response( array(
		'success'  => true,
		'order_id' => $order_id,
		'tracking' => $tracking,
	) );
}

/**
 * Add tracking column to WooCommerce orders list.
 */
add_filter( 'manage_edit-shop_order_columns', 'wtcc_shipping_add_tracking_column' );
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'wtcc_shipping_add_tracking_column' );
function wtcc_shipping_add_tracking_column( $columns ) {
	$new_columns = array();
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'order_status' === $key ) {
			$new_columns['wtc_tracking'] = __( 'Tracking', 'wtc-shipping' );
		}
	}
	return $new_columns;
}

/**
 * Display tracking in orders list.
 */
add_action( 'manage_shop_order_posts_custom_column', 'wtcc_shipping_render_tracking_column', 10, 2 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'wtcc_shipping_render_tracking_column', 10, 2 );
function wtcc_shipping_render_tracking_column( $column, $order_id_or_order ) {
	if ( 'wtc_tracking' !== $column ) {
		return;
	}

	$order = is_a( $order_id_or_order, 'WC_Order' ) ? $order_id_or_order : wc_get_order( $order_id_or_order );
	if ( ! $order ) {
		return;
	}

	$tracking = $order->get_meta( '_wtcc_tracking_number', true );

	if ( $tracking ) {
		printf(
			'<a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=%s" target="_blank" class="button button-small"><span class="dashicons dashicons-location"></span> %s</a>',
			esc_attr( $tracking ),
			esc_html( substr( $tracking, -8 ) )
		);
	} else {
		echo '<span class="description">—</span>';
	}
}

/**
 * Add tracking info to customer emails.
 */
add_action( 'woocommerce_email_order_meta', 'wtcc_shipping_add_tracking_to_email', 10, 3 );
function wtcc_shipping_add_tracking_to_email( $order, $sent_to_admin, $plain_text ) {
	$tracking = $order->get_meta( '_wtcc_tracking_number', true );

	if ( ! $tracking ) {
		return;
	}

	$tracking_url = 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking;

	if ( $plain_text ) {
		echo "\n\n==========\n";
		echo "TRACKING INFORMATION\n";
		echo 'Tracking Number: ' . $tracking . "\n";
		echo "Carrier: USPS\n";
		echo 'Track your package: ' . $tracking_url . "\n";
		echo "==========\n\n";
	} else {
		echo '<h3>' . esc_html__( 'Tracking Information', 'wtc-shipping' ) . '</h3>';
		echo '<p><strong>' . esc_html__( 'Tracking Number:', 'wtc-shipping' ) . '</strong> ' . esc_html( $tracking ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Carrier:', 'wtc-shipping' ) . '</strong> USPS</p>';
		echo '<p><a href="' . esc_url( $tracking_url ) . '" class="button">' . esc_html__( 'Track Package', 'wtc-shipping' ) . '</a></p>';
	}
}

/**
 * Add tracking to My Account order details.
 */
add_action( 'woocommerce_order_details_after_order_table', 'wtcc_shipping_add_tracking_to_account' );
function wtcc_shipping_add_tracking_to_account( $order ) {
	$tracking = $order->get_meta( '_wtcc_tracking_number', true );

	if ( ! $tracking ) {
		return;
	}

	$tracking_url = 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking;
	?>
	<section class="woocommerce-tracking-info">
		<h2><?php esc_html_e( 'Tracking Information', 'wtc-shipping' ); ?></h2>
		<div class="woocommerce-message woocommerce-message--info">
			<p><strong><?php esc_html_e( 'Tracking Number:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( $tracking ); ?></p>
			<p><strong><?php esc_html_e( 'Carrier:', 'wtc-shipping' ); ?></strong> USPS</p>
			<p><a href="<?php echo esc_url( $tracking_url ); ?>" target="_blank" class="button"><?php esc_html_e( 'Track Package', 'wtc-shipping' ); ?></a></p>
		</div>
	</section>
	<?php
}

/**
 * Metabox for tracking on order edit page.
 */
add_action( 'add_meta_boxes', 'wtcc_shipping_add_tracking_metabox' );
function wtcc_shipping_add_tracking_metabox() {
	$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' )
		&& wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';

	add_meta_box(
		'wtcc_tracking_metabox',
		__( 'USPS Tracking', 'wtc-shipping' ),
		'wtcc_shipping_tracking_metabox_content',
		$screen,
		'side',
		'high'
	);
}

/**
 * Tracking metabox content.
 */
function wtcc_shipping_tracking_metabox_content( $post_or_order ) {
	$order = is_a( $post_or_order, 'WC_Order' ) ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}

	$tracking = $order->get_meta( '_wtcc_tracking_number', true );
	wp_nonce_field( 'wtcc_save_tracking', 'wtcc_tracking_nonce' );
	?>
	<p>
		<label for="wtcc_tracking_number"><strong><?php esc_html_e( 'USPS Tracking Number', 'wtc-shipping' ); ?></strong></label>
	</p>
	<p>
		<input type="text" id="wtcc_tracking_number" name="wtcc_tracking_number" value="<?php echo esc_attr( $tracking ); ?>" placeholder="9400111899223456789012" class="widefat">
	</p>
	<?php if ( $tracking ) : ?>
	<p>
		<a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=<?php echo esc_attr( $tracking ); ?>" target="_blank" class="button button-secondary widefat"><?php esc_html_e( 'Track on USPS.com', 'wtc-shipping' ); ?></a>
	</p>
	<?php endif; ?>
	<?php
}

/**
 * Save tracking from metabox.
 */
add_action( 'woocommerce_process_shop_order_meta', 'wtcc_shipping_save_tracking_metabox' );
add_action( 'woocommerce_update_order', 'wtcc_shipping_save_tracking_metabox' );
function wtcc_shipping_save_tracking_metabox( $order_id ) {
	if ( ! isset( $_POST['wtcc_tracking_nonce'] ) || ! wp_verify_nonce( $_POST['wtcc_tracking_nonce'], 'wtcc_save_tracking' ) ) {
		return;
	}

	if ( ! isset( $_POST['wtcc_tracking_number'] ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$tracking     = sanitize_text_field( $_POST['wtcc_tracking_number'] );
	$old_tracking = $order->get_meta( '_wtcc_tracking_number', true );

	if ( $tracking !== $old_tracking ) {
		$order->update_meta_data( '_wtcc_tracking_number', $tracking );
		$order->update_meta_data( '_wtcc_tracking_carrier', 'USPS' );
		$order->update_meta_data( '_wtcc_tracking_url', 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking );
		$order->save();

		if ( $tracking ) {
			$order->add_order_note( sprintf( __( 'USPS tracking number updated: %s', 'wtc-shipping' ), $tracking ) );
			do_action( 'wtcc_shipping_tracking_updated', $order_id, $tracking, 'USPS' );
		}
	}
}
