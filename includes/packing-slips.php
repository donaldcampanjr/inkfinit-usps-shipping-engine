<?php
/**
 * Inkfinit Shipping - Packing Slip Generation
 * Print professional packing slips with barcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/admin-ui-helpers.php';

/**
 * Add packing slip bulk action
 */
add_filter( 'bulk_actions-edit-shop_order', 'wtcc_add_packing_slip_bulk_action' );
add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'wtcc_add_packing_slip_bulk_action' );
function wtcc_add_packing_slip_bulk_action( $actions ) {
	$actions['wtcc_print_packing_slips'] = __( 'Print Packing Slips', 'wtc-shipping' );
	return $actions;
}

/**
 * Handle packing slip bulk action
 */
add_filter( 'handle_bulk_actions-edit-shop_order', 'wtcc_handle_packing_slip_bulk_action', 10, 3 );
add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', 'wtcc_handle_packing_slip_bulk_action', 10, 3 );
function wtcc_handle_packing_slip_bulk_action( $redirect_to, $action, $order_ids ) {
	if ( $action !== 'wtcc_print_packing_slips' ) {
		return $redirect_to;
	}
	
	// Store order IDs in transient for the print page
	$print_key = 'wtcc_packing_slips_' . wp_generate_password( 12, false );
	set_transient( $print_key, $order_ids, 300 ); // 5 minutes
	
	// Redirect to print page
	return add_query_arg( array(
		'wtcc_action' => 'print_packing_slips',
		'print_key' => $print_key,
	), admin_url( 'admin.php?page=wtc-core-shipping-packing-slips' ) );
}

/**
 * Register packing slips admin page
 */
add_action( 'admin_menu', 'wtcc_register_packing_slips_page' );
function wtcc_register_packing_slips_page() {
	add_submenu_page(
		null, // Hidden from menu
		__( 'Print Packing Slips', 'wtc-shipping' ),
		__( 'Packing Slips', 'wtc-shipping' ),
		'manage_woocommerce',
		'wtc-core-shipping-packing-slips',
		'wtcc_render_packing_slips_page'
	);
}

/**
 * Render packing slips print page
 */
function wtcc_render_packing_slips_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Unauthorized' );
	}
	
	$print_key = sanitize_text_field( $_GET['print_key'] ?? '' );
	
	if ( empty( $print_key ) ) {
		echo '<div class="wrap"><h1>Print Packing Slips</h1><p>No orders selected.</p></div>';
		return;
	}
	
	$order_ids = get_transient( $print_key );
	
	if ( ! $order_ids || ! is_array( $order_ids ) ) {
		echo '<div class="wrap"><h1>Print Packing Slips</h1><p>Invalid or expired print request.</p></div>';
		return;
	}
	
	// Get company logo
	$logo_url = get_option( 'wtcc_packing_slip_logo', '' );
	$show_logo = ! empty( $logo_url );
	
	// Get options
	$show_prices = get_option( 'wtcc_packing_slip_show_prices', 'yes' ) === 'yes';
	$show_barcode = get_option( 'wtcc_packing_slip_show_barcode', 'yes' ) === 'yes';
	$footer_text = get_option( 'wtcc_packing_slip_footer', 'Thank you for your order!' );
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="UTF-8">
		<title>Packing Slips</title>
		<?php wp_print_styles( 'wtc-shipping-packing-slips' ); ?>
	</head>
	<body>
		<button class="print-button" onclick="window.print()">Print</button>
		<?php foreach ( $order_ids as $order_id ) : ?>
			<?php
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			?>
			<div class="page">
				<div class="header">
					<div class="company-info">
						<?php if ( $show_logo ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="Company Logo" class="company-logo">
						<?php endif; ?>
						<strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong><br>
						<?php echo esc_html( get_option( 'woocommerce_store_address' ) ); ?><br>
						<?php echo esc_html( get_option( 'woocommerce_store_city' ) . ', ' . get_option( 'woocommerce_store_postcode' ) ); ?>
					</div>
					<div class="order-info">
						<h1>Packing Slip</h1>
						<div><strong>Order:</strong> #<?php echo esc_html( $order->get_order_number() ); ?></div>
						<div><strong>Date:</strong> <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></div>
					</div>
				</div>

				<div class="addresses">
					<div class="address-box">
						<h3>Shipping Address</h3>
						<?php echo wp_kses_post( $order->get_formatted_shipping_address( 'N/A' ) ); ?>
					</div>
					<div class="address-box">
						<h3>Billing Address</h3>
						<?php echo wp_kses_post( $order->get_formatted_billing_address( 'N/A' ) ); ?>
					</div>
				</div>

				<table class="items-table">
					<thead>
						<tr>
							<th>SKU</th>
							<th>Product</th>
							<th class="qty">Qty</th>
							<?php if ( $show_prices ) : ?>
								<th class="price">Price</th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $order->get_items() as $item ) : ?>
							<?php
							$product = $item->get_product();
							?>
							<tr>
								<td class="sku"><?php echo esc_html( $product ? $product->get_sku() : '' ); ?></td>
								<td>
									<?php echo esc_html( $item->get_name() ); ?>
									<?php
									$meta_data = $item->get_formatted_meta_data( '_', true );
									if ( ! empty( $meta_data ) ) {
										echo '<div style="font-size: 11px; color: #666;">';
										foreach ( $meta_data as $meta ) {
											echo '<div>' . wp_kses_post( $meta->display_key . ': ' . wp_strip_all_tags( $meta->display_value ) ) . '</div>';
										}
										echo '</div>';
									}
									?>
								</td>
								<td class="qty"><?php echo esc_html( $item->get_quantity() ); ?></td>
								<?php if ( $show_prices ) : ?>
									<td class="price"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $show_barcode ) : ?>
					<div class="barcode">
						<?php echo wtcc_generate_barcode_svg( $order->get_order_number() ); ?>
					</div>
				<?php endif; ?>

				<div class="footer">
					<?php echo esc_html( $footer_text ); ?>
				</div>
			</div>
		<?php endforeach; ?>
	</body>
	</html>
	<?php
	exit;
}

