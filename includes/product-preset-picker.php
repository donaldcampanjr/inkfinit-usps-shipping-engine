<?php
/**
 * UNIFIED PRESET SYSTEM
 * Single source of truth for shipping configuration
 * Pick preset → auto-fills weight/dimensions → updates status → saves
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MAIN PRESET SELECTOR PANEL
 * This is the ONE place to configure shipping - everything else auto-fills
 */
add_action( 'woocommerce_product_data_panels', 'wtcc_render_preset_selector_panel' );
function wtcc_render_preset_selector_panel() {
	global $post;
	$product_id = $post->ID;
	$current_preset = get_post_meta( $product_id, '_wtc_preset', true );
	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );
	
	// Get current product data for display
	$product = wc_get_product( $product_id );
	$has_data = $product && $product->get_weight() && $product->get_length() && $product->get_width() && $product->get_height();
	?>
	<div id="wtcc_preset_panel" class="panel woocommerce_options_panel">
		
		<!-- Quick Start Instructions -->
		<div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; padding: 15px 20px; margin: 15px 12px;">
			<h3 style="margin: 0 0 8px 0; font-size: 14px; color: #1e40af;">📦 Quick Setup</h3>
			<p style="margin: 0; color: #1e40af; font-size: 13px;">
				Select a preset below to <strong>automatically fill</strong> weight and dimensions. That's it!
			</p>
		</div>
		
		<div class="options_group">
			<p class="form-field">
				<label for="wtcc_preset_select" style="font-weight: 600; font-size: 14px;">Select Shipping Preset</label>
				<select id="wtcc_preset_select" name="wtcc_preset" style="width: 100%; max-width: 400px; padding: 8px; font-size: 14px;">
					<option value="">-- Choose a preset to auto-fill shipping data --</option>
					<?php foreach ( $all_presets as $key => $preset ) : 
						$weight_display = ( $preset['weight'] ?? 0 ) . ' ' . ( $preset['unit'] ?? 'oz' );
						$dims_display = ( $preset['length'] ?? 0 ) . '×' . ( $preset['width'] ?? 0 ) . '×' . ( $preset['height'] ?? 0 ) . '"';
					?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_preset, $key ); ?>>
							<?php echo esc_html( ( $preset['label'] ?? $preset['class_label'] ?? $key ) . ' (' . $weight_display . ') • ' . $dims_display ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
		
		<!-- Current Status -->
		<div id="wtcc_preset_status" class="options_group" style="padding: 15px 20px; margin: 0 12px 15px;">
			<?php if ( $has_data ) : ?>
				<div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-left: 4px solid #10b981; padding: 12px 16px; border-radius: 6px;">
					<span class="dashicons dashicons-yes-alt" style="color: #10b981; font-size: 18px; vertical-align: middle;"></span>
					<span style="color: #065f46; font-weight: 500; vertical-align: middle;">
						✓ Shipping data set
						<?php if ( $current_preset && isset( $all_presets[ $current_preset ] ) ) : ?>
							(<?php echo esc_html( $all_presets[ $current_preset ]['label'] ?? $current_preset ); ?>)
						<?php endif; ?>
					</span>
				</div>
			<?php else : ?>
				<div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 6px;">
					<span class="dashicons dashicons-warning" style="color: #f59e0b; font-size: 18px; vertical-align: middle;"></span>
					<span style="color: #92400e; vertical-align: middle;">
						<strong>Action needed:</strong> Select a preset above to set shipping data
					</span>
				</div>
			<?php endif; ?>
		</div>
		
	</div>
	<?php
}

/**
 * ADD PRESET TAB TO PRODUCT DATA - FIRST POSITION
 * Priority 1 ensures it appears before all other tabs
 */
add_filter( 'woocommerce_product_data_tabs', 'wtcc_add_preset_tab', 1 );
function wtcc_add_preset_tab( $tabs ) {
	// Create new tabs array with Shipping Preset first
	$preset_tab = array(
		'wtcc_preset' => array(
			'label'    => '📦 Shipping Preset',
			'target'   => 'wtcc_preset_panel',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 1,
		),
	);
	// Merge preset tab first, then all others
	return array_merge( $preset_tab, $tabs );
}

/**
 * AJAX: Auto-fill fields when preset selected
 */
