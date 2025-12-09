<?php
/**
 * Product Dimension Alerts & Warnings
 * 
 * Displays alerts for products missing weight/dimensions
 * Uses USPS standard box sizes as safe fallbacks
 * 
 * @package WTC_Shipping
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get USPS standard box sizes for safe fallbacks
 * These are actual USPS Priority Mail box dimensions
 * 
 * @return array Standard box definitions
 */
function wtcc_get_standard_box_sizes() {
	$saved_boxes = get_option( 'wtcc_standard_box_sizes', array() );
	
	if ( ! empty( $saved_boxes ) && is_array( $saved_boxes ) ) {
		return $saved_boxes;
	}
	
	// Default USPS Priority Mail box sizes (in inches)
	return array(
		'small_flat_rate' => array(
			'name'   => 'Small Flat Rate Box',
			'length' => 8.69,
			'width'  => 5.44,
			'height' => 1.75,
			'max_weight_oz' => 1120, // 70 lbs
		),
		'medium_flat_rate_1' => array(
			'name'   => 'Medium Flat Rate Box (Top Load)',
			'length' => 11.25,
			'width'  => 8.75,
			'height' => 6,
			'max_weight_oz' => 1120,
		),
		'medium_flat_rate_2' => array(
			'name'   => 'Medium Flat Rate Box (Side Load)',
			'length' => 14,
			'width'  => 12,
			'height' => 3.5,
			'max_weight_oz' => 1120,
		),
		'large_flat_rate' => array(
			'name'   => 'Large Flat Rate Box',
			'length' => 12.25,
			'width'  => 12.25,
			'height' => 6,
			'max_weight_oz' => 1120,
		),
		'regional_a' => array(
			'name'   => 'Regional Rate Box A',
			'length' => 10.125,
			'width'  => 7.125,
			'height' => 5,
			'max_weight_oz' => 240, // 15 lbs
		),
		'regional_b' => array(
			'name'   => 'Regional Rate Box B',
			'length' => 12.25,
			'width'  => 10.5,
			'height' => 5.5,
			'max_weight_oz' => 320, // 20 lbs
		),
		'default_safe' => array(
			'name'   => 'Default Safe Box',
			'length' => 12,
			'width'  => 10,
			'height' => 6,
			'max_weight_oz' => 1120,
		),
	);
}

/**
 * Get the safe fallback box for products without dimensions
 * 
 * @return array Box dimensions
 */
function wtcc_get_safe_fallback_box() {
	$fallback_key = get_option( 'wtcc_fallback_box_size', 'default_safe' );
	$boxes = wtcc_get_standard_box_sizes();
	
	return $boxes[ $fallback_key ] ?? $boxes['default_safe'];
}

/**
 * Check if a product is missing critical shipping data
 * 
 * @param WC_Product|int $product Product object or ID.
 * @return array Missing fields with severity
 */
function wtcc_check_product_shipping_data( $product ) {
	if ( is_int( $product ) ) {
		$product = wc_get_product( $product );
	}
	
	if ( ! $product || ! $product->needs_shipping() ) {
		return array();
	}
	
	$issues = array();
	
	// Check weight
	$weight = $product->get_weight();
	if ( empty( $weight ) || $weight <= 0 ) {
		$issues['weight'] = array(
			'field'    => 'Weight',
			'severity' => 'warning',
			'message'  => 'No weight set. Using estimated weight for shipping calculations.',
		);
	}
	
	// Check dimensions
	$length = $product->get_length();
	$width  = $product->get_width();
	$height = $product->get_height();
	
	if ( ( empty( $length ) || $length <= 0 ) && 
		 ( empty( $width ) || $width <= 0 ) && 
		 ( empty( $height ) || $height <= 0 ) ) {
		$issues['dimensions'] = array(
			'field'    => 'Dimensions',
			'severity' => 'info',
			'message'  => 'No dimensions set. Using safe default box size for shipping.',
		);
	} elseif ( empty( $length ) || empty( $width ) || empty( $height ) ) {
		$issues['partial_dimensions'] = array(
			'field'    => 'Dimensions',
			'severity' => 'warning',
			'message'  => 'Some dimensions missing. Please set length, width, and height for accurate shipping.',
		);
	}
	
	return $issues;
}

/**
 * Add shipping data alerts to product edit page
 */
