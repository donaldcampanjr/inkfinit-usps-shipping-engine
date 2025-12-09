<?php
/**
 * Product Purchase Limits
 * 
 * Allows setting maximum quantity per product that customers can purchase in a single checkout.
 * Configurable per product with clear validation and error messages.
 * 
 * NOTE: The purchase limit field is displayed in product-preset-picker.php
 * and saved in the wtcc_shipping_save_product_preset() function.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue frontend styles for cart/checkout
 */
add_action( 'wp_enqueue_scripts', 'wtcc_enqueue_frontend_styles' );
function wtcc_enqueue_frontend_styles() {
	if ( is_cart() || is_checkout() || is_product() ) {
		wp_enqueue_style(
			'wtcc-frontend-style',
			WTCC_SHIPPING_PLUGIN_URL . 'assets/frontend-style.css',
			array(),
			WTCC_SHIPPING_VERSION
		);
	}
}

/**
 * Validate cart quantity against purchase limit
 */
add_filter( 'woocommerce_add_to_cart_validation', 'wtcc_validate_purchase_limit', 10, 5 );
function wtcc_validate_purchase_limit( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
	// Validate inputs
	if ( ! is_numeric( $product_id ) || ! is_numeric( $quantity ) ) {
		return $passed;
	}
	
	$check_id = $variation_id > 0 ? absint( $variation_id ) : absint( $product_id );
	$limit    = absint( get_post_meta( $check_id, '_wtc_purchase_limit', true ) );
	
	if ( ! $limit || $limit <= 0 ) {
		return $passed;
	}
	
	// Calculate current quantity in cart for this product
	$cart_quantity = 0;
	if ( ! empty( WC()->cart ) ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$cart_product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
			if ( $cart_product_id === $check_id ) {
				$cart_quantity += $cart_item['quantity'];
			}
		}
	}
	
	// Check if adding this quantity would exceed limit
	$total_quantity = $cart_quantity + $quantity;
	
	if ( $total_quantity > $limit ) {
		$product = wc_get_product( $check_id );
		$remaining = max( 0, $limit - $cart_quantity );
		
		if ( $cart_quantity > 0 ) {
			wc_add_notice(
				sprintf(
					__( '<strong>Purchase limit reached:</strong> "%1$s" has a maximum of <strong>%2$d per order</strong>. You currently have <span class="wtcc-limit-current">%3$d</span> in your cart. You can only add <strong>%4$d more</strong>.', 'wtc-shipping' ),
					$product->get_name(),
					$limit,
					$cart_quantity,
					$remaining
				),
				'error'
			);
		} else {
			wc_add_notice(
				sprintf(
					__( '<strong>Purchase limit:</strong> You can only purchase a maximum of <strong>%1$d</strong> of "%2$s" per order.', 'wtc-shipping' ),
					$limit,
					$product->get_name()
				),
				'error'
			);
		}
		
		return false;
	}
	
	return $passed;
}

/**
 * Validate cart totals before checkout
 */
add_action( 'woocommerce_check_cart_items', 'wtcc_validate_cart_purchase_limits' );
function wtcc_validate_cart_purchase_limits() {
	if ( ! WC()->cart ) {
		return;
	}
	
	// Track quantities by product
	$quantities = array();
	
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
		
		if ( ! isset( $quantities[ $product_id ] ) ) {
			$quantities[ $product_id ] = 0;
		}
		
		$quantities[ $product_id ] += $cart_item['quantity'];
	}
	
	// Check each product against its limit
	foreach ( $quantities as $product_id => $quantity ) {
		$limit = get_post_meta( $product_id, '_wtc_purchase_limit', true );
		
		if ( $limit > 0 && $quantity > $limit ) {
			$product = wc_get_product( $product_id );
			
			wc_add_notice(
				sprintf(
					__( '<strong>Cart Error:</strong> "%1$s" exceeds the maximum purchase limit. You have <span class="wtcc-limit-current">%2$d</span> but the maximum is <span class="wtcc-limit-max">%3$d per order</span>. Please reduce the quantity before checkout.', 'wtc-shipping' ),
					$product->get_name(),
					$quantity,
					$limit
				),
				'error'
			);
		}
	}
}