add_action( 'wp_ajax_wtcc_apply_preset', 'wtcc_ajax_apply_preset' );
function wtcc_ajax_apply_preset() {
	check_ajax_referer( 'wtcc_preset_nonce', 'nonce' );

	$product_id = intval( $_POST['product_id'] ?? 0 );
	$preset_key = sanitize_text_field( $_POST['preset_key'] ?? '' );

	if ( ! $product_id || ! $preset_key ) {
		wp_send_json_error( 'Missing data' );
	}

	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );

	if ( ! isset( $all_presets[ $preset_key ] ) ) {
		wp_send_json_error( 'Preset not found' );
	}

	$preset = $all_presets[ $preset_key ];

	// Auto-fill product fields
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		wp_send_json_error( 'Product not found' );
	}

	$weight_unit = get_option( 'woocommerce_weight_unit', 'lbs' );
	$dim_unit = get_option( 'woocommerce_dimension_unit', 'in' );

	// Convert preset weight to product unit
	$preset_weight = $preset['weight'] ?? 0;
	$preset_unit = $preset['unit'] ?? 'oz';
	
	if ( $preset_unit === 'lb' && $weight_unit === 'oz' ) {
		$product_weight = $preset_weight * 16;
	} elseif ( $preset_unit === 'oz' && $weight_unit === 'lbs' ) {
		$product_weight = $preset_weight / 16;
	} else {
		$product_weight = $preset_weight;
	}

	// Set product properties AND update meta directly for immediate effect
	$product->set_weight( $product_weight );
	$product->set_length( $preset['length'] ?? 0 );
	$product->set_width( $preset['width'] ?? 0 );
	$product->set_height( $preset['height'] ?? 0 );
	
	// Set shipping class if preset specifies one
	$shipping_class_slug = $preset['class'] ?? '';
	$shipping_class_id = 0;
	if ( ! empty( $shipping_class_slug ) ) {
		// Find or create the shipping class term
		$term = get_term_by( 'slug', $shipping_class_slug, 'product_shipping_class' );
		if ( ! $term ) {
			// Try to create the shipping class
			$class_label = $preset['class_label'] ?? ucfirst( $shipping_class_slug );
			$class_desc = $preset['class_desc'] ?? '';
			$result = wp_insert_term( $class_label, 'product_shipping_class', array(
				'slug'        => $shipping_class_slug,
				'description' => $class_desc,
			) );
			if ( ! is_wp_error( $result ) ) {
				$shipping_class_id = $result['term_id'];
			}
		} else {
			$shipping_class_id = $term->term_id;
		}
		
		if ( $shipping_class_id ) {
			$product->set_shipping_class_id( $shipping_class_id );
		}
	}

	// Save product
	$product->save();
	
	// FORCE update meta directly to ensure immediate visibility
	update_post_meta( $product_id, '_weight', strval( $product_weight ) );
	update_post_meta( $product_id, '_length', strval( $preset['length'] ?? 0 ) );
	update_post_meta( $product_id, '_width', strval( $preset['width'] ?? 0 ) );
	update_post_meta( $product_id, '_height', strval( $preset['height'] ?? 0 ) );

	// Save preset reference
	update_post_meta( $product_id, '_wtc_preset', $preset_key );
	
	error_log( "PRESET APPLIED: product_id=$product_id, weight=$product_weight, length=" . ( $preset['length'] ?? 0 ) . ", width=" . ( $preset['width'] ?? 0 ) . ", height=" . ( $preset['height'] ?? 0 ) . ", shipping_class_id=$shipping_class_id" );

	wp_send_json_success( array(
		'message'            => 'Preset applied & saved',
		'weight'             => $product_weight,
		'weight_display'     => $product_weight . ' ' . $weight_unit,
		'length'             => floatval( $preset['length'] ?? 0 ),
		'width'              => floatval( $preset['width'] ?? 0 ),
		'height'             => floatval( $preset['height'] ?? 0 ),
		'dims'               => ( $preset['length'] ?? 0 ) . '×' . ( $preset['width'] ?? 0 ) . '×' . ( $preset['height'] ?? 0 ) . ' in',
		'max_wt'             => ( $preset['max_weight'] ?? 0 ) . ' ' . ( $preset['unit'] ?? 'oz' ),
		'shipping_class_id'  => $shipping_class_id,
		'shipping_class'     => $shipping_class_slug,
	) );
}

/**
 * ENQUEUE PRESET PICKER JAVASCRIPT
 */
add_action( 'admin_enqueue_scripts', 'wtcc_enqueue_preset_picker_assets' );
function wtcc_enqueue_preset_picker_assets( $hook ) {
	// Load on both edit and new product screens
	if ( ! is_admin() || ( strpos( $hook, 'post.php' ) === false && strpos( $hook, 'post-new.php' ) === false ) ) {
		return;
	}

	global $post, $typenow;
	
	// Check post type - works for both edit and new
	$post_type = '';
	if ( $post && isset( $post->post_type ) ) {
		$post_type = $post->post_type;
	} elseif ( $typenow ) {
		$post_type = $typenow;
	} elseif ( isset( $_GET['post_type'] ) ) {
		$post_type = sanitize_key( $_GET['post_type'] );
	}
	
	if ( $post_type !== 'product' ) {
		return;
	}

	// Enqueue admin styles
	wp_enqueue_style(
		'wtcc-preset-picker-admin',
		WTCC_SHIPPING_PLUGIN_URL . 'assets/admin-product-preset-picker.css',
		array(),
		WTCC_SHIPPING_VERSION
	);

	// Enqueue script
	wp_enqueue_script(
		'wtcc-preset-picker',
		WTCC_SHIPPING_PLUGIN_URL . 'assets/preset-picker.js',
		array( 'jquery' ),
		WTCC_SHIPPING_VERSION,
		true
	);

	wp_localize_script( 'wtcc-preset-picker', 'wtccPresetData', array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'wtcc_preset_nonce' ),
		'productId' => $post ? $post->ID : 0,
	) );
}