add_action( 'woocommerce_product_options_shipping', 'wtcc_add_shipping_data_alerts', 5 );
function wtcc_add_shipping_data_alerts() {
	global $post;
	
	if ( ! $post ) {
		return;
	}
	
	$product = wc_get_product( $post->ID );
	if ( ! $product ) {
		return;
	}
	
	// Wrapper for dynamic JavaScript updates
	echo '<div id="wtcc-shipping-status-wrapper">';
	
	$issues = wtcc_check_product_shipping_data( $product );
	
	if ( empty( $issues ) ) {
		// All good - show success indicator
		?>
		<div class="wtcc-shipping-status wtcc-status-good" style="
			background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
			border-left: 4px solid #10b981;
			padding: 12px 16px;
			margin: 10px 0 15px 0;
			border-radius: 6px;
			display: flex;
			align-items: center;
			gap: 10px;
		">
			<span class="dashicons dashicons-yes-alt" style="color: #10b981; font-size: 20px;"></span>
			<span style="color: #065f46; font-weight: 500;">Shipping data complete – ready for accurate rate calculations</span>
		</div>
		</div><!-- close #wtcc-shipping-status-wrapper -->
		<?php
		return;
	}
	
	// Show warnings
	foreach ( $issues as $key => $issue ) {
		$bg_color = $issue['severity'] === 'warning' 
			? 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)' 
			: 'linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%)';
		$border_color = $issue['severity'] === 'warning' ? '#f59e0b' : '#3b82f6';
		$icon_color = $issue['severity'] === 'warning' ? '#f59e0b' : '#3b82f6';
		$text_color = $issue['severity'] === 'warning' ? '#92400e' : '#1e40af';
		$icon = $issue['severity'] === 'warning' ? 'dashicons-warning' : 'dashicons-info';
		?>
		<div class="wtcc-shipping-status wtcc-status-<?php echo esc_attr( $issue['severity'] ); ?>" style="
			background: <?php echo esc_attr( $bg_color ); ?>;
			border-left: 4px solid <?php echo esc_attr( $border_color ); ?>;
			padding: 12px 16px;
			margin: 10px 0 15px 0;
			border-radius: 6px;
			display: flex;
			align-items: center;
			gap: 10px;
		">
			<span class="dashicons <?php echo esc_attr( $icon ); ?>" style="color: <?php echo esc_attr( $icon_color ); ?>; font-size: 20px;"></span>
			<span style="color: <?php echo esc_attr( $text_color ); ?>;">
				<strong><?php echo esc_html( $issue['field'] ); ?>:</strong>
				<?php echo esc_html( $issue['message'] ); ?>
			</span>
		</div>
		<?php
	}
	
	// Close wrapper div
	echo '</div>';
}

/**
 * Add column to products list showing shipping status
 */
add_filter( 'manage_edit-product_columns', 'wtcc_add_shipping_status_column', 20 );
function wtcc_add_shipping_status_column( $columns ) {
	$new_columns = array();
	
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		
		// Add after product name
		if ( 'name' === $key ) {
			$new_columns['wtcc_shipping'] = '<span class="dashicons dashicons-car" style="vertical-align: middle; margin-right: 4px;"></span>Shipping Data Status';
		}
	}
	
	return $new_columns;
}

/**
 * Render shipping status column content
 */
add_action( 'manage_product_posts_custom_column', 'wtcc_render_shipping_status_column', 10, 2 );
function wtcc_render_shipping_status_column( $column, $post_id ) {
	if ( 'wtcc_shipping' !== $column ) {
		return;
	}
	
	$product = wc_get_product( $post_id );
	
	if ( ! $product || ! $product->needs_shipping() ) {
		echo '<span class="dashicons dashicons-minus" style="color: #94a3b8;" title="Virtual/Downloadable"></span>';
		return;
	}
	
	$issues = wtcc_check_product_shipping_data( $product );
	
	if ( empty( $issues ) ) {
		echo '<span class="dashicons dashicons-yes-alt" style="color: #10b981;" title="Shipping data complete"></span>';
	} elseif ( isset( $issues['weight'] ) ) {
		echo '<span class="dashicons dashicons-warning" style="color: #f59e0b;" title="Missing weight"></span>';
	} else {
		echo '<span class="dashicons dashicons-info" style="color: #3b82f6;" title="Using default dimensions"></span>';
	}
}

/**
 * Add bulk action to check products for shipping data
 */