/**
 * Generate barcode SVG using Code 128
 */
function wtcc_generate_barcode_svg( $text ) {
	// Simple Code 128 barcode generator
	// For production, consider using a library like picqer/php-barcode-generator
	
	$width = 2;
	$height = 50;
	$bars = wtcc_encode_code128( $text );
	
	if ( ! $bars ) {
		return '<div>Barcode generation failed</div>';
	}
	
	$svg_width = strlen( $bars ) * $width;
	
	$svg = '<svg width="' . $svg_width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
	$x = 0;
	
	for ( $i = 0; $i < strlen( $bars ); $i++ ) {
		if ( $bars[ $i ] === '1' ) {
			$svg .= '<rect x="' . $x . '" y="0" width="' . $width . '" height="' . $height . '" fill="black"/>';
		}
		$x += $width;
	}
	
	$svg .= '</svg>';
	
	return $svg;
}

/**
 * Encode text to Code 128 barcode pattern
 */
function wtcc_encode_code128( $text ) {
	// Simplified Code 128B encoding
	// Start: 11010010000
	// Stop: 1100011101011
	
	$patterns = array(
		'0' => '11011001100', '1' => '11001101100', '2' => '11001100110', '3' => '10010011000',
		'4' => '10010001100', '5' => '10001001100', '6' => '10011001000', '7' => '10011000100',
		'8' => '10001100100', '9' => '11001001000', 'A' => '11001000100', 'B' => '11000100100',
		'C' => '10110011100', 'D' => '10011011100', 'E' => '10011001110', 'F' => '10111001100',
		'G' => '10011101100', 'H' => '10011100110', 'I' => '11001110010', 'J' => '11001011100',
		'K' => '11001001110', 'L' => '11011100100', 'M' => '11001110100', 'N' => '11101101110',
		'O' => '11101001100', 'P' => '11100101100', 'Q' => '11100100110', 'R' => '11101100100',
		'S' => '11100110100', 'T' => '11100110010', 'U' => '11011011000', 'V' => '11011000110',
		'W' => '11000110110', 'X' => '10100011000', 'Y' => '10001011000', 'Z' => '10001000110',
		' ' => '10110001000', '-' => '10001101000', '_' => '10001100010',
	);
	
	$bars = '11010010000'; // Start B
	
	$text = strtoupper( $text );
	for ( $i = 0; $i < strlen( $text ); $i++ ) {
		$char = $text[ $i ];
		if ( isset( $patterns[ $char ] ) ) {
			$bars .= $patterns[ $char ];
		}
	}
	
	$bars .= '1100011101011'; // Stop
	
	return $bars;
}