/**
 * AUTO-SAVE: When product is saved, automatically configure shipping
 * Team just enters product weight in General tab - that's it!
 * 
 * DETECTION LOGIC:
 * 1. If shipping class selected → link to that preset
 * 2. If preset matches existing → link to it
 * 3. If preset modified → create variant
 * 4. If new data → create new preset
 * 
 * Note: WooCommerce handles CSRF protection for this hook automatically
 */
add_action( 'woocommerce_admin_process_product_object', 'wtcc_shipping_auto_configure_from_weight', 20 );
function wtcc_shipping_auto_configure_from_weight( $product ) {
	// Ensure we have a valid product object
	if ( ! $product || ! is_object( $product ) ) {
		return;
	}
	
	$product_id = $product->get_id();
	
	// Get current product data
	$weight = $product->get_weight();
	$length = floatval( $product->get_length() ?? 0 );
	$width = floatval( $product->get_width() ?? 0 );
	$height = floatval( $product->get_height() ?? 0 );
	$weight_unit = get_option( 'woocommerce_weight_unit', 'lbs' );
	$dim_unit = get_option( 'woocommerce_dimension_unit', 'in' );
	
	// Get previously saved preset/class for comparison
	$old_preset_key = get_post_meta( $product_id, '_wtc_preset', true );
	$old_shipping_class_id = get_post_meta( $product_id, '_wtc_previous_shipping_class_id', true );
	
	// SCENARIO 1: User manually selected a shipping class
	if ( isset( $_POST['product_shipping_class'] ) && $_POST['product_shipping_class'] !== '-1' && $_POST['product_shipping_class'] !== '' ) {
		$shipping_class_id = intval( $_POST['product_shipping_class'] );
		
		if ( $shipping_class_id > 0 ) {
			$term = get_term( $shipping_class_id, 'product_shipping_class' );
			if ( $term && ! is_wp_error( $term ) ) {
				// Find matching preset for this class
				$presets = wtcc_shipping_get_presets();
				$matched_preset = null;
				
				foreach ( $presets as $key => $preset ) {
					if ( isset( $preset['class'] ) && $preset['class'] === $term->slug ) {
						$matched_preset = $key;
						break;
					}
				}
				
				// Store selected preset
				update_post_meta( $product_id, '_wtc_preset', $matched_preset ?: '' );
				update_post_meta( $product_id, '_wtc_previous_shipping_class_id', $shipping_class_id );
				update_post_meta( $product_id, '_wtc_preset_source', 'user_selected' );
				
				// Store shipping class data for comparison on next save
				if ( $weight > 0 ) {
					update_post_meta( $product_id, '_wtc_last_preset_data', array(
						'weight'      => $weight,
						'weight_unit' => $weight_unit,
						'length'      => $length,
						'width'       => $width,
						'height'      => $height,
						'dim_unit'    => $dim_unit,
					) );
				}
				
				return; // Don't auto-assign, let user's selection persist
			}
		}
		
		return;
	}
	
	// SCENARIO 2-4: No manual class selection - auto-detect/create preset
	if ( $weight && $weight > 0 ) {
		$weight_oz = wtcc_shipping_convert_to_oz( $weight, $weight_unit );
		$presets = wtcc_shipping_get_presets();
		$custom_presets = wtcc_shipping_get_custom_presets();
		$all_presets = array_merge( $presets, $custom_presets );
		
		// Get last saved data for comparison
		$last_data = get_post_meta( $product_id, '_wtc_last_preset_data', true );
		$data_changed = wtcc_detect_preset_change( $last_data, array(
			'weight'      => $weight,
			'weight_unit' => $weight_unit,
			'length'      => $length,
			'width'       => $width,
			'height'      => $height,
			'dim_unit'    => $dim_unit,
		) );
		
		// SCENARIO 2: Check for exact preset match (weight + dimensions within tolerance)
		$matched_preset_key = wtcc_find_matching_preset( $weight, $weight_unit, $length, $width, $height, $dim_unit );
		
		if ( $matched_preset_key ) {
			// Exact preset match found
			$is_using_preset = $old_preset_key === $matched_preset_key && ! $data_changed;
			
			if ( ! $is_using_preset && $old_preset_key && $data_changed ) {
				// User had different preset before and now matches a standard one
				// This is a "returning to preset" scenario
				wtcc_log_preset_action( $product_id, 'returned_to_preset', array(
					'old_preset' => $old_preset_key,
					'new_preset' => $matched_preset_key,
				) );
			}
			
			update_post_meta( $product_id, '_wtc_preset', $matched_preset_key );
			update_post_meta( $product_id, '_wtc_preset_source', 'matched_existing' );
		} else {
			// SCENARIO 3 & 4: No exact match - need to create or detect variant
			
			// Check if current data is variant of an existing preset
			$variant_of_preset = wtcc_detect_preset_variant( $weight, $weight_unit, $length, $width, $height, $dim_unit );
			
			if ( $variant_of_preset && $old_preset_key !== $variant_of_preset['preset_key'] ) {
				// Data is variant of a preset, but different from what was stored
				// SCENARIO 3: User modified preset - create variant
				
				$variant_key = wtcc_create_preset_variant(
					$variant_of_preset['preset_key'],
					$product->get_name(),
					$weight,
					$weight_unit,
					$length,
					$width,
					$height,
					$dim_unit,
					$all_presets
				);
				
				wtcc_log_preset_action( $product_id, 'preset_variant_created', array(
					'base_preset' => $variant_of_preset['preset_key'],
					'variant_key' => $variant_key,
					'differences' => $variant_of_preset['differences'],
				) );
				
				update_post_meta( $product_id, '_wtc_preset', $variant_key );
				update_post_meta( $product_id, '_wtc_preset_source', 'variant_created' );
				
				// Store creation note
				set_transient( 'wtc_preset_variant_created_' . get_current_user_id(), array(
					'product'    => $product->get_name(),
					'base'       => $variant_of_preset['preset_key'],
					'variant'    => $variant_key,
					'diffs'      => $variant_of_preset['differences'],
				), 45 );
				
			} else {
				// SCENARIO 4: Completely new data - create new preset
				
				$product_name = $product->get_name();
				$preset_key = sanitize_key( $product_name );
				
				// Ensure unique key
				$counter = 1;
				$original_key = $preset_key;
				while ( isset( $all_presets[ $preset_key ] ) ) {
					$preset_key = $original_key . '_' . $counter;
					$counter++;
				}
				
				// Create new preset
				$new_preset = array(
					'label'            => $product_name . ' (' . $weight . ' ' . $weight_unit . ')',
					'weight'           => $weight,
					'unit'             => $weight_unit,
					'length'           => $length,
					'width'            => $width,
					'height'           => $height,
					'dimensions_unit'  => $dim_unit,
					'max_weight'       => $weight,
					'class'            => $preset_key,
					'class_label'      => $product_name,
					'class_desc'       => 'Auto-created: ' . $weight . ' ' . $weight_unit,
					'is_auto_created'  => true,
				);
				
				$custom_presets[ $preset_key ] = $new_preset;
				update_option( 'wtc_custom_presets', $custom_presets );
				
				wtcc_log_preset_action( $product_id, 'preset_created', array(
					'preset_key' => $preset_key,
					'preset'     => $new_preset,
				) );
				
				// Set success message
				set_transient( 'wtc_preset_created_' . get_current_user_id(), array(
					'name'   => $product_name,
					'weight' => $weight . ' ' . $weight_unit,
					'type'   => 'new',
				), 45 );
				
				update_post_meta( $product_id, '_wtc_preset', $preset_key );
				update_post_meta( $product_id, '_wtc_preset_source', 'new_created' );
			}
		}
		
		// Create/assign shipping class
		$preset_key = get_post_meta( $product_id, '_wtc_preset', true );
		
		if ( $preset_key ) {
			$term = term_exists( $preset_key, 'product_shipping_class' );
			if ( ! $term ) {
				$term = wp_insert_term( $product->get_name(), 'product_shipping_class', array(
					'slug'        => $preset_key,
					'description' => 'Auto: ' . $weight . ' ' . $weight_unit,
				) );
			}
			
			if ( ! is_wp_error( $term ) ) {
				$product->set_shipping_class_id( (int) $term['term_id'] );
				
				// Store dimensions and weight in term meta
				$weight_oz = wtcc_shipping_convert_to_oz( $weight, $weight_unit );
				$class_data = array(
					'length'           => $length,
					'width'            => $width,
					'height'           => $height,
					'dimensions_unit'  => $dim_unit,
					'max_weight'       => $weight_oz,
					'preset_key'       => $preset_key,
				);
				wtcc_update_shipping_class_data( $term['term_id'], $class_data );
				
				// Store last data for next comparison
				update_post_meta( $product_id, '_wtc_last_preset_data', array(
					'weight'      => $weight,
					'weight_unit' => $weight_unit,
					'length'      => $length,
					'width'       => $width,
					'height'      => $height,
					'dim_unit'    => $dim_unit,
				) );
			}
		}
	}
}