/**
 * Override max quantity in quantity input field
 */
add_filter( 'woocommerce_quantity_input_args', 'wtcc_limit_quantity_input', 10, 2 );
function wtcc_limit_quantity_input( $args, $product ) {
	$product_id = $product->get_id();
	
	// For variations, get variation ID
	if ( $product->is_type( 'variation' ) ) {
		$product_id = $product->get_id();
	}
	
	$limit = get_post_meta( $product_id, '_wtc_purchase_limit', true );
	
	if ( $limit > 0 ) {
		// Check current cart quantity
		$cart_quantity = 0;
		if ( ! empty( WC()->cart ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$cart_product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
				if ( $cart_product_id === $product_id ) {
					$cart_quantity += $cart_item['quantity'];
				}
			}
		}
		
		$remaining = max( 1, $limit - $cart_quantity );
		$args['max_value'] = min( $remaining, $limit );
		
		// Ensure input_value doesn't exceed max
		if ( isset( $args['input_value'] ) && $args['input_value'] > $args['max_value'] ) {
			$args['input_value'] = $args['max_value'];
		}
	}
	
	return $args;
}

/**
 * Display purchase limit notice on product page
 */
add_action( 'woocommerce_before_add_to_cart_button', 'wtcc_display_purchase_limit_notice' );
function wtcc_display_purchase_limit_notice() {
	global $product;
	
	$product_id = $product->get_id();
	$limit      = get_post_meta( $product_id, '_wtc_purchase_limit', true );
	
	if ( $limit > 0 ) {
		// Check if already in cart
		$cart_quantity = 0;
		if ( ! empty( WC()->cart ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$cart_product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
				if ( $cart_product_id === $product_id ) {
					$cart_quantity += $cart_item['quantity'];
				}
			}
		}
		
		$remaining = max( 0, $limit - $cart_quantity );
		$is_at_limit = $cart_quantity >= $limit;
		$notice_class = $is_at_limit ? 'wtcc-limit-warning' : '';
		
		echo '<div class="wtcc-purchase-limit-notice ' . esc_attr( $notice_class ) . '">';
		
		if ( $is_at_limit ) {
			echo '<p>';
			printf(
				__( '<span class="dashicons dashicons-warning"></span> <strong>Purchase Limit Reached:</strong> You have <strong>%1$d</strong> in your cart (maximum %2$d per order). Remove items to add more.', 'wtc-shipping' ),
				$cart_quantity,
				$limit
			);
			echo '</p>';
		} elseif ( $cart_quantity > 0 ) {
			echo '<p>';
			printf(
				__( '<strong>Purchase Limit:</strong> Maximum <strong>%1$d per order</strong>. You have <strong>%2$d</strong> in your cart. You can add <strong>%3$d more</strong>.', 'wtc-shipping' ),
				$limit,
				$cart_quantity,
				$remaining
			);
			echo '</p>';
		} else {
			echo '<p>';
			printf(
				__( '<strong>Purchase Limit:</strong> Maximum <strong>%d per order</strong>.', 'wtc-shipping' ),
				$limit
			);
			echo '</p>';
		}
		
		echo '</div>';
	}
}

/**
 * Display purchase limit notices on cart page
 */
add_action( 'woocommerce_before_cart', 'wtcc_display_cart_limit_notices' );
function wtcc_display_cart_limit_notices() {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	
	$limited_items = array();
	
	// Collect all items with limits
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
		$limit = get_post_meta( $product_id, '_wtc_purchase_limit', true );
		
		if ( $limit > 0 ) {
			$product = $cart_item['data'];
			$quantity = $cart_item['quantity'];
			$is_at_limit = $quantity >= $limit;
			
			$limited_items[] = array(
				'name'        => $product->get_name(),
				'quantity'    => $quantity,
				'limit'       => $limit,
				'at_limit'    => $is_at_limit,
			);
		}
	}
	
	if ( ! empty( $limited_items ) ) {
		foreach ( $limited_items as $item ) {
			$notice_class = $item['at_limit'] ? 'wtcc-at-limit' : '';
			
			echo '<div class="wtcc-cart-limit-notice ' . esc_attr( $notice_class ) . '">';
			echo '<div>';
			
			if ( $item['at_limit'] ) {
				echo '<p>';
				printf(
					__( '<strong>%1$s:</strong> You have <span class="wtcc-limit-current">%2$d</span> (maximum <span class="wtcc-limit-max">%3$d per order</span>). This is the maximum allowed quantity.', 'wtc-shipping' ),
					esc_html( $item['name'] ),
					$item['quantity'],
					$item['limit']
				);
				echo '</p>';
			} else {
				$remaining = $item['limit'] - $item['quantity'];
				echo '<p>';
				printf(
					__( '<strong>%1$s:</strong> You have <span class="wtcc-limit-current">%2$d</span> of <span class="wtcc-limit-max">%3$d maximum per order</span>. You can add <strong>%4$d more</strong>.', 'wtc-shipping' ),
					esc_html( $item['name'] ),
					$item['quantity'],
					$item['limit'],
					$remaining
				);
				echo '</p>';
			}
			
			echo '</div>';
			echo '</div>';
		}
	}
}

