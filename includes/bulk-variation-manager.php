<?php
/**
 * Bulk Variation Manager
 * Simple WordPress native styling
 * 
 * @package WTC_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all product attributes used in variations
 */
function wtcc_get_variation_attributes() {
	$attributes = array();
	
	// Check if WooCommerce function exists
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return $attributes;
	}
	
	// Get global taxonomy-based attributes
	$taxonomies = wc_get_attribute_taxonomies();
	
	if ( ! empty( $taxonomies ) && is_array( $taxonomies ) ) {
		foreach ( $taxonomies as $tax ) {
			// Verify $tax is an object with required properties
			if ( ! is_object( $tax ) || empty( $tax->attribute_name ) || empty( $tax->attribute_label ) ) {
				continue;
			}
			
			$taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
			if ( ! empty( $taxonomy_name ) ) {
				$attributes[ $taxonomy_name ] = $tax->attribute_label;
			}
		}
	}
	
	// Also get local/custom product attributes used in variations
	global $wpdb;
	$local_attrs = $wpdb->get_col(
		"SELECT DISTINCT meta_key 
		FROM {$wpdb->postmeta} pm
		JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE p.post_type = 'product_variation'
		AND p.post_status = 'publish'
		AND pm.meta_key LIKE 'attribute_%'
		LIMIT 100"
	);
	
	if ( ! empty( $local_attrs ) ) {
		foreach ( $local_attrs as $meta_key ) {
			// Extract attribute name from meta_key (e.g., 'attribute_pa_size' or 'attribute_size')
			$attr_name = str_replace( 'attribute_', '', $meta_key );
			
			// Skip if we already have this as a taxonomy
			if ( isset( $attributes[ $attr_name ] ) ) {
				continue;
			}
			
			// Check if it's a taxonomy attribute (pa_*)
			if ( strpos( $attr_name, 'pa_' ) === 0 && taxonomy_exists( $attr_name ) ) {
				$tax_obj = get_taxonomy( $attr_name );
				if ( $tax_obj ) {
					$attributes[ $attr_name ] = $tax_obj->labels->singular_name ?? ucfirst( str_replace( 'pa_', '', $attr_name ) );
				}
			} else {
				// Local attribute - create a label from the name
				$label = ucfirst( str_replace( array( '_', '-', 'pa_' ), ' ', $attr_name ) );
				$attributes[ $attr_name ] = trim( $label );
			}
		}
	}
	
	return $attributes;
}

/**
 * Get all terms for a given attribute
 */
function wtcc_get_attribute_terms( $attribute ) {
	// Validate attribute is not empty
	if ( empty( $attribute ) ) {
		return array();
	}
	
	// Sanitize the attribute parameter
	$attribute = sanitize_text_field( $attribute );
	
	$result = array();
	
	// Try multiple taxonomy name formats
	$taxonomy_names_to_try = array( $attribute );
	
	// If attribute doesn't start with pa_, also try with pa_ prefix
	if ( strpos( $attribute, 'pa_' ) !== 0 ) {
		$taxonomy_names_to_try[] = 'pa_' . $attribute;
	}
	
	// If attribute starts with pa_, also try without it
	if ( strpos( $attribute, 'pa_' ) === 0 ) {
		$taxonomy_names_to_try[] = str_replace( 'pa_', '', $attribute );
	}
	
	// Try each taxonomy name
	foreach ( $taxonomy_names_to_try as $try_taxonomy ) {
		if ( taxonomy_exists( $try_taxonomy ) ) {
			$terms = get_terms( array(
				'taxonomy'   => $try_taxonomy,
				'hide_empty' => false,
			) );
			
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) && is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( is_object( $term ) && ! empty( $term->slug ) && ! empty( $term->name ) ) {
						$result[ $term->slug ] = $term->name;
					}
				}
				if ( ! empty( $result ) ) {
					return $result;
				}
			}
		}
	}
	
	// Fallback: Get unique values from variation meta (for local/custom attributes)
	global $wpdb;
	
	// Try multiple meta key formats
	$meta_keys_to_try = array(
		'attribute_' . $attribute,
		'attribute_pa_' . $attribute,
	);
	
	if ( strpos( $attribute, 'pa_' ) === 0 ) {
		$meta_keys_to_try[] = 'attribute_' . str_replace( 'pa_', '', $attribute );
	}
	
	$values = array();
	foreach ( $meta_keys_to_try as $meta_key ) {
		$found_values = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT pm.meta_value 
			FROM {$wpdb->postmeta} pm
			JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s 
			AND pm.meta_value != ''
			AND p.post_type = 'product_variation'
			AND p.post_status = 'publish'
			ORDER BY pm.meta_value ASC
			LIMIT 500",
			$meta_key
		) );
		
		if ( ! empty( $found_values ) ) {
			$values = array_merge( $values, $found_values );
		}
	}
	
	$values = array_unique( $values );
	
	if ( ! empty( $values ) ) {
		foreach ( $values as $value ) {
			// Use value as both key and label for local attributes
			$result[ $value ] = ucfirst( $value );
		}
	}
	
	return $result;
}