/**
 * VARIATION PRESET HANDLER
 * When a variation is saved, apply preset auto-detection
 * Same logic as parent products, but for individual variations
 */
add_action( 'woocommerce_admin_process_product_object', 'wtcc_shipping_auto_configure_variation_preset', 21 );
function wtcc_shipping_auto_configure_variation_preset( $product ) {
	// Only process variations
	if ( ! $product || ! $product->is_type( 'variation' ) ) {
		return;
	}

	$product_id = $product->get_id();

	// Get current variation data
	$weight = $product->get_weight();
	$length = floatval( $product->get_length() ?? 0 );
	$width = floatval( $product->get_width() ?? 0 );
	$height = floatval( $product->get_height() ?? 0 );
	$weight_unit = get_option( 'woocommerce_weight_unit', 'lbs' );
	$dim_unit = get_option( 'woocommerce_dimension_unit', 'in' );

	// Skip if no weight
	if ( ! $weight || $weight <= 0 ) {
		return;
	}

	// Check if a shipping class was selected
	if ( isset( $_POST['product_shipping_class'] ) && $_POST['product_shipping_class'] !== '-1' && $_POST['product_shipping_class'] !== '' ) {
		$shipping_class_id = intval( $_POST['product_shipping_class'] );

		if ( $shipping_class_id > 0 ) {
			$term = get_term( $shipping_class_id, 'product_shipping_class' );
			if ( $term && ! is_wp_error( $term ) ) {
				// Find matching preset for this class
				$presets = wtcc_shipping_get_presets();
				$matched_preset = null;

				foreach ( $presets as $key => $preset ) {
					if ( isset( $preset['class'] ) && $preset['class'] === $term->slug ) {
						$matched_preset = $key;
						break;
					}
				}

				// Store preset to variation
				update_post_meta( $product_id, '_wtc_preset', $matched_preset ?: '' );
				update_post_meta( $product_id, '_wtc_preset_source', 'user_selected' );
				return;
			}
		}
	}

	// Auto-detect preset if no manual selection
	$weight_oz = wtcc_shipping_convert_to_oz( $weight, $weight_unit );
	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );

	$current_data = array(
		'weight'      => $weight,
		'weight_unit' => $weight_unit,
		'length'      => $length,
		'width'       => $width,
		'height'      => $height,
		'dim_unit'    => $dim_unit,
	);

	// Try to find exact match
	$matched_preset = wtcc_find_matching_preset( $all_presets, $current_data );

	if ( $matched_preset ) {
		update_post_meta( $product_id, '_wtc_preset', $matched_preset );
		update_post_meta( $product_id, '_wtc_preset_source', 'matched_existing' );
		return;
	}

	// Try to find variant
	$variant_preset = wtcc_detect_preset_variant( $all_presets, $current_data );

	if ( $variant_preset ) {
		$variant_key = 'var_' . md5( $weight_oz . $length . $width . $height );
		update_post_meta( $product_id, '_wtc_preset', $variant_key );
		update_post_meta( $product_id, '_wtc_preset_source', 'variant_created' );
		return;
	}

	// Create new preset for variation if unique enough
	$parent_product = wc_get_product( $product->get_parent_id() );
	if ( $parent_product ) {
		$parent_name = $parent_product->get_name();
		$preset_key = 'var_' . md5( $parent_name . $weight_oz . $length . $width . $height );

		$new_preset = array(
			'label'              => $parent_name . ' Variation',
			'weight'             => $weight,
			'unit'               => $weight_unit,
			'length'             => $length,
			'width'              => $width,
			'height'             => $height,
			'dimensions_unit'    => $dim_unit,
			'max_weight'         => $weight_oz + 2,
			'class'              => $preset_key,
			'class_label'        => $parent_name . ' Variation',
			'class_desc'         => 'Auto-created for variation',
		);

		$custom_presets[ $preset_key ] = $new_preset;
		update_option( 'wtc_custom_presets', $custom_presets );

		update_post_meta( $product_id, '_wtc_preset', $preset_key );
		update_post_meta( $product_id, '_wtc_preset_source', 'variation_created' );
	}
}