/**
 * Display purchase limit summary on checkout page
 */
add_action( 'woocommerce_review_order_before_payment', 'wtcc_display_checkout_limit_summary' );
function wtcc_display_checkout_limit_summary() {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	
	$limited_items = array();
	
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
		$limit = get_post_meta( $product_id, '_wtc_purchase_limit', true );
		
		if ( $limit > 0 ) {
			$product = $cart_item['data'];
			$limited_items[] = array(
				'name'     => $product->get_name(),
				'quantity' => $cart_item['quantity'],
				'limit'    => $limit,
			);
		}
	}
	
	if ( ! empty( $limited_items ) ) {
		echo '<div class="wtcc-checkout-limits-summary">';
		echo '<h3>' . __( 'Purchase Limits Verified', 'wtc-shipping' ) . '</h3>';
		echo '<ul>';
		
		foreach ( $limited_items as $item ) {
			echo '<li>';
			echo '<span class="wtcc-product-name">' . esc_html( $item['name'] ) . '</span>';
			echo '<span class="wtcc-limit-badge">' . esc_html( $item['quantity'] ) . ' / ' . esc_html( $item['limit'] ) . '</span>';
			echo '</li>';
		}
		
		echo '</ul>';
		echo '</div>';
	}
}

/**
 * Prevent cart quantity updates that exceed limits
 */
add_filter( 'woocommerce_update_cart_validation', 'wtcc_validate_cart_update', 10, 4 );
function wtcc_validate_cart_update( $passed, $cart_item_key, $values, $quantity ) {
	$product_id = $values['variation_id'] > 0 ? $values['variation_id'] : $values['product_id'];
	$limit = get_post_meta( $product_id, '_wtc_purchase_limit', true );
	
	if ( $limit > 0 && $quantity > $limit ) {
		$product = wc_get_product( $product_id );
		
		wc_add_notice(
			sprintf(
				__( '<strong>Quantity adjusted:</strong> "%1$s" has a maximum of <strong>%2$d per order</strong>. Your cart quantity has been set to the maximum allowed.', 'wtc-shipping' ),
				$product->get_name(),
				$limit
			),
			'notice'
		);
		
		// Force quantity to limit
		WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $limit;
		
		return false;
	}
	
	return $passed;
}

/**
 * Add helpful tooltip to quantity inputs on cart page
 */
add_action( 'woocommerce_after_cart_item_quantity_update', 'wtcc_add_cart_quantity_tooltip', 10, 4 );
function wtcc_add_cart_quantity_tooltip( $cart_item_key, $quantity, $old_quantity, $cart ) {
	$cart_item = $cart->cart_contents[ $cart_item_key ];
	$product_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
	$limit = get_post_meta( $product_id, '_wtc_purchase_limit', true );
	
	if ( $limit > 0 ) {
		// Add class to cart item for CSS styling
		add_filter( 'woocommerce_cart_item_class', function( $class, $item ) use ( $cart_item_key ) {
			if ( isset( $item['key'] ) && $item['key'] === $cart_item_key ) {
				$class .= ' has-purchase-limit';
			}
			return $class;
		}, 10, 2 );
	}
}

// NOTE: Max Qty column is added in product-preset-picker.php
// Removed duplicate column code here to prevent two Max Qty columns
