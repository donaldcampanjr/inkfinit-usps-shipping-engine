<?php
/**
 * Inkfinit Shipping - Apply Preset Defaults to Products
 * When a preset is selected on a product, automatically fill in dimensions/weight from preset defaults
 * Uses new wtcc_shipping_get_presets() system for full data sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DISABLED: Preset selector moved to dedicated Shipping Preset tab
 * See product-preset-picker.php for the main preset selector
 * Keeping function for backwards compatibility but not hooking it
 */
// add_action( 'woocommerce_product_options_shipping', 'wtcc_add_preset_selector_field' );
function wtcc_add_preset_selector_field() {
	global $product_object;
	
	if ( ! $product_object ) {
		return;
	}

	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );
	$current_preset = get_post_meta( $product_object->get_id(), '_wtc_preset', true );
	
	echo '<div class="options_group">';
	
	// Build options with preset details
	$options = array( '' => '-- Select a Preset (auto-fills below) --' );
	foreach ( $all_presets as $key => $preset ) {
		$weight_unit = $preset['unit'] ?? 'oz';
		$dims = '';
		if ( isset( $preset['length'], $preset['width'], $preset['height'] ) ) {
			$dims = ' • ' . $preset['length'] . '×' . $preset['width'] . '×' . $preset['height'] . '"';
		}
		$options[ $key ] = $preset['class_label'] . ' (' . $preset['weight'] . ' ' . $weight_unit . ')' . $dims;
	}
	
	woocommerce_wp_select( array(
		'id'          => '_wtc_preset_selector',
		'label'       => __( 'Select Preset', 'wtc-shipping' ),
		'description' => __( 'Choose a preset to automatically fill Weight, Length, Width, Height below', 'wtc-shipping' ),
		'desc_tip'    => true,
		'options'     => $options,
		'value'       => $current_preset,
		'class'       => 'wtcc-preset-selector',
	) );
	
	echo '</div>';
	
	// Output JavaScript for instant auto-fill
	wtcc_output_preset_autofill_js( $all_presets );
}

/**
 * Output JavaScript for instant preset auto-fill
 * Fills fields immediately when preset is selected (no save needed)
 */
function wtcc_output_preset_autofill_js( $all_presets ) {
	static $js_output = false;
	
	if ( $js_output ) {
		return;
	}
	$js_output = true;
	
	// Create preset data in JSON format
	$preset_data = array();
	foreach ( $all_presets as $key => $preset ) {
		$preset_data[ $key ] = array(
			'weight'     => floatval( $preset['weight'] ?? 0 ),
			'unit'       => $preset['unit'] ?? 'oz',
			'length'     => floatval( $preset['length'] ?? 0 ),
			'width'      => floatval( $preset['width'] ?? 0 ),
			'height'     => floatval( $preset['height'] ?? 0 ),
			'max_weight' => floatval( $preset['max_weight'] ?? 0 ),
			'class_label' => $preset['class_label'] ?? '',
			'class_desc'  => $preset['class_desc'] ?? '',
		);
	}
	
	// Get shop units
	$shop_weight_unit = get_option( 'woocommerce_weight_unit', 'lbs' );
	$shop_dim_unit = get_option( 'woocommerce_dimension_unit', 'in' );
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		var presetData = <?php echo wp_json_encode( $preset_data ); ?>;
		var shopWeightUnit = '<?php echo esc_attr( $shop_weight_unit ); ?>';
		var shopDimUnit = '<?php echo esc_attr( $shop_dim_unit ); ?>';
		
		// Helper: Convert weight to shop unit
		function convertWeight(weight, fromUnit, toUnit) {
			if (fromUnit === toUnit) return weight;
			
			var toOz = { 'oz': 1, 'lb': 16, 'lbs': 16, 'g': 0.035274, 'kg': 35.274 };
			var fromOz = { 'oz': 1, 'lb': 1/16, 'lbs': 1/16, 'g': 1/0.035274, 'kg': 1/35.274 };
			
			var oz = weight * (toOz[fromUnit.toLowerCase()] || 1);
			return oz * (fromOz[toUnit.toLowerCase()] || 1);
		}
		
		// Handle preset selection
		$('.wtcc-preset-selector').on('change', function() {
			var presetKey = $(this).val();
			
			if (!presetKey || !presetData[presetKey]) {
				return;
			}
			
			var preset = presetData[presetKey];
			
			// Fill weight field
			var weightValue = convertWeight(preset.weight, preset.unit, shopWeightUnit);
			$('input[name="_weight"]').val(weightValue.toFixed(2)).trigger('change');
			
			// Fill dimension fields
			$('input[name="_length"]').val(preset.length.toFixed(1)).trigger('change');
			$('input[name="_width"]').val(preset.width.toFixed(1)).trigger('change');
			$('input[name="_height"]').val(preset.height.toFixed(1)).trigger('change');
			
			// Store preset in hidden field for form submission
			$('#_wtc_preset_selector').data('filled-by-preset', presetKey);
			
			// Visual feedback
			console.log('✓ Preset filled: ' + presetKey);
		});
	});
	</script>
	<?php
}