/**
 * Detect if data has changed from last save
 *
 * @param array $last_data Previous preset data
 * @param array $current_data Current product data
 * @return bool True if any significant change detected
 */
function wtcc_detect_preset_change( $last_data, $current_data ) {
	if ( ! $last_data || ! is_array( $last_data ) ) {
		return true; // First save or no previous data
	}
	
	$tolerance = 0.5; // Allow small variations (0.5 oz / 0.5 inches)
	
	// Compare weight
	if ( isset( $last_data['weight'] ) && isset( $current_data['weight'] ) ) {
		$last_oz = wtcc_shipping_convert_to_oz( $last_data['weight'], $last_data['weight_unit'] ?? 'lbs' );
		$curr_oz = wtcc_shipping_convert_to_oz( $current_data['weight'], $current_data['weight_unit'] ?? 'lbs' );
		if ( abs( $last_oz - $curr_oz ) > $tolerance ) {
			return true;
		}
	}
	
	// Compare dimensions
	$dim_fields = array( 'length', 'width', 'height' );
	foreach ( $dim_fields as $field ) {
		$last_val = floatval( $last_data[ $field ] ?? 0 );
		$curr_val = floatval( $current_data[ $field ] ?? 0 );
		if ( abs( $last_val - $curr_val ) > $tolerance ) {
			return true;
		}
	}
	
	return false;
}

/**
 * Find matching preset for given weight and dimensions
 * Allows small tolerances for matching
 *
 * @return string|false Preset key if match found
 */