add_filter( 'bulk_actions-edit-product', 'wtcc_add_check_shipping_bulk_action' );
function wtcc_add_check_shipping_bulk_action( $actions ) {
	$actions['wtcc_check_shipping'] = 'Check Shipping Data';
	return $actions;
}

/**
 * Handle bulk shipping check action
 */
add_filter( 'handle_bulk_actions-edit-product', 'wtcc_handle_check_shipping_bulk_action', 10, 3 );
function wtcc_handle_check_shipping_bulk_action( $redirect_to, $action, $post_ids ) {
	if ( 'wtcc_check_shipping' !== $action ) {
		return $redirect_to;
	}
	
	$missing_data = 0;
	$complete = 0;
	
	foreach ( $post_ids as $post_id ) {
		$issues = wtcc_check_product_shipping_data( $post_id );
		if ( ! empty( $issues ) ) {
			$missing_data++;
		} else {
			$complete++;
		}
	}
	
	return add_query_arg( array(
		'wtcc_shipping_checked' => 1,
		'wtcc_missing' => $missing_data,
		'wtcc_complete' => $complete,
	), $redirect_to );
}

/**
 * Show admin notice after bulk check
 */
add_action( 'admin_notices', 'wtcc_bulk_check_admin_notice' );
function wtcc_bulk_check_admin_notice() {
	if ( ! isset( $_GET['wtcc_shipping_checked'] ) ) {
		return;
	}
	
	$missing = intval( $_GET['wtcc_missing'] ?? 0 );
	$complete = intval( $_GET['wtcc_complete'] ?? 0 );
	
	$class = $missing > 0 ? 'notice-warning' : 'notice-success';
	
	echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible">';
	echo '<p><strong>Inkfinit Shipping Check:</strong> ';
	echo esc_html( $complete ) . ' products have complete shipping data. ';
	if ( $missing > 0 ) {
		echo '<span style="color: #f59e0b;">' . esc_html( $missing ) . ' products are missing weight or dimensions.</span>';
	}
	echo '</p></div>';
}

/**
 * Add fallback box settings to admin
 */
add_action( 'wtcc_usps_api_settings_after', 'wtcc_fallback_box_settings' );
function wtcc_fallback_box_settings() {
	$boxes = wtcc_get_standard_box_sizes();
	$current = get_option( 'wtcc_fallback_box_size', 'default_safe' );
	?>
	<h2 style="margin-top: 30px;"><?php esc_html_e( 'Default Package Settings', 'wtc-shipping' ); ?></h2>
	<p class="description">When products don't have dimensions set, these defaults are used for accurate USPS rate calculations.</p>
	
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="wtcc_fallback_box_size"><?php esc_html_e( 'Fallback Box Size', 'wtc-shipping' ); ?></label>
			</th>
			<td>
				<select name="wtcc_fallback_box_size" id="wtcc_fallback_box_size" style="min-width: 300px;">
					<?php foreach ( $boxes as $key => $box ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
							<?php echo esc_html( $box['name'] ); ?> 
							(<?php echo esc_html( $box['length'] . '" × ' . $box['width'] . '" × ' . $box['height'] . '"' ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'This box size is used when products are missing dimensions. Choose a size that fits most of your products.', 'wtc-shipping' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="wtcc_fallback_weight"><?php esc_html_e( 'Fallback Weight', 'wtc-shipping' ); ?></label>
			</th>
			<td>
				<input type="number" 
					   name="wtcc_fallback_weight" 
					   id="wtcc_fallback_weight" 
					   value="<?php echo esc_attr( get_option( 'wtcc_fallback_weight', 8 ) ); ?>"
					   min="0.1"
					   step="0.1"
					   style="width: 100px;"> 
				<span><?php echo esc_html( get_option( 'woocommerce_weight_unit', 'oz' ) ); ?></span>
				<p class="description"><?php esc_html_e( 'Default weight for products without weight set. Used only as a last resort.', 'wtc-shipping' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save fallback box settings
 */
add_action( 'wtcc_usps_api_settings_save', 'wtcc_save_fallback_box_settings' );
function wtcc_save_fallback_box_settings() {
	if ( isset( $_POST['wtcc_fallback_box_size'] ) ) {
		update_option( 'wtcc_fallback_box_size', sanitize_key( $_POST['wtcc_fallback_box_size'] ) );
	}
	if ( isset( $_POST['wtcc_fallback_weight'] ) ) {
		update_option( 'wtcc_fallback_weight', floatval( $_POST['wtcc_fallback_weight'] ) );
	}
}
