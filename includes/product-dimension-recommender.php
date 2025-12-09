<?php
/**
 * Smart Preset Recommendation System
 * Analyzes product dimensions and suggests best-fit presets or multiple boxes
 * 
 * @package WTC_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add dimension recommendation metabox to product edit screen
 */
add_action( 'add_meta_boxes', 'wtcc_add_dimension_recommender_metabox' );
function wtcc_add_dimension_recommender_metabox() {
	add_meta_box(
		'wtcc_dimension_recommender',
		'📦 Shipping Recommendation',
		'wtcc_render_dimension_recommender',
		'product',
		'side',
		'high'
	);
}

/**
 * Render the dimension recommender metabox
 */
function wtcc_render_dimension_recommender( $post ) {
	$product = wc_get_product( $post->ID );
	
	if ( ! $product ) {
		return;
	}
	
	// Get product dimensions
	$length = $product->get_length();
	$width  = $product->get_width();
	$height = $product->get_height();
	$weight = $product->get_weight();
	
	$dim_unit = get_option( 'woocommerce_dimension_unit', 'in' );
	$weight_unit = get_option( 'woocommerce_weight_unit', 'lbs' );
	
	?>
	<div id="wtc-recommendation-box" style="padding: 12px 0;">
		<?php if ( $length && $width && $height && $weight ) : ?>
			<?php
			// Analyze dimensions and get recommendation
			$recommendation = wtcc_analyze_product_dimensions( $length, $width, $height, $weight, $dim_unit, $weight_unit );
			?>
			
			<div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px; margin-bottom: 12px;">
				<h4 style="margin: 0 0 8px 0; font-size: 13px;">
					<?php echo esc_html( $recommendation['icon'] ); ?> <?php echo esc_html( $recommendation['title'] ); ?>
				</h4>
				<p style="margin: 0; font-size: 12px; line-height: 1.6;">
					<?php echo wp_kses_post( $recommendation['message'] ); ?>
				</p>
			</div>
			
			<?php if ( ! empty( $recommendation['presets'] ) ) : ?>
				<div style="margin-bottom: 12px;">
					<strong style="font-size: 12px; display: block; margin-bottom: 6px;">Recommended Presets:</strong>
					<ul style="margin: 0; padding-left: 20px; font-size: 12px;">
						<?php foreach ( $recommendation['presets'] as $preset ) : ?>
							<li style="margin-bottom: 4px;">
								<strong><?php echo esc_html( $preset['name'] ); ?></strong><br>
								<span style="color: #666;"><?php echo esc_html( $preset['dimensions'] ); ?></span>
								<?php if ( ! empty( $preset['reason'] ) ) : ?>
									<br><em style="color: #2271b1;"><?php echo esc_html( $preset['reason'] ); ?></em>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			
			<div style="font-size: 11px; color: #666; padding-top: 8px; border-top: 1px solid #ddd;">
				<strong>Your Product:</strong> <?php echo esc_html( $length ); ?> × <?php echo esc_html( $width ); ?> × <?php echo esc_html( $height ); ?> <?php echo esc_html( $dim_unit ); ?>, <?php echo esc_html( $weight ); ?> <?php echo esc_html( $weight_unit ); ?>
			</div>
			
		<?php else : ?>
			<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px;">
				<p style="margin: 0; font-size: 12px;">
					<strong>⚠️ Missing Dimensions</strong><br>
					Enter product dimensions and weight in the <strong>Shipping</strong> tab to get smart recommendations.
				</p>
			</div>
		<?php endif; ?>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		// Auto-refresh when dimensions change
		var dimensionFields = $('#product_length, #product_width, #product_height, #_weight');
		
		dimensionFields.on('change keyup', function() {
			// Debounce updates
			clearTimeout(window.wtcDimensionTimer);
			window.wtcDimensionTimer = setTimeout(function() {
				wtcUpdateRecommendation();
			}, 800);
		});
		
		function wtcUpdateRecommendation() {
			var length = $('#product_length').val();
			var width = $('#product_width').val();
			var height = $('#product_height').val();
			var weight = $('#_weight').val();
			
			if (!length || !width || !height || !weight) {
				return;
			}
			
			$('#wtc-recommendation-box').html('<div style="padding: 12px; text-align: center;"><span class="spinner is-active" style="float: none;"></span></div>');
			
			$.post(ajaxurl, {
				action: 'wtcc_get_dimension_recommendation',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wtc_dimension_recommendation' ) ); ?>',
				length: length,
				width: width,
				height: height,
				weight: weight,
				product_id: <?php echo absint( $post->ID ); ?>
			}, function(response) {
				if (response.success && response.data.html) {
					$('#wtc-recommendation-box').html(response.data.html);
				}
			});
		}
	});
	</script>
	<?php
}