/**
 * Find all variations with a specific attribute value
 */
function wtcc_find_variations_by_attribute( $attribute, $term_slug ) {
	global $wpdb;
	
	// Handle both custom attributes and taxonomy-based attributes
	$meta_key = 'attribute_' . sanitize_title( $attribute );
	
	// First try exact match for local attributes
	$variation_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} pm
		JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE pm.meta_key = %s 
		AND pm.meta_value = %s
		AND p.post_type = 'product_variation'
		AND p.post_status = 'publish'",
		$meta_key,
		$term_slug
	) );
	
	// If no results, try taxonomy-based approach for global attributes
	if ( empty( $variation_ids ) ) {
		$taxonomy = 'pa_' . sanitize_title( $attribute );
		
		$variation_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
			JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
			JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
			WHERE p.post_type = 'product_variation'
			AND p.post_status = 'publish'
			AND tt.taxonomy = %s
			AND t.slug = %s",
			$taxonomy,
			$term_slug
		) );
	}
	
	// If still no results, try alternative approaches
	if ( empty( $variation_ids ) ) {
		// Try without 'pa_' prefix
		$direct_taxonomy = $attribute;
		
		$variation_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
			JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
			JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
			WHERE p.post_type = 'product_variation'
			AND p.post_status = 'publish'
			AND tt.taxonomy = %s
			AND t.slug = %s",
			$direct_taxonomy,
			$term_slug
		) );
	}
	
	return $variation_ids;
}

/**
 * Bulk update variation prices
 */
function wtcc_bulk_update_variation_prices( $variation_ids, $price_type, $adjustment_type, $amount ) {
	$results = array(
		'updated' => 0,
		'failed'  => 0,
		'details' => array(),
	);
	
	foreach ( $variation_ids as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			$results['failed']++;
			continue;
		}
		
		$old_price = ( $price_type === 'sale' ) ? $variation->get_sale_price() : $variation->get_regular_price();
		$old_price = floatval( $old_price );
		
		switch ( $adjustment_type ) {
			case 'set':
				$new_price = $amount;
				break;
			case 'fixed':
				$new_price = $old_price + $amount;
				break;
			case 'percentage':
				$new_price = $old_price * ( 1 + ( $amount / 100 ) );
				break;
			default:
				$new_price = $old_price;
		}
		
		$new_price = max( 0, round( $new_price, 2 ) );
		
		if ( $price_type === 'sale' ) {
			$variation->set_sale_price( $new_price );
		} else {
			$variation->set_regular_price( $new_price );
		}
		
		$variation->save();
		$results['updated']++;
	}
	
	return $results;
}

/**
 * Bulk update variation stock
 */