/**
 * Apply preset when form is submitted
 */
add_action( 'woocommerce_admin_process_product_object', 'wtcc_apply_preset_on_save', 15 );
function wtcc_apply_preset_on_save( $product ) {
	if ( ! $product || ! is_object( $product ) ) {
		return;
	}

	$preset_key = isset( $_POST['_wtc_preset_selector'] ) ? sanitize_key( $_POST['_wtc_preset_selector'] ) : '';
	
	if ( empty( $preset_key ) ) {
		return;
	}

	// Get the preset
	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );
	
	if ( ! isset( $all_presets[ $preset_key ] ) ) {
		return;
	}

	$preset = $all_presets[ $preset_key ];
	
	// Store the selected preset
	update_post_meta( $product->get_id(), '_wtc_preset', $preset_key );
	update_post_meta( $product->get_id(), '_wtc_preset_source', 'user_selected' );
	
	// Show success notice
	set_transient( 'wtc_preset_filled_' . get_current_user_id(), array(
		'preset_key'   => $preset_key,
		'preset_label' => $preset['class_label'] ?? $preset_key,
		'product_name' => $product->get_name(),
	), 45 );
}

/**
 * Show notice when preset auto-fills form
 */
add_action( 'admin_notices', 'wtcc_show_preset_filled_notice' );
function wtcc_show_preset_filled_notice() {
	$screen = get_current_screen();
	if ( $screen && $screen->id !== 'product' ) {
		return;
	}

	$notice = get_transient( 'wtc_preset_filled_' . get_current_user_id() );
	if ( $notice ) {
		echo '<div class="notice notice-success is-dismissible">';
		echo '<p><strong>✓ Preset Applied:</strong> "' . esc_html( $notice['preset_label'] ) . '" - Weight and dimensions auto-filled for "' . esc_html( $notice['product_name'] ) . '"</p>';
		echo '</div>';
		delete_transient( 'wtc_preset_filled_' . get_current_user_id() );
	}
}

/**
 * Hide warning/info messages when a preset is already selected
 * Suppress the "Add product weight" and "Add dimensions" messages
 */
add_action( 'admin_notices', 'wtcc_suppress_preset_warnings', 5 );
function wtcc_suppress_preset_warnings() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'product' ) {
		return;
	}

	global $post;
	if ( ! $post ) {
		return;
	}

	$preset_key = get_post_meta( $post->ID, '_wtc_preset', true );
	
	// If product has a preset selected, don't show the "add weight/dimensions" warnings
	if ( $preset_key ) {
		// Remove WooCommerce shipping notices
		remove_action( 'admin_notices', 'wtcc_show_auto_config_notice' );
	}
}

/**
 * Display applied preset info on product page
 * DISABLED - This is redundant with the shipping status notice already showing preset status
 * The form fields already show the values, no need for duplicate display
 */