/**
 * AJAX handler for dimension recommendations
 */
add_action( 'wp_ajax_wtcc_get_dimension_recommendation', 'wtcc_ajax_get_dimension_recommendation' );
function wtcc_ajax_get_dimension_recommendation() {
	check_ajax_referer( 'wtc_dimension_recommendation', 'nonce' );
	
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}
	
	$length = floatval( $_POST['length'] ?? 0 );
	$width  = floatval( $_POST['width'] ?? 0 );
	$height = floatval( $_POST['height'] ?? 0 );
	$weight = floatval( $_POST['weight'] ?? 0 );
	
	$dim_unit = get_option( 'woocommerce_dimension_unit', 'in' );
	$weight_unit = get_option( 'woocommerce_weight_unit', 'lbs' );
	
	$recommendation = wtcc_analyze_product_dimensions( $length, $width, $height, $weight, $dim_unit, $weight_unit );
	
	ob_start();
	?>
	<div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px; margin-bottom: 12px;">
		<h4 style="margin: 0 0 8px 0; font-size: 13px;">
			<?php echo esc_html( $recommendation['icon'] ); ?> <?php echo esc_html( $recommendation['title'] ); ?>
		</h4>
		<p style="margin: 0; font-size: 12px; line-height: 1.6;">
			<?php echo wp_kses_post( $recommendation['message'] ); ?>
		</p>
	</div>
	
	<?php if ( ! empty( $recommendation['presets'] ) ) : ?>
		<div style="margin-bottom: 12px;">
			<strong style="font-size: 12px; display: block; margin-bottom: 6px;">Recommended Presets:</strong>
			<ul style="margin: 0; padding-left: 20px; font-size: 12px;">
				<?php foreach ( $recommendation['presets'] as $preset ) : ?>
					<li style="margin-bottom: 4px;">
						<strong><?php echo esc_html( $preset['name'] ); ?></strong><br>
						<span style="color: #666;"><?php echo esc_html( $preset['dimensions'] ); ?></span>
						<?php if ( ! empty( $preset['reason'] ) ) : ?>
							<br><em style="color: #2271b1;"><?php echo esc_html( $preset['reason'] ); ?></em>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	
	<div style="font-size: 11px; color: #666; padding-top: 8px; border-top: 1px solid #ddd;">
		<strong>Your Product:</strong> <?php echo esc_html( $length ); ?> × <?php echo esc_html( $width ); ?> × <?php echo esc_html( $height ); ?> <?php echo esc_html( $dim_unit ); ?>, <?php echo esc_html( $weight ); ?> <?php echo esc_html( $weight_unit ); ?>
	</div>
	<?php
	
	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}

/**
 * Analyze product dimensions and recommend presets/boxes
 */