function wtcc_find_matching_preset( $weight, $weight_unit, $length, $width, $height, $dim_unit ) {
	$weight_oz = wtcc_shipping_convert_to_oz( $weight, $weight_unit );
	$presets = wtcc_shipping_get_presets();
	
	foreach ( $presets as $key => $preset ) {
		$preset_oz = wtcc_shipping_convert_to_oz( $preset['weight'], $preset['unit'] );
		
		// Weight must match within 0.1 oz
		if ( abs( $preset_oz - $weight_oz ) > 0.1 ) {
			continue;
		}
		
		// Dimensions must match within 0.5 inches
		$preset_length = floatval( $preset['length'] ?? 0 );
		$preset_width = floatval( $preset['width'] ?? 0 );
		$preset_height = floatval( $preset['height'] ?? 0 );
		
		if ( abs( $preset_length - $length ) > 0.5 ) continue;
		if ( abs( $preset_width - $width ) > 0.5 ) continue;
		if ( abs( $preset_height - $height ) > 0.5 ) continue;
		
		return $key;
	}
	
	return false;
}

/**
 * Check if data is a variant of an existing preset
 * Returns preset info and what changed
 *
 * @return array|false Array with 'preset_key' and 'differences' if variant found
 */
function wtcc_detect_preset_variant( $weight, $weight_unit, $length, $width, $height, $dim_unit ) {
	$weight_oz = wtcc_shipping_convert_to_oz( $weight, $weight_unit );
	$presets = wtcc_shipping_get_presets();
	
	foreach ( $presets as $key => $preset ) {
		$preset_oz = wtcc_shipping_convert_to_oz( $preset['weight'], $preset['unit'] );
		
		// Check if weight is close to preset (within 2 oz)
		if ( abs( $preset_oz - $weight_oz ) > 2.0 ) {
			continue;
		}
		
		// This could be a variant - collect differences
		$differences = array();
		
		if ( abs( $preset_oz - $weight_oz ) > 0.1 ) {
			$differences['weight'] = array(
				'expected' => $preset['weight'] . ' ' . $preset['unit'],
				'actual'   => $weight . ' ' . $weight_unit,
			);
		}
		
		$preset_length = floatval( $preset['length'] ?? 0 );
		$preset_width = floatval( $preset['width'] ?? 0 );
		$preset_height = floatval( $preset['height'] ?? 0 );
		
		if ( abs( $preset_length - $length ) > 0.5 ) {
			$differences['length'] = array(
				'expected' => $preset_length,
				'actual'   => $length,
			);
		}
		if ( abs( $preset_width - $width ) > 0.5 ) {
			$differences['width'] = array(
				'expected' => $preset_width,
				'actual'   => $width,
			);
		}
		if ( abs( $preset_height - $height ) > 0.5 ) {
			$differences['height'] = array(
				'expected' => $preset_height,
				'actual'   => $height,
			);
		}
		
		// If close enough to be variant (has differences but similar overall)
		if ( ! empty( $differences ) && count( $differences ) <= 2 ) {
			return array(
				'preset_key'   => $key,
				'differences'  => $differences,
			);
		}
	}
	
	return false;
}

/**
 * Create a variant of an existing preset
 * E.g., "T-Shirt" becomes "T-Shirt (Oversized)"
 *
 * @param string $base_preset_key Base preset to variant from
 * @param string $product_name Product name for description
 * @return string New preset key
 */
function wtcc_create_preset_variant( $base_preset_key, $product_name, $weight, $weight_unit, $length, $width, $height, $dim_unit, $all_presets ) {
	$presets = wtcc_shipping_get_presets();
	$base_preset = $presets[ $base_preset_key ] ?? null;
	
	if ( ! $base_preset ) {
		return false;
	}
	
	// Generate variant key: "t-shirt_variant_1"
	$variant_key = $base_preset_key . '_variant';
	$counter = 1;
	$original_variant_key = $variant_key;
	
	while ( isset( $all_presets[ $variant_key ] ) ) {
		$variant_key = $original_variant_key . '_' . $counter;
		$counter++;
	}
	
	// Describe the differences
	$variant_desc = 'Variant of ' . $base_preset['class_label'];
	$differences = array();
	
	$base_oz = wtcc_shipping_convert_to_oz( $base_preset['weight'], $base_preset['unit'] );
	$curr_oz = wtcc_shipping_convert_to_oz( $weight, $weight_unit );
	
	if ( abs( $base_oz - $curr_oz ) > 0.1 ) {
		$differences[] = 'Weight ' . $weight . $weight_unit;
	}
	if ( abs( $base_preset['length'] - $length ) > 0.5 ) {
		$differences[] = 'Length ' . $length . '"';
	}
	if ( abs( $base_preset['width'] - $width ) > 0.5 ) {
		$differences[] = 'Width ' . $width . '"';
	}
	if ( abs( $base_preset['height'] - $height ) > 0.5 ) {
		$differences[] = 'Height ' . $height . '"';
	}
	
	if ( ! empty( $differences ) ) {
		$variant_desc .= ' - ' . implode( ', ', $differences );
	}
	
	// Create variant preset
	$custom_presets = wtcc_shipping_get_custom_presets();
	$custom_presets[ $variant_key ] = array(
		'label'            => $base_preset['class_label'] . ' Variant (' . $weight . ' ' . $weight_unit . ')',
		'weight'           => $weight,
		'unit'             => $weight_unit,
		'length'           => $length,
		'width'            => $width,
		'height'           => $height,
		'dimensions_unit'  => $dim_unit,
		'max_weight'       => $weight,
		'class'            => $variant_key,
		'class_label'      => $base_preset['class_label'] . ' Variant',
		'class_desc'       => $variant_desc,
		'parent_preset'    => $base_preset_key,
		'is_variant'       => true,
	);
	
	update_option( 'wtc_custom_presets', $custom_presets );
	
	return $variant_key;
}