/**
 * Menu registration moved to plugin.php main menu function
 */

/**
 * Render packing slip settings page
 */
function wtcc_render_packing_slip_settings_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Unauthorized' );
	}
	
	// Require Pro license for this feature.
	if ( function_exists( 'wtcc_require_license_tier' ) && wtcc_require_license_tier( __( 'Packing Slips', 'wtc-shipping' ), 'pro' ) ) {
		return;
	}
	
	// Handle form submission
	if ( isset( $_POST['wtcc_save_packing_slip_settings'] ) && check_admin_referer( 'wtcc_packing_slip_settings' ) ) {
		update_option( 'wtcc_packing_slip_logo', sanitize_text_field( $_POST['packing_slip_logo'] ?? '' ) );
		update_option( 'wtcc_packing_slip_show_prices', isset( $_POST['show_prices'] ) ? 'yes' : 'no' );
		update_option( 'wtcc_packing_slip_show_barcode', isset( $_POST['show_barcode'] ) ? 'yes' : 'no' );
		update_option( 'wtcc_packing_slip_footer', wp_kses_post( $_POST['footer_text'] ?? '' ) );
		
		echo '<div class="notice notice-success is-dismissible"><p>Settings saved!</p></div>';
	}
	
	$logo_url = get_option( 'wtcc_packing_slip_logo', '' );
	$show_prices = get_option( 'wtcc_packing_slip_show_prices', 'yes' ) === 'yes';
	$show_barcode = get_option( 'wtcc_packing_slip_show_barcode', 'yes' ) === 'yes';
	$footer_text = get_option( 'wtcc_packing_slip_footer', 'Thank you for your order!' );
	?>
	<div class="wrap">
		<?php wtcc_admin_header( 'Packing Slip Settings' ); ?>
		
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Main content -->
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <div class="postbox">
                            <h2 class="hndle"><?php echo wtcc_section_heading( __( 'Customize Your Packing Slips', 'wtc-shipping' ) ); ?></h2>
                            <div class="inside">
                                <form method="post" action="">
                                    <?php wp_nonce_field( 'wtcc_packing_slip_settings' ); ?>
                                    <input type="hidden" name="wtcc_save_packing_slip_settings" value="1">
                                    
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><label for="packing_slip_logo">Logo URL</label></th>
                                            <td>
                                                <input type="url" id="packing_slip_logo" name="packing_slip_logo" 
                                                       value="<?php echo esc_attr( $logo_url ); ?>" 
                                                       class="regular-text">
                                                <p class="description">URL to your company logo (optional)</p>
                                            </td>
                                        </tr>
                                        
                                        <tr>
                                            <th scope="row">Display Options</th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="show_prices" value="1" <?php checked( $show_prices ); ?>>
                                                    Show prices and totals
                                                </label>
                                                <br>
                                                <label>
                                                    <input type="checkbox" name="show_barcode" value="1" <?php checked( $show_barcode ); ?>>
                                                    Show order barcode
                                                </label>
                                            </td>
                                        </tr>
                                        
                                        <tr>
                                            <th scope="row"><label for="footer_text">Footer Text</label></th>
                                            <td>
                                                <textarea id="footer_text" name="footer_text" rows="3" class="large-text"><?php echo esc_textarea( $footer_text ); ?></textarea>
                                                <p class="description">Text to display at the bottom of packing slips</p>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <?php submit_button('Save Settings', 'primary'); ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><?php echo wtcc_section_heading( __( 'How to Use', 'wtc-shipping' ) ); ?></h2>
                        <div class="inside">
                            <ol style="padding-left: 20px;">
                                <li>Go to <strong>WooCommerce → Orders</strong></li>
                                <li>Select one or more orders using checkboxes</li>
                                <li>Choose <strong>"Print Packing Slips"</strong> from the Bulk Actions dropdown</li>
                                <li>Click <strong>Apply</strong></li>
                                <li>A print-ready page will open with all packing slips</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /#postbox-container-1 -->
            </div><!-- /#post-body -->
            <br class="clear">
        </div><!-- /#poststuff -->
	</div>
	<?php
}