function wtcc_analyze_product_dimensions( $length, $width, $height, $weight, $dim_unit = 'in', $weight_unit = 'lbs' ) {
	// Convert to inches for consistent analysis
	$length_in = wtcc_convert_to_inches( $length, $dim_unit );
	$width_in  = wtcc_convert_to_inches( $width, $dim_unit );
	$height_in = wtcc_convert_to_inches( $height, $dim_unit );
	$weight_oz = wtcc_convert_to_oz( $weight, $weight_unit );
	
	// Get all available presets
	$presets = wtcc_shipping_get_presets();
	
	// Find matching presets
	$matches = array();
	$partial_matches = array();
	
	foreach ( $presets as $key => $preset ) {
		// Parse preset dimensions
		if ( empty( $preset['dimensions'] ) ) {
			continue;
		}
		
		$dims = wtcc_parse_preset_dimensions( $preset['dimensions'] );
		if ( ! $dims ) {
			continue;
		}
		
		// Check if product fits
		$fits = wtcc_product_fits_in_box( 
			$length_in, $width_in, $height_in, 
			$dims['length'], $dims['width'], $dims['height'] 
		);
		
		$weight_fits = true;
		if ( ! empty( $preset['max_weight'] ) ) {
			$preset_weight_oz = wtcc_convert_to_oz( $preset['max_weight'], $preset['weight_unit'] ?? 'lbs' );
			$weight_fits = $weight_oz <= $preset_weight_oz;
		}
		
		if ( $fits && $weight_fits ) {
			$matches[] = array(
				'name'       => $preset['label'] ?? $key,
				'dimensions' => $preset['dimensions'],
				'reason'     => 'Perfect fit',
				'score'      => wtcc_calculate_fit_score( $length_in, $width_in, $height_in, $dims ),
			);
		} elseif ( $fits && ! $weight_fits ) {
			$partial_matches[] = array(
				'name'       => $preset['label'] ?? $key,
				'dimensions' => $preset['dimensions'],
				'reason'     => 'Fits dimensions but exceeds weight limit',
				'score'      => 0,
			);
		}
	}
	
	// Sort matches by best fit (smallest wasted space)
	usort( $matches, function( $a, $b ) {
		return $b['score'] <=> $a['score'];
	} );
	
	// Build recommendation
	if ( empty( $matches ) && empty( $partial_matches ) ) {
		return array(
			'icon'    => '⚠️',
			'title'   => 'Custom Box Needed',
			'message' => 'This product doesn\'t fit any preset boxes. You may need a custom box or split shipment.',
			'presets' => array(),
		);
	}
	
	if ( ! empty( $matches ) ) {
		return array(
			'icon'    => '✅',
			'title'   => count( $matches ) === 1 ? 'Perfect Match Found' : 'Multiple Options Available',
			'message' => 'We found ' . count( $matches ) . ' preset box' . ( count( $matches ) > 1 ? 'es' : '' ) . ' that fit this product.',
			'presets' => array_slice( $matches, 0, 3 ), // Top 3 recommendations
		);
	}
	
	return array(
		'icon'    => '⚠️',
		'title'   => 'Weight Limit Exceeded',
		'message' => 'Product fits dimensionally but exceeds weight limits. Consider multiple boxes or flat rate shipping.',
		'presets' => array_slice( $partial_matches, 0, 2 ),
	);
}

/**
 * Check if product fits in box (any orientation)
 */
function wtcc_product_fits_in_box( $p_length, $p_width, $p_height, $b_length, $b_width, $b_height ) {
	$product = array( $p_length, $p_width, $p_height );
	$box = array( $b_length, $b_width, $b_height );
	
	sort( $product );
	sort( $box );
	
	return $product[0] <= $box[0] && $product[1] <= $box[1] && $product[2] <= $box[2];
}

/**
 * Calculate fit score (lower wasted space = higher score)
 */
function wtcc_calculate_fit_score( $p_length, $p_width, $p_height, $box_dims ) {
	$product_volume = $p_length * $p_width * $p_height;
	$box_volume = $box_dims['length'] * $box_dims['width'] * $box_dims['height'];
	
	if ( $box_volume == 0 ) {
		return 0;
	}
	
	// Higher score = less wasted space
	return ( $product_volume / $box_volume ) * 100;
}

/**
 * Parse preset dimensions string (e.g., "12 × 10 × 8")
 */
function wtcc_parse_preset_dimensions( $dimensions ) {
	$dimensions = preg_replace( '/[^0-9.×x ]/', '', $dimensions );
	$parts = preg_split( '/[×x]/', $dimensions );
	
	if ( count( $parts ) < 3 ) {
		return false;
	}
	
	return array(
		'length' => floatval( trim( $parts[0] ) ),
		'width'  => floatval( trim( $parts[1] ) ),
		'height' => floatval( trim( $parts[2] ) ),
	);
}

/**
 * Convert dimension to inches
 */
function wtcc_convert_to_inches( $value, $from_unit ) {
	$value = floatval( $value );
	
	switch ( strtolower( $from_unit ) ) {
		case 'cm':
			return $value / 2.54;
		case 'm':
			return $value * 39.3701;
		case 'mm':
			return $value / 25.4;
		case 'yd':
			return $value * 36;
		default: // inches
			return $value;
	}
}

/**
 * Convert weight to ounces
 */
function wtcc_convert_to_oz( $value, $from_unit ) {
	$value = floatval( $value );
	
	switch ( strtolower( $from_unit ) ) {
		case 'kg':
			return $value * 35.274;
		case 'g':
			return $value / 28.35;
		case 'lbs':
		case 'lb':
			return $value * 16;
		default: // oz
			return $value;
	}
}