function wtcc_bulk_update_variation_stock( $variation_ids, $stock_action, $quantity ) {
	$results = array(
		'updated' => 0,
		'failed'  => 0,
	);
	
	foreach ( $variation_ids as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			$results['failed']++;
			continue;
		}
		
		$variation->set_manage_stock( true );
		$current_stock = $variation->get_stock_quantity();
		
		switch ( $stock_action ) {
			case 'set':
				$new_stock = $quantity;
				break;
			case 'add':
				$new_stock = intval( $current_stock ) + $quantity;
				break;
			case 'subtract':
				$new_stock = intval( $current_stock ) - $quantity;
				break;
			default:
				$new_stock = $current_stock;
		}
		
		$new_stock = max( 0, $new_stock );
		$variation->set_stock_quantity( $new_stock );
		$variation->save();
		$results['updated']++;
	}
	
	return $results;
}

/**
 * Bulk update variation presets
 */
function wtcc_bulk_update_variation_presets( $variation_ids, $preset_key ) {
	$results = array(
		'updated' => 0,
		'failed'  => 0,
		'details' => array(),
	);

	// Get the preset to validate it exists
	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );

	if ( ! isset( $all_presets[ $preset_key ] ) ) {
		$results['failed'] = count( $variation_ids );
		$results['error'] = 'Preset not found';
		return $results;
	}

	$preset = $all_presets[ $preset_key ];

	foreach ( $variation_ids as $variation_id ) {
		try {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
				$results['failed']++;
				continue;
			}

			// Update variation product meta with preset
			update_post_meta( $variation_id, '_wtc_preset', $preset_key );
			update_post_meta( $variation_id, '_wtc_preset_source', 'bulk_updated' );

			// Sync preset data to variation shipping class
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
				$variation->set_shipping_class_id( $term_id );

				// Sync dimensions to term meta
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
				$variation->save();
				$results['updated']++;
			} else {
				$results['failed']++;
			}
		} catch ( Exception $e ) {
			$results['failed']++;
		}
	}

	return $results;
}

/**
 * AJAX: Get attribute terms
 */
add_action( 'wp_ajax_wtcc_get_attribute_terms', 'wtcc_ajax_get_attribute_terms' );
function wtcc_ajax_get_attribute_terms() {
	check_ajax_referer( 'wtcc_variation_manager', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Permission denied', 403 );
	}
	
	$attribute = sanitize_text_field( $_POST['attribute'] ?? '' );
	if ( empty( $attribute ) ) {
		wp_send_json_error( 'Attribute required' );
	}
	
	$terms = wtcc_get_attribute_terms( $attribute );
	wp_send_json_success( $terms );
}

/**
 * AJAX: Preview variations
 */
add_action( 'wp_ajax_wtcc_preview_variation_changes', 'wtcc_ajax_preview_variation_changes' );
function wtcc_ajax_preview_variation_changes() {
	check_ajax_referer( 'wtcc_variation_manager', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Permission denied', 403 );
	}
	
	$attribute = sanitize_text_field( $_POST['attribute'] ?? '' );
	$term = sanitize_text_field( $_POST['term'] ?? '' );
	
	if ( empty( $attribute ) || empty( $term ) ) {
		wp_send_json_error( 'Attribute and term required' );
	}
	
	$variation_ids = wtcc_find_variations_by_attribute( $attribute, $term );
	
	$preview = array();
	foreach ( array_slice( $variation_ids, 0, 50 ) as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( $variation ) {
			$parent = wc_get_product( $variation->get_parent_id() );
			$preview[] = array(
				'id'            => $variation_id,
				'parent_name'   => $parent ? $parent->get_name() : '',
				'variation'     => $variation->get_name(),
				'sku'           => $variation->get_sku(),
				'regular_price' => $variation->get_regular_price(),
				'sale_price'    => $variation->get_sale_price(),
				'stock'         => $variation->get_stock_quantity(),
			);
		}
	}
	
	wp_send_json_success( array(
		'count'   => count( $variation_ids ),
		'preview' => $preview,
	) );
}

/**
 * AJAX: Apply bulk update
 */