/**
 * Log preset actions for tracking and debugging
 *
 * @param int $product_id Product ID
 * @param string $action Action type (created, variant_created, matched, etc)
 * @param array $data Additional data to log
 */
function wtcc_log_preset_action( $product_id, $action, $data = array() ) {
	$log = get_post_meta( $product_id, '_wtc_preset_actions_log', true );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	
	$log[] = array(
		'timestamp' => current_time( 'mysql' ),
		'action'    => $action,
		'data'      => $data,
		'user_id'   => get_current_user_id(),
	);
	
	// Keep only last 20 actions
	if ( count( $log ) > 20 ) {
		$log = array_slice( $log, -20 );
	}
	
	update_post_meta( $product_id, '_wtc_preset_actions_log', $log );
}


/**
 * Save purchase limit field separately
 * This runs on product save
 */
add_action( 'woocommerce_process_product_meta', 'wtcc_save_purchase_limit_field' );
function wtcc_save_purchase_limit_field( $product_id ) {
	if ( isset( $_POST['_wtc_purchase_limit'] ) ) {
		$limit = absint( $_POST['_wtc_purchase_limit'] );
		if ( $limit > 0 ) {
			update_post_meta( $product_id, '_wtc_purchase_limit', $limit );
		} else {
			delete_post_meta( $product_id, '_wtc_purchase_limit' );
		}
	}
}

/**
 * Add purchase limit field to General tab inventory section
 * This is the ONLY extra field - just max quantity
 */
add_action( 'woocommerce_product_options_inventory_product_data', 'wtcc_add_purchase_limit_field' );
function wtcc_add_purchase_limit_field() {
	global $product_object;
	
	echo '<div class="options_group">';
	woocommerce_wp_text_input( array(
		'id'          => '_wtc_purchase_limit',
		'label'       => 'Max Per Customer',
		'desc_tip'    => true,
		'description' => 'Maximum quantity one customer can buy. Leave blank for unlimited.',
		'type'        => 'number',
		'custom_attributes' => array(
			'step' => '1',
			'min'  => '1',
		),
		'value'       => get_post_meta( $product_object->get_id(), '_wtc_purchase_limit', true ),
	) );
	echo '</div>';
}

/**
 * Show admin notice when shipping is auto-configured
 * ONLY show if product doesn't have a preset selected
 */
add_action( 'admin_notices', 'wtcc_show_auto_config_notice', 20 );
function wtcc_show_auto_config_notice() {
	$screen = get_current_screen();
	if ( $screen && $screen->id === 'product' ) {
		global $post;
		if ( $post ) {
			// Don't show warnings if preset is already selected
			$preset_key = get_post_meta( $post->ID, '_wtc_preset', true );
			if ( $preset_key ) {
				return; // Preset is already set, no warning needed
			}
			
			$weight = get_post_meta( $post->ID, '_weight', true );
			
			if ( $weight && $weight > 0 ) {
				// Only show info if shipping is properly configured
				$shipping_class = get_post_meta( $post->ID, '_product_shipping_class', true );
				if ( $shipping_class ) {
					// All good, no warning needed
					return;
				}
			}
		}
	}
}

/**
 * Show success message when preset auto-created or variant created
 */
add_action( 'admin_notices', 'wtcc_shipping_preset_created_notice' );
function wtcc_shipping_preset_created_notice() {
	$notice_new = get_transient( 'wtc_preset_created_' . get_current_user_id() );
	$notice_variant = get_transient( 'wtc_preset_variant_created_' . get_current_user_id() );
	
	if ( $notice_new ) {
		echo '<div class="notice notice-success is-dismissible">';
		echo '<p><strong>✓ New Preset Created:</strong> "' . esc_html( $notice_new['name'] ) . '" (' . esc_html( $notice_new['weight'] ) . ') is ready for checkout.</p>';
		echo '</div>';
		delete_transient( 'wtc_preset_created_' . get_current_user_id() );
	}
	
	if ( $notice_variant ) {
		$diffs = isset( $notice_variant['diffs'] ) ? $notice_variant['diffs'] : array();
		$diff_text = '';
		
		if ( ! empty( $diffs ) ) {
			$diff_labels = array();
			foreach ( $diffs as $field => $info ) {
				$diff_labels[] = ucfirst( $field ) . ': ' . $info['actual'] . ' (vs ' . $info['expected'] . ')';
			}
			$diff_text = ' — ' . implode( ', ', $diff_labels );
		}
		
		echo '<div class="notice notice-info is-dismissible">';
		echo '<p><strong>ℹ Preset Variant Created:</strong> "' . esc_html( $notice_variant['variant'] ) . '" based on "' . esc_html( $notice_variant['base'] ) . '"' . esc_html( $diff_text ) . '.</p>';
		echo '</div>';
		delete_transient( 'wtc_preset_variant_created_' . get_current_user_id() );
	}
}