// add_action( 'woocommerce_product_options_shipping', 'wtcc_display_applied_preset_info', 8 );
function wtcc_display_applied_preset_info() {
	global $product_object;

	if ( ! $product_object ) {
		return;
	}

	$preset_key = get_post_meta( $product_object->get_id(), '_wtc_preset', true );
	$preset_source = get_post_meta( $product_object->get_id(), '_wtc_preset_source', true );

	if ( $preset_key ) {
		$presets = wtcc_shipping_get_presets();
		$custom_presets = wtcc_shipping_get_custom_presets();
		$all_presets = array_merge( $presets, $custom_presets );
		
		if ( isset( $all_presets[ $preset_key ] ) ) {
			$preset = $all_presets[ $preset_key ];
			$source_label = wtcc_get_preset_source_label( $preset_source );
			
			echo '<div class="options_group">';
			echo '<div style="padding: 12px; background-color: #f0f6fc; border: 1px solid #00a32a; border-radius: 4px; margin-bottom: 12px;">';
			echo '<p style="margin: 0 0 8px 0; color: #00a32a;"><strong>✓ Preset Applied:</strong> ' . esc_html( $preset['class_label'] ?? $preset_key ) . '</p>';
			echo '<small style="color: #666; display: block; line-height: 1.6;">';
			
			echo 'Weight: <strong>' . esc_html( $preset['weight'] . ' ' . $preset['unit'] ) . '</strong><br>';
			
			if ( isset( $preset['length'], $preset['width'], $preset['height'] ) ) {
				echo 'Dimensions: <strong>' . esc_html( $preset['length'] . '×' . $preset['width'] . '×' . $preset['height'] . '"' ) . '</strong><br>';
			}
			
			if ( isset( $preset['max_weight'] ) && $preset['max_weight'] > 0 ) {
				echo 'Max Weight: <strong>' . esc_html( $preset['max_weight'] . ' oz' ) . '</strong>';
			}
			
			if ( $source_label ) {
				echo '<br><span style="color: #999; font-size: 11px;">Source: ' . esc_html( $source_label ) . '</span>';
			}
			
			echo '</small>';
			echo '</div>';
			echo '</div>';
		}
	}
}

/**
 * Ensure shipping class has all preset data synced
 * Called when product is saved
 */
add_action( 'woocommerce_admin_process_product_object', 'wtcc_sync_preset_to_shipping_class', 22 );
function wtcc_sync_preset_to_shipping_class( $product ) {
	if ( ! $product || ! is_object( $product ) ) {
		return;
	}

	$preset_key = get_post_meta( $product->get_id(), '_wtc_preset', true );
	
	if ( ! $preset_key ) {
		return;
	}

	// Get preset
	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );
	
	if ( ! isset( $all_presets[ $preset_key ] ) ) {
		return;
	}

	$preset = $all_presets[ $preset_key ];
	
	// Get or create shipping class
	$term = term_exists( $preset_key, 'product_shipping_class' );
	if ( ! $term ) {
		$term = wp_insert_term( 
			$preset['class_label'] ?? $preset_key, 
			'product_shipping_class', 
			array( 'slug' => $preset_key )
		);
	}
	
	if ( ! is_wp_error( $term ) ) {
		$term_id = is_array( $term ) ? $term['term_id'] : $term;
		$product->set_shipping_class_id( $term_id );
		
		// Sync all preset data to shipping class meta
		$weight_oz = wtcc_shipping_convert_to_oz( $preset['weight'], $preset['unit'] ?? 'oz' );
		
		$class_data = array(
			'length'           => floatval( $preset['length'] ?? 0 ),
			'width'            => floatval( $preset['width'] ?? 0 ),
			'height'           => floatval( $preset['height'] ?? 0 ),
			'dimensions_unit'  => $preset['dimensions_unit'] ?? 'in',
			'max_weight'       => floatval( $preset['max_weight'] ?? $weight_oz ),
			'preset_key'       => $preset_key,
		);
		
		wtcc_update_shipping_class_data( $term_id, $class_data );
	}
}

