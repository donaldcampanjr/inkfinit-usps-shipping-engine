<?php
/**
 * INTEGRATE PRESETS INTO WOOCOMMERCE SHIPPING CLASSES
 * Shows presets with weight/size data directly in WC Shipping settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ADD PRESETS TO WC SHIPPING SETTINGS PAGE
 * Shows in: WooCommerce → Settings → Shipping → Shipping Classes
 */
add_action( 'woocommerce_admin_field_wtcc_presets_table', 'wtcc_render_presets_in_shipping_settings' );
function wtcc_render_presets_in_shipping_settings() {
	$presets = wtcc_shipping_get_presets();
	$custom_presets = wtcc_shipping_get_custom_presets();
	$all_presets = array_merge( $presets, $custom_presets );

	?>
	<div class="wtcc-presets-in-shipping">
		<h2>Shipping Presets with Dimensions</h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Preset Name</th>
					<th>Weight</th>
					<th>Max Weight</th>
					<th>Dimensions (L×W×H)</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $all_presets as $key => $preset ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $preset['label'] ?? $key ); ?></strong></td>
						<td><?php echo esc_html( $preset['weight'] ?? 0 ); ?> <?php echo esc_html( $preset['unit'] ?? 'oz' ); ?></td>
						<td><?php echo esc_html( $preset['max_weight'] ?? 'N/A' ); ?> <?php echo esc_html( $preset['unit'] ?? 'oz' ); ?></td>
						<td>
							<?php
							$l = $preset['length'] ?? 0;
							$w = $preset['width'] ?? 0;
							$h = $preset['height'] ?? 0;
							echo esc_html( $l . '×' . $w . '×' . $h . ' in' );
							?>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-presets&edit=' . urlencode( $key ) ) ); ?>" class="button button-small">Edit</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-presets' ) ); ?>" class="button button-primary">Manage Presets</a>
		</p>
	</div>
	<?php
}

/**
 * REGISTER PRESETS FIELD IN WC SHIPPING SETTINGS
 */
add_filter( 'woocommerce_shipping_classes_settings', 'wtcc_add_presets_field_to_shipping_settings' );
function wtcc_add_presets_field_to_shipping_settings( $settings ) {
	// Insert presets table at top of shipping settings
	array_unshift( $settings, array(
		'type'  => 'wtcc_presets_table',
		'id'    => 'wtcc_presets_info',
	) );
	return $settings;
}

/**
 * ADD PRESET LINK TO WC SHIPPING CLASSES TAB
 */
add_action( 'woocommerce_shipping_classes_settings_top', 'wtcc_shipping_presets_link_in_wc' );
function wtcc_shipping_presets_link_in_wc() {
	?>
	<div class="notice notice-info inline">
		<p><strong>💡 Tip:</strong> Use <a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-presets' ) ); ?>">Shipping Presets</a> to define weight, dimensions, and rates for product groups. Then assign presets to products for instant shipping calculations.</p>
	</div>
	<?php
}

/**
 * SHOW SHIPPING PRESET DATA ON PRODUCT EDIT
 * Display current preset on product page
 */
add_action( 'woocommerce_product_data_panels', 'wtcc_show_preset_on_product_edit' );
function wtcc_show_preset_on_product_edit() {
	global $post;
	$preset = get_post_meta( $post->ID, '_wtc_preset', true );
	if ( $preset ) {
		$presets = wtcc_shipping_get_presets();
		$custom = wtcc_shipping_get_custom_presets();
		$all = array_merge( $presets, $custom );
		if ( isset( $all[ $preset ] ) ) {
			$preset_data = $all[ $preset ];
			?>
			<div class="notice notice-success inline">
				<p>
					<strong>Preset:</strong> <?php echo esc_html( $preset_data['label'] ?? $preset ); ?><br>
					Weight: <?php echo esc_html( $preset_data['weight'] ?? 0 ); ?> <?php echo esc_html( $preset_data['unit'] ?? 'oz' ); ?> |
					Dims: <?php echo esc_html( $preset_data['length'] ?? 0 ); ?>×<?php echo esc_html( $preset_data['width'] ?? 0 ); ?>×<?php echo esc_html( $preset_data['height'] ?? 0 ); ?> in
				</p>
			</div>
			<?php
		}
	}
}