/**
 * Display shipping status on product page (for admins)
 */
add_action( 'woocommerce_product_meta_end', 'wtcc_shipping_show_preset_frontend' );
function wtcc_shipping_show_preset_frontend() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $product;
	$weight = wtcc_shipping_get_product_weight_oz( $product );
	$preset_key = get_post_meta( $product->get_id(), '_wtc_preset', true );
	$preset_source = get_post_meta( $product->get_id(), '_wtc_preset_source', true );

	if ( $weight <= 0 ) {
		echo '<div class="wtcc-info-box wtcc-info-box-warning">';
		echo '<p><strong>⚠ No Weight Set:</strong> Add weight in product editor to enable shipping.</p>';
		echo '</div>';
	} else {
		$presets = wtcc_shipping_get_presets();
		$custom_presets = wtcc_shipping_get_custom_presets();
		$all_presets = array_merge( $presets, $custom_presets );
		
		$preset_info = $all_presets[ $preset_key ] ?? null;
		$source_label = wtcc_get_preset_source_label( $preset_source );
		
		echo '<div class="wtcc-info-box wtcc-info-box-success">';
		echo '<p><strong>✓ Shipping Configured:</strong></p>';
		echo '<p class="wtcc-info-box-meta">';
		echo 'Weight: <strong>' . esc_html( number_format( $weight, 2 ) ) . ' oz</strong>';
		
		if ( $preset_info ) {
			echo ' — Preset: <strong>' . esc_html( $preset_info['class_label'] ?? $preset_key ) . '</strong>';
		}
		
		if ( $source_label ) {
			echo ' <span class="wtcc-info-box-source">(' . esc_html( $source_label ) . ')</span>';
		}
		
		echo '</p>';
		
		if ( isset( $preset_info['is_variant'] ) && $preset_info['is_variant'] ) {
			$parent = $all_presets[ $preset_info['parent_preset'] ] ?? null;
			echo '<p class="wtcc-info-box-variant">→ Variant of: ' . esc_html( $parent['class_label'] ?? $preset_info['parent_preset'] ) . '</p>';
		}
		
		echo '</div>';
	}
}

/**
 * Get human-readable label for preset source
 *
 * @param string $source Source code
 * @return string Human-readable label
 */
function wtcc_get_preset_source_label( $source ) {
	$labels = array(
		'user_selected'      => 'User Selected',
		'matched_existing'   => 'Matched Existing',
		'variant_created'    => 'Variant Created',
		'new_created'        => 'New Created',
	);
	
	return $labels[ $source ] ?? '';
}

/**
 * Add Max Quantity column to Products admin table
 */
add_filter( 'manage_edit-product_columns', 'wtcc_add_max_quantity_column', 20 );
function wtcc_add_max_quantity_column( $columns ) {
	// Insert Max Qty column after Stock column
	$new_columns = array();
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'is_in_stock' === $key ) {
			$new_columns['max_quantity'] = 'Max Qty';
		}
	}
	return $new_columns;
}

/**
 * Display Max Quantity column content
 */
add_action( 'manage_product_posts_custom_column', 'wtcc_display_max_quantity_column', 10, 2 );
function wtcc_display_max_quantity_column( $column, $post_id ) {
	if ( 'max_quantity' === $column ) {
		$limit = get_post_meta( $post_id, '_wtc_purchase_limit', true );
		if ( $limit && $limit > 0 ) {
			echo '<span class="wtcc-max-quantity">' . esc_html( $limit ) . ' per order</span>';
		} else {
			echo '<span class="wtcc-max-quantity-none">—</span>';
		}
	}
}

/**
 * Make Max Quantity column sortable
 */
add_filter( 'manage_edit-product_sortable_columns', 'wtcc_make_max_quantity_sortable' );
function wtcc_make_max_quantity_sortable( $columns ) {
	$columns['max_quantity'] = '_wtc_purchase_limit';
	return $columns;
}

/**
 * Enqueue frontend styles for the preset status box.
 */
add_action( 'wp_enqueue_scripts', 'wtcc_enqueue_preset_frontend_assets' );
function wtcc_enqueue_preset_frontend_assets() {
	if ( is_product() ) {
		wp_enqueue_style(
			'wtcc-preset-frontend',
			WTCC_SHIPPING_PLUGIN_URL . 'assets/frontend-product-preset-picker.css',
			array(),
			WTCC_SHIPPING_VERSION
		);
	}
}