add_action( 'wp_ajax_wtcc_apply_variation_changes', 'wtcc_ajax_apply_variation_changes' );
function wtcc_ajax_apply_variation_changes() {
	check_ajax_referer( 'wtcc_variation_manager', 'nonce' );
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Permission denied', 403 );
	}
	
	$attribute = sanitize_text_field( $_POST['attribute'] ?? '' );
	$term = sanitize_text_field( $_POST['term'] ?? '' );
	$action_type = sanitize_key( $_POST['action_type'] ?? '' );
	
	if ( empty( $attribute ) || empty( $term ) || empty( $action_type ) ) {
		wp_send_json_error( 'Missing required fields' );
	}
	
	$variation_ids = wtcc_find_variations_by_attribute( $attribute, $term );
	
	if ( empty( $variation_ids ) ) {
		wp_send_json_error( 'No variations found' );
	}
	
	if ( $action_type === 'price' ) {
		$price_type = sanitize_key( $_POST['price_type'] ?? 'regular' );
		$adjustment_type = sanitize_key( $_POST['adjustment_type'] ?? 'set' );
		$amount = floatval( $_POST['amount'] ?? 0 );
		$results = wtcc_bulk_update_variation_prices( $variation_ids, $price_type, $adjustment_type, $amount );
	} elseif ( $action_type === 'stock' ) {
		$stock_action = sanitize_key( $_POST['stock_action'] ?? 'set' );
		$quantity = intval( $_POST['quantity'] ?? 0 );
		$results = wtcc_bulk_update_variation_stock( $variation_ids, $stock_action, $quantity );
	} elseif ( $action_type === 'preset' ) {
		$preset_key = sanitize_key( $_POST['preset_key'] ?? '' );
		if ( empty( $preset_key ) ) {
			wp_send_json_error( 'Preset key required' );
		}
		$results = wtcc_bulk_update_variation_presets( $variation_ids, $preset_key );
	} else {
		wp_send_json_error( 'Invalid action type' );
	}

	wp_send_json_success( $results );
}

/**
 * Render bulk variation manager page
 */
