<?php
/**
 * Label Printer Settings & Configuration
 * Configure printer type, label format, and printing preferences
 * 
 * @package WTC_Shipping
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register label printer settings
 */
add_action( 'admin_init', 'wtcc_register_label_printer_settings' );
function wtcc_register_label_printer_settings() {
	register_setting( 'wtcc_shipping_settings', 'wtcc_label_printer_type' );
	register_setting( 'wtcc_shipping_settings', 'wtcc_label_format' );
	register_setting( 'wtcc_shipping_settings', 'wtcc_label_size' );
	register_setting( 'wtcc_shipping_settings', 'wtcc_include_receipt' );
	register_setting( 'wtcc_shipping_settings', 'wtcc_auto_print_on_create' );
	register_setting( 'wtcc_shipping_settings', 'wtcc_label_save_location' );
}

/**
 * Render label printer settings section
 * Call this from your main settings page
 */
function wtcc_render_label_printer_settings() {
	$printer_type = get_option( 'wtcc_label_printer_type', 'regular' );
	$label_format = get_option( 'wtcc_label_format', 'PDF' );
	$label_size = get_option( 'wtcc_label_size', '4X6' );
	$include_receipt = get_option( 'wtcc_include_receipt', 'yes' );
	$auto_print = get_option( 'wtcc_auto_print_on_create', 'no' );
	$save_location = get_option( 'wtcc_label_save_location', 'order_meta' );
	
	$formats = wtcc_get_available_label_formats();
	?>
	<div class="postbox">
		<div class="postbox-header">
			<h2>🖨️ Label Printer Configuration</h2>
		</div>
		<div class="inside">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="wtcc_label_printer_type"><?php esc_html_e( 'Printer Type', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<select name="wtcc_label_printer_type" id="wtcc_label_printer_type" class="regular-text">
							<option value="regular" <?php selected( $printer_type, 'regular' ); ?>>
								<?php esc_html_e( 'Regular Printer (8.5" x 11")', 'wtc-shipping' ); ?>
							</option>
							<option value="thermal" <?php selected( $printer_type, 'thermal' ); ?>>
								<?php esc_html_e( 'Thermal Label Printer (4" x 6")', 'wtc-shipping' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Choose your primary printer type. This will set optimal defaults for format and size.', 'wtc-shipping' ); ?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="wtcc_label_format"><?php esc_html_e( 'Label Format', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<select name="wtcc_label_format" id="wtcc_label_format" class="regular-text">
							<?php foreach ( $formats['formats'] as $format_key => $format_data ) : ?>
								<option value="<?php echo esc_attr( $format_key ); ?>" <?php selected( $label_format, $format_key ); ?>>
									<?php echo esc_html( $format_data['name'] . ' - ' . $format_data['description'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<strong>PDF:</strong> Works with any printer, easiest option<br>
							<strong>ZPL:</strong> For thermal printers (Zebra, Rollo, etc) - faster printing<br>
							<strong>PNG:</strong> Image format, universal compatibility
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="wtcc_label_size"><?php esc_html_e( 'Label Size', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<select name="wtcc_label_size" id="wtcc_label_size" class="regular-text">
							<?php foreach ( $formats['sizes'] as $size_key => $size_data ) : ?>
								<option value="<?php echo esc_attr( $size_key ); ?>" <?php selected( $label_size, $size_key ); ?>>
									<?php echo esc_html( $size_data['name'] . ' - ' . $size_data['description'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<strong>4" x 6":</strong> Standard thermal label size (recommended)<br>
							<strong>6" x 4":</strong> Landscape orientation<br>
							<strong>8.5" x 11":</strong> Full page for regular printers
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Include Receipt', 'wtc-shipping' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="wtcc_include_receipt" value="yes" <?php checked( $include_receipt, 'yes' ); ?>>
							<?php esc_html_e( 'Include postage receipt with label', 'wtc-shipping' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Adds a receipt section showing postage paid. Recommended for accounting.', 'wtc-shipping' ); ?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Auto-Print', 'wtc-shipping' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="wtcc_auto_print_on_create" value="yes" <?php checked( $auto_print, 'yes' ); ?>>
							<?php esc_html_e( 'Automatically open print dialog when label is created', 'wtc-shipping' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Useful if you print labels immediately after creation.', 'wtc-shipping' ); ?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="wtcc_label_save_location"><?php esc_html_e( 'Save Labels', 'wtc-shipping' ); ?></label>
					</th>
					<td>
						<select name="wtcc_label_save_location" id="wtcc_label_save_location" class="regular-text">
							<option value="order_meta" <?php selected( $save_location, 'order_meta' ); ?>>
								<?php esc_html_e( 'Order Meta Only (database)', 'wtc-shipping' ); ?>
							</option>
							<option value="filesystem" <?php selected( $save_location, 'filesystem' ); ?>>
								<?php esc_html_e( 'File System (wp-uploads/wtc-shipping-labels/)', 'wtc-shipping' ); ?>
							</option>
							<option value="both" <?php selected( $save_location, 'both' ); ?>>
								<?php esc_html_e( 'Both (recommended for backup)', 'wtc-shipping' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Where to store label files for later retrieval.', 'wtc-shipping' ); ?>
						</p>
					</td>
				</tr>
			</table>
			
			<div class="card" style="margin-top: 20px; max-width: 600px;">
				<h3 style="margin-top: 0;">🔧 Supported Thermal Printers</h3>
				<ul style="margin-bottom: 0;">
					<li><strong>Zebra:</strong> ZP450, ZP500, ZP505, GK420d (ZPL format)</li>
					<li><strong>Rollo:</strong> All models (ZPL format)</li>
					<li><strong>DYMO:</strong> 4XL (PDF or PNG format)</li>
					<li><strong>Brother:</strong> QL series (PDF format)</li>
					<li><strong>Generic:</strong> Any thermal printer supporting ZPL or PDF</li>
				</ul>
				<p style="margin: 15px 0 0 0;">
					<em>Note: For best results with thermal printers, use 4"x6" size and ZPL format.</em>
				</p>
			</div>
		</div>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		// Auto-adjust format/size when printer type changes
		$('#wtcc_label_printer_type').on('change', function() {
			var printerType = $(this).val();
			if (printerType === 'thermal') {
				$('#wtcc_label_format').val('ZPL');
				$('#wtcc_label_size').val('4X6');
			} else {
				$('#wtcc_label_format').val('PDF');
				$('#wtcc_label_size').val('8.5X11');
			}
		});
	});
	</script>
	<?php
}

/**
 * Add label printing metabox to order edit page
 */
add_action( 'add_meta_boxes', 'wtcc_add_label_printing_metabox' );
function wtcc_add_label_printing_metabox() {
	$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) 
		&& wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';
		
	add_meta_box(
		'wtcc_label_printing',
		__( '🖨️ USPS Label Printing', 'wtc-shipping' ),
		'wtcc_label_printing_metabox_content',
		$screen,
		'side',
		'high'
	);
}

/**
 * Label printing metabox content
 */
function wtcc_label_printing_metabox_content( $post_or_order ) {
	$order = is_a( $post_or_order, 'WC_Order' ) ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}
	
	$tracking = $order->get_meta( '_wtcc_tracking_number', true );
	$label_format = $order->get_meta( '_wtcc_label_format', true );
	$label_size = $order->get_meta( '_wtcc_label_size', true );
	$postage = $order->get_meta( '_wtcc_postage_paid', true );
	$label_created = $order->get_meta( '_wtcc_label_created', true );
	
	wp_nonce_field( 'wtcc_create_label', 'wtcc_label_nonce' );
	
	if ( $tracking ) {
		// Label exists - show details and download options
		?>
		<div class="wtcc-label-exists" style="padding: 10px 0;">
			<p style="margin: 0 0 10px 0; padding: 10px; background: #d1fae5; border-left: 3px solid #10b981; border-radius: 4px;">
				<strong style="color: #065f46;">✓ Label Created</strong>
			</p>
			
			<p style="margin: 8px 0;">
				<strong><?php esc_html_e( 'Tracking:', 'wtc-shipping' ); ?></strong><br>
				<a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=<?php echo esc_attr( $tracking ); ?>" target="_blank">
					<?php echo esc_html( $tracking ); ?>
				</a>
			</p>
			
			<?php if ( $postage ) : ?>
			<p style="margin: 8px 0;">
				<strong><?php esc_html_e( 'Postage:', 'wtc-shipping' ); ?></strong>
				$<?php echo esc_html( number_format( $postage, 2 ) ); ?>
			</p>
			<?php endif; ?>
			
			<?php if ( $label_format && $label_size ) : ?>
			<p style="margin: 8px 0; font-size: 12px; color: #64748b;">
				<?php echo esc_html( $label_format . ' | ' . $label_size ); ?>
			</p>
			<?php endif; ?>
			
			<?php if ( $label_created ) : ?>
			<p style="margin: 8px 0; font-size: 12px; color: #64748b;">
				<?php echo esc_html( human_time_diff( strtotime( $label_created ), current_time( 'timestamp' ) ) . ' ago' ); ?>
			</p>
			<?php endif; ?>
			
			<p style="margin: 15px 0 0 0;">
				<a href="<?php echo esc_url( wtcc_get_label_download_url( $order->get_id() ) ); ?>" 
				   class="button button-primary" 
				   style="width: 100%; text-align: center; margin-bottom: 8px;">
					<?php esc_html_e( '⬇ Download Label', 'wtc-shipping' ); ?>
				</a>
				
				<button type="button" 
				        class="button wtcc-print-label-btn" 
				        data-order="<?php echo esc_attr( $order->get_id() ); ?>"
				        style="width: 100%; margin-bottom: 8px;">
					<?php esc_html_e( '🖨️ Print Label', 'wtc-shipping' ); ?>
				</button>
				
				<button type="button" 
				        class="button wtcc-recreate-label-btn" 
				        data-order="<?php echo esc_attr( $order->get_id() ); ?>"
				        style="width: 100%;">
					<?php esc_html_e( '🔄 Re-create Label', 'wtc-shipping' ); ?>
				</button>
			</p>
		</div>
		<?php
	} else {
		// No label - show creation options
		$printer_type = get_option( 'wtcc_label_printer_type', 'regular' );
		$default_format = get_option( 'wtcc_label_format', 'PDF' );
		$default_size = get_option( 'wtcc_label_size', '4X6' );
		$formats = wtcc_get_available_label_formats();
		?>
		<div class="wtcc-create-label" style="padding: 10px 0;">
			<p style="margin: 0 0 15px 0;">
				<?php esc_html_e( 'No label created yet. Configure options and create label for this order.', 'wtc-shipping' ); ?>
			</p>
			
			<p style="margin: 8px 0;">
				<label for="wtcc_label_format_select" style="font-weight: 600; display: block; margin-bottom: 4px;">
					<?php esc_html_e( 'Format:', 'wtc-shipping' ); ?>
				</label>
				<select id="wtcc_label_format_select" name="wtcc_label_format_select" style="width: 100%;">
					<?php foreach ( $formats['formats'] as $format_key => $format_data ) : ?>
						<option value="<?php echo esc_attr( $format_key ); ?>" <?php selected( $default_format, $format_key ); ?>>
							<?php echo esc_html( $format_data['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			
			<p style="margin: 8px 0;">
				<label for="wtcc_label_size_select" style="font-weight: 600; display: block; margin-bottom: 4px;">
					<?php esc_html_e( 'Size:', 'wtc-shipping' ); ?>
				</label>
				<select id="wtcc_label_size_select" name="wtcc_label_size_select" style="width: 100%;">
					<?php foreach ( $formats['sizes'] as $size_key => $size_data ) : ?>
						<option value="<?php echo esc_attr( $size_key ); ?>" <?php selected( $default_size, $size_key ); ?>>
							<?php echo esc_html( $size_data['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			
			<p style="margin: 15px 0 0 0;">
				<button type="button" 
				        class="button button-primary wtcc-create-label-btn" 
				        data-order="<?php echo esc_attr( $order->get_id() ); ?>"
				        style="width: 100%;">
					<?php esc_html_e( '🏷️ Create Shipping Label', 'wtc-shipping' ); ?>
				</button>
			</p>
			
			<div class="wtcc-label-info-box" style="margin-top: 15px; padding: 10px; background: #f0f9ff; border-left: 3px solid #3b82f6; border-radius: 4px; font-size: 12px;">
				<strong><?php esc_html_e( 'Your Settings:', 'wtc-shipping' ); ?></strong><br>
				<?php
				printf(
					esc_html__( 'Printer: %s', 'wtc-shipping' ),
					'thermal' === $printer_type ? esc_html__( 'Thermal', 'wtc-shipping' ) : esc_html__( 'Regular', 'wtc-shipping' )
				);
				?>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			$('.wtcc-create-label-btn').on('click', function() {
				var btn = $(this);
				var orderId = btn.data('order');
				var format = $('#wtcc_label_format_select').val();
				var size = $('#wtcc_label_size_select').val();
				
				btn.prop('disabled', true).text('Creating...');
				
				$.post(ajaxurl, {
					action: 'wtcc_create_label',
					order_id: orderId,
					label_format: format,
					label_size: size,
					nonce: '<?php echo wp_create_nonce( 'wtcc_create_label' ); ?>'
				}, function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert('Error: ' + response.data.message);
						btn.prop('disabled', false).text('🏷️ Create Shipping Label');
					}
				});
			});
		});
		</script>
		<?php
	}
}

/**
 * AJAX handler: Create shipping label
 */
add_action( 'wp_ajax_wtcc_create_label', 'wtcc_ajax_create_label' );
function wtcc_ajax_create_label() {
	check_ajax_referer( 'wtcc_create_label', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied' ) );
	}
	
	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$label_format = isset( $_POST['label_format'] ) ? sanitize_text_field( $_POST['label_format'] ) : 'PDF';
	$label_size = isset( $_POST['label_size'] ) ? sanitize_text_field( $_POST['label_size'] ) : '4X6';
	
	if ( ! $order_id ) {
		wp_send_json_error( array( 'message' => 'Invalid order ID' ) );
	}
	
	// Load label printing functions
	require_once dirname( __FILE__ ) . '/usps-label-api.php';
	
	$options = array(
		'label_format' => $label_format,
		'label_size'   => $label_size,
		'receipt'      => get_option( 'wtcc_include_receipt', 'yes' ) === 'yes',
	);
	
	$result = wtcc_create_shipping_label_for_order( $order_id, $options );
	
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 
			'message' => $result->get_error_message(),
			'code'    => $result->get_error_code(),
		) );
	}
	
	wp_send_json_success( array(
		'tracking' => $result['tracking_number'],
		'postage'  => $result['postage'],
		'format'   => $result['label_format'],
	) );
}