function wtcc_render_variation_manager_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Unauthorized', 'wtc-shipping' ) );
	}

	if ( function_exists( 'wtcc_is_pro' ) && ! wtcc_is_pro() ) {
		?>
		<div class="wrap">
			<?php wtcc_admin_header( __( 'Bulk Variation Manager', 'wtc-shipping' ) ); ?>
			<div class="notice notice-info" style="margin-top:15px;">
				<p>
					<?php esc_html_e( 'The Bulk Variation Manager is a Pro feature. Upgrade to Inkfinit USPS Shipping Engine Pro to unlock bulk pricing tools, automation, labels, and tracking.', 'wtc-shipping' ); ?>
				</p>
				<p>
					<a href="https://inkfinit.pro/pricing" class="button button-primary" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View Pro Plans', 'wtc-shipping' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
		return;
	}

	$attributes = wtcc_get_variation_attributes();
	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );
	?>
	<div class="wrap">
		<?php wtcc_admin_header( 'Bulk Variation Manager', 'Bulk edit product variation prices, stock, and shipping presets based on attributes.' ); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">

				<!-- Main Content -->
				<div id="post-body-content">
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Step 1: Select Variations', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<p><?php esc_html_e( 'Choose an attribute and a value to select all matching product variations.', 'wtc-shipping' ); ?></p>
							<table class="form-table">
								<tr>
									<th scope="row"><label for="wtcc_attribute"><?php esc_html_e( 'Attribute', 'wtc-shipping' ); ?></label></th>
									<td>
										<select id="wtcc_attribute" name="wtcc_attribute">
											<option value=""><?php esc_html_e( 'Select an attribute...', 'wtc-shipping' ); ?></option>
											<?php foreach ( $attributes as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="wtcc_term"><?php esc_html_e( 'Value', 'wtc-shipping' ); ?></label></th>
									<td>
										<select id="wtcc_term" name="wtcc_term" disabled>
											<option value=""><?php esc_html_e( 'Select an attribute first', 'wtc-shipping' ); ?></option>
										</select>
									</td>
								</tr>
							</table>
							<p>
								<button type="button" id="wtcc_preview_button" class="button button-secondary">
									<?php esc_html_e( 'Preview Matching Variations', 'wtc-shipping' ); ?>
								</button>
								<span class="spinner"></span>
							</p>
						</div>
					</div>

					<div id="wtcc_preview_card" class="postbox">
						<h2 class="hndle">
							<span><?php esc_html_e( 'Step 2: Preview Selection', 'wtc-shipping' ); ?></span>
							<span id="wtcc_preview_count" class="wtcc-preview-count-badge"></span>
						</h2>
						<div class="inside">
							<p><?php esc_html_e( 'Showing up to 50 matching variations. The bulk action will apply to all matched variations.', 'wtc-shipping' ); ?></p>
							<div id="wtcc_preview_table_container">
								<table id="wtcc_preview_table" class="wp-list-table widefat fixed striped">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Product', 'wtc-shipping' ); ?></th>
											<th><?php esc_html_e( 'SKU', 'wtc-shipping' ); ?></th>
											<th><?php esc_html_e( 'Regular Price', 'wtc-shipping' ); ?></th>
											<th><?php esc_html_e( 'Sale Price', 'wtc-shipping' ); ?></th>
											<th><?php esc_html_e( 'Stock', 'wtc-shipping' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<!-- Preview rows will be inserted here -->
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div id="wtcc_action_card" class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Step 3: Choose Bulk Action', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th scope="row"><label for="wtcc_action_type"><?php esc_html_e( 'Action', 'wtc-shipping' ); ?></label></th>
									<td>
										<select id="wtcc_action_type" name="wtcc_action_type">
											<option value=""><?php esc_html_e( 'Select an action...', 'wtc-shipping' ); ?></option>
											<option value="price"><?php esc_html_e( 'Update Price', 'wtc-shipping' ); ?></option>
											<option value="stock"><?php esc_html_e( 'Update Stock', 'wtc-shipping' ); ?></option>
											<option value="preset"><?php esc_html_e( 'Assign Shipping Preset', 'wtc-shipping' ); ?></option>
										</select>
									</td>
								</tr>
							</table>

							<!-- Price Fields -->
							<div id="wtcc_action_price" class="wtcc-action-fields">
								<table class="form-table">
									<tr>
										<th scope="row"><label for="wtcc_price_type"><?php esc_html_e( 'Price Type', 'wtc-shipping' ); ?></label></th>
										<td>
											<select id="wtcc_price_type" name="wtcc_price_type">
												<option value="regular"><?php esc_html_e( 'Regular Price', 'wtc-shipping' ); ?></option>
												<option value="sale"><?php esc_html_e( 'Sale Price', 'wtc-shipping' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wtcc_adjustment_type"><?php esc_html_e( 'Adjustment', 'wtc-shipping' ); ?></label></th>
										<td>
											<select id="wtcc_adjustment_type" name="wtcc_adjustment_type">
												<option value="set"><?php esc_html_e( 'Set to fixed amount', 'wtc-shipping' ); ?></option>
												<option value="fixed"><?php esc_html_e( 'Increase/Decrease by fixed amount', 'wtc-shipping' ); ?></option>
												<option value="percentage"><?php esc_html_e( 'Increase/Decrease by percentage', 'wtc-shipping' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wtcc_amount"><?php esc_html_e( 'Amount', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="number" step="0.01" id="wtcc_amount" name="wtcc_amount" class="small-text" />
											<p class="description"><?php esc_html_e( 'Enter a positive value to increase, negative to decrease. For percentage, use values like 10 or -15.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
								</table>
							</div>

							<!-- Stock Fields -->
							<div id="wtcc_action_stock" class="wtcc-action-fields">
								<table class="form-table">
									<tr>
										<th scope="row"><label for="wtcc_stock_action"><?php esc_html_e( 'Stock Action', 'wtc-shipping' ); ?></label></th>
										<td>
											<select id="wtcc_stock_action" name="wtcc_stock_action">
												<option value="set"><?php esc_html_e( 'Set stock quantity', 'wtc-shipping' ); ?></option>
												<option value="add"><?php esc_html_e( 'Add to stock quantity', 'wtc-shipping' ); ?></option>
												<option value="subtract"><?php esc_html_e( 'Subtract from stock quantity', 'wtc-shipping' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wtcc_quantity"><?php esc_html_e( 'Quantity', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="number" id="wtcc_quantity" name="wtcc_quantity" class="small-text" />
										</td>
									</tr>
								</table>
							</div>

							<!-- Preset Fields -->
							<div id="wtcc_action_preset" class="wtcc-action-fields">
								<table class="form-table">
									<tr>
										<th scope="row"><label for="wtcc_preset_key"><?php esc_html_e( 'Shipping Preset', 'wtc-shipping' ); ?></label></th>
										<td>
											<select id="wtcc_preset_key" name="wtcc_preset_key">
												<option value=""><?php esc_html_e( 'Select a preset...', 'wtc-shipping' ); ?></option>
												<?php foreach ( $all_presets as $key => $preset ) : ?>
													<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $preset['label'] ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
								</table>
							</div>

							<p>
								<button type="button" id="wtcc_apply_button" class="button button-primary">
									<?php esc_html_e( 'Apply Bulk Update', 'wtc-shipping' ); ?>
								</button>
								<span class="spinner"></span>
							</p>
						</div>
					</div>

					<div id="wtcc_results" class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Results', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<!-- Results will be shown here -->
						</div>
					</div>

				</div><!-- #post-body-content -->

				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'How to Use', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<ol>
								<li><strong><?php esc_html_e( 'Select Attribute:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'Choose the product attribute you want to filter by (e.g., Size, Color).', 'wtc-shipping' ); ?></li>
								<li><strong><?php esc_html_e( 'Select Value:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'Choose the specific value for that attribute (e.g., Large, Blue).', 'wtc-shipping' ); ?></li>
								<li><strong><?php esc_html_e( 'Preview:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'Click "Preview" to see which variations will be affected.', 'wtc-shipping' ); ?></li>
								<li><strong><?php esc_html_e( 'Choose Action:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'Select whether to update price, stock, or shipping preset.', 'wtc-shipping' ); ?></li>
								<li><strong><?php esc_html_e( 'Apply Update:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'Click "Apply" to perform the bulk update. This cannot be undone.', 'wtc-shipping' ); ?></li>
							</ol>
						</div>
					</div>
				</div><!-- #postbox-container-1 -->

			</div><!-- #post-body -->
		</div><!-- #poststuff -->
	</div>
	<?php
}

/**
 * Enqueue scripts and styles for bulk variation manager
 */
function wtcc_enqueue_variation_manager_assets( $hook ) {
	if ( 'shipping-engine_page_wtc-variation-manager' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'wtcc-bulk-variation-manager',
		plugin_dir_url( __FILE__ ) . '../assets/admin-bulk-variation-manager.js',
		array( 'jquery' ),
		filemtime( plugin_dir_path( __FILE__ ) . '../assets/admin-bulk-variation-manager.js' ),
		true
	);

	   wp_localize_script( 'wtcc-bulk-variation-manager', 'wtcc_bulk_manager', array(
		   'ajaxurl' => admin_url( 'admin-ajax.php' ),
		   'nonce'    => wp_create_nonce( 'wtcc_variation_manager' ),
		   'i18n'     => array(
			   'loading'        => __( 'Loading…', 'wtc-shipping' ),
			   'select_attribute_first' => __( 'Select attribute first', 'wtc-shipping' ),
			   'select_value'   => __( 'Select value', 'wtc-shipping' ),
			   'no_values_found'=> __( 'No values found', 'wtc-shipping' ),
			   'variations_found'=> __( 'variations found', 'wtc-shipping' ),
			   'showing_preview'=> __( 'showing preview', 'wtc-shipping' ),
			   'no_variations_to_preview'=> __( 'No variations to preview', 'wtc-shipping' ),
			   'confirm_apply'  => __( 'Are you sure you want to apply changes?', 'wtc-shipping' ),
			   'update_complete'=> __( 'Update complete', 'wtc-shipping' ),
			   'variations_updated'=> __( 'variations updated', 'wtc-shipping' ),
			   'failed'         => __( 'failed', 'wtc-shipping' ),
			   'error'          => __( 'An error occurred. Please try again.', 'wtc-shipping' ),
		   ),
	   ) );
}
add_action( 'admin_enqueue_scripts', 'wtcc_enqueue_variation_manager_assets' );
